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
        $this->assertSame('hebergement.admissions.dossier.document', $rules['admin.hebergement.campagnes_admission.validate_document']);
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

    /** Les droits des onglets des admissions passent dans des sous-modules sans que les rôles les perdent. */
    public function test_droits_des_onglets_admissions_ranges_en_sous_modules(): void
    {
        Schema::table('modules', function (Blueprint $t) {
            $t->string('slug')->nullable();
            $t->string('name')->nullable();
            $t->integer('parent_id')->nullable();
            $t->integer('level')->default(0);
            $t->integer('position')->default(0);
            $t->text('actions')->nullable();
            $t->boolean('show_in_menu')->default(false);
            $t->boolean('is_system')->default(false);
            $t->timestamps();
        });
        Schema::table('permissions', function (Blueprint $t) {
            foreach (['module', 'action', 'name', 'description'] as $colonne) {
                $t->string($colonne)->nullable();
            }
            $t->timestamps();
        });
        DB::table('roles')->insert(['id' => 3, 'name' => 'Sous-onglets']);
        DB::table('modules')->insert([
            ['id' => 4, 'is_active' => true, 'slug' => 'hebergement.admissions.derogations', 'parent_id' => 3, 'level' => 2, 'name' => 'Dérogations', 'actions' => '["validate"]'],
            ['id' => 2, 'is_active' => true, 'slug' => 'hebergement', 'parent_id' => null, 'level' => 0, 'name' => 'Hébergement', 'actions' => '[]'],
            ['id' => 3, 'is_active' => true, 'slug' => 'hebergement.admissions', 'parent_id' => 2, 'level' => 1, 'name' => 'Campagnes et admissions', 'actions' => '["view","create","campagnes_view","commission_view","commission_attente_valider"]'],
        ]);
        DB::table('permissions')->insert([
            ['id' => 10, 'slug' => 'hebergement.admissions.view', 'module_id' => 3, 'action' => 'view'],
            ['id' => 11, 'slug' => 'hebergement.admissions.commission_view', 'module_id' => 3, 'action' => 'commission_view'],
            ['id' => 14, 'slug' => 'hebergement.admissions.commission_validees_approuver', 'module_id' => 3, 'action' => 'commission_validees_approuver'],
            ['id' => 15, 'slug' => 'hebergement.admissions.demandes_view', 'module_id' => 3, 'action' => 'demandes_view'],
            ['id' => 16, 'slug' => 'hebergement.admissions.toutes_view', 'module_id' => 3, 'action' => 'toutes_view'],
            ['id' => 17, 'slug' => 'hebergement.admissions.provisoire_readmission_valider', 'module_id' => 3, 'action' => 'provisoire_readmission_valider'],
            ['id' => 18, 'slug' => 'hebergement.admissions.definitive_attente_export', 'module_id' => 3, 'action' => 'definitive_attente_export'],
            ['id' => 19, 'slug' => 'hebergement.admissions.rejetees_lever_refus', 'module_id' => 3, 'action' => 'rejetees_lever_refus'],
            ['id' => 13, 'slug' => 'hebergement.admissions.campagnes_view', 'module_id' => 3, 'action' => 'campagnes_view'],
            ['id' => 12, 'slug' => 'hebergement.admissions.commission_attente_valider', 'module_id' => 3, 'action' => 'commission_attente_valider'],
        ]);
        DB::table('role_permission')->insert([['role_id' => 2, 'permission_id' => 11], ['role_id' => 2, 'permission_id' => 12], ['role_id' => 2, 'permission_id' => 13], ['role_id' => 2, 'permission_id' => 14], ['role_id' => 2, 'permission_id' => 16], ['role_id' => 3, 'permission_id' => 15], ['role_id' => 3, 'permission_id' => 17], ['role_id' => 3, 'permission_id' => 18], ['role_id' => 3, 'permission_id' => 19]]);

        $migration = require database_path('migrations/2026_11_25_000001_ranger_droits_admissions_en_sous_modules.php');
        $migration->up();
        $nombre = DB::table('permissions')->count();
        $migration->up();
        $this->assertSame($nombre, DB::table('permissions')->count());

        // Le module ne garde que ses droits de base ; l'onglet imbriqué suit son onglet parent.
        $this->assertSame('["view","create"]', DB::table('modules')->where('id', 3)->value('actions'));
        $this->assertSame(['hebergement.admissions.view'], DB::table('permissions')->where('module_id', 3)->pluck('slug')->all());
        $commission = DB::table('modules')->where('slug', 'hebergement.admissions.commission')->first();
        $attente = DB::table('modules')->where('slug', 'hebergement.admissions.commission-attente')->first();
        $this->assertSame([3, 2], [(int) $commission->parent_id, (int) $commission->level]);
        $this->assertSame([(int) $commission->id, 3], [(int) $attente->parent_id, (int) $attente->level]);

        // La liste des campagnes n'a pas de sous-module : elle suit « Consulter » du module.
        $this->assertFalse(DB::table('modules')->where('slug', 'hebergement.admissions.campagnes')->exists());
        $this->assertTrue((new User(['role_id' => 2]))->hasPermission('hebergement.admissions.view'));

        // « Toutes les demandes » et « Liste des demandes » ne font qu'un ; qui n'ouvrait pas la liste ne la reçoit pas.
        $this->assertFalse(DB::table('modules')->where('slug', 'hebergement.admissions.toutes')->exists());
        $this->assertTrue((new User(['role_id' => 2]))->hasPermission('hebergement.admissions.demandes.view'));
        $this->assertFalse((new User(['role_id' => 3]))->hasPermission('hebergement.admissions.demandes.view'));

        // Les listes provisoires (admissions et réadmissions) partagent les mêmes droits.
        $this->assertSame(1, DB::table('modules')->where('slug', 'like', 'hebergement.admissions.provisoire%')->count());
        $this->assertTrue((new User(['role_id' => 3]))->hasPermission('hebergement.admissions.provisoire.validate'));

        // Les listes définitives n'ont plus de sous-module par onglet.
        $this->assertSame(['hebergement.admissions.definitives'], DB::table('modules')->where('slug', 'like', 'hebergement.admissions.definitive%')->pluck('slug')->all());
        $this->assertTrue((new User(['role_id' => 3]))->hasPermission('hebergement.admissions.definitives.export'));

        // « Lever un refus automatique » rejoint « Déroger ».
        $this->assertSame('["deroger"]', DB::table('modules')->where('id', 4)->value('actions'));
        $this->assertFalse(DB::table('permissions')->where('slug', 'like', '%lever_refus')->exists());
        $this->assertTrue((new User(['role_id' => 3]))->hasPermission('hebergement.admissions.derogations.deroger'));

        // « Approuver la liste » se donne sur la décision de la commission.
        $this->assertTrue((new User(['role_id' => 2]))->hasPermission('hebergement.admissions.commission.approuver'));
        $this->assertSame('["view","approuver"]', $commission->actions);
        $this->assertFalse(DB::table('permissions')->where('slug', 'like', '%commission%validees%approuver')->exists());

        // Tous les droits du service existent, et l'attribution du rôle suit.
        foreach (\App\Services\AdmissionPermissions::SCOPES as $onglet => [, $actions]) {
            foreach ($actions as $action) {
                $slug = \App\Services\AdmissionPermissions::slug($onglet, $action);
                $this->assertTrue(DB::table('permissions')->where('slug', $slug)->exists(), $slug);
            }
        }
        $this->assertTrue((new User(['role_id' => 2]))->hasPermission('hebergement.admissions.commission-attente.validate'));
        $this->assertFalse((new User(['role_id' => 2]))->hasPermission('hebergement.admissions.commission-attente.refuser'));

        // Désactiver le module coupe toujours les droits de ses onglets.
        DB::table('modules')->where('id', 3)->update(['is_active' => false]);
        $this->assertFalse((new User(['role_id' => 2]))->hasPermission('hebergement.admissions.commission-attente.validate'));
    }

    public function test_migration_covers_route_permissions_without_granting_other_roles(): void
    {
        Schema::table('modules', function (Blueprint $t) {
            $t->string('slug')->nullable();
            $t->string('name')->nullable();
            $t->integer('parent_id')->nullable();
            $t->integer('level')->default(0);
            $t->integer('position')->default(0);
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
        // Permissions ajoutées par les migrations suivantes (même principe : rôle 1 seulement).
        foreach (['2026_10_17_000001_add_campagne_update_delete_permissions.php', '2026_11_10_000002_add_eligibilite_permissions.php', '2026_11_13_000002_add_discipline_configure_permission.php', '2026_11_23_000003_add_types_paiement_permissions.php', '2026_11_24_000001_add_admission_screen_permissions.php', '2026_11_24_000006_add_reclamations_permissions.php', '2026_11_24_000009_rename_reclamations_update_permission_to_traiter.php', '2026_11_25_000001_ranger_droits_admissions_en_sous_modules.php', '2026_11_25_000004_add_resultats_permissions.php', '2026_11_25_000007_add_resultats_admettre_permission.php'] as $suivante) {
            (require database_path('migrations/'.$suivante))->up();
        }
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
