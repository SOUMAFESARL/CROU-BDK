<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceSystemSecurity;
use App\Http\Middleware\LogUserActivity;
use App\Models\PaymentProvider;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentProviderSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->withoutMiddleware([EnforceSystemSecurity::class, LogUserActivity::class]);
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Role::create(['id' => 1, 'name' => 'Administrateur']);
        Role::create(['id' => 2, 'name' => 'Agent']);
        foreach ([
            '2026_08_11_000001_create_role_permissions_tables.php',
            '2026_08_11_000002_create_modules_table.php',
            '2026_09_19_000001_create_payment_providers_table.php',
            '2026_09_19_000002_add_payment_provider_permission.php',
            '2026_09_19_000003_add_specific_payment_credentials.php',
            '2026_09_19_000004_create_online_payments_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
    }

    private function admin(int $role = 1): void
    {
        $this->actingAs(new User(['id_user' => 'test-admin', 'role_id' => $role]), 'admin');
    }

    private function url(PaymentProvider $provider): string
    {
        return route('admin.parametrage.payment-providers.update', $provider);
    }

    private function data(): array
    {
        return ['api_url' => 'https://example.test/pay', 'api_key' => 'api-secret', 'merchant_key' => 'merchant-secret', 'is_active' => 1];
    }

    public function test_networks_start_disabled_and_secrets_are_encrypted_and_hidden(): void
    {
        $this->assertSame(6, PaymentProvider::count());
        $this->assertCount(0, PaymentProvider::visibleToStudents());
        $this->admin();
        $provider = PaymentProvider::first();
        $this->put($this->url($provider), $this->data())->assertRedirect();
        $provider->refresh();
        $this->assertTrue($provider->is_active);
        $this->assertSame('api-secret', $provider->api_key);
        $this->assertSame('merchant-secret', $provider->merchant_key);
        $this->assertNotSame('api-secret', $provider->getRawOriginal('api_key'));
        $this->assertNotSame('merchant-secret', $provider->getRawOriginal('merchant_key'));
        $this->assertArrayNotHasKey('api_key', $provider->toArray());
        $this->assertArrayNotHasKey('merchant_key', $provider->toArray());
        $this->assertSame([['code' => 'moov', 'name' => 'Moov']], PaymentProvider::visibleToStudents()->toArray());
    }

    public function test_blank_keys_preserve_credentials_and_disabling_hides_the_network(): void
    {
        $this->admin();
        $provider = PaymentProvider::first();
        $provider->update($this->data());
        $this->put($this->url($provider), ['api_url' => 'https://example.test/new', 'api_key' => '', 'merchant_key' => '', 'is_active' => 0])->assertRedirect();
        $provider->refresh();
        $this->assertSame('api-secret', $provider->api_key);
        $this->assertSame('merchant-secret', $provider->merchant_key);
        $this->assertFalse($provider->is_active);
        $this->assertCount(0, PaymentProvider::visibleToStudents());
        $this->put($this->url($provider), ['api_url' => $provider->api_url, 'is_active' => 1])->assertRedirect();
        $this->assertTrue($provider->fresh()->is_active);
    }

    public function test_incomplete_activation_is_rejected_without_flashing_secrets(): void
    {
        $this->admin();
        $provider = PaymentProvider::first();
        $this->from('/admin/parametrage/api-paiement')
            ->put($this->url($provider), ['is_active' => 1, 'api_key' => 'never-flash-this'])
            ->assertSessionHasErrors(['api_url', 'merchant_key'], null, 'provider_'.$provider->id)
            ->assertSessionMissing('_old_input.api_key')
            ->assertSessionMissing('_old_input.merchant_key');
        $this->assertFalse($provider->fresh()->is_active);
        $this->assertNull($provider->fresh()->api_key);
    }

    public function test_invalid_urls_are_rejected_and_drafts_can_be_saved(): void
    {
        $this->admin();
        $provider = PaymentProvider::first();
        $this->put($this->url($provider), array_replace($this->data(), ['api_url' => 'javascript:alert(1)']))
            ->assertSessionHasErrors(['api_url'], null, 'provider_'.$provider->id);
        $this->put($this->url($provider), ['is_active' => 0, 'api_url' => 'https://example.test'])
            ->assertSessionHasNoErrors();
        $this->assertSame('https://example.test', $provider->fresh()->api_url);
    }

    public function test_admin_permission_is_required_for_reading_and_writing(): void
    {
        $provider = PaymentProvider::first();
        $this->get(route('admin.parametrage.payment-providers.index'))->assertRedirect();
        $this->put($this->url($provider), $this->data())->assertRedirect();
        $this->admin(2);
        $this->get(route('admin.parametrage.payment-providers.index'))->assertForbidden();
        $this->put($this->url($provider), $this->data())->assertForbidden();
        $permission = DB::table('permissions')->where('slug', 'parametrage.payment-providers.configure')->value('id');
        Role::find(2)->permissions()->attach($permission);
        $this->admin(2);
        $this->put($this->url($provider), $this->data())->assertRedirect();
        $this->assertTrue($provider->fresh()->is_active);
    }

    public function test_wave_does_not_require_a_merchant_key(): void
    {
        $this->admin();
        $provider = PaymentProvider::where('code', 'wave')->firstOrFail();
        $this->put($this->url($provider), [
            'api_url' => 'https://api.wave.com', 'api_key' => 'wave-key',
            'request_signing_secret' => 'signing-key', 'webhook_secret' => 'webhook-key', 'is_active' => 1,
        ])->assertSessionHasNoErrors();
        $provider->refresh();
        $this->assertTrue($provider->is_active);
        $this->assertNull($provider->merchant_key);
        $this->assertSame('signing-key', $provider->request_signing_secret);
        $this->assertNotSame('signing-key', $provider->getRawOriginal('request_signing_secret'));
        $this->assertArrayNotHasKey('request_signing_secret', $provider->toArray());
    }

    public function test_mtn_requires_three_credentials_and_clears_them_when_switching_environment(): void
    {
        $this->admin();
        $provider = PaymentProvider::where('code', 'mtn')->firstOrFail();
        $this->put($this->url($provider), [
            'api_url' => 'https://sandbox.momodeveloper.mtn.com',
            'api_key' => 'test-key', 'environment' => 'sandbox', 'is_active' => 1,
        ])->assertSessionHasErrors(['api_user', 'subscription_key'], null, 'provider_'.$provider->id)
            ->assertSessionMissing('_old_input.api_key');
        $this->put($this->url($provider), [
            'api_url' => 'https://sandbox.momodeveloper.mtn.com', 'api_key' => 'test-key',
            'api_user' => 'test-user', 'subscription_key' => 'test-subscription',
            'environment' => 'sandbox', 'is_active' => 1,
        ])->assertSessionHasNoErrors();
        $provider->refresh();
        $this->assertTrue($provider->is_active);
        $this->assertArrayNotHasKey('subscription_key', $provider->toArray());
        $this->assertNotSame('test-subscription', $provider->getRawOriginal('subscription_key'));
        $this->put($this->url($provider), [
            'api_url' => 'https://proxy.momoapi.mtn.com', 'environment' => 'mtnivorycoast', 'is_active' => 1,
        ])->assertSessionHasErrors(['api_user', 'api_key', 'subscription_key'], null, 'provider_'.$provider->id);
        $this->put($this->url($provider), [
            'api_url' => 'https://proxy.momoapi.mtn.com', 'environment' => 'mtnivorycoast', 'is_active' => 0,
        ])->assertSessionHasNoErrors();
        $provider->refresh();
        $this->assertFalse($provider->is_active);
        $this->assertNull($provider->api_key);
        $this->assertNull($provider->api_user);
        $this->assertNull($provider->subscription_key);
    }

    public function test_student_display_only_contains_enabled_network_names(): void
    {
        $provider = PaymentProvider::first();
        $provider->update($this->data());
        $html = view('etudiant.partials.payment_providers', ['paymentProviders' => PaymentProvider::visibleToStudents()])->render();
        $this->assertStringContainsString('Moov', $html);
        foreach (['Orange', 'MTN', 'Wave', 'CinetPay', 'TrésorPay', 'api-secret', 'merchant-secret', 'example.test'] as $hidden) {
            $this->assertStringNotContainsString($hidden, $html);
        }
        $provider->update(['is_active' => false]);
        $html = view('etudiant.partials.payment_providers', ['paymentProviders' => PaymentProvider::visibleToStudents()])->render();
        $this->assertStringContainsString('Aucun réseau', $html);
        $this->assertStringNotContainsString('Moov', $html);
    }
}
