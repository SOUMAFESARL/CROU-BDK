<?php
// app/Repositories/Interfaces/PaiementRepositoryInterface.php

namespace App\Repositories\Interfaces;

use App\Models\Paiement;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PaiementRepositoryInterface
{
    public function getAllPaginated(array $filters = [], int $perPage = 20): LengthAwarePaginator;
    public function findById(string $id): ?Paiement;
    public function findByNumero(string $numero): ?Paiement;
    public function create(array $data): Paiement;
    public function update(string $id, array $data): bool;
    public function delete(string $id): bool;
    public function getTotalEncaissementMois(): float;
    public function getTauxRecouvrement(): float;
    public function getNombrePaiementsMois(): int;
    public function getImpayes(): float;
    public function getTauxImpayes(): float;
    public function getLoyerMoyen(): float;
   // public function getStatistiquesParResidence(): Collection;
    public function getPaiementsParMois(int $nbMois = 6): Collection;
    public function getPaiementsParStatut(): Collection;

        public function all(): Collection;
    public function find(string $id): ?object;
    public function getLastPaiementByEtudiant(string $etudiantId): ?object;
    
    
    public function getByEtudiant(string $etudiantId): Collection;
    public function getByEtudiantPaginated(string $etudiantId, int $perPage = 10): LengthAwarePaginator;
    public function getByStatut(string $statut): Collection;
    public function getByPeriode(string $mois, string $annee): Collection;

    public function getTotalEncaissement(): float;
public function getTotalDu(): float;
public function getNombrePaiementsEnRetard(): int;
public function getTotalPaiementsEnRetard(): float;
public function getQuery();
}