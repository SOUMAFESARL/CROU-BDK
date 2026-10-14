

    <div id="page-maintenance" class="content">
        @php

            $stats =$result['statOT'];
            $ordres=    $result['ordres'];
        @endphp
        <!-- EN-TÊTE DE LA PAGE -->
        <div class="maintenance-header">
            <div>
                <h1 class="page-title"><i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i> Signaler un incident de maintenance</h1>
                <p class="page-subtitle">Gestion des ordres de travail et suivi des interventions</p>
            </div>
            <div class="header-stats">
                <div class="stat-item">
                    <span class="stat-value">{{ $stats['en_attente'] + $stats['en_cours'] + $stats['en_attente_pieces'] ?? 0 }}</span>
                    <span class="stat-label">En cours</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value">{{ $stats['termine'] ?? 0 }}</span>
                    <span class="stat-label">Résolus</span>
                </div>
                <div class="stat-item urgent">
                    <span class="stat-value">{{ $stats['urgent'] ?? 0 }}</span>
                    <span class="stat-label">Urgents</span>
                </div>
            </div>
        </div>


        <!-- GRILLE PRINCIPALE -->
        <div class="maintenance-grid">

            <form action="{{ route('etudiants.store_ot') }}" method="post" enctype="multipart/form-data">
                @csrf

                <!-- COLONNE GAUCHE : NOUVELLE DEMANDE -->
                <div class="panel panel-new-request">
                    <div class="panel-header">
                        <h2>📝 Nouvelle demande</h2>
                        <span class="panel-badge">Priorité</span>
                    </div>
                    <div class="panel-body">

                        <!-- Catégorie -->
                        <div class="form-group">
                            <label class="form-label">Catégorie du problème <span class="required">*</span></label>
                            <div class="form-hint">Sélectionnez la nature du problème</div>
                            <select class="form-select" name="categorie" required>
                                <option value="">— Sélectionner une catégorie —</option>
                                <option value="plomberie">🚰 Plomberie</option>
                                <option value="electricite">⚡ Électricité</option>
                                <option value="mobilier">🪑 Mobilier / Équipement</option>
                                <option value="climatisation">❄️ Climatisation / Ventilation</option>
                                <option value="porte">🚪 Porte / Fenêtre</option>
                                <option value="securite">🔒 Sécurité</option>
                                <option value="autre">📌 Autre</option>
                            </select>
                        </div>

                        <!-- Type d'intervention -->
                        <div class="form-group">
                            <label class="form-label">Type d'intervention <span class="required">*</span></label>
                            <div class="urgency-options">
                                <label class="urgency-option low">
                                    <input type="radio" name="type_intervention" value="corrective" checked />
                                    <span class="urgency-dot"></span>
                                    Corrective
                                </label>
                                <label class="urgency-option medium">
                                    <input type="radio" name="type_intervention" value="preventive" />
                                    <span class="urgency-dot"></span>
                                    Préventive
                                </label>
                               <!-----  <label class="urgency-option high">
                                    <input type="radio" name="type_intervention" value="urgente" />
                                    <span class="urgency-dot"></span>
                                    Urgente
                                </label>----------->
                            </div>
                        </div>

                        <!-- Niveau d'urgence -->
                        <div class="form-group">
                            <label class="form-label">Niveau d'urgence <span class="required">*</span></label>
                            <div class="priorite-options">
                                <label class="priorite-option high">
                                    <input type="radio" name="priorite" value="critique" />
                                    <span class="urgency-dot"></span>
                                    Critique
                                </label>
                                <label class="priorite-option medium">
                                    <input type="radio" name="priorite" value="elevee" />
                                    <span class="urgency-dot"></span>
                                    Élevée
                                </label>
                                <label class="priorite-option low">
                                    <input type="radio" name="priorite" value="moyenne" checked />
                                    <span class="urgency-dot"></span>
                                    Moyenne
                                </label>
                                <label class="priorite-option low">
                                    <input type="radio" name="priorite" value="faible" />
                                    <span class="urgency-dot"></span>
                                    Faible
                                </label>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="form-group">
                            <label class="form-label">Description du problème <span class="required">*</span></label>
                            <div class="form-hint">Décrivez le problème avec le maximum de détails</div>
                            <textarea class="form-textarea" required name="description" placeholder="Ex: La prise électrique près du bureau ne fonctionne plus depuis hier soir..."></textarea>
                            <div class="char-counter">0 / 500 caractères</div>
                        </div>

                        <!-- Disponibilité -->
                        <div class="form-group">
                            <label class="form-label">Disponibilité pour intervention</label>
                            <div class="form-hint">Indiquez vos disponibilités pour faciliter l'intervention</div>
                            <div class="availability-inputs">
                                <input type="date" class="form-input" name="date_disponibilite" style="flex:1;" />
                                <select class="form-select" style="flex:1;" name="periode_disponibilite">
                                    <option>Matin (8h-12h)</option>
                                    <option>Après-midi (13h-17h)</option>
                                    <option>Soirée (17h-20h)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Pièces jointes -->
                        <div class="form-group">
                            <label class="form-label">Pièces jointes</label>
                            <div class="file-upload">
                                <input type="file" id="file-upload" name="pieces_jointes[]" multiple accept="image/*,.pdf" hidden />
                                <label for="file-upload" class="file-upload-label">
                                    <span class="upload-icon">📎</span>
                                    <span>Glissez vos photos ou PDF ici</span>
                                    <span class="upload-hint">ou cliquez pour parcourir</span>
                                </label>
                                <div class="file-list" id="file-list"></div>
                            </div>
                        </div>

                        <button class="btn btn-primary btn-submit" type="submit">
                            <span class="btn-icon">📤</span>
                            Soumettre la demande
                        </button>
                    </div>
                </div>

            </form>

            <!-- COLONNE DROITE : DEMANDES PRÉCÉDENTES -->
            <div class="panel panel-requests">
                <div class="panel-header">
                    <div class="header-left">
                        <h2><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i> Demandes précédentes</h2>
                        <span class="panel-badge">{{ $ordres->count() }} demandes</span>
                    </div>
                    <div class="header-actions">
                        <button class="btn-icon-only" title="Voir toutes les demandes"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
                    </div>
                </div>
                <div class="panel-body">

                    <!-- Filtre des demandes -->
                    <div class="request-filters">
                        <button class="filter-chip active" data-filter="toutes">Toutes</button>
                        <button class="filter-chip" data-filter="en_cours">En cours</button>
                        <button class="filter-chip" data-filter="termine">Résolues</button>
                        <button class="filter-chip" data-filter="urgent">Urgentes</button>
                    </div>

                    <!-- Liste des demandes -->
                    <div class="request-list">
                        @forelse($ordres as $ordre)
                            @php
                                $statusClass = match($ordre->statut) {
                                    'en_attente', 'en_attente_pieces' => 'status-pending',
                                    'en_cours' => 'status-in-progress',
                                    'termine' => 'status-resolved',
                                    default => 'status-pending'
                                };

                                $badgeClass = match($ordre->statut) {
                                    'en_attente' => 'danger',
                                    'en_cours' => 'warning',
                                    'en_attente_pieces' => 'info',
                                    'termine' => 'success',
                                    'annule' => 'secondary',
                                    'reporte' => 'orange',
                                    default => 'secondary'
                                };

                                $priorityClass = match($ordre->priorite) {
                                    'critique' => 'high',
                                    'elevee' => 'medium',
                                    'moyenne' => 'low',
                                    'faible' => 'low',
                                    default => 'low'
                                };
                            @endphp
                            <div class="request-item {{ $statusClass }}">
                                <div class="request-status">
                                    <span class="status-indicator {{
                                        $ordre->statut === 'en_cours' ? 'in-progress' :
                                        ($ordre->statut === 'termine' ? 'resolved' : 'pending')
                                    }}"></span>
                                    <span class="status-label">{{ $ordre->statut_label }}</span>
                                </div>
                                <div class="request-content">
                                    <div class="request-header">
                                        <h3 class="request-title">{{ $ordre->titre ?? 'Sans titre' }}</h3>
                                        <span class="request-priority {{ $priorityClass }}">
                                            {{ $ordre->priorite_label }}
                                        </span>
                                    </div>

                                    <div class="request-header">
                                        <h3 class="request-title">{{ $ordre->description ?? 'Sans description' }}</h3>

                                    </div>
                                    <div class="request-meta">
                                        <span class="meta-tag">{{ $ordre->categorie_label }}</span>
                                        <span class="meta-date">📅 {{ $ordre->created_at ? $ordre->created_at->format('d/m/Y') : 'N/A' }}</span>
                                        <span class="meta-id">#{{ $ordre->numero_ot }}</span>
                                    </div>

                                    @if($ordre->statut === 'en_cours')
                                        <div class="request-progress">
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: 60%;"></div>
                                            </div>
                                            <span class="progress-text">60%</span>
                                        </div>
                                    @endif

                                    @if($ordre->statut === 'termine')
                                        <div class="request-resolution">
                                            <span class="resolution-badge">✅ Résolue le {{ $ordre->date_resolution ? $ordre->date_resolution->format('d/m/Y') : 'N/A' }}</span>
                                            @if($ordre->taux_satisfaction)
                                                <span class="satisfaction">⭐ {{ $ordre->taux_satisfaction }}/5</span>
                                            @endif
                                        </div>
                                    @endif

                                    <div class="request-footer">
                                        @if($ordre->assigne_a)
                                            <span class="technician">👨‍🔧 Assigné à : <strong>{{ $ordre->assigneA?->name ?? 'Technicien' }}</strong></span>
                                        @else
                                            <span class="technician">👨‍🔧 En attente d'affectation</span>
                                        @endif
                                        @if($ordre->date_debut_intervention)
                                            <span class="eta">⏱️ Intervention : {{ $ordre->date_debut_intervention->format('d/m/Y') }}</span>
                                        @elseif($ordre->date_disponibilite)
                                            <span class="eta">📅 Disponible : {{ $ordre->date_disponibilite->format('d/m/Y') }}</span>
                                        @else
                                            <span class="eta">⏱️ Créé le {{ $ordre->created_at ? $ordre->created_at->format('d/m/Y') : 'N/A' }}</span>
                                        @endif
                                    </div>
                                    <div class="request-actions">
                                        <button class="btn-action small">📄 Détails</button>
                                        @if($ordre->statut !== 'termine' && $ordre->statut !== 'annule')
                                            <button class="btn-action small success">📞 Contacter</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div style="text-align:center; padding:30px 20px; color:var(--muted);">
                                <span style="font-size:40px; display:block; margin-bottom:10px;">🔧</span>
                                <p style="font-size:var(--app-font-min, 15px); font-weight:500;">Aucune demande de maintenance</p>
                                <p style="font-size:var(--app-font-min, 15px);">Vous n'avez pas encore soumis de demande.</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Pagination -->
                    @if($ordres instanceof \Illuminate\Pagination\LengthAwarePaginator && $ordres->hasPages())
                        <div class="pagination">
                            {{ $ordres->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- SECTION STATISTIQUES -->
        <div class="stats-section">
            <div class="stats-header">
                <h3>📊 Statistiques des demandes</h3>
                <span class="stats-period">Ce mois-ci</span>
            </div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-card-icon">📈</span>
                        <span class="stat-card-value">{{ $stats['total'] > 0 ? round(($stats['termine'] / $stats['total']) * 100) : 0 }}%</span>
                    </div>
                    <span class="stat-card-label">Taux de résolution</span>
                    <div class="stat-card-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width:{{ $stats['total'] > 0 ? round(($stats['termine'] / $stats['total']) * 100) : 0 }}%;"></div>
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-card-icon">⏱️</span>
                        <span class="stat-card-value">{{ $stats['temps_moyen_resolution'] ?? 0 }} j</span>
                    </div>
                    <span class="stat-card-label">Temps moyen de résolution</span>
                    <div class="stat-card-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width:{{ ($stats['temps_moyen_resolution'] ?? 0) > 0 ? min(100, (($stats['temps_moyen_resolution'] ?? 0) / 7) * 100) : 0 }}%;"></div>
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-card-icon">🔧</span>
                        <span class="stat-card-value">{{ $stats['termine'] ?? 0 }}</span>
                    </div>
                    <span class="stat-card-label">Demandes résolues</span>
                    <div class="stat-card-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width:{{ $stats['total'] > 0 ? min(100, (($stats['termine'] / $stats['total']) * 100)) : 0 }}%;"></div>
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span class="stat-card-icon">⭐</span>
                        <span class="stat-card-value">4.5</span>
                    </div>
                    <span class="stat-card-label">Satisfaction moyenne</span>
                    <div class="stat-card-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width:90%;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <style>
        /* ============================================
           STYLES - PAGE MAINTENANCE
        ============================================ */

        :root {
            --primary: #1e7aa8;
            --primary-dark: #166a94;
            --success: #2a8d6c;
            --warning: #d68a3a;
            --danger: #b13e3e;
            --info: #5b7b99;
            --border: #dae2ec;
            --bg-light: #f4f7fc;
            --text: #0a2b44;
            --muted: #5b7b99;
            --radius: 16px;
            --shadow: 0 4px 16px rgba(0, 20, 40, 0.06);
        }

        #page-maintenance {
            max-width: 1440px;
            margin: 0 auto;
            padding: 20px;
            font-family: 'Segoe UI', Roboto, system-ui, sans-serif;
        }

        /* --- EN-TÊTE --- */
        .maintenance-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
            padding: 20px 24px;
            background: #fff;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text);
            margin: 0;
        }

        .page-subtitle {
            font-size: var(--app-font-min, 15px);
            color: var(--muted);
            margin: 2px 0 0;
        }

        .header-stats {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
        }

        .stat-item {
            text-align: center;
            padding: 4px 16px;
            border-left: 1px solid var(--border);
        }

        .stat-item:first-child {
            border-left: none;
        }

        .stat-item .stat-value {
            display: block;
            font-size: 22px;
            font-weight: 700;
            color: var(--text);
        }

        .stat-item .stat-label {
            font-size: var(--app-font-min, 15px);
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-item.urgent .stat-value {
            color: var(--danger);
        }

        /* --- FILTRES --- */
        .maintenance-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 24px;
            padding: 12px 16px;
            background: #fff;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            align-items: center;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .filter-input {
            padding: 8px 14px;
            border: 1px solid var(--border);
            border-radius: 30px;
            font-size: var(--app-font-min, 15px);
            min-width: 200px;
            background: var(--bg-light);
            transition: 0.2s;
        }

        .filter-input:focus {
            border-color: var(--primary);
            outline: none;
            background: #fff;
        }

        .filter-select {
            padding: 8px 14px;
            border: 1px solid var(--border);
            border-radius: 30px;
            font-size: var(--app-font-min, 15px);
            background: var(--bg-light);
            cursor: pointer;
            transition: 0.2s;
        }

        .filter-select:focus {
            border-color: var(--primary);
            outline: none;
        }

        .btn-filter {
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 8px 24px;
            border-radius: 30px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.2s;
            margin-left: auto;
            text-decoration: none;
            display: inline-block;
        }

        .btn-filter:hover {
            background: var(--primary-dark);
        }

        /* --- GRILLE PRINCIPALE --- */
        .maintenance-grid {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 24px;
            align-items: start;
        }

        /* --- PANELS --- */
        .panel {
            background: #fff;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            background: var(--bg-light);
        }

        .panel-header h2 {
            font-size: 16px;
            font-weight: 600;
            color: var(--text);
            margin: 0;
        }

        .panel-badge {
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
            background: #e7edf5;
            padding: 2px 12px;
            border-radius: 30px;
            color: var(--info);
        }

        .panel-body {
            padding: 20px;
        }

        /* --- FORMULAIRES --- */
        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: var(--app-font-min, 15px);
            font-weight: 600;
            color: var(--text);
            margin-bottom: 4px;
        }

        .form-label .required {
            color: var(--danger);
        }

        .form-hint {
            font-size: var(--app-font-min, 15px);
            color: var(--muted);
            margin-bottom: 6px;
        }

        .form-select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: var(--app-font-min, 15px);
            background: #fff;
            transition: 0.2s;
        }

        .form-select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(30, 122, 168, 0.1);
        }

        .form-textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: var(--app-font-min, 15px);
            min-height: 100px;
            resize: vertical;
            transition: 0.2s;
            font-family: inherit;
        }

        .form-textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(30, 122, 168, 0.1);
        }

        .form-input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: var(--app-font-min, 15px);
            transition: 0.2s;
        }

        .form-input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(30, 122, 168, 0.1);
        }

        .char-counter {
            text-align: right;
            font-size: var(--app-font-min, 15px);
            color: var(--muted);
            margin-top: 4px;
        }

        .availability-inputs {
            display: flex;
            gap: 10px;
        }

        /* --- URGENCE --- */
        .urgency-options {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .urgency-option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border: 2px solid var(--border);
            border-radius: 30px;
            font-size: var(--app-font-min, 15px);
            cursor: pointer;
            transition: 0.2s;
        }

        .urgency-option:hover {
            border-color: var(--primary);
        }

        .urgency-option input[type="radio"] {
            display: none;
        }

        .urgency-option .urgency-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }

        .urgency-option.low .urgency-dot { background: var(--success); }
        .urgency-option.medium .urgency-dot { background: var(--warning); }
        .urgency-option.high .urgency-dot { background: var(--danger); }

        .urgency-option.active.low { border-color: var(--success); background: #ddf0e8; }
        .urgency-option.active.medium { border-color: var(--warning); background: #fcead8; }
        .urgency-option.active.high { border-color: var(--danger); background: #fce1e1; }

        .priorite-options {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .priorite-option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border: 2px solid var(--border);
            border-radius: 30px;
            font-size: var(--app-font-min, 15px);
            cursor: pointer;
            transition: 0.2s;
        }

        .priorite-option:hover {
            border-color: var(--primary);
        }

        .priorite-option input[type="radio"] {
            display: none;
        }

        .priorite-option .urgency-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }

        .priorite-option.low .urgency-dot { background: var(--success); }
        .priorite-option.medium .urgency-dot { background: var(--warning); }
        .priorite-option.high .urgency-dot { background: var(--danger); }

        .priorite-option.active.low { border-color: var(--success); background: #ddf0e8; }
        .priorite-option.active.medium { border-color: var(--warning); background: #fcead8; }
        .priorite-option.active.high { border-color: var(--danger); background: #fce1e1; }

        /* --- FILE UPLOAD --- */
        .file-upload {
            border: 2px dashed var(--border);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: 0.2s;
        }

        .file-upload:hover {
            border-color: var(--primary);
            background: var(--bg-light);
        }

        .file-upload-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            cursor: pointer;
        }

        .file-upload-label .upload-icon {
            font-size: 28px;
        }

        .file-upload-label .upload-hint {
            font-size: var(--app-font-min, 15px);
            color: var(--muted);
        }

        .file-list {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .file-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            background: var(--bg-light);
            border-radius: 30px;
            font-size: var(--app-font-min, 15px);
        }

        .file-tag .remove-file {
            cursor: pointer;
            color: var(--danger);
            font-weight: 700;
        }

        /* --- BOUTONS --- */
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
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            font-size: 15px;
        }

        .btn-submit .btn-icon {
            font-size: 18px;
        }

        .btn-action {
            padding: 4px 12px;
            border: 1px solid var(--border);
            border-radius: 30px;
            font-size: var(--app-font-min, 15px);
            background: #fff;
            color: var(--text);
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-action:hover {
            background: var(--bg-light);
        }

        .btn-action.small {
            padding: 3px 10px;
            font-size: var(--app-font-min, 15px);
        }

        .btn-action.success {
            border-color: var(--success);
            color: var(--success);
        }

        .btn-action.success:hover {
            background: #ddf0e8;
        }

        .btn-action.warning {
            border-color: var(--warning);
            color: var(--warning);
        }

        .btn-action.warning:hover {
            background: #fcead8;
        }

        .btn-action.primary {
            border-color: var(--primary);
            color: var(--primary);
        }

        .btn-action.primary:hover {
            background: #dce8f5;
        }

        .btn-icon-only {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: var(--muted);
            transition: 0.2s;
            padding: 4px 8px;
            border-radius: 6px;
        }

        .btn-icon-only:hover {
            background: var(--bg-light);
            color: var(--text);
        }

        /* --- LISTE DES DEMANDES --- */
        .header-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-actions {
            display: flex;
            gap: 4px;
        }

        .request-filters {
            display: flex;
            gap: 6px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .filter-chip {
            padding: 4px 14px;
            border: 1px solid var(--border);
            border-radius: 30px;
            font-size: var(--app-font-min, 15px);
            background: #fff;
            cursor: pointer;
            transition: 0.2s;
            color: var(--muted);
        }

        .filter-chip:hover {
            border-color: var(--primary);
            color: var(--text);
        }

        .filter-chip.active {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }

        .request-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .request-item {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            transition: 0.2s;
        }

        .request-item:hover {
            border-color: var(--primary);
            box-shadow: 0 2px 8px rgba(0, 20, 40, 0.06);
        }

        .request-item.status-in-progress {
            border-left: 4px solid var(--warning);
        }

        .request-item.status-resolved {
            border-left: 4px solid var(--success);
        }

        .request-item.status-pending {
            border-left: 4px solid var(--info);
        }

        .request-status {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }

        .status-indicator.in-progress { background: var(--warning); }
        .status-indicator.resolved { background: var(--success); }
        .status-indicator.pending { background: var(--info); }

        .status-label {
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
            color: var(--muted);
        }

        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }

        .request-title {
            font-size: var(--app-font-min, 15px);
            font-weight: 600;
            color: var(--text);
            margin: 0;
        }

        .request-priority {
            font-size: var(--app-font-min, 15px);
            font-weight: 600;
            padding: 2px 10px;
            border-radius: 30px;
            text-transform: uppercase;
        }

        .request-priority.high {
            background: #fce1e1;
            color: var(--danger);
        }

        .request-priority.medium {
            background: #fcead8;
            color: var(--warning);
        }

        .request-priority.low {
            background: #ddf0e8;
            color: var(--success);
        }

        .request-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            font-size: var(--app-font-min, 15px);
            color: var(--muted);
            margin-bottom: 8px;
        }

        .meta-tag {
            background: var(--bg-light);
            padding: 1px 10px;
            border-radius: 30px;
            color: var(--info);
        }

        .meta-date, .meta-id {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .request-progress {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 8px 0;
        }

        .progress-bar {
            flex: 1;
            height: 6px;
            background: var(--border);
            border-radius: 8px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 8px;
            background: var(--primary);
            transition: width 0.6s ease;
        }

        .progress-text {
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
            color: var(--muted);
        }

        .request-resolution {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 6px 0;
        }

        .resolution-badge {
            font-size: var(--app-font-min, 15px);
            color: var(--success);
            background: #ddf0e8;
            padding: 2px 12px;
            border-radius: 30px;
        }

        .satisfaction {
            font-size: var(--app-font-min, 15px);
            color: var(--warning);
        }

        .request-footer {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            font-size: var(--app-font-min, 15px);
            color: var(--muted);
            margin: 8px 0 10px;
        }

        .request-footer strong {
            color: var(--text);
        }

        .request-actions {
            display: flex;
            gap: 6px;
            margin-top: 8px;
        }

        /* --- PAGINATION --- */
        .pagination {
            display: flex;
            align-items: center;
            gap: 4px;
            margin-top: 16px;
            justify-content: center;
        }

        .page-btn {
            padding: 6px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #fff;
            cursor: pointer;
            font-size: var(--app-font-min, 15px);
            color: var(--text);
            transition: 0.2s;
        }

        .page-btn:hover:not(.active) {
            background: var(--bg-light);
        }

        .page-btn.active {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }

        .page-ellipsis {
            padding: 6px 4px;
            color: var(--muted);
        }

        .page-btn.prev, .page-btn.next {
            padding: 6px 10px;
        }

        /* --- STATISTIQUES --- */
        .stats-section {
            margin-top: 24px;
            padding: 20px 24px;
            background: #fff;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .stats-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .stats-header h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--text);
            margin: 0;
        }

        .stats-period {
            font-size: var(--app-font-min, 15px);
            color: var(--muted);
            background: var(--bg-light);
            padding: 2px 14px;
            border-radius: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
        }

        .stat-card {
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: 12px;
            transition: 0.2s;
        }

        .stat-card:hover {
            border-color: var(--primary);
        }

        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2px;
        }

        .stat-card-icon {
            font-size: 22px;
        }

        .stat-card-value {
            font-size: 22px;
            font-weight: 700;
            color: var(--text);
        }

        .stat-card-label {
            font-size: var(--app-font-min, 15px);
            color: var(--muted);
            display: block;
            margin-bottom: 6px;
        }

        .stat-card-progress .progress-bar {
            height: 4px;
        }

        .stat-card-progress .progress-fill {
            background: var(--primary);
        }

        /* --- RESPONSIVE --- */
        @media (max-width: 1024px) {
            .maintenance-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .maintenance-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .header-stats {
                width: 100%;
                justify-content: space-around;
            }

            .maintenance-filters {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-input {
                min-width: unset;
                width: 100%;
            }

            .btn-filter {
                margin-left: 0;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }

            .availability-inputs {
                flex-direction: column;
            }

            .request-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .header-stats {
                flex-wrap: wrap;
            }

            .stat-item {
                border-left: none;
                padding: 4px 8px;
            }
        }
    </style>

    <script>
        // ============================================
        // JAVASCRIPT - PAGE MAINTENANCE
        // ============================================

        // --- Gestion des onglets d'urgence ---
        document.querySelectorAll('.urgency-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.urgency-option').forEach(opt => {
                    opt.classList.remove('active');
                });
                this.classList.add('active');
                const radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                }
            });
        });

        document.querySelectorAll('.priorite-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.priorite-option').forEach(opt => {
                    opt.classList.remove('active');
                });
                this.classList.add('active');
                const radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                }
            });
        });

        // --- Gestion des filtres de demandes ---
        document.querySelectorAll('.filter-chip').forEach(chip => {
            chip.addEventListener('click', function() {
                document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
                this.classList.add('active');

                const filter = this.dataset.filter;
                const items = document.querySelectorAll('.request-item');

                items.forEach(item => {
                    if (filter === 'toutes') {
                        item.style.display = 'block';
                        return;
                    }

                    const isInProgress = item.classList.contains('status-in-progress') ||
                        item.classList.contains('status-pending');
                    const isResolved = item.classList.contains('status-resolved');
                    const isUrgent = item.querySelector('.request-priority.high') !== null;

                    if (filter === 'en_cours' && isInProgress) {
                        item.style.display = 'block';
                    } else if (filter === 'termine' && isResolved) {
                        item.style.display = 'block';
                    } else if (filter === 'urgent' && isUrgent) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });

        // --- Gestion des fichiers joints ---
        document.getElementById('file-upload')?.addEventListener('change', function(e) {
            const fileList = document.getElementById('file-list');
            const files = e.target.files;

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const tag = document.createElement('span');
                tag.className = 'file-tag';
                tag.innerHTML = `${file.name} (${(file.size / 1024).toFixed(1)} KB) <span class="remove-file" onclick="this.parentElement.remove()">×</span>`;
                fileList.appendChild(tag);
            }
        });

        // --- Compteur de caractères ---
        document.querySelector('.form-textarea')?.addEventListener('input', function(e) {
            const count = this.value.length;
            const max = 500;
            const counter = this.parentElement.querySelector('.char-counter');
            if (counter) {
                counter.textContent = `${count} / ${max} caractères`;
                if (count > max) {
                    counter.style.color = '#b13e3e';
                    this.style.borderColor = '#b13e3e';
                } else {
                    counter.style.color = '';
                    this.style.borderColor = '';
                }
            }
        });

        // --- Pagination ---
        document.querySelectorAll('.page-btn:not(.prev):not(.next)').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.page-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });

        document.querySelector('.page-btn.prev')?.addEventListener('click', function() {
            const active = document.querySelector('.page-btn.active');
            const prev = active?.previousElementSibling;
            if (prev && prev.classList.contains('page-btn') && !prev.classList.contains('prev')) {
                active.classList.remove('active');
                prev.classList.add('active');
            }
        });

        document.querySelector('.page-btn.next')?.addEventListener('click', function() {
            const active = document.querySelector('.page-btn.active');
            const next = active?.nextElementSibling;
            if (next && next.classList.contains('page-btn') && !next.classList.contains('next')) {
                active.classList.remove('active');
                next.classList.add('active');
            }
        });

        // --- Initialisation : sélection par défaut de l'urgence ---
        document.querySelector('.urgency-option.low')?.classList.add('active');
        document.querySelector('.priorite-option.low')?.classList.add('active');

        console.log('✅ Page maintenance initialisée avec succès !');
    </script>

