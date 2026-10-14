<?php

namespace App\Http\Services;

use App\Repositories\Interfaces\InterventionRepositoryInterface;
use App\Models\Intervention;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;


class InterventionService
{
    protected $repository;

    public function __construct(InterventionRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Récupère toutes les interventions avec filtres
     */
    public function getAllInterventions(array $filters = []): Collection
    {
        return $this->repository->getAll($filters);
    }

    /**
     * Récupère une intervention par ID
     */
    public function getInterventionById(string $id): ?Intervention
    {
        return $this->repository->findById($id);
    }

    /**
     * Récupère les interventions paginées
     */
    public function getPaginatedInterventions(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->repository->getPaginated($perPage, $filters);
    }

    /**
     * Crée une nouvelle intervention
     */
    public function createIntervention(array $data): Intervention
    {

        $this->validateInterventionData($data);

        // Préparer les données
        $data['date_creation'] = now();
        $data['created_at'] = now();

        if (isset($data['materiel_utilise']) && is_string($data['materiel_utilise'])) {
            $data['materiel_utilise'] = json_decode($data['materiel_utilise'], true);
        }

        if (isset($data['pieces_detachees']) && is_string($data['pieces_detachees'])) {
            $data['pieces_detachees'] = json_decode($data['pieces_detachees'], true);
        }

        if (isset($data['photos_avant']) && is_string($data['photos_avant'])) {
            $data['photos_avant'] = json_decode($data['photos_avant'], true);
        }

        if (isset($data['photos_apres']) && is_string($data['photos_apres'])) {
            $data['photos_apres'] = json_decode($data['photos_apres'], true);
        }
//dd($data);
        return $this->repository->create($data);
    }

    /**
     * Met à jour une intervention
     */
    public function updateIntervention(string $id, array $data): ?Intervention
    {
        $this->validateInterventionData($data, true);

        $data['date_mise_a_jour'] = now();
        $data['updated_at'] = now();

        if (isset($data['materiel_utilise']) && is_string($data['materiel_utilise'])) {
            $data['materiel_utilise'] = json_decode($data['materiel_utilise'], true);
        }

        if (isset($data['pieces_detachees']) && is_string($data['pieces_detachees'])) {
            $data['pieces_detachees'] = json_decode($data['pieces_detachees'], true);
        }

        if (isset($data['photos_avant']) && is_string($data['photos_avant'])) {
            $data['photos_avant'] = json_decode($data['photos_avant'], true);
        }

        if (isset($data['photos_apres']) && is_string($data['photos_apres'])) {
            $data['photos_apres'] = json_decode($data['photos_apres'], true);
        }

        return $this->repository->update($id, $data);
    }

    /**
     * Supprime une intervention
     */
    public function deleteIntervention(string $id): bool
    {
        $intervention = $this->getInterventionById($id);

        if (!$intervention) {
            throw new \InvalidArgumentException("L'intervention avec l'ID {$id} n'existe pas.");
        }

        return $this->repository->delete($id);
    }

    /**
     * Récupère les interventions d'un ordre de travail
     */
    public function getInterventionsByOrdreTravail(string $ordreTravailId): Collection
    {
        return $this->repository->getByOrdreTravail($ordreTravailId);
    }

    /**
     * Récupère les interventions d'un technicien
     */
    public function getInterventionsByTechnicien(string $technicienId): Collection
    {
        return $this->repository->getByTechnicien($technicienId);
    }

    /**
     * Récupère les interventions par période
     */
    public function getInterventionsByPeriod(string $startDate, string $endDate): Collection
    {
        return $this->repository->getByPeriod($startDate, $endDate);
    }

    /**
     * Restaure une intervention supprimée
     */
    public function restoreIntervention(string $id): bool
    {
        return $this->repository->restore($id);
    }

    /**
     * Supprime définitivement une intervention
     */
    public function forceDeleteIntervention(string $id): bool
    {
        return $this->repository->forceDelete($id);
    }

    /**
     * Valide les données de l'intervention
     */
    protected function validateInterventionData(array $data, bool $isUpdate = false): void
    {
        $rules = [
            'id_ordres_travail' => 'required|exists:ordres_travail,id_ordres_travail',
            'date_intervention' => 'required|date',
            'duree' => 'required|integer|min:1',
            'description' => 'required|string|max:1000',
            'technicien_id' => 'nullable|exists:maintenanciers,id_maintenancier',
            'equipe' => 'nullable|string|max:255',
            'materiel_utilise' => 'nullable|array',
            'pieces_detachees' => 'nullable|array',
            'resultat' => 'required|in:resolu,partiellement_resolu,non_resolu,necessite_suivi',
            'remarques' => 'nullable|string|max:2000',
            'photos_avant' => 'nullable|array',
            'photos_apres' => 'nullable|array',
        ];

        if ($isUpdate) {
            // Pour la mise à jour, tous les champs sont optionnels
            $rules = array_map(function ($rule) {
                return str_replace('required|', 'sometimes|', $rule);
            }, $rules);
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Obtient les statistiques des interventions
     */
    public function getInterventionStats(array $filters = []): array
    {
        $interventions = $this->repository->getAll($filters);

        $stats = [
            'total' => $interventions->count(),
            'resolu' => $interventions->where('resultat', 'resolu')->count(),
            'partiellement_resolu' => $interventions->where('resultat', 'partiellement_resolu')->count(),
            'non_resolu' => $interventions->where('resultat', 'non_resolu')->count(),
            'necessite_suivi' => $interventions->where('resultat', 'necessite_suivi')->count(),
            'duree_moyenne' => $interventions->avg('duree'),
            'duree_totale' => $interventions->sum('duree'),
        ];

        return $stats;
    }
/**
 * Enregistre la satisfaction d'un ordre de travail.
 *
 * @throws ValidationException
 */
public function storeSatisfaction(array $data): Intervention
{
    $validator = Validator::make($data, [
        'ot_id' => [
            'required',
            'exists:ordres_travail,id_ordres_travail',
        ],
        'taux_satisfaction' => [
            'required',
            'integer',
            'between:1,5',
        ],
        'commentaire_satisfaction' => [
            'nullable',
            'string',
            'max:2000',
        ],
    ], [
        'ot_id.required' =>
            "L'ordre de travail est obligatoire.",

        'ot_id.exists' =>
            "L'ordre de travail sélectionné n'existe pas.",

        'taux_satisfaction.required' =>
            'Veuillez sélectionner une note.',

        'taux_satisfaction.integer' =>
            'La note doit être un nombre entier.',

        'taux_satisfaction.between' =>
            'La note doit être comprise entre 1 et 5.',

        'commentaire_satisfaction.max' =>
            'Le commentaire ne doit pas dépasser 2 000 caractères.',
    ]);

    if ($validator->fails()) {
        throw new ValidationException($validator);
    }

    $validated = $validator->validated();

    return DB::transaction(function () use ($validated) {
        $interventions = $this->repository
            ->getByOrdreTravail($validated['ot_id']);

        $intervention = $interventions
            ->sortByDesc('date_intervention')
            ->first();

        if (!$intervention) {
            throw ValidationException::withMessages([
                'ot_id' =>
                    "Aucune intervention n'est associée à cet ordre de travail.",
            ]);
        }

        if (!is_null($intervention->taux_satisfaction)) {
            throw ValidationException::withMessages([
                'taux_satisfaction' =>
                    'Un avis a déjà été enregistré pour cette intervention.',
            ]);
        }

       // $validated['updated_by'] = auth()->id();

        $result = $this->repository->storeSatisfaction(
            $validated['ot_id'],
            $validated
        );

        if (!$result) {
            throw ValidationException::withMessages([
                'ot_id' =>
                    "Impossible de retrouver l'intervention concernée.",
            ]);
        }

        return $result;
    });
}
}
