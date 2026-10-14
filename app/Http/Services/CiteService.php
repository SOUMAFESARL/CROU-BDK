<?php

namespace App\Http\Services;

use App\Exceptions\OccupationIncompatibleException;
use App\Repositories\Interfaces\CiteRepositoryInterface;
use App\Repositories\Interfaces\OccupationServiceInterface;

use Illuminate\Support\Facades\DB;
use App\Models\Cite;

class CiteService
{
    protected    $citeRepository;
        protected  $occupationService;
    public function __construct(
         CiteRepositoryInterface   $citeRepository,
         OccupationServiceInterface $occupationService
    ) {
        $this->citeRepository = $citeRepository;
        $this->occupationService = $occupationService;
    }

    /**
     * Créer une nouvelle cité.
     */
    public function createCite(array $data): Cite
    {

        return DB::transaction(function () use ($data) {

            return $this->citeRepository->create($data);
        });
    }

    /**
     * Mettre à jour une cité.
     * Si le type d'occupation change, propagation en cascade vers bâtiments et chambres.
     */
    public function updateCite(string $code, array $data): Cite
    {

        return DB::transaction(function () use ($code, $data) {

            $cite = $this->citeRepository->findByCode($code);

            $ancienType  = $this->citeRepository->getTypeOccupation($code);

            $nouveauType = $data['type_cite'] ?? $ancienType;
           // dd($ancienType ,$nouveauType );
            $cite = $this->citeRepository->update($code, $data);

            // Si le type d'occupation a changé ET n'est plus mixte → propagation obligatoire


                $this->occupationService->propagateOccupationFromCite($code, $nouveauType);


            return $cite;
        });
    }

    /**
     * Supprimer une cité (avec vérification des dépendances).
     */
    public function deleteCite(string $code): void
    {
        if ($this->citeRepository->hasBatiments($code)) {
            throw new \RuntimeException(
                'Impossible de supprimer cette cité : elle contient des bâtiments. '
                . 'Veuillez d\'abord supprimer tous les bâtiments associés.'
            );
        }

        $this->citeRepository->delete($code);
    }

    /**
     * Retourner les types d'occupation autorisés pour les bâtiments d'une cité.
     */
    public function getAllowedBatimentTypes(string $citeCode): array
    {
        return $this->occupationService->getAllowedTypesForBatiment($citeCode);
    }

    /**
     * Retourner toutes les cités d'un campus.
     */
    public function getCitesByCampus(string $campusCode): \Illuminate\Database\Eloquent\Collection
    {
        return $this->citeRepository->getByCampus($campusCode);
    }

    public function getCites(): \Illuminate\Database\Eloquent\Collection
    {

        return $this->citeRepository->getAll();
    }

    public function findById(string $id): ?Cite
    {
        return $this->citeRepository->findByCode($id);
    }
    public function getCiteByStatut(string $statut): \Illuminate\Database\Eloquent\Collection
    {
        return $this->citeRepository->getCiteByStatut($statut);
    }
}