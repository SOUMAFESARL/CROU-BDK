@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'residents'
])

@section('title', 'Campagne '.$campagne->annee_campagne)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/campagnes-admission.css') }}">
    <link rel="stylesheet" href="{{ asset('css/allocation-chambres.css') }}">
@endpush

@php
    $nombre = fn ($valeur) => number_format((float) $valeur, 0, ',', ' ');
    $pourcent = fn ($valeur) => rtrim(rtrim(number_format((float) $valeur, 2, ',', ' '), '0'), ',');
    $initiales = fn ($nom) => collect(preg_split('/\s+/', trim((string) $nom)))->filter()->take(2)
        ->map(fn ($mot) => mb_strtoupper(mb_substr($mot, 0, 1)))->implode('') ?: '?';
    // Libellé du niveau : le code stocké sur la demande est souvent l'identifiant du niveau.
    $niveau = function ($etudiant) {
        if (!$etudiant) return null;
        $demande = $etudiant->demandeActuelle();
        return $demande?->niveauEtudiantActuel?->name ?? $demande?->niveau_etude_actuel_code;
    };
    $tonesAssignation = [
        'confirmee' => ['tone-green', 'circle-check'],
        'en_attente' => ['tone-amber', 'hourglass'],
    ];
    $onglets = [
        'assignations' => ['Attributions', $assignations->count(), 'bed-double'],
        'etudiants' => ['Éligibles sans chambre', $etudiants_sans_chambre->count(), 'user-x'],
        'chambres' => ['Chambres disponibles', $chambres_disponibles->count(), 'door-open'],
    ];
    $taux = max(0, min(100, (float) ($stats['taux_allocation'] ?? 0)));
@endphp

