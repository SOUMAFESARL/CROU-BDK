<?php

namespace App\Http\Controllers\api\parametrage;


use App\Http\Controllers\Controller;
use App\Repositories\Interfaces\ConfigAppRepositoryInterface;
use Illuminate\Http\Request;
use App\Models\Region;
use App\Models\Document;
use App\Models\TypeChambre;
use App\Models\NiveauEtude;
use App\Models\Filiere;
use App\Models\Ville;
use App\Models\Genre;
use App\Models\groupeSanguin;
use App\Models\TypePiece;
use App\Models\Annonce;
use App\Http\Services\EtudiantService;


class parametrageController extends Controller
{
     protected   $repo;
    public function __construct(
        ConfigAppRepositoryInterface $repo,
        EtudiantService $etudiantService,
    ) {
        $this->repo =$repo;
        $this->etudiantService =$etudiantService;
    }
    public function region()
    {
        try {
           $region = Region::all();
            return response()->json(
                $region

                , 200);
        } catch (\Exception $th) {
            return response()->json(['message' => "Erreur survenue lors du chargement."], 400);
        }
    }

    public function ville($region)
    {
        try {
            $villle = Ville::where('region_id', $region)->get();

            return response()->json(
                $villle

                , 200);
        } catch (\Exception $th) {
            return response()->json(['message' => "Erreur survenue lors du chargement."], 400);
        }
    }

    public function genre()
    {
        try {
            $genre = Genre::all();

            return response()->json(
                $genre

                , 200);
        } catch (\Exception $th) {
            return response()->json(['message' => "Erreur survenue lors du chargement."], 400);
        }
    }

    public function typeDocument()
    {
        try {
            $document = Document::getAllDocuments($is_active=1);
            return response()->json(
                $document

                , 200);
        } catch (\Exception $th) {
            return response()->json(['message' => "Erreur survenue lors du chargement."], 400);
        }
    }

    public function typeChanbre()
    {
        try {
            $type = TypeChambre::all();
            return response()->json(
                $type

                , 200);
        } catch (\Exception $th) {
            return response()->json(['message' => "Erreur survenue lors du chargement."], 400);
        }
    }
    public function niveauEtude()
    {
        try {
            $niveau = NiveauEtude::all();
            return response()->json(
                $niveau

                , 200);
        } catch (\Exception $th) {
            return response()->json(['message' => "Erreur survenue lors du chargement."], 400);
        }
    }

    public function groupeSanguin()
    {
        try {
            $groupeSanguin = groupeSanguin::all();
            return response()->json(
                $groupeSanguin

                , 200);
        } catch (\Exception $th) {
            return response()->json(['message' => "Erreur survenue lors du chargement."], 400);
        }
    }
    public function TypePiece()
    {
        try {
            $typePiece = TypePiece::all();
            return response()->json(
                $typePiece

                , 200);
        } catch (\Exception $th) {
            return response()->json(['message' => "Erreur survenue lors du chargement."], 400);
        }
    }
    public function Filiere()
    {
        try {
            $filiere = Filiere::all();
            return response()->json(
                $filiere

                , 200);
        } catch (\Exception $th) {
            return response()->json(['message' => "Erreur survenue lors du chargement."], 400);
        }
    }

    public function config(){
        $config = $this->repo->getConfig();

         return response()->json( [
            // Alias pratiques utilisés dans la vue
            'config'       => $config,
            'profil'       => $config,
            'notif'        => $config,
            'paiement'     => $config,
            'taxes'        => $config,
            'devises'      => $config,
            'integrations' => $config,
            'securite'     => $config,
        ]);
    }


     public function annonces(Request $request,$etudiant_id)
    {
            
        $etudiant = $this->etudiantService->getEtudiantDetails($etudiant_id);

        if (!$etudiant) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }

        $annonceFilters = $request->only(['canal', 'priorite', 'search']); // nom distinct, pas de collision

        $query = Annonce::visibleParEtudiant($etudiant['data'])
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

        $annonces = $query->limit(5)->get();

        return response()->json([
            'success' => true,
            'data'    => $annonces,
        ]);
    }

}