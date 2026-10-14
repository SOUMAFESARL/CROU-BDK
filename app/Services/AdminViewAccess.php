<?php

namespace App\Services;

use App\Models\User;

class AdminViewAccess
{
    public function any(?User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($user?->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function allows(?User $user, string $block): bool
    {
        $rule = config('admin_view_permissions')[$block] ?? [];
        if (isset($rule['all'])) {
            foreach ($rule['all'] as $permission) {
                if (!$user?->hasPermission($permission)) {
                    return false;
                }
            }
            return $rule['all'] !== [];
        }
        return $this->any($user, $rule);
    }

    public function housing(?User $user): array
    {
        $access = [];
        foreach (['occupation', 'finance', 'maintenance', 'discipline', 'contracts', 'inspections', 'departures', 'announcements'] as $section) {
            $access[$section] = $this->allows($user, 'housing.'.$section);
        }

        return $access;
    }
}
