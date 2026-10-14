<?php

namespace App\Services;

use App\Models\Etudiant;
use App\Models\User;

class AdminStudentView
{
    public function find(string $id, User $user): Etudiant
    {
        $access = app(AdminViewAccess::class);
        $query = Etudiant::query()->with(['derniereDemande.niveauEtudiantActuel', 'derniereDemande.niveauEtudiantPrecedent']);
        if ($access->allows($user, 'admissions.personal')) {
            $query->with(['region', 'ville', 'civilite', 'typePiece', 'groupeSanguin']);
        } else {
            $columns = ['id_etudiant', 'name', 'matricule', 'statut_inscription'];
            $query->select($columns);
        }
        if ($access->allows($user, 'admissions.documents')) {
            $query->with('documents');
        }
        if ($access->allows($user, 'admissions.history')) {
            $query->with(['demandes.niveauEtudiantActuel', 'demandes.niveauEtudiantPrecedent']);
        }
        $student = $query->findOrFail($id);
        // Prevent accidental lazy loading from a partial or a JSON serializer.
        foreach (['documents' => 'admissions.documents', 'demandes' => 'admissions.history'] as $relation => $block) {
            if (!$access->allows($user, $block)) {
                $student->setRelation($relation, collect());
            }
        }

        return $student;
    }
}
