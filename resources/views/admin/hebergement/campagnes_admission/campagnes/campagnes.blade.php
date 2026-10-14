@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'campagnes'
])

@section('title', 'Campagnes')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/campagnes-admission.css') }}">
@endpush

@php
    // [filtre, libellé, clé des statistiques, icône, couleur]
    $filtres = [
        ['tous', 'Toutes les campagnes', 'total', 'calendar', 'kpi-blue'],
        ['active', 'Actives', 'active', 'circle-play', 'kpi-green'],
        ['planifier', 'Planifiées', 'planifier', 'calendar-clock', 'kpi-violet'],
        ['terminer', 'Terminées', 'terminer', 'archive', 'kpi-primary'],
    ];
    $statutsCampagne = [
        'active' => ['Active', 'tone-green'],
        'planifier' => ['Planifiée', 'tone-blue'],
        'terminer' => ['Terminée', 'tone-gray'],
    ];
    $titresFiltre = [
        'tous' => 'Toutes les campagnes', 'active' => 'Campagnes actives',
        'planifier' => 'Campagnes planifiées', 'terminer' => 'Campagnes terminées',
    ];
    $niveaux = [
        'L1' => 'Licence 1', 'L2' => 'Licence 2', 'L3' => 'Licence 3', 'M1' => 'Master 1',
        'M2' => 'Master 2', 'D1' => 'Doctorat 1', 'D2' => 'Doctorat 2', 'D3' => 'Doctorat 3',
    ];
    // Critères oui / non : [champ, libellé, description]
    $typesAdmission = [
        ['admission', 'Admission', 'Premières demandes de logement'],
        ['readmission', 'Réadmission', 'Étudiants déjà logés qui renouvellent'],
        ['internationnaux', 'Étudiants internationaux', 'Ouvrir la campagne aux étudiants étrangers'],
    ];
    $conditions = [
        ['redoublement', 'Redoublants autorisés', 'Les étudiants redoublants peuvent postuler'],
        ['pmr', 'Étudiants PMR', 'Accepter les étudiants à mobilité réduite'],
        ['is_participate', 'Vie en résidence', 'Participer aux activités organisées par le CROU Bondoukou'],
        ['is_discipline', 'Étudiant discipliné', 'N’avoir causé aucun trouble à l’ordre ou à la discipline'],
    ];
    $oldResidences = (array) old('residences', []);
    $oldDocuments = (array) old('documents', []);
    $oui = fn ($v) => $v ? 'Oui' : 'Non';
@endphp

