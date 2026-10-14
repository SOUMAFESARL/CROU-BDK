<?php
namespace Tests\Feature;
use App\Http\Services\{StudentContractApprovalService, StudentContractService};
use App\Models\{Etudiant, StudentContractApproval};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StudentContractApprovalTest extends TestCase
{
    private string $html = '<html><body>Contrat version 1</body></html>';
    private Etudiant $student;
    protected function setUp(): void {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);
        DB::purge('sqlite');
        Schema::create('assignation_chambres', function(Blueprint $t) {
            $t->string('id_assignation')->primary(); $t->string('etudiant_id'); $t->string('chambre_id');
            $t->string('statut'); $t->timestamp('date_assignation'); $t->timestamp('date_fin')->nullable(); $t->timestamps();
        });
        Schema::create('chambre', function(Blueprint $t) { $t->string('id_chambre')->primary(); $t->softDeletes(); });
        DB::table('chambre')->insert(['id_chambre'=>'room']);
        DB::table('assignation_chambres')->insert(['id_assignation'=>'assignment','etudiant_id'=>'student','chambre_id'=>'room','statut'=>'confirmee','date_assignation'=>now()]);
        (require database_path('migrations/2026_09_23_000002_create_student_contract_approvals.php'))->up();
        $this->student=new Etudiant(['id_etudiant'=>'student','name'=>'Etudiant']);
        $this->mock(StudentContractService::class)->shouldReceive('html')->andReturnUsing(fn()=>$this->html);
    }
    public function test_approval_keeps_the_version_and_original_date_on_repeated_submission(): void {
        $service=app(StudentContractApprovalService::class);
        $contract=$service->current($this->student);
        $this->assertNull($contract->approved_at);
        $approved=$service->approve($this->student,$contract->id,$contract->document_hash);
        $date=$approved->approved_at;
        $this->assertSame($this->html,$approved->document_html);
        $this->assertSame(StudentContractApprovalService::CONSENT,$approved->consent_text);
        $this->travel(2)->hours();
        $this->html='<html>Modified after approval</html>';
        $this->assertTrue($service->approve($this->student,$contract->id,$contract->document_hash)->approved_at->equalTo($date));
        $this->assertSame($contract->document_html,$service->current($this->student)->document_html);
        $this->assertSame(1,StudentContractApproval::count());
    }
    public function test_another_student_cannot_approve_the_contract(): void {
        $service=app(StudentContractApprovalService::class); $c=$service->current($this->student);
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $service->approve(new Etudiant(['id_etudiant'=>'other']),$c->id,$c->document_hash);
    }
    public function test_changes_since_preview_require_a_new_review(): void {
        $service=app(StudentContractApprovalService::class); $c=$service->current($this->student);
        $this->html='<html>New terms</html>';
        $this->expectException(ValidationException::class);
        $service->approve($this->student,$c->id,$c->document_hash);
    }
    public function test_expired_assignment_cannot_be_approved(): void {
        $service=app(StudentContractApprovalService::class); $c=$service->current($this->student);
        DB::table('assignation_chambres')->update(['date_fin'=>now()->subDay()]);
        $this->expectException(ValidationException::class);
        $service->approve($this->student,$c->id,$c->document_hash);
    }
    public function test_missing_confirmed_assignment_has_no_contract(): void {
        DB::table('assignation_chambres')->update(['statut'=>'annulee']);
        $this->assertNull(app(StudentContractApprovalService::class)->current($this->student));
    }
    public function test_consent_is_mandatory(): void {
        $c=app(StudentContractApprovalService::class)->current($this->student);
        $request=\Illuminate\Http\Request::create('/','POST',['contract_id'=>$c->id,'document_hash'=>$c->document_hash]);
        $this->expectException(ValidationException::class);
        app(\App\Http\Controllers\etudiant\ContractApprovalController::class)->store($request,app(StudentContractApprovalService::class));
    }
}
