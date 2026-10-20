<?php

namespace App\Http\Controllers\admin\immobilier\parametrage;

use App\Services\AdminAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    /** Nombre de lignes qui référencent chaque identifiant, cumulé sur plusieurs `[table, colonne]`. */
    private function references(array $sources): Collection
    {
        $totaux = [];
        foreach ($sources as [$table, $colonne]) {
            $lignes = DB::table($table)->whereNotNull($colonne)->selectRaw($colonne.' as cle, count(*) as total')->groupBy($colonne)->pluck('total', 'cle');
            foreach ($lignes as $cle => $n) {
                $totaux[(string) $cle] = ($totaux[(string) $cle] ?? 0) + (int) $n;
            }
        }

        return collect($totaux);
    }
}
