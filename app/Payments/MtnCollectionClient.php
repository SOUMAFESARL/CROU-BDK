<?php

namespace App\Payments;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

final class MtnCollectionClient
{
    private ?string $token = null;
    private int $tokenExpiresAt = 0;

    public function __construct(
        private readonly string $apiUser,
        #[\SensitiveParameter] private readonly string $apiKey,
        #[\SensitiveParameter] private readonly string $subscriptionKey,
        private readonly string $environment = 'sandbox',
    ) {
        if ($apiUser === '' || $apiKey === '' || $subscriptionKey === ''
            || !in_array($environment, ['sandbox', 'mtnivorycoast'], true)) {
            throw new InvalidArgumentException('Identifiants MTN Collection ou environnement invalides.');
        }
    }

    public function currency(): string
    {
        return $this->environment === 'sandbox' ? 'EUR' : 'XOF';
    }

    public function create(string $referenceId, string $externalId, int $amount, string $phone, ?string $callbackUrl = null): array
    {
        $this->validateReference($referenceId);
        if ($amount <= 0 || $externalId === '' || strlen($externalId) > 100
            || !preg_match('/^[0-9]{8,15}$/D', $phone)
            || ($this->environment === 'mtnivorycoast' && !preg_match('/^225[0-9]{10}$/D', $phone))) {
            throw new InvalidArgumentException('Montant, référence ou numéro MTN invalide.');
        }
        // The UUID must be persisted by the caller BEFORE sending this request.
        // A 202 response acknowledges receipt; it never confirms a payment.
        $headers = ['X-Reference-Id' => $referenceId];
        if ($callbackUrl !== null) {
            if (!filter_var($callbackUrl, FILTER_VALIDATE_URL) || parse_url($callbackUrl, PHP_URL_SCHEME) !== 'https') {
                throw new InvalidArgumentException('URL de notification MTN invalide.');
            }
            $headers['X-Callback-Url'] = $callbackUrl;
        }
        $response = $this->authenticated()->withHeaders($headers)
            ->post($this->baseUrl().'/collection/v1_0/requesttopay', [
                'amount' => (string) $amount, 'currency' => $this->currency(),
                'externalId' => $externalId,
                'payer' => ['partyIdType' => 'MSISDN', 'partyId' => $phone],
                'payerMessage' => 'Paiement de loyer', 'payeeNote' => 'Paiement de loyer',
            ]);
        if (in_array($response->status(), [400, 401, 403, 404, 422], true)) {
            throw new PaymentRequestRejected('Demande MTN refusée.');
        }
        if ($response->status() !== 202) {
            throw new RuntimeException('Demande MTN non confirmée (HTTP '.$response->status().'). Vérifier la référence avant toute nouvelle tentative.');
        }
        return ['id' => $referenceId, 'status' => 'pending'];
    }

    public function verify(string $referenceId): VerifiedPayment
    {
        $this->validateReference($referenceId);
        $response = $this->authenticated()->get($this->baseUrl().'/collection/v1_0/requesttopay/'.$referenceId);
        if (!$response->successful() || !is_array($response->json())) {
            throw new RuntimeException('Vérification MTN indisponible (HTTP '.$response->status().').');
        }
        $data = $response->json();
        $status = match ($data['status'] ?? null) {
            'SUCCESSFUL' => 'succeeded',
            'FAILED' => 'failed',
            'PENDING' => 'pending',
            default => throw new RuntimeException('Statut MTN inconnu : reprendre la vérification.'),
        };
        return new VerifiedPayment($status, (string) ($data['externalId'] ?? ''),
            (string) ($data['amount'] ?? ''), (string) ($data['currency'] ?? ''));
    }

    private function authenticated(): PendingRequest
    {
        if ($this->token === null || time() >= $this->tokenExpiresAt) {
            $response = $this->http()->withBasicAuth($this->apiUser, $this->apiKey)
                ->send('POST', $this->baseUrl().'/collection/token/');
            $token = $response->json('access_token');
            $expires = $response->json('expires_in');
            if (!$response->successful() || !is_string($token) || $token === ''
                || !is_numeric($expires) || (int) $expires <= 0) {
                throw new RuntimeException('Authentification MTN refusée ou réponse invalide.');
            }
            $this->token = $token;
            $this->tokenExpiresAt = time() + max(0, (int) $expires - 30);
        }
        return $this->http()->withToken($this->token)
            ->withHeaders(['X-Target-Environment' => $this->environment]);
    }

    private function http(): PendingRequest
    {
        return Http::acceptJson()->asJson()->timeout(15)->connectTimeout(5)
            ->withOptions(['allow_redirects' => false])
            ->withHeaders(['Ocp-Apim-Subscription-Key' => $this->subscriptionKey]);
    }

    private function baseUrl(): string
    {
        return $this->environment === 'sandbox'
            ? 'https://sandbox.momodeveloper.mtn.com' : 'https://proxy.momoapi.mtn.com';
    }

    private function validateReference(string $reference): void
    {
        if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/iD', $reference)) {
            throw new InvalidArgumentException('La référence MTN doit être un UUID v4.');
        }
    }
}
