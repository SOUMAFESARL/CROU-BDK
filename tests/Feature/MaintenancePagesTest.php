<?php

namespace Tests\Feature;

use App\Http\Controllers\admin\immobilier\vie_residentielle\MaintenanceController;
use App\Http\Services\CiteService;
use App\Http\Services\InterventionService;
use App\Models\Cite;
use App\Models\Maintenancier;
use App\Models\OrdreTravail;
use App\Models\User;
use App\Repositories\Interfaces\MaintenancierRepositoryInterface;
use App\Repositories\Interfaces\OrdreTravailServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

/** Pages Inertia de la maintenance : liste, fiche, modification et actions de statut (services simulés). */
class MaintenancePagesTest extends TestCase
{
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('batiment', function (Blueprint $t) {
            $t->string('id_batiment')->primary();
            $t->string('cite_id');
            $t->string('name');
            $t->softDeletes();
        });
        Schema::create('chambre', function (Blueprint $t) {
            $t->string('id_chambre')->primary();
            $t->string('batiment_id');
            $t->string('numero_chambre')->nullable();
            $t->softDeletes();
        });
        Schema::create('equipement', function (Blueprint $t) {
            $t->string('equipement_id')->primary();
            $t->string('name');
        });
        Schema::create('users', function (Blueprint $t) {
            $t->string('id_user')->primary();
            $t->string('name');
            $t->softDeletes();
        });
        DB::table('batiment')->insert(['id_batiment' => 'b1', 'cite_id' => 'c1', 'name' => 'Bâtiment A']);
        DB::table('chambre')->insert(['id_chambre' => 'ch1', 'batiment_id' => 'b1', 'numero_chambre' => 'A-12']);
        DB::table('users')->insert(['id_user' => 'admin', 'name' => 'Responsable maintenance']);
        $this->actingAs(new User(['id_user' => 'admin', 'name' => 'Admin', 'role_id' => 1]), 'admin');

