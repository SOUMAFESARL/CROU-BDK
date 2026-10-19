<?php

namespace Tests\Feature;

use App\Http\Services\AllocationChambreService;
use App\Http\Services\AttributionRules;
use App\Models\AssignationChambre;
use App\Models\Campagne;
use App\Models\Chambre;
use App\Models\Etudiant;
use App\Repositories\AssignationChambreRepository;
use App\Repositories\Interfaces\ChambreRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class DossierWorkflowTest extends \Tests\Support\DossierTestCase
{
    public function test_full_cycle_requires_student_acceptance_before_final_confirmation(): void
    {
        $dossier=$this->dossier('student',true);
        $this->assertSame('brouillon',$dossier->etat_dossier);
        $this->workflow->transition($dossier->getKey(),'soumis','etudiant','student');
        $this->workflow->admin($dossier->getKey(),'incomplet','Certificat à fournir.');
        $this->workflow->transition($dossier->getKey(),'soumis','etudiant','student','Certificat ajouté.');
        $this->workflow->admin($dossier->getKey(),'en_etude','Pièces reçues.');
        $this->workflow->admin($dossier->getKey(),'eligible');
        $assignment=$this->allocate();
        $this->assertSame('pre_attribue',$dossier->fresh()->etat_dossier);
        try { $this->service->confirmerAssignation($assignment->getKey()); $this->fail('Acceptation ignorée'); }
        catch (ValidationException $e) { $this->assertArrayHasKey('etat_dossier',$e->errors()); }
        $this->assertSame('en_attente',$assignment->fresh()->statut);
        $this->accept($dossier,$assignment);
        $this->service->confirmerAssignation($assignment->getKey());
        $this->assertSame('attribue_definitif',$dossier->fresh()->etat_dossier);
        $this->assertSame('confirmee',$assignment->fresh()->statut);
        $this->assertSame('residents',Etudiant::findOrFail('student')->statut_inscription);
        $this->assertSame(['brouillon','soumis','incomplet','soumis','en_etude','eligible','pre_attribue','accepte','attribue_definitif'],
            $dossier->transitions()->pluck('nouvel_etat')->all());
        $this->assertDatabaseHas('dossier_transitions',['demande_id'=>$dossier->getKey(),'nouvel_etat'=>'accepte','acteur_type'=>'etudiant','acteur_id'=>'student']);
    }
    public function test_incomplete_and_refused_decisions_require_reasons(): void
    {
        $dossier=$this->dossier();
        foreach(['incomplet','refuse'] as $state) {
            try { $this->workflow->admin($dossier->getKey(),$state); $this->fail('Motif omis'); }
            catch(ValidationException $e) { $this->assertSame('soumis',$dossier->fresh()->etat_dossier); }
        }
        $this->assertDatabaseCount('dossier_transitions',1);
    }
    public function test_stages_cannot_be_skipped_or_written_directly(): void
    {
        $dossier=$this->dossier();
        try {$this->workflow->admin($dossier->getKey(),'eligible');$this->fail('Étude ignorée');}
        catch(ValidationException $e) {$this->assertSame('soumis',$dossier->fresh()->etat_dossier);}
        $this->expectException(ValidationException::class);
        $dossier->update(['statut_demande'=>'Acceptée','etat_dossier'=>'attribue_definitif']);
    }
    public function test_student_cannot_accept_someone_elses_proposal(): void
    {
        $dossier=$this->eligibleRequest();$assignment=$this->allocate();
        try {$this->workflow->transition($dossier->getKey(),'accepte','etudiant','other',null,['assignation_id'=>$assignment->getKey()]);$this->fail('Autre étudiant autorisé');}
        catch(\Symfony\Component\HttpKernel\Exception\HttpException $e) {$this->assertSame(403,$e->getStatusCode());}
        $this->assertSame('pre_attribue',$dossier->fresh()->etat_dossier);
    }
    public function test_admin_cannot_accept_in_place_of_student(): void
    {
        $dossier=$this->eligibleRequest();$assignment=$this->allocate();
        try {$this->workflow->admin($dossier->getKey(),'accepte');$this->fail('Agent accepté');}
        catch(\Symfony\Component\HttpKernel\Exception\HttpException $e) {$this->assertSame(403,$e->getStatusCode());}
        $this->assertSame('pre_attribue',$dossier->fresh()->etat_dossier);
    }
    public function test_failed_eligibility_keeps_the_request_under_review(): void
    {
        $dossier=$this->dossier();$this->workflow->admin($dossier->getKey(),'en_etude');
        $this->evaluation['eligible']=false;
        try {$this->workflow->admin($dossier->getKey(),'eligible');$this->fail('Inéligible admis');}
        catch(ValidationException $e) {$this->assertSame('en_etude',$dossier->fresh()->etat_dossier);}
        $this->assertDatabaseCount('assignation_chambres',0);
    }
    public function test_refusal_releases_the_proposal_and_is_audited(): void
    {
        $dossier=$this->eligibleRequest();$assignment=$this->allocate();
        $this->workflow->transition($dossier->getKey(),'refuse','etudiant','student','Je refuse cette chambre.',
            ['assignation_id'=>$assignment->getKey()]);
        $this->assertSame('annulee',$assignment->fresh()->statut);
        $this->assertSame('refuse',$dossier->fresh()->etat_dossier);
        $this->assertSame(1,Chambre::findOrFail('room')->getNombrePlacesDisponibles());
    }
    public function test_reassignment_requires_a_new_acceptance(): void
    {
        $dossier=$this->eligibleRequest();$assignment=$this->allocate();$this->accept($dossier,$assignment);
        $this->room('second');
        $next=$this->service->reaffecterChambre(Etudiant::findOrFail('student'),Chambre::findOrFail('second'),Campagne::findOrFail('campaign'),'Changement demandé.');
        $this->assertSame('pre_attribue',$dossier->fresh()->etat_dossier);
        try {$this->accept($dossier,$assignment);$this->fail('Ancienne proposition acceptée');}
        catch(ValidationException $e) {$this->assertSame('pre_attribue',$dossier->fresh()->etat_dossier);}
        $this->accept($dossier,$next);
        $this->assertSame('accepte',$dossier->fresh()->etat_dossier);
    }
    public function test_expired_proposal_cannot_be_accepted(): void
    {
        $dossier=$this->eligibleRequest();$assignment=$this->allocate();
        $assignment->update(['date_fin'=>now()->subMinute()]);
        $this->expectException(ValidationException::class);
        $this->accept($dossier,$assignment);
    }
    public function test_closed_campaign_cannot_receive_a_draft(): void
    {
        $dossier=$this->dossier('student',true);DB::table('campagne')->update(['statut'=>'terminer']);
        $this->expectException(ValidationException::class);
        $this->workflow->transition($dossier->getKey(),'soumis','etudiant','student');
    }
    public function test_expiry_never_changes_a_final_assignment(): void
    {
        $dossier=$this->eligibleRequest();$assignment=$this->allocate();$this->accept($dossier,$assignment);
        $this->service->confirmerAssignation($assignment->getKey());
        $this->expectException(ValidationException::class);
        $this->workflow->transition($dossier->getKey(),'expire','systeme',null,'Campagne terminée.');
    }
    public function test_direct_admission_confirms_existing_resident_without_academic_eligibility(): void
    {
        \Illuminate\Support\Facades\Schema::table('etudiants', function (\Illuminate\Database\Schema\Blueprint $t) {
            $t->boolean('is_validation')->default(false);
            $t->timestamp('date_validation')->nullable();
            $t->string('users_validation')->nullable();
        });
        $dossier=$this->dossier();$this->evaluation['eligible']=false;
        $dossier->update(['date_demande'=>'2025-10-01']);
        $state=$this->workflow->admissionDirecte($dossier,Chambre::findOrFail('room'),Campagne::findOrFail('campaign'),1);
        $this->assertSame('attribue_definitif',$state);
        $assignment=AssignationChambre::firstOrFail();
        $this->assertSame('confirmee',$assignment->statut);
        $this->assertNull($assignment->date_fin);
        $this->assertSame('2025-10-01',$assignment->date_assignation->format('Y-m-d'));
        $this->assertSame(1,$assignment->lit->numero);
        $this->assertSame('Acceptée',$dossier->fresh()->statut_demande);
        $this->assertSame($assignment->getKey(),$dossier->fresh()->assignation_id);
        $this->assertSame('residents',Etudiant::findOrFail('student')->statut_inscription);
        $this->assertTrue(Etudiant::findOrFail('student')->is_validation);
        $this->assertSame(1,Chambre::findOrFail('room')->occupants_actuels);
        $this->assertDatabaseHas('dossier_transitions',['nouvel_etat'=>'attribue_definitif']);
        $this->assertDatabaseMissing('dossier_transitions',['nouvel_etat'=>'accepte']);
        try {
            $this->workflow->admissionDirecte($this->dossier('other'),Chambre::findOrFail('room'),Campagne::findOrFail('campaign'));
            $this->fail('A full room cannot receive another resident.');
        } catch (ValidationException $e) {
            $this->assertDatabaseCount('assignation_chambres',1);
            $this->assertSame('soumis',\App\Models\DemandeEtudiant::where('etudiant_id','other')->firstOrFail()->etat_dossier);
        }
    }
    public function test_http_transition_checks_actor_permission_and_owner(): void
    {
        $dossier=$this->dossier();
        $this->postJson(route('admin.dossiers.transition',$dossier),['etat'=>'en_etude'])->assertOk()->assertJsonPath('data.etat_dossier','en_etude');
        $student=Etudiant::findOrFail('other');$this->actingAs($student,'etudiant');
        $this->postJson(route('etudiants.dossiers.transition',$dossier),['etat'=>'annule','motif'=>'Test'])->assertForbidden();
    }
    public function test_replaying_a_transition_does_not_duplicate_the_audit(): void
    {
        $dossier=$this->eligibleRequest();$assignment=$this->allocate();$this->accept($dossier,$assignment);
        $count=$dossier->transitions()->count();
        try {$this->accept($dossier,$assignment);$this->fail('Double acceptation');}
        catch(ValidationException $e) {$this->assertSame($count,$dossier->transitions()->count());}
    }

    public function test_returning_to_eligibility_releases_the_previous_proposal(): void
    {
        $dossier=$this->eligibleRequest();$assignment=$this->allocate();
        $this->workflow->admin($dossier->getKey(),'eligible','Reprendre la recherche de chambre.');
        $this->assertNull($dossier->fresh()->assignation_id);
        $this->assertSame('annulee',$assignment->fresh()->statut);
        $this->assertSame(1,Chambre::findOrFail('room')->getNombrePlacesDisponibles());
    }
    public function test_final_confirmation_rechecks_eligibility_atomically(): void
    {
        $dossier=$this->eligibleRequest();$assignment=$this->allocate();$this->accept($dossier,$assignment);
        $this->evaluation['eligible']=false;
        try {$this->service->confirmerAssignation($assignment->getKey());$this->fail('Critères ignorés');}
        catch(ValidationException $e) {
            $this->assertSame('accepte',$dossier->fresh()->etat_dossier);
            $this->assertSame('en_attente',$assignment->fresh()->statut);
            $this->assertSame('en_attente',Etudiant::findOrFail('student')->statut_inscription);
        }
    }
    public function test_confirmed_stay_cannot_be_cancelled_outside_departure_process(): void
    {
        $dossier=$this->eligibleRequest();$assignment=$this->allocate();$this->accept($dossier,$assignment);
        $this->service->confirmerAssignation($assignment->getKey());
        try {$this->service->annulerAssignation($assignment->getKey());$this->fail('Séjour annulé');}
        catch(ValidationException $e) {
            $this->assertSame('attribue_definitif',$dossier->fresh()->etat_dossier);
            $this->assertSame('confirmee',$assignment->fresh()->statut);
        }
    }
    public function test_migration_preserves_existing_assignments_without_fabricating_acceptance(): void
    {
        $migration=require database_path('migrations/2026_09_23_000006_create_dossier_workflow.php');
        $migration->down();
        foreach(['student'=>'confirmee','other'=>'en_attente'] as $student=>$status) {
            DB::table('demande')->insert(['id_demande'=>$student,'etudiant_id'=>$student,'campagne_id'=>'campaign',
                'statut_demande'=>'Acceptée','created_at'=>now()]);
            DB::table('assignation_chambres')->insert(['id_assignation'=>$student,'etudiant_id'=>$student,
                'chambre_id'=>'room','campagne_id'=>'campaign','statut'=>$status,'date_assignation'=>now()]);
        }
        $migration->up();
        $this->assertDatabaseHas('demande',['id_demande'=>'student','etat_dossier'=>'attribue_definitif','assignation_id'=>'student']);
        $this->assertDatabaseHas('demande',['id_demande'=>'other','etat_dossier'=>'pre_attribue','assignation_id'=>'other']);
        $this->assertSame(2,DB::table('dossier_transitions')->where('acteur_type','reprise')->count());
        $this->assertDatabaseMissing('dossier_transitions',['nouvel_etat'=>'accepte']);
    }
    public function test_api_acceptance_uses_authenticated_owner_and_current_proposal(): void
    {
        (require database_path('migrations/2026_08_27_100000_create_student_api_tokens_table.php'))->up();
        DB::table('etudiants')->update(['statut'=>1]);
        $token=\App\Models\StudentApiToken::create(['etudiant_id'=>'student','token_hash'=>hash('sha256','owner-token'),'expires_at'=>now()->addHour()]);
        $dossier=$this->eligibleRequest();$assignment=$this->allocate();
        $url='/api/V1/dossiers/'.$dossier->getKey();
        $this->getJson($url)->assertUnauthorized();
        $this->withToken('owner-token')->getJson($url)->assertOk()->assertJsonPath('data.etat_dossier','pre_attribue');
        $this->withToken('owner-token')->postJson($url.'/transition',['etat'=>'accepte'])->assertUnprocessable();
        $this->withToken('owner-token')->postJson($url.'/transition',['etat'=>'accepte','assignation_id'=>$assignment->getKey()])
            ->assertOk()->assertJsonPath('data.etat_dossier','accepte');
        $this->assertSame('en_attente',$assignment->fresh()->statut);
        $other=$this->dossier('other');
        $this->withToken('owner-token')->getJson('/api/V1/dossiers/'.$other->getKey())->assertForbidden();
    }
    public function test_tracking_panel_displays_proposal_and_history(): void
    {
        $dossier=$this->eligibleRequest();$assignment=$this->allocate();
        $this->view('dossier-cycle',['dossier'=>$dossier->fresh(),'mode'=>'student'])
            ->assertSee('Pré-attribué')->assertSee('room')->assertSee('Historique du dossier')
            ->assertSee($assignment->getKey());
    }
    public function test_admin_without_admission_permission_cannot_advance_dossier(): void
    {
        $dossier=$this->dossier();
        $actor=Mockery::mock(\App\Models\User::class)->makePartial();
        $actor->fill(['id_user'=>'observer']);
        $actor->shouldReceive('hasPermission')->andReturn(false);
        $this->actingAs($actor,'admin');
        $this->postJson(route('admin.dossiers.transition',$dossier),['etat'=>'en_etude'])->assertForbidden();
        $this->assertSame('soumis',$dossier->fresh()->etat_dossier);
    }
    public function test_credentials_are_sent_once_when_the_request_becomes_eligible(): void
    {
        Schema::table('etudiants', function (Blueprint $t) { $t->string('email1')->nullable(); $t->string('password')->nullable(); });
        DB::table('etudiants')->where('id_etudiant','student')->update(['email1'=>'etudiant@example.com']);
        \Illuminate\Support\Facades\Notification::fake();
        $dossier=$this->dossier();
        $this->workflow->admin($dossier->getKey(),'en_etude');
        \Illuminate\Support\Facades\Notification::assertNothingSent();
        $this->workflow->admin($dossier->getKey(),'eligible');
        \Illuminate\Support\Facades\Notification::assertSentOnDemandTimes(\App\Notifications\EtudiantIdentifiantsNotification::class,1);
        $this->assertNotNull(DB::table('etudiants')->where('id_etudiant','student')->value('password'));
        // Un nouveau passage en « Éligible » ne remplace pas le mot de passe déjà transmis.
        $this->workflow->admin($dossier->getKey(),'en_etude','Réexamen.');
        $this->workflow->admin($dossier->getKey(),'eligible');
        \Illuminate\Support\Facades\Notification::assertSentOnDemandTimes(\App\Notifications\EtudiantIdentifiantsNotification::class,1);
    }
}
