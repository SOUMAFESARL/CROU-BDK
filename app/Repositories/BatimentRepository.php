<?php

namespace App\Repositories;

use App\Models\Aile_Batiment;
use App\Models\Batiment;
use App\Models\Chambre;
use App\Models\Cite;
use App\Models\Niveau_Aile_Batiment;
use App\Repositories\Interfaces\BatimentRepositoryInterface;
use App\Repositories\Interfaces\CiteRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class BatimentRepository implements BatimentRepositoryInterface
{
    protected      $model;
        protected   $chambreModel;
        protected  $citeRepository;
    public function __construct(
         Batiment               $model,
         Chambre                $chambreModel,
         CiteRepositoryInterface $citeRepository
    ) {
        $this->model = $model;
        $this->chambreModel = $chambreModel;

        $this->citeRepository = $citeRepository;


    }

    // -------------------------------------------------------------------------
    // Lecture
    // -------------------------------------------------------------------------

    public function getAll(): Collection
    {
        return $this->model->withDefaultRelations()->orderBy('name')->get();
    }

    public function findByCode(string $code): ?Batiment
    {
        return $this->model->withDefaultRelations()->where('id_batiment', $code)->first();
    }

    public function getByCite(string $citeCode): Collection
    {
        return $this->model
            ->where('cite_id', $citeCode)
            ->orderBy('name')
            ->get();
    }

    public function getByCampus(string $campusCode): Collection
    {
        return $this->model
            ->where('campus_id', $campusCode)
            ->orderBy('name')
            ->get();
    }

    public function getWithStructure(string $code): ?Batiment
    {
        return $this->model
            ->with([
                'ailes.niveaux',
                'chambres.typeChambre',
            ])
            ->where('code_batiment', $code)
            ->first();
    }

    // -------------------------------------------------------------------------
    // Type d'occupation
    // -------------------------------------------------------------------------

    /**
     * Types autorisés pour un bâtiment selon sa cité.
     */
    public function getAllowedOccupationTypes(string $citeCode): array
    {
        return $this->citeRepository->getAllowedOccupationTypes($citeCode);
    }

    // -------------------------------------------------------------------------
    // Écriture
    // -------------------------------------------------------------------------

    public function create(array $data): Batiment
    {
        return $this->model->firstOrCreate(
            [
                'code_cite'   => $data['code_cite'],
                'code_campus' => $data['code_campus'],
                'name'        => $data['name'],
            ],
            [
                'code_batiment'         => generate('batiment', 'code_batiment', 150, 'a-zA-Z0-9'),
                'type_architecture'     => $data['type_architecture'],
                'type_occupation'       => $data['type_occupation'],
                'etat_initial'          => $data['etat_initial'] ?? 'operationnel',
                'description'           => $data['description'] ?? null,
                'plan_file'             => $data['plan_file'] ?? null,
                'total_chambres'        => $data['total_chambres'] ?? 0,
                'total_etages'          => $data['total_etages'] ?? 0,
                'total_ailes'           => $data['total_ailes'] ?? 0,
                'services_acces_handicape' => $data['services']['acces_handicape'] ?? false,
                'services_ascenseur'    => $data['services']['ascenseur'] ?? false,
                'services_gardiennage'  => $data['services']['gardiennage'] ?? false,
                'is_valid'              => $data['is_valid'] ?? true,
                'created_by'            => getInfoAdmin()->users->code,
            ]
        );
    }

    public function update(string $code, array $data): Batiment
    {
        $batiment = $this->findByCodeOrFail($code);

        $batiment->update([
            'name'             => $data['name']           ?? $batiment->name,
            'type_occupation'  => $data['type_occupation'] ?? $batiment->type_occupation,
            'etat_initial'     => $data['etat_initial']   ?? $batiment->etat_initial,
            'description'      => $data['description']    ?? $batiment->description,
            'updated_by'       => getInfoAdmin()->users->code,
        ]);

        return $batiment->fresh();
    }

    public function delete(string $code): bool
    {
        $batiment = $this->findByCodeOrFail($code);
        return $batiment->delete();
    }

    // -------------------------------------------------------------------------
    // Totaux
    // -------------------------------------------------------------------------

    public function updateTotals(string $code): Batiment
    {
        $batiment = $this->findByCodeOrFail($code);

        $totalChambres = $this->chambreModel
            ->where('code_batiment', $batiment->code_batiment)
            ->count();

        $totalEtages = Niveau_Aile_Batiment::whereIn(
            'code_aile',
            Aile_Batiment::where('code_batiment', $batiment->code_batiment)->pluck('code_aile')
        )->distinct('niveau_numero')->count();

        $totalAiles = Aile_Batiment::where('code_batiment', $batiment->code_batiment)->count();

        $batiment->update([
            'total_chambres' => $totalChambres,
            'total_etages'   => $totalEtages,
            'total_ailes'    => $totalAiles,
        ]);

        return $batiment->fresh();
    }

    // -------------------------------------------------------------------------
    // Vérifications
    // -------------------------------------------------------------------------

    public function hasChambres(string $code): bool
    {
        return $this->chambreModel->where('code_batiment', $code)->exists();
    }

    // -------------------------------------------------------------------------
    // Helpers privés
    // -------------------------------------------------------------------------

    private function findByCodeOrFail(string $code): Batiment
    {
        $batiment = $this->findByCode($code);
        if (!$batiment) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Bâtiment introuvable : {$code}");
        }
        return $batiment;
    }
    /**
     * Vérifie si un bâtiment avec ce nom existe déjà dans la cité,
     * en excluant éventuellement un code (pour les mises à jour).
     */
    public function existsInCite(string $citeCode, string $name, ?string $excludeCode = null): bool
    {
        $query = $this->model
            ->where('code_cite', $citeCode)
            ->where('name', $name);

        if ($excludeCode) {
            $query->where('code_batiment', '!=', $excludeCode);
        }

        return $query->exists();
    }

}