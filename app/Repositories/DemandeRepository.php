<?php
namespace App\Repositories;

use App\Models\DemandeEtudiant;
use App\Repositories\Interfaces\DemandeRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class DemandeRepository implements DemandeRepositoryInterface
{
    protected $model;

    public function __construct(DemandeEtudiant $demande)
    {
        $this->model = $demande;
    }

    public function create(array $datas): DemandeEtudiant
    {
        try {

            $data = [];

            DB::beginTransaction();


            $data = [
                'etudiant_id' => $datas['id_etudiant'],
                'niveau_etude_precedent_code' => $datas['niveau_precedent'] ?? null,
                'annee_academique_precedent' => $datas['annee_academique_precedent'] ?? null,
                'annee_academique_actuel' => $datas['annee_academique_actuel'] ?? null,
                'niveau_etude_actuel_code' => $datas['niveau'] ?? null,
                'filiere_code' => $datas['filiere'] ?? null,
                'commentaires' => $datas['cas_social'] ?? null,
                'boursier' => $datas['boursier'] ?? false,
                'type_hebergement_souhaite' => null,
                'periode' => now()->format('Y'),
                'statut_demande' => 'En_attente',
                'date_demande' => now(),
                'moyenne' => $datas['moyenne'],
                'credit_valider' => $datas['credit_valider'],
                'campagne_id' => $datas['id_campagne'],

            ];
            //dd( $data);
           // dd($datas);

            $demande = new DemandeEtudiant($data);
            $demande->workflowDraft = !empty($datas['save_as_draft']);
            $demande->save();


//dd($demande);
            DB::commit();



            return $demande;
        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    public function update(string $id, array $data): DemandeEtudiant
    {
        try {

            DB::beginTransaction();

            $demande = $this->find($id);

            if (!$demande) {
                throw new \Exception("Demande non trouvée avec l'ID: {$id}");
            }

            $data['updated_by'] = Auth::guard('etudiant')->user()->id_etudiant ?? $data['updated_by'] ?? null;
            $demande->update($data);

            DB::commit();

            Log::info('Demande mise à jour avec succès', ['id_demande' => $id]);

            return $demande->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour de la demande', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function find(string $id): ?DemandeEtudiant
    {
      //  dd(DemandeEtudiant::where('id_demande',$id)->first());
        return DemandeEtudiant::where('id_demande',$id)->first();
    }

    public function delete(string $id): bool
    {
        try {
            DB::beginTransaction();

            $demande = $this->find($id);
            if (!$demande) {
                throw new \Exception("Demande non trouvée avec l'ID: {$id}");
            }

            $demande->delete();

            DB::commit();

            Log::info('Demande supprimée avec succès', ['id_demande' => $id]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la suppression de la demande', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function findByEtudiant(string $etudiantId): Collection
    {
        return $this->model
            ->where('etudiant_id', $etudiantId)
            ->orderBy('date_demande', 'desc')
            ->get();
    }

    public function findByStatut(string $statut): Collection
    {
        return $this->model
            ->where('statut_demande', $statut)
            ->orderBy('date_demande', 'desc')
            ->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['etudiant'])
            ->orderBy('date_demande', 'desc')
            ->paginate($perPage);
    }

    public function countByStatut(): array
    {
        return $this->model
            ->select('statut_demande', DB::raw('count(*) as total'))
            ->groupBy('statut_demande')
            ->pluck('total', 'statut_demande')
            ->toArray();
    }

    public function updateStatut(string $id, string $statut, ?string $commentaires = null): bool
    {
        try {

            app(\App\Http\Services\DossierWorkflow::class)->legacy($id, $statut, $commentaires);
            return true;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour du statut', [
                'id' => $id,
                'statut' => $statut,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

   public function changerStatut(string $id, string $statut, ?string $commentaires = null): array
{
    try {
        $result = $this->updateStatut($id, $statut, $commentaires); // <-- corrigé

        if ($result) {
            return ['success' => true, 'message' => "Statut de la demande modifié avec succès"];
        }

        return ['success' => false, 'message' => 'Erreur lors du changement de statut'];

    } catch (\Exception $e) {
        Log::error('Erreur lors du changement de statut', ['id' => $id, 'error' => $e->getMessage()]);
        return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
    }
}
}