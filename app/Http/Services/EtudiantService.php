<?php
namespace App\Http\Services;

use App\Repositories\Interfaces\EtudiantRepositoryInterface;
use App\Http\Requests\EtudiantRequest;
use Illuminate\Support\Facades\Log;
use App\Http\Services\CampagneValidationService;
use App\Models\Etudiant;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\Activation;
use App\Http\Services\CampagneService;
use App\Http\Services\DemandeService;
use App\Repositories\CampagneRepository;
use App\Mail\AcceptationLogement;

use App\Repositories\Interfaces\DemandeRepositoryInterface;


class EtudiantService
{
    protected $etudiantRepository;
    protected $validationService;

    protected $campagneService;
protected $etudiantService;
    public function __construct(EtudiantRepositoryInterface $etudiantRepository,CampagneValidationService $validationService)
    {
        //dd($etudiantRepository);
        $this->etudiantRepository = $etudiantRepository;
        $this->validationService =  $validationService;
    }

    public function getAll(?string $sortBy = null, ?string $sortDirection = 'asc')
    {

        return $this->etudiantRepository->getAll($sortBy, $sortDirection);
    }

    public function creerDossier(EtudiantRequest $request): array
    {
        try {


            $validatedData = $request->validated();

            // Préparation des données pour l'insertion
            $data = [
                'name' => $request->name,
                'date_naissance' => $request->date_naissance,
                'lieu_naissance' => $request->lieu_naissance,
                'genre_id' => $request->genre,
                'nationalite' => $request->nationalite,
                'email1' => $request->email,
                'telephone1' => $request->telephone,
                'regions_id' => $request->region,
                'ville_id' => $request->ville,
                'adresse' => $request->adresse,
                'matricule' => $request->matricule,
                'distance' => $request->distance,
                
                'autiste' => $request->pmr ?? 0, // Si pmr correspond à autiste
             //   'cas_social' => $request->cas_social,
              //  'type_chambre_id' => $request->type_chambre,
                'type_piece_id' => $request->type_piece_id,
                'numero_piece' => $request->numero_piece,
                'date_expiration' => $request->date_expiration,
                'groupe_sanguin_id' => $request->groupe_sanguin_id,
            ];

           // dd($request->all(),$data);
            //dd($data)   ;

            // Création de l'étudiant
            $etudiant = $this->etudiantRepository->create($data);



            return [
                'success' => true,
                'message' => 'Votre demande d\'hébergement a été soumise avec succès.',
                'data' => $etudiant
            ];

        } catch (\Exception $e) {


            return [
                'success' => false,
                'message' => 'Une erreur est survenue lors de la soumission de votre demande.',
                'error' => \App\Support\UserFacingErrors::caught($e)
            ];
        }
    }

    public function changeStatutEtudiant(string $id, string $etat)
    {
       // dd("sdahjkl;");
        $etudiants = $this->etudiantRepository->find($id);

        $etudiants->statut_inscription = $etat;
        $etudiants->save();

        return $this->getEtudiantDetails($id);
    }

    public function getListeEtudiants(?string $etat = null): array
    {
        try {
            $etudiants = $this->etudiantRepository->getAllWithFilters($etat);

            return [
                'success' => true,
                'message' => 'Liste récupérée avec succès',
                'data' => $etudiants
            ];

        } catch (\Exception $e) {

            return [
                'success' => false,
                'message' => 'Aucune liste associée',
                'error' => \App\Support\UserFacingErrors::caught($e),
                'data' => []
            ];
        }
    }

