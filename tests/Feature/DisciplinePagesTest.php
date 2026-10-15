<?php

namespace Tests\Feature;

use App\Http\Controllers\admin\immobilier\vie_residentielle\DisciplinaryReportController;
use App\Http\Controllers\admin\immobilier\vie_residentielle\SanctionController;
use App\Models\DisciplinaryReport;
use App\Models\Sanction;
use App\Models\SanctionHistory;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Pages Inertia de la discipline : registre des sanctions et file des rapports disciplinaires. */
class DisciplinePagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        (require database_path('migrations/2026_08_10_000003_create_disciplinary_reports_tables.php'))->up();
        Schema::create('etudiants', function (Blueprint $t) {
            $t->string('id_etudiant')->primary();
            $t->string('name')->nullable();
            $t->string('matricule')->nullable();
            $t->softDeletes();
        });
        Schema::create('cite', function (Blueprint $t) {
            $t->string('id_cite')->primary();
            $t->string('name');
            $t->softDeletes();
        });
        Schema::create('batiment', function (Blueprint $t) {
            $t->string('id_batiment')->primary();
            $t->string('cite_id');
            $t->string('name');
            $t->softDeletes();
        });
        Schema::create('chambre', function (Blueprint $t) {
            $t->string('id_chambre')->primary();
            $t->string('cite_id');
            $t->string('batiment_id');
            $t->string('numero_chambre');
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
            '2026_08_10_000001_create_sanctions_tables.php',
            '2026_08_10_000002_add_scope_to_sanctions_table.php',
            '2026_08_17_000001_add_student_declaration_fields_to_disciplinary_reports.php',
            '2026_08_10_000005_create_departure_requests_and_settlements.php',
            '2026_08_31_000010_complete_departure_workflow.php',
            '2026_09_23_000004_link_disciplinary_decisions_to_sanctions.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }

        DB::table('cite')->insert(['id_cite' => 'c1', 'name' => 'Cité Bondoukou']);
        DB::table('batiment')->insert(['id_batiment' => 'b1', 'cite_id' => 'c1', 'name' => 'Bâtiment A']);
        DB::table('chambre')->insert([
            ['id_chambre' => 'ch1', 'cite_id' => 'c1', 'batiment_id' => 'b1', 'numero_chambre' => 'A-12'],
            ['id_chambre' => 'ch2', 'cite_id' => 'c1', 'batiment_id' => 'b1', 'numero_chambre' => 'A-13'],
        ]);
        DB::table('etudiants')->insert([['id_etudiant' => 'e1', 'name' => 'Awa Koné', 'matricule' => 'M001'], ['id_etudiant' => 'e2', 'name' => 'Ancien résident', 'matricule' => 'M002']]);
        DB::table('assignation_chambres')->insert([
            ['id_assignation' => 'a1', 'etudiant_id' => 'e1', 'chambre_id' => 'ch1', 'statut' => 'confirmee', 'date_assignation' => now()->subMonth(), 'date_fin' => null],
            ['id_assignation' => 'a2', 'etudiant_id' => 'e2', 'chambre_id' => 'ch2', 'statut' => 'confirmee', 'date_assignation' => now()->subYear(), 'date_fin' => now()->subMonth()],
        ]);
    }

    /** Rôle 1 : tous les droits ; sinon agent limité à la consultation de la discipline. */
    private function connecter(int $role): void
    {
        $agent = $role === 1
            ? new User(['id_user' => 'agent', 'name' => 'Agent', 'role_id' => 1])
            : new class extends User
            {
                public function __construct()
                {
                    parent::__construct(['id_user' => 'agent', 'name' => 'Agent', 'role_id' => 2]);
                }

                public function hasPermission(string $permission): bool
                {
                    return $permission === 'hebergement.discipline.view';
                }
            };
        $this->actingAs($agent, 'admin');
    }

    /** Props d'une page Inertia rendue par une action de contrôleur. */
    private function page(object $controleur, string $composant, array $query = []): array
    {
        $request = Request::create('/admin/gestion-hebergement/vie-residentielle', 'GET', $query);
        $request->headers->set('X-Inertia', 'true');
        $request->setUserResolver(fn () => auth('admin')->user());
        $this->app->instance('request', $request);

        $page = $controleur->index($request)->toResponse($request)->getData(true);
        $this->assertSame($composant, $page['component']);

        return $page['props'];
    }

    private function sanction(): Sanction
    {
        $sanction = Sanction::create([
            'scope_type' => 'etudiant', 'etudiant_id' => 'e1', 'cite_id' => 'c1', 'batiment_id' => 'b1', 'chambre_id' => 'ch1',
            'type' => 'suspension', 'statut' => 'active', 'motif' => 'Tapage nocturne répété', 'date_decision' => '2026-10-01',
            'date_debut' => '2026-10-02', 'date_fin' => '2026-10-20',
        ]);
        SanctionHistory::create(['sanction_id' => $sanction->id_sanction, 'action' => 'creation', 'nouveau_statut' => 'active', 'details' => 'Sanction suspension enregistrée.']);

        return $sanction;
    }

    public function test_registre_des_sanctions_avec_les_droits(): void
    {
        $this->connecter(1);
        $sanction = $this->sanction();

        $props = $this->page(app(SanctionController::class), 'Admin/Hebergement/Discipline/Sanctions', ['type' => 'inconnu', 'statut' => 'active']);

        // Valeur de filtre inconnue ignorée, filtre valide conservé.
        $this->assertSame(['type' => '', 'statut' => 'active', 'cite' => ''], $props['filters']);
        $ligne = $props['sanctions']['data'][0];
        $this->assertSame([$sanction->id_sanction, 'Awa Koné', 'Suspension', '2026-10-02', '2026-10-20'], [
            $ligne['id'], $ligne['cible']['titre'], $ligne['type_label'], $ligne['date_debut'], $ligne['date_fin'],
        ]);
        $this->assertStringContainsString('Chambre A-12', $ligne['cible']['detail']);
        $this->assertSame(['creation'], array_column($ligne['historique'], 'action'));
        $this->assertNotNull($ligne['url_statut']);
        $this->assertSame(['total' => 1, 'active' => 1, 'avertissements' => 0, 'suspensions' => 1], $props['stats']);
        // Seuls les résidents dont l'affectation est en cours sont proposés.
        $this->assertSame(['e1'], array_column($props['structure']['residents'], 'id'));
        $this->assertSame(['ch1'], array_column($props['structure']['chambres'], 'id'));
        $this->assertNotNull($props['urls']['store']);
    }

    public function test_sans_droit_de_modification_aucune_action_n_est_proposee(): void
    {
        $this->connecter(2);
        $this->sanction();

        $props = $this->page(app(SanctionController::class), 'Admin/Hebergement/Discipline/Sanctions');

        $this->assertNull($props['sanctions']['data'][0]['url_statut']);
        $this->assertNull($props['urls']['store']);
        // La consultation donne accès à la file des rapports, pas à leur traitement.
        $this->assertNotNull($props['urls']['incidents']);
    }

    public function test_file_des_rapports_et_actions_du_workflow(): void
    {
        $this->connecter(1);
        $transmis = DisciplinaryReport::create([
            'etudiant_id' => 'e1', 'cite_id' => 'c1', 'batiment_id' => 'b1', 'chambre_id' => 'ch1', 'date_incident' => now()->subDays(3),
            'nature' => 'Tapage', 'description' => 'Musique forte après minuit.', 'statut' => 'transmis', 'gravite' => 'moyenne', 'transmis_at' => now()->subDays(2),
        ]);
        DisciplinaryReport::create([
            'etudiant_id' => 'e1', 'cite_id' => 'c1', 'batiment_id' => 'b1', 'chambre_id' => 'ch1', 'date_incident' => now()->subDays(10),
            'nature' => 'Dégradation', 'description' => 'Porte abîmée.', 'statut' => 'cloture', 'gravite' => 'grave',
        ]);

        $props = $this->page(app(DisciplinaryReportController::class), 'Admin/Hebergement/Discipline/Incidents', ['gravite' => 'moyenne']);

        $this->assertCount(1, $props['rapports']['data']);
        $ligne = $props['rapports']['data'][0];
        $this->assertSame([$transmis->id_rapport, 'Transmis', 'Awa Koné', 'Cité Bondoukou · Bâtiment A · Ch. A-12'], [$ligne['id'], $ligne['statut_label'], $ligne['resident'], $ligne['localisation']]);
        $this->assertSame(['prendre_en_charge', 'retourner'], $ligne['actions']);
        $this->assertNotNull($ligne['attente']);
        $this->assertNotNull($ligne['url_transition']);
        $this->assertSame(['brouillons' => 0, 'transmis' => 1, 'en_cours' => 0, 'decides' => 1], $props['stats']);
        $this->assertContains('expulsion', array_column($props['sanctions'], 'value'));
        $this->assertSame(['c1'], array_column($props['structure']['cites'], 'id'));
    }

    public function test_sans_droit_de_traitement_les_actions_du_workflow_sont_masquees(): void
    {
        $this->connecter(2);
        DisciplinaryReport::create([
            'etudiant_id' => 'e1', 'cite_id' => 'c1', 'batiment_id' => 'b1', 'chambre_id' => 'ch1', 'date_incident' => now(),
            'nature' => 'Tapage', 'description' => 'Bruit.', 'statut' => 'brouillon', 'gravite' => 'faible',
        ]);

        $ligne = $this->page(app(DisciplinaryReportController::class), 'Admin/Hebergement/Discipline/Incidents')['rapports']['data'][0];

        $this->assertSame([], $ligne['actions']);
        $this->assertNull($ligne['url_transition']);
    }
}
