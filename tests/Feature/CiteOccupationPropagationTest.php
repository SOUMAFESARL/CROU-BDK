<?php

namespace Tests\Feature;

use App\Http\Services\OccupationService;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Modifier une cité propage son type d'occupation à ses bâtiments puis à leurs chambres.
 * Régression : la propagation filtrait les chambres sur `id_batiment` (colonne inexistante, la bonne est
 * `batiment_id`) ; toute modification d'une cité ayant des bâtiments échouait en erreur SQL.
 */
class CiteOccupationPropagationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('batiment', function (Blueprint $t) {
            $t->string('id_batiment')->primary(); $t->string('cite_id')->nullable(); $t->string('type_occupation_id')->nullable();
            $t->string('updated_by')->nullable(); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('chambre', function (Blueprint $t) {
            $t->string('id_chambre')->primary(); $t->string('batiment_id')->nullable(); $t->string('type_occupation_id')->nullable();
            $t->string('updated_by')->nullable(); $t->timestamps(); $t->softDeletes();
        });

        DB::table('batiment')->insert(['id_batiment' => 'b1', 'cite_id' => 'c1', 'type_occupation_id' => 'mixte']);
        DB::table('chambre')->insert([
            ['id_chambre' => 'r1', 'batiment_id' => 'b1', 'type_occupation_id' => 'mixte'],
            ['id_chambre' => 'r2', 'batiment_id' => 'autre', 'type_occupation_id' => 'mixte'],
        ]);

        $this->actingAs(new User(['id_user' => 'actor', 'role_id' => 1, 'name' => 'Admin']), 'admin');
    }

    public function test_le_type_de_la_cite_est_propage_aux_batiments_et_chambres(): void
    {
        app(OccupationService::class)->propagateOccupationFromCite('c1', 'femmes');

        $this->assertSame('femmes', DB::table('batiment')->where('id_batiment', 'b1')->value('type_occupation_id'));
        $this->assertSame('femmes', DB::table('chambre')->where('id_chambre', 'r1')->value('type_occupation_id'));
        // Les chambres d'un autre bâtiment ne sont pas touchées.
        $this->assertSame('mixte', DB::table('chambre')->where('id_chambre', 'r2')->value('type_occupation_id'));
    }
}
