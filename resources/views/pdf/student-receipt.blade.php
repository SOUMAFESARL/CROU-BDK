<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,sans-serif;font-size:12px}h1{font-size:22px}td{padding:10px;border-bottom:1px solid #ddd}table{width:100%;border-collapse:collapse}</style></head><body>
<h1>{{ $payment->statut_paiement === 'rembourse' ? 'Reçu de restitution' : 'Reçu de paiement' }}</h1>
<p>Référence : {{ $payment->numero_paiement ?? $payment->getKey() }}</p>
<p>Étudiant : {{ $student->name }} — Matricule : {{ $student->matricule }}</p>
<table><tr><td>Date</td><td>{{ $payment->date_paiement?->format('d/m/Y') }}</td></tr>
<tr><td>Nature</td><td>{{ ucfirst($payment->type_paiement) }}</td></tr>
<tr><td>Montant {{ $payment->statut_paiement === 'rembourse' ? 'restitué' : 'payé' }}</td><td>{{ number_format((float)$payment->montant, 0, ',', ' ') }} {{ $payment->devise ?? 'XOF' }}</td></tr>
<tr><td>Mode de règlement</td><td>{{ str_replace('_', ' ', $payment->methode_paiement) }}</td></tr>
<tr><td>Référence de transaction</td><td>{{ $payment->reference_transaction ?: '—' }}</td></tr></table>
<p>Document établi à partir de l’opération enregistrée. Édité le {{ now()->format('d/m/Y à H:i') }}.</p>
</body></html>
