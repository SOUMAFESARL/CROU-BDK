<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RedirectIfNotEtudiant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $guard = 'etudiant'): Response
    {
        // ✅ CORRECTION : Rediriger si l'utilisateur N'EST PAS connecté
        if (!Auth::guard($guard)->check()) {
            // Sauvegarder l'URL demandée pour redirection après login
            session()->put('url.intended', $request->url());
            return redirect()->route('etudiant.login')->with('error', 'Veuillez vous connecter pour accéder à cette page.');
        }


        return $next($request);
    }
}