<?php

namespace App\Http\Controllers\admin\immobilier\parametrage\type_cite;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\admin\immobilier\parametrage\ReferentielInertia;
use App\Models\Batiment;
use App\Models\Cite;
use App\Models\TypeCite;
use Inertia\Inertia;

class TypeCiteController extends Controller
{
    use ReferentielInertia;

    public function index()
    {
        // Si requête JSON
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => TypeCite::orderBy('name', 'asc')->get()
            ]);
        }

        return Inertia::render('Admin/Hebergement/Parametrage/TypesCite', $this->pageProps(request()));
    }

    /** Liste paginée (10 par page) avec l'usage de chaque type ; URL d'action seulement si elles sont autorisées. */
    private function pageProps(Request $request): array
    {
        $url = $this->urlSiAutorise($request);
        $query = TypeCite::query()->orderBy('name');
        $q = $this->rechercher($request, $query);
        // Un type de cité sert aussi de « type d'occupation » aux bâtiments : son libellé peut restreindre l'attribution par sexe.
        $cites = Cite::query()->selectRaw('type_cite_id, count(*) as total')->groupBy('type_cite_id')->pluck('total', 'type_cite_id');
        $batiments = Batiment::query()->selectRaw('type_occupation_id, count(*) as total')->groupBy('type_occupation_id')->pluck('total', 'type_occupation_id');
        $total = TypeCite::count();
        $utilises = TypeCite::pluck('id_type_cite')->filter(fn ($id) => ($cites[$id] ?? 0) + ($batiments[$id] ?? 0) > 0)->count();

        return [
            'types' => $query->paginate(10)->withQueryString()->through(fn (TypeCite $type) => [
                'id' => (string) $type->id_type_cite,
                'name' => $type->name,
                'description' => $type->description,
                'code' => $type->code_type,
                'cites' => (int) ($cites[$type->id_type_cite] ?? 0),
                'batiments' => (int) ($batiments[$type->id_type_cite] ?? 0),
                'urls' => [
                    'update' => $url('admin.hebergement.parametrage-herb.type-cite.update', $type->id_type_cite),
                    'destroy' => $url('admin.hebergement.parametrage-herb.type-cite.destroy', $type->id_type_cite),
                ],
            ]),
            'stats' => ['total' => $total, 'utilises' => $utilises, 'inutilises' => $total - $utilises],
            'filters' => ['q' => $q],
            'urls' => [
                'index' => route('admin.hebergement.parametrage-herb.type-cite.index'),
                'parametrage' => route('admin.hebergement.parametrage-herb.index'),
                'store' => $url('admin.hebergement.parametrage-herb.type-cite.store'),
            ],
        ];
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
