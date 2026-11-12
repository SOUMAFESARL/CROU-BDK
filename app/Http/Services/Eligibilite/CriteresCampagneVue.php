<?php

namespace App\Http\Services\Eligibilite;

use App\Http\Services\CampagneService;
use App\Models\Campagne;
use App\Models\CampagneRegles;
use App\Models\Filiere;
use App\Models\NiveauEtude;
use App\Models\User;
use App\Services\AdminAccess;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * Données de la page « Critères de la campagne » du paramétrage hébergement : le paramétrage général, puis un niveau
 * par volet ouvert (admission, réadmission), chacun avec sa définition (assistant prérempli), sa version en vigueur
 * et son historique. Chaque bloc (règles, places par cité) n'est envoyé qu'avec le droit de le consulter.
 */
class CriteresCampagneVue
{
    public const REGLES_VIEW = 'hebergement.eligibilite.regles.view';

    public const REGLES_CONFIGURE = 'hebergement.eligibilite.regles.configure';

    public const QUOTAS_VIEW = 'hebergement.eligibilite.quotas.view';

    public const QUOTAS_CONFIGURE = 'hebergement.eligibilite.quotas.configure';

    /** Page du paramétrage des critères (paramétrage général et volets sur la même page). */
    public const PAGE = 'admin.hebergement.parametrage-herb.criteres-campagne.index';

    public function __construct(private CriteresCampagne $criteres, private AdminAccess $access) {}

    /** Droits de l'administrateur sur les deux blocs. */
    public function droits(User $admin): array
    {
        $configurerRegles = $admin->hasPermission(self::REGLES_CONFIGURE);
        $configurerPlaces = $admin->hasPermission(self::QUOTAS_CONFIGURE);

        return [
            'voirRegles' => $configurerRegles || $admin->hasPermission(self::REGLES_VIEW),
            'configurerRegles' => $configurerRegles,
            'voirPlaces' => $configurerPlaces || $admin->hasPermission(self::QUOTAS_VIEW),
            'configurerPlaces' => $configurerPlaces,
        ];
    }

    /** L'administrateur voit au moins un bloc des critères. */
    public function visible(User $admin): bool
    {
        $droits = $this->droits($admin);

        return $droits['voirRegles'] || $droits['voirPlaces'];
    }

    /** Lien vers la page des critères de la campagne, éventuellement ouverte sur un niveau (null sans accès). */
    public function lienPage(User $admin, Campagne $campagne, ?string $volet = null): ?string
    {
        return $this->access->allows($admin, self::PAGE)
            ? route(self::PAGE, array_filter(['campagne_id' => $campagne->getKey(), 'volet' => $volet])) : null;
    }

    /**
     * Niveaux de la page : le paramétrage général (avec le droit de consulter les règles), puis un niveau par volet
     * ouvert par la campagne (admission et/ou réadmission).
     */
    public function niveaux(Campagne $campagne, User $admin): array
    {
        $general = $this->droits($admin)['voirRegles'] ? ['general' => $this->page($campagne, 'general', $admin)] : [];

        return $general + collect($this->criteres->volets($campagne))
            ->filter()
            ->map(fn (bool $ouvert, string $volet) => $this->page($campagne, $volet, $admin))
            ->all();
    }

    /**
     * Niveau d'un jeu (general, admission, readmission) : assistant prérempli (brouillon, version en vigueur
     * ou modèle CROU), version en vigueur détaillée et historique des versions. Un volet reçoit aussi le paramétrage
     * général qu'il applique (crédits, âges, catégories).
     */
    public function page(Campagne $campagne, string $volet, User $admin): array
    {
        $droits = $this->droits($admin);
        if ($volet === 'general') {
            // Le paramétrage général n'a pas de places par cité.
            $droits['voirPlaces'] = false;
            $droits['configurerPlaces'] = false;
        }
        $vigueur = $this->criteres->enVigueur($campagne, $volet);
        $brouillon = $this->criteres->brouillon($campagne, $volet);
        $modele = $this->criteres->modeleParDefaut($volet, $campagne);
        $places = $this->criteres->placesCourantes($campagne, $volet);
        $modifiable = $this->criteres->modifiable($campagne) && $this->criteres->voletOuvert($campagne, $volet);
        $action = fn (string $nom, bool $condition) => $condition && $this->access->allows($admin, $nom)
            ? route($nom, ['campagne' => $campagne->getKey(), 'volet' => $volet]) : null;

        return [
            'volet' => $volet,
            'campagne' => $this->campagne($campagne),
            'droits' => $droits,
            // Saisie : sans droit de consulter les règles, le modèle sert seulement à initialiser l'assistant (étapes masquées).
            'regles' => $droits['voirRegles'] ? $this->criteres->reglesCourantes($campagne, $volet) : $modele,
            'places' => $droits['voirPlaces'] ? $this->placesAffichees($places, $volet, $campagne) : [],
            'source' => $brouillon ? 'brouillon' : ($vigueur ? 'en_vigueur' : 'modele'),
            'etat' => [
                'etat' => $vigueur ? 'en_vigueur' : ($brouillon ? 'brouillon' : 'a_parametrer'),
                'version' => $vigueur?->version,
                'brouillon' => $brouillon !== null,
            ],
            // Consultation : version en vigueur, ou modèle proposé tant que rien n'est appliqué.
            'enVigueur' => $vigueur ? $this->version($vigueur, $droits, $campagne, $volet) : null,
            'modele' => ! $vigueur ? [
                'regles' => $droits['voirRegles'] ? $modele : null,
                'places' => $droits['voirPlaces'] ? $this->placesAffichees($places, $volet, $campagne) : null,
            ] : null,
            'versions' => $this->criteres->versions($campagne, $volet)->map(fn (CampagneRegles $regles) => [
                'id' => $regles->id,
                'version' => $regles->version,
                'statut' => $regles->statut,
                'auteur' => $regles->updated_by_name ?? $regles->created_by_name,
                'modifie_le' => $regles->updated_at?->format('Y-m-d H:i'),
                'applique_par' => $regles->applique_par_name,
                'applique_le' => $regles->applique_le?->format('Y-m-d H:i'),
                'motif' => $regles->motif,
            ])->values()->all(),
            'general' => $volet !== 'general' && $droits['voirRegles'] ? $this->general($campagne) : null,
            'filieres' => $volet === 'admission' ? [] : $this->filieres(),
            'niveauxEtude' => $this->niveauxEtude(),
            'urls' => [
                'submit' => $modifiable && ($droits['configurerRegles'] || $droits['configurerPlaces'])
                    ? $action('admin.hebergement.eligibilite.criteres.update', true) : null,
                'appliquer' => $action('admin.hebergement.eligibilite.criteres.appliquer', $modifiable && (! $vigueur || $brouillon)),
                'abandonner' => $action('admin.hebergement.eligibilite.criteres.abandonner', $this->criteres->modifiable($campagne) && $brouillon !== null),
            ],
        ];
    }

