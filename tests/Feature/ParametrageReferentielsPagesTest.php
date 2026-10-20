<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceSystemSecurity;
use App\Http\Middleware\LogUserActivity;
use App\Models\ConfigApp;
use App\Models\Role;
use App\Models\User;
use App\Repositories\Interfaces\ConfigAppRepositoryInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

/** Pages React du paramétrage : départements, filières, pays, régions, villes, niveaux d'étude, types d'utilisateur et configuration. */
class ParametrageReferentielsPagesTest extends TestCase
{
    private const BASE = 'admin.parametrage.';

    private ConfigApp $settings;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->withoutMiddleware([EnforceSystemSecurity::class, LogUserActivity::class]);
        $this->settings = new ConfigApp([
            'app_name' => 'CROU Bondoukou', 'email_enabled' => true, 'mail_host' => 'smtp.test', 'mail_password' => 'motdepasse-secret',
            'sms_enabled' => true, 'sms_api_key' => 'cle-secrete', 'sms_api_url' => 'https://sms.test/send', 'sms_sender' => 'CROU',
        ]);
        $this->settings->syncOriginal();
        $this->settings->setRelation('region', null)->setRelation('ville', null);
        $this->mock(ConfigAppRepositoryInterface::class)->shouldReceive('getConfig')->andReturn($this->settings)->byDefault();

