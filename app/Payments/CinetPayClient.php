<?php

namespace App\Payments;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

final class CinetPayClient
{
    public function __construct(
        #[\SensitiveParameter] private readonly string $apiKey,
        private readonly string $siteId,
    ) {
        if ($apiKey === '' || $siteId === '') {
            throw new InvalidArgumentException('Clé API et site_id CinetPay requis.');
        }
    }

    public function create(string $reference, int $amount, string $notifyUrl, string $returnUrl, string $phone = ''): array
    {
        if ($amount <= 0 || $amount % 5 !== 0 || !preg_match('/^[a-zA-Z0-9]+$/D', $reference)) {
            throw new InvalidArgumentException('CinetPay exige un montant positif multiple de 5 et une référence alphanumérique.');
        }
        foreach ([$notifyUrl, $returnUrl] as $url) {
            if (!filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https') {
                throw new InvalidArgumentException('Les URL de notification et de retour doivent utiliser HTTPS.');
            }
        }
        $result = $this->request('', [
            'transaction_id' => $reference, 'amount' => $amount, 'currency' => 'XOF',
            'description' => 'Paiement de loyer', 'notify_url' => $notifyUrl,
            'return_url' => $returnUrl, 'channels' => 'MOBILE_MONEY', 'lang' => 'fr',
        ] + ($phone !== '' ? ['customer_phone_number' => $phone, 'customer_country' => 'CI', 'lock_phone_number' => true] : []));
        if (in_array((string) ($result['code'] ?? ''), ['608', '609', '613', '624', '1010'], true)) {
            throw new PaymentRequestRejected('Paramètres ou identifiants CinetPay refusés.');
        }
        $url = $result['data']['payment_url'] ?? null;
        if (($result['code'] ?? null) !== '201' || !is_string($url)
            || !filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https'
            || !in_array(parse_url($url, PHP_URL_HOST), ['checkout.cinetpay.com', 'secure.cinetpay.com'], true)) {
            throw new RuntimeException('Création du paiement CinetPay refusée ou réponse invalide.');
        }
        return ['id' => $reference, 'url' => $url];
    }

    public function verify(string $reference): VerifiedPayment
    {
        if ($reference === '') {
            throw new InvalidArgumentException('Référence CinetPay requise.');
        }
        $result = $this->request('/check', ['transaction_id' => $reference]);
        $data = $result['data'] ?? [];
        $status = match ($data['status'] ?? null) {
            'ACCEPTED' => ($result['code'] ?? null) === '00' ? 'succeeded' : 'pending',
            'REFUSED' => 'failed',
            'WAITING_FOR_CUSTOMER', 'PENDING' => 'pending',
            default => throw new RuntimeException('Statut CinetPay inconnu : vérification à reprendre.'),
        };
        // The reference is bound to the authenticated verification request, not webhook input.
        return new VerifiedPayment($status, $reference, (string) ($data['amount'] ?? ''), (string) ($data['currency'] ?? ''));
    }

    private function request(string $path, array $data): array
    {
        $response = Http::acceptJson()->asJson()->withUserAgent('CROU-Payments/1.0')
            ->timeout(15)->connectTimeout(5)->withOptions(['allow_redirects' => false])
            ->post('https://api-checkout.cinetpay.com/v2/payment'.$path,
                $data + ['apikey' => $this->apiKey, 'site_id' => $this->siteId]);
        if ($path === '' && in_array($response->status(), [400, 401, 403, 404, 422], true)) {
            throw new PaymentRequestRejected('Demande CinetPay refusée.');
        }
        if (!$response->successful() || !is_array($response->json())) {
            throw new RuntimeException('CinetPay indisponible ou requête refusée (HTTP '.$response->status().').');
        }
        return $response->json();
    }
}
