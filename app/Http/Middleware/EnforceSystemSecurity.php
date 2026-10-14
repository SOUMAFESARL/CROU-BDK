<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceSystemSecurity
{
    public function handle(Request $request, Closure $next): Response
    {
        if (systemConfig('ip_restriction', false)) {
            $allowed = collect(preg_split('/[\s,;]+/', (string) systemConfig('allowed_ips', '')))->filter();
            if ($allowed->isNotEmpty()) {
                abort_unless($allowed->contains($request->ip()), 403, 'Cette adresse IP n’est pas autorisée.');
            }
        }

        if (systemConfig('force_https', false) && !$request->secure()) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
