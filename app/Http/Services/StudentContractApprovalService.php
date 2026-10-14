<?php
namespace App\Http\Services;
use App\Models\{Etudiant, StudentContractApproval};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudentContractApprovalService
{
    public const CONSENT = 'Je confirme avoir lu le contrat de logement présenté et en approuver les conditions.';

    public function current(Etudiant $student): ?StudentContractApproval
    {
        return DB::transaction(function () use ($student) {
            $assignment = $student->assignationsChambre()->where('statut', 'confirmee')
                ->orderByDesc('date_assignation')->orderByDesc('created_at')->lockForUpdate()->first();
            if (!$assignment || !$assignment->chambre || ($assignment->date_fin && $assignment->date_fin->lte(now()))) return null;
            $contract = StudentContractApproval::where('assignment_id', $assignment->getKey())->first();
            if ($contract) {
                abort_unless((string)$contract->student_id === (string)$student->getKey(), 404);
                if (!$contract->approved_at) {
                    $html = app(StudentContractService::class)->html($student);
                    if ($html === null) return null;
                    $contract->update(['document_html'=>$html, 'document_hash'=>hash('sha256', $html), 'student_name'=>$student->name]);
                }
                return $contract;
            }
            $html = app(StudentContractService::class)->html($student);
            if ($html === null) return null;
            return StudentContractApproval::create([
                'id'=>(string)Str::uuid(), 'assignment_id'=>$assignment->getKey(),
                'student_id'=>$student->getKey(), 'student_name'=>$student->name,
                'document_html'=>$html, 'document_hash'=>hash('sha256', $html),
            ]);
        }, 3);
    }

    public function approve(Etudiant $student, string $id, string $hash): StudentContractApproval
    {
        return DB::transaction(function () use ($student, $id, $hash) {
            $assignment = $student->assignationsChambre()->where('statut', 'confirmee')
                ->orderByDesc('date_assignation')->orderByDesc('created_at')->lockForUpdate()->first();
            $contract = StudentContractApproval::where('student_id', $student->getKey())->whereKey($id)
                ->lockForUpdate()->firstOrFail();
            if (!$assignment || (string)$assignment->getKey() !== (string)$contract->assignment_id
                || ($assignment->date_fin && $assignment->date_fin->lte(now()))) {
                throw ValidationException::withMessages(['contract'=>'Ce contrat ne correspond plus à votre logement actuel.']);
            }
            if (!hash_equals($contract->document_hash, $hash)) {
                throw ValidationException::withMessages(['contract'=>'Le contrat présenté ne correspond pas à cette version. Consultez-le à nouveau.']);
            }
            if (!$contract->approved_at) {
                $html = app(StudentContractService::class)->html($student);
                if ($html === null || !hash_equals($contract->document_hash, hash('sha256', $html))) {
                    throw ValidationException::withMessages(['contract'=>'Le contrat a changé. Consultez la nouvelle version avant de l’approuver.']);
                }
                $contract->update(['approved_at'=>now(), 'consent_text'=>self::CONSENT]);
            }
            return $contract;
        }, 3);
    }
}
