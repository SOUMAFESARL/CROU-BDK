<?php

namespace App\Repositories\Interfaces;

use App\Models\Intervention;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface InterventionRepositoryInterface
{
    /**
     * Récupère toutes les interventions
     */
    public function getAll(array $filters = []): Collection;

    /**
     * Récupère une intervention par son ID
     */
    public function findById(string $id): ?Intervention;

    /**
     * Récupère les interventions avec pagination
     */
    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Crée une nouvelle intervention
     */
    public function create(array $data): Intervention;

    /**
     * Met à jour une intervention
     */
    public function update(string $id, array $data): ?Intervention;

    /**
     * Supprime une intervention (soft delete)
     */
    public function delete(string $id): bool;

    /**
     * Récupère les interventions d'un ordre de travail
     */
    public function getByOrdreTravail(string $ordreTravailId): Collection;

    /**
     * Récupère les interventions d'un technicien
     */
    public function getByTechnicien(string $technicienId): Collection;

    /**
     * Récupère les interventions par période
     */
    public function getByPeriod(string $startDate, string $endDate): Collection;

    /**
     * Récupère les interventions avec soft delete
     */
    public function getWithTrashed(): Collection;

    /**
     * Restaure une intervention supprimée
     */
    public function restore(string $id): bool;

    /**
     * Supprime définitivement une intervention
     */
    public function forceDelete(string $id): bool;

       public function storeSatisfaction(
        string $ordreTravailId,
        array $data
    ): ?Intervention;
}
