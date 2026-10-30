<?php

namespace Tests\Feature;

use App\Http\Services\DecisionAutomatiqueDossier;
use App\Models\DemandeEtudiant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Auth,DB,Mail,Schema};
use Illuminate\Validation\ValidationException;

class DecisionAutomatiqueDossierTest extends \Tests\Support\DossierTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        (require database_path('migrations/2026_09_23_000003_create_campaign_scorings.php'))->up();
        (require database_path('migrations/2026_10_26_000001_add_distance_thresholds_to_campaign_scorings.php'))->up();
        (require database_path('migrations/2026_10_27_000001_add_criteria_to_campaign_scorings.php'))->up();
        (require database_path('migrations/2026_10_27_000002_add_validation_auto_to_campagne.php'))->up();
        Schema::table('demande', function (Blueprint $t) { $t->decimal('moyenne', 5, 2)->nullable(); $t->boolean('boursier')->nullable(); });
        Schema::table('etudiants', function (Blueprint $t) { $t->decimal('distance', 8, 2)->nullable(); $t->boolean('boursier')->nullable(); });
        DB::table('etudiants')->update(['distance' => 300, 'boursier' => true]);
        // Barème académique seul : la moyenne détermine le score.
        DB::table('campaign_scorings')->insert([
            'campagne_id' => 'campaign', 'minimum_score' => 50, 'pmr_first' => false, 'active' => true, 'created_by' => 'agent',
            'weights' => json_encode(['social' => 0, 'academique' => 100, 'geographique' => 0, 'specifique' => 0]),
            'points' => json_encode(['academique' => ['tb' => 100, 'bien' => 80, 'ab' => 60, 'passable' => 40, 'insuffisant' => 10]]),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** Dépôt par l'étudiant (aucun admin connecté). */
    private function depot(float $moyenne, string $student = 'student'): DemandeEtudiant
    {
        Auth::guard('admin')->logout();
        $dossier = new DemandeEtudiant(['etudiant_id' => $student, 'campagne_id' => 'campaign', 'statut_demande' => 'En_attente', 'moyenne' => $moyenne]);
        $dossier->save();
        return $dossier->fresh();
    }

    private function admin(): void
    {
        $actor = \Mockery::mock(\App\Models\User::class)->makePartial();
        $actor->fill(['id_user' => 'agent', 'name' => 'Agent']);
        $actor->shouldReceive('hasPermission')->andReturn(true);
        $this->actingAs($actor, 'admin');
    }

    public function test_score_au_dessus_du_minimum_rend_le_dossier_eligible(): void
    {
        $dossier = $this->depot(15);
        $this->assertSame('eligible', $dossier->etat_dossier);
        $transition = $dossier->transitions->last();
        $this->assertSame('systeme', $transition->acteur_type);
        $this->assertTrue($transition->details[DecisionAutomatiqueDossier::MARQUEUR]);
        $this->assertEquals(80, $transition->details['score']);
    }

    public function test_score_egal_au_minimum_rend_le_dossier_eligible(): void
    {
        DB::table('campaign_scorings')->update(['minimum_score' => 60]);
        $this->assertSame('eligible', $this->depot(12)->etat_dossier);
    }

    public function test_score_sous_le_minimum_refuse_le_dossier(): void
    {
        $dossier = $this->depot(11);
        $this->assertSame('refuse', $dossier->etat_dossier);
        $this->assertStringContainsString('40/100', $dossier->transitions->last()->motif);
        $this->assertSame('annuler', DB::table('etudiants')->where('id_etudiant', 'student')->value('statut_inscription'));
    }

    public function test_sans_bareme_actif_le_dossier_reste_soumis(): void
    {
        DB::table('campaign_scorings')->update(['active' => false]);
        $this->assertSame('soumis', $this->depot(5)->etat_dossier);
    }

    public function test_donnee_manquante_compte_zero_point(): void
    {
        DB::table('campaign_scorings')->update(['weights' => json_encode(['social' => 0, 'academique' => 50, 'geographique' => 50, 'specifique' => 0])]);
        DB::table('etudiants')->update(['distance' => null]);
        $dossier = $this->depot(15);
        // Moyenne 15 → 80 pts × 50 % = 40 ; distance non renseignée → 0 pt : 40 < 50.
        $this->assertSame('refuse', $dossier->etat_dossier);
        $this->assertEquals(40, $dossier->transitions->last()->details['score']);
        $this->assertEquals(0, $dossier->transitions->last()->details['subscores']['geographique']);
    }

    public function test_sanction_bloquante_laisse_le_dossier_soumis(): void
    {
        $this->evaluation = ['eligible' => false, 'regles' => [['label' => 'Absence de sanction disciplinaire bloquante', 'ok' => false]]];
        $this->assertSame('soumis', $this->depot(18)->etat_dossier);
    }

    public function test_dossier_saisi_par_un_admin_n_est_pas_decide_automatiquement(): void
    {
        $this->assertSame('soumis', $this->dossier()->etat_dossier);
    }

    public function test_depot_public_avec_une_session_admin_ouverte_est_decide_automatiquement(): void
    {
        // Admin connecté dans le même navigateur, mais dépôt fait hors de l'espace admin.
        $this->admin();
        $dossier = new DemandeEtudiant(['etudiant_id' => 'student', 'campagne_id' => 'campaign', 'statut_demande' => 'En_attente', 'moyenne' => 5]);
        $dossier->save();
        $this->assertSame('refuse', $dossier->fresh()->etat_dossier);
        $this->assertSame('etudiant', DB::table('dossier_transitions')->where('demande_id', $dossier->getKey())->whereNull('ancien_etat')->value('acteur_type'));
    }

    public function test_soumission_d_un_brouillon_declenche_la_decision(): void
    {
        $dossier = $this->dossier('student', true);
        DB::table('demande')->where('id_demande', $dossier->getKey())->update(['moyenne' => 9]);
        Auth::guard('admin')->logout();
        $resultat = $this->workflow->transition($dossier->getKey(), 'soumis', 'etudiant', 'student');
        $this->assertSame('refuse', $resultat->etat_dossier);
    }

    public function test_un_refus_automatique_peut_etre_rendu_eligible_en_un_clic(): void
    {
        $dossier = $this->depot(5);
        $this->admin();
        $this->workflow->admin($dossier->getKey(), 'eligible', 'Situation sociale particulière.');
        $dossier->refresh();
        $this->assertSame('eligible', $dossier->etat_dossier);
        $this->assertSame(DecisionAutomatiqueDossier::PREFIXE_LEVEE.'Situation sociale particulière.', $dossier->transitions->last()->motif);
    }

    public function test_la_levee_d_un_refus_automatique_exige_un_motif_de_derogation(): void
    {
        $dossier = $this->depot(5);
        $this->admin();
        try {
            $this->workflow->admin($dossier->getKey(), 'eligible', '  ');
            $this->fail('Le motif de la dérogation aurait dû être exigé.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('motif', $e->errors());
        }
        $this->assertSame('refuse', $dossier->fresh()->etat_dossier);
        $this->expectException(ValidationException::class);
        app(DecisionAutomatiqueDossier::class)->rendreEligibles('');
    }

    public function test_un_refus_prononce_par_un_agent_ne_peut_pas_etre_leve_directement(): void
    {
        $dossier = $this->dossier();
        $this->workflow->admin($dossier->getKey(), 'refuse', 'Dossier non conforme.');
        $this->expectException(ValidationException::class);
        $this->workflow->admin($dossier->getKey(), 'eligible', 'Erreur.');
    }

    public function test_levee_groupee_des_refus_automatiques(): void
    {
        $premier = $this->depot(5, 'student');
        $second = $this->depot(6, 'other');
        $this->admin();
        $resultat = app(DecisionAutomatiqueDossier::class)->rendreEligibles('Capacité d’accueil suffisante.');
        $this->assertSame(2, $resultat['eligibles']);
        $this->assertSame('eligible', $premier->fresh()->etat_dossier);
        $this->assertSame('eligible', $second->fresh()->etat_dossier);
        $this->assertSame([], app(DecisionAutomatiqueDossier::class)->refusAutomatiquesEnCours());
    }

    public function test_validation_differee_laisse_le_dossier_soumis_jusqu_a_la_date(): void
    {
        DB::table('campagne')->update(['date_validation_auto' => today()->addDays(3)->toDateString()]);
        $dossier = $this->depot(15);
        $this->assertSame('soumis', $dossier->etat_dossier);
        $this->assertSame(0, app(DecisionAutomatiqueDossier::class)->traiterValidationsDifferees());
        $this->assertSame('soumis', $dossier->fresh()->etat_dossier);
    }

    public function test_validation_differee_decide_les_dossiers_en_lot_a_la_date(): void
    {
        DB::table('campagne')->update(['date_validation_auto' => today()->addDays(3)->toDateString()]);
        $eligible = $this->depot(15, 'student');
        $refuse = $this->depot(5, 'other');
        $this->travel(3)->days();
        $this->assertSame(2, app(DecisionAutomatiqueDossier::class)->traiterValidationsDifferees());
        $this->assertSame('eligible', $eligible->fresh()->etat_dossier);
        $this->assertSame('refuse', $refuse->fresh()->etat_dossier);
        $this->assertNotNull(DB::table('campagne')->value('validation_auto_effectuee_at'));
        // Une seule passe par campagne.
        $this->assertSame(0, app(DecisionAutomatiqueDossier::class)->traiterValidationsDifferees());
    }

    public function test_validation_differee_ignore_les_dossiers_saisis_par_un_admin(): void
    {
        DB::table('campagne')->update(['date_validation_auto' => today()->addDay()->toDateString()]);
        $dossier = $this->dossier();
        $this->travel(1)->days();
        $this->artisan('dossiers:validation-auto')->assertSuccessful();
        $this->assertSame('soumis', $dossier->fresh()->etat_dossier);
    }

    public function test_apres_la_date_les_nouveaux_dossiers_sont_decides_des_reception(): void
    {
        DB::table('campagne')->update(['date_validation_auto' => today()->toDateString()]);
        $this->assertSame('eligible', $this->depot(15)->etat_dossier);
    }
}
