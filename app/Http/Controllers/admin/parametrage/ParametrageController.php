<?php

namespace App\Http\Controllers\admin\parametrage;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ParametrageController extends  Controller
{
    public function index()
    {
        return view('admin.parametrage.index');
    }
}