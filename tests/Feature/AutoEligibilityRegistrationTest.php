<?php
namespace Tests\Feature;
use App\Http\Services\EtudiantService;
use App\Models\Etudiant;
class AutoEligibilityRegistrationTest extends \Tests\Support\DossierTestCase
{
    public function test_submission_does_not_make_an_admission_decision(): void
    {
        $dossier=$this->dossier();
        $this->evaluation=['eligible'=>false,'score_global'=>84];
        $result=app(EtudiantService::class)->finaliserInscriptionAutomatique('student',$dossier->getKey());
        $this->assertTrue($result['success']);
        $this->assertFalse($result['eligible']);
        $this->assertSame('soumis',$result['data']['etat_dossier']);
        $this->assertSame('soumis',$dossier->fresh()->etat_dossier);
        $this->assertSame(1,$dossier->transitions()->count());
    }
    public function test_high_score_does_not_skip_administrative_review(): void
    {
        $dossier=$this->dossier();
        $this->evaluation=['eligible'=>true,'score_global'=>100];
        $result=app(EtudiantService::class)->finaliserInscriptionAutomatique('student',$dossier->getKey());
        $this->assertSame('soumis',$result['data']['etat_dossier']);
        $this->assertSame(0,\App\Models\AssignationChambre::count());
    }
    public function test_draft_remains_unsubmitted(): void
    {
        $dossier=$this->dossier('student',true);
        $result=app(EtudiantService::class)->finaliserInscriptionAutomatique('student',$dossier->getKey());
        $this->assertSame('brouillon',$result['data']['etat_dossier']);
        $this->assertStringContainsString('Brouillon',$result['message']);
    }
    public function test_registration_finalization_is_bound_to_the_owner(): void
    {
        $dossier=$this->dossier();
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        app(EtudiantService::class)->finaliserInscriptionAutomatique('other',$dossier->getKey());
    }
}
