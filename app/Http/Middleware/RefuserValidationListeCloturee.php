<?php

namespace App\Http\Middleware;

use App\Http\Services\Eligibilite\VerrouListeProvisoire;
use App\Models\DemandeEtudiant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Changement d'état d'un dossier par un agent (`admin.dossiers.transition`) : la validation (`eligible`) d'une demande
 * d'une liste provisoire est refusée dès que la liste des demandes validées de son volet a été approuvée en commission
 * (`VerrouListeProvisoire`). Les autres transitions (mise en attente, rejet…) ne sont pas concernées.
 */
class RefuserValidationListeCloturee
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->input('etat') === 'eligible') {
            $demande = $request->route('demande');
            $demande = $demande instanceof DemandeEtudiant ? $demande : DemandeEtudiant::find($demande);
            if ($demande) {
                app(VerrouListeProvisoire::class)->verifier($demande);
            }
        }

        return $next($request);
    }
}
