<?php

namespace App\Http\Services\Eligibilite;

use App\Models\Campagne;
use App\Models\CampagneRegles;
use App\Models\Chambre;
use App\Models\Cite;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Critères d'une campagne, définis en une fois pour chaque volet : un jeu de critères d'admission (nouveaux étudiants)
 * et un jeu de critères de réadmission (anciens résidents). Chaque jeu couvre l'entonnoir de son volet
 * (docs/notes/entonnoir-eligibilite-provisoire.pdf) : places par cité, recevabilité, conditions, catégories et classement.
 * Il n'y a plus de barème de score séparé.
 *
 * Cycle, par volet : modèle CROU par défaut → brouillon (modifications) → « en vigueur » (appliqué) → archivé.
 * Une seule version en vigueur par campagne et par volet ; la remplacer exige un motif. Une campagne terminée est figée.
 * Ce service ne calcule pas la liste provisoire : il fournit le jeu de règles que le moteur lira.
 */
class CriteresCampagne
{
    /** Volets et leur complément de nom (« places d'admission », « critères de réadmission »). */
    public const VOLETS = ['admission' => 'd’admission', 'readmission' => 'de réadmission'];

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

    /** Sous-quotas de réadmission saisis en nombre de places par cité (C2 : N par groupe ; C4 : taux ; C6 : solde). */
    public const QUOTAS_PLACES = ['C1', 'C3', 'C5'];

    /**
     * Critères de départage à mérite égal, par volet. Réadmission : âge, excellence, comportement (compte rendu S/DAL).
     * Admission : éloignement du lieu du bac, puis âge ; le comportement et le dossier antérieur ne s'appliquent pas.
     */
    public const DEPARTAGE = [
        'readmission' => ['age_plus_jeune', 'excellence', 'comportement'],
        'admission' => ['eloignement', 'age_plus_jeune'],
    ];

    public const PRIORITE_BOURSIERS = ['categorie', 'departage', 'aucune'];

    public const SOURCE_BOURSIER = ['justifie', 'declare'];

    public const REPORT_PLACES = ['C6_puis_admission', 'C6', 'admission', 'vacant'];

    public const GROUPES_C2 = ['filiere_niveau', 'filiere'];

    public const BASES_C4 = ['places_readmission_cite', 'capacite_cite'];

    public const MERITE_ADMISSION = ['moyenne_bac_desc', 'rang_orientation_asc'];

    public const HANDICAP_VISIBLE = ['commission', 'aucune'];

    /** Modèle CROU de Bondoukou du volet, décisions Q1 (le plus jeune d'abord) et Q2 (boursiers dans chaque catégorie). */
    public function modeleParDefaut(string $volet, ?Campagne $campagne = null): array
    {
        $recevabilite = [
            'internationaux' => $campagne?->internationnaux === null ? true : (bool) $campagne->internationnaux,
            'sanctions_bloquantes' => ['suspension', 'expulsion'],
        ];
        $classement = [
            'priorite_boursiers' => 'categorie',
            'source_boursier' => 'justifie',
            'departage' => self::DEPARTAGE[$volet],
            'k_liste_elargie' => 2,
        ];

        if ($volet === 'admission') {
            return [
                'calendrier' => ['date_liste_provisoire' => null, 'delai_completude_jours' => 7],
                'recevabilite' => $recevabilite,
                'admission' => [
                    'merite' => 'moyenne_bac_desc',
                    'eloignement' => ['tranches_km' => [100, 300], 'hors_pays' => 'T4'],
                    'handicap_visible' => 'commission',
                    'age_max_L1' => null,
                ],
                'classement' => $classement,
            ];
        }

        return [
            'calendrier' => [
                // null = date de clôture des dépôts (hypothèse Q9).
                'date_controle_prealables' => null,
                'date_liste_provisoire' => null,
                'delai_completude_jours' => 7,
            ],
            'recevabilite' => $recevabilite,
            'readmission' => [
                // R-10 / R-11 désactivables : sans eux, l'année validée n'est pas une condition pour postuler.
                'ue_actif' => true,
                'seuil_ue' => 100,
                'concours_actif' => true,
                'seuil_concours' => 80,
                'filieres_concours' => [],
                'loyers' => ['actif' => true, 'mois_exiges' => 10, 'tolerance_fcfa' => 0],
                'categories' => [
                    'C1' => ['actif' => true],
                    'C2' => ['actif' => true, 'n' => 5, 'groupe' => 'filiere_niveau'],
                    'C3' => ['actif' => true, 'n' => 3],
                    'C4' => ['actif' => true, 'taux' => 10, 'base' => 'places_readmission_cite'],
                    'C5' => ['actif' => true, 'prealables' => true],
                    'C6' => ['actif' => true],
                ],
                'report_places' => 'C6_puis_admission',
                'age_max' => null,
            ],
            'classement' => $classement,
        ];
    }

