@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'residents'
])

@section('title', 'Statistiques d’attribution')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/campagnes-admission.css') }}">
    <link rel="stylesheet" href="{{ asset('css/allocation-chambres.css') }}">
@endpush

@php
    $nombre = fn ($valeur) => number_format((float) $valeur, 0, ',', ' ');
    $pourcent = fn ($valeur) => rtrim(rtrim(number_format((float) $valeur, 2, ',', ' '), '0'), ',');
    $taux = max(0, min(100, (float) ($stats['taux_allocation'] ?? 0)));
    $enAttente = (int) ($stats['assignations_en_attente'] ?? 0);
    $confirmees = (int) ($stats['assignations_confirmees'] ?? 0);
    $demandesEnAttente = (int) ($stats['sans_chambre'] ?? 0);
@endphp

@section('content')
<div class="as-refonte ca-page">
    <x-breadcrumbs
            title="Statistiques d’attribution"
            :items="[
                'Gestion Hébergement' => route('admin.hebergement.index'),
                'Attribution des chambres' => route('admin.allocation.index'),
                $campagne->annee_campagne => route('admin.allocation.show', $campagne->id_campagne),
                'Statistiques' => null,
            ]"
    />

    <div class="ca-header">
        <div class="ca-header-text">
            <p class="ca-subtitle">Campagne <strong>{{ $campagne->annee_campagne }}</strong> · Vue d’ensemble des demandes, des propositions et de l’occupation par type de chambre.</p>
        </div>
        <div class="ca-actions">
            @adminroute('admin.allocation.show')
            <a href="{{ route('admin.allocation.show', $campagne->id_campagne) }}" class="ca-btn ca-btn-outline"><x-admin-icon name="arrow-left" /> Suivi de la campagne</a>
            @endadminroute
            @adminroute('admin.allocation.export-csv')
            <a href="{{ route('admin.allocation.export-csv', $campagne->id_campagne) }}" class="ca-btn ca-btn-primary"><x-admin-icon name="download" /> Exporter (CSV)</a>
            @endadminroute
        </div>
    </div>

    @if(empty($stats))
        <div class="ca-card">
            <div class="ca-empty">
                <span class="ca-empty-icon"><x-admin-icon name="chart-column" size="22" /></span>
                <span>Aucune statistique disponible pour cette campagne.</span>
            </div>
        </div>
    @else
        <div class="ca-kpis ca-kpis--teintes">
            <div class="ca-kpi kpi-blue">
                <span class="ca-kpi-label">Demandes attribuées définitivement</span>
                <span class="ca-kpi-row"><strong class="ca-kpi-value">{{ $nombre($stats['demandes_acceptees']) }}</strong></span>
            </div>
            <div class="ca-kpi kpi-green">
                <span class="ca-kpi-label">Attributions confirmées</span>
                <span class="ca-kpi-row"><strong class="ca-kpi-value">{{ $nombre($confirmees) }}</strong></span>
            </div>
            <div class="ca-kpi kpi-amber">
                <span class="ca-kpi-label">Propositions à confirmer</span>
                <span class="ca-kpi-row"><strong class="ca-kpi-value">{{ $nombre($enAttente) }}</strong></span>
            </div>
            <div class="ca-kpi kpi-plum">
                <span class="ca-kpi-label">Demandes en attente d’étude</span>
                <span class="ca-kpi-row"><strong class="ca-kpi-value">{{ $nombre($demandesEnAttente) }}</strong></span>
            </div>
        </div>

        <div class="ca-card">
            <div class="ca-card-header"><h2 class="ca-card-title">Taux d’attribution</h2></div>
            <div class="ca-card-body al-rate-card">
                <div>
                    <div class="al-rate-big">
                        <strong>{{ $pourcent($taux) }} %</strong>
                        <span class="ca-muted">des demandes de la campagne sont attribuées définitivement</span>
                    </div>
                    <div class="ca-progress" style="margin-top:10px"><span style="width: {{ $taux }}%"></span></div>
                </div>
                <dl class="ca-dl">
                    <div class="ca-dl-row"><dt>Attributions confirmées</dt><dd style="color:var(--ca-primary)">{{ $nombre($confirmees) }}</dd></div>
                    <div class="ca-dl-row"><dt>Propositions en attente</dt><dd style="color:#a15c00">{{ $nombre($enAttente) }}</dd></div>
                    <div class="ca-dl-row"><dt>Total des attributions actives</dt><dd>{{ $nombre($stats['total_assignations'] ?? ($confirmees + $enAttente)) }}</dd></div>
                </dl>
            </div>
        </div>

        <div class="ca-card">
            <div class="ca-card-header">
                <h2 class="ca-card-title">Occupation par type de chambre</h2>
                <span class="ca-card-meta">Attributions de la campagne rapportées au parc de chaque type</span>
            </div>
            <div class="ca-card-body">
                @if(empty($stats['par_type']))
                    <div class="ca-empty">
                        <span class="ca-empty-icon"><x-admin-icon name="bed-double" size="22" /></span>
                        <span>Aucun type de chambre n’est paramétré.</span>
                    </div>
                @else
                    <div class="al-grid">
                        @foreach($stats['par_type'] as $data)
                            @php $occupation = $data['total'] > 0 ? min(100, round($data['occupees'] / $data['total'] * 100, 1)) : 0; @endphp
                            <article class="al-tile">
                                <div class="al-tile-head">
                                    <h3 class="al-tile-title">Chambres {{ $data['nom'] }}</h3>
                                    <span class="ca-badge tone-blue">{{ $pourcent($occupation) }} %</span>
                                </div>
                                <div class="ca-progress"><span style="width: {{ $occupation }}%"></span></div>
                                <dl class="ca-dl">
                                    <div class="ca-dl-row"><dt>Attribuées</dt><dd>{{ $nombre($data['occupees']) }}</dd></div>
                                    <div class="ca-dl-row"><dt>Disponibles</dt><dd style="color:var(--ca-primary)">{{ $nombre($data['disponibles']) }}</dd></div>
                                    <div class="ca-dl-row"><dt>Total</dt><dd>{{ $nombre($data['total']) }}</dd></div>
                                </dl>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        @if($demandesEnAttente > 0 || $enAttente > 0 || $taux < 50 || $taux >= 100)
            <div class="ca-card">
                <div class="ca-card-header">
                    <h2 class="ca-card-title"><span class="ca-kpi-icon tone-amber"><x-admin-icon name="list-checks" /></span> Points d’attention</h2>
                </div>
                <div class="ca-card-body">
                    <ul class="al-checks">
                        @if($demandesEnAttente > 0)
                            <li class="is-warn"><x-admin-icon name="triangle-alert" size="16" /> <span><strong>{{ $nombre($demandesEnAttente) }}</strong> demande(s) attendent encore leur étude ; elles ne peuvent pas recevoir de chambre tant qu’elles ne sont pas éligibles.</span></li>
                        @endif
                        @if($enAttente > 0)
                            <li class="is-warn"><x-admin-icon name="hourglass" size="16" /> <span><strong>{{ $nombre($enAttente) }}</strong> proposition(s) attendent la réponse de l’étudiant ou la validation définitive.</span></li>
                        @endif
                        @if($taux >= 100)
                            <li class="is-ok"><x-admin-icon name="circle-check" size="16" /> <span>Toutes les demandes de la campagne sont attribuées définitivement.</span></li>
                        @elseif($taux < 50)
                            <li class="is-warn"><x-admin-icon name="info" size="16" /> <span>Moins de la moitié des demandes sont attribuées définitivement : vérifiez les places disponibles et les dossiers en attente.</span></li>
                        @endif
                    </ul>
                </div>
            </div>
        @endif
    @endif
</div>
@endsection
