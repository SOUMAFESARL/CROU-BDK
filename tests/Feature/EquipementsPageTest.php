<?php

namespace Tests\Feature;

use App\Http\Controllers\admin\immobilier\equipements\EquipementController;
use App\Models\Equipement;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EquipementsPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('type_equipement', function (Blueprint $t) { $t->string('id_type_equipement')->primary(); $t->string('name'); $t->timestamps(); $t->softDeletes(); });
        Schema::create('equipement', function (Blueprint $t) {
            $t->string('equipement_id')->primary(); $t->string('name'); $t->string('reference')->nullable(); $t->string('type_equipement_id')->nullable();
            $t->decimal('stock_initial'); $t->decimal('stock_actuel')->nullable(); $t->integer('threshold')->default(0); $t->string('photo')->nullable(); $t->timestamps();
        });
        Schema::create('equipement_mouvement', function (Blueprint $t) { $t->string('equipement_mouvement_id')->primary(); $t->string('equipement_id'); $t->string('type'); $t->decimal('quantity'); $t->string('reason')->nullable(); $t->string('created_by')->nullable(); $t->timestamps(); });

        DB::table('type_equipement')->insert([['id_type_equipement' => 'lit', 'name' => 'Literie'], ['id_type_equipement' => 'mob', 'name' => 'Mobilier']]);
        $rows = [['Matelas', 'lit', 50, 5], ['Oreiller', 'lit', 3, 5], ['Chaise', 'mob', 0, 2]];
        for ($i = 1; $i <= 10; $i++) {
            $rows[] = ["Table $i", 'mob', 20, 1];
        }
        foreach ($rows as $i => [$name, $type, $stock, $seuil]) {
            DB::table('equipement')->insert(['equipement_id' => "e$i", 'name' => $name, 'type_equipement_id' => $type, 'stock_initial' => $stock, 'threshold' => $seuil]);
        }
    }

    private function page(array $query): array
    {
        $request = Request::create('/admin/gestion-hebergement/equipements', 'GET', $query);
        $this->app->instance('request', $request);

        return app(EquipementController::class)->index($request)->getData();
    }

    public function test_indicateurs_et_pagination_par_dix(): void
    {
        $data = $this->page([]);

        $this->assertSame(['tous' => 13, 'disponible' => 11, 'faible' => 1, 'rupture' => 1], $data['stats']);
        $this->assertSame(13, $data['equipements']->total());
        $this->assertCount(10, $data['equipements']->items());
        $this->assertEquals(253, $data['stockTotal']);
    }

    public function test_filtres_statut_type_et_recherche_sans_accents(): void
    {
        $this->assertSame(['Oreiller'], collect($this->page(['statut' => 'faible'])['equipements']->items())->pluck('name')->all());
        $this->assertSame(['Chaise'], collect($this->page(['statut' => 'rupture'])['equipements']->items())->pluck('name')->all());
        $this->assertSame(2, $this->page(['type' => 'lit'])['equipements']->total());
        $this->assertSame(2, $this->page(['q' => 'LITERIE'])['equipements']->total());
        $this->assertSame('tous', $this->page(['statut' => 'inconnu'])['statut']);
        $this->assertSame('', $this->page(['type' => 'inconnu'])['type']);
    }

    public function test_suppression_depuis_le_formulaire_redirige_avec_un_message(): void
    {
        DB::table('equipement')->where('equipement_id', 'e2')->update(['stock_initial' => 0]);

        $response = app(EquipementController::class)->destroy(Equipement::find('e2'));

        $this->assertTrue($response->isRedirect());
        $this->assertSame('Équipement supprimé avec succès.', session('success'));
        $this->assertNull(Equipement::find('e2'));
    }

    public function test_la_vue_compile(): void
    {
        $compiled = app('blade.compiler')->compileString(file_get_contents(resource_path('views/admin/hebergement/equipements/index.blade.php')));
        $this->assertNotEmpty(token_get_all($compiled, TOKEN_PARSE));
    }
}
