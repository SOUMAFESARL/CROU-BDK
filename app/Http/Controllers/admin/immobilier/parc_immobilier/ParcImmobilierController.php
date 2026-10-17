<?php


namespace App\Http\Controllers\admin\immobilier\parc_immobilier;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Campus;
use App\Models\Cite;
use App\Models\Batiment;
use App\Models\Chambre;
use App\Models\AssignationChambre;
use App\Models\Region;
use App\Models\TypeCite;
use App\Models\TypeChambre;
use App\Models\Niveau_Aile_Batiment;
use App\Models\Aile_Batiment;
use App\Http\Services\CampusService;
use App\Http\Services\CiteService;
use App\Http\Services\BatimentService;
use App\Http\Services\ChambreService;
use App\Http\Services\UserService;
use App\Models\ReglementInterieur;
use App\Services\AdminAccess;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ParcImmobilierController extends Controller
{
    protected $campusService;
    protected $citeService;
    protected $batimentService;
    protected $chambreService;
    protected  $userService;


    public function __construct(CampusService $campusService,CiteService $citeService,
           BatimentService  $batimentService,ChambreService $chambreService,UserService $userService)
    {
        $this->campusService = $campusService;
        $this->citeService = $citeService;
        $this->batimentService = $batimentService;
        $this->chambreService = $chambreService;
        $this->userService = $userService;

    }

    /**
     * Vue d'ensemble du parc (Inertia) : Cités → Bâtiments d'une cité (?cite=) → Chambres d'un bâtiment (?batiment=).
     * 10 lignes par page côté serveur ; les actions (création, modification, statut, suppression) sont inchangées.
     * L'ancienne vue Blade `admin.hebergement.parc-immobilier.index` est conservée pour retour arrière.
     */
    public function index(Request $request, AdminAccess $access)
    {
        $admin = $request->user('admin');
        // Statuts réellement présents sur les cités : proposés dans le filtre « Toutes les cités ».
        $statutsCite = Cite::query()->whereNotNull('statut')->where('statut', '!=', '')
            ->distinct()->orderBy('statut')->pluck('statut')->values()->all();

        $batiment = $request->filled('batiment') ? Batiment::find($request->query('batiment')) : null;
        $cite = $batiment
            ? Cite::find($batiment->cite_id)
            : ($request->filled('cite') ? Cite::find($request->query('cite')) : null);
        $niveau = $batiment ? 'chambre' : ($cite ? 'batiment' : 'cite');

        // Statuts réellement présents sur les bâtiments de la cité ouverte : proposés dans le filtre « Tous les bâtiments ».
        $statutsBatiment = $niveau === 'batiment'
            ? Batiment::query()->where('cite_id', $cite->id_cite)->whereNotNull('statut')->where('statut', '!=', '')
                ->distinct()->orderBy('statut')->pluck('statut')->values()->all()
            : [];
        $statutsNiveau = $niveau === 'cite' ? $statutsCite : $statutsBatiment;

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'etat' => in_array($request->query('etat'), self::ETATS_CHAMBRE, true) ? $request->query('etat') : '',
            'statut' => in_array($request->query('statut'), $statutsNiveau, true) ? $request->query('statut') : '',
        ];

        $can = [
            'create' => $access->allows($admin, 'admin.hebergement.parc-immobilier.create'),
            'updateChambre' => $access->allows($admin, 'admin.hebergement.parc-immobilier.updateChambre'),
            'toggle' => $access->allows($admin, 'admin.hebergement.parc-immobilier.toggle-status'),
            'destroy' => $access->allows($admin, 'admin.hebergement.parc-immobilier.destroy'),
        ];

        $plan = [];
        $items = match ($niveau) {
            'cite' => $this->pageCites($filters['q'], $filters['statut'], $can),
            'batiment' => $this->pageBatiments($cite, $filters['q'], $filters['statut'], $can),
            'chambre' => $this->pageChambres($batiment, $filters, $can, $plan),
        };

        $etats = $this->chambresValides()->selectRaw('etat, count(*) as total')->groupBy('etat')->pluck('total', 'etat');
        $resume = fn (array $batimentIds) => $this->agregerChambres(
            $this->chambresValides()->whereIn('batiment_id', $batimentIds)
                ->get(['id_chambre', 'batiment_id', 'etat', 'capacite_max', 'type_occupation_id']),
            fn () => 'tout'
        )->get('tout', self::AGREGAT_VIDE);

        // Depuis une cité ou un bâtiment ouvert, le formulaire d'ajout présélectionne ce contexte.
        $createUrl = fn (string $type) => $can['create'] ? route('admin.hebergement.parc-immobilier.create', array_filter([
            'type' => $type,
            'cite' => $type !== 'cite' ? $cite?->id_cite : null,
            'batiment' => $type === 'chambre' ? $batiment?->id_batiment : null,
        ])) : null;

        return Inertia::render('Admin/Hebergement/ParcImmobilier/Index', [
            'niveau' => $niveau,
            'items' => $items,
            'plan' => $plan,
            'filters' => $filters,
            'statutsCite' => $statutsCite,
            'statutsBatiment' => $statutsBatiment,
            'contexte' => [
                'cite' => $cite ? [
                    'id' => $cite->id_cite,
                    'name' => $cite->name,
                    'statut' => $cite->statut,
                    'resume' => $batiment ? null : $resume(Batiment::where('cite_id', $cite->id_cite)->pluck('id_batiment')->all()),
                ] : null,
                'batiment' => $batiment ? [
                    'id' => $batiment->id_batiment,
                    'name' => $batiment->name,
                    'statut' => $batiment->statut,
                    'resume' => $resume([$batiment->id_batiment]),
                ] : null,
            ],
            'stats' => [
                'cites' => Cite::count(),
                'batiments' => Batiment::count(),
                'chambres' => (int) $etats->sum(),
                'disponibles' => (int) ($etats['disponible'] ?? 0),
                'occupees' => (int) ($etats['occupee'] ?? 0),
                'reservees' => (int) ($etats['reservee'] ?? 0),
                'maintenance' => (int) (($etats['maintenance'] ?? 0) + ($etats['hors_service'] ?? 0)),
                'places' => (int) $this->chambresValides()->sum('capacite_max'),
                'occupants' => AssignationChambre::actives()->whereIn('chambre_id', $this->chambresValides()->select('id_chambre'))->count(),
            ],
            'typesChambre' => TypeChambre::orderBy('name')->get(['id_type_chambre', 'name'])
                ->map(fn ($type) => ['id' => (string) $type->id_type_chambre, 'name' => $type->name]),
            'urls' => [
                'hebergement' => route('admin.hebergement.index'),
                'updateChambre' => $can['updateChambre'] ? route('admin.hebergement.parc-immobilier.updateChambre') : null,
                'map' => $access->allows($admin, 'admin.map') ? route('admin.map') : null,
                'equipements' => $access->allows($admin, 'admin.hebergement.equipements.affectations.index')
                    ? route('admin.hebergement.equipements.affectations.index') : null,
                'create' => array_filter([
                    'cite' => $createUrl('cite'),
                    'batiment' => $createUrl('batiment'),
                    'chambre' => $createUrl('chambre'),
                ]),
            ],
        ]);
    }

    private const ETATS_CHAMBRE = ['disponible', 'occupee', 'reservee', 'maintenance', 'hors_service'];

    private const AGREGAT_VIDE = [
        'chambres' => 0, 'disponibles' => 0, 'occupees' => 0, 'reservees' => 0, 'maintenance' => 0,
        'places' => 0, 'occupants' => 0, 'categories' => [],
    ];

    /** Chambres réellement configurées (numéro et type renseignés), comme `ChambreRepository::getAll()`. */
    private function chambresValides()
    {
        return Chambre::query()->whereNotNull('numero_chambre')->whereNotNull('type_chambre_id');
    }

    /**
     * Compteurs de chambres et d'occupants regroupés par `$cle` (cité, bâtiment…).
     * Les occupants sont les affectations actives, classés par type d'occupation de la chambre.
     */
    private function agregerChambres(\Illuminate\Support\Collection $chambres, callable $cle): \Illuminate\Support\Collection
    {
        $types = TypeCite::pluck('name', 'id_type_cite');
        $occupants = $chambres->isEmpty() ? collect() : AssignationChambre::actives()
            ->whereIn('chambre_id', $chambres->pluck('id_chambre')->all())
            ->selectRaw('chambre_id, count(*) as total')
            ->groupBy('chambre_id')
            ->pluck('total', 'chambre_id');
        $nb = fn ($chambre) => (int) ($occupants[$chambre->id_chambre] ?? 0);

        return $chambres->groupBy($cle)->map(fn ($groupe) => [
            'chambres' => $groupe->count(),
            'disponibles' => $groupe->where('etat', 'disponible')->count(),
            'occupees' => $groupe->where('etat', 'occupee')->count(),
            'reservees' => $groupe->where('etat', 'reservee')->count(),
            'maintenance' => $groupe->whereIn('etat', ['maintenance', 'hors_service'])->count(),
            'places' => (int) $groupe->sum('capacite_max'),
            'occupants' => (int) $groupe->sum($nb),
            'categories' => $groupe->groupBy(fn ($chambre) => $types[$chambre->type_occupation_id] ?? 'Non catégorisé')
                ->map(fn ($chambres) => (int) $chambres->sum($nb))
                ->filter()
                ->all(),
        ]);
    }

    private function contact($user): ?string
    {
        $contact = trim(($user?->name ?? '') . ' - ' . ($user?->telephone_principal ?? ''), ' -');

        return $contact !== '' ? $contact : null;
    }

    /** Liens d'action d'un élément, envoyés seulement si l'administrateur y a droit. */
    private function urlsElement(string $type, string $id, array $can): array
    {
        $params = ['type' => $type, 'code' => $id];

        return [
            // Les chambres se modifient dans une fenêtre (updateChambre), les autres dans le formulaire existant.
            'edit' => $type !== 'chambre' && $can['create']
                ? route('admin.hebergement.parc-immobilier.create', ['type' => $type, 'edit' => 1, 'id' => $id])
                : null,
            'status' => $can['toggle'] ? route('admin.hebergement.parc-immobilier.toggle-status', $params) : null,
            'destroy' => $can['destroy'] ? route('admin.hebergement.parc-immobilier.destroy', $params) : null,
        ];
    }

    private function pageCites(string $search, string $statut, array $can)
    {
        $cites = Cite::query()
            ->with(['responsable', 'typeCite', 'campus'])
            ->withCount([
                'batiment as batiments_total',
                'batiment as batiments_desactives' => fn ($query) => $query->where('statut', 'desactive'),
            ])
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('code_cite', 'like', "%{$search}%")
                ->orWhere('adresse', 'like', "%{$search}%")))
            ->when($statut !== '', fn ($query) => $query->where('statut', $statut))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $citeDuBatiment = Batiment::whereIn('cite_id', $cites->getCollection()->pluck('id_cite')->all())->pluck('cite_id', 'id_batiment');
        $agregats = $this->agregerChambres(
            $this->chambresValides()->whereIn('batiment_id', $citeDuBatiment->keys()->all())
                ->get(['id_chambre', 'batiment_id', 'etat', 'capacite_max', 'type_occupation_id']),
            fn ($chambre) => $citeDuBatiment[$chambre->batiment_id] ?? null
        );

        return $cites->through(fn (Cite $cite) => [
            'id' => $cite->id_cite,
            'type' => 'cite',
            'name' => $cite->name,
            'code' => $cite->code_cite,
            'statut' => $cite->statut,
            'occupation' => $cite->typeCite?->name,
            'responsable' => $this->contact($cite->responsable),
            'responsable_nom' => $cite->responsable?->name ?: null,
            'localisation' => $cite->adresse ?: $cite->campus?->name,
            'batiments' => (int) $cite->batiments_total,
            'batiments_desactives' => (int) $cite->batiments_desactives,
            'resume' => $agregats->get($cite->id_cite, self::AGREGAT_VIDE),
            'urls' => $this->urlsElement('cite', $cite->id_cite, $can),
        ]);
    }

    private function pageBatiments(Cite $cite, string $search, string $statut, array $can)
    {
        $batiments = Batiment::query()
            ->with(['responsable', 'typeOccupation'])
            ->where('cite_id', $cite->id_cite)
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->when($statut !== '', fn ($query) => $query->where('statut', $statut))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $chambres = $this->chambresValides()
            ->whereIn('batiment_id', $batiments->getCollection()->pluck('id_batiment')->all())
            ->get(['id_chambre', 'batiment_id', 'etat', 'capacite_max', 'type_occupation_id', 'niveau_aile_code']);
        $agregats = $this->agregerChambres($chambres, fn ($chambre) => $chambre->batiment_id);
        // Niveaux lus dans la structure (comme le formulaire de modification), pas déduits des chambres configurées :
        // un étage dont les chambres restent à configurer compte quand même.
        $niveauxParBatiment = Niveau_Aile_Batiment::query()
            ->join('aile_batiment', 'aile_batiment.code_aile', '=', 'niveau_aile_batiment.code_aile')
            ->whereIn('aile_batiment.batiment_id', $batiments->getCollection()->pluck('id_batiment')->all())
            ->selectRaw('aile_batiment.batiment_id, count(distinct niveau_aile_batiment.niveau_numero) as total')
            ->groupBy('aile_batiment.batiment_id')
            ->pluck('total', 'batiment_id');

        return $batiments->through(function (Batiment $batiment) use ($cite, $agregats, $niveauxParBatiment, $can) {
            $niveaux = (int) ($niveauxParBatiment[$batiment->id_batiment] ?? 0);

            return [
                'id' => $batiment->id_batiment,
                'type' => 'batiment',
                'name' => $batiment->name,
                'cite' => $cite->name,
                'statut' => $batiment->statut,
                'occupation' => $batiment->typeOccupation?->name,
                'responsable' => $this->contact($batiment->responsable),
                'responsable_nom' => $batiment->responsable?->name ?: null,
                'localisation' => $cite->adresse ?: null,
                'niveaux' => $niveaux ?: (int) $batiment->total_etages,
                'resume' => $agregats->get($batiment->id_batiment, self::AGREGAT_VIDE),
                'urls' => $this->urlsElement('batiment', $batiment->id_batiment, $can),
            ];
        });
    }

    /** Chambres paginées pour le tableau ; `$plan` reçoit toutes les chambres du bâtiment (vue plan par niveau). */
    private function pageChambres(Batiment $batiment, array $filters, array $can, array &$plan)
    {
        $base = fn () => $this->chambresValides()
            ->with(['typeChambre', 'typeOccupation', 'niveau_aile'])
            ->where('batiment_id', $batiment->id_batiment);

        $toutes = $base()->orderBy('numero_chambre')->get();
        $etudiants = AssignationChambre::actives()
            ->with('etudiant')
            ->whereIn('chambre_id', $toutes->pluck('id_chambre')->all())
            ->get()
            ->groupBy('chambre_id');

        $ligne = fn (Chambre $chambre) => [
            'id' => $chambre->id_chambre,
            'type' => 'chambre',
            'name' => $chambre->numero_chambre,
            'etat' => $chambre->etat,
            'statut' => $chambre->statut,
            'type_chambre' => $chambre->typeChambre?->name,
            'type_chambre_id' => $chambre->type_chambre_id !== null ? (string) $chambre->type_chambre_id : '',
            'occupation' => $chambre->typeOccupation?->name,
            'niveau' => $chambre->niveau_aile?->niveau_numero,
            'capacite' => (int) $chambre->capacite_max,
            'nb_lits' => (int) $chambre->nb_lits,
            'superficie' => $chambre->superficie !== null ? (float) $chambre->superficie : null,
            'prix' => (int) $chambre->prix,
            'niveau_confort' => $chambre->niveau_confort,
            'pmr' => (bool) $chambre->pmr,
            'etudiants' => $etudiants->get($chambre->id_chambre, collect())
                ->map(fn ($assignation) => [
                    'nom' => $assignation->etudiant?->name ?? 'Étudiant inconnu',
                    'matricule' => $assignation->etudiant?->matricule,
                ])->values()->all(),
            'urls' => $this->urlsElement('chambre', $chambre->id_chambre, $can),
        ];

        $plan = $toutes->map($ligne)->values()->all();

        return $base()
            ->when($filters['etat'] !== '', fn ($query) => $query->where('etat', $filters['etat']))
            ->when($filters['q'] !== '', fn ($query) => $query->where('numero_chambre', 'like', "%{$filters['q']}%"))
            ->orderBy('numero_chambre')
            ->paginate(10)
            ->withQueryString()
            ->through($ligne);
    }

    /**
     * Formulaires d'ajout / de modification (Inertia, en plusieurs étapes) : cité, bâtiment, configuration des chambres.
     * Les actions d'enregistrement restent `storeCite` / `updateCite`, `storeBatiment` / `updateBatiment`,
     * `massCreateChambres` / `quickCreateChambre` / `updateChambre`. Le campus garde l'ancien formulaire Blade.
     */
    public function create(Request $request, AdminAccess $access)
    {
        if ($request->query('type') === 'campus') {
            return $this->createCampusBlade($request);
        }

        $type = in_array($request->query('type'), ['cite', 'batiment', 'chambre'], true) ? $request->query('type') : null;
        $admin = $request->user('admin');
        $editMode = $request->boolean('edit') && $request->filled('id') && in_array($type, ['cite', 'batiment'], true);
        $url = fn (string $route) => $access->allows($admin, $route) ? route($route) : null;

        $item = null;
        if ($editMode) {
            $item = $type === 'cite'
                ? $this->citeFormulaire($this->citeService->findById($request->query('id')))
                : $this->batimentFormulaire(Batiment::find($request->query('id')));
            abort_if($item === null, 404);
        }

        $props = [
            'type' => $type,
            'mode' => $editMode ? 'edit' : 'create',
            'item' => $item,
            'campusId' => $this->campusId(),
            'typesCite' => TypeCite::orderBy('name')->get(['id_type_cite', 'name'])
                ->map(fn ($t) => ['id' => (string) $t->id_type_cite, 'name' => $t->name])->values(),
            'cites' => Cite::orderBy('name')->get(['id_cite', 'name', 'type_cite_id', 'statut'])
                ->map(fn ($c) => [
                    'id' => (string) $c->id_cite,
                    'name' => $c->name,
                    'type_cite_id' => $c->type_cite_id,
                    'actif' => $c->statut !== 'desactive',
                ])->values(),
            'selection' => [
                'cite' => $request->query('cite'),
                'batiment' => $request->query('batiment'),
            ],
            'urls' => [
                'hebergement' => route('admin.hebergement.index'),
                'index' => route('admin.hebergement.parc-immobilier.index'),
                'create' => [
                    'cite' => route('admin.hebergement.parc-immobilier.create', ['type' => 'cite']),
                    'batiment' => route('admin.hebergement.parc-immobilier.create', ['type' => 'batiment']),
                    'chambre' => route('admin.hebergement.parc-immobilier.create', ['type' => 'chambre']),
                ],
                'submit' => match ($type) {
                    'cite' => $url($editMode ? 'admin.hebergement.parc-immobilier.updateCite' : 'admin.hebergement.parc-immobilier.storeCite'),
                    'batiment' => $url($editMode ? 'admin.hebergement.parc-immobilier.updateBatiment' : 'admin.hebergement.parc-immobilier.storeBatiment'),
                    default => null,
                },
                'chambresBatiment' => $url('admin.hebergement.parc-immobilier.getChambresByBatiment'),
                'chambresNiveau' => $url('admin.hebergement.parc-immobilier.getChambresByNiveau'),
                'massCreate' => $url('admin.hebergement.parc-immobilier.massCreateChambres'),
                'quickCreate' => $url('admin.hebergement.parc-immobilier.quickCreateChambre'),
                'updateChambre' => $url('admin.hebergement.parc-immobilier.updateChambre'),
            ],
        ];

        if (in_array($type, ['cite', 'batiment'], true)) {
            // Mêmes informations que l'ancien formulaire (coordonnées reprises dans les contacts de la cité).
            $props['users'] = \App\Models\User::query()->orderBy('name')
                ->get(['id_user', 'name', 'matricule', 'telephone_principal', 'telephone_secondaire', 'email', 'email_secondaire'])
                ->map(fn ($u) => [
                    'id' => (string) $u->id_user,
                    'name' => $u->name,
                    'matricule' => $u->matricule,
                    'telephone' => $u->telephone_principal,
                    'telephone2' => $u->telephone_secondaire,
                    'email' => $u->email,
                    'email2' => $u->email_secondaire,
                ])->values();
        }

        if ($type === 'cite') {
            $props['reglements'] = ReglementInterieur::all()
                ->map(fn ($r) => ['id' => (string) $r->id_reglement, 'name' => $r->titre])->values();
        }

        if ($type === 'chambre') {
            $props['batiments'] = Batiment::orderBy('name')
                ->get(['id_batiment', 'name', 'cite_id', 'type_occupation_id', 'type_architecture', 'statut'])
                ->map(fn ($b) => [
                    'id' => (string) $b->id_batiment,
                    'name' => $b->name,
                    'cite_id' => $b->cite_id,
                    'type_occupation_id' => $b->type_occupation_id,
                    'type_architecture' => $b->type_architecture,
                    'actif' => $b->statut !== 'desactive',
                ])->values();
            $props['typesChambre'] = TypeChambre::orderBy('name')->get()
                ->map(fn ($t) => [
                    'id' => (string) $t->id_type_chambre,
                    'name' => $t->name,
                    'montant' => (float) ($t->montant ?? 0),
                    'capacite' => TypeChambre::capaciteChambre($t->id_type_chambre),
                    'equipements' => $this->equipementsType($t->equipement),
                ])->values();
        }

        return Inertia::render('Admin/Hebergement/ParcImmobilier/Form', $props);
    }

    /** Équipements par défaut d'un type de chambre (JSON `{"Chaise":"Chaise", …}` ou liste). */
    private function equipementsType($equipement): array
    {
        $decoded = is_array($equipement) ? $equipement : json_decode((string) $equipement, true);

        return is_array($decoded)
            ? array_values(array_filter(array_map('strval', $decoded), fn ($e) => $e !== ''))
            : [];
    }

    private function citeFormulaire(?Cite $cite): ?array
    {
        if (!$cite) {
            return null;
        }

        return [
            'id' => (string) $cite->id_cite,
            'name' => $cite->name,
            'code_cite' => $cite->code_cite,
            'type_cite' => $cite->type_cite_id,
            'capacite_max' => $cite->capacite_max,
            'nombre_batiments' => $cite->nombre_batiments,
            'superficie' => $cite->superficie,
            'annee_construction' => $cite->annee_construction,
            'statut' => $cite->statut,
            'responsable_id' => $cite->responsable_id,
            'telephone1' => $cite->telephone1,
            'telephone2' => $cite->telephone2,
            'telephone3' => $cite->telephone3,
            'email1' => $cite->email1,
            'email2' => $cite->email2,
            'reglement_id' => $cite->reglement_interieur_id,
            'adresse' => $cite->adresse,
            'latitude' => $cite->latitude,
            'longitude' => $cite->longitude,
            'wifi_gratuit' => (bool) $cite->wifi_gratuit,
            'salle_etude' => (bool) $cite->salle_etude,
            'infirmerie' => (bool) $cite->infirmerie,
            'restaurant' => (bool) $cite->restaurant,
            'description' => $cite->description,
            'plan' => $cite->plan ? asset($cite->plan) : null,
        ];
    }

    private function batimentFormulaire(?Batiment $batiment): ?array
    {
        if (!$batiment) {
            return null;
        }

        return [
            'id' => (string) $batiment->id_batiment,
            'name' => $batiment->name,
            'code_cite' => $batiment->cite_id,
            'type_occupation' => $batiment->type_occupation_id,
            'responsable_id' => $batiment->responsable_id,
            'etat_initial' => $batiment->etat_initial,
            'type_architecture' => $batiment->type_architecture,
            'latitude' => $batiment->latitude,
            'longitude' => $batiment->longitude,
            'acces_handicape' => (bool) $batiment->services_acces_handicape,
            'ascenseur' => (bool) $batiment->services_ascenseur,
            'gardiennage' => (bool) $batiment->services_gardiennage,
            'description' => $batiment->description,
            'total_chambres' => (int) $batiment->total_chambres,
            'total_etages' => (int) $batiment->total_etages,
            'total_ailes' => (int) $batiment->total_ailes,
            'plan' => $batiment->plan_file ? asset($batiment->plan_file) : null,
            'structure' => Batiment::structureExistante($batiment),
        ];
    }

    /** Ancien formulaire Blade, conservé pour le campus (non migré). */
    private function createCampusBlade(Request $request)
    {
        $editMode = $request->query('edit');
        $itemCode = $request->query('id');

        return view('admin.hebergement.parc-immobilier.create', [
            'type_chambre' => TypeChambre::all(),
            'selectedType' => 'campus',
            'type_cites' => TypeCite::all(),
            'campus' => $this->campusService->getCampus($this->campusId()),
            'regions' => Region::all(),
            'editMode' => $editMode,
            'itemToEdit' => $editMode && $itemCode ? Campus::where('code', $itemCode)->first() : null,
            'itemCode' => $itemCode,
            'cites' => $this->citeService->getCites(),
            'batimentSelectionne' => null,
            'chambresExistantes' => collect(),
            'mode' => 'creation',
            'users' => $this->userService->getAllUsers(),
            'reglements' => ReglementInterieur::all(),
        ]);
    }

    public function storeCampus(Request $request)
    {
        try {
            $data = $request->all();

            $this->campusService->createCampus($data);


            return back()->with('success', 'Campus créé avec succès !');

        }catch (\Exception $e){
            return back()->withErrors(['error' => 'Probleme survenu lors de l\'enregistrement']);
        }


    }


    public function updateCampus(Request $request)
    {
        try {
            $data = $request->all();

            $this->campusService->updateCampus($request['code'],  $data);


            return redirect()
                ->route('admin.hebergement.parc-immobilier.index')
                ->with('success', 'Campus modifié avec succès !');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Problème survenu lors de la modification : ' . \App\Support\UserFacingErrors::caught($e));
        }
    }



    public function storeCite(Request $request)
    {
        $this->validerCite($request);

        try {
            $this->citeService->createCite($request->all());
            return back()->with('success', 'Cité créée avec succès !');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Problème survenu lors de l\'enregistrement : ' . \App\Support\UserFacingErrors::caught($e)]);
        }
    }

    public function updateCite(Request $request)
    {
        $this->validerCite($request, (string) $request->input('code'));

        try {
            $cite = $this->citeService->updateCite($request->input('code'), $request->all());
            if ($request->boolean('remove_plan') && !$request->hasFile('plan')) {
                $cite->update(['plan' => null]);
            }
            return redirect()
                ->route('admin.hebergement.parc-immobilier.index')
                ->with('success', 'Cité modifiée avec succès !');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Problème survenu lors de la modification : ' . \App\Support\UserFacingErrors::caught($e));
        }
    }

    /**
     * Règles de la cité : mêmes champs obligatoires que l'ancien formulaire (nom, code, type, capacité, adresse, GPS).
     * Les erreurs sont renvoyées par champ : le formulaire en plusieurs étapes revient sur l'étape concernée.
     */
    private function validerCite(Request $request, ?string $id = null): void
    {
        // Téléphones saisis par paires (« 07 00 00 00 00 ») : enregistrés sans espaces.
        foreach (['telephone1', 'telephone2', 'telephone3'] as $champ) {
            if (is_string($request->input($champ))) {
                $request->merge([$champ => preg_replace('/\s+/', '', $request->input($champ))]);
            }
        }

        $request->validate([
            'code' => $id !== null ? 'required|exists:cite,id_cite' : 'nullable',
            'campus_id' => 'required|string',
            'name' => 'required|string|max:255',
            'code_cite' => ['required', 'string', 'max:50', \Illuminate\Validation\Rule::unique('cite', 'code_cite')->ignore($id, 'id_cite')],
            'type_cite' => 'required|exists:type_cite,id_type_cite',
            'capacite_max' => 'required|integer|min:0',
            'nombre_batiments' => 'nullable|integer|min:0',
            'superficie' => 'nullable|numeric|min:0',
            'annee_construction' => 'nullable|integer|between:1900,' . date('Y'),
            'statut' => 'nullable|in:ouvert,ferme,en_renovation,en_construction',
            'responsable_id' => 'nullable|exists:users,id_user',
            'telephone1' => 'nullable|string|max:20',
            'telephone2' => 'nullable|string|max:20',
            'telephone3' => 'nullable|string|max:20',
            'email1' => 'nullable|email|max:255',
            'email2' => 'nullable|email|max:255',
            'reglement_id' => 'nullable|string|max:150',
            'adresse' => 'required|string|max:500',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'plan' => 'nullable|file|mimes:pdf,png,jpg,jpeg|max:10240',
        ], [], [
            'name' => 'nom de la cité',
            'code_cite' => 'code de la cité',
            'type_cite' => 'type de cité',
            'capacite_max' => 'capacité',
            'nombre_batiments' => 'nombre de bâtiments',
            'annee_construction' => 'année de construction',
            'responsable_id' => 'responsable',
            'telephone1' => 'téléphone 1',
            'telephone2' => 'téléphone 2',
            'telephone3' => 'téléphone 3',
            'email1' => 'e-mail 1',
            'email2' => 'e-mail 2',
        ]);
    }

    public function storeBatiment(Request $request)
    {
        try {

            // Valider la requête
            $validator = Validator::make($request->all(), [
                'batiment_data' => 'required|json',
                'plan_file'     => 'nullable|file|mimes:png,jpg,jpeg,pdf|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides.',
                    'errors'  => $validator->errors()
                ], 422);
            }

            // Décoder les données JSON
            $batimentData    = json_decode($request->batiment_data, true);
            $model_batiment  = new Batiment();

            if ($refus = $this->validerBatiment($batimentData, null)) {
                return $refus;
            }



            // Gérer le fichier uploadé
            $planFilePath = null;
            if ($request->hasFile('plan_file')) {
                $planFilePath = upload( 'batiment',  'png', $request, 'plan_file');
            }

            // Vérifier l'existence du campus
            $campus = Campus::where('id_campus', $batimentData['code_campus'])->first();
            if (!$campus) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campus non trouvé.'
                ], 404);
            }

            // Vérifier l'existence de la cité
            $cite = Cite::where('id_cite', $batimentData['code_cite'])
                ->where('campus_id', $campus->id_campus)
                ->first();

            if (!$cite) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cité universitaire non trouvée pour ce campus.'
                ], 404);
            }

            // ✅ Calculer le total des chambres depuis les étages envoyés par le frontend
            //    (NE PAS mettre de valeur en dur ici)
            $totalChambres = 0;
            if (isset($batimentData['structure_data']['etages'])) {
                // Structure simple : sommer les chambres de chaque étage
                foreach ($batimentData['structure_data']['etages'] as $etage) {
                    $totalChambres += (int)($etage['chambres'] ?? 0);
                }
            } elseif (isset($batimentData['structure_data']['ailes'])) {
                // Structure complexe : sommer les chambres de chaque aile/étage
                foreach ($batimentData['structure_data']['ailes'] as $aile) {
                    foreach ($aile['etages'] ?? [] as $etage) {
                        $totalChambres += (int)($etage['total_chambres'] ?? 0);
                    }
                }
            }
            // Utiliser le total calculé, ou celui envoyé par le frontend si déjà calculé
            $batimentData['total_chambres'] = $totalChambres > 0
                ? $totalChambres
                : (int)($batimentData['total_chambres'] ?? 0);

            $latitude  = isset($batimentData['latitude'])  ? (float)$batimentData['latitude']  : null;
            $longitude = isset($batimentData['longitude']) ? (float)$batimentData['longitude'] : null;

            DB::beginTransaction();
            $batiment = null;

            try {
                // Créer le bâtiment
                $batiment = Batiment::firstOrCreate(
                    [
                        'cite_id'   => $batimentData['code_cite'],
                      //  'campus_id' => $batimentData['campus_id'],
                        'name'        => $batimentData['name'],
                    ],
                    [
                       // 'code_batiment'          => generate('batiment', 'code_batiment', 150, 'a-zA-Z0-9'),
                        'type_architecture'      => $batimentData['type_architecture'],
                        'type_occupation_id'        => $batimentData['type_occupation'],
                        'etat_initial'           => $batimentData['etat_initial'],
                        'description'            => $batimentData['description'] ?? null,
                        'plan_file'              => $planFilePath,
                        'responsable_id'         => $batimentData['responsable_id'] ?? null,
                        'total_chambres'         => $batimentData['total_chambres'], // ✅ valeur calculée
                        'total_etages'           => $batimentData['total_etages']
                            ?? (($batimentData['structure_data']['nombre_etages'] ?? 0) + 1),
                        'total_ailes'            => $batimentData['total_ailes'] ?? 0,
                        'services_acces_handicape' => $batimentData['services']['acces_handicape'] ?? false,
                        'services_ascenseur'     => $batimentData['services']['ascenseur']    ?? false,
                        'services_gardiennage'   => $batimentData['services']['gardiennage']  ?? false,
                        'is_valid'               => $batimentData['is_valid'] ?? true,
                        'created_by'             => getInfoAdmin()->users->code,
                        'longitude'              => $longitude,
                        'latitude'               => $latitude,
                    ]
                );

                // Créer les ailes/niveaux selon le type d'architecture
                if ($batimentData['type_architecture'] === 'complexe'
                    && isset($batimentData['structure_data']['ailes'])) {
                    $model_batiment->createAilesAndNiveaux($batiment, $batimentData['structure_data']['ailes']);
                } else {
                    $model_batiment->createSimpleStructure($batiment, $batimentData);
                }

                // Créer les chambres
                $model_batiment->createChambres($batiment, $batimentData);

                // Mettre à jour les totaux du bâtiment
                $model_batiment->updateBatimentTotals($batiment);

                // Enregistrer dans l'historique
                $model_batiment->saveToHistorique($batiment, 'creation', $batimentData);

                DB::commit();

                session()->flash('success', 'Bâtiment enregistré avec succès!');

                return response()->json([
                    'success'       => true,
                    'message'       => 'Bâtiment enregistré avec succès!',
                    'batiment_id'   => $batiment->id_batiment,
                    'code_batiment' => $batiment->code_batiment,
                    'redirect'      => route('admin.hebergement.parc-immobilier.index', ['cite' => $batiment->cite_id])
                ], 201);

            } catch (\Throwable $e) {
                DB::rollBack();
                // Tables MyISAM : le rollback n'annule rien, on retire à la main le bâtiment créé et sa structure.
                if ($batiment?->wasRecentlyCreated) {
                    $ailes = Aile_Batiment::where('batiment_id', $batiment->id_batiment)->pluck('code_aile');
                    Chambre::where('batiment_id', $batiment->id_batiment)->forceDelete();
                    Niveau_Aile_Batiment::whereIn('code_aile', $ailes)->delete();
                    Aile_Batiment::where('batiment_id', $batiment->id_batiment)->delete();
                    $batiment->forceDelete();
                }

                // Le formulaire affiche le message ; pas de flash qui réapparaîtrait sur la page suivante.
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la création : ' . \App\Support\UserFacingErrors::caught($e)
                ], 500);
            }

        } catch (\Exception $e) {
            session()->flash('error', 'Erreur: ' . \App\Support\UserFacingErrors::caught($e));

            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . \App\Support\UserFacingErrors::caught($e)
            ], 500);
        }
    }

    public function updateBatiment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'code'         => 'required|exists:batiment,id_batiment',
                'batiment_data' => 'required|json',
                'plan_file'    => 'nullable|file|mimes:png,jpg,jpeg,pdf|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données de modification invalides.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = json_decode($request->input('batiment_data'), true, 512, JSON_THROW_ON_ERROR);
            $batiment = Batiment::findOrFail($request->input('code'));

            if ($refus = $this->validerBatiment($data, (string) $batiment->id_batiment)) {
                return $refus;
            }
            $planFilePath = $batiment->plan_file;

            if ($request->hasFile('plan_file')) {
                $planFilePath = upload('batiment', 'png', $request, 'plan_file');
            } elseif ($request->boolean('remove_plan')) {
                $planFilePath = null;
            }

            $batiment->update([
                'name' => $data['name'] ?? $batiment->name,
                'cite_id' => $data['code_cite'] ?? $batiment->cite_id,
                'type_architecture' => $data['type_architecture'] ?? $batiment->type_architecture,
                'type_occupation_id' => $data['type_occupation'] ?? $batiment->type_occupation_id,
                'etat_initial' => $data['etat_initial'] ?? $batiment->etat_initial,
                'description' => $data['description'] ?? $batiment->description,
                'plan_file' => $planFilePath,
                'responsable_id' => $data['responsable_id'] ?? null,
                'total_chambres' => $data['total_chambres'] ?? $batiment->total_chambres,
                'total_etages' => $data['total_etages'] ?? $batiment->total_etages,
                'total_ailes' => $data['total_ailes'] ?? $batiment->total_ailes,
                'services_acces_handicape' => $data['services']['acces_handicape'] ?? false,
                'services_ascenseur' => $data['services']['ascenseur'] ?? false,
                'services_gardiennage' => $data['services']['gardiennage'] ?? false,
                'longitude' => isset($data['longitude']) ? (float) $data['longitude'] : $batiment->longitude,
                'latitude' => isset($data['latitude']) ? (float) $data['latitude'] : $batiment->latitude,
                'updated_by' => getInfoAdmin()->users->id_user ?? null,
            ]);

            // Structure : nouveaux niveaux / ailes et chambres ajoutées (les totaux sont recalculés depuis la base).
            if (is_array($data['structure_data']['ailes'] ?? null)) {
                DB::transaction(fn () => Batiment::appliquerStructureModifiee($batiment->refresh(), $data['structure_data']['ailes']));
            }

            return response()->json([
                'success' => true,
                'message' => 'Bâtiment modifié avec succès.',
                'redirect' => route('admin.hebergement.parc-immobilier.index', ['cite' => $batiment->cite_id]),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification : ' . \App\Support\UserFacingErrors::caught($e),
            ], 500);
        }
    }


    /**
     * Retrait de niveaux d'une aile : uniquement les plus hauts (on retire toujours en commençant par le niveau supérieur),
     * et seulement si aucune de leurs chambres n'est occupée, réservée ou attribuée (proposition ou affectation en cours).
     * Renvoie le message de refus, ou null si le retrait est permis.
     */
    private function refusRetraitNiveaux(\Illuminate\Support\Collection $niveauxAile, array $retires, array $niveauxEnvoyes): ?string
    {
        $envoyes = collect($niveauxEnvoyes)->pluck('code')->filter()->all();
        $aRetirer = $niveauxAile->whereIn('niveau_aile_code', $retires);
        if ($aRetirer->count() !== count($retires) || array_intersect($retires, $envoyes)) {
            return "Un niveau à retirer n'appartient pas à ce bâtiment. Rechargez la page.";
        }

        $plusHautGarde = $niveauxAile->whereNotIn('niveau_aile_code', $retires)->max(fn ($n) => (int) $n->niveau_numero);
        if ($plusHautGarde !== null && $plusHautGarde > $aRetirer->min(fn ($n) => (int) $n->niveau_numero)) {
            return 'Retirez les niveaux en commençant par le niveau le plus haut.';
        }

        $chambres = Chambre::whereIn('niveau_aile_code', $retires);
        $occupee = (clone $chambres)->where(fn ($q) => $q->where('occupants_actuels', '>', 0)->orWhereIn('etat', ['occupee', 'reservee']))->exists()
            || AssignationChambre::mobilisantPlace()->whereIn('chambre_id', (clone $chambres)->pluck('id_chambre'))->exists();
        if ($occupee) {
            $niveau = $aRetirer->sortBy('niveau_numero')->first();
            $libelle = (int) $niveau->niveau_numero === 0 ? 'Le rez-de-chaussée' : "L'étage {$niveau->niveau_numero}";

            return "{$libelle} (ou un niveau au-dessus) a des chambres occupées ou attribuées : libérez-les avant de retirer le niveau.";
        }

        return null;
    }

    /**
     * Structure modifiée d'un bâtiment existant : on peut ajouter des niveaux, des ailes et des chambres, renommer une aile,
     * changer l'occupation d'un niveau ou retirer les niveaux supérieurs libres ; jamais diminuer les chambres d'un niveau gardé
     * ni changer l'occupation d'un niveau occupé.
     */
    private function validerStructureModifiee($validator, array $ailes, ?Batiment $batiment): void
    {
        if (!$batiment) {
            return;
        }
        $ailesExistantes = Aile_Batiment::where('batiment_id', $batiment->id_batiment)->get()->keyBy('code_aile');
        $niveauxExistants = Niveau_Aile_Batiment::whereIn('code_aile', $ailesExistantes->keys())->get()->keyBy('niveau_aile_code');
        $chambres = Batiment::chambresParNiveau($batiment);
        $erreur = fn (string $message) => $validator->errors()->add('structure_data', $message);

        foreach (array_values($ailes) as $aileData) {
            $codeAile = $aileData['code'] ?? null;
            if ($codeAile && !$ailesExistantes->has($codeAile)) {
                $erreur("Une aile envoyée n'appartient pas à ce bâtiment. Rechargez la page.");
                return;
            }
            if (mb_strlen(trim((string) ($aileData['nom'] ?? ''))) > 100) {
                $erreur("Le nom d'une aile ne doit pas dépasser 100 caractères.");
                return;
            }

            $niveauxAile = $codeAile ? $niveauxExistants->where('code_aile', $codeAile) : collect();
            $retires = array_values(array_unique(array_filter((array) ($aileData['niveaux_retires'] ?? []), 'is_string')));
            if ($retires && ($message = $this->refusRetraitNiveaux($niveauxAile, $retires, $aileData['niveaux'] ?? []))) {
                $erreur($message);
                return;
            }

            // Numéros encore utilisés dans l'aile : un nouveau niveau peut reprendre le numéro d'un niveau retiré.
            $numeros = $niveauxAile->whereNotIn('niveau_aile_code', $retires)->pluck('niveau_numero')->map(fn ($n) => (int) $n)->all();
            foreach ($aileData['niveaux'] ?? [] as $niveauData) {
                $voulues = $niveauData['chambres'] ?? null;
                if (!is_numeric($voulues) || (int) $voulues < 0 || (int) $voulues > 500) {
                    $erreur('Le nombre de chambres d’un niveau doit être compris entre 0 et 500.');
                    return;
                }
                $codeNiveau = $niveauData['code'] ?? null;
                if ($codeNiveau) {
                    $niveau = $niveauxExistants->get($codeNiveau);
                    if (!$niveau || $niveau->code_aile !== $codeAile) {
                        $erreur("Un niveau envoyé n'appartient pas à ce bâtiment. Rechargez la page.");
                        return;
                    }
                    $libelle = (int) $niveau->niveau_numero === 0 ? 'Le rez-de-chaussée' : "L'étage {$niveau->niveau_numero}";
                    $existantes = $chambres[$codeNiveau]['total'] ?? 0;
                    if ((int) $voulues < $existantes) {
                        $erreur("{$libelle} compte déjà {$existantes} chambre(s) : ce nombre ne peut pas être diminué ici.");
                        return;
                    }
                    $type = Batiment::typeOccupationNiveau($niveauData['type_occupation'] ?? null, $batiment);
                    if ($type !== $niveau->type_occupation && ($chambres[$codeNiveau]['occupees'] ?? 0) > 0) {
                        $erreur("{$libelle} a des chambres occupées : son type d'occupation ne peut pas être modifié.");
                        return;
                    }
                } else {
                    $numero = $niveauData['numero'] ?? null;
                    if (!is_numeric($numero) || (int) $numero < 0 || (int) $numero > 30 || in_array((int) $numero, $numeros, true)) {
                        $erreur('Le numéro d’un nouveau niveau est invalide ou déjà utilisé dans son aile.');
                        return;
                    }
                    $numeros[] = (int) $numero;
                }
            }
        }
    }

    /**
     * Contrôles serveur du bâtiment (le formulaire les applique déjà étape par étape) :
     * champs obligatoires, nom unique dans la cité, au moins une chambre à la création, et type d'occupation compatible avec la cité
     * (une cité « Uniquement Homme/Femme » n'accepte que des bâtiments du même type ; une cité mixte accepte tout).
     * Renvoie la réponse 422 `{success: false, message, errors}` à retourner, ou null si tout est valide.
     */
    private function validerBatiment(?array $data, ?string $batimentId): ?\Illuminate\Http\JsonResponse
    {
        $creation = $batimentId === null;
        $validator = Validator::make($data ?? [], [
            'code_cite' => 'required|exists:cite,id_cite',
            'name' => 'required|string|max:100',
            'type_occupation' => 'required|exists:type_cite,id_type_cite',
            'type_architecture' => ($creation ? 'required' : 'nullable') . '|in:simple,complexe',
            'etat_initial' => 'nullable|in:operationnel,renovation,en_construction,ferme',
            'responsable_id' => 'nullable|exists:users,id_user',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ], [], [
            'code_cite' => 'cité',
            'name' => 'nom du bâtiment',
            'type_occupation' => "type d'occupation",
            'type_architecture' => "type d'architecture",
            'etat_initial' => 'état initial',
            'responsable_id' => 'concierge',
        ]);

        $validator->after(function ($validator) use ($data, $creation, $batimentId) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $cite = Cite::with('typeCite')->find($data['code_cite']);
            $typeCite = $cite?->typeCite;
            if ($typeCite && mb_strtolower(trim($typeCite->name)) !== 'mixte' && $typeCite->id_type_cite !== $data['type_occupation']) {
                $validator->errors()->add('type_occupation', "La cité « {$cite->name} » est réservée au type « {$typeCite->name} » : le bâtiment doit avoir le même type d'occupation.");
            }
            // Nom unique dans la cité : `storeBatiment` utilise firstOrCreate(cité, nom) et recréerait la structure d'un bâtiment existant.
            $doublon = Batiment::where('cite_id', $data['code_cite'])->where('name', trim($data['name']))
                ->when($batimentId, fn ($q) => $q->where('id_batiment', '!=', $batimentId))->exists();
            if ($doublon) {
                $validator->errors()->add('name', 'Un bâtiment porte déjà ce nom dans cette cité.');
            }
            if (!$creation && is_array($data['structure_data']['ailes'] ?? null)) {
                $this->validerStructureModifiee($validator, $data['structure_data']['ailes'], Batiment::find($batimentId));
            }
            if ($creation) {
                $structure = $data['structure_data'] ?? [];
                $total = collect($structure['etages'] ?? [])->sum(fn ($e) => (int) ($e['chambres'] ?? 0))
                    + collect($structure['ailes'] ?? [])->sum(fn ($a) => collect($a['etages'] ?? [])->sum(fn ($e) => (int) ($e['total_chambres'] ?? 0)));
                if ($total < 1) {
                    $validator->errors()->add('structure_data', 'Indiquez au moins une chambre dans la structure du bâtiment.');
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        return null;
    }

    public function storeChambre(Request $request)
    {
      dd($request->all());

        try {
            DB::beginTransaction();

            $validated = $request->validate([
                'code' => 'required|exists:chambre,code_chambre',
                'numero_chambre' => 'required|string|max:250',
                'type_chambre_code' => 'required|exists:type_chambre,id_type_chambre',
            ]);

            // Récupérer la chambre partielle
            $chambre = Chambre::where('code_chambre', $validated['code'])->first();



            // Vérifier que le numéro de chambre n'existe pas déjà dans le même niveau
            $existe = Chambre::where('code_batiment', $chambre->code_batiment)
                ->where('niveau_aile_code', $chambre->niveau_aile_code)
                ->where('numero_chambre', $validated['numero_chambre'])
                ->where('code_chambre', '!=', $chambre->code_chambre)
                ->exists();

            if ($existe) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce numéro de chambre existe déjà pour ce niveau'
                ], 422);
            }



            // Mettre à jour la chambre avec les bons champs
            $chambre->update([

                'numero_chambre' => $validated['numero_chambre'],
                'type_chambre_code' => $validated['type_chambre_code'],
                'updated_by' => getInfoAdmin()->users->code ?? null,
                'updated_at' => now(),
            ]);

            DB::commit();

            // Charger la relation typeChambre pour la réponse
            $chambre->load('typeChambre');

            return back()->with('Succes','Chambre enregistree avec succes');

        } catch (\Exception $e) {
            DB::rollBack();


            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . \App\Support\UserFacingErrors::caught($e)
            ], 500);
        }


    }

    public function getChambresByBatiment(Request $request)
    {
        try {
            $batimentCode = $request->get('batiment_code');

            if (!$batimentCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code bâtiment manquant'
                ], 400);
            }

            // Récupérer le bâtiment
            $batiment = Batiment::where('id_batiment', $batimentCode)->first();

            if (!$batiment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bâtiment non trouvé'
                ], 404);
            }

            // Récupérer toutes les chambres
            $chambres = Chambre::where('batiment_id', $batiment->id_batiment)
                ->with('typeChambre')
                ->orderBy('niveau_aile_code')
                ->orderBy('numero_chambre')
                ->get();

            // Grouper par niveau_aile_code
            $chambresParNiveau = $chambres->groupBy('niveau_aile_code');

            // ✅ Récupérer les niveaux avec toutes leurs colonnes
            $niveauxExistants = Niveau_Aile_Batiment::with(['aile' => function($q) use ($batiment) {
                $q->where('batiment_id', $batiment->id_batiment);
            }])->whereHas('aile', function($q) use ($batiment) {
                $q->where('batiment_id', $batiment->id_batiment);
            })->get();

            $niveaux = [];

            if ($niveauxExistants->isNotEmpty()) {
                foreach ($niveauxExistants as $niveau) {
                    // ✅ Récupérer TOUTES les informations du niveau
                    $niveauNumero = $niveau->niveau_numero; // C'est 0 ou 1
                    $aile = $niveau->aile;
                    $aileName = $aile ? $aile->name : "Aile {$niveau->code_aile}";

                    // Compter les chambres pour ce niveau spécifique
                    $chambresNiveau = isset($chambresParNiveau[$niveau->niveau_aile_code])
                        ? $chambresParNiveau[$niveau->niveau_aile_code]
                        : collect();

                    $niveaux[] = [
                        'id_niveau_aile' => $niveau->id_niveau_aile,
                        'niveau_numero' => (int)$niveauNumero, // ✅ Forcer en entier (0 ou 1)
                        'niveau_aile_code' => $niveau->niveau_aile_code,
                        'code_aile' => $niveau->code_aile,
                        'aile_name' => $aileName,
                        'aile_numero' => $aile ? $aile->numero : null,
                        'niveau' => (int)$niveauNumero,
                        'code' => $niveau->niveau_aile_code,
                        'label' => $niveau->label ?? ($niveauNumero == 0 ? 'Rez-de-chaussée' : "Étage {$niveauNumero}"),
                        'type_occupation' => $niveau->type_occupation,
                        'total_chambres' => $niveau->total_chambres, // Le total stocké dans la table
                        'chambres_crees' => $chambresNiveau->filter(function($c) {
                            return !is_null($c->numero_chambre) && !is_null($c->type_chambre_id);
                        })->count(),
                        // Même critère que massCreateChambres : sans numéro = chambre encore à configurer.
                        'chambres_non_crees' => $chambresNiveau->filter(function($c) {
                            return is_null($c->numero_chambre);
                        })->count(),
                        'chambres_nord' => $niveau->chambres_nord,
                        'chambres_sud' => $niveau->chambres_sud,
                        'chambres_est' => $niveau->chambres_est,
                        'chambres_ouest' => $niveau->chambres_ouest
                    ];
                }

                // Trier par aile puis par niveau
                usort($niveaux, function($a, $b) {
                    if ($a['code_aile'] === $b['code_aile']) {
                        return $a['niveau_numero'] - $b['niveau_numero'];
                    }
                    return strcmp($a['code_aile'], $b['code_aile']);
                });
            }

            return response()->json([
                'success' => true,
                'batiment' => [
                    'code' => $batiment->id_batiment,
                    'name' => $batiment->name,
                    'type_architecture' => $batiment->type_architecture,
                    'total_chambres' => $batiment->total_chambres ?? $chambres->count(),
                    'total_etages' => $batiment->total_etages ?? $niveauxExistants->count(),
                    'total_ailes' => $batiment->total_ailes ?? 0,
                ],
                'etages' => $niveaux, // ✅ Maintenant avec tous les niveaux (0 et 1)
                'chambres_par_etage' => $chambresParNiveau,
                'chambres' => $chambres->map(function($chambre) {
                    return [
                        'code' => $chambre->id_chambre,
                        'numero_chambre' => $chambre->numero_chambre,
                        'niveau_code' => $chambre->niveau_aile_code,
                        'type_chambre_code' => $chambre->type_chambre_id ?? null,
                        'type_chambre_nom' => $chambre->typeChambre->name ?? 'Non défini',
                        'statut' => $chambre->etat,
                        'est_creee' => !is_null($chambre->numero_chambre) && !is_null($chambre->type_chambre_id)
                    ];
                })
            ]);

        } catch (\Exception $e) {


            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . \App\Support\UserFacingErrors::caught($e)
            ], 500);
        }
    }

    public function getChambresByNiveau(Request $request)
    {
        try {
            $niveauCode = $request->get('niveau_code');
            $batimentCode = $request->get('batiment_code');

            if (!$niveauCode || !$batimentCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code niveau ou bâtiment manquant'
                ], 400);
            }

            // Récupérer toutes les chambres de ce niveau
            $chambres = Chambre::where('niveau_aile_code', $niveauCode)
                ->where('batiment_id', $batimentCode)
                ->with('typeChambre')
                ->get();

            // ✅ CORRECTION : Séparer selon numero_chambre ET type_chambre_code
            $chambresCrees = $chambres->filter(function($chambre) {
                return !is_null($chambre->numero_chambre) &&
                    !is_null($chambre->type_chambre_id) &&
                    $chambre->numero_chambre !== '' &&
                    $chambre->type_chambre_id !== '';
            });

            $chambresNonCrees = $chambres->filter(function($chambre) {
                return is_null($chambre->numero_chambre) &&
                    is_null($chambre->type_chambre_id) ||
                    ($chambre->numero_chambre === '' && $chambre->type_chambre_id === '');
            });

            // Préparer les données pour les chambres créées
            $chambresCreesData = $chambresCrees->map(function($chambre) {
                return [
                    'code' => $chambre->id_chambre,
                    'numero' => $chambre->numero_chambre,
                    'type' => $chambre->typeChambre->name ?? 'Non défini',
                    'type_code' => $chambre->type_chambre_id,
                    'niveau_confort' => $chambre->niveau_confort,
                    'pmr' => $chambre->pmr,
                    'nb_lits' => $chambre->nb_lits,
                    'type_occupation_id' => $chambre->type_occupation_id,
                    'etat' => $chambre->etat,
                    'capacite' => $chambre->capacite_max,
                    'prix' => $chambre->prix_actuel ?? $chambre->prix,
                    'equipements' => $chambre->equipements,
                    'superficie' => $chambre->superficie
                ];
            })->values();

            // Préparer les données pour les chambres non créées
            $chambresNonCreesData = $chambresNonCrees->map(function($chambre) {
                return [
                    'code' => $chambre->id_chambre,
                    'code_aile' => $chambre->code_aile,
                    'code_batiment' => $chambre->batiment_id,
                    'niveau_aile_code' => $chambre->niveau_aile_code
                ];
            })->values();

            // Récupérer les informations du niveau
            $niveau = Niveau_Aile_Batiment::where('niveau_aile_code', $niveauCode)->first();

            return response()->json([
                'success' => true,
                'niveau' => [
                    'code' => $niveauCode,
                    'label' => $niveau ? $niveau->label :
                        (strpos($niveauCode, 'RDC') !== false ? 'Rez-de-chaussée' :
                            (preg_match('/(\d+)/', $niveauCode, $matches) ? "Étage {$matches[1]}" : $niveauCode)),
                    'total_chambres' => $chambres->count()
                ],
                'chambres_crees' => [
                    'count' => $chambresCrees->count(),
                    'items' => $chambresCreesData
                ],
                'chambres_non_crees' => [
                    'count' => $chambresNonCrees->count(),
                    'items' => $chambresNonCreesData
                ]
            ]);

        } catch (\Exception $e) {



            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . \App\Support\UserFacingErrors::caught($e)
            ], 500);
        }
    }

    public function quickCreateChambre(Request $request)
    {

        try {
            DB::beginTransaction();

            $validated = $request->validate([
                'code'             => 'required|exists:chambre,id_chambre',
                'numero_chambre'   => 'required|string|max:250',
                'type_chambre_code'=> 'required|exists:type_chambre,id_type_chambre',
                'niveau_confort' => 'nullable|string|max:250',
                'typeOccupation' => 'nullable|string|max:250',
                'pmr' => 'nullable|boolean',
                'nb_lits' =>'nullable|integer',
            ]);

            $chambre = Chambre::where('id_chambre', $validated['code'])->first();

            // Unicité du numéro dans le même niveau
            $existe = Chambre::where('batiment_id', $chambre->batiment_id)
                ->where('niveau_aile_code', $chambre->niveau_aile_code)
                ->where('numero_chambre', $validated['numero_chambre'])
                ->where('id_chambre', '!=', $chambre->id_chambre)
                ->exists();

            if ($existe) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce numéro de chambre existe déjà pour ce niveau'
                ], 422);
            }


            $capaciteChambre = TypeChambre::capaciteChambre($validated['type_chambre_code']);
            $cautionChambre = TypeChambre::cautionChambre($validated['type_chambre_code']);
            $avanceChambre = TypeChambre::avanceChambre($validated['type_chambre_code']);
            $equipementChambre = TypeChambre::equipementChambre($validated['type_chambre_code']);
            $montantChambre = TypeChambre::montantChambre($validated['type_chambre_code']);
            $superficieChambre = TypeChambre::superficieChambre($validated['type_chambre_code']);

            $chambre->update([
                'numero_chambre'    => $validated['numero_chambre'],
                'type_chambre_id' => $validated['type_chambre_code'],
                'superficie' => $superficieChambre,
                'niveau_confort' => $validated['niveau_confort'] ?? 'standard',
                'pmr' => $validated['pmr'] ?? false,
                'capacite_max' => $capaciteChambre,
                'prix' => $montantChambre,
                'nb_lits'  => $validated['nb_lits'],
                'type_occupation_id'        => $validated['typeOccupation'],
                'type_occupation_id' =>$this->typeOccupation($validated['typeOccupation'], $chambre->batiment_id),
                'avance' => $avanceChambre,
                'caution' => $cautionChambre,
                'equipements' => $equipementChambre,
                'updated_by'        => getInfoAdmin()->users->id_user ?? null,
                'updated_at'        => now(),
            ]);

            DB::commit();
            $chambre->load('typeChambre');

            // ✅ Toujours retourner du JSON, jamais de redirect
            return response()->json([
                'success' => true,
                'message' => 'Chambre créée avec succès',
                'chambre' => [
                    'code'              => $chambre->id_chambre,
                    'numero_chambre'    => $chambre->numero_chambre,
                    'type_chambre_code' => $chambre->type_chambre_code,
                    'type_chambre_nom'  => $chambre->typeChambre->name ?? '',
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . \App\Support\UserFacingErrors::caught($e)
            ], 500);
        }
    }



    public function quickCreateAllChambre(Request $request)
    {
        try {

            DB::beginTransaction();

            $validated = $request->validate([
                'code'             => 'required|exists:chambre,id_chambre',
                'numero_chambre'   => 'required|string|max:250',
                'type_chambre_code'=> 'required|exists:type_chambre,id_type_chambre',

                'niveau_confort' => 'nullable|string|max:250',
                'pmr' => 'nullable|boolean',

                'nb_lits' =>'nullable|integer',
            ]);

            $chambre = Chambre::where('id_chambre', $validated['code'])->first();

            // Unicité du numéro dans le même niveau
            $existe = Chambre::where('batiment_id', $chambre->batiment_id)
                ->where('niveau_aile_code', $chambre->niveau_aile_code)
                ->where('numero_chambre', $validated['numero_chambre'])
                ->where('id_chambre', '!=', $chambre->id_chambre)
                ->exists();

            if ($existe) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce numéro de chambre existe déjà pour ce niveau'
                ], 422);
            }

            $capaciteChambre = TypeChambre::capaciteChambre($validated['type_chambre_code']);
            $cautionChambre = TypeChambre::cautionChambre($validated['type_chambre_code']);
            $avanceChambre = TypeChambre::avanceChambre($validated['type_chambre_code']);
            $equipementChambre = TypeChambre::equipementChambre($validated['type_chambre_code']);
            $montantChambre = TypeChambre::montantChambre($validated['type_chambre_code']);
            $superficieChambre = TypeChambre::superficieChambre($validated['type_chambre_code']);

            $chambre->update([
                'numero_chambre'    => $validated['numero_chambre'],
                'type_chambre_code' => $validated['type_chambre_code'],
                'superficie' => $superficieChambre,
                'niveau_confort' => $validated['niveau_confort'] ?? 'standard',
                'pmr' => $validated['pmr'] ?? false,
                'capacite_max' => $capaciteChambre,
                'prix' => $montantChambre,
                'nb_lits'  => $validated['nb_lits'],
                'avance' => $avanceChambre,
                'caution' => $cautionChambre,
                'equipements' => $equipementChambre,
                'updated_by'        => getInfoAdmin()->users->id_user ?? null,
                'updated_at'        => now(),
            ]);

            DB::commit();
            $chambre->load('typeChambre');

            // ✅ Toujours retourner du JSON, jamais de redirect
            return response()->json([
                'success' => true,
                'message' => 'Chambre créée avec succès',
                'chambre' => [
                    'code'              => $chambre->id_chambre,
                    'numero_chambre'    => $chambre->numero_chambre,
                    'type_chambre_code' => $chambre->type_chambre_code,
                    'type_chambre_nom'  => $chambre->typeChambre->name ?? '',
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . \App\Support\UserFacingErrors::caught($e)
            ], 500);
        }
    }

    // public function massCreateChambres(Request $request)
    // {
    //     try {

    //         $request->validate([
    //             'niveau_code'                        => 'required|string',
    //             'batiment_code'                      => 'required|string',
    //             'configurations'                     => 'required|array|min:1',
    //             'configurations.*.type_chambre_code' => 'required|exists:type_chambre,id_type_chambre',
    //             'configurations.*.quantity'          => 'required|integer|min:1',
    //             'configurations.*.pmr'               => 'nullable|in:0,1',
    //             'configurations.*.nb_lits'           => 'nullable|integer|min:1|max:3',
    //             'configurations.*.prefix'            => 'nullable|string|max:20',
    //             'configurations.*.confort'            => 'nullable|string|max:20',
    //             'configurations.*.typeOccupation'            => 'nullable|string|max:150',
    //         ]);

    //         $niveauCode     = $request->input('niveau_code');
    //         $batimentCode   = $request->input('batiment_code');
    //         $configurations = $request->input('configurations');

    //         // Récupérer toutes les chambres non créées de ce niveau
    //         // (même logique que getChambresByNiveau)
    //         $chambresNonCrees = Chambre::where('batiment_id', $batimentCode)
    //             ->where('niveau_aile_code', $niveauCode)
    //             ->whereNull('numero_chambre')   // ou votre condition "non créée"
    //             ->orderBy('created_at')
    //             ->get();

    //         if ($chambresNonCrees->isEmpty()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Aucune chambre à créer dans ce niveau'
    //             ], 422);
    //         }

    //         DB::beginTransaction();

    //         $indexChambre  = 0; // pointeur dans la liste des chambres non créées
    //         $chambresCrees = [];
    //         $erreurs       = [];

    //         foreach ($configurations as $config) {
    //             $typeChambreCode = $config['type_chambre_code'];
    //             $quantity        = (int) $config['quantity'];
    //             $prefix          = $config['prefix'] ?? '';
    //             $pmr             = isset($config['pmr']) ? (bool)$config['pmr'] : false;
    //             $nbLits          = (int) ($config['nb_lits'] ?? 1);
    //             $typeOccupation  = $config['typeOccupation'] ?? '';
    //             $confort         = $config['confort'] ?? 'standard';

    //             // Récupérer les infos du type de chambre (même appels que quickCreateChambre)
    //             $capacite   = TypeChambre::capaciteChambre($typeChambreCode);
    //             $caution    = TypeChambre::cautionChambre($typeChambreCode);
    //             $avance     = TypeChambre::avanceChambre($typeChambreCode);
    //             $equipement = TypeChambre::equipementChambre($typeChambreCode);
    //             $montant    = TypeChambre::montantChambre($typeChambreCode);
    //             $superficie = TypeChambre::superficieChambre($typeChambreCode);

    //             for ($i = 1; $i <= $quantity; $i++) {

    //                 // Plus de chambres disponibles dans ce niveau
    //                 if ($indexChambre >= $chambresNonCrees->count()) {
    //                     $erreurs[] = "Plus de chambres disponibles dans ce niveau (arrêt à la config {$typeChambreCode}, chambre {$i})";
    //                     break 2;
    //                 }

    //                 $chambre = $chambresNonCrees[$indexChambre];

    //                 // Générer le numéro de porte
    //                 $numeroChambre = $this->genererNumeroChambre($prefix, $i);

    //                 // Vérifier l'unicité du numéro (même logique que quickCreateChambre)
    //                 $existe = Chambre::where('batiment_id', $chambre->batiment_id)
    //                     ->where('niveau_aile_code', $chambre->niveau_aile_code)
    //                     ->where('numero_chambre', $numeroChambre)
    //                     ->where('id_chambre', '!=', $chambre->id_chambre)
    //                     ->exists();

    //                 if ($existe) {
    //                     $erreurs[] = "Numéro '{$numeroChambre}' déjà utilisé dans ce niveau, chambre ignorée";
    //                     $indexChambre++;
    //                     continue;
    //                 }



    //                 // Mettre à jour la chambre (identique à quickCreateChambre)
    //                 $chambre->update([
    //                     'numero_chambre'    => $numeroChambre,
    //                     'type_chambre_id' => $typeChambreCode,
    //                     'superficie'        => $superficie,
    //                     'niveau_confort'    => $confort,
    //                     'pmr'               => $pmr,
    //                     'type_occupation_id' =>$this->typeOccupation($typeOccupation, $chambre->batiment_id),
    //                     'capacite_max'      => $capacite,
    //                     'prix'              => $montant,
    //                     'nb_lits'           => $nbLits,
    //                     'avance'            => $avance,
    //                     'caution'           => $caution,
    //                     'equipements'       => $equipement,
    //                     'updated_by'        => getInfoAdmin()->users->id_user ?? null,
    //                     'updated_at'        => now(),
    //                 ]);

    //                 $chambresCrees[] = $chambre->id_chambre;
    //                 $indexChambre++;
    //             }
    //         }

    //         DB::commit();

    //         $message = count($chambresCrees) . ' chambre(s) créée(s) avec succès !';
    //         if (!empty($erreurs)) {
    //             $message .= ' (' . implode(', ', $erreurs) . ')';
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'message' => $message,
    //             'data'    => [
    //                 'total_crees' => count($chambresCrees),
    //                 'erreurs'     => $erreurs,
    //             ]
    //         ]);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         \Log::error('massCreateChambres: ' . $e->getMessage());
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Erreur: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function massCreateChambres(Request $request)
{
    try {

        $request->validate([
            'niveau_code'                        => 'required|string',
            'batiment_code'                      => 'required|string',
            'configurations'                     => 'required|array|min:1',
            'configurations.*.type_chambre_code' => 'required|exists:type_chambre,id_type_chambre',
            'configurations.*.quantity'          => 'required|integer|min:1',
            'configurations.*.pmr'               => 'nullable|in:0,1',
            'configurations.*.nb_lits'           => 'nullable|integer|min:1|max:3',
            'configurations.*.prefix'            => 'nullable|string|max:20',
            'configurations.*.confort'           => 'nullable|string|max:20',
            'configurations.*.typeOccupation'    => 'nullable|string|max:150',
        ]);

        $niveauCode     = $request->input('niveau_code');
        $batimentCode   = $request->input('batiment_code');
        $configurations = $request->input('configurations');

        $chambresNonCrees = Chambre::where('batiment_id', $batimentCode)
            ->where('niveau_aile_code', $niveauCode)
            ->whereNull('numero_chambre')
            ->orderBy('created_at')
            ->get();

        if ($chambresNonCrees->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune chambre à créer dans ce niveau'
            ], 422);
        }

        DB::beginTransaction();

        $indexChambre  = 0;
        $chambresCrees = [];
        $erreurs       = [];

        // Cache local : un compteur de suffixe par préfixe, initialisé une seule fois
        // depuis la BD, puis incrémenté en mémoire à chaque numéro attribué.
        $suffixeParPrefixe = [];



        foreach ($configurations as $config) {
            $typeChambreCode = $config['type_chambre_code'];
            $quantity        = (int) $config['quantity'];
            $prefix          = $config['prefix'] ?? '';
            $pmr             = isset($config['pmr']) ? (bool)$config['pmr'] : false;
            $nbLits          = (int) ($config['nb_lits'] ?? 1);
            $typeOccupation  = $config['typeOccupation'] ?? '';
            $confort         = $config['confort'] ?? 'standard';

            $capacite   = TypeChambre::capaciteChambre($typeChambreCode);
            $caution    = TypeChambre::cautionChambre($typeChambreCode);
            $avance     = TypeChambre::avanceChambre($typeChambreCode);
           // $equipement = TypeChambre::equipementChambre($typeChambreCode);
            $montant    = TypeChambre::montantChambre($typeChambreCode);
            $superficie = TypeChambre::superficieChambre($typeChambreCode);

                        $equipementsInput = $config['equipements'] ?? [];

// On garde la structure [{"name":"Chaise","quantity":1}, ...]
// mais on force quantity en entier (le formulaire l'envoie souvent en string "1")
                    $equipement = collect($equipementsInput)
                        ->map(function ($item) {
                            return [
                                'name'     => $item['name'],
                                'quantity' => (int) ($item['quantity'] ?? 1),
                            ];
                        })
                        ->values()
                        ->toArray();

                    // Si aucun équipement envoyé pour cette config, on retombe sur les équipements par défaut du type
                    if (empty($equipement)) {
                        $equipement = TypeChambre::equipementChambre($typeChambreCode);
                    }

            // Clé de cache : on regroupe par préfixe exact (ex: "CH-", "SH-", "" pour vide/numérique)
            $cleCache = $prefix;

            if (!array_key_exists($cleCache, $suffixeParPrefixe)) {
                $suffixeParPrefixe[$cleCache] = $this->getDernierSuffixe($prefix, $batimentCode, $niveauCode);
            }

            for ($i = 1; $i <= $quantity; $i++) {

                if ($indexChambre >= $chambresNonCrees->count()) {
                    $erreurs[] = "Plus de chambres disponibles dans ce niveau (arrêt à la config {$typeChambreCode}, chambre {$i})";
                    break 2;
                }

                $chambre = $chambresNonCrees[$indexChambre];

                // Incrémente le compteur en mémoire pour ce préfixe et construit le numéro
                $suffixeParPrefixe[$cleCache]++;
                $numeroChambre = $this->formaterNumeroChambre($prefix, $suffixeParPrefixe[$cleCache]);

                // Sécurité supplémentaire : vérifie qu'il n'existe pas déjà en BD
                $existe = Chambre::where('batiment_id', $chambre->batiment_id)
                    ->where('niveau_aile_code', $chambre->niveau_aile_code)
                    ->where('numero_chambre', $numeroChambre)
                    ->where('id_chambre', '!=', $chambre->id_chambre)
                    ->exists();

                if ($existe) {
                    // On ne "perd" pas ce numéro : on repart d'un cran plus loin
                    // en le recalculant depuis la BD au cas où le compteur mémoire soit désynchronisé
                    $suffixeParPrefixe[$cleCache] = $this->getDernierSuffixe($prefix, $batimentCode, $niveauCode);
                    $suffixeParPrefixe[$cleCache]++;
                    $numeroChambre = $this->formaterNumeroChambre($prefix, $suffixeParPrefixe[$cleCache]);
                }

                $chambre->update([
                    'numero_chambre'     => $numeroChambre,
                    'type_chambre_id'    => $typeChambreCode,
                    'superficie'         => $superficie,
                    'niveau_confort'     => $confort,
                    'pmr'                => $pmr,
                    'type_occupation_id' => $this->typeOccupation($typeOccupation, $chambre->batiment_id),
                    'capacite_max'       => $capacite,
                    'prix'               => $montant,
                    'nb_lits'            => $nbLits,
                    'avance'             => $avance,
                    'caution'            => $caution,
                    'equipements'        => $equipement,
                    'updated_by'         => getInfoAdmin()->users->id_user ?? null,
                    'updated_at'         => now(),
                ]);

                $chambresCrees[] = $chambre->id_chambre;
                $indexChambre++;
            }
        }

        DB::commit();

        $message = count($chambresCrees) . ' chambre(s) créée(s) avec succès !';
        if (!empty($erreurs)) {
            $message .= ' (' . implode(', ', $erreurs) . ')';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => [
                'total_crees' => count($chambresCrees),
                'erreurs'     => $erreurs,
            ]
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('massCreateChambres: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Erreur: ' . \App\Support\UserFacingErrors::caught($e)
        ], 500);
    }
}

