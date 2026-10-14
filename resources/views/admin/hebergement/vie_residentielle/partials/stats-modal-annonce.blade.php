{{-- resources/views/admin/annonces/partials/stats-modal.blade.php --}}

<!-- ============================================ -->
<!-- MODAL : STATISTIQUES DÉTAILLÉES              -->
<!-- ============================================ -->
<div class="modal fade" id="statsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <!-- En-tête -->
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div>
                    <h5 class="modal-title text-white">
                        <i class="fas fa-chart-bar me-2"></i>
                        Statistiques détaillées
                    </h5>
                    <small class="text-white-50" id="statsModalSubtitle">Analyse des performances des annonces</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <!-- Corps -->
            <div class="modal-body" id="statsContent">
                <!-- Indicateur de chargement -->
                <div id="statsLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-3 text-muted">Chargement des statistiques...</p>
                </div>

                <!-- Contenu des statistiques (caché par défaut) -->
                <div id="statsData" style="display: none;">
                    <!-- En-tête des statistiques -->
                    <div class="row g-3 mb-4" id="statsSummary">
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted">Total vues</h6>
                                    <h3 class="mb-0" id="statTotalVues">0</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted">Clics</h6>
                                    <h3 class="mb-0" id="statTotalClics">0</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted">Réactions</h6>
                                    <h3 class="mb-0" id="statTotalReactions">0</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted">Partages</h6>
                                    <h3 class="mb-0" id="statTotalPartages">0</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Graphique des statistiques par jour -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-chart-line me-2"></i>
                                Évolution des performances
                            </h6>
                        </div>
                        <div class="card-body">
                            <canvas id="statsChart" height="250"></canvas>
                        </div>
                    </div>

                    <!-- Tableau des statistiques par canal -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-table me-2"></i>
                                Détails par canal
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover" id="statsTable">
                                    <thead>
                                    <tr>
                                        <th>Canal</th>
                                        <th class="text-center">Envoyés</th>
                                        <th class="text-center">Ouverts</th>
                                        <th class="text-center">Clics</th>
                                        <th class="text-center">Taux d'ouverture</th>
                                        <th class="text-center">Taux de clic</th>
                                    </tr>
                                    </thead>
                                    <tbody id="statsTableBody">
                                    <!-- Rempli par JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pied de page -->
            <div class="modal-footer">
                <div class="me-auto">
                    <span class="text-muted small" id="statsLastUpdate">
                        Dernière mise à jour : <span id="statsUpdateTime">-</span>
                    </span>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Fermer
                </button>
                <button type="button" class="btn btn-primary" onclick="refreshStats()">
                    <i class="fas fa-sync me-1"></i> Rafraîchir
                </button>
                <button type="button" class="btn btn-success" onclick="exportStats()">
                    <i class="fas fa-file-export me-1"></i> Exporter
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- SCRIPTS POUR LE MODAL                        -->
<!-- ============================================ -->
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // ================================================================
        // GESTION DES STATISTIQUES
        // ================================================================

        let statsChart = null;
        let currentStatsData = null;

        /**
         * Ouvrir le modal et charger les statistiques
         */
        window.openStatsModal = function(annonceId = null) {
            const modal = new bootstrap.Modal(document.getElementById('statsModal'));

            // Réinitialiser l'affichage
            document.getElementById('statsLoading').style.display = 'block';
            document.getElementById('statsData').style.display = 'none';

            modal.show();

            // Charger les statistiques
            loadStats(annonceId);
        };

        /**
         * Charger les statistiques
         */
        function loadStats(annonceId = null) {
            const url = annonceId
                ? `/admin/annonces/${annonceId}/stats`
                : '/admin/annonces/stats/detaillees';

            fetch(url)
                .then(res => {
                    if (!res.ok) throw new Error('Erreur de chargement');
                    return res.json();
                })
                .then(data => {
                    currentStatsData = data;
                    renderStats(data);
                    document.getElementById('statsLoading').style.display = 'none';
                    document.getElementById('statsData').style.display = 'block';
                    document.getElementById('statsUpdateTime').textContent = new Date().toLocaleString();

                    // Mettre à jour le sous-titre
                    if (annonceId) {
                        document.getElementById('statsModalSubtitle').textContent =
                            `Statistiques pour l'annonce #${annonceId}`;
                    } else {
                        document.getElementById('statsModalSubtitle').textContent =
                            'Analyse des performances globales';
                    }
                })
                .catch(err => {
                    console.error('Erreur:', err);
                    document.getElementById('statsLoading').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Erreur lors du chargement des statistiques.
                        <button class="btn btn-sm btn-outline-danger ms-2" onclick="loadStats()">
                            <i class="fas fa-sync me-1"></i> Réessayer
                        </button>
                    </div>
                `;
                });
        }

        /**
         * Rafraîchir les statistiques
         */
        window.refreshStats = function() {
            const annonceId = currentStatsData?.annonce_id || null;
            document.getElementById('statsLoading').style.display = 'block';
            document.getElementById('statsData').style.display = 'none';
            loadStats(annonceId);
            showToast('Statistiques rafraîchies', 'info');
        };

        /**
         * Exporter les statistiques
         */
        window.exportStats = function() {
            if (!currentStatsData) {
                showToast('Aucune donnée à exporter', 'warning');
                return;
            }

            // Générer un CSV
            const headers = ['Date', 'Vues', 'Clics', 'Réactions', 'Partages'];
            const rows = currentStatsData.par_jour || [];

            let csv = headers.join(',') + '\n';
            rows.forEach(row => {
                csv += `${row.date},${row.vues},${row.clics},${row.reactions},${row.partages}\n`;
            });

            // Télécharger
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `statistiques_annonces_${new Date().toISOString().slice(0,10)}.csv`;
            link.click();
            URL.revokeObjectURL(link.href);

            showToast('Export CSV effectué', 'success');
        };

        /**
         * Afficher les statistiques
         */
        function renderStats(data) {
            // Mettre à jour les résumés
            document.getElementById('statTotalVues').textContent = data.total_vues || 0;
            document.getElementById('statTotalClics').textContent = data.total_clics || 0;
            document.getElementById('statTotalReactions').textContent = data.total_reactions || 0;
            document.getElementById('statTotalPartages').textContent = data.total_partages || 0;

            // Tableau par canal
            renderStatsTable(data.par_canal || []);

            // Graphique
            renderStatsChart(data.par_jour || []);
        }

        /**
         * Rendre le tableau des statistiques par canal
         */
        function renderStatsTable(canalData) {
            const tbody = document.getElementById('statsTableBody');

            if (!canalData || canalData.length === 0) {
                tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-muted py-3">
                        <i class="fas fa-inbox me-2"></i> Aucune donnée par canal disponible
                    </td>
                </tr>
            `;
                return;
            }

            const canalLabels = {
                'flux': '📡 Flux d\'actualité',
                'sms': '📱 SMS',
                'email': '✉️ Email',
                'push': '🔔 Push'
            };

            let html = '';
            canalData.forEach(canal => {
                const tauxOuverture = canal.envoyes > 0
                    ? ((canal.ouverts / canal.envoyes) * 100).toFixed(1)
                    : 0;
                const tauxClic = canal.ouverts > 0
                    ? ((canal.clics / canal.ouverts) * 100).toFixed(1)
                    : 0;

                html += `
                <tr>
                    <td><strong>${canalLabels[canal.canal] || canal.canal}</strong></td>
                    <td class="text-center">${canal.envoyes || 0}</td>
                    <td class="text-center">${canal.ouverts || 0}</td>
                    <td class="text-center">${canal.clics || 0}</td>
                    <td class="text-center">
                        <div class="progress" style="height: 6px; width: 80px; display: inline-block;">
                            <div class="progress-bar bg-primary"
                                 style="width: ${tauxOuverture}%;"
                                 role="progressbar"
                                 aria-valuenow="${tauxOuverture}"
                                 aria-valuemin="0"
                                 aria-valuemax="100">
                            </div>
                        </div>
                        <span class="ms-1">${tauxOuverture}%</span>
                    </td>
                    <td class="text-center">
                        <div class="progress" style="height: 6px; width: 80px; display: inline-block;">
                            <div class="progress-bar bg-success"
                                 style="width: ${tauxClic}%;"
                                 role="progressbar"
                                 aria-valuenow="${tauxClic}"
                                 aria-valuemin="0"
                                 aria-valuemax="100">
                            </div>
                        </div>
                        <span class="ms-1">${tauxClic}%</span>
                    </td>
                </tr>
            `;
            });

            tbody.innerHTML = html;
        }

        /**
         * Rendre le graphique des statistiques
         */
        function renderStatsChart(dailyData) {
            const ctx = document.getElementById('statsChart').getContext('2d');

            if (statsChart) {
                statsChart.destroy();
            }

            if (!dailyData || dailyData.length === 0) {
                // Afficher un message si aucune donnée
                document.getElementById('statsChart').parentElement.innerHTML = `
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-chart-bar fa-2x d-block mb-2"></i>
                    Aucune donnée journalière disponible
                </div>
            `;
                return;
            }

            // Préparer les données
            const labels = dailyData.map(d => {
                const date = new Date(d.date);
                return date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' });
            });

            statsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Vues',
                            data: dailyData.map(d => d.vues || 0),
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4
                        },
                        {
                            label: 'Clics',
                            data: dailyData.map(d => d.clics || 0),
                            borderColor: '#48bb78',
                            backgroundColor: 'rgba(72, 187, 120, 0.1)',
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4
                        },
                        {
                            label: 'Réactions',
                            data: dailyData.map(d => d.reactions || 0),
                            borderColor: '#ed8936',
                            backgroundColor: 'rgba(237, 137, 54, 0.1)',
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: ${context.parsed.y}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });
        }

        // ================================================================
        // INITIALISATION
        // ================================================================
        document.addEventListener('DOMContentLoaded', function() {
            // Écouter l'ouverture du modal pour redimensionner le graphique
            document.getElementById('statsModal').addEventListener('shown.bs.modal', function() {
                if (statsChart) {
                    setTimeout(() => statsChart.resize(), 300);
                }
            });
        });

        // Exposer les fonctions globalement
        window.openStatsModal = openStatsModal;
        window.refreshStats = refreshStats;
        window.exportStats = exportStats;

        console.log('📊 Module de statistiques chargé');
    </script>
@endpush