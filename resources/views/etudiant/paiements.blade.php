{{-- resources/views/etudiant/paiements.blade.php --}}
<div id="page-paiements" class="content ">
    <!-- EN-TÊTE DE LA PAGE -->
    <div class="payment-header">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i> Paiements des loyers</h1>
            <p class="page-subtitle">Gestion des encaissements et suivi des impayés</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-outline" onclick="showToast('Téléchargement du relevé en cours...')">
                <i class="fa-solid fa-download" aria-hidden="true"></i> Télécharger relevé
            </button>
            <button class="btn btn-primary" onclick="showToast('Vous serez notifié des prochaines échéances')">
                <i class="fa-solid fa-bell" aria-hidden="true"></i> Paramétrer rappels
            </button>
        </div>
    </div>

    <!-- STATISTIQUES -->
    <div class="stats-grid">
        <div class="stat-card green">
                    <div class="stat-icon"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></div>
            <div class="stat-content">
                <div class="label">Total payé</div>
                <div class="value">{{ number_format($stats['total_paye'] ?? 0, 0, ',', ' ') }}</div>
                <div class="sub">{{ systemCurrencySymbol() }} · <span id="nbPaiements">{{ $stats['nb_paiements'] ?? 0 }}</span> paiements</div>
                <div class="stat-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width:{{ min($stats['taux_recouvrement'] ?? 0, 100) }}%;"></div>
                    </div>
                    <span class="progress-label">{{ $stats['taux_recouvrement'] ?? 0 }}% de recouvrement</span>
                </div>
            </div>
        </div>
        <div class="stat-card amber">
            <div class="stat-icon">📅</div>
            <div class="stat-content">
                <div class="label">Prochain paiement</div>
                <div class="value">{{ number_format($loyers->first()->montant_total ?? 0, 0, ',', ' ') }}</div>
                <div class="sub">{{ systemCurrencySymbol() }} · {{ $prochainPaiement['libelle'] ?? 'N/A' }}</div>
                <div class="countdown" id="countdown">
                    ⏱️ {{ $prochainPaiement['libelle'] ?? 'À venir' }}
                </div>
            </div>
        </div>
        <div class="stat-card teal">
            <div class="stat-icon">✔️</div>
            <div class="stat-content">
                <div class="label">Statut compte</div>
                <div class="value">{{ $stats['statut_global'] ?? 'À jour' }}</div>
                <div class="sub">{{ $stats['nb_impayes'] ?? 0 }} impayé(s)</div>
                <div class="status-badge {{ ($stats['statut_global'] ?? 'À jour') === 'À jour' ? 'success' : 'danger' }}">
                    {{ ($stats['statut_global'] ?? 'À jour') === 'À jour' ? '✓ Bonne situation' : '⚠️ Attention' }}
                </div>
            </div>
        </div>
        <div class="stat-card purple">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
                <div class="label">Moyenne mensuelle</div>
                <div class="value">{{ number_format($stats['total_attendu'] > 0 ? $stats['total_attendu'] / max($stats['nb_loyers'], 1) : 0, 0, ',', ' ') }}</div>
                <div class="sub">{{ systemCurrencySymbol() }} · Loyer standard</div>
                <div class="stat-trend {{ ($stats['taux_recouvrement'] ?? 0) >= 75 ? 'up' : 'down' }}">
                    {{ ($stats['taux_recouvrement'] ?? 0) >= 75 ? '▲' : '▼' }}
                    {{ ($stats['taux_recouvrement'] ?? 0) >= 75 ? 'Bon' : 'À améliorer' }}
                </div>
            </div>
        </div>
    </div>

    <!-- ALERTE IMPAYÉS -->
    @if($impayes->isNotEmpty())
    <div class="alert alert-warning" style="background:#fcf7f2; border-left:4px solid #b13e3e; padding:16px; border-radius:8px; margin-bottom:20px;">
        <strong>⚠️ Attention :</strong> Vous avez <strong>{{ $impayes->count() }}</strong> impayé(s) pour un total de
        <strong>{{ formatSystemAmount($impayes->sum('solde_restant')) }}</strong>
    </div>
    @endif

    <!-- GRILLE PRINCIPALE -->
    @include('etudiant.partials.payment_providers', ['paymentProviders' => $paymentProviders ?? collect()])

    <div class="payment-grid">

        <!-- COLONNE GAUCHE : HISTORIQUE -->
        <div class="panel panel-history">
            <div class="panel-header">
                <div class="header-left">
                    <h2><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i> Historique des paiements</h2>
                    <span class="panel-badge" id="totalTransactions">{{ $paiements->count() }} transactions</span>
                </div>
                <div class="header-actions">
                    <button class="btn-icon-only" title="Filtrer" onclick="toggleFilter()"><i class="fa-solid fa-filter" aria-hidden="true"></i></button>
                    <button class="btn-icon-only" title="Exporter"><i class="fa-solid fa-upload" aria-hidden="true"></i></button>
                </div>
            </div>
            <div class="panel-body">

                <!-- Filtres -->
                <div class="table-filters" id="tableFilters">
                    <div class="filter-group">
                        <select class="filter-select" id="filterPerPage" onchange="loadPaiements(1)">
                            <option value="5">5 par page</option>
                            <option value="10" selected>10 par page</option>
                            <option value="25">25 par page</option>
                            <option value="50">50 par page</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <select class="filter-select" id="filterStatut" onchange="loadPaiements(1)">
                            <option value="">Tous les statuts</option>
                            <option value="valide">Payé</option>
                            <option value="en_attente">En attente</option>
                            <option value="annule">Annulé</option>
                        </select>
                    </div>
                    <button class="btn-filter-small" onclick="loadPaiements(1)">Appliquer</button>
                    <button class="btn-filter-small outline" onclick="resetFilters()">Réinitialiser</button>
                </div>

                <!-- Tableau -->
                <div class="table-responsive">
                    <table class="payment-table">
                        <thead>
                        <tr>
                            <th>Période</th>
                            <th>Date paiement</th>
                            <th>Référence</th>
                            <th>Méthode</th>
                            <th>Montant</th>
                            <th>Statut</th>
                        </tr>
                        </thead>
                        <tbody id="paiementsTableBody">
                            @include('etudiant.partials.paiements_rows', ['paiements' => $paiements])
                        </tbody>
                    </table>
                </div>

                <!-- Résumé -->
                <div class="table-summary">
                    <div class="summary-item">
                        <span class="summary-label">Total payé</span>
                        <span class="summary-value" id="summaryTotalPaye">{{ formatSystemAmount($stats['total_paye'] ?? 0) }}</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Restant dû</span>
                        <span class="summary-value warning" id="summaryTotalImpayes">{{ formatSystemAmount($stats['total_impayes'] ?? 0) }}</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Prochaine échéance</span>
                        <span class="summary-value">{{ $prochainPaiement['libelle'] ?? 'N/A' }}</span>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="pagination" id="paginationContainer">
                    {{ $paiements->links() }}
                </div>
            </div>
        </div>

        <!-- COLONNE DROITE : PAIEMENT RAPIDE -->

        <!-- <div class="panel panel-payment">
            <div class="panel-header">
                <h2>💳 Paiement rapide</h2>
                <span class="panel-badge">Sécurisé</span>
            </div>
            <div class="panel-body">


                <div class="payment-amount">
                    <span class="payment-label">Montant à payer</span>
                    <div class="payment-amount-display">
                        <span class="currency">{{ systemCurrencySymbol() }}</span>
                        <span class="amount" id="montantAPayer">
                            @php
                                $montantAPayer = $impayes->sum('solde_restant') > 0 ? $impayes->sum('solde_restant') : ($loyers->first()->montant_total ?? 0);
                            @endphp
                            {{ number_format($montantAPayer, 0, ',', ' ') }}
                        </span>
                    </div>
                    <span class="payment-period">
                        Loyer · {{ $prochainPaiement['libelle'] ?? 'N/A' }}
                        @if($impayes->isNotEmpty())
                            <span style="color:#b13e3e; display:block; font-size:var(--app-font-min, 15px); margin-top:4px;">
                                ⚠️ Inclut {{ $impayes->count() }} mois d'arriéré(s)
                            </span>
                        @endif
                    </span>
                </div>


                <div class="payment-methods">
                    <label class="payment-label">Choisissez votre méthode</label>
                    <div class="methods-grid">
                        <button class="method-btn active" onclick="selectMethod(this)" data-method="orange_money">
                            <span class="method-icon">📱</span>
                            <span class="method-name">Orange Money</span>
                        </button>
                        <button class="method-btn" onclick="selectMethod(this)" data-method="mtn_money">
                            <span class="method-icon">📱</span>
                            <span class="method-name">MTN Money</span>
                        </button>
                        <button class="method-btn" onclick="selectMethod(this)" data-method="moov_money">
                            <span class="method-icon">📱</span>
                            <span class="method-name">Moov Money</span>
                        </button>
                        <button class="method-btn" onclick="selectMethod(this)" data-method="carte_bancaire">
                            <span class="method-icon">💳</span>
                            <span class="method-name">Carte bancaire</span>
                        </button>
                        <button class="method-btn" onclick="selectMethod(this)" data-method="virement">
                            <span class="method-icon">🏦</span>
                            <span class="method-name">Virement</span>
                        </button>
                    </div>
                </div>


                <div class="payment-info">
                    <div class="info-row">
                        <span class="info-label">📞 Numéro de téléphone</span>
                        <input type="tel" class="info-input" id="telephone" placeholder="07 00 00 00 00" value="{{ $etudiant->telephone1 ?? '' }}" />
                    </div>
                    <div class="info-row">
                        <span class="info-label">📧 Email de confirmation</span>
                        <input type="email" class="info-input" id="email" placeholder="exemple@email.com" value="{{ $etudiant->email ?? '' }}" />
                    </div>
                </div>


                <div class="payment-options">
                    <label class="checkbox-label">
                        <input type="checkbox" checked id="saveCoords" />
                        <span>Enregistrer mes coordonnées pour les prochains paiements</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" checked id="sendReceipt" />
                        <span>Recevoir un reçu par email</span>
                    </label>
                </div>


                <button class="btn btn-primary btn-payment" onclick="processPayment()">
                    <span class="btn-icon">🔒</span>
                    <span id="btnPaymentText">Payer {{ formatSystemAmount($montantAPayer) }}</span>
                </button>

                <div class="payment-secure">
                    <span class="secure-icon">🔒</span>
                    <span>Paiement sécurisé · Transaction cryptée</span>
                </div>
            </div>
        </div> -->



    </div>

    <!-- SECTION : MES PAIEMENTS RÉCURRENTS -->
    <div class="recurring-section">
        <div class="section-header">
            <h3>🔄 Paiements récurrents</h3>
            <span class="section-badge">{{ $loyers->where('statut', 'actif')->count() }} actifs</span>
        </div>
        <div class="recurring-grid">
            @php
                $loyersActifs = $loyers->where('statut', 'actif')->take(3);
            @endphp
            @forelse($loyersActifs as $loyer)
            <div class="recurring-card">
                <div class="recurring-header">
                    <span class="recurring-icon">🏠</span>
                    <span class="recurring-title">Loyer {{ $loyer->mois_facturation ?? '' }}/{{ $loyer->annee_facturation ?? '' }}</span>
                    <span class="recurring-status {{ $loyer->solde_restant > 0 ? 'paused' : 'active' }}">
                        {{ $loyer->solde_restant > 0 ? 'En attente' : 'Payé' }}
                    </span>
                </div>
                <div class="recurring-details">
                    <span class="recurring-amount">{{ formatSystemAmount($loyer->montant_total) }}</span>
                    <span class="recurring-period">Solde: {{ formatSystemAmount($loyer->solde_restant) }}</span>
                </div>
                <div class="recurring-footer">
                    <span class="recurring-next">
                        @if($loyer->date_limit_paiement)
                            Échéance: {{ $loyer->date_limit_paiement->format('d/m/Y') }}
                        @else
                            À définir
                        @endif
                    </span>
                    @if($loyer->solde_restant > 0)
                        <button class="btn-action small primary" onclick="payerLoyer('{{ $loyer->id_loyer }}')">Payer</button>
                    @endif
                </div>
            </div>
            @empty
            <div class="recurring-card">
                <div class="recurring-header">
                    <span class="recurring-icon">📭</span>
                    <span class="recurring-title">Aucun loyer actif</span>
                    <span class="recurring-status paused">Inactif</span>
                </div>
                <div class="recurring-details">
                    <span class="recurring-amount">{{ formatSystemAmount(0) }}</span>
                    <span class="recurring-period">Aucun paiement en cours</span>
                </div>
                <div class="recurring-footer">
                    <span class="recurring-next">Contactez le service</span>
                </div>
            </div>
            @endforelse
        </div>
    </div>

    <!-- SECTION : CONSEILS & ASSISTANCE -->
    <div class="help-section">
        <div class="help-card">
            <div class="help-icon">❓</div>
            <div class="help-content">
                <h4>Besoin d'aide ?</h4>
                <p>Contactez le service financier du CROU pour toute question relative à vos paiements.</p>
            </div>
            <button class="btn btn-outline" onclick="showToast('Service financier: 07 00 00 00 00')"><i class="fa-solid fa-phone" aria-hidden="true"></i> Nous contacter</button>
        </div>
        <div class="help-card">
            <div class="help-icon">📋</div>
            <div class="help-content">
                <h4>Règlement intérieur</h4>
                <p>Consultez les conditions générales de paiement et les modalités de caution.</p>
            </div>
            <button class="btn btn-outline" onclick="showToast('Règlement intérieur disponible dans votre espace documents')"><i class="fa-solid fa-file-lines" aria-hidden="true"></i> Voir le document</button>
        </div>
    </div>
