<?php

namespace App\Http\Controllers;

use App\Http\Services\FicheDemandeService;
use App\Models\DemandeEtudiant;
use Illuminate\Http\Request;

/** Téléchargement de la fiche de demande d'hébergement (PDF), web uniquement. */
class FicheDemandeController extends Controller
{
    public function __construct(private FicheDemandeService $fiche) {}

    /** Fin du formulaire public : lien signé temporaire (middleware `signed:relative`), sans connexion. */
    public function publique(Request $request, string $demande)
    {
        return $this->telecharger(DemandeEtudiant::findOrFail($demande), $request->boolean('restreinte'));
    }

    /** Espace étudiant : uniquement les demandes de l'étudiant connecté. */
    public function etudiant(string $demande)
    {
        return $this->telecharger(DemandeEtudiant::where('etudiant_id', auth('etudiant')->id())->findOrFail($demande));
    }

    private function telecharger(DemandeEtudiant $demande, bool $restreinte = false)
    {
        abort_unless($this->fiche->disponible($demande), 404);

        return $this->fiche->pdf($demande, $restreinte)
            ->download($this->fiche->nomFichier($demande))
            ->header('Cache-Control', 'private, no-store');
    }
}
