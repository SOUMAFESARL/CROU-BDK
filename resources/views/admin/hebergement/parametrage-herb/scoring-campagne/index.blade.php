@extends('admin.layouts.app', ['activeMenu'=>'hebergement','activeSubMenu'=>'parametrage-herb'])
@section('title', 'Scoring des campagnes')
@section('content')
<div class="page-body">
    <x-breadcrumbs title="Scoring" :items="['Gestion Hébergement'=>route('admin.hebergement.index'),'Paramétrage'=>route('admin.hebergement.parametrage-herb.index'),'Scoring'=>null]" />
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <div class="card"><div class="card-body">
        <h2 class="h4">Barème de classement par campagne</h2>
        <form method="GET" class="mb-3">
            <label for="scoring-campaign">Campagne</label>
            <select id="scoring-campaign" name="campagne_id" class="form-control">
                @foreach($campagnes as $item)<option value="{{ $item->getKey() }}" @selected($campagne?->getKey()===$item->getKey())>{{ $item->annee_campagne }} — {{ $item->description }}</option>@endforeach
            </select>
            <button class="btn btn-outline-primary mt-2">Afficher le barème</button>
        </form>
        <p>Chaque sous-score va de 0 à 100. Le score final est la somme des sous-scores multipliés par leur pondération. Les pondérations doivent totaliser 100 %.</p>
        <p>Les critères « échelon de bourse », « orphelin », « parent isolé », « famille nombreuse », « zone rurale », « sportif » et « artiste » ne sont pas encore collectés dans les dossiers et ne sont pas utilisés.</p>
        @if($campagne)
        @if(!$scoring)<div class="alert alert-info">Aucun barème enregistré. Les valeurs proposées ci-dessous sont des exemples à adapter. Elles ne seront appliquées qu’après enregistrement et activation.</div>@endif
        @adminroute('admin.hebergement.parametrage-herb.scoring-campagne.store')
        <form method="POST" action="{{ route('admin.hebergement.parametrage-herb.scoring-campagne.store') }}">
            @csrf
            <input type="hidden" name="campagne_id" value="{{ $campagne->getKey() }}">
            @php
                $defaults = [
                    'social'=>['boursier'=>100,'non_boursier'=>0],
                    'academique'=>['tb'=>100,'bien'=>80,'ab'=>60,'passable'=>40,'insuffisant'=>0],
                    'geographique'=>['local'=>0,'proche'=>35,'loin'=>65,'tres_loin'=>100],
                    'specifique'=>['pmr'=>100,'autre'=>0],
                ];
            @endphp
            <div class="row">
                @foreach($groups as $group=>$label)
                <fieldset class="col-md-6 mb-4">
                    <legend class="h5">{{ $label }}</legend>
                    <label for="weight-{{ $group }}">Pondération (%)</label>
                    <input id="weight-{{ $group }}" name="weights[{{ $group }}]" type="number" step="0.01" min="0" max="100" required class="form-control mb-3" value="{{ old('weights.'.$group, $scoring?->weights[$group] ?? ['social'=>40,'academique'=>30,'geographique'=>20,'specifique'=>10][$group]) }}">
                    @foreach($fields[$group] as $key=>$fieldLabel)
                    <label for="point-{{ $group }}-{{ $key }}">{{ $fieldLabel }} — points /100</label>
                    <input id="point-{{ $group }}-{{ $key }}" name="points[{{ $group }}][{{ $key }}]" type="number" step="0.01" min="0" max="100" required class="form-control mb-2" value="{{ old('points.'.$group.'.'.$key, $scoring?->points[$group][$key] ?? $defaults[$group][$key]) }}">
                    @endforeach
                </fieldset>
                @endforeach
            </div>
            <label for="minimum-score">Score minimum pour l’attribution automatique (/100)</label>
            <input id="minimum-score" name="minimum_score" type="number" step="0.01" min="0" max="100" required class="form-control mb-3" value="{{ old('minimum_score', $scoring?->minimum_score ?? 0) }}">
            <input type="hidden" name="pmr_first" value="0">
            <p><label><input type="checkbox" name="pmr_first" value="1" @checked(old('pmr_first', $scoring?->pmr_first ?? false))> Priorité absolue aux étudiants dont le statut PMR est confirmé</label></p>
            <input type="hidden" name="active" value="0">
            <p><label><input type="checkbox" name="active" value="1" @checked(old('active', $scoring?->active ?? false))> Activer ce barème pour cette campagne</label></p>
            <p>À priorité et score égaux, le dossier le plus ancien passe en premier. Sous le seuil, le dossier reste sans attribution automatique. Une donnée manquante pour un critère pondéré bloque le classement et doit être complétée.</p>
            <button type="submit" class="btn btn-primary">Enregistrer le barème</button>
        </form>
        @endadminroute
        <h3 class="h5 mt-4">Historique des versions</h3>
        <p>Les changements s’appliquent aux prochains lancements ; les attributions existantes sont conservées.</p>
        <ul>@forelse($history as $version)<li>Version #{{ $version->id }} — {{ $version->created_at->format('d/m/Y H:i') }} — {{ $version->active ? 'Activé' : 'Désactivé' }} — Auteur : {{ $version->created_by }}</li>@empty<li>Aucune version enregistrée.</li>@endforelse</ul>
        @else<p>Aucune campagne disponible.</p>@endif
    </div></div>
</div>
@endsection
