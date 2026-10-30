<?php

namespace App\Services;

use App\Models\AssignationChambre;
use App\Models\Chambre;
use App\Models\Etudiant;
use App\Models\OrdreTravail;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Props de la page React « Maintenance » de l'espace étudiant (Etudiant/Maintenance) :
 * indicateurs et liste paginée des demandes (ordres de travail) déposées par l'étudiant,
 * ordres de travail initiés par l'administration qui le concernent (onglet « Travaux programmés »)
 * et de quoi ouvrir l'assistant de signalement (équipements de sa chambre confirmée).
 * Seules les valeurs affichées sont envoyées, déjà formatées.
 */
class EtudiantMaintenance
{
    /** Filtres de la liste (`?filtre=`). */
    public const FILTRES = ['toutes', 'en_cours', 'resolues', 'urgentes'];

    private const CLOTUREES = ['termine', 'annule'];

    private const URGENTES = ['critique', 'elevee'];

    /** Onglets de la page (`?onglet=`). */
    public const ONGLETS = ['demandes', 'administration'];

    /** Natures d'intervention (OrdreTravail::TYPE_INTERVENTIONS). */
    private const NATURES = ['corrective' => 'Corrective', 'preventive' => 'Préventive', 'urgente' => 'Urgente'];

    /** Niveaux d'urgence (OrdreTravail::PRIORITES), libellés du formulaire de signalement. */
    private const PRIORITES = [
        'critique' => ['Critique', 'danger'],
        'elevee' => ['Urgente', 'warning'],
        'moyenne' => ['Normale', 'info'],
        'faible' => ['Peut attendre', 'neutral'],
    ];

    public function __construct(private EtudiantChambre $chambres) {}

    public function props(Etudiant $etudiant, Request $request): array
    {
        $id = (string) $etudiant->getKey();
        $filtre = in_array($request->query('filtre'), self::FILTRES, true) ? $request->query('filtre') : 'toutes';
        $ordres = $this->filtrer($this->requete($id), $filtre)
            ->with('assigneA')
            ->latest('created_at')
            ->orderByDesc('id_ordres_travail')
            ->paginate(10)
            ->withPath(route('etudiants.dashboard'))->appends($request->except('page'));
        $assignation = $this->chambres->assignation($id);
        $chambre = $assignation?->chambre;
        $administration = $this->administration($assignation)
            ->with('assigneA')
            ->latest('created_at')
            ->orderByDesc('id_ordres_travail')
            ->paginate(10, ['*'], 'page_admin')
            ->withPath(route('etudiants.dashboard'))->appends($request->except('page_admin') + ['onglet' => 'administration']);

        return [
            'onglet' => in_array($request->query('onglet'), self::ONGLETS, true) ? $request->query('onglet') : 'demandes',
            'compteurs' => $this->compteurs($id),
            'filtre' => $filtre,
            'demandes' => collect($ordres->items())->map(fn (OrdreTravail $ordre) => $this->demande($ordre))->values()->all(),
            'pagination' => self::pagination($ordres),
            'ordresAdministration' => collect($administration->items())->map(fn (OrdreTravail $ordre) => $this->ordreAdministration($ordre, $chambre))->values()->all(),
            'paginationAdministration' => self::pagination($administration),
            'administrationEnCours' => $this->administration($assignation)->whereNotIn('statut', self::CLOTUREES)->count(),
            'chambre' => $chambre
                ? collect(['Chambre '.($chambre->numero_chambre ?: $chambre->code_chambre), $chambre->batiment?->name, $chambre->cite?->name])->filter()->implode(' · ')
                : null,
            'equipements' => $chambre ? $this->chambres->equipements($chambre) : [],
            'categories' => EtudiantChambre::categories(),
            'urls' => [
                'signalement' => route('etudiants.store_ot'),
                'page' => route('etudiants.dashboard', ['section' => 'maintenance']),
                'chambre' => route('etudiants.dashboard', ['section' => 'chambre']),
            ],
        ];
    }

    private function requete(string $etudiantId): Builder
    {
        return OrdreTravail::query()->where('etudiant_id', $etudiantId);
    }

    /**
     * Ordres de travail créés par l'administration (sans étudiant demandeur) visant le logement de l'étudiant
     * selon l'emplacement choisi : sa chambre, son bâtiment entier (sans chambre) ou toute sa cité (sans bâtiment).
     * Sans chambre confirmée, aucun ordre ne le concerne ; les ordres clôturés avant son arrivée ne sont pas repris.
     */
    private function administration(?AssignationChambre $assignation): Builder
    {
        $requete = OrdreTravail::query()->whereNull('etudiant_id');
        $chambre = $assignation?->chambre;
        if (! $chambre) {
            return $requete->whereRaw('1 = 0');
        }
        if ($assignation->date_assignation) {
            $requete->where(fn (Builder $q) => $q->whereNotIn('statut', self::CLOTUREES)->orWhere('created_at', '>=', $assignation->date_assignation));
        }

        return $requete->where(function (Builder $q) use ($chambre) {
            $q->where('chambre_id', $chambre->getKey());
            if ($chambre->batiment_id) {
                $q->orWhere(fn (Builder $b) => $b->whereNull('chambre_id')->where('batiment_id', $chambre->batiment_id));
            }
            if ($chambre->cite_id) {
                $q->orWhere(fn (Builder $c) => $c->whereNull('chambre_id')->whereNull('batiment_id')->where('cite_id', $chambre->cite_id));
            }
        });
    }

