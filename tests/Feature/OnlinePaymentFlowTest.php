<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceSystemSecurity;
use App\Http\Middleware\LogUserActivity;
use App\Jobs\VerifyOnlinePayment;
use App\Models\Etudiant;
use App\Models\OnlinePayment;
use App\Models\PaymentProvider;
use App\Payments\OnlinePaymentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OnlinePaymentFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'app.url' => 'https://residence.test']);
        DB::purge('sqlite');
        $this->withoutMiddleware([EnforceSystemSecurity::class, LogUserActivity::class]);
        Http::preventStrayRequests();
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
        DB::table('chambre')->insert(['id_chambre' => 'room-1', 'numero_chambre' => 'A-101']);
        DB::table('assignation_chambres')->insert(['id_assignation' => 'assignment-1',
            'etudiant_id' => 'student-1', 'chambre_id' => 'room-1', 'statut' => 'confirmee',
            'date_assignation' => now()->subMonth(), 'created_at' => now()->subMonth()]);
        Schema::create('loyers', function (Blueprint $t) {
            $t->string('id_loyer')->primary();
            foreach (['etudiant_id', 'chambre_id', 'cite_id', 'assignation_id', 'statut'] as $f) $t->string($f)->nullable();
            foreach (['montant_total', 'total_paye', 'solde_restant', 'total_penalites'] as $f) $t->decimal($f, 10, 2)->default(0);
            $t->integer('mois_facturation'); $t->integer('annee_facturation');
            $t->timestamp('date_paiement')->nullable(); $t->date('date_dernier_paiement')->nullable(); $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('paiements', function (Blueprint $t) {
            $t->string('id_paiement')->primary();
            foreach (['numero_paiement', 'resident_id', 'loyer_id', 'contrat_id', 'chambre_id', 'cite_id', 'type_paiement', 'mois', 'devise', 'methode_paiement', 'reference_transaction', 'transaction_id', 'statut_paiement', 'notes'] as $f) $t->string($f)->nullable();
            $t->decimal('montant', 15, 2); $t->decimal('montant_du', 15, 2)->nullable();
            $t->timestamp('date_paiement')->nullable(); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('paiement_repartitions', function (Blueprint $t) {
            $t->string('id_repartition')->primary();
            foreach (['paiement_id', 'type', 'mois', 'annee', 'description'] as $f) $t->string($f)->nullable();
            $t->decimal('montant', 15, 2); $t->timestamps();
        });
        DB::table('loyers')->insert(['id_loyer' => 'rent-1', 'etudiant_id' => 'student-1',
            'chambre_id' => 'room-1', 'cite_id' => 'cite-1', 'statut' => 'actif',
            'montant_total' => 10000, 'total_paye' => 2000, 'solde_restant' => 8000,
            'mois_facturation' => 9, 'annee_facturation' => 2026]);
        $this->actingAs(new Etudiant(['id_etudiant' => 'student-1']), 'etudiant');
        PaymentProvider::where('code', 'wave')->firstOrFail()->update([
            'is_active' => true, 'api_key' => 'wave-secret', 'webhook_secret' => 'hook-secret']);
    }

    private function startWave(): OnlinePayment
    {
        Http::fake(['https://api.wave.com/v1/checkout/sessions' => Http::response([
            'id' => 'cos-test', 'wave_launch_url' => 'https://pay.wave.com/c/cos-test'])]);
        $this->post(route('etudiants.online-payments.store'), [
            'provider' => 'wave', 'loyer_id' => 'rent-1', 'amount' => 8000, 'phone' => '2250500000000', 'student_id' => 'other',
        ])->assertRedirect();
        return OnlinePayment::sole();
    }

    private function confirmWave(OnlinePayment $payment, string $amount = '8000', string $currency = 'XOF'): OnlinePayment
    {
        Http::fake(['https://api.wave.com/v1/checkout/sessions/cos-test' => Http::response([
            'id' => 'cos-test', 'payment_status' => 'succeeded', 'checkout_status' => 'complete',
            'client_reference' => $payment->reference, 'amount' => $amount, 'currency' => $currency,
        ])]);
        $payment->update(['next_check_at' => now()->subMinute()]);
        return app(OnlinePaymentService::class)->refresh($payment);
    }

    public function test_student_payment_uses_server_balance_and_posts_one_receipt_only(): void
    {
        $payment = $this->startWave();
        $this->assertSame(8000, $payment->amount);
        $this->assertSame('student-1', $payment->student_id);
        $this->assertSame('pending', $payment->status);
        $this->assertArrayNotHasKey('credentials', $payment->toArray());
        $this->assertStringNotContainsString('wave-secret', $payment->getRawOriginal('credentials'));
        $this->assertSame(0, DB::table('paiements')->count());
        $this->get(route('etudiants.online-payments.show', $payment))->assertOk()->assertDontSee('wave-secret');
        $this->assertSame(0, DB::table('paiements')->count());
        $this->post(route('etudiants.online-payments.store'), ['provider' => 'wave', 'loyer_id' => 'rent-1'])->assertRedirect();
        $this->assertSame(1, OnlinePayment::count());
        Http::assertSentCount(1);
        PaymentProvider::where('code', 'wave')->update(['is_active' => false]);
        $payment = $this->confirmWave($payment);
        $this->assertSame('succeeded', $payment->status, $payment->last_error ?? '');
        app(OnlinePaymentService::class)->refresh($payment);
        $this->assertSame(1, DB::table('paiements')->count());
        $this->assertSame(1, DB::table('paiement_repartitions')->count());
        $this->assertEquals(10000, DB::table('loyers')->value('total_paye'));
        $this->assertEquals(0, DB::table('loyers')->value('solde_restant'));
        $this->assertSame('valide', DB::table('paiements')->value('statut_paiement'));
        $this->assertSame($payment->reference, DB::table('paiements')->value('reference_transaction'));
    }

    public function test_cinetpay_notification_triggers_authenticated_verification_and_accounting(): void
    {
        Bus::fake([VerifyOnlinePayment::class]);
        PaymentProvider::where('code', 'cinetpay')->firstOrFail()->update([
            'is_active' => true, 'api_key' => 'cinet-secret', 'merchant_key' => 'site-123']);
        $callback = '';
        Http::fake(['https://api-checkout.cinetpay.com/v2/payment' => function ($request) use (&$callback) {
            $callback = $request['notify_url'];
            return Http::response(['code' => '201', 'data' => ['payment_url' => 'https://checkout.cinetpay.com/payment/test']]);
        }]);
        $payment = app(OnlinePaymentService::class)->start('student-1', 'rent-1', 'cinetpay');
        $this->assertSame('pending', $payment->status);
        $path = parse_url($callback, PHP_URL_PATH);
        $this->post($path.'wrong', ['cpm_trans_id' => $payment->reference])->assertForbidden();
        $this->post($path, ['cpm_trans_id' => 'wrong'])->assertUnprocessable();
        $this->post($path, ['cpm_trans_id' => $payment->reference, 'cpm_site_id' => 'site-123'])->assertOk();
        Bus::assertDispatchedAfterResponse(VerifyOnlinePayment::class);
        Http::fake(['https://api-checkout.cinetpay.com/v2/payment/check' => Http::response([
            'code' => '00', 'data' => ['status' => 'ACCEPTED', 'amount' => '8000', 'currency' => 'XOF']])]);
        $this->assertSame('succeeded', app(OnlinePaymentService::class)->refresh($payment->fresh())->status);
        $this->assertSame(1, DB::table('paiements')->count());
    }

    public function test_mtn_202_is_pending_until_api_confirms_payment(): void
    {
        PaymentProvider::where('code', 'mtn')->firstOrFail()->update([
            'is_active' => true, 'api_key' => 'key', 'api_user' => 'user',
            'subscription_key' => 'subscription', 'environment' => 'mtnivorycoast']);
        Http::fake([
            'https://proxy.momoapi.mtn.com/collection/token/' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
            'https://proxy.momoapi.mtn.com/collection/v1_0/requesttopay' => Http::response('', 202),
        ]);
        $payment = app(OnlinePaymentService::class)->start('student-1', 'rent-1', 'mtn', '2250500000000');
        $this->assertSame('pending', $payment->status);
        $this->assertSame(0, DB::table('paiements')->count());
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/requesttopay')
            && $r->hasHeader('X-Callback-Url') && $r['currency'] === 'XOF' && $r['amount'] === '8000');
        Http::fake([
            'https://proxy.momoapi.mtn.com/collection/token/' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
            'https://proxy.momoapi.mtn.com/collection/v1_0/requesttopay/'.$payment->id => Http::response([
                'status' => 'SUCCESSFUL', 'externalId' => $payment->reference, 'amount' => '8000', 'currency' => 'XOF']),
        ]);
        $payment->update(['next_check_at' => now()->subMinute()]);
        $this->assertSame('succeeded', app(OnlinePaymentService::class)->refresh($payment)->status);
        $this->assertSame('mtn_money', DB::table('paiements')->value('methode_paiement'));
    }

    public function test_payment_creation_requires_login_and_csrf_token(): void
    {
        $this->app->instance('env', 'production');
        $this->postJson(route('etudiants.online-payments.store'), [
            'provider' => 'wave', 'loyer_id' => 'rent-1',
        ])->assertStatus(419);
        $this->app->instance('env', 'testing');
        auth('etudiant')->logout();
        $this->get(route('etudiants.online-payments.index'))->assertRedirect();
        Http::assertNothingSent();
    }

    public function test_accounting_failure_rolls_back_the_receipt_and_balance(): void
    {
        $payment = $this->startWave();
        Schema::drop('paiement_repartitions');
        $payment = $this->confirmWave($payment);
        $this->assertSame('pending', $payment->status);
        $this->assertSame('verification_unavailable', $payment->last_error);
        $this->assertSame(0, DB::table('paiements')->count());
        $this->assertEquals(8000, DB::table('loyers')->value('solde_restant'));
    }

    public function test_form_automatically_displays_own_room_and_remaining_rent(): void
    {
        DB::table('loyers')->insert(['id_loyer' => 'other-rent', 'etudiant_id' => 'student-2',
            'chambre_id' => 'room-1', 'statut' => 'actif', 'montant_total' => 99999,
            'solde_restant' => 99999, 'mois_facturation' => 1, 'annee_facturation' => 2025]);
        $this->get(route('etudiants.online-payments.index', ['provider' => 'wave']))->assertOk()
            ->assertSee('A-101')->assertSee('8 000')->assertSee('10 000')->assertSee('2 000')
            ->assertDontSee('99 999')->assertDontSee('Choisir un loyer')
            ->assertSee('value="rent-1"', false)->assertSee('name="amount"', false)
            ->assertSee('name="phone"', false)->assertSee('Payer avec Wave');
        Http::assertNothingSent();
    }

    public function test_partial_payment_redirects_to_operator_and_updates_only_the_paid_amount(): void
    {
        Http::fake(['https://api.wave.com/v1/checkout/sessions' => Http::response([
            'id' => 'cos-test', 'wave_launch_url' => 'https://pay.wave.com/c/cos-test'])]);
        $this->post(route('etudiants.online-payments.store'), [
            'provider' => 'wave', 'loyer_id' => 'rent-1', 'amount' => 3000, 'phone' => '2250500000000',
        ])->assertRedirect('https://pay.wave.com/c/cos-test');
        $payment = OnlinePayment::sole();
        $this->assertSame(3000, $payment->amount);
        Http::assertSent(fn ($request) => $request['amount'] === '3000' && $request['restrict_payer_mobile'] === '+2250500000000');
        $this->assertSame('succeeded', $this->confirmWave($payment, '3000')->status);
        $this->assertEquals(5000, DB::table('loyers')->value('solde_restant'));
        $this->assertEquals(5000, DB::table('loyers')->value('total_paye'));
        $this->assertEquals(3000, DB::table('paiements')->value('montant'));
    }

    public function test_invalid_amount_or_phone_never_starts_a_payment(): void
    {
        foreach ([0, -1, 8001, '1.5', 'abc'] as $amount) {
            $this->post(route('etudiants.online-payments.store'), [
                'provider' => 'wave', 'loyer_id' => 'rent-1', 'amount' => $amount, 'phone' => '2250500000000',
            ])->assertSessionHasErrors('amount');
        }
        foreach (['', 'invalid'] as $phone) {
            $this->post(route('etudiants.online-payments.store'), [
                'provider' => 'wave', 'loyer_id' => 'rent-1', 'amount' => 3000, 'phone' => $phone,
            ])->assertSessionHasErrors('phone');
        }
        Http::assertNothingSent();
        $this->assertSame(0, OnlinePayment::count());
    }

    public function test_network_cards_select_a_form_without_starting_a_transaction(): void
    {
        $this->get(route('etudiants.online-payments.index'))->assertOk()
            ->assertSee('provider=wave')->assertDontSee('provider=mtn')
            ->assertDontSee('id="online-payment-form"', false);
        $this->get(route('etudiants.online-payments.index', ['provider' => 'wave']))->assertOk()
            ->assertSee('id="online-payment-form"', false);
        PaymentProvider::where('code', 'wave')->update(['is_active' => false]);
        $this->get(route('etudiants.online-payments.index', ['provider' => 'wave']))->assertOk()
            ->assertDontSee('id="online-payment-form"', false)->assertDontSee('provider=wave');
        Http::assertNothingSent();
    }

    public function test_pending_payment_cannot_be_replaced_with_a_different_amount(): void
    {
        $this->startWave();
        $this->post(route('etudiants.online-payments.store'), [
            'provider' => 'wave', 'loyer_id' => 'rent-1', 'amount' => 3000, 'phone' => '2250500000000',
        ])->assertSessionHasErrors('amount');
        Http::assertSentCount(1);
        $this->assertSame(1, OnlinePayment::count());
    }

    public function test_modal_loads_form_and_submits_without_internal_redirect(): void
    {
        $response = $this->getJson(route('etudiants.online-payments.index', ['provider' => 'wave']))->assertOk();
        $this->assertStringContainsString('name="amount"', $response->json('html'));
        $this->assertStringContainsString('name="phone"', $response->json('html'));
        $this->assertStringContainsString('A-101', $response->json('html'));
        Http::assertNothingSent();
        Http::fake(['https://api.wave.com/v1/checkout/sessions' => Http::response([
            'id' => 'cos-test', 'wave_launch_url' => 'https://pay.wave.com/c/cos-test'])]);
        $this->postJson(route('etudiants.online-payments.store'), [
            'provider' => 'wave', 'loyer_id' => 'rent-1', 'amount' => 3000, 'phone' => '2250500000000',
        ])->assertOk()->assertJsonPath('status', 'pending')
            ->assertJsonPath('checkout_url', 'https://pay.wave.com/c/cos-test')
            ->assertJsonStructure(['show_url']);
    }

    public function test_old_room_and_expired_assignment_cannot_be_paid(): void
    {
        DB::table('loyers')->where('id_loyer', 'rent-1')->update(['chambre_id' => 'previous-room']);
        $this->post(route('etudiants.online-payments.store'), [
            'provider' => 'wave', 'loyer_id' => 'rent-1',
        ])->assertSessionHasErrors('loyer_id');
        DB::table('loyers')->where('id_loyer', 'rent-1')->update(['chambre_id' => 'room-1']);
        DB::table('assignation_chambres')->update(['date_fin' => now()->subDay()]);
        $this->post(route('etudiants.online-payments.store'), [
            'provider' => 'wave', 'loyer_id' => 'rent-1',
        ])->assertSessionHasErrors('loyer_id');
        Http::assertNothingSent();
        $this->assertSame(0, OnlinePayment::count());
    }

    public function test_assignment_change_after_display_rejects_old_rent(): void
    {
        DB::table('loyers')->where('id_loyer', 'rent-1')->update(['assignation_id' => 'old-assignment']);
        $this->post(route('etudiants.online-payments.store'), [
            'provider' => 'wave', 'loyer_id' => 'rent-1',
        ])->assertSessionHasErrors('loyer_id');
        Http::assertNothingSent();
    }

    public function test_wrong_amount_is_never_accounted(): void
    {
        $payment = $this->confirmWave($this->startWave(), '7999');
        $this->assertSame('review', $payment->status);
        $this->assertSame(0, DB::table('paiements')->count());
        $this->assertEquals(8000, DB::table('loyers')->value('solde_restant'));
    }

    public function test_wrong_currency_is_never_accounted(): void
    {
        $this->assertSame('review', $this->confirmWave($this->startWave(), '8000', 'EUR')->status);
        $this->assertSame(0, DB::table('paiements')->count());
    }

    public function test_another_student_cannot_read_verify_or_pay_this_rent(): void
    {
        $payment = $this->startWave();
        $this->actingAs(new Etudiant(['id_etudiant' => 'student-2']), 'etudiant');
        $this->get(route('etudiants.online-payments.show', $payment))->assertNotFound();
        $this->postJson(route('etudiants.online-payments.check', $payment))->assertNotFound();
        $this->post(route('etudiants.online-payments.store'), ['provider' => 'wave', 'loyer_id' => 'rent-1'])->assertNotFound();
        Http::assertSentCount(1);
    }

    public function test_disabled_unsupported_and_sandbox_networks_cannot_charge_students(): void
    {
        PaymentProvider::where('code', 'wave')->update(['is_active' => false]);
        PaymentProvider::where('code', 'mtn')->firstOrFail()->update([
            'is_active' => true, 'api_key' => 'key', 'api_user' => 'user', 'subscription_key' => 'subscription', 'environment' => 'sandbox']);
        foreach (['wave', 'moov', 'orange', 'tresorpay', 'mtn'] as $code) {
            $this->post(route('etudiants.online-payments.store'), ['provider' => $code, 'loyer_id' => 'rent-1'])->assertSessionHasErrors('provider');
        }
        Http::assertNothingSent();
        $this->assertCount(0, PaymentProvider::payableToStudents());
        $this->get(route('etudiants.online-payments.index'))->assertOk()->assertSee('Aucun moyen');
    }

    public function test_wave_webhook_requires_signature_and_does_not_trust_its_amount(): void
    {
        Bus::fake([VerifyOnlinePayment::class]);
        $payment = $this->startWave();
        $body = json_encode(['type' => 'checkout.session.completed', 'data' => [
            'client_reference' => $payment->reference, 'amount' => '1', 'payment_status' => 'succeeded']]);
        $url = '/api/payments/notifications/wave';
        $this->call('POST', $url, [], [], [], ['CONTENT_TYPE' => 'application/json'], $body)->assertForbidden();
        $stamp = (string) time();
        $signature = 't='.$stamp.',v1='.hash_hmac('sha256', $stamp.$body, 'hook-secret');
        $this->call('POST', $url, [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_WAVE_SIGNATURE' => $signature], $body)->assertOk();
        Bus::assertDispatchedAfterResponse(VerifyOnlinePayment::class);
        $this->assertSame(0, DB::table('paiements')->count());
        $this->assertSame('succeeded', $this->confirmWave($payment)->status);
        $this->call('POST', $url, [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_WAVE_SIGNATURE' => $signature], $body)->assertOk();
        $this->assertSame(1, DB::table('paiements')->count());
    }

    public function test_uncertain_creation_is_not_retried_and_can_be_recovered(): void
    {
        Http::fake(['https://api.wave.com/v1/checkout/sessions' => Http::response([], 503)]);
        $service = app(OnlinePaymentService::class);
        $payment = $service->start('student-1', 'rent-1', 'wave');
        $this->assertSame('pending', $payment->status);
        $this->assertSame('creation_uncertain', $payment->last_error);
        $this->assertSame($payment->id, $service->start('student-1', 'rent-1', 'wave')->id);
        Http::assertSentCount(1);
        Http::fake([
            'https://api.wave.com/v1/checkout/sessions/search*' => Http::response(['result' => [[
                'id' => 'cos-test', 'client_reference' => $payment->reference, 'wave_launch_url' => 'https://pay.wave.com/c/cos-test']]]),
            'https://api.wave.com/v1/checkout/sessions/cos-test' => Http::response([
                'id' => 'cos-test', 'payment_status' => 'succeeded', 'checkout_status' => 'complete',
                'client_reference' => $payment->reference, 'amount' => '8000', 'currency' => 'XOF']),
        ]);
        $payment->update(['next_check_at' => now()->subMinute()]);
        $this->artisan('payments:reconcile')->assertSuccessful();
        $this->assertSame('succeeded', $payment->fresh()->status);
        $this->assertSame(1, DB::table('paiements')->count());
    }

    private function apiStudent(): void
    {
        Schema::create('etudiants', function (Blueprint $t) {
            $t->string('id_etudiant')->primary(); $t->integer('statut'); $t->softDeletes();
        });
        DB::table('etudiants')->insert(['id_etudiant' => 'student-1', 'statut' => 1]);
        (require database_path('migrations/2026_08_27_100000_create_student_api_tokens_table.php'))->up();
        \App\Models\StudentApiToken::create(['etudiant_id' => 'student-1', 'token_hash' => hash('sha256', 'api-test-token'), 'name' => 'test']);
        auth('etudiant')->logout();
        $this->withToken('api-test-token');
    }

    public function test_api_payment_network_activation_and_deactivation(): void
    {
        $this->apiStudent();
        $this->getJson('/api/V1/paiements-en-ligne/moyens')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'wave')->assertDontSee('wave-secret');
        $provider = PaymentProvider::where('code', 'wave')->firstOrFail();
        $provider->update(['is_active' => false]);
        $this->getJson('/api/V1/paiements-en-ligne/moyens')->assertOk()->assertJsonCount(0, 'data');
        $this->postJson('/api/V1/paiements-en-ligne', ['provider' => 'wave', 'loyer_id' => 'rent-1'])
            ->assertUnprocessable()->assertJsonValidationErrors('provider');
        Http::assertNothingSent();
        $this->assertSame(0, OnlinePayment::count());
        $provider->update(['is_active' => true]);
        $this->getJson('/api/V1/paiements-en-ligne/moyens')->assertJsonCount(1, 'data');
    }

    public function test_api_payment_uses_token_balance_and_can_be_verified_after_deactivation(): void
    {
        $this->apiStudent();
        Http::fake(['https://api.wave.com/v1/checkout/sessions' => Http::response([
            'id' => 'api-session', 'wave_launch_url' => 'https://pay.wave.com/c/api-session'])]);
        $this->postJson('/api/V1/paiements-en-ligne', [
            'provider' => 'wave', 'loyer_id' => 'rent-1', 'amount' => 1, 'student_id' => 'other',
        ])->assertOk()->assertJsonPath('data.amount', 8000)->assertJsonPath('data.status', 'pending')->assertDontSee('wave-secret');
        $payment = OnlinePayment::sole();
        $this->assertSame('student-1', $payment->student_id);
        $this->postJson('/api/V1/paiements-en-ligne', ['provider' => 'wave', 'loyer_id' => 'rent-1'])->assertOk();
        $this->assertSame(1, OnlinePayment::count());
        PaymentProvider::where('code', 'wave')->update(['is_active' => false]);
        Http::fake(['https://api.wave.com/v1/checkout/sessions/api-session' => Http::response([
            'id' => 'api-session', 'payment_status' => 'succeeded', 'checkout_status' => 'complete',
            'client_reference' => $payment->reference, 'amount' => '8000', 'currency' => 'XOF'])]);
        $payment->update(['next_check_at' => now()->subMinute()]);
        $this->postJson('/api/V1/paiements-en-ligne/'.$payment->id.'/verifier')
            ->assertOk()->assertJsonPath('data.status', 'succeeded');
        $this->getJson('/api/V1/paiement')->assertOk()->assertJsonPath('paiements.total', 1)
            ->assertJsonPath('stats.total_impayes', 0);
        $receipt = $this->getJson('/api/V1/paiements')->json('paiements.data.0.receipt_url');
        $this->get($receipt, ['Accept' => 'application/pdf'])->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $payment->update(['student_id' => 'other']);
        $this->getJson('/api/V1/paiements-en-ligne/'.$payment->id)->assertNotFound();
        $this->postJson('/api/V1/paiements-en-ligne/'.$payment->id.'/verifier')->assertNotFound();
    }

    public function test_api_payment_list_preserves_legacy_link_and_pagination(): void
    {
        $this->apiStudent();
        foreach (['one', 'two', 'foreign'] as $id) {
            DB::table('paiements')->insert(['id_paiement' => $id, 'resident_id' => $id === 'foreign' ? 'other' : 'student-1',
                'montant' => 1000, 'statut_paiement' => 'valide', 'created_at' => now()]);
        }
        $first = $this->getJson('/api/V1/paiement/student-1?per_page=1')->assertOk()
            ->assertJsonPath('paiements.total', 2)->assertJsonCount(1, 'paiements.data');
        $this->getJson($first->json('paiements.next_page_url'))->assertOk()->assertJsonPath('paiements.current_page', 2);
        $this->getJson('/api/V1/paiement/other')->assertNotFound();
        $this->getJson('/api/V1/paiement?per_page=0')->assertUnprocessable();
        $this->getJson('/api/V1/paiements')->assertOk()->assertDontSee('foreign');
    }
}
