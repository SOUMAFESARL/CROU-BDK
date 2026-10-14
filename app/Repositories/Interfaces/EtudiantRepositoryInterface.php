<?php
namespace App\Repositories\Interfaces;

use App\Models\Etudiant;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

interface EtudiantRepositoryInterface
{
    public function getAll(): Collection;
    public function create(array $data): Etudiant;
    public function update(string $id, array $data): Etudiant;
    public function find(string $id): ?Etudiant;
    public function delete(int $id): bool;

    public function getAllWithFilters(?string $etat = null): Collection; // Type de retour correct
    public function findWithRelations(string $id): ?Etudiant;

    public function countResidents(): int;
    public function countNouvellesDemandes(): int;
    public function countEnAttente(): int;
    public function countAnnulees(): int;
    public function getStats(): array;
    public function verifierEligibilite(mixed $demande, mixed $campagne): array;
}