<?php


namespace App\Repositories\Interfaces;

use App\Models\OrdreTravail;
use App\Models\Intervention;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface OrdreTravailRepositoryInterface
{
    /**
     * Récupérer tous les ordres de travail
     */
    public function getAll(array $filters = []): Collection;

    /**
     * Récupérer les ordres de travail avec pagination
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Récupérer un ordre de travail par son ID
     */
    public function findById(string $id): ?OrdreTravail;

    /**
     * Récupérer un ordre de travail par son numéro
     */
    public function findByNumero(string $numero): ?OrdreTravail;

    /**
     * Récupérer un ordre de travail par etudiant
     */
    public function findByEtudiant(string $id): ?OrdreTravail;

    /**
     * Créer un nouvel ordre de travail
     */
    public function create(array $data): OrdreTravail;

    /**
     * Mettre à jour un ordre de travail
     */
    public function update(string $id, array $data): OrdreTravail;

    /**
     * Supprimer un ordre de travail
     */
    public function delete(string $id): bool;

    /**
     * Assigner un technicien à un ordre de travail
     */
    public function assigner(string $id, array $data): OrdreTravail;

    /**
     * Mettre à jour le statut d'un ordre de travail
     */
    public function updateStatut(string $id, string $statut): OrdreTravail;

    /**
     * Ajouter une intervention à un ordre de travail
     */
    public function ajouterIntervention(string $id, array $data): Intervention;

    /**
     * Récupérer les statistiques des ordres de travail
     */
    public function getStats(array $filters = []): array;

    /**
     * Récupérer les ordres de travail par statut
     */
    public function getByStatut(string $statut): Collection;

    /**
     * Récupérer les ordres de travail urgents
     */
    public function getUrgents(): Collection;

    /**
     * Récupérer les ordres de travail par résidence
     */
    public function getByResidence(string $residenceId): Collection;

    /**
     * Récupérer les ordres de travail par technicien
     */
    public function getByTechnicien(string $userId): Collection;
}