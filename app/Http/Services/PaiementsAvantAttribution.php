<?php

namespace App\Http\Services;

use App\Http\Services\Eligibilite\CriteresCampagne;
use App\Models\Campagne;
use App\Models\Chambre;
use App\Models\Etudiant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Paiements exigés avant l'attribution d'une chambre : la caution (nouvelles demandes) et l'avance (nouvelles demandes
 * et réadmissions), en mois de loyer selon les conditions financières de la campagne pour le volet de l'étudiant
 * (`CriteresCampagne::conditionsFinancieres`).
 *
 * Sont comptés les paiements validés de l'étudiant enregistrés sans chambre (« Nouveau paiement » avant l'attribution)
 * depuis le dépôt de sa demande pour la campagne. Le montant exigé se calcule sur le loyer mensuel du type de la chambre
 * attribuée (à défaut, le prix de la chambre).
 */
class PaiementsAvantAttribution
{
    public function __construct(private CriteresCampagne $criteres) {}

    /**
     * Montants exigés, versés et restant dus par type (`caution`, `avance`) ; types sans exigence omis.
     *
     * @return array<string, array{exige: float, verse: float, reste: float}>
     */
    public function etat(Etudiant $etudiant, Chambre $chambre, Campagne $campagne): array
    {
        $loyer = (float) ($chambre->typeChambre?->montant ?: $chambre->prix ?: 0);
        if ($loyer <= 0 || ! Schema::hasTable('paiements')) {
            return [];
        }
        $etudiantId = (string) $etudiant->getKey();
        $conditions = $this->criteres->conditionsFinancieres($campagne, $this->criteres->voletEtudiant($campagne, $etudiantId));
        $exiges = array_filter([
            'caution' => $conditions['caution_mois'] * $loyer,
            PaiementService::TYPE_AVANCE => $conditions['avance_mois'] * $loyer,
        ], fn (float $montant) => $montant > 0);
        if ($exiges === []) {
            return [];
        }

        $depot = DB::table('demande')->where('etudiant_id', $etudiantId)->where('campagne_id', $campagne->getKey())->min('created_at');
        $verses = DB::table('paiements')
            ->where('resident_id', $etudiantId)
            ->where('statut_paiement', 'valide')
            ->whereNull('chambre_id')
            ->whereIn('type_paiement', array_keys($exiges))
            ->when(Schema::hasColumn('paiements', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
            ->when($depot, fn ($q) => $q->where('created_at', '>=', $depot))
            ->groupBy('type_paiement')
            ->selectRaw('type_paiement, SUM(montant) as total')
            ->pluck('total', 'type_paiement');

        return collect($exiges)->map(fn (float $exige, string $type) => [
            'exige' => $exige,
            'verse' => (float) ($verses[$type] ?? 0),
            'reste' => max(0.0, $exige - (float) ($verses[$type] ?? 0)),
        ])->all();
    }

    /** Refuse l'attribution tant que la caution et l'avance exigées ne sont pas entièrement payées. */
    public function verifier(Etudiant $etudiant, Chambre $chambre, Campagne $campagne): void
    {
        $manquants = collect($this->etat($etudiant, $chambre, $campagne))->filter(fn (array $etat) => $etat['reste'] > 0);
        if ($manquants->isEmpty()) {
            return;
        }
        $libelles = ['caution' => 'Caution', PaiementService::TYPE_AVANCE => 'Avance'];
        $detail = $manquants->map(fn (array $etat, string $type) => sprintf(
            '%s : %s FCFA restant sur %s FCFA',
            $libelles[$type],
            number_format($etat['reste'], 0, ',', ' '),
            number_format($etat['exige'], 0, ',', ' ')
        ))->join(' ; ');

        throw ValidationException::withMessages([
            'paiements' => sprintf('Attribution impossible : %s n’a pas encore payé ce qui est exigé avant l’attribution (Paiements › Nouveau paiement). %s.',
                trim((string) $etudiant->name) ?: 'l’étudiant',
                $detail),
        ]);
    }
}
