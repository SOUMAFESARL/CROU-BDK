<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceSystemSecurity;
use App\Http\Middleware\LogUserActivity;
use App\Http\Services\CatalogueSanctions;
use App\Http\Services\DisciplinaryImpactService;
use App\Http\Services\NoteConduite;
use App\Models\ConfigApp;
use App\Models\DisciplinaryReport;
use App\Models\Etudiant;
use App\Models\Role;
use App\Models\Sanction;
use App\Models\User;
use App\Repositories\Interfaces\ConfigAppRepositoryInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Paramétrage des sanctions (docs/parametrage-sanctions.md) : page et droits, note de conduite initiale,
 * types de sanction, note de conduite d'un étudiant et usage des types dans les décisions disciplinaires.
 */
class ParametrageSanctionsTest extends TestCase
{
    private const BASE = 'admin.hebergement.parametrage-herb.sanctions.';

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
        Role::create(['id' => 2, 'name' => 'Agent']);
        Schema::create('etudiants', function (Blueprint $t) {
            $t->string('id_etudiant')->primary();
            $t->string('name')->nullable();
            $t->softDeletes();
        });
        Schema::create('assignation_chambres', function (Blueprint $t) {
            $t->string('id_assignation')->primary();
            $t->string('etudiant_id');
            $t->string('chambre_id');
            $t->string('statut');
            $t->dateTime('date_assignation');
            $t->dateTime('date_fin')->nullable();
            $t->timestamps();
        });
        foreach ([
            '2026_08_11_000001_create_role_permissions_tables.php',
            '2026_08_11_000002_create_modules_table.php',
            '2026_08_10_000003_create_disciplinary_reports_tables.php',
            '2026_08_10_000001_create_sanctions_tables.php',
            '2026_08_10_000002_add_scope_to_sanctions_table.php',
            '2026_09_23_000004_link_disciplinary_decisions_to_sanctions.php',
            '2026_11_13_000001_create_parametrage_sanctions_tables.php',
            '2026_11_13_000002_add_discipline_configure_permission.php',
        ] as $migration) {
            if ($migration === '2026_09_23_000004_link_disciplinary_decisions_to_sanctions.php') {
                Schema::create('departure_requests', fn (Blueprint $t) => $t->string('id')->primary());
            }
            (require database_path('migrations/'.$migration))->up();
        }

