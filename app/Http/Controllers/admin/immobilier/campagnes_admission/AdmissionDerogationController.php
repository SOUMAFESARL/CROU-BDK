<?php
namespace App\Http\Controllers\admin\immobilier\campagnes_admission;

use App\Http\Controllers\Controller;
use App\Http\Services\AdmissionDerogationService;
use Illuminate\Http\Request;

class AdmissionDerogationController extends Controller
{
    /** Bouton « Déroger » de la fiche du dossier : la demande irrecevable ou refusée est rendue recevable (motif obligatoire). */
    public function store(Request $request, string $student, AdmissionDerogationService $service)
    {
        $data = $request->validate(['motif' => 'required|string|max:2000'], [
            'motif.required' => 'Le motif de la dérogation est obligatoire.',
            'motif.max' => 'Le motif ne doit pas dépasser 2 000 caractères.',
        ]);
        $service->deroger(auth('admin')->user(), $student, $data['motif']);

        return back()->with('success', 'Demande dérogée : elle passe en tête de la liste provisoire. Les règles de disponibilité et de capacité restent applicables.');
    }

    /** Décision sur une dérogation demandée avant le bouton « Déroger » (ancien circuit à deux responsables). */
    public function decide(Request $request, string $derogation, AdmissionDerogationService $service)
    {
        $data = $request->validate(['decision' => 'required|in:approve,reject', 'reason' => 'required|string|max:2000']);
        $service->decide(auth('admin')->user(), $derogation, $data['decision'] === 'approve', $data['reason']);
        return back()->with('success', $data['decision'] === 'approve'
            ? 'Dérogation approuvée : la demande devient recevable. Les règles de disponibilité et de capacité restent applicables.'
            : 'Dérogation refusée.');
    }
}
