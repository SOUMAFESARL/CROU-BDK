@extends('etudiant.layouts.documents')
@section('title', 'Mes documents')
@section('content')
<p>Consultez vos contrats, états des lieux, reçus et justificatifs.</p>
<div class="documents-grid">
<section class="document-card"><h2>Contrat de logement</h2>
@if($hasContract)<a href="{{ route('etudiants.pdfContrat') }}" target="_blank" rel="noopener">Consulter mon contrat PDF</a>
<p><a class="btn document-action" href="{{ route('etudiants.contract.approval') }}">Approbation de mon contrat</a></p>
@else<p>Aucun contrat disponible.</p>@endif</section>
<section class="document-card"><h2>États des lieux d’entrée et de sortie</h2><ul>
@forelse($inventories as $item)<li><a href="{{ route('etudiants.documents.inventory', $item->getKey()) }}" target="_blank" rel="noopener">État des lieux {{ $item->type_etat === 'entree' ? 'd’entrée' : 'de sortie' }} — {{ $item->date_etat?->format('d/m/Y') }} (PDF)</a></li>
@empty<li>Aucun état des lieux enregistré.</li>@endforelse</ul></section>
<section class="document-card"><h2>Reçus de paiement et de restitution</h2><ul>
@forelse($receipts as $item)<li><a href="{{ route('etudiants.documents.receipt', $item->getKey()) }}">{{ $item->statut_paiement === 'rembourse' ? 'Restitution' : 'Paiement' }} — {{ $item->numero_paiement ?? $item->getKey() }} — {{ number_format((float)$item->montant, 0, ',', ' ') }} {{ $item->devise ?? 'XOF' }} — {{ $item->date_paiement?->format('d/m/Y') }} (PDF)</a></li>
@empty<li>Aucun paiement validé ni restitution enregistré.</li>@endforelse</ul></section>
<section class="document-card"><h2>Pièces justificatives et documents déposés</h2><ul>
@forelse($attachments as $item)<li><a href="{{ route('etudiants.documents.attachment', $item->getKey()) }}" target="_blank" rel="noopener noreferrer">{{ $item->type_document ?: 'Document' }}</a> — {{ $item->statut_libelle }}</li>
@empty<li>Aucun document déposé.</li>@endforelse</ul></section>
<section class="document-card"><h2>Décomptes de sortie</h2><ul>
@forelse($departures as $item)<li><a href="{{ route('etudiants.documents.settlement', $item->getKey()) }}">Décompte du {{ $item->date_demande?->format('d/m/Y') }} (PDF)</a></li>
@empty<li>Aucun décompte établi.</li>@endforelse</ul><p>Le décompte présente les retenues et la restitution de caution. Il ne remplace pas un quitus de sortie délivré par l’administration.</p></section>

</div>
@endsection
