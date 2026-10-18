<?php



namespace App\Http\Controllers\web;


use App\Http\Controllers\Controller;
use App\Http\Requests\EtudiantRequest;
use App\Http\Services\EtudiantService;
use Illuminate\Http\Request;
use App\Models\Campagne;
use App\Models\Document;
use App\Models\Region;
use App\Models\Ville;
use App\Models\TypeChambre;
use App\Models\NiveauEtude;
use App\Models\Filiere;
use App\Models\Genre;
use App\Models\TypePiece;
use App\Models\groupeSanguin;
use App\Repositories\Interfaces\EtudiantRepositoryInterface;
use App\Repositories\Interfaces\DemandeRepositoryInterface;
use App\Repositories\Interfaces\DocumentEtudiantRepositoryInterface;
use App\Http\Services\DemandeService;
use App\Http\Services\CampagneValidationService;
use App\Http\Services\DocumentEtudiantService;
use App\Http\Requests\DemandeRequest;
use App\Http\Requests\DocumentEtudiantRequest;
use App\Http\Services\CampagneService;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Support\Facades\Auth;
use App\Support\SystemSecurity;

class  frontedController extends Controller
{
    // Déclarez la propriété
    protected $etudiantService;
    protected $campagneService;
    protected  $etat='active';

    // Injectez le service dans le constructeur
    public function __construct(EtudiantService $etudiantService,CampagneService $campagneService)
    {
        $this->etudiantService = $etudiantService;
        $this->campagneService = $campagneService;
    }
    // Page d'accueil : choix de l'espace de connexion (administration ou étudiant).
    public function index(): InertiaResponse
    {
        return Inertia::render('Auth/Choix', [
            'adminLoginUrl' => route('admin.login'),
            'etudiantLoginUrl' => route('etudiant.login'),
            'logoUrl' => asset('admin/assets/images/logos/crou_bdk.jpeg'),
            'backgroundUrl' => asset('files/assets/images/auth/crou_bondoukou.jpg'),
            'year' => now()->year,
        ]);
    }
    public function about(){
        $result = $this->campagneService->getListeCampagnesWithStats($this->etat );
        return view('web.a-propos.index',compact('result'));
    }

    public function contact(){
        $result = $this->campagneService->getListeCampagnesWithStats($this->etat );
        return view('web.contact.index',compact('result'));
    }
    public function contact_send(){
        $result = $this->campagneService->getListeCampagnesWithStats($this->etat );
        return view('web.contact.index',compact('result'));
    }

    public function login(): InertiaResponse
    {
        return Inertia::render('Etudiant/Auth/Login', [
            'loginUrl' => route('login.etudiant'),
            'forgotUrl' => route('etudiant.password.request'),
            // Lien vers le formulaire de demande, proposé uniquement pendant une période d'admission ouverte.
            'registerUrl' => Campagne::admissionsOuvertes()->exists() ? route('etudiant.register') : null,
            'csrfToken' => csrf_token(),
            'oldEmail' => old('email'),
            'logoUrl' => asset('admin/assets/images/logos/crou_bdk.jpeg'),
            'backgroundUrl' => asset('files/assets/images/auth/crou_bondoukou.jpg'),
            'year' => now()->year,
        ]);
    }

    /**
     * Formulaire public de demande d'hébergement (nouvel étudiant), proposé pendant une période d'admission ouverte.
     * L'envoi reste traité par postRegister() : les règles serveur ne changent pas.
     */
    public function register(): InertiaResponse
    {
        $campagne = Campagne::admissionsOuvertes()
            ->with('documentCampagnes.documents')
            ->orderByDesc('created_at')
            ->first();

        $options = fn ($items, string $cle = 'id') => $items
            ->map(fn ($item) => ['id' => (string) $item->{$cle}, 'libelle' => (string) $item->name])
            ->values();

        $documents = $campagne
            ? $campagne->documentCampagnes
                ->flatMap(fn ($lien) => $lien->documents)
                ->unique('id_document')
                ->sortBy('ordre_affiche')
                ->map(fn (Document $document) => [
                    'slug' => $document->slug,
                    'nom' => $document->name,
                    'requis' => (bool) $document->champs_requis,
                    'formats' => collect(preg_split('/[\s,;|]+/', (string) $document->format_accepte))
                        ->map(fn ($format) => strtolower(ltrim($format, '.')))
                        ->filter()
                        ->values(),
                ])
                ->values()
            : [];

        return Inertia::render('Etudiant/Auth/Demande', [
            'campagne' => $campagne ? [
                'id' => $campagne->id_campagne,
                'annee' => $campagne->annee_campagne,
                'dateLimite' => $campagne->periode_fin ? \Illuminate\Support\Carbon::parse($campagne->periode_fin)->format('d/m/Y') : null,
            ] : null,
            'documents' => $documents,
            'genres' => $options(Genre::all()),
            'typesPiece' => $options(TypePiece::all()),
            'groupesSanguins' => $options(groupeSanguin::all()),
            'niveaux' => $options(NiveauEtude::all(), 'id_niveau_etude'),
            'filieres' => $options(Filiere::orderBy('name')->get(), 'id_filiere'),
            'regions' => $options(Region::orderBy('name')->get()),
            // Toutes les villes d'un coup : la liste admin/list/city est réservée aux administrateurs.
            'villes' => Ville::orderBy('name')->get(['id', 'name', 'region_id'])
                ->map(fn ($ville) => ['id' => (string) $ville->id, 'libelle' => (string) $ville->name, 'region' => (string) $ville->region_id])
                ->values(),
            'liens' => [
                'envoyer' => route('register.post'),
                'connexion' => route('etudiant.login'),
                // Le compte est créé sans mot de passe : l'étudiant le définit via « Mot de passe oublié ».
                'motDePasse' => route('etudiant.password.request'),
            ],
            'logoUrl' => asset('admin/assets/images/logos/crou_bdk.jpeg'),
            'year' => now()->year,
        ]);
    }



