<?php
// app/Http/Controllers/admin/immobilier/vie_residentielle/PaiementController.php

namespace App\Http\Controllers\admin\immobilier\vie_residentielle;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Services\PaiementService;
use App\Http\Services\PaiementsAvantAttribution;
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
use App\Http\Services\Eligibilite\CriteresCampagne;
use App\Http\Services\Eligibilite\ListeDefinitivePubliee;
use App\Models\TypeChambre;
use App\Services\AdminAccess;
use App\Services\CalendrierLoyer;
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
    $impayes = $statsLoyers['total_solde_restant'];
    $tauxRecouvrement = $statsLoyers['taux_recouvrement'];
    $nombreRetard = $statsLoyers['nombre_en_retard'];
    // Calendrier des loyers de la campagne choisie (onglet « Calendrier des loyers ») ; aucune pénalité de retard ne s'applique.
    $calendrier = $campagne ? $this->calendrierCampagne($campagne) : null;
    
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
    // « Total encaissé » : somme des paiements validés de la campagne, tous types confondus (loyer, avance, caution…),
    // et leur nombre ; les montants des loyers facturés ne comptent pas les paiements faits avant l'attribution.
    $encaisses = Paiement::query()
        ->when($filters['campagne'], fn ($query, $id) => $query->deCampagne($id))
        ->where('statut_paiement', 'valide');
    $totalEncaisse = (clone $encaisses)->sum('montant');
    $nombrePaiements = $encaisses->count();

    return Inertia::render('Admin/Hebergement/Paiements/Index', [
        'paiements' => $paiements->appends($request->query())->through(fn (Paiement $paiement) => [
            'id' => (string) $paiement->id_paiement,
            'numero' => $paiement->numero_paiement,
            'resident' => $paiement->resident?->name,
            'telephone' => $paiement->resident?->telephone1 ?? $paiement->resident?->telephone2,
            'residence' => $paiement->cite?->name,
            'chambre' => $paiement->chambre?->numero_chambre,
            'mois' => $paiement->mois ? formatPaiementMois($paiement->mois, 0, true) : null,
            'montant' => (float) $paiement->montant,
            'methode' => $paiement->methode_label,
            'type' => $paiement->type_label,
            'type_code' => (string) $paiement->type_paiement,
            'statut' => $paiement->statut_paiement,
            'statut_label' => $paiement->statut_label,
            'date_paiement' => $paiement->date_paiement?->format('Y-m-d H:i'),
            'urls' => [
                'show' => $url('admin.hebergement.paiements.show', ['id' => $paiement->id_paiement]),
                // Reçu unique pour les types enregistrés ensemble : même document sur chaque ligne de l'encaissement.
                'recu' => $url('admin.hebergement.paiements.recu', ['id' => $paiement->id_paiement]),
                'valider' => $paiement->statut_paiement === 'en_attente' ? $url('admin.hebergement.paiements.valider', ['id' => $paiement->id_paiement]) : null,
                'annuler' => $paiement->statut_paiement === 'en_attente' ? $url('admin.hebergement.paiements.annuler', ['id' => $paiement->id_paiement]) : null,
            ],
        ]),
        'kpis' => [
            'total_encaisse' => (float) $totalEncaisse,
            'taux_recouvrement' => (float) $tauxRecouvrement,
            'impayes' => (float) $impayes,
            // Prochaine échéance du calendrier des loyers de la campagne (null sans calendrier ou après la dernière).
            'prochaine_echeance' => $calendrier ? $this->prochaineEcheance($calendrier) : null,
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
        'onglet' => $request->query('onglet') === 'calendrier' ? 'calendrier' : 'paiements',
        'calendrier' => $calendrier,
        'urls' => [
            'index' => route('admin.hebergement.paiements.index'),
            'calendrier' => $campagne ? $url('admin.hebergement.paiements.calendrier.update', ['campagne' => $campagne->id_campagne]) : null,
            'export' => $url('admin.hebergement.paiements.export', ['campagne' => $choixCampagne] + $request->query()),
            'store' => $url('admin.hebergement.paiements.store'),
            'recherche' => $url('admin.hebergement.paiements.recherche'),
            'relances' => $url('admin.hebergement.paiements.relances.envoyer'),
            // Le rapport s'ouvre sur la campagne affichée.
            'rapport' => $url('admin.hebergement.paiements.rapport_financier', ['campagne' => $choixCampagne]),
        ],
    ]);
}

    /** Valeur du paramètre `campagne` qui affiche toutes les campagnes. */
    private const TOUTES_CAMPAGNES = 'toutes';

    /**
     * Onglet « Calendrier des loyers » : les 10 mois de loyer de la campagne et leur échéance (enregistrés, ou proposés
     * s'ils ne sont pas encore configurés) ; les mois déjà payés ne peuvent pas être retirés.
     */
    private function calendrierCampagne(Campagne $campagne): array
    {
        // La liste des campagnes ne charge que quelques colonnes : relire la campagne (calendrier, année de début).
        $campagne = Campagne::find($campagne->getKey()) ?? $campagne;
        $service = app(CalendrierLoyer::class);
        $payes = $service->moisPayes($campagne);

        return [
            'campagne' => $campagne->libelle ?: $campagne->annee_campagne,
            'configure' => $service->de($campagne) !== [],
            'nombre_mois' => CalendrierLoyer::NOMBRE_MOIS,
            'mois' => array_map(fn (array $entree) => $entree + [
                'libelle' => CalendrierLoyer::libelle($entree['mois']),
                'paye' => in_array($entree['mois'], $payes, true),
            ], $service->propose($campagne)),
            'mois_payes' => $payes,
            // Valeurs du formulaire : premier mois, jour d'échéance, échéance dans le mois du loyer ou le mois suivant.
            'regle' => $service->regle($campagne),
        ];
    }

    /** Première échéance à venir (aujourd'hui compris) du calendrier enregistré : date et mois concerné. */
    private function prochaineEcheance(array $calendrier): ?array
    {
        if (! $calendrier['configure']) {
            return null;
        }
        $entree = collect($calendrier['mois'])->first(fn (array $mois) => $mois['echeance'] >= today()->format('Y-m-d'));

        return $entree ? ['date' => $entree['echeance'], 'mois' => $entree['libelle']] : null;
    }

    /**
     * Enregistre le calendrier des loyers d'une campagne (onglet « Calendrier des loyers ») : 10 mois et leur échéance.
     */
    public function enregistrerCalendrier(Request $request, string $campagne)
    {
        $campagne = Campagne::findOrFail($campagne);
        // Seul le premier mois se saisit : les 10 mois se suivent ; échéance le jour J du mois du loyer ou du mois suivant.
        $validated = $request->validate([
            'premier_mois' => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'jour' => 'required|integer|min:1|max:31',
            'echeance' => 'required|in:' . CalendrierLoyer::MOIS_LOYER . ',' . CalendrierLoyer::MOIS_SUIVANT,
        ], [
            'premier_mois.required' => 'Choisissez le premier mois de loyer.',
            'premier_mois.regex' => 'Premier mois de loyer invalide.',
            'jour.required' => 'Indiquez le jour d’échéance.',
            'jour.integer' => 'Le jour d’échéance est un nombre entier.',
            'jour.min' => 'Le jour d’échéance est compris entre 1 et 31.',
            'jour.max' => 'Le jour d’échéance est compris entre 1 et 31.',
            'echeance.required' => 'Choisissez le mois de l’échéance.',
            'echeance.in' => 'Choisissez le mois de l’échéance.',
        ]);

        app(CalendrierLoyer::class)->enregistrer($campagne, $validated['premier_mois'], (int) $validated['jour'], $validated['echeance']);

        return redirect()
            ->route('admin.hebergement.paiements.index', ['campagne' => $campagne->id_campagne, 'onglet' => 'calendrier'])
            ->with('success', 'Calendrier des loyers enregistré.');
    }

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
     * Recherche dynamique de « Nouveau paiement » : résidents logés (affectation confirmée : loyer, avance…), puis
     * étudiants de la liste « Admis à loger » sans chambre (`ListeDefinitivePubliee` : caution et avance avant l'attribution).
     */
    public function rechercheResidents(Request $request)
    {
        $query = trim((string) $request->get('q'));

        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $correspond = function ($q) use ($query) {
            $q->where('name', 'LIKE', "%{$query}%")
              ->orWhere('telephone2', 'LIKE', "%{$query}%")
              ->orWhere('matricule', 'LIKE', "%{$query}%")
              ->orWhere('telephone1', 'LIKE', "%{$query}%")
              ->orWhere('email1', 'LIKE', "%{$query}%")
              ->orWhere('email2', 'LIKE', "%{$query}%")
              // Numéro de table au bac saisi dans la demande d’hébergement (bacheliers).
              ->orWhereHas('demandes', fn ($d) => $d->where('num_table_bac', 'LIKE', "%{$query}%"));
        };

        $residents = Etudiant::with(['assignationActive.campagne', 'assignationActive.chambre.cite', 'assignationActive.chambre.batiment', 'assignationActive.chambre.typeChambre'])
            ->whereHas('assignationActive')
            ->where($correspond)
            ->limit(self::RECHERCHE_MAX)
            ->get();

        $admis = $residents->count() >= self::RECHERCHE_MAX ? collect() : app(ListeDefinitivePubliee::class)->candidats(Etudiant::query())
            ->with('derniereDemande.campagne')
            ->where($correspond)
            ->limit(self::RECHERCHE_MAX - $residents->count())
            ->get();

        return response()->json(array_merge($this->lignesResidents($residents), $this->lignesAdmis($admis)));
    }

    /** Mois (`mm-aaaa`) restants du calendrier des loyers à partir de `$prochain` ; null si la campagne n'en a pas. */
    private function moisCalendrier(?Campagne $campagne, string $prochain): ?array
    {
        $service = app(CalendrierLoyer::class);

        return $service->de($campagne) ? $service->moisProposes($campagne, $prochain, CalendrierLoyer::NOMBRE_MOIS) : null;
    }

    /** Nombre maximum de résultats de la recherche de « Nouveau paiement ». */
    private const RECHERCHE_MAX = 15;

    /**
     * Résidents logés proposés par « Nouveau paiement » : montant sur le loyer mensuel du type de leur chambre,
     * arriérés et prochain mois à payer pour le loyer et l'avance.
     *
     * @param  \Illuminate\Support\Collection<int, Etudiant>  $residents  avec `assignationActive.chambre` chargée
     */
    private function lignesResidents(\Illuminate\Support\Collection $residents): array
    {
        return $residents->map(function (Etudiant $resident) {
            $assignation = $resident->assignationActive;
            $chambre = $assignation?->chambre;
            $cite = $chambre?->cite;
            $batiment = $chambre?->batiment;
            $arriere = (float) ($this->loyerService->calculerArriere($resident->id_etudiant)['solde_restant'] ?? 0);
            $loyerActuel = $this->paiementService->getLoyerActuel($resident->id_etudiant);
            $montantMois = (float) ($loyerActuel ? ($loyerActuel->solde_restant ?? $loyerActuel->montant_total) : 0);
            // Mois payés et mois en retard du séjour (même calcul que « Mes loyers » de l'espace étudiant).
            $etatMois = app(\App\Services\EtudiantPaiements::class)->etatDesMois((string) $resident->id_etudiant);

            return [
                'id_etudiant' => $resident->id_etudiant,
                'name' => trim((string) $resident->name),
                'matricule' => $resident->matricule,
                'num_table_bac' => $resident->demandes()->whereNotNull('num_table_bac')->latest('created_at')->value('num_table_bac'),
                'telephone' => $resident->telephone1 ?? $resident->telephone2 ?? null,
                'residence' => $cite?->name,
                'residence_id' => $cite?->id_cite,
                'batiment' => $batiment?->name,
                'batiment_id' => $batiment?->id_batiment,
                'chambre' => $chambre?->numero_chambre,
                'chambre_id' => $chambre?->id_chambre,
                'contrat_id' => $assignation?->id_assignation,
                // Loyer mensuel du type de chambre (Paramétrage hébergement › Types de chambre) : base des montants.
                'loyer_mensuel' => $this->loyerMensuelChambre($chambre),
                'arriere' => $arriere,
                'montant_mois' => $montantMois,
                'total_du' => $arriere + $montantMois,
                'statut_paiement' => $arriere > 0 ? 'En retard' : 'À jour',
                'mois_payes' => $etatMois['payes'],
                'mois_en_retard' => $etatMois['en_retard'],
                // Réadmission : pas de caution.
                'readmission' => $this->voletResident($assignation, $resident->id_etudiant) === 'readmission',
                // Mois suivant le dernier mois payé (loyer ou avance) : premier mois du champ « Mois » (mm-aaaa).
                'prochain_mois' => $prochain = $this->paiementService->prochainMoisAPayer($resident->id_etudiant),
                // Mois restants du calendrier des loyers de la campagne (null sans calendrier : mois consécutifs).
                'mois_calendrier' => $this->moisCalendrier($assignation?->campagne, $prochain),
                'sans_chambre' => false,
            ];
        })->values()->all();
    }

    /**
     * Étudiants admis à loger proposés par « Nouveau paiement » : le montant de leurs paiements se calcule sur le loyer
     * d'un type de chambre choisi dans le formulaire (`types_chambre`).
     *
     * @param  \Illuminate\Support\Collection<int, Etudiant>  $etudiants  avec `derniereDemande.campagne` chargée
     */
    private function lignesAdmis(\Illuminate\Support\Collection $etudiants): array
    {
        if ($etudiants->isEmpty()) {
            return [];
        }
        $paiements = app(PaiementsAvantAttribution::class);
        $typesChambre = $paiements->typesChambre()
            ->map(fn (TypeChambre $type) => ['id' => (string) $type->id_type_chambre, 'name' => $type->name, 'montant' => (float) $type->montant])
            ->values()->all();
        $etats = $paiements->etatsAvantChambre($etudiants);

        return $etudiants->map(function (Etudiant $etudiant) use ($etats, $typesChambre) {
            $etat = $etats[(string) $etudiant->getKey()];

            return [
                'id_etudiant' => $etudiant->id_etudiant,
                'name' => trim((string) $etudiant->name),
                'matricule' => $etudiant->matricule,
                'num_table_bac' => $etudiant->derniereDemande?->num_table_bac,
                'telephone' => $etudiant->telephone1 ?? $etudiant->telephone2 ?? null,
                'residence' => null,
                'residence_id' => null,
                'batiment' => null,
                'batiment_id' => null,
                'chambre' => null,
                'chambre_id' => null,
                'contrat_id' => null,
                'loyer_mensuel' => 0,
                'arriere' => 0,
                'montant_mois' => 0,
                'total_du' => 0,
                'statut_paiement' => 'À jour',
                'readmission' => $etat['volet'] === 'readmission',
                'prochain_mois' => $prochain = $this->paiementService->prochainMoisAPayer($etudiant->id_etudiant),
                'mois_calendrier' => $this->moisCalendrier($etudiant->derniereDemande?->campagne, $prochain),
                // Admis à loger, pas encore de chambre : caution et avance à verser avant l'attribution,
                // montant sur le type de chambre choisi.
                'sans_chambre' => true,
                'campagne' => $etudiant->derniereDemande->campagne?->annee_campagne,
                'types_chambre' => $typesChambre,
                // Mois restant à payer par type exigé avant l'attribution (types soldés omis) : lignes proposées.
                'a_payer' => (object) collect($etat['types'])->filter(fn (array $solde) => $solde['reste'] > 0)
                    ->map(fn (array $solde) => min(self::MOIS_MAX, (int) ceil($solde['reste'] / $etat['loyer'])))->all(),
            ];
        })->values()->all();
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
            // Sans chambre : étudiant admis à loger, montant calculé sur le type de chambre choisi.
            'chambre_id' => 'nullable|exists:chambre,id_chambre',
            'cite_id' => 'nullable|exists:cite,id_cite',
            'contrat_id' => 'nullable|exists:assignation_chambres,id_assignation',
            'batiment_id'=> 'nullable|required_with:chambre_id|exists:batiment,id_batiment',
            'type_chambre_id' => 'nullable|string',
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
            'batiment_id.required_with' => 'Le bâtiment de cet étudiant est introuvable.',
        ]);

        $avantAttribution = empty($validated['chambre_id']);
        $loyerMensuel = $avantAttribution
            ? $this->loyerAvantAttribution($validated)
            : $this->loyerMensuelChambre(\App\Models\Chambre::with('typeChambre')->find($validated['chambre_id']));
        if (! $avantAttribution && $loyerMensuel <= 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'resident_id' => 'Le type de la chambre de cet étudiant n’a pas de loyer mensuel (Paramétrage hébergement › Types de chambre).',
            ]);
        }

        // Un étudiant en réadmission ne paie pas de caution (même règle de volet que la caution du loyer).
        $assignation = AssignationChambre::with('campagne')->where('etudiant_id', $validated['resident_id'])->where('statut', 'confirmee')->first();
        if (in_array(self::TYPE_CAUTION, array_column($validated['lignes'], 'type_paiement'), true)
            && $this->voletResident($assignation, $validated['resident_id']) === 'readmission') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'lignes' => 'Cet étudiant est en réadmission : la caution ne s’applique pas.',
            ]);
        }

        // Loyer et avance soldent des loyers mois par mois : les mois cochés doivent se suivre à partir du mois
        // qui suit le dernier mois payé, autant que de mois saisis (loyer d'abord, puis avance).
        $saisies = array_values($validated['lignes']);
        $mensuelles = array_filter($saisies, fn ($ligne) => in_array($ligne['type_paiement'], PaiementService::TYPES_LOYER, true));
        // Avant l'attribution, seule l'avance (loyer payé d'avance) est concernée : mêmes mois consécutifs à cocher.
        if ($mensuelles) {
            $demandes = array_sum(array_column($mensuelles, 'mois'));
            $attendus = $this->paiementService->moisConsecutifs($validated['resident_id'], $demandes);
            // Calendrier des loyers : on ne paie pas au-delà de ses 10 mois.
            if (count($attendus) < $demandes) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'mois_concernes' => count($attendus) === 0
                        ? 'Tous les mois de loyer du calendrier de la campagne sont déjà payés.'
                        : sprintf('Il ne reste que %d mois de loyer à payer sur le calendrier de la campagne.', count($attendus)),
                ]);
            }
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
            ] + ($avantAttribution && $ligne['type_paiement'] === PaiementService::TYPE_AVANCE
                // Avance avant l'attribution : loyers des mois cochés payés d'avance (aucun loyer n'existe encore).
                ? ['mois' => array_values($validated['mois_concernes'] ?? [])]
                : []), $saisies);
            $commun = collect($validated)->except(['lignes', 'mois_concernes', 'type_chambre_id'])->all()
                // Paiements avant l'attribution (caution, avance…) : enregistrés hors loyer.
                + ($avantAttribution ? ['avant_attribution' => true] : []);
            $indexMensuel = $avantAttribution ? false : collect($lignes)->search(fn ($ligne) => in_array($ligne['type_paiement'], PaiementService::TYPES_LOYER, true));
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

            $horsLoyer = collect($lignes)->when(! $avantAttribution, fn ($l) => $l->whereNotIn('type_paiement', PaiementService::TYPES_LOYER))
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
            // Pas encore logé (liste définitive publiée) : volet de la campagne de sa dernière demande.
            $demande = Etudiant::with('derniereDemande.campagne')->find($etudiantId)?->derniereDemande;

            return $demande?->campagne ? app(CriteresCampagne::class)->voletEtudiant($demande->campagne, $etudiantId) : 'admission';
        }

        return app(CriteresCampagne::class)
            ->voletEtudiant($assignation->campagne, $etudiantId, $assignation->getKey());
    }

    /**
     * Paiement avant l'attribution de la chambre : l'étudiant doit figurer dans la liste « Admis à loger », sans
     * affectation confirmée. Caution (nouvelles demandes) et avance (nouvelles demandes et réadmissions) se paient avant
     * l'attribution ; le loyer mensuel est refusé. Le montant se calcule sur le loyer mensuel du type de chambre choisi.
     */
    protected function loyerAvantAttribution(array $validated): float
    {
        if (! app(ListeDefinitivePubliee::class)->figure($validated['resident_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'resident_id' => 'Cet étudiant n’a pas de chambre attribuée et ne figure pas dans la liste « Admis à loger » : le paiement ne peut pas être enregistré.',
            ]);
        }
        // Avant l'attribution : caution (nouvelles demandes) et avance (toutes) se paient ; le loyer mensuel, non.
        if (in_array(PaiementService::TYPE_LOYER, array_column($validated['lignes'], 'type_paiement'), true)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'lignes' => 'Le loyer s’encaisse après l’attribution de la chambre ; avant, encaissez la caution et l’avance.',
            ]);
        }
        $type = filled($validated['type_chambre_id'] ?? null)
            ? app(PaiementsAvantAttribution::class)->typesChambre()->first(fn (TypeChambre $type) => (string) $type->id_type_chambre === (string) $validated['type_chambre_id'])
            : null;
        if (! $type) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'type_chambre_id' => 'Choisissez le type de chambre qui sert de base au montant (Paramétrage hébergement › Types de chambre).',
            ]);
        }

        return (float) $type->montant;
    }

    /** Loyer mensuel défini sur le type de la chambre (Paramétrage hébergement › Types de chambre), à défaut le prix de la chambre. */
    protected function loyerMensuelChambre(?\App\Models\Chambre $chambre): float
    {
        return (float) ($chambre?->typeChambre?->montant ?: $chambre?->prix ?: 0);
    }

    /**
     * Message de répartition du loyer et de l'avance : arriérés, mois en cours, puis mois suivants. Sans loyer du mois
     * en cours à payer (déjà soldé ou pas encore facturé), tout le reste va aux mois suivants.
     */
    protected function getMessageRepartition(array $verification): string
    {
        $fcfa = fn ($montant) => number_format((float) $montant, 0, ',', ' ').' FCFA';
        $messages = [];

        if ($verification['arriere_total'] > 0) {
            $messages[] = sprintf('Arriérés (%s) entièrement payés.', $fcfa($verification['arriere_total']));
        }

        $reste = (float) $verification['reste_apres_arriere'];
        $moisEnCours = (float) $verification['montant_mois'];
        if ($reste <= 0) {
            return implode(' ', $messages);
        }

        if ($moisEnCours <= 0) {
            $messages[] = sprintf('Avance sur les mois suivants : %s.', $fcfa($reste));
        } elseif ($reste >= $moisEnCours) {
            $messages[] = sprintf('Loyer du mois en cours (%s) payé.', $fcfa($moisEnCours));
            if ($verification['reste_apres_mois'] > 0) {
                $messages[] = sprintf('Avance sur les mois suivants : %s.', $fcfa($verification['reste_apres_mois']));
            }
        } else {
            $messages[] = sprintf('Paiement partiel du mois en cours : %s sur %s.', $fcfa($reste), $fcfa($moisEnCours));
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
        'createdBy'
    ])->findOrFail($id);

    $url = $this->urlSiAutorise($request);
    $moisListe = $paiement->mois;
    if (is_string($moisListe)) {
        $moisListe = json_decode($moisListe, true) ?? [$moisListe];
    }
    return Inertia::render('Admin/Hebergement/Paiements/Show', [
        'paiement' => [
            'id' => (string) $paiement->id_paiement,
            'numero' => $paiement->numero_paiement,
            'montant' => formatSystemAmount($paiement->montant),
            'methode' => $paiement->methode_label,
            'type' => $paiement->type_label,
            'type_code' => (string) $paiement->type_paiement,
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
        ],
        'urls' => [
            'index' => $url('admin.hebergement.paiements.index'),
            'valider' => $paiement->statut_paiement === 'en_attente' ? $url('admin.hebergement.paiements.valider', ['id' => $paiement->id_paiement]) : null,
            'recu' => $url('admin.hebergement.paiements.recu', ['id' => $paiement->id_paiement]),
        ],
    ]);
}

