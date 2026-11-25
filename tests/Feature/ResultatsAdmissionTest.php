<?php

namespace Tests\Feature;

use App\Http\Services\Eligibilite\RecevabiliteDemandes;
use App\Http\Services\Eligibilite\ResultatsAdmission;
use App\Models\Campagne;
use App\Models\Etudiant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Résultats d'admission : figés à la publication, passage dans « Admis à loger », consultation publique. */
class ResultatsAdmissionTest extends \Tests\Support\DossierTestCase
{
    use \Tests\Support\EditionEtudiantTables;

    private const CLASSES = ['recevables' => [], 'rejetees' => [], 'refusees' => [], 'validees' => [], 'en_attente' => [], 'definitives' => [], 'hors_liste' => []];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Schema::table('etudiants', function (Blueprint $t) {
            $t->string('matricule')->nullable();
            $t->date('date_inscription')->nullable();
        });
        $this->creerTablesEditionEtudiant();
        Schema::table('campagne', function (Blueprint $t) {
            foreach (['libelle', 'annee_campagne', 'periode_debut', 'periode_fin'] as $colonne) {
                $t->string($colonne)->nullable();
            }
            $t->boolean('admission')->default(true);
            $t->boolean('readmission')->default(false);
        });
        Schema::table('demande', function (Blueprint $t) {
            if (! Schema::hasColumn('demande', 'num_table_bac')) {
                $t->string('num_table_bac')->nullable();
            }
        });
        foreach (['2026_11_20_000001_add_approbation_commission_to_demande.php', '2026_11_20_000002_add_attente_commission_to_demande.php',
            '2026_11_20_000003_add_liste_approbation_commission_to_demande.php', '2026_11_20_000004_create_publication_liste_definitive_table.php',
            '2026_11_24_000005_create_reclamations_tables.php', '2026_11_25_000002_create_resultats_admission_table.php', '2026_11_25_000003_create_resultats_visibilite_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        DB::table('campagne')->update(['libelle' => 'Campagne test', 'annee_campagne' => '2026-2027']);
        // Campagne cochée dans le menu « Résultats d'admission ».
        app(ResultatsAdmission::class)->definirCampagnesVisibles(['campaign'], null);
        foreach (['student' => 'KONÉ Awa', 'other' => 'YAO Jean', 'troisieme' => 'BAMBA Ali', 'quatrieme' => 'DIALLO Issa'] as $id => $nom) {
            DB::table('etudiants')->updateOrInsert(['id_etudiant' => $id], ['name' => $nom, 'genre_id' => 1, 'statut_inscription' => 'demande']);
            $this->dossier($id);
        }
        DB::table('etudiants')->where('id_etudiant', 'student')->update(['matricule' => 'M-001']);
        DB::table('demande')->where('etudiant_id', 'other')->update(['num_table_bac' => '2501 234567']);
        DB::table('etudiants')->where('id_etudiant', 'troisieme')->update(['matricule' => 'M-003']);
        DB::table('etudiants')->where('id_etudiant', 'quatrieme')->update(['matricule' => 'M-004']);
    }

    private function etat(string $etudiant): string
    {
        return DB::table('demande')->where('etudiant_id', $etudiant)->value('etat_dossier');
    }

    private function approuver(string $etudiant, string $liste = 'validees'): void
    {
        $this->workflow->admin($this->workflow->current($etudiant, 'campaign')->getKey(), 'eligible');
        DB::table('demande')->where('etudiant_id', $etudiant)->update(['date_approbation_commission' => now(), 'liste_approbation_commission' => $liste]);
    }

    /** Publication du volet admission : « student » admis, « other » en file d'attente, « troisieme » irrecevable, « quatrieme » validé mais pas approuvé. */
    private function publier(array $classement = []): array
    {
        $this->approuver('student');
        $this->approuver('other', 'attente');
        $this->enregistrerPublication();
        $population = Etudiant::with('derniereDemande.campagne')->get();

        return app(ResultatsAdmission::class)->publier(Campagne::findOrFail('campaign'), ['admission'], $population, $classement
            + ['definitives' => ['student', 'other'], 'rejetees' => ['troisieme'], 'validees' => ['quatrieme']] + self::CLASSES);
    }

    private function enregistrerPublication(): void
    {
        foreach (['validees', 'attente'] as $liste) {
            DB::table('publication_liste_definitive')->insert(['id' => $liste, 'campagne_id' => 'campaign', 'liste' => $liste, 'volet' => 'admission', 'date_publication' => now()]);
        }
    }

    private function consulter(string $numero)
    {
        return $this->from(route('etudiant.resultats'))->post(route('etudiant.resultats.show'), ['numero' => $numero]);
    }

    private function props($reponse): array
    {
        preg_match('/data-page="([^"]+)"/', $reponse->assertOk()->getContent(), $matches);
        $page = json_decode(html_entity_decode($matches[1] ?? ''), true);
        $this->assertSame('Etudiant/Auth/Resultat', $page['component']);

        return $page['props'];
    }

    public function test_la_publication_fige_les_resultats_et_admet_a_loger(): void
    {
        $this->assertSame(['admis' => 1, 'bloques' => []], $this->publier());

        $this->assertSame(['other' => 'recevable_non_admis', 'student' => 'admis', 'troisieme' => 'non_recevable'],
            DB::table('resultats_admission')->orderBy('etudiant_id')->pluck('resultat', 'etudiant_id')->all());
        // Seule la demande validée publiée passe dans « Admis à loger », par le système.
        $this->assertSame(['confirme', 'eligible'], [$this->etat('student'), $this->etat('other')]);
        $this->assertSame('en_verification', DB::table('etudiants')->where('id_etudiant', 'student')->value('statut_inscription'));
        $this->assertDatabaseHas('dossier_transitions', ['nouvel_etat' => 'confirme', 'acteur_type' => 'systeme']);

        // Rejouer la publication ne change rien.
        $this->assertSame(0, app(ResultatsAdmission::class)->publier(Campagne::findOrFail('campaign'), ['admission'],
            Etudiant::with('derniereDemande.campagne')->get(), ['definitives' => ['student', 'other'], 'rejetees' => ['troisieme']] + self::CLASSES)['admis']);
    }

    public function test_une_nouvelle_publication_garde_l_admission_depuis_la_file_d_attente(): void
    {
        $this->publier();
        DB::table('demande')->where('etudiant_id', 'other')->update(['etat_dossier' => 'confirme']);
        app(ResultatsAdmission::class)->publier(Campagne::findOrFail('campaign'), ['admission'],
            Etudiant::with('derniereDemande.campagne')->get(), ['definitives' => ['student', 'other']] + self::CLASSES);

        $this->assertDatabaseHas('resultats_admission', ['etudiant_id' => 'other', 'resultat' => 'admis']);
    }

    public function test_un_volet_non_publie_et_une_approbation_posterieure_ne_donnent_pas_d_admission(): void
    {
        $this->enregistrerPublication();
        $this->travel(5)->minutes();
        $this->approuver('student');
        $population = Etudiant::with('derniereDemande.campagne')->get();
        $classement = ['definitives' => ['student']] + self::CLASSES;

        // Approuvée après la publication : pas encore de résultat pour cette demande.
        $service = app(ResultatsAdmission::class);
        $this->assertSame(0, $service->publier(Campagne::findOrFail('campaign'), ['admission'], $population, $classement)['admis']);
        $this->assertSame('eligible', $this->etat('student'));
        $this->assertDatabaseMissing('resultats_admission', ['etudiant_id' => 'student']);

        DB::table('resultats_admission')->delete();
        $service->publier(Campagne::findOrFail('campaign'), ['readmission'], $population, $classement);
        $this->assertDatabaseCount('resultats_admission', 0);
    }

    public function test_une_sanction_bloquante_est_signalee_sans_bloquer_la_publication(): void
    {
        DB::table('sanctions')->insert(['id_sanction' => 's1', 'numero_sanction' => 'S-1', 'date_decision' => today()->toDateString(), 'scope_type' => 'etudiant', 'etudiant_id' => 'student', 'type' => 'expulsion', 'statut' => 'active', 'motif' => 'Test']);
        $this->approuverSansControle('student');
        $this->approuver('other', 'attente');
        $this->enregistrerPublication();
        $bilan = app(ResultatsAdmission::class)->publier(Campagne::findOrFail('campaign'), ['admission'],
            Etudiant::with('derniereDemande.campagne')->get(), ['definitives' => ['student', 'other']] + self::CLASSES);

        $this->assertSame(0, $bilan['admis']);
        $this->assertStringStartsWith('KONÉ Awa : ', $bilan['bloques'][0]);
        $this->assertSame('eligible', $this->etat('student'));
    }

    private function approuverSansControle(string $etudiant): void
    {
        DB::table('demande')->where('etudiant_id', $etudiant)->update(['etat_dossier' => 'eligible', 'date_approbation_commission' => now(), 'liste_approbation_commission' => 'validees']);
    }

    public function test_consultation_des_trois_types_de_resultat(): void
    {
        $this->publier();

        // Matricule ou numéro de table : casse, espaces et ponctuation indifférents.
        $admis = $this->props($this->consulter(' m001 '))['resultat'];
        $this->assertSame(['admis', 'KONÉ Awa', 'Campagne test · 2026-2027', 'Admission'], [$admis['type'], $admis['nom'], $admis['campagne'], $admis['volet']]);
        $this->assertStringContainsString('comptabilité', $admis['message']);
        $this->assertSame('recevable_non_admis', $this->props($this->consulter('2501234567'))['resultat']['type']);
        $this->assertSame('non_recevable', $this->props($this->consulter('M-003'))['resultat']['type']);

        // Demande validée mais pas publiée, numéro inconnu ou vide.
        $this->consulter('M-004')->assertSessionHasErrors(['numero' => 'Le résultat de votre demande n’est pas encore publié. Revenez après la publication de la liste définitive.']);
        $this->consulter('X-999')->assertSessionHasErrors('numero');
        $this->consulter(' - ')->assertSessionHasErrors('numero');
        $this->consulter('')->assertSessionHasErrors('numero');

        // Chambre attribuée : plus de paiement demandé. Admission remise en cause : pas de message d'admission.
        DB::table('demande')->where('etudiant_id', 'student')->update(['etat_dossier' => 'attribue_definitif']);
        $this->assertStringContainsString('chambre vous a été attribuée', $this->props($this->consulter('M-001'))['resultat']['message']);
        DB::table('demande')->where('etudiant_id', 'student')->update(['etat_dossier' => 'refuse']);
        $this->consulter('M-001')->assertSessionHasErrors('numero');
    }

    public function test_numero_partage_par_deux_etudiants(): void
    {
        $this->publier();
        DB::table('demande')->where('etudiant_id', 'troisieme')->update(['num_table_bac' => '2501-234567']);
        $this->consulter('2501234567')->assertSessionHasErrors('numero');

        // L'autre étudiant n'a pas de demande dans la campagne publiée : celui de la campagne est retenu.
        DB::table('campagne')->insert(['id_campagne' => 'ancienne', 'statut' => 'terminer', 'created_at' => now()->subYear()]);
        DB::table('demande')->where('etudiant_id', 'troisieme')->update(['campagne_id' => 'ancienne']);
        $this->assertSame('YAO Jean', $this->props($this->consulter('2501234567'))['resultat']['nom']);
    }

    public function test_lien_de_la_page_de_connexion_et_reclamation(): void
    {
        $connexion = function (): array {
            preg_match('/data-page="([^"]+)"/', $this->get('/login')->assertOk()->getContent(), $matches);

            return json_decode(html_entity_decode($matches[1] ?? ''), true)['props'];
        };
        $this->assertNull($connexion()['resultatsUrl']);
        $this->assertNull($this->props($this->get(route('etudiant.resultats')))['campagne']);

        $this->publier();
        $this->assertSame(route('etudiant.resultats'), $connexion()['resultatsUrl']);
        $this->assertSame('Campagne test · 2026-2027', $this->props($this->get(route('etudiant.resultats')))['campagne']);

        // Réclamation proposée aux non admis pendant une période ouverte.
        $this->assertNull($this->props($this->consulter('M-003'))['liens']['reclamation']);
        \App\Models\ReclamationPeriode::create(['campagne_id' => 'campaign', 'date_debut' => today()->toDateString(), 'date_fin' => today()->addDays(3)->toDateString()]);
        $this->assertSame(route('etudiant.reclamation'), $this->props($this->consulter('M-003'))['liens']['reclamation']);
        $this->assertNull($this->props($this->consulter('M-001'))['liens']['reclamation']);
    }

    public function test_commande_pour_les_publications_anterieures(): void
    {
        $this->approuver('student');
        $this->enregistrerPublication();
        $this->mock(RecevabiliteDemandes::class)->shouldReceive('classer')->once()->andReturn(['definitives' => ['student'], 'rejetees' => ['troisieme']] + self::CLASSES);

        $this->artisan('admissions:appliquer-publications')->assertSuccessful();

        $this->assertSame('confirme', $this->etat('student'));
        $this->assertDatabaseHas('resultats_admission', ['etudiant_id' => 'student', 'resultat' => 'admis']);
    }
}
