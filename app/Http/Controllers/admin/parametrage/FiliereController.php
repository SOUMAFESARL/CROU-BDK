<?php

namespace App\Http\Controllers\admin\parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Departement;
use App\Models\Filiere;



class FiliereController extends  Controller
{
    public function index()
    {
        $filiere = Filiere::with('departement')->get();
        $departement = Departement::all();
        return view('admin.parametrage.filiere.index',compact('filiere','departement'));
    }
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required',
                'departement_code' => 'sometimes',
                'description' => 'sometimes',


            ]);

            $validated['code_filiere'] = generate('filiere','code_filiere',50,'a-zA-Z0-9');
            $validated['created_by'] = getInfoAdmin()->users->code;
            Filiere::create($validated);

            return back()->with('success', 'Filiere créé avec succès !');
        }


    catch (\Exception $e){
        return back()->withErrors(['error' => 'Probleme survenu lors de l\'enregistrement']);
    }
    }
    public function update(Request $request,$id)
    {
        try {
            $validated = $request->validate([
                'name' => 'required',
                'departement_code' => 'sometimes',
                'description' => 'sometimes',


            ]);
            $validated['updated_by'] = getInfoAdmin()->users->code;
            Filiere::where('id_filiere',$id)->first()->update($validated);

            return back()->with('success', 'Filiere modifié avec succès !');
        }

    catch (\Exception $e){
        return back()->withErrors(['error' => 'Probleme survenu lors de la modification']);
    }
    }
    public function delete(Request $request,$id)
    {
        try {
            Filiere::where('id_filiere',$id)->first()->delete();

            return back()->with('success', 'Filiere supprimé avec succès !');
        }

        catch (\Exception $e){
            return back()->withErrors(['error' => 'Probleme survenu lors de la suppression']);
        }
    }

}