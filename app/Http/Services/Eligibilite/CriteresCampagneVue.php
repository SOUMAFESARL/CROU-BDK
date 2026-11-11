<?php

namespace App\Http\Services\Eligibilite;

use App\Http\Services\CampagneService;
use App\Models\Campagne;
use App\Models\CampagneRegles;
use App\Models\Filiere;
use App\Models\User;
use App\Services\AdminAccess;
use Illuminate\Support\Carbon;

/**
 * Données de la page « Critères de la campagne » du paramétrage hébergement : un niveau par volet ouvert
 * (admission, réadmission), chacun avec sa définition (assistant prérempli), sa version en vigueur et son historique.
 * Chaque bloc (règles, places par cité) n'est envoyé qu'avec le droit de le consulter.
 */
class CriteresCampagneVue
{
    public const REGLES_VIEW = 'hebergement.eligibilite.regles.view';

    public const REGLES_CONFIGURE = 'hebergement.eligibilite.regles.configure';

    public const QUOTAS_VIEW = 'hebergement.eligibilite.quotas.view';

    public const QUOTAS_CONFIGURE = 'hebergement.eligibilite.quotas.configure';

    /** Page du paramétrage des critères (les deux niveaux sur la même page). */
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

    /** Niveaux de la page : un par volet ouvert par la campagne (admission et/ou réadmission). */
    public function niveaux(Campagne $campagne, User $admin): array
    {
        return collect($this->criteres->volets($campagne))
            ->filter()
            ->map(fn (bool $ouvert, string $volet) => $this->page($campagne, $volet, $admin))
            ->all();
    }

    /**
     * Niveau d'un volet : assistant prérempli (brouillon, version en vigueur ou modèle CROU),
     * version en vigueur détaillée et historique des versions.
     */
    public function page(Campagne $campagne, string $volet, User $admin): array
    {
        $droits = $this->droits($admin);
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
            'places' => $droits['voirPlaces'] ? $this->placesAffichees($places, $this->criteres->reglesCourantes($campagne, $volet), $volet) : [],
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
                'places' => $droits['voirPlaces'] ? $this->placesAffichees($places, $modele, $volet) : null,
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
            'filieres' => $volet === 'readmission' ? $this->filieres() : [],
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
        ];
    }

    private function version(CampagneRegles $regles, array $droits, Campagne $campagne, string $volet): array
    {
        $parametres = $this->criteres->completer($volet, $regles->parametres ?? [], $campagne);

        return [
            'version' => $regles->version,
            'statut' => $regles->statut,
            'auteur' => $regles->updated_by_name ?? $regles->created_by_name,
            'modifie_le' => $regles->updated_at?->format('Y-m-d H:i'),
            'applique_par' => $regles->applique_par_name,
            'applique_le' => $regles->applique_le?->format('Y-m-d H:i'),
            'motif' => $regles->motif,
            'regles' => $droits['voirRegles'] ? $parametres : null,
            'places' => $droits['voirPlaces'] ? $this->placesAffichees($regles->places ?? [], $parametres, $volet) : null,
        ];
    }

    /** Places par cité ; en réadmission, avec le quota des cas sociaux (C4) calculé et le solde (C2 / C6). */
    private function placesAffichees(array $places, array $regles, string $volet): array
    {
        return collect($places)->map(function (array $ligne) use ($regles, $volet) {
            if ($volet !== 'readmission') {
                return $ligne;
            }
            $ligne['quotas'] ??= ['C1' => null, 'C3' => null, 'C5' => null];

            return $ligne + [
                'cas_sociaux' => $this->criteres->placesCasSociaux($ligne, $regles),
                'solde' => ($ligne['places'] ?? null) === null ? null : max(0, (int) $ligne['places'] - $this->criteres->sousQuotas($ligne, $regles)),
            ];
        })->values()->all();
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
}
