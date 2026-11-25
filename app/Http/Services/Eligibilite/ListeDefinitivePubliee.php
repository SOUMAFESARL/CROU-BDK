<?php

namespace App\Http\Services\Eligibilite;

use App\Models\Etudiant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Étudiants de la liste « Admis à loger » (Résidents & contrats, demandes validées de la liste définitive publiée) qui
 * n'ont pas encore de chambre : les seuls proposés par « Nouveau paiement ».
 *
 * Leur statut d'inscription `en_verification` est tenu par `DossierWorkflow` : il est donné par la publication de la
 * liste définitive (`eligible → confirme`, `ResultatsAdmission`).
 */
class ListeDefinitivePubliee
{
    /** Liste publiée (`publication_liste_definitive.liste`) : les demandes validées, pas la file d'attente. */
    public const LISTE = 'validees';

    /** Statut d'inscription des étudiants admis à loger. */
    public const STATUT_INSCRIPTION = 'en_verification';

    /** Limite une requête d'étudiants aux admis à loger, sans affectation confirmée. */
    public function candidats(Builder $query): Builder
    {
        return $query->where(DB::raw('LOWER(statut_inscription)'), self::STATUT_INSCRIPTION)
            ->whereDoesntHave('assignationActive')
            ->whereHas('derniereDemande.campagne');
    }

    /** L'étudiant figure-t-il dans la liste « Admis à loger », sans affectation confirmée ? */
    public function figure(string $etudiantId): bool
    {
        return $this->candidats(Etudiant::query())->whereKey($etudiantId)->exists();
    }
}
