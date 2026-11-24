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
            $t->string('campagne_id')->nullable();
            $t->string('requested_by')->nullable();
            $t->string('requester_name')->nullable();
            $t->text('reason')->nullable();
            $t->string('decider_name')->nullable();
            $t->text('decision_reason')->nullable();
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
        $this->assertSame(['q' => 'konan', 'etat_dossier' => '', 'type' => '', 'campagne' => ''], $parNom['filters']);
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

    public function test_liste_des_demandes_selon_la_campagne_choisie(): void
    {
        $this->agentLimite();
        DB::table('campagne')->where('id_campagne', 'campaign')->update(['created_at' => now()]);
        DB::table('campagne')->insert(['id_campagne' => 'ancienne', 'statut' => 'cloturee', 'created_at' => now()->subYear()]);
        DB::table('demande')->where('etudiant_id', 'student')->update(['campagne_id' => 'ancienne']);

        // Par défaut : campagne active, dont aucune demande n'a été déposée.
        $props = $this->page('liste', ['etat' => 'toutes'])['props'];
        $this->assertSame('campaign', $props['campagneActive']['id']);
        $this->assertSame(['campaign', 'ancienne'], array_column($props['campagnes'], 'id'));
        $this->assertSame([true, false], array_column($props['campagnes'], 'active'));
        $this->assertSame(0, $props['dossiers']['total']);
        $this->assertSame(0, $props['stats']['toutes']);

        // Campagne choisie : ses demandes, et les indicateurs conservent le choix.
        $props = $this->page('liste', ['etat' => 'toutes'], query: ['campagne' => 'ancienne'])['props'];
        $this->assertSame('ancienne', $props['campagneActive']['id']);
        $this->assertSame('ancienne', $props['filters']['campagne']);
        $this->assertSame(['student'], collect($props['dossiers']['data'])->pluck('id')->all());
        $this->assertSame(1, $props['stats']['toutes']);
        $this->assertStringContainsString('campagne=ancienne', $props['urls']['filtres']['rejetees']);
        // Décision de la commission : même choix de campagne ; listes définitives : pas de choix de campagne.
        $this->assertStringContainsString('campagne=ancienne', $props['urls']['filtres']['validees']);
        $validees = $this->page('liste', ['etat' => 'validees'], query: ['campagne' => 'ancienne'])['props'];
        $this->assertSame(['campaign', 'ancienne'], array_column($validees['campagnes'], 'id'));
        $this->assertSame('ancienne', $validees['campagneActive']['id']);
        $this->assertSame('campaign', $this->page('liste', ['etat' => 'validees'])['props']['campagneActive']['id']);
        $this->assertStringNotContainsString('campagne=', $props['urls']['filtres']['definitives']);
        $this->assertSame([], $this->page('liste', ['etat' => 'definitives'], query: ['campagne' => 'ancienne'])['props']['campagnes']);

        // Identifiant inconnu : campagne active.
        $this->assertSame('campaign', $this->page('liste', ['etat' => 'toutes'], query: ['campagne' => 'pirate'])['props']['campagneActive']['id']);
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
        $this->assertSame(['toutes' => 1, 'recevables' => 0, 'rejetees' => 1, 'refusees' => 0, 'validees' => 0, 'definitives' => 0], $props['stats']);
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
        $this->assertSame(['toutes' => 1, 'recevables' => 0, 'rejetees' => 0, 'refusees' => 1, 'validees' => 0, 'definitives' => 0], $props['stats']);
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
        $this->agentEligibilite(['hebergement.admissions.eligibility', 'hebergement.admissions.derogations.validate']);
        $ids = fn (string $etat) => collect($this->page('liste', ['etat' => $etat])['props']['dossiers']['data'])->pluck('id')->all();
        // Nouveau bachelier : 9/20 = 180/400 sous le seuil du modèle CROU (200/400) → irrecevable.
        DB::table('demande')->where('etudiant_id', 'student')->update(['niveau_etude_precedent_code' => 'TLE', 'niveau_etude_actuel_code' => 'L1', 'moyenne' => 9]);
        $id = (string) DB::table('demande')->where('etudiant_id', 'student')->value('id_demande');

        $props = $this->page('show', ['id' => 'student'], '/admin/gestion-hebergement/campagnes_admissions/student')['props'];
        $this->assertSame(route('admin.hebergement.campagnes_admission.derogations.store', ['student' => 'student']), $props['urls']['deroger']);
        $this->assertNull($props['eligibilite']['derogation']);
        // Carte « Dérogation à la recevabilité » et « Modifier les informations » retirées de la fiche.
        $this->assertArrayNotHasKey('derogations', $props);
        $this->assertArrayNotHasKey('edition', $props);

        // Dérogation en attente : la demande reste irrecevable ; approuvée : elle devient recevable.
        DB::table('admission_derogations')->insert(['id' => 'derog', 'demande_id' => $id, 'etudiant_id' => 'student', 'status' => 'pending']);
        $this->assertSame(['student'], $ids('rejetees'));
        DB::table('admission_derogations')->update(['status' => 'approved', 'decided_at' => '2026-11-18 10:00:00']);
        $this->assertSame(['student'], $ids('recevables'));
        $this->assertSame([], $ids('rejetees'));

        $props = $this->page('show', ['id' => 'student'], '/admin/gestion-hebergement/campagnes_admissions/student')['props'];
        $this->assertSame(1, $props['eligibilite']['non_conformes']);
        $this->assertSame('2026-11-18 10:00', $props['eligibilite']['derogation']['date']);
        $this->assertNull($props['urls']['deroger']);
    }

    public function test_pas_de_derogation_pour_une_demande_recevable(): void
    {
        $this->agentEligibilite(['hebergement.admissions.eligibility', 'hebergement.admissions.derogations.validate']);
        DB::table('demande')->where('etudiant_id', 'student')->update(['niveau_etude_precedent_code' => 'TLE', 'niveau_etude_actuel_code' => 'L1', 'moyenne' => 12]);

        $this->assertNull($this->page('show', ['id' => 'student'], '/admin/gestion-hebergement/campagnes_admissions/student')['props']['urls']['deroger']);
        $this->post(route('admin.hebergement.campagnes_admission.derogations.store', ['student' => 'student']), ['motif' => 'Motif'])->assertSessionHasErrors('motif');
        $this->assertSame(0, DB::table('admission_derogations')->count());
    }

    /** Migrations de la décision de la commission (approbation, mise en attente, liste d'origine) et de la ville du bac. */
    private function tablesCommission(): void
    {
        foreach (['2026_11_17_000001_add_ville_bac_to_demande.php', '2026_11_20_000001_add_approbation_commission_to_demande.php',
            '2026_11_20_000002_add_attente_commission_to_demande.php', '2026_11_20_000003_add_liste_approbation_commission_to_demande.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
    }

    public function test_deroger_une_demande_irrecevable_la_place_en_tete_de_la_liste_provisoire(): void
    {
        $this->agentEligibilite(['hebergement.admissions.eligibility', 'hebergement.admissions.derogations.validate']);
        $this->tablesCommission();
        $this->dossier('other');
        DB::table('etudiants')->where('id_etudiant', 'other')->update(['name' => 'Yao Marc', 'nationalite' => 'IVOIRIENNE', 'date_naissance' => now()->subYears(20)->toDateString()]);
        // « other » recevable (14/20) ; « student » irrecevable (9/20 = 180/400 sous le seuil de 200/400).
        DB::table('demande')->where('etudiant_id', 'other')->update(['niveau_etude_precedent_code' => 'TLE', 'niveau_etude_actuel_code' => 'L1', 'moyenne' => 14, 'ville_bac' => 'Bondoukou']);
        DB::table('demande')->where('etudiant_id', 'student')->update(['niveau_etude_precedent_code' => 'TLE', 'niveau_etude_actuel_code' => 'L1', 'moyenne' => 9, 'ville_bac' => 'Bondoukou']);
        $fiche = fn (string $id) => $this->page('show', ['id' => $id], '/admin/gestion-hebergement/campagnes_admissions/'.$id)['props'];
        $ids = fn (string $etat) => collect($this->page('liste', ['etat' => $etat])['props']['dossiers']['data'])->pluck('id')->all();
        $provisoire = fn () => collect($this->page('liste', ['etat' => 'recevables'], '/admin/gestion-hebergement/campagnes_admissions/liste/recevables', ['liste' => 'admission'])['props']['classement']['lignes']['data'])
            ->map(fn ($l) => [$l['id'], $l['rang'], $l['derogee']])->all();
        $this->assertSame([['other', 1, false]], $provisoire());

        // Bouton « Déroger » : demande irrecevable seulement.
        $url = $fiche('student')['urls']['deroger'];
        $this->assertSame(route('admin.hebergement.campagnes_admission.derogations.store', ['student' => 'student']), $url);
        $this->assertNull($fiche('other')['urls']['deroger']);

        // Motif obligatoire.
        $this->post($url, ['motif' => '  '])->assertSessionHasErrors('motif');
        $this->assertSame(['student'], $ids('rejetees'));

        // Dérogée : recevable sur-le-champ, en tête de la liste provisoire avec le badge « Dérogé » ; le dossier reste « Soumis ».
        $this->post($url, ['motif' => 'Situation sociale particulière.'])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame([], $ids('rejetees'));
        $this->assertSame([['student', 1, true], ['other', 2, false]], $provisoire());
        $this->assertSame('soumis', DB::table('demande')->where('etudiant_id', 'student')->value('etat_dossier'));
        $derogation = DB::table('admission_derogations')->where('etudiant_id', 'student')->first();
        $this->assertSame(['approved', 'Situation sociale particulière.', 'limite'], [$derogation->status, $derogation->reason, $derogation->decided_by]);
        $props = $fiche('student');
        $this->assertSame('Situation sociale particulière.', $props['eligibilite']['derogation']['motif']);
        $this->assertNull($props['urls']['deroger']);
    }

    public function test_deroger_une_demande_refusee_la_rouvre(): void
    {
        $this->agentEligibilite(['hebergement.admissions.eligibility', 'hebergement.admissions.derogations.validate', 'hebergement.admissions.validate']);
        $this->tablesCommission();
        DB::table('demande')->where('etudiant_id', 'student')->update(['niveau_etude_precedent_code' => 'TLE', 'niveau_etude_actuel_code' => 'L1', 'moyenne' => 12, 'ville_bac' => 'Bondoukou']);
        $demande = DB::table('demande')->where('etudiant_id', 'student');
        $ids = fn (string $etat) => collect($this->page('liste', ['etat' => $etat])['props']['dossiers']['data'])->pluck('id')->all();
        // Rejetée par la commission depuis la liste provisoire : « Demandes refusées ».
        $this->post(route('admin.dossiers.transition', $demande->value('id_demande')), ['etat' => 'refuse', 'motif' => 'Désistement constaté.'])->assertRedirect();
        $this->assertSame(['student'], $ids('refusees'));

        $url = $this->page('show', ['id' => 'student'], '/admin/gestion-hebergement/campagnes_admissions/student')['props']['urls']['deroger'];
        $this->assertNotNull($url);
        $this->post($url, ['motif' => 'Désistement annulé par l’étudiant.'])->assertSessionHasNoErrors();

        // Le dossier revient en « Soumis » (motif dans l'historique) et la demande en tête de la liste provisoire.
        $this->assertSame('soumis', $demande->value('etat_dossier'));
        $this->assertSame('Dérogation : Désistement annulé par l’étudiant.', DB::table('dossier_transitions')->orderByDesc('id')->value('motif'));
        $this->assertSame([], $ids('refusees'));
        // La fiche conserve le motif du rejet (bloc « Motifs de rejet ») à côté de celui de la dérogation.
        $props = $this->page('show', ['id' => 'student'], '/admin/gestion-hebergement/campagnes_admissions/student')['props'];
        $this->assertSame('Désistement annulé par l’étudiant.', $props['eligibilite']['derogation']['motif']);
        $this->assertCount(1, $props['rejets']);
        $this->assertSame(['Désistement constaté.', false], [$props['rejets'][0]['motif'], $props['rejets'][0]['automatique']]);
        $ligne = $this->page('liste', ['etat' => 'recevables'], '/admin/gestion-hebergement/campagnes_admissions/liste/recevables', ['liste' => 'admission'])['props']['classement']['lignes']['data'][0];
        $this->assertSame(['student', 1, true], [$ligne['id'], $ligne['rang'], $ligne['derogee']]);
    }

    public function test_aucune_derogation_apres_l_approbation_de_la_liste_en_commission(): void
    {
        $this->agentEligibilite(['hebergement.admissions.eligibility', 'hebergement.admissions.derogations.validate']);
        $this->tablesCommission();
        $this->dossier('other');
        DB::table('demande')->where('etudiant_id', 'student')->update(['niveau_etude_precedent_code' => 'TLE', 'niveau_etude_actuel_code' => 'L1', 'moyenne' => 9]);
        // Liste des demandes validées (admission) approuvée en commission pour la campagne.
        DB::table('demande')->where('etudiant_id', 'other')->update([
            'niveau_etude_precedent_code' => 'TLE', 'niveau_etude_actuel_code' => 'L1', 'etat_dossier' => 'eligible',
            'date_approbation_commission' => now(), 'liste_approbation_commission' => 'validees',
        ]);
        $url = route('admin.hebergement.campagnes_admission.derogations.store', ['student' => 'student']);

        $this->assertNull($this->page('show', ['id' => 'student'], '/admin/gestion-hebergement/campagnes_admissions/student')['props']['urls']['deroger']);
        $this->post($url, ['motif' => 'Motif'])->assertSessionHasErrors(['motif' => \App\Http\Services\AdmissionDerogationService::MESSAGE_CLOTURE]);
        $this->assertSame(0, DB::table('admission_derogations')->count());

        // File d'attente approuvée seule : la liste provisoire n'est pas clôturée.
        DB::table('demande')->where('etudiant_id', 'other')->update(['liste_approbation_commission' => 'attente']);
        $this->assertSame($url, $this->page('show', ['id' => 'student'], '/admin/gestion-hebergement/campagnes_admissions/student')['props']['urls']['deroger']);
    }

    public function test_deroger_exige_le_droit_de_deroger(): void
    {
        $this->agentEligibilite(['hebergement.admissions.eligibility']);
        DB::table('demande')->where('etudiant_id', 'student')->update(['niveau_etude_precedent_code' => 'TLE', 'niveau_etude_actuel_code' => 'L1', 'moyenne' => 9]);

        $this->assertNull($this->page('show', ['id' => 'student'], '/admin/gestion-hebergement/campagnes_admissions/student')['props']['urls']['deroger']);
        $this->post(route('admin.hebergement.campagnes_admission.derogations.store', ['student' => 'student']), ['motif' => 'Motif'])->assertForbidden();
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
        // Section « Suivi du dossier » supprimée de la fiche : plus envoyée au navigateur.
        $this->assertArrayNotHasKey('suivi', $props);
        // Sans le droit de déroger : pas de bouton « Déroger » ; plus de modification des informations sur la fiche.
        $this->assertNull($props['urls']['deroger']);
        $this->assertArrayNotHasKey('modifier', $props['urls']);
        $this->assertArrayNotHasKey('edition', $props);
        $this->assertArrayNotHasKey('options', $props);
    }

    public function test_l_administration_modifie_les_informations_saisies_par_l_etudiant(): void
    {
        $this->agentLimite(['hebergement.admissions.update']);

        $this->put(route('admin.hebergement.campagnes_admission.update_etudiant', ['etudiant' => 'student']), ['name' => 'Konan Awa Marie', 'genre_id' => 2, 'matricule' => 'M-001', 'telephone1' => '0700000001',
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
        $edition = app(\App\Http\Services\EtudiantEditionService::class);
        $this->assertSame('245', $edition->valeurs(\App\Models\Etudiant::with('derniereDemande')->findOrFail('student'))['moyenne']);
        $this->assertTrue(collect($edition->options()['niveaux'])->firstWhere('id', 'TLE')['terminale']);

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

        // Filtre par type de demande (calculé, non stocké) ; un type inconnu est ignoré.
        $parType = fn (string $type) => $this->page('liste', ['etat' => 'toutes'], query: ['type' => $type])['props'];
        $this->assertSame(['ancien', 'ancien2'], collect($parType('readmission')['dossiers']['data'])->pluck('id')->sort()->values()->all());
        $this->assertSame(['other', 'student'], collect($parType('admission')['dossiers']['data'])->pluck('id')->sort()->values()->all());
        $this->assertSame('readmission', $parType('readmission')['filters']['type']);
        $this->assertSame(['', 4], [$parType('pirate')['filters']['type'], $parType('pirate')['dossiers']['total']]);
        $this->assertSame(2, $this->page('liste', ['etat' => 'recevables'], '/admin/gestion-hebergement/campagnes_admissions/liste/recevables', ['type' => 'admission'])['props']['dossiers']['total']);

        // Liste des demandes : type de demande de chaque dossier, compteurs des onglets.
        $props = $recevables();
        $this->assertSame('demandes', $props['classement']['liste']);
        $this->assertNull($props['classement']['lignes']);
        $this->assertSame(['demandes' => 4, 'admission' => 2, 'readmission' => 2, 'toutes' => 4, 'validees' => 0, 'attente' => 0], $props['classement']['compteurs']);
        $this->assertSame(['ancien' => 'readmission', 'ancien2' => 'readmission', 'other' => 'admission', 'student' => 'admission'],
            collect($props['dossiers']['data'])->pluck('type_demande', 'id')->sortKeys()->all());
        $this->assertStringContainsString('liste=admission', $props['classement']['urls']['admission']);

        $admission = $recevables(['liste' => 'admission'])['classement'];
        $this->assertSame('admission', $admission['liste']);
        $this->assertSame([['other', 1], ['student', 2]], collect($admission['lignes']['data'])->map(fn ($l) => [$l['id'], $l['rang']])->all());
        $this->assertSame([240, 400], [(int) $admission['lignes']['data'][0]['merite']['valeur'], $admission['lignes']['data'][0]['merite']['max']]);
        $this->assertTrue($admission['lignes']['data'][0]['hors_ville']);
        $this->assertSame(20, $admission['lignes']['data'][0]['age']);

        // Téléchargement de la liste provisoire ouverte : Excel et PDF à imprimer, liste complète.
        $this->assertNull($props['classement']['exports']);
        $this->assertStringEndsWith('/liste-provisoire/admission/excel', $admission['exports']['excel']);
        $excel = $this->get($admission['exports']['excel']);
        $excel->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('liste-provisoire-admission', (string) $excel->headers->get('content-disposition'));
        $pdf = $this->get($admission['exports']['pdf']);
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get('/admin/gestion-hebergement/campagnes_admissions/liste-provisoire/admission/csv')->assertNotFound();

        $readmission = $recevables(['liste' => 'readmission'])['classement'];
        $this->assertSame([['ancien', 'C1'], ['ancien2', 'C6']], collect($readmission['lignes']['data'])->map(fn ($l) => [$l['id'], $l['categorie']['code']])->all());
        // Même bouton « Télécharger » sur la liste provisoire des réadmissions.
        $this->assertStringEndsWith('/liste-provisoire/readmission/excel', $readmission['exports']['excel']);
        $this->assertStringEndsWith('/liste-provisoire/readmission/pdf', $readmission['exports']['pdf']);
        $this->assertStringContainsString('liste-provisoire-readmission', (string) $this->get($readmission['exports']['excel'])->assertOk()->headers->get('content-disposition'));
        $this->get($readmission['exports']['pdf'])->assertOk()->assertHeader('content-type', 'application/pdf');

        // Priorité aux boursiers (paramétrage général, cochée par défaut) : un boursier passe devant le bac hors ville.
        $demande('student')->update(['boursier' => true]);
        $admission = $recevables(['liste' => 'admission'])['classement']['lignes']['data'];
        $this->assertSame([['student', true], ['other', false]], collect($admission)->map(fn ($l) => [$l['id'], $l['boursier']])->all());
        // Décochée : le classement revient au mérite puis au départage.
        $this->enVigueur('general', ['priorite_boursiers' => false]);
        $this->assertSame(['other', 'student'], collect($recevables(['liste' => 'admission'])['classement']['lignes']['data'])->pluck('id')->all());
        DB::table('campagne_regles')->where('volet', 'general')->delete();
        $demande('student')->update(['boursier' => false]);

        // État des pièces de chaque ligne : aucune pièce demandée ni déposée → pas d'état.
        $this->assertNull($admission[0]['documents']);
        Schema::table('document_etudiant', function (Blueprint $t) {
            $t->string('type_document')->nullable();
            $t->string('statut')->nullable();
            $t->string('id_campagne')->nullable();
        });
        Schema::create('document', function (Blueprint $t) {
            $t->string('id_document')->primary();
            $t->string('name');
            $t->boolean('champs_requis')->default(false);
            $t->timestamps();
        });
        Schema::create('document_campagne', function (Blueprint $t) {
            $t->string('id_document_campagne')->primary();
            $t->string('id_campagne');
            $t->string('document_id');
            $t->timestamps();
        });
        DB::table('document')->insert([['id_document' => 'cni', 'name' => 'CNI', 'champs_requis' => true], ['id_document' => 'photo', 'name' => 'Photo', 'champs_requis' => false]]);
        DB::table('document_campagne')->insert([['id_document_campagne' => 'dc1', 'id_campagne' => 'campaign', 'document_id' => 'cni'], ['id_document_campagne' => 'dc2', 'id_campagne' => 'campaign', 'document_id' => 'photo']]);
        $piece = fn (string $id, string $etudiant, string $type, string $statut, string $date) => DB::table('document_etudiant')->insert(['id_document' => $id, 'etudiant_id' => $etudiant, 'type_document' => $type, 'statut' => $statut, 'id_campagne' => 'campaign', 'created_at' => $date]);
        // student : la CNI rejetée a été remplacée par une version validée (seule la dernière compte), photo validée → vert.
        $piece('p1', 'student', 'CNI', 'rejete', '2026-01-01');
        $piece('p2', 'student', 'CNI', 'valide', '2026-01-02');
        $piece('p3', 'student', 'Photo', 'valide', '2026-01-02');
        // other : photo validée mais CNI obligatoire absente → rouge.
        $piece('p4', 'other', 'Photo', 'valide', '2026-01-02');
        $pieces = collect($recevables(['liste' => 'admission'])['classement']['lignes']['data'])->pluck('documents', 'id');
        $this->assertSame(['statut' => 'valide', 'valides' => 2, 'rejetes' => 0, 'en_verification' => 0, 'absents' => 0], $pieces['student']);
        $this->assertSame(['incomplet', 1], [$pieces['other']['statut'], $pieces['other']['absents']]);
        $piece('p5', 'other', 'CNI', 'en_verification', '2026-01-03');
        $this->assertSame('en_verification', collect($recevables(['liste' => 'admission'])['classement']['lignes']['data'])->firstWhere('id', 'other')['documents']['statut']);
        $piece('p6', 'student', 'Photo', 'rejete', '2026-01-03');
        $this->assertSame('incomplet', collect($recevables(['liste' => 'admission'])['classement']['lignes']['data'])->firstWhere('id', 'student')['documents']['statut']);

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

    public function test_validation_et_rejet_depuis_la_liste_provisoire(): void
    {
        $this->agentEligibilite(['hebergement.admissions.validate']);
        (require database_path('migrations/2026_11_17_000001_add_ville_bac_to_demande.php'))->up();
        $this->dossier('other');
        DB::table('etudiants')->where('id_etudiant', 'other')->update(['name' => 'Yao Marc', 'nationalite' => 'IVOIRIENNE', 'date_naissance' => now()->subYears(20)->toDateString()]);
        DB::table('demande')->where('etudiant_id', 'student')->update(['niveau_etude_precedent_code' => 'TLE', 'niveau_etude_actuel_code' => 'L1', 'moyenne' => 12, 'ville_bac' => 'Bondoukou']);
        DB::table('demande')->where('etudiant_id', 'other')->update(['niveau_etude_precedent_code' => 'TLE', 'niveau_etude_actuel_code' => 'L1', 'moyenne' => 12, 'ville_bac' => 'Abidjan']);
        $liste = fn (string $etat) => $this->page('liste', ['etat' => $etat], '/admin/gestion-hebergement/campagnes_admissions/liste/'.$etat, ['liste' => 'admission'])['props'];

        // Liste provisoire : « Validation » et « Rejet » sur chaque dossier soumis.
        $lignes = collect($liste('recevables')['classement']['lignes']['data'])->keyBy('id');
        $this->assertSame(['other', 'student'], $lignes->keys()->all());
        $this->assertSame([true, true], [$lignes['other']['decision']['valider'], $lignes['other']['decision']['rejeter']]);

        // Validation : le dossier passe « Recevable » et rejoint la liste validée, chaque liste a son propre classement.
        $this->post($lignes['other']['decision']['url'], ['etat' => 'eligible'])->assertRedirect();
        $this->assertSame('eligible', DB::table('demande')->where('etudiant_id', 'other')->value('etat_dossier'));
        $recevables = $liste('recevables');
        $this->assertSame([['student', 1]], collect($recevables['classement']['lignes']['data'])->map(fn ($l) => [$l['id'], $l['rang']])->all());
        $this->assertSame(['recevables' => 1, 'validees' => 1], array_intersect_key($recevables['stats'], ['recevables' => 0, 'validees' => 0]));
        $validees = $liste('validees');
        $this->assertSame('validees', $validees['etat']);
        $this->assertSame('validee', $validees['classement']['type']);
        // Onglets « Demandes validées » puis « File d'attente » (admissions et réadmissions mêlées, filtrables par type de demande).
        $this->assertSame(['validees', ['validees', 'attente'], null], [$validees['classement']['liste'], $validees['classement']['onglets'], $validees['classement']['volet']]);
        // Le type de demande filtré est conservé en changeant d'onglet.
        $this->assertStringContainsString('type=admission', $this->page('liste', ['etat' => 'validees'], '/admin/gestion-hebergement/campagnes_admissions/liste/validees', ['type' => 'admission'])['props']['classement']['urls']['attente']);
        $this->assertSame([['other', 'admission', 1]], collect($validees['classement']['lignes']['data'])->map(fn ($l) => [$l['id'], $l['volet'], $l['rang']])->all());
        // Colonnes des demandes validées : matricule et filière de la demande.
        DB::table('etudiants')->where('id_etudiant', 'other')->update(['matricule' => 'M-002']);
        DB::table('demande')->where('etudiant_id', 'other')->update(['filiere_code' => '1']);
        $ligneValidee = $liste('validees')['classement']['lignes']['data'][0];
        $this->assertSame(['M-002', 'Informatique'], [$ligneValidee['matricule'], $ligneValidee['filiere']]);
        // Civilité (genre) et date de naissance (données personnelles : l'administrateur a tous les droits).
        $this->assertSame(['Masculin', now()->subYears(20)->toDateString()], [$ligneValidee['civilite'], $ligneValidee['date_naissance']]);
        $parType = fn (string $type) => $this->page('liste', ['etat' => 'validees'], '/admin/gestion-hebergement/campagnes_admissions/liste/validees', ['type' => $type])['props']['classement'];
        $this->assertSame(['admission', ['other']], [$parType('admission')['volet'], collect($parType('admission')['lignes']['data'])->pluck('id')->all()]);
        $this->assertSame(['readmission', []], [$parType('readmission')['volet'], $parType('readmission')['lignes']['data']]);
        // Liste validée : rejet seul (pas de nouvelle validation), pas de téléchargement.
        $decision = $validees['classement']['lignes']['data'][0]['decision'];
        $this->assertSame([false, true], [$decision['valider'], $decision['rejeter']]);
        $this->assertNull($validees['classement']['exports']);

        // Rejet : motif obligatoire, puis la demande passe dans « Demandes refusées ».
        $url = $lignes['student']['decision']['url'];
        $this->post($url, ['etat' => 'refuse'])->assertSessionHasErrors('etat_dossier');
        $this->post($url, ['etat' => 'refuse', 'motif' => 'Pièces falsifiées.'])->assertRedirect();
        $props = $liste('recevables');
        $this->assertSame([], $props['classement']['lignes']['data']);
        $this->assertSame(['student'], collect($this->page('liste', ['etat' => 'refusees'])['props']['dossiers']['data'])->pluck('id')->all());

        // Rejet depuis la liste validée : motif obligatoire, la demande sort de la liste validée pour « Demandes refusées ».
        $this->post($decision['url'], ['etat' => 'refuse'])->assertSessionHasErrors('etat_dossier');
        $this->post($decision['url'], ['etat' => 'refuse', 'motif' => 'Désistement constaté.'])->assertRedirect();
        $this->assertSame([], $liste('validees')['classement']['lignes']['data']);
        $this->assertEqualsCanonicalizing(['other', 'student'], collect($this->page('liste', ['etat' => 'refusees'])['props']['dossiers']['data'])->pluck('id')->all());

        // Motif du rejet enregistré sur la transition du dossier.
        $this->assertSame('Désistement constaté.', DB::table('dossier_transitions')->where('nouvel_etat', 'refuse')->orderByDesc('id')->value('motif'));

        // Sans le droit de changer l'état du dossier : pas de menu d'actions.
        DB::table('demande')->where('etudiant_id', 'student')->update(['etat_dossier' => 'soumis']);
        DB::table('dossier_transitions')->where('demande_id', DB::table('demande')->where('etudiant_id', 'student')->value('id_demande'))->delete();
        $this->agentLimite(['hebergement.admissions.personal', 'hebergement.admissions.documents']);
        $this->assertNull($liste('recevables')['classement']['lignes']['data'][0]['decision']);
    }

    public function test_approbation_de_la_liste_validee_en_commission(): void
    {
        $this->agentEligibilite(['hebergement.admissions.validate']);
        (require database_path('migrations/2026_11_17_000001_add_ville_bac_to_demande.php'))->up();
        (require database_path('migrations/2026_11_20_000001_add_approbation_commission_to_demande.php'))->up();
        (require database_path('migrations/2026_11_20_000002_add_attente_commission_to_demande.php'))->up();
        (require database_path('migrations/2026_11_20_000003_add_liste_approbation_commission_to_demande.php'))->up();
        (require database_path('migrations/2026_11_20_000004_create_publication_liste_definitive_table.php'))->up();
        $this->dossier('other');
        DB::table('etudiants')->where('id_etudiant', 'other')->update(['name' => 'Yao Marc', 'nationalite' => 'IVOIRIENNE', 'date_naissance' => now()->subYears(20)->toDateString()]);
        DB::table('demande')->where('etudiant_id', 'student')->update(['niveau_etude_precedent_code' => 'TLE', 'niveau_etude_actuel_code' => 'L1', 'moyenne' => 12, 'ville_bac' => 'Bondoukou']);
        DB::table('demande')->where('etudiant_id', 'other')->update(['niveau_etude_precedent_code' => 'TLE', 'niveau_etude_actuel_code' => 'L1', 'moyenne' => 12, 'ville_bac' => 'Abidjan']);
        $liste = fn (string $etat, array $query = []) => $this->page('liste', ['etat' => $etat], '/admin/gestion-hebergement/campagnes_admissions/liste/'.$etat, ['liste' => 'admission', ...$query])['props'];
        $rangs = fn (array $props) => collect($props['classement']['lignes']['data'])->map(fn ($l) => [$l['id'], $l['rang']])->all();

        // Les deux dossiers sont validés depuis la liste provisoire : ils rejoignent la liste validée en commission.
        $lignes = collect($liste('recevables')['classement']['lignes']['data'])->keyBy('id');
        foreach (['other', 'student'] as $id) {
            $this->post($lignes[$id]['decision']['url'], ['etat' => 'eligible'])->assertRedirect();
        }
        $validees = $liste('validees')['classement'];
        // Liste unique : « Approuver » porte sur les deux volets ; filtrée par type, sur ce seul volet.
        $url = route('admin.hebergement.campagnes_admission.liste_validee_approuver', ['volet' => 'toutes']);
        $this->assertSame(['url' => $url, 'liste' => 'validees', 'nombre' => 2], $validees['approbation']);
        $readmissions = $this->page('liste', ['etat' => 'validees'], '/admin/gestion-hebergement/campagnes_admissions/liste/validees', ['type' => 'readmission'])['props']['classement'];
        $this->assertSame(['url' => route('admin.hebergement.campagnes_admission.liste_validee_approuver', ['volet' => 'readmission']), 'liste' => 'validees', 'nombre' => 0], $readmissions['approbation']);
        $this->post(route('admin.hebergement.campagnes_admission.liste_validee_approuver', ['volet' => 'readmission']))->assertSessionHasErrors('approbation');

        // Approbation : toute la liste passe dans la liste définitive, avec ses rangs ; l'état du dossier ne change pas.
        $this->post($url)->assertRedirect(route('admin.hebergement.campagnes_admission.admission', ['etat' => 'definitives']));
        $validees = $liste('validees')['classement'];
        $this->assertSame([], $validees['lignes']['data']);
        // Onglet vide après approbation : la liste est signalée comme déjà approuvée pour la campagne.
        $this->assertSame(['validees' => 2, 'attente' => 0], $validees['approuvees']);
        $definitives = $liste('definitives');
        $this->assertSame(['definitives', 'definitive'], [$definitives['etat'], $definitives['classement']['type']]);
        $this->assertNull($definitives['classement']['approuvees']);
        // Listes définitives : onglets « Demandes validées » (par défaut) et « File d'attente », selon la liste approuvée.
        $this->assertSame(['validees', ['validees', 'attente']], [$definitives['classement']['liste'], $definitives['classement']['onglets']]);
        $this->assertSame([['other', 1], ['student', 2]], $rangs($definitives));
        $this->assertSame(['validees' => 2, 'attente' => 0], array_intersect_key($definitives['classement']['compteurs'], ['validees' => 0, 'attente' => 0]));
        $this->assertSame([], $liste('definitives', ['liste' => 'attente'])['classement']['lignes']['data'] ?? null);
        $this->assertSame(['validees' => 0, 'definitives' => 2], array_intersect_key($definitives['stats'], ['validees' => 0, 'definitives' => 0]));
        // Listes définitives : aucune action ni approbation ; téléchargement Excel ou PDF de l'onglet ouvert.
        $this->assertNull($definitives['classement']['lignes']['data'][0]['decision']);
        $this->assertNull($definitives['classement']['lignes']['data'][0]['commission']);
        // Listes définitives : pas d'icône d'état des pièces du dossier.
        $this->assertNull($definitives['classement']['lignes']['data'][0]['documents']);
        $this->assertNull($definitives['classement']['approbation']);
        // Liste pas encore publiée : bouton « Publier », pas de téléchargement (refusé côté serveur).
        $publier = route('admin.hebergement.campagnes_admission.liste_definitive_publier', ['liste' => 'validees', 'volet' => 'toutes']);
        $this->assertSame(['publiee' => false, 'date' => null, 'url' => $publier, 'nombre' => 2], $definitives['classement']['publication']);
        $this->assertNull($definitives['classement']['exports']);
        $this->get(route('admin.hebergement.campagnes_admission.liste_definitive_export', ['liste' => 'validees', 'volet' => 'toutes', 'format' => 'excel']))
            ->assertRedirect()->assertSessionHas('error');
        // File d'attente vide : rien à publier.
        $this->post(route('admin.hebergement.campagnes_admission.liste_definitive_publier', ['liste' => 'attente', 'volet' => 'toutes']))->assertSessionHasErrors('publication');
        // Publication : le téléchargement apparaît, le bouton « Publier » disparaît.
        $this->post($publier)->assertRedirect(route('admin.hebergement.campagnes_admission.admission', ['etat' => 'definitives']))->assertSessionHasNoErrors();
        // Une publication par volet ayant des demandes approuvées (ici les admissions seules).
        $this->assertSame(['admission'], DB::table('publication_liste_definitive')->where('liste', 'validees')->pluck('volet')->all());
        $definitives = $liste('definitives');
        $this->assertTrue($definitives['classement']['publication']['publiee']);
        $this->assertNotNull($definitives['classement']['publication']['date']);
        $this->assertNull($definitives['classement']['publication']['url']);
        $this->post($publier)->assertSessionHasErrors('publication');
        $this->assertStringEndsWith('/liste-definitive/validees/toutes/excel', $definitives['classement']['exports']['excel']);
        $this->assertStringContainsString('liste-definitive-validees-toutes', (string) $this->get($definitives['classement']['exports']['excel'])->assertOk()->headers->get('content-disposition'));
        $this->get($definitives['classement']['exports']['pdf'])->assertOk()->assertHeader('content-type', 'application/pdf');
        // File d'attente vide, donc jamais publiée : pas de téléchargement.
        $this->get(route('admin.hebergement.campagnes_admission.liste_definitive_export', ['liste' => 'attente', 'volet' => 'admission', 'format' => 'excel']))->assertRedirect();
        $demande = DB::table('demande')->where('etudiant_id', 'other')->first();
        $this->assertSame(['eligible', 'limite', 'validees'], [$demande->etat_dossier, $demande->approbation_commission_par, $demande->liste_approbation_commission]);
        $this->assertNotNull($demande->date_approbation_commission);

        // Liste validée vide : rien à approuver.
        $this->post($url)->assertSessionHasErrors('approbation');

        // Dossier rejeté puis réexaminé : il revient dans la liste provisoire, désormais clôturée (liste des demandes
        // validées approuvée) : il ne peut plus y être validé ni mis en attente, seulement rejeté.
        $this->travel(1)->minutes();
        $transition = route('admin.dossiers.transition', $demande->id_demande);
        $this->post($transition, ['etat' => 'refuse', 'motif' => 'Erreur de saisie.'])->assertRedirect();
        $this->post($transition, ['etat' => 'soumis', 'motif' => 'Réexamen.'])->assertRedirect();
        $ligne = $liste('recevables')['classement']['lignes']['data'][0];
        $this->assertSame(['other', false, null, true], [$ligne['id'], $ligne['decision']['valider'], $ligne['decision']['attente'], $ligne['decision']['rejeter']]);
        $this->post($transition, ['etat' => 'eligible'])->assertSessionHasErrors('etat');
        $this->post(route('admin.hebergement.campagnes_admission.mettre_en_attente', $demande->id_demande))->assertSessionHasErrors('attente');
        $this->assertSame('soumis', DB::table('demande')->where('etudiant_id', 'other')->value('etat_dossier'));
        $this->assertSame([], $liste('validees')['classement']['lignes']['data']);
        $this->assertSame([['student', 1]], $rangs($liste('definitives')));
        $this->assertTrue($liste('definitives')['classement']['publication']['publiee']);

        // Sans le droit de valider les admissions : pas de bouton « Approuver » ni « Publier », actions refusées.
        $this->agentLimite(['hebergement.admissions.personal']);
        $this->assertNull($liste('validees')['classement']['approbation']);
        $this->post($url)->assertForbidden();
        $this->post($publier)->assertForbidden();
        // L'ancienne approbation (antérieure au rejet) reste ignorée.
        $this->assertSame($demande->date_approbation_commission, DB::table('demande')->where('etudiant_id', 'other')->value('date_approbation_commission'));

        // Approbation postérieure à la publication : la liste doit être publiée à nouveau avant d'être téléchargée.
        $this->agentLimite(['hebergement.admissions.personal', 'hebergement.admissions.validate']);
        $this->travel(1)->minutes();
        DB::table('demande')->where('etudiant_id', 'student')->update(['date_approbation_commission' => now()]);
        $definitives = $liste('definitives')['classement'];
        $this->assertSame([false, $publier, 1], [$definitives['publication']['publiee'], $definitives['publication']['url'], $definitives['publication']['nombre']]);
        $this->assertNull($definitives['exports']);
        $this->post($publier)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNotNull($liste('definitives')['classement']['exports']);
    }

    public function test_mise_en_attente_par_la_commission(): void
    {
        $this->agentEligibilite(['hebergement.admissions.validate']);
        foreach (['2026_11_17_000001_add_ville_bac_to_demande.php', '2026_11_20_000001_add_approbation_commission_to_demande.php', '2026_11_20_000002_add_attente_commission_to_demande.php', '2026_11_20_000003_add_liste_approbation_commission_to_demande.php', '2026_11_20_000004_create_publication_liste_definitive_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        $this->dossier('other');
        DB::table('etudiants')->where('id_etudiant', 'other')->update(['name' => 'Yao Marc', 'nationalite' => 'IVOIRIENNE', 'date_naissance' => now()->subYears(20)->toDateString()]);
        DB::table('demande')->where('etudiant_id', 'student')->update(['niveau_etude_precedent_code' => 'TLE', 'niveau_etude_actuel_code' => 'L1', 'moyenne' => 12, 'ville_bac' => 'Bondoukou']);
        DB::table('demande')->where('etudiant_id', 'other')->update(['niveau_etude_precedent_code' => 'TLE', 'niveau_etude_actuel_code' => 'L1', 'moyenne' => 12, 'ville_bac' => 'Abidjan']);
        $liste = fn (string $etat, array $query = []) => $this->page('liste', ['etat' => $etat], '/admin/gestion-hebergement/campagnes_admissions/liste/'.$etat, ['liste' => 'admission', ...$query])['props'];
        $decisions = fn (array $props) => collect($props['classement']['lignes']['data'])->map(fn ($l) => [$l['id'], $l['rang'], $l['commission']['decision'] ?? null])->all();
        $idOther = DB::table('demande')->where('etudiant_id', 'other')->value('id_demande');

        // Liste provisoire : trois décisions de la commission (validation, mise en attente, rejet).
        $lignes = collect($liste('recevables')['classement']['lignes']['data'])->keyBy('id');
        $attente = route('admin.hebergement.campagnes_admission.mettre_en_attente', $idOther);
        $this->assertSame([true, $attente, true], [$lignes['other']['decision']['valider'], $lignes['other']['decision']['attente'], $lignes['other']['decision']['rejeter']]);

        // Mise en attente (sans motif) : la demande passe dans la décision de la commission, classée dans son onglet ; l'état du
        // dossier ne change pas.
        $this->post($attente)->assertRedirect()->assertSessionHasNoErrors();
        $demande = DB::table('demande')->where('id_demande', $idOther)->first();
        $this->assertSame(['soumis', 'limite'], [$demande->etat_dossier, $demande->attente_commission_par]);
        $this->assertNotNull($demande->date_attente_commission);
        $recevables = $liste('recevables');
        $this->assertSame([['student', 1, null]], $decisions($recevables));
        $this->assertSame(['recevables' => 1, 'validees' => 1], array_intersect_key($recevables['stats'], ['recevables' => 0, 'validees' => 0]));
        // Décision de la commission : onglets « Demandes validées » (par défaut) puis « File d'attente ».
        $validees = $liste('validees')['classement'];
        $this->assertSame(['validees', ['validees', 'attente'], 'validee', []], [$validees['liste'], $validees['onglets'], $validees['decision'], $validees['lignes']['data']]);
        $this->assertStringContainsString('liste=attente', $validees['urls']['attente']);
        $commission = $liste('validees', ['liste' => 'attente'])['classement'];
        $this->assertSame(['attente', 'attente'], [$commission['liste'], $commission['decision']]);
        $this->assertSame([['other', 1, 'attente']], $decisions(['classement' => $commission]));
        $ligne = $commission['lignes']['data'][0];
        $this->assertNotNull($ligne['commission']['date']);
        // Demande en attente : validation ou rejet (plus de mise en attente) ; approbation de la file d'attente.
        $this->assertSame([true, true, null], [$ligne['decision']['valider'], $ligne['decision']['rejeter'], $ligne['decision']['attente']]);
        $this->assertSame([0, 1], [$commission['compteurs']['validees'], $commission['compteurs']['attente']]);
        $approuverAttente = route('admin.hebergement.campagnes_admission.file_attente_approuver', ['volet' => 'toutes']);
        $this->assertSame(['url' => $approuverAttente, 'liste' => 'attente', 'nombre' => 1], $commission['approbation']);
        $this->assertSame(0, $validees['approbation']['nombre']);
        $this->post($attente)->assertSessionHasErrors('attente');

        // L'autre demande est validée : chaque onglet garde ses demandes, classées à partir de 1 dans chaque onglet.
        $this->post($lignes['student']['decision']['url'], ['etat' => 'eligible'])->assertRedirect();
        $this->assertSame([['student', 1, 'validee']], $decisions($liste('validees')));
        $this->assertSame([['other', 1, 'attente']], $decisions($liste('validees', ['liste' => 'attente'])));
        $this->assertSame(['validees' => 1, 'attente' => 1], array_intersect_key($liste('validees')['classement']['compteurs'], ['validees' => 0, 'attente' => 0]));

        // Approbation : seules les demandes validées passent dans la liste définitive ; la file d'attente reste.
        $this->post(route('admin.hebergement.campagnes_admission.liste_validee_approuver', ['volet' => 'toutes']))->assertRedirect();
        $this->assertSame([], $liste('validees')['classement']['lignes']['data']);
        $this->assertSame([['other', 1, 'attente']], $decisions($liste('validees', ['liste' => 'attente'])));
        $this->assertSame([['student', 1, null]], $decisions($liste('definitives')));
        $this->assertNull(DB::table('demande')->where('id_demande', $idOther)->value('date_approbation_commission'));

        // Validation depuis la file d'attente (toujours permise après l'approbation de la liste des demandes validées) :
        // la demande passe dans « Demandes validées ».
        $this->travel(1)->minutes();
        $transition = route('admin.dossiers.transition', $idOther);
        $this->post($transition, ['etat' => 'eligible'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame([['other', 1, 'validee']], $decisions($liste('validees')));

        // Approbation de la file d'attente : la demande (remise en attente depuis « Demandes validées ») est validée
        // (« Recevable ») puis passe dans la liste définitive.
        $this->travel(1)->minutes();
        $this->post($attente)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame([['other', 1, 'attente']], $decisions($liste('validees', ['liste' => 'attente'])));
        $this->post($approuverAttente)->assertRedirect(route('admin.hebergement.campagnes_admission.admission', ['etat' => 'definitives', 'liste' => 'attente']));
        $this->assertSame(['eligible', 'attente'], [DB::table('demande')->where('id_demande', $idOther)->value('etat_dossier'), DB::table('demande')->where('id_demande', $idOther)->value('liste_approbation_commission')]);
        $fileVide = $liste('validees', ['liste' => 'attente'])['classement'];
        $this->assertSame([], $fileVide['lignes']['data']);
        $this->assertSame(1, $fileVide['approuvees']['attente']);
        // Listes définitives : chaque demande figure dans l'onglet de la liste approuvée.
        $this->assertSame([['student', 1, null]], $decisions($liste('definitives')));
        $fileDefinitive = $liste('definitives', ['liste' => 'attente'])['classement'];
        $this->assertSame([['other', 1, null]], $decisions(['classement' => $fileDefinitive]));
        // File d'attente des listes définitives : téléchargeable une fois publiée.
        $this->assertNull($fileDefinitive['exports']);
        $this->post($fileDefinitive['publication']['url'])->assertRedirect(route('admin.hebergement.campagnes_admission.admission', ['etat' => 'definitives', 'liste' => 'attente']));
        $this->assertStringEndsWith('/liste-definitive/attente/toutes/pdf', $liste('definitives', ['liste' => 'attente'])['classement']['exports']['pdf']);
        // File d'attente vide : rien à approuver.
        $this->post($approuverAttente)->assertSessionHasErrors('approbation');

        // Rejet puis réexamen après l'approbation de la liste des demandes validées : la demande revient dans la liste
        // provisoire, clôturée : elle ne peut plus y être validée ni mise en attente, seulement rejetée.
        $this->travel(1)->minutes();
        $this->post($transition, ['etat' => 'refuse', 'motif' => 'Pièce non conforme.'])->assertRedirect();
        $this->assertSame([], $liste('definitives', ['liste' => 'attente'])['classement']['lignes']['data']);
        $this->post($transition, ['etat' => 'soumis', 'motif' => 'Réexamen.'])->assertRedirect();
        $this->assertSame([['other', 1, null]], $decisions($liste('recevables')));
        $ligne = $liste('recevables')['classement']['lignes']['data'][0];
        $this->assertSame([false, null, true], [$ligne['decision']['valider'], $ligne['decision']['attente'], $ligne['decision']['rejeter']]);
        $this->post($attente)->assertSessionHasErrors('attente');
        $this->post($transition, ['etat' => 'eligible'])->assertSessionHasErrors('etat');

        // Sans le droit de valider les admissions : pas de mise en attente ni d'approbation de la file d'attente.
        DB::table('demande')->where('id_demande', $idOther)->update(['etat_dossier' => 'soumis', 'date_attente_commission' => null, 'date_approbation_commission' => null]);
        DB::table('dossier_transitions')->where('demande_id', $idOther)->delete();
        $this->agentLimite(['hebergement.admissions.personal']);
        $this->assertNull($liste('recevables')['classement']['lignes']['data'][0]['decision']);
        $this->post($attente)->assertForbidden();
        $this->post($approuverAttente)->assertForbidden();
    }

    public function test_mise_en_attente_d_une_demande_validee(): void
    {
        $this->agentEligibilite(['hebergement.admissions.validate']);
        foreach (['2026_11_17_000001_add_ville_bac_to_demande.php', '2026_11_20_000001_add_approbation_commission_to_demande.php', '2026_11_20_000002_add_attente_commission_to_demande.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        DB::table('demande')->where('etudiant_id', 'student')->update(['niveau_etude_precedent_code' => 'TLE', 'niveau_etude_actuel_code' => 'L1', 'moyenne' => 12, 'ville_bac' => 'Bondoukou']);
        $liste = fn (string $etat, array $query = []) => $this->page('liste', ['etat' => $etat], '/admin/gestion-hebergement/campagnes_admissions/liste/'.$etat, ['liste' => 'admission', ...$query])['props'];
        $idDemande = DB::table('demande')->where('etudiant_id', 'student')->value('id_demande');

        $this->post(route('admin.dossiers.transition', $idDemande), ['etat' => 'eligible'])->assertRedirect();
        // Demandes validées : « Mettre en attente » et « Rejeter » (plus de validation).
        $decision = $liste('validees')['classement']['lignes']['data'][0]['decision'];
        $attente = route('admin.hebergement.campagnes_admission.mettre_en_attente', $idDemande);
        $this->assertSame([false, $attente, true], [$decision['valider'], $decision['attente'], $decision['rejeter']]);

        // Mise en attente : le dossier revient en « Soumis » (transition tracée) et la demande passe dans la file d'attente.
        $this->travel(1)->minutes();
        $this->post($attente)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('soumis', DB::table('demande')->where('id_demande', $idDemande)->value('etat_dossier'));
        $derniere = DB::table('dossier_transitions')->where('demande_id', $idDemande)->orderByDesc('id')->first();
        $this->assertSame(['eligible', 'soumis', 'admin'], [$derniere->ancien_etat, $derniere->nouvel_etat, $derniere->acteur_type]);
        $this->assertSame([], $liste('validees')['classement']['lignes']['data']);
        $this->assertSame(['student'], collect($liste('validees', ['liste' => 'attente'])['classement']['lignes']['data'])->pluck('id')->all());
        $this->assertSame([], $liste('recevables')['classement']['lignes']['data']);

        // Une demande approuvée (liste définitive) ne peut plus être mise en attente.
        $this->post(route('admin.hebergement.campagnes_admission.file_attente_approuver', ['volet' => 'toutes']))->assertRedirect();
        $this->post($attente)->assertSessionHasErrors('attente');
        $this->assertSame('eligible', DB::table('demande')->where('id_demande', $idDemande)->value('etat_dossier'));
    }

    public function test_modification_refusee_sans_le_droit_de_modifier_les_admissions(): void
    {
        $this->agentLimite();

        $this->put(route('admin.hebergement.campagnes_admission.update_etudiant', ['etudiant' => 'student']), ['name' => 'Pirate'])
            ->assertForbidden();
        $this->assertSame('Konan Awa', DB::table('etudiants')->where('id_etudiant', 'student')->value('name'));
    }
}
