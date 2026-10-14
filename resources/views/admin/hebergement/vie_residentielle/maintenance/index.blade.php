


@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'vie-cite'
])

@section('title', 'Maintenance et suivi')

@section('content')

    <x-breadcrumbs
            title="Maintenance et suivi"
            :items="[
            'Gestion Hébergement' => route('admin.hebergement.index'),
            'Vie résidentielle' => route('admin.hebergement.vie_residentielle.index'),
            'Maintenance' => null,
        ]"
    />

    <div class="maintenance-container">

        <!-- EN-TÊTE -->
        <div class="maintenance-header">
            <div>
                <h1>🔧 Maintenance et suivi</h1>
                <span class="subtitle">Gestion des ordres de travail et suivi des interventions</span>
            </div>
            <div class="maintenance-meta">
                📅 {{ now()->format('F Y') }}
                <span class="campaign-badge" style="background:#eaf0f8; padding:2px 12px; border-radius:20px; margin-left:8px; font-weight:600; color:#1e7aa8;">{{ now()->weekOfYear }}e semaine</span>
            </div>
        </div>

        <!-- KPI GLOBAUX -->
        <div class="kpi-grid">
            <div class="kpi-card info">
                <div class="kpi-label">OT en cours</div>
                <div class="kpi-value">{{ $stats['en_attente'] + $stats['en_cours'] + $stats['en_attente_pieces'] ?? 0 }}</div>
                <div class="kpi-trend stable">● {{ $stats['en_cours'] ?? 0 }} en cours</div>
            </div>
            <div class="kpi-card success">
                <div class="kpi-label">OT résolus</div>
                <div class="kpi-value">{{ $stats['termine'] ?? 0 }}</div>
                <div class="kpi-trend up">▲ {{ $stats['total'] > 0 ? round(($stats['termine'] / $stats['total']) * 100) : 0 }}% résolus</div>
            </div>
            <div class="kpi-card warning">
                <div class="kpi-label">Temps résolution moyen</div>
                <div class="kpi-value">{{ $stats['temps_moyen_resolution'] ?? 0 }} j</div>
                <div class="kpi-trend warning">Objectif &lt;3 j</div>
            </div>
            <div class="kpi-card danger">
                <div class="kpi-label">OT urgents</div>
                <div class="kpi-value">{{ $stats['urgent'] ?? 0 }}</div>
                <div class="kpi-trend danger">{{ $stats['urgent'] > 0 ? '▲ À traiter' : '● Aucun' }}</div>
            </div>
            <div class="kpi-card purple">
                <div class="kpi-label">Coût maintenance (mois)</div>
                <div class="kpi-value">{{ number_format($stats['cout_total'] ?? 0, 0, ',', ' ') }} FCFA</div>
                <div class="kpi-trend up">● Total</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Taux satisfaction</div>
                <div class="kpi-value">{{ $stats['taux_satisfaction'] ?? 0 }}%</div>
                <div class="kpi-trend {{ ($stats['taux_satisfaction'] ?? 0) >= 80 ? 'up' : 'warning' }}">
                    {{ ($stats['satisfaction_count'] ?? 0) > 0 ? ($stats['satisfaction_count'] . ' retours') : 'Aucun retour' }}
                </div>
            </div>
        </div>

        <!-- FILTRES -->
        <!-- <form method="GET" action="#" class="filter-bar">
            <div class="filter-group">
                <label>🏢 Cite</label>
                <select name="cite_id">
                    <option value="">Toutes</option>
                    @foreach($cites as $cite)
                        <option value="{{ $cite->id_cite }}" {{ request('cite_id') == $cite->id_cite ? 'selected' : '' }}>
                            {{ $cite->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label>📊 Statut</label>
                <select name="statut">
                    <option value="">Tous</option>
                    <option value="en_attente" {{ request('statut') == 'en_attente' ? 'selected' : '' }}>En attente</option>
                    <option value="en_cours" {{ request('statut') == 'en_cours' ? 'selected' : '' }}>En cours</option>
                    <option value="en_attente_pieces" {{ request('statut') == 'en_attente_pieces' ? 'selected' : '' }}>En attente pièces</option>
                    <option value="termine" {{ request('statut') == 'termine' ? 'selected' : '' }}>Terminé</option>
                    <option value="annule" {{ request('statut') == 'annule' ? 'selected' : '' }}>Annulé</option>
                </select>
            </div>
            <div class="filter-group">
                <label>⚠️ Priorité</label>
                <select name="priorite">
                    <option value="">Toutes</option>
                    <option value="critique" {{ request('priorite') == 'critique' ? 'selected' : '' }}>Critique</option>
                    <option value="elevee" {{ request('priorite') == 'elevee' ? 'selected' : '' }}>Élevée</option>
                    <option value="moyenne" {{ request('priorite') == 'moyenne' ? 'selected' : '' }}>Moyenne</option>
                    <option value="faible" {{ request('priorite') == 'faible' ? 'selected' : '' }}>Faible</option>
                </select>
            </div>
            <div class="filter-group">
                <label>🔧 Type</label>
                <select name="categorie">
                    <option value="">Tous</option>
                    <option value="electricite" {{ request('categorie') == 'electricite' ? 'selected' : '' }}>Électricité</option>
                    <option value="eau" {{ request('categorie') == 'eau' ? 'selected' : '' }}>Eau</option>
                    <option value="sanitaire" {{ request('categorie') == 'sanitaire' ? 'selected' : '' }}>Sanitaire</option>
                    <option value="mobilier" {{ request('categorie') == 'mobilier' ? 'selected' : '' }}>Mobilier</option>
                    <option value="securite" {{ request('categorie') == 'securite' ? 'selected' : '' }}>Sécurité</option>
                    <option value="climatisation" {{ request('categorie') == 'climatisation' ? 'selected' : '' }}>Climatisation</option>
                    <option value="batiment" {{ request('categorie') == 'batiment' ? 'selected' : '' }}>Bâtiment</option>
                    <option value="autre" {{ request('categorie') == 'autre' ? 'selected' : '' }}>Autre</option>
                </select>
            </div>
            <button type="submit" class="btn-apply">Appliquer</button>
            <a href="#" class="btn-export" style="text-decoration:none;">⟳ Réinitialiser</a>
        </form>

      
        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-title">📊 OT par statut <span class="chart-badge">Répartition</span></div>
                <div class="chart-wrapper">
                    <canvas id="otStatusChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <div class="chart-title">🏠 OT par résidence <span class="chart-badge">En cours</span></div>
                <div class="chart-wrapper">
                    <canvas id="otByResidenceChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <div class="chart-title">⏱️ Temps résolution <span class="chart-badge">par type</span></div>
                <div class="chart-wrapper">
                    <canvas id="resolutionTimeChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <div class="chart-title">🔧 Interventions par type <span class="chart-badge">Ce mois</span></div>
                <div class="chart-wrapper">
                    <canvas id="interventionTypeChart"></canvas>
                </div>
            </div>
        </div>

       
        <div class="section-header">
            <h2>
                📋 Ordres de travail (OT)
                <span class="section-badge">{{ $stats['en_attente'] + $stats['en_cours'] + $stats['en_attente_pieces'] ?? 0 }} en cours · {{ $stats['urgent'] ?? 0 }} urgents</span>
            </h2>
            <div class="section-actions">
                
                <button class="btn-action primary" onclick="openModal('otModal')">➕ Nouvel OT</button>
                @adminroute('admin.hebergement.reporting.module')
                <a class="btn-action success" href="{{ route('admin.hebergement.reporting.module', ['module' => 'vie-cite']) }}">📊 Rapport maintenance</a>
@endadminroute
                <button class="btn-action secondary">📅 Planning interventions</button>
                
                <button class="btn-action warning" onclick="openModal('maintenancierModal')">👨‍🔧 Maintenancier</button>
            </div>
        </div> -->

        <div class="section-header">
            <h2>Maintenances non terminées <span class="section-badge">{{ $ordres->total() }}</span></h2>
        </div>
        @include('admin.hebergement.vie_residentielle.maintenance.table', ['ordres' => $ordres, 'completed' => false])

        <div class="section-header" id="maintenances-terminees">
            <h2>Maintenances terminées <span class="section-badge">{{ $ordresTermines->total() }}</span></h2>
        </div>
        @include('admin.hebergement.vie_residentielle.maintenance.table', ['ordres' => $ordresTermines, 'completed' => true])
        <!-- RÉCAPITULATIF PAR RÉSIDENCE -->
        <div class="section-header">
            <h2>📊 Récapitulatif maintenance par résidence</h2>
            <span class="section-badge">Mois en cours</span>
        </div>

        <div class="table-responsive">
            <table class="dashboard-table">
                <thead>
                <tr>
                    <th>Résidence</th>
                    <th>OT créés</th>
                    <th>OT résolus</th>
                    <th>Taux résolution</th>
                    <th>Temps moyen</th>
                    <th>Coût maintenance</th>
                    <th>OT urgents</th>
                    <th>Principale cause</th>
                </tr>
                </thead>
                <tbody>
                @forelse($statsParCite ?? [] as $stat)
                    <tr>
                        <td><strong>{{ $stat['name'] ?? 'N/A' }}</strong></td>
                        <td>{{ $stat['total'] ?? 0 }}</td>
                        <td>{{ $stat['termine'] ?? 0 }}</td>
                        <td>
                            <span class="badge {{ ($stat['taux_resolution'] ?? 0) >= 80 ? 'success' : (($stat['taux_resolution'] ?? 0) >= 60 ? 'warning' : 'danger') }}">
                                {{ $stat['taux_resolution'] ?? 0 }}%
                            </span>
                            <div class="progress-bar" style="width:80px;">
                                <div class="progress-fill {{ ($stat['taux_resolution'] ?? 0) >= 80 ? 'success' : (($stat['taux_resolution'] ?? 0) >= 60 ? 'warning' : 'danger') }}"
                                     style="width:{{ $stat['taux_resolution'] ?? 0 }}%"></div>
                            </div>
                        </td>
                        <td><span class="badge">{{ $stat['temps_moyen'] ?? 0 }} j</span></td>
                        <td>{{ number_format($stat['cout_total'] ?? 0, 0, ',', ' ') }} FCFA</td>
                        <td><span class="badge {{ ($stat['urgent'] ?? 0) > 0 ? 'danger' : 'success' }}">{{ $stat['urgent'] ?? 0 }}</span></td>
                        <td><span class="tag {{ $stat['principale_cause'] ?? 'autre' }}">{{ $stat['principale_cause_label'] ?? 'N/A' }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center; padding:20px; color:var(--muted);">
                            Aucune donnée disponible
                        </td>
                    </tr>
                @endforelse
                <tr class="row-total">
                    <td><strong>Total CROU</strong></td>
                    <td><strong>{{ $stats['total'] ?? 0 }}</strong></td>
                    <td><strong>{{ $stats['termine'] ?? 0 }}</strong></td>
                    <td>
                        <strong>{{ $stats['total'] > 0 ? round(($stats['termine'] / $stats['total']) * 100) : 0 }}%</strong>
                    </td>
                    <td><strong>{{ $stats['temps_moyen_resolution'] ?? 0 }} j</strong></td>
                    <td><strong>{{ number_format($stats['cout_total'] ?? 0, 0, ',', ' ') }} FCFA</strong></td>
                    <td><strong>{{ $stats['urgent'] ?? 0 }}</strong></td>
                    <td><strong>-</strong></td>
                </tr>
                </tbody>
            </table>
            <div class="table-footer">
                💡 <strong>Objectif taux résolution :</strong> >85% ·
                @if(isset($statsParCite) && count($statsParCite) > 0)
                    @php
                        $best = collect($statsParCite)->sortByDesc('taux_resolution')->first();
                    @endphp
                    @if($best)
                        Résidence la plus performante : <strong>{{ $best['name'] ?? 'N/A' }}</strong> ({{ $best['taux_resolution'] ?? 0 }}%)
                    @endif
                @endif
                @if(($stats['urgent'] ?? 0) > 0)
                    <span style="color:#b13e3e;">⚠️ {{ $stats['urgent'] }} OT urgents à traiter</span>
                @endif
            </div>
        </div>

    </div>

    <!-- ============================================================ -->
    <!-- MODAL : ENREGISTRER MAINTENANCIER                             -->
    <!-- ============================================================ -->
    <div id="maintenancierModal" class="modal-overlay" onclick="if(event.target === this) closeModal('maintenancierModal')">
        <div class="modal-content">
            <div class="modal-header">
                <h3>👨‍🔧 Enregistrer un maintenancier</h3>
                <button class="modal-close" onclick="closeModal('maintenancierModal')">✕</button>
            </div>
            @adminroute('admin.hebergement.maintenance.store_maintenancier')
            <form  method="POST" action="{{route('admin.hebergement.maintenance.store_maintenancier')}}">
                @csrf
                <div class="modal-body">
                    <div class="form-row-modal">
                        <div class="form-group-modal">
                            <label>Nom complet <span class="required">*</span></label>
                            <input type="text" name="name" class="form-control-modal" placeholder="Ex: KOUADIO Jean" required>
                        </div>
                        <div class="form-group-modal">
                            <label>Adresse <span class="required">*</span></label>
                            <input type="text" name="adresse" class="form-control-modal" placeholder="Ex: Abidjan" required>
                        </div>
                    </div>

                    <div class="form-row-modal">
                        <div class="form-group-modal">
                            <label>📧 Email </label>
                            <input type="email" name="email" class="form-control-modal" placeholder="john.doe@crou.ci" >
                        </div>
                        <div class="form-group-modal">
                            <label>📱 Téléphone <span class="required">*</span></label>
                            <input type="tel" name="tel"  class="form-control-modal" placeholder="xx xx xx xx xx" required>
                        </div>
                    </div>

                    <div class="form-row-modal">
                        <div class="form-group-modal">
                            <label>🔧 Spécialité <span class="required">*</span></label>
                            <select name="specialite" class="form-control-modal" required>
                                <option value="">Sélectionnez une spécialité</option>
                                @foreach($specialites as $key => $field)
                                    <option {{$field =='multiservices' ? 'selected' : ''}} value="{{$field}}">{{$field}}</option>
                                @endforeach

                            </select>
                        </div>

                        <div class="form-group-modal">
                            <label>🏢 Résidence assignée</label>
                            <select name="cite_id" class="form-control-modal">
                                <option value="">Aucune</option>
                                @foreach($cites as $cite)
                                    <option value="{{ $cite->id_cite }}">{{ $cite->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group-modal">
                        <label>📝 Notes / Observations</label>
                        <textarea name="notes" class="form-control-modal" placeholder="Informations complémentaires sur le maintenancier..." rows="2"></textarea>
                    </div>

                    <div class="form-group-modal" style="margin-top:8px;">
                        <label style="display:flex; align-items:center; gap:8px; font-size:var(--app-font-min, 15px); cursor:pointer;">
                            <input type="checkbox" style="width:18px; height:18px; accent-color:#1e7aa8;" required>
                            <span>J'atteste que les informations fournies sont exactes et conformes</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal secondary" onclick="closeModal('maintenancierModal')">Annuler</button>
                    <button type="submit" class="btn-modal primary">💾 Enregistrer le maintenancier</button>
                </div>
            </form>
            @endadminroute
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODAL : NOUVEL ORDRE DE TRAVAIL (OT)                          -->
    <!-- ============================================================ -->
    <div id="otModal" class="modal-overlay" onclick="if(event.target === this) closeModal('otModal')">
        <div class="modal-content" style="max-width:700px;">
            <div class="modal-header">
                <h3>🔧 Nouvel ordre de travail (OT)</h3>
                <button class="modal-close" onclick="closeModal('otModal')">✕</button>
            </div>
            @adminroute('admin.hebergement.maintenance.store_ot')
            <form action="{{route('admin.hebergement.maintenance.store_ot')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <!-- Informations générales -->
                    <div class="form-row-modal">
                        <div class="form-group-modal">
                            <label>🏢 Résidence </label>
                            <select class="form-control-modal" name="cite_id" onchange="StructureManager.handleCiteChange(this.value)">
                                <option value="">Sélectionnez</option>
                                @foreach($cites as $cite)
                                    <option value="{{ $cite->id_cite }}">{{ $cite->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group-modal">
                            <label>🏢 Batiment </label>
                            <select class="form-control-modal" id="batiment_id"  name="batiment_id" onchange="StructureManager.handleBatiementChange(this.value)">
                                <option value="">Sélectionnez</option>

                            </select>
                        </div>
                        <div class="form-group-modal">
                            <label>🏠 Chambre </label>
                            <select class="form-control-modal" id="chambre_id" name="chambre_id">
                                <option value="">Sélectionnez</option>

                            </select>
                        </div>
                    </div>

                    <div class="form-row-modal">
                        <div class="form-group-modal">
                            <label>📋 Type d'intervention <span class="required">*</span></label>
                            <select class="form-control-modal" required name="categorie">
                                <option value="">Sélectionnez</option>
                               @foreach(\App\Models\OrdreTravail::CATEGORIES_INTERVENTIONS as $key => $value )
                                    <option value="{{$key}}">{{$value}}</option>
                               @endforeach
                            </select>
                        </div>
                        <div class="form-group-modal">
                            <label>⚠️ Priorité <span class="required">*</span></label>
                            <select class="form-control-modal" required name="priorite">
                                <option value="faible">🟢 Faible</option>
                                <option value="moyenne" selected>🟡 Moyenne</option>
                                <option value="elevee">🟠 Élevée</option>
                                <option value="critique">🔴 Critique</option>
                            </select>
                        </div>
                    </div>

{{--                    <div class="form-group-modal">--}}
{{--                        <label>👤 Demandeur <span class="required">*</span></label>--}}
{{--                        <input type="text" class="form-control-modal" placeholder="Nom du résident ou du responsable" required>--}}
{{--                    </div>--}}

                    <div class="form-group-modal">
                        <label>📝 Description du problème <span class="required">*</span></label>
                        <textarea class="form-control-modal" name="description" placeholder="Décrivez précisément le problème à résoudre..." rows="3" required></textarea>
                    </div>

                    <div class="form-row-modal">
                        <div class="form-group-modal">
                            <label>👨‍🔧 Maintenancier assigné</label>
                            <select class="form-control-modal" name="assigne_a" id="maintenancier_select">
                                <option value="">Non assigné</option>
                                @foreach($maintenanciers as $maintenancier)
                                    <option value="{{ $maintenancier->id_maintenancier }}"
                                            data-specialite="{{ $maintenancier->specialite }}"
                                            data-email="{{ $maintenancier->email }}"
                                            data-tel="{{ $maintenancier->tel }}">
                                        {{ $maintenancier->name }} - {{ $maintenancier->specialite }}
                                        @if($maintenancier->cite)
                                            ({{ $maintenancier->cite->name }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-hint" id="maintenancier_info"></div>
                        </div>
                        <div id="assignationDetails" style="display:none;">
                            <div class="form-group-modal">
                                <label>📅 Date debut d'intervention prévue</label>
                                <input type="datetime-local" name="date_debut_intervention" class="form-control-modal" value="{{ now()->format('Y-m-d\TH:i') }}" >
                            </div>
                            <div class="form-group-modal">
                                <label>📅 Date fin d'intervention prévue</label>
                                <input type="datetime-local" name="date_fin_intervention" class="form-control-modal" value="{{ now()->format('Y-m-d\TH:i') }}">
                            </div>
                        </div>
                    </div>

{{--                    <div class="form-group-modal">--}}
{{--                        <label>📎 Pièces jointes</label>--}}
{{--                        <div class="upload-area-modal" onclick="document.getElementById('otFileUpload').click()">--}}
{{--                            <span>📤 Cliquez pour joindre des fichiers (photos, PDF)</span>--}}
{{--                            <input type="file" id="otFileUpload" style="display:none;" multiple accept=".pdf,.jpg,.jpeg,.png">--}}
{{--                        </div>--}}
{{--                    </div>--}}

                    <div class="form-group-modal" style="margin-top:8px;">
                        <label style="display:flex; align-items:center; gap:8px; font-size:var(--app-font-min, 15px); cursor:pointer;">
                            <input type="checkbox" style="width:18px; height:18px; accent-color:#1e7aa8;" required>
                            <span>Je confirme que les informations sont exactes</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal secondary" onclick="closeModal('otModal')">Annuler</button>
                    <button type="submit" class="btn-modal primary">📤 Créer l'ordre de travail</button>
                </div>
            </form>
            @endadminroute

        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>


        document.addEventListener('DOMContentLoaded', function () {

            const select = document.getElementById('maintenancier_select');
            const details = document.getElementById('assignationDetails');

            function toggleDetails() {
                if (select.value !== '') {
                    details.style.display = 'block';
                } else {
                    details.style.display = 'none';
                }
            }

            // Vérifie au chargement
            toggleDetails();

            // Vérifie à chaque changement
            select.addEventListener('change', toggleDetails);

        });



        document.addEventListener('DOMContentLoaded', function() {

            if (typeof Chart === 'undefined') {
                console.error('Chart.js n\'est pas chargé !');
                return;
            }

            const COLORS = {
                blue: '#1e7aa8',
                green: '#2a8d6c',
                orange: '#d68a3a',
                red: '#b13e3e',
                purple: '#7a5d9e',
                lightBlue: '#6a9ec4',
                lightGreen: '#6abf9e',
                lightRed: '#e88080',
                lightOrange: '#e8a050',
                grey: '#a0b8cc'
            };

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

            // 1. OT par statut (Doughnut)
            const ctx1 = document.getElementById('otStatusChart');
            if (ctx1) {
                const stats = @json($stats);
                new Chart(ctx1, {
                    type: 'doughnut',
                    data: {
                        labels: ['En attente', 'En cours', 'En attente pièces', 'Terminé'],
                        datasets: [{
                            data: [
                                stats.en_attente || 0,
                                stats.en_cours || 0,
                                stats.en_attente_pieces || 0,
                                stats.termine || 0
                            ],
                            backgroundColor: [COLORS.red, COLORS.orange, COLORS.purple, COLORS.green],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { boxWidth: 12, font: { size: 10 }, padding: 12 }
                            }
                        },
                        cutout: '65%'
                    }
                });
            }

            // 2. OT par résidence
            const ctx2 = document.getElementById('otByResidenceChart');
            if (ctx2) {
                const statsParCite = @json($statsParCite ?? []);
                const labels = statsParCite.map(item => item.name || 'N/A');
                const values = statsParCite.map(item => item.en_cours || 0);
                const colors = ['#1e7aa8', '#b13e3e', '#2a8d6c', '#d68a3a', '#7a5d9e', '#6a9ec4'];

                new Chart(ctx2, {
                    type: 'bar',
                    data: {
                        labels: labels.length > 0 ? labels : ['Aucune donnée'],
                        datasets: [{
                            label: 'OT en cours',
                            data: values.length > 0 ? values : [0],
                            backgroundColor: colors.slice(0, labels.length || 1),
                            borderRadius: 4,
                            barPercentage: 0.5
                        }]
                    },
                    options: {
                        ...defaultOptions,
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }

            // 3. Temps de résolution par type
            const ctx3 = document.getElementById('resolutionTimeChart');
            if (ctx3) {
                new Chart(ctx3, {
                    type: 'bar',
                    data: {
                        labels: ['Électricité', 'Eau', 'Sanitaire', 'Mobilier', 'Sécurité'],
                        datasets: [{
                            label: 'Temps moyen (jours)',
                            data: [5.2, 4.8, 3.5, 2.8, 6.5],
                            backgroundColor: [COLORS.orange, COLORS.blue, COLORS.purple, COLORS.green, COLORS.red],
                            borderRadius: 4,
                            barPercentage: 0.6
                        }]
                    },
                    options: {
                        ...defaultOptions,
                        scales: { y: { beginAtZero: true, max: 8, ticks: { callback: v => v + ' j' } } }
                    }
                });
            }

            // 4. Interventions par type
            const ctx4 = document.getElementById('interventionTypeChart');
            if (ctx4) {
                new Chart(ctx4, {
                    type: 'bar',
                    data: {
                        labels: ['Électricité', 'Eau', 'Sanitaire', 'Mobilier', 'Sécurité', 'Autres'],
                        datasets: [{
                            label: 'Nombre d\'interventions',
                            data: [35, 28, 22, 18, 12, 7],
                            backgroundColor: [
                                COLORS.orange, COLORS.blue, COLORS.purple,
                                COLORS.green, COLORS.red, COLORS.grey
                            ],
                            borderRadius: 4,
                            barPercentage: 0.6
                        }]
                    },
                    options: {
                        ...defaultOptions,
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }

            console.log('✅ Graphiques de maintenance initialisés avec succès !');
        });

        // ============================================================
        // FONCTIONS POUR LES MODALS
        // ============================================================
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                // Animation d'entrée
                const content = modal.querySelector('.modal-content');
                if (content) {
                    content.style.transform = 'scale(0.95) translateY(-20px)';
                    content.style.opacity = '0';
                    setTimeout(() => {
                        content.style.transform = 'scale(1) translateY(0)';
                        content.style.opacity = '1';
                    }, 50);
                }
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                const content = modal.querySelector('.modal-content');
                if (content) {
                    content.style.transform = 'scale(0.95) translateY(-20px)';
                    content.style.opacity = '0';
                    setTimeout(() => {
                        modal.style.display = 'none';
                        document.body.style.overflow = 'auto';
                    }, 250);
                } else {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            }
        }

        // Fermer les modals avec la touche ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay').forEach(modal => {
                    if (modal.style.display === 'flex') {
                        closeModal(modal.id);
                    }
                });
            }
        });

        // Gestion des uploads dans les modals
        document.addEventListener('DOMContentLoaded', function() {
            const uploadAreas = document.querySelectorAll('.upload-area-modal');
            uploadAreas.forEach(area => {
                const fileInput = area.querySelector('input[type="file"]');
                if (fileInput) {
                    area.addEventListener('click', function(e) {
                        if (e.target.tagName !== 'INPUT') {
                            fileInput.click();
                        }
                    });
                    fileInput.addEventListener('change', function() {
                        if (this.files.length > 0) {
                            const count = this.files.length;
                            area.innerHTML = `<span>📎 ${count} fichier(s) sélectionné(s)</span>
                                             <input type="file" style="display:none;" multiple accept=".pdf,.jpg,.jpeg,.png">`;
                            area.appendChild(this);
                            area.style.borderColor = '#2a8d6c';
                            area.style.background = '#f0faf5';
                        }
                    });
                }
            });
        });
    </script>

    <script>
        class StructureManager {



            static handleCiteChange(citeId) {
                if (!citeId) return;
                getRequest('{{ url("/") }}/admin/list/batiment-cite?parent_id=' + citeId, 'batiment_id', 'select', citeId);
            }
            static handleBatiementChange(citeId) {
                if (!citeId) return;
                getRequest('{{ url("/") }}/admin/list/chambre-batiment?parent_id=' + citeId, 'chambre_id', 'select', citeId);
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            const select = document.getElementById('maintenancier_select');
            const infoDiv = document.getElementById('maintenancier_info');

            if (select && infoDiv) {
                select.addEventListener('change', function() {
                    const selected = this.options[this.selectedIndex];
                    if (this.value) {
                        const specialite = selected.dataset.specialite || '';
                        const email = selected.dataset.email || '';
                        const tel = selected.dataset.tel || '';

                        let info = '';
                        if (specialite) info += `🔧 ${specialite}`;
                        if (email) info += `${info ? ' · ' : ''}📧 ${email}`;
                        if (tel) info += `${info ? ' · ' : ''}📱 ${tel}`;

                        infoDiv.textContent = info;
                        infoDiv.style.display = 'block';
                    } else {
                        infoDiv.textContent = '';
                        infoDiv.style.display = 'none';
                    }
                });
            }
        });
    </script>

@endpush

@push('styles')
    <style>
        /* ============================================
           STYLES - MAINTENANCE
        ============================================ */
        .maintenance-container {
            max-width: 1440px;
            margin: 0 auto;
            padding: 0 4px;
        }

        .maintenance-header {
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

        .maintenance-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #0b2b4a;
            margin: 0;
        }

        .maintenance-header .subtitle {
            font-size: var(--app-font-min, 15px);
            color: #4a6f8a;
            font-weight: 400;
            margin-left: 8px;
        }

        .maintenance-meta {
            font-size: var(--app-font-min, 15px);
            color: #1e3b5c;
            font-weight: 500;
            background: #f2f6fb;
            padding: 8px 18px;
            border-radius: 30px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .campaign-badge {
            background: #eaf0f8;
            padding: 2px 12px;
            border-radius: 20px;
            font-weight: 600;
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
            cursor: pointer;
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
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
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

        /* --- Section --- */
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
            text-decoration: none;
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
        .btn-action.secondary {
            background: #e7edf5;
            color: #1f4462;
        }
        .btn-action.secondary:hover {
            background: #d5dfeb;
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
            min-width: 1000px;
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

        .dashboard-table .row-urgent {
            background: #fcf2f2;
        }

        .dashboard-table .row-in-progress {
            background: #fcf7f2;
        }

        .dashboard-table .row-resolved {
            background: #f0faf5;
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

        .priority-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
        }
        .priority-dot.critique { background: #b13e3e; }
        .priority-dot.eleve { background: #d68a3a; }
        .priority-dot.moyen { background: #f0c040; }
        .priority-dot.faible { background: #6a9ec4; }

        .table-footer {
            text-align: right;
            font-size: var(--app-font-min, 15px);
            color: #5f7e9c;
            padding: 6px 14px 2px;
        }

        /* --- Tags --- */
        .tag {
            display: inline-block;
            padding: 1px 8px;
            border-radius: 12px;
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
            background: #e7edf5;
            color: #1f4462;
            margin-right: 4px;
        }

        .tag.electricite { background: #fcead8; color: #a8621a; }
        .tag.eau { background: #dce8f5; color: #1e5f8a; }
        .tag.sanitaire { background: #e8e1f0; color: #6a4d8a; }
        .tag.mobilier { background: #ddf0e8; color: #1a6e4f; }
        .tag.securite { background: #fce1e1; color: #b13e3e; }
        .tag.climatisation { background: #dce8f5; color: #1e5f8a; }
        .tag.batiment { background: #e7edf5; color: #1f4462; }
        .tag.autre { background: #e7edf5; color: #1f4462; }

        /* --- Pagination --- */
        .pagination-container {
            padding: 12px 16px;
            display: flex;
            justify-content: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .pagination-container nav {
            display: flex;
            gap: 4px;
        }

        .pagination-container .page-link {
            padding: 6px 12px;
            border: 1px solid #dae2ec;
            border-radius: 8px;
            background: #fff;
            color: #17324b;
            text-decoration: none;
            font-size: var(--app-font-min, 15px);
            transition: 0.2s;
        }

        .pagination-container .page-link:hover {
            background: #f2f6fb;
        }

        .pagination-container .page-item.active .page-link {
            background: #1e7aa8;
            color: #fff;
            border-color: #1e7aa8;
        }

        .pagination-container .page-item.disabled .page-link {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* --- Progress Bar --- */
        .progress-bar {
            display: inline-block;
            height: 6px;
            background: #e2e9f2;
            border-radius: 8px;
            overflow: hidden;
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

        /* ============================================
           STYLES - MODALS
        ============================================ */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(11, 43, 74, 0.55);
            backdrop-filter: blur(4px);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .modal-content {
            background: #ffffff;
            border-radius: 20px;
            max-width: 580px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 20, 40, 0.25);
            transition: transform 0.25s ease, opacity 0.25s ease;
        }

        .modal-content::-webkit-scrollbar {
            width: 6px;
        }
        .modal-content::-webkit-scrollbar-track {
            background: #f2f6fb;
            border-radius: 10px;
        }
        .modal-content::-webkit-scrollbar-thumb {
            background: #b0c8dd;
            border-radius: 10px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 28px 16px;
            border-bottom: 1px solid #eaeef4;
            position: sticky;
            top: 0;
            background: #fff;
            border-radius: 20px 20px 0 0;
            z-index: 1;
        }

        .modal-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: #0b2b4a;
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 22px;
            color: #5b7b99;
            cursor: pointer;
            padding: 0 6px;
            transition: color 0.2s;
            line-height: 1;
        }

        .modal-close:hover {
            color: #b13e3e;
        }

        .modal-body {
            padding: 20px 28px;
        }

        .modal-footer {
            padding: 16px 28px 24px;
            border-top: 1px solid #eaeef4;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            background: #fafcfe;
            border-radius: 0 0 20px 20px;
            position: sticky;
            bottom: 0;
        }

        .form-row-modal {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-group-modal {
            margin-bottom: 16px;
        }

        .form-group-modal label {
            display: block;
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
            color: #1f4462;
            margin-bottom: 4px;
        }

        .form-group-modal label .required {
            color: #b13e3e;
            margin-left: 2px;
        }

        .form-control-modal {
            width: 100%;
            padding: 9px 14px;
            border: 1px solid #dae2ec;
            border-radius: 10px;
            font-size: var(--app-font-min, 15px);
            background: #f8fafc;
            color: #17324b;
            transition: border-color 0.2s, box-shadow 0.2s;
            font-family: inherit;
        }

        .form-control-modal:focus {
            outline: none;
            border-color: #1e7aa8;
            box-shadow: 0 0 0 3px rgba(30, 122, 168, 0.12);
            background: #fff;
        }

        .form-control-modal:disabled {
            background: #eef2f7;
            cursor: not-allowed;
        }

        select.form-control-modal {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%235b7b99' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
        }

        textarea.form-control-modal {
            resize: vertical;
            min-height: 60px;
        }

        .btn-modal {
            padding: 9px 28px;
            border-radius: 30px;
            border: none;
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s;
        }

        .btn-modal:hover {
            transform: translateY(-1px);
        }

        .btn-modal.primary {
            background: #1e7aa8;
            color: #fff;
        }
        .btn-modal.primary:hover {
            background: #166a94;
        }

        .btn-modal.secondary {
            background: #e7edf5;
            color: #1f4462;
        }
        .btn-modal.secondary:hover {
            background: #d5dfeb;
        }

        .btn-modal.success {
            background: #2a8d6c;
            color: #fff;
        }
        .btn-modal.success:hover {
            background: #1f7a5c;
        }

        .btn-modal.danger {
            background: #b13e3e;
            color: #fff;
        }
        .btn-modal.danger:hover {
            background: #9a3333;
        }

        .upload-area-modal {
            border: 2px dashed #dae2ec;
            border-radius: 10px;
            padding: 14px 18px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
            font-size: var(--app-font-min, 15px);
            color: #5b7b99;
            background: #f8fafc;
        }

        .upload-area-modal:hover {
            border-color: #1e7aa8;
            background: #f0f6fb;
        }

        /* --- Responsive modal --- */
        @media (max-width: 640px) {
            .modal-content {
                max-width: 100%;
                border-radius: 16px;
                max-height: 95vh;
            }
            .modal-header {
                padding: 16px 20px 12px;
            }
            .modal-body {
                padding: 16px 20px;
            }
            .modal-footer {
                padding: 12px 20px 18px;
                flex-direction: column;
            }
            .btn-modal {
                width: 100%;
                justify-content: center;
                text-align: center;
            }
            .form-row-modal {
                grid-template-columns: 1fr;
                gap: 0;
            }
            .section-actions {
                flex-direction: column;
                width: 100%;
            }
            .section-actions .btn-action {
                width: 100%;
                justify-content: center;
            }
        }

        /* --- Responsive général --- */
        @media (max-width: 768px) {
            .maintenance-header {
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
                width: 100%;
                justify-content: center;
            }
            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 480px) {
            .kpi-grid {
                grid-template-columns: 1fr;
            }
            .maintenance-meta {
                font-size: var(--app-font-min, 15px);
                padding: 6px 14px;
            }
            .dashboard-table {
                font-size: var(--app-font-min, 15px);
                min-width: 700px;
            }
        }
    </style>
@endpush