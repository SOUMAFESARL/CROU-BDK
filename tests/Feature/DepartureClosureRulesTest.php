<?php
namespace Tests\Feature;

use App\Http\Services\DepartureClosureRules;
use App\Models\DepartureSetting;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Validation\ValidationException;

class DepartureClosureRulesTest extends \Tests\Support\DossierTestCase
{
    private function settings(): DepartureSetting
    {
        return new DepartureSetting(['notice_days'=>30,'late_notice_penalty'=>5000,
            'unpaid_blocking_threshold'=>10000,
            'damage_retention_grid'=>[['label'=>'Serrure','amount'=>7500]]]);
    }
    public function test_configured_notice_and_damage_amounts_are_applied_and_snapshotted(): void
    {
        $result=app(DepartureClosureRules::class)->evaluate($this->settings(),'2026-09-01','2026-09-15',[
            ['name'=>'Chambre','elements'=>[
                ['name'=>'Serrure porte','etat'=>'mauvais'],
                ['name'=>'Serrure placard','etat'=>'bon'],
            ]],
        ],9999);
        $this->assertSame(14,$result['preavis_effectif_jours']);
        $this->assertSame(5000.0,$result['penalite_preavis']);
        $this->assertSame(7500.0,$result['retenues_degradations'][0]['amount']);
        $this->assertCount(1,$result['retenues_degradations']);
        $this->assertSame(10000.0,$result['parametres']['unpaid_blocking_threshold']);
        $this->assertSame(9999.0,$result['impayes_constates']);
    }
    public function test_full_notice_does_not_trigger_penalty(): void
    {
        $result=app(DepartureClosureRules::class)->evaluate($this->settings(),'2026-09-01','2026-10-01',[],0);
        $this->assertSame(30,$result['preavis_effectif_jours']);
        $this->assertEquals(0,$result['penalite_preavis']);
    }
    public function test_unpaid_amount_at_threshold_blocks_closure(): void
    {
        $this->expectException(ValidationException::class);
        app(DepartureClosureRules::class)->evaluate($this->settings(),'2026-09-01','2026-10-01',[],10000);
    }
    public function test_zero_threshold_disables_blocking_and_changed_parameters_apply(): void
    {
        $settings=$this->settings();
        $settings->unpaid_blocking_threshold=0;
        $settings->notice_days=7;
        $settings->late_notice_penalty=2000;
        $result=app(DepartureClosureRules::class)->evaluate($settings,'2026-09-01','2026-09-05',[],20000);
        $this->assertSame(2000.0,$result['penalite_preavis']);
        $this->assertSame(7,$result['parametres']['notice_days']);
    }
    private function exitSchema(): void
    {
        Schema::create('departure_requests',function(Blueprint $t) {
            $t->string('id_demande_sortie')->primary();
            foreach(['assignation_id','etudiant_id','statut','decision'] as $field) $t->string($field);
        });
        Schema::create('departure_settlements',function(Blueprint $t) {
            $t->string('id_decompte')->primary();$t->string('demande_sortie_id');$t->string('etat_des_lieux_id');
        });
        Schema::create('etat_des_lieux',function(Blueprint $t) {
            $t->string('etat_des_lieux_id')->primary();
            foreach(['id_etudiant','id_chambre','type_etat'] as $field) $t->string($field);
        });
    }
    public function test_dossier_cannot_close_without_the_departure_process(): void
    {
        $this->exitSchema();
        $dossier=$this->eligibleRequest();$assignment=$this->allocate();
        try {
            $this->workflow->transition($dossier->getKey(),'cloture','systeme',null,'Clôture directe');
            $this->fail('Clôture sans décompte');
        } catch(ValidationException $e) {
            $this->assertSame('attribue_definitif',$dossier->fresh()->etat_dossier);
            $this->assertSame('confirmee',$assignment->fresh()->statut);
        }
    }
    public function test_completed_departure_can_close_and_keeps_applied_rules_in_history(): void
    {
        $this->exitSchema();
        $dossier=$this->eligibleRequest();$assignment=$this->allocate();
        DB::table('departure_requests')->insert(['id_demande_sortie'=>'exit','assignation_id'=>$assignment->getKey(),
            'etudiant_id'=>'student','statut'=>'cloturee','decision'=>'approuvee']);
        DB::table('departure_settlements')->insert(['id_decompte'=>'balance','demande_sortie_id'=>'exit','etat_des_lieux_id'=>'inventory']);
        DB::table('etat_des_lieux')->insert(['etat_des_lieux_id'=>'inventory','id_etudiant'=>'student','id_chambre'=>'room','type_etat'=>'sortie']);
        $assignment->update(['statut'=>'annulee','date_fin'=>now()]);
        $rules=app(DepartureClosureRules::class)->evaluate($this->settings(),'2026-09-01','2026-10-01',[],0);
        $this->workflow->transition($dossier->getKey(),'cloture','systeme',null,'Sortie réalisée',['regles_cloture'=>$rules]);
        $this->assertSame('cloture',$dossier->fresh()->etat_dossier);
        $history=$dossier->transitions->last();
        $this->assertSame('balance',$history->details['decompte_id']);
        $this->assertSame(30,$history->details['regles_cloture']['parametres']['notice_days']);
    }
}
