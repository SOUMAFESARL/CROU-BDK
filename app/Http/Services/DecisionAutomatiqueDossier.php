<?php
namespace App\Http\Services;

use App\Models\{Campagne,CampaignScoring,DemandeEtudiant,Etudiant};
use Illuminate\Support\Facades\{DB,Log,Schema};
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Décision automatique à la soumission d'un dossier, selon le barème de scoring actif de la campagne :
 * score ≥ « Score minimum pour l'attribution automatique » → Éligible ; score inférieur → Refusé.
 * Une donnée du barème non renseignée dans le dossier vaut 0 point pour son volet.
 * Sans barème actif, ou si l'éligibilité est bloquée (sanction…), le dossier reste « Soumis » pour une décision manuelle.
 * Un refus automatique peut être levé par l'admin (passage direct en « Éligible »).
 * Si la campagne fixe une date de validation automatique (`date_validation_auto`), les dossiers soumis avant
 * cette date restent « Soumis » et sont décidés en lot ce jour-là (`dossiers:validation-auto`) ; ensuite, chaque
 * nouveau dossier est de nouveau décidé dès sa réception.
 */
class DecisionAutomatiqueDossier
{
    /** Clé posée dans `dossier_transitions.details` des décisions prises par le barème. */
    public const MARQUEUR = 'decision_automatique';
    public const MOTIF_LEVEE = 'Refus automatique levé : dossier rendu éligible par le service Logement.';

    public function __construct(private DossierWorkflow $workflow, private CampaignScoringService $scoring) {}

    /** Applique la décision au dossier encore « Soumis » ; renvoie le nouvel état ou null si rien n'a été décidé. */
    public function decider(string $demandeId): ?string
    {
        try {
            $demande = DemandeEtudiant::find($demandeId);
            if (!$demande || $demande->etat_dossier !== 'soumis' || !Schema::hasTable('campaign_scorings')) return null;
            // Validation différée : le dossier attend la date fixée par la campagne.
            if (Campagne::find($demande->campagne_id)?->validationAutoEnAttente()) return null;
            $config = CampaignScoring::where('campagne_id', $demande->campagne_id)->latest('id')->first();
            if (!$config || !$config->active) return null;
            $resultat = $this->scoring->calculate(Etudiant::findOrFail($demande->etudiant_id), $demande, $config);
            $score = (float) $resultat['score'];
            $minimum = (float) $config->minimum_score;
            $details = [self::MARQUEUR => true] + $resultat;
            $format = fn (float $valeur) => rtrim(rtrim(number_format($valeur, 2, ',', ''), '0'), ',');
            if ($score >= $minimum) {
                $this->workflow->transition($demandeId, 'eligible', 'systeme', null,
                    'Éligibilité automatique : score '.$format($score).'/100 ≥ minimum requis ('.$format($minimum).'/100).', $details);
                return 'eligible';
            }
            $this->workflow->transition($demandeId, 'refuse', 'systeme', null,
                'Refus automatique : score '.$format($score).'/100 inférieur au minimum requis ('.$format($minimum).'/100).', $details);
            return 'refuse';
        } catch (ValidationException $e) {
            // Éligibilité bloquée (sanction, campagne close…) : décision laissée à l'admin.
            Log::info('Décision automatique non appliquée', ['demande_id' => $demandeId, 'raison' => collect($e->errors())->flatten()->first()]);
        } catch (Throwable $e) {
            Log::warning('Décision automatique impossible', ['demande_id' => $demandeId, 'erreur' => $e->getMessage()]);
        }
        return null;
    }

    /**
     * Validation différée : décide les dossiers en attente des campagnes dont la date de validation est atteinte
     * (une seule fois par campagne ; un dossier laissé « Soumis » relève ensuite de l'admin).
     * @return int nombre de dossiers décidés
     */
    public function traiterValidationsDifferees(): int
    {
        if (!Schema::hasColumn('campagne', 'date_validation_auto')) return 0;
        return Campagne::whereNotNull('date_validation_auto')->whereDate('date_validation_auto', '<=', today())
            ->whereNull('validation_auto_effectuee_at')->get()
            ->sum(fn (Campagne $campagne) => $this->traiterCampagne($campagne));
    }