        $cite = (new Cite)->forceFill(['id_cite' => 'c1', 'name' => 'Cité Bondoukou']);
        $this->mock(CiteService::class)->shouldReceive('getCites')->andReturn(new Collection([$cite]));
        $technicien = (new Maintenancier)->forceFill(['id_maintenancier' => 'm1', 'name' => 'Koffi Yao', 'specialite' => 'plomberie', 'tel' => '0700000000']);
        $technicien->setRelation('cite', null);
        $this->mock(MaintenancierRepositoryInterface::class)->shouldReceive('getAll')->andReturn(new Collection([$technicien]));
        $this->mock(InterventionService::class);
        $this->service = $this->mock(OrdreTravailServiceInterface::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function ordre(array $attributs = []): OrdreTravail
    {
        $ordre = (new OrdreTravail)->forceFill(array_merge([
            'id_ordres_travail' => 'ot1', 'numero_ot' => 'OT2026-0001', 'titre' => 'Fuite sous l’évier', 'description' => 'Fuite d’eau continue.',
            'categorie' => 'plomberie', 'priorite' => 'critique', 'type_intervention' => 'corrective', 'statut' => 'en_cours',
            'cite_id' => 'c1', 'batiment_id' => 'b1', 'chambre_id' => 'ch1', 'created_by' => 'admin', 'created_at' => '2026-10-10 09:00:00',
            'date_echeance' => '2026-10-01',
        ], $attributs));
        $ordre->setRelations([
            'residence' => (new Cite)->forceFill(['id_cite' => 'c1', 'name' => 'Cité Bondoukou']),
            'batiment' => null, 'chambre' => null, 'resident' => null, 'equipement' => null, 'createdBy' => null,
            'assigneA' => null, 'interventions' => new Collection,
        ]);

        return $ordre;
    }

    private function page(string $action, string $composant, array $query = [], array $parametres = []): array
    {
        $request = Request::create('/admin/gestion-hebergement/maintenance', 'GET', $query);
        $request->headers->set('X-Inertia', 'true');
        $request->setUserResolver(fn () => auth('admin')->user());
        $this->app->instance('request', $request);

        $page = app()->call([app(MaintenanceController::class), $action], ['request' => $request, ...$parametres])->toResponse($request)->getData(true);
        $this->assertSame($composant, $page['component']);

        return $page['props'];
    }

    public function test_liste_des_ordres_avec_filtres_et_actions(): void
    {
        $paginateur = fn (array $ordres) => new LengthAwarePaginator(new Collection($ordres), count($ordres), 10, 1, ['path' => '/admin/gestion-hebergement/maintenance']);
        $this->service->shouldReceive('getPaginated')->withArgs(fn ($filtres) => $filtres['completed'] === false && ! isset($filtres['priorite']))->andReturn($paginateur([$this->ordre()]));
        $this->service->shouldReceive('getPaginated')->withArgs(fn ($filtres) => $filtres['completed'] === true && $filtres['page_name'] === 'termines_page')->andReturn($paginateur([]));
        $this->service->shouldReceive('getStats')->andReturn([
            'total' => 4, 'en_attente' => 1, 'en_cours' => 1, 'en_attente_pieces' => 0, 'termine' => 2, 'urgent' => 1,
            'cout_total' => 15000, 'temps_moyen_resolution' => 2.5, 'satisfaction_count' => 1, 'taux_satisfaction' => 4,
        ]);
        $this->service->shouldReceive('getAll')->andReturn(new Collection);

        $props = $this->page('index', 'Admin/Hebergement/Maintenance/Index', ['priorite' => 'urgente', 'categorie' => 'plomberie']);

        // « urgente » n'est pas une priorité d'OT : filtre ignoré.
        $this->assertSame(['search' => '', 'cite_id' => '', 'priorite' => '', 'categorie' => 'plomberie'], $props['filters']);
        $ligne = $props['ordres']['data'][0];
        $this->assertSame(['OT2026-0001', 'Cité Bondoukou', 'Plomberie', 'Critique', 'En cours'], [$ligne['numero'], $ligne['residence'], $ligne['categorie_label'], $ligne['priorite_label'], $ligne['statut_label']]);
        $this->assertStringEndsWith('/maintenance/ot1', $ligne['urls']['show']);
        $this->assertStringEndsWith('/maintenance/ot1/edit', $ligne['urls']['edit']);
        $this->assertSame(1, $props['stats']['urgent']);
        $this->assertEquals(2.5, $props['stats']['temps_moyen']);
        $this->assertSame([['id' => 'b1', 'cite_id' => 'c1', 'name' => 'Bâtiment A']], $props['structure']['batiments']);
        $this->assertSame('Koffi Yao', $props['maintenanciers'][0]['name']);
        $this->assertSame(50, $props['statsParCite'][0]['taux_resolution']);
        $this->assertNotNull($props['urls']['store']);
        $this->assertNotNull($props['urls']['storeMaintenancier']);
    }

    public function test_fiche_d_un_ordre_en_cours_et_actions_proposees(): void
    {
        $this->service->shouldReceive('findById')->with('ot1')->andReturn($this->ordre());

        $props = $this->page('show', 'Admin/Hebergement/Maintenance/Show', [], ['id' => 'ot1']);

        $this->assertSame('Responsable maintenance', $props['ordre']['auteur']);
        $this->assertSame('Plomberie', $props['ordre']['categorie_label']);
        $this->assertSame(['en_attente_pieces', 'termine', 'reporte'], $props['transitions']);
        // Déjà en cours : plus d'assignation possible ; intervention et annulation proposées.
        $this->assertNull($props['urls']['assigner']);
        $this->assertNotNull($props['urls']['intervention']);
        $this->assertNotNull($props['urls']['annuler']);
        $this->assertNull($props['urls']['satisfaction']);
    }

    public function test_ordre_en_attente_propose_l_assignation(): void
    {
        $this->service->shouldReceive('findById')->with('ot1')->andReturn($this->ordre(['statut' => 'en_attente']));

        $props = $this->page('show', 'Admin/Hebergement/Maintenance/Show', [], ['id' => 'ot1']);

        $this->assertSame([], $props['transitions']);
        $this->assertNotNull($props['urls']['assigner']);
        $this->assertNull($props['urls']['intervention']);
    }

    public function test_formulaire_de_modification_et_ordre_cloture(): void
    {
        DB::table('equipement')->insert(['equipement_id' => 'eq1', 'name' => 'Chauffe-eau']);
        $this->service->shouldReceive('findById')->with('ot1')->andReturn($this->ordre(['cout_estime' => 12000]));

        $props = $this->page('edit', 'Admin/Hebergement/Maintenance/Edit', [], ['id' => 'ot1']);

        $this->assertSame(['Fuite sous l’évier', 'plomberie', 'critique', '2026-10-01', '12000', ''], [
            $props['ordre']['titre'], $props['ordre']['categorie'], $props['ordre']['priorite'], $props['ordre']['date_echeance'], $props['ordre']['cout_estime'], $props['ordre']['cout_reel'],
        ]);
        $this->assertSame([['id' => 'eq1', 'name' => 'Chauffe-eau']], $props['equipements']);

        $termine = Mockery::mock(OrdreTravailServiceInterface::class);
        $termine->shouldReceive('findById')->with('ot2')->andReturn($this->ordre(['id_ordres_travail' => 'ot2', 'statut' => 'termine']));
        $this->app->instance(OrdreTravailServiceInterface::class, $termine);
        $reponse = app(MaintenanceController::class)->edit(Request::create('/'), 'ot2');
        $this->assertInstanceOf(RedirectResponse::class, $reponse);
        $this->assertStringEndsWith('/maintenance/ot2', $reponse->getTargetUrl());
    }

    public function test_cloture_avec_solution_et_annulation(): void
    {
        $this->service->shouldReceive('updateStatut')->once()->with('ot1', 'termine', 'Joint remplacé')->andReturn($this->ordre(['statut' => 'termine']));
        $this->service->shouldReceive('updateStatut')->once()->with('ot1', 'annule')->andReturn($this->ordre(['statut' => 'annule']));
        $controleur = app(MaintenanceController::class);

        $reponse = $controleur->changerStatut(Request::create('/', 'POST', ['statut' => 'termine', 'solution' => 'Joint remplacé']), 'ot1');
        $this->assertStringEndsWith('/maintenance/ot1', $reponse->getTargetUrl());
        $this->assertSame('Statut mis à jour avec succès.', session('success'));

        $reponse = $controleur->annuler(Request::create('/', 'POST'), 'ot1');
        $this->assertStringEndsWith('/maintenance/ot1', $reponse->getTargetUrl());
        $this->assertSame('Ordre de travail annulé.', session('success'));
    }
}
