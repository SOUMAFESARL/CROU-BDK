<?php

namespace App\Http\Controllers\admin\parametrage;

use App\Http\Controllers\Controller;
use App\Models\PaymentProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class PaymentProviderController extends Controller
{
    public function index(): View
    {
        return view('admin.parametrage.payment_providers.index', [
            'providers' => PaymentProvider::orderBy('id')->get(),
            'onlinePayments' => \App\Models\OnlinePayment::latest()->paginate(20),
        ]);
    }

    public function update(Request $request, PaymentProvider $provider): RedirectResponse
    {
        $secretFields = match ($provider->code) {
            'mtn' => ['api_key', 'api_user', 'subscription_key'],
            'wave' => ['api_key', 'request_signing_secret', 'webhook_secret'],
            default => ['api_key', 'merchant_key'],
        };
        $rules = [
            'api_url' => ['nullable', 'url:http,https', 'max:2048'],
            'is_active' => ['required', 'boolean'],
        ];
        foreach ($secretFields as $field) {
            $rules[$field] = ['nullable', 'string', 'max:4096'];
        }
        if ($provider->code === 'mtn') {
            $rules['environment'] = ['required', 'in:sandbox,mtnivorycoast'];
        }
        $changedEnvironment = $provider->code === 'mtn'
            && $request->input('environment') !== $provider->environment;

        $validator = Validator::make($request->only(array_keys($rules)), $rules, [
            'api_url.url' => 'Le lien API doit être une URL HTTP ou HTTPS valide.',
        ]);
        $validator->after(function ($validator) use ($request, $provider, $secretFields, $changedEnvironment) {
            if ($validator->errors()->isNotEmpty() || !$request->boolean('is_active')) {
                return;
            }
            $required = array_merge(['api_url'], array_diff($secretFields, ['request_signing_secret']));
            foreach ($required as $field) {
                $value = $field === 'api_url' || $changedEnvironment
                    ? $request->input($field)
                    : ($request->filled($field) ? $request->input($field) : $provider->{$field});
                if (blank($value)) {
                    $validator->errors()->add($field, 'Renseignez '.$field.' avant d’activer ce réseau.');
                }
            }
            $expected = match ($provider->code) {
                'wave' => 'https://api.wave.com',
                'cinetpay' => 'https://api-checkout.cinetpay.com/v2/payment',
                'mtn' => $request->input('environment') === 'mtnivorycoast'
                    ? 'https://proxy.momoapi.mtn.com' : 'https://sandbox.momodeveloper.mtn.com',
                default => null,
            };
            if ($expected !== null && rtrim((string) $request->input('api_url'), '/') !== $expected) {
                $validator->errors()->add('api_url', 'Pour ce connecteur, utilisez '.$expected.'.');
            }
        });

        if ($validator->fails()) {
            return back()->withErrors($validator, 'provider_'.$provider->id)
                ->withInput($request->only(['api_url', 'is_active', 'environment']))
                ->with('editing_provider', $provider->id);
        }
        $data = $validator->validated();
        foreach ($secretFields as $secret) {
            if (blank($data[$secret] ?? null)) {
                if ($changedEnvironment) {
                    $data[$secret] = null;
                } else {
                    unset($data[$secret]);
                }
            }
        }
        $provider->update($data);
        return redirect()->route('admin.parametrage.payment-providers.index')
            ->with('success', $provider->name.' : paramètres enregistrés.');
    }
}
