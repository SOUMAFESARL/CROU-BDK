<?php
namespace Tests\Feature;

use App\Http\Services\{CandidateRankingService, AllocationChambreService};
use App\Models\{Etudiant, DemandeEtudiant, Campagne, AssignationChambre, Chambre};
use App\Repositories\Interfaces\{EtudiantRepositoryInterface, ChambreRepositoryInterface, AssignationChambreRepositoryInterface};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\TestCase;

class CandidateRankingTest extends TestCase
{
    private array $scores = [];
    private Campagne $campaign;
    protected function setUp(): void {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);
        DB::purge('sqlite');
        (require database_path('migrations/2026_09_23_000003_create_campaign_scorings.php'))->up();
        Schema::create('campagne',function(Blueprint $t){$t->string('id_campagne')->primary();$t->softDeletes();});
        DB::table('campagne')->insert(['id_campagne'=>'campaign']);
        $this->campaign=Campagne::find('campaign');
        $this->mock(EtudiantRepositoryInterface::class)->shouldReceive('verifierEligibilite')
            ->andReturnUsing(function($student,$campaign){
                $this->assertSame('campaign',$campaign['data']['campagnes']->first()->getKey());
                $this->assertSame('campaign',$student->derniereDemande->campagne_id);
                return ['score_global'=>$this->scores[$student->getKey()] ?? null,'total_regles'=>10];
            });
    }
    private function student(string $id, float $score, string $date='2026-01-01'): Etudiant {
        $this->scores[$id]=$score;
        $s=\Mockery::mock(Etudiant::class)->makePartial();
        $s->fill(['id_etudiant'=>$id,'name'=>$id]);
        $s->shouldReceive('getAnneeEtude')->andReturn('L1');
        $request=new DemandeEtudiant(['id_demande'=>'request-'.$id,'campagne_id'=>'campaign','statut_demande'=>'En_verification','etat_dossier'=>'eligible','date_demande'=>$date,'created_at'=>$date]);
        $s->setRelation('demandes',collect([$request]));
        return $s;
    }
    public function test_score_descending_then_date_then_id_determine_rank(): void {
        $a=$this->student('a',100,'2026-01-02');
        $b=$this->student('b',90,'2026-01-01');
        $c=$this->student('c',100,'2026-01-01');
        $d=$this->student('d',100,'2026-01-01');
        $ranked=app(CandidateRankingService::class)->rank(collect([$b,$d,$a,$c]),$this->campaign);
        $this->assertSame(['request-c','request-d','request-a','request-b'],$ranked->pluck('demande_id')->all());
        $this->assertSame([1,2,3,4],$ranked->pluck('rang')->all());
    }
    public function test_an_old_verified_request_does_not_override_a_new_refusal(): void {
        $s=$this->student('a',100);
        $s->demandes->push(new DemandeEtudiant(['id_demande'=>'new','campagne_id'=>'campaign','statut_demande'=>'Refusée','etat_dossier'=>'refuse','created_at'=>'2026-02-01']));
        $this->assertCount(0,app(CandidateRankingService::class)->rank(collect([$s]),$this->campaign));
    }
    public function test_other_campaign_requests_do_not_change_the_score_context(): void {
        $s=$this->student('a',100);
        $s->demandes->push(new DemandeEtudiant(['id_demande'=>'other','campagne_id'=>'other','statut_demande'=>'En_verification','etat_dossier'=>'eligible','created_at'=>'2027-01-01']));
        $ranked=app(CandidateRankingService::class)->rank(collect([$s]),$this->campaign);
        $this->assertSame('request-a',$ranked->first()['demande_id']);
    }
    public function test_missing_score_stops_the_batch_instead_of_assigning_arbitrary_priority(): void {
        $s=$this->student('a',100);unset($this->scores['a']);
        $this->expectException(\RuntimeException::class);
        app(CandidateRankingService::class)->rank(collect([$s]),$this->campaign);
    }
    public function test_last_available_place_goes_to_the_highest_ranked_candidate(): void {
        $low=$this->student('low',80);$high=$this->student('high',100);
        $repo=$this->mock(AssignationChambreRepositoryInterface::class);
        $repo->shouldReceive('getStudentsSansChambres')->with('campaign')->andReturn(collect([$low,$high]));
        $service=new class($this->mock(ChambreRepositoryInterface::class),$repo) extends AllocationChambreService {
            public array $order=[];
            public function allocateChambre(Etudiant $student, Campagne $campaign, ?Chambre $room=null, ?int $bed=null): ?AssignationChambre {
                $this->order[]=$student->getKey();
                if(count($this->order)>1)return null;
                $assignment=new AssignationChambre();
                $assignment->setRelation('chambre',new Chambre(['numero_chambre'=>'101','type_chambre_code'=>'double']));
                return $assignment;
            }
        };
        $result=$service->allocateAllStudents('campaign');
        $this->assertSame(['high','low'],$service->order);
        $this->assertSame('high',$result['allocated'][0]['id_etudiant']);
        $this->assertSame(1,$result['allocated'][0]['rang']);
        $this->assertSame(2,$result['not_allocated'][0]['rang']);
        $this->assertSame(50.0,$result['success_rate']);
    }

    private function scoringData(): array {
        $points=[];
        foreach (\App\Http\Services\CampaignScoringService::FIELDS as $group=>$fields) $points[$group]=array_fill_keys(array_keys($fields),0);
        $points['academique']=['tb'=>100,'bien'=>80,'ab'=>60,'passable'=>40,'insuffisant'=>0];
        $points['social']=['boursier'=>100,'non_boursier'=>0];
        $points['geographique']=['local'=>0,'proche'=>35,'loin'=>65,'tres_loin'=>100];
        $points['specifique']=['pmr'=>100,'autre'=>0];
        return ['campagne_id'=>'campaign','weights'=>['social'=>0,'academique'=>100,'geographique'=>0,'specifique'=>0],
            'points'=>$points,'minimum_score'=>0,'pmr_first'=>false,'active'=>true,'created_by'=>'admin'];
    }
    public function test_active_campaign_scoring_replaces_the_provisional_score(): void {
        $config=\App\Models\CampaignScoring::create($this->scoringData());
        $a=$this->student('a',100); $a->demandes->first()->moyenne=12;
        $b=$this->student('b',80); $b->demandes->first()->moyenne=18;
        $ranked=app(CandidateRankingService::class)->rank(collect([$a,$b]),$this->campaign);
        $this->assertSame(['request-b','request-a'],$ranked->pluck('demande_id')->all());
        $this->assertSame([100.0,60.0],$ranked->pluck('score')->all());
        $this->assertSame($config->id,$ranked->first()['scoring_version']);
    }
    public function test_confirmed_pmr_priority_precedes_score_only_when_enabled(): void {
        $config=\App\Models\CampaignScoring::create(array_replace($this->scoringData(),['pmr_first'=>true]));
        $a=$this->student('a',100); $a->demandes->first()->moyenne=18;
        $b=$this->student('b',80); $b->demandes->first()->moyenne=10; $b->pmr_confirme=true;
        $ranked=app(CandidateRankingService::class)->rank(collect([$a,$b]),$this->campaign);
        $this->assertSame('request-b',$ranked->first()['demande_id']);
        $config->update(['pmr_first'=>false]);
        $ranked=app(CandidateRankingService::class)->rank(collect([$a,$b]),$this->campaign);
        $this->assertSame('request-a',$ranked->first()['demande_id']);
    }
    public function test_weighted_score_and_distance_boundaries(): void {
        $data=$this->scoringData(); $data['weights']=array_fill_keys(array_keys($data['weights']),25);
        $config=new \App\Models\CampaignScoring($data);
        $s=$this->student('a',100); $s->boursier=true; $s->pmr_confirme=true; $s->distance=100;
        $request=$s->demandes->first(); $request->moyenne=14;
        $score=app(\App\Http\Services\CampaignScoringService::class)->calculate($s,$request,$config);
        $this->assertSame(86.25,$score['score']);
        $s->distance=200;
        $this->assertSame(95.0,app(\App\Http\Services\CampaignScoringService::class)->calculate($s,$request,$config)['score']);
    }
    public function test_minimum_score_prevents_automatic_reservation(): void {
        \App\Models\CampaignScoring::create(array_replace($this->scoringData(),['minimum_score'=>90]));
        $s=$this->student('low',100);$s->demandes->first()->moyenne=12;
        $repo=$this->mock(AssignationChambreRepositoryInterface::class);
        $repo->shouldReceive('getStudentsSansChambres')->andReturn(collect([$s]));
        $service=new class($this->mock(ChambreRepositoryInterface::class),$repo) extends AllocationChambreService {
            public function allocateChambre(Etudiant $s,Campagne $c,?Chambre $r=null,?int $b=null): ?AssignationChambre {
                throw new \LogicException('Below-threshold candidate must not be allocated');
            }
        };
        $result=$service->allocateAllStudents('campaign');
        $this->assertSame(0,$result['success_count']);
        $this->assertStringContainsString('seuil',$result['not_allocated'][0]['reason']);
    }
    public function test_scoring_page_save_persists_a_new_version(): void {
        auth('admin')->setUser(new \App\Models\User(['id_user'=>'admin','role_id'=>1]));
        $controller=app(\App\Http\Controllers\admin\immobilier\parametrage\scoring_campagne\ScoringCampagneController::class);
        $request=\Illuminate\Http\Request::create('/','POST',$this->scoringData());
        $controller->store($request); $controller->store($request);
        $this->assertSame(2,\App\Models\CampaignScoring::count());
        $this->assertSame('admin',\App\Models\CampaignScoring::latest('id')->first()->created_by);
    }
    public function test_invalid_weight_total_is_rejected(): void {
        auth('admin')->setUser(new \App\Models\User(['id_user'=>'admin','role_id'=>1]));
        $data=$this->scoringData(); $data['weights']['social']=40;
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(\App\Http\Controllers\admin\immobilier\parametrage\scoring_campagne\ScoringCampagneController::class)
            ->store(\Illuminate\Http\Request::create('/','POST',$data));
    }
    public function test_missing_academic_data_cannot_be_silently_scored(): void {
        \App\Models\CampaignScoring::create($this->scoringData());
        $s=$this->student('a',100);
        $this->expectException(\RuntimeException::class);
        app(CandidateRankingService::class)->rank(collect([$s]),$this->campaign);
    }
}
