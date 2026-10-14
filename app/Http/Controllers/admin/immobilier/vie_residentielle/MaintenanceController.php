<?php

namespace App\Http\Controllers\admin\immobilier\vie_residentielle;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\OrdreTravailRequest;
use App\Models\OrdreTravail;
use App\Models\Cite;
use App\Models\Maintenancier;
use App\Repositories\Interfaces\OrdreTravailRepositoryInterface;
use App\Repositories\Interfaces\OrdreTravailServiceInterface;
use Illuminate\Support\Facades\Log;
use App\Http\Services\CiteService;
use App\Http\Requests\MaintenancierRequest;
use App\Repositories\Interfaces\MaintenancierRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use App\Http\Services\InterventionService;

use Illuminate\Validation\ValidationException;
use Throwable;

class MaintenanceController extends Controller
{
    protected $service;
    protected $citeService;
    protected  $maintenancierRepository;
    protected $interventionService;


    public function __construct(OrdreTravailServiceInterface $service,CiteService $citeService,
                                MaintenancierRepositoryInterface $maintenancierRepository,
                                InterventionService $interventionService)
    {
        $this->service = $service;
        $this->citeService = $citeService;
        $this->maintenancierRepository = $maintenancierRepository;
        $this->interventionService = $interventionService;
    }
    public function index(Request $request)
    {
        $filters = $request->only([
            'statut', 'priorite', 'categorie', 'cite_id', 'search', 'date_debut', 'date_fin'
        ]);

        $maintenanciers = $this->maintenancierRepository->getAll();
        $specialites = Maintenancier::SPECIALITE;
        $ordres = $this->service->getPaginated(array_merge($filters, [
            'completed' => false,
        ]), 15)->withQueryString();
        $ordresTermines = $this->service->getPaginated(array_merge($filters, [
            'completed' => true,
            'page_name' => 'termines_page',
        ]), 15)->withQueryString();
        $stats = $this->service->getStats($filters);

        // Récupérer les données pour les filtres
        $cites = $this->citeService->getCites();

        // Statistiques par cité pour le tableau de synthèse
        $statsParCite = $cites->map(function ($cite) use ($filters) {
            $citeFilters = array_merge($filters, ['cite_id' => $cite->id_cite]);
            $citeStats = $this->service->getStats($citeFilters);
            $ordresByCite = $this->service->getAll($citeFilters);

            $topCause = $ordresByCite->filter(fn($ordre) => !empty($ordre->cause))
                ->groupBy('cause')
                ->sortByDesc(fn($group) => $group->count())
                ->keys()
                ->first();

            return [
                'id_cite' => $cite->id_cite,
                'name' => $cite->name,
                'total' => $citeStats['total'],
                'termine' => $citeStats['termine'],
                'urgent' => $citeStats['urgent'],
                'cout_total' => $citeStats['cout_total'],
                'temps_moyen' => $citeStats['temps_moyen_resolution'],
                'taux_resolution' => $citeStats['total'] > 0 ? round(($citeStats['termine'] / $citeStats['total']) * 100) : 0,
                'principale_cause' => $topCause ? Str::slug($topCause, '_') : 'autre',
                'principale_cause_label' => $topCause ? ucfirst(str_replace('_', ' ', $topCause)) : 'Autre',
            ];
        })->toArray();

        return view('admin.hebergement.vie_residentielle.maintenance.index', compact(
            'ordres',
            'ordresTermines',
            'stats',
            'filters',
            'cites',
            'specialites',
            'maintenanciers',
            'statsParCite'
        ));
    }



    public function storeMaintenancier(MaintenancierRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();


            $maintenancier = $this->maintenancierRepository->create($data);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Maintenancier créé avec succès',
                    'data' => $maintenancier
                ]);
            }

            return redirect()
                ->route('admin.hebergement.vie_residentielle.maintenance.index')
                ->with('success', 'Maintenancier créé avec succès');

        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la création: ' . \App\Support\UserFacingErrors::caught($e)
                ], 500);
            }

            return back()
                ->withInput()
                ->with('error', 'Erreur lors de la création: ' . \App\Support\UserFacingErrors::caught($e));
        }
    }


