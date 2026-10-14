@extends('admin.layouts.app', ['activeMenu' => 'hebergement', 'activeSubMenu' => 'vie-cite'])

@section('title', 'Gestion des sanctions')

@section('content')
<div class="sanction-shell">
    <x-breadcrumbs title="Gestion des sanctions" :items="[
        'Gestion Hébergement' => route('admin.hebergement.index'),
        'Vie de la cité' => route('admin.hebergement.vie_residentielle.index'),
    ]" />

    @if(session('success'))<div class="sanction-alert success"><i class="fa-solid fa-circle-check"></i>{{ session('success') }}</div>@endif
    @if($errors->any())<div class="sanction-alert error"><i class="fa-solid fa-triangle-exclamation"></i><div><strong>Veuillez corriger le formulaire.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif

    <header class="sanction-heading">
        <div><span>Vie de la cité</span><h1>Sanctions disciplinaires</h1><p>Appliquer le règlement tout en conservant une trace complète de chaque décision.</p></div>
        <button type="button" class="primary-button" data-open-sanction><i class="fa-solid fa-gavel"></i> Appliquer une sanction</button>
    </header>

    <div class="sanction-stats">
        <article><i class="fa-solid fa-scale-balanced blue"></i><div><small>Total enregistré</small><strong>{{ $stats['total'] }}</strong></div></article>
        <article><i class="fa-solid fa-circle-exclamation red"></i><div><small>Sanctions actives</small><strong>{{ $stats['active'] }}</strong></div></article>
        <article><i class="fa-solid fa-triangle-exclamation amber"></i><div><small>Avertissements actifs</small><strong>{{ $stats['avertissements'] }}</strong></div></article>
        <article><i class="fa-solid fa-user-lock purple"></i><div><small>Suspensions actives</small><strong>{{ $stats['suspensions'] }}</strong></div></article>
    </div>

    <section class="sanction-panel">
        <div class="panel-top">
            <div><span>Registre disciplinaire</span><h2>Historique des sanctions</h2></div>
            <form method="GET" class="filters">
                <select name="type"><option value="">Tous les types</option><option value="avertissement" @selected(request('type')==='avertissement')>Avertissement</option><option value="blame" @selected(request('type')==='blame')>Blâme</option><option value="suspension" @selected(request('type')==='suspension')>Suspension</option><option value="expulsion" @selected(request('type')==='expulsion')>Expulsion</option></select>
                <select name="statut"><option value="">Tous les statuts</option><option value="active" @selected(request('statut')==='active')>Active</option><option value="levee" @selected(request('statut')==='levee')>Levée</option><option value="annulee" @selected(request('statut')==='annulee')>Annulée</option></select>
                <select name="cite"><option value="">Toutes les cités</option>@foreach($cites as $cite)<option value="{{ $cite->id_cite }}" @selected(request('cite')===$cite->id_cite)>{{ $cite->name }}</option>@endforeach</select>
                <button><i class="fa-solid fa-filter"></i> Filtrer</button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="sanction-table">
                <thead><tr><th>Décision</th><th>Portée concernée</th><th>Type</th><th>Motif</th><th>Période</th><th>Statut</th><th>Traçabilité</th><th></th></tr></thead>
                <tbody>
                @forelse($sanctions as $sanction)
                    <tr>
                        <td><strong>{{ $sanction->numero_sanction }}</strong><small>{{ $sanction->date_decision?->format('d/m/Y') }}</small></td>
                        <td>
                            @switch($sanction->scope_type)
                                @case('cite') <strong>Toute la cité {{ $sanction->cite?->name }}</strong><small>Tous les résidents de la cité</small> @break
                                @case('batiment') <strong>Tout le bâtiment {{ $sanction->batiment?->name }}</strong><small>{{ $sanction->cite?->name }}</small> @break
                                @case('chambre') <strong>Toute la chambre {{ $sanction->chambre?->numero_chambre }}</strong><small>{{ $sanction->cite?->name }} · {{ $sanction->batiment?->name }}</small> @break
                                @default <strong>{{ $sanction->etudiant?->name ?? 'Résident inconnu' }}</strong><small>{{ $sanction->etudiant?->matricule }} · Chambre {{ $sanction->chambre?->numero_chambre }}</small>
                            @endswitch
                        </td>
                        <td><span class="type-badge {{ $sanction->type }}">{{ \App\Http\Services\DisciplinaryImpactService::LABELS[$sanction->type] ?? ucfirst($sanction->type) }}</span></td>
                        <td class="reason">{{ $sanction->motif }}</td>
                        <td>{{ $sanction->type === 'suspension' ? (($sanction->date_debut?->format('d/m/Y') ?? '—').' au '.($sanction->date_fin?->format('d/m/Y') ?? '—')) : '—' }}</td>
                        <td><span class="status-badge {{ $sanction->statut }}">{{ ucfirst($sanction->statut) }}</span></td>
                        <td><button type="button" class="history-button" data-history='@json($sanction->historique)' data-number="{{ $sanction->numero_sanction }}"><i class="fa-solid fa-clock-rotate-left"></i> {{ $sanction->historique->count() }} événement(s)</button></td>
                        <td>@adminroute('admin.hebergement.vie_residentielle.sanctions.status')
