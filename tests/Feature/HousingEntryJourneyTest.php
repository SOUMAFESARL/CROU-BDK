<?php
namespace Tests\Feature;
use App\Models\{HousingEntrySetting,HousingEntryAppointment,HousingEntryMessage,StudentContractApproval,Etudiant,AssignationChambre};
use App\Http\Services\{EntryAppointmentService,HousingEntryJourney,ContractTerms};
use Illuminate\Support\Facades\{DB,Schema,Mail};
use Illuminate\Database\Schema\Blueprint;

class HousingEntryJourneyTest extends \Tests\Support\DossierTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(\Carbon\Carbon::parse('2026-09-28 08:00:00'));
        (require database_path('migrations/2026_09_24_000001_create_housing_entry_workflow.php'))->up();
        (require database_path('migrations/2026_09_23_000002_create_student_contract_approvals.php'))->up();
        Schema::table('campagne',function(Blueprint $t){$t->date('date_echeance_demande')->nullable();$t->string('annee_campagne')->nullable();});
        Schema::table('etudiants',function(Blueprint $t){$t->string('email1')->nullable();$t->string('email2')->nullable();});
        Schema::create('annonces',function(Blueprint $t){
            $t->string('id_annonce')->primary();
            foreach(['titre','contenu','statut','audience','canaux','priorite','vues','clics','reactions'] as $field)$t->text($field)->nullable();
            $t->timestamp('date_publication')->nullable();$t->timestamp('date_expiration')->nullable();$t->timestamps();$t->softDeletes();
        });
        $this->mock(\App\Repositories\CampagneRepository::class)->shouldReceive('getAll')
            ->andReturn(new \Illuminate\Database\Eloquent\Collection());
        HousingEntrySetting::current()->update(['values'=>[
            'enabled'=>true,'started_at'=>now()->subMinute()->toDateTimeString(),'proposal_hours'=>4,
            'appointments_enabled'=>true,'start_date'=>'2026-09-28','weekdays'=>[1],
            'opening_time'=>'09:00','closing_time'=>'10:00','slot_minutes'=>30,'capacity'=>1,'lead_hours'=>0,'location'=>'Accueil résidence',
        ]]);
    }
    private function approvedAssignment(string $student='student'): AssignationChambre
    {
        $dossier=$this->dossier($student);
        $this->workflow->admin($dossier->getKey(),'en_etude');$this->workflow->admin($dossier->getKey(),'eligible');
        $assignment=$this->allocate($student);
        $this->workflow->transition($dossier->getKey(),'accepte','etudiant',$student,null,['assignation_id'=>$assignment->getKey()]);
        $this->service->confirmerAssignation($assignment->getKey());
        StudentContractApproval::create(['id'=>(string)\Illuminate\Support\Str::uuid(),'assignment_id'=>$assignment->getKey(),
            'student_id'=>$student,'student_name'=>$student,'document_html'=>'Contract','document_hash'=>hash('sha256','Contract'),'approved_at'=>now()]);
        return $assignment->fresh();
    }
    public function test_configured_deadline_applies_and_acceptance_keeps_the_place(): void
    {
        $dossier=$this->eligibleRequest();$assignment=$this->allocate();
        $this->assertTrue($assignment->date_fin->equalTo(now()->addHours(4)));
        $this->accept($dossier,$assignment);
        $this->assertNull($assignment->fresh()->date_fin);
        $this->travel(5)->hours();
        $this->assertTrue($this->service->confirmerAssignation($assignment->getKey()));
    }
    public function test_appointments_follow_slots_capacity_and_are_not_duplicated(): void
    {
        DB::table('chambre')->update(['capacite_max'=>2]);
        $first=$this->approvedAssignment();$second=$this->approvedAssignment('other');
        $service=app(EntryAppointmentService::class);
        $a=$service->schedule($first->getKey());$b=$service->schedule($second->getKey());
        $this->assertSame('09:00',$a->starts_at->format('H:i'));
        $this->assertSame('09:30',$b->starts_at->format('H:i'));
        $this->assertSame($a->id,$service->schedule($first->getKey())->id);
        $this->assertSame(2,HousingEntryAppointment::count());
        $this->assertSame(2,HousingEntryMessage::count());
    }
    public function test_contract_approval_is_required_before_scheduling(): void
    {
        $assignment=$this->approvedAssignment();
        StudentContractApproval::query()->update(['approved_at'=>null]);
        $this->assertNull(app(EntryAppointmentService::class)->schedule($assignment->getKey()));
        $this->assertSame(0,HousingEntryAppointment::count());
    }
    public function test_campaign_and_detailed_proposal_are_prepared_once(): void
    {
        $this->eligibleRequest();$assignment=$this->allocate();
        $journey=app(HousingEntryJourney::class);$journey->prepare();$journey->prepare();
        $this->assertSame(1,DB::table('housing_campaign_announcements')->count());
        $this->assertSame(1,DB::table('annonces')->count());
        $message=HousingEntryMessage::firstOrFail();
        $this->assertSame('proposal',$message->kind);
        $this->assertStringContainsString('room',$message->body);
        $this->assertNotNull($message->details['deadline']);
        $this->assertSame(1,HousingEntryMessage::count());
    }
    public function test_cancelled_proposals_are_not_emailed(): void
    {
        Mail::fake();
        $this->eligibleRequest();
        $assignment = $this->allocate();
        $journey = app(HousingEntryJourney::class);
        $journey->prepare();
        $message = HousingEntryMessage::where('kind', 'proposal')->firstOrFail();
        $this->assertSame(1, HousingEntryMessage::where('kind', 'proposal')->count());

        app(\App\Http\Services\AllocationChambreService::class)
            ->annulerAssignation($assignment->getKey(), 'Proposition annulée pour le test.');

        $journey->sendPending();

        Mail::assertNothingSent();
        $this->assertSame('cancelled', $message->fresh()->status);
    }
}