    public function enVigueur(Campagne $campagne, string $volet): ?CampagneRegles
    {
        return $this->requete($campagne, $volet)->where('statut', CampagneRegles::EN_VIGUEUR)->first();
    }

    public function brouillon(Campagne $campagne, string $volet): ?CampagneRegles
    {
        return $this->requete($campagne, $volet)->where('statut', CampagneRegles::BROUILLON)->first();
    }

    /** Toutes les versions du volet, la plus récente d'abord. */
    public function versions(Campagne $campagne, string $volet): Collection
    {
        return $this->requete($campagne, $volet)->orderByDesc('version')->get();
    }

    /**
     * État des critères pour la liste des campagnes, en une requête pour toute la page :
     * campagne_id => [volet => ['etat' => a_parametrer|brouillon|en_vigueur, 'version' => ?int, 'brouillon' => bool]].
     */
    public function etats(array $campagneIds): array
    {
        $versions = CampagneRegles::whereIn('campagne_id', $campagneIds)
            ->whereIn('statut', [CampagneRegles::BROUILLON, CampagneRegles::EN_VIGUEUR])
            ->get(['campagne_id', 'volet', 'statut', 'version'])
            ->groupBy(fn (CampagneRegles $regles) => $regles->campagne_id.'|'.$regles->volet);

        return collect($campagneIds)->mapWithKeys(fn ($id) => [$id => collect(array_keys(self::VOLETS))->mapWithKeys(function ($volet) use ($versions, $id) {
            $liste = $versions->get($id.'|'.$volet, collect());
            $vigueur = $liste->firstWhere('statut', CampagneRegles::EN_VIGUEUR);
            $brouillon = $liste->contains('statut', CampagneRegles::BROUILLON);

            return [$volet => [
                'etat' => $vigueur ? 'en_vigueur' : ($brouillon ? 'brouillon' : 'a_parametrer'),
                'version' => $vigueur?->version,
                'brouillon' => $brouillon,
            ]];
        })->all()])->all();
    }

