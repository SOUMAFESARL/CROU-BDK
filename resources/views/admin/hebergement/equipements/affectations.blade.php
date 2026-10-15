@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'equipements'
])

@section('title', 'Affectations au parc')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/campagnes-admission.css') }}">
    <style>
        .af-list-header { flex-direction: column; align-items: stretch; }
        .af-toolbar { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: flex-start; }
        .af-toolbar .ca-input { width: auto; min-width: 0; }
        .af-toolbar .ca-select-trigger { min-width: 200px; min-height: 40px; }
        .af-search { position: relative; flex: 0 1 300px; }
        .af-search.is-loading svg { animation: af-pulse 1s ease-in-out infinite; }
        @keyframes af-pulse { 50% { opacity: .35; } }
        [data-af-results] { transition: opacity .15s; }
        [data-af-results].is-loading { opacity: .55; }
        .af-search .ca-input { width: 100%; padding-left: 36px; }
        .af-search svg { position: absolute; top: 50%; left: 12px; transform: translateY(-50%); color: #8a9087; pointer-events: none; }
        .af-thumb { display: inline-flex; flex: none; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: var(--ca-radius); color: #a15c00; background: #fff4d6; }
        .af-place { display: flex; flex-direction: column; gap: 2px; }
        .af-place .ca-muted { display: flex; align-items: center; gap: 4px; }
        .af-target { margin: 0 0 16px; padding: 10px 12px; border-radius: var(--ca-radius); background: var(--ca-surface); font-size: 13px; color: var(--ca-muted); }
        .af-target strong { color: var(--ca-text); font-weight: 500; }
        .af-radios { display: grid; grid-template-columns: minmax(0, 1fr); gap: 10px; }
        .af-radios input[type="radio"] { flex: none; width: 16px; height: 16px; margin: 2px 0 0; accent-color: var(--ca-primary); }
        .af-stock-info { display: flex; align-items: center; gap: 6px; }
        .af-stock-info[hidden] { display: none; }
        @media (min-width: 640px) { .af-radios { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (max-width: 639px) { .af-toolbar .ca-input, .af-toolbar .ca-btn, .af-toolbar .ca-select { flex: 1 1 100%; } }
    </style>
@endpush

@php
    // [statut, libellé, couleur]
    $filtres = [
        ['actives', 'Affectations en cours', 'kpi-blue'],
        ['a_reparer', 'Matériel à remettre en état', 'kpi-amber'],
        ['retournees', 'Retours au magasin', 'kpi-green'],
    ];
    $titres = [
        'actives' => 'Matériel affecté au parc',
        'a_reparer' => 'Matériel à remettre en état',
        'retournees' => 'Matériel retourné au magasin',
    ];
    $etats = [
        'bon' => ['Bon', 'tone-green', 'circle-check', 'Utilisable, peut revenir au magasin'],
        'moyen' => ['Moyen', 'tone-amber', 'triangle-alert', 'Usé, à surveiller'],
        'defectueux' => ['Défectueux', 'tone-red', 'circle-x', 'Hors d’usage, à réparer'],
    ];
    $nombre = fn ($valeur) => number_format((float) $valeur, 0, ',', ' ');
    $formulaireOuvert = $errors->any() ? old('_form') : null;
    $filtresActifs = $recherche !== '' || request()->filled('cite_id');
    $parametres = array_filter(['q' => $recherche ?: null, 'cite_id' => request('cite_id')]);
@endphp

@section('content')
<div class="as-refonte ca-page">
    <x-breadcrumbs
            title="Affectations au parc"
            :items="[
                'Gestion Hébergement' => route('admin.hebergement.index'),
                'Équipements' => route('admin.hebergement.equipements.index'),
                'Affectations au parc' => null,
            ]"
    />

    <div class="ca-header">
        <div class="ca-header-text">
            <p class="ca-subtitle">Chaque affectation retire la quantité du magasin ; un retour la réintègre. Le matériel en état moyen ou défectueux doit être remis en bon état avant son retour.</p>
        </div>
        <div class="ca-actions">
            @adminroute('admin.hebergement.equipements.index')
            <a href="{{ route('admin.hebergement.equipements.index') }}" class="ca-btn ca-btn-outline">
                <x-admin-icon name="package" /> Catalogue du magasin
            </a>
            @endadminroute
            @adminroute('admin.hebergement.equipements.affectations.store')
            <button type="button" class="ca-btn ca-btn-primary" data-ca-open="af-store-form">
                <x-admin-icon name="plus" /> Affecter un équipement
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
    @if($errors->any() && $formulaireOuvert !== 'store')
        <div class="ca-alert ca-alert-error" role="alert">
            <x-admin-icon name="circle-x" />
            <div>L’opération n’a pas abouti :
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        </div>
    @endif

    {{-- Indicateurs / filtres --}}
    <div class="ca-kpis ca-kpis--teintes" data-af-kpis>
        @foreach($filtres as [$filtre, $libelle, $couleur])
            <a href="{{ route('admin.hebergement.equipements.affectations.index', $parametres + ['statut' => $filtre]) }}"
               class="ca-kpi {{ $couleur }} {{ $statut === $filtre ? 'is-active' : '' }}"
               @if($statut === $filtre) aria-current="page" @endif>
                <span class="ca-kpi-label">{{ $libelle }}</span>
                <span class="ca-kpi-row">
                    <strong class="ca-kpi-value">{{ $nombre($stats[$filtre] ?? 0) }}</strong>
                    @if($filtre === 'actives')
                        <span class="ca-kpi-badge">{{ $nombre($stats['unites']) }} unité(s)</span>
                    @elseif($filtre === 'a_reparer')
                        <span class="ca-kpi-badge">{{ $nombre($stats['defectueux']) }} défectueuse(s)</span>
                    @endif
                </span>
            </a>
        @endforeach
        <div class="ca-kpi kpi-primary">
            <span class="ca-kpi-label">Disponible en magasin</span>
            <span class="ca-kpi-row">
                <strong class="ca-kpi-value">{{ $nombre($stats['magasin']) }}</strong>
                <span class="ca-kpi-badge" style="background:var(--ca-accent);color:#1f2937">unité(s)</span>
            </span>
        </div>
    </div>

    {{-- Liste des affectations --}}
    <div class="ca-card">
        <div class="ca-card-header af-list-header">
            <h2 class="ca-card-title">{{ $titres[$statut] ?? 'Affectations' }}</h2>
            <form method="GET" action="{{ route('admin.hebergement.equipements.affectations.index') }}" class="af-toolbar" role="search" data-af-filters>
                <input type="hidden" name="statut" value="{{ $statut }}">
                <label class="af-search" data-af-search>
                    <span class="sr-only">Rechercher un équipement</span>
                    <x-admin-icon name="search" size="16" />
                    <input type="search" name="q" value="{{ $recherche }}" class="ca-input" placeholder="Rechercher un équipement…" autocomplete="off">
                </label>
                <label class="sr-only" for="af-filter-cite">Cité</label>
                <select id="af-filter-cite" name="cite_id" class="ca-input" data-ca-select>
                    <option value="">Toutes les cités</option>
                    @foreach($cites as $cite)
                        <option value="{{ $cite->id_cite }}" @selected(request('cite_id') === $cite->id_cite)>{{ $cite->name }}</option>
                    @endforeach
                </select>
                <a href="{{ route('admin.hebergement.equipements.affectations.index', ['statut' => $statut]) }}" class="ca-link-btn" data-af-reset @unless($filtresActifs) hidden @endunless>Réinitialiser</a>
            </form>
        </div>

        <div data-af-results aria-live="polite">
        <div class="ca-table-wrap">
            <table class="ca-table">
                <thead>
                <tr>
                    <th class="is-num">#</th>
                    <th>Équipement</th>
                    <th>Emplacement</th>
                    <th>Quantité</th>
                    <th>État</th>
                    <th>Suivi</th>
                    <th class="is-actions"><span class="sr-only">Actions</span></th>
                </tr>
                </thead>
                <tbody>
                @forelse($affectations as $item)
                    @php
                        [$libelleEtat, $toneEtat, $iconeEtat] = $etats[$item->etat] ?? $etats['bon'];
                        $nomEquipement = $item->equipement?->name ?? 'Équipement indisponible';
                        $batiment = $item->batiment?->name ?? $item->batiment?->code;
                        $lieu = collect([
                            $item->cite?->name ?? $item->cite_id,
                            $batiment,
                            $item->chambre_id ? 'Chambre '.($item->chambre?->numero_chambre ?? $item->chambre_id) : null,
                        ])->filter()->implode(' · ');
                    @endphp
                    <tr>
                        <td class="is-num">{{ ($affectations->firstItem() ?? 1) + $loop->index }}</td>
                        <td>
                            <div class="ca-person">
                                <span class="af-thumb" aria-hidden="true"><x-admin-icon name="package" /></span>
                                <div>
                                    <span class="ca-strong">{{ $nomEquipement }}</span>
                                    @if($item->notes)
                                        <span class="ca-muted ca-small" style="display:block" title="{{ $item->notes }}">{{ \Illuminate\Support\Str::limit($item->notes, 60) }}</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="af-place">
                                <span>{{ $item->cite?->name ?? $item->cite_id }}</span>
                                <span class="ca-muted ca-small">
                                    @if($item->chambre_id)
                                        <x-admin-icon name="door-open" size="14" /> {{ $batiment ?? 'Bâtiment' }} · Chambre {{ $item->chambre?->numero_chambre ?? $item->chambre_id }}
                                    @elseif($item->batiment_id)
                                        <x-admin-icon name="building" size="14" /> {{ $batiment }} · commun au bâtiment
                                    @else
                                        <x-admin-icon name="houses" size="14" /> Commun à la cité
                                    @endif
                                </span>
                            </div>
                        </td>
                        <td class="ca-mono ca-strong">{{ $nombre($item->quantite) }}</td>
                        <td><span class="ca-badge {{ $toneEtat }}"><x-admin-icon :name="$iconeEtat" size="14" /> {{ $libelleEtat }}</span></td>
                        <td class="ca-small">
                            @if($item->returned_at)
                                <span class="ca-badge tone-gray"><x-admin-icon name="archive" size="14" /> Retourné</span>
                                <span class="ca-muted" style="display:block;margin-top:4px">le {{ $item->returned_at->format('d/m/Y à H:i') }}</span>
                            @else
                                <span class="ca-badge tone-blue"><x-admin-icon name="map-pin" size="14" /> Affecté</span>
                                <span class="ca-muted" style="display:block;margin-top:4px">depuis le {{ $item->created_at?->format('d/m/Y') }}</span>
                            @endif
                        </td>
                        <td class="is-actions">
                            @if(!$item->returned_at)
                                @adminroute('admin.hebergement.equipements.affectations.update')
                                <button type="button" class="ca-icon-btn" title="Mettre à jour l’état" aria-label="Mettre à jour l’état de {{ $nomEquipement }}"
                                        data-af-etat data-action="{{ route('admin.hebergement.equipements.affectations.update', $item) }}"
                                        data-name="{{ $nomEquipement }}" data-lieu="{{ $lieu }}" data-etat="{{ $item->etat }}">
                                    <x-admin-icon name="pencil" />
                                </button>
                                @endadminroute
                                @adminroute('admin.hebergement.equipements.affectations.retour')
                                @if($item->etat === 'bon')
                                    <button type="button" class="ca-icon-btn" title="Retour au magasin" aria-label="Retourner {{ $nomEquipement }} au magasin"
                                            data-af-retour data-action="{{ route('admin.hebergement.equipements.affectations.retour', $item) }}"
                                            data-name="{{ $nomEquipement }}" data-lieu="{{ $lieu }}" data-quantite="{{ $nombre($item->quantite) }}">
                                        <x-admin-icon name="arrow-left-right" />
                                    </button>
                                @else
                                    <button type="button" class="ca-icon-btn" disabled title="Remettez le matériel en bon état avant son retour au magasin" aria-label="Retour impossible : matériel à remettre en bon état">
                                        <x-admin-icon name="arrow-left-right" />
                                    </button>
                                @endif
                                @endadminroute
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="ca-empty">
                                <span class="ca-empty-icon"><x-admin-icon name="package" size="22" /></span>
                                @if($filtresActifs)
                                    <span>Aucune affectation ne correspond à ces critères.</span>
                                    <a href="{{ route('admin.hebergement.equipements.affectations.index', ['statut' => $statut]) }}" class="ca-link-btn">Effacer les filtres</a>
                                @else
                                    <span>Aucune affectation dans cette catégorie.</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($affectations->total() > 0)
            <div class="ca-card-footer">
                <span>{{ $affectations->firstItem() }} à {{ $affectations->lastItem() }} sur {{ $affectations->total() }} affectation(s)</span>
                {{ $affectations->onEachSide(1)->links() }}
            </div>
        @endif
        </div>
    </div>

    {{-- Nouvelle affectation : assistant en 3 étapes --}}
    @adminroute('admin.hebergement.equipements.affectations.store')
    @php $etapes = ['Matériel', 'Emplacement', 'Vérification']; @endphp
    <dialog id="af-store-form" class="ca-dialog is-wizard" aria-labelledby="af-store-title" @if($formulaireOuvert === 'store') data-ca-autoopen @endif>
        <form id="afStoreForm" method="POST" action="{{ route('admin.hebergement.equipements.affectations.store') }}" novalidate>
            @csrf
            <input type="hidden" name="_form" value="store">
            <div class="ca-dialog-head">
                <div>
                    <h3 class="ca-dialog-title" id="af-store-title">Affecter un équipement</h3>
                    <p class="ca-subtitle">
                        Étape <strong data-af-step-current>1</strong> sur {{ count($etapes) }}
                        · Les champs marqués <span style="color:#d92d20">*</span> sont obligatoires.
                    </p>
                </div>
                <button type="button" class="ca-icon-btn" data-ca-close aria-label="Fermer"><x-admin-icon name="x" /></button>
            </div>

            <ol class="ca-stepper" aria-label="Étapes de l’affectation">
                @foreach($etapes as $index => $etape)
                    <li>
                        <button type="button" class="ca-stepper-item" data-af-step-tab="{{ $index }}" disabled>
                            <span class="ca-stepper-num">
                                <span class="ca-stepper-index">{{ $index + 1 }}</span>
                                <x-admin-icon name="check" size="14" />
                            </span>
                            <span class="ca-stepper-label">{{ $etape }}</span>
                        </button>
                    </li>
                @endforeach
            </ol>

            <div class="ca-dialog-body">
                <div class="ca-alert ca-alert-error" role="alert" data-af-errors hidden></div>
                @if($formulaireOuvert === 'store')
                    <div class="ca-alert ca-alert-error" role="alert">
                        <x-admin-icon name="circle-x" />
                        <div>L’affectation n’a pas été enregistrée :
                            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                        </div>
                    </div>
                @endif

                {{-- Étape 1 : matériel --}}
                <div data-af-step="0">
                    <section class="ca-section">
                        <div class="ca-section-head">
                            <span class="ca-kpi-icon tone-green"><x-admin-icon name="package" /></span>
                            <div>
                                <h4 class="ca-section-title">Matériel à affecter</h4>
                                <p class="ca-section-desc">Seul le matériel disponible en magasin peut être affecté.</p>
                            </div>
                        </div>
                        <div class="ca-form-grid cols-2">
                            <div class="ca-field ca-span-full">
                                <label class="ca-label" for="af-equipement">Équipement <span class="req">*</span></label>
                                <select class="ca-input @error('equipement_id') is-invalid @enderror" name="equipement_id" id="af-equipement" required data-ca-select data-af-label="Équipement">
                                    <option value="">Sélectionner un équipement</option>
                                    @foreach($equipements as $equipment)
                                        @php $disponible = max(0, (int) $equipment->stock_actuel); @endphp
                                        <option value="{{ $equipment->equipement_id }}" data-stock="{{ $disponible }}" @disabled($disponible < 1)
                                                @selected($formulaireOuvert === 'store' && old('equipement_id') === $equipment->equipement_id)>{{ $equipment->name }} — {{ $nombre($disponible) }} disponible(s)</option>
                                    @endforeach
                                </select>
                                <span class="ca-hint af-stock-info" data-af-stock hidden><x-admin-icon name="info" size="14" /> <span></span></span>
                                @error('equipement_id')<span class="ca-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="ca-field">
                                <label class="ca-label" for="af-quantite">Quantité <span class="req">*</span></label>
                                <input class="ca-input @error('quantite') is-invalid @enderror" type="number" min="1" step="1" max="1000000" name="quantite" id="af-quantite"
                                       placeholder="Ex. 4" value="{{ $formulaireOuvert === 'store' ? old('quantite', 1) : 1 }}" required data-af-label="Quantité">
                                @error('quantite')<span class="ca-error">{{ $message }}</span>@enderror
                            </div>
                            <fieldset class="ca-field ca-span-full" style="border:0;margin:0;padding:0">
                                <legend class="ca-label" style="margin-bottom:6px">État à la sortie <span class="req">*</span></legend>
                                <div class="af-radios">
                                    @foreach($etats as $cle => [$libelle, , , $description])
                                        <label class="ca-check">
                                            <input type="radio" name="etat" value="{{ $cle }}" required data-af-label="État"
                                                   @checked(($formulaireOuvert === 'store' ? old('etat', 'bon') : 'bon') === $cle)>
                                            <span class="ca-check-body"><span class="ca-check-title">{{ $libelle }}</span><span class="ca-check-desc">{{ $description }}</span></span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('etat')<span class="ca-error">{{ $message }}</span>@enderror
                            </fieldset>
                        </div>
                    </section>
                </div>

                {{-- Étape 2 : emplacement --}}
                <div data-af-step="1" hidden>
                    <section class="ca-section">
                        <div class="ca-section-head">
                            <span class="ca-kpi-icon tone-green"><x-admin-icon name="map-pin" /></span>
                            <div>
                                <h4 class="ca-section-title">Emplacement</h4>
                                <p class="ca-section-desc">Sans bâtiment, l’équipement est commun à la cité ; sans chambre, il est commun au bâtiment.</p>
                            </div>
                        </div>
                        <div class="ca-form-grid">
                            <div class="ca-field">
                                <label class="ca-label" for="af-cite">Cité <span class="req">*</span></label>
                                <select class="ca-input @error('cite_id') is-invalid @enderror" name="cite_id" id="af-cite" required data-ca-select data-af-label="Cité">
                                    <option value="">Sélectionner une cité</option>
                                    @foreach($cites as $cite)
                                        <option value="{{ $cite->id_cite }}" @selected($formulaireOuvert === 'store' && old('cite_id') === $cite->id_cite)>{{ $cite->name }}</option>
                                    @endforeach
                                </select>
                                @error('cite_id')<span class="ca-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="ca-field">
                                <label class="ca-label" for="af-batiment">Bâtiment</label>
                                <select class="ca-input @error('batiment_id') is-invalid @enderror" name="batiment_id" id="af-batiment" data-ca-select>
                                    <option value="">Équipement commun à la cité</option>
                                    @foreach($batiments as $building)
                                        <option data-cite="{{ $building->cite_id }}" value="{{ $building->id_batiment }}" @selected($formulaireOuvert === 'store' && old('batiment_id') === $building->id_batiment)>{{ $building->name ?? $building->code ?? $building->id_batiment }}</option>
                                    @endforeach
                                </select>
                                <span class="ca-hint">Facultatif — choisissez d’abord la cité.</span>
                                @error('batiment_id')<span class="ca-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="ca-field">
                                <label class="ca-label" for="af-chambre">Chambre</label>
                                <select class="ca-input @error('chambre_id') is-invalid @enderror" name="chambre_id" id="af-chambre" data-ca-select>
                                    <option value="">Équipement commun au bâtiment</option>
                                    @foreach($chambres as $room)
                                        <option data-building="{{ $room->batiment_id }}" value="{{ $room->id_chambre }}" @selected($formulaireOuvert === 'store' && old('chambre_id') === $room->id_chambre)>Chambre {{ $room->numero_chambre }}</option>
                                    @endforeach
                                </select>
                                <span class="ca-hint">Facultatif — choisissez d’abord le bâtiment.</span>
                                @error('chambre_id')<span class="ca-error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </section>
                </div>

                {{-- Étape 3 : vérification --}}
                <div data-af-step="2" hidden>
                    <section class="ca-section">
                        <div class="ca-section-head">
                            <span class="ca-kpi-icon tone-green"><x-admin-icon name="list-checks" /></span>
                            <div>
                                <h4 class="ca-section-title">Vérification</h4>
                                <p class="ca-section-desc">Contrôlez l’affectation avant de l’enregistrer : la quantité sera retirée du magasin.</p>
                            </div>
                        </div>
                        <dl class="ca-dl" style="margin-bottom:16px">
                            <div class="ca-dl-row"><dt>Équipement</dt><dd data-af-recap="equipement"></dd></div>
                            <div class="ca-dl-row"><dt>Quantité</dt><dd data-af-recap="quantite"></dd></div>
                            <div class="ca-dl-row"><dt>État à la sortie</dt><dd data-af-recap="etat"></dd></div>
                            <div class="ca-dl-row"><dt>Emplacement</dt><dd data-af-recap="lieu"></dd></div>
                            <div class="ca-dl-row"><dt>Stock restant en magasin</dt><dd data-af-recap="reste"></dd></div>
                        </dl>
                        <div class="ca-field">
                            <label class="ca-label" for="af-notes">Observations</label>
                            <textarea name="notes" id="af-notes" maxlength="2000" rows="3" class="ca-input @error('notes') is-invalid @enderror" placeholder="Ex. Remplacement des matelas usagés">{{ $formulaireOuvert === 'store' ? old('notes') : '' }}</textarea>
                            <span class="ca-hint">Facultatif — 2 000 caractères maximum.</span>
                            @error('notes')<span class="ca-error">{{ $message }}</span>@enderror
                        </div>
                    </section>
                </div>
            </div>

            <div class="ca-dialog-foot">
                <button type="button" class="ca-btn ca-btn-outline" data-ca-close>Annuler</button>
                <span class="ca-foot-spacer"></span>
                <button type="button" class="ca-btn ca-btn-outline" data-af-step-prev hidden>
                    <x-admin-icon name="arrow-left" /> Précédent
                </button>
                <button type="button" class="ca-btn ca-btn-primary" data-af-step-next>
                    Suivant <x-admin-icon name="arrow-right" />
                </button>
                <button type="submit" class="ca-btn ca-btn-primary" data-af-step-submit hidden>
                    <x-admin-icon name="check" /> Confirmer l’affectation
                </button>
            </div>
        </form>
    </dialog>
    @endadminroute

    {{-- Mise à jour de l'état --}}
    @adminroute('admin.hebergement.equipements.affectations.update')
    <dialog id="af-etat-form" class="ca-dialog is-narrow" aria-labelledby="af-etat-title">
        <form method="POST" action="">
            @csrf
            @method('PATCH')
            <div class="ca-dialog-head">
                <h3 class="ca-dialog-title" id="af-etat-title">État du matériel</h3>
                <button type="button" class="ca-icon-btn" data-ca-close aria-label="Fermer"><x-admin-icon name="x" /></button>
            </div>
            <div class="ca-dialog-body">
                <p class="af-target"><strong data-af-name></strong> · <span data-af-lieu></span></p>
                <fieldset class="ca-field" style="border:0;margin:0;padding:0">
                    <legend class="ca-label" style="margin-bottom:6px">Nouvel état <span class="req">*</span></legend>
                    <div class="af-radios" style="grid-template-columns:minmax(0,1fr)">
                        @foreach($etats as $cle => [$libelle, , , $description])
                            <label class="ca-check">
                                <input type="radio" name="etat" value="{{ $cle }}" required>
                                <span class="ca-check-body"><span class="ca-check-title">{{ $libelle }}</span><span class="ca-check-desc">{{ $description }}</span></span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            </div>
            <div class="ca-dialog-foot">
                <button type="button" class="ca-btn ca-btn-outline" data-ca-close>Annuler</button>
                <button type="submit" class="ca-btn ca-btn-primary"><x-admin-icon name="check" /> Enregistrer</button>
            </div>
        </form>
    </dialog>
    @endadminroute

    {{-- Retour au magasin --}}
    @adminroute('admin.hebergement.equipements.affectations.retour')
    <dialog id="af-retour-form" class="ca-dialog is-narrow" aria-labelledby="af-retour-title" role="alertdialog">
        <form method="POST" action="">
            @csrf
            <div class="ca-dialog-head">
                <h3 class="ca-dialog-title" id="af-retour-title">Retour au magasin</h3>
                <button type="button" class="ca-icon-btn" data-ca-close aria-label="Fermer"><x-admin-icon name="x" /></button>
            </div>
            <div class="ca-dialog-body">
                <p style="margin:0"><strong data-af-quantite></strong> × « <strong data-af-name></strong> » sera réintégré au stock disponible.</p>
                <p class="ca-muted ca-small" style="margin:8px 0 0">Emplacement libéré : <span data-af-lieu></span>. Cette opération est définitive.</p>
            </div>
            <div class="ca-dialog-foot">
                <button type="button" class="ca-btn ca-btn-outline" data-ca-close>Annuler</button>
                <button type="submit" class="ca-btn ca-btn-primary"><x-admin-icon name="arrow-left-right" /> Confirmer le retour</button>
            </div>
        </form>
    </dialog>
    @endadminroute
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/ca-select.js') }}"></script>
    <script src="{{ asset('js/campagnes-admission.js') }}"></script>
    <script src="{{ asset('js/equipements-affectations.js') }}"></script>
@endpush
