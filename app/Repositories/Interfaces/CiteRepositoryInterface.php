<?php

namespace App\Repositories\Interfaces;

use App\Models\Cite;
use Illuminate\Database\Eloquent\Collection;

interface CiteRepositoryInterface
{
    /**
     * Récupérer toutes les cités
     */
    public function getAll(): Collection;

    /**
     * Récupérer une cité par son code
     */
    public function findByCode(string $code): ?Cite;

    /**
     * Récupérer les cités d'un campus
     */
    public function getByCampus(string $campusCode): Collection;

    /**
     * Créer une cité
     */
    public function create(array $data): Cite;

    /**
     * Mettre à jour une cité
     */
    public function update(string $code, array $data): Cite;

    /**
     * Supprimer une cité
     */
    public function delete(string $code): bool;

    /**
     * Vérifier si une cité a des bâtiments
     */
    public function hasBatiments(string $code): bool;

    /**
     * Récupérer le type d'occupation d'une cité
     * Retourne: 'mixte', 'hommes', 'femmes'
     */
    public function getTypeOccupation(string $code): string;

    /**
     * Récupérer les cités avec leurs bâtiments
     */
    public function getWithBatiments(string $code): ?Cite;
    public function getCiteByStatut( string $statut): Collection;
}