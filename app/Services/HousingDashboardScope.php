<?php

namespace App\Services;

use App\Models\{Chambre, Cite, User};
use Illuminate\Support\Facades\DB;

class HousingDashboardScope
{
    public function residences(User $user): ?array
    {
        $persona = config('housing_personas.personas.'.$user->role?->housing_persona);
        if (($persona['scope'] ?? 'global') !== 'residence') return null;
        return array_values(array_unique(array_merge(
            DB::table('housing_user_residences')->where('user_id', $user->getKey())->pluck('cite_id')->all(),
            Cite::where('responsable_id', $user->getKey())->pluck('id_cite')->all()
        )));
    }

    public function apply($query, ?array $residences, string $column = 'cite_id')
    {
        if ($residences === null) return $query;
        if ($column === 'chambre_id') {
            return $query->whereIn('chambre_id', Chambre::whereIn('cite_id', $residences)->select('id_chambre'));
        }
        return $query->whereIn($column, $residences);
    }
}
