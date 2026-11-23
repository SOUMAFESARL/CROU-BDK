<?php
// app/Http/Controllers/admin/immobilier/vie_residentielle/PaiementController.php

namespace App\Http\Controllers\admin\immobilier\vie_residentielle;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Services\PaiementService;
use App\Models\Campagne;
use App\Models\Cite;
use App\Models\Loyer;
use App\Models\Paiement;
use App\Models\TypePaiement;
use App\Models\Etudiant;
use Carbon\Carbon;
use App\Models\AssignationChambre;
use App\Http\Services\EtudiantService;
use App\Http\Services\LoyerService;
use App\Http\Services\CiteService;
use App\Services\AdminAccess;
use Inertia\Inertia;

class PaiementController extends Controller
{
    protected $paiementService;
    protected $etudiantService;
     protected  $loyerService;
     protected $citeService;

    public function __construct(PaiementService $paiementService,EtudiantService $etudiantService,
    LoyerService $loyerService,CiteService $citeService)
    {
        $this->paiementService = $paiementService;
        $this->etudiantService = $etudiantService;
         $this->loyerService = $loyerService;
         $this->citeService = $citeService;


    }

   

    public function index(Request $request)
{
    $filters = $request->only(['residence', 'statut', 'type', 'mois', 'recherche', 'date_debut', 'date_fin']);

    // Campagne affichée (liste, indicateurs, récapitulatif) : `?campagne=<id>`, `toutes`, ou par défaut la campagne active.
    $campagnes = Campagne::query()->orderByDesc('created_at')->get(['id_campagne', 'libelle', 'annee_campagne', 'statut']);
    $campagne = $this->campagneChoisie($request, $campagnes);
    $filters['campagne'] = $campagne?->id_campagne;
    $choixCampagne = $campagne ? (string) $campagne->id_campagne : self::TOUTES_CAMPAGNES;

    $paiements = $this->paiementService->getPaiementsPaginated($filters, 20);
    $residences = Cite::orderBy('name')->get();
    $residents = $this->etudiantService->getAll('name', 'asc');
    
 //   dd($statsParResidence);
    // ============================================================
    // STATISTIQUES À PARTIR DE LA TABLE loyers (correct)
    // ============================================================
    $statsLoyers = $this->loyerService->getStatistiquesLoyers(null, null, null, $filters['campagne']);
    
    // Utiliser les bonnes valeurs
    $totalDu = $statsLoyers['total_montant'];
    $totalEncaisse = $statsLoyers['total_paye'];
    $impayes = $statsLoyers['total_solde_restant'];
    $tauxRecouvrement = $statsLoyers['taux_recouvrement'];
    $nombreRetard = $statsLoyers['nombre_en_retard'];
    $totalPenalites = $statsLoyers['total_penalites'];
    
    // ============================================================
    // STATISTIQUES DE LA PAGE (sur les paiements affichés)
    // ============================================================
    $paiementsPage = $paiements->getCollection();
    $totalEncaissePage = $paiementsPage->where('statut_paiement', 'valide')->sum('montant');
    $totalDuPage = $paiementsPage->sum('montant_du');
    $impayesPage = $totalDuPage - $totalEncaissePage;
    $tauxPage = $totalDuPage > 0 ? round(($totalEncaissePage / $totalDuPage) * 100, 1) : 0;
    $retardPage = $paiementsPage->where('statut_paiement', '!=', 'valide')
        ->where('date_echeance', '<', now())
        ->count();
    $url = $this->urlSiAutorise($request);
    $nombrePaiements = Paiement::query()
        ->when($filters['campagne'], fn ($query, $id) => $query->deCampagne($id))
        ->whereMonth('date_paiement', now()->month)
        ->whereYear('date_paiement', now()->year)
        ->count();

    return Inertia::render('Admin/Hebergement/Paiements/Index', [
        'paiements' => $paiements->appends($request->query())->through(fn (Paiement $paiement) => [
            'id' => (string) $paiement->id_paiement,
            'numero' => $paiement->numero_paiement,
            'resident' => $paiement->resident?->name,
            'telephone' => $paiement->resident?->telephone1 ?? $paiement->resident?->telephone2,
            'residence' => $paiement->cite?->name,
            'chambre' => $paiement->chambre?->numero_chambre,
            'mois' => $paiement->mois ? formatPaiementMois($paiement->mois, 0) : null,
            'montant' => (float) $paiement->montant,
            'methode' => $paiement->methode_label,
            'type' => $paiement->type_label,
            'statut' => $paiement->statut_paiement,
            'statut_label' => $paiement->statut_label,
            'date_paiement' => $paiement->date_paiement?->format('Y-m-d H:i'),
            'urls' => [
                'show' => $url('admin.hebergement.paiements.show', ['id' => $paiement->id_paiement]),
                'valider' => $paiement->statut_paiement === 'en_attente' ? $url('admin.hebergement.paiements.valider', ['id' => $paiement->id_paiement]) : null,
                'annuler' => $paiement->statut_paiement === 'en_attente' ? $url('admin.hebergement.paiements.annuler', ['id' => $paiement->id_paiement]) : null,
            ],
        ]),
        'kpis' => [
            'total_encaisse' => (float) $totalEncaisse,
            'taux_recouvrement' => (float) $tauxRecouvrement,
            'impayes' => (float) $impayes,
            'penalites' => (float) $totalPenalites,
            'nombre_paiements' => (int) $nombrePaiements,
            'nombre_retard' => (int) $nombreRetard,
        ],
        'residences' => $residences->map(fn (Cite $cite) => ['id' => (string) $cite->id_cite, 'name' => $cite->name])->values(),
        // Types actifs du paramétrage (Paramétrage hébergement › Types de paiement), proposés dans « Nouveau paiement ».
        'typesPaiement' => TypePaiement::options(),
        'campagnes' => $campagnes->map(fn (Campagne $option) => [
            'id' => (string) $option->id_campagne,
            'libelle' => $option->libelle ?: $option->annee_campagne,
            'annee' => $option->annee_campagne,
            'active' => $option->statut === 'active',
        ])->values(),
        'filters' => [
            'campagne' => $choixCampagne,
            'residence' => (string) ($filters['residence'] ?? ''),
            'statut' => (string) ($filters['statut'] ?? ''),
            'mois' => (string) ($filters['mois'] ?? ''),
        ],
        'urls' => [
            'index' => route('admin.hebergement.paiements.index'),
            'export' => $url('admin.hebergement.paiements.export', ['campagne' => $choixCampagne] + $request->query()),
            'store' => $url('admin.hebergement.paiements.store'),
            'recherche' => $url('admin.hebergement.paiements.recherche'),
            'relances' => $url('admin.hebergement.paiements.relances.envoyer'),
            'rapport' => $url('admin.hebergement.paiements.rapport_financier'),
        ],
    ]);
}

