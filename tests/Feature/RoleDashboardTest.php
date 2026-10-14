<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\DashboardAccess;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RoleDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('roles', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('modules', function (Blueprint $t) { $t->id(); $t->boolean('is_active'); });
        Schema::create('permissions', function (Blueprint $t) { $t->id(); $t->string('slug'); $t->integer('module_id'); });
        Schema::create('role_permission', function (Blueprint $t) { $t->integer('role_id'); $t->integer('permission_id'); $t->timestamps(); });
        DB::table('roles')->insert(['id' => 2, 'name' => 'Comptable']);
        DB::table('modules')->insert(['id' => 1, 'is_active' => true]);
        DB::table('permissions')->insert(['id' => 1, 'slug' => 'hebergement.paiements.view', 'module_id' => 1]);
        DB::table('role_permission')->insert(['role_id' => 2, 'permission_id' => 1]);
    }

    public function test_finance_role_only_receives_financial_indicators(): void
    {
        $access = app(DashboardAccess::class)->forUser(new User(['role_id' => 2]));
        $this->assertTrue($access['finance']);
        foreach (['students', 'housing', 'parc', 'admissions', 'maintenance'] as $key) $this->assertFalse($access[$key]);
        DB::table('modules')->update(['is_active' => false]);
        $this->assertFalse(app(DashboardAccess::class)->forUser(new User(['role_id' => 2]))['finance']);
    }

    public function test_administrator_keeps_global_dashboard_and_unassigned_user_has_no_access(): void
    {
        $admin = app(DashboardAccess::class)->forUser(new User(['role_id' => 1]));
        $empty = app(DashboardAccess::class)->forUser(new User);
        foreach (['students', 'housing', 'parc', 'admissions', 'finance', 'maintenance'] as $key) {
            $this->assertTrue($admin[$key]);
            $this->assertFalse($empty[$key]);
        }
    }

    public function test_unassigned_dashboard_does_not_query_business_tables(): void
    {
        $this->actingAs(new User(['id_user' => 'test']), 'admin');
        DB::enableQueryLog();
        $request = \Illuminate\Http\Request::create('/dashboard');
        $request->headers->set('X-Inertia', 'true');
        $response = app(\App\Http\Controllers\admin\Dashboard\dashboardController::class)->index(
            $request, app(DashboardAccess::class), app(\App\Services\AdminAccess::class))->toResponse($request);
        $page = $response->getData(true);
        $this->assertSame([], DB::getQueryLog());
        $this->assertSame('Admin/Dashboard/Index', $page['component']);
        $this->assertFalse($page['props']['access']['finance']);
        $this->assertSame(['parc' => null, 'admissions' => null, 'paiements' => null, 'maintenance' => null], $page['props']['links']);
    }
}
