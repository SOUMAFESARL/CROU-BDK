<?php

namespace Tests\Feature;

use App\Http\Controllers\admin\ActivityLogController;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ActivityLogsPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $configRepo = \Mockery::mock(\App\Repositories\Interfaces\ConfigAppRepositoryInterface::class);
        $configRepo->shouldReceive('getConfig')->andReturn(null);
        $this->app->instance(\App\Repositories\Interfaces\ConfigAppRepositoryInterface::class, $configRepo);
        Schema::create('roles', function (Blueprint $t) { $t->id(); $t->string('name'); });
        DB::table('roles')->insert(['id' => 1, 'name' => 'Administrateur']);
        (require base_path('database/migrations/2026_08_18_000001_create_activity_logs_table.php'))->up();
        $this->actingAs(new User(['id_user' => 'actor', 'role_id' => 1, 'name' => 'Admin']), 'admin');
    }

    public function test_journal_is_rendered_with_inertia_paginated_by_ten_and_filterable(): void
    {
        foreach (range(1, 12) as $i) {
            ActivityLog::create([
                'actor_type' => 'admin',
                'actor_id' => $i <= 3 ? 'agent-a' : 'agent-b',
                'actor_name' => $i <= 3 ? 'Agent A' : 'Agent B',
                'action' => $i === 1 ? 'delete' : 'update',
                'method' => 'POST',
                'url' => 'https://crou.test/admin',
                'ip_address' => '127.0.0.1',
                'status_code' => 302,
            ]);
        }

        $page = $this->journalPage([]);
        $this->assertSame('Admin/ActivityLogs/Index', $page['component']);
        $this->assertTrue($page['props']['canViewAll']);
        $this->assertCount(10, $page['props']['logs']['data']);
        $this->assertSame(12, $page['props']['logs']['total']);
        $this->assertSame(['id', 'date', 'actor', 'actor_type', 'action', 'action_label', 'ip', 'status'], array_keys($page['props']['logs']['data'][0]));
        $this->assertCount(2, $page['props']['actors']);

        $this->assertSame(3, $this->journalPage(['actor_id' => 'agent-a'])['props']['logs']['total']);

        $deleted = $this->journalPage(['action' => 'delete'])['props']['logs'];
        $this->assertSame(1, $deleted['total']);
        $this->assertSame('Suppression', $deleted['data'][0]['action_label']);
    }

    public function test_journal_peut_etre_exporte_en_excel_et_imprime_avec_les_filtres(): void
    {
        foreach (['delete', 'update', 'update'] as $action) {
            ActivityLog::create([
                'actor_type' => 'admin',
                'actor_id' => 'agent-a',
                'actor_name' => 'Agent <A>',
                'action' => $action,
                'method' => 'POST',
                'url' => 'https://crou.test/admin',
                'ip_address' => '127.0.0.1',
                'status_code' => 302,
            ]);
        }

        $request = $this->journalRequest(['action' => 'delete']);
        $response = app(ActivityLogController::class)->export($request);
        $this->assertSame('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($response->getFile()->getPathname()));
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        $this->assertStringContainsString('Suppression', $sheet);
        $this->assertStringContainsString('Agent &lt;A&gt;', $sheet);
        $this->assertStringNotContainsString('Modification', $sheet);

        $view = app(ActivityLogController::class)->print($this->journalRequest(['action' => 'update']));
        $this->assertCount(2, $view->getData()['logs']);
        $this->assertSame(['Action' => 'Modification'], $view->getData()['criteria']);
        $this->assertStringContainsString('window.print()', $view->render());
    }

    public function test_export_sans_resultat_est_refuse(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(ActivityLogController::class)->export($this->journalRequest(['action' => 'delete']));
    }

    private function journalRequest(array $query): Request
    {
        $request = Request::create('/admin/journal-activite/export', 'GET', $query);
        $request->setUserResolver(fn ($guard = null) => auth('admin')->user());
        $this->app->instance('request', $request);

        return $request;
    }

    private function journalPage(array $query): array
    {
        $request = Request::create('/admin/journal-activite', 'GET', $query);
        $request->headers->set('X-Inertia', 'true');
        $request->setUserResolver(fn ($guard = null) => auth('admin')->user());
        $this->app->instance('request', $request);

        return app(ActivityLogController::class)->index($request)->toResponse($request)->getData(true);
    }
}
