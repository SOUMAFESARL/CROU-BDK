{{--
    Fenêtres « Accepter / Refuser la proposition », styles et scripts communs aux sections Blade
    de etudiant/index (utilisés par la section Demandes). Le contenu du tableau de bord est passé
    en React (resources/js/pages/Etudiant/TableauDeBord.tsx) : ce partial disparaîtra avec la
    migration de la section Demandes.
--}}

@php
$campagne = $data['campagne'] ?? null;
$dateEcheanceDemande = optional($campagne)->date_echeance_demande;
$dateEcheanceDemandeIso = $dateEcheanceDemande
? \Illuminate\Support\Carbon::parse($dateEcheanceDemande)->format('Y-m-d')
: '';
@endphp

{{-- Titre, fil d'Ariane et avatar : barre du haut de <x-etudiant-shell>. --}}

{{-- ============================================
    MODAL: Confirmation d'acceptation
    ============================================ --}}
<div id="confirmAcceptModal" class="modal" style="display: none;">
    <div class="modal-overlay" onclick="closeConfirmModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3>✅ Confirmer l'acceptation</h3>
            <button class="modal-close" onclick="closeConfirmModal()">×</button>
        </div>
        <form action="{{ route('etudiants.validation-demande', ['idDemande' => $data['derniere_demande']->id_demande ?? 0]) }}" method="post">
            @csrf
            <div class="modal-body">
                <div class="alert alert-info">
                    <div class="alert-icon">ℹ️</div>
                    <div class="alert-content">
                        <strong>Êtes-vous sûr ?</strong>
                        <p>En acceptant cette offre d'hébergement, vous confirmez votre résidence pour l'année académique <strong>{{ $data['campagne']->annee_campagne ?? 'N/A' }}</strong>.</p>
                    </div>
                </div>
                <input type="hidden" name="id_etudiant" value="{{ $data['etudiant']->id_etudiant ?? '' }}">
                {{-- Pré-attribution exigée par DossierWorkflow pour répondre à la proposition. --}}
                <input type="hidden" name="assignation_id" value="{{ $data['derniere_demande']->assignation_id ?? '' }}">
                <input type="hidden" name="action" value="accepter">
                <div class="details-box">
                    <div class="detail-row">
                        <span class="detail-label">📅 Année académique</span>
                        <span class="detail-value">{{ $data['campagne']->annee_campagne ?? 'N/A' }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">⏰ Délai de validation</span>
                        <span class="detail-value">{{ $dateEcheanceDemandeIso ? formatDateFr($dateEcheanceDemandeIso, true) : 'N/A' }}</span>
                    </div>
                    @if(isset($data['derniere_demande']->chambre_proposee))
                    <div class="detail-row">
                        <span class="detail-label">🏠 Chambre proposée</span>
                        <span class="detail-value">{{ $data['derniere_demande']->chambre_proposee }}</span>
                    </div>
                    @endif
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeConfirmModal()">Annuler</button>
                <button type="submit" class="btn btn-accept" id="btnConfirmAccept">
                    <span class="btn-text">✅ Confirmer l'acceptation</span>
                    <span class="btn-loader">
                        <svg class="spinner" width="16" height="16" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" />
                            <path d="M22 12a10 10 0 01-5 8.65" fill="none" stroke="currentColor" stroke-width="2" />
                        </svg>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ============================================
    MODAL: Refus de la demande
    ============================================ --}}
<div id="rejectModal" class="modal" style="display: none;">
    <div class="modal-overlay" onclick="closeRejectModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3>❌ Refuser l'offre d'hébergement</h3>
            <button class="modal-close" onclick="closeRejectModal()">×</button>
        </div>
        <form action="{{ route('etudiants.validation-demande', ['idDemande' => $data['derniere_demande']->id_demande ?? 0]) }}" method="post">
            @csrf
            <div class="modal-body">
                <div class="alert alert-warning">
                    <div class="alert-icon">⚠️</div>
                    <div class="alert-content">
                        <strong>Attention !</strong>
                        <p>En refusant cette offre, vous ne recevrez pas d'hébergement pour cette année académique.</p>
                    </div>
                </div>
                <input type="hidden" name="id_etudiant" value="{{ $data['etudiant']->id_etudiant ?? '' }}">
                {{-- Pré-attribution exigée par DossierWorkflow pour répondre à la proposition. --}}
                <input type="hidden" name="assignation_id" value="{{ $data['derniere_demande']->assignation_id ?? '' }}">
                <input type="hidden" name="action" value="refuser">
                <div class="form-group">
                    <label for="rejectReason">Motif du refus <span class="text-danger">*</span></label>
                    <textarea id="rejectReason" name="motif_refus" class="form-control" rows="4" placeholder="Veuillez expliquer pourquoi vous refusez cette offre..." required maxlength="500"></textarea>
                    <div class="char-counter">
                        <span id="charCount">0</span> / 500 caractères
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">Annuler</button>
                <button type="submit" class="btn btn-reject" id="btnConfirmReject">
                    <span class="btn-text">❌ Confirmer le refus</span>
                    <span class="btn-loader">
                        <svg class="spinner" width="16" height="16" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" />
                            <path d="M22 12a10 10 0 01-5 8.65" fill="none" stroke="currentColor" stroke-width="2" />
                        </svg>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ============================================
    ALERTES
    ============================================ --}}