/**
 * Reçu PDF d'un paiement : document dédié (et non une copie de la page de détail), tenant sur une page A4 —
 * en-tête avec le logo et le nom du système, bénéficiaire, détail du paiement, répartition et signatures.
 * Reçu unique pour les types de paiement enregistrés ensemble : même document depuis chacun d'eux.
 * Le même reçu est proposé à l'étudiant (Mes paiements › Historique) : App\Services\RecuPaiement.
 */
public function recu(string $id)
{
    return app(\App\Services\RecuPaiement::class)->reponse(Paiement::findOrFail($id));
}

/**
 * Rapport financier (année, mois et résidence facultatifs). Les chiffres viennent de App\Services\RapportFinancier :
 * attendu, recouvré, impayés et retards sur les loyers facturés ; total encaissé, modes et évolution sur les
 * paiements validés, tous types confondus.
 */
public function rapportFinancier(Request $request)
{
    // Filtres : cité, campagne (`?campagne=<id>`, `toutes`, ou par défaut la campagne active) et mois de cette campagne.
    $campagnes = Campagne::query()->orderByDesc('created_at')->get(['id_campagne', 'libelle', 'annee_campagne', 'statut']);
    $campagne = $this->campagneChoisie($request, $campagnes);
    // La liste ne charge que quelques colonnes : relire la campagne (calendrier des loyers, année de début).
    $campagne = $campagne ? (Campagne::find($campagne->getKey()) ?? $campagne) : null;
    $residence = (string) $request->query('residence', '') ?: null;

    $rapport = app(\App\Services\RapportFinancier::class)->donnees($campagne, (string) $request->query('mois', '') ?: null, $residence);
    $moisChoisi = $rapport['moisChoisi'];
    unset($rapport['moisChoisi']);
    $url = $this->urlSiAutorise($request);

    return Inertia::render('Admin/Hebergement/Paiements/RapportFinancier', [
        ...$rapport,
        'residences' => Cite::orderBy('name')->get()->map(fn (Cite $cite) => ['id' => (string) $cite->id_cite, 'name' => $cite->name])->values(),
        'campagnes' => $campagnes->map(fn (Campagne $option) => [
            'id' => (string) $option->id_campagne,
            'libelle' => $option->libelle ?: $option->annee_campagne,
            'active' => $option->statut === 'active',
        ])->values(),
        'filters' => [
            'campagne' => $campagne ? (string) $campagne->id_campagne : self::TOUTES_CAMPAGNES,
            // Mois de la campagne (`aaaa-mm`) ; vide : toute la campagne.
            'mois' => (string) ($moisChoisi ?? ''),
            'residence' => (string) ($residence ?? ''),
        ],
        'urls' => [
            'rapport' => route('admin.hebergement.paiements.rapport_financier'),
            'index' => $url('admin.hebergement.paiements.index'),
        ],
    ]);
}


}