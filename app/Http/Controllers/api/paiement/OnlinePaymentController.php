<?php

namespace App\Http\Controllers\api\paiement;

use App\Http\Controllers\Controller;
use App\Models\OnlinePayment;
use App\Models\PaymentProvider;
use App\Payments\OnlinePaymentService;
use App\Payments\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OnlinePaymentController extends Controller
{
    public function providers()
    {
        return response()->json(['success' => true, 'data' => PaymentProvider::payableToStudents()])
            ->header('Cache-Control', 'private, no-store');
    }

    public function index(Request $request)
    {
        $data = $request->validate(['per_page' => 'sometimes|integer|min:1|max:100']);
        $payments = OnlinePayment::where('student_id', $request->user()->getKey())
            ->latest()->orderByDesc('id')->paginate($data['per_page'] ?? 15)->withQueryString();
        $payments->through(fn ($payment) => $this->serialize($payment));
        return response()->json(['success' => true, 'data' => $payments])->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, OnlinePaymentService $service)
    {
        $data = $request->validate([
            'loyer_id' => 'required|string|max:150',
            'provider' => ['required', Rule::in(PaymentGateway::SUPPORTED)],
            'phone' => 'nullable|string|max:20',
        ]);
        $payment = $service->start((string) $request->user()->getKey(), $data['loyer_id'], $data['provider'], $data['phone'] ?? '');
        return response()->json(['success' => true, 'data' => $this->serialize($payment)], 200)
            ->header('Cache-Control', 'private, no-store');
    }

    public function show(Request $request, string $payment)
    {
        return response()->json(['success' => true, 'data' => $this->serialize($this->owned($request, $payment))])
            ->header('Cache-Control', 'private, no-store');
    }

    public function check(Request $request, string $payment, OnlinePaymentService $service)
    {
        $payment = $service->refresh($this->owned($request, $payment));
        return response()->json(['success' => true, 'data' => $this->serialize($payment)])
            ->header('Cache-Control', 'private, no-store');
    }

    private function owned(Request $request, string $id): OnlinePayment
    {
        return OnlinePayment::where('student_id', $request->user()->getKey())->findOrFail($id);
    }

    private function serialize(OnlinePayment $payment): array
    {
        return array_merge($payment->only(['id', 'reference', 'provider_code', 'loyer_id', 'amount', 'currency',
            'status', 'checkout_url', 'paiement_id', 'created_at', 'verified_at']), [
            'label' => $payment->status_label,
            'receipt_url' => $payment->paiement_id ? route('api.student.documents.receipt', $payment->paiement_id) : null,
        ]);
    }
}