    private function campagne(Campagne $campagne): array
    {
        $date = fn ($valeur) => $valeur ? Carbon::parse($valeur)->toDateString() : null;

        return [
            'id' => (string) $campagne->getKey(),
            'libelle' => $campagne->libelle,
            'annee' => $campagne->annee_campagne,
            'statut' => $campagne->statut,
            'type' => CampagneService::typeDe($campagne),
            'periode_debut' => $date($campagne->periode_debut),
            'periode_fin' => $date($campagne->periode_fin),
            'date_echeance_demande' => $date($campagne->date_echeance_demande),
            'volets' => $this->criteres->volets($campagne),
            'modifiable' => $this->criteres->modifiable($campagne),
            // « en cours » ou « terminée » : critères figés.
            'verrou' => $this->criteres->verrou($campagne),
        ];
    }

    private function version(CampagneRegles $regles, array $droits, Campagne $campagne, string $volet): array
    {
        return [
            'version' => $regles->version,
            'statut' => $regles->statut,
            'auteur' => $regles->updated_by_name ?? $regles->created_by_name,
            'modifie_le' => $regles->updated_at?->format('Y-m-d H:i'),
            'applique_par' => $regles->applique_par_name,
            'applique_le' => $regles->applique_le?->format('Y-m-d H:i'),
            'motif' => $regles->motif,
            'regles' => $droits['voirRegles'] ? $this->criteres->completer($volet, $regles->parametres ?? [], $campagne) : null,
            'places' => $droits['voirPlaces'] ? $this->placesAffichees($regles->places ?? [], $volet, $campagne) : null,
        ];
    }

    /**
     * Paramétrage général appliqué par les volets : version en vigueur, sinon brouillon ou modèle (signalé par `etat`),
     * et catégories de réadmission qui en découlent.
     */
    private function general(Campagne $campagne): array
    {
        $vigueur = $this->criteres->enVigueur($campagne, 'general');
        $regles = $vigueur
            ? $this->criteres->completer('general', $vigueur->parametres ?? [], $campagne)
            : $this->criteres->reglesCourantes($campagne, 'general');

        return [
            'etat' => $vigueur ? 'en_vigueur' : ($this->criteres->brouillon($campagne, 'general') ? 'brouillon' : 'a_parametrer'),
            'regles' => $regles,
            'categories' => $this->criteres->categories($regles),
        ];
    }

    /** Places par cité ; en réadmission, avec les places des cas sociaux (C4) calculées selon le paramétrage général. */
    private function placesAffichees(array $places, string $volet, Campagne $campagne): array
    {
        if ($volet !== 'readmission') {
            return array_values($places);
        }
        $general = $this->criteres->reglesCourantes($campagne, 'general');

        return collect($places)->map(fn (array $ligne) => Arr::except($ligne, ['quotas']) + [
            'cas_sociaux' => $this->criteres->placesCasSociaux($ligne, $general),
        ])->values()->all();
    }

    private function filieres(): array
    {
        return Filiere::query()->orderBy('name')->get(['id_filiere', 'name', 'code_filiere'])
            ->map(fn (Filiere $filiere) => [
                'value' => (string) $filiere->id_filiere,
                'label' => $filiere->name ?: $filiere->code_filiere,
                'description' => $filiere->code_filiere,
            ])->values()->all();
    }

    /** Niveaux d'études (identifiant, libellé), dans l'ordre du paramétrage, pour l'âge maximum par niveau (hors Terminale). */
    private function niveauxEtude(): array
    {
        return NiveauEtude::query()->ordonnes()->get(['id_niveau_etude', 'name', 'abreviation'])
            ->reject(fn (NiveauEtude $niveau) => CriteresCampagne::niveauSansAgeMax($niveau->name, $niveau->abreviation))
            ->map(fn (NiveauEtude $niveau) => [
                'value' => (string) $niveau->id_niveau_etude,
                // Libellé complet (« Licence 1 ») plutôt que l'abréviation (« L1 »).
                'label' => $niveau->name ?: ($niveau->abreviation ?: (string) $niveau->id_niveau_etude),
                'description' => $niveau->abreviation,
            ])->values()->all();
    }
}