/**
 * Récupère le dernier suffixe numérique déjà utilisé pour ce préfixe,
 * dans ce bâtiment + ce niveau.
 */
private function getDernierSuffixe(string $prefix, $batimentCode, $niveauCode): int
{
    if (empty($prefix)) {
        return (int) (Chambre::where('batiment_id', $batimentCode)
            ->where('niveau_aile_code', $niveauCode)
            ->pluck('numero_chambre')
            ->filter(fn ($n) => is_numeric($n))
            ->map(fn ($n) => (int) $n)
            ->max() ?? 0);
    }

    if (is_numeric($prefix)) {
        $max = Chambre::where('batiment_id', $batimentCode)
            ->where('niveau_aile_code', $niveauCode)
            ->pluck('numero_chambre')
            ->filter(fn ($n) => is_numeric($n))
            ->map(fn ($n) => (int) $n)
            ->max();

        return $max ?? ((int) $prefix - 1);
    }

    $existants = Chambre::where('batiment_id', $batimentCode)
        ->where('niveau_aile_code', $niveauCode)
        ->where('numero_chambre', 'like', $prefix . '%')
        ->pluck('numero_chambre');

    $maxSuffixe = 0;
    foreach ($existants as $numero) {
        $suffixe = substr($numero, strlen($prefix));
        if (is_numeric($suffixe)) {
            $maxSuffixe = max($maxSuffixe, (int) $suffixe);
        }
    }

    return $maxSuffixe;
}

