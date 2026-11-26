<?php

namespace App\Services;

use App\Models\Campagne;
use App\Models\Loyer;
use App\Models\Paiement;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Chiffres de la page « Rapport financier » (Paiements) pour une campagne (ou toutes), un mois de cette campagne
 * (`aaaa-mm`, facultatif) et une cité (facultative).
 *
 * Deux sources :
 * - les **loyers facturés** de la campagne (`Loyer::scopeDeCampagne`, mois = mois facturé) donnent l'attendu, le
 *   recouvré (`total_paye`), les impayés (`solde_restant`), les retards (solde dû après l'échéance) et le taux de
 *   recouvrement, comme sur la page Paiements ;
 * - les **paiements validés** de la campagne (`Paiement::scopeDeCampagne`, mois = date du paiement, à défaut de la
 *   création), tous types confondus (loyer, avance, caution…), donnent le total encaissé, sa répartition par mode et
 *   l'évolution mois par mois.
 *
 * Les mois proposés sont ceux du calendrier des loyers de la campagne (`CalendrierLoyer::propose` : enregistré, sinon
 * proposé). Deux requêtes en tout, agrégées en PHP pour rester portables (MySQL en production, SQLite pour les tests).
 */
class RapportFinancier
{
    private const MODES = [
        'orange_money' => ['Orange Money', '#f47b20'],
        'mtn_money' => ['MTN Money', '#ffcb00'],
        'moov_money' => ['Moov Money', '#00a651'],
        'especes' => ['Espèces', '#1e7aa8'],
        'virement' => ['Virement bancaire', '#6a4d8a'],
        'carte_bancaire' => ['Carte bancaire', '#b13e3e'],
        'autre' => ['Autre', '#5b7b99'],
    ];

    private const MOIS_COURTS = [1 => 'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];

    public function __construct(private CalendrierLoyer $calendrier) {}

    /**
     * Mois de loyer de la campagne (`aaaa-mm`), dans l'ordre ; aucun sans campagne.
     *
     * @return list<string>
     */
    public function moisDeCampagne(?Campagne $campagne): array
    {
        return $campagne ? array_column($this->calendrier->propose($campagne), 'mois') : [];
    }

    /** `$mois` : un mois de `moisDeCampagne($campagne)` (sinon toute la campagne). */
    public function donnees(?Campagne $campagne, ?string $mois = null, ?string $cite = null): array
    {
        $campagneId = $campagne ? (string) $campagne->getKey() : null;
        $moisCampagne = $this->moisDeCampagne($campagne);
        $mois = in_array($mois, $moisCampagne, true) ? $mois : null;

        $loyersCampagne = $this->loyers($campagneId, $cite);
        $paiementsCampagne = $this->paiements($campagneId, $cite);
        $loyers = $mois ? $loyersCampagne->where('mois', $mois) : $loyersCampagne;
        $paiements = $mois ? $paiementsCampagne->where('mois', $mois) : $paiementsCampagne;

        $attendu = $loyers->sum('montant');
        $recouvre = $loyers->sum('paye');
        $impayes = $loyers->sum('solde');
        $retards = $loyers->where('en_retard', true);
        $encaisse = $paiements->sum('montant');

        // Évolution sur toute la campagne, même avec un mois choisi : ses mois de loyer, plus les mois où des paiements
        // ont été faits en dehors (caution ou avance versée avant le premier mois, par exemple). Sans campagne : tous
        // les mois qui ont un loyer ou un paiement.
        $axe = collect($moisCampagne)
            ->merge($paiementsCampagne->pluck('mois'))
            ->merge($campagne ? [] : $loyersCampagne->pluck('mois'))
            ->filter()->unique()->sort()->values();

        return [
            'stats' => [
                'total_attendu' => $attendu,
                'total_recouvre' => $recouvre,
                'total_encaisse' => $encaisse,
                'total_impayes' => $impayes,
                'total_retard' => $retards->sum('solde'),
                'nb_retard' => $retards->count(),
                'nb_impayes' => $loyers->where('solde', '>', 0)->count(),
                'nb_loyers' => $loyers->count(),
                'nb_paiements' => $paiements->count(),
                'taux_recouvrement' => self::taux($recouvre, $attendu),
            ],
            'evolution' => $axe->map(fn (string $cle) => [
                'label' => self::libelleCourt($cle),
                'encaisse' => $paiementsCampagne->where('mois', $cle)->sum('montant'),
                'attendu' => $loyersCampagne->where('mois', $cle)->sum('montant'),
            ])->all(),
            // Loyers de la période : payé, reste dû pas encore échu, reste dû en retard (le total vaut l'attendu).
            'repartition' => [
                'paye' => $recouvre,
                'a_echoir' => max(0, $impayes - $retards->sum('solde')),
                'retard' => $retards->sum('solde'),
            ],
            'statsParMode' => $this->parMode($paiements, $encaisse),
            // Valeurs du filtre « Mois » : les mois de loyer de la campagne.
            'mois' => array_map(fn (string $cle) => ['value' => $cle, 'label' => ucfirst(CalendrierLoyer::libelle($cle))], $moisCampagne),
            'moisChoisi' => $mois,
        ];
    }

    /** Loyers non annulés de la campagne (tous sans campagne), avec leur mois facturé `aaaa-mm`. */
    private function loyers(?string $campagne, ?string $cite): Collection
    {
        // En retard à partir du lendemain de l'échéance.
        $aujourdhui = today();

        return Loyer::query()
            ->where(fn ($q) => $q->where('statut', '!=', 'annule')->orWhereNull('statut'))
            ->when($campagne, fn ($q, $id) => $q->deCampagne($id))
            ->when($cite, fn ($q, $id) => $q->where('cite_id', $id))
            ->get(['mois_facturation', 'annee_facturation', 'montant_total', 'total_paye', 'solde_restant', 'date_limit_paiement', 'created_at'])
            ->map(function (Loyer $loyer) use ($aujourdhui) {
                $echeance = $loyer->date_limit_paiement ? Carbon::parse($loyer->date_limit_paiement)->startOfDay() : null;
                $solde = max(0, (float) $loyer->solde_restant);
                $mois = $loyer->mois_facturation && $loyer->annee_facturation
                    ? sprintf('%04d-%02d', $loyer->annee_facturation, $loyer->mois_facturation)
                    : ($echeance ?? $loyer->created_at)?->format('Y-m');

                return [
                    'mois' => $mois,
                    'montant' => (float) $loyer->montant_total,
                    'paye' => max(0, (float) $loyer->total_paye),
                    'solde' => $solde,
                    'en_retard' => $solde > 0 && $echeance !== null && $echeance->lt($aujourdhui),
                ];
            });
    }

    /** Paiements validés de la campagne (tous sans campagne), tous types, avec le mois `aaaa-mm` du paiement. */
    private function paiements(?string $campagne, ?string $cite): Collection
    {
        return Paiement::query()
            ->where('statut_paiement', 'valide')
            ->when($campagne, fn ($q, $id) => $q->deCampagne($id))
            ->when($cite, fn ($q, $id) => $q->where('cite_id', $id))
            ->get(['methode_paiement', 'montant', 'date_paiement', 'created_at'])
            ->map(fn (Paiement $paiement) => [
                'methode' => (string) ($paiement->methode_paiement ?: 'autre'),
                'mois' => ($paiement->date_paiement ?? $paiement->created_at)?->format('Y-m'),
                'montant' => (float) $paiement->montant,
            ]);
    }

    private function parMode(Collection $paiements, float $encaisse): array
    {
        return $paiements->groupBy('methode')->map(function (Collection $lignes, string $methode) use ($encaisse) {
            [$libelle, $couleur] = self::MODES[$methode] ?? [ucfirst(str_replace('_', ' ', $methode)), '#5b7b99'];
            $total = $lignes->sum('montant');

            return [
                'methode' => $methode,
                'libelle' => $libelle,
                'couleur' => $couleur,
                'nombre' => $lignes->count(),
                'total' => $total,
                'moyenne' => round($total / $lignes->count(), 2),
                'pourcentage' => $encaisse > 0 ? round($total / $encaisse * 100, 1) : 0,
            ];
        })->sortByDesc('total')->values()->all();
    }

    /** `aaaa-mm` → « Déc 2026 ». */
    private static function libelleCourt(string $cle): string
    {
        return (self::MOIS_COURTS[(int) substr($cle, 5, 2)] ?? $cle).' '.substr($cle, 0, 4);
    }

    /** Part recouvrée de l'attendu, en %, bornée à 0–100. */
    private static function taux(float $recouvre, float $attendu): float
    {
        return $attendu > 0 ? round(min(100, $recouvre / $attendu * 100), 1) : 0;
    }
}
