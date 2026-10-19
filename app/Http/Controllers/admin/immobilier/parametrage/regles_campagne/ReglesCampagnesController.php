<?php

namespace App\Http\Controllers\admin\immobilier\parametrage\regles_campagne;

use App\Http\Controllers\Controller;
use App\Services\AdminAccess;
use App\Services\ParametrageHebergement;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReglesCampagnesController extends Controller
{
    /** Critères d'éligibilité d'une campagne (saisis dans le formulaire de la campagne). */
    public function index(Request $request, ParametrageHebergement $parametrage, AdminAccess $access)
    {
        $campagnes = $parametrage->campagnes();
        $campagne = $parametrage->campagneChoisie($request, $campagnes);
        $admin = $request->user('admin');

        return Inertia::render('Admin/Hebergement/Parametrage/Regles', [
            'campagnes' => $parametrage->options($campagnes),
            'campagneId' => $campagne ? (string) $campagne->getKey() : null,
            'regles' => $campagne ? $parametrage->regles($campagne) : null,
            'urls' => [
                'index' => route('admin.hebergement.parametrage-herb.regles-campagne.index'),
                'editCampagne' => $campagne && $access->allows($admin, 'admin.hebergement.campagnes_admission.edit_campagne')
                    ? route('admin.hebergement.campagnes_admission.edit_campagne', $campagne->getKey()) : null,
                'scoring' => $access->allows($admin, 'admin.hebergement.parametrage-herb.scoring-campagne.index') ? route('admin.hebergement.parametrage-herb.scoring-campagne.index') : null,
            ],
        ]);
    }
}
