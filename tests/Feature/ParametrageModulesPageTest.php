<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceSystemSecurity;
use App\Http\Middleware\LogUserActivity;
use App\Models\ApplicationModule;
use App\Models\ConfigApp;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Repositories\Interfaces\ConfigAppRepositoryInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Page React « Gestion des modules » : arborescence, génération des permissions et règles de suppression. */
class ParametrageModulesPageTest extends TestCase
{
    private const BASE = 'admin.parametrage.modules.';

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->withoutMiddleware([EnforceSystemSecurity::class, LogUserActivity::class]);
        $settings = new ConfigApp(['app_name' => 'CROU Bondoukou']);
        $settings->setRelation('region', null)->setRelation('ville', null);
        $this->mock(ConfigAppRepositoryInterface::class)->shouldReceive('getConfig')->andReturn($settings)->byDefault();

        Schema::create('roles', function (Blueprint $t) { $t->id(); $t->string('name'); $t->timestamps(); });
        Role::create(['id' => 1, 'name' => 'Administrateur']);
        Role::create(['id' => 2, 'name' => 'Agent']);
        foreach (['2026_08_11_000001_create_role_permissions_tables.php', '2026_08_11_000002_create_modules_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        $this->actingAs(new User(['id_user' => 'test-admin', 'name' => 'Admin Test', 'role_id' => 1]), 'admin');
    }

    /** Page Inertia rendue (lue dans `data-page` : un middleware global reconstruit la réponse). */
    private function page(): array
    {
        $this->withoutVite();
        $html = $this->get(route(self::BASE.'index'))->assertOk()->getContent();
        preg_match('/data-page="([^"]+)"/', $html, $matches);

        return json_decode(html_entity_decode($matches[1] ?? ''), true) ?? [];
    }

    public function test_page_liste_les_modules_a_plat_avec_urls(): void
    {
        $page = $this->page();

        $this->assertSame('Admin/Parametrage/Modules', $page['component']);
        $modules = collect($page['props']['modules']);
        $this->assertSame(ApplicationModule::count(), $page['props']['stats']['total']);
        $hebergement = $modules->firstWhere('slug', 'hebergement');
        $this->assertNull($hebergement['parent_id']);
        $this->assertTrue($hebergement['is_system']);
        // Module système : ni suppression ni lien de suppression.
        $this->assertNull($hebergement['urls']['destroy']);
        $this->assertSame(route(self::BASE.'toggle', $hebergement['id']), $hebergement['urls']['toggle']);
        $this->assertSame($hebergement['id'], $modules->firstWhere('slug', 'hebergement.parc')['parent_id']);
        $this->assertSame('Consulter', $page['props']['actions']['view']);
        $this->assertSame(route(self::BASE.'store'), $page['props']['urls']['store']);
    }

    public function test_creation_genere_les_permissions_et_suppression_controlee(): void
    {
        $parent = ApplicationModule::where('slug', 'hebergement')->value('id');
        $this->post(route(self::BASE.'store'), ['name' => 'Sanctions', 'slug' => 'Mauvais slug', 'position' => 1])->assertSessionHasErrors('slug');

        $this->post(route(self::BASE.'store'), [
            'parent_id' => $parent, 'name' => 'Sanctions', 'slug' => 'hebergement.sanctions', 'position' => 12,
            'actions' => ['view', 'create'], 'is_active' => true, 'show_in_menu' => false,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $module = ApplicationModule::where('slug', 'hebergement.sanctions')->firstOrFail();
        $this->assertSame([1, false], [$module->level, $module->show_in_menu]);
        $this->assertEqualsCanonicalizing(['hebergement.sanctions.view', 'hebergement.sanctions.create'], $module->permissions()->pluck('slug')->all());

        // Action retirée : la permission disparaît.
        $this->put(route(self::BASE.'update', $module), [
            'parent_id' => $parent, 'name' => 'Sanctions', 'slug' => 'hebergement.sanctions', 'position' => 12, 'actions' => ['view'], 'is_active' => true,
        ])->assertSessionHasNoErrors();
        $this->assertSame(['hebergement.sanctions.view'], $module->permissions()->pluck('slug')->all());

        // Permission attribuée à un rôle : suppression refusée.
        Role::find(2)->permissions()->attach(Permission::where('slug', 'hebergement.sanctions.view')->value('id'));
        $this->assertSame(1, collect($this->page()['props']['modules'])->firstWhere('slug', 'hebergement.sanctions')['permissions_attribuees']);
        $this->delete(route(self::BASE.'destroy', $module))->assertSessionHasErrors('module');
        Role::find(2)->permissions()->detach();
        $this->delete(route(self::BASE.'destroy', $module))->assertSessionHas('success');
        $this->assertNull(ApplicationModule::find($module->id));
    }

    public function test_identifiant_module_systeme_et_parent_descendant_refuses(): void
    {
        $hebergement = ApplicationModule::where('slug', 'hebergement')->firstOrFail();
        $enfant = ApplicationModule::where('slug', 'hebergement.parc')->value('id');
        $donnees = ['name' => 'Gestion Hébergement', 'slug' => 'hebergement', 'position' => 3, 'actions' => ['view'], 'is_active' => true];

        $this->put(route(self::BASE.'update', $hebergement), ['slug' => 'logement'] + $donnees)->assertSessionHasErrors('slug');
        $this->put(route(self::BASE.'update', $hebergement), ['parent_id' => $enfant] + $donnees)->assertSessionHasErrors('parent_id');
        $this->put(route(self::BASE.'update', $hebergement), $donnees)->assertSessionHasNoErrors();
        $this->assertSame('hebergement', $hebergement->fresh()->slug);

        $this->patch(route(self::BASE.'toggle', $hebergement))->assertSessionHas('success');
        $this->assertFalse($hebergement->fresh()->is_active);
    }
}
