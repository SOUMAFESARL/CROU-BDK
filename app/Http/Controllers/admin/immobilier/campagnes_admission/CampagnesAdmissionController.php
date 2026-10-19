<?php

namespace App\Http\Controllers\admin\immobilier\campagnes_admission;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Services\EtudiantService;
use App\Models\Document;
use App\Http\Requests\CampagneRequest;
use App\Http\Services\CampagneService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use \App\Models\Cite;
use \App\Models\DocumentCampagne;
use \App\Models\ResidenceCampagne;
use App\Http\Services\CiteService;
use Illuminate\Support\Str;
use App\Http\Services\DocumentEtudiantService;
use App\Repositories\Interfaces\DocumentEtudiantRepositoryInterface;
use App\Repositories\Interfaces\EtudiantRepositoryInterface;
use App\Http\Services\CampagneValidationService;
use App\Models\Campagne;
use App\Services\AdminAccess;
use App\Services\AdminViewAccess;
use App\Http\Services\DossierWorkflow;
use App\Models\Etudiant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;


class CampagnesAdmissionController extends Controller
{


    protected $campusService;
    protected $citeService;

    public function __construct(EtudiantService $etudiantService,CampagneService $campagneService,CiteService $citeService)
    {
       
        $this->etudiantService = $etudiantService;
        $this->campagneService = $campagneService;
        $this->citeService = $citeService;
         
    }


    /**
     * Filtres de la liste des admissions (statut d'inscription de l'étudiant) : demandes uniquement,
     * les étudiants logés et les dossiers en vérification sont suivis sur la page « Résidents & contrats ».
     */
    private const FILTRES_ADMISSION = ['nouvelles_demandes', 'en_attente', 'annuler'];

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
        $etat = in_array($filtre, self::FILTRES_ADMISSION, true) ? $filtre : 'nouvelles_demandes';
        $recherche = trim((string) $request->query('q', ''));
        $etatDossier = (string) $request->query('etat_dossier', '');
        $etatDossier = array_key_exists($etatDossier, DossierWorkflow::LABELS) ? $etatDossier : '';
        $url = fn (string $route, array $parametres = []) => $access->allows($admin, $route) ? route($route, $parametres) : null;
        $repository = app(\App\Repositories\EtudiantRepository::class);
        // Bandeau : campagne au statut « active » (comme la page Campagnes), pas celle de l'année universitaire.
        $campagne = $this->campagneActive();

