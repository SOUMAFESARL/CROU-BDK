<?php

namespace App\Http\Controllers\admin\immobilier\vie_residentielle;

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

    /**
     * Liste des annonces
     */
    public function index(Request $request)
    {
        $filters = $request->only(['statut', 'canal', 'search', 'date_debut', 'date_fin', 'audience', 'priorite']);
       $cites = $this->citeService->getCites();
        $batiments = $this->batimentService->getAll();
        $annonces = $this->annonceRepository->paginateWithFilters($filters, 15);
        $chambres = $this->chambreService->getAll();
        $etudiants= $this->etidantService->getListeEtudiants('residents')['data'];

        $stats = $this->annonceRepository->countByStatut();


        return view('admin.hebergement.vie_residentielle.annonce', compact('annonces', 'stats', 'filters','cites','batiments','chambres','etudiants'));
    }

    public function show($id)
    {
        $annonce = $this->annonceRepository->find($id); // adapte à ton repository/modèle

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
        ]);

        $data = $this->normalizeAudience($data);
        $annonce = $this->annonceRepository->update($id, $data);
        abort_unless($annonce, 404);

        return response()->json([
            'success' => true,
            'message' => 'Annonce mise à jour avec succès',
            'data'    => $annonce,
        ]);
    }
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
        ]);
        $image =[];
        if($request->hasFile('fichiers')){
            foreach ($request->fichiers as $fichier) {
              $image[]=  doc_upload('annonce',  'png', $fichier);
            }
        }
       $data['pieces_jointes'] = $image;
       $data = $this->normalizeAudience($data);

        $annonce = $this->annonceRepository->create($data);

        if ($data['statut'] === 'planifie' && !empty($data['date_planifiee'])) {
            $this->annonceRepository->schedule($annonce->id_annonce, $data['date_planifiee']);
        }

        return response()->json(['success' => true, 'message' => 'Annonce enregistrée. Les envois seront traités à la publication.', 'data' => $annonce], 201);

//        return response()->json([
//            'success' => true,
//            'message' => 'Annonce créée avec succès',
//            'data'    => $annonce,
//        ], 201);
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
    public function destroy(string $id)
    {
        $this->annonceRepository->delete($id);

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