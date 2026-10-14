<?php
// app/Observers/AssignationChambreObserver.php

namespace App\Observers;

use App\Models\AssignationChambre;
use App\Http\Services\LoyerService;

class AssignationChambreObserver
{
    protected $loyerService;

    public function __construct(LoyerService $loyerService)
    {
        $this->loyerService = $loyerService;
    }

    /**
     * Handle the AssignationChambre "created" event.
     */
    public function created(AssignationChambre $assignation)
    {
        // Générer automatiquement le loyer quand une assignation est confirmée
        if ($assignation->statut === 'confirmee') {
            $this->loyerService->genererLoyerPourAssignation($assignation);
        }
    }

    /**
     * Handle the AssignationChambre "updated" event.
     */
    public function updated(AssignationChambre $assignation)
    {
        // Si le statut passe à "confirmee", générer le loyer
        if ($assignation->isDirty('statut') && $assignation->statut === 'confirmee') {
            $this->loyerService->genererLoyerPourAssignation($assignation);
        }
    }
}