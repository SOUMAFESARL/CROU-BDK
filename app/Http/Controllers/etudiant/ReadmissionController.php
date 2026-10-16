<?php
namespace App\Http\Controllers\etudiant;
use App\Http\Controllers\Controller;
use App\Http\Services\ReadmissionService;
use App\Models\{Campagne,NiveauEtude};
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReadmissionController extends Controller
{
    private function student(Request $request)
    {
        $student=$request->attributes->get('student') ?? auth('etudiant')->user();
        abort_unless($student,401);
        return $student;
    }
    public function show(Request $request)
    {
        $student=$this->student($request);
        $campagnes=Campagne::where('statut','active')->get();
        $niveaux=NiveauEtude::orderBy('name')->get();
        if ($request->expectsJson()) return response()->json(['data'=>compact('campagnes','niveaux')]);
        return Inertia::render('Etudiant/Readmission',[
            'campagnes'=>$campagnes->map(fn($campagne)=>[
                'id'=>$campagne->getKey(),
                'libelle'=>$campagne->annee_campagne ?: 'Campagne en cours',
                'dateLimite'=>$campagne->date_echeance_demande ? \Carbon\Carbon::parse($campagne->date_echeance_demande)->format('d/m/Y') : null,
            ])->values(),
            'niveaux'=>$niveaux->map(fn($niveau)=>['id'=>$niveau->getKey(),'libelle'=>$niveau->name])->values(),
            'liens'=>['envoyer'=>route('etudiants.readmission.store'),'demandes'=>route('etudiants.dashboard',['section'=>'demandes'])],
        ]);
    }
    public function store(Request $request, ReadmissionService $service)
    {
        $student=$this->student($request);
        $data=$request->validate([
            'campagne_id'=>'required|string|exists:campagne,id_campagne',
            'niveau'=>'required|string|exists:niveau_etude,id_niveau_etude',
            'moyenne'=>'nullable|numeric|min:0|max:20',
            'credit_valider'=>'nullable|numeric|min:0',
            'boursier'=>'nullable|boolean',
            'cas_social'=>'nullable|string|max:2000',
        ]);
        $dossier=$service->submit($student,$data);
        if ($request->expectsJson()) return response()->json(['success'=>true,'message'=>'Demande de réadmission soumise.','data'=>$dossier],201);
        return redirect()->route('etudiants.dashboard',['section'=>'demandes'])
            ->with('success','Votre demande de réadmission a été soumise avec votre compte existant.');
    }
}
