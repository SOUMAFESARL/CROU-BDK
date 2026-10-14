<?php

namespace App\Http\Controllers\admin\immobilier\parametrage\type_chambre;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TypeChambre;

class TypeChambreController extends Controller
{
    /**
     * Afficher la liste des types de chambre
     */
    public function index()
    {
        $chambres = TypeChambre::orderBy('name', 'asc')->get();

        // Transformer les données pour Alpine.js
        $chambresData = $chambres->map(function ($chambre) {
            return [
                'id' => $chambre->id_type_chambre,
                'nom' => $chambre->name ?? '—',
                'description' => $chambre->description ?? '—',
                'superficie' => $chambre->superficie ?? '—',
                'equipement' => $chambre->equipement ?? '—',
                'montant' => $chambre->montant ?? '0',
                'avance' => $chambre->avance ?? '0',
                'caution' => $chambre->caution ?? '0',
            ];
        });

        return view('admin.hebergement.parametrage-herb.type-chambre.index', [
            'chambresData' => $chambresData,
        ]);
    }

    /**
     * Enregistrer un nouveau type de chambre
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'montant' => 'nullable|integer',
            'superficie' => 'nullable|integer',
            'avance' => 'nullable|integer',
            'caution' => 'nullable|integer',
            'equipement' => 'sometimes|array',
        ]);

        if(isset($validated['equipement'])){
            $validated['equipement'] = json_encode($validated['equipement']);
        }

        TypeChambre::create($validated);

        return back()->with('success', 'Type de chambre créé avec succès !');
    }

    /**
     * Mettre à jour un type de chambre
     */
    public function update(Request $request, $id)
    {
        $typeChambre = TypeChambre::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'montant' => 'nullable|integer',
            'superficie' => 'nullable|integer',
            'avance' => 'nullable|integer',
            'caution' => 'nullable|integer',
            'equipement' => 'sometimes|array',
        ]);

        if(isset($validated['equipement'])){
            $validated['equipement'] = json_encode($validated['equipement']);
        }

        $typeChambre->update($validated);

        return back()->with('success', 'Type de chambre modifié avec succès !');
    }

    /**
     * Supprimer un type de chambre
     */
    public function destroy($id)
    {
        $typeChambre = TypeChambre::findOrFail($id);
        $typeChambre->delete();

        return back()->with('success', 'Type de chambre supprimé avec succès !');
    }

    // NOTE: Les méthodes create() et edit() ont été supprimées car on utilise des modals
}