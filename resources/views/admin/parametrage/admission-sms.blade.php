@extends('admin.layouts.app', ['activeMenu' => 'parametres'])
@section('title', 'Configuration des SMS')

@section('content')
    @php
        $editorBundle = 'build/admission-sms/editor.js';
        $editorLicense = config('services.tinymce.license_key');
        $useTinyMce = filled($editorLicense) && is_file(public_path($editorBundle));
    @endphp

    <div class="page-body">
        <x-breadcrumbs title="Configuration des SMS" :items="['Gestion Paramétrage' => route('admin.parametrage.index'), 'Configuration des SMS' => null]" />
        @if(session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif
        @error('messages')
            <div class="alert alert-danger" role="alert">{{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('admin.parametrage.admission-sms.update') }}">
            @csrf
            @method('PUT')
            <p>Personnalisez le SMS associé à chaque résultat de demande de logement. Le SMS utilise le texte brut, limité à 480 caractères.</p>
            <div class="row g-3">
                @foreach($labels as $key => $label)
                    <div class="col-12 col-lg-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <label class="form-label fw-semibold" for="sms-{{ $key }}">{{ $label }}</label>
                                <textarea class="form-control @error('messages.'.$key) is-invalid @enderror"
                                    id="sms-{{ $key }}" name="messages[{{ $key }}]" rows="5" maxlength="480"
                                    required @readonly(!$canEdit) aria-describedby="sms-{{ $key }}-count sms-{{ $key }}-error"
                                    @error('messages.'.$key) aria-invalid="true" @enderror>{{ old('messages.'.$key, $messages[$key]) }}</textarea>
                                @if($useTinyMce)
                                    <div data-sms-tinymce data-input-id="sms-{{ $key }}" data-counter-id="sms-{{ $key }}-count"
                                        data-error-id="sms-{{ $key }}-error" data-license-key="{{ $editorLicense }}"
                                        data-formatted-name="formatted[{{ $key }}]"
                                        data-initial-html="{{ old('messages.'.$key) === null ? ($formatted[$key] ?? '') : '' }}"></div>
                                @endif
                                <small class="d-block mt-1 text-muted" id="sms-{{ $key }}-count">480 caractères maximum</small>
                                <div id="sms-{{ $key }}-error" class="text-danger" role="alert">@error('messages.'.$key){{ $message }}@enderror</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @if($canEdit)
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Enregistrer les modèles</button>
                </div>
            @endif
        </form>
    </div>
    @if($useTinyMce)
        <script type="module" src="{{ asset($editorBundle) }}?v={{ filemtime(public_path($editorBundle)) }}"></script>
    @endif
@endsection
