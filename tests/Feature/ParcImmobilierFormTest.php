<?php

namespace Tests\Feature;

use App\Http\Controllers\admin\immobilier\parc_immobilier\ParcImmobilierController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** Formulaires en plusieurs étapes du parc (cité, bâtiment, chambres) et règles serveur associées. */
class ParcImmobilierFormTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $t) {
            $t->string('id_user')->primary(); $t->string('name');
            foreach (['matricule', 'telephone_principal', 'telephone_secondaire', 'email', 'email_secondaire', 'campus_id'] as $column) {
                $t->string($column)->nullable();
            }
            $t->integer('role_id')->nullable(); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('type_cite', function (Blueprint $t) { $t->string('id_type_cite')->primary(); $t->string('name'); $t->timestamps(); $t->softDeletes(); });
        Schema::create('type_chambre', function (Blueprint $t) {
            $t->string('id_type_chambre')->primary(); $t->string('name'); $t->double('montant')->default(0); $t->text('equipement')->nullable(); $t->timestamps();
        });
        Schema::create('reglement_interieur', function (Blueprint $t) { $t->string('id_reglement')->primary(); $t->string('titre'); $t->timestamps(); });
        Schema::create('cite', function (Blueprint $t) {
            $t->string('id_cite')->primary(); $t->string('code_cite')->nullable(); $t->string('name');
            foreach (['type_cite_id', 'campus_id', 'adresse', 'responsable_id', 'statut', 'telephone1', 'telephone2', 'telephone3',
                'email1', 'email2', 'reglement_interieur_id', 'plan', 'created_by', 'updated_by', 'deleted_by'] as $column) {
                $t->string($column)->nullable();
            }
            foreach (['capacite_max', 'nombre_batiments', 'annee_construction'] as $column) {
                $t->integer($column)->nullable();
            }
            foreach (['wifi_gratuit', 'salle_etude', 'infirmerie', 'restaurant'] as $column) {
                $t->boolean($column)->default(false);
            }
            $t->decimal('superficie')->nullable(); $t->float('latitude')->nullable(); $t->float('longitude')->nullable();
            $t->timestamps(); $t->softDeletes();
        });
        Schema::create('batiment', function (Blueprint $t) {
            $t->string('id_batiment')->primary(); $t->string('name');
            foreach (['cite_id', 'campus_id', 'responsable_id', 'type_occupation_id', 'type_architecture', 'etat_initial', 'description', 'plan_file', 'created_by', 'updated_by'] as $column) {
                $t->string($column)->nullable();
            }
            foreach (['total_chambres', 'total_etages', 'total_ailes'] as $column) {
                $t->integer($column)->default(0);
            }
            foreach (['services_acces_handicape', 'services_ascenseur', 'services_gardiennage'] as $column) {
                $t->boolean($column)->default(false);
            }
            $t->string('statut')->default('actif'); $t->float('latitude')->nullable(); $t->float('longitude')->nullable();
            $t->timestamps(); $t->softDeletes();
        });

        DB::table('users')->insert(['id_user' => 'resp', 'name' => 'Kouassi Responsable', 'telephone_principal' => '0700000000', 'email' => 'resp@crou.ci']);
        DB::table('type_cite')->insert([
            ['id_type_cite' => 'tm', 'name' => 'Mixte'],
            ['id_type_cite' => 'th', 'name' => 'Uniquement Homme'],
            ['id_type_cite' => 'tf', 'name' => 'Uniquement Femme'],
        ]);
        DB::table('type_chambre')->insert([
            ['id_type_chambre' => 'ind', 'name' => 'Individuelle', 'montant' => 12000, 'equipement' => '{"Chaise":"Chaise","Table":"Table"}'],
            ['id_type_chambre' => 'dbl', 'name' => 'Double', 'montant' => 6000, 'equipement' => null],
        ]);
        DB::table('reglement_interieur')->insert(['id_reglement' => 'reg1', 'titre' => 'Règlement 2026']);
        DB::table('cite')->insert([
            ['id_cite' => 'cH', 'code_cite' => 'CH', 'name' => 'Cité Hommes', 'type_cite_id' => 'th', 'campus_id' => 'k1', 'statut' => 'ouvert', 'latitude' => 8.04, 'longitude' => -2.8],
            ['id_cite' => 'cM', 'code_cite' => 'CM', 'name' => 'Cité Mixte', 'type_cite_id' => 'tm', 'campus_id' => 'k1', 'statut' => 'ouvert', 'latitude' => null, 'longitude' => null],
        ]);
        DB::table('batiment')->insert([
            'id_batiment' => 'b1', 'name' => 'BAT A', 'cite_id' => 'cH', 'type_occupation_id' => 'th', 'type_architecture' => 'simple',
            'etat_initial' => 'operationnel', 'total_chambres' => 12, 'total_etages' => 3, 'total_ailes' => 1, 'services_ascenseur' => true,
        ]);

