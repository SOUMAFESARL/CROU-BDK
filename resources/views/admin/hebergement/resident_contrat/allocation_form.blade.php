@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'residents'
])

@section('title', 'Attribuer une chambre')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/campagnes-admission.css') }}">
    <link rel="stylesheet" href="{{ asset('css/allocation-chambres.css') }}">
@endpush

@php
    $nombre = fn ($valeur) => number_format((float) $valeur, 0, ',', ' ');
    $litService = app(\App\Http\Services\LitService::class);
    $etudiantChoisi = (string) old('etudiant_id', request('etudiant_id', ''));
    $mode = old('allocation_type', 'automatique') === 'manuelle' ? 'manuelle' : 'automatique';
    $chambreChoisie = (string) old('chambre_id', '');
    $litChoisi = (string) old('numero_lit', '');
    // Libellé du niveau : le code stocké sur la demande est souvent l'identifiant du niveau.
    $niveau = function ($etudiant) {
        if (!$etudiant) return null;
        $demande = $etudiant->demandeActuelle();
        return $demande?->niveauEtudiantActuel?->name ?? $demande?->niveau_etude_actuel_code;
    };
    // Types visés par l'attribution automatique (Etudiant::getPrioriteChambres()).
    $libellesTypes = ['individuelle' => 'individuelle', 'double' => 'double', 'triple' => 'triple ou plus'];
@endphp

