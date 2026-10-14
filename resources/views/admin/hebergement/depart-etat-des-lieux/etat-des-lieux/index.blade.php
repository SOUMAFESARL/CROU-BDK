
@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'departs'
])

@section('title', 'États des lieux de sortie')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/edl-page.css') }}?v=20261010">
    <style>
        :root {
            --paper: #F5F7FB;
            --card: #FFFFFF;
            --ink: #18233B;
            --ink-soft: #778198;
            --line: #E7EBF2;
            --primary: #315EFB;
            --primary-dark: #2449D8;
            --primary-soft: #EDF1FF;
            --stamp: #315EFB;
            --ok: #20A970;
            --warn: #E29A20;
            --bad: #E45851;
            --radius: 14px;
        }

        * {
            box-sizing: border-box;
        }

        /* Statistiques */
        .stat-card {
            background: white;
            border-radius: var(--radius);
            padding: 18px;
            box-shadow: 0 7px 24px rgba(30,45,75,0.045);
            border: 1px solid var(--line);
            border-top: 3px solid var(--primary);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 12px 28px rgba(30,45,75,.09); }
        .stat-card .number { font-size: 27px; font-weight: 750; color: var(--ink); }
        .stat-card .label { font-size: var(--app-font-min, 15px); color: var(--ink-soft); font-weight: 600; }
        .stat-card .icon { width: 42px; height: 42px; display: grid; place-items: center; border-radius: 11px; font-size: 17px; color: var(--primary); background: var(--primary-soft); opacity: 1; }
        .stat-card.success { border-top-color: var(--ok); }
        .stat-card.success .icon { color: var(--ok); background: #EAF9F3; }
        .stat-card.warning { border-top-color: var(--warn); }
        .stat-card.warning .icon { color: var(--warn); background: #FFF6E5; }
        .stat-card.danger { border-top-color: var(--bad); }
        .stat-card.danger .icon { color: var(--bad); background: #FFF0EF; }

        /* Filtres */
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: var(--radius);
            border: 1px solid var(--line);
            box-shadow: 0 7px 24px rgba(30,45,75,0.045);
            margin-bottom: 25px;
        }

        .page-wrapper { background: var(--paper); }
        .container-fluid { color: var(--ink); font-family: Montserrat, sans-serif; }
        .filter-section .form-label { color: var(--ink); font-size: var(--app-font-min, 15px); }
        .filter-section .form-label i { color: var(--primary); }
        .filter-section .form-control,
        .filter-section .form-select {
            min-height: 42px;
            border-color: var(--line);
            border-radius: 9px;
            color: var(--ink);
            font-size: var(--app-font-min, 15px);
            box-shadow: none;
        }
        .filter-section .form-control:focus,
        .filter-section .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(49,94,251,.10); }
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover, .btn-primary:focus { background: var(--primary-dark); border-color: var(--primary-dark); }
        .btn-secondary { background: #667085; border-color: #667085; }

        .container-fluid > .card {
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: 0 7px 24px rgba(30,45,75,.045);
            overflow: hidden;
        }
        .container-fluid > .card .card-body { padding: 20px; }
        .container-fluid > .card h5 { color: var(--ink); font-size: var(--app-font-min, 15px); font-weight: 700; }
        .container-fluid > .card h5 i { color: var(--primary); }

        /* Table */
        .table td { vertical-align: middle; }
        .badge { font-size: var(--app-font-min, 15px); padding: 5px 10px; }
        .btn-action { padding: 4px 10px; font-size: var(--app-font-min, 15px); border-radius: 8px; margin: 2px; }

        /* ============================================ */
        /* STYLE EDL - COMME DANS VOTRE EXEMPLE ORIGINAL */
        /* ============================================ */
        .edl-container {
            max-width: 880px;
            margin: 0 auto;
            background: var(--card);
            padding: 30px;
            border-radius: var(--radius);
            border: 1px solid var(--line);
        }
        .edl-container header.top {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-bottom: 2px solid var(--ink);
            padding-bottom: 18px;
            margin-bottom: 18px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .edl-container header.top h1 {
            font-weight: 600;
            font-size: 30px;
            margin: 0 0 4px;
        }
        .edl-container header.top p {
            margin: 0;
            color: var(--ink-soft);
            font-size: var(--app-font-min, 15px);
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .edl-container .ref-stamp {
            border: 1.5px solid var(--stamp);
            color: var(--stamp);
            font-size: var(--app-font-min, 15px);
            letter-spacing: .06em;
            padding: 6px 10px;
            border-radius: 6px;
            text-align: right;
            white-space: nowrap;
        }
        .edl-container section.block {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 20px 22px;
            margin-bottom: 18px;
        }
        .edl-container h2.block-title {
            font-weight: 600;
            font-size: 18px;
            margin: 0 0 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .edl-container h2.block-title .idx {
            font-size: var(--app-font-min, 15px);
            color: #fff;
            background: var(--ink);
            border-radius: 5px;
            padding: 2px 7px;
        }
        .edl-container .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 14px;
        }
        .edl-container label {
            display: block;
            font-size: var(--app-font-min, 15px);
            color: var(--ink-soft);
            margin-bottom: 4px;
            font-weight: 500;
        }
        .edl-container input[type=text],
        .edl-container input[type=date],
        .edl-container input[type=number],
        .edl-container textarea,
        .edl-container select {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 7px;
            padding: 9px 10px;
            font-size: var(--app-font-min, 15px);
            background: #F8FAFD;
            color: var(--ink);
        }
        .edl-container textarea { resize: vertical; min-height: 60px; }

        /* Pièces */
        .edl-container .room {
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 14px;
            background: #F8FAFD;
        }
        .edl-container .room-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            gap: 10px;
        }
        .edl-container .room-head input {
            font-size: 16px;
            font-weight: 600;
            border: none;
            background: transparent;
            padding: 2px 0;
            border-bottom: 1px dashed var(--line);
        }
        .edl-container .remove-room {
            background: none;
            border: 1px solid var(--line);
            border-radius: 6px;
            color: var(--bad);
            font-size: var(--app-font-min, 15px);
            padding: 5px 9px;
            cursor: pointer;
        }
        .edl-container .remove-room:hover { background: #FFF0EF; }
        .edl-container table.elements {
            width: 100%;
            border-collapse: collapse;
            font-size: var(--app-font-min, 15px);
        }
        .edl-container table.elements th {
            text-align: left;
            color: var(--ink-soft);
            font-weight: 500;
            font-size: var(--app-font-min, 15px);
            text-transform: uppercase;
            letter-spacing: .03em;
            padding: 6px 6px;
            border-bottom: 1px solid var(--line);
        }
        .edl-container table.elements td {
            padding: 6px 6px;
            border-bottom: 1px solid #EEF1F6;
            vertical-align: top;
        }
        .edl-container table.elements td input[type=text] {
            padding: 6px 8px;
            font-size: var(--app-font-min, 15px);
        }
        .edl-container .etat-select {
            display: flex;
            gap: 4px;
        }
        .edl-container .etat-btn {
            flex: 1;
            border: 1px solid var(--line);
            border-radius: 6px;
            background: #fff;
            font-size: var(--app-font-min, 15px);
            padding: 6px 0;
            cursor: pointer;
            color: var(--ink-soft);
            font-weight: 500;
            transition: all 0.2s;
        }
        .edl-container .etat-btn[data-v="bon"].active {
            background: var(--ok);
            color: #fff;
            border-color: var(--ok);
        }
        .edl-container .etat-btn[data-v="moyen"].active {
            background: var(--warn);
            color: #fff;
            border-color: var(--warn);
        }
        .edl-container .etat-btn[data-v="mauvais"].active {
            background: var(--bad);
            color: #fff;
            border-color: var(--bad);
        }
        .edl-container .add-row,
        .edl-container .add-room {
            background: none;
            border: 1px dashed var(--ink-soft);
            border-radius: 7px;
            color: var(--ink-soft);
            font-size: var(--app-font-min, 15px);
            padding: 9px 12px;
            cursor: pointer;
            width: 100%;
            margin-top: 6px;
            transition: all 0.2s;
        }
        .edl-container .add-row:hover,
        .edl-container .add-room:hover {
            border-color: var(--ink);
            color: var(--ink);
        }

        /* ============================================ */
        /* SIGNATURES - AVEC ZONE DE DESSIN             */
        /* ============================================ */
        .edl-container .sig-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .edl-container .sig-box {
            text-align: center;
            padding: 15px;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            background: var(--card);
        }
        .edl-container .sig-pad {
            display: block;
            width: 100%;
            height: 150px;
            border: 2px dashed var(--line);
            border-radius: 7px;
            background: #F8FAFD;
            touch-action: none;
            cursor: crosshair;
            transition: border-color 0.3s ease;
        }
        .edl-container .sig-pad.disabled {
            opacity: 0.6;
            pointer-events: none;
            cursor: not-allowed;
        }
        .edl-container .sig-pad.drawn {
            border-color: var(--ok);
            border-style: solid;
        }
        .edl-container .sig-existing {
            margin-bottom: 8px;
            padding: 6px;
            background: var(--primary-soft);
            border: 1px solid var(--line);
            border-radius: 7px;
            font-size: var(--app-font-min, 15px);
            color: var(--ink-soft);
            text-align: center;
        }
        .edl-container .sig-existing img {
            max-height: 60px;
            display: block;
            margin: 4px auto 0;
        }
        .edl-container .sig-label {
            font-size: var(--app-font-min, 15px);
            font-weight: 600;
            margin: 0 0 10px 0;
        }
        .edl-container .sig-actions {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .edl-container .sig-actions button {
            border: 1px solid var(--line);
            background: #fff;
            border-radius: 6px;
            font-size: var(--app-font-min, 15px);
            padding: 5px 12px;
            cursor: pointer;
            color: var(--ink-soft);
            transition: all 0.2s;
        }
        .edl-container .sig-actions button:hover {
            color: var(--ink);
            border-color: var(--ink);
        }
        .edl-container .sig-actions .btn-outline-success {
            border-color: var(--ok);
            color: var(--ok);
        }
        .edl-container .sig-actions .btn-outline-success:hover {
            background: var(--ok);
            color: #fff;
        }
        .edl-container .sig-actions .btn-outline-danger {
            border-color: var(--bad);
            color: var(--bad);
        }
        .edl-container .sig-actions .btn-outline-danger:hover {
            background: var(--bad);
            color: #fff;
        }
        .edl-container .sig-status {
            margin-top: 8px;
            font-size: var(--app-font-min, 15px);
            display: none;
        }
        .edl-container .sig-status.show {
            display: block;
        }
        .edl-container .hint {
            font-size: var(--app-font-min, 15px);
            color: var(--ink-soft);
            margin-top: 6px;
        }

        /* Toolbar */
        .edl-container .toolbar {
            display: flex;
            justify-content: center;
            gap: 10px;
            padding: 14px 0;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .edl-container .toolbar button {
            font-weight: 600;
            font-size: var(--app-font-min, 15px);
            border-radius: 8px;
            padding: 11px 20px;
            cursor: pointer;
            border: 1px solid var(--ink);
            transition: all 0.2s;
        }
        .edl-container .toolbar button:hover {
            transform: scale(1.02);
        }
        .edl-container .toolbar .btn-secondary {
            background: #fff;
            color: var(--ink);
        }
        .edl-container .toolbar .btn-secondary:hover {
            background: #f0f0f0;
        }
        .edl-container .toolbar .btn-success {
            background: var(--ok);
            color: #fff;
            border-color: var(--ok);
        }
        .edl-container .toolbar .btn-success:hover {
            background: #17895B;
        }
        .edl-container .toolbar .btn-warning {
            background: var(--stamp);
            color: #fff;
            border-color: var(--stamp);
        }
        .edl-container .toolbar .btn-warning:hover {
            background: var(--primary-dark);
        }

        /* Modal */
        .modal-content {
            border-radius: var(--radius);
            border: none;
            overflow: hidden;
        }
        .modal-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border-radius: var(--radius) var(--radius) 0 0;
            padding: 15px 20px;
        }
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        .modal-body {
            padding: 0;
            background: var(--paper);
        }

        .edl-container {
            box-shadow: 0 12px 35px rgba(30,45,75,.07);
        }
        .edl-container header.top { border-bottom-color: var(--primary); }
        .edl-container h2.block-title .idx { background: var(--primary); }
        .edl-container section.block { box-shadow: 0 4px 14px rgba(30,45,75,.025); }
        .edl-container .add-row:hover,
        .edl-container .add-room:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-soft); }

        #studentsTable { margin-bottom: 0; }
        #studentsTable thead th {
            background: #F8F9FC;
            color: #7C879C;
            border: 0;
            padding: 12px 10px;
            font-size: var(--app-font-min, 15px);
            font-weight: 700;
            letter-spacing: .45px;
            text-transform: uppercase;
            white-space: nowrap;
        }
        #studentsTable tbody td { border-color: #EEF1F6; padding: 12px 10px; color: var(--ink); font-size: var(--app-font-min, 15px); }
        #studentsTable tbody tr:hover { background: #F8FAFF; }
        .pagination .page-link { color: var(--primary); border-color: var(--line); font-size: var(--app-font-min, 15px); }
        .pagination .page-item.active .page-link { background: var(--primary); border-color: var(--primary); }
        .edl-actions { display:flex; flex-direction:column; align-items:stretch; gap:6px; min-width:170px; }
        .edl-actions .btn { display:flex; align-items:center; justify-content:center; gap:7px; white-space:nowrap; font-size:var(--app-font-min, 15px); font-weight:650; padding:7px 10px; border-radius:8px; }
        .edl-modal-content {
            max-height: 80vh;
            overflow-y: auto;
        }

        @media print {
            .no-print { display: none !important; }
            .edl-container { border: none; padding: 0; }
            .edl-container .toolbar,
            .edl-container .add-row,
            .edl-container .add-room,
            .edl-container .remove-room,
            .edl-container .sig-actions { display: none !important; }
            .edl-container .sig-pad { border: 1px solid #ccc !important; }
        }

        /* Toast */
        #toastContainer {
            position: fixed;
            bottom: 0;
            right: 0;
            padding: 1rem;
            z-index: 9999;
        }
        .toast {
            min-width: 250px;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid py-4">

        <!-- Breadcrumbs -->
        <x-breadcrumbs
                title="États des lieux de sortie"
                :items="[
            'Gestion Hébergement' => route('admin.hebergement.index'),
            'Départs et états des lieux' => route('admin.hebergement.depart_etat_des_lieux.depart'),
            'États de sortie' => null,
        ]"
        />

        <!-- Statistiques -->
        <div class="row mb-4 no-print">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="number" id="totalEtudiants">0</div>
                            <div class="label">Total étudiants</div>
                        </div>
                        <div class="icon"><i class="fas fa-users"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="number" id="edlEntreeCount">0</div>
                            <div class="label">EDL Entrée</div>
                        </div>
                        <div class="icon"><i class="fas fa-sign-in-alt"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="number" id="edlSortieCount">0</div>
                            <div class="label">EDL Sortie</div>
                        </div>
                        <div class="icon"><i class="fas fa-sign-out-alt"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card danger">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="number" id="sansEdlCount">0</div>
                            <div class="label">Sans EDL</div>
                        </div>
                        <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="filter-section no-print">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold"><i class="fas fa-building me-1"></i> Cités</label>
                    <select class="form-select" id="filterCite">
                        <option value="">Toutes les cités</option>
                        @foreach($cites ?? [] as $cite)
                            <option value="{{ $cite->id_cite }}">{{ $cite->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold"><i class="fas fa-filter me-1"></i> Statut EDL</label>
                    <select class="form-select" id="filterStatus">
                        <option value="">Tous</option>
                        <option value="entree">État d'entrée</option>
                        <option value="sortie">État de sortie</option>
                        <option value="both">Les deux</option>
                        <option value="none">Aucun</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold"><i class="fas fa-search me-1"></i> Recherche</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchStudent" placeholder="Nom, matricule, chambre...">
                        <button class="btn btn-primary" onclick="applyFilters()"><i class="fas fa-search"></i></button>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-secondary w-100" onclick="resetFilters()">
                        <i class="fas fa-undo me-1"></i> Réinitialiser
                    </button>
                </div>
            </div>
        </div>

        <!-- Tableau -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i> Liste des états des lieux</h5>
                    @adminroute('admin.hebergement.depart_etat_des_lieux.depart')
                    <a href="{{ route('admin.hebergement.depart_etat_des_lieux.depart') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i> Demande de départ
                    </a>
                    @endadminroute
                </div>

                <div class="table-responsive">
                    <table class="table table-hover" id="studentsTable">
                        <thead class="table-light">
                        <tr>
                            <th style="width:5%">#</th>
                            <th style="width:18%">Étudiant</th>
                            <th style="width:10%">Matricule</th>
                            <th style="width:10%">Cité</th>
                            <th style="width:8%">Bâtiment</th>
                            <th style="width:8%">Chambre</th>
                            <th style="width:15%">État entrée</th>
                            <th style="width:15%">État sortie</th>
                            <th style="width:20%">Actions</th>
                        </tr>
                        </thead>
                        <tbody id="studentsTableBody"></tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 no-print">
                    <div>
                    <span class="text-muted">
                        Affichage de <span id="displayStart">1</span> à <span id="displayEnd">10</span>
                        sur <span id="totalItems">0</span> étudiants
                    </span>
                    </div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0" id="paginationControls"></ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- MODAL : VISUALISER L'ÉTAT DES LIEUX          -->
    <!-- ============================================ -->
    <div class="modal fade" id="viewEdlModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-alt me-2"></i><span id="modalEdlTitle">État des lieux</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body edl-modal-content" id="edlContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary"><span class="visually-hidden">Chargement...</span></div>
                        <p class="mt-3">Chargement de l'état des lieux...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i> Fermer</button>
                    <button type="button" class="btn btn-primary" id="printEdlBtn"><i class="fas fa-print me-1"></i> Imprimer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- MODAL : ÉDITER L'ÉTAT DES LIEUX              -->
    <!-- ============================================ -->
    <div class="modal fade" id="edlFormModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-clipboard-list me-2"></i><span id="edlFormTitle">État des lieux</span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 0;">
                    <div class="edl-container" style="max-width: 100%; padding: 20px;">

                        <form id="edlForm">
                            @csrf
                            <input type="hidden" id="edlFormMode" value="entree">
                            <input type="hidden" id="edlFormEtudiantId" value="">
                            <input type="hidden" id="edlFormId" value="">
                            <input type="hidden" id="edlFormCiteId" value="">
                            <input type="hidden" id="edlFormBatimentId" value="">
                            <input type="hidden" id="edlFormChambreId" value="">
                            <input type="hidden" id="edlFormMethod" value="PUTT">

                            <!-- HEADER -->
                            <header class="top">
                                <div>
                                    <h1 id="edlFormMainTitle">État des lieux</h1>
                                    <p>Annexe au contrat de location</p>
                                </div>
                                <div class="ref-stamp" id="edlFormStamp">REF — À COMPLÉTER</div>
                            </header>

                            <!-- Informations générales -->
                            <section class="block">
                                <h2 class="block-title"><span class="idx">01</span>Informations générales</h2>
                                <div class="grid">
                                    <div><label>Cité</label><input type="text" id="edlFormCite" readonly placeholder="Nom cité"></div>
                                    <div><label>Bâtiment</label><input type="text" id="edlFormBatiment" readonly placeholder="Bâtiment"></div>
                                    <div><label>Chambre</label><input type="text" id="edlFormChambre" readonly placeholder="Chambre"></div>
                                    <div><label>Étudiant</label><input type="text" id="edlFormEtudiant" readonly placeholder="Étudiant"></div>
                                    <div><label>Nombre de clés</label><input type="number" min="0" id="edlFormCles" placeholder="0"></div>
                                    <div><label>Date de l'état des lieux</label><input type="date" id="edlFormDate"></div>
                                    <div id="roomExitStatusField"><label>État de la chambre après la sortie <span class="text-danger">*</span></label><select id="edlRoomExitStatus" required><option value="">Choisir le nouveau statut</option><option value="disponible">Disponible — remise en location</option><option value="maintenance">Maintenance — intervention requise</option><option value="hors_service">Hors service — indisponible</option></select><small class="text-muted">Ce statut sera appliqué automatiquement lors de la clôture du séjour.</small></div>
                                </div>
                            </section>

                            <!-- État des pièces -->
                            <section class="block">
                                <h2 class="block-title"><span class="idx">02</span>État des pièces</h2>
                                <div class="rooms" id="edlFormRooms"></div>
                              {{--   <button class="add-room" type="button" onclick="addRoomEdl()">+ Ajouter une pièce</button>  --}}
                            </section>

                            <!-- Observations -->
                            <section class="block">
                                <h2 class="block-title"><span class="idx">03</span>Observations générales</h2>
                                <textarea id="edlFormObservations" placeholder="Remarques complémentaires, réserves, accessoires..."></textarea>
                            </section>

                            <!-- ============================================ -->
                            <!-- SECTION SIGNATURES AVEC ZONE DE DESSIN       -->
                            <!-- ============================================ -->
                            <section class="block">
                                <h2 class="block-title"><span class="idx">04</span>Signatures</h2>
                                <p class="hint">Signer directement dans les cadres ci-dessous (souris, doigt ou stylet).</p>

                                <div class="sig-grid">
                                    <!-- Signature CROU -->
                                    <div class="sig-box">
                                        <div class="sig-label">Le CROU</div>

                                        <!-- Signature existante -->
                                        <div id="sigCrouExisting" class="sig-existing" style="display:none;">
                                            Signature enregistrée
                                            <img id="sigCrouExistingImg" src="" alt="Signature CROU">
                                        </div>

                                        <!-- Zone de dessin -->
                                        <canvas class="sig-pad" id="sigCrouCanvas"></canvas>

                                        <!-- Boutons d'action -->
                                        <div class="sig-actions">
                                            <button type="button" class="btn-outline-danger" onclick="clearSignaturePad('sigCrouCanvas')">
                                                <i class="fas fa-eraser me-1"></i> Effacer
                                            </button>
                                            <button type="button" class="btn-outline-success" onclick="saveSignature('sigCrouCanvas')">
                                                <i class="fas fa-check me-1"></i> Valider
                                            </button>
                                        </div>

                                        <!-- Statut de validation -->
                                        <div id="sigCrouStatus" class="sig-status">
                                            <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Signature validée</span>
                                        </div>
                                    </div>

                                    <!-- Signature Étudiant -->
                                    <div class="sig-box">
                                        <div class="sig-label">Étudiant</div>

                                        <!-- Signature existante -->
                                        <div id="sigEtudiantExisting" class="sig-existing" style="display:none;">
                                            Signature enregistrée
                                            <img id="sigEtudiantExistingImg" src="" alt="Signature Étudiant">
                                        </div>

                                        <!-- Zone de dessin -->
                                        <canvas class="sig-pad" id="sigEtudiantCanvas"></canvas>

                                        <!-- Boutons d'action -->
                                        <div class="sig-actions">
                                            <button type="button" class="btn-outline-danger" onclick="clearSignaturePad('sigEtudiantCanvas')">
                                                <i class="fas fa-eraser me-1"></i> Effacer
                                            </button>
                                            <button type="button" class="btn-outline-success" onclick="saveSignature('sigEtudiantCanvas')">
                                                <i class="fas fa-check me-1"></i> Valider
                                            </button>
                                        </div>

                                        <!-- Statut de validation -->
                                        <div id="sigEtudiantStatus" class="sig-status">
                                            <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Signature validée</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 text-muted small">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Cliquez sur <strong>"Valider"</strong> après avoir dessiné votre signature pour la sauvegarder.
                                </div>
                            </section>

                            <!-- ============================================ -->
                            <!-- BOUTONS D'ACTION                            -->
                            <!-- ============================================ -->
                            <div class="toolbar">
                                <!-- <button type="button" class="btn-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-1"></i> Annuler
                                </button> -->
                                <button type="button" class="btn-warning" onclick="printEdlForm()">
                                    <i class="fas fa-print me-1"></i> Imprimer
                                </button>
                                <button type="button" class="btn-success" id="edlSaveBtn">
                                    <i class="fas fa-save me-1"></i> Mettre à jour
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- TOAST CONTAINER                             -->
    <!-- ============================================ -->
    <div id="toastContainer"></div>

    @push('scripts')
        <script src="{{ asset('js/edl-photos.js') }}?v=20261010"></script>
        <script>
            // ================================================================
            // DONNÉES
            // ================================================================
            const etudiantsData = @json($etudiantsData ?? []);
            const ELEMENTS_DEFAUT = ["Sol", "Murs", "Plafond", "Fenêtres", "Porte", "Électricité", "Équipements"];

            let filteredData = [...etudiantsData];
            let currentPage = 1;
            const itemsPerPage = 5;
            let roomCounters = { entree: 0, sortie: 0 };

            // ================================================================
            // SIGNATURES AVEC LE SYSTÈME PADS
            // ================================================================
            const pads = {};
            let savedSignatures = {};

            // URLs utilitaires (template fournie par Blade pour assurer exactitude)
            const UPDATE_EDL_URL_TEMPLATE = "{{ route('admin.hebergement.depart_etat_des_lieux.update_eld', ['id' => 'EDL_ID']) }}";
            const CREATE_EDL_URL = "{{ route('admin.hebergement.depart_etat_des_lieux.store_edl') }}";

            function setupSignaturePad(canvas) {
                const ctx = canvas.getContext("2d");
                let strokes = [];
                let hasDrawn = false;
                let isDrawing = false;
                let lastX = 0, lastY = 0;

                function resize() {
                    const rect = canvas.getBoundingClientRect();
                    const dpr = window.devicePixelRatio || 1;
                    canvas.width = rect.width * dpr;
                    canvas.height = rect.height * dpr;
                    ctx.scale(dpr, dpr);
                    ctx.lineWidth = 2;
                    ctx.lineCap = "round";
                    ctx.lineJoin = "round";
                    ctx.strokeStyle = "#18233B";
                    redraw();
                }

                function redraw() {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    strokes.forEach(stroke => {
                        if (stroke.length < 2) return;
                        ctx.beginPath();
                        ctx.moveTo(stroke[0].x, stroke[0].y);
                        for (let i = 1; i < stroke.length; i++) {
                            ctx.lineTo(stroke[i].x, stroke[i].y);
                        }
                        ctx.stroke();
                    });
                }

                function getPosition(e) {
                    const rect = canvas.getBoundingClientRect();
                    const touch = e.touches ? e.touches[0] : e;
                    return {
                        x: touch.clientX - rect.left,
                        y: touch.clientY - rect.top
                    };
                }

                function startDrawing(e) {
                    e.preventDefault();
                    isDrawing = true;
                    const pos = getPosition(e);
                    lastX = pos.x;
                    lastY = pos.y;
                    const currentStroke = [{ x: pos.x, y: pos.y }];
                    strokes.push(currentStroke);
                    hasDrawn = true;
                    canvas.classList.add('drawn');
                    // Cacher le statut
                    const statusId = canvas.id === 'sigCrouCanvas' ? 'sigCrouStatus' : 'sigEtudiantStatus';
                    document.getElementById(statusId).classList.remove('show');
                }

                function draw(e) {
                    e.preventDefault();
                    if (!isDrawing) return;
                    const pos = getPosition(e);
                    ctx.beginPath();
                    ctx.moveTo(lastX, lastY);
                    ctx.lineTo(pos.x, pos.y);
                    ctx.stroke();
                    lastX = pos.x;
                    lastY = pos.y;
                    strokes[strokes.length - 1].push({ x: pos.x, y: pos.y });
                }

                function stopDrawing(e) {
                    e.preventDefault();
                    isDrawing = false;
                }

                // Écouteurs souris
                canvas.addEventListener('mousedown', startDrawing);
                canvas.addEventListener('mousemove', draw);
                canvas.addEventListener('mouseup', stopDrawing);
                canvas.addEventListener('mouseleave', stopDrawing);

                // Écouteurs tactile
                canvas.addEventListener('touchstart', startDrawing, { passive: false });
                canvas.addEventListener('touchmove', draw, { passive: false });
                canvas.addEventListener('touchend', stopDrawing, { passive: false });

                resize();
                window.addEventListener('resize', resize);

                return {
                    resize,
                    clear: function() {
                        strokes = [];
                        hasDrawn = false;
                        isDrawing = false;
                        canvas.classList.remove('drawn');
                        ctx.clearRect(0, 0, canvas.width, canvas.height);
                        const statusId = canvas.id === 'sigCrouCanvas' ? 'sigCrouStatus' : 'sigEtudiantStatus';
                        document.getElementById(statusId).classList.remove('show');
                        delete savedSignatures[canvas.id];
                    },
                    isDrawn: function() {
                        return hasDrawn;
                    },
                    getDataURL: function() {
                        return canvas.toDataURL('image/png');
                    }
                };
            }

            function initSignaturePads() {
                document.querySelectorAll('.sig-pad').forEach(canvas => {
                    pads[canvas.id] = setupSignaturePad(canvas);
                });
            }

            function toggleEdlSaveButton(hidden) {
                const saveBtn = document.getElementById('edlSaveBtn');
                if (!saveBtn) return;
                saveBtn.style.display = hidden ? 'none' : '';
                saveBtn.hidden = hidden;
                saveBtn.disabled = hidden;
                saveBtn.classList.toggle('d-none', hidden);
            }

            function clearSignaturePad(id) {
                if (pads[id]) {
                    pads[id].clear();
                    showToast('Signature effacée', 'info');
                    if (id === 'sigCrouCanvas') {
                        savedSignatures[id] = '';
                        toggleEdlSaveButton(false);
                    }
                }
            }

            function saveSignature(id) {
                if (pads[id] && pads[id].isDrawn()) {
                    savedSignatures[id] = pads[id].getDataURL();
                    const statusId = id === 'sigCrouCanvas' ? 'sigCrouStatus' : 'sigEtudiantStatus';
                    document.getElementById(statusId).classList.add('show');
                    showToast('Signature validée avec succès !', 'success');
                    if (id === 'sigCrouCanvas') {
                        toggleEdlSaveButton(false);
                    }
                } else {
                    showToast('Veuillez d\'abord dessiner une signature', 'warning');
                }
            }

            function resizeVisibleSignaturePads() {
                document.querySelectorAll('.sig-pad').forEach(canvas => {
                    if (canvas.offsetParent !== null && pads[canvas.id]) {
                        pads[canvas.id].resize();
                    }
                });
            }

            // Activer / désactiver l'interaction d'un canvas (utile pour séparer admin / étudiant)
            function setCanvasInteractive(id, interactive) {
                const canvas = document.getElementById(id);
                if (!canvas) return;
                if (interactive) {
                    canvas.classList.remove('disabled');
                    canvas.style.pointerEvents = '';
                    canvas.style.opacity = '';
                    canvas.style.cursor = 'crosshair';
                } else {
                    canvas.classList.add('disabled');
                    canvas.style.pointerEvents = 'none';
                    canvas.style.opacity = '0.6';
                    canvas.style.cursor = 'not-allowed';
                }
            }

            // Écouter l'ouverture du modal pour redimensionner les pads
            document.addEventListener('shown.bs.modal', function(event) {
                if (event.target.id === 'edlFormModal') {
                    setTimeout(resizeVisibleSignaturePads, 300);
                }
            });

            // ================================================================
            // INITIALISATION
            // ================================================================
            document.addEventListener('DOMContentLoaded', function() {
                const citeSet = new Map();
                etudiantsData.forEach(s => citeSet.set(s.cite_id, s.cite));
                const select = document.getElementById('filterCite');
                citeSet.forEach((name, id) => {
                    select.insertAdjacentHTML('beforeend', `<option value="${id}">${name}</option>`);
                });

                renderTable();
                updateStats();
                setupEventListeners();
                initSignaturePads();
                setTimeout(resizeVisibleSignaturePads, 500);
            });

            // ================================================================
            // ÉVÉNEMENTS
            // ================================================================
            function setupEventListeners() {
                document.getElementById('searchStudent').addEventListener('keyup', applyFilters);
                document.getElementById('filterCite').addEventListener('change', applyFilters);
                document.getElementById('filterStatus').addEventListener('change', applyFilters);
                document.getElementById('printEdlBtn').addEventListener('click', printEdlContent);
                document.getElementById('edlSaveBtn').addEventListener('click', saveEdlForm);
            }

            // ================================================================
            // FILTRES
            // ================================================================
            function applyFilters() {
                const search = document.getElementById('searchStudent').value.toLowerCase().trim();
                const cite = document.getElementById('filterCite').value;
                const status = document.getElementById('filterStatus').value;

                filteredData = etudiantsData.filter(student => {
                    if (search) {
                        const searchable = [student.name, student.matricule, student.email, student.chambre, student.cite, student.batiment]
                            .join(' ').toLowerCase();
                        if (!searchable.includes(search)) return false;
                    }
                    if (cite && student.cite_id !== cite) return false;
                    if (status) {
                        const hasEntree = student.edl_entree !== null;
                        const hasSortie = student.edl_sortie !== null;
                        if (status === 'entree' && !hasEntree) return false;
                        if (status === 'sortie' && !hasSortie) return false;
                        if (status === 'both' && !(hasEntree && hasSortie)) return false;
                        if (status === 'none' && (hasEntree || hasSortie)) return false;
                    }
                    return true;
                });

                currentPage = 1;
                renderTable();
                updateStats();
            }

            function resetFilters() {
                document.getElementById('searchStudent').value = '';
                document.getElementById('filterCite').value = '';
                document.getElementById('filterStatus').value = '';
                filteredData = [...etudiantsData];
                currentPage = 1;
                renderTable();
                updateStats();
            }

            // ================================================================
            // RENDU TABLEAU
            // ================================================================
            function renderTable() {
                const tbody = document.getElementById('studentsTableBody');
                const start = (currentPage - 1) * itemsPerPage;
                const end = Math.min(start + itemsPerPage, filteredData.length);
                const pageData = filteredData.slice(start, end);

                if (pageData.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4">
            <i class="fas fa-inbox fa-2x text-muted d-block mb-2"></i>
            <span class="text-muted">Aucun étudiant trouvé</span>
        </td></tr>`;
                    document.getElementById('displayStart').textContent = 0;
                    document.getElementById('displayEnd').textContent = 0;
                    document.getElementById('totalItems').textContent = filteredData.length;
                    renderPagination();
                    return;
                }

                let html = '';
                pageData.forEach((student, index) => {
                    const globalIndex = start + index + 1;

                    let entreeBadge = student.edl_entree
                        ? `<span class="badge bg-success status-badge" title="EDL entrée du ${student.edl_entree.date}">
                  <i class="fas fa-check-circle me-1"></i> ${student.edl_entree.date}
               </span>
               <button class="btn btn-sm btn-outline-info mt-1 view-edl" data-etudiant="${student.id}" data-mode="entree">
                   <i class="fas fa-eye"></i>
               </button>`
                        : `<span class="badge bg-warning text-dark status-badge"><i class="fas fa-clock me-1"></i> Non renseigné</span>`;

                    let sortieBadge = student.edl_sortie
                        ? `<span class="badge bg-danger status-badge" title="EDL sortie du ${student.edl_sortie.date}">
                  <i class="fas fa-check-circle me-1"></i> ${student.edl_sortie.date}
               </span>
               <button class="btn btn-sm btn-outline-info mt-1 view-edl" data-etudiant="${student.id}" data-mode="sortie">
                   <i class="fas fa-eye"></i>
               </button>`
                        : `<span class="badge bg-warning text-dark status-badge"><i class="fas fa-clock me-1"></i> Non renseigné</span>`;

                    let entreeAction = '';
                    if (student.edl_entree && !student.edl_entree.signature_crou) {
                        entreeAction = `<button class="btn btn-outline-success edit-edl" data-etudiant="${student.id}" data-mode="entree">
                            <i class="fas fa-signature"></i> Signer l'état d'entrée
                        </button>`;
                    }

                    const sortieAction = student.edl_sortie
                        ? `<button class="btn btn-outline-primary view-edl" data-etudiant="${student.id}" data-mode="sortie">
                            <i class="fas fa-eye"></i> Voir l'état de sortie
                           </button>`
                        : `<button class="btn btn-danger edit-edl" data-etudiant="${student.id}" data-mode="sortie">
                            <i class="fas fa-sign-out-alt"></i> Faire l'état de sortie
                           </button>`;

                    const actionsHtml = `<div class="edl-actions">${entreeAction}${sortieAction}</div>`;

                    html += `
            <tr data-cite="${student.cite_id}" data-entree="${student.edl_entree ? '1' : '0'}" data-sortie="${student.edl_sortie ? '1' : '0'}">
                <td>${globalIndex}</td>
                <td><strong>${student.name}</strong><br><small class="text-muted">${student.email || ''}</small></td>
                <td><span class="badge bg-secondary">${student.matricule}</span></td>
                <td>${student.cite}</td>
                <td>${student.batiment}</td>
                <td><span class="badge bg-dark">${student.chambre}</span></td>
                <td>${entreeBadge}</td>
                <td>${sortieBadge}</td>
                <td>${actionsHtml}</td>
            </tr>
        `;
                });

                tbody.innerHTML = html;
                document.getElementById('displayStart').textContent = filteredData.length > 0 ? start + 1 : 0;
                document.getElementById('displayEnd').textContent = end;
                document.getElementById('totalItems').textContent = filteredData.length;

                attachEvents();
                renderPagination();
            }

            // ================================================================
            // PAGINATION
            // ================================================================
            function renderPagination() {
                const totalPages = Math.ceil(filteredData.length / itemsPerPage);
                const pagination = document.getElementById('paginationControls');

                if (totalPages <= 1) {
                    pagination.innerHTML = `
            <li class="page-item disabled"><a class="page-link" href="#">Précédent</a></li>
            <li class="page-item active"><a class="page-link" href="#">1</a></li>
            <li class="page-item disabled"><a class="page-link" href="#">Suivant</a></li>
        `;
                    return;
                }

                let html = `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" data-page="${currentPage - 1}">Précédent</a>
    </li>`;
                for (let i = 1; i <= totalPages; i++) {
                    html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
            <a class="page-link" href="#" data-page="${i}">${i}</a>
        </li>`;
                }
                html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
        <a class="page-link" href="#" data-page="${currentPage + 1}">Suivant</a>
    </li>`;

                pagination.innerHTML = html;
                pagination.querySelectorAll('.page-link[data-page]').forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        const page = parseInt(this.dataset.page);
                        if (page >= 1 && page <= totalPages) {
                            currentPage = page;
                            renderTable();
                        }
                    });
                });
            }

            // ================================================================
            // STATISTIQUES
            // ================================================================
            function updateStats() {
                document.getElementById('totalEtudiants').textContent = etudiantsData.length;
                document.getElementById('edlEntreeCount').textContent = etudiantsData.filter(s => s.edl_entree).length;
                document.getElementById('edlSortieCount').textContent = etudiantsData.filter(s => s.edl_sortie).length;
                document.getElementById('sansEdlCount').textContent = etudiantsData.filter(s => !s.edl_entree && !s.edl_sortie).length;
            }

            // ================================================================
            // ATTACHER LES ÉVÉNEMENTS
            // ================================================================
            function attachEvents() {
                document.querySelectorAll('.view-edl').forEach(btn => {
                    btn.removeEventListener('click', viewEdlHandler);
                    btn.addEventListener('click', viewEdlHandler);
                });
                document.querySelectorAll('.edit-edl').forEach(btn => {
                    btn.removeEventListener('click', editEdlHandler);
                    btn.addEventListener('click', editEdlHandler);
                });
                document.querySelectorAll('.print-edl').forEach(btn => {
                    btn.removeEventListener('click', printEdlHandler);
                    btn.addEventListener('click', printEdlHandler);
                });
            }

            // ================================================================
            // HANDLERS
            // ================================================================
            function viewEdlHandler(e) {
                e.preventDefault();
                const btn = this;
                const etudiantId = btn.dataset.etudiant;
                const mode = btn.dataset.mode;

                const student = etudiantsData.find(s => String(s.id) === String(etudiantId));
                if (!student) return showToast('Étudiant non trouvé', 'danger');

                const edl = mode === 'entree' ? student.edl_entree : student.edl_sortie;
                if (!edl) return showToast(`Aucun état ${mode === 'entree' ? "d'entrée" : "de sortie"} trouvé`, 'warning');

                document.getElementById('modalEdlTitle').textContent = `État des lieux ${mode === 'entree' ? "d'entrée" : "de sortie"} - ${student.name}`;

                const content = document.getElementById('edlContent');
                content.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>`;

                setTimeout(() => {
                    content.innerHTML = generateEdlHtml(student, mode, edl);
                }, 300);

                new bootstrap.Modal(document.getElementById('viewEdlModal')).show();
            }

            function editEdlHandler(e) {
                e.preventDefault();
                const btn = this;
                const etudiantId = btn.dataset.etudiant;
                const mode = btn.dataset.mode;

                const student = etudiantsData.find(s => String(s.id) === String(etudiantId));
                if (!student) return showToast('Étudiant non trouvé', 'danger');

                const edl = mode === 'entree' ? student.edl_entree : student.edl_sortie;

                // Remplir le formulaire
                document.getElementById('edlFormMode').value = mode;
                document.getElementById('edlFormEtudiantId').value = student.id;
                document.getElementById('edlFormId').value = edl ? edl.id : '';
                document.getElementById('edlFormMethod').value = edl ? 'PUT' : 'POST';
                document.getElementById('edlFormEtudiant').value = student.name;
                document.getElementById('edlFormCite').value = student.cite;
                document.getElementById('edlFormCiteId').value = student.cite_id || '';
                document.getElementById('edlFormBatiment').value = student.batiment;
                document.getElementById('edlFormBatimentId').value = student.batiment_id || '';
                document.getElementById('edlFormChambre').value = student.chambre;
                document.getElementById('edlFormChambreId').value = student.chambre_id || '';
                document.getElementById('edlFormDate').value = edl ? edl.date.slice(0, 10) : new Date().toISOString().slice(0, 10);
                document.getElementById('edlFormCles').value = edl?.nbre_cle_recus ?? 0;
                document.getElementById('edlFormObservations').value = edl?.observation_generale ?? '';
                document.getElementById('roomExitStatusField').style.display = mode === 'sortie' ? '' : 'none';
                document.getElementById('edlRoomExitStatus').value = '';

                document.getElementById('edlFormTitle').textContent = `${mode === 'entree' ? 'État d\'entrée' : 'État de sortie'} - ${student.name}`;
                document.getElementById('edlFormMainTitle').textContent = mode === 'entree' ? 'État des lieux d\'entrée' : 'État des lieux de sortie';

                const datestr = new Date().toISOString().slice(0,10).replace(/-/g,"");
                document.getElementById('edlFormStamp').textContent = (mode === 'entree' ? 'EDL-ENTREE-' : 'EDL-SORTIE-') + datestr;

                // Signatures existantes
                const sigCrouExisting = document.getElementById('sigCrouExisting');
                const sigEtudiantExisting = document.getElementById('sigEtudiantExisting');
                if (edl?.signature_crou) {
                    sigCrouExisting.style.display = 'block';
                    document.getElementById('sigCrouExistingImg').src = edl.signature_crou;
                    toggleEdlSaveButton(true);
                } else {
                    sigCrouExisting.style.display = 'none';
                    toggleEdlSaveButton(false);
                }
                if (edl?.signature_etudiant) {
                    sigEtudiantExisting.style.display = 'block';
                    document.getElementById('sigEtudiantExistingImg').src = edl.signature_etudiant;
                } else {
                    sigEtudiantExisting.style.display = 'none';
                }

                // Réinitialiser les signatures
                if (pads['sigCrouCanvas']) {
                    pads['sigCrouCanvas'].clear();
                    document.getElementById('sigCrouStatus').classList.remove('show');
                }
                if (pads['sigEtudiantCanvas']) {
                    pads['sigEtudiantCanvas'].clear();
                    document.getElementById('sigEtudiantStatus').classList.remove('show');
                }
                savedSignatures = {};

                // Pour l'interface d'administration : permettre uniquement au CROU de signer
                // (les champs restent en lecture seule car fournis par l'étudiant)
                setCanvasInteractive('sigCrouCanvas', true);
                setCanvasInteractive('sigEtudiantCanvas', false);

                // Pièces
                const roomsContainer = document.getElementById('edlFormRooms');
                roomsContainer.innerHTML = '';
                roomCounters[mode] = 0;

                if (edl?.pieces && edl.pieces.length > 0) {
                    edl.pieces.forEach(piece => {
                        addRoomEdl(piece.name_piece || piece.name, piece.elements, piece.images);
                    });
                } else if (mode === 'sortie' && student.edl_entree?.pieces?.length > 0) {
                    // La sortie est un document distinct, mais les constatations
                    // d'entrée servent de référence et restent disponibles.
                    student.edl_entree.pieces.forEach(piece => {
                        addRoomEdl(piece.name_piece || piece.name, piece.elements.map(el => ({...el, photo: null, photo_url: null})));
                    });
                } else {
                    ['Chambre', 'Cuisine', 'Salle de bain'].forEach(name => {
                        addRoomEdl(name, null);
                    });
                }

                // L'entrée appartient à l'étudiant : le CROU la consulte et signe.
                // La sortie appartient au CROU : ses constatations restent éditables.
                const entryReadOnly = mode === 'entree';
                ['edlFormCles', 'edlFormDate', 'edlFormObservations'].forEach(id => {
                    document.getElementById(id).readOnly = entryReadOnly;
                });
                document.querySelectorAll('#edlFormRooms .etat-btn').forEach(button => {
                    button.disabled = entryReadOnly;
                    button.style.pointerEvents = entryReadOnly ? 'none' : '';
                });

                setTimeout(resizeVisibleSignaturePads, 300);
                new bootstrap.Modal(document.getElementById('edlFormModal')).show();
                toggleEdlSaveButton(!!edl?.signature_crou);
            }

            function printEdlHandler(e) {
                e.preventDefault();
                const btn = this;
                const etudiantId = btn.dataset.etudiant;

                const student = etudiantsData.find(s => String(s.id) === String(etudiantId));
                if (!student) return showToast('Étudiant non trouvé', 'danger');

                if (!student.edl_entree && !student.edl_sortie) {
                    return showToast('Aucun état des lieux trouvé', 'warning');
                }

                const content = generateEdlHtml(student, 'both', null, true);
                const win = window.open('', '_blank', 'width=800,height=600');
                win.document.write(`
        <!DOCTYPE html>
        <html><head><title>État des lieux - ${student.name}</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>body{padding:20px;background:white;} .no-print{display:none;}</style>
            <link rel="stylesheet" href="{{ asset('files/assets/css/typography.css') }}?v=20260923">
</head><body>${content}

        </body></html>
    `);
                win.onload = function () { win.print(); win.close(); };
                win.document.close();
            }

            // ================================================================
            // GÉNÉRER HTML EDL
            // ================================================================
            function generateEdlHtml(student, mode, edl, isPrint = false) {
                const modeLabel = mode === 'entree' ? "d'entrée" : "de sortie";

                let html = `
        <div class="edl-container" style="max-width:100%;">
            <header class="top">
                <div>
                    <h1>${mode === 'both' ? 'État des lieux' : 'État des lieux ' + modeLabel}</h1>
                    <p>Annexe au contrat de location</p>
                </div>
                <div class="ref-stamp">${mode === 'entree' ? 'EDL-ENTREE-' : 'EDL-SORTIE-'}${new Date().toISOString().slice(0,10).replace(/-/g,"")}</div>
            </header>

            <section class="block">
                <h2 class="block-title"><span class="idx">01</span>Informations générales</h2>
                <div class="grid">
                    <div><label>Cité</label><input type="text" readonly value="${student.cite}"></div>
                    <div><label>Bâtiment</label><input type="text" readonly value="${student.batiment}"></div>
                    <div><label>Chambre</label><input type="text" readonly value="${student.chambre}"></div>
                    <div><label>Étudiant</label><input type="text" readonly value="${student.name}"></div>
                    <div><label>Nombre de clés</label><input type="text" readonly value="${edl?.nbre_cle_recus ?? '0'}"></div>
                    <div><label>Date de l'état</label><input type="text" readonly value="${edl?.date ?? ''}"></div>
                </div>
            </section>
    `;

                if (mode === 'both') {
                    html += generateSingleEdl(student, 'entree', student.edl_entree);
                    html += generateSingleEdl(student, 'sortie', student.edl_sortie);
                } else {
                    html += generateSingleEdl(student, mode, edl);
                }

                html += `
            <section class="block">
                <h2 class="block-title"><span class="idx">04</span>Signatures</h2>
                <div class="sig-grid">
                    <div class="sig-box">
                        <div class="sig-label">Le CROU</div>
                        ${edl?.signature_crou
                    ? `<div class="sig-existing"><img src="${edl.signature_crou}" alt="Signature CROU"></div>`
                    : `<div style="height:100px;border:1px dashed var(--line);display:flex;align-items:center;justify-content:center;color:var(--ink-soft);">Non signé</div>`}
                    </div>
                    <div class="sig-box">
                        <div class="sig-label">Étudiant</div>
                        ${edl?.signature_etudiant
                    ? `<div class="sig-existing"><img src="${edl.signature_etudiant}" alt="Signature Étudiant"></div>`
                    : `<div style="height:100px;border:1px dashed var(--line);display:flex;align-items:center;justify-content:center;color:var(--ink-soft);">Non signé</div>`}
                    </div>
                </div>
            </section>
        </div>
    `;

                return html;
            }

            function generateSingleEdl(student, mode, edl) {
                if (!edl) {
                    return `<section class="block"><div class="alert alert-warning">Aucun état ${mode === 'entree' ? "d'entrée" : "de sortie"}</div></section>`;
                }

                let piecesHtml = '';
                (edl.pieces || []).forEach(piece => {
                    let rows = '';
                    (piece.elements || []).forEach(el => {
                        const cls = el.etat === 'bon' ? 'bg-success' : el.etat === 'moyen' ? 'bg-warning' : 'bg-danger';
                        rows += `<tr>
                <td>${el.name_element || el.name || ''}</td>
                <td><span class="badge ${cls}">${(el.etat || '').toUpperCase()}</span></td>
                <td>${escapeHtml(el.observation || '')}</td>
                <td>${el.photo_url ? `<a href="${escapeHtml(el.photo_url)}" target="_blank" rel="noopener"><img src="${escapeHtml(el.photo_url)}" alt="Photo de l’élément" style="width:100px;height:100px;object-fit:cover"></a>` : 'Aucune photo'}</td>
            </tr>`;
                    });
                    piecesHtml += `
            <div class="room">
                <div class="room-head"><input type="text" readonly value="${piece.name_piece || piece.name}"></div>
                <table class="elements">
                    <thead><tr><th style="width:22%">Élément</th><th style="width:30%">État</th><th>Observations</th><th>Photo facultative</th></tr></thead>
                    <tbody>${rows}</tbody>
                </table>
                <div style="display:flex;flex-wrap:wrap;gap:10px;padding:12px">
                    ${(piece.images || []).map(photo => `<a href="${escapeHtml(photo.url)}" target="_blank" rel="noopener"><img src="${escapeHtml(photo.url)}" alt="Photo de la pièce" style="width:120px;height:120px;object-fit:cover"></a>`).join('')}
                </div>
            </div>
        `;
                });

                return `
        <section class="block">
            <h2 class="block-title"><span class="idx">02</span>État des pièces</h2>
            ${piecesHtml}
        </section>
        <section class="block">
            <h2 class="block-title"><span class="idx">03</span>Observations générales</h2>
            <p>${edl.observation_generale || 'Aucune observation'}</p>
        </section>
    `;
            }

            // ================================================================
            // ÉDITION EDL - PIÈCES
            // ================================================================
            function addRoomEdl(name, elements, photos = []) {
                const mode = document.getElementById('edlFormMode').value || 'entree';
                const readonly = mode === 'entree' ? 'readonly' : '';
                roomCounters[mode] = (roomCounters[mode] || 0) + 1;
                const container = document.getElementById('edlFormRooms');

                const div = document.createElement('div');
                div.className = 'room';
                div.id = mode + '-room' + roomCounters[mode];

                const elList = elements || ELEMENTS_DEFAUT;
                let rows = '';
                elList.forEach(el => {
                    const nameVal = typeof el === 'string' ? el : el.name_element || el.name || '';
                    const etatVal = typeof el === 'string' ? '' : el.etat || '';
                    const obsVal = typeof el === 'string' ? '' : el.observation || '';
                    rows += `
            <tr>
                <td><input ${readonly} type="text" value="${escapeHtml(nameVal)}" placeholder="Élément"></td>
                <td>
                    <div class="etat-select">
                        <button type="button" class="etat-btn ${etatVal === 'bon' ? 'active' : ''}" data-v="bon">Bon</button>
                        <button type="button" class="etat-btn ${etatVal === 'moyen' ? 'active' : ''}" data-v="moyen">Moyen</button>
                        <button type="button" class="etat-btn ${etatVal === 'mauvais' ? 'active' : ''}" data-v="mauvais">Mauvais</button>
                    </div>
                </td>
                <td><input ${readonly} type="text" value="${escapeHtml(obsVal)}" placeholder="Détail, défaut constaté..."></td>
            </tr>
        `;
                });

                div.innerHTML = `
        <div class="room-head">
            <input ${readonly} type="text" value="${escapeHtml(name || 'Nouvelle pièce')}" placeholder="Nom de la pièce">
           {{-- <button class="remove-room" type="button" onclick="this.closest('.room').remove()">Supprimer</button> --}}
        </div>
        <table class="elements">
            <thead><tr><th style="width:22%">Élément</th><th style="width:30%">État</th><th>Observations</th><th>Photo facultative</th></tr></thead>
            <tbody>${rows}</tbody>
        </table>
      {{--  <button class="add-row" type="button" onclick="addRowToRoom(this)">+ Ajouter un élément</button> --}}
    `;

                container.appendChild(div);
                wireEtatButtons(div);
                div.querySelectorAll('tbody tr').forEach((tr, index) => {
                    EdlPhotos.mountElement(tr, elList[index] || {}, mode === 'entree');
                });
                EdlPhotos.mount(div, photos, mode === 'entree');
            }

            function addRowToRoom(btn) {
                const tbody = btn.closest('.room').querySelector('tbody');
                const tr = document.createElement('tr');
                tr.innerHTML = `
        <td><input type="text" placeholder="Élément"></td>
        <td>
            <div class="etat-select">
                <button type="button" class="etat-btn" data-v="bon">Bon</button>
                <button type="button" class="etat-btn" data-v="moyen">Moyen</button>
                <button type="button" class="etat-btn" data-v="mauvais">Mauvais</button>
            </div>
        </td>
        <td><input type="text" placeholder="Détail, défaut constaté..."></td>
    `;
                tbody.appendChild(tr);
                wireEtatButtons(tr);
                EdlPhotos.mountElement(tr);
            }

            function wireEtatButtons(scope) {
                scope.querySelectorAll('.etat-btn').forEach(btn => {
                    btn.onclick = function() {
                        this.closest('.etat-select').querySelectorAll('.etat-btn').forEach(b => b.classList.remove('active'));
                        this.classList.add('active');
                    };
                });
            }

            function escapeHtml(str) {
                if (!str) return '';
                return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            }

            // ================================================================
            // LECTURE DES PIÈCES DU FORMULAIRE
            // ================================================================
            function readRoomsFromForm() {
                const rooms = [];
                document.querySelectorAll('#edlFormRooms .room').forEach((roomEl, roomIndex) => {
                    const roomName = roomEl.querySelector('.room-head input').value || 'Pièce ' + (roomIndex + 1);
                    const elements = [];
                    roomEl.querySelectorAll('tbody tr').forEach((tr, elIndex) => {
                        const inputs = tr.querySelectorAll('td input[type="text"]');
                        const activeBtn = tr.querySelector('.etat-btn.active');
                        elements.push({
                            name_element: inputs[0] ? inputs[0].value : '',
                            etat: activeBtn ? activeBtn.dataset.v : 'bon',
                            observation: inputs[1] ? inputs[1].value : '',
                            photo: EdlPhotos.readElement(tr),
                            ordre: elIndex + 1
                        });
                    });
                    rooms.push({
                        name_piece: roomName,
                        ordre: roomIndex + 1,
                        elements: elements,
                        photos: EdlPhotos.read(roomEl)
                    });
                });
                return rooms;
            }

            // ================================================================
            // SAUVEGARDE EDL
            // ================================================================
            function saveEdlForm() {
                if (!EdlPhotos.ready()) return;
                const mode = document.getElementById('edlFormMode').value;
                const etudiantId = document.getElementById('edlFormEtudiantId').value;
                const edlId = document.getElementById('edlFormId').value;
                const method = document.getElementById('edlFormMethod').value;

                // Récupérer les signatures
                let sigCrouData = savedSignatures['sigCrouCanvas'] || '';
                let sigEtudiantData = savedSignatures['sigEtudiantCanvas'] || '';

                // Si aucune signature sauvegardée, vérifier si une a été dessinée
                if (!sigCrouData && pads['sigCrouCanvas'] && pads['sigCrouCanvas'].isDrawn()) {
                    sigCrouData = pads['sigCrouCanvas'].getDataURL();
                }
                if (!sigEtudiantData && pads['sigEtudiantCanvas'] && pads['sigEtudiantCanvas'].isDrawn()) {
                    sigEtudiantData = pads['sigEtudiantCanvas'].getDataURL();
                }

                const pieces = readRoomsFromForm();
                const data = {
                    type_etat: mode,
                    id_etudiant: etudiantId,
                    id_cite: document.getElementById('edlFormCiteId').value || document.getElementById('edlFormCite').value || '',
                    id_batiment: document.getElementById('edlFormBatimentId').value || document.getElementById('edlFormBatiment').value || '',
                    id_chambre: document.getElementById('edlFormChambreId').value || document.getElementById('edlFormChambre').value || '',
                    date_etat: document.getElementById('edlFormDate').value,
                    nbre_cle_recus: document.getElementById('edlFormCles').value || 0,
                    observation_generale: document.getElementById('edlFormObservations').value,
                    pieces_json: JSON.stringify(pieces),
                    signature_crou: sigCrouData,
                    signature_etudiant: sigEtudiantData,
                    etat_chambre_sortie: document.getElementById('edlRoomExitStatus').value
                };

                if (mode === 'sortie' && !data.etat_chambre_sortie) {
                    return showToast('Choisissez le statut de la chambre après la sortie.', 'warning');
                }

              //  if (!data.date) return showToast('La date est obligatoire', 'warning');
              //  if (data.pieces.length === 0) return showToast('Ajoutez au moins une pièce', 'warning');

                // Construire l'URL correcte selon le mode/méthode
                let url = CREATE_EDL_URL;
                if (method === 'PUT') {
                    // remplace le placeholder par l'id réel
                    url = UPDATE_EDL_URL_TEMPLATE.replace('EDL_ID', edlId || '');
                }

                // Utiliser PUT réel si nécessaire
                const fetchMethod = method === 'PUT' ? 'PUT' : 'POST';
                fetch(url, {
                    method: fetchMethod,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify(data)
                })
                    .then(async res => {
                        const ct = res.headers.get('content-type') || '';
                        if (!res.ok) {
                            // essayer de récupérer message d'erreur JSON sinon texte brut
                            if (ct.includes('application/json')) {
                                const j = await res.json();
                                throw new Error(j.message || 'Erreur serveur');
                            }
                            const txt = await res.text();
                            throw new Error('Erreur serveur: ' + (txt.substring(0,200) || res.statusText));
                        }
                        if (ct.includes('application/json')) return res.json();
                        // si serveur renvoie HTML, renvoyer erreur claire
                        const text = await res.text();
                        throw new Error('Réponse inattendue du serveur: ' + text.substring(0,200));
                    })
                    .then(result => {
                        const modalEl = document.getElementById('edlFormModal');
                        if (window.bootstrap && modalEl) {
                            const modalInstance = typeof bootstrap.Modal.getInstance === 'function'
                                ? bootstrap.Modal.getInstance(modalEl)
                                : new bootstrap.Modal(modalEl);
                            modalInstance.hide();
                        }
                        showToast(result.message || 'Séjour clôturé et chambre mise à jour avec succès !', 'success');
                        setTimeout(() => location.reload(), 500);
                    })
                    .catch(err => {
                        showToast('Erreur : ' + err.message, 'danger');
                    });
            }

            // ================================================================
            // IMPRESSION DEPUIS LE MODAL EDL
            // ================================================================
            function printEdlForm() {
                const mode = document.getElementById('edlFormMode').value;
                const etudiantId = document.getElementById('edlFormEtudiantId').value;

                const student = etudiantsData.find(s => String(s.id) === String(etudiantId));
                if (!student) return showToast('Étudiant non trouvé', 'danger');

                const edl = mode === 'entree' ? student.edl_entree : student.edl_sortie;
                if (!edl) return showToast('Aucun état des lieux trouvé', 'warning');

                const content = generateEdlHtml(student, mode, edl, true);
                const win = window.open('', '_blank', 'width=800,height=600');
                win.document.write(`
        <!DOCTYPE html>
        <html><head><title>État des lieux - ${student.name}</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>body{padding:20px;background:white;}</style>
        </head><body>${content}

        </body></html>
    `);
                win.onload = function () { win.print(); win.close(); };
                win.document.close();
            }

            // ================================================================
            // IMPRESSION MODAL
            // ================================================================
            function printEdlContent() {
                const content = document.getElementById('edlContent');
                const win = window.open('', '_blank', 'width=800,height=600');
                win.document.write(`
        <!DOCTYPE html>
        <html><head><title>État des lieux</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>body{padding:20px;background:white;}</style>
        </head><body>${content.innerHTML}

        </body></html>
    `);
                win.onload = function () { win.print(); win.close(); };
                win.document.close();
            }

            // ================================================================
            // TOAST
            // ================================================================
            function showToast(message, type = 'info') {
                const colors = {
                    success: 'bg-success',
                    danger: 'bg-danger',
                    warning: 'bg-warning',
                    info: 'bg-info'
                };
                const container = document.getElementById('toastContainer');
                const toast = document.createElement('div');
                toast.className = `toast align-items-center text-white ${colors[type] || 'bg-info'} border-0`;
                toast.role = 'alert';
                toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body"><i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
                container.appendChild(toast);
                const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
                bsToast.show();
                toast.addEventListener('hidden.bs.toast', () => toast.remove());
            }

            // ================================================================
            // INITIALISATION FINALE
            // ================================================================
            renderTable();
        </script>
    @endpush
@endsection
