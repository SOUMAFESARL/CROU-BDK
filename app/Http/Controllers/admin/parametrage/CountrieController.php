<?php

namespace App\Http\Controllers\admin\parametrage;

use App\Http\Controllers\admin\immobilier\parametrage\ReferentielInertia;
use App\Http\Controllers\Controller;
use App\Models\Countrie;
use App\Support\UserFacingErrors;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CountrieController extends Controller
{
    use ReferentielInertia;

    public function index(Request $request)
    {
        $url = $this->urlSiAutorise($request);
        $query = Countrie::query()->orderBy('name');
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        if ($q !== '') {
            $query->where(fn (Builder $w) => $w->where('name', 'like', '%'.$q.'%')->orWhere('code', 'like', '%'.$q.'%'));
        }
        [$etudiants, $campus] = $this->usages();
        $total = Countrie::count();
        $utilises = Countrie::pluck('id')->filter(fn ($id) => ($etudiants[$id] ?? 0) + ($campus[$id] ?? 0) > 0)->count();

        return Inertia::render('Admin/Parametrage/Pays', [
            'elements' => $query->paginate(10)->withQueryString()->through(fn (Countrie $c) => [
                'id' => (string) $c->id,
                'name' => $c->name,
                'code' => $c->code,
                'etudiants' => (int) ($etudiants[$c->id] ?? 0),
                'campus' => (int) ($campus[$c->id] ?? 0),
                'urls' => [
                    'update' => $url('admin.parametrage.countrie.update', $c->id),
                    'destroy' => $url('admin.parametrage.countrie.delete', $c->id),
                ],
            ]),
            'stats' => ['total' => $total, 'utilises' => $utilises, 'inutilises' => $total - $utilises],
            'filters' => ['q' => $q],
            'urls' => [
                'index' => route('admin.parametrage.countrie.index'),
                'store' => $url('admin.parametrage.countrie.store'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->regles(), $this->messages());

        try {
            Countrie::create($validated);

            return back()->with('success', 'Pays créé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Problème survenu lors de l\'enregistrement : '.UserFacingErrors::caught($e));
        }
    }

    public function update(Request $request, $id)
    {
        $pays = Countrie::findOrFail($id);
        $validated = $request->validate($this->regles($pays->id), $this->messages());

        try {
            $pays->update($validated);

            return back()->with('success', 'Pays modifié avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Problème survenu lors de la modification : '.UserFacingErrors::caught($e));
        }
    }

    public function delete(Request $request, $id)
    {
        $pays = Countrie::findOrFail($id);
        [$etudiants, $campus] = $this->usages();
        if (($etudiants[$pays->id] ?? 0) + ($campus[$pays->id] ?? 0) > 0) {
            return back()->with('error', 'Ce pays est utilisé par des étudiants ou des campus : modifiez-les avant de le supprimer.');
        }

        try {
            $pays->delete();

            return back()->with('success', 'Pays supprimé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Problème survenu lors de la suppression : '.UserFacingErrors::caught($e));
        }
    }

    private function regles(?int $id = null): array
    {
        return [
            'name' => ['required', 'string', 'max:225', Rule::unique('countrie', 'name')->ignore($id)],
            'code' => ['nullable', 'string', 'max:150'],
        ];
    }

    private function messages(): array
    {
        return ['name.unique' => 'Ce pays existe déjà.'];
    }

    private function usages(): array
    {
        return [$this->references([['etudiants', 'countrie_id']]), $this->references([['campus', 'countrie_id']])];
    }
}
