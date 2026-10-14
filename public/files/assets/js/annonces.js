// public/js/admin/annonces.js

(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        apiUrl: '/admin/annonces',
        perPage: 10,
        debounceDelay: 300,
    };

    // État
    let currentPage = 1;
    let filters = {};
    let isLoading = false;

    // ================================================================
    // INITIALISATION
    // ================================================================
    document.addEventListener('DOMContentLoaded', function() {
        initSelect2();
        initChannelSelector();
        initAudienceSelector();
        initStatutSelector();
        initFilters();
        initFileInput();
        initCharCounter();

        loadStats();
        loadTable();

        // Rafraîchissement automatique toutes les 60 secondes
        setInterval(refreshData, 60000);
    });

    // ================================================================
    // INITIALISATION DES COMPOSANTS
    // ================================================================
    function initSelect2() {
        $('.select2-multiple').select2({
            placeholder: 'Sélectionner...',
            allowClear: true,
            width: '100%',
            dropdownAutoWidth: true
        });
    }

    function initChannelSelector() {
        document.querySelectorAll('.channel-option').forEach(opt => {
            opt.addEventListener('click', function() {
                const checkbox = this.querySelector('input[type="checkbox"]');
                checkbox.checked = !checkbox.checked;
                this.classList.toggle('active');
            });
        });
    }

    function initAudienceSelector() {
        document.querySelectorAll('.audience-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.audience-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                toggleAudienceSelectors(this.dataset.audience);
            });
        });
    }

    function initStatutSelector() {
        document.getElementById('annonceStatut').addEventListener('change', function() {
            const planifContainer = document.getElementById('planificationContainer');
            planifContainer.style.display = this.value === 'planifie' ? 'block' : 'none';
        });
    }

    function initFilters() {
        const debounce = debounce(loadTable, CONFIG.debounceDelay);

        document.getElementById('searchAnnonce').addEventListener('input', debounce);
        document.getElementById('filterStatut').addEventListener('change', loadTable);
        document.getElementById('filterCanal').addEventListener('change', loadTable);
        document.getElementById('filterDateDebut').addEventListener('change', loadTable);
        document.getElementById('filterDateFin').addEventListener('change', loadTable);
    }

    function initFileInput() {
        document.getElementById('annonceFichiers').addEventListener('change', handleFiles);
    }

    function initCharCounter() {
        const input = document.getElementById('annonceTitre');
        const counter = document.getElementById('charCount');
        input.addEventListener('input', function() {
            counter.textContent = this.value.length;
        });
    }

    // ================================================================
    // CHARGEMENT DES DONNÉES
    // ================================================================
    function loadStats() {
        fetch('/admin/annonces/stats')
            .then(res => res.json())
            .then(data => {
                document.getElementById('totalAnnonces').textContent = data.total || 0;
                document.getElementById('publiees').textContent = data.publiees || 0;
                document.getElementById('planifiees').textContent = data.planifiees || 0;
                document.getElementById('brouillons').textContent = data.brouillons || 0;
            })
            .catch(() => {
                // Fallback sur les données du serveur
                const stats = window.initialStats || {};
                document.getElementById('totalAnnonces').textContent = stats.total || 0;
                document.getElementById('publiees').textContent = stats.publiees || 0;
                document.getElementById('planifiees').textContent = stats.planifiees || 0;
                document.getElementById('brouillons').textContent = stats.brouillons || 0;
            });
    }

    function loadTable() {
        if (isLoading) return;
        isLoading = true;

        const container = document.getElementById('announcementsList');
        container.innerHTML = `
            <div class="table-loading">
                <div class="spinner-border text-primary spinner" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            </div>
        `;

        // Récupérer les filtres
        filters = {
            search: document.getElementById('searchAnnonce').value,
            statut: document.getElementById('filterStatut').value,
            canal: document.getElementById('filterCanal').value,
            date_debut: document.getElementById('filterDateDebut').value,
            date_fin: document.getElementById('filterDateFin').value,
            page: currentPage,
            per_page: CONFIG.perPage
        };

        // Construire l'URL
        const params = new URLSearchParams(filters);
        const url = `/admin/annonces/datatable?${params.toString()}`;

        fetch(url)
            .then(res => res.json())
            .then(data => {
                renderTable(data);
                renderPagination(data);
                document.getElementById('totalFiltered').textContent = `${data.total || 0} annonces`;
            })
            .catch(err => {
                console.error('Erreur de chargement:', err);
                container.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Erreur lors du chargement des annonces. Veuillez réessayer.
                        <button class="btn btn-sm btn-outline-danger ms-2" onclick="refreshTable()">
                            <i class="fas fa-sync me-1"></i> Réessayer
                        </button>
                    </div>
                `;
            })
            .finally(() => {
                isLoading = false;
            });
    }

    // ================================================================
    // RENDU
    // ================================================================
    function renderTable(data) {
        const container = document.getElementById('announcementsList');
        const items = data.data || [];

        if (items.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-bullhorn fa-3x text-muted d-block mb-3"></i>
                    <h5 class="text-muted">Aucune annonce trouvée</h5>
                    <p class="text-muted small">Créez votre première annonce avec le formulaire ci-dessus</p>
                </div>
            `;
            return;
        }

        let html = '';
        items.forEach((annonce, index) => {
            const statusClass = getStatusClass(annonce.statut);
            const statusLabel = getStatusLabel(annonce.statut);

            // Canaux
            let channelsHtml = '';
            const channelLabels = { flux: 'Flux', sms: 'SMS', email: 'Email', push: 'Push' };
            (annonce.canaux || []).forEach(canal => {
                channelsHtml += `<span class="channel-badge ${canal}">${channelLabels[canal] || canal}</span>`;
            });

            // Audience
            const audienceLabels = {
                all: 'Tous les étudiants',
                cite: 'Par cité',
                batiment: 'Par bâtiment',
                chambre: 'Par chambre',
                specific: 'Sélection spécifique'
            };

            html += `
                <div class="announcement-card fade-in">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="card-title">
                                ${annonce.titre || 'Sans titre'}
                                ${annonce.priorite !== 'normale' ?
                `<span class="badge bg-${annonce.priorite === 'urgente' ? 'danger' : 'warning'} ms-2" style="font-size:10px;">
                                        ${annonce.priorite.toUpperCase()}
                                    </span>` : ''}
                            </div>
                            <div class="card-meta">
                                <i class="far fa-calendar-alt me-1"></i>
                                ${formatDate(annonce.created_at)}
                                ${annonce.date_publication ? ` • <i class="far fa-clock me-1"></i> Publié le ${formatDate(annonce.date_publication)}` : ''}
                                ${annonce.createur ? ` • <i class="far fa-user me-1"></i> ${annonce.createur.name}` : ''}
                            </div>
                        </div>
                        <div>
                            <span class="status-badge ${statusClass}">${statusLabel}</span>
                        </div>
                    </div>

                    <div class="card-content">
                        ${annonce.contenu ? (annonce.contenu.length > 200 ? annonce.contenu.substring(0, 200) + '...' : annonce.contenu) : ''}
                    </div>

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3 pt-3 border-top">
                        <div class="channels">
                            ${channelsHtml}
                            <span class="badge bg-light text-dark">
                                <i class="fas fa-users me-1"></i>
                                ${audienceLabels[annonce.audience] || annonce.audience}
                            </span>
                        </div>
                        <div class="d-flex gap-2">
                            ${annonce.statut === 'publie' ? `
                                <span class="text-muted small me-2">
                                    <i class="far fa-eye me-1"></i> ${annonce.vues || 0}
                                    <i class="far fa-hand-peace ms-2 me-1"></i> ${annonce.reactions || 0}
                                </span>
                            ` : ''}
                            <button class="btn btn-sm btn-outline-info" onclick="viewAnnouncement('${annonce.id_annonce}')" title="Voir">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-primary" onclick="editAnnouncement('${annonce.id_annonce}')" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </button>
                            ${annonce.statut !== 'publie' ? `
                                <button class="btn btn-sm btn-outline-success" onclick="publishNow('${annonce.id_annonce}')" title="Publier">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            ` : ''}
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteAnnouncement('${annonce.id_annonce}')" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
        document.getElementById('paginationInfo').textContent =
            `Affichage de ${data.from || 0} à ${data.to || 0} sur ${data.total || 0} annonces`;
    }

    function renderPagination(data) {
        const container = document.getElementById('paginationControls');
        const current = data.current_page || 1;
        const last = data.last_page || 1;

        if (last <= 1) {
            container.innerHTML = `
                <li class="page-item disabled"><a class="page-link" href="#">Précédent</a></li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item disabled"><a class="page-link" href="#">Suivant</a></li>
            `;
            return;
        }

        let html = `<li class="page-item ${current === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${current - 1}">Précédent</a>
        </li>`;

        // Afficher max 5 pages
        let startPage = Math.max(1, current - 2);
        let endPage = Math.min(last, current + 2);

        if (startPage > 1) {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
            if (startPage > 2) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }

        for (let i = startPage; i <= endPage; i++) {
            html += `<li class="page-item ${i === current ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>`;
        }

        if (endPage < last) {
            if (endPage < last - 1) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${last}">${last}</a></li>`;
        }

        html += `<li class="page-item ${current === last ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${current + 1}">Suivant</a>
        </li>`;

        container.innerHTML = html;

        // Événements de pagination
        container.querySelectorAll('.page-link[data-page]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = parseInt(this.dataset.page);
                if (page >= 1 && page <= last) {
                    currentPage = page;
                    loadTable();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });
        });
    }

    // ================================================================
    // ACTIONS
    // ================================================================
    window.saveAnnouncement = function() {
        const data = getFormData();
        if (!validateForm(data)) return;

        const id = document.getElementById('editId').value;
        const url = id ? `/admin/annonces/${id}` : '/admin/annonces';
        const method = id ? 'PUT' : 'POST';

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'X-HTTP-Method-Override': method
            },
            body: JSON.stringify(data)
        })
            .then(res => res.json())
            .then(() => {
                resetForm();
                loadTable();
                loadStats();
                showToast('Annonce enregistrée avec succès', 'success');
            })
            .catch(err => {
                showToast('Erreur lors de l\'enregistrement', 'danger');
            });
    };

    window.publishAnnouncement = function() {
        const data = getFormData();
        if (!validateForm(data)) return;

        data.statut = document.getElementById('annonceStatut').value;

        const id = document.getElementById('editId').value;
        const url = id ? `/admin/annonces/${id}` : '/admin/annonces';
        const method = id ? 'PUT' : 'POST';

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'X-HTTP-Method-Override': method
            },
            body: JSON.stringify(data)
        })
            .then(res => res.json())
            .then(() => {
                resetForm();
                loadTable();
                loadStats();
                const message = data.statut === 'planifie' ? 'Annonce planifiée' : 'Annonce publiée';
                showToast(message + ' avec succès', 'success');
            })
            .catch(err => {
                showToast('Erreur lors de la publication', 'danger');
            });
    };

    window.publishNow = function(id) {
        if (!confirm('Publier cette annonce immédiatement ?')) return;

        fetch(`/admin/annonces/${id}/publish`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        })
            .then(res => res.json())
            .then(() => {
                loadTable();
                loadStats();
                showToast('Annonce publiée avec succès', 'success');
            })
            .catch(() => {
                showToast('Erreur lors de la publication', 'danger');
            });
    };

    window.editAnnouncement = function(id) {
        fetch(`/admin/annonces/${id}`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('editId').value = id;
                document.getElementById('annonceTitre').value = data.titre || '';
                document.getElementById('annonceContenu').value = data.contenu || '';
                document.getElementById('annoncePriorite').value = data.priorite || 'normale';
                document.getElementById('annonceStatut').value = data.statut || 'brouillon';

                // Canaux
                document.querySelectorAll('.channel-option').forEach(opt => {
                    const val = opt.dataset.channel;
                    const cb = opt.querySelector('input');
                    if (data.canaux && data.canaux.includes(val)) {
                        cb.checked = true;
                        opt.classList.add('active');
                    } else {
                        cb.checked = false;
                        opt.classList.remove('active');
                    }
                });

                // Audience
                document.querySelectorAll('.audience-btn').forEach(b => {
                    b.classList.toggle('active', b.dataset.audience === data.audience);
                });
                toggleAudienceSelectors(data.audience);

                // Planification
                if (data.statut === 'planifie' && data.date_planification) {
                    document.getElementById('planificationContainer').style.display = 'block';
                    document.getElementById('annonceDatePlanifiee').value = data.date_planification.replace(' ', 'T').slice(0, 16);
                }

                showToast('Annonce chargée pour modification', 'info');
                document.getElementById('announcementForm').scrollIntoView({ behavior: 'smooth' });
            })
            .catch(() => {
                showToast('Erreur lors du chargement de l\'annonce', 'danger');
            });
    };

    window.deleteAnnouncement = function(id) {
        if (!confirm('Supprimer cette annonce définitivement ?')) return;

        fetch(`/admin/annonces/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        })
            .then(res => res.json())
            .then(() => {
                loadTable();
                loadStats();
                showToast('Annonce supprimée', 'info');
            })
            .catch(() => {
                showToast('Erreur lors de la suppression', 'danger');
            });
    };

    window.viewAnnouncement = function(id) {
        showToast('Affichage de l\'annonce...', 'info');
    };

    window.resetForm = function() {
        document.getElementById('editId').value = '';
        document.getElementById('annonceTitre').value = '';
        document.getElementById('annonceContenu').value = '';
        document.getElementById('annoncePriorite').value = 'normale';
        document.getElementById('annonceStatut').value = 'brouillon';
        document.getElementById('planificationContainer').style.display = 'none';

        document.querySelectorAll('.channel-option').forEach(opt => {
            const cb = opt.querySelector('input');
            const isFlux = opt.dataset.channel === 'flux';
            cb.checked = isFlux;
            opt.classList.toggle('active', isFlux);
        });

        document.querySelectorAll('.audience-btn').forEach(b => {
            b.classList.toggle('active', b.dataset.audience === 'all');
        });
        toggleAudienceSelectors('all');

        $('.select2-multiple').val(null).trigger('change');

        document.getElementById('charCount').textContent = '0';
        showToast('Formulaire réinitialisé', 'info');
    };

    window.refreshTable = function() {
        loadTable();
        loadStats();
        showToast('Données rafraîchies', 'info');
    };

    window.exportAnnonces = function() {
        const params = new URLSearchParams(filters);
        window.open(`/admin/annonces/export?${params.toString()}`, '_blank');
        showToast('Export en cours...', 'info');
    };

    window.insertText = function(text) {
        const textarea = document.getElementById('annonceContenu');
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const before = textarea.value.substring(0, start);
        const after = textarea.value.substring(end);
        textarea.value = before + text + after;
        textarea.focus();
        textarea.selectionStart = start + text.length;
        textarea.selectionEnd = start + text.length;
    };

    // ================================================================
    // UTILITAIRES
    // ================================================================
    function getFormData() {
        const canaux = [];
        document.querySelectorAll('.channel-option input:checked').forEach(cb => {
            canaux.push(cb.value);
        });

        let audience = 'all';
        document.querySelectorAll('.audience-btn.active').forEach(btn => {
            audience = btn.dataset.audience;
        });

        return {
            titre: document.getElementById('annonceTitre').value,
            contenu: document.getElementById('annonceContenu').value,
            canaux: canaux,
            audience: audience,
            audience_cible: getAudienceCible(),
            priorite: document.getElementById('annoncePriorite').value,
            date_planification: document.getElementById('annonceDatePlanifiee').value || null,
            date_expiration: null,
            pieces_jointes: []
        };
    }

    function getAudienceCible() {
        const audience = document.querySelector('.audience-btn.active')?.dataset?.audience || 'all';
        const cible = {};

        if (audience === 'cite') {
            cible.cites = $('#selectCites').val() || [];
        }
        if (audience === 'batiment') {
            cible.batiments = $('#selectBatiments').val() || [];
        }
        if (audience === 'specific') {
            cible.etudiants = $('#selectEtudiants').val() || [];
        }

        return cible;
    }

    function validateForm(data) {
        if (!data.titre?.trim()) {
            showToast('Le titre est obligatoire', 'warning');
            return false;
        }
        if (!data.contenu?.trim()) {
            showToast('Le contenu est obligatoire', 'warning');
            return false;
        }
        if (data.canaux?.length === 0) {
            showToast('Sélectionnez au moins un canal', 'warning');
            return false;
        }
        if (data.titre.length > 100) {
            showToast('Le titre ne doit pas dépasser 100 caractères', 'warning');
            return false;
        }
        return true;
    }

    function toggleAudienceSelectors(audience) {
        const cible = document.getElementById('audienceCible');
        const specific = document.getElementById('audienceSpecific');
        cible.style.display = ['cite', 'batiment', 'chambre'].includes(audience) ? 'block' : 'none';
        specific.style.display = audience === 'specific' ? 'block' : 'none';
    }

    function handleFiles(e) {
        const files = e.target.files;
        const list = document.getElementById('fileList');
        if (!list) return;

        list.innerHTML = '';
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const div = document.createElement('div');
            div.className = 'd-flex justify-content-between align-items-center bg-light p-2 mb-1 rounded';
            div.innerHTML = `
                <span><i class="fas fa-file me-2"></i> ${file.name} (${formatFileSize(file.size)})</span>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            list.appendChild(div);
        }
    }

    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' o';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' Ko';
        return (bytes / 1048576).toFixed(1) + ' Mo';
    }

    function getStatusClass(status) {
        const map = { publie: 'publie', brouillon: 'brouillon', planifie: 'planifie', annule: 'annule' };
        return map[status] || 'brouillon';
    }

    function getStatusLabel(status) {
        const map = { publie: 'Publié', brouillon: 'Brouillon', planifie: 'Planifié', annule: 'Annulé' };
        return map[status] || status;
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        try {
            const d = new Date(dateStr);
            return d.toLocaleDateString('fr-FR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch {
            return dateStr;
        }
    }

    function debounce(func, delay) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    }

    function refreshData() {
        loadStats();
        // Ne recharger le tableau que si la page est visible
        if (!document.hidden) {
            loadTable();
        }
    }

    function showToast(message, type = 'info') {
        const colors = {
            success: 'bg-success',
            danger: 'bg-danger',
            warning: 'bg-warning',
            info: 'bg-info'
        };

        const container = document.getElementById('toastContainer') || createToastContainer();
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white ${colors[type] || 'bg-info'} border-0`;
        toast.role = 'alert';
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        container.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
        bsToast.show();
        toast.addEventListener('hidden.bs.toast', () => toast.remove());
    }

    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
        return container;
    }

    // Exposer les fonctions globalement
    window.refreshTable = refreshTable;
    window.exportAnnonces = exportAnnonces;

})();