<?php


namespace App\Repositories;

use App\Models\OrdreTravail;
use App\Models\Intervention;
use App\Repositories\Interfaces\OrdreTravailRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrdreTravailRepository implements OrdreTravailRepositoryInterface
{
    protected OrdreTravail $model;

    public function __construct(OrdreTravail $model)
    {
        $this->model = $model;
    }

    /**
     * Récupérer tous les ordres de travail
     */
    public function getAll(array $filters = []): Collection
    {
        $query = $this->model->withDefaultRelations()->newQuery();

        return $this->applyFilters($query, $filters)->get();
    }

    /**
     * Récupérer les ordres de travail avec pagination
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        //dd('gfhjkl');
        $query = $this->model->withDefaultRelations()->newQuery();

        if (array_key_exists('completed', $filters)) {
            $query->where('statut', $filters['completed'] ? '=' : '!=', 'termine');
            $query->orderByDesc($filters['completed'] ? 'date_resolution' : 'created_at')
                ->orderByDesc('id_ordres_travail');
        }

        return $this->applyFilters($query, $filters)
            ->paginate($perPage, ['*'], $filters['page_name'] ?? 'page');
    }

    /**
     * Récupérer un ordre de travail par son ID
     */
    public function findById(string $id): ?OrdreTravail
    {
        return $this->model->withDefaultRelations()->find($id);
    }

    /**
     * Récupérer un ordre de travail par son numéro
     */
    public function findByNumero(string $numero): ?OrdreTravail
    {
        return $this->model->where('numero_ot', $numero)->withDefaultRelations()->first();
    }

    /**
     * Récupérer un ordre de travail par etudiant
     */
    public function findByEtudiant(string $id): ?OrdreTravail
    {
        return $this->model->where('etudiant_id', $id)->withDefaultRelations()->get();
    }

    /**
     * Créer un nouvel ordre de travail
     */
    public function create(array $data): OrdreTravail
    {

      //  dd($data);
        return $this->model->create($data);
    }

    /**
     * Mettre à jour un ordre de travail
     */
    public function update(string $id, array $data): OrdreTravail
    {
        $ordre = $this->findById($id);
        $ordre->update($data);
        return $ordre->fresh();
    }

    /**
     * Supprimer un ordre de travail
     */
    public function delete(string $id): bool
    {
        $ordre = $this->findById($id);
        return $ordre ? $ordre->delete() : false;
    }

    /**
     * Assigner un technicien à un ordre de travail
     */
    public function assigner(string $id, array $data): OrdreTravail
    {
        $ordre = $this->findById($id);
        $ordre->update($data);
        $ordre->statut = 'en_cours';

        $ordre->save();
        return $ordre->fresh();
    }

    /**
     * Mettre à jour le statut d'un ordre de travail
     */
    public function updateStatut(string $id, string $statut, ?string $solution = null): OrdreTravail
    {
        return DB::transaction(function () use ($id, $statut, $solution) {
            $ordre = $this->model->newQuery()->lockForUpdate()->findOrFail($id);

            if ($statut === 'termine') {
                $ordre->terminer($solution);
            } else {
                $ordre->statut = $statut;
                $ordre->save();
            }

            return $ordre->fresh();
        });
    }

    /**
     * Ajouter une intervention à un ordre de travail
     */
    public function ajouterIntervention(string $id, array $data): Intervention
    {
        $ordre = $this->findById($id);
        $data['id_intervention'] = Str::uuid()->toString();
        return $ordre->interventions()->create($data);
    }

    /**
     * Récupérer les statistiques des ordres de travail
     */
    public function getStats(array $filters = []): array
    {
        $query = $this->model->newQuery();
        $this->applyFilters($query, $filters);

        return [
            'total' => $query->count(),
            'en_attente' => $query->clone()->where('statut', 'en_attente')->count(),
            'en_cours' => $query->clone()->where('statut', 'en_cours')->count(),
            'en_attente_pieces' => $query->clone()->where('statut', 'en_attente_pieces')->count(),
            'termine' => $query->clone()->where('statut', 'termine')->count(),
            'urgent' => $query->clone()->where('priorite', 'critique')->count(),
            'cout_total' => $query->clone()->sum('cout_reel'),
            'temps_moyen_resolution' => $query->clone()->whereNotNull('date_resolution')
                    ->select(DB::raw('AVG(TIMESTAMPDIFF(DAY, date_disponibilite, date_resolution)) as avg'))
                    ->value('avg') ?? 0,
            'satisfaction_count' => $query->clone()->whereNotNull('taux_satisfaction')->count(),
            'taux_satisfaction' => round($query->clone()->whereNotNull('taux_satisfaction')->avg('taux_satisfaction') ?? 0),
        ];
    }

    /**
     * Récupérer les ordres de travail par statut
     */
    public function getByStatut(string $statut): Collection
    {
        return $this->model->where('statut', $statut)->get();
    }

    /**
     * Récupérer les ordres de travail urgents
     */
    public function getUrgents(): Collection
    {
        return $this->model->where('priorite', 'critique')
            ->whereIn('statut', ['en_attente', 'en_cours', 'en_attente_pieces'])
            ->get();
    }

    /**
     * Récupérer les ordres de travail par résidence
     */
    public function getByResidence(string $residenceId): Collection
    {
        return $this->model->where('cite_id', $residenceId)->get();
    }

    /**
     * Récupérer les ordres de travail par technicien
     */
    public function getByTechnicien(string $userId): Collection
    {
        return $this->model->where('assigne_a', $userId)->get();
    }

    /**
     * Appliquer les filtres à la requête
     */
    protected function applyFilters($query, array $filters)
    {
        if (!empty($filters['etudiant_id'])) {
            $query->where('etudiant_id', $filters['etudiant_id']);
        }

        if (isset($filters['statut']) && $filters['statut'] !== 'tous') {
            $query->where('statut', $filters['statut']);
        }

        if (isset($filters['priorite']) && $filters['priorite'] !== 'tous') {
            $query->where('priorite', $filters['priorite']);
        }

        if (isset($filters['categorie']) && $filters['categorie'] !== 'tous') {
            $query->where('categorie', $filters['categorie']);
        }

        if (isset($filters['cite_id']) && $filters['cite_id'] !== 'tous') {
            $query->where('cite_id', $filters['cite_id']);
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('numero_ot', 'LIKE', $search)
                    ->orWhere('titre', 'LIKE', $search)
                    ->orWhere('description', 'LIKE', $search);
            });
        }

        if (isset($filters['date_debut']) && isset($filters['date_fin'])) {
            $query->whereBetween('date_disponibilite', [$filters['date_debut'], $filters['date_fin']]);
        }

        if (isset($filters['date_debut']) && !isset($filters['date_fin'])) {
            $query->whereDate('date_disponibilite', '>=', $filters['date_debut']);
        }

        if (!isset($filters['date_debut']) && isset($filters['date_fin'])) {
            $query->whereDate('date_disponibilite', '<=', $filters['date_fin']);
        }

        return $query;
    }


}
