<?php

namespace App\Http\Middleware;

use App\Services\AdminMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'admin.inertia';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => fn () => [
                'user' => ($user = Auth::guard('admin')->user()) ? [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role?->name,
                    'avatar' => $user->photo ?: asset('files/assets/images/avatar-4.jpg'),
                ] : null,
                'profileUrl' => route('admin.profile.show'),
                'logoutUrl' => route('admin.logout'),
            ],
            'menu' => fn () => app(AdminMenu::class)->forUser(Auth::guard('admin')->user(), $request),
            // Cookie écrit par la barre latérale (React et Blade) : 'false' = repliée.
            'sidebarOpen' => $request->cookie('sidebar_state') !== 'false',
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
