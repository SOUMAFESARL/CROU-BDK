<?php

namespace App\Http\Middleware;

use App\Models\Etudiant;
use App\Models\StudentApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateStudentApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();
        if (! $plainToken) {
            return response()->json(['success' => false, 'message' => 'Token d’authentification manquant.'], 401);
        }

        $token = StudentApiToken::query()
            ->where('token_hash', hash('sha256', $plainToken))
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->first();

        $student = $token ? Etudiant::find($token->etudiant_id) : null;
        if (! $student || (int) $student->statut !== 1) {
            return response()->json(['success' => false, 'message' => 'Token invalide ou expiré.'], 401);
        }

        $token->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('student', $student);
        $request->setUserResolver(fn () => $student);

        return $next($request);
    }
}