/**
 * Formate le numéro final à partir du préfixe et du suffixe calculé.
 */
private function formaterNumeroChambre(string $prefix, int $suffixe): string
{
    if (empty($prefix) || is_numeric($prefix)) {
        return (string) $suffixe;
    }

    return $prefix . str_pad($suffixe, 2, '0', STR_PAD_LEFT);
}

    private function typeOccupation($typeOccupation, $batiment_id){
        $batiment = $this->batimentService->findByCode($batiment_id);

        if ($batiment && $batiment->typeOccupation) {
            $typeOccupationSelected = TypeCite::where('id_type_cite', $typeOccupation)->first();

            if ($typeOccupationSelected) {
                // Si le bâtiment est mixte et que le type sélectionné est mixte, on garde mixte
                if ($batiment->typeOccupation->name == 'Mixte' && $typeOccupationSelected->name == 'Mixte') {
                    $typeOccupation = $typeOccupationSelected->id_type_cite;
                } else {
                    // Sinon, on force le type d'occupation du bâtiment
                    $typeOccupation = $batiment->typeOccupation->id_type_cite;
                }
            }
        }

        return $typeOccupation;
    }


    /**
     * Génère le numéro de porte à partir du préfixe et de l'index
     */
    // private function genererNumeroChambre(string $prefix, int $index): string
    // {
    //     if (empty($prefix)) {
    //         return (string) $index;
    //     }

    //     // Préfixe purement numérique → incrémenter (101, 102, 103...)
    //     if (is_numeric($prefix)) {
    //         return (string) ((int)$prefix + $index - 1);
    //     }

    //     // Préfixe alphanumérique → concaténer avec index padé (A-01, A-02...)
    //     return $prefix . str_pad($index, 2, '0', STR_PAD_LEFT);
    // }

