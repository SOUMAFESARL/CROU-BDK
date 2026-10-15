<?php

namespace App\Http\Controllers\admin\immobilier\residents_contrat;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Campagne;
use App\Models\Etudiant;
use App\Models\Chambre;
use App\Models\AssignationChambre;
use App\Http\Services\AllocationChambreService;
use App\Repositories\Interfaces\ChambreRepositoryInterface;
use App\Repositories\Interfaces\AssignationChambreRepositoryInterface;

use Exception;

class AllocationChambreController extends Controller
{
    protected AllocationChambreService $service;
    protected ChambreRepositoryInterface $chambreRepository;
    protected AssignationChambreRepositoryInterface $assignationRepository;

    public function __construct(
        AllocationChambreService $service,
        ChambreRepositoryInterface $chambreRepository,
        AssignationChambreRepositoryInterface $assignationRepository
    ) {
        $this->service = $service;
        $this->chambreRepository = $chambreRepository;
        $this->assignationRepository = $assignationRepository;
    }

    /**
     * Afficher la liste des campagnes
     */
    public function index()
    {
        $campagnes = Campagne::where('statut', '!=', 'annulee')
            ->withCount([
                // Dossiers éligibles qui attendent une proposition de chambre.
                'demandes as dossiers_eligibles_count' => fn ($q) => $q->where('etat_dossier', 'eligible'),
                'assignations as propositions_count' => fn ($q) => $q->where('statut', 'en_attente'),
                'assignations as confirmees_count' => fn ($q) => $q->where('statut', 'confirmee'),
            ])
            ->orderByDesc('periode_debut')
            ->paginate(10);

        $parStatut = Campagne::where('statut', '!=', 'annulee')
            ->selectRaw('statut, count(*) as total')->groupBy('statut')->pluck('total', 'statut');

        return view('admin.hebergement.resident_contrat.allocation_index', [
            'campagnes' => $campagnes,
            'stats' => [
                'total' => (int) $parStatut->sum(),
                'active' => (int) ($parStatut['active'] ?? 0),
                'planifiee' => (int) ($parStatut['planifiee'] ?? 0),
                'terminee' => (int) ($parStatut['terminee'] ?? 0),
            ],
        ]);
    }

    /**
     * Afficher les détails d'une campagne
     */
    public function show($campagneId)
    {

        $campagne = Campagne::findOrFail($campagneId);

        try {
            $stats = $this->service->getStatistiques($campagneId);
           // dd($stats);
        } catch (Exception $e) {
            $stats = [];
        }

     //  dd($campagne);
      //  dd($campagne,$stats);
        $assignations = $this->assignationRepository->getByCampagne($campagneId);
        $etudiants = $this->assignationRepository->getStudentsSansChambres($campagneId);
        $chambres = $this->chambreRepository->getDisponibles($campagneId);
//dd($assignations);
        return view('admin.hebergement.resident_contrat.allocation_show', [
            'campagne' => $campagne,
            'stats' => $stats,
            'assignations' => $assignations,
            'etudiants_sans_chambre' => $etudiants,
            'chambres_disponibles' => $chambres,
        ]);
    }

    /**
     * Afficher le formulaire d'allocation
     */
    public function confirmerPmr(Request $request, $campagneId)
    {
        $data = $request->validate([
            'etudiant_id' => ['required', 'string'],
            'pmr_confirme' => ['required', 'boolean'],
        ]);
        \Illuminate\Support\Facades\DB::transaction(function () use ($data, $campagneId) {
            $etudiant = Etudiant::whereKey($data['etudiant_id'])
                ->whereHas('demandes', fn ($q) => $q->where('campagne_id', $campagneId))
                ->lockForUpdate()->firstOrFail();
            $etudiant->update([
                'pmr_confirme' => $data['pmr_confirme'],
                'pmr_confirme_by' => auth('admin')->id(),
                'pmr_confirme_at' => now(),
            ]);
        });
        return back()->with('success', 'Confirmation PMR enregistree.');
    }

    public function allocateForm($campagneId)
    {

        $campagne = Campagne::findOrFail($campagneId);

        $etudiants = $this->assignationRepository->getStudentsSansChambres($campagneId);

        $chambres = $this->chambreRepository->getDisponibles($campagneId);
        $regles = app(\App\Http\Services\AttributionRules::class);

        return view('admin.hebergement.resident_contrat.allocation_form', [
            'campagne' => $campagne,
            'etudiants' => $etudiants,
            'chambres' => $chambres,
            // Pré-contrôle affiché à l'écran ; AttributionRules::erreurs() refait la vérification complète.
            'sexesEtudiants' => $etudiants->mapWithKeys(fn ($e) => [$e->getKey() => $regles->sexeEtudiant($e)]),
            'sexesChambres' => $chambres->mapWithKeys(fn ($c) => [$c->getKey() => $regles->sexeRequis($c)]),
        ]);
    }

