<?php

namespace App\Services;

use App\Models\Etudiant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Pagination\AbstractPaginator;

/** Apply the same field visibility to HTML, embedded JSON and AJAX responses. */
class AdminViewData
{
    public function filter(mixed $value, User $actor, ?\SplObjectStorage $seen = null): mixed
    {
        $seen ??= new \SplObjectStorage;
        if ($value instanceof Model) {
            if (isset($seen[$value])) {
                return $seen[$value];
            }
            $copy = clone $value;
            $copy->makeHidden(['password', 'remember_token', 'two_factor_secret', 'mail_password', 'sms_api_key', 'api_key_secret']);
            $seen[$value] = $copy;
            if ($copy instanceof Etudiant) {
                if (!$actor->hasPermission('hebergement.admissions.personal')) {
                    $this->keep($copy, ['id_etudiant', 'name', 'matricule', 'statut_inscription', 'statut_disciplinaire', 'created_at', 'updated_at', 'is_validation', 'is_rejet', 'demandes_count']);
                    foreach (['region', 'ville', 'civilite', 'typePiece', 'groupeSanguin'] as $relation) {
                        $copy->setRelation($relation, null);
                    }
                }
                foreach ([
                    'documents' => 'hebergement.admissions.documents', 'demandes' => 'hebergement.admissions.history',
                    'maintenance' => 'hebergement.maintenance.view', 'edl_entree' => 'hebergement.departs.view', 'edl_sortie' => 'hebergement.departs.view',
                ] as $relation => $permission) {
                    if (!$actor->hasPermission($permission)) {
                        $copy->setRelation($relation, collect());
                    }
                }
                if (!$actor->hasPermission('hebergement.discipline.view')) {
                    $copy->offsetUnset('statut_disciplinaire');
                    $copy->makeHidden('statut_disciplinaire');
                }
                if (!$actor->hasPermission('hebergement.admissions.history')) {
                    $copy->offsetUnset('demandes_count');
                }
            } elseif ($copy instanceof User && !$actor->hasPermission('utilisateurs.personal')) {
                $this->keep($copy, ['id_user', 'name', 'matricule', 'role_id', 'type_utilisateur_id', 'campus_id', 'statut', 'created_at', 'updated_at', 'created_by', 'updated_by']);
                foreach (['genre', 'region', 'ville'] as $relation) {
                    $copy->setRelation($relation, null);
                }
            }
            foreach ($copy->getRelations() as $name => $relation) {
                $copy->setRelation($name, $this->filter($relation, $actor, $seen));
            }
            return $copy;
        }
        if ($value instanceof AbstractPaginator) {
            $copy = clone $value;
            return $copy->setCollection($this->filter($copy->getCollection(), $actor, $seen));
        }
        if ($value instanceof Collection) {
            return $value->map(fn ($item) => $this->filter($item, $actor, $seen));
        }
        if (is_array($value)) {
            $value = array_diff_key($value, array_flip(['password', 'remember_token', 'two_factor_secret', 'mail_password', 'sms_api_key', 'api_key_secret']));
            if (isset($value['id_user']) && array_key_exists('name', $value) && !$actor->hasPermission('utilisateurs.personal')) {
                $value = array_intersect_key($value, array_flip(['id_user', 'name', 'matricule', 'role_id', 'type_utilisateur_id', 'campus_id', 'statut', 'role', 'type_utilisateur', 'campus', 'created_at', 'updated_at']));
            }
            if (isset($value['id_etudiant']) && array_key_exists('name', $value)) {
                if (!$actor->hasPermission('hebergement.admissions.personal')) {
                    $value = array_intersect_key($value, array_flip(['id_etudiant', 'name', 'matricule', 'statut_inscription', 'created_at', 'updated_at', 'is_validation', 'is_rejet', 'demandes_count', 'derniere_demande', 'assignations_chambre', 'assignation_active', 'documents', 'demandes']));
                }
                foreach ([
                    'documents' => 'hebergement.admissions.documents', 'demandes' => 'hebergement.admissions.history',
                    'demandes_count' => 'hebergement.admissions.history', 'statut_disciplinaire' => 'hebergement.discipline.view',
                ] as $field => $permission) {
                    if (!$actor->hasPermission($permission)) {
                        unset($value[$field]);
                    }
                }
            }
            return array_map(fn ($item) => $this->filter($item, $actor, $seen), $value);
        }
        return $value;
    }

    private function keep(Model $model, array $fields): void
    {
        $model->setRawAttributes(array_intersect_key($model->getAttributes(), array_flip($fields)));
    }
}
