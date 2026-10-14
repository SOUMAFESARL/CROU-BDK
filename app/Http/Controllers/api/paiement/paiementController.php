<?php

namespace App\Http\Controllers\api\paiement;

use App\Http\Controllers\Controller;
use App\Models\Loyer;
use App\Models\Paiement;
use Illuminate\Http\Request;

class paiementController extends Controller
{
    public function getPaiementData(Request $request, ?string $etudiant_id = null)
    {
        $id = (string) $request->user()->getKey();
        abort_if($etudiant_id !== null && $etudiant_id !== $id, 404);
        $filters = $request->validate(['per_page' => 'sometimes|integer|min:1|max:100', 'page' => 'sometimes|integer|min:1']);
        $loyers = Loyer::where('etudiant_id', $id)->where('statut', '!=', 'annule')
            ->orderByDesc('annee_facturation')->orderByDesc('mois_facturation')->get();
        $payments = Paiement::where('resident_id', $id)->where('statut_paiement', '!=', 'annule')
            ->latest()->orderByDesc('id_paiement')->paginate($filters['per_page'] ?? 10)->withQueryString();
        $payments->getCollection()->each(function ($payment) {
            $payment->setAttribute('receipt_url', in_array($payment->statut_paiement, ['valide', 'rembourse'], true)
                ? route('api.student.documents.receipt', $payment->getKey()) : null);
        });
        $expected = $loyers->sum('montant_total');
        $paid = $loyers->sum('total_paye');
        $arrears = $loyers->where('solde_restant', '>', 0)->values();
        $next = $arrears->sortBy(fn ($rent) => sprintf('%04d-%02d', $rent->annee_facturation, $rent->mois_facturation))->first();
        return response()->json([
            'etudiant' => $request->user()->only(['id_etudiant', 'name', 'matricule']),
            'loyers' => $loyers, 'paiements' => $payments,
            'stats' => [
                'total_attendu' => $expected, 'total_paye' => $paid,
                'total_impayes' => $arrears->sum('solde_restant'), 'total_penalites' => $loyers->sum('total_penalites'),
                'nb_paiements' => $payments->total(), 'nb_loyers' => $loyers->count(), 'nb_impayes' => $arrears->count(),
                'taux_recouvrement' => $expected > 0 ? round($paid / $expected * 100, 1) : 0,
                'statut_global' => $arrears->isNotEmpty() ? 'En retard' : 'À jour',
            ],
            'impayes' => $arrears,
            'prochainPaiement' => $next ? [
                'loyer_id' => $next->getKey(), 'mois' => $next->mois_facturation,
                'annee' => $next->annee_facturation,
                'libelle' => sprintf('%02d-%04d', $next->mois_facturation, $next->annee_facturation),
            ] : null,
        ])->header('Cache-Control', 'private, no-store');
    }
}
