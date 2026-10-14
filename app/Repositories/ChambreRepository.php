<?php

namespace App\Repositories;

use App\Models\Batiment;
use App\Models\Chambre;
use App\Repositories\Interfaces\BatimentRepositoryInterface;
use App\Repositories\Interfaces\ChambreRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ChambreRepository implements ChambreRepositoryInterface
{
    protected                     $model;
        protected                    $batimentModel;
        protected  $batimentRepository;
    public function __construct(
        Chambre                    $model,
         Batiment                   $batimentModel,
         BatimentRepositoryInterface $batimentRepository
    ) {
        $this->model = $model;
        $this->batimentModel = $batimentModel;
        $this->batimentRepository = $batimentRepository;
    }
    public function getDisponibles(string $campagneId): Collection
    {
        return $this->model->withDefaultRelations()
            ->disponibles($campagneId)
            ->orderBy('cite_id')
            ->orderBy('batiment_id')
            ->orderBy('numero_chambre')
            ->get()->filter(fn ($room) => $room->peutAccueillir())->values();
    }
    // -------------------------------------------------------------------------
    // Lecture
    // -------------------------------------------------------------------------

    public function getAll(): Collection
    {
        return $this->model->withDefaultRelations()->orderBy('numero_chambre')->whereNotNull('numero_chambre')
        ->whereNotNull('type_chambre_id')->get();
    }

    public function findByCode(string $code): ?Chambre
    {
        return $this->model->withDefaultRelations()->where('id_chambre', $code)->first();
    }
    public function findById(string $code): ?Chambre
    {
      //  dd($this->model->where('id_chambre', $code)->first());
        return $this->model->where('id_chambre', $code)->first();
    }

    public function getByBatiment(string $batimentCode): Collection
    {
        return $this->model
            ->where('batiment_id', $batimentCode)
            ->with('typeChambre')
            ->orderBy('niveau_aile_code')
            ->orderBy('numero_chambre')
            ->get();
    }

    public function getByNiveau(string $niveauCode, string $batimentCode): Collection
    {
        return $this->model
            ->where('niveau_aile_code', $niveauCode)
            ->where('batiment_id', $batimentCode)
            ->with('typeChambre')
            ->get();
    }

    public function getChambresCreees(string $niveauCode, string $batimentCode): Collection
    {
        return $this->model
            ->where('niveau_aile_code', $niveauCode)
            ->where('batiment_id', $batimentCode)
            ->whereNotNull('numero_chambre')
            ->whereNotNull('type_chambre_id')

            ->with('typeChambre')
            ->get();
    }

    public function getChambresNonCreees(string $niveauCode, string $batimentCode): Collection
    {
        return $this->model
            ->where('niveau_aile_code', $niveauCode)
            ->where('batiment_id', $batimentCode)
            ->where(function ($q) {
                $q->whereNull('numero_chambre')
                    ->orWhereNull('type_chambre_id');

            })
            ->get();
    }

    // -------------------------------------------------------------------------
    // Types d'occupation autorisés
    // -------------------------------------------------------------------------

    /**
     * Types d'occupation autorisés pour une chambre selon son bâtiment.
     */
    public function getAllowedOccupationTypes(string $batimentCode): array
    {
        $batiment = $this->batimentModel->where('batiment_id', $batimentCode)->first();

        if (!$batiment) {
            return ['mixte', 'hommes', 'femmes'];
        }

        return match ($batiment->type_occupation) {
            'hommes' => ['hommes'],
            'femmes' => ['femmes'],
            default  => ['mixte', 'hommes', 'femmes'],
        };
    }

    // -------------------------------------------------------------------------
    // Écriture
    // -------------------------------------------------------------------------

    /**
     * Compléter une chambre partielle (sans numero_chambre ni type_chambre_code).
     */
    public function completeChambre(string $code, string $numeroChambre, string $typeChambreCode): Chambre
    {
        $chambre = $this->findByCodeOrFail($code);

        $chambre->update([
            'numero_chambre'    => $numeroChambre,
            'type_chambre_id' => $typeChambreCode,
            'updated_by'        => getInfoAdmin()->users->id_user,
            'updated_at'        => now(),
        ]);

        return $chambre->fresh()->load('typeChambre');
    }

    public function update(string $code, array $data): Chambre
    {
        $chambre = $this->findByCodeOrFail($code);

        $updateData = array_filter([
            'numero_chambre'    => $data['numero_chambre']    ?? null,
            'type_chambre_id' => $data['type_chambre_code'] ?? null,
            'etat'              => $data['etat']              ?? null,
            'capacite_max'      => $data['capacite_max']      ?? null,
            'updated_by'        => getInfoAdmin()->users->code,
        ], fn($v) => !is_null($v));

        $chambre->update($updateData);

        return $chambre->fresh()->load('typeChambre');
    }

    /**
     * Réinitialiser une chambre (supprimer numero et type pour la rendre "non créée").
     */
    public function resetChambre(string $code): Chambre
    {
        $chambre = $this->findByCodeOrFail($code);

        $chambre->update([
            'numero_chambre'    => null,
            'type_chambre_id' => null,
            'updated_by'        => getInfoAdmin()->users->code,
        ]);

        return $chambre->fresh();
    }

    // -------------------------------------------------------------------------
    // Vérifications
    // -------------------------------------------------------------------------

    public function isNumeroDuplicate(
        string $numeroChambre,
        string $batimentCode,
        string $niveauCode,
        ?string $excludeCode = null
    ): bool {
        $query = $this->model
            ->where('batiment_id', $batimentCode)
            ->where('niveau_aile_code', $niveauCode)
            ->where('numero_chambre', $numeroChambre);

        if ($excludeCode) {
            $query->where('batiment_id', '!=', $excludeCode);
        }

        return $query->exists();
    }

    






    // -------------------------------------------------------------------------
    // Helpers privés
    // -------------------------------------------------------------------------

    private function findByCodeOrFail(string $code): Chambre
    {
        $chambre = $this->findByCode($code);
        if (!$chambre) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Chambre introuvable : {$code}");
        }
        return $chambre;
    }
}