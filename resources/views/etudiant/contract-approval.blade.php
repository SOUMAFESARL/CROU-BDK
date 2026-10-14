@extends('etudiant.layouts.documents')
@section('title', 'Approbation de mon contrat')
@section('content')
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if($errors->any())<div role="alert" class="document-card">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<section class="document-card">
<h2>Mon contrat de logement</h2>
<p>Consultez votre contrat avant de confirmer votre approbation.</p>
<p><a class="btn document-action" href="{{ route('etudiants.pdfContrat') }}" target="_blank" rel="noopener">Consulter le contrat PDF</a></p>
@if($contract->approved_at)
    <p><strong>Contrat approuvé le {{ $contract->approved_at->format('d/m/Y à H:i') }}.</strong></p>
    <p>{{ $contract->consent_text }}</p>
@else
    <form method="POST" action="{{ route('etudiants.contract.approve') }}">
        @csrf
        <input type="hidden" name="contract_id" value="{{ $contract->id }}">
        <input type="hidden" name="document_hash" value="{{ $contract->document_hash }}">
        <p><label><input type="checkbox" name="consent" value="1" required> {{ \App\Http\Services\StudentContractApprovalService::CONSENT }}</label></p>
        <button type="submit" class="btn document-action">Approuver mon contrat</button>
    </form>
@endif
<p><a href="{{ route('etudiants.documents.personal') }}">Retour à mes documents</a></p>
</section>
@endsection