    /**
     * Allouer une chambre à un étudiant
     */
    public function allocate(Request $request, $campagneId)
    {
        $validated = $request->validate([
            'etudiant_id' => 'required|string|exists:etudiants,id_etudiant',
            'chambre_id' => 'required_if:allocation_type,manuelle|nullable|string|exists:chambre,id_chambre',
            'allocation_type' => 'required|in:automatique,manuelle',
            'numero_lit' => 'nullable|integer|min:1',
        ]);

        try {
            $campagne = Campagne::findOrFail($campagneId);
            $etudiant = Etudiant::findOrFail($validated['etudiant_id']);

            $chambre = null;
            if ($validated['allocation_type'] === 'manuelle' && !empty($validated['chambre_id'])) {
                $chambre = Chambre::findOrFail($validated['chambre_id']);
            }

            $assignation = $this->service->allocateChambre($etudiant, $campagne, $chambre, $chambre ? ($validated['numero_lit'] ?? null) : null);

            if ($assignation) {
                return back()->with('success',
                    "Chambre {$assignation->chambre->numero_chambre} assignée avec succès à {$etudiant->name}");
            }

            return back()->withInput()->with('error', 'Impossible d\'assigner une chambre. Vérifiez les critères.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Erreur: ' . \App\Support\UserFacingErrors::caught($e));
        }
    }

    /**
     * Allocation automatique pour toute la campagne
     */
    public function allocateAll(Request $request, $campagneId)
    {
        try {
            $result = $this->service->allocateAllStudents($campagneId);

            $message = "Allocation terminée: {$result['success_count']}/{$result['total']} étudiants assignés ({$result['success_rate']}%)";

            return back()->with([
                'success' => $message,
                'allocation_result' => $result,
            ]);
        } catch (Exception $e) {
            return back()->with('error', 'Erreur lors de l\'allocation: ' . \App\Support\UserFacingErrors::caught($e));
        }
    }

    /**
     * Confirmer une assignation
     */
    public function confirm(Request $request, $campagneId)
    {

        $validated = $request->validate([
            'assignation_id' => ['required', 'string', \Illuminate\Validation\Rule::exists('assignation_chambres', 'id_assignation')->where('campagne_id', $campagneId)],
        ]);

        try {
            $this->service->confirmerAssignation($validated['assignation_id']);
            return back()->with('success', 'Assignation confirmée');
        } catch (Exception $e) {
            return back()->with('error', \App\Support\UserFacingErrors::caught($e));
        }
    }

    /**
     * Annuler une assignation
     */
    public function cancel(Request $request, $campagneId)
    {
        $validated = $request->validate([
            'assignation_id' => ['required', 'string', \Illuminate\Validation\Rule::exists('assignation_chambres', 'id_assignation')->where('campagne_id', $campagneId)],
            'motif' => 'nullable|string|max:255',
        ]);

        try {
            $this->service->annulerAssignation($validated['assignation_id'], $validated['motif'] ?? null);
            return back()->with('success', 'Assignation annulée');
        } catch (Exception $e) {
            return back()->with('error', \App\Support\UserFacingErrors::caught($e));
        }
    }

    /**
     * Réaffecter un étudiant
     */
    public function reassign(Request $request, $campagneId)
    {
        $validated = $request->validate([
            'etudiant_id' => 'required|string|exists:etudiants,id_etudiant',
            'chambre_id' => 'required|string|exists:chambre,id_chambre',
            'numero_lit' => 'nullable|integer|min:1',
        ]);

        try {
            $campagne = Campagne::findOrFail($campagneId);
            $etudiant = Etudiant::findOrFail($validated['etudiant_id']);
            $chambre = Chambre::findOrFail($validated['chambre_id']);

            $assignation = $this->service->reaffecterChambre($etudiant, $chambre, $campagne, 'Reaffectation', $validated['numero_lit'] ?? null);

            if ($assignation) {
                return back()->with('success',
                    "Chambre réaffectée avec succès: {$assignation->chambre->numero_chambre}");
            }

            return back()->with('error', 'Impossible de réaffecter');
        } catch (Exception $e) {
            return back()->with('error', \App\Support\UserFacingErrors::caught($e));
        }
    }

