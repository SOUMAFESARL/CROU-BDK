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
use App\Models\DocumentCampagne;
use App\Models\ResidenceCampagne;
use Illuminate\Validation\ValidationException;

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
        if (isset($data['date_echeance_demande']) && isset($data['date_echeance_contrat'])) {
            if ($data['date_echeance_contrat'] <= $data['date_echeance_demande']) {
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
     * Mettre à jour une campagne, ses résidences et ses documents requis (même règles que la création).
     */
    public function updateCampagne(string $id, array $data): Campagne
    {
        $campagne = $this->campagneRepository->findById($id);
        if (!$campagne) {
            throw ValidationException::withMessages(['campagne' => 'Campagne introuvable ou déjà supprimée.']);
        }

        $residences = $data['residences'] ?? null;
        $documents = $data['documents'] ?? null;
        unset($data['residences'], $data['documents']);

        $this->validateDates($data);
        $data = $this->prepareBooleanData($data);

        DB::transaction(function () use ($campagne, $data, $residences, $documents) {
            $this->campagneRepository->update($campagne->id_campagne, $data);

            if ($residences !== null) {
                $this->synchroniserResidences($campagne, $residences);
            }
            if ($documents !== null) {
                $this->synchroniserDocuments($campagne, $documents);
            }
        });

        return $campagne->fresh();
    }

    /**
     * Nombre de dossiers rattachés à la campagne (demandes, attributions, dérogations).
     * Une campagne qui en a ne peut pas être supprimée : l'historique des étudiants en dépend.
     */
    public function nombreDossiers(string $id): int
    {
        return DB::table('demande')->where('campagne_id', $id)->count()
            + DB::table('assignation_chambres')->where('campagne_id', $id)->count()
            + DB::table('admission_derogations')->where('campagne_id', $id)->count();
    }

    /**
     * Supprimer (suppression logique) une campagne sans dossier rattaché.
     * Les liens résidences / documents sont conservés pour permettre une restauration.
     */
    public function deleteCampagne(string $id): void
    {
        $campagne = $this->campagneRepository->findById($id);
        if (!$campagne) {
            throw ValidationException::withMessages(['campagne' => 'Campagne introuvable ou déjà supprimée.']);
        }

        if ($this->nombreDossiers($id) > 0) {
            throw ValidationException::withMessages([
                'campagne' => 'Cette campagne ne peut pas être supprimée : des étudiants y ont déjà déposé une demande ou reçu une attribution.',
            ]);
        }

        $this->campagneRepository->delete($id);
    }

    /** Remplace les résidences de la campagne par la sélection (ajouts et retraits uniquement). */
    protected function synchroniserResidences(Campagne $campagne, array $citeIds): void
    {
        $citeIds = array_values(array_unique($citeIds));
        $actuelles = ResidenceCampagne::where('campagne_id', $campagne->id_campagne)->pluck('cite_id')->all();

        ResidenceCampagne::where('campagne_id', $campagne->id_campagne)
            ->whereIn('cite_id', array_diff($actuelles, $citeIds))
            ->delete();

        foreach (array_diff($citeIds, $actuelles) as $citeId) {
            ResidenceCampagne::create(['campagne_id' => $campagne->id_campagne, 'cite_id' => $citeId]);
        }
    }

    /** Remplace les documents requis de la campagne par la sélection (ajouts et retraits uniquement). */
    protected function synchroniserDocuments(Campagne $campagne, array $documentIds): void
    {
        $documentIds = array_values(array_unique($documentIds));
        $actuels = DocumentCampagne::where('id_campagne', $campagne->id_campagne)->pluck('document_id')->all();

        DocumentCampagne::where('id_campagne', $campagne->id_campagne)
            ->whereIn('document_id', array_diff($actuels, $documentIds))
            ->delete();

        foreach (array_diff($documentIds, $actuels) as $documentId) {
            DocumentCampagne::create(['id_campagne' => $campagne->id_campagne, 'document_id' => $documentId]);
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
