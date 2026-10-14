<?php

namespace Tests\Feature;

use App\Services\HousingReportingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HousingReportingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(new \App\Models\User(['role_id' => 1]), 'admin');
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->travelTo(now()->setDate(2026, 9, 25)->startOfDay());
        Schema::create('cite', function (Blueprint $t) {
            $t->string('id_cite'); $t->string('name'); $t->softDeletes();
        });
        Schema::create('batiment', function (Blueprint $t) {
            $t->string('id_batiment'); $t->string('cite_id'); $t->softDeletes();
        });
        Schema::create('chambre', function (Blueprint $t) {
            $t->string('id_chambre'); $t->string('cite_id'); $t->integer('capacite_max'); $t->string('etat'); $t->softDeletes();
        });
        Schema::create('assignation_chambres', function (Blueprint $t) {
            $t->string('id_assignation'); $t->string('chambre_id'); $t->string('etudiant_id'); $t->string('statut');
            $t->dateTime('date_assignation'); $t->dateTime('date_fin')->nullable();
        });
        DB::table('cite')->insert([['id_cite' => 'A', 'name' => 'Cité A'], ['id_cite' => 'B', 'name' => 'Cité B']]);
        DB::table('chambre')->insert([
            ['id_chambre' => 'r1', 'cite_id' => 'A', 'capacite_max' => 4, 'etat' => 'disponible'],
            ['id_chambre' => 'r2', 'cite_id' => 'A', 'capacite_max' => 2, 'etat' => 'maintenance'],
            ['id_chambre' => 'r3', 'cite_id' => 'B', 'capacite_max' => 20, 'etat' => 'disponible'],
        ]);
    }

    private function report(string $module): array
    {
        return (new HousingReportingService)->reports($module, now()->startOfMonth(), now()->endOfDay(), 'A')[$module]['sections'];
    }

    public function test_only_the_selected_module_is_loaded(): void
    {
        $reports = (new HousingReportingService)->reports('parc-immobilier', now()->startOfMonth(), now()->endOfDay(), 'A');

        $this->assertSame(['parc-immobilier'], array_keys($reports));
        $this->assertFalse($reports['parc-immobilier']['sections'][0]['unavailable']);
    }

    public function test_module_view_compiles_to_valid_php(): void
    {
        $compiled = app('blade.compiler')->compileString(file_get_contents(resource_path('views/admin/hebergement/reporting/modules.blade.php')));

        $this->assertNotEmpty(token_get_all($compiled, TOKEN_PARSE));
    }

    private function assignment(string $id, string $status = 'confirmee', string $date = '2026-09-01', string $room = 'r1'): void
    {
        DB::table('assignation_chambres')->insert(['id_assignation' => $id, 'chambre_id' => $room, 'etudiant_id' => $id, 'statut' => $status, 'date_assignation' => $date]);
    }

    public function test_capacity_excludes_reserved_and_unusable_places_without_counting_other_cities(): void
    {
        $this->assignment('resident');
        $this->assignment('reservation', 'en_attente');
        $this->assignment('future', 'confirmee', '2026-10-01');
        $this->assignment('cancelled', 'annulee');
        $this->assignment('other', 'confirmee', '2026-09-01', 'r3');
        $metrics = $this->report('parc-immobilier')[0]['metrics'];
        $this->assertSame(6, $metrics['Capacité totale']);
        $this->assertSame(1, $metrics['Places occupées']);
        $this->assertSame(2, $metrics['Places réservées']);
        $this->assertSame(1, $metrics['Places libres utilisables']);
    }

    public function test_finance_uses_validated_payments_and_non_cancelled_rents_in_the_selected_period(): void
    {
        Schema::create('loyers', function (Blueprint $t) {
            $t->string('id_loyer'); $t->string('cite_id'); $t->string('statut'); $t->date('date_limit_paiement');
            $t->decimal('montant_total'); $t->decimal('solde_restant'); $t->softDeletes();
        });
        Schema::create('paiements', function (Blueprint $t) {
            $t->string('cite_id'); $t->string('statut_paiement'); $t->string('type_paiement'); $t->date('date_paiement');
            $t->decimal('montant'); $t->softDeletes();
        });
        foreach ([['A', 'en_attente', 1000], ['A', 'annule', 5000], ['B', 'en_attente', 9000]] as [$cite, $status, $amount]) {
            DB::table('loyers')->insert(['id_loyer' => $status.$cite, 'cite_id' => $cite, 'statut' => $status, 'date_limit_paiement' => '2026-09-20', 'montant_total' => $amount, 'solde_restant' => $amount / 2]);
        }
        foreach ([['A', 'valide', 'loyer', 100], ['A', 'valide', 'caution', 50], ['A', 'en_attente', 'loyer', 300], ['A', 'rembourse', 'loyer', 20], ['B', 'valide', 'loyer', 999]] as [$cite, $status, $type, $amount]) {
            DB::table('paiements')->insert(['cite_id' => $cite, 'statut_paiement' => $status, 'type_paiement' => $type, 'date_paiement' => '2026-09-25', 'montant' => $amount]);
        }
        DB::table('paiements')->insert(['cite_id' => 'A', 'statut_paiement' => 'valide', 'type_paiement' => 'loyer', 'date_paiement' => '2026-08-31', 'montant' => 888]);
        $metrics = $this->report('finances')[0]['metrics'];
        $this->assertSame(1000.0, $metrics['Loyers facturés (F CFA)']);
        $this->assertSame(500.0, $metrics['Reste à recouvrer (F CFA)']);
        $this->assertSame(150.0, $metrics['Encaissements validés (F CFA)']);
        $this->assertSame(100.0, $metrics['Dont loyers (F CFA)']);
        $this->assertSame(20.0, $metrics['Paiements remboursés (F CFA)']);
    }

    public function test_stock_reporting_neither_writes_nor_double_counts_initial_stock(): void
    {
        Schema::create('equipement', function (Blueprint $t) {
            $t->string('equipement_id'); $t->integer('stock_initial'); $t->integer('stock_actuel')->nullable(); $t->integer('threshold'); $t->softDeletes();
        });
        Schema::create('equipement_mouvement', function (Blueprint $t) {
            $t->string('equipement_id'); $t->string('type'); $t->integer('quantity'); $t->timestamps();
        });
        DB::table('equipement')->insert(['equipement_id' => 'e1', 'stock_initial' => 10, 'threshold' => 8]);
        DB::table('equipement_mouvement')->insert([
            ['equipement_id' => 'e1', 'type' => 'entry', 'quantity' => 10, 'created_at' => '2026-08-01'],
            ['equipement_id' => 'e1', 'type' => 'exit', 'quantity' => 3, 'created_at' => '2026-09-02'],
        ]);
        DB::enableQueryLog();
        $metrics = $this->report('equipements')[0]['metrics'];
        $queries = DB::getQueryLog();
        $this->assertEquals(7, $metrics['Unités en stock']);
        $this->assertSame(1, $metrics['Références au seuil d’alerte']);
        $this->assertSame(3.0, $metrics['Unités sorties']);
        $this->assertNull(DB::table('equipement')->value('stock_actuel'));
        foreach ($queries as $query) $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete)\b/i', $query['query']);
    }

    public function test_admissions_only_count_assignments_linked_to_the_selected_cohort(): void
    {
        Schema::create('demande', function (Blueprint $t) {
            $t->string('assignation_id')->nullable(); $t->dateTime('date_demande')->nullable(); $t->timestamps();
            $t->string('etat_dossier'); $t->string('campagne_id'); $t->string('niveau_etude_actuel_code'); $t->boolean('boursier');
        });
        $this->assignment('matching'); $this->assignment('unrelated');
        $this->assignment('pending', 'en_attente');
        $this->assignment('other', 'confirmee', '2026-09-01', 'r3');
        foreach (['matching', 'pending', 'other', null] as $assignment) {
            DB::table('demande')->insert(['assignation_id' => $assignment, 'date_demande' => '2026-09-01', 'etat_dossier' => 'soumis', 'campagne_id' => 'camp', 'niveau_etude_actuel_code' => 'L1', 'boursier' => false]);
        }
        $metrics = $this->report('campagnes')[0]['metrics'];
        $this->assertSame(2, $metrics['Dossiers déposés']);
        $this->assertSame(1, $metrics['Dossiers avec attribution confirmée']);
        $this->assertSame(50.0, $metrics['Demandes satisfaites (%)']);
    }

    public function test_missing_module_tables_are_reported_as_unavailable_not_zero(): void
    {
        $sections = $this->report('departs');
        $this->assertTrue($sections[0]['unavailable']);
        $this->assertSame([], $sections[0]['metrics']);
    }
}
