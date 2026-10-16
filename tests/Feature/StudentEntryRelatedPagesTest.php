<?php

namespace Tests\Feature;

use App\Http\Services\StudentContractApprovalService;
use App\Http\Services\StudentContractService;
use App\Models\Etudiant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Pages React liées à l'entrée dans le logement : approbation du contrat, documents de la résidence, réadmission. */
class StudentEntryRelatedPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $settings = new \App\Models\ConfigApp(['app_name' => 'Residence test']);
        $settings->setRelation('region', null)->setRelation('ville', null);
        $this->mock(\App\Repositories\Interfaces\ConfigAppRepositoryInterface::class)
            ->shouldReceive('getConfig')->andReturn($settings);
        $this->withoutMiddleware([\App\Http\Middleware\EnforceSystemSecurity::class, \App\Http\Middleware\LogUserActivity::class]);
        Schema::create('assignation_chambres', function (Blueprint $t) {
            $t->string('id_assignation')->primary(); $t->string('etudiant_id'); $t->string('chambre_id');
            $t->string('statut'); $t->timestamp('date_assignation'); $t->timestamp('date_fin')->nullable(); $t->timestamps();
        });
        Schema::create('chambre', function (Blueprint $t) { $t->string('id_chambre')->primary(); $t->softDeletes(); });
        DB::table('chambre')->insert(['id_chambre' => 'room']);
        (require database_path('migrations/2026_09_23_000002_create_student_contract_approvals.php'))->up();
        $this->mock(StudentContractService::class)->shouldReceive('html')->andReturn('<html><body>Contrat version 1</body></html>');
        $this->actingAs(new Etudiant(['id_etudiant' => 'student', 'name' => 'Étudiant test', 'matricule' => 'TEST']), 'etudiant');
    }

    /** Page Inertia : composant et props lus dans data-page (un middleware global reconstruit la réponse). */
    private function inertiaPage(string $url): array
    {
        $html = $this->get($url)->assertOk()->getContent();
        preg_match('/data-page="([^"]+)"/', $html, $matches);

        return json_decode(html_entity_decode($matches[1] ?? ''), true) ?? [];
    }

    public function test_contract_page_without_contract_explains_when_it_will_be_available(): void
    {
        $page = $this->inertiaPage(route('etudiants.contract.approval'));

        $this->assertSame('Etudiant/ApprobationContrat', $page['component'] ?? null);
        $this->assertNull($page['props']['contrat']);
        $this->assertSame(StudentContractApprovalService::CONSENT, $page['props']['consentement']);
    }

    public function test_contract_is_shown_then_approved_with_its_version(): void
    {
        DB::table('assignation_chambres')->insert(['id_assignation' => 'assignment', 'etudiant_id' => 'student', 'chambre_id' => 'room', 'statut' => 'confirmee', 'date_assignation' => now()]);

        $contrat = $this->inertiaPage(route('etudiants.contract.approval'))['props']['contrat'];
        $this->assertFalse($contrat['approuve']);
        $this->assertStringContainsString('Contrat version 1', $contrat['document']);

        $this->withHeaders(['X-Inertia' => 'true'])->post(route('etudiants.contract.approve'), [
            'contract_id' => $contrat['id'], 'document_hash' => $contrat['empreinte'],
        ])->assertSessionHasErrors('consent');
        $this->flushHeaders();
        $this->post(route('etudiants.contract.approve'), [
            'contract_id' => $contrat['id'], 'document_hash' => $contrat['empreinte'], 'consent' => 1,
        ])->assertRedirect(route('etudiants.contract.approval'));

        $approuve = $this->inertiaPage(route('etudiants.contract.approval'))['props']['contrat'];
        $this->assertTrue($approuve['approuve']);
        $this->assertSame(StudentContractApprovalService::CONSENT, $approuve['consentementDonne']);
    }

    public function test_residence_documents_page_lists_only_published_versions(): void
    {
        (require database_path('migrations/2026_09_20_000001_create_legal_documents_tables.php'))->up();
        foreach (['publie' => false, 'brouillon' => true] as $titre => $draft) {
            $id = DB::table('legal_documents')->insertGetId(['type' => 'reglement', 'title' => $titre, 'institution' => 'CROU', 'body' => '', 'status' => 'published', 'signatory_roles' => '[]']);
            $version = DB::table('legal_document_versions')->insertGetId(['legal_document_id' => $id, 'revision' => 1,
                'snapshot' => json_encode(['draft' => $draft, 'title' => $titre, 'type_label' => 'Règlement', 'reference' => 'REF-1', 'version_label' => '1.0', 'effective_date' => '01/10/2026'])]);
            DB::table('legal_documents')->where('id', $id)->update(['published_version_id' => $version]);
        }

        $page = $this->inertiaPage(route('etudiants.documents.index'));

        $this->assertSame('Etudiant/DocumentsResidence', $page['component'] ?? null);
        $this->assertSame(['publie'], array_column($page['props']['documents'], 'titre'));
        $this->assertSame('REF-1', $page['props']['documents'][0]['reference']);
    }
}
