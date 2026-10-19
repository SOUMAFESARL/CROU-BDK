<?php

namespace App\Services;

use App\Models\Campagne;
use App\Models\Chambre;
use App\Models\Cite;
use App\Models\DemandeEtudiant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Données des pages React du « Paramétrage du module hébergement » (accueil, quotas, bâtiments, règles).
 * Les trois pages de campagne affichaient des données inventées : elles lisent désormais les campagnes réelles.
 */
class ParametrageHebergement
{
    /** Rubriques de l'accueil, dans l'ordre d'affichage (filtrées par droits dans `accueil()`). */
    private const SECTIONS = [
        [
            'titre' => 'Parc immobilier',
            'description' => 'Référentiels utilisés par les cités, les bâtiments et les chambres.',
            'modules' => [
                ['label' => 'Types de cité', 'icon' => 'city', 'route' => 'admin.hebergement.parametrage-herb.type-cite.index', 'description' => 'Catégories de résidences'],
                ['label' => 'Types d’équipements', 'icon' => 'sofa', 'route' => 'admin.hebergement.parametrage-herb.type-equipement.index', 'description' => 'Familles de matériel'],
                ['label' => 'Types de chambre', 'icon' => 'bed', 'route' => 'admin.hebergement.parametrage-herb.type-chambre.index', 'description' => 'Tarifs et équipements par type'],
            ],
        ],
        [
            'titre' => 'Campagnes & admissions',
            'description' => 'Capacités, résidences ouvertes, critères et barème de classement des campagnes.',
            'modules' => [
                ['label' => 'Capacités et quotas', 'icon' => 'pie', 'route' => 'admin.hebergement.parametrage-herb.quotas-campagne.index', 'description' => 'Places et demandes par campagne'],
                ['label' => 'Bâtiments par campagne', 'icon' => 'building', 'route' => 'admin.hebergement.parametrage-herb.batiments-campagne.index', 'description' => 'Résidences ouvertes'],
                ['label' => 'Règles d’éligibilité', 'icon' => 'check', 'route' => 'admin.hebergement.parametrage-herb.regles-campagne.index', 'description' => 'Critères des campagnes'],
                ['label' => 'Scoring', 'icon' => 'star', 'route' => 'admin.hebergement.parametrage-herb.scoring-campagne.index', 'description' => 'Barème de classement'],
            ],
        ],
        [
            'titre' => 'Documents et entrée dans les lieux',
            'description' => 'Pièces demandées aux étudiants, notifications, contrat et rendez-vous d’entrée.',
            'modules' => [
                ['label' => 'Documents à fournir', 'icon' => 'file', 'route' => 'admin.hebergement.parametrage-herb.document.index', 'description' => 'Pièces des dossiers'],
                ['label' => 'Notifications, contrat et entrée', 'icon' => 'calendar', 'route' => 'admin.housing-entry.settings', 'description' => 'Parcours d’entrée automatique'],
            ],
        ],
    ];

    public function __construct(private AdminAccess $access) {}

    /** Rubriques de l'accueil avec les seuls modules accessibles (aucun lien interdit envoyé au navigateur). */
    public function accueil(?User $admin): array
    {
        return collect(self::SECTIONS)
            ->map(fn (array $section) => [
                'titre' => $section['titre'],
                'description' => $section['description'],
                'modules' => collect($section['modules'])
                    ->filter(fn (array $module) => $this->access->allows($admin, $module['route']))
                    ->map(fn (array $module) => [
                        'label' => $module['label'],
                        'icon' => $module['icon'],
                        'description' => $module['description'],
                        'href' => route($module['route']),
                    ])->values()->all(),
            ])
            ->filter(fn (array $section) => $section['modules'] !== [])
            ->values()->all();
    }

    /** Campagnes proposées dans la liste de choix (la plus récente d'abord). */
    public function campagnes(): Collection
    {
        return Campagne::orderByDesc('created_at')->get();
    }

    /** Campagne demandée (`?campagne_id=`) ; une valeur inconnue ramène à la plus récente. */
    public function campagneChoisie(Request $request, Collection $campagnes): ?Campagne
    {
        $id = (string) $request->query('campagne_id', '');

        return $campagnes->first(fn (Campagne $c) => (string) $c->getKey() === $id) ?? $campagnes->first();
    }

    /** @return list<array{id: string, annee: string|null, description: string|null, statut: string|null}> */
    public function options(Collection $campagnes): array
    {
        return $campagnes->map(fn (Campagne $c) => [
            'id' => (string) $c->getKey(),
            'annee' => $c->annee_campagne,
            'description' => $c->description,
            'statut' => $c->statut,
        ])->values()->all();
    }

