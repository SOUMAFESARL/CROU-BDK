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
     * par l'administration pendant la validation), selon `RecevabiliteDemandes` ; s'y ajoute la « Liste validée »
     * (onglet à part : demandes recevables validées depuis une liste provisoire, dossier « Recevable »).
     * Les étudiants logés et les dossiers en vérification sont suivis sur la page « Résidents & contrats ».
     */
    private const FILTRES_ADMISSION = ['toutes', 'recevables', 'rejetees', 'refusees', 'validees'];

    /** Anciens filtres (liens enregistrés) : nouvelles demandes, en attente, rejetées. */
    private const FILTRES_ADMISSION_ANCIENS = ['nouvelles_demandes' => 'toutes', 'en_attente' => 'recevables', 'annuler' => 'rejetees'];

    public function liste(Request $request, ?string $etat = null)
    {
        try {
            if (!$request->wantsJson()) {
                return $this->pageAdmissions($request, $etat ?? $request->get('etat'));
            }
            $result = $this->etudiantService->getListeEtudiantsWithStats($etat ?? $request->get('etat'));
            $result = app(\App\Services\AdminViewData::class)->filter($result, $request->user('admin'));

            return response()->json($result);
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
    private function pageAdmissions(Request $request, ?string $filtre)
    {
        $admin = $request->user('admin');
        $access = app(AdminAccess::class);
        $vues = app(AdminViewAccess::class);
        $personnel = $vues->allows($admin, 'admissions.personal');
        $historique = $vues->allows($admin, 'admissions.history');
        $filtre = self::FILTRES_ADMISSION_ANCIENS[(string) $filtre] ?? $filtre;
        $etat = in_array($filtre, self::FILTRES_ADMISSION, true) ? $filtre : 'toutes';
        $recherche = trim((string) $request->query('q', ''));
        $etatDossier = (string) $request->query('etat_dossier', '');
        $etatDossier = array_key_exists($etatDossier, DossierWorkflow::LABELS) ? $etatDossier : '';
        // Type de demande (volet de l'étudiant dans la campagne de sa demande) : admission ou réadmission.
        $typeDemande = (string) $request->query('type', '');
        $typeDemande = array_key_exists($typeDemande, \App\Http\Services\Eligibilite\CriteresCampagne::VOLETS) ? $typeDemande : '';
        $url = fn (string $route, array $parametres = []) => $access->allows($admin, $route) ? route($route, $parametres) : null;
        $repository = app(\App\Repositories\EtudiantRepository::class);
        // Bandeau : campagne au statut « active » (comme la page Campagnes), pas celle de l'année universitaire.
        $campagne = $this->campagneActive();
        // Conformité de la dernière demande de chaque étudiant aux critères de sa campagne : compteurs et listes filtrées.
        $population = $repository->getPopulationDemandes();
        $classement = app(\App\Http\Services\Eligibilite\RecevabiliteDemandes::class)->classer($population);
        $criteres = app(\App\Http\Services\Eligibilite\CriteresCampagne::class);
        $etudiants = match ($etat) {
            'recevables' => $classement['recevables'],
            'rejetees' => $classement['rejetees'],
            'refusees' => $classement['refusees'],
            'validees' => $classement['validees'],
            default => null,
        };
        if ($typeDemande !== '') {
            // Le type n'est pas stocké : il se calcule sur la population des demandes, puis restreint la liste.
            $volets = $criteres->voletsDemandes($population->map(fn (Etudiant $etudiant) => $etudiant->derniereDemande));
            $duType = array_keys(array_filter($volets, fn (string $volet) => $volet === $typeDemande));
            $etudiants = $etudiants === null ? $duType : array_values(array_intersect($etudiants, $duType));
        }
        $dossiers = $repository->getAdmissionsPage('demandes', 10, $recherche, $etatDossier, $etudiants);
        // Levée des refus automatiques (barème) : par dossier, ou pour les dossiers cochés dans la liste.
        $decision = app(DecisionAutomatiqueDossier::class);
        $peutLever = $access->allows($admin, 'admin.hebergement.campagnes_admission.lever_refus_automatiques');
        $refusAutomatiques = $peutLever
            ? $decision->refusAutomatiques(collect($dossiers->items())->map(fn (Etudiant $etudiant) => $etudiant->derniereDemande?->getKey())->all())
            : [];
        // Type de demande (admission / réadmission) : volet de l'étudiant dans la campagne de sa demande.
        $dossiers->getCollection()->load('derniereDemande.campagne');
        $types = $criteres->voletsDemandes(collect($dossiers->items())->map(fn (Etudiant $etudiant) => $etudiant->derniereDemande));
        // Demandes recevables : listes provisoires ; liste validée : demandes validées depuis ces listes (mêmes rangs).
        $listesProvisoires = in_array($etat, ['recevables', 'validees'], true)
            ? $this->classementProvisoire($request, $etat === 'validees', $population, $classement, $campagne, $recherche, $personnel, $vues->allows($admin, 'admissions.documents'), $url, $access, $admin)
            : null;

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
                'refus_automatique' => in_array((string) $etudiant->derniereDemande?->getKey(), $refusAutomatiques, true),
            ]),
            'stats' => [
                'toutes' => $population->count(),
                'recevables' => count($classement['recevables']),
                'rejetees' => count($classement['rejetees']),
                'refusees' => count($classement['refusees']),
                'validees' => count($classement['validees']),
            ],
            'etat' => $etat,
            'classement' => $listesProvisoires,
            'filters' => ['q' => $recherche, 'etat_dossier' => $etatDossier, 'type' => $typeDemande],
            'etatsDossier' => collect(DossierWorkflow::LABELS)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
            'colonnes' => ['personnel' => $personnel, 'historique' => $historique],
            'campagneActive' => $campagne ? [
                'annee' => $campagne->annee_campagne,
                'echeance' => $campagne->date_echeance_demande ? Carbon::parse($campagne->date_echeance_demande)->toDateString() : null,
                'moyenne_min' => $campagne->moyenne_min,
                'distance_min' => $campagne->distance_min,
            ] : null,
            'urls' => [
                'campagnes' => $url('admin.hebergement.campagnes_admission.campagnes'),
                'leverRefus' => $peutLever ? route('admin.hebergement.campagnes_admission.lever_refus_automatiques') : null,
                'filtres' => $access->allows($admin, 'admin.hebergement.campagnes_admission.admission')
                    ? collect(self::FILTRES_ADMISSION)->mapWithKeys(fn ($f) => [$f => route('admin.hebergement.campagnes_admission.admission', ['etat' => $f])])
                    : null,
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
        $voirAdmissions = $admin->hasPermission('hebergement.admissions.view');
        $derogations = $voirAdmissions ? \App\Models\AdmissionDerogation::where('etudiant_id', $id)->latest()->get() : collect();
        $peutApprouver = app(\App\Http\Services\AdmissionDerogationService::class)->canApprove($admin);
        // Dérogation approuvée sur la demande courante : la demande irrecevable est rendue recevable.
        $derogationApprouvee = $derniere ? \App\Models\AdmissionDerogation::where('demande_id', $derniere->getKey())
            ->where('status', 'approved')->latest('decided_at')->first() : null;
        $personnel = $vues->allows($admin, 'admissions.personal');
        $documents = $vues->allows($admin, 'admissions.documents');
        $historique = $vues->allows($admin, 'admissions.history');

        $statutDisciplinaire = $vues->allows($admin, 'housing.discipline') ? $etudiant->statut_disciplinaire : 'aucune';
        // Modification des informations de l'étudiant : valeurs et référentiels envoyés seulement avec le droit de modifier.
        $modifiable = $access->allows($admin, 'admin.hebergement.campagnes_admission.update_etudiant');
        $edition = app(\App\Http\Services\EtudiantEditionService::class);

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
                    'derogation' => $derogationApprouvee ? [
                        'auteur' => $derogationApprouvee->decider_name,
                        'date' => $derogationApprouvee->decided_at?->format('Y-m-d H:i'),
                    ] : null,
                ]
                : null,
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
            'derogations' => $voirAdmissions ? [
                'liste' => $derogations->map(fn ($derogation) => [
                    'id' => (string) $derogation->id,
                    'statut' => $derogation->status,
                    'demandeur' => $derogation->requester_name,
                    'date' => $derogation->created_at?->format('Y-m-d H:i'),
                    'motif' => $derogation->reason,
                    // Critères non conformes au moment de la demande (`etat` : constat de recevabilité ; `ok` : anciennes dérogations).
                    'non_respectes' => collect($derogation->eligibility['regles'] ?? [])
                        ->filter(fn ($regle) => array_key_exists('etat', $regle) ? $regle['etat'] === 'nok' : !($regle['ok'] ?? false))
                        ->map(fn ($regle) => $regle['label'] ?? 'Critère non respecté')->values(),
                    'decision' => $derogation->decided_at ? [
                        'auteur' => $derogation->decider_name,
                        'date' => $derogation->decided_at->format('Y-m-d H:i'),
                        'motif' => $derogation->decision_reason,
                    ] : null,
                    // Le demandeur ne peut pas valider sa propre dérogation.
                    'decider' => !$derogation->decided_at && $peutApprouver && (string) $derogation->requested_by !== (string) $admin->getKey()
                        ? $url('admin.hebergement.campagnes_admission.derogations.decide', ['derogation' => $derogation->id]) : null,
                ])->values(),
                // Seule une demande irrecevable (au moins un critère non conforme, sans dérogation approuvée) peut être rendue recevable.
                'demander' => in_array($derniere?->etat_dossier, ['soumis', 'en_etude'], true)
                    && !$derogations->where('demande_id', $derniere->getKey())->contains('status', 'pending')
                    && app(\App\Http\Services\Eligibilite\RecevabiliteDemandes::class)->classe($etudiant) === \App\Http\Services\Eligibilite\RecevabiliteDemandes::REJETEE
                    ? $url('admin.hebergement.campagnes_admission.derogations.store', ['student' => $etudiant->getKey()]) : null,
            ] : null,
            'suivi' => $voirAdmissions && $derniere ? $this->suiviDossier($derniere, $admin) : null,
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
            'edition' => $modifiable ? $edition->valeurs(Etudiant::with('derniereDemande')->findOrFail($etudiant->getKey())) : null,
            'options' => $modifiable ? $edition->options() : null,
            'urls' => [
                'admissions' => $url('admin.hebergement.campagnes_admission.admission'),
                'contrat' => $url('admin.etudiant.contrat', ['etudiant' => $etudiant->id_etudiant]),
                'modifier' => $url('admin.hebergement.campagnes_admission.update_etudiant', ['etudiant' => $etudiant->id_etudiant]),
            ],
        ]);
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
     * `$validee` : « Liste validée » — mêmes onglets par volet (sans la liste des demandes), limités aux demandes validées.
     * Le rang est calculé sur les demandes recevables et validées ensemble : valider une demande ne renumérote pas les autres.
     * Sur une liste provisoire, chaque ligne porte les actions « Valider » (dossier « Recevable ») et « Rejeter » (« Refusé »,
     * motif obligatoire) permises par `DossierWorkflow`, avec le droit de changer l'état du dossier ; sur la liste validée,
     * seul le rejet est proposé (la demande passe alors dans « Demandes refusées »).
     *
     * @param  array{recevables: string[], validees: string[]}  $classement  `RecevabiliteDemandes::classer()`
     */
    private function classementProvisoire(Request $request, bool $validee, EloquentCollection $population, array $classement, ?Campagne $campagne, string $recherche, bool $personnel, bool $documents, \Closure $url, AdminAccess $access, $admin): array
    {
        [$campagne, $volets, $listes] = $this->listesProvisoires($population, $classement, $campagne);
        $listes = $listes[$validee ? 'validees' : 'provisoires'];
        // Onglets selon le type de campagne : une liste provisoire par volet ouvert (admission seule → pas de réadmission).
        $onglets = $validee ? $volets : ['demandes', ...$volets];
        $liste = in_array($request->query('liste'), $onglets, true) ? $request->query('liste') : ($onglets[0] ?? 'demandes');
        $lien = fn (string $onglet) => $access->allows($admin, 'admin.hebergement.campagnes_admission.admission')
            ? route('admin.hebergement.campagnes_admission.admission', ['etat' => $validee ? 'validees' : 'recevables'] + ($onglet === 'demandes' ? [] : ['liste' => $onglet]))
            : null;
        // Téléchargement de la liste provisoire ouverte (Excel, PDF imprimable).
        $exports = ! $validee && $liste !== 'demandes' && $access->allows($admin, 'admin.hebergement.campagnes_admission.liste_provisoire_export')
            ? collect(['excel', 'pdf'])->mapWithKeys(fn ($format) => [$format => route('admin.hebergement.campagnes_admission.liste_provisoire_export', ['volet' => $liste, 'format' => $format])])->all()
            : null;
        // Validation / rejet depuis la liste provisoire, rejet seul depuis la liste validée (même action que le suivi du dossier).
        $peutDecider = $access->allows($admin, 'admin.dossiers.transition');
        $proposees = $validee ? ['refuse'] : ['eligible', 'refuse'];

        $lignes = null;
        if ($liste !== 'demandes') {
            $motif = Str::lower(Str::ascii($recherche));
            $filtrees = collect($listes[$liste])->filter(fn (array $ligne) => $motif === '' || Str::contains(
                Str::lower(Str::ascii(implode(' ', [$ligne['etudiant']->name, $ligne['etudiant']->matricule, $ligne['etudiant']->derniereDemande?->numero_demande]))),
                $motif,
            ))->values();
            $page = max(1, (int) $request->query('page', 1));
            $courantes = $filtrees->forPage($page, 10)->values();
            // État des pièces du dossier (envoyé seulement avec le droit de voir les documents).
            $pieces = $documents ? $this->etatPieces($courantes->map(fn (array $ligne) => $ligne['etudiant']), $campagne) : [];
            $lignes = (new LengthAwarePaginator($courantes, $filtrees->count(), 10, $page, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]))->through(function (array $ligne) use ($personnel, $url, $pieces, $peutDecider, $proposees) {
                $etudiant = $ligne['etudiant'];
                $demande = $etudiant->derniereDemande;
                // Un refus ne se lève pas depuis la liste : il passe par le réexamen sur la fiche du dossier.
                $decisions = $peutDecider && $demande && $demande->etat_dossier !== 'refuse'
                    ? array_values(array_intersect($proposees, DossierWorkflow::NEXT[$demande->etat_dossier] ?? []))
                    : [];

                return [
                    'id' => (string) $etudiant->id_etudiant,
                    'rang' => $ligne['rang'],
                    'name' => $etudiant->name,
                    'numero_demande' => $demande?->numero_demande,
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
                    'decision' => $decisions ? [
                        'url' => route('admin.dossiers.transition', $demande),
                        'valider' => in_array('eligible', $decisions, true),
                        'rejeter' => in_array('refuse', $decisions, true),
                    ] : null,
                ];
            });
        }

        return [
            'liste' => $liste,
            'validee' => $validee,
            'onglets' => $onglets,
            'campagne' => $campagne?->annee_campagne,
            'compteurs' => [
                'demandes' => count($classement['recevables']),
                'admission' => count($listes['admission']),
                'readmission' => count($listes['readmission']),
            ],
            'lignes' => $lignes,
            'colonnes' => ['age' => $personnel],
            'urls' => collect($onglets)->mapWithKeys(fn ($onglet) => [$onglet => $lien($onglet)]),
            'exports' => $exports,
        ];
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
     * Campagne des listes provisoires (campagne active, sinon celle de la demande recevable ou validée la plus récente),
     * volets qu'elle ouvre et demandes classées par volet (`ClassementProvisoire`) : le classement porte sur les demandes
     * recevables et validées ensemble (rangs stables), puis il est partagé entre les listes provisoires (demandes encore
     * à valider) et la liste validée.
     *
     * @param  array{recevables: string[], validees: string[]}  $classement  `RecevabiliteDemandes::classer()`
     * @return array{0: ?Campagne, 1: list<string>, 2: array{provisoires: array{admission: array, readmission: array}, validees: array{admission: array, readmission: array}}}
     */
    private function listesProvisoires(EloquentCollection $population, array $classement, ?Campagne $campagne): array
    {
        $classees = $population->whereIn('id_etudiant', [...$classement['recevables'], ...$classement['validees']]);
        $campagne ??= $classees->map(fn (Etudiant $etudiant) => $etudiant->derniereDemande)->filter()
            ->sortByDesc('created_at')->first()?->campagne;
        $criteres = app(\App\Http\Services\Eligibilite\CriteresCampagne::class);
        $vides = ['admission' => [], 'readmission' => []];
        if (! $campagne) {
            return [null, [], ['provisoires' => $vides, 'validees' => $vides]];
        }
        $listes = app(\App\Http\Services\Eligibilite\ClassementProvisoire::class)->classer(
            $classees,
            $campagne,
            $criteres->voletsDemandes($classees->map(fn (Etudiant $etudiant) => $etudiant->derniereDemande)),
        );
        $partage = ['provisoires' => $vides, 'validees' => $vides];
        foreach ($listes as $volet => $lignes) {
            [$validees, $provisoires] = collect($lignes)->partition(fn (array $ligne) => in_array((string) $ligne['etudiant']->getKey(), $classement['validees'], true));
            $partage['provisoires'][$volet] = $provisoires->values()->all();
            $partage['validees'][$volet] = $validees->values()->all();
        }

        return [$campagne, array_keys(array_filter($criteres->volets($campagne))), $partage];
    }

    /**
     * Téléchargement d'une liste provisoire complète (tous les rangs, sans la recherche de l'écran) : classeur Excel
     * ou PDF à imprimer (ouvert dans un nouvel onglet). L'âge n'y figure qu'avec le droit de voir les données personnelles.
     */
    public function exportListeProvisoire(Request $request, string $volet, string $format)
    {
        $admin = $request->user('admin');
        $personnel = app(AdminViewAccess::class)->allows($admin, 'admissions.personal');
        $population = app(\App\Repositories\EtudiantRepository::class)->getPopulationDemandes();
        $classement = app(\App\Http\Services\Eligibilite\RecevabiliteDemandes::class)->classer($population);
        [$campagne, $volets, $listes] = $this->listesProvisoires($population, $classement, $this->campagneActive());
        // Liste provisoire : demandes encore à valider (les demandes validées figurent dans la « Liste validée »).
        $listes = $listes['provisoires'];
        if (! in_array($volet, $volets, true)) {
            return redirect()->route('admin.hebergement.campagnes_admission.admission', ['etat' => 'recevables'])
                ->with('error', 'Cette liste provisoire n’est pas disponible pour la campagne.');
        }

        $readmission = $volet === 'readmission';
        $nombre = fn ($valeur) => $valeur === null ? '' : rtrim(rtrim(number_format((float) $valeur, 2, ',', ' '), '0'), ',');
        $entetes = array_merge(
            ['Rang', 'Nom et prénoms', 'Matricule', 'N° de demande', 'Niveau'],
            $readmission ? ['Catégorie', 'Boursier', 'Moyenne (/20)', 'Crédits validés (%)'] : ['Boursier', 'Note', '% du maximum', 'Ville du bac'],
            $personnel ? ['Âge'] : [],
        );
        $lignes = collect($listes[$volet])->map(function (array $ligne) use ($readmission, $personnel, $nombre) {
            $etudiant = $ligne['etudiant'];
            $demande = $etudiant->derniereDemande;
            $merite = $ligne['merite'] ?? null;

            return array_merge(
                [$ligne['rang'], (string) $etudiant->name, (string) $etudiant->matricule, (string) $demande?->numero_demande, (string) $demande?->niveauEtudiantActuel?->name],
                $readmission
                    ? [
                        $ligne['categorie'] ? $ligne['categorie']['code'].' · '.$ligne['categorie']['libelle'] : '',
                        ($ligne['boursier'] ?? false) ? 'Oui' : 'Non',
                        $nombre($ligne['moyenne'] ?? null),
                        $nombre($ligne['credits'] ?? null),
                    ]
                    : [
                        ($ligne['boursier'] ?? false) ? 'Oui' : 'Non',
                        $merite ? $nombre($merite['valeur']).' / '.$merite['max'] : '',
                        $merite ? $nombre($merite['pourcentage']) : '',
                        (string) ($ligne['ville_bac'] ?? ''),
                    ],
                $personnel ? [$etudiant->date_naissance ? Carbon::parse($etudiant->date_naissance)->age : ''] : [],
            );
        })->all();

        $titre = 'Liste provisoire des '.($readmission ? 'réadmissions' : 'admissions');
        $fichier = 'liste-provisoire-'.($readmission ? 'readmission' : 'admission').'-'.Str::slug((string) $campagne->annee_campagne);
        if ($format === 'excel') {
            return \App\Support\SimpleXlsx::download($fichier.'.xlsx', $readmission ? 'Réadmissions' : 'Admissions', $entetes, $lignes);
        }

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.liste-provisoire', [
            'titre' => $titre,
            'campagne' => $campagne->annee_campagne,
            'entetes' => $entetes,
            'lignes' => $lignes,
        ])->setPaper('a4', 'landscape')->stream($fichier.'.pdf');
    }

    /** Campagne au statut « active » la plus récente : référence du bandeau et des critères d'éligibilité. */
    private function campagneActive(): ?Campagne
    {
        return Campagne::where('statut', 'active')->orderByDesc('created_at')->first();
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
        $resultat = app(DecisionAutomatiqueDossier::class)->rendreEligibles($data['motif'], $data['demandes']);
        if ($resultat['eligibles'] === 0 && !$resultat['echecs']) {
            return back()->with('error', 'Aucun dossier refusé automatiquement à rendre éligible.');
        }
        $message = $resultat['eligibles'].' dossier(s) rendu(s) éligible(s).';
        if ($resultat['echecs']) {
            $message .= ' '.count($resultat['echecs']).' dossier(s) non traité(s) : '.collect($resultat['echecs'])->unique()->first();
        }

        return back()->with($resultat['eligibles'] > 0 ? 'success' : 'error', $message);
    }

    /** Suivi du dossier (équivalent admin du partiel `dossier-cycle`) : parcours, actions permises et historique. */
    private function suiviDossier($dossier, $admin): array
    {
        // La levée d'un refus automatique a son propre bouton (motif de dérogation obligatoire) ; un refus prononcé par un agent passe par « Soumis ».
        $refusAutomatique = $dossier->etat_dossier === 'refuse' && app(DecisionAutomatiqueDossier::class)->estRefusAutomatique($dossier->getKey());
        $actions = array_values(array_intersect(DossierWorkflow::NEXT[$dossier->etat_dossier] ?? [], ['soumis', 'incomplet', 'eligible', 'refuse', 'annule']));
        if ($dossier->etat_dossier === 'refuse') $actions = array_values(array_diff($actions, ['eligible']));
        $peutChanger = app(AdminAccess::class)->allows($admin, 'admin.dossiers.transition');
        $proposition = in_array($dossier->etat_dossier, ['pre_attribue', 'accepte', 'attribue_definitif'], true) ? $dossier->proposition : null;

        return [
            'labels' => DossierWorkflow::LABELS,
            'proposition' => $proposition ? [
                'chambre' => $proposition->chambre?->numero_chambre ?? $proposition->chambre_id,
                'jusqu_au' => $proposition->date_fin ? Carbon::parse($proposition->date_fin)->format('Y-m-d H:i') : null,
            ] : null,
            'actions' => $peutChanger ? array_map(fn ($etat) => ['etat' => $etat, 'label' => $etat === 'soumis' ? 'Réexaminer (retour à « Soumis »)' : (DossierWorkflow::LABELS[$etat] ?? $etat)], $actions) : [],
            'transition' => $peutChanger && $actions ? route('admin.dossiers.transition', $dossier) : null,
            'rendre_eligible' => $peutChanger && $refusAutomatique ? route('admin.dossiers.transition', $dossier) : null,
            'decision' => $this->decisionAutomatique($dossier),
            'refus' => $this->motifRefus($dossier),
            'assignation_id' => $dossier->assignation_id,
            'historique' => $admin->hasPermission('hebergement.admissions.history')
                ? $dossier->transitions->map(fn ($transition) => [
                    'date' => $transition->created_at?->format('Y-m-d H:i'),
                    'etat' => DossierWorkflow::LABELS[$transition->nouvel_etat] ?? $transition->nouvel_etat,
                    'auteur' => $transition->details['auteur_nom'] ?? $transition->acteur_type,
                    'motif' => $transition->motif,
                ])->values()
                : null,
        ];
    }

    /**
     * Motif du refus ou de l'annulation en cours (dernière transition vers l'état actuel), affiché sur la fiche
     * indépendamment de l'historique : rejet depuis une liste provisoire ou la liste validée, refus depuis le suivi…
     */
    private function motifRefus($dossier): ?array
    {
        if (! in_array($dossier->etat_dossier, \App\Http\Services\Eligibilite\RecevabiliteDemandes::ETATS_REFUS, true)) {
            return null;
        }
        $transition = $dossier->transitions->where('nouvel_etat', $dossier->etat_dossier)->sortBy('id')->last();
        if (! $transition || trim((string) $transition->motif) === '') {
            return null;
        }

        return [
            'etat' => DossierWorkflow::LABELS[$dossier->etat_dossier] ?? $dossier->etat_dossier,
            'motif' => $transition->motif,
            'auteur' => $transition->details['auteur_nom'] ?? ($transition->acteur_type === 'systeme' ? 'Traitement automatique' : null),
            'date' => $transition->created_at?->format('Y-m-d H:i'),
        ];
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
                'admissions' => $url('admin.hebergement.campagnes_admission.admission'),
                'validees' => $url('admin.hebergement.campagnes_admission.admission', ['etat' => 'validees']),
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
