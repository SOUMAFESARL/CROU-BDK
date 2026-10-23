<?php

namespace Tests\Feature;

use App\Models\Etudiant;
use App\Services\EtudiantCarte;
use App\Services\EtudiantMenu;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Page React « Carte interactive » de l'espace étudiant (Etudiant/Carte). */
class StudentMapPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('type_cite', function (Blueprint $t) {
            $t->string('id_type_cite')->primary();
            $t->string('name')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('cite', function (Blueprint $t) {
            $t->string('id_cite')->primary();
            foreach (['name', 'statut', 'type_cite_id', 'adresse', 'telephone1', 'email1', 'latitude', 'longitude'] as $colonne) {
                $t->string($colonne)->nullable();
            }
            foreach (['wifi_gratuit', 'salle_etude', 'infirmerie', 'restaurant'] as $colonne) {
                $t->boolean($colonne)->default(false);
            }
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('batiment', function (Blueprint $t) {
            $t->string('id_batiment')->primary();
            foreach (['name', 'statut', 'cite_id', 'type_occupation_id', 'latitude', 'longitude'] as $colonne) {
                $t->string($colonne)->nullable();
            }
            $t->integer('is_valid')->default(1);
            $t->integer('total_etages')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('campus', function (Blueprint $t) {
            $t->string('id_campus')->primary();
            foreach (['name', 'statut', 'adresse', 'telephone1', 'latitude', 'longitude'] as $colonne) {
                $t->string($colonne)->nullable();
            }
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('chambre', function (Blueprint $t) {
            $t->string('id_chambre')->primary();
            foreach (['numero_chambre', 'code_chambre', 'batiment_id', 'cite_id'] as $colonne) {
                $t->string($colonne)->nullable();
            }
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('assignation_chambres', function (Blueprint $t) {
            $t->string('id_assignation')->primary();
            foreach (['etudiant_id', 'chambre_id', 'statut'] as $colonne) {
                $t->string($colonne)->nullable();
            }
            $t->timestamp('date_assignation')->nullable();
            $t->timestamp('date_fin')->nullable();
            $t->timestamps();
        });

        DB::table('type_cite')->insert(['id_type_cite' => 'mixte', 'name' => 'Mixte']);
        DB::table('cite')->insert([
            ['id_cite' => 'nord', 'name' => 'Cité Nord', 'statut' => 'ouvert', 'type_cite_id' => 'mixte', 'latitude' => '8.04', 'longitude' => '-2.80', 'wifi_gratuit' => true, 'restaurant' => true],
            ['id_cite' => 'sud', 'name' => 'Cité Sud', 'statut' => 'ouvert', 'type_cite_id' => null, 'latitude' => null, 'longitude' => null, 'wifi_gratuit' => false, 'restaurant' => false],
            ['id_cite' => 'fermee', 'name' => 'Cité fermée', 'statut' => 'ferme', 'type_cite_id' => null, 'latitude' => '8.05', 'longitude' => '-2.81', 'wifi_gratuit' => false, 'restaurant' => false],
        ]);
        DB::table('batiment')->insert([
            ['id_batiment' => 'a', 'name' => 'A', 'statut' => 'actif', 'cite_id' => 'nord', 'latitude' => '8.041', 'longitude' => '-2.801', 'is_valid' => 1],
            ['id_batiment' => 'b', 'name' => 'B', 'statut' => 'desactive', 'cite_id' => 'nord', 'latitude' => '8.042', 'longitude' => '-2.802', 'is_valid' => 1],
            ['id_batiment' => 'c', 'name' => 'C', 'statut' => 'actif', 'cite_id' => 'sud', 'latitude' => '0', 'longitude' => '0', 'is_valid' => 1],
        ]);
        DB::table('campus')->insert([
            ['id_campus' => 'u', 'name' => 'Université', 'statut' => 'ouvert', 'latitude' => '8.03', 'longitude' => '-2.79'],
            ['id_campus' => 'v', 'name' => 'Sans position', 'statut' => 'ouvert', 'latitude' => null, 'longitude' => null],
        ]);
        DB::table('chambre')->insert(['id_chambre' => 'room', 'numero_chambre' => 'A12', 'batiment_id' => 'a', 'cite_id' => 'nord']);
    }

    private function etudiant(): Etudiant
    {
        return (new Etudiant)->forceFill(['id_etudiant' => 'student']);
    }

    public function test_props_list_geolocated_active_places_without_occupation(): void
    {
        $props = app(EtudiantCarte::class)->props($this->etudiant());

        $this->assertSame(['nord'], collect($props['cites'])->pluck('id')->all(), 'Cité fermée et cité sans position ni bâtiment positionné exclues');
        $this->assertSame(['Wi-Fi gratuit', 'Restaurant'], $props['cites'][0]['services']);
        $this->assertSame('Mixte', $props['cites'][0]['type']);
        $this->assertSame(['a'], collect($props['batiments'])->pluck('id')->all(), 'Bâtiment désactivé et bâtiment à (0,0) exclus');
        $this->assertSame(['u'], collect($props['campus'])->pluck('id')->all());
        $this->assertNull($props['residence']);
        $this->assertArrayNotHasKey('resume', $props['cites'][0]);
    }

    public function test_active_assignment_highlights_student_residence(): void
    {
        DB::table('assignation_chambres')->insert([
            'id_assignation' => 'stay', 'etudiant_id' => 'student', 'chambre_id' => 'room', 'statut' => 'confirmee', 'date_assignation' => now()->subMonth(),
        ]);

        $props = app(EtudiantCarte::class)->props($this->etudiant());

        $this->assertSame(['cite_id' => 'nord', 'batiment_id' => 'a', 'chambre' => 'A12'], $props['residence']);
    }

    public function test_carte_interactive_is_a_react_section(): void
    {
        $this->assertContains('carte-interactive', EtudiantMenu::SECTIONS_REACT);
    }
}
