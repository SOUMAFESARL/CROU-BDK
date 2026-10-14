@extends('admin.layouts.app', ['activeMenu' => 'parametres', 'activeSubMenu' => 'legal-documents'])
@section('title', 'Aperçu du document')
@section('content')
<div class="legal-settings">
    <div class="legal-actions">
        @adminroute('admin.parametrage.legal-documents.edit')
        <a href="{{ route('admin.parametrage.legal-documents.edit', $document) }}">Retour à la rédaction</a>
@endadminroute
        @adminroute('admin.parametrage.legal-documents.pdf')
        <a class="btn btn-primary" href="{{ route('admin.parametrage.legal-documents.pdf', $document) }}" target="_blank" rel="noopener">Ouvrir le PDF</a>
@endadminroute
    </div>
    @if($snapshot['draft'])<p class="warning">BROUILLON — version enregistrée, non validée.</p>@endif
    <article class="legal-card">
        <p>{{ $snapshot['institution'] }}</p>
        <h1>{{ $snapshot['title'] }}</h1>
        <p class="muted">Référence : {{ $snapshot['reference'] ?: 'À renseigner' }} · Version {{ $snapshot['version_label'] }} · Application : {{ $snapshot['effective_date'] ?: 'À renseigner' }}</p>
        <p>{{ $snapshot['scope_label'] }} @if($snapshot['academic_year'])— {{ $snapshot['academic_year'] }}@endif</p>
        @if(($snapshot['body_format'] ?? 'text') === 'html')
            <div class="legal-body legal-rich">{!! \App\Http\Services\LegalDocumentHtml::clean($snapshot['body']) !!}</div>
        @else
            <div class="legal-body">{{ $snapshot['body'] }}</div>
        @endif
        @if($snapshot['schedule'])
            <h2 class="mt-4">Calendrier</h2>
            <div class="table-scroll">@include('pdf.legal-document-schedule')</div>
        @endif
        <p class="mt-4">@if($snapshot['location'])Lieu : {{ $snapshot['location'] }}.@endif @if($snapshot['contact'])Contact : {{ $snapshot['contact'] }}.@endif</p>
        <div class="legal-grid">
        @foreach($snapshot['signatures'] as $signature)
            <div class="legal-card"><h3>{{ $signature['role'] }}</h3><p>{{ $signature['name'] ?: 'Nom à renseigner' }}</p>
                @if($signature['image'])<img class="signature-preview" src="{{ $signature['image'] }}" alt="Signature du {{ $signature['role'] }}">
                @else<p class="muted">Signature non configurée ou désactivée.</p>@endif
            </div>
        @endforeach
        </div>
        @if($snapshot['validated_at'])<p class="muted">Version validée le {{ $snapshot['validated_at'] }}.</p>@endif
    </article>
</div>
@include('admin.parametrage.legal_documents.styles')
@endsection
