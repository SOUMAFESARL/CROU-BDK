<?php

namespace App\Http\Controllers\admin\immobilier\parametrage;

use App\Services\AdminAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/** Outils communs des pages React des référentiels du paramétrage hébergement (types, documents). */
trait ReferentielInertia
{
    /** URL d'une route admin, ou null si l'administrateur connecté n'y a pas accès (remplace `@adminroute`). */
    private function urlSiAutorise(Request $request): \Closure
    {
        $access = app(AdminAccess::class);
        $admin = $request->user('admin');

        return fn (string $route, mixed $parametres = []) => $access->allows($admin, $route) ? route($route, $parametres) : null;
    }

    /** Recherche `?q=` (nom ou description) ; renvoie le terme nettoyé. */
    private function rechercher(Request $request, Builder $query): string
    {
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        if ($q !== '') {
            $query->where(fn (Builder $w) => $w->where('name', 'like', '%'.$q.'%')->orWhere('description', 'like', '%'.$q.'%'));
        }

        return $q;
    }
}
