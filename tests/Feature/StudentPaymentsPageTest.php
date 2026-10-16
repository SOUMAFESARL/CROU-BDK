<?php

namespace Tests\Feature;

use App\Models\Etudiant;
use App\Models\PaymentProvider;
use App\Services\EtudiantMenu;
use App\Services\EtudiantPaiements;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Page React « Mes paiements » de l'espace étudiant (Etudiant/MesPaiements). */
class StudentPaymentsPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $settings = new \App\Models\ConfigApp(['app_name' => 'Residence test']);
        $settings->setRelation('region', null)->setRelation('ville', null);
        $this->mock(\App\Repositories\Interfaces\ConfigAppRepositoryInterface::class)
            ->shouldReceive('getConfig')->andReturn($settings);
        foreach (['000001_create_payment_providers_table', '000003_add_specific_payment_credentials', '000004_create_online_payments_table'] as $migration) {
            (require database_path('migrations/2026_09_19_'.$migration.'.php'))->up();
        }
        Schema::create('assignation_chambres', function (Blueprint $t) {
            $t->string('id_assignation')->primary(); $t->string('etudiant_id'); $t->string('chambre_id');
            $t->string('statut'); $t->dateTime('date_assignation'); $t->dateTime('date_fin')->nullable(); $t->timestamps();
        });
        Schema::create('chambre', function (Blueprint $t) {
            $t->string('id_chambre')->primary(); $t->string('numero_chambre'); $t->softDeletes();
        });
        Schema::create('loyers', function (Blueprint $t) {
            $t->string('id_loyer')->primary();
            foreach (['etudiant_id', 'chambre_id', 'assignation_id', 'statut'] as $f) $t->string($f)->nullable();
            foreach (['montant_total', 'total_paye', 'solde_restant', 'total_penalites'] as $f) $t->decimal($f, 10, 2)->default(0);
            $t->integer('mois_facturation'); $t->integer('annee_facturation');
            $t->date('date_limit_paiement')->nullable(); $t->softDeletes(); $t->timestamps();
        });
        Schema::create('paiements', function (Blueprint $t) {
            $t->string('id_paiement')->primary();
            foreach (['numero_paiement', 'resident_id', 'mois', 'methode_paiement', 'reference_transaction', 'statut_paiement'] as $f) $t->string($f)->nullable();
            $t->decimal('montant', 15, 2); $t->timestamp('date_paiement')->nullable(); $t->timestamps(); $t->softDeletes();
        });

        DB::table('chambre')->insert(['id_chambre' => 'room', 'numero_chambre' => 'A-101']);
        DB::table('assignation_chambres')->insert(['id_assignation' => 'stay', 'etudiant_id' => 'student', 'chambre_id' => 'room',
            'statut' => 'confirmee', 'date_assignation' => now()->subMonth(), 'created_at' => now()->subMonth()]);
        DB::table('loyers')->insert([
            ['id_loyer' => 'sept', 'etudiant_id' => 'student', 'chambre_id' => 'room', 'statut' => 'actif', 'montant_total' => 10000,
                'total_paye' => 2000, 'solde_restant' => 8000, 'total_penalites' => 500, 'mois_facturation' => 9, 'annee_facturation' => 2026,
                'date_limit_paiement' => now()->subDays(10)->toDateString()],
            ['id_loyer' => 'oct', 'etudiant_id' => 'student', 'chambre_id' => 'room', 'statut' => 'actif', 'montant_total' => 10000,
                'total_paye' => 10000, 'solde_restant' => 0, 'total_penalites' => 0, 'mois_facturation' => 10, 'annee_facturation' => 2026,
                'date_limit_paiement' => null],
            ['id_loyer' => 'other', 'etudiant_id' => 'mate', 'chambre_id' => 'room', 'statut' => 'actif', 'montant_total' => 99999,
                'total_paye' => 0, 'solde_restant' => 99999, 'total_penalites' => 0, 'mois_facturation' => 9, 'annee_facturation' => 2026,
                'date_limit_paiement' => null],
        ]);
        DB::table('paiements')->insert([
            ['id_paiement' => 'paid', 'numero_paiement' => 'PAY-1', 'resident_id' => 'student', 'mois' => json_encode(['09-2026']),
                'methode_paiement' => 'mtn_money', 'statut_paiement' => 'valide', 'montant' => 2000, 'created_at' => now()->subDays(2)],
            ['id_paiement' => 'failed', 'numero_paiement' => 'PAY-2', 'resident_id' => 'student', 'mois' => null,
                'methode_paiement' => null, 'statut_paiement' => 'echoue', 'montant' => 1000, 'created_at' => now()->subDay()],
            ['id_paiement' => 'cancelled', 'numero_paiement' => 'PAY-3', 'resident_id' => 'student', 'mois' => null,
                'methode_paiement' => null, 'statut_paiement' => 'annule', 'montant' => 500, 'created_at' => now()],
        ]);
        PaymentProvider::where('code', 'wave')->firstOrFail()->update(['is_active' => true, 'api_key' => 'wave-secret', 'webhook_secret' => 'hook-secret']);
    }

    private function props(array $query = []): array
    {
        $etudiant = new Etudiant(['id_etudiant' => 'student', 'telephone1' => '+225 07 00 00 00 01']);

        return app(EtudiantPaiements::class)->props($etudiant, Request::create('/etudiant/dashboard', 'GET', ['section' => 'paiements'] + $query));
    }

    public function test_props_show_rent_situation_payable_rent_and_history(): void
    {
        $props = $this->props();

        $situation = $props['situation'];
        $this->assertSame([1, 1, 60], [$situation['nbImpayes'], $situation['nbRetards'], $situation['recouvrement']]);
        $this->assertSame(['libelle' => 'En retard', 'ton' => 'red'], $situation['etat']);
        $this->assertSame('Septembre 2026', $situation['prochaine']['periode']);
        $this->assertStringContainsString('8 000', $situation['reste']);

        // Le montant payable vient du solde serveur du plus ancien loyer de la chambre occupée.
        $reglement = $props['reglement'];
        $this->assertNull($reglement['indisponible']);
        $this->assertSame(['sept', 'A-101', 8000], [$reglement['loyer']['id'], $reglement['loyer']['chambre'], $reglement['loyer']['soldeValeur']]);
        $this->assertSame('0700000001', $reglement['telephone']);
        $this->assertSame([['code' => 'wave', 'nom' => 'Wave']], $reglement['reseaux']);

        $this->assertSame(['Octobre 2026', 'Payé'], [$props['loyers'][0]['periode'], $props['loyers'][0]['statut']['libelle']]);
        $this->assertSame(['Septembre 2026', 'En retard'], [$props['loyers'][1]['periode'], $props['loyers'][1]['statut']['libelle']]);
        $this->assertCount(2, $props['loyers']);

        // Les paiements annulés sont masqués ; seul un paiement validé a un reçu.
        $this->assertSame(['failed', 'paid'], array_column($props['paiements'], 'id'));
        $this->assertNull($props['paiements'][0]['recu']);
        $this->assertSame(route('etudiants.documents.receipt', 'paid'), $props['paiements'][1]['recu']);
        $this->assertSame(['Septembre 2026', 'MTN Money', 'Payé'], [$props['paiements'][1]['periode'], $props['paiements'][1]['methode'], $props['paiements'][1]['statut']['libelle']]);
        $this->assertSame(route('etudiants.online-payments.store'), $props['urls']['payer']);

        $this->assertStringNotContainsString('wave-secret', json_encode($props));
        $this->assertStringNotContainsString('hook-secret', json_encode($props));
    }

    public function test_history_filter_and_online_payment_follow_up(): void
    {
        \App\Models\OnlinePayment::create(['id' => 'op-1', 'reference' => 'op1', 'provider_id' => PaymentProvider::where('code', 'wave')->value('id'),
            'provider_code' => 'wave', 'student_id' => 'student', 'loyer_id' => 'sept', 'amount' => 8000, 'currency' => 'XOF',
            'status' => 'pending', 'credentials' => [], 'callback_token_hash' => str_repeat('0', 64)]);

        $props = $this->props(['statut' => 'echoue']);
        $this->assertSame('echoue', $props['statut']);
        $this->assertSame(['failed'], array_column($props['paiements'], 'id'));
        $this->assertNull($this->props(['statut' => 'annule'])['statut']);

        $this->assertSame([['Wave', 'info']], array_map(fn ($p) => [$p['reseau'], $p['statut']['ton']], $props['enLigne']));
        $this->assertSame(url('/etudiant/paiements-en-ligne/op-1'), $props['enLigne'][0]['url']);
    }

    public function test_nothing_is_payable_without_an_occupied_room(): void
    {
        DB::table('assignation_chambres')->update(['statut' => 'annulee']);

        $reglement = $this->props()['reglement'];

        $this->assertNull($reglement['loyer']);
        $this->assertStringContainsString('Aucune chambre occupée', $reglement['indisponible']);
    }

    public function test_payments_and_maintenance_open_as_react_pages_from_the_menu(): void
    {
        $this->assertContains('paiements', EtudiantMenu::SECTIONS_REACT);
        $this->assertContains('maintenance', EtudiantMenu::SECTIONS_REACT);
        $this->assertFileDoesNotExist(resource_path('views/etudiant/paiements.blade.php'));
        $this->assertFileDoesNotExist(resource_path('views/etudiant/maintenance.blade.php'));
    }
}
