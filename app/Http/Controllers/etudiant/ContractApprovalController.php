<?php
namespace App\Http\Controllers\etudiant;
use App\Http\Controllers\Controller;
use App\Http\Services\StudentContractApprovalService;
use Illuminate\Http\Request;
use Inertia\Inertia;
class ContractApprovalController extends Controller {
    public function show(StudentContractApprovalService $service) {
        $contract = $service->current(auth('etudiant')->user());

        return Inertia::render('Etudiant/ApprobationContrat', [
            // Sans contrat en cours, la page explique quand il sera disponible (lien permanent depuis « Mon entrée »).
            'contrat' => $contract ? [
                'id' => $contract->id,
                'empreinte' => $contract->document_hash,
                'document' => $contract->document_html,
                'approuve' => (bool) $contract->approved_at,
                'dateApprobation' => $contract->approved_at?->format('d/m/Y à H:i'),
                'consentementDonne' => $contract->consent_text,
            ] : null,
            'consentement' => StudentContractApprovalService::CONSENT,
            'liens' => [
                'pdf' => route('etudiants.pdfContrat'),
                'approuver' => route('etudiants.contract.approve'),
                'entree' => route('etudiants.housing-entry'),
                'documents' => route('etudiants.documents.personal'),
            ],
        ])->toResponse(request())->header('Cache-Control', 'private, no-store');
    }
    public function store(Request $request, StudentContractApprovalService $service) {
        $data = $request->validate(['contract_id'=>'required|uuid', 'document_hash'=>'required|string|size:64', 'consent'=>'accepted']);
        $service->approve(auth('etudiant')->user(), $data['contract_id'], $data['document_hash']);
        return redirect()->route('etudiants.contract.approval')->with('success', 'Votre approbation du contrat a été enregistrée.');
    }
}