    /** Critères et conditions d'une campagne (page « Règles d'éligibilité »). */
    public function regles(Campagne $campagne): array
    {
        $campagne->loadMissing('documentCampagnes.documents', 'residences.cites');
        $date = fn ($valeur) => $valeur ? Carbon::parse($valeur)->toDateString() : null;

        return [
            'periode_debut' => $date($campagne->periode_debut),
            'periode_fin' => $date($campagne->periode_fin),
            'date_echeance_demande' => $date($campagne->date_echeance_demande),
            'date_echeance_contrat' => $date($campagne->date_echeance_contrat),
            'moyenne_min' => $campagne->moyenne_min,
            'pourcentage_validate' => $campagne->pourcentage_validate !== null ? (int) $campagne->pourcentage_validate : null,
            'distance_min' => $campagne->distance_min,
            'ages' => collect(['L1', 'L2', 'L3', 'M1', 'M2', 'D1', 'D2', 'D3'])
                ->mapWithKeys(fn ($niveau) => [$niveau => $campagne->{'age_'.$niveau.'_max'}])
                ->filter(fn ($age) => $age !== null),
            'conditions' => collect(['admission', 'readmission', 'internationnaux', 'redoublement', 'pmr', 'is_participate', 'is_discipline'])
                ->mapWithKeys(fn ($champ) => [$champ => (bool) $campagne->{$champ}]),
            'documents' => $campagne->documentCampagnes->flatMap->documents->pluck('name')->unique()->values(),
            'residences' => $campagne->residences->flatMap->cites->pluck('name')->unique()->values(),
        ];
    }

    /** Résidences ouvertes à la campagne et leurs bâtiments (page « Bâtiments par campagne »). */
    public function batiments(Campagne $campagne): array
    {
        $cites = $this->citesDeLaCampagne($campagne);
        $chambres = $this->chambres($cites->pluck('id_cite'));

        return $cites->map(function (Cite $cite) use ($chambres) {
            $batiments = $cite->batiment->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->map(function ($batiment) use ($chambres) {
                $liste = $chambres->where('batiment_id', $batiment->id_batiment);

                return [
                    'id' => (string) $batiment->id_batiment,
                    'name' => $batiment->name,
                    'actif' => $batiment->statut === 'actif',
                    'type_occupation' => $batiment->typeOccupation?->name,
                ] + $this->capacite($liste);
            })->values();

            return [
                'id' => (string) $cite->id_cite,
                'name' => $cite->name,
                'type' => $cite->typeCite?->name,
                'actif' => $cite->estActive(),
                'batiments' => $batiments,
            ] + $this->capacite($chambres->where('cite_id', $cite->id_cite));
        })->values()->all();
    }

    /** Capacités par type de chambre et répartition des demandes (page « Capacités et quotas »). */
    public function quotas(Campagne $campagne): array
    {
        $cites = $this->citesDeLaCampagne($campagne);
        $chambres = $this->chambres($cites->pluck('id_cite'));

        $types = $chambres->groupBy(fn ($chambre) => $chambre->typeChambre?->name ?? 'Type non défini')
            ->map(fn ($liste, $nom) => ['type' => $nom] + $this->capacite($liste))
            ->sortBy('type', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        // Les dossiers en brouillon, annulés ou expirés ne sont pas des demandes en cours.
        $demandes = DemandeEtudiant::query()
            ->where('campagne_id', $campagne->getKey())
            ->whereNotIn('etat_dossier', ['brouillon', 'annule', 'expire']);
        $genres = (clone $demandes)
            ->leftJoin('etudiants', 'etudiants.id_etudiant', '=', 'demande.etudiant_id')
            ->leftJoin('genre', 'genre.id', '=', 'etudiants.genre_id')
            ->selectRaw('genre.name as genre, count(*) as total')
            ->groupBy('genre.name')
            ->pluck('total', 'genre');

        return [
            'capacite' => $this->capacite($chambres),
            'types' => $types,
            'demandes' => [
                'total' => (clone $demandes)->count(),
                'boursiers' => (clone $demandes)->where('boursier', 1)->count(),
                'genres' => $genres->map(fn ($total, $genre) => ['genre' => $genre ?: 'Non renseigné', 'total' => (int) $total])->values(),
            ],
        ];
    }

    private function citesDeLaCampagne(Campagne $campagne): Collection
    {
        $ids = DB::table('residence_campagne')->where('campagne_id', $campagne->getKey())->pluck('cite_id')->unique();

        return Cite::with(['typeCite', 'batiment.typeOccupation'])
            ->whereIn('id_cite', $ids)
            ->orderBy('name')
            ->get();
    }

    /** Chambres numérotées des cités (les gabarits sans numéro ne sont pas des chambres réelles). */
    private function chambres(Collection $citeIds): Collection
    {
        return Chambre::with('typeChambre')
            ->whereIn('cite_id', $citeIds)
            ->whereNotNull('numero_chambre')
            ->get(['id_chambre', 'cite_id', 'batiment_id', 'type_chambre_id', 'statut', 'etat', 'capacite_max', 'occupants_actuels', 'pmr']);
    }

    /** Totaux d'un ensemble de chambres : seules les chambres actives et disponibles comptent dans les places libres. */
    private function capacite(Collection $chambres): array
    {
        $places = (int) $chambres->sum(fn ($c) => max(1, (int) $c->capacite_max));
        $occupees = (int) $chambres->sum(fn ($c) => min(max(1, (int) $c->capacite_max), max(0, (int) $c->occupants_actuels)));
        $libres = (int) $chambres
            ->filter(fn ($c) => $c->statut === 'actif' && $c->etat === 'disponible')
            ->sum(fn ($c) => max(0, max(1, (int) $c->capacite_max) - max(0, (int) $c->occupants_actuels)));

        return [
            'chambres' => $chambres->count(),
            'places' => $places,
            'occupees' => $occupees,
            'libres' => $libres,
            'pmr' => $chambres->filter(fn ($c) => (bool) $c->pmr)->count(),
        ];
    }
}