</div>

<style>
    /* ============================================
       STYLES - PAGE PAIEMENTS
    ============================================ */

    .payment-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 24px;
        padding: 20px 24px;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 2px 10px rgba(0, 20, 40, 0.06);
    }

    .page-title {
        font-size: 24px;
        font-weight: 700;
        color: #0a2b44;
        margin: 0;
    }

    .page-subtitle {
        font-size: var(--app-font-min, 15px);
        color: #5b7b99;
        margin: 2px 0 0;
    }

    .header-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 10px;
        font-weight: 500;
        font-size: var(--app-font-min, 15px);
        cursor: pointer;
        transition: 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-primary {
        background: #1e7aa8;
        color: #fff;
    }

    .btn-primary:hover {
        background: #166a94;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(30, 122, 168, 0.3);
    }

    .btn-outline {
        background: transparent;
        color: #1e7aa8;
        border: 1px solid #1e7aa8;
    }

    .btn-outline:hover {
        background: #f4f7fc;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: #fff;
        border-radius: 16px;
        padding: 18px 20px;
        box-shadow: 0 2px 10px rgba(0, 20, 40, 0.06);
        display: flex;
        align-items: flex-start;
        gap: 14px;
        border-left: 4px solid #5b7b99;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 16px rgba(0, 20, 40, 0.10);
    }

    .stat-card .stat-icon { font-size: 28px; flex-shrink: 0; margin-top: 2px; }
    .stat-card .stat-content { flex: 1; min-width: 0; }
    .stat-card .label { font-size: var(--app-font-min, 15px); font-weight: 600; color: #5b7b99; text-transform: uppercase; letter-spacing: 0.5px; }
    .stat-card .value { font-size: 26px; font-weight: 700; color: #0a2b44; margin: 2px 0; }
    .stat-card .sub { font-size: var(--app-font-min, 15px); color: #5b7b99; }

    .stat-card.green { border-left-color: #2a8d6c; }
    .stat-card.amber { border-left-color: #d68a3a; }
    .stat-card.teal { border-left-color: #1a6e4f; }
    .stat-card.purple { border-left-color: #6a4d8a; }

    .stat-progress { margin-top: 8px; display: flex; align-items: center; gap: 10px; }
    .stat-progress .progress-bar { flex: 1; height: 4px; background: #dae2ec; border-radius: 8px; overflow: hidden; }
    .stat-progress .progress-fill { height: 100%; border-radius: 8px; background: #1e7aa8; transition: width 0.6s ease; }
    .stat-progress .progress-label { font-size: var(--app-font-min, 15px); color: #5b7b99; }

    .countdown { margin-top: 6px; font-size: var(--app-font-min, 15px); color: #d68a3a; font-weight: 500; }

    .status-badge { display: inline-block; padding: 2px 12px; border-radius: 30px; font-size: var(--app-font-min, 15px); font-weight: 500; margin-top: 6px; }
    .status-badge.success { background: #ddf0e8; color: #2a8d6c; }
    .status-badge.danger { background: #fce1e1; color: #b13e3e; }

    .stat-trend { font-size: var(--app-font-min, 15px); margin-top: 4px; }
    .stat-trend.up { color: #2a8d6c; }
    .stat-trend.down { color: #b13e3e; }

    /* GRILLE PRINCIPALE */
    .payment-grid {
        display: grid;
        grid-template-columns: 1.5fr 1fr;
        gap: 24px;
        align-items: start;
    }

    /* PANELS */
    .panel {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 2px 10px rgba(0, 20, 40, 0.06);
        overflow: hidden;
    }

    .panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 20px;
        border-bottom: 1px solid #dae2ec;
        background: #f4f7fc;
    }

    .panel-header h2 { font-size: 16px; font-weight: 600; color: #0a2b44; margin: 0; }
    .header-left { display: flex; align-items: center; gap: 10px; }
    .panel-badge { font-size: var(--app-font-min, 15px); font-weight: 500; background: #e7edf5; padding: 2px 12px; border-radius: 30px; color: #5b7b99; }
    .header-actions { display: flex; gap: 4px; }

    .btn-icon-only {
        background: none;
        border: none;
        font-size: 18px;
        cursor: pointer;
        color: #5b7b99;
        transition: 0.2s;
        padding: 4px 8px;
        border-radius: 6px;
    }
    .btn-icon-only:hover { background: #f4f7fc; color: #0a2b44; }

    .panel-body { padding: 20px; }

    /* FILTRES */
    .table-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 16px;
        padding: 8px 12px;
        background: #f4f7fc;
        border-radius: 10px;
        align-items: center;
    }

    .filter-group { display: flex; align-items: center; gap: 6px; }
    .filter-select {
        padding: 6px 12px;
        border: 1px solid #dae2ec;
        border-radius: 30px;
        font-size: var(--app-font-min, 15px);
        background: #fff;
        cursor: pointer;
        transition: 0.2s;
    }
    .filter-select:focus { outline: none; border-color: #1e7aa8; }

    .btn-filter-small {
        padding: 6px 16px;
        border: none;
        border-radius: 30px;
        font-size: var(--app-font-min, 15px);
        font-weight: 500;
        cursor: pointer;
        transition: 0.2s;
        background: #1e7aa8;
        color: #fff;
    }
    .btn-filter-small:hover { background: #166a94; }
    .btn-filter-small.outline { background: transparent; color: #5b7b99; border: 1px solid #dae2ec; }
    .btn-filter-small.outline:hover { background: #f4f7fc; }

    /* TABLEAU */
    .table-responsive { overflow-x: auto; }
    .payment-table { width: 100%; border-collapse: collapse; font-size: var(--app-font-min, 15px); min-width: 600px; }
    .payment-table thead th { text-align: left; padding: 10px 12px; background: #f2f6fb; color: #5b7b99; font-weight: 600; font-size: var(--app-font-min, 15px); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #dae2ec; }
    .payment-table tbody td { padding: 10px 12px; border-bottom: 1px solid #eaeef4; color: #0a2b44; vertical-align: middle; }
    .payment-table tbody tr:hover td { background: #f8fafc; }
    .payment-table .row-pending { background: #fcf7f2; }
    .payment-table .row-paid:hover td { background: #f0faf5; }

    .period-date { font-weight: 500; display: block; }
    .period-tag { font-size: var(--app-font-min, 15px); color: #d68a3a; background: #fcead8; padding: 1px 8px; border-radius: 30px; }
    .ref-code { font-family: monospace; font-size: var(--app-font-min, 15px); color: #5b7b99; background: #f4f7fc; padding: 2px 8px; border-radius: 4px; }
    .method-tag { display: inline-block; padding: 2px 10px; border-radius: 30px; font-size: var(--app-font-min, 15px); font-weight: 500; background: #fcead8; color: #a8621a; }
    .amount-cell { font-weight: 600; }

    /* BADGES */
    .badge { display: inline-block; padding: 2px 12px; border-radius: 30px; font-size: var(--app-font-min, 15px); font-weight: 500; }
    .badge.success { background: #ddf0e8; color: #2a8d6c; }
    .badge.pending { background: #fcead8; color: #d68a3a; }
    .badge.danger { background: #fce1e1; color: #b13e3e; }

    /* RÉSUMÉ */
    .table-summary {
        display: flex;
        flex-wrap: wrap;
        gap: 20px 40px;
        margin: 16px 0 14px;
        padding: 12px 16px;
        background: #f4f7fc;
        border-radius: 10px;
    }
    .summary-item { display: flex; align-items: center; gap: 8px; font-size: var(--app-font-min, 15px); }
    .summary-label { color: #5b7b99; }
    .summary-value { font-weight: 600; color: #0a2b44; }
    .summary-value.warning { color: #d68a3a; }

    /* PAGINATION */
    .pagination {
        display: flex;
        align-items: center;
        gap: 4px;
        margin-top: 12px;
        justify-content: center;
        flex-wrap: wrap;
    }
    .page-link {
        padding: 6px 12px;
        border: 1px solid #dae2ec;
        border-radius: 6px;
        background: #fff;
        cursor: pointer;
        font-size: var(--app-font-min, 15px);
        color: #0a2b44;
        transition: 0.2s;
        text-decoration: none;
        display: inline-block;
        min-width: 32px;
        text-align: center;
    }
    .page-link:hover:not(.disabled):not(.active) {
        background: #f4f7fc;
        border-color: #1e7aa8;
    }
    .page-link.active {
        background: #1e7aa8;
        color: #fff;
        border-color: #1e7aa8;
        cursor: default;
    }
    .page-link.disabled {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
    }

    /* PAIEMENT RAPIDE */
    .payment-amount { text-align: center; padding: 16px; background: #f4f7fc; border-radius: 12px; margin-bottom: 20px; }
    .payment-amount-display { display: flex; align-items: baseline; justify-content: center; gap: 4px; }
    .payment-amount-display .currency { font-size: 18px; font-weight: 500; color: #5b7b99; }
    .payment-amount-display .amount { font-size: 36px; font-weight: 700; color: #0a2b44; }
    .payment-period { font-size: var(--app-font-min, 15px); color: #5b7b99; display: block; margin-top: 2px; }

    .payment-methods { margin-bottom: 16px; }
    .payment-label { font-size: var(--app-font-min, 15px); color: #5b7b99; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 4px; }
    .methods-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(90px, 1fr)); gap: 8px; margin-top: 8px; }
    .method-btn {
        padding: 10px 8px;
        border: 2px solid #dae2ec;
        border-radius: 10px;
        background: #fff;
        cursor: pointer;
        text-align: center;
        font-size: var(--app-font-min, 15px);
        color: #5b7b99;
        transition: 0.2s;
    }
    .method-btn:hover { border-color: #1e7aa8; }
    .method-btn.active { border-color: #1e7aa8; background: #dce8f5; color: #0a2b44; }
    .method-btn .method-icon { display: block; font-size: 24px; margin-bottom: 2px; }
    .method-btn .method-name { font-size: var(--app-font-min, 15px); font-weight: 500; }

    .payment-info { margin-bottom: 16px; }
    .info-row { display: flex; flex-direction: column; gap: 4px; margin-bottom: 10px; }
    .info-label { font-size: var(--app-font-min, 15px); font-weight: 500; color: #0a2b44; }
    .info-input {
        padding: 10px 14px;
        border: 1px solid #dae2ec;
        border-radius: 10px;
        font-size: var(--app-font-min, 15px);
        transition: 0.2s;
        font-family: inherit;
    }
    .info-input:focus { border-color: #1e7aa8; outline: none; box-shadow: 0 0 0 3px rgba(30, 122, 168, 0.1); }

    .payment-options { margin-bottom: 16px; display: flex; flex-direction: column; gap: 8px; }
    .checkbox-label { display: flex; align-items: center; gap: 10px; font-size: var(--app-font-min, 15px); cursor: pointer; }
    .checkbox-label input[type="checkbox"] { width: 16px; height: 16px; accent-color: #1e7aa8; cursor: pointer; }

    .btn-payment {
        width: 100%;
        padding: 14px;
        font-size: 16px;
        border-radius: 12px;
        margin-bottom: 12px;
        background: #1e7aa8;
        color: #fff;
        border: none;
        font-weight: 600;
        cursor: pointer;
        transition: 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .btn-payment:hover { background: #166a94; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(30, 122, 168, 0.3); }
    .btn-payment:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }

    .payment-secure { text-align: center; font-size: var(--app-font-min, 15px); color: #5b7b99; display: flex; align-items: center; justify-content: center; gap: 6px; }

    /* SECTION RÉCURRENTS */
    .recurring-section { margin-top: 24px; padding: 20px 24px; background: #fff; border-radius: 16px; box-shadow: 0 2px 10px rgba(0, 20, 40, 0.06); }
    .section-header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
    .section-header h3 { font-size: 16px; font-weight: 600; color: #0a2b44; margin: 0; }
    .section-badge { font-size: var(--app-font-min, 15px); font-weight: 500; background: #e7edf5; padding: 2px 12px; border-radius: 30px; color: #5b7b99; }

    .recurring-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; }
    .recurring-card { border: 1px solid #dae2ec; border-radius: 12px; padding: 16px; transition: 0.2s; }
    .recurring-card:hover { border-color: #1e7aa8; box-shadow: 0 2px 10px rgba(0, 20, 40, 0.06); }
    .recurring-header { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
    .recurring-icon { font-size: 20px; }
    .recurring-title { font-weight: 600; color: #0a2b44; flex: 1; }
    .recurring-status { font-size: var(--app-font-min, 15px); font-weight: 600; padding: 2px 10px; border-radius: 30px; text-transform: uppercase; }
    .recurring-status.active { background: #ddf0e8; color: #2a8d6c; }
    .recurring-status.paused { background: #fcead8; color: #d68a3a; }

    .recurring-details { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: var(--app-font-min, 15px); }
    .recurring-amount { font-weight: 700; color: #0a2b44; }
    .recurring-period { color: #5b7b99; }

    .recurring-footer { display: flex; justify-content: space-between; align-items: center; font-size: var(--app-font-min, 15px); color: #5b7b99; padding-top: 8px; border-top: 1px solid #dae2ec; }
    .btn-action { padding: 3px 12px; border: 1px solid #dae2ec; border-radius: 30px; font-size: var(--app-font-min, 15px); background: #fff; cursor: pointer; transition: 0.2s; }
    .btn-action:hover { background: #f4f7fc; }
    .btn-action.primary { border-color: #1e7aa8; color: #1e7aa8; }
    .btn-action.primary:hover { background: #dce8f5; }

    /* SECTION ASSISTANCE */
    .help-section { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; margin-top: 24px; }
    .help-card { display: flex; align-items: center; gap: 16px; padding: 16px 20px; background: #fff; border-radius: 16px; box-shadow: 0 2px 10px rgba(0, 20, 40, 0.06); transition: 0.2s; }
    .help-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0, 20, 40, 0.10); }
    .help-icon { font-size: 32px; flex-shrink: 0; }
    .help-content { flex: 1; }
    .help-content h4 { font-size: var(--app-font-min, 15px); font-weight: 600; color: #0a2b44; margin: 0 0 2px; }
    .help-content p { font-size: var(--app-font-min, 15px); color: #5b7b99; margin: 0; }

    /* ANIMATIONS */
    .loading-shimmer {
        animation: shimmer 1.2s ease-in-out infinite;
    }
    @keyframes shimmer {
        0% { opacity: 0.5; }
        50% { opacity: 1; }
        100% { opacity: 0.5; }
    }

    .fade-in {
        animation: fadeIn 0.3s ease;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* RESPONSIVE */
    @media (max-width: 1024px) {
        .payment-grid { grid-template-columns: 1fr; }
    }

    @media (max-width: 768px) {
        .payment-header { flex-direction: column; align-items: flex-start; }
        .stats-grid { grid-template-columns: 1fr 1fr; }
        .header-actions { width: 100%; }
        .header-actions .btn { flex: 1; justify-content: center; }
        .table-filters { flex-direction: column; align-items: stretch; }
        .methods-grid { grid-template-columns: repeat(3, 1fr); }
        .recurring-grid { grid-template-columns: 1fr; }
        .help-section { grid-template-columns: 1fr; }
    }

    @media (max-width: 480px) {
        .stats-grid { grid-template-columns: 1fr; }
        .payment-table { font-size: var(--app-font-min, 15px); min-width: 500px; }
        .payment-amount-display .amount { font-size: 28px; }
        .methods-grid { grid-template-columns: repeat(2, 1fr); }
    }
</style>

<script>
    // ============================================
    // VARIABLES GLOBALES
    // ============================================
    let currentPage = 1;
    let perPage = 10;
    let statutFilter = '';
    let isLoading = false;

    // ============================================
    // CHARGEMENT DES PAIEMENTS VIA AJAX
    // ============================================
    function loadPaiements(page = 1) {
        if (isLoading) return;

        isLoading = true;
        currentPage = page;
        perPage = document.getElementById('filterPerPage')?.value || 10;
        statutFilter = document.getElementById('filterStatut')?.value || '';

        const tbody = document.getElementById('paiementsTableBody');
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align:center; padding:20px;">
                    <div class="loading-shimmer">⏳ Chargement des paiements...</div>
                </td>
            </tr>
        `;

        const url = new URL('{{ route("etudiants.paiements.ajax") }}');
        url.searchParams.append('page', page);
        url.searchParams.append('per_page', perPage);
        if (statutFilter) {
            url.searchParams.append('statut', statutFilter);
        }

        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                renderPaiements(result.data);
                renderPagination(result.pagination);
                updateStats(result.stats);
                document.getElementById('totalTransactions').textContent = result.pagination.total + ' transactions';
                document.getElementById('nbPaiements').textContent = result.pagination.total;
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align:center; padding:20px; color:#b13e3e;">
                            ❌ Erreur: ${result.error || 'Chargement impossible'}
                        </td>
                    </tr>
                `;
                showToast('❌ Erreur: ' + (result.error || 'Chargement impossible'));
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align:center; padding:20px; color:#b13e3e;">
                        ❌ Une erreur est survenue lors du chargement
                    </td>
                </tr>
            `;
        })
        .finally(() => {
            isLoading = false;
        });
    }

    // ============================================
    // RENDU DES PAIEMENTS
    // ============================================
    function renderPaiements(paiements) {
        const tbody = document.getElementById('paiementsTableBody');

        if (!paiements || paiements.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align:center; padding:20px; color:#5b7b99;">
                        Aucun paiement trouvé
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        paiements.forEach(paiement => {
            const isPaid = paiement.statut_paiement === 'valide';
            const isPending = paiement.statut_paiement === 'en_attente';
            const rowClass = isPaid ? 'row-paid' : (isPending ? 'row-pending' : '');
            const badgeClass = isPaid ? 'success' : (isPending ? 'pending' : 'danger');
            const badgeText = isPaid ? 'Payé' : (isPending ? 'En attente' : 'Annulé');

            let moisLibelle = 'N/A';
            if (paiement.mois) {
                if (Array.isArray(paiement.mois)) {
                    moisLibelle = paiement.mois[0] || 'N/A';
                } else if (typeof paiement.mois === 'string') {
                    try {
                        const parsed = JSON.parse(paiement.mois);
                        moisLibelle = Array.isArray(parsed) ? (parsed[0] || 'N/A') : paiement.mois;
                    } catch {
                        moisLibelle = paiement.mois;
                    }
                }
            }

            const datePaiement = paiement.date_paiement ? new Date(paiement.date_paiement).toLocaleDateString('fr-FR') : '--';

            html += `
                <tr class="${rowClass} fade-in">
                    <td>
                        <span class="period-date">${moisLibelle}</span>
                        ${isPending ? '<span class="period-tag">En attente</span>' : ''}
                    </td>
                    <td>${datePaiement}</td>
                    <td><span class="ref-code">${paiement.numero_paiement || 'N/A'}</span></td>
                    <td><span class="method-tag orange">${paiement.methode_label || paiement.methode_paiement || 'N/A'}</span></td>
                    <td class="amount-cell">${Number(paiement.montant).toLocaleString('fr-FR')} FCFA</td>
                    <td><span class="badge ${badgeClass}">${badgeText}</span></td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    }

    // ============================================
    // RENDU DE LA PAGINATION
    // ============================================
    function renderPagination(pagination) {
        const container = document.getElementById('paginationContainer');

        if (!pagination || pagination.last_page <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '';
        const current = pagination.current_page;
        const last = pagination.last_page;

        // Précédent
        if (current > 1) {
            html += `<span class="page-link" onclick="loadPaiements(${current - 1})">‹</span>`;
        } else {
            html += `<span class="page-link disabled">‹</span>`;
        }

        // Pages
        const maxVisible = 5;
        let startPage = Math.max(1, current - Math.floor(maxVisible / 2));
        let endPage = Math.min(last, startPage + maxVisible - 1);

        if (endPage - startPage < maxVisible - 1) {
            startPage = Math.max(1, endPage - maxVisible + 1);
        }

        if (startPage > 1) {
            html += `<span class="page-link" onclick="loadPaiements(1)">1</span>`;
            if (startPage > 2) {
                html += `<span class="page-link disabled">…</span>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            const active = i === current ? 'active' : '';
            html += `<span class="page-link ${active}" onclick="${i !== current ? `loadPaiements(${i})` : ''}">${i}</span>`;
        }

        if (endPage < last) {
            if (endPage < last - 1) {
                html += `<span class="page-link disabled">…</span>`;
            }
            html += `<span class="page-link" onclick="loadPaiements(${last})">${last}</span>`;
        }

        // Suivant
        if (current < last) {
            html += `<span class="page-link" onclick="loadPaiements(${current + 1})">›</span>`;
        } else {
            html += `<span class="page-link disabled">›</span>`;
        }

        container.innerHTML = html;
    }

    // ============================================
    // MISE À JOUR DES STATISTIQUES
    // ============================================
    function updateStats(stats) {
        if (!stats) return;

        if (stats.total_paye !== undefined) {
            document.getElementById('summaryTotalPaye').textContent = Number(stats.total_paye).toLocaleString('fr-FR') + ' FCFA';
        }
        if (stats.total_impayes !== undefined) {
            document.getElementById('summaryTotalImpayes').textContent = Number(stats.total_impayes).toLocaleString('fr-FR') + ' FCFA';
        }
    }

    // ============================================
    // RÉINITIALISATION DES FILTRES
    // ============================================
    function resetFilters() {
        document.getElementById('filterPerPage').value = '10';
        document.getElementById('filterStatut').value = '';
        loadPaiements(1);
    }

    // ============================================
    // SÉLECTION DES MÉTHODES DE PAIEMENT
    // ============================================
    function selectMethod(element) {
        document.querySelectorAll('.method-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        element.classList.add('active');
    }

    // ============================================
    // PAIEMENT D'UN LOYER SPÉCIFIQUE
    // ============================================
    function payerLoyer(loyerId) {
        showToast('💳 Redirection vers le paiement du loyer sélectionné...');
    }

    // ============================================
    // TOGGLE FILTRES
    // ============================================
    function toggleFilter() {
        const filters = document.getElementById('tableFilters');
        if (filters) {
            filters.style.display = filters.style.display === 'none' ? 'flex' : 'none';
        }
    }

    // ============================================
    // TRAITEMENT DU PAIEMENT
    // ============================================
    function processPayment() {
        const activeMethod = document.querySelector('.method-btn.active');
        const methodName = activeMethod ? activeMethod.querySelector('.method-name').textContent : 'non sélectionnée';
        const methodValue = activeMethod ? activeMethod.dataset.method : 'especes';

        const phone = document.getElementById('telephone')?.value || '';
        const email = document.getElementById('email')?.value || '';

        if (!phone || phone.length < 8) {
            showToast('⚠️ Veuillez renseigner un numéro de téléphone valide');
            return;
        }

        const data = {
            montant: document.getElementById('montantAPayer')?.textContent?.replace(/\s/g, '') || '0',
            methode_paiement: methodValue,
            telephone: phone,
            email: email,
            _token: document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
        };

        const loadingBtn = document.querySelector('.btn-payment');
        const originalText = document.getElementById('btnPaymentText').textContent;

        loadingBtn.innerHTML = '⏳ Traitement en cours...';
        loadingBtn.disabled = true;
        loadingBtn.style.opacity = '0.7';

        fetch('#', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                loadingBtn.innerHTML = '✅ Paiement effectué !';
                loadingBtn.style.background = '#2a8d6c';
                showToast('✅ ' + result.message);
                setTimeout(() => location.reload(), 2000);
            } else {
                loadingBtn.innerHTML = '❌ Erreur';
                loadingBtn.style.background = '#b13e3e';
                showToast('❌ ' + (result.message || 'Erreur lors du paiement'));
                setTimeout(() => {
                    loadingBtn.innerHTML = originalText;
                    loadingBtn.disabled = false;
                    loadingBtn.style.opacity = '1';
                    loadingBtn.style.background = '';
                    document.getElementById('btnPaymentText').textContent = originalText;
                }, 3000);
            }
        })
        .catch(() => {
            loadingBtn.innerHTML = '❌ Erreur';
            loadingBtn.style.background = '#b13e3e';
            showToast('❌ Une erreur est survenue');
            setTimeout(() => {
                loadingBtn.innerHTML = originalText;
                loadingBtn.disabled = false;
                loadingBtn.style.opacity = '1';
                loadingBtn.style.background = '';
                document.getElementById('btnPaymentText').textContent = originalText;
            }, 3000);
        });
    }

    // ============================================
    // TOAST NOTIFICATION
    // ============================================
    function showToast(message) {
        let toast = document.querySelector('.toast-notification');

        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'toast-notification';
            document.body.appendChild(toast);

            const style = document.createElement('style');
            style.textContent = `
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
                    box-shadow: 0 8px 24px rgba(0,20,40,0.2);
                    transform: translateY(100px);
                    opacity: 0;
                    transition: all 0.4s ease;
                    z-index: 1000;
                    max-width: 400px;
                }
                .toast-notification.show { transform: translateY(0); opacity: 1; }
                .toast-notification.success { background: #2a8d6c; }
                .toast-notification.error { background: #b13e3e; }
            `;
            document.head.appendChild(style);
        }

        toast.textContent = message;
        toast.className = 'toast-notification show';
        if (message.includes('✅')) toast.classList.add('success');
        if (message.includes('❌') || message.includes('Erreur')) toast.classList.add('error');

        clearTimeout(toast._timeout);
        toast._timeout = setTimeout(() => {
            toast.classList.remove('show');
        }, 4000);
    }

    // ============================================
    // INITIALISATION
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        loadPaiements(1);
        console.log('✅ Page paiements initialisée avec pagination AJAX');
    });
</script>
