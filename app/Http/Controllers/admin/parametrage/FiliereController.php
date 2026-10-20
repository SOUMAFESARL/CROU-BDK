<?php

namespace App\Http\Controllers\admin\parametrage;

use App\Http\Controllers\admin\immobilier\parametrage\ReferentielInertia;
use App\Http\Controllers\Controller;
use App\Models\DemandeEtudiant;
use App\Models\Departement;
use App\Models\Filiere;
use App\Support\UserFacingErrors;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class FiliereController extends Controller
{
    use ReferentielInertia;

    public function index(Request $request)
    {
        $url = $this->urlSiAutorise($request);
        $departements = Departement::query()->orderBy('name')->get(['code_departement', 'name', 'abreviation']);
        $query = Filiere::query()->with('departement')->orderBy('name');
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        if ($q !== '') {
            $query->where(fn (Builder $w) => $w->where('name', 'like', '%'.$q.'%')
                ->orWhere('description', 'like', '%'.$q.'%')
                ->orWhereHas('departement', fn (Builder $d) => $d->where('name', 'like', '%'.$q.'%')->orWhere('abreviation', 'like', '%'.$q.'%')));
        }
        // ?departement= : code du département, « aucun » pour les filières non rattachées.
        $departement = (string) $request->query('departement', '');
        if ($departement === 'aucun') {
            $query->where(fn (Builder $w) => $w->whereNull('departement_code')->orWhere('departement_code', ''));
        } elseif ($departements->contains('code_departement', $departement)) {
            $query->where('departement_code', $departement);
        } else {
            $departement = '';
        }
        $etudiants = $this->etudiantsParFiliere();
        $total = Filiere::count();
        $utilises = Filiere::pluck('code_filiere')->filter(fn ($code) => ($etudiants[$code] ?? 0) > 0)->count();

        return Inertia::render('Admin/Parametrage/Filieres', [
            'elements' => $query->paginate(10)->withQueryString()->through(fn (Filiere $f) => [
                'id' => (string) $f->id_filiere,
                'name' => $f->name,
                'description' => $f->description,
                'departement_code' => $f->departement_code ?? '',
                'departement' => $f->departement?->name,
                'etudiants' => (int) ($etudiants[$f->code_filiere] ?? 0),
                'urls' => [
                    'update' => $url('admin.parametrage.filiere.update', $f->id_filiere),
                    'destroy' => $url('admin.parametrage.filiere.delete', $f->id_filiere),
                ],
            ]),
            'departements' => $departements->map(fn (Departement $d) => [
                'code' => $d->code_departement,
                'name' => $d->name,
                'abreviation' => $d->abreviation,
            ])->values(),
            'stats' => ['total' => $total, 'utilises' => $utilises, 'inutilises' => $total - $utilises],
            'filters' => ['q' => $q, 'departement' => $departement],
            'urls' => [
                'index' => route('admin.parametrage.filiere.index'),
                'store' => $url('admin.parametrage.filiere.store'),
                'departements' => $url('admin.parametrage.departement.index'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->regles());

        try {
            $validated['code_filiere'] = generate('filiere', 'code_filiere', 50, 'a-zA-Z0-9');
            $validated['created_by'] = getInfoAdmin()->users->code ?? null;
            Filiere::create($validated);

            return back()->with('success', 'Filière créée avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Problème survenu lors de l\'enregistrement : '.UserFacingErrors::caught($e));
        }
    }

    public function update(Request $request, $id)
    {
        $filiere = Filiere::where('id_filiere', $id)->firstOrFail();
        $validated = $request->validate($this->regles());

        try {
            $validated['updated_by'] = getInfoAdmin()->users->code ?? null;
            $filiere->update($validated);

            return back()->with('success', 'Filière modifiée avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Problème survenu lors de la modification : '.UserFacingErrors::caught($e));
        }
    }

    public function delete(Request $request, $id)
    {
        $filiere = Filiere::where('id_filiere', $id)->firstOrFail();
        if (($this->etudiantsParFiliere()[$filiere->code_filiere] ?? 0) > 0) {
            return back()->with('error', 'Cette filière est suivie par des étudiants : modifiez leur filière avant de la supprimer.');
        }

        try {
            $filiere->delete();

            return back()->with('success', 'Filière supprimée avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Problème survenu lors de la suppression : '.UserFacingErrors::caught($e));
        }
    }

    private function regles(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'departement_code' => ['nullable', 'string', Rule::exists('departement', 'code_departement')],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    // La filière d'un étudiant est portée par sa demande (demande.filiere_code), pas par la table etudiants.
    private function etudiantsParFiliere()
    {
        return DemandeEtudiant::query()->whereNotNull('filiere_code')->whereHas('etudiant')
            ->selectRaw('filiere_code, count(distinct etudiant_id) as total')->groupBy('filiere_code')->pluck('total', 'filiere_code');
    }
}
