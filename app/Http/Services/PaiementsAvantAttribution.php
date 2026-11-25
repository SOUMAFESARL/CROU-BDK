<?php

namespace App\Http\Services;

use App\Http\Services\Eligibilite\CriteresCampagne;
use App\Models\Campagne;
use App\Models\Chambre;
use App\Models\Etudiant;
use App\Models\TypeChambre;
use Illuminate\Support\Collection;
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
        $volet = $this->criteres->voletEtudiant($campagne, (string) $etudiant->getKey());

        return $this->soldes((string) $etudiant->getKey(), $campagne, $volet, $loyer);
    }

    /**
     * Paiements exigés des étudiants admis à loger, avant le choix de leur chambre : le montant exigé se calcule sur le
     * type de chambre actif le moins cher (le contrôle définitif, sur la chambre attribuée, reste `verifier()`).
     * `paye` : tout ce qui est exigé est versé (ou rien n'est exigé).
     *
     * @param  iterable<Etudiant>  $etudiants  avec `derniereDemande.campagne` chargée
     * @return array<string, array{volet: string, paye: bool, loyer: float, types: array<string, array{exige: float, verse: float, reste: float}>}> par identifiant d'étudiant
     */
    public function etatsAvantChambre(iterable $etudiants): array
    {
        $loyer = (float) $this->typesChambre()->min('montant');
        $payable = $loyer > 0 && Schema::hasTable('paiements');

        return collect($etudiants)->mapWithKeys(function (Etudiant $etudiant) use ($loyer, $payable) {
            $id = (string) $etudiant->getKey();
            $campagne = $etudiant->derniereDemande?->campagne;
            $volet = $this->criteres->voletEtudiant($campagne, $id);
            $types = $payable && $campagne ? $this->soldes($id, $campagne, $volet, $loyer) : [];

            return [$id => [
                'volet' => $volet,
                'paye' => collect($types)->every(fn (array $etat) => $etat['reste'] <= 0),
                'loyer' => $loyer,
                'types' => $types,
            ]];
        })->all();
    }

    /** Types de chambre actifs avec un loyer mensuel : base du montant d'un paiement avant l'attribution de la chambre. */
    public function typesChambre(): Collection
    {
        if (! Schema::hasTable('type_chambre')) {
            return collect();
        }

        return TypeChambre::query()
            ->when(Schema::hasColumn('type_chambre', 'is_active'), fn ($q) => $q->where(fn ($q) => $q->whereNull('is_active')->orWhere('is_active', true)))
            ->where('montant', '>', 0)
            ->orderBy('montant')->orderBy('name')
            ->get();
    }

    /** Exigé, versé et restant dû par type, pour un loyer mensuel donné ; en réadmission, la caution n'est jamais exigée. */
    private function soldes(string $etudiantId, Campagne $campagne, string $volet, float $loyer): array
    {
        $conditions = $this->criteres->conditionsFinancieres($campagne, $volet);
        $exiges = array_filter([
            'caution' => $volet === 'readmission' ? 0.0 : $conditions['caution_mois'] * $loyer,
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
