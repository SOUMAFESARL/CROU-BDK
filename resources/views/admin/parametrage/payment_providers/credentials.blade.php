@php
    $fields = match ($provider->code) {
        'mtn' => ['api_user' => 'API user', 'api_key' => 'API key', 'subscription_key' => 'Clé de souscription Collection'],
        'wave' => ['api_key' => 'Clé API Wave Business', 'request_signing_secret' => 'Secret de signature des requêtes (si activé chez Wave)', 'webhook_secret' => 'Secret de signature des notifications Wave'],
        'cinetpay' => ['api_key' => 'Clé API Checkout v2', 'merchant_key' => 'Site ID CinetPay (site_id)'],
        default => ['api_key' => 'Clé API', 'merchant_key' => 'Clé marchand'],
    };
@endphp
@if($provider->code === 'mtn')
    <label for="environment-{{ $provider->id }}">Environnement MTN</label>
    <select class="form-control" id="environment-{{ $provider->id }}" name="environment"
        onchange="this.form.elements.api_url.value = this.value === 'sandbox' ? 'https://sandbox.momodeveloper.mtn.com' : 'https://proxy.momoapi.mtn.com'">
        <option value="sandbox" @selected(($editing ? old('environment') : $provider->environment) === 'sandbox')>Sandbox (tests en EUR)</option>
        <option value="mtnivorycoast" @selected(($editing ? old('environment') : $provider->environment) === 'mtnivorycoast')>Côte d’Ivoire (XOF)</option>
    </select>
    <p class="text-muted mt-2">En changeant d’environnement, renseignez à nouveau les trois identifiants. Les anciens identifiants ne seront pas réutilisés.</p>
@elseif($provider->code === 'wave')
    <p class="text-muted mt-2">Dans Wave Business, enregistrez la notification <strong>checkout.session.completed</strong> avec signature HMAC et cette URL :</p>
    <code style="overflow-wrap:anywhere">{{ rtrim(config('app.url'), '/') }}/api/payments/notifications/wave</code>
@elseif($provider->code === 'cinetpay')
    <p class="text-muted mt-2">Configuration Checkout v2. Les identifiants api_password du nouveau Seamless ne sont pas compatibles avec cette version.</p>
@elseif(in_array($provider->code, ['moov', 'orange', 'tresorpay'], true))
    <p class="text-muted mt-2">Paramètres provisoires : le protocole du contrat Côte d’Ivoire reste à confirmer avant de connecter les transactions.</p>
@endif
@foreach($fields as $field => $label)
    <label for="{{ $field }}-{{ $provider->id }}">{{ $label }}</label>
    <input class="form-control" id="{{ $field }}-{{ $provider->id }}" type="password" name="{{ $field }}" maxlength="4096" autocomplete="new-password"
        placeholder="{{ $provider->getRawOriginal($field) ? 'Valeur enregistrée — laisser vide pour conserver' : 'À renseigner' }}" />
@endforeach
