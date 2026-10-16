<?php

namespace Tests\Feature;

use App\Http\Controllers\admin\immobilier\vie_residentielle\PaiementController;
use App\Http\Services\CiteService;
use App\Http\Services\EtudiantService;
use App\Http\Services\LoyerService;
use App\Http\Services\PaiementService;
use App\Models\ConfigApp;
use App\Models\Paiement;
use App\Repositories\Interfaces\ConfigAppRepositoryInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

/** Pages Inertia des paiements : liste, détail et rapport financier (les services métier sont simulés). */
class PaiementsPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->mock(ConfigAppRepositoryInterface::class)->shouldReceive('getConfig')->andReturn(new ConfigApp);

        Schema::create('cite', function (Blueprint $t) {
            $t->string('id_cite')->primary();
            $t->string('name');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('chambre', function (Blueprint $t) {
            $t->string('id_chambre')->primary();
            $t->string('numero_chambre');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('etudiants', function (Blueprint $t) {
            $t->string('id_etudiant')->primary();
            $t->string('name');
            $t->string('matricule')->nullable();
            $t->string('telephone1')->nullable();
            $t->string('email1')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->string('id_user')->primary();
            $t->string('name');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('paiements', function (Blueprint $t) {
            $t->string('id_paiement')->primary();
            $t->string('numero_paiement')->nullable();
            $t->string('resident_id')->nullable();
            $t->string('chambre_id')->nullable();
            $t->string('cite_id')->nullable();
            $t->decimal('montant', 12, 2);
            $t->string('type_paiement')->nullable();
            $t->string('methode_paiement')->nullable();
            $t->string('statut_paiement');
            $t->text('mois')->nullable();
            $t->dateTime('date_paiement')->nullable();
            $t->date('date_echeance')->nullable();
            $t->string('reference_transaction')->nullable();
            $t->text('notes')->nullable();
            $t->string('created_bd')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('paiement_repartitions', function (Blueprint $t) {
            $t->string('id_repartition')->primary();
            $t->string('paiement_id');
            $t->string('type');
            $t->integer('mois')->nullable();
            $t->integer('annee')->nullable();
            $t->decimal('montant', 12, 2);
            $t->string('description')->nullable();
            $t->timestamps();
        });

        DB::table('cite')->insert(['id_cite' => 'c1', 'name' => 'Cité Bondoukou']);
        DB::table('chambre')->insert(['id_chambre' => 'ch1', 'numero_chambre' => 'A-12']);
        DB::table('etudiants')->insert(['id_etudiant' => 'e1', 'name' => 'Awa Koné', 'matricule' => 'M001', 'telephone1' => '0700000000']);
        DB::table('paiements')->insert([
            'id_paiement' => 'p1', 'numero_paiement' => 'PAY-001', 'resident_id' => 'e1', 'chambre_id' => 'ch1', 'cite_id' => 'c1', 'montant' => 25000,
            'type_paiement' => 'loyer', 'methode_paiement' => 'especes', 'statut_paiement' => 'en_attente', 'mois' => json_encode(['2026-10']),
            'created_at' => now(),
        ]);
        DB::table('paiement_repartitions')->insert([
            ['id_repartition' => 'r1', 'paiement_id' => 'p1', 'type' => 'arriere', 'mois' => 9, 'annee' => 2026, 'montant' => 15000, 'description' => null],
            ['id_repartition' => 'r2', 'paiement_id' => 'p1', 'type' => 'mois_cours', 'mois' => 10, 'annee' => 2026, 'montant' => 5000, 'description' => null],
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function controleur(): PaiementController
    {
        $paiements = Mockery::mock(PaiementService::class);
        $paiements->shouldReceive('getPaiementsPaginated')->andReturnUsing(fn () => new LengthAwarePaginator(
            Paiement::with(['resident', 'chambre', 'cite'])->get(), 1, 20, 1, ['path' => '/admin/gestion-hebergement/paiements']
        ));
        $paiements->shouldReceive('getKPIs')->andReturn([['label' => 'Nombre de paiements', 'value' => 7]]);
        $paiements->shouldReceive('getStatistiquesPaiements')->andReturn(['total_encaisse' => 40000, 'nombre_paiements' => 2]);
        $paiements->shouldReceive('getEvolutionPaiements')->andReturn(array_fill(0, 12, 0));
        $paiements->shouldReceive('getEvolutionAttendus')->andReturn(array_fill(0, 12, 1000));
        $paiements->shouldReceive('getDerniersPaiements')->andReturn([[
            'id' => 'p1', 'numero' => 'PAY-001', 'resident' => 'Awa Koné', 'montant' => 25000, 'montant_formate' => '25 000 FCFA',
            'methode' => 'Espèces', 'statut' => 'En attente', 'statut_badge' => 'warning', 'date' => '15/10/2026 10:00', 'residence' => 'Cité Bondoukou',
        ]]);
        $paiements->shouldReceive('getStatistiquesParModePaiement')->andReturn([[
            'methode' => 'especes', 'libelle' => 'Espèces', 'couleur' => '#1e7aa8', 'nombre' => 2, 'total' => 40000.0, 'total_formate' => '40 000 FCFA',
            'moyenne' => 20000.0, 'moyenne_formate' => '20 000 FCFA', 'pourcentage' => 100.0,
        ]]);

        $loyers = Mockery::mock(LoyerService::class);
        $loyers->shouldReceive('getStatistiquesParResidence')->andReturn(collect([[
            'residence' => 'Cité Bondoukou', 'total_attendu' => '100 000 FCFA', 'total_encaisse' => '40 000 FCFA', 'taux_recouvrement' => 40,
            'impayes' => '60 000 FCFA', 'nb_residents' => 4, 'loyer_moyen' => '25 000 FCFA', 'nb_en_retard' => 2, 'alerte' => true,
        ]]));
        $loyers->shouldReceive('getStatistiquesLoyers')->andReturn([
            'total_montant' => 100000.0, 'total_paye' => 40000.0, 'total_solde_restant' => 60000.0, 'taux_recouvrement' => 40.0,
            'nombre_en_retard' => 2, 'total_penalites' => 1500.0,
        ]);
        $loyers->shouldReceive('getStatistiquesParResidenceFiltre')->andReturn([]);

        $etudiants = Mockery::mock(EtudiantService::class);
        $etudiants->shouldReceive('getAll')->andReturn(collect());

        return new PaiementController($paiements, $etudiants, $loyers, Mockery::mock(CiteService::class));
    }

    /** Props d'une page Inertia rendue par une action du contrôleur. */
    private function page(string $action, string $composant, array $query = [], array $parametres = []): array
    {
        $request = Request::create('/admin/gestion-hebergement/paiements', 'GET', $query);
        $request->headers->set('X-Inertia', 'true');
        $this->app->instance('request', $request);

        $page = app()->call([$this->controleur(), $action], ['request' => $request, ...$parametres])->toResponse($request)->getData(true);
        $this->assertSame($composant, $page['component']);

        return $page['props'];
    }

    public function test_liste_des_paiements_indicateurs_et_filtres(): void
    {
        $props = $this->page('index', 'Admin/Hebergement/Paiements/Index', ['statut' => 'en_attente', 'mois' => '2026-10']);

        $ligne = $props['paiements']['data'][0];
        $this->assertEquals(['PAY-001', 'Awa Koné', 'Cité Bondoukou', 'A-12', 25000, 'Espèces', 'en_attente', 'En attente'], [
            $ligne['numero'], $ligne['resident'], $ligne['residence'], $ligne['chambre'], $ligne['montant'], $ligne['methode'], $ligne['statut'], $ligne['statut_label'],
        ]);
        $this->assertSame('octobre-2026', $ligne['mois']);
        $this->assertEquals(['total_encaisse' => 40000, 'taux_recouvrement' => 40, 'impayes' => 60000, 'penalites' => 1500, 'nombre_paiements' => 7, 'nombre_retard' => 2], $props['kpis']);
        $this->assertSame(['residence' => '', 'statut' => 'en_attente', 'mois' => '2026-10'], $props['filters']);
        $this->assertSame([['id' => 'c1', 'name' => 'Cité Bondoukou']], $props['residences']);
        $this->assertStringContainsString('statut=en_attente', $props['paiements']['first_page_url']);
        // Sans administrateur connecté, aucune action n'est proposée.
        $this->assertNull($props['urls']['store']);
        $this->assertNull($props['urls']['export']);
        $this->assertNull($ligne['urls']['valider']);
    }

    public function test_detail_d_un_paiement_avec_sa_repartition(): void
    {
        $props = $this->page('show', 'Admin/Hebergement/Paiements/Show', [], ['id' => 'p1']);

        $paiement = $props['paiement'];
        $this->assertSame(['PAY-001', 'en_attente', 'Awa Koné', 'M001', 'A-12'], [
            $paiement['numero'], $paiement['statut'], $paiement['resident']['name'], $paiement['resident']['matricule'], $paiement['resident']['chambre'],
        ]);
        $this->assertSame(['2026-10'], $paiement['mois']);
        $this->assertSame(['arriere', 'mois_cours'], array_column($paiement['repartitions'], 'type'));
        $this->assertSame('9/2026', $paiement['repartitions'][0]['periode']);
        $this->assertNotNull($paiement['ecart']);
        $this->assertNull($props['urls']['valider']);
    }

    public function test_rapport_financier(): void
    {
        $props = $this->page('rapportFinancier', 'Admin/Hebergement/Paiements/RapportFinancier', ['annee' => '2026']);

        // Les flottants entiers perdent leur « .0 » en JSON : comparaison par valeur.
        $this->assertEquals(100000, $props['stats']['total_attendu']);
        $this->assertEquals(40000, $props['stats']['total_encaisse']);
        $this->assertEquals(60000, $props['stats']['total_impayes']);
        $this->assertEquals(40, $props['stats']['taux_recouvrement']);
        $this->assertCount(12, $props['evolution']);
        $this->assertEquals(['label' => 'Jan', 'encaisse' => 0, 'attendu' => 1000], $props['evolution'][0]);
        $this->assertSame('Espèces', $props['statsParMode'][0]['libelle']);
        $this->assertSame('PAY-001', $props['derniersPaiements'][0]['numero']);
        $this->assertSame(['annee' => '2026', 'mois' => '', 'residence' => ''], $props['filters']);
    }
}
