<?php

namespace App\Repositories;

use App\Models\AssignationChambre;
use App\Models\Etudiant;
use App\Repositories\Interfaces\AssignationChambreRepositoryInterface;

class AssignationChambreRepository implements AssignationChambreRepositoryInterface
{
    /**
     * Créer une assignation
     */
    public function create(array $data): AssignationChambre
    {
        return AssignationChambre::create($data);
    }

    /**
     * Obtenir une assignation par ID
     */
    public function getById($id): ?AssignationChambre
    {
        return AssignationChambre::find($id);
    }

    /**
     * Obtenir l'assignation actuelle d'un étudiant
     */
    public function getByEtudiant($etudiantId, $campagneId): ?AssignationChambre
    {
        return AssignationChambre::where('etudiant_id', $etudiantId)
            ->where('campagne_id', $campagneId)
            ->where('statut', '!=', 'annulee')
            ->latest('created_at')
            ->first();
    }

    /**
     * Obtenir les assignations d'une campagne
     */
    public function getByCampagne($campagneId)
    {
      //  dd($campagneId);
        return AssignationChambre::withDefaultRelations()->where('campagne_id', $campagneId)
            ->where('statut', '!=', 'annulee')
            ->with(['etudiant', 'chambre', 'dossier'])
            ->get();
    }

    /**
     * Obtenir les assignations d'une chambre
     */
    public function getByChambre($chambreId)
    {
        return AssignationChambre::where('chambre_id', $chambreId)
            ->actives()
            ->with('etudiant')
            ->get();
    }

    /**
     * Mettre à jour une assignation
     */
    public function update($id, array $data): AssignationChambre
    {
        $assignation = $this->getById($id);
        if ($assignation) {
            $assignation->update($data);
        }
        return $assignation;
    }

    /**
     * Supprimer une assignation
     */
    public function delete($id): bool
    {
        $assignation = $this->getById($id);
        return $assignation ? $assignation->delete() : false;
    }

    /**
     * Vérifier si un étudiant a une chambre assignée
     */
    public function hasActiveAssignment($etudiantId, $campagneId): bool
    {
        return AssignationChambre::where('etudiant_id', $etudiantId)
            ->where('campagne_id', $campagneId)
            ->mobilisantPlace()
            ->exists();
    }

    /**
     * Compter les assignations confirmées
     */
    public function countConfirmed($campagneId): int
    {
        return AssignationChambre::where('campagne_id', $campagneId)
            ->where('statut', 'confirmee')
            ->count();
    }

    /**
     * Obtenir les étudiants sans chambre
     */
    public function getStudentsSansChambres($campagneId)
    {
        return Etudiant::sansChambre($campagneId)
            ->with(['documents', 'demandes.niveauEtudiantActuel'])
            ->whereDoesntHave('assignationsChambre', fn ($q) => $q->mobilisantPlace())
            //->where('is_validation', true)
            ->whereHas('demandes', function ($q) use ($campagneId) {
                $q->where('campagne_id', $campagneId)
                    ->where('etat_dossier', 'eligible');
            })
            ->get();
    }
}