@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'campagnes'
])

@section('title', 'Admissions')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/campagnes-admission.css') }}">
@endpush

@php
    $dossiers = $etudiants;
    $anneeActive = $campagne_active['annee_campagne'] ?? null;

    // [filtre, libellé, clé des statistiques, icône, couleur, aide]
    $filtres = [
        ['nouvelles_demandes', 'Nouvelles demandes', 'nouvelles_demandes', 'inbox', 'kpi-blue', $anneeActive ? 'Campagne '.$anneeActive : 'Demandes reçues'],
        ['en_attente', 'En attente', 'en_attente', 'hourglass', 'kpi-amber', 'Campagnes précédentes'],
        ['annuler', 'Demandes rejetées', 'annulees', 'ban', 'kpi-plum', 'Refusées'],
    ];
    $libellesFiltre = [
        'nouvelles_demandes' => 'Nouvelles demandes', 'en_attente' => 'Demandes en attente',
        'annuler' => 'Demandes rejetées', 'en_verification' => 'Demandes en vérification', 'residents' => 'Résidents',
    ];
@endphp

@section('content')
<div class="as-refonte ca-page">
    <x-breadcrumbs
            title="Admissions"
            :items="[
        'Gestion Hébergement' => route('admin.hebergement.index'),
        'Admissions' => null,
    ]"
    />

    <div class="ca-header">
        <div class="ca-header-text">
            <p class="ca-subtitle">Suivez les demandes de logement et traitez chaque dossier de candidature.</p>
        </div>
        <div class="ca-actions">
            @adminroute('admin.hebergement.campagnes_admission.campagnes')
            <a href="{{ route('admin.hebergement.campagnes_admission.campagnes') }}" class="ca-btn ca-btn-outline">
                <x-admin-icon name="calendar" /> Campagnes
            </a>
            @endadminroute
            @adminroute('admin.etudiant.admission_directe')
            <a href="{{ route('admin.etudiant.admission_directe') }}" class="ca-btn ca-btn-primary">
                <x-admin-icon name="user-plus" /> Enregistrer un étudiant déjà logé
            </a>
            @endadminroute
        </div>
    </div>

    @if(session('error'))
        <div class="ca-alert ca-alert-error" role="alert"><x-admin-icon name="circle-x" /> <div>{{ session('error') }}</div></div>
    @endif

    @if($campagne_active)
        <div class="ca-banner">
            <span class="ca-banner-item"><x-admin-icon name="circle-play" /> <strong>Campagne active {{ $campagne_active['annee_campagne'] }}</strong></span>
            @if(!empty($campagne_active['date_echeance_demande']))
                <span class="ca-banner-item"><x-admin-icon name="calendar-clock" /> Échéance des demandes : {{ \Carbon\Carbon::parse($campagne_active['date_echeance_demande'])->format('d/m/Y') }}</span>
            @endif
            @if(!empty($campagne_active['moyenne_min']))
                <span class="ca-banner-item"><x-admin-icon name="graduation-cap" /> Moyenne min. {{ $campagne_active['moyenne_min'] }}/20</span>
            @endif
            @if(!empty($campagne_active['distance_min']))
                <span class="ca-banner-item"><x-admin-icon name="map-pin" /> Distance min. {{ $campagne_active['distance_min'] }} km</span>
            @endif
        </div>
    @else
        <div class="ca-banner is-muted">
            <span class="ca-banner-item"><x-admin-icon name="info" /> Aucune campagne active pour le moment.</span>
        </div>
    @endif

    @adminroute('admin.hebergement.campagnes_admission.admission')
    <div class="ca-kpis ca-kpis--teintes">
        @foreach($filtres as [$filtre, $libelle, $cle, $icone, $couleur, $aide])
            <a href="{{ route('admin.hebergement.campagnes_admission.admission', ['etat' => $filtre]) }}"
               class="ca-kpi {{ $couleur }} {{ $etat == $filtre ? 'is-active' : '' }}"
               @if($etat == $filtre) aria-current="page" @endif>
                <span class="ca-kpi-label">{{ $libelle }}</span>
                <span class="ca-kpi-row">
                    <strong class="ca-kpi-value">{{ $stats[$cle] ?? 0 }}</strong>
                    <span class="ca-kpi-badge">{{ $aide }}</span>
                </span>
            </a>
        @endforeach
    </div>
    @endadminroute

    <div class="ca-card">
        <div class="ca-card-header">
            <h2 class="ca-card-title">{{ $libellesFiltre[$etat] ?? 'Dossiers' }}</h2>
            <span class="ca-card-meta">{{ $dossiers->total() }} dossier(s)</span>
        </div>

        <div class="ca-table-wrap">
            <table class="ca-table">
                <thead>
                <tr>
                    <th class="is-num">#</th>
                    <th>Étudiant</th>
                    <th>Date de demande</th>
                    @adminblock('admissions.personal')
                    <th>Téléphone</th>
                    <th>Adresse</th>
                    <th>Région / Ville</th>
                    <th>Date de naissance</th>
                    @endadminblock
                    @adminblock('admissions.history')
                    <th class="is-center">Demandes</th>
                    @endadminblock
                    <th>État du dossier</th>
                    <th class="is-actions"><span class="sr-only">Actions</span></th>
                </tr>
                </thead>
                <tbody>
                @forelse($dossiers as $dossier)
                    @php
                        $mots = preg_split('/\s+/', trim((string) $dossier->name)) ?: [];
                        $initiales = mb_substr($mots[0] ?? '', 0, 1).mb_substr($mots[1] ?? '', 0, 1);
                    @endphp
                    <tr>
                        <td class="is-num">{{ ($dossiers->firstItem() ?? 1) + $loop->index }}</td>
                        <td>
                            <div class="ca-person">
                                <span class="ca-avatar" aria-hidden="true">{{ $initiales ?: '?' }}</span>
                                <div>
                                    <div class="ca-strong">{{ $dossier->name }}</div>
                                    @if($dossier->derniereDemande?->numero_demande)
                                        <div class="ca-small ca-muted">Demande #{{ $dossier->derniereDemande->numero_demande }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="ca-nowrap">
                            {{ $dossier->created_at?->format('d/m/Y') }}
                            <div class="ca-small ca-muted">{{ $dossier->created_at?->format('H:i') }}</div>
                        </td>
                        @adminblock('admissions.personal')
                        <td class="ca-nowrap">{{ $dossier->telephone1 ?: '—' }}</td>
                        <td>{{ $dossier->adresse ?: '—' }}</td>
                        <td>
                            {{ optional($dossier->region)->name ?? '—' }}
                            @if(optional($dossier->ville)->name)<div class="ca-small ca-muted">{{ $dossier->ville->name }}</div>@endif
                        </td>
                        <td class="ca-nowrap">{{ $dossier->date_naissance?->format('d/m/Y') ?? '—' }}</td>
                        @endadminblock
                        @adminblock('admissions.history')
                        <td class="is-center ca-mono">{{ $dossier->demandes_count }}</td>
                        @endadminblock
                        <td>@include('admin.hebergement.campagnes_admission.partials.etat-dossier', ['etat' => $dossier->derniereDemande?->etat_dossier])</td>
                        <td class="is-actions">
                            @adminroute('admin.hebergement.campagnes_admission.show')
                            <a href="{{ route('admin.hebergement.campagnes_admission.show', ['id' => $dossier->id_etudiant]) }}"
                               class="ca-btn ca-btn-soft ca-btn-sm">
                                <x-admin-icon name="eye" size="16" /> Suivre le dossier
                            </a>
                            @endadminroute
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10">
                            <div class="ca-empty">
                                <span class="ca-empty-icon"><x-admin-icon name="folder-open" size="22" /></span>
                                <span>Aucun dossier trouvé pour ce filtre.</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($dossiers->total() > 0)
            <div class="ca-card-footer">
                <span>
                    {{ $dossiers->firstItem() }} à {{ $dossiers->lastItem() }} sur {{ $dossiers->total() }} dossier(s)
                </span>
                {{ $dossiers->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