    /** Valeur du paramètre `campagne` qui affiche toutes les campagnes. */
    private const TOUTES_CAMPAGNES = 'toutes';

    /**
     * Campagne choisie (`?campagne=<id>`) ; à défaut (ou identifiant inconnu), la campagne active, sinon la plus récente.
     * `null` pour « Toutes les campagnes » ou s'il n'existe aucune campagne.
     */
    private function campagneChoisie(Request $request, \Illuminate\Support\Collection $campagnes): ?Campagne
    {
        $id = (string) $request->query('campagne', '');
        if ($id === self::TOUTES_CAMPAGNES) {
            return null;
        }

        return ($id !== '' ? $campagnes->first(fn (Campagne $campagne) => (string) $campagne->id_campagne === $id) : null)
            ?? $campagnes->first(fn (Campagne $campagne) => $campagne->statut === 'active')
            ?? $campagnes->first();
    }

    /**
     * URL d'une route admin, ou null si l'administrateur connecté n'y a pas accès (remplace `@adminroute`).
     */
    private function urlSiAutorise(Request $request): \Closure
    {
        $access = app(AdminAccess::class);
        $admin = $request->user('admin');

        return fn (string $route, array $parametres = []) => $access->allows($admin, $route) ? route($route, $parametres) : null;
    }

   

