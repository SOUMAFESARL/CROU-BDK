<?php
namespace Tests\Feature;
use App\Models\{DisciplinaryReport,User};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
use Tests\TestCase;
class SecurityDisciplinaryReportTest extends TestCase
{
    private string $url='/api/V1/service-securite/incidents-disciplinaires';
    protected function setUp(): void {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','auth.guards.admin.provider'=>'admins','session.driver'=>'array']);
        DB::purge('sqlite');
        $this->withoutMiddleware([\App\Http\Middleware\LogUserActivity::class,\App\Http\Middleware\EnsureTokenIsValid::class,\App\Http\Middleware\EnforceSystemSecurity::class]);
        Schema::create('roles',function(Blueprint $t){$t->id();$t->string('name');});
        (require database_path('migrations/2026_08_11_000001_create_role_permissions_tables.php'))->up();
        (require database_path('migrations/2026_08_11_000002_create_modules_table.php'))->up();
        DB::table('roles')->insert([['id'=>1,'name'=>'Superadmin'],['id'=>2,'name'=>'Agent']]);
        DB::table('role_permission')->insert(['role_id'=>1,'permission_id'=>DB::table('permissions')->value('id')]);
        (require database_path('migrations/2026_08_10_000003_create_disciplinary_reports_tables.php'))->up();
        foreach(['etudiants'=>'id_etudiant','cite'=>'id_cite','batiment'=>'id_batiment','chambre'=>'id_chambre'] as $table=>$key)
            Schema::create($table,function(Blueprint $t)use($key){$t->string($key)->primary();$t->softDeletes();});
        Schema::table('etudiants', function(Blueprint $t) { $t->string('name')->nullable(); $t->string('matricule')->nullable(); });
        Schema::create('assignation_chambres', function(Blueprint $t) {
            $t->string('id_assignation')->primary(); $t->string('etudiant_id'); $t->string('chambre_id');
            $t->string('statut'); $t->dateTime('date_assignation'); $t->dateTime('date_fin')->nullable(); $t->timestamps();
        });
        foreach ([
            '2026_08_10_000001_create_sanctions_tables.php',
            '2026_08_10_000002_add_scope_to_sanctions_table.php',
            '2026_08_17_000001_add_student_declaration_fields_to_disciplinary_reports.php',
            '2026_08_10_000005_create_departure_requests_and_settlements.php',
            '2026_08_31_000010_complete_departure_workflow.php',
            '2026_09_23_000004_link_disciplinary_decisions_to_sanctions.php',
        ] as $migration) (require database_path('migrations/'.$migration))->up();
        DB::table('etudiants')->insert([['id_etudiant'=>'student','name'=>'Déclarant'],['id_etudiant'=>'target','name'=>'Résident concerné']]);

    }
    private function report(): DisciplinaryReport {
        return DisciplinaryReport::create(['etudiant_id'=>'student','cite_id'=>'cite','batiment_id'=>'building','chambre_id'=>'room','date_incident'=>now(),'nature'=>'Tapage','description'=>'Incident','statut'=>'transmis','gravite'=>'moyenne']);
    }
    private function login(int $role=1): void {
        $this->actingAs(new User(['id_user'=>'agent','name'=>'Agent','role_id'=>$role]),'admin');
    }
    public function test_guest_is_rejected(): void {$this->getJson($this->url)->assertUnauthorized();}
    public function test_permission_is_required(): void {$this->login(2);$this->getJson($this->url)->assertForbidden();}
    public function test_list_and_show(): void {
        $this->login();$report=$this->report();
        $this->getJson($this->url.'?statut=transmis')->assertOk()->assertJsonPath('data.0.id',$report->id_rapport);
        $this->getJson($this->url.'/'.$report->id_rapport)->assertOk()->assertJsonPath('data.statut','transmis');
    }
    public function test_transition_is_audited_and_replay_is_rejected(): void {
        $this->login();$report=$this->report();$url=$this->url.'/'.$report->id_rapport.'/transition';
        $this->patchJson($url,['action'=>'prendre_en_charge'])->assertOk()->assertJsonPath('data.statut','en_cours');
        $this->assertDatabaseHas('disciplinary_report_histories',['rapport_id'=>$report->id_rapport,'auteur_id'=>'agent','ancien_statut'=>'transmis','nouveau_statut'=>'en_cours']);
        $this->patchJson($url,['action'=>'prendre_en_charge'])->assertUnprocessable()->assertJsonValidationErrors('action');
        $this->assertSame(1,DB::table('disciplinary_report_histories')->count());
    }
    public function test_decision_requires_a_reason(): void {
        $this->login();$report=$this->report();$report->update(['statut'=>'en_cours']);
        $this->patchJson($this->url.'/'.$report->id_rapport.'/transition',['action'=>'decider'])->assertUnprocessable()->assertJsonValidationErrors(['commentaire','decision']);
        $this->assertSame('en_cours',$report->fresh()->statut);
    }

