<?php

namespace Tests\Feature;

use App\Models\Etudiant;
use App\Models\User;
use App\Services\AdminViewAccess;
use App\Services\AdminViewData;
use App\Services\HousingReportingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SharedAdminViewsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
    }

    private function actor(array $permissions): User
    {
        return new class($permissions) extends User {
            public function __construct(private array $granted = []) { parent::__construct(['id_user' => 'actor', 'role_id' => 2]); }
            public function hasPermission(string $permission): bool { return in_array($permission, $this->granted, true); }
        };
    }

    /** Props Inertia du tableau de bord « Vie résidentielle » pour l'administrateur connecté. */
    private function vieResidentielle(\App\Http\Services\OrdreTravailService $service): array
    {
        $request = \Illuminate\Http\Request::create('/admin/gestion-hebergement/vie-residentielle');
        $request->headers->set('X-Inertia', 'true');
        $page = (new \App\Http\Controllers\admin\immobilier\vie_residentielle\VieResidentielleController($service))->index()
            ->toResponse($request)->getData(true);
        $this->assertSame('Admin/Hebergement/VieResidentielle/Index', $page['component']);

        return $page['props'];
    }

    public function test_page_entry_does_not_grant_its_sections(): void
    {
        $actor = $this->actor(['hebergement.vie-residentielle.view']);
        $this->assertTrue(app(\App\Services\AdminAccess::class)->allows($actor, 'admin.hebergement.vie_residentielle.index'));
        $this->assertNotContains(true, app(AdminViewAccess::class)->housing($actor));
        $this->assertFalse(app(AdminViewAccess::class)->allows($actor, 'unknown.section'));
        $this->actingAs($actor, 'admin');
        $service = \Mockery::mock(\App\Http\Services\OrdreTravailService::class);
        $service->shouldNotReceive('getAll');
        DB::enableQueryLog();
        $props = $this->vieResidentielle($service);
        $this->assertSame([], $props['residences']);
        $this->assertSame([], $props['kpis']);
        $this->assertSame([], $props['ordresRecents']);
        $this->assertSame([], DB::getQueryLog());
    }

    public function test_finance_profile_only_receives_its_tab_and_no_other_panels(): void
    {
        foreach (['cite' => ['id_cite', 'name'], 'paiements' => ['id_paiement', 'cite_id', 'type_paiement', 'statut_paiement', 'montant'],
            'cautions' => ['id_caution', 'cite_id', 'statut', 'montant_retenu'], 'loyers' => ['id_loyer', 'cite_id', 'statut', 'montant_total', 'solde_restant'],
            'ordres_travail' => ['id_ordres_travail', 'cite_id', 'cout_reel']] as $table => $colonnes) {
            Schema::create($table, function ($t) use ($colonnes) {
                foreach ($colonnes as $colonne) {
                    $t->string($colonne)->nullable();
                }
                $t->timestamps();
                $t->softDeletes();
            });
        }
        DB::table('cite')->insert(['id_cite' => 'c1', 'name' => 'Cité Bondoukou']);
        DB::table('loyers')->insert(['id_loyer' => 'l1', 'cite_id' => 'c1', 'statut' => 'en_retard', 'montant_total' => 20000, 'solde_restant' => 5000]);

        $actor = $this->actor(['hebergement.paiements.view']);
        $this->actingAs($actor, 'admin');
        $service = \Mockery::mock(\App\Http\Services\OrdreTravailService::class);
        $service->shouldNotReceive('getAll');
        $props = $this->vieResidentielle($service);

        $this->assertSame(['finance'], array_keys(array_filter($props['viewAccess'])));
        $this->assertSame(['recouvrement', 'impayes'], array_column($props['kpis'], 'key'));
        $this->assertEquals(75, $props['kpis'][0]['value']);
        // Seules les colonnes financières de la cité sont envoyées au navigateur.
        $this->assertSame(['id', 'name', 'recovery_rate', 'unpaid_rate', 'finance'], array_keys($props['residences'][0]));
        $this->assertSame([], $props['ordresRecents']);
        $this->assertSame(['paiements'], array_column($props['raccourcis'], 'key'));
        $this->assertSame([null], array_unique(array_values($props['synthese'])));
    }

    public function test_report_permissions_filter_modules_and_sections_before_queries(): void
    {
        $actor = $this->actor(['hebergement.reporting.view', 'hebergement.maintenance.view']);
        $service = new HousingReportingService;
        $this->assertSame(['vie-cite'], array_keys($service->visibleModules($actor)));
        DB::enableQueryLog();
        $this->assertSame([], $service->reports('finances', now()->startOfMonth(), now(), null, $actor));
        $this->assertSame([], DB::getQueryLog());
        $reports = $service->reports('vie-cite', now()->startOfMonth(), now(), null, $actor);
        $this->assertSame(['Maintenance'], array_column($reports['vie-cite']['sections'], 'title'));
    }

    public function test_personal_fields_and_hidden_relations_are_removed_from_json_without_mutating_models(): void
    {
        $student = new Etudiant(['id_etudiant' => 'student', 'name' => 'Visible name', 'telephone1' => 'PRIVATE_PHONE', 'email1' => 'PRIVATE_EMAIL']);
        $student->setRelation('documents', collect([['file' => 'PRIVATE_DOCUMENT']]));
        $student->setRelation('demandes', collect([['motif' => 'PRIVATE_HISTORY']]));
        $user = new User(['id_user' => 'user', 'name' => 'Agent', 'email' => 'PRIVATE_USER_EMAIL', 'password' => 'PRIVATE_PASSWORD']);
        $filtered = app(AdminViewData::class)->filter(['student' => $student, 'user' => $user], $this->actor([]));
        $json = json_encode($filtered);
        $this->assertStringContainsString('Visible name', $json);
        $this->assertStringNotContainsString('PRIVATE_', $json);
        $this->assertSame('PRIVATE_PHONE', $student->telephone1);
        $this->assertCount(1, $student->documents);
        $raw = app(AdminViewData::class)->filter($student->toArray(), $this->actor([]));
        $this->assertStringNotContainsString('PRIVATE_', json_encode($raw));
        $allowed = app(AdminViewData::class)->filter($student, $this->actor(['hebergement.admissions.personal', 'hebergement.admissions.documents']));
        $this->assertSame('PRIVATE_PHONE', $allowed->telephone1);
        $this->assertCount(1, $allowed->documents);
        $this->assertCount(0, $allowed->demandes);
    }

    public function test_assign_role_validates_against_lowercase_roles_table(): void
    {
        Schema::create('roles', function ($table) { $table->id(); $table->string('name'); });
        DB::table('roles')->insert(['id' => 2, 'name' => 'Gestionnaire']);
        $request = \Illuminate\Http\Request::create('/admin/roles', 'PUT', ['role_id' => 2]);
        $request->setUserResolver(fn () => new User(['role_id' => 1]));
        $target = \Mockery::mock(User::class)->makePartial();
        $target->role_id = 2;
        $target->name = 'Agent';
        $target->shouldReceive('update')->once()->with(['role_id' => 2])->andReturn(true);
        DB::enableQueryLog();
        $response = (new \App\Http\Controllers\admin\parametrage\RolePermissionController)->assignRole($request, $target);
        $this->assertSame(302, $response->getStatusCode());
        $queries = array_column(DB::getQueryLog(), 'query');
        $this->assertStringContainsString('"roles"', implode(' ', $queries));
        $this->assertStringNotContainsString('Roles', implode(' ', $queries));
    }
}
