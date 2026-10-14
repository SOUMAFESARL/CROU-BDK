<?php
// app/Repositories/AnnonceRepository.php

namespace App\Repositories;

use App\Models\Annonce;
use App\Models\AnnonceStat;
use App\Repositories\Interfaces\AnnonceRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AnnonceRepository implements AnnonceRepositoryInterface
{
    protected Annonce $model;

    public function __construct(Annonce $model)
    {
        $this->model = $model;
    }

    /**
     * Récupérer toutes les annonces
     */
    public function all(): Collection
    {
        return $this->model->with(['createur'])->orderBy('created_at', 'desc')->get();
    }

    /**
     * Récupérer une annonce par son ID
     */
    public function find(string $id): ?object
    {
        return $this->model->with(['createur'])->find($id);
    }

    /**
     * Créer une nouvelle annonce
     */
    public function create(array $data): object
    {
        $data['id_annonce'] = (string) Str::uuid();
        return DB::transaction(fn () => $this->model->create($data));
    }

    /**
     * Mettre à jour une annonce
     */
    public function update(string $id, array $data): bool
    {
        $annonce = $this->model->find($id);
        if (!$annonce) {
            return false;
        }
        return DB::transaction(fn () => $annonce->update($data));
    }

    /**
     * Supprimer une annonce
     */
    public function delete(string $id): bool
    {
        $annonce = $this->model->find($id);
        if (!$annonce) {
            return false;
        }
        return $annonce->delete();
    }

    /**
     * Récupérer les annonces avec pagination et filtres
     */
    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Annonce::query()
            ->with('createur')
            ->withCount([
                'deliveries as deliveries_sent_count' => fn ($q) => $q->where('status', 'sent'),
                'deliveries as deliveries_failed_count' => fn ($q) => $q->where('status', 'failed'),
                'deliveries as deliveries_pending_count' => fn ($q) => $q->whereIn('status', ['pending', 'sending']),
            ])
            ->latest('created_at');

        if (!empty($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }

        if (!empty($filters['canal'])) {
            // Si "canaux" est une colonne JSON (ex: ["flux","email"])
            $query->whereJsonContains('canaux', $filters['canal']);
        }

        if (!empty($filters['audience'])) {
            $query->where('audience', $filters['audience']);
        }

        if (!empty($filters['priorite'])) {
            $query->where('priorite', $filters['priorite']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('titre', 'like', "%{$search}%")
                    ->orWhere('contenu', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['date_debut'])) {
            $query->whereDate('created_at', '>=', $filters['date_debut']);
        }

        if (!empty($filters['date_fin'])) {
            $query->whereDate('created_at', '<=', $filters['date_fin']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Récupérer les annonces actives
     */
    public function getActives(): Collection
    {
        return $this->model->actif()->with(['createur'])->orderBy('priorite', 'desc')->get();
    }

    /**
     * Récupérer les annonces par statut
     */
    public function getByStatut(string $statut): Collection
    {
        return $this->model->where('statut', $statut)->with(['createur'])->get();
    }

    /**
     * Récupérer les annonces par canal
     */
    public function getByCanal(string $canal): Collection
    {
        return $this->model->parCanal($canal)->with(['createur'])->get();
    }

    /**
     * Publier une annonce
     */
    public function publish(string $id): bool
    {
        $annonce = $this->model->find($id);
        if (!$annonce) {
            return false;
        }
        return DB::transaction(fn () => $annonce->publier());
    }

    /**
     * Planifier une annonce
     */
    public function schedule(string $id, string $date): bool
    {
        $annonce = $this->model->find($id);
        if (!$annonce) {
            return false;
        }
        return $annonce->planifier($date);
    }

    /**
     * Annuler une annonce
     */
    public function cancel(string $id): bool
    {
        $annonce = $this->model->find($id);
        if (!$annonce) {
            return false;
        }
        return $annonce->annuler();
    }

    /**
     * Incrémenter les vues
     */
    public function incrementViews(string $id): bool
    {
        $annonce = $this->model->find($id);
        if (!$annonce) {
            return false;
        }
        return $annonce->incrementerVues();
    }

    /**
     * Enregistrer une statistique
     */
    public function recordStat(string $annonceId, array $data): bool
    {
        $stat = AnnonceStat::create([
            'id_stats' => (string) Str::uuid(),
            'annonce_id' => $annonceId,
            'date_stat' => $data['date'] ?? now(),
            'vues' => $data['vues'] ?? 0,
            'clics' => $data['clics'] ?? 0,
            'reactions' => $data['reactions'] ?? 0,
            'partages' => $data['partages'] ?? 0,
        ]);

        return (bool) $stat;
    }

    /**
     * Récupérer les statistiques d'une annonce
     */
    public function getStats(string $annonceId, ?string $dateDebut = null, ?string $dateFin = null): array
    {
        $query = AnnonceStat::where('annonce_id', $annonceId);

        if ($dateDebut) {
            $query->where('date_stat', '>=', $dateDebut);
        }
        if ($dateFin) {
            $query->where('date_stat', '<=', $dateFin);
        }

        $stats = $query->orderBy('date_stat')->get();

        return [
            'total_vues' => $stats->sum('vues'),
            'total_clics' => $stats->sum('clics'),
            'total_reactions' => $stats->sum('reactions'),
            'total_partages' => $stats->sum('partages'),
            'par_jour' => $stats->groupBy('date_stat')->map(function ($items) {
                return [
                    'vues' => $items->sum('vues'),
                    'clics' => $items->sum('clics'),
                    'reactions' => $items->sum('reactions'),
                    'partages' => $items->sum('partages'),
                ];
            })->toArray()
        ];
    }

    /**
     * Récupérer les annonces pour un étudiant
     */
    public function getForEtudiant(string $etudiantId): Collection
    {
        $student = \App\Models\Etudiant::findOrFail($etudiantId);
        return $this->model->visibleParEtudiant($student)->orderByDesc('created_at')->get();
    }

    /**
     * Compter les annonces par statut
     */
    public function countByStatut(): array
    {
        return [
            'total' => $this->model->count(),
            'publiees' => $this->model->where('statut', 'publie')->count(),
            'planifiees' => $this->model->where('statut', 'planifie')->count(),
            'brouillons' => $this->model->where('statut', 'brouillon')->count(),
            'annulees' => $this->model->where('statut', 'annule')->count(),
        ];
    }

    /**
     * Exporter les annonces
     */
    public function export(array $filters): array
    {
        $query = $this->model->query();

        if (!empty($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }
        if (!empty($filters['canal'])) {
            $query->whereJsonContains('canaux', $filters['canal']);
        }
        if (!empty($filters['date_debut'])) {
            $query->where('created_at', '>=', $filters['date_debut']);
        }
        if (!empty($filters['date_fin'])) {
            $query->where('created_at', '<=', $filters['date_fin'] . ' 23:59:59');
        }

        return $query->get()->map(function ($annonce) {
            return [
                'id' => $annonce->id_annonce,
                'titre' => $annonce->titre,
                'contenu' => $annonce->contenu,
                'canaux' => implode(', ', $annonce->canaux),
                'statut' => $annonce->statut_libelle,
                'priorite' => $annonce->priorite_libelle,
                'audience' => $annonce->audience_libelle,
                'date_creation' => $annonce->created_at->format('d/m/Y H:i'),
                'date_publication' => $annonce->date_publication?->format('d/m/Y H:i') ?? 'Non publiée',
                'vues' => $annonce->vues,
                'reactions' => $annonce->reactions,
                'auteur' => $annonce->createur?->name ?? 'Inconnu'
            ];
        })->toArray();
    }
}