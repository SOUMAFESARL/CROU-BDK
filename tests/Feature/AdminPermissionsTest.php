<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckAdminAccess;
use App\Http\Middleware\CheckPermission;
use App\Models\User;
use App\Services\AdminAccess;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AdminPermissionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('name');
        });
        Schema::create('modules', function (Blueprint $t) {
            $t->id();
            $t->boolean('is_active');
        });
        Schema::create('permissions', function (Blueprint $t) {
            $t->id();
            $t->string('slug');
            $t->integer('module_id');
        });
        Schema::create('role_permission', function (Blueprint $t) {
            $t->integer('role_id');
            $t->integer('permission_id');
            $t->timestamps();
            $t->primary(['role_id', 'permission_id']);
        });
        DB::table('roles')->insert(['id' => 2, 'name' => 'Administrateur']);
        DB::table('modules')->insert(['id' => 1, 'is_active' => true]);
        DB::table('permissions')->insert(['id' => 1, 'slug' => 'hebergement.paiements.view', 'module_id' => 1]);
        DB::table('role_permission')->insert(['role_id' => 2, 'permission_id' => 1]);
    }

    private function requestFor(string $name, ?User $user): Request
    {
        $request = Request::create('/admin/test');
        $route = (new Route('GET', 'admin/test', fn () => null))->name($name);
        $request->setRouteResolver(fn () => $route);
        $request->setUserResolver(fn () => $user);

        return $request;
    }

    public function test_read_permission_does_not_allow_writes_or_other_modules(): void
    {
        $access = app(AdminAccess::class);
        $user = new User(['role_id' => 2]);
        $this->assertTrue($access->allows($user, 'admin.hebergement.paiements.index'));
        $this->assertTrue($access->allows($user, 'admin.hebergement.index'));
        foreach (['admin.hebergement.paiements.store', 'admin.hebergement.paiements.valider', 'admin.hebergement.paiements.export', 'admin.users.index', 'admin.parametrage.roles-permissions.index'] as $route) {
            $this->assertFalse($access->allows($user, $route), $route);
        }
        $response = app(CheckAdminAccess::class)->handle($this->requestFor('admin.hebergement.paiements.index', $user), fn () => response('allowed'));
        $this->assertSame('allowed', $response->getContent());
        $this->expectException(HttpException::class);
        app(CheckAdminAccess::class)->handle($this->requestFor('admin.hebergement.paiements.store', $user), fn () => $this->fail('Write controller reached'));
    }

    public function test_react_sidebar_menu_only_lists_allowed_entries(): void
    {
        $menu = app(\App\Services\AdminMenu::class);
        $request = Request::create('/admin/hebergement');

        $labels = array_column($menu->forUser(new User(['role_id' => 2]), $request), 'label');
        $this->assertSame(['Tableau de bord', 'Gestion Hébergement'], $labels);

        $this->assertCount(5, $menu->forUser(new User(['role_id' => 1]), $request));
        $this->assertSame([], $menu->forUser(null, $request));
    }

    public function test_sidebar_submenu_only_lists_allowed_modules_and_marks_the_active_one(): void
    {
        $menu = app(\App\Services\AdminMenu::class);
        $request = Request::create('/admin/gestion-hebergement/paiements');
        $request->setRouteResolver(fn () => app('router')->getRoutes()->getByName('admin.hebergement.paiements.index'));

        $hebergement = collect($menu->forUser(new User(['role_id' => 2]), $request))->firstWhere('label', 'Gestion Hébergement');
        $this->assertTrue($hebergement['active']);
        $this->assertSame(['Paiements loyer', 'Vie résidentielle'], array_column($hebergement['children'], 'label'));
        $this->assertTrue($hebergement['children'][0]['active']);

        $tous = collect($menu->forUser(new User(['role_id' => 1]), $request))->firstWhere('label', 'Gestion Hébergement');
        $this->assertCount(count(config('admin_menu')[2]['children']), $tous['children']);
        $this->assertSame(['Paiements loyer'], array_column(array_filter($tous['children'], fn ($c) => $c['active']), 'label'));

        $cartes = $menu->childrenOf(new User(['role_id' => 2]), 'admin.hebergement.index', $request);
        $this->assertSame('Encaissements et soldes', $cartes[0]['description']);
    }

    public function test_parametrage_submenu_is_filtered_and_marks_the_active_module(): void
    {
        $menu = app(\App\Services\AdminMenu::class);
        $request = Request::create('/admin/parametrage/ville');
        $request->setRouteResolver(fn () => app('router')->getRoutes()->getByName('admin.parametrage.ville.index'));

        // Le rôle 2 n'a aucun droit de paramétrage : ni rubrique ni modules.
        $this->assertNull(collect($menu->forUser(new User(['role_id' => 2]), $request))->firstWhere('label', 'Paramètres'));
        $this->assertSame([], $menu->childrenOf(new User(['role_id' => 2]), 'admin.parametrage.index', $request));

        $parametres = collect($menu->forUser(new User(['role_id' => 1]), $request))->firstWhere('label', 'Paramètres');
        $this->assertTrue($parametres['active']);
        $this->assertCount(12, $parametres['children']);
        $this->assertSame(['Villes'], array_column(array_filter($parametres['children'], fn ($c) => $c['active']), 'label'));
    }

    public function test_inactive_module_and_missing_role_are_denied(): void
    {
        DB::table('modules')->update(['is_active' => false]);
        $access = app(AdminAccess::class);
        $this->assertFalse($access->allows(new User(['role_id' => 2]), 'admin.hebergement.paiements.index'));
        $this->assertFalse($access->allows(new User, 'admin.users.index'));
        $this->assertFalse($access->allows(null, 'admin.dashboard.index'));
        $this->assertTrue($access->allows(new User, 'admin.dashboard.index'));
        $this->assertTrue($access->allows(new User, 'admin.logout'));
        $this->assertTrue($access->allows(new User(['role_id' => 1]), 'admin.users.store'));
        $this->assertFalse($access->allows(new User(['role_id' => 1]), 'admin.unclassified'));
    }

    public function test_no_configuration_does_not_bypass_permissions(): void
    {
        DB::table('role_permission')->delete();
        $this->actingAs(new User(['role_id' => 2]), 'admin');
        $this->expectException(HttpException::class);
        app(CheckPermission::class)->handle(Request::create('/'), fn () => $this->fail('Permission bypass'), 'parametrage.roles.update');
    }

    public function test_login_is_public_but_lookup_endpoints_require_authentication(): void
    {
        $middleware = app(CheckAdminAccess::class);
        $response = $middleware->handle($this->requestFor('admin.login', null), fn () => response('login'));
        $this->assertSame('login', $response->getContent());
        $response = $middleware->handle($this->requestFor('admin.chambre_batiment', null), fn () => $this->fail('Lookup exposed'));
        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_every_admin_route_is_classified_and_protected(): void
    {
        $rules = config('admin_permissions.routes');
        foreach (app('router')->getRoutes() as $route) {
            if (! str_starts_with((string) $route->getName(), 'admin.')) {
                continue;
            }
            $this->assertContains(CheckAdminAccess::class, $route->gatherMiddleware(), $route->getName());
            $this->assertTrue(array_key_exists($route->getName(), $rules) || in_array($route->getName(), config('admin_permissions.public'), true), $route->getName());
        }
    }

    public function test_sensitive_get_actions_require_mutation_permissions(): void
    {
        $rules = config('admin_permissions.routes');
        $this->assertSame('hebergement.admissions.validate', $rules['admin.hebergement.campagnes_admission.validate_document']);
        $this->assertSame('hebergement.annonces.update', $rules['admin.hebergement.annonce.update']);
        $this->assertSame('hebergement.parc.create', $rules['admin.hebergement.parc-immobilier.massCreateChambres']);
        $this->assertSame('parametrage.roles.delete', $rules['admin.parametrage.roles-permissions.roles.destroy']);
    }

    public function test_user_creation_requires_separate_role_assignment_permission(): void
    {
        $request = \App\Http\Requests\UserRequest::create('/admin/utilisateur/store', 'POST', ['role_id' => 2]);
        $request->setUserResolver(fn () => new User(['role_id' => 2]));
        $this->assertFalse($request->authorize());
        DB::table('permissions')->insert(['id' => 2, 'slug' => 'parametrage.roles.assign', 'module_id' => 1]);
        DB::table('role_permission')->insert(['role_id' => 2, 'permission_id' => 2]);
        $this->assertTrue($request->authorize());
        $request->merge(['role_id' => 1]);
        $this->assertFalse($request->authorize());
        $request->setUserResolver(fn () => new User(['role_id' => 1]));
        $this->assertTrue($request->authorize());
    }

    public function test_migration_covers_route_permissions_without_granting_other_roles(): void
    {
        Schema::table('modules', function (Blueprint $t) {
            $t->string('slug')->nullable();
            $t->string('name')->nullable();
            $t->integer('parent_id')->nullable();
            $t->integer('level')->default(0);
            $t->text('actions')->nullable();
            $t->boolean('show_in_menu')->default(false);
            $t->boolean('is_system')->default(false);
            $t->timestamps();
        });
        Schema::table('permissions', function (Blueprint $t) {
            $t->string('module')->nullable();
            $t->string('action')->nullable();
            $t->string('name')->nullable();
            $t->text('description')->nullable();
            $t->timestamps();
        });
        DB::table('roles')->insert(['id' => 1, 'name' => 'Super administrateur']);
        DB::table('modules')->where('id', 1)->update(['slug' => 'hebergement.paiements', 'name' => 'Paiements', 'actions' => '["view"]']);
        $migration = require database_path('migrations/2026_09_29_000001_complete_admin_permissions.php');
        $migration->up();
        $count = DB::table('permissions')->count();
        $migration->up();
        $this->assertSame($count, DB::table('permissions')->count());
        $this->assertSame(1, DB::table('role_permission')->where('role_id', 2)->count());
        foreach (config('admin_permissions.routes') as $rule) {
            foreach ((array) $rule as $slug) {
                $this->assertTrue(DB::table('permissions')->where('slug', $slug)->exists(), $slug);
            }
        }
    }

    public function test_menu_hides_unavailable_modules(): void
    {
        $this->actingAs(new User(['role_id' => 2]), 'admin');
        $html = view('components.app-menu', ['activeMenu' => 'hebergement', 'activeSubMenu' => 'finances'])->render();
        $this->assertStringContainsString(route('admin.hebergement.paiements.index'), $html);
        $this->assertStringNotContainsString(route('admin.users.index'), $html);
        $this->assertStringNotContainsString(route('admin.parametrage.roles-permissions.index'), $html);
        $this->assertStringNotContainsString(route('admin.hebergement.parc-immobilier.index'), $html);
    }

    public function test_all_admin_templates_compile(): void
    {
        $compiler = app('blade.compiler');
        $files = \Illuminate\Support\Facades\File::allFiles(resource_path('views/admin'));
        foreach ($files as $file) {
            if (! str_contains($file->getContents(), '@adminroute')) {
                continue;
            }
            $compiled = $compiler->compileString($file->getContents());
            try {
                $this->assertIsArray(token_get_all($compiled, TOKEN_PARSE), $file->getPathname());
            } catch (\ParseError $error) {
                $this->fail($file->getPathname().': '.$error->getMessage());
            }
        }
    }
}
