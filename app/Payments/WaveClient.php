<?php

namespace App\Payments;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

final class WaveClient
{
    public function __construct(
        #[\SensitiveParameter] private readonly string $apiKey,
        #[\SensitiveParameter] private readonly ?string $signingSecret = null,
    ) {
        if ($apiKey === '') {
            throw new InvalidArgumentException('Clé API Wave requise.');
        }
    }

    public function create(string $reference, int $amount, string $successUrl, string $errorUrl, string $phone = ''): array
    {
        if ($amount <= 0 || $reference === '' || strlen($reference) > 255) {
            throw new InvalidArgumentException('Montant ou référence Wave invalide.');
        }
        foreach ([$successUrl, $errorUrl] as $url) {
            if (!filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https') {
                throw new InvalidArgumentException('Les URL de retour doivent utiliser HTTPS.');
            }
        }
        $result = $this->request('POST', '/v1/checkout/sessions', [
            'amount' => (string) $amount, 'currency' => 'XOF',
            'client_reference' => $reference, 'success_url' => $successUrl, 'error_url' => $errorUrl,
        ] + ($phone !== '' ? ['restrict_payer_mobile' => '+'.ltrim($phone, '+')] : []));
        $id = $result['id'] ?? null;
        $url = $result['wave_launch_url'] ?? null;
        if (!is_string($id) || $id === '' || !is_string($url)
            || parse_url($url, PHP_URL_SCHEME) !== 'https' || parse_url($url, PHP_URL_HOST) !== 'pay.wave.com') {
            throw new RuntimeException('Réponse de création Wave invalide.');
        }
        return ['id' => $id, 'url' => $url];
    }

    public function findByReference(string $reference): ?array
    {
        $result = $this->request('GET', '/v1/checkout/sessions/search?client_reference='.rawurlencode($reference));
        $sessions = $result['result'] ?? null;
        if (!is_array($sessions) || count($sessions) > 1) {
            throw new RuntimeException('Recherche Wave ambiguë.');
        }
        if ($sessions === []) return null;
        $session = $sessions[0];
        $id = $session['id'] ?? null;
        $url = $session['wave_launch_url'] ?? null;
        if (($session['client_reference'] ?? null) !== $reference || !is_string($id) || $id === ''
            || !is_string($url) || parse_url($url, PHP_URL_SCHEME) !== 'https'
            || parse_url($url, PHP_URL_HOST) !== 'pay.wave.com') {
            throw new RuntimeException('Résultat Wave invalide.');
        }
        return ['id' => $id, 'url' => $url];
    }

    public function verify(string $sessionId): VerifiedPayment
    {
        $result = $this->request('GET', '/v1/checkout/sessions/'.rawurlencode($sessionId));
        if (($result['id'] ?? null) !== $sessionId) {
            throw new RuntimeException('La session Wave ne correspond pas.');
        }
        $status = match ($result['payment_status'] ?? null) {
            'succeeded' => ($result['checkout_status'] ?? null) === 'complete' ? 'succeeded' : 'pending',
            'cancelled' => 'failed',
            'processing' => ($result['checkout_status'] ?? null) === 'expired' ? 'failed' : 'pending',
            default => throw new RuntimeException('Statut Wave inconnu.'),
        };
        return new VerifiedPayment($status, (string) ($result['client_reference'] ?? ''),
            (string) ($result['amount'] ?? ''), (string) ($result['currency'] ?? ''));
    }

    private function request(string $method, string $path, array $data = []): array
    {
        $body = $method === 'GET' ? '' : json_encode($data, JSON_THROW_ON_ERROR);
        $request = Http::acceptJson()->withToken($this->apiKey)->timeout(15)->connectTimeout(5)
            ->withOptions(['allow_redirects' => false]);
        if ($this->signingSecret) {
            $timestamp = (string) time();
            $signature = hash_hmac('sha256', $timestamp.$body, $this->signingSecret);
            $request = $request->withHeaders(['Wave-Signature' => "t={$timestamp},v1={$signature}"]);
        }
        // Never automatically retry creation: a timeout can follow a successful debit/session creation.
        $response = $request->send($method, 'https://api.wave.com'.$path,
            $method === 'GET' ? [] : ['body' => $body, 'headers' => ['Content-Type' => 'application/json']]);
        if ($method === 'POST' && in_array($response->status(), [400, 401, 403, 404, 422], true)) {
            throw new PaymentRequestRejected('Demande Wave refusée.');
        }
        if (!$response->successful() || !is_array($response->json())) {
            // Do not include provider response bodies or credentials in exceptions/logs.
            throw new RuntimeException('Wave indisponible ou requête refusée (HTTP '.$response->status().').');
        }
        return $response->json();
    }
}
