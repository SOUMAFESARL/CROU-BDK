<?php

namespace App\Http\Controllers\admin\parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Region;



class RegionController extends  Controller
{
    public function index()
    {
        $region = Region::all();
        return view('admin.parametrage.regions.index',compact('region'));
    }
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required',


            ]);
            Region::create($validated);

            return back()->with('success', 'Region créé avec succès !');
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
            Region::firstOrFail($id)->update($validated);

            return back()->with('success', 'Region modifié avec succès !');
        }

    catch (\Exception $e){
        return back()->withErrors(['error' => 'Probleme survenu lors de la modification']);
    }
    }
    public function delete(Request $request,$id)
    {
        try {
            Region::findOrFail($id)->delete();

            return back()->with('success', 'Region supprimé avec succès !');
        }

    catch (\Exception $e){
        return back()->withErrors(['error' => 'Probleme survenu lors de la suppression']);
    }
    }

}