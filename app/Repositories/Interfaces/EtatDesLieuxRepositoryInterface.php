<?php

namespace App\Repositories\Interfaces;

use App\Models\EtatDesLieux;
use Illuminate\Database\Eloquent\Collection;

interface EtatDesLieuxRepositoryInterface
{
    /** Liste tous les EDL (entrée + sortie), avec filtres optionnels */
    public function all(array $filters = []): Collection;

    /** Trouver un EDL par son ID (avec pièces, éléments, images) */
    public function findById(string $id): ?EtatDesLieux;

    /** Trouver l'EDL d'entrée pour une chambre et un étudiant donnés */
    public function findEntree(string $idChambre, string $idEtudiant): ?EtatDesLieux;

    /** Trouver l'EDL de sortie pour une chambre et un étudiant donnés */
    public function findSortie(string $idChambre, string $idEtudiant): ?EtatDesLieux;

    /**
     * Créer un EDL complet (en-tête + pièces + éléments + photos).
     *
     * $data = [
     *   'id_cite'               => '...',
     *   'id_batiment'           => '...',
     *   'id_chambre'            => '...',
     *   'id_etudiant'           => '...',
     *   'type_etat'             => 'entree'|'sortie',
     *   'date_etat'             => '2026-07-01',
     *   'nbre_cle_recus'        => 2,
     *   'observation_generale'  => '...',
     *   'signature_crou'        => 'data:image/png;base64,...',
     *   'signature_etudiant'    => 'data:image/png;base64,...',
     *   'pieces' => [
     *     [
     *       'name_piece' => 'Chambre',
     *       'ordre'      => 1,
     *       'elements'   => [
     *         ['name_element' => 'Sol', 'etat' => 'bon', 'observation' => '', 'ordre' => 1],
     *         ...
     *       ],
     *       'photos' => ['chemin/vers/photo1.jpg', ...]   // chemins après upload
     *     ],
     *     ...
     *   ],
     * ]
     */
    public function create(array $data): EtatDesLieux;

    /** Mettre à jour l'en-tête d'un EDL (signatures, clés, observations) */
    public function update(string $id, array $data): EtatDesLieux;

    /** Supprimer un EDL et tout ce qui y est rattaché (cascade applicative) */
    public function delete(string $id): bool;

    /** Historique complet pour un étudiant donné */
    public function historiqueEtudiant(string $idEtudiant): Collection;

    /** Liste des EDL d'une chambre, triés par date décroissante */
    public function parChambre(string $idChambre): Collection;
}
