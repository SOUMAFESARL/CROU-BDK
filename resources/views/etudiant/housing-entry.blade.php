@extends('etudiant.layouts.documents')
@section('title', 'Mon entrée dans les lieux')
@section('content')
<section class="document-card">
<h2>Mon rendez-vous d’entrée</h2>
@forelse($appointments as $appointment)
    <p><strong>{{ $appointment->starts_at->format('d/m/Y à H:i') }}</strong> — {{ $appointment->location }}
        ({{ $appointment->status === 'scheduled' ? 'Programmé' : 'Annulé' }})</p>
@empty
    <p>Le rendez-vous est programmé après l’attribution définitive et votre approbation du contrat, selon les créneaux d’accueil disponibles.</p>
@endforelse
<p><a href="{{ route('etudiants.contract.approval') }}">Consulter et approuver mon contrat</a></p>
</section>
<section class="document-card">
<h2>Mes notifications</h2>
@forelse($messages as $message)
    <article><h3>{{ $message->subject }}</h3><p>{{ $message->body }}</p>
        <small>{{ $message->created_at->format('d/m/Y H:i') }}@if($message->status === 'cancelled') — Proposition ou rendez-vous annulé @endif</small>
    </article>
@empty
    <p>Aucune notification pour le moment.</p>
@endforelse
{{ $messages->links() }}
<p><a href="{{ route('etudiants.dashboard',['section'=>'demandes']) }}">Suivre ma demande et répondre à la proposition</a></p>
</section>
@endsection