@section('content')
<div class="as-refonte ca-page">
    <x-breadcrumbs
            title="Campagnes"
            :items="[
        'Gestion Hébergement' => route('admin.hebergement.index'),
        'Campagnes' => null,
    ]"
    />

    <div class="ca-header">
        <div class="ca-header-text">
            <p class="ca-subtitle">Planifiez les campagnes d’admission et de réadmission, leurs critères et les résidences ouvertes.</p>
        </div>
        <div class="ca-actions">
            @adminroute('admin.hebergement.campagnes_admission.admission')
            <a href="{{ route('admin.hebergement.campagnes_admission.admission') }}" class="ca-btn ca-btn-outline">
                <x-admin-icon name="clipboard-list" /> Admissions
            </a>
            @endadminroute
            @adminroute('admin.hebergement.campagnes_admission.post_campagnes')
            <button type="button" class="ca-btn ca-btn-primary" data-ca-open="campagne-form">
                <x-admin-icon name="plus" /> Nouvelle campagne
            </button>
            @endadminroute
        </div>
    </div>

    {{-- Messages --}}
    @if(session('success'))
        <div class="ca-alert ca-alert-success" role="status"><x-admin-icon name="circle-check" /> <div>{{ session('success') }}</div></div>
    @endif
    @if(session('error'))
        <div class="ca-alert ca-alert-error" role="alert"><x-admin-icon name="circle-x" /> <div>{{ session('error') }}</div></div>
    @endif
    @if($errors->any())
        <div class="ca-alert ca-alert-error" role="alert">
            <x-admin-icon name="circle-x" />
            <div>La campagne n’a pas été enregistrée :
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        </div>
    @endif

    {{-- Indicateurs / filtres --}}
    @adminroute('admin.hebergement.campagnes_admission.campagnes')
    <div class="ca-kpis ca-kpis--teintes">
        @foreach($filtres as [$filtre, $libelle, $cle, $icone, $couleur])
            <a href="{{ route('admin.hebergement.campagnes_admission.campagnes', ['etat' => $filtre]) }}"
               class="ca-kpi {{ $couleur }} {{ $etat == $filtre ? 'is-active' : '' }}"
               @if($etat == $filtre) aria-current="page" @endif>
                <span class="ca-kpi-label">{{ $libelle }}</span>
                <span class="ca-kpi-row">
                    <strong class="ca-kpi-value">{{ $stats[$cle] ?? 0 }}</strong>
                </span>
            </a>
        @endforeach
    </div>
    @endadminroute

    {{-- Liste des campagnes --}}
    <div class="ca-card">
        <div class="ca-card-header">
            <h2 class="ca-card-title">{{ $titresFiltre[$etat] ?? 'Campagnes' }}</h2>
            <span class="ca-card-meta">{{ count($campagnes) }} campagne(s)</span>
        </div>
        <div class="ca-table-wrap">
            <table class="ca-table">
                <thead>
                <tr>
                    <th class="is-num">#</th>
                    <th>Campagne</th>
                    <th>Période</th>
                    <th>Type</th>
                    <th>Statut</th>
                    <th>Documents requis</th>
                    <th>Résidences (places libres)</th>
                    <th class="is-actions"><span class="sr-only">Actions</span></th>
                </tr>
                </thead>
                <tbody>
                @forelse($campagnes as $campagne)
                    @php
                        $type = $campagne->admission && $campagne->readmission ? 'Admission et réadmission'
                            : ($campagne->admission ? 'Admission' : 'Réadmission');
                        [$libelleStatut, $toneStatut] = $statutsCampagne[$campagne->statut] ?? ['Terminée', 'tone-gray'];
                        $documentsCampagne = $campagne->documentCampagnes->flatMap->documents;
                        $residencesCampagne = $campagne->residences->flatMap->cites->map(fn ($cite) => [
                            'name' => $cite->name,
                            'places' => $cite->batiment->sum(fn ($batiment) => $batiment->chambresDisponibles->sum('capacite_max')),
                        ]);
                    @endphp
                    <tr>
                        <td class="is-num">{{ $loop->iteration }}</td>
                        <td>
                            <div class="ca-strong ca-nowrap">Campagne {{ $campagne->annee_campagne }}</div>
                            @if($campagne->description)
                                <div class="ca-small ca-muted" style="max-width: 260px">{{ \Illuminate\Support\Str::limit($campagne->description, 70) }}</div>
                            @endif
                        </td>
                        <td class="ca-nowrap">
                            {{ \Carbon\Carbon::parse($campagne->periode_debut)->format('d/m/Y') }}
                            <span class="ca-muted">→</span>
                            {{ \Carbon\Carbon::parse($campagne->periode_fin)->format('d/m/Y') }}
                        </td>
                        <td><span class="ca-badge tone-green">{{ $type }}</span></td>
                        <td><span class="ca-badge {{ $toneStatut }}">{{ $libelleStatut }}</span></td>
                        <td>
                            <div class="ca-chips">
                                @forelse($documentsCampagne->take(3) as $doc)
                                    <span class="ca-badge tone-blue">{{ $doc->name }}</span>
                                @empty
                                    <span class="ca-muted">—</span>
                                @endforelse
                                @if($documentsCampagne->count() > 3)
                                    <span class="ca-badge tone-gray">+{{ $documentsCampagne->count() - 3 }}</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="ca-chips">
                                @forelse($residencesCampagne as $residence)
                                    <span class="ca-badge tone-green">{{ $residence['name'] }} ({{ $residence['places'] }})</span>
                                @empty
                                    <span class="ca-muted">—</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="is-actions">
                            <button type="button" class="ca-icon-btn" data-ca-open="campagne-detail-{{ $loop->index }}"
                                    title="Voir le détail" aria-label="Voir le détail de la campagne {{ $campagne->annee_campagne }}">
                                <x-admin-icon name="eye" />
                            </button>

                            {{-- Détail de la campagne --}}
                            <dialog id="campagne-detail-{{ $loop->index }}" class="ca-dialog is-narrow" aria-labelledby="campagne-detail-title-{{ $loop->index }}" style="text-align: left; white-space: normal">
                                <div class="ca-dialog-head">
                                    <div>
                                        <h3 class="ca-dialog-title" id="campagne-detail-title-{{ $loop->index }}">Campagne {{ $campagne->annee_campagne }}</h3>
                                        <div class="flex flex-wrap gap-2 mt-2">
                                            <span class="ca-badge {{ $toneStatut }}">{{ $libelleStatut }}</span>
                                            <span class="ca-badge tone-green">{{ $type }}</span>
                                        </div>
                                    </div>
                                    <button type="button" class="ca-icon-btn" data-ca-close aria-label="Fermer"><x-admin-icon name="x" /></button>
                                </div>
                                <div class="ca-dialog-body">
                                    @if($campagne->description)
                                        <p style="margin: 0 0 16px">{{ $campagne->description }}</p>
                                    @endif

                                    <h4 class="ca-dl-title">Calendrier</h4>
                                    <dl class="ca-dl">
                                        <div class="ca-dl-row"><dt>Ouverture</dt><dd>{{ \Carbon\Carbon::parse($campagne->periode_debut)->format('d/m/Y') }}</dd></div>
                                        <div class="ca-dl-row"><dt>Clôture</dt><dd>{{ \Carbon\Carbon::parse($campagne->periode_fin)->format('d/m/Y') }}</dd></div>
                                        <div class="ca-dl-row"><dt>Échéance des demandes</dt><dd>{{ $campagne->date_echeance_demande ? \Carbon\Carbon::parse($campagne->date_echeance_demande)->format('d/m/Y') : '—' }}</dd></div>
                                        <div class="ca-dl-row"><dt>Échéance des contrats</dt><dd>{{ $campagne->date_echeance_contrat ? \Carbon\Carbon::parse($campagne->date_echeance_contrat)->format('d/m/Y') : '—' }}</dd></div>
                                    </dl>

                                    <h4 class="ca-dl-title">Critères d’éligibilité</h4>
                                    <dl class="ca-dl">
                                        <div class="ca-dl-row"><dt>Moyenne minimale</dt><dd>{{ $campagne->moyenne_min !== null ? $campagne->moyenne_min.'/20' : '—' }}</dd></div>
                                        <div class="ca-dl-row"><dt>Crédits validés</dt><dd>{{ $campagne->pourcentage_validate !== null ? $campagne->pourcentage_validate.' %' : '—' }}</dd></div>
                                        <div class="ca-dl-row"><dt>Distance domicile – campus</dt><dd>{{ $campagne->distance_min !== null ? $campagne->distance_min.' km' : '—' }}</dd></div>
                                        @foreach($niveaux as $code => $niveau)
                                            @if($campagne->{'age_'.$code.'_max'} !== null)
                                                <div class="ca-dl-row"><dt>Âge maximum {{ $niveau }}</dt><dd>{{ $campagne->{'age_'.$code.'_max'} }} ans</dd></div>
                                            @endif
                                        @endforeach
                                    </dl>

                                    <h4 class="ca-dl-title">Conditions</h4>
                                    <dl class="ca-dl">
                                        @foreach(array_merge($typesAdmission, $conditions) as [$champ, $libelle])
                                            <div class="ca-dl-row"><dt>{{ $libelle }}</dt><dd>{{ $oui($campagne->{$champ}) }}</dd></div>
                                        @endforeach
                                    </dl>

                                    <h4 class="ca-dl-title">Résidences concernées</h4>
                                    <div class="ca-chips" style="max-width: none">
                                        @forelse($residencesCampagne as $residence)
                                            <span class="ca-badge tone-green">{{ $residence['name'] }} — {{ $residence['places'] }} place(s) libre(s)</span>
                                        @empty
                                            <span class="ca-muted">Aucune</span>
                                        @endforelse
                                    </div>

                                    <h4 class="ca-dl-title" style="margin-top: 16px">Documents requis</h4>
                                    <div class="ca-chips" style="max-width: none">
                                        @forelse($documentsCampagne as $doc)
                                            <span class="ca-badge tone-blue">{{ $doc->name }}</span>
                                        @empty
                                            <span class="ca-muted">Aucun</span>
                                        @endforelse
                                    </div>
                                </div>
                                <div class="ca-dialog-foot">
                                    <button type="button" class="ca-btn ca-btn-outline" data-ca-close>Fermer</button>
                                </div>
                            </dialog>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="ca-empty">
                                <span class="ca-empty-icon"><x-admin-icon name="calendar" size="22" /></span>
                                <span>Aucune campagne trouvée pour ce filtre.</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Création d'une campagne --}}
    @adminroute('admin.hebergement.campagnes_admission.post_campagnes')
    <dialog id="campagne-form" class="ca-dialog" aria-labelledby="campagne-form-title" @if($errors->any()) data-ca-autoopen @endif>
        <form id="campagneForm" method="POST" action="{{ route('admin.hebergement.campagnes_admission.post_campagnes') }}" novalidate>
            @csrf
            <div class="ca-dialog-head">
                <div>
                    <h3 class="ca-dialog-title" id="campagne-form-title">Nouvelle campagne</h3>
                    <p class="ca-subtitle">Les champs marqués <span style="color:#d92d20">*</span> sont obligatoires.</p>
                </div>
                <button type="button" class="ca-icon-btn" data-ca-close aria-label="Fermer"><x-admin-icon name="x" /></button>
            </div>

            <div class="ca-dialog-body">
                <div id="campagneFormErrors" class="ca-alert ca-alert-error" role="alert" hidden></div>

                {{-- Informations générales --}}
                <section class="ca-section">
                    <div class="ca-section-head">
                        <span class="ca-kpi-icon tone-green"><x-admin-icon name="info" /></span>
                        <div>
                            <h4 class="ca-section-title">Informations générales</h4>
                            <p class="ca-section-desc">Année universitaire et présentation de la campagne.</p>
                        </div>
                    </div>
                    <div class="ca-form-grid">
                        <div class="ca-field">
                            <label for="annee_campagne" class="ca-label">Année universitaire <span class="req">*</span></label>
                            <input type="text" id="annee_campagne" name="annee_campagne"
                                   class="ca-input @error('annee_campagne') is-invalid @enderror"
                                   value="{{ old('annee_campagne', $suggestedYear ?? '') }}"
                                   placeholder="2025-2026" pattern="\d{4}-\d{4}" maxlength="9" inputmode="numeric" autocomplete="off" required>
                            <span class="ca-hint">Format AAAA-AAAA, deux années consécutives.</span>
                            @error('annee_campagne')<span class="ca-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="ca-field">
                            <label for="description" class="ca-label">Description <span class="req">*</span></label>
                            <textarea id="description" name="description" rows="3" minlength="10" maxlength="1000" required
                                      class="ca-input @error('description') is-invalid @enderror"
                                      placeholder="Objectifs et modalités de la campagne…">{{ old('description') }}</textarea>
                            <span class="ca-hint">Entre 10 et 1 000 caractères.</span>
                            @error('description')<span class="ca-error">{{ $message }}</span>@enderror
                        </div>
                    </div>
                </section>

                {{-- Calendrier --}}
                <section class="ca-section">
                    <div class="ca-section-head">
                        <span class="ca-kpi-icon tone-blue"><x-admin-icon name="calendar" /></span>
                        <div>
                            <h4 class="ca-section-title">Calendrier</h4>
                            <p class="ca-section-desc">Chaque date doit être postérieure à la précédente.</p>
                        </div>
                    </div>
                    <div class="ca-form-grid cols-2">
                        @foreach([
                            ['periode_debut', 'Ouverture de la campagne', 'Les étudiants peuvent postuler à partir de cette date'],
                            ['periode_fin', 'Clôture de la campagne', 'Date limite de dépôt des candidatures'],
                            ['date_echeance_demande', 'Échéance des demandes', 'Date limite d’analyse des demandes'],
                            ['date_echeance_contrat', 'Échéance des contrats', 'Date limite de signature des contrats'],
                        ] as [$champ, $libelle, $aide])
                            <div class="ca-field">
                                <label for="{{ $champ }}" class="ca-label">{{ $libelle }} <span class="req">*</span></label>
                                <input type="date" id="{{ $champ }}" name="{{ $champ }}" value="{{ old($champ) }}" required
                                       class="ca-input @error($champ) is-invalid @enderror">
                                <span class="ca-hint">{{ $aide }}</span>
                                @error($champ)<span class="ca-error">{{ $message }}</span>@enderror
                            </div>
                        @endforeach
                    </div>
                </section>

                {{-- Critères d'éligibilité --}}
                <section class="ca-section">
                    <div class="ca-section-head">
                        <span class="ca-kpi-icon tone-amber"><x-admin-icon name="list-checks" /></span>
                        <div>
                            <h4 class="ca-section-title">Critères d’éligibilité</h4>
                            <p class="ca-section-desc">Laisser vide un critère qui ne s’applique pas.</p>
                        </div>
                    </div>
                    <div class="ca-form-grid cols-3">
                        <div class="ca-field">
                            <label for="moyenne_min" class="ca-label">Moyenne minimale</label>
                            <div class="ca-input-addon">
                                <input type="number" id="moyenne_min" name="moyenne_min" min="0" max="20" step="0.01" value="{{ old('moyenne_min') }}"
                                       class="ca-input @error('moyenne_min') is-invalid @enderror">
                                <span>/ 20</span>
                            </div>
                            @error('moyenne_min')<span class="ca-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="ca-field">
                            <label for="pourcentage_validate" class="ca-label">Crédits validés minimum</label>
                            <div class="ca-input-addon">
                                <input type="number" id="pourcentage_validate" name="pourcentage_validate" min="0" max="100" step="1" value="{{ old('pourcentage_validate') }}"
                                       class="ca-input @error('pourcentage_validate') is-invalid @enderror">
                                <span>%</span>
                            </div>
                            <span class="ca-hint">Ne s’applique pas aux étudiants de L1.</span>
                            @error('pourcentage_validate')<span class="ca-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="ca-field">
                            <label for="distance_min" class="ca-label">Distance domicile – campus</label>
                            <div class="ca-input-addon">
                                <input type="number" id="distance_min" name="distance_min" min="0" step="0.01" value="{{ old('distance_min') }}"
                                       class="ca-input @error('distance_min') is-invalid @enderror">
                                <span>km</span>
                            </div>
                            <span class="ca-hint">Distance minimale exigée.</span>
                            @error('distance_min')<span class="ca-error">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <p class="ca-subhead" style="margin-top: 20px">Âge maximum par niveau</p>
                    <div class="ca-form-grid cols-4">
                        @foreach($niveaux as $code => $niveau)
                            @php $champ = 'age_'.$code.'_max'; @endphp
                            <div class="ca-field">
                                <label for="{{ $champ }}" class="ca-label">{{ $niveau }}</label>
                                <div class="ca-input-addon">
                                    <input type="number" id="{{ $champ }}" name="{{ $champ }}" min="0" max="40" step="1"
                                           value="{{ old($champ) }}" data-ca-age
                                           class="ca-input @error($champ) is-invalid @enderror">
                                    <span>ans</span>
                                </div>
                                @error($champ)<span class="ca-error">{{ $message }}</span>@enderror
                            </div>
                        @endforeach
                    </div>
                    <span class="ca-hint" style="display: block; margin-top: 8px">Chaque niveau doit avoir un âge maximum supérieur à celui du niveau précédent (40 ans au plus).</span>
                </section>

                {{-- Types d'admission et conditions --}}
                <section class="ca-section">
                    <div class="ca-section-head">
                        <span class="ca-kpi-icon tone-purple"><x-admin-icon name="users" /></span>
                        <div>
                            <h4 class="ca-section-title">Public et conditions</h4>
                            <p class="ca-section-desc">Au moins un type : admission ou réadmission.</p>
                        </div>
                    </div>
                    <p class="ca-subhead">Types d’admission</p>
                    <div class="ca-checks cols-3">
                        @foreach($typesAdmission as [$champ, $libelle, $aide])
                            <label class="ca-check" for="{{ $champ }}">
                                <input type="checkbox" id="{{ $champ }}" name="{{ $champ }}" value="1" @checked(old($champ))>
                                <span class="ca-check-body">
                                    <span class="ca-check-title">{{ $libelle }}</span>
                                    <span class="ca-check-desc">{{ $aide }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <p class="ca-subhead" style="margin-top: 16px">Conditions</p>
                    <div class="ca-checks">
                        @foreach($conditions as [$champ, $libelle, $aide])
                            <label class="ca-check" for="{{ $champ }}">
                                <input type="checkbox" id="{{ $champ }}" name="{{ $champ }}" value="1" @checked(old($champ))>
                                <span class="ca-check-body">
                                    <span class="ca-check-title">{{ $libelle }}</span>
                                    <span class="ca-check-desc">{{ $aide }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </section>

                {{-- Résidences --}}
                <section class="ca-section">
                    <div class="ca-section-head">
                        <span class="ca-kpi-icon tone-green"><x-admin-icon name="building" /></span>
                        <div>
                            <h4 class="ca-section-title">Résidences concernées <span class="req" style="color:#d92d20">*</span></h4>
                            <p class="ca-section-desc">Cités ouvertes et places actuellement libres.</p>
                        </div>
                    </div>
                    @if(count($residences))
                        <div class="ca-checks ca-check-list">
                            @foreach($residences as $residence)
                                @php
                                    $chambresLibres = $residence->batiment->sum(fn ($batiment) => $batiment->chambresDisponibles->count());
                                    $placesLibres = $residence->batiment->sum(fn ($batiment) => $batiment->chambresDisponibles->sum('capacite_max'));
                                @endphp
                                <label class="ca-check" for="residence_{{ $residence->id_cite }}">
                                    <input type="checkbox" id="residence_{{ $residence->id_cite }}" name="residences[]"
                                           value="{{ $residence->id_cite }}" data-places="{{ $placesLibres }}" class="residence-checkbox"
                                           @checked(in_array($residence->id_cite, $oldResidences))>
                                    <span class="ca-check-body">
                                        <span class="ca-check-title">
                                            {{ $residence->name }}
                                            <span class="ca-badge {{ $placesLibres > 0 ? 'tone-green' : 'tone-gray' }}">{{ $placesLibres }} place(s)</span>
                                        </span>
                                        <span class="ca-check-desc" style="{{ $chambresLibres ? '' : 'color:#b42318' }}">
                                            {{ $chambresLibres ? $chambresLibres.' chambre(s) disponible(s)' : 'Aucune chambre disponible' }}
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <div class="ca-summary" aria-live="polite">
                            <span>Résidences sélectionnées : <strong id="selectedCount">0</strong></span>
                            <span>Places libres : <strong id="totalPlaces">0</strong></span>
                        </div>
                    @else
                        <div class="ca-empty">
                            <span class="ca-empty-icon"><x-admin-icon name="building" size="22" /></span>
                            <span>Aucune résidence ouverte. Ouvrez une cité dans le parc immobilier avant de créer une campagne.</span>
                        </div>
                    @endif
                    @error('residences')<span class="ca-error">{{ $message }}</span>@enderror
                </section>

                {{-- Documents requis --}}
                <section class="ca-section">
                    <div class="ca-section-head">
                        <span class="ca-kpi-icon tone-blue"><x-admin-icon name="file-text" /></span>
                        <div style="flex: 1">
                            <h4 class="ca-section-title">Documents requis</h4>
                            <p class="ca-section-desc">Pièces que l’étudiant doit fournir avec sa demande.</p>
                        </div>
                        @if(count($document))
                            <span class="ca-small">
                                <button type="button" class="ca-link-btn" data-ca-docs="1">Tout sélectionner</button>
                                <span class="ca-muted">·</span>
                                <button type="button" class="ca-link-btn" data-ca-docs="0">Aucun</button>
                            </span>
                        @endif
                    </div>
                    <div class="ca-checks cols-3">
                        @forelse($document as $key => $doc)
                            <label class="ca-check" for="doc_{{ $key }}">
                                <input type="checkbox" id="doc_{{ $key }}" name="documents[]" value="{{ $doc->id_document }}" class="document-checkbox"
                                       @checked(in_array($doc->id_document, $oldDocuments))>
                                <span class="ca-check-body"><span class="ca-check-title">{{ $doc->name }}</span></span>
                            </label>
                        @empty
                            <span class="ca-muted">Aucun document configuré.</span>
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="ca-dialog-foot">
                <button type="button" class="ca-btn ca-btn-outline" data-ca-close>Annuler</button>
                <button type="submit" class="ca-btn ca-btn-primary"><x-admin-icon name="check" /> Enregistrer la campagne</button>
            </div>
        </form>
    </dialog>
    @endadminroute
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/campagnes-admission.js') }}"></script>
@endpush
