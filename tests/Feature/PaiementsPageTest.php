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
    /** Campagne transmise aux statistiques de loyers simulées (dernier appel). */
    private ?string $campagneStats = 'non appelé';

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
            $t->string('contrat_id')->nullable();
            $t->string('loyer_id')->nullable();
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
        // Types de paiement paramétrables (types système semés) proposés dans « Nouveau paiement ».
        (require database_path('migrations/2026_11_23_000002_create_types_paiement_table.php'))->up();
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

        Schema::create('campagne', function (Blueprint $t) {
            $t->string('id_campagne')->primary();
            $t->string('libelle')->nullable();
            $t->string('annee_campagne')->nullable();
            $t->string('statut')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('assignation_chambres', function (Blueprint $t) {
            $t->string('id_assignation')->primary();
            $t->string('campagne_id')->nullable();
            $t->timestamps();
        });
        Schema::create('loyers', function (Blueprint $t) {
            $t->string('id_loyer')->primary();
            $t->string('assignation_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        DB::table('campagne')->insert([
            ['id_campagne' => 'k0', 'libelle' => 'Campagne 2025-2026', 'annee_campagne' => '2025-2026', 'statut' => 'terminee', 'created_at' => now()->subYear()],
            ['id_campagne' => 'k1', 'libelle' => 'Campagne 2026-2027', 'annee_campagne' => '2026-2027', 'statut' => 'active', 'created_at' => now()],
        ]);
        DB::table('assignation_chambres')->insert([['id_assignation' => 'a0', 'campagne_id' => 'k0'], ['id_assignation' => 'a1', 'campagne_id' => 'k1']]);
        DB::table('loyers')->insert(['id_loyer' => 'l0', 'assignation_id' => 'a0']);
        DB::table('cite')->insert(['id_cite' => 'c1', 'name' => 'Cité Bondoukou']);
        DB::table('chambre')->insert(['id_chambre' => 'ch1', 'numero_chambre' => 'A-12']);
        DB::table('etudiants')->insert(['id_etudiant' => 'e1', 'name' => 'Awa Koné', 'matricule' => 'M001', 'telephone1' => '0700000000']);
        DB::table('paiements')->insert([
            'id_paiement' => 'p1', 'numero_paiement' => 'PAY-001', 'resident_id' => 'e1', 'contrat_id' => 'a1', 'chambre_id' => 'ch1', 'cite_id' => 'c1', 'montant' => 25000,
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
        $loyers->shouldReceive('getStatistiquesLoyers')->andReturnUsing(function ($annee = null, $mois = null, $residence = null, $campagne = null) {
            $this->campagneStats = $campagne;

            return [
                'total_montant' => 100000.0, 'total_paye' => 40000.0, 'total_solde_restant' => 60000.0, 'taux_recouvrement' => 40.0,
                'nombre_en_retard' => 2, 'total_penalites' => 1500.0,
            ];
        });
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
        // `nombre_paiements` : paiements de la campagne datés du mois en cours (p1 n'a pas de date de paiement).
        $this->assertEquals(['total_encaisse' => 40000, 'taux_recouvrement' => 40, 'impayes' => 60000, 'penalites' => 1500, 'nombre_paiements' => 0, 'nombre_retard' => 2], $props['kpis']);
        // Sans choix explicite : la campagne active.
        $this->assertSame(['campagne' => 'k1', 'residence' => '', 'statut' => 'en_attente', 'mois' => '2026-10'], $props['filters']);
        $this->assertSame('k1', $this->campagneStats);
        // Le récapitulatif par résidence a été retiré de la page.
        $this->assertArrayNotHasKey('statsParResidence', $props);
        $this->assertSame([['id' => 'c1', 'name' => 'Cité Bondoukou']], $props['residences']);
        // Types actifs du paramétrage, loyer en tête ; libellé du type lu dans le paramétrage.
        $this->assertSame(['loyer', 'caution', 'penalite', 'autre'], array_column($props['typesPaiement'], 'value'));
        $this->assertSame('Loyer', $ligne['type']);
        $this->assertStringContainsString('statut=en_attente', $props['paiements']['first_page_url']);
        // Sans administrateur connecté, aucune action n'est proposée.
        $this->assertNull($props['urls']['store']);
        $this->assertNull($props['urls']['export']);
        $this->assertNull($ligne['urls']['valider']);
    }

    public function test_classement_des_paiements_par_campagne(): void
    {
        // Campagnes de la plus récente à la plus ancienne.
        $props = $this->page('index', 'Admin/Hebergement/Paiements/Index');
        $this->assertSame([
            ['id' => 'k1', 'libelle' => 'Campagne 2026-2027', 'annee' => '2026-2027', 'active' => true],
            ['id' => 'k0', 'libelle' => 'Campagne 2025-2026', 'annee' => '2025-2026', 'active' => false],
        ], $props['campagnes']);

        // Campagne choisie, puis toutes les campagnes (aucune campagne transmise aux statistiques).
        $this->assertSame('k0', $this->page('index', 'Admin/Hebergement/Paiements/Index', ['campagne' => 'k0'])['filters']['campagne']);
        $this->assertSame('k0', $this->campagneStats);
        $this->assertSame('toutes', $this->page('index', 'Admin/Hebergement/Paiements/Index', ['campagne' => 'toutes'])['filters']['campagne']);
        $this->assertNull($this->campagneStats);
        // Identifiant inconnu : retour à la campagne active.
        $this->assertSame('k1', $this->page('index', 'Admin/Hebergement/Paiements/Index', ['campagne' => 'inconnue'])['filters']['campagne']);

        // Rattachement d'un paiement : par son contrat (attribution) ou, sans contrat, par le loyer facturé.
        DB::table('paiements')->insert([
            'id_paiement' => 'p2', 'numero_paiement' => 'PAY-002', 'resident_id' => 'e1', 'loyer_id' => 'l0', 'montant' => 10000,
            'statut_paiement' => 'valide', 'created_at' => now(),
        ]);
        $this->assertSame(['p1'], Paiement::query()->deCampagne('k1')->pluck('id_paiement')->all());
        $this->assertSame(['p2'], Paiement::query()->deCampagne('k0')->pluck('id_paiement')->all());
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

    /** « Nouveau paiement » : contrôleur dont le service de paiement enregistre les lignes reçues. */
    private function enregistrer(array $lignes, array $verification = ['suffisant' => true, 'arriere_total' => 0, 'montant_mois' => 12000, 'reste_apres_arriere' => 0, 'reste_apres_mois' => 0], bool $readmission = false, ?array $moisConcernes = null): array
    {
        Schema::table('chambre', fn (Blueprint $t) => $t->string('type_chambre_id')->nullable());
        Schema::table('assignation_chambres', function (Blueprint $t) {
            $t->string('etudiant_id')->nullable();
            $t->string('statut')->nullable();
        });
        Schema::table('campagne', function (Blueprint $t) {
            $t->boolean('admission')->default(true);
            $t->boolean('readmission')->default(true);
        });
        // Attribution confirmée de la campagne en cours (a1) ; `$readmission` : déjà logé lors d'une campagne précédente (a0).
        DB::table('assignation_chambres')->where('id_assignation', 'a1')->update(['etudiant_id' => 'e1', 'statut' => 'confirmee']);
        DB::table('assignation_chambres')->where('id_assignation', 'a0')->update(['etudiant_id' => $readmission ? 'e1' : 'autre', 'statut' => 'terminee']);
        Schema::create('type_chambre', function (Blueprint $t) {
            $t->string('id_type_chambre')->primary();
            $t->string('name');
            $t->decimal('montant', 12, 2)->default(0);
            $t->timestamps();
        });
        Schema::create('batiment', fn (Blueprint $t) => $t->string('id_batiment')->primary());
        DB::table('type_chambre')->insert(['id_type_chambre' => 't1', 'name' => 'Individuelle', 'montant' => 12000]);
        DB::table('chambre')->where('id_chambre', 'ch1')->update(['type_chambre_id' => 't1']);
        DB::table('batiment')->insert(['id_batiment' => 'b1']);

        $enregistrees = [];
        $paiements = Mockery::mock(PaiementService::class);
        $paiements->shouldReceive('verifierPaiementSuffisant')->andReturn($verification);
        // Prochain mois à payer : décembre 2026.
        $suite = ['12-2026', '01-2027', '02-2027', '03-2027', '04-2027', '05-2027'];
        $paiements->shouldReceive('moisConsecutifs')->andReturnUsing(fn ($etudiant, $nombre) => array_slice($suite, 0, $nombre));
        // Par défaut, le formulaire coche les mois attendus (loyer + avance).
        $moisConcernes ??= array_slice($suite, 0, array_sum(array_column(array_filter($lignes, fn ($l) => in_array($l['type_paiement'], ['loyer', 'avance'], true)), 'mois')));
        $paiements->shouldReceive('creerPaiements')->andReturnUsing(function ($commun, $lignesRecues) use (&$enregistrees) {
            $enregistrees = $lignesRecues;

            return [];
        });
        $controleur = new PaiementController($paiements, Mockery::mock(EtudiantService::class), Mockery::mock(LoyerService::class), Mockery::mock(CiteService::class));

        // Montant envoyé par le navigateur : doit être ignoré.
        $request = Request::create('/admin/gestion-hebergement/paiements', 'POST', [
            'resident_id' => 'e1', 'chambre_id' => 'ch1', 'batiment_id' => 'b1', 'cite_id' => 'c1',
            'methode_paiement' => 'especes', 'montant' => 1, 'lignes' => $lignes, 'mois_concernes' => $moisConcernes,
        ]);
        $this->app->instance('request', $request);
        $controleur->store($request);

        return $enregistrees;
    }

    public function test_nouveau_paiement_montant_calcule_depuis_le_type_de_chambre(): void
    {
        $lignes = $this->enregistrer([['type_paiement' => 'loyer', 'mois' => 3, 'montant' => 5], ['type_paiement' => 'caution', 'mois' => 2]]);

        $this->assertEquals([['type_paiement' => 'loyer', 'montant' => 36000], ['type_paiement' => 'caution', 'montant' => 24000]], $lignes);
    }

    public function test_nouveau_paiement_pas_de_caution_en_readmission(): void
    {
        try {
            $this->enregistrer([['type_paiement' => 'loyer', 'mois' => 1], ['type_paiement' => 'caution', 'mois' => 2]], readmission: true);
            $this->fail('La caution doit être refusée en réadmission.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertSame(['lignes'], array_keys($e->errors()));
        }
    }

    public function test_nouveau_paiement_readmission_sans_caution_accepte(): void
    {
        $lignes = $this->enregistrer([['type_paiement' => 'loyer', 'mois' => 1]], readmission: true);

        $this->assertEquals([['type_paiement' => 'loyer', 'montant' => 12000]], $lignes);
    }

    public function test_nouveau_paiement_avance_apres_le_loyer_sur_des_mois_consecutifs(): void
    {
        DB::table('types_paiement')->insert(['id_type_paiement' => 'tp-avance', 'code' => 'avance', 'name' => 'Avance', 'actif' => true, 'systeme' => false, 'ordre' => 0]);

        // Avance envoyée avant le loyer : enregistrée après lui, pour prendre les mois qui suivent.
        $lignes = $this->enregistrer([['type_paiement' => 'avance', 'mois' => 2], ['type_paiement' => 'loyer', 'mois' => 1]]);

        $this->assertEquals([['type_paiement' => 'loyer', 'montant' => 12000], ['type_paiement' => 'avance', 'montant' => 24000]], $lignes);
    }

    public function test_nouveau_paiement_mois_non_consecutifs_refuses(): void
    {
        try {
            $this->enregistrer([['type_paiement' => 'loyer', 'mois' => 2]], moisConcernes: ['12-2026', '02-2027']);
            $this->fail('Des mois non consécutifs doivent être refusés.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertSame(['mois_concernes'], array_keys($e->errors()));
        }
    }

    public function test_nouveau_paiement_mois_insuffisants_pour_les_arrieres(): void
    {
        try {
            $this->enregistrer([['type_paiement' => 'caution', 'mois' => 1], ['type_paiement' => 'loyer', 'mois' => 1]], ['suffisant' => false, 'arriere_total' => 30000]);
            $this->fail('Le loyer insuffisant doit être refusé.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertSame(['lignes.1.mois'], array_keys($e->errors()));
            $this->assertStringContainsString('au moins 3 mois', $e->errors()['lignes.1.mois'][0]);
        }
    }
}
