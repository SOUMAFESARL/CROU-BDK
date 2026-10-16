<?php

namespace App\Http\Controllers\etudiant;

use App\Http\Controllers\Controller;
use App\Repositories\Interfaces\EtatDesLieuxRepositoryInterface;
use Illuminate\Http\Request;
use App\Http\Requests\CampagneRequest;
use App\Http\Services\CampagneService;
use App\Http\Services\DemandeService;
use App\Http\Services\EtudiantService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Campus;
use App\Models\Cite;
use App\Models\Annonce;
use App\Models\Batiment;
use App\Models\Campagne;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Paiement;
use App\Models\Loyer;
use App\Models\DisciplinaryReport;
use App\Models\AssignationChambre;
use App\Models\DepartureRequest;
use Carbon\Carbon;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\OrdreTravailRequest;

use App\Repositories\Interfaces\OrdreTravailServiceInterface;


class dashboardController extends Controller
{
    protected $etudiantService;
    protected $campagneService;
    protected $demandeService;
    protected $etat = 'active';
    protected $etatDesLieux;
    protected $service;
    protected $serviceOrdreTravail;

    public function __construct(
        EtudiantService $etudiantService,
        CampagneService $campagneService,
        DemandeService $demandeService,
        EtatDesLieuxRepositoryInterface $etatDesLieux,
        OrdreTravailServiceInterface $serviceOrdreTravail
    ) {
        $this->etudiantService = $etudiantService;
        $this->campagneService = $campagneService;
        $this->demandeService = $demandeService;
        $this->etatDesLieux = $etatDesLieux;
        $this->serviceOrdreTravail = $serviceOrdreTravail;
    }

    /**
     * Afficher le dashboard de l'étudiant
     */


    /** Sections encore servies par la page Blade etudiant/index (`?section=`) ; sans section : tableau de bord React. */
    private const SECTIONS_BLADE = ['chambre', 'demandes', 'etat-lieux', 'paiements', 'annonce', 'maintenance', 'incidents', 'carte-interactive'];