<button type="button" class="action-button" data-status-url="{{ route('admin.hebergement.vie_residentielle.sanctions.status', $sanction) }}" data-current="{{ $sanction->statut }}"><i class="fa-solid fa-pen"></i></button>
@endadminroute
</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty">Aucune sanction ne correspond aux critères.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $sanctions->links() }}</div>
    </section>
</div>

<!-- MODAL SANCTION -->
<div class="sanction-modal" id="sanctionModal" style="display:none;">
    <div class="modal-backdrop" data-close-modal></div>
    <div class="modal-card">
        <button class="modal-close" data-close-modal>&times;</button>
        <span class="modal-kicker">Nouvelle décision</span>
        <h2>Appliquer une sanction</h2>
        @adminroute('admin.hebergement.vie_residentielle.sanctions.store')
        <form method="POST" action="{{ route('admin.hebergement.vie_residentielle.sanctions.store') }}">@csrf
            <div class="form-grid">
                <label class="full"><span>Portée de la sanction *</span><select name="scope_type" id="scopeType" required><option value="etudiant" @selected(old('scope_type')==='etudiant')>Un résident précis</option><option value="chambre" @selected(old('scope_type')==='chambre')>Tous les résidents d’une chambre</option><option value="batiment" @selected(old('scope_type')==='batiment')>Tout un bâtiment</option><option value="cite" @selected(old('scope_type')==='cite')> Toute une cité</option></select></label>
                <label><span>1. Cité *</span><select name="cite_id" id="targetCite" required><option value="">Sélectionner une cité</option>@foreach($cites as $cite)<option value="{{ $cite->id_cite }}" @selected(old('cite_id')===$cite->id_cite)>{{ $cite->name }}</option>@endforeach</select></label>
                <label id="buildingField"><span>2. Bâtiment *</span><select name="batiment_id" id="targetBuilding"><option value="">Sélectionner d’abord une cité</option></select></label>
                <label id="roomField"><span>3. Chambre *</span><select name="chambre_id" id="targetRoom"><option value="">Sélectionner d’abord un bâtiment</option></select></label>
                <label id="residentField"><span>4. Résident *</span><select name="etudiant_id" id="targetResident"><option value="">Sélectionner d’abord une chambre</option></select></label>
                <label><span>Type de sanction *</span><select name="type" id="sanctionType" required><option value="avertissement">Avertissement</option><option value="blame">Blâme</option><option value="suspension">Suspension</option></select></label>
                <label><span>Date de décision *</span><input type="date" name="date_decision" value="{{ old('date_decision', now()->format('Y-m-d')) }}" required></label>
                <div class="suspension-dates"><label><span>Début de suspension *</span><input type="date" name="date_debut"></label><label><span>Fin de suspension *</span><input type="date" name="date_fin"></label></div>
                <label class="full"><span>Motif *</span><textarea name="motif" rows="3" required>{{ old('motif') }}</textarea></label>
                <label class="full"><span>Faits reprochés</span><textarea name="faits_reproches" rows="3">{{ old('faits_reproches') }}</textarea></label>
                <label class="full"><span>Commentaire interne</span><textarea name="commentaire" rows="2">{{ old('commentaire') }}</textarea></label>
            </div>
            <div class="form-actions">
                <button type="button" data-close-modal>Annuler</button>
                <button type="submit" class="primary-button"><i class="fa-solid fa-gavel"></i> Enregistrer la sanction</button>
            </div>
        </form>
        @endadminroute
    </div>
