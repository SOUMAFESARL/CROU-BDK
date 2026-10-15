<?php

namespace App\Http\Controllers\admin\immobilier\equipements;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\TypeEquipement;
use App\Models\EquipementMouvement;
use App\Models\Equipement;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class EquipementController extends Controller
{
    public function index(Request $request)
    {
        // Les mouvements sont chargés d'avance : stock_actuel les lit pour chaque équipement.
        $equipements = Equipement::with(['type_equipement', 'mouvements'])->orderBy('name')->get()
            ->each(function ($materiel) {
                $this->enrichirAvecStock($materiel);
            });

        // Indicateurs calculés sur tout le catalogue, avant les filtres.
        $stats = [
            'tous' => $equipements->count(),
            'disponible' => $equipements->filter(fn ($e) => $this->statutStock($e) === 'disponible')->count(),
            'faible' => $equipements->filter(fn ($e) => $this->statutStock($e) === 'faible')->count(),
            'rupture' => $equipements->filter(fn ($e) => $this->statutStock($e) === 'rupture')->count(),
        ];

        $recherche = trim((string) $request->query('q', ''));
        $statut = in_array($request->query('statut'), ['disponible', 'faible', 'rupture'], true) ? $request->query('statut') : 'tous';
        $types = TypeEquipement::orderBy('name', 'asc')->get();
        $type = $types->contains('id_type_equipement', $request->query('type')) ? (string) $request->query('type') : '';

        $normaliser = fn ($texte) => mb_strtolower(Str::ascii((string) $texte));
        $filtres = $equipements
            ->when($statut !== 'tous', fn ($liste) => $liste->filter(fn ($e) => $this->statutStock($e) === $statut))
            ->when($type !== '', fn ($liste) => $liste->where('type_equipement_id', $type))
            ->when($recherche !== '', fn ($liste) => $liste->filter(fn ($e) => str_contains(
                $normaliser($e->name.' '.($e->type_equipement->name ?? '')), $normaliser($recherche)
            )))
            ->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginees = new LengthAwarePaginator($filtres->forPage($page, 10)->values(), $filtres->count(), 10, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        return view('admin.hebergement.equipements.index', [
            'equipements' => $paginees,
            'stats' => $stats,
            'stockTotal' => $equipements->sum('stock_actuel'),
            'statut' => $statut,
            'recherche' => $recherche,
            'type' => $type,
            'type_equipement' => $types,
        ]);
    }


    public function mouvements(Request $request, Equipement $materiel)
    {
        $materiel->load(['type_equipement', 'mouvements']);
        $this->enrichirAvecStock($materiel);

        $tous = $materiel->mouvements;
        $stats = [
            'tous' => $tous->count(),
            'entry' => $tous->where('type', 'entry')->count(),
            'exit' => $tous->where('type', 'exit')->count(),
            'entrees' => (float) $tous->where('type', 'entry')->sum('quantity'),
            'sorties' => (float) $tous->where('type', 'exit')->sum('quantity'),
            'dernier' => $tous->max('created_at'),
        ];

        $type = in_array($request->query('type'), ['entry', 'exit'], true) ? $request->query('type') : 'tous';

        return view('admin.hebergement.equipements.historique', [
            'equipement' => $materiel,
            'stats' => $stats,
            'type' => $type,
            'mouvements' => $materiel->mouvements()
                ->with('createdBy')
                ->when($type !== 'tous', fn ($query) => $query->where('type', $type))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
        ]);
    }


    public function storeMouvement(Request $request)
    {
        $data = $request->validate([
            'equipement_id' => 'required|string', 'type' => 'required|in:entry,exit',
            'quantite' => 'required|numeric|min:0.01', 'motif' => 'nullable|string|max:255',
        ]);
        \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $equipment = Equipement::whereKey($data['equipement_id'])->lockForUpdate()->firstOrFail();
            if ($data['type'] === 'exit' && $equipment->stock_actuel < $data['quantite']) {
                throw \Illuminate\Validation\ValidationException::withMessages(['quantite' => 'Stock disponible insuffisant.']);
            }
            app(\App\Services\EquipmentAllocationService::class)->movement($equipment, $data['type'], (float) $data['quantite'], $data['motif'] ?? null, getInfoAdmin()->users->code);
        });
        return back()->with('success', 'Mouvement enregistré avec succès.');
    }
    public function destroy(Equipement $materiel)
    {
//dd($id);
        try {
            // return back()->with('errors',$id);
           // $materiel = Materiel::findOrFail($id);
            //dd( $materiel);
            // Vérifier s'il reste du stock
            if ($materiel->stock_actuel > 0) {

                return back()->withErrors(['error' =>"Impossible de supprimer: stock restant existant"]);
            }

            // Vérifier les mouvements associés
            if ($materiel->mouvements()->exists()) {

                return back()->withErrors(['error' =>"Supprimez d'abord les mouvements associés"]);
            }

            $materiel->delete();

            // Le formulaire de la page est un POST classique : on revient à la liste avec un message.
            return request()->expectsJson()
                ? response()->json(['success' => "Equipement supprimé avec succès!"], 200)
                : back()->with('success', 'Équipement supprimé avec succès.');

        } catch (\Exception $th) {
            report($th);
            return request()->expectsJson()
                ? response()->json(['errors' => "Erreur survenue lors de la suppression."], 400)
                : back()->withErrors(['error' => 'Erreur survenue lors de la suppression.']);
        }


    }

    public function store(Request $request)
    {
        try {


            $validated = $request->validate([
                'threshold' => 'required',
                'date_reception' =>'sometimes',
                'prix' => 'sometimes',
                'stock_initial' =>'sometimes',
                'name' =>'sometimes',
                'type_equipement_id'=> 'required',


            ]);


            $equipement = Equipement::firstOrCreate($validated, [
                'stock_actuel' => $validated['stock_initial'],
            'photo' => upload('equipement','png', $request, 'photo'),
            'created_by' => getInfoAdmin()->users->code
            ]);

            $data=[
                'equipement_id' => $equipement->equipement_id,
                'type' => 'entry',
                'quantity' => $validated['stock_initial'],
                'reason' => 'Entree de stock ',
                'created_by' => getInfoAdmin()->users->code
            ];




            $equipement->mouvements()->create($data);


            return back()->with('success', 'Equipement créé avec succès !');

        }catch (\Exception $e){
          //  dd($e->getMessage());
            return back()->withErrors(['error' => 'Probleme survenu lors de l\'enregistrement']);
        }
    }

    public function update(Request $request){

        try {
            $validated = $request->validate([
                'threshold' => 'required',



                'name' =>'sometimes',
                'type_equipement_id'=> 'required',


            ]);
            $equipement = Equipement::where('equipement_id', $request['equipement_id'])->firstOrFail();

            $validated['photo'] = $request->hasFile('photo') ?  update('equipement', $equipement->photo,'png',$request,'photo') : $equipement->photo;

            $equipement->update($validated);
            return back()->with('success', 'Equipement créé avec succès !');
        }catch (\Exception $e){
            report($e);
            return back()->withErrors(['error' => 'Probleme survenu lors de l\'enregistrement']);
        }
    }

    private function enrichirAvecStock(Equipement $materiel): void
    {
        $materiel->setStockStatus();
    }

    /** Clé du statut de stock (mêmes seuils que Equipement::setStockStatus()). */
    private function statutStock(Equipement $materiel): string
    {
        return match ($materiel->stock_status['text'] ?? 'Disponible') {
            'Rupture' => 'rupture',
            'Stock faible' => 'faible',
            default => 'disponible',
        };
    }




}