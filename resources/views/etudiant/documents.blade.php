@extends('etudiant.layouts.documents')
@section('title', 'Documents de la résidence')
@section('content')
    <p>Consultez les documents validés par l’administration. Les PDF peuvent être enregistrés ou imprimés depuis votre navigateur.</p>
    <div class="documents-grid">
    @forelse($documents as $document)
        @php($snapshot = $document['snapshot'])
        <article class="document-card">
            <p>{{ $snapshot['type_label'] }}</p>
            <h2>{{ $snapshot['title'] }}</h2>
            <p>{{ $snapshot['scope_label'] ?? '' }} · {{ $snapshot['academic_year'] ?? '' }}</p>
            <p>Référence : {{ $snapshot['reference'] }} · Version : {{ $snapshot['version_label'] }}<br>Date d’application : {{ $snapshot['effective_date'] }}</p>
            <a class="btn document-action" href="{{ route('etudiants.documents.pdf', $document['id']) }}" target="_blank" rel="noopener">Consulter le PDF</a>
        </article>
    @empty
        <article class="document-card">Aucun document validé n’est disponible pour le moment.</article>
    @endforelse

    </div>
@endsection
