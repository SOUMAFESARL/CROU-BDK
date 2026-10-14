<?php

namespace App\Repositories;

use App\Models\Intervention;
use App\Repositories\Interfaces\InterventionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class InterventionRepository implements InterventionRepositoryInterface
{
    protected $model;

    public function __construct(Intervention $model)
    {
        $this->model = $model;
    }

    public function getAll(array $filters = []): Collection
    {
        $query = $this->model->query();

        // Appliquer les filtres
        if (!empty($filters['technicien_id'])) {
            $query->where('technicien_id', $filters['technicien_id']);
        }

        if (!empty($filters['id_ordres_travail'])) {
            $query->where('id_ordres_travail', $filters['id_ordres_travail']);
        }

        if (!empty($filters['date_debut'])) {
            $query->where('date_intervention', '>=', $filters['date_debut']);
        }

        if (!empty($filters['date_fin'])) {
            $query->where('date_intervention', '<=', $filters['date_fin']);
        }

        if (!empty($filters['resultat'])) {
            $query->where('resultat', $filters['resultat']);
        }

        return $query->orderBy('date_intervention', 'desc')->get();
    }

    public function findById(string $id): ?Intervention
    {
        return $this->model->find($id);
    }

    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->query();

        // Appliquer les filtres
        if (!empty($filters['technicien_id'])) {
            $query->where('technicien_id', $filters['technicien_id']);
        }

        if (!empty($filters['id_ordres_travail'])) {
            $query->where('id_ordres_travail', $filters['id_ordres_travail']);
        }

        if (!empty($filters['date_debut'])) {
            $query->where('date_intervention', '>=', $filters['date_debut']);
        }

        if (!empty($filters['date_fin'])) {
            $query->where('date_intervention', '<=', $filters['date_fin']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('description', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('remarques', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('date_intervention', 'desc')->paginate($perPage);
    }

    public function create(array $data): Intervention
    {
        return DB::transaction(function () use ($data) {
            // Nettoyer les données
            unset($data['id_intervention']); // L'UUID est généré automatiquement

            // Assurer que les champs JSON sont bien encodés
            if (isset($data['materiel_utilise']) && is_array($data['materiel_utilise'])) {
                $data['materiel_utilise'] = json_encode($data['materiel_utilise']);
            }

            if (isset($data['pieces_detachees']) && is_array($data['pieces_detachees'])) {
                $data['pieces_detachees'] = json_encode($data['pieces_detachees']);
            }

            if (isset($data['photos_avant']) && is_array($data['photos_avant'])) {
                $data['photos_avant'] = json_encode($data['photos_avant']);
            }

            if (isset($data['photos_apres']) && is_array($data['photos_apres'])) {
                $data['photos_apres'] = json_encode($data['photos_apres']);
            }

            return $this->model->create($data);
        });
    }

    public function update(string $id, array $data): ?Intervention
    {
        return DB::transaction(function () use ($id, $data) {
            $intervention = $this->findById($id);

            if (!$intervention) {
                return null;
            }

            // Assurer que les champs JSON sont bien encodés
            if (isset($data['materiel_utilise']) && is_array($data['materiel_utilise'])) {
                $data['materiel_utilise'] = json_encode($data['materiel_utilise']);
            }

            if (isset($data['pieces_detachees']) && is_array($data['pieces_detachees'])) {
                $data['pieces_detachees'] = json_encode($data['pieces_detachees']);
            }

            if (isset($data['photos_avant']) && is_array($data['photos_avant'])) {
                $data['photos_avant'] = json_encode($data['photos_avant']);
            }

            if (isset($data['photos_apres']) && is_array($data['photos_apres'])) {
                $data['photos_apres'] = json_encode($data['photos_apres']);
            }

            $intervention->update($data);
            return $intervention->fresh();
        });
    }

    public function delete(string $id): bool
    {
        $intervention = $this->findById($id);

        if (!$intervention) {
            return false;
        }

        return $intervention->delete();
    }

    public function getByOrdreTravail(string $ordreTravailId): Collection
    {
        return $this->model
            ->where('id_ordres_travail', $ordreTravailId)
            ->orderBy('date_intervention', 'desc')
            ->get();
    }

    public function getByTechnicien(string $technicienId): Collection
    {
        return $this->model
            ->where('technicien_id', $technicienId)
            ->orderBy('date_intervention', 'desc')
            ->get();
    }

    public function getByPeriod(string $startDate, string $endDate): Collection
    {
        return $this->model
            ->whereBetween('date_intervention', [$startDate, $endDate])
            ->orderBy('date_intervention', 'desc')
            ->get();
    }

    public function getWithTrashed(): Collection
    {
        return $this->model->withTrashed()->get();
    }

    public function restore(string $id): bool
    {
        $intervention = $this->model->withTrashed()->find($id);

        if (!$intervention) {
            return false;
        }

        return $intervention->restore();
    }

    public function forceDelete(string $id): bool
    {
        $intervention = $this->model->withTrashed()->find($id);

        if (!$intervention) {
            return false;
        }

        return $intervention->forceDelete();
    }

public function storeSatisfaction(
    string $ordreTravailId,
    array $data
): ?Intervention {
    $intervention = $this->model->newQuery()
        ->where('id_ordres_travail', $ordreTravailId)
        ->whereNull('deleted_at')
        ->orderByDesc('date_intervention')
        ->orderByDesc('id_intervention')
        ->first();

    if (!$intervention) {
        return null;
    }

    $intervention->update([
        'taux_satisfaction' => $data['taux_satisfaction'],
        'commentaire_satisfaction' =>
            $data['commentaire_satisfaction'] ?? null,
        'date_satisfaction' => now(),
        'date_mise_a_jour' => now(),
        'updated_at' => now(),
        'updated_by' => $data['updated_by'] ?? null,
    ]);

    return $intervention->fresh();
}

}
