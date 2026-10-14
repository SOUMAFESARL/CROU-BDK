<?php

namespace App\Http\Controllers\admin\immobilier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ImmobilierController extends Controller
{
    public function index()
    {

        return view('admin.immobilier.parc-immobilier.index');
    }
}
