<?php
namespace Tests\Feature;

use App\Repositories\{CampagneRepository,EtudiantRepository};
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Database\Schema\Blueprint;

class RequestPerformanceTest extends \Tests\Support\DossierTestCase
{
    public function test_student_counters_use_one_query_and_keep_all_states(): void
    {
        DB::table('etudiants')->where('id_etudiant','student')->update(['statut_inscription'=>'En_verification']);
        DB::table('etudiants')->where('id_etudiant','other')->update(['statut_inscription'=>'residents']);
        DB::enableQueryLog();DB::flushQueryLog();
        $stats=(new EtudiantRepository)->getStats();
        $this->assertCount(1,DB::getQueryLog());
        $this->assertSame(2,$stats['total']);
        $this->assertSame(1,$stats['en_verification']);
        $this->assertSame(1,$stats['residents']);
        $this->assertSame(0,$stats['annulees']);
        DB::disableQueryLog();
    }
    public function test_campaign_counters_use_one_query_and_include_other_states_in_total(): void
    {
        DB::table('campagne')->insert(['id_campagne'=>'draft','statut'=>'brouillon']);
        DB::enableQueryLog();DB::flushQueryLog();
        $stats=app(CampagneRepository::class)->getStats();
        $this->assertCount(1,DB::getQueryLog());
        $this->assertSame(['active'=>1,'terminer'=>0,'planifier'=>0,'total'=>2],$stats);
        DB::disableQueryLog();
    }
    public function test_admission_page_limits_loaded_students_and_does_not_load_documents(): void
    {
        Schema::table('etudiants',fn(Blueprint $t)=>$t->timestamp('date_inscription')->nullable());
        for($i=0;$i<30;$i++) DB::table('etudiants')->insert([
            'id_etudiant'=>'candidate-'.$i,'statut_inscription'=>'demande','date_inscription'=>now(),
        ]);
        $this->dossier('candidate-29');
        $page=(new EtudiantRepository)->getAdmissionsPage('nouvelles_demandes');
        $this->assertSame(30,$page->total());
        $this->assertCount(25,$page);
        foreach($page as $student) {
            $this->assertFalse($student->relationLoaded('documents'));
            $this->assertFalse($student->relationLoaded('edl_entree'));
            $this->assertTrue($student->relationLoaded('derniereDemande'));
            $this->assertNotNull($student->demandes_count);
        }
    }
    public function test_read_requests_do_not_resolve_activity_log_actors(): void
    {
        \Illuminate\Support\Facades\Auth::shouldReceive('guard')->never();
        $request=\Illuminate\Http\Request::create('/','GET');
        $response=(new \App\Http\Middleware\LogUserActivity)->handle($request,fn()=>response('ok'));
        $this->assertSame('ok',$response->getContent());
    }
}
