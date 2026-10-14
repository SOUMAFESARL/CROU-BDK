<?php

namespace App\Http\Controllers\admin\immobilier\parametrage\batiments_campagne;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BatimentsCampagneController extends Controller
{

    public function index()
    {
        return view('admin.hebergement.parametrage-herb.batiments-campagne.index');
    }
}