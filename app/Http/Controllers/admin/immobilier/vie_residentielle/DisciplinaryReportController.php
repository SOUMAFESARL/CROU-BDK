<?php

namespace App\Http\Controllers\admin\immobilier\vie_residentielle;

use App\Http\Controllers\Controller;
use App\Models\AssignationChambre;
use App\Models\Batiment;
use App\Models\Chambre;
use App\Models\Cite;
use App\Models\DisciplinaryReport;
use App\Models\DisciplinaryReportHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DisciplinaryReportController extends Controller
{
    public function index(Request $request)
    {
        $reports = DisciplinaryReport::with(['etudiant:id_etudiant,name,matricule', 'cite:id_cite,name', 'batiment:id_batiment,name', 'chambre:id_chambre,numero_chambre', 'historique', 'sanction.etudiant'])
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->when($request->filled('gravite'), fn ($q) => $q->where('gravite', $request->string('gravite')))
            ->when($request->filled('source'), fn ($q) => $q->where('source', $request->string('source')))
            ->latest('date_incident')->paginate(15)->withQueryString();
        $assignments = AssignationChambre::actives()->with(['etudiant:id_etudiant,name,matricule', 'chambre:id_chambre,numero_chambre,batiment_id,cite_id'])->get();
        $residents = $assignments->filter(fn ($a) => $a->etudiant && $a->chambre)->map(fn ($a) => ['id'=>$a->etudiant_id,'name'=>$a->etudiant->name,'matricule'=>$a->etudiant->matricule,'chambre_id'=>$a->chambre_id])->values();
        $rooms = $assignments->pluck('chambre')->filter()->unique('id_chambre')->values();
        $buildings = Batiment::whereIn('id_batiment', $rooms->pluck('batiment_id'))->get(['id_batiment','cite_id','name']);
        $cites = Cite::whereIn('id_cite', $rooms->pluck('cite_id'))->get(['id_cite','name']);
        $stats = ['brouillons'=>DisciplinaryReport::whereIn('statut',['brouillon','retourne'])->count(),'transmis'=>DisciplinaryReport::where('statut','transmis')->count(),'en_cours'=>DisciplinaryReport::where('statut','en_cours')->count(),'decides'=>DisciplinaryReport::whereIn('statut',['decision_prise','cloture'])->count()];
        return view('admin.hebergement.vie_residentielle.incidents.index', compact('reports','residents','rooms','buildings','cites','stats'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['cite_id'=>'required|exists:cite,id_cite','batiment_id'=>'required|exists:batiment,id_batiment','chambre_id'=>'required|exists:chambre,id_chambre','etudiant_id'=>'required|exists:etudiants,id_etudiant','date_incident'=>'required|date|before_or_equal:now','lieu'=>'nullable|string|max:255','nature'=>'required|string|max:100','description'=>'required|string|max:5000','temoins'=>'nullable|string|max:2000','gravite'=>'required|in:faible,moyenne,grave,critique','submit_action'=>'required|in:brouillon,transmettre']);
        if (!Batiment::where('id_batiment',$data['batiment_id'])->where('cite_id',$data['cite_id'])->exists()) throw ValidationException::withMessages(['batiment_id'=>'Le bâtiment n’appartient pas à cette cité.']);
        if (!Chambre::where('id_chambre',$data['chambre_id'])->where('batiment_id',$data['batiment_id'])->exists()) throw ValidationException::withMessages(['chambre_id'=>'La chambre n’appartient pas à ce bâtiment.']);
        if (!AssignationChambre::actives()->where('chambre_id',$data['chambre_id'])->where('etudiant_id',$data['etudiant_id'])->exists()) throw ValidationException::withMessages(['etudiant_id'=>'Le résident n’est pas affecté à cette chambre.']);
        $action = $data['submit_action']; unset($data['submit_action']);
        $author = getInfoAdmin()->users->id_user ?? null;
        DB::transaction(function () use ($data,$action,$author) {
            $status = $action === 'transmettre' ? 'transmis' : 'brouillon';
            $report = DisciplinaryReport::create($data + ['statut'=>$status,'transmis_at'=>$status==='transmis'?now():null,'created_by'=>$author]);
            DisciplinaryReportHistory::create(['rapport_id'=>$report->id_rapport,'action'=>$action==='transmettre'?'creation_et_transmission':'creation','nouveau_statut'=>$status,'commentaire'=>$status==='transmis'?'Rapport transmis au Service Sécurité.':'Brouillon enregistré.','auteur_id'=>$author]);
        });
        return back()->with('success', $action === 'transmettre' ? 'Rapport transmis au Service Sécurité.' : 'Brouillon enregistré.');
    }

    public function transition(Request $request, DisciplinaryReport $report)
    {
        abort_unless($request->user('admin')?->hasPermission('hebergement.discipline.update'), 403);
        $data = $request->validate(['action'=>'required|in:transmettre,prendre_en_charge,retourner,decider,cloturer','commentaire'=>'nullable|required_if:action,retourner,decider|string|max:5000','decision'=>'nullable|required_if:action,decider|string|max:5000'] + \App\Http\Services\DisciplinaryImpactService::decisionRules());
        $transitions = ['transmettre'=>[['brouillon','retourne'],'transmis'],'prendre_en_charge'=>[['transmis'],'en_cours'],'retourner'=>[['transmis','en_cours'],'retourne'],'decider'=>[['en_cours'],'decision_prise'],'cloturer'=>[['decision_prise'],'cloture']];
        $author = getInfoAdmin()->users->id_user ?? null;
        DB::transaction(function () use ($report,$data,$transitions,$author) {
            $lockedReport = DisciplinaryReport::query()->lockForUpdate()->findOrFail($report->id_rapport);
            [$allowed,$next] = $transitions[$data['action']];
            if (!in_array($lockedReport->statut,$allowed,true)) throw ValidationException::withMessages(['action'=>'Ce rapport a déjà évolué. Actualisez la page avant de poursuivre.']);
            $old=$lockedReport->statut; $updates=['statut'=>$next,'updated_by'=>$author];
            if($next==='transmis')$updates['transmis_at']=now(); if($next==='en_cours')$updates['pris_en_charge_at']=now(); if($next==='decision_prise'){$updates['decision_at']=now();$updates['decision']=$data['decision'];}
            $lockedReport->update($updates);
            if ($next === 'decision_prise') app(\App\Http\Services\DisciplinaryImpactService::class)->decider($lockedReport, $data, $author);
            DisciplinaryReportHistory::create(['rapport_id'=>$lockedReport->id_rapport,'action'=>$data['action'],'ancien_statut'=>$old,'nouveau_statut'=>$next,'commentaire'=>$data['commentaire']??$data['decision']??null,'auteur_id'=>$author]);
        });
        return back()->with('success','Le workflow du rapport a été mis à jour.');
    }
}
