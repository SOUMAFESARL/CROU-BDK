<?php

namespace App\Http\Controllers\admin\immobilier\parametrage\regles_campagne;

use App\Http\Controllers\Controller;
use App\Http\Services\Eligibilite\CriteresCampagneVue;
use App\Services\AdminAccess;
use App\Services\ParametrageHebergement;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Page « Critères de la campagne » : les critères se paramètrent sur une seule page, en deux niveaux
 * (admission puis réadmission) pour une campagne qui ouvre les deux volets, un seul niveau sinon.
 * Chaque niveau se définit, s'applique et se consulte (version en vigueur, historique) en une fois.
 * `?volet=` choisit le niveau affiché à l'ouverture (ex. après un enregistrement).
 */
class ReglesCampagnesController extends Controller
{
    public function index(Request $request, ParametrageHebergement $parametrage, AdminAccess $access, CriteresCampagneVue $vue)
    {
        $campagnes = $parametrage->campagnes();
        $campagne = $parametrage->campagneChoisie($request, $campagnes);
        $admin = $request->user('admin');
        $niveaux = $campagne && $vue->visible($admin) ? $vue->niveaux($campagne, $admin) : null;
        $demande = (string) $request->query('volet', '');

        return Inertia::render('Admin/Hebergement/Parametrage/Criteres', [
            'campagnes' => $parametrage->options($campagnes),
            'campagneId' => $campagne ? (string) $campagne->getKey() : null,
            // Contexte de la campagne (calendrier, pièces, résidences) affiché avec les critères.
            'contexte' => $campagne ? $parametrage->regles($campagne) : null,
            // Un niveau par volet ouvert, dans l'ordre admission puis réadmission ; null sans droit de consulter.
            'niveaux' => $niveaux,
            'niveau' => $niveaux ? (isset($niveaux[$demande]) ? $demande : array_key_first($niveaux)) : null,
            'urls' => [
                'index' => route(CriteresCampagneVue::PAGE),
                'editCampagne' => $campagne && $access->allows($admin, 'admin.hebergement.campagnes_admission.edit_campagne')
                    ? route('admin.hebergement.campagnes_admission.edit_campagne', $campagne->getKey()) : null,
            ],
        ]);
    }
}
