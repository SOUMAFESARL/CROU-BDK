

<div id="page-annonce" class="content">
    <style>
        :root {
            --paper: #F6F2EA;
            --card: #FFFFFF;
            --ink: #23271F;
            --ink-soft: #5B5F54;
            --line: #D8D2C3;
            --radius: 10px;
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --success: #48bb78;
            --warning: #ed8936;
            --danger: #fc8181;
        }

        #page-annonce * {
            box-sizing: border-box;
        }

        #page-annonce {
            background: var(--paper);
            color: var(--ink);
            font-family: 'Inter', 'Segoe UI', sans-serif;
            padding: 32px 16px 80px;
        }

        #page-annonce .sheet {
            max-width: 880px;
            margin: 0 auto;
        }

        /* Header */
        #page-annonce header.top {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-bottom: 2px solid var(--ink);
            padding-bottom: 18px;
            margin-bottom: 18px;
            flex-wrap: wrap;
            gap: 10px;
        }
        #page-annonce header.top h1 {
            font-family: 'Fraunces', serif;
            font-weight: 600;
            font-size: 30px;
            margin: 0 0 4px;
        }
        #page-annonce header.top p {
            margin: 0;
            color: var(--ink-soft);
            font-size: var(--app-font-min, 15px);
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        /* Compteur */
        #page-annonce .counter-badge {
            background: var(--primary);
            color: #fff;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: var(--app-font-min, 15px);
            font-weight: 600;
            white-space: nowrap;
        }

        /* Filtres */
        #page-annonce .filter-section {
            background: var(--card);
            padding: 16px 20px;
            border-radius: var(--radius);
            border: 1px solid var(--line);
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
        }
        #page-annonce .filter-section select,
        #page-annonce .filter-section input {
            padding: 8px 12px;
            border: 1px solid var(--line);
            border-radius: 7px;
            font-size: var(--app-font-min, 15px);
            background: #FCFAF6;
            color: var(--ink);
            flex: 1;
            min-width: 140px;
        }
        #page-annonce .filter-section select:focus,
        #page-annonce .filter-section input:focus {
            outline: 2px solid var(--primary);
            outline-offset: 1px;
        }

        /* Annonce */
        #page-annonce .annonce-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 22px 24px;
            margin-bottom: 18px;
            transition: all 0.2s;
            position: relative;
        }
        #page-annonce .annonce-card:hover {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            transform: translateY(-2px);
        }

        #page-annonce .annonce-card .badge-priorite {
            position: absolute;
            top: 16px;
            right: 16px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: var(--app-font-min, 15px);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        #page-annonce .badge-priorite.normale {
            background: #E2E8F0;
            color: #4A5568;
        }
        #page-annonce .badge-priorite.haute {
            background: #FEFCBF;
            color: #975A16;
        }
        #page-annonce .badge-priorite.urgente {
            background: #FED7D7;
            color: #9B2C2C;
            animation: pulse-urgent 1.5s ease-in-out infinite;
        }

        @keyframes pulse-urgent {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        #page-annonce .annonce-card .titre {
            font-family: 'Fraunces', serif;
            font-size: 20px;
            font-weight: 600;
            margin: 0 0 6px 0;
            padding-right: 80px;
            color: var(--ink);
        }

        #page-annonce .annonce-card .meta {
            font-size: var(--app-font-min, 15px);
            color: var(--ink-soft);
            margin-bottom: 12px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
        }
        #page-annonce .annonce-card .meta .canal {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
        }
        #page-annonce .canal.flux { background: #BEE3F8; color: #2A69AC; }
        #page-annonce .canal.sms { background: #C6F6D5; color: #22543D; }
        #page-annonce .canal.email { background: #FEFCBF; color: #975A16; }
        #page-annonce .canal.push { background: #FED7D7; color: #9B2C2C; }

        #page-annonce .annonce-card .contenu {
            font-size: 15px;
            line-height: 1.7;
            color: var(--ink);
            margin: 12px 0 16px 0;
            word-wrap: break-word;
        }

        #page-annonce .annonce-card .contenu img {
            max-width: 100%;
            border-radius: 8px;
            margin: 8px 0;
        }

        #page-annonce .annonce-card .contenu a {
            color: var(--primary);
            text-decoration: underline;
        }

        #page-annonce .annonce-card .contenu ul,
        #page-annonce .annonce-card .contenu ol {
            padding-left: 24px;
        }

        #page-annonce .annonce-card .footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            padding-top: 14px;
            border-top: 1px solid var(--line);
            font-size: var(--app-font-min, 15px);
            color: var(--ink-soft);
        }

        #page-annonce .annonce-card .footer .stats {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        #page-annonce .annonce-card .footer .stats span {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        #page-annonce .annonce-card .footer .actions {
            display: flex;
            gap: 6px;
        }

        #page-annonce .annonce-card .footer .actions button {
            background: none;
            border: 1px solid var(--line);
            border-radius: 6px;
            padding: 5px 12px;
            font-size: var(--app-font-min, 15px);
            cursor: pointer;
            color: var(--ink-soft);
            transition: all 0.2s;
        }
        #page-annonce .annonce-card .footer .actions button:hover {
            border-color: var(--ink);
            color: var(--ink);
            background: #F0EADB;
        }
        #page-annonce .annonce-card .footer .actions button.like.active {
            border-color: var(--danger);
            color: var(--danger);
            background: #FED7D7;
        }

        /* Aucune annonce */
        #page-annonce .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--card);
            border-radius: var(--radius);
            border: 1px solid var(--line);
        }
        #page-annonce .empty-state i {
            font-size: 48px;
            color: var(--ink-soft);
            opacity: 0.5;
            margin-bottom: 16px;
            display: block;
        }
        #page-annonce .empty-state h3 {
            font-family: 'Fraunces', serif;
            font-weight: 600;
            color: var(--ink-soft);
            margin: 0 0 8px;
        }
        #page-annonce .empty-state p {
            color: var(--ink-soft);
            font-size: var(--app-font-min, 15px);
            margin: 0;
        }

        /* Chargement */
        #page-annonce .loading-state {
            text-align: center;
            padding: 40px 20px;
        }
        #page-annonce .loading-state .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--line);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 16px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Pagination */
        #page-annonce .pagination-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid var(--line);
        }
        #page-annonce .pagination-wrapper .info {
            font-size: var(--app-font-min, 15px);
            color: var(--ink-soft);
        }
        #page-annonce .pagination-wrapper .pages {
            display: flex;
            gap: 4px;
        }
        #page-annonce .pagination-wrapper .pages button {
            border: 1px solid var(--line);
            border-radius: 6px;
            padding: 6px 12px;
            background: var(--card);
            cursor: pointer;
            font-size: var(--app-font-min, 15px);
            color: var(--ink);
            transition: all 0.2s;
        }
        #page-annonce .pagination-wrapper .pages button:hover:not(:disabled) {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }
        #page-annonce .pagination-wrapper .pages button.active {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }
        #page-annonce .pagination-wrapper .pages button:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        /* Toast */
        #page-annonce #toastContainer {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        #page-annonce .toast-custom {
            padding: 12px 20px;
            border-radius: 8px;
            color: #fff;
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            animation: slideIn 0.3s ease;
            min-width: 280px;
        }
        #page-annonce .toast-custom.success { background: var(--success); }
        #page-annonce .toast-custom.error { background: var(--danger); }
        #page-annonce .toast-custom.info { background: var(--primary); }
        #page-annonce .toast-custom.warning { background: var(--warning); }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Responsive */
        @media (max-width: 640px) {
            #page-annonce header.top {
                flex-direction: column;
                align-items: stretch;
            }
            #page-annonce header.top .counter-badge {
                align-self: flex-start;
            }
            #page-annonce .annonce-card .titre {
                font-size: 17px;
                padding-right: 0;
            }
            #page-annonce .annonce-card .badge-priorite {
                position: static;
                display: inline-block;
                margin-bottom: 8px;
            }
            #page-annonce .annonce-card .meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 6px;
            }
            #page-annonce .annonce-card .footer {
                flex-direction: column;
                align-items: stretch;
            }
            #page-annonce .annonce-card .footer .stats {
                flex-wrap: wrap;
            }
            #page-annonce .filter-section {
                flex-direction: column;
            }
            #page-annonce .filter-section select,
            #page-annonce .filter-section input {
                width: 100%;
                min-width: unset;
            }
            #page-annonce .pagination-wrapper {
                flex-direction: column;
                align-items: stretch;
            }
            #page-annonce .pagination-wrapper .pages {
                justify-content: center;
                flex-wrap: wrap;
            }
        }

        @media print {
            #page-annonce .filter-section,
            #page-annonce .pagination-wrapper,
            #page-annonce .annonce-card .footer .actions,
            #page-annonce .annonce-card .badge-priorite {
                display: none !important;
            }
            #page-annonce .annonce-card {
                break-inside: avoid;
                border: 1px solid #ccc;
            }
        }
    </style>

    <div class="sheet">

        <!-- ===== HEADER ===== -->
        <header class="top">
            <div>
                <h1>Actualités</h1>
                <p>Restez informé des dernières annonces</p>
            </div>
            <div class="counter-badge" id="totalBadge">
                <i class="fas fa-bullhorn me-2"></i>
                <span id="totalCount">0</span> annonce(s)
            </div>
        </header>

        <!-- ===== FILTRES ===== -->
        <div class="filter-section" id="filterSection">
            <select id="filterCanal">
                <option value="">Tous les canaux</option>
                <option value="flux">📡 Flux d'actualité</option>
                <option value="sms">📱 SMS</option>
                <option value="email">✉️ Email</option>
                <option value="push">🔔 Push</option>
            </select>

            <select id="filterPriorite">
                <option value="">Toutes les priorités</option>
                <option value="normale">Normale</option>
                <option value="haute">Haute</option>
                <option value="urgente">Urgente</option>
            </select>

            <input type="text" id="searchInput" placeholder="🔍 Rechercher une annonce...">

            <button onclick="resetFilters()" style="
                padding: 8px 16px;
                border: 1px solid var(--line);
                border-radius: 7px;
                background: var(--card);
                cursor: pointer;
                font-size: var(--app-font-min, 15px);
                color: var(--ink-soft);
                white-space: nowrap;
            ">
                <i class="fas fa-undo me-1"></i> Réinitialiser
            </button>
        </div>

        <!-- ===== LISTE DES ANNONCES ===== -->
        <div id="annoncesContainer">
            <div class="loading-state">
                <div class="spinner"></div>
                <p style="color: var(--ink-soft);">Chargement des annonces...</p>
            </div>
        </div>

        <!-- ===== PAGINATION ===== -->
        <div class="pagination-wrapper" id="paginationWrapper">
            <div class="info" id="paginationInfo">Affichage de 0 à 0 sur 0 annonces</div>
            <div class="pages" id="paginationPages"></div>
        </div>

    </div>

    <!-- ===== TOAST CONTAINER ===== -->
    <div id="toastContainer"></div>

    <script>
        (function () {
            // ================================================================
            // CONFIG — route réelle côté serveur (scopeVisibleParEtudiant)
            //
            // IMPORTANT : si ce fragment n'est PAS compilé par Blade (par ex. servi
            // comme fichier statique, ou chargé tel quel sans passer par une vue),
            // la directive  ci-dessous restera du texte littéral au
            // lieu d'être remplacée. Dans ce cas, remplace-la à la main par l'URL
            // réelle, ex: const ANNONCES_URL = "/mes-annonces";
            // ================================================================
            const ANNONCES_URL = "{{ route('etudiants.annonces') }}";

            // ================================================================
            // ÉTAT
            // ================================================================
            let currentPage = 1;
            let currentMeta = { last_page: 1, total: 0, from: 0, to: 0 };
            let likedIds = new Set();
            let searchDebounceTimer = null;

            // ================================================================
            // INITIALISATION
            //
            // Volontairement PAS de document.addEventListener('DOMContentLoaded', ...) :
            // si ce bloc HTML est injecté après coup (AJAX / swap de contenu par un
            // thème admin), l'événement DOMContentLoaded du document parent s'est
            // déjà déclenché depuis longtemps et ne se reproduira jamais — le code
            // ne s'exécuterait donc jamais. On initialise directement à la place.
            // ================================================================
            function init() {
                const savedLikes = localStorage.getItem('annonce_likes');
                if (savedLikes) {
                    try {
                        likedIds = new Set(JSON.parse(savedLikes));
                    } catch (e) {
                        likedIds = new Set();
                    }
                }

                setupEventListeners();
                loadAnnonces(1);
            }

            function setupEventListeners() {
                const canalEl = document.getElementById('filterCanal');
                const prioriteEl = document.getElementById('filterPriorite');
                const searchEl = document.getElementById('searchInput');

                if (canalEl) canalEl.addEventListener('change', () => loadAnnonces(1));
                if (prioriteEl) prioriteEl.addEventListener('change', () => loadAnnonces(1));
                if (searchEl) {
                    searchEl.addEventListener('input', () => {
                        clearTimeout(searchDebounceTimer);
                        searchDebounceTimer = setTimeout(() => loadAnnonces(1), 350);
                    });
                }
            }

            // ================================================================
            // CHARGEMENT DEPUIS LE SERVEUR
            // ================================================================
            async function loadAnnonces(page) {
                currentPage = page || 1;

                const params = new URLSearchParams();
                const canal = document.getElementById('filterCanal')?.value || '';
                const priorite = document.getElementById('filterPriorite')?.value || '';
                const search = document.getElementById('searchInput')?.value.trim() || '';

                if (canal) params.set('canal', canal);
                if (priorite) params.set('priorite', priorite);
                if (search) params.set('search', search);
                params.set('page', currentPage);

                showLoadingState();

                try {
                    const res = await fetch(`${ANNONCES_URL}?${params.toString()}`, {
                        headers: { 'Accept': 'application/json' }
                    });

                    if (!res.ok) {
                        throw new Error('Impossible de charger les annonces (HTTP ' + res.status + ')');
                    }

                    const contentType = res.headers.get('content-type') || '';
                    if (!contentType.includes('application/json')) {
                        // Le serveur a renvoyé du HTML au lieu de JSON — signe que
                        // ANNONCES_URL pointe vers la mauvaise page (souvent la
                        // page courante elle-même) plutôt que vers l'endpoint API.
                        throw new Error('Réponse inattendue du serveur (pas du JSON) — vérifie ANNONCES_URL');
                    }

                    const result = await res.json();
                    const paginator = result.data;

                    currentMeta = {
                        last_page: paginator.last_page,
                        total: paginator.total,
                        from: paginator.from || 0,
                        to: paginator.to || 0
                    };

                    renderAnnonces(paginator.data);
                    renderPagination();

                } catch (err) {
                    console.error('[annonces] échec du chargement:', err);
                    document.getElementById('annoncesContainer').innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-triangle-exclamation"></i>
                        <h3>Erreur de chargement</h3>
                        <p>Impossible de récupérer les annonces pour le moment. Réessayez plus tard.</p>
                    </div>
                `;
                    document.getElementById('paginationPages').innerHTML = '';
                    document.getElementById('paginationInfo').textContent = '';
                }
            }

            function showLoadingState() {
                document.getElementById('annoncesContainer').innerHTML = `
                <div class="loading-state">
                    <div class="spinner"></div>
                    <p style="color: var(--ink-soft);">Chargement des annonces...</p>
                </div>
            `;
            }

            window.resetFilters = function () {
                document.getElementById('filterCanal').value = '';
                document.getElementById('filterPriorite').value = '';
                document.getElementById('searchInput').value = '';
                loadAnnonces(1);
                showToast('Filtres réinitialisés', 'info');
            };

            // ================================================================
            // RENDU DES ANNONCES
            // ================================================================
            function renderAnnonces(annonces) {
                document.getElementById('totalCount').textContent = currentMeta.total;

                if (!annonces || annonces.length === 0) {
                    document.getElementById('annoncesContainer').innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>Aucune annonce</h3>
                        <p>Aucune annonce ne correspond à vos critères de recherche.</p>
                    </div>
                `;
                    document.getElementById('paginationInfo').textContent = 'Affichage de 0 à 0 sur 0 annonces';
                    document.getElementById('paginationPages').innerHTML = '';
                    return;
                }

                const canalLabels = {
                    'flux': '📡 Flux',
                    'sms': '📱 SMS',
                    'email': '✉️ Email',
                    'push': '🔔 Push'
                };
                const prioriteLabels = {
                    'normale': 'Normale',
                    'haute': 'Haute',
                    'urgente': '🔴 Urgente'
                };

                let html = '';
                annonces.forEach(annonce => {
                    const id = annonce.id_annonce;
                    const isLiked = likedIds.has(id);
                    const prioriteClass = annonce.priorite || 'normale';
                    const canaux = annonce.canaux || [];

                    const canauxHtml = canaux.map(c =>
                        `<span class="canal ${c}">${canalLabels[c] || c}</span>`
                    ).join('');

                    // Le contenu vient de Summernote côté admin : déjà du HTML propre, on l'injecte tel quel.
                    const contenuHtml = annonce.contenu || '';

                    const auteur = (annonce.createur && annonce.createur.name) || annonce.auteur || 'Administration';
                    const datePub = annonce.date_publication || annonce.created_at;

                    html += `
                    <div class="annonce-card" data-id="${id}">
                        <span class="badge-priorite ${prioriteClass}">${prioriteLabels[prioriteClass] || 'Normale'}</span>

                        <h2 class="titre">${escapeHtml(annonce.titre)}</h2>

                        <div class="meta">
                            <span>
                                <i class="far fa-calendar-alt me-1"></i>
                                ${formatDate(datePub)}
                            </span>
                            <span>
                                <i class="far fa-user me-1"></i>
                                ${escapeHtml(auteur)}
                            </span>
                            ${canauxHtml}
                        </div>

                        <div class="contenu">${contenuHtml}</div>

                        <div class="footer">
                            <div class="stats">
                                <span>
                                    <i class="far fa-eye me-1"></i>
                                    ${annonce.vues || 0}
                                </span>
                            </div>
                            <div class="actions">
                                <button class="like ${isLiked ? 'active' : ''}"
                                        onclick="toggleLike(${id})"
                                        title="${isLiked ? 'Retirer le like' : "J'aime"}">
                                    <i class="fas fa-heart"></i>
                                    J'aime
                                </button>
                                <button onclick="shareAnnonce(${id}, '${escapeHtml(annonce.titre)}')" title="Partager">
                                    <i class="fas fa-share-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                });

                document.getElementById('annoncesContainer').innerHTML = html;
                document.getElementById('paginationInfo').textContent =
                    `Affichage de ${currentMeta.from} à ${currentMeta.to} sur ${currentMeta.total} annonces`;
            }

            // ================================================================
            // PAGINATION (pilotée par le serveur)
            // ================================================================
            function renderPagination() {
                const container = document.getElementById('paginationPages');
                const totalPages = currentMeta.last_page;

                if (totalPages <= 1) {
                    container.innerHTML = `
                    <button disabled>Précédent</button>
                    <button class="active">1</button>
                    <button disabled>Suivant</button>
                `;
                    return;
                }

                let html = '';
                html += `<button onclick="window.__annoncesLoadPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>Précédent</button>`;

                for (let i = 1; i <= totalPages; i++) {
                    html += `<button onclick="window.__annoncesLoadPage(${i})" class="${i === currentPage ? 'active' : ''}">${i}</button>`;
                }

                html += `<button onclick="window.__annoncesLoadPage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>Suivant</button>`;

                container.innerHTML = html;
            }
            window.__annoncesLoadPage = function (page) {
                loadAnnonces(page);
                document.getElementById('annoncesContainer').scrollIntoView({ behavior: 'smooth' });
            };

            // ================================================================
            // ACTIONS (like reste local — pas de colonne "likes" côté serveur)
            // ================================================================
            window.toggleLike = function (id) {
                if (likedIds.has(id)) {
                    likedIds.delete(id);
                    showToast('Like retiré', 'info');
                } else {
                    likedIds.add(id);
                    showToast('Vous avez aimé cette annonce ❤️', 'success');
                }

                localStorage.setItem('annonce_likes', JSON.stringify([...likedIds]));

                const btn = document.querySelector(`.annonce-card[data-id="${id}"] .like`);
                if (btn) btn.classList.toggle('active', likedIds.has(id));
            };

            window.shareAnnonce = function (id, titre) {
                const url = window.location.href.split('?')[0] + '?annonce=' + id;

                if (navigator.share) {
                    navigator.share({ title: titre, url }).catch(() => {});
                } else {
                    navigator.clipboard.writeText(url).then(() => {
                        showToast('Lien copié dans le presse-papier 📋', 'success');
                    }).catch(() => {
                        prompt('Copiez ce lien :', url);
                    });
                }
            };

            // ================================================================
            // TOAST
            // ================================================================
            function showToast(message, type) {
                type = type || 'info';
                const container = document.getElementById('toastContainer');
                const toast = document.createElement('div');
                toast.className = `toast-custom ${type}`;
                toast.textContent = message;
                container.appendChild(toast);

                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateX(100%)';
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            }

            // ================================================================
            // UTILITAIRES
            // ================================================================
            function formatDate(dateStr) {
                if (!dateStr) return 'Date inconnue';
                const d = new Date(String(dateStr).replace(' ', 'T'));
                if (isNaN(d.getTime())) return 'Date inconnue';
                return d.toLocaleDateString('fr-FR', {
                    day: '2-digit',
                    month: 'long',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }

            function escapeHtml(str) {
                if (!str) return '';
                const div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            }

            // ================================================================
            // DÉMARRAGE IMMÉDIAT
            // ================================================================
            init();
        })();
    </script>
</div>