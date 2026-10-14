<?php

namespace App\Http\Services;

use App\Repositories\CampagneRepository;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Models\Campagne;
use App\Models\DemandeEtudiant;

class CampagneService
{
    protected  $campagneRepository;

    public function __construct(CampagneRepository $campagneRepository)
    {
        $this->campagneRepository = $campagneRepository;
    }

    /**
     * Créer une nouvelle campagne
     */
    public function createCampagne(array $data): Campagne
    {
        try {
            DB::beginTransaction();

            // Validation des dates
            $this->validateDates($data);

            // Validation des âges
            $this->validateAges($data);

            // Préparation des données booléennes
            $data = $this->prepareBooleanData($data);

            // Création de la campagne
            $campagne = $this->campagneRepository->create($data);

            DB::commit();
            return $campagne;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création de la campagne: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Valider les dates
     */
    protected function validateDates(array $data): void
    {
        // Vérifier les dates principales
        if (isset($data['periode_debut']) && isset($data['periode_fin'])) {
            if ($data['periode_fin'] <= $data['periode_debut']) {
                throw new Exception('La date de fin de campagne doit être postérieure à la date de début');
            }
        }

        // Vérifier la date d'échéance des demandes
        if (isset($data['periode_fin']) && isset($data['date_echeance_demande'])) {
            if ($data['date_echeance_demande'] <= $data['periode_fin']) {
                throw new Exception('La date d\'échéance des demandes doit être après la fin de la campagne');
            }
        }

        // Vérifier la date d'échéance des contrats
        if (isset($data['date_echeance_demande']) && isset($data['date_echance_contrat'])) {
            if ($data['date_echance_contrat'] <= $data['date_echeance_demande']) {
                throw new Exception('La date d\'échéance des contrats doit être après la date d\'échéance des demandes');
            }
        }
    }

    /**
     * Valider les âges
     */
    protected function validateAges(array $data): void
    {
        if (isset($data['age_min']) && isset($data['age_max'])) {
            if ($data['age_max'] <= $data['age_min']) {
                throw new Exception('L\'âge maximum doit être supérieur à l\'âge minimum');
            }
        }
    }

    /**
     * Préparer les données booléennes
     */
    protected function prepareBooleanData(array $data): array
    {
        $booleanFields = [
            'new_bachelier',
            'old_bachelier',
            'readmission',
            'pmr',
            'internationnaux',
            'redoublement',
            'is_participate',
            'is_discipline'
        ];

        foreach ($booleanFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = filter_var($data[$field], FILTER_VALIDATE_BOOLEAN);
            } else {
                $data[$field] = false;
            }
        }

        return $data;
    }

    /**
     * Mettre à jour une campagne
     */
    public function updateCampagne(string $id, array $data): bool
    {
        try {
            DB::beginTransaction();

            $campagne = $this->campagneRepository->findById($id);
            if (!$campagne) {
                throw new Exception('Campagne non trouvée');
            }

            // Validation des dates
            $periodeDebut = $data['periode_debut'] ?? $campagne->periode_debut;
            $periodeFin = $data['periode_fin'] ?? $campagne->periode_fin;
            $dateEcheanceDemande = $data['date_echeance_demande'] ?? $campagne->date_echeance_demande;
            $dateEcheanceContrat = $data['date_echance_contrat'] ?? $campagne->date_echance_contrat;

            if ($periodeFin <= $periodeDebut) {
                throw new Exception('La date de fin de campagne doit être postérieure à la date de début');
            }

            if ($dateEcheanceDemande <= $periodeFin) {
                throw new Exception('La date d\'échéance des demandes doit être après la fin de la campagne');
            }

            if ($dateEcheanceContrat <= $dateEcheanceDemande) {
                throw new Exception('La date d\'échéance des contrats doit être après la date d\'échéance des demandes');
            }

            // Validation des âges
//            $ageMin = $data['age_min'] ?? $campagne->age_min;
//            $ageMax = $data['age_max'] ?? $campagne->age_max;
//
//            if ($ageMax <= $ageMin) {
//                throw new Exception('L\'âge maximum doit être supérieur à l\'âge minimum');
//            }

            // Préparation des données booléennes
            $data = $this->prepareBooleanData($data);

            $result = $this->campagneRepository->update($id, $data);

            DB::commit();
            return $result;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour de la campagne: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupérer toutes les campagnes
     */
    public function getListeCampagne(?string $etat = null): array
    {
        try {
            $campagne = $this->campagneRepository->getAllWithFilters($etat);

            return [
                'success' => true,
                'message' => 'Liste récupérée avec succès',
                'data' => $campagne
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

    public function getCampagneDetails(string $id): array
    {
        try {

            $campagne = $this->campagneRepository->findWithRelations($id);

            if (!$campagne) {
                return [
                    'success' => false,
                    'message' => 'Campagne non trouvé',
                    'data' => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Détails récupérés avec succès',
                'data' => $campagne
            ];

        } catch (\Exception $e) {
            //   Log::error('Erreur lors de la récupération des détails: ' . $e->getMessage());

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
            $stats = $this->campagneRepository->getStats();

            return [
                'success' => true,
                'message' => 'Statistiques récupérées avec succès',
                'data' => $stats
            ];
        } catch (\Exception $e) {
            //Log::error('Erreur lors de la récupération des statistiques: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => \App\Support\UserFacingErrors::caught($e),
                'data' => [
                    'active' => 0,
                    'terminer' => 0,
                    'planifier' => 0,

                    'total' => 0
                ]
            ];
        }
    }


    public function getListeCampagnesWithStats(?string $etat = null): array
    {
        try {

            $campagnes = $this->campagneRepository->getAllWithFilters($etat);
         //   dd($campagnes);
            $stats = $this->campagneRepository->getStats();

            return [
                'success' => true,
                'message' => 'Données récupérées avec succès',
                'data' => [
                    'campagnes' => $campagnes,
                    'stats' => $stats,
                    'filtre_actif' => $etat
                ]
            ];
        } catch (\Exception $e) {
            // Log::error('Erreur lors de la récupération des données: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erreur lors de la récupération des données',
                'error' => \App\Support\UserFacingErrors::caught($e),
                'data' => [
                    'campagnes' => [],
                    'stats' => [
                        'active' => 0,
                        'terminer' => 0,
                        'planifier' => 0,

                        'total' => 0
                    ],
                    'filtre_actif' => $etat
                ]
            ];
        }
    }

    /** Données de la campagne qui pilote l'année universitaire courante. */
    public function getCampagneUniversitaireWithStats(): array
    {
        $campagne = $this->campagneRepository->getCurrentCampagne();

        return [
            'success' => (bool) $campagne,
            'message' => $campagne
                ? 'Campagne universitaire récupérée avec succès'
                : 'Aucune campagne universitaire configurée',
            'data' => [
                'campagnes' => $campagne ? collect([$campagne]) : collect(),
                'stats' => $this->campagneRepository->getStats(),
                'filtre_actif' => 'annee_universitaire',
            ],
        ];
    }

    public function rejeterDemandesCampagneExpiree(?Collection $campagnes = null): int
    {
        try {
            $campagneTerminee = $campagnes?->first() ?? Campagne::where('statut', 'terminer')
                ->orderByDesc('date_echeance_contrat')
                ->first();

            if (!$campagneTerminee || $campagneTerminee->statut !== 'terminer') {
                return 0;
            }

            $demandes = $campagneTerminee->demandes()->whereIn('etat_dossier',
                ['soumis','incomplet','en_etude','eligible','pre_attribue','accepte'])->get();
            $count = 0;
            foreach ($demandes as $demande) {
                app(DossierWorkflow::class)->transition($demande->getKey(),'expire','systeme',null,
                    'Échéance de la campagne atteinte.');
                $count++;
            }

            return $count;
        } catch (Exception $e) {
            Log::error('Erreur lors du rejet automatique des demandes expirées', [
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }


}