    /**
     * Retirer une chambre assignée
     */
    public function remove(Request $request, $campagneId)
    {
        $validated = $request->validate([
            'assignation_id' => ['required', 'string', \Illuminate\Validation\Rule::exists('assignation_chambres', 'id_assignation')->where('campagne_id', $campagneId)],
        ]);

        try {
            $this->service->annulerAssignation($validated['assignation_id'], 'Retrait manuel');
            return back()->with('success', 'Chambre retirée');
        } catch (Exception $e) {
            return back()->with('error', \App\Support\UserFacingErrors::caught($e));
        }
    }

    /**
     * API: Obtenir les assignations en JSON
     */
    public function getAssignations($campagneId)
    {
        $assignations = $this->assignationRepository->getByCampagne($campagneId);

        return response()->json([
            'data' => $assignations->map(function ($a) {
                return [
                    'id' => $a->id_assignation,
                    'etudiant' => $a->etudiant->name,
                    'matricule' => $a->etudiant->matricule,
                    'niveau' => $a->etudiant->getAnneeEtude(),
                    'chambre' => $a->chambre->numero_chambre,
                    'lit' => $a->lit?->numero,
                    'type' => $a->chambre->type_chambre_code,
                    'batiment' => $a->chambre->batiment?->name,
                    'statut' => $a->statut,
                    'date_assignation' => $a->date_assignation?->format('d/m/Y H:i'),
                ];
            }),
            'total' => $assignations->count(),
        ]);
    }

    /**
     * Vérifier l'éligibilité d'un étudiant
     */
    public function checkEligibility(Request $request)
    {
        $validated = $request->validate([
            'etudiant_id' => 'required|string|exists:etudiants,id_etudiant',
            'chambre_id' => 'required|string|exists:chambre,id_chambre',
            'numero_lit' => 'nullable|integer|min:1',
        ]);

        try {
            $etudiant = Etudiant::findOrFail($validated['etudiant_id']);
            $chambre = Chambre::findOrFail($validated['chambre_id']);

            $eligibility = $this->service->verifierEligibilite($etudiant, $chambre);

            return response()->json($eligibility);
        } catch (Exception $e) {
            return response()->json(['error' => \App\Support\UserFacingErrors::caught($e)], 400);
        }
    }

    /**
     * Obtenir les chambres disponibles pour un étudiant
     */
    public function getAvailableChambers(Request $request)
    {
        $validated = $request->validate([
            'etudiant_id' => 'required|string|exists:etudiants,id_etudiant',
        ]);

        try {
            $etudiant = Etudiant::findOrFail($validated['etudiant_id']);
            $chambres = $this->service->getChambresDisponibles($etudiant);

            return response()->json([
                'data' => $chambres,
                'total' => collect($chambres)->sum(fn($c) => count($c)),
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => \App\Support\UserFacingErrors::caught($e)], 400);
        }
    }

    /**
     * Exporter les assignations en CSV
     */
    public function exportCsv($campagneId)
    {
        $campagne = Campagne::findOrFail($campagneId);
        $assignations = $this->assignationRepository->getByCampagne($campagneId);

        $filename = "allocations_" . $campagne->annee_campagne . "_" . date('Y-m-d') . ".csv";

        $headers = [
            "Content-Type" => "text/csv; charset=utf-8",
            "Content-Disposition" => "attachment; filename=$filename"
        ];

        $rows = [];
        $rows[] = ['Matricule', 'Nom Étudiant', 'Niveau', 'Chambre', 'Type', 'Bâtiment', 'Statut', 'Date'];

        foreach ($assignations as $a) {
            $rows[] = [
                $a->etudiant->matricule,
                $a->etudiant->name,
                $a->etudiant->getAnneeEtude(),
                $a->chambre->numero_chambre,
                $a->chambre->typeChambre?->name,
                $a->chambre->batiment?->name,
                $a->statut,
                $a->date_assignation?->format('d/m/Y H:i'),
            ];
        }

        return response()->stream(function () use ($rows) {
            $file = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($file, $row, ';');
            }
            fclose($file);
        }, 200, $headers);
    }

    /**
     * Afficher les statistiques détaillées
     */
    public function statistics($campagneId)
    {
        try {
            $campagne = Campagne::findOrFail($campagneId);
            $stats = $this->service->getStatistiques($campagneId);

            return view('admin.hebergement.resident_contrat.allocation_statistics', [
                'campagne' => $campagne,
                'stats' => $stats,
            ]);
        } catch (Exception $e) {
            return back()->with('error', \App\Support\UserFacingErrors::caught($e));
        }
    }
}