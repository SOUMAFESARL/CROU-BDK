<?php

namespace App\Http\Controllers\admin\parametrage;

use App\Http\Controllers\admin\immobilier\parametrage\ReferentielInertia;
use App\Http\Controllers\Controller;
use App\Models\NiveauEtude;
use App\Support\UserFacingErrors;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class NiveauEtudeController extends Controller
{
    use ReferentielInertia;

    public function index(Request $request)
    {
        $url = $this->urlSiAutorise($request);
        $query = NiveauEtude::query()->ordonnes();
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        if ($q !== '') {
            $query->where(fn (Builder $w) => $w->where('name', 'like', '%'.$q.'%')
                ->orWhere('abreviation', 'like', '%'.$q.'%')
                ->orWhere('description', 'like', '%'.$q.'%'));
        }
        $demandes = $this->demandesParNiveau();
        $total = NiveauEtude::count();
        // Rang de chaque niveau dans l'ordre complet (la recherche et la pagination n'en montrent qu'une partie).
        $rangs = NiveauEtude::query()->ordonnes()->pluck('id_niveau_etude')->flip();
        $utilises = NiveauEtude::pluck('id_niveau_etude')->filter(fn ($id) => ($demandes[$id] ?? 0) > 0)->count();

        return Inertia::render('Admin/Parametrage/NiveauxEtude', [
            'elements' => $query->paginate(10)->withQueryString()->through(fn (NiveauEtude $n) => [
                'id' => (string) $n->id_niveau_etude,
                'name' => $n->name,
                'abreviation' => $n->abreviation,
                'description' => $n->description,
                'demandes' => (int) ($demandes[$n->id_niveau_etude] ?? 0),
                'rang' => (int) $rangs[$n->id_niveau_etude] + 1,
                'premier' => $rangs[$n->id_niveau_etude] === 0,
                'dernier' => $rangs[$n->id_niveau_etude] === $total - 1,
                'urls' => [
                    'deplacer' => $url('admin.parametrage.niveau-etude.deplacer', $n->id_niveau_etude),
                    'update' => $url('admin.parametrage.niveau-etude.update', $n->id_niveau_etude),
                    'destroy' => $url('admin.parametrage.niveau-etude.delete', $n->id_niveau_etude),
                ],
            ]),
            'stats' => ['total' => $total, 'utilises' => $utilises, 'inutilises' => $total - $utilises],
            'filters' => ['q' => $q],
            'urls' => [
                'index' => route('admin.parametrage.niveau-etude.index'),
                'store' => $url('admin.parametrage.niveau-etude.store'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->regles(), ['name.unique' => 'Ce niveau d\'étude existe déjà.']);

        try {
            NiveauEtude::create($validated);

            return back()->with('success', 'Niveau d\'étude créé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Problème survenu lors de l\'enregistrement : '.UserFacingErrors::caught($e));
        }
    }

    public function update(Request $request, $id)
    {
        $niveau = NiveauEtude::where('id_niveau_etude', $id)->firstOrFail();
        $validated = $request->validate($this->regles($niveau->id_niveau_etude), ['name.unique' => 'Ce niveau d\'étude existe déjà.']);

        try {
            $validated['updated_by'] = getInfoAdmin()->users->id_user ?? null;
            $niveau->update($validated);

            return back()->with('success', 'Niveau d\'étude modifié avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Problème survenu lors de la modification : '.UserFacingErrors::caught($e));
        }
    }

    /** Monte ou descend un niveau d'un cran ; cet ordre est repris par les formulaires de demande et l'API. */
    public function deplacer(Request $request, $id)
    {
        $sens = $request->validate(['sens' => ['required', Rule::in(['haut', 'bas'])]], ['sens.*' => 'Sens de déplacement invalide.'])['sens'];
        $niveau = NiveauEtude::where('id_niveau_etude', $id)->firstOrFail();

        try {
            DB::transaction(function () use ($niveau, $sens) {
                $ids = NiveauEtude::query()->ordonnes()->lockForUpdate()->pluck('id_niveau_etude')->values()->all();
                $i = array_search($niveau->id_niveau_etude, $ids, true);
                $j = $sens === 'haut' ? $i - 1 : $i + 1;
                if (! isset($ids[$j])) {
                    return;
                }
                [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
                // Renumérotation complète 1..n : corrige au passage les ordres en double ou absents.
                foreach ($ids as $rang => $cle) {
                    NiveauEtude::where('id_niveau_etude', $cle)->update(['ordre' => $rang + 1]);
                }
            });

            return back();
        } catch (\Exception $e) {
            return back()->with('error', 'Problème survenu lors du changement d\'ordre :'.UserFacingErrors::caught($e));
        }
    }

    public function delete(Request $request, $id)
    {
        $niveau = NiveauEtude::where('id_niveau_etude', $id)->firstOrFail();
        if (($this->demandesParNiveau()[$niveau->id_niveau_etude] ?? 0) > 0) {
            return back()->with('error', 'Ce niveau d\'étude est cité dans des demandes de logement : il ne peut pas être supprimé.');
        }

        try {
            $niveau->delete();

            return back()->with('success', 'Niveau d\'étude supprimé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Problème survenu lors de la suppression : '.UserFacingErrors::caught($e));
        }
    }

    private function regles(?string $id = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('niveau_etude', 'name')->ignore($id, 'id_niveau_etude')],
            'abreviation' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** Demandes qui citent le niveau comme niveau actuel ou précédent (une demande comptée une fois par niveau). */
    private function demandesParNiveau()
    {
        $actuel = DB::table('demande')->whereNotNull('niveau_etude_actuel_code')->select('id_demande', 'niveau_etude_actuel_code as niveau');
        $niveaux = DB::table('demande')->whereNotNull('niveau_etude_precedent_code')->select('id_demande', 'niveau_etude_precedent_code as niveau')->union($actuel);

        return DB::query()->fromSub($niveaux, 'n')->selectRaw('niveau, count(*) as total')->groupBy('niveau')->pluck('total', 'niveau');
    }
}
