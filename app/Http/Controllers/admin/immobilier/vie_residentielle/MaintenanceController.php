<?php

namespace App\Http\Controllers\admin\immobilier\vie_residentielle;

use App\Http\Controllers\Controller;
use App\Http\Requests\MaintenancierRequest;
use App\Http\Requests\OrdreTravailRequest;
use App\Http\Services\CiteService;
use App\Http\Services\InterventionService;
use App\Http\Services\OrdreTravailService;
use App\Models\Batiment;
use App\Models\Chambre;
use App\Models\Equipement;
use App\Models\Maintenancier;
use App\Models\OrdreTravail;
use App\Models\User;
use App\Repositories\Interfaces\MaintenancierRepositoryInterface;
use App\Repositories\Interfaces\OrdreTravailServiceInterface;
use App\Services\AdminAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

class MaintenanceController extends Controller
{
    protected $service;

    protected $citeService;

    protected $maintenancierRepository;

    protected $interventionService;

    public function __construct(OrdreTravailServiceInterface $service, CiteService $citeService,
        MaintenancierRepositoryInterface $maintenancierRepository,
        InterventionService $interventionService)
    {
        $this->service = $service;
        $this->citeService = $citeService;
        $this->maintenancierRepository = $maintenancierRepository;
        $this->interventionService = $interventionService;
    }

    /** Statuts d'un ordre de travail encore ouvert. */
    private const OUVERTS = ['en_attente', 'en_cours', 'en_attente_pieces', 'reporte'];

    /**
     * URL d'une route admin, ou null si l'administrateur connecté n'y a pas accès (remplace `@adminroute`).
     */
    private function urlSiAutorise(Request $request): \Closure
    {
        $access = app(AdminAccess::class);
        $admin = $request->user('admin');

        return fn (string $route, array|string $parametres = []) => $access->allows($admin, $route) ? route($route, $parametres) : null;
    }

    /** Libellé de catégorie sans pictogramme (« ⚡ Électricité » → « Électricité »). */
    private function libelleCategorie(?string $categorie): string
    {
        $libelle = OrdreTravail::CATEGORIES_INTERVENTIONS[$categorie] ?? ucfirst(str_replace('_', ' ', (string) $categorie));

        return trim(preg_replace('/^[^\p{L}]+/u', '', $libelle));
    }

    private function categories(): array
    {
        return collect(OrdreTravail::CATEGORIES_INTERVENTIONS)
            ->map(fn ($libelle, $valeur) => ['value' => $valeur, 'label' => $this->libelleCategorie($valeur)])
            ->values()->all();
    }

    /** Ligne de tableau d'un ordre de travail (tableau explicite, jamais le modèle brut). */
    private function ligne(OrdreTravail $ordre, \Closure $url): array
    {
        $ouvert = ! in_array($ordre->statut, ['termine', 'annule'], true);

        return [
            'id' => $ordre->id_ordres_travail,
            'numero' => $ordre->numero_ot,
            'titre' => $ordre->titre,
            'residence' => $ordre->residence?->name,
            'batiment' => $ordre->batiment?->name,
            'chambre' => $ordre->chambre?->numero_chambre,
            'demandeur' => $ordre->resident?->name,
            'telephone' => $ordre->resident?->telephone1,
            'categorie' => $ordre->categorie,
            'categorie_label' => $this->libelleCategorie($ordre->categorie),
            'description' => $ordre->description,
            'priorite' => $ordre->priorite,
            'priorite_label' => $ordre->priorite_label,
            'statut' => $ordre->statut,
            'statut_label' => $ordre->statut_label,
            'technicien' => $ordre->assigneA?->name,
            'date_creation' => $ordre->created_at?->format('Y-m-d H:i'),
            'date_resolution' => $ordre->date_resolution?->format('Y-m-d H:i'),
            'urls' => [
                'show' => $url('admin.hebergement.maintenance.show', $ordre->id_ordres_travail),
                'edit' => $ouvert ? $url('admin.hebergement.maintenance.edit', $ordre->id_ordres_travail) : null,
            ],
        ];
    }

