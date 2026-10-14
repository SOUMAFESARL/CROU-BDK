@extends('admin.layouts.app', ['activeMenu' => 'parametres', 'activeSubMenu' => 'legal-signatories'])
@section('title', 'Signatures des responsables')
@section('content')
<div class="legal-settings">
    <x-breadcrumbs title="Signatures des responsables" :items="['Paramétrage' => route('admin.parametrage.index'), 'Signatures' => null]" />
    <h1>Signatures des responsables</h1>
    <p class="muted">Une configuration commune à l’application. Renseignez le titulaire de chaque fonction et son image de signature.</p>
    <div class="legal-actions">@adminroute('admin.parametrage.legal-documents.index')
<a class="btn btn-outline-primary" href="{{ route('admin.parametrage.legal-documents.index') }}">Rédiger les documents</a>
@endadminroute</div>
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if(!extension_loaded('gd'))<p class="warning">L’extension PHP GD doit être activée sur le serveur pour exporter les signatures PNG dans les PDF.</p>@endif
    <div class="legal-grid">
    @foreach($roles as $code => $label)
        @php
            $signatory = $signatories->get($code);
            $bag = $errors->getBag($code);
            $editing = $bag->any();
        @endphp
        @adminroute('admin.parametrage.legal-signatories.update')
        <form class="legal-card" method="POST" enctype="multipart/form-data" action="{{ route('admin.parametrage.legal-signatories.update', $code) }}">
            @csrf @method('PUT')
            <h2>{{ $label }}</h2>
            @if($bag->any())<div class="alert alert-danger" role="alert"><ul>@foreach($bag->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <label for="name-{{ $code }}">Nom et prénoms du responsable</label>
            <input id="name-{{ $code }}" name="full_name" maxlength="150" value="{{ $editing ? old('full_name') : $signatory?->full_name }}">
            <label for="signature-{{ $code }}">Image de signature</label>
            @if($signatory?->signature_path)
                <p><img class="signature-preview" src="{{ route('admin.parametrage.legal-signatories.image', $code) }}?v={{ $signatory->updated_at?->timestamp }}" alt="Signature enregistrée du {{ $label }}"></p>
            @else
                <p class="muted">Aucune signature enregistrée.</p>
            @endif
            <input id="signature-{{ $code }}" type="file" name="signature" accept="image/png,image/jpeg">
            <small class="muted">PNG ou JPEG, 2 Mo maximum, jusqu’à 2 000 × 1 000 pixels. Un PNG sur fond transparent est conseillé. Sans nouveau fichier, la signature actuelle est conservée.</small>
            <label class="inline-check"><input type="checkbox" name="remove_signature" value="1"> Retirer l’image actuelle</label>
            <input type="hidden" name="is_active" value="0">
            <label class="inline-check"><input type="checkbox" name="is_active" value="1" @checked($editing ? old('is_active') : $signatory?->is_active)> Autoriser l’insertion dans les documents</label>
            <button type="submit" class="btn btn-primary">Enregistrer cette signature</button>
        </form>
        @endadminroute
    @endforeach
    </div>
    <section class="legal-card">
        <h2>Quels signataires pour quels documents ?</h2>
        <p>Voici les sélections proposées à la création. Ajustez-les dans chaque document selon votre circuit interne de validation.</p>
        <div class="table-scroll"><table class="legal-table"><thead><tr><th>Document</th><th>Signataires proposés</th><th></th></tr></thead><tbody>
        @foreach($types as $type => $title)
            <tr><td>{{ $title }}</td><td>{{ collect(\App\Models\LegalDocument::SUGGESTED_SIGNATORIES[$type])->map(fn ($role) => $roles[$role])->join(', ') }}</td>
                <td>@adminroute('admin.parametrage.legal-documents.create')
<a href="{{ route('admin.parametrage.legal-documents.create', ['type' => $type]) }}">Rédiger</a>
@endadminroute</td></tr>
        @endforeach
        </tbody></table></div>
        <p class="muted">Modifier ou désactiver une signature agit sur les prochains documents et brouillons. Les versions déjà validées conservent leurs signatures.</p>
    </section>
</div>
@include('admin.parametrage.legal_documents.styles')
@endsection
