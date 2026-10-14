<?php
// app/Repositories/DocumentEtudiantRepository.php
namespace App\Repositories;

use App\Models\DocumentEtudiant;
use App\Repositories\Interfaces\DocumentEtudiantRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Document;

class DocumentEtudiantRepository implements DocumentEtudiantRepositoryInterface
{
    protected $model;

    public function __construct(DocumentEtudiant $document)
    {
        $this->model = $document;
    }

    public function create(array $datas): DocumentEtudiant
    {
        try {
            //dd($datas['id_etudiant']);
            DB::beginTransaction();

            $liste = Document::getAllDocuments(1);

            if (!$liste->isEmpty()) {

                foreach ($liste as $documents) {


                    if (isset($datas[$documents->slug])  && !empty($datas[$documents->slug]) ) {


                        $image = upload( 'etudiant-document',  'pdf', $datas, $documents->slug);

                     $data =[
                         'chemin_fichier'=> $image,
                         'extension' => $documents->extension ?? 'pdf',
                         'type_document'=> $documents->name,
                         'etudiant_id'=> $datas['id_etudiant'],
                         'taille_octets' => getFileSizeFromUrl($image),
                         'id_campagne' =>  $datas['id_campagne'],

                     ];

                    }
                    //dd($data);
                    $document = DocumentEtudiant::create($data);


                }


            }
            // Gestion du fichier si présent






            DB::commit();



            return $document;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création du document', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    public function update(string $id, array $data): DocumentEtudiant
    {
        try {
            DB::beginTransaction();

            $document = $this->find($id);
            if (!$document) {
                throw new \Exception("Document non trouvé avec l'ID: {$id}");
            }

            // Gestion du nouveau fichier
            if (isset($data['fichier']) && $data['fichier'] instanceof \Illuminate\Http\UploadedFile) {
                // Supprimer l'ancien fichier
                if ($document->chemin_fichier && Storage::disk('public')->exists($document->chemin_fichier)) {
                    Storage::disk('public')->delete($document->chemin_fichier);
                }

                $fichier = $data['fichier'];
                $path = $fichier->store('documents/etudiants/' . $document->etudiant_code, 'public');

                $data['chemin_fichier'] = $path;
                $data['extension'] = $fichier->getClientOriginalExtension();
                $data['taille_octets'] = $fichier->getSize();

                unset($data['fichier']);
            }

            $data['updated_by'] = auth()->id() ?? $data['updated_by'] ?? null;
            $document->update($data);

            DB::commit();

            Log::info('Document mis à jour avec succès', ['id_document' => $id]);

            return $document->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour du document', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function find(string $id): ?DocumentEtudiant
    {
        return $this->model->with(['etudiant', 'documentPrecedent'])->where('id_document',$id)->first();
    }

    public function delete(int $id): bool
    {
        try {
            DB::beginTransaction();

            $document = $this->find($id);
            if (!$document) {
                throw new \Exception("Document non trouvé avec l'ID: {$id}");
            }

            // Supprimer le fichier physique
            if ($document->chemin_fichier && Storage::disk('public')->exists($document->chemin_fichier)) {
                Storage::disk('public')->delete($document->chemin_fichier);
            }

            $document->delete();

            DB::commit();

            Log::info('Document supprimé avec succès', ['id_document' => $id]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la suppression du document', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function findByEtudiant(int $etudiantId): Collection
    {
        return $this->model
            ->where('etudiant_code', $etudiantId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findByType(string $type): Collection
    {
        return $this->model
            ->where('type_document', $type)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findByStatut(string $statut): Collection
    {
        return $this->model
            ->where('statut', $statut)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function updateStatut(string $id, string $statut,string $message=null): bool
    {
        try {
            $data = [
                'statut' => $statut,
                'updated_by' => getInfoAdmin()->users->code
            ];

            if ($message !== null && $statut === 'rejete') {
                $data['motif_rejet'] = $message;
            }

            if ($statut === 'valide') {
                $data['date_validation'] = now();
                $data['valide_par'] = getInfoAdmin()->users->code ;
            }

            return $this->update($id, $data) ? true : false;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour du statut du document', [
                'id' => $id,
                'statut' => $statut,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function validerDocument(int $id, int $validateurId, int $score = null): bool
    {
        try {
            $data = [
                'statut' => 'valide',
                'date_validation' => now(),
                'valide_par' => $validateurId,
                'updated_by' => auth()->id()
            ];

            if ($score !== null) {
                $data['score_qualite'] = $score;
            }

            return $this->update($id, $data) ? true : false;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la validation du document', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getDocumentsEnAttente(): Collection
    {
        return $this->model
            ->where('statut', 'en_attente')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function getHistoriqueVersions(int $documentId): Collection
    {
        return $this->model
            ->where('document_precedent_id', $documentId)
            ->orWhere('id_document', $documentId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}