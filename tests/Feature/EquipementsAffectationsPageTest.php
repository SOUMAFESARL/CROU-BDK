<?php

namespace Tests\Feature;

use App\Http\Controllers\admin\immobilier\equipements\EquipmentAllocationController;
use App\Services\EquipmentAllocationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EquipementsAffectationsPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('equipement', function (Blueprint $t) { $t->string('equipement_id')->primary(); $t->string('name'); $t->decimal('stock_initial'); $t->decimal('stock_actuel')->nullable(); $t->timestamps(); });
        Schema::create('equipement_mouvement', function (Blueprint $t) { $t->string('equipement_mouvement_id')->primary(); $t->string('equipement_id'); $t->string('type'); $t->decimal('quantity'); $t->string('reason')->nullable(); $t->string('created_by')->nullable(); $t->timestamps(); });
        foreach (['cite' => 'id_cite', 'batiment' => 'id_batiment', 'chambre' => 'id_chambre'] as $table => $key) {
            Schema::create($table, function (Blueprint $t) use ($key) { $t->string($key)->primary(); $t->string('name')->nullable(); $t->string('numero_chambre')->nullable(); $t->string('cite_id')->nullable(); $t->string('batiment_id')->nullable(); $t->softDeletes(); });
        }
        (require database_path('migrations/2026_09_28_000002_create_equipement_affectations.php'))->up();
        DB::table('equipement')->insert([['equipement_id' => 'e', 'name' => 'Matelas', 'stock_initial' => 10], ['equipement_id' => 'f', 'name' => 'Chaise', 'stock_initial' => 5]]);
        DB::table('cite')->insert([['id_cite' => 'c', 'name' => 'Cité A'], ['id_cite' => 'd', 'name' => 'Cité B']]);
        DB::table('batiment')->insert(['id_batiment' => 'b', 'name' => 'Bât. 1', 'cite_id' => 'c']);
        DB::table('chambre')->insert(['id_chambre' => 'r', 'numero_chambre' => '12', 'cite_id' => 'c', 'batiment_id' => 'b']);

        $service = app(EquipmentAllocationService::class);
        $service->allocate(['equipement_id' => 'e', 'cite_id' => 'c', 'batiment_id' => 'b', 'chambre_id' => 'r', 'quantite' => 4, 'etat' => 'bon'], 'agent');
        $service->allocate(['equipement_id' => 'f', 'cite_id' => 'd', 'quantite' => 2, 'etat' => 'defectueux'], 'agent');
        $retour = $service->allocate(['equipement_id' => 'e', 'cite_id' => 'c', 'quantite' => 1, 'etat' => 'bon'], 'agent');
        $service->returnToStock($retour, 'agent');
    }

    private function page(array $query): array
    {
        $request = Request::create('/admin/gestion-hebergement/equipements/affectations', 'GET', $query);
        $request->headers->set('X-Inertia', 'true');
        $this->app->instance('request', $request);

        $page = app()->call([app(EquipmentAllocationController::class), 'index'], ['request' => $request])->toResponse($request)->getData(true);
        $this->assertSame('Admin/Hebergement/Equipements/Affectations', $page['component']);

        return $page['props'];
    }

    public function test_indicateurs(): void
    {
        $data = $this->page([]);

        $this->assertSame('actives', $data['statut']);
        $this->assertSame(['actives' => 2, 'unites' => 6, 'retournees' => 1, 'a_reparer' => 1, 'defectueux' => 1], array_diff_key($data['stats'], ['magasin' => 0]));
        $this->assertEquals(9, $data['stats']['magasin']);
        $this->assertSame(2, $data['affectations']['total']);
        $this->assertSame(['Cité A', 'Cité B'], array_column($data['cites'], 'name'));
        $this->assertSame([['id' => 'b', 'name' => 'Bât. 1', 'cite_id' => 'c']], $data['batiments']);
    }

    public function test_filtres_statut_cite_et_recherche(): void
    {
        $retournees = $this->page(['statut' => 'retournees'])['affectations'];
        $this->assertSame(1, $retournees['total']);
        $this->assertNotNull($retournees['data'][0]['retourne_le']);
        $this->assertSame(['Chaise'], array_column($this->page(['statut' => 'a_reparer'])['affectations']['data'], 'equipement'));
        $this->assertSame(['Matelas'], array_column($this->page(['cite_id' => 'c'])['affectations']['data'], 'equipement'));
        $this->assertSame(['Chaise'], array_column($this->page(['q' => 'chai'])['affectations']['data'], 'equipement'));
        $this->assertSame('actives', $this->page(['statut' => 'inconnu'])['statut']);
    }

    public function test_historique_des_mouvements_filtre_et_indicateurs(): void
    {
        Schema::create('type_equipement', function (Blueprint $t) { $t->string('id_type_equipement')->primary(); $t->string('name'); });
        Schema::table('equipement', function (Blueprint $t) { $t->string('type_equipement_id')->nullable(); $t->decimal('threshold')->default(0); });
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->string('code'); $t->string('name'); $t->softDeletes(); });

        $historique = function (array $query) {
            $request = Request::create('/admin/gestion-hebergement/equipements/e/mouvement', 'GET', $query);
            $request->headers->set('X-Inertia', 'true');
            $this->app->instance('request', $request);

            $page = app()->call([app(\App\Http\Controllers\admin\immobilier\equipements\EquipementController::class), 'mouvements'], [
                'request' => $request, 'materiel' => \App\Models\Equipement::findOrFail('e'),
            ])->toResponse($request)->getData(true);
            $this->assertSame('Admin/Hebergement/Equipements/Historique', $page['component']);

            return $page['props'];
        };

        $data = $historique([]);
        $this->assertSame('tous', $data['type']);
        // Stock initial reporté en entrée + 2 affectations (sorties) + 1 retour (entrée).
        $this->assertSame(4, $data['stats']['tous']);
        $this->assertSame(2, $data['stats']['entry']);
        $this->assertSame(2, $data['stats']['exit']);
        $this->assertSame(4, $data['mouvements']['total']);
        $this->assertSame('Matelas', $data['equipement']['name']);

        $sorties = $historique(['type' => 'exit']);
        $this->assertSame(2, $sorties['mouvements']['total']);
        $this->assertSame(['exit', 'exit'], array_column($sorties['mouvements']['data'], 'type'));
        $this->assertSame('tous', $historique(['type' => 'inconnu'])['type']);
    }
}
