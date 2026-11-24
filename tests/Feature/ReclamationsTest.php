<?php

namespace Tests\Feature;

use App\Http\Controllers\admin\immobilier\campagnes_admission\CampagnesAdmissionController;
use App\Models\Reclamation;
use App\Models\ReclamationPeriode;
use App\Services\AdminAccess;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** Réclamations : période ouverte, formulaire public, liste et traitement, motif sur la fiche de la demande, droits. */
class ReclamationsTest extends \Tests\Support\DossierTestCase
{
    use \Tests\Support\EditionEtudiantTables;

    private const ROUTE = 'admin.hebergement.reclamations.';

    private const DROITS = ['hebergement.reclamations.view', 'hebergement.reclamations.traiter', 'hebergement.reclamations.configure'];

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
        foreach (['date_naissance' => 'etudiants', 'libelle' => 'campagne', 'annee_campagne' => 'campagne', 'periode_fin' => 'campagne'] as $colonne => $table) {
            if (! Schema::hasColumn($table, $colonne)) {
                Schema::table($table, fn (Blueprint $t) => $t->string($colonne)->nullable());
            }
        }
        foreach (['admission', 'readmission'] as $volet) {
            if (! Schema::hasColumn('campagne', $volet)) {
                Schema::table('campagne', fn (Blueprint $t) => $t->boolean($volet)->default(false));
            }
        }
        Schema::table('admission_derogations', function (Blueprint $t) {
            $t->string('etudiant_id')->nullable();
            $t->string('campagne_id')->nullable();
            $t->text('reason')->nullable();
            $t->string('decider_name')->nullable();
            $t->timestamps();
        });
        (require database_path('migrations/2026_11_24_000005_create_reclamations_tables.php'))->up();
        (require database_path('migrations/2026_11_24_000007_add_num_bac_matricule_to_reclamations.php'))->up();
        (require database_path('migrations/2026_11_24_000008_make_date_naissance_nullable_on_reclamations.php'))->up();

