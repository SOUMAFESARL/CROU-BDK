<?php

namespace App\Http\Middleware;

use App\Services\AdminAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $name = (string) $request->route()?->getName();
        if (in_array($name, config('admin_permissions.public', []), true)) {
            return $next($request);
        }

        $user = $request->user('admin');
        if (!$user) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Non authentifié.'], 401)
                : redirect()->route('admin.login');
        }

        if (!in_array($user->statut, ['active', 'en_attente', null], true)) {
            \Illuminate\Support\Facades\Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return $request->expectsJson()
                ? response()->json(['message' => 'Votre compte est désactivé.'], 403)
                : redirect()->route('admin.login')->withErrors(['email' => 'Votre compte est désactivé.']);
        }
        abort_unless(app(AdminAccess::class)->allows($user, $name), 403, 'Vous ne disposez pas de cette permission.');

        $response = $next($request);
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $response->setData(app(\App\Services\AdminViewData::class)->filter($response->getOriginalContent(), $user));
        }
        return $response;
    }
}