        Schema::create('roles', function (Blueprint $t) { $t->id(); $t->string('name'); $t->timestamps(); });
        Role::create(['id' => 1, 'name' => 'Administrateur']);
        Role::create(['id' => 2, 'name' => 'Agent']);
        foreach (['2026_08_11_000001_create_role_permissions_tables.php', '2026_08_11_000002_create_modules_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }

        Schema::create('departement', function (Blueprint $t) {
            $t->increments('id_departement'); $t->string('code_departement'); $t->string('name'); $t->string('abreviation')->nullable(); $t->text('description')->nullable();
            $t->string('created_by')->nullable(); $t->string('updated_by')->nullable(); $t->timestamps();
        });
        Schema::create('filiere', function (Blueprint $t) {
            $t->increments('id_filiere'); $t->string('code_filiere'); $t->string('name')->nullable(); $t->string('description')->nullable(); $t->string('departement_code')->nullable();
            $t->string('created_by')->nullable(); $t->string('updated_by')->nullable(); $t->timestamps();
        });
        Schema::create('type_utilisateur', function (Blueprint $t) { $t->increments('id'); $t->string('name'); $t->timestamps(); });
        Schema::create('users', function (Blueprint $t) {
            $t->string('id_user')->primary(); $t->string('name')->nullable(); $t->integer('type_utilisateur_id')->nullable();
            $t->integer('region_id')->nullable(); $t->integer('ville_id')->nullable(); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('etudiants', function (Blueprint $t) {
            $t->string('id_etudiant')->primary(); $t->string('departement_code')->nullable();
            $t->integer('countrie_id')->nullable(); $t->integer('regions_id')->nullable(); $t->integer('ville_id')->nullable(); $t->timestamps(); $t->softDeletes();
        });
        // La filière et les niveaux de l'étudiant sont portés par sa demande (comme en base réelle).
        Schema::create('demande', function (Blueprint $t) {
            $t->string('id_demande')->primary(); $t->string('etudiant_id'); $t->string('filiere_code')->nullable();
            $t->string('niveau_etude_actuel_code')->nullable(); $t->string('niveau_etude_precedent_code')->nullable(); $t->timestamps();
        });
        Schema::create('countrie', function (Blueprint $t) { $t->increments('id'); $t->string('name')->nullable(); $t->string('code')->nullable(); $t->timestamps(); });
        Schema::create('regions', function (Blueprint $t) { $t->increments('id'); $t->string('name'); $t->timestamps(); });
        Schema::create('villes', function (Blueprint $t) { $t->increments('id'); $t->string('name'); $t->integer('region_id'); $t->timestamps(); });
        Schema::create('campus', function (Blueprint $t) {
            $t->string('id_campus')->primary(); $t->integer('ville_id')->nullable(); $t->integer('region_id')->nullable(); $t->integer('countrie_id')->nullable(); $t->timestamps();
        });
        Schema::create('config_app', function (Blueprint $t) { $t->increments('id'); $t->string('region_id')->nullable(); $t->string('ville_id')->nullable(); $t->timestamps(); });
        Schema::create('niveau_etude', function (Blueprint $t) {
            $t->string('id_niveau_etude')->primary(); $t->string('name'); $t->string('abreviation')->nullable(); $t->text('description')->nullable();
            $t->string('created_by')->nullable(); $t->string('updated_by')->nullable(); $t->timestamps();
        });

        DB::table('departement')->insert([
            ['id_departement' => 1, 'code_departement' => 'D-ST', 'name' => 'Sciences et technologies', 'abreviation' => 'UFR ST'],
            ['id_departement' => 2, 'code_departement' => 'D-LL', 'name' => 'Lettres et langues', 'abreviation' => null],
        ]);
        DB::table('filiere')->insert([
            ['id_filiere' => 1, 'code_filiere' => 'F-MI', 'name' => 'Mathématiques-Informatique', 'departement_code' => 'D-ST'],
            ['id_filiere' => 2, 'code_filiere' => 'F-PC', 'name' => 'Physique-Chimie', 'departement_code' => 'D-ST'],
            ['id_filiere' => 3, 'code_filiere' => 'F-HI', 'name' => 'Histoire', 'departement_code' => null],
        ]);
        DB::table('countrie')->insert([['id' => 1, 'name' => 'Côte d\'Ivoire', 'code' => 'CI'], ['id' => 2, 'name' => 'Togo', 'code' => 'TG'], ['id' => 3, 'name' => 'Cameroun', 'code' => null]]);
        DB::table('regions')->insert([['id' => 1, 'name' => 'Région du Gontougo'], ['id' => 2, 'name' => 'District autonome d\'Abidjan'], ['id' => 3, 'name' => 'Région du Bafing']]);
        DB::table('villes')->insert([
            ['id' => 1, 'name' => 'Bondoukou', 'region_id' => 1], ['id' => 2, 'name' => 'Tanda', 'region_id' => 1], ['id' => 3, 'name' => 'Abidjan', 'region_id' => 2],
        ]);
        DB::table('campus')->insert([['id_campus' => 'c1', 'ville_id' => 1, 'region_id' => 1, 'countrie_id' => 2]]);
        DB::table('niveau_etude')->insert([
            ['id_niveau_etude' => 'n-l1', 'name' => 'Licence 1', 'abreviation' => 'L1'], ['id_niveau_etude' => 'n-l2', 'name' => 'Licence 2', 'abreviation' => 'L2'],
            ['id_niveau_etude' => 'n-m1', 'name' => 'Master 1', 'abreviation' => 'M1'],
        ]);
        DB::table('etudiants')->insert([['id_etudiant' => 'e1', 'departement_code' => 'D-ST', 'countrie_id' => 1, 'regions_id' => 2, 'ville_id' => 3]]);
        DB::table('demande')->insert([
            ['id_demande' => 'd1', 'etudiant_id' => 'e1', 'filiere_code' => 'F-MI', 'niveau_etude_actuel_code' => 'n-l2', 'niveau_etude_precedent_code' => 'n-l1'],
            ['id_demande' => 'd2', 'etudiant_id' => 'e1', 'filiere_code' => null, 'niveau_etude_actuel_code' => 'n-l2', 'niveau_etude_precedent_code' => 'n-l2'],
        ]);
        DB::table('type_utilisateur')->insert([['id' => 2, 'name' => 'Administratif'], ['id' => 3, 'name' => 'Technique']]);
        DB::table('users')->insert([['id_user' => 'u1', 'name' => 'Agent', 'type_utilisateur_id' => 2]]);
    }

    private function admin(int $role = 1): void
    {
        $this->actingAs(new User(['id_user' => 'test-admin', 'name' => 'Admin Test', 'role_id' => $role]), 'admin');
    }

    /** Page Inertia rendue (lue dans `data-page` : un middleware global reconstruit la réponse). */
    private function page(string $url): array
    {
        $this->withoutVite();
        $html = $this->get($url)->assertOk()->getContent();
        preg_match('/data-page="([^"]+)"/', $html, $matches);

        return json_decode(html_entity_decode($matches[1] ?? ''), true) ?? [];
    }

    public function test_departements_avec_usage_recherche_et_suppression_bloquee(): void
    {
        $this->admin();
        $page = $this->page(route(self::BASE.'departement.index'));

        $this->assertSame('Admin/Parametrage/Departements', $page['component']);
        $this->assertSame(['total' => 2, 'utilises' => 1, 'inutilises' => 1], $page['props']['stats']);
        $st = collect($page['props']['elements']['data'])->firstWhere('id', '1');
        $this->assertSame([2, 1, 'UFR ST'], [$st['filieres'], $st['etudiants'], $st['abreviation']]);
        $this->assertSame(route(self::BASE.'departement.delete', 1), $st['urls']['destroy']);
        $this->assertSame(['Sciences et technologies'], array_column($this->page(route(self::BASE.'departement.index', ['q' => 'ufr']))['props']['elements']['data'], 'name'));

        // Département utilisé : refusé côté serveur aussi.
        $this->delete(route(self::BASE.'departement.delete', 1))->assertSessionHas('error');
        $this->assertSame(2, DB::table('departement')->count());
        $this->delete(route(self::BASE.'departement.delete', 2))->assertSessionHas('success');
        $this->assertSame(1, DB::table('departement')->count());
    }

    public function test_departement_valide_et_cree(): void
    {
        $this->admin();
        $this->post(route(self::BASE.'departement.store'), ['name' => ''])->assertSessionHasErrors('name');
        $this->post(route(self::BASE.'departement.store'), ['name' => 'Économie', 'abreviation' => 'UFR SEG', 'description' => ''])
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $cree = DB::table('departement')->where('name', 'Économie')->first();
        $this->assertSame('UFR SEG', $cree->abreviation);
        $this->assertNotEmpty($cree->code_departement);

        $this->put(route(self::BASE.'departement.update', 2), ['name' => 'Lettres, langues et arts', 'abreviation' => 'UFR LLA'])->assertSessionHas('success');
        $this->assertSame('UFR LLA', DB::table('departement')->where('id_departement', 2)->value('abreviation'));
    }

    public function test_filieres_filtre_par_departement_et_rattachement_verifie(): void
    {
        $this->admin();
        $page = $this->page(route(self::BASE.'filiere.index'));

        $this->assertSame('Admin/Parametrage/Filieres', $page['component']);
        $this->assertSame(['total' => 3, 'utilises' => 1, 'inutilises' => 2], $page['props']['stats']);
        $this->assertSame(['D-LL', 'D-ST'], array_column($page['props']['departements'], 'code'));
        $mi = collect($page['props']['elements']['data'])->firstWhere('id', '1');
        $this->assertSame(['Sciences et technologies', 'D-ST', 1], [$mi['departement'], $mi['departement_code'], $mi['etudiants']]);

        $this->assertSame(['Histoire'], array_column($this->page(route(self::BASE.'filiere.index', ['departement' => 'aucun']))['props']['elements']['data'], 'name'));
        $this->assertCount(2, $this->page(route(self::BASE.'filiere.index', ['departement' => 'D-ST']))['props']['elements']['data']);
        // Département inconnu ignoré ; la recherche porte aussi sur le département.
        $this->assertSame('', $this->page(route(self::BASE.'filiere.index', ['departement' => 'xyz']))['props']['filters']['departement']);
        $this->assertCount(2, $this->page(route(self::BASE.'filiere.index', ['q' => 'technologies']))['props']['elements']['data']);

        $this->post(route(self::BASE.'filiere.store'), ['name' => 'Anglais', 'departement_code' => 'inconnu'])->assertSessionHasErrors('departement_code');
        $this->post(route(self::BASE.'filiere.store'), ['name' => 'Anglais', 'departement_code' => 'D-LL'])->assertSessionHasNoErrors();
        $this->assertSame('D-LL', DB::table('filiere')->where('name', 'Anglais')->value('departement_code'));

        $this->delete(route(self::BASE.'filiere.delete', 1))->assertSessionHas('error');
        $this->delete(route(self::BASE.'filiere.delete', 3))->assertSessionHas('success');
        $this->assertSame(3, DB::table('filiere')->count());
    }

    public function test_import_departements_cree_met_a_jour_et_signale_les_lignes_invalides(): void
    {
        $this->admin();
        // En-têtes accentués, séparateur « ; », encodage Windows-1252 comme un CSV enregistré par Excel.
        $csv = mb_convert_encoding("Nom;Abréviation;Description\n"
            ."Sciences et technologies;UFR ST;Sciences exactes\n"
            ."Économie et gestion;UFR SEG;\n"
            .";UFR X;Sans nom\n"
            ."lettres et langues;;\n", 'Windows-1252', 'UTF-8');
        $this->post(route(self::BASE.'departement.import'), ['fichier' => UploadedFile::fake()->createWithContent('departements.csv', $csv)])
            ->assertSessionHas('import_rapport', fn ($r) => $r['message'] === '1 ligne(s) ajoutée(s), 1 mise(s) à jour, 1 déjà à jour.'
                && count($r['lignes']) === 1 && str_starts_with($r['lignes'][0], 'Ligne 4 :'));

        $this->assertSame(3, DB::table('departement')->count());
        $this->assertSame('Sciences exactes', DB::table('departement')->where('id_departement', 1)->value('description'));
        $this->assertSame('UFR SEG', DB::table('departement')->where('name', 'Économie et gestion')->value('abreviation'));
        // Le rapport est transmis à la page pour la fenêtre des lignes refusées.
        $this->assertCount(1, $this->page(route(self::BASE.'departement.index'))['props']['import']['lignes']);

        $this->post(route(self::BASE.'departement.import'), ['fichier' => UploadedFile::fake()->createWithContent('d.csv', "sigle;description\nX;Y\n")])
            ->assertSessionHasErrors(['fichier' => 'Colonne(s) manquante(s) : nom. Téléchargez le modèle et conservez ses en-têtes.']);
        $this->post(route(self::BASE.'departement.import'), ['fichier' => UploadedFile::fake()->create('d.pdf', 10, 'application/pdf')])->assertSessionHasErrors('fichier');
    }

    public function test_import_filieres_depuis_le_modele_excel(): void
    {
        $this->admin();
        $this->get(route(self::BASE.'filiere.modele'))->assertOk()->assertDownload('modele_import_filieres.xlsx');

        // Fichier au format du modèle, relu par l'import (aller-retour .xlsx).
        $xlsx = \App\Support\SimpleXlsx::download('f.xlsx', 'Filieres', ['nom', 'departement', 'description'], [
            ['Anglais', 'lettres et langues', 'Langue anglaise'],
            ['Histoire', 'Lettres et langues', ''],   // filière existante sans département : rattachée
            ['Physique-Chimie', 'ufr st', 'PC'],      // trouvée par l'abréviation : mise à jour
            ['Biologie', 'Médecine', ''],             // département inconnu : refusée
            ['Mathématiques-Informatique', '', ''],   // rien à changer
        ])->getFile()->getPathname();
        $this->post(route(self::BASE.'filiere.import'), ['fichier' => new UploadedFile($xlsx, 'filieres.xlsx', null, null, true)])
            ->assertSessionHas('import_rapport', fn ($r) => $r['message'] === '1 ligne(s) ajoutée(s), 2 mise(s) à jour, 1 déjà à jour.'
                && $r['lignes'] === ['Ligne 5 : département « Médecine » introuvable. Créez-le ou importez d\'abord les départements.']);

        $this->assertSame('D-LL', DB::table('filiere')->where('name', 'Anglais')->value('departement_code'));
        $this->assertSame('D-LL', DB::table('filiere')->where('id_filiere', 3)->value('departement_code'));
        $this->assertSame('PC', DB::table('filiere')->where('id_filiere', 2)->value('description'));
        $this->assertSame(4, DB::table('filiere')->count());
    }

    public function test_import_refuse_sans_droit_de_creation(): void
    {
        $this->admin(2);
        $this->get(route(self::BASE.'filiere.modele'))->assertForbidden();
        $this->post(route(self::BASE.'departement.import'), ['fichier' => UploadedFile::fake()->createWithContent('d.csv', "nom\nX\n")])->assertForbidden();
        $this->assertSame(2, DB::table('departement')->count());
    }

    public function test_pays_unicite_usage_et_suppression_bloquee(): void
    {
        $this->admin();
        $page = $this->page(route(self::BASE.'countrie.index'));

        $this->assertSame('Admin/Parametrage/Pays', $page['component']);
        $this->assertSame(['total' => 3, 'utilises' => 2, 'inutilises' => 1], $page['props']['stats']);
        $togo = collect($page['props']['elements']['data'])->firstWhere('id', '2');
        $this->assertSame(['TG', 0, 1], [$togo['code'], $togo['etudiants'], $togo['campus']]);
        $this->assertSame(['Togo'], array_column($this->page(route(self::BASE.'countrie.index', ['q' => 'tg']))['props']['elements']['data'], 'name'));

        $this->post(route(self::BASE.'countrie.store'), ['name' => 'Togo'])->assertSessionHasErrors('name');
        $this->post(route(self::BASE.'countrie.store'), ['name' => 'Ghana', 'code' => 'GH'])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->put(route(self::BASE.'countrie.update', 3), ['name' => 'Cameroun', 'code' => 'CM'])->assertSessionHasNoErrors();
        $this->assertSame('CM', DB::table('countrie')->where('id', 3)->value('code'));

        $this->delete(route(self::BASE.'countrie.delete', 1))->assertSessionHas('error');
        $this->delete(route(self::BASE.'countrie.delete', 2))->assertSessionHas('error');
        $this->delete(route(self::BASE.'countrie.delete', 3))->assertSessionHas('success');
        $this->assertSame(3, DB::table('countrie')->count());
    }

    public function test_regions_comptent_villes_et_references(): void
    {
        $this->admin();
        $page = $this->page(route(self::BASE.'region.index'));

        $this->assertSame('Admin/Parametrage/Regions', $page['component']);
        $this->assertSame(['total' => 3, 'utilises' => 2, 'inutilises' => 1], $page['props']['stats']);
        $gontougo = collect($page['props']['elements']['data'])->firstWhere('id', '1');
        $this->assertSame([2, 0, 1], [$gontougo['villes'], $gontougo['etudiants'], $gontougo['autres']]);
        $this->assertSame(route(self::BASE.'ville.index'), $page['props']['urls']['villes']);

        $this->post(route(self::BASE.'region.store'), ['name' => ''])->assertSessionHasErrors('name');
        $this->put(route(self::BASE.'region.update', 3), ['name' => 'Région du Gontougo'])->assertSessionHasErrors('name');
        $this->put(route(self::BASE.'region.update', 3), ['name' => 'Région du Bafing (Touba)'])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame('Région du Bafing (Touba)', DB::table('regions')->where('id', 3)->value('name'));

        // Région avec des villes, puis région citée par un étudiant : refusées.
        $this->delete(route(self::BASE.'region.delete', 1))->assertSessionHas('error');
        DB::table('villes')->where('region_id', 2)->delete();
        $this->delete(route(self::BASE.'region.delete', 2))->assertSessionHas('error');
        $this->delete(route(self::BASE.'region.delete', 3))->assertSessionHas('success');
        $this->assertSame(2, DB::table('regions')->count());
    }

    public function test_villes_filtre_par_region_et_unicite_dans_la_region(): void
    {
        $this->admin();
        $page = $this->page(route(self::BASE.'ville.index'));

        $this->assertSame('Admin/Parametrage/Villes', $page['component']);
        $this->assertSame(['total' => 3, 'utilises' => 2, 'inutilises' => 1], $page['props']['stats']);
        $this->assertSame(['2', '3', '1'], array_column($page['props']['regions'], 'id'));
        $bondoukou = collect($page['props']['elements']['data'])->firstWhere('id', '1');
        $this->assertSame(['Région du Gontougo', '1', 0, 1], [$bondoukou['region'], $bondoukou['region_id'], $bondoukou['etudiants'], $bondoukou['autres']]);

        $this->assertSame(['Bondoukou', 'Tanda'], array_column($this->page(route(self::BASE.'ville.index', ['region' => '1']))['props']['elements']['data'], 'name'));
        $this->assertSame('', $this->page(route(self::BASE.'ville.index', ['region' => '99']))['props']['filters']['region']);
        $this->assertCount(2, $this->page(route(self::BASE.'ville.index', ['q' => 'gontougo']))['props']['elements']['data']);

        $this->post(route(self::BASE.'ville.store'), ['name' => 'Koun-Fao'])->assertSessionHasErrors('region_id');
        $this->post(route(self::BASE.'ville.store'), ['name' => 'Tanda', 'region_id' => 1])->assertSessionHasErrors('name');
        // Homonyme dans une autre région : accepté.
        $this->post(route(self::BASE.'ville.store'), ['name' => 'Tanda', 'region_id' => '3'])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->put(route(self::BASE.'ville.update', 2), ['name' => 'Tanda', 'region_id' => '1'])->assertSessionHasNoErrors();

        $this->delete(route(self::BASE.'ville.delete', 1))->assertSessionHas('error');
        $this->delete(route(self::BASE.'ville.delete', 3))->assertSessionHas('error');
        $this->delete(route(self::BASE.'ville.delete', 2))->assertSessionHas('success');
        $this->assertSame(3, DB::table('villes')->count());
    }

    public function test_niveaux_etude_comptent_les_demandes(): void
    {
        $this->admin();
        $page = $this->page(route(self::BASE.'niveau-etude.index'));

        $this->assertSame('Admin/Parametrage/NiveauxEtude', $page['component']);
        $this->assertSame(['total' => 3, 'utilises' => 2, 'inutilises' => 1], $page['props']['stats']);
        $demandes = collect($page['props']['elements']['data'])->pluck('demandes', 'id')->all();
        $this->assertSame(['n-l1' => 1, 'n-l2' => 2, 'n-m1' => 0], $demandes);
        $this->assertSame(['Master 1'], array_column($this->page(route(self::BASE.'niveau-etude.index', ['q' => 'm1']))['props']['elements']['data'], 'name'));

        $this->post(route(self::BASE.'niveau-etude.store'), ['name' => 'Licence 1'])->assertSessionHasErrors('name');
        $this->post(route(self::BASE.'niveau-etude.store'), ['name' => 'Licence 3', 'abreviation' => 'L3', 'description' => ''])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertNotEmpty(DB::table('niveau_etude')->where('name', 'Licence 3')->value('id_niveau_etude'));
        $this->put(route(self::BASE.'niveau-etude.update', 'n-m1'), ['name' => 'Master 1', 'abreviation' => 'M1R'])->assertSessionHasNoErrors();
        $this->assertSame('M1R', DB::table('niveau_etude')->where('id_niveau_etude', 'n-m1')->value('abreviation'));

        $this->delete(route(self::BASE.'niveau-etude.delete', 'n-l1'))->assertSessionHas('error');
        $this->delete(route(self::BASE.'niveau-etude.delete', 'n-m1'))->assertSessionHas('success');
        $this->assertSame(3, DB::table('niveau_etude')->count());
    }

    public function test_types_utilisateur_comptent_les_comptes(): void
    {
        $this->admin();
        $page = $this->page(route(self::BASE.'type-utilisateur.index'));

        $this->assertSame('Admin/Parametrage/TypesUtilisateur', $page['component']);
        $this->assertSame(['total' => 2, 'utilises' => 1, 'inutilises' => 1], $page['props']['stats']);
        $this->assertSame(1, collect($page['props']['elements']['data'])->firstWhere('id', '2')['utilisateurs']);

        $this->put(route(self::BASE.'type-utilisateur.update', 3), ['name' => 'Technicien'])->assertSessionHas('success');
        $this->assertSame('Technicien', DB::table('type_utilisateur')->where('id', 3)->value('name'));
        $this->delete(route(self::BASE.'type-utilisateur.delete', 2))->assertSessionHas('error');
        $this->delete(route(self::BASE.'type-utilisateur.delete', 3))->assertSessionHas('success');
    }

    public function test_configuration_sans_secret_dans_les_props(): void
    {
        $this->admin();
        $page = $this->page(route(self::BASE.'configuration.index', ['menus' => 'notifications']));

        $this->assertSame('Admin/Parametrage/Configuration', $page['component']);
        $this->assertSame('notifications', $page['props']['section']);
        $this->assertSame(['smtp' => true, 'sms' => true], $page['props']['secrets']);
        $this->assertArrayNotHasKey('mail_password', $page['props']['notifications']);
        $this->assertStringNotContainsString('motdepasse-secret', json_encode($page));
        $this->assertStringNotContainsString('cle-secrete', json_encode($page));
        $this->assertSame('CROU Bondoukou', $page['props']['general']['app_name']);
        $this->assertSame(route(self::BASE.'configuration.update', ['menus' => 'profil']), $page['props']['urls']['update']['profil']);

        // Section inconnue : onglet général ; sections Paiements, Taxes, Devises et Sécurité rétablies.
        $this->assertSame('configuration', $this->page(route(self::BASE.'configuration.index', ['menus' => 'xyz']))['props']['section']);
        foreach (['paiements', 'taxes', 'devises', 'securite'] as $section) {
            $this->assertSame($section, $this->page(route(self::BASE.'configuration.index', ['menus' => $section]))['props']['section']);
        }
        $devises = $this->page(route(self::BASE.'configuration.index', ['menus' => 'devises']))['props']['devises'];
        $this->assertSame('espace', $devises['thousands_separator']);
    }

    public function test_configuration_devises_paiements_et_securite(): void
    {
        $this->admin();
        $repo = $this->mock(ConfigAppRepositoryInterface::class);
        $repo->shouldReceive('getConfig')->andReturn($this->settings);
        $repo->shouldReceive('updateDevises')->once()->with(Mockery::on(fn (array $data) => $data['thousands_separator'] === ' '
            && $data['secondary_currencies'] === ['EUR'] && $data['auto_rate'] === false))->andReturn($this->settings);
        $repo->shouldReceive('updatePaiements')->once()->with(Mockery::on(fn (array $data) => $data['methods'] === ['cash', 'mobile']
            && $data['online_payment_enabled'] === true && (int) $data['min_amount'] === 500))->andReturn($this->settings);
        $repo->shouldReceive('updateSecurite')->never();

        $this->put(route(self::BASE.'configuration.update', ['menus' => 'devises']), [
            'base_currency' => 'XOF', 'currency_symbol' => 'FCFA', 'symbol_position' => 'after', 'thousands_separator' => 'espace',
            'secondary_currencies' => ['XOF', 'EUR'], 'auto_rate' => 0, 'rate_frequency' => 'daily',
        ])->assertSessionHasNoErrors()->assertRedirect(route(self::BASE.'configuration.index', ['menus' => 'devises']));

        $this->put(route(self::BASE.'configuration.update', ['menus' => 'paiements']), [
            'methods' => ['cash', 'mobile'], 'online_payment_enabled' => 1, 'auto_receipt' => 1, 'min_amount' => '500', 'daily_limit' => '0',
        ])->assertSessionHasNoErrors();

        // Garde-fous : l'administrateur ne peut pas exclure sa propre adresse IP ni forcer HTTPS hors HTTPS.
        $this->put(route(self::BASE.'configuration.update', ['menus' => 'securite']), [
            'two_factor_auth' => 0, 'session_lifetime' => '120', 'allow_multiple_sessions' => 1, 'password_min_length' => '8',
            'password_require_uppercase' => 1, 'password_require_number' => 1, 'password_require_special' => 0,
            'max_login_attempts' => '5', 'lockout_duration' => '15', 'ip_restriction' => 1, 'allowed_ips' => '10.0.0.1', 'force_https' => 1,
        ])->assertSessionHasErrors(['allowed_ips', 'force_https']);
    }

    public function test_configuration_conserve_les_secrets_vides_et_enregistre_les_booleens(): void
    {
        $this->admin();
        $repo = $this->mock(ConfigAppRepositoryInterface::class);
        $repo->shouldReceive('getConfig')->andReturn($this->settings);
        $repo->shouldReceive('updateNotifications')->once()->with(Mockery::on(function (array $data) {
            return !array_key_exists('mail_password', $data) && $data['sms_api_key'] === 'nouvelle-cle'
                && $data['email_enabled'] === true && $data['notif_report'] === false;
        }))->andReturn($this->settings);
        $repo->shouldReceive('updateGeneral')->once()->with(Mockery::on(fn (array $data) => $data['maintenance_mode'] === false && $data['activity_log'] === true))
            ->andReturn($this->settings);

        // Envoi identique à celui de la page React : booléens en 1 / 0.
        $this->put(route(self::BASE.'configuration.update', ['menus' => 'notifications']), [
            'email_enabled' => 1, 'mail_driver' => 'smtp', 'mail_host' => 'smtp.test', 'mail_port' => '587', 'mail_password' => '',
            'sms_enabled' => 1, 'sms_provider' => 'helper_json', 'sms_api_url' => 'https://sms.test/send', 'sms_api_key' => 'nouvelle-cle', 'sms_sender' => 'CROU',
            'notif_new_user' => 1, 'notif_new_order' => 1, 'notif_payment_ok' => 1, 'notif_payment_fail' => 1, 'notif_low_stock' => 0, 'notif_report' => 0,
        ])->assertSessionHasNoErrors()->assertRedirect(route(self::BASE.'configuration.index', ['menus' => 'notifications']));

        $this->put(route(self::BASE.'configuration.update'), [
            'app_name' => 'CROU Bondoukou', 'company_name' => 'Centre', 'locale' => 'fr', 'timezone' => 'Africa/Abidjan', 'date_format' => 'd/m/Y',
            'maintenance_mode' => 0, 'activity_log' => 1,
        ])->assertSessionHasNoErrors();
    }

    public function test_un_profil_sans_droit_est_refuse(): void
    {
        $this->admin(2);
        $this->get(route(self::BASE.'departement.index'))->assertForbidden();
        $this->get(route(self::BASE.'filiere.index'))->assertForbidden();
        $this->get(route(self::BASE.'type-utilisateur.index'))->assertForbidden();
        $this->get(route(self::BASE.'configuration.index'))->assertForbidden();
    }
}
