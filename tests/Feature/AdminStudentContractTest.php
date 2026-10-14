<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceSystemSecurity;
use App\Http\Middleware\LogUserActivity;
use App\Models\ConfigApp;
use App\Models\Etudiant;
use App\Models\User;
use App\Repositories\Interfaces\ConfigAppRepositoryInterface;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminStudentContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->withoutMiddleware([EnforceSystemSecurity::class, LogUserActivity::class]);
        $settings = new ConfigApp(['app_name' => 'Résidence test',
            'logo' => 'file:///'.str_replace('\\', '/', public_path('files/assets/logo/logo.jpg'))]);
        $settings->setRelation('region', null)->setRelation('ville', null);
        $this->mock(ConfigAppRepositoryInterface::class)->shouldReceive('getConfig')->andReturn($settings);
        Schema::create('etudiants', function (Blueprint $t) {
            $t->string('id_etudiant')->primary(); $t->string('name'); $t->string('matricule');
            $t->date('date_naissance')->nullable(); $t->string('adresse')->nullable(); $t->softDeletes();
        });
        Schema::create('assignation_chambres', function (Blueprint $t) {
            $t->string('id_assignation')->primary(); $t->string('etudiant_id'); $t->string('chambre_id');
            $t->string('statut'); $t->dateTime('date_assignation'); $t->timestamps();
        });
        Schema::create('chambre', function (Blueprint $t) {
            $t->string('id_chambre')->primary(); $t->string('numero_chambre');
            $t->decimal('loyer')->default(15000); $t->softDeletes();
        });
        DB::table('etudiants')->insert([
            ['id_etudiant' => 'student-a', 'name' => 'Alice Test', 'matricule' => 'A001'],
            ['id_etudiant' => 'student-b', 'name' => 'Benoit Test', 'matricule' => 'B002'],
        ]);
        DB::table('chambre')->insert(['id_chambre' => 'room', 'numero_chambre' => 'A-12']);
    }

    private function url(string $id = 'student-a'): string
    {
        return route('admin.etudiant.contrat', ['etudiant' => $id]);
    }

    private function admin(): void
    {
        $this->actingAs(new User(['id_user' => 'admin-test', 'role_id' => 1]), 'admin');
    }

    private function assign(string $student = 'student-a', string $status = 'confirmee'): void
    {
        DB::table('assignation_chambres')->insert(['id_assignation' => uniqid(),
            'etudiant_id' => $student, 'chambre_id' => 'room', 'statut' => $status,
            'date_assignation' => '2026-09-01 00:00:00', 'created_at' => '2026-09-01 00:00:00']);
    }

    public function test_guest_and_student_cannot_access_admin_contracts(): void
    {
        $this->get($this->url())->assertRedirect();
        $this->actingAs(Etudiant::findOrFail('student-a'), 'etudiant');
        $this->get($this->url())->assertRedirect();
    }

    public function test_missing_student_returns_404(): void
    {
        $this->admin();
        $this->get($this->url('missing'))->assertNotFound();
    }

    public function test_no_confirmed_room_shows_explanation_instead_of_an_empty_contract(): void
    {
        $this->admin();
        $this->assign('student-a', 'annulee');
        $this->get($this->url())->assertNotFound()->assertSee('Contrat indisponible');
    }

    public function test_admin_opens_real_pdf_without_a_student_session(): void
    {
        $this->admin();
        $this->assign();
        $response = $this->get($this->url())->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('contrat-a001.pdf', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_requested_student_is_used_even_when_another_student_is_logged_in(): void
    {
        $this->admin();
        $this->actingAs(Etudiant::findOrFail('student-a'), 'etudiant');
        $this->assign('student-b');
        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        Pdf::shouldReceive('loadView')->once()->with('pdf.contrat', \Mockery::on(function ($data) {
            $this->assertSame('Benoit Test', $data['locataire_nom']);
            $this->assertSame('B002', $data['locataire_matricule']);
            $this->assertSame('A-12', $data['chambre_designation']);
            $this->assertSame('01/09/2026', $data['date_entree']);
            $this->assertSame('[DATE DE NAISSANCE]', $data['locataire_naissance']);
            return true;
        }))->andReturn($pdf);
        $pdf->shouldReceive('setPaper')->once()->with('a4', 'portrait');
        $pdf->shouldReceive('setOptions')->once();
        $pdf->shouldReceive('stream')->once()->with('contrat-b002.pdf')->andReturn(response('%PDF-test', 200, ['Content-Type' => 'application/pdf']));
        $this->get($this->url('student-b'))->assertOk();
    }

    public function test_contract_link_targets_admin_route_in_new_tab(): void
    {
        $html = view('admin.etudiant.contract-link', ['student' => Etudiant::findOrFail('student-a')])->render();
        $this->assertStringContainsString($this->url(), $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('Consulter le contrat', $html);
    }
}
