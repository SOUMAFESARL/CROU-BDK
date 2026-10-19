<?php

namespace App\Http\Controllers\admin\immobilier\parametrage\type_equipement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\admin\immobilier\parametrage\ReferentielInertia;
use App\Models\Equipement;
use App\Models\TypeEquipement;
use Inertia\Inertia;

class TypeEquipementController extends Controller
{
    use ReferentielInertia;

    /**
     * Afficher la liste des types d'équipements
     */
    public function index(Request $request)
    {
        $url = $this->urlSiAutorise($request);
        $query = TypeEquipement::query()->orderBy('name');
        $q = $this->rechercher($request, $query);
        $equipements = Equipement::query()->selectRaw('type_equipement_id, count(*) as total')->groupBy('type_equipement_id')->pluck('total', 'type_equipement_id');
        $total = TypeEquipement::count();
        $utilises = TypeEquipement::pluck('id_type_equipement')->filter(fn ($id) => ($equipements[$id] ?? 0) > 0)->count();

        return Inertia::render('Admin/Hebergement/Parametrage/TypesEquipement', [
            'types' => $query->paginate(10)->withQueryString()->through(fn (TypeEquipement $type) => [
                'id' => (string) $type->id_type_equipement,
                'name' => $type->name,
                'description' => $type->description,
                'equipements' => (int) ($equipements[$type->id_type_equipement] ?? 0),
                'urls' => [
                    'update' => $url('admin.hebergement.parametrage-herb.type-equipement.update', $type->id_type_equipement),
                    'destroy' => $url('admin.hebergement.parametrage-herb.type-equipement.destroy', $type->id_type_equipement),
                ],
            ]),
            'stats' => ['total' => $total, 'utilises' => $utilises, 'inutilises' => $total - $utilises],
            'filters' => ['q' => $q],
            'urls' => [
                'index' => route('admin.hebergement.parametrage-herb.type-equipement.index'),
                'store' => $url('admin.hebergement.parametrage-herb.type-equipement.store'),
                'equipements' => $url('admin.hebergement.equipements.index'),
            ],
        ]);
    }

    /**
     * Enregistrer un nouveau type d'équipement
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        TypeEquipement::create($validated);

        return back()->with('success', 'Type d\'équipement ajouté avec succès !');
    }

    /**
     * Mettre à jour un type d'équipement
     */
    public function update(Request $request, $id)
    {
        $type_equipement = TypeEquipement::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $type_equipement->update($validated);

        return back()->with('success', 'Type d\'équipement modifié avec succès !');
    }

    /**
     * Supprimer un type d'équipement
     */
    public function destroy($id)
    {
        $type_equipement = TypeEquipement::findOrFail($id);
        $type_equipement->delete();

        return back()->with('success', 'Type d\'équipement supprimé avec succès !');
    }
}