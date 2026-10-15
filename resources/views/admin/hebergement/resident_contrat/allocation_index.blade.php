@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'residents'
])

@section('title', 'Attribution des chambres')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/campagnes-admission.css') }}">
    <link rel="stylesheet" href="{{ asset('css/allocation-chambres.css') }}">
@endpush

@php
    // [clé de stats, libellé, couleur]
    $indicateurs = [
        ['total', 'Campagnes', 'kpi-blue'],
        ['active', 'En cours', 'kpi-green'],
        ['planifiee', 'Planifiées', 'kpi-amber'],
        ['terminee', 'Terminées', 'kpi-plum'],
    ];
    $statuts = [
        'active' => ['tone-green', 'circle-play'],
        'planifiee' => ['tone-amber', 'calendar-clock'],
        'terminee' => ['tone-gray', 'archive'],
    ];
    $nombre = fn ($valeur) => number_format((float) $valeur, 0, ',', ' ');
@endphp

@section('content')
<div class="as-refonte ca-page">
    <x-breadcrumbs
            title="Attribution des chambres"
            :items="[
                'Gestion Hébergement' => route('admin.hebergement.index'),
                'Attribution des chambres' => null,
            ]"
    />

    <div class="ca-header">
        <div class="ca-header-text">
            <p class="ca-subtitle">Choisissez une campagne pour proposer des chambres aux dossiers éligibles, suivre les propositions et valider les attributions définitives.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="ca-alert ca-alert-success" role="status"><x-admin-icon name="circle-check" /> <div>{{ session('success') }}</div></div>
    @endif
    @if(session('error'))
        <div class="ca-alert ca-alert-error" role="alert"><x-admin-icon name="circle-x" /> <div>{{ session('error') }}</div></div>
    @endif

    <div class="ca-kpis ca-kpis--teintes">
        @foreach($indicateurs as [$cle, $libelle, $couleur])
            <div class="ca-kpi {{ $couleur }}">
                <span class="ca-kpi-label">{{ $libelle }}</span>
                <span class="ca-kpi-row"><strong class="ca-kpi-value">{{ $nombre($stats[$cle] ?? 0) }}</strong></span>
            </div>
        @endforeach
    </div>

    @include('admin.hebergement.resident_contrat.ranking-summary')

    <div class="ca-card">
        <div class="ca-card-header">
            <div>
                <h2 class="ca-card-title">Campagnes d’attribution</h2>
                <span class="ca-card-meta">Les campagnes annulées ne sont pas affichées.</span>
            </div>
            <div class="al-view-toggle" role="group" aria-label="Affichage des campagnes" data-al-views>
                <button type="button" class="ca-icon-btn is-active" data-al-view="liste" aria-pressed="true" title="Affichage en liste">
                    <x-admin-icon name="list" /><span class="sr-only">Liste</span>
                </button>
                <button type="button" class="ca-icon-btn" data-al-view="cartes" aria-pressed="false" title="Affichage en cartes">
                    <x-admin-icon name="layout-grid" /><span class="sr-only">Cartes</span>
                </button>
            </div>
        </div>

        <div class="ca-table-wrap" data-al-view-panel="liste">
            <table class="ca-table">
                <thead>
                <tr>
                    <th>Campagne</th>
                    <th>Statut</th>
                    <th>Période</th>
                    <th class="al-num" title="Dossiers éligibles en attente d’une proposition de chambre">À loger</th>
                    <th class="al-num" title="Propositions de chambre en attente de réponse ou de validation">Propositions</th>
                    <th class="al-num">Confirmées</th>
                    <th>Taux d’allocation</th>
                    <th class="is-actions"><span class="sr-only">Actions</span></th>
                </tr>
                </thead>
                <tbody>
                @forelse($campagnes as $campagne)
                    @php
                        [$toneStatut, $iconeStatut] = $statuts[$campagne->statut] ?? ['tone-gray', 'info'];
                        $taux = max(0, min(100, (float) $campagne->getTauxAllocation()));
                    @endphp
                    <tr>
                        <td>
                            <div class="al-campagne">
                                <span class="ca-strong">{{ $campagne->annee_campagne }}</span>
                                @if($campagne->description)
                                    <span class="ca-muted ca-small" title="{{ $campagne->description }}">{{ $campagne->description }}</span>
                                @endif
                            </div>
                        </td>
                        <td><span class="ca-badge {{ $toneStatut }}"><x-admin-icon :name="$iconeStatut" size="14" /> {{ $campagne->getLabelStatut() }}</span></td>
                        <td class="ca-nowrap">
                            {{ $campagne->periode_debut?->format('d/m/Y') ?? '—' }}
                            <span class="ca-muted">→</span>
                            {{ $campagne->periode_fin?->format('d/m/Y') ?? '—' }}
                        </td>
                        <td class="al-num ca-mono">{{ $nombre($campagne->dossiers_eligibles_count) }}</td>
                        <td class="al-num ca-mono">{{ $nombre($campagne->propositions_count) }}</td>
                        <td class="al-num ca-mono">{{ $nombre($campagne->confirmees_count) }}</td>
                        <td>
                            <div class="al-rate">
                                <span class="al-rate-value">{{ rtrim(rtrim(number_format($taux, 2, ',', ' '), '0'), ',') }} %</span>
                                <div class="ca-progress" title="Attributions confirmées / étudiants ayant déposé une demande"><span style="width: {{ $taux }}%"></span></div>
                            </div>
                        </td>
                        <td class="is-actions">
                            @adminroute('admin.allocation.show')
                            <a href="{{ route('admin.allocation.show', $campagne->id_campagne) }}" class="ca-icon-btn" title="Suivi de la campagne" aria-label="Suivi de la campagne {{ $campagne->annee_campagne }}">
                                <x-admin-icon name="eye" />
                            </a>
                            @endadminroute
                            @adminroute('admin.allocation.allocate-form')
                            <a href="{{ route('admin.allocation.allocate-form', $campagne->id_campagne) }}" class="ca-icon-btn" title="Attribuer une chambre" aria-label="Attribuer une chambre — campagne {{ $campagne->annee_campagne }}">
                                <x-admin-icon name="bed-double" />
                            </a>
                            @endadminroute
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="ca-empty">
                                <span class="ca-empty-icon"><x-admin-icon name="inbox" size="22" /></span>
                                <span>Aucune campagne pour le moment.</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="al-cards" data-al-view-panel="cartes" hidden>
            @forelse($campagnes as $campagne)
                @php
                    [$toneStatut, $iconeStatut] = $statuts[$campagne->statut] ?? ['tone-gray', 'info'];
                    $taux = max(0, min(100, (float) $campagne->getTauxAllocation()));
                @endphp
                <article class="al-campagne-card">
                    <div class="al-campagne-card-head">
                        <div class="al-campagne">
                            <span class="ca-strong">{{ $campagne->annee_campagne }}</span>
                            @if($campagne->description)
                                <span class="ca-muted ca-small" title="{{ $campagne->description }}">{{ $campagne->description }}</span>
                            @endif
                        </div>
                        <span class="ca-badge {{ $toneStatut }}"><x-admin-icon :name="$iconeStatut" size="14" /> {{ $campagne->getLabelStatut() }}</span>
                    </div>
                    <p class="ca-muted ca-small al-campagne-card-periode">
                        <x-admin-icon name="calendar" size="14" />
                        {{ $campagne->periode_debut?->format('d/m/Y') ?? '—' }} → {{ $campagne->periode_fin?->format('d/m/Y') ?? '—' }}
                    </p>
                    <dl class="al-campagne-card-chiffres">
                        <div title="Dossiers éligibles en attente d’une proposition de chambre"><dt>À loger</dt><dd class="ca-mono">{{ $nombre($campagne->dossiers_eligibles_count) }}</dd></div>
                        <div title="Propositions de chambre en attente de réponse ou de validation"><dt>Propositions</dt><dd class="ca-mono">{{ $nombre($campagne->propositions_count) }}</dd></div>
                        <div><dt>Confirmées</dt><dd class="ca-mono">{{ $nombre($campagne->confirmees_count) }}</dd></div>
                    </dl>
                    <div class="al-rate">
                        <span class="ca-small ca-muted">Taux d’allocation : <span class="al-rate-value">{{ rtrim(rtrim(number_format($taux, 2, ',', ' '), '0'), ',') }} %</span></span>
                        <div class="ca-progress" title="Attributions confirmées / étudiants ayant déposé une demande"><span style="width: {{ $taux }}%"></span></div>
                    </div>
                    <div class="al-campagne-card-actions">
                        @adminroute('admin.allocation.show')
                        <a href="{{ route('admin.allocation.show', $campagne->id_campagne) }}" class="ca-btn ca-btn-sm ca-btn-outline" title="Suivi de la campagne" aria-label="Détails de la campagne {{ $campagne->annee_campagne }}">
                            <x-admin-icon name="eye" size="16" /> Détails
                        </a>
                        @endadminroute
                        @adminroute('admin.allocation.allocate-form')
                        <a href="{{ route('admin.allocation.allocate-form', $campagne->id_campagne) }}" class="ca-btn ca-btn-sm al-btn-allouer" title="Attribuer une chambre" aria-label="Allouer une chambre — campagne {{ $campagne->annee_campagne }}">
                            <x-admin-icon name="bed-double" size="16" /> Allouer
                        </a>
                        @endadminroute
                    </div>
                </article>
            @empty
                <div class="ca-empty">
                    <span class="ca-empty-icon"><x-admin-icon name="inbox" size="22" /></span>
                    <span>Aucune campagne pour le moment.</span>
                </div>
            @endforelse
        </div>

        @if($campagnes->total() > 0)
            <div class="ca-card-footer">
                <span>{{ $campagnes->firstItem() }} à {{ $campagnes->lastItem() }} sur {{ $campagnes->total() }} campagne(s)</span>
                {{ $campagnes->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/allocation-chambres.js') }}"></script>
@endpush
