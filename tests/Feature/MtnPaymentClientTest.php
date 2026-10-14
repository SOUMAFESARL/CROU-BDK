<?php

namespace Tests\Feature;

use App\Payments\MtnCollectionClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MtnPaymentClientTest extends TestCase
{
    private const REFERENCE = 'c72025f5-5cd1-4630-99e4-8ba4722fad56';

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    private function fake(string $status = 'SUCCESSFUL', string $currency = 'XOF'): void
    {
        Http::fake([
            '*/collection/token/' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
            '*/collection/v1_0/requesttopay' => Http::response('', 202),
            '*/collection/v1_0/requesttopay/*' => Http::response([
                'status' => $status, 'amount' => '5000', 'currency' => $currency, 'externalId' => 'rent1',
            ]),
        ]);
    }

    public function test_production_request_is_pending_and_uses_ivory_coast_parameters(): void
    {
        $this->fake();
        $client = new MtnCollectionClient('user', 'key', 'subscription', 'mtnivorycoast');
        $result = $client->create(self::REFERENCE, 'rent1', 5000, '2250500000000');
        $this->assertSame('pending', $result['status']);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/token/')
            && $r->hasHeader('Authorization', 'Basic '.base64_encode('user:key')) && $r->body() === '');
        Http::assertSent(fn (Request $r) => $r->url() === 'https://proxy.momoapi.mtn.com/collection/v1_0/requesttopay'
            && $r->hasHeader('X-Target-Environment', 'mtnivorycoast')
            && $r->hasHeader('X-Reference-Id', self::REFERENCE)
            && $r->hasHeader('Ocp-Apim-Subscription-Key', 'subscription')
            && $r->hasHeader('Authorization', 'Bearer token')
            && $r['currency'] === 'XOF' && $r['amount'] === '5000'
            && $r['payer']['partyId'] === '2250500000000');
        $this->assertTrue($client->verify(self::REFERENCE)->matches('rent1', 5000));
        Http::assertSentCount(3); // token reused for verification
    }

    public function test_sandbox_is_distinct_and_cannot_confirm_an_xof_payment(): void
    {
        $this->fake('SUCCESSFUL', 'EUR');
        $client = new MtnCollectionClient('user', 'key', 'subscription');
        $client->create(self::REFERENCE, 'rent1', 5000, '46733123450');
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/requesttopay')
            && str_starts_with($r->url(), 'https://sandbox.momodeveloper.mtn.com/')
            && $r['currency'] === 'EUR' && $r->hasHeader('X-Target-Environment', 'sandbox'));
        $this->assertFalse($client->verify(self::REFERENCE)->matches('rent1', 5000));
    }

    public function test_pending_response_is_not_a_success(): void
    {
        $this->fake('PENDING');
        $result = (new MtnCollectionClient('user', 'key', 'subscription', 'mtnivorycoast'))->verify(self::REFERENCE);
        $this->assertSame('pending', $result->status);
        $this->assertFalse($result->matches('rent1', 5000));
    }

    public function test_failed_response_is_not_a_success(): void
    {
        $this->fake('FAILED');
        $result = (new MtnCollectionClient('user', 'key', 'subscription', 'mtnivorycoast'))->verify(self::REFERENCE);
        $this->assertSame('failed', $result->status);
        $this->assertFalse($result->matches('rent1', 5000));
    }

    public function test_invalid_reference_is_rejected_before_network_call(): void
    {
        try {
            (new MtnCollectionClient('user', 'key', 'subscription'))->create('invalid', 'rent1', 5000, '46733123450');
            $this->fail('Expected rejection.');
        } catch (\InvalidArgumentException $e) {
            Http::assertNothingSent();
        }
    }

    public function test_creation_failure_is_not_retried_and_does_not_expose_body(): void
    {
        Http::fake([
            '*/collection/token/' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
            '*/collection/v1_0/requesttopay' => Http::response('private-details', 500),
        ]);
        try {
            (new MtnCollectionClient('user', 'key', 'subscription'))->create(self::REFERENCE, 'rent1', 5000, '46733123450');
            $this->fail('Expected rejection.');
        } catch (\RuntimeException $e) {
            $this->assertStringNotContainsString('private-details', $e->getMessage());
        }
        Http::assertSentCount(2);
    }
}
