<?php

namespace App\Http\Controllers\admin\immobilier\vie_residentielle;

use Inertia\Inertia;
use Illuminate\Support\Str;
use App\Services\AdminAccess;
use App\Models\Etudiant;
use App\Models\Chambre;
use App\Models\Batiment;
use App\Models\Annonce;
use App\Http\Controllers\Controller;
use App\Repositories\Interfaces\AnnonceRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Services\CiteService;
use App\Http\Services\BatimentService;
use App\Http\Services\EtudiantService;
use App\Http\Services\ChambreService;

class AnnonceController extends Controller
{
    protected $annonceRepository;
    protected $citeService;
    protected $batimentService;
    protected $chambreService;
    protected  $etidantService;

    public function __construct(AnnonceRepositoryInterface $annonceRepository
        ,CiteService $citeService,EtudiantService $etidantService,
         BatimentService  $batimentService,ChambreService $chambreService
    )
    {
        $this->annonceRepository = $annonceRepository;
        $this->citeService = $citeService;
        $this->batimentService = $batimentService;
        $this->chambreService = $chambreService;
        $this->etidantService = $etidantService;
    }

    private const STATUTS = ['publie' => 'Publiée', 'brouillon' => 'Brouillon', 'planifie' => 'Planifiée', 'annule' => 'Annulée'];

    private const AUDIENCES = [
        'all' => 'Tous les étudiants', 'cite' => 'Par cité', 'batiment' => 'Par bâtiment',
        'chambre' => 'Par chambre', 'specific' => 'Sélection d’étudiants',
    ];

