<?php

namespace App\Http\Controllers\admin\parametrage;

use App\Http\Controllers\admin\immobilier\parametrage\ReferentielInertia;
use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Support\UserFacingErrors;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class RegionController extends Controller
{
    use ReferentielInertia;

    public function index(Request $request)
    {
        $url = $this->urlSiAutorise($request);
        $query = Region::query()->orderBy('name');
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        if ($q !== '') {
            $query->where('name', 'like', '%'.$q.'%');
        }
        [$etudiants, $autres] = $this->usages();
        $villes = $this->references([['villes', 'region_id']]);
        $total = Region::count();
        $utilises = Region::pluck('id')->filter(fn ($id) => ($villes[$id] ?? 0) + ($etudiants[$id] ?? 0) + ($autres[$id] ?? 0) > 0)->count();

        return Inertia::render('Admin/Parametrage/Regions', [
            'elements' => $query->paginate(10)->withQueryString()->through(fn (Region $r) => [
                'id' => (string) $r->id,
                'name' => $r->name,
                'villes' => (int) ($villes[$r->id] ?? 0),
                'etudiants' => (int) ($etudiants[$r->id] ?? 0),
                'autres' => (int) ($autres[$r->id] ?? 0),
                'urls' => [
                    'update' => $url('admin.parametrage.region.update', $r->id),
                    'destroy' => $url('admin.parametrage.region.delete', $r->id),
                ],
            ]),
            'stats' => ['total' => $total, 'utilises' => $utilises, 'inutilises' => $total - $utilises],
            'filters' => ['q' => $q],
            'urls' => [
                'index' => route('admin.parametrage.region.index'),
                'store' => $url('admin.parametrage.region.store'),
                'villes' => $url('admin.parametrage.ville.index'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->regles(), ['name.unique' => 'Cette région existe déjà.']);

        try {
            Region::create($validated);

            return back()->with('success', 'Région créée avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Problème survenu lors de l\'enregistrement : '.UserFacingErrors::caught($e));
        }
    }

    public function update(Request $request, $id)
    {
        $region = Region::findOrFail($id);
        $validated = $request->validate($this->regles($region->id), ['name.unique' => 'Cette région existe déjà.']);

        try {
            $region->update($validated);

            return back()->with('success', 'Région modifiée avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Problème survenu lors de la modification : '.UserFacingErrors::caught($e));
        }
    }

    public function delete(Request $request, $id)
    {
        $region = Region::findOrFail($id);
        [$etudiants, $autres] = $this->usages();
        // Les villes de la région (region_id obligatoire) et les fiches qui la citent deviendraient orphelines.
        if ($region->villes()->exists() || ($etudiants[$region->id] ?? 0) + ($autres[$region->id] ?? 0) > 0) {
            return back()->with('error', 'Cette région contient des villes ou est utilisée par des étudiants, campus ou comptes : modifiez-les avant de la supprimer.');
        }

        try {
            $region->delete();

            return back()->with('success', 'Région supprimée avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Problème survenu lors de la suppression : '.UserFacingErrors::caught($e));
        }
    }

    private function regles(?int $id = null): array
    {
        return ['name' => ['required', 'string', 'max:150', Rule::unique('regions', 'name')->ignore($id)]];
    }

    /** Étudiants (`etudiants.regions_id`) et autres références : campus, comptes administrateurs, configuration. */
    private function usages(): array
    {
        return [
            $this->references([['etudiants', 'regions_id']]),
            $this->references([['campus', 'region_id'], ['users', 'region_id'], ['config_app', 'region_id']]),
        ];
    }
}
