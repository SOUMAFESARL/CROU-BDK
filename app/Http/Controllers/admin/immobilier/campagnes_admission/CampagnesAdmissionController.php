<?php

namespace App\Http\Controllers\admin\immobilier\campagnes_admission;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Services\EtudiantService;
use App\Models\Document;
use App\Http\Requests\CampagneRequest;
use App\Http\Requests\DemandeHebergementRequest;
use App\Http\Services\CampagneService;
use Illuminate\Http\RedirectResponse;
use \App\Models\Cite;
use App\Http\Services\DocumentEtudiantService;
use App\Repositories\Interfaces\DocumentEtudiantRepositoryInterface;
use App\Repositories\Interfaces\EtudiantRepositoryInterface;
use App\Http\Services\CampagneValidationService;
use App\Models\Campagne;
use App\Services\AdminAccess;
use App\Services\AdmissionResultDiffusion;
use App\Services\AdmissionPermissions;
use Illuminate\Support\Facades\DB;
use App\Services\AdminViewAccess;
use App\Http\Services\DossierWorkflow;
use App\Http\Services\DecisionAutomatiqueDossier;
use App\Http\Services\CampaignScoringService;
use App\Models\CampaignScoring;
use App\Models\DemandeEtudiant;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use App\Models\Etudiant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;


class CampagnesAdmissionController extends Controller
{
    protected EtudiantService $etudiantService;

    protected CampagneService $campagneService;

    public function __construct(EtudiantService $etudiantService, CampagneService $campagneService)
    {
        $this->etudiantService = $etudiantService;
        $this->campagneService = $campagneService;
    }


    /**
     * Filtres de la liste des admissions : toutes les demandes, demandes recevables (tous les critères de la campagne
     * conformes), demandes irrecevables (au moins un critère non conforme) et demandes refusées (refusées ou annulées
     * par l'administration pendant la validation), selon `RecevabiliteDemandes` ; s'y ajoutent la « Décision de la
     * commission » (onglet à part, filtre `validees` : demandes recevables validées — dossier « Recevable » — ou mises en
     * attente depuis une liste provisoire) et les « Listes définitives » (onglet à part : demandes approuvées en
     * commission, depuis les demandes validées ou la file d'attente).
     * Les étudiants logés et les dossiers en vérification sont suivis sur la page « Résidents & contrats ».
     */
    private const FILTRES_ADMISSION = ['toutes', 'recevables', 'rejetees', 'refusees', 'validees', 'definitives'];

    /**
     * Filtres de la « Liste des demandes » et « Décision de la commission » : demandes de la campagne choisie dans le
     * bandeau (`?campagne=`, par défaut la campagne active, sinon la plus récente).
     */
    private const FILTRES_PAR_CAMPAGNE = ['toutes', 'recevables', 'rejetees', 'refusees', 'validees'];

    /** Anciens filtres (liens enregistrés) : nouvelles demandes, en attente, rejetées. */
    private const FILTRES_ADMISSION_ANCIENS = ['nouvelles_demandes' => 'toutes', 'en_attente' => 'recevables', 'annuler' => 'rejetees'];

    public function liste(Request $request, ?string $etat = null)
    {
        $permissions = app(AdmissionPermissions::class);
        $admin = $request->user('admin');
        $etat ??= $request->query('etat');
        abort_unless($etat === null || is_string($etat), 403);
        if (!$etat && !$request->wantsJson() && !$permissions->allows($admin, 'toutes')) {
            $destination = $permissions->demandesUrl($admin) ?? $permissions->url($admin, 'validees') ?? $permissions->url($admin, 'definitives');
            abort_unless($destination, 403);

            return redirect($destination);
        }

        $filtre = self::FILTRES_ADMISSION_ANCIENS[(string) $etat] ?? $etat;
        $filtre = in_array($filtre, self::FILTRES_ADMISSION, true) ? $filtre : 'toutes';
        abort_unless($permissions->sections($admin, $filtre) !== [], 403);
        if ($request->wantsJson()) {
            abort_unless($permissions->allows($admin, 'toutes'), 403);
        }

        try {
            if (!$request->wantsJson()) {
                return $this->pageAdmissions($request, $filtre);
            }
            $result = $this->etudiantService->getListeEtudiantsWithStats($etat ?? $request->get('etat'));
            $result = app(\App\Services\AdminViewData::class)->filter($result, $request->user('admin'));

            return response()->json($result);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            throw $e;
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

    /**
     * Liste des dossiers de candidature (page React `Admin/Hebergement/Admissions/Index`).
     * Les colonnes personnelles et l'historique ne sont envoyés qu'avec les droits correspondants.
     */
    private function pageAdmissions(Request $request, string $filtre)
    {
        $admin = $request->user('admin');
        $access = app(AdminAccess::class);
        $vues = app(AdminViewAccess::class);
        $personnel = $vues->allows($admin, 'admissions.personal');
        $historique = $vues->allows($admin, 'admissions.history');
        $etat = $filtre;
        $permissions = app(AdmissionPermissions::class);
        $sections = $permissions->sections($admin, $etat);
        $requestedList = $request->query('liste');
        abort_unless($requestedList === null || is_string($requestedList), 403);
        $requestedList = $permissions->isListTab($etat, $requestedList) ? $requestedList : null;
        abort_if($requestedList !== null && !isset($sections[$requestedList]), 403);
        $recherche = trim((string) $request->query('q', ''));
        $etatDossier = (string) $request->query('etat_dossier', '');
        $etatDossier = array_key_exists($etatDossier, DossierWorkflow::LABELS) ? $etatDossier : '';
        // Type de demande (volet de l'étudiant dans la campagne de sa demande) : admission ou réadmission.
        $typeDemande = (string) $request->query('type', '');
        $typeDemande = array_key_exists($typeDemande, \App\Http\Services\Eligibilite\CriteresCampagne::VOLETS) ? $typeDemande : '';
        $url = fn (string $route, array $parametres = []) => $access->allows($admin, $route) ? route($route, $parametres) : null;
        $repository = app(\App\Repositories\EtudiantRepository::class);
        // Liste des demandes (toutes, recevables, irrecevables, refusées) et décision de la commission : demandes de la
        // campagne choisie dans le bandeau (`?campagne=`, par défaut la campagne active). Listes définitives : campagne
        // active, sur toutes les demandes.
        $parCampagne = in_array($etat, self::FILTRES_PAR_CAMPAGNE, true);
        $campagnes = $parCampagne ? Campagne::query()->orderByDesc('created_at')->get() : null;
        $campagne = $parCampagne ? $this->campagneSelectionnee($request, $campagnes) : $this->campagneActive();
        // Conformité de la dernière demande de chaque étudiant aux critères de sa campagne : compteurs et listes filtrées.
        $population = $repository->getPopulationDemandes();
        if ($parCampagne) {
            $population = $this->populationCampagne($population, $campagne);
        }
        // Campagne choisie conservée par les liens de la liste des demandes (indicateurs, onglets, téléchargements).
        $choixCampagne = $parCampagne && $campagne && $request->filled('campagne') ? ['campagne' => (string) $campagne->getKey()] : [];
        $filtersUrls = collect(self::FILTRES_ADMISSION)->mapWithKeys(fn ($screen) => [
            $screen => $permissions->url($admin, $screen, in_array($screen, self::FILTRES_PAR_CAMPAGNE, true) ? $choixCampagne : []),
        ]);
        $classement = app(\App\Http\Services\Eligibilite\RecevabiliteDemandes::class)->classer($population);
        $criteres = app(\App\Http\Services\Eligibilite\CriteresCampagne::class);
        // Demandes irrecevables : classées par volet selon les mêmes critères que les listes provisoires.
        $rangsIrrecevables = $etat === 'rejetees' ? $this->rangsIrrecevables($population, $classement) : [];
        $etudiants = match ($etat) {
            'recevables' => $classement['recevables'],
            'rejetees' => array_map('strval', array_keys($rangsIrrecevables)),
            'refusees' => $classement['refusees'],
            // Décision de la commission : demandes validées et demandes mises en attente.
            'validees' => [...$classement['validees'], ...$classement['en_attente']],
            'definitives' => $classement['definitives'],
            // Toutes les demandes : celles de la campagne choisie.
            default => $parCampagne ? $population->map(fn (Etudiant $etudiant) => (string) $etudiant->getKey())->all() : null,
        };
        if ($typeDemande !== '') {
            // Le type n'est pas stocké : il se calcule sur la population des demandes, puis restreint la liste.
            $volets = $criteres->voletsDemandes($population->map(fn (Etudiant $etudiant) => $etudiant->derniereDemande));
            $duType = array_keys(array_filter($volets, fn (string $volet) => $volet === $typeDemande));
            $etudiants = $etudiants === null ? $duType : array_values(array_intersect($etudiants, $duType));
        }
        $rawList = in_array($etat, ['toutes', 'rejetees', 'refusees'], true)
            || ($etat === 'recevables' && ($requestedList ?? array_key_first($sections)) === 'demandes');
        $dossiers = $repository->getAdmissionsPage('demandes', 10, $recherche, $etatDossier, $rawList ? $etudiants : [], null, $etat === 'rejetees');
        // Levée des refus automatiques (barème) : par dossier, ou pour les dossiers cochés dans la liste.
        $decision = app(DecisionAutomatiqueDossier::class);
        $scopeRefus = $etat === 'recevables' ? 'recevables_demandes' : $etat;
        $peutLever = $permissions->allows($admin, $scopeRefus, 'lever_refus');
        $refusAutomatiques = $rawList && $peutLever
            ? $decision->refusAutomatiques(collect($dossiers->items())->map(fn (Etudiant $etudiant) => $etudiant->derniereDemande?->getKey())->all())
            : [];
        // Type de demande (admission / réadmission) : volet de l'étudiant dans la campagne de sa demande.
        $dossiers->getCollection()->load('derniereDemande.campagne');
        $types = $criteres->voletsDemandes(collect($dossiers->items())->map(fn (Etudiant $etudiant) => $etudiant->derniereDemande));
        // Demandes recevables : listes provisoires ; décision de la commission : demandes validées ou mises en attente
        // depuis ces listes ; liste définitive : demandes approuvées en commission (mêmes rangs).
        $typesListe = ['recevables' => 'provisoire', 'validees' => 'validee', 'definitives' => 'definitive'];
        $listesProvisoires = isset($typesListe[$etat])
            ? $this->classementProvisoire($request, $typesListe[$etat], $population, $classement, $campagne, $recherche, $typeDemande, $personnel, $vues->allows($admin, 'admissions.documents'), $url, $access, $admin, $choixCampagne)
            : null;

        $commission = $permissions->sections($admin, 'validees');
        $definitives = $permissions->sections($admin, 'definitives');
        $nombreDefinitives = match (count($definitives)) {
            0 => 0,
            2 => count($classement['definitives']),
            default => $population->whereIn('id_etudiant', $classement['definitives'])
                ->filter(fn (Etudiant $student) => isset($definitives[$student->derniereDemande?->liste_approbation_commission === 'attente' ? 'attente' : 'validees']))
                ->count(),
        };

        return Inertia::render('Admin/Hebergement/Admissions/Index', [
            'dossiers' => $dossiers->through(fn (Etudiant $etudiant) => [
                'id' => (string) $etudiant->id_etudiant,
                'name' => $etudiant->name,
                'numero_demande' => $etudiant->derniereDemande?->numero_demande,
                'demande_id' => $etudiant->derniereDemande ? (string) $etudiant->derniereDemande->getKey() : null,
                'type_demande' => $types[(string) $etudiant->id_etudiant] ?? null,
                'etat_dossier' => $etudiant->derniereDemande?->etat_dossier,
                'date' => $etudiant->created_at?->format('Y-m-d H:i'),
                // Téléphone, adresse et date de naissance : consultables sur la fiche du dossier (liste sans défilement horizontal).
                'personnel' => $personnel ? [
                    'region' => $etudiant->region?->name,
                    'ville' => $etudiant->ville?->name,
                ] : null,
                'demandes' => $historique ? (int) $etudiant->demandes_count : null,
                'url' => $url('admin.hebergement.campagnes_admission.show', ['id' => $etudiant->id_etudiant]),
                // Demandes irrecevables : rang dans le volet, et demande conforme écartée par la liste élargie.
                'rang' => $rangsIrrecevables[(string) $etudiant->id_etudiant] ?? null,
                'hors_liste' => $etat === 'rejetees' && in_array((string) $etudiant->id_etudiant, $classement['hors_liste'], true),
                'refus_automatique' => in_array((string) $etudiant->derniereDemande?->getKey(), $refusAutomatiques, true),
            ]),
            'stats' => [
                'toutes' => $filtersUrls['toutes'] ? $population->count() : 0,
                'recevables' => $filtersUrls['recevables'] ? count($classement['recevables']) : 0,
                'rejetees' => $filtersUrls['rejetees'] ? count($classement['rejetees']) : 0,
                'refusees' => $filtersUrls['refusees'] ? count($classement['refusees']) : 0,
                'validees' => (isset($commission['validees']) ? count($classement['validees']) : 0)
                    + (isset($commission['attente']) ? count($classement['en_attente']) : 0),
                'definitives' => $nombreDefinitives,
            ],
            'etat' => $etat,
            'classement' => $listesProvisoires,
            'filters' => ['q' => $recherche, 'etat_dossier' => $etatDossier, 'type' => $typeDemande, 'campagne' => $choixCampagne['campagne'] ?? ''],
            'etatsDossier' => collect(DossierWorkflow::LABELS)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
            'colonnes' => ['personnel' => $personnel, 'historique' => $historique],
            // Liste des demandes : campagnes proposées dans le bandeau (libellé et année), la plus récente d'abord.
            'campagnes' => $campagnes?->map(fn (Campagne $option) => [
                'id' => (string) $option->getKey(),
                'libelle' => $option->libelle,
                'annee' => $option->annee_campagne,
                'active' => $option->statut === 'active',
            ])->values()->all() ?? [],
            'campagneActive' => $campagne ? [
                'id' => (string) $campagne->getKey(),
                'libelle' => $campagne->libelle,
                'active' => $campagne->statut === 'active',
                'annee' => $campagne->annee_campagne,
                'echeance' => $campagne->date_echeance_demande ? Carbon::parse($campagne->date_echeance_demande)->toDateString() : null,
                'moyenne_min' => $campagne->moyenne_min,
                'distance_min' => $campagne->distance_min,
            ] : null,
            'urls' => [
                'campagnes' => $permissions->allows($admin, 'campagnes') ? $url('admin.hebergement.campagnes_admission.campagnes') : null,
                'leverRefus' => $rawList && $peutLever ? route(AdmissionPermissions::ROUTE.'lever_refus_automatiques', ['scope' => $scopeRefus]) : null,
                'admissions' => $permissions->demandesUrl($admin, $choixCampagne),
                'filtres' => $filtersUrls,
            ],
        ]);
    }

    /** Traitement d'un dossier de candidature (page React `Admin/Hebergement/Admissions/Show`). */
    public function show(Request $request, string $id)
    {
        $admin = $request->user('admin');
        $access = app(AdminAccess::class);
        $vues = app(AdminViewAccess::class);
        $etudiant = app(\App\Services\AdminStudentView::class)->find($id, $admin);
        $derniere = $etudiant->derniereDemande;
        $url = fn (string $route, array $parametres = []) => $access->allows($admin, $route) ? route($route, $parametres) : null;
        $permissions = app(AdmissionPermissions::class);
        $voirAdmissions = $permissions->allows($admin, 'toutes');
        if (!$voirAdmissions && $derniere) {
            $classe = app(\App\Http\Services\Eligibilite\RecevabiliteDemandes::class)->classe($etudiant);
            $scope = match ($classe) {
                \App\Http\Services\Eligibilite\RecevabiliteDemandes::REJETEE => 'rejetees',
                \App\Http\Services\Eligibilite\RecevabiliteDemandes::REFUSEE => 'refusees',
                \App\Http\Services\Eligibilite\RecevabiliteDemandes::DEFINITIVE => 'definitive_'.($derniere->liste_approbation_commission === 'attente' ? 'attente' : 'validees'),
                default => $this->scopeDossier($etudiant, $classe),
            };
            $voirAdmissions = ($scope && $permissions->allows($admin, $scope))
                || ($classe === \App\Http\Services\Eligibilite\RecevabiliteDemandes::RECEVABLE && $permissions->allows($admin, 'recevables_demandes'));
        }
        abort_unless($voirAdmissions || $admin->hasPermission('hebergement.residents.view') || $admin->hasPermission('hebergement.attributions.view'), 403);
        // Bouton « Déroger » : demande des listes « Demandes irrecevables » ou « Demandes refusées », tant que la liste
        // des demandes validées de son volet n'est pas approuvée en commission (`AdmissionDerogationService::obstacle`).
        $routeDeroger = 'admin.hebergement.campagnes_admission.derogations.store';
        $deroger = $voirAdmissions && $derniere && $access->allows($admin, $routeDeroger)
            && app(\App\Http\Services\AdmissionDerogationService::class)->obstacle($etudiant) === null
            ? route($routeDeroger, ['student' => $etudiant->getKey()]) : null;
        // Dérogation approuvée sur la demande courante : la demande irrecevable est rendue recevable.
        $derogationApprouvee = $derniere ? \App\Models\AdmissionDerogation::where('demande_id', $derniere->getKey())
            ->where('status', 'approved')->latest('decided_at')->first() : null;
        $personnel = $vues->allows($admin, 'admissions.personal');
        $documents = $vues->allows($admin, 'admissions.documents');
        $historique = $vues->allows($admin, 'admissions.history');

        $statutDisciplinaire = $vues->allows($admin, 'housing.discipline') ? $etudiant->statut_disciplinaire : 'aucune';

        return Inertia::render('Admin/Hebergement/Admissions/Show', [
            'etudiant' => [
                'id' => (string) $etudiant->id_etudiant,
                'name' => $etudiant->name,
                'matricule' => $etudiant->matricule,
                'statut_inscription' => $etudiant->statut_inscription,
                'is_validation' => (bool) $etudiant->is_validation,
                'is_rejet' => (bool) $etudiant->is_rejet,
            ],
            'demande' => $derniere ? [
                'id' => (string) $derniere->getKey(),
                'numero' => $derniere->numero_demande,
                'etat' => $derniere->etat_dossier,
                'date' => $derniere->created_at ? Carbon::parse($derniere->created_at)->toDateString() : null,
            ] : null,
            'discipline' => $statutDisciplinaire !== 'aucune' ? [
                'label' => app(\App\Http\Services\CatalogueSanctions::class)->libelle($statutDisciplinaire),
                'bloquant' => app(\App\Http\Services\CatalogueSanctions::class)->estBloquant($statutDisciplinaire),
            ] : null,
            // Critères de rejet du volet de la demande (admission ou réadmission) comparés aux valeurs saisies.
            'eligibilite' => $derniere && $vues->allows($admin, 'admissions.eligibility')
                ? $this->conformite($etudiant, $derniere, $admin, $vues->allows($admin, 'housing.discipline')) + [
                    // Demande conforme, mais classée au-delà de la liste élargie de son volet : irrecevable.
                    'hors_liste' => app(\App\Http\Services\Eligibilite\RecevabiliteDemandes::class)->horsListeElargie($etudiant),
                    'derogation' => $derogationApprouvee ? [
                        'auteur' => $derogationApprouvee->decider_name,
                        'date' => $derogationApprouvee->decided_at?->format('Y-m-d H:i'),
                        'motif' => $derogationApprouvee->reason,
                    ] : null,
                ]
                : null,
            // Rejets de la demande courante (passages en « Refusé » : agent, commission ou barème), du plus récent au plus ancien.
            'rejets' => $derniere && $vues->allows($admin, 'admissions.eligibility') ? $this->rejets($derniere) : [],
            'bareme' => $vues->allows($admin, 'admissions.eligibility') ? $this->scoreBareme($derniere, $etudiant) : null,
            'documents' => $documents ? collect($etudiant->documents)->map(fn ($document) => [
                'id' => (string) $document['id_document'],
                'type' => $document['type_document'],
                'url' => $document['chemin_fichier'],
                'statut' => $document['statut'],
                'valider' => !in_array($document['statut'], ['valide', 'rejete'], true)
                    ? $url('admin.hebergement.campagnes_admission.validate_document', ['documentId' => $document['id_document'], 'statut' => 'valide']) : null,
                'rejeter' => !in_array($document['statut'], ['valide', 'rejete'], true)
                    ? $url('admin.hebergement.campagnes_admission.validate_document', ['documentId' => $document['id_document'], 'statut' => 'rejete']) : null,
            ])->values() : null,
            'profil' => $personnel ? $this->profilDemande($etudiant, $derniere) : null,
            // Toutes les demandes de l'étudiant, toutes campagnes confondues.
            'historique' => $historique ? $etudiant->demandes->sortByDesc('created_at')->values()->map(fn ($item) => [
                'id' => (string) $item->getKey(),
                'numero' => $item->numero_demande,
                'campagne_id' => $item->campagne_id ? (string) $item->campagne_id : null,
                'annee' => $item->campagne?->annee_campagne ?? $item->annee_academique_actuel,
                'date' => $item->created_at ? Carbon::parse($item->created_at)->toDateString() : null,
                'etat' => $item->etat_dossier,
            ]) : null,
            'urls' => [
                'admissions' => $url('admin.hebergement.campagnes_admission.admission'),
                'deroger' => $deroger,
            ],
        ]);
    }

    /**
     * Motifs de rejet de la demande (bloc « Motifs de rejet » de la fiche) : transitions vers « Refusé » de
     * l'historique du dossier (`DossierWorkflow`, motif obligatoire), du plus récent au plus ancien.
     *
     * @return list<array{id: string, motif: string|null, auteur: string|null, automatique: bool, date: string|null}>
     */
    private function rejets(DemandeEtudiant $demande): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('dossier_transitions')) {
            return [];
        }

        return \App\Models\DossierTransition::query()->where('demande_id', $demande->getKey())->where('nouvel_etat', 'refuse')
            ->orderByDesc('id')->get()
            ->map(fn (\App\Models\DossierTransition $transition) => [
                'id' => (string) $transition->id,
                'motif' => filled($transition->motif) ? $transition->motif : null,
                'auteur' => $transition->details['auteur_nom'] ?? null,
                'automatique' => $transition->acteur_type === 'systeme',
                'date' => $transition->created_at?->format('Y-m-d H:i'),
            ])->values()->all();
    }

