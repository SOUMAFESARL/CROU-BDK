<?php

namespace App\Http\Services\Eligibilite;

use App\Models\Campagne;
use App\Models\CampagneRegles;
use App\Models\Chambre;
use App\Models\Cite;
use App\Models\NiveauEtude;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Critères d'une campagne, en trois jeux versionnés séparément (docs/notes/eligibilite-criteres-campagne.md) :
 *
 * 1. le **paramétrage général** (`general`), commun aux deux volets et préalable à leur application : crédits exigés
 *    par filière (et écoles sur concours), rang par filière, rang des clubs et associations, cas sociaux, comité des
 *    résidents, âge maximum par niveau d'études ;
 * 2. les critères d'**admission** (nouveaux étudiants) ;
 * 3. les critères de **réadmission** (anciens résidents).
 *
 * Chaque volet a deux niveaux : critères de **rejet** (un candidat qui n'en remplit pas un n'est pas classé) puis
 * critères de **classement** de la liste provisoire.
 *
 * Cycle, par jeu : modèle CROU par défaut → brouillon (modifications) → « en vigueur » (appliqué).
 * Une seule version en vigueur par campagne et par jeu ; appliquer un brouillon la modifie en place (rien n'est archivé).
 * Les critères d'une campagne active ou terminée sont figés : ils se paramètrent tant que la campagne est planifiée.
 * Ce service ne calcule pas la liste provisoire : il fournit le jeu de règles que le moteur lira.
 */
class CriteresCampagne
{
    /** Jeux de critères d'une campagne et leur complément de nom (« critères généraux », « critères d'admission »). */
    public const JEUX = ['general' => 'généraux', 'admission' => 'd’admission', 'readmission' => 'de réadmission'];

    /** Volets (publics) d'une campagne. */
    public const VOLETS = ['admission' => 'd’admission', 'readmission' => 'de réadmission'];

    /** Niveaux d'études sans âge maximum (libellé ou abréviation, sans accents ni casse) : la Terminale. */
    public const NIVEAUX_SANS_AGE_MAX = ['terminale'];

    /** Identifiants des niveaux sans âge maximum, calculés une fois par requête. */
    private ?array $niveauxSansAgeMax = null;

    /** Catégories de réadmission, dans l'ordre de la cascade (un candidat est retenu dans la première qui le retient). */
    public const CATEGORIES = [
        'C1' => 'Écoles sur concours',
        'C2' => 'Mérite par filière',
        'C3' => 'Clubs et associations',
        'C4' => 'Cas sociaux',
        'C5' => 'Comité des résidents',
        'C6' => 'Places restantes',
    ];

    /** Mérite propre à chaque catégorie (fixé par le compte rendu S/DAL, non modifiable). */
    public const MERITE_CATEGORIES = [
        'C1' => ['pct_valide_desc', 'moyenne_pv_desc'],
        'C2' => ['rang_pv_asc'],
        'C3' => ['rang_liste_asc'],
        'C4' => ['moyenne_pv_desc'],
        'C5' => ['moyenne_pv_desc'],
        'C6' => ['moyenne_pv_desc'],
    ];

    /**
     * Critères de départage, par volet (ordre par défaut ; les critères absents du modèle sont proposés désactivés).
     * Réadmission : âge (le plus jeune d'abord), puis excellence et comportement, facultatifs.
     * Admission : baccalauréat obtenu hors de la ville de référence, puis âge.
     */
    public const DEPARTAGE = [
        'readmission' => ['age_plus_jeune', 'excellence', 'comportement'],
        'admission' => ['hors_ville_bac', 'age_plus_jeune'],
    ];

    /** Départage du modèle CROU, par volet. */
    public const DEPARTAGE_DEFAUT = [
        'readmission' => ['age_plus_jeune'],
        'admission' => ['hors_ville_bac', 'age_plus_jeune'],
    ];

    /** Priorité aux boursiers : `categorie` (oui, devant les non-boursiers) ou `aucune` (non). */
    public const PRIORITE_BOURSIERS = ['categorie', 'aucune'];

    /** Statut de boursier retenu : celui déclaré par l'étudiant suffit. */
    public const SOURCE_BOURSIER = 'declare';

    public const REPORT_PLACES = ['C6_puis_admission', 'C6', 'admission', 'vacant'];

    public const GROUPES_C2 = ['filiere_niveau', 'filiere'];

    public const BASES_C4 = ['places_readmission_cite', 'capacite_cite'];

    /** Mérite des nouveaux étudiants : note du baccalauréat (sur 20) ou nombre de crédits. */
    public const MERITE_ADMISSION = ['moyenne_bac_desc', 'credits_desc'];

    /** Bornes du seuil de rejet en admission selon le mérite retenu. */
    public const SEUIL_MAX_ADMISSION = ['moyenne_bac_desc' => 20, 'credits_desc' => 360];

    /** Note de conduite minimale du modèle CROU en réadmission (sur la note initiale du paramétrage des sanctions, 20 par défaut). */
    public const NOTE_CONDUITE_MIN_DEFAUT = 10;

    /** Modèle CROU de Bondoukou d'un jeu (compte rendu S/DAL, décisions Q1 et Q2). */
    public function modeleParDefaut(string $jeu, ?Campagne $campagne = null): array
    {
        if ($jeu === 'general') {
            return [
                // Pourcentage des crédits de l'année exigé ; une filière peut avoir son propre seuil
                // et être une école sur concours (réadmission d'office au-delà du seuil : catégorie C1).
                'credits' => ['seuil_defaut' => 100, 'filieres' => []],
                'rang_filiere' => ['actif' => true, 'n' => 5, 'groupe' => 'filiere_niveau'],
                'clubs' => ['actif' => true, 'n' => 3],
                'cas_sociaux' => ['actif' => true, 'taux' => 10, 'base' => 'places_readmission_cite'],
                'comite' => ['actif' => true, 'places' => 10],
                // Âge maximum par niveau d'études : [['niveau' => id_niveau_etude, 'age' => 25], …] ; un niveau absent n'a pas de limite.
                'age_max' => [],
            ];
        }

        $recevabilite = [
            'internationaux' => $campagne?->internationnaux === null ? true : (bool) $campagne->internationnaux,
            'sanctions_bloquantes' => ['suspension', 'expulsion'],
        ];

        if ($jeu === 'admission') {
            return [
                'calendrier' => ['date_liste_provisoire' => null, 'delai_completude_jours' => 7],
                'recevabilite' => $recevabilite,
                'admission' => [
                    'merite' => 'moyenne_bac_desc',
                    // Rejet : note du bac (ou crédits) inférieure au seuil ; âge au-delà du maximum de son niveau.
                    'seuil_actif' => true,
                    'seuil' => 10,
                    'age_actif' => true,
                    // Classement : un bac obtenu hors de cette ville est prioritaire.
                    'ville_bac' => 'Bondoukou',
                ],
                'classement' => [
                    'departage' => self::DEPARTAGE_DEFAUT['admission'],
                    'k_liste_elargie' => 2,
                ],
            ];
        }

        return [
            'calendrier' => [
                // Les préalables (loyers, crédits) sont contrôlés à la clôture des dépôts (hypothèse Q9).
                'date_liste_provisoire' => null,
                'delai_completude_jours' => 7,
            ],
            'recevabilite' => $recevabilite,
            // Rejet : sans demande de réadmission, un résident n'est pas classé (règle fixe) ; puis loyers, crédits,
            // conduite et âge. Chacun de ces critères est désactivable.
            'readmission' => [
                'loyers' => ['actif' => true, 'mois_exiges' => 10],
                'credits_actif' => true,
                // Conduite en cité : note de conduite (paramétrage des sanctions) au moins égale à `note_min`, sinon rejet
                // (`NoteConduite::motifRejet`). Une sanction bloquante active (suspension, expulsion) bloque en outre le dossier.
                'conduite' => ['actif' => true, 'note_min' => self::NOTE_CONDUITE_MIN_DEFAUT, 'sanctions' => ['suspension', 'expulsion']],
                'age_actif' => true,
                'report_places' => 'C6_puis_admission',
            ],
            'classement' => [
                'priorite_boursiers' => 'categorie',
                // Le statut de boursier déclaré par l'étudiant suffit (règle fixe, non paramétrable).
                'source_boursier' => self::SOURCE_BOURSIER,
                'departage' => self::DEPARTAGE_DEFAUT['readmission'],
                'k_liste_elargie' => 2,
            ],
        ];
    }

    public function enVigueur(Campagne $campagne, string $jeu): ?CampagneRegles
    {
        return $this->requete($campagne, $jeu)->where('statut', CampagneRegles::EN_VIGUEUR)->first();
    }

    public function brouillon(Campagne $campagne, string $jeu): ?CampagneRegles
    {
        return $this->requete($campagne, $jeu)->where('statut', CampagneRegles::BROUILLON)->first();
    }

    /** Toutes les versions du jeu, la plus récente d'abord. */
    public function versions(Campagne $campagne, string $jeu): Collection
    {
        return $this->requete($campagne, $jeu)->orderByDesc('version')->get();
    }

    /**
     * État des critères pour la liste des campagnes, en une requête pour toute la page :
     * campagne_id => [jeu => ['etat' => a_parametrer|brouillon|en_vigueur, 'version' => ?int, 'brouillon' => bool]].
     */
    public function etats(array $campagneIds): array
    {
        $versions = CampagneRegles::whereIn('campagne_id', $campagneIds)
            ->whereIn('statut', [CampagneRegles::BROUILLON, CampagneRegles::EN_VIGUEUR])
            ->get(['campagne_id', 'volet', 'statut', 'version'])
            ->groupBy(fn (CampagneRegles $regles) => $regles->campagne_id.'|'.$regles->volet);

        return collect($campagneIds)->mapWithKeys(fn ($id) => [$id => collect(array_keys(self::JEUX))->mapWithKeys(function ($jeu) use ($versions, $id) {
            $liste = $versions->get($id.'|'.$jeu, collect());
            $vigueur = $liste->firstWhere('statut', CampagneRegles::EN_VIGUEUR);
            $brouillon = $liste->contains('statut', CampagneRegles::BROUILLON);

            return [$jeu => [
                'etat' => $vigueur ? 'en_vigueur' : ($brouillon ? 'brouillon' : 'a_parametrer'),
                'version' => $vigueur?->version,
                'brouillon' => $brouillon,
            ]];
        })->all()])->all();
    }

    /** Statuts de campagne qui figent les critères, et le libellé affiché. */
    public const STATUTS_FIGES = ['active' => 'en cours', 'terminer' => 'terminée'];

    /** Les critères ne se modifient plus une fois la campagne active ou terminée. */
    public function modifiable(Campagne $campagne): bool
    {
        return $this->verrou($campagne) === null;
    }

    /** « en cours » ou « terminée » si les critères de la campagne sont figés, sinon null. */
    public function verrou(Campagne $campagne): ?string
    {
        return self::STATUTS_FIGES[(string) $campagne->statut] ?? null;
    }

    /** Volets ouverts par la campagne ; une campagne sans public renseigné ouvre les deux. */
    public function volets(Campagne $campagne): array
    {
        $admission = (bool) $campagne->admission;
        $readmission = (bool) $campagne->readmission;

        return ! $admission && ! $readmission
            ? ['admission' => true, 'readmission' => true]
            : ['admission' => $admission, 'readmission' => $readmission];
    }

    /** Le paramétrage général s'applique à toute campagne ; un volet, s'il est ouvert par la campagne. */
    public function voletOuvert(Campagne $campagne, string $jeu): bool
    {
        return $jeu === 'general' || ($this->volets($campagne)[$jeu] ?? false);
    }

    /** Règles en cours d'édition : brouillon, sinon version en vigueur, sinon modèle par défaut (complétées par le modèle). */
    public function reglesCourantes(Campagne $campagne, string $jeu): array
    {
        $source = $this->brouillon($campagne, $jeu) ?? $this->enVigueur($campagne, $jeu);

        return $this->completer($jeu, $source?->parametres ?? [], $campagne);
    }

    /**
     * Cités ouvertes à la campagne et leur capacité réelle (chambres numérotées actives, hors service exclues).
     *
     * @return Collection<int, array{cite_id: string, cite: string, public: ?string, capacite: int}>
     */
    public function cites(Campagne $campagne): Collection
    {
        $ids = DB::table('residence_campagne')->where('campagne_id', $campagne->getKey())
            ->when(Schema::hasColumn('residence_campagne', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
            ->pluck('cite_id')->map(fn ($id) => (string) $id)->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $places = Chambre::query()
            ->whereIn('cite_id', $ids)
            ->whereNotNull('numero_chambre')
            ->where('statut', 'actif')
            ->whereNotIn('etat', ['hors_service', 'fermee'])
            ->get(['cite_id', 'capacite_max'])
            ->groupBy(fn ($chambre) => (string) $chambre->cite_id)
            ->map(fn ($chambres) => (int) $chambres->sum(fn ($c) => max(1, (int) $c->capacite_max)));

        return Cite::with('typeCite')->whereIn('id_cite', $ids)->orderBy('name')->get()
            ->map(fn (Cite $cite) => [
                'cite_id' => (string) $cite->id_cite,
                'cite' => (string) $cite->name,
                'public' => $cite->typeCite?->name,
                'capacite' => (int) ($places[(string) $cite->id_cite] ?? 0),
            ])->values();
    }

    /**
     * Places du volet par cité en cours d'édition (brouillon, sinon en vigueur), alignées sur les cités actuelles de la campagne.
     * `autre_volet` : places réservées à l'autre volet (ses critères courants), pour contrôler la capacité.
     * Le paramétrage général n'a pas de places.
     */
    public function placesCourantes(Campagne $campagne, string $jeu): array
    {
        if ($jeu === 'general') {
            return [];
        }
        $source = $this->brouillon($campagne, $jeu) ?? $this->enVigueur($campagne, $jeu);
        $saisies = collect($source?->places ?? [])->keyBy('cite_id');
        $autres = $this->placesAutreVolet($campagne, $jeu);

        return $this->cites($campagne)->map(function (array $cite) use ($saisies, $autres) {
            $saisie = $saisies->get($cite['cite_id'], []);

            return $cite + ['places' => $saisie['places'] ?? null, 'autre_volet' => $autres[$cite['cite_id']] ?? null];
        })->all();
    }

    /**
     * Enregistre les blocs envoyés dans le brouillon du jeu (créé au besoin). Un bloc absent garde sa valeur actuelle.
     * Les droits par bloc sont contrôlés par le contrôleur ; ici, les règles métier.
     */
    public function enregistrer(Campagne $campagne, string $jeu, ?array $regles, ?array $places, User $admin): CampagneRegles
    {
        $this->verifierModifiable($campagne, $jeu);

        $regles = $regles !== null ? $this->normaliserRegles($jeu, $regles, $campagne) : $this->reglesCourantes($campagne, $jeu);
        $places = $jeu === 'general' ? null
            : ($places !== null ? $this->normaliserPlaces($places, $campagne) : $this->placesEnregistrables($campagne, $jeu));
        $this->verifierPlaces($jeu, $places ?? [], $regles, $campagne);
        $this->verifierCalendrier($regles, $campagne);

        return DB::transaction(function () use ($campagne, $jeu, $regles, $places, $admin) {
            $brouillon = $this->brouillon($campagne, $jeu) ?? new CampagneRegles([
                'campagne_id' => $campagne->getKey(),
                'volet' => $jeu,
                'version' => $this->prochaineVersion($campagne, $jeu),
                'statut' => CampagneRegles::BROUILLON,
                'created_by' => (string) $admin->getKey(),
                'created_by_name' => $admin->name,
            ]);
            $brouillon->fill(['parametres' => $regles, 'places' => $places, 'updated_by_name' => $admin->name])->save();

            return $brouillon;
        });
    }

    /**
     * Met en vigueur le brouillon du jeu (ou, à défaut de toute version, le modèle CROU par défaut).
     * Un volet ne s'applique qu'une fois le paramétrage général en vigueur.
     * S'il existe déjà une version en vigueur, elle est modifiée en place avec le brouillon (aucune version archivée).
     */
    public function appliquer(Campagne $campagne, string $jeu, User $admin): CampagneRegles
    {
        $this->verifierModifiable($campagne, $jeu);

        return DB::transaction(function () use ($campagne, $jeu, $admin) {
            if ($jeu !== 'general' && ! $this->enVigueur($campagne, 'general')) {
                throw ValidationException::withMessages(['criteres' => 'Appliquez d’abord le paramétrage général de la campagne : il précède les critères d’admission et de réadmission.']);
            }
            $vigueur = $this->enVigueur($campagne, $jeu);
            $brouillon = $this->brouillon($campagne, $jeu);

            if ($vigueur && ! $brouillon) {
                throw ValidationException::withMessages(['criteres' => 'Aucune modification à appliquer : les critères en vigueur sont à jour.']);
            }

            $version = $brouillon ?? new CampagneRegles([
                'campagne_id' => $campagne->getKey(),
                'volet' => $jeu,
                'version' => $this->prochaineVersion($campagne, $jeu),
                'parametres' => $this->modeleParDefaut($jeu, $campagne),
                'places' => $jeu === 'general' ? null : $this->placesEnregistrables($campagne, $jeu),
                'created_by' => (string) $admin->getKey(),
                'created_by_name' => $admin->name,
            ]);
            $parametres = $this->completer($jeu, $version->parametres ?? [], $campagne);
            // Les places sont revérifiées au moment de l'application (cités, capacités, places de l'autre volet
            // ou paramétrage général ont pu changer).
            $this->verifierPlaces($jeu, $version->places ?? [], $parametres, $campagne);

            if ($vigueur) {
                // La version en vigueur reprend le contenu du brouillon, qui disparaît.
                $vigueur->fill(['places' => $version->places, 'updated_by_name' => $version->updated_by_name ?? $admin->name]);
                $version->delete();
                $version = $vigueur;
            }
            $version->fill([
                'statut' => CampagneRegles::EN_VIGUEUR,
                'parametres' => $parametres,
                'applique_par' => (string) $admin->getKey(),
                'applique_par_name' => $admin->name,
                'applique_le' => now(),
            ])->save();

            return $version;
        });
    }

    /** Abandonne le brouillon du jeu : les critères en vigueur (ou le modèle par défaut) redeviennent la référence. */
    public function abandonnerBrouillon(Campagne $campagne, string $jeu): void
    {
        $this->verifierNonFigee($campagne);
        $brouillon = $this->brouillon($campagne, $jeu);
        if (! $brouillon) {
            throw ValidationException::withMessages(['criteres' => 'Aucun brouillon à abandonner.']);
        }
        $brouillon->delete();
    }

    /**
     * Complète un jeu de règles enregistré avec les clés du modèle du jeu : les clés inconnues du modèle
     * (autre volet, anciennes versions des critères) sont ignorées, les listes sont reprises en entier.
     */
    public function completer(string $jeu, array $parametres, ?Campagne $campagne = null): array
    {
        $resultat = $this->fusionner($this->modeleParDefaut($jeu, $campagne), $parametres);

        if ($jeu === 'general') {
            $resultat['age_max'] = $this->sansNiveauxExclus($resultat['age_max'] ?? []);

            return $resultat;
        }
        if ($jeu === 'admission' && ! in_array($resultat['admission']['merite'], self::MERITE_ADMISSION, true)) {
            $resultat['admission']['merite'] = 'moyenne_bac_desc';
        }
        if ($jeu === 'readmission') {
            // Priorité aux boursiers : oui (toujours devant les non-boursiers) ou non ; l'ancien « à mérite égal » vaut oui.
            $resultat['classement']['priorite_boursiers'] = $resultat['classement']['priorite_boursiers'] === 'aucune' ? 'aucune' : 'categorie';
            $resultat['classement']['source_boursier'] = self::SOURCE_BOURSIER;
        }
        // Départage : seuls les critères propres au volet ; au moins un reste actif.
        $departage = array_values(array_intersect($resultat['classement']['departage'], self::DEPARTAGE[$jeu]));
        $resultat['classement']['departage'] = $departage ?: self::DEPARTAGE_DEFAUT[$jeu];

        return $resultat;
    }

    /** Vrai si ce niveau d'études n'a pas d'âge maximum (Terminale). */
    public static function niveauSansAgeMax(?string $nom, ?string $abreviation): bool
    {
        $normaliser = fn (?string $texte) => Str::lower(Str::ascii(trim((string) $texte)));

        return in_array($normaliser($nom), self::NIVEAUX_SANS_AGE_MAX, true)
            || in_array($normaliser($abreviation), self::NIVEAUX_SANS_AGE_MAX, true);
    }

    /** Retire de l'âge maximum les niveaux qui n'en ont pas (Terminale), y compris dans les versions déjà enregistrées. */
    private function sansNiveauxExclus(array $ages): array
    {
        if ($ages === []) {
            return [];
        }
        $this->niveauxSansAgeMax ??= Schema::hasTable('niveau_etude')
            ? NiveauEtude::query()->get(['id_niveau_etude', 'name', 'abreviation'])
                ->filter(fn (NiveauEtude $niveau) => self::niveauSansAgeMax($niveau->name, $niveau->abreviation))
                ->map(fn (NiveauEtude $niveau) => (string) $niveau->id_niveau_etude)->values()->all()
            : [];

        return array_values(array_filter($ages, fn ($ligne) => ! in_array((string) ($ligne['niveau'] ?? ''), $this->niveauxSansAgeMax, true)));
    }

    /**
     * Catégories de réadmission en cascade, déduites du paramétrage général : C1 si au moins une filière est une école
     * sur concours, C2 à C5 selon leur activation, C6 toujours.
     */
    public function categories(array $general): array
    {
        $actif = [
            'C1' => collect($general['credits']['filieres'] ?? [])->contains(fn ($f) => (bool) ($f['concours'] ?? false)),
            'C2' => (bool) ($general['rang_filiere']['actif'] ?? false),
            'C3' => (bool) ($general['clubs']['actif'] ?? false),
            'C4' => (bool) ($general['cas_sociaux']['actif'] ?? false),
            'C5' => (bool) ($general['comite']['actif'] ?? false),
            'C6' => true,
        ];

        return collect(self::CATEGORIES)->map(fn ($label, $code) => [
            'actif' => $actif[$code],
            'merite' => self::MERITE_CATEGORIES[$code],
        ])->all();
    }

    /** Places des cas sociaux (C4) d'une cité de réadmission, selon le taux du paramétrage général (arrondi inférieur). */
    public function placesCasSociaux(array $ligne, array $general): ?int
    {
        $c4 = $general['cas_sociaux'] ?? [];
        if (! ($c4['actif'] ?? false)) {
            return null;
        }
        $base = ($c4['base'] ?? 'places_readmission_cite') === 'capacite_cite' ? ($ligne['capacite'] ?? 0) : ($ligne['places'] ?? null);

        return $base === null ? null : (int) floor(((int) $base * (int) ($c4['taux'] ?? 0)) / 100);
    }

    /** Places du comité des résidents (C5), toutes cités confondues ; 0 si la catégorie est désactivée. */
    public function placesComite(array $general): int
    {
        return ($general['comite']['actif'] ?? false) ? (int) ($general['comite']['places'] ?? 0) : 0;
    }

    public static function autreVolet(string $volet): string
    {
        return $volet === 'admission' ? 'readmission' : 'admission';
    }

    /** Modèle complété par les valeurs enregistrées, clé par clé ; une liste (ou un modèle vide) est remplacée en entier. */
    private function fusionner(array $modele, array $saisie): array
    {
        foreach ($modele as $cle => $defaut) {
            if (! array_key_exists($cle, $saisie)) {
                continue;
            }
            $valeur = $saisie[$cle];
            $modele[$cle] = is_array($defaut) && $defaut !== [] && ! array_is_list($defaut) && is_array($valeur)
                ? $this->fusionner($defaut, $valeur)
                : (is_array($defaut) && is_array($valeur) ? array_values($valeur) : $valeur);
        }

        return $modele;
    }

    private function requete(Campagne $campagne, string $jeu)
    {
        return CampagneRegles::where('campagne_id', $campagne->getKey())->where('volet', $jeu);
    }

    /** Places de l'autre volet (brouillon, sinon en vigueur) par cité ; vide si ce volet est fermé. */
    private function placesAutreVolet(Campagne $campagne, string $volet): array
    {
        $autre = self::autreVolet($volet);
        if (! $this->voletOuvert($campagne, $autre)) {
            return [];
        }
        $source = $this->brouillon($campagne, $autre) ?? $this->enVigueur($campagne, $autre);

        return collect($source?->places ?? [])
            ->filter(fn ($ligne) => isset($ligne['cite_id']) && ($ligne['places'] ?? null) !== null)
            ->mapWithKeys(fn ($ligne) => [(string) $ligne['cite_id'] => (int) $ligne['places']])->all();
    }

    /** Places courantes sans la donnée calculée `autre_volet` (format enregistré). */
    private function placesEnregistrables(Campagne $campagne, string $volet): array
    {
        return array_map(fn (array $ligne) => Arr::except($ligne, ['autre_volet']), $this->placesCourantes($campagne, $volet));
    }

    /** Valeurs validées par `CampagneCriteresRequest` → jeu de règles typé (entiers, booléens, listes sans doublon). */
    private function normaliserRegles(string $jeu, array $saisie, Campagne $campagne): array
    {
        $entier = fn ($valeur) => $valeur === null || $valeur === '' ? null : (int) $valeur;
        $date = fn ($valeur) => $valeur ? Carbon::parse($valeur)->toDateString() : null;

        if ($jeu === 'general') {
            return $this->completer($jeu, [
                'credits' => [
                    'seuil_defaut' => (int) $saisie['credits']['seuil_defaut'],
                    'filieres' => collect($saisie['credits']['filieres'] ?? [])
                        ->map(fn ($ligne) => [
                            'filiere_id' => (string) $ligne['filiere_id'],
                            'seuil' => (int) $ligne['seuil'],
                            'concours' => (bool) ($ligne['concours'] ?? false),
                        ])->unique('filiere_id')->values()->all(),
                ],
                'rang_filiere' => [
                    'actif' => (bool) $saisie['rang_filiere']['actif'],
                    'n' => (int) $saisie['rang_filiere']['n'],
                    'groupe' => $saisie['rang_filiere']['groupe'],
                ],
                'clubs' => ['actif' => (bool) $saisie['clubs']['actif'], 'n' => (int) $saisie['clubs']['n']],
                'cas_sociaux' => [
                    'actif' => (bool) $saisie['cas_sociaux']['actif'],
                    'taux' => (int) $saisie['cas_sociaux']['taux'],
                    'base' => $saisie['cas_sociaux']['base'],
                ],
                'comite' => ['actif' => (bool) $saisie['comite']['actif'], 'places' => (int) $saisie['comite']['places']],
                'age_max' => $this->sansNiveauxExclus(collect($saisie['age_max'] ?? [])
                    ->filter(fn ($ligne) => ($ligne['age'] ?? null) !== null && $ligne['age'] !== '')
                    ->map(fn ($ligne) => ['niveau' => (string) $ligne['niveau'], 'age' => (int) $ligne['age']])
                    ->unique('niveau')->values()->all()),
            ], $campagne);
        }

        $commun = [
            'recevabilite' => [
                'internationaux' => (bool) $saisie['recevabilite']['internationaux'],
                'sanctions_bloquantes' => ['suspension', 'expulsion'],
            ],
        ];
        $classement = [
            // Critères de départage actifs, dans l'ordre ; un critère absent est désactivé.
            'departage' => array_values(array_unique($saisie['classement']['departage'])),
            'k_liste_elargie' => (int) $saisie['classement']['k_liste_elargie'],
        ];

        if ($jeu === 'admission') {
            $seuil = $saisie['admission']['seuil'] ?? null;

            return $this->completer($jeu, $commun + [
                'calendrier' => [
                    'date_liste_provisoire' => $date($saisie['calendrier']['date_liste_provisoire'] ?? null),
                    'delai_completude_jours' => (int) $saisie['calendrier']['delai_completude_jours'],
                ],
                'admission' => [
                    'merite' => $saisie['admission']['merite'],
                    'seuil_actif' => (bool) $saisie['admission']['seuil_actif'],
                    'seuil' => $seuil === null || $seuil === '' ? null : round((float) $seuil, 2),
                    'age_actif' => (bool) $saisie['admission']['age_actif'],
                    'ville_bac' => trim((string) $saisie['admission']['ville_bac']),
                ],
                'classement' => $classement,
            ], $campagne);
        }

        return $this->completer($jeu, $commun + [
            'calendrier' => [
                'date_liste_provisoire' => $date($saisie['calendrier']['date_liste_provisoire'] ?? null),
                'delai_completude_jours' => (int) $saisie['calendrier']['delai_completude_jours'],
            ],
            'readmission' => [
                'loyers' => [
                    'actif' => (bool) $saisie['readmission']['loyers']['actif'],
                    'mois_exiges' => (int) $saisie['readmission']['loyers']['mois_exiges'],
                ],
                'credits_actif' => (bool) $saisie['readmission']['credits_actif'],
                'conduite' => [
                    'actif' => (bool) $saisie['readmission']['conduite']['actif'],
                    'note_min' => ($note = $saisie['readmission']['conduite']['note_min'] ?? null) === null || $note === '' ? null : round((float) $note, 2),
                    'sanctions' => ['suspension', 'expulsion'],
                ],
                'age_actif' => (bool) $saisie['readmission']['age_actif'],
                'report_places' => $saisie['readmission']['report_places'],
            ],
            'classement' => $classement + [
                'priorite_boursiers' => $saisie['classement']['priorite_boursiers'],
            ],
        ], $campagne);
    }

    /** Places saisies → une ligne par cité de la campagne (instantané du nom et de la capacité). */
    private function normaliserPlaces(array $saisie, Campagne $campagne): array
    {
        $saisies = collect($saisie)->keyBy(fn ($ligne) => (string) ($ligne['cite_id'] ?? ''));
        $inconnues = $saisies->keys()->diff($this->cites($campagne)->pluck('cite_id'));
        if ($inconnues->isNotEmpty()) {
            throw ValidationException::withMessages(['places' => 'Une des cités saisies n’est pas ouverte à cette campagne : actualisez la page.']);
        }
        $entier = fn ($valeur) => $valeur === null || $valeur === '' ? null : (int) $valeur;

        return $this->cites($campagne)
            ->map(fn (array $cite) => $cite + ['places' => $entier($saisies->get($cite['cite_id'], [])['places'] ?? null)])
            ->all();
    }

    /**
     * Contrôles bloquants E0 (EX-09) : places du volet + places de l'autre volet ≤ capacité de la cité ;
     * en réadmission, cas sociaux (C4, calculés) + comité des résidents (C5) ≤ places de réadmission saisies.
     * Le paramétrage général est contrôlé contre les places de réadmission en cours (comité, cas sociaux).
     */
    private function verifierPlaces(string $jeu, array $places, array $regles, Campagne $campagne): void
    {
        if ($jeu === 'general') {
            if ($this->voletOuvert($campagne, 'readmission') && ($message = $this->depassementReservations($this->placesCourantes($campagne, 'readmission'), $regles))) {
                throw ValidationException::withMessages([($regles['comite']['actif'] ?? false) ? 'regles.comite.places' : 'regles.cas_sociaux.taux' => $message]);
            }

            return;
        }

        $erreurs = [];
        $capacites = $this->cites($campagne)->keyBy('cite_id');
        $autres = $this->placesAutreVolet($campagne, $jeu);
        $libelleAutre = self::VOLETS[self::autreVolet($jeu)];
        foreach (array_values($places) as $index => $ligne) {
            $nom = $ligne['cite'] ?? 'la cité';
            $capacite = (int) ($capacites[$ligne['cite_id'] ?? '']['capacite'] ?? $ligne['capacite'] ?? 0);
            $propres = (int) ($ligne['places'] ?? 0);
            $autre = (int) ($autres[$ligne['cite_id'] ?? ''] ?? 0);
            if ($propres + $autre > $capacite) {
                $erreurs["places.$index.places"] = $autre > 0
                    ? "{$nom} : {$propres} places + {$autre} places {$libelleAutre} pour une capacité de {$capacite} places."
                    : "{$nom} : {$propres} places pour une capacité de {$capacite} places.";
            }
        }
        if ($jeu === 'readmission' && ! $erreurs && ($message = $this->depassementReservations($places, $this->reglesCourantes($campagne, 'general')))) {
            $erreurs['places'] = $message;
        }

        if ($erreurs) {
            throw ValidationException::withMessages($erreurs);
        }
    }

    /** Message si les places réservées (cas sociaux + comité) dépassent les places de réadmission saisies ; null sinon. */
    private function depassementReservations(array $places, array $general): ?string
    {
        $saisies = collect($places)->filter(fn ($ligne) => ($ligne['places'] ?? null) !== null);
        if ($saisies->isEmpty()) {
            return null;
        }
        $total = (int) $saisies->sum('places');
        $casSociaux = (int) $saisies->sum(fn ($ligne) => (int) $this->placesCasSociaux($ligne, $general));
        $comite = $this->placesComite($general);

        return $casSociaux + $comite > $total
            ? "Les places réservées (cas sociaux : {$casSociaux}, comité des résidents : {$comite}) dépassent les {$total} places de réadmission."
            : null;
    }

    /** Date de la liste provisoire : pas avant la clôture des dépôts. */
    private function verifierCalendrier(array $regles, Campagne $campagne): void
    {
        $cloture = $campagne->periode_fin ? Carbon::parse($campagne->periode_fin)->toDateString() : null;
        $liste = $regles['calendrier']['date_liste_provisoire'] ?? null;
        $erreurs = [];

        if ($cloture && $liste && $liste < $cloture) {
            $erreurs['regles.calendrier.date_liste_provisoire'] = 'La liste provisoire ne peut pas être générée avant la clôture des dépôts ('.Carbon::parse($cloture)->format('d/m/Y').').';
        }

        if ($erreurs) {
            throw ValidationException::withMessages($erreurs);
        }
    }

    private function verifierModifiable(Campagne $campagne, string $jeu): void
    {
        $this->verifierNonFigee($campagne);
        if (! $this->voletOuvert($campagne, $jeu)) {
            throw ValidationException::withMessages(['criteres' => 'Cette campagne n’est pas ouverte '.($jeu === 'admission' ? 'à l’admission' : 'à la réadmission').' : ces critères ne s’appliquent pas.']);
        }
    }

    private function verifierNonFigee(Campagne $campagne): void
    {
        if ($verrou = $this->verrou($campagne)) {
            throw ValidationException::withMessages(['criteres' => "Cette campagne est {$verrou} : ses critères ne peuvent plus être modifiés."]);
        }
    }

    private function prochaineVersion(Campagne $campagne, string $jeu): int
    {
        return (int) $this->requete($campagne, $jeu)->max('version') + 1;
    }
}
