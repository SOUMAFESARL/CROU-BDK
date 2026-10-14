<?php

namespace App\Http\Controllers\admin\immobilier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class dasboardController extends Controller
{
    public function index()
    {
        return view('admin.immobilier.index');
    }
}
