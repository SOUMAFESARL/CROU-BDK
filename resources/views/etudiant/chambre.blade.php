
<div id="page-chambre" class="content">

    {{-- ============================================
        EN-TÊTE DE LA CHAMBRE
    ============================================ --}}
    <div class="chambre-hero">
        <div class="chambre-hero-left">
            <div class="chambre-icon-big">
                <svg width="30" height="30" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
            </div>
            <div>
                <h2>{{ $data['derniereAssignation']->chambre?->numero_chambre ?? 'Non défini' }}</h2>
                <p>
                    {{ $data['derniereAssignation']->chambre?->batiment?->name ?? 'Non défini' }} ·
                    {{ $data['derniereAssignation']->chambre?->cite?->name ?? 'Non défini' }} ·
                    {{ $data['derniereAssignation']->chambre?->campus?->name ?? 'Non défini' }}
                </p>
            </div>
        </div>
        <div class="chambre-hero-right">
            <span class="status-badge occupied">🟢 Occupée</span>
            <button class="btn btn-outline-light" onclick="showToast('📸 Plan de la chambre en cours de chargement...')">
                🗺️ Voir le plan
            </button>
        </div>
    </div>

    {{-- ============================================
        GRILLE DES STATISTIQUES DE LA CHAMBRE
    ============================================ --}}
    <div class="chambre-stats-grid">
        <div class="chambre-stat-card">
            <span class="stat-icon">🛏️</span>
            <div class="stat-content">
                <span class="stat-label">Type de chambre</span>
                <span class="stat-value">{{ $data['derniereAssignation']->chambre?->typeChambre?->name ?? 'Non défini' }}</span>
            </div>
        </div>
        <div class="chambre-stat-card">
            <span class="stat-icon">💰</span>
            <div class="stat-content">
                <span class="stat-label">Loyer mensuel</span>
                <span class="stat-value">{{ formatSystemAmount($data['derniereAssignation']->chambre?->typeChambre?->montant ?? 0) }}</span>
            </div>
        </div>
        <div class="chambre-stat-card">
            <span class="stat-icon">🏢</span>
            <div class="stat-content">
                <span class="stat-label">Bâtiment</span>
                <span class="stat-value">{{ $data['derniereAssignation']->chambre?->batiment?->name ?? 'Non défini' }}</span>
            </div>
        </div>
        <div class="chambre-stat-card">
            <span class="stat-icon">📐</span>
            <div class="stat-content">
                <span class="stat-label">Superficie</span>
                <span class="stat-value">{{ $data['derniereAssignation']->chambre?->superficie ?? '12' }} m²</span>
            </div>
        </div>
    </div>

    {{-- ============================================
        GRILLE PRINCIPALE
    ============================================ --}}
    <div class="chambre-grid">

        {{-- COLONNE GAUCHE : CARACTÉRISTIQUES --}}
        <div class="panel panel-info">
            <div class="panel-header">
                <h2><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i> Caractéristiques</h2>
                <span class="panel-badge">Détails</span>
            </div>
            <div class="panel-body">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-key">🔑 Numéro</span>
                        <span class="info-value">{{ $data['derniereAssignation']->chambre?->numero_chambre ?? 'Non défini' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-key">🏠 Type</span>
                        <span class="info-value">{{ $data['derniereAssignation']->chambre?->typeChambre?->name ?? 'Non défini' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-key">📐 Superficie</span>
                        <span class="info-value">{{ $data['derniereAssignation']->chambre?->superficie ?? '12' }} m²</span>
                    </div>
                    <div class="info-item">
                        <span class="info-key">🏢 Bâtiment</span>
                        <span class="info-value">{{ $data['derniereAssignation']->chambre?->batiment?->name ?? 'Non défini' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-key">🏫 Résidence</span>
                        <span class="info-value">{{ $data['derniereAssignation']->chambre?->cite?->name ?? 'Non défini' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-key">📊 Loyer mensuel</span>
                        <span class="info-value">{{ formatSystemAmount($data['derniereAssignation']->chambre?->typeChambre?->montant ?? 0) }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-key">📅 Date d'entrée</span>
                        <span class="info-value">{{ ($data['derniereAssignation']  && $data['derniereAssignation']->created_at )? formatDateFr($data['derniereAssignation']->created_at->format('Y-m-d'), true) : 'Non définie' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-key">📌 Statut</span>
                        <span class="info-value">
                            <span class="badge success">✅ Occupée</span>
                        </span>
                    </div>
                    <div class="info-item full-width">
                        <span class="info-key">📝 Description</span>
                        <span class="info-value">{{ $data['derniereAssignation']->chambre?->description ?? 'Aucune description disponible pour cette chambre.' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- COLONNE DROITE : ÉQUIPEMENTS & ACTIONS --}}
        <div class="panel panel-equipments">
            <div class="panel-header">
                <h2><i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i> Équipements inclus</h2>
                <span class="panel-badge">{{ !empty($equipement) ? count($equipement) : 0 }} équipements</span>
            </div>
            <div class="panel-body">

                {{-- LISTE DES ÉQUIPEMENTS --}}
                <div class="equipments-grid">
                    @php
                        $equipementRaw = $data['derniereAssignation']->chambre?->equipements
                            ?? $data['derniereAssignation']->chambre?->typeChambre?->equipement
                            ?? null;
                        $equipement = $equipementRaw ? json_decode($equipementRaw, true) : null;
                    @endphp

                    @if(!empty($equipement) && is_array($equipement))
                        @foreach($equipement as $key => $item)
                            @php
                                $equipmentName = is_array($item) ? ($item['name'] ?? '') : (string) $item;
                                $equipmentKey = strtolower(trim($equipmentName));
                            @endphp
                            <div class="equipment-item">
                                <span class="equipment-icon">
                                    @switch($equipmentKey)
                                        @case('lit')
                                        @case('matelas')
                                            🛏️
                                            @break
                                        @case('armoire')
                                        @case('table')
                                        @case('chaise')
                                            🪑
                                            @break
                                        @case('ventilateur')
                                        @case('climatisation')
                                            ❄️
                                            @break
                                        @case('douche')
                                        @case('wc')
                                            🚿
                                            @break
                                        @case('detecteur_fumee')
                                        @case('extincteur')
                                            🔥
                                            @break
                                        @default
                                            📎
                                    @endswitch
                                </span>
                                <span class="equipment-name">{{ $equipmentName ?: 'Équipement' }}</span>
                                <span class="equipment-status good">● Bon état</span>
                            </div>
                        @endforeach
                    @else
                        <div class="no-equipment">
                            <span class="no-equipment-icon">🔧</span>
                            <p>Aucun équipement enregistré pour cette chambre</p>
                            <span class="no-equipment-hint">Contactez le responsable de la résidence pour plus d'informations</span>
                        </div>
                    @endif
                </div>

                {{-- ACTIONS RAPIDES --}}
                <div class="equipment-actions">
                    <button class="btn btn-primary" onclick="window.location.href='#'">
                        🔧 Signaler un équipement défectueux
                    </button>
                    <button class="btn btn-outline" onclick="showToast('📄 Téléchargement du plan en cours...')">
                        🗺️ Voir le plan de la chambre
                    </button>
                </div>

                {{-- NOTE --}}
                <div class="equipment-notice">
                    <span class="notice-icon">ℹ️</span>
                    <div class="notice-content">
                        <strong>Important :</strong>
                        <p>Les équipements endommagés doivent être signalés via une demande de maintenance. Un technicien interviendra dans les plus brefs délais.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================
        SECTION : GRILLE DE LA CHAMBRE (VISUALISATION)
    ============================================ --}}
    <div class="panel panel-layout">
        <div class="panel-header">
            <h2><i class="fa-solid fa-bed" aria-hidden="true"></i> Plan de la chambre</h2>
            <div class="header-actions">
                <button class="btn-icon-only" title="Voir en grand"><i class="fa-solid fa-magnifying-glass-plus" aria-hidden="true"></i></button>
                <button class="btn-icon-only" title="Télécharger"><i class="fa-solid fa-download" aria-hidden="true"></i></button>
            </div>
        </div>
        <div class="panel-body">
            <div class="room-layout">
                <div class="room-grid">
                    {{-- Porte --}}
                    <div class="room-element door">
                        <span>🚪</span>
                        <span class="element-label">Porte</span>
                    </div>

                    {{-- Fenêtre --}}
                    <div class="room-element window">
                        <span>🪟</span>
                        <span class="element-label">Fenêtre</span>
                    </div>

                    {{-- Lit --}}
                    <div class="room-element bed">
                        <span>🛏️</span>
                        <span class="element-label">Lit</span>
                    </div>

                    {{-- Bureau --}}
                    <div class="room-element desk">
                        <span>🪑</span>
                        <span class="element-label">Bureau</span>
                    </div>

                    {{-- Armoire --}}
                    <div class="room-element wardrobe">
                        <span>🗄️</span>
                        <span class="element-label">Armoire</span>
                    </div>

                    {{-- Salle de bain --}}
                    <div class="room-element bathroom">
                        <span>🚿</span>
                        <span class="element-label">Salle de bain</span>
                    </div>

                    {{-- Prises --}}
                    <div class="room-element sockets">
                        <span>🔌</span>
                        <span class="element-label">Prises</span>
                    </div>

                    {{-- Ventilateur --}}
                    <div class="room-element fan">
                        <span>🌀</span>
                        <span class="element-label">Ventilateur</span>
                    </div>
                </div>

                {{-- Légende --}}
                <div class="room-legend">
                    <span class="legend-item"><span class="legend-color door"></span> Porte</span>
                    <span class="legend-item"><span class="legend-color window"></span> Fenêtre</span>
                    <span class="legend-item"><span class="legend-color bed"></span> Lit</span>
                    <span class="legend-item"><span class="legend-color desk"></span> Bureau</span>
                    <span class="legend-item"><span class="legend-color wardrobe"></span> Armoire</span>
                    <span class="legend-item"><span class="legend-color bathroom"></span> Salle de bain</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================
        SECTION : VOISINS DE CHAMBRE
    ============================================ --}}
    @php
        $roommates = collect($result['roommates'] ?? []);
        $currentRoomNumber = $data['derniereAssignation']->chambre?->numero_chambre;
    @endphp
    <div class="panel panel-neighbors">
        <div class="panel-header">
            <h2>👥 Voisins de chambre</h2>
            <span class="panel-badge">{{ $roommates->count() }} voisin(s)</span>
        </div>
        <div class="panel-body">
            <div class="neighbors-grid">
                @forelse($roommates as $roommate)
                    <div class="neighbor-card">
                        <div class="neighbor-avatar">{{ getInitials($roommate->name ?? 'Étudiant') }}</div>
                        <div class="neighbor-info">
                            <span class="neighbor-name">{{ $roommate->name ?? 'Étudiant' }}</span>
                            <span class="neighbor-room">Chambre {{ $currentRoomNumber ?? 'Non renseignée' }}</span>
                            <span class="neighbor-status online">● Occupant actif</span>
                        </div>
                    </div>
                @empty
                    <div class="no-neighbor">
                        <div class="neighbor-avatar">👤</div>
                        <div class="neighbor-info">
                            <span class="neighbor-name">Aucun voisin de chambre</span>
                            <span class="neighbor-room">Aucune autre assignation active dans cette chambre.</span>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

</div>

{{-- ============================================
    STYLES
============================================ --}}
<style>
    /* ============================================
       VARIABLES
    ============================================ */
    :root {
        --primary: #1e7aa8;
        --primary-dark: #166a94;
        --success: #2a8d6c;
        --warning: #d68a3a;
        --danger: #b13e3e;
        --info: #5b7b99;
        --border: #dae2ec;
        --bg-light: #f4f7fc;
        --text: #0a2b44;
        --muted: #5b7b99;
        --radius: 16px;
        --shadow: 0 4px 16px rgba(0, 20, 40, 0.06);
        --shadow-hover: 0 8px 24px rgba(0, 20, 40, 0.10);
    }

    /* ============================================
       CHAMBRE HERO
    ============================================ */
    .chambre-hero {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        padding: 24px 28px;
        background: linear-gradient(135deg, #1e7aa8 0%, #2a8d6c 100%);
        border-radius: var(--radius);
        margin-bottom: 24px;
        color: #fff;
    }

    .chambre-hero-left {
        display: flex;
        align-items: center;
        gap: 18px;
    }

    .chambre-icon-big {
        width: 56px;
        height: 56px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .chambre-icon-big svg {
        width: 28px;
        height: 28px;
    }

    .chambre-hero-left h2 {
        margin: 0;
        font-size: 26px;
        font-weight: 700;
    }

    .chambre-hero-left p {
        margin: 2px 0 0;
        opacity: 0.85;
        font-size: var(--app-font-min, 15px);
    }

    .chambre-hero-right {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 16px;
        border-radius: 30px;
        font-size: var(--app-font-min, 15px);
        font-weight: 600;
        background: rgba(255, 255, 255, 0.2);
        color: #fff;
    }

    .status-badge.occupied {
        background: rgba(255, 255, 255, 0.2);
    }

    .btn-outline-light {
        background: transparent;
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.4);
        padding: 8px 16px;
        border-radius: 10px;
        font-size: var(--app-font-min, 15px);
        cursor: pointer;
        transition: 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-outline-light:hover {
        background: rgba(255, 255, 255, 0.15);
        border-color: rgba(255, 255, 255, 0.7);
    }

    /* ============================================
       CHAMBRE STATS
    ============================================ */
    .chambre-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .chambre-stat-card {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 16px 20px;
        background: #fff;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        transition: 0.2s;
    }

    .chambre-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
    }

    .chambre-stat-card .stat-icon {
        font-size: 28px;
        flex-shrink: 0;
    }

    .chambre-stat-card .stat-content {
        flex: 1;
        min-width: 0;
    }

    .chambre-stat-card .stat-label {
        display: block;
        font-size: var(--app-font-min, 15px);
        font-weight: 600;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .chambre-stat-card .stat-value {
        display: block;
        font-size: 16px;
        font-weight: 600;
        color: var(--text);
        margin-top: 1px;
    }

    /* ============================================
       GRILLE PRINCIPALE
    ============================================ */
    .chambre-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-bottom: 24px;
    }

    /* ============================================
       PANELS
    ============================================ */
    .panel {
        background: #fff;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        overflow: hidden;
    }

    .panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 20px;
        border-bottom: 1px solid var(--border);
        background: var(--bg-light);
    }

    .panel-header h2 {
        font-size: 16px;
        font-weight: 600;
        color: var(--text);
        margin: 0;
    }

    .panel-badge {
        font-size: var(--app-font-min, 15px);
        font-weight: 500;
        background: #e7edf5;
        padding: 2px 12px;
        border-radius: 30px;
        color: var(--info);
    }

    .panel-body {
        padding: 20px;
    }

    .header-actions {
        display: flex;
        gap: 4px;
    }

    .btn-icon-only {
        background: none;
        border: none;
        font-size: 18px;
        cursor: pointer;
        color: var(--muted);
        transition: 0.2s;
        padding: 4px 8px;
        border-radius: 6px;
    }

    .btn-icon-only:hover {
        background: var(--bg-light);
        color: var(--text);
    }

    /* ============================================
       INFO GRID
    ============================================ */
    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    .info-item {
        display: flex;
        flex-direction: column;
        padding: 10px 14px;
        background: var(--bg-light);
        border-radius: 10px;
    }

    .info-item.full-width {
        grid-column: 1 / -1;
    }

    .info-key {
        font-size: var(--app-font-min, 15px);
        font-weight: 600;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .info-value {
        font-size: var(--app-font-min, 15px);
        font-weight: 500;
        color: var(--text);
        margin-top: 2px;
    }

    /* ============================================
       BADGE
    ============================================ */
    .badge {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 30px;
        font-size: var(--app-font-min, 15px);
        font-weight: 600;
    }

    .badge.success {
        background: #e8f5e9;
        color: var(--success);
    }

    .badge.pending {
        background: #fff3e0;
        color: var(--warning);
    }

    .badge.danger {
        background: #ffebee;
        color: var(--danger);
    }

    /* ============================================
       EQUIPMENTS
    ============================================ */
    .equipments-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 16px;
    }

    .equipment-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        border: 1px solid var(--border);
        border-radius: 10px;
        transition: 0.2s;
    }

    .equipment-item:hover {
        border-color: var(--primary);
        background: var(--bg-light);
    }

    .equipment-icon {
        font-size: 20px;
        flex-shrink: 0;
    }

    .equipment-name {
        flex: 1;
        font-size: var(--app-font-min, 15px);
        font-weight: 500;
        color: var(--text);
    }

    .equipment-status {
        font-size: var(--app-font-min, 15px);
        font-weight: 500;
    }

    .equipment-status.good {
        color: var(--success);
    }

    .equipment-status.warning {
        color: var(--warning);
    }

    .equipment-status.danger {
        color: var(--danger);
    }

    .no-equipment {
        grid-column: 1 / -1;
        text-align: center;
        padding: 30px 20px;
        color: var(--muted);
    }

    .no-equipment-icon {
        font-size: 40px;
        display: block;
        margin-bottom: 8px;
    }

    .no-equipment p {
        margin: 0;
        font-size: var(--app-font-min, 15px);
        font-weight: 500;
    }

    .no-equipment-hint {
        font-size: var(--app-font-min, 15px);
        opacity: 0.7;
    }

    /* ============================================
       EQUIPMENT ACTIONS
    ============================================ */
    .equipment-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }

    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 10px;
        font-size: var(--app-font-min, 15px);
        font-weight: 500;
        cursor: pointer;
        transition: 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }

    .btn-primary {
        background: var(--primary);
        color: #fff;
    }

    .btn-primary:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(30, 122, 168, 0.3);
    }

    .btn-outline {
        background: transparent;
        color: var(--primary);
        border: 1px solid var(--primary);
    }

    .btn-outline:hover {
        background: var(--bg-light);
    }

    /* ============================================
       EQUIPMENT NOTICE
    ============================================ */
    .equipment-notice {
        display: flex;
        gap: 12px;
        padding: 14px 16px;
        background: #e3f2fd;
        border-radius: 10px;
        border-left: 4px solid #1565c0;
    }

    .notice-icon {
        font-size: 20px;
        flex-shrink: 0;
    }

    .notice-content {
        flex: 1;
        font-size: var(--app-font-min, 15px);
        color: #1565c0;
    }

    .notice-content p {
        margin: 4px 0 0;
    }

    /* ============================================
       ROOM LAYOUT
    ============================================ */
    .panel-layout .panel-body {
        padding: 20px;
    }

    .room-layout {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .room-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        padding: 20px;
        background: var(--bg-light);
        border-radius: 12px;
        min-height: 200px;
        position: relative;
    }

    .room-element {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 12px 8px;
        background: #fff;
        border-radius: 10px;
        border: 2px solid var(--border);
        transition: 0.2s;
        cursor: default;
        min-height: 80px;
    }

    .room-element:hover {
        border-color: var(--primary);
        transform: scale(1.03);
    }

    .room-element span:first-child {
        font-size: 24px;
    }

    .room-element .element-label {
        font-size: var(--app-font-min, 15px);
        color: var(--muted);
        margin-top: 4px;
        font-weight: 500;
    }

    .room-element.door { border-color: #1565c0; }
    .room-element.window { border-color: #42a5f5; }
    .room-element.bed { border-color: var(--success); }
    .room-element.desk { border-color: #7a5d9e; }
    .room-element.wardrobe { border-color: #d68a3a; }
    .room-element.bathroom { border-color: #26a69a; }
    .room-element.sockets { border-color: #5b7b99; }
    .room-element.fan { border-color: #ef5350; }

    /* ============================================
       ROOM LEGEND
    ============================================ */
    .room-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 12px 20px;
        padding: 12px 16px;
        background: var(--bg-light);
        border-radius: 10px;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: var(--app-font-min, 15px);
        color: var(--muted);
    }

    .legend-color {
        width: 14px;
        height: 14px;
        border-radius: 4px;
        border: 1px solid var(--border);
    }

    .legend-color.door { background: #1565c0; }
    .legend-color.window { background: #42a5f5; }
    .legend-color.bed { background: var(--success); }
    .legend-color.desk { background: #7a5d9e; }
    .legend-color.wardrobe { background: #d68a3a; }
    .legend-color.bathroom { background: #26a69a; }

    /* ============================================
       NEIGHBORS
    ============================================ */
    .panel-neighbors .panel-body {
        padding: 20px;
    }

    .neighbors-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
    }

    .neighbor-card {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 16px;
        border: 1px solid var(--border);
        border-radius: 12px;
        transition: 0.2s;
    }

    .neighbor-card:hover {
        border-color: var(--primary);
        box-shadow: var(--shadow);
    }

    .neighbor-avatar {
        font-size: 32px;
        flex-shrink: 0;
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--bg-light);
        border-radius: 50%;
    }

    .neighbor-info {
        flex: 1;
        min-width: 0;
    }

    .neighbor-name {
        display: block;
        font-size: var(--app-font-min, 15px);
        font-weight: 600;
        color: var(--text);
    }

    .neighbor-room {
        display: block;
        font-size: var(--app-font-min, 15px);
        color: var(--muted);
    }

    .neighbor-status {
        display: inline-block;
        font-size: var(--app-font-min, 15px);
        font-weight: 500;
        margin-top: 2px;
    }

    .neighbor-status.online {
        color: var(--success);
    }

    .neighbor-status.offline {
        color: var(--muted);
    }

    /* ============================================
       TOAST NOTIFICATION
    ============================================ */
    .toast-notification {
        position: fixed;
        bottom: 30px;
        right: 30px;
        background: #0a2b44;
        color: #fff;
        padding: 14px 24px;
        border-radius: 12px;
        font-size: var(--app-font-min, 15px);
        font-weight: 500;
        box-shadow: 0 8px 24px rgba(0, 20, 40, 0.2);
        transform: translateY(100px);
        opacity: 0;
        transition: all 0.4s ease;
        z-index: 1000;
        max-width: 400px;
    }

    .toast-notification.show {
        transform: translateY(0);
        opacity: 1;
    }

    /* ============================================
       RESPONSIVE
    ============================================ */
    @media (max-width: 1024px) {
        .chambre-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .chambre-hero {
            flex-direction: column;
            align-items: flex-start;
        }

        .chambre-hero-right {
            width: 100%;
            justify-content: flex-start;
        }

        .chambre-stats-grid {
            grid-template-columns: 1fr 1fr;
        }

        .info-grid {
            grid-template-columns: 1fr;
        }

        .equipments-grid {
            grid-template-columns: 1fr;
        }

        .room-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .neighbors-grid {
            grid-template-columns: 1fr;
        }

        .equipment-actions {
            flex-direction: column;
        }

        .equipment-actions .btn {
            width: 100%;
            justify-content: center;
        }
    }

    @media (max-width: 480px) {
        .chambre-stats-grid {
            grid-template-columns: 1fr;
        }

        .chambre-hero-left {
            flex-direction: column;
            text-align: center;
            width: 100%;
        }

        .chambre-hero-left h2 {
            font-size: 22px;
        }

        .room-grid {
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            padding: 12px;
        }

        .room-element {
            min-height: 60px;
            padding: 8px 4px;
        }

        .room-element span:first-child {
            font-size: 18px;
        }

        .room-legend {
            flex-wrap: wrap;
        }
    }
</style>

{{-- ============================================
    SCRIPTS
============================================ --}}
<script>
    // ============================================
    // TOAST NOTIFICATION
    // ============================================
    function showToast(message) {
        // Vérifier si un toast existe déjà
        let toast = document.querySelector('.toast-notification');

        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'toast-notification';
            document.body.appendChild(toast);
        }

        toast.textContent = message;
        toast.classList.add('show');

        clearTimeout(toast._timeout);
        toast._timeout = setTimeout(() => {
            toast.classList.remove('show');
        }, 4000);
    }

    // ============================================
    // INITIALISATION
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        console.log('✅ Page chambre initialisée avec succès !');
    });
</script>
