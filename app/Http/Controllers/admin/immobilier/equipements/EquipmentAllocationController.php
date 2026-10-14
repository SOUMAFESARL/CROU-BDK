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
        $filters = $request->validate(['cite_id' => 'nullable|string', 'batiment_id' => 'nullable|string', 'chambre_id' => 'nullable|string']);
        $query = EquipementAffectation::with(['equipement', 'cite', 'batiment', 'chambre'])->latest();
        foreach ($filters as $key => $value) if ($value) $query->where($key, $value);
        return view('admin.hebergement.equipements.affectations', [
            'affectations' => $query->paginate(25)->withQueryString(),
            'equipements' => Equipement::with('mouvements')->orderBy('name')->get(),
            'cites' => Cite::orderBy('name')->get(), 'batiments' => Batiment::all(), 'chambres' => Chambre::whereNotNull('numero_chambre')->get(),
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
