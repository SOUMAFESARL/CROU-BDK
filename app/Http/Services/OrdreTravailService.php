<?php
// app/Http/Services/OrdreTravailService.php

namespace App\Http\Services;

use App\Models\OrdreTravail;
use App\Models\Intervention;
use App\Repositories\Interfaces\OrdreTravailRepositoryInterface;
use App\Repositories\Interfaces\OrdreTravailServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrdreTravailService implements OrdreTravailServiceInterface
{
    /** Changements de statut autorisés (l'assignation fait passer de « en attente » à « en cours »). */
    public const TRANSITIONS = [
        'en_attente' => ['en_cours', 'annule'],
        'en_cours' => ['en_attente_pieces', 'termine', 'annule', 'reporte'],
        'en_attente_pieces' => ['en_cours', 'termine', 'annule'],
        'termine' => [],
        'annule' => [],
        'reporte' => ['en_attente', 'en_cours'],
    ];

    protected  $repository;

    public function __construct(OrdreTravailRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Récupérer tous les ordres de travail
     */
    public function getAll(array $filters = []): Collection
    {
        return $this->repository->getAll($filters);
    }

    /**
     * Récupérer les ordres de travail avec pagination
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getPaginated($filters, $perPage);
    }

    /**
     * Récupérer un ordre de travail par son ID
     */
    public function findById(string $id): ?OrdreTravail
    {
        return $this->repository->findById($id);
    }

    /**
     * Récupérer un ordre de travail par etudiant
     */
    public function findByEtudiant(string $id): ?OrdreTravail
    {
        return $this->repository->findByEtudiant($id);
    }

    /**
     * Récupérer un ordre de travail par son numéro
     */
    public function findByNumero(string $numero): ?OrdreTravail
    {
        return $this->repository->findByNumero($numero);
    }

    /**
     * Créer un nouvel ordre de travail
     */
    public function create(array $data): OrdreTravail
    {
        try {

            DB::beginTransaction();

            $this->validateCreateData($data);

         

            $ordre = $this->repository->create($data);

//            if (isset($data['pieces']) && is_array($data['pieces'])) {
//                $this->attachPieces($ordre, $data['pieces']);
//            }

            DB::commit();
            return $ordre->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création de l\'OT: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Mettre à jour un ordre de travail
     */
    public function update(string $id, array $data): OrdreTravail
    {
        try {
            DB::beginTransaction();

            $ordre = $this->repository->findById($id);
            if (!$ordre) {
                throw new \Exception('Ordre de travail non trouvé');
            }

            $this->validateUpdateData($data, $ordre);
            $ordre = $this->repository->update($id, $data);

            if (isset($data['pieces'])) {
                $this->syncPieces($ordre, $data['pieces']);
            }

            DB::commit();
            return $ordre->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour de l\'OT: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Supprimer un ordre de travail
     */
    public function delete(string $id): bool
    {
        try {
            DB::beginTransaction();

            $ordre = $this->repository->findById($id);
            if (!$ordre) {
                throw new \Exception('Ordre de travail non trouvé');
            }

            if (in_array($ordre->statut, ['en_cours', 'en_attente_pieces', 'termine'])) {
                throw new \Exception('Impossible de supprimer un ordre de travail en cours ou clôturé');
            }

            $result = $this->repository->delete($id);

            DB::commit();
            return $result;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la suppression de l\'OT: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Assigner un technicien à un ordre de travail
     */
    public function assigner(string $id, array $data): OrdreTravail
    {
        try {
            DB::beginTransaction();

            $ordre = $this->repository->findById($id);
            if (!$ordre) {
                throw new \Exception('Ordre de travail non trouvé');
            }

            if ($ordre->statut !== 'en_attente') {
                throw new \Exception('Cet ordre de travail ne peut pas être assigné');
            }

            $ordre = $this->repository->assigner($id, $data);

            DB::commit();
            return $ordre;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de l\'assignation de l\'OT: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Mettre à jour le statut d'un ordre de travail
     */
    public function updateStatut(string $id, string $statut, ?string $solution = null): OrdreTravail
    {
        try {
            DB::beginTransaction();

            $ordre = $this->repository->findById($id);
            if (!$ordre) {
                throw new \Exception('Ordre de travail non trouvé');
            }

            $this->validateStatutTransition($ordre->statut, $statut);
            $ordre = $this->repository->updateStatut($id, $statut, $solution);

            DB::commit();
            return $ordre;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour du statut de l\'OT: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Ajouter une intervention à un ordre de travail
     */
    public function ajouterIntervention(string $id, array $data): Intervention
    {
        try {
            DB::beginTransaction();

            $ordre = $this->repository->findById($id);
            if (!$ordre) {
                throw new \Exception('Ordre de travail non trouvé');
            }

            $intervention = $this->repository->ajouterIntervention($id, $data);

            DB::commit();
            return $intervention;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de l\'ajout de l\'intervention: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupérer les statistiques des ordres de travail
     */
    public function getStats(array $filters = []): array
    {
        return $this->repository->getStats($filters);
    }

    /**
     * Récupérer les ordres de travail urgents
     */
    public function getUrgents(): Collection
    {
        return $this->repository->getUrgents();
    }

    /**
     * Récupérer les ordres de travail par résidence
     */
    public function getByResidence(string $residenceId): Collection
    {
        return $this->repository->getByResidence($residenceId);
    }

    /**
     * Récupérer les ordres de travail par technicien
     */
    public function getByTechnicien(string $userId): Collection
    {
        return $this->repository->getByTechnicien($userId);
    }

    /**
     * Valider les données de création
     */
    protected function validateCreateData(array $data): void
    {

        $required = [ 'description', 'categorie', 'priorite'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Le champ '{$field}' est requis");
            }
        }

        if (!in_array($data['categorie'], OrdreTravail::CATEGORIES ?? [])) {
            throw new \Exception('Catégorie invalide');
        }

        if (!in_array($data['priorite'], OrdreTravail::PRIORITES ?? [])) {
            throw new \Exception('Priorité invalide');
        }
    }

    /**
     * Valider les données de mise à jour
     */
    protected function validateUpdateData(array $data, OrdreTravail $ordre): void
    {
        if (in_array($ordre->statut, ['termine', 'annule'])) {
            throw new \Exception('Cet ordre de travail est clôturé et ne peut pas être modifié');
        }

        if (isset($data['priorite']) && !in_array($data['priorite'], ['critique', 'elevee', 'moyenne', 'faible'])) {
            throw new \Exception('Priorité invalide');
        }

        if (isset($data['categorie']) && !in_array($data['categorie'], OrdreTravail::CATEGORIES ?? [])) {
            throw new \Exception('Catégorie invalide');
        }
    }

    /**
     * Valider la transition de statut
     */
    protected function validateStatutTransition(string $oldStatut, string $newStatut): void
    {
        $transitions = self::TRANSITIONS;

        if (!isset($transitions[$oldStatut]) || !in_array($newStatut, $transitions[$oldStatut])) {
            throw new \Exception("Transition de statut invalide: '{$oldStatut}' → '{$newStatut}'");
        }
    }

    /**
     * Attacher des pièces détachées à l'OT
     */
    protected function attachPieces(OrdreTravail $ordre, array $pieces): void
    {
        foreach ($pieces as $piece) {
            if (isset($piece['id']) && isset($piece['quantite'])) {
                $ordre->piecesDetachees()->attach($piece['id'], [
                    'quantite' => $piece['quantite'],
                    'prix_unitaire' => $piece['prix_unitaire'] ?? null,
                    'date_utilisation' => now()
                ]);
            }
        }
    }

    /**
     * Synchroniser les pièces détachées de l'OT
     */
    protected function syncPieces(OrdreTravail $ordre, array $pieces): void
    {
        $syncData = [];
        foreach ($pieces as $piece) {
            if (isset($piece['id']) && isset($piece['quantite'])) {
                $syncData[$piece['id']] = [
                    'quantite' => $piece['quantite'],
                    'prix_unitaire' => $piece['prix_unitaire'] ?? null,
                    'date_utilisation' => now()
                ];
            }
        }
        $ordre->piecesDetachees()->sync($syncData);
    }
}