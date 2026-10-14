<?php
namespace App\Http\Controllers;
use App\Http\Services\DossierWorkflow;
use App\Models\DemandeEtudiant;
use Illuminate\Http\Request;

class DossierWorkflowController extends Controller
{
    public function admin(Request $request, DemandeEtudiant $demande)
    {
        $data=$request->validate(['etat'=>'required|in:en_etude,incomplet,eligible,refuse,annule','motif'=>'nullable|string|max:5000']);
        $updated=app(DossierWorkflow::class)->admin($demande->getKey(),$data['etat'],$data['motif']??null);
        return $request->expectsJson() ? response()->json(['data'=>$updated->load('transitions')])
            : back()->with('success','Dossier : '.DossierWorkflow::LABELS[$updated->etat_dossier].'.');
    }
    public function student(Request $request, DemandeEtudiant $demande)
    {
        $student=$request->attributes->get('student') ?? auth('etudiant')->user();
        abort_unless($student,401);
        abort_unless((string)$demande->etudiant_id === (string)$student->getKey(),403);
        $data=$request->validate(['etat'=>'required|in:soumis,accepte,refuse,annule','motif'=>'nullable|string|max:5000','assignation_id'=>'nullable|required_if:etat,accepte,refuse|string']);
        $updated=app(DossierWorkflow::class)->transition($demande->getKey(),$data['etat'],'etudiant',$student->getKey(),$data['motif']??null,['assignation_id'=>$data['assignation_id']??null]);
        return $request->expectsJson() ? response()->json(['data'=>$updated->load('transitions','proposition.chambre')])
            : back()->with('success','Dossier : '.DossierWorkflow::LABELS[$updated->etat_dossier].'.');
    }
    public function show(Request $request, DemandeEtudiant $demande)
    {
        $student=$request->attributes->get('student') ?? auth('etudiant')->user();
        abort_unless($student && (string)$demande->etudiant_id===(string)$student->getKey(),403);
        return response()->json(['data'=>$demande->load('transitions','proposition.chambre')]);
    }
}
