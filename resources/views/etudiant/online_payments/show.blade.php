@extends('etudiant.online_payments.layout')
@section('title', 'Suivi du paiement')
@section('content')
<h1>Suivi du paiement</h1>
<div class="card">
    <p class="notice {{ $payment->status === 'succeeded' ? 'success' : '' }}" role="status">{{ $payment->status_label }}</p>
    <div class="amount">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</div>
    <dl>
        <dt>Réseau</dt><dd>{{ \App\Models\PaymentProvider::NETWORKS[$payment->provider_code] ?? $payment->provider_code }}</dd>
        <dt>Référence</dt><dd><code>{{ $payment->reference }}</code></dd>
        <dt>Date</dt><dd>{{ $payment->created_at->format('d/m/Y H:i') }}</dd>
    </dl>
    @if($payment->status === 'succeeded')
        <p>Votre règlement est enregistré. Le solde de votre loyer a été mis à jour.</p>
    @elseif($payment->status === 'review')
        <p>Contactez le service financier avec cette référence. Ne renouvelez pas ce paiement pendant sa vérification.</p>
    @elseif($payment->status === 'failed')
        <p>L’opérateur n’a pas confirmé ce paiement. Vous pouvez retourner à la liste des loyers.</p>
    @else
        @if($payment->provider_code === 'mtn')
            <p>Validez la demande de paiement sur votre téléphone MTN. La confirmation peut prendre quelques instants.</p>
        @elseif($payment->checkout_url)
            <p>Ouvrez la page de paiement pour terminer le règlement auprès de l’opérateur.</p>
            <a class="button" href="{{ $payment->checkout_url }}" rel="noreferrer">Ouvrir le paiement</a>
        @else
            <p>Nous vérifions la création de votre paiement. Ne lancez pas une nouvelle transaction.</p>
        @endif
        <p class="muted">Cette page vérifie automatiquement la confirmation. Vous pouvez la retrouver dans « Mes paiements en ligne ».</p>
        <form id="verify-payment" method="POST" action="{{ route('etudiants.online-payments.check', $payment) }}">
            @csrf
            <button type="submit">Vérifier maintenant</button>
        </form>
        <p id="verification-info" class="muted" aria-live="polite"></p>
    @endif
    <div class="actions"><a href="{{ route('etudiants.online-payments.index') }}">Mes loyers et paiements</a></div>
</div>
@endsection
@section('scripts')
<script>
(() => {
    const form = document.getElementById('verify-payment');
    if (!form) return;
    const initialStatus = @json($payment->status);
    const initialCheckout = @json((bool) $payment->checkout_url);
    let running = false;
    async function verify() {
        if (running) return;
        running = true;
        const button = form.querySelector('button');
        button.disabled = true;
        try {
            const response = await fetch(form.action, {
                method: 'POST', credentials: 'same-origin',
                headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': form.elements._token.value}
            });
            if (!response.ok || response.redirected) throw new Error('verification');
            const result = await response.json();
            if (result.status !== initialStatus || result.checkout_ready !== initialCheckout) {
                window.location.reload();
                return;
            }
            document.getElementById('verification-info').textContent = 'Confirmation toujours en attente.';
        } catch (_) {
            document.getElementById('verification-info').textContent = 'Vérification indisponible pour le moment. La reprise est automatique.';
        } finally {
            running = false;
            button.disabled = false;
        }
    }
    form.addEventListener('submit', e => { e.preventDefault(); verify(); });
    setInterval(() => { if (!document.hidden) verify(); }, 15000);
})();
</script>
@endsection