    // public function postRegister(EtudiantRequest $request)
    // {
    //     try {


    //         $etudiantService = new EtudiantService(
    //             app()->make(EtudiantRepositoryInterface::class),
    //             app()->make(CampagneValidationService::class)
    //         );
    //         // Le service va traiter la création du dossier
    //         $result = $etudiantService->creerDossier($request);



    //         $demandeService = new DemandeService(
    //             app()->make(DemandeRepositoryInterface::class)
    //         );

    //         $documentService = new DocumentEtudiantService(
    //             app()->make(DocumentEtudiantRepositoryInterface::class)
    //         );

    //         $requestData = $request->all();
    //         $requestData['id_etudiant'] = $result['data']['id_etudiant'];

    //         $demandeRequest = new DemandeRequest();
    //         $documentRequest = new DocumentEtudiantRequest();
    //         $demandeRequest->merge($requestData);
    //         $documentRequest->merge($requestData);

    //         $demande = $demandeService->creerDemande($demandeRequest);


    //         // dd($demandeRequest);
    //         $document = $documentService->uploadDocument($documentRequest);



    //         if ($result['success']) {
    //             //dd($result['success']);
    //             return back()->with('success', $result['message']);
    //         } else {
    //             return back()
    //                 ->withInput()
    //                 ->with('error', $result['message']);
    //         }
    //     } catch (\Exception $e) {
    //         return back()
    //             ->withInput()
    //             ->with('error', 'Une erreur inattendue est survenue : ' . $e->getMessage());
    //     }
    // }


  public function postRegister(EtudiantRequest $request)
{
    try {
        $etudiantService = new EtudiantService(
            app()->make(EtudiantRepositoryInterface::class),
            app()->make(CampagneValidationService::class)
        );

        $result = $etudiantService->creerDossier($request);

        if (!$result['success']) {
            return back()->withInput()->with('error', $result['message']);
        }

        $demandeService = new DemandeService(
            app()->make(DemandeRepositoryInterface::class)
        );

        $documentService = new DocumentEtudiantService(
            app()->make(DocumentEtudiantRepositoryInterface::class)
        );

        $requestData = $request->all();
        $requestData['id_etudiant'] = $result['data']['id_etudiant'];

        $demandeRequest  = new DemandeRequest();
        $documentRequest = new DocumentEtudiantRequest();
        $demandeRequest->merge($requestData);
        $documentRequest->merge($requestData);

        $demande  = $demandeService->creerDemande($demandeRequest);
        $document = $documentService->uploadDocument($documentRequest);

        // --- Vérification automatique de l'éligibilité (documents non bloquants) ---
        $finalisation = $etudiantService->finaliserInscriptionAutomatique(
            $result['data']['id_etudiant'],
            $demande['data']['id_demande'] ?? null
        );

        $type = ($finalisation['success'] ?? false) ? 'success' : 'error';

        return back()->with($type, $finalisation['message']);

    } catch (\Exception $e) {
        return back()
            ->withInput()
            ->with('error', 'Une erreur inattendue est survenue : ' . \App\Support\UserFacingErrors::caught($e));
    }
}


    public function authenticateEtudiant(Request $request): RedirectResponse
    {

        $credentials = $request->validate([
            'email' => ['required', 'email'], // Added email validation
            'password' => ['required'],
        ]);

        // Rename email1 to email for Auth::attempt()
        $authCredentials = [
            'email1' => $request->email,  // Map email1 to email
            'password' => $request->password,
        ];

        $loginKey = SystemSecurity::loginKey($request, 'etudiant');
        if (SystemSecurity::tooManyAttempts($loginKey)) {
            return back()->withErrors(['email' => 'Compte temporairement bloqué. Réessayez dans '.SystemSecurity::remainingSeconds($loginKey).' secondes.'])->onlyInput('email');
        }
        if (Auth::guard('etudiant')->attempt($authCredentials, $request->remember)) {
            SystemSecurity::clear($loginKey);
            $request->session()->regenerate();
            SystemSecurity::invalidateOtherSessions($request, (string) Auth::guard('etudiant')->id());

            $user = Auth::guard('etudiant')->user();

            if ($user->statut == 1) {
               // dd($user);
                return redirect()->intended(route('etudiants.dashboard'));
            } else {
                Auth::guard('etudiant')->logout(); // Log out the disabled user
                return back()->withErrors([
                    'email' => "Votre compte a été désactivé. Veuillez contacter votre administrateur."
                ])->onlyInput('email');
            }
        }

        SystemSecurity::hit($loginKey);
        return back()->withErrors([
            'error' => 'Veuillez vérifier votre email ou votre mot de passe.',
        ])->onlyInput('email');
    }

    /**
     * Déconnexion de l'espace étudiant : ferme la session (et le cookie « se souvenir de moi »)
     * puis renvoie vers la page de connexion.
     */
    public function logoutEtudiant(Request $request): RedirectResponse
    {
        Auth::guard('etudiant')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('etudiant.login');
    }




}
