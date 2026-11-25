<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AdminAccess;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;

/** Menu « Résultats d'admission » : demandes publiées par statut, visibilité de la page publique, droits. */
class ResultatsAdminPageTest extends \Tests\Support\DossierTestCase
{
    use \Tests\Support\EditionEtudiantTables;

    private const ROUTE = 'admin.hebergement.resultats.';

    private const DROITS = ['hebergement.resultats.view', 'hebergement.resultats.publier', 'hebergement.resultats.export', 'hebergement.resultats.admettre'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Schema::table('etudiants', fn (Blueprint $t) => $t->string('matricule')->nullable());
        $this->creerTablesEditionEtudiant();
        foreach (['libelle' => 'campagne', 'annee_campagne' => 'campagne', 'periode_debut' => 'campagne', 'periode_fin' => 'campagne', 'num_table_bac' => 'demande'] as $colonne => $table) {
            if (! Schema::hasColumn($table, $colonne)) {
                Schema::table($table, fn (Blueprint $t) => $t->string($colonne)->nullable());
            }
        }
        foreach (['2026_11_24_000005_create_reclamations_tables.php', '2026_11_25_000002_create_resultats_admission_table.php', '2026_11_25_000003_create_resultats_visibilite_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        DB::table('campagne')->update(['libelle' => 'Campagne test', 'annee_campagne' => '2026-2027']);
        $resultats = ['student' => ['KONÉ Awa', 'admis', 'confirme'], 'other' => ['YAO Jean', 'recevable_non_admis', 'eligible'], 'troisieme' => ['BAMBA Ali', 'non_recevable', 'refuse']];
        foreach ($resultats as $id => [$nom, $resultat, $etat]) {
            DB::table('etudiants')->updateOrInsert(['id_etudiant' => $id], ['name' => $nom, 'matricule' => 'M-'.$id, 'genre_id' => 1, 'statut_inscription' => 'demande']);
            $demande = $this->dossier($id);
            DB::table('demande')->where('etudiant_id', $id)->update(['etat_dossier' => $etat]);
            DB::table('resultats_admission')->insert(['id' => $id, 'campagne_id' => 'campaign', 'demande_id' => DB::table('demande')->where('etudiant_id', $id)->value('id_demande'),
                'etudiant_id' => $id, 'volet' => $id === 'other' ? 'readmission' : 'admission', 'resultat' => $resultat, 'publie_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    private function agent(array $permissions): void
    {
        $actor = Mockery::mock(User::class)->makePartial();
        $actor->fill(['id_user' => 'agent-res', 'name' => 'Agent Résultats']);
        $actor->shouldReceive('hasPermission')->andReturnUsing(fn ($permission) => in_array($permission, $permissions, true));
        $this->actingAs($actor, 'admin');
    }

    private function page(string $url): array
    {
        preg_match('/data-page="([^"]+)"/', $this->get($url)->assertOk()->getContent(), $matches);

        return json_decode(html_entity_decode($matches[1] ?? ''), true) ?? [];
    }

    private function liste(array $query = []): array
    {
        $page = $this->page(route(self::ROUTE.'index', $query));
        $this->assertSame('Admin/Hebergement/Resultats/Index', $page['component']);

        return $page['props'];
    }

    private function noms(array $props): array
    {
        return array_column($props['resultats']['data'], 'etudiant');
    }

    public function test_demandes_par_statut(): void
    {
        $this->agent(self::DROITS);
        $props = $this->liste();
        $this->assertSame(['KONÉ Awa'], $this->noms($props));
        $this->assertSame(['validees' => 1, 'attente' => 1, 'non_recevables' => 1], $props['compteurs']);
        $this->assertSame([['campaign', 'Campagne test · 2026-2027', false]], array_map(fn ($c) => [$c['id'], $c['libelle'], $c['visible']], $props['campagnes']));
        $this->assertFalse($props['resultats']['data'][0]['remise_en_cause']);

        $this->assertSame(['YAO Jean'], $this->noms($this->liste(['statut' => 'attente'])));
        $this->assertSame(['BAMBA Ali'], $this->noms($this->liste(['statut' => 'non_recevables'])));
        // Statut inconnu : premier onglet.
        $this->assertSame(['KONÉ Awa'], $this->noms($this->liste(['statut' => 'autre', 'campagne' => 'inconnue'])));
    }

    public function test_filtres_et_admission_remise_en_cause(): void
    {
        $this->agent(self::DROITS);
        $this->assertSame(['validees' => 0, 'attente' => 1, 'non_recevables' => 0], $this->liste(['type' => 'readmission'])['compteurs']);
        $this->assertSame(['validees' => 0, 'attente' => 0, 'non_recevables' => 1], $this->liste(['q' => 'M-troisieme'])['compteurs']);

        DB::table('demande')->where('etudiant_id', 'student')->update(['etat_dossier' => 'annule']);
        $this->assertTrue($this->liste()['resultats']['data'][0]['remise_en_cause']);
    }

    public function test_campagne_de_la_derniere_publication_par_defaut(): void
    {
        $this->agent(self::DROITS);
        DB::table('campagne')->insert(['id_campagne' => 'recente', 'libelle' => 'Récente', 'statut' => 'active', 'created_at' => now()]);
        DB::table('resultats_admission')->where('id', 'other')->update(['campagne_id' => 'recente', 'publie_at' => now()->addMinute()]);

        $props = $this->liste(['statut' => 'attente']);
        $this->assertSame(['recente', 'campaign'], array_column($props['campagnes'], 'id'));
        $this->assertSame(['YAO Jean'], $this->noms($props));
        $this->assertSame(['KONÉ Awa'], $this->noms($this->liste(['campagne' => 'campaign'])));
    }

    public function test_la_page_publique_est_affichee_pour_une_campagne_cochee_puis_masquee(): void
    {
        $this->agent(self::DROITS);
        // Rien n'est visible tant qu'aucune campagne n'est cochée.
        $this->assertFalse($this->liste()['visibilite']['visible']);
        $this->assertNull($this->page('/login')['props']['resultatsUrl']);
        $this->put(route(self::ROUTE.'visibilite'), [])->assertSessionHasErrors('visible');
        $this->put(route(self::ROUTE.'visibilite'), ['visible' => true, 'campagnes' => []])->assertSessionHasErrors(['campagnes' => 'Cochez au moins une campagne.']);
        $this->put(route(self::ROUTE.'visibilite'), ['visible' => true, 'campagnes' => ['inconnue']])->assertSessionHasErrors('campagnes.0');
        $this->assertDatabaseCount('resultats_visibilite', 0);

        $this->from(route(self::ROUTE.'index'))->put(route(self::ROUTE.'visibilite'), ['visible' => true, 'campagnes' => ['campaign']])
            ->assertRedirect(route(self::ROUTE.'index'))->assertSessionHas('success');
        $this->assertDatabaseHas('resultats_visibilite', ['campagne_id' => 'campaign', 'modifie_par' => 'Agent Résultats']);
        $props = $this->liste();
        $this->assertSame([true, true], [$props['visibilite']['visible'], $props['campagnes'][0]['visible']]);
        $this->assertSame(route('etudiant.resultats'), $this->page('/login')['props']['resultatsUrl']);
        $this->post(route('etudiant.resultats.show'), ['numero' => 'M-student'])->assertOk()->assertSee('KON');

        $this->put(route(self::ROUTE.'visibilite'), ['visible' => false])->assertSessionHas('success');
        $this->assertDatabaseCount('resultats_visibilite', 0);
        $this->assertNull($this->page(route('etudiant.resultats'))['props']['campagne']);
        $this->assertNull($this->page('/login')['props']['resultatsUrl']);
        $this->post(route('etudiant.resultats.show'), ['numero' => 'M-student'])
            ->assertSessionHasErrors(['numero' => 'Les résultats ne sont pas consultables pour le moment.']);
    }

    public function test_seules_les_campagnes_cochees_sont_consultables(): void
    {
        $this->agent(self::DROITS);
        DB::table('campagne')->insert(['id_campagne' => 'recente', 'libelle' => 'Récente', 'statut' => 'active', 'created_at' => now()]);
        DB::table('resultats_admission')->where('id', 'other')->update(['campagne_id' => 'recente', 'publie_at' => now()->addMinute()]);
        DB::table('demande')->where('etudiant_id', 'other')->update(['campagne_id' => 'recente']);
        $consulter = fn (string $numero) => $this->post(route('etudiant.resultats.show'), ['numero' => $numero]);

        $this->put(route(self::ROUTE.'visibilite'), ['visible' => true, 'campagnes' => ['campaign']])->assertSessionHasNoErrors();
        $this->assertSame('Campagne test · 2026-2027', $this->page(route('etudiant.resultats'))['props']['campagne']);
        $consulter('M-student')->assertOk();
        $consulter('M-other')->assertSessionHasErrors('numero');

        // Les campagnes cochées remplacent les précédentes.
        $this->put(route(self::ROUTE.'visibilite'), ['visible' => true, 'campagnes' => ['recente']])->assertSessionHasNoErrors();
        $consulter('M-other')->assertOk();
        $consulter('M-student')->assertSessionHasErrors('numero');

        $this->put(route(self::ROUTE.'visibilite'), ['visible' => true, 'campagnes' => ['campaign', 'recente']])->assertSessionHasNoErrors();
        $this->assertSame('Récente, Campagne test · 2026-2027', $this->page(route('etudiant.resultats'))['props']['campagne']);

        // Campagne supprimée : plus consultable, et ne peut plus être cochée.
        DB::table('campagne')->where('id_campagne', 'recente')->update(['deleted_at' => now()]);
        $this->assertSame('Campagne test · 2026-2027', $this->page(route('etudiant.resultats'))['props']['campagne']);
        $this->put(route(self::ROUTE.'visibilite'), ['visible' => true, 'campagnes' => ['recente']])->assertSessionHasErrors('campagnes.0');
    }

    public function test_sans_resultat_publie(): void
    {
        $this->agent(self::DROITS);
        DB::table('resultats_admission')->delete();
        $props = $this->liste();
        $this->assertSame([[], [], ''], [$props['campagnes'], $props['resultats']['data'], $props['filters']['campagne']]);

        // Tables absentes (base pas encore mise à jour).
        Schema::drop('resultats_admission');
        Schema::drop('resultats_visibilite');
        $this->assertSame(['validees' => 0, 'attente' => 0, 'non_recevables' => 0], $this->liste()['compteurs']);
        $this->put(route(self::ROUTE.'visibilite'), ['visible' => false])->assertSessionHasErrors('visible');
    }

    /** « other » : file d'attente publiée, approuvée en commission. */
    private function approuverFileAttente(): void
    {
        foreach (['2026_11_20_000001_add_approbation_commission_to_demande.php', '2026_11_20_000002_add_attente_commission_to_demande.php', '2026_11_20_000003_add_liste_approbation_commission_to_demande.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        DB::table('demande')->where('etudiant_id', 'other')->update(['date_approbation_commission' => now(), 'liste_approbation_commission' => 'attente']);
        DB::table('etudiants')->where('id_etudiant', 'other')->update(['statut_inscription' => 'en_attente']);
    }

    public function test_une_demande_de_la_file_d_attente_passe_dans_admis_a_loger(): void
    {
        $this->agent(self::DROITS);
        $this->approuverFileAttente();
        // Possible après la clôture de la campagne.
        DB::table('campagne')->update(['statut' => 'terminer']);
        $url = $this->liste(['statut' => 'attente'])['resultats']['data'][0]['url_admettre'];
        $this->assertSame(route(self::ROUTE.'admettre', 'other'), $url);

        $this->from(route(self::ROUTE.'index', ['statut' => 'attente']))->post($url)
            ->assertRedirect(route(self::ROUTE.'index', ['statut' => 'attente']))->assertSessionHasNoErrors()->assertSessionHas('success');

        $demande = DB::table('demande')->where('etudiant_id', 'other')->first();
        $this->assertSame('confirme', $demande->etat_dossier);
        $this->assertSame('en_verification', DB::table('etudiants')->where('id_etudiant', 'other')->value('statut_inscription'));
        $this->assertTrue(app(\App\Http\Services\Eligibilite\ListeDefinitivePubliee::class)->figure('other'));
        $this->assertDatabaseHas('dossier_transitions', ['demande_id' => $demande->id_demande, 'ancien_etat' => 'eligible', 'nouvel_etat' => 'confirme', 'acteur_type' => 'admin', 'acteur_id' => 'agent-res']);
        $this->assertSame(['validees' => 2, 'attente' => 0, 'non_recevables' => 1], $this->liste()['compteurs']);
        $this->assertSame([null, null], array_column($this->liste()['resultats']['data'], 'url_admettre'));

        // Déjà admise.
        $this->post($url)->assertSessionHasErrors(['admission' => 'Cette demande n’est plus dans la file d’attente : actualisez la page.']);
        $this->post(route(self::ROUTE.'admettre', 'inconnu'))->assertNotFound();
    }

    public function test_admission_a_loger_refusee(): void
    {
        $this->agent(self::DROITS);
        $this->approuverFileAttente();
        $refus = function (): void {
            $this->assertNull($this->liste(['statut' => 'attente'])['resultats']['data'][0]['url_admettre']);
            $this->post(route(self::ROUTE.'admettre', 'other'))->assertSessionHasErrors('admission');
        };
        // Hors file d'attente.
        $this->post(route(self::ROUTE.'admettre', 'troisieme'))->assertSessionHasErrors('admission');
        // Demande pas encore recevable, ou pas approuvée en commission.
        DB::table('demande')->where('etudiant_id', 'other')->update(['etat_dossier' => 'soumis']);
        $refus();
        DB::table('demande')->where('etudiant_id', 'other')->update(['etat_dossier' => 'eligible', 'date_approbation_commission' => null]);
        $refus();
        // Campagne supprimée.
        DB::table('demande')->where('etudiant_id', 'other')->update(['date_approbation_commission' => now()]);
        DB::table('campagne')->update(['deleted_at' => now()]);
        $refus();
        DB::table('campagne')->update(['deleted_at' => null]);

        // Sanction bloquante : refusée par le workflow, le résultat ne change pas.
        DB::table('sanctions')->insert(['id_sanction' => 's1', 'numero_sanction' => 'S-1', 'date_decision' => today()->toDateString(), 'scope_type' => 'etudiant', 'etudiant_id' => 'other', 'type' => 'expulsion', 'statut' => 'active', 'motif' => 'Test']);
        $this->post(route(self::ROUTE.'admettre', 'other'))->assertSessionHasErrors();
        $this->assertDatabaseHas('resultats_admission', ['id' => 'other', 'resultat' => 'recevable_non_admis']);

        // Sans le droit « Valider une demande », ou sans « Consulter ».
        DB::table('sanctions')->delete();
        $this->agent(['hebergement.resultats.view', 'hebergement.resultats.publier', 'hebergement.residents.update', 'admissions.manage']);
        $this->assertNull($this->liste(['statut' => 'attente'])['resultats']['data'][0]['url_admettre']);
        $this->post(route(self::ROUTE.'admettre', 'other'))->assertForbidden();
        $this->agent(['hebergement.resultats.admettre']);
        $this->post(route(self::ROUTE.'admettre', 'other'))->assertForbidden();
        $this->assertSame('eligible', DB::table('demande')->where('etudiant_id', 'other')->value('etat_dossier'));
    }

    public function test_export_de_l_onglet(): void
    {
        $this->agent(self::DROITS);
        DB::table('demande')->where('etudiant_id', 'student')->update(['num_table_bac' => '2501']);
        $exports = $this->liste()['exports'];
        $excel = $this->get($exports['excel'])->assertOk();
        $this->assertStringContainsString('resultats-validees-campagne-test-2026-2027.xlsx', (string) $excel->headers->get('content-disposition'));
        $zip = new \ZipArchive();
        $zip->open($excel->baseResponse->getFile()->getPathname());
        $contenu = $zip->getFromName('xl/worksheets/sheet1.xml').$zip->getFromName('xl/sharedStrings.xml');
        $zip->close();
        foreach (['KONÉ Awa', 'M-student', '2501', 'Admis à loger'] as $attendu) {
            $this->assertStringContainsString($attendu, $contenu);
        }
        $this->assertStringNotContainsString('YAO Jean', $contenu);
        $this->assertStringContainsString('application/pdf', (string) $this->get($exports['pdf'])->assertOk()->headers->get('content-type'));

        // Filtres de l'écran repris ; onglet vide : pas d'export.
        $this->assertStringContainsString('type=readmission', $this->liste(['statut' => 'attente', 'type' => 'readmission'])['exports']['excel']);
        $this->assertNull($this->liste(['type' => 'readmission'])['exports']);
        $this->get(route(self::ROUTE.'export', ['statut' => 'autre', 'format' => 'excel']))->assertNotFound();

        DB::table('resultats_admission')->delete();
        $this->get(route(self::ROUTE.'export', ['statut' => 'validees', 'format' => 'excel']))->assertRedirect(route(self::ROUTE.'index'))->assertSessionHas('error');
    }

    public function test_migration_des_droits_idempotente(): void
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
        foreach (['2026_08_11_000001_create_role_permissions_tables.php', '2026_08_11_000002_create_modules_table.php', '2026_11_25_000004_add_resultats_permissions.php', '2026_11_25_000004_add_resultats_permissions.php', '2026_11_25_000005_rename_resultats_module.php', '2026_11_25_000005_rename_resultats_module.php', '2026_11_25_000007_add_resultats_admettre_permission.php', '2026_11_25_000007_add_resultats_admettre_permission.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }

        $this->assertSame(self::DROITS, DB::table('permissions')->where('slug', 'like', 'hebergement.resultats.%')->orderBy('id')->pluck('slug')->all());
        $this->assertSame(['view', 'publier', 'export', 'admettre'], json_decode(DB::table('modules')->where('slug', 'hebergement.resultats')->value('actions'), true));
        $this->assertSame('Résultats d’admission', DB::table('modules')->where('slug', 'hebergement.resultats')->value('name'));
        $this->assertSame(['Résultats d’admission'], DB::table('permissions')->where('slug', 'like', 'hebergement.resultats.%')->distinct()->pluck('module')->all());
        $this->assertSame([1, 1, 1, 1], DB::table('role_permission')->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
            ->where('slug', 'like', 'hebergement.resultats.%')->pluck('role_id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_droits_du_module(): void
    {
        $access = app(AdminAccess::class);
        foreach (['view' => [true, false, false], 'publier' => [false, true, false], 'export' => [false, false, true]] as $droit => $attendu) {
            $this->agent(['hebergement.resultats.'.$droit]);
            $this->assertSame($attendu, [$access->allows(auth('admin')->user(), self::ROUTE.'index'), $access->allows(auth('admin')->user(), self::ROUTE.'visibilite'), $access->allows(auth('admin')->user(), self::ROUTE.'export')]);
        }

        // Publier et exporter exigent le droit de consulter.
        $this->agent(['hebergement.resultats.publier', 'hebergement.resultats.export']);
        $this->put(route(self::ROUTE.'visibilite'), ['visible' => true, 'campagnes' => ['campaign']])->assertForbidden();
        $this->get(route(self::ROUTE.'export', ['statut' => 'validees', 'format' => 'excel']))->assertForbidden();

        // Consultation seule : ni bouton de visibilité, ni export, ni fiche de la demande.
        $this->agent(['hebergement.resultats.view']);
        $props = $this->liste();
        $this->assertNull($props['visibilite']['url']);
        $this->assertNull($props['exports']);
        $this->get(route(self::ROUTE.'export', ['statut' => 'validees', 'format' => 'pdf']))->assertForbidden();
        $this->assertNull($props['resultats']['data'][0]['url_dossier']);
        $this->put(route(self::ROUTE.'visibilite'), ['visible' => false])->assertForbidden();
        $this->assertDatabaseCount('resultats_visibilite', 0);

        $this->agent([...self::DROITS, 'hebergement.residents.view']);
        $this->assertSame(route('admin.hebergement.campagnes_admission.show', ['id' => 'student', 'origine' => 'resultats']), $this->liste()['resultats']['data'][0]['url_dossier']);
    }

    public function test_menu_apres_campagnes_et_admission(): void
    {
        $libelles = array_column(collect(config('admin_menu'))->firstWhere('route', 'admin.hebergement.index')['children'], 'label');
        $this->assertSame('Résultats d’admission', $libelles[array_search('Campagnes & admission', $libelles, true) + 1]);
    }
}
