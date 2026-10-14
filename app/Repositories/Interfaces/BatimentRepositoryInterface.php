<?php

namespace App\Repositories\Interfaces;

use App\Models\Batiment;
use Illuminate\Database\Eloquent\Collection;

interface BatimentRepositoryInterface
{
    /**
     * Récupérer tous les bâtiments
     */
    public function getAll(): Collection;

    /**
     * Récupérer un bâtiment par son code
     */
    public function findByCode(string $code): ?Batiment;

    /**
     * Récupérer les bâtiments d'une cité
     */
    public function getByCite(string $citeCode): Collection;

    /**
     * Récupérer les bâtiments d'un campus
     */
    public function getByCampus(string $campusCode): Collection;

    /**
     * Créer un bâtiment
     */
    public function create(array $data): Batiment;

    /**
     * Mettre à jour un bâtiment
     */
    public function update(string $code, array $data): Batiment;

    /**
     * Supprimer un bâtiment
     */
    public function delete(string $code): bool;

    /**
     * Vérifier si un bâtiment a des chambres
     */
    public function hasChambres(string $code): bool;

    /**
     * Récupérer les types d'occupation autorisés pour un bâtiment
     * selon le type de la cité parente
     * - Cité mixte  → ['mixte', 'hommes', 'femmes']
     * - Cité hommes → ['hommes']
     * - Cité femmes → ['femmes']
     */
    public function getAllowedOccupationTypes(string $citeCode): array;

    /**
     * Récupérer un bâtiment avec ses niveaux et ailes
     */
    public function getWithStructure(string $code): ?Batiment;

    /**
     * Mettre à jour les totaux d'un bâtiment
     */
    public function updateTotals(string $code): Batiment;

    /**
     * Vérifier si un bâtiment avec ce nom existe déjà dans la cité.
     * Passer $excludeCode pour ignorer un bâtiment lors d'une mise à jour.
     */
    public function existsInCite(string $citeCode, string $name, ?string $excludeCode = null): bool;
}