<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceSystemSecurity;
use App\Http\Services\Eligibilite\CriteresCampagne;
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
 * Critères d'une campagne, définis sur une seule page « Critères de la campagne » : paramétrage général, puis un niveau
 * par volet ouvert (rejet, puis classement) ; brouillon, application versionnée par jeu, désactivation, capacité
 * partagée entre volets, places réservées, droits par bloc.
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
        Schema::create('niveau_etude', function (Blueprint $t) {
            $t->string('id_niveau_etude')->primary();
            $t->string('name');
            $t->string('abreviation')->nullable();
            $t->unsignedInteger('ordre')->default(0);
            $t->timestamps();
        });
        DB::table('niveau_etude')->insert([
            ['id_niveau_etude' => 'n2', 'name' => 'Licence 2', 'abreviation' => 'L2', 'ordre' => 2],
            ['id_niveau_etude' => 'n1', 'name' => 'Licence 1', 'abreviation' => 'L1', 'ordre' => 1],
            // La Terminale n'a pas d'âge maximum : elle n'est pas proposée.
            ['id_niveau_etude' => 'n0', 'name' => 'Terminale', 'abreviation' => 'terminale', 'ordre' => 0],
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

    public function test_page_propose_le_parametrage_general_puis_les_volets(): void
    {
        $this->admin();
        $page = $this->page(route(self::PAGE, ['campagne_id' => 'k1', 'volet' => 'readmission']));

        $this->assertSame('Admin/Hebergement/Parametrage/Criteres', $page['component']);
        // Le paramétrage général d'abord, puis les deux volets ouverts par la campagne, sur la même page.
        $this->assertSame(['general', 'admission', 'readmission'], array_keys($page['props']['niveaux']));
        $this->assertSame('readmission', $page['props']['niveau']);

        $general = $page['props']['niveaux']['general'];
        $this->assertSame(['credits', 'rang_filiere', 'clubs', 'cas_sociaux', 'comite', 'age_max'], array_keys($general['regles']));
        $this->assertSame(100, $general['regles']['credits']['seuil_defaut']);
        $this->assertSame(5, $general['regles']['rang_filiere']['n']);
        $this->assertSame(3, $general['regles']['clubs']['n']);
        $this->assertSame(10, $general['regles']['cas_sociaux']['taux']);
        $this->assertSame(10, $general['regles']['comite']['places']);
        $this->assertSame([], $general['places']);
        $this->assertFalse($general['droits']['voirPlaces']);
        $this->assertSame(['n1', 'n2'], array_column($general['niveauxEtude'], 'value'));
        $this->assertSame(['Licence 1', 'Licence 2'], array_column($general['niveauxEtude'], 'label'));
        $this->assertNotEmpty($general['filieres']);

        $criteres = $page['props']['niveaux']['readmission'];
        $this->assertSame('modele', $criteres['source']);
        $this->assertNull($criteres['enVigueur']);
        // Rejet (loyers, crédits, conduite, âge) puis classement (boursiers, puis le plus jeune).
        $this->assertSame(['calendrier', 'recevabilite', 'readmission', 'classement'], array_keys($criteres['regles']));
        $this->assertSame(['loyers', 'credits_actif', 'conduite', 'age_actif', 'report_places'], array_keys($criteres['regles']['readmission']));
        $this->assertSame(10, $criteres['regles']['readmission']['loyers']['mois_exiges']);
        $this->assertSame('categorie', $criteres['regles']['classement']['priorite_boursiers']);
        // Le statut de boursier déclaré suffit ; les préalables sont contrôlés à la clôture des dépôts.
        $this->assertSame('declare', $criteres['regles']['classement']['source_boursier']);
        $this->assertArrayNotHasKey('date_controle_prealables', $criteres['regles']['calendrier']);
        $this->assertSame(['age_plus_jeune'], $criteres['regles']['classement']['departage']);
        $this->assertSame([100, 2], array_column($criteres['places'], 'capacite'));
        $this->assertArrayNotHasKey('quotas', $criteres['places'][0]);
        // Paramétrage général rappelé dans le volet : sans école sur concours, C1 est inactive.
        $this->assertSame('a_parametrer', $criteres['general']['etat']);
        $this->assertFalse($criteres['general']['categories']['C1']['actif']);
        $this->assertTrue($criteres['general']['categories']['C6']['actif']);
        $this->assertSame(route(self::BASE.'update', ['k1', 'readmission']), $criteres['urls']['submit']);
        $this->assertSame(route(self::BASE.'appliquer', ['k1', 'readmission']), $criteres['urls']['appliquer']);
        $this->assertNull($criteres['urls']['abandonner']);
    }

    public function test_page_d_admission_a_ses_propres_criteres(): void
    {
        $this->admin();
        $criteres = $this->niveau('admission');

        $this->assertSame('admission', $criteres['volet']);
        $this->assertSame(['calendrier', 'recevabilite', 'admission', 'classement'], array_keys($criteres['regles']));
        $this->assertArrayNotHasKey('date_controle_prealables', $criteres['regles']['calendrier']);
        // Rejet : note du bac (seuil) et âge ; classement : mérite, bac hors Bondoukou, puis âge. Pas de priorité boursiers.
        $this->assertSame('moyenne_bac_desc', $criteres['regles']['admission']['merite']);
        $this->assertTrue($criteres['regles']['admission']['seuil_actif']);
        $this->assertSame('Bondoukou', $criteres['regles']['admission']['ville_bac']);
        $this->assertSame(['hors_ville_bac', 'age_plus_jeune'], $criteres['regles']['classement']['departage']);
        $this->assertArrayNotHasKey('priorite_boursiers', $criteres['regles']['classement']);
        $this->assertArrayNotHasKey('cas_sociaux', $criteres['places'][0]);
        $this->assertSame([], $criteres['filieres']);
    }

    public function test_parametrage_general_enregistre_et_applique(): void
    {
        $this->admin();

        $this->put(route(self::BASE.'update', ['k1', 'general']), $this->general(['appliquer' => true]))
            ->assertRedirect(route(self::PAGE, ['campagne_id' => 'k1', 'volet' => 'general']))
            ->assertSessionHasNoErrors();

        $version = DB::table('campagne_regles')->where('volet', 'general')->first();
        $this->assertSame('en_vigueur', $version->statut);
        $this->assertNull($version->places);
        $parametres = json_decode($version->parametres, true);
        $this->assertSame([['filiere_id' => '1', 'seuil' => 80, 'concours' => true]], $parametres['credits']['filieres']);
        // Un niveau sans âge saisi n'a pas de limite.
        $this->assertSame([['niveau' => 'n1', 'age' => 25]], $parametres['age_max']);

        // Un âge envoyé pour la Terminale est ignoré.
        $donnees = $this->general(['appliquer' => true]);
        $donnees['regles']['age_max'][] = ['niveau' => 'n0', 'age' => 22];
        $donnees['regles']['comite']['places'] = 12;
        $this->put(route(self::BASE.'update', ['k1', 'general']), $donnees)->assertSessionHasNoErrors();
        $vigueur = DB::table('campagne_regles')->where('volet', 'general')->where('statut', 'en_vigueur')->first();
        $this->assertSame([['niveau' => 'n1', 'age' => 25]], json_decode($vigueur->parametres, true)['age_max']);
        $this->assertSame(4, $parametres['rang_filiere']['n']);
        $this->assertSame(10, $parametres['comite']['places']);

        // La réadmission rappelle le paramétrage en vigueur : une école sur concours active C1.
        $readmission = $this->niveau('readmission');
        $this->assertSame('en_vigueur', $readmission['general']['etat']);
        $this->assertTrue($readmission['general']['categories']['C1']['actif']);

        // Le paramétrage général n'a pas de places.
        $this->put(route(self::BASE.'update', ['k1', 'general']), $this->general(['places' => [['cite_id' => 'filles', 'places' => 1]]]))
            ->assertSessionHasErrors('places');
    }

    public function test_les_volets_s_appliquent_apres_le_parametrage_general(): void
    {
        $this->admin();

        $this->post(route(self::BASE.'appliquer', ['k1', 'readmission']))->assertSessionHasErrors('criteres');
        $this->put(route(self::BASE.'update', ['k1', 'admission']), $this->admission(['appliquer' => true]))->assertSessionHasErrors('criteres');
        $this->assertSame(0, DB::table('campagne_regles')->count());

        $this->post(route(self::BASE.'appliquer', ['k1', 'general']))->assertSessionHas('success');
        $this->post(route(self::BASE.'appliquer', ['k1', 'readmission']))->assertSessionHas('success');
        $this->assertSame(['general', 'readmission'], DB::table('campagne_regles')->where('statut', 'en_vigueur')->orderBy('volet')->pluck('volet')->all());
    }

    public function test_priorite_aux_boursiers_oui_ou_non(): void
    {
        $this->appliquerGeneral();
        $route = route(self::BASE.'update', ['k1', 'readmission']);
        $classement = fn () => json_decode(DB::table('campagne_regles')->where('volet', 'readmission')->value('parametres'), true)['classement'];

        $payload = $this->readmission(['appliquer' => false]);
        $payload['regles']['classement']['priorite_boursiers'] = 'aucune';
        $this->put($route, $payload)->assertSessionHasNoErrors();
        $this->assertSame(['aucune', 'declare'], [$classement()['priorite_boursiers'], $classement()['source_boursier']]);

        // L'ancienne option « à mérite égal » n'est plus proposée.
        $payload['regles']['classement']['priorite_boursiers'] = 'departage';
        $this->put($route, $payload)->assertSessionHasErrors('regles.classement.priorite_boursiers');
    }

    public function test_desactivation_de_criteres(): void
    {
        $this->appliquerGeneral();
        $donnees = $this->readmission(['appliquer' => true]);
        $donnees['regles']['readmission']['credits_actif'] = false;
        $donnees['regles']['readmission']['conduite']['actif'] = false;
        $donnees['regles']['readmission']['loyers']['actif'] = false;
        $donnees['regles']['readmission']['age_actif'] = false;
        $donnees['regles']['classement']['departage'] = ['excellence', 'age_plus_jeune'];

        $this->put(route(self::BASE.'update', ['k1', 'readmission']), $donnees)->assertSessionHasNoErrors();
        $parametres = json_decode(DB::table('campagne_regles')->where('volet', 'readmission')->value('parametres'), true);
        $this->assertFalse($parametres['readmission']['credits_actif']);
        $this->assertFalse($parametres['readmission']['conduite']['actif']);
        $this->assertFalse($parametres['readmission']['loyers']['actif']);
        $this->assertFalse($parametres['readmission']['age_actif']);
        $this->assertSame(['excellence', 'age_plus_jeune'], $parametres['classement']['departage']);

        // Au moins un critère de départage reste actif.
        $donnees['regles']['classement']['departage'] = [];
        $this->put(route(self::BASE.'update', ['k1', 'readmission']), $donnees)->assertSessionHasErrors('regles.classement.departage');
    }

    public function test_note_de_conduite_minimale(): void
    {
        $this->admin();
        $route = route(self::BASE.'update', ['k1', 'readmission']);

        // Exigée quand « Conduite en cité » est actif, bornée par la note initiale (20 sans paramétrage enregistré).
        $donnees = $this->readmission(['appliquer' => false]);
        $donnees['regles']['readmission']['conduite']['note_min'] = null;
        $this->put($route, $donnees)->assertSessionHasErrors('regles.readmission.conduite.note_min');
        $donnees['regles']['readmission']['conduite']['note_min'] = 21;
        $this->put($route, $donnees)->assertSessionHasErrors('regles.readmission.conduite.note_min');

        // Facultative quand le critère est désactivé.
        $donnees['regles']['readmission']['conduite'] = ['actif' => false, 'note_min' => null];
        $this->put($route, $donnees)->assertSessionHasNoErrors();

        // Modèle CROU : critère actif, 10 points minimum.
        $modele = app(CriteresCampagne::class)->modeleParDefaut('readmission');
        $this->assertSame(['actif' => true, 'note_min' => 10, 'sanctions' => ['suspension', 'expulsion']], $modele['readmission']['conduite']);
    }

    public function test_chaque_jeu_s_applique_et_se_versionne_separement(): void
    {
        $this->appliquerGeneral();

        $this->post(route(self::BASE.'appliquer', ['k1', 'readmission']))->assertSessionHas('success');
        $this->post(route(self::BASE.'appliquer', ['k1', 'admission']))->assertSessionHas('success');
        $versions = DB::table('campagne_regles')->where('campagne_id', 'k1')->orderBy('volet')->get();
        $this->assertSame(['admission', 'general', 'readmission'], $versions->pluck('volet')->all());
        $this->assertSame([1, 1, 1], $versions->pluck('version')->map(fn ($v) => (int) $v)->all());
        $this->assertSame(['en_vigueur', 'en_vigueur', 'en_vigueur'], $versions->pluck('statut')->all());
        $this->assertSame(10, json_decode($versions[2]->parametres, true)['readmission']['loyers']['mois_exiges']);
        $this->assertArrayNotHasKey('readmission', json_decode($versions[0]->parametres, true));
        $this->assertSame('test-admin', $versions[0]->applique_par);

        // Sans brouillon, la version en vigueur du jeu est déjà à jour.
        $this->post(route(self::BASE.'appliquer', ['k1', 'readmission']))->assertSessionHasErrors('criteres');
        $this->post(route(self::BASE.'appliquer', ['k1', 'general']))->assertSessionHasErrors('criteres');
        $this->assertSame(3, DB::table('campagne_regles')->count());
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
        $this->assertSame(['suspension', 'expulsion'], $parametres['readmission']['conduite']['sanctions']);
        $this->assertEquals(12, $parametres['readmission']['conduite']['note_min']);
        $this->assertArrayNotHasKey('categories', $parametres['readmission']);
        $places = json_decode($brouillon->places, true);
        $this->assertSame(['filles', 'garcons'], array_column($places, 'cite_id'));
        $this->assertSame(60, $places[0]['places']);
        $this->assertArrayNotHasKey('quotas', $places[0]);

        $criteres = $this->niveau('readmission');
        $this->assertSame('brouillon', $criteres['source']);
        $this->assertSame(6, $criteres['places'][0]['cas_sociaux']); // 10 % de 60 (paramétrage général)
        $this->assertNotNull($criteres['urls']['abandonner']);
        $this->assertSame([['version' => 1, 'statut' => 'brouillon']], array_map(fn ($v) => array_intersect_key($v, array_flip(['version', 'statut'])), $criteres['versions']));

        // Le brouillon est repris à la modification suivante (une seule ligne).
        $this->put(route(self::BASE.'update', ['k1', 'readmission']), $this->readmission(['appliquer' => false]))->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('campagne_regles')->count());
    }

    public function test_admission_enregistree_sans_bloc_de_readmission(): void
    {
        $this->appliquerGeneral();
        $this->put(route(self::BASE.'update', ['k1', 'admission']), $this->admission(['appliquer' => true]))->assertSessionHasNoErrors();

        $version = DB::table('campagne_regles')->where('volet', 'admission')->first();
        $this->assertSame('en_vigueur', $version->statut);
        $parametres = json_decode($version->parametres, true);
        $this->assertEquals(12.5, $parametres['admission']['seuil']);
        $this->assertSame('Abidjan', $parametres['admission']['ville_bac']);
        $this->assertSame(['age_plus_jeune', 'hors_ville_bac'], $parametres['classement']['departage']);
        $this->assertArrayNotHasKey('readmission', $parametres);
        $this->assertSame(30, json_decode($version->places, true)[0]['places']);
    }

    public function test_appliquer_un_brouillon_modifie_la_version_en_vigueur_sans_archiver(): void
    {
        $this->appliquerGeneral();
        $route = route(self::BASE.'update', ['k1', 'readmission']);
        $this->put($route, $this->readmission(['appliquer' => true]))->assertSessionHasNoErrors();
        $lignes = fn () => DB::table('campagne_regles')->where('volet', 'readmission');
        $this->assertSame('en_vigueur', $lignes()->value('statut'));

        // Aucun motif n'est demandé et rien n'est archivé : la version en vigueur est modifiée en place.
        $this->put($route, $this->readmission(['appliquer' => false]))->assertSessionHasNoErrors();
        $this->assertSame(['en_vigueur', 'brouillon'], $lignes()->orderBy('version')->pluck('statut')->all());
        $this->post(route(self::BASE.'appliquer', ['k1', 'readmission']))->assertSessionHasNoErrors();
        $this->assertSame([['version' => 1, 'statut' => 'en_vigueur']], $lignes()->get(['version', 'statut'])->map(fn ($l) => (array) $l)->all());

        $this->put($route, $this->readmission(['appliquer' => true]))->assertSessionHasNoErrors();
        $this->assertSame([['version' => 1, 'statut' => 'en_vigueur']], $lignes()->get(['version', 'statut'])->map(fn ($l) => (array) $l)->all());
    }

    public function test_capacite_partagee_et_places_reservees(): void
    {
        $this->admin();

        $this->put(route(self::BASE.'update', ['k1', 'readmission']), $this->readmission([], [['cite_id' => 'filles', 'places' => 110]]))
            ->assertSessionHasErrors('places.0.places');
        // 10 places de réadmission : 1 place de cas social + 10 places du comité dépassent.
        $this->put(route(self::BASE.'update', ['k1', 'readmission']), $this->readmission([], [['cite_id' => 'filles', 'places' => 10]]))
            ->assertSessionHasErrors('places');
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

        // Le comité ne peut pas réserver plus que les 61 places de réadmission saisies.
        $general = $this->general();
        $general['regles']['comite']['places'] = 100;
        $this->put(route(self::BASE.'update', ['k1', 'general']), $general)->assertSessionHasErrors('regles.comite.places');
    }

    public function test_criteres_invalides_refuses(): void
    {
        $this->admin();
        $admission = $this->admission();
        $admission['regles']['admission']['seuil'] = 25; // note du bac sur 20
        // Le comportement ne départage pas les nouveaux étudiants.
        $admission['regles']['classement']['departage'] = ['comportement'];
        $this->put(route(self::BASE.'update', ['k1', 'admission']), $admission)->assertSessionHasErrors([
            'regles.admission.seuil', 'regles.classement.departage.0',
        ]);
        // Un seuil actif doit être renseigné.
        $admission['regles']['admission']['merite'] = 'credits_desc';
        $admission['regles']['admission']['seuil'] = null;
        $this->put(route(self::BASE.'update', ['k1', 'admission']), $admission)->assertSessionHasErrors('regles.admission.seuil');

        $readmission = $this->readmission();
        $readmission['regles']['classement']['departage'] = ['excellence', 'excellence', 'age_plus_jeune'];
        $readmission['regles']['readmission']['loyers']['mois_exiges'] = 13;
        $this->put(route(self::BASE.'update', ['k1', 'readmission']), $readmission)->assertSessionHasErrors([
            'regles.classement.departage.0', 'regles.readmission.loyers.mois_exiges',
        ]);

        $general = $this->general();
        $general['regles']['credits']['seuil_defaut'] = 120;
        $general['regles']['credits']['filieres'][] = ['filiere_id' => '99', 'seuil' => 50, 'concours' => false];
        $general['regles']['age_max'] = [['niveau' => 'n1', 'age' => 10], ['niveau' => 'X9', 'age' => 30]];
        $this->put(route(self::BASE.'update', ['k1', 'general']), $general)->assertSessionHasErrors([
            'regles.credits.seuil_defaut', 'regles.credits.filieres.1.filiere_id', 'regles.age_max.0.age', 'regles.age_max.1.niveau',
        ]);
        $this->assertSame(0, DB::table('campagne_regles')->count());
    }

    public function test_volet_ferme_par_la_campagne(): void
    {
        $this->appliquerGeneral();
        DB::table('campagne')->where('id_campagne', 'k1')->update(['readmission' => false]);

        // Campagne d'admission seule : pas de niveau de réadmission, la page s'ouvre sur le paramétrage général.
        $page = $this->page(route(self::PAGE, ['campagne_id' => 'k1', 'volet' => 'readmission']))['props'];
        $this->assertSame(['general', 'admission'], array_keys($page['niveaux']));
        $this->assertSame('general', $page['niveau']);
        $this->put(route(self::BASE.'update', ['k1', 'readmission']), $this->readmission())->assertSessionHasErrors('criteres');
        $this->post(route(self::BASE.'appliquer', ['k1', 'readmission']))->assertSessionHasErrors('criteres');
        $this->assertSame(0, DB::table('campagne_regles')->where('volet', 'readmission')->count());

        // Jeu inconnu : route introuvable.
        $this->put('/admin/gestion-hebergement/campagnes/k1/criteres/score', $this->readmission())->assertNotFound();
    }

    public function test_chaque_bloc_exige_son_droit(): void
    {
        $this->accorder(2, ['hebergement.eligibilite.regles.view', 'hebergement.eligibilite.regles.configure']);
        $this->admin(2);
        $this->post(route(self::BASE.'appliquer', ['k1', 'general']))->assertSessionHas('success');

        // Places sans `quotas.configure` : refusé, pas ignoré.
        $this->put(route(self::BASE.'update', ['k1', 'readmission']), $this->readmission())->assertForbidden();

        $donnees = $this->readmission(['appliquer' => true]);
        unset($donnees['places']);
        $this->put(route(self::BASE.'update', ['k1', 'readmission']), $donnees)->assertSessionHasNoErrors();
        $this->assertSame('en_vigueur', DB::table('campagne_regles')->where('volet', 'readmission')->value('statut'));

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
        $this->post(route(self::BASE.'appliquer', ['k1', 'general']))->assertForbidden();
        $this->post(route(self::BASE.'appliquer', ['k1', 'admission']))->assertForbidden();
    }

    /** @dataProvider statutsFiges */
    public function test_campagne_active_ou_terminee_figee(string $statut, string $verrou): void
    {
        $this->admin();
        DB::table('campagne')->where('id_campagne', 'k1')->update(['statut' => $statut]);

        $this->post(route(self::BASE.'appliquer', ['k1', 'general']))->assertSessionHasErrors('criteres');
        $this->post(route(self::BASE.'appliquer', ['k1', 'readmission']))->assertSessionHasErrors('criteres');
        $this->put(route(self::BASE.'update', ['k1', 'readmission']), $this->readmission(['appliquer' => false]))->assertSessionHasErrors('criteres');
        $this->assertSame(0, DB::table('campagne_regles')->count());
        $criteres = $this->niveau('readmission');
        $this->assertNull($criteres['urls']['submit']);
        $this->assertNull($criteres['urls']['appliquer']);
        $this->assertFalse($criteres['campagne']['modifiable']);
        $this->assertSame($verrou, $criteres['campagne']['verrou']);
        $this->assertNull($this->niveau('general')['urls']['submit']);
    }

    public static function statutsFiges(): array
    {
        return ['active' => ['active', 'en cours'], 'terminée' => ['terminer', 'terminée']];
    }

    public function test_abandon_du_brouillon(): void
    {
        $this->admin();
        $this->put(route(self::BASE.'update', ['k1', 'readmission']), $this->readmission(['appliquer' => false]))->assertSessionHasNoErrors();

        // Le brouillon d'un jeu ne touche pas les autres.
        $this->delete(route(self::BASE.'abandonner', ['k1', 'admission']))->assertSessionHasErrors('criteres');
        $this->delete(route(self::BASE.'abandonner', ['k1', 'general']))->assertSessionHasErrors('criteres');
        $this->delete(route(self::BASE.'abandonner', ['k1', 'readmission']))->assertSessionHas('success');
        $this->assertSame(0, DB::table('campagne_regles')->count());
    }

    public function test_anciennes_versions_completees_par_le_modele(): void
    {
        $this->admin();
        // Version enregistrée avant la réorganisation : clés inconnues ignorées, modèle complété.
        DB::table('campagne_regles')->insert([
            'id' => 'v-ancienne', 'campagne_id' => 'k1', 'volet' => 'admission', 'version' => 1, 'statut' => 'en_vigueur',
            'parametres' => json_encode(['admission' => ['merite' => 'rang_orientation_asc', 'handicap_visible' => 'commission'], 'classement' => ['departage' => ['eloignement'], 'priorite_boursiers' => 'categorie']]),
            'places' => json_encode([]),
        ]);

        $regles = $this->niveau('admission')['regles'];
        $this->assertSame('moyenne_bac_desc', $regles['admission']['merite']);
        $this->assertArrayNotHasKey('handicap_visible', $regles['admission']);
        $this->assertSame(['hors_ville_bac', 'age_plus_jeune'], $regles['classement']['departage']);
        $this->assertArrayNotHasKey('priorite_boursiers', $regles['classement']);
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

    private function general(array $surcharge = []): array
    {
        return array_merge([
            'regles' => [
                'credits' => ['seuil_defaut' => 100, 'filieres' => [['filiere_id' => '1', 'seuil' => 80, 'concours' => true]]],
                'rang_filiere' => ['actif' => true, 'n' => 4, 'groupe' => 'filiere_niveau'],
                'clubs' => ['actif' => true, 'n' => 3],
                'cas_sociaux' => ['actif' => true, 'taux' => 10, 'base' => 'places_readmission_cite'],
                'comite' => ['actif' => true, 'places' => 10],
                'age_max' => [['niveau' => 'n1', 'age' => 25], ['niveau' => 'n2', 'age' => null]],
            ],
            'appliquer' => false,
        ], $surcharge);
    }

    /** Administrateur connecté et paramétrage général de k1 appliqué (préalable aux volets). */
    private function appliquerGeneral(): void
    {
        $this->admin();
        $this->put(route(self::BASE.'update', ['k1', 'general']), $this->general(['appliquer' => true]))->assertSessionHasNoErrors();
    }

    private function readmission(array $surcharge = [], ?array $places = null): array
    {
        return array_merge([
            'regles' => [
                'calendrier' => ['date_liste_provisoire' => '2026-11-10', 'delai_completude_jours' => 7],
                'recevabilite' => ['internationaux' => true],
                'readmission' => [
                    'loyers' => ['actif' => true, 'mois_exiges' => 10],
                    'credits_actif' => true,
                    'conduite' => ['actif' => true, 'note_min' => 12],
                    'age_actif' => true,
                    'report_places' => 'C6_puis_admission',
                ],
                'classement' => [
                    'priorite_boursiers' => 'categorie',
                    'departage' => ['age_plus_jeune'], 'k_liste_elargie' => 2,
                ],
            ],
            'places' => $places ?? [
                ['cite_id' => 'filles', 'places' => 60],
                ['cite_id' => 'garcons', 'places' => 1],
            ],
            'appliquer' => false,
        ], $surcharge);
    }

    private function admission(array $surcharge = [], ?array $places = null): array
    {
        return array_merge([
            'regles' => [
                'calendrier' => ['date_liste_provisoire' => '2026-11-10', 'delai_completude_jours' => 7],
                'recevabilite' => ['internationaux' => true],
                'admission' => [
                    'merite' => 'moyenne_bac_desc', 'seuil_actif' => true, 'seuil' => 12.5,
                    'age_actif' => true, 'ville_bac' => 'Abidjan',
                ],
                'classement' => ['departage' => ['age_plus_jeune', 'hors_ville_bac'], 'k_liste_elargie' => 2],
            ],
            'places' => $places ?? [
                ['cite_id' => 'filles', 'places' => 30],
                ['cite_id' => 'garcons', 'places' => 1],
            ],
            'appliquer' => false,
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
