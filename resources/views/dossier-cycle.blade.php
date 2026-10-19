@if($dossier)
@php
    $labels = \App\Http\Services\DossierWorkflow::LABELS;
    $next = \App\Http\Services\DossierWorkflow::NEXT[$dossier->etat_dossier] ?? [];
    $allowed = ($mode ?? 'student') === 'admin'
        ? ['soumis','incomplet','eligible','refuse','annule']
        : (in_array($dossier->etat_dossier,['brouillon','incomplet'],true) ? ['soumis','annule']
            : ($dossier->etat_dossier === 'soumis' ? ['annule']
            : ($dossier->etat_dossier === 'eligible' ? ['confirme','refuse']
            : ($dossier->etat_dossier === 'pre_attribue' ? ['accepte','refuse'] : []))));
    $actions = array_intersect($next, $allowed);
    if (($mode ?? 'student') === 'admin' && !app(\App\Services\AdminAccess::class)->allows(auth('admin')->user(), 'admin.dossiers.transition')) {
        $actions = [];
    }
    $proposal = $dossier->proposition;
@endphp
<section class="card mb-3">
    <div class="card-body">
        <h3>Suivi du dossier</h3>
        <p>État actuel : <strong>{{ $labels[$dossier->etat_dossier] ?? $dossier->etat_dossier }}</strong></p>
        <p class="text-muted">Brouillon → Soumis → Éligible → Confirmé par l’étudiant → Attribué définitif.</p>
        @if($proposal && in_array($dossier->etat_dossier,['pre_attribue','accepte','attribue_definitif'],true))
            <p>Proposition : chambre <strong>{{ $proposal->chambre?->numero_chambre ?? $proposal->chambre_id }}</strong>
                @if($proposal->date_fin) — valable jusqu’au {{ $proposal->date_fin->format('d/m/Y H:i') }} @endif
            </p>
        @endif
        @if($dossier->etat_dossier === 'incomplet')
            <p>Complétez les éléments signalés dans l’historique, puis soumettez à nouveau votre dossier.</p>
        @endif
        @if($dossier->etat_dossier === 'accepte')
            <p>La proposition a été acceptée. La validation définitive reste à effectuer par le service Logement.</p>
        @endif
        @if(count($actions))
            <form method="POST" action="{{ route(($mode ?? 'student') === 'admin' ? 'admin.dossiers.transition' : 'etudiants.dossiers.transition', $dossier) }}">
                @csrf
                <input type="hidden" name="assignation_id" value="{{ $dossier->assignation_id }}">
                <label class="d-block">Action
                    <select name="etat" class="form-control" required>
                        <option value="">Choisir</option>
                        @foreach($actions as $state)<option value="{{ $state }}">{{ ($mode ?? 'student') === 'admin' && $state === 'soumis' ? 'Réexaminer (retour à « Soumis »)' : $labels[$state] }}</option>@endforeach
                    </select>
                </label>
                <label class="d-block mt-2">Motif ou éléments à compléter
                    <textarea name="motif" class="form-control" maxlength="5000" rows="2">{{ old('motif') }}</textarea>
                </label>
                <small>Un motif est obligatoire pour déclarer le dossier incomplet, le refuser, l’annuler ou rouvrir son étude.</small>
                <button class="btn btn-primary mt-2" type="submit">Enregistrer cette étape</button>
            </form>
        @endif
        @if(($mode ?? 'student') !== 'admin' || auth('admin')->user()?->hasPermission('hebergement.admissions.history'))
        <details class="mt-3"><summary>Historique du dossier</summary>
            <ul class="mt-2">
                @foreach($dossier->transitions as $transition)
                    <li>
                        {{ $transition->created_at?->format('d/m/Y H:i') }} —
                        <strong>{{ $labels[$transition->nouvel_etat] ?? $transition->nouvel_etat }}</strong>
                        ({{ $transition->details['auteur_nom'] ?? $transition->acteur_type }})
                        @if($transition->motif)<p>{{ $transition->motif }}</p>@endif
                    </li>
                @endforeach
            </ul>
        </details>
        @endif
    </div>
</section>
@endif
