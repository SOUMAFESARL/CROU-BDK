<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceSystemSecurity;
use App\Http\Middleware\LogUserActivity;
use App\Models\ConfigApp;
use App\Models\Paiement;
use App\Models\Role;
use App\Models\TypePaiement;
use App\Models\User;
use App\Repositories\Interfaces\ConfigAppRepositoryInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Paramétrage hébergement › Types de paiement : référentiel proposé au champ « Type de paiement » de « Nouveau paiement ». */
class TypesPaiementTest extends TestCase
{
    private const BASE = 'admin.hebergement.parametrage-herb.type-paiement.';

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->withoutMiddleware([EnforceSystemSecurity::class, LogUserActivity::class]);
        $settings = new ConfigApp(['app_name' => 'Résidence test']);
        $settings->setRelation('region', null)->setRelation('ville', null);
        $this->mock(ConfigAppRepositoryInterface::class)->shouldReceive('getConfig')->andReturn($settings);

        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
        Role::create(['id' => 1, 'name' => 'Administrateur']);
        Role::create(['id' => 2, 'name' => 'Consultation paramétrage']);
        Role::create(['id' => 3, 'name' => 'Caissier']);
        foreach (['2026_08_11_000001_create_role_permissions_tables.php', '2026_08_11_000002_create_modules_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        Schema::create('paiements', function (Blueprint $t) {
            $t->string('id_paiement')->primary();
            $t->string('numero_paiement')->nullable();
            $t->string('type_paiement')->default('loyer');
            $t->decimal('montant', 12, 2)->default(0);
            $t->string('statut_paiement')->default('valide');
            $t->timestamps();
            $t->softDeletes();
        });
        (require database_path('migrations/2026_11_23_000002_create_types_paiement_table.php'))->up();
        // Rôle 2 : consultation du paramétrage avant la migration des droits dédiés (il doit la conserver).
        $consultation = DB::table('permissions')->insertGetId(['slug' => 'hebergement.parametrage.view', 'name' => 'Consulter', 'module' => 'Paramétrage hébergement', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('role_permission')->insert(['role_id' => 2, 'permission_id' => $consultation]);
        (require database_path('migrations/2026_11_23_000003_add_types_paiement_permissions.php'))->up();

        $this->actingAs(new User(['id_user' => 'test-admin', 'name' => 'Admin Test', 'role_id' => 1]), 'admin');
    }

    /** Page Inertia rendue (lue dans `data-page` : un middleware global reconstruit la réponse). */
    private function page(string $url): array
    {
        $this->withoutVite();
        $html = $this->get($url)->assertOk()->getContent();
        preg_match('/data-page="([^"]+)"/', $html, $matches);

        return json_decode(html_entity_decode($matches[1] ?? ''), true) ?? [];
    }

    private function type(string $code): TypePaiement
    {
        return TypePaiement::where('code', $code)->firstOrFail();
    }

    public function test_droits_dedies_aux_types_de_paiement(): void
    {
        $slugs = DB::table('permissions')->where('slug', 'like', 'hebergement.types-paiement.%')->orderBy('id')->pluck('slug')->all();
        $this->assertSame(['hebergement.types-paiement.view', 'hebergement.types-paiement.create', 'hebergement.types-paiement.update', 'hebergement.types-paiement.delete'], $slugs);
        $droits = fn (int $role) => DB::table('role_permission')->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
            ->where('role_id', $role)->where('slug', 'like', 'hebergement.types-paiement.%')->orderBy('permissions.id')->pluck('slug')->all();
        $this->assertSame($slugs, $droits(1));
        // Qui consultait le paramétrage consulte les types de paiement, sans pouvoir les modifier.
        $this->assertSame(['hebergement.types-paiement.view'], $droits(2));
        $this->assertSame([], $droits(3));
        $this->assertSame('hebergement.types-paiement.delete', config('admin_permissions.routes')[self::BASE.'destroy']);

        // Migration rejouée : aucun doublon.
        (require database_path('migrations/2026_11_23_000003_add_types_paiement_permissions.php'))->up();
        $this->assertSame(4, DB::table('permissions')->where('slug', 'like', 'hebergement.types-paiement.%')->count());

        $this->actingAs(new User(['id_user' => 'lecteur', 'name' => 'Lecteur', 'role_id' => 2]), 'admin');
        $types = $this->page(route(self::BASE.'index'))['props'];
        $this->assertNull($types['urls']['store']);
        $this->assertNull($types['types']['data'][0]['urls']['destroy']);
        $this->delete(route(self::BASE.'destroy', $this->type('autre')->id_type_paiement))->assertForbidden();

        $this->actingAs(new User(['id_user' => 'caissier', 'name' => 'Caissier', 'role_id' => 3]), 'admin');
        $this->get(route(self::BASE.'index'))->assertForbidden();
    }

    public function test_les_types_systeme_sont_semes_et_proteges(): void
    {
        DB::table('paiements')->insert(['id_paiement' => 'p1', 'type_paiement' => 'loyer']);
        $page = $this->page(route(self::BASE.'index'));

        $this->assertSame('Admin/Hebergement/Parametrage/TypesPaiement', $page['component']);
        $this->assertSame(['total' => 4, 'utilises' => 4, 'inutilises' => 0], $page['props']['stats']);
        // Le code interne n'est pas envoyé à la page (colonne retirée) : seul le libellé est affiché.
        $this->assertArrayNotHasKey('code', $page['props']['types']['data'][0]);
        $types = collect($page['props']['types']['data'])->keyBy(fn ($t) => TypePaiement::find($t['id'])->code);
        $this->assertSame(['loyer', 'caution', 'penalite', 'autre'], $types->keys()->all());
        $this->assertSame('Loyer', $types['loyer']['name']);
        $this->assertTrue($types['loyer']['systeme']);
        $this->assertSame(1, $types['loyer']['paiements']);
        // Types système : modifiables (nom, description) ; bouton de suppression proposé à tous les types.
        $this->assertNotNull($types['loyer']['urls']['update']);
        $this->assertNotNull($types['loyer']['urls']['destroy']);

        $loyer = $this->type('loyer');
        $this->put(route(self::BASE.'update', $loyer->id_type_paiement), ['name' => 'Loyer', 'statut' => 'inactif'])->assertSessionHasErrors('statut');
        $this->assertTrue($loyer->fresh()->actif);
        // Utilisé par un paiement : suppression refusée ; inutilisé (Pénalité) : supprimé.
        $this->delete(route(self::BASE.'destroy', $loyer->id_type_paiement))->assertSessionHasErrors('type');
        $this->assertNotNull($loyer->fresh());
        $penalite = $this->type('penalite');
        $this->delete(route(self::BASE.'destroy', $penalite->id_type_paiement))->assertSessionHasNoErrors();
        $this->assertNull($penalite->fresh());
        $this->assertNotContains('penalite', TypePaiement::codesActifs());
        // Les paiements enregistrés par l'application gardent un libellé.
        $this->assertSame('Pénalité', TypePaiement::libelle('penalite'));
    }

    public function test_ajout_d_un_type_avec_code_derive_du_nom(): void
    {
        $this->post(route(self::BASE.'store'), ['name' => 'Frais de dossier', 'description' => 'Ouverture du dossier', 'statut' => 'actif'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $type = $this->type('frais_de_dossier');
        $this->assertSame(['Frais de dossier', 'Ouverture du dossier', true, false], [$type->name, $type->description, $type->actif, $type->systeme]);

        // Nom déjà pris (casse ignorée) : refusé.
        $this->post(route(self::BASE.'store'), ['name' => 'frais de dossier', 'statut' => 'actif'])->assertSessionHasErrors('name');

        // Le code ne change pas quand le nom change.
        $this->put(route(self::BASE.'update', $type->id_type_paiement), ['name' => 'Frais d’inscription', 'statut' => 'actif'])->assertSessionHasNoErrors();
        $this->assertSame(['frais_de_dossier', 'Frais d’inscription'], [$type->fresh()->code, $type->fresh()->name]);
    }

    public function test_seuls_les_types_actifs_sont_proposes_dans_nouveau_paiement(): void
    {
        TypePaiement::create(['code' => 'reparation', 'name' => 'Réparation', 'actif' => true, 'ordre' => 50]);
        TypePaiement::create(['code' => 'ancien', 'name' => 'Ancien type', 'actif' => false, 'ordre' => 50]);

        $this->assertSame(['loyer', 'caution', 'penalite', 'reparation', 'autre'], array_column(TypePaiement::options(), 'value'));
        $this->assertSame(['loyer', 'caution', 'penalite', 'reparation', 'autre'], TypePaiement::codesActifs());
        // L'historique reste lisible : un type désactivé garde son libellé.
        $this->assertSame('Ancien type', (new Paiement(['type_paiement' => 'ancien']))->type_label);
        $this->assertSame('Pénalité', (new Paiement(['type_paiement' => 'penalite']))->type_label);
    }

    public function test_suppression_bloquee_si_le_type_est_utilise(): void
    {
        $type = TypePaiement::create(['code' => 'reparation', 'name' => 'Réparation', 'ordre' => 50]);
        DB::table('paiements')->insert(['id_paiement' => 'p1', 'type_paiement' => 'reparation']);

        $this->delete(route(self::BASE.'destroy', $type->id_type_paiement))->assertSessionHasErrors('type');
        $this->assertNotNull($type->fresh());

        DB::table('paiements')->delete();
        $this->delete(route(self::BASE.'destroy', $type->id_type_paiement))->assertSessionHasNoErrors();
        $this->assertNull($type->fresh());
    }
}
