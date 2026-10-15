@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'equipements'
])

@section('title', 'Équipements')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/campagnes-admission.css') }}">
    <style>
        .eq-thumb { width: 38px; height: 38px; flex: none; border-radius: var(--ca-radius); object-fit: cover; border: 1px solid var(--ca-border); background: var(--ca-surface); }
        .eq-thumb-icon { display: inline-flex; align-items: center; justify-content: center; color: #a15c00; background: #fff4d6; border-color: transparent; }
        .eq-catalogue-header { flex-direction: column; align-items: stretch; }
        .eq-toolbar { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: flex-start; }
        .eq-toolbar .ca-input { width: auto; min-width: 0; }
        .eq-search { position: relative; flex: 0 1 300px; }
        .eq-search.is-loading svg { animation: eq-pulse 1s ease-in-out infinite; }
        @keyframes eq-pulse { 50% { opacity: .35; } }
        [data-eq-results] { transition: opacity .15s; }
        [data-eq-results].is-loading { opacity: .55; }
        .eq-file-preview { display: none; width: 56px; height: 56px; border-radius: var(--ca-radius); object-fit: cover; border: 1px solid var(--ca-border); }
        .ca-file.has-preview .eq-file-preview { display: block; }
        .ca-file.has-preview .ca-file-icon { display: none; }
        .eq-file-badge { display: none; padding: 2px 8px; border-radius: 999px; background: #fff; color: var(--ca-muted); font-size: 12px; }
        .ca-file.has-current:not(.has-file) .eq-file-badge { display: inline-block; }
        .eq-search .ca-input { width: 100%; padding-left: 36px; }
        .eq-search svg { position: absolute; top: 50%; left: 12px; transform: translateY(-50%); color: #8a9087; pointer-events: none; }
        .eq-stock { display: flex; flex-direction: column; gap: 4px; min-width: 120px; }
        .eq-stock .ca-progress { width: 120px; }
        .eq-stock .ca-progress > span.is-faible { background: var(--ca-accent); }
        .eq-stock .ca-progress > span.is-rupture { background: #d92d20; }
        .eq-target { margin: 0 0 16px; padding: 10px 12px; border-radius: var(--ca-radius); background: var(--ca-surface); font-size: 13px; color: var(--ca-muted); }
        .eq-target strong { color: var(--ca-text); font-weight: 500; }
        .eq-radios input[type="radio"] { flex: none; width: 16px; height: 16px; margin: 2px 0 0; accent-color: var(--ca-primary); }
        .eq-radios { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .eq-toolbar .ca-select-trigger { min-width: 200px; min-height: 40px; }
        @media (max-width: 639px) { .eq-toolbar .ca-input, .eq-toolbar .ca-btn, .eq-toolbar .ca-select { flex: 1 1 100%; } }
    </style>
@endpush

@php
    // [statut, libellé, icône, couleur]
    $filtres = [
        ['tous', 'Tous les équipements', 'package', 'kpi-blue'],
        ['disponible', 'Stock suffisant', 'circle-check', 'kpi-green'],
        ['faible', 'Stock faible', 'triangle-alert', 'kpi-amber'],
        ['rupture', 'En rupture', 'circle-x', 'kpi-plum'],
    ];
    $badges = [
        'Disponible' => ['Stock suffisant', 'tone-green', 'check', ''],
        'Stock faible' => ['Stock faible', 'tone-amber', 'triangle-alert', 'is-faible'],
        'Rupture' => ['Rupture', 'tone-red', 'x', 'is-rupture'],
    ];
    $nombre = fn ($valeur) => number_format((float) $valeur, 0, ',', ' ');
    $formulaireOuvert = $errors->any() ? old('_form') : null;
    $filtresActifs = $recherche !== '' || $type !== '' || $statut !== 'tous';
@endphp

@section('content')
<div class="as-refonte ca-page">
    <x-breadcrumbs
            title="Équipements"
            :items="[
                'Gestion Hébergement' => route('admin.hebergement.index'),
                'Équipements' => null,
            ]"
    />

    <div class="ca-header">
        <div class="ca-header-text">
            <p class="ca-subtitle">Catalogue du matériel en magasin : stock disponible, seuils d’alerte et mouvements d’entrée ou de sortie.</p>
        </div>
        <div class="ca-actions">
            @adminroute('admin.hebergement.equipements.affectations.index')
            <a href="{{ route('admin.hebergement.equipements.affectations.index') }}" class="ca-btn ca-btn-outline">
                <x-admin-icon name="building" /> Affectations au parc
            </a>
            @endadminroute
            @adminroute('admin.hebergement.parametrage-herb.type-equipement.store')
            <button type="button" class="ca-btn ca-btn-soft" data-ca-open="eq-type-form">
                <x-admin-icon name="tag" /> Nouveau type
            </button>
            @endadminroute
            @adminroute('admin.hebergement.equipements.store')
            <button type="button" class="ca-btn ca-btn-primary" data-ca-open="eq-store-form">
                <x-admin-icon name="plus" /> Entrée de stock
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
            <div>L’opération n’a pas abouti :
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        </div>
    @endif

    {{-- Indicateurs / filtres --}}
    <div class="ca-kpis ca-kpis--teintes" data-eq-kpis>
        @foreach($filtres as [$filtre, $libelle, $icone, $couleur])
            <a href="{{ route('admin.hebergement.equipements.index', array_filter(['statut' => $filtre === 'tous' ? null : $filtre, 'q' => $recherche ?: null, 'type' => $type ?: null])) }}"
               class="ca-kpi {{ $couleur }} {{ $statut === $filtre ? 'is-active' : '' }}"
               @if($statut === $filtre) aria-current="page" @endif>
                <span class="ca-kpi-label">{{ $libelle }}</span>
                <span class="ca-kpi-row">
                    <strong class="ca-kpi-value">{{ $nombre($stats[$filtre] ?? 0) }}</strong>
                    @if($filtre === 'tous')
                        <span class="ca-kpi-badge">{{ $nombre($stockTotal) }} unité(s) en magasin</span>
                    @endif
                </span>
            </a>
        @endforeach
    </div>

    {{-- Catalogue --}}
    <div class="ca-card">
        <div class="ca-card-header eq-catalogue-header">
            <h2 class="ca-card-title">Catalogue &amp; disponibilités</h2>
            <form method="GET" action="{{ route('admin.hebergement.equipements.index') }}" class="eq-toolbar" role="search" data-eq-filters>
                @if($statut !== 'tous')<input type="hidden" name="statut" value="{{ $statut }}">@endif
                <label class="eq-search" data-eq-search>
                    <span class="sr-only">Rechercher un équipement</span>
                    <x-admin-icon name="search" size="16" />
                    <input type="search" name="q" value="{{ $recherche }}" class="ca-input" placeholder="Rechercher un équipement…" autocomplete="off">
                </label>
                <label class="sr-only" for="eq-filter-type">Type d’équipement</label>
                <select id="eq-filter-type" name="type" class="ca-input" data-ca-select>
                    <option value="">Tous les types</option>
                    @foreach($type_equipement as $item)
                        <option value="{{ $item->id_type_equipement }}" @selected($type === (string) $item->id_type_equipement)>{{ $item->name }}</option>
                    @endforeach
                </select>
                <a href="{{ route('admin.hebergement.equipements.index') }}" class="ca-link-btn" data-eq-reset @unless($filtresActifs) hidden @endunless>Réinitialiser</a>
            </form>
        </div>

        <div data-eq-results aria-live="polite">
        <div class="ca-table-wrap">
            <table class="ca-table">
                <thead>
                <tr>
                    <th class="is-num">#</th>
                    <th>Équipement</th>
                    <th>Stock initial</th>
                    <th>Stock actuel</th>
                    <th>Seuil d’alerte</th>
                    <th>Statut</th>
                    <th class="is-actions"><span class="sr-only">Actions</span></th>
                </tr>
                </thead>
                <tbody>
                @forelse($equipements as $equipement)
                    @php
                        [$libelleStatut, $toneStatut, $iconeStatut, $classeBarre] = $badges[$equipement->stock_status['text'] ?? 'Disponible'] ?? $badges['Disponible'];
                        $initial = (float) $equipement->stock_initial;
                        $actuel = (float) $equipement->stock_actuel;
                        $taux = $initial > 0 ? max(0, min(100, round($actuel / $initial * 100))) : ($actuel > 0 ? 100 : 0);
                    @endphp
                    <tr>
                        <td class="is-num">{{ ($equipements->firstItem() ?? 1) + $loop->index }}</td>
                        <td>
                            <div class="ca-person">
                                @if($equipement->photo)
                                    <img src="{{ $equipement->photo }}" alt="" class="eq-thumb" loading="lazy">
                                @else
                                    <span class="eq-thumb eq-thumb-icon" aria-hidden="true"><x-admin-icon name="package" size="18" /></span>
                                @endif
                                <div>
                                    <span class="ca-strong">{{ $equipement->name }}</span>
                                    <span class="ca-muted ca-small" style="display:block">{{ $equipement->type_equipement->name ?? 'Type non défini' }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="ca-mono">{{ $nombre($initial) }}</td>
                        <td>
                            <div class="eq-stock">
                                <span class="ca-mono ca-strong">{{ $nombre($actuel) }}</span>
                                <div class="ca-progress" title="{{ $taux }} % du stock initial"><span class="{{ $classeBarre }}" style="width: {{ $taux }}%"></span></div>
                            </div>
                        </td>
                        <td class="ca-mono">{{ $nombre($equipement->threshold) }}</td>
                        <td><span class="ca-badge {{ $toneStatut }}"><x-admin-icon :name="$iconeStatut" size="14" /> {{ $libelleStatut }}</span></td>
                        <td class="is-actions">
                            @adminroute('admin.hebergement.equipements.mouvements')
                            <a href="{{ route('admin.hebergement.equipements.mouvements', $equipement->equipement_id) }}" class="ca-icon-btn" title="Historique des mouvements" aria-label="Historique des mouvements de {{ $equipement->name }}">
                                <x-admin-icon name="history" />
                            </a>
                            @endadminroute
                            @adminroute('admin.hebergement.equipements.store_mouvement')
                            <button type="button" class="ca-icon-btn" title="Entrée ou sortie de stock" aria-label="Mouvement de stock pour {{ $equipement->name }}"
                                    data-eq-move data-id="{{ $equipement->equipement_id }}" data-name="{{ $equipement->name }}" data-stock="{{ $nombre($actuel) }}">
                                <x-admin-icon name="arrow-left-right" />
                            </button>
                            @endadminroute
                            @adminroute('admin.hebergement.equipements.update')
                            <button type="button" class="ca-icon-btn" title="Modifier" aria-label="Modifier {{ $equipement->name }}"
                                    data-eq-edit data-id="{{ $equipement->equipement_id }}" data-name="{{ $equipement->name }}"
                                    data-type="{{ $equipement->type_equipement_id }}" data-threshold="{{ $equipement->threshold }}" data-photo="{{ $equipement->photo }}">
                                <x-admin-icon name="pencil" />
                            </button>
                            @endadminroute
                            @adminroute('admin.hebergement.equipements.destroy')
                            <button type="button" class="ca-icon-btn is-danger" title="Supprimer" aria-label="Supprimer {{ $equipement->name }}"
                                    data-eq-delete data-action="{{ route('admin.hebergement.equipements.destroy', $equipement->equipement_id) }}" data-name="{{ $equipement->name }}">
                                <x-admin-icon name="trash-2" />
                            </button>
                            @endadminroute
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="ca-empty">
                                <span class="ca-empty-icon"><x-admin-icon name="package" size="22" /></span>
                                @if($filtresActifs)
                                    <span>Aucun équipement ne correspond à ces critères.</span>
                                    <a href="{{ route('admin.hebergement.equipements.index') }}" class="ca-link-btn">Voir tout le catalogue</a>
                                @else
                                    <span>Aucun équipement enregistré pour le moment.</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($equipements->total() > 0)
            <div class="ca-card-footer">
                <span>{{ $equipements->firstItem() }} à {{ $equipements->lastItem() }} sur {{ $equipements->total() }} équipement(s)</span>
                {{ $equipements->onEachSide(1)->links() }}
            </div>
        @endif
        </div>
    </div>

    {{-- Entrée de stock (nouvel équipement) --}}
    @adminroute('admin.hebergement.equipements.store')
    <dialog id="eq-store-form" class="ca-dialog is-narrow" aria-labelledby="eq-store-title" @if($formulaireOuvert === 'store') data-ca-autoopen @endif>
        <form method="POST" action="{{ route('admin.hebergement.equipements.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_form" value="store">
            <div class="ca-dialog-head">
                <div>
                    <h3 class="ca-dialog-title" id="eq-store-title">Nouvelle entrée de stock</h3>
                    <p class="ca-section-desc">Enregistre un nouvel équipement et sa quantité reçue en magasin.</p>
                </div>
                <button type="button" class="ca-icon-btn" data-ca-close aria-label="Fermer"><x-admin-icon name="x" /></button>
            </div>
            <div class="ca-dialog-body">
                <div class="ca-form-grid cols-2">
                    <div class="ca-field">
                        <label class="ca-label" for="eq-store-type">Type d’équipement <span class="req">*</span></label>
                        <select class="ca-input" name="type_equipement_id" id="eq-store-type" required data-ca-select>
                            <option value="">Sélectionner le type</option>
                            @foreach($type_equipement as $item)
                                <option value="{{ $item->id_type_equipement }}" @selected($formulaireOuvert === 'store' && old('type_equipement_id') == $item->id_type_equipement)>{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ca-field">
                        <label class="ca-label" for="eq-store-name">Désignation <span class="req">*</span></label>
                        <input type="text" class="ca-input" name="name" id="eq-store-name" maxlength="255" placeholder="Ex. Matelas 1 place" value="{{ $formulaireOuvert === 'store' ? old('name') : '' }}" required>
                    </div>
                    <div class="ca-field">
                        <label class="ca-label" for="eq-store-qty">Quantité reçue <span class="req">*</span></label>
                        <input type="number" class="ca-input" min="0" step="1" name="stock_initial" id="eq-store-qty" placeholder="0" value="{{ $formulaireOuvert === 'store' ? old('stock_initial') : '' }}" required>
                    </div>
                    <div class="ca-field">
                        <label class="ca-label" for="eq-store-price">Coût unitaire <span class="req">*</span></label>
                        <div class="ca-input-addon">
                            <input type="number" class="ca-input" step="0.01" min="0" name="prix" id="eq-store-price" value="{{ $formulaireOuvert === 'store' ? old('prix', 0) : 0 }}" required>
                            <span>FCFA</span>
                        </div>
                    </div>
                    <div class="ca-field">
                        <label class="ca-label" for="eq-store-threshold">Seuil d’alerte <span class="req">*</span></label>
                        <input type="number" class="ca-input" min="0" step="1" name="threshold" id="eq-store-threshold" value="{{ $formulaireOuvert === 'store' ? old('threshold', 0) : 0 }}" required>
                        <span class="ca-hint">Statut « Stock faible » à partir de cette quantité.</span>
                    </div>
                    <div class="ca-field">
                        <label class="ca-label" for="eq-store-date">Date de réception <span class="req">*</span></label>
                        <input type="date" class="ca-input" name="date_reception" id="eq-store-date" value="{{ $formulaireOuvert === 'store' ? old('date_reception', date('Y-m-d')) : date('Y-m-d') }}" required>
                    </div>
                    <div class="ca-field ca-span-full">
                        <label class="ca-label" for="eq-store-photo">Photo</label>
                        <div class="ca-file" data-eq-file>
                            <input type="file" name="photo" id="eq-store-photo" accept=".jpg,.jpeg,.png,.gif">
                            <span class="ca-file-icon" aria-hidden="true"><x-admin-icon name="upload" size="20" /></span>
                            <img class="eq-file-preview" alt="" data-eq-file-preview>
                            <span class="ca-file-title"><strong>Choisir une photo</strong> ou la glisser ici</span>
                            <span class="ca-hint">Facultatif — JPG, PNG ou GIF.</span>
                            <span class="ca-file-name" data-eq-file-name></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="ca-dialog-foot">
                <button type="button" class="ca-btn ca-btn-outline" data-ca-close>Annuler</button>
                <button type="submit" class="ca-btn ca-btn-primary"><x-admin-icon name="check" /> Enregistrer</button>
            </div>
        </form>
    </dialog>
    @endadminroute

    {{-- Nouveau type d'équipement --}}
    @adminroute('admin.hebergement.parametrage-herb.type-equipement.store')
    <dialog id="eq-type-form" class="ca-dialog is-narrow" aria-labelledby="eq-type-title" @if($formulaireOuvert === 'type') data-ca-autoopen @endif>
        <form method="POST" action="{{ route('admin.hebergement.parametrage-herb.type-equipement.store') }}">
            @csrf
            <input type="hidden" name="_form" value="type">
            <div class="ca-dialog-head">
                <h3 class="ca-dialog-title" id="eq-type-title">Nouveau type d’équipement</h3>
                <button type="button" class="ca-icon-btn" data-ca-close aria-label="Fermer"><x-admin-icon name="x" /></button>
            </div>
            <div class="ca-dialog-body">
                <div class="ca-form-grid">
                    <div class="ca-field">
                        <label class="ca-label" for="eq-type-name">Nom <span class="req">*</span></label>
                        <input type="text" class="ca-input" name="name" id="eq-type-name" maxlength="255" placeholder="Ex. Literie" value="{{ $formulaireOuvert === 'type' ? old('name') : '' }}" required>
                    </div>
                    <div class="ca-field">
                        <label class="ca-label" for="eq-type-description">Description</label>
                        <textarea class="ca-input" name="description" id="eq-type-description" rows="3" placeholder="Description du type d’équipement">{{ $formulaireOuvert === 'type' ? old('description') : '' }}</textarea>
                    </div>
                </div>
            </div>
            <div class="ca-dialog-foot">
                <button type="button" class="ca-btn ca-btn-outline" data-ca-close>Annuler</button>
                <button type="submit" class="ca-btn ca-btn-primary"><x-admin-icon name="check" /> Enregistrer</button>
            </div>
        </form>
    </dialog>
    @endadminroute

    {{-- Mouvement de stock --}}
    @adminroute('admin.hebergement.equipements.store_mouvement')
    <dialog id="eq-move-form" class="ca-dialog is-narrow" aria-labelledby="eq-move-title" @if($formulaireOuvert === 'move') data-ca-autoopen @endif>
        <form method="POST" action="{{ route('admin.hebergement.equipements.store_mouvement') }}">
            @csrf
            <input type="hidden" name="_form" value="move">
            <input type="hidden" name="equipement_id" id="eq-move-id" value="{{ $formulaireOuvert === 'move' ? old('equipement_id') : '' }}">
            <div class="ca-dialog-head">
                <h3 class="ca-dialog-title" id="eq-move-title">Mouvement de stock</h3>
                <button type="button" class="ca-icon-btn" data-ca-close aria-label="Fermer"><x-admin-icon name="x" /></button>
            </div>
            <div class="ca-dialog-body">
                <p class="eq-target" id="eq-move-target" hidden>
                    Équipement : <strong data-eq-name></strong> · stock actuel <strong data-eq-stock></strong>
                </p>
                <div class="ca-form-grid">
                    <fieldset class="ca-field" style="border:0;margin:0;padding:0">
                        <legend class="ca-label" style="margin-bottom:6px">Type de mouvement <span class="req">*</span></legend>
                        <div class="eq-radios">
                            <label class="ca-check">
                                <input type="radio" name="type" value="entry" required @checked($formulaireOuvert === 'move' && old('type') === 'entry')>
                                <span class="ca-check-body"><span class="ca-check-title">Entrée</span><span class="ca-check-desc">Ajoute la quantité au magasin</span></span>
                            </label>
                            <label class="ca-check">
                                <input type="radio" name="type" value="exit" @checked($formulaireOuvert === 'move' && old('type') === 'exit')>
                                <span class="ca-check-body"><span class="ca-check-title">Sortie</span><span class="ca-check-desc">Retire la quantité du magasin</span></span>
                            </label>
                        </div>
                    </fieldset>
                    <div class="ca-field">
                        <label class="ca-label" for="eq-move-qty">Quantité <span class="req">*</span></label>
                        <input type="number" class="ca-input" name="quantite" id="eq-move-qty" min="1" step="1" placeholder="0" value="{{ $formulaireOuvert === 'move' ? old('quantite') : '' }}" required>
                        <span class="ca-hint">Une sortie ne peut pas dépasser le stock actuel.</span>
                    </div>
                    <div class="ca-field">
                        <label class="ca-label" for="eq-move-reason">Motif</label>
                        <textarea class="ca-input" name="motif" id="eq-move-reason" rows="2" maxlength="255" placeholder="Ex. Livraison fournisseur, matériel réformé…">{{ $formulaireOuvert === 'move' ? old('motif') : '' }}</textarea>
                    </div>
                </div>
            </div>
            <div class="ca-dialog-foot">
                <button type="button" class="ca-btn ca-btn-outline" data-ca-close>Annuler</button>
                <button type="submit" class="ca-btn ca-btn-primary"><x-admin-icon name="arrow-left-right" /> Enregistrer le mouvement</button>
            </div>
        </form>
    </dialog>
    @endadminroute

    {{-- Modification d'un équipement --}}
    @adminroute('admin.hebergement.equipements.update')
    <dialog id="eq-edit-form" class="ca-dialog is-narrow" aria-labelledby="eq-edit-title" @if($formulaireOuvert === 'edit') data-ca-autoopen @endif>
        <form method="POST" action="{{ route('admin.hebergement.equipements.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" name="_form" value="edit">
            <input type="hidden" name="equipement_id" id="eq-edit-id" value="{{ $formulaireOuvert === 'edit' ? old('equipement_id') : '' }}">
            <div class="ca-dialog-head">
                <h3 class="ca-dialog-title" id="eq-edit-title">Modifier l’équipement</h3>
                <button type="button" class="ca-icon-btn" data-ca-close aria-label="Fermer"><x-admin-icon name="x" /></button>
            </div>
            <div class="ca-dialog-body">
                <div class="ca-form-grid">
                    <div class="ca-field">
                        <label class="ca-label" for="eq-edit-name">Désignation <span class="req">*</span></label>
                        <input type="text" class="ca-input" name="name" id="eq-edit-name" maxlength="255" value="{{ $formulaireOuvert === 'edit' ? old('name') : '' }}" required>
                    </div>
                    <div class="ca-field">
                        <label class="ca-label" for="eq-edit-type">Type d’équipement <span class="req">*</span></label>
                        <select class="ca-input" name="type_equipement_id" id="eq-edit-type" required data-ca-select>
                            <option value="">Sélectionner le type</option>
                            @foreach($type_equipement as $item)
                                <option value="{{ $item->id_type_equipement }}" @selected($formulaireOuvert === 'edit' && old('type_equipement_id') == $item->id_type_equipement)>{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ca-field">
                        <label class="ca-label" for="eq-edit-threshold">Seuil d’alerte <span class="req">*</span></label>
                        <input type="number" class="ca-input" name="threshold" id="eq-edit-threshold" min="0" step="1" value="{{ $formulaireOuvert === 'edit' ? old('threshold') : '' }}" required>
                        <span class="ca-hint">Le statut passe à « Stock faible » quand le stock atteint cette valeur.</span>
                    </div>
                    <div class="ca-field">
                        <label class="ca-label" for="eq-edit-photo">Nouvelle photo</label>
                        <div class="ca-file" data-eq-file id="eq-edit-photo-zone">
                            <input type="file" name="photo" id="eq-edit-photo" accept=".jpg,.jpeg,.png,.gif">
                            <span class="ca-file-icon" aria-hidden="true"><x-admin-icon name="upload" size="20" /></span>
                            <img class="eq-file-preview" alt="" data-eq-file-preview>
                            <span class="eq-file-badge">Photo actuelle</span>
                            <span class="ca-file-title"><strong>Choisir une nouvelle photo</strong> ou la glisser ici</span>
                            <span class="ca-hint">Facultatif — JPG, PNG ou GIF. Laisser vide pour conserver la photo actuelle.</span>
                            <span class="ca-file-name" data-eq-file-name></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="ca-dialog-foot">
                <button type="button" class="ca-btn ca-btn-outline" data-ca-close>Annuler</button>
                <button type="submit" class="ca-btn ca-btn-primary"><x-admin-icon name="check" /> Enregistrer</button>
            </div>
        </form>
    </dialog>
    @endadminroute

    {{-- Suppression --}}
    @adminroute('admin.hebergement.equipements.destroy')
    <dialog id="eq-delete-form" class="ca-dialog is-narrow" aria-labelledby="eq-delete-title" role="alertdialog">
        <form method="POST" action="">
            @csrf
            @method('DELETE')
            <div class="ca-dialog-head">
                <h3 class="ca-dialog-title" id="eq-delete-title">Supprimer l’équipement ?</h3>
                <button type="button" class="ca-icon-btn" data-ca-close aria-label="Fermer"><x-admin-icon name="x" /></button>
            </div>
            <div class="ca-dialog-body">
                <p style="margin:0">« <strong data-eq-name></strong> » sera retiré du catalogue.</p>
                <p class="ca-muted ca-small" style="margin:8px 0 0">La suppression n’est possible que si le stock est à zéro et qu’aucun mouvement n’est enregistré.</p>
            </div>
            <div class="ca-dialog-foot">
                <button type="button" class="ca-btn ca-btn-outline" data-ca-close>Annuler</button>
                <button type="submit" class="ca-btn ca-btn-danger"><x-admin-icon name="trash-2" /> Supprimer</button>
            </div>
        </form>
    </dialog>
    @endadminroute
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/ca-select.js') }}"></script>
    <script src="{{ asset('js/ca-datepicker.js') }}"></script>
    <script src="{{ asset('js/campagnes-admission.js') }}"></script>
    <script>
        (function () {
            'use strict';
            function open(id) {
                var dialog = document.getElementById(id);
                if (dialog && typeof dialog.showModal === 'function' && !dialog.open) dialog.showModal();
                return dialog;
            }
            function setText(dialog, selector, value) {
                dialog.querySelectorAll(selector).forEach(function (el) { el.textContent = value; });
            }

            document.addEventListener('click', function (event) {
                var move = event.target.closest('[data-eq-move]');
                if (move) {
                    var moveDialog = document.getElementById('eq-move-form');
                    moveDialog.querySelector('form').reset();
                    document.getElementById('eq-move-id').value = move.dataset.id;
                    setText(moveDialog, '[data-eq-name]', move.dataset.name);
                    setText(moveDialog, '[data-eq-stock]', move.dataset.stock);
                    document.getElementById('eq-move-target').hidden = false;
                    open('eq-move-form');
                    return;
                }
                var edit = event.target.closest('[data-eq-edit]');
                if (edit) {
                    document.getElementById('eq-edit-id').value = edit.dataset.id;
                    document.getElementById('eq-edit-name').value = edit.dataset.name;
                    var editType = document.getElementById('eq-edit-type');
                    editType.value = edit.dataset.type || '';
                    editType.dispatchEvent(new Event('ca-select:refresh'));
                    document.getElementById('eq-edit-threshold').value = edit.dataset.threshold;
                    var editPhoto = document.getElementById('eq-edit-photo');
                    document.getElementById('eq-edit-photo-zone').dataset.current = edit.dataset.photo || '';
                    editPhoto.value = '';
                    editPhoto.dispatchEvent(new Event('change'));
                    open('eq-edit-form');
                    return;
                }
                var del = event.target.closest('[data-eq-delete]');
                if (del) {
                    var deleteDialog = document.getElementById('eq-delete-form');
                    deleteDialog.querySelector('form').action = del.dataset.action;
                    setText(deleteDialog, '[data-eq-name]', del.dataset.name);
                    open('eq-delete-form');
                }
            });

            // Recherche instantanée : recharge la liste et les compteurs sans bouton ni rechargement complet.
            var filters = document.querySelector('[data-eq-filters]');
            if (filters) {
                var results = document.querySelector('[data-eq-results]');
                var kpis = document.querySelector('[data-eq-kpis]');
                var searchBox = filters.querySelector('[data-eq-search]');
                var reset = filters.querySelector('[data-eq-reset]');
                var timer = null;
                var controller = null;

                function refresh(url) {
                    if (controller) controller.abort();
                    controller = new AbortController();
                    results.classList.add('is-loading');
                    searchBox.classList.add('is-loading');
                    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal })
                        .then(function (response) {
                            if (!response.ok) throw new Error('HTTP ' + response.status);
                            return response.text();
                        })
                        .then(function (html) {
                            var page = new DOMParser().parseFromString(html, 'text/html');
                            var newResults = page.querySelector('[data-eq-results]');
                            var newKpis = page.querySelector('[data-eq-kpis]');
                            var newReset = page.querySelector('[data-eq-reset]');
                            if (newResults) results.innerHTML = newResults.innerHTML;
                            if (newKpis && kpis) kpis.innerHTML = newKpis.innerHTML;
                            if (newReset && reset) reset.hidden = newReset.hidden;
                            history.replaceState(null, '', url);
                        })
                        .catch(function (error) {
                            if (error.name !== 'AbortError') window.location.href = url;
                        })
                        .finally(function () {
                            results.classList.remove('is-loading');
                            searchBox.classList.remove('is-loading');
                        });
                }

                function currentUrl() {
                    var params = new URLSearchParams();
                    new FormData(filters).forEach(function (value, key) {
                        if (String(value).trim() !== '') params.append(key, String(value).trim());
                    });
                    var query = params.toString();
                    return filters.action + (query ? '?' + query : '');
                }

                filters.addEventListener('input', function (event) {
                    if (event.target.name !== 'q') return;
                    clearTimeout(timer);
                    timer = setTimeout(function () { refresh(currentUrl()); }, 300);
                });
                filters.addEventListener('change', function (event) {
                    if (event.target.name !== 'type') return;
                    clearTimeout(timer);
                    refresh(currentUrl());
                });
                filters.addEventListener('submit', function (event) {
                    event.preventDefault();
                    clearTimeout(timer);
                    refresh(currentUrl());
                });
            }

            // Zone de dépôt de la photo : nom du fichier, aperçu et glisser-déposer.
            document.querySelectorAll('[data-eq-file]').forEach(function (zone) {
                var input = zone.querySelector('input[type="file"]');
                var name = zone.querySelector('[data-eq-file-name]');
                var preview = zone.querySelector('[data-eq-file-preview]');
                function update() {
                    var file = input.files && input.files[0];
                    var current = zone.dataset.current || '';
                    zone.classList.toggle('has-file', !!file);
                    zone.classList.toggle('has-current', current !== '');
                    name.textContent = file ? file.name : '';
                    if (preview.src.indexOf('blob:') === 0) URL.revokeObjectURL(preview.src);
                    preview.removeAttribute('src');
                    // Sans nouveau fichier, l'aperçu montre la photo actuelle (modification).
                    if (file && file.type.indexOf('image/') === 0) preview.src = URL.createObjectURL(file);
                    else if (!file && current) preview.src = current;
                    zone.classList.toggle('has-preview', preview.hasAttribute('src'));
                }
                input.addEventListener('change', update);
                ['dragenter', 'dragover'].forEach(function (type) {
                    zone.addEventListener(type, function () { zone.classList.add('is-dragover'); });
                });
                ['dragleave', 'drop'].forEach(function (type) {
                    zone.addEventListener(type, function () { zone.classList.remove('is-dragover'); });
                });
                var form = input.form;
                if (form) form.addEventListener('reset', function () { setTimeout(update, 0); });
            });
        })();
    </script>
@endpush
