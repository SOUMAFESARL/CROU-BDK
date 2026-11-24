<?php

namespace App\Http\Services\Eligibilite;

use App\Models\AdmissionDerogation;
use App\Models\Campagne;
use App\Models\DemandeEtudiant;
use App\Models\DossierTransition;
use App\Models\Etudiant;
use App\Repositories\EtudiantRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
 * - liste élargie : une liste provisoire est limitée, par volet, au nombre de places × le coefficient de la liste élargie
 *   (`ClassementProvisoire::capaciteListe`) ; les demandes recevables classées au-delà deviennent « irrecevables »
 *   (`horsListe`), sans changement d'état du dossier. Les demandes déjà retenues (validées, en attente, confirmées,
 *   attribuées) occupent une ligne de la liste ; les demandes dérogées passent devant et n'en sont jamais écartées.
 * - sinon (aucun critère non conforme mais une information manquante) : ni recevable ni irrecevable, la demande ne figure
 *   que dans « Toutes les demandes ».
 *
 * - « refusée » : demande refusée ou annulée par l'administration pendant la validation (état actuel « Refusé » ou
 *   « Annulé », dernière transition faite par un agent). Elle sort des listes recevables / irrecevables ;
 *   une annulation par l'étudiant ou un refus automatique (barème) n'en fait pas partie.
 * - « validée » : demande recevable dont le dossier est passé « Recevable » (`eligible`), validée par un agent depuis
 *   une liste provisoire (ou par une dérogation approuvée) ; elle sort des demandes recevables pour la « Décision de la
 *   commission » et y reste jusqu'à son approbation (ou la confirmation de l'étudiant).
 * - « en attente » : demande recevable mise en attente par la commission depuis une liste provisoire
 *   (`demande.date_attente_commission`, dossier encore « Soumis » / « En étude » et aucune transition depuis) ; elle sort
 *   des demandes recevables pour la « Décision de la commission », où elle sera validée ou rejetée.
 * - « définitive » : demande validée approuvée en commission (`demande.date_approbation_commission`, postérieure à la
 *   dernière validation du dossier : un dossier rejeté puis revalidé doit être approuvé à nouveau) ; elle passe de la
 *   « Décision de la commission » à la « Liste définitive ».
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

    public const VALIDEE = 'validee';

    public const DEFINITIVE = 'definitive';

    public const EN_ATTENTE = 'en_attente';

    /** États du dossier d'une demande que la commission peut mettre en attente (avant validation ou rejet). */
    public const ETATS_ATTENTE = ['soumis', 'en_etude'];

    /** État du dossier d'une demande validée (`DossierWorkflow`, « Recevable »). */
    public const ETAT_VALIDE = 'eligible';

    /** États du dossier d'une demande refusée par l'administration (`DossierWorkflow`). */
    public const ETATS_REFUS = ['refuse', 'annule'];

    /** États du dossier d'une demande déjà retenue : elle occupe une ligne de la liste élargie. */
    private const ETATS_RETENUS = ['eligible', 'confirme', 'pre_attribue', 'accepte', 'attribue_definitif', 'cloture'];

    public function __construct(private ConformiteDemande $conformite, private ClassementProvisoire $provisoire, private CriteresCampagne $criteres) {}

    /**
     * @param  iterable<Etudiant>  $etudiants  avec `derniereDemande` (et sa campagne) chargée ; toutes les demandes de
     *                                         chaque campagne, sinon la coupure de la liste élargie serait fausse
     * @return array{recevables: string[], rejetees: string[], refusees: string[], validees: string[], en_attente: string[], definitives: string[], hors_liste: string[]} identifiants des étudiants (`hors_liste` : demandes de `rejetees` écartées par la liste élargie)
     */
    public function classer(iterable $etudiants): array
    {
        $etudiants = collect($etudiants);
        $demandes = $etudiants->map(fn (Etudiant $etudiant) => $etudiant->derniereDemande)->filter()->all();
        $refusParAgent = $this->refusParAgent($demandes);
        $derogations = $this->derogationsApprouvees($demandes);
        $approuvees = $this->approbationsCommission($demandes);
        $attentes = $this->misesEnAttente($demandes);
        $resultat = ['recevables' => [], 'rejetees' => [], 'refusees' => [], 'validees' => [], 'en_attente' => [], 'definitives' => [], 'hors_liste' => []];
        $classes = $etudiants->mapWithKeys(fn (Etudiant $etudiant) => [
            (string) $etudiant->getKey() => $this->classeConformite($etudiant, $refusParAgent, $derogations, $approuvees, $attentes),
        ])->all();
        $resultat['hors_liste'] = $this->horsListe($etudiants, $classes, $derogations);
        $horsListe = array_flip($resultat['hors_liste']);
        foreach ($etudiants as $etudiant) {
            $id = (string) $etudiant->getKey();
            $classe = isset($horsListe[$id]) ? self::REJETEE : $classes[$id];
            $liste = match ($classe) {
                self::RECEVABLE => 'recevables',
                self::REJETEE => 'rejetees',
                self::REFUSEE => 'refusees',
                self::VALIDEE => 'validees',
                self::EN_ATTENTE => 'en_attente',
                self::DEFINITIVE => 'definitives',
                default => null,
            };
            if ($liste !== null) {
                $resultat[$liste][] = (string) $etudiant->getKey();
            }
        }

        return $resultat;
    }

    /**
     * Classe de la dernière demande de l'étudiant (coupure de la liste élargie comprise) ; null sans demande.
     */
    public function classe(Etudiant $etudiant): ?string
    {
        $classe = $this->classeConformite($etudiant);

        return $classe === self::RECEVABLE && $this->ecartee($etudiant) ? self::REJETEE : $classe;
    }

    /** Demande conforme à tous les critères, mais classée au-delà de la liste élargie de son volet. */
    public function horsListeElargie(Etudiant $etudiant): bool
    {
        return $this->classeConformite($etudiant) === self::RECEVABLE && $this->ecartee($etudiant);
    }

    /** Position d'une demande recevable par rapport à la coupure : les demandes de sa campagne sont reclassées. */
    private function ecartee(Etudiant $etudiant): bool
    {
        $campagne = $etudiant->derniereDemande?->campagne;
        if (! $campagne) {
            return false;
        }
        $volet = $this->criteres->voletsDemandes([$etudiant->derniereDemande])[(string) $etudiant->getKey()] ?? 'admission';
        if ($this->provisoire->capaciteListe($campagne, $volet) === null) {
            return false;
        }
        $population = app(EtudiantRepository::class)->getPopulationDemandes()
            ->filter(fn (Etudiant $autre) => (string) $autre->derniereDemande?->campagne_id === (string) $campagne->getKey());

        return in_array((string) $etudiant->getKey(), $this->classer($population)['hors_liste'], true);
    }

    /**
     * Places du volet encore libres : places des critères en vigueur moins les demandes déjà retenues (validées,
     * confirmées, attribuées ; les demandes en attente n'occupent pas de place) ; null si aucune place n'est renseignée.
     */
    public function placesLibres(Campagne $campagne, string $volet): ?int
    {
        $places = $this->provisoire->places($campagne, $volet);
        if ($places === null) {
            return null;
        }
        $retenues = DemandeEtudiant::with('campagne')->where('campagne_id', (string) $campagne->getKey())->whereIn('etat_dossier', self::ETATS_RETENUS)->get();

        return max(0, $places - (array_count_values($this->criteres->voletsDemandes($retenues))[$volet] ?? 0));
    }

    /**
     * Demandes recevables classées au-delà de la liste élargie de leur volet, dans l'ordre du classement.
     *
     * @param  array<string, ?string>  $classes  classe (conformité) par étudiant
     * @param  string[]  $derogations  demandes dérogées
     * @return string[] identifiants des étudiants
     */
    private function horsListe(Collection $etudiants, array $classes, array $derogations): array
    {
        $hors = [];
        $recevables = $etudiants->filter(fn (Etudiant $etudiant) => $classes[(string) $etudiant->getKey()] === self::RECEVABLE && $etudiant->derniereDemande?->campagne);
        foreach ($recevables->groupBy(fn (Etudiant $etudiant) => (string) $etudiant->derniereDemande->campagne_id) as $campagneId => $candidats) {
            $campagne = $candidats->first()->derniereDemande->campagne;
            $capacites = collect(array_keys(CriteresCampagne::VOLETS))
                ->mapWithKeys(fn (string $volet) => [$volet => $this->provisoire->capaciteListe($campagne, $volet)])
                ->filter(fn (?int $capacite) => $capacite !== null);
            if ($capacites->isEmpty()) {
                continue;
            }
            $enAttente = $etudiants->filter(fn (Etudiant $etudiant) => $classes[(string) $etudiant->getKey()] === self::EN_ATTENTE
                && (string) $etudiant->derniereDemande?->campagne_id === (string) $campagneId)->map(fn (Etudiant $etudiant) => $etudiant->derniereDemande);
            // Une demande retenue peut avoir quitté la page « Admissions » (confirmée, étudiant logé).
            $retenues = collect(DemandeEtudiant::with('campagne')->where('campagne_id', (string) $campagneId)->whereIn('etat_dossier', self::ETATS_RETENUS)->get()->all())
                ->concat($enAttente);
            $occupees = array_count_values($this->criteres->voletsDemandes($retenues));
            $volets = $this->criteres->voletsDemandes($candidats->map(fn (Etudiant $etudiant) => $etudiant->derniereDemande));
            $listes = $this->provisoire->classer($candidats, $campagne, $volets);
            foreach ($capacites as $volet => $capacite) {
                [$derogees, $autres] = collect($listes[$volet])->map(fn (array $ligne) => $ligne['etudiant'])
                    ->partition(fn (Etudiant $etudiant) => in_array((string) $etudiant->derniereDemande->getKey(), $derogations, true));
                $libres = max(0, $capacite - ($occupees[$volet] ?? 0) - $derogees->count());
                array_push($hors, ...$autres->values()->slice($libres)->map(fn (Etudiant $etudiant) => (string) $etudiant->getKey())->values()->all());
            }
        }

        return $hors;
    }

    /**
     * Classe de la demande selon ses seuls critères (sans la liste élargie).
     *
     * @param  string[]|null  $refusParAgent  demandes refusées par un agent (`refusParAgent`), calculées ici si null
     * @param  string[]|null  $derogations  demandes rendues recevables par dérogation (`derogationsApprouvees`), calculées ici si null
     * @param  string[]|null  $approuvees  demandes approuvées en commission (`approbationsCommission`), calculées ici si null
     * @param  string[]|null  $attentes  demandes mises en attente par la commission (`misesEnAttente`), calculées ici si null
     */
    private function classeConformite(Etudiant $etudiant, ?array $refusParAgent = null, ?array $derogations = null, ?array $approuvees = null, ?array $attentes = null): ?string
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
            $classe = self::RECEVABLE;
        } else {
            $regles = collect($this->conformite->evaluer($etudiant, $demande, true)['regles']);
            $classe = match (true) {
                $regles->contains('etat', 'nok') => self::REJETEE,
                $regles->every(fn (array $regle) => $regle['etat'] === 'ok') => self::RECEVABLE,
                default => self::A_COMPLETER,
            };
        }

        if ($classe !== self::RECEVABLE) {
            return $classe;
        }
        if ($demande->etat_dossier !== self::ETAT_VALIDE) {
            // Demande recevable mise en attente par la commission : « Décision de la commission ».
            $attentes ??= $this->misesEnAttente([$demande]);

            return in_array((string) $demande->getKey(), $attentes, true) ? self::EN_ATTENTE : $classe;
        }
        // Demande recevable validée par l'administration : « Décision de la commission », puis, une fois approuvée
        // en commission, « Liste définitive ».
        $approuvees ??= $this->approbationsCommission([$demande]);

        return in_array((string) $demande->getKey(), $approuvees, true) ? self::DEFINITIVE : self::VALIDEE;
    }

    /**
     * Demandes (identifiants) validées et approuvées en commission : dossier « Recevable » dont l'approbation
     * (`date_approbation_commission`) n'est pas antérieure à la dernière validation (transition vers `eligible`).
     *
     * `$etats` : états du dossier retenus (par défaut « Recevable » ; `ListeDefinitivePubliee` y ajoute « Confirmé »).
     *
     * @param  iterable<\App\Models\DemandeEtudiant>  $demandes
     * @param  list<string>  $etats
     * @return string[]
     */
    public function approbationsCommission(iterable $demandes, array $etats = [self::ETAT_VALIDE]): array
    {
        $dates = collect($demandes)
            ->filter(fn ($demande) => in_array($demande->etat_dossier, $etats, true) && ! empty($demande->date_approbation_commission))
            ->mapWithKeys(fn ($demande) => [(string) $demande->getKey() => Carbon::parse($demande->date_approbation_commission)]);
        if ($dates->isEmpty()) {
            return [];
        }
        $validations = Schema::hasTable('dossier_transitions')
            ? $dates->keys()->chunk(500)
                ->flatMap(fn ($ids) => DB::table('dossier_transitions')->whereIn('demande_id', $ids->all())
                    ->where('nouvel_etat', self::ETAT_VALIDE)->groupBy('demande_id')
                    ->select('demande_id', DB::raw('MAX(created_at) as derniere'))->get())
                ->mapWithKeys(fn ($ligne) => [(string) $ligne->demande_id => $ligne->derniere])
            : collect();

        return $dates->filter(fn (Carbon $date, string $id) => empty($validations[$id]) || $date->gte(Carbon::parse($validations[$id])))
            ->keys()->map(fn ($id) => (string) $id)->values()->all();
    }

    /**
     * Demandes (identifiants) mises en attente par la commission : dossier encore « Soumis » / « En étude » dont la mise
     * en attente (`date_attente_commission`) n'est pas antérieure à la dernière transition du dossier (un dossier rejeté
     * puis réexaminé revient dans la liste provisoire).
     *
     * @param  iterable<\App\Models\DemandeEtudiant>  $demandes
     * @return string[]
     */
    public function misesEnAttente(iterable $demandes): array
    {
        $dates = collect($demandes)
            ->filter(fn ($demande) => in_array($demande->etat_dossier, self::ETATS_ATTENTE, true) && ! empty($demande->date_attente_commission))
            ->mapWithKeys(fn ($demande) => [(string) $demande->getKey() => Carbon::parse($demande->date_attente_commission)]);
        if ($dates->isEmpty()) {
            return [];
        }
        $transitions = Schema::hasTable('dossier_transitions')
            ? $dates->keys()->chunk(500)
                ->flatMap(fn ($ids) => DB::table('dossier_transitions')->whereIn('demande_id', $ids->all())->groupBy('demande_id')
                    ->select('demande_id', DB::raw('MAX(created_at) as derniere'))->get())
                ->mapWithKeys(fn ($ligne) => [(string) $ligne->demande_id => $ligne->derniere])
            : collect();

        return $dates->filter(fn (Carbon $date, string $id) => empty($transitions[$id]) || $date->gte(Carbon::parse($transitions[$id])))
            ->keys()->map(fn ($id) => (string) $id)->values()->all();
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
