<?php

namespace Tests\Feature;

use App\Http\Controllers\admin\parametrage\RolePermissionController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RolesPermissionsPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('roles', function (Blueprint $t) { $t->id(); $t->string('name'); $t->timestamps(); });
        Schema::create('modules', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('parent_id')->nullable(); $t->string('name'); $t->string('slug');
            $t->string('icon')->nullable(); $t->integer('position')->default(0); $t->boolean('is_active')->default(true);
        });
        Schema::create('permissions', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('slug'); $t->string('action')->nullable();
            $t->string('description')->nullable(); $t->unsignedBigInteger('module_id')->nullable(); $t->timestamps();
        });
        Schema::create('role_permission', function (Blueprint $t) { $t->integer('role_id'); $t->integer('permission_id'); $t->timestamps(); });
        Schema::create('users', function (Blueprint $t) {
            $t->string('id_user')->primary(); $t->string('name'); $t->string('email')->nullable(); $t->string('matricule')->nullable();
            $t->integer('role_id')->nullable(); $t->string('campus_id')->nullable(); $t->string('statut')->default('active'); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('campus', function (Blueprint $t) { $t->string('id_campus')->primary(); $t->string('name'); $t->timestamps(); $t->softDeletes(); });

        DB::table('roles')->insert([['id' => 1, 'name' => 'Super administrateur'], ['id' => 2, 'name' => 'Comptable']]);
        DB::table('modules')->insert([
            ['id' => 1, 'parent_id' => null, 'name' => 'Hébergement', 'slug' => 'hebergement', 'position' => 1],
            ['id' => 2, 'parent_id' => 1, 'name' => 'Paiements', 'slug' => 'hebergement.paiements', 'position' => 1],
            ['id' => 3, 'parent_id' => null, 'name' => 'Paramétrage', 'slug' => 'parametrage', 'position' => 2],
            ['id' => 4, 'parent_id' => 3, 'name' => 'Documents légaux', 'slug' => 'parametrage.legal-documents', 'position' => 1],
        ]);
        DB::table('permissions')->insert([
            ['id' => 1, 'name' => 'Voir', 'slug' => 'hebergement.view', 'action' => 'view', 'module_id' => 1],
            ['id' => 2, 'name' => 'Voir', 'slug' => 'hebergement.paiements.view', 'action' => 'view', 'module_id' => 2],
            ['id' => 3, 'name' => 'Valider', 'slug' => 'hebergement.paiements.validate', 'action' => 'validate', 'module_id' => 2],
            ['id' => 4, 'name' => 'Signatures', 'slug' => 'parametrage.legal-documents.signatures', 'action' => 'configure', 'module_id' => 4],
        ]);
        DB::table('role_permission')->insert(['role_id' => 2, 'permission_id' => 2]);
        DB::table('campus')->insert(['id_campus' => 'c1', 'name' => 'Campus Bondoukou']);
        foreach (range(1, 12) as $i) {
            DB::table('users')->insert(['id_user' => "u{$i}", 'name' => sprintf('Agent %02d', $i), 'matricule' => "M{$i}", 'role_id' => 2, 'campus_id' => 'c1', 'statut' => $i > 9 ? 'inactive' : 'active']);
        }

        $this->actingAs(new User(['id_user' => 'actor', 'role_id' => 1, 'name' => 'Admin']), 'admin');
    }

    public function test_page_rendue_avec_inertia_et_arbre_des_modules(): void
    {
        $page = $this->rolesPage([]);
        $this->assertSame('Admin/Parametrage/RolesPermissions/Index', $page['component']);

        $roles = collect($page['props']['roles'])->keyBy('name');
        $this->assertTrue($roles['Super administrateur']['isSuperAdmin']);
        $this->assertNotNull($roles['Comptable']['destroyUrl']);
        $this->assertSame([2], $roles['Comptable']['permissionIds']);
        $this->assertSame(12, $roles['Comptable']['usersCount']);
        $this->assertTrue($roles['Comptable']['editable']);

        $tree = collect($page['props']['moduleTree'])->keyBy('slug');
        $this->assertSame(['hebergement', 'parametrage'], $tree->keys()->all());
        $this->assertSame('Paramètres', $tree['parametrage']['name']);
        $this->assertSame('Consulter', $tree['hebergement']['permissions'][0]['label']);
        $this->assertSame([3, 2], array_column($tree['hebergement']['children'][0]['permissions'], 'id'));
        // La permission technique des signatures reste masquée.
        $this->assertSame([], $tree['parametrage']['children'][0]['permissions']);
    }

    public function test_affectations_paginees_par_dix_et_recherchables(): void
    {
        $users = $this->rolesPage([])['props']['users'];
        $this->assertCount(10, $users['data']);
        $this->assertSame(12, $users['total']);
        $this->assertSame('Campus Bondoukou', $users['data'][0]['campus']);
        $this->assertStringContainsString('onglet=affectations', $users['next_page_url']);

        $filtered = $this->rolesPage(['search' => 'Agent 11'])['props']['users'];
        $this->assertSame(1, $filtered['total']);
        $this->assertSame('u11', $filtered['data'][0]['id']);
    }

    public function test_affectations_filtrables_par_statut(): void
    {
        $page = $this->rolesPage(['statut' => 'inactive']);
        $users = $page['props']['users'];
        $this->assertSame(3, $users['total']);
        $this->assertSame(['inactive'], array_values(array_unique(array_column($users['data'], 'statut'))));
        $this->assertSame('inactive', $page['props']['filters']['statut']);

        $this->assertSame(9, $this->rolesPage(['statut' => 'active'])['props']['users']['total']);
        $this->assertSame(1, $this->rolesPage(['statut' => 'inactive', 'search' => 'Agent 11'])['props']['users']['total']);

        // Une valeur inconnue est ignorée.
        $ignored = $this->rolesPage(['statut' => 'supprime'])['props'];
        $this->assertSame(12, $ignored['users']['total']);
        $this->assertSame('', $ignored['filters']['statut']);
    }

    private function rolesPage(array $query): array
    {
        $request = Request::create('/admin/parametrage/roles-permissions', 'GET', $query);
        $request->headers->set('X-Inertia', 'true');
        $request->setUserResolver(fn ($guard = null) => auth('admin')->user());
        $this->app->instance('request', $request);

        return app()->call([app(RolePermissionController::class), 'index'], ['request' => $request])->toResponse($request)->getData(true);
    }
}
