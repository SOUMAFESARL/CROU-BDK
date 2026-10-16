<?php

namespace App\Services;

use App\Http\Services\EdlPhotoService;
use App\Models\AssignationChambre;
use App\Models\EdlElement;
use App\Models\EdlImage;
use App\Models\EdlPiece;
use App\Models\EtatDesLieux;
use App\Models\Etudiant;
use App\Repositories\Interfaces\EtatDesLieuxRepositoryInterface;

/**
 * Props de la page React « État des lieux » de l'espace étudiant (Etudiant/EtatDesLieux).
 * Constats d'entrée et de sortie de la chambre de l'étudiant (assignation confirmée, sinon la dernière),
 * et ce que l'étudiant peut encore y faire : saisir ou modifier l'entrée tant que le CROU ne l'a pas signée,
 * signer la sortie établie par le CROU. Les règles sont revérifiées par dashboardController::storeEdl / updateEdl.
 */
class EtudiantEtatDesLieux
{
    public function __construct(
        private EtudiantChambre $chambres,
        private EtatDesLieuxRepositoryInterface $etatsDesLieux,
    ) {}

    public function props(Etudiant $etudiant): array
    {
        $id = (string) $etudiant->getKey();
        $assignation = $this->chambres->assignation($id)
            ?? AssignationChambre::with(['chambre.batiment', 'chambre.cite'])->where('etudiant_id', $id)->latest('date_assignation')->first();
        $chambre = $assignation?->chambre;
        $entree = $chambre ? $this->etatsDesLieux->findEntree((string) $chambre->id_chambre, $id) : null;
        $sortie = $chambre ? $this->etatsDesLieux->findSortie((string) $chambre->id_chambre, $id) : null;

        return [
            'etudiant' => ['id' => $id, 'nom' => $etudiant->name],
            'chambre' => $chambre ? [
                'idCite' => (string) $chambre->cite_id,
                'idBatiment' => (string) $chambre->batiment_id,
                'idChambre' => (string) $chambre->id_chambre,
                'cite' => $chambre->cite?->name,
                'batiment' => $chambre->batiment?->name,
                'numero' => $chambre->numero_chambre ?: $chambre->code_chambre,
            ] : null,
            'entree' => $entree ? $this->constat($entree) : null,
            'sortie' => $sortie ? $this->constat($sortie) : null,
            'urls' => [
                'enregistrer' => route('etudiants.store_eld'),
                'chambre' => route('etudiants.dashboard', ['section' => 'chambre']),
                'maintenance' => route('etudiants.dashboard', ['section' => 'maintenance']),
            ],
        ];
    }

    private function constat(EtatDesLieux $edl): array
    {
        return [
            'id' => (string) $edl->getKey(),
            'lieu' => ['idCite' => (string) $edl->id_cite, 'idBatiment' => (string) $edl->id_batiment, 'idChambre' => (string) $edl->id_chambre],
            'date' => $edl->date_etat?->format('Y-m-d'),
            'dateAffichee' => $edl->date_etat?->format('d/m/Y'),
            'cles' => $edl->nbre_cle_recus !== null ? (int) $edl->nbre_cle_recus : null,
            'observation' => $edl->observation_generale,
            'signatureCrou' => EdlPhotoService::url($edl->signature_crou),
            'signatureEtudiant' => EdlPhotoService::url($edl->signature_etudiant),
            'pieces' => $edl->pieces->map(fn (EdlPiece $piece) => [
                'nom' => (string) $piece->name_piece,
                'photos' => $piece->images->filter(fn (EdlImage $image) => $image->chemin_fichier)
                    ->map(fn (EdlImage $image) => ['chemin' => $image->chemin_fichier, 'url' => $image->url])->values()->all(),
                'elements' => $piece->elements->sortBy('ordre')->map(fn (EdlElement $element) => [
                    'nom' => (string) $element->name_element,
                    'etat' => in_array($element->etat, ['bon', 'moyen', 'mauvais'], true) ? $element->etat : null,
                    'observation' => $element->observation,
                    'photo' => $element->photo ? ['chemin' => $element->photo, 'url' => $element->photo_url] : null,
                ])->values()->all(),
            ])->values()->all(),
            'pdf' => route('etudiants.documents.inventory', $edl->getKey()),
            'modifier' => route('etudiants.update_eld', $edl->getKey()),
        ];
    }
}
