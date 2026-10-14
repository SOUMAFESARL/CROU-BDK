<?php

namespace App\Http\Controllers\admin\immobilier\parametrage\quotas_campagne;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class QuotasCampagneController extends Controller
{

    public function index()
    {
        return view('admin.hebergement.parametrage-herb.quotas-campagne.index');
    }
}