@extends('admin.layouts.app', ['activeMenu' => 'parametres', 'activeSubMenu' => 'legal-documents'])
@section('title', 'Téléverser un document')
@section('content')
<div class="legal-settings">
    <x-breadcrumbs title="Téléverser un document" :items="['Paramétrage' => route('admin.parametrage.index'), 'Documents' => route('admin.parametrage.legal-documents.index'), 'Téléversement' => null]" />
    <h1>{{ $types[$document->type] }}</h1>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <p class="muted">Importez le document préparé au format PDF. Les signatures éventuelles doivent déjà figurer dans le fichier.</p>
    <form id="legal-document-form" method="POST" enctype="multipart/form-data" action="{{ $document->exists ? route('admin.parametrage.legal-documents.update', $document) : route('admin.parametrage.legal-documents.store') }}">
        @csrf
        @if($document->exists) @method('PUT') @endif
        <input type="hidden" name="type" value="{{ $document->type }}">
        <input type="hidden" name="lock_version" value="{{ $document->lock_version }}">
        <section class="legal-card">
            <h2>Informations du document</h2>
            <label for="title">Titre</label><input id="title" name="title" value="{{ old('title', $document->title) }}" maxlength="200" required>
            <div class="form-grid">
                <label>Établissement<input name="institution" value="{{ old('institution', $document->institution) }}" maxlength="200" required></label>
                <label>Résidence ou périmètre concerné<input name="scope_label" value="{{ old('scope_label', $document->scope_label) }}" maxlength="150"></label>
                <label>Référence<input name="reference" value="{{ old('reference', $document->reference) }}" placeholder="Ex. REG-2026-001" maxlength="100"></label>
                <label>Version<input name="version_label" value="{{ old('version_label', $document->version_label) }}" maxlength="30" required></label>
                <label>Année universitaire<input name="academic_year" value="{{ old('academic_year', $document->academic_year) }}" placeholder="2026-2027" maxlength="30"></label>
                <label>Date d’application<input type="date" name="effective_date" value="{{ old('effective_date', $document->effective_date?->format('Y-m-d')) }}"></label>
            </div>
        </section>
        <section class="legal-card">
            <h2>Fichier du document</h2>
            @if($document->file_path)
                <p>Fichier actuel : <strong>{{ $document->file_name }}</strong></p>
                @adminroute('admin.parametrage.legal-documents.pdf')
                <a href="{{ route('admin.parametrage.legal-documents.pdf', $document) }}" target="_blank" rel="noopener">Consulter le PDF</a>
                @endadminroute
                <p class="muted">Choisissez un fichier pour le remplacer, ou laissez ce champ vide pour le conserver.</p>
            @elseif($document->exists)
                <p class="muted">Ce document a été rédigé avec l’ancien éditeur. Téléversez sa version PDF pour le mettre à jour.</p>
            @endif
            <label for="document-file">Document PDF (2 Mo maximum)</label>
            <input id="document-file" type="file" name="file" accept=".pdf,application/pdf" @required(!$document->file_path)>
        </section>
        <div class="legal-actions">
            <button class="btn btn-primary" type="submit">Enregistrer le document</button>
            <a href="{{ route('admin.parametrage.legal-documents.index') }}">Retour aux documents</a>
        </div>
    </form>
    @if($document->exists && $document->file_path && $document->status !== 'published')
        @adminroute('admin.parametrage.legal-documents.publish')
        <form method="POST" action="{{ route('admin.parametrage.legal-documents.publish', $document) }}" class="legal-card">
            @csrf
            <input type="hidden" name="lock_version" value="{{ $document->lock_version }}">
            <h2>Valider le document enregistré</h2>
            <p>La validation conserve le fichier dans l’historique et le met à disposition dans les documents de la résidence.</p>
            <button id="publish-document" type="submit" class="btn btn-success">Valider cette version</button>
        </form>
        @endadminroute
    @endif
    @if($versions->isNotEmpty())
        <section class="legal-card">
            <h2>Historique des versions validées</h2>
            <ul>@foreach($versions as $version)
                <li>@adminroute('admin.parametrage.legal-documents.versions.pdf')
                    <a href="{{ route('admin.parametrage.legal-documents.versions.pdf', [$document, $version]) }}" target="_blank" rel="noopener">Révision {{ $version->revision }} — {{ $version->created_at->format('d/m/Y H:i') }}</a>
                @endadminroute</li>
            @endforeach</ul>
        </section>
    @endif
</div>
@include('admin.parametrage.legal_documents.styles')
@endsection
@push('scripts')
<script>
document.getElementById('legal-document-form').addEventListener('input', () => {
    const button = document.getElementById('publish-document');
    if (button) button.disabled = true;
});
</script>
@endpush