    public function getEtudiantDetails(string $id): array
    {
        try {

            $etudiant = $this->etudiantRepository->findWithRelations($id);

            if (!$etudiant) {
                return [
                    'success' => false,
                    'message' => 'Étudiant non trouvé',
                    'data' => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Détails récupérés avec succès',
                'data' => $etudiant
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des détails de l’étudiant', [
                'etudiant_id' => $id,
                'exception' => $e,
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la récupération des détails',
                'error' => \App\Support\UserFacingErrors::caught($e),
                'data' => null
            ];
        }
    }


    public function getStats(): array
    {
        try {
            $stats = $this->etudiantRepository->getStats();

            return [
                'success' => true,
                'message' => 'Statistiques récupérées avec succès',
                'data' => $stats
            ];
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des statistiques: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => \App\Support\UserFacingErrors::caught($e),
                'data' => [
                    'residents' => 0,
                    'nouvelles_demandes' => 0,
                    'en_attente' => 0,
                    'annulees' => 0,
                    'total' => 0
                ]
            ];
        }
    }


    public function getListeEtudiantsWithStats(?string $etat = null): array
    {
        try {


            $etudiants = $this->etudiantRepository->getAllWithFilters($etat);
           // dd($etudiants,$etat);
            $stats = $this->etudiantRepository->getStats();
            //dd($stats);
            return [
                'success' => true,
                'message' => 'Données récupérées avec succès',
                'data' => [
                    'etudiants' => $etudiants,
                    'stats' => $stats,
                    'filtre_actif' => $etat,
                    'campagne_active' => $this->validationService->getCampagneActive()?->only([
                        'id_campagne', 'annee_campagne', 'date_echeance_demande',
                        'moyenne_min', 'distance_min', 'age_min', 'age_max',
                    ]),
                ]
            ];
        } catch (\Exception $e) {
           // Log::error('Erreur lors de la récupération des données: ' . $e->getMessage());
//dd($e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur lors de la récupération des données',
                'error' => \App\Support\UserFacingErrors::caught($e),
                'data' => [
                    'etudiants' => [],
                    'stats' => [
                        'residents' => 0,
                        'nouvelles_demandes' => 0,
                        'en_attente' => 0,
                        'annulees' => 0,
                        'total' => 0
                    ],
                    'filtre_actif' => $etat
                ]
            ];
        }
    }

    public function ValidateDemandeEtudiant(array $data, string $id)
    {
        try {
            $demande = \App\Models\DemandeEtudiant::where('etudiant_id',$id)->latest('created_at')->orderByDesc('id_demande')->firstOrFail();
            $target = match($data['decision'] ?? null) {
                'eligible'=>'eligible','non_eligible'=>'refuse',default=>null,
            };
            if (!$target) return ['bool'=>'error','message'=>'Décision invalide.'];
            app(DossierWorkflow::class)->admin($demande->getKey(),$target,$data['commentaire']??null);
            return ['bool'=>'success','message'=>'Dossier : '.DossierWorkflow::LABELS[$target].'.'];
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ['bool'=>'error','message'=>implode(' ',\Illuminate\Support\Arr::flatten($e->errors()))];
        }
    }

    public function validerDemandeEtudiant(string $id, array $data, string $demande_id): array
    {
        $student = request()->attributes->get('student') ?? auth('etudiant')->user();
        abort_unless($student && (string)$student->getKey()===(string)$id,403);
        // Dossier éligible : l'étudiant confirme (ou refuse) son intérêt ; pré-attribué : il répond à la proposition.
        $consentement = \App\Models\DemandeEtudiant::whereKey($demande_id)->value('etat_dossier') === 'eligible';
        $target=match($data['action']??null){'accepter'=>$consentement ? 'confirme' : 'accepte','refuser'=>'refuse',default=>null};
        if (!$target) return ['success'=>false,'message'=>'Action invalide.'];
        try {
            app(DossierWorkflow::class)->transition($demande_id,$target,'etudiant',$id,
                $target==='refuse' ? ($data['motif_refus']??null)
                    : ($consentement ? 'Demande d’hébergement confirmée par l’étudiant.' : 'Proposition de chambre acceptée par l’étudiant.'),
                ['assignation_id'=>$data['assignation_id']??null]);
            return ['success'=>true,'message'=>match(true) {
                $target==='confirme' => 'Demande confirmée. Une chambre vous sera attribuée par le service Logement.',
                $target==='accepte' => 'Proposition acceptée. La validation définitive relève du service Logement.',
                $consentement => 'Demande refusée. Vous ne serez pas logé(e) pour cette campagne.',
                default => 'Proposition refusée.',
            }];
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ['success'=>false,'message'=>implode(' ',\Illuminate\Support\Arr::flatten($e->errors()))];
        }
    }

    public function getStatutDemande(string $id): array
    {
        try {
            $etudiant = $this->etudiantRepository->find($id);

            if (!$etudiant) {
                return [
                    'success' => false,
                    'message' => 'Étudiant non trouvé',
                    'data' => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Statut récupéré avec succès',
                'data' => [
                    'id_etudiant' => $etudiant->id_etudiant,
                    'statut_inscription' => $etudiant->statut_inscription,
                    'etat_dossier' => $etudiant->derniereDemande?->etat_dossier,
                    'date_inscription' => $etudiant->date_inscription,
                    'date_validation_etudiant' => $etudiant->date_validation_etudiant ?? null,
                    'est_validable' => in_array($etudiant->derniereDemande?->etat_dossier, ['eligible', 'pre_attribue'], true),
                    'motif_refus' => $etudiant->motif_refus ?? null,
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du statut: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erreur lors de la récupération du statut',
                'error' => \App\Support\UserFacingErrors::caught($e),
                'data' => null
            ];
        }
    }


    /**
     * Récupérer les détails de la demande pour affichage à l'étudiant
     *
     * @param string $id - ID de l'étudiant
     * @return array
     */
    public function getDetailsDemande(string $id): array
    {

        try {
            $etudiant = $this->etudiantRepository->findWithRelations($id);

            if (!$etudiant) {
                return [
                    'success' => false,
                    'message' => 'Demande non trouvée',
                    'data' => null
                ];
            }

            // Récupérer les détails de la dernière demande
            $derniereDemande = $etudiant->derniereDemande;
           // dd($derniereDemande);
            return [
                'success' => true,
                'message' => 'Détails récupérés avec succès',
                'data' => [
                    'id_etudiant' => $etudiant->id_etudiant,
                    'nom' => $etudiant->name,
                    'email' => $etudiant->email1,
                    'telephone' => $etudiant->telephone1,
                    'statut_actuel' => $etudiant->statut_inscription,
                    'date_demande' => $etudiant->date_inscription,
                    'derniere_demande' => $derniereDemande ? [
                        'moyenne' => $derniereDemande->moyenne,
                        'niveau' => $derniereDemande->niveauEtudiantActuel->name ?? 'N/A',
                        'credit_valides' => $derniereDemande->credit_valider,
                    ] : null,
                    'documents_valides' => collect($etudiant->documents)
                        ->where('statut', 'valide')
                        ->count(),
                    'total_documents' => count($etudiant->documents),
                    'peut_valider' => in_array($derniereDemande?->etat_dossier, ['eligible', 'pre_attribue'], true),
                    'etat_dossier' => $derniereDemande?->etat_dossier,
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des détails: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erreur lors de la récupération des détails',
                'error' => \App\Support\UserFacingErrors::caught($e),
                'data' => null
            ];
        }
    }

public function finaliserInscriptionAutomatique(string $etudiantId, ?string $demandeId): array
{
    $dossier=\App\Models\DemandeEtudiant::whereKey($demandeId)->where('etudiant_id',$etudiantId)->firstOrFail();
    return ['success'=>true,'eligible'=>false,'message'=>$dossier->etat_dossier==='brouillon'
        ? 'Brouillon enregistré. Soumettez-le depuis votre espace étudiant.'
        : 'Dossier soumis. L’administration vérifiera les pièces et l’éligibilité avant de proposer une chambre.',
        'data'=>['etat_dossier'=>$dossier->etat_dossier]];
}
}
