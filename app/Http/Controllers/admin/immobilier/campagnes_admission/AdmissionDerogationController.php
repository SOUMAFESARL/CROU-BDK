<?php
namespace App\Http\Controllers\admin\immobilier\campagnes_admission;

use App\Http\Controllers\Controller;
use App\Http\Services\AdmissionDerogationService;
use Illuminate\Http\Request;

class AdmissionDerogationController extends Controller
{
    public function store(Request $request, string $student, AdmissionDerogationService $service)
    {
        $data = $request->validate(['demande_id' => 'required|string', 'reason' => 'required|string|max:2000']);
        $service->request(auth('admin')->user(), $student, $data['demande_id'], $data['reason']);
        return back()->with('success', 'Dérogation demandée. Un autre responsable habilité doit la valider.');
    }

    public function decide(Request $request, string $derogation, AdmissionDerogationService $service)
    {
        $data = $request->validate(['decision' => 'required|in:approve,reject', 'reason' => 'required|string|max:2000']);
        $service->decide(auth('admin')->user(), $derogation, $data['decision'] === 'approve', $data['reason']);
        return back()->with('success', $data['decision'] === 'approve'
            ? 'Dérogation approuvée. Le candidat peut poursuivre son admission ; les règles de disponibilité et de capacité restent applicables.'
            : 'Dérogation refusée.');
    }
}
