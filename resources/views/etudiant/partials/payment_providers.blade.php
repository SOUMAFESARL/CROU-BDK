<section class="panel" aria-labelledby="payment-networks-title" style="margin-bottom: 20px; padding: 20px;">
    <h2 id="payment-networks-title" style="font-size: 18px; margin-bottom: 12px;">Réseaux de paiement</h2>
    @if($paymentProviders->isNotEmpty())
        <ul style="display: flex; flex-wrap: wrap; gap: 12px; list-style: none; padding: 0; margin: 0;">
            @foreach($paymentProviders as $paymentProvider)
                <li style="flex:1 1 180px;">
                    <button type="button" class="payment-network-card" data-payment-form="{{ route('etudiants.online-payments.index', ['provider' => $paymentProvider->code]) }}" aria-haspopup="dialog" aria-controls="rent-payment-dialog" style="display:flex;flex-direction:column;gap:10px;padding:24px;border:1px solid #cbd5e1;border-radius:12px;color:inherit;background:#f8fafc;width:100%;cursor:pointer;">
                        <strong>{{ $paymentProvider->name }}</strong>
                        <span>Payer mon loyer &rarr;</span>
                    </button>
                </li>
            @endforeach
        </ul>
    @else
        <p style="margin: 0;">Aucun réseau de paiement n’est activé pour le moment.</p>
    @endif
</section>
<dialog id="rent-payment-dialog" aria-labelledby="rent-payment-title">
    <header><h2 id="rent-payment-title">Paiement du loyer</h2><button type="button" data-close-payment aria-label="Fermer">&times;</button></header>
    <div data-payment-message role="status" aria-live="polite"></div>
    <div data-payment-content></div>
</dialog>
<style>
#rent-payment-dialog{width:min(560px,calc(100% - 24px));max-height:90vh;overflow:auto;margin:auto;padding:24px;border:0;border-radius:16px;background:#fff;color:#18354b;box-shadow:0 20px 60px #0003}#rent-payment-dialog::backdrop{background:#0f172a99}#rent-payment-dialog header{display:flex;align-items:center;justify-content:space-between;gap:16px}#rent-payment-dialog header h2{font-size:21px}#rent-payment-dialog button{cursor:pointer}#rent-payment-dialog [data-close-payment]{background:transparent;border:0;font-size:28px;padding:6px}#rent-payment-dialog label{display:block;margin:14px 0 5px;font-weight:600}#rent-payment-dialog input:not([type=hidden]){width:100%;border:1px solid #a9bacb;border-radius:8px;padding:12px;font:inherit}#rent-payment-dialog dl{display:grid;grid-template-columns:1fr 1fr;gap:6px;margin:16px 0}#rent-payment-dialog dd{margin:0;font-weight:600}#rent-payment-dialog .muted{color:#577184;font-size:14px}#rent-payment-dialog .card{border:0;box-shadow:none;padding:0;margin:12px 0}#rent-payment-dialog button[type=submit],#rent-payment-dialog [data-retry-payment]{background:#16794a;color:#fff;border:0;border-radius:8px;padding:12px 20px;margin-top:16px}#rent-payment-dialog button:disabled{opacity:.6;cursor:wait}#rent-payment-dialog [data-payment-message]:not(:empty){padding:12px;margin:12px 0;background:#eef6ff;border-radius:8px}#rent-payment-dialog [data-payment-message][role=alert]{background:#fff0ef;color:#8b2929}
</style>
<script src="{{ asset('js/student-payment-modal.js') }}" defer></script>
