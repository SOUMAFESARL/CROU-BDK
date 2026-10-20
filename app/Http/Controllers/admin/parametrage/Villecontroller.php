<?php

namespace App\Http\Controllers\admin\parametrage;

use App\Http\Controllers\admin\immobilier\parametrage\ReferentielInertia;
use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\Ville;
use App\Support\UserFacingErrors;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class Villecontroller extends Controller
{
    use ReferentielInertia;

    public function index(Request $request)
    {
        $url = $this->urlSiAutorise($request);
        $regions = Region::query()->orderBy('name')->get(['id', 'name']);
        $query = Ville::query()->with('region')->orderBy('name');
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        if ($q !== '') {
            $query->where(fn (Builder $w) => $w->where('name', 'like', '%'.$q.'%')
                ->orWhereHas('region', fn (Builder $r) => $r->where('name', 'like', '%'.$q.'%')));
        }
        // ?region= : identifiant de la région ; valeur inconnue ignorée.
        $region = (string) $request->query('region', '');
        if ($regions->contains(fn (Region $r) => (string) $r->id === $region)) {
            $query->where('region_id', (int) $region);
        } else {
            $region = '';
        }
        [$etudiants, $autres] = $this->usages();
        $total = Ville::count();
        $utilises = Ville::pluck('id')->filter(fn ($id) => ($etudiants[$id] ?? 0) + ($autres[$id] ?? 0) > 0)->count();

        return Inertia::render('Admin/Parametrage/Villes', [
            'elements' => $query->paginate(10)->withQueryString()->through(fn (Ville $v) => [
                'id' => (string) $v->id,
                'name' => $v->name,
                'region_id' => (string) $v->region_id,
                'region' => $v->region?->name,
                'etudiants' => (int) ($etudiants[$v->id] ?? 0),
                'autres' => (int) ($autres[$v->id] ?? 0),
                'urls' => [
                    'update' => $url('admin.parametrage.ville.update', $v->id),
                    'destroy' => $url('admin.parametrage.ville.delete', $v->id),
                ],
            ]),
            'regions' => $regions->map(fn (Region $r) => ['id' => (string) $r->id, 'name' => $r->name])->values(),
            'stats' => ['total' => $total, 'utilises' => $utilises, 'inutilises' => $total - $utilises],
            'filters' => ['q' => $q, 'region' => $region],
            'urls' => [
                'index' => route('admin.parametrage.ville.index'),
                'store' => $url('admin.parametrage.ville.store'),
                'regions' => $url('admin.parametrage.region.index'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->regles($request), $this->messages());

        try {
            Ville::create($validated);

            return back()->with('success', 'Ville créée avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Problème survenu lors de l\'enregistrement : '.UserFacingErrors::caught($e));
        }
    }

    public function update(Request $request, $id)
    {
        $ville = Ville::findOrFail($id);
        $validated = $request->validate($this->regles($request, $ville->id), $this->messages());

        try {
            $ville->update($validated);

            return back()->with('success', 'Ville modifiée avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Problème survenu lors de la modification : '.UserFacingErrors::caught($e));
        }
    }

    public function delete(Request $request, $id)
    {
        $ville = Ville::findOrFail($id);
        [$etudiants, $autres] = $this->usages();
        if (($etudiants[$ville->id] ?? 0) + ($autres[$ville->id] ?? 0) > 0) {
            return back()->with('error', 'Cette ville est utilisée par des étudiants, campus ou comptes : modifiez-les avant de la supprimer.');
        }

        try {
            $ville->delete();

            return back()->with('success', 'Ville supprimée avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Problème survenu lors de la suppression : '.UserFacingErrors::caught($e));
        }
    }

    /** Le nom est unique dans sa région (deux régions peuvent avoir une ville homonyme). */
    private function regles(Request $request, ?int $id = null): array
    {
        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('villes', 'name')->where('region_id', (int) $request->input('region_id'))->ignore($id)],
            'region_id' => ['required', 'integer', Rule::exists('regions', 'id')],
        ];
    }

    private function messages(): array
    {
        return [
            'name.unique' => 'Cette ville existe déjà dans la région choisie.',
            'region_id.required' => 'Choisissez la région de la ville.',
            'region_id.exists' => 'La région choisie n\'existe pas.',
        ];
    }

    /** Étudiants (`etudiants.ville_id`) et autres références : campus, comptes administrateurs, configuration. */
    private function usages(): array
    {
        return [
            $this->references([['etudiants', 'ville_id']]),
            $this->references([['campus', 'ville_id'], ['users', 'ville_id'], ['config_app', 'ville_id']]),
        ];
    }
}
