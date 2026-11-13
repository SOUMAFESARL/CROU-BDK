<?php

namespace App\Services;

use App\Http\Services\CatalogueSanctions;
use App\Http\Services\NoteConduite;
use App\Models\Etudiant;

/**
 * Note de conduite et sanctions d'un étudiant, pour l'espace étudiant (onglet « Ma conduite » du profil et page
 * « Conduite et signalements »). Seules les sanctions qui le visent personnellement sont montrées.
 */
class EtudiantConduite
{
    private const STATUTS = [
        'active' => ['En vigueur', 'danger'],
        'levee' => ['Levée', 'neutral'],
        'annulee' => ['Annulée', 'success'],
    ];

    public function __construct(private NoteConduite $conduite, private CatalogueSanctions $catalogue) {}

    public function props(Etudiant $etudiant): array
    {
        $bilan = $this->conduite->bilan((string) $etudiant->getKey());
        $part = $bilan['initiale'] > 0 ? $bilan['note'] / $bilan['initiale'] : 1;

        return [
            'note' => $bilan['note'],
            'initiale' => $bilan['initiale'],
            'retires' => $bilan['retires'],
            // Ton de la jauge : au moins 75 % de la note initiale, au moins la moitié, en dessous.
            'ton' => $part >= 0.75 ? 'success' : ($part >= 0.5 ? 'warning' : 'danger'),
            'sanctions' => $bilan['sanctions']->map(function (array $ligne) {
                $sanction = $ligne['sanction'];
                [$libelle, $ton] = self::STATUTS[$sanction->statut] ?? [ucfirst((string) $sanction->statut), 'neutral'];

                return [
                    'id' => (string) $sanction->getKey(),
                    'numero' => (string) $sanction->numero_sanction,
                    'type' => $this->catalogue->libelle($sanction->type),
                    'date' => $sanction->date_decision?->format('d/m/Y'),
                    'periode' => $sanction->date_debut && $sanction->date_fin
                        ? 'Du '.$sanction->date_debut->format('d/m/Y').' au '.$sanction->date_fin->format('d/m/Y')
                        : null,
                    'motif' => $sanction->motif,
                    'points' => $ligne['points'],
                    // Une sanction annulée restitue ses points.
                    'compte' => $ligne['compte'],
                    'statut' => ['cle' => $sanction->statut, 'libelle' => $libelle, 'ton' => $ton],
                ];
            })->values()->all(),
        ];
    }
}
