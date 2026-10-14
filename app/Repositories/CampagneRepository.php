<?php

namespace App\Repositories;

use App\Models\Campagne;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CampagneRepository
{
    protected  $model;

    public function __construct(Campagne $model)
    {
        $this->model = $model;
    }

    /**
     * Récupérer toutes les campagnes
     */
    public function getAll(): Collection
    {
        $campagnes = $this->model->orderBy('annee_debut', 'desc')->get();

        $uneDejaActive = false; // 🔒 flag pour tracker si une campagne est déjà active

        $campagnes->each(function ($campagne) use (&$uneDejaActive) {
            $nouveauStatut = $this->determineStatut($campagne->toArray());

            // Si le statut calculé est 'active' mais qu'une campagne est déjà active
            if ($nouveauStatut === 'active') {
                if ($uneDejaActive) {
                    $nouveauStatut = 'planifier'; // 🚫 on la repasse en planifier
                } else {
                    $uneDejaActive = true; // ✅ on marque qu'une campagne est active
                }
            }

            if ($campagne->statut !== $nouveauStatut) {
                $campagne->statut = $nouveauStatut;
                $campagne->save();
            }
        });

        return $campagnes;
    }

    /**
     * Récupérer les campagnes avec pagination
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->orderBy('annee_debut', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Trouver une campagne par son ID
     */
    public function findById(string $id): ?Campagne
    {
        return $this->model->find($id);
    }

    /**
     * Créer une nouvelle campagne
     */
    public function create(array $data): Campagne
    {
        // Générer un UUID si non fourni
        if (empty($data['id_campagne'])) {
            $data['id_campagne'] = (string) Str::uuid();
        }

        // Extraire les années si annee_campagne est fourni
        if (!empty($data['annee_campagne']) && empty($data['annee_debut'])) {
            if (preg_match('/(\d{4})-(\d{4})/', $data['annee_campagne'], $matches)) {
                $data['annee_debut'] = (int) $matches[1];
                $data['annee_fin'] = (int) $matches[2];
            }
        }


        // Définir created_by si non fourni
        if (empty($data['created_by'])) {
            $user = getInfoAdmin()->users->id_user ?? auth()->user()->id_user ?? null;
            $data['created_by'] = $user;
        }
        $data['statut'] = $this->determineStatut($data);

        return $this->model->create($data);
    }

    /**
     * Mettre à jour une campagne
     */
    public function update(string $id, array $data): bool
    {
        $campagne = $this->findById($id);
        if ($campagne) {
            // Mettre à jour updated_by
            $user = getInfoAdmin()->users->code ?? auth()->user()->code ?? null;
            $data['updated_by'] = $user;

            // Extraire les années si annee_campagne est fourni
            if (!empty($data['annee_campagne'])) {
                if (preg_match('/(\d{4})-(\d{4})/', $data['annee_campagne'], $matches)) {
                    $data['annee_debut'] = (int) $matches[1];
                    $data['annee_fin'] = (int) $matches[2];
                }
            }

            return $campagne->update($data);
        }
        return false;
    }

    /**
     * Supprimer une campagne
     */
    public function delete(string $id): bool
    {
        $campagne = $this->findById($id);
        if ($campagne) {
            // Définir deleted_by
            $user = getInfoAdmin()->users->code ?? auth()->user()->code ?? null;
            $campagne->deleted_by = $user;
            $campagne->save();

            return $campagne->delete();
        }
        return false;
    }

    /**
     * Restaurer une campagne supprimée
     */
    public function restore(string $id): bool
    {
        $campagne = $this->model->withTrashed()->where('id_campagne', $id)->first();
        if ($campagne) {
            $campagne->deleted_by = null;
            $campagne->save();
            return $campagne->restore();
        }
        return false;
    }

    /**
     * Supprimer définitivement une campagne
     */
    public function forceDelete(string $id): bool
    {
        $campagne = $this->model->withTrashed()->find($id);
        if ($campagne) {
            return $campagne->forceDelete();
        }
        return false;
    }

    /**
     * Récupérer la campagne en cours
     */
    public function getCurrentCampagne(): ?Campagne
    {
        return $this->model->current()
            ->orderByDesc('created_at')
            ->first()
            ?? $this->model->orderByDesc('annee_debut')->orderByDesc('created_at')->first();
    }

    /**
     * Récupérer les campagnes par année
     */
    public function getByYear(int $year): Collection
    {
        return $this->model->byYear($year)->get();
    }

    /**
     * Vérifier si une année académique existe
     */
    public function exists(string $academicYear, ?string $excludeId = null): bool
    {
        $query = $this->model->where('annee_campagne', $academicYear);

        if ($excludeId) {
            $query->where('id_campagne', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Générer la prochaine année académique
     */
    public function getNextAcademicYear(): string
    {
        $lastCampagne = $this->model->orderBy('annee_debut', 'desc')->first();

        if ($lastCampagne) {
            $nextDebut = $lastCampagne->annee_debut + 1;
        } else {
            $currentYear = date('Y');
            $nextDebut = date('m') >= 9 ? $currentYear : $currentYear - 1;
        }

        $nextFin = $nextDebut + 1;

        return $nextDebut . '-' . $nextFin;
    }


    /**
     * Déterminer le statut d'une campagne
     */
    protected function determineStatut(array $data): string
    {
        if (empty($data['periode_debut']) || empty($data['periode_fin'])) {
            return 'brouillon';
        }

        $aujourdhui = Carbon::now();
        $debut = Carbon::parse($data['periode_debut']);
        $fin = Carbon::parse($data['periode_fin']);

        // Vérifier la validité des dates
        if ($debut->greaterThan($fin)) {
            throw new Exception('La date de début ne peut pas être postérieure à la date de fin');
        }

        // Déterminer le statut
        if ($debut->lte($aujourdhui) && $fin->gte($aujourdhui)) {
            return 'active';
        } elseif ($debut->gt($aujourdhui)) {
            return 'planifier';
        } elseif ($fin->lt($aujourdhui)) {
            return 'terminer';
        }

        return 'planifier';
    }


    public function findWithRelations(string $id): ?Campagne
    {
        return $this->model->withDefaultRelations()->where('id_campagne',$id)->first();
    }
    public function getAllWithFilters(?string $etat = null): Collection
    {

        try {
            $query = $this->model->withDefaultRelations()
                ->orderBy('created_at', 'DESC');

            switch ($etat) {
                case 'active':
                    $query->where('statut', 'active');
                    break;
                case 'terminer':
                    $query->where('statut', 'terminer');
                    break;
                case 'planifier':
                    $query->where('statut', 'planifier');
                    break;

                default:

                    break;

            }

            return $query->get();
        }catch (\Exception $e){
            return "Erreur survenue lors de la recherche";
        }

    }

    public function countActive(): int
    {
        return Campagne::where('statut', 'active')->count();
    }

    public function countTerminer(): int
    {
        return Campagne::where('statut', 'terminer')->count();
    }

    public function countPlanifier(): int
    {
        return Campagne::where('statut', 'planifier')->count();
    }
    public function getStats(): array
    {
        $counts = Campagne::selectRaw('statut, COUNT(*) as aggregate')->groupBy('statut')->pluck('aggregate','statut');
        return [
            'active'=>(int)($counts['active'] ?? 0),
            'terminer'=>(int)($counts['terminer'] ?? 0),
            'planifier'=>(int)($counts['planifier'] ?? 0),
            'total'=>(int)$counts->sum(),
        ];
    }
}
