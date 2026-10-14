<?php

namespace App\Http\Controllers\api\paiement;

use App\Http\Controllers\Controller;
use App\Jobs\VerifyOnlinePayment;
use App\Models\OnlinePayment;
use App\Payments\WaveWebhookSignature;
use Illuminate\Http\Request;

class OnlinePaymentNotificationController extends Controller
{
    public function notification(Request $request, OnlinePayment $payment, string $token)
    {
        abort_unless(in_array($payment->provider_code, ['mtn', 'cinetpay'], true)
            && hash_equals($payment->callback_token_hash, hash('sha256', $token)), 403);
        if ($request->isMethod('get')) {
            return response()->json(['received' => true]);
        }
        if ($payment->provider_code === 'cinetpay') {
            abort_unless($request->input('cpm_trans_id') === $payment->reference, 422);
            if ($request->filled('cpm_site_id')) {
                abort_unless((string) $request->input('cpm_site_id') === (string) $payment->credentials['merchant_key'], 422);
            }
        }
        return $this->scheduleVerification($payment);
    }

    public function wave(Request $request)
    {
        abort_if(strlen($request->getContent()) > 65536, 413);
        $reference = $request->input('data.client_reference');
        abort_unless(is_string($reference) && strlen($reference) <= 40, 422);
        $payment = OnlinePayment::where('provider_code', 'wave')->where('reference', $reference)->firstOrFail();
        abort_unless(WaveWebhookSignature::valid($request->getContent(),
            (string) $request->header('Wave-Signature'), (string) ($payment->credentials['webhook_secret'] ?? '')), 403);
        // Never trust the status or amount in a webhook; verify through the authenticated API.
        return $this->scheduleVerification($payment);
    }

    private function scheduleVerification(OnlinePayment $payment)
    {
        if (!in_array($payment->status, ['succeeded', 'review'], true)) {
            $payment->update(['notified_at' => now(), 'next_check_at' => now()]);
            // Persist first, respond quickly, then verify. Scheduled reconciliation recovers interruptions.
            VerifyOnlinePayment::dispatchAfterResponse($payment->id);
        }
        return response()->json(['received' => true]);
    }
}
