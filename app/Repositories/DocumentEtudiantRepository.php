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
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class DocumentEtudiantRepository implements DocumentEtudiantRepositoryInterface
{
    /** Formats acceptés pour les pièces d’un dossier. */
    private const EXTENSIONS = ['pdf', 'jpg', 'png'];

    protected $model;

    public function __construct(DocumentEtudiant $document)
    {
        $this->model = $document;
    }

    /**
     * Enregistre les pièces jointes reçues (clé = slug du document) et crée une ligne par fichier.
     * Un fichier illisible ou d'un format refusé fait échouer l'enregistrement : aucune pièce n'est perdue en silence.
     */
    public function create(array $datas): ?DocumentEtudiant
    {
        $pieces = $this->stockerPieces($datas);

        try {
            return $this->enregistrerPieces($pieces, $datas['id_etudiant'], $datas['id_campagne'] ?? null);
        } catch (\Throwable $e) {
            self::supprimerFichiers($pieces);
            throw $e;
        }
    }

    /**
     * Copie sur disque les fichiers reçus pour des documents connus, dans le dossier public historique
     * admin/assets/images/etudiant-document (servi tel quel, que la racine web soit public/ ou la racine du projet).
     * Si un fichier est refusé, ceux déjà copiés sont supprimés et une erreur est levée sous le slug du document.
     *
     * @return array<int, array{fichier: string, chemin_fichier: string, extension: string, taille_octets: int, type_document: string}>
     */
    public function stockerPieces(array $datas): array
    {
        $fichiers = collect($datas)->filter(fn ($valeur) => $valeur instanceof UploadedFile);
        if ($fichiers->isEmpty()) {
            return [];
        }

        $types = Document::whereIn('slug', $fichiers->keys()->all())->get()->keyBy('slug');
        $dossier = self::dossierPieces();
        $pieces = [];

        try {
            foreach ($fichiers as $slug => $fichier) {
                $type = $types->get($slug);
                if (! $type) {
                    continue;
                }
                // Extension déduite du contenu, jamais du nom envoyé par le client.
                $extension = $fichier->isValid() ? strtolower((string) $fichier->guessExtension()) : '';
                $extension = $extension === 'jpeg' ? 'jpg' : $extension;
                if (! in_array($extension, self::EXTENSIONS, true)) {
                    throw ValidationException::withMessages([
                        $slug => "Le document {$type->name} n’a pas été reçu correctement (PDF, JPG ou PNG attendu).",
                    ]);
                }

                $taille = (int) $fichier->getSize();
                $nom = uniqid('', true).'.'.$extension;
                try {
                    $fichier->move($dossier, $nom);
                } catch (\Throwable $e) {
                    report($e);
                    throw ValidationException::withMessages([
                        $slug => "Le document {$type->name} n’a pas pu être enregistré. Réessayez.",
                    ]);
                }

                $pieces[] = [
                    'fichier' => $dossier.'/'.$nom,
                    'chemin_fichier' => url('admin/assets/images/etudiant-document/'.$nom),
                    'extension' => $extension,
                    'taille_octets' => $taille,
                    'type_document' => $type->name,
                ];
            }
        } catch (\Throwable $e) {
            self::supprimerFichiers($pieces);
            throw $e;
        }

        return $pieces;
    }

    /** Crée une ligne document_etudiant par pièce déjà stockée ; retourne la dernière. */
    public function enregistrerPieces(array $pieces, string $etudiantId, ?string $campagneId): ?DocumentEtudiant
    {
        return DB::transaction(function () use ($pieces, $etudiantId, $campagneId) {
            $document = null;
            foreach ($pieces as $piece) {
                $document = DocumentEtudiant::create([
                    ...collect($piece)->except('fichier')->all(),
                    'etudiant_id' => $etudiantId,
                    'id_campagne' => $campagneId,
                ]);
            }

            return $document;
        });
    }

    /** Supprime du disque des pièces stockées dont l'enregistrement a échoué. */
    public static function supprimerFichiers(array $pieces): void
    {
        foreach ($pieces as $piece) {
            if (is_file($piece['fichier'])) {
                @unlink($piece['fichier']);
            }
        }
    }

    /** Dossier physique des pièces : celui du contrôleur frontal, comme l'ancien helper upload(). */
    public static function dossierPieces(): string
    {
        return rtrim(config('student_documents.upload_root') ?: getcwd(), '/\\').'/admin/assets/images/etudiant-document';
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