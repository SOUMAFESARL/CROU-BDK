<?php

namespace App\Http\Controllers\admin\immobilier\equipements;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\TypeEquipement;
use App\Models\EquipementMouvement;
use App\Models\Equipement;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use App\Services\AdminAccess;
use Carbon\Carbon;
use Inertia\Inertia;

class EquipementController extends Controller
{
    public function index(Request $request, AdminAccess $access)
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

        $admin = $request->user('admin');
        $url = fn (string $route, array $parametres = []) => $access->allows($admin, $route) ? route($route, $parametres) : null;
        $filtre = fn (string $statut) => route('admin.hebergement.equipements.index', array_filter([
            'statut' => $statut === 'tous' ? null : $statut, 'q' => $recherche ?: null, 'type' => $type ?: null,
        ]));

        return Inertia::render('Admin/Hebergement/Equipements/Index', [
            'equipements' => $paginees->through(fn (Equipement $materiel) => [
                ...$this->equipementProps($materiel),
                'urls' => [
                    'mouvements' => $url('admin.hebergement.equipements.mouvements', ['materiel' => $materiel->equipement_id]),
                    'destroy' => $url('admin.hebergement.equipements.destroy', ['materiel' => $materiel->equipement_id]),
                ],
            ]),
            'stats' => $stats,
            'stockTotal' => (float) $equipements->sum('stock_actuel'),
            'filters' => ['statut' => $statut, 'q' => $recherche, 'type' => $type],
            'types' => $types->map(fn ($item) => ['id' => (string) $item->id_type_equipement, 'name' => $item->name])->values(),
            'urls' => [
                'index' => route('admin.hebergement.equipements.index'),
                'filtres' => collect(['tous', 'disponible', 'faible', 'rupture'])->mapWithKeys(fn ($s) => [$s => $filtre($s)]),
                'affectations' => $url('admin.hebergement.equipements.affectations.index'),
                'storeType' => $url('admin.hebergement.parametrage-herb.type-equipement.store'),
                'store' => $url('admin.hebergement.equipements.store'),
                'update' => $url('admin.hebergement.equipements.update'),
                'storeMouvement' => $url('admin.hebergement.equipements.store_mouvement'),
            ],
        ]);
    }


    public function mouvements(Request $request, AdminAccess $access, Equipement $materiel)
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
        $admin = $request->user('admin');
        $url = fn (string $route, array $parametres = []) => $access->allows($admin, $route) ? route($route, $parametres) : null;

        return Inertia::render('Admin/Hebergement/Equipements/Historique', [
            'equipement' => [...$this->equipementProps($materiel), 'reference' => $materiel->reference],
            'stats' => [...$stats, 'dernier' => $stats['dernier'] ? Carbon::parse($stats['dernier'])->toDateString() : null],
            'type' => $type,
            'mouvements' => $materiel->mouvements()
                ->with('createdBy')
                ->when($type !== 'tous', fn ($query) => $query->where('type', $type))
                ->latest()
                ->paginate(15)
                ->withQueryString()
                ->through(fn (EquipementMouvement $mouvement) => [
                    'id' => (string) $mouvement->equipement_mouvement_id,
                    'date' => $mouvement->created_at?->format('Y-m-d H:i'),
                    'type' => $mouvement->type,
                    'quantite' => (float) $mouvement->quantity,
                    'motif' => $mouvement->reason,
                    'auteur' => $mouvement->createdBy->name ?? null,
                ]),
            'urls' => [
                'catalogue' => $url('admin.hebergement.equipements.index'),
                'storeMouvement' => $url('admin.hebergement.equipements.store_mouvement'),
                'filtres' => collect(['tous', 'entry', 'exit'])->mapWithKeys(fn ($filtre) => [$filtre => route('admin.hebergement.equipements.mouvements', array_filter([
                    'materiel' => $materiel->equipement_id, 'type' => $filtre === 'tous' ? null : $filtre,
                ]))]),
            ],
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
        // Validation hors du try : les erreurs de saisie reviennent champ par champ au formulaire.
        $validated = $request->validate([
            'type_equipement_id' => 'required|string|exists:type_equipement,id_type_equipement',
            'name' => 'required|string|max:150',
            'stock_initial' => 'required|integer|min:0',
            'prix' => 'nullable|numeric|min:0',
            'threshold' => 'required|integer|min:0',
            'date_reception' => 'nullable|date',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:10240',
        ]);
        unset($validated['photo']);

        try {
            $acteur = getInfoAdmin()->users->code;
            // Photo facultative : le helper `upload` lit $_FILES['photo'] et plante si aucun fichier n'est envoyé.
            $photo = $request->hasFile('photo') ? upload('equipement', 'png', $request, 'photo') : null;

            \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $photo, $acteur) {
                $equipement = Equipement::create([
                    ...$validated,
                    'prix' => $validated['prix'] ?? 0,
                    'stock_actuel' => $validated['stock_initial'],
                    'photo' => $photo,
                    'created_by' => $acteur,
                ]);

                if ($validated['stock_initial'] > 0) {
                    $equipement->mouvements()->create([
                        'type' => 'entry',
                        'quantity' => $validated['stock_initial'],
                        'reason' => 'Entrée de stock initiale',
                        'created_by' => $acteur,
                    ]);
                }
            });

            return back()->with('success', 'Équipement enregistré avec succès.');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Problème survenu lors de l\'enregistrement : ' . \App\Support\UserFacingErrors::caught($e)]);
        }
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'equipement_id' => 'required|string',
            'threshold' => 'required|integer|min:0',
            'name' => 'sometimes|required|string|max:150',
            'type_equipement_id' => 'required|string|exists:type_equipement,id_type_equipement',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:10240',
        ]);
        unset($validated['equipement_id'], $validated['photo']);

        try {
            $equipement = Equipement::where('equipement_id', $request['equipement_id'])->firstOrFail();

            $validated['photo'] = $request->hasFile('photo') ?  update('equipement', $equipement->photo,'png',$request,'photo') : $equipement->photo;

            $equipement->update($validated);
            return back()->with('success', 'Équipement modifié avec succès.');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Problème survenu lors de la modification : ' . \App\Support\UserFacingErrors::caught($e)]);
        }
    }

    /** Colonnes d'un équipement envoyées aux pages React (catalogue et historique). */
    private function equipementProps(Equipement $materiel): array
    {
        return [
            'id' => (string) $materiel->equipement_id,
            'name' => $materiel->name,
            'type' => $materiel->type_equipement->name ?? null,
            'type_id' => $materiel->type_equipement_id ? (string) $materiel->type_equipement_id : null,
            'photo' => $materiel->photo ?: null,
            'stock_initial' => (float) $materiel->stock_initial,
            'stock_actuel' => (float) $materiel->stock_actuel,
            'threshold' => (float) $materiel->threshold,
            'statut' => $this->statutStock($materiel),
        ];
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