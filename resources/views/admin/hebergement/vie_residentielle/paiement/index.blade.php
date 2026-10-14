{{-- resources/views/admin/hebergement/vie_residentielle/paiement/index.blade.php --}}
@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'finances'
])

@section('title', 'Paiements des loyers')

@push('styles')
    <style>
        /* ============================================
           STYLES - PAIEMENTS
        ============================================ */
        .payment-container {
            max-width: 1440px;
            margin: 0 auto;
            padding: 0 4px;
        }

        /* --- En-tête --- */
        .payment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 28px;
            padding: 16px 20px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0, 20, 40, 0.06);
        }

        .payment-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #0b2b4a;
            margin: 0;
        }

        .payment-header .subtitle {
            font-size: var(--app-font-min, 15px);
            color: #4a6f8a;
            font-weight: 400;
            margin-left: 8px;
        }

        .payment-meta {
            font-size: var(--app-font-min, 15px);
            color: #1e3b5c;
            font-weight: 500;
            background: #f2f6fb;
            padding: 8px 18px;
            border-radius: 30px;
        }

        /* --- KPI Cards --- */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }

        .kpi-card {
            background: #fff;
            padding: 18px 16px;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0, 20, 40, 0.06);
            border-left: 4px solid #1e7aa8;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 20, 40, 0.10);
        }

        .kpi-card .kpi-label {
            font-size: var(--app-font-min, 15px);
            font-weight: 600;
            color: #5b7b99;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .kpi-card .kpi-value {
            font-size: 28px;
            font-weight: 700;
            color: #0a2b44;
            margin: 4px 0 2px;
        }

        .kpi-card .kpi-trend {
            font-size: var(--app-font-min, 15px);
            display: inline-block;
            padding: 0 10px;
            border-radius: 30px;
        }

        .kpi-card .kpi-trend.up { background: #ddf0e8; color: #1a6e4f; }
        .kpi-card .kpi-trend.danger { background: #fce1e1; color: #b13e3e; }
        .kpi-card .kpi-trend.warning { background: #fcead8; color: #a8621a; }
        .kpi-card .kpi-trend.stable { background: #e7edf5; color: #1f4462; }

        .kpi-card.success { border-left-color: #2a8d6c; }
        .kpi-card.info { border-left-color: #1e7aa8; }
        .kpi-card.danger { border-left-color: #b13e3e; }
        .kpi-card.warning { border-left-color: #d68a3a; }
        .kpi-card.purple { border-left-color: #7a5d9e; }

        /* --- Filtres --- */
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 14px 20px;
            background: #fff;
            padding: 12px 20px;
            border-radius: 14px;
            margin-bottom: 28px;
            box-shadow: 0 2px 8px rgba(0, 20, 40, 0.04);
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-group label {
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
            color: #1f4462;
        }

        .filter-group select,
        .filter-group input {
            padding: 6px 14px;
            border-radius: 30px;
            border: 1px solid #dae2ec;
            background: #f8fafc;
            font-size: var(--app-font-min, 15px);
            color: #17324b;
            outline: none;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            border-color: #1e7aa8;
        }

        .btn-apply {
            background: #1e7aa8;
            color: #fff;
            border: none;
            padding: 8px 24px;
            border-radius: 30px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
            margin-left: auto;
        }

        .btn-apply:hover {
            background: #166a94;
        }

        .btn-export {
            background: #2a8d6c;
            color: #fff;
            border: none;
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-export:hover {
            background: #1f7a5c;
        }

        /* --- Graphiques --- */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }

        .chart-card {
            background: #fff;
            border-radius: 20px;
            padding: 18px 18px 12px;
            box-shadow: 0 4px 16px rgba(0, 20, 40, 0.05);
        }

        .chart-title {
            font-size: var(--app-font-min, 15px);
            font-weight: 600;
            color: #1a3b57;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .chart-badge {
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
            background: #e7edf5;
            padding: 1px 10px;
            border-radius: 30px;
            color: #1f4c70;
        }

        .chart-wrapper {
            position: relative;
            height: 190px;
        }

        /* --- Tableaux --- */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin: 32px 0 16px;
        }

        .section-header h2 {
            font-size: 18px;
            font-weight: 600;
            color: #1a3b57;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-badge {
            background: #dce6ef;
            padding: 2px 14px;
            border-radius: 30px;
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
            color: #1f4c70;
        }

        .section-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 6px 16px;
            border-radius: 30px;
            border: none;
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-action.primary {
            background: #1e7aa8;
            color: #fff;
        }
        .btn-action.primary:hover {
            background: #166a94;
        }
        .btn-action.success {
            background: #2a8d6c;
            color: #fff;
        }
        .btn-action.success:hover {
            background: #1f7a5c;
        }
        .btn-action.warning {
            background: #d68a3a;
            color: #fff;
        }
        .btn-action.warning:hover {
            background: #c07a2e;
        }
        .btn-action.danger {
            background: #b13e3e;
            color: #fff;
        }
        .btn-action.danger:hover {
            background: #9a3333;
        }

        .table-responsive {
            background: #fff;
            border-radius: 20px;
            padding: 4px 4px 8px;
            box-shadow: 0 4px 16px rgba(0, 20, 40, 0.05);
            overflow-x: auto;
            margin-bottom: 8px;
        }

        .dashboard-table {
            width: 100%;
            border-collapse: collapse;
            font-size: var(--app-font-min, 15px);
            min-width: 900px;
        }

        .dashboard-table thead th {
            text-align: left;
            padding: 12px 14px;
            background: #f2f6fb;
            color: #1f4462;
            font-weight: 600;
            font-size: var(--app-font-min, 15px);
            text-transform: uppercase;
            letter-spacing: 0.4px;
            border-bottom: 1px solid #dae2ec;
        }

        .dashboard-table tbody td {
            padding: 10px 14px;
            border-bottom: 1px solid #eaeef4;
            color: #17324b;
            vertical-align: middle;
        }

        .dashboard-table tbody tr:last-child td {
            border-bottom: none;
        }

        .dashboard-table .row-paid {
            background: #f0faf5;
        }

        .dashboard-table .row-unpaid {
            background: #fcf7f2;
        }

        .dashboard-table .row-overdue {
            background: #fcf2f2;
        }

        .dashboard-table .row-total {
            font-weight: 600;
            background: #f0f5fa;
        }

        /* --- Badges --- */
        .badge {
            display: inline-block;
            padding: 2px 12px;
            border-radius: 30px;
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
            color: #1d4b70;
            background: #e7edf5;
        }

        .badge.success { background: #ddf0e8; color: #1a6e4f; }
        .badge.warning { background: #fcead8; color: #a8621a; }
        .badge.danger { background: #fce1e1; color: #b13e3e; }
        .badge.info { background: #dce8f5; color: #1e5f8a; }
        .badge.purple { background: #e8e1f0; color: #6a4d8a; }

        .status-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
        }
        .status-dot.paid { background: #2a8d6c; }
        .status-dot.pending { background: #d68a3a; }
        .status-dot.overdue { background: #b13e3e; }

        .table-footer {
            text-align: right;
            font-size: var(--app-font-min, 15px);
            color: #5f7e9c;
            padding: 6px 14px 2px;
        }

        /* --- Progress bar --- */
        .progress-bar {
            width: 100px;
            height: 6px;
            background: #e2e9f2;
            border-radius: 8px;
            overflow: hidden;
            display: inline-block;
            vertical-align: middle;
        }

        .progress-fill {
            height: 100%;
            border-radius: 8px;
            transition: width 0.6s ease;
        }

        .progress-fill.success { background: #2a8d6c; }
        .progress-fill.warning { background: #d68a3a; }
        .progress-fill.danger { background: #b13e3e; }

        /* --- Styles pour la recherche dynamique --- */
        #search_resident:focus {
            border-color: #1e7aa8;
            box-shadow: 0 0 0 3px rgba(30, 122, 168, 0.1);
        }

        #resident_results {
            position: absolute;
            z-index: 1000;
            width: 100%;
            max-height: 300px;
            overflow-y: auto;
            background: #fff;
            border: 1px solid #dae2ec;
            border-radius: 8px;
            margin-top: 4px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        #resident_results::-webkit-scrollbar {
            width: 6px;
        }

        #resident_results::-webkit-scrollbar-track {
            background: #f0f2f5;
            border-radius: 8px;
        }

        #resident_results::-webkit-scrollbar-thumb {
            background: #c1d0df;
            border-radius: 8px;
        }

        #resident_results::-webkit-scrollbar-thumb:hover {
            background: #a0b3c7;
        }

        #resident_results .result-item:hover {
            background: #f0f6fb !important;
        }

        .resident-info-card {
            margin-top: 12px;
            padding: 12px 16px;
            background: #f0f6fb;
            border-radius: 8px;
            border-left: 4px solid #1e7aa8;
            animation: fadeIn 0.3s ease;
        }

        .resident-info-card .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            font-size: var(--app-font-min, 15px);
        }

        .resident-info-card .info-grid .label {
            font-weight: 600;
            color: #1f4462 !important; 
        }

        .resident-info-card .info-grid .value {
            color: #17324b;
        }

        .resident-info-card .info-grid .value.highlight {
            font-weight: 700;
            color: #1a6e4f;
        }

        .resident-info-card .info-grid .value.danger {
            color: #b13e3e;
        }

        .arriere-message {
            margin-top: 8px;
            padding: 8px 12px;
            background: #fcf2f2;
            border-radius: 6px;
            color: #b13e3e;
            font-size: var(--app-font-min, 15px);
            border-left: 3px solid #b13e3e;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* --- Responsive --- */
        @media (max-width: 768px) {
            .payment-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .kpi-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .charts-grid {
                grid-template-columns: 1fr;
            }
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-group {
                flex-wrap: wrap;
            }
            .btn-apply,
            .btn-export {
                margin-left: 0;
            }
            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .resident-info-card .info-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .kpi-grid {
                grid-template-columns: 1fr;
            }
            .payment-meta {
                font-size: var(--app-font-min, 15px);
                padding: 6px 14px;
            }
        }
    </style>
@endpush

@section('content')

    <x-breadcrumbs
            title="Paiements des loyers"
            :items="[
            'Gestion Hébergement' => route('admin.hebergement.index'),
            'Vie résidentielle' => route('admin.hebergement.vie_residentielle.index'),
            'Paiements' => null,
        ]"
    />

    <div class="payment-container">

        <!-- EN-TÊTE -->
        <div class="payment-header">
            <div>
                <h1>💰 Paiements des loyers</h1>
                <span class="subtitle">Gestion des encaissements et suivi des impayés</span>
            </div>
            <div class="payment-meta">
                📅 Période : {{ now()->format('F Y') }}
                <span class="campaign-badge" style="background:#eaf0f8; padding:2px 12px; border-radius:20px; margin-left:8px; font-weight:600; color:#1e7aa8;">Campagne N+1</span>
            </div>
        </div>

        <!-- KPI GLOBAUX -->
        <div class="kpi-grid">
            @foreach($kpis as $kpi)
            <div class="kpi-card {{ $kpi['type'] }}">
                <div class="kpi-label">{{ $kpi['label'] }}</div>
                <div class="kpi-value">{{ $kpi['value'] }}</div>
                <div class="kpi-trend {{ $kpi['trend_class'] }}">{{ $kpi['trend'] }}</div>
            </div>
            @endforeach
        </div>

        <!-- FILTRES -->
        @adminroute('admin.hebergement.paiements.index')
        <form method="GET" action="{{ route('admin.hebergement.paiements.index') }}" class="filter-bar">
            <div class="filter-group">
                <label>🏢 Résidence</label>
                <select name="residence">
                    <option value="">Toutes</option>
                    @foreach($residences as $residence)
                        <option value="{{ $residence->id_cite }}" {{ request('residence') == $residence->id_cite ? 'selected' : '' }}>
                            {{ $residence->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label>📊 Statut</label>
                <select name="statut">
                    <option value="">Tous</option>
                    <option value="valide" {{ request('statut') == 'valide' ? 'selected' : '' }}>Payé</option>
                    <option value="en_attente" {{ request('statut') == 'en_attente' ? 'selected' : '' }}>En attente</option>
                    <option value="annule" {{ request('statut') == 'annule' ? 'selected' : '' }}>Annulé</option>
                </select>
            </div>
            <div class="filter-group">
                <label>📅 Mois</label>
                <input type="month" name="mois" value="{{ request('mois', now()->format('Y-m')) }}">
            </div>
            <button type="submit" class="btn-apply">Appliquer</button>
            @adminroute('admin.hebergement.paiements.export')
            <a href="{{ route('admin.hebergement.paiements.export', request()->query()) }}" class="btn-export">📥 Exporter</a>
            @endadminroute

        </form>
        @endadminroute

        <!-- TABLEAU DES PAIEMENTS -->
        <div class="section-header">
            <h2>
                📋 Liste des paiements
                <span class="section-badge">{{ $paiements->total() }} paiements</span>
            </h2>
            <div class="section-actions"> 
                @adminroute('admin.hebergement.paiements.store')
<button class="btn-action primary" onclick="openModalPaiement()">➕ Nouveau paiement</button>
@endadminroute

                <button class="btn-action warning" onclick="openModalRelance()">📤 Relances impayés</button>
                @adminroute('admin.hebergement.paiements.rapport_financier')
                <a href="{{ route('admin.hebergement.paiements.rapport_financier') }}" class="btn-action success">📊 Rapport financier</a>
@endadminroute
            </div>
        </div>

        <div class="table-responsive">
            <table class="dashboard-table">
                <thead>
                <tr>
                    <th>Résident</th>
                    <th>Résidence</th>
                    <th>Chambre</th>
                    <th>Mois</th>
                    <th>Montant</th>
                    <th>Méthode</th>
                    <th>Statut</th>
                    <th>Date paiement</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($paiements as $paiement)
                    @php
                        $rowClass = match($paiement->statut_paiement) {
                            'valide' => 'row-paid',
                            'en_attente' => 'row-unpaid',
                            'annule', 'echoue' => 'row-overdue',
                            default => ''
                        };
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td><strong>{{ $paiement->resident?->name ?? 'N/A' }} </strong> <br/>{{ $paiement->resident?->tel1 ?? 'N/A' }}  </td>
                        <td>{{ $paiement->cite->name ?? 'N/A' }}</td>
                        <td>{{ $paiement->chambre?->numero_chambre ?? 'N/A' }}</td>
                        <td>{!! formatPaiementMois($paiement->mois) !!}</td>
                        <td>{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>
                        <td><span class="badge info">{{ $paiement->methode_label }}</span></td>
                        <td>
                            <span class="status-dot {{ $paiement->statut_paiement === 'valide' ? 'paid' : ($paiement->statut_paiement === 'en_attente' ? 'pending' : 'overdue') }}"></span>
                            <span class="badge {{ $paiement->statut_badge }}">{{ $paiement->statut_label }}</span>
                        </td>
                        <td>{{ $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y') : '--' }}</td>
                        <td>
                            @if($paiement->statut_paiement === 'en_attente')
                            @adminroute('admin.hebergement.paiements.valider')
                            <form action="{{ route('admin.hebergement.paiements.valider', $paiement->id_paiement) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn-action success" style="padding:2px 10px; font-size:var(--app-font-min, 15px);" title="Valider">✅</button>
                            </form>
                            @endadminroute
                            @adminroute('admin.hebergement.paiements.annuler')
                            <form action="{{ route('admin.hebergement.paiements.annuler', $paiement->id_paiement) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn-action danger" style="padding:2px 10px; font-size:var(--app-font-min, 15px);" title="Annuler">❌</button>
                            </form>
                            @endadminroute
                            @endif
                            @adminroute('admin.hebergement.paiements.show')
                            <a href="{{ route('admin.hebergement.paiements.show', $paiement->id_paiement) }}" class="btn-action primary" style="display: initial;
 font-size:var(--app-font-min, 15px);" title="Détails">📄</a>
@endadminroute
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align:center; padding:20px;">Aucun paiement trouvé</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
            <div class="table-footer">
                📊 Taux de recouvrement : <strong>{{ $tauxRecouvrement }}%</strong> · Impayés : <strong>{{ number_format($impayes, 0, ',', ' ') }} FCFA</strong> · 
                <span style="color:#b13e3e;">⚠️ {{ $nombreRetard }} paiements en retard</span>
            </div>
        </div>

        {{ $paiements->links() }}

        <!-- RÉCAPITULATIF PAR RÉSIDENCE -->
        <div class="section-header">
            <h2>📊 Récapitulatif par résidence</h2>
            <span class="section-badge">Mois en cours</span>
        </div>

        <div class="table-responsive">
            <table class="dashboard-table">
                <thead>
                <tr>
                    <th>Résidence</th>
                    <th>Total attendu</th>
                    <th>Total encaissé</th>
                    <th>Taux recouvrement</th>
                    <th>Impayés</th>
                    <th>Nbr résidents</th>
                    <th>Loyer moyen</th>
                </tr>
                </thead>
                <tbody>
                @foreach($statsParResidence as $stat)
                <tr class="{{ $stat['alerte'] ? 'row-warning' : '' }}" style="{{ $stat['alerte'] ? 'background:#fcf7f2;' : '' }}">
                    <td>
                        <strong>{{ $stat['residence'] }}</strong>
                        @if($stat['alerte'])
                            <span style="color:#b13e3e;">⚠️</span>
                        @else
                            <span style="color:#1a6e4f;">★</span>
                        @endif
                    </td>
                    <td>{{ $stat['total_attendu'] }}</td>
                    <td>{{ $stat['total_encaisse'] }}</td>
                    <td>
                        <span class="badge {{ $stat['taux_recouvrement'] >= 90 ? 'success' : 'warning' }}">
                            {{ $stat['taux_recouvrement'] }}%
                        </span>
                        <div class="progress-bar">
                            <div class="progress-fill {{ $stat['taux_recouvrement'] >= 90 ? 'success' : 'warning' }}" 
                                 style="width:{{ $stat['taux_recouvrement'] }}%"></div>
                        </div>
                    </td>
                    <td>{{ $stat['impayes'] }}</td>
                    <td>{{ $stat['nb_residents'] }}</td>
                    <td>{{ $stat['loyer_moyen'] }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
            <div class="table-footer">💡 <strong>Objectif recouvrement :</strong> >95%</div>
        </div>

    </div>

    <!-- ============================================================
         MODAL NOUVEAU PAIEMENT AVEC RECHERCHE DYNAMIQUE
    ============================================================ -->
    <div id="modalPaiement" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; overflow-y:auto;">
        <div style="background:#fff; max-width:600px; margin:40px auto; border-radius:16px; box-shadow:0 10px 40px rgba(0,0,0,0.2);">
            
            <!-- En-tête du modal -->
            <div style="display:flex; justify-content:space-between; align-items:center; padding:20px 24px; border-bottom:1px solid #eaeef4;">
                <h3 style="margin:0; font-size:18px; color:#0b2b4a;">💰 Nouveau paiement</h3>
                <button onclick="closeModalPaiement()" 
                        style="background:none; border:none; font-size:24px; cursor:pointer; color:#5b7b99;">&times;</button>
            </div>

            <!-- Corps du modal -->
            @adminroute('admin.hebergement.paiements.store')
            <form id="formPaiement" method="POST" action="{{ route('admin.hebergement.paiements.store') }}" style="padding:24px;">
                @csrf
                
                <!-- RECHERCHE RÉSIDENT -->
                <div style="margin-bottom:16px; position:relative;">
                    <label style="display:block; font-size:var(--app-font-min, 15px); font-weight:600; color:#1f4462; margin-bottom:6px;">
                        🔍 Rechercher un résident *
                    </label>
                    <input type="text" id="search_resident" 
                           placeholder="Tapez le nom, prénom, matricule ou numéro de téléphone..."
                           autocomplete="off"
                           style="width:100%; padding:10px 14px; border:1px solid #dae2ec; border-radius:8px; font-size:var(--app-font-min, 15px); background:#f8fafc;">
                    
                    <!-- Liste des résultats -->
                    <div id="resident_results" style="display:none;"></div>
                    
                    <input type="hidden" name="resident_id" id="selected_resident_id">
                    
                    <!-- Informations du résident sélectionné -->
                    <div id="resident_info" style="display:none;" class="resident-info-card">
                        <div class="info-grid">
                            <div><span class="label">👤 Nom :</span> <span class="value" id="info_nom">-</span></div>
                             <div><span class="label">📱 Téléphone :</span> <span class="value" id="info_telephone">-</span></div>
                            <div><span class="label">📋 Matricule :</span> <span class="value" id="info_matricule">-</span></div>
                            <div><span class="label">🏢 Résidence :</span> <span class="value" id="info_residence">-</span></div>
                            <div><span class="label">🏢 Batiment :</span> <span class="value" id="info_batiment">-</span></div>
                            <div><span class="label">🚪 Chambre :</span> <span class="value" id="info_chambre">-</span></div>
                            <div><span class="label">💰 Loyer mensuel :</span> <span class="value highlight" id="info_loyer">-</span></div>
                            <div><span class="label">📊 Statut :</span> <span class="value" id="info_statut">-</span></div>
                        </div>
                        <div id="arriere_container"></div>
                    </div>
                </div>

                <!-- Type de paiement -->
                <div style="margin-bottom:16px;">
                    <label style="display:block; font-size:var(--app-font-min, 15px); font-weight:600; color:#1f4462; margin-bottom:6px;">📋 Type de paiement *</label>
                    <select name="type_paiement" id="type_paiement" required
                            style="width:100%; padding:10px 14px; border:1px solid #dae2ec; border-radius:8px; font-size:var(--app-font-min, 15px); background:#f8fafc;">
                        <option value="loyer">Loyer</option>
                        <option value="caution">Caution</option>
                        <option value="penalite">Pénalité</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>

                <!-- Montant -->
                <div style="margin-bottom:16px;">
                    <label style="display:block; font-size:var(--app-font-min, 15px); font-weight:600; color:#1f4462; margin-bottom:6px;">💵 Montant (FCFA) *</label>
                    <input type="number" name="montant" id="montant" min="100" step="100" required
                           placeholder="Ex: 50000"
                           style="width:100%; padding:10px 14px; border:1px solid #dae2ec; border-radius:8px; font-size:var(--app-font-min, 15px); background:#f8fafc;">
                </div>

                <!-- Méthode de paiement -->
                <div style="margin-bottom:16px;">
                    <label style="display:block; font-size:var(--app-font-min, 15px); font-weight:600; color:#1f4462; margin-bottom:6px;">🏦 Méthode de paiement *</label>
                    <select name="methode_paiement" id="methode_paiement" required
                            style="width:100%; padding:10px 14px; border:1px solid #dae2ec; border-radius:8px; font-size:var(--app-font-min, 15px); background:#f8fafc;">
                        <option value="">Sélectionner</option>
                        <option value="orange_money">Orange Money</option>
                        <option value="mtn_money">MTN Money</option>
                        <option value="moov_money">Moov Money</option>
                        <option selected value="especes">Espèces</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>

                <!-- Notes -->
                <div style="margin-bottom:16px;">
                    <label style="display:block; font-size:var(--app-font-min, 15px); font-weight:600; color:#1f4462; margin-bottom:6px;">📝 Notes</label>
                    <textarea name="notes" id="notes" rows="3"
                              placeholder="Commentaires éventuels..."
                              style="width:100%; padding:10px 14px; border:1px solid #dae2ec; border-radius:8px; font-size:var(--app-font-min, 15px); background:#f8fafc; resize:vertical;"></textarea>
                </div>

                <!-- Champs cachés -->
                <input type="hidden" name="chambre_id" id="hidden_chambre_id">
                <input type="hidden" name="cite_id" id="hidden_cite_id">
                <input type="hidden" name="contrat_id" id="hidden_contrat_id">
                <input type="hidden" name="batiment_id" id="hidden_batiment_id">
                <input type="hidden" name="devise" value="FCFA">
                <input type="hidden" name="montant_du" id="hidden_montant_du" value="0">

                <!-- Boutons -->
                <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:20px; padding-top:16px; border-top:1px solid #eaeef4;">
                    <button type="button" onclick="closeModalPaiement()"
                            style="background:#f0f2f5; color:#1f4462; border:none; padding:10px 24px; border-radius:8px; font-weight:500; cursor:pointer;">
                        Annuler
                    </button>
                    <button type="submit"
                            style="background:#2a8d6c; color:#fff; border:none; padding:10px 24px; border-radius:8px; font-weight:600; cursor:pointer;">
                        💰 Enregistrer le paiement
                    </button>
                </div>
            </form>
            @endadminroute
        </div>
    </div>

    <!-- MODAL RELANCE IMPAYÉS -->
    <div id="modalRelance" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; overflow-y:auto;">
        <div style="background:#fff; max-width:500px; margin:40px auto; border-radius:16px; box-shadow:0 10px 40px rgba(0,0,0,0.2);">
            
            <!-- En-tête du modal -->
            <div style="display:flex; justify-content:space-between; align-items:center; padding:20px 24px; border-bottom:1px solid #eaeef4;">
                <h3 style="margin:0; font-size:18px; color:#0b2b4a;">📤 Relance des impayés</h3>
                <button onclick="closeModalRelance()" 
                        style="background:none; border:none; font-size:24px; cursor:pointer; color:#5b7b99;">&times;</button>
            </div>

            <!-- Corps du modal -->
            @adminroute('admin.hebergement.paiements.relances.envoyer')
            <form id="formRelance" method="POST" action="{{ route('admin.hebergement.paiements.relances.envoyer') }}" style="padding:24px;">
                @csrf
                
                <div style="background:#fcf7f2; padding:16px; border-radius:12px; margin-bottom:20px;">
                    <p style="margin:0; font-size:var(--app-font-min, 15px); color:#a8621a;">
                        <strong>⚠️ {{ $nombreRetard ?? 0 }} paiements en retard</strong><br>
                        Cette action enverra une notification de relance aux résidents concernés.
                    </p>
                </div>

                <!-- Type de relance -->
                <div style="margin-bottom:16px;">
                    <label style="display:block; font-size:var(--app-font-min, 15px); font-weight:600; color:#1f4462; margin-bottom:6px;">📨 Mode d'envoi</label>
                    <select name="mode_envoi" required
                            style="width:100%; padding:10px 14px; border:1px solid #dae2ec; border-radius:8px; font-size:var(--app-font-min, 15px); background:#f8fafc;">
                        <option value="sms">📱 SMS</option>
                        <option value="email">📧 Email</option>
                        <option value="both">📱 SMS + 📧 Email</option>
                    </select>
                </div>

                <!-- Message -->
                <div style="margin-bottom:16px;">
                    <label style="display:block; font-size:var(--app-font-min, 15px); font-weight:600; color:#1f4462; margin-bottom:6px;">💬 Message de relance</label>
                    <textarea name="message_relance" rows="4" required
                              style="width:100%; padding:10px 14px; border:1px solid #dae2ec; border-radius:8px; font-size:var(--app-font-min, 15px); background:#f8fafc; resize:vertical;">Bonjour {{ '{nom}' }},

Votre paiement de loyer de {{ '{montant}' }} FCFA pour le mois de {{ '{mois}' }} est en retard. Merci de régulariser votre situation dans les plus brefs délais.

Cordialement,
Le service hébergement</textarea>
                    <small style="color:#5b7b99;">
                        Variables disponibles : {'{nom}'}, {'{montant}'}, {'{mois}'}, {'{residence}'}, {'{chambre}'}
                    </small>
                </div>

                <!-- Boutons -->
                <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:20px; padding-top:16px; border-top:1px solid #eaeef4;">
                    <button type="button" onclick="closeModalRelance()"
                            style="background:#f0f2f5; color:#1f4462; border:none; padding:10px 24px; border-radius:8px; font-weight:500; cursor:pointer;">
                        Annuler
                    </button>
                    <button type="submit"
                            style="background:#d68a3a; color:#fff; border:none; padding:10px 24px; border-radius:8px; font-weight:600; cursor:pointer;">
                        📤 Envoyer les relances
                    </button>
                </div>
            </form>
            @endadminroute
        </div>
    </div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // GESTION DU MODAL PAIEMENT
    // ============================================
    const modalPaiement = document.getElementById('modalPaiement');
    const formPaiement = document.getElementById('formPaiement');
    const searchInput = document.getElementById('search_resident');
    const resultsDiv = document.getElementById('resident_results');
    const residentInfo = document.getElementById('resident_info');
    const selectedId = document.getElementById('selected_resident_id');
    const arriereContainer = document.getElementById('arriere_container');
    
    // Champs d'information
    const infoFields = {
        nom: document.getElementById('info_nom'),
        telephone: document.getElementById('info_telephone'),
        matricule: document.getElementById('info_matricule'),
        residence: document.getElementById('info_residence'),
        batiment: document.getElementById('info_batiment'),
        chambre: document.getElementById('info_chambre'),
        loyer: document.getElementById('info_loyer'),
        statut: document.getElementById('info_statut')
    };
    
    // Champs cachés
    const hiddenChambre = document.getElementById('hidden_chambre_id');
    const hiddenCite = document.getElementById('hidden_cite_id');
    const hiddenContrat = document.getElementById('hidden_contrat_id');
    const hiddenBatiment = document.getElementById('hidden_batiment_id');
    const hiddenMontantDu = document.getElementById('hidden_montant_du');
    
    // Fonctions d'ouverture/fermeture des modals (accessibles globalement)
    window.openModalPaiement = function() {
        modalPaiement.style.display = 'block';
        // Reset du formulaire
        searchInput.value = '';
        selectedId.value = '';
        residentInfo.style.display = 'none';
        resultsDiv.style.display = 'none';
        document.getElementById('montant').value = '';
        document.getElementById('montant').readOnly = false;
        arriereContainer.innerHTML = '';
        // Reset des champs cachés
        hiddenChambre.value = '';
        hiddenCite.value = '';
        hiddenContrat.value = '';
        hiddenBatiment.value = '';
        hiddenMontantDu.value = '0';
        // Focus sur la recherche
        setTimeout(() => searchInput.focus(), 100);
    };
    
    window.closeModalPaiement = function() {
        modalPaiement.style.display = 'none';
    };
    
    window.openModalRelance = function() {
        document.getElementById('modalRelance').style.display = 'block';
    };
    
    window.closeModalRelance = function() {
        document.getElementById('modalRelance').style.display = 'none';
    };
    
    // Fermer le modal en cliquant à l'extérieur
    modalPaiement.addEventListener('click', function(e) {
        if (e.target === modalPaiement) {
            closeModalPaiement();
        }
    });
    
    document.getElementById('modalRelance').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModalRelance();
        }
    });
    
    // Fermer avec la touche Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModalPaiement();
            closeModalRelance();
        }
    });
    
    // ============================================
    // RECHERCHE DYNAMIQUE DES RÉSIDENTS
    // ============================================
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length < 2) {
            resultsDiv.style.display = 'none';
            residentInfo.style.display = 'none';
            selectedId.value = '';
            return;
        }
        
        searchTimeout = setTimeout(() => {
            fetch(`/admin/gestion-hebergement/paiements/recherche-residents?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    resultsDiv.innerHTML = '';
                    
                    if (data.length === 0) {
                        resultsDiv.innerHTML = '<div style="padding:12px; color:#5b7b99; text-align:center;">Aucun résident trouvé</div>';
                        resultsDiv.style.display = 'block';
                        return;
                    }
                    
                    data.forEach((resident, index) => {
                        const div = document.createElement('div');
                        div.className = 'result-item';
                        div.style.cssText = `
                            padding: 10px 14px;
                            cursor: pointer;
                            border-bottom: ${index < data.length - 1 ? '1px solid #eaeef4' : 'none'};
                            transition: background 0.15s;
                        `;
                        
                        // Badge de statut
                        const statusBadge = resident.arriere && resident.arriere > 0 
                            ? '<span style="background:#fce1e1; color:#b13e3e; padding:1px 8px; border-radius:12px; font-size:var(--app-font-min, 15px); font-weight:600; margin-left:6px;">⚠️ Arriéré</span>'
                            : '<span style="background:#ddf0e8; color:#1a6e4f; padding:1px 8px; border-radius:12px; font-size:var(--app-font-min, 15px); font-weight:600; margin-left:6px;">✓ En règle</span>';
                        
                        div.innerHTML = `
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <strong>${resident.name}</strong>
                                    ${statusBadge}
                                    <span style="color:#5b7b99; font-size:var(--app-font-min, 15px); margin-left:8px;">📋 ${resident.matricule || '-'}</span>
                                    <span style="color:#5b7b99; font-size:var(--app-font-min, 15px); margin-left:8px;">📱 ${resident.telephone || '-'}</span>
                                </div>
                                <span style="font-weight:700; color:#1a6e4f; font-size:var(--app-font-min, 15px);">
                                    ${resident.loyer_mensuel ? Number(resident.loyer_mensuel).toLocaleString('fr-FR') + ' FCFA' : '-'}
                                </span>
                            </div>
                            <div style="font-size:var(--app-font-min, 15px); color:#1e7aa8; margin-top:2px;">
                                🏢 ${resident.residence || 'N/A'} - 🚪 ${resident.chambre || 'N/A'}
                            </div>
                            <div style="font-size:var(--app-font-min, 15px); color:#1e7aa8; margin-top:2px;">
                                🏢 ${resident.batiment || 'N/A'} - 🚪 ${resident.batiment || 'N/A'}
                            </div>
                        `;
                        
                        div.addEventListener('mouseenter', function() {
                            this.style.background = '#f0f6fb';
                        });
                        div.addEventListener('mouseleave', function() {
                            this.style.background = '';
                        });
                        
                        div.addEventListener('click', function() {
                            selectResident(resident);
                        });
                        
                        resultsDiv.appendChild(div);
                    });
                    
                    resultsDiv.style.display = 'block';
                })
                .catch(error => {
                    console.error('Erreur de recherche:', error);
                    resultsDiv.innerHTML = '<div style="padding:12px; color:#b13e3e; text-align:center;">❌ Erreur lors de la recherche</div>';
                    resultsDiv.style.display = 'block';
                });
        }, 300);
    });
    
    // Fonction pour sélectionner un résident
    function selectResident(resident) {
        // Remplir l'ID
        selectedId.value = resident.id_etudiant;
        searchInput.value = resident.name + ' (' + resident.matricule + ')';
        
        // Afficher les infos
        infoFields.nom.textContent = resident.name || '-';
        infoFields.telephone.textContent = resident.telephone || '-';
        infoFields.matricule.textContent = resident.matricule || '-';
        infoFields.residence.textContent = resident.residence || '-';
        infoFields.batiment.textContent = resident.batiment || '-';
        infoFields.chambre.textContent = resident.chambre || '-';
        infoFields.loyer.textContent = resident.loyer_mensuel ? Number(resident.loyer_mensuel).toLocaleString('fr-FR') + ' FCFA' : '-';
        infoFields.statut.textContent = resident.arriere && resident.arriere > 0 ? '⚠️ En retard' : '✅ En règle';
        infoFields.statut.className = 'value ' + (resident.arriere && resident.arriere > 0 ? 'danger' : '');
        
        // Afficher les arriérés
        arriereContainer.innerHTML = '';
        if (resident.arriere && resident.arriere > 0) {
            const arriereDiv = document.createElement('div');
            arriereDiv.className = 'arriere-message';
            arriereDiv.innerHTML = `
                ⚠️ <strong>Arriéré total :</strong> ${Number(resident.arriere).toLocaleString('fr-FR')} FCFA
                <span style="display:block; font-size:var(--app-font-min, 15px); margin-top:4px; color:#8a3a3a;">
                    💡 Montant recommandé : ${(resident.loyer_mensuel + resident.arriere).toLocaleString('fr-FR')} FCFA
                </span>
            `;
            arriereContainer.appendChild(arriereDiv);
            
            // Pré-remplir le montant avec loyer + arriéré
            document.getElementById('montant').value = resident.loyer_mensuel + resident.arriere;
        } else {
            // Pré-remplir le montant avec le loyer seul
            document.getElementById('montant').value = resident.loyer_mensuel || '';
        }
        
        // Remplir les champs cachés
        hiddenChambre.value = resident.chambre_id || '';
        hiddenCite.value = resident.residence_id || '';
        hiddenContrat.value = resident.contrat_id || '';
        hiddenBatiment.value = resident.batiment_id || '';
        hiddenMontantDu.value = resident.loyer_mensuel || 0;
        
        // Afficher la carte d'info
        residentInfo.style.display = 'block';
        resultsDiv.style.display = 'none';
    }
    
    // Fermer les résultats lors du clic à l'extérieur
    document.addEventListener('click', function(e) {
        const searchContainer = searchInput.parentElement;
        if (!searchContainer.contains(e.target)) {
            resultsDiv.style.display = 'none';
        }
    });
    
    // Gestion du focus pour la recherche
    searchInput.addEventListener('focus', function() {
        if (this.value.length >= 2) {
            this.dispatchEvent(new Event('input'));
        }
    });
    
    // ============================================
    // VALIDATION DU FORMULAIRE
    // ============================================
    if (formPaiement) {
        formPaiement.addEventListener('submit', function(e) {
            // Vérifier qu'un résident est sélectionné
            if (!selectedId.value) {
                e.preventDefault();
                alert('⚠️ Veuillez sélectionner un résident valide.');
                searchInput.focus();
                return;
            }
            
            // Vérifier le montant
            const montant = document.getElementById('montant').value;
            if (!montant || parseFloat(montant) < 100) {
                e.preventDefault();
                alert('⚠️ Veuillez saisir un montant valide (minimum 100 FCFA).');
                document.getElementById('montant').focus();
                return;
            }
            
            // Vérifier la méthode de paiement
            const methode = document.getElementById('methode_paiement').value;
            if (!methode) {
                e.preventDefault();
                alert('⚠️ Veuillez sélectionner une méthode de paiement.');
                document.getElementById('methode_paiement').focus();
                return;
            }
            
            // Confirmation
            const montantFormate = Number(montant).toLocaleString('fr-FR');
            if (!confirm(`Confirmer l'enregistrement du paiement de ${montantFormate} FCFA ?`)) {
                e.preventDefault();
                return;
            }
            
            // Désactiver le bouton
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ Enregistrement...';
        });
    }
    
    // ============================================
    // VALIDATION DU FORMULAIRE DE RELANCE
    // ============================================
    const formRelance = document.getElementById('formRelance');
    if (formRelance) {
        formRelance.addEventListener('submit', function(e) {
            if (!confirm('Confirmer l\'envoi des relances aux résidents en retard de paiement ?')) {
                e.preventDefault();
                return;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ Envoi en cours...';
        });
    }
    
    // ============================================
    // AFFICHAGE DES MESSAGES FLASH
    // ============================================
    @if(session('success'))
        showNotification('✅ {{ session('success') }}', 'success');
    @endif
    
    @if(session('error'))
        showNotification('❌ {{ session('error') }}', 'error');
    @endif
    
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 24px;
            border-radius: 12px;
            color: #fff;
            font-weight: 500;
            z-index: 9999;
            animation: slideIn 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            max-width: 400px;
        `;
        
        if (type === 'success') {
            notification.style.background = '#2a8d6c';
        } else {
            notification.style.background = '#b13e3e';
        }
        
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 4000);
    }
});
</script>

<style>
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
</style>
@endpush