<?php

namespace Tests\Feature;

use App\Models\Etudiant;
use App\Services\EtudiantEtatDesLieux;
use App\Services\EtudiantMenu;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Page React « État des lieux » de l'espace étudiant (Etudiant/EtatDesLieux). */
class StudentInventoryPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('assignation_chambres', function (Blueprint $t) {
            $t->string('id_assignation')->primary();
            $t->string('etudiant_id');
            $t->string('chambre_id');
            $t->string('lit_id')->nullable();
            $t->string('statut');
            $t->dateTime('date_assignation');
            $t->dateTime('date_fin')->nullable();
            $t->timestamps();
        });
        Schema::create('chambre', function (Blueprint $t) {
            $t->string('id_chambre')->primary();
            $t->string('numero_chambre');
            $t->string('code_chambre')->nullable();
            foreach (['cite_id', 'batiment_id', 'campus_id', 'type_chambre_id'] as $f) {
                $t->string($f)->nullable();
            }
            $t->softDeletes();
        });
        foreach (['cite' => 'id_cite', 'batiment' => 'id_batiment'] as $table => $key) {
            Schema::create($table, function (Blueprint $t) use ($key) {
                $t->string($key)->primary();
                $t->string('name');
                $t->softDeletes();
            });
        }
        Schema::create('etat_des_lieux', function (Blueprint $t) {
            $t->string('etat_des_lieux_id')->primary();
            foreach (['id_cite', 'id_batiment', 'id_chambre', 'id_etudiant', 'type_etat', 'observation_generale', 'signature_crou', 'signature_etudiant', 'created_by', 'updated_by'] as $f) {
                $t->string($f)->nullable();
            }
            $t->dateTime('date_etat')->nullable();
            $t->integer('nbre_cle_recus')->nullable();
            $t->timestamps();
        });
        Schema::create('edl_piece', function (Blueprint $t) {
            $t->string('edl_piece_id')->primary();
            $t->string('id_etat_des_lieux');
            $t->string('name_piece');
            $t->integer('ordre');
            $t->string('created_by')->nullable();
            $t->string('updated_by')->nullable();
            $t->timestamps();
        });
        Schema::create('edl_element', function (Blueprint $t) {
            $t->string('edl_element_id')->primary();
            $t->string('id_edl_piece');
            $t->string('name_element');
            $t->string('etat');
            $t->text('observation')->nullable();
            $t->string('photo')->nullable();
            $t->integer('ordre');
            $t->string('created_by')->nullable();
            $t->string('updated_by')->nullable();
            $t->timestamps();
        });
        Schema::create('edl_image', function (Blueprint $t) {
            $t->string('edl_image_id')->primary();
            $t->string('id_edl_piece');
            $t->string('chemin_fichier');
            $t->string('created_by')->nullable();
            $t->string('updated_by')->nullable();
            $t->timestamps();
        });

        DB::table('cite')->insert(['id_cite' => 'cite', 'name' => 'Cité Nord']);
        DB::table('batiment')->insert(['id_batiment' => 'bat', 'name' => 'Bâtiment B']);
        DB::table('chambre')->insert(['id_chambre' => 'room', 'numero_chambre' => 'B-12', 'cite_id' => 'cite', 'batiment_id' => 'bat']);
        DB::table('assignation_chambres')->insert(['id_assignation' => 'stay', 'etudiant_id' => 'student', 'chambre_id' => 'room',
            'statut' => 'confirmee', 'date_assignation' => now()->subMonth(), 'created_at' => now()->subMonth()]);
    }

    private function constat(string $id, string $type, array $extra = []): void
    {
        DB::table('etat_des_lieux')->insert($extra + [
            'etat_des_lieux_id' => $id, 'id_cite' => 'cite', 'id_batiment' => 'bat', 'id_chambre' => 'room', 'id_etudiant' => 'student',
            'type_etat' => $type, 'date_etat' => '2026-09-01 10:00:00', 'nbre_cle_recus' => 2,
        ]);
        DB::table('edl_piece')->insert(['edl_piece_id' => $id.'-p', 'id_etat_des_lieux' => $id, 'name_piece' => 'Chambre', 'ordre' => 1]);
        DB::table('edl_element')->insert([
            ['edl_element_id' => $id.'-e2', 'id_edl_piece' => $id.'-p', 'name_element' => 'Porte', 'etat' => $type === 'sortie' ? 'mauvais' : 'bon',
                'observation' => $type === 'sortie' ? 'Serrure cassée' : null, 'photo' => null, 'ordre' => 2],
            ['edl_element_id' => $id.'-e1', 'id_edl_piece' => $id.'-p', 'name_element' => 'Sol', 'etat' => 'moyen',
                'observation' => 'Rayures', 'photo' => 'edl/photos/sol.jpg', 'ordre' => 1],
        ]);
        DB::table('edl_image')->insert(['edl_image_id' => $id.'-i', 'id_edl_piece' => $id.'-p', 'chemin_fichier' => 'edl/photos/vue.jpg']);
    }

    private function props(): array
    {
        return app(EtudiantEtatDesLieux::class)->props(new Etudiant(['id_etudiant' => 'student', 'name' => 'Awa Koné']));
    }

    public function test_room_and_entry_inventory_are_exposed_for_editing(): void
    {
        $this->constat('in', 'entree', ['observation_generale' => 'RAS', 'signature_etudiant' => 'signatures/etu.png']);
        // Constat d'un autre étudiant sur la même chambre : jamais exposé.
        DB::table('etat_des_lieux')->insert(['etat_des_lieux_id' => 'other', 'id_chambre' => 'room', 'id_etudiant' => 'mate', 'type_etat' => 'sortie', 'date_etat' => now()]);

        $props = $this->props();

        $this->assertSame(['idCite' => 'cite', 'idBatiment' => 'bat', 'idChambre' => 'room', 'cite' => 'Cité Nord', 'batiment' => 'Bâtiment B', 'numero' => 'B-12'], $props['chambre']);
        $this->assertNull($props['sortie']);
        $entree = $props['entree'];
        $this->assertSame(['in', '2026-09-01', '01/09/2026', 2, 'RAS'], [$entree['id'], $entree['date'], $entree['dateAffichee'], $entree['cles'], $entree['observation']]);
        $this->assertNull($entree['signatureCrou']);
        $this->assertStringEndsWith('/signatures/etu.png', $entree['signatureEtudiant']);
        // Éléments dans l'ordre de saisie, photos avec chemin (renvoyé tel quel à l'enregistrement) et URL d'affichage.
        $piece = $entree['pieces'][0];
        $this->assertSame(['Sol', 'Porte'], array_column($piece['elements'], 'nom'));
        $this->assertSame('edl/photos/sol.jpg', $piece['elements'][0]['photo']['chemin']);
        $this->assertStringEndsWith('/edl/photos/sol.jpg', $piece['elements'][0]['photo']['url']);
        $this->assertNull($piece['elements'][1]['photo']);
        $this->assertSame('edl/photos/vue.jpg', $piece['photos'][0]['chemin']);
        $this->assertSame(route('etudiants.update_eld', 'in'), $entree['modifier']);
        $this->assertSame(route('etudiants.documents.inventory', 'in'), $entree['pdf']);
    }

    public function test_exit_inventory_is_exposed_for_signature(): void
    {
        $this->constat('in', 'entree', ['signature_crou' => 'signatures/crou.png']);
        $this->constat('out', 'sortie', ['signature_crou' => 'signatures/crou2.png']);

        $props = $this->props();

        $this->assertNotNull($props['entree']['signatureCrou']);
        $this->assertSame('out', $props['sortie']['id']);
        $this->assertNull($props['sortie']['signatureEtudiant']);
        $this->assertSame(['etat' => 'mauvais', 'observation' => 'Serrure cassée'], array_intersect_key($props['sortie']['pieces'][0]['elements'][1], ['etat' => 1, 'observation' => 1]));
        $this->assertSame(['idCite' => 'cite', 'idBatiment' => 'bat', 'idChambre' => 'room'], $props['sortie']['lieu']);
    }

    public function test_student_without_room_has_nothing_to_fill(): void
    {
        DB::table('assignation_chambres')->delete();

        $props = $this->props();

        $this->assertNull($props['chambre']);
        $this->assertNull($props['entree']);
        $this->assertNull($props['sortie']);
    }

    public function test_section_is_served_by_react(): void
    {
        $this->assertContains('etat-lieux', EtudiantMenu::SECTIONS_REACT);
    }
}
