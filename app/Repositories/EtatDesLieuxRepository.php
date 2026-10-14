<?php

namespace App\Repositories;

use App\Repositories\Interfaces\EtatDesLieuxRepositoryInterface;
use App\Models\EdlElement;
use App\Models\EdlImage;
use App\Models\EdlPiece;
use App\Models\EtatDesLieux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EtatDesLieuxRepository implements EtatDesLieuxRepositoryInterface
{
    /* ------------------------------------------------------------------ */
    /*  Lecture                                                             */
    /* ------------------------------------------------------------------ */

    public function all(array $filters = []): Collection
    {
        $query = EtatDesLieux::withDefaultRelations();

        if (!empty($filters['type_etat'])) {
            $query->where('type_etat', $filters['type_etat']);
        }
        if (!empty($filters['chambre_id'])) {
            $query->where('id_chambre', $filters['chambre_id']);
        }
        if (!empty($filters['etudiant_id'])) {
            $query->where('id_etudiant', $filters['etudiant_id']);
        }
        if (!empty($filters['cite_id'])) {
            $query->where('id_cite', $filters['cite_id']);
        }

        return $query->latest('date_etat')->get();
    }


    public function findById(string $id): ?EtatDesLieux
    {
        return EtatDesLieux::with([
            'pieces.elements',
            'pieces.images',
            'cite',
            'batiment',
            'chambre',
            'etudiant',
        ])->find($id);
    }

    public function findEntree(string $idChambre, string $idEtudiant): ?EtatDesLieux
    {
        return EtatDesLieux::with('pieces.elements', 'pieces.images')
            ->where('id_chambre', $idChambre)
            ->where('id_etudiant', $idEtudiant)
            ->where('type_etat', 'entree')
            ->latest('date_etat')
            ->first();
    }

    public function findSortie(string $idChambre, string $idEtudiant): ?EtatDesLieux
    {
        return EtatDesLieux::with('pieces.elements', 'pieces.images')
            ->where('id_chambre', $idChambre)
            ->where('id_etudiant', $idEtudiant)
            ->where('type_etat', 'sortie')
            ->latest('date_etat')
            ->first();
    }

    public function historiqueEtudiant(string $idEtudiant): Collection
    {
        return EtatDesLieux::with(['chambre', 'cite', 'batiment'])
            ->where('id_etudiant', $idEtudiant)
            ->latest('date_etat')
            ->get();
    }

    public function parChambre(string $idChambre): Collection
    {
        return EtatDesLieux::with(['etudiant'])
            ->where('id_chambre', $idChambre)
            ->latest('date_etat')
            ->get();
    }

    /* ------------------------------------------------------------------ */
    /*  Écriture                                                            */
    /* ------------------------------------------------------------------ */

    public function create(array $data): EtatDesLieux
    {
        $data['pieces'] = app(\App\Http\Services\EdlPhotoService::class)->enregistrer($data['pieces'] ?? []);
        return DB::transaction(function () use ($data) {


            // 1. En-tête EDL
            $edl = EtatDesLieux::create([
                'created_by'           => $data['created_by'] ?? $this->usersId(),
                'id_cite'              => $data['id_cite'],
                'id_batiment'          => $data['id_batiment'],
                'id_chambre'           => $data['id_chambre'],
                'id_etudiant'          => $data['id_etudiant'],
                'type_etat'            => $data['type_etat'],
                'date_etat'            => $data['date_etat'] ?? now(),
                'nbre_cle_recus'       => $data['nbre_cle_recus'] ?? 1,
                'observation_generale' => $data['observation_generale'] ?? null,
                'signature_crou'       => $data['signature_crou'] ?? null,
                'signature_etudiant'   => $data['signature_etudiant'] ?? null,

            ]);

            // 2. Pièces, éléments, photos
            foreach ($data['pieces'] ?? [] as $ordre => $pieceData) {
                $piece = EdlPiece::create([
                    'id_etat_des_lieux' => $edl->etat_des_lieux_id,
                    'name_piece'        => $pieceData['name_piece'] ,
                    'ordre'             => $pieceData['ordre'] ?? ($ordre + 1),

                ]);

                foreach ($pieceData['elements'] ?? [] as $eOrdre => $el) {
                    EdlElement::create([
                        'id_edl_piece'  => $piece->edl_piece_id,
                        'name_element'  => $el['name_element'] ,
                        'etat'          => $el['etat'] ?? 'bon',
                        'observation'   => $el['observation'] ?? null,
                        'photo'         => $el['photo'] ?? null,
                        'ordre'         => $el['ordre'] ?? ($eOrdre + 1),
                    //    'created_by'    => $userId,
                      //  'updated_by'    => $userId,
                    ]);
                }

                foreach ($pieceData['photos'] ?? [] as $chemin) {
                    EdlImage::create([
                        'id_edl_piece'  => $piece->edl_piece_id,
                        'chemin_fichier' => $chemin,
                     
                    ]);
                }
            }

            return $edl->load('pieces.elements', 'pieces.images');
        });
    }

    public function update(string $id, array $data): EtatDesLieux
    {
        return DB::transaction(function () use ($id, $data) {
            $edl = EtatDesLieux::findOrFail($id);

            if (isset($data['pieces'])) {
                $existing = $edl->pieces()->with('images', 'elements')->get()->flatMap(fn ($piece) => $piece->images->pluck('chemin_fichier')->merge($piece->elements->pluck('photo')->filter()))->all();
                $data['pieces'] = app(\App\Http\Services\EdlPhotoService::class)->enregistrer($data['pieces'], $existing);
            }

            $edl->update(array_merge(
                array_intersect_key($data, array_flip([
                    'date_etat', 'nbre_cle_recus', 'observation_generale',
                    'signature_crou', 'signature_etudiant',
                ])),
                ['updated_by' => $this->usersId()]
            ));

            // Mise à jour des pièces / éléments si fournis
            if (!empty($data['pieces'])) {
                // Suppression et recréation (stratégie simple pour le MVP)
                foreach ($edl->pieces as $piece) {
                    $piece->elements()->delete();
                    $piece->images()->delete();
                }
                $edl->pieces()->delete();

                // Recréation via le même chemin que create()
                foreach ($data['pieces'] as $ordre => $pieceData) {
                    $piece = EdlPiece::create([
                        'id_etat_des_lieux' => $edl->etat_des_lieux_id,
                        'name_piece'        => $pieceData['name_piece'],
                        'ordre'             => $pieceData['ordre'] ?? ($ordre + 1),

                    ]);
                    foreach ($pieceData['elements'] ?? [] as $eOrdre => $el) {
                        EdlElement::create([
                            'id_edl_piece' => $piece->edl_piece_id,
                            'name_element' => $el['name_element'],
                            'etat'         => $el['etat'] ?? 'bon',
                            'observation'  => $el['observation'] ?? null,
                            'photo'        => $el['photo'] ?? null,
                            'ordre'        => $el['ordre'] ?? ($eOrdre + 1),

                        ]);
                    }
                    foreach ($pieceData['photos'] ?? [] as $chemin) {
                        EdlImage::create([
                            'id_edl_piece'   => $piece->edl_piece_id,
                            'chemin_fichier'  => $chemin,

                        ]);
                    }
                }
            }

            return $edl->fresh('pieces.elements', 'pieces.images');
        });
    }

    public function delete(string $id): bool
    {
        $edl = EtatDesLieux::findOrFail($id);

        DB::transaction(function () use ($edl) {
            foreach ($edl->pieces as $piece) {
                $piece->elements()->delete();
                $piece->images()->delete();
            }
            $edl->pieces()->delete();
            $edl->delete();
        });

        return true;
    }

    private function usersId(): string
    {
        return (string) (request()->attributes->get('student')?->getKey() ?? Auth::guard('etudiant')->id() ?? getInfoAdmin()->users->id_user);
    }
}
