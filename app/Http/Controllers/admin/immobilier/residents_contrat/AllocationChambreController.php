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
use App\Http\Services\AttributionRules;
use App\Http\Services\DossierWorkflow;
use App\Http\Services\LitService;
use App\Services\AdminAccess;
use Inertia\Inertia;

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
     * Liste des campagnes d'attribution (page React `Admin/Hebergement/Allocations/Index`).
     */
    public function index(Request $request, AdminAccess $access)
    {
        $campagnes = Campagne::where('statut', '!=', 'annulee')
            ->withCount([
                // Dossiers confirmés par l'étudiant qui attendent une chambre.
                'demandes as dossiers_eligibles_count' => fn ($q) => $q->where('etat_dossier', 'confirme'),
                'assignations as propositions_count' => fn ($q) => $q->where('statut', 'en_attente'),
                'assignations as confirmees_count' => fn ($q) => $q->where('statut', 'confirmee'),
            ])
            ->orderByDesc('periode_debut')
            ->paginate(10);

        $parStatut = Campagne::where('statut', '!=', 'annulee')
            ->selectRaw('statut, count(*) as total')->groupBy('statut')->pluck('total', 'statut');
        $url = $this->urlAutorisee($request, $access);

        return Inertia::render('Admin/Hebergement/Allocations/Index', [
            'campagnes' => $campagnes->through(fn (Campagne $campagne) => [
                ...$this->campagneProps($campagne),
                'a_loger' => (int) $campagne->dossiers_eligibles_count,
                'propositions' => (int) $campagne->propositions_count,
                'confirmees' => (int) $campagne->confirmees_count,
                'taux' => max(0, min(100, (float) $campagne->getTauxAllocation())),
                'urls' => [
                    'show' => $url('admin.allocation.show', ['campagneId' => $campagne->id_campagne]),
                    'allocate' => $url('admin.allocation.allocate-form', ['campagneId' => $campagne->id_campagne]),
                ],
            ]),
            'stats' => [
                'total' => (int) $parStatut->sum(),
                'active' => (int) ($parStatut['active'] ?? 0),
                'planifiee' => (int) ($parStatut['planifiee'] ?? 0),
                'terminee' => (int) ($parStatut['terminee'] ?? 0),
            ],
            'classement' => $this->classement($request),
        ]);
    }

    /**
     * Suivi d'une campagne : attributions, éligibles sans chambre et chambres disponibles (page React).
     */
    public function show(Request $request, AdminAccess $access, $campagneId)
    {
        $campagne = Campagne::findOrFail($campagneId);

        try {
            $stats = $this->service->getStatistiques($campagneId);
        } catch (Exception $e) {
            $stats = [];
        }

        $assignations = $this->assignationRepository->getByCampagne($campagneId);
        $etudiants = $this->assignationRepository->getStudentsSansChambres($campagneId);
        $chambres = $this->chambreRepository->getDisponibles($campagneId);
        $url = $this->urlAutorisee($request, $access);
        $parametres = ['campagneId' => $campagne->id_campagne];

        return Inertia::render('Admin/Hebergement/Allocations/Show', [
            'campagne' => $this->campagneProps($campagne),
            'stats' => $this->statsProps($stats),
            'assignations' => $assignations->map(function (AssignationChambre $assignation) {
                $etudiant = $assignation->etudiant;
                $chambre = $assignation->chambre;
                $etat = $assignation->dossier?->etat_dossier;

                return [
                    'id' => (string) $assignation->id_assignation,
                    'statut' => $assignation->statut,
                    'etat_dossier' => $etat,
                    'etat_label' => DossierWorkflow::LABELS[$etat] ?? ucfirst(str_replace('_', ' ', (string) $assignation->statut)),
                    'etudiant' => $etudiant?->name,
                    'matricule' => $etudiant?->matricule,
                    'niveau' => $this->niveau($etudiant),
                    'chambre' => $chambre?->numero_chambre,
                    'lit' => $assignation->lit?->numero,
                    'batiment' => $chambre?->batiment?->name,
                    'type' => ucfirst($chambre?->typeChambre?->name ?? 'Non défini'),
                    'echeance' => $assignation->statut === 'en_attente' && $assignation->date_fin
                        ? \Illuminate\Support\Carbon::parse($assignation->date_fin)->format('Y-m-d H:i') : null,
                    // Validation définitive : seulement après l'accord de l'étudiant (dossier « accepté »).
                    'confirmable' => $assignation->statut === 'en_attente' && $etat === 'accepte',
                    'annulable' => $assignation->statut === 'en_attente',
                ];
            })->values(),
            'etudiants' => $etudiants->map(fn (Etudiant $etudiant) => [
                'id' => (string) $etudiant->id_etudiant,
                'name' => $etudiant->name,
                'matricule' => $etudiant->matricule,
                'email' => $etudiant->email1,
                'niveau' => $this->niveau($etudiant),
                'pmr' => (bool) $etudiant->pmr_confirme,
            ])->values(),
            'chambres' => $chambres->map(fn (Chambre $chambre) => [
                'id' => (string) $chambre->id_chambre,
                'numero' => $chambre->numero_chambre,
                'type' => ucfirst($chambre->typeChambre?->name ?? 'Non défini'),
                'lieu' => collect([$chambre->batiment?->name, $chambre->cite?->name])->filter()->implode(' · '),
                'capacite' => (int) $chambre->capacite_max,
                'libres' => (int) $chambre->getNombrePlacesDisponibles(),
                'occupation' => max(0, min(100, (float) $chambre->getPourcentageOccupation())),
                'prix' => (float) $chambre->prix,
                'pmr' => (bool) $chambre->pmr,
            ])->values(),
            'classement' => $this->classement($request),
            'urls' => [
                'index' => $url('admin.allocation.index'),
                'statistics' => $url('admin.allocation.statistics', $parametres),
                'export' => $url('admin.allocation.export-csv', $parametres),
                'allocateAll' => $url('admin.allocation.allocate-all', $parametres),
                'allocateForm' => $url('admin.allocation.allocate-form', $parametres),
                'confirm' => $url('admin.allocation.confirm', $parametres),
                'cancel' => $url('admin.allocation.cancel', $parametres),
            ],
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

    /**
     * Formulaire d'attribution d'une chambre (page React) avec le pré-contrôle PMR / sexe / places.
     */
    public function allocateForm(Request $request, AdminAccess $access, $campagneId)
    {
        $campagne = Campagne::findOrFail($campagneId);
        $etudiants = $this->assignationRepository->getStudentsSansChambres($campagneId);
        $chambres = $this->chambreRepository->getDisponibles($campagneId);
        // Pré-contrôle affiché à l'écran ; AttributionRules::erreurs() refait la vérification complète.
        $regles = app(AttributionRules::class);
        $lits = app(LitService::class);
        // Types visés par l'attribution automatique (Etudiant::getPrioriteChambres()).
        $libellesTypes = ['individuelle' => 'individuelle', 'double' => 'double', 'triple' => 'triple ou plus'];
        $url = $this->urlAutorisee($request, $access);
        $parametres = ['campagneId' => $campagne->id_campagne];

        return Inertia::render('Admin/Hebergement/Allocations/Allocate', [
            'campagne' => $this->campagneProps($campagne),
            'etudiants' => $etudiants->map(fn (Etudiant $etudiant) => [
                'id' => (string) $etudiant->id_etudiant,
                'name' => $etudiant->name,
                'matricule' => $etudiant->matricule,
                'email' => $etudiant->email1,
                'niveau' => $this->niveau($etudiant),
                'pmr' => (bool) $etudiant->pmr_confirme,
                'sexe' => $regles->sexeEtudiant($etudiant),
                'priorites' => collect($etudiant->getPrioriteChambres())->map(fn ($type) => $libellesTypes[$type] ?? $type)->implode(', '),
            ])->values(),
            'chambres' => $chambres->map(fn (Chambre $chambre) => [
                'id' => (string) $chambre->id_chambre,
                'numero' => $chambre->numero_chambre,
                'type' => ucfirst($chambre->typeChambre?->name ?? 'Non défini'),
                'batiment' => $chambre->batiment?->name,
                'lieu' => collect([$chambre->batiment?->name, $chambre->cite?->name])->filter()->implode(' · '),
                'capacite' => (int) $chambre->capacite_max,
                'occupes' => (int) $chambre->getNombrePlacesOccupees(),
                'libres' => (int) $chambre->getNombrePlacesDisponibles(),
                'prix' => (float) $chambre->prix,
                'pmr' => (bool) $chambre->pmr,
                'sexe' => $regles->sexeRequis($chambre),
                'lits' => array_values(array_map('intval', (array) $lits->disponibles($chambre))),
            ])->values(),
            'etudiantInitial' => (string) $request->query('etudiant_id', ''),
            'urls' => [
                'show' => $url('admin.allocation.show', $parametres),
                'allocate' => $url('admin.allocation.allocate', $parametres),
                'pmr' => $url('admin.allocation.pmr', $parametres),
            ],
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
     * Statistiques d'attribution d'une campagne (page React).
     */
    public function statistics(Request $request, AdminAccess $access, $campagneId)
    {
        try {
            $campagne = Campagne::findOrFail($campagneId);
            $stats = $this->service->getStatistiques($campagneId);
            $url = $this->urlAutorisee($request, $access);
            $parametres = ['campagneId' => $campagne->id_campagne];

            return Inertia::render('Admin/Hebergement/Allocations/Statistics', [
                'campagne' => $this->campagneProps($campagne),
                'stats' => $this->statsProps($stats),
                'urls' => [
                    'show' => $url('admin.allocation.show', $parametres),
                    'export' => $url('admin.allocation.export-csv', $parametres),
                ],
            ]);
        } catch (Exception $e) {
            return back()->with('error', \App\Support\UserFacingErrors::caught($e));
        }
    }

    /** URL d'une route si l'admin connecté y a accès (remplace `@adminroute`), sinon null. */
    private function urlAutorisee(Request $request, AdminAccess $access): \Closure
    {
        $admin = $request->user('admin');

        return fn (string $route, array $parametres = []) => $access->allows($admin, $route) ? route($route, $parametres) : null;
    }

    private function campagneProps(Campagne $campagne): array
    {
        return [
            'id' => (string) $campagne->id_campagne,
            'annee' => $campagne->annee_campagne,
            'description' => $campagne->description,
            'statut' => $campagne->statut,
            'statut_label' => $campagne->getLabelStatut(),
            'periode_debut' => $campagne->periode_debut?->toDateString(),
            'periode_fin' => $campagne->periode_fin?->toDateString(),
        ];
    }

    /** Statistiques de `getStatistiques()` ; `null` si elles n'ont pas pu être calculées. */
    private function statsProps(array $stats): ?array
    {
        if ($stats === []) {
            return null;
        }

        return [
            'demandes_acceptees' => (int) ($stats['demandes_acceptees'] ?? 0),
            'assignations_confirmees' => (int) ($stats['assignations_confirmees'] ?? 0),
            'assignations_en_attente' => (int) ($stats['assignations_en_attente'] ?? 0),
            'total_assignations' => (int) ($stats['total_assignations'] ?? 0),
            'sans_chambre' => (int) ($stats['sans_chambre'] ?? 0),
            'taux_allocation' => max(0, min(100, (float) ($stats['taux_allocation'] ?? 0))),
            'par_type' => array_values(array_map(fn ($type) => [
                'nom' => $type['nom'],
                'total' => (int) $type['total'],
                'occupees' => (int) $type['occupees'],
                'disponibles' => (int) $type['disponibles'],
            ], $stats['par_type'] ?? [])),
        ];
    }

    /** Classement utilisé par la dernière « Attribution automatique » (flash `allocation_result`), trié par rang. */
    private function classement(Request $request): ?array
    {
        $resultat = $request->session()->get('allocation_result');
        if (!$resultat) {
            return null;
        }

        return [
            'reservations' => count($resultat['allocated'] ?? []),
            'lignes' => collect($resultat['allocated'] ?? [])->merge($resultat['not_allocated'] ?? [])->sortBy('rang')
                ->map(fn ($entree) => [
                    'rang' => $entree['rang'] ?? null,
                    'name' => $entree['name'] ?? null,
                    'score' => isset($entree['score']) ? (float) $entree['score'] : null,
                    'chambre' => $entree['chambre'] ?? null,
                    'raison' => $entree['reason'] ?? null,
                    'bareme' => $entree['scoring_version'] ?? null,
                    'priorite' => $entree['priority'] ?? null,
                ])->values()->all(),
        ];
    }

    /** Libellé du niveau : le code stocké sur la demande est souvent l'identifiant du niveau. */
    private function niveau(?Etudiant $etudiant): ?string
    {
        if (!$etudiant) {
            return null;
        }
        $demande = $etudiant->demandeActuelle();

        return $demande?->niveauEtudiantActuel?->name ?? $demande?->niveau_etude_actuel_code;
    }

}