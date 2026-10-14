<?php



namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ville;
use App\Models\Cite;
use App\Models\Batiment;
use App\Models\Chambre;


class preferenceController extends Controller
{
    public function getVille(Request $request)
    {
        $city = Ville::where('region_id',$request->parent_id)->get();

        $sql = '<option style="color: black" value=""  selected>---Sélectionnez une ville---</option>';
      //  $sql ='';
        foreach ($city as $row) {
           $sql .= '<option style="color: black" value="' . $row->id . '"  >' . $row->name .'</option>';
           //  $sql.=' <option value="' . $row->id . '  "> ' . $row->name .'</option>';
        }

        return response()->json([
            'select_tag' => $sql

        ]);
    }

    public function getCiteOfCampus(Request $request)
    {
        $city = Cite::where('campus_id',$request->parent_id)->get();

        $sql = '<option style="color: black" value=""  selected>---Sélectionnez une cite---</option>';
        foreach ($city as $row) {
            $sql .= '<option style="color: black" value="' . $row->id_cite . '"  >' . $row->name .'</option>';
            // $sql.=' <option value="' . $row->nom_marque .' "></option>';
        }

        return response()->json([
            'select_tag' => $sql

        ]);
    }

    public function getBatimentOfCite(Request $request)
    {
        $batiments = Batiment::where('cite_id', $request->parent_id)->get();

        $sql = '<option value="" selected>---Sélectionnez un batiment---</option>';
        foreach ($batiments as $row) {
            // Utilisez 'code_batiment' au lieu de 'code'
            $sql .= '<option value="' . $row->id_batiment . '">' . $row->name . '</option>';
        }

        return response()->json([
            'select_tag' => $sql
        ]);
    }

    public function getChambreOfBatiment(Request $request)
    {
        $chambre = Chambre::where('batiment_id', $request->parent_id)->WhereNotNull('numero_chambre')->get();

        $sql = '<option value="" selected>---Sélectionnez une chambre---</option>';
        foreach ($chambre as $row) {
            // Utilisez 'code_batiment' au lieu de 'code'
            $sql .= '<option value="' . $row->id_chambre . '">' . $row->numero_chambre . '</option>';
        }

        return response()->json([
            'select_tag' => $sql
        ]);
    }
}