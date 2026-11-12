<?php

namespace App\Http\Controllers\admin\immobilier\campagnes_admission;

use App\Http\Controllers\Controller;
use App\Http\Requests\CampagneCriteresRequest;
use App\Http\Services\Eligibilite\CriteresCampagne;
use App\Http\Services\Eligibilite\CriteresCampagneVue;
use App\Models\Campagne;
use App\Services\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Actions sur un jeu de critères d'une campagne (paramétrage général, admission, réadmission) : enregistrer, appliquer,
 * abandonner le brouillon. Les critères se définissent et se consultent dans la page « Critères de la campagne » du
 * paramétrage (paramétrage général, puis un niveau par volet ouvert).
 * Voir docs/notes/eligibilite-criteres-campagne.md.
 */
class CriteresCampagneController extends Controller
{
    public function __construct(private CriteresCampagne $criteres, private AdminAccess $access) {}

    /**
     * Enregistre les blocs envoyés dans le brouillon du volet, puis le met en vigueur si demandé (tout ou rien).
     * Un bloc envoyé sans le droit correspondant est refusé (403).
     */
    public function update(CampagneCriteresRequest $request, string $campagne, string $volet): RedirectResponse
    {
        $campagne = Campagne::findOrFail($campagne);
        $admin = $request->user('admin');
        abort_if($request->has('regles') && ! $admin->hasPermission(CriteresCampagneVue::REGLES_CONFIGURE), 403, 'Vous n’avez pas le droit de paramétrer les règles des critères.');
        abort_if($request->has('places') && ! $admin->hasPermission(CriteresCampagneVue::QUOTAS_CONFIGURE), 403, 'Vous n’avez pas le droit de saisir les places et quotas.');
        $appliquer = $request->boolean('appliquer');
        abort_if($appliquer && ! $this->access->allows($admin, 'admin.hebergement.eligibilite.criteres.appliquer'), 403, 'Vous n’avez pas le droit d’appliquer les critères.');

        $validees = $request->validated();
        DB::transaction(function () use ($campagne, $volet, $validees, $admin, $appliquer) {
            $this->criteres->enregistrer($campagne, $volet, $validees['regles'] ?? null, $validees['places'] ?? null, $admin);
            if ($appliquer) {
                $this->criteres->appliquer($campagne, $volet, $admin);
            }
        });

        return $this->retourPage($campagne, $volet)
            ->with('success', $appliquer
                ? 'Critères '.CriteresCampagne::JEUX[$volet].' enregistrés et appliqués à la campagne.'
                : 'Brouillon des critères '.CriteresCampagne::JEUX[$volet].' enregistré. Appliquez-le pour le mettre en vigueur.');
    }

    /** Met en vigueur le brouillon du jeu, ou le modèle CROU par défaut si ce jeu n'a encore aucun critère. */
    public function appliquer(Request $request, string $campagne, string $volet): RedirectResponse
    {
        $campagne = Campagne::findOrFail($campagne);
        $version = $this->criteres->appliquer($campagne, $volet, $request->user('admin'));

        return $this->retourPage($campagne, $volet)->with('success', 'Critères '.CriteresCampagne::JEUX[$volet]." appliqués à la campagne (version {$version->version}).");
    }

    /** Supprime le brouillon en attente du volet. */
    public function abandonner(string $campagne, string $volet): RedirectResponse
    {
        $campagne = Campagne::findOrFail($campagne);
        $this->criteres->abandonnerBrouillon($campagne, $volet);

        return $this->retourPage($campagne, $volet)->with('success', 'Brouillon des critères '.CriteresCampagne::JEUX[$volet].' abandonné.');
    }

    /** Retour sur la page « Critères de la campagne », ouverte sur le niveau traité. */
    private function retourPage(Campagne $campagne, string $volet): RedirectResponse
    {
        return redirect()->route(CriteresCampagneVue::PAGE, ['campagne_id' => $campagne->getKey(), 'volet' => $volet]);
    }
}