@section('content')
<div class="as-refonte ca-page">
    <x-breadcrumbs
            title="Attribuer une chambre"
            :items="[
                'Gestion Hébergement' => route('admin.hebergement.index'),
                'Attribution des chambres' => route('admin.allocation.index'),
                $campagne->annee_campagne => route('admin.allocation.show', $campagne->id_campagne),
                'Attribuer une chambre' => null,
            ]"
    />

    <div class="ca-header">
        <div class="ca-header-text">
            <p class="ca-subtitle">Campagne <strong>{{ $campagne->annee_campagne }}</strong> · La chambre est d’abord <strong>proposée</strong> à l’étudiant ; elle devient définitive après son accord et la validation d’un agent.</p>
        </div>
        @adminroute('admin.allocation.show')
        <div class="ca-actions">
            <a href="{{ route('admin.allocation.show', $campagne->id_campagne) }}" class="ca-btn ca-btn-outline"><x-admin-icon name="arrow-left" /> Suivi de la campagne</a>
        </div>
        @endadminroute
    </div>

    @if(session('success'))
        <div class="ca-alert ca-alert-success" role="status"><x-admin-icon name="circle-check" /> <div>{{ session('success') }}</div></div>
    @endif
    @if(session('error'))
        <div class="ca-alert ca-alert-error" role="alert"><x-admin-icon name="circle-x" /> <div>{{ session('error') }}</div></div>
    @endif
    @if($errors->any())
        <div class="ca-alert ca-alert-error" role="alert">
            <x-admin-icon name="circle-x" />
            <div>L’opération n’a pas abouti :
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        </div>
    @endif

    <div class="ca-layout">
        <div>
            @adminroute('admin.allocation.allocate')
            <form method="POST" action="{{ route('admin.allocation.allocate', $campagne->id_campagne) }}" class="ca-card" id="alAllocateForm" novalidate data-al-once>
                @csrf
                <div class="ca-card-body">
                    {{-- 1. Étudiant --}}
                    <section class="ca-section">
                        <div class="ca-section-head">
                            <span class="ca-kpi-icon tone-green"><x-admin-icon name="user-round" /></span>
                            <div>
                                <h2 class="ca-section-title">1. Étudiant</h2>
                                <p class="ca-section-desc">Dossiers éligibles de la campagne qui n’ont encore aucune chambre proposée ou occupée.</p>
                            </div>
                        </div>
                        <div class="ca-field">
                            <label class="ca-label" for="al-etudiant">Étudiant <span class="req">*</span></label>
                            <select name="etudiant_id" id="al-etudiant" class="ca-input @error('etudiant_id') is-invalid @enderror" required
                                    data-ca-select data-ca-select-search="Nom ou matricule…">
                                <option value="">{{ $etudiants->isEmpty() ? 'Aucun dossier éligible en attente' : 'Choisir un étudiant' }}</option>
                                @foreach($etudiants as $etudiant)
                                    @php
                                        $priorites = $etudiant->getPrioriteChambres();
                                        $libelleNiveau = $niveau($etudiant);
                                    @endphp
                                    <option value="{{ $etudiant->id_etudiant }}" @selected($etudiantChoisi === (string) $etudiant->id_etudiant)
                                            data-nom="{{ $etudiant->name }}"
                                            data-matricule="{{ $etudiant->matricule }}"
                                            data-email="{{ $etudiant->email1 }}"
                                            data-niveau="{{ $libelleNiveau ?? 'Non renseigné' }}"
                                            data-pmr="{{ $etudiant->pmr_confirme ? '1' : '0' }}"
                                            data-sexe="{{ $sexesEtudiants[$etudiant->id_etudiant] ?? '' }}"
                                            data-priorites="{{ collect($priorites)->map(fn ($t) => $libellesTypes[$t] ?? $t)->implode(', ') }}">
                                        {{ $etudiant->name }} — {{ $etudiant->matricule }}{{ $libelleNiveau ? ' ('.$libelleNiveau.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="ca-error" data-al-error="etudiant" hidden>Choisissez un étudiant.</span>
                        </div>
                        <div class="al-info" data-al-etudiant-info hidden>
                            <div class="al-info-grid">
                                <div class="al-info-item"><span>Matricule</span><strong class="ca-mono" data-al-info="matricule">—</strong></div>
                                <div class="al-info-item"><span>Niveau</span><strong data-al-info="niveau">—</strong></div>
                                <div class="al-info-item"><span>E-mail</span><strong data-al-info="email">—</strong></div>
                                <div class="al-info-item"><span>Statut PMR</span><strong data-al-info="pmr">—</strong></div>
                            </div>
                        </div>
                    </section>

                    {{-- 2. Mode --}}
                    <section class="ca-section">
                        <div class="ca-section-head">
                            <span class="ca-kpi-icon tone-green"><x-admin-icon name="route" /></span>
                            <div>
                                <h2 class="ca-section-title">2. Mode d’attribution</h2>
                                <p class="ca-section-desc">Le choix automatique applique les mêmes règles que l’attribution de masse.</p>
                            </div>
                        </div>
                        <fieldset class="al-modes" style="border:0;margin:0;padding:0">
                            <legend class="sr-only">Mode d’attribution</legend>
                            <label class="ca-check">
                                <input type="radio" name="allocation_type" value="automatique" @checked($mode === 'automatique') data-al-mode>
                                <span class="ca-check-body">
                                    <span class="ca-check-title">Automatique</span>
                                    <span class="ca-check-desc">Première chambre compatible, selon le niveau d’études de l’étudiant.</span>
                                </span>
                            </label>
                            <label class="ca-check">
                                <input type="radio" name="allocation_type" value="manuelle" @checked($mode === 'manuelle') data-al-mode>
                                <span class="ca-check-body">
                                    <span class="ca-check-title">Manuelle</span>
                                    <span class="ca-check-desc">Vous choisissez la chambre et, si besoin, le lit.</span>
                                </span>
                            </label>
                        </fieldset>
                    </section>

                    {{-- 3. Chambre (mode manuel) --}}
                    <section class="ca-section" data-al-chambre-section @if($mode !== 'manuelle') hidden @endif>
                        <div class="ca-section-head">
                            <span class="ca-kpi-icon tone-green"><x-admin-icon name="door-open" /></span>
                            <div>
                                <h2 class="ca-section-title">3. Chambre et lit</h2>
                                <p class="ca-section-desc">Seules les chambres actives des résidences de la campagne, avec au moins une place libre, sont proposées.</p>
                            </div>
                        </div>
                        <div class="ca-form-grid cols-2">
                            <div class="ca-field ca-span-full">
                                <label class="ca-label" for="al-chambre">Chambre <span class="req">*</span></label>
                                <select name="chambre_id" id="al-chambre" class="ca-input @error('chambre_id') is-invalid @enderror"
                                        data-ca-select data-ca-select-search="Numéro, type, bâtiment…" @disabled($mode !== 'manuelle')>
                                    <option value="">{{ $chambres->isEmpty() ? 'Aucune chambre disponible' : 'Choisir une chambre' }}</option>
                                    @foreach($chambres as $chambre)
                                        @php
                                            $occupes = $chambre->getNombrePlacesOccupees();
                                            $capacite = (int) $chambre->capacite_max;
                                            $libres = $chambre->getNombrePlacesDisponibles();
                                            $type = ucfirst($chambre->typeChambre?->name ?? 'Non défini');
                                        @endphp
                                        <option value="{{ $chambre->id_chambre }}" @selected($chambreChoisie === (string) $chambre->id_chambre) @disabled($libres <= 0)
                                                data-numero="{{ $chambre->numero_chambre }}"
                                                data-type="{{ $type }}"
                                                data-lieu="{{ collect([$chambre->batiment?->name, $chambre->cite?->name])->filter()->implode(' · ') }}"
                                                data-capacite="{{ $capacite }}"
                                                data-occupes="{{ $occupes }}"
                                                data-libres="{{ $libres }}"
                                                data-prix="{{ $nombre($chambre->prix) }}"
                                                data-pmr="{{ $chambre->pmr ? '1' : '0' }}"
                                                data-sexe="{{ $sexesChambres[$chambre->id_chambre] ?? 'incomplet' }}"
                                                data-lits="{{ json_encode($litService->disponibles($chambre)) }}">
                                            {{ $chambre->numero_chambre }} — {{ $type }}{{ $chambre->batiment ? ' · '.$chambre->batiment->name : '' }} — {{ $libres }} place(s) libre(s) sur {{ $capacite }}{{ $chambre->pmr ? ' · PMR' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="ca-error" data-al-error="chambre" hidden>Choisissez une chambre pour une attribution manuelle.</span>
                                @foreach($errors->get('chambre_id') as $message)
                                    <span class="ca-error">{{ $message }}</span>
                                @endforeach
                            </div>
                            <div class="ca-field">
                                <label class="ca-label" for="al-lit">Lit</label>
                                <select name="numero_lit" id="al-lit" class="ca-input" data-ca-select data-al-lit-initial="{{ $litChoisi }}" @disabled($mode !== 'manuelle')>
                                    <option value="">Premier lit disponible</option>
                                </select>
                                <span class="ca-hint">Laissez « Premier lit disponible » pour un choix automatique.</span>
                            </div>
                        </div>
                        <div class="al-info" data-al-chambre-info hidden>
                            <div class="al-info-grid">
                                <div class="al-info-item"><span>Type</span><strong data-al-info="type">—</strong></div>
                                <div class="al-info-item"><span>Emplacement</span><strong data-al-info="lieu">—</strong></div>
                                <div class="al-info-item"><span>Places libres</span><strong data-al-info="libres">—</strong></div>
                                <div class="al-info-item"><span>Loyer mensuel</span><strong data-al-info="prix">—</strong></div>
                            </div>
                            <div class="ca-progress" title="Occupation de la chambre"><span data-al-info="occupation" style="width:0%"></span></div>
                        </div>
                    </section>
                </div>
                <div class="ca-card-footer" style="justify-content:flex-end">
                    @adminroute('admin.allocation.show')
                    <a href="{{ route('admin.allocation.show', $campagne->id_campagne) }}" class="ca-btn ca-btn-outline">Annuler</a>
                    @endadminroute
                    <button type="submit" class="ca-btn ca-btn-primary" @disabled($etudiants->isEmpty())><x-admin-icon name="check" /> Proposer la chambre</button>
                </div>
            </form>
            @endadminroute
        </div>

        <aside class="ca-aside">
            {{-- Confirmation PMR --}}
            @adminroute('admin.allocation.pmr')
            <div class="ca-card">
                <div class="ca-card-header">
                    <h2 class="ca-card-title"><span class="ca-kpi-icon tone-purple"><x-admin-icon name="accessibility" /></span> Statut PMR</h2>
                </div>
                <form method="POST" action="{{ route('admin.allocation.pmr', $campagne->id_campagne) }}" class="ca-card-body" data-al-once>
                    @csrf
                    <p class="ca-section-desc" style="margin:0 0 12px">Enregistrez le résultat de la vérification du dossier avant de proposer une chambre adaptée.</p>
                    <div class="ca-field" style="margin-bottom:12px">
                        <label class="ca-label" for="al-pmr-etudiant">Étudiant <span class="req">*</span></label>
                        <select id="al-pmr-etudiant" name="etudiant_id" class="ca-input" required data-ca-select data-ca-select-search="Nom ou matricule…">
                            <option value="">Choisir un étudiant</option>
                            @foreach($etudiants as $etudiant)
                                <option value="{{ $etudiant->getKey() }}">{{ $etudiant->name }} — {{ $etudiant->matricule }} ({{ $etudiant->pmr_confirme ? 'PMR confirmé' : 'non confirmé' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <fieldset class="ca-field" style="border:0;margin:0 0 14px;padding:0">
                        <legend class="ca-label" style="margin-bottom:6px">Résultat de la vérification <span class="req">*</span></legend>
                        <div class="al-modes" style="grid-template-columns:minmax(0,1fr)">
                            <label class="ca-check"><input type="radio" name="pmr_confirme" value="1" required><span class="ca-check-body"><span class="ca-check-title">PMR confirmé</span></span></label>
                            <label class="ca-check"><input type="radio" name="pmr_confirme" value="0" required><span class="ca-check-body"><span class="ca-check-title">PMR non confirmé</span></span></label>
                        </div>
                    </fieldset>
                    <button type="submit" class="ca-btn ca-btn-outline ca-btn-block"><x-admin-icon name="check" /> Enregistrer la vérification</button>
                </form>
            </div>
            @endadminroute

            {{-- Résumé et pré-contrôle --}}
            <div class="ca-card">
                <div class="ca-card-header"><h2 class="ca-card-title">Résumé</h2></div>
                <div class="ca-card-body">
                    <div class="al-summary-item">
                        <span class="al-summary-label">Étudiant</span>
                        <span class="al-summary-value is-empty" data-al-summary="etudiant">Non sélectionné</span>
                        <span class="al-summary-sub" data-al-summary="niveau"></span>
                    </div>
                    <div class="al-summary-item">
                        <span class="al-summary-label">Chambre</span>
                        <span class="al-summary-value" data-al-summary="chambre">Choix automatique</span>
                        <span class="al-summary-sub" data-al-summary="chambre-sub"></span>
                    </div>
                    <div class="al-summary-item" style="border-bottom:0;padding-bottom:0">
                        <span class="al-summary-label" style="margin-bottom:8px">Pré-contrôle</span>
                        <ul class="al-checks" data-al-checks>
                            <li class="is-idle"><x-admin-icon name="info" size="16" /> Sélectionnez un étudiant.</li>
                        </ul>
                    </div>
                </div>
                <div class="ca-card-footer">
                    <span>{{ $nombre($etudiants->count()) }} étudiant(s) sans chambre</span>
                    <span>{{ $nombre($chambres->count()) }} chambre(s) disponible(s)</span>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/ca-select.js') }}"></script>
    <script src="{{ asset('js/campagnes-admission.js') }}"></script>
    <script src="{{ asset('js/allocation-chambres.js') }}"></script>
@endpush
