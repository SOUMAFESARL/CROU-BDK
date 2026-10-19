<?php

namespace App\Http\Controllers\admin\immobilier\parametrage\batiments_campagne;

use App\Http\Controllers\Controller;
use App\Services\AdminAccess;
use App\Services\ParametrageHebergement;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BatimentsCampagneController extends Controller
{
    /** Résidences et bâtiments ouverts à une campagne (choisis dans le formulaire de la campagne). */
    public function index(Request $request, ParametrageHebergement $parametrage, AdminAccess $access)
    {
        $campagnes = $parametrage->campagnes();
        $campagne = $parametrage->campagneChoisie($request, $campagnes);
        $admin = $request->user('admin');

        return Inertia::render('Admin/Hebergement/Parametrage/Batiments', [
            'campagnes' => $parametrage->options($campagnes),
            'campagneId' => $campagne ? (string) $campagne->getKey() : null,
            'batiments' => $campagne ? $parametrage->batiments($campagne) : null,
            'urls' => [
                'index' => route('admin.hebergement.parametrage-herb.batiments-campagne.index'),
                'editCampagne' => $campagne && $access->allows($admin, 'admin.hebergement.campagnes_admission.edit_campagne')
                    ? route('admin.hebergement.campagnes_admission.edit_campagne', $campagne->getKey()) : null,
                'parc' => $access->allows($admin, 'admin.hebergement.parc-immobilier.index') ? route('admin.hebergement.parc-immobilier.index') : null,
            ],
        ]);
    }
}
