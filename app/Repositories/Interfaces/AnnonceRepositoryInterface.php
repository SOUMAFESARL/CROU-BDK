<?php
// app/Repositories/Interfaces/AnnonceRepositoryInterface.php

namespace App\Repositories\Interfaces;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface AnnonceRepositoryInterface
{
    /**
     * Récupérer toutes les annonces
     */
    public function all(): Collection;

    /**
     * Récupérer une annonce par son ID
     */
    public function find(string $id): ?object;

    /**
     * Créer une nouvelle annonce
     */
    public function create(array $data): object;

    /**
     * Mettre à jour une annonce
     */
    public function update(string $id, array $data): bool;

    /**
     * Supprimer une annonce
     */
    public function delete(string $id): bool;

    /**
     * Récupérer les annonces avec pagination et filtres
     */
    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Récupérer les annonces actives
     */
    public function getActives(): Collection;

    /**
     * Récupérer les annonces par statut
     */
    public function getByStatut(string $statut): Collection;

    /**
     * Récupérer les annonces par canal
     */
    public function getByCanal(string $canal): Collection;

    /**
     * Publier une annonce
     */
    public function publish(string $id): bool;

    /**
     * Planifier une annonce
     */
    public function schedule(string $id, string $date): bool;

    /**
     * Annuler une annonce
     */
    public function cancel(string $id): bool;

    /**
     * Incrémenter les vues
     */
    public function incrementViews(string $id): bool;

    /**
     * Enregistrer une statistique
     */
    public function recordStat(string $annonceId, array $data): bool;

    /**
     * Récupérer les statistiques d'une annonce
     */
    public function getStats(string $annonceId, ?string $dateDebut = null, ?string $dateFin = null): array;

    /**
     * Récupérer les annonces pour un étudiant
     */
    public function getForEtudiant(string $etudiantId): Collection;

    /**
     * Compter les annonces par statut
     */
    public function countByStatut(): array;

    /**
     * Exporter les annonces
     */
    public function export(array $filters): array;
}