    /**
     * Informations saisies par l'étudiant, regroupées comme les étapes du formulaire de demande
     * (Identité, Études, Situation, Adresse). Les informations académiques viennent de la dernière demande.
     * Après la Terminale, la moyenne enregistrée (sur 20) est la note au bac saisie sur 400 : elle est restituée sur 400.
     */
    private function profilDemande(Etudiant $etudiant, ?DemandeEtudiant $demande): array
    {
        $precedent = $demande?->niveauEtudiantPrecedent;
        $actuel = $demande?->niveauEtudiantActuel;
        $bachelier = $precedent !== null && \App\Http\Services\Eligibilite\CriteresCampagne::niveauSansAgeMax($precedent->name, $precedent->abreviation ?? null);
        $moyenne = $demande !== null && $demande->moyenne !== null && $demande->moyenne !== '' ? (float) $demande->moyenne : null;
        $credits = $demande !== null && $demande->credit_valider !== null && $demande->credit_valider !== '' ? (int) $demande->credit_valider : null;
        $filiere = filled($demande?->filiere_code) ? \App\Models\Filiere::query()->whereKey($demande->filiere_code)->value('name') : null;

        return [
            'identite' => [
                'civilite' => $etudiant->civilite?->name,
                'date_naissance' => $etudiant->date_naissance?->toDateString(),
                'lieu_naissance' => $etudiant->lieu_naissance,
                'nationalite' => $etudiant->nationalite,
                'telephone1' => $etudiant->telephone1,
                'telephone2' => $etudiant->telephone2,
                'email1' => $etudiant->email1,
                'email2' => $etudiant->email2,
                'type_piece' => $etudiant->typePiece?->name,
                'numero_piece' => $etudiant->numero_piece,
                'date_expiration' => $etudiant->date_expiration ? Carbon::parse($etudiant->date_expiration)->toDateString() : null,
                'groupe_sanguin' => $etudiant->groupeSanguin?->name,
            ],
            'etudes' => [
                'campagne' => $demande?->campagne?->annee_campagne ?? $demande?->annee_academique_actuel,
                'niveau_precedent' => $precedent?->name,
                'niveau' => $actuel?->name,
                // Redoublant : niveau précédent identique au niveau actuel (règle du formulaire).
                'redoublant' => $precedent !== null && $actuel !== null ? (string) $precedent->getKey() === (string) $actuel->getKey() : null,
                'bachelier' => $bachelier,
                'matricule' => $etudiant->matricule,
                'filiere' => $filiere,
                'moyenne' => $moyenne,
                'note_bac' => $bachelier && $moyenne !== null ? round($moyenne * DemandeHebergementRequest::NOTE_BAC_MAX / 20, 2) : null,
                'credits' => $credits,
                'credits_max' => DemandeEtudiant::CREDITS_PAR_AN,
                'ville_bac' => $demande?->ville_bac,
                'num_table_bac' => $demande?->num_table_bac,
            ],
            'situation' => [
                'boursier' => (bool) ($demande?->boursier ?? $etudiant->boursier),
                'observations' => $demande?->commentaires,
            ],
            'adresse' => [
                'region' => $etudiant->region?->name,
                'ville' => $etudiant->ville?->name,
                'adresse' => $etudiant->adresse,
                'distance' => $etudiant->distance !== null && $etudiant->distance !== '' ? (float) $etudiant->distance : null,
            ],
        ];
    }

    /**
     * Critères de rejet du volet de la demande (admission ou réadmission) comparés aux valeurs saisies par l'étudiant,
     * avec le lien vers les critères de la campagne (si l'agent peut les consulter). Aide à l'étude : la décision
     * reste prise par le workflow.
     */
    private function conformite(Etudiant $etudiant, DemandeEtudiant $demande, $admin, bool $discipline): array
    {
        $conformite = app(\App\Http\Services\Eligibilite\ConformiteDemande::class)->evaluer($etudiant, $demande, $discipline);
        $vue = app(\App\Http\Services\Eligibilite\CriteresCampagneVue::class);
        $campagne = $demande->campagne;

        return $conformite + [
            'lien' => $campagne && $vue->visible($admin) ? $vue->lienPage($admin, $campagne, $conformite['volet']) : null,
        ];
    }

