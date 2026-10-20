<?php

namespace App\Http\Controllers\admin\immobilier\residents_contrat;

use App\Http\Controllers\Controller;
use App\Http\Services\CampagneValidationService;
use App\Http\Services\EtudiantEditionService;
use App\Http\Services\EtudiantService;
use App\Models\AssignationChambre;
use App\Models\Etudiant;
use App\Repositories\EtudiantRepository;
use App\Services\AdminAccess;
use App\Services\AdminViewAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ResidentContratControler extends Controller
{
    /** Filtres de la page : dossiers en vérification et étudiants déjà logés. */
    private const FILTRES = ['en_verification', 'residents'];

    public function __construct(protected EtudiantService $etudiantService) {}

    public function liste(Request $request, ?string $etat = null)
    {
        try {
            if (! $request->wantsJson()) {
                return $this->pageResidents($request, $etat ?? $request->get('etat'));
            }

            $result = $this->etudiantService->getListeEtudiantsWithStats($etat ?? $request->get('etat'));

            return response()->json(app(\App\Services\AdminViewData::class)->filter($result, $request->user('admin')));
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la récupération de la liste',
                    'error' => \App\Support\UserFacingErrors::caught($e),
                ], 500);
            }

            return redirect()->back()->with('error', 'Erreur lors de la récupération de la liste');
        }
    }

    /**
     * Résidents et contrats (page React `Admin/Hebergement/Residents/Index`).
     * Colonnes personnelles et historique envoyés seulement avec les droits correspondants ;
     * chaque URL d'action n'est envoyée que si `AdminAccess` l'autorise.
     */
    private function pageResidents(Request $request, ?string $filtre)
    {
        $admin = $request->user('admin');
        $access = app(AdminAccess::class);
        $vues = app(AdminViewAccess::class);
        $personnel = $vues->allows($admin, 'admissions.personal');
        $historique = $vues->allows($admin, 'admissions.history');
        $etat = in_array($filtre, self::FILTRES, true) ? $filtre : 'residents';
        $url = fn (string $route, array $parametres = []) => $access->allows($admin, $route) ? route($route, $parametres) : null;
        $repository = app(EtudiantRepository::class);
        $campagne = app(CampagneValidationService::class)->getCampagneActive();

        $recherche = trim((string) $request->query('q', ''));
        $page = $repository->getAdmissionsPage($etat, 10, $recherche);
        $page->getCollection()->load(['assignationsChambre' => fn ($q) => $q->where('statut', 'confirmee')
            ->with(['chambre.batiment', 'chambre.cite'])]);

        $modifiable = $access->allows($admin, 'admin.hebergement.residents_contrat.update_etudiant');

        $stats = $repository->getStats();

        return Inertia::render('Admin/Hebergement/Residents/Index', [
            'residents' => $page->through(function (Etudiant $etudiant) use ($personnel, $historique, $url, $etat, $modifiable) {
                /** @var AssignationChambre|null $assignation */
                $assignation = $etudiant->assignationsChambre->sortByDesc('created_at')->first();
                $chambre = $assignation?->chambre;

                return [
                    'id' => (string) $etudiant->id_etudiant,
                    'name' => $etudiant->name,
                    'numero_demande' => $etudiant->derniereDemande?->numero_demande,
                    'date' => $etudiant->created_at?->format('Y-m-d H:i'),
                    'personnel' => $personnel ? [
                        'telephone' => $etudiant->telephone1,
                        'adresse' => $etudiant->adresse,
                        'region' => $etudiant->region?->name,
                        'ville' => $etudiant->ville?->name,
                        'date_naissance' => $etudiant->date_naissance?->toDateString(),
                    ] : null,
                    'demandes' => $historique ? (int) $etudiant->demandes_count : null,
                    'chambre' => $assignation ? [
                        'numero' => $chambre?->numero_chambre,
                        'lieu' => collect([$chambre?->batiment?->name, $chambre?->cite?->name])->filter()->implode(' · ') ?: null,
                    ] : null,
                    'urls' => [
                        'dossier' => $url('admin.hebergement.campagnes_admission.show', ['id' => $etudiant->id_etudiant]),
                        'traiter' => $etat === 'en_verification'
                            ? $url('admin.hebergement.campagnes_admission.show', ['id' => $etudiant->id_etudiant])
                            : null,
                        // Dossier confirmé par l'étudiant : attribution de la chambre (formulaire présélectionné sur l'étudiant).
                        'attribuer' => $etat === 'en_verification' && $etudiant->derniereDemande?->etat_dossier === 'confirme'
                            ? $url('admin.allocation.allocate-form', [
                                'campagneId' => $etudiant->derniereDemande->campagne_id,
                                'etudiant_id' => $etudiant->id_etudiant,
                            ])
                            : null,
                        'contrat' => $url('admin.etudiant.contrat', ['etudiant' => $etudiant->id_etudiant]),
                        'modifier' => $modifiable
                            ? route('admin.hebergement.residents_contrat.update_etudiant', ['etudiant' => $etudiant->id_etudiant])
                            : null,
                    ],
                    // Valeurs du formulaire de modification : envoyées seulement avec le droit de modifier.
                    'edition' => $modifiable ? app(EtudiantEditionService::class)->valeurs($etudiant) : null,
                ];
            }),
            'stats' => [
                'residents' => $stats['residents'],
                'en_verification' => $stats['en_verification'],
            ],
            'etat' => $etat,
            'filters' => ['q' => $recherche],
            'colonnes' => ['personnel' => $personnel, 'historique' => $historique],
            'campagneActive' => $campagne ? ['annee' => $campagne->annee_campagne] : null,
            // Référentiels du formulaire de modification : envoyés seulement avec le droit de modifier.
            'options' => $modifiable ? app(EtudiantEditionService::class)->options() : null,
            'urls' => [
                'admissionDirecte' => $url('admin.etudiant.admission_directe'),
                'attributions' => $campagne ? $url('admin.allocation.show', ['campagneId' => $campagne->id_campagne]) : null,
                'filtres' => $access->allows($admin, 'admin.hebergement.residents_contrat.admission')
                    ? collect(self::FILTRES)->mapWithKeys(fn ($f) => [$f => route('admin.hebergement.residents_contrat.admission', ['etat' => $f])])
                    : null,
            ],
        ]);
    }

    /**
     * Modification de toutes les informations d'un étudiant depuis la liste des résidents (`EtudiantEditionService`).
     * Le statut, le dossier et l'affectation ne sont pas modifiables ici (cycle géré par `DossierWorkflow`).
     */
    public function updateEtudiant(Request $request, string $etudiant, EtudiantEditionService $edition): RedirectResponse
    {
        $edition->mettreAJour($request, Etudiant::with('derniereDemande')->findOrFail($etudiant));

        return back()->with('success', 'Informations de l’étudiant mises à jour.');
    }
}
