<?php

namespace App\Http\Controllers\admin\immobilier\equipements;

use App\Http\Controllers\Controller;
use App\Models\{Equipement, EquipementAffectation, Cite, Batiment, Chambre};
use App\Services\EquipmentAllocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\AdminAccess;
use Inertia\Inertia;

class EquipmentAllocationController extends Controller
{
    public function index(Request $request, AdminAccess $access)
    {
        $filters = $request->validate([
            'cite_id' => 'nullable|string', 'batiment_id' => 'nullable|string', 'chambre_id' => 'nullable|string',
            'statut' => 'nullable|string', 'q' => 'nullable|string|max:100',
        ]);
        $statut = in_array($filters['statut'] ?? null, ['actives', 'retournees', 'a_reparer'], true) ? $filters['statut'] : 'actives';
        $recherche = trim($filters['q'] ?? '');

        $query = EquipementAffectation::with(['equipement', 'cite', 'batiment', 'chambre'])->latest();
        foreach (['cite_id', 'batiment_id', 'chambre_id'] as $key) {
            if (!empty($filters[$key])) $query->where($key, $filters[$key]);
        }
        match ($statut) {
            'retournees' => $query->whereNotNull('returned_at'),
            'a_reparer' => $query->whereNull('returned_at')->where('etat', '!=', 'bon'),
            default => $query->whereNull('returned_at'),
        };
        if ($recherche !== '') {
            $query->whereHas('equipement', fn ($q) => $q->where('name', 'like', '%'.$recherche.'%'));
        }

        $actives = EquipementAffectation::whereNull('returned_at');
        $equipements = Equipement::with('mouvements')->orderBy('name')->get();
        $admin = $request->user('admin');
        $url = fn (string $route, array $parametres = []) => $access->allows($admin, $route) ? route($route, $parametres) : null;
        $parametres = array_filter(['q' => $recherche ?: null, 'cite_id' => $filters['cite_id'] ?? null]);

        return Inertia::render('Admin/Hebergement/Equipements/Affectations', [
            'affectations' => $query->paginate(15)->withQueryString()->through(fn (EquipementAffectation $item) => [
                'id' => (string) $item->id,
                'equipement' => $item->equipement?->name,
                'notes' => $item->notes,
                'cite' => $item->cite?->name ?? $item->cite_id,
                'batiment' => $item->batiment_id ? ($item->batiment?->name ?? $item->batiment?->code ?? 'Bâtiment') : null,
                'chambre' => $item->chambre_id ? ($item->chambre?->numero_chambre ?? $item->chambre_id) : null,
                'quantite' => (int) $item->quantite,
                'etat' => $item->etat,
                'retourne_le' => $item->returned_at?->format('Y-m-d H:i'),
                'affecte_le' => $item->created_at?->toDateString(),
                'urls' => $item->returned_at ? ['update' => null, 'retour' => null] : [
                    'update' => $url('admin.hebergement.equipements.affectations.update', ['affectation' => $item->id]),
                    'retour' => $url('admin.hebergement.equipements.affectations.retour', ['affectation' => $item->id]),
                ],
            ]),
            'equipements' => $equipements->map(fn (Equipement $equipement) => [
                'id' => (string) $equipement->equipement_id,
                'name' => $equipement->name,
                'stock' => max(0, (int) $equipement->stock_actuel),
            ])->values(),
            'cites' => Cite::orderBy('name')->get()->map(fn ($cite) => ['id' => (string) $cite->id_cite, 'name' => $cite->name])->values(),
            'batiments' => Batiment::all()->map(fn ($batiment) => [
                'id' => (string) $batiment->id_batiment,
                'name' => $batiment->name ?? $batiment->code ?? (string) $batiment->id_batiment,
                'cite_id' => (string) $batiment->cite_id,
            ])->values(),
            'chambres' => Chambre::whereNotNull('numero_chambre')->get()->map(fn ($chambre) => [
                'id' => (string) $chambre->id_chambre,
                'numero' => (string) $chambre->numero_chambre,
                'batiment_id' => (string) $chambre->batiment_id,
            ])->values(),
            'statut' => $statut,
            'filters' => ['q' => $recherche, 'cite_id' => (string) ($filters['cite_id'] ?? '')],
            'stats' => [
                'actives' => (clone $actives)->count(),
                'unites' => (int) (clone $actives)->sum('quantite'),
                'retournees' => EquipementAffectation::whereNotNull('returned_at')->count(),
                'a_reparer' => (clone $actives)->where('etat', '!=', 'bon')->count(),
                'defectueux' => (clone $actives)->where('etat', 'defectueux')->count(),
                'magasin' => (float) $equipements->sum(fn ($equipement) => max(0, $equipement->stock_actuel)),
            ],
            'urls' => [
                'index' => route('admin.hebergement.equipements.affectations.index'),
                'catalogue' => $url('admin.hebergement.equipements.index'),
                'store' => $url('admin.hebergement.equipements.affectations.store'),
                'filtres' => collect(['actives', 'a_reparer', 'retournees'])->mapWithKeys(fn ($filtre) => [
                    $filtre => route('admin.hebergement.equipements.affectations.index', $parametres + ['statut' => $filtre]),
                ]),
            ],
        ]);
    }

    public function store(Request $request, EquipmentAllocationService $service)
    {
        $data = $request->validate([
            'equipement_id' => 'required|string|max:150', 'cite_id' => 'required|string|max:150',
            'batiment_id' => 'nullable|string|max:150', 'chambre_id' => 'nullable|string|max:150',
            'quantite' => 'required|integer|min:1|max:1000000', 'etat' => 'required|in:bon,moyen,defectueux', 'notes' => 'nullable|string|max:2000',
        ]);
        $service->allocate($data, Auth::guard('admin')->user()?->code);
        return back()->with('success', 'Équipement affecté au parc. Le stock disponible a été mis à jour.');
    }
    public function update(Request $request, EquipementAffectation $affectation)
    {
        $data = $request->validate(['etat' => 'required|in:bon,moyen,defectueux']);
        $changed = EquipementAffectation::whereKey($affectation->id)->whereNull('returned_at')->update($data);
        abort_unless($changed, 409, 'Cette affectation a déjà été retournée.');
        return back()->with('success', 'État du matériel mis à jour.');
    }
    public function retour(EquipementAffectation $affectation, EquipmentAllocationService $service)
    {
        $service->returnToStock($affectation, Auth::guard('admin')->user()?->code);
        return back()->with('success', 'Retour au stock enregistré.');
    }
}
