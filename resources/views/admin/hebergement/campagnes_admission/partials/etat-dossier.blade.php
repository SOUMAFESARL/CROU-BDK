{{-- Badge de l'état du dossier (Demande.etat_dossier, libellés de DossierWorkflow::LABELS). --}}
@php
    $tones = [
        'brouillon' => 'tone-gray', 'soumis' => 'tone-blue', 'incomplet' => 'tone-amber',
        'en_etude' => 'tone-purple', 'eligible' => 'tone-green', 'pre_attribue' => 'tone-green',
        'accepte' => 'tone-green', 'attribue_definitif' => 'tone-green', 'refuse' => 'tone-red',
        'annule' => 'tone-gray', 'expire' => 'tone-gray', 'cloture' => 'tone-gray',
    ];
    $etatDossier = $etat ?? null;
@endphp
<span class="ca-badge {{ $tones[$etatDossier] ?? 'tone-amber' }}">
    {{ \App\Http\Services\DossierWorkflow::LABELS[$etatDossier] ?? 'À vérifier' }}
</span>
