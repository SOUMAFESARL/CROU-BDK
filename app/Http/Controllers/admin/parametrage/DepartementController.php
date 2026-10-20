<?php

namespace App\Http\Controllers\admin\parametrage;

use App\Http\Controllers\admin\immobilier\parametrage\ReferentielInertia;
use App\Http\Controllers\Controller;
use App\Models\Departement;
use App\Models\Etudiant;
use App\Models\Filiere;
use App\Support\UserFacingErrors;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DepartementController extends Controller
{
    use ReferentielInertia;

    private const REGLES = [
        'name' => ['required', 'string', 'max:255'],
        'abreviation' => ['nullable', 'string', 'max:100'],
        'description' => ['nullable', 'string', 'max:2000'],
    ];

    public function index(Request $request)
    {
        $url = $this->urlSiAutorise($request);
        $query = Departement::query()->orderBy('name');
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        if ($q !== '') {
            $query->where(fn (Builder $w) => $w->where('name', 'like', '%'.$q.'%')
                ->orWhere('abreviation', 'like', '%'.$q.'%')
                ->orWhere('description', 'like', '%'.$q.'%'));
        }
        $filieres = $this->filieresParDepartement();
        $etudiants = $this->etudiantsParDepartement();
        $total = Departement::count();
        $utilises = Departement::pluck('code_departement')->filter(fn ($code) => ($filieres[$code] ?? 0) + ($etudiants[$code] ?? 0) > 0)->count();

        return Inertia::render('Admin/Parametrage/Departements', [
            'elements' => $query->paginate(10)->withQueryString()->through(fn (Departement $d) => [
                'id' => (string) $d->id_departement,
                'name' => $d->name,
                'abreviation' => $d->abreviation,
                'description' => $d->description,
                'filieres' => (int) ($filieres[$d->code_departement] ?? 0),
                'etudiants' => (int) ($etudiants[$d->code_departement] ?? 0),
                'urls' => [
                    'update' => $url('admin.parametrage.departement.update', $d->id_departement),
                    'destroy' => $url('admin.parametrage.departement.delete', $d->id_departement),
                ],
            ]),
            'stats' => ['total' => $total, 'utilises' => $utilises, 'inutilises' => $total - $utilises],
            'filters' => ['q' => $q],
            'urls' => [
                'index' => route('admin.parametrage.departement.index'),
                'store' => $url('admin.parametrage.departement.store'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(self::REGLES);

        try {
            $validated['code_departement'] = generate('departement', 'code_departement', 50, 'a-zA-Z0-9');
            $validated['created_by'] = getInfoAdmin()->users->code ?? null;
            Departement::create($validated);

            return back()->with('success', 'Département créé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Problème survenu lors de l\'enregistrement : '.UserFacingErrors::caught($e));
        }
    }

    public function update(Request $request, $id)
    {
        $departement = Departement::where('id_departement', $id)->firstOrFail();
        $validated = $request->validate(self::REGLES);

        try {
            $validated['updated_by'] = getInfoAdmin()->users->code ?? null;
            $departement->update($validated);

            return back()->with('success', 'Département modifié avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Problème survenu lors de la modification : '.UserFacingErrors::caught($e));
        }
    }

    public function delete(Request $request, $id)
    {
        $departement = Departement::where('id_departement', $id)->firstOrFail();
        $code = $departement->code_departement;
        // Un département rattaché à des filières ou à des étudiants laisserait des références orphelines.
        if (($this->filieresParDepartement()[$code] ?? 0) + ($this->etudiantsParDepartement()[$code] ?? 0) > 0) {
            return back()->with('error', 'Ce département est utilisé par des filières ou des étudiants : rattachez-les à un autre département avant de le supprimer.');
        }

        try {
            $departement->delete();

            return back()->with('success', 'Département supprimé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Problème survenu lors de la suppression : '.UserFacingErrors::caught($e));
        }
    }

    private function filieresParDepartement()
    {
        return Filiere::query()->whereNotNull('departement_code')->selectRaw('departement_code, count(*) as total')->groupBy('departement_code')->pluck('total', 'departement_code');
    }

    private function etudiantsParDepartement()
    {
        return Etudiant::query()->whereNotNull('departement_code')->selectRaw('departement_code, count(*) as total')->groupBy('departement_code')->pluck('total', 'departement_code');
    }
}