    /**
     * Envoyer les relances pour les impayés
     */
    public function envoyerRelances(Request $request)
{
    $request->validate([
        'mode_envoi' => 'required|in:sms,email,both',
        'message_relance' => 'required|string|min:10',
    ]);

    try {
        // Récupérer TOUS les loyers non totalement payés
        $loyersImpayes = Loyer::with(['etudiant', 'chambre.cite'])
            ->where('solde_restant', '>', 0)
            ->where('statut', '!=', 'annule')
            ->where('statut', '!=', 'termine')
            ->get();

        $compteur = 0;
        $message = $request->message_relance;

        foreach ($loyersImpayes as $loyer) {
            $resident = $loyer->etudiant;
            
            if (!$resident) continue;

            // Remplacer les variables dans le message
            $messagePersonnalise = str_replace(
                ['{nom}', '{montant}', '{mois}', '{residence}', '{chambre}', '{solde_restant}', '{date_limite}'],
                [
                    $resident->name ?? '',
                    
                    number_format($loyer->montant_total, 0, ',', ' ') . ' FCFA',
                    $this->getMoisLibelle($loyer->mois_facturation) . ' ' . $loyer->annee_facturation,
                    $loyer->cite->name ?? 'N/A',
                    $loyer->chambre->numero ?? 'N/A',
                    number_format($loyer->solde_restant, 0, ',', ' ') . ' FCFA',
                    $loyer->date_limit_paiement ? $loyer->date_limit_paiement->format('d/m/Y') : 'N/A',
                ],
                $message
            );

            // Envoyer SMS
            // if (in_array($request->mode_envoi, ['sms', 'both']) && $resident->telephone) {
            //     $this->envoyerSms($resident->telephone, $messagePersonnalise);
            //     \Log::info('Relance SMS envoyée', [
            //         'telephone' => $resident->telephone,
            //         'loyer_id' => $loyer->id_loyer,
            //         'solde' => $loyer->solde_restant
            //     ]);
            // }

            // Envoyer Email
            if (in_array($request->mode_envoi, ['email', 'both']) && $resident->email) {
                $this->envoyerEmail($resident->email, $resident->prenom, $resident->nom, $messagePersonnalise);
                \Log::info('Relance Email envoyée', [
                    'email' => $resident->email,
                    'loyer_id' => $loyer->id_loyer,
                    'solde' => $loyer->solde_restant
                ]);
            }

            // Marquer la relance dans les notes du loyer
            $loyer->notes = ($loyer->notes ? $loyer->notes . "\n" : '') 
                . 'Relance envoyée le ' . now()->format('d/m/Y H:i') . ' - Solde: ' . number_format($loyer->solde_restant, 0, ',', ' ') . ' FCFA';
            $loyer->save();

            $compteur++;
        }

        $messageRetour = $compteur > 0 
            ? $compteur . ' relance(s) envoyée(s) avec succès !'
            : 'Aucun loyer impayé trouvé.';

        return redirect()
            ->route('admin.hebergement.paiements.index')
            ->with('success', $messageRetour);

    } catch (\Exception $e) {
        \Log::error('Erreur envoi relances', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return back()
            ->with('error', 'Erreur lors de l\'envoi des relances : ' . \App\Support\UserFacingErrors::caught($e));
    }
}

   

  
    /**
     * Exporter les paiements
     */
    public function export(Request $request)
    {
        $filters = $request->only(['residence', 'statut', 'type', 'mois', 'date_debut', 'date_fin']);
        // Même campagne que la liste affichée (`campagne` = identifiant, ou `toutes`).
        if ($request->filled('campagne')) {
            $filters['campagne'] = $this->campagneChoisie($request, Campagne::query()->orderByDesc('created_at')->get(['id_campagne', 'statut']))?->id_campagne;
        }
        $paiements = $this->paiementService->exporterPaiements($filters);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="paiements_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function() use ($paiements) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['N° Paiement', 'Date', 'Résident', 'Résidence', 'Chambre', 'Type', 'Montant', 'Montant Dû', 'Méthode', 'Statut', 'Date Échéance']);

            foreach ($paiements as $paiement) {
                fputcsv($file, [
                    $paiement->numero_paiement,
                    $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y') : '-',
                    $paiement->resident->nom_complet ?? 'N/A',
                    $paiement->cite->name ?? 'N/A',
                    $paiement->chambre->numero ?? 'N/A',
                    $paiement->type_label,
                    $paiement->montant,
                    $paiement->montant_du ?? 0,
                    $paiement->methode_label,
                    $paiement->statut_label,
                    $paiement->date_echeance ? $paiement->date_echeance->format('d/m/Y') : '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

 /**
     * Recherche dynamique des résidents
     */
    public function rechercheResidents(Request $request)
    {
        $query = $request->get('q');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }
        
        $residents = Etudiant::with(['assignationActive.campagne', 'assignationActive.chambre.cite', 'assignationActive.chambre.batiment', 'assignationActive.chambre.typeChambre'])
            ->where(function($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('telephone2', 'LIKE', "%{$query}%")
                  ->orWhere('matricule', 'LIKE', "%{$query}%")
                  ->orWhere('telephone1', 'LIKE', "%{$query}%")
                  ->orWhere('email1', 'LIKE', "%{$query}%")
                  ->orWhere('email2', 'LIKE', "%{$query}%");
            })
            ->whereHas('assignationActive', function($q) {
                $q->where('statut', 'confirmee');
            })
            ->limit(15)
            ->get();

    ///    dd($residents);
        return response()->json($residents->map(function($resident) {
            $assignation = $resident->assignationActive;
            $chambre = $assignation ? $assignation->chambre : null;
            $cite = $chambre ? $chambre->cite : null;
             $batiment = $chambre ? $chambre->batiment : null;
            
            // Loyer mensuel du type de chambre (Paramétrage hébergement › Types de chambre) : base des montants de « Nouveau paiement ».
            $loyerMensuel = $this->loyerMensuelChambre($chambre);
            
            // Calcul des arriérés (à adapter selon votre logique métier)
            $arriere = $this->loyerService->calculerArriere($resident->id_etudiant);
            $loyerActuel = $this->paiementService->getLoyerActuel($resident->id_etudiant);
             $montantMois = $loyerActuel ? ($loyerActuel->solde_restant ?? $loyerActuel->montant_total) : 0;
//dd($arriere);
            return [
                'id_etudiant' => $resident->id_etudiant,
                'name' => trim($resident->name ),
                'matricule' => $resident->matricule,
                'telephone' => $resident->telephone1 ?? $resident->telephone2 ?? null,
                'residence' => $cite ? $cite->name : null,
                'residence_id' => $cite ? $cite->id_cite : null,
                 'batiment' => $batiment ? $batiment->name : null,
                'batiment_id' => $batiment ? $batiment->id_batiment : null,
                'chambre' => $chambre ? $chambre->numero_chambre : null,
                'chambre_id' => $chambre ? $chambre->id_chambre : null,
                'contrat_id' => $assignation ? $assignation->id_assignation : null,
                'loyer_mensuel' => $loyerMensuel,
                 'arriere' => $arriere['solde_restant'],
                'montant_mois' => $montantMois,
                'total_du' => $arriere['solde_restant'] + $montantMois,
                'statut_paiement' => $arriere['solde_restant'] > 0 ? 'En retard' : 'À jour',
                // Réadmission : pas de caution (type masqué dans « Nouveau paiement »).
                'readmission' => $this->voletResident($assignation, $resident->id_etudiant) === 'readmission',
                // Mois suivant le dernier mois payé (loyer ou avance) : premier mois proposé dans le champ « Mois » (mm-aaaa).
                'prochain_mois' => $this->paiementService->prochainMoisAPayer($resident->id_etudiant),
            ];
        }));
    }

      /**
     * Récupère les détails d'arriérés d'un étudiant
     */
    public function getArriere($etudiantId)
    {
        $arriere = $this->loyerService->calculerArriere($etudiantId);
        
        return response()->json($arriere);
    }

    /**
     * Récupère tous les étudiants avec leurs arriérés
     */
    public function getTousLesArrieres()
    {
        $etudiants = Etudiant::whereHas('assignationActive')->get();
        $etudiantIds = $etudiants->pluck('id_etudiant')->toArray();
        
        $arrieres = $this->loyerService->getAllEtudiantsAvecArrieres($etudiantIds);
        
        return response()->json($arrieres);
    }

    /**
     * Applique les pénalités à tous les loyers en retard
     */
    public function appliquerPenalites()
    {
        $nombre = $this->loyerService->appliquerPenalites();
        
        return response()->json([
            'success' => true,
            'message' => "{$nombre} loyers ont été pénalisés",
            'nombre' => $nombre
        ]);
    }

    /**
     * Statistiques des loyers
     */
    public function getStatistiques()
    {
        $stats = $this->loyerService->getStatistiques();
        
        return response()->json($stats);
    }

     /**
     * Enregistre un nouveau paiement
     */
    public function store(Request $request)
    {
        // Formulaire « Nouveau paiement » : un ou plusieurs types cochés, chacun avec un nombre de mois (`lignes`).
        // Le montant n'est jamais saisi : nombre de mois × loyer mensuel du type de chambre du résident.
        // Hors du try : les erreurs de saisie reviennent sous les champs du formulaire.
        $validated = $request->validate([
            'resident_id' => 'required|exists:etudiants,id_etudiant',
            'lignes' => 'required|array|min:1',
            'lignes.*.type_paiement' => ['required', 'distinct', \Illuminate\Validation\Rule::in(TypePaiement::codesActifs())],
            'lignes.*.mois' => 'required|integer|min:1|max:' . self::MOIS_MAX,
            // Méthodes limitées aux modes acceptés (Configuration › Paiements) ; « autre » couvre virement et carte.
            'methode_paiement' => ['required', 'in:orange_money,mtn_money,moov_money,especes,autre', function ($attribut, $valeur, $echec) {
                $mode = match ($valeur) { 'especes' => 'cash', 'autre' => null, default => 'mobile' };
                if ($mode && !in_array($mode, systemPaymentMethods(), true)) {
                    $echec('Cette méthode de paiement n’est pas acceptée (Configuration › Paiements).');
                }
            }],
            'chambre_id' => 'required|exists:chambre,id_chambre',
            'cite_id' => 'nullable|exists:cite,id_cite',
            'contrat_id' => 'nullable|exists:assignation_chambres,id_assignation',
            'batiment_id'=> 'required|exists:batiment,id_batiment',
            'notes' => 'nullable|string',
            // Mois cochés pour le loyer et l'avance (mm-aaaa), contrôlés plus bas.
            'mois_concernes' => 'nullable|array',
            'mois_concernes.*' => 'string',
        ], [
            'lignes.required' => 'Cochez au moins un type de paiement.',
            'lignes.min' => 'Cochez au moins un type de paiement.',
            'lignes.*.type_paiement.required' => 'Choisissez le type de paiement.',
            'lignes.*.type_paiement.distinct' => 'Ce type de paiement est coché plusieurs fois.',
            'lignes.*.type_paiement.in' => 'Ce type de paiement n’est pas proposé (Paramétrage hébergement › Types de paiement).',
            'lignes.*.mois.required' => 'Indiquez le nombre de mois.',
            'lignes.*.mois.integer' => 'Le nombre de mois doit être un nombre entier.',
            'lignes.*.mois.min' => 'Au moins 1 mois.',
            'lignes.*.mois.max' => sprintf('%d mois au maximum.', self::MOIS_MAX),
        ]);

        $loyerMensuel = $this->loyerMensuelChambre(\App\Models\Chambre::with('typeChambre')->find($validated['chambre_id']));
        if ($loyerMensuel <= 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'resident_id' => 'Le type de la chambre de ce résident n’a pas de loyer mensuel (Paramétrage hébergement › Types de chambre).',
            ]);
        }

        // Un étudiant en réadmission ne paie pas de caution (même règle de volet que la caution du loyer).
        $assignation = AssignationChambre::with('campagne')->where('etudiant_id', $validated['resident_id'])->where('statut', 'confirmee')->first();
        if (in_array(self::TYPE_CAUTION, array_column($validated['lignes'], 'type_paiement'), true)
            && $this->voletResident($assignation, $validated['resident_id']) === 'readmission') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'lignes' => 'Ce résident est en réadmission : la caution ne s’applique pas.',
            ]);
        }

