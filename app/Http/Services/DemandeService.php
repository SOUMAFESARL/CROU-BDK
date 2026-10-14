<?php
// app/Services/DemandeService.php
namespace App\Http\Services;

use App\Repositories\Interfaces\DemandeRepositoryInterface;
use App\Http\Requests\DemandeRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DemandeService
{
    protected $demandeRepository;

    public function __construct(DemandeRepositoryInterface $demandeRepository)
    {
        $this->demandeRepository = $demandeRepository;
    }

    public function creerDemande(DemandeRequest $request): array
    {
        try {

            DB::beginTransaction();

            $validatedData = $request->all();

            $demande = $this->demandeRepository->create($validatedData);

            DB::commit();



            return [
                'success' => true,
                'message' => 'Demande créée avec succès',
                'data' => $demande
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erreur lors de la création de la demande', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la création: ' . \App\Support\UserFacingErrors::caught($e),
                'error' => \App\Support\UserFacingErrors::caught($e)
            ];
        }
    }

    public function mettreAJourDemande(int $id, DemandeRequest $request): array
    {
        try {
            $demande = $this->demandeRepository->find($id);

            if (!$demande) {
                return [
                    'success' => false,
                    'message' => 'Demande non trouvée'
                ];
            }

            $validatedData = $request->validated();
            $demande = $this->demandeRepository->update($id, $validatedData);

            return [
                'success' => true,
                'message' => 'Demande mise à jour avec succès',
                'data' => $demande
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour de la demande', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . \App\Support\UserFacingErrors::caught($e)
            ];
        }
    }

    public function changerStatut(string $id, string $statut, ?string $commentaires = null): array
    {
        try {

            $result = $this->demandeRepository->updateStatut($id, $statut, $commentaires);

            if ($result) {
                return [
                    'success' => true,
                    'message' => "Statut de la demande modifié avec succès"
                ];
            }

            return [
                'success' => false,
                'message' => 'Erreur lors du changement de statut'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors du changement de statut', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur: ' . \App\Support\UserFacingErrors::caught($e)
            ];
        }
    }

    public function getDemandesByEtudiant(int $etudiantId): array
    {
        try {
            $demandes = $this->demandeRepository->findByEtudiant($etudiantId);

            return [
                'success' => true,
                'data' => $demandes
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des demandes', [
                'etudiant_id' => $etudiantId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur: ' . \App\Support\UserFacingErrors::caught($e)
            ];
        }
    }


}