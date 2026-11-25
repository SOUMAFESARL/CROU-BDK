<?php

namespace App\Http\Services\Eligibilite;

use App\Http\Services\DossierWorkflow;
use App\Models\Campagne;
use App\Models\DemandeEtudiant;
use App\Models\Etudiant;
use App\Repositories\EtudiantRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Résultats d'admission : à la publication des listes définitives, le résultat de chaque demande des volets publiés est
 * figé (`resultats_admission`) et les demandes validées publiées passent dans « Admis à loger » (dossier `confirme`).
 * Les étudiants consultent ensuite leur résultat sur `/resultats` (numéro de table au bac ou matricule).
 *
 * - `admis` : demande de la liste définitive publiée (onglet « Demandes validées »), ou de sa file d'attente admise
 *   ensuite à loger par un agent (`admettre`) ;
 * - `recevable_non_admis` : demande recevable non retenue (file d'attente, demande recevable non validée) ;
 * - `non_recevable` : demande irrecevable, refusée, ou dont la recevabilité n'a pas pu être établie.
 *
 * Une demande validée pas encore approuvée ou publiée n'a pas de résultat.
 */
class ResultatsAdmission
{
    public const TABLE = 'resultats_admission';

    /** Campagnes visibles sur la page publique : une ligne par campagne cochée. */
    public const TABLE_VISIBILITE = 'resultats_visibilite';

    public const ADMIS = 'admis';

    public const RECEVABLE_NON_ADMIS = 'recevable_non_admis';

    public const NON_RECEVABLE = 'non_recevable';

    /** États du dossier d'un étudiant admis qui a déjà sa chambre. */
    private const ETATS_LOGES = ['attribue_definitif', 'cloture'];

    public function __construct(
        private RecevabiliteDemandes $recevabilite,
        private CriteresCampagne $criteres,
        private DossierWorkflow $workflow,
    ) {}

    public function disponible(): bool
    {
        return Schema::hasTable(self::TABLE);
    }

    /**
     * Fige les résultats des volets publiés et admet à loger les demandes validées publiées. À appeler une fois la
     * publication enregistrée (`publication_liste_definitive`) ; peut être rejoué.
     *
     * @param  list<string>  $volets  volets publiés
     * @param  Collection<int, Etudiant>  $population  avec `derniereDemande.campagne` chargée
     * @param  array  $classement  `RecevabiliteDemandes::classer($population)`
     * @return array{admis: int, bloques: list<string>} étudiants passés dans « Admis à loger », et motifs de ceux qui n'ont pas pu y passer
     */
    public function publier(Campagne $campagne, array $volets, Collection $population, array $classement): array
    {
        $campagneId = (string) $campagne->getKey();
        $etudiants = $population->filter(fn (Etudiant $etudiant) => (string) $etudiant->derniereDemande?->campagne_id === $campagneId);
        $voletsEtudiants = $this->criteres->voletsDemandes($etudiants->map(fn (Etudiant $etudiant) => $etudiant->derniereDemande));
        $etudiants = $etudiants->filter(fn (Etudiant $etudiant) => in_array($voletsEtudiants[(string) $etudiant->getKey()] ?? null, $volets, true));
        $admis = $this->admis($campagneId, $etudiants, $voletsEtudiants, $classement['definitives']);

        $classes = [];
        foreach (['recevables', 'rejetees', 'refusees', 'validees', 'en_attente', 'definitives'] as $liste) {
            foreach ($classement[$liste] as $id) {
                $classes[$id] = $liste;
            }
        }
        $date = now();
        $lignes = [];
        foreach ($etudiants as $etudiant) {
            $id = (string) $etudiant->getKey();
            $resultat = isset($admis[$id]) ? self::ADMIS : $this->resultatHorsListe($etudiant->derniereDemande, $classes[$id] ?? null);
            if ($resultat === null) {
                continue;
            }
            $lignes[] = [
                'id' => (string) Str::uuid(),
                'campagne_id' => $campagneId,
                'demande_id' => (string) $etudiant->derniereDemande->getKey(),
                'etudiant_id' => $id,
                'volet' => $voletsEtudiants[$id],
                'resultat' => $resultat,
                'publie_at' => $date,
                'created_at' => $date,
                'updated_at' => $date,
            ];
        }
        foreach (array_chunk($lignes, 200) as $lot) {
            DB::table(self::TABLE)->upsert($lot, ['demande_id'], ['volet', 'resultat', 'publie_at', 'updated_at']);
        }

        $bilan = ['admis' => 0, 'bloques' => []];
        foreach ($admis as $etudiant) {
            if ($etudiant->derniereDemande->etat_dossier !== RecevabiliteDemandes::ETAT_VALIDE) {
                continue;
            }
            try {
                $this->workflow->transition((string) $etudiant->derniereDemande->getKey(), 'confirme', 'systeme', null,
                    'Publication de la liste définitive : admis à loger.');
                $bilan['admis']++;
            } catch (ValidationException $e) {
                $bilan['bloques'][] = trim((string) $etudiant->name).' : '.collect($e->errors())->flatten()->first();
            }
        }

        return $bilan;
    }

    /**
     * Identifiants des demandes que `admettre` accepte, parmi celles données : approuvées en commission (dossier
     * « Recevable »), campagne non supprimée, dernière demande de l'étudiant.
     *
     * @param  iterable<DemandeEtudiant>  $demandes
     * @return list<string>
     */
    public function admissibles(iterable $demandes): array
    {
        $demandes = collect($demandes)->filter(fn (DemandeEtudiant $demande) => $demande->campagne && $demande->etudiant);
        $approuvees = $this->recevabilite->approbationsCommission($demandes);

        return $demandes->filter(fn (DemandeEtudiant $demande) => in_array((string) $demande->getKey(), $approuvees, true)
            && (string) $demande->etudiant->derniereDemande?->getKey() === (string) $demande->getKey())
            ->map(fn (DemandeEtudiant $demande) => (string) $demande->getKey())->values()->all();
    }

    /**
     * Fait passer une demande de la file d'attente publiée dans « Admis à loger » : le dossier devient `confirme` (paiement,
     * puis attribution d'une chambre) et le résultat consulté par l'étudiant devient « admis ». La demande garde sa place
     * dans la file d'attente des listes définitives déjà publiées.
     */
    public function admettre(string $resultatId): Etudiant
    {
        abort_unless($this->disponible(), 404);

        return DB::transaction(function () use ($resultatId) {
            $resultat = DB::table(self::TABLE)->where('id', $resultatId)->lockForUpdate()->first();
            abort_unless($resultat !== null, 404);
            $refuser = fn (string $message) => throw ValidationException::withMessages(['admission' => $message]);
            if ($resultat->resultat !== self::RECEVABLE_NON_ADMIS) {
                $refuser('Cette demande n’est plus dans la file d’attente : actualisez la page.');
            }
            $demande = DemandeEtudiant::with(['campagne', 'etudiant.derniereDemande'])->find($resultat->demande_id);
            if (! $demande || $this->admissibles([$demande]) === []) {
                $refuser('Cette demande ne peut pas être admise à loger : elle doit être recevable, approuvée en commission et être la dernière demande de l’étudiant.');
            }
            $this->workflow->admettreFileAttente((string) $demande->getKey());
            DB::table(self::TABLE)->where('id', $resultatId)->update(['resultat' => self::ADMIS, 'updated_at' => now()]);

            return $demande->etudiant;
        }, 3);
    }

    /**
     * Applique les publications déjà enregistrées (commande `admissions:appliquer-publications`, après la mise à jour) :
     * seules les demandes approuvées avant leur publication sont admises.
     *
     * @return array{admis: int, bloques: list<string>}
     */
    public function appliquerPublications(): array
    {
        $bilan = ['admis' => 0, 'bloques' => []];
        foreach (DB::table('publication_liste_definitive')->get(['campagne_id', 'volet'])->groupBy('campagne_id') as $campagneId => $publications) {
            $campagne = Campagne::find($campagneId);
            if (! $campagne) {
                continue;
            }
            $population = app(EtudiantRepository::class)->getPopulationDemandes((string) $campagneId)
                ->filter(fn (Etudiant $etudiant) => (string) $etudiant->derniereDemande?->campagne_id === (string) $campagneId);
            $volets = $publications->pluck('volet')->unique()->values()->all();
            $resultat = DB::transaction(fn () => $this->publier($campagne, $volets, $population, $this->recevabilite->classer($population)));
            $bilan['admis'] += $resultat['admis'];
            array_push($bilan['bloques'], ...$resultat['bloques']);
        }

        return $bilan;
    }

    /**
     * Campagnes dont les résultats sont visibles (cochées dans le menu « Résultats d'admission ») ; aucune par défaut.
     *
     * @return list<string>
     */
    public function campagnesVisibles(): array
    {
        return Schema::hasTable(self::TABLE_VISIBILITE)
            ? DB::table(self::TABLE_VISIBILITE)->pluck('campagne_id')->map(fn ($id) => (string) $id)->all() : [];
    }

    /** @param  list<string>  $campagnes  campagnes visibles, à la place des précédentes ; aucune : page masquée */
    public function definirCampagnesVisibles(array $campagnes, ?string $auteur): void
    {
        if (! Schema::hasTable(self::TABLE_VISIBILITE)) {
            throw ValidationException::withMessages(['visible' => 'La visibilité n’est pas encore disponible : la base de données doit être mise à jour.']);
        }
        DB::transaction(function () use ($campagnes, $auteur) {
            DB::table(self::TABLE_VISIBILITE)->whereNotIn('campagne_id', $campagnes)->delete();
            DB::table(self::TABLE_VISIBILITE)->insertOrIgnore(array_map(fn (string $id) => [
                'campagne_id' => $id, 'modifie_par' => $auteur, 'created_at' => now(), 'updated_at' => now(),
            ], $campagnes));
        });
    }

    /**
     * Campagnes dont les étudiants peuvent consulter les résultats (publiés et visibles), la dernière publiée d'abord.
     *
     * @return Collection<int, Campagne>
     */
    public function campagnesConsultables(): Collection
    {
        if (! $this->disponible()) {
            return collect();
        }
        $ids = DB::table(self::TABLE)->whereIn('campagne_id', $this->campagnesVisibles())
            ->groupBy('campagne_id')->orderByRaw('max(publie_at) desc')->pluck('campagne_id');
        $campagnes = Campagne::whereIn('id_campagne', $ids)->get()->keyBy(fn (Campagne $campagne) => (string) $campagne->getKey());

        return $ids->map(fn ($id) => $campagnes[(string) $id] ?? null)->filter()->values();
    }

    /** Admission remise en cause depuis la publication (refus, annulation, réexamen, expiration). */
    public function admissionRemiseEnCause(string $resultat, ?string $etatDossier): bool
    {
        return $resultat === self::ADMIS
            && ! in_array($etatDossier, [RecevabiliteDemandes::ETAT_VALIDE, ...RecevabiliteDemandes::ETATS_ADMIS], true);
    }

    /**
     * Résultat de la dernière demande de l'étudiant désigné par son numéro de table au bac ou son matricule (espaces,
     * casse et ponctuation indifférents).
     *
     * @return array{type: string, nom: string, campagne: string, volet: string, titre: string, message: string}
     */
    public function consulter(string $numero): array
    {
        $consultables = $this->campagnesConsultables()->map(fn (Campagne $campagne) => (string) $campagne->getKey())->all();
        if ($consultables === []) {
            $this->invalide('Les résultats ne sont pas consultables pour le moment.');
        }
        $demande = $this->demandeDe($numero, $consultables);
        $resultat = $this->disponible() ? DB::table(self::TABLE)->where('demande_id', (string) $demande->getKey())->first() : null;
        if (! $resultat || ! in_array((string) $resultat->campagne_id, $consultables, true)) {
            $this->invalide('Le résultat de votre demande n’est pas encore publié. Revenez après la publication de la liste définitive.');
        }
        if ($this->admissionRemiseEnCause($resultat->resultat, $demande->etat_dossier)) {
            $this->invalide('Votre dossier a changé de situation depuis la publication des résultats : adressez-vous au service Logement du CROU.');
        }

        [$titre, $message] = match ($resultat->resultat) {
            self::ADMIS => ['Vous êtes admis(e)', in_array($demande->etat_dossier, self::ETATS_LOGES, true)
                ? 'Une chambre vous a été attribuée. Adressez-vous au service Logement du CROU pour votre entrée.'
                : 'Votre demande figure sur la liste définitive. Rendez-vous à la comptabilité du CROU pour effectuer votre paiement : vous aurez accès à votre chambre après ce paiement.'],
            self::RECEVABLE_NON_ADMIS => ['Demande recevable, non admise', 'Votre demande est recevable, mais vous n’êtes pas admis(e) pour une chambre : le nombre de places est limité.'],
            default => ['Demande non recevable', 'Votre demande n’est pas recevable : elle ne remplit pas les conditions d’admission de la campagne.'],
        };

        return [
            'type' => $resultat->resultat,
            'nom' => trim((string) $demande->etudiant?->name),
            'campagne' => collect([$demande->campagne?->libelle, $demande->campagne?->annee_campagne])->filter()->implode(' · '),
            'volet' => $resultat->volet === 'readmission' ? 'Réadmission' : 'Admission',
            'titre' => $titre,
            'message' => $message,
        ];
    }

    /**
     * Demandes de la liste définitive publiée (« Demandes validées » approuvées avant la publication de leur volet).
     *
     * @param  array<string, string>  $volets  volet par identifiant d'étudiant
     * @param  string[]  $definitives
     * @return array<string, Etudiant> par identifiant d'étudiant
     */
    private function admis(string $campagneId, Collection $etudiants, array $volets, array $definitives): array
    {
        $publications = DB::table('publication_liste_definitive')->where('campagne_id', $campagneId)
            ->where('liste', ListeDefinitivePubliee::LISTE)->pluck('date_publication', 'volet');

        return $etudiants->whereIn('id_etudiant', $definitives)
            ->filter(function (Etudiant $etudiant) use ($publications, $volets) {
                $demande = $etudiant->derniereDemande;
                $publiee = $publications[$volets[(string) $etudiant->getKey()]] ?? null;

                return $publiee && $demande->liste_approbation_commission !== 'attente'
                    && Carbon::parse($publiee)->gte(Carbon::parse($demande->date_approbation_commission));
            })
            ->mapWithKeys(fn (Etudiant $etudiant) => [(string) $etudiant->getKey() => $etudiant])->all();
    }

    /** Résultat d'une demande qui ne figure pas sur la liste définitive publiée ; null : pas de résultat. */
    private function resultatHorsListe(DemandeEtudiant $demande, ?string $classe): ?string
    {
        if ($classe === 'refusees' || $demande->etat_dossier === 'refuse') {
            return self::NON_RECEVABLE;
        }
        // Demande non déposée, retirée par l'étudiant ou expirée.
        if (in_array($demande->etat_dossier, ['brouillon', 'annule', 'expire'], true)) {
            return null;
        }

        return match ($classe) {
            'recevables', 'en_attente' => self::RECEVABLE_NON_ADMIS,
            // File d'attente : admise depuis par un agent (`admettre`), une nouvelle publication garde son admission.
            'definitives' => match (true) {
                $demande->liste_approbation_commission !== 'attente' => null,
                in_array($demande->etat_dossier, RecevabiliteDemandes::ETATS_ADMIS, true) => self::ADMIS,
                default => self::RECEVABLE_NON_ADMIS,
            },
            'validees' => null,
            default => self::NON_RECEVABLE,
        };
    }

    /**
     * Dernière demande déposée par l'étudiant désigné par le numéro.
     *
     * @param  list<string>  $consultables  campagnes dont les résultats sont consultables
     */
    private function demandeDe(string $numero, array $consultables): DemandeEtudiant
    {
        $cle = Str::lower(str_replace([' ', '-', '/', '.'], '', Str::ascii(Str::squish($numero))));
        if ($cle === '') {
            $this->invalide('Saisissez votre numéro de table au bac ou votre matricule.');
        }
        $colonne = fn (string $nom) => "LOWER(REPLACE(REPLACE(REPLACE(REPLACE({$nom}, ' ', ''), '-', ''), '/', ''), '.', ''))";
        $deposees = fn () => DemandeEtudiant::query()->where('etat_dossier', '!=', 'brouillon')->whereHas('campagne');
        $etudiants = $deposees()
            ->where(fn ($q) => $q->whereRaw($colonne('num_table_bac').' = ?', [$cle])
                ->orWhereHas('etudiant', fn ($e) => $e->whereRaw($colonne('matricule').' = ?', [$cle])))
            ->pluck('etudiant_id')->unique();
        $demandes = $deposees()->with(['campagne', 'etudiant'])->whereIn('etudiant_id', $etudiants->all())
            ->orderByDesc('created_at')->orderByDesc('id_demande')->get()->unique('etudiant_id')->values();
        // Même numéro de table pour deux étudiants (sessions différentes du bac) : celui d'une campagne consultable.
        if ($demandes->count() > 1) {
            $demandes = $demandes->filter(fn (DemandeEtudiant $demande) => in_array((string) $demande->campagne_id, $consultables, true))->values();
        }
        if ($demandes->isEmpty()) {
            $this->invalide('Aucune demande ne correspond à ce numéro. Saisissez le numéro de table au bac ou le matricule indiqué dans votre demande.');
        }
        if ($demandes->count() > 1) {
            $this->invalide('Plusieurs demandes correspondent à ce numéro : adressez-vous au service Logement du CROU.');
        }

        return $demandes->first();
    }

    private function invalide(string $message): never
    {
        throw ValidationException::withMessages(['numero' => $message]);
    }
}
