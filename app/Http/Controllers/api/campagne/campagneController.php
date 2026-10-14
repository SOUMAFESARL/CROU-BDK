<?php

namespace App\Http\Controllers\api\campagne;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Services\CampagneService;
use App\Http\Services\EtudiantService;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class  campagneController extends Controller
{
    protected $etudiantService;
    protected $campagneService;
    protected  $etat='active';

    public function __construct(EtudiantService $etudiantService,CampagneService $campagneService)
    {
        $this->etudiantService = $etudiantService;
        $this->campagneService = $campagneService;
    }

    public function index(){

        $result = $this->campagneService->getListeCampagnesWithStats($this->etat);

        return response()->json(['message' => $result ], 200);
    }


    public function AcceptOrRefuseDemande(Request $request)
    {

        try {

            // ÉTAPE 1: Valider les entrées
            $validated = $request->validate([
                'action' => 'required|in:accepter,refuser',
                'assignation_id' => 'required|string',
                'motif_refus' => 'required_if:action,refuser|string|max:500',
                'demande_id' => 'required',
                'etudiant_id' => 'nullable|string',
            ]);

            $idEtudiantConnecte = $request->attributes->get('student')->getKey();
            $idDemande =  $validated['demande_id'];

            // ÉTAPE 2: Vérifier que la demande appartient à l'étudiant
            $detailsResult = $this->etudiantService->getDetailsDemande($idEtudiantConnecte);

            if (!$detailsResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Demande non trouvée'
                ], 404);
            }

            if ($detailsResult['data']['id_etudiant'] !== $idEtudiantConnecte) {
                Log::warning("Tentative de validation non autorisée: étudiant {$idEtudiantConnecte} sur demande {$idDemande}");
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            if (!$detailsResult['data']['peut_valider']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette demande ne peut pas être validée (état: ' . $detailsResult['data']['statut_actuel'] . ')'
                ], 400);
            }

            // ÉTAPE 3: Préparer les données
            $data = [
                'action' => $validated['action'],
                'assignation_id' => $validated['assignation_id'],

            ];


            if ($validated['action'] === 'refuser') {
                $data['motif_refus'] = $validated['motif_refus'];
            }

            // ÉTAPE 4: Valider la demande
            $result = $this->etudiantService->validerDemandeEtudiant($idEtudiantConnecte, $data,$idDemande);
            // dd($detailsResult,$data);
            // ÉTAPE 5: Retourner le résultat
            if (!$result['success']) {
                return response()->json($result, 400);
            }

            // Log l'action
            Log::info("Demande {$idDemande} {$validated['action']}e par l'étudiant {$idEtudiantConnecte}");

            // Déterminer le message de succès
            $message = $validated['action'] === 'accepter'
                ? '✅ Votre demande a été acceptée avec succès!'
                : '❌ Votre demande a été refusée.';

            return response()->json(['message' => $message], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => \App\Support\UserFacingErrors::caught($e)], 400);

        } catch (\Exception $e) {
          Log::error('Erreur lors de la validation de la demande: ' . $e->getMessage());

            return response()->json(['message' => "Une erreur est survenue lors du traitement de votre demande"], 400);

        }
    }

    public function pdfContrat($studiant_id)
    {
        //   allConfig()
        $idEtudiant = $this->etudiantService->getDetailsEtudiant($studiant_id);
        // Récupérer la liste des campagnes avec leurs statistiques
        $result = $this->campagneService->getListeCampagnesWithStats($this->etat);

        // Ajouter les détails de l'étudiant au résultat
        $result['etudiant'] = $idEtudiant;
        $pdf = Pdf::loadView('pdf.contrat', compact('result'));

       // return $pdf->download("contrat-".Str::slug($idEtudiant->name).".pdf");

        return $pdf->stream();
    }


}