public function storeIntervention(Request $request)
{

    try {
        $intervention = $this->interventionService->createIntervention($request->all());

        return back()->with('success', 'Intervention créé avec succès');


    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => \App\Support\UserFacingErrors::caught($e)
        ], 422);
    }

}

    /**
     * Afficher le formulaire de création
     */


    public function store(Request $request) // OrdreTravailRequest
    {
        try {


            // Récupérer les données du formulaire
            $data = $request->all();


                if(!empty($data['assigne_a'] && isset($data['assigne_a']))){

                    $data['statut'] = 'en_cours';

                }else{
                    $data['statut'] = 'en_attente';
                    $data['date_debut_intervention'] = null;
                    $data['date_fin_intervention'] = null;
                    $data['assigne_a'] = null;
                }
            // Définir le statut par défaut


            // Créer l'ordre de travail
            $ordre = $this->service->create($data);

            //  dd('dfghbn');

            if (!$ordre) {
                throw new \Exception('Erreur lors de la création de l\'ordre de travail');
            }

            Log::info("Ordre de travail créé par le conciege : {$ordre->numero_ot}");

            return redirect()
                ->back()
                ->with('success', '✅ Votre demande de maintenance a été soumise avec succès ! Le numéro de suivi est : ' . $ordre->numero_ot);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de l\'OT: ' . $e->getMessage());
            return redirect()
                ->back()
                ->withInput()
                ->with('error', '❌ Erreur lors de la création: ' . \App\Support\UserFacingErrors::caught($e));
        }
    }

    /**
     * Afficher les détails d'un ordre de travail
     */
    public function show(string $id)
    {
        $ordre = $this->service->findById($id);
        $maintenanciers = $this->maintenancierRepository->getAll();

        if (!$ordre) {
            abort(404, 'Ordre de travail non trouvé');
        }

        return view('admin.hebergement.vie_residentielle.maintenance.show', compact('ordre','maintenanciers'));
    }

    /**
     * Afficher le formulaire de modification
     */
    public function edit(string $id)
    {
        $ordre = $this->service->findById($id);

        if (!$ordre) {
            abort(404, 'Ordre de travail non trouvé');
        }

        // Vérifier si l'OT peut être modifié
        if (in_array($ordre->statut, ['termine', 'annule'])) {
            return redirect()
                ->route('admin.hebergement.vie_residentielle.maintenance.show', $id)
                ->with('warning', 'Cet ordre de travail est clôturé et ne peut pas être modifié');
        }

        $cites = \App\Models\Cite::select('id_cite as value', 'name as label')->get();
        $batiments = \App\Models\Batiment::select('id_batiment as value', 'name as label')->get();
        $chambres = \App\Models\Chambre::select('id_chambre as value', 'numero_chambre as label')->get();
        $equipements = \App\Models\Equipement::select('equipement_id as value', 'name as label')->get();
        $pieces = [];

        return view('admin.hebergement.vie_residentielle.maintenance.edit', compact(
            'ordre', 'cites', 'batiments', 'chambres', 'equipements', 'pieces'
        ));
    }

    /**
     * Mettre à jour un ordre de travail
     */
    public function update(OrdreTravailRequest $request, string $id)
    {
        try {
            $ordre = $this->service->findById($id);
            if (!$ordre) {
                abort(404, 'Ordre de travail non trouvé');
            }

            $data = $request->validated();
            $ordre = $this->service->update($id, $data);

            return redirect()
                ->route('admin.hebergement.vie_residentielle.maintenance.show', $ordre->id_ordres_travail)
                ->with('success', 'Ordre de travail mis à jour avec succès');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour de l\'OT: ' . $e->getMessage());
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour: ' . \App\Support\UserFacingErrors::caught($e));
        }
    }

    /**
     * Supprimer un ordre de travail
     */
    public function destroy(string $id)
    {
        try {
            $ordre = $this->service->findById($id);
            if (!$ordre) {
                abort(404, 'Ordre de travail non trouvé');
            }

            // Vérifier si l'OT peut être supprimé
            if (in_array($ordre->statut, ['en_cours', 'en_attente_pieces', 'termine'])) {
                return redirect()
                    ->back()
                    ->with('error', 'Cet ordre de travail ne peut pas être supprimé car il est en cours ou clôturé');
            }

            $this->service->delete($id);

            return redirect()
                ->route('admin.hebergement.vie_residentielle.maintenance.index')
                ->with('success', 'Ordre de travail supprimé avec succès');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression de l\'OT: ' . $e->getMessage());
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la suppression: ' . \App\Support\UserFacingErrors::caught($e));
        }
    }

    /**
     * Assigner un technicien à un ordre de travail
     */
    public function assigner(Request $request, string $id)
    {
        try {
            $request->validate([
                'assigne_a' => 'required|exists:maintenanciers,id_maintenancier'
            ]);
            $data= $request->all();
         //   dd($data);

            $ordre = $this->service->assigner($id, $data);

            return redirect()
                ->route('admin.hebergement.vie_residentielle.maintenance.show', $ordre->id_ordres_travail)
                ->with('success', 'Technicien assigné avec succès');
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'assignation: ' . $e->getMessage());
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de l\'assignation: ' . \App\Support\UserFacingErrors::caught($e));
        }
    }

    /**
     * Changer le statut d'un ordre de travail
     */
    public function changerStatut(Request $request, string $id)
    {
        try {
          //  dd('sdfazbvn,asdm');
            $request->validate([
                'statut' => 'required|in:en_attente,en_cours,en_attente_pieces,termine,annule,reporte',
                'solution' => 'nullable|string'
            ]);

            $ordre = $this->service->updateStatut($id, $request->statut, $request->solution);

            return redirect()
                ->route('admin.hebergement.vie_residentielle.maintenance.show', $ordre->id_ordres_travail)
                ->with('success', 'Statut mis à jour avec succès');
        } catch (\Exception $e) {
            Log::error('Erreur lors du changement de statut: ' . $e->getMessage());
            return redirect()
                ->back()
                ->with('error', 'Erreur lors du changement de statut: ' . \App\Support\UserFacingErrors::caught($e));
        }
    }

    /**
     * Exporter les ordres de travail en CSV
     */
    public function export(Request $request)
    {
        try {
            $filters = $request->only([
                'statut', 'priorite', 'categorie', 'cite_id', 'date_debut', 'date_fin'
            ]);

            $ordres = $this->service->getAll($filters);

            $headers = [
                'N° OT', 'Titre', 'Catégorie', 'Priorité', 'Statut',
                'Date création', 'Date résolution', 'Coût réel'
            ];

            $rows = $ordres->map(function ($ordre) {
                return [
                    $ordre->numero_ot,
                    $ordre->titre,
                    $ordre->categorie_label,
                    $ordre->priorite_label,
                    $ordre->statut_label,
                    $ordre->date_disponibilite?->format('d/m/Y H:i'),
                    $ordre->date_resolution?->format('d/m/Y H:i'),
                    number_format($ordre->cout_reel, 0, ',', ' ') . ' FCFA'
                ];
            });

            $filename = 'ordres_travail_' . date('Ymd_His') . '.csv';

            return response()->stream(
                function () use ($headers, $rows) {
                    $handle = fopen('php://output', 'w');
                    fputcsv($handle, $headers, ';');
                    foreach ($rows as $row) {
                        fputcsv($handle, $row, ';');
                    }
                    fclose($handle);
                },
                200,
                [
                    'Content-Type' => 'text/csv; charset=UTF-8',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                ]
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'export: ' . $e->getMessage());
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de l\'export: ' . \App\Support\UserFacingErrors::caught($e));
        }
    }

    /**
     * Récupérer les données pour les graphiques
     */
    public function chartData(Request $request)
    {
        try {
            $filters = $request->only(['date_debut', 'date_fin']);
            $stats = $this->service->getStats($filters);

            // Données pour le graphique des statuts
            $statusData = [
                'labels' => ['En attente', 'En cours', 'En attente pièces', 'Terminé', 'Annulé'],
                'values' => [
                    $stats['en_attente'] ?? 0,
                    $stats['en_cours'] ?? 0,
                    $stats['en_attente_pieces'] ?? 0,
                    $stats['termine'] ?? 0,
                    0
                ],
                'colors' => ['#b13e3e', '#d68a3a', '#1e7aa8', '#2a8d6c', '#5b7b99']
            ];

            // Données pour le graphique des catégories
            $categories = $this->service->getAll($filters)
                ->groupBy('categorie')
                ->map(function ($group) {
                    return $group->count();
                });

            $categoryData = [
                'labels' => $categories->keys()->map(function ($key) {
                    return (new OrdreTravail())->getCategorieLabelAttribute($key);
                })->values()->toArray(),
                'values' => $categories->values()->toArray()
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'status_chart' => $statusData,
                    'category_chart' => $categoryData
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des données: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données'
            ], 500);
        }
    }

public function storeSatisfaction(Request $request): RedirectResponse
{
    try {
        $this->interventionService->storeSatisfaction(
            $request->only([
                'ot_id',
                'taux_satisfaction',
                'commentaire_satisfaction',
            ])
        );

        return back()->with(
            'success',
            'Votre avis a été enregistré avec succès.'
        );
    } catch (ValidationException $exception) {
        throw $exception;
    } catch (Throwable $exception) {
        report($exception);

        return back()
            ->withInput()
            ->with(
                'error',
                "Une erreur est survenue pendant l'enregistrement de votre avis."
            );
    }
}


}