@section('content')
<div class="as-refonte ca-page">
    <x-breadcrumbs
            :title="'Campagne '.$campagne->annee_campagne"
            :items="[
                'Gestion Hébergement' => route('admin.hebergement.index'),
                'Attribution des chambres' => route('admin.allocation.index'),
                $campagne->annee_campagne => null,
            ]"
    />

    <div class="ca-header">
        <div class="ca-header-text">
            <p class="ca-subtitle">
                <span class="ca-badge {{ $campagne->statut === 'active' ? 'tone-green' : 'tone-gray' }}" style="margin-right:6px">{{ $campagne->getLabelStatut() }}</span>
                {{ $campagne->description ?: 'Suivi des propositions et des attributions de chambres de la campagne.' }}
            </p>
        </div>
        <div class="ca-actions">
            @adminroute('admin.allocation.index')
            <a href="{{ route('admin.allocation.index') }}" class="ca-btn ca-btn-outline"><x-admin-icon name="arrow-left" /> Allocations</a>
            @endadminroute
            @adminroute('admin.allocation.statistics')
            <a href="{{ route('admin.allocation.statistics', $campagne->id_campagne) }}" class="ca-btn ca-btn-outline"><x-admin-icon name="chart-column" /> Statistiques</a>
            @endadminroute
            @adminroute('admin.allocation.export-csv')
            <a href="{{ route('admin.allocation.export-csv', $campagne->id_campagne) }}" class="ca-btn ca-btn-outline"><x-admin-icon name="download" /> Exporter (CSV)</a>
            @endadminroute
            @adminroute('admin.allocation.allocate-all')
            <button type="button" class="ca-btn ca-btn-soft" data-ca-open="al-auto-form" @disabled($etudiants_sans_chambre->isEmpty())
                    @if($etudiants_sans_chambre->isEmpty()) title="Aucun étudiant éligible sans chambre" @endif><x-admin-icon name="zap" /> Attribution automatique</button>
            @endadminroute
            @adminroute('admin.allocation.allocate-form')
            <a href="{{ route('admin.allocation.allocate-form', $campagne->id_campagne) }}" class="ca-btn ca-btn-primary"><x-admin-icon name="plus" /> Attribuer une chambre</a>
            @endadminroute
        </div>
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

    {{-- Indicateurs --}}
    <div class="ca-kpis ca-kpis--teintes">
        <div class="ca-kpi kpi-blue">
            <span class="ca-kpi-label">Éligibles sans chambre</span>
            <span class="ca-kpi-row"><strong class="ca-kpi-value">{{ $nombre($etudiants_sans_chambre->count()) }}</strong></span>
        </div>
        <div class="ca-kpi kpi-amber">
            <span class="ca-kpi-label">Propositions en attente</span>
            <span class="ca-kpi-row"><strong class="ca-kpi-value">{{ $nombre($stats['assignations_en_attente'] ?? 0) }}</strong></span>
        </div>
        <div class="ca-kpi kpi-green">
            <span class="ca-kpi-label">Attributions confirmées</span>
            <span class="ca-kpi-row"><strong class="ca-kpi-value">{{ $nombre($stats['assignations_confirmees'] ?? 0) }}</strong></span>
        </div>
        <div class="ca-kpi kpi-plum">
            <span class="ca-kpi-label">Chambres avec place libre</span>
            <span class="ca-kpi-row"><strong class="ca-kpi-value">{{ $nombre($chambres_disponibles->count()) }}</strong></span>
        </div>
    </div>

    @include('admin.hebergement.resident_contrat.ranking-summary')

    @if(!empty($stats))
        <div class="ca-card">
            <div class="ca-card-body al-rate-card">
                <div>
                    <span class="ca-kpi-label">Demandes attribuées définitivement</span>
                    <div class="al-rate-big">
                        <strong>{{ $pourcent($taux) }} %</strong>
                        <span class="ca-muted">{{ $nombre($stats['demandes_acceptees'] ?? 0) }} demande(s) au statut « Acceptée »</span>
                    </div>
                    <div class="ca-progress" style="margin-top:10px"><span style="width: {{ $taux }}%"></span></div>
                </div>
                @if(!empty($stats['par_type']))
                    <dl class="ca-dl">
                        @foreach($stats['par_type'] as $data)
                            <div class="ca-dl-row">
                                <dt>Chambres {{ $data['nom'] }}</dt>
                                <dd>
                                    {{ $nombre($data['occupees']) }} attribuée(s)
                                    <span class="ca-muted" style="font-weight:400">· {{ $nombre($data['disponibles']) }} disponible(s) / {{ $nombre($data['total']) }}</span>
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </div>
        </div>
    @endif

    {{-- Onglets --}}
    <div class="ca-card" data-al-tabs>
        <div class="al-tabs" role="tablist" aria-label="Suivi de la campagne">
            @foreach($onglets as $cle => [$libelle, $total, $icone])
                <button type="button" class="al-tab" role="tab" id="al-tab-{{ $cle }}" aria-controls="al-panel-{{ $cle }}"
                        aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? '0' : '-1' }}" data-al-tab="{{ $cle }}">
                    <x-admin-icon :name="$icone" size="16" /> {{ $libelle }} <span class="al-tab-count">{{ $nombre($total) }}</span>
                </button>
            @endforeach
        </div>

        {{-- Attributions --}}
        <div class="al-panel" role="tabpanel" id="al-panel-assignations" aria-labelledby="al-tab-assignations" data-al-panel="assignations">
            @if($assignations->count() > 0)
                <div class="al-panel-tools">
                    <label class="al-search">
                        <span class="sr-only">Rechercher une attribution</span>
                        <x-admin-icon name="search" size="16" />
                        <input type="search" class="ca-input" placeholder="Nom, matricule, chambre…" autocomplete="off" data-al-filter="assignations">
                    </label>
                    <span class="ca-muted" data-al-count="assignations"></span>
                </div>
                <div class="ca-table-wrap">
                    <table class="ca-table">
                        <thead>
                        <tr>
                            <th>Étudiant</th>
                            <th>Niveau</th>
                            <th>Chambre</th>
                            <th>Type</th>
                            <th>État du dossier</th>
                            <th class="is-actions"><span class="sr-only">Actions</span></th>
                        </tr>
                        </thead>
                        <tbody data-al-rows="assignations">
                        @foreach($assignations as $assignation)
                            @php
                                $etudiant = $assignation->etudiant;
                                $chambre = $assignation->chambre;
                                [$tone, $icone] = $tonesAssignation[$assignation->statut] ?? ['tone-red', 'circle-x'];
                                $etat = \App\Http\Services\DossierWorkflow::LABELS[$assignation->dossier?->etat_dossier] ?? ucfirst(str_replace('_', ' ', (string) $assignation->statut));
                                $echeance = $assignation->statut === 'en_attente' && $assignation->date_fin ? \Illuminate\Support\Carbon::parse($assignation->date_fin) : null;
                                $nomChambre = 'Chambre '.($chambre?->numero_chambre ?? 'N/D').($assignation->lit ? ' · lit '.$assignation->lit->numero : '');
                            @endphp
                            <tr data-al-text="{{ $etudiant?->name }} {{ $etudiant?->matricule }} {{ $chambre?->numero_chambre }} {{ $chambre?->batiment?->name }} {{ $etat }}">
                                <td>
                                    <div class="ca-person">
                                        <span class="ca-avatar" aria-hidden="true">{{ $initiales($etudiant?->name) }}</span>
                                        <div>
                                            <span class="ca-strong">{{ $etudiant?->name ?? 'Étudiant introuvable' }}</span>
                                            <span class="ca-muted ca-small ca-mono" style="display:block">{{ $etudiant?->matricule ?? 'N/D' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $niveau($etudiant) ?? '—' }}</td>
                                <td>
                                    <span class="ca-strong">{{ $chambre?->numero_chambre ?? 'N/D' }}</span>
                                    <span class="ca-muted ca-small" style="display:block">
                                        {{ $assignation->lit ? 'Lit '.$assignation->lit->numero : 'Lit non renseigné' }}@if($chambre?->batiment) · {{ $chambre->batiment->name }}@endif
                                    </span>
                                </td>
                                <td><span class="ca-badge tone-blue">{{ ucfirst($chambre?->typeChambre?->name ?? 'Non défini') }}</span></td>
                                <td>
                                    <span class="ca-badge {{ $tone }}"><x-admin-icon :name="$icone" size="14" /> {{ $etat }}</span>
                                    @if($echeance)
                                        <span class="ca-muted ca-small" style="display:block;margin-top:4px">Réponse attendue avant le {{ $echeance->format('d/m/Y à H:i') }}</span>
                                    @endif
                                </td>
                                <td class="is-actions">
                                    @if($assignation->statut === 'en_attente' && $assignation->dossier?->etat_dossier === 'accepte')
                                        @adminroute('admin.allocation.confirm')
                                        <button type="button" class="ca-icon-btn" title="Valider définitivement" aria-label="Valider définitivement l’attribution de {{ $etudiant?->name }}"
                                                data-ca-open="al-confirm-form" data-al-assignation="{{ $assignation->id_assignation }}" data-al-nom="{{ $etudiant?->name }}" data-al-chambre="{{ $nomChambre }}">
                                            <x-admin-icon name="user-check" />
                                        </button>
                                        @endadminroute
                                    @endif
                                    @if($assignation->statut === 'en_attente')
                                        @adminroute('admin.allocation.cancel')
                                        <button type="button" class="ca-icon-btn is-danger" title="Annuler la proposition" aria-label="Annuler la proposition faite à {{ $etudiant?->name }}"
                                                data-ca-open="al-cancel-form" data-al-assignation="{{ $assignation->id_assignation }}" data-al-nom="{{ $etudiant?->name }}" data-al-chambre="{{ $nomChambre }}">
                                            <x-admin-icon name="x" />
                                        </button>
                                        @endadminroute
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="ca-empty" data-al-empty="assignations" hidden>
                    <span class="ca-empty-icon"><x-admin-icon name="search" size="22" /></span>
                    <span>Aucune attribution ne correspond à cette recherche.</span>
                </div>
            @else
                <div class="ca-empty">
                    <span class="ca-empty-icon"><x-admin-icon name="bed-double" size="22" /></span>
                    <span>Aucune attribution pour le moment.</span>
                </div>
            @endif
        </div>

        {{-- Éligibles sans chambre --}}
        <div class="al-panel" role="tabpanel" id="al-panel-etudiants" aria-labelledby="al-tab-etudiants" data-al-panel="etudiants" hidden>
            @if($etudiants_sans_chambre->count() > 0)
                <div class="al-panel-tools">
                    <label class="al-search">
                        <span class="sr-only">Rechercher un étudiant</span>
                        <x-admin-icon name="search" size="16" />
                        <input type="search" class="ca-input" placeholder="Nom, matricule, e-mail…" autocomplete="off" data-al-filter="etudiants">
                    </label>
                    <span class="ca-muted" data-al-count="etudiants"></span>
                </div>
                <div class="ca-table-wrap">
                    <table class="ca-table">
                        <thead>
                        <tr>
                            <th>Étudiant</th>
                            <th>E-mail</th>
                            <th>Niveau</th>
                            <th>PMR</th>
                            <th class="is-actions"><span class="sr-only">Actions</span></th>
                        </tr>
                        </thead>
                        <tbody data-al-rows="etudiants">
                        @foreach($etudiants_sans_chambre as $etudiant)
                            <tr data-al-text="{{ $etudiant->name }} {{ $etudiant->matricule }} {{ $etudiant->email1 }}">
                                <td>
                                    <div class="ca-person">
                                        <span class="ca-avatar" aria-hidden="true">{{ $initiales($etudiant->name) }}</span>
                                        <div>
                                            <span class="ca-strong">{{ $etudiant->name }}</span>
                                            <span class="ca-muted ca-small ca-mono" style="display:block">{{ $etudiant->matricule }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $etudiant->email1 ?: '—' }}</td>
                                <td>{{ $niveau($etudiant) ?? '—' }}</td>
                                <td>
                                    @if($etudiant->pmr_confirme)
                                        <span class="ca-badge tone-blue"><x-admin-icon name="accessibility" size="14" /> Confirmé</span>
                                    @else
                                        <span class="ca-muted">—</span>
                                    @endif
                                </td>
                                <td class="is-actions">
                                    @adminroute('admin.allocation.allocate-form')
                                    <a href="{{ route('admin.allocation.allocate-form', ['campagneId' => $campagne->id_campagne, 'etudiant_id' => $etudiant->id_etudiant]) }}"
                                       class="ca-btn ca-btn-outline ca-btn-sm" aria-label="Attribuer une chambre à {{ $etudiant->name }}">
                                        <x-admin-icon name="bed-double" size="16" /> Attribuer
                                    </a>
                                    @endadminroute
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="ca-empty" data-al-empty="etudiants" hidden>
                    <span class="ca-empty-icon"><x-admin-icon name="search" size="22" /></span>
                    <span>Aucun étudiant ne correspond à cette recherche.</span>
                </div>
            @else
                <div class="ca-empty">
                    <span class="ca-empty-icon"><x-admin-icon name="circle-check" size="22" /></span>
                    <span>Tous les dossiers éligibles ont reçu une proposition de chambre.</span>
                </div>
            @endif
        </div>

        {{-- Chambres disponibles --}}
        <div class="al-panel" role="tabpanel" id="al-panel-chambres" aria-labelledby="al-tab-chambres" data-al-panel="chambres" hidden>
            @if($chambres_disponibles->count() > 0)
                <div class="al-panel-tools">
                    <label class="al-search">
                        <span class="sr-only">Rechercher une chambre</span>
                        <x-admin-icon name="search" size="16" />
                        <input type="search" class="ca-input" placeholder="Numéro, type, bâtiment, cité…" autocomplete="off" data-al-filter="chambres">
                    </label>
                    <span class="ca-muted" data-al-count="chambres"></span>
                </div>
                <div class="ca-card-body">
                    <div class="al-grid" data-al-rows="chambres">
                        @foreach($chambres_disponibles as $chambre)
                            @php
                                $capacite = (int) $chambre->capacite_max;
                                $libres = $chambre->getNombrePlacesDisponibles();
                                $occupation = max(0, min(100, (float) $chambre->getPourcentageOccupation()));
                                $type = ucfirst($chambre->typeChambre?->name ?? 'Non défini');
                            @endphp
                            <article class="al-tile" data-al-text="{{ $chambre->numero_chambre }} {{ $type }} {{ $chambre->batiment?->name }} {{ $chambre->cite?->name }}">
                                <div class="al-tile-head">
                                    <div>
                                        <h3 class="al-tile-title">Chambre {{ $chambre->numero_chambre }}</h3>
                                        <span class="al-tile-sub">{{ collect([$chambre->batiment?->name, $chambre->cite?->name])->filter()->implode(' · ') ?: '—' }}</span>
                                    </div>
                                    <span class="ca-badge tone-blue">{{ $type }}</span>
                                </div>
                                <dl class="ca-dl">
                                    <div class="ca-dl-row"><dt>Places libres</dt><dd style="color:var(--ca-primary)">{{ $libres }} / {{ $capacite }}</dd></div>
                                    <div class="ca-dl-row"><dt>Loyer</dt><dd>{{ $nombre($chambre->prix) }} FCFA</dd></div>
                                </dl>
                                <div>
                                    <div class="al-tile-foot"><span>Occupation</span><span>{{ $pourcent($occupation) }} %</span></div>
                                    <div class="ca-progress" style="margin-top:4px"><span style="width: {{ $occupation }}%"></span></div>
                                </div>
                                @if($chambre->pmr)
                                    <span class="ca-badge tone-purple" style="align-self:flex-start"><x-admin-icon name="accessibility" size="14" /> Adaptée PMR</span>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </div>
                <div class="ca-empty" data-al-empty="chambres" hidden>
                    <span class="ca-empty-icon"><x-admin-icon name="search" size="22" /></span>
                    <span>Aucune chambre ne correspond à cette recherche.</span>
                </div>
            @else
                <div class="ca-empty">
                    <span class="ca-empty-icon"><x-admin-icon name="door-open" size="22" /></span>
                    <span>Aucune chambre disponible dans les résidences de cette campagne.</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Attribution automatique --}}
    @adminroute('admin.allocation.allocate-all')
    <dialog id="al-auto-form" class="ca-dialog is-narrow" aria-labelledby="al-auto-title" role="alertdialog">
        <form method="POST" action="{{ route('admin.allocation.allocate-all', $campagne->id_campagne) }}" data-al-once>
            @csrf
            <div class="ca-dialog-head">
                <h3 class="ca-dialog-title" id="al-auto-title">Attribution automatique</h3>
                <button type="button" class="ca-icon-btn" data-ca-close aria-label="Fermer"><x-admin-icon name="x" /></button>
            </div>
            <div class="ca-dialog-body">
                <p style="margin:0">Une proposition de chambre sera faite à chacun des <strong>{{ $nombre($etudiants_sans_chambre->count()) }}</strong> dossier(s) éligible(s) sans chambre, dans l’ordre du classement, tant qu’il reste des places compatibles.</p>
                <p class="ca-muted ca-small" style="margin:8px 0 0">Les étudiants devront accepter leur proposition avant la validation définitive.</p>
            </div>
            <div class="ca-dialog-foot">
                <button type="button" class="ca-btn ca-btn-outline" data-ca-close>Annuler</button>
                <button type="submit" class="ca-btn ca-btn-primary"><x-admin-icon name="zap" /> Lancer l’attribution</button>
            </div>
        </form>
    </dialog>
    @endadminroute

    {{-- Validation définitive --}}
    @adminroute('admin.allocation.confirm')
    <dialog id="al-confirm-form" class="ca-dialog is-narrow" aria-labelledby="al-confirm-title" role="alertdialog">
        <form method="POST" action="{{ route('admin.allocation.confirm', $campagne->id_campagne) }}" data-al-once>
            @csrf
            <input type="hidden" name="assignation_id" value="" data-al-field="assignation">
            <div class="ca-dialog-head">
                <h3 class="ca-dialog-title" id="al-confirm-title">Valider définitivement</h3>
                <button type="button" class="ca-icon-btn" data-ca-close aria-label="Fermer"><x-admin-icon name="x" /></button>
            </div>
            <div class="ca-dialog-body">
                <p style="margin:0"><strong data-al-field="nom"></strong> a accepté la proposition : <strong data-al-field="chambre"></strong>.</p>
                <p class="ca-muted ca-small" style="margin:8px 0 0">L’attribution devient définitive et le dossier passe à l’état « Attribué définitif ».</p>
            </div>
            <div class="ca-dialog-foot">
                <button type="button" class="ca-btn ca-btn-outline" data-ca-close>Annuler</button>
                <button type="submit" class="ca-btn ca-btn-primary"><x-admin-icon name="check" /> Valider</button>
            </div>
        </form>
    </dialog>
    @endadminroute

    {{-- Annulation d'une proposition --}}
    @adminroute('admin.allocation.cancel')
    <dialog id="al-cancel-form" class="ca-dialog is-narrow" aria-labelledby="al-cancel-title" role="alertdialog">
        <form method="POST" action="{{ route('admin.allocation.cancel', $campagne->id_campagne) }}" data-al-once>
            @csrf
            <input type="hidden" name="assignation_id" value="" data-al-field="assignation">
            <div class="ca-dialog-head">
                <h3 class="ca-dialog-title" id="al-cancel-title">Annuler la proposition</h3>
                <button type="button" class="ca-icon-btn" data-ca-close aria-label="Fermer"><x-admin-icon name="x" /></button>
            </div>
            <div class="ca-dialog-body">
                <p style="margin:0 0 14px">La proposition « <strong data-al-field="chambre"></strong> » faite à <strong data-al-field="nom"></strong> sera annulée et la place libérée.</p>
                <div class="ca-field">
                    <label class="ca-label" for="al-cancel-motif">Motif <span class="ca-hint">(facultatif)</span></label>
                    <textarea id="al-cancel-motif" name="motif" class="ca-input" rows="3" maxlength="255" placeholder="Ex. : erreur de chambre, demande de l’étudiant…"></textarea>
                    <span class="ca-hint">255 caractères au maximum.</span>
                </div>
            </div>
            <div class="ca-dialog-foot">
                <button type="button" class="ca-btn ca-btn-outline" data-ca-close>Retour</button>
                <button type="submit" class="ca-btn ca-btn-danger"><x-admin-icon name="x" /> Annuler la proposition</button>
            </div>
        </form>
    </dialog>
    @endadminroute
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/campagnes-admission.js') }}"></script>
    <script src="{{ asset('js/allocation-chambres.js') }}"></script>
@endpush
