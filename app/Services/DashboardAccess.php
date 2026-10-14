<?php

namespace App\Services;

use App\Models\User;

class DashboardAccess
{
    public function forUser(User $user): array
    {
        $role = $user->role;
        $admin = (int) $user->role_id === 1;
        $permissions = $role ? $role->permissions()->whereHas('applicationModule', fn ($q) => $q->where('is_active', true))->pluck('slug')->all() : [];
        $can = fn (string $permission) => $admin || in_array($permission, $permissions, true);
        $persona = config('housing_personas.personas.'.$role?->housing_persona);

        return [
            'roleName' => $role?->name ?? 'Aucun rôle attribué',
            'level' => $persona['level'] ?? ($admin ? 'Stratégique' : 'Personnalisé'),
            'scope' => $persona['scope'] ?? 'global',
            'persona' => $role?->housing_persona,
            'students' => $can('hebergement.admissions.view'),
            'housing' => $can('hebergement.parc.view') || $can('hebergement.residents.view'),
            'parc' => $can('hebergement.parc.view'),
            'admissions' => $can('hebergement.admissions.view'),
            'finance' => $can('hebergement.paiements.view'),
            'maintenance' => $can('hebergement.maintenance.view'),
            'discipline' => $can('hebergement.discipline.view'),
            'readmissions' => $can('hebergement.readmissions.view'),
            'departures' => $can('hebergement.departs.view'),
            'announcements' => $can('hebergement.annonces.view'),
            'reporting' => $can('hebergement.reporting.view'),
        ];
    }
}
