<?php

namespace App\Http\Controllers\admin\immobilier\parametrage\type_chambre;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\admin\immobilier\parametrage\ReferentielInertia;
use App\Models\Chambre;
use App\Models\TypeChambre;
use Inertia\Inertia;

class TypeChambreController extends Controller
{
    use ReferentielInertia;

    /** Équipements proposés à la case à cocher (mêmes libellés que l'ancien formulaire, clés du JSON `equipement`). */
    public const EQUIPEMENTS = ['Chaise', 'Table', 'Courant', 'Eau', 'Cuisine', 'Placard', 'Matelas', 'Prise', 'Douche', 'WC', 'Climatiseur', 'Ventilateur'];

    /**
     * Afficher la liste des types de chambre
     */
    public function index(Request $request)
    {
        $url = $this->urlSiAutorise($request);
        $query = TypeChambre::query()->orderBy('name');
        $q = $this->rechercher($request, $query);
        // Seules les chambres numérotées sont des chambres réelles (les autres sont des gabarits de niveau).
        $chambres = Chambre::query()->whereNotNull('numero_chambre')->selectRaw('type_chambre_id, count(*) as total')->groupBy('type_chambre_id')->pluck('total', 'type_chambre_id');
        $total = TypeChambre::count();

        return Inertia::render('Admin/Hebergement/Parametrage/TypesChambre', [
            'types' => $query->paginate(10)->withQueryString()->through(fn (TypeChambre $type) => [
                'id' => (string) $type->id_type_chambre,
                'name' => $type->name,
                'description' => $type->description === '—' ? null : $type->description,
                'superficie' => $type->superficie !== null ? (int) $type->superficie : null,
                'montant' => (int) $type->montant,
                'avance' => (int) $type->avance,
                'caution' => (int) $type->caution,
                // Stocké en JSON {"Chaise":"Chaise", …} : seules les clés cochées comptent.
                'equipements' => array_values(array_keys(array_filter((array) (json_decode((string) $type->equipement, true) ?: [])))),
                'chambres' => (int) ($chambres[$type->id_type_chambre] ?? 0),
                'urls' => [
                    'update' => $url('admin.hebergement.parametrage-herb.type-chambre.update', $type->id_type_chambre),
                    'destroy' => $url('admin.hebergement.parametrage-herb.type-chambre.destroy', $type->id_type_chambre),
                ],
            ]),
            'stats' => [
                'total' => $total,
                'chambres' => (int) $chambres->only(TypeChambre::pluck('id_type_chambre')->all())->sum(),
                'loyer_min' => $total ? (int) TypeChambre::min('montant') : null,
                'loyer_max' => $total ? (int) TypeChambre::max('montant') : null,
            ],
            'equipementsDisponibles' => self::EQUIPEMENTS,
            'filters' => ['q' => $q],
            'urls' => [
                'index' => route('admin.hebergement.parametrage-herb.type-chambre.index'),
                'store' => $url('admin.hebergement.parametrage-herb.type-chambre.store'),
            ],
        ]);
    }

    /**
     * Enregistrer un nouveau type de chambre
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'montant' => 'nullable|integer',
            'superficie' => 'nullable|integer',
            'avance' => 'nullable|integer',
            'caution' => 'nullable|integer',
            'equipement' => 'sometimes|array',
        ]);

        if(isset($validated['equipement'])){
            $validated['equipement'] = json_encode($validated['equipement']);
        }

        TypeChambre::create($validated);

        return back()->with('success', 'Type de chambre créé avec succès !');
    }

    /**
     * Mettre à jour un type de chambre
     */
    public function update(Request $request, $id)
    {
        $typeChambre = TypeChambre::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'montant' => 'nullable|integer',
            'superficie' => 'nullable|integer',
            'avance' => 'nullable|integer',
            'caution' => 'nullable|integer',
            'equipement' => 'sometimes|array',
        ]);

        if(isset($validated['equipement'])){
            $validated['equipement'] = json_encode($validated['equipement']);
        }

        $typeChambre->update($validated);

        return back()->with('success', 'Type de chambre modifié avec succès !');
    }

    /**
     * Supprimer un type de chambre
     */
    public function destroy($id)
    {
        $typeChambre = TypeChambre::findOrFail($id);
        $typeChambre->delete();

        return back()->with('success', 'Type de chambre supprimé avec succès !');
    }

    // NOTE: Les méthodes create() et edit() ont été supprimées car on utilise des modals
}