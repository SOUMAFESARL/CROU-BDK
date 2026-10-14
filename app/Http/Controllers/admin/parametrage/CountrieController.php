<?php

namespace App\Http\Controllers\admin\parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Countrie;



class CountrieController extends  Controller
{
    public function index()
    {
        $countrie = Countrie::all();
        return view('admin.parametrage.pays.index',compact('countrie'));
    }
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required',
                'code' => 'nullable|string',

            ]);
            Countrie::create($validated);

            return back()->with('success', 'Pays créé avec succès !');
        }catch (\Exception $e){
            return back()->withErrors(['error' => 'Probleme survenu lors de l\'enregistrement']);
        }


    }
    public function update(Request $request,$id)
    {
        try {
            $validated = $request->validate([
                'name' => 'required',
                'code' => 'nullable|string',

            ]);
            Countrie::findOrFail($id)->update($validated);

            return back()->with('success', 'Pays modifié avec succès !');
        }catch (\Exception $e){
            return back()->withErrors(['error' => 'Probleme survenu lors de la modification']);
        }

    }
    public function delete(Request $request,$id)
    {
        try {
            Countrie::findOrFail($id)->delete();

            return back()->with('success', 'Pays supprimé avec succès !');

        }catch (\Exception $e){
            return back()->withErrors(['error' => 'Probleme survenu lors de la suppression']);
        }

    }

}