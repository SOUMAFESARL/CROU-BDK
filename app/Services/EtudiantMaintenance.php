<?php

namespace App\Services;

use App\Models\Etudiant;
use App\Models\OrdreTravail;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Props de la page React « Maintenance » de l'espace étudiant (Etudiant/Maintenance) :
 * indicateurs et liste paginée des demandes (ordres de travail) déposées par l'étudiant,
 * et de quoi ouvrir l'assistant de signalement (équipements de sa chambre confirmée).
 * Seules les valeurs affichées sont envoyées, déjà formatées.
 */
class EtudiantMaintenance
{
    /** Filtres de la liste (`?filtre=`). */
    public const FILTRES = ['toutes', 'en_cours', 'resolues', 'urgentes'];

    private const CLOTUREES = ['termine', 'annule'];

    private const URGENTES = ['critique', 'elevee'];

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

        return [
            'compteurs' => $this->compteurs($id),
            'filtre' => $filtre,
            'demandes' => collect($ordres->items())->map(fn (OrdreTravail $ordre) => $this->demande($ordre))->values()->all(),
            'pagination' => self::pagination($ordres),
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
