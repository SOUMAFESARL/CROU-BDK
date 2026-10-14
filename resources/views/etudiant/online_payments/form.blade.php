<div class="card">
    @if($providers->isEmpty())
        <p>Aucun moyen de paiement en ligne n’est disponible pour le moment. Contactez le service financier.</p>
    @elseif(!$assignment?->chambre)
        <p>Aucune chambre occupée n’est enregistrée dans votre dossier. Contactez le service d’hébergement.</p>
    @elseif(!$loyer)
        <p>Chambre {{ $assignment->chambre->numero_chambre }} : aucun loyer restant à régler n’est disponible. Si votre loyer n’a pas encore été établi, contactez le service financier.</p>
    @elseif(!$selectedProvider)
        <p>Sélectionnez un réseau ci-dessus pour ouvrir votre formulaire de paiement.</p>
    @else
        <h2>Payer avec {{ $selectedProvider->name }}</h2>
        <form method="POST" action="{{ route('etudiants.online-payments.store') }}" id="online-payment-form">
            @csrf
            <input type="hidden" name="loyer_id" value="{{ $loyer->id_loyer }}">
            <dl>
                <dt>Votre chambre</dt><dd>{{ $assignment->chambre->numero_chambre }}</dd>
                <dt>Période à régler</dt><dd>{{ sprintf('%02d/%04d', $loyer->mois_facturation, $loyer->annee_facturation) }}</dd>
                <dt>Loyer facturé</dt><dd>{{ number_format((float) $loyer->montant_total, 0, ',', ' ') }} FCFA</dd>
                @if((float) $loyer->total_penalites > 0)
                    <dt>Pénalités</dt><dd>{{ number_format((float) $loyer->total_penalites, 0, ',', ' ') }} FCFA</dd>
                @endif
                @if((float) $loyer->total_paye > 0)
                    <dt>Déjà réglé</dt><dd>{{ number_format((float) $loyer->total_paye, 0, ',', ' ') }} FCFA</dd>
                @endif
                <dt>Solde restant</dt><dd class="amount">{{ number_format((float) $loyer->solde_restant, 0, ',', ' ') }} FCFA</dd>
            </dl>
            <p class="muted">Vous pouvez régler tout ou partie du solde. Le loyer le plus ancien est proposé en premier.</p>
            <input type="hidden" name="provider" value="{{ $selectedProvider->code }}">
            <label for="amount">Montant à payer (FCFA)</label>
            <input id="amount" name="amount" type="number" inputmode="numeric" required min="{{ $selectedProvider->code === 'cinetpay' ? 5 : 1 }}" max="{{ (int) $loyer->solde_restant }}" step="{{ $selectedProvider->code === 'cinetpay' ? 5 : 1 }}" value="{{ old('amount', (int) $loyer->solde_restant) }}" aria-describedby="amount-help">
            <small id="amount-help" class="muted">Sans dépasser le solde restant. @if($selectedProvider->code === 'cinetpay') CinetPay accepte les multiples de 5 FCFA. @endif</small>
            <div id="phone-field">
                <label for="phone">Votre numéro de téléphone {{ $selectedProvider->name }}</label>
                <input id="phone" name="phone" type="tel" inputmode="numeric" maxlength="13" pattern="225[0-9]{10}"
                    value="{{ old('phone') }}" placeholder="2250500000000" aria-describedby="phone-help" required>
                <small id="phone-help" class="muted">Indicatif 225 suivi de votre numéro à 10 chiffres.</small>
            </div>
            <p class="muted">Le bouton ci-dessous lance le paiement auprès de {{ $selectedProvider->name }}. Vous validerez ensuite l’opération sur votre téléphone ou sur la page de l’opérateur.</p>
            <button type="submit" id="start-payment">Payer avec {{ $selectedProvider->name }}</button>
        </form>
    @endif
</div>
