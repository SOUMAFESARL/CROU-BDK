<?php

namespace App\Repositories\Interfaces;

interface OccupationServiceInterface
{
    /**
     * Valider que le type d'occupation d'un bâtiment est compatible
     * avec le type d'occupation de sa cité parente.
     *
     * Règles :
     * - Cité mixte  → bâtiment peut être mixte, hommes ou femmes
     * - Cité hommes → bâtiment doit être hommes uniquement
     * - Cité femmes → bâtiment doit être femmes uniquement
     *
     * @throws \App\Exceptions\OccupationIncompatibleException
     */
    public function validateBatimentOccupation(string $citeCode, string $typeOccupationBatiment): void;

    /**
     * Valider que le type d'occupation d'une chambre est compatible
     * avec le type d'occupation du bâtiment parent.
     *
     * Règles :
     * - Bâtiment mixte  → chambre peut être mixte, hommes ou femmes
     * - Bâtiment hommes → chambre doit être hommes uniquement
     * - Bâtiment femmes → chambre doit être femmes uniquement
     *
     * @throws \App\Exceptions\OccupationIncompatibleException
     */
    public function validateChambreOccupation(string $batimentCode, string $typeOccupationChambre): void;

    /**
     * Retourner les types d'occupation autorisés pour un bâtiment
     * selon le type de sa cité parente.
     *
     * @return array<string> Ex: ['mixte', 'hommes', 'femmes'] ou ['hommes']
     */
    public function getAllowedTypesForBatiment(string $citeCode): array;

    /**
     * Retourner les types d'occupation autorisés pour une chambre
     * selon le type de son bâtiment parent.
     *
     * @return array<string>
     */
    public function getAllowedTypesForChambre(string $batimentCode): array;

    /**
     * Propager le type d'occupation d'une cité vers ses bâtiments
     * lorsqu'elle passe de mixte à hommes ou femmes.
     * Met à jour tous les bâtiments et chambres enfants.
     */
    public function propagateOccupationFromCite(string $citeCode, string $nouveauType): void;

    /**
     * Propager le type d'occupation d'un bâtiment vers ses chambres.
     */
    public function propagateOccupationFromBatiment(string $batimentCode, string $nouveauType): void;
}