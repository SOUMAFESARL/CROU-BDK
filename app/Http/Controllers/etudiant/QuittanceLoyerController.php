<?php

namespace App\Http\Controllers\etudiant;

use App\Http\Controllers\Controller;
use App\Services\EtudiantPaiements;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * Quittance de loyer d'un mois payé du séjour (Mes paiements › Mes loyers) : aperçu dans le navigateur,
 * ou téléchargement avec `?telecharger=1`. Un mois non payé, hors séjour ou d'un autre étudiant renvoie 404.
 */
class QuittanceLoyerController extends Controller
{
    public function show(Request $request, EtudiantPaiements $paiements, string $mois)
    {
        $etudiant = $request->attributes->get('student') ?? auth('etudiant')->user();
        abort_unless($etudiant, 403);
        $quittance = $paiements->quittance($etudiant, $mois);
        abort_unless($quittance, 404, 'Aucune quittance pour ce mois : le loyer n’est pas entièrement payé.');

        $pdf = Pdf::loadView('pdf.quittance-loyer', $quittance)
            ->setPaper('a4')
            ->setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false]);
        $fichier = 'quittance-loyer-'.$mois.'.pdf';

        return ($request->boolean('telecharger') ? $pdf->download($fichier) : $pdf->stream($fichier))
            ->header('Cache-Control', 'private, no-store');
    }
}
