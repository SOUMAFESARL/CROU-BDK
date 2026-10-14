<?php
namespace Tests\Feature;

use App\Http\Services\StudentContractService;
use App\Models\StudentContractApproval;
use App\Models\StudentApiToken;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\TestCase;

class StudentContractApiTest extends TestCase
{
    private string $html = '<html><body>Contrat version 1</body></html>';

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);
        DB::purge('sqlite');
        $this->withoutMiddleware(\App\Http\Middleware\LogUserActivity::class);
        Schema::create('etudiants', function (Blueprint $t) {
            $t->string('id_etudiant')->primary(); $t->string('name'); $t->integer('statut'); $t->softDeletes();
        });
        DB::table('etudiants')->insert(['id_etudiant'=>'student','name'=>'Étudiant test','statut'=>1]);
        Schema::create('assignation_chambres', function (Blueprint $t) {
            $t->string('id_assignation')->primary(); $t->string('etudiant_id'); $t->string('chambre_id');
            $t->string('statut'); $t->timestamp('date_assignation'); $t->timestamp('date_fin')->nullable(); $t->timestamps();
        });
        Schema::create('chambre', function (Blueprint $t) { $t->string('id_chambre')->primary(); $t->softDeletes(); });
        DB::table('chambre')->insert(['id_chambre'=>'room']);
        DB::table('assignation_chambres')->insert(['id_assignation'=>'assignment','etudiant_id'=>'student',
            'chambre_id'=>'room','statut'=>'confirmee','date_assignation'=>now()]);
        (require database_path('migrations/2026_09_23_000002_create_student_contract_approvals.php'))->up();
        (require database_path('migrations/2026_08_27_100000_create_student_api_tokens_table.php'))->up();
        StudentApiToken::create(['etudiant_id'=>'student','token_hash'=>hash('sha256','contract-token')]);
        $this->withToken('contract-token');
        $this->mock(StudentContractService::class)->makePartial()->shouldReceive('html')->andReturnUsing(fn () => $this->html);
    }

    private function current(): array
    {
        return $this->getJson('/api/V1/contrats/actuel')->assertOk()->json('data');
    }

    public function test_bearer_contract_approval_is_versioned_and_idempotent(): void
    {
        $contract = $this->current();
        $this->assertSame(hash('sha256', $this->html), $contract['document_hash']);
        $this->assertSame($this->html, $contract['document_html']);
        $this->assertFalse($contract['approved']);
        $payload = ['document_hash'=>$contract['document_hash'],'consent'=>true];
        $response = $this->postJson($contract['approval_url'], $payload)->assertOk()->assertJsonPath('data.approved', true);
        $date = $response->json('data.approved_at');
        $this->travel(1)->hours();
        $this->html = '<html><body>Version modifiée</body></html>';
        $this->patchJson($contract['approval_url'], $payload)->assertOk()->assertJsonPath('data.approved_at', $date);
        $this->assertSame($contract['document_html'], $this->current()['document_html']);
        $this->assertSame(1, StudentContractApproval::count());
    }

    public function test_consent_and_current_document_hash_are_required(): void
    {
        $contract = $this->current();
        $this->postJson($contract['approval_url'], ['document_hash'=>$contract['document_hash']])->assertUnprocessable();
        $this->postJson($contract['approval_url'], ['document_hash'=>'invalid','consent'=>true])->assertUnprocessable();
        $this->postJson($contract['approval_url'], ['document_hash'=>$contract['document_hash'],'consent'=>false])->assertUnprocessable();
        $this->html = '<html><body>Nouvelles conditions</body></html>';
        $this->postJson($contract['approval_url'], ['document_hash'=>$contract['document_hash'],'consent'=>true])->assertUnprocessable();
        $fresh = $this->current();
        $this->postJson($fresh['approval_url'], ['document_hash'=>$fresh['document_hash'],'consent'=>true])->assertOk();
    }

    public function test_another_students_contract_cannot_be_downloaded_or_approved(): void
    {
        $contract = $this->current();
        $foreign = StudentContractApproval::create(['id'=>(string)\Illuminate\Support\Str::uuid(),
            'assignment_id'=>'foreign-assignment','student_id'=>'other','student_name'=>'Autre étudiant',
            'document_html'=>$this->html,'document_hash'=>$contract['document_hash']]);
        $this->postJson('/api/V1/contrats/'.$foreign->id.'/approuver', [])->assertNotFound();
        $this->getJson('/api/V1/contrats/'.$foreign->id.'/pdf')->assertNotFound();
        $this->getJson('/api/V1/contrat/other')->assertNotFound();
        $this->getJson('/api/V1/campagne/contrat/other')->assertNotFound();
    }

    public function test_expired_assignment_is_not_presented_as_current_or_approved(): void
    {
        $contract = $this->current();
        DB::table('assignation_chambres')->update(['date_fin'=>now()->subDay()]);
        $this->getJson('/api/V1/contrats/actuel')->assertNotFound();
        $this->postJson($contract['approval_url'], ['document_hash'=>$contract['document_hash'],'consent'=>true])->assertUnprocessable();
    }

    public function test_contract_and_legacy_routes_require_bearer_authentication(): void
    {
        $this->withoutToken();
        foreach (['/contrats/actuel','/contrat','/contrat/student','/campagne/contrat/student','/contrats/unknown/pdf'] as $path) {
            $this->getJson('/api/V1'.$path)->assertUnauthorized();
        }
        $this->postJson('/api/V1/contrats/unknown/approuver', [])->assertUnauthorized();
    }

    public function test_pdf_uses_the_requested_owned_snapshot(): void
    {
        $contract = $this->current();
        app(StudentContractService::class)->shouldReceive('streamApproval')->once()
            ->with(\Mockery::on(fn ($model) => $model->getKey() === $contract['id']), 'contrat-'.$contract['id'].'.pdf')
            ->andReturn(response('%PDF-test')->header('Content-Type','application/pdf')->header('Cache-Control','private, no-store'));
        $this->get($contract['pdf_url'])->assertOk()->assertHeader('Content-Type','application/pdf');
    }
}