</div>

<!-- MODAL STATUT -->
<div class="sanction-modal" id="statusModal" style="display:none;">
    <div class="modal-backdrop" data-close-modal></div>
    <div class="modal-card small">
        <button class="modal-close" data-close-modal>&times;</button>
        <span class="modal-kicker">Suivi</span>
        <h2>Modifier le statut</h2>
        <form method="POST" id="statusForm">@csrf @method('PATCH')
            <label><span>Nouveau statut</span><select name="statut" id="newStatus"><option value="active">Active</option><option value="levee">Levée</option><option value="annulee">Annulée</option></select></label>
            <label><span>Justification *</span><textarea name="details" rows="4" required></textarea></label>
            <div class="form-actions">
                <button type="button" data-close-modal>Annuler</button>
                <button class="primary-button">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL HISTORIQUE -->
<div class="sanction-modal" id="historyModal" style="display:none;">
    <div class="modal-backdrop" data-close-modal></div>
    <div class="modal-card small">
        <button class="modal-close" data-close-modal>&times;</button>
        <span class="modal-kicker">Traçabilité</span>
        <h2 id="historyTitle">Historique</h2>
        <div id="historyContent" class="history-list"></div>
    </div>
</div>
@endsection

@push('styles')
<style>
:root{--s-ink:#18233b;--s-muted:#778198;--s-line:#e7ebf2;--s-blue:#315efb}.page-wrapper{background:#f5f7fb}.sanction-shell{max-width:1550px;margin:auto;padding:24px 28px 40px;font-family:Montserrat,sans-serif;color:var(--s-ink)}.sanction-heading{display:flex;justify-content:space-between;align-items:flex-end;gap:20px;margin:22px 0}.sanction-heading>div>span,.panel-top>div>span,.modal-kicker{font-size:var(--app-font-min, 15px);color:var(--s-blue);font-weight:750;text-transform:uppercase;letter-spacing:1px}.sanction-heading h1{font-size:27px;margin:5px 0}.sanction-heading p{font-size:var(--app-font-min, 15px);color:var(--s-muted);margin:0}.primary-button{border:0;border-radius:9px;background:var(--s-blue);color:#fff;padding:11px 15px;font-size:var(--app-font-min, 15px);font-weight:700;cursor:pointer}.sanction-alert{display:flex;gap:9px;padding:12px 14px;border-radius:10px;margin-top:14px;font-size:var(--app-font-min, 15px)}.sanction-alert.success{background:#eaf9f3;color:#16885d}.sanction-alert.error{background:#fff0ef;color:#c84943}.sanction-alert ul{margin:5px 0 0;padding-left:16px}.sanction-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:14px}.sanction-stats article,.sanction-panel{background:#fff;border:1px solid var(--s-line);border-radius:14px;box-shadow:0 7px 22px rgba(30,45,75,.04)}.sanction-stats article{display:flex;align-items:center;gap:11px;padding:15px}.sanction-stats i{width:36px;height:36px;border-radius:9px;display:grid;place-items:center}.sanction-stats i.blue{background:#edf1ff;color:#315efb}.sanction-stats i.red{background:#fff0ef;color:#e45851}.sanction-stats i.amber{background:#fff6e5;color:#da911b}.sanction-stats i.purple{background:#f1edff;color:#6d4fc8}.sanction-stats small,.sanction-stats strong{display:block}.sanction-stats small{font-size:var(--app-font-min, 15px);color:var(--s-muted)}.sanction-stats strong{font-size:20px;margin-top:3px}.sanction-panel{padding:18px}.panel-top{display:flex;align-items:flex-end;justify-content:space-between;gap:15px}.panel-top h2{font-size:var(--app-font-min, 15px);margin:4px 0 0}.filters{display:flex;gap:7px}.filters select,.filters button{height:36px;border:1px solid var(--s-line);border-radius:8px;background:#fff;padding:0 10px;font-size:var(--app-font-min, 15px)}.filters button{background:#26344e;color:#fff}.sanction-table{width:100%;border-collapse:collapse;margin-top:16px}.sanction-table th{background:#f7f9fc;color:#8a94a8;text-transform:uppercase;font-size:var(--app-font-min, 15px);letter-spacing:.5px;padding:10px;text-align:left}.sanction-table td{border-bottom:1px solid #eef1f5;padding:11px 10px;font-size:var(--app-font-min, 15px);vertical-align:middle}.sanction-table td strong,.sanction-table td small{display:block}.sanction-table td small{color:var(--s-muted);font-size:var(--app-font-min, 15px);margin-top:3px}.reason{max-width:240px}.type-badge,.status-badge{display:inline-flex;border-radius:7px;padding:5px 8px;font-weight:700;font-size:var(--app-font-min, 15px)}.type-badge.avertissement{background:#fff6e5;color:#c9820d}.type-badge.blame{background:#fff0ef;color:#d34c45}.type-badge.suspension{background:#f1edff;color:#694bc3}.status-badge.active{background:#fff0ef;color:#d34c45}.status-badge.levee{background:#eaf9f3;color:#168c5f}.status-badge.annulee{background:#eef1f5;color:#667187}.history-button,.action-button{border:0;background:#edf1ff;color:#315efb;border-radius:7px;padding:7px 9px;font-size:var(--app-font-min, 15px);cursor:pointer}.empty{text-align:center;padding:35px!important;color:var(--s-muted)}.pagination-wrap{margin-top:14px}

/* STYLES MODALS CORRIGÉS */
.sanction-modal {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(0, 0, 0, 0);
    transition: background 0.3s ease;
}

.sanction-modal:not([style*="display:none"]) {
    background: rgba(13, 22, 40, 0.55);
}

.sanction-modal[style*="display:none"] {
    display: none !important;
}

.modal-backdrop {
    position: fixed;
    inset: 0;
    background: transparent;
    z-index: -1;
}

.modal-card {
    position: relative;
    width: min(720px, 100%);
    max-height: 90vh;
    overflow: auto;
    background: #fff;
    border-radius: 15px;
    padding: 22px;
    box-shadow: 0 24px 70px rgba(0, 0, 0, 0.25);
    z-index: 10000;
    animation: modalFadeIn 0.25s ease;
}

@keyframes modalFadeIn {
    from {
        opacity: 0;
        transform: translateY(-20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modal-card.small {
    width: min(520px, 100%);
}

.modal-card h2 {
    font-size: 18px;
    margin: 5px 0 18px;
}

.modal-close {
    position: absolute;
    right: 15px;
    top: 12px;
    border: 0;
    background: transparent;
    font-size: 24px;
    color: #8992a3;
    cursor: pointer;
    z-index: 10001;
}

.modal-close:hover {
    color: #18233b;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.form-grid .full {
    grid-column: 1/-1;
}

.modal-card label {
    display: block;
}

.modal-card label>span {
    display: block;
    font-size: var(--app-font-min, 15px);
    font-weight: 650;
    color: #5f6b81;
    margin: 0 0 5px 2px;
}

.modal-card input,
.modal-card select,
.modal-card textarea {
    width: 100%;
    border: 1px solid var(--s-line);
    border-radius: 9px;
    padding: 10px;
    font-size: var(--app-font-min, 15px);
    outline: 0;
    background: #fff;
}

.modal-card input:focus,
.modal-card select:focus,
.modal-card textarea:focus {
    border-color: #315efb;
    box-shadow: 0 0 0 3px rgba(49, 94, 251, 0.1);
}

.suspension-dates {
    display: none;
    grid-column: 1/-1;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    background: #f8f9fc;
    padding: 11px;
    border-radius: 10px;
}

.suspension-dates.visible {
    display: grid;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    border-top: 1px solid var(--s-line);
    margin-top: 17px;
    padding-top: 14px;
}

.form-actions>button:not(.primary-button) {
    border: 1px solid var(--s-line);
    background: #fff;
    border-radius: 9px;
    padding: 10px 14px;
    font-size: var(--app-font-min, 15px);
    cursor: pointer;
}

.form-actions>button:not(.primary-button):hover {
    background: #f7f9fc;
}

.history-list {
    display: grid;
    gap: 11px;
    max-height: 400px;
    overflow-y: auto;
}

.history-item {
    border-left: 3px solid #315efb;
    padding: 4px 0 4px 12px;
}

.history-item strong,
.history-item small,
.history-item span {
    display: block;
}

.history-item strong {
    font-size: var(--app-font-min, 15px);
    text-transform: capitalize;
}

.history-item span {
    font-size: var(--app-font-min, 15px);
    margin: 4px 0;
}

.history-item small {
    font-size: var(--app-font-min, 15px);
    color: var(--s-muted);
}

@media (max-width: 900px) {
    .sanction-stats {
        grid-template-columns: 1fr 1fr;
    }
    .panel-top {
        align-items: flex-start;
        flex-direction: column;
    }
    .filters {
        width: 100%;
        flex-wrap: wrap;
    }
    .filters select {
        flex: 1;
    }
    .table-responsive {
        overflow-x: auto;
    }
    .sanction-table {
        min-width: 1050px;
    }
}

@media (max-width: 560px) {
    .sanction-shell {
        padding: 18px 12px;
    }
    .sanction-heading {
        align-items: flex-start;
        flex-direction: column;
    }
    .sanction-stats,
    .form-grid {
        grid-template-columns: 1fr;
    }
    .form-grid .full {
        grid-column: auto;
    }
    .suspension-dates {
        grid-template-columns: 1fr;
    }
    .filters select,
    .filters button {
        width: 100%;
        flex: none;
    }
    .modal-card {
        padding: 16px;
        width: 95%;
    }
}
</style>
@endpush

@push('scripts')
@php
    $oldSanctionTarget = [
        'building' => old('batiment_id'),
        'room' => old('chambre_id'),
        'resident' => old('etudiant_id'),
    ];
@endphp
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Gestion des modals
    const modals = document.querySelectorAll('.sanction-modal');

    // Fonction pour fermer tous les modals
    const closeAllModals = () => {
        modals.forEach(modal => modal.style.display = 'none');
    };

    // Fermeture par clic sur backdrop
    document.querySelectorAll('[data-close-modal]').forEach(button => {
        button.addEventListener('click', closeAllModals);
    });

    // Fermeture par Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeAllModals();
    });

    // Ouvrir le modal de sanction
    document.querySelector('[data-open-sanction]')?.addEventListener('click', () => {
        document.querySelector('#sanctionModal').style.display = 'flex';
    });

    // Ciblage hiérarchique : cité → bâtiment → chambre → résident
    const buildings = @json($buildings);
    const rooms = @json($rooms);
    const residents = @json($residents);
    const oldTarget = {{ Js::from($oldSanctionTarget) }};
    const scopeType = document.querySelector('#scopeType');
    const citeSelect = document.querySelector('#targetCite');
    const buildingSelect = document.querySelector('#targetBuilding');
    const roomSelect = document.querySelector('#targetRoom');
    const residentSelect = document.querySelector('#targetResident');
    const setOptions = (select, items, placeholder, valueKey, labelBuilder, selected = null) => {
        select.replaceChildren(new Option(placeholder, ''));
        items.forEach(item => select.add(new Option(labelBuilder(item), item[valueKey], false, String(item[valueKey]) === String(selected || ''))));
    };
    const loadResidents = selected => setOptions(residentSelect, residents.filter(item => String(item.chambre_id) === String(roomSelect.value)), 'Sélectionner un résident', 'id', item => `${item.name} — ${item.matricule || 'sans matricule'}`, selected);
    const loadRooms = (roomSelected, residentSelected) => {
        setOptions(roomSelect, rooms.filter(item => String(item.batiment_id) === String(buildingSelect.value)), 'Sélectionner une chambre', 'id_chambre', item => `Chambre ${item.numero_chambre}`, roomSelected);
        loadResidents(residentSelected);
    };
    const loadBuildings = (buildingSelected, roomSelected, residentSelected) => {
        setOptions(buildingSelect, buildings.filter(item => String(item.cite_id) === String(citeSelect.value)), 'Sélectionner un bâtiment', 'id_batiment', item => item.name, buildingSelected);
        loadRooms(roomSelected, residentSelected);
    };
    citeSelect?.addEventListener('change', () => loadBuildings());
    buildingSelect?.addEventListener('change', () => loadRooms());
    roomSelect?.addEventListener('change', () => loadResidents());
    const updateScopeFields = () => {
        const scope = scopeType.value;
        const needsBuilding = scope !== 'cite';
        const needsRoom = ['chambre', 'etudiant'].includes(scope);
        const needsResident = scope === 'etudiant';
        document.querySelector('#buildingField').hidden = !needsBuilding;
        document.querySelector('#roomField').hidden = !needsRoom;
        document.querySelector('#residentField').hidden = !needsResident;
        buildingSelect.required = needsBuilding;
        roomSelect.required = needsRoom;
        residentSelect.required = needsResident;
    };
    scopeType?.addEventListener('change', updateScopeFields);
    loadBuildings(oldTarget.building, oldTarget.room, oldTarget.resident);
    updateScopeFields();

    // Toggle des dates de suspension
    const type = document.querySelector('#sanctionType');
    const dates = document.querySelector('.suspension-dates');

    const toggleDates = () => {
        const required = type?.value === 'suspension';
        if (dates) {
            dates.classList.toggle('visible', required);
            dates.querySelectorAll('input').forEach(input => input.required = required);
        }
    };

    type?.addEventListener('change', toggleDates);
    toggleDates();

    // Modal de modification de statut
    document.querySelectorAll('[data-status-url]').forEach(button => {
        button.addEventListener('click', () => {
            document.querySelector('#statusForm').action = button.dataset.statusUrl;
            document.querySelector('#newStatus').value = button.dataset.current;
            document.querySelector('#statusModal').style.display = 'flex';
        });
    });

    // Modal d'historique
    document.querySelectorAll('.history-button').forEach(button => {
        button.addEventListener('click', () => {
            try {
                const items = JSON.parse(button.dataset.history || '[]');
                document.querySelector('#historyTitle').textContent = 'Historique ' + button.dataset.number;
                const content = document.querySelector('#historyContent');
                content.replaceChildren();

                if (Array.isArray(items) && items.length > 0) {
                    items.forEach(item => {
                        const row = document.createElement('div');
                        row.className = 'history-item';

                        const action = document.createElement('strong');
                        action.textContent = (item.action || 'Événement').replace(/_/g, ' ');

                        const details = document.createElement('span');
                        details.textContent = item.details || '';

                        const meta = document.createElement('small');
                        const date = item.created_at ? new Date(item.created_at).toLocaleString('fr-FR') : 'Date inconnue';
                        meta.textContent = `${date} · ${item.ancien_statut || '—'} → ${item.nouveau_statut || '—'}`;

                        row.append(action, details, meta);
                        content.append(row);
                    });
                } else {
                    const empty = document.createElement('p');
                    empty.textContent = 'Aucun événement enregistré.';
                    empty.style.color = '#778198';
                    empty.style.textAlign = 'center';
                    empty.style.padding = '20px 0';
                    content.append(empty);
                }

                document.querySelector('#historyModal').style.display = 'flex';
            } catch (error) {
                console.error('Erreur lors du chargement de l\'historique:', error);
                const content = document.querySelector('#historyContent');
                content.replaceChildren();
                const errorMsg = document.createElement('p');
                errorMsg.textContent = 'Erreur lors du chargement des données.';
                errorMsg.style.color = '#c84943';
                content.append(errorMsg);
                document.querySelector('#historyModal').style.display = 'flex';
            }
        });
    });

    // Si erreurs de validation, ouvrir le modal
    @if($errors->any())
        document.querySelector('#sanctionModal').style.display = 'flex';
    @endif
});
</script>
@endpush
