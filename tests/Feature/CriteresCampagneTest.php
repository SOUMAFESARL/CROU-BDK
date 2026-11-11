<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceSystemSecurity;
use App\Http\Middleware\LogUserActivity;
use App\Models\ConfigApp;
use App\Models\Role;
use App\Models\User;
use App\Repositories\Interfaces\ConfigAppRepositoryInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Critères d'une campagne, définis sur une seule page « Critères de la campagne », un niveau par volet ouvert :
 * brouillon, application versionnée par volet, désactivation, capacité partagée entre volets, droits par bloc.
 */
class CriteresCampagneTest extends TestCase
{
    private const BASE = 'admin.hebergement.eligibilite.criteres.';

    private const PAGE = 'admin.hebergement.parametrage-herb.criteres-campagne.index';

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->withoutMiddleware([EnforceSystemSecurity::class, LogUserActivity::class]);
        $settings = new ConfigApp(['app_name' => 'Résidence test']);
        $settings->setRelation('region', null)->setRelation('ville', null);
        $this->mock(ConfigAppRepositoryInterface::class)->shouldReceive('getConfig')->andReturn($settings);

        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
        Role::create(['id' => 1, 'name' => 'Administrateur']);
        Role::create(['id' => 2, 'name' => 'S/DAL']);
        foreach ([
            '2026_08_11_000001_create_role_permissions_tables.php',
            '2026_08_11_000002_create_modules_table.php',
            '2026_11_10_000002_add_eligibilite_permissions.php',
            '2026_11_11_000001_create_campagne_regles_table.php',
            '2026_11_11_000002_add_volet_to_campagne_regles.php',
            '2026_09_23_000003_create_campaign_scorings.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }

        Schema::create('campagne', function (Blueprint $t) {
            $t->string('id_campagne')->primary();
            $t->string('libelle')->nullable();
            $t->string('annee_campagne')->nullable();
            $t->text('description')->nullable();
            $t->string('statut')->nullable();
            foreach (['admission', 'readmission', 'internationnaux', 'redoublement', 'pmr', 'is_participate', 'is_discipline'] as $volet) {
                $t->boolean($volet)->nullable();
            }
            $t->float('moyenne_min')->nullable();
            $t->float('distance_min')->nullable();
            $t->integer('pourcentage_validate')->nullable();
            foreach (['L1', 'L2', 'L3', 'M1', 'M2', 'D1', 'D2', 'D3'] as $niveau) {
                $t->integer("age_{$niveau}_max")->nullable();
            }
            foreach (['periode_debut', 'periode_fin', 'date_echeance_demande', 'date_echeance_contrat'] as $date) {
                $t->date($date)->nullable();
            }
            $t->timestamps();
            $t->softDeletes();
        });
        (require database_path('migrations/2026_10_27_000002_add_validation_auto_to_campagne.php'))->up();
        Schema::create('document', function (Blueprint $t) {
            $t->string('id_document')->primary();
            $t->string('name');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('document_campagne', function (Blueprint $t) {
            $t->string('id_document_campagne')->primary();
            $t->string('id_campagne');
            $t->string('document_id');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('residence_campagne', function (Blueprint $t) {
            $t->string('id_residence_campagne')->primary();
            $t->string('campagne_id');
            $t->string('cite_id');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('type_cite', function (Blueprint $t) {
            $t->string('id_type_cite')->primary();
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('cite', function (Blueprint $t) {
            $t->string('id_cite')->primary();
            $t->string('name');
            $t->string('type_cite_id')->nullable();
            $t->string('statut')->default('ouvert');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('chambre', function (Blueprint $t) {
            $t->string('id_chambre')->primary();
            $t->string('numero_chambre')->nullable();
            $t->string('cite_id');
            $t->string('batiment_id')->nullable();
            $t->string('statut')->default('actif');
            $t->string('etat')->default('disponible');
            $t->integer('capacite_max')->default(2);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('filiere', function (Blueprint $t) {
            $t->increments('id_filiere');
            $t->string('code_filiere');
            $t->string('name')->nullable();
            $t->timestamps();
        });

        DB::table('type_cite')->insert([['id_type_cite' => 'f', 'name' => 'Uniquement Femme'], ['id_type_cite' => 'h', 'name' => 'Uniquement Homme']]);
        DB::table('cite')->insert([
            ['id_cite' => 'filles', 'name' => 'Cité A (filles)', 'type_cite_id' => 'f'],
            ['id_cite' => 'garcons', 'name' => 'Cité B (garçons)', 'type_cite_id' => 'h'],
        ]);
        // Cité filles : 50 chambres doubles actives = 100 places ; une chambre hors service et un gabarit sans numéro ne comptent pas.
        DB::table('chambre')->insert(collect(range(1, 50))->map(fn ($n) => ['id_chambre' => "f$n", 'numero_chambre' => (string) $n, 'cite_id' => 'filles'])->all());
        DB::table('chambre')->insert([
            ['id_chambre' => 'f-hs', 'numero_chambre' => '99', 'cite_id' => 'filles', 'etat' => 'hors_service'],
            ['id_chambre' => 'f-gab', 'numero_chambre' => null, 'cite_id' => 'filles', 'etat' => 'disponible'],
            ['id_chambre' => 'g1', 'numero_chambre' => '1', 'cite_id' => 'garcons', 'etat' => 'disponible'],
        ]);
        DB::table('filiere')->insert([['code_filiere' => 'ENSAU', 'name' => 'ENSAU'], ['code_filiere' => 'GEO', 'name' => 'Géographie']]);
        DB::table('campagne')->insert([
            'id_campagne' => 'k1', 'libelle' => 'Campagne 2026-2027', 'annee_campagne' => '2026-2027', 'statut' => 'planifier',
            'admission' => true, 'readmission' => true, 'periode_debut' => '2026-09-01', 'periode_fin' => '2026-10-31',
        ]);
        DB::table('residence_campagne')->insert([
            ['id_residence_campagne' => 'rc1', 'campagne_id' => 'k1', 'cite_id' => 'filles'],
            ['id_residence_campagne' => 'rc2', 'campagne_id' => 'k1', 'cite_id' => 'garcons'],
        ]);
    }

    public function test_page_de_readmission_propose_le_modele_crou(): void
    {
        $this->admin();
        $page = $this->page(route(self::PAGE, ['campagne_id' => 'k1', 'volet' => 'readmission']));

        $this->assertSame('Admin/Hebergement/Parametrage/Criteres', $page['component']);
        // Campagne d'admission et de réadmission : les deux niveaux sur la même page, ouverte sur le niveau demandé.
        $this->assertSame(['admission', 'readmission'], array_keys($page['props']['niveaux']));
        $this->assertSame('readmission', $page['props']['niveau']);
        $criteres = $page['props']['niveaux']['readmission'];
        $this->assertSame('modele', $criteres['source']);
        $this->assertNull($criteres['enVigueur']);
        // Un seul jeu de critères, propre au volet : pas de bloc d'admission, pas de barème séparé.
        $this->assertSame(['calendrier', 'recevabilite', 'readmission', 'classement'], array_keys($criteres['regles']));
        $this->assertSame(100, $criteres['regles']['readmission']['seuil_ue']);
        $this->assertSame(['age_plus_jeune', 'excellence', 'comportement'], $criteres['regles']['classement']['departage']);
        $this->assertSame([100, 2], array_column($criteres['places'], 'capacite'));
        $this->assertSame(['Uniquement Femme', 'Uniquement Homme'], array_column($criteres['places'], 'public'));
        $this->assertSame(['C1' => null, 'C3' => null, 'C5' => null], $criteres['places'][0]['quotas']);
        $this->assertSame(route(self::BASE.'update', ['k1', 'readmission']), $criteres['urls']['submit']);
        $this->assertSame(route(self::BASE.'appliquer', ['k1', 'readmission']), $criteres['urls']['appliquer']);
        $this->assertNull($criteres['urls']['abandonner']);
        $this->assertNotEmpty($criteres['filieres']);
    }

    public function test_page_d_admission_a_ses_propres_criteres(): void
    {
        $this->admin();
        $criteres = $this->niveau('admission');

        $this->assertSame('admission', $criteres['volet']);
        $this->assertSame(['calendrier', 'recevabilite', 'admission', 'classement'], array_keys($criteres['regles']));
        $this->assertArrayNotHasKey('date_controle_prealables', $criteres['regles']['calendrier']);
        $this->assertSame('moyenne_bac_desc', $criteres['regles']['admission']['merite']);
        // Admission : éloignement puis âge ; pas de comportement.
        $this->assertSame(['eloignement', 'age_plus_jeune'], $criteres['regles']['classement']['departage']);
        $this->assertArrayNotHasKey('quotas', $criteres['places'][0]);
        $this->assertSame([], $criteres['filieres']);
    }

    public function test_desactivation_de_criteres(): void
    {
        $this->admin();
        $donnees = $this->readmission(['appliquer' => true]);
        $donnees['regles']['readmission']['ue_actif'] = false;
        $donnees['regles']['readmission']['concours_actif'] = false;
        $donnees['regles']['readmission']['loyers']['actif'] = false;
        $donnees['regles']['readmission']['categories']['C3']['actif'] = false;
        $donnees['regles']['classement']['departage'] = ['excellence', 'age_plus_jeune'];

        $this->put(route(self::BASE.'update', ['k1', 'readmission']), $donnees)->assertSessionHasNoErrors();
        $parametres = json_decode(DB::table('campagne_regles')->value('parametres'), true);
        $this->assertFalse($parametres['readmission']['ue_actif']);
        $this->assertFalse($parametres['readmission']['concours_actif']);
        $this->assertFalse($parametres['readmission']['loyers']['actif']);
        $this->assertFalse($parametres['readmission']['categories']['C3']['actif']);
        $this->assertSame(['excellence', 'age_plus_jeune'], $parametres['classement']['departage']);

        // Au moins un critère de départage reste actif.
        $donnees['regles']['classement']['departage'] = [];
        $donnees['motif'] = 'Test';
        $this->put(route(self::BASE.'update', ['k1', 'readmission']), $donnees)->assertSessionHasErrors('regles.classement.departage');
    }

    public function test_chaque_volet_s_applique_et_se_versionne_separement(): void
    {
        $this->admin();

        $this->post(route(self::BASE.'appliquer', ['k1', 'readmission']))->assertSessionHas('success');
        $this->post(route(self::BASE.'appliquer', ['k1', 'admission']))->assertSessionHas('success');
        $versions = DB::table('campagne_regles')->where('campagne_id', 'k1')->orderBy('volet')->get();
        $this->assertSame(['admission', 'readmission'], $versions->pluck('volet')->all());
        $this->assertSame([1, 1], $versions->pluck('version')->map(fn ($v) => (int) $v)->all());
        $this->assertSame(['en_vigueur', 'en_vigueur'], $versions->pluck('statut')->all());
        $this->assertSame(80, json_decode($versions[1]->parametres, true)['readmission']['seuil_concours']);
        $this->assertArrayNotHasKey('readmission', json_decode($versions[0]->parametres, true));
        $this->assertSame('test-admin', $versions[0]->applique_par);

        // Sans brouillon, la version en vigueur du volet est déjà à jour.
        $this->post(route(self::BASE.'appliquer', ['k1', 'readmission']))->assertSessionHasErrors('criteres');
        $this->assertSame(2, DB::table('campagne_regles')->count());
    }

    public function test_modification_enregistree_en_brouillon(): void
    {
        $this->admin();

        $this->put(route(self::BASE.'update', ['k1', 'readmission']), $this->readmission(['appliquer' => false]))
            ->assertRedirect(route(self::PAGE, ['campagne_id' => 'k1', 'volet' => 'readmission']))
            ->assertSessionHasNoErrors();

        $brouillon = DB::table('campagne_regles')->where('campagne_id', 'k1')->first();
        $this->assertSame(['brouillon', 'readmission'], [$brouillon->statut, $brouillon->volet]);
        $parametres = json_decode($brouillon->parametres, true);
        $this->assertSame(4, $parametres['readmission']['categories']['C2']['n']);
        $this->assertSame(['1'], $parametres['readmission']['filieres_concours']);
        $this->assertSame(['rang_pv_asc'], $parametres['readmission']['categories']['C2']['merite']);
        $places = json_decode($brouillon->places, true);
        $this->assertSame(['filles', 'garcons'], array_column($places, 'cite_id'));
        $this->assertSame(60, $places[0]['places']);
        $this->assertSame(10, $places[0]['quotas']['C1']);

        $criteres = $this->niveau('readmission');
        $this->assertSame('brouillon', $criteres['source']);
        $this->assertSame(6, $criteres['places'][0]['cas_sociaux']); // 10 % de 60
        $this->assertSame(60 - 10 - 6 - 6 - 5, $criteres['places'][0]['solde']);
        $this->assertNotNull($criteres['urls']['abandonner']);
        $this->assertSame([['version' => 1, 'statut' => 'brouillon']], array_map(fn ($v) => array_intersect_key($v, array_flip(['version', 'statut'])), $criteres['versions']));

        // Le brouillon est repris à la modification suivante (une seule ligne).
        $this->put(route(self::BASE.'update', ['k1', 'readmission']), $this->readmission(['appliquer' => false]))->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('campagne_regles')->count());
    }

    public function test_admission_enregistree_sans_bloc_de_readmission(): void
    {
        $this->admin();
        $this->put(route(self::BASE.'update', ['k1', 'admission']), $this->admission(['appliquer' => true]))->assertSessionHasNoErrors();

        $version = DB::table('campagne_regles')->where('volet', 'admission')->first();
        $this->assertSame('en_vigueur', $version->statut);
        $parametres = json_decode($version->parametres, true);
        $this->assertSame([150, 400], $parametres['admission']['eloignement']['tranches_km']);
        $this->assertSame(['age_plus_jeune', 'eloignement'], $parametres['classement']['departage']);
        $this->assertSame(30, json_decode($version->places, true)[0]['places']);
        $this->assertArrayNotHasKey('quotas', json_decode($version->places, true)[0]);
    }

    public function test_remplacer_les_criteres_en_vigueur_exige_un_motif(): void
    {
        $this->admin();
        $route = route(self::BASE.'update', ['k1', 'readmission']);
        $this->put($route, $this->readmission(['appliquer' => true]))->assertSessionHasNoErrors();
        $this->assertSame('en_vigueur', DB::table('campagne_regles')->value('statut'));

        // Sans motif : rien n'est enregistré (ni brouillon, ni nouvelle version).
        $this->put($route, $this->readmission(['appliquer' => true]))->assertSessionHasErrors('motif');
        $this->assertSame(1, DB::table('campagne_regles')->count());

        $this->put($route, $this->readmission(['appliquer' => true, 'motif' => 'Quotas du comité de direction']))->assertSessionHasNoErrors();
        $this->assertSame(['archivee', 'en_vigueur'], DB::table('campagne_regles')->orderBy('version')->pluck('statut')->all());
        $this->assertSame('Quotas du comité de direction', DB::table('campagne_regles')->where('version', 2)->value('motif'));
    }

    public function test_capacite_partagee_entre_les_deux_volets_et_sous_quotas(): void
    {
        $this->admin();

        $this->put(route(self::BASE.'update', ['k1', 'readmission']), $this->readmission([], [['cite_id' => 'filles', 'places' => 110]]))
            ->assertSessionHasErrors('places.0.places');
        $this->put(route(self::BASE.'update', ['k1', 'readmission']), $this->readmission([], [['cite_id' => 'filles', 'places' => 20, 'quotas' => ['C1' => 10, 'C3' => 8, 'C5' => 5]]]))
            ->assertSessionHasErrors('places.0.quotas');
        $this->put(route(self::BASE.'update', ['k1', 'readmission']), $this->readmission([], [['cite_id' => 'inconnue', 'places' => 1]]))
            ->assertSessionHasErrors('places');
        $this->assertSame(0, DB::table('campagne_regles')->count());

        // 60 places de réadmission : l'admission ne peut plus en prendre que 40 dans la cité des filles.
        $this->put(route(self::BASE.'update', ['k1', 'readmission']), $this->readmission())->assertSessionHasNoErrors();
        $this->put(route(self::BASE.'update', ['k1', 'admission']), $this->admission([], [['cite_id' => 'filles', 'places' => 50]]))
            ->assertSessionHasErrors('places.0.places');
        $admission = $this->niveau('admission');
        $this->assertSame(60, $admission['places'][0]['autre_volet']);
        $this->put(route(self::BASE.'update', ['k1', 'admission']), $this->admission([], [['cite_id' => 'filles', 'places' => 40]]))
            ->assertSessionHasNoErrors();
    }

    public function test_criteres_invalides_refuses(): void
    {
        $this->admin();
        $admission = $this->admission();
        $admission['regles']['admission']['eloignement']['tranches_km'] = [300, 100];
        // Le comportement ne départage pas les nouveaux étudiants.
        $admission['regles']['classement']['departage'] = ['comportement'];
        $this->put(route(self::BASE.'update', ['k1', 'admission']), $admission)->assertSessionHasErrors([
            'regles.admission.eloignement.tranches_km', 'regles.classement.departage.0',
        ]);

        $readmission = $this->readmission();
        $readmission['regles']['classement']['departage'] = ['excellence', 'excellence', 'age_plus_jeune'];
        $readmission['regles']['readmission']['seuil_ue'] = 120;
        $this->put(route(self::BASE.'update', ['k1', 'readmission']), $readmission)->assertSessionHasErrors([
            'regles.classement.departage.0', 'regles.readmission.seuil_ue',
        ]);
        $this->assertSame(0, DB::table('campagne_regles')->count());
    }

    public function test_volet_ferme_par_la_campagne(): void
    {
        $this->admin();
        DB::table('campagne')->where('id_campagne', 'k1')->update(['readmission' => false]);

        // Campagne d'admission seule : le niveau de réadmission n'apparaît pas, la page s'ouvre sur l'admission.
        $page = $this->page(route(self::PAGE, ['campagne_id' => 'k1', 'volet' => 'readmission']))['props'];
        $this->assertSame(['admission'], array_keys($page['niveaux']));
        $this->assertSame('admission', $page['niveau']);
        $this->put(route(self::BASE.'update', ['k1', 'readmission']), $this->readmission())->assertSessionHasErrors('criteres');
        $this->post(route(self::BASE.'appliquer', ['k1', 'readmission']))->assertSessionHasErrors('criteres');
        $this->assertSame(0, DB::table('campagne_regles')->count());

        // Volet inconnu : route introuvable.
        $this->put('/admin/gestion-hebergement/campagnes/k1/criteres/score', $this->readmission())->assertNotFound();
    }

    public function test_chaque_bloc_exige_son_droit(): void
    {
        $this->accorder(2, ['hebergement.eligibilite.regles.view', 'hebergement.eligibilite.regles.configure']);
        $this->admin(2);

        // Places sans `quotas.configure` : refusé, pas ignoré.
        $this->put(route(self::BASE.'update', ['k1', 'readmission']), $this->readmission())->assertForbidden();

        $donnees = $this->readmission(['appliquer' => true]);
        unset($donnees['places']);
        $this->put(route(self::BASE.'update', ['k1', 'readmission']), $donnees)->assertSessionHasNoErrors();
        $this->assertSame('en_vigueur', DB::table('campagne_regles')->value('statut'));

        $criteres = $this->niveau('readmission');
        $this->assertFalse($criteres['droits']['voirPlaces']);
        $this->assertSame([], $criteres['places']);
        $this->assertNull($criteres['enVigueur']['places']);
        $this->assertNotNull($criteres['enVigueur']['regles']);
        $this->assertSame('en_vigueur', $criteres['source']);
    }

    public function test_profil_sans_droit_refuse(): void
    {
        $this->admin(2);
        $this->get(route(self::PAGE, ['campagne_id' => 'k1']))->assertForbidden();
        $this->post(route(self::BASE.'appliquer', ['k1', 'admission']))->assertForbidden();
    }

    public function test_campagne_terminee_figee(): void
    {
        $this->admin();
        DB::table('campagne')->where('id_campagne', 'k1')->update(['statut' => 'terminer']);

        $this->post(route(self::BASE.'appliquer', ['k1', 'readmission']))->assertSessionHasErrors('criteres');
        $criteres = $this->niveau('readmission');
        $this->assertNull($criteres['urls']['submit']);
        $this->assertNull($criteres['urls']['appliquer']);
    }

    public function test_abandon_du_brouillon(): void
    {
        $this->admin();
        $this->put(route(self::BASE.'update', ['k1', 'readmission']), $this->readmission(['appliquer' => false]))->assertSessionHasNoErrors();

        // Le brouillon d'un volet ne touche pas l'autre.
        $this->delete(route(self::BASE.'abandonner', ['k1', 'admission']))->assertSessionHasErrors('criteres');
        $this->delete(route(self::BASE.'abandonner', ['k1', 'readmission']))->assertSessionHas('success');
        $this->assertSame(0, DB::table('campagne_regles')->count());
    }

    public function test_les_versions_communes_sont_scindees_par_volet(): void
    {
        Schema::drop('campagne_regles');
        (require database_path('migrations/2026_11_11_000001_create_campagne_regles_table.php'))->up();
        DB::table('campagne_regles')->insert([
            'id' => 'v1', 'campagne_id' => 'k1', 'version' => 1, 'statut' => 'en_vigueur', 'parametres' => '{"readmission":{"seuil_ue":90}}',
            'places' => json_encode([['cite_id' => 'filles', 'cite' => 'Cité A', 'public' => null, 'capacite' => 100, 'readmission' => 60, 'admission' => 40, 'quotas' => ['C1' => 10, 'C3' => null, 'C5' => null]]]),
        ]);

        (require database_path('migrations/2026_11_11_000002_add_volet_to_campagne_regles.php'))->up();

        $lignes = DB::table('campagne_regles')->orderBy('volet')->get()->keyBy('volet');
        $this->assertSame(['admission', 'readmission'], $lignes->keys()->all());
        $this->assertSame(40, json_decode($lignes['admission']->places, true)[0]['places']);
        $this->assertArrayNotHasKey('quotas', json_decode($lignes['admission']->places, true)[0]);
        $this->assertSame(60, json_decode($lignes['readmission']->places, true)[0]['places']);
        $this->assertSame(10, json_decode($lignes['readmission']->places, true)[0]['quotas']['C1']);
        $this->assertSame('en_vigueur', $lignes['admission']->statut);
    }

    public function test_les_baremes_de_score_sont_desactives(): void
    {
        DB::table('campaign_scorings')->insert([
            'campagne_id' => 'k1', 'weights' => '{}', 'points' => '{}', 'minimum_score' => 50, 'pmr_first' => false, 'active' => true, 'created_by' => 'test-admin',
        ]);

        (require database_path('migrations/2026_11_11_000003_desactiver_baremes_de_score.php'))->up();

        $this->assertSame(0, (int) DB::table('campaign_scorings')->where('active', true)->count());
        $this->assertSame(1, DB::table('campaign_scorings')->count());
    }

    private function readmission(array $surcharge = [], ?array $places = null): array
    {
        return array_merge([
            'regles' => [
                'calendrier' => ['date_controle_prealables' => '2026-11-05', 'date_liste_provisoire' => '2026-11-10', 'delai_completude_jours' => 7],
                'recevabilite' => ['internationaux' => true],
                'readmission' => [
                    'seuil_ue' => 100, 'seuil_concours' => 80, 'filieres_concours' => ['1'],
                    'loyers' => ['actif' => true, 'mois_exiges' => 10, 'tolerance_fcfa' => 0],
                    'categories' => [
                        'C1' => ['actif' => true],
                        'C2' => ['actif' => true, 'n' => 4, 'groupe' => 'filiere_niveau'],
                        'C3' => ['actif' => true, 'n' => 3],
                        'C4' => ['actif' => true, 'taux' => 10, 'base' => 'places_readmission_cite'],
                        'C5' => ['actif' => true, 'prealables' => true],
                        'C6' => ['actif' => true],
                    ],
                    'report_places' => 'C6_puis_admission',
                    'age_max' => null,
                ],
                'classement' => [
                    'priorite_boursiers' => 'categorie', 'source_boursier' => 'justifie',
                    'departage' => ['age_plus_jeune', 'excellence', 'comportement'], 'k_liste_elargie' => 2,
                ],
            ],
            'places' => $places ?? [
                ['cite_id' => 'filles', 'places' => 60, 'quotas' => ['C1' => 10, 'C3' => 6, 'C5' => 5]],
                ['cite_id' => 'garcons', 'places' => 1, 'quotas' => ['C1' => null, 'C3' => null, 'C5' => null]],
            ],
            'appliquer' => false,
            'motif' => null,
        ], $surcharge);
    }

    private function admission(array $surcharge = [], ?array $places = null): array
    {
        return array_merge([
            'regles' => [
                'calendrier' => ['date_liste_provisoire' => '2026-11-10', 'delai_completude_jours' => 7],
                'recevabilite' => ['internationaux' => true],
                'admission' => [
                    'merite' => 'moyenne_bac_desc', 'eloignement' => ['tranches_km' => [150, 400]],
                    'handicap_visible' => 'commission', 'age_max_L1' => 25,
                ],
                'classement' => [
                    'priorite_boursiers' => 'departage', 'source_boursier' => 'justifie',
                    'departage' => ['age_plus_jeune', 'eloignement'], 'k_liste_elargie' => 2,
                ],
            ],
            'places' => $places ?? [
                ['cite_id' => 'filles', 'places' => 30],
                ['cite_id' => 'garcons', 'places' => 1],
            ],
            'appliquer' => false,
            'motif' => null,
        ], $surcharge);
    }

    private function admin(int $role = 1): void
    {
        $this->actingAs(new User(['id_user' => 'test-admin', 'name' => 'Admin Test', 'role_id' => $role]), 'admin');
    }

    private function accorder(int $role, array $permissions): void
    {
        foreach ($permissions as $slug) {
            DB::table('role_permission')->insert(['role_id' => $role, 'permission_id' => DB::table('permissions')->where('slug', $slug)->value('id')]);
        }
    }

    /** Page Inertia rendue (lue dans `data-page`). */
    /** Niveau d'un volet sur la page « Critères de la campagne » de k1. */
    private function niveau(string $volet): ?array
    {
        return $this->page(route(self::PAGE, ['campagne_id' => 'k1', 'volet' => $volet]))['props']['niveaux'][$volet] ?? null;
    }

    private function page(string $url): array
    {
        $this->withoutVite();
        $html = $this->get($url)->assertOk()->getContent();
        preg_match('/data-page="([^"]+)"/', $html, $matches);

        return json_decode(html_entity_decode($matches[1] ?? ''), true) ?? [];
    }
}