        // Loyer et avance soldent des loyers mois par mois : les mois cochés doivent se suivre à partir du mois
        // qui suit le dernier mois payé, autant que de mois saisis (loyer d'abord, puis avance).
        $saisies = array_values($validated['lignes']);
        $mensuelles = array_filter($saisies, fn ($ligne) => in_array($ligne['type_paiement'], PaiementService::TYPES_LOYER, true));
        if ($mensuelles) {
            $attendus = $this->paiementService->moisConsecutifs($validated['resident_id'], array_sum(array_column($mensuelles, 'mois')));
            if (array_values($validated['mois_concernes'] ?? []) !== $attendus) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'mois_concernes' => sprintf(
                        'Les mois du loyer et de l’avance doivent se suivre à partir de %s (%d mois cochés attendus).',
                        $attendus[0] ?? '—',
                        count($attendus)
                    ),
                ]);
            }
        }

        try {
            // Loyer puis avance en premier : l'avance prend les mois qui suivent ceux du loyer.
            $rangs = array_flip(PaiementService::TYPES_LOYER);
            usort($saisies, fn ($a, $b) => ($rangs[$a['type_paiement']] ?? 99) <=> ($rangs[$b['type_paiement']] ?? 99));
            $lignes = array_map(fn (array $ligne) => [
                'type_paiement' => $ligne['type_paiement'],
                'montant' => (int) $ligne['mois'] * $loyerMensuel,
            ], $saisies);
            $commun = collect($validated)->except(['lignes', 'mois_concernes'])->all();
            $indexMensuel = collect($lignes)->search(fn ($ligne) => in_array($ligne['type_paiement'], PaiementService::TYPES_LOYER, true));
            $messages = [];

            // Loyer et avance sont contrôlés (arriérés) et répartis sur les loyers ; les autres types sont enregistrés tels quels.
            if ($indexMensuel !== false) {
                $montantMensuel = collect($lignes)->whereIn('type_paiement', PaiementService::TYPES_LOYER)->sum('montant');
                $verification = $this->paiementService->verifierPaiementSuffisant($validated['resident_id'], $montantMensuel);

                if (!$verification['suffisant']) {
                    // Index de la ligne dans l'ordre envoyé par le formulaire (erreur affichée sous son nombre de mois).
                    $indexFormulaire = collect($validated['lignes'])->values()->search(fn ($ligne) => $ligne['type_paiement'] === $lignes[$indexMensuel]['type_paiement']);
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "lignes.{$indexFormulaire}.mois" => sprintf(
                            'Insuffisant : les arriérés s’élèvent à %s FCFA, il faut au moins %d mois.',
                            number_format($verification['arriere_total'], 0, ',', ' '),
                            (int) ceil($verification['arriere_total'] / $loyerMensuel)
                        ),
                    ]);
                }

                $messages[] = $this->getMessageRepartition($verification);
            }

            // Un paiement par type coché, dans une seule transaction.
            $this->paiementService->creerPaiements($commun, $lignes);

            $horsLoyer = collect($lignes)->whereNotIn('type_paiement', PaiementService::TYPES_LOYER)
                ->map(fn ($ligne) => '« ' . TypePaiement::libelle($ligne['type_paiement']) . ' »')
                ->values();
            if ($horsLoyer->isNotEmpty()) {
                $messages[] = sprintf(
                    '%s %s hors loyer.',
                    $horsLoyer->count() > 1 ? 'Paiements' : 'Paiement',
                    $horsLoyer->join(', ', ' et ')
                );
            }
            $messages[] = count($lignes) > 1 ? sprintf('%d paiements enregistrés avec succès.', count($lignes)) : 'Paiement enregistré avec succès.';

            return redirect()
                ->route('admin.hebergement.paiements.index')
                ->with('success', implode(' ', array_filter($messages)));

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Erreur lors de l\'enregistrement du paiement: ' . \App\Support\UserFacingErrors::caught($e))
                ->withInput();
        }
    }

    /** Nombre de mois maximum par type coché dans « Nouveau paiement ». */
    public const MOIS_MAX = 12;

    /** Type de paiement non proposé aux étudiants en réadmission. */
    public const TYPE_CAUTION = 'caution';

    /**
     * Volet du résident (admission ou réadmission) d'après son attribution confirmée : même règle que la caution
     * calculée à la génération des loyers (`LoyerService::conditionsFinancieres`). Sans attribution : admission.
     */
    protected function voletResident(?AssignationChambre $assignation, string $etudiantId): string
    {
        if (!$assignation) {
            return 'admission';
        }

        return app(\App\Http\Services\Eligibilite\CriteresCampagne::class)
            ->voletEtudiant($assignation->campagne, $etudiantId, $assignation->getKey());
    }

    /** Loyer mensuel défini sur le type de la chambre (Paramétrage hébergement › Types de chambre), à défaut le prix de la chambre. */
    protected function loyerMensuelChambre(?\App\Models\Chambre $chambre): float
    {
        return (float) ($chambre?->typeChambre?->montant ?: $chambre?->prix ?: 0);
    }

    /**
     * Récupère le message de répartition du paiement
     */
    protected function getMessageRepartition(array $verification): string
    {
        $messages = [];
        
        if ($verification['arriere_total'] > 0) {
            $messages[] = sprintf(
                '✅ Arriérés (%s FCFA) entièrement payés.',
                number_format($verification['arriere_total'], 0, ',', ' ')
            );
        }

        if ($verification['reste_apres_arriere'] > 0) {
            if ($verification['reste_apres_arriere'] >= $verification['montant_mois']) {
                $messages[] = sprintf(
                    '✅ Loyer du mois (%s FCFA) payé.',
                    number_format($verification['montant_mois'], 0, ',', ' ')
                );
                
                if ($verification['reste_apres_mois'] > 0) {
                    $messages[] = sprintf(
                        '💰 Avance sur prochain mois: %s FCFA.',
                        number_format($verification['reste_apres_mois'], 0, ',', ' ')
                    );
                }
            } else {
                $messages[] = sprintf(
                    '⚠️ Paiement partiel du mois: %s FCFA sur %s FCFA.',
                    number_format($verification['reste_apres_arriere'], 0, ',', ' '),
                    number_format($verification['montant_mois'], 0, ',', ' ')
                );
            }
        }

        return implode(' ', $messages);
    }

   

    /**
     * Valide un paiement en attente
     */
    public function valider(Request $request, string $id)
    {
        try {
            $paiement = Paiement::find($id);
            if (!$paiement) {
                return back()->with('error', 'Paiement non trouvé.');
            }

            $paiement->update([
                'statut_paiement' => 'valide',
                'date_paiement' => now()
            ]);

            // Appliquer le paiement sur les loyers
            // Logique à implémenter si nécessaire

            return back()->with('success', 'Paiement validé avec succès.');

        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la validation: ' . \App\Support\UserFacingErrors::caught($e));
        }
    }

    /**
     * Annule un paiement
     */
    public function annuler(Request $request, string $id)
    {
        try {
            $paiement = Paiement::find($id);
            if (!$paiement) {
                return back()->with('error', 'Paiement non trouvé.');
            }

            $paiement->update([
                'statut_paiement' => 'annule'
            ]);

            return back()->with('success', 'Paiement annulé avec succès.');

        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de l\'annulation: ' . \App\Support\UserFacingErrors::caught($e));
        }
    }

   

  
    // ==================== MÉTHODES PRIVÉES ====================

    private function getKpis(): array
    {
        $stats = $this->loyerService->getStatistiques();
        
        return [
            [
                'label' => 'Total encaissé',
                'value' => number_format($stats['total_paye'], 0, ',', ' ') . ' FCFA',
                'type' => 'success',
                'trend' => '↑ ' . $stats['taux_recouvrement'] . '% de recouvrement',
                'trend_class' => 'up'
            ],
            [
                'label' => 'Arriérés totaux',
                'value' => number_format($stats['total_restant'], 0, ',', ' ') . ' FCFA',
                'type' => 'danger',
                'trend' => $stats['nombre_en_retard'] . ' loyers en retard',
                'trend_class' => 'danger'
            ],
            [
                'label' => 'Loyers actifs',
                'value' => $stats['nombre_actifs'] + $stats['nombre_en_retard'],
                'type' => 'info',
                'trend' => $stats['nombre_termines'] . ' terminés',
                'trend_class' => 'stable'
            ],
            [
                'label' => 'Pénalités totales',
                'value' => number_format($stats['total_penalites'], 0, ',', ' ') . ' FCFA',
                'type' => 'warning',
                'trend' => '⚠️ Retards accumulés',
                'trend_class' => 'warning'
            ]
        ];
    }

    private function getStatsParResidence(): array
    {
        $cites = $this->citeService->getCites();
        $stats = [];

        foreach ($cites as $cite) { 
            $totalAttendu = 0;
            $totalEncaisse = 0;
            $nbResidents = 0;
            $totalLoyers = 0;

            foreach ($cite->batiment?->chambres ?? [] as $chambre) {
                foreach ($chambre->assignations->where('statut', 'confirmee') as $assignation) {
                    $nbResidents++;
                    $loyerMensuel = $chambre->prix ?? 0;
                    $totalAttendu += $loyerMensuel;
                    $totalLoyers += $loyerMensuel;
                    
                    // Récupérer les paiements de l'étudiant
                    $arriere = $this->loyerService->calculerArriere($assignation->etudiant_id);
                    $totalEncaisse += $arriere['total_paye'] ?? 0;
                }
            }

            $taux = $totalAttendu > 0 ? round(($totalEncaisse / $totalAttendu) * 100, 2) : 0;
            $impayes = $totalAttendu - $totalEncaisse;

            $stats[] = [
                'residence' => $cite->name,
                'total_attendu' => number_format($totalAttendu, 0, ',', ' ') . ' FCFA',
                'total_encaisse' => number_format($totalEncaisse, 0, ',', ' ') . ' FCFA',
                'taux_recouvrement' => $taux,
                'impayes' => number_format($impayes, 0, ',', ' ') . ' FCFA',
                'nb_residents' => $nbResidents,
                'loyer_moyen' => $nbResidents > 0 ? number_format($totalLoyers / $nbResidents, 0, ',', ' ') . ' FCFA' : '0 FCFA',
                'alerte' => $taux < 80
            ];
        }

        return $stats;
    }

    private function getTauxRecouvrement(): float
    {
        $stats = $this->loyerService->getStatistiques();
        return $stats['taux_recouvrement'] ?? 0;
    }

    private function getImpayes(): float
    {
        $stats = $this->loyerService->getStatistiques();
        return $stats['total_restant'] ?? 0;
    }

    private function getNombreRetard(): int
    {
        $stats = $this->loyerService->getStatistiques();
        return $stats['nombre_en_retard'] ?? 0;
    }


    /**
 * Affiche le détail d'un paiement
 */