        return Inertia::render('Admin/Hebergement/Admissions/Index', [
            'dossiers' => $repository->getAdmissionsPage($etat, 10, $recherche, $etatDossier)->through(fn (Etudiant $etudiant) => [
                'id' => (string) $etudiant->id_etudiant,
                'name' => $etudiant->name,
                'numero_demande' => $etudiant->derniereDemande?->numero_demande,
                'etat_dossier' => $etudiant->derniereDemande?->etat_dossier,
                'date' => $etudiant->created_at?->format('Y-m-d H:i'),
                'personnel' => $personnel ? [
                    'telephone' => $etudiant->telephone1,
                    'adresse' => $etudiant->adresse,
                    'region' => $etudiant->region?->name,
                    'ville' => $etudiant->ville?->name,
                    'date_naissance' => $etudiant->date_naissance?->toDateString(),
                ] : null,
                'demandes' => $historique ? (int) $etudiant->demandes_count : null,
                'url' => $url('admin.hebergement.campagnes_admission.show', ['id' => $etudiant->id_etudiant]),
            ]),
            'stats' => $repository->getStats(),
            'etat' => $etat,
            'filters' => ['q' => $recherche, 'etat_dossier' => $etatDossier],
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
                'filtres' => $access->allows($admin, 'admin.hebergement.campagnes_admission.admission')
                    ? collect(self::FILTRES_ADMISSION)->mapWithKeys(fn ($f) => [$f => route('admin.hebergement.campagnes_admission.admission', ['etat' => $f])])
                    : null,
            ],
        ]);
    }

    // Nouvelle méthode pour récupérer uniquement les stats (pour API ou appels AJAX)
    public function getStats()
    {
        $result = $this->etudiantService->getStats(); 

        if ($result['success']) {
            return response()->json($result);
        }

        return response()->json($result, 500);
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
                'label' => \App\Http\Services\DisciplinaryImpactService::LABELS[$statutDisciplinaire] ?? $statutDisciplinaire,
                'bloquant' => in_array($statutDisciplinaire, ['suspension', 'expulsion'], true),
            ] : null,
            'eligibilite' => $vues->allows($admin, 'admissions.eligibility')
                ? $this->criteresEligibilite($etudiant, $this->campagneActive())
                : null,
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
                    'non_respectes' => collect($derogation->eligibility['regles'] ?? [])->reject(fn ($regle) => $regle['ok'] ?? false)
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
                'demander' => in_array($derniere?->etat_dossier, ['soumis', 'en_etude'], true)
                    && !$derogations->where('demande_id', $derniere->getKey())->contains('status', 'pending')
                    ? $url('admin.hebergement.campagnes_admission.derogations.store', ['student' => $etudiant->getKey()]) : null,
            ] : null,
            'suivi' => $voirAdmissions && $derniere ? $this->suiviDossier($derniere, $admin) : null,
            'profil' => $personnel ? [
                'boursier' => (bool) $etudiant->boursier,
                'niveau' => $derniere?->niveauEtudiantActuel?->name,
                'campagne' => $derniere?->campagne?->annee_campagne,
                'groupe_sanguin' => $etudiant->groupeSanguin?->name,
                'telephone1' => $etudiant->telephone1,
                'telephone2' => $etudiant->telephone2,
                'email1' => $etudiant->email1,
                'email2' => $etudiant->email2,
                'region' => $etudiant->region?->name,
                'ville' => $etudiant->ville?->name,
                'adresse' => $etudiant->adresse,
                'nationalite' => $etudiant->nationalite,
                'date_naissance' => $etudiant->date_naissance?->toDateString(),
                'lieu_naissance' => $etudiant->lieu_naissance,
                'type_piece' => $etudiant->typePiece?->name,
                'numero_piece' => $etudiant->numero_piece,
                'date_expiration' => $etudiant->date_expiration ? Carbon::parse($etudiant->date_expiration)->toDateString() : null,
            ] : null,
            'historique' => $historique ? $etudiant->demandes->sortByDesc('created_at')->values()->map(fn ($item) => [
                'id' => (string) $item->getKey(),
                'numero' => $item->numero_demande,
                'annee' => $item->annee_academique_precedent,
                'date' => $item->created_at ? Carbon::parse($item->created_at)->toDateString() : null,
                'statut' => $item->statut_demande,
            ]) : null,
            'urls' => [
                'admissions' => $url('admin.hebergement.campagnes_admission.admission'),
                'contrat' => $url('admin.etudiant.contrat', ['etudiant' => $etudiant->id_etudiant]),
            ],
        ]);
    }

    /**
     * Critères d'éligibilité affichés à l'agent (mêmes règles que l'ancienne vue) : état ok, nok ou nd (non renseigné).
     * La décision reste prise par le workflow ; ce tableau sert d'aide à l'étude du dossier.
     */
    private function criteresEligibilite(Etudiant $demande, ?Campagne $campagne): array
    {
        $derniere = $demande->derniereDemande;
        $niveauActuel = $derniere?->niveauEtudiantActuel;
        $regles = [];
        $regle = fn (string $label, string $regleCampagne, string $valeur, string $etat) => ['label' => $label, 'campagne' => $regleCampagne, 'etudiant' => $valeur, 'etat' => $etat];

        // 1. Âge maximum selon le niveau
        $age = $demande->date_naissance?->age;
        $ageMax = $campagne?->{'age_'.strtoupper($niveauActuel->abreviation ?? '').'_max'} ?? null;
        if ($ageMax !== null) {
            $regles[] = $age === null
                ? $regle('Âge maximum', '≤ '.$ageMax.' ans', 'Date de naissance non renseignée', 'nd')
                : $regle('Âge maximum (niveau '.strtoupper($niveauActuel->name ?? '?').')', '≤ '.$ageMax.' ans', $age.' ans', $age <= $ageMax ? 'ok' : 'nok');
        }
        // 2. Moyenne minimale
        if ($campagne?->moyenne_min !== null) {
            $moyenne = $derniere?->moyenne;
            $regles[] = $moyenne === null
                ? $regle('Moyenne', '≥ '.$campagne->moyenne_min.'/20', 'Non renseignée', 'nd')
                : $regle('Moyenne', '≥ '.$campagne->moyenne_min.'/20', $moyenne.'/20', $moyenne >= $campagne->moyenne_min ? 'ok' : 'nok');
        }
        // 3. Crédits validés (hors L1)
        if ($campagne?->pourcentage_validate !== null && ($niveauActuel->abreviation ?? null) != 'L1') {
            $credits = $derniere?->credit_valider;
            $regles[] = $credits === null
                ? $regle('Crédits validés', '≥ '.$campagne->pourcentage_validate.' %', 'Non renseigné', 'nd')
                : $regle('Crédits validés', '≥ '.$campagne->pourcentage_validate.' %', $credits.' %', $credits >= $campagne->pourcentage_validate ? 'ok' : 'nok');
        }
        // 4. Distance domicile ↔ campus
        if ($campagne?->distance_min !== null) {
            $distance = $demande->distance;
            $regles[] = $distance === null
                ? $regle('Distance domicile – campus', '≥ '.$campagne->distance_min.' km', 'Non renseignée', 'nd')
                : $regle('Distance domicile – campus', '≥ '.$campagne->distance_min.' km', $distance.' km', $distance >= $campagne->distance_min ? 'ok' : 'nok');
        }
        if ($campagne !== null) {
            // 5. Boursier (toujours accepté)
            $regles[] = $regle('Boursier', 'Acceptés', $demande->boursier ? 'Oui' : 'Non', 'ok');
        }
        // 6. Redoublement
        if ($campagne?->redoublement !== null) {
            $autorise = (bool) $campagne->redoublement;
            $redoublant = (bool) ($demande->redoublant ?? false);
            $regles[] = $regle('Redoublement', $autorise ? 'Autorisé' : 'Non autorisé', $redoublant ? 'Redoublant' : 'Non redoublant', $autorise || !$redoublant ? 'ok' : 'nok');
        }
        // 7. PMR
        if ($campagne?->pmr !== null) {
            $autorise = (bool) $campagne->pmr;
            $pmr = (bool) ($demande->pmr ?? false);
            $regles[] = $regle('PMR (mobilité réduite)', $autorise ? 'Accepté' : 'Non accepté', $pmr ? 'Oui' : 'Non', !$pmr || $autorise ? 'ok' : 'nok');
        }
        // 8. Étudiants internationaux
        if ($campagne?->internationnaux !== null) {
            $autorise = (bool) $campagne->internationnaux;
            $nationalite = strtolower(trim((string) $demande->nationalite));
            $international = $demande->nationalite !== null && !in_array($nationalite, ['ivoirienne', 'ivoirien'], true);
            $regles[] = $regle('Étudiant international', $autorise ? 'Accepté' : 'Non accepté', $international ? 'Oui ('.$demande->nationalite.')' : 'Non', !$international || $autorise ? 'ok' : 'nok');
        }
        // 9. Nouveau bachelier
        if ($campagne?->new_bachelier !== null) {
            $autorise = (bool) $campagne->new_bachelier;
            $nouveau = (bool) ($demande->nouveau_bachelier ?? false);
            $regles[] = $regle('Nouveau bachelier', $autorise ? 'Accepté' : 'Non accepté', $nouveau ? 'Oui' : 'Non', !$nouveau || $autorise ? 'ok' : 'nok');
        }
        // 10. Réadmission
        if ($campagne?->readmission !== null) {
            $autorise = (bool) $campagne->readmission;
            $nombre = $demande->demandes->count();
            $regles[] = $regle('Réadmission', $autorise ? 'Autorisée' : 'Non autorisée', $nombre > 1 ? 'Oui ('.$nombre.' demande(s))' : 'Non', $nombre <= 1 || $autorise ? 'ok' : 'nok');
        }
        // 11. Documents validés
        $total = count($demande->documents);
        $valides = collect($demande->documents)->where('statut', 'valide')->count();
        $pourcentage = $total > 0 ? round($valides / $total * 100) : 0;
        $requis = $campagne?->pourcentage_validate ?? 100;
        $regles[] = $regle('Documents validés', '≥ '.$requis.' %', $valides.'/'.$total.' ('.$pourcentage.' %)', $pourcentage >= $requis ? 'ok' : 'nok');

        $conformes = collect($regles)->where('etat', 'ok')->count();

        return [
            'campagne' => $campagne?->annee_campagne,
            'regles' => $regles,
            'conformes' => $conformes,
            'score' => $regles ? (int) round($conformes / count($regles) * 100) : 0,
        ];
    }

    /** Campagne au statut « active » la plus récente : référence du bandeau et des critères d'éligibilité. */
    private function campagneActive(): ?Campagne
    {
        return Campagne::where('statut', 'active')->orderByDesc('created_at')->first();
    }

    /** Suivi du dossier (équivalent admin du partiel `dossier-cycle`) : parcours, actions permises et historique. */
    private function suiviDossier($dossier, $admin): array
    {
        $actions = array_values(array_intersect(DossierWorkflow::NEXT[$dossier->etat_dossier] ?? [], ['soumis', 'incomplet', 'eligible', 'refuse', 'annule']));
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

    public function getCampagne($id)
    {
        try {
            $campagne = $this->campagneService->getCampagneDetails($id);

            return response()->json([
                'success' => true,
                'data' => $campagne
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Campagne non trouvée'
            ], 404);
        }
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
        $campagnes->loadMissing('residences.cites.batiment.chambresDisponibles');
        $page = LengthAwarePaginator::resolveCurrentPage();
        $admin = $request->user('admin');
        $url = fn (string $route) => $access->allows($admin, $route) ? route($route) : null;

        return Inertia::render('Admin/Hebergement/Campagnes/Index', [
            'campagnes' => (new LengthAwarePaginator(
                $campagnes->forPage($page, 10)->map(fn (Campagne $campagne) => $this->campagneListe($campagne) + [
                    'urls' => [
                        'edit' => $access->allows($admin, 'admin.hebergement.campagnes_admission.edit_campagne')
                            ? route('admin.hebergement.campagnes_admission.edit_campagne', $campagne->id_campagne) : null,
                        'destroy' => $access->allows($admin, 'admin.hebergement.campagnes_admission.destroy_campagne')
                            ? route('admin.hebergement.campagnes_admission.destroy_campagne', $campagne->id_campagne) : null,
                    ],
                ])->values(),
                $campagnes->count(),
                10,
                $page,
                ['path' => $request->url(), 'query' => $request->query()],
            )),
            'stats' => $result['data']['stats'],
            'etat' => $etat,
            'urls' => [
                'hebergement' => route('admin.hebergement.index'),
                'admissions' => $url('admin.hebergement.campagnes_admission.admission'),
                'create' => $url('admin.hebergement.campagnes_admission.create_campagne'),
                'filtres' => collect(self::FILTRES_CAMPAGNE)
                    ->mapWithKeys(fn ($filtre) => [$filtre => route('admin.hebergement.campagnes_admission.campagnes', ['etat' => $filtre])]),
            ],
        ]);
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
        $texte = fn ($valeur) => $valeur === null ? '' : (string) $valeur;

        return Inertia::render('Admin/Hebergement/Campagnes/Edit', $this->optionsFormulaire($citeIds, $documentIds) + [
            'campagne' => [
                'id' => (string) $campagne->id_campagne,
                'annee_campagne' => (string) $campagne->annee_campagne,
                'description' => (string) $campagne->description,
                'periode_debut' => $date($campagne->periode_debut),
                'periode_fin' => $date($campagne->periode_fin),
                'date_echeance_demande' => $date($campagne->date_echeance_demande),
                'date_echeance_contrat' => $date($campagne->date_echeance_contrat),
                'moyenne_min' => $texte($campagne->moyenne_min),
                'pourcentage_validate' => $texte($campagne->pourcentage_validate),
                'distance_min' => $texte($campagne->distance_min),
                ...collect(self::NIVEAUX)->mapWithKeys(fn ($niveau) => ['age_'.$niveau.'_max' => $texte($campagne->{'age_'.$niveau.'_max'})])->all(),
                ...collect(['admission', 'readmission', 'internationnaux', 'redoublement', 'pmr', 'is_participate', 'is_discipline'])
                    ->mapWithKeys(fn ($champ) => [$champ => (bool) $campagne->{$champ}])->all(),
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
                    'places' => (int) $cite->batiment->sum(fn ($batiment) => $batiment->chambresDisponibles->sum('capacite_max')),
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
            'description' => $campagne->description,
            'statut' => $campagne->statut,
            'periode_debut' => $date($campagne->periode_debut),
            'periode_fin' => $date($campagne->periode_fin),
            'date_echeance_demande' => $date($campagne->date_echeance_demande),
            'date_echeance_contrat' => $date($campagne->date_echeance_contrat),
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

    /**
     * Créer une nouvelle campagne
     */
    public function storeCampagne(CampagneRequest $request): RedirectResponse
    {
        try {

            $data = $request->validated();

            // Traitement des résidences sélectionnées
            $residences = $data['residences'] ?? [];
            unset($data['residences']);

            // Traitement des documents requis
            $documents = $data['documents'] ?? [];
            unset($data['documents']);

            // Création de la campagne

        //  dd($requestData);
            $campagne = $this->campagneService->createCampagne($data);

            // Association des résidences (à implémenter selon votre logique)
            if (!empty($residences)) {
                foreach ($residences as $residence) {

                    $residenceCampagne = ResidenceCampagne::create([
                        'campagne_id' => $campagne['id_campagne'],
                        'cite_id' => $residence,

                    ]);

                }
            }

            // Association des documents requis (à implémenter selon votre logique)
            if (!empty($documents)) {
                foreach ($documents as $doc) {

                    $documentCampagne = DocumentCampagne::create([
                        'id_campagne' => $campagne['id_campagne'],
                        'document_id' => $doc,

                    ]);

                }

            }

            return redirect()
                ->route('admin.hebergement.campagnes_admission.campagnes')
                ->with('success', 'Campagne créée avec succès');

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
       // dd($documentId, $statut, $message);
        $document = $documentService->updateDocument($documentId, $statut, $message);

        return back();
    }
}
