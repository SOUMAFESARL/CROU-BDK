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

    public function test_donnee_manquante_laisse_le_dossier_soumis(): void
    {
        DB::table('campaign_scorings')->update(['weights' => json_encode(['social' => 0, 'academique' => 50, 'geographique' => 50, 'specifique' => 0])]);
        DB::table('etudiants')->update(['distance' => null]);
        $this->assertSame('soumis', $this->depot(15)->etat_dossier);
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
        $this->workflow->admin($dossier->getKey(), 'eligible');
        $dossier->refresh();
        $this->assertSame('eligible', $dossier->etat_dossier);
        $this->assertSame(DecisionAutomatiqueDossier::MOTIF_LEVEE, $dossier->transitions->last()->motif);
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
        $resultat = app(DecisionAutomatiqueDossier::class)->rendreEligibles();
        $this->assertSame(2, $resultat['eligibles']);
        $this->assertSame('eligible', $premier->fresh()->etat_dossier);
        $this->assertSame('eligible', $second->fresh()->etat_dossier);
        $this->assertSame([], app(DecisionAutomatiqueDossier::class)->refusAutomatiquesEnCours());
    }
}
