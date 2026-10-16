<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>État des lieux</title><link rel="icon" href="{{ systemLogo() }}">
<style>body{font:16px Arial;max-width:960px;margin:24px auto;padding:16px}table{width:100%;border-collapse:collapse}td,th{padding:10px;border:1px solid #ddd;text-align:left}img{max-width:240px;max-height:200px;margin:8px}@media print{.actions{display:none}}</style>    <link rel="stylesheet" href="{{ asset('files/assets/css/typography.css') }}?v=20260923">
</head><body>
<div class="actions"><a href="{{ route('etudiants.documents.personal') }}">← Mes documents</a> <button onclick="window.print()">Imprimer / Enregistrer en PDF</button></div>
<h1>État des lieux {{ $inventory->type_etat === 'entree' ? 'd’entrée' : 'de sortie' }}</h1>
<p>{{ $inventory->date_etat?->format('d/m/Y') }} — Référence : {{ $inventory->getKey() }}</p>
@unless($inventory->signature_crou && $inventory->signature_etudiant)<p>Signatures incomplètes : document en cours de validation.</p>@endunless
@foreach($inventory->pieces as $piece)<h2>{{ $piece->name_piece }}</h2><table><tr><th>Élément</th><th>État</th><th>Observation</th></tr>
@foreach($piece->elements as $element)<tr><td>{{ $element->name_element }}</td><td>{{ $element->etat }}</td><td>{{ $element->observation }}</td></tr>@endforeach</table>
@foreach($piece->images as $image)<img src="{{ $image->url }}" alt="Photo de {{ $piece->name_piece }}">@endforeach
@endforeach
<h2>Observations générales</h2><p>{{ $inventory->observation_generale ?: 'Aucune observation' }}</p>
<p>Nombre de clés reçues : {{ $inventory->nbre_cle_recus }}</p>
@foreach(['signature_crou' => 'CROU', 'signature_etudiant' => 'Étudiant'] as $field => $label)
<h2>Signature {{ $label }}</h2>@if($inventory->$field)<img src="{{ asset('storage/'.$inventory->$field) }}" alt="Signature {{ $label }}">@else<p>En attente</p>@endif
@endforeach</body></html>
