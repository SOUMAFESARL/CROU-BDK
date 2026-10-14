{{-- resources/views/admin/annonces/partials/filters.blade.php --}}

<!-- ============================================ -->
<!-- FILTRES ET RECHERCHE                         -->
<!-- ============================================ -->
<div class="filter-section no-print">
    <div class="row g-3">
        <!-- Recherche -->
        <div class="col-md-3">
            <label class="form-label fw-bold">
                <i class="fas fa-search me-1"></i> Recherche
            </label>
            <div class="input-group">
                <input type="text"
                       class="form-control"
                       id="searchAnnonce"
                       placeholder="Titre, contenu, auteur..."
                       value="{{ request('search') ?? '' }}">
                <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('searchAnnonce').value='';applyFilters();">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <small class="text-muted">Recherche par titre, contenu ou auteur</small>
        </div>

        <!-- Filtre Statut -->
        <div class="col-md-2">
            <label class="form-label fw-bold">
                <i class="fas fa-filter me-1"></i> Statut
            </label>
            <select class="form-select" id="filterStatut">
                <option value="">Tous les statuts</option>
                <option value="publie" {{ request('statut') === 'publie' ? 'selected' : '' }}>
                    <i class="fas fa-check-circle text-success me-1"></i> Publié
                </option>
                <option value="brouillon" {{ request('statut') === 'brouillon' ? 'selected' : '' }}>
                    <i class="fas fa-pencil-alt text-secondary me-1"></i> Brouillon
                </option>
                <option value="planifie" {{ request('statut') === 'planifie' ? 'selected' : '' }}>
                    <i class="fas fa-clock text-warning me-1"></i> Planifié
                </option>
                <option value="annule" {{ request('statut') === 'annule' ? 'selected' : '' }}>
                    <i class="fas fa-times-circle text-danger me-1"></i> Annulé
                </option>
            </select>
        </div>

        <!-- Filtre Canal -->
        <div class="col-md-2">
            <label class="form-label fw-bold">
                <i class="fas fa-hashtag me-1"></i> Canal
            </label>
            <select class="form-select" id="filterCanal">
                <option value="">Tous les canaux</option>
                <option value="flux" {{ request('canal') === 'flux' ? 'selected' : '' }}>
                    <i class="fas fa-rss text-primary me-1"></i> Flux d'actualité
                </option>
                <option value="sms" {{ request('canal') === 'sms' ? 'selected' : '' }}>
                    <i class="fas fa-sms text-success me-1"></i> SMS
                </option>
                <option value="email" {{ request('canal') === 'email' ? 'selected' : '' }}>
                    <i class="fas fa-envelope text-warning me-1"></i> Email
                </option>
                <option value="push" {{ request('canal') === 'push' ? 'selected' : '' }}>
                    <i class="fas fa-bell text-danger me-1"></i> Push
                </option>
            </select>
        </div>

        <!-- Filtre Priorité -->
        <div class="col-md-2">
            <label class="form-label fw-bold">
                <i class="fas fa-flag me-1"></i> Priorité
            </label>
            <select class="form-select" id="filterPriorite">
                <option value="">Toutes</option>
                <option value="normale" {{ request('priorite') === 'normale' ? 'selected' : '' }}>
                    <i class="fas fa-circle text-secondary me-1"></i> Normale
                </option>
                <option value="haute" {{ request('priorite') === 'haute' ? 'selected' : '' }}>
                    <i class="fas fa-circle text-warning me-1"></i> Haute
                </option>
                <option value="urgente" {{ request('priorite') === 'urgente' ? 'selected' : '' }}>
                    <i class="fas fa-circle text-danger me-1"></i> Urgente
                </option>
            </select>
        </div>

        <!-- Filtre Période -->
        <div class="col-md-3">
            <label class="form-label fw-bold">
                <i class="fas fa-calendar me-1"></i> Période de création
            </label>
            <div class="input-group input-group-sm">
                <input type="date"
                       class="form-control"
                       id="filterDateDebut"
                       value="{{ request('date_debut') ?? '' }}"
                       placeholder="Du">
                <span class="input-group-text">à</span>
                <input type="date"
                       class="form-control"
                       id="filterDateFin"
                       value="{{ request('date_fin') ?? '' }}"
                       placeholder="Au">
            </div>
        </div>
    </div>

    <!-- Boutons d'action -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-primary" onclick="applyFilters()">
                    <i class="fas fa-search me-1"></i> Filtrer
                </button>
                <button class="btn btn-secondary" onclick="resetFilters()">
                    <i class="fas fa-undo me-1"></i> Réinitialiser
                </button>
                <button class="btn btn-outline-success" onclick="saveFilters()">
                    <i class="fas fa-save me-1"></i> Sauvegarder les filtres
                </button>
                <div class="ms-auto">
                    <span class="badge bg-primary" id="activeFiltersCount">
                        <i class="fas fa-filter me-1"></i>
                        <span id="filterCount">0</span> filtre(s) actif(s)
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres actifs (tags) -->
    <div class="row mt-2" id="activeFiltersTags" style="display: none;">
        <div class="col-12">
            <div class="d-flex flex-wrap gap-2">
                <span class="fw-bold me-2">Filtres actifs :</span>
                <div id="filterTagsContainer">
                    <!-- Les tags seront ajoutés dynamiquement -->
                </div>
                <button class="btn btn-sm btn-link text-danger" onclick="resetFilters()">
                    <i class="fas fa-times-circle me-1"></i> Tout effacer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts associés aux filtres -->