    /** Cités, bâtiments et chambres pour les listes en cascade des formulaires. */
    private function structure(): array
    {
        return [
            'cites' => $this->citeService->getCites()->map(fn ($cite) => ['id' => $cite->id_cite, 'name' => $cite->name])->values()->all(),
            'batiments' => Batiment::query()->orderBy('name')->get(['id_batiment', 'cite_id', 'name'])
                ->map(fn ($batiment) => ['id' => $batiment->id_batiment, 'cite_id' => $batiment->cite_id, 'name' => $batiment->name])->values()->all(),
            'chambres' => Chambre::query()->whereNotNull('numero_chambre')->orderBy('numero_chambre')->get(['id_chambre', 'batiment_id', 'numero_chambre'])
                ->map(fn ($chambre) => ['id' => $chambre->id_chambre, 'batiment_id' => $chambre->batiment_id, 'numero' => $chambre->numero_chambre])->values()->all(),
        ];
    }

    private function maintenanciers(): array
    {
        return $this->maintenancierRepository->getAll()->map(fn ($maintenancier) => [
            'id' => $maintenancier->id_maintenancier,
            'name' => $maintenancier->name,
            'specialite' => $maintenancier->specialite,
            'tel' => $maintenancier->tel,
            'email' => $maintenancier->email,
            'cite' => $maintenancier->cite?->name,
        ])->values()->all();
    }

