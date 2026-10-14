<?php
// app/Repositories/Interfaces/DocumentEtudiantRepositoryInterface.php
namespace App\Repositories\Interfaces;

use App\Models\DocumentEtudiant;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface DocumentEtudiantRepositoryInterface
{
    public function create(array $data): DocumentEtudiant;
    public function update(string $id, array $data): DocumentEtudiant;
    public function find(string $id): ?DocumentEtudiant;
    public function delete(int $id): bool;
    public function findByEtudiant(int $etudiantId): Collection;
    public function findByType(string $type): Collection;
    public function findByStatut(string $statut): Collection;
    public function updateStatut(string $id, string $statut, ?string $motifRejet = null): bool;
    public function validerDocument(int $id, int $validateurId, int $score = null): bool;
    public function getDocumentsEnAttente(): Collection;
    public function getHistoriqueVersions(int $documentId): Collection;
}