@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'vie-cite'
])

@section('title', 'Gestion des annonces')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: var(--app-brand-color, #1f7c43);
            --primary-dark: #176236;
            --success: #1f8a5b;
            --warning: #d58a16;
            --danger: #d9534f;
            --info: #2878a8;

            /* Teintes claires dérivées des couleurs de base ci-dessus,
               utilisées pour les badges (fond clair + texte foncé de la même famille) */
            --primary-bg: #bee3f8;   --primary-text: #2a69ac;
            --success-bg: #c6f6d5;   --success-text: #22543d;
            --warning-bg: #fefcbf;   --warning-text: #975a16;
            --danger-bg: #fed7d7;    --danger-text: #9b2c2c;
            --neutral-bg: #e2e8f0;   --neutral-text: #4a5568;
        }

        /* Statistiques */
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border-left: 4px solid var(--primary);
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-card .number { font-size: 28px; font-weight: 700; color: #2d3748; }
        .stat-card .label { font-size: var(--app-font-min, 15px); color: #718096; }
        .stat-card .icon { font-size: 30px; opacity: 0.7; }
        .stat-card.success { border-left-color: var(--success); }
        .stat-card.warning { border-left-color: var(--warning); }
        .stat-card.danger { border-left-color: var(--danger); }
        .stat-card.info { border-left-color: var(--info); }

        /* Filtres */
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        /* Cartes d'annonces */
        .announcement-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            transition: all 0.3s;
            border-left: 4px solid var(--primary);
        }
        .announcement-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }
        .announcement-card .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        .announcement-card .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            margin: 0;
        }
        .announcement-card .card-meta {
            font-size: var(--app-font-min, 15px);
            color: #718096;
        }
        .announcement-card .card-content {
            color: #4a5568;
            margin: 10px 0;
        }
        .announcement-card .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }
        .announcement-card .channels {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .announcement-card .channel-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: var(--app-font-min, 15px);
            font-weight: 600;
        }
        .channel-badge.flux { background: var(--primary-bg); color: var(--primary-text); }
        .channel-badge.sms { background: var(--success-bg); color: var(--success-text); }
        .channel-badge.email { background: var(--warning-bg); color: var(--warning-text); }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: var(--app-font-min, 15px);
            font-weight: 600;
        }
        .status-badge.publie { background: var(--success-bg); color: var(--success-text); }
        .status-badge.brouillon { background: var(--neutral-bg); color: var(--neutral-text); }
        .status-badge.planifie { background: var(--warning-bg); color: var(--warning-text); }
        .status-badge.annule { background: var(--danger-bg); color: var(--danger-text); }

        /* Formulaire */
        .form-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        .form-section .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }

        /* Canal selector */
        .channel-selector {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .channel-option {
            flex: 1;
            min-width: 120px;
            padding: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }
        .channel-option:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
        }
        .channel-option.active {
            border-color: var(--primary);
            background: #ebf4ff;
        }
        .channel-option .icon {
            font-size: 28px;
            margin-bottom: 5px;
            display: block;
        }
        .channel-option .label {
            font-size: var(--app-font-min, 15px);
            font-weight: 600;
            color: #2d3748;
        }
        .channel-option .sub-label {
            font-size: var(--app-font-min, 15px);
            color: #718096;
        }
        .channel-option input[type="checkbox"] {
            display: none;
        }

        /* Audience selector */
        .audience-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 10px 0;
        }
        .audience-btn {
            padding: 8px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
        }
        .audience-btn:hover {
            border-color: var(--primary);
        }
        .audience-btn.active {
            border-color: var(--primary);
            background: #ebf4ff;
            color: var(--primary);
        }

        /* Éditeur de texte enrichi */
        .editor-toolbar {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px 8px 0 0;
            padding: 8px 12px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .editor-toolbar button {
            border: none;
            background: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            color: #4a5568;
            transition: all 0.2s;
        }
        .editor-toolbar button:hover {
            background: #e2e8f0;
        }
        .editor-content {
            border: 1px solid #e2e8f0;
            border-radius: 0 0 8px 8px;
            padding: 15px;
            min-height: 150px;
            background: white;
        }

        /* Timeline */
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e2e8f0;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 25px;
            padding-left: 20px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary);
            border: 2px solid white;
            box-shadow: 0 0 0 3px var(--primary);
        }
        .timeline-item.success::before { background: var(--success); box-shadow: 0 0 0 3px var(--success); }
        .timeline-item.warning::before { background: var(--warning); box-shadow: 0 0 0 3px var(--warning); }
        .timeline-item.danger::before { background: var(--danger); box-shadow: 0 0 0 3px var(--danger); }

        /* Animations */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Select2 personnalisé */
        .select2-container--default .select2-selection--multiple {
            border-color: #e2e8f0;
            border-radius: 8px;
            min-height: 45px;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background: #ebf4ff;
            border-color: var(--primary);
            color: var(--primary);
            border-radius: 20px;
            padding: 2px 12px;
        }

        @media print {
            .no-print { display: none !important; }
        }

        .announcement-page {
            max-width: 1520px;
            margin: 0 auto;
            padding: 24px 28px 40px !important;
            font-family: Montserrat, sans-serif;
            color: #18233b;
        }
        .announcement-page .row.mb-4 { margin-left: -7px; margin-right: -7px; }
        .announcement-page .row.mb-4 > [class*="col-"] { padding: 0 7px; }
        .announcement-page .stat-card,
        .announcement-page .form-section,
        .announcement-page .filter-section,
        .announcement-page .announcement-card {
            border: 1px solid #e7ebf2;
            border-radius: 13px;
            box-shadow: 0 4px 16px rgba(24, 35, 59, 0.05);
        }
        .announcement-page .stat-card {
            min-height: 112px;
            display: flex;
            align-items: center;
            border-left: 1px solid #e7ebf2;
            padding: 18px;
        }
        .announcement-page .stat-card > div { width: 100%; }
        .announcement-page .stat-card .number { font-size: 25px; color: #18233b; }
        .announcement-page .stat-card .label { color: #778198 !important; }
        .announcement-page .stat-card .icon {
            width: 44px;
            height: 44px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            font-size: 18px;
            color: var(--primary);
            background: color-mix(in srgb, var(--primary) 11%, white);
        }
        .announcement-page .stat-card.success .icon { color: var(--success); background: #eaf9f3; }
        .announcement-page .stat-card.warning .icon { color: var(--warning); background: #fff6e5; }
        .announcement-page .stat-card.info .icon { color: var(--info); background: #edf6fb; }
        .announcement-page .form-section,
        .announcement-page .filter-section { padding: 22px; }
        .announcement-page .section-title {
            color: #18233b;
            font-size: 16px;
            border-bottom-color: #eef1f5;
        }
        .announcement-page .section-title i { color: var(--primary); }
        .announcement-page .form-label {
            color: #455066;
            font-size: var(--app-font-min, 15px);
            font-weight: 600 !important;
        }
        .announcement-page .form-control,
        .announcement-page .form-select,
        .announcement-page .input-group-text {
            min-height: 42px;
            border-color: #dfe4eb;
            border-radius: 9px;
            font-size: var(--app-font-min, 15px);
        }
        .announcement-page .form-control:focus,
        .announcement-page .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 12%, transparent);
        }
        .announcement-page .channel-option,
        .announcement-page .audience-btn {
            border-color: #dfe4eb;
            border-radius: 10px;
            box-shadow: none;
        }
        .announcement-page .channel-option.active,
        .announcement-page .audience-btn.active {
            color: var(--primary);
            border-color: var(--primary);
            background: color-mix(in srgb, var(--primary) 8%, white);
        }
        .announcement-page .channel-option .icon { color: var(--primary); }
        .announcement-page .btn {
            border-radius: 8px;
            font-size: var(--app-font-min, 15px);
            font-weight: 650;
            padding: 9px 14px;
        }
        .announcement-page .btn-primary,
        .announcement-page .btn-success {
            border-color: var(--primary);
            background: var(--primary);
        }
        .announcement-page .btn-primary:hover,
        .announcement-page .btn-success:hover {
            border-color: var(--primary-dark);
            background: var(--primary-dark);
        }
        .announcement-page .announcement-card {
            border-left: 3px solid var(--primary);
            padding: 20px 22px;
        }
        .announcement-page .announcement-card:hover {
            box-shadow: 0 10px 28px rgba(24, 35, 59, 0.09);
            transform: translateY(-1px);
        }
        .announcement-page .announcement-card .card-header,
        .announcement-page .announcement-card .card-footer {
            background: transparent;
        }
        .announcement-page .announcement-card .card-title { color: #18233b; }
        .announcement-page .announcement-card .card-content { line-height: 1.65; }
        .announcement-page .status-badge,
        .announcement-page .channel-badge { border-radius: 7px; }
        .announcement-page #announcementsList + .d-flex { font-size: var(--app-font-min, 15px); }

        @media (max-width: 991px) {
            .announcement-page { padding: 18px 14px 32px !important; }
            .announcement-page .row.mb-4 > [class*="col-"] { margin-bottom: 12px; }
        }
        @media (max-width: 767px) {
            .announcement-page .channel-selector { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
            .announcement-page .channel-option { min-width: 0; }
            .announcement-page .announcement-card .card-header,
            .announcement-page .announcement-card .card-footer { align-items: flex-start; flex-direction: column; }
            .announcement-page .announcement-card .card-footer > .d-flex { width: 100%; flex-wrap: wrap; }
        }
        @media (max-width: 480px) {
            .announcement-page .channel-selector { grid-template-columns: 1fr; }
            .announcement-page .form-section,
            .announcement-page .filter-section,
            .announcement-page .announcement-card { padding: 16px; }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid py-4 announcement-page">

        <!-- Breadcrumbs -->
        <x-breadcrumbs
                title="Gestion des annonces"
                :items="[
            'Communication' => route('admin.hebergement.vie_residentielle.index'),
            'Annonces' => null,
        ]"
        />

        <!-- Statistiques -->
        <div class="row mb-4 no-print">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="number" id="totalAnnonces">{{ $stats['total'] }}</div>
                            <div class="label text-black">Total annonces</div>
                        </div>
                        <div class="icon"><i class="fas fa-bullhorn"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="number" id="publiees">{{ $stats['publiees'] }}</div>
                            <div class="label text-black">Publiées</div>
                        </div>
                        <div class="icon text-black"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="number" id="planifiees">{{ $stats['planifiees'] }}</div>
                            <div class="label text-black">Planifiées</div>
                        </div>
                        <div class="icon"><i class="fas fa-clock"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="number" id="brouillons">{{ $stats['brouillons'] }}</div>
                            <div class="label text-black">Brouillons</div>
                        </div>
                        <div class="icon"><i class="fas fa-pencil-alt"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- FORMULAIRE DE CRÉATION D'ANNONCE             -->
        <!-- ============================================ -->
        <div class="form-section no-print">
            <div class="section-title"  id="formSectionTitle">
                <i class="fas fa-plus-circle me-2"></i>
                Nouvelle annonce
            </div>

            <form id="announcementForm">
                @csrf

                <!-- Canal de diffusion -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Canal de diffusion</label>
                    <p class="small text-muted">Site web et SMS incluent un e-mail si l'étudiant possède une adresse.
                        Un seul e-mail est envoyé même si plusieurs canaux sont cochés.
                        Modifier une annonce publiée ne renvoie pas les messages.</p>
                    <div class="channel-selector" id="channelSelector">
                        <div class="channel-option active" data-channel="flux">
                            <input type="checkbox" name="canaux[]" value="flux" checked>
                            <span class="icon"><i class="fas fa-rss"></i></span>
                            <span class="label">Site web</span>
                            <span class="sub-label">Publication web + e-mail</span>
                        </div>
                        <div class="channel-option" data-channel="sms">
                            <input type="checkbox" name="canaux[]" value="sms">
                            <span class="icon"><i class="fas fa-sms"></i></span>
                            <span class="label">SMS</span>
                            <span class="sub-label">SMS + e-mail</span>
                        </div>
                        <div class="channel-option" data-channel="email">
                            <input type="checkbox" name="canaux[]" value="email">
                            <span class="icon"><i class="fas fa-envelope"></i></span>
                            <span class="label">Email</span>
                            <span class="sub-label">E-mail uniquement</span>
                        </div>
                    </div>
                </div>

                <!-- Audience -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Audience cible</label>
                    <div class="audience-group" id="audienceGroup">
                        <button type="button" class="audience-btn active" data-audience="all">
                            <i class="fas fa-users me-1"></i> Tous les étudiants
                        </button>
                        <button type="button" class="audience-btn" data-audience="cite">
                            <i class="fas fa-building me-1"></i> Par cité
                        </button>
                        <button type="button" class="audience-btn" data-audience="batiment">
                            <i class="fas fa-hotel me-1"></i> Par bâtiment
                        </button>
                        <button type="button" class="audience-btn" data-audience="chambre">
                            <i class="fas fa-door-open me-1"></i> Par chambre
                        </button>
                        <button type="button" class="audience-btn" data-audience="specific">
                            <i class="fas fa-user-check me-1"></i> Sélection spécifique
                        </button>
                    </div>
                    <input type="hidden" id="annonceAudience" name="audience" value="all">

                    <!-- Sélecteurs d'audience (cachés par défaut) -->
                    <div id="audienceSelectors" class="mt-3">
                        <div class="row g-3" id="audienceCible" style="display: none;">
                            <div class="col-md-4" id="col-cite">
                                <label class="form-label">Cités</label>
                                <select class="form-select select2-multiple" multiple id="selectCites" name="cites[]">
                                    @foreach($cites ?? [] as $cite)
                                        <option value="{{ $cite->id_cite }}">{{ $cite->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4" id="col-batiment" style="display: none;">
                                <label class="form-label">Bâtiments</label>
                                <select class="form-select select2-multiple" multiple id="selectBatiments" name="batiments[]">
                                    @foreach($batiments ?? [] as $batiment)
                                        <option value="{{ $batiment->id_batiment }}">{{ $batiment->name }} ==> {{ $batiment->cite->name ?? '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4" id="col-chambre" style="display: none;">
                                <label class="form-label">Chambres</label>
                                <select class="form-select select2-multiple" multiple id="selectChambres" name="chambres[]">
                                    @foreach($chambres ?? [] as $chambre)
                                        <option value="{{ $chambre->id_chambre }}">{{ $chambre->numero_chambre }} ==> {{ $chambre->batiment->name ?? '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div id="audienceSpecific" style="display: none;">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Étudiants sélectionnés</label>
                                    <select class="form-select select2-multiple" multiple id="selectEtudiants" name="etudiants[]">
                                        @foreach($etudiants ?? [] as $etudiant)
                                            <option value="{{ $etudiant->id_etudiant }}">
                                                {{ $etudiant->name }} - {{ $etudiant->matricule ?? '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Titre -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Titre de l'annonce <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="annonceTitre" name="titre" placeholder="Ex: Réunion d'information" required>
                    <div class="form-text">Maximum 100 caractères</div>
                </div>

                <!-- Contenu -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Contenu <span class="text-danger">*</span></label>
                    <textarea id="annonceContenu" name="contenu" class="form-control" rows="5" required>{{ old('contenu') }}</textarea>
                </div>

                <!-- Pièce jointe -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Pièces jointes</label>
                    <input type="file" class="form-control" id="annonceFichiers" name="fichiers[]" multiple>
                    <div class="form-text">PDF, DOC, JPG, PNG - Max 5 Mo</div>
                    <div id="fileList" class="mt-2"></div>
                </div>

                <!-- Options de publication -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Statut</label>
                        <select class="form-select" id="annonceStatut" name="statut">
                            <option value="brouillon">Brouillon</option>
                            <option value="publie">Publier immédiatement</option>
                            <option value="planifie">Planifier</option>
                        </select>
                    </div>
                    <div class="col-md-6" id="planificationContainer" style="display: none;">
                        <label class="form-label fw-bold">Date de publication</label>
                        <input type="datetime-local" class="form-control" id="annonceDatePlanifiee" name="date_planifiee">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Priorité</label>
                        <select class="form-select" id="annoncePriorite" name="priorite">
                            <option value="normale">Normale</option>
                            <option value="haute">Haute</option>
                            <option value="urgente">Urgente</option>
                        </select>
                    </div>
                    <!-- Champ Expiration masqué visuellement, mais conservé (name/id) pour ne pas casser le formulaire -->
                    <div class="col-md-6 d-none">
                        <label class="form-label fw-bold">Expiration</label>
                        <input type="datetime-local" class="form-control" id="annonceExpiration" name="date_expiration">
                    </div>
                </div>

                <!-- Boutons d'action -->
                <div class="mt-4">
                    <button type="button" class="btn btn-secondary me-2" onclick="resetForm()">
                        <i class="fas fa-undo me-1"></i> Réinitialiser
                    </button>
                    <button type="button" class="btn btn-success" onclick="submitAnnouncement('brouillon')">
                        <i class="fas fa-save me-1"></i> Enregistrer
                    </button>
                    <button type="button" class="btn btn-primary" onclick="submitAnnouncement('publie')">
                        <i class="fas fa-paper-plane me-1"></i> Publier
                    </button>
                </div>
            </form>
        </div>

        <!-- ============================================ -->
        <!-- FILTRES ET LISTE DES ANNONCES                -->
        <!-- ============================================ -->
        @adminroute('admin.hebergement.annonce.index')
        <form method="GET" action="{{ route('admin.hebergement.annonce.index') }}">
            <div class="filter-section no-print">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold"><i class="fas fa-search me-1"></i> Recherche</label>
                        <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Titre, contenu...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold"><i class="fas fa-filter me-1"></i> Statut</label>
                        <select name="statut" class="form-select">
                            <option value="">Tous</option>
                            @foreach(['publie' => 'Publié', 'brouillon' => 'Brouillon', 'planifie' => 'Planifié', 'annule' => 'Annulé'] as $val => $label)
                                <option value="{{ $val }}" @selected(($filters['statut'] ?? '') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold"><i class="fas fa-hashtag me-1"></i> Canal</label>
                        <select name="canal" class="form-select">
                            <option value="">Tous</option>
                            @foreach(['flux' => 'Site web', 'sms' => 'SMS', 'email' => 'E-mail'] as $val => $label)
                                <option value="{{ $val }}" @selected(($filters['canal'] ?? '') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold"><i class="fas fa-calendar me-1"></i> Période</label>
                        <div class="input-group">
                            <input type="date" name="date_debut" class="form-control" value="{{ $filters['date_debut'] ?? '' }}">
                            <span class="input-group-text">à</span>
                            <input type="date" name="date_fin" class="form-control" value="{{ $filters['date_fin'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-1"></i> Filtrer
                        </button>
                    </div>
                </div>
            </div>
        </form>
        @endadminroute

        <!-- Liste des annonces -->
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i> Liste des annonces</h5>
                    <div>
                        <span class="text-muted me-3">{{ $annonces->total() }} annonces</span>
                        {{-- Export : à brancher sur un endpoint dédié qui reprend les mêmes filtres --}}
                        <a href="#" class="btn btn-sm btn-outline-secondary disabled" title="Export à venir">
                            <i class="fas fa-file-export me-1"></i> Exporter
                        </a>
                    </div>
                </div>

                <div id="announcementsList">
                    @forelse($annonces as $annonce)
                        <div class="announcement-card fade-in">
                            <div class="card-header">
                                <div>
                                    <div class="card-title">
                                        {{ $annonce->titre }}
                                        @if($annonce->priorite !== 'normale')
                                            <span class="badge bg-{{ $annonce->priorite === 'urgente' ? 'danger' : 'warning' }} ms-2" style="font-size:var(--app-font-min, 15px);">
                                                {{ strtoupper($annonce->priorite) }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="card-meta">
                                        <i class="far fa-calendar-alt me-1"></i>
                                        {{ optional($annonce->created_at)->format('d/m/Y H:i') }}
                                        @if($annonce->date_publication)
                                            • <i class="far fa-clock me-1"></i> Publié le {{ \Carbon\Carbon::parse($annonce->date_publication)->format('d/m/Y H:i') }}
                                        @endif
                                        @if($annonce->createur)
                                            • <i class="far fa-user me-1"></i> {{ $annonce->createur->name }}
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    <span class="status-badge {{ $annonce->statut }}">
                                        {{ ['publie' => 'Publié', 'brouillon' => 'Brouillon', 'planifie' => 'Planifié', 'annule' => 'Annulé'][$annonce->statut] ?? $annonce->statut }}
                                    </span>
                                </div>
                            </div>

                            <div class="card-content">
                                {{ \Illuminate\Support\Str::limit(strip_tags($annonce->contenu), 200) }}
                            </div>

                            <div class="px-3 pb-2 small text-muted">
                                Envois acceptés : {{ $annonce->deliveries_sent_count }} ·
                                En attente : {{ $annonce->deliveries_pending_count }} ·
                                Échecs : {{ $annonce->deliveries_failed_count }}
                                @if($annonce->deliveries_failed_count)
                                    <span class="text-danger">Vérifiez les paramètres e-mail et SMS.</span>
                                @endif
                            </div>
                            <div class="card-footer">
                                <div class="channels">
                                    @foreach(array_intersect($annonce->canaux ?? [], \App\Models\Annonce::CANAUX) as $canal)
                                        <span class="channel-badge {{ $canal }}">
                                            {{ ['flux' => 'Site web', 'sms' => 'SMS', 'email' => 'E-mail'][$canal] ?? $canal }}
                                        </span>
                                    @endforeach
                                    <span class="badge bg-light text-dark">
                                        <i class="fas fa-users me-1"></i>
                                        {{ ['all' => 'Tous les étudiants', 'cite' => 'Par cité', 'batiment' => 'Par bâtiment', 'chambre' => 'Par chambre', 'specific' => 'Sélection spécifique'][$annonce->audience] ?? $annonce->audience }}
                                    </span>
                                </div>
                                <div class="d-flex gap-2">
                                    @if($annonce->statut === 'publie')
                                        <span class="text-muted small me-2">
                                            <i class="far fa-eye me-1"></i> {{ $annonce->vues ?? 0 }}
                                            <i class="far fa-hand-peace ms-2 me-1"></i> {{ $annonce->reactions ?? 0 }}
                                        </span>
                                    @endif

                                        <button type="button" class="btn btn-sm btn-outline-info" title="Voir l'annonce"
                                                aria-label="Voir l'annonce {{ $annonce->titre }}" onclick="viewAnnouncement(@js($annonce->id_annonce))">
                                            <i class="fas fa-eye"></i>
                                        </button>

                                        @adminroute('admin.hebergement.annonce.edit')
<button type="button" class="btn btn-sm btn-outline-primary" title="Modifier l'annonce"
                                                aria-label="Modifier l'annonce {{ $annonce->titre }}" onclick="editAnnouncement(@js($annonce->id_annonce))">
                                            <i class="fas fa-edit"></i>
                                        </button>
@endadminroute


                                    @if($annonce->statut !== 'publie')
                                        @adminroute('admin.hebergement.annonce.publish')
                                        <form action="{{ route('admin.hebergement.annonce.publish', $annonce->id_annonce) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Publier maintenant" aria-label="Publier maintenant {{ $annonce->titre }}">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </form>
                                        @endadminroute
                                    @endif

                                    @adminroute('admin.hebergement.annonce.destroy')
                                    <form action="{{ route('admin.hebergement.annonce.destroy', $annonce->id_annonce) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette annonce ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer l'annonce" aria-label="Supprimer l'annonce {{ $annonce->titre }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endadminroute
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5">
                            <i class="fas fa-bullhorn fa-3x text-muted d-block mb-3"></i>
                            <h5 class="text-muted">Aucune annonce trouvée</h5>
                            <p class="text-muted small">Créez votre première annonce en utilisant le formulaire ci-dessus</p>
                        </div>
                    @endforelse
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 no-print">
                    <div>
                        <span class="text-muted">
                            Affichage de {{ $annonces->firstItem() ?? 0 }} à {{ $annonces->lastItem() ?? 0 }} sur {{ $annonces->total() }} annonces
                        </span>
                    </div>
                    <nav>
                        {{ $annonces->onEachSide(1)->links() }}
                    </nav>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="viewAnnonceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="card-meta mb-3" id="viewModalMeta"></div>
                    <div id="viewModalContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <!--
            IMPORTANT : Select2 est initialisé via jQuery ($). Ce fichier ne charge PAS jQuery
            lui-même : il suppose qu'il est déjà chargé globalement par admin.layouts.app.
        -->
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
        <script>
            $('#annonceContenu').summernote({
                height: 400,
                lang: 'fr-FR',
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture', 'video']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ]
            });
        </script>
        <script>
            const STORE_ANNONCE_URL = "{{ route('admin.hebergement.annonce.store') }}";
            const ANNONCE_SHOW_URL_TEMPLATE   = "{{ route('admin.hebergement.annonce.show', ['id' => '__ID__']) }}";
            const ANNONCE_UPDATE_URL_TEMPLATE = "{{ route('admin.hebergement.annonce.update', ['id' => '__ID__']) }}";

            let editingId = null;

            async function fetchAnnonce(id) {
                const res = await fetch(ANNONCE_SHOW_URL_TEMPLATE.replace('__ID__', id), {
                    headers: { 'Accept': 'application/json' }
                });
                const result = await res.json();
                if (!res.ok) throw new Error(result.message || 'Annonce introuvable');
                return result.data;
            }

            async function viewAnnouncement(id) {
                try {
                    const a = await fetchAnnonce(id);
                    document.getElementById('viewModalTitle').textContent = a.titre;
                    document.getElementById('viewModalMeta').textContent =
                        `${a.statut} • ${a.priorite} • créée le ${a.created_at}`;
                    document.getElementById('viewModalContent').innerHTML = a.contenu;
                    new bootstrap.Modal(document.getElementById('viewAnnonceModal')).show();
                } catch (e) {
                    showToast(e.message, 'danger');
                }
            }

            async function editAnnouncement(id) {
                try {
                    const a = await fetchAnnonce(id);
                    editingId = id;

                    document.getElementById('annonceTitre').value = a.titre;
                    $('#annonceContenu').summernote('code', a.contenu);
                    document.getElementById('annoncePriorite').value = a.priorite;
                    document.getElementById('annonceStatut').value = a.statut;
                    document.getElementById('annonceStatut').dispatchEvent(new Event('change'));

                    if (a.statut === 'planifie' && a.date_planification) {
                        document.getElementById('annonceDatePlanifiee').value =
                            a.date_planification.replace(' ', 'T').slice(0, 16);
                    }

                    document.querySelectorAll('.channel-option').forEach(opt => {
                        const active = (a.canaux || []).includes(opt.dataset.channel);
                        opt.querySelector('input').checked = active;
                        opt.classList.toggle('active', active);
                    });

                    document.querySelectorAll('.audience-btn').forEach(b =>
                        b.classList.toggle('active', b.dataset.audience === a.audience)
                    );
                    document.getElementById('annonceAudience').value = a.audience;
                    toggleAudienceSelectors(a.audience);
                    for (const name of ['cites', 'batiments', 'chambres', 'etudiants']) {
                        const selected = (a.audience_cible?.[name] || []).map(String);
                        document.querySelectorAll('select[name="' + name + '[]"] option').forEach(option => {
                            option.selected = selected.includes(option.value);
                        });
                    }

                    document.getElementById('formSectionTitle').innerHTML =
                        '<i class="fas fa-edit me-2"></i> Modifier l’annonce';

                    document.getElementById('announcementForm').scrollIntoView({ behavior: 'smooth' });
                    showToast('Annonce chargée pour modification', 'info');
                } catch (e) {
                    showToast(e.message, 'danger');
                }
            }
        </script>
        <script>
            // ================================================================
            // INITIALISATION
            // ================================================================
            document.addEventListener('DOMContentLoaded', function() {
                $('.select2-multiple').select2({
                    placeholder: 'Sélectionner...',
                    allowClear: true,
                    width: '100%'
                });

                // Canal selector
                document.querySelectorAll('.channel-option').forEach(opt => {
                    opt.addEventListener('click', function() {
                        const checkbox = this.querySelector('input[type="checkbox"]');
                        checkbox.checked = !checkbox.checked;
                        this.classList.toggle('active');
                    });
                });

                // Audience selector
                document.querySelectorAll('.audience-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        document.querySelectorAll('.audience-btn').forEach(b => b.classList.remove('active'));
                        this.classList.add('active');
                        document.getElementById('annonceAudience').value = this.dataset.audience;
                        toggleAudienceSelectors(this.dataset.audience);
                    });
                });

                // Statut selector (bug isPlanifie corrigé ici)
                document.getElementById('annonceStatut').addEventListener('change', function() {
                    const planifContainer = document.getElementById('planificationContainer');
                    const isPlanifie = this.value === 'planifie';
                    planifContainer.style.display = isPlanifie ? 'block' : 'none';
                    if (!isPlanifie) {
                        document.getElementById('annonceDatePlanifiee').value = '';
                    }
                });

                // File input
                document.getElementById('annonceFichiers').addEventListener('change', handleFiles);
            });

            // ================================================================
            // GESTION DES FICHIERS (aperçu uniquement — l'envoi réel passe par FormData)
            // ================================================================
            function handleFiles(e) {
                const files = e.target.files;
                const list = document.getElementById('fileList');
                list.innerHTML = '';
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    const div = document.createElement('div');
                    div.className = 'd-flex justify-content-between align-items-center bg-light p-2 mb-1 rounded';
                    div.innerHTML = `
            <span><i class="fas fa-file me-2"></i> ${file.name} (${formatFileSize(file.size)})</span>
            <button type="button" class="btn btn-sm btn-outline-danger" title="Retirer ce fichier" aria-label="Retirer ${file.name}" onclick="this.parentElement.remove()">
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

            // ================================================================
            // AUDIENCE SELECTORS
            // ================================================================
            // - "Tous les étudiants"    : aucun sélecteur
            // - "Par cité"              : Cités
            // - "Par bâtiment"          : Cités + Bâtiments
            // - "Par chambre"           : Cités + Bâtiments + Chambres
            // - "Sélection spécifique"  : Étudiants
            function toggleAudienceSelectors(audience) {
                const cible = document.getElementById('audienceCible');
                const colCite = document.getElementById('col-cite');
                const colBatiment = document.getElementById('col-batiment');
                const colChambre = document.getElementById('col-chambre');
                const specific = document.getElementById('audienceSpecific');

                cible.style.display = 'none';
                colCite.style.display = 'none';
                colBatiment.style.display = 'none';
                colChambre.style.display = 'none';
                specific.style.display = 'none';

                switch (audience) {
                    case 'cite':
                        cible.style.display = 'flex';
                        colCite.style.display = 'block';
                        break;
                    case 'batiment':
                        cible.style.display = 'flex';
                        colCite.style.display = 'block';
                        colBatiment.style.display = 'block';
                        break;
                    case 'chambre':
                        cible.style.display = 'flex';
                        colCite.style.display = 'block';
                        colBatiment.style.display = 'block';
                        colChambre.style.display = 'block';
                        break;
                    case 'specific':
                        specific.style.display = 'block';
                        break;
                    case 'all':
                    default:
                        break;
                }
            }

            // ================================================================
            // FORMULAIRE
            // ================================================================
            function resetForm() {
                document.getElementById('annonceTitre').value = '';
                $('#annonceContenu').summernote('code', '');
                document.getElementById('annonceFichiers').value = '';
                document.getElementById('fileList').innerHTML = '';
                document.getElementById('annonceStatut').value = 'brouillon';
                document.getElementById('annoncePriorite').value = 'normale';
                document.getElementById('annonceDatePlanifiee').value = '';
                document.getElementById('planificationContainer').style.display = 'none';
                document.getElementById('annonceAudience').value = 'all';

                document.getElementById('formSectionTitle').innerHTML =
                    '<i class="fas fa-plus-circle me-2"></i> Nouvelle annonce';

                document.querySelectorAll('.channel-option').forEach(opt => {
                    opt.classList.remove('active');
                    opt.querySelector('input[type="checkbox"]').checked = false;
                });
                document.querySelector('.channel-option[data-channel="flux"]').classList.add('active');
                document.querySelector('.channel-option[data-channel="flux"] input').checked = true;

                document.querySelectorAll('.audience-btn').forEach(b => b.classList.remove('active'));
                document.querySelector('.audience-btn[data-audience="all"]').classList.add('active');
                toggleAudienceSelectors('all');

                $('.select2-multiple').val(null).trigger('change');

                showToast('Formulaire réinitialisé', 'info');
            }

            // async function submitAnnouncement(forcedStatut = null) {
            //     const form = document.getElementById('announcementForm');
            //
            //     if (forcedStatut) {
            //         document.getElementById('annonceStatut').value = forcedStatut;
            //     }
            //
            //     // Summernote synchronise automatiquement le textarea sous-jacent avant lecture du FormData
            //     const formData = new FormData(form);
            //
            //     if (!formData.get('titre')?.trim()) {
            //         return showToast('Le titre est obligatoire', 'warning');
            //     }
            //     if (!formData.getAll('canaux[]').length) {
            //         return showToast('Sélectionnez au moins un canal de diffusion', 'warning');
            //     }
            //     if (formData.get('statut') === 'planifie' && !formData.get('date_planifiee')) {
            //         return showToast('Choisissez une date de planification', 'warning');
            //     }
            //
            //     try {
            //         const response = await fetch(STORE_ANNONCE_URL, {
            //             method: 'POST',
            //             headers: { 'Accept': 'application/json' }, // ne PAS fixer Content-Type avec FormData
            //             body: formData
            //         });
            //
            //         const result = await response.json();
            //
            //         if (response.status === 422) {
            //             const firstError = Object.values(result.errors)[0][0];
            //             return showToast(firstError, 'warning');
            //         }
            //
            //         if (!response.ok) {
            //             return showToast(result.message || 'Erreur lors de l’enregistrement', 'danger');
            //         }
            //
            //         showToast(result.message || 'Annonce enregistrée avec succès', 'success');
            //         resetForm();
            //
            //         // La liste est rendue côté serveur : on recharge pour la voir apparaître
            //         setTimeout(() => window.location.reload(), 800);
            //
            //     } catch (err) {
            //         console.error(err);
            //         showToast('Erreur réseau, réessayez', 'danger');
            //     }
            // }


            async function submitAnnouncement(forcedStatut = null) {
                const form = document.getElementById('announcementForm');
                if (forcedStatut) document.getElementById('annonceStatut').value = forcedStatut;

                $('#annonceContenu').val($('#annonceContenu').summernote('code'));
                const formData = new FormData(form);

                if (!formData.get('titre')?.trim()) return showToast('Le titre est obligatoire', 'warning');
                if (!formData.getAll('canaux[]').length) return showToast('Sélectionnez au moins un canal de diffusion', 'warning');
                if (formData.get('statut') === 'planifie' && !formData.get('date_planifiee')) {
                    return showToast('Choisissez une date de planification', 'warning');
                }

                const isEditing = editingId !== null;
                const url = isEditing ? ANNONCE_UPDATE_URL_TEMPLATE.replace('__ID__', editingId) : STORE_ANNONCE_URL;

                if (isEditing) formData.append('_method', 'PUT'); // Laravel method spoofing (POST réel, traité comme PUT)

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json' },
                        body: formData
                    });
                    const result = await response.json();

                    if (response.status === 422) {
                        return showToast(Object.values(result.errors)[0][0], 'warning');
                    }
                    if (!response.ok) {
                        return showToast(result.message || 'Erreur', 'danger');
                    }

                    showToast(result.message, 'success');
                    resetForm();
                    setTimeout(() => window.location.reload(), 800);
                } catch (err) {
                    showToast('Erreur réseau, réessayez', 'danger');
                }
            }

            // ================================================================
            // UTILITAIRES
            // ================================================================
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
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fermer la notification"></button>
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
        </script>
    @endpush
@endsection
