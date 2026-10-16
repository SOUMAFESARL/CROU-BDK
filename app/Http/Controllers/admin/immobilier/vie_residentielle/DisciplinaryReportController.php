<?php

namespace App\Http\Controllers\admin\immobilier\vie_residentielle;

use Inertia\Inertia;
use App\Services\AdminAccess;
use App\Http\Services\DisciplinaryImpactService;
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
    /** Actions du workflow proposées selon l'état du rapport (mêmes transitions que `transition()`). */
    private const ACTIONS = [
        'brouillon' => ['transmettre'],
        'retourne' => ['transmettre'],
        'transmis' => ['prendre_en_charge', 'retourner'],
        'en_cours' => ['decider', 'retourner'],
        'decision_prise' => ['cloturer'],
    ];

    private const STATUTS = [
        'brouillon' => 'Brouillon', 'transmis' => 'Transmis', 'en_cours' => 'En cours', 'retourne' => 'Retourné',
        'decision_prise' => 'Décision prise', 'cloture' => 'Clôturé',
    ];

    public function index(Request $request)
    {
        $filtres = [
            'statut' => array_key_exists((string) $request->query('statut'), self::STATUTS) ? $request->query('statut') : '',
            'gravite' => in_array($request->query('gravite'), ['faible', 'moyenne', 'grave', 'critique'], true) ? $request->query('gravite') : '',
            'source' => in_array($request->query('source'), ['etudiant', 'administration'], true) ? $request->query('source') : '',
        ];
        $reports = DisciplinaryReport::with(['etudiant:id_etudiant,name,matricule', 'cite:id_cite,name', 'batiment:id_batiment,name', 'chambre:id_chambre,numero_chambre', 'historique', 'sanction.etudiant'])
            ->when($filtres['statut'], fn ($q, $statut) => $q->where('statut', $statut))
            ->when($filtres['gravite'], fn ($q, $gravite) => $q->where('gravite', $gravite))
            ->when($filtres['source'], fn ($q, $source) => $q->where('source', $source))
            ->latest('date_incident')->paginate(10)->withQueryString();

        $access = app(AdminAccess::class);
        $admin = $request->user('admin');
        $url = fn (string $route, array $parametres = []) => $access->allows($admin, $route) ? route($route, $parametres) : null;
        $peutTraiter = $access->allows($admin, 'admin.hebergement.vie_residentielle.incidents.transition');

        $reports->through(fn (DisciplinaryReport $report) => [
            'id' => $report->id_rapport,
            'numero' => $report->numero_rapport,
            'date_incident' => $report->date_incident?->format('Y-m-d H:i'),
            'source' => $report->source === 'etudiant' ? 'Espace étudiant' : 'Administration',
            'confidentiel' => (bool) $report->confidentiel,
            'resident' => $report->etudiant?->name,
            'matricule' => $report->etudiant?->matricule,
            'localisation' => collect([$report->cite?->name, $report->batiment?->name, $report->chambre ? 'Ch. '.$report->chambre->numero_chambre : null])->filter()->implode(' · '),
            'nature' => $report->nature,
            'description' => $report->description,
            'lieu' => $report->lieu,
            'temoins' => $report->temoins,
            'gravite' => $report->gravite,
            'statut' => $report->statut,
            'statut_label' => self::STATUTS[$report->statut] ?? str((string) $report->statut)->replace('_', ' ')->title()->toString(),
            'attente' => $report->statut === 'transmis' && $report->transmis_at?->lt(now()->subDay()) ? $report->transmis_at->diffForHumans(null, true) : null,
            'transmis_at' => $report->transmis_at?->format('Y-m-d H:i'),
            'pris_en_charge_at' => $report->pris_en_charge_at?->format('Y-m-d H:i'),
            'decision' => $report->decision,
            'sanction' => $report->sanction ? [
                'type' => DisciplinaryImpactService::LABELS[$report->sanction->type] ?? $report->sanction->type,
                'etudiant' => $report->sanction->etudiant?->name,
                'statut' => $report->sanction->statut,
                'date_fin' => $report->sanction->date_fin?->toDateString(),
            ] : null,
            'historique' => $report->historique->sortBy('created_at')->map(fn ($item) => [
                'id' => $item->getKey(),
                'statut' => self::STATUTS[$item->nouveau_statut] ?? str((string) $item->nouveau_statut)->replace('_', ' ')->title()->toString(),
                'date' => $item->created_at?->format('Y-m-d H:i'),
                'commentaire' => $item->commentaire,
            ])->values(),
            'actions' => $peutTraiter ? (self::ACTIONS[$report->statut] ?? []) : [],
            'url_transition' => $peutTraiter ? route('admin.hebergement.vie_residentielle.incidents.transition', $report) : null,
        ]);

        $assignments = AssignationChambre::actives()->with(['etudiant:id_etudiant,name,matricule', 'chambre:id_chambre,numero_chambre,batiment_id,cite_id'])->get();
        $residents = $assignments->filter(fn ($a) => $a->etudiant && $a->chambre)->map(fn ($a) => ['id' => $a->etudiant_id, 'name' => $a->etudiant->name, 'matricule' => $a->etudiant->matricule, 'chambre_id' => $a->chambre_id])->values();
        $rooms = $assignments->pluck('chambre')->filter()->unique('id_chambre')->values();
        $buildings = Batiment::whereIn('id_batiment', $rooms->pluck('batiment_id'))->orderBy('name')->get(['id_batiment', 'cite_id', 'name']);
        $cites = Cite::whereIn('id_cite', $rooms->pluck('cite_id'))->orderBy('name')->get(['id_cite', 'name']);

        return Inertia::render('Admin/Hebergement/Discipline/Incidents', [
            'rapports' => $reports,
            'stats' => [
                'brouillons' => DisciplinaryReport::whereIn('statut', ['brouillon', 'retourne'])->count(),
                'transmis' => DisciplinaryReport::where('statut', 'transmis')->count(),
                'en_cours' => DisciplinaryReport::where('statut', 'en_cours')->count(),
                'decides' => DisciplinaryReport::whereIn('statut', ['decision_prise', 'cloture'])->count(),
            ],
            'filters' => $filtres,
            'structure' => [
                'cites' => $cites->map(fn ($cite) => ['id' => $cite->id_cite, 'name' => $cite->name])->values(),
                'batiments' => $buildings->map(fn ($batiment) => ['id' => $batiment->id_batiment, 'cite_id' => $batiment->cite_id, 'name' => $batiment->name])->values(),
                'chambres' => $rooms->sortBy('numero_chambre')->map(fn ($chambre) => ['id' => $chambre->id_chambre, 'batiment_id' => $chambre->batiment_id, 'numero' => $chambre->numero_chambre])->values(),
                'residents' => $residents,
            ],
            'sanctions' => collect(DisciplinaryImpactService::LABELS)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
            'urls' => [
                'index' => route('admin.hebergement.vie_residentielle.incidents.index'),
                'store' => $url('admin.hebergement.vie_residentielle.incidents.store'),
                'sanctions' => $url('admin.hebergement.vie_residentielle.sanctions.index'),
            ],
        ]);
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