@push('scripts')
    <script>
        // ================================================================
        // GESTION DES FILTRES
        // ================================================================

        /**
         * Appliquer les filtres et recharger les données
         */
        window.applyFilters = function() {
            const filters = getFilters();

            // Mettre à jour l'URL avec les paramètres de filtre
            const params = new URLSearchParams(filters);
            const newUrl = window.location.pathname + '?' + params.toString();
            window.history.pushState({ path: newUrl }, '', newUrl);

            // Recharger le tableau
            if (typeof loadTable === 'function') {
                loadTable();
            } else {
                // Fallback: recharger la page
                window.location.href = newUrl;
            }

            updateFilterTags(filters);
        };

        /**
         * Réinitialiser tous les filtres
         */
        window.resetFilters = function() {
            document.getElementById('searchAnnonce').value = '';
            document.getElementById('filterStatut').value = '';
            document.getElementById('filterCanal').value = '';
            document.getElementById('filterPriorite').value = '';
            document.getElementById('filterDateDebut').value = '';
            document.getElementById('filterDateFin').value = '';

            // Mettre à jour l'URL
            const url = window.location.pathname;
            window.history.pushState({ path: url }, '', url);

            // Recharger les données
            if (typeof loadTable === 'function') {
                loadTable();
            } else {
                window.location.href = url;
            }

            updateFilterTags({});
            showToast('Filtres réinitialisés', 'info');
        };

        /**
         * Récupérer les valeurs des filtres
         */
        function getFilters() {
            return {
                search: document.getElementById('searchAnnonce').value.trim(),
                statut: document.getElementById('filterStatut').value,
                canal: document.getElementById('filterCanal').value,
                priorite: document.getElementById('filterPriorite').value,
                date_debut: document.getElementById('filterDateDebut').value,
                date_fin: document.getElementById('filterDateFin').value
            };
        }

        /**
         * Sauvegarder les filtres dans localStorage
         */
        window.saveFilters = function() {
            const filters = getFilters();
            localStorage.setItem('annonce_filters', JSON.stringify(filters));
            showToast('Filtres sauvegardés', 'success');
        };

        /**
         * Charger les filtres sauvegardés
         */
        window.loadSavedFilters = function() {
            try {
                const saved = localStorage.getItem('annonce_filters');
                if (saved) {
                    const filters = JSON.parse(saved);
                    document.getElementById('searchAnnonce').value = filters.search || '';
                    document.getElementById('filterStatut').value = filters.statut || '';
                    document.getElementById('filterCanal').value = filters.canal || '';
                    document.getElementById('filterPriorite').value = filters.priorite || '';
                    document.getElementById('filterDateDebut').value = filters.date_debut || '';
                    document.getElementById('filterDateFin').value = filters.date_fin || '';
                    applyFilters();
                }
            } catch (e) {
                console.warn('Erreur lors du chargement des filtres:', e);
            }
        };

        /**
         * Mettre à jour l'affichage des tags de filtres
         */
        function updateFilterTags(filters) {
            const container = document.getElementById('filterTagsContainer');
            const tagsContainer = document.getElementById('activeFiltersTags');
            const countElement = document.getElementById('filterCount');

            if (!container) return;

            // Nettoyer les tags existants
            container.innerHTML = '';

            // Compter les filtres actifs
            let count = 0;
            const labels = {
                search: 'Recherche',
                statut: 'Statut',
                canal: 'Canal',
                priorite: 'Priorité',
                date_debut: 'Date début',
                date_fin: 'Date fin'
            };

            const values = {
                statut: {
                    'publie': 'Publié',
                    'brouillon': 'Brouillon',
                    'planifie': 'Planifié',
                    'annule': 'Annulé'
                },
                canal: {
                    'flux': 'Flux d\'actualité',
                    'sms': 'SMS',
                    'email': 'Email',
                    'push': 'Push'
                },
                priorite: {
                    'normale': 'Normale',
                    'haute': 'Haute',
                    'urgente': 'Urgente'
                }
            };

            // Ajouter les tags pour chaque filtre actif
            Object.keys(filters).forEach(key => {
                const value = filters[key];
                if (value && value.trim && value.trim() !== '') {
                    count++;
                    let label = labels[key] || key;
                    let displayValue = value;

                    // Formater la valeur selon le type de filtre
                    if (key === 'statut' && values.statut[value]) {
                        displayValue = values.statut[value];
                    } else if (key === 'canal' && values.canal[value]) {
                        displayValue = values.canal[value];
                    } else if (key === 'priorite' && values.priorite[value]) {
                        displayValue = values.priorite[value];
                    } else if (key === 'search') {
                        displayValue = `"${value}"`;
                    }

                    const tag = document.createElement('span');
                    tag.className = 'badge bg-light text-dark border d-inline-flex align-items-center gap-1 p-2';
                    tag.innerHTML = `
                    <i class="fas fa-tag text-primary me-1"></i>
                    ${label}: <strong>${displayValue}</strong>
                    <button class="btn btn-sm btn-link text-danger p-0 ms-1"
                            onclick="removeFilter('${key}')"
                            title="Supprimer ce filtre">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                    container.appendChild(tag);
                }
            });

            // Mettre à jour le compteur
            countElement.textContent = count;

            // Afficher ou masquer la section des tags
            tagsContainer.style.display = count > 0 ? 'block' : 'none';
        }

        /**
         * Supprimer un filtre spécifique
         */
        window.removeFilter = function(key) {
            const element = document.getElementById(getFilterElementId(key));
            if (element) {
                element.value = '';
                // Si c'est un select, réinitialiser à l'option vide
                if (element.tagName === 'SELECT') {
                    element.selectedIndex = 0;
                }
                applyFilters();
            }
        };

        /**
         * Obtenir l'ID de l'élément de filtre
         */
        function getFilterElementId(key) {
            const map = {
                'search': 'searchAnnonce',
                'statut': 'filterStatut',
                'canal': 'filterCanal',
                'priorite': 'filterPriorite',
                'date_debut': 'filterDateDebut',
                'date_fin': 'filterDateFin'
            };
            return map[key] || key;
        }

        // ================================================================
        // INITIALISATION DES FILTRES
        // ================================================================
        document.addEventListener('DOMContentLoaded', function() {
            // Charger les filtres sauvegardés automatiquement
            // loadSavedFilters();

            // Mettre à jour les tags initiaux
            const initialFilters = getFilters();
            updateFilterTags(initialFilters);

            // Écouter les changements de filtre pour mettre à jour les tags
            document.querySelectorAll('#searchAnnonce, #filterStatut, #filterCanal, #filterPriorite, #filterDateDebut, #filterDateFin')
                .forEach(element => {
                    element.addEventListener('change', function() {
                        const filters = getFilters();
                        updateFilterTags(filters);
                    });
                    if (element.type === 'text' || element.type === 'search') {
                        element.addEventListener('input', function() {
                            const filters = getFilters();
                            updateFilterTags(filters);
                        });
                    }
                });

            // Raccourci clavier: Entrée pour appliquer les filtres
            document.getElementById('searchAnnonce').addEventListener('keyup', function(e) {
                if (e.key === 'Enter') {
                    applyFilters();
                }
            });
        });

        // ================================================================
        // EXPORT DES FILTRES (pour les stats)
        // ================================================================
        window.getActiveFilters = function() {
            return getFilters();
        };

        console.log('📊 Filtres chargés avec succès');
    </script>
@endpush