public function show(Request $request, string $id)
{
    $paiement = Paiement::with([
        'resident',
        'chambre',
        'cite',
        'repartitions',
        'createdBy'
    ])->findOrFail($id);

    $url = $this->urlSiAutorise($request);
    $moisListe = $paiement->mois;
    if (is_string($moisListe)) {
        $moisListe = json_decode($moisListe, true) ?? [$moisListe];
    }
    $totalReparti = (float) $paiement->repartitions->sum('montant');

    return Inertia::render('Admin/Hebergement/Paiements/Show', [
        'paiement' => [
            'id' => (string) $paiement->id_paiement,
            'numero' => $paiement->numero_paiement,
            'montant' => formatSystemAmount($paiement->montant),
            'methode' => $paiement->methode_label,
            'type' => $paiement->type_label,
            'statut' => $paiement->statut_paiement,
            'statut_label' => $paiement->statut_label,
            'date_paiement' => $paiement->date_paiement?->format('Y-m-d H:i'),
            'date_echeance' => $paiement->date_echeance?->toDateString(),
            'reference' => $paiement->reference_transaction,
            'cree_par' => $paiement->createdBy?->name,
            'notes' => $paiement->notes,
            'mois' => $paiement->mois ? array_values(array_map('strval', (array) $moisListe)) : [],
            'resident' => [
                'name' => $paiement->resident?->name,
                'matricule' => $paiement->resident?->matricule,
                'telephone' => $paiement->resident?->telephone1,
                'email' => $paiement->resident?->email1,
                'residence' => $paiement->cite?->name,
                'chambre' => $paiement->chambre?->numero_chambre,
            ],
            'repartitions' => $paiement->repartitions->map(fn ($rep) => [
                'id' => (string) $rep->getKey(),
                'type' => $rep->type,
                'periode' => $rep->mois && $rep->annee ? $rep->mois.'/'.$rep->annee : null,
                'montant' => formatSystemAmount($rep->montant),
                'description' => $rep->description,
            ])->values(),
            'total_reparti' => formatSystemAmount($totalReparti),
            'ecart' => $totalReparti != $paiement->montant ? formatSystemAmount($paiement->montant - $totalReparti) : null,
        ],
        'urls' => [
            'index' => $url('admin.hebergement.paiements.index'),
            'valider' => $paiement->statut_paiement === 'en_attente' ? $url('admin.hebergement.paiements.valider', ['id' => $paiement->id_paiement]) : null,
        ],
    ]);
}

