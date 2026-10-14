<?php

namespace Tests\Feature;

use App\Payments\CinetPayClient;
use App\Payments\VerifiedPayment;
use App\Payments\WaveClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentClientsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    public function test_wave_creation_uses_bearer_and_exact_body_signature(): void
    {
        Http::fake(['api.wave.com/*' => Http::response(['id' => 'cos-test', 'wave_launch_url' => 'https://pay.wave.com/c/cos-test'])]);
        $result = (new WaveClient('key', 'signing'))->create('order1', 5000, 'https://example.test/ok', 'https://example.test/error');
        $this->assertSame('cos-test', $result['id']);
        Http::assertSent(function (Request $request) {
            $signature = $request->header('Wave-Signature')[0];
            preg_match('/^t=(\d+),v1=([a-f0-9]+)$/', $signature, $parts);
            return $request->hasHeader('Authorization', 'Bearer key')
                && $request['amount'] === '5000' && $request['currency'] === 'XOF'
                && $request['client_reference'] === 'order1'
                && hash_equals(hash_hmac('sha256', $parts[1].$request->body(), 'signing'), $parts[2]);
        });
    }

    public function test_wave_verification_checks_reference_amount_and_currency(): void
    {
        Http::fake(['api.wave.com/*' => Http::response([
            'id' => 'cos-test', 'client_reference' => 'order1', 'amount' => '5000',
            'currency' => 'XOF', 'payment_status' => 'succeeded', 'checkout_status' => 'complete',
        ])]);
        $verified = (new WaveClient('key'))->verify('cos-test');
        $this->assertTrue($verified->matches('order1', 5000));
        $this->assertFalse($verified->matches('other', 5000));
        $this->assertFalse($verified->matches('order1', 100));
        $this->assertFalse($verified->matches('order1', 5000, 'EUR'));
    }

    public function test_cinetpay_creation_uses_site_id_and_mobile_money(): void
    {
        Http::fake(['api-checkout.cinetpay.com/*' => Http::response([
            'code' => '201', 'data' => ['payment_url' => 'https://checkout.cinetpay.com/payment/test'],
        ])]);
        $result = (new CinetPayClient('key', 'site'))->create('order1', 5000, 'https://example.test/notify', 'https://example.test/return');
        $this->assertSame('order1', $result['id']);
        Http::assertSent(fn (Request $r) => $r['site_id'] === 'site' && $r['apikey'] === 'key'
            && $r['channels'] === 'MOBILE_MONEY' && $r['amount'] === 5000 && $r['transaction_id'] === 'order1');
    }

    public function test_cinetpay_waiting_status_never_validates_payment(): void
    {
        Http::fake(['api-checkout.cinetpay.com/*' => Http::response([
            'code' => '201', 'data' => ['status' => 'WAITING_FOR_CUSTOMER', 'amount' => '5000', 'currency' => 'XOF'],
        ])]);
        $verified = (new CinetPayClient('key', 'site'))->verify('order1');
        $this->assertSame('pending', $verified->status);
        $this->assertFalse($verified->matches('order1', 5000));
    }

    public function test_cinetpay_requires_authenticated_accepted_response(): void
    {
        Http::fake(['api-checkout.cinetpay.com/*' => Http::response([
            'code' => '00', 'data' => ['status' => 'ACCEPTED', 'amount' => '5000.00', 'currency' => 'XOF'],
        ])]);
        $verified = (new CinetPayClient('key', 'site'))->verify('order1');
        $this->assertTrue($verified->matches('order1', 5000));
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/check') && $r['transaction_id'] === 'order1');
    }

    public function test_amount_checks_reject_fractional_malformed_or_missing_amounts(): void
    {
        foreach (['', '5000garbage', '5000.01', '5e3', '-5000'] as $amount) {
            $this->assertFalse((new VerifiedPayment('succeeded', 'order1', $amount, 'XOF'))->matches('order1', 5000));
        }
    }

    public function test_cinetpay_rejects_non_multiple_of_five_without_network_call(): void
    {
        try {
            (new CinetPayClient('key', 'site'))->create('order1', 5001, 'https://example.test/notify', 'https://example.test/return');
            $this->fail('Expected invalid amount rejection.');
        } catch (\InvalidArgumentException $e) {
            Http::assertNothingSent();
        }
    }

    public function test_provider_http_failure_does_not_expose_response_body(): void
    {
        Http::fake(['api.wave.com/*' => Http::response('sensitive-provider-detail', 401)]);
        try {
            (new WaveClient('key'))->verify('cos-test');
            $this->fail('Expected failure.');
        } catch (\RuntimeException $e) {
            $this->assertStringNotContainsString('sensitive-provider-detail', $e->getMessage());
        }
        Http::assertSentCount(1);
    }
}
