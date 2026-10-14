


@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'reporting'
])

@section('title', 'Reporting Hébergement')

@section('content')
<div class="report-shell">
    <x-breadcrumbs
        title="Reporting & Statistiques"
        :items="['Gestion Hébergement' => route('admin.hebergement.index')]"
    />

    <div class="report-heading">
        <div>
            <span class="eyebrow">Pilotage opérationnel</span>
            <h1>Tableau de bord stratégique des résidences</h1>
            <p>Performance globale des résidences : capacité, attribution, recouvrement et qualité de service.</p>
        </div>
        @adminroute('admin.hebergement.reporting.index')
        <form method="GET" action="{{ route('admin.hebergement.reporting.index') }}" class="report-filters">
            <label>
                <span>Période</span>
                <select name="periode">
                    <option value="week" @selected($periode === 'week')>Cette semaine</option>
                    <option value="month" @selected($periode === 'month')>Ce mois</option>
                    <option value="quarter" @selected($periode === 'quarter')>Ce trimestre</option>
                    <option value="year" @selected($periode === 'year')>Cette année</option>
                </select>
            </label>
            <label>
                <span>Cité</span>
                <select name="cite">
                    <option value="">Toutes les cités</option>
                    @foreach($cites as $cite)
                        <option value="{{ $cite->id_cite }}" @selected($citeId === $cite->id_cite)>{{ $cite->name }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" style="margin-bottom: 6px;"><i class="fa-solid fa-filter"></i> Appliquer</button>
        </form>
        @endadminroute
    </div>

    <div class="date-context"><span></span>{{ $debut->translatedFormat('d M Y') }} — {{ $fin->translatedFormat('d M Y') }}</div>

    <nav class="report-nav nav nav-pills" aria-label="Sections des statistiques">
        <button type="button" class="nav-link active" data-report-target="strategy" aria-selected="true"><i class="fa-solid fa-gauge-high"></i> Vue stratégique</button>
        <button type="button" class="nav-link" data-report-target="admissions" aria-selected="false"><i class="fa-solid fa-file-circle-check"></i> Admissions & attributions</button>
        <button type="button" class="nav-link" data-report-target="trends" aria-selected="false"><i class="fa-solid fa-chart-line"></i> Tendances</button>
        <button type="button" class="nav-link" data-report-target="assets" aria-selected="false"><i class="fa-solid fa-building-circle-check"></i> État du parc</button>
        <button type="button" class="nav-link" data-report-target="discipline" aria-selected="false"><i class="fa-solid fa-scale-balanced"></i> Discipline</button>
        <button type="button" class="nav-link" data-report-target="residences" aria-selected="false"><i class="fa-solid fa-city"></i> Comparatif des cités</button>
    </nav>

    <div class="kpi-grid report-tab-section" data-report-section="strategy">
        <article class="kpi blue"><span><i class="fa-solid fa-bed"></i></span><div><small>Taux d’occupation global</small><strong>{{ $kpis['occupancy_rate'] }}%</strong><p>{{ $kpis['occupied'] }} places occupées sur {{ $kpis['capacity'] }}</p></div></article>
        <article class="kpi purple"><span><i class="fa-solid fa-key"></i></span><div><small>Taux d’attribution</small><strong>{{ $kpis['attribution_rate'] }}%</strong><p>{{ $kpis['confirmed_assignments'] }} attribution(s) / {{ $admissionStats['eligible'] }} dossier(s) éligible(s)</p></div></article>
        <article class="kpi amber"><span><i class="fa-solid fa-file-invoice-dollar"></i></span><div><small>Taux d’impayés</small><strong>{{ $kpis['unpaid_rate'] }}%</strong><p>{{ number_format($kpis['unpaid_amount'], 0, ',', ' ') }} F restant à recouvrer</p></div></article>
        <article class="kpi green"><span><i class="fa-solid fa-wallet"></i></span><div><small>Recettes locatives</small><strong>{{ number_format($kpis['revenue'], 0, ',', ' ') }} F</strong><p>Encaissements validés sur la période</p></div></article>
        <article class="kpi red"><span><i class="fa-solid fa-screwdriver-wrench"></i></span><div><small>Incidents techniques</small><strong>{{ number_format($kpis['technical_incidents'], 0, ',', ' ') }}</strong><p>{{ $kpis['open_work_orders'] }} intervention(s) encore ouverte(s)</p></div></article>
        <article class="kpi rose"><span><i class="fa-solid fa-scale-balanced"></i></span><div><small>Incidents disciplinaires</small><strong>{{ number_format($kpis['disciplinary_incidents'], 0, ',', ' ') }}</strong><p>Signalements classés discipline ou comportement</p></div></article>
        <article class="kpi orange"><span><i class="fa-solid fa-person-walking-arrow-right"></i></span><div><small>Départs anticipés</small><strong>{{ number_format($kpis['early_departures'], 0, ',', ' ') }}</strong><p>Affectations annulées sur la période</p></div></article>
        <article class="kpi slate"><span><i class="fa-solid fa-door-closed"></i></span><div><small>Chambres hors service</small><strong>{{ number_format($kpis['out_of_service_rooms'], 0, ',', ' ') }}</strong><p>Sur {{ $kpis['chambres'] }} chambre(s) analysée(s)</p></div></article>
    </div>

    <section class="panel admission-panel report-tab-section" data-report-section="admissions" hidden>
        <div class="panel-title"><div><span>Qualité des admissions</span><h2>KPI des dossiers reçus</h2></div>@adminroute('admin.hebergement.campagnes_admission.admission')
<a href="{{ route('admin.hebergement.campagnes_admission.admission') }}">Voir les dossiers <i class="feather icon-arrow-right"></i></a>
@endadminroute</div>
        <div class="admission-kpis">
            <article><i class="fa-solid fa-folder-open blue"></i><div><small>Dossiers reçus</small><strong>{{ number_format($admissionStats['total'], 0, ',', ' ') }}</strong><p>sur la période sélectionnée</p></div></article>
            <article><i class="fa-solid fa-folder-tree green"></i><div><small>Dossiers complets</small><strong>{{ $admissionStats['complete_rate'] }}%</strong><p>{{ $admissionStats['complete'] }} dossier(s)</p></div></article>
            <article><i class="fa-solid fa-user-check purple"></i><div><small>Dossiers éligibles</small><strong>{{ $admissionStats['eligible_rate'] }}%</strong><p>{{ $admissionStats['eligible'] }} dossier(s) accepté(s)</p></div></article>
            <article><i class="fa-solid fa-folder-minus amber"></i><div><small>Dossiers incomplets</small><strong>{{ $admissionStats['incomplete_rate'] }}%</strong><p>{{ $admissionStats['incomplete'] }} dossier(s)</p></div></article>
        </div>
        <div class="admission-charts">
            <article><h3>Répartition par sexe</h3><div id="admissionGenderChart" class="chart admission-chart"></div></article>
            <article><h3>Répartition par niveau</h3><div id="admissionLevelChart" class="chart admission-chart"></div></article>
            <article><h3>Répartition par filière</h3><div id="admissionProgramChart" class="chart admission-chart"></div></article>
            <article><h3>Statut de bourse</h3><div id="admissionScholarshipChart" class="chart admission-chart"></div></article>
        </div>
    </section>

    <section class="panel allocation-kpi-panel report-tab-section" data-report-section="admissions" hidden>
        <div class="panel-title"><div><span>Performance des attributions</span><h2>Satisfaction de la demande de logement</h2></div>@adminroute('admin.hebergement.campagnes_admission.admission')
<a href="{{ route('admin.hebergement.campagnes_admission.admission') }}">Gérer les attributions <i class="feather icon-arrow-right"></i></a>
@endadminroute</div>
        <div class="allocation-kpis">
            <article class="blue"><div class="allocation-icon"><i class="fa-solid fa-key"></i></div><div class="allocation-value"><small>Taux d’attribution</small><strong>{{ $admissionStats['attribution_rate'] }}%</strong><p>{{ $admissionStats['attributed'] }} chambre(s) attribuée(s) pour {{ $admissionStats['eligible'] }} dossier(s) éligible(s)</p></div></article>
            <article class="green"><div class="allocation-icon"><i class="fa-solid fa-face-smile"></i></div><div class="allocation-value"><small>Satisfaction de la demande</small><strong>{{ $admissionStats['demand_satisfaction_rate'] }}%</strong><p>{{ $admissionStats['attributed'] }} demande(s) satisfaite(s) sur {{ $admissionStats['total'] }} reçue(s)</p></div></article>
            <article class="red"><div class="allocation-icon"><i class="fa-solid fa-user-xmark"></i></div><div class="allocation-value"><small>Taux de refus étudiant</small><strong>{{ $admissionStats['refusal_rate'] }}%</strong><p>{{ $admissionStats['refused'] }} dossier(s) refusé(s) sur {{ $admissionStats['total'] }} reçu(s)</p></div></article>
        </div>
    </section>

    <div class="mini-grid report-tab-section" data-report-section="strategy">
        <div><i class="fa-solid fa-city"></i><span><small>Parc</small><strong>{{ $kpis['cites'] }} cité(s) · {{ $kpis['batiments'] }} bâtiment(s)</strong></span></div>
        <div><i class="fa-solid fa-door-open"></i><span><small>Disponibilité</small><strong>{{ $kpis['available'] }} place(s) · {{ $kpis['chambres'] }} chambre(s)</strong></span></div>
        <div><i class="fa-solid fa-circle-check"></i><span><small>Admissions</small><strong>{{ $kpis['accepted_requests'] }} acceptée(s) · {{ $kpis['rejected_requests'] }} refusée(s)</strong></span></div>
        <div><i class="fa-solid fa-boxes-stacked"></i><span><small>Équipements</small><strong>{{ number_format($kpis['inventory_items'], 0, ',', ' ') }} unités · {{ $kpis['low_stock'] }} alerte(s)</strong></span></div>
        <div><i class="fa-solid fa-clipboard-check"></i><span><small>États des lieux</small><strong>{{ $kpis['entry_inspections'] }} entrée(s) · {{ $kpis['exit_inspections'] }} sortie(s)</strong></span></div>
        <div><i class="fa-solid fa-coins"></i><span><small>Coût maintenance</small><strong>{{ number_format($kpis['maintenance_cost'], 0, ',', ' ') }} F CFA</strong></span></div>
    </div>

    <div class="report-grid main-grid report-tab-section" data-report-section="strategy">
        <section class="panel activity-panel">
            <div class="panel-title"><div><span>Évolution</span><h2>Activité de la période</h2></div></div>
            <div id="reportActivityChart" class="chart large-chart"></div>
        </section>
        <section class="panel occupancy-panel">
            <div class="panel-title"><div><span>Capacité</span><h2>Occupation des places</h2><small>{{ $kpis['chambres'] }} chambres représentent {{ $kpis['capacity'] }} places : {{ $kpis['single_rooms'] }} individuelle(s) et {{ $kpis['shared_rooms'] }} à plusieurs places.</small></div></div>
            <div id="reportOccupancyChart" class="chart donut-chart"></div>
            <div class="occupancy-legend">
                <div><i class="occupied"></i><span><strong>{{ $kpis['occupied'] }} place(s)</strong><small>dans {{ $kpis['occupied_rooms'] }} chambre(s) occupée(s)</small></span></div>
                <div><i class="available"></i><span><strong>{{ $kpis['available'] }}</strong><small>Disponibles</small></span></div>
            </div>
        </section>
    </div>

    <div class="report-grid split-grid report-tab-section" data-report-section="strategy">
        <section class="panel">
            <div class="panel-title"><div><span>Admissions</span><h2>Statut des demandes</h2></div>@adminroute('admin.hebergement.campagnes_admission.admission')
<a href="{{ route('admin.hebergement.campagnes_admission.admission') }}">Voir les dossiers <i class="feather icon-arrow-right"></i></a>
@endadminroute</div>
            <div id="reportRequestsChart" class="chart bar-chart"></div>
        </section>
        <section class="panel">
            <div class="panel-title"><div><span>Finances</span><h2>Statut des paiements</h2></div>@adminroute('admin.hebergement.paiements.index')
<a href="{{ route('admin.hebergement.paiements.index') }}">Voir les paiements <i class="feather icon-arrow-right"></i></a>
@endadminroute</div>
            <div id="reportPaymentsChart" class="chart bar-chart"></div>
        </section>
    </div>

    <section class="panel monthly-panel report-tab-section" data-report-section="trends" hidden>
        <div class="panel-title"><div><span>Anticipation</span><h2>Tendances mensuelles — 12 derniers mois</h2></div></div>
        <p class="panel-description">Évolution de la capacité occupée, du recouvrement et des incidents{{ $citeId ? ' pour la cité sélectionnée' : '' }}.</p>
        <div class="monthly-grid">
            <article class="monthly-card"><div class="monthly-title"><i class="fa-solid fa-bed blue"></i><div><strong>Évolution de l’occupation</strong><small>Taux d’occupation mensuel</small></div></div><div id="monthlyOccupancyChart" class="chart monthly-chart"></div></article>
            <article class="monthly-card"><div class="monthly-title"><i class="fa-solid fa-coins amber"></i><div><strong>Évolution des impayés</strong><small>Solde restant à recouvrer</small></div></div><div id="monthlyUnpaidChart" class="chart monthly-chart"></div></article>
            <article class="monthly-card"><div class="monthly-title"><i class="fa-solid fa-triangle-exclamation red"></i><div><strong>Évolution des incidents</strong><small>Nouveaux incidents signalés</small></div></div><div id="monthlyIncidentsChart" class="chart monthly-chart"></div></article>
        </div>
    </section>

    <section class="panel assets-panel report-tab-section" data-report-section="assets" hidden>
        <div class="panel-title"><div><span>Patrimoine</span><h2>État opérationnel du parc</h2></div>@adminroute('admin.hebergement.parc-immobilier.index')
<a href="{{ route('admin.hebergement.parc-immobilier.index') }}">Gérer le parc <i class="feather icon-arrow-right"></i></a>
@endadminroute</div>
        <p class="panel-description">Indicateurs calculés sur {{ $kpis['chambres'] }} chambre(s){{ $citeId ? ' de la cité sélectionnée' : ' de l’ensemble des résidences' }}.</p>
        <div class="assets-kpis">
            <article class="amber">
                <div class="asset-kpi-head"><span><i class="fa-solid fa-screwdriver-wrench"></i></span><small>Chambres en maintenance</small></div>
                <strong>{{ $kpis['maintenance_room_rate'] }}%</strong>
                <div class="asset-progress"><span style="width:{{ min(100, $kpis['maintenance_room_rate']) }}%"></span></div>
                <p>{{ $kpis['maintenance_rooms'] }} chambre(s) nécessitent une intervention</p>
            </article>
            <article class="red">
                <div class="asset-kpi-head"><span><i class="fa-solid fa-ban"></i></span><small>Chambres hors service</small></div>
                <strong>{{ $kpis['out_of_service_room_rate'] }}%</strong>
                <div class="asset-progress"><span style="width:{{ min(100, $kpis['out_of_service_room_rate']) }}%"></span></div>
                <p>{{ $kpis['out_of_service_rooms'] }} chambre(s) indisponibles</p>
            </article>
            <article class="blue">
                <div class="asset-kpi-head"><span><i class="fa-solid fa-arrows-rotate"></i></span><small>Rotation des chambres</small></div>
                <strong>{{ $kpis['room_rotation_rate'] }}%</strong>
                <div class="asset-progress"><span style="width:{{ min(100, $kpis['room_rotation_rate']) }}%"></span></div>
                <p>{{ $kpis['rotated_rooms'] }} chambre(s) libérée(s) sur la période</p>
            </article>
        </div>
        <div class="asset-definition">
            <i class="fa-solid fa-circle-info"></i>
            <p><strong>Méthode :</strong> les taux de maintenance et hors service sont rapportés au nombre de chambres du parc analysé. Le taux de rotation correspond aux chambres ayant enregistré au moins un départ pendant la période.</p>
        </div>
    </section>

    <section class="panel discipline-panel report-tab-section" data-report-section="discipline" hidden>
        <div class="panel-title"><div><span>Vie résidentielle</span><h2>Indicateurs disciplinaires</h2></div>@adminroute('admin.hebergement.vie_residentielle.incidents.index')
<a href="{{ route('admin.hebergement.vie_residentielle.incidents.index') }}">Ouvrir les rapports <i class="feather icon-arrow-right"></i></a>
@endadminroute</div>
        <p class="panel-description">Rapports, décisions et sanctions{{ $citeId ? ' pour la cité sélectionnée' : ' pour l’ensemble des résidences' }} sur la période.</p>
        <div class="discipline-kpis">
            <article class="red"><i class="fa-solid fa-file-shield"></i><div><small>Rapports reçus</small><strong>{{ $disciplineStats['reports'] }}</strong><p>{{ $disciplineStats['pending'] }} à traiter ou en cours</p></div></article>
            <article class="green"><i class="fa-solid fa-check-double"></i><div><small>Taux de décision</small><strong>{{ $disciplineStats['decision_rate'] }}%</strong><p>{{ $disciplineStats['decided'] }} décision(s) rendue(s)</p></div></article>
            <article class="purple"><i class="fa-solid fa-gavel"></i><div><small>Sanctions prononcées</small><strong>{{ $disciplineStats['sanctions'] }}</strong><p>sur la période sélectionnée</p></div></article>
            <article class="amber"><i class="fa-solid fa-triangle-exclamation"></i><div><small>Sanctions actives</small><strong>{{ $disciplineStats['active_sanctions'] }}</strong><p>mesures actuellement applicables</p></div></article>
        </div>
        <div class="discipline-charts">
            <article><h3>Statut des rapports</h3><div id="disciplineStatusChart" class="chart discipline-chart"></div></article>
            <article><h3>Gravité des incidents</h3><div id="disciplineGravityChart" class="chart discipline-chart"></div></article>
            <article><h3>Types de sanctions</h3><div id="disciplineTypeChart" class="chart discipline-chart"></div></article>
            <article><h3>Portée des sanctions</h3><div id="disciplineScopeChart" class="chart discipline-chart"></div></article>
        </div>
        <article class="discipline-trend-card"><div><span>Évolution</span><h3>Rapports et sanctions — 12 derniers mois</h3></div><div id="disciplineTrendChart" class="chart discipline-trend"></div></article>
    </section>

    <section class="panel residence-panel report-tab-section" data-report-section="residences" hidden>
        <div class="panel-title"><div><span>Disponibilités</span><h2>Taux d’occupation par résidence</h2></div>@adminroute('admin.hebergement.parc-immobilier.index')
<a href="{{ route('admin.hebergement.parc-immobilier.index') }}">Ouvrir le parc <i class="feather icon-arrow-right"></i></a>
@endadminroute</div>
        <div class="residence-occupancy-grid">
            @forelse($residences as $residence)
                <article>
                    <div><span>{{ mb_substr($residence['name'], 0, 2) }}</span><div><strong>{{ $residence['name'] }}</strong><small>{{ $residence['rooms'] }} chambre(s)</small></div></div>
                    <strong class="occupancy-value">{{ $residence['rate'] }}%</strong>
                    <div class="occupancy-track"><span style="width: {{ min(100, $residence['rate']) }}%"></span></div>
                    <p><b>{{ $residence['occupied'] }}</b> occupée(s) · <b>{{ $residence['available'] }}</b> libre(s) · {{ $residence['capacity'] }} places</p>
                </article>
            @empty
                <div class="empty">Aucune résidence disponible.</div>
            @endforelse
        </div>
        <div class="table-responsive">
            <table class="report-table">
                <thead><tr><th>Cité</th><th>Chambres</th><th>Occupation</th><th>Places libres</th><th>Encaissements</th><th>Maintenance</th></tr></thead>
                <tbody>
                @forelse($residences as $residence)
                    <tr>
                        <td><strong>{{ $residence['name'] }}</strong></td>
                        <td>{{ $residence['rooms'] }}</td>
                        <td><div class="rate"><div><span style="width: {{ min(100, $residence['rate']) }}%"></span></div><strong>{{ $residence['rate'] }}%</strong></div><small>{{ $residence['occupied'] }} / {{ $residence['capacity'] }} places</small></td>
                        <td><span class="badge-soft green">{{ $residence['available'] }}</span></td>
                        <td><strong>{{ number_format($residence['revenue'], 0, ',', ' ') }} F</strong></td>
                        <td><span class="badge-soft {{ $residence['maintenance'] > 0 ? 'red' : 'green' }}">{{ $residence['maintenance'] }} ouverte(s)</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">Aucune donnée disponible pour les filtres sélectionnés.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="building-heading"><div><span>Détail opérationnel</span><h2>Taux d’occupation par bâtiment</h2></div></div>
        <div class="table-responsive">
            <table class="report-table building-table">
                <thead><tr><th>Résidence</th><th>Bâtiment</th><th>Chambres</th><th>Places occupées</th><th>Places libres</th><th>Taux d’occupation</th></tr></thead>
                <tbody>
                @forelse($buildingPerformance as $building)
                    <tr>
                        <td>{{ $building['residence'] }}</td>
                        <td><strong>{{ $building['name'] }}</strong></td>
                        <td>{{ $building['rooms'] }}</td>
                        <td>{{ $building['occupied'] }} / {{ $building['capacity'] }}</td>
                        <td><span class="badge-soft green">{{ $building['available'] }}</span></td>
                        <td><div class="rate"><div><span style="width: {{ min(100, $building['rate']) }}%"></span></div><strong>{{ $building['rate'] }}%</strong></div></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">Aucun bâtiment disponible pour la résidence sélectionnée.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection

@push('styles')
<style>
:root{--report-ink:#18233b;--report-muted:#778198;--report-line:#e7ebf2;--report-blue:#315efb;--report-bg:#f5f7fb}.page-wrapper{background:var(--report-bg);min-height:calc(100vh - 70px)}.report-shell{max-width:1600px;margin:auto;padding:24px 28px 40px;font-family:Montserrat,sans-serif;color:var(--report-ink)}.report-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:24px;margin-top:22px}.eyebrow,.panel-title span{color:var(--report-blue);font-size:var(--app-font-min, 15px);font-weight:750;letter-spacing:1px;text-transform:uppercase}.report-heading h1{font-size:27px;font-weight:750;margin:5px 0}.report-heading p{margin:0;color:var(--report-muted);font-size:var(--app-font-min, 15px)}.report-filters{display:flex;align-items:flex-end;gap:10px}.report-filters label span{display:block;color:var(--report-muted);font-size:var(--app-font-min, 15px);font-weight:650;margin:0 0 5px 2px}.report-filters select,.report-filters button{height:40px;border:1px solid var(--report-line);border-radius:10px;background:#fff;padding:0 12px;font-size:var(--app-font-min, 15px);color:var(--report-ink);outline:0}.report-filters select{min-width:145px}.report-filters button{background:var(--report-blue);color:#fff;border-color:var(--report-blue);font-weight:650}.date-context{display:flex;align-items:center;gap:8px;margin:11px 0 20px;color:#8d96a8;font-size:var(--app-font-min, 15px)}.date-context span{width:7px;height:7px;border-radius:50%;background:#20b477;box-shadow:0 0 0 4px rgba(32,180,119,.12)}
.report-nav{display:flex;flex-wrap:wrap;gap:7px;margin:0 0 16px;padding:6px;background:#fff;border:1px solid var(--report-line);border-radius:13px;box-shadow:0 5px 18px rgba(30,45,75,.035)}.report-nav .nav-link{border:0;border-radius:9px;background:transparent;color:#6d788e;padding:10px 14px;font-size:var(--app-font-min, 15px);font-weight:650;cursor:pointer;transition:.18s}.report-nav .nav-link i{margin-right:6px}.report-nav .nav-link:hover{background:#f1f4fa;color:var(--report-ink)}.report-nav .nav-link.active{background:var(--report-blue);color:#fff;box-shadow:0 5px 12px rgba(49,94,251,.22)}.report-tab-section[hidden]{display:none!important}
.kpi-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}.kpi,.panel,.mini-grid>div{background:#fff;border:1px solid var(--report-line);border-radius:14px;box-shadow:0 7px 22px rgba(30,45,75,.04)}.kpi{display:flex;gap:13px;padding:17px;border-top:3px solid;min-width:0}.kpi>span{width:38px;height:38px;border-radius:10px;display:grid;place-items:center;flex:0 0 auto}.kpi small,.kpi strong,.kpi p{display:block}.kpi small{color:var(--report-muted);font-size:var(--app-font-min, 15px);text-transform:uppercase;letter-spacing:.5px}.kpi strong{font-size:21px;margin:5px 0 3px}.kpi p{font-size:var(--app-font-min, 15px);color:var(--report-muted);margin:0;line-height:1.4}.kpi.blue{border-top-color:#315efb}.kpi.blue>span{background:#edf1ff;color:#315efb}.kpi.amber{border-top-color:#eda52b}.kpi.amber>span{background:#fff6e5;color:#da911b}.kpi.green{border-top-color:#20b477}.kpi.green>span{background:#eaf9f3;color:#20a36e}.kpi.red{border-top-color:#e45851}.kpi.red>span{background:#fff0ef;color:#e45851}.kpi.purple{border-top-color:#7758d8}.kpi.purple>span{background:#f1edff;color:#6d4fc8}.kpi.rose{border-top-color:#d95f8d}.kpi.rose>span{background:#fff0f6;color:#c94f7e}.kpi.orange{border-top-color:#e77b35}.kpi.orange>span{background:#fff2e9;color:#d96b25}.kpi.slate{border-top-color:#60708d}.kpi.slate>span{background:#eef1f5;color:#52617b}
.mini-grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:10px;margin:14px 0}.mini-grid>div{display:flex;align-items:center;gap:9px;padding:12px;min-width:0}.mini-grid i{color:#60708d;font-size:var(--app-font-min, 15px)}.mini-grid small,.mini-grid strong{display:block}.mini-grid small{color:var(--report-muted);font-size:var(--app-font-min, 15px);margin-bottom:3px}.mini-grid strong{font-size:var(--app-font-min, 15px);line-height:1.35}.report-grid{display:grid;gap:14px;margin-bottom:14px;min-width:0}.main-grid{grid-template-columns:minmax(0,1.8fr) minmax(260px,.65fr)}.split-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.panel{padding:18px;min-width:0;overflow:hidden}.panel-title{display:flex;justify-content:space-between;align-items:flex-start;gap:10px}.panel-title h2{font-size:var(--app-font-min, 15px);margin:4px 0 0;font-weight:700}.panel-title a{color:var(--report-blue);font-size:var(--app-font-min, 15px);font-weight:650;text-decoration:none}.chart{display:block;width:100%;max-width:100%;min-width:0;}.chart .apexcharts-canvas,.chart .apexcharts-svg{max-width:100%!important}.large-chart{height:315px}.donut-chart{height:245px}.bar-chart{height:245px}.occupancy-legend{display:grid;grid-template-columns:1fr 1fr;border-top:1px solid var(--report-line);padding-top:12px}.occupancy-legend>div{display:flex;align-items:center;justify-content:center;gap:8px}.occupancy-legend i{width:6px;height:24px;border-radius:4px}.occupancy-legend i.occupied{background:#315efb}.occupancy-legend i.available{background:#dfe5f1}.occupancy-legend strong,.occupancy-legend small{display:block}.occupancy-legend strong{font-size:var(--app-font-min, 15px)}.occupancy-legend small{font-size:var(--app-font-min, 15px);color:var(--report-muted)}
.admission-panel{margin-top:14px}.admission-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin:16px 0}.admission-kpis article{display:flex;align-items:center;gap:10px;border:1px solid var(--report-line);border-radius:11px;padding:12px;min-width:0}.admission-kpis i{width:34px;height:34px;border-radius:9px;display:grid;place-items:center}.admission-kpis i.blue{background:#edf1ff;color:#315efb}.admission-kpis i.green{background:#eaf9f3;color:#20a36e}.admission-kpis i.purple{background:#f1edff;color:#6d4fc8}.admission-kpis i.amber{background:#fff6e5;color:#da911b}.admission-kpis small,.admission-kpis strong,.admission-kpis p{display:block}.admission-kpis small{font-size:var(--app-font-min, 15px);color:var(--report-muted)}.admission-kpis strong{font-size:18px;margin:2px 0}.admission-kpis p{font-size:var(--app-font-min, 15px);color:var(--report-muted);margin:0}.admission-charts{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}.admission-charts article{border:1px solid var(--report-line);border-radius:11px;padding:12px 8px 2px;min-width:0}.admission-charts h3{font-size:var(--app-font-min, 15px);margin:0 5px}.admission-chart{height:215px}
.allocation-kpi-panel{margin-top:14px}.allocation-kpis{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:16px}.allocation-kpis article{display:flex;gap:12px;align-items:flex-start;border:1px solid var(--report-line);border-left:4px solid;border-radius:12px;padding:15px;min-width:0}.allocation-kpis article.blue{border-left-color:#315efb}.allocation-kpis article.green{border-left-color:#20b477}.allocation-kpis article.red{border-left-color:#e45851}.allocation-icon{width:38px;height:38px;border-radius:10px;display:grid;place-items:center;flex:0 0 auto}.allocation-kpis .blue .allocation-icon{background:#edf1ff;color:#315efb}.allocation-kpis .green .allocation-icon{background:#eaf9f3;color:#20a36e}.allocation-kpis .red .allocation-icon{background:#fff0ef;color:#e45851}.allocation-value small,.allocation-value strong,.allocation-value p{display:block}.allocation-value small{font-size:var(--app-font-min, 15px);color:var(--report-muted);text-transform:uppercase;letter-spacing:.4px}.allocation-value strong{font-size:23px;margin:4px 0}.allocation-value p{font-size:var(--app-font-min, 15px);color:var(--report-muted);line-height:1.45;margin:0}
.residence-panel{margin-top:0}.report-table{width:100%;border-collapse:collapse;margin-top:16px}.report-table th{padding:10px 12px;background:#f8f9fc;text-transform:uppercase;letter-spacing:.5px;color:#8a94a8;font-size:var(--app-font-min, 15px);text-align:left}.report-table td{padding:12px;border-bottom:1px solid #eff1f5;font-size:var(--app-font-min, 15px);vertical-align:middle}.report-table tr:last-child td{border-bottom:0}.report-table td small{display:block;color:var(--report-muted);font-size:var(--app-font-min, 15px);margin-top:4px}.rate{display:flex;align-items:center;gap:8px;min-width:160px}.rate>div{height:6px;background:#ebeff5;border-radius:8px;overflow:hidden;flex:1}.rate>div span{display:block;height:100%;background:linear-gradient(90deg,#315efb,#7792ff);border-radius:8px}.rate>strong{font-size:var(--app-font-min, 15px);width:34px;text-align:right}.badge-soft{display:inline-flex;padding:5px 8px;border-radius:7px;font-size:var(--app-font-min, 15px);font-weight:700}.badge-soft.green{background:#eaf9f3;color:#159463}.badge-soft.red{background:#fff0ef;color:#d94e48}.empty{text-align:center!important;padding:35px!important;color:var(--report-muted)}
.residence-occupancy-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:11px;margin:16px 0 5px}.residence-occupancy-grid article{border:1px solid var(--report-line);border-radius:12px;padding:14px;position:relative}.residence-occupancy-grid article>div:first-child{display:flex;align-items:center;gap:9px;padding-right:50px}.residence-occupancy-grid article>div:first-child>span{width:31px;height:31px;border-radius:8px;background:#edf1ff;color:#315efb;display:grid;place-items:center;font-size:var(--app-font-min, 15px);font-weight:750;text-transform:uppercase}.residence-occupancy-grid strong,.residence-occupancy-grid small{display:block}.residence-occupancy-grid article>div strong{font-size:var(--app-font-min, 15px)}.residence-occupancy-grid small{font-size:var(--app-font-min, 15px);color:var(--report-muted);margin-top:2px}.occupancy-value{position:absolute;right:14px;top:17px;font-size:16px!important}.occupancy-track{height:7px;background:#edf0f5;border-radius:8px;overflow:hidden;margin:13px 0 8px}.occupancy-track span{display:block;height:100%;border-radius:8px;background:linear-gradient(90deg,#315efb,#718cff)}.residence-occupancy-grid p{font-size:var(--app-font-min, 15px);color:var(--report-muted);margin:0}.residence-occupancy-grid p b{color:var(--report-ink)}.building-heading{border-top:1px solid var(--report-line);padding-top:18px;margin-top:20px}.building-heading span{color:var(--report-blue);font-size:var(--app-font-min, 15px);font-weight:750;text-transform:uppercase;letter-spacing:.8px}.building-heading h2{font-size:var(--app-font-min, 15px);margin:4px 0 0}.building-table{margin-top:12px}
.monthly-panel{margin-bottom:14px}.panel-description{margin:6px 0 16px;color:var(--report-muted);font-size:var(--app-font-min, 15px)}.monthly-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.monthly-card{border:1px solid var(--report-line);border-radius:12px;padding:13px 10px 3px;min-width:0}.monthly-title{display:flex;align-items:center;gap:9px;padding:0 3px}.monthly-title>i{width:30px;height:30px;border-radius:8px;display:grid;place-items:center;font-size:var(--app-font-min, 15px)}.monthly-title>i.blue{color:#315efb;background:#edf1ff}.monthly-title>i.amber{color:#d99012;background:#fff6e5}.monthly-title>i.red{color:#e45851;background:#fff0ef}.monthly-title strong,.monthly-title small{display:block}.monthly-title strong{font-size:var(--app-font-min, 15px)}.monthly-title small{font-size:var(--app-font-min, 15px);color:var(--report-muted);margin-top:2px}.monthly-chart{height:225px}
.assets-kpis{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:13px}.assets-kpis article{border:1px solid var(--report-line);border-top:4px solid;border-radius:13px;padding:17px}.assets-kpis article.amber{border-top-color:#eda52b}.assets-kpis article.red{border-top-color:#e45851}.assets-kpis article.blue{border-top-color:#315efb}.asset-kpi-head{display:flex;align-items:center;gap:9px}.asset-kpi-head span{width:34px;height:34px;border-radius:9px;display:grid;place-items:center}.assets-kpis .amber .asset-kpi-head span{background:#fff6e5;color:#da911b}.assets-kpis .red .asset-kpi-head span{background:#fff0ef;color:#e45851}.assets-kpis .blue .asset-kpi-head span{background:#edf1ff;color:#315efb}.asset-kpi-head small{font-size:var(--app-font-min, 15px);color:var(--report-muted);font-weight:650;text-transform:uppercase}.assets-kpis article>strong{display:block;font-size:27px;margin:14px 0 8px}.asset-progress{height:7px;background:#edf0f5;border-radius:8px;overflow:hidden}.asset-progress span{display:block;height:100%;border-radius:8px}.assets-kpis .amber .asset-progress span{background:#eda52b}.assets-kpis .red .asset-progress span{background:#e45851}.assets-kpis .blue .asset-progress span{background:#315efb}.assets-kpis article>p{font-size:var(--app-font-min, 15px);color:var(--report-muted);margin:9px 0 0}.asset-definition{display:flex;gap:9px;align-items:flex-start;background:#f6f8fc;border-radius:10px;padding:12px;margin-top:14px;color:var(--report-muted)}.asset-definition i{color:#315efb;margin-top:2px}.asset-definition p{font-size:var(--app-font-min, 15px);line-height:1.5;margin:0}.asset-definition strong{color:var(--report-ink)}
.discipline-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:11px}.discipline-kpis article{display:flex;align-items:center;gap:10px;border:1px solid var(--report-line);border-radius:11px;padding:13px}.discipline-kpis i{width:35px;height:35px;border-radius:9px;display:grid;place-items:center}.discipline-kpis .red i{background:#fff0ef;color:#e45851}.discipline-kpis .green i{background:#eaf9f3;color:#20a36e}.discipline-kpis .purple i{background:#f1edff;color:#7052cc}.discipline-kpis .amber i{background:#fff6e5;color:#d88d14}.discipline-kpis small,.discipline-kpis strong,.discipline-kpis p{display:block}.discipline-kpis small{font-size:var(--app-font-min, 15px);color:var(--report-muted)}.discipline-kpis strong{font-size:19px;margin:2px 0}.discipline-kpis p{font-size:var(--app-font-min, 15px);color:var(--report-muted);margin:0}.discipline-charts{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:11px;margin-top:12px}.discipline-charts article,.discipline-trend-card{border:1px solid var(--report-line);border-radius:11px;padding:12px 9px 2px}.discipline-charts h3,.discipline-trend-card h3{font-size:var(--app-font-min, 15px);margin:0 4px}.discipline-chart{height:215px}.discipline-trend-card{margin-top:12px;padding:13px}.discipline-trend-card>div:first-child span{font-size:var(--app-font-min, 15px);color:var(--report-blue);font-weight:750;text-transform:uppercase}.discipline-trend{height:255px}
@media(max-width:1250px){.kpi-grid,.admission-kpis,.admission-charts,.residence-occupancy-grid,.discipline-kpis,.discipline-charts{grid-template-columns:repeat(2,1fr)}.mini-grid{grid-template-columns:repeat(3,1fr)}.monthly-grid{grid-template-columns:1fr 1fr}.monthly-card:last-child{grid-column:1/-1}}@media(max-width:850px){.report-shell{padding:18px 13px 30px}.report-heading{align-items:flex-start;flex-direction:column}.report-filters{width:100%;flex-wrap:wrap}.report-filters label{flex:1}.report-filters select{width:100%}.main-grid,.split-grid,.monthly-grid,.allocation-kpis,.assets-kpis{grid-template-columns:1fr}.monthly-card:last-child{grid-column:auto}.mini-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:520px){.kpi-grid,.mini-grid,.admission-kpis,.admission-charts,.residence-occupancy-grid,.discipline-kpis,.discipline-charts{grid-template-columns:1fr}.report-filters label,.report-filters button{width:100%;flex:none}.report-heading h1{font-size:22px}}
.panel {
    padding: 40px;
    min-width: 0;
    /* overflow: hidden; */
}
.discipline-chart {
    height: 242px;
}

/* Typographie renforcée pour améliorer la lisibilité du reporting. */
.report-shell{font-size:var(--app-font-min, 15px)}
.report-shell .eyebrow,.report-shell .panel-title span{font-size:var(--app-font-min, 15px)}
.report-shell .report-heading h1{font-size:30px}
.report-shell .report-heading p{font-size:15px;line-height:1.55}
.report-shell .report-filters label span,.report-shell .date-context{font-size:var(--app-font-min, 15px)}
.report-shell .report-filters select,.report-shell .report-filters button{font-size:var(--app-font-min, 15px)}
.report-shell .report-nav .nav-link{font-size:var(--app-font-min, 15px)}
.report-shell .kpi small,.report-shell .admission-kpis small,.report-shell .allocation-value small,.report-shell .discipline-kpis small{font-size:var(--app-font-min, 15px);line-height:1.4}
.report-shell .kpi strong{font-size:24px}
.report-shell .kpi p,.report-shell .admission-kpis p,.report-shell .allocation-value p,.report-shell .discipline-kpis p{font-size:var(--app-font-min, 15px);line-height:1.5}
.report-shell .mini-grid small,.report-shell .occupancy-legend small{font-size:var(--app-font-min, 15px)}
.report-shell .mini-grid strong,.report-shell .occupancy-legend strong{font-size:var(--app-font-min, 15px)}
.report-shell .panel-title h2,.report-shell .building-heading h2{font-size:16px}
.report-shell .panel-title a{font-size:var(--app-font-min, 15px)}
.report-shell .admission-charts h3,.report-shell .discipline-charts h3,.report-shell .discipline-trend-card h3{font-size:var(--app-font-min, 15px)}
.report-shell .report-table th{font-size:var(--app-font-min, 15px)}
.report-shell .report-table td{font-size:var(--app-font-min, 15px)}
.report-shell .report-table td small,.report-shell .badge-soft,.report-shell .rate>strong{font-size:var(--app-font-min, 15px)}
.report-shell .residence-occupancy-grid article>div strong,.report-shell .monthly-title strong{font-size:var(--app-font-min, 15px)}
.report-shell .residence-occupancy-grid small,.report-shell .residence-occupancy-grid p,.report-shell .monthly-title small{font-size:var(--app-font-min, 15px);line-height:1.45}
.report-shell .residence-occupancy-grid article>div:first-child>span{font-size:var(--app-font-min, 15px)}
.report-shell .occupancy-value{font-size:19px!important}
.report-shell .building-heading span,.report-shell .panel-description,.report-shell .asset-kpi-head small,.report-shell .assets-kpis article>p,.report-shell .asset-definition p{font-size:var(--app-font-min, 15px);line-height:1.55}
.report-shell .discipline-trend-card>div:first-child span{font-size:var(--app-font-min, 15px)}
.report-shell .apexcharts-text,.report-shell .apexcharts-legend-text{font-size:var(--app-font-min, 15px)!important}
@media(max-width:520px){.report-shell .report-heading h1{font-size:24px}}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('.report-shell');
    if (!root || root.dataset.chartsReady) return;
    root.dataset.chartsReady = 'true';
    const muted = '#778198', grid = '#edf0f5';
    const money = value => new Intl.NumberFormat('fr-FR', {notation:'compact', maximumFractionDigits:1}).format(value) + ' F';
    const charts = [];

    const activityChart = new ApexCharts(document.querySelector('#reportActivityChart'), {
        series:[
            {name:'Demandes',type:'area',data:@json($charts['trend']['requests'])},
            {name:'Maintenance',type:'line',data:@json($charts['trend']['maintenance'])},
            {name:'Encaissements',type:'column',data:@json($charts['trend']['payments'])}
        ],chart:{height:'100%',type:'line',toolbar:{show:false},fontFamily:'Montserrat'},colors:['#315efb','#e45851','#20b477'],stroke:{width:[3,3,0],curve:'smooth'},fill:{opacity:[.12,1,.9]},plotOptions:{bar:{columnWidth:'35%',borderRadius:3}},dataLabels:{enabled:false},xaxis:{categories:@json($charts['trend']['labels']),labels:{style:{colors:muted,fontSize:'8px'}}},yaxis:[{labels:{style:{colors:muted,fontSize:'8px'}}},{opposite:true,labels:{style:{colors:muted,fontSize:'8px'},formatter:money}}],grid:{borderColor:grid,strokeDashArray:4},legend:{position:'top',horizontalAlign:'left',fontSize:'9px'},tooltip:{shared:true,intersect:false}
    });
    charts.push(activityChart);
    activityChart.render();

    const occupancyChart = new ApexCharts(document.querySelector('#reportOccupancyChart'), {
        series:[{{ $kpis['occupied'] }},{{ $kpis['available'] }}],labels:['Occupées','Disponibles'],chart:{height:'100%',type:'donut',fontFamily:'Montserrat'},colors:['#315efb','#dfe5f1'],plotOptions:{pie:{donut:{size:'72%',labels:{show:true,total:{show:true,label:'Occupation',formatter:()=>@json($kpis['occupancy_rate'].'%')}}}}},dataLabels:{enabled:false},legend:{show:false},stroke:{width:2,colors:['#fff']}
    });
    charts.push(occupancyChart);
    occupancyChart.render();

    const horizontalChart = (selector, labels, values, colors) => {
        const chart = new ApexCharts(document.querySelector(selector), {
        series:[{data:values}],chart:{height:'100%',type:'bar',toolbar:{show:false},fontFamily:'Montserrat'},colors,plotOptions:{bar:{horizontal:true,borderRadius:5,distributed:true,barHeight:'50%'}},dataLabels:{enabled:true,style:{fontSize:'9px'},formatter:value=>Math.round(value)},xaxis:{categories:labels,labels:{style:{colors:muted,fontSize:'8px'}}},yaxis:{labels:{style:{colors:'#33405a',fontSize:'9px'}}},grid:{borderColor:grid,strokeDashArray:4},legend:{show:false}
        });
        charts.push(chart);
        chart.render();
    };
    horizontalChart('#reportRequestsChart', @json($charts['requests']['labels']), @json($charts['requests']['values']), ['#eda52b','#7758d8','#20b477','#e45851']);
    horizontalChart('#reportPaymentsChart', @json($charts['payments']['labels']), @json($charts['payments']['values']), ['#20b477','#eda52b','#e45851']);

    const admissionChart = (selector, labels, values, colors) => {
        const chart = new ApexCharts(document.querySelector(selector), {
            series:values,labels,chart:{height:'100%',type:'donut',fontFamily:'Montserrat'},colors,stroke:{width:2,colors:['#fff']},dataLabels:{enabled:false},plotOptions:{pie:{donut:{size:'66%'}}},legend:{position:'bottom',fontSize:'8px',markers:{size:4}},tooltip:{y:{formatter:value=>Math.round(value)+' dossier(s)'}}
        });
        charts.push(chart);
        chart.render();
    };
    admissionChart('#admissionGenderChart', @json($admissionStats['gender']['labels']), @json($admissionStats['gender']['values']), ['#315efb','#d95f8d','#aab3c3']);
    admissionChart('#admissionLevelChart', @json($admissionStats['level']['labels']), @json($admissionStats['level']['values']), ['#315efb','#20b477','#eda52b','#7758d8','#e45851','#55a6c8']);
    admissionChart('#admissionProgramChart', @json($admissionStats['program']['labels']), @json($admissionStats['program']['values']), ['#7758d8','#315efb','#20b477','#eda52b','#e45851','#55a6c8']);
    admissionChart('#admissionScholarshipChart', @json($admissionStats['scholarship']['labels']), @json($admissionStats['scholarship']['values']), ['#20b477','#dfe5f1']);

    const monthlyLabels = @json($charts['monthly']['labels']);
    const monthlyChart = (selector, data, color, formatter) => {
        const chart = new ApexCharts(document.querySelector(selector), {
            series:[{name:'Valeur',data}],chart:{height:'100%',type:'area',toolbar:{show:false},zoom:{enabled:false},fontFamily:'Montserrat'},colors:[color],stroke:{curve:'smooth',width:2.5},fill:{type:'gradient',gradient:{opacityFrom:.25,opacityTo:.03,stops:[0,95]}},dataLabels:{enabled:false},markers:{size:3,strokeWidth:0},xaxis:{categories:monthlyLabels,tickAmount:5,labels:{rotate:0,hideOverlappingLabels:true,style:{colors:muted,fontSize:'8px'}},axisBorder:{show:false},axisTicks:{show:false}},yaxis:{min:0,forceNiceScale:true,tickAmount:4,labels:{style:{colors:muted,fontSize:'8px'},formatter}},grid:{borderColor:grid,strokeDashArray:4},legend:{show:false},tooltip:{y:{formatter}}
        });
        charts.push(chart);
        chart.render();
    };
    monthlyChart('#monthlyOccupancyChart', @json($charts['monthly']['occupancy']), '#315efb', value => Number(value).toLocaleString('fr-FR', {maximumFractionDigits:1}) + ' %');
    monthlyChart('#monthlyUnpaidChart', @json($charts['monthly']['unpaid']), '#eda52b', value => money(value));
    monthlyChart('#monthlyIncidentsChart', @json($charts['monthly']['incidents']), '#e45851', value => Math.round(value) + ' incident' + (Math.round(value) > 1 ? 's' : ''));

    admissionChart('#disciplineStatusChart', @json($disciplineStats['statuses']['labels']), @json($disciplineStats['statuses']['values']), ['#eda52b','#315efb','#7758d8','#e45851','#20b477','#60708d']);
    admissionChart('#disciplineGravityChart', @json($disciplineStats['gravity']['labels']), @json($disciplineStats['gravity']['values']), ['#20b477','#eda52b','#e45851','#9d2830']);
    admissionChart('#disciplineTypeChart', @json($disciplineStats['types']['labels']), @json($disciplineStats['types']['values']), ['#eda52b','#e45851','#7758d8']);
    admissionChart('#disciplineScopeChart', @json($disciplineStats['scopes']['labels']), @json($disciplineStats['scopes']['values']), ['#315efb','#20b477','#eda52b','#7758d8']);
    const disciplineTrend = new ApexCharts(document.querySelector('#disciplineTrendChart'), {
        series:[{name:'Rapports',data:@json($disciplineStats['trend']['reports'])},{name:'Sanctions',data:@json($disciplineStats['trend']['sanctions'])}],
        chart:{height:'100%',type:'line',toolbar:{show:false},fontFamily:'Montserrat'},colors:['#e45851','#7758d8'],stroke:{curve:'smooth',width:3},markers:{size:3},dataLabels:{enabled:false},xaxis:{categories:@json($disciplineStats['trend']['labels']),labels:{style:{colors:muted,fontSize:'8px'}}},yaxis:{min:0,forceNiceScale:true,labels:{style:{colors:muted,fontSize:'8px'},formatter:value=>Math.round(value)}},grid:{borderColor:grid,strokeDashArray:4},legend:{position:'top',horizontalAlign:'left',fontSize:'9px'}
    });
    charts.push(disciplineTrend); disciplineTrend.render();

    const activateReportTab = target => {
        document.querySelectorAll('[data-report-section]').forEach(section => {
            section.hidden = section.dataset.reportSection !== target;
        });
        document.querySelectorAll('[data-report-target]').forEach(button => {
            const active = button.dataset.reportTarget === target;
            button.classList.toggle('active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        history.replaceState(null, '', '#'+target);
        requestAnimationFrame(() => requestAnimationFrame(() => {
            charts.forEach(chart => {
                if (!chart.el || chart.el.closest('[hidden]')) return;
                const width = Math.floor(chart.el.getBoundingClientRect().width);
                if (width > 0) chart.updateOptions({chart:{width}}, false, false, false);
            });
        }));
    };
    document.querySelectorAll('[data-report-target]').forEach(button => {
        button.addEventListener('click', () => activateReportTab(button.dataset.reportTarget));
    });
    const requestedTab = location.hash.slice(1);
    if (['strategy','admissions','trends','assets','discipline','residences'].includes(requestedTab)) activateReportTab(requestedTab);

    // ApexCharts conserve parfois la largeur calculée avant la fin de l'ouverture
    // du menu latéral. On resynchronise chaque graphe avec sa carte parente.
    if ('ResizeObserver' in window) {
        const observer = new ResizeObserver(entries => {
            entries.forEach(entry => {
                const container = entry.target;
                const chart = charts.find(item => item.el === container);
                const width = Math.floor(entry.contentRect.width);
                if (chart && width > 0) {
                    chart.updateOptions({chart:{width}}, false, false, false);
                }
            });
        });
        document.querySelectorAll('.report-shell .chart').forEach(container => observer.observe(container));
    }
});
</script>
@endpush
