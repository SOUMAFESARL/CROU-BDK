<?php

namespace Tests\Feature;

use App\Models\DisciplinaryReport;
use App\Models\Etudiant;
use App\Services\EtudiantIncidents;
use App\Services\EtudiantMenu;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Page React « Signaler un incident » de l'espace étudiant (Etudiant/Incidents, `?section=incidents`). */
class StudentIncidentsPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->withoutMiddleware([\App\Http\Middleware\EnforceSystemSecurity::class, \App\Http\Middleware\LogUserActivity::class]);

        foreach (['cite' => 'id_cite', 'batiment' => 'id_batiment'] as $table => $cle) {
            Schema::create($table, function (Blueprint $t) use ($cle) {
                $t->string($cle)->primary();
                $t->string('name')->nullable();
                $t->softDeletes();
            });
        }
        Schema::create('chambre', function (Blueprint $t) {
            $t->string('id_chambre')->primary();
            foreach (['numero_chambre', 'code_chambre', 'batiment_id', 'cite_id'] as $colonne) {
                $t->string($colonne)->nullable();
            }
            $t->softDeletes();
        });
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
            $t->timestamp('date_assignation');
            $t->timestamp('date_fin')->nullable();
        });
        (require database_path('migrations/2026_08_10_000003_create_disciplinary_reports_tables.php'))->up();
        foreach ([
            '2026_08_17_000001_add_student_declaration_fields_to_disciplinary_reports.php',
            '2026_08_10_000001_create_sanctions_tables.php',
            '2026_08_10_000002_add_scope_to_sanctions_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }

        DB::table('cite')->insert(['id_cite' => 'cite', 'name' => 'Cité Nord']);
        DB::table('batiment')->insert(['id_batiment' => 'bat', 'name' => 'Bâtiment A']);
        DB::table('chambre')->insert(['id_chambre' => 'room', 'numero_chambre' => 'A12', 'batiment_id' => 'bat', 'cite_id' => 'cite']);
        DB::table('etudiants')->insert([['id_etudiant' => 'student', 'name' => 'Résident'], ['id_etudiant' => 'other', 'name' => 'Autre']]);
    }

    private function assigner(string $etudiant): void
    {
        DB::table('assignation_chambres')->insert([
            'id_assignation' => 'as-'.$etudiant, 'etudiant_id' => $etudiant, 'chambre_id' => 'room',
            'statut' => 'confirmee', 'date_assignation' => now()->subMonth(),
        ]);
    }

    private function rapport(string $declarant, string $statut, array $donnees = []): void
    {
        DisciplinaryReport::create(array_merge([
            'etudiant_id' => $declarant, 'declarant_id' => $declarant, 'source' => 'etudiant', 'cite_id' => 'cite', 'batiment_id' => 'bat',
            'chambre_id' => 'room', 'date_incident' => now()->subDay(), 'nature' => 'Tapage / nuisance sonore', 'lieu' => 'Couloir',
            'description' => 'Bruit toute la nuit dans le couloir.', 'gravite' => 'moyenne', 'statut' => $statut,
        ], $donnees));
    }

    public function test_props_list_only_own_reports_with_counters_and_residence(): void
    {
        $this->assigner('student');
        DB::table('sanctions')->insert([
            'id_sanction' => 'sa', 'numero_sanction' => 'SAN-1', 'date_decision' => now()->subDay(), 'etudiant_id' => 'student', 'scope_type' => 'etudiant', 'type' => 'avertissement', 'statut' => 'active',
            'motif' => 'Tapage', 'date_debut' => now()->subDay(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->rapport('student', 'transmis', ['confidentiel' => true, 'gravite' => 'grave']);
        $this->rapport('student', 'cloture', ['date_incident' => now()->subDays(5)]);
        $this->rapport('student', 'retourne', ['date_incident' => now()->subDays(9)]);
        $this->rapport('other', 'en_cours');

        $props = app(EtudiantIncidents::class)->props(Etudiant::findOrFail('student'));

        $this->assertSame(['cle' => 'avertissement', 'libelle' => 'Avertissement'], $props['statutDisciplinaire']);
        $this->assertSame(['cite' => 'Cité Nord', 'batiment' => 'Bâtiment A', 'chambre' => 'A12'], $props['residence']);
        $this->assertSame(['total' => 3, 'enCours' => 1, 'traites' => 1, 'retournes' => 1], $props['compteurs']);
        $this->assertSame(['transmis', 'cloture', 'retourne'], array_column(array_column($props['signalements'], 'statut'), 'cle'));
        $premier = $props['signalements'][0];
        $this->assertTrue($premier['confidentiel']);
        $this->assertSame(['cle' => 'grave', 'libelle' => 'Grave'], $premier['gravite']);
        $this->assertSame('Transmis', $premier['statut']['libelle']);
        // Ni décision ni sanction envoyées à la page.
        $this->assertArrayNotHasKey('decision', $premier);
        $this->assertCount(count(EtudiantIncidents::NATURES), $props['natures']);
        $this->assertSame(3, $props['pagination']['total']);
    }

    public function test_props_without_active_room_or_sanction(): void
    {
        $props = app(EtudiantIncidents::class)->props(Etudiant::findOrFail('student'));

        $this->assertNull($props['statutDisciplinaire']);
        $this->assertNull($props['residence']);
        $this->assertSame([], $props['signalements']);
    }

    public function test_store_creates_report_and_redirects_with_flash(): void
    {
        $this->assigner('student');
        $this->actingAs(Etudiant::findOrFail('student'), 'etudiant');

        $this->from(route('etudiants.dashboard', ['section' => 'incidents']))
            ->post(route('etudiants.incidents.store'), [
                'nature' => 'vol', 'date_incident' => now()->subHour()->format('Y-m-d\TH:i'), 'lieu' => 'Salle d’étude',
                'description' => 'Mon ordinateur a disparu de la salle d’étude.', 'gravite' => 'grave', 'confidentiel' => 1,
            ])
            ->assertRedirect(route('etudiants.dashboard', ['section' => 'incidents']))
            ->assertSessionHas('success');

        $rapport = DisciplinaryReport::firstOrFail();
        $this->assertSame(['Vol ou tentative de vol', 'transmis', 'room', 'student'], [$rapport->nature, $rapport->statut, $rapport->chambre_id, $rapport->declarant_id]);
        $this->assertTrue((bool) $rapport->confidentiel);
    }

    public function test_store_rejects_invalid_input_and_missing_room(): void
    {
        $this->actingAs(Etudiant::findOrFail('student'), 'etudiant');

        $this->post(route('etudiants.incidents.store'), ['nature' => 'inconnue', 'description' => 'Trop court', 'gravite' => 'moyenne'])
            ->assertSessionHasErrors(['nature', 'date_incident', 'lieu', 'description']);

        $this->post(route('etudiants.incidents.store'), [
            'nature' => 'tapage', 'date_incident' => now()->subHour()->format('Y-m-d\TH:i'), 'lieu' => 'Couloir',
            'description' => 'Musique très forte après minuit.', 'gravite' => 'faible',
        ])->assertSessionHasErrors(['residence']);
        $this->assertSame(0, DisciplinaryReport::count());
    }

    public function test_section_is_served_by_react(): void
    {
        $this->assertContains('incidents', EtudiantMenu::SECTIONS_REACT);
        $this->assertFileDoesNotExist(resource_path('views/etudiant/incidents.blade.php'));
    }
}
