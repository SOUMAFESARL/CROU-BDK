<?php

namespace Tests\Feature;

use App\Models\Etudiant;
use App\Services\EtudiantDemandes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Props de la page React « Mes demandes » (Etudiant/MesDemandes). */
class StudentRequestsPageTest extends \Tests\Support\DossierTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('document_etudiant', function (Blueprint $t) {
            $t->string('id_document')->primary(); $t->string('etudiant_id'); $t->string('statut')->nullable(); $t->softDeletes(); $t->timestamps();
        });
        Schema::create('departure_requests', function (Blueprint $t) {
            $t->string('id_demande_sortie')->primary(); $t->string('etudiant_id'); $t->string('assignation_id')->nullable();
            $t->string('chambre_id')->nullable(); $t->string('statut'); $t->string('decision')->nullable(); $t->string('motif')->nullable();
            $t->string('motif_decision')->nullable(); $t->date('date_demande')->nullable(); $t->date('date_sortie_prevue')->nullable();
            $t->timestamp('date_decision')->nullable(); $t->timestamps();
        });
        Schema::create('student_contract_approvals', function (Blueprint $t) {
            $t->string('id')->primary(); $t->string('assignment_id'); $t->string('student_id'); $t->string('student_name')->nullable();
            $t->longText('document_html')->nullable(); $t->string('document_hash')->nullable(); $t->text('consent_text')->nullable();
            $t->timestamp('approved_at')->nullable(); $t->timestamps();
        });
        Schema::create('housing_entry_appointments', function (Blueprint $t) {
            $t->id(); $t->string('assignment_id'); $t->string('student_id'); $t->dateTime('starts_at'); $t->dateTime('ends_at');
            $t->string('location'); $t->string('status')->default('scheduled'); $t->timestamps();
        });
        Schema::table('campagne', function (Blueprint $t) {
            $t->string('annee_campagne')->nullable(); $t->date('date_echeance_demande')->nullable();
        });
        DB::table('campagne')->update(['annee_campagne' => '2026-2027']);
    }

    private function props(): array
    {
        return app(EtudiantDemandes::class)->props(Etudiant::findOrFail('student'));
    }

    public function test_student_without_request_gets_empty_state_and_no_departure_form(): void
    {
        $props = $this->props();

        $this->assertNull($props['dossier']);
        $this->assertSame([], $props['autres']);
        $this->assertNull($props['sortie']['formulaire']);
        $this->assertSame(route('etudiants.readmission'), $props['liens']['readmission']);
    }

    public function test_submitted_dossier_can_only_be_cancelled_with_a_reason(): void
    {
        $this->dossier();

        $dossier = $this->props()['dossier'];

        $this->assertSame('soumis', $dossier['etat']['code']);
        $this->assertSame([['etat' => 'annule', 'libelle' => 'Annuler mon dossier', 'motifObligatoire' => true]], $dossier['actions']);
        $this->assertSame('en_cours', $dossier['etapes'][0]['statut']);
        $this->assertNull($dossier['decision']);
    }

    public function test_eligible_dossier_asks_the_student_to_confirm_and_keeps_history(): void
    {
        $dossier = $this->eligibleRequest();

        $props = $this->props()['dossier'];

        $this->assertSame('eligible', $props['etat']['code']);
        $this->assertSame([], $props['actions']);
        $this->assertSame('consentement', $props['decision']['type']);
        $this->assertNull($props['decision']['assignationId']);
        $this->assertSame(route('etudiants.validation-demande', ['idDemande' => $dossier->getKey()]), $props['decision']['url']);
        $this->assertNull($props['proposition']);
        $this->assertSame(['fait', 'fait', 'en_cours', 'a_venir', 'a_venir'], array_column($props['etapes'], 'statut'));
        $this->assertSame('Critères vérifiés.', collect($props['historique'])->firstWhere('etat', 'Éligible')['motif']);
    }

    public function test_pre_allocated_dossier_of_the_former_journey_exposes_proposal_answer(): void
    {
        $dossier = $this->eligibleRequest();
        $assignment = $this->reserver();

        $props = $this->props()['dossier'];

        $this->assertSame('pre_attribue', $props['etat']['code']);
        $this->assertSame('proposition', $props['decision']['type']);
        $this->assertSame((string) $assignment->getKey(), $props['decision']['assignationId']);
        $this->assertSame(route('etudiants.validation-demande', ['idDemande' => $dossier->getKey()]), $props['decision']['url']);
        $this->assertStringContainsString('Chambre room', $props['proposition']['chambre']);
    }

    public function test_confirmed_then_allocated_dossier_is_definitive(): void
    {
        $this->eligibleRequest();
        $this->allocate();

        $props = $this->props()['dossier'];

        $this->assertSame('attribue_definitif', $props['etat']['code']);
        $this->assertNull($props['decision']);
        $this->assertStringContainsString('Chambre room', $props['proposition']['chambre']);
        $this->assertSame(['fait', 'fait', 'fait', 'fait', 'fait'], array_column($props['etapes'], 'statut'));
    }

    public function test_definitive_dossier_message_follows_contract_approval(): void
    {
        $this->eligibleRequest();
        $assignment = $this->allocate();
        $this->assertStringContainsString('Approuvez votre contrat', $this->props()['dossier']['message']);

        DB::table('student_contract_approvals')->insert(['id' => 'contract', 'assignment_id' => $assignment->getKey(),
            'student_id' => 'student', 'document_hash' => 'h', 'approved_at' => now()]);
        $message = $this->props()['dossier']['message'];
        $this->assertStringContainsString('Votre contrat est approuvé', $message);
        $this->assertStringNotContainsString('Approuvez votre contrat', $message);

        DB::table('housing_entry_appointments')->insert(['assignment_id' => $assignment->getKey(), 'student_id' => 'student',
            'starts_at' => '2026-11-03 09:00:00', 'ends_at' => '2026-11-03 09:30:00', 'location' => 'Accueil cité', 'status' => 'scheduled']);
        $this->assertStringContainsString('03/11/2026 à 09:00 (Accueil cité)', $this->props()['dossier']['message']);
    }

    public function test_departure_form_needs_an_active_room_and_no_pending_request(): void
    {
        DB::table('assignation_chambres')->insert(['id_assignation' => 'stay', 'etudiant_id' => 'student', 'chambre_id' => 'room',
            'campagne_id' => 'campaign', 'statut' => 'confirmee', 'date_assignation' => now()->subMonth()]);

        $formulaire = $this->props()['sortie']['formulaire'];
        $this->assertSame('room', $formulaire['chambre']);
        $this->assertSame(today()->addDays(15)->toDateString(), $formulaire['dateMin']);
        $this->assertSame(route('etudiants.departure-requests.store'), $formulaire['url']);

        DB::table('departure_requests')->insert([
            ['id_demande_sortie' => 'pending', 'etudiant_id' => 'student', 'chambre_id' => 'room', 'statut' => 'en_attente', 'decision' => 'approuvee',
                'motif' => 'stage', 'date_demande' => today(), 'date_sortie_prevue' => today()->addMonth(), 'motif_decision' => null],
            ['id_demande_sortie' => 'old', 'etudiant_id' => 'student', 'chambre_id' => 'room', 'statut' => 'annulee', 'decision' => 'refusee',
                'motif' => 'autre', 'date_demande' => today()->subYear(), 'date_sortie_prevue' => today()->subYear(), 'motif_decision' => 'Préavis insuffisant'],
            ['id_demande_sortie' => 'foreign', 'etudiant_id' => 'other', 'chambre_id' => 'room', 'statut' => 'en_attente', 'decision' => null,
                'motif' => 'stage', 'date_demande' => today(), 'date_sortie_prevue' => today(), 'motif_decision' => null],
        ]);

        $sortie = $this->props()['sortie'];
        $this->assertNull($sortie['formulaire']);
        $this->assertSame('pending', $sortie['enCours']['id']);
        $this->assertTrue($sortie['enCours']['approuvee']);
        $this->assertSame('Stage', $sortie['enCours']['motif']);
        $this->assertSame([['libelle' => 'Refusée', 'ton' => 'danger']], array_column($sortie['historique'], 'statut'));
        $this->assertSame('Préavis insuffisant', $sortie['historique'][0]['motifDecision']);
    }
}
