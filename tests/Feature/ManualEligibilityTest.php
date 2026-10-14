<?php

namespace Tests\Feature;

use App\Http\Services\CampagneService;
use App\Http\Services\CampagneValidationService;
use App\Http\Services\EtudiantService;
use App\Mail\Activation;
use App\Models\Etudiant;
use App\Repositories\Interfaces\EtudiantRepositoryInterface;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class ManualEligibilityTest extends \Tests\Support\DossierTestCase
{
    public function test_age_limit_is_checked_with_the_campaign_service_response(): void
    {
        $student = new Etudiant(['date_naissance' => now()->subYears(30)]);
        $student->setRelation('derniereDemande', (object) [
            'niveauEtudiantActuel' => (object) ['abreviation' => 'L1', 'name' => 'Licence 1'],
        ]);
        $student->setRelation('documents', collect([(object) ['statut' => 'valide']]));
        $campaign = new \App\Models\Campagne(['age_L1_max' => 25]);
        $result = (new \App\Repositories\EtudiantRepository())->verifierEligibilite($student, [
            'data' => ['campagnes' => collect([$campaign])],
        ]);
        $this->assertFalse($result['eligible']);
        $this->assertFalse($result['regles'][0]['ok']);
        $this->assertStringContainsString('Âge maximum', $result['regles'][0]['label']);
    }

    private function manual(array $data): array
    {
        return app(EtudiantService::class)->ValidateDemandeEtudiant($data,'student');
    }
    public function test_manual_approval_cannot_override_failed_rules_even_with_a_forged_score(): void
    {
        Mail::fake();
        $dossier=$this->dossier();
        $this->workflow->admin($dossier->getKey(),'en_etude');
        $this->evaluation=['eligible'=>false,'regles'=>[['label'=>'Distance','ok'=>false]]];
        $result=$this->manual(['decision'=>'eligible','score_calcule'=>100]);
        $this->assertSame('error',$result['bool']);
        $this->assertSame('en_etude',$dossier->fresh()->etat_dossier);
        Mail::assertNothingSent();
    }
    public function test_pending_decision_opens_review_without_validating_eligibility(): void
    {
        $dossier=$this->dossier();
        $this->evaluation=['eligible'=>false];
        $result=$this->manual(['decision'=>'en_attente']);
        $this->assertSame('success',$result['bool']);
        $this->assertSame('en_etude',$dossier->fresh()->etat_dossier);
        $this->assertSame(0,\App\Models\AssignationChambre::count());
    }
    public function test_missing_eligibility_result_blocks_approval(): void
    {
        $dossier=$this->dossier();
        $this->workflow->admin($dossier->getKey(),'en_etude');
        $this->evaluation=[];
        $this->assertSame('error',$this->manual(['decision'=>'eligible'])['bool']);
        $this->assertSame('en_etude',$dossier->fresh()->etat_dossier);
    }
    public function test_eligible_student_can_proceed_without_becoming_a_resident(): void
    {
        Mail::fake();
        $dossier=$this->dossier();
        $this->workflow->admin($dossier->getKey(),'en_etude');
        $this->assertSame('success',$this->manual(['decision'=>'eligible'])['bool']);
        $this->assertSame('eligible',$dossier->fresh()->etat_dossier);
        $this->assertSame('en_verification',Etudiant::findOrFail('student')->statut_inscription);
        Mail::assertNothingSent();
    }
    public function test_explicit_rejection_requires_a_nonempty_reason(): void
    {
        $dossier=$this->dossier();
        $result=$this->manual(['decision'=>'non_eligible','commentaire'=>'   ']);
        $this->assertSame('error',$result['bool']);
        $this->assertStringContainsString('motif',$result['message']);
        $this->assertSame('soumis',$dossier->fresh()->etat_dossier);
    }
}
