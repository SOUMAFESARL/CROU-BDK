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

/** Pages React du paramétrage hébergement : composants, props filtrées par droits et actions inchangées. */
class HebergementParametragePagesTest extends TestCase
{
    private const BASE = 'admin.hebergement.parametrage-herb.';

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->withoutMiddleware([EnforceSystemSecurity::class, LogUserActivity::class]);
        $settings = new ConfigApp(['app_name' => 'Résidence test']);
        $settings->setRelation('region', null)->setRelation('ville', null);
        $this->mock(ConfigAppRepositoryInterface::class)->shouldReceive('getConfig')->andReturn($settings);

        Schema::create('roles', function (Blueprint $t) { $t->id(); $t->string('name'); $t->timestamps(); });
        Role::create(['id' => 1, 'name' => 'Administrateur']);
        Role::create(['id' => 2, 'name' => 'Agent']);
        foreach ([
            '2026_08_11_000001_create_role_permissions_tables.php',
            '2026_08_11_000002_create_modules_table.php',
            '2026_09_23_000003_create_campaign_scorings.php',
            '2026_09_24_000001_create_housing_entry_workflow.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }

        Schema::create('type_cite', function (Blueprint $t) {
            $t->string('id_type_cite')->primary(); $t->string('code_type')->nullable(); $t->string('name'); $t->text('description')->nullable();
            $t->string('categorie')->default('universitaire'); $t->string('public_cible')->nullable(); $t->integer('age_min')->default(0); $t->integer('age_max')->default(0);
            $t->boolean('is_active')->default(true); $t->string('created_by')->nullable(); $t->string('updated_by')->nullable(); $t->timestamps();
        });
        Schema::create('type_chambre', function (Blueprint $t) {
            $t->string('id_type_chambre')->primary(); $t->string('name'); $t->text('description')->nullable(); $t->double('montant')->default(0);
            $t->integer('caution')->default(0); $t->integer('avance')->default(0); $t->integer('superficie')->default(0); $t->text('equipement')->nullable(); $t->timestamps();
        });
        Schema::create('type_equipement', function (Blueprint $t) {
            $t->string('id_type_equipement')->primary(); $t->string('name'); $t->text('description')->nullable(); $t->decimal('cout_moyen')->default(0);
            $t->decimal('cout_maintenance')->default(0); $t->boolean('est_consommable')->default(false); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('equipement', function (Blueprint $t) { $t->string('equipement_id')->primary(); $t->string('name'); $t->string('type_equipement_id'); $t->timestamps(); });
        Schema::create('cite', function (Blueprint $t) {
            $t->string('id_cite')->primary(); $t->string('name'); $t->string('type_cite_id')->nullable(); $t->string('statut')->default('actif'); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('batiment', function (Blueprint $t) {
            $t->string('id_batiment')->primary(); $t->string('name'); $t->string('cite_id'); $t->string('type_occupation_id')->nullable(); $t->string('statut')->default('actif'); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('chambre', function (Blueprint $t) {
            $t->string('id_chambre')->primary(); $t->string('numero_chambre')->nullable(); $t->string('cite_id'); $t->string('batiment_id'); $t->string('type_chambre_id')->nullable();
            $t->string('statut')->default('actif'); $t->string('etat')->default('disponible'); $t->integer('capacite_max')->default(1); $t->integer('occupants_actuels')->default(0);
            $t->boolean('pmr')->default(false); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('campagne', function (Blueprint $t) {
            $t->string('id_campagne')->primary(); $t->string('annee_campagne')->nullable(); $t->integer('annee_debut')->nullable(); $t->integer('annee_fin')->nullable();
            $t->text('description')->nullable(); $t->string('statut')->nullable(); $t->float('moyenne_min')->nullable(); $t->float('distance_min')->nullable();
            $t->integer('pourcentage_validate')->nullable(); $t->integer('age_L1_max')->nullable(); $t->integer('age_M1_max')->nullable();
            foreach (['admission', 'readmission', 'internationnaux', 'redoublement', 'pmr', 'is_participate', 'is_discipline'] as $condition) {
                $t->boolean($condition)->default(false);
            }
            foreach (['periode_debut', 'periode_fin', 'date_echeance_demande', 'date_echeance_contrat'] as $date) {
                $t->date($date)->nullable();
            }
            $t->string('created_by')->nullable(); $t->string('updated_by')->nullable(); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('residence_campagne', function (Blueprint $t) { $t->string('id_residence_campagne')->primary(); $t->string('campagne_id'); $t->string('cite_id'); $t->timestamps(); });
        Schema::create('document', function (Blueprint $t) {
            $t->string('id_document')->primary(); $t->string('name'); $t->string('slug'); $t->text('description')->nullable(); $t->integer('delai_validite_mois')->nullable();
            $t->boolean('champs_requis')->default(false); $t->boolean('statut')->default(true); $t->integer('ordre_affiche')->default(0);
            $t->string('created_by')->nullable(); $t->string('updated_by')->nullable(); $t->timestamps();
        });
        Schema::create('document_campagne', function (Blueprint $t) { $t->string('id_document_campagne')->primary(); $t->string('id_campagne'); $t->string('document_id'); $t->timestamps(); });
        Schema::create('etudiants', function (Blueprint $t) { $t->string('id_etudiant')->primary(); $t->string('name')->nullable(); $t->integer('genre_id')->nullable(); });
        Schema::create('genre', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('demande', function (Blueprint $t) {
            $t->string('id_demande')->primary(); $t->string('etudiant_id'); $t->string('campagne_id'); $t->boolean('boursier')->default(false);
            $t->string('etat_dossier')->default('soumis'); $t->timestamps(); $t->softDeletes();
        });

        DB::table('type_cite')->insert([['id_type_cite' => 'mixte', 'name' => 'Mixte'], ['id_type_cite' => 'femmes', 'name' => 'Uniquement Femme']]);
        DB::table('type_chambre')->insert([
            'id_type_chambre' => 'double', 'name' => 'Double', 'montant' => 6000, 'caution' => 1, 'avance' => 6,
            'equipement' => json_encode(['Chaise' => 'Chaise', 'Table' => 'Table']),
        ]);
        DB::table('cite')->insert(['id_cite' => 'cite-a', 'name' => 'Cité A', 'type_cite_id' => 'mixte']);
        DB::table('batiment')->insert(['id_batiment' => 'bat-1', 'name' => 'Bâtiment 1', 'cite_id' => 'cite-a', 'type_occupation_id' => 'mixte']);
        DB::table('chambre')->insert([
            ['id_chambre' => 'c1', 'numero_chambre' => '101', 'cite_id' => 'cite-a', 'batiment_id' => 'bat-1', 'type_chambre_id' => 'double', 'capacite_max' => 2, 'occupants_actuels' => 1, 'etat' => 'disponible', 'pmr' => true],
            ['id_chambre' => 'c2', 'numero_chambre' => '102', 'cite_id' => 'cite-a', 'batiment_id' => 'bat-1', 'type_chambre_id' => 'double', 'capacite_max' => 2, 'occupants_actuels' => 2, 'etat' => 'occupee', 'pmr' => false],
            // Gabarit sans numéro : ignoré dans les capacités.
            ['id_chambre' => 'c3', 'numero_chambre' => null, 'cite_id' => 'cite-a', 'batiment_id' => 'bat-1', 'type_chambre_id' => 'double', 'capacite_max' => 2, 'occupants_actuels' => 0, 'etat' => 'disponible', 'pmr' => false],
        ]);
        DB::table('campagne')->insert([
            'id_campagne' => 'camp', 'annee_campagne' => '2026-2027', 'description' => 'Campagne test', 'statut' => 'active',
            'moyenne_min' => 10, 'distance_min' => 50, 'age_L1_max' => 25, 'admission' => true, 'created_at' => now(),
        ]);
        DB::table('residence_campagne')->insert(['id_residence_campagne' => 'rc', 'campagne_id' => 'camp', 'cite_id' => 'cite-a']);
        DB::table('document')->insert([
            ['id_document' => 'releve', 'name' => 'Relevé de notes', 'slug' => 'releve', 'champs_requis' => true, 'statut' => true, 'ordre_affiche' => 1],
            ['id_document' => 'ancien', 'name' => 'Ancien justificatif', 'slug' => 'ancien', 'champs_requis' => false, 'statut' => false, 'ordre_affiche' => 2],
        ]);
        DB::table('document_campagne')->insert(['id_document_campagne' => 'dc', 'id_campagne' => 'camp', 'document_id' => 'releve']);
        DB::table('genre')->insert([['id' => 1, 'name' => 'Masculin'], ['id' => 2, 'name' => 'Féminin']]);
        DB::table('etudiants')->insert([['id_etudiant' => 'e1', 'genre_id' => 1], ['id_etudiant' => 'e2', 'genre_id' => 2], ['id_etudiant' => 'e3', 'genre_id' => 2]]);
        DB::table('demande')->insert([
            ['id_demande' => 'd1', 'etudiant_id' => 'e1', 'campagne_id' => 'camp', 'boursier' => true, 'etat_dossier' => 'soumis'],
            ['id_demande' => 'd2', 'etudiant_id' => 'e2', 'campagne_id' => 'camp', 'boursier' => false, 'etat_dossier' => 'eligible'],
            ['id_demande' => 'd3', 'etudiant_id' => 'e3', 'campagne_id' => 'camp', 'boursier' => true, 'etat_dossier' => 'brouillon'],
        ]);
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

    public function test_accueil_liste_les_modules_par_rubrique(): void
    {
        $this->admin();
        $page = $this->page(route(self::BASE.'index'));

        $this->assertSame('Admin/Hebergement/Parametrage/Index', $page['component']);
        $this->assertSame(['Parc immobilier', 'Campagnes & admissions', 'Documents et entrée dans les lieux'], array_column($page['props']['sections'], 'titre'));
        $hrefs = collect($page['props']['sections'])->flatMap(fn ($s) => array_column($s['modules'], 'href'));
        $this->assertContains(route('admin.housing-entry.settings'), $hrefs);
        $this->assertCount(9, $hrefs);
    }

    public function test_types_de_cite_avec_usage_et_recherche(): void
    {
        $this->admin();
        $page = $this->page(route(self::BASE.'type-cite.index'));

        $this->assertSame('Admin/Hebergement/Parametrage/TypesCite', $page['component']);
        $this->assertSame(['total' => 2, 'utilises' => 1, 'inutilises' => 1], $page['props']['stats']);
        $mixte = collect($page['props']['types']['data'])->firstWhere('id', 'mixte');
        $this->assertSame([1, 1], [$mixte['cites'], $mixte['batiments']]);
        $this->assertSame(route(self::BASE.'type-cite.update', 'mixte'), $mixte['urls']['update']);

        $recherche = $this->page(route(self::BASE.'type-cite.index', ['q' => 'femme']))['props'];
        $this->assertSame(['Uniquement Femme'], array_column($recherche['types']['data'], 'name'));
        $this->assertSame('femme', $recherche['filters']['q']);

        // La réponse JSON est conservée.
        $this->getJson(route(self::BASE.'type-cite.index'))->assertOk()->assertJsonPath('success', true)->assertJsonCount(2, 'data');
    }

    public function test_types_de_chambre_decodent_les_equipements_et_enregistrent_le_meme_format(): void
    {
        $this->admin();
        $page = $this->page(route(self::BASE.'type-chambre.index'));

        $this->assertSame('Admin/Hebergement/Parametrage/TypesChambre', $page['component']);
        $double = $page['props']['types']['data'][0];
        $this->assertSame(['Chaise', 'Table'], $double['equipements']);
        $this->assertSame(2, $double['chambres']);
        $this->assertContains('Ventilateur', $page['props']['equipementsDisponibles']);

        // Envoi identique à celui de la page React (equipement[Nom]=Nom).
        $this->put(route(self::BASE.'type-chambre.update', 'double'), [
            'name' => 'Double', 'description' => 'Deux lits', 'montant' => 7000, 'caution' => 1, 'avance' => 3,
            'equipement' => ['Matelas' => 'Matelas', 'WC' => 'WC'],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $type = DB::table('type_chambre')->first();
        $this->assertEquals(7000, $type->montant);
        $this->assertSame(['Matelas' => 'Matelas', 'WC' => 'WC'], json_decode($type->equipement, true));
    }

    public function test_types_d_equipements_comptent_le_catalogue(): void
    {
        DB::table('type_equipement')->insert([['id_type_equipement' => 'lit', 'name' => 'Literie'], ['id_type_equipement' => 'mob', 'name' => 'Mobilier']]);
        DB::table('equipement')->insert(['equipement_id' => 'm1', 'name' => 'Matelas', 'type_equipement_id' => 'lit']);
        $this->admin();
        $page = $this->page(route(self::BASE.'type-equipement.index'));

        $this->assertSame('Admin/Hebergement/Parametrage/TypesEquipement', $page['component']);
        $this->assertSame(['total' => 2, 'utilises' => 1, 'inutilises' => 1], $page['props']['stats']);
        $this->assertSame(1, collect($page['props']['types']['data'])->firstWhere('id', 'lit')['equipements']);
    }

    public function test_documents_filtres_et_modification_du_statut(): void
    {
        $this->admin();
        $page = $this->page(route(self::BASE.'document.index'));

        $this->assertSame('Admin/Hebergement/Parametrage/Documents', $page['component']);
        $this->assertSame(['total' => 2, 'actifs' => 1, 'obligatoires' => 1, 'inactifs' => 1], $page['props']['stats']);
        $this->assertSame(1, collect($page['props']['documents']['data'])->firstWhere('id', 'releve')['campagnes']);
        $this->assertSame(['Ancien justificatif'], array_column($this->page(route(self::BASE.'document.index', ['statut' => 'inactifs']))['props']['documents']['data'], 'name'));
        // Filtre inconnu ignoré.
        $this->assertSame('tous', $this->page(route(self::BASE.'document.index', ['statut' => 'xyz']))['props']['filters']['statut']);

        // Comme la page React : cases cochées envoyées « on », absentes sinon.
        $this->put(route(self::BASE.'document.update', 'releve'), ['name' => 'Relevé de notes', 'ordre_affiche' => '3', 'delai_validite_mois' => '12', 'champs_requis' => 'on'])
            ->assertSessionHasNoErrors();
        $document = DB::table('document')->where('id_document', 'releve')->first();
        $this->assertSame([3, 12, 1, 0], [(int) $document->ordre_affiche, (int) $document->delai_validite_mois, (int) $document->champs_requis, (int) $document->statut]);
    }

    public function test_scoring_propose_des_exemples_sans_bareme(): void
    {
        $this->admin();
        $page = $this->page(route(self::BASE.'scoring-campagne.index'));

        $this->assertSame('Admin/Hebergement/Parametrage/Scoring', $page['component']);
        $this->assertSame('camp', $page['props']['campagneId']);
        $this->assertNull($page['props']['scoring']);
        $this->assertSame(route(self::BASE.'scoring-campagne.store'), $page['props']['urls']['store']);
        $this->assertArrayHasKey('academique', $page['props']['fields']);
    }

    public function test_pages_de_campagne_lisent_les_donnees_reelles(): void
    {
        $this->admin();

        $batiments = $this->page(route(self::BASE.'batiments-campagne.index'))['props'];
        $cite = $batiments['batiments'][0];
        $this->assertSame('Cité A', $cite['name']);
        $this->assertSame(['chambres' => 2, 'places' => 4, 'occupees' => 3, 'libres' => 1, 'pmr' => 1], array_intersect_key($cite, array_flip(['chambres', 'places', 'occupees', 'libres', 'pmr'])));
        $this->assertSame('Mixte', $cite['batiments'][0]['type_occupation']);

        $quotas = $this->page(route(self::BASE.'quotas-campagne.index', ['campagne_id' => 'camp']))['props']['quotas'];
        $this->assertSame('Double', $quotas['types'][0]['type']);
        // Le brouillon n'est pas compté.
        $this->assertSame(2, $quotas['demandes']['total']);
        $this->assertSame(1, $quotas['demandes']['boursiers']);

        $regles = $this->page(route(self::BASE.'regles-campagne.index', ['campagne_id' => 'inconnue']))['props'];
        // Campagne inconnue : retour à la plus récente.
        $this->assertSame('camp', $regles['campagneId']);
        $this->assertEquals(10, $regles['regles']['moyenne_min']);
        $this->assertSame(['L1' => 25], $regles['regles']['ages']);
        $this->assertSame(['Relevé de notes'], $regles['regles']['documents']);
        $this->assertSame(['Cité A'], $regles['regles']['residences']);
        $this->assertTrue($regles['regles']['conditions']['admission']);
    }

    public function test_parametrage_de_l_entree_en_react(): void
    {
        $this->admin();
        $page = $this->page(route('admin.housing-entry.settings'));

        $this->assertSame('Admin/Hebergement/Parametrage/EntreeLogement', $page['component']);
        $this->assertFalse($page['props']['options']['enabled']);
        $this->assertSame('Durée du contrat', collect($page['props']['terms'])->firstWhere('key', 'duree')['label']);
        $this->assertSame(route('admin.housing-entry.update'), $page['props']['urls']['update']);
        $this->assertSame(0, $page['props']['failed']);
    }

    public function test_un_profil_sans_droit_est_refuse(): void
    {
        $this->admin(2);
        $this->get(route(self::BASE.'type-cite.index'))->assertForbidden();
        $this->get(route(self::BASE.'regles-campagne.index'))->assertForbidden();
    }
}
