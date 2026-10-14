<?php

namespace App\Payments;

use App\Models\OnlinePayment;
use App\Models\PaymentProvider;
use Illuminate\Validation\ValidationException;

class PaymentGateway
{
    public const SUPPORTED = ['wave', 'mtn', 'cinetpay'];

    public function credentials(PaymentProvider $provider): array
    {
        if (!$provider->is_active || !in_array($provider->code, self::SUPPORTED, true)) {
            throw ValidationException::withMessages(['provider' => 'Ce moyen de paiement n’est pas disponible.']);
        }
        if ($provider->code === 'mtn' && $provider->environment !== 'mtnivorycoast') {
            throw ValidationException::withMessages(['provider' => 'Le réseau MTN doit être configuré pour les paiements réels en Côte d’Ivoire.']);
        }
        $fields = match ($provider->code) {
            'mtn' => ['api_key', 'api_user', 'subscription_key', 'environment'],
            'cinetpay' => ['api_key', 'merchant_key'],
            'wave' => ['api_key', 'request_signing_secret', 'webhook_secret'],
        };
        $credentials = ['code' => $provider->code];
        foreach ($fields as $field) {
            $credentials[$field] = $provider->{$field};
            if (blank($credentials[$field]) && $field !== 'request_signing_secret') {
                throw ValidationException::withMessages(['provider' => 'Les identifiants de ce réseau sont incomplets.']);
            }
        }
        return $credentials;
    }

    public function create(OnlinePayment $payment, string $phone, string $callbackUrl, string $returnUrl): array
    {
        $client = $this->client($payment);
        return match ($payment->provider_code) {
            'wave' => $client->create($payment->reference, $payment->amount, $returnUrl, $returnUrl, $phone),
            'cinetpay' => $client->create($payment->reference, $payment->amount, $callbackUrl, $returnUrl, $phone),
            'mtn' => $client->create($payment->id, $payment->reference, $payment->amount, $phone, $callbackUrl),
        };
    }

    public function verify(OnlinePayment $payment): VerifiedPayment
    {
        $client = $this->client($payment);
        if ($payment->provider_code === 'wave' && !$payment->provider_reference) {
            $session = $client->findByReference($payment->reference);
            if ($session === null) {
                throw new \RuntimeException('Session Wave pas encore retrouvée.');
            }
            $payment->update(['provider_reference' => $session['id'], 'checkout_url' => $session['url']]);
        }
        return $client->verify($payment->provider_reference ?: ($payment->provider_code === 'mtn' ? $payment->id : $payment->reference));
    }

    private function client(OnlinePayment $payment): WaveClient|CinetPayClient|MtnCollectionClient
    {
        $c = $payment->credentials;
        return match ($payment->provider_code) {
            'wave' => new WaveClient($c['api_key'], $c['request_signing_secret'] ?? null),
            'cinetpay' => new CinetPayClient($c['api_key'], $c['merchant_key']),
            'mtn' => new MtnCollectionClient($c['api_user'], $c['api_key'], $c['subscription_key'], $c['environment']),
            default => throw new \RuntimeException('Connecteur indisponible.'),
        };
    }
}
