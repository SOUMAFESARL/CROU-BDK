<?php

namespace App\Http\Controllers\etudiant;

use App\Http\Controllers\Controller;
use App\Models\Loyer;
use App\Models\OnlinePayment;
use App\Models\PaymentProvider;
use App\Payments\OnlinePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class OnlinePaymentController extends Controller
{
    public function index(Request $request, OnlinePaymentService $service)
    {
        $id = (string) Auth::guard('etudiant')->id();
        $assignment = $service->currentAssignment($id);
        $providers = PaymentProvider::payableToStudents();
        $selectedCode = $request->expectsJson() ? $request->query('provider') : $request->old('provider', $request->query('provider'));
        $data = [
            'providers' => $providers,
            'selectedProvider' => is_string($selectedCode) ? $providers->firstWhere('code', $selectedCode) : null,
            'assignment' => $assignment,
            'loyer' => $assignment?->chambre ? $service->rentForAssignment($id, $assignment) : null,
        ];
        if ($request->expectsJson()) {
            return response()->json(['html' => view('etudiant.online_payments.form', $data)->render()])
                ->header('Cache-Control', 'private, no-store');
        }
        $data['payments'] = OnlinePayment::where('student_id', $id)->latest()->paginate(10);
        return view('etudiant.online_payments.index', $data);
    }

    public function store(Request $request, OnlinePaymentService $service)
    {
        $data = $request->validate([
            'loyer_id' => ['required', 'string', 'max:150'],
            'provider' => ['required', Rule::in(['wave', 'mtn', 'cinetpay'])],
            'amount' => ['sometimes', 'required', 'integer', 'min:1', 'max:99999999'],
            'phone' => ['required_with:amount', 'nullable', 'string', 'regex:/^225[0-9]{10}$/D'],
        ]);
        $payment = $service->start((string) Auth::guard('etudiant')->id(), $data['loyer_id'], $data['provider'], $data['phone'] ?? '', isset($data['amount']) ? (int) $data['amount'] : null);
        if ($request->expectsJson()) {
            return response()->json([
                'status' => $payment->status,
                'label' => $payment->status_label,
                'checkout_url' => $payment->status === 'pending' ? $payment->checkout_url : null,
                'check_url' => route('etudiants.online-payments.check', $payment),
                'show_url' => route('etudiants.online-payments.show', $payment),
            ])->header('Cache-Control', 'private, no-store');
        }
        if ($payment->status === 'pending' && $payment->checkout_url) {
            return redirect()->away($payment->checkout_url);
        }
        return redirect()->route('etudiants.online-payments.show', $payment);
    }

    public function show(OnlinePayment $payment)
    {
        $this->authorizeOwner($payment);
        return response()->view('etudiant.online_payments.show', compact('payment'))
            ->header('Cache-Control', 'no-store');
    }

    public function check(Request $request, OnlinePayment $payment, OnlinePaymentService $service)
    {
        $this->authorizeOwner($payment);
        $payment = $service->refresh($payment);
        if ($request->expectsJson()) {
            return response()->json(['status' => $payment->status, 'label' => $payment->status_label, 'checkout_ready' => (bool) $payment->checkout_url])
                ->header('Cache-Control', 'no-store');
        }
        return redirect()->route('etudiants.online-payments.show', $payment);
    }

    private function authorizeOwner(OnlinePayment $payment): void
    {
        abort_unless($payment->student_id === (string) Auth::guard('etudiant')->id(), 404);
    }
}
