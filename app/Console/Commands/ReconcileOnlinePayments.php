<?php

namespace App\Console\Commands;

use App\Models\OnlinePayment;
use App\Payments\OnlinePaymentService;
use Illuminate\Console\Command;

class ReconcileOnlinePayments extends Command
{
    protected $signature = 'payments:reconcile {--limit=50}';
    protected $description = 'Vérifie les paiements en ligne en attente et comptabilise les confirmations.';

    public function handle(OnlinePaymentService $service): int
    {
        $limit = max(1, min(500, (int) $this->option('limit')));
        $payments = OnlinePayment::whereNotIn('status', ['succeeded', 'review'])
            ->whereNotNull('next_check_at')->where('next_check_at', '<=', now())
            ->orderBy('next_check_at')->limit($limit)->get();
        foreach ($payments as $payment) {
            $result = $service->refresh($payment);
            $this->line($result->reference.' : '.$result->status);
        }
        return self::SUCCESS;
    }
}
