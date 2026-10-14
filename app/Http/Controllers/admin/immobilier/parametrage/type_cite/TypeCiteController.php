<?php

namespace App\Http\Controllers\admin\immobilier\parametrage\type_cite;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TypeCite;

class TypeCiteController extends Controller
{
    public function index()
    {
        $type_cites = TypeCite::orderBy('name', 'asc')->get();

        // Si requête JSON
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $type_cites
            ]);
        }

        // Sinon, vue HTML
        return view('admin.hebergement.parametrage-herb.type-cite.index', [
            'type_cites' => $type_cites,
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:250',
                'description' => 'nullable|string',
            ]);

            $typeCite = TypeCite::create($validated);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Type de cité créé avec succès !',
                    'data' => $typeCite
                ], 201);
            }

            return back()->with('success', 'Type de cité créé avec succès !');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'enregistrement : ' . \App\Support\UserFacingErrors::caught($e)
                ], 500);
            }

            return back()->with('error', 'Erreur lors de l\'enregistrement : ' . \App\Support\UserFacingErrors::caught($e));
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $type_cite = TypeCite::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:250',
                'description' => 'nullable|string',
            ]);

            $type_cite->update($validated);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Type de cité modifié avec succès !',
                    'data' => $type_cite
                ]);
            }

            return back()->with('success', 'Type de cité modifié avec succès !');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la modification : ' . \App\Support\UserFacingErrors::caught($e)
                ], 500);
            }

            return back()->with('error', 'Erreur lors de la modification : ' . \App\Support\UserFacingErrors::caught($e));
        }
    }

    public function destroy($id)
    {
        try {
            $type_cite = TypeCite::findOrFail($id);
            $type_cite->delete();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Type de cité supprimé avec succès !'
                ]);
            }

            return back()->with('success', 'Type de cité supprimé avec succès !');

        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la suppression : ' . \App\Support\UserFacingErrors::caught($e)
                ], 500);
            }

            return back()->with('error', 'Erreur lors de la suppression : ' . \App\Support\UserFacingErrors::caught($e));
        }
    }
}