        $this->actingAs(new User(['id_user' => 'actor', 'role_id' => 1, 'name' => 'Admin', 'campus_id' => 'k1']), 'admin');
    }

    public function test_formulaire_cite_rendu_avec_inertia(): void
    {
        $page = $this->formPage(['type' => 'cite']);

        $this->assertSame('Admin/Hebergement/ParcImmobilier/Form', $page['component']);
        $props = $page['props'];
        $this->assertSame('cite', $props['type']);
        $this->assertSame('create', $props['mode']);
        $this->assertNull($props['item']);
        $this->assertSame('k1', $props['campusId']);
        $this->assertSame([['id' => 'reg1', 'name' => 'Règlement 2026']], $props['reglements']);
        $this->assertSame('resp@crou.ci', collect($props['users'])->firstWhere('id', 'resp')['email']);
        $this->assertSame(route('admin.hebergement.parc-immobilier.storeCite'), $props['urls']['submit']);
        $this->assertArrayNotHasKey('batiments', $props);
    }

    public function test_modification_d_un_batiment_prerempli(): void
    {
        $props = $this->formPage(['type' => 'batiment', 'edit' => 1, 'id' => 'b1'])['props'];

        $this->assertSame('edit', $props['mode']);
        $this->assertSame(route('admin.hebergement.parc-immobilier.updateBatiment'), $props['urls']['submit']);
        $this->assertSame('cH', $props['item']['code_cite']);
        $this->assertSame('th', $props['item']['type_occupation']);
        $this->assertTrue($props['item']['ascenseur']);
        $this->assertSame(12, $props['item']['total_chambres']);
        // La cité porte son type : le formulaire limite le type d'occupation du bâtiment.
        $this->assertSame('th', collect($props['cites'])->firstWhere('id', 'cH')['type_cite_id']);
    }

    public function test_formulaire_chambres_avec_batiments_et_types(): void
    {
        $props = $this->formPage(['type' => 'chambre', 'batiment' => 'b1'])['props'];

        $this->assertSame('chambre', $props['type']);
        $this->assertSame('b1', $props['selection']['batiment']);
        $this->assertSame([['id' => 'b1', 'name' => 'BAT A', 'cite_id' => 'cH', 'type_occupation_id' => 'th', 'type_architecture' => 'simple', 'actif' => true]], $props['batiments']);
        $types = collect($props['typesChambre'])->keyBy('id');
        $this->assertSame(['Chaise', 'Table'], $types['ind']['equipements']);
        $this->assertSame(1, $types['ind']['capacite']);
        $this->assertSame(2, $types['dbl']['capacite']);
        $this->assertSame([], $types['dbl']['equipements']);
        $this->assertNotNull($props['urls']['massCreate']);
    }

    public function test_cite_refusee_sans_les_champs_obligatoires(): void
    {
        try {
            app(ParcImmobilierController::class)->storeCite($this->requete('POST', ['campus_id' => 'k1', 'code_cite' => 'CH', 'latitude' => '120']));
            $this->fail('La validation aurait dû échouer.');
        } catch (ValidationException $e) {
            $erreurs = $e->errors();
            foreach (['name', 'type_cite', 'capacite_max', 'adresse', 'longitude'] as $champ) {
                $this->assertArrayHasKey($champ, $erreurs, $champ);
            }
            // Code déjà utilisé par une autre cité, latitude hors limites.
            $this->assertArrayHasKey('code_cite', $erreurs);
            $this->assertArrayHasKey('latitude', $erreurs);
        }
        $this->assertSame(2, DB::table('cite')->count());
    }

    public function test_cite_enregistree_sans_plan_et_telephones_normalises(): void
    {
        app(ParcImmobilierController::class)->storeCite($this->requete('POST', [
            'campus_id' => 'k1', 'name' => 'Cité Nouvelle', 'code_cite' => 'CN', 'type_cite' => 'tf', 'statut' => 'ouvert',
            'capacite_max' => '300', 'nombre_batiments' => '', 'superficie' => '', 'annee_construction' => '2020',
            'responsable_id' => 'resp', 'telephone1' => '07 01 02 03 04', 'telephone2' => '', 'telephone3' => '',
            'email1' => 'cite@crou.ci', 'email2' => '', 'reglement_id' => 'reg1', 'adresse' => 'Bondoukou',
            'latitude' => '8.04', 'longitude' => '-2.8', 'wifi_gratuit' => '1',
        ]));

        $cite = DB::table('cite')->where('code_cite', 'CN')->first();
        $this->assertNotNull($cite);
        $this->assertSame('0701020304', $cite->telephone1);
        $this->assertNull($cite->plan);
        $this->assertEquals(1, $cite->wifi_gratuit);
        $this->assertEquals(0, $cite->restaurant);
        $this->assertSame('reg1', $cite->reglement_interieur_id);
    }

    public function test_batiment_refuse_si_type_incompatible_avec_la_cite(): void
    {
        $reponse = $this->storeBatiment(['code_cite' => 'cH', 'name' => 'BAT B', 'type_occupation' => 'tf']);

        $this->assertSame(422, $reponse->getStatusCode());
        $this->assertArrayHasKey('type_occupation', $reponse->getData(true)['errors']);
        $this->assertSame(1, DB::table('batiment')->count());
    }

    public function test_batiment_refuse_si_nom_deja_utilise_ou_sans_chambre(): void
    {
        $doublon = $this->storeBatiment(['code_cite' => 'cH', 'name' => 'BAT A', 'type_occupation' => 'th']);
        $this->assertSame(422, $doublon->getStatusCode());
        $this->assertArrayHasKey('name', $doublon->getData(true)['errors']);

        // Cité mixte : tout type accepté, mais il faut au moins une chambre.
        $vide = $this->storeBatiment(['code_cite' => 'cM', 'name' => 'BAT M', 'type_occupation' => 'tf'], 0);
        $this->assertSame(422, $vide->getStatusCode());
        $this->assertSame(['structure_data'], array_keys($vide->getData(true)['errors']));
    }

    public function test_modification_batiment_refusee_si_nom_pris_dans_la_cite(): void
    {
        DB::table('batiment')->insert(['id_batiment' => 'b2', 'name' => 'BAT C', 'cite_id' => 'cH', 'type_occupation_id' => 'th']);
        $request = $this->requete('PUT', [
            'code' => 'b2',
            'batiment_data' => json_encode(['code_cite' => 'cH', 'name' => 'BAT A', 'type_occupation' => 'th']),
        ]);

        $reponse = app(ParcImmobilierController::class)->updateBatiment($request);

        $this->assertSame(422, $reponse->getStatusCode());
        $this->assertArrayHasKey('name', $reponse->getData(true)['errors']);
        $this->assertSame('BAT C', DB::table('batiment')->where('id_batiment', 'b2')->value('name'));
    }

    private function storeBatiment(array $donnees, int $chambres = 4)
    {
        $donnees += [
            'code_campus' => 'k1',
            'type_architecture' => 'simple',
            'etat_initial' => 'operationnel',
            'structure_data' => ['type' => 'simple', 'nombre_etages' => 0, 'etages' => [['etage_numero' => 0, 'chambres' => $chambres, 'type_occupation' => 'mixte']]],
        ];

        return app(ParcImmobilierController::class)->storeBatiment($this->requete('POST', ['batiment_data' => json_encode($donnees)]));
    }

    private function requete(string $methode, array $donnees): Request
    {
        $request = Request::create('/admin/gestion-hebergement/parc-immobilier', $methode, $donnees);
        $request->setUserResolver(fn ($guard = null) => auth('admin')->user());
        $this->app->instance('request', $request);

        return $request;
    }

    private function formPage(array $query): array
    {
        $request = Request::create('/admin/gestion-hebergement/parc-immobilier/create', 'GET', $query);
        $request->headers->set('X-Inertia', 'true');
        $request->setUserResolver(fn ($guard = null) => auth('admin')->user());
        $this->app->instance('request', $request);

        return app()->call([app(ParcImmobilierController::class), 'create'], ['request' => $request])->toResponse($request)->getData(true);
    }
}
