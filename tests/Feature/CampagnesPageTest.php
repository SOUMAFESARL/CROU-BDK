<?php

namespace Tests\Feature;

use App\Http\Controllers\admin\immobilier\campagnes_admission\CampagnesAdmissionController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Page des campagnes passée en Inertia + React : liste, détail et formulaire de création. */
class CampagnesPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('campagne', function (Blueprint $t) {
            $t->string('id_campagne')->primary();
            foreach (['libelle', 'description', 'annee_campagne', 'created_by', 'updated_by', 'deleted_by'] as $column) {
                $t->string($column)->nullable();
            }
            $t->string('statut')->default('planifier'); // comme le schéma MySQL
            $t->integer('annee_debut')->nullable(); $t->integer('annee_fin')->nullable();
            foreach (['periode_debut', 'periode_fin', 'date_echeance_demande', 'date_echeance_contrat'] as $column) {
                $t->date($column)->nullable();
            }
            foreach (['admission', 'readmission', 'pmr', 'internationnaux', 'redoublement', 'is_participate', 'is_discipline'] as $column) {
                $t->boolean($column)->nullable()->default(false);
            }
            $t->float('moyenne_min')->nullable(); $t->float('distance_min')->nullable(); $t->integer('pourcentage_validate')->nullable();
            foreach (['L1', 'L2', 'L3', 'M1', 'M2', 'D1', 'D2', 'D3'] as $niveau) {
                $t->integer("age_{$niveau}_max")->nullable();
            }
            $t->timestamps(); $t->softDeletes();
        });
        Schema::create('residence_campagne', function (Blueprint $t) {
            $t->string('id_residence_campagne')->primary(); $t->string('campagne_id'); $t->string('cite_id'); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('document_campagne', function (Blueprint $t) {
            $t->string('id_document_campagne')->primary(); $t->string('id_campagne'); $t->string('document_id'); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('document', function (Blueprint $t) {
            $t->string('id_document')->primary(); $t->string('name'); $t->string('slug')->nullable(); $t->integer('statut')->default(1);
            $t->timestamps(); $t->softDeletes();
        });
        Schema::create('cite', function (Blueprint $t) {
            $t->string('id_cite')->primary(); $t->string('name'); $t->string('statut')->nullable(); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('batiment', function (Blueprint $t) {
            $t->string('id_batiment')->primary(); $t->string('name'); $t->string('cite_id')->nullable(); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('chambre', function (Blueprint $t) {
            $t->string('id_chambre')->primary(); $t->string('numero_chambre')->nullable(); $t->string('batiment_id')->nullable();
            $t->string('etat')->default('disponible'); $t->integer('capacite_max')->default(1); $t->timestamps(); $t->softDeletes();
        });

        // Dossiers rattachés à une campagne : ils empêchent sa suppression.
        Schema::create('demande', function (Blueprint $t) {
            $t->string('id_demande')->primary(); $t->string('etudiant_id')->nullable(); $t->string('campagne_id'); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('assignation_chambres', function (Blueprint $t) {
            $t->id(); $t->string('campagne_id'); $t->timestamps();
        });
        Schema::create('admission_derogations', function (Blueprint $t) {
            $t->id(); $t->string('campagne_id')->nullable(); $t->timestamps();
        });

        DB::table('cite')->insert([
            ['id_cite' => 'cA', 'name' => 'Cité A', 'statut' => 'ouvert'],
            ['id_cite' => 'cF', 'name' => 'Cité fermée', 'statut' => 'ferme'],
        ]);
        DB::table('batiment')->insert(['id_batiment' => 'b1', 'name' => 'Bâtiment 1', 'cite_id' => 'cA']);
        DB::table('chambre')->insert([
            ['id_chambre' => 'r1', 'numero_chambre' => '101', 'batiment_id' => 'b1', 'etat' => 'disponible', 'capacite_max' => 2],
            ['id_chambre' => 'r2', 'numero_chambre' => '102', 'batiment_id' => 'b1', 'etat' => 'disponible', 'capacite_max' => 3],
            ['id_chambre' => 'r3', 'numero_chambre' => '103', 'batiment_id' => 'b1', 'etat' => 'occupee', 'capacite_max' => 2],
        ]);
        DB::table('document')->insert([
            ['id_document' => 'd1', 'name' => 'Certificat de scolarité', 'statut' => 1],
            ['id_document' => 'd2', 'name' => 'Ancien document', 'statut' => 0],
        ]);
        DB::table('campagne')->insert([
            [
                'id_campagne' => 'k1', 'libelle' => 'Campagne principale 2025', 'annee_campagne' => '2025-2026', 'description' => 'Campagne principale', 'statut' => 'active',
                'periode_debut' => '2025-09-01', 'periode_fin' => '2025-10-31', 'date_echeance_demande' => '2025-11-15', 'date_echeance_contrat' => '2025-11-30',
                'admission' => true, 'readmission' => true, 'moyenne_min' => 10.5, 'age_L1_max' => 22, 'age_L2_max' => 23, 'created_at' => now(),
            ],
            [
                'id_campagne' => 'k2', 'libelle' => null, 'annee_campagne' => '2026-2027', 'description' => null, 'statut' => 'planifier',
                'periode_debut' => '2026-09-01', 'periode_fin' => '2026-10-31', 'date_echeance_demande' => null, 'date_echeance_contrat' => null,
                'admission' => false, 'readmission' => true, 'moyenne_min' => null, 'age_L1_max' => null, 'age_L2_max' => null, 'created_at' => now()->subDay(),
            ],
        ]);
        DB::table('residence_campagne')->insert(['id_residence_campagne' => 'rc1', 'campagne_id' => 'k1', 'cite_id' => 'cA']);
        DB::table('document_campagne')->insert(['id_document_campagne' => 'dc1', 'id_campagne' => 'k1', 'document_id' => 'd1']);

        $this->actingAs(new User(['id_user' => 'actor', 'role_id' => 1, 'name' => 'Admin']), 'admin');
    }

    public function test_liste_des_campagnes_rendue_avec_inertia(): void
    {
        $page = $this->page('campagnes', '/admin/gestion-hebergement/campagnes_admissions/campagnes');

        $this->assertSame('Admin/Hebergement/Campagnes/Index', $page['component']);
        $props = $page['props'];
        $this->assertSame('tous', $props['etat']);
        $this->assertSame(['active' => 1, 'terminer' => 0, 'planifier' => 1, 'total' => 2], $props['stats']);
        $this->assertSame(['k1', 'k2'], array_column($props['campagnes']['data'], 'id'));

        $k1 = $props['campagnes']['data'][0];
        $this->assertSame('2025-09-01', $k1['periode_debut']);
        $this->assertSame('Campagne principale 2025', $k1['libelle']);
        $this->assertNull($props['campagnes']['data'][1]['libelle']);
        $this->assertSame([['name' => 'Cité A', 'places' => 5]], $k1['residences']);
        $this->assertSame(['Certificat de scolarité'], $k1['documents']);
        $this->assertSame(['L1' => 22, 'L2' => 23], $k1['criteres']['ages']);
        $this->assertTrue($k1['conditions']['admission']);
        $this->assertFalse($k1['conditions']['pmr']);
        $this->assertNotNull($props['urls']['create']);
    }

    public function test_campagnes_filtrees_par_statut(): void
    {
        $props = $this->page('campagnes', '/admin/gestion-hebergement/campagnes_admissions/campagnes/planifier', ['etat' => 'planifier'])['props'];
        $this->assertSame('planifier', $props['etat']);
        $this->assertSame(['k2'], array_column($props['campagnes']['data'], 'id'));

        // Un filtre inconnu affiche toutes les campagnes.
        $inconnu = $this->page('campagnes', '/admin/gestion-hebergement/campagnes_admissions/campagnes/nimporte', ['etat' => 'nimporte'])['props'];
        $this->assertSame('tous', $inconnu['etat']);
        $this->assertCount(2, $inconnu['campagnes']['data']);
    }

    public function test_formulaire_de_creation_propose_les_cites_ouvertes_et_documents_actifs(): void
    {
        $page = $this->page('createCampagne', '/admin/gestion-hebergement/campagnes_admissions/campagnes/nouvelle');

        $this->assertSame('Admin/Hebergement/Campagnes/Create', $page['component']);
        $this->assertSame([['id' => 'cA', 'name' => 'Cité A', 'chambres' => 2]], $page['props']['residences']);
        $this->assertSame([['id' => 'd1', 'name' => 'Certificat de scolarité']], $page['props']['documents']);
        $this->assertNotNull($page['props']['urls']['submit']);
    }

    public function test_creation_d_une_campagne_puis_retour_a_la_liste(): void
    {
        $this->withoutMiddleware();

        $this->post(route('admin.hebergement.campagnes_admission.post_campagnes'), $this->donnees())
            ->assertRedirect(route('admin.hebergement.campagnes_admission.campagnes'))
            ->assertSessionHas('success');

        $campagne = DB::table('campagne')->where('annee_campagne', '2030-2031')->first();
        $this->assertNotNull($campagne);
        $this->assertSame('Campagne de test', $campagne->libelle);
        // Le type choisi renseigne admission / réadmission ; le reste du public et les critères ne sont pas paramétrés.
        $this->assertSame(1, (int) $campagne->admission);
        $this->assertSame(1, (int) $campagne->readmission);
        foreach (['internationnaux', 'pmr', 'redoublement', 'is_discipline', 'moyenne_min', 'age_L1_max', 'date_echeance_demande', 'date_echeance_contrat'] as $champ) {
            $this->assertNull($campagne->{$champ}, $champ);
        }
        $this->assertSame(['cA'], DB::table('residence_campagne')->where('campagne_id', $campagne->id_campagne)->pluck('cite_id')->all());
        $this->assertSame(['d1'], DB::table('document_campagne')->where('id_campagne', $campagne->id_campagne)->pluck('document_id')->all());
    }

    public function test_le_libelle_est_obligatoire(): void
    {
        $this->withoutMiddleware();

        $this->from(route('admin.hebergement.campagnes_admission.create_campagne'))
            ->post(route('admin.hebergement.campagnes_admission.post_campagnes'), $this->donnees(['libelle' => '']))
            ->assertSessionHasErrors('libelle');
        $this->from(route('admin.hebergement.campagnes_admission.edit_campagne', 'k2'))
            ->put(route('admin.hebergement.campagnes_admission.update_campagne', 'k2'), $this->donnees(['libelle' => str_repeat('a', 151)]))
            ->assertSessionHasErrors('libelle');
        $this->assertSame(0, DB::table('campagne')->where('annee_campagne', '2030-2031')->count());
    }

    public function test_le_type_de_campagne_est_obligatoire_et_renseigne_le_public(): void
    {
        $this->withoutMiddleware();

        $this->from(route('admin.hebergement.campagnes_admission.create_campagne'))
            ->post(route('admin.hebergement.campagnes_admission.post_campagnes'), $this->donnees(['type' => '']))
            ->assertSessionHasErrors('type');
        $this->from(route('admin.hebergement.campagnes_admission.create_campagne'))
            ->post(route('admin.hebergement.campagnes_admission.post_campagnes'), $this->donnees(['type' => 'inconnu']))
            ->assertSessionHasErrors('type');
        $this->assertSame(0, DB::table('campagne')->where('annee_campagne', '2030-2031')->count());
        $this->flushSession();

        $this->post(route('admin.hebergement.campagnes_admission.post_campagnes'), $this->donnees(['type' => 'admission']))
            ->assertSessionHasNoErrors();
        $creee = DB::table('campagne')->where('annee_campagne', '2030-2031')->first();
        $this->assertSame([1, 0], [(int) $creee->admission, (int) $creee->readmission]);

        $this->put(route('admin.hebergement.campagnes_admission.update_campagne', 'k1'), $this->donnees(['type' => 'readmission']))
            ->assertSessionHasNoErrors();
        $modifiee = DB::table('campagne')->where('id_campagne', 'k1')->first();
        $this->assertSame([0, 1], [(int) $modifiee->admission, (int) $modifiee->readmission]);
    }

    public function test_la_description_est_facultative(): void
    {
        $this->withoutMiddleware();

        $this->post(route('admin.hebergement.campagnes_admission.post_campagnes'), $this->donnees(['description' => '']))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');
        $this->assertEmpty(DB::table('campagne')->where('annee_campagne', '2030-2031')->value('description'));
    }

    public function test_la_liste_propose_modifier_et_supprimer(): void
    {
        $k1 = $this->page('campagnes', '/admin/gestion-hebergement/campagnes_admissions/campagnes')['props']['campagnes']['data'][0];

        $this->assertSame(route('admin.hebergement.campagnes_admission.edit_campagne', 'k1'), $k1['urls']['edit']);
        $this->assertSame(route('admin.hebergement.campagnes_admission.destroy_campagne', 'k1'), $k1['urls']['destroy']);
    }

    public function test_formulaire_de_modification_prerempli(): void
    {
        DB::table('cite')->where('id_cite', 'cA')->update(['statut' => 'ferme']);

        $page = $this->page('editCampagne', '/admin/gestion-hebergement/campagnes_admissions/campagnes/k1/modifier', ['id' => 'k1']);

        $this->assertSame('Admin/Hebergement/Campagnes/Edit', $page['component']);
        $campagne = $page['props']['campagne'];
        $this->assertSame('2025-2026', $campagne['annee_campagne']);
        $this->assertSame('Campagne principale 2025', $campagne['libelle']);
        $this->assertSame('2025-09-01', $campagne['periode_debut']);
        $this->assertArrayNotHasKey('date_echeance_demande', $campagne);
        $this->assertArrayNotHasKey('moyenne_min', $campagne);
        $this->assertArrayNotHasKey('admission', $campagne);
        $this->assertSame('admission_readmission', $campagne['type']);
        $this->assertSame(['cA'], $campagne['residences']);
        $this->assertSame(['d1'], $campagne['documents']);
        // La cité liée reste proposée même fermée depuis, pour ne pas la retirer à l'insu de l'utilisateur.
        $this->assertSame(['cA'], array_column($page['props']['residences'], 'id'));
        $this->assertSame(0, $page['props']['dossiers']);
        $this->assertSame(route('admin.hebergement.campagnes_admission.update_campagne', 'k1'), $page['props']['urls']['submit']);
    }

    public function test_modification_d_une_campagne_et_de_ses_liens(): void
    {
        $this->withoutMiddleware();
        DB::table('cite')->insert(['id_cite' => 'cB', 'name' => 'Cité B', 'statut' => 'ouvert']);
        DB::table('document')->insert(['id_document' => 'd3', 'name' => 'Extrait de naissance', 'statut' => 1]);

        $this->put(route('admin.hebergement.campagnes_admission.update_campagne', 'k1'), $this->donnees([
            'libelle' => 'Campagne modifiée',
            'description' => 'Campagne principale modifiée',
            'residences' => ['cB'],
            'documents' => ['d1', 'd3'],
        ]))
            ->assertRedirect(route('admin.hebergement.campagnes_admission.campagnes'))
            ->assertSessionHas('success');

        $campagne = DB::table('campagne')->where('id_campagne', 'k1')->first();
        $this->assertSame('Campagne principale modifiée', $campagne->description);
        $this->assertSame('Campagne modifiée', $campagne->libelle);
        $this->assertSame('2030-2031', $campagne->annee_campagne);
        $this->assertSame(2030, (int) $campagne->annee_debut);
        // Type « admission et réadmission » ; critères et échéances ne passent plus par ce formulaire : valeurs conservées.
        $this->assertSame(1, (int) $campagne->admission);
        $this->assertSame(1, (int) $campagne->readmission);
        $this->assertSame(10.5, (float) $campagne->moyenne_min);
        $this->assertSame('2025-11-15', $campagne->date_echeance_demande);
        $this->assertSame('planifier', $campagne->statut); // dates futures : statut recalculé
        $this->assertSame(['cB'], DB::table('residence_campagne')->where('campagne_id', 'k1')->pluck('cite_id')->all());
        $this->assertEqualsCanonicalizing(['d1', 'd3'], DB::table('document_campagne')->where('id_campagne', 'k1')->pluck('document_id')->all());
        // Le lien conservé n'est pas recréé.
        $this->assertTrue(DB::table('document_campagne')->where('id_document_campagne', 'dc1')->exists());
    }

    public function test_modification_soumise_aux_memes_regles_que_la_creation(): void
    {
        $this->withoutMiddleware();

        $this->from(route('admin.hebergement.campagnes_admission.edit_campagne', 'k1'))
            ->put(route('admin.hebergement.campagnes_admission.update_campagne', 'k1'), $this->donnees(['residences' => [], 'periode_fin' => '2030-08-01']))
            ->assertSessionHasErrors(['residences', 'periode_fin']);
        $this->assertSame('Campagne principale', DB::table('campagne')->where('id_campagne', 'k1')->value('description'));
    }

    public function test_suppression_d_une_campagne_sans_dossier(): void
    {
        $this->withoutMiddleware();

        $this->delete(route('admin.hebergement.campagnes_admission.destroy_campagne', 'k2'))
            ->assertRedirect(route('admin.hebergement.campagnes_admission.campagnes'))
            ->assertSessionHas('success');

        $this->assertNotNull(DB::table('campagne')->where('id_campagne', 'k2')->value('deleted_at'));
        $props = $this->page('campagnes', '/admin/gestion-hebergement/campagnes_admissions/campagnes')['props'];
        $this->assertSame(['k1'], array_column($props['campagnes']['data'], 'id'));
    }

    public function test_une_campagne_avec_des_demandes_ne_peut_pas_etre_supprimee(): void
    {
        $this->withoutMiddleware();
        DB::table('demande')->insert(['id_demande' => 'dm1', 'etudiant_id' => 'e1', 'campagne_id' => 'k1']);

        $this->from(route('admin.hebergement.campagnes_admission.campagnes'))
            ->delete(route('admin.hebergement.campagnes_admission.destroy_campagne', 'k1'))
            ->assertRedirect(route('admin.hebergement.campagnes_admission.campagnes'))
            ->assertSessionHas('error');

        $this->assertNull(DB::table('campagne')->where('id_campagne', 'k1')->value('deleted_at'));
    }

    private function donnees(array $surcharge = []): array
    {
        return array_merge([
            'libelle' => 'Campagne de test',
            'annee_campagne' => '2030-2031',
            'type' => 'admission_readmission',
            'description' => 'Campagne de test complète',
            'periode_debut' => '2030-09-01',
            'periode_fin' => '2030-10-01',
            'residences' => ['cA'],
            'documents' => ['d1'],
        ], $surcharge);
    }

    private function page(string $action, string $uri, array $parametres = []): array
    {
        $request = Request::create($uri, 'GET');
        $request->headers->set('X-Inertia', 'true');
        $request->setUserResolver(fn ($guard = null) => auth('admin')->user());
        $this->app->instance('request', $request);

        return app()->call([app(CampagnesAdmissionController::class), $action], ['request' => $request, ...$parametres])
            ->toResponse($request)->getData(true);
    }
}
