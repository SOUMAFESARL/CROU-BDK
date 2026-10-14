<?php

namespace App\Services;

use App\Models\User;

class AdminAccess
{
    public function allowsUrl(?User $user, string $url, string $method = 'GET'): bool
    {
        if (!$url || $url === '#') {
            return false;
        }

        try {
            $route = app('router')->getRoutes()->match(\Illuminate\Http\Request::create($url, $method));
            return str_starts_with((string) $route->getName(), 'admin.')
                && $this->allows($user, $route->getName());
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $exception) {
            return false;
        }
    }

    public function allows(?User $user, string $routeName): bool
    {
        if (!$user) {
            return false;
        }

        if (in_array($routeName, ['admin.users.edit', 'admin.users.update'], true)
            && !$user->hasPermission('utilisateurs.personal')) {
            return false;
        }

        if (in_array($routeName, ['admin.users.create', 'admin.users.store'], true)
            && !$user->hasPermission('parametrage.roles.assign')) {
            return false;
        }

        $rules = config('admin_permissions.routes', []);
        // New routes must be classified explicitly, even for administrators.
        if (!array_key_exists($routeName, $rules)) {
            return false;
        }

        if ($rules[$routeName] === []) {
            return true;
        }

        foreach ((array) $rules[$routeName] as $permission) {
            if ($user->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }
}
