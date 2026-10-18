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
    protected function setUp(): void
    {
        parent::setUp();
        Schema::table('etudiants', function (Blueprint $t) {
            $t->string('matricule')->nullable();
            $t->date('date_inscription')->nullable();
            $t->boolean('is_validation')->default(false);
            $t->boolean('is_rejet')->default(false);
        });
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
    }

    public function test_suivi_propose_les_transitions_permises(): void
    {
        $this->agentLimite(['hebergement.admissions.validate', 'hebergement.admissions.history']);

        $page = $this->page('show', ['id' => 'student'], '/admin/gestion-hebergement/campagnes_admissions/student');

        $suivi = $page['props']['suivi'];
        // Depuis « soumis », l'agent peut passer à : incomplet, en étude, refusé, annulé (pas « expiré », réservé au système).
        $this->assertSame(['incomplet', 'en_etude', 'refuse', 'annule'], array_column($suivi['actions'], 'etat'));
        $this->assertNotNull($suivi['transition']);
        $this->assertNotEmpty($suivi['historique']);
    }
}
