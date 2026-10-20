<?php

namespace Tests\Feature;

use App\Http\Controllers\admin\immobilier\residents_contrat\ResidentContratControler;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;

/** Page React « Résidents & contrats » : filtres, chambre confirmée et actions selon les droits. */
class ResidentsPageTest extends \Tests\Support\DossierTestCase
{
    use \Tests\Support\EditionEtudiantTables;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::table('etudiants', function (Blueprint $t) {
            $t->date('date_inscription')->nullable();
            foreach (['matricule', 'numero_etudiant', 'telephone1', 'telephone2', 'email1', 'email2', 'lieu_naissance', 'nationalite',
                'adresse', 'name_tuteur', 'telephone_tuteur', 'adresse_tuteur'] as $colonne) {
                $t->string($colonne)->nullable();
            }
            $t->date('date_naissance')->nullable();
        });
        $this->creerTablesEditionEtudiant();
        Schema::table('campagne', function (Blueprint $t) {
            $t->string('annee_campagne')->nullable();
        });
        DB::table('etudiants')->update(['statut_inscription' => 'residents']);
        DB::table('etudiants')->where('id_etudiant', 'student')->update(['name' => 'Konan Awa']);
        $this->dossier();
        DB::table('assignation_chambres')->insert([
            ['id_assignation' => 'a1', 'etudiant_id' => 'student', 'chambre_id' => 'room', 'campagne_id' => 'campaign', 'statut' => 'confirmee', 'created_at' => now()],
            ['id_assignation' => 'a2', 'etudiant_id' => 'other', 'chambre_id' => 'room', 'campagne_id' => 'campaign', 'statut' => 'annulee', 'created_at' => now()],
        ]);
    }

    private function agent(array $permissions): void
    {
        $actor = Mockery::mock(\App\Models\User::class)->makePartial();
        $actor->fill(['id_user' => 'limite', 'name' => 'Agent']);
        $actor->shouldReceive('hasPermission')->andReturnUsing(fn ($permission) => in_array($permission, $permissions, true));
        $this->actingAs($actor, 'admin');
    }

    private function page(?string $etat = null, array $query = []): array
    {
        $request = Request::create('/admin/gestion-hebergement/residents_contrat/liste', 'GET', $query);
        $request->headers->set('X-Inertia', 'true');
        $request->setUserResolver(fn ($guard = null) => auth('admin')->user());
        $this->app->instance('request', $request);

        return app()->call([app(ResidentContratControler::class), 'liste'], ['request' => $request, 'etat' => $etat])
            ->toResponse($request)->getData(true);
    }

    public function test_liste_des_residents_avec_chambre_confirmee(): void
    {
        $this->agent(['hebergement.residents.view', 'hebergement.attributions.assign', 'hebergement.attributions.view']);

        $page = $this->page('residents');

        $this->assertSame('Admin/Hebergement/Residents/Index', $page['component']);
        $props = $page['props'];
        $this->assertSame('residents', $props['etat']);
        $this->assertSame(['residents' => 2, 'en_verification' => 0], $props['stats']);
        $this->assertSame(['personnel' => false, 'historique' => false], $props['colonnes']);
        $this->assertSame(10, $props['residents']['per_page']);

        $lignes = collect($props['residents']['data'])->keyBy('id');
        $this->assertSame('room', $lignes['student']['chambre']['numero']);
        $this->assertNull($lignes['other']['chambre']);
        $this->assertNull($lignes['student']['personnel']);

        $urls = $lignes['student']['urls'];
        $this->assertStringContainsString('/contrat', $urls['contrat']);
        $this->assertArrayNotHasKey('allouer', $urls);
        $this->assertNotNull($urls['dossier']);
        $this->assertNull($urls['traiter']);
        $this->assertNotNull($props['urls']['attributions']);
    }

    public function test_actions_masquees_sans_droits_et_filtre_inconnu(): void
    {
        $this->agent(['hebergement.residents.view']);

        $props = $this->page('nimporte')['props'];

        $this->assertSame('residents', $props['etat']);
        $urls = collect($props['residents']['data'])->firstWhere('id', 'student')['urls'];
        $this->assertArrayNotHasKey('allouer', $urls);
        $this->assertNotNull($urls['contrat']);
        $this->assertNull($props['urls']['attributions']);
        $this->assertNull($props['urls']['admissionDirecte']);
        // Sans le droit de modifier : ni action, ni valeurs du formulaire, ni référentiels.
        $this->assertNull($urls['modifier']);
        $this->assertNull(collect($props['residents']['data'])->firstWhere('id', 'student')['edition']);
        $this->assertNull($props['options']);
    }

    public function test_modification_des_informations_d_un_etudiant(): void
    {
        $this->agent(['hebergement.residents.view', 'hebergement.residents.update', 'hebergement.admissions.create']);
        DB::table('etudiants')->where('id_etudiant', 'other')->update(['matricule' => 'M-002', 'telephone1' => '0700000002']);

        $props = $this->page('residents')['props'];
        $ligne = collect($props['residents']['data'])->firstWhere('id', 'student');
        $this->assertStringContainsString('/residents_contrat/etudiants/student', $ligne['urls']['modifier']);
        $this->assertSame('Konan Awa', $ligne['edition']['name']);
        $this->assertTrue($ligne['edition']['avec_demande']);
        $this->assertCount(2, $props['options']['genres']);
        $this->assertSame(['id' => '10', 'name' => 'Bondoukou', 'region_id' => '1'], $props['options']['villes'][0]);
        $this->assertNotNull($props['urls']['admissionDirecte']);

        $donnees = ['name' => 'Konan Awa Marie', 'genre_id' => 2, 'matricule' => 'M-001', 'telephone1' => '0700000001',
            'email1' => 'awa@example.com', 'date_naissance' => '2003-05-12', 'name_tuteur' => 'Konan Yao'];
        $this->put(route('admin.hebergement.residents_contrat.update_etudiant', ['etudiant' => 'student']), $donnees)
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $etudiant = DB::table('etudiants')->where('id_etudiant', 'student')->first();
        $this->assertSame('Konan Awa Marie', $etudiant->name);
        $this->assertSame('awa@example.com', $etudiant->email1);
        $this->assertSame('Konan Yao', $etudiant->name_tuteur);
        $this->assertSame('residents', $etudiant->statut_inscription);

        // Matricule et téléphone déjà utilisés par un autre étudiant : refusés.
        $this->put(route('admin.hebergement.residents_contrat.update_etudiant', ['etudiant' => 'student']),
            ['matricule' => 'M-002', 'telephone1' => '0700000002'] + $donnees)
            ->assertSessionHasErrors(['matricule', 'telephone1']);
    }

    public function test_modification_de_toutes_les_informations_et_de_la_demande(): void
    {
        $this->agent(['hebergement.residents.view', 'hebergement.residents.update']);
        $etatAvant = DB::table('demande')->where('etudiant_id', 'student')->value('etat_dossier');

        $donnees = ['name' => 'Konan Awa', 'genre_id' => 2, 'matricule' => 'M-001', 'telephone1' => '0700000001',
            'groupe_sanguin_id' => 1, 'type_piece_id' => 1, 'numero_piece' => 'CI0001', 'date_expiration' => '2034-01-10',
            'regions_id' => 1, 'ville_id' => 10, 'distance' => 120, 'annee_academique_actuel' => '2025-2026',
            'boursier' => true, 'type_bourse' => 'Nationale', 'allergies' => true, 'maladies_chroniques' => false, 'autiste' => false,
            'annee_academique_precedent' => '2024-2025', 'niveau_etude_precedent_code' => 'L1', 'niveau_etude_actuel_code' => 'L2',
            'filiere_code' => '1', 'moyenne' => '13.5', 'credit_valider' => 90];
        $this->put(route('admin.hebergement.residents_contrat.update_etudiant', ['etudiant' => 'student']), $donnees)
            ->assertSessionHasNoErrors();

        $etudiant = DB::table('etudiants')->where('id_etudiant', 'student')->first();
        $this->assertSame('CI0001', $etudiant->numero_piece);
        $this->assertSame(10, (int) $etudiant->ville_id);
        $this->assertSame(1, (int) $etudiant->boursier);
        $this->assertSame('Nationale', $etudiant->type_bourse);
        $this->assertSame(1, (int) $etudiant->allergies);
        $demande = DB::table('demande')->where('etudiant_id', 'student')->first();
        $this->assertSame('L2', $demande->niveau_etude_actuel_code);
        $this->assertSame('1', (string) $demande->filiere_code);
        $this->assertEquals(13.5, (float) $demande->moyenne);
        $this->assertSame(90, (int) $demande->credit_valider);
        $this->assertSame(1, (int) $demande->boursier);
        // L'état du dossier n'est jamais modifié par ce formulaire.
        $this->assertSame($etatAvant, $demande->etat_dossier);

        // Ville hors de la région, date d'expiration invalide, niveau inconnu : refusés.
        $this->put(route('admin.hebergement.residents_contrat.update_etudiant', ['etudiant' => 'student']),
            ['ville_id' => 20, 'date_expiration' => 'pas-une-date', 'niveau_etude_actuel_code' => 'X'] + $donnees)
            ->assertSessionHasErrors(['ville_id', 'date_expiration', 'niveau_etude_actuel_code']);

        // Plus boursier : le type de bourse est effacé.
        $this->put(route('admin.hebergement.residents_contrat.update_etudiant', ['etudiant' => 'student']), ['boursier' => false] + $donnees)
            ->assertSessionHasNoErrors();
        $this->assertNull(DB::table('etudiants')->where('id_etudiant', 'student')->value('type_bourse'));
    }

    public function test_modification_refusee_sans_droit(): void
    {
        $this->agent(['hebergement.residents.view']);

        $this->put(route('admin.hebergement.residents_contrat.update_etudiant', ['etudiant' => 'student']), ['name' => 'Pirate'])
            ->assertForbidden();
        $this->assertSame('Konan Awa', DB::table('etudiants')->where('id_etudiant', 'student')->value('name'));
    }

    public function test_dossiers_en_verification_proposent_le_traitement(): void
    {
        $this->agent(['hebergement.residents.view']);
        DB::table('etudiants')->where('id_etudiant', 'other')->update(['statut_inscription' => 'en_verification']);

        $props = $this->page('en_verification')['props'];

        $this->assertSame(1, $props['residents']['total']);
        $this->assertNotNull($props['residents']['data'][0]['urls']['traiter']);
        // Pas encore confirmé par l'étudiant : pas d'attribution.
        $this->assertNull($props['residents']['data'][0]['urls']['attribuer']);
    }

    public function test_dossier_confirme_par_l_etudiant_propose_l_attribution_de_chambre(): void
    {
        $dossier = $this->dossier('other');
        $this->workflow->admin($dossier->getKey(), 'eligible');
        $this->confirm('other');
        $this->assertSame('en_verification', DB::table('etudiants')->where('id_etudiant', 'other')->value('statut_inscription'));

        $this->agent(['hebergement.residents.view', 'hebergement.attributions.assign']);
        $urls = $this->page('en_verification')['props']['residents']['data'][0]['urls'];
        $this->assertSame(route('admin.allocation.allocate-form', ['campagneId' => 'campaign', 'etudiant_id' => 'other']), $urls['attribuer']);

        // Sans le droit d'attribuer : pas d'icône.
        $this->agent(['hebergement.residents.view']);
        $this->assertNull($this->page('en_verification')['props']['residents']['data'][0]['urls']['attribuer']);
    }

    public function test_recherche_par_nom_ou_numero_de_chambre(): void
    {
        $this->agent(['hebergement.residents.view']);

        $props = $this->page('residents', ['q' => 'konan'])['props'];
        $this->assertSame(['q' => 'konan'], $props['filters']);
        $this->assertSame(['student'], collect($props['residents']['data'])->pluck('id')->all());

        // Chambre « room » : seule l'affectation confirmée compte (celle de « other » est annulée).
        $parChambre = $this->page('residents', ['q' => 'room'])['props']['residents']['data'];
        $this->assertSame(['student'], collect($parChambre)->pluck('id')->all());

        $this->assertSame(0, $this->page('residents', ['q' => 'introuvable'])['props']['residents']['total']);
    }
}
