<?php
namespace App\Http\Controllers\etudiant;
use App\Http\Controllers\Controller;
use App\Http\Services\StudentContractApprovalService;
use Illuminate\Http\Request;
class ContractApprovalController extends Controller {
    public function show(StudentContractApprovalService $service) {
        $contract = $service->current(auth('etudiant')->user());
        abort_unless($contract, 404, 'Aucun contrat disponible.');
        return response()->view('etudiant.contract-approval', compact('contract'))->header('Cache-Control', 'private, no-store');
    }
    public function store(Request $request, StudentContractApprovalService $service) {
        $data = $request->validate(['contract_id'=>'required|uuid', 'document_hash'=>'required|string|size:64', 'consent'=>'accepted']);
        $service->approve(auth('etudiant')->user(), $data['contract_id'], $data['document_hash']);
        return redirect()->route('etudiants.contract.approval')->with('success', 'Votre approbation du contrat a été enregistrée.');
    }
}
