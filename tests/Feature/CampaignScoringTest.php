<?php
namespace Tests\Feature;

use App\Models\{Etudiant, DemandeEtudiant};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\TestCase;

class CampaignScoringTest extends TestCase
{
    protected function setUp(): void {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);
        DB::purge('sqlite');
        (require database_path('migrations/2026_09_23_000003_create_campaign_scorings.php'))->up();
        (require database_path('migrations/2026_10_26_000001_add_distance_thresholds_to_campaign_scorings.php'))->up();
        (require database_path('migrations/2026_10_27_000001_add_criteria_to_campaign_scorings.php'))->up();
        Schema::create('campagne',function(Blueprint $t){$t->string('id_campagne')->primary();$t->softDeletes();});
        DB::table('campagne')->insert(['id_campagne'=>'campaign']);
    }
    private function student(string $id, string $date='2026-01-01'): Etudiant {
        $s=\Mockery::mock(Etudiant::class)->makePartial();
        $s->fill(['id_etudiant'=>$id,'name'=>$id]);
        $s->shouldReceive('getAnneeEtude')->andReturn('L1');
        $request=new DemandeEtudiant(['id_demande'=>'request-'.$id,'campagne_id'=>'campaign','statut_demande'=>'En_verification','etat_dossier'=>'confirme','date_demande'=>$date,'created_at'=>$date]);
        $s->setRelation('demandes',collect([$request]));
        return $s;
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
    public function test_weighted_score_and_distance_boundaries(): void {
        $data=$this->scoringData(); $data['weights']=array_fill_keys(array_keys($data['weights']),25);
        $config=new \App\Models\CampaignScoring($data);
        $s=$this->student('a'); $s->boursier=true; $s->pmr_confirme=true; $s->distance=100;
        $request=$s->demandes->first(); $request->moyenne=14;
        $score=app(\App\Http\Services\CampaignScoringService::class)->calculate($s,$request,$config);
        $this->assertSame(86.25,$score['score']);
        $s->distance=200;
        $this->assertSame(95.0,app(\App\Http\Services\CampaignScoringService::class)->calculate($s,$request,$config)['score']);
    }
    public function test_custom_distance_thresholds_define_the_distance_bands(): void {
        $data=$this->scoringData(); $data['weights']=['social'=>0,'academique'=>0,'geographique'=>100,'specifique'=>0];
        $data['distance_thresholds']=['proche'=>10,'loin'=>20,'tres_loin'=>30];
        $config=new \App\Models\CampaignScoring($data);
        $s=$this->student('a'); $request=$s->demandes->first();
        $service=app(\App\Http\Services\CampaignScoringService::class);
        foreach ([9=>0.0,10=>35.0,25=>65.0,30=>100.0] as $distance=>$expected) {
            $s->distance=$distance;
            $this->assertSame($expected,$service->calculate($s,$request,$config)['score']);
        }
    }
    public function test_distance_thresholds_must_increase(): void {
        auth('admin')->setUser(new \App\Models\User(['id_user'=>'admin','role_id'=>1]));
        $data=$this->scoringData(); $data['distance_thresholds']=['proche'=>20,'loin'=>20,'tres_loin'=>30];
        try {
            app(\App\Http\Controllers\admin\immobilier\parametrage\scoring_campagne\ScoringCampagneController::class)
                ->store(\Illuminate\Http\Request::create('/','POST',$data));
            $this->fail('Des seuils non croissants doivent être refusés.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('distance_thresholds.loin',$e->errors());
        }
        $data['distance_thresholds']=['proche'=>10,'loin'=>20,'tres_loin'=>30];
        app(\App\Http\Controllers\admin\immobilier\parametrage\scoring_campagne\ScoringCampagneController::class)
            ->store(\Illuminate\Http\Request::create('/','POST',$data));
        $this->assertSame(['proche'=>10,'loin'=>20,'tres_loin'=>30],\App\Models\CampaignScoring::latest('id')->first()->distance_thresholds);
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
    public function test_missing_academic_data_scores_zero_points(): void {
        $config=new \App\Models\CampaignScoring($this->scoringData());
        $s=$this->student('a'); $request=$s->demandes->first();
        $service=app(\App\Http\Services\CampaignScoringService::class);
        $resultat=$service->calculate($s,$request,$config);
        $this->assertSame(0.0,$resultat['score']);
        $this->assertSame(\App\Http\Services\CampaignScoringService::NON_RENSEIGNE,$resultat['categories']['academique']);
        $this->assertStringContainsString('non renseigné',$service->detail($s,$request,$config)['volets'][1]['situation']);
    }
    private function ageCriterion(float $weight=100): array {
        return ['label'=>'Tranche d’âge','source'=>'age','weight'=>$weight,'other_points'=>10,
            'bands'=>[['min'=>null,'max'=>20,'points'=>100],['min'=>20,'max'=>25,'points'=>60]]];
    }
    public function test_added_age_criterion_scores_by_band_at_request_date(): void {
        $data=$this->scoringData(); $data['weights']['academique']=0;
        $data['criteria']=\App\Http\Services\CampaignScoringService::normalizeCriteria([$this->ageCriterion()]);
        $config=new \App\Models\CampaignScoring($data);
        $s=$this->student('a'); $request=$s->demandes->first(); $request->date_demande='2026-09-01';
        $service=app(\App\Http\Services\CampaignScoringService::class);
        foreach (['2007-01-01'=>100.0,'2006-09-01'=>60.0,'2006-09-02'=>100.0,'2001-09-01'=>10.0] as $naissance=>$expected) {
            $s->date_naissance=$naissance;
            $this->assertSame($expected,$service->calculate($s,$request,$config)['score'],$naissance);
        }
        $volet=$service->detail($s,$request,$config)['volets'][4];
        $this->assertSame('Tranche d’âge',$volet['volet']);
        $this->assertStringContainsString('hors tranches',$volet['situation']);
    }
    public function test_added_list_criterion_scores_by_value_and_missing_data_scores_zero(): void {
        $data=$this->scoringData(); $data['weights']=['social'=>0,'academique'=>50,'geographique'=>0,'specifique'=>0];
        $data['criteria']=\App\Http\Services\CampaignScoringService::normalizeCriteria([['label'=>'Genre','source'=>'genre','weight'=>50,'other_points'=>0,
            'values'=>[['value'=>'2','points'=>100],['value'=>'1','points'=>20]]]]);
        $config=new \App\Models\CampaignScoring($data);
        $s=$this->student('a'); $request=$s->demandes->first(); $request->moyenne=18;
        $service=app(\App\Http\Services\CampaignScoringService::class);
        $s->genre_id=2; $this->assertSame(100.0,$service->calculate($s,$request,$config)['score']);
        $s->genre_id=1; $this->assertSame(60.0,$service->calculate($s,$request,$config)['score']);
        $s->genre_id=3; $this->assertSame(50.0,$service->calculate($s,$request,$config)['score']);
        $s->genre_id=null; $this->assertSame(50.0,$service->calculate($s,$request,$config)['score']);
        $this->assertStringContainsString('non renseigné',$service->detail($s,$request,$config)['volets'][4]['situation']);
    }
    public function test_added_criteria_count_in_the_weight_total_and_bands_cannot_overlap(): void {
        auth('admin')->setUser(new \App\Models\User(['id_user'=>'admin','role_id'=>1]));
        $controller=app(\App\Http\Controllers\admin\immobilier\parametrage\scoring_campagne\ScoringCampagneController::class);
        $data=$this->scoringData(); $data['criteria']=[$this->ageCriterion(20)];
        try { $controller->store(\Illuminate\Http\Request::create('/','POST',$data)); $this->fail('Total de 120 % accepté.'); }
        catch (\Illuminate\Validation\ValidationException $e) { $this->assertArrayHasKey('weights',$e->errors()); }
        $data['weights']['academique']=80;
        $data['criteria'][0]['bands'][1]['min']=19;
        try { $controller->store(\Illuminate\Http\Request::create('/','POST',$data)); $this->fail('Tranches qui se chevauchent acceptées.'); }
        catch (\Illuminate\Validation\ValidationException $e) { $this->assertArrayHasKey('criteria.0.bands.1.min',$e->errors()); }
        $data['criteria'][0]['bands'][1]['min']=20;
        $controller->store(\Illuminate\Http\Request::create('/','POST',$data));
        $critere=\App\Models\CampaignScoring::latest('id')->first()->criteria[0];
        $this->assertMatchesRegularExpression('/^c_[a-z0-9]{10}$/',$critere['key']);
        $this->assertSame(['age',20.0],[$critere['source'],(float)$critere['weight']]);
    }
}