    public function index(Request $request)
    {
        $filters = array_filter($request->only(['statut', 'priorite', 'categorie', 'cite_id', 'search']), fn ($valeur) => filled($valeur));
        $filters = array_intersect_key($filters, array_flip(['priorite', 'categorie', 'cite_id', 'search']));
        if (isset($filters['priorite']) && ! in_array($filters['priorite'], OrdreTravail::PRIORITES, true)) {
            unset($filters['priorite']);
        }
        if (isset($filters['categorie']) && ! array_key_exists($filters['categorie'], OrdreTravail::CATEGORIES_INTERVENTIONS)) {
            unset($filters['categorie']);
        }

        $url = $this->urlSiAutorise($request);
        $ordres = $this->service->getPaginated(array_merge($filters, ['completed' => false]), 10)->withQueryString()
            ->through(fn (OrdreTravail $ordre) => $this->ligne($ordre, $url));
        $ordresTermines = $this->service->getPaginated(array_merge($filters, ['completed' => true, 'page_name' => 'termines_page']), 10)->withQueryString()
            ->through(fn (OrdreTravail $ordre) => $this->ligne($ordre, $url));
        $stats = $this->service->getStats($filters);
        $cites = $this->citeService->getCites();

        // Synthèse par cité (mêmes calculs que l'ancien écran).
        $statsParCite = $cites->map(function ($cite) use ($filters) {
            $citeFilters = array_merge($filters, ['cite_id' => $cite->id_cite]);
            $citeStats = $this->service->getStats($citeFilters);
            $topCause = $this->service->getAll($citeFilters)->filter(fn ($ordre) => ! empty($ordre->cause))
                ->groupBy('cause')
                ->sortByDesc(fn ($group) => $group->count())
                ->keys()
                ->first();

            return [
                'id' => $cite->id_cite,
                'name' => $cite->name,
                'total' => (int) $citeStats['total'],
                'termine' => (int) $citeStats['termine'],
                'urgent' => (int) $citeStats['urgent'],
                'cout_total' => (float) $citeStats['cout_total'],
                'temps_moyen' => round((float) $citeStats['temps_moyen_resolution'], 1),
                'taux_resolution' => $citeStats['total'] > 0 ? (int) round(($citeStats['termine'] / $citeStats['total']) * 100) : 0,
                'principale_cause' => $topCause ? ucfirst(str_replace('_', ' ', $topCause)) : null,
            ];
        })->values()->all();

        return Inertia::render('Admin/Hebergement/Maintenance/Index', [
            'ordres' => $ordres,
            'ordresTermines' => $ordresTermines,
            'stats' => [
                'total' => (int) $stats['total'],
                'en_attente' => (int) $stats['en_attente'],
                'en_cours' => (int) $stats['en_cours'],
                'en_attente_pieces' => (int) $stats['en_attente_pieces'],
                'termine' => (int) $stats['termine'],
                'urgent' => (int) $stats['urgent'],
                'cout_total' => (float) $stats['cout_total'],
                'temps_moyen' => round((float) $stats['temps_moyen_resolution'], 1),
                'satisfaction' => (int) ($stats['taux_satisfaction'] ?? 0),
                'satisfaction_count' => (int) ($stats['satisfaction_count'] ?? 0),
            ],
            'statsParCite' => $statsParCite,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'cite_id' => $filters['cite_id'] ?? '',
                'priorite' => $filters['priorite'] ?? '',
                'categorie' => $filters['categorie'] ?? '',
            ],
            'categories' => $this->categories(),
            'specialites' => Maintenancier::SPECIALITE,
            'maintenanciers' => $this->maintenanciers(),
            'structure' => $this->structure(),
            'urls' => [
                'index' => route('admin.hebergement.maintenance.index'),
                'store' => $url('admin.hebergement.maintenance.store_ot'),
                'storeMaintenancier' => $url('admin.hebergement.maintenance.store_maintenancier'),
                'rapport' => $url('admin.hebergement.reporting.module', ['module' => 'vie-cite']),
            ],
        ]);
    }

    public function storeMaintenancier(MaintenancierRequest $request)
    {
        try {
            $maintenancier = $this->maintenancierRepository->create($request->validated());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Maintenancier créé avec succès',
                    'data' => $maintenancier,
                ]);
            }

            return back()->with('success', 'Maintenancier enregistré avec succès.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la création: '.\App\Support\UserFacingErrors::caught($e),
                ], 500);
            }

            return back()
                ->withInput()
                ->with('error', 'Erreur lors de la création : '.\App\Support\UserFacingErrors::caught($e));
        }
    }

    public function storeIntervention(Request $request)
    {
        try {
            $this->interventionService->createIntervention($request->only([
                'id_ordres_travail', 'date_intervention', 'duree', 'description', 'technicien_id', 'resultat', 'remarques',
            ]));

            return back()->with('success', 'Intervention enregistrée avec succès.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return back()->with('error', \App\Support\UserFacingErrors::caught($e));
        }
    }

    /**
     * Créer un ordre de travail depuis l'espace admin.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'titre' => ['nullable', 'string', 'max:255'],
            'cite_id' => ['nullable', 'string', 'exists:cite,id_cite'],
            'batiment_id' => ['nullable', 'string', 'exists:batiment,id_batiment'],
            'chambre_id' => ['nullable', 'string', 'exists:chambre,id_chambre'],
            'categorie' => ['required', Rule::in(OrdreTravail::CATEGORIES)],
            'priorite' => ['required', Rule::in(OrdreTravail::PRIORITES)],
            'type_intervention' => ['nullable', Rule::in(OrdreTravail::TYPE_INTERVENTIONS)],
            'description' => ['required', 'string', 'max:5000'],
            'assigne_a' => ['nullable', 'string', 'exists:maintenanciers,id_maintenancier'],
            'date_debut_intervention' => ['nullable', 'date'],
            'date_fin_intervention' => ['nullable', 'date', 'after_or_equal:date_debut_intervention'],
        ], [
            'categorie.required' => 'Choisissez le type d’intervention.',
            'priorite.required' => 'Choisissez la priorité.',
            'description.required' => 'Décrivez le problème à résoudre.',
            'date_fin_intervention.after_or_equal' => 'La fin prévue doit être postérieure au début.',
        ]);

        if (! empty($data['batiment_id']) && ! empty($data['cite_id'])
            && ! Batiment::where('id_batiment', $data['batiment_id'])->where('cite_id', $data['cite_id'])->exists()) {
            throw ValidationException::withMessages(['batiment_id' => 'Le bâtiment sélectionné n’appartient pas à cette cité.']);
        }
        if (! empty($data['chambre_id']) && ! empty($data['batiment_id'])
            && ! Chambre::where('id_chambre', $data['chambre_id'])->where('batiment_id', $data['batiment_id'])->exists()) {
            throw ValidationException::withMessages(['chambre_id' => 'La chambre sélectionnée n’appartient pas à ce bâtiment.']);
        }

        try {
            if (! empty($data['assigne_a'])) {
                $data['statut'] = 'en_cours';
            } else {
                $data['statut'] = 'en_attente';
                $data['date_debut_intervention'] = null;
                $data['date_fin_intervention'] = null;
                $data['assigne_a'] = null;
            }
            $data['type_intervention'] ??= 'corrective';

            $ordre = $this->service->create($data);

            Log::info("Ordre de travail créé depuis l'administration : {$ordre->numero_ot}");

            return back()->with('success', 'Ordre de travail '.$ordre->numero_ot.' créé avec succès.');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de l\'OT: '.$e->getMessage());

            return back()
                ->withInput()
                ->with('error', 'Erreur lors de la création : '.\App\Support\UserFacingErrors::caught($e));
        }
    }

    /**
     * Afficher les détails d'un ordre de travail
     */
    public function show(Request $request, string $id)
    {
        $ordre = $this->service->findById($id);
        if (! $ordre) {
            abort(404, 'Ordre de travail non trouvé');
        }

        $url = $this->urlSiAutorise($request);
        $cle = $ordre->id_ordres_travail;
        $ouvert = ! in_array($ordre->statut, ['termine', 'annule'], true);
        $auteur = $ordre->created_by
            ? ($ordre->createdBy?->name ?? User::where('id_user', $ordre->created_by)->value('name') ?? 'Inconnu')
            : 'Étudiant';
        $urlStatut = $url('admin.hebergement.maintenance.statut', $cle);
        // Changements de statut proposés (l'annulation a son propre bouton).
        $transitions = $urlStatut
            ? array_values(array_diff(OrdreTravailService::TRANSITIONS[$ordre->statut] ?? [], ['annule']))
            : [];
        // « Démarrer » sans technicien n'a pas de sens : l'assignation fait déjà passer en cours.
        if ($ordre->statut === 'en_attente') {
            $transitions = [];
        }

        return Inertia::render('Admin/Hebergement/Maintenance/Show', [
            'ordre' => [
                'id' => $cle,
                'numero' => $ordre->numero_ot,
                'titre' => $ordre->titre,
                'description' => $ordre->description,
                'cause' => $ordre->cause,
                'solution' => $ordre->solution_apportee,
                'instructions' => $ordre->instructions,
                'categorie_label' => $this->libelleCategorie($ordre->categorie),
                'type_label' => $ordre->type_intervention ? $ordre->type_intervention_label : null,
                'priorite' => $ordre->priorite,
                'priorite_label' => $ordre->priorite_label,
                'statut' => $ordre->statut,
                'statut_label' => $ordre->statut_label,
                'cout_estime' => $ordre->cout_estime !== null ? (float) $ordre->cout_estime : null,
                'cout_reel' => $ordre->cout_reel !== null ? (float) $ordre->cout_reel : null,
                'temps_passe' => $ordre->temps_passe,
                'date_echeance' => $ordre->date_echeance?->toDateString(),
                'residence' => $ordre->residence?->name,
                'batiment' => $ordre->batiment?->name,
                'chambre' => $ordre->chambre?->numero_chambre,
                'equipement' => $ordre->equipement?->name,
                'resident' => $ordre->resident?->name,
                'resident_tel' => $ordre->resident?->telephone1,
                'auteur' => $auteur,
                'created_at' => $ordre->created_at?->format('Y-m-d H:i'),
                'updated_at' => $ordre->updated_at?->format('Y-m-d H:i'),
                'date_resolution' => $ordre->date_resolution?->format('Y-m-d H:i'),
                'technicien' => $ordre->assigneA ? [
                    'name' => $ordre->assigneA->name,
                    'specialite' => $ordre->assigneA->specialite,
                    'tel' => $ordre->assigneA->tel,
                    'email' => $ordre->assigneA->email,
                ] : null,
                'date_debut_intervention' => $ordre->date_debut_intervention?->format('Y-m-d H:i'),
                'date_fin_intervention' => $ordre->date_fin_intervention?->format('Y-m-d H:i'),
                'satisfaction' => $ordre->taux_satisfaction,
                'commentaire_satisfaction' => $ordre->commentaire_satisfaction,
                'interventions' => $ordre->interventions->sortBy('date_intervention')->map(fn ($intervention) => [
                    'id' => $intervention->id_intervention,
                    'date' => $intervention->date_intervention ? \Carbon\Carbon::parse($intervention->date_intervention)->format('Y-m-d H:i') : null,
                    'description' => $intervention->description,
                    'resultat' => $intervention->resultat,
                    'duree' => $intervention->duree,
                    'remarques' => $intervention->remarques,
                    'satisfaction' => $intervention->taux_satisfaction,
                ])->values(),
            ],
            'maintenanciers' => $this->maintenanciers(),
            'transitions' => $transitions,
            'urls' => [
                'index' => $url('admin.hebergement.maintenance.index'),
                'edit' => $ouvert ? $url('admin.hebergement.maintenance.edit', $cle) : null,
                'assigner' => $ordre->statut === 'en_attente' && ! $ordre->assigne_a ? $url('admin.hebergement.maintenance.assigner', $cle) : null,
                'statut' => $urlStatut,
                'annuler' => $ouvert && $ordre->statut !== 'reporte' ? $url('admin.hebergement.maintenance.annuler', $cle) : null,
                'intervention' => $ouvert && $ordre->statut !== 'en_attente' ? $url('admin.hebergement.maintenance.intervention.store') : null,
                'satisfaction' => $ordre->statut === 'termine' && ! $ordre->taux_satisfaction
                    && $ordre->interventions->whereNull('taux_satisfaction')->isNotEmpty()
                    ? $url('admin.hebergement.maintenance.satisfaction') : null,
            ],
        ]);
    }

    /**
     * Afficher le formulaire de modification
     */
    public function edit(Request $request, string $id)
    {
        $ordre = $this->service->findById($id);
        if (! $ordre) {
            abort(404, 'Ordre de travail non trouvé');
        }

        if (in_array($ordre->statut, ['termine', 'annule'])) {
            return redirect()
                ->route('admin.hebergement.maintenance.show', $id)
                ->with('error', 'Cet ordre de travail est clôturé et ne peut pas être modifié.');
        }

        $url = $this->urlSiAutorise($request);

        return Inertia::render('Admin/Hebergement/Maintenance/Edit', [
            'ordre' => [
                'id' => $ordre->id_ordres_travail,
                'numero' => $ordre->numero_ot,
                'statut_label' => $ordre->statut_label,
                'titre' => (string) $ordre->titre,
                'description' => (string) $ordre->description,
                'categorie' => (string) $ordre->categorie,
                'priorite' => (string) $ordre->priorite,
                'type_intervention' => (string) ($ordre->type_intervention ?: 'corrective'),
                'date_echeance' => $ordre->date_echeance?->toDateString() ?? '',
                'cause' => (string) $ordre->cause,
                'cite_id' => (string) $ordre->cite_id,
                'batiment_id' => (string) $ordre->batiment_id,
                'chambre_id' => (string) $ordre->chambre_id,
                'equipement_id' => (string) $ordre->equipement_id,
                'cout_estime' => $ordre->cout_estime !== null ? (string) (float) $ordre->cout_estime : '',
                'cout_reel' => $ordre->cout_reel !== null ? (string) (float) $ordre->cout_reel : '',
                'temps_passe' => $ordre->temps_passe !== null ? (string) $ordre->temps_passe : '',
            ],
            'categories' => $this->categories(),
            'structure' => $this->structure(),
            'equipements' => Equipement::query()->orderBy('name')->get(['equipement_id', 'name'])
                ->map(fn ($equipement) => ['id' => $equipement->equipement_id, 'name' => $equipement->name])->values(),
            'urls' => [
                'update' => $url('admin.hebergement.maintenance.update', $ordre->id_ordres_travail),
                'show' => $url('admin.hebergement.maintenance.show', $ordre->id_ordres_travail),
            ],
        ]);
    }

    /**
     * Mettre à jour un ordre de travail
     */
    public function update(OrdreTravailRequest $request, string $id)
    {
        try {
            $ordre = $this->service->findById($id);
            if (! $ordre) {
                abort(404, 'Ordre de travail non trouvé');
            }

            $ordre = $this->service->update($id, $request->validated());

            return redirect()
                ->route('admin.hebergement.maintenance.show', $ordre->id_ordres_travail)
                ->with('success', 'Ordre de travail mis à jour avec succès.');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour de l\'OT: '.$e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour : '.\App\Support\UserFacingErrors::caught($e));
        }
    }

    /**
     * Assigner un technicien à un ordre de travail
     */
    public function assigner(Request $request, string $id)
    {
        $data = $request->validate([
            'assigne_a' => ['required', 'exists:maintenanciers,id_maintenancier'],
            'date_debut_intervention' => ['nullable', 'date'],
            'date_fin_intervention' => ['nullable', 'date', 'after_or_equal:date_debut_intervention'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'notification_envoyee' => ['nullable', 'boolean'],
        ], [
            'assigne_a.required' => 'Choisissez le maintenancier à assigner.',
            'date_fin_intervention.after_or_equal' => 'La fin prévue doit être postérieure au début.',
        ]);

        try {
            $ordre = $this->service->assigner($id, $data);

            return redirect()
                ->route('admin.hebergement.maintenance.show', $ordre->id_ordres_travail)
                ->with('success', 'Technicien assigné avec succès.');
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'assignation: '.$e->getMessage());

            return redirect()
                ->back()
                ->with('error', 'Erreur lors de l\'assignation : '.\App\Support\UserFacingErrors::caught($e));
        }
    }

    /**
     * Changer le statut d'un ordre de travail
     */
    public function changerStatut(Request $request, string $id)
    {
        $request->validate([
            'statut' => 'required|in:en_attente,en_cours,en_attente_pieces,termine,annule,reporte',
            'solution' => 'nullable|string|max:5000',
        ]);

        try {
            $ordre = $this->service->updateStatut($id, $request->statut, $request->solution);

            return redirect()
                ->route('admin.hebergement.maintenance.show', $ordre->id_ordres_travail)
                ->with('success', 'Statut mis à jour avec succès.');
        } catch (\Exception $e) {
            Log::error('Erreur lors du changement de statut: '.$e->getMessage());

            return redirect()
                ->back()
                ->with('error', 'Erreur lors du changement de statut : '.\App\Support\UserFacingErrors::caught($e));
        }
    }

    /**
     * Annuler un ordre de travail (route `annuler`, même règle de transition que `changerStatut`).
     */
    public function annuler(Request $request, string $id)
    {
        try {
            $ordre = $this->service->updateStatut($id, 'annule');

            return redirect()
                ->route('admin.hebergement.maintenance.show', $ordre->id_ordres_travail)
                ->with('success', 'Ordre de travail annulé.');
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'annulation de l\'OT: '.$e->getMessage());

            return redirect()
                ->back()
                ->with('error', 'Erreur lors de l\'annulation : '.\App\Support\UserFacingErrors::caught($e));
        }
    }

    public function storeSatisfaction(Request $request)
    {
        try {
            $this->interventionService->storeSatisfaction(
                $request->only(['ot_id', 'taux_satisfaction', 'commentaire_satisfaction'])
            );

            return back()->with('success', 'L’avis du résident a été enregistré.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', "Une erreur est survenue pendant l'enregistrement de l'avis.");
        }
    }
}