    private function filtrer(Builder $requete, string $filtre): Builder
    {
        return match ($filtre) {
            'en_cours' => $requete->whereNotIn('statut', self::CLOTUREES),
            'resolues' => $requete->where('statut', 'termine'),
            'urgentes' => $requete->whereIn('priorite', self::URGENTES)->whereNotIn('statut', self::CLOTUREES),
            default => $requete,
        };
    }

    /** Effectif de chaque filtre et taux de résolution, sur les seules demandes de l'étudiant. */
    private function compteurs(string $etudiantId): array
    {
        $compte = fn (string $filtre) => $this->filtrer($this->requete($etudiantId), $filtre)->count();
        $total = $compte('toutes');
        $resolues = $compte('resolues');

        return [
            'toutes' => $total,
            'en_cours' => $compte('en_cours'),
            'resolues' => $resolues,
            'urgentes' => $compte('urgentes'),
            'tauxResolution' => $total > 0 ? (int) round($resolues / $total * 100) : 0,
        ];
    }

    private function demande(OrdreTravail $ordre): array
    {
        $statut = (string) $ordre->statut;
        [$libelleStatut, $tonStatut] = EtudiantChambre::STATUTS_SIGNALEMENT[$statut] ?? [ucfirst(str_replace('_', ' ', $statut)), 'neutral'];
        [$libellePriorite, $tonPriorite] = self::PRIORITES[$ordre->priorite] ?? [ucfirst((string) $ordre->priorite), 'neutral'];
        $date = fn ($valeur) => $valeur ? formatDateFr($valeur->format('Y-m-d'), true) : null;
        $technicien = rescue(fn () => $ordre->assigneA?->name, null, false);

        return [
            'id' => (string) $ordre->getKey(),
            'numero' => $ordre->numero_ot,
            'titre' => $ordre->titre ?: EtudiantChambre::libelleCategorie((string) $ordre->categorie),
            'description' => $ordre->description,
            'categorie' => $ordre->categorie ? EtudiantChambre::libelleCategorie((string) $ordre->categorie) : null,
            'priorite' => ['libelle' => $libellePriorite, 'ton' => $tonPriorite],
            'statut' => ['libelle' => $libelleStatut, 'ton' => $tonStatut],
            // Avancement : 0 déposée, 1 prise en charge (technicien affecté ou travaux en cours), 2 terminée ; null si annulée ou reportée.
            'etape' => match (true) {
                $statut === 'termine' => 2,
                in_array($statut, ['annule', 'reporte'], true) => null,
                $statut === 'en_cours' || $statut === 'en_attente_pieces' || $ordre->assigne_a => 1,
                default => 0,
            },
            'creeLe' => $date($ordre->created_at),
            'disponibilite' => collect([$date($ordre->date_disponibilite), $ordre->periode_disponibilite])->filter()->implode(' · ') ?: null,
            'intervention' => $date($ordre->date_debut_intervention),
            'resolueLe' => $date($ordre->date_resolution),
            'technicien' => $technicien,
            'solution' => $statut === 'termine' ? $ordre->solution_apportee : null,
            'satisfaction' => $ordre->taux_satisfaction ? (int) $ordre->taux_satisfaction : null,
        ];
    }

    private function ordreAdministration(OrdreTravail $ordre, ?Chambre $chambre): array
    {
        $statut = (string) $ordre->statut;
        [$libelleStatut, $tonStatut] = EtudiantChambre::STATUTS_SIGNALEMENT[$statut] ?? [ucfirst(str_replace('_', ' ', $statut)), 'neutral'];
        [$libellePriorite, $tonPriorite] = self::PRIORITES[$ordre->priorite] ?? [ucfirst((string) $ordre->priorite), 'neutral'];
        $date = fn ($valeur) => $valeur ? formatDateFr($valeur->format('Y-m-d'), true) : null;
        $debut = $date($ordre->date_debut_intervention);
        $fin = $date($ordre->date_fin_intervention);

        return [
            'id' => (string) $ordre->getKey(),
            'numero' => $ordre->numero_ot,
            'titre' => $ordre->titre ?: EtudiantChambre::libelleCategorie((string) $ordre->categorie),
            'description' => $ordre->description,
            'categorie' => $ordre->categorie ? EtudiantChambre::libelleCategorie((string) $ordre->categorie) : null,
            'nature' => self::NATURES[$ordre->type_intervention] ?? null,
            'priorite' => ['libelle' => $libellePriorite, 'ton' => $tonPriorite],
            'statut' => ['libelle' => $libelleStatut, 'ton' => $tonStatut],
            'portee' => match (true) {
                (bool) $ordre->chambre_id => 'Ma chambre',
                (bool) $ordre->batiment_id => 'Tout le bâtiment'.($chambre?->batiment?->name ? ' ('.$chambre->batiment->name.')' : ''),
                default => 'Toute la cité'.($chambre?->cite?->name ? ' ('.$chambre->cite->name.')' : ''),
            },
            'creeLe' => $date($ordre->created_at),
            'intervention' => $debut ? ($fin && $fin !== $debut ? "Du {$debut} au {$fin}" : $debut) : null,
            'technicien' => rescue(fn () => $ordre->assigneA?->name, null, false),
            'resolueLe' => $date($ordre->date_resolution),
            'solution' => $statut === 'termine' ? $ordre->solution_apportee : null,
        ];
    }

    /** Pagination simple (page courante, total, liens précédent / suivant) pour les pages étudiant. */
    public static function pagination(LengthAwarePaginator $page): array
    {
        return [
            'page' => $page->currentPage(),
            'pages' => $page->lastPage(),
            'total' => $page->total(),
            'precedent' => $page->previousPageUrl(),
            'suivant' => $page->nextPageUrl(),
        ];
    }
}
