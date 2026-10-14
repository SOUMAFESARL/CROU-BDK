{{-- resources/views/admin/hebergement/vie_residentielle/paiement/rapport-financier.blade.php --}}
@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'finances'
])

@section('title', 'Rapport financier')

@push('styles')
<style>
    .rapport-container {
        max-width: 1440px;
        margin: 0 auto;
        padding: 0 4px;
    }

    /* ============================================
       SUMMARY CARDS
    ============================================ */
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 28px;
    }

    .summary-card {
        background: #fff;
        padding: 20px;
        border-radius: 16px;
        box-shadow: 0 2px 10px rgba(0, 20, 40, 0.06);
        text-align: center;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .summary-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0, 20, 40, 0.10);
    }

    .summary-card .number {
        font-size: 25px;
        font-weight: 700;
        color: #0a2b44;
        margin: 8px 0 4px;
    }

    .summary-card .number.success { color: #1a6e4f; }
    .summary-card .number.danger { color: #b13e3e; }
    .summary-card .number.warning { color: #a8621a; }
    .summary-card .number.info { color: #1e5f8a; }
    .summary-card .number.purple { color: #6a4d8a; }

    .summary-card .label {
        font-size: 12px;
        font-weight: 600;
        color: #5b7b99;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .summary-card .sub {
        font-size: 12px;
        color: #5b7b99;
        margin-top: 4px;
    }

    /* ============================================
       FILTERS
    ============================================ */
    .filter-section {
        background: #fff;
        padding: 20px 24px;
        border-radius: 16px;
        margin-bottom: 28px;
        box-shadow: 0 2px 10px rgba(0, 20, 40, 0.06);
    }

    .filter-section .filter-row {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: flex-end;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .filter-group label {
        font-size: 12px;
        font-weight: 600;
        color: #1f4462;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .filter-group select,
    .filter-group input {
        padding: 8px 14px;
        border-radius: 8px;
        border: 1px solid #dae2ec;
        background: #f8fafc;
        font-size: 13px;
        color: #17324b;
        outline: none;
        min-width: 150px;
    }

    .filter-group select:focus,
    .filter-group input:focus {
        border-color: #1e7aa8;
        box-shadow: 0 0 0 3px rgba(30, 122, 168, 0.1);
    }

    .filter-actions {
        display: flex;
        gap: 10px;
        margin-left: auto;
    }

    .btn-generate {
        padding: 8px 28px;
        background: #1e7aa8;
        color: #fff;
        border: none;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: 0.2s;
    }

    .btn-generate:hover {
        background: #166a94;
    }

    /* ============================================
       CHARTS
    ============================================ */
    .chart-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 28px;
    }

    .chart-card {
        background: #fff;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0, 20, 40, 0.06);
    }

    .chart-card .chart-title {
        font-size: 14px;
        font-weight: 600;
        color: #1a3b57;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .chart-card .chart-title .badge {
        font-size: 10px;
        padding: 2px 10px;
    }

    .chart-card .chart-wrapper {
        height: 250px;
        position: relative;
    }

    .chart-card canvas {
        width: 100% !important;
        height: 100% !important;
    }

    /* ============================================
       TABLES
    ============================================ */
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
        font-size: 12px;
        font-weight: 500;
        color: #1f4c70;
    }

    .btn-action {
        padding: 6px 16px;
        border-radius: 30px;
        border: none;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
    }

    .btn-action.primary {
        background: #1e7aa8;
        color: #fff;
    }

    .btn-action.primary:hover {
        background: #166a94;
        color: #fff;
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
        font-size: 13px;
        min-width: 900px;
    }

    .dashboard-table thead th {
        text-align: left;
        padding: 12px 14px;
        background: #f2f6fb;
        color: #1f4462;
        font-weight: 600;
        font-size: 11px;
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

    .dashboard-table .row-warning {
        background: #fcf7f2;
    }

    .dashboard-table .highlight-row {
        background: #f0f6fb !important;
        font-weight: 600;
    }

    .dashboard-table .highlight-row td {
        border-top: 2px solid #dae2ec;
    }

    .badge {
        display: inline-block;
        padding: 2px 12px;
        border-radius: 30px;
        font-size: 11px;
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

    .table-footer {
        text-align: right;
        font-size: 13px;
        color: #5f7e9c;
        padding: 12px 14px;
        background: #f8fafc;
        border-radius: 0 0 16px 16px;
        border-top: 1px solid #eaeef4;
    }

    .mode-color-dot {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin-right: 8px;
        vertical-align: middle;
    }

    /* ============================================
       PRINT
    ============================================ */
    @media print {
        .no-print {
            display: none !important;
        }
        .filter-section {
            display: none !important;
        }
        .summary-card {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
        }
        .chart-card {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
        }
        .table-responsive {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
        }
    }

    /* ============================================
       RESPONSIVE
    ============================================ */
    @media (max-width: 768px) {
        .chart-section {
            grid-template-columns: 1fr;
        }
        .filter-section .filter-row {
            flex-direction: column;
            align-items: stretch;
        }
        .filter-group select,
        .filter-group input {
            min-width: 100%;
        }
        .filter-actions {
            margin-left: 0;
            flex-wrap: wrap;
        }
        .summary-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 480px) {
        .summary-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')

<x-breadcrumbs
    title="Rapport financier"
    :items="[
        'Gestion Hébergement' => route('admin.hebergement.index'),
        'Vie résidentielle' => route('admin.hebergement.vie_residentielle.index'),
        'Paiements' => route('admin.hebergement.paiements.index'),
        'Rapport financier' => null,
    ]"
/>

<div class="rapport-container">

    <!-- ============================================
         SECTION 1 : RÉSUMÉ DES INDICATEURS
    ============================================ -->
    <div class="summary-grid">
        <div class="summary-card">
            <div class="label">💰 Total encaissé</div>
            <div class="number success">{{ number_format($totalEncaisse ?? 0, 0, ',', ' ') }} FCFA</div>
            <div class="sub">{{ $nbPaiements ?? 0 }} paiements</div>
        </div>
        <div class="summary-card">
            <div class="label">📊 Total attendu</div>
            <div class="number info">{{ number_format($totalAttendu ?? 0, 0, ',', ' ') }} FCFA</div>
            <div class="sub">{{ $nbLoyers ?? 0 }} loyers</div>
        </div>
        <div class="summary-card">
            <div class="label">⚠️ Impayés</div>
            <div class="number danger">{{ number_format($totalImpayes ?? 0, 0, ',', ' ') }} FCFA</div>
            <div class="sub">{{ $nbImpayes ?? 0 }} loyers impayés</div>
        </div>
        <div class="summary-card">
            <div class="label">📈 Taux de recouvrement</div>
            <div class="number purple">{{ $tauxRecouvrement ?? 0 }}%</div>
            <div class="sub">Objectif > 95%</div>
        </div>
        <div class="summary-card">
            <div class="label">⏰ Retards</div>
            <div class="number warning">{{ number_format($totalRetard ?? 0, 0, ',', ' ') }} FCFA</div>
            <div class="sub">{{ $nbRetard ?? 0 }} loyers en retard</div>
        </div>
        <div class="summary-card">
            <div class="label">📅 Pénalités</div>
            <div class="number danger">{{ number_format($totalPenalites ?? 0, 0, ',', ' ') }} FCFA</div>
            <div class="sub">Retards accumulés</div>
        </div>
    </div>

    <!-- ============================================
         SECTION 2 : FILTRES
    ============================================ -->
    <div class="filter-section no-print">
        @adminroute('admin.hebergement.paiements.rapport_financier')
        <form method="GET" action="{{ route('admin.hebergement.paiements.rapport_financier') }}" class="filter-row">
            <div class="filter-group">
                <label>📅 Année</label>
                <select name="annee">
                    @for($i = date('Y'); $i >= date('Y') - 5; $i--)
                        <option value="{{ $i }}" {{ request('annee', date('Y')) == $i ? 'selected' : '' }}>
                            {{ $i }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="filter-group">
                <label>📆 Mois</label>
                <select name="mois">
                    <option value="">Tous les mois</option>
                    @foreach(['01' => 'Janvier', '02' => 'Février', '03' => 'Mars', '04' => 'Avril', '05' => 'Mai', '06' => 'Juin', '07' => 'Juillet', '08' => 'Août', '09' => 'Septembre', '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'] as $key => $label)
                        <option value="{{ $key }}" {{ request('mois') == $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
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
            <div class="filter-actions">
                <button type="submit" class="btn-generate">📊 Générer</button>
            </div>
        </form>
        @endadminroute
    </div>

    <!-- ============================================
         SECTION 3 : GRAPHIQUES PRINCIPAUX
    ============================================ -->
    <div class="chart-section">
        <div class="chart-card">
            <div class="chart-title">
                📈 Évolution des paiements
                <span class="badge info">{{ $annee ?? date('Y') }}</span>
            </div>
            <div class="chart-wrapper">
                <canvas id="evolutionChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <div class="chart-title">📊 Répartition des paiements</div>
            <div class="chart-wrapper">
                <canvas id="repartitionChart"></canvas>
            </div>
        </div>
    </div>

    <!-- ============================================
         SECTION 4 : STATISTIQUES PAR MODE DE PAIEMENT
    ============================================ -->
    <div class="section-header">
        <h2>🏦 Répartition par mode de paiement</h2>
        <span class="section-badge">{{ count($statsParMode ?? []) }} modes</span>
    </div>

    <div class="table-responsive">
        <table class="dashboard-table">
            <thead>
                <tr>
                    <th>Mode de paiement</th>
                    <th>Nombre de paiements</th>
                    <th>Montant total</th>
                    <th>Montant moyen</th>
                    <th>Pourcentage</th>
                </tr>
            </thead>
            <tbody>
                @php $totalPaiements = 0; @endphp
                @forelse($statsParMode ?? [] as $mode)
                    @php $totalPaiements += $mode['nombre']; @endphp
                    <tr>
                        <td>
                            <span class="mode-color-dot" style="background:{{ $mode['couleur'] }};"></span>
                            <strong>{{ $mode['libelle'] }}</strong>
                        </td>
                        <td>{{ $mode['nombre'] }}</td>
                        <td><strong>{{ $mode['total_formate'] }}</strong></td>
                        <td>{{ $mode['moyenne_formate'] }}</td>
                        <td>
                            <span class="badge {{ $mode['pourcentage'] >= 30 ? 'success' : 'info' }}">
                                {{ $mode['pourcentage'] }}%
                            </span>
                            <div class="progress-bar">
                                <div class="progress-fill {{ $mode['pourcentage'] >= 30 ? 'success' : 'info' }}" 
                                     style="width:{{ $mode['pourcentage'] }}%"></div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center; padding:20px; color:#5b7b99;">
                            Aucune donnée disponible pour la période sélectionnée
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if(!empty($statsParMode))
            <tfoot>
                <tr class="highlight-row">
                    <td><strong>TOTAL</strong></td>
                    <td><strong>{{ $totalPaiements }}</strong></td>
                    <td><strong>{{ number_format(array_sum(array_column($statsParMode, 'total')), 0, ',', ' ') }} FCFA</strong></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>

    <!-- Graphiques des modes de paiement -->
    <div class="chart-section">
        <div class="chart-card">
            <div class="chart-title">📊 Répartition par mode de paiement</div>
            <div class="chart-wrapper" style="height:300px;">
                <canvas id="modeChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <div class="chart-title">📈 Évolution par mode de paiement</div>
            <div class="chart-wrapper" style="height:300px;">
                <canvas id="modeEvolutionChart"></canvas>
            </div>
        </div>
    </div>

    <!-- ============================================
         SECTION 5 : STATISTIQUES PAR RÉSIDENCE
    ============================================ -->
    <div class="section-header">
        <h2>
            📊 Détail par résidence
            <span class="section-badge">{{ count($statsParResidence ?? []) }} résidences</span>
        </h2>
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
                    <th>Retards</th>
                </tr>
            </thead>
            <tbody>
                @php 
                    $totalAttenduAll = 0; 
                    $totalEncaisseAll = 0; 
                    $totalImpayesAll = 0; 
                @endphp
                @forelse($statsParResidence as $stat)
                    @php
                        $totalAttendu = $stat['total_attendu'] ?? 0;
                        $totalEncaisse = $stat['total_encaisse'] ?? 0;
                        $totalImpayes = $stat['total_impayes'] ?? 0;
                        $tauxRecouvrement = $stat['taux_recouvrement'] ?? 0;
                        $nbResidents = $stat['nb_residents'] ?? 0;
                        $loyerMoyen = $stat['loyer_moyen'] ?? 0;
                        $nbEnRetard = $stat['nb_en_retard'] ?? 0;
                        $alerte = $stat['alerte'] ?? false;
                        
                        $totalAttenduAll += $totalAttendu;
                        $totalEncaisseAll += $totalEncaisse;
                        $totalImpayesAll += $totalImpayes;
                    @endphp
                    <tr class="{{ $alerte ? 'row-warning' : '' }}">
                        <td>
                            <strong>{{ $stat['residence'] ?? 'N/A' }}</strong>
                            @if($alerte)
                                <span style="color:#b13e3e;">⚠️</span>
                            @else
                                <span style="color:#1a6e4f;">★</span>
                            @endif
                        </td>
                        <td>{{ number_format($totalAttendu, 0, ',', ' ') }} FCFA</td>
                        <td>{{ number_format($totalEncaisse, 0, ',', ' ') }} FCFA</td>
                        <td>
                            <span class="badge {{ $tauxRecouvrement >= 90 ? 'success' : 'warning' }}">
                                {{ $tauxRecouvrement }}%
                            </span>
                            <div class="progress-bar">
                                <div class="progress-fill {{ $tauxRecouvrement >= 90 ? 'success' : 'warning' }}" 
                                     style="width:{{ min($tauxRecouvrement, 100) }}%"></div>
                            </div>
                        </td>
                        <td>{{ number_format($totalImpayes, 0, ',', ' ') }} FCFA</td>
                        <td>{{ $nbResidents }}</td>
                        <td>{{ number_format($loyerMoyen, 0, ',', ' ') }} FCFA</td>
                        <td>
                            <span class="badge {{ $nbEnRetard > 0 ? 'danger' : 'success' }}">
                                {{ $nbEnRetard }} retard(s)
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center; padding:20px; color:#5b7b99;">
                            Aucune donnée disponible pour la période sélectionnée
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if(!empty($statsParResidence))
            <tfoot>
                <tr class="highlight-row">
                    <td><strong>TOTAL</strong></td>
                    <td><strong>{{ number_format($totalAttenduAll, 0, ',', ' ') }} FCFA</strong></td>
                    <td><strong>{{ number_format($totalEncaisseAll, 0, ',', ' ') }} FCFA</strong></td>
                    <td>
                        @php $tauxGlobal = $totalAttenduAll > 0 ? round(($totalEncaisseAll / $totalAttenduAll) * 100, 1) : 0; @endphp
                        <span class="badge {{ $tauxGlobal >= 90 ? 'success' : 'warning' }}">
                            {{ $tauxGlobal }}%
                        </span>
                    </td>
                    <td><strong>{{ number_format($totalImpayesAll, 0, ',', ' ') }} FCFA</strong></td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>

    <!-- ============================================
         SECTION 6 : DERNIERS PAIEMENTS
    ============================================ -->
    <div class="section-header" style="margin-top:32px;">
        <h2>📋 Derniers paiements</h2>
        @adminroute('admin.hebergement.paiements.index')
        <a href="{{ route('admin.hebergement.paiements.index') }}" class="btn-action primary">
            Voir tous
        </a>
        @endadminroute
    </div>

    <div class="table-responsive">
        <table class="dashboard-table">
            <thead>
                <tr>
                    <th>Numéro</th>
                    <th>Résident</th>
                    <th>Montant</th>
                    <th>Méthode</th>
                    <th>Statut</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($derniersPaiements ?? [] as $paiement)
                    <tr>
                        <td><strong>{{ $paiement['numero'] ?? $paiement->numero_paiement ?? 'N/A' }}</strong></td>
                        <td>{{ $paiement['resident'] ?? $paiement->resident?->name ?? 'N/A' }}</td>
                        <td><strong>{{ $paiement['montant_formate'] ?? number_format($paiement->montant ?? 0, 0, ',', ' ') . ' FCFA' }}</strong></td>
                        <td><span class="badge info">{{ $paiement['methode'] ?? $paiement->methode_label ?? 'N/A' }}</span></td>
                        <td>
                            @php
                                $statut = $paiement['statut'] ?? $paiement->statut_label ?? 'N/A';
                                $isPaid = $statut === 'Validé' || ($paiement->statut_paiement ?? '') === 'valide';
                                $badgeClass = $paiement['statut_badge'] ?? $paiement->statut_badge ?? 'warning';
                            @endphp
                            <span class="status-dot {{ $isPaid ? 'paid' : 'pending' }}"></span>
                            <span class="badge {{ $badgeClass }}">
                                {{ $statut }}
                            </span>
                        </td>
                        <td>{{ $paiement['date'] ?? ($paiement->created_at ? $paiement->created_at->format('d/m/Y H:i') : '--') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:20px; color:#5b7b99;">
                            Aucun paiement récent
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- ============================================
         FOOTER
    ============================================ -->
    <div class="table-footer">
        💡 <strong>Objectif recouvrement :</strong> >95% · 
        <span style="color:#b13e3e;">⚠️ {{ $nbRetard ?? 0 }} loyers en retard</span> · 
        📅 Période : {{ now()->format('F Y') }}
    </div>

</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Définition des couleurs par défaut
    const defaultColors = ['#1e7aa8', '#2a8d6c', '#d68a3a', '#6a4d8a', '#b13e3e', '#5b7b99', '#f47b20'];

    // ============================================
    // GRAPHIQUE 1 : Évolution des paiements
    // ============================================
    const ctx1 = document.getElementById('evolutionChart');
    if (ctx1) {
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: {!! json_encode($evolutionLabels ?? ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc']) !!},
                datasets: [
                    {
                        label: 'Montants encaissés',
                        data: {!! json_encode($evolutionData ?? array_fill(0, 12, 0)) !!},
                        borderColor: '#1e7aa8',
                        backgroundColor: 'rgba(30, 122, 168, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#1e7aa8',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4
                    },
                    {
                        label: 'Montants attendus',
                        data: {!! json_encode($evolutionAttendus ?? array_fill(0, 12, 0)) !!},
                        borderColor: '#b13e3e',
                        backgroundColor: 'rgba(177, 62, 62, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderDash: [5, 5],
                        pointBackgroundColor: '#b13e3e',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { usePointStyle: true, padding: 20 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y.toLocaleString('fr-FR') + ' FCFA';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M FCFA';
                                if (value >= 1000) return (value / 1000).toFixed(1) + 'k FCFA';
                                return value.toLocaleString('fr-FR') + ' FCFA';
                            }
                        }
                    }
                }
            }
        });
    }

    // ============================================
    // GRAPHIQUE 2 : Répartition des paiements
    // ============================================
    const ctx2 = document.getElementById('repartitionChart');
    if (ctx2) {
        const repartitionData = {!! json_encode($repartitionData ?? [0, 0, 0]) !!};
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Payé', 'Impayé', 'En retard'],
                datasets: [{
                    data: repartitionData,
                    backgroundColor: ['#2a8d6c', '#b13e3e', '#d68a3a'],
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { usePointStyle: true, padding: 20 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                return context.label + ': ' + context.parsed.toLocaleString('fr-FR') + ' FCFA (' + percentage + '%)';
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }

    // ============================================
    // GRAPHIQUE 3 : Répartition par mode de paiement
    // ============================================
    const ctx3 = document.getElementById('modeChart');
    if (ctx3) {
        const modeData = {!! json_encode($modeData ?? []) !!};
        const modeLabels = {!! json_encode($modeLabels ?? []) !!};
        const modeColors = {!! json_encode(array_values($modeColors ?? [])) !!};
        
        new Chart(ctx3, {
            type: 'doughnut',
            data: {
                labels: modeLabels,
                datasets: [{
                    data: modeData,
                    backgroundColor: modeColors.length > 0 ? modeColors : defaultColors,
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { usePointStyle: true, padding: 15 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                return context.label + ': ' + context.parsed.toLocaleString('fr-FR') + ' FCFA (' + percentage + '%)';
                            }
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }

    // ============================================
    // GRAPHIQUE 4 : Évolution par mode de paiement
    // ============================================
    const ctx4 = document.getElementById('modeEvolutionChart');
    if (ctx4) {
        const evolutionModeData = {!! json_encode($evolutionModeData ?? []) !!};
        const modeLabels = {!! json_encode($modeLabels ?? []) !!};
        const modeColors = {!! json_encode(array_values($modeColors ?? [])) !!};
        const labels = {!! json_encode($evolutionLabels ?? ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc']) !!};
        
        const datasets = modeLabels.map((label, index) => ({
            label: label,
            data: evolutionModeData[index] ?? Array(12).fill(0),
            borderColor: modeColors[index] || defaultColors[index % defaultColors.length],
            backgroundColor: 'rgba(0,0,0,0)',
            tension: 0.4,
            pointBackgroundColor: modeColors[index] || defaultColors[index % defaultColors.length],
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 3
        }));
        
        new Chart(ctx4, {
            type: 'line',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { usePointStyle: true, padding: 15 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y.toLocaleString('fr-FR') + ' FCFA';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M FCFA';
                                if (value >= 1000) return (value / 1000).toFixed(1) + 'k FCFA';
                                return value.toLocaleString('fr-FR') + ' FCFA';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush