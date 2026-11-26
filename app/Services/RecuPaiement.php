<?php

namespace App\Services;

use App\Models\AssignationChambre;
use App\Models\Paiement;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Reçu PDF d'un paiement (vue `pdf/recu-paiement.blade.php`), le même pour l'administration (Paiements › « Imprimer
 * le reçu ») et pour l'étudiant (Mes paiements › Historique) : une seule page A4, un seul reçu pour les types
 * enregistrés ensemble (`Paiement::paiementsDuRecu`).
 */
class RecuPaiement
{
    /** Lignes de répartition sur une page : au-delà, les dernières sont regroupées en « Autres périodes ». */
    private const LIGNES_MAX = 12;

    /** Reçu affiché dans le navigateur, ou téléchargé avec `$telecharger`. */
    public function reponse(Paiement $paiement, bool $telecharger = false): \Illuminate\Http\Response
    {
        [$pdf, $premier] = $this->pdf($paiement);
        $fichier = 'recu-'.($premier->numero_paiement ?: $premier->getKey()).'.pdf';

        return $telecharger ? $pdf->download($fichier) : $pdf->stream($fichier);
    }

    /** @return array{0: \Barryvdh\DomPDF\PDF, 1: Paiement} le PDF et le premier paiement du reçu (son numéro). */
    private function pdf(Paiement $paiement): array
    {
        $paiements = $paiement->paiementsDuRecu()->load(['resident', 'chambre', 'cite', 'repartitions']);
        $paiement = $paiements->first();

        // Une seule page : au-delà de 12 lignes (moins une par type supplémentaire), les dernières sont regroupées en une ligne.
        $max = max(3, self::LIGNES_MAX - ($paiements->count() - 1));
        $repartitions = $paiements->flatMap(fn (Paiement $p) => $p->repartitions)->values();
        $reste = $repartitions->slice($max - 1);
        $lignes = $repartitions->count() > $max ? $repartitions->take($max - 1) : $repartitions;

        return [Pdf::loadView('pdf.recu-paiement', [
            'paiement' => $paiement,
            'montant' => formatSystemAmount($paiements->sum('montant')),
            'details' => $paiements->map(function (Paiement $p) {
                $mois = $p->mois ? formatPaiementMois($p->mois, 0) : null;

                return [
                    'numero' => $p->numero_paiement,
                    'type' => $p->type_label,
                    'mois' => $mois && $mois !== '-' ? $mois : null,
                    'montant' => formatSystemAmount($p->montant),
                ];
            })->all(),
            'references' => $paiements->pluck('reference_transaction')->filter()->unique()->join(', '),
            'notes' => $paiements->pluck('notes')->filter()->unique()->join(' '),
            // Filière et niveau de la demande de la campagne de l'attribution (`contrat_id`), à défaut la dernière demande.
            'civilite' => app(ParcoursEtudiant::class)->civilite($paiement->resident),
            'parcours' => app(ParcoursEtudiant::class)->pour($paiement->resident_id,
                $paiement->contrat_id ? AssignationChambre::whereKey($paiement->contrat_id)->value('campagne_id') : null),
            'matricule' => $paiement->resident?->matricule
                ?: $paiement->resident?->demandes()->whereNotNull('num_table_bac')->latest('created_at')->value('num_table_bac'),
            'repartitions' => $lignes->map(fn ($rep) => [
                'libelle' => ['arriere' => 'Arriéré', 'mois_cours' => 'Mois en cours', 'avance' => 'Avance'][$rep->type] ?? ucfirst((string) $rep->type),
                'periode' => $rep->mois && $rep->annee ? str_pad((string) $rep->mois, 2, '0', STR_PAD_LEFT).'/'.$rep->annee : null,
                'montant' => formatSystemAmount($rep->montant),
            ])->when($repartitions->count() > $max, fn ($l) => $l->push([
                'libelle' => sprintf('Autres périodes (%d)', $reste->count()),
                'periode' => null,
                'montant' => formatSystemAmount($reste->sum('montant')),
            ]))->values()->all(),
        ])->setPaper('a4'), $paiement];
    }
}
