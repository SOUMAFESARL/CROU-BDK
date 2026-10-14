<?php

namespace App\Http\Controllers\admin\immobilier\residents_contrat;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Services\EtudiantService;
use App\Models\Document;
use App\Http\Requests\CampagneRequest;
use App\Http\Services\CampagneService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use \App\Models\Cite;
use \App\Models\DocumentCampagne;
use \App\Models\ResidenceCampagne;
use Illuminate\Support\Str;
use App\Http\Services\DocumentEtudiantService;
use App\Repositories\Interfaces\DocumentEtudiantRepositoryInterface;
use App\Repositories\Interfaces\EtudiantRepositoryInterface;
use App\Http\Services\CampagneValidationService;

class ResidentContratControler extends Controller
{


    protected $etudiantService;

    public function __construct(EtudiantService $etudiantService,CampagneService $campagneService)
    {
        $this->etudiantService = $etudiantService;
        $this->campagneService = $campagneService;
    }


    public function liste(Request $request, ?string $etat = null)
    {
        try {

            $result = $this->etudiantService->getListeEtudiantsWithStats($etat ?? $request->get('etat'));
            $result = app(\App\Services\AdminViewData::class)->filter($result, $request->user('admin'));
            //  dd($result);
            if ($request->wantsJson()) {
                return response()->json($result);
            }

            return view('admin.hebergement.resident_contrat.index', [
                'etudiants' => $result['data']['etudiants'],
                'stats' => $result['data']['stats'],
                'etat' => $result['data']['filtre_actif'] ?? 'residents',
                'success' => $result['success'],
                'message' => $result['message'] ?? null,
                'campagne_active' => $result['data']['campagne_active'], // ← nouveau
            ]);

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la récupération de la liste',
                    'error' => \App\Support\UserFacingErrors::caught($e)
                ], 500);
            }

            return redirect()->back()->with('error', 'Erreur lors de la récupération de la liste');
        }
    }


    public function attributionChambre($etudiant_id)
    {

    }

}
