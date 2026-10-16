<?php

namespace App\Http\Controllers\admin\parametrage;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ParametrageController extends  Controller
{
    /** Accueil du paramétrage : modules du sous-menu « Paramètres » (config/admin_menu.php), filtrés par droits. */
    public function index(Request $request)
    {
        return \Inertia\Inertia::render('Admin/Parametrage/Index', [
            'modules' => app(\App\Services\AdminMenu::class)->childrenOf($request->user('admin'), 'admin.parametrage.index', $request),
        ]);
    }
}