@extends('etudiant.layouts.documents')
@section('title', 'Demande de réadmission')
@section('content')
<section class="document-card">
    <h2>Demander une réadmission</h2>
    <p>Votre compte et vos documents sont conservés. Cette demande sera examinée selon les règles de la campagne.</p>
    @if($errors->any())
        <div role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    @endif
    @if($campagnes->isEmpty())
        <p>Aucune campagne active pour le moment.</p>
    @else
        <form method="POST" action="{{ route('etudiants.readmission.store') }}">
            @csrf
            <p><label>Campagne
                <select name="campagne_id" required>
                    @foreach($campagnes as $campagne)
                        <option value="{{ $campagne->getKey() }}" @selected(old('campagne_id') === $campagne->getKey())>{{ $campagne->annee_campagne }}</option>
                    @endforeach
                </select>
            </label></p>
            <p><label>Niveau d’étude actuel
                <select name="niveau" required>
                    <option value="">Sélectionner</option>
                    @foreach($niveaux as $niveau)<option value="{{ $niveau->getKey() }}" @selected(old('niveau') === $niveau->getKey())>{{ $niveau->name }}</option>@endforeach
                </select>
            </label></p>
            <p><label>Moyenne sur 20 <input name="moyenne" type="number" min="0" max="20" step="0.01" value="{{ old('moyenne') }}"></label></p>
            <p><label>Crédits validés <input name="credit_valider" type="number" min="0" value="{{ old('credit_valider') }}"></label></p>
            <p><label><input name="boursier" type="checkbox" value="1" @checked(old('boursier'))> Boursier</label></p>
            <p><label>Situation sociale ou observations <textarea name="cas_social" maxlength="2000">{{ old('cas_social') }}</textarea></label></p>
            <button type="submit" class="btn document-action">Soumettre ma demande</button>
        </form>
    @endif
</section>
@endsection
