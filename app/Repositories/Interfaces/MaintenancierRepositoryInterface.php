<?php

namespace App\Repositories\Interfaces;

use App\Models\Maintenancier;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface MaintenancierRepositoryInterface
{
    /**
     * Récupérer tous les maintenanciers
     */
    public function getAll(): Collection;

    /**
     * Récupérer tous les maintenanciers avec pagination
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator;

    /**
     * Récupérer un maintenancier par son ID
     */
    public function findById(string $id): ?Maintenancier;

    /**
     * Récupérer un maintenancier par son ID avec relations
     */
    public function findWithRelations(string $id): ?Maintenancier;

    /**
     * Créer un nouveau maintenancier
     */
    public function create(array $data): Maintenancier;

    /**
     * Mettre à jour un maintenancier
     */
    public function update(string $id, array $data): bool;

    /**
     * Supprimer un maintenancier (soft delete)
     */
    public function delete(string $id): bool;

    /**
     * Restaurer un maintenancier supprimé
     */
    public function restore(string $id): bool;

    /**
     * Supprimer définitivement un maintenancier
     */
    public function forceDelete(string $id): bool;

    /**
     * Récupérer les maintenanciers par spécialité
     */
    public function getBySpecialite(string $specialite): Collection;

    /**
     * Récupérer les maintenanciers par résidence
     */
    public function getByCite(string $citeId): Collection;

    /**
     * Récupérer les maintenanciers actifs
     */
    public function getActive(): Collection;

    /**
     * Récupérer les maintenanciers inactifs
     */
    public function getInactive(): Collection;

    /**
     * Récupérer les maintenanciers avec leurs relations
     */
    public function getWithRelations(): Collection;

    /**
     * Rechercher des maintenanciers
     */
    public function search(string $term): Collection;

    /**
     * Compter les maintenanciers par spécialité
     */
    public function countBySpecialite(): array;

    /**
     * Compter les maintenanciers par résidence
     */
    public function countByCite(): array;

    /**
     * Mettre à jour le statut d'un maintenancier
     */
    public function updateStatus(string $id, bool $statut): bool;
}