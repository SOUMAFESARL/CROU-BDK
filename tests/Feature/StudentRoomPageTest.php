<?php

namespace Tests\Feature;

use App\Models\Etudiant;
use App\Models\OrdreTravail;
use App\Repositories\Interfaces\OrdreTravailServiceInterface;
use App\Services\EtudiantChambre;
use App\Services\EtudiantMaintenance;
use Illuminate\Http\Request;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Pages React « Ma chambre » (Etudiant/MaChambre) et « Maintenance » (Etudiant/Maintenance), signalement d'un problème. */
class StudentRoomPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $settings = new \App\Models\ConfigApp(['app_name' => 'Residence test']);
        $settings->setRelation('region', null)->setRelation('ville', null);
        $this->mock(\App\Repositories\Interfaces\ConfigAppRepositoryInterface::class)
            ->shouldReceive('getConfig')->andReturn($settings);
        $this->withoutMiddleware([\App\Http\Middleware\EnforceSystemSecurity::class, \App\Http\Middleware\LogUserActivity::class]);

        foreach (['cite' => 'id_cite', 'batiment' => 'id_batiment', 'campus' => 'id_campus', 'equipement' => 'equipement_id'] as $table => $cle) {
            Schema::create($table, function (Blueprint $t) use ($cle) {
                $t->string($cle)->primary();
                $t->string('name')->nullable();
                $t->softDeletes();
                $t->timestamps();
            });
        }
        Schema::create('type_chambre', function (Blueprint $t) {
            $t->string('id_type_chambre')->primary();
            $t->string('name');
            $t->text('description')->nullable();
            $t->double('montant')->default(0);
            $t->integer('superficie')->default(0);
            $t->text('equipement')->nullable();
            $t->timestamps();
        });
        Schema::create('chambre', function (Blueprint $t) {
            $t->string('id_chambre')->primary();
            foreach (['numero_chambre', 'code_chambre', 'type_chambre_id', 'batiment_id', 'cite_id', 'campus_id', 'cote_orientation', 'niveau_confort', 'superficie', 'equipements'] as $colonne) {
                $t->string($colonne)->nullable();
            }
            $t->integer('capacite_max')->default(1);
            $t->integer('prix')->default(0);
            $t->boolean('pmr')->default(false);
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('etudiants', function (Blueprint $t) {
            $t->string('id_etudiant')->primary();
            $t->string('name')->nullable();
            $t->string('faculte')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('assignation_chambres', function (Blueprint $t) {
            $t->string('id_assignation')->primary();
            foreach (['etudiant_id', 'chambre_id', 'campagne_id', 'statut'] as $colonne) {
                $t->string($colonne)->nullable();
            }
            $t->timestamp('date_assignation')->nullable();
            $t->timestamp('date_fin')->nullable();
            $t->timestamps();
        });
        (require database_path('migrations/2026_09_22_000001_create_lits.php'))->up();
        (require database_path('migrations/2026_09_28_000002_create_equipement_affectations.php'))->up();
        Schema::create('ordres_travail', function (Blueprint $t) {
            $t->string('id_ordres_travail')->primary();
            foreach (['numero_ot', 'etudiant_id', 'cite_id', 'batiment_id', 'chambre_id', 'type_intervention', 'titre', 'description', 'categorie', 'priorite', 'statut', 'assigne_a', 'periode_disponibilite', 'solution_apportee'] as $colonne) {
                $t->string($colonne)->nullable();
            }
            foreach (['date_disponibilite', 'date_debut_intervention', 'date_fin_intervention', 'date_resolution'] as $colonne) {
                $t->timestamp($colonne)->nullable();
            }
            $t->integer('taux_satisfaction')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('maintenanciers', function (Blueprint $t) {
            $t->string('id_maintenancier')->primary();
            $t->string('name')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });

        DB::table('cite')->insert(['id_cite' => 'cite', 'name' => 'Cité Bondoukou']);
        DB::table('batiment')->insert(['id_batiment' => 'building', 'name' => 'Bâtiment A']);
        DB::table('type_chambre')->insert(['id_type_chambre' => 'double', 'name' => 'Chambre double', 'montant' => 15000, 'superficie' => 14]);
        DB::table('chambre')->insert(['id_chambre' => 'room', 'numero_chambre' => 'A12', 'type_chambre_id' => 'double',
            'batiment_id' => 'building', 'cite_id' => 'cite', 'capacite_max' => 2]);
        DB::table('etudiants')->insert([['id_etudiant' => 'student', 'name' => 'Awa Koné'], ['id_etudiant' => 'mate', 'name' => 'Fatou Traoré']]);
        DB::table('equipement')->insert([['equipement_id' => 'fan', 'name' => 'ventilateur'], ['equipement_id' => 'chair', 'name' => 'chaise']]);
    }

    private function assigner(string $etudiant, array $donnees = []): void
    {
        DB::table('assignation_chambres')->insert(array_merge([
            'id_assignation' => 'stay-'.$etudiant, 'etudiant_id' => $etudiant, 'chambre_id' => 'room', 'campagne_id' => 'campaign',
            'statut' => 'confirmee', 'date_assignation' => now()->subMonth(),
        ], $donnees));
    }

    private function affecter(string $equipement, string $etat, array $donnees = []): void
    {
        DB::table('equipement_affectations')->insert(array_merge([
            'id' => (string) \Illuminate\Support\Str::uuid(), 'equipement_id' => $equipement, 'cite_id' => 'cite', 'batiment_id' => 'building',
            'chambre_id' => 'room', 'quantite' => 1, 'etat' => $etat,
        ], $donnees));
    }

    public function test_props_show_room_equipment_roommates_and_reports(): void
    {
        $this->assigner('student');
        $this->assigner('mate');
        $this->affecter('fan', 'defectueux');
        $this->affecter('chair', 'bon', ['returned_at' => now()]);
        DB::table('ordres_travail')->insert(['id_ordres_travail' => 'ot', 'numero_ot' => 'OT2026-0001', 'etudiant_id' => 'student',
            'titre' => 'Ventilateur en panne', 'categorie' => 'climatisation', 'statut' => 'en_cours', 'created_at' => now()]);

        $props = app(EtudiantChambre::class)->props(Etudiant::findOrFail('student'));
        $chambre = $props['chambre'];

        $this->assertSame('A12', $chambre['numero']);
        $this->assertSame('Chambre double', $chambre['type']);
        $this->assertSame('Bâtiment A', $chambre['batiment']);
        $this->assertSame('14 m²', $chambre['superficie']);
        $this->assertStringContainsString('15 000', $chambre['loyer']);
        $this->assertSame(['occupants' => 2, 'capacite' => 2], $chambre['occupation']);
        $this->assertFalse($chambre['aVenir']);
        // Le matériel réintégré au stock n'apparaît plus.
        $this->assertSame([['id' => 'fan', 'nom' => 'Ventilateur', 'quantite' => 1, 'etat' => 'Défectueux', 'ton' => 'danger']], $chambre['equipements']);
        $this->assertSame('Fatou Traoré', $chambre['colocataires'][0]['nom']);
        $this->assertSame('FT', $chambre['colocataires'][0]['initiales']);
        $this->assertSame(['En cours', 'info'], [$chambre['signalements'][0]['statut'], $chambre['signalements'][0]['ton']]);
        $this->assertContains(['code' => 'climatisation', 'libelle' => 'Climatisation'], $props['categories']);
        $this->assertSame(route('etudiants.store_ot'), $props['urls']['signalement']);
    }

    public function test_upcoming_room_is_shown_and_cancelled_one_is_not(): void
    {
        $this->assigner('student', ['date_assignation' => now()->addWeek()]);
        $this->assertTrue(app(EtudiantChambre::class)->props(Etudiant::findOrFail('student'))['chambre']['aVenir']);

        DB::table('assignation_chambres')->update(['statut' => 'annulee']);
        $this->assertNull(app(EtudiantChambre::class)->props(Etudiant::findOrFail('student'))['chambre']);
    }

    public function test_report_keeps_only_student_fields_and_locates_the_room(): void
    {
        $this->assigner('student');
        $this->affecter('fan', 'bon');
        $recu = null;
        $this->mock(OrdreTravailServiceInterface::class)->shouldReceive('create')->once()
            ->andReturnUsing(function (array $data) use (&$recu) {
                $recu = $data;

                return (new OrdreTravail)->forceFill(['numero_ot' => 'OT2026-0002']);
            });
        $this->actingAs(Etudiant::findOrFail('student'), 'etudiant');

        $this->from(route('etudiants.dashboard', ['section' => 'chambre']))
            ->post(route('etudiants.store_ot'), [
                'titre' => 'Ventilateur défectueux', 'description' => 'Il ne démarre plus depuis hier.', 'categorie' => 'climatisation',
                'priorite' => 'elevee', 'equipement_id' => 'fan', 'statut' => 'termine', 'assigne_a' => 'tech', 'cout_reel' => 1,
            ])
            ->assertRedirect(route('etudiants.dashboard', ['section' => 'chambre']))
            ->assertSessionHas('success');

        $this->assertSame('en_attente', $recu['statut']);
        $this->assertSame('corrective', $recu['type_intervention']);
        $this->assertSame(['room', 'building', 'cite', 'fan'], [$recu['chambre_id'], $recu['batiment_id'], $recu['cite_id'], $recu['equipement_id']]);
        $this->assertArrayNotHasKey('assigne_a', $recu);
        $this->assertArrayNotHasKey('cout_reel', $recu);
    }

    public function test_report_rejects_equipment_outside_the_room(): void
    {
        $this->assigner('student');
        $this->mock(OrdreTravailServiceInterface::class)->shouldNotReceive('create');
        $this->actingAs(Etudiant::findOrFail('student'), 'etudiant');

        $this->from(route('etudiants.dashboard', ['section' => 'chambre']))
            ->post(route('etudiants.store_ot'), ['description' => 'Cassé', 'categorie' => 'mobilier', 'priorite' => 'faible', 'equipement_id' => 'chair'])
            ->assertSessionHasErrors(['equipement_id' => 'Cet équipement n’est pas affecté à votre chambre.']);

        $this->post(route('etudiants.store_ot'), ['categorie' => 'inconnue', 'priorite' => 'faible'])
            ->assertSessionHasErrors(['description', 'categorie']);
    }

    private function ordre(string $id, array $donnees = []): void
    {
        DB::table('ordres_travail')->insert(array_merge([
            'id_ordres_travail' => $id, 'numero_ot' => 'OT-'.$id, 'etudiant_id' => 'student', 'categorie' => 'electricite',
            'priorite' => 'moyenne', 'statut' => 'en_attente', 'created_at' => now(),
        ], $donnees));
    }

    private function maintenance(array $query = []): array
    {
        return app(EtudiantMaintenance::class)->props(Etudiant::findOrFail('student'), Request::create('/etudiant/dashboard', 'GET', ['section' => 'maintenance'] + $query));
    }

    public function test_maintenance_page_lists_only_own_requests_with_real_counters(): void
    {
        $this->assigner('student');
        $this->affecter('fan', 'moyen');
        DB::table('maintenanciers')->insert(['id_maintenancier' => 'tech', 'name' => 'Koffi Yao']);
        $this->ordre('a', ['titre' => 'Prise HS', 'priorite' => 'critique', 'statut' => 'en_cours', 'assigne_a' => 'tech', 'created_at' => now()->subDay()]);
        $this->ordre('b', ['statut' => 'termine', 'solution_apportee' => 'Ampoule changée', 'date_resolution' => now(), 'taux_satisfaction' => 4, 'created_at' => now()->subDays(3)]);
        $this->ordre('c', ['priorite' => 'elevee', 'statut' => 'annule', 'created_at' => now()->subDays(5)]);
        $this->ordre('autre', ['etudiant_id' => 'mate', 'priorite' => 'critique']);

        $props = $this->maintenance();

        $this->assertSame(['toutes' => 3, 'en_cours' => 1, 'resolues' => 1, 'urgentes' => 1, 'tauxResolution' => 33], $props['compteurs']);
        $this->assertSame(['a', 'b', 'c'], array_column($props['demandes'], 'id'));
        [$enCours, $terminee, $annulee] = $props['demandes'];
        $this->assertSame(['Prise HS', 'Koffi Yao', 1, ['libelle' => 'Critique', 'ton' => 'danger']], [$enCours['titre'], $enCours['technicien'], $enCours['etape'], $enCours['priorite']]);
        $this->assertNull($enCours['solution']);
        // Sans titre, la catégorie sert de titre.
        $this->assertSame(['Électricité', 2, 'Ampoule changée', 4], [$terminee['titre'], $terminee['etape'], $terminee['solution'], $terminee['satisfaction']]);
        $this->assertSame(['Annulé', null], [$annulee['statut']['libelle'], $annulee['etape']]);
        $this->assertSame('Chambre A12 · Bâtiment A · Cité Bondoukou', $props['chambre']);
        $this->assertSame('fan', $props['equipements'][0]['id']);
        $this->assertSame(route('etudiants.store_ot'), $props['urls']['signalement']);
    }

    public function test_maintenance_filters_and_paginates(): void
    {
        foreach (range(1, 12) as $n) {
            $this->ordre("o$n", ['created_at' => now()->subMinutes($n), 'priorite' => $n === 1 ? 'elevee' : 'faible']);
        }
        $this->ordre('done', ['statut' => 'termine', 'created_at' => now()->subDay()]);

        $this->assertSame(['o1'], array_column($this->maintenance(['filtre' => 'urgentes'])['demandes'], 'id'));
        $this->assertSame(['done'], array_column($this->maintenance(['filtre' => 'resolues'])['demandes'], 'id'));
        $this->assertSame('toutes', $this->maintenance(['filtre' => 'inconnu'])['filtre']);

        $page = $this->maintenance();
        $this->assertCount(10, $page['demandes']);
        $this->assertSame(['page' => 1, 'pages' => 2, 'total' => 13], array_intersect_key($page['pagination'], array_flip(['page', 'pages', 'total'])));
        $this->assertStringContainsString('section=maintenance', $page['pagination']['suivant']);
        // Sans chambre confirmée : pas d'équipement proposé, le formulaire reste disponible.
        $this->assertNull($page['chambre']);
        $this->assertSame([], $page['equipements']);
    }

    public function test_maintenance_lists_admin_work_orders_targeting_room_building_or_cite(): void
    {
        $this->assigner('student');
        DB::table('chambre')->insert(['id_chambre' => 'other-room', 'numero_chambre' => 'A13', 'batiment_id' => 'building', 'cite_id' => 'cite']);
        $admin = ['etudiant_id' => null, 'cite_id' => 'cite'];
        $this->ordre('chambre', $admin + ['batiment_id' => 'building', 'chambre_id' => 'room', 'titre' => 'Peinture', 'created_at' => now()->subHour()]);
        $this->ordre('batiment', $admin + ['batiment_id' => 'building', 'type_intervention' => 'preventive', 'created_at' => now()->subHours(2),
            'date_debut_intervention' => now()->addDay(), 'date_fin_intervention' => now()->addDays(3)]);
        $this->ordre('cite', $admin + ['created_at' => now()->subHours(3)]);
        // Hors cible : autre chambre, autre bâtiment, autre cité, demande d'un étudiant, ordre clôturé avant l'arrivée.
        $this->ordre('autre-chambre', $admin + ['batiment_id' => 'building', 'chambre_id' => 'other-room']);
        $this->ordre('autre-batiment', $admin + ['batiment_id' => 'building-b']);
        $this->ordre('autre-cite', ['etudiant_id' => null, 'cite_id' => 'cite-b']);
        $this->ordre('etudiant', ['cite_id' => 'cite', 'batiment_id' => 'building', 'chambre_id' => 'room']);
        $this->ordre('ancien', $admin + ['statut' => 'termine', 'created_at' => now()->subYear()]);

        $props = $this->maintenance(['onglet' => 'administration']);

        $this->assertSame('administration', $props['onglet']);
        $this->assertSame(['chambre', 'batiment', 'cite'], array_column($props['ordresAdministration'], 'id'));
        $this->assertSame(['Ma chambre', 'Tout le bâtiment (Bâtiment A)', 'Toute la cité (Cité Bondoukou)'], array_column($props['ordresAdministration'], 'portee'));
        $this->assertSame('Préventive', $props['ordresAdministration'][1]['nature']);
        $this->assertStringStartsWith('Du ', $props['ordresAdministration'][1]['intervention']);
        $this->assertSame(3, $props['administrationEnCours']);
        // Les demandes de l'étudiant ne reprennent pas les ordres de l'administration.
        $this->assertSame(['etudiant'], array_column($props['demandes'], 'id'));
        // Un colocataire de la chambre reçoit les mêmes ordres ; sans chambre, aucun.
        $this->assigner('mate');
        $colocataire = app(EtudiantMaintenance::class)->props(Etudiant::findOrFail('mate'), Request::create('/etudiant/dashboard', 'GET'));
        $this->assertSame(['chambre', 'batiment', 'cite'], array_column($colocataire['ordresAdministration'], 'id'));
        DB::table('assignation_chambres')->delete();
        $this->assertSame([], $this->maintenance()['ordresAdministration']);
        $this->assertSame('demandes', $this->maintenance()['onglet']);
    }
}
