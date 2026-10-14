<?php

namespace App\Repositories\Interfaces;

use App\Models\DemandeEtudiant;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface DemandeRepositoryInterface
{
    public function create(array $data): DemandeEtudiant;
    public function update(string $id, array $data): DemandeEtudiant;
    public function find(string $id): ?DemandeEtudiant;
    public function delete(string $id): bool;
    public function findByEtudiant(string $etudiantId): Collection;
    public function findByStatut(string $statut): Collection;
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function countByStatut(): array;
    public function updateStatut(string $id, string $statut, ?string $commentaires = null): bool;
    public function changerStatut(string $id, string $statut, ?string $commentaires = null): array;
}