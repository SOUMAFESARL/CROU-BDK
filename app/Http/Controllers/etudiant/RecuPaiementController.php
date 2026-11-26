<?php

namespace App\Http\Controllers\etudiant;

use App\Http\Controllers\Controller;
use App\Models\Paiement;
use App\Services\RecuPaiement;
use Illuminate\Http\Request;

/**
 * Reçu d'un paiement de l'étudiant (Mes paiements › Historique), le même que celui de l'administration :
 * aperçu dans le navigateur, ou téléchargement avec `?telecharger=1`. Seuls ses paiements validés ou remboursés.
 */
class RecuPaiementController extends Controller
{
    public function show(Request $request, RecuPaiement $recu, string $paiement)
    {
        $etudiant = $request->attributes->get('student') ?? auth('etudiant')->user();
        abort_unless($etudiant, 403);
        $paiement = Paiement::where('resident_id', $etudiant->getKey())
            ->whereIn('statut_paiement', ['valide', 'rembourse'])->findOrFail($paiement);

        return $recu->reponse($paiement, $request->boolean('telecharger'))->header('Cache-Control', 'private, no-store');
    }
}