    /**
     * Liste des annonces (page Inertia ; création, modification et publication dans la même page).
     */
    public function index(Request $request)
    {
        $filters = [
            'statut' => array_key_exists((string) $request->query('statut'), self::STATUTS) ? $request->query('statut') : '',
            'canal' => in_array($request->query('canal'), Annonce::CANAUX, true) ? $request->query('canal') : '',
            'search' => trim((string) $request->query('search', '')),
            'date_debut' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('date_debut')) ? $request->query('date_debut') : '',
            'date_fin' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('date_fin')) ? $request->query('date_fin') : '',
        ];

        $access = app(AdminAccess::class);
        $admin = $request->user('admin');
        $url = fn (string $route, array|string $parametres = []) => $access->allows($admin, $route) ? route($route, $parametres) : null;

        $annonces = $this->annonceRepository->paginateWithFilters(array_filter($filters), 10)
            ->through(fn (Annonce $annonce) => [
                'id' => $annonce->id_annonce,
                'titre' => $annonce->titre,
                'contenu' => (string) $annonce->contenu,
                'extrait' => Str::limit(trim(html_entity_decode(strip_tags((string) $annonce->contenu))), 220),
                'statut' => $annonce->statut,
                'statut_label' => self::STATUTS[$annonce->statut] ?? $annonce->statut,
                'priorite' => $annonce->priorite,
                'canaux' => array_values(array_intersect($annonce->canaux ?? [], Annonce::CANAUX)),
                'audience' => $annonce->audience,
                'audience_label' => self::AUDIENCES[$annonce->audience] ?? $annonce->audience,
                'cibles' => $annonce->audience_cible ?: (object) [],
                'date_creation' => $annonce->created_at?->format('Y-m-d H:i'),
                'date_publication' => $annonce->date_publication?->format('Y-m-d H:i'),
                'date_planification' => $annonce->date_planification?->format('Y-m-d\TH:i'),
                'createur' => $annonce->createur?->name,
                'envois' => [
                    'acceptes' => (int) $annonce->deliveries_sent_count,
                    'en_attente' => (int) $annonce->deliveries_pending_count,
                    'echecs' => (int) $annonce->deliveries_failed_count,
                ],
                'vues' => (int) $annonce->vues,
                'reactions' => (int) $annonce->reactions,
                'pieces_jointes' => array_values(array_filter((array) $annonce->pieces_jointes, 'is_string')),
                'urls' => [
                    'update' => $annonce->statut !== 'annule' ? $url('admin.hebergement.annonce.update', $annonce->id_annonce) : null,
                    'publish' => !in_array($annonce->statut, ['publie', 'annule'], true) ? $url('admin.hebergement.annonce.publish', $annonce->id_annonce) : null,
                    'destroy' => $url('admin.hebergement.annonce.destroy', $annonce->id_annonce),
                ],
            ]);

        return Inertia::render('Admin/Hebergement/Annonces/Index', [
            'annonces' => $annonces,
            'stats' => $this->annonceRepository->countByStatut(),
            'filters' => $filters,
            'audiences' => collect(self::AUDIENCES)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
            'cibles' => [
                'cites' => $this->citeService->getCites()->map(fn ($cite) => ['id' => $cite->id_cite, 'name' => $cite->name])->values(),
                'batiments' => Batiment::query()->orderBy('name')->get(['id_batiment', 'cite_id', 'name'])
                    ->map(fn ($batiment) => ['id' => $batiment->id_batiment, 'cite_id' => $batiment->cite_id, 'name' => $batiment->name])->values(),
                'chambres' => Chambre::query()->whereNotNull('numero_chambre')->orderBy('numero_chambre')->get(['id_chambre', 'batiment_id', 'numero_chambre'])
                    ->map(fn ($chambre) => ['id' => $chambre->id_chambre, 'batiment_id' => $chambre->batiment_id, 'numero' => $chambre->numero_chambre])->values(),
                'etudiants' => Etudiant::query()->where('statut_inscription', 'residents')->orderBy('name')->get(['id_etudiant', 'name', 'matricule'])
                    ->map(fn ($etudiant) => ['id' => $etudiant->id_etudiant, 'name' => $etudiant->name, 'matricule' => $etudiant->matricule])->values(),
            ],
            'urls' => [
                'index' => route('admin.hebergement.annonce.index'),
                'store' => $url('admin.hebergement.annonce.store'),
            ],
        ]);
    }

    public function show($id)
    {
        $annonce = $this->annonceRepository->find($id);

        if (!$annonce) {
            return response()->json(['message' => 'Annonce introuvable'], 404);
        }

        return response()->json(['success' => true, 'data' => $annonce]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'titre'           => 'required|string|max:255',
            'contenu'         => 'required|string',
            'canaux'          => 'required|array|min:1',
            'canaux.*' => 'required|in:flux,sms,email|distinct',
            'audience'        => 'required|in:all,cite,batiment,chambre,specific',
            'cites'           => 'nullable|required_if:audience,cite|array|min:1',
            'cites.*' => 'required|string|exists:cite,id_cite',
            'batiments'       => 'nullable|required_if:audience,batiment|array|min:1',
            'batiments.*' => 'required|string|exists:batiment,id_batiment',
            'chambres'        => 'nullable|required_if:audience,chambre|array|min:1',
            'chambres.*' => 'required|string|exists:chambre,id_chambre',
            'etudiants'       => 'nullable|required_if:audience,specific|array|min:1',
            'etudiants.*' => 'required|string|exists:etudiants,id_etudiant',
            'statut'          => 'required|in:brouillon,publie,planifie',
            'priorite'        => 'required|in:normale,haute,urgente',
            'date_planifiee'  => 'nullable|required_if:statut,planifie|date|after:now',
        ], self::MESSAGES);

        $data = $this->normalizeAudience($data);
        $annonce = $this->annonceRepository->update($id, $data);
        abort_unless($annonce, 404);

        if (!$request->expectsJson()) {
            return back()->with('success', 'Annonce mise à jour avec succès.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Annonce mise à jour avec succès',
            'data'    => $annonce,
        ]);
    }

    /** Messages des règles communes à la création et à la modification. */
    private const MESSAGES = [
        'titre.required' => 'Le titre est obligatoire.',
        'contenu.required' => 'Le contenu est obligatoire.',
        'canaux.required' => 'Choisissez au moins un canal de diffusion.',
        'cites.required_if' => 'Choisissez au moins une cité.',
        'batiments.required_if' => 'Choisissez au moins un bâtiment.',
        'chambres.required_if' => 'Choisissez au moins une chambre.',
        'etudiants.required_if' => 'Choisissez au moins un étudiant.',
        'date_planifiee.required_if' => 'Indiquez la date de publication.',
        'date_planifiee.after' => 'La date de publication doit être dans le futur.',
    ];

    /**
     * Créer une annonce
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'titre'                => 'required|string|max:255',
            'contenu'               => 'required|string',
            'canaux'                => 'required|array|min:1',
            'canaux.*' => 'required|in:flux,sms,email|distinct',
            'audience'              => 'required|in:all,cite,batiment,chambre,specific',
            'cites'                 => 'nullable|required_if:audience,cite|array|min:1',
            'cites.*'               => 'exists:cite,id_cite',
            'batiments'             => 'nullable|required_if:audience,batiment|array|min:1',
            'batiments.*'           => 'exists:batiment,id_batiment',
            'chambres'              => 'nullable|required_if:audience,chambre|array|min:1',
            'chambres.*'            => 'exists:chambre,id_chambre',
            'etudiants'             => 'nullable|required_if:audience,specific|array|min:1',
            'etudiants.*'           => 'exists:etudiants,id_etudiant',
            'statut'                => 'required|in:brouillon,publie,planifie',
            'priorite'              => 'required|in:normale,haute,urgente',
            'date_planifiee' => 'nullable|required_if:statut,planifie|date|after:now',
            'fichiers'              => 'nullable|array',
            'fichiers.*'            => 'file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ], self::MESSAGES);
        $image = [];
        if ($request->hasFile('fichiers')) {
            foreach ($request->fichiers as $fichier) {
                $image[] = doc_upload('annonce', 'png', $fichier);
            }
        }
        $data['pieces_jointes'] = $image;
        $data = $this->normalizeAudience($data);

        $annonce = $this->annonceRepository->create($data);

        if ($data['statut'] === 'planifie' && !empty($data['date_planifiee'])) {
            $this->annonceRepository->schedule($annonce->id_annonce, $data['date_planifiee']);
        }

        $message = match ($data['statut']) {
            'publie' => 'Annonce publiée. Les envois sont en cours.',
            'planifie' => 'Annonce planifiée. Elle sera diffusée à la date prévue.',
            default => 'Brouillon enregistré.',
        };

        if (!$request->expectsJson()) {
            return back()->with('success', $message);
        }

        return response()->json(['success' => true, 'message' => 'Annonce enregistrée. Les envois seront traités à la publication.', 'data' => $annonce], 201);
    }

    /**
     * Publier une annonce
     */
    public function publish(Request $request, string $id)
    {
        abort_unless($this->annonceRepository->publish($id), 404);
        if (!$request->expectsJson()) return back()->with('success', 'Annonce publiée. Envois en cours.');

        return response()->json([
            'success' => true,
            'message' => 'Annonce publiée avec succès'
        ]);
    }

    /**
     * Supprimer une annonce
     */
    public function destroy(Request $request, string $id)
    {
        $this->annonceRepository->delete($id);

        if (!$request->expectsJson()) {
            return back()->with('success', 'Annonce supprimée.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Annonce supprimée avec succès'
        ]);
    }

    /**
     * Statistiques d'une annonce
     */
    public function stats(string $id)
    {
        $stats = $this->annonceRepository->getStats($id);

        return response()->json($stats);
    }

    /**
     * Exporter les annonces
     */
    public function export(Request $request)
    {
        $filters = $request->only(['statut', 'canal', 'date_debut', 'date_fin']);
        $data = $this->annonceRepository->export($filters);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
    private function normalizeAudience(array $data): array
    {
        $key = ['cite' => 'cites', 'batiment' => 'batiments', 'chambre' => 'chambres', 'specific' => 'etudiants'][$data['audience']] ?? null;
        $data['audience_cible'] = $key ? [$key => array_values(array_unique(array_map('strval', $data[$key] ?? [])))] : [];
        $data['date_planification'] = $data['statut'] === 'planifie' ? $data['date_planifiee'] : null;
        return $data;
    }

}