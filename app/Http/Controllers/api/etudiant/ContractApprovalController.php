<?php

namespace App\Http\Controllers\api\etudiant;

use App\Http\Controllers\Controller;
use App\Http\Services\{StudentContractApprovalService, StudentContractService};
use App\Models\StudentContractApproval;
use Illuminate\Http\Request;

class ContractApprovalController extends Controller
{
    public function current(Request $request, StudentContractApprovalService $service)
    {
        $contract = $service->current($request->user());
        abort_unless($contract, 404, 'Aucun contrat disponible pour votre logement actuel.');
        return $this->response($contract);
    }

    public function approve(Request $request, string $contract, StudentContractApprovalService $service)
    {
        // Ownership is checked before examining the submitted consent.
        StudentContractApproval::where('student_id', $request->user()->getKey())->findOrFail($contract);
        $data = $request->validate([
            'document_hash' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'],
            'consent' => 'required|accepted',
        ]);
        return $this->response($service->approve($request->user(), $contract, $data['document_hash']));
    }

    public function pdf(Request $request, string $contract, StudentContractService $service)
    {
        $approval = StudentContractApproval::where('student_id', $request->user()->getKey())->findOrFail($contract);
        return $service->streamApproval($approval, 'contrat-'.$approval->getKey().'.pdf');
    }

    public function legacyPdf(Request $request, StudentContractService $service, ?string $id = null)
    {
        abort_if($id !== null && (string) $id !== (string) $request->user()->getKey(), 404);
        return $service->stream($request->user());
    }

    private function response(StudentContractApproval $contract)
    {
        return response()->json(['success' => true, 'data' => [
            'id' => $contract->getKey(), 'assignment_id' => $contract->assignment_id,
            'student_name' => $contract->student_name,
            'document_hash' => $contract->document_hash, 'document_html' => $contract->document_html,
            'approved' => $contract->approved_at !== null,
            'approved_at' => $contract->approved_at?->toISOString(),
            'consent_text' => $contract->consent_text ?? StudentContractApprovalService::CONSENT,
            'pdf_url' => route('api.student.contracts.pdf', $contract->getKey()),
            'approval_url' => route('api.student.contracts.approve', $contract->getKey()),
        ]])->header('Cache-Control', 'private, no-store');
    }
}
