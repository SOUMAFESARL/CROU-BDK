<?php

namespace App\Http\Controllers\admin\parametrage;

use App\Http\Controllers\Controller;
use App\Models\Etudiant;
use App\Models\OnlinePayment;
use App\Models\PaymentProvider;
use App\Services\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class PaymentProviderController extends Controller
{
    /** Identifiants confidentiels de chaque connecteur, avec leur libellé à l'écran. */
    private const CREDENTIALS = [
        'mtn' => ['api_user' => 'API user', 'api_key' => 'API key', 'subscription_key' => 'Clé de souscription Collection'],
        'wave' => ['api_key' => 'Clé API Wave Business', 'request_signing_secret' => 'Secret de signature des requêtes', 'webhook_secret' => 'Secret de signature des notifications Wave'],
        'cinetpay' => ['api_key' => 'Clé API Checkout v2', 'merchant_key' => 'Site ID CinetPay (site_id)'],
        'default' => ['api_key' => 'Clé API', 'merchant_key' => 'Clé marchand'],
    ];

    /** Identifiants facultatifs même pour activer le réseau. */
    private const OPTIONAL_CREDENTIALS = ['request_signing_secret'];

    /** Regroupement des états d'un encaissement en ligne (filtre `?statut=`). */
    private const STATUTS_ENCAISSEMENT = [
        'confirmes' => ['succeeded'],
        'a_verifier' => ['review'],
        'echoues' => ['failed', 'cancelled'],
    ];

    public function index(Request $request): Response
    {
        $access = app(AdminAccess::class);
        $peutModifier = $access->allows($request->user('admin'), 'admin.parametrage.payment-providers.update');
        $payables = PaymentProvider::payableToStudents()->pluck('code')->all();
        $statut = array_key_exists((string) $request->query('statut'), self::STATUTS_ENCAISSEMENT) || $request->query('statut') === 'en_attente'
            ? (string) $request->query('statut') : '';
        $termines = array_merge(...array_values(self::STATUTS_ENCAISSEMENT));

        $encaissements = OnlinePayment::query()
            ->when($statut === 'en_attente', fn ($query) => $query->whereNotIn('status', $termines))
            ->when($statut && $statut !== 'en_attente', fn ($query) => $query->whereIn('status', self::STATUTS_ENCAISSEMENT[$statut]))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $etudiants = Schema::hasTable('etudiants')
            ? Etudiant::whereIn('id_etudiant', $encaissements->getCollection()->pluck('student_id')->unique())->pluck('name', 'id_etudiant')
            : collect();

        $compteurs = OnlinePayment::query()->selectRaw('status, count(*) as total, sum(amount) as montant')->groupBy('status')->get()->toBase()->keyBy('status');
        $compte = fn (array $statuts) => (int) $compteurs->only($statuts)->sum('total');
        $toutes = (int) $compteurs->sum('total');

        return Inertia::render('Admin/Parametrage/ApiPaiement/Index', [
            'providers' => PaymentProvider::orderBy('id')->get()->map(fn (PaymentProvider $provider) => [
                'id' => (int) $provider->id,
                'code' => $provider->code,
                'name' => $provider->name,
                'is_active' => (bool) $provider->is_active,
                'environment' => $provider->code === 'mtn' ? ($provider->environment ?: 'sandbox') : null,
                'api_url' => (string) $provider->api_url,
                // Jamais la valeur des clés : seulement le fait qu'elles soient enregistrées.
                'credentials' => collect($this->credentials($provider->code))->map(fn (string $label, string $field) => [
                    'field' => $field,
                    'label' => $label,
                    'saved' => filled($provider->getRawOriginal($field)),
                    'required' => !in_array($field, self::OPTIONAL_CREDENTIALS, true),
                ])->values(),
                'expected_urls' => $provider->code === 'mtn'
                    ? ['sandbox' => $this->expectedUrl('mtn', 'sandbox'), 'mtnivorycoast' => $this->expectedUrl('mtn', 'mtnivorycoast')]
                    : array_filter(['default' => $this->expectedUrl($provider->code, null)]),
                'connecte' => in_array($provider->code, ['wave', 'mtn', 'cinetpay'], true),
                'payable' => in_array($provider->code, $payables, true),
                'webhook_url' => $provider->code === 'wave' ? rtrim(config('app.url'), '/').'/api/payments/notifications/wave' : null,
                'update_url' => $peutModifier ? route('admin.parametrage.payment-providers.update', $provider) : null,
            ])->values(),
            'encaissements' => $encaissements->through(fn (OnlinePayment $payment) => [
                'id' => (string) $payment->id,
                'reference' => $payment->reference,
                'loyer_id' => $payment->loyer_id,
                'etudiant' => $etudiants[$payment->student_id] ?? null,
                'student_id' => $payment->student_id,
                'reseau' => PaymentProvider::NETWORKS[$payment->provider_code] ?? $payment->provider_code,
                'montant' => (int) $payment->amount,
                'devise' => $payment->currency ?: 'XOF',
                'status' => $payment->status,
                'status_label' => $payment->status_label,
                'last_error' => $payment->last_error,
                'created_at' => $payment->created_at?->format('Y-m-d H:i'),
            ]),
            'kpis' => [
                'total' => $toutes,
                'confirmes' => $compte(self::STATUTS_ENCAISSEMENT['confirmes']),
                'montant_confirme' => (int) $compteurs->only(self::STATUTS_ENCAISSEMENT['confirmes'])->sum('montant'),
                'a_verifier' => $compte(self::STATUTS_ENCAISSEMENT['a_verifier']),
                'echoues' => $compte(self::STATUTS_ENCAISSEMENT['echoues']),
                'en_attente' => $toutes - $compte($termines),
            ],
            'filters' => ['statut' => $statut],
            'urls' => ['index' => route('admin.parametrage.payment-providers.index')],
        ]);
    }

    /** @return array<string, string> */
    private function credentials(string $code): array
    {
        return self::CREDENTIALS[$code] ?? self::CREDENTIALS['default'];
    }

    /** Adresse d'API imposée par les connecteurs raccordés (null : libre). */
    private function expectedUrl(string $code, ?string $environment): ?string
    {
        return match ($code) {
            'wave' => 'https://api.wave.com',
            'cinetpay' => 'https://api-checkout.cinetpay.com/v2/payment',
            'mtn' => $environment === 'mtnivorycoast' ? 'https://proxy.momoapi.mtn.com' : 'https://sandbox.momodeveloper.mtn.com',
            default => null,
        };
    }

    public function update(Request $request, PaymentProvider $provider): RedirectResponse
    {
        $secretFields = array_keys($this->credentials($provider->code));
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
            $required = array_merge(['api_url'], array_diff($secretFields, self::OPTIONAL_CREDENTIALS));
            foreach ($required as $field) {
                $value = $field === 'api_url' || $changedEnvironment
                    ? $request->input($field)
                    : ($request->filled($field) ? $request->input($field) : $provider->{$field});
                if (blank($value)) {
                    $validator->errors()->add($field, 'Renseignez « '.($this->credentials($provider->code)[$field] ?? 'Lien API').' » avant d’activer ce réseau.');
                }
            }
            $expected = $this->expectedUrl($provider->code, $request->input('environment'));
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