    private function decision(string $type, array $extra = []): array {
        $this->login();
        $report = $this->report();
        $report->update(['statut'=>'en_cours','source'=>'etudiant','declarant_id'=>'student']);
        $data = array_merge(['action'=>'decider','decision'=>'Décision motivée','commentaire'=>'Faits vérifiés',
            'sanction'=>$type,'etudiant_sanctionne_id'=>'target'], $extra);
        return [$report, $this->patchJson($this->url.'/'.$report->getKey().'/transition', $data)];
    }
    public function test_decision_requires_an_explicit_target_and_sanction(): void {
        [$report,$response] = $this->decision('expulsion',['etudiant_sanctionne_id'=>null]);
        $response->assertUnprocessable()->assertJsonValidationErrors('etudiant_sanctionne_id');
        $this->assertDatabaseCount('sanctions',0);
        $this->assertSame('en_cours',$report->fresh()->statut);
        $this->patchJson($this->url.'/'.$report->getKey().'/transition',
            ['action'=>'decider','decision'=>'Motif','commentaire'=>'Motif'])
            ->assertUnprocessable()->assertJsonValidationErrors('sanction');
    }
    public function test_warning_targets_the_named_student_not_the_reporter(): void {
        [$report,$response]=$this->decision('avertissement');
        $response->assertOk()->assertJsonPath('data.sanction.etudiant_id','target');
        $service=app(\App\Http\Services\DisciplinaryImpactService::class);
        $this->assertSame('avertissement',$service->statut('target'));
        $this->assertSame('aucune',$service->statut('student'));
        $service->verifier(\App\Models\Etudiant::findOrFail('target'));
        $this->assertDatabaseCount('sanction_histories',1);
        $this->assertDatabaseCount('departure_requests',0);
    }
    public function test_no_sanction_does_not_erase_an_earlier_sanction(): void {
        $this->decision('blame')[1]->assertOk();
        $this->decision('aucune',['etudiant_sanctionne_id'=>null])[1]->assertOk();
        $this->assertSame('blame',app(\App\Http\Services\DisciplinaryImpactService::class)->statut('target'));
        $this->assertDatabaseCount('sanctions',1);
    }
    public function test_suspension_requires_an_end_date_and_expires_after_that_day(): void {
        $this->decision('suspension')[1]->assertUnprocessable()->assertJsonValidationErrors('sanction_fin');
        $this->decision('suspension',['sanction_fin'=>today()->toDateString()])[1]->assertOk();
        $service=app(\App\Http\Services\DisciplinaryImpactService::class);
        $this->assertSame('suspension',$service->statut('target'));
        try { $service->verifier(\App\Models\Etudiant::findOrFail('target')); $this->fail('Suspension contournée'); }
        catch (\Illuminate\Validation\ValidationException $e) { $this->assertArrayHasKey('etudiant_id',$e->errors()); }
        $this->travel(1)->days();
        $this->assertSame('aucune',$service->statut('target'));
        $service->verifier(\App\Models\Etudiant::findOrFail('target'));
        $this->travelBack();
    }
    public function test_expulsion_prepares_departure_without_freeing_the_occupied_place(): void {
        DB::table('assignation_chambres')->insert(['id_assignation'=>'stay','etudiant_id'=>'target','chambre_id'=>'room',
            'statut'=>'confirmee','date_assignation'=>now()->subDay()]);
        [$report,$response]=$this->decision('expulsion');
        $response->assertOk()->assertJsonPath('data.sanction.type','expulsion');
        $this->assertDatabaseHas('assignation_chambres',['id_assignation'=>'stay','statut'=>'confirmee','date_fin'=>null]);
        $this->assertDatabaseHas('departure_requests',['assignation_id'=>'stay','motif'=>'expulsion','decision'=>'approuvee','statut'=>'en_attente']);
        $this->assertSame('expulsion',\App\Models\Etudiant::findOrFail('target')->statut_disciplinaire);
        $this->patchJson($this->url.'/'.$report->getKey().'/transition',['action'=>'decider','decision'=>'Motif','commentaire'=>'Motif',
            'sanction'=>'expulsion','etudiant_sanctionne_id'=>'target'])->assertUnprocessable();
        $this->assertDatabaseCount('sanctions',1);
        $this->assertDatabaseCount('departure_requests',1);
        $this->patchJson($this->url.'/'.$report->getKey().'/transition',['action'=>'cloturer'])->assertOk();
        $this->assertSame('expulsion',app(\App\Http\Services\DisciplinaryImpactService::class)->statut('target'));
    }
    public function test_lifting_expulsion_cancels_pending_departure_and_unblocks_student(): void {
        DB::table('assignation_chambres')->insert(['id_assignation'=>'stay','etudiant_id'=>'target','chambre_id'=>'room',
            'statut'=>'confirmee','date_assignation'=>now()->subDay()]);
        $this->decision('expulsion')[1]->assertOk();
        $sanction=\App\Models\Sanction::firstOrFail();
        $request=\Illuminate\Http\Request::create('/','PATCH',['statut'=>'levee','details'=>'Décision réexaminée']);
        $request->setUserResolver(fn()=>auth('admin')->user());
        app(\App\Http\Controllers\admin\immobilier\vie_residentielle\SanctionController::class)->changeStatus($request,$sanction);
        $this->assertSame('aucune',app(\App\Http\Services\DisciplinaryImpactService::class)->statut('target'));
        $this->assertDatabaseHas('departure_requests',['assignation_id'=>'stay','statut'=>'annulee']);
        $this->assertDatabaseCount('sanction_histories',2);
    }
    public function test_expulsion_blocks_allocation_before_looking_for_a_room(): void {
        $this->decision('expulsion')[1]->assertOk();
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(\App\Http\Services\AllocationChambreService::class)->allocateChambre(
            \App\Models\Etudiant::findOrFail('target'),new \App\Models\Campagne());
    }
    public function test_expulsion_blocks_eligibility_even_with_a_good_score(): void {
        $this->decision('expulsion')[1]->assertOk();
        $result=app(\App\Repositories\Interfaces\EtudiantRepositoryInterface::class)
            ->verifierEligibilite(\App\Models\Etudiant::findOrFail('target'), [], true);
        $this->assertFalse($result['eligible']);
        $this->assertSame(0,$result['score_global']);
    }
    public function test_web_decision_uses_the_same_sanction_service(): void {
        $this->login();
        $report=$this->report(); $report->update(['statut'=>'en_cours']);
        $request=\Illuminate\Http\Request::create('/','PATCH',['action'=>'decider','decision'=>'Blâme motivé',
            'commentaire'=>'Faits vérifiés','sanction'=>'blame','etudiant_sanctionne_id'=>'target']);
        $request->setUserResolver(fn()=>auth('admin')->user());
        app(\App\Http\Controllers\admin\immobilier\vie_residentielle\DisciplinaryReportController::class)->transition($request,$report);
        $this->assertSame('blame',app(\App\Http\Services\DisciplinaryImpactService::class)->statut('target'));
        $this->assertDatabaseHas('disciplinary_report_histories',['rapport_id'=>$report->getKey(),'nouveau_statut'=>'decision_prise']);
    }