//    private function genererNumeroChambre(string $prefix, int $niveauId): string
// {
//     if (empty($prefix)) {
//         $dernierNumero = Chambre::where('niveau_id', $niveauId)
//             ->pluck('numero_chambre')
//             ->filter(fn ($n) => is_numeric($n))
//             ->map(fn ($n) => (int) $n)
//             ->max();

//         return (string) (($dernierNumero ?? 0) + 1);
//     }

//     if (is_numeric($prefix)) {
//         $dernierNumero = Chambre::where('niveau_id', $niveauId)
//             ->pluck('numero_chambre')
//             ->filter(fn ($n) => is_numeric($n))
//             ->map(fn ($n) => (int) $n)
//             ->max();

//         $base = $dernierNumero ?? ((int) $prefix - 1);

//         return (string) ($base + 1);
//     }

//     $existants = Chambre::where('niveau_id', $niveauId)
//         ->where('numero_chambre', 'like', $prefix . '%')
//         ->pluck('numero_chambre');

//     $maxSuffixe = 0;
//     foreach ($existants as $numero) {
//         $suffixe = substr($numero, strlen($prefix));
//         if (is_numeric($suffixe)) {
//             $maxSuffixe = max($maxSuffixe, (int) $suffixe);
//         }
//     }

//     $prochainIndex = $maxSuffixe + 1;

