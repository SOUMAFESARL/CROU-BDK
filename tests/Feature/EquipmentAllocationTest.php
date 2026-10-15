<?php

namespace Tests\Feature;

use App\Models\Equipement;
use App\Models\EquipementAffectation;
use App\Services\EquipmentAllocationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EquipmentAllocationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('equipement', function (Blueprint $t) { $t->string('equipement_id')->primary(); $t->string('name'); $t->decimal('stock_initial'); $t->decimal('stock_actuel')->nullable(); $t->timestamps(); });
        Schema::create('equipement_mouvement', function (Blueprint $t) { $t->string('equipement_mouvement_id')->primary(); $t->string('equipement_id'); $t->string('type'); $t->decimal('quantity'); $t->string('reason')->nullable(); $t->string('created_by')->nullable(); $t->timestamps(); });
        foreach (['cite' => 'id_cite', 'batiment' => 'id_batiment', 'chambre' => 'id_chambre'] as $table => $key) {
            Schema::create($table, function (Blueprint $t) use ($key) { $t->string($key)->primary(); $t->string('cite_id')->nullable(); $t->string('batiment_id')->nullable(); $t->softDeletes(); });
        }
        (require database_path('migrations/2026_09_28_000002_create_equipement_affectations.php'))->up();
        DB::table('equipement')->insert(['equipement_id' => 'e', 'name' => 'Matelas', 'stock_initial' => 10]);
        DB::table('cite')->insert(['id_cite' => 'c']);
        DB::table('batiment')->insert(['id_batiment' => 'b', 'cite_id' => 'c']);
        DB::table('chambre')->insert(['id_chambre' => 'r', 'cite_id' => 'c', 'batiment_id' => 'b']);
    }
    private function data(): array { return ['equipement_id' => 'e', 'cite_id' => 'c', 'batiment_id' => 'b', 'chambre_id' => 'r', 'quantite' => 4, 'etat' => 'bon']; }

    public function test_allocation_and_repeated_return_conserve_stock(): void
    {
        $service = app(EquipmentAllocationService::class);
        $allocation = $service->allocate($this->data(), 'agent');
        $this->assertEquals(6, Equipement::find('e')->stock_actuel);
        $this->assertSame('r', $allocation->chambre->id_chambre);
        $service->returnToStock($allocation, 'agent');
        $service->returnToStock($allocation, 'agent');
        $this->assertEquals(10, Equipement::find('e')->stock_actuel);
        $this->assertSame(3, DB::table('equipement_mouvement')->count());
    }
    public function test_invalid_hierarchy_and_overallocation_do_not_change_stock(): void
    {
        foreach ([['quantite' => 11], ['batiment_id' => null], ['chambre_id' => 'wrong']] as $changes) {
            try {
                app(EquipmentAllocationService::class)->allocate(array_replace($this->data(), $changes), 'agent');
                $this->fail('Invalid allocation accepted');
            } catch (ValidationException $e) {
                $this->assertNotEmpty($e->errors());
            }
        }
        $this->assertSame(0, EquipementAffectation::count());
        $this->assertSame(0, DB::table('equipement_mouvement')->count());
    }
    public function test_defective_equipment_does_not_return_to_available_stock(): void
    {
        $service = app(EquipmentAllocationService::class);
        $allocation = $service->allocate(array_replace($this->data(), ['etat' => 'defectueux']), 'agent');
        try { $service->returnToStock($allocation, 'agent'); $this->fail('Defective return accepted'); }
        catch (ValidationException $e) { $this->assertArrayHasKey('etat', $e->errors()); }
        $this->assertEquals(6, Equipement::find('e')->stock_actuel);
        $this->assertNull($allocation->fresh()->returned_at);
    }
    public function test_initial_entry_is_not_counted_twice(): void
    {
        Equipement::find('e')->mouvements()->create(['type' => 'entry', 'quantity' => 10]);
        app(EquipmentAllocationService::class)->allocate($this->data(), 'agent');
        $this->assertEquals(6, Equipement::find('e')->stock_actuel);
    }
}
