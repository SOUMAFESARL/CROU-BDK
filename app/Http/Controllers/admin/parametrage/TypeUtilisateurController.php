<?php

namespace App\Http\Controllers\admin\parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TypeUtilisateur;



class TypeUtilisateurController extends  Controller
{
    public function index()
    {
        $typeUtilisateur = TypeUtilisateur::all();
        return view('admin.parametrage.type-utilisateur.index',compact('typeUtilisateur'));
    }
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required',


            ]);
            TypeUtilisateur::create($validated);

            return back()->with('success', 'Type utilisateur créé avec succès !');
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


            ]);
            TypeUtilisateur::findOrFail($id)->update($validated);

            return back()->with('success', 'Type utilisateur modifié avec succès !');
        }

    catch (\Exception $e){
        return back()->withErrors(['error' => 'Probleme survenu lors de la modification']);
    }
    }
    public function delete(Request $request,$id)
    {
        try {
            TypeUtilisateur::findOrFail($id)->delete();


            return back()->with('success', 'Type utilisateur supprimé avec succès !');
        }
    catch (\Exception $e){
        return back()->withErrors(['error' => 'Probleme survenu lors de la suppression']);
    }
    }

}