//     return $prefix . str_pad($prochainIndex, 2, '0', STR_PAD_LEFT);
// }

    public function updateChambre(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|exists:chambre,id_chambre',
                'numero_chambre' => 'required|string|max:250',
                'type_chambre_code' => 'required|exists:type_chambre,id_type_chambre',
                'niveau_confort' => 'nullable|string|max:250',
                'typeOccupation' => 'nullable|string|max:250',
                'pmr' => 'nullable|boolean',
                'nb_lits' => 'nullable|integer',
                'etat' => 'sometimes|required|in:disponible,occupee,reservee,maintenance,hors_service,fermee',
            ]);

            $chambre = Chambre::where('id_chambre', $validated['code'])->firstOrFail();

            // Vérifier si le nouveau numéro existe déjà dans le même bâtiment/étage
            $existe = Chambre::where('batiment_id', $chambre->batiment_id)
                ->where('niveau_aile_code', $chambre->niveau_aile_code)
                ->where('numero_chambre', $validated['numero_chambre'])
                ->where('id_chambre', '!=', $chambre->id_chambre)
                ->exists();

            if ($existe) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce numéro de porte existe déjà pour cet étage'
                ], 422);
            }

            $updateData = [
                'numero_chambre' => $validated['numero_chambre'],
                'type_chambre_id' => $validated['type_chambre_code'],
                'niveau_confort' => $validated['niveau_confort'] ?? $chambre->niveau_confort,
                'pmr' => $validated['pmr'] ?? $chambre->pmr,
                'nb_lits' => $validated['nb_lits'] ?? $chambre->nb_lits,
                'updated_by' => getInfoAdmin()->users->id_user ?? null,
            ];

            if (!empty($validated['typeOccupation'])) {
                $updateData['type_occupation_id'] = $this->typeOccupation($validated['typeOccupation'], $chambre->batiment_id);
            }

            if (isset($validated['etat'])) {
                $updateData['etat'] = $validated['etat'];
            }

            $chambre = app(\App\Http\Services\ChambreEtatService::class)
                ->modifier($chambre->getKey(), $updateData);

            return response()->json([
                'success' => true,
                'message' => 'Chambre mise à jour avec succès',
                'chambre' => [
                    'code' => $chambre->id_chambre,
                    'numero_chambre' => $chambre->numero_chambre,
                    'type_chambre_code' => $chambre->type_chambre_id,
                    'etat' => $chambre->etat,
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => \App\Support\UserFacingErrors::caught($e),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . \App\Support\UserFacingErrors::caught($e)
            ], 500);
        }
    }


    public function updateMultipleChambres(Request $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validate([
                'batiment_code' => 'required|exists:batiments,code',
                'chambres' => 'required|array',
                'chambres.*.code' => 'required|exists:chambres,code',
                'chambres.*.numero' => 'required|string|max:50',
                'chambres.*.type_chambre' => 'nullable|exists:type_chambres,code',
            ]);

            $batiment = Batiment::where('code', $validated['batiment_code'])->first();
            $updatedCount = 0;
            $errors = [];

            foreach ($validated['chambres'] as $chambreData) {
                try {
                    $chambre = Chambre::where('id_chambre', $chambreData['code'])->first();

                    // Vérifier unicité du numéro
                    $existe = Chambre::where('batiment_id', $batiment->id_batiment)
                        ->where('etage', $chambre->etage)
                        ->where('numero_porte', $chambreData['numero'])
                        ->where('code', '!=', $chambre->code)
                        ->exists();

                    if ($existe) {
                        $errors[] = "Chambre {$chambre->numero_porte}: Numéro déjà utilisé";
                        continue;
                    }

                    $updateData = [
                        'numero_porte' => $chambreData['numero'],
                        'updated_by' => getInfoAdmin()->users->code
                    ];

                    if (isset($chambreData['type_chambre']) && !empty($chambreData['type_chambre'])) {
                        $updateData['type_chambre_code'] = $chambreData['type_chambre'];
                    }

                    $chambre->update($updateData);
                    $updatedCount++;

                } catch (\Exception $e) {
                    $errors[] = "Erreur sur chambre {$chambreData['numero']}: " . \App\Support\UserFacingErrors::caught($e);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "$updatedCount chambre(s) mise(s) à jour avec succès",
                'errors' => $errors,
                'updated_count' => $updatedCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . \App\Support\UserFacingErrors::caught($e)
            ], 500);
        }
    }


    public function listChambresByBatiment(Request $request)
    {

        $batimentId = $request->get('batiment_id');

        if (!$batimentId) {
            return response()->json([]);
        }

        $chambres = Chambre::with('typeChambre')
            ->where('batiment_id', $batimentId)
            ->orderBy('etage')
            ->orderBy('numero_porte')
            ->get();

        return response()->json($chambres);
    }

    public function destroy($type, $code)
    {
        try {
            // Valider le type
            $validTypes = ['campus', 'cite', 'batiment', 'chambre'];
            if (!in_array($type, $validTypes)) {
                return back()->with('error', 'Type invalide');
            }

            // Déterminer le modèle et les dépendances en fonction du type
            switch ($type) {
                case 'campus':
                    return $this->destroyCampus($code);
                case 'cite':
                    return $this->destroyCite($code);
                case 'batiment':
                    return $this->destroyBatiment($code);
                case 'chambre':
                    return $this->destroyChambre($code);
                default:
                    return back()->with('error', 'Type non reconnu');
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la suppression : ' . \App\Support\UserFacingErrors::caught($e));
        }
    }

    public function toggleStatus(string $type, string $code)
    {
        $newStatus = null;

        try {
            DB::transaction(function () use ($type, $code, &$newStatus) {
                $activeStatus = 'actif';
                $inactiveStatus = 'desactive';

                switch ($type) {
                    case 'cite':
                        $cite = Cite::where('id_cite', $code)->lockForUpdate()->firstOrFail();
                        $isDeactivating = $cite->statut !== $inactiveStatus;
                        $batimentIds = Batiment::where('cite_id', $cite->id_cite)->pluck('id_batiment');
                        $chambresDeLaCite = Chambre::where(function ($query) use ($cite, $batimentIds) {
                            $query->where('cite_id', $cite->id_cite)
                                ->orWhereIn('batiment_id', $batimentIds);
                        });

                        if ($isDeactivating && (clone $chambresDeLaCite)
                            ->whereHas('assignations', fn ($query) => $query->mobilisantPlace())
                            ->exists()) {
                            throw new \RuntimeException('Impossible de désactiver cette cité car elle contient un étudiant.');
                        }

                        $newStatus = $isDeactivating ? $inactiveStatus : $activeStatus;
                        $cite->update(['statut' => $newStatus]);
                        if ($isDeactivating) {
                            Batiment::where('cite_id', $cite->id_cite)->update(['statut' => $inactiveStatus]);
                            $chambresDeLaCite->update(['statut' => $inactiveStatus]);
                        }
                        break;

                    case 'batiment':
                        $batiment = Batiment::where('id_batiment', $code)->lockForUpdate()->firstOrFail();
                        $isDeactivating = $batiment->statut !== $inactiveStatus;

                        if ($isDeactivating && Chambre::where('batiment_id', $batiment->id_batiment)
                            ->whereHas('assignations', fn ($query) => $query->mobilisantPlace())
                            ->exists()) {
                            throw new \RuntimeException('Impossible de désactiver ce bâtiment car il contient un étudiant.');
                        }

                        $newStatus = $isDeactivating ? $inactiveStatus : $activeStatus;
                        $batiment->update(['statut' => $newStatus]);
                        if ($isDeactivating) {
                            Chambre::where('batiment_id', $batiment->id_batiment)->update(['statut' => $inactiveStatus]);
                        }
                        break;

                    case 'chambre':
                        $chambre = Chambre::where('id_chambre', $code)->lockForUpdate()->firstOrFail();
                        $isDeactivating = $chambre->statut !== $inactiveStatus;

                        if ($isDeactivating && $chambre->assignations()->mobilisantPlace()->exists()) {
                            throw new \RuntimeException('Impossible de désactiver cette chambre car elle contient un étudiant.');
                        }

                        $newStatus = $isDeactivating ? $inactiveStatus : $activeStatus;
                        $chambre->update(['statut' => $newStatus]);
                        break;

                    default:
                        throw new \InvalidArgumentException('Type invalide.');
                }
            });

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'type' => $type,
                    'code' => $code,
                    'status' => $newStatus,
                    'message' => 'Statut mis à jour avec succès.',
                ]);
            }

            return redirect()->route('admin.hebergement.parc-immobilier.index')
                ->with('success', 'Statut mis à jour avec succès.');
        } catch (\Throwable $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => \App\Support\UserFacingErrors::caught($e),
                ], 422);
            }

            return back()->with('status_error', \App\Support\UserFacingErrors::caught($e));
        }
    }

