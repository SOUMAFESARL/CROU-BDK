<?php

namespace App\Repositories;

use App\Models\Maintenancier;
use App\Repositories\Interfaces\MaintenancierRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MaintenancierRepository implements MaintenancierRepositoryInterface
{
    /**
     * @var Maintenancier
     */
    protected Maintenancier $model;

    /**
     * MaintenancierRepository constructor.
     */
    public function __construct(Maintenancier $maintenancier)
    {
        $this->model = $maintenancier;
    }

    /**
     * {@inheritDoc}
     */
    public function getAll(): Collection
    {
        return $this->model->all();
    }

    /**
     * {@inheritDoc}
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with('cite')
            ->orderBy('name', 'asc')
            ->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function findById(string $id): ?Maintenancier
    {
        return $this->model->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findWithRelations(string $id): ?Maintenancier
    {
        return $this->model->with('cite')->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): Maintenancier
    {
        try {
            DB::beginTransaction();

            $maintenancier = $this->model->create($data);

            DB::commit();
            return $maintenancier;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création du maintenancier: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function update(string $id, array $data): bool
    {
        try {
            DB::beginTransaction();

            $maintenancier = $this->findById($id);
            if (!$maintenancier) {
                throw new \Exception('Maintenancier non trouvé');
            }

            $result = $maintenancier->update($data);

            DB::commit();
            return $result;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour du maintenancier: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $id): bool
    {
        try {
            DB::beginTransaction();

            $maintenancier = $this->findById($id);
            if (!$maintenancier) {
                throw new \Exception('Maintenancier non trouvé');
            }

            $result = $maintenancier->delete();

            DB::commit();
            return $result;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la suppression du maintenancier: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function restore(string $id): bool
    {
        try {
            DB::beginTransaction();

            $maintenancier = $this->model->withTrashed()->find($id);
            if (!$maintenancier) {
                throw new \Exception('Maintenancier non trouvé');
            }

            $result = $maintenancier->restore();

            DB::commit();
            return $result;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la restauration du maintenancier: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function forceDelete(string $id): bool
    {
        try {
            DB::beginTransaction();

            $maintenancier = $this->model->withTrashed()->find($id);
            if (!$maintenancier) {
                throw new \Exception('Maintenancier non trouvé');
            }

            $result = $maintenancier->forceDelete();

            DB::commit();
            return $result;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la suppression définitive du maintenancier: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getBySpecialite(string $specialite): Collection
    {
        return $this->model->where('specialite', $specialite)
            ->where('statut', true)
            ->with('cite')
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByCite(string $citeId): Collection
    {
        return $this->model->where('cite_id', $citeId)
            ->where('statut', true)
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getActive(): Collection
    {
        return $this->model->where('statut', true)
            ->with('cite')
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getInactive(): Collection
    {
        return $this->model->where('statut', false)
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getWithRelations(): Collection
    {
        return $this->model->with('cite')
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function search(string $term): Collection
    {
        return $this->model->where('name', 'LIKE', "%{$term}%")
            ->orWhere('email', 'LIKE', "%{$term}%")
            ->orWhere('tel', 'LIKE', "%{$term}%")
            ->orWhere('specialite', 'LIKE', "%{$term}%")
            ->with('cite')
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function countBySpecialite(): array
    {
        return $this->model->select('specialite', DB::raw('count(*) as total'))
            ->groupBy('specialite')
            ->pluck('total', 'specialite')
            ->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function countByCite(): array
    {
        return $this->model->select('cite_id', DB::raw('count(*) as total'))
            ->whereNotNull('cite_id')
            ->groupBy('cite_id')
            ->with('cite')
            ->get()
            ->pluck('total', 'cite.name')
            ->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function updateStatus(string $id, bool $statut): bool
    {
        try {
            DB::beginTransaction();

            $maintenancier = $this->findById($id);
            if (!$maintenancier) {
                throw new \Exception('Maintenancier non trouvé');
            }

            $result = $maintenancier->update(['statut' => $statut]);

            DB::commit();
            return $result;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour du statut: ' . $e->getMessage());
            throw $e;
        }
    }
}