    /**
     * Score du barème de scoring actif de la campagne du dossier, détaillé par volet et calculé sur les données
     * actuelles ; rappelle le score retenu lors de la décision automatique s'il a changé depuis (données modifiées).
     */
    private function scoreBareme(?DemandeEtudiant $dossier, Etudiant $etudiant): ?array
    {
        if (!$dossier || !Schema::hasTable('campaign_scorings')) return null;
        $config = CampaignScoring::where('campagne_id', $dossier->campagne_id)->latest('id')->first();
        if (!$config || !$config->active) return null;
        $decision = $this->decisionAutomatique($dossier);
        try {
            $detail = app(CampaignScoringService::class)->detail($etudiant, $dossier, $config);
        } catch (RuntimeException $e) {
            // Donnée manquante (moyenne, distance, bourse) : message sans l'identifiant technique du dossier.
            $detail = ['score' => null, 'minimum' => (float) $config->minimum_score, 'volets' => [],
                'erreur' => preg_replace('/ pour le dossier .*$/u', '', $e->getMessage()).'.'];
        }

        return $detail + [
            'erreur' => null,
            'score_decision' => $decision && $decision['score'] !== $detail['score'] ? $decision['score'] : null,
        ];
    }

    /** Dernière décision prise par le barème (score, minimum) si elle est à l'origine de l'état actuel. */
    private function decisionAutomatique($dossier): ?array
    {
        $transition = $dossier->transitions->sortBy('id')->last();
        $details = $transition?->details ?? [];
        if ($transition?->acteur_type !== 'systeme' || ($details[DecisionAutomatiqueDossier::MARQUEUR] ?? false) !== true) return null;

        return ['etat' => $transition->nouvel_etat, 'score' => (float) ($details['score'] ?? 0), 'minimum' => (float) ($details['minimum_score'] ?? 0)];
    }

    /**
     * Listes provisoires d'admission et de réadmission des demandes recevables (`ClassementProvisoire`), pour la
     * campagne active (sinon celle de la demande recevable la plus récente), une par volet ouvert par la campagne
     * (une campagne d'admission seule n'a pas de liste de réadmission). Onglet ouvert : `?liste=` (un volet fermé ramène à la liste des demandes) ; la
     * recherche (`q`) filtre la liste ouverte sans changer les rangs. L'âge n'est envoyé qu'avec les données personnelles.
     *
     * `$type` : `provisoire` (listes provisoires des demandes recevables), `validee` (« Décision de la commission ») ou
     * `definitive` (« Listes définitives ») — la décision de la commission et les listes définitives mêlent les volets
     * ouverts (admissions puis réadmissions, chacune avec son rang), filtrables par type de demande (`$typeDemande`,
     * `?type=admission|readmission`) ; toutes deux ont deux onglets (`?liste=validees|attente`) : les demandes validées,
     * puis la file d'attente (sur les listes définitives : demandes approuvées depuis l'une ou l'autre, téléchargeables
     * en Excel ou PDF). Chaque onglet a son propre classement (rangs à partir de 1 par volet) : décider ou approuver
     * une demande renumérote les onglets qu'elle quitte et qu'elle rejoint.
     * Sur une liste provisoire, chaque ligne porte les trois décisions de la commission : « Valider » (dossier
     * « Recevable », `DossierWorkflow`), « Mettre en attente » (l'état du dossier ne change pas) et « Rejeter » (« Refusé »,
     * motif obligatoire), avec le droit de changer l'état du dossier ; sur la décision de la commission, une demande en
     * attente peut être validée ou rejetée, une demande validée seulement rejetée (elle passe alors dans « Demandes
     * refusées »), et le bouton « Approuver » de ses deux onglets fait passer les demandes validées et la file
     * d'attente (les deux volets, ou le type filtré) dans les listes définitives ; la liste définitive n'a pas d'action.
     *
     * @param  'provisoire'|'validee'|'definitive'  $type
     * @param  array{recevables: string[], validees: string[], en_attente: string[], definitives: string[]}  $classement  `RecevabiliteDemandes::classer()`
     * @param  ''|'admission'|'readmission'  $typeDemande  filtre de la décision de la commission et de la liste définitive (vide : les deux volets)
     */
    private function classementProvisoire(Request $request, string $type, EloquentCollection $population, array $classement, ?Campagne $campagne, string $recherche, string $typeDemande, bool $personnel, bool $documents, \Closure $url, AdminAccess $access, $admin, array $choixCampagne = []): array
    {
        [$campagne, $volets, $partage] = $this->listesProvisoires($population, $classement, $campagne);
        $listes = $partage[['provisoire' => 'provisoires', 'validee' => 'validees', 'definitive' => 'definitives'][$type]];
        $provisoire = $type === 'provisoire';
        // Décision de la commission et liste définitive : admissions et réadmissions dans une même liste, filtrable par
        // type de demande.
        $unique = $type !== 'provisoire';
        // Onglets : sur les demandes recevables, une liste provisoire par volet ouvert par la campagne (admission seule →
        // pas de réadmission) ; sur la décision de la commission et les listes définitives, les demandes validées puis la
        // file d'attente.
        $etat = ['provisoire' => 'recevables', 'validee' => 'validees', 'definitive' => 'definitives'][$type];
        $permissions = app(AdmissionPermissions::class);
        $sections = $permissions->sections($admin, $etat);
        $ouverts = $provisoire ? ['demandes', ...$volets] : ['validees', 'attente'];
        $onglets = array_values(array_intersect($ouverts, array_keys($sections)));
        if ($onglets === []) {
            $onglets = array_keys($sections);
        }
        $liste = $request->query('liste');
        $liste = in_array($liste, $onglets, true) ? $liste : $onglets[0];
        $scope = $sections[$liste];
        $peut = fn (string $action) => $permissions->allows($admin, $scope, $action);
        $voletFiltre = $unique && in_array($typeDemande, $volets, true) ? $typeDemande : null;
        // Décision de la commission / listes définitives : l'onglet ouvert retient les demandes validées ou la file
        // d'attente (listes définitives : liste d'origine de l'approbation).
        $decisionFiltre = $unique ? ($liste === 'attente' ? 'attente' : 'validee') : null;
        // Lignes de la liste ouverte, chacune avec son volet (rang propre à chaque volet).
        $lignesVolets = collect($unique ? ($voletFiltre ? [$voletFiltre] : $volets) : ($liste === 'demandes' ? [] : [$liste]))
            ->flatMap(fn (string $volet) => collect($listes[$volet] ?? [])->map(fn (array $ligne) => $ligne + ['volet' => $volet]))
            ->values();
        $source = $decisionFiltre ? $lignesVolets->where('commission', $decisionFiltre)->values() : $lignesVolets;
        // Changer d'onglet de la décision de la commission ou des listes définitives conserve le type de demande filtré.
        $lien = fn (string $onglet) => $access->allows($admin, 'admin.hebergement.campagnes_admission.admission')
            ? route('admin.hebergement.campagnes_admission.admission', ['etat' => $etat] + ($onglet === 'demandes' ? [] : ['liste' => $onglet]) + ($voletFiltre ? ['type' => $voletFiltre] : []) + $choixCampagne)
            : null;
        // Approbation en commission, même bouton (même action, même droit) sur les deux onglets : elle porte sur les
        // demandes validées et sur la file d'attente (validée puis approuvée) ; elles passent dans les listes définitives.
        $approbation = $type === 'validee' && $volets !== [] && $permissions->allows($admin, 'commission_validees', 'approuver')
            ? [
                // La campagne choisie dans le bandeau est approuvée (celle affichée).
                'url' => route('admin.hebergement.campagnes_admission.liste_validee_approuver', ['volet' => $voletFiltre ?? 'toutes'] + $choixCampagne),
                'validees' => $lignesVolets->where('commission', 'validee')->count(),
                'attente' => $lignesVolets->where('commission', 'attente')->count(),
            ]
            : null;
        // Listes définitives : tant que l'onglet ouvert n'est pas publié (type de demande filtré), il ne se télécharge pas.
        // Un seul bouton « Publier », dans l'onglet « Demandes validées » : il publie aussi la file d'attente.
        $etatPublication = null;
        $publication = null;
        if ($type === 'definitive') {
            $voletsPublies = $voletFiltre ? [$voletFiltre] : $volets;
            $etats = collect(['validees', 'attente'])->mapWithKeys(fn (string $onglet) => [$onglet => $this->etatPublication($campagne, $partage['definitives'], $voletsPublies, $onglet)]);
            $etatPublication = $etats[$liste];
            // Demandes que le bouton publiera : celles des volets de chaque onglet pas encore publiés.
            $aPublier = fn (string $onglet) => $lignesVolets->where('commission', $onglet === 'attente' ? 'attente' : 'validee')
                ->whereIn('volet', $etats[$onglet]['a_publier'])->count();
            $toutPublie = $etats->flatMap(fn (array $etat) => $etat['volets'])->isNotEmpty() && $etats->every(fn (array $etat) => $etat['a_publier'] === []);
            $publication = [
                'publiee' => $etatPublication['publiee'],
                'date' => $etatPublication['date'],
                'url' => $liste === 'validees' && ! $toutPublie && $volets !== [] && $peut('publier')
                    ? route('admin.hebergement.campagnes_admission.liste_definitive_publier', ['volet' => $voletFiltre ?? 'toutes'])
                    : null,
                'validees' => $aPublier('validees'),
                'attente' => $aPublier('attente'),
            ];
        }
        // Téléchargement de la liste provisoire ou de la liste définitive ouverte, une fois publiée (Excel, PDF
        // imprimable) ; liste définitive : type de demande filtré.
        $exports = match (true) {
            $provisoire && $liste !== 'demandes' && $peut('export') => collect(['excel', 'pdf'])
                ->mapWithKeys(fn ($format) => [$format => route('admin.hebergement.campagnes_admission.liste_provisoire_export', ['volet' => $liste, 'format' => $format] + $choixCampagne)])->all(),
            $type === 'definitive' && $etatPublication['publiee'] && $peut('export') => collect(['excel', 'pdf'])
                ->mapWithKeys(fn ($format) => [$format => route('admin.hebergement.campagnes_admission.liste_definitive_export', ['liste' => $liste, 'volet' => $voletFiltre ?? 'toutes', 'format' => $format])])->all(),
            default => null,
        };
        // Décision de la commission : demandes déjà approuvées pour la campagne depuis chaque onglet (type de demande
        // filtré) ; un onglet vide dont la liste a déjà été approuvée l'indique au lieu de « Aucune demande ».
        $approuvees = $type === 'validee'
            ? collect($voletFiltre ? [$voletFiltre] : $volets)->flatMap(fn (string $volet) => $partage['definitives'][$volet] ?? [])
            : collect();
        // Validation / rejet avec les droits de la liste courante depuis la liste provisoire et pour une demande en
        // attente, rejet seul pour une demande validée, aucune action sur la liste définitive ; mise en attente depuis la
        // liste provisoire et depuis les demandes validées (le dossier revient alors en « Soumis »).
        $decisionsAutorisees = array_keys(array_filter(['eligible' => $peut('valider'), 'refuse' => $peut('refuser')]));
        $peutMettreEnAttente = ($provisoire || ($type === 'validee' && $liste === 'validees'))
            && $peut('attente');
        // Liste provisoire clôturée : la liste des demandes validées du volet a été approuvée en commission ; ses
        // demandes ne peuvent plus être validées ni mises en attente (seulement rejetées).
        $clotures = $provisoire ? app(\App\Http\Services\Eligibilite\VerrouListeProvisoire::class)->voletsClotures($campagne ? (string) $campagne->getKey() : null) : [];

        // « Tout valider » (liste provisoire des admissions) : partage des demandes entre « Demandes validées » (places
        // libres) et « File d'attente » ; pas de bouton sur une liste clôturée, ni sans les droits de valider et de mettre
        // en attente.
        $validationGroupee = null;
        if ($provisoire && $liste === 'admission' && $campagne && ! in_array($liste, $clotures, true) && $peut('valider') && $peut('attente')) {
            $nombre = count($this->demandesADecider($listes[$liste]));
            $places = app(\App\Http\Services\Eligibilite\RecevabiliteDemandes::class)->placesLibres($campagne, $liste);
            $validationGroupee = [
                'url' => route('admin.hebergement.campagnes_admission.liste_provisoire_valider', ['volet' => $liste] + $choixCampagne),
                'nombre' => $nombre,
                // null : aucune place renseignée dans les critères en vigueur (validation groupée impossible).
                'validees' => $places === null ? null : min($places, $nombre),
                'attente' => $places === null ? null : max(0, $nombre - $places),
            ];
        }

        $lignes = null;
        if ($liste !== 'demandes') {
            $motif = Str::lower(Str::ascii($recherche));
            $filtrees = $source->filter(fn (array $ligne) => $motif === '' || Str::contains(
                Str::lower(Str::ascii(implode(' ', [$ligne['etudiant']->name, $ligne['etudiant']->matricule, $ligne['etudiant']->derniereDemande?->numero_demande]))),
                $motif,
            ))->values();
            $page = max(1, (int) $request->query('page', 1));
            $courantes = $filtrees->forPage($page, 10)->values();
            // État des pièces du dossier (envoyé seulement avec le droit de voir les documents ; pas sur les listes
            // définitives, déjà approuvées en commission).
            $pieces = $documents && $type !== 'definitive' ? $this->etatPieces($courantes->map(fn (array $ligne) => $ligne['etudiant']), $campagne) : [];
            // Filières des demandes de la page (colonne « Filière » des demandes validées), en une requête.
            $codesFilieres = $courantes->map(fn (array $ligne) => $ligne['etudiant']->derniereDemande?->filiere_code)->filter(fn ($code) => filled($code))->unique()->values();
            $filieres = $codesFilieres->isEmpty() ? collect() : \App\Models\Filiere::query()->whereKey($codesFilieres->all())->pluck('name', 'id_filiere');
            // Civilité des étudiants de la page (colonne « Civilité » des demandes validées).
            EloquentCollection::make($courantes->map(fn (array $ligne) => $ligne['etudiant'])->all())->loadMissing('civilite');
            $lignes = (new LengthAwarePaginator($courantes, $filtrees->count(), 10, $page, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]))->through(function (array $ligne) use ($type, $personnel, $url, $pieces, $filieres, $decisionsAutorisees, $peutMettreEnAttente, $clotures) {
                $etudiant = $ligne['etudiant'];
                $demande = $etudiant->derniereDemande;
                // Listes définitives : `commission` est la liste d'origine de l'approbation (pas une décision en cours).
                $commission = $type === 'validee' ? ($ligne['commission'] ?? null) : null;
                $proposees = $commission === 'validee' || in_array($ligne['volet'], $clotures, true) ? ['refuse'] : ['eligible', 'refuse'];
                // Un refus ne se lève pas depuis la liste : il passe par le réexamen sur la fiche du dossier.
                $decisions = $decisionsAutorisees && $demande && $demande->etat_dossier !== 'refuse'
                    ? array_values(array_intersect($decisionsAutorisees, $proposees, DossierWorkflow::NEXT[$demande->etat_dossier] ?? []))
                    : [];
                // Mise en attente : demande recevable encore à décider, ou demande validée (retour en « Soumis » permis).
                $attente = $peutMettreEnAttente && $demande && ! in_array($ligne['volet'], $clotures, true) && ($commission === 'validee'
                    ? in_array('soumis', DossierWorkflow::NEXT[$demande->etat_dossier] ?? [], true)
                    : in_array($demande->etat_dossier, \App\Http\Services\Eligibilite\RecevabiliteDemandes::ETATS_ATTENTE, true))
                    ? route('admin.hebergement.campagnes_admission.mettre_en_attente', $demande)
                    : null;

                return [
                    'id' => (string) $etudiant->id_etudiant,
                    'volet' => $ligne['volet'],
                    'rang' => $ligne['rang'],
                    'name' => $etudiant->name,
                    'numero_demande' => $demande?->numero_demande,
                    'matricule' => filled($etudiant->matricule) ? (string) $etudiant->matricule : null,
                    'civilite' => $etudiant->civilite?->name,
                    // Date de naissance : envoyée seulement avec le droit de voir les données personnelles (comme l'âge).
                    'date_naissance' => $personnel ? $etudiant->date_naissance?->toDateString() : null,
                    'filiere' => filled($demande?->filiere_code) ? ($filieres[$demande->filiere_code] ?? null) : null,
                    'niveau' => $demande?->niveauEtudiantActuel?->name,
                    'etat_dossier' => $demande?->etat_dossier,
                    'age' => $personnel && $etudiant->date_naissance ? Carbon::parse($etudiant->date_naissance)->age : null,
                    'merite' => $ligne['merite'] ?? null,
                    'ville_bac' => $ligne['ville_bac'] ?? null,
                    'hors_ville' => $ligne['hors_ville'] ?? null,
                    'categorie' => $ligne['categorie'] ?? null,
                    'boursier' => $ligne['boursier'] ?? null,
                    'moyenne' => $ligne['moyenne'] ?? null,
                    'credits' => $ligne['credits'] ?? null,
                    'documents' => $pieces[(string) $etudiant->id_etudiant] ?? null,
                    'url' => $url('admin.hebergement.campagnes_admission.show', ['id' => $etudiant->id_etudiant]),
                    // Demande rendue recevable par dérogation : en tête de la liste provisoire, badge « Dérogé ».
                    'derogee' => (bool) ($ligne['derogee'] ?? false),
                    // Décision de la commission : demande validée ou en attente (date de la mise en attente).
                    'commission' => $commission ? [
                        'decision' => $commission,
                        'date' => $commission === 'attente' && $demande?->date_attente_commission ? Carbon::parse($demande->date_attente_commission)->format('Y-m-d H:i') : null,
                    ] : null,
                    'decision' => $decisions || $attente ? [
                        'url' => route(AdmissionPermissions::ROUTE.'decision_liste', $demande),
                        'valider' => in_array('eligible', $decisions, true),
                        'rejeter' => in_array('refuse', $decisions, true),
                        'attente' => $attente,
                    ] : null,
                ];
            });
        }

