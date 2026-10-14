<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = Auth::guard('admin')->user();
        abort_unless($user, 401);

        abort_unless($user->hasPermission($permission), 403, 'Vous ne disposez pas de cette permission.');

        return $next($request);
    }
}