    public function test_failure_applying_expulsion_rolls_back_the_decision_and_audit(): void {
        DB::table('assignation_chambres')->insert(['id_assignation'=>'reservation','etudiant_id'=>'target','chambre_id'=>'room',
            'statut'=>'en_attente','date_assignation'=>now()]);
        $this->mock(\App\Http\Services\AllocationChambreService::class)->shouldReceive('annulerAssignation')
            ->once()->andThrow(new \RuntimeException('Échec simulé'));
        [$report,$response]=$this->decision('expulsion');
        $response->assertStatus(500);
        $this->assertSame('en_cours',$report->fresh()->statut);
        $this->assertDatabaseCount('sanctions',0);
        $this->assertDatabaseCount('sanction_histories',0);
        $this->assertDatabaseCount('disciplinary_report_histories',0);
    }
    public function test_another_active_expulsion_is_not_cleared_by_lifting_one(): void {
        DB::table('assignation_chambres')->insert(['id_assignation'=>'stay','etudiant_id'=>'target','chambre_id'=>'room',
            'statut'=>'confirmee','date_assignation'=>now()->subDay()]);
        [$first]=$this->decision('expulsion');
        [$second]=$this->decision('expulsion');
        $lifted=\App\Models\Sanction::where('rapport_id',$second->getKey())->firstOrFail();
        DB::transaction(function() use($lifted) {
            $lifted->update(['statut'=>'levee']);
            app(\App\Http\Services\DisciplinaryImpactService::class)->synchroniser($lifted,'agent');
        });
        $this->assertSame('expulsion',app(\App\Http\Services\DisciplinaryImpactService::class)->statut('target'));
        $this->assertDatabaseHas('departure_requests',['assignation_id'=>'stay','statut'=>'en_attente',
            'sanction_id'=>\App\Models\Sanction::where('rapport_id',$first->getKey())->value('id_sanction')]);
        $this->assertDatabaseCount('departure_requests',1);
    }

}