        DB::table('campagne')->update(['libelle' => 'Campagne test', 'annee_campagne' => '2026-2027', 'admission' => true, 'periode_fin' => today()->subDays(2)->toDateString()]);
        DB::table('etudiants')->where('id_etudiant', 'student')->update(['name' => 'KONÉ Awa Marie', 'date_naissance' => '2003-05-12', 'statut_inscription' => 'demande', 'matricule' => 'M-001']);
        DB::table('etudiants')->where('id_etudiant', 'other')->update(['name' => 'YAO Jean', 'date_naissance' => '2002-01-01', 'statut_inscription' => 'demande']);
        $this->dossier();
    }

    private function periode(int $debut = -1, int $fin = 5): ReclamationPeriode
    {
        return ReclamationPeriode::create([
            'campagne_id' => 'campaign',
            'date_debut' => today()->addDays($debut)->toDateString(),
            'date_fin' => today()->addDays($fin)->toDateString(),
        ]);
    }

    private function saisie(array $valeurs = []): array
    {
        return $valeurs + [
            'nom_prenoms' => 'awa marie  kone', 'num_bac_matricule' => ' m-001 ',
            'type_demande' => 'admission', 'motif' => 'Ma moyenne a été mal saisie.',
        ];
    }

    private function agent(array $permissions): void
    {
        $actor = Mockery::mock(\App\Models\User::class)->makePartial();
        $actor->fill(['id_user' => 'agent-rec', 'name' => 'Agent Réclamation']);
        $actor->shouldReceive('hasPermission')->andReturnUsing(fn ($permission) => in_array($permission, $permissions, true));
        $this->actingAs($actor, 'admin');
    }

    /** Page Inertia rendue (lue dans `data-page` : un middleware global reconstruit la réponse). */
    private function page(string $url): array
    {
        $this->withoutVite();
        $html = $this->get($url)->assertOk()->getContent();
        preg_match('/data-page="([^"]+)"/', $html, $matches);

        return json_decode(html_entity_decode($matches[1] ?? ''), true) ?? [];
    }

    private function fiche(string $etudiant, array $query = []): array
    {
        $request = Request::create('/admin/gestion-hebergement/campagnes_admissions/'.$etudiant, 'GET', $query);
        $request->headers->set('X-Inertia', 'true');
        $request->setUserResolver(fn ($guard = null) => auth('admin')->user());
        $this->app->instance('request', $request);

        return app()->call([app(CampagnesAdmissionController::class), 'show'], ['request' => $request, 'id' => $etudiant])
            ->toResponse($request)->getData(true)['props'];
    }

    public function test_lien_de_reclamation_sur_la_page_de_connexion_pendant_une_periode_ouverte(): void
    {
        $this->assertNull($this->page('/login')['props']['reclamationUrl']);

        $aVenir = $this->periode(2, 5);
        $this->assertNull($this->page('/login')['props']['reclamationUrl']);

        $aVenir->update(['date_debut' => today(), 'date_fin' => today()]);
        $this->assertSame(route('etudiant.reclamation'), $this->page('/login')['props']['reclamationUrl']);

        $aVenir->update(['date_debut' => today()->subDays(3), 'date_fin' => today()->subDay()]);
        $this->assertNull($this->page('/login')['props']['reclamationUrl']);

        // Campagne supprimée : la période n'ouvre plus rien.
        $aVenir->update(['date_fin' => today()]);
        DB::table('campagne')->update(['deleted_at' => now()]);
        $this->assertNull($this->page('/login')['props']['reclamationUrl']);
    }

    public function test_formulaire_public_propose_les_types_ouverts_par_la_campagne(): void
    {
        $this->assertNull($this->page('/reclamation')['props']['periode']);
        $this->periode();

        $props = $this->page('/reclamation')['props'];
        $this->assertSame('Campagne test · 2026-2027', $props['periode']['campagne']);
        $this->assertSame([['value' => 'admission', 'label' => 'Admission']], $props['types']);
    }

    public function test_depot_rattache_a_la_demande_de_l_etudiant(): void
    {
        $periode = $this->periode();
        $this->from('/reclamation')->post('/reclamation', $this->saisie())->assertRedirect('/reclamation')->assertSessionHas('success');

        $reclamation = Reclamation::sole();
        $this->assertSame('student', $reclamation->etudiant_id);
        $this->assertSame($this->workflow->current('student', 'campaign')->getKey(), $reclamation->demande_id);
        $this->assertSame([$periode->getKey(), 'campaign', 'en_attente'], [$reclamation->periode_id, $reclamation->campagne_id, $reclamation->statut]);
        $this->assertMatchesRegularExpression('/^REC-\d{4}-\d{5}$/', $reclamation->numero);
        $this->assertStringContainsString($reclamation->numero, session('success'));
        // L'état du dossier n'est pas touché.
        $this->assertSame('soumis', $this->workflow->current('student', 'campaign')->etat_dossier);

        // Une seule réclamation non traitée par demande ; une nouvelle est possible après décision.
        $this->from('/reclamation')->post('/reclamation', $this->saisie())->assertSessionHasErrors('motif');
        $reclamation->update(['statut' => 'rejetee']);
        $this->from('/reclamation')->post('/reclamation', $this->saisie())->assertSessionHasNoErrors();
        $this->assertSame(2, Reclamation::count());
    }

    public function test_depot_refuse_hors_periode_ou_sans_demande_correspondante(): void
    {
        $this->from('/reclamation')->post('/reclamation', $this->saisie())->assertSessionHas('error');
        $this->periode();

        $this->from('/reclamation')->post('/reclamation', [])->assertSessionHasErrors(['nom_prenoms', 'num_bac_matricule', 'type_demande', 'motif']);
        $this->from('/reclamation')->post('/reclamation', $this->saisie(['num_bac_matricule' => 'M-002']))->assertSessionHasErrors('num_bac_matricule');
        $this->from('/reclamation')->post('/reclamation', $this->saisie(['nom_prenoms' => 'KONÉ Awa']))->assertSessionHasErrors('nom_prenoms');
        // Étudiant sans demande pour cette campagne.
        $this->from('/reclamation')->post('/reclamation', $this->saisie(['nom_prenoms' => 'YAO Jean']))->assertSessionHasErrors('nom_prenoms');
        // Volet fermé par la campagne.
        $this->from('/reclamation')->post('/reclamation', $this->saisie(['type_demande' => 'readmission']))->assertSessionHasErrors('type_demande');
        // Les deux volets ouverts : le type choisi doit être celui de la demande.
        DB::table('campagne')->update(['readmission' => true]);
        $this->from('/reclamation')->post('/reclamation', $this->saisie(['type_demande' => 'readmission']))->assertSessionHasErrors('type_demande');
        // Homonymes dont aucune fiche ne porte de numéro.
        DB::table('etudiants')->where('id_etudiant', 'student')->update(['matricule' => null]);
        DB::table('etudiants')->where('id_etudiant', 'other')->update(['name' => 'Kone Marie Awa']);
        $this->dossier('other');
        $this->from('/reclamation')->post('/reclamation', $this->saisie())->assertSessionHasErrors('nom_prenoms');

        $this->assertSame(0, Reclamation::count());
    }

    public function test_numero_de_table_ou_matricule_departage_les_homonymes(): void
    {
        $this->periode();
        DB::table('etudiants')->where('id_etudiant', 'other')->update(['name' => 'Kone Marie Awa']);
        $this->dossier('other');
        if (! Schema::hasColumn('demande', 'num_table_bac')) {
            Schema::table('demande', fn (Blueprint $t) => $t->string('num_table_bac', 50)->nullable());
        }
        DB::table('demande')->where('etudiant_id', 'other')->update(['num_table_bac' => '2501234567']);

        $this->post('/reclamation', $this->saisie())->assertSessionHasNoErrors();
        $this->post('/reclamation', $this->saisie(['num_bac_matricule' => '25 0123 4567']))->assertSessionHasNoErrors();

        $this->assertSame(['other' => '25 0123 4567', 'student' => 'M-001'], Reclamation::orderBy('etudiant_id')->pluck('num_bac_matricule', 'etudiant_id')->all());
    }

    public function test_fiche_sans_numero_rattachee_par_le_nom(): void
    {
        DB::table('etudiants')->where('id_etudiant', 'student')->update(['matricule' => null]);
        $this->periode();
        $this->post('/reclamation', $this->saisie(['num_bac_matricule' => '2509999999']))->assertSessionHasNoErrors();
        $this->assertSame('student', Reclamation::sole()->etudiant_id);
    }

    public function test_liste_par_statut_avec_les_informations_de_la_demande(): void
    {
        $this->periode();
        $this->post('/reclamation', $this->saisie());
        Reclamation::sole()->update(['statut' => 'acceptee']);
        $this->post('/reclamation', $this->saisie(['motif' => 'Deuxième réclamation.']));
        $this->agent(self::DROITS);

        $page = $this->page(route(self::ROUTE.'liste'));
        $this->assertSame('Admin/Hebergement/Reclamations/Liste', $page['component']);
        $this->assertSame(['' => 2, 'en_attente' => 1, 'ouverte' => 0, 'acceptee' => 1, 'rejetee' => 0], $page['props']['compteurs']);
        $this->assertSame(2, $page['props']['reclamations']['total']);

        $props = $this->page(route(self::ROUTE.'liste', ['statut' => 'en_attente', 'q' => 'awa']))['props'];
        $this->assertSame(1, $props['reclamations']['total']);
        $ligne = $props['reclamations']['data'][0];
        $this->assertSame('Deuxième réclamation.', $ligne['motif']);
        $this->assertSame('2003-05-12', $ligne['date_naissance']);
        $this->assertSame(['M-001', 'M-001'], [$ligne['matricule'], $ligne['num_bac_matricule']]);
        $this->assertSame(['numero'], array_keys($ligne['demande']));
        $this->assertNotEmpty($ligne['demande']['numero']);
        $this->assertSame(route('admin.hebergement.campagnes_admission.show', ['id' => 'student', 'origine' => 'reclamations']), $ligne['url_dossier']);
        $this->assertSame(route(self::ROUTE.'index'), $props['urls']['accueil']);

        $this->assertSame(0, $this->page(route(self::ROUTE.'liste', ['q' => 'inconnu']))['props']['reclamations']['total']);
        $this->assertSame(0, $this->page(route(self::ROUTE.'liste', ['type' => 'readmission']))['props']['reclamations']['total']);
    }

    public function test_traitement_change_le_statut(): void
    {
        $this->periode();
        $this->post('/reclamation', $this->saisie());
        $reclamation = Reclamation::sole();
        $url = route(self::ROUTE.'traiter', $reclamation->getKey());
        $this->agent(self::DROITS);

        $this->put($url, ['statut' => 'en_cours'])->assertSessionHasErrors('statut');
        // Ouverte : choisie par l'agent, encore non traitée (pas de seconde réclamation).
        $this->put($url, ['statut' => 'ouverte'])->assertSessionHasNoErrors();
        $this->assertSame(1, $this->page(route(self::ROUTE.'liste', ['statut' => 'ouverte']))['props']['compteurs']['ouverte']);
        $this->from('/reclamation')->post('/reclamation', $this->saisie())->assertSessionHasErrors('motif');
        $this->put($url, ['statut' => 'en_attente'])->assertSessionHasNoErrors();
        $reclamation->update(['reponse' => 'Ancienne réponse.']);
        $this->put($url, ['statut' => 'acceptee'])->assertSessionHasNoErrors();

        $reclamation->refresh();
        $this->assertSame(['acceptee', 'Ancienne réponse.', 'agent-rec'], [$reclamation->statut, $reclamation->reponse, $reclamation->traite_par]);
        $this->assertNotNull($reclamation->traite_at);

        $this->agent(['hebergement.reclamations.view']);
        $this->put($url, ['statut' => 'rejetee'])->assertForbidden();
        $this->assertNull($this->page(route(self::ROUTE.'liste'))['props']['reclamations']['data'][0]['url_traiter']);
    }

    public function test_configuration_des_periodes(): void
    {
        $this->agent(self::DROITS);
        $donnees = ['campagne_id' => 'campaign', 'date_debut' => today()->toDateString(), 'date_fin' => today()->addDays(3)->toDateString()];

        $this->post(route(self::ROUTE.'periodes.store'), ['campagne_id' => 'absente', 'date_debut' => '2026-12-05', 'date_fin' => '2026-12-01'])
            ->assertSessionHasErrors(['campagne_id', 'date_fin']);
        $this->post(route(self::ROUTE.'periodes.store'), $donnees)->assertSessionHasNoErrors();
        $periode = ReclamationPeriode::sole();

        $ligne = $this->page(route(self::ROUTE.'configuration'))['props']['periodes']['data'][0];
        $this->assertSame(['campaign', 'ouverte', 0], [$ligne['campagne_id'], $ligne['etat'], $ligne['reclamations']]);
        $this->assertSame('Campagne test · 2026-2027', $ligne['campagne']['libelle']);

        // Chevauchement refusé (une seule période ouverte à la fois) ; la période elle-même reste modifiable.
        $this->post(route(self::ROUTE.'periodes.store'), $donnees)->assertSessionHasErrors('date_debut');
        $this->put(route(self::ROUTE.'periodes.update', $periode->getKey()), ['date_fin' => today()->addDay()->toDateString()] + $donnees)->assertSessionHasNoErrors();
        $this->assertSame(today()->addDay()->toDateString(), $periode->refresh()->date_fin->toDateString());

        // Dès qu'une réclamation existe : campagne figée et suppression refusée.
        $this->post('/reclamation', $this->saisie())->assertSessionHasNoErrors();
        DB::table('campagne')->insert(['id_campagne' => 'autre', 'statut' => 'active', 'periode_fin' => today()->subDays(2)->toDateString()]);
        $this->put(route(self::ROUTE.'periodes.update', $periode->getKey()), ['campagne_id' => 'autre'] + $donnees)->assertSessionHasErrors('campagne_id');
        $this->delete(route(self::ROUTE.'periodes.destroy', $periode->getKey()))->assertSessionHasErrors('periode');
        Reclamation::query()->delete();
        $this->delete(route(self::ROUTE.'periodes.destroy', $periode->getKey()))->assertSessionHasNoErrors();
        $this->assertSame(0, ReclamationPeriode::count());
    }

    public function test_la_reclamation_commence_apres_la_fin_de_la_campagne(): void
    {
        $this->agent(self::DROITS);
        $fin = today()->addDays(4);
        DB::table('campagne')->update(['periode_fin' => $fin->toDateString()]);
        $donnees = fn ($debut) => ['campagne_id' => 'campaign', 'date_debut' => $debut->toDateString(), 'date_fin' => $fin->copy()->addDays(10)->toDateString()];

        // Avant la fin ou le dernier jour de la campagne : refusé.
        foreach ([today(), $fin] as $debut) {
            $this->post(route(self::ROUTE.'periodes.store'), $donnees($debut))->assertSessionHasErrors('date_debut');
        }
        $this->post(route(self::ROUTE.'periodes.store'), $donnees($fin->copy()->addDay()))->assertSessionHasNoErrors();
        $periode = ReclamationPeriode::sole();
        $this->put(route(self::ROUTE.'periodes.update', $periode->getKey()), $donnees($fin))->assertSessionHasErrors('date_debut');
        $this->assertSame($fin->toDateString(), $this->page(route(self::ROUTE.'configuration'))['props']['campagnes'][0]['fin']);

        // Prolonger la campagne sur la période de réclamation est refusé ; la raccourcir reste possible.
        $service = app(\App\Http\Services\ReclamationService::class);
        $service->verifierFinCampagne('campaign', $fin->copy()->subDay()->toDateString());
        $service->verifierFinCampagne('campaign', $fin->toDateString());
        try {
            $service->verifierFinCampagne('campaign', $fin->copy()->addDay()->toDateString());
            $this->fail('La prolongation de la campagne devait être refusée.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('periode_fin', $e->errors());
        }

        // Campagne sans date de fin.
        $periode->delete();
        DB::table('campagne')->update(['periode_fin' => null]);
        $this->post(route(self::ROUTE.'periodes.store'), $donnees(today()))->assertSessionHasErrors('campagne_id');
    }

    public function test_fiche_de_la_demande_affiche_le_motif_de_la_reclamation(): void
    {
        $this->periode();
        $this->post('/reclamation', $this->saisie());

        // Droit de consulter les réclamations seul : la fiche de l'étudiant concerné est ouverte, sans les autres blocs.
        $this->agent(['hebergement.reclamations.view']);
        $props = $this->fiche('student', ['origine' => 'reclamations']);
        $this->assertSame('Ma moyenne a été mal saisie.', $props['reclamations'][0]['motif']);
        $this->assertNull($props['reclamations'][0]['url_traiter']);
        $this->assertSame(route(self::ROUTE.'liste'), $props['urls']['reclamations']);
        $this->assertNull($props['profil']);
        $this->assertNull($props['documents']);
        $this->assertNull($props['urls']['deroger']);

        // Sans ce droit : aucun motif, et pas de lien de retour vers les réclamations.
        $this->agent(['hebergement.residents.view']);
        $props = $this->fiche('student', ['origine' => 'reclamations']);
        $this->assertSame([], $props['reclamations']);
        $this->assertNull($props['urls']['reclamations']);

        // Étudiant sans réclamation : le droit sur les réclamations n'ouvre pas sa fiche.
        $this->agent(['hebergement.reclamations.view']);
        $this->expectException(HttpException::class);
        $this->fiche('other');
    }

    public function test_droits_du_module(): void
    {
        $access = app(AdminAccess::class);
        $routes = ['index', 'liste', 'configuration', 'periodes.store', 'periodes.update', 'periodes.destroy', 'traiter'];
        $attendus = [
            'hebergement.reclamations.view' => ['index', 'liste'],
            'hebergement.reclamations.traiter' => ['traiter'],
            'hebergement.reclamations.configure' => ['index', 'configuration', 'periodes.store', 'periodes.update', 'periodes.destroy'],
            'hebergement.admissions.view' => [],
        ];
        foreach ($attendus as $droit => $autorisees) {
            $this->agent([$droit]);
            foreach ($routes as $route) {
                $this->assertSame(in_array($route, $autorisees, true), $access->allows(auth('admin')->user(), self::ROUTE.$route), "$droit / $route");
            }
        }

        // Accueil : une carte par page accessible.
        $cartes = function (array $droits) {
            $this->agent($droits);
            $page = $this->page(route(self::ROUTE.'index'));
            $this->assertSame('Admin/Hebergement/Reclamations/Index', $page['component']);

            return array_column($page['props']['modules'], 'href');
        };
        $this->assertSame([route(self::ROUTE.'liste'), route(self::ROUTE.'configuration')], $cartes(self::DROITS));
        $this->assertSame([route(self::ROUTE.'liste')], $cartes(['hebergement.reclamations.view']));
        $this->assertSame([route(self::ROUTE.'configuration')], $cartes(['hebergement.reclamations.configure']));

        $this->get(route(self::ROUTE.'liste'))->assertForbidden();
        $this->agent([]);
        $this->get(route(self::ROUTE.'index'))->assertForbidden();
        $this->get(route(self::ROUTE.'configuration'))->assertForbidden();
    }

    public function test_migrations_des_droits_idempotentes_et_accordees(): void
    {
        foreach (['role_permission', 'permissions', 'modules', 'roles'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
        DB::table('roles')->insert([['id' => 1, 'name' => 'Administrateur'], ['id' => 2, 'name' => 'Agent']]);
        foreach (['2026_08_11_000001_create_role_permissions_tables.php', '2026_08_11_000002_create_modules_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        for ($i = 0; $i < 2; $i++) {
            (require database_path('migrations/2026_11_24_000006_add_reclamations_permissions.php'))->up();
        }
        // Un rôle qui avait le droit de traiter (`.update`) le garde sous son nouveau nom.
        DB::table('role_permission')->insert(['role_id' => 2, 'permission_id' => DB::table('permissions')->where('slug', 'hebergement.reclamations.update')->value('id')]);
        for ($i = 0; $i < 2; $i++) {
            (require database_path('migrations/2026_11_24_000009_rename_reclamations_update_permission_to_traiter.php'))->up();
        }

        $this->assertSame(self::DROITS, DB::table('permissions')->where('slug', 'like', 'hebergement.reclamations.%')->orderBy('id')->pluck('slug')->all());
        $roles = DB::table('role_permission')->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
            ->where('slug', 'like', 'hebergement.reclamations.%')->orderBy('role_id')->pluck('role_id')->all();
        $this->assertSame([1, 1, 1, 2], array_map('intval', $roles));
        $this->assertSame(1, DB::table('role_permission')->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
            ->where('slug', 'hebergement.reclamations.traiter')->where('role_id', 2)->count());
        $this->assertSame(['view', 'traiter', 'configure'], json_decode(DB::table('modules')->where('slug', 'hebergement.reclamations')->value('actions'), true));
        $this->assertSame(1, DB::table('modules')->where('slug', 'hebergement.reclamations')->count());
    }
}
