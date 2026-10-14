
@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'vie-cite'
])

@section('title', 'Vie résidentielle')

@section('content')

    <x-breadcrumbs
            title="Vie résidentielle"
            :items="[
            'Gestion Hébergement' => route('admin.hebergement.index'),
            'Vie résidentielle' => null,
        ]"
    />

    <div class="dashboard-container">

        <!-- EN-TÊTE -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <h1>🏠 Pilotage Vie Résidentielle</h1>
                <span class="subtitle">Cycle de vie complet du résident</span>
            </div>
            <div class="dashboard-meta">
                <div class="dashboard-actions">
                    @adminroute('admin.hebergement.paiements.index')
                    <a href="{{ route('admin.hebergement.paiements.index') }}" class="quick-action-btn payment">
                        💳 Paiements
                    </a>
                    @endadminroute
                    @adminroute('admin.hebergement.maintenance.index')
                    <a href="{{ route('admin.hebergement.maintenance.index') }}" class="quick-action-btn maintenance">
                        🔧 Maintenance
                    </a>
                    @endadminroute
                    @adminroute('admin.hebergement.annonce.index')
                    <a href="{{ route('admin.hebergement.annonce.index') }}" class="quick-action-btn payment">
                        💳 Annonces
                    </a>
                    @endadminroute
                    @adminroute('admin.hebergement.vie_residentielle.sanctions.index')
                    <a href="{{ route('admin.hebergement.vie_residentielle.sanctions.index') }}" class="quick-action-btn maintenance">
                        ⚖️ Sanctions
                    </a>
                    @endadminroute
                    @adminroute('admin.hebergement.vie_residentielle.incidents.index')
                    <a href="{{ route('admin.hebergement.vie_residentielle.incidents.index') }}" class="quick-action-btn maintenance">
                        🛡️ Rapports disciplinaires
                    </a>
                    @endadminroute
                </div>
                📅 Année {{ $periodLabel }} · MAJ : {{ $lastUpdated }}
                <span class="campaign-badge">Campagne N+1</span>
            </div>
        </div>

        <!-- INDICATEURS CLÉS -->
        <div class="kpi-grid">
            @foreach($kpis as $kpi)
                <div class="kpi-card {{ $kpi['type'] }}">
                    <div class="kpi-label">{{ $kpi['label'] }}</div>
                    <div class="kpi-value">{{ $kpi['value'] }}</div>
                    <div class="kpi-trend {{ $kpi['trend_class'] }}">{{ $kpi['trend'] }}</div>
                </div>
            @endforeach
        </div>

        <!-- RACCOURCIS RAPIDES -->
        <div class="quick-actions-grid">
            @adminroute('admin.hebergement.paiements.index')
            <a href="{{ route('admin.hebergement.paiements.index') }}" class="quick-action-card payment">
                <div class="quick-action-icon">💰</div>
                <div class="quick-action-content">
                    <h4>Paiements des loyers</h4>
                    <p>Gestion des encaissements et suivi des impayés</p>
                    <span class="quick-action-badge">{{ $paiementsGlobaux->where('created_at', '>=', now()->startOfMonth())->count() }} paiements ce mois</span>
                </div>
                <div class="quick-action-arrow">→</div>
            </a>
            @endadminroute
            @adminroute('admin.hebergement.maintenance.index')
            <a href="{{ route('admin.hebergement.maintenance.index') }}" class="quick-action-card maintenance">
                <div class="quick-action-icon">🔧</div>
                <div class="quick-action-content">
                    <h4>Maintenance & suivi</h4>
                    <p>Ordres de travail et suivi des interventions</p>
                    <span class="quick-action-badge danger">{{ $globalOTUrgents }} OT urgents</span>
                </div>
                <div class="quick-action-arrow">→</div>
            </a>
            @endadminroute
            @adminroute('admin.hebergement.depart_etat_des_lieux.depart')
            <a href="{{ route('admin.hebergement.depart_etat_des_lieux.depart') }}" class="quick-action-card contracts">
                <div class="quick-action-icon">📄</div>
                <div class="quick-action-content">
                    <h4>Contrats & États des lieux</h4>
                    <p>Gestion des contrats et états des lieux</p>
                    <span class="quick-action-badge">{{ collect($residences)->sum('contrats.sejours_confirmes') }} séjours confirmés</span>
                </div>
                <div class="quick-action-arrow">→</div>
            </a>
            @endadminroute
            @adminroute('admin.hebergement.vie_residentielle.sanctions.index')
            <a href="{{ route('admin.hebergement.vie_residentielle.sanctions.index') }}" class="quick-action-card contracts">
                <div class="quick-action-icon">⚖️</div>
                <div class="quick-action-content">
                    <h4>Sanctions disciplinaires</h4>
                    <p>Avertissements, blâmes, suspensions et traçabilité</p>
                    <span class="quick-action-badge">{{ $sanctions->where('statut', 'active')->count() }} sanctions actives</span>
                </div>
                <div class="quick-action-arrow">→</div>
            </a>
            @endadminroute
            @adminroute('admin.hebergement.vie_residentielle.incidents.index')
            <a href="{{ route('admin.hebergement.vie_residentielle.incidents.index') }}" class="quick-action-card maintenance">
                <div class="quick-action-icon">🛡️</div><div class="quick-action-content"><h4>Rapports disciplinaires</h4><p>Transmission et décision du Service Sécurité</p><span class="quick-action-badge">{{ $rapportsDisciplinaires->whereIn('statut', ['transmis','en_cours'])->count() }} à traiter</span></div><div class="quick-action-arrow">→</div>
            </a>
            @endadminroute
        </div>

        <!-- BARRE DE FILTRES -->
        <div class="filter-bar">
            <div class="filter-group">
                <label for="filter-residence">🏢 Résidence</label>
                <select id="filter-residence">
                    <option value="">Toutes</option>
                    @foreach($residences as $residence)
                        <option value="{{ $residence['name'] }}">{{ $residence['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label for="filter-period">📊 Période</label>
                <select id="filter-period">
                    <option>Mois en cours</option>
                    <option>Trimestre</option>
                    <option>Semestre</option>
                    <option>Année universitaire</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="filter-profile">👤 Profil</label>
                <select id="filter-profile">
                    <option>Tous</option>
                    <option>Boursiers</option>
                    <option>Non-boursiers</option>
                    <option>PMR</option>
                    <option>Cas sociaux</option>
                </select>
            </div>
            <button class="btn-apply">Appliquer</button>
        </div>

        <!-- GRAPHIQUES -->
        <!-- <div class="section-header">
            <h2>📊 Tableau de bord graphique</h2>
            <span class="section-badge">Indicateurs clés de la vie résidentielle</span>
        </div> -->

        <!-- <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-title">🏠 Occupation & Rotation <span class="chart-badge">par résidence</span></div>
                <div class="chart-wrapper">
                    <canvas id="occupationRotationChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <div class="chart-title">🔄 Entrées / Sorties <span class="chart-badge">Mois en cours</span></div>
                <div class="chart-wrapper">
                    <canvas id="entriesExitsChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <div class="chart-title">⚠️ Incidents (mensuels) <span class="chart-badge">Techniques vs Disciplinaires</span></div>
                <div class="chart-wrapper">
                    <canvas id="incidentsChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <div class="chart-title">⏱️ Résolution incidents & Taux de réadmission</div>
                <div class="chart-wrapper">
                    <canvas id="resolutionChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <div class="chart-title">💰 Paiements & Cautions <span class="chart-badge">Montants moyens</span></div>
                <div class="chart-wrapper">
                    <canvas id="paymentsChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <div class="chart-title">⚖️ Sanctions disciplinaires <span class="chart-badge">Par type</span></div>
                <div class="chart-wrapper">
                    <canvas id="sanctionsChart"></canvas>
                </div>
            </div>
        </div> -->

        <!-- ONGLETS DÉTAILLÉS -->
        <div class="section-header">
            <h2>📋 Détails opérationnels</h2>
            <span class="section-badge">Tous les aspects de la vie résidentielle</span>
        </div>

        @unless(in_array(true, $viewAccess, true))
            <p class="p-4 text-gray-500">Aucun bloc de cette page n’est autorisé pour votre profil.</p>
        @endunless
        <div class="tabs-container">
            @if($viewAccess['occupation'])
<button class="tab-btn" data-tab="tab1">🏠 Occupation & Parc</button>
@endif
            @if($viewAccess['finance'])
<button class="tab-btn" data-tab="tab2">💰 Finances & Paiements</button>
@endif
            @if($viewAccess['maintenance'])
<button class="tab-btn" data-tab="tab3">⚠️ Incidents & Maintenance</button>
@endif
            @if($viewAccess['discipline'])
<button class="tab-btn" data-tab="tab4">⚖️ Discipline & Sécurité</button>
@endif
            @if(($viewAccess['contracts'] || $viewAccess['inspections']))
<button class="tab-btn" data-tab="tab5">📄 Contrats & États des lieux</button>
@endif
            @if($viewAccess['departures'])
<button class="tab-btn" data-tab="tab6">📤 Départs & Archivage</button>
@endif
        </div>

        <!-- TAB 1 : Occupation & Parc -->
        @if($viewAccess['occupation'])
<div id="tab1" class="tab-content">
            <div class="table-responsive">
                <table class="dashboard-table">
                    <thead>
                    <tr>
                        <th>Résidence</th>
                        <th>Occupation</th>
                        <th>Rotation</th>
                        <th>Chambres totales</th>
                        <th>Disponibles</th>
                        <th>En maintenance</th>
                        <th>Hors service</th>
                        <th>Lits occupés</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($residences as $residence)
                        <tr class="{{ $residence['occupation_rate'] < 90 ? 'row-warning' : '' }}">
                            <td><strong>{{ $residence['name'] }}</strong></td>
                            <td>
                                <span class="badge {{ $residence['occupation_rate'] >= 95 ? 'success' : ($residence['occupation_rate'] >= 90 ? 'warning' : 'danger') }}">
                                    {{ number_format($residence['occupation_rate'], 1, ',', ' ') }}%
                                </span>
                            </td>
                            <td>{{ number_format($residence['rotation_rate'], 1, ',', ' ') }}%</td>
                            <td>{{ $residence['total_chambres'] }}</td>
                            <td>{{ $residence['available'] }}</td>
                            <td>{{ $residence['maintenance'] }}</td>
                            <td>{{ $residence['hors_service'] }}</td>
                            <td>{{ $residence['occupied_lits'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;">Aucune résidence disponible</td>
                        </tr>
                    @endforelse
                    <tr class="row-total">
                        <td><strong>Moyenne CROU</strong></td>
                        <td>{{ number_format(collect($residences)->avg('occupation_rate'), 1, ',', ' ') }}%</td>
                        <td>{{ number_format(collect($residences)->avg('rotation_rate'), 1, ',', ' ') }}%</td>
                        <td>{{ collect($residences)->sum('total_chambres') }}</td>
                        <td>{{ collect($residences)->sum('available') }}</td>
                        <td>{{ collect($residences)->sum('maintenance') }}</td>
                        <td>{{ collect($residences)->sum('hors_service') }}</td>
                        <td>{{ collect($residences)->sum('occupied_lits') }}</td>
                    </tr>
                    </tbody>
                </table>
                <div class="table-footer">📌 {{ collect($residences)->sum('reserved') }} chambres réservées · {{ collect($residences)->sum('occupied_status') }} chambres occupées</div>
            </div>
        </div>
@endif


        <!-- TAB 2 : Finances & Paiements -->
        @if($viewAccess['finance'])
<div id="tab2" class="tab-content">
            <div class="table-responsive">
                <table class="dashboard-table">
                    <thead>
                    <tr>
                        <th>Résidence</th>
                        <th>Impayés</th>
                        <th>Recouvrement</th>
                        <th>Retenue caution (moy.)</th>
                        <th>Caution restituée</th>
                        <th>Coût réparations</th>
                        <th>Solde loyers / réparations*</th>
                        <th>Loyer moyen</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($residences as $residence)
                    <tr class="{{ $residence['unpaid_rate'] >= 10 ? 'row-danger' : '' }}">
                        <td><strong>{{ $residence['name'] }}</strong></td>
                        <td><span class="badge {{ $residence['unpaid_rate'] >= 10 ? 'danger' : 'success' }}">{{ number_format($residence['unpaid_rate'],1,',',' ') }}%</span></td>
                        <td>{{ number_format($residence['recovery_rate'],1,',',' ') }}%</td>
                        <td>{{ number_format($residence['finance']['retenue_moyenne'],0,',',' ') }} FCFA</td>
                        <td>{{ number_format($residence['finance']['caution_restituee_rate'],1,',',' ') }}%</td>
                        <td>{{ number_format($residence['finance']['cout_reparations'],0,',',' ') }} FCFA</td>
                        <td><span class="badge {{ $residence['finance']['rentabilite'] >= 0 ? 'success' : 'danger' }}">{{ number_format($residence['finance']['rentabilite'],0,',',' ') }} FCFA</span></td>
                        <td>{{ number_format($residence['finance']['loyer_moyen'],0,',',' ') }} FCFA</td>
                    </tr>
                    @empty<tr><td colspan="8" style="text-align:center">Aucune donnée financière.</td></tr>@endforelse
                    <tr class="row-total">
                        <td><strong>Moyenne CROU</strong></td>
                        <td>{{ number_format(collect($residences)->avg('unpaid_rate'),1,',',' ') }}%</td><td>{{ number_format(collect($residences)->avg('recovery_rate'),1,',',' ') }}%</td>
                        <td>{{ number_format(collect($residences)->avg('finance.retenue_moyenne'),0,',',' ') }} FCFA</td><td>{{ number_format(collect($residences)->avg('finance.caution_restituee_rate'),1,',',' ') }}%</td>
                        <td>{{ number_format(collect($residences)->sum('finance.cout_reparations'),0,',',' ') }} FCFA</td><td>{{ number_format(collect($residences)->sum('finance.rentabilite'),0,',',' ') }} FCFA</td><td>{{ number_format(collect($residences)->avg('finance.loyer_moyen'),0,',',' ') }} FCFA</td>
                    </tr>
                    </tbody>
                </table>
                <div class="table-footer">* Solde = paiements de loyers validés − coût réel des réparations.</div>
            </div>
        </div>
@endif


        <!-- TAB 3 : Incidents & Maintenance -->
       @if($viewAccess['maintenance'])
<div id="tab3" class="tab-content">
    <div class="table-responsive">
        <table class="dashboard-table">
            <thead>
            <tr>
                <th>Résidence</th>
                <th>OT créés</th>
                <th>OT résolus</th>
                <th>Taux résolution</th>
                <th>Temps moyen résolution</th>
                <th>OT en cours</th>
                <th>Catégorie principale</th>
                <th>Incidents récurrents</th>
            </tr>
            </thead>
            <tbody>
            @forelse($residences as $residence)
                @php
                    $otEnCours = $residence['ot_en_cours'];
                    $tauxRecurrence = $residence['ot_recurrence_rate'];
                @endphp
                <tr class="{{ $residence['ot_taux_resolution'] < 80 ? 'row-warning' : '' }}">
                    <td>
                        <strong>{{ $residence['name'] }}</strong>
                        @if($residence['ot_taux_resolution'] < 80)
                            <span class="alert-icon">⚠️</span>
                        @elseif($residence['ot_taux_resolution'] >= 90)
                            <span class="star-icon">★</span>
                        @endif
                    </td>
                    <td>{{ $residence['ot_total'] }}</td>
                    <td>{{ $residence['ot_resolus'] }}</td>
                    <td>
                        <span class="badge {{ $residence['ot_taux_resolution'] >= 90 ? 'success' : ($residence['ot_taux_resolution'] >= 80 ? 'warning' : 'danger') }}">
                            {{ $residence['ot_taux_resolution'] }}%
                        </span>
                    </td>
                    <td>
                        @if($residence['ot_temps_resolution'] > 0)
                            <span class="badge {{ $residence['ot_temps_resolution'] <= 3 ? 'success' : ($residence['ot_temps_resolution'] <= 7 ? 'warning' : 'danger') }}">
                                {{ $residence['ot_temps_resolution'] }} j
                            </span>
                        @else
                            <span class="badge">-</span>
                        @endif
                    </td>
                    <td>
                        @if($otEnCours > 0)
                            <span class="badge {{ $otEnCours > 5 ? 'danger' : 'warning' }}">
                                {{ $otEnCours }}
                            </span>
                        @else
                            <span class="badge success">0</span>
                        @endif
                    </td>
                    <td>
                        @if($residence['ot_categorie_principale'] !== '-')
                            <span class="badge purple">{{ ucfirst($residence['ot_categorie_principale']) }}</span>
                        @else
                            <span class="badge">-</span>
                        @endif
                    </td>
                    <td>
                        @if($tauxRecurrence > 20)
                            <span class="badge danger">{{ $tauxRecurrence }}%</span>
                        @elseif($tauxRecurrence > 10)
                            <span class="badge warning">{{ $tauxRecurrence }}%</span>
                        @elseif($residence['ot_total'] > 0)
                            <span class="badge success">{{ $tauxRecurrence }}%</span>
                        @else
                            <span class="badge">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center;">Aucune résidence disponible</td>
                </tr>
            @endforelse
            <tr class="row-total">
                <td><strong>Total CROU</strong></td>
                <td>{{ collect($residences)->sum('ot_total') }}</td>
                <td>{{ collect($residences)->sum('ot_resolus') }}</td>
                <td>
                    <span class="badge {{ $globalOTResolutionRate >= 90 ? 'success' : ($globalOTResolutionRate >= 80 ? 'warning' : 'danger') }}">
                        {{ $globalOTResolutionRate }}%
                    </span>
                </td>
                <td>
                    @php
                        $globalTempsMoyen = collect($residences)->filter(fn($r) => $r['ot_temps_resolution'] > 0)->avg('ot_temps_resolution');
                    @endphp
                    <span class="badge">{{ $globalTempsMoyen ? round($globalTempsMoyen, 1) . ' j' : '-' }}</span>
                </td>
                <td>
                    <span class="badge {{ $globalOTEnCours > 5 ? 'danger' : 'warning' }}">
                        {{ $globalOTEnCours }}
                    </span>
                </td>
                <td>
                    @php
                        $categoriesGlobales = [];
                        foreach($residences as $residence) {
                            foreach($residence['incidents_par_categorie'] as $cat => $count) {
                                $categoriesGlobales[$cat] = ($categoriesGlobales[$cat] ?? 0) + $count;
                            }
                        }
                        $categoriePrincipaleGlobale = !empty($categoriesGlobales) 
                            ? array_search(max($categoriesGlobales), $categoriesGlobales) 
                            : '-';
                    @endphp
                    <span class="badge purple">{{ ucfirst($categoriePrincipaleGlobale) }}</span>
                </td>
                <td>
                    @php
                        $totalRecurrenceGlobale = collect($residences)->sum('ot_total');
                        $recurrenceGlobale = $totalRecurrenceGlobale > 0 
                            ? round((collect($residences)->sum(fn($r) => $r['ot_total'] - $r['ot_resolus']) / $totalRecurrenceGlobale) * 100, 1)
                            : 0;
                    @endphp
                    <span class="badge {{ $recurrenceGlobale > 20 ? 'danger' : ($recurrenceGlobale > 10 ? 'warning' : 'success') }}">
                        {{ $recurrenceGlobale }}%
                    </span>
                </td>
            </tr>
            </tbody>
        </table>
        <div class="table-footer">
            🔧 {{ $globalOTUrgents }} OT urgents en attente · 
            Dernière mise à jour : {{ $lastUpdated }}
        </div>
    </div>
    
    <!-- Détail des interventions récentes -->
    @if($ordresTravail->isNotEmpty())
    <div class="recent-interventions" style="margin-top: 20px;">
        <h4 style="margin-bottom: 12px; color: #1a3b57;">
            📋 Dernières interventions
            <span class="section-badge">{{ $ordresTravail->count() }} OT au total</span>
        </h4>
        <div class="table-responsive">
            <table class="dashboard-table">
                <thead>
                <tr>
                    <th>N° OT</th>
                    <th>Résidence</th>
                    <th>Type</th>
                    <th>Catégorie</th>
                    <th>Priorité</th>
                    <th>Statut</th>
                    <th>Assigné à</th>
                    <th>Date début</th>
                    <th>Date résolution</th>
                </tr>
                </thead>
                <tbody>
                @foreach($ordresTravail->take(10) as $ot)
                    @php
                        $statutClass = match($ot->statut) {
                            'termine' => 'success',
                            'en_cours' => 'info',
                            'en_attente' => 'warning',
                            'annule' => 'danger',
                            default => ''
                        };
                        $prioriteClass = match($ot->priorite) {
                            'urgente' => 'danger',
                            'haute' => 'warning',
                            'moyenne' => 'info',
                            'basse' => 'success',
                            default => ''
                        };
                    @endphp
                    <tr>
                        <td><strong>{{ $ot->numero_ot }}</strong></td>
                        <td>{{ $ot->residence->name ?? 'N/A' }}</td>
                        <td><span class="badge">{{ ucfirst($ot->type_intervention) }}</span></td>
                        <td><span class="badge purple">{{ ucfirst($ot->categorie) }}</span></td>
                        <td><span class="badge {{ $prioriteClass }}">{{ ucfirst($ot->priorite) }}</span></td>
                        <td><span class="badge {{ $statutClass }}">{{ str_replace('_', ' ', ucfirst($ot->statut)) }}</span></td>
                        <td>{{ $ot->assigneA->name ?? 'Non assigné' }}</td>
                        <td>{{ \Carbon\Carbon::parse($ot->date_debut_intervention)->format('d/m/Y') }}</td>
                        <td>
                            @if($ot->date_resolution)
                                {{ \Carbon\Carbon::parse($ot->date_resolution)->format('d/m/Y') }}
                            @else
                                <span class="badge warning">En attente</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endif


        <!-- TAB 4 : Discipline & Sécurité -->
        @if($viewAccess['discipline'])
<div id="tab4" class="tab-content">
            <div class="table-responsive">
                <table class="dashboard-table">
                    <thead>
                    <tr>
                        <th>Résidence</th>
                        <th>Incidents discipl.</th>
                        <th>Avertissements</th>
                        <th>Blâmes</th>
                        <th>Suspensions</th>
                        <th>Sanctions actives</th>
                        <th>Décisions rendues</th>
                        <th>Signalements sécurité</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($residences as $residence)
                    <tr class="{{ $residence['discipline']['incidents'] > 5 ? 'row-warning' : '' }}">
                        <td><strong>{{ $residence['name'] }}</strong></td><td>{{ $residence['discipline']['incidents'] }}</td><td>{{ $residence['discipline']['avertissements'] }}</td><td>{{ $residence['discipline']['blames'] }}</td><td>{{ $residence['discipline']['suspensions'] }}</td><td>{{ $residence['discipline']['sanctions_actives'] }}</td><td>{{ $residence['discipline']['decisions'] }}</td><td>{{ $residence['discipline']['transmis_securite'] }}</td>
                    </tr>
                    @empty<tr><td colspan="8" style="text-align:center">Aucune donnée disciplinaire.</td></tr>@endforelse
                    <tr class="row-total">
                        <td><strong>Moyenne CROU</strong></td>
                        <td>{{ collect($residences)->sum('discipline.incidents') }}</td><td>{{ collect($residences)->sum('discipline.avertissements') }}</td><td>{{ collect($residences)->sum('discipline.blames') }}</td><td>{{ collect($residences)->sum('discipline.suspensions') }}</td><td>{{ collect($residences)->sum('discipline.sanctions_actives') }}</td><td>{{ collect($residences)->sum('discipline.decisions') }}</td><td>{{ collect($residences)->sum('discipline.transmis_securite') }}</td>
                    </tr>
                    </tbody>
                </table>
                <div class="table-footer">⚖️ Toutes les décisions sont tracées et historisées dans le dossier du résident</div>
            </div>
        </div>
@endif


        <!-- TAB 5 : Contrats & États des lieux -->
        @if(($viewAccess['contracts'] || $viewAccess['inspections']))
<div id="tab5" class="tab-content">
            <div class="table-responsive">
                <table class="dashboard-table">
                    <thead>
                    <tr>
                        <th>Résidence</th>
                        @if($viewAccess['contracts'])
<th>Contrats générés</th>
@endif

                        @if($viewAccess['contracts'])
<th>Contrats approuvés</th>
@endif

                        @if($viewAccess['inspections'])
<th>États des lieux entrée</th>
@endif

                        @if($viewAccess['inspections'])
<th>États des lieux sortie</th>
@endif

                        @if($viewAccess['inspections'])
<th>Dégradations constatées</th>
@endif

                        @if($viewAccess['inspections'])
<th>Photos archivées</th>
@endif

                        @if($viewAccess['inspections'])
<th>États des lieux signés</th>
@endif

                    </tr>
                    </thead>
                    <tbody>
                    @forelse($residences as $residence)
                    <tr><td><strong>{{ $residence['name'] }}</strong></td>@if($viewAccess['contracts'])
<td>{{ $residence['contrats']['total'] }}</td>
@endif
@if($viewAccess['contracts'])
<td>{{ $residence['contrats']['confirmes'] }}</td>
@endif
@if($viewAccess['inspections'])
<td>{{ $residence['contrats']['edl_entree'] }}</td>
@endif
@if($viewAccess['inspections'])
<td>{{ $residence['contrats']['edl_sortie'] }}</td>
@endif
@if($viewAccess['inspections'])
<td>{{ $residence['contrats']['degradations'] }}</td>
@endif
@if($viewAccess['inspections'])
<td>{{ $residence['contrats']['photos'] }}</td>
@endif
@if($viewAccess['inspections'])
<td>{{ number_format($residence['contrats']['conformite'],1,',',' ') }}%</td>
@endif
</tr>
                    @empty<tr><td colspan="8" style="text-align:center">Aucune donnée de séjour.</td></tr>@endforelse
                    <tr class="row-total">
                        <td><strong>Moyenne CROU</strong></td>
                        @if($viewAccess['contracts'])
<td>{{ collect($residences)->sum('contrats.total') }}</td>
@endif
@if($viewAccess['contracts'])
<td>{{ collect($residences)->sum('contrats.confirmes') }}</td>
@endif
@if($viewAccess['inspections'])
<td>{{ collect($residences)->sum('contrats.edl_entree') }}</td>
@endif
@if($viewAccess['inspections'])
<td>{{ collect($residences)->sum('contrats.edl_sortie') }}</td>
@endif
@if($viewAccess['inspections'])
<td>{{ collect($residences)->sum('contrats.degradations') }}</td>
@endif
@if($viewAccess['inspections'])
<td>{{ collect($residences)->sum('contrats.photos') }}</td>
@endif
@if($viewAccess['inspections'])
<td>{{ number_format(collect($residences)->avg('contrats.conformite'),1,',',' ') }}%</td>
@endif

                    </tr>
                    </tbody>
                </table>
                <div class="table-footer">📄 Contrats enregistrés et approbations étudiantes · Taux de signature des états des lieux.</div>
            </div>
        </div>
@endif


        <!-- TAB 6 : Départs & Archivage -->
        @if($viewAccess['departures'])
<div id="tab6" class="tab-content">
            <div class="table-responsive">
                <table class="dashboard-table">
                    <thead>
                    <tr>
                        <th>Résidence</th>
                        <th>Demandes de départ</th>
                        <th>Départs anticipés</th>
                        <th>Départs disciplinaires</th>
                        <th>Préavis non respecté</th>
                        <th>Dossiers clôturés</th>
                        <th>Retenues calculées</th>
                        <th>Taux de clôture</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($residences as $residence)
                    <tr class="{{ $residence['departs']['preavis_non_respecte'] > 0 ? 'row-warning' : '' }}"><td><strong>{{ $residence['name'] }}</strong></td><td>{{ $residence['departs']['demandes'] }}</td><td>{{ $residence['departs']['anticipes'] }}</td><td>{{ $residence['departs']['disciplinaires'] }}</td><td>{{ $residence['departs']['preavis_non_respecte'] }}</td><td>{{ $residence['departs']['archives'] }}</td><td>{{ number_format($residence['departs']['retenues'],0,',',' ') }} FCFA</td><td>{{ number_format($residence['departs']['cloture_rate'],1,',',' ') }}%</td></tr>
                    @empty<tr><td colspan="8" style="text-align:center">Aucune donnée de départ.</td></tr>@endforelse
                    <tr class="row-total">
                        <td><strong>Moyenne CROU</strong></td>
                        <td>{{ collect($residences)->sum('departs.demandes') }}</td><td>{{ collect($residences)->sum('departs.anticipes') }}</td><td>{{ collect($residences)->sum('departs.disciplinaires') }}</td><td>{{ collect($residences)->sum('departs.preavis_non_respecte') }}</td><td>{{ collect($residences)->sum('departs.archives') }}</td><td>{{ number_format(collect($residences)->sum('departs.retenues'),0,',',' ') }} FCFA</td><td>{{ number_format(collect($residences)->avg('departs.cloture_rate'),1,',',' ') }}%</td>
                    </tr>
                    </tbody>
                </table>
                <div class="table-footer">📦 Archivage complet : contrat, états des lieux, paiements, incidents, décisions disciplinaires</div>
            </div>
        </div>
@endif


        <!-- RECOMMANDATIONS -->
        <div class="recommendations">
            <h3>📌 Analyses stratégiques</h3>

            <div class="rec-highlights">
                @if($viewAccess['occupation'])
<span class="highlight-item">⚡ <strong>Taux de réadmission :</strong> {{ $kpis[3]['value'] }}</span>
@endif
                @if($viewAccess['contracts'])
<span class="highlight-item">📄 <strong>Séjours confirmés :</strong> {{ collect($residences)->sum('contrats.sejours_confirmes') }}</span>
@endif
                @if($viewAccess['maintenance'])
<span class="highlight-item">🔧 <strong>OT résolus :</strong> {{ $globalOTResolutionRate }}%</span>
@endif
                @if($viewAccess['departures'])
<span class="highlight-item">📤 <strong>Départs clôturés :</strong> {{ $demandesSortie->where('statut','cloturee')->count() }}/{{ $demandesSortie->count() }}</span>
@endif
            </div>
        </div>

    </div>

@endsection

@push('scripts')
    <!-- Chart.js doit être chargé AVANT l'initialisation des graphiques -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // ============================================
            // GESTION DES ONGLETS
            // ============================================
            const tabs = document.querySelectorAll('.tab-btn');
            const contents = document.querySelectorAll('.tab-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    tabs.forEach(t => t.classList.remove('active'));
                    contents.forEach(c => c.classList.remove('active'));
                    this.classList.add('active');
                    const target = document.getElementById(this.dataset.tab);
                    if (target) target.classList.add('active');
                });
            });

            tabs[0]?.click();

            // ============================================
            // VÉRIFICATION DE L'EXISTENCE DE CHART.JS
            // ============================================
            if (typeof Chart === 'undefined') {
                console.error('Chart.js n\'est pas chargé !');
                return;
            }

            // ============================================
            // COULEURS
            // ============================================
            const COLORS = {
                blue: '#1e7aa8',
                green: '#2a8d6c',
                orange: '#d68a3a',
                red: '#b13e3e',
                purple: '#7a5d9e',
                lightBlue: '#6a9ec4',
                lightGreen: '#6abf9e',
                lightRed: '#e88080',
                lightOrange: '#e8a050'
            };

            // ============================================
            // CONFIGURATION PAR DÉFAUT DES GRAPHIQUES
            // ============================================
            const defaultOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, font: { size: 10 }, padding: 12 }
                    }
                }
            };

            // ============================================
            // 1. OCCUPATION & ROTATION
            // ============================================
            const canvas1 = document.getElementById('occupationRotationChart');
            if (canvas1) {
                new Chart(canvas1, {
                    type: 'bar',
                    data: {
                        labels: ['Cité A', 'Cité B', 'Cité C'],
                        datasets: [{
                            label: "Taux d'occupation (%)",
                            data: [96, 89, 98],
                            backgroundColor: [COLORS.green, COLORS.orange, COLORS.green],
                            borderRadius: 4,
                            barPercentage: 0.4,
                            yAxisID: 'y'
                        }, {
                            label: 'Taux de rotation (%)',
                            data: [10.5, 22.0, 8.0],
                            backgroundColor: COLORS.purple,
                            borderRadius: 4,
                            barPercentage: 0.4,
                            yAxisID: 'y'
                        }]
                    },
                    options: {
                        ...defaultOptions,
                        scales: {
                            y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } }
                        }
                    }
                });
            }

            // ============================================
            // 2. ENTRÉES / SORTIES
            // ============================================
            const canvas2 = document.getElementById('entriesExitsChart');
            if (canvas2) {
                new Chart(canvas2, {
                    type: 'bar',
                    data: {
                        labels: ['Cité A', 'Cité B', 'Cité C'],
                        datasets: [{
                            label: 'Entrées (nouveaux contrats)',
                            data: [28, 35, 15],
                            backgroundColor: COLORS.green,
                            borderRadius: 4,
                            barPercentage: 0.4
                        }, {
                            label: 'Sorties',
                            data: [22, 25, 12],
                            backgroundColor: COLORS.red,
                            borderRadius: 4,
                            barPercentage: 0.4
                        }]
                    },
                    options: {
                        ...defaultOptions,
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }

            // ============================================
            // 3. INCIDENTS TECHNIQUES VS DISCIPLINAIRES
            // ============================================
            const canvas3 = document.getElementById('incidentsChart');
            if (canvas3) {
                new Chart(canvas3, {
                    type: 'bar',
                    data: {
                        labels: ['Cité A', 'Cité B', 'Cité C'],
                        datasets: [{
                            label: 'Incidents techniques',
                            data: [28, 45, 15],
                            backgroundColor: COLORS.blue,
                            borderRadius: 4,
                            barPercentage: 0.4
                        }, {
                            label: 'Incidents disciplinaires',
                            data: [5, 12, 2],
                            backgroundColor: COLORS.red,
                            borderRadius: 4,
                            barPercentage: 0.4
                        }]
                    },
                    options: {
                        ...defaultOptions,
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }

            // ============================================
            // 4. RÉSOLUTION & RÉADMISSION (MIXTE)
            // ============================================
            const canvas4 = document.getElementById('resolutionChart');
            if (canvas4) {
                new Chart(canvas4, {
                    type: 'bar',
                    data: {
                        labels: ['Cité A', 'Cité B', 'Cité C'],
                        datasets: [{
                            label: 'Temps résolution (jours)',
                            data: [4.5, 6.2, 2.1],
                            backgroundColor: COLORS.orange,
                            borderRadius: 4,
                            barPercentage: 0.5,
                            yAxisID: 'y'
                        }, {
                            label: "Taux de réadmission (%)",
                            data: [75, 60, 85],
                            type: 'line',
                            borderColor: COLORS.green,
                            backgroundColor: COLORS.green,
                            pointBackgroundColor: COLORS.green,
                            tension: 0.2,
                            borderWidth: 3,
                            pointRadius: 4,
                            fill: false,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        ...defaultOptions,
                        scales: {
                            y: {
                                beginAtZero: true,
                                position: 'left',
                                max: 8,
                                ticks: { callback: v => v + ' j' }
                            },
                            y1: {
                                beginAtZero: true,
                                position: 'right',
                                max: 100,
                                grid: { drawOnChartArea: false },
                                ticks: { callback: v => v + '%' }
                            }
                        }
                    }
                });
            }

            // ============================================
            // 5. PAIEMENTS & CAUTIONS
            // ============================================
            const canvas5 = document.getElementById('paymentsChart');
            if (canvas5) {
                new Chart(canvas5, {
                    type: 'bar',
                    data: {
                        labels: ['Cité A', 'Cité B', 'Cité C'],
                        datasets: [{
                            label: 'Retenue caution (moy.)',
                            data: [25000, 40000, 15000],
                            backgroundColor: COLORS.orange,
                            borderRadius: 4,
                            barPercentage: 0.4
                        }, {
                            label: 'Coût réparations/chambre',
                            data: [35000, 55000, 18000],
                            backgroundColor: COLORS.red,
                            borderRadius: 4,
                            barPercentage: 0.4
                        }, {
                            label: 'Loyer moyen',
                            data: [45000, 42000, 50000],
                            backgroundColor: COLORS.green,
                            borderRadius: 4,
                            barPercentage: 0.4
                        }]
                    },
                    options: {
                        ...defaultOptions,
                        scales: {
                            y: { beginAtZero: true, ticks: { callback: v => (v / 1000) + 'k' } }
                        }
                    }
                });
            }

            // ============================================
            // 6. SANCTIONS DISCIPLINAIRES
            // ============================================
            const canvas6 = document.getElementById('sanctionsChart');
            if (canvas6) {
                new Chart(canvas6, {
                    type: 'bar',
                    data: {
                        labels: ['Cité A', 'Cité B', 'Cité C'],
                        datasets: [{
                            label: 'Avertissements',
                            data: [3, 7, 1],
                            backgroundColor: COLORS.lightOrange,
                            borderRadius: 3,
                            barPercentage: 0.3
                        }, {
                            label: 'Blâmes',
                            data: [1, 3, 0],
                            backgroundColor: COLORS.orange,
                            borderRadius: 3,
                            barPercentage: 0.3
                        }, {
                            label: 'Exclusions temporaires',
                            data: [1, 2, 0],
                            backgroundColor: COLORS.red,
                            borderRadius: 3,
                            barPercentage: 0.3
                        }]
                    },
                    options: {
                        ...defaultOptions,
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }

            console.log('✅ Tous les graphiques ont été initialisés avec succès !');
        });
    </script>
@endpush




@push('styles')
    <style>
        /* ============================================
           STYLES PRINCIPAUX
        ============================================ */
        .dashboard-container {
            max-width: 1440px;
            margin: 0 auto;
            padding: 0 4px;
        }

        /* --- En-tête --- */
        .dashboard-header {
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

        .dashboard-title h1 {
            font-size: 24px;
            font-weight: 700;
            color: #0b2b4a;
            margin: 0;
        }

        .dashboard-title .subtitle {
            font-size: var(--app-font-min, 15px);
            color: #4a6f8a;
            font-weight: 400;
            margin-left: 8px;
        }

        .dashboard-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            font-size: var(--app-font-min, 15px);
            color: #1e3b5c;
            font-weight: 500;
            background: #f2f6fb;
            padding: 8px 18px;
            border-radius: 30px;
        }

        .dashboard-actions {
            display: flex;
            gap: 6px;
        }

        .quick-action-btn {
            padding: 4px 14px;
            border-radius: 30px;
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .quick-action-btn.payment {
            background: #ddf0e8;
            color: #1a6e4f;
        }

        .quick-action-btn.payment:hover {
            background: #c0e6d9;
            transform: translateY(-1px);
        }

        .quick-action-btn.maintenance {
            background: #fcead8;
            color: #a8621a;
        }

        .quick-action-btn.maintenance:hover {
            background: #f9dcc0;
            transform: translateY(-1px);
        }

        .campaign-badge {
            background: #eaf0f8;
            padding: 2px 12px;
            border-radius: 20px;
            margin-left: 8px;
            font-weight: 600;
            color: #1e7aa8;
        }

        /* --- RACCOURCIS RAPIDES --- */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }

        .quick-action-card {
            background: #fff;
            border-radius: 16px;
            padding: 18px 20px;
            box-shadow: 0 2px 10px rgba(0, 20, 40, 0.06);
            display: flex;
            align-items: center;
            gap: 16px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid transparent;
            position: relative;
        }

        .quick-action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 20, 40, 0.12);
            border-color: #dce6ef;
        }

        .quick-action-card .quick-action-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }

        .quick-action-card.payment .quick-action-icon {
            background: #ddf0e8;
        }

        .quick-action-card.maintenance .quick-action-icon {
            background: #fcead8;
        }

        .quick-action-card.contracts .quick-action-icon {
            background: #dce8f5;
        }

        .quick-action-card .quick-action-content {
            flex: 1;
            min-width: 0;
        }

        .quick-action-card .quick-action-content h4 {
            font-size: var(--app-font-min, 15px);
            font-weight: 600;
            color: #0a2b44;
            margin: 0 0 2px;
        }

        .quick-action-card .quick-action-content p {
            font-size: var(--app-font-min, 15px);
            color: #5b7b99;
            margin: 0 0 4px;
        }

        .quick-action-card .quick-action-badge {
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
            background: #e7edf5;
            padding: 1px 10px;
            border-radius: 30px;
            color: #1f4c70;
            display: inline-block;
        }

        .quick-action-card .quick-action-badge.danger {
            background: #fce1e1;
            color: #b13e3e;
        }

        .quick-action-card .quick-action-arrow {
            font-size: 18px;
            color: #b0c4d8;
            transition: transform 0.2s;
            flex-shrink: 0;
        }

        .quick-action-card:hover .quick-action-arrow {
            transform: translateX(4px);
            color: #1e7aa8;
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

        .kpi-card .kpi-trend.up {
            background: #ddf0e8;
            color: #1a6e4f;
        }
        .kpi-card .kpi-trend.danger {
            background: #fce1e1;
            color: #b13e3e;
        }
        .kpi-card .kpi-trend.warning {
            background: #fcead8;
            color: #a8621a;
        }
        .kpi-card .kpi-trend.stable {
            background: #e7edf5;
            color: #1f4462;
        }

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

        .filter-group select {
            padding: 6px 14px;
            border-radius: 30px;
            border: 1px solid #dae2ec;
            background: #f8fafc;
            font-size: var(--app-font-min, 15px);
            color: #17324b;
            outline: none;
            cursor: pointer;
        }

        .filter-group select:focus {
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

        /* --- Sections --- */
        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 32px 0 16px;
        }

        .section-header h2 {
            font-size: 18px;
            font-weight: 600;
            color: #1a3b57;
            margin: 0;
        }

        .section-badge {
            background: #dce6ef;
            padding: 2px 14px;
            border-radius: 30px;
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
            color: #1f4c70;
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

        /* --- Onglets --- */
        .tabs-container {
            display: flex;
            gap: 4px;
            margin-bottom: 18px;
            background: #eaf0f8;
            padding: 4px;
            border-radius: 14px;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 8px 20px;
            border: none;
            background: transparent;
            border-radius: 12px;
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
            color: #1f4462;
            cursor: pointer;
            transition: 0.2s;
        }

        .tab-btn.active {
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 20, 40, 0.08);
            color: #0b2b4a;
        }

        .tab-btn:hover:not(.active) {
            background: rgba(255, 255, 255, 0.5);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* --- Tableaux --- */
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
            min-width: 700px;
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
        }

        .dashboard-table tbody tr:last-child td {
            border-bottom: none;
        }

        .dashboard-table .row-warning {
            background: #fcf7f2;
        }

        .dashboard-table .row-danger {
            background: #fcf2f2;
        }

        .dashboard-table .row-total {
            font-weight: 600;
            background: #f0f5fa;
        }

        .table-footer {
            text-align: right;
            font-size: var(--app-font-min, 15px);
            color: #5f7e9c;
            padding: 6px 14px 2px;
        }

        /* --- Badges --- */
        .badge {
            display: inline-block;
            padding: 1px 12px;
            border-radius: 30px;
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
            color: #1d4b70;
            background: #e7edf5;
        }

        .badge.success {
            background: #ddf0e8;
            color: #1a6e4f;
        }
        .badge.warning {
            background: #fcead8;
            color: #a8621a;
        }
        .badge.danger {
            background: #fce1e1;
            color: #b13e3e;
        }
        .badge.purple {
            background: #e8e1f0;
            color: #6a4d8a;
        }

        .alert-icon {
            font-size: var(--app-font-min, 15px);
            margin-left: 2px;
        }
        .star-icon {
            color: #e8a050;
            font-size: var(--app-font-min, 15px);
            margin-left: 2px;
        }

        /* --- Recommandations --- */
        .recommendations {
            background: #fff;
            border-radius: 20px;
            padding: 22px 26px;
            margin-top: 28px;
            box-shadow: 0 4px 16px rgba(0, 20, 40, 0.05);
        }

        .recommendations h3 {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #0b2b4a;
            font-weight: 600;
            font-size: 17px;
            margin: 0 0 16px;
        }

        .recommendations ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .recommendations li {
            display: flex;
            gap: 14px;
            align-items: flex-start;
            border-bottom: 1px solid #eef3f8;
            padding-bottom: 10px;
        }

        .recommendations li:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .rec-number {
            flex-shrink: 0;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: var(--app-font-min, 15px);
        }

        .rec-number.danger { background: #b13e3e; }
        .rec-number.success { background: #1a6e4f; }
        .rec-number.warning { background: #d68a3a; }
        .rec-number.purple { background: #7a5d9e; }

        .rec-highlights {
            display: flex;
            flex-wrap: wrap;
            gap: 16px 28px;
            margin-top: 16px;
            background: #eaf0f8;
            padding: 12px 20px;
            border-radius: 14px;
            font-size: var(--app-font-min, 15px);
            color: #1e4463;
        }

        .highlight-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* --- Responsive --- */
        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .dashboard-meta {
                width: 100%;
                justify-content: flex-start;
                padding: 12px 16px;
            }

            .dashboard-actions {
                width: 100%;
                justify-content: flex-start;
            }

            .quick-action-btn {
                font-size: var(--app-font-min, 15px);
                padding: 4px 12px;
            }

            .quick-actions-grid {
                grid-template-columns: 1fr;
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

            .btn-apply {
                margin-left: 0;
            }

            .tabs-container {
                flex-wrap: wrap;
            }

            .tab-btn {
                flex: 1 1 auto;
                text-align: center;
                font-size: var(--app-font-min, 15px);
                padding: 6px 14px;
            }

            .dashboard-title h1 {
                font-size: 20px;
            }
            .dashboard-title .subtitle {
                display: block;
                margin-left: 0;
            }
        }

        @media (max-width: 480px) {
            .kpi-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-meta {
                font-size: var(--app-font-min, 15px);
                padding: 8px 12px;
            }

            .quick-action-card {
                padding: 14px 16px;
            }

            .quick-action-card .quick-action-icon {
                width: 40px;
                height: 40px;
                font-size: 20px;
            }

            .quick-action-card .quick-action-content h4 {
                font-size: var(--app-font-min, 15px);
            }
        }
    </style>
@endpush