    public function index()
    {
        // Vérifier si l'étudiant est authentifié
        if (!Auth::guard('etudiant')->check()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Veuillez vous connecter pour accéder à cette page.'
            ]);
        }

        try {

            $idEtudiant = Auth::guard('etudiant')->user()->id_etudiant;
            // Récupérer la liste des campagnes avec leurs statistiques
            $result = $this->campagneService->getCampagneUniversitaireWithStats();

            // Ajouter les détails de l'étudiant au résultat
            $result['etudiant'] = $this->etudiantService->getEtudiantDetails($idEtudiant);

            if (!($result['etudiant']['success'] ?? false) || empty($result['etudiant']['data'])) {
                Log::warning('Profil étudiant introuvable pour le tableau de bord', [
                    'etudiant_id' => $idEtudiant,
                    'message' => $result['etudiant']['message'] ?? null,
                    'error' => $result['etudiant']['error'] ?? null,
                ]);

                Auth::guard('etudiant')->logout();
                request()->session()->invalidate();
                request()->session()->regenerateToken();

                return redirect()->route('etudiant.login')->withErrors([
                    'email' => 'Votre profil étudiant est introuvable ou incomplet. Veuillez vous reconnecter.',
                ]);
            }

            // La campagne du tableau de bord est celle de la dernière demande de
            // l'étudiant. Elle doit rester disponible pendant toute l'année
            // universitaire, même lorsque sa période d'admission est terminée.
            $etudiant = $result['etudiant']['data'];
            $campagneEtudiant = $etudiant->derniereDemande?->campagne_id
                ? Campagne::find($etudiant->derniereDemande->campagne_id)
                : null;

            $campagneTableauDeBord = $campagneEtudiant
                ?? collect($result['data']['campagnes'] ?? [])->first()
                ?? Campagne::query()
                    ->orderByDesc('annee_debut')
                    ->orderByDesc('created_at')
                    ->first();

            $result['data']['campagnes'] = $campagneTableauDeBord
                ? collect([$campagneTableauDeBord])
                : collect();

            // Tableau de bord migré en React ; les autres sections restent sur la page Blade etudiant/index.
            if (! in_array(request('section'), self::SECTIONS_BLADE, true)) {
                return \Inertia\Inertia::render('Etudiant/TableauDeBord', app(\App\Services\EtudiantDashboard::class)->props(
                    $etudiant,
                    $campagneTableauDeBord,
                    $this->getPaiementData($idEtudiant),
                    $this->serviceOrdreTravail->getPaginated(['etudiant_id' => $idEtudiant], 10),
                ))->toResponse(request())->header('Cache-Control', 'private, no-store');
            }
//dd($result['etudiant']);
            // Récupérer les statistiques des ordres de travail
            $result['statOT'] = $this->serviceOrdreTravail->getStats();

            // Récupérer les ordres de travail de l'étudiant avec pagination
            $filters = request()->only(['search', 'statut', 'categorie']);
            $filters['etudiant_id'] = $idEtudiant;
            $result['ordres'] = $this->serviceOrdreTravail->getPaginated($filters, 10);

            // Récupérer les données pour la carte
            $result['carte'] = $this->carte();
          $result['paiementData'] = $this->getPaiementData($idEtudiant);
            $result['paymentProviders'] = \App\Models\PaymentProvider::payableToStudents();
            $result['disciplinaryReports'] = DisciplinaryReport::query()
                ->where('declarant_id', $idEtudiant)
                ->latest('date_incident')
                ->limit(10)
                ->get();
            $result['departureRequests'] = DepartureRequest::with([
                    'chambre.cite',
                    'chambre.batiment',
                    'settlement',
                ])
                ->where('etudiant_id', $idEtudiant)
                ->latest('date_demande')
                ->get();

            $activeAssignment = AssignationChambre::actives()
                ->where('etudiant_id', $idEtudiant)
                ->latest('date_assignation')
                ->first();

            $result['roommates'] = $activeAssignment
                ? AssignationChambre::with('etudiant')
                    ->actives()
                    ->where('chambre_id', $activeAssignment->chambre_id)
                    ->where('etudiant_id', '!=', $idEtudiant)
                    ->get()
                    ->pluck('etudiant')
                    ->filter()
                    ->values()
                : collect();


//dd($result);
            // Retourner la vue avec les données
            return view('etudiant.index', compact('result'));

        } catch (\Exception $e) {
            Log::error('Erreur dashboard étudiant: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Une erreur est survenue : ' . \App\Support\UserFacingErrors::caught($e)]);
        }
    }

    public function storeDepartureRequest(Request $request): RedirectResponse
    {
        $studentId = Auth::guard('etudiant')->user()->id_etudiant;
        $minimumDate = today()->addDays(15)->toDateString();

        $data = $request->validate([
            'date_sortie_prevue' => ['required', 'date', 'after_or_equal:'.$minimumDate],
            'motif' => ['required', 'in:fin_etudes,demenagement,stage,raison_personnelle,autre'],
            'observations' => ['nullable', 'string', 'max:2000'],
            'conditions_acceptees' => ['accepted'],
        ], [
            'date_sortie_prevue.after_or_equal' => 'La date de sortie doit être prévue au moins 15 jours à l’avance.',
            'conditions_acceptees.accepted' => 'Vous devez accepter les conditions de sortie.',
        ]);

        $assignment = AssignationChambre::actives()
            ->where('etudiant_id', $studentId)
            ->latest('date_assignation')
            ->first();

        if (!$assignment) {
            return back()->withErrors(['departure' => 'Aucune chambre active ne permet de déposer une demande de sortie.']);
        }

        if (DepartureRequest::where('etudiant_id', $studentId)->where('statut', 'en_attente')->exists()) {
            return back()->withErrors(['departure' => 'Vous avez déjà une demande de sortie en attente.']);
        }

        DepartureRequest::create([
            'etudiant_id' => $studentId,
            'assignation_id' => $assignment->id_assignation,
            'chambre_id' => $assignment->chambre_id,
            'date_demande' => today(),
            'date_sortie_prevue' => $data['date_sortie_prevue'],
            'motif' => $data['motif'],
            'observations' => $data['observations'] ?? null,
            'statut' => 'en_attente',
            'created_by' => $studentId,
        ]);

        return redirect()
            ->route('etudiants.dashboard', ['section' => 'demandes', 'focus' => 'departure'])
            ->with('success', 'Votre demande de sortie a été enregistrée.');
    }

    public function profile(Request $request)
    {
        $student = $request->user('etudiant');
        abort_unless($student, 401);
        // Le libellé du genre est facultatif : la page reste disponible si la référence manque.
        $genre = rescue(fn () => $student->genre?->name, null, false);
        $onglet = (string) $request->query('onglet');

        return \Inertia\Inertia::render('Etudiant/Profil', [
            'onglet' => in_array($onglet, ['identite', 'coordonnees', 'securite'], true) ? $onglet : 'identite',
            'etudiant' => [
                'nom' => $student->name,
                'initiales' => \App\Services\EtudiantMenu::initiales($student->name),
                'matricule' => $student->matricule,
                'numeroEtudiant' => $student->numero_etudiant,
                'genre' => $genre,
                'dateNaissance' => $student->date_naissance?->format('d/m/Y'),
                'lieuNaissance' => $student->lieu_naissance,
                'nationalite' => $student->nationalite ? mb_convert_case($student->nationalite, MB_CASE_TITLE) : null,
                'faculte' => $student->faculte,
                'email1' => $student->email1,
            ],
            'coordonnees' => [
                'telephone1' => (string) $student->telephone1,
                'telephone2' => (string) $student->telephone2,
                'email2' => (string) $student->email2,
            ],
            // Mêmes règles que SystemSecurity::passwordRule(), affichées et vérifiées en direct (le serveur revérifie).
            'reglesMotDePasse' => [
                'min' => (int) systemConfig('password_min_length', 8),
                'mixed' => (bool) systemConfig('password_require_uppercase', true),
                'number' => (bool) systemConfig('password_require_number', true),
                'symbol' => (bool) systemConfig('password_require_special', false),
            ],
            'urls' => [
                'coordonnees' => route('etudiants.profile.update'),
                'motDePasse' => route('etudiants.profile.password'),
            ],
        ])->toResponse($request)->header('Cache-Control', 'private, no-store');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $student = $request->user('etudiant');
        abort_unless($student, 401);
        $data = $request->validateWithBag('passwordChange', [
            'password' => ['required', 'string', 'confirmed', \App\Support\SystemSecurity::passwordRule()],
        ], [
            'password.required' => 'Renseignez le nouveau mot de passe.',
            'password.confirmed' => 'La confirmation ne correspond pas au nouveau mot de passe.',
        ]);
        $student->password = \Illuminate\Support\Facades\Hash::make($data['password']);
        $student->setRememberToken(\Illuminate\Support\Str::random(60));
        $student->save();
        $request->session()->regenerate();
        return redirect()->route('etudiants.profile', ['onglet' => 'securite'])
            ->with('success', 'Votre mot de passe a été modifié.');
    }
    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email2' => ['nullable', 'email', 'max:255'],
            'telephone1' => ['required', 'string', 'max:30'],
            'telephone2' => ['nullable', 'string', 'max:30'],
        ], [
            'email2.email' => 'L’adresse e-mail secondaire n’est pas valide.',
            'telephone1.required' => 'Le numéro de téléphone principal est obligatoire.',
        ]);

        $student = Auth::guard('etudiant')->user();
        $student->update($data);

        return redirect()
            ->route('etudiants.profile', ['onglet' => 'coordonnees'])
            ->with('success', 'Vos coordonnées ont été mises à jour.');
    }

    public function annonces(Request $request)
    {
        if (!Auth::guard('etudiant')->check()) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }

        $etudiant = Auth::guard('etudiant')->user();

        $annonceFilters = $request->only(['canal', 'priorite', 'search']); // nom distinct, pas de collision

        $query = Annonce::visibleParEtudiant($etudiant)
            ->orderByDesc('date_publication'); 

        if (!empty($annonceFilters['canal'])) {
            $query->whereJsonContains('canaux', $annonceFilters['canal']);
        }

        if (!empty($annonceFilters['priorite'])) {
            $query->where('priorite', $annonceFilters['priorite']);
        }

        if (!empty($annonceFilters['search'])) {
            $search = $annonceFilters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('titre', 'like', "%{$search}%")
                    ->orWhere('contenu', 'like', "%{$search}%");
            });
        }

        $annonces = $query->paginate(5)->withQueryString();

        return response()->json([
            'success' => true,
            'data'    => $annonces,
        ]);
    }

    /**
     * Afficher la page de maintenance avec les ordres de travail
     */
    public function maintenance()
    {
        // Vérifier si l'étudiant est authentifié
        if (!Auth::guard('etudiant')->check()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Veuillez vous connecter pour accéder à cette page.'
            ]);
        }

        $idEtudiant = Auth::guard('etudiant')->user()->id_etudiant;

        try {
            // Récupérer les statistiques des ordres de travail
            $stats = $this->serviceOrdreTravail->getStats(['etudiant_id' => $idEtudiant]);

            // Récupérer les ordres de travail de l'étudiant avec pagination et filtres
            $filters = request()->only(['search', 'statut', 'categorie']);
            $filters['etudiant_id'] = $idEtudiant;
            $ordres = $this->serviceOrdreTravail->getPaginated($filters, 10);

            // Récupérer les détails de l'étudiant
            $etudiant = $this->etudiantService->getEtudiantDetails($idEtudiant);

            // Récupérer les cités pour les filtres
            $cites = Cite::where('statut', 'ouvert')->get();

            return view('etudiant.maintenance', compact('stats', 'ordres', 'etudiant', 'cites'));

        } catch (\Exception $e) {
            Log::error('Erreur page maintenance: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Une erreur est survenue : ' . \App\Support\UserFacingErrors::caught($e)]);
        }
    }

    public function carte()
    {
        // Récupérer tous les campus avec coordonnées
        $campuses = Campus::where('statut', '!=', 'ferme')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        // Récupérer toutes les cités avec coordonnées
        $cites = Cite::with('typeCite')->where('statut', '!=', 'ferme')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        // Récupérer tous les bâtiments avec coordonnées
        $batiments = Batiment::where('is_valid', 1)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        return ['campuses' => $campuses, 'cites' =>$cites, 'batiments'=>$batiments];
    }

    /**
     * Afficher la page de validation de demande
     * GET /etudiant/demande/{idDemande}
     */
    public function showValidationPage($idDemande)
    {
        // Vérifier l'authentification
        if (!Auth::guard('etudiant')->check()) {
            return redirect()->route('login');
        }

        $idEtudiantConnecte = Auth::guard('etudiant')->user()->id_etudiant;

        try {
            // Récupérer les détails de la demande
            $result = $this->etudiantService->getDetailsDemande($idDemande);

            if (!$result['success']) {
                return back()->withErrors(['error' => 'Demande non trouvée']);
            }
            // Vérifier que c'est bien la demande de l'étudiant connecté
            if ($result['data']['id_etudiant'] !== $idEtudiantConnecte) {
                Log::warning("Tentative d'accès non autorisé à la demande {$idDemande} par {$idEtudiantConnecte}");
                return back()->withErrors(['error' => 'Accès non autorisé']);
            }

            // Vérifier que la demande est validable
            if (!$result['data']['peut_valider']) {
                return back()->withErrors(['error' => 'Cette demande ne peut pas être validée']);
            }

            $details = $result['data'];
            return view('etudiant.validation-demande', compact('details'));

        } catch (\Exception $e) {
            Log::error('Erreur affichage validation: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Une erreur est survenue']);
        }
    }

    /**
     * Traiter l'acceptation ou le refus de la demande
     * POST /etudiant/demande/{idDemande}/valider
     */
    public function AcceptOrRefuseDemande(Request $request, $idDemande)
    {

        // Vérifier l'authentification
        if (!Auth::guard('etudiant')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }
       // dd($request->all());

        $idEtudiantConnecte = Auth::guard('etudiant')->user()->id_etudiant;



        try {
            // ÉTAPE 1: Valider les entrées
            $validated = $request->validate([
                'action' => 'required|in:accepter,refuser',
                'assignation_id' => 'required|string',
                'motif_refus' => 'required_if:action,refuser|nullable|string|max:500',
            ], [
                'motif_refus.required_if' => 'Indiquez le motif du refus.',
                'motif_refus.max' => 'Le motif ne doit pas dépasser 500 caractères.',
            ]);


            // ÉTAPE 2: Vérifier que la demande appartient à l'étudiant
            // Réponses en redirection + message (formulaire du tableau de bord React, via Inertia).
            $detailsResult = $this->etudiantService->getDetailsDemande($idEtudiantConnecte);

            if (!$detailsResult['success']) {
                return back()->with('error', 'Demande non trouvée.');
            }

            if ($detailsResult['data']['id_etudiant'] !== $idEtudiantConnecte) {
               Log::warning("Tentative de validation non autorisée: étudiant {$idEtudiantConnecte} sur demande {$idDemande}");
                return back()->with('error', 'Accès non autorisé.');
            }

            if (!$detailsResult['data']['peut_valider']) {
                return back()->with('error', 'Cette proposition ne peut plus recevoir de réponse.');
            }

            // ÉTAPE 3: Préparer les données
            $data = [
                'action' => $validated['action'],
                'assignation_id' => $validated['assignation_id'],

            ];


            if ($validated['action'] === 'refuser') {
                $data['motif_refus'] = $validated['motif_refus'];
            }

            // ÉTAPE 4: Valider la demande
            $result = $this->etudiantService->validerDemandeEtudiant($idEtudiantConnecte, $data,$idDemande);
           // dd($detailsResult,$data);
            // ÉTAPE 5: Retourner le résultat
            if (!$result['success']) {
                return back()->with('error', $result['message'] ?? 'La réponse n’a pas pu être enregistrée.');
            }

            // Log l'action
         //   Log::info("Demande {$idDemande} {$validated['action']}e par l'étudiant {$idEtudiantConnecte}");

            return back()->with('success', $result['message']);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Erreurs de champ renvoyées au formulaire (motif du refus…).
            throw $e;

        } catch (\Exception $e) {
            Log::error('Erreur lors de la validation de la demande: ' . $e->getMessage());
            return back()->with('error', 'Une erreur est survenue lors du traitement de votre réponse.');

        }
    }

    /**
     * Récupérer le statut actuel de la demande (AJAX)
     * GET /etudiant/demande/{idDemande}/statut
     */
    public function getStatutDemande($idDemande)
    {
        if (!Auth::guard('etudiant')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        } 

        $idEtudiantConnecte = Auth::guard('etudiant')->user()->id_etudiant;

        try {
            // Vérifier l'accès
            $detailsResult = $this->etudiantService->getDetailsDemande($idDemande);

            if (!$detailsResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Demande non trouvée'
                ], 404);
            }

            if ($detailsResult['data']['id_etudiant'] !== $idEtudiantConnecte) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            // Retourner le statut
            $result = $this->etudiantService->getStatutDemande($idDemande);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Erreur récupération statut: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du statut'
            ], 500);
        }
    }

    /**
     * Afficher l'historique des demandes de l'étudiant
     * GET /etudiant/demandes
     */
    public function listDemandes()
    {
        if (!Auth::guard('etudiant')->check()) {
            return redirect()->route('login');
        }

        $idEtudiant = Auth::guard('etudiant')->user()->id_etudiant;

        try {
            // Récupérer les détails de l'étudiant avec toutes ses demandes
            $result = $this->etudiantService->getEtudiantDetails($idEtudiant);

            if (!$result['success']) {
                return back()->withErrors(['error' => 'Impossible de charger vos demandes']);
            }

            $etudiant = $result['data'];

            return view('etudiant.mes-demandes', compact('etudiant'));

        } catch (\Exception $e) {
            Log::error('Erreur listDemandes: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Une erreur est survenue']);
        }
    }


    public function storeEdl(Request $request): RedirectResponse
    {
        $validated = $this->validateEdl($request);
        $etudiantId = (string) Auth::guard('etudiant')->user()->id_etudiant;

        // L'identité de l'étudiant et la signature CROU ne doivent jamais
        // pouvoir être imposées depuis le formulaire étudiant.
        $validated['id_etudiant'] = $etudiantId;
        unset($validated['signature_crou']);

        if ($validated['type_etat'] !== 'entree') {
            return redirect()->back()->with('error', "L'état des lieux de sortie doit d'abord être établi et assigné par le CROU.");
        }

        $existing = $this->etatDesLieux->findEntree($validated['id_chambre'], $etudiantId);

        $data = $this->buildData($validated, $request);

        if ($existing) {
            if ($existing->signature_crou) {
                return redirect()->back()->with('error', "Cet état des lieux est verrouillé depuis la signature du CROU.");
            }

            $this->etatDesLieux->update($existing->etat_des_lieux_id, $data);
        } else {
            $this->etatDesLieux->create($data);
        }

        $label = $request->type_etat === 'entree' ? "d'entrée" : "de sortie";
        return redirect()->back()->with('success', "État des lieux {$label} enregistré avec succès.");
    }

    public function updateEdl(Request $request, string $id): RedirectResponse
    {
        $existing = $this->etatDesLieux->findById($id);
        $etudiantId = (string) Auth::guard('etudiant')->user()->id_etudiant;

        if (!$existing || (string) $existing->id_etudiant !== $etudiantId) {
            abort(404);
        }

        $validated = $this->validateEdl($request);
        $validated['id_etudiant'] = $etudiantId;
        unset($validated['signature_crou']);

        if ($existing->type_etat === 'sortie') {
            if ($existing->signature_etudiant) {
                return redirect()->back()->with('error', "Vous avez déjà signé cet état des lieux de sortie.");
            }

            $signature = $validated['signature_etudiant'] ?? null;
            if (!$signature || !Str::startsWith($signature, 'data:image')) {
                return redirect()->back()->with('error', "Votre signature est obligatoire pour valider l'état des lieux de sortie.");
            }

            // Pour une sortie, l'étudiant ne peut modifier aucune constatation :
            // il ajoute uniquement sa propre signature au document créé par le CROU.
            $data = $this->buildData(['signature_etudiant' => $signature], $request);
            $this->etatDesLieux->update($id, $data);

            return redirect()->back()->with('success', "État des lieux de sortie signé avec succès.");
        }

        if ($existing->type_etat !== 'entree') {
            return redirect()->back()->with('error', "Type d'état des lieux invalide.");
        }

        if ($existing->signature_crou) {
            return redirect()->back()->with('error', "Cet état des lieux d'entrée ne peut plus être modifié après la signature du CROU.");
        }

        $data = $this->buildData($validated, $request);
        $this->etatDesLieux->update($id, $data);

        return redirect()->back()->with('success', 'État des lieux mis à jour avec succès.');
    }


    private function validateEdl(Request $request): array
    {
        return $request->validate([
            'type_etat'            => 'required|in:entree,sortie',
            'id_cite'              => 'required|string',
            'id_batiment'          => 'required|string',
            'id_chambre'           => 'required|string',
            'id_etudiant'          => 'required|string',
            'date_etat'            => 'nullable|date',
            'nbre_cle_recus'       => 'nullable|integer|min:0',
            'observation_generale' => 'nullable|string',
            // La signature CROU est exclusivement renseignée depuis l'espace admin.
            'signature_crou'       => 'prohibited',
            'signature_etudiant'   => 'nullable|string',
            'pieces_json'          => 'nullable|string',
        ]);
    }

