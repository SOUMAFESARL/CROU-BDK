{{-- resources/views/etudiant/partials/paiements_rows.blade.php --}}
@forelse($paiements as $paiement)
    @php
        $isPaid = $paiement->statut_paiement === 'valide';
        $isPending = $paiement->statut_paiement === 'en_attente';
        $rowClass = $isPaid ? 'row-paid' : ($isPending ? 'row-pending' : '');
        $badgeClass = $isPaid ? 'success' : ($isPending ? 'pending' : 'danger');
        $badgeText = $isPaid ? 'Payé' : ($isPending ? 'En attente' : 'Annulé');
        $moisLibelle = $paiement->mois ? (is_array($paiement->mois) ? $paiement->mois[0] ?? 'N/A' : $paiement->mois) : 'N/A';
    @endphp
    <tr class="{{ $rowClass }}">
        <td>
            <span class="period-date">{{ $moisLibelle }}</span>
            @if($isPending)
                <span class="period-tag">En attente</span>
            @endif
        </td>
        <td>{{ $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y') : '--' }}</td>
        <td><span class="ref-code">{{ $paiement->numero_paiement }}</span></td>
        <td><span class="method-tag orange">{{ $paiement->methode_label ?? $paiement->methode_paiement }}</span></td>
        <td class="amount-cell">{{ formatSystemAmount($paiement->montant) }}</td>
        <td><span class="badge {{ $badgeClass }}">{{ $badgeText }}</span></td>
    </tr>
@empty
    <tr>
        <td colspan="6" style="text-align:center; padding:20px; color:#5b7b99;">
            Aucun paiement effectué
        </td>
    </tr>
@endforelse
