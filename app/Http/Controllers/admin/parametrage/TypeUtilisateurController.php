<?php

namespace App\Http\Controllers\admin\parametrage;

use App\Http\Controllers\admin\immobilier\parametrage\ReferentielInertia;
use App\Http\Controllers\Controller;
use App\Models\TypeUtilisateur;
use App\Models\User;
use App\Support\UserFacingErrors;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TypeUtilisateurController extends Controller
{
    use ReferentielInertia;

    private const REGLES = ['name' => ['required', 'string', 'max:100']];

    public function index(Request $request)
    {
        $url = $this->urlSiAutorise($request);
        $query = TypeUtilisateur::query()->orderBy('name');
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        if ($q !== '') {
            $query->where('name', 'like', '%'.$q.'%');
        }
        $utilisateurs = $this->utilisateursParType();
        $total = TypeUtilisateur::count();
        $utilises = TypeUtilisateur::pluck('id')->filter(fn ($id) => ($utilisateurs[$id] ?? 0) > 0)->count();

        return Inertia::render('Admin/Parametrage/TypesUtilisateur', [
            'elements' => $query->paginate(10)->withQueryString()->through(fn (TypeUtilisateur $t) => [
                'id' => (string) $t->id,
                'name' => $t->name,
                'utilisateurs' => (int) ($utilisateurs[$t->id] ?? 0),
                'urls' => [
                    'update' => $url('admin.parametrage.type-utilisateur.update', $t->id),
                    'destroy' => $url('admin.parametrage.type-utilisateur.delete', $t->id),
                ],
            ]),
            'stats' => ['total' => $total, 'utilises' => $utilises, 'inutilises' => $total - $utilises],
            'filters' => ['q' => $q],
            'urls' => [
                'index' => route('admin.parametrage.type-utilisateur.index'),
                'store' => $url('admin.parametrage.type-utilisateur.store'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(self::REGLES);

        try {
            TypeUtilisateur::create($validated);

            return back()->with('success', 'Type d\'utilisateur créé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Problème survenu lors de l\'enregistrement : '.UserFacingErrors::caught($e));
        }
    }

    public function update(Request $request, $id)
    {
        $type = TypeUtilisateur::findOrFail($id);
        $validated = $request->validate(self::REGLES);

        try {
            $type->update($validated);

            return back()->with('success', 'Type d\'utilisateur modifié avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Problème survenu lors de la modification : '.UserFacingErrors::caught($e));
        }
    }

    public function delete(Request $request, $id)
    {
        $type = TypeUtilisateur::findOrFail($id);
        if (($this->utilisateursParType()[$type->id] ?? 0) > 0) {
            return back()->with('error', 'Ce type est attribué à des utilisateurs : modifiez leur type avant de le supprimer.');
        }

        try {
            $type->delete();

            return back()->with('success', 'Type d\'utilisateur supprimé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Problème survenu lors de la suppression : '.UserFacingErrors::caught($e));
        }
    }

    private function utilisateursParType()
    {
        return User::query()->whereNotNull('type_utilisateur_id')->selectRaw('type_utilisateur_id, count(*) as total')->groupBy('type_utilisateur_id')->pluck('total', 'type_utilisateur_id');
    }
}
