<?php

namespace App\Repositories\Interfaces;

use App\Models\Chambre;
use Illuminate\Database\Eloquent\Collection;

interface ChambreRepositoryInterface
{
    /**
     * Récupérer toutes les chambres
     */
    public function getAll(): Collection;

    /**
     * Récupérer une chambre par son code
     */
    public function findByCode(string $code): ?Chambre;

    /**
     * Récupérer une chambre par son Id
     */
    public function findById(string $code): ?Chambre;

    /**
     * Récupérer les chambres d'un bâtiment
     */
    public function getByBatiment(string $batimentCode): Collection;

    /**
     * Récupérer les chambres par niveau (niveau_aile_code)
     */
    public function getByNiveau(string $niveauCode, string $batimentCode): Collection;

    /**
     * Récupérer les chambres créées (avec numero_chambre et type_chambre_code)
     */
    public function getChambresCreees(string $niveauCode, string $batimentCode): Collection;

    /**
     * Récupérer les chambres non créées (sans numero_chambre ou type_chambre_code)
     */
    public function getChambresNonCreees(string $niveauCode, string $batimentCode): Collection;

    /**
     * Créer (compléter) une chambre — assigner numero et type
     */
    public function completeChambre(string $code, string $numeroChambre, string $typeChambreCode): Chambre;

    /**
     * Vérifier si un numéro de chambre existe déjà dans le même niveau
     */
    public function isNumeroDuplicate(string $numeroChambre, string $batimentCode, string $niveauCode, ?string $excludeCode = null): bool;

    /**
     * Récupérer les types d'occupation autorisés pour une chambre
     * selon le type d'occupation du bâtiment parent
     * - Bâtiment mixte  → ['mixte', 'hommes', 'femmes']
     * - Bâtiment hommes → ['hommes']
     * - Bâtiment femmes → ['femmes']
     */
    public function getAllowedOccupationTypes(string $batimentCode): array;

    /**
     * Mettre à jour une chambre
     */
    public function update(string $code, array $data): Chambre;

    /**
     * Supprimer une chambre (réinitialiser numero et type)
     */
    public function resetChambre(string $code): Chambre;
    public function getDisponibles(string $campagneId): Collection;

    

}