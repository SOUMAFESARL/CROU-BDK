<?php

namespace App\Repositories\Interfaces;

interface AssignationChambreRepositoryInterface
{
    /**
     * Créer une assignation
     */
    public function create(array $data);

    /**
     * Obtenir une assignation par ID
     */
    public function getById($id);

    /**
     * Obtenir l'assignation actuelle d'un étudiant
     */
    public function getByEtudiant($etudiantId, $campagneId);

    /**
     * Obtenir les assignations d'une campagne
     */
    public function getByCampagne($campagneId);

    /**
     * Obtenir les assignations d'une chambre
     */
    public function getByChambre($chambreId);

    /**
     * Mettre à jour une assignation
     */
    public function update($id, array $data);

    /**
     * Supprimer une assignation
     */
    public function delete($id);

    /**
     * Vérifier si un étudiant a une chambre assignée
     */
    public function hasActiveAssignment($etudiantId, $campagneId);

    /**
     * Compter les assignations confirmées
     */
    public function countConfirmed($campagneId);

    /**
     * Obtenir les étudiants sans chambre
     */
    public function getStudentsSansChambres($campagneId);
}