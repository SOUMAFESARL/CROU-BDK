<?php

namespace App\Http\Controllers\admin\immobilier\equipements;

use App\Http\Controllers\Controller;
use App\Models\{Equipement, EquipementAffectation, Cite, Batiment, Chambre};
use App\Services\EquipmentAllocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EquipmentAllocationController extends Controller
{
    public function index(Request $request)
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

        return view('admin.hebergement.equipements.affectations', [
            'affectations' => $query->paginate(15)->withQueryString(),
            'equipements' => $equipements,
            'cites' => Cite::orderBy('name')->get(), 'batiments' => Batiment::all(), 'chambres' => Chambre::whereNotNull('numero_chambre')->get(),
            'statut' => $statut,
            'recherche' => $recherche,
            'stats' => [
                'actives' => (clone $actives)->count(),
                'unites' => (int) (clone $actives)->sum('quantite'),
                'retournees' => EquipementAffectation::whereNotNull('returned_at')->count(),
                'a_reparer' => (clone $actives)->where('etat', '!=', 'bon')->count(),
                'defectueux' => (clone $actives)->where('etat', 'defectueux')->count(),
                'magasin' => $equipements->sum(fn ($equipement) => max(0, $equipement->stock_actuel)),
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
