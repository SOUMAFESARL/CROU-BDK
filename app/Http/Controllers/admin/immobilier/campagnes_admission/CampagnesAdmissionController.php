<?php

namespace App\Http\Controllers\admin\immobilier\campagnes_admission;

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
use App\Http\Services\CiteService;
use Illuminate\Support\Str;
use App\Http\Services\DocumentEtudiantService;
use App\Repositories\Interfaces\DocumentEtudiantRepositoryInterface;
use App\Repositories\Interfaces\EtudiantRepositoryInterface;
use App\Http\Services\CampagneValidationService;


class CampagnesAdmissionController extends Controller
{


    protected $campusService;
    protected $citeService;

    public function __construct(EtudiantService $etudiantService,CampagneService $campagneService,CiteService $citeService)
    {
       
        $this->etudiantService = $etudiantService;
        $this->campagneService = $campagneService;
        $this->citeService = $citeService;
         
    }


    public function liste(Request $request, ?string $etat = null)
    {
        try {

            if (!$request->wantsJson()) {
                $filter = $etat ?? $request->get('etat');
                $repository = app(\App\Repositories\EtudiantRepository::class);
                return view('admin.hebergement.campagnes_admission.admission.admission', [
                    'etudiants'=>$repository->getAdmissionsPage($filter),
                    'stats'=>$repository->getStats(), 'etat'=>$filter ?? 'residents',
                    'success'=>true, 'message'=>null,
                    'campagne_active'=>app(CampagneValidationService::class)->getCampagneActive()?->only([
                        'id_campagne','annee_campagne','date_echeance_demande',
                        'moyenne_min','distance_min','age_min','age_max',
                    ]),
                ]);
            }
            $result = $this->etudiantService->getListeEtudiantsWithStats($etat ?? $request->get('etat'));
            $result = app(\App\Services\AdminViewData::class)->filter($result, $request->user('admin'));
         //  dd($result);
            if ($request->wantsJson()) {
                return response()->json($result);
            }
//dd($result['data']);
            return view('admin.hebergement.campagnes_admission.admission.admission', [
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
        //dd('dcxhzjbkvjn,dmsx.zc'. $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la récupération de la liste');
        }
    }

    // Nouvelle méthode pour récupérer uniquement les stats (pour API ou appels AJAX)
    public function getStats()
    {
        $result = $this->etudiantService->getStats(); 

        if ($result['success']) {
            return response()->json($result);
        }

        return response()->json($result, 500);
    }

    public function show(string $id)
    {
        $student = app(\App\Services\AdminStudentView::class)->find($id, auth('admin')->user());
        $campaigns = app(\App\Services\AdminViewAccess::class)->allows(auth('admin')->user(), 'admissions.eligibility')
            ? $this->campagneService->getListeCampagnesWithStats('active')['data'] : collect();



        return view('admin.hebergement.campagnes_admission.admission.traiter-demande', [
            'demande' => $student,
            'campagnes' => $campaigns,
            'derogations' => auth('admin')->user()->hasPermission('hebergement.admissions.view')
                ? \App\Models\AdmissionDerogation::where('etudiant_id', $id)->latest()->get() : collect(),
            'canApproveDerogation' => app(\App\Http\Services\AdmissionDerogationService::class)->canApprove(auth('admin')->user()),
            'moduleActif' => 'admission'
        ]);
    }


    public function getCampagne($id)
    {
        try {
            $campagne = $this->campagneService->getCampagneDetails($id);

            return response()->json([
                'success' => true,
                'data' => $campagne
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Campagne non trouvée'
            ], 404);
        }
    }




    public function campagnes(Request $request, ?string $etat = null): View
    {



        $result = $this->campagneService->getListeCampagnesWithStats($etat ?? $request->get('etat'));

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        return view('admin.hebergement.campagnes_admission.campagnes.campagnes', [
            'campagnes' => $result['data']['campagnes'],
            'stats' => $result['data']['stats'],
            'etat' => $result['data']['filtre_actif'] ?? 'planifier',
            'success' => $result['success'],
            'message' => $result['message'] ?? null,
            "document" => Document::getAllDocuments(1),
            'residences' =>  $this->citeService->getCiteByStatut('ouvert'),
            'moduleActif' => 'campagnes',
        ]);



    }

    /**
     * Créer une nouvelle campagne
     */
    public function storeCampagne(CampagneRequest $request): RedirectResponse
    {
        try {

            $data = $request->validated();

            // Traitement des résidences sélectionnées
            $residences = $data['residences'] ?? [];
            unset($data['residences']);

            // Traitement des documents requis
            $documents = $data['documents'] ?? [];
            unset($data['documents']);

            // Création de la campagne

        //  dd($requestData);
            $campagne = $this->campagneService->createCampagne($data);

            // Association des résidences (à implémenter selon votre logique)
            if (!empty($residences)) {
                foreach ($residences as $residence) {

                    $residenceCampagne = ResidenceCampagne::create([
                        'campagne_id' => $campagne['id_campagne'],
                        'cite_id' => $residence,

                    ]);

                }
            }

            // Association des documents requis (à implémenter selon votre logique)
            if (!empty($documents)) {
                foreach ($documents as $doc) {

                    $documentCampagne = DocumentCampagne::create([
                        'id_campagne' => $campagne['id_campagne'],
                        'document_id' => $doc,

                    ]);

                }

            }

            return redirect()->bacK()->with('success', 'Campagne créée avec succès');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la création: ' . \App\Support\UserFacingErrors::caught($e));
        }
    }


    /**
     * Traite la soumission du formulaire de traitement
     */
    public function submitTraitement(Request $request, $id)
    {
        // Valider les données
        $validated = $request->validate([
            'decision' => 'required|in:eligible,non_eligible,en_attente',
            'commentaire' => 'nullable|required_if:decision,non_eligible|string|max:500',
            'score_calcule' => 'nullable|integer|min:0|max:100',
            'motif' => 'nullable|string|max:100',
            'dernere_demande' => 'required|string|max:500',
        ], [
            'commentaire.required_if' => 'Le commentaire est obligatoire lorsque la décision est « Non éligible ».',
        ]);

        $etudiantService = new EtudiantService(
            app()->make(EtudiantRepositoryInterface::class),
            app()->make(CampagneValidationService::class)
        );

      

      $resultat = $etudiantService->ValidateDemandeEtudiant($validated, $id); 

        return back()->with($resultat['bool'], $resultat['message']);
    }

    public function valideDocument($documentId, $statut, $message = null){

        $documentService = new DocumentEtudiantService(
            app()->make(DocumentEtudiantRepositoryInterface::class)
        );
       // dd($documentId, $statut, $message);
        $document = $documentService->updateDocument($documentId, $statut, $message);

        return back();
    }
}