/**
 * Affiche le rapport financier
 */
public function rapportFinancier(Request $request)
{
    
    $annee = $request->get('annee', date('Y'));
    $mois = $request->get('mois');
    $residence = $request->get('residence');
 
    // Statistiques globales
    $statsLoyers = $this->loyerService->getStatistiquesLoyers($annee, $mois, $residence);
    $statsPaiements = $this->paiementService->getStatistiquesPaiements($annee, $mois, $residence);
    
    $totalAttendu = $statsLoyers['total_montant'] ?? 0;
    $totalEncaisse = $statsPaiements['total_encaisse'] ?? 0;
    $totalImpayes = $totalAttendu - $totalEncaisse;
    $totalPenalites = $statsLoyers['total_penalites'] ?? 0;
    $totalRetard = $statsLoyers['total_retard'] ?? 0;
    $nbRetard = $statsLoyers['nombre_retard'] ?? 0;
    $nbImpayes = $statsLoyers['nombre_impayes'] ?? 0;
    $nbLoyers = $statsLoyers['nombre_loyers'] ?? 0;
    $nbPaiements = $statsPaiements['nombre_paiements'] ?? 0;
    
    $tauxRecouvrement = $totalAttendu > 0 ? round(($totalEncaisse / $totalAttendu) * 100, 1) : 0;
    
    // Statistiques par résidence
    $statsParResidence = $this->loyerService->getStatistiquesParResidenceFiltre($annee, $mois, $residence);
   // dd($statsParResidence);
    // Données pour les graphiques
    $evolutionData = $this->paiementService->getEvolutionPaiements($annee, $mois, $residence);
    $repartitionData = [
        $totalEncaisse,
        $totalImpayes,
        $totalRetard
    ];
    
    // Derniers paiements
    $derniersPaiements = $this->paiementService->getDerniersPaiements(10);
    
    // Résidences pour les filtres
    $residences = Cite::orderBy('name')->get();
    
    // Labels pour les graphiques
    $evolutionLabels = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
    $evolutionAttendus = $this->paiementService->getEvolutionAttendus($annee, $mois, $residence);
    
    $statsParMode = $this->paiementService->getStatistiquesParModePaiement($annee, $mois, $residence);
    
    // Statistiques par mode pour les graphiques
    $modeLabels = [];
    $modeData = [];
    $modeColors = [
        'orange_money' => '#f47b20',
        'mtn_money' => '#ffcb00',
        'moov_money' => '#00a651',
        'especes' => '#1e7aa8',
        'virement' => '#6a4d8a',
        'carte_bancaire' => '#b13e3e',
        'autre' => '#5b7b99'
    ];
    
    foreach ($statsParMode as $mode) {
        $modeLabels[] = $mode['libelle'];
        $modeData[] = $mode['total'];
    }


    $url = $this->urlSiAutorise($request);

    return Inertia::render('Admin/Hebergement/Paiements/RapportFinancier', [
        'stats' => [
            'total_attendu' => (float) $totalAttendu,
            'total_encaisse' => (float) $totalEncaisse,
            'total_impayes' => (float) $totalImpayes,
            'total_penalites' => (float) $totalPenalites,
            'total_retard' => (float) $totalRetard,
            'nb_retard' => (int) $nbRetard,
            'nb_impayes' => (int) $nbImpayes,
            'nb_loyers' => (int) $nbLoyers,
            'nb_paiements' => (int) $nbPaiements,
            'taux_recouvrement' => (float) $tauxRecouvrement,
        ],
        'evolution' => collect($evolutionLabels)->map(fn ($label, $index) => [
            'label' => $label,
            'encaisse' => (float) ($evolutionData[$index] ?? 0),
            'attendu' => (float) ($evolutionAttendus[$index] ?? 0),
        ])->values(),
        'repartition' => [
            'paye' => (float) $repartitionData[0],
            'impaye' => (float) $repartitionData[1],
            'retard' => (float) $repartitionData[2],
        ],
        'statsParMode' => collect($statsParMode)->map(fn ($mode) => [
            'methode' => (string) $mode['methode'],
            'libelle' => $mode['libelle'],
            'couleur' => $mode['couleur'],
            'nombre' => (int) $mode['nombre'],
            'total' => (float) $mode['total'],
            'moyenne' => (float) $mode['moyenne'],
            'pourcentage' => (float) $mode['pourcentage'],
        ])->values(),
        'statsParResidence' => collect($statsParResidence)->map(fn ($stat) => [
            'residence' => $stat['residence'] ?? null,
            'total_attendu' => (float) ($stat['total_attendu'] ?? 0),
            'total_encaisse' => (float) ($stat['total_encaisse'] ?? 0),
            'total_impayes' => (float) ($stat['total_impayes'] ?? 0),
            'taux_recouvrement' => (float) ($stat['taux_recouvrement'] ?? 0),
            'nb_residents' => (int) ($stat['nb_residents'] ?? 0),
            'loyer_moyen' => (float) ($stat['loyer_moyen'] ?? 0),
            'nb_en_retard' => (int) ($stat['nb_en_retard'] ?? 0),
            'alerte' => (bool) ($stat['alerte'] ?? false),
        ])->values(),
        'derniersPaiements' => collect($derniersPaiements)->map(fn ($paiement) => [
            'id' => (string) $paiement['id'],
            'numero' => $paiement['numero'],
            'resident' => $paiement['resident'],
            'montant' => (float) $paiement['montant'],
            'methode' => $paiement['methode'],
            'statut_label' => $paiement['statut'],
            'statut_badge' => $paiement['statut_badge'],
            'date' => $paiement['date'],
            'url' => $url('admin.hebergement.paiements.show', ['id' => $paiement['id']]),
        ])->values(),
        'residences' => $residences->map(fn (Cite $cite) => ['id' => (string) $cite->id_cite, 'name' => $cite->name])->values(),
        'filters' => [
            'annee' => (string) $annee,
            'mois' => (string) ($mois ?? ''),
            'residence' => (string) ($residence ?? ''),
        ],
        'urls' => [
            'rapport' => route('admin.hebergement.paiements.rapport_financier'),
            'index' => $url('admin.hebergement.paiements.index'),
        ],
    ]);
}


}