// Méthodes privées pour chaque type
    private function destroyCampus($id)
    {
        $campus = Campus::where('id_campus', $id)->firstOrFail();

        // Vérifier les dépendances
        $citesCount = Cite::where('campus_id', $campus->id_campus)->count();
        if ($citesCount > 0) {
            return back()->with('error',
                'Impossible de supprimer ce campus car il contient des cités. ' .
                'Veuillez d\'abord supprimer toutes les cités associées.');
        }

        $campus->delete();

        return redirect()
            ->route('admin.hebergement.parc-immobilier.index')
            ->with('success', 'Campus supprimé avec succès !');
    }

    private function destroyCite($id)
    {
        $cite = Cite::where('id_cite', $id)->firstOrFail();

        // Vérifier les dépendances
        $batimentsCount = Batiment::where('cite_id', $cite->id_cite)->count();
        if ($batimentsCount > 0) {
            return back()->with('error',
                'Impossible de supprimer cette cité car elle contient des bâtiments. ' .
                'Veuillez d\'abord supprimer tous les bâtiments associés.');
        }

        $cite->delete();

        return redirect()
            ->route('admin.hebergement.parc-immobilier.index')
            ->with('success', 'Cité supprimée avec succès !');
    }

    private function destroyBatiment($id)
    {
        $batiment = Batiment::where('id_batiment', $id)->firstOrFail();

        // Vérifier les dépendances
        $chambresCount = Chambre::where('batiment_id', $batiment->id_batiment)->count();
        if ($chambresCount > 0) {
            return back()->with('error',
                'Impossible de supprimer ce bâtiment car il contient des chambres. ' .
                'Veuillez d\'abord supprimer toutes les chambres associées.');
        }

        $batiment->delete();

        return redirect()
            ->route('admin.hebergement.parc-immobilier.index')
            ->with('success', 'Bâtiment supprimé avec succès !');
    }

    private function destroyChambre($id)
    {
        $chambre = Chambre::where('id_chambre', $id)->firstOrFail();

        // Vérifier si la chambre est occupée
        if ($chambre->statut === 'occupée' || $chambre->lits_disponibles < $chambre->lits_total) {
            return back()->with('error',
                'Impossible de supprimer cette chambre car elle est occupée. ' .
                'Veuillez d\'abord libérer la chambre.');
        }

        $chambre->delete();

        return redirect()
            ->route('admin.hebergement.parc-immobilier.index')
            ->with('success', 'Chambre supprimée avec succès !');
    }


    private function handleBase64File($base64File)
    {
        if (strpos($base64File, 'data:image') === 0) {
            $image = Image::make($base64File);
            $fileName = time() . '_plan.png';
            $path = 'storage/batiments/plans/' . $fileName;

            Storage::put('public/batiments/plans/' . $fileName, (string) $image->encode());

            return $path;
        }

        return null;
    }

    private function campusId(): string
    {
        return getInfoAdmin()->users->campus_id;
    }

}
