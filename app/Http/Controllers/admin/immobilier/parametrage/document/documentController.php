<?php

namespace App\Http\Controllers\admin\immobilier\parametrage\document;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\admin\immobilier\parametrage\ReferentielInertia;
use App\Models\Document;
use App\Models\DocumentCampagne;
use Inertia\Inertia;

class documentController extends  Controller
{
    use ReferentielInertia;

    public function __construct(){}
    /** Filtres des indicateurs (`?statut=`), une valeur inconnue est ignorée. */
    private const FILTRES = ['actifs', 'obligatoires', 'inactifs'];

    public function index(Request $request)
    {
        $url = $this->urlSiAutorise($request);
        $statut = in_array($request->query('statut'), self::FILTRES, true) ? $request->query('statut') : 'tous';
        $query = Document::query()->orderBy('ordre_affiche')->orderBy('name');
        $q = $this->rechercher($request, $query);
        match ($statut) {
            'actifs' => $query->where('statut', 1),
            'inactifs' => $query->where('statut', 0),
            'obligatoires' => $query->where('champs_requis', 1),
            default => null,
        };
        $campagnes = DocumentCampagne::query()->selectRaw('document_id, count(distinct id_campagne) as total')->groupBy('document_id')->pluck('total', 'document_id');

        return Inertia::render('Admin/Hebergement/Parametrage/Documents', [
            'documents' => $query->paginate(10)->withQueryString()->through(fn (Document $document) => [
                'id' => (string) $document->id_document,
                'name' => $document->name,
                'description' => $document->description,
                'ordre_affiche' => $document->ordre_affiche !== null ? (int) $document->ordre_affiche : null,
                'delai_validite_mois' => $document->delai_validite_mois !== null ? (int) $document->delai_validite_mois : null,
                'requis' => (bool) $document->champs_requis,
                'actif' => (bool) $document->statut,
                'campagnes' => (int) ($campagnes[$document->id_document] ?? 0),
                'urls' => [
                    'update' => $url('admin.hebergement.parametrage-herb.document.update', ['document' => $document->id_document]),
                ],
            ]),
            'stats' => [
                'total' => Document::count(),
                'actifs' => Document::where('statut', 1)->count(),
                'obligatoires' => Document::where('champs_requis', 1)->count(),
                'inactifs' => Document::where('statut', 0)->count(),
            ],
            'filters' => ['q' => $q, 'statut' => $statut],
            'urls' => [
                'index' => route('admin.hebergement.parametrage-herb.document.index'),
                'store' => $url('admin.hebergement.parametrage-herb.document.store'),
            ],
        ]);
    }

    public function store(Request $request)
    {
       // dd($request);
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'ordre_affiche' => 'nullable|string',
            'delai_validite_mois'=> 'nullable|string',
            'champs_requis'=> 'nullable|string',
            'description'=> 'nullable|string',
        ]);
        $validated['champs_requis'] = ($validated['champs_requis'] ?? null) === 'on' ? 1 : 0;
       $document = Document::create($validated);

        return back()->with('success', 'Document créé avec succès !');
    }

    public function update(Request $request, $document)
    {


        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'ordre_affiche' => 'nullable|string',
            'delai_validite_mois'=> 'sometimes',
            'champs_requis'=> 'nullable|string',
            'statut'=> 'nullable|string',
            'description'=> 'nullable|string',
        ]);

        $validated['champs_requis'] = ($validated['champs_requis'] ?? null) === 'on' ? 1 : 0;
        $validated['statut'] = ($validated['statut'] ?? null) === 'on' ? 1 : 0;

    // dd($document);

        // Récupérer le document existant
        $document = Document::where('id_document', $document)->firstOrFail();

        // Mettre à jour
        $document->update($validated);

        return back()->with('success', 'Document modifié avec succès !');
    }

}