        return [
            'liste' => $liste,
            'type' => $type,
            'onglets' => $onglets,
            // Décision de la commission / listes définitives : type de demande filtré (null : admissions et réadmissions).
            'volet' => $voletFiltre,
            // Décision de la commission / listes définitives : onglet ouvert (`validee` ou `attente`) ; null ailleurs.
            'decision' => $decisionFiltre,
            'campagne' => $campagne?->annee_campagne,
            'compteurs' => [
                'demandes' => isset($sections['demandes']) ? count($classement['recevables']) : 0,
                'admission' => isset($sections['admission']) ? count($listes['admission']) : 0,
                'readmission' => isset($sections['readmission']) ? count($listes['readmission']) : 0,
                'toutes' => $provisoire
                    ? (isset($sections['admission']) ? count($listes['admission']) : 0) + (isset($sections['readmission']) ? count($listes['readmission']) : 0)
                    : (isset($sections['validees'], $sections['attente']) ? count($listes['admission']) + count($listes['readmission']) : $source->count()),
                // Onglets de la décision de la commission et des listes définitives : demandes validées / file d'attente
                // (type de demande filtré).
                'validees' => isset($sections['validees']) ? $lignesVolets->where('commission', 'validee')->count() : 0,
                'attente' => isset($sections['attente']) ? $lignesVolets->where('commission', 'attente')->count() : 0,
            ],
            // Décision de la commission : demandes déjà approuvées pour la campagne depuis chaque onglet (type de demande
            // filtré) ; null ailleurs.
            'approuvees' => $type === 'validee' ? [
                'validees' => isset($sections['validees']) ? $approuvees->where('commission', 'validee')->count() : 0,
                'attente' => isset($sections['attente']) ? $approuvees->where('commission', 'attente')->count() : 0,
            ] : null,
            'lignes' => $lignes,
            'colonnes' => ['age' => $personnel],
            'urls' => collect($onglets)->mapWithKeys(fn ($onglet) => [$onglet => $lien($onglet)]),
            'exports' => $exports,
            'approbation' => $approbation,
            'validationGroupee' => $validationGroupee,
            // Listes définitives : publication de l'onglet ouvert (`url` : bouton « Publier » des deux onglets, sur « Demandes
            // validées » seulement, null une fois tout publié ou sans droit) ; null ailleurs.
            'publication' => $publication,
        ];
    }

    /**
     * Publication d'un onglet des listes définitives (`$liste` : `validees` | `attente`) pour les volets donnés : un
     * volet est publié quand une publication enregistrée (`publication_liste_definitive`) est postérieure à la dernière
     * approbation de ses demandes ; un volet sans demande approuvée dans l'onglet ne compte pas. La liste est publiée
     * quand elle a au moins une demande et que tous ses volets sont publiés.
     *
     * @param  array{admission: array, readmission: array}  $definitives  lignes des listes définitives par volet
     * @param  list<string>  $volets
     * @return array{publiee: bool, date: ?string, volets: list<string>, a_publier: list<string>}
     */
    private function etatPublication(?Campagne $campagne, array $definitives, array $volets, string $liste): array
    {
        $origine = $liste === 'attente' ? 'attente' : 'validee';
        $lignes = collect($volets)->mapWithKeys(fn (string $volet) => [$volet => collect($definitives[$volet] ?? [])->where('commission', $origine)])
            ->filter(fn ($lignes) => $lignes->isNotEmpty());
        $avecDemandes = $lignes->keys()->all();
        if (! $campagne || $avecDemandes === [] || ! Schema::hasTable('publication_liste_definitive')) {
            return ['publiee' => false, 'date' => null, 'volets' => $avecDemandes, 'a_publier' => $avecDemandes];
        }
        $publications = \Illuminate\Support\Facades\DB::table('publication_liste_definitive')
            ->where('campagne_id', (string) $campagne->getKey())->where('liste', $liste)->whereIn('volet', $avecDemandes)
            ->pluck('date_publication', 'volet');
        $aPublier = $lignes->filter(function ($lignesVolet, string $volet) use ($publications) {
            $publiee = $publications[$volet] ?? null;
            $derniere = $lignesVolet->map(fn (array $ligne) => $ligne['etudiant']->derniereDemande?->date_approbation_commission)->filter()->max();

            return ! $publiee || ($derniere && Carbon::parse($derniere)->gt(Carbon::parse($publiee)));
        })->keys()->all();
        $date = $aPublier === [] ? collect($avecDemandes)->map(fn (string $volet) => Carbon::parse($publications[$volet]))->max()?->format('Y-m-d H:i') : null;

        return ['publiee' => $aPublier === [], 'date' => $date, 'volets' => $avecDemandes, 'a_publier' => $aPublier];
    }

    /**
     * Publication des listes définitives, « Demandes validées » et « File d'attente » ensemble (`volet` : `toutes` — les
     * volets ouverts — ou un type de demande ; classement recalculé côté serveur) : dans chaque onglet, chaque volet qui a
     * des demandes approuvées et n'est pas encore publié (ou a reçu une approbation depuis sa publication) est publié ;
     * les listes peuvent alors être téléchargées et leurs notifications sont enregistrées pour l’envoi.
     */
    public function publierListeDefinitive(Request $request, string $volet, AdmissionResultDiffusion $diffusion)
    {
        $this->autoriserListe($request, 'definitive_validees', 'publier');
        if (! Schema::hasTable('publication_liste_definitive') || ! Schema::hasColumn(AdmissionResultDiffusion::TABLE, 'body_html')) {
            throw ValidationException::withMessages(['publication' => 'La publication n’est pas encore disponible : la base de données doit être mise à jour.']);
        }
        $admin = $request->user('admin');
        $population = app(\App\Repositories\EtudiantRepository::class)->getPopulationDemandes();
        $classement = app(\App\Http\Services\Eligibilite\RecevabiliteDemandes::class)->classer($population);
        [$campagne, $volets, $listes] = $this->listesProvisoires($population, $classement, $this->campagneActive());
        $publies = $volet === 'toutes' ? $volets : array_values(array_intersect([$volet], $volets));

        if (! $campagne) {
            throw ValidationException::withMessages(['publication' => 'Aucune demande approuvée à publier.']);
        }

        [$publiees, $notifications] = DB::transaction(function () use ($campagne, $listes, $publies, $admin, $population, $classement, $diffusion) {
            Campagne::query()->whereKey($campagne->getKey())->lockForUpdate()->firstOrFail();

            $etats = collect(['validees', 'attente'])
                ->mapWithKeys(fn (string $liste) => [$liste => $this->etatPublication($campagne, $listes['definitives'], $publies, $liste)]);
            if ($etats->every(fn (array $etat) => $etat['volets'] === [])) {
                throw ValidationException::withMessages(['publication' => 'Aucune demande approuvée à publier.']);
            }
            $etats = $etats->filter(fn (array $etat) => $etat['a_publier'] !== []);
            if ($etats->isEmpty()) {
                throw ValidationException::withMessages(['publication' => 'Les listes définitives sont déjà publiées.']);
            }

            $date = now();
            $publications = $etats->flatMap(fn (array $etat, string $liste) => array_map(fn (string $v) => [
                'id' => (string) Str::uuid(),
                'campagne_id' => (string) $campagne->getKey(),
                'liste' => $liste,
                'volet' => $v,
                'date_publication' => $date,
                'publiee_par' => (string) $admin?->getKey(),
                'created_at' => $date,
                'updated_at' => $date,
            ], $etat['a_publier']))->values()->all();
            DB::table('publication_liste_definitive')->upsert($publications, ['campagne_id', 'liste', 'volet'], ['date_publication', 'publiee_par', 'updated_at']);

            $notifications = 0;
            foreach ($etats as $liste => $etat) {
                $notifications += $diffusion->prepare($campagne, $population, $classement, $listes['definitives'], $etat['a_publier'], $liste);
            }

            return [$etats->keys()->all(), $notifications];
        }, 3);

        // Envoi dès la réponse rendue, sans attendre le planificateur (`admissions:notifier`), qui reprend les restes.
        defer(fn () => $diffusion->sendAll());

        $message = match ($publiees) {
            ['validees'] => 'Liste des demandes validées publiée : elle peut être téléchargée.',
            ['attente'] => 'File d’attente publiée : elle peut être téléchargée.',
            default => 'Liste des demandes validées et file d’attente publiées : elles peuvent être téléchargées.',
        };

        // Onglet « File d'attente » quand elle seule a été publiée.
        return redirect()->route('admin.hebergement.campagnes_admission.admission', ['etat' => 'definitives'] + ($publiees === ['attente'] ? ['liste' => 'attente'] : []) + ($volet !== 'toutes' ? ['type' => $volet] : []))
            ->with('success', $message.match (true) {
                $notifications > 0 => ' Les notifications de résultats sont en cours d’envoi.',
                ! in_array(true, AdmissionResultDiffusion::channels(\App\Models\ConfigApp::query()->first()), true) => ' Aucune notification programmée : l’e-mail et le SMS sont désactivés (Configurations système › Notifications).',
                default => ' Aucune notification programmée : l’envoi des messages concernés est désactivé (Paramétrage › Configuration des messages).',
            });
    }

    /**
     * État des pièces du dossier de chaque étudiant pour la campagne : dernière version déposée de chaque pièce (hors
     * versions obsolètes), comparée aux pièces obligatoires de la campagne (`champs_requis`).
     * `statut` : `valide` (toutes les pièces déposées sont validées), `incomplet` (au moins une pièce rejetée ou une pièce
     * obligatoire absente), `en_verification` (pièces en attente de vérification, aucune rejetée ni absente) ;
     * pas d'entrée quand aucune pièce n'est demandée ni déposée.
     *
     * @param  \Illuminate\Support\Collection<int, Etudiant>  $etudiants
     * @return array<string, array{statut: string, valides: int, rejetes: int, en_verification: int, absents: int}>
     */
    private function etatPieces(\Illuminate\Support\Collection $etudiants, ?Campagne $campagne): array
    {
        if ($etudiants->isEmpty() || ! Schema::hasTable('document_etudiant')) {
            return [];
        }
        $requises = $campagne && Schema::hasTable('document_campagne') && Schema::hasTable('document')
            ? \App\Models\DocumentCampagne::with('documents')->where('id_campagne', $campagne->getKey())->get()
                ->flatMap(fn ($lien) => $lien->documents)
                ->filter(fn ($document) => (bool) $document->champs_requis)
                ->pluck('name')->unique()->values()
            : collect();
        $colonnes = Schema::getColumnListing('document_etudiant');
        $deposes = \Illuminate\Support\Facades\DB::table('document_etudiant')
            ->whereIn('etudiant_id', $etudiants->map(fn (Etudiant $etudiant) => (string) $etudiant->getKey())->all())
            ->when(in_array('statut', $colonnes, true), fn ($requete) => $requete->where(fn ($q) => $q->whereNull('statut')->orWhere('statut', '!=', 'obsolete')))
            // Pièces de la campagne (les anciennes pièces sans campagne restent prises en compte).
            ->when($campagne && in_array('id_campagne', $colonnes, true), fn ($requete) => $requete->where(fn ($q) => $q->whereNull('id_campagne')->orWhere('id_campagne', $campagne->getKey())))
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('etudiant_id');

        $etats = [];
        foreach ($etudiants as $etudiant) {
            // Dernière version de chaque pièce.
            $pieces = collect($deposes->get((string) $etudiant->getKey(), []))->unique(fn ($piece) => $piece->type_document ?? $piece->id_document);
            $statuts = $pieces->map(fn ($piece) => $piece->statut ?? 'en_verification');
            $absents = $requises->diff($pieces->pluck('type_document')->filter())->count();
            if ($pieces->isEmpty() && $absents === 0) {
                continue;
            }
            $compte = [
                'valides' => $statuts->filter(fn ($statut) => $statut === 'valide')->count(),
                'rejetes' => $statuts->filter(fn ($statut) => $statut === 'rejete')->count(),
                'en_verification' => $statuts->reject(fn ($statut) => in_array($statut, ['valide', 'rejete'], true))->count(),
                'absents' => $absents,
            ];
            $etats[(string) $etudiant->getKey()] = [
                'statut' => $compte['rejetes'] > 0 || $absents > 0 ? 'incomplet' : ($compte['en_verification'] > 0 ? 'en_verification' : 'valide'),
            ] + $compte;
        }

        return $etats;
    }

    /**
     * Campagne des listes provisoires (campagne active, sinon celle de la demande recevable, validée, en attente ou
     * approuvée la plus récente), volets qu'elle ouvre et demandes classées par volet (`ClassementProvisoire`) : le
     * classement est partagé entre les listes provisoires
     * (demandes encore à décider), la décision de la commission (demandes validées ou en attente, chaque ligne portant sa
     * décision : `commission` = `validee` | `attente`) et les listes définitives (chaque ligne portant la liste d'origine
     * de l'approbation : `commission` = `validee` | `attente`, `validee` pour une approbation sans liste enregistrée),
     * chaque onglet (liste et volet) étant renuméroté à partir de 1.
     *
     * @param  array{recevables: string[], validees: string[], en_attente: string[], definitives: string[]}  $classement  `RecevabiliteDemandes::classer()`
     * @return array{0: ?Campagne, 1: list<string>, 2: array<'provisoires'|'validees'|'definitives', array{admission: array, readmission: array}>}
     */
    private function listesProvisoires(EloquentCollection $population, array $classement, ?Campagne $campagne): array
    {
        $classees = $population->whereIn('id_etudiant', [...$classement['recevables'], ...$classement['validees'], ...$classement['en_attente'], ...$classement['definitives']]);
        $campagne ??= $classees->map(fn (Etudiant $etudiant) => $etudiant->derniereDemande)->filter()
            ->sortByDesc('created_at')->first()?->campagne;
        $criteres = app(\App\Http\Services\Eligibilite\CriteresCampagne::class);
        $vides = ['admission' => [], 'readmission' => []];
        if (! $campagne) {
            return [null, [], ['provisoires' => $vides, 'validees' => $vides, 'definitives' => $vides]];
        }
        $listes = app(\App\Http\Services\Eligibilite\ClassementProvisoire::class)->classer(
            $classees,
            $campagne,
            $criteres->voletsDemandes($classees->map(fn (Etudiant $etudiant) => $etudiant->derniereDemande)),
        );
        $partage = ['provisoires' => $vides, 'validees' => $vides, 'definitives' => $vides];
        // Demandes rendues recevables par dérogation (badge « Dérogé ») : en tête de la liste provisoire de leur volet,
        // dans l'ordre du classement.
        $derogees = app(\App\Http\Services\Eligibilite\RecevabiliteDemandes::class)
            ->derogationsApprouvees($classees->map(fn (Etudiant $etudiant) => $etudiant->derniereDemande)->filter());
        // Rang propre à chaque onglet : renumérotation à partir de 1 dans l'ordre du classement, par liste d'origine
        // (`commission`) pour la décision de la commission et les listes définitives. L'ordre du classement ne dépend
        // que de chaque demande : il est le même que celui d'un classement de l'onglet seul.
        $rangs = function (\Illuminate\Support\Collection $lignes): array {
            $compteurs = [];

            return $lignes->map(function (array $ligne) use (&$compteurs) {
                $onglet = $ligne['commission'] ?? '';
                $compteurs[$onglet] = ($compteurs[$onglet] ?? 0) + 1;

                return ['rang' => $compteurs[$onglet]] + $ligne;
            })->values()->all();
        };
        foreach ($listes as $volet => $lignes) {
            $lignes = collect($lignes)->map(fn (array $ligne) => $ligne + [
                'derogee' => in_array((string) $ligne['etudiant']->derniereDemande?->getKey(), $derogees, true),
            ]);
            $dans = fn (array $ids) => $lignes->filter(fn (array $ligne) => in_array((string) $ligne['etudiant']->getKey(), $ids, true))->values();
            $partage['provisoires'][$volet] = $rangs($dans($classement['recevables'])->sortBy(fn (array $ligne) => $ligne['derogee'] ? 0 : 1)->values());
            // Décision de la commission : demandes validées et en attente, dans l'ordre du classement.
            $partage['validees'][$volet] = $rangs($dans([...$classement['validees'], ...$classement['en_attente']])
                ->map(fn (array $ligne) => $ligne + ['commission' => in_array((string) $ligne['etudiant']->getKey(), $classement['en_attente'], true) ? 'attente' : 'validee']));
            $partage['definitives'][$volet] = $rangs($dans($classement['definitives'])
                ->map(fn (array $ligne) => $ligne + ['commission' => $ligne['etudiant']->derniereDemande?->liste_approbation_commission === 'attente' ? 'attente' : 'validee']));
        }

        return [$campagne, array_keys(array_filter($criteres->volets($campagne))), $partage];
    }

    /**
     * Téléchargement d'une liste provisoire complète (tous les rangs, sans la recherche de l'écran) : classeur Excel
     * ou PDF à imprimer (ouvert dans un nouvel onglet). L'âge n'y figure qu'avec le droit de voir les données personnelles.
     */
    public function exportListeProvisoire(Request $request, string $volet, string $format)
    {
        $this->autoriserListe($request, 'provisoire_'.$volet, 'export');
        $admin = $request->user('admin');
        $personnel = app(AdminViewAccess::class)->allows($admin, 'admissions.personal');
        // Demandes de la campagne choisie dans la liste des demandes (`?campagne=`, par défaut la campagne active).
        $campagne = $this->campagneSelectionnee($request);
        $population = $this->populationCampagne(app(\App\Repositories\EtudiantRepository::class)->getPopulationDemandes(), $campagne);
        $classement = app(\App\Http\Services\Eligibilite\RecevabiliteDemandes::class)->classer($population);
        [$campagne, $volets, $listes] = $this->listesProvisoires($population, $classement, $campagne);
        // Liste provisoire : demandes encore à décider (les demandes validées ou en attente figurent dans la « Décision de la commission »).
        $listes = $listes['provisoires'];
        if (! in_array($volet, $volets, true)) {
            return redirect()->route('admin.hebergement.campagnes_admission.admission', ['etat' => 'recevables'])
                ->with('error', 'Cette liste provisoire n’est pas disponible pour la campagne.');
        }

        $readmission = $volet === 'readmission';
        [$entetes, $lignes] = $this->lignesExport(collect($listes[$volet])->map(fn (array $ligne) => $ligne + ['volet' => $volet]), $volet, $personnel);

        return $this->telecharger($format, [
            'titre' => 'Liste provisoire des '.($readmission ? 'réadmissions' : 'admissions'),
            'campagne' => $campagne->annee_campagne,
            'entetes' => $entetes,
            'lignes' => $lignes,
        ], 'liste-provisoire-'.($readmission ? 'readmission' : 'admission').'-'.Str::slug((string) $campagne->annee_campagne), $readmission ? 'Réadmissions' : 'Admissions');
    }

    /**
     * Téléchargement d'une liste définitive complète (`liste` : demandes approuvées depuis les « Demandes validées » ou la
     * « File d'attente » ; `volet` : `toutes` — admissions puis réadmissions — ou un type de demande ; tous les rangs,
     * sans la recherche de l'écran) : classeur Excel ou PDF à imprimer. L'âge n'y figure qu'avec le droit de voir les
     * données personnelles.
     */
    public function exportListeDefinitive(Request $request, string $liste, string $volet, string $format)
    {
        $this->autoriserListe($request, 'definitive_'.$liste, 'export');
        $admin = $request->user('admin');
        $personnel = app(AdminViewAccess::class)->allows($admin, 'admissions.personal');
        $population = app(\App\Repositories\EtudiantRepository::class)->getPopulationDemandes();
        $classement = app(\App\Http\Services\Eligibilite\RecevabiliteDemandes::class)->classer($population);
        [$campagne, $volets, $listes] = $this->listesProvisoires($population, $classement, $this->campagneActive());
        $exportes = $volet === 'toutes' ? $volets : array_values(array_intersect([$volet], $volets));
        if (! $campagne || $exportes === []) {
            return redirect()->route('admin.hebergement.campagnes_admission.admission', ['etat' => 'definitives'])
                ->with('error', 'Cette liste définitive n’est pas disponible pour la campagne.');
        }
        // Une liste définitive ne se télécharge qu'une fois publiée.
        if (! $this->etatPublication($campagne, $listes['definitives'], $exportes, $liste)['publiee']) {
            return redirect()->route('admin.hebergement.campagnes_admission.admission', ['etat' => 'definitives'] + ($liste === 'attente' ? ['liste' => 'attente'] : []))
                ->with('error', 'Cette liste définitive n’est pas encore publiée : publiez-la avant de la télécharger.');
        }
        $origine = $liste === 'attente' ? 'attente' : 'validee';
        $lignes = collect($exportes)
            ->flatMap(fn (string $v) => collect($listes['definitives'][$v])->where('commission', $origine)->map(fn (array $ligne) => $ligne + ['volet' => $v]))
            ->values();
        $unVolet = count($exportes) === 1 ? $exportes[0] : null;
        // PDF des demandes validées : mêmes colonnes que l'écran (rang, matricule, nom et prénoms, filière, niveau), A4
        // portrait ; l'Excel garde le détail complet.
        $pdfValidees = $format === 'pdf' && $origine === 'validee';
        [$entetes, $lignes] = $pdfValidees ? $this->lignesExportValidees($lignes, $personnel) : $this->lignesExport($lignes, $unVolet, $personnel);
        $nomListe = $origine === 'attente' ? 'file d’attente' : 'demandes validées';
        $type = $unVolet === null ? '' : ($unVolet === 'readmission' ? ' des réadmissions' : ' des admissions');

        return $this->telecharger($format, [
            'titre' => 'Liste définitive'.$type.' — '.$nomListe,
            'campagne' => $campagne->annee_campagne,
            'entetes' => $entetes,
            'lignes' => $lignes,
            'resume' => 'demande(s) approuvée(s) en commission',
            'vide' => 'Aucune demande approuvée dans cette liste.',
            'pied' => 'Liste définitive approuvée en commission : le classement ne vaut pas attribution d’une chambre.',
        ], 'liste-definitive-'.($origine === 'attente' ? 'file-attente' : 'validees').'-'.($unVolet ?? 'toutes').'-'.Str::slug((string) $campagne->annee_campagne), $origine === 'attente' ? 'File d’attente' : 'Demandes validées', $pdfValidees ? 'portrait' : 'landscape');
    }

    /**
     * Colonnes et lignes du PDF des demandes validées de la liste définitive, dans l'ordre du classement : rang,
     * matricule, civilité, nom et prénoms, date de naissance (seulement avec le droit de voir les données personnelles),
     * filière, niveau (civilités et filières chargées en une requête chacune).
     *
     * @param  \Illuminate\Support\Collection<int, array>  $lignes  lignes du classement
     * @return array{0: list<string>, 1: list<list<mixed>>}
     */
    private function lignesExportValidees(\Illuminate\Support\Collection $lignes, bool $personnel): array
    {
        EloquentCollection::make($lignes->map(fn (array $ligne) => $ligne['etudiant'])->all())->loadMissing('civilite');
        $codes = $lignes->map(fn (array $ligne) => $ligne['etudiant']->derniereDemande?->filiere_code)->filter(fn ($code) => filled($code))->unique()->values();
        $filieres = $codes->isEmpty() ? collect() : \App\Models\Filiere::query()->whereKey($codes->all())->pluck('name', 'id_filiere');
        $donnees = $lignes->map(function (array $ligne) use ($filieres, $personnel) {
            $etudiant = $ligne['etudiant'];
            $demande = $etudiant->derniereDemande;

            return array_merge(
                [$ligne['rang'], (string) $etudiant->matricule, (string) $etudiant->civilite?->name, (string) $etudiant->name],
                $personnel ? [(string) $etudiant->date_naissance?->format('d/m/Y')] : [],
                [
                    filled($demande?->filiere_code) ? (string) ($filieres[$demande->filiere_code] ?? '') : '',
                    (string) $demande?->niveauEtudiantActuel?->name,
                ],
            );
        })->all();
        $entetes = array_merge(['Rang', 'Matricule', 'Civilité', 'Nom & prénoms'], $personnel ? ['Date de naissance'] : [], ['Filière', 'Niveau']);

        return [$entetes, $donnees];
    }

    /**
     * Colonnes et lignes d'une liste téléchargée, dans l'ordre du classement. `$volet` : volet unique de la liste
     * (colonnes propres à l'admission ou à la réadmission) ; null : admissions et réadmissions mêlées (type de demande et
     * mérite de chaque ligne).
     *
     * @param  \Illuminate\Support\Collection<int, array>  $lignes  lignes du classement, chacune avec son `volet`
     * @return array{0: list<string>, 1: list<list<mixed>>}
     */
    private function lignesExport(\Illuminate\Support\Collection $lignes, ?string $volet, bool $personnel): array
    {
        $nombre = fn ($valeur) => $valeur === null ? '' : rtrim(rtrim(number_format((float) $valeur, 2, ',', ' '), '0'), ',');
        $boursier = fn (array $ligne) => ($ligne['boursier'] ?? false) ? 'Oui' : 'Non';
        $note = fn (array $ligne) => ($merite = $ligne['merite'] ?? null) ? $nombre($merite['valeur']).' / '.$merite['max'] : '';
        $entetes = array_merge(
            ['Rang', 'Nom et prénoms', 'Matricule', 'N° de demande'],
            $volet === null ? ['Type de demande'] : [],
            ['Niveau'],
            match ($volet) {
                'readmission' => ['Catégorie', 'Boursier', 'Moyenne (/20)', 'Crédits validés (%)'],
                'admission' => ['Boursier', 'Note', '% du maximum', 'Ville du bac'],
                default => ['Boursier', 'Mérite', 'Ville du bac'],
            },
            $personnel ? ['Âge'] : [],
        );
        $donnees = $lignes->map(function (array $ligne) use ($volet, $personnel, $nombre, $boursier, $note) {
            $etudiant = $ligne['etudiant'];
            $demande = $etudiant->derniereDemande;
            $merite = $ligne['merite'] ?? null;
            $readmission = $ligne['volet'] === 'readmission';
            $moyenne = $ligne['moyenne'] ?? null;
            $credits = $ligne['credits'] ?? null;

            return array_merge(
                [$ligne['rang'], (string) $etudiant->name, (string) $etudiant->matricule, (string) $demande?->numero_demande],
                $volet === null ? [$readmission ? 'Réadmission' : 'Admission'] : [],
                [(string) $demande?->niveauEtudiantActuel?->name],
                match ($volet) {
                    'readmission' => [
                        ($ligne['categorie'] ?? null) ? $ligne['categorie']['code'].' · '.$ligne['categorie']['libelle'] : '',
                        $boursier($ligne),
                        $nombre($moyenne),
                        $nombre($credits),
                    ],
                    'admission' => [$boursier($ligne), $note($ligne), $merite ? $nombre($merite['pourcentage']) : '', (string) ($ligne['ville_bac'] ?? '')],
                    // Liste mêlée : moyenne et crédits (réadmission), ou note et pourcentage du maximum (admission).
                    default => [
                        $boursier($ligne),
                        $readmission
                            ? implode(' · ', array_filter([$moyenne !== null ? $nombre($moyenne).' / 20' : '', $credits !== null ? $nombre($credits).' % des crédits' : '']))
                            : ($merite ? $note($ligne).' ('.$nombre($merite['pourcentage']).' %)' : ''),
                        $readmission ? '' : (string) ($ligne['ville_bac'] ?? ''),
                    ],
                },
                $personnel ? [$etudiant->date_naissance ? Carbon::parse($etudiant->date_naissance)->age : ''] : [],
            );
        })->all();

        return [$entetes, $donnees];
    }

    /**
     * Liste téléchargée : classeur Excel (`$feuille`), ou PDF à imprimer (vue `pdf.liste-provisoire`, A4 paysage par
     * défaut, `$orientation`) ouvert dans un nouvel onglet.
     *
     * @param  array{titre: string, campagne: ?string, entetes: list<string>, lignes: list<list<mixed>>, resume?: string, vide?: string, pied?: string}  $vue
     * @param  'portrait'|'landscape'  $orientation
     */
    private function telecharger(string $format, array $vue, string $fichier, string $feuille, string $orientation = 'landscape')
    {
        if ($format === 'excel') {
            return \App\Support\SimpleXlsx::download($fichier.'.xlsx', $feuille, $vue['entetes'], $vue['lignes']);
        }

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.liste-provisoire', $vue)->setPaper('a4', $orientation)->stream($fichier.'.pdf');
    }

    /**
     * Approbation en commission de la « Décision de la commission » (même bouton sur ses deux onglets) : toutes
     * les demandes de la liste, validées et en file d'attente (`toutes` : les deux volets ouverts ; sinon le type de
     * demande filtré ; classement recalculé côté serveur, sans la recherche de l'écran), sont approuvées
     * (`demande.date_approbation_commission`) et passent chacune dans son onglet des « Listes définitives ».
     * - Demandes validées : l'état du dossier ne change pas (« Recevable »).
     * - File d'attente : chaque demande est d'abord validée (transition `eligible` par `DossierWorkflow`, comme « Valider » :
     *   l'étudiant reçoit ses accès). Une demande que le workflow refuse de valider (sanction bloquante, campagne close…)
     *   reste dans la file d'attente ; le message en donne le motif.
     */
    public function approuverListeValidee(Request $request, string $volet)
    {
        $this->autoriserListe($request, 'commission_validees', 'approuver');
        $admin = $request->user('admin');
        [$volets, $listes] = $this->listesCommission($request);
        $approuves = $volet === 'toutes' ? $volets : array_values(array_intersect([$volet], $volets));
        if ($approuves === []) {
            throw ValidationException::withMessages(['approbation' => 'Cette liste n’est pas disponible pour la campagne.']);
        }
        $lignes = collect($approuves)->flatMap(fn (string $v) => $listes['validees'][$v]);
        $demandes = fn (string $decision) => $lignes->where('commission', $decision)
            ->map(fn (array $ligne) => $ligne['etudiant']->derniereDemande?->getKey())
            ->filter()->map(fn ($id) => (string) $id)->values();
        $validees = $demandes('validee');
        $attente = $demandes('attente');
        if ($validees->isEmpty() && $attente->isEmpty()) {
            throw ValidationException::withMessages(['approbation' => 'Aucune demande à approuver dans cette liste.']);
        }
        $approbation = fn (string $liste) => ['date_approbation_commission' => now(), 'approbation_commission_par' => (string) $admin?->getKey(), 'updated_at' => now()] + $this->listeApprobation($liste);

        $nombreValidees = \Illuminate\Support\Facades\DB::table('demande')->whereIn('id_demande', $validees->all())
            ->where('etat_dossier', \App\Http\Services\Eligibilite\RecevabiliteDemandes::ETAT_VALIDE)
            ->update($approbation('validees'));

        $workflow = app(DossierWorkflow::class);
        $nombreAttente = 0;
        $echecs = [];
        foreach ($attente as $id) {
            try {
                $workflow->admin($id, 'eligible', null, [], ['commission_validees', 'approuver']);
            } catch (ValidationException $exception) {
                $echecs[$id] = (string) collect($exception->errors())->flatten()->first();

                continue;
            }
            // Approbation postérieure à la validation : la demande figure dans la liste définitive.
            \Illuminate\Support\Facades\DB::table('demande')->where('id_demande', $id)->update($approbation('attente'));
            $nombreAttente++;
        }
        $approuvees = $nombreValidees + $nombreAttente;
        if ($approuvees === 0) {
            throw ValidationException::withMessages(['approbation' => 'Aucune demande n’a pu être approuvée'.($echecs ? ' : '.collect($echecs)->first() : '.')]);
        }
        $message = $approuvees > 1
            ? "{$approuvees} demandes approuvées".($nombreAttente > 0 ? " (dont {$nombreAttente} de la file d’attente)" : '').' : elles figurent dans les listes définitives.'
            : 'Demande approuvée : elle figure dans les listes définitives.';
        if ($echecs) {
            $message .= ' '.count($echecs).' demande(s) restée(s) en attente : '.collect($echecs)->first();
        }

        // Onglet « File d'attente » des listes définitives quand seule la file d'attente a été approuvée.
        return redirect()->route('admin.hebergement.campagnes_admission.admission', ['etat' => 'definitives']
            + ($nombreValidees === 0 ? ['liste' => 'attente'] : [])
            + (count($approuves) === 1 ? ['type' => $approuves[0]] : []))
            ->with('success', $message);
    }

    /**
     * Volets ouverts et listes de la décision de la commission pour la campagne choisie dans le bandeau (`?campagne=`,
     * par défaut la campagne active, sinon la plus récente) : mêmes demandes que celles affichées, classement recalculé
     * côté serveur.
     *
     * @return array{0: list<string>, 1: array<'provisoires'|'validees'|'definitives', array{admission: array, readmission: array}>}
     */
    private function listesCommission(Request $request): array
    {
        $campagne = $this->campagneSelectionnee($request);
        $population = $this->populationCampagne(app(\App\Repositories\EtudiantRepository::class)->getPopulationDemandes(), $campagne);
        $classement = app(\App\Http\Services\Eligibilite\RecevabiliteDemandes::class)->classer($population);
        [, $volets, $listes] = $this->listesProvisoires($population, $classement, $campagne);

        return [$volets, $listes];
    }

    /**
     * Liste d'origine d'une approbation en commission (`validees` | `attente`), enregistrée sur la demande quand la
     * colonne existe (migration `2026_11_20_000003`) : onglet de la demande dans les listes définitives.
     */
    private function listeApprobation(string $liste): array
    {
        return Schema::hasColumn('demande', 'liste_approbation_commission') ? ['liste_approbation_commission' => $liste] : [];
    }

    /**
     * Mise en attente par la commission : la demande (dernière demande de l'étudiant) passe dans la « File d'attente » de
     * la « Décision de la commission », où elle sera validée ou rejetée ; aucun motif n'est demandé.
     * - depuis une liste provisoire : demande recevable, dossier encore « Soumis » / « En étude » — l'état du dossier ne
     *   change pas ;
     * - depuis les « Demandes validées » (pas encore approuvées) : le dossier revient d'abord de « Recevable » à « Soumis »
     *   (transition `soumis` par `DossierWorkflow`, tracée dans l'historique), puis la demande est mise en attente.
     */
    private function autoriserListe(Request $request, ?string $scope, string $action): void
    {
        abort_unless($scope && app(AdmissionPermissions::class)->allows($request->user('admin'), $scope, $action), 403);
    }

    private function scopeDossier(Etudiant $etudiant, ?string $classe): ?string
    {
        return match ($classe) {
            \App\Http\Services\Eligibilite\RecevabiliteDemandes::RECEVABLE => 'provisoire_'.(
                app(\App\Http\Services\Eligibilite\CriteresCampagne::class)->voletsDemandes([$etudiant->derniereDemande])[(string) $etudiant->getKey()] ?? ''
            ),
            \App\Http\Services\Eligibilite\RecevabiliteDemandes::VALIDEE => 'commission_validees',
            \App\Http\Services\Eligibilite\RecevabiliteDemandes::EN_ATTENTE => 'commission_attente',
            default => null,
        };
    }

    public function deciderDansListe(Request $request, DemandeEtudiant $demande)
    {
        $data = $request->validate(['etat' => 'required|in:eligible,refuse', 'motif' => 'nullable|string|max:5000']);
        $etudiant = Etudiant::with('derniereDemande.campagne')->findOrFail($demande->etudiant_id);
        abort_unless((string) $etudiant->derniereDemande?->getKey() === (string) $demande->getKey(), 403);
        $classe = app(\App\Http\Services\Eligibilite\RecevabiliteDemandes::class)->classe($etudiant);
        $scope = $this->scopeDossier($etudiant, $classe);
        $action = $data['etat'] === 'eligible' ? 'valider' : 'refuser';
        $this->autoriserListe($request, $scope, $action);
        $updated = app(DossierWorkflow::class)->admin($demande->getKey(), $data['etat'], $data['motif'] ?? null, [], [$scope, $action]);

        return $request->expectsJson() ? response()->json(['data' => $updated->load('transitions')])
            : back()->with('success', 'Dossier : '.DossierWorkflow::LABELS[$updated->etat_dossier].'.');
    }

    /**
     * Demandes d'une liste provisoire encore à décider (« Soumis » / « En étude »), dans l'ordre du classement.
     *
     * @return list<string>
     */
    private function demandesADecider(array $lignes): array
    {
        return collect($lignes)->map(fn (array $ligne) => $ligne['etudiant']->derniereDemande)
            ->filter(fn ($demande) => $demande && in_array($demande->etat_dossier, \App\Http\Services\Eligibilite\RecevabiliteDemandes::ETATS_ATTENTE, true))
            ->map(fn ($demande) => (string) $demande->getKey())->values()->all();
    }

    /**
     * « Tout valider » : décide en une fois de toutes les demandes d'une liste provisoire (tous les rangs, sans la
     * recherche de l'écran ; classement recalculé côté serveur, déjà limité à places × coefficient de la liste élargie).
     * Dans l'ordre du classement, les places libres du volet vont aux « Demandes validées » (transition `eligible` par
     * `DossierWorkflow`), les demandes suivantes passent dans la « File d'attente ». Une demande que le workflow refuse
     * de valider reste dans la liste provisoire et laisse sa place à la suivante ; le message en donne le motif.
     */
    public function validerListeProvisoire(Request $request, string $volet): RedirectResponse
    {
        $this->autoriserListe($request, 'provisoire_'.$volet, 'valider');
        $this->autoriserListe($request, 'provisoire_'.$volet, 'attente');
        $erreur = fn (string $message) => throw ValidationException::withMessages(['validation' => $message]);
        if (! Schema::hasColumn('demande', 'date_attente_commission')) {
            $erreur('La mise en attente n’est pas encore disponible : la base de données doit être mise à jour.');
        }
        $campagne = $this->campagneSelectionnee($request);
        if (! $campagne) {
            $erreur('Aucune campagne n’est disponible.');
        }
        if ($campagne->statut !== 'active') {
            $erreur('La campagne n’est plus active.');
        }
        // Un seul traitement à la fois par liste (double clic, deux agents) : les places ne sont pas attribuées deux fois.
        $verrou = \Illuminate\Support\Facades\Cache::lock('liste-provisoire-valider-'.$campagne->getKey().'-'.$volet, 600);
        if (! $verrou->get()) {
            $erreur('La validation de cette liste est déjà en cours.');
        }
        try {
            [$volets, $listes] = $this->listesCommission($request);
            if (! in_array($volet, $volets, true)) {
                $erreur('Cette liste n’est pas disponible pour la campagne.');
            }
            if (in_array($volet, app(\App\Http\Services\Eligibilite\VerrouListeProvisoire::class)->voletsClotures((string) $campagne->getKey()), true)) {
                $erreur(\App\Http\Services\Eligibilite\VerrouListeProvisoire::MESSAGE);
            }
            $places = app(\App\Http\Services\Eligibilite\RecevabiliteDemandes::class)->placesLibres($campagne, $volet);
            if ($places === null) {
                $erreur('Renseignez d’abord le nombre de places dans les critères de la campagne.');
            }
            $demandes = $this->demandesADecider($listes['provisoires'][$volet]);
            if ($demandes === []) {
                $erreur('Aucune demande à valider dans cette liste.');
            }
            $workflow = app(DossierWorkflow::class);
            [$validees, $attente, $echecs] = [0, [], []];
            foreach ($demandes as $id) {
                if ($validees >= $places) {
                    $attente[] = $id;

                    continue;
                }
                try {
                    $workflow->admin($id, 'eligible', null, [], ['provisoire_'.$volet, 'valider']);
                    $validees++;
                } catch (ValidationException $exception) {
                    $echecs[] = (string) collect($exception->errors())->flatten()->first();
                }
            }
            foreach (array_chunk($attente, 500) as $lot) {
                DB::table('demande')->whereIn('id_demande', $lot)
                    ->whereIn('etat_dossier', \App\Http\Services\Eligibilite\RecevabiliteDemandes::ETATS_ATTENTE)
                    ->update(['date_attente_commission' => now(), 'attente_commission_par' => (string) $request->user('admin')?->getKey(), 'updated_at' => now()]);
            }
        } finally {
            $verrou->release();
        }
        if ($validees === 0 && $attente === []) {
            $erreur('Aucune demande n’a pu être validée : '.$echecs[0]);
        }
        $message = $validees.' demande(s) validée(s), '.count($attente).' demande(s) en file d’attente.';
        if ($echecs) {
            $message .= ' '.count($echecs).' demande(s) restée(s) dans la liste provisoire : '.$echecs[0];
        }
        $choix = $request->query('campagne') ? ['campagne' => (string) $campagne->getKey()] : [];
        $commission = app(AdmissionPermissions::class)->url($request->user('admin'), 'validees', ['type' => $volet] + $choix);

        return ($commission ? redirect()->to($commission) : back())->with('success', $message);
    }

    public function mettreEnAttente(Request $request, string $demande)
    {
        $recevabilite = app(\App\Http\Services\Eligibilite\RecevabiliteDemandes::class);
        if (! Schema::hasColumn('demande', 'date_attente_commission')) {
            throw ValidationException::withMessages(['attente' => 'La mise en attente n’est pas encore disponible : la base de données doit être mise à jour.']);
        }
        $dossier = DemandeEtudiant::findOrFail($demande);
        $etudiant = Etudiant::with(['derniereDemande.campagne', 'derniereDemande.niveauEtudiantPrecedent', 'derniereDemande.niveauEtudiantActuel'])
            ->findOrFail($dossier->etudiant_id);
        if ((string) $etudiant->derniereDemande?->getKey() !== (string) $dossier->getKey()) {
            throw ValidationException::withMessages(['attente' => 'Cette demande n’est plus la demande en cours de l’étudiant.']);
        }
        $classe = $recevabilite->classe($etudiant);
        if ($classe === $recevabilite::EN_ATTENTE) {
            $this->autoriserListe($request, 'commission_attente', 'view');
            throw ValidationException::withMessages(['attente' => 'Cette demande est déjà en attente dans la décision de la commission.']);
        }
        if (!in_array($classe, [$recevabilite::VALIDEE, $recevabilite::RECEVABLE], true)) {
            throw ValidationException::withMessages(['attente' => 'Seule une demande recevable d’une liste provisoire ou une demande validée non approuvée peut être mise en attente.']);
        }
        $this->autoriserListe($request, $this->scopeDossier($etudiant, $classe), 'attente');
        if ($classe === $recevabilite::VALIDEE) {
            // Demande validée : retour en « Soumis » (le workflow vérifie la transition et la campagne active).
            app(DossierWorkflow::class)->admin($dossier->getKey(), 'soumis', 'Mise en attente par la commission.', [], ['commission_validees', 'attente']);
        } elseif (app(\App\Http\Services\Eligibilite\VerrouListeProvisoire::class)->bloque($dossier)) {
            // Liste provisoire clôturée (liste des demandes validées approuvée en commission) : plus de mise en attente.
            throw ValidationException::withMessages(['attente' => 'La liste des demandes validées a déjà été approuvée en commission : cette demande ne peut plus être mise en attente.']);
        } elseif (! in_array($dossier->etat_dossier, $recevabilite::ETATS_ATTENTE, true)) {
            throw ValidationException::withMessages(['attente' => 'Ce dossier ne peut plus être mis en attente (état : '
                .(DossierWorkflow::LABELS[$dossier->etat_dossier] ?? $dossier->etat_dossier).'). Actualisez la liste.']);
        }
        // Mise en attente postérieure à la dernière transition du dossier (sinon elle serait ignorée).
        \Illuminate\Support\Facades\DB::table('demande')->where('id_demande', $dossier->getKey())->update([
            'date_attente_commission' => now(),
            'attente_commission_par' => (string) $request->user('admin')?->getKey(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Demande mise en attente : elle figure dans la décision de la commission.');
    }

    /**
     * Rang des demandes irrecevables dans leur volet (admissions puis réadmissions), selon `ClassementProvisoire` : les
     * demandes conformes écartées par la liste élargie d'abord, puis celles dont un critère n'est pas conforme.
     *
     * @return array<string, ?int> rang par étudiant, dans l'ordre d'affichage
     */
    private function rangsIrrecevables(EloquentCollection $population, array $classement): array
    {
        $irrecevables = $population->whereIn('id_etudiant', $classement['rejetees']);
        $volets = app(\App\Http\Services\Eligibilite\CriteresCampagne::class)->voletsDemandes($irrecevables->map(fn (Etudiant $etudiant) => $etudiant->derniereDemande));
        $rangs = [];
        foreach ($irrecevables->filter(fn (Etudiant $etudiant) => $etudiant->derniereDemande?->campagne)->groupBy(fn (Etudiant $etudiant) => (string) $etudiant->derniereDemande->campagne_id) as $candidats) {
            $listes = app(\App\Http\Services\Eligibilite\ClassementProvisoire::class)->classer($candidats, $candidats->first()->derniereDemande->campagne, $volets);
            foreach ($listes as $lignes) {
                $ids = collect($lignes)->map(fn (array $ligne) => (string) $ligne['etudiant']->getKey());
                [$ecartees, $nonConformes] = $ids->partition(fn (string $id) => in_array($id, $classement['hors_liste'], true));
                foreach ($ecartees->concat($nonConformes)->values() as $index => $id) {
                    $rangs[$id] = $index + 1;
                }
            }
        }

        // Demandes dont la campagne n'existe plus : à la fin, sans classement.
        return $rangs + array_fill_keys($classement['rejetees'], null);
    }

    /** Campagne au statut « active » la plus récente : référence du bandeau et des critères d'éligibilité. */
    private function campagneActive(): ?Campagne
    {
        return Campagne::where('statut', 'active')->orderByDesc('created_at')->first();
    }

    /**
     * Campagne choisie dans le bandeau de la liste des demandes (`?campagne=<id>`) ; à défaut (ou identifiant inconnu),
     * la campagne active, sinon la campagne la plus récente. `null` seulement s'il n'existe aucune campagne.
     */
    private function campagneSelectionnee(Request $request, ?EloquentCollection $campagnes = null): ?Campagne
    {
        $id = (string) $request->query('campagne', '');
        if ($campagnes !== null) {
            return ($id !== '' ? $campagnes->first(fn (Campagne $campagne) => (string) $campagne->getKey() === $id) : null)
                ?? $campagnes->first(fn (Campagne $campagne) => $campagne->statut === 'active')
                ?? $campagnes->first();
        }

        return ($id !== '' ? Campagne::find($id) : null)
            ?? $this->campagneActive()
            ?? Campagne::query()->orderByDesc('created_at')->first();
    }

    /** Étudiants dont la demande en cours (dernière demande) a été déposée pour la campagne ; sans campagne, tous. */
    private function populationCampagne(EloquentCollection $population, ?Campagne $campagne): EloquentCollection
    {
        if (! $campagne) {
            return $population;
        }

        return $population->filter(fn (Etudiant $etudiant) => (string) $etudiant->derniereDemande?->campagne_id === (string) $campagne->getKey())->values();
    }

    /**
     * Lève les refus automatiques (barème de scoring) des dossiers sélectionnés (`demandes[]`) avec un motif
     * de dérogation obligatoire ; seuls ceux réellement refusés par le barème sont traités.
     */
    public function leverRefusAutomatiques(Request $request): RedirectResponse
    {
        $data = $request->validate(
            ['demandes' => 'required|array|min:1|max:500', 'demandes.*' => 'string|max:150', 'motif' => 'required|string|max:2000'],
            ['motif.required' => 'Le motif de la dérogation est obligatoire.', 'motif.max' => 'Le motif de la dérogation ne doit pas dépasser 2 000 caractères.'],
        );
        $scope = $request->query('scope', 'dossier');
        abort_unless(is_string($scope), 403);
        $this->autoriserListe($request, $scope, 'lever_refus');
        $ids = $data['demandes'];
        $filtre = match ($scope) {
            'recevables_demandes' => 'recevables',
            'rejetees', 'refusees' => $scope,
            default => null,
        };
        if ($filtre) {
            $population = app(\App\Repositories\EtudiantRepository::class)->getPopulationDemandes();
            // Classement sur toutes les demandes (coupure de la liste élargie), puis seulement celles sélectionnées.
            $classement = app(\App\Http\Services\Eligibilite\RecevabiliteDemandes::class)->classer($population);
            $population = $population->filter(fn (Etudiant $student) => in_array((string) $student->derniereDemande?->getKey(), $ids, true));
            $autorisations = $population->whereIn('id_etudiant', $classement[$filtre])
                ->map(fn (Etudiant $student) => (string) $student->derniereDemande->getKey())->all();
            abort_if(array_diff($ids, $autorisations) !== [], 403);
        }
        $resultat = app(DecisionAutomatiqueDossier::class)->rendreEligibles($data['motif'], $ids, $scope);
        if ($resultat['eligibles'] === 0 && !$resultat['echecs']) {
            return back()->with('error', 'Aucun dossier refusé automatiquement à rendre éligible.');
        }
        $message = $resultat['eligibles'].' dossier(s) rendu(s) éligible(s).';
        if ($resultat['echecs']) {
            $message .= ' '.count($resultat['echecs']).' dossier(s) non traité(s) : '.collect($resultat['echecs'])->unique()->first();
        }

        return back()->with($resultat['eligibles'] > 0 ? 'success' : 'error', $message);
    }

    /** Filtres de la liste des campagnes (statut en base, `tous` = aucun filtre). */
    private const FILTRES_CAMPAGNE = ['tous', 'active', 'planifier', 'terminer'];

    private const NIVEAUX = ['L1', 'L2', 'L3', 'M1', 'M2', 'D1', 'D2', 'D3'];

    /**
     * Liste des campagnes (Inertia) : indicateurs par statut, tableau paginé et détail en panneau latéral.
     * La création se fait sur une page dédiée (`createCampagne`).
     */
    public function campagnes(Request $request, AdminAccess $access, ?string $etat = null)
    {
        $permissions = app(AdmissionPermissions::class);
        $admin = $request->user('admin');
        if (!$permissions->allows($admin, 'campagnes')) {
            $destination = $permissions->demandesUrl($admin) ?? $permissions->url($admin, 'validees') ?? $permissions->url($admin, 'definitives');
            abort_unless($destination, 403);

            return redirect($destination);
        }
        $etat = $etat ?? $request->get('etat');
        $etat = in_array($etat, self::FILTRES_CAMPAGNE, true) ? $etat : 'tous';
        $result = $this->campagneService->getListeCampagnesWithStats($etat === 'tous' ? null : $etat);

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        // En cas d'erreur, le service renvoie une liste vide (et le dépôt une chaîne) : la page reste affichable.
        $campagnes = $result['data']['campagnes'] instanceof EloquentCollection ? $result['data']['campagnes'] : new EloquentCollection();
        // Recherche (libellé, année, description) et filtre par année universitaire, appliqués avant la pagination.
        // Le statut est filtré par le segment `{etat}` de l'URL (indicateurs et liste « Statut »).
        $annees = Campagne::query()->whereNotNull('annee_campagne')->where('annee_campagne', '!=', '')
            ->distinct()->orderByDesc('annee_campagne')->pluck('annee_campagne')->map(fn ($annee) => (string) $annee)->values();
        $recherche = trim((string) $request->query('q', ''));
        $annee = (string) $request->query('annee', '');
        $annee = $annees->contains($annee) ? $annee : '';
        $campagnes = $this->rechercherCampagnes($campagnes, $recherche, $annee);
        $campagnes->loadMissing('residences.cites.batiment.chambresDisponibles');
        $page = LengthAwarePaginator::resolveCurrentPage();
        $admin = $request->user('admin');
        $url = fn (string $route) => $access->allows($admin, $route) ? route($route) : null;
        $pageCampagnes = $campagnes->forPage($page, 10);
        // Critères de la campagne : étape qui suit la création d'une campagne (un seul bouton vers la page du paramétrage).
        $criteres = app(\App\Http\Services\Eligibilite\CriteresCampagne::class);
        $vueCriteres = app(\App\Http\Services\Eligibilite\CriteresCampagneVue::class);
        $etatsCriteres = $criteres->etats($pageCampagnes->map(fn (Campagne $campagne) => (string) $campagne->id_campagne)->values()->all());
        $lien = fn (string $route, Campagne $campagne) => $access->allows($admin, $route) ? route($route, $campagne->id_campagne) : null;
        $voirCriteres = $vueCriteres->visible($admin);

        return Inertia::render('Admin/Hebergement/Campagnes/Index', [
            'campagnes' => (new LengthAwarePaginator(
                $pageCampagnes->map(function (Campagne $campagne) use ($lien, $admin, $criteres, $vueCriteres, $voirCriteres, $etatsCriteres) {
                    // Un état et un lien par volet ouvert par la campagne (null pour un volet fermé).
                    $volets = $criteres->volets($campagne);
                    $parVolet = fn (callable $valeur) => collect($volets)->map(fn (bool $ouvert, string $volet) => $ouvert ? $valeur($volet) : null)->all();

                    return $this->campagneListe($campagne) + [
                        'criteres_etat' => $parVolet(fn (string $volet) => $etatsCriteres[(string) $campagne->id_campagne][$volet]),
                        'urls' => [
                            'edit' => $lien('admin.hebergement.campagnes_admission.edit_campagne', $campagne),
                            'destroy' => $lien('admin.hebergement.campagnes_admission.destroy_campagne', $campagne),
                            'criteres' => $voirCriteres ? $vueCriteres->lienPage($admin, $campagne) : null,
                        ],
                    ];
                })->values(),
                $campagnes->count(),
                10,
                $page,
                ['path' => $request->url(), 'query' => $request->query()],
            )),
            'stats' => $result['data']['stats'],
            'etat' => $etat,
            'filters' => ['q' => $recherche, 'annee' => $annee],
            'annees' => $annees,
            'urls' => [
                'hebergement' => route('admin.hebergement.index'),
                'admissions' => $permissions->demandesUrl($admin),
                'validees' => $permissions->url($admin, 'validees'),
                'definitives' => $permissions->url($admin, 'definitives'),
                'create' => $url('admin.hebergement.campagnes_admission.create_campagne'),
                'filtres' => collect(self::FILTRES_CAMPAGNE)
                    // Changer de statut conserve la recherche et l'année universitaire en cours.
                    ->mapWithKeys(fn ($filtre) => [$filtre => route('admin.hebergement.campagnes_admission.campagnes', array_filter([
                        'etat' => $filtre,
                        'q' => $recherche,
                        'annee' => $annee,
                    ], fn ($valeur) => $valeur !== ''))]),
            ],
        ]);
    }

    /**
     * Recherche insensible à la casse dans le libellé, l'année et la description,
     * puis filtre sur l'année universitaire (`annee_campagne`, valeur exacte). L'ordre du dépôt est conservé.
     */
    private function rechercherCampagnes(EloquentCollection $campagnes, string $recherche, string $annee): EloquentCollection
    {
        if ($annee !== '') {
            $campagnes = $campagnes->filter(fn (Campagne $campagne) => (string) $campagne->annee_campagne === $annee);
        }

        if ($recherche !== '') {
            $terme = mb_strtolower($recherche);
            $campagnes = $campagnes->filter(fn (Campagne $campagne) => collect([$campagne->libelle, $campagne->annee_campagne, $campagne->description])
                ->contains(fn ($valeur) => $valeur !== null && str_contains(mb_strtolower((string) $valeur), $terme)));
        }

        return $campagnes->values();
    }

    /** Formulaire de création d'une campagne (Inertia, en plusieurs étapes). */
    public function createCampagne(Request $request, AdminAccess $access)
    {
        $admin = $request->user('admin');

        return Inertia::render('Admin/Hebergement/Campagnes/Create', $this->optionsFormulaire() + [
            'urls' => [
                'hebergement' => route('admin.hebergement.index'),
                'index' => route('admin.hebergement.campagnes_admission.campagnes'),
                'submit' => $access->allows($admin, 'admin.hebergement.campagnes_admission.post_campagnes')
                    ? route('admin.hebergement.campagnes_admission.post_campagnes') : null,
            ],
        ]);
    }

    /** Formulaire de modification d'une campagne : même assistant que la création, prérempli. */
    public function editCampagne(Request $request, AdminAccess $access, string $id)
    {
        $campagne = Campagne::with(['residences', 'documentCampagnes'])->findOrFail($id);
        $admin = $request->user('admin');
        $citeIds = $campagne->residences->pluck('cite_id')->map(fn ($cite) => (string) $cite)->unique()->values()->all();
        $documentIds = $campagne->documentCampagnes->pluck('document_id')->map(fn ($document) => (string) $document)->unique()->values()->all();
        $date = fn ($valeur) => $valeur ? Carbon::parse($valeur)->toDateString() : '';

        return Inertia::render('Admin/Hebergement/Campagnes/Edit', $this->optionsFormulaire($citeIds, $documentIds) + [
            'campagne' => [
                'id' => (string) $campagne->id_campagne,
                'annee_campagne' => (string) $campagne->annee_campagne,
                'type' => CampagneService::typeDe($campagne),
                'libelle' => (string) $campagne->libelle,
                'description' => (string) $campagne->description,
                'periode_debut' => $date($campagne->periode_debut),
                'periode_fin' => $date($campagne->periode_fin),
                'residences' => $citeIds,
                'documents' => $documentIds,
            ],
            'dossiers' => $this->campagneService->nombreDossiers($campagne->id_campagne),
            'urls' => [
                'hebergement' => route('admin.hebergement.index'),
                'index' => route('admin.hebergement.campagnes_admission.campagnes'),
                'submit' => $access->allows($admin, 'admin.hebergement.campagnes_admission.update_campagne')
                    ? route('admin.hebergement.campagnes_admission.update_campagne', $campagne->id_campagne) : null,
            ],
        ]);
    }

    /**
     * Résidences (cités ouvertes) et documents (actifs) proposés par le formulaire.
     * En modification, les cités et documents déjà liés restent proposés même s'ils ont été fermés ou désactivés,
     * pour ne pas les retirer de la campagne à l'insu de l'utilisateur.
     */
    private function optionsFormulaire(array $citeIds = [], array $documentIds = []): array
    {
        $documents = Document::getAllDocuments(1);
        if ($documentIds) {
            $documents = $documents->merge(Document::whereIn('id_document', $documentIds)->get())->unique('id_document');
        }

        return [
            'residences' => Cite::query()
                ->where(fn ($query) => $query->where('statut', 'ouvert')->orWhereIn('id_cite', $citeIds))
                ->orderBy('name')
                ->with('batiment.chambresDisponibles')->get()
                ->map(fn (Cite $cite) => [
                    'id' => (string) $cite->id_cite,
                    'name' => $cite->name,
                    'chambres' => (int) $cite->batiment->sum(fn ($batiment) => $batiment->chambresDisponibles->count()),
                ])->values(),
            'documents' => $documents
                ->map(fn ($document) => ['id' => (string) $document->id_document, 'name' => $document->name])->values(),
        ];
    }

    /** Enregistre les modifications d'une campagne. */
    public function updateCampagne(CampagneRequest $request, string $id): RedirectResponse
    {
        $this->campagneService->updateCampagne($id, $request->validated());

        return redirect()
            ->route('admin.hebergement.campagnes_admission.campagnes')
            ->with('success', 'Campagne modifiée avec succès');
    }

    /** Supprime une campagne sans dossier rattaché (suppression logique). */
    public function destroyCampagne(string $id): RedirectResponse
    {
        try {
            $this->campagneService->deleteCampagne($id);
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()
            ->route('admin.hebergement.campagnes_admission.campagnes')
            ->with('success', 'Campagne supprimée avec succès');
    }

    /** Ligne de la liste des campagnes, avec tout ce qu'affiche le panneau de détail. */
    private function campagneListe(Campagne $campagne): array
    {
        $date = fn ($valeur) => $valeur ? Carbon::parse($valeur)->toDateString() : null;
        $residences = $campagne->residences->flatMap->cites->map(fn (Cite $cite) => [
            'name' => $cite->name,
            'places' => (int) $cite->batiment->sum(fn ($batiment) => $batiment->chambresDisponibles->sum('capacite_max')),
        ])->values();

        return [
            'id' => (string) $campagne->id_campagne,
            'annee' => $campagne->annee_campagne,
            'libelle' => $campagne->libelle,
            'description' => $campagne->description,
            'statut' => $campagne->statut,
            'periode_debut' => $date($campagne->periode_debut),
            'periode_fin' => $date($campagne->periode_fin),
            // null : chaque dossier est décidé par le barème dès sa réception.
            'date_validation_auto' => $date($campagne->date_validation_auto),
            'criteres' => [
                'moyenne_min' => $campagne->moyenne_min,
                'pourcentage_validate' => $campagne->pourcentage_validate !== null ? (int) $campagne->pourcentage_validate : null,
                'distance_min' => $campagne->distance_min,
                'ages' => collect(self::NIVEAUX)
                    ->mapWithKeys(fn ($niveau) => [$niveau => $campagne->{'age_'.$niveau.'_max'}])
                    ->filter(fn ($age) => $age !== null),
            ],
            'conditions' => collect(['admission', 'readmission', 'internationnaux', 'redoublement', 'pmr', 'is_participate', 'is_discipline'])
                ->mapWithKeys(fn ($champ) => [$champ => (bool) $campagne->{$champ}]),
            'residences' => $residences,
            'documents' => $campagne->documentCampagnes->flatMap->documents->pluck('name')->values(),
        ];
    }

    /** Crée une campagne avec ses résidences et ses documents requis. */
    public function storeCampagne(CampagneRequest $request): RedirectResponse
    {
        try {
            $this->campagneService->createCampagne($request->validated());

            return redirect()
                ->route('admin.hebergement.campagnes_admission.campagnes')
                ->with('success', 'Campagne créée avec succès. Étape suivante : paramétrer ses critères (bouton « Paramétrer les critères » de la ligne).');

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
    /**
     * Correction par l'administration des informations saisies par l'étudiant (fiche + informations académiques de sa dernière demande).
     * L'état du dossier reste géré par `DossierWorkflow`.
     */
    public function updateEtudiant(Request $request, string $etudiant, \App\Http\Services\EtudiantEditionService $edition): RedirectResponse
    {
        $edition->mettreAJour($request, Etudiant::with('derniereDemande')->findOrFail($etudiant));

        return back()->with('success', 'Informations de l’étudiant mises à jour.');
    }

    public function submitTraitement(Request $request, $id)
    {
        // Valider les données
        $validated = $request->validate([
            'decision' => 'required|in:eligible,non_eligible',
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
        $document = $documentService->updateDocument($documentId, $statut, $message);

        return back();
    }
}
