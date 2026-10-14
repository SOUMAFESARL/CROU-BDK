<?php


namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface LoyerRepositoryInterface
{
    /**
     * Récupère tous les loyers
     */
    public function all(): Collection;

    /**
     * Récupère un loyer par son ID
     */
    public function find(string $id): ?object;

    /**
     * Crée un nouveau loyer
     */
    public function create(array $data): object;

    /**
     * Met à jour un loyer
     */
    public function update(string $id, array $data): bool;

    /**
     * Supprime un loyer
     */
    public function delete(string $id): bool;

    /**
     * Récupère les loyers d'un étudiant
     */
    public function getByEtudiant(string $etudiantId): Collection;

    /**
     * Récupère les loyers d'un étudiant avec pagination
     */
    public function getByEtudiantPaginated(string $etudiantId, int $perPage = 10): LengthAwarePaginator;

    /**
     * Récupère les loyers actifs d'un étudiant
     */
    public function getActifByEtudiant(string $etudiantId): Collection;

    /**
     * Calcule les arriérés d'un étudiant
     */
    public function calculerArriere(string $etudiantId): array;

    /**
     * Calcule les arriérés pour plusieurs étudiants
     */
    public function calculerArrieresGroupes(array $etudiantIds): array;

    /**
     * Récupère les loyers en retard
     */
    public function getEnRetard(): Collection;

    /**
     * Récupère les loyers par résidence
     */
    public function getByCite(string $citeId): Collection;

    /**
     * Récupère les loyers par année universitaire
     */
    public function getByAnneeUniversitaire(string $annee): Collection;

    /**
     * Met à jour le solde d'un loyer
     */
    public function updateSolde(string $loyerId): bool;

    /**
     * Applique les pénalités pour tous les loyers en retard
     */
    public function appliquerPenalites(): int;

    /**
     * Enregistre un paiement pour un loyer
     */
    public function enregistrerPaiement(string $loyerId, float $montant, ?string $datePaiement = null): bool;

    /**
     * Récupère les statistiques des loyers
     */
    public function getStatistiques(): array;

    /**
     * Récupère les loyers avec filtres
     */
    public function getWithFilters(array $filters): Collection;

    /**
     * Récupère les loyers avec filtres paginés
     */
    public function getWithFiltersPaginated(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function getLoyerByMois(string $etudiantId, string $mois, string $annee): ?object;


public function getTotalSoldeRestant(): float;
public function getTotalMontantTotal(): float;
public function getTotalPaye(): float;
public function getNombreLoyersEnRetard(): int;
public function getTotalLoyersEnRetard(): float;
public function getStatistiquesLoyers(?string $annee = null, ?string $mois = null, ?string $residence = null): array;
     public function getStatistiquesParResidence(): Collection;
    public function getStatistiquesParResidenceFiltre(?string $mois = null, ?string $annee = null): Collection;

    }