    /** Une campagne terminée ne peut plus changer de critères. */
    public function modifiable(Campagne $campagne): bool
    {
        return $campagne->statut !== 'terminer';
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

    public function voletOuvert(Campagne $campagne, string $volet): bool
    {
        return $this->volets($campagne)[$volet] ?? false;
    }

    /** Règles en cours d'édition : brouillon, sinon version en vigueur, sinon modèle par défaut (complétées par le modèle). */
    public function reglesCourantes(Campagne $campagne, string $volet): array
    {
        $source = $this->brouillon($campagne, $volet) ?? $this->enVigueur($campagne, $volet);

        return $this->completer($volet, $source?->parametres ?? [], $campagne);
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
     */
    public function placesCourantes(Campagne $campagne, string $volet): array
    {
        $source = $this->brouillon($campagne, $volet) ?? $this->enVigueur($campagne, $volet);
        $saisies = collect($source?->places ?? [])->keyBy('cite_id');
        $autres = $this->placesAutreVolet($campagne, $volet);

        return $this->cites($campagne)->map(function (array $cite) use ($saisies, $autres, $volet) {
            $saisie = $saisies->get($cite['cite_id'], []);

            return $cite + ['places' => $saisie['places'] ?? null]
                + ($volet === 'readmission' ? ['quotas' => $this->quotas($saisie['quotas'] ?? [])] : [])
                + ['autre_volet' => $autres[$cite['cite_id']] ?? null];
        })->all();
    }

    /**
     * Enregistre les blocs envoyés dans le brouillon du volet (créé au besoin). Un bloc absent garde sa valeur actuelle.
     * Les droits par bloc sont contrôlés par le contrôleur ; ici, les règles métier.
     */
    public function enregistrer(Campagne $campagne, string $volet, ?array $regles, ?array $places, User $admin): CampagneRegles
    {
        $this->verifierModifiable($campagne, $volet);

        $regles = $regles !== null ? $this->normaliserRegles($volet, $regles, $campagne) : $this->reglesCourantes($campagne, $volet);
        $places = $places !== null ? $this->normaliserPlaces($volet, $places, $campagne) : $this->placesEnregistrables($campagne, $volet);
        $this->verifierPlaces($volet, $places, $regles, $campagne);
        $this->verifierCalendrier($regles, $campagne);

        return DB::transaction(function () use ($campagne, $volet, $regles, $places, $admin) {
            $brouillon = $this->brouillon($campagne, $volet) ?? new CampagneRegles([
                'campagne_id' => $campagne->getKey(),
                'volet' => $volet,
                'version' => $this->prochaineVersion($campagne, $volet),
                'statut' => CampagneRegles::BROUILLON,
                'created_by' => (string) $admin->getKey(),
                'created_by_name' => $admin->name,
            ]);
            $brouillon->fill(['parametres' => $regles, 'places' => $places, 'updated_by_name' => $admin->name])->save();

            return $brouillon;
        });
    }

    /**
     * Met en vigueur le brouillon du volet (ou, à défaut de toute version, le modèle CROU par défaut).
     * Remplacer une version en vigueur exige un motif ; l'ancienne version est archivée.
     */
    public function appliquer(Campagne $campagne, string $volet, ?string $motif, User $admin): CampagneRegles
    {
        $this->verifierModifiable($campagne, $volet);
        $motif = trim((string) $motif);

        return DB::transaction(function () use ($campagne, $volet, $motif, $admin) {
            $vigueur = $this->enVigueur($campagne, $volet);
            $brouillon = $this->brouillon($campagne, $volet);

            if ($vigueur && ! $brouillon) {
                throw ValidationException::withMessages(['criteres' => 'Aucune modification à appliquer : les critères en vigueur sont à jour.']);
            }
            if ($vigueur && mb_strlen($motif) < 5) {
                throw ValidationException::withMessages(['motif' => 'Indiquez le motif du remplacement des critères en vigueur (5 caractères au minimum).']);
            }

            $version = $brouillon ?? new CampagneRegles([
                'campagne_id' => $campagne->getKey(),
                'volet' => $volet,
                'version' => $this->prochaineVersion($campagne, $volet),
                'parametres' => $this->modeleParDefaut($volet, $campagne),
                'places' => $this->placesEnregistrables($campagne, $volet),
                'created_by' => (string) $admin->getKey(),
                'created_by_name' => $admin->name,
            ]);
            $parametres = $this->completer($volet, $version->parametres ?? [], $campagne);
            // Les places sont revérifiées au moment de l'application (cités, capacités ou places de l'autre volet ont pu changer).
            $this->verifierPlaces($volet, $version->places ?? [], $parametres, $campagne);

            $vigueur?->update(['statut' => CampagneRegles::ARCHIVEE]);
            $version->fill([
                'statut' => CampagneRegles::EN_VIGUEUR,
                'parametres' => $parametres,
                'motif' => $motif !== '' ? $motif : null,
                'applique_par' => (string) $admin->getKey(),
                'applique_par_name' => $admin->name,
                'applique_le' => now(),
            ])->save();

            return $version;
        });
    }

    /** Abandonne le brouillon du volet : les critères en vigueur (ou le modèle par défaut) redeviennent la référence. */
    public function abandonnerBrouillon(Campagne $campagne, string $volet): void
    {
        if (! $this->modifiable($campagne)) {
            throw ValidationException::withMessages(['criteres' => 'Cette campagne est terminée : ses critères ne peuvent plus être modifiés.']);
        }
        $brouillon = $this->brouillon($campagne, $volet);
        if (! $brouillon) {
            throw ValidationException::withMessages(['criteres' => 'Aucun brouillon à abandonner.']);
        }
        $brouillon->delete();
    }

    /** Complète un jeu de règles enregistré avec les clés du modèle du volet ; les clés propres à l'autre volet sont ignorées. */
    public function completer(string $volet, array $parametres, ?Campagne $campagne = null): array
    {
        $modele = $this->modeleParDefaut($volet, $campagne);
        $resultat = array_replace_recursive($modele, array_intersect_key($parametres, $modele));
        $resultat['calendrier'] = array_intersect_key($resultat['calendrier'], $modele['calendrier']);
        // Listes : remplacées en entier, jamais fusionnées index par index.
        foreach (['readmission.filieres_concours', 'classement.departage', 'admission.eloignement.tranches_km', 'recevabilite.sanctions_bloquantes'] as $cle) {
            if (Arr::has($modele, $cle) && Arr::has($parametres, $cle)) {
                Arr::set($resultat, $cle, array_values((array) Arr::get($parametres, $cle)));
            }
        }
        // Départage : seuls les critères propres au volet ; au moins un reste actif.
        $departage = array_values(array_intersect($resultat['classement']['departage'], self::DEPARTAGE[$volet]));
        $resultat['classement']['departage'] = $departage ?: self::DEPARTAGE[$volet];
        if ($volet === 'readmission') {
            foreach (self::MERITE_CATEGORIES as $code => $merite) {
                $resultat['readmission']['categories'][$code]['merite'] = $merite;
            }
        }

        return $resultat;
    }

    /** Places du cas social (C4) d'une cité de réadmission, calculées par le taux du jeu de règles (arrondi inférieur). */
    public function placesCasSociaux(array $ligne, array $regles): ?int
    {
        $c4 = $regles['readmission']['categories']['C4'] ?? [];
        if (! ($c4['actif'] ?? false)) {
            return null;
        }
        $base = ($c4['base'] ?? 'places_readmission_cite') === 'capacite_cite' ? ($ligne['capacite'] ?? 0) : ($ligne['places'] ?? null);

        return $base === null ? null : (int) floor(((int) $base * (int) ($c4['taux'] ?? 0)) / 100);
    }

    /** Somme des sous-quotas de réadmission d'une cité (C1, C3, C5 saisis des catégories actives + C4 calculé). */
    public function sousQuotas(array $ligne, array $regles): int
    {
        return collect(self::QUOTAS_PLACES)
            ->filter(fn ($code) => $regles['readmission']['categories'][$code]['actif'] ?? false)
            ->sum(fn ($code) => (int) ($ligne['quotas'][$code] ?? 0))
            + (int) $this->placesCasSociaux($ligne, $regles);
    }

    public static function autreVolet(string $volet): string
    {
        return $volet === 'admission' ? 'readmission' : 'admission';
    }

    private function requete(Campagne $campagne, string $volet)
    {
        return CampagneRegles::where('campagne_id', $campagne->getKey())->where('volet', $volet);
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

    private function quotas(array $saisie): array
    {
        $entier = fn ($valeur) => $valeur === null || $valeur === '' ? null : (int) $valeur;

        return collect(self::QUOTAS_PLACES)->mapWithKeys(fn ($code) => [$code => $entier($saisie[$code] ?? null)])->all();
    }

    /** Valeurs validées par `CampagneCriteresRequest` → jeu de règles typé du volet (entiers, booléens, listes sans doublon). */
    private function normaliserRegles(string $volet, array $saisie, Campagne $campagne): array
    {
        $entier = fn ($valeur) => $valeur === null || $valeur === '' ? null : (int) $valeur;
        $date = fn ($valeur) => $valeur ? Carbon::parse($valeur)->toDateString() : null;
        $commun = [
            'recevabilite' => [
                'internationaux' => (bool) $saisie['recevabilite']['internationaux'],
                'sanctions_bloquantes' => ['suspension', 'expulsion'],
            ],
            'classement' => [
                'priorite_boursiers' => $saisie['classement']['priorite_boursiers'],
                'source_boursier' => $saisie['classement']['source_boursier'],
                // Critères de départage actifs, dans l'ordre ; un critère absent est désactivé.
                'departage' => array_values(array_unique($saisie['classement']['departage'])),
                'k_liste_elargie' => (int) $saisie['classement']['k_liste_elargie'],
            ],
        ];

        if ($volet === 'admission') {
            return $this->completer($volet, $commun + [
                'calendrier' => [
                    'date_liste_provisoire' => $date($saisie['calendrier']['date_liste_provisoire'] ?? null),
                    'delai_completude_jours' => (int) $saisie['calendrier']['delai_completude_jours'],
                ],
                'admission' => [
                    'merite' => $saisie['admission']['merite'],
                    'eloignement' => [
                        'tranches_km' => array_map('intval', array_values($saisie['admission']['eloignement']['tranches_km'])),
                        'hors_pays' => 'T4',
                    ],
                    'handicap_visible' => $saisie['admission']['handicap_visible'],
                    'age_max_L1' => $entier($saisie['admission']['age_max_L1'] ?? null),
                ],
            ], $campagne);
        }

        $categories = [];
        foreach (array_keys(self::CATEGORIES) as $code) {
            $source = $saisie['readmission']['categories'][$code] ?? [];
            $categories[$code] = ['actif' => (bool) ($source['actif'] ?? false)] + match ($code) {
                'C2' => ['n' => (int) $source['n'], 'groupe' => $source['groupe']],
                'C3' => ['n' => (int) $source['n']],
                'C4' => ['taux' => (int) $source['taux'], 'base' => $source['base']],
                'C5' => ['prealables' => (bool) ($source['prealables'] ?? true)],
                default => [],
            };
        }

        return $this->completer($volet, $commun + [
            'calendrier' => [
                'date_controle_prealables' => $date($saisie['calendrier']['date_controle_prealables'] ?? null),
                'date_liste_provisoire' => $date($saisie['calendrier']['date_liste_provisoire'] ?? null),
                'delai_completude_jours' => (int) $saisie['calendrier']['delai_completude_jours'],
            ],
            'readmission' => [
                'ue_actif' => (bool) ($saisie['readmission']['ue_actif'] ?? true),
                'seuil_ue' => (int) $saisie['readmission']['seuil_ue'],
                'concours_actif' => (bool) ($saisie['readmission']['concours_actif'] ?? true),
                'seuil_concours' => (int) $saisie['readmission']['seuil_concours'],
                'filieres_concours' => array_values(array_unique(array_map('strval', $saisie['readmission']['filieres_concours'] ?? []))),
                'loyers' => [
                    'actif' => (bool) $saisie['readmission']['loyers']['actif'],
                    'mois_exiges' => (int) $saisie['readmission']['loyers']['mois_exiges'],
                    'tolerance_fcfa' => (int) $saisie['readmission']['loyers']['tolerance_fcfa'],
                ],
                'categories' => $categories,
                'report_places' => $saisie['readmission']['report_places'],
                'age_max' => $entier($saisie['readmission']['age_max'] ?? null),
            ],
        ], $campagne);
    }

    /** Places saisies → une ligne par cité de la campagne (instantané du nom et de la capacité). */
    private function normaliserPlaces(string $volet, array $saisie, Campagne $campagne): array
    {
        $saisies = collect($saisie)->keyBy(fn ($ligne) => (string) ($ligne['cite_id'] ?? ''));
        $inconnues = $saisies->keys()->diff($this->cites($campagne)->pluck('cite_id'));
        if ($inconnues->isNotEmpty()) {
            throw ValidationException::withMessages(['places' => 'Une des cités saisies n’est pas ouverte à cette campagne : actualisez la page.']);
        }
        $entier = fn ($valeur) => $valeur === null || $valeur === '' ? null : (int) $valeur;

        return $this->cites($campagne)->map(function (array $cite) use ($saisies, $volet, $entier) {
            $ligne = $saisies->get($cite['cite_id'], []);

            return $cite + ['places' => $entier($ligne['places'] ?? null)]
                + ($volet === 'readmission' ? ['quotas' => $this->quotas($ligne['quotas'] ?? [])] : []);
        })->all();
    }

    /**
     * Contrôles bloquants E0 (EX-09) : places du volet + places de l'autre volet ≤ capacité de la cité ;
     * en réadmission, sous-quotas C1, C3, C4 (calculé) et C5 des catégories actives ≤ places de réadmission.
     */
    private function verifierPlaces(string $volet, array $places, array $regles, Campagne $campagne): void
    {
        $erreurs = [];
        $capacites = $this->cites($campagne)->keyBy('cite_id');
        $autres = $this->placesAutreVolet($campagne, $volet);
        $libelleAutre = self::VOLETS[self::autreVolet($volet)];
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

            if ($volet !== 'readmission' || ($ligne['places'] ?? null) === null) {
                continue;
            }
            $sousQuotas = $this->sousQuotas($ligne, $regles);
            if ($sousQuotas > $propres) {
                $erreurs["places.$index.quotas"] = "{$nom} : les sous-quotas (C1, C3, C4, C5 : {$sousQuotas} places) dépassent les {$propres} places de réadmission.";
            }
        }

        if ($erreurs) {
            throw ValidationException::withMessages($erreurs);
        }
    }

    /** Date de contrôle des préalables (réadmission) et date de la liste provisoire : après la clôture des dépôts, dans l'ordre. */
    private function verifierCalendrier(array $regles, Campagne $campagne): void
    {
        $cloture = $campagne->periode_fin ? Carbon::parse($campagne->periode_fin)->toDateString() : null;
        $controle = $regles['calendrier']['date_controle_prealables'] ?? null;
        $liste = $regles['calendrier']['date_liste_provisoire'] ?? null;
        $erreurs = [];

        if ($cloture && $controle && $controle < $cloture) {
            $erreurs['regles.calendrier.date_controle_prealables'] = 'La date de contrôle des préalables doit suivre la clôture des dépôts ('.Carbon::parse($cloture)->format('d/m/Y').').';
        }
        if ($cloture && $liste && $liste < $cloture) {
            $erreurs['regles.calendrier.date_liste_provisoire'] = 'La liste provisoire ne peut pas être générée avant la clôture des dépôts ('.Carbon::parse($cloture)->format('d/m/Y').').';
        } elseif ($liste && $controle && $liste < $controle) {
            $erreurs['regles.calendrier.date_liste_provisoire'] = 'La liste provisoire doit être générée après le contrôle des préalables.';
        }

        if ($erreurs) {
            throw ValidationException::withMessages($erreurs);
        }
    }

    private function verifierModifiable(Campagne $campagne, string $volet): void
    {
        if (! $this->modifiable($campagne)) {
            throw ValidationException::withMessages(['criteres' => 'Cette campagne est terminée : ses critères ne peuvent plus être modifiés.']);
        }
        if (! $this->voletOuvert($campagne, $volet)) {
            throw ValidationException::withMessages(['criteres' => 'Cette campagne n’est pas ouverte '.($volet === 'admission' ? 'à l’admission' : 'à la réadmission').' : ces critères ne s’appliquent pas.']);
        }
    }

    private function prochaineVersion(Campagne $campagne, string $volet): int
    {
        return (int) $this->requete($campagne, $volet)->max('version') + 1;
    }
}
