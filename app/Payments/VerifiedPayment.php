<?php

namespace App\Payments;

final readonly class VerifiedPayment
{
    public function __construct(
        public string $status,
        public string $reference,
        public string $amount,
        public string $currency,
    ) {}

    public function matches(string $reference, int $amount, string $currency = 'XOF'): bool
    {
        // XOF has no fractional units. Accept equivalent API decimal formatting.
        return $this->status === 'succeeded'
            && hash_equals($reference, $this->reference)
            && $this->currency === $currency
            && preg_match('/^[0-9]+(?:\.0{1,2})?$/D', $this->amount) === 1
            && ltrim(explode('.', $this->amount)[0], '0') === (string) $amount;
    }
}
