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

    /** Données communes transmises à `creerPaiements` (dernier enregistrement). */
    private array $communEnregistre = [];

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
            $t->string('statut_inscription')->nullable();
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
            $t->string('created_by')->nullable();
            $t->string('encaissement_id')->nullable();
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
            $t->integer('annee_debut')->nullable();
            $t->string('statut')->nullable();
            $t->string('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        // Calendrier des loyers de la campagne (onglet « Calendrier des loyers »).
        (require database_path('migrations/2026_11_26_000001_add_calendrier_loyer_to_campagne_table.php'))->up();
        Schema::create('assignation_chambres', function (Blueprint $t) {
            $t->string('id_assignation')->primary();
            $t->string('campagne_id')->nullable();
            $t->timestamps();
        });
        Schema::create('loyers', function (Blueprint $t) {
            $t->string('id_loyer')->primary();
            $t->string('assignation_id')->nullable();
            $t->integer('mois_facturation')->nullable();
            $t->integer('annee_facturation')->nullable();
            $t->decimal('total_paye', 12, 2)->default(0);
            $t->decimal('solde_restant', 12, 2)->default(0);
            $t->dateTime('date_limit_paiement')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        // Demandes : rattachent à une campagne les paiements avant l'attribution (ni contrat ni loyer).
        Schema::create('demande', function (Blueprint $t) {
            $t->string('id_demande')->primary();
            foreach (['etudiant_id', 'campagne_id', 'etat_dossier', 'liste_approbation_commission', 'num_table_bac'] as $champ) {
                $t->string($champ)->nullable();
            }
            $t->dateTime('date_approbation_commission')->nullable();
            $t->timestamps();
        });

        DB::table('campagne')->insert([
            ['id_campagne' => 'k0', 'libelle' => 'Campagne 2025-2026', 'annee_campagne' => '2025-2026', 'annee_debut' => 2025, 'statut' => 'terminee', 'created_at' => now()->subYear()],
            ['id_campagne' => 'k1', 'libelle' => 'Campagne 2026-2027', 'annee_campagne' => '2026-2027', 'annee_debut' => 2026, 'statut' => 'active', 'created_at' => now()],
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
        // Mois abrégés dans le tableau.
        $this->assertSame('oct-2026', $ligne['mois']);
        // `total_encaisse` / `nombre_paiements` : paiements validés de la campagne (p1 est en attente).
        // Aucune pénalité de retard : à la place, la prochaine échéance du calendrier des loyers (non configuré ici).
        $this->assertEquals(['total_encaisse' => 0, 'taux_recouvrement' => 40, 'impayes' => 60000, 'prochaine_echeance' => null, 'nombre_paiements' => 0, 'nombre_retard' => 2], $props['kpis']);
        // Encaissé : tous les types validés de la campagne (loyer, caution avant l'attribution…), pas les loyers facturés seuls.
        DB::table('paiements')->where('id_paiement', 'p1')->update(['statut_paiement' => 'valide']);
        DB::table('demande')->insert(['id_demande' => 'd0', 'etudiant_id' => 'e1', 'campagne_id' => 'k1', 'created_at' => now()->subWeek()]);
        DB::table('paiements')->insert(['id_paiement' => 'p5', 'numero_paiement' => 'PAY-005', 'resident_id' => 'e1', 'montant' => 24000, 'type_paiement' => 'caution', 'methode_paiement' => 'especes', 'statut_paiement' => 'valide', 'created_at' => now()]);
        $kpis = $this->page('index', 'Admin/Hebergement/Paiements/Index')['kpis'];
        $this->assertEquals([49000, 2], [$kpis['total_encaisse'], $kpis['nombre_paiements']]);
        DB::table('paiements')->where('id_paiement', 'p1')->update(['statut_paiement' => 'en_attente']);
        DB::table('paiements')->where('id_paiement', 'p5')->delete();
        DB::table('demande')->where('id_demande', 'd0')->delete();
        $this->assertSame('paiements', $props['onglet']);
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

        // Avant l'attribution (caution, avance : ni contrat ni loyer) : campagne de la demande déposée avant le paiement.
        DB::table('demande')->insert(['id_demande' => 'd1', 'etudiant_id' => 'e1', 'campagne_id' => 'k1', 'created_at' => now()->subWeek()]);
        DB::table('paiements')->insert([
            ['id_paiement' => 'p3', 'numero_paiement' => 'PAY-003', 'resident_id' => 'e1', 'montant' => 24000, 'type_paiement' => 'caution', 'statut_paiement' => 'valide', 'created_at' => now()],
            // Antérieur à la demande : pas de cette campagne.
            ['id_paiement' => 'p4', 'numero_paiement' => 'PAY-004', 'resident_id' => 'e1', 'montant' => 8000, 'type_paiement' => 'avance', 'statut_paiement' => 'valide', 'created_at' => now()->subMonth()],
        ]);
        $this->assertSame(['p1', 'p3'], Paiement::query()->deCampagne('k1')->orderBy('id_paiement')->pluck('id_paiement')->all());
        $this->assertSame(['p2'], Paiement::query()->deCampagne('k0')->pluck('id_paiement')->all());
    }

    public function test_detail_d_un_paiement(): void
    {
        // « Créé par » : nom de l'administrateur qui a enregistré le paiement.
        DB::table('users')->insert(['id_user' => 'u1', 'name' => 'Koffi Yao']);
        DB::table('paiements')->where('id_paiement', 'p1')->update(['created_by' => 'u1']);
        $props = $this->page('show', 'Admin/Hebergement/Paiements/Show', [], ['id' => 'p1']);

        $paiement = $props['paiement'];
        $this->assertSame(['PAY-001', 'en_attente', 'Awa Koné', 'M001', 'A-12'], [
            $paiement['numero'], $paiement['statut'], $paiement['resident']['name'], $paiement['resident']['matricule'], $paiement['resident']['chambre'],
        ]);
        $this->assertSame(['2026-10'], $paiement['mois']);
        $this->assertArrayNotHasKey('repartitions', $paiement);
        $this->assertSame('Koffi Yao', $paiement['cree_par']);
        $this->assertNull($props['urls']['valider']);
    }

    public function test_recu_pdf_d_un_paiement_sur_une_seule_page(): void
    {
        // Longue répartition (24 lignes) : regroupée pour tenir sur une page.
        foreach (range(1, 24) as $i) {
            DB::table('paiement_repartitions')->insert(['id_repartition' => "rx{$i}", 'paiement_id' => 'p1', 'type' => 'avance', 'mois' => ($i % 12) + 1, 'annee' => 2027, 'montant' => 1000]);
        }

        $reponse = $this->controleur()->recu('p1');

        $this->assertSame('application/pdf', $reponse->headers->get('Content-Type'));
        $this->assertStringContainsString('recu-PAY-001.pdf', (string) $reponse->headers->get('Content-Disposition'));
        $this->assertSame(1, preg_match_all('#/Type\s*/Page[^s]#', $reponse->getContent()));
    }

    public function test_l_etudiant_obtient_le_meme_recu_que_l_administration(): void
    {
        $vue = null;
        \Illuminate\Support\Facades\View::composer('pdf.recu-paiement', function ($v) use (&$vue) {
            $vue = $v->getData();
        });
        $controleur = new \App\Http\Controllers\etudiant\RecuPaiementController;
        $requete = function (string $etudiant, array $query = []) {
            $requete = \Illuminate\Http\Request::create('/etudiant/mes-paiements/recu/p1', 'GET', $query);
            $requete->attributes->set('student', \App\Models\Etudiant::make()->forceFill(['id_etudiant' => $etudiant]));

            return $requete;
        };
        $p1 = DB::table('paiements')->where('id_paiement', 'p1')->first();
        // Un paiement en attente n'a pas de reçu côté étudiant.
        try {
            $controleur->show($requete($p1->resident_id), app(\App\Services\RecuPaiement::class), 'p1');
            $this->fail('Reçu d’un paiement en attente');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
        }
        DB::table('paiements')->where('id_paiement', 'p1')->update(['statut_paiement' => 'valide']);

        $apercu = $controleur->show($requete($p1->resident_id), app(\App\Services\RecuPaiement::class), 'p1');
        $this->assertSame('application/pdf', $apercu->headers->get('Content-Type'));
        $this->assertStringStartsWith('inline', (string) $apercu->headers->get('Content-Disposition'));
        $this->assertSame('PAY-001', $vue['details'][0]['numero']);
        $this->assertSame(['filiere', 'niveau'], array_keys($vue['parcours']));
        $telechargement = $controleur->show($requete($p1->resident_id, ['telecharger' => 1]), app(\App\Services\RecuPaiement::class), 'p1');
        $this->assertStringStartsWith('attachment', (string) $telechargement->headers->get('Content-Disposition'));

        // Le reçu d'un autre étudiant est introuvable.
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $controleur->show($requete('quelqu-un-d-autre'), app(\App\Services\RecuPaiement::class), 'p1');
    }

    public function test_recu_unique_pour_les_types_enregistres_ensemble(): void
    {
        $base = ['resident_id' => 'e1', 'contrat_id' => null, 'methode_paiement' => 'especes', 'statut_paiement' => 'valide', 'created_at' => now()];
        DB::table('paiements')->insert([
            $base + ['id_paiement' => 'g1', 'numero_paiement' => 'PAY-011', 'type_paiement' => 'loyer', 'montant' => 16000, 'mois' => json_encode(['12-2026', '01-2027']), 'encaissement_id' => 'enc'],
            $base + ['id_paiement' => 'g2', 'numero_paiement' => 'PAY-012', 'type_paiement' => 'caution', 'montant' => 24000, 'mois' => '[]', 'encaissement_id' => 'enc'],
            // Même instant, mais enregistré seul : pas sur ce reçu.
            $base + ['id_paiement' => 'g3', 'numero_paiement' => 'PAY-013', 'type_paiement' => 'autre', 'montant' => 5000, 'mois' => '[]', 'encaissement_id' => null],
        ]);
        $vue = null;
        \Illuminate\Support\Facades\View::composer('pdf.recu-paiement', function ($v) use (&$vue) {
            $vue = $v->getData();
        });

        // Même reçu depuis chacun des deux paiements.
        foreach (['g1', 'g2'] as $id) {
            $reponse = $this->controleur()->recu($id);
            $this->assertStringContainsString('recu-PAY-011.pdf', (string) $reponse->headers->get('Content-Disposition'));
            $this->assertSame(1, preg_match_all('~/Type\s*/Page\b(?!s)~', $reponse->getContent()));
            $this->assertSame(['PAY-011', 'PAY-012'], array_column($vue['details'], 'numero'));
            $this->assertSame(formatSystemAmount(40000), $vue['montant']);
        }
        $this->controleur()->recu('g3');
        $this->assertSame(['PAY-013'], array_column($vue['details'], 'numero'));

        // Un paiement annulé sort du reçu commun.
        DB::table('paiements')->where('id_paiement', 'g2')->update(['statut_paiement' => 'annule']);
        $this->controleur()->recu('g1');
        $this->assertSame(['PAY-011'], array_column($vue['details'], 'numero'));
        $this->assertSame(formatSystemAmount(16000), $vue['montant']);
    }

    public function test_reprise_des_paiements_deja_enregistres_ensemble(): void
    {
        Schema::table('paiements', fn (Blueprint $t) => $t->dropColumn('encaissement_id'));
        $base = ['resident_id' => 'e1', 'created_by' => 'u1', 'methode_paiement' => 'especes', 'statut_paiement' => 'valide', 'montant' => 1000];
        $instant = '2026-11-25 14:11:20';
        DB::table('paiements')->insert([
            $base + ['id_paiement' => 'g1', 'type_paiement' => 'avance', 'created_at' => $instant],
            $base + ['id_paiement' => 'g2', 'type_paiement' => 'caution', 'created_at' => $instant],
            $base + ['id_paiement' => 'g3', 'type_paiement' => 'autre', 'created_at' => '2026-11-25 15:00:00'],
            ['resident_id' => 'e2'] + $base + ['id_paiement' => 'g4', 'type_paiement' => 'caution', 'created_at' => $instant],
            $base + ['id_paiement' => 'g5', 'type_paiement' => 'avance', 'created_at' => '2026-10-15 10:00:00'],
            $base + ['id_paiement' => 'g6', 'type_paiement' => 'caution', 'created_at' => '2026-10-15 10:00:00'],
        ]);

        (require database_path('migrations/2026_11_25_000006_add_encaissement_to_paiements.php'))->up();

        $encaissements = DB::table('paiements')->pluck('encaissement_id', 'id_paiement');
        $this->assertNotNull($encaissements['g1']);
        $this->assertSame($encaissements['g1'], $encaissements['g2']);
        foreach (['g3', 'g4', 'g5', 'g6'] as $id) {
            $this->assertNull($encaissements[$id]);
        }
    }

    /**
     * Rapport financier, filtré par cité, campagne (active par défaut) et mois de la campagne : attendu, recouvré,
     * impayés et retards sur les loyers facturés de la campagne (mois facturé) ; encaissé, modes et évolution sur les
     * paiements validés de la campagne, tous types (mois du paiement).
     */
    public function test_rapport_financier(): void
    {
        \Carbon\Carbon::setTestNow('2026-11-26 10:00:00');
        Schema::table('loyers', function (Blueprint $t) {
            $t->string('cite_id')->nullable();
            $t->string('etudiant_id')->nullable();
            $t->decimal('montant_total', 12, 2)->default(0);
            $t->string('statut')->nullable();
        });
        DB::table('loyers')->delete();
        $loyer = fn (string $id, string $mois, float $paye, string $statut = 'actif', string $assignation = 'a1') => [
            'id_loyer' => $id, 'assignation_id' => $assignation, 'cite_id' => 'c1', 'etudiant_id' => $id === 'l3' ? 'e2' : 'e1',
            'mois_facturation' => (int) substr($mois, 5, 2), 'annee_facturation' => (int) substr($mois, 0, 4),
            'montant_total' => 10000, 'total_paye' => $paye, 'solde_restant' => 10000 - $paye,
            'date_limit_paiement' => $mois.'-10', 'statut' => $statut, 'created_at' => now(),
        ];
        DB::table('loyers')->insert([
            $loyer('l1', '2026-10', 10000, 'termine'),
            $loyer('l2', '2026-11', 4000),            // échéance passée : 6 000 en retard
            $loyer('l3', '2026-12', 0),               // pas encore échu
            $loyer('l4', '2026-11', 0, 'annule'),
            $loyer('l5', '2025-11', 0, 'actif', 'a0'), // campagne 2025-2026
        ]);
        $paiement = fn (string $id, string $type, string $methode, float $montant, string $date, ?string $contrat = 'a1', string $statut = 'valide') => [
            'id_paiement' => $id, 'numero_paiement' => strtoupper($id), 'resident_id' => 'e1', 'contrat_id' => $contrat, 'cite_id' => $contrat ? 'c1' : null,
            'montant' => $montant, 'type_paiement' => $type, 'methode_paiement' => $methode, 'statut_paiement' => $statut, 'date_paiement' => $date, 'created_at' => $date,
        ];
        // Caution avant l'attribution : rattachée à la campagne par la demande déposée avant le paiement.
        DB::table('demande')->insert(['id_demande' => 'd1', 'etudiant_id' => 'e1', 'campagne_id' => 'k1', 'created_at' => '2026-08-01 09:00:00']);
        DB::table('paiements')->insert([
            $paiement('r1', 'loyer', 'especes', 10000, '2026-10-05 09:00:00'),
            $paiement('r2', 'loyer', 'especes', 4000, '2026-11-15 09:00:00'),
            $paiement('r3', 'caution', 'orange_money', 24000, '2026-08-20 09:00:00', null),
            $paiement('r4', 'loyer', 'especes', 9000, '2025-11-05 09:00:00', 'a0'),
            $paiement('r5', 'loyer', 'especes', 7000, '2026-11-21 09:00:00', 'a1', 'annule'),
        ]);
        $rapport = fn (array $filtres = []) => $this->page('rapportFinancier', 'Admin/Hebergement/Paiements/RapportFinancier', $filtres);

        // Par défaut : la campagne active (k1), tous ses mois, toutes les cités.
        $props = $rapport();
        $this->assertSame(['campagne' => 'k1', 'mois' => '', 'residence' => ''], $props['filters']);
        $this->assertEquals([
            'total_attendu' => 30000, 'total_recouvre' => 14000, 'total_encaisse' => 38000, 'total_impayes' => 16000, 'total_retard' => 6000,
            'nb_retard' => 1, 'nb_impayes' => 2, 'nb_loyers' => 3, 'nb_paiements' => 3, 'taux_recouvrement' => 46.7,
        ], $props['stats']);
        $this->assertEquals(['paye' => 14000, 'a_echoir' => 10000, 'retard' => 6000], $props['repartition']);
        // Mois proposés : ceux du calendrier des loyers de la campagne (proposé ici : 10 mois à partir de septembre 2026).
        $this->assertCount(10, $props['mois']);
        $this->assertSame([['value' => '2026-09', 'label' => 'Septembre 2026'], ['value' => '2027-06', 'label' => 'Juin 2027']], [$props['mois'][0], $props['mois'][9]]);
        // Évolution : les mois de la campagne, plus août 2026 (caution versée avant le premier mois).
        $this->assertCount(11, $props['evolution']);
        $this->assertEquals([
            ['label' => 'Aoû 2026', 'encaisse' => 24000, 'attendu' => 0],
            ['label' => 'Sep 2026', 'encaisse' => 0, 'attendu' => 0],
            ['label' => 'Oct 2026', 'encaisse' => 10000, 'attendu' => 10000],
            ['label' => 'Nov 2026', 'encaisse' => 4000, 'attendu' => 10000],
            ['label' => 'Déc 2026', 'encaisse' => 0, 'attendu' => 10000],
        ], array_slice($props['evolution'], 0, 5));
        $this->assertEquals([['Orange Money', 1, 24000, 63.2], ['Espèces', 2, 14000, 36.8]], array_map(
            fn (array $mode) => [$mode['libelle'], $mode['nombre'], $mode['total'], $mode['pourcentage']], $props['statsParMode'],
        ));
        $this->assertSame([['id' => 'k1', 'libelle' => 'Campagne 2026-2027', 'active' => true], ['id' => 'k0', 'libelle' => 'Campagne 2025-2026', 'active' => false]], $props['campagnes']);
        $this->assertArrayNotHasKey('statsParResidence', $props);
        $this->assertArrayNotHasKey('derniersPaiements', $props);

        // Un mois de la campagne : indicateurs de ce mois, évolution de toute la campagne.
        $props = $rapport(['campagne' => 'k1', 'mois' => '2026-11']);
        $this->assertEquals([10000, 4000, 4000, 40], [$props['stats']['total_attendu'], $props['stats']['total_recouvre'], $props['stats']['total_encaisse'], $props['stats']['taux_recouvrement']]);
        $this->assertCount(11, $props['evolution']);
        $this->assertSame('2026-11', $props['filters']['mois']);
        // Mois hors de la campagne : ignoré.
        $this->assertSame('', $rapport(['campagne' => 'k1', 'mois' => '2025-11'])['filters']['mois']);

        // Une cité : les paiements sans cité (caution avant l'attribution) n'y sont pas comptés.
        $props = $rapport(['residence' => 'c1']);
        $this->assertEquals([14000, 2], [$props['stats']['total_encaisse'], $props['stats']['nb_paiements']]);

        // Autre campagne.
        $props = $rapport(['campagne' => 'k0']);
        $this->assertEquals([10000, 0, 9000, 10000], [$props['stats']['total_attendu'], $props['stats']['total_recouvre'], $props['stats']['total_encaisse'], $props['stats']['total_retard']]);

        // Toutes les campagnes : pas de mois à choisir.
        $props = $rapport(['campagne' => 'toutes', 'mois' => '2026-11']);
        $this->assertSame(['campagne' => 'toutes', 'mois' => '', 'residence' => ''], $props['filters']);
        $this->assertSame([], $props['mois']);
        $this->assertEquals([40000, 47000], [$props['stats']['total_attendu'], $props['stats']['total_encaisse']]);
        \Carbon\Carbon::setTestNow();
    }

    /** « Nouveau paiement » : contrôleur dont le service de paiement enregistre les lignes reçues. */
    private function enregistrer(array $lignes, array $verification = ['suffisant' => true, 'arriere_total' => 0, 'montant_mois' => 12000, 'reste_apres_arriere' => 0, 'reste_apres_mois' => 0], bool $readmission = false, ?array $moisConcernes = null, ?array $sansChambre = null): array
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
        if ($sansChambre !== null) {
            $this->etudiantAdmisALoger(...$sansChambre);
        }

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
            $this->communEnregistre = $commun;

            return [];
        });
        $controleur = new PaiementController($paiements, Mockery::mock(EtudiantService::class), Mockery::mock(LoyerService::class), Mockery::mock(CiteService::class));

        // Montant envoyé par le navigateur : doit être ignoré.
        // Sans chambre : ni chambre ni bâtiment, type de chambre choisi comme base du montant.
        $logement = $sansChambre !== null
            ? ['type_chambre_id' => $sansChambre['typeChambre'] ?? 't1']
            : ['chambre_id' => 'ch1', 'batiment_id' => 'b1', 'cite_id' => 'c1'];
        $request = Request::create('/admin/gestion-hebergement/paiements', 'POST', $logement + [
            'resident_id' => 'e1', 'methode_paiement' => 'especes', 'montant' => 1, 'lignes' => $lignes, 'mois_concernes' => $moisConcernes,
        ]);
        $this->app->instance('request', $request);
        $controleur->store($request);

        return $enregistrees;
    }

    /** e1 sans affectation confirmée, dernière demande de la campagne k1 ; `$admis` : figure dans la liste « Admis à loger ». */
    private function etudiantAdmisALoger(bool $admis = true, ?string $typeChambre = null): void
    {
        DB::table('assignation_chambres')->where('etudiant_id', 'e1')->update(['etudiant_id' => 'autre']);
        DB::table('campagne')->where('id_campagne', 'k1')->update(['admission' => true, 'readmission' => false]);
        DB::table('etudiants')->where('id_etudiant', 'e1')->update(['statut_inscription' => $admis ? 'en_verification' : 'en_attente']);
        DB::table('demande')->insert([
            'id_demande' => 'd1', 'etudiant_id' => 'e1', 'campagne_id' => 'k1', 'etat_dossier' => $admis ? 'confirme' : 'eligible',
            'created_at' => now()->subWeek(),
        ]);
    }

    public function test_nouveau_paiement_sans_chambre_pour_un_etudiant_admis_a_loger(): void
    {
        // Montant : mois × loyer du type de chambre choisi ; ni chambre ni bâtiment.
        $lignes = $this->enregistrer([['type_paiement' => 'caution', 'mois' => 2]], sansChambre: []);

        $this->assertEquals([['type_paiement' => 'caution', 'montant' => 24000]], $lignes);
    }

    public function test_nouveau_paiement_sans_chambre_refuse_le_loyer(): void
    {
        try {
            $this->enregistrer([['type_paiement' => 'loyer', 'mois' => 1]], sansChambre: []);
            $this->fail('Le loyer doit être refusé sans chambre.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertSame(['lignes'], array_keys($e->errors()));
        }
    }

    public function test_nouveau_paiement_sans_chambre_caution_et_avance_avant_l_attribution(): void
    {
        DB::table('types_paiement')->insert(['id_type_paiement' => 'tp-avance', 'code' => 'avance', 'name' => 'Avance', 'actif' => true, 'systeme' => false, 'ordre' => 0]);

        // L'avance est un loyer payé d'avance : elle porte les mois cochés (consécutifs), sans contrôle des arriérés.
        $lignes = $this->enregistrer([['type_paiement' => 'caution', 'mois' => 3], ['type_paiement' => 'avance', 'mois' => 2]], sansChambre: []);

        $this->assertEquals([['type_paiement' => 'avance', 'montant' => 24000, 'mois' => ['12-2026', '01-2027']], ['type_paiement' => 'caution', 'montant' => 36000]], $lignes);
        $this->assertTrue($this->communEnregistre['avant_attribution'] ?? false);
    }

    public function test_nouveau_paiement_sans_chambre_exige_un_type_de_chambre(): void
    {
        try {
            $this->enregistrer([['type_paiement' => 'caution', 'mois' => 1]], sansChambre: ['typeChambre' => 'inconnu']);
            $this->fail('Le type de chambre est obligatoire.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertSame(['type_chambre_id'], array_keys($e->errors()));
        }
    }

    public function test_nouveau_paiement_sans_chambre_hors_admis_a_loger_refuse(): void
    {
        try {
            $this->enregistrer([['type_paiement' => 'caution', 'mois' => 1]], sansChambre: ['admis' => false]);
            $this->fail('Un étudiant hors de la liste « Admis à loger » ne peut pas payer sans chambre.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertSame(['resident_id'], array_keys($e->errors()));
        }
    }

    /** Résultats de la recherche « Awa » de « Nouveau paiement ». */
    private function rechercher(): array
    {
        if (! Schema::hasColumn('etudiants', 'telephone2')) {
            Schema::table('etudiants', function (Blueprint $t) {
                $t->string('telephone2')->nullable();
                $t->string('email2')->nullable();
            });
        }
        $paiements = Mockery::mock(PaiementService::class);
        $paiements->shouldReceive('prochainMoisAPayer')->andReturn('12-2026');
        $paiements->shouldReceive('getLoyerActuel')->andReturn((object) ['solde_restant' => 12000, 'montant_total' => 12000]);
        $loyers = Mockery::mock(LoyerService::class);
        $loyers->shouldReceive('calculerArriere')->andReturn(['solde_restant' => 24000]);
        $controleur = new PaiementController($paiements, Mockery::mock(EtudiantService::class), $loyers, Mockery::mock(CiteService::class));

        return $controleur->rechercheResidents(Request::create('/x', 'GET', ['q' => 'Awa']))->getData(true);
    }

    public function test_recherche_propose_les_etudiants_admis_a_loger(): void
    {
        $this->enregistrer([['type_paiement' => 'caution', 'mois' => 1]], sansChambre: []);

        $resultats = $this->rechercher();

        $this->assertCount(1, $resultats);
        $this->assertSame(['e1', true, null, '2026-2027', false], [$resultats[0]['id_etudiant'], $resultats[0]['sans_chambre'], $resultats[0]['chambre_id'], $resultats[0]['campagne'], $resultats[0]['readmission']]);
        $this->assertSame([['id' => 't1', 'name' => 'Individuelle', 'montant' => 12000]], $resultats[0]['types_chambre']);
        // Conditions par défaut du volet admission : 3 mois de caution, 2 mois d'avance, rien de versé.
        $this->assertSame(['caution' => 3, 'avance' => 2], $resultats[0]['a_payer']);

        // Caution soldée, 1 mois d'avance versé : il reste 1 mois d'avance.
        foreach (['caution' => 36000, 'avance' => 12000] as $type => $montant) {
            DB::table('paiements')->insert(['id_paiement' => "p-{$type}", 'resident_id' => 'e1', 'montant' => $montant, 'type_paiement' => $type, 'statut_paiement' => 'valide', 'created_at' => now()]);
        }
        $this->assertSame(['avance' => 1], $this->rechercher()[0]['a_payer']);
    }

    public function test_recherche_propose_les_residents_loges(): void
    {
        // Résident logé (affectation confirmée) : chambre, loyer du type de chambre et arriérés, pour payer le loyer.
        $this->enregistrer([['type_paiement' => 'loyer', 'mois' => 1]]);
        DB::table('etudiants')->where('id_etudiant', 'e1')->update(['statut_inscription' => 'residents']);
        Schema::table('assignation_chambres', fn (Blueprint $t) => $t->string('chambre_id')->nullable());
        DB::table('assignation_chambres')->where('id_assignation', 'a1')->update(['chambre_id' => 'ch1']);

        $resultats = $this->rechercher();

        $this->assertCount(1, $resultats);
        $this->assertSame(['e1', false, 'ch1', 'a1', '12-2026'], [$resultats[0]['id_etudiant'], $resultats[0]['sans_chambre'], $resultats[0]['chambre_id'], $resultats[0]['contrat_id'], $resultats[0]['prochain_mois']]);
        $this->assertEquals([12000, 24000, 36000, 'En retard'], [$resultats[0]['loyer_mensuel'], $resultats[0]['arriere'], $resultats[0]['total_du'], $resultats[0]['statut_paiement']]);
    }

    public function test_recherche_sans_chambre_limitee_aux_admis_a_loger(): void
    {
        $this->enregistrer([['type_paiement' => 'loyer', 'mois' => 1]]);

        // Dossier « Recevable », pas encore admis à loger : pas proposé.
        $this->etudiantAdmisALoger(false);
        $this->assertSame([], $this->rechercher());
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

    public function test_nouveau_paiement_refuse_au_dela_des_mois_du_calendrier(): void
    {
        // Le service ne trouve que 6 mois restants à payer (calendrier des loyers) : 7 mois de loyer sont refusés.
        try {
            $this->enregistrer([['type_paiement' => 'loyer', 'mois' => 7]]);
            $this->fail('Un paiement au-delà des mois du calendrier doit être refusé.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertSame(['mois_concernes'], array_keys($e->errors()));
            $this->assertStringContainsString('Il ne reste que 6 mois', $e->errors()['mois_concernes'][0]);
        }
    }

    /** 10 mois consécutifs à partir de `$premier`, échéance le `$jour` du mois du loyer (attendu après enregistrement). */
    private function calendrierSaisi(int $jour = 5, string $premier = '2026-10'): array
    {
        $debut = \Carbon\CarbonImmutable::createFromFormat('!Y-m', $premier);

        return array_map(fn (int $rang) => [
            'mois' => $debut->addMonths($rang)->format('Y-m'),
            'echeance' => $debut->addMonths($rang)->setDay($jour)->format('Y-m-d'),
        ], range(0, 9));
    }

    /** Action « Enregistrer le calendrier » : premier mois de loyer, jour d'échéance, mois de l'échéance. */
    private function enregistrerCalendrier(string $premier = '2026-10', $jour = 5, string $echeance = 'mois_loyer', string $campagne = 'k1'): \Illuminate\Http\RedirectResponse
    {
        $request = Request::create('/admin/gestion-hebergement/paiements/calendrier/'.$campagne, 'PUT', ['premier_mois' => $premier, 'jour' => $jour, 'echeance' => $echeance]);
        $this->app->instance('request', $request);

        return $this->controleur()->enregistrerCalendrier($request, $campagne);
    }

    public function test_onglet_calendrier_des_loyers_propose_puis_enregistre(): void
    {
        $this->travelTo('2026-11-20 10:00:00');

        // Pas encore configuré : proposition de 10 mois à partir de septembre de l'année de début, échéance le 10.
        $props = $this->page('index', 'Admin/Hebergement/Paiements/Index', ['onglet' => 'calendrier']);
        $this->assertSame('calendrier', $props['onglet']);
        $this->assertFalse($props['calendrier']['configure']);
        $this->assertSame(['premier_mois' => '2026-09', 'jour' => 10, 'echeance' => 'mois_loyer'], $props['calendrier']['regle']);
        $this->assertCount(10, $props['calendrier']['mois']);
        $this->assertSame(['mois' => '2026-09', 'echeance' => '2026-09-10', 'libelle' => 'septembre 2026', 'paye' => false], $props['calendrier']['mois'][0]);
        $this->assertSame('2027-06', $props['calendrier']['mois'][9]['mois']);
        $this->assertNull($props['kpis']['prochaine_echeance']);
        // Sans administrateur connecté, pas d'enregistrement proposé.
        $this->assertNull($props['urls']['calendrier']);

        // Seul le premier mois se saisit : les 10 mois se suivent.
        $reponse = $this->enregistrerCalendrier();
        $this->assertStringContainsString('onglet=calendrier', $reponse->getTargetUrl());
        $this->assertSame($this->calendrierSaisi(), \App\Models\Campagne::find('k1')->calendrier_loyer);

        // Enregistré : la prochaine échéance remplace l'indicateur des pénalités ; la règle est relue pour le formulaire.
        $props = $this->page('index', 'Admin/Hebergement/Paiements/Index');
        $this->assertTrue($props['calendrier']['configure']);
        $this->assertSame(['premier_mois' => '2026-10', 'jour' => 5, 'echeance' => 'mois_loyer'], $props['calendrier']['regle']);
        $this->assertSame(['2026-10', '2027-07'], [$props['calendrier']['mois'][0]['mois'], $props['calendrier']['mois'][9]['mois']]);
        $this->assertSame(['date' => '2026-12-05', 'mois' => 'décembre 2026'], $props['kpis']['prochaine_echeance']);
        // « Toutes les campagnes » : pas de calendrier.
        $this->assertNull($this->page('index', 'Admin/Hebergement/Paiements/Index', ['campagne' => 'toutes'])['calendrier']);
    }

    public function test_calendrier_des_loyers_echeance_le_jour_du_mois_suivant(): void
    {
        // Le 31 du mois suivant : ramené au dernier jour des mois courts (loyer de janvier 2027 → 28/02/2027).
        $this->enregistrerCalendrier('2026-10', 31, 'mois_suivant');

        $calendrier = \App\Models\Campagne::find('k1')->calendrier_loyer;
        $this->assertSame(['mois' => '2026-10', 'echeance' => '2026-11-30'], $calendrier[0]);
        $this->assertSame(['mois' => '2027-01', 'echeance' => '2027-02-28'], $calendrier[3]);
        $this->assertSame(['mois' => '2027-07', 'echeance' => '2027-08-31'], $calendrier[9]);
        $this->assertSame(['premier_mois' => '2026-10', 'jour' => 31, 'echeance' => 'mois_suivant'], app(\App\Services\CalendrierLoyer::class)->regle(\App\Models\Campagne::find('k1')));
    }

    public function test_calendrier_des_loyers_saisie_controlee(): void
    {
        try {
            $this->enregistrerCalendrier('2026-13', 0, 'autre');
            $this->fail('Une saisie invalide doit être refusée.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertEqualsCanonicalizing(['premier_mois', 'jour', 'echeance'], array_keys($e->errors()));
        }
        $this->assertNull(\App\Models\Campagne::find('k1')->calendrier_loyer);
    }

    public function test_calendrier_des_loyers_garde_les_mois_payes_et_reporte_les_echeances(): void
    {
        DB::table('loyers')->insert([
            // Septembre 2026 payé ; décembre 2026 facturé, non soldé ; loyer d'une autre campagne (a0) intact.
            ['id_loyer' => 'l-sept', 'assignation_id' => 'a1', 'mois_facturation' => 9, 'annee_facturation' => 2026, 'total_paye' => 12000, 'solde_restant' => 0, 'date_limit_paiement' => '2026-09-10 23:59:59'],
            ['id_loyer' => 'l-dec', 'assignation_id' => 'a1', 'mois_facturation' => 12, 'annee_facturation' => 2026, 'total_paye' => 0, 'solde_restant' => 12000, 'date_limit_paiement' => '2026-12-10 23:59:59'],
            ['id_loyer' => 'l-autre', 'assignation_id' => 'a0', 'mois_facturation' => 12, 'annee_facturation' => 2026, 'total_paye' => 0, 'solde_restant' => 12000, 'date_limit_paiement' => '2026-12-10 23:59:59'],
        ]);

        // Un calendrier qui commence en octobre retirerait septembre, déjà payé.
        try {
            $this->enregistrerCalendrier('2026-10');
            $this->fail('Un mois déjà payé doit rester dans le calendrier.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertSame(['premier_mois'], array_keys($e->errors()));
            $this->assertStringContainsString('septembre 2026', $e->errors()['premier_mois'][0]);
        }

        // À partir de septembre, échéance le 15 : la date limite du loyer de décembre non soldé suit.
        $this->enregistrerCalendrier('2026-09', 15);
        $this->assertStringStartsWith('2026-12-15', (string) DB::table('loyers')->where('id_loyer', 'l-dec')->value('date_limit_paiement'));
        $this->assertStringStartsWith('2026-09-10', (string) DB::table('loyers')->where('id_loyer', 'l-sept')->value('date_limit_paiement'));
        $this->assertStringStartsWith('2026-12-10', (string) DB::table('loyers')->where('id_loyer', 'l-autre')->value('date_limit_paiement'));

        $props = $this->page('index', 'Admin/Hebergement/Paiements/Index');
        $this->assertSame(['2026-09'], $props['calendrier']['mois_payes']);
    }

    public function test_recherche_propose_les_mois_restants_du_calendrier(): void
    {
        $this->enregistrer([['type_paiement' => 'loyer', 'mois' => 1]]);
        DB::table('etudiants')->where('id_etudiant', 'e1')->update(['statut_inscription' => 'residents']);
        Schema::table('assignation_chambres', fn (Blueprint $t) => $t->string('chambre_id')->nullable());
        DB::table('assignation_chambres')->where('id_assignation', 'a1')->update(['chambre_id' => 'ch1']);

        // Sans calendrier : mois consécutifs côté formulaire.
        $this->assertNull($this->rechercher()[0]['mois_calendrier']);

        // Calendrier d'octobre 2026 à juillet 2027, prochain mois à payer : décembre 2026.
        DB::table('campagne')->where('id_campagne', 'k1')->update(['calendrier_loyer' => json_encode($this->calendrierSaisi())]);
        $this->assertSame(['12-2026', '01-2027', '02-2027', '03-2027', '04-2027', '05-2027', '06-2027', '07-2027'], $this->rechercher()[0]['mois_calendrier']);
    }
}
