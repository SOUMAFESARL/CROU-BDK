<?php

namespace App\Http\Services\Eligibilite;

use App\Models\AdmissionDerogation;
use App\Models\DossierTransition;
use App\Models\Etudiant;
use Illuminate\Support\Facades\Schema;

/**
 * Classement des demandes de la page « Admissions » selon la conformité de la dernière demande de chaque étudiant
 * aux critères de sa campagne (`ConformiteDemande`, même calcul que le bloc « Éligibilité » de la fiche du dossier) :
 *
 * - « recevable » : tous les critères sont conformes (« Tous les critères de recevabilité sont conformes : la demande
 *   est recevable. ») ;
 * - « irrecevable » : au moins un critère n'est pas conforme. Une demande irrecevable peut être corrigée (« Modifier les
 *   informations » : elle est reclassée selon les nouvelles valeurs) ou rendue recevable par une dérogation approuvée
 *   (`AdmissionDerogationService`) : elle figure alors parmi les recevables ;
 * - sinon (aucun critère non conforme mais une information manquante) : ni recevable ni irrecevable, la demande ne figure
 *   que dans « Toutes les demandes ».
 *
 * - « refusée » : demande refusée ou annulée par l'administration pendant la validation (état actuel « Refusé » ou
 *   « Annulé », dernière transition faite par un agent). Elle sort des listes recevables / irrecevables ;
 *   une annulation par l'étudiant ou un refus automatique (barème) n'en fait pas partie.
 *
 * Le classement est le même pour tous les agents : la situation disciplinaire (note de conduite, sanction bloquante)
 * est toujours prise en compte, même si l'agent ne peut pas la consulter.
 * Il est indicatif : il ne change pas l'état du dossier (`DossierWorkflow`).
 */
class RecevabiliteDemandes
{
    public const RECEVABLE = 'recevable';

    public const REJETEE = 'rejetee';

    public const A_COMPLETER = 'a_completer';

    public const REFUSEE = 'refusee';

    /** États du dossier d'une demande refusée par l'administration (`DossierWorkflow`). */
    public const ETATS_REFUS = ['refuse', 'annule'];

    public function __construct(private ConformiteDemande $conformite) {}

    /**
     * @param  iterable<Etudiant>  $etudiants  avec `derniereDemande` (et sa campagne) chargée
     * @return array{recevables: string[], rejetees: string[], refusees: string[]} identifiants des étudiants
     */
    public function classer(iterable $etudiants): array
    {
        $etudiants = collect($etudiants);
        $demandes = $etudiants->map(fn (Etudiant $etudiant) => $etudiant->derniereDemande)->filter()->all();
        $refusParAgent = $this->refusParAgent($demandes);
        $derogations = $this->derogationsApprouvees($demandes);
        $resultat = ['recevables' => [], 'rejetees' => [], 'refusees' => []];
        foreach ($etudiants as $etudiant) {
            $classe = $this->classe($etudiant, $refusParAgent, $derogations);
            $liste = match ($classe) {
                self::RECEVABLE => 'recevables',
                self::REJETEE => 'rejetees',
                self::REFUSEE => 'refusees',
                default => null,
            };
            if ($liste !== null) {
                $resultat[$liste][] = (string) $etudiant->getKey();
            }
        }

        return $resultat;
    }

    /**
     * Classe de la dernière demande de l'étudiant ; null sans demande.
     *
     * @param  string[]|null  $refusParAgent  demandes refusées par un agent (`refusParAgent`), calculées ici si null
     * @param  string[]|null  $derogations  demandes rendues recevables par dérogation (`derogationsApprouvees`), calculées ici si null
     */
    public function classe(Etudiant $etudiant, ?array $refusParAgent = null, ?array $derogations = null): ?string
    {
        $demande = $etudiant->derniereDemande;
        if ($demande === null) {
            return null;
        }
        $refusParAgent ??= $this->refusParAgent([$demande]);
        if (in_array((string) $demande->getKey(), $refusParAgent, true)) {
            return self::REFUSEE;
        }
        // Dérogation approuvée : recevable quels que soient les critères non conformes.
        $derogations ??= $this->derogationsApprouvees([$demande]);
        if (in_array((string) $demande->getKey(), $derogations, true)) {
            return self::RECEVABLE;
        }
        $regles = collect($this->conformite->evaluer($etudiant, $demande, true)['regles']);

        return match (true) {
            $regles->contains('etat', 'nok') => self::REJETEE,
            $regles->every(fn (array $regle) => $regle['etat'] === 'ok') => self::RECEVABLE,
            default => self::A_COMPLETER,
        };
    }

    /**
     * Demandes (identifiants) rendues recevables par une dérogation approuvée.
     *
     * @param  iterable<\App\Models\DemandeEtudiant>  $demandes
     * @return string[]
     */
    public function derogationsApprouvees(iterable $demandes): array
    {
        $ids = collect($demandes)->map(fn ($demande) => (string) $demande->getKey())->values();
        if ($ids->isEmpty() || ! Schema::hasTable('admission_derogations')) {
            return [];
        }

        return $ids->chunk(500)
            ->flatMap(fn ($lot) => AdmissionDerogation::query()->whereIn('demande_id', $lot->all())
                ->where('status', 'approved')->pluck('demande_id'))
            ->map(fn ($id) => (string) $id)->unique()->values()->all();
    }

    /**
     * Demandes (identifiants) refusées ou annulées par l'administration : état actuel « Refusé » / « Annulé »
     * atteint par une transition faite par un agent (la plus récente du dossier).
     *
     * @param  iterable<\App\Models\DemandeEtudiant>  $demandes
     * @return string[]
     */
    private function refusParAgent(iterable $demandes): array
    {
        $etats = collect($demandes)->filter(fn ($demande) => in_array($demande->etat_dossier, self::ETATS_REFUS, true))
            ->mapWithKeys(fn ($demande) => [(string) $demande->getKey() => $demande->etat_dossier]);
        if ($etats->isEmpty() || ! Schema::hasTable('dossier_transitions')) {
            return [];
        }

        return $etats->keys()->chunk(500)
            ->flatMap(fn ($ids) => DossierTransition::query()->whereIn('demande_id', $ids->all())
                ->orderBy('id')->get(['demande_id', 'nouvel_etat', 'acteur_type']))
            ->groupBy(fn (DossierTransition $transition) => (string) $transition->demande_id)
            ->map(fn ($transitions) => $transitions->last())
            ->filter(fn (DossierTransition $derniere, string $id) => $derniere->acteur_type === 'admin' && $derniere->nouvel_etat === $etats[$id])
            ->keys()->map(fn ($id) => (string) $id)->values()->all();
    }
}
