<?php

namespace App\Repositories;

use App\Repositories\Interfaces\CampusRepositoryInterface;
use App\Models\Campus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;

class CampusRepository implements CampusRepositoryInterface
{
    protected  $model;

    public function __construct(Campus $model)
    {
        $this->model = $model;
    }

    /**
     * Récupérer tous les campus
     */
    public function getAll(array $filters = []): array
    {
        $query = $this->model->with(['region', 'ville', 'directeur']);

        if (isset($filters['region_id'])) {
            $query->byRegion($filters['region_id']);
        }

        if (isset($filters['ville_id'])) {
            $query->byVille($filters['ville_id']);
        }

        if (isset($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        return $query->get()->toArray();
    }

    /**
     * Récupérer un campus par ID
     */
    public function getById(string $id): ?Campus
    {
        return $this->model
            ->withDefaultRelations()
            ->where('id_campus', $id)->first();
    }

    /**
     * Récupérer un campus par code
     */
    public function getByCode(string $code): ?Campus
    {
        return $this->model
            ->withDefaultRelations()
            ->where('code', $code)
            ->first();
    }

    /**
     * Créer un campus
     */
    public function create(array $data): Campus
    {
      //  dd($data,'desnbdshjkz');
      //  $data['created_by'] = Auth::id();
        if ($data['latitude']) {
            $data['latitude'] = (float) $data['latitude'];
        }
        if ($data['longitude']) {
            $data['longitude'] = (float) $data['longitude'];
        }
        $campus = $this->model->create($data);
      //  dd($data,'desnbdshjkz');
        return $campus->fresh(['region', 'ville', 'directeur']);
    }

    /**
     * Mettre à jour un campus
     */
    public function update(string $id, array $data): Campus
    {
        $campus = $this->getByCode($id);
//dd($campus);
        if (!$campus) {
            throw new \Exception("Campus non trouvé");
        }

       // $data['updated_by'] = Auth::id();
        if ($data['latitude']) {
            $data['latitude'] = (float) $data['latitude'];
        }
        if ($data['longitude']) {
            $data['longitude'] = (float) $data['longitude'];
        }

        $campus->update($data);

        return $campus->fresh(['region', 'ville', 'directeur']);
    }

    /**
     * Supprimer un campus
     */
    public function delete(string $id): bool
    {
        $campus = $this->getById($id);

        if (!$campus) {
            throw new \Exception("Campus non trouvé");
        }

        $campus->update(['deleted_by' => Auth::id()]);

        return $campus->delete();
    }

    /**
     * Restaurer un campus
     */
    public function restore(string $id): bool
    {
        $campus = $this->model->withTrashed()->find($id);

        if (!$campus) {
            throw new \Exception("Campus non trouvé");
        }

        return $campus->restore();
    }

    /**
     * Supprimer définitivement un campus
     */
    public function forceDelete(string $id): bool
    {
        $campus = $this->model->withTrashed()->find($id);

        if (!$campus) {
            throw new \Exception("Campus non trouvé");
        }

        // Supprimer les fichiers
        if ($campus->logo && Storage::disk('public')->exists($campus->logo)) {
            Storage::disk('public')->delete($campus->logo);
        }

        return $campus->forceDelete();
    }

    /**
     * Récupérer les campus par région
     */
    public function getByRegion(int $regionId): array
    {
        return $this->model
            ->byRegion($regionId)
            ->with(['region', 'ville', 'directeur'])
            ->get()
            ->toArray();
    }

    /**
     * Récupérer les campus par ville
     */
    public function getByVille(int $villeId): array
    {
        return $this->model
            ->byVille($villeId)
            ->with(['region', 'ville', 'directeur'])
            ->get()
            ->toArray();
    }

    /**
     * Récupérer les campus actifs
     */
    public function getActive(): array
    {
        return $this->model
            ->active()
            ->with(['region', 'ville', 'directeur'])
            ->get()
            ->toArray();
    }

    /**
     * Chercher des campus
     */
    public function search(string $term): array
    {
        return $this->model
            ->search($term)
            ->with(['region', 'ville', 'directeur'])
            ->get()
            ->toArray();
    }

    /**
     * Compter les campus
     */
    public function count(array $filters = []): int
    {
        $query = $this->model;

        if (isset($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }

        if (isset($filters['region_id'])) {
            $query->byRegion($filters['region_id']);
        }

        return $query->count();
    }

    /**
     * Obtenir le campus principal
     */
    public function getPrincipal(): ?Campus
    {
        // À adapter selon votre logique métier
        return $this->model
            ->active()
            ->first();
    }
}