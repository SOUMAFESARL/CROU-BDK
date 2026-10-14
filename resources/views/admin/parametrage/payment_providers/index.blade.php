@extends('admin.layouts.app', ['activeMenu' => 'parametres', 'activeSubMenu' => 'payment-providers'])
@section('title', 'API de paiement')
@section('content')
<div class="page-body payment-settings">
    <x-breadcrumbs title="API de paiement" :items="['Gestion Paramétrage' => route('admin.parametrage.index'), 'API de paiement' => null]" />
    @if(session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    <div class="payment-settings-intro">
        <h2>Réseaux de paiement</h2>
        <p>Configurez les réseaux. Seuls les connecteurs raccordés et complets sont proposés pour payer : Wave, MTN Côte d’Ivoire et CinetPay Checkout v2.</p>
        <p>Les clés sont chiffrées. Laissez un champ de clé vide pour conserver sa valeur actuelle.</p>
    </div>
    <div class="provider-grid">
        @foreach($providers as $provider)
            @php
                $bag = $errors->getBag('provider_'.$provider->id);
                $editing = (string) session('editing_provider') === (string) $provider->id;
            @endphp
            @adminroute('admin.parametrage.payment-providers.update')
            <form class="provider-card" method="POST" action="{{ route('admin.parametrage.payment-providers.update', $provider) }}" autocomplete="off">
                @csrf
                @method('PUT')
                <div class="provider-heading">
                    <h3>{{ $provider->name }}</h3>
                    <span class="provider-status {{ $provider->is_active ? 'is-active' : '' }}">{{ $provider->is_active ? 'Activé' : 'Inactif · masqué' }}</span>
                </div>
                @if($bag->any())
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            @foreach($bag->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif
                <label for="api-url-{{ $provider->id }}">Lien API</label>
                <input class="form-control" id="api-url-{{ $provider->id }}" type="url" name="api_url" maxlength="2048"
                    value="{{ $editing ? old('api_url') : $provider->api_url }}" placeholder="https://..." />
                @include('admin.parametrage.payment_providers.credentials')
                <div class="provider-footer">
                    <input type="hidden" name="is_active" value="0" />
                    <label class="provider-toggle" for="active-{{ $provider->id }}">
                        <input id="active-{{ $provider->id }}" type="checkbox" name="is_active" value="1" @checked($editing ? old('is_active') : $provider->is_active) />
                        Activer le réseau
                    </label>
                    <button class="btn btn-primary" type="submit">Enregistrer</button>
                </div>
            </form>
            @endadminroute
        @endforeach
    </div>
    <div class="payment-settings-intro">
        <h2>Suivi des encaissements en ligne</h2>
        <p>Une transaction « À vérifier » nécessite un rapprochement avec l’opérateur avant toute régularisation comptable.</p>
        <div class="table-responsive"><table class="table">
            <thead><tr><th>Date</th><th>Référence / loyer</th><th>Étudiant</th><th>Réseau</th><th>Montant</th><th>État</th></tr></thead>
            <tbody>
            @forelse($onlinePayments as $payment)
                <tr><td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $payment->reference }}<br><small>{{ $payment->loyer_id }}</small></td>
                    <td>{{ $payment->student_id }}</td>
                    <td>{{ \App\Models\PaymentProvider::NETWORKS[$payment->provider_code] ?? $payment->provider_code }}</td>
                    <td>{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</td>
                    <td>{{ $payment->status_label }}@if($payment->last_error)<br><small>{{ $payment->last_error }}</small>@endif</td></tr>
            @empty
                <tr><td colspan="6">Aucun encaissement en ligne.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        {{ $onlinePayments->links() }}
    </div>
</div>
@endsection
@push('styles')
<style>
.payment-settings{padding-bottom:30px}.payment-settings-intro{background:#fff;padding:22px;border-radius:14px;margin:18px 0}.payment-settings-intro h2{font-size:22px;color:#1f2937}.payment-settings-intro p{color:#64748b;margin:6px 0}.provider-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px}.provider-card{background:#fff;padding:24px;border:1px solid #e5e7eb;border-radius:14px;box-shadow:0 4px 16px #00000006}.provider-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:20px}.provider-heading h3{font-size:20px;margin:0}.provider-status{font-size:var(--app-font-min, 15px);border-radius:20px;padding:6px 10px;background:#f1f5f9;color:#475569}.provider-status.is-active{background:#dcfce7;color:#166534}.provider-card>label{display:block;font-weight:600;margin:16px 0 7px}.provider-card .form-control{width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:10px 12px}.provider-footer{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-top:24px;flex-wrap:wrap}.provider-toggle{display:flex;align-items:center;gap:8px;margin:0;cursor:pointer}.provider-toggle input{width:18px;height:18px;accent-color:#1f7c43}@media(max-width:900px){.provider-grid{grid-template-columns:1fr}}@media(max-width:480px){.provider-card{padding:16px}.provider-heading{align-items:flex-start;flex-direction:column}}
</style>
@endpush
