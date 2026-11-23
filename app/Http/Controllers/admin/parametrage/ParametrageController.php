<?php

namespace App\Http\Controllers\admin\parametrage;


use App\Http\Controllers\Controller;
use App\Services\AdminMenu;
use Illuminate\Http\Request;

class ParametrageController extends  Controller
{
    /** Accueil du paramétrage : modules du sous-menu « Paramètres » (config/admin_menu.php), filtrés par droits. */
    public function index(Request $request)
    {
        $user = $request->user('admin');
        $modules = app(AdminMenu::class)->childrenOf($user, 'admin.parametrage.index', $request);

        return \Inertia\Inertia::render('Admin/Parametrage/Index', [
            'modules' => $modules,
        ]);
    }
}