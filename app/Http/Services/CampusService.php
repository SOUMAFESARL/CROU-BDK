<?php

namespace App\Http\Services;

use App\Repositories\Interfaces\CampusRepositoryInterface;
use App\Models\Campus;
use Illuminate\Support\Facades\Storage;
use Exception;
use Illuminate\Database\Eloquent\Collection;

class CampusService
{
    protected  $repository;

    public function __construct(CampusRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Récupérer tous les campus
     */
    public function getAllCampus(array $filters = []): array
    {
        return $this->repository->getAll($filters);
    }

    /**
     * Récupérer un campus
     */
    public function getCampus(string $id): ?Campus
    {
        return $this->repository->getById($id);
    }

    /**
     * Récupérer un campus par code
     */
    public function getCampusByCode(string $code): ?Campus
    {
        return $this->repository->getByCode($code);
    }

    /**
     * Créer un campus
     */
    public function createCampus(array $data): Campus
    {

        // Traiter le logo
        if (isset($data['logo']) && $data['logo']) {
            $data['logo'] = upload('campus','png', $data, 'logo');
        }

        return $this->repository->create($data);
    }

    /**
     * Mettre à jour un campus
     */
    public function updateCampus(string $id, array $data): Campus
    {

        $campus = $this->repository->getByCode($id);
      
        if (!$campus) {
            throw new Exception('Campus non trouvé');
        }

        // Traiter le logo
        if (isset($data['logo']) && $data['logo']) {


            $data['logo'] = update( 'campus', $campus->logo, 'png', $data, 'logo');
            
        } else {
            unset($data['logo']);
        }

        return $this->repository->update($id, $data);
    }

    /**
     * Supprimer un campus
     */
    public function deleteCampus(string $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Restaurer un campus
     */
    public function restoreCampus(string $id): bool
    {
        return $this->repository->restore($id);
    }

    /**
     * Supprimer définitivement un campus
     */
    public function forceDeleteCampus(string $id): bool
    {
        return $this->repository->forceDelete($id);
    }

    /**
     * Récupérer les campus par région
     */
    public function getCampusByRegion(int $regionId): array
    {
        return $this->repository->getByRegion($regionId);
    }

    /**
     * Récupérer les campus par ville
     */
    public function getCampusByVille(int $villeId): array
    {
        return $this->repository->getByVille($villeId);
    }

    /**
     * Récupérer les campus actifs
     */
    public function getActiveCampus(): array
    {
        return $this->repository->getActive();
    }

    /**
     * Chercher des campus
     */
    public function searchCampus(string $term): array
    {
        return $this->repository->search($term);
    }

    /**
     * Compter les campus
     */
    public function countCampus(array $filters = []): int
    {
        return $this->repository->count($filters);
    }

    /**
     * Valider les données d'un campus
     */
    public function validateCampusData(array $data): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors[] = 'Le nom du campus est obligatoire';
        }

        if (empty($data['code_campus'])) {
            $errors[] = 'Le code du campus est obligatoire';
        }

        if (empty($data['email1']) || !filter_var($data['email1'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Un email valide est obligatoire';
        }

        if (empty($data['telephone1'])) {
            $errors[] = 'Le téléphone est obligatoire';
        }

        if (empty($data['statut']) || !in_array($data['statut'], ['actif', 'inactif', 'desactive'])) {
            $errors[] = 'Le statut est invalide';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}