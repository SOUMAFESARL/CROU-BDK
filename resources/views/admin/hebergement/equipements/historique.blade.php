@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'equipements'
])

@section('title', 'Mouvements · '.$equipement->name)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/campagnes-admission.css') }}">
    <style>
        .mv-summary { display: flex; flex-wrap: wrap; align-items: center; gap: 16px 24px; padding: 18px; }
        .mv-ident { display: flex; align-items: center; gap: 14px; flex: 1 1 260px; min-width: 0; }
        .mv-photo { width: 64px; height: 64px; flex: none; border-radius: 10px; object-fit: cover; border: 1px solid var(--ca-border); background: var(--ca-surface); }
        .mv-photo-icon { display: inline-flex; align-items: center; justify-content: center; color: #a15c00; background: #fff4d6; border-color: transparent; }
        .mv-name { margin: 0; font-size: 17px; font-weight: 500; }
        .mv-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 10px; margin-top: 6px; font-size: 13px; color: var(--ca-muted); }
        .mv-ref { padding: 2px 8px; border-radius: 6px; background: var(--ca-surface); font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px; }
        .mv-figures { display: grid; grid-template-columns: repeat(3, minmax(90px, auto)); gap: 4px 28px; }
        .mv-figure { display: flex; flex-direction: column; gap: 2px; }
        .mv-figure span { font-size: 12.5px; color: var(--ca-muted); }
        .mv-figure strong { font-size: 18px; font-weight: 500; font-variant-numeric: tabular-nums; }
        .mv-progress { grid-column: 1 / -1; width: 100%; margin-top: 8px; }
        .mv-progress > span.is-faible { background: var(--ca-accent); }
        .mv-progress > span.is-rupture { background: #d92d20; }
        .mv-date { display: flex; flex-direction: column; gap: 2px; }
        .mv-qty { font-weight: 500; font-variant-numeric: tabular-nums; }
        .mv-qty.is-entry { color: var(--ca-primary); }
        .mv-qty.is-exit { color: #b42318; }
        .mv-reason { max-width: 360px; }
        .mv-target { margin: 0 0 16px; padding: 10px 12px; border-radius: var(--ca-radius); background: var(--ca-surface); font-size: 13px; color: var(--ca-muted); }
        .mv-target strong { color: var(--ca-text); font-weight: 500; }
        .mv-radios { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .mv-radios input[type="radio"] { flex: none; width: 16px; height: 16px; margin: 2px 0 0; accent-color: var(--ca-primary); }
        @media (max-width: 639px) {
            .mv-figures { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 4px 12px; width: 100%; }
            .mv-radios { grid-template-columns: minmax(0, 1fr); }
        }
    </style>
@endpush

@php
    // [filtre, libellé, couleur]
    $filtres = [
        ['tous', 'Tous les mouvements', 'kpi-blue'],
        ['entry', 'Entrées en magasin', 'kpi-green'],
        ['exit', 'Sorties du magasin', 'kpi-plum'],
    ];
    $badges = [
        'Disponible' => ['Stock suffisant', 'tone-green', 'check', ''],
        'Stock faible' => ['Stock faible', 'tone-amber', 'triangle-alert', 'is-faible'],
        'Rupture' => ['Rupture', 'tone-red', 'x', 'is-rupture'],
    ];
    $nombre = fn ($valeur) => number_format((float) $valeur, 0, ',', ' ');
    [$libelleStatut, $toneStatut, $iconeStatut, $classeBarre] = $badges[$equipement->stock_status['text'] ?? 'Disponible'] ?? $badges['Disponible'];
    $actuel = (float) $equipement->stock_actuel;
    $reference = max((float) $equipement->stock_initial, $stats['entrees']);
    $taux = $reference > 0 ? max(0, min(100, round($actuel / $reference * 100))) : ($actuel > 0 ? 100 : 0);
    $formulaireOuvert = $errors->any() ? old('_form') : null;
    $titres = ['tous' => 'Journal des mouvements', 'entry' => 'Entrées en magasin', 'exit' => 'Sorties du magasin'];
@endphp

@section('content')
<div class="as-refonte ca-page">
    <x-breadcrumbs
            title="Historique des mouvements"
            :items="[
                'Gestion Hébergement' => route('admin.hebergement.index'),
                'Équipements' => route('admin.hebergement.equipements.index'),
                $equipement->name => null,
            ]"
    />

    <div class="ca-header">
        <div class="ca-header-text">
            <p class="ca-subtitle">Toutes les entrées et sorties de stock de cet équipement, de la plus récente à la plus ancienne. Les affectations au parc et les retours au magasin y figurent aussi.</p>
        </div>
        <div class="ca-actions">
            @adminroute('admin.hebergement.equipements.index')
            <a href="{{ route('admin.hebergement.equipements.index') }}" class="ca-btn ca-btn-outline">
                <x-admin-icon name="arrow-left" /> Retour au catalogue
            </a>
            @endadminroute
            @adminroute('admin.hebergement.equipements.store_mouvement')
            <button type="button" class="ca-btn ca-btn-primary" data-ca-open="mv-move-form">
                <x-admin-icon name="arrow-left-right" /> Mouvement de stock
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

    {{-- Fiche de l'équipement --}}
    <div class="ca-card">
        <div class="mv-summary">
            <div class="mv-ident">
                @if($equipement->photo)
                    <img src="{{ $equipement->photo }}" alt="" class="mv-photo">
                @else
                    <span class="mv-photo mv-photo-icon" aria-hidden="true"><x-admin-icon name="package" size="26" /></span>
                @endif
                <div style="min-width:0">
                    <h2 class="mv-name">{{ $equipement->name }}</h2>
                    <div class="mv-meta">
                        <span>{{ $equipement->type_equipement->name ?? 'Type non défini' }}</span>
                        @if($equipement->reference)<span class="mv-ref">{{ $equipement->reference }}</span>@endif
                        <span class="ca-badge {{ $toneStatut }}"><x-admin-icon :name="$iconeStatut" size="14" /> {{ $libelleStatut }}</span>
                    </div>
                </div>
            </div>
            <div class="mv-figures">
                <div class="mv-figure"><span>Stock actuel</span><strong>{{ $nombre($actuel) }}</strong></div>
                <div class="mv-figure"><span>Stock initial</span><strong>{{ $nombre($equipement->stock_initial) }}</strong></div>
                <div class="mv-figure"><span>Seuil d’alerte</span><strong>{{ $nombre($equipement->threshold) }}</strong></div>
                <div class="ca-progress mv-progress" title="{{ $taux }} % des quantités reçues encore en magasin"><span class="{{ $classeBarre }}" style="width: {{ $taux }}%"></span></div>
            </div>
        </div>
    </div>

    {{-- Indicateurs / filtres --}}
    <div class="ca-kpis ca-kpis--teintes">
        @foreach($filtres as [$filtre, $libelle, $couleur])
            <a href="{{ route('admin.hebergement.equipements.mouvements', array_filter(['materiel' => $equipement->equipement_id, 'type' => $filtre === 'tous' ? null : $filtre])) }}"
               class="ca-kpi {{ $couleur }} {{ $type === $filtre ? 'is-active' : '' }}"
               @if($type === $filtre) aria-current="page" @endif>
                <span class="ca-kpi-label">{{ $libelle }}</span>
                <span class="ca-kpi-row">
                    <strong class="ca-kpi-value">{{ $nombre($stats[$filtre]) }}</strong>
                    @if($filtre === 'entry')
                        <span class="ca-kpi-badge">+{{ $nombre($stats['entrees']) }} unité(s)</span>
                    @elseif($filtre === 'exit')
                        <span class="ca-kpi-badge">−{{ $nombre($stats['sorties']) }} unité(s)</span>
                    @elseif($stats['dernier'])
                        <span class="ca-kpi-badge">dernier le {{ \Illuminate\Support\Carbon::parse($stats['dernier'])->format('d/m/Y') }}</span>
                    @endif
                </span>
            </a>
        @endforeach
    </div>

    {{-- Journal --}}
    <div class="ca-card">
        <div class="ca-card-header">
            <h2 class="ca-card-title">{{ $titres[$type] }}</h2>
            @if($type !== 'tous')
                <a href="{{ route('admin.hebergement.equipements.mouvements', $equipement->equipement_id) }}" class="ca-link-btn">Voir tous les mouvements</a>
            @endif
        </div>

        <div class="ca-table-wrap">
            <table class="ca-table">
                <thead>
                <tr>
                    <th class="is-num">#</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Quantité</th>
                    <th>Motif</th>
                    <th>Saisi par</th>
                </tr>
                </thead>
                <tbody>
                @forelse($mouvements as $mouvement)
                    @php $entree = $mouvement->type === 'entry'; @endphp
                    <tr>
                        <td class="is-num">{{ ($mouvements->firstItem() ?? 1) + $loop->index }}</td>
                        <td>
                            <div class="mv-date">
                                <span class="ca-strong">{{ $mouvement->created_at?->format('d/m/Y') }}</span>
                                <span class="ca-muted ca-small">à {{ $mouvement->created_at?->format('H:i') }}</span>
                            </div>
                        </td>
                        <td>
                            @if($entree)
                                <span class="ca-badge tone-green"><x-admin-icon name="plus" size="14" /> Entrée</span>
                            @else
                                <span class="ca-badge tone-red"><x-admin-icon name="minus" size="14" /> Sortie</span>
                            @endif
                        </td>
                        <td><span class="mv-qty {{ $entree ? 'is-entry' : 'is-exit' }}">{{ $entree ? '+' : '−' }}{{ $nombre($mouvement->quantity) }}</span></td>
                        <td class="mv-reason">
                            @if($mouvement->reason)
                                <span title="{{ $mouvement->reason }}">{{ \Illuminate\Support\Str::limit($mouvement->reason, 80) }}</span>
                            @else
                                <span class="ca-muted">—</span>
                            @endif
                        </td>
                        <td>{{ $mouvement->createdBy->name ?? 'Non renseigné' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="ca-empty">
                                <span class="ca-empty-icon"><x-admin-icon name="history" size="22" /></span>
                                @if($type !== 'tous')
                                    <span>Aucun mouvement de ce type pour cet équipement.</span>
                                    <a href="{{ route('admin.hebergement.equipements.mouvements', $equipement->equipement_id) }}" class="ca-link-btn">Voir tous les mouvements</a>
                                @else
                                    <span>Aucun mouvement enregistré pour cet équipement.</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($mouvements->total() > 0)
            <div class="ca-card-footer">
                <span>{{ $mouvements->firstItem() }} à {{ $mouvements->lastItem() }} sur {{ $mouvements->total() }} mouvement(s)</span>
                {{ $mouvements->onEachSide(1)->links() }}
            </div>
        @endif
    </div>

    {{-- Mouvement de stock --}}
    @adminroute('admin.hebergement.equipements.store_mouvement')
    <dialog id="mv-move-form" class="ca-dialog is-narrow" aria-labelledby="mv-move-title" @if($formulaireOuvert === 'move') data-ca-autoopen @endif>
        <form method="POST" action="{{ route('admin.hebergement.equipements.store_mouvement') }}">
            @csrf
            <input type="hidden" name="_form" value="move">
            <input type="hidden" name="equipement_id" value="{{ $equipement->equipement_id }}">
            <div class="ca-dialog-head">
                <h3 class="ca-dialog-title" id="mv-move-title">Mouvement de stock</h3>
                <button type="button" class="ca-icon-btn" data-ca-close aria-label="Fermer"><x-admin-icon name="x" /></button>
            </div>
            <div class="ca-dialog-body">
                <p class="mv-target">
                    Équipement : <strong>{{ $equipement->name }}</strong> · stock actuel <strong>{{ $nombre($actuel) }}</strong>
                </p>
                <div class="ca-form-grid">
                    <fieldset class="ca-field" style="border:0;margin:0;padding:0">
                        <legend class="ca-label" style="margin-bottom:6px">Type de mouvement <span class="req">*</span></legend>
                        <div class="mv-radios">
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
                        <label class="ca-label" for="mv-move-qty">Quantité <span class="req">*</span></label>
                        <input type="number" class="ca-input" name="quantite" id="mv-move-qty" min="1" step="1" placeholder="0" value="{{ $formulaireOuvert === 'move' ? old('quantite') : '' }}" required>
                        <span class="ca-hint">Une sortie ne peut pas dépasser le stock actuel ({{ $nombre($actuel) }}).</span>
                    </div>
                    <div class="ca-field">
                        <label class="ca-label" for="mv-move-reason">Motif</label>
                        <textarea class="ca-input" name="motif" id="mv-move-reason" rows="2" maxlength="255" placeholder="Ex. Livraison fournisseur, matériel réformé…">{{ $formulaireOuvert === 'move' ? old('motif') : '' }}</textarea>
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
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/campagnes-admission.js') }}"></script>
@endpush
