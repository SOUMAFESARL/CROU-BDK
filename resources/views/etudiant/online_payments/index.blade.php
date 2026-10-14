@extends('etudiant.online_payments.layout')
@section('title', 'Payer mon loyer')
@section('content')
<h1>Payer mon loyer</h1>
<p class="muted">Le loyer de votre chambre est renseigné automatiquement. Choisissez votre moyen de paiement.</p>
<nav aria-label="Réseaux de paiement" style="display:flex;flex-wrap:wrap;gap:12px;margin:20px 0;">
    @foreach($providers as $provider)
        <a href="{{ route('etudiants.online-payments.index', ['provider' => $provider->code]) }}" @if($selectedProvider?->code === $provider->code) aria-current="page" @endif
           style="flex:1 1 160px;padding:24px;border:2px solid {{ $selectedProvider?->code === $provider->code ? '#16794a' : '#cbd5e1' }};border-radius:12px;text-decoration:none;background:white;">
            <strong>{{ $provider->name }}</strong><br><small>Payer mon loyer &rarr;</small>
        </a>
    @endforeach
</nav>
@if($errors->any())
    <div class="notice error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
@include('etudiant.online_payments.form')
<h2>Mes paiements en ligne</h2>
<div class="card table-scroll">
    <table>
        <thead><tr><th>Date</th><th>Réseau</th><th>Montant</th><th>État</th><th></th></tr></thead>
        <tbody>
        @forelse($payments as $payment)
            <tr>
                <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ \App\Models\PaymentProvider::NETWORKS[$payment->provider_code] ?? $payment->provider_code }}</td>
                <td>{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</td>
                <td>{{ $payment->status_label }}</td>
                <td><a href="{{ route('etudiants.online-payments.show', $payment) }}">Consulter</a></td>
            </tr>
        @empty
            <tr><td colspan="5">Aucun paiement en ligne pour le moment.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $payments->links() }}
</div>
@endsection
@section('scripts')
<script>
(() => {
    const form = document.getElementById('online-payment-form');
    if (!form) return;
    const initialLabel = document.getElementById('start-payment').textContent;
    form.addEventListener('submit', () => {
        const button = document.getElementById('start-payment');
        button.disabled = true;
        button.textContent = 'Préparation du paiement…';
    });
    window.addEventListener('pageshow', () => {
        document.getElementById('start-payment').disabled = false;
        document.getElementById('start-payment').textContent = initialLabel;
    });
})();
</script>
@endsection
