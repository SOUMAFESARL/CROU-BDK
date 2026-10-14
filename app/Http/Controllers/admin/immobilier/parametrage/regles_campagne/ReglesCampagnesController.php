<?php

namespace App\Http\Controllers\admin\immobilier\parametrage\regles_campagne;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReglesCampagnesController extends Controller
{

    public function index()
    {
        return view('admin.hebergement.parametrage-herb.regles-campagne.index');
    }
}