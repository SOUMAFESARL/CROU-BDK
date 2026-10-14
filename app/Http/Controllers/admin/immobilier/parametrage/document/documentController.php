<?php

namespace App\Http\Controllers\admin\immobilier\parametrage\document;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;

class documentController extends  Controller
{
    public function __construct(){}
    public function index()
    {
        $document = Document::getAllDocuments();

       //dd($document);

        return view('admin.hebergement.parametrage-herb.document.document',compact('document'));

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