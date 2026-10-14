@extends('admin.layouts.app', ['activeMenu' => 'parametres', 'activeSubMenu' => 'legal-documents'])
@section('title', 'Documents de la résidence')
@section('content')
<div class="legal-settings">
    <x-breadcrumbs title="Documents de la résidence" :items="['Paramétrage' => route('admin.parametrage.index'), 'Documents' => null]" />
    <h1>Documents de la résidence</h1>
    <p class="muted">Téléversez vos documents PDF, puis validez les versions à mettre à disposition.</p>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="legal-grid">
        @foreach($types as $type => $title)
        <div class="legal-card"><h2>{{ $title }}</h2>
            @adminroute('admin.parametrage.legal-documents.create')
            <a class="btn btn-primary" href="{{ route('admin.parametrage.legal-documents.create', ['type' => $type]) }}">Téléverser un document</a>
@endadminroute
        </div>
        @endforeach
    </div>
    <section class="legal-card">
        <h2>Documents enregistrés</h2>
        <div class="table-scroll"><table class="legal-table">
            <thead><tr><th>Document</th><th>Référence / version</th><th>Fichier</th><th>État</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse($documents as $document)
                <tr><td>{{ $document->title }}<br><small class="muted">{{ $types[$document->type] }}</small></td>
                    <td>{{ $document->reference ?: 'Sans référence' }}<br>{{ $document->version_label }}</td>
                    <td>{{ $document->file_name ?: 'Ancien document' }}</td>
                    <td><span class="{{ $document->status === 'published' ? 'badge-valid' : 'badge-draft' }}">{{ $document->status === 'published' ? 'Validé' : 'Brouillon' }}</span></td>
                    <td>@adminroute('admin.parametrage.legal-documents.edit')
<a href="{{ route('admin.parametrage.legal-documents.edit', $document) }}">Ouvrir</a>
@endadminroute<br>
                        @adminroute('admin.parametrage.legal-documents.pdf')
                        <a href="{{ route('admin.parametrage.legal-documents.pdf', $document) }}" target="_blank" rel="noopener">PDF</a>
@endadminroute</td></tr>
            @empty
                <tr><td colspan="5">Aucun document enregistré. Choisissez un type ci-dessus pour commencer.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        {{ $documents->links() }}
    </section>
</div>
@include('admin.parametrage.legal_documents.styles')
@endsection