        // Agent du rôle 2 : consultation de la discipline seulement.
        $module = DB::table('modules')->where('slug', 'hebergement.discipline')->value('id');
        $vue = DB::table('permissions')->insertGetId([
            'slug' => 'hebergement.discipline.view', 'module_id' => $module, 'module' => 'Discipline', 'action' => 'view',
            'name' => 'Consulter — Discipline', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('role_permission')->insert(['role_id' => 2, 'permission_id' => $vue]);
        DB::table('etudiants')->insert([['id_etudiant' => 'e1', 'name' => 'Awa Koné'], ['id_etudiant' => 'e2', 'name' => 'Autre']]);
    }

    private function admin(int $role = 1): void
    {
        $this->actingAs(new User(['id_user' => 'test-admin', 'name' => 'Admin Test', 'role_id' => $role]), 'admin');
    }

    private function page(): array
    {
        $this->withoutVite();
        $html = $this->get(route(self::BASE.'index'))->assertOk()->getContent();
        preg_match('/data-page="([^"]+)"/', $html, $matches);

        return json_decode(html_entity_decode($matches[1] ?? ''), true) ?? [];
    }

    private function sanction(array $donnees): Sanction
    {
        return Sanction::create($donnees + [
            'scope_type' => 'etudiant', 'etudiant_id' => 'e1', 'statut' => 'active', 'motif' => 'Tapage',
            'date_decision' => today()->subDays(3),
        ]);
    }

    private function catalogue(): CatalogueSanctions
    {
        $catalogue = app(CatalogueSanctions::class);
        $catalogue->oublier();

        return $catalogue;
    }

    public function test_page_avec_le_bareme_d_origine_et_les_droits(): void
    {
        $this->admin();
        $page = $this->page();
        $this->assertSame('Admin/Hebergement/Parametrage/Sanctions', $page['component']);
        $this->assertEquals(20, $page['props']['noteInitiale']);
        // Les plus lourdes d'abord : expulsion, suspension, puis points décroissants.
        $this->assertSame(['expulsion', 'suspension', 'blame', 'avertissement'], array_column($page['props']['types'], 'code'));
        $this->assertEquals([20, 10, 5, 2], array_column($page['props']['types'], 'points_retires'));
        $this->assertSame(['total' => 4, 'actifs' => 4, 'bloquants' => 2], $page['props']['stats']);
        $this->assertNotNull($page['props']['urls']['store']);
        $this->assertNotNull($page['props']['types'][0]['urls']['update']);

        // Consultation seule sans le droit « Paramétrer les sanctions » : aucune action proposée ni autorisée.
        $this->admin(2);
        $page = $this->page();
        $this->assertNull($page['props']['urls']['store']);
        $this->assertNull($page['props']['urls']['note']);
        $this->assertNull($page['props']['types'][0]['urls']['update']);
        $this->put(route(self::BASE.'note'), ['note_initiale' => 30])->assertForbidden();
        $this->post(route(self::BASE.'store'), ['libelle' => 'Blâme écrit', 'points_retires' => 3, 'effet' => 'aucun'])->assertForbidden();
        $this->assertEquals(20, DB::table('parametres_conduite')->value('note_initiale'));
    }

    public function test_note_initiale_et_types_de_sanction(): void
    {
        $this->admin();

        $this->put(route(self::BASE.'note'), ['note_initiale' => 0])->assertSessionHasErrors('note_initiale');
        $this->put(route(self::BASE.'note'), ['note_initiale' => 25])->assertSessionHasNoErrors();
        $this->assertEquals(25, $this->catalogue()->noteInitiale());

        // Ajout : code déduit du libellé, libellé unique.
        $this->post(route(self::BASE.'store'), ['libelle' => 'Exclusion temporaire', 'points_retires' => '7.5', 'effet' => 'suspension'])->assertSessionHasNoErrors();
        $type = DB::table('types_sanction')->where('code', 'exclusion_temporaire')->first();
        $this->assertEquals([7.5, 'suspension', 1], [$type->points_retires, $type->effet, $type->actif]);
        $this->post(route(self::BASE.'store'), ['libelle' => 'exclusion temporaire', 'points_retires' => 3, 'effet' => 'aucun'])->assertSessionHasErrors('libelle');
        $this->post(route(self::BASE.'store'), ['libelle' => 'Inconnu', 'points_retires' => -1, 'effet' => 'bannissement'])->assertSessionHasErrors(['points_retires', 'effet']);

        // Modification : points et effet modifiables tant que le type n'a jamais été appliqué.
        $this->put(route(self::BASE.'update', $type->id_type_sanction), ['libelle' => 'Exclusion temporaire', 'points_retires' => 8, 'effet' => 'aucun', 'actif' => true])->assertSessionHasNoErrors();
        $this->assertSame('aucun', DB::table('types_sanction')->where('code', 'exclusion_temporaire')->value('effet'));

        // Un type appliqué garde son effet et ne se supprime pas : il se désactive.
        $this->sanction(['type' => 'exclusion_temporaire']);
        $this->put(route(self::BASE.'update', $type->id_type_sanction), ['libelle' => 'Exclusion temporaire', 'points_retires' => 8, 'effet' => 'expulsion', 'actif' => true])->assertSessionHasErrors('effet');
        $this->delete(route(self::BASE.'destroy', $type->id_type_sanction))->assertSessionHasErrors('type');
        $this->put(route(self::BASE.'update', $type->id_type_sanction), ['libelle' => 'Exclusion temporaire', 'points_retires' => 8, 'effet' => 'aucun', 'actif' => false])->assertSessionHasNoErrors();
        $this->assertNotContains('exclusion_temporaire', $this->catalogue()->actifs()->pluck('code'));
        $this->assertSame('Exclusion temporaire', $this->catalogue()->libelle('exclusion_temporaire'));

        // Un type jamais appliqué se supprime.
        $avertissement = DB::table('types_sanction')->where('code', 'avertissement')->value('id_type_sanction');
        $this->delete(route(self::BASE.'destroy', $avertissement))->assertSessionHasNoErrors();
        $this->assertNull($this->catalogue()->type('avertissement'));
    }

    public function test_note_de_conduite_et_critere_de_rejet(): void
    {
        // Points figés à la sanction, sinon barème du type ; annulée : points restitués ; levée : points conservés.
        $this->sanction(['type' => 'avertissement', 'points_retires' => 3]);
        $this->sanction(['type' => 'blame', 'statut' => 'annulee']);
        $this->sanction(['type' => 'suspension', 'statut' => 'levee']);
        // Une sanction collective ou visant un autre étudiant ne compte pas.
        $this->sanction(['type' => 'blame', 'scope_type' => 'chambre', 'etudiant_id' => null, 'chambre_id' => 'ch1']);
        $this->sanction(['type' => 'blame', 'etudiant_id' => 'e2']);

        $conduite = app(NoteConduite::class);
        $bilan = $conduite->bilan('e1');
        $this->assertSame([20.0, 13.0, 7.0], [$bilan['initiale'], $bilan['retires'], $bilan['note']]);
        $this->assertCount(3, $bilan['sanctions']);
        $this->assertSame(15.0, $conduite->note('e2'));

        $regles = fn (bool $actif, ?float $minimum) => ['readmission' => ['conduite' => ['actif' => $actif, 'note_min' => $minimum]]];
        $this->assertSame('Note de conduite insuffisante : 7/20 pour un minimum de 10.', $conduite->motifRejet($regles(true, 10), 'e1'));
        $this->assertNull($conduite->motifRejet($regles(true, 7), 'e1'));
        $this->assertNull($conduite->motifRejet($regles(false, 10), 'e1'));

        // La note ne descend pas sous 0.
        $this->sanction(['type' => 'expulsion']);
        $this->assertSame(0.0, $conduite->note('e1'));
    }

    public function test_les_decisions_utilisent_les_types_du_parametrage(): void
    {
        $catalogue = $this->catalogue();
        $travaux = $catalogue->creer(['libelle' => 'Travaux d’intérêt collectif', 'points_retires' => 4, 'effet' => 'aucun'], 'test');
        $catalogue->creer(['libelle' => 'Exclusion temporaire', 'points_retires' => 6, 'effet' => 'suspension'], 'test');
        $catalogue->creer(['libelle' => 'Ancienne mesure', 'points_retires' => 1, 'effet' => 'aucun', 'actif' => false], 'test');

        $valider = fn (array $donnees) => Validator::make($donnees + ['action' => 'decider', 'etudiant_sanctionne_id' => 'e1'], DisciplinaryImpactService::decisionRules());
        $this->assertTrue($valider(['sanction' => $travaux->code])->passes());
        $this->assertTrue($valider(['sanction' => 'aucune', 'etudiant_sanctionne_id' => null])->passes());
        // Type désactivé refusé ; type « suspension » : date de fin obligatoire.
        $this->assertTrue($valider(['sanction' => 'ancienne_mesure'])->fails());
        $this->assertArrayHasKey('sanction_fin', $valider(['sanction' => 'exclusion_temporaire'])->errors()->toArray());

        $options = collect($catalogue->options(avecAucune: true));
        $this->assertSame('aucune', $options->first()['value']);
        $this->assertNotContains('ancienne_mesure', $options->pluck('value'));
        $this->assertSame('−4 point(s) de conduite', $options->firstWhere('value', $travaux->code)['description']);

        // La décision fige les points du barème dans la sanction.
        $rapport = DisciplinaryReport::create([
            'etudiant_id' => 'e2', 'cite_id' => 'c', 'batiment_id' => 'b', 'chambre_id' => 'ch', 'date_incident' => now(),
            'nature' => 'Tapage', 'description' => 'Bruit', 'statut' => 'en_cours', 'gravite' => 'moyenne',
        ]);
        DB::transaction(fn () => app(DisciplinaryImpactService::class)->decider($rapport, [
            'sanction' => 'exclusion_temporaire', 'etudiant_sanctionne_id' => 'e1', 'sanction_fin' => today()->addWeek()->toDateString(),
            'decision' => 'Exclusion de 7 jours',
        ], 'test'));
        $sanction = Sanction::where('rapport_id', $rapport->getKey())->firstOrFail();
        $this->assertEquals([6, today()->addWeek()->toDateString()], [$sanction->points_retires, $sanction->date_fin->toDateString()]);

        // Un type à effet « suspension » bloque comme une suspension.
        $this->assertSame('exclusion_temporaire', app(DisciplinaryImpactService::class)->statut('e1'));
        $this->expectException(ValidationException::class);
        app(DisciplinaryImpactService::class)->verifier(Etudiant::findOrFail('e1'));
    }
}
