<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RedirectIfEdudiant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $guard = 'etudiant'): Response
    {

        if (Auth::guard($guard)->check()) {
           // dd('12345678990qqq');
            return redirect()->route('etudiants.dashboard');
   //   return to_route('etudiants.dashboard');
        }
        return $next($request);
    }
}
