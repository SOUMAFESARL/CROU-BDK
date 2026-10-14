<?php

namespace App\Http\Services;

use App\Repositories\Interfaces\DocumentEtudiantRepositoryInterface;
use App\Http\Requests\DocumentEtudiantRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Document;

class DocumentEtudiantService
{
    protected $documentRepository;

    public function __construct(DocumentEtudiantRepositoryInterface $documentRepository)
    {
        $this->documentRepository = $documentRepository;
    }

    public function uploadDocument(DocumentEtudiantRequest $request): array
    {
        try {
           // dd('service document creation');
            DB::beginTransaction();

            $validatedData = $request->all();

            $document = Document::getAllDocuments(1);

            if (!$document->isEmpty()) {
                foreach ($document as $documents) {
                    if ($request->hasFile($documents->slug)) {
                        $validatedData[$documents->slug] = $request->file($documents->slug);

                    }
                }
            }
            // Ajouter le fichier à la data


            $document = $this->documentRepository->create($validatedData);

            DB::commit();



            return [
                'success' => true,
                'message' => 'Document uploadé avec succès',
                'data' => $document
            ];

        } catch (\Exception $e) {
            DB::rollBack();


            return [
                'success' => false,
                'message' => 'Erreur lors de l\'upload: ' . \App\Support\UserFacingErrors::caught($e)
            ];
        }
    }

    public function mettreAJourDocument(int $id, DocumentEtudiantRequest $request): array
    {
        try {
            $document = $this->documentRepository->find($id);

            if (!$document) {
                return [
                    'success' => false,
                    'message' => 'Document non trouvé'
                ];
            }

            $validatedData = $request->validated();

            if ($request->hasFile('fichier')) {
                $validatedData['fichier'] = $request->file('fichier');
            }

            $document = $this->documentRepository->update($id, $validatedData);

            return [
                'success' => true,
                'message' => 'Document mis à jour avec succès',
                'data' => $document
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour du document', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur: ' . \App\Support\UserFacingErrors::caught($e)
            ];
        }
    }

    public function validerDocument(int $id, int $validateurId, ?int $score = null): array
    {
        try {
            $result = $this->documentRepository->validerDocument($id, $validateurId, $score);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Document validé avec succès'
                ];
            }

            return [
                'success' => false,
                'message' => 'Erreur lors de la validation'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors de la validation du document', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur: ' . \App\Support\UserFacingErrors::caught($e)
            ];
        }
    }

    public function updateDocument(string $id, string $statut,string $message=null): array
    {
      //  dd("dsgjdfkmx");
        try {
            $result = $this->documentRepository->updateStatut($id, $statut,$message);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Document rejeté avec succès'
                ];
            }

            return [
                'success' => false,
                'message' => 'Erreur lors du rejet'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors du rejet du document', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur: ' . \App\Support\UserFacingErrors::caught($e)
            ];
        }
    }

    public function getDocumentsEtudiant(int $etudiantId): array
    {
        try {
            $documents = $this->documentRepository->findByEtudiant($etudiantId);

            return [
                'success' => true,
                'data' => $documents
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des documents', [
                'etudiant_id' => $etudiantId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur: ' . \App\Support\UserFacingErrors::caught($e)
            ];
        }
    }

    public function telechargerDocument(int $id): array
    {
        try {
            $document = $this->documentRepository->find($id);

            if (!$document || !$document->chemin_fichier) {
                return [
                    'success' => false,
                    'message' => 'Document non trouvé'
                ];
            }

            if (!Storage::disk('public')->exists($document->chemin_fichier)) {
                return [
                    'success' => false,
                    'message' => 'Fichier non trouvé sur le serveur'
                ];
            }

            return [
                'success' => true,
                'file' => Storage::disk('public')->path($document->chemin_fichier),
                'name' => basename($document->chemin_fichier)
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors du téléchargement du document', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur: ' . \App\Support\UserFacingErrors::caught($e)
            ];
        }
    }
}