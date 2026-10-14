<?php

namespace Tests\Feature;

use App\Models\Chambre;
use App\Models\Intervention;
use App\Models\OrdreTravail;
use App\Repositories\OrdreTravailRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ParcMaintenanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('ordres_travail', function (Blueprint $table) {
            $table->string('id_ordres_travail')->primary();
            $table->string('chambre_id')->nullable();
            $table->string('statut');
            $table->string('solution_apportee')->nullable();
            $table->timestamp('date_resolution')->nullable();
            $table->timestamp('date_fin_intervention')->nullable();
            $table->timestamps();
        });
        Schema::create('interventions', function (Blueprint $table) {
            $table->string('id_intervention')->primary();
            $table->string('id_ordres_travail');
            $table->timestamps();
        });
        Schema::create('chambre', function (Blueprint $table) {
            $table->string('id_chambre')->primary();
            $table->string('etat');
            $table->string('statut');
            $table->integer('capacite_max');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('assignation_chambres', function (Blueprint $table) {
            $table->string('id_assignation')->primary();
            $table->string('chambre_id');
            $table->string('statut');
            $table->timestamp('date_assignation');
            $table->timestamp('date_fin')->nullable();
        });
        DB::table('chambre')->insert([
            'id_chambre' => 'room', 'etat' => 'maintenance', 'statut' => 'actif', 'capacite_max' => 1,
        ]);
        DB::table('ordres_travail')->insert([
            'id_ordres_travail' => 'order', 'chambre_id' => 'room', 'statut' => 'en_cours',
        ]);
    }

    private function terminer(): void
    {
        Model::withoutEvents(fn () => (new OrdreTravailRepository(new OrdreTravail()))
            ->updateStatut('order', 'termine'));
    }

    public function test_interventions_are_linked_in_both_directions(): void
    {
        $ordre = OrdreTravail::findOrFail('order');
        Model::withoutEvents(fn () => $ordre->ajouterIntervention(['id_intervention' => 'intervention']));

        $this->assertSame('order', Intervention::findOrFail('intervention')->ordreTravail->getKey());
        $this->assertSame('intervention', $ordre->fresh()->interventions->sole()->getKey());
    }

    public function test_completion_makes_an_empty_room_available(): void
    {
        $this->terminer();
        $this->assertSame('disponible', Chambre::findOrFail('room')->etat);
        $this->assertSame('actif', Chambre::findOrFail('room')->statut);
        $this->assertNotNull(OrdreTravail::findOrFail('order')->date_resolution);
    }

    public function test_completion_keeps_a_full_room_occupied(): void
    {
        DB::table('assignation_chambres')->insert([
            'id_assignation' => 'assignment', 'chambre_id' => 'room',
            'statut' => 'confirmee', 'date_assignation' => now()->subDay(),
        ]);
        $this->terminer();
        $this->assertSame('occupee', Chambre::findOrFail('room')->etat);
    }

    public function test_other_open_orders_keep_the_room_in_maintenance(): void
    {
        foreach (['en_attente', 'en_cours', 'en_attente_pieces', 'reporte'] as $statut) {
            DB::table('ordres_travail')->updateOrInsert(['id_ordres_travail' => 'other'], [
                'chambre_id' => 'room', 'statut' => $statut,
            ]);
            $this->terminer();
            $this->assertSame('maintenance', Chambre::findOrFail('room')->etat, $statut);
        }
    }

    public function test_completion_preserves_administrative_deactivation_and_closed_rooms(): void
    {
        DB::table('chambre')->update(['statut' => 'desactive', 'etat' => 'fermee']);
        $this->terminer();
        $this->assertSame('desactive', Chambre::findOrFail('room')->statut);
        $this->assertSame('fermee', Chambre::findOrFail('room')->etat);
    }

    public function test_completion_preserves_manual_room_blocks(): void
    {
        foreach (['reservee', 'hors_service', 'fermee'] as $etat) {
            DB::table('chambre')->update(['etat' => $etat]);
            $this->terminer();
            $this->assertSame($etat, Chambre::findOrFail('room')->etat);
        }
    }

    public function test_direct_completion_also_updates_the_room_and_preserves_resolution_date(): void
    {
        Model::withoutEvents(fn () => OrdreTravail::findOrFail('order')->terminer('Reparation effectuee'));
        $date = OrdreTravail::findOrFail('order')->date_resolution;
        $this->travel(1)->days();
        $this->terminer();
        $this->assertSame('disponible', Chambre::findOrFail('room')->etat);
        $this->assertEquals($date, OrdreTravail::findOrFail('order')->date_resolution);
        $this->assertSame('Reparation effectuee', OrdreTravail::findOrFail('order')->solution_apportee);
        $this->travelBack();
    }
}