<div id="alertContainer"></div>

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
       MODAL
    ============================================ */
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        animation: fadeIn 0.3s ease;
    }

    .modal-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        cursor: pointer;
        backdrop-filter: blur(4px);
    }

    .modal-content {
        position: relative;
        background: white;
        border-radius: var(--radius);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        max-width: 500px;
        width: 90%;
        z-index: 1001;
        animation: slideUp 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes slideUp {
        from {
            transform: translateY(30px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .modal-header {
        padding: 20px 24px;
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        color: var(--text);
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 28px;
        cursor: pointer;
        color: var(--muted);
        transition: 0.2s;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-close:hover {
        background: var(--bg-light);
        color: var(--text);
    }

    .modal-body {
        padding: 24px;
    }

    .modal-footer {
        padding: 16px 24px;
        border-top: 1px solid var(--border);
        display: flex;
        gap: 12px;
        justify-content: flex-end;
    }

    /* ============================================
       ALERTS
    ============================================ */
    .alert {
        display: flex;
        gap: 12px;
        padding: 14px 16px;
        border-radius: 10px;
        margin-bottom: 16px;
        font-size: var(--app-font-min, 15px);
        align-items: flex-start;
    }

    .alert-icon {
        font-size: 20px;
        flex-shrink: 0;
    }

    .alert-content {
        flex: 1;
    }

    .alert-content p {
        margin: 4px 0 0;
    }

    .alert-info {
        background: #e3f2fd;
        color: #1565c0;
        border-left: 4px solid #1565c0;
    }

    .alert-warning {
        background: #fff3e0;
        color: #e65100;
        border-left: 4px solid #e65100;
    }

    .alert-success {
        background: #e8f5e9;
        color: #2e7d32;
        border-left: 4px solid #2e7d32;
    }

    .alert-danger {
        background: #ffebee;
        color: #c62828;
        border-left: 4px solid #c62828;
    }

    /* ============================================
       DETAILS BOX
    ============================================ */
    .details-box {
        background: var(--bg-light);
        padding: 14px 16px;
        border-radius: 10px;
        margin: 12px 0;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 6px 0;
        font-size: var(--app-font-min, 15px);
        border-bottom: 1px solid var(--border);
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-label {
        color: var(--muted);
    }

    .detail-value {
        font-weight: 600;
        color: var(--text);
    }

    /* ============================================
       FORMULAIRES
    ============================================ */
    .form-group {
        margin-bottom: 16px;
    }

    .form-group label {
        display: block;
        margin-bottom: 6px;
        font-size: var(--app-font-min, 15px);
        font-weight: 600;
        color: var(--text);
    }

    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid var(--border);
        border-radius: 10px;
        font-family: inherit;
        font-size: var(--app-font-min, 15px);
        resize: vertical;
        transition: 0.2s;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(30, 122, 168, 0.1);
    }

    .text-danger {
        color: var(--danger);
    }

    .char-counter {
        font-size: var(--app-font-min, 15px);
        color: var(--muted);
        margin-top: 4px;
        text-align: right;
    }

    /* ============================================
       BOUTONS
    ============================================ */
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 10px;
        font-size: var(--app-font-min, 15px);
        cursor: pointer;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .btn-lg {
        padding: 12px 28px;
        font-size: 16px;
    }

    .btn-text {
        display: inline;
    }

    .btn-loader {
        display: none;
    }

    .btn.loading .btn-text {
        display: none;
    }

    .btn.loading .btn-loader {
        display: inline-flex;
    }

    .spinner {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    .btn-accept {
        background: var(--success);
        color: white;
    }

    .btn-accept:hover:not(:disabled) {
        background: #1f7a5c;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(42, 141, 108, 0.3);
    }

    .btn-reject {
        background: var(--danger);
        color: white;
    }

    .btn-reject:hover:not(:disabled) {
        background: #9a3333;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(177, 62, 62, 0.3);
    }

    .btn-secondary {
        background: var(--info);
        color: white;
    }

    .btn-secondary:hover {
        background: #4a6a8a;
    }

    /* ============================================
       TOPBAR
    ============================================ */
    .topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        padding: 12px 20px;
        background: #fff;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
    }

    .topbar-right {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .date-badge {
        font-size: var(--app-font-min, 15px);
        color: var(--muted);
        background: var(--bg-light);
        padding: 6px 14px;
        border-radius: 30px;
    }

    .notif-btn {
        position: relative;
        background: none;
        border: 1px solid var(--border);
        border-radius: 50%;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: 0.2s;
        color: var(--muted);
    }

    .notif-btn:hover {
        background: var(--bg-light);
        border-color: var(--primary);
    }

    .notif-dot {
        position: absolute;
        top: 6px;
        right: 6px;
        width: 8px;
        height: 8px;
        background: var(--danger);
        border-radius: 50%;
        border: 2px solid #fff;
    }

    /* ============================================
       WELCOME BANNER
    ============================================ */
    .welcome-banner {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        padding: 20px 24px;
        background: linear-gradient(135deg, #1e7aa8 0%, #2a8d6c 100%);
        border-radius: var(--radius);
        margin-bottom: 24px;
        color: #fff;
    }

    .welcome-content h2 {
        margin: 0;
        font-size: 22px;
        font-weight: 700;
    }

    .welcome-content p {
        margin: 4px 0 0;
        opacity: 0.9;
    }

    .welcome-content strong {
        color: #fff;
    }

    .status-badge {
        display: inline-block;
        padding: 6px 16px;
        border-radius: 30px;
        font-size: var(--app-font-min, 15px);
        font-weight: 600;
    }

    .status-badge.success {
        background: rgba(255, 255, 255, 0.2);
        color: #fff;
    }

    .status-badge.pending {
        background: #fff3e0;
        color: #e65100;
    }

    /* ============================================
       STATS
    ============================================ */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: #fff;
        border-radius: var(--radius);
        padding: 18px 20px;
        box-shadow: var(--shadow);
        display: flex;
        align-items: flex-start;
        gap: 14px;
        transition: 0.2s;
        border-left: 4px solid var(--info);
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
    }

    .stat-card .stat-icon {
        font-size: 28px;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .stat-card .stat-content {
        flex: 1;
        min-width: 0;
    }

    .stat-card .label {
        font-size: var(--app-font-min, 15px);
        font-weight: 600;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-card .value {
        font-size: 26px;
        font-weight: 700;
        color: var(--text);
        margin: 2px 0;
    }

    .stat-card .sub {
        font-size: var(--app-font-min, 15px);
        color: var(--muted);
    }

    .stat-card.teal {
        border-left-color: #1e7aa8;
    }

    .stat-card.amber {
        border-left-color: var(--warning);
    }

    .stat-card.green {
        border-left-color: var(--success);
    }

    .stat-card.red {
        border-left-color: var(--danger);
    }

    .stat-card.purple {
        border-left-color: #7a5d9e;
    }

    .stat-card.blue {
        border-left-color: #1565c0;
    }

    .stat-trend {
        font-size: var(--app-font-min, 15px);
        margin-top: 4px;
    }

    .stat-trend.up {
        color: var(--success);
    }

    .stat-trend.down {
        color: var(--danger);
    }

    .stat-progress {
        margin-top: 6px;
    }

    .stat-progress .progress-bar {
        height: 4px;
        background: var(--border);
        border-radius: 8px;
        overflow: hidden;
    }

    .stat-progress .progress-fill {
        height: 100%;
        border-radius: 8px;
        background: var(--primary);
        transition: width 0.6s ease;
    }

    /* ============================================
       DASHBOARD GRID
    ============================================ */
    .dashboard-grid {
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
       TIMELINE
    ============================================ */
    .timeline {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .tl-item {
        display: flex;
        gap: 14px;
        align-items: flex-start;
    }

    .tl-icon {
        font-size: 18px;
        flex-shrink: 0;
        width: 32px;
        text-align: center;
    }

    .tl-content {
        flex: 1;
    }

    .tl-date {
        font-size: var(--app-font-min, 15px);
        color: var(--muted);
        font-weight: 500;
    }

    .tl-text {
        font-size: var(--app-font-min, 15px);
        color: var(--text);
    }

    /* ============================================
       INFO LIST
    ============================================ */
    .info-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--border);
    }

    .info-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .info-label {
        font-size: var(--app-font-min, 15px);
        color: var(--muted);
    }

    .info-value {
        font-size: var(--app-font-min, 15px);
        font-weight: 600;
        color: var(--text);
    }

    /* ============================================
       QUICK ACTIONS
    ============================================ */
    .quick-actions {
        background: #fff;
        border-radius: var(--radius);
        padding: 20px 24px;
        box-shadow: var(--shadow);
    }

    .quick-actions h3 {
        font-size: 16px;
        font-weight: 600;
        color: var(--text);
        margin: 0 0 16px;
    }

    .quick-actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
    }

    .quick-action-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding: 16px;
        border: 1px solid var(--border);
        border-radius: 12px;
        text-decoration: none;
        transition: 0.2s;
        background: #fff;
    }

    .quick-action-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-hover);
        border-color: var(--primary);
    }

    .quick-action-card .quick-icon {
        font-size: 32px;
        margin-bottom: 8px;
    }

    .quick-action-card .quick-title {
        font-size: var(--app-font-min, 15px);
        font-weight: 600;
        color: var(--text);
    }

    .quick-action-card .quick-desc {
        font-size: var(--app-font-min, 15px);
        color: var(--muted);
        margin-top: 2px;
    }

    .quick-action-card.payment:hover {
        border-color: var(--success);
    }

    .quick-action-card.maintenance:hover {
        border-color: var(--warning);
    }

    .quick-action-card.contract:hover {
        border-color: var(--primary);
    }

    .quick-action-card.profile:hover {
        border-color: #7a5d9e;
    }

    button.quick-action-card {
        width: 100%;
        text-align: left;
        font-family: inherit;
        cursor: pointer;
    }

    /* ============================================
       DEMANDE BANNER
    ============================================ */
    .demande-banner {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        padding: 20px 24px;
        background: linear-gradient(135deg, #1565c0 0%, #1e7aa8 100%);
        border-radius: var(--radius);
        margin-bottom: 24px;
        color: #fff;
    }

    .banner-content h2 {
        margin: 0;
        font-size: 22px;
        font-weight: 700;
    }

    .banner-content p {
        margin: 4px 0 0;
        opacity: 0.9;
    }

    /* ============================================
       DEMANDE PANEL
    ============================================ */
    .demande-panel .panel-body {
        padding: 24px;
    }

    .demande-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }

    .demande-info-item {
        padding: 12px 16px;
        background: var(--bg-light);
        border-radius: 10px;
    }

    .demande-info-item .info-label {
        display: block;
        font-size: var(--app-font-min, 15px);
        font-weight: 600;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .demande-info-item .info-value {
        display: block;
        font-size: 15px;
        font-weight: 600;
        color: var(--text);
        margin-top: 2px;
    }

    /* ============================================
       DOCUMENTS
    ============================================ */
    .documents-section {
        margin: 20px 0;
    }

    .documents-section h4 {
        font-size: var(--app-font-min, 15px);
        font-weight: 600;
        color: var(--text);
        margin: 0 0 12px;
    }

    .documents-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 10px;
    }

    .document-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        border: 1px solid var(--border);
        border-radius: 10px;
        transition: 0.2s;
    }

    .document-item:hover {
        border-color: var(--primary);
    }

    .document-item .doc-icon {
        font-size: 24px;
    }

    .document-item .doc-info {
        flex: 1;
        min-width: 0;
    }

    .document-item .doc-name {
        display: block;
        font-size: var(--app-font-min, 15px);
        font-weight: 500;
        color: var(--text);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .document-item .doc-status {
        font-size: var(--app-font-min, 15px);
    }

    .document-item.valide {
        border-left: 3px solid var(--success);
    }

    .document-item.rejete {
        border-left: 3px solid var(--danger);
    }

    .document-item.pending {
        border-left: 3px solid var(--warning);
    }

    /* ============================================
       DEMANDE PROGRESS
    ============================================ */
    .demande-progress {
        margin: 20px 0;
    }

    .progress-header {
        display: flex;
        justify-content: space-between;
        font-size: var(--app-font-min, 15px);
        font-weight: 500;
        color: var(--text);
        margin-bottom: 6px;
    }

    .demande-progress .progress-bar {
        height: 6px;
        background: var(--border);
        border-radius: 8px;
        overflow: hidden;
    }

    .demande-progress .progress-fill {
        height: 100%;
        border-radius: 8px;
        background: var(--primary);
        transition: width 0.6s ease;
    }

    .progress-steps {
        display: flex;
        justify-content: space-between;
        margin-top: 12px;
        position: relative;
    }

    .progress-steps::before {
        content: '';
        position: absolute;
        top: 14px;
        left: 10%;
        right: 10%;
        height: 2px;
        background: var(--border);
    }

    .step {
        display: flex;
        flex-direction: column;
        align-items: center;
        z-index: 1;
        flex: 1;
    }

    .step .step-number {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: var(--border);
        color: var(--muted);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: var(--app-font-min, 15px);
        font-weight: 700;
        transition: 0.3s;
        position: relative;
    }

    .step.active .step-number {
        background: var(--primary);
        color: #fff;
    }

    .step .step-label {
        font-size: var(--app-font-min, 15px);
        color: var(--muted);
        margin-top: 4px;
        text-align: center;
    }

    .step.active .step-label {
        color: var(--text);
        font-weight: 600;
    }

    /* ============================================
       DEMANDE ACTIONS
    ============================================ */
    .demande-actions {
        display: flex;
        gap: 16px;
        margin: 20px 0;
        flex-wrap: wrap;
    }

    /* ============================================
       DEMANDE NOTICE
    ============================================ */
    .demande-notice {
        display: flex;
        gap: 12px;
        padding: 14px 16px;
        background: #e3f2fd;
        border-radius: 10px;
        margin-top: 16px;
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
       ALERT CONTAINER
    ============================================ */
    #alertContainer {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        max-width: 400px;
        width: 100%;
    }

    #alertContainer .alert {
        position: relative;
        animation: slideInRight 0.3s ease;
        margin-bottom: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    #alertContainer .alert .alert-close {
        position: absolute;
        top: 8px;
        right: 12px;
        background: none;
        border: none;
        font-size: 20px;
        cursor: pointer;
        opacity: 0.6;
        transition: 0.2s;
    }

    #alertContainer .alert .alert-close:hover {
        opacity: 1;
    }

    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    /* ============================================
       RESPONSIVE
    ============================================ */
    @media (max-width: 1024px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .topbar {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }

        .welcome-banner {
            flex-direction: column;
            align-items: flex-start;
        }

        .demande-banner {
            flex-direction: column;
            align-items: flex-start;
        }

        .stats-grid {
            grid-template-columns: 1fr 1fr;
        }

        .quick-actions-grid {
            grid-template-columns: 1fr 1fr;
        }

        .demande-info-grid {
            grid-template-columns: 1fr 1fr;
        }

        .documents-grid {
            grid-template-columns: 1fr;
        }

        .progress-steps {
            flex-wrap: wrap;
            gap: 8px;
        }

        .progress-steps::before {
            display: none;
        }

        .step {
            flex: 0 0 auto;
        }

        .modal-content {
            width: 95%;
            margin: 20px;
        }
    }

    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .quick-actions-grid {
            grid-template-columns: 1fr;
        }

        .demande-info-grid {
            grid-template-columns: 1fr;
        }

        .demande-actions {
            flex-direction: column;
        }

        .demande-actions .btn {
            width: 100%;
            justify-content: center;
        }

        .modal-footer {
            flex-direction: column-reverse;
        }

        .modal-footer .btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

{{-- ============================================
    SCRIPTS
    ============================================ --}}
<script>
    // ============================================
    // MODAL: ACCEPTATION
    // ============================================
    function showAcceptModal(demandId) {
        document.getElementById('confirmAcceptModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeConfirmModal() {
        document.getElementById('confirmAcceptModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // ============================================
    // MODAL: REFUS
    // ============================================
    function showRejectModal(demandId) {
        document.getElementById('rejectModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
        document.getElementById('rejectReason').value = '';
        document.getElementById('charCount').textContent = '0';
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // ============================================
    // COMPTEUR DE CARACTÈRES
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        const textarea = document.getElementById('rejectReason');
        const charCount = document.getElementById('charCount');

        if (textarea && charCount) {
            textarea.addEventListener('input', function() {
                const count = this.value.length;
                charCount.textContent = count;

                if (count > 450) {
                    charCount.style.color = '#b13e3e';
                } else {
                    charCount.style.color = '';
                }
            });
        }
    });

    // ============================================
    // ALERTS
    // ============================================
    function showAlert(type, message) {
        const container = document.getElementById('alertContainer');
        const alertId = 'alert-' + Date.now();

        const icons = {
            success: '✅',
            danger: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };

        const alertHTML = `
            <div id="${alertId}" class="alert alert-${type}">
                <span class="alert-icon">${icons[type] || 'ℹ️'}</span>
                <div class="alert-content">${message}</div>
                <button class="alert-close" onclick="this.parentElement.remove()">×</button>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', alertHTML);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            const alert = document.getElementById(alertId);
            if (alert) {
                alert.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => alert.remove(), 300);
            }
        }, 5000);
    }

    // ============================================
    // FERMER MODALES AVEC ÉCHAP
    // ============================================
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeConfirmModal();
            closeRejectModal();
        }
    });

    // ============================================
    // FERMER MODALES EN CLIQUANT SUR L'OVERLAY
    // ============================================
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function() {
            closeConfirmModal();
            closeRejectModal();
        });
    });

</script>