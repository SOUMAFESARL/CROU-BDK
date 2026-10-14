<?php

namespace App\Repositories\Interfaces;

use App\Models\Campus;
use Illuminate\Database\Eloquent\Collection;

interface CampusRepositoryInterface
{
    /**
     * Récupérer tous les campus
     */
    public function getAll(array $filters = []): array;

    /**
     * Récupérer un campus par ID
     */
    public function getById(string $id): ?Campus;

    /**
     * Récupérer un campus par code
     */
    public function getByCode(string $code): ?Campus;

    /**
     * Créer un campus
     */
    public function create(array $data): Campus;

    /**
     * Mettre à jour un campus
     */
    public function update(string $id, array $data): Campus;

    /**
     * Supprimer un campus
     */
    public function delete(string $id): bool;

    /**
     * Restaurer un campus supprimé
     */
    public function restore(string $id): bool;

    /**
     * Supprimer définitivement un campus
     */
    public function forceDelete(string $id): bool;

    /**
     * Récupérer les campus par région
     */
    public function getByRegion(int $regionId): array;

    /**
     * Récupérer les campus par ville
     */
    public function getByVille(int $villeId): array;

    /**
     * Récupérer les campus actifs
     */
    public function getActive(): array;

    /**
     * Chercher des campus
     */
    public function search(string $term): array;

    /**
     * Compter les campus
     */
    public function count(array $filters = []): int;

    /**
     * Obtenir l'agence principale
     */
    public function getPrincipal(): ?Campus;
}