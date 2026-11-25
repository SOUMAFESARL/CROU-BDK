<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Http\Services\Eligibilite\ResultatsAdmission;
use App\Http\Services\ReclamationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Page publique « Résultats d'admission » : résultat de la demande, après la publication des listes définitives. */
class ResultatPublicController extends Controller
{
    public function __construct(private ResultatsAdmission $resultats) {}

    public function create(): Response
    {
        return $this->page();
    }

    public function show(Request $request): Response
    {
        $donnees = $request->validate(['numero' => ['required', 'string', 'max:50']], [
            'numero.required' => 'Saisissez votre numéro de table au bac ou votre matricule.',
            'numero.max' => 'Le numéro ne doit pas dépasser 50 caractères.',
        ]);

        return $this->page($this->resultats->consulter($donnees['numero']));
    }

    private function page(?array $resultat = null): Response
    {
        $campagnes = $this->resultats->campagnesConsultables();

        return Inertia::render('Etudiant/Auth/Resultat', [
            // Campagnes dont les résultats sont publiés et visibles ; null : aucune (page masquée).
            'campagne' => $campagnes->map(fn ($campagne) => collect([$campagne->libelle, $campagne->annee_campagne])->filter()->implode(' · '))->implode(', ') ?: null,
            'resultat' => $resultat,
            'liens' => [
                'consulter' => route('etudiant.resultats.show'),
                'formulaire' => route('etudiant.resultats'),
                'connexion' => route('etudiant.login'),
                // Réclamation d'un étudiant non admis, pendant une période de réclamation ouverte.
                'reclamation' => $resultat && $resultat['type'] !== ResultatsAdmission::ADMIS && app(ReclamationService::class)->periodeOuverte()
                    ? route('etudiant.reclamation') : null,
            ],
            'logoUrl' => systemLogo('admin/assets/images/logos/crou_bdk.jpeg'),
            'backgroundUrl' => asset('files/assets/images/auth/crou_bondoukou.jpg'),
            'year' => now()->year,
        ]);
    }
}
