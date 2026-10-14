<?php

namespace App\Jobs;

use App\Models\OnlinePayment;
use App\Payments\OnlinePaymentService;
use Illuminate\Foundation\Bus\Dispatchable;

class VerifyOnlinePayment
{
    use Dispatchable;

    public function __construct(public string $paymentId) {}

    public function handle(OnlinePaymentService $service): void
    {
        $payment = OnlinePayment::find($this->paymentId);
        if ($payment) $service->refresh($payment);
    }
}
