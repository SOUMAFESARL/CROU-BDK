<?php

namespace App\Http\Controllers\admin\parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\NiveauEtude;



class NiveauEtudeController extends  Controller
{
    public function index()
    {
        $niveau = NiveauEtude::all();
        return view('admin.parametrage.niveau_etude.index',compact('niveau'));
    }
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required',
                'abreviation' => 'sometimes',
                'description' => 'sometimes',


            ]);



            NiveauEtude::create($validated);

            return back()->with('success', 'Niveau d\'etude créé avec succès !');

        } catch (\Exception $e){
        return back()->withErrors(['error' => 'Probleme survenu lors de l\'enregistrement']);
    }
    }
    public function update(Request $request,$id)
    {
        try {
            $validated = $request->validate([
                'name' => 'required',
                'abreviation' => 'sometimes',
                'description' => 'sometimes',


            ]);
           
            NiveauEtude::where('id_niveau_etude',$id)->first()->update($validated);

            return back()->with('success', 'Niveau d\'etude modifié avec succès !');
        } catch (\Exception $e){
        return back()->withErrors(['error' => 'Probleme survenu lors de la modification']);
    }
    }
    public function delete(Request $request,$id)
    {
        try {
            NiveauEtude::where('id_niveau_etude',$id)->first()->delete();

            return back()->with('success', 'Niveau d\'etude  supprimé avec succès !');
        }


    catch (\Exception $e){
        return back()->withErrors(['error' => 'Probleme survenu lors de la suppression']);
    }
    }

}