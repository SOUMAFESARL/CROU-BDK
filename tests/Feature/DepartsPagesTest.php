<?php

namespace Tests\Feature;

use App\Http\Controllers\admin\immobilier\depart_etat_des_lieux\DepartEtatDesLieuxController;
use App\Http\Controllers\admin\immobilier\depart_etat_des_lieux\DepartureSettingController;
use App\Models\DepartureRequest;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** Pages Inertia des départs : demandes de sortie, états des lieux, paramètres de clôture et actions associées. */
class DepartsPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('cite', function (Blueprint $t) {
            $t->string('id_cite')->primary();
            $t->string('name');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('batiment', function (Blueprint $t) {
            $t->string('id_batiment')->primary();
            $t->string('cite_id');
            $t->string('name');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('chambre', function (Blueprint $t) {
            $t->string('id_chambre')->primary();
            $t->string('cite_id');
            $t->string('batiment_id');
            $t->string('numero_chambre');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('etudiants', function (Blueprint $t) {
            $t->string('id_etudiant')->primary();
            $t->string('name');
            $t->string('matricule')->nullable();
            $t->string('email1')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('assignation_chambres', function (Blueprint $t) {
            $t->string('id_assignation')->primary();
            $t->string('etudiant_id');
            $t->string('chambre_id');
            $t->string('statut');
            $t->timestamp('date_assignation')->nullable();
            $t->timestamp('date_fin')->nullable();
            $t->timestamps();
        });
        Schema::create('etat_des_lieux', function (Blueprint $t) {
            $t->string('etat_des_lieux_id')->primary();
            foreach (['id_cite', 'id_batiment', 'id_chambre', 'id_etudiant', 'type_etat', 'observation_generale', 'signature_crou', 'signature_etudiant', 'created_by', 'updated_by'] as $colonne) {
                $t->string($colonne)->nullable();
            }
            $t->dateTime('date_etat')->nullable();
            $t->integer('nbre_cle_recus')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('edl_piece', function (Blueprint $t) {
            $t->string('edl_piece_id')->primary();
            $t->string('id_etat_des_lieux');
            $t->string('name_piece');
            $t->integer('ordre')->default(1);
            $t->string('created_by')->nullable();
            $t->string('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('edl_element', function (Blueprint $t) {
            $t->string('edl_element_id')->primary();
            $t->string('id_edl_piece');
            $t->string('name_element');
            $t->string('etat')->nullable();
            $t->text('observation')->nullable();
            $t->string('photo')->nullable();
            $t->integer('ordre')->default(1);
            $t->string('created_by')->nullable();
            $t->string('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('edl_image', function (Blueprint $t) {
            $t->string('edl_image_id')->primary();
            $t->string('id_edl_piece');
            $t->string('chemin_fichier');
            $t->string('created_by')->nullable();
            $t->string('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        (require database_path('migrations/2026_08_10_000001_create_sanctions_tables.php'))->up();
        (require database_path('migrations/2026_08_10_000004_create_departure_settings_table.php'))->up();
        (require database_path('migrations/2026_08_10_000005_create_departure_requests_and_settlements.php'))->up();
        (require database_path('migrations/2026_08_31_000010_complete_departure_workflow.php'))->up();
        Schema::table('departure_requests', fn (Blueprint $t) => $t->string('sanction_id', 150)->nullable());

        DB::table('cite')->insert(['id_cite' => 'c1', 'name' => 'Cité Bondoukou']);
        DB::table('batiment')->insert(['id_batiment' => 'b1', 'cite_id' => 'c1', 'name' => 'Bâtiment A']);
        DB::table('chambre')->insert([
            ['id_chambre' => 'ch1', 'cite_id' => 'c1', 'batiment_id' => 'b1', 'numero_chambre' => 'A-12'],
            ['id_chambre' => 'ch2', 'cite_id' => 'c1', 'batiment_id' => 'b1', 'numero_chambre' => 'A-14'],
        ]);
        DB::table('etudiants')->insert([
            ['id_etudiant' => 'e1', 'name' => 'Awa Koné', 'matricule' => 'M001', 'email1' => 'awa@example.ci'],
            ['id_etudiant' => 'e2', 'name' => 'Yao Kouassi', 'matricule' => 'M002', 'email1' => null],
            ['id_etudiant' => 'e3', 'name' => 'Ama Brou', 'matricule' => 'M003', 'email1' => null],
        ]);
        DB::table('assignation_chambres')->insert([
            ['id_assignation' => 'a1', 'etudiant_id' => 'e1', 'chambre_id' => 'ch1', 'statut' => 'confirmee', 'date_assignation' => now()->subMonth()],
            ['id_assignation' => 'a2', 'etudiant_id' => 'e2', 'chambre_id' => 'ch2', 'statut' => 'confirmee', 'date_assignation' => now()->subMonth()],
        ]);
        DB::table('departure_settings')->insert(['notice_days' => 30, 'late_notice_penalty' => 5000, 'unpaid_blocking_threshold' => 0, 'damage_retention_grid' => json_encode([['label' => 'Serrure', 'amount' => 7500]])]);

        $demande = fn (string $id, string $etudiant, string $chambre, string $statut, string $decision) => [
            'id_demande_sortie' => $id, 'etudiant_id' => $etudiant, 'assignation_id' => 'a-'.$etudiant, 'chambre_id' => $chambre,
            'date_demande' => '2026-10-01', 'date_sortie_prevue' => '2026-11-15', 'motif' => 'fin_etudes', 'statut' => $statut, 'decision' => $decision,
        ];
        DB::table('departure_requests')->insert([
            $demande('r1', 'e1', 'ch1', 'en_attente', 'en_attente'),
            $demande('r2', 'e2', 'ch2', 'en_attente', 'approuvee'),
            $demande('r3', 'e3', 'ch1', 'cloturee', 'approuvee'),
            $demande('r4', 'e3', 'ch1', 'annulee', 'refusee'),
        ]);
        DB::table('departure_settlements')->insert([
            'id_decompte' => 's3', 'demande_sortie_id' => 'r3', 'etat_des_lieux_id' => 'edl-s', 'etudiant_id' => 'e3', 'chambre_id' => 'ch1',
            'preavis_requis_jours' => 30, 'preavis_effectif_jours' => 10, 'penalite_preavis' => 5000, 'total_retenues' => 5000,
            'impute_caution' => 5000, 'caution_disponible' => 20000, 'caution_a_restituer' => 15000,
        ]);
        DB::table('etat_des_lieux')->insert([
            'etat_des_lieux_id' => 'edl-e', 'id_cite' => 'c1', 'id_batiment' => 'b1', 'id_chambre' => 'ch1', 'id_etudiant' => 'e1',
            'type_etat' => 'entree', 'date_etat' => '2026-09-02 00:00:00', 'nbre_cle_recus' => 2, 'signature_etudiant' => 'signatures/e1.png',
        ]);
        DB::table('edl_piece')->insert(['edl_piece_id' => 'p1', 'id_etat_des_lieux' => 'edl-e', 'name_piece' => 'Chambre']);
        DB::table('edl_element')->insert(['edl_element_id' => 'el1', 'id_edl_piece' => 'p1', 'name_element' => 'Serrure', 'etat' => 'bon']);

        $this->actingAs(new User(['id_user' => 'admin', 'name' => 'Admin', 'role_id' => 1]), 'admin');
    }

    private function page(string $controleur, string $action, string $composant, array $query = []): array
    {
        $request = Request::create('/admin/gestion-hebergement/depart-etat-des-lieux', 'GET', $query);
        $request->headers->set('X-Inertia', 'true');
        $request->setUserResolver(fn () => auth('admin')->user());
        $this->app->instance('request', $request);

        $page = app()->call([app($controleur), $action], ['request' => $request])->toResponse($request)->getData(true);
        $this->assertSame($composant, $page['component']);

        return $page['props'];
    }

    private function action(string $methode, array $donnees, array $parametres = []): mixed
    {
        $request = Request::create('/admin/gestion-hebergement/depart-etat-des-lieux', 'POST', $donnees);
        $request->setUserResolver(fn () => auth('admin')->user());
        $this->app->instance('request', $request);

        return app()->call([app(DepartEtatDesLieuxController::class), $methode], ['request' => $request, ...$parametres]);
    }

    public function test_page_des_departs_avec_statuts_actions_et_decompte(): void
    {
        $props = $this->page(DepartEtatDesLieuxController::class, 'depart', 'Admin/Hebergement/Departs/Index');
        $demandes = collect($props['demandes'])->keyBy('id');

        $this->assertSame(['en_attente', 'approuvee', 'cloturee', 'refusee'], [$demandes['r1']['statut'], $demandes['r2']['statut'], $demandes['r3']['statut'], $demandes['r4']['statut']]);
        $this->assertSame('Fin d’études', $demandes['r1']['motif']);
        $this->assertSame('Cité Bondoukou', $demandes['r1']['cite']);
        // Décision seulement pour une demande à traiter ; état des lieux seulement pour une demande approuvée.
        $this->assertNotNull($demandes['r1']['urls']['decision']);
        $this->assertNull($demandes['r2']['urls']['decision']);
        $this->assertStringContainsString('etudiant=e2', $demandes['r2']['urls']['etat_des_lieux']);
        $this->assertNull($demandes['r1']['urls']['etat_des_lieux']);
        // Décompte et restitution de la caution d'un séjour clôturé.
        $this->assertSame(15000.0, (float) $demandes['r3']['decompte']['caution_a_restituer']);
        $this->assertStringEndsWith('/decompte/s3/restitution-caution', $demandes['r3']['urls']['restitution']);
        $this->assertNotNull($demandes['r3']['urls']['pdf']);
        // Résidents en séjour pour une nouvelle demande, préavis déjà déposé signalé.
        $residents = collect($props['residents'])->keyBy('id');
        $this->assertTrue($residents['e1']['preavis']);
        $this->assertSame('A-14', $residents['e2']['chambre']);
        $this->assertSame('entree', $props['inspections'][0]['type']);
        $this->assertStringEndsWith('/etat-des-lieux/edl-e/pdf', $props['inspections'][0]['pdf']);
        $this->assertNotNull($props['urls']['store']);
        $this->assertCount(7, $props['modesRestitution']);
        // Liste des états des lieux affichée en premier onglet de la page.
        $edl = collect($props['etatsDesLieux']['residents'])->keyBy('id');
        $this->assertSame('r2', $edl['e2']['demande']['id']);
        $this->assertNotNull($edl['e1']['entree']);
        $this->assertSame(30, $props['etatsDesLieux']['preavisJours']);
        $this->assertNotNull($props['etatsDesLieux']['urls']['store']);
        $this->assertSame('e2', $demandes['r2']['etudiant_id']);
    }

    public function test_page_des_etats_des_lieux_signature_et_sortie_a_realiser(): void
    {
        $props = $this->page(DepartEtatDesLieuxController::class, 'etatDesLieux', 'Admin/Hebergement/Departs/EtatsDesLieux');
        $residents = collect($props['residents'])->keyBy('id');

        $entree = $residents['e1']['entree'];
        $this->assertSame('2026-09-02', $entree['date']);
        $this->assertSame(['nom' => 'Serrure', 'etat' => 'bon', 'observation' => null, 'photo' => null], $entree['pieces'][0]['elements'][0]);
        $this->assertStringEndsWith('/etat-des-lieux/entree/edl-e', $entree['urls']['signer']);
        $this->assertNull($residents['e1']['demande']);
        // Demande approuvée sans état des lieux : sortie à réaliser.
        $this->assertSame('r2', $residents['e2']['demande']['id']);
        $this->assertNull($residents['e2']['entree']);
        $this->assertSame('A-14', $residents['e2']['chambre']);
        $this->assertEquals([['label' => 'Serrure', 'amount' => 7500]], $props['grille']);
        $this->assertSame(30, $props['preavisJours']);
    }

    public function test_entree_deja_signee_par_le_crou_n_est_plus_a_signer(): void
    {
        DB::table('etat_des_lieux')->update(['signature_crou' => 'signatures/crou.png']);

        $props = $this->page(DepartEtatDesLieuxController::class, 'etatDesLieux', 'Admin/Hebergement/Departs/EtatsDesLieux');

        $this->assertNull($props['residents'][0]['entree']['urls']['signer']);
        $this->assertStringEndsWith('/signatures/crou.png', $props['residents'][0]['entree']['signature_crou']);
    }

    public function test_page_des_parametres_de_cloture(): void
    {
        $props = $this->page(DepartureSettingController::class, 'edit', 'Admin/Hebergement/Departs/Parametres');

        $this->assertSame(30, $props['settings']['notice_days']);
        $this->assertSame(5000.0, (float) $props['settings']['late_notice_penalty']);
        $this->assertEquals([['label' => 'Serrure', 'amount' => 7500]], $props['settings']['damage_retention_grid']);
        $this->assertNotNull($props['urls']['update']);
    }

    public function test_nouvelle_demande_de_sortie_redirige_avec_message(): void
    {
        $reponse = $this->action('storeDepartureRequest', ['etudiant_id' => 'e2', 'date_sortie_prevue' => now()->addDays(40)->toDateString(), 'motif' => 'stage']);

        $this->assertInstanceOf(RedirectResponse::class, $reponse);
        $this->assertSame('Demande de sortie et préavis enregistrés.', session('success'));
        // La demande en attente de l'étudiant est remplacée (une seule demande ouverte).
        $this->assertSame(1, DepartureRequest::where('etudiant_id', 'e2')->where('statut', 'en_attente')->count());
        $this->assertSame('stage', DepartureRequest::where('etudiant_id', 'e2')->value('motif'));
    }

    public function test_demande_refusee_sans_sejour_en_cours(): void
    {
        try {
            $this->action('storeDepartureRequest', ['etudiant_id' => 'e3', 'date_sortie_prevue' => now()->addDays(5)->toDateString(), 'motif' => 'autre']);
            $this->fail('Une demande sans séjour en cours doit être refusée.');
        } catch (ValidationException $e) {
            $this->assertSame(["Ce résident n'a pas de séjour en cours."], $e->errors()['etudiant_id']);
        }
    }

    public function test_refus_exige_un_motif_et_approbation_enregistree(): void
    {
        try {
            $this->action('decide', ['decision' => 'refusee'], ['id' => 'r1']);
            $this->fail('Le motif du refus est obligatoire.');
        } catch (ValidationException $e) {
            $this->assertSame(['Indiquez le motif du refus.'], $e->errors()['motif_decision']);
        }

        $this->action('decide', ['decision' => 'approuvee'], ['id' => 'r1']);
        $this->assertSame('approuvee', DepartureRequest::find('r1')->decision);
    }

    public function test_sortie_sans_signature_du_crou_est_refusee(): void
    {
        try {
            $this->action('storeEdl', [
                'type_etat' => 'sortie', 'id_cite' => 'c1', 'id_batiment' => 'b1', 'id_chambre' => 'ch2', 'id_etudiant' => 'e2',
                'etat_chambre_sortie' => 'disponible', 'pieces_json' => '[]',
            ]);
            $this->fail('La signature du CROU est obligatoire.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('signature_crou', $e->errors());
        }
        $this->assertSame('en_attente', DepartureRequest::find('r2')->statut);
    }

    public function test_routes_du_module_enregistrees_dans_les_permissions(): void
    {
        $routes = config('admin_permissions.routes');
        $this->assertSame('hebergement.departs.view', $routes['admin.hebergement.depart_etat_des_lieux.edl.pdf']);
        $this->assertSame('hebergement.departs.close', $routes['admin.hebergement.depart_etat_des_lieux.departure_request.decision']);
        $this->assertSame('hebergement.parametrage.configure', $routes['admin.hebergement.depart_etat_des_lieux.settings.update']);
    }
}
