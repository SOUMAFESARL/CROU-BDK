<?php

namespace App\Http\Controllers\admin\immobilier\parametrage\type_equipement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TypeEquipement;

class TypeEquipementController extends Controller
{
    /**
     * Afficher la liste des types d'équipements
     */
    public function index()
    {
        $type_equipements = TypeEquipement::orderBy('name', 'asc')->get();

        return view('admin.hebergement.parametrage-herb.type-equipement.index', [
            'type_equipements' => $type_equipements,
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