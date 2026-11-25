<?php

namespace App\Http\Controllers\admin\immobilier\residents_contrat;

use App\Http\Controllers\Controller;
use App\Http\Services\CampagneValidationService;
use App\Http\Services\EtudiantEditionService;
use App\Http\Services\EtudiantService;
use App\Http\Services\PaiementsAvantAttribution;
use App\Models\AssignationChambre;
use App\Models\Campagne;
use App\Models\Etudiant;
use App\Repositories\EtudiantRepository;
use App\Services\AdminAccess;
use App\Services\AdminViewAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ResidentContratControler extends Controller
{
    /** Filtres de la page : admis à loger (liste définitive publiée) et étudiants déjà logés. */
    private const FILTRES = ['admis_a_loger', 'residents'];

    /** Statut d'inscription (`EtudiantRepository`) de chaque filtre. */
    private const STATUTS = ['admis_a_loger' => 'en_verification', 'residents' => 'residents'];

    public function __construct(protected EtudiantService $etudiantService) {}

    public function liste(Request $request, ?string $etat = null)
    {
        // Ancienne adresse de la liste « Admis à loger » (liens enregistrés).
        if ($etat === 'en_verification' && ! $request->wantsJson()) {
            return redirect()->route('admin.hebergement.residents_contrat.admission', ['etat' => 'admis_a_loger'] + $request->query());
        }

        try {
            if (! $request->wantsJson()) {
                return $this->pageResidents($request, $etat ?? $request->get('etat'));
            }

            $etat ??= $request->get('etat');
            $result = $this->etudiantService->getListeEtudiantsWithStats(self::STATUTS[$etat] ?? $etat);

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
        // Campagne choisie dans le bandeau (`?campagne=`) : par défaut la campagne active, `toutes` = aucun filtre.
        $campagnes = Campagne::query()->orderByDesc('created_at')->get();
        $choix = (string) $request->query('campagne', '');
        $toutes = $choix === 'toutes';
        $campagne = $toutes ? null : (($choix !== '' ? $campagnes->first(fn (Campagne $c) => (string) $c->getKey() === $choix) : null)
            ?? app(CampagneValidationService::class)->getCampagneActive()
            ?? $campagnes->first());
        $campagneId = $campagne ? (string) $campagne->getKey() : null;
        // Choix conservé par les liens des indicateurs quand il a été fait dans le bandeau.
        $choixCampagne = $request->filled('campagne') ? ['campagne' => $toutes ? 'toutes' : $campagneId] : [];

        $recherche = trim((string) $request->query('q', ''));
        $page = $repository->getAdmissionsPage(self::STATUTS[$etat], 10, $recherche, null, null, $campagneId);
        $page->getCollection()->load(['assignationsChambre' => fn ($q) => $q->where('statut', 'confirmee')
            ->with(['chambre.batiment', 'chambre.cite'])]);

        // Admis à loger : paiements exigés avant l'attribution (caution et avance, ou avance en réadmission).
        $admis = $etat === 'admis_a_loger';
        $paiements = [];
        if ($admis) {
            $page->getCollection()->load('derniereDemande.campagne');
            $paiements = app(PaiementsAvantAttribution::class)->etatsAvantChambre($page->getCollection());
        }
        $montantsVisibles = $access->allows($admin, 'admin.hebergement.paiements.index');

        return Inertia::render('Admin/Hebergement/Residents/Index', [
            'residents' => $page->through(function (Etudiant $etudiant) use ($personnel, $historique, $url, $etat, $admis, $paiements, $montantsVisibles) {
                /** @var AssignationChambre|null $assignation */
                $assignation = $etudiant->assignationsChambre->sortByDesc('created_at')->first();
                $chambre = $assignation?->chambre;
                $paiement = $paiements[(string) $etudiant->getKey()] ?? null;

                return [
                    'id' => (string) $etudiant->id_etudiant,
                    'name' => $etudiant->name,
                    'numero_demande' => $etudiant->derniereDemande?->numero_demande,
                    'date' => $etudiant->created_at?->format('Y-m-d H:i'),
                    'personnel' => $personnel ? [
                        'telephone' => $etudiant->telephone1,
                        'date_naissance' => $etudiant->date_naissance?->toDateString(),
                    ] : null,
                    // Résidents logés seulement : nombre de demandes, chambre et contrat.
                    'demandes' => $historique && $etat === 'residents' ? (int) $etudiant->demandes_count : null,
                    'chambre' => $assignation ? [
                        'numero' => $chambre?->numero_chambre,
                        'lieu' => collect([$chambre?->batiment?->name, $chambre?->cite?->name])->filter()->implode(' · ') ?: null,
                    ] : null,
                    // Admis à loger seulement : montants envoyés avec le droit de consulter les paiements.
                    'paiement' => $paiement ? [
                        'readmission' => $paiement['volet'] === 'readmission',
                        'types' => collect($paiement['types'])->map(fn (array $solde, string $type) => [
                            'type' => $type,
                            'statut' => $solde['reste'] <= 0 ? 'paye' : ($solde['verse'] > 0 ? 'partiel' : 'non_paye'),
                            'verse' => $montantsVisibles ? $solde['verse'] : null,
                            'exige' => $montantsVisibles ? $solde['exige'] : null,
                        ])->values()->all(),
                    ] : null,
                    'urls' => [
                        'dossier' => $url('admin.hebergement.campagnes_admission.show', ['id' => $etudiant->id_etudiant]),
                        // Étudiant admis à loger : attribution de la chambre (formulaire présélectionné sur l'étudiant),
                        // une fois payées la caution et l'avance exigées.
                        'attribuer' => $admis && ($paiement['paye'] ?? false) && $etudiant->derniereDemande?->etat_dossier === 'confirme'
                            ? $url('admin.allocation.allocate-form', [
                                'campagneId' => $etudiant->derniereDemande->campagne_id,
                                'etudiant_id' => $etudiant->id_etudiant,
                            ])
                            : null,
                        'contrat' => $etat === 'residents' ? $url('admin.etudiant.contrat', ['etudiant' => $etudiant->id_etudiant]) : null,
                    ],
                ];
            }),
            'stats' => [
                'residents' => $repository->compterAdmissions('residents', $campagneId),
                'admis_a_loger' => $repository->compterAdmissions(self::STATUTS['admis_a_loger'], $campagneId),
            ],
            'etat' => $etat,
            'filters' => ['q' => $recherche, 'campagne' => $toutes ? 'toutes' : (string) $campagneId],
            'colonnes' => ['personnel' => $personnel, 'historique' => $historique],
            // Campagnes proposées dans le bandeau (libellé et année), la plus récente d'abord.
            'campagnes' => $campagnes->map(fn (Campagne $option) => [
                'id' => (string) $option->getKey(),
                'libelle' => $option->libelle,
                'annee' => $option->annee_campagne,
                'active' => $option->statut === 'active',
            ])->values()->all(),
            // Campagne choisie (`null` : toutes les campagnes).
            'campagneActive' => $campagne ? ['id' => $campagneId, 'annee' => $campagne->annee_campagne] : null,
            'urls' => [
                'admissionDirecte' => $url('admin.etudiant.admission_directe'),
                // Suivi des attributions de la campagne choisie (toutes les campagnes : campagne active).
                'attributions' => ($suivi = $campagne ?? app(CampagneValidationService::class)->getCampagneActive())
                    ? $url('admin.allocation.show', ['campagneId' => $suivi->id_campagne]) : null,
                'filtres' => $access->allows($admin, 'admin.hebergement.residents_contrat.admission')
                    ? collect(self::FILTRES)->mapWithKeys(fn ($f) => [$f => route('admin.hebergement.residents_contrat.admission', ['etat' => $f] + $choixCampagne)])
                    : null,
            ],
        ]);
    }

    /**
     * Modification des informations d'un étudiant (`EtudiantEditionService`) ; plus proposée dans la liste des résidents.
     * Le statut, le dossier et l'affectation ne sont pas modifiables ici (cycle géré par `DossierWorkflow`).
     */
    public function updateEtudiant(Request $request, string $etudiant, EtudiantEditionService $edition): RedirectResponse
    {
        $edition->mettreAJour($request, Etudiant::with('derniereDemande')->findOrFail($etudiant));

        return back()->with('success', 'Informations de l’étudiant mises à jour.');
    }
}
