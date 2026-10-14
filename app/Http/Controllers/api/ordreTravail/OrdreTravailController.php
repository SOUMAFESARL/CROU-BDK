<?php


namespace App\Http\Controllers\api\ordreTravail;


use App\Http\Controllers\Controller;
use App\Repositories\Interfaces\EtatDesLieuxRepositoryInterface;
use Illuminate\Http\Request;
use App\Http\Requests\CampagneRequest;
use App\Http\Services\CampagneService;
use App\Http\Services\DemandeService;
use App\Http\Services\EtudiantService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Campus;
use App\Models\Cite;
use App\Models\Batiment;
use Illuminate\Support\Str;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

use Illuminate\Support\Facades\Storage;
use App\Http\Requests\OrdreTravailRequest;

use App\Repositories\Interfaces\OrdreTravailServiceInterface;

    class OrdreTravailController extends Controller
    {
        protected $etudiantService;
        protected $campagneService;
        protected $demandeService;
        protected $etat = 'active';
        protected $etatDesLieux;
        protected $service;
        protected $serviceOrdreTravail;

        public function __construct(
            EtudiantService $etudiantService,
            CampagneService $campagneService,
            DemandeService $demandeService,
            EtatDesLieuxRepositoryInterface $etatDesLieux,
            OrdreTravailServiceInterface $serviceOrdreTravail
        ) {
            $this->etudiantService = $etudiantService;
            $this->campagneService = $campagneService;
            $this->demandeService = $demandeService;
            $this->etatDesLieux = $etatDesLieux;
            $this->serviceOrdreTravail = $serviceOrdreTravail;
        }


        public function store(Request $request) // OrdreTravailRequest
        {
            try {


                // Récupérer les données du formulaire
                $data = $request->all();
                $idEtudiant = $request->etudiant_id;

                // Récupérer les détails de l'étudiant
                $result = $this->etudiantService->getEtudiantDetails($idEtudiant);

                if (!$result['success']) {
                    throw new \Exception('Impossible de récupérer les informations de l\'étudiant');
                }

                $etudiant = $result['data'];

                // Récupérer l'assignation de chambre actuelle
                $assignation = $etudiant->assignationsChambre->first(); // Prendre la première assignation

                // Ajouter les informations de localisation
                if ($assignation && $assignation->chambre) {
                    $data['etudiant_id'] = $etudiant->id_etudiant;
                    $data['batiment_id'] = $assignation->chambre->batiment_id ?? null;
                    $data['chambre_id'] = $assignation->chambre->id_chambre ?? null;
                    $data['cite_id'] = $assignation->chambre->cite_id ?? null;
                } else {
                    // Si l'étudiant n'a pas de chambre assignée, utiliser les données du formulaire
                    $data['etudiant_id'] = $etudiant->id_etudiant;
                }

                // Définir le statut par défaut
                $data['statut'] = 'en_attente';
                //   dd($data);
                // Créer l'ordre de travail
                $ordre = $this->serviceOrdreTravail->create($data);

                //  dd('dfghbn');

                if (!$ordre) {
                    throw new \Exception('Erreur lors de la création de l\'ordre de travail');
                }

                Log::info("Ordre de travail créé par l'étudiant {$idEtudiant}: {$ordre->numero_ot}");

                return response()->json(['message' => '✅ Votre demande de maintenance a été soumise avec succès ! Le numéro de suivi est : ' . $ordre->numero_ot], 200);


            } catch (\Illuminate\Validation\ValidationException $e) {
                return response()->json(['message' => "Une erreur est survenue lors du traitement de votre demande"], 400);
            } catch (\Exception $e) {
                Log::error('Erreur lors de la création de l\'OT: ' . $e->getMessage());
                return response()->json(['message' => "Une erreur est survenue lors du traitement de votre demande"], 400);
            }
        }
    }