//    private function buildData(array $validated, Request $request): array
//    {
//        // Signatures base64 → fichiers disque
//        foreach (['signature_crou', 'signature_etudiant'] as $field) {
//            $val = $validated[$field] ?? null;
//            if ($val && Str::startsWith($val, 'data:image')) {
//                $bin      = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $val));
//                $filename = 'signatures/' . Str::uuid() . '.png';
//                Storage::disk('public')->put($filename, $bin);
//
//             //   $filename = upload( 'signatures',  'png', $image = null, $val)
//                $validated[$field] = $filename;
//            } else {
//                // Pas de nouvelle signature → on ne touche pas à l'existante
//                unset($validated[$field]);
//            }
//        }
//
//        // Pièces JSON → tableau PHP
//        $pieces = [];
//        if (!empty($validated['pieces_json'])) {
//            $decoded = json_decode($validated['pieces_json'], true);
//            if (is_array($decoded)) {
//                $pieces = $decoded;
//            }
//        }
//        $validated['pieces'] = $pieces;
//        unset($validated['pieces_json']);
//
//        return $validated;
//    }


    private function buildData(array $validated, Request $request): array
    {
        // Signatures base64 → fichiers disque
        foreach (['signature_crou', 'signature_etudiant'] as $field) {
            $val = $validated[$field] ?? null;
            if ($val && Str::startsWith($val, 'data:image')) {
                $bin      = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $val));
                $filename = 'signatures/' . Str::uuid() . '.png';
                Storage::disk('public')->put($filename, $bin);
                $validated[$field] = $filename;
            } else {
                unset($validated[$field]);
            }
        }

        // Pièces JSON → tableau PHP avec clés normalisées et valeurs sécurisées
        $pieces = [];
        if (!empty($validated['pieces_json'])) {
            $decoded = json_decode($validated['pieces_json'], true);
            if (is_array($decoded)) {
                foreach ($decoded as $roomIndex => $room) {
                    $elements = [];
                    foreach ($room['elements'] ?? [] as $elIndex => $el) {
                        $etat = in_array($el['etat'] ?? '', ['bon', 'moyen', 'mauvais'])
                            ? $el['etat']
                            : 'bon';                         // valeur par défaut sûre

                        $observation = trim((string) ($el['observation'] ?? $el['obs'] ?? ''));
                        if ($etat === 'mauvais' && $observation === '') {
                            $pieceName = $room['name_piece'] ?? $room['name'] ?? 'Pièce ' . ($roomIndex + 1);
                            $elementName = $el['name_element'] ?? $el['name'] ?? 'Élément ' . ($elIndex + 1);

                            throw ValidationException::withMessages([
                                'pieces_json' => "Un commentaire est obligatoire pour « {$elementName} » dans « {$pieceName} » lorsque son état est mauvais.",
                            ]);
                        }

                        $elements[] = [
                            'name_element' => $el['name_element'] ?? $el['name'] ?? '',
                            'etat'         => $etat,
                            'observation'  => $observation !== '' ? $observation : null,
                            'photo'        => $el['photo'] ?? null,
                            'ordre'        => $el['ordre'] ?? ($elIndex + 1),
                        ];
                    }

                    $pieces[] = [
                        'name_piece' => $room['name_piece'] ?? $room['name'] ?? '',
                        'ordre'      => $room['ordre'] ?? ($roomIndex + 1),
                        'elements'   => $elements,
                        'photos'     => $room['photos'] ?? [],
                    ];
                }
            }
        }

        $validated['pieces'] = $pieces;
        unset($validated['pieces_json']);

        return $validated;
    }


    public function storeOrdreTravail(Request $request) // OrdreTravailRequest
    {
        try {
            if (!Auth::guard('etudiant')->check()) {
                return redirect()->route('login')->withErrors(['error' => 'Veuillez vous connecter']);
            }

            // Récupérer les données du formulaire
            $data = $request->all();
            $idEtudiant = Auth::guard('etudiant')->user()->id_etudiant;

            // Récupérer les détails de l'étudiant
            $result = $this->etudiantService->getEtudiantDetails($idEtudiant);

            if (!$result['success']) {
                throw new \Exception('Impossible de récupérer les informations de l\'étudiant');
            }

            $etudiant = $result['data'];

            // Récupérer l'assignation de chambre actuelle
            $assignation = $etudiant->assignationsChambre->first(); // Prendre la première assignation

            // Ajouter les informations de localisation
            if ($assignation && $assignation->chambre) {
                $data['etudiant_id'] = $etudiant->id_etudiant;
                $data['batiment_id'] = $assignation->chambre->batiment_id ?? null;
                $data['chambre_id'] = $assignation->chambre->id_chambre ?? null;
                $data['cite_id'] = $assignation->chambre->cite_id ?? null;
            } else {
                // Si l'étudiant n'a pas de chambre assignée, utiliser les données du formulaire
                $data['etudiant_id'] = $etudiant->id_etudiant;
            }

            // Définir le statut par défaut
            $data['statut'] = 'en_attente';

            // Créer l'ordre de travail
            $ordre = $this->serviceOrdreTravail->create($data);

          //  dd('dfghbn');

            if (!$ordre) {
                throw new \Exception('Erreur lors de la création de l\'ordre de travail');
            }

            Log::info("Ordre de travail créé par l'étudiant {$idEtudiant}: {$ordre->numero_ot}");

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

public function annonce(){

}
    public function pdfContrat($id_etudiant = null)
    {
        if (request()->routeIs('etudiants.pdfContrat')) {
            abort_if($id_etudiant !== null && (string) $id_etudiant !== (string) Auth::guard('etudiant')->id(), 404);
            $id_etudiant = Auth::guard('etudiant')->id();
        }
        $idEtudiant = $id_etudiant ?? Auth::guard('etudiant')->id();
        $etudiant = \App\Models\Etudiant::findOrFail($idEtudiant);

        return app(\App\Http\Services\StudentContractService::class)->stream($etudiant);
    }


    /**
 * Affiche la page des paiements de l'étudiant
 */
/**
 * Récupère les données de paiement pour l'étudiant (retourne un tableau)
 */
private function getPaiementData(string $idEtudiant): array
{
    try {
        // Récupérer les loyers de l'étudiant
        $loyers = Loyer::where('etudiant_id', $idEtudiant)
            ->where('statut', '!=', 'annule')
            ->orderBy('annee_facturation', 'desc')
            ->orderBy('mois_facturation', 'desc')
            ->get();

        // Récupérer les paiements de l'étudiant AVEC PAGINATION
        $paiements = Paiement::where('resident_id', $idEtudiant)
            ->where('statut_paiement', '!=', 'annule')
            ->orderBy('created_at', 'desc')
            ->paginate(10); // <- Utiliser paginate() au lieu de get()

        // Calculer les statistiques
        $totalAttendu = $loyers->sum('montant_total');
        $totalPaye = $loyers->sum('total_paye');
        $totalImpayes = $loyers->sum('solde_restant');
        $totalPenalites = $loyers->sum('total_penalites');
        $nbPaiements = $paiements->total(); // total() existe sur Paginator
        $nbLoyers = $loyers->count();
        $nbImpayes = $loyers->where('solde_restant', '>', 0)->count();
        
        $tauxRecouvrement = $totalAttendu > 0 
            ? round(($totalPaye / $totalAttendu) * 100, 1) 
            : 0;

        $stats = [
            'total_attendu' => $totalAttendu,
            'total_paye' => $totalPaye,
            'total_impayes' => $totalImpayes,
            'total_penalites' => $totalPenalites,
            'nb_paiements' => $nbPaiements,
            'nb_loyers' => $nbLoyers,
            'nb_impayes' => $nbImpayes,
            'taux_recouvrement' => $tauxRecouvrement,
            'statut_global' => $totalImpayes > 0 ? 'En retard' : 'À jour'
        ];

        // Récupérer les impayés
        $impayes = $loyers->where('solde_restant', '>', 0);

        // Calculer le prochain paiement
        $prochainPaiement = $this->getProchainPaiement($idEtudiant);

        // Récupérer l'étudiant
        $etudiant = \App\Models\Etudiant::find($idEtudiant);

        return [
            'etudiant' => $etudiant,
            'loyers' => $loyers,
            'paiements' => $paiements, // C'est un Paginator
            'stats' => $stats,
            'impayes' => $impayes,
            'prochainPaiement' => $prochainPaiement,
        ];

    } catch (\Exception $e) {
        Log::error('Erreur récupération paiements: ' . $e->getMessage());
        return [
            'etudiant' => null,
            'loyers' => collect(),
            'paiements' => collect(),
            'stats' => [],
            'impayes' => collect(),
            'prochainPaiement' => null,
        ];
    }
}

/**
 * Récupère le prochain mois à payer
 */
private function getProchainPaiement(string $etudiantId): array
{
    // Récupérer le dernier paiement validé
    $dernierPaiement = \App\Models\Paiement::where('resident_id', $etudiantId)
        ->where('statut_paiement', 'valide')
        ->orderBy('created_at', 'desc')
        ->first();

    if ($dernierPaiement && !empty($dernierPaiement->mois)) {
        $moisPayes = is_array($dernierPaiement->mois) ? $dernierPaiement->mois : json_decode($dernierPaiement->mois, true);
        if (is_array($moisPayes) && !empty($moisPayes)) {
            $dernierMois = end($moisPayes);
            if (preg_match('/(\d{2})-(\d{4})/', $dernierMois, $matches)) {
                $mois = (int) $matches[1] + 1;
                $annee = (int) $matches[2];
                if ($mois > 12) {
                    $mois = 1;
                    $annee++;
                }
                return [
                    'mois' => $mois,
                    'annee' => $annee,
                    'libelle' => $this->getMoisLibelle($mois) . ' ' . $annee
                ];
            }
        }
    }

    // Si aucun paiement, retourner le mois en cours
    $moisActuel = (int) now()->format('m');
    $anneeActuelle = (int) now()->format('Y');
    
    return [
        'mois' => $moisActuel,
        'annee' => $anneeActuelle,
        'libelle' => $this->getMoisLibelle($moisActuel) . ' ' . $anneeActuelle
    ];
}

/**
 * Récupère le libellé du mois en français
 */
private function getMoisLibelle($mois): string
{
    $moisListe = [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
        5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
        9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
    ];
    return $moisListe[(int) $mois] ?? $mois;
}

/**
 * Récupère les données de paiement avec pagination AJAX
 */
public function getPaiementsAjax(Request $request)
{
    if (!Auth::guard('etudiant')->check()) {
        return response()->json(['error' => 'Non authentifié'], 401);
    }

    $idEtudiant = Auth::guard('etudiant')->user()->id_etudiant;
    $page = $request->get('page', 1);
    $perPage = $request->get('per_page', 10);

    try {
        // Récupérer les paiements paginés
        $paiements = \App\Models\Paiement::where('resident_id', $idEtudiant)
            ->where('statut_paiement', '!=', 'annule')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Récupérer les statistiques
        $loyers = \App\Models\Loyer::where('etudiant_id', $idEtudiant)
            ->where('statut', '!=', 'annule')
            ->get();

        $totalPaye = $loyers->sum('total_paye');
        $totalImpayes = $loyers->sum('solde_restant');

        return response()->json([
            'success' => true,
            'data' => $paiements->items(),
            'pagination' => [
                'current_page' => $paiements->currentPage(),
                'last_page' => $paiements->lastPage(),
                'per_page' => $paiements->perPage(),
                'total' => $paiements->total(),
                'from' => $paiements->firstItem(),
                'to' => $paiements->lastItem(),
            ],
            'stats' => [
                'total_paye' => $totalPaye,
                'total_impayes' => $totalImpayes,
                'nb_paiements' => $paiements->total(),
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => \App\Support\UserFacingErrors::caught($e)
        ], 500);
    }
}


}
