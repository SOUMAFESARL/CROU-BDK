<?php

namespace App\Http\Controllers\admin\immobilier\parametrage\quotas_campagne;

use App\Http\Controllers\Controller;
use App\Services\AdminAccess;
use App\Services\ParametrageHebergement;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QuotasCampagneController extends Controller
{
    /** Capacités des résidences ouvertes à une campagne et répartition des demandes (aucun quota n'est encore enregistré). */
    public function index(Request $request, ParametrageHebergement $parametrage, AdminAccess $access)
    {
        $campagnes = $parametrage->campagnes();
        $campagne = $parametrage->campagneChoisie($request, $campagnes);
        $admin = $request->user('admin');

        return Inertia::render('Admin/Hebergement/Parametrage/Quotas', [
            'campagnes' => $parametrage->options($campagnes),
            'campagneId' => $campagne ? (string) $campagne->getKey() : null,
            'quotas' => $campagne ? $parametrage->quotas($campagne) : null,
            'urls' => [
                'index' => route('admin.hebergement.parametrage-herb.quotas-campagne.index'),
                'editCampagne' => $campagne && $access->allows($admin, 'admin.hebergement.campagnes_admission.edit_campagne')
                    ? route('admin.hebergement.campagnes_admission.edit_campagne', $campagne->getKey()) : null,
                'batiments' => $access->allows($admin, 'admin.hebergement.parametrage-herb.batiments-campagne.index') ? route('admin.hebergement.parametrage-herb.batiments-campagne.index') : null,
            ],
        ]);
    }
}
