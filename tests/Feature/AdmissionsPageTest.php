<?php

namespace Tests\Feature;

use App\Http\Controllers\admin\immobilier\campagnes_admission\CampagnesAdmissionController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;

/** Pages React des admissions : liste des dossiers et traitement d'une demande (données filtrées selon les droits). */
class AdmissionsPageTest extends \Tests\Support\DossierTestCase
{
    use \Tests\Support\EditionEtudiantTables;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::table('etudiants', function (Blueprint $t) {
            $t->string('matricule')->nullable();
            $t->date('date_inscription')->nullable();
            $t->boolean('is_validation')->default(false);
            $t->boolean('is_rejet')->default(false);
        });
        $this->creerTablesEditionEtudiant();
        Schema::table('admission_derogations', function (Blueprint $t) {
            $t->string('etudiant_id')->nullable();
            $t->timestamps();
        });
        DB::table('etudiants')->update(['statut_inscription' => 'demande']);
        DB::table('etudiants')->where('id_etudiant', 'student')->update(['name' => 'Konan Awa', 'matricule' => 'M-001']);
        $this->dossier();
    }

    /** Admin qui voit les admissions sans les données personnelles ni les documents (et, par défaut, sans l'historique). */
    private function agentLimite(array $permissions = []): void
    {
        $actor = Mockery::mock(\App\Models\User::class)->makePartial();
        $actor->fill(['id_user' => 'limite', 'name' => 'Agent']);
        $actor->shouldReceive('hasPermission')->andReturnUsing(fn ($permission) => in_array($permission, ['hebergement.admissions.view', ...$permissions], true));
        $this->actingAs($actor, 'admin');
    }

    private function page(string $action, array $parametres = [], string $uri = '/admin/gestion-hebergement/campagnes_admissions/liste', array $query = []): array
    {
        $request = Request::create($uri, 'GET', $query);
        $request->headers->set('X-Inertia', 'true');
        $request->setUserResolver(fn ($guard = null) => auth('admin')->user());
        $this->app->instance('request', $request);

        return app()->call([app(CampagnesAdmissionController::class), $action], ['request' => $request, ...$parametres])
            ->toResponse($request)->getData(true);
    }

    public function test_liste_des_dossiers_sans_donnees_personnelles(): void
    {
        $this->agentLimite();

        $page = $this->page('liste', ['etat' => 'nouvelles_demandes']);

        $this->assertSame('Admin/Hebergement/Admissions/Index', $page['component']);
        $props = $page['props'];
        $this->assertSame('nouvelles_demandes', $props['etat']);
        $this->assertSame(['personnel' => false, 'historique' => false], $props['colonnes']);
        $this->assertSame(2, $props['dossiers']['total']);
        $this->assertSame(10, $props['dossiers']['per_page']);
        $this->assertNull($props['dossiers']['data'][0]['personnel']);
        $this->assertNull($props['dossiers']['data'][0]['demandes']);
        $this->assertSame(2, $props['stats']['nouvelles_demandes']);
        $this->assertSame('active', DB::table('campagne')->value('statut'));
        $this->assertArrayNotHasKey('admissionDirecte', $props['urls']);
    }

    public function test_filtre_inconnu_ou_residents_ramene_aux_nouvelles_demandes(): void
    {
        $this->agentLimite();
        DB::table('etudiants')->where('id_etudiant', 'other')->update(['statut_inscription' => 'residents']);

        foreach (['nimporte', 'residents', null] as $etat) {
            $props = $this->page('liste', ['etat' => $etat])['props'];
            $this->assertSame('nouvelles_demandes', $props['etat']);
            // Les étudiants logés ne figurent pas dans la liste des demandes.
            $this->assertSame(['student'], collect($props['dossiers']['data'])->pluck('id')->all());
        }
    }

    public function test_recherche_et_filtre_par_statut_de_la_demande(): void
    {
        $this->agentLimite();

        $parNom = $this->page('liste', ['etat' => 'nouvelles_demandes'], query: ['q' => 'konan'])['props'];
        $this->assertSame(['q' => 'konan', 'etat_dossier' => ''], $parNom['filters']);
        $this->assertSame(['student'], collect($parNom['dossiers']['data'])->pluck('id')->all());
        $this->assertSame(['student'], collect($this->page('liste', ['etat' => 'nouvelles_demandes'], query: ['q' => 'M-001'])['props']['dossiers']['data'])->pluck('id')->all());

        $soumis = $this->page('liste', ['etat' => 'nouvelles_demandes'], query: ['etat_dossier' => 'soumis'])['props'];
        $this->assertSame(['student'], collect($soumis['dossiers']['data'])->pluck('id')->all());
        $this->assertContains('soumis', array_column($soumis['etatsDossier'], 'value'));
        $this->assertSame(0, $this->page('liste', ['etat' => 'nouvelles_demandes'], query: ['etat_dossier' => 'refuse'])['props']['dossiers']['total']);

        // Valeur inconnue : filtre ignoré.
        $inconnu = $this->page('liste', ['etat' => 'nouvelles_demandes'], query: ['etat_dossier' => 'pirate'])['props'];
        $this->assertSame('', $inconnu['filters']['etat_dossier']);
        $this->assertSame(2, $inconnu['dossiers']['total']);
    }

    public function test_traitement_d_une_demande_avec_droits_limites(): void
    {
        $this->agentLimite();

        $page = $this->page('show', ['id' => 'student'], '/admin/gestion-hebergement/campagnes_admissions/student');

        $this->assertSame('Admin/Hebergement/Admissions/Show', $page['component']);
        $props = $page['props'];
        $this->assertSame('Konan Awa', $props['etudiant']['name']);
        $this->assertSame('soumis', $props['demande']['etat']);
        // Blocs réservés : non envoyés au navigateur sans le droit correspondant.
        $this->assertNull($props['eligibilite']);
        $this->assertNull($props['documents']);
        $this->assertNull($props['profil']);
        $this->assertNull($props['historique']);
        $this->assertNull($props['discipline']);
        // Suivi du dossier : pas d'action sans le droit de transition, historique masqué sans `admissions.history`.
        $this->assertSame([], $props['suivi']['actions']);
        $this->assertNull($props['suivi']['historique']);
        $this->assertSame([], $props['derogations']['liste']);
        $this->assertNull($props['derogations']['demander']);
        // Sans le droit de modifier : ni bouton, ni valeurs, ni référentiels.
        $this->assertNull($props['urls']['modifier']);
        $this->assertNull($props['edition']);
        $this->assertNull($props['options']);
    }

    public function test_l_administration_modifie_les_informations_saisies_par_l_etudiant(): void
    {
        $this->agentLimite(['hebergement.admissions.update']);

        $props = $this->page('show', ['id' => 'student'], '/admin/gestion-hebergement/campagnes_admissions/student')['props'];
        $this->assertSame(route('admin.hebergement.campagnes_admission.update_etudiant', ['etudiant' => 'student']), $props['urls']['modifier']);
        $this->assertSame('M-001', $props['edition']['matricule']);
        $this->assertTrue($props['edition']['avec_demande']);
        $this->assertCount(2, $props['options']['niveaux']);

        $this->put($props['urls']['modifier'], ['name' => 'Konan Awa Marie', 'genre_id' => 2, 'matricule' => 'M-001', 'telephone1' => '0700000001',
            'niveau_etude_actuel_code' => 'L1', 'moyenne' => '12'])
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame('Konan Awa Marie', DB::table('etudiants')->where('id_etudiant', 'student')->value('name'));
        $demande = DB::table('demande')->where('etudiant_id', 'student')->first();
        $this->assertSame('L1', $demande->niveau_etude_actuel_code);
        $this->assertEquals(12, (float) $demande->moyenne);
        $this->assertSame('soumis', $demande->etat_dossier);
    }

    public function test_informations_saisies_regroupees_par_etape_du_formulaire(): void
    {
        $this->agentLimite(['hebergement.admissions.personal']);
        Schema::table('demande', function (Blueprint $t) {
            $t->string('ville_bac')->nullable();
        });
        DB::table('niveau_etude')->insert(['id_niveau_etude' => 'TLE', 'name' => 'Terminale']);
        DB::table('etudiants')->where('id_etudiant', 'student')->update(['regions_id' => 1, 'ville_id' => 10, 'distance' => 35, 'nationalite' => 'IVOIRIENNE']);
        $demande = DB::table('demande')->where('etudiant_id', 'student');

        // Étudiant : moyenne sur 20 et crédits validés.
        $demande->update(['niveau_etude_precedent_code' => 'L1', 'niveau_etude_actuel_code' => 'L2', 'filiere_code' => 1, 'moyenne' => 12.5, 'credit_valider' => 48, 'commentaires' => 'Orphelin.']);
        $profil = $this->page('show', ['id' => 'student'], '/admin/gestion-hebergement/campagnes_admissions/student')['props']['profil'];
        $this->assertSame(['identite', 'etudes', 'situation', 'adresse'], array_keys($profil));
        $this->assertSame('Licence 1', $profil['etudes']['niveau_precedent']);
        $this->assertSame('Licence 2', $profil['etudes']['niveau']);
        $this->assertFalse($profil['etudes']['redoublant']);
        $this->assertFalse($profil['etudes']['bachelier']);
        $this->assertSame('Informatique', $profil['etudes']['filiere']);
        $this->assertEquals(12.5, $profil['etudes']['moyenne']);
        $this->assertSame(48, $profil['etudes']['credits']);
        $this->assertNull($profil['etudes']['note_bac']);
        $this->assertSame('Orphelin.', $profil['situation']['observations']);
        $this->assertSame('Gontougo', $profil['adresse']['region']);
        $this->assertSame('Bondoukou', $profil['adresse']['ville']);
        $this->assertEquals(35, $profil['adresse']['distance']);

        // Nouveau bachelier : la moyenne enregistrée (sur 20) est restituée en note au bac sur 400.
        $demande->update(['niveau_etude_precedent_code' => 'TLE', 'niveau_etude_actuel_code' => 'L1', 'moyenne' => 12.25, 'credit_valider' => null, 'ville_bac' => 'Bouaké']);
        $etudes = $this->page('show', ['id' => 'student'], '/admin/gestion-hebergement/campagnes_admissions/student')['props']['profil']['etudes'];
        $this->assertTrue($etudes['bachelier']);
        $this->assertEquals(245, $etudes['note_bac']);
        $this->assertNull($etudes['credits']);
        $this->assertSame('Bouaké', $etudes['ville_bac']);
    }

    /** Agent qui voit l'étude d'éligibilité (données personnelles, documents et historique). */
    private function agentEligibilite(array $permissions = []): void
    {
        $this->agentLimite(['hebergement.admissions.personal', 'hebergement.admissions.documents', 'hebergement.admissions.history', ...$permissions]);
        foreach (['2026_11_11_000001_create_campagne_regles_table.php', '2026_11_11_000002_add_volet_to_campagne_regles.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        Schema::create('document_etudiant', function (Blueprint $t) {
            $t->string('id_document')->primary();
            $t->string('etudiant_id');
            $t->timestamps();
        });
        Schema::table('niveau_etude', fn (Blueprint $t) => $t->string('abreviation')->nullable());
        DB::table('niveau_etude')->insert(['id_niveau_etude' => 'TLE', 'name' => 'Terminale', 'abreviation' => 'TLE']);
        DB::table('etudiants')->where('id_etudiant', 'student')->update(['nationalite' => 'IVOIRIENNE', 'date_naissance' => now()->subYears(25)->toDateString()]);
    }

    private function enVigueur(string $volet, array $parametres): void
    {
        \App\Models\CampagneRegles::create(['campagne_id' => 'campaign', 'volet' => $volet, 'version' => 1, 'statut' => 'en_vigueur', 'parametres' => $parametres]);
    }

    private function eligibilite(): array
    {
        return $this->page('show', ['id' => 'student'], '/admin/gestion-hebergement/campagnes_admissions/student')['props']['eligibilite'];
    }

    public function test_criteres_d_admission_compares_aux_valeurs_saisies(): void
    {
        $this->agentEligibilite();
        // Nouveau bachelier : 9/20 enregistré = 180/400.
        DB::table('demande')->where('etudiant_id', 'student')->update(['niveau_etude_precedent_code' => 'TLE', 'niveau_etude_actuel_code' => 'L1', 'moyenne' => 9]);

        // Sans critères appliqués : modèle CROU (note du bac ≥ 200/400, internationaux non acceptés, aucun âge maximum).
        $eligibilite = $this->eligibilite();
        $this->assertSame('admission', $eligibilite['volet']);
        $this->assertSame('modele', $eligibilite['origine']);
        // Pas de sanction à l'admission : la discipline ne concerne que les anciens résidents (réadmission).
        $this->assertSame(['Note du baccalauréat', 'Étudiants internationaux'], array_column($eligibilite['regles'], 'label'));
        $this->assertSame(['≥ 200/400', '180/400', 'nok'], [$eligibilite['regles'][0]['campagne'], $eligibilite['regles'][0]['etudiant'], $eligibilite['regles'][0]['etat']]);
        $this->assertSame('ok', $eligibilite['regles'][1]['etat']);
        $this->assertSame(1, $eligibilite['non_conformes']);

        // Critères en vigueur : seuil du bac abaissé, âge maximum de la Licence 1 dépassé ; crédits ignorés (autre public).
        $this->enVigueur('admission', ['admission' => ['merite' => ['moyenne_bac_desc' => ['actif' => true, 'seuil' => 150], 'credits_desc' => ['actif' => true, 'seuil' => 30]]]]);
        $this->enVigueur('general', ['age_max' => [['niveau' => 'L1', 'age' => 22]]]);
        $eligibilite = $this->eligibilite();
        $this->assertSame('en_vigueur', $eligibilite['origine']);
        $regles = collect($eligibilite['regles'])->keyBy('label');
        $this->assertSame('ok', $regles['Note du baccalauréat']['etat']);
        $this->assertFalse($regles->has('Crédits validés'));
        $this->assertSame(['≤ 22 ans', '25 ans', 'nok'], array_values(collect($regles['Âge maximum (Licence 1)'])->only(['campagne', 'etudiant', 'etat'])->all()));
    }

    public function test_criteres_de_readmission_pour_un_ancien_resident(): void
    {
        $this->agentEligibilite(['hebergement.discipline.view']);
        // Ancien résident : logé lors d'une autre campagne.
        DB::table('assignation_chambres')->insert(['id_assignation' => 'ancienne', 'etudiant_id' => 'student', 'chambre_id' => 'room', 'campagne_id' => 'autre', 'statut' => 'terminee']);
        DB::table('demande')->where('etudiant_id', 'student')->update(['niveau_etude_precedent_code' => 'L1', 'niveau_etude_actuel_code' => 'L2', 'filiere_code' => '1', 'credit_valider' => 48]);
        $this->enVigueur('readmission', ['readmission' => [
            'loyers' => ['actif' => false, 'mois_exiges' => 10],
            'credits' => ['actif' => true, 'seuil_defaut' => 100, 'filieres' => [['filiere_id' => '1', 'seuil' => 80, 'concours' => true]]],
            'conduite' => ['actif' => true, 'note_min' => 10, 'sanctions' => ['suspension', 'expulsion']],
            'age_actif' => true,
        ]]);

        $eligibilite = $this->eligibilite();

        $this->assertSame('readmission', $eligibilite['volet']);
        $regles = collect($eligibilite['regles'])->keyBy('label');
        $this->assertSame(['Demande de réadmission', 'Crédits validés', 'Note de conduite', 'Étudiants internationaux', 'Sanction bloquante'], $regles->keys()->all());
        // Seuil propre à la filière (école sur concours) : 48/60 = 80 %.
        $this->assertSame('≥ 80 % des crédits (filière Informatique, école sur concours)', $regles['Crédits validés']['campagne']);
        $this->assertSame(['48/60 (80 %)', 'ok'], [$regles['Crédits validés']['etudiant'], $regles['Crédits validés']['etat']]);
        $this->assertSame('ok', $regles['Note de conduite']['etat']);
        $this->assertSame('ok', $regles['Sanction bloquante']['etat']);
        $this->assertSame(0, $eligibilite['non_conformes']);
    }

    public function test_modification_refusee_sans_le_droit_de_modifier_les_admissions(): void
    {
        $this->agentLimite();

        $this->put(route('admin.hebergement.campagnes_admission.update_etudiant', ['etudiant' => 'student']), ['name' => 'Pirate'])
            ->assertForbidden();
        $this->assertSame('Konan Awa', DB::table('etudiants')->where('id_etudiant', 'student')->value('name'));
    }

    public function test_suivi_propose_les_transitions_permises(): void
    {
        $this->agentLimite(['hebergement.admissions.validate', 'hebergement.admissions.history']);

        $page = $this->page('show', ['id' => 'student'], '/admin/gestion-hebergement/campagnes_admissions/student');

        $suivi = $page['props']['suivi'];
        // Depuis « soumis », l'agent peut passer à : incomplet, éligible, refusé, annulé (pas « expiré », réservé au système).
        $this->assertSame(['incomplet', 'eligible', 'refuse', 'annule'], array_column($suivi['actions'], 'etat'));
        $this->assertNotNull($suivi['transition']);
        $this->assertNotEmpty($suivi['historique']);
    }
}
