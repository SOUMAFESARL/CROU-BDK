<?php

namespace Tests\Feature;

use App\Models\Etudiant;
use App\Models\User;
use App\Services\AdminViewAccess;
use App\Services\AdminViewData;
use App\Services\HousingReportingService;
use Illuminate\Support\Facades\Blade;
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
        $view = (new \App\Http\Controllers\admin\immobilier\vie_residentielle\VieResidentielleController($service))->index();
        $this->assertSame([], $view->getData()['residences']);
        $this->assertSame([], $view->getData()['kpis']);
        $this->assertSame([], DB::getQueryLog());
    }

    public function test_finance_profile_only_receives_its_tab_and_no_other_panels(): void
    {
        $actor = $this->actor(['hebergement.paiements.view']);
        $this->actingAs($actor, 'admin');
        $source = file_get_contents(resource_path('views/admin/hebergement/vie_residentielle/index.blade.php'));
        $source = preg_replace('/@extends\(.*?\)\s*/s', '', $source, 1);
        $source .= "\n@yield('content')\n@stack('scripts')";
        $html = Blade::render($source, [
            'viewAccess' => app(AdminViewAccess::class)->housing($actor), 'residences' => [], 'kpis' => [],
            'periodLabel' => '2026-2027', 'lastUpdated' => '29/09/2026',
            'paiementsGlobaux' => collect(), 'ordresTravail' => collect(), 'sanctions' => collect(),
            'rapportsDisciplinaires' => collect(), 'demandesSortie' => collect(), 'decomptesSortie' => collect(),
            'globalOTUrgents' => 0, 'globalOTEnCours' => 0, 'globalOTResolutionRate' => 0,
        ]);
        $this->assertStringContainsString('id="tab2"', $html);
        foreach ([1, 3, 4, 5, 6] as $tab) {
            $this->assertStringNotContainsString('id="tab'.$tab.'"', $html);
            $this->assertStringNotContainsString('data-tab="tab'.$tab.'"', $html);
        }
        $this->assertStringContainsString('tabs[0]?.click()', $html);
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
