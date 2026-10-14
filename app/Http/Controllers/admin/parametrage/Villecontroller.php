<?php

namespace App\Http\Controllers\admin\parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Region;
use App\Models\Ville;



class Villecontroller extends  Controller
{
    public function index()
    {
        $villes = Ville::with('region')->get();
        $regions = Region::all();

        // Si requête JSON
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'villes' => $villes,
                    'regions' => $regions
                ]
            ]);
        }

        // Sinon, vue HTML
        return view('admin.parametrage.villes.index', compact('villes', 'regions'));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'region_id' => 'required|exists:regions,id',
            ]);

            $ville = Ville::create($validated);

            // Charger la relation pour la réponse JSON
            $ville->load('region');

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ville créée avec succès !',
                    'data' => $ville
                ], 201);
            }

            return back()->with('success', 'Ville créée avec succès !');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'enregistrement : ' . \App\Support\UserFacingErrors::caught($e)
                ], 500);
            }

            return back()->with('error', 'Problème survenu lors de l\'enregistrement');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'region_id' => 'required|exists:regions,id',
            ]);

            $ville = Ville::findOrFail($id);
            $ville->update($validated);

            // Charger la relation pour la réponse JSON
            $ville->load('region');

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ville modifiée avec succès !',
                    'data' => $ville
                ]);
            }

            return back()->with('success', 'Ville modifiée avec succès !');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la modification : ' . \App\Support\UserFacingErrors::caught($e)
                ], 500);
            }

            return back()->with('error', 'Problème survenu lors de la modification');
        }
    }

    public function delete(Request $request, $id)
    {
        try {
            $ville = Ville::findOrFail($id);
            $ville->delete();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ville supprimée avec succès !'
                ]);
            }

            return back()->with('success', 'Ville supprimée avec succès !');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la suppression : ' . \App\Support\UserFacingErrors::caught($e)
                ], 500);
            }

            return back()->with('error', 'Problème survenu lors de la suppression');
        }
    }

}