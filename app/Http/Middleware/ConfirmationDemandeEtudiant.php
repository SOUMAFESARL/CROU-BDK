<?php

namespace App\Http\Middleware;

use App\Services\EtudiantMenu;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tant que l'étudiant n'a pas confirmé (ou refusé) son dossier « Éligible », l'espace étudiant
 * se limite au tableau de bord, où la réponse est attendue, au profil et à la déconnexion.
 */
class ConfirmationDemandeEtudiant
{
    /** Routes accessibles en attendant la réponse de l'étudiant. */
    private const ROUTES_AUTORISEES = [
        'etudiants.validation-demande', 'etudiants.accepter-demande', 'etudiants.refuser-demande',
        'etudiants.dossiers.transition', 'etudiants.profile', 'etudiants.profile.*', 'etudiants.logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs(...self::ROUTES_AUTORISEES)
            || ($request->routeIs('etudiants.dashboard') && in_array($request->query('section'), [null, '', 'dashboard'], true))
            || ! EtudiantMenu::confirmationAttendue($request->user('etudiant'))) {
            return $next($request);
        }

        $message = 'Confirmez ou refusez d’abord votre demande d’hébergement depuis le tableau de bord.';

        return $request->expectsJson()
            ? response()->json(['success' => false, 'message' => $message], 403)
            : redirect()->route('etudiants.dashboard')->with('error', $message);
    }
}
