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

        $page = $this->page('liste', ['etat' => 'toutes']);

        $this->assertSame('Admin/Hebergement/Admissions/Index', $page['component']);
        $props = $page['props'];
        $this->assertSame('toutes', $props['etat']);
        $this->assertSame(['personnel' => false, 'historique' => false], $props['colonnes']);
        // « other » n'a déposé aucune demande : il ne figure pas dans la liste.
        $this->assertSame(1, $props['dossiers']['total']);
        $this->assertSame(10, $props['dossiers']['per_page']);
        $this->assertNull($props['dossiers']['data'][0]['personnel']);
        $this->assertNull($props['dossiers']['data'][0]['demandes']);
        $this->assertSame(1, $props['stats']['toutes']);
        $this->assertSame('active', DB::table('campagne')->value('statut'));
        $this->assertArrayNotHasKey('admissionDirecte', $props['urls']);
    }

    public function test_filtre_inconnu_ou_residents_ramene_a_toutes_les_demandes(): void
    {
        $this->agentLimite();
        DB::table('etudiants')->where('id_etudiant', 'other')->update(['statut_inscription' => 'residents']);

        foreach (['nimporte', 'residents', null] as $etat) {
            $props = $this->page('liste', ['etat' => $etat])['props'];
            $this->assertSame('toutes', $props['etat']);
            // Les étudiants logés ne figurent pas dans la liste des demandes.
            $this->assertSame(['student'], collect($props['dossiers']['data'])->pluck('id')->all());
        }
    }

    public function test_recherche_et_filtre_par_statut_de_la_demande(): void
    {
        $this->agentLimite();

        $parNom = $this->page('liste', ['etat' => 'toutes'], query: ['q' => 'konan'])['props'];
        $this->assertSame(['q' => 'konan', 'etat_dossier' => ''], $parNom['filters']);
        $this->assertSame(['student'], collect($parNom['dossiers']['data'])->pluck('id')->all());
        $this->assertSame(['student'], collect($this->page('liste', ['etat' => 'toutes'], query: ['q' => 'M-001'])['props']['dossiers']['data'])->pluck('id')->all());

        $soumis = $this->page('liste', ['etat' => 'toutes'], query: ['etat_dossier' => 'soumis'])['props'];
        $this->assertSame(['student'], collect($soumis['dossiers']['data'])->pluck('id')->all());
        $this->assertContains('soumis', array_column($soumis['etatsDossier'], 'value'));
        $this->assertSame(0, $this->page('liste', ['etat' => 'toutes'], query: ['etat_dossier' => 'refuse'])['props']['dossiers']['total']);

        // Valeur inconnue : filtre ignoré.
        $inconnu = $this->page('liste', ['etat' => 'toutes'], query: ['etat_dossier' => 'pirate'])['props'];
        $this->assertSame('', $inconnu['filters']['etat_dossier']);
        $this->assertSame(1, $inconnu['dossiers']['total']);
    }

    public function test_demandes_recevables_et_rejetees_selon_les_criteres_de_la_campagne(): void
    {
        $this->agentEligibilite();
        $demande = DB::table('demande')->where('etudiant_id', 'student');
        $ids = fn (string $etat) => collect($this->page('liste', ['etat' => $etat])['props']['dossiers']['data'])->pluck('id')->all();

        // Nouveau bachelier : 9/20 = 180/400 sous le seuil du modèle CROU (200/400) → rejetée.
        $demande->update(['niveau_etude_precedent_code' => 'TLE', 'niveau_etude_actuel_code' => 'L1', 'moyenne' => 9]);
        $props = $this->page('liste', ['etat' => 'rejetees'])['props'];
        $this->assertSame('rejetees', $props['etat']);
        $this->assertSame(['student'], collect($props['dossiers']['data'])->pluck('id')->all());
        $this->assertSame(['toutes' => 1, 'recevables' => 0, 'rejetees' => 1, 'refusees' => 0], $props['stats']);
        $this->assertSame([], $ids('recevables'));

        // 12/20 = 240/400 : tous les critères conformes → recevable.
        $demande->update(['moyenne' => 12]);
        $this->assertSame(['student'], $ids('recevables'));
        $this->assertSame([], $ids('rejetees'));
        $this->assertSame(['student'], $ids('toutes'));

        // Information manquante (nationalité non renseignée) sans critère non conforme : ni recevable ni rejetée.
        DB::table('etudiants')->where('id_etudiant', 'student')->update(['nationalite' => '']);
        $this->assertSame([], $ids('recevables'));
        $this->assertSame([], $ids('rejetees'));
        $this->assertSame(['student'], $ids('toutes'));

        // Refusée par un agent pendant la validation : sort des listes recevables / irrecevables, reste dans « Toutes ».
        DB::table('etudiants')->where('id_etudiant', 'student')->update(['nationalite' => 'IVOIRIENNE']);
        $id = (string) $demande->value('id_demande');
        $refus = fn (string $etat, string $acteur) => [
            $demande->update(['etat_dossier' => $etat]),
            DB::table('dossier_transitions')->insert(['demande_id' => $id, 'ancien_etat' => 'soumis', 'nouvel_etat' => $etat, 'acteur_type' => $acteur, 'created_at' => now()]),
        ];
        $refus('refuse', 'admin');
        $props = $this->page('liste', ['etat' => 'refusees'])['props'];
        $this->assertSame('refusees', $props['etat']);
        $this->assertSame(['student'], collect($props['dossiers']['data'])->pluck('id')->all());
        $this->assertSame(['toutes' => 1, 'recevables' => 0, 'rejetees' => 0, 'refusees' => 1], $props['stats']);
        $this->assertSame([], $ids('recevables'));

        // Refus automatique (barème) ou annulation par l'étudiant : pas une décision de l'administration.
        $refus('refuse', 'systeme');
        $this->assertSame([], $ids('refusees'));
        $this->assertSame(['student'], $ids('recevables'));
        $refus('annule', 'etudiant');
        $this->assertSame([], $ids('refusees'));
        $refus('annule', 'admin');
        $this->assertSame(['student'], $ids('refusees'));

        // Anciens liens : « en_attente » → recevables, « annuler » → rejetées, « nouvelles_demandes » → toutes.
        $this->assertSame('recevables', $this->page('liste', ['etat' => 'en_attente'])['props']['etat']);
        $this->assertSame('rejetees', $this->page('liste', ['etat' => 'annuler'])['props']['etat']);
        $this->assertSame('toutes', $this->page('liste', ['etat' => 'nouvelles_demandes'])['props']['etat']);
    }

    public function test_demande_irrecevable_rendue_recevable_par_derogation(): void
    {
        $this->agentEligibilite(['hebergement.admissions.eligibility', 'hebergement.admissions.create']);
        $ids = fn (string $etat) => collect($this->page('liste', ['etat' => $etat])['props']['dossiers']['data'])->pluck('id')->all();
        // Nouveau bachelier : 9/20 = 180/400 sous le seuil du modèle CROU (200/400) → irrecevable.
        DB::table('demande')->where('etudiant_id', 'student')->update(['niveau_etude_precedent_code' => 'TLE', 'niveau_etude_actuel_code' => 'L1', 'moyenne' => 9]);
        $id = (string) DB::table('demande')->where('etudiant_id', 'student')->value('id_demande');

        $props = $this->page('show', ['id' => 'student'], '/admin/gestion-hebergement/campagnes_admissions/student')['props'];
        $this->assertNotNull($props['derogations']['demander']);
        $this->assertNull($props['eligibilite']['derogation']);

        // Dérogation en attente : la demande reste irrecevable ; approuvée : elle devient recevable.
        DB::table('admission_derogations')->insert(['id' => 'derog', 'demande_id' => $id, 'etudiant_id' => 'student', 'status' => 'pending']);
        $this->assertSame(['student'], $ids('rejetees'));
        DB::table('admission_derogations')->update(['status' => 'approved', 'decided_at' => '2026-11-18 10:00:00']);
        $this->assertSame(['student'], $ids('recevables'));
        $this->assertSame([], $ids('rejetees'));

        $props = $this->page('show', ['id' => 'student'], '/admin/gestion-hebergement/campagnes_admissions/student')['props'];
        $this->assertSame(1, $props['eligibilite']['non_conformes']);
        $this->assertSame('2026-11-18 10:00', $props['eligibilite']['derogation']['date']);
        $this->assertNull($props['derogations']['demander']);
    }

    public function test_pas_de_derogation_pour_une_demande_recevable(): void
    {
        $this->agentEligibilite(['hebergement.admissions.eligibility', 'hebergement.admissions.create']);
        DB::table('demande')->where('etudiant_id', 'student')->update(['niveau_etude_precedent_code' => 'TLE', 'niveau_etude_actuel_code' => 'L1', 'moyenne' => 12]);

        $this->assertNull($this->page('show', ['id' => 'student'], '/admin/gestion-hebergement/campagnes_admissions/student')['props']['derogations']['demander']);
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

    /** Mêmes critères que le formulaire de demande : matricule facultatif ; nouveau bachelier : note au bac /400, ni matricule ni crédits. */
    public function test_modification_avec_les_criteres_du_formulaire_de_demande(): void
    {
        $this->agentLimite(['hebergement.admissions.update']);
        // Ordre des niveaux : Terminale, puis Licence 1, Licence 2.
        DB::table('niveau_etude')->insert(['id_niveau_etude' => 'TLE', 'name' => 'Terminale', 'ordre' => 1]);
        DB::table('niveau_etude')->where('id_niveau_etude', 'L1')->update(['ordre' => 2]);
        DB::table('niveau_etude')->where('id_niveau_etude', 'L2')->update(['ordre' => 3]);
        DB::table('etudiants')->where('id_etudiant', 'student')->update(['matricule' => null]);
        $url = route('admin.hebergement.campagnes_admission.update_etudiant', ['etudiant' => 'student']);
        $base = ['name' => 'Konan Awa', 'genre_id' => 2, 'telephone1' => '0700000001', 'matricule' => ''];
        $demande = fn () => DB::table('demande')->where('etudiant_id', 'student')->first();

        // Matricule non renseigné au départ : la modification passe sans matricule.
        $this->put($url, $base + ['niveau_etude_precedent_code' => 'L1', 'niveau_etude_actuel_code' => 'L2', 'moyenne' => '12', 'credit_valider' => '48'])
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertNull(DB::table('etudiants')->where('id_etudiant', 'student')->value('matricule'));
        $this->assertSame(48, (int) $demande()->credit_valider);

        // Moyenne sur 20 (entre 1 et 20) ; niveau actuel inférieur au précédent refusé.
        $this->put($url, $base + ['niveau_etude_precedent_code' => 'L2', 'niveau_etude_actuel_code' => 'L1', 'moyenne' => '25'])
            ->assertSessionHasErrors(['moyenne', 'niveau_etude_actuel_code']);

        // Nouveau bachelier : note au bac sur 400 enregistrée sur 20, crédits effacés, matricule ignoré.
        $this->put($url, $base + ['matricule' => 'IGNORE', 'niveau_etude_precedent_code' => 'TLE', 'niveau_etude_actuel_code' => 'L1', 'moyenne' => '245', 'credit_valider' => '30'])
            ->assertSessionHasNoErrors();
        $this->assertEquals(12.25, (float) $demande()->moyenne);
        $this->assertNull($demande()->credit_valider);
        $this->assertNull(DB::table('etudiants')->where('id_etudiant', 'student')->value('matricule'));
        // La note est restituée sur 400 dans le formulaire ; la Terminale est signalée comme niveau d'un nouveau bachelier.
        $props = $this->page('show', ['id' => 'student'], '/admin/gestion-hebergement/campagnes_admissions/student')['props'];
        $this->assertSame('245', $props['edition']['moyenne']);
        $this->assertTrue(collect($props['options']['niveaux'])->firstWhere('id', 'TLE')['terminale']);

        // La Terminale n'est pas un niveau actuel possible.
        $this->put($url, $base + ['niveau_etude_precedent_code' => 'TLE', 'niveau_etude_actuel_code' => 'TLE', 'moyenne' => '245'])
            ->assertSessionHasErrors(['niveau_etude_actuel_code']);
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

    public function test_listes_provisoires_d_admission_et_de_readmission(): void
    {
        $this->agentEligibilite();
        (require database_path('migrations/2026_11_17_000001_add_ville_bac_to_demande.php'))->up();
        foreach (['ancien' => 'Bamba Ali', 'ancien2' => 'Coulibaly Ines'] as $id => $nom) {
            DB::table('etudiants')->insert(['id_etudiant' => $id, 'genre_id' => 1, 'statut_inscription' => 'demande', 'name' => $nom]);
            $this->dossier($id);
            // Anciens résidents : logés lors d'une autre campagne.
            DB::table('assignation_chambres')->insert(['id_assignation' => 'a-'.$id, 'etudiant_id' => $id, 'chambre_id' => 'room', 'campagne_id' => 'autre', 'statut' => 'terminee']);
        }
        $this->dossier('other');
        DB::table('etudiants')->where('id_etudiant', 'other')->update(['name' => 'Yao Marc']);
        DB::table('etudiants')->whereIn('id_etudiant', ['other', 'ancien', 'ancien2'])->update(['nationalite' => 'IVOIRIENNE', 'date_naissance' => now()->subYears(20)->toDateString()]);
        $demande = fn (string $id) => DB::table('demande')->where('etudiant_id', $id);
        // Admission : même note du bac (240/400) ; le bac obtenu hors de la ville de référence (Bondoukou) passe devant.
        $demande('student')->update(['niveau_etude_precedent_code' => 'TLE', 'niveau_etude_actuel_code' => 'L1', 'moyenne' => 12, 'ville_bac' => 'Bondoukou']);
        $demande('other')->update(['niveau_etude_precedent_code' => 'TLE', 'niveau_etude_actuel_code' => 'L1', 'moyenne' => 12, 'ville_bac' => 'Abidjan']);
        // Réadmission : l'école sur concours au-delà de son seuil (C1) passe devant une meilleure moyenne (C6).
        $demande('ancien')->update(['niveau_etude_precedent_code' => 'L1', 'niveau_etude_actuel_code' => 'L2', 'filiere_code' => '1', 'credit_valider' => 48, 'moyenne' => 11]);
        $demande('ancien2')->update(['niveau_etude_precedent_code' => 'L1', 'niveau_etude_actuel_code' => 'L2', 'credit_valider' => 60, 'moyenne' => 15, 'boursier' => true]);
        $this->enVigueur('readmission', ['readmission' => [
            'loyers' => ['actif' => false, 'mois_exiges' => 10],
            'credits' => ['actif' => true, 'seuil_defaut' => 100, 'filieres' => [['filiere_id' => '1', 'seuil' => 80, 'concours' => true]]],
            'conduite' => ['actif' => false, 'note_min' => 10, 'sanctions' => ['suspension', 'expulsion']],
        ]]);
        $recevables = fn (array $query = []) => $this->page('liste', ['etat' => 'recevables'], '/admin/gestion-hebergement/campagnes_admissions/liste/recevables', $query)['props'];

        // Liste des demandes : type de demande de chaque dossier, compteurs des onglets.
        $props = $recevables();
        $this->assertSame('demandes', $props['classement']['liste']);
        $this->assertNull($props['classement']['lignes']);
        $this->assertSame(['demandes' => 4, 'admission' => 2, 'readmission' => 2], $props['classement']['compteurs']);
        $this->assertSame(['ancien' => 'readmission', 'ancien2' => 'readmission', 'other' => 'admission', 'student' => 'admission'],
            collect($props['dossiers']['data'])->pluck('type_demande', 'id')->sortKeys()->all());
        $this->assertStringContainsString('liste=admission', $props['classement']['urls']['admission']);

        $admission = $recevables(['liste' => 'admission'])['classement'];
        $this->assertSame('admission', $admission['liste']);
        $this->assertSame([['other', 1], ['student', 2]], collect($admission['lignes']['data'])->map(fn ($l) => [$l['id'], $l['rang']])->all());
        $this->assertSame([240, 400], [(int) $admission['lignes']['data'][0]['merite']['valeur'], $admission['lignes']['data'][0]['merite']['max']]);
        $this->assertTrue($admission['lignes']['data'][0]['hors_ville']);
        $this->assertSame(20, $admission['lignes']['data'][0]['age']);

        $readmission = $recevables(['liste' => 'readmission'])['classement'];
        $this->assertSame([['ancien', 'C1'], ['ancien2', 'C6']], collect($readmission['lignes']['data'])->map(fn ($l) => [$l['id'], $l['categorie']['code']])->all());

        // La recherche filtre la liste ouverte sans changer les rangs.
        $recherche = $recevables(['liste' => 'admission', 'q' => 'konan'])['classement']['lignes']['data'];
        $this->assertSame([['student', 2]], collect($recherche)->map(fn ($l) => [$l['id'], $l['rang']])->all());

        // Campagne d'admission et de réadmission : les deux listes provisoires.
        $this->assertSame(['demandes', 'admission', 'readmission'], $props['classement']['onglets']);
        // Campagne d'admission seule : pas d'onglet de réadmission (un lien vers cet onglet ramène à la liste des demandes).
        if (! Schema::hasColumn('campagne', 'admission')) {
            Schema::table('campagne', function (Blueprint $t) {
                $t->boolean('admission')->default(false);
                $t->boolean('readmission')->default(false);
            });
        }
        DB::table('campagne')->update(['admission' => true, 'readmission' => false]);
        $admissionSeule = $recevables(['liste' => 'readmission'])['classement'];
        $this->assertSame(['demandes', 'admission'], $admissionSeule['onglets']);
        $this->assertSame(['demandes', 'admission'], array_keys($admissionSeule['urls']));
        $this->assertSame('demandes', $admissionSeule['liste']);

        // Pas d'onglets de classement hors des demandes recevables.
        $this->assertNull($this->page('liste', ['etat' => 'toutes'])['props']['classement']);
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