    /**
     * Fixe le moment de la validation automatique de la campagne (null = dès la réception).
     * Une nouvelle date relance le lot à cette date ; le retour à « dès la réception » décide aussitôt les dossiers en attente.
     */
    public function planifier(Campagne $campagne, ?string $date): void
    {
        $ancienne = $campagne->date_validation_auto?->toDateString();
        if ($ancienne === $date) return;
        $enAttente = $ancienne !== null && $campagne->validation_auto_effectuee_at === null;
        $campagne->update(['date_validation_auto' => $date, 'validation_auto_effectuee_at' => null]);
        if ($date === null && $enAttente) $this->traiterCampagne($campagne->fresh());
    }

    /** Décide les dossiers « Soumis » par l'étudiant de la campagne, puis la marque comme traitée. */
    public function traiterCampagne(Campagne $campagne): int
    {
        $decides = 0;
        foreach ($this->soumisParEtudiant($campagne->getKey()) as $demandeId) {
            if ($this->decider($demandeId) !== null) $decides++;
        }
        $campagne->newQuery()->whereKey($campagne->getKey())->update(['validation_auto_effectuee_at' => now()]);
        return $decides;
    }

    /**
     * Dossiers « Soumis » de la campagne dont la dernière entrée en « Soumis » vient de l'étudiant
     * (ni saisie admin, ni retour en réexamen par un agent : ceux-là sont décidés à la main).
     */
    private function soumisParEtudiant(string $campagneId): array
    {
        $ids = DemandeEtudiant::where('campagne_id', $campagneId)->where('etat_dossier', 'soumis')->pluck('id_demande')->all();
        if (!$ids) return [];
        return DB::table('dossier_transitions')->whereIn('demande_id', $ids)->where('nouvel_etat', 'soumis')
            ->orderBy('created_at')->orderBy('id')->get(['demande_id', 'acteur_type'])
            ->keyBy('demande_id')
            ->filter(fn ($transition) => $transition->acteur_type === 'etudiant')
            ->keys()->map(fn ($id) => (string) $id)->values()->all();
    }

    /** Parmi les dossiers donnés, ceux actuellement refusés par le barème (et non par un agent). */
    public function refusAutomatiques(array $demandeIds): array
    {
        $demandeIds = array_values(array_filter(array_map('strval', $demandeIds)));
        if (!$demandeIds) return [];
        $refuses = DemandeEtudiant::whereIn('id_demande', $demandeIds)->where('etat_dossier', 'refuse')->pluck('id_demande')->map(fn ($id) => (string) $id)->all();
        if (!$refuses) return [];
        // La dernière entrée en « Refusé » est celle qui a placé le dossier dans son état actuel.
        return DB::table('dossier_transitions')->whereIn('demande_id', $refuses)->where('nouvel_etat', 'refuse')
            ->orderBy('created_at')->orderBy('id')->get(['demande_id', 'acteur_type', 'details'])
            ->keyBy('demande_id')
            ->filter(fn ($transition) => $transition->acteur_type === 'systeme'
                && (json_decode((string) $transition->details, true)[self::MARQUEUR] ?? false) === true)
            ->keys()->map(fn ($id) => (string) $id)->values()->all();
    }

    public function estRefusAutomatique(string $demandeId): bool
    {
        return $this->refusAutomatiques([$demandeId]) !== [];
    }

    /** Dossiers refusés automatiquement des campagnes actives (candidats à la levée en un clic). */
    public function refusAutomatiquesEnCours(): array
    {
        $ids = DemandeEtudiant::where('etat_dossier', 'refuse')
            ->whereIn('campagne_id', \App\Models\Campagne::where('statut', 'active')->select('id_campagne'))
            ->pluck('id_demande')->all();
        return $this->refusAutomatiques($ids);
    }

    /**
     * Lève les refus automatiques donnés (tous ceux des campagnes actives si la liste est vide).
     * @return array{eligibles:int, echecs:array<string,string>}
     */
    public function rendreEligibles(array $demandeIds = []): array
    {
        $ids = $demandeIds ? $this->refusAutomatiques($demandeIds) : $this->refusAutomatiquesEnCours();
        $eligibles = 0;
        $echecs = [];
        foreach ($ids as $id) {
            try {
                $this->workflow->admin($id, 'eligible', self::MOTIF_LEVEE);
                $eligibles++;
            } catch (ValidationException $e) {
                $echecs[$id] = (string) collect($e->errors())->flatten()->first();
            }
        }
        return ['eligibles' => $eligibles, 'echecs' => $echecs];
    }
}
