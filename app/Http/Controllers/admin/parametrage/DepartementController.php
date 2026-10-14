<?php

namespace App\Http\Controllers\admin\parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Departement;



class DepartementController extends  Controller
{
    public function index()
    {
        $departement = Departement::all();
        return view('admin.parametrage.departement.index',compact('departement'));
    }
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required',
                'abreviation' => 'sometimes',
                'description' => 'sometimes',


            ]);

            $validated['code_departement'] = generate('departement','code_departement',50,'a-zA-Z0-9');
            $validated['created_by'] = getInfoAdmin()->users->code;
            Departement::create($validated);

            return back()->with('success', 'Departement créé avec succès !');

        }catch (\Exception $e){
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
            $validated['updated_by'] = getInfoAdmin()->users->code;
            Departement::where('id_departement',$id)->first()->update($validated);

            return back()->with('success', 'Departement modifié avec succès !');

        }catch (\Exception $e){
        return back()->withErrors(['error' => 'Probleme survenu lors de la modification']);
    }
    }
    public function delete(Request $request,$id)
    {
        try {
            Departement::where('id_departement',$id)->first()->delete();

            return back()->with('success', 'Departement supprimé avec succès !');


        }catch (\Exception $e){
        return back()->withErrors(['error' => 'Probleme survenu lors de la suppression']);
        }
    }

}