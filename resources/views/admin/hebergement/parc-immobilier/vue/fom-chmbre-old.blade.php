<div id="chambre-form" class="form-container"
     style="{{ $selectedType === 'chambre' ? 'display: block;' : 'display: none;' }}">
    <h4 class="card-title mb-4">{{ $editMode ? 'Modifier la Chambre' : 'Formulaire Nouvelle Chambre' }}</h4>
    <form id="form-chambre"
          action="{{ $editMode ? route('admin.hebergement.parc-immobilier.updateChambre') : route('admin.hebergement.parc-immobilier.storeChambre') }}"
          method="POST"
          enctype="multipart/form-data">
        @csrf
        @if($editMode)
            @method('PUT')
            <input type="hidden" name="code" value="{{ $itemToEdit->code ?? '' }}">
        @endif
        <input type="hidden" name="type" value="chambre">

        <!-- Ligne 1: Campus, Cité, Bâtiment -->
        <div class="form-row-three">
            <div class="form-group">
                <label>Campus <span class="required">*</span></label>
                <select name="code_campus" required class="form-select"
                        onchange="StructureManager.handleCampusChange(this.value)">
                    <option value="">Sélectionner un campus...</option>
                    @foreach($campus as $item)
                        <option value="{{ $item->code }}"
                                {{ ($editMode && $itemToEdit && $itemToEdit->cite_code == $item->code) ? 'selected' : '' }}>
                            {{ $item->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Cité de rattachement <span class="required">*</span></label>
                <select name="code_cite" id="code_citea" required class="form-select"
                        onchange="StructureManager.handleCiteChange(this.value)">
                    <option value="">Sélectionner une cité...</option>
                </select>
            </div>

            <div class="form-group">
                <label>Bâtiment <span class="required">*</span></label>
                <select name="code_batiment" id="code_batiment_chambre" required class="form-select"
                        onchange="StructureManager.handleBatimentChange(this.value)">
                    <option value="">Sélectionner un bâtiment</option>
                </select>
            </div>
        </div>

        <!-- Sélection du type de structure (déterminée automatiquement par le bâtiment) -->
        <div class="form-group">
            <label>Structure du Bâtiment</label>
            <div class="structure-type-selector">
                <button type="button" class="structure-btn active" data-structure-type="simple" id="structure-btn-simple" style="display: none;">
                    <i class="fas fa-building"></i> Structure Simple
                </button>
                <button type="button" class="structure-btn" data-structure-type="meme" id="structure-btn-meme" style="display: none;">
                    <i class="fas fa-layer-group"></i> Structure Complexe
                </button>

            </div>
        </div>

        <!-- Section Structure Dynamique - Sélection des Niveaux -->
        <div id="structure-section">
            <!-- Pour Structure Simple -->
            <!-- Dans la section structure simple, remplacer les checkboxes par des radios -->
            <div id="structure-simple-section" class="structure-container">
                <div class="form-group">
                    <label>Sélectionner l'étage <span class="required">*</span></label>
                    <div class="radio-grid" id="simple-niveaux-grid">

                        <!-- Les étages seront chargés dynamiquement en radio buttons -->
                    </div>
                </div>

                <!-- ✅ DASHBOARD DES CHAMBRES PAR NIVEAU -->
                <div id="niveau-chambres-dashboard" class="niveau-chambres-dashboard" style="display: none;">
                    <div class="dashboard-header">
                        <h4><i class="fas fa-layer-group"></i> <span class="niveau-selection-info"></span></h4>
                    </div>
                    <div class="dashboard-grid">
                        <div class="dashboard-column" id="chambres-crees-container"></div>
                        <div class="dashboard-column" id="chambres-non-crees-container"></div>
                    </div>
                </div>
            </div>

            <!-- Pour Structure Complexe (Même Hauteur) -->
            <div id="structure-complexe-meme-section" class="structure-container" style="display: none;">
                <div class="form-group">
                    <label>Sélectionner les ailes <span class="required">*</span></label>
                    <!-- ✅ CONTENEUR VIDÉ - sera rempli par JavaScript -->
                    <div class="checkbox-grid" id="meme-aile-grid">
                        <!-- Les ailes seront chargées dynamiquement depuis le serveur -->
                    </div>
                </div>

                <!-- Les conteneurs des niveaux pour chaque aile seront injectés ici -->
                <div id="meme-niveaux-containers"></div>

                <!-- ✅ DASHBOARD DES CHAMBRES PAR NIVEAU POUR STRUCTURE COMPLEXE -->
                <!-- DÉPLACÉ À L'EXTÉRIEUR DE LA SÉLECTION DES AILES, MAIS TOUJOURS DANS LA STRUCTURE -->
            </div>

            <!-- ✅ DASHBOARD DES CHAMBRES PAR NIVEAU POUR STRUCTURE COMPLEXE - DÉPLACÉ EN BAS -->
            <div id="niveau-chambres-dashboard-complexe" class="niveau-chambres-dashboard" style="display: none;">
                <div class="dashboard-header">
                    <h4><i class="fas fa-layer-group"></i> <span class="niveau-selection-info-complexe"></span></h4>
                </div>
                <div class="dashboard-grid">
                    <div class="dashboard-column" id="chambres-crees-container-complexe"></div>
                    <div class="dashboard-column" id="chambres-non-crees-container-complexe"></div>
                </div>
            </div>
        </div>




        <div class="form-actions" id="form-actions" style="display: none;">
            <button type="submit" class="btn btn-info">
                <i class="fas fa-save"></i> <span id="submit-btn-text">Enregistrer les Chambres</span>
            </button>
            <button type="button" class="btn btn-secondary" onclick="StructureManager.resetForm()">
                <i class="fas fa-redo"></i> Réinitialiser
            </button>
        </div>
    </form>
</div>

<style>
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }

    .form-group label .required {
        color: #dc3545;
    }

    .structure-btn i {
        font-size: 24px;
    }

    .radio-grid label, .checkbox-grid label {
        display: flex;
        align-items: center;
        padding: 10px 15px;
        background: white;
        border: 2px solid #dee2e6;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .checkbox-grid label {
        padding: 8px 12px;
        border: 1px solid #dee2e6;
    }

    .radio-grid label:hover, .checkbox-grid label:hover {
        background: #e9ecef;
        border-color: #adb5bd;
    }

    .radio-grid input[type="radio"], .checkbox-grid input[type="checkbox"] {
        margin-right: 8px;
        cursor: pointer;
    }

    .radio-grid input[type="radio"] {
        width: 18px;
        height: 18px;
    }

    .checkbox-grid input[type="checkbox"]:checked + span,
    .radio-grid input[type="radio"]:checked + span {
        font-weight: 600;
        color: #0066cc;
    }

    .radio-grid input[type="radio"]:checked {
        accent-color: #0066cc;
    }

    .radio-grid label:has(input[type="radio"]:checked),
    .checkbox-grid label:has(input[type="checkbox"]:checked) {
        background: #e7f3ff;
        border-color: #0066cc;
    }



    .section-title i {
        color: #0066cc;
    }



    .aile-title i {
        font-size: 18px;
    }





    .niveau-chambres-title span {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .niveau-chambres-title i {
        color: #0066cc;
    }



    /* ============================================= */
    /* STYLES POUR LES AILES DANS LA SECTION PRINCIPALE */
    /* ============================================= */

    .aile-header {
        padding: 10px 15px;
        background: #0066cc;
        color: white;
        border-radius: 8px;
        margin-bottom: 15px;
    }

    .aile-header h5 {
        color: white;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .niveau-header {
        padding: 8px 12px;
        background: #e7f3ff;
        border-radius: 6px;
        margin-bottom: 10px;
    }

    .niveau-header h6 {
        color: #0066cc;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* ============================================= */
    /* STYLES POUR LES MINI-CARTES DE CHAMBRES */
    /* ============================================= */

    .chambre-mini-card {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 10px 15px;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.2s;
    }

    .chambre-mini-card:hover {
        border-color: #0066cc;
        background: #f8f9fa;
    }

    .chambre-mini-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .chambre-mini-info i {
        color: #0066cc;
    }

    .chambre-mini-badge {
        background: #e7f3ff;
        color: #0066cc;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: var(--app-font-min, 15px);
        font-weight: 500;
    }

    /* ============================================= */
    /* STYLES POUR LES MESSAGES ET ÉTATS */
    /* ============================================= */

    .empty-chambres-message {
        text-align: center;
        padding: 20px;
        color: #6c757d;
        font-style: italic;
        background: #f8f9fa;
        border-radius: 6px;
        border: 1px dashed #dee2e6;
    }

    .alert-info {
        background-color: #d1ecf1;
        border-color: #bee5eb;
        color: #0c5460;
        padding: 12px 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert-info i {
        font-size: 18px;
    }



    .text-muted small {
        font-size: var(--app-font-min, 15px);
    }

    /* ============================================= */
    /* UTILITAIRES */
    /* ============================================= */

    .d-flex {
        display: flex;
    }

    .justify-content-between {
        justify-content: space-between;
    }

    .align-items-center {
        align-items: center;
    }

    .mb-0 {
        margin-bottom: 0 !important;
    }

    .mb-3 {
        margin-bottom: 1rem !important;
    }

    .mb-4 {
        margin-bottom: 1.5rem !important;
    }

    .mt-4 {
        margin-top: 1.5rem !important;
    }

    .mt-3 {
        margin-top: 1rem !important;
    }

    .text-left {
        text-align: left;
    }

    .text-primary {
        color: #0066cc !important;
    }

    .text-secondary {
        color: #6c757d !important;
    }

    .text-white {
        color: white !important;
    }

    /* ============================================= */
    /* RESPONSIVE DESIGN */
    /* ============================================= */

    @media (max-width: 768px) {
        .form-row-three {
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .structure-type-selector {
            flex-direction: column;
        }

        .radio-grid, .checkbox-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .form-row-two-inline, .chambre-form-fields {
            grid-template-columns: 1fr;
            gap: 10px;
        }

        .form-actions {
            flex-direction: column;
        }

        .btn {
            width: 100%;
            justify-content: center;
        }
    }

    @media (max-width: 480px) {
        .radio-grid, .checkbox-grid {
            grid-template-columns: 1fr;
        }

        .structure-btn {
            padding: 12px 15px;
            font-size: var(--app-font-min, 15px);
        }

        .structure-btn i {
            font-size: 20px;
        }
    }


    /* ============================================= */
    /* STYLES POUR LE DASHBOARD DES CHAMBRES PAR NIVEAU */
    /* ============================================= */

    .niveau-chambres-dashboard {
        margin-top: 30px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 12px;
        border: 1px solid #dee2e6;
    }

    .dashboard-header {
        margin-bottom: 20px;
        padding-bottom: 15px;

    }

    .dashboard-header h4 {
        margin: 0;
        color: #333;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .niveau-selection-info {
        color: #0066cc;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
    }

    .dashboard-column {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    /* Style des boîtes de chambres */
    .chambres-box {
        background: white;
        border-radius: 10px;
        overflow: hidden;

        border: 1px solid #dee2e6;
    }

    .chambres-box .box-header {
        padding: 15px 20px;
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .chambres-box .box-header h5 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .chambres-box .box-body {
        padding: 15px;
        max-height: 500px;
        overflow-y: auto;
    }

    /* Style spécifique pour les chambres créées */
    .chambres-crees .box-header {
        background: #d4edda;
        border-bottom: 1px solid #28a745;
    }

    .chambres-crees .box-header h5 {
        color: #155724;
    }

    .chambres-crees .box-header h5 i {
        color: #28a745;
    }

    /* Style spécifique pour les chambres non créées */
    .chambres-non-crees .box-header {
        background: #fff3cd;
        border-bottom: 1px solid #ffc107;
    }

    .chambres-non-crees .box-header h5 {
        color: #856404;
    }

    .chambres-non-crees .box-header h5 i {
        color: #ffc107;
    }

    /* Style des items de chambre */
    .chambre-item {
        padding: 12px;
        margin-bottom: 10px;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        transition: all 0.2s;
    }

    .chambre-item:hover {
        border-color: #0066cc;

    }

    .chambre-info {
        display: flex;
        gap: 12px;
    }

    .chambre-icon {
        width: 40px;
        height: 40px;
        background: #e7f3ff;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #0066cc;
        font-size: 20px;
    }

    .chambre-details {
        flex: 1;
    }

    .chambre-numero {
        font-size: 15px;
        font-weight: 600;
        color: #333;
        margin-bottom: 5px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .chambre-type {
        display: inline-block;
        padding: 2px 8px;
        background: #e7f3ff;
        color: #0066cc;
        border-radius: 4px;
        font-size: var(--app-font-min, 15px);
        font-weight: 500;
    }

    .chambre-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        font-size: var(--app-font-min, 15px);
        color: #6c757d;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .meta-item i {
        font-size: var(--app-font-min, 15px);
    }

    .statut {
        padding: 2px 8px;
        border-radius: 4px;
        font-weight: 500;
    }

    .statut-disponible {
        background: #d4edda;
        color: #155724;
    }

    .statut-occupee {
        background: #f8d7da;
        color: #721c24;
    }

    .statut-maintenance {
        background: #fff3cd;
        color: #856404;
    }

    .statut-reservee {
        background: #cce5ff;
        color: #004085;
    }

    /* Actions des chambres */
    .chambre-actions {
        margin-top: 10px;
        display: flex;
        justify-content: flex-end;
        gap: 8px;
    }

    .chambre-actions-quick {
        margin-top: 8px;
        display: flex;
        gap: 8px;
    }

    /* Chambre non créée */
    .chambre-non-creee
        background: #fffaf0;
    }

    .chambre-non-creee .chambre-icon {
        background: #fff3cd;
        color: #856404;
    }

    /* État vide */
    .empty-state {
        padding: 40px 20px;
        text-align: center;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 48px;
        margin-bottom: 15px;
        color: #adb5bd;
    }

    .empty-state p {
        margin: 0;
        font-style: italic;
    }

    /* Badges */
    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: var(--app-font-min, 15px);
        font-weight: 500;
    }

    .bg-success {
        background: #28a745;
        color: white;
    }

    .bg-warning {
        background: #ffc107;
        color: #212529;
    }

    /* Scroll personnalisé */
    .chambres-box .box-body::-webkit-scrollbar {
        width: 6px;
    }

    .chambres-box .box-body::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .chambres-box .box-body::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 10px;
    }

    .chambres-box .box-body::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    /* Responsive */
    @media (max-width: 992px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }
    }

    @media (max-width: 768px) {
        .chambre-info {
            flex-direction: column;
            align-items: flex-start;
        }

        .chambre-icon {
            width: 32px;
            height: 32px;
            font-size: 16px;
        }

        .chambre-actions-quick {
            flex-direction: column;
        }

        .chambre-actions-quick .btn {
            width: 100%;
        }

        .quick-chambre-form {
            padding: 20px;
        }

        .quick-chambre-form .form-row-two {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .quick-chambre-form .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            padding: 10px 0;
        }

        .quick-chambre-form .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }

        .quick-chambre-form .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }

        .btn-success {
            background: #28a745;
            color: white;
            border: none;
        }

        .btn-success:hover {
            background: #218838;
        }
    }



    /* ============================================= */
    /* STYLES POUR LA SÉLECTION DES ÉTAGES/AILES */
    /* ============================================= */

    /* Style de base pour les conteneurs de sélection */
    .checkbox-grid, .radio-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 12px;
        margin: 15px 0;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 12px;
        border: 1px solid #e9ecef;
    }

    /* Style des items (checkboxes et radios) */
    .checkbox-item, .radio-item {
        position: relative;
        display: flex;
        align-items: center;
        padding: 12px 16px;
        background: white;
        border: 2px solid #dee2e6;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;

    }

    .checkbox-item:hover, .radio-item:hover {
        border-color: #0066cc;
        background: #f0f7ff;

    }

    /* Style pour les items cochés */
    .checkbox-item:has(input:checked),
    .radio-item:has(input:checked) {
        background: #e7f3ff;
        border-color: #0066cc;

    }

    /* Style des inputs */
    .checkbox-item input,
    .radio-item input {
        margin-right: 12px;
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #0066cc;
    }

    /* Style des labels */
    .checkbox-label, .radio-label {
        flex: 1;
        font-weight: 500;
        color: #495057;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .checkbox-label i, .radio-label i {
        color: #0066cc;
        font-size: 16px;
    }


    /* ============================================= */
    /* STYLES POUR LES CONTENEURS D'AILES (STRUCTURE COMPLEXE) */
    /* ============================================= */

    .meme-aile-container {

        background: white;
        border-radius: 16px;


    }

    @keyframes slideDown {
        from {
            opacity: 0;

        }
        to {
            opacity: 1;

        }
    }

    /* En-tête de l'aile */
    .meme-aile-container::before {
        content: attr(data-aile-name);
        display: block;
        padding: 10px 0 15px 0;
        font-size: 16px;
        font-weight: 600;
        color: #0066cc;

        margin-bottom: 15px;
    }

    /* Style des checkboxes de niveaux dans une aile */
    .meme-aile-container .checkbox-grid {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 12px;
        margin: 10px 0 0 0;
    }

    .meme-aile-container .checkbox-item {
        background: white;
        border-width: 1px;
        padding: 10px 14px;
    }

    .meme-aile-container .checkbox-item input {
        margin-right: 10px;
    }

    /* ============================================= */
    /* STYLES POUR LA SECTION DES CHAMBRES PAR NIVEAU/AILE */
    /* ============================================= */

    .aile-chambres-group {
        margin-bottom: 25px;
        background: white;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #e9ecef;
    }

    .aile-chambres-header {
        padding: 15px 20px;
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }

    .aile-chambres-header h6 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: #495057;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .aile-chambres-header h6 i {
        color: #0066cc;
    }

    /* ============================================= */
    /* STYLES POUR LES BOUTONS DE STRUCTURE */
    /* ============================================= */

    .structure-type-selector {
        display: flex;
        gap: 15px;
        margin: 20px 0;
        flex-wrap: wrap;
    }

    .structure-btn {
        flex: 1;
        min-width: 200px;
        padding: 15px 20px;
        background: white;
        border: 2px solid #dee2e6;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        color: #495057;
    }

    .structure-btn i {
        font-size: 28px;
        color: #6c757d;
        transition: all 0.3s ease;
    }

    .structure-btn:hover {
        border-color: #0066cc;
        background: #f8f9fa;


    }

    .structure-btn:hover i {
        color: #0066cc;
    }

    .structure-btn.active {
        background: #0066cc;
        border-color: #004d99;
        color: white;

    }

    .structure-btn.active i {
        color: white;
    }

    /* ============================================= */
    /* STYLES POUR LES MESSAGES D'INFORMATION */
    /* ============================================= */

    .structure-info-message {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 15px 20px;
        background: #e7f3ff;
        border-left: 4px solid #0066cc;
        border-radius: 8px;
        margin: 20px 0;
        color: #004d99;
        font-weight: 500;
    }

    .structure-info-message i {
        font-size: 20px;
    }



    /* ============================================= */
    /* RESPONSIVE POUR LA SÉLECTION */
    /* ============================================= */

    @media (max-width: 768px) {
        .checkbox-grid, .radio-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            padding: 12px;
        }

        .checkbox-item, .radio-item {
            padding: 10px 12px;
        }

        .checkbox-label, .radio-label {
            font-size: var(--app-font-min, 15px);
        }

        .checkbox-label i, .radio-label i {
            font-size: var(--app-font-min, 15px);
        }

        .structure-btn {
            min-width: 150px;
            padding: 12px 15px;
        }

        .structure-btn i {
            font-size: 24px;
        }
    }

    @media (max-width: 480px) {
        .checkbox-grid, .radio-grid {
            grid-template-columns: 1fr;
        }

        .structure-type-selector {
            flex-direction: column;
        }

        .structure-btn {
            width: 100%;
        }
    }

    /* ============================================= */
    /* STYLES POUR LES ÉTATS SPÉCIFIQUES */
    /* ============================================= */

    /* Style pour les étages avec des chambres existantes */
    .checkbox-item.has-chambres {
        border-left: 4px solid #28a745;
    }

    .checkbox-item.has-chambres::after {
        content: "✓";
        position: absolute;
        top: -5px;
        right: -5px;
        width: 20px;
        height: 20px;
        background: #28a745;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: var(--app-font-min, 15px);
        font-weight: bold;
        border: 2px solid white;
    }

    /* Style pour les étages complets */
    .checkbox-item.full {
        opacity: 0.6;
        background: #f8f9fa;
        border-color: #adb5bd;
        cursor: not-allowed;
    }

    .checkbox-item.full input {
        cursor: not-allowed;
    }

    /* Tooltip personnalisé */
    .checkbox-item[data-tooltip] {
        position: relative;
    }

    .checkbox-item[data-tooltip]:hover::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: 100%;
        left: 50%;

        background: #333;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: var(--app-font-min, 15px);
        white-space: nowrap;
        z-index: 1000;
        margin-bottom: 5px;
    }
</style>

@push('scripts')
    <script>
        // =============================================.
        // STRUCTURE MANAGER - Gestionnaire Principal
        // =============================================.
        class StructureManager {
            static currentStructure = null;
            static structures = {};
            static chambreCounter = 0;

            static init() {
                // Initialiser les structures
                this.structures = {
                    'simple': new SimpleStructure(),
                    'meme': new MemeHauteurStructure(),

                };

                // Initialiser les événements
                this.initEvents();

                // Activer la structure par défaut
                this.showStructure('simple');
            }



            static initEvents() {
                // Boutons de sélection de structure
                $('.structure-btn').off('click').on('click', function() {
                    const type = $(this).data('structure-type');
                    StructureManager.showStructure(type);
                });

                // Form submission
                $('#form-chambre').on('submit', function(e) {
                    return StructureManager.validateForm(e);
                });
            }

            static showStructure(type) {
                // Cacher toutes les structures
                $('.structure-container').hide();

                // Désactiver tous les boutons
                $('.structure-btn').removeClass('active');

                // Activer la structure sélectionnée
                this.currentStructure = this.structures[type];
                this.currentStructure.show();

                // Activer le bouton correspondant
                $(`.structure-btn[data-structure-type="${type}"]`).addClass('active');

                // Cacher les sections communes
                this.hideCommonSections();
            }

            static hideCommonSections() {

                $('#common-info-section').hide();
                $('#form-actions').hide();
            }

            static showCommonSections() {

                $('#form-actions').show();
            }

            static updateSubmitButton() {
                const totalChambres = $('.chambre-card').length;
                if (totalChambres > 0) {
                    $('#submit-btn-text').text(`Enregistrer ${totalChambres} Chambre${totalChambres > 1 ? 's' : ''}`);
                } else {
                    $('#submit-btn-text').text('Enregistrer les Chambres');
                }
            }

            static validateForm(e) {
                // // Only intercept if it's the main form submission and NOT the quick creation
                // if (e.target.id === 'form-chambre' && !e.target.id.includes('quick')) {
                //     e.preventDefault();
                //
                //     // Your existing JSON display code...
                //     const formData = this.collectFormData();
                //     console.dir(formData, { depth: null, colors: true });
                //     alert('Données JSON affichées dans la console !\nCliquez sur les triangles ▶ pour explorer les données.');
                //
                //     return false;
                // }
                //
                // // Allow other forms (like quick creation) to submit normally
                return true;
            }



            static collectFormData() {
                const form = document.getElementById('form-chambre');
                const formData = new FormData(form);

                // Structure de base
                const data = {
                    metadata: {
                        type: 'chambre',
                        edit_mode: form.querySelector('input[name="_method"]') ? true : false,
                        timestamp: new Date().toISOString()
                    },
                    selection_structure: {
                        campus: form.querySelector('select[name="code_campus"]').value,
                        cite: form.querySelector('select[name="code_cite"]').value,
                        batiment: form.querySelector('select[name="code_batiment"]').value,
                        structure_type: $('.structure-btn.active').data('structure-type')
                    },
                    chambres: []
                };

                // Collecter les chambres selon la structure
                if (data.selection_structure.structure_type === 'simple') {
                    this.collectSimpleStructureChambres(data);
                } else if (data.selection_structure.structure_type === 'meme') {
                    this.collectMemeStructureChambres(data);
                }

                // Calculer les totaux
                data.resume = {
                    total_chambres: data.chambres.length,
                    validation: this.validateChambresData(data.chambres)
                };

                return data;
            }

            static collectSimpleStructureChambres(data) {
                $('.chambre-card[data-structure="simple"]').each(function(index) {
                    const niveau_numero = $(this).data('niveau');  // ✅ C'est le NUMÉRO (0,1,2)

                    const chambreData = {
                        id: `chambre_simple_${index + 1}`,
                        structure_type: 'simple',
                        // ✅ Envoyer le NUMÉRO au backend
                        niveau_numero: niveau_numero,
                        numero: $(this).find('input[name$="[numero]"]').val(),
                        type_chambre: $(this).find('select[name$="[type_chambre]"]').val(),
                        niveau_label: $(this).find('.chambre-card-badge').text()
                    };
                    data.chambres.push(chambreData);
                });
            }

            // ✅ AJOUTER CETTE MÉTHODE DANS StructureManager
            static getNiveauInfo(checkbox) {
                const $checkbox = $(checkbox);
                return {
                    numero: parseInt($checkbox.data('niveau')),  // 0, 1, 2...
                    code: $checkbox.data('code'),                // NIV_XXXX_0, NIV_XXXX_1...
                    label: $checkbox.data('label')
                };
            }

            static collectMemeStructureChambres(data) {
                const memeStructure = this.structures.meme;
                let globalIndex = 1;

                // Parcourir toutes les ailes
                for (const aileNum in memeStructure.chambresByAileAndNiveau) {
                    const aileName = $(`.meme-aile-checkbox[value="${aileNum}"]`).data('aile-name');
                    const aileChambres = memeStructure.chambresByAileAndNiveau[aileNum];

                    // Parcourir tous les niveaux de cette aile
                    for (const niveau in aileChambres) {
                        const niveauChambres = aileChambres[niveau];
                        const niveauLabel = niveau == 0 ? 'RDC' : `Niveau ${niveau}`;

                        // Ajouter chaque chambre
                        niveauChambres.forEach((chambre, chambreIndex) => {
                            const chambreData = {
                                id: `chambre_meme_${globalIndex}`,
                                structure_type: 'meme',
                                aile: parseInt(aileNum),
                                aile_name: aileName,
                                niveau: parseInt(niveau),
                                niveau_label: niveauLabel,
                                numero: chambre.numero,
                                type_chambre: chambre.type_chambre,
                                chambre_numero: chambre.chambreNum
                            };
                            data.chambres.push(chambreData);
                            globalIndex++;
                        });
                    }
                }
            }

            static validateChambresData(chambres) {
                const errors = [];

                chambres.forEach((chambre, index) => {
                    if (!chambre.numero || chambre.numero.trim() === '') {
                        errors.push(`Chambre ${index + 1}: Numéro de porte manquant`);
                    }

                    // Validation supplémentaire pour les numéros de porte
                    if (!/^[A-Za-z0-9\-_]+$/.test(chambre.numero)) {
                        errors.push(`Chambre ${index + 1}: Numéro de porte invalide (caractères autorisés: lettres, chiffres, tirets)`);
                    }
                });

                return {
                    valide: errors.length === 0,
                    erreurs: errors
                };
            }

            static resetForm() {
                if (confirm('Êtes-vous sûr de vouloir réinitialiser le formulaire ?')) {
                    if (this.currentStructure) {
                        this.currentStructure.reset();
                    }
                    $('#chambres-list').empty();
                    this.hideCommonSections();
                    this.chambreCounter = 0;
                }
            }

            // Méthodes pour les sélecteurs
            static handleCampusChange(campusId) {
                if (!campusId) return;

                console.log('Loading cites for campus:', campusId);
                getRequest(
                    '{{ url("/") }}/admin/list/cite-campus?parent_id=' + campusId,
                    'code_citea',
                    'select',
                    campusId
                );
            }

            // Dans handleCiteChange, ajoutez ce log pour voir la réponse
            static handleCiteChange(citeId) {
                if (!citeId) return;

                console.log('🟣 CITÉ sélectionnée:', citeId);
                console.log('🟣 Chargement des bâtiments pour la cité:', citeId);

                getRequest(
                    '{{ url("/") }}/admin/list/batiment-cite?parent_id=' + citeId,
                    'code_batiment_chambre',
                    'select',
                    citeId,

                    function(response) {
                        console.log('🟣 Bâtiments reçus:', response);
                        console.log('🟣 Structure de la réponse:', JSON.stringify(response, null, 2));

                        // Vérifiez le HTML généré
                        setTimeout(() => {
                            const select = document.getElementById('code_batiment_chambre');
                            console.log('🟣 Options du select:');
                            Array.from(select.options).forEach(opt => {
                                console.log(`   value: "${opt.value}", text: "${opt.text}"`);
                            });
                        }, 100);
                    }
                );


            }

            static handleBatimentChange(batimentId) {
                console.log('🔵 handleBatimentChange CALLED avec:', batimentId);

                if (!batimentId) {
                    console.log('🔵 Aucun bâtiment sélectionné, on cache tout');
                    if (this.currentStructure) {
                        this.currentStructure.hide();
                    }
                    this.hideCommonSections();
                    this.hideStructureButtons();
                    this.resetEtagesSelection();
                    return;
                }

                console.log('🔵 Appel de getBatimentInfo pour:', batimentId);
                this.getBatimentInfo(batimentId);
            }

            static resetEtagesSelection() {
                // Réinitialiser les radios d'étages
                $('.simple-niveau-radio').prop('checked', false);
                $('.meme-niveau-radio').prop('checked', false);  // Ajouter cette ligne

                // Réinitialiser les étages sélectionnés dans la structure
                if (this.currentStructure) {
                    if (this.currentStructure.type === 'simple') {
                        this.currentStructure.selectedNiveau = null;
                    } else if (this.currentStructure.type === 'meme') {
                        this.currentStructure.selectedNiveau = null;  // Ajouter cette ligne
                    }
                }

                // Cacher les sections de chambres
                $('#chambres-list').empty();
                this.hideCommonSections();
            }




            static getBatimentInfo(batimentId) {
                console.log('='.repeat(50));
                console.log('🟢 SÉLECTION DU BÂTIMENT');
                console.log('='.repeat(50));
                console.log('📍 ID du bâtiment sélectionné:', batimentId);

                $.ajax({
                    url: '{{ route("admin.hebergement.parc-immobilier.getChambresByBatiment") }}',
                    method: 'GET',
                    data: { batiment_code: batimentId },
                    success: function(response) {
                        console.log('✅ RÉPONSE DU SERVEUR');
                        console.log('----------------------');
                        console.log('📦 Réponse complète:', response);

                        if (response.success) {
                            console.log('🎯 INFORMATIONS BÂTIMENT');
                            console.log('   └─ Code:', response.batiment?.code);
                            console.log('   └─ Nom:', response.batiment?.name);
                            console.log('   └─ TYPE ARCHITECTURE:', response.batiment?.type_architecture);

                            // ✅ LOG SPÉCIFIQUE POUR LES ÉTAGES
                            console.log('📊 ÉTAGES REÇUS DU SERVEUR:', response.etages);
                            console.log('   └─ Nombre d\'étages:', response.etages?.length || 0);

                            if (response.etages && response.etages.length > 0) {
                                console.table(response.etages.map(e => ({
                                    'Niveau numéro': e.niveau_numero,
                                    'Code': e.code,
                                    'Label': e.label,
                                    'ID': e.id_niveau_aile,
                                    'Code aile': e.code_aile,
                                    'Total chambres': e.total_chambres
                                })));
                            }

                            // Vérifier si le niveau 1 est présent
                            const niveau1 = response.etages?.find(e => e.niveau_numero === 1);
                            console.log('🔍 Niveau 1 trouvé?', niveau1 ? 'OUI' : 'NON', niveau1);

                            StructureManager.setStructureBasedOnBatiment(
                                response.batiment.type_architecture,
                                {
                                    ...response.batiment,
                                    etages: response.etages || [],
                                    chambres_par_etage: response.chambres_par_etage || {}
                                }
                            );
                        }

                        console.log('='.repeat(50) + '\n');
                    },
                    error: function(xhr, status, error) {
                        console.error('❌ ERREUR AJAX');
                        console.error('----------------');
                        console.error('Status:', status);
                        console.error('Error:', error);
                        console.error('Response:', xhr.responseText);
                        console.error('='.repeat(50) + '\n');
                    }
                });
            }


            static setStructureBasedOnBatiment(typeArchitecture, batimentData = null) {
                // Cacher tous les boutons de structure d'abord
                this.hideStructureButtons();

                // Afficher le message d'information
                $('#structure-info-message').show();

                // Déterminer quelle structure afficher en fonction du type_architecture
                let structureType = 'simple';

                if (typeArchitecture === 'complexe') {
                    structureType = 'meme';
                } else if (typeArchitecture === 'simple') {
                    structureType = 'simple';
                }

                // Afficher le bouton correspondant
                $(`#structure-btn-${structureType}`).show().addClass('active');

                // ✅ AFFICHER LA STRUCTURE ET PASSER LES ÉTAGES
                this.showStructure(structureType);

                if (this.currentStructure) {
                    this.currentStructure.reset();

                    // ✅ PASSER LES ÉTAGES À LA STRUCTURE
                    if (batimentData && batimentData.etages) {
                        console.log('📤 Transmission des étages à la structure:', batimentData.etages);
                        this.currentStructure.setEtages(batimentData.etages);
                    }

                    this.currentStructure.show();
                }
            }

            static hideStructureButtons() {
                // Cacher tous les boutons de structure
                $('.structure-btn').hide().removeClass('active');
                $('#structure-info-message').hide();
            }

            static showDefaultStructure() {
                // En cas d'erreur, afficher la structure simple par défaut
                this.hideStructureButtons();
                $('#structure-btn-simple').show().addClass('active');
                this.showStructure('simple');
            }

            static getNextChambreNumber() {
                return ++this.chambreCounter;
            }
        }

        // =============================================
        // SIMPLE STRUCTURE - Structure Simple
        // =============================================
        class SimpleStructure {
            constructor() {
                this.type = 'simple';
                this.selectedNiveau = null;
                this.containerId = 'structure-simple-section';
                this.batimentData = null;
                this.etages = [];
            }

            setEtages(etages) {
                console.log('📥 SimpleStructure reçoit les étages:', etages);

                // ✅ S'assurer que TOUS les étages sont conservés
                this.etages = etages.map(etage => ({
                    niveau_numero: parseInt(etage.niveau_numero ?? etage.niveau ?? 0),
                    niveau_aile_code: etage.niveau_aile_code ?? etage.code ?? `NIV_${etage.niveau}`,
                    label: etage.label || (etage.niveau_numero == 0 ? 'Rez-de-chaussée' : `Étage ${etage.niveau_numero}`),
                    total_chambres: etage.total_chambres || 0,
                    // Ajouter ces informations pour le débogage
                    id_niveau_aile: etage.id_niveau_aile,
                    code_aile: etage.code_aile,
                    aile_name: etage.aile_name
                }));

                console.log('📊 Étages traités:', this.etages);
                this.updateEtagesCheckboxes();
            }

            updateEtagesCheckboxes() {
                const grid = $('#simple-niveaux-grid');
                grid.empty();

                // Changer la classe pour utiliser radio-grid
                grid.removeClass('checkbox-grid').addClass('radio-grid');

                console.log('🔄 Création des radios pour', this.etages.length, 'étages');

                this.etages.forEach(etage => {
                    // Créer un radio button pour CHAQUE étage (un seul choix possible)
                    const radioHtml = `
            <label class="radio-item">
                <input type="radio"
                       name="niveau_selection_simple"  // Même name pour tous = un seul choix possible
                       class="simple-niveau-radio"
                       data-niveau="${etage.niveau_numero}"
                       data-code="${etage.niveau_aile_code}"
                       data-label="${etage.label}"
                       data-id="${etage.id_niveau_aile || ''}"
                       data-aile="${etage.code_aile || ''}">
                <span class="radio-label">

                    ${etage.label}
                    ${etage.total_chambres > 0 ? `<small class="text-muted">(${etage.total_chambres} ch.)</small>` : ''}
                </span>
            </label>
        `;
                    grid.append(radioHtml);
                });

                console.log('✅ Radios créées:', $('.simple-niveau-radio').length);

                // Restaurer la sélection précédente si elle existe
                this.restoreSelectedNiveau();

                // Initialiser les événements
                this.initEvents();
            }


            setBatimentData(batimentData) {
                this.batimentData = batimentData;
                this.updateEtagesCheckboxes();
            }



            restoreSelectedNiveau() {
                if (this.selectedNiveau) {
                    $(`.simple-niveau-radio[data-niveau="${this.selectedNiveau.niveau}"]`).prop('checked', true);
                }
            }

            show() {
                $(`#${this.containerId}`).show();
                this.initEvents();

                // ✅ Si on a déjà des étages, les afficher
                if (this.etages && this.etages.length > 0) {
                    this.updateEtagesCheckboxes();
                }
            }

            hide() {
                $(`#${this.containerId}`).hide();
                this.selectedNiveau = null;
            }

            initEvents() {
                // Nettoyer les anciens événements
                $('.simple-niveau-radio').off('change');

                // Ajouter les nouveaux événements
                $('.simple-niveau-radio').on('change', (e) => this.handleNiveauChange(e.target));
            }

            handleNiveauChange(radio) {
                const $radio = $(radio);

                if ($radio.is(':checked')) {
                    const niveau = $radio.data('niveau');
                    const label = $radio.data('label');
                    const code = $radio.data('code');

                    // Sauvegarder le niveau sélectionné (un seul)
                    this.selectedNiveau = {
                        niveau: niveau,
                        label: label,
                        code: code
                    };

                    // Charger les chambres pour ce niveau
                    const batimentCode = $('#code_batiment_chambre').val();
                    if (batimentCode && code) {
                        NiveauChambresManager.loadChambresByNiveau(
                            code,
                            batimentCode,
                            label,
                            'simple'
                        );
                    }

                    this.updateChambresSection();
                }
            }





            updateChambresSection() {
                if (this.selectedNiveau) {
                    this.updateNiveauSection();
                    StructureManager.showCommonSections();
                } else {
                    $('#chambres-list').empty();
                    StructureManager.hideCommonSections();
                    $('#niveau-chambres-dashboard').hide(); // Cacher le dashboard si aucun niveau sélectionné
                }
            }


            updateNiveauSection() {
                const container = $('#chambres-list');

                // Sauvegarder les données des chambres existantes
                const chambresData = this.saveChambresData();

                // Vider le conteneur
                container.empty();

                if (this.selectedNiveau) {
                    const niveauId = `niveau_simple_${this.selectedNiveau.niveau}`;
                    const chambresContainerId = `chambres_simple_${this.selectedNiveau.niveau}`;

                    // Créer la section du niveau
                    const niveauHtml = `
            <div class="niveau-section" id="${niveauId}">
                <div class="d-flex justify-content-between align-items-center mb-3" style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                    <h6 class="mb-0">
                        <i class="fas fa-layer-group"></i> ${this.selectedNiveau.label}
                    </h6>
                    @adminroute('admin.hebergement.parc-immobilier.create')
<button type="button" class="btn btn-sm btn-outline-primary"
                            onclick="StructureManager.structures.simple.addChambre(${this.selectedNiveau.niveau})">
                        <i class="fas fa-plus"></i> Ajouter une chambre
                    </button>
@endadminroute

                </div>
                <div class="chambres-container" id="${chambresContainerId}"></div>
            </div>
        `;

                    container.append(niveauHtml);

                    // Restaurer les chambres pour ce niveau
                    this.restoreChambresForNiveau(this.selectedNiveau.niveau, chambresData);
                }

                StructureManager.updateSubmitButton();
            }

// Nouvelle méthode pour sauvegarder les données
            saveChambresData() {
                const chambresData = {};

                $('.chambre-card[data-structure="simple"]').each(function() {
                    const niveau = $(this).data('niveau');
                    const chambreId = $(this).attr('id');

                    if (!chambresData[niveau]) {
                        chambresData[niveau] = [];
                    }

                    // Sauvegarder toutes les données du formulaire
                    const chambreData = {
                        id: chambreId,
                        numero: $(this).find('input[name$="[numero]"]').val(),
                        type_chambre: $(this).find('select[name$="[type_chambre]"]').val(),
                        chambreNum: chambreId.split('_').pop() // Récupérer le numéro
                    };

                    chambresData[niveau].push(chambreData);
                });

                return chambresData;
            }

// Nouvelle méthode pour restaurer les chambres
            restoreChambresForNiveau(niveau, chambresData) {
                const chambresContainerId = `chambres_simple_${niveau}`;
                const container = $(`#${chambresContainerId}`);

                if (!chambresData[niveau] || !container.length) return;

                chambresData[niveau].forEach(chambreData => {
                    // Trouver le prochain numéro disponible pour éviter les conflits
                    const existingChambreNums = $('.chambre-card').map(function() {
                        const id = $(this).attr('id');
                        const match = id.match(/chambre_simple_\d+_(\d+)/);
                        return match ? parseInt(match[1]) : 0;
                    }).get();

                    const maxNum = existingChambreNums.length > 0 ? Math.max(...existingChambreNums) : 0;
                    const chambreNum = maxNum + 1;
                    const chambreId = `chambre_simple_${niveau}_${chambreNum}`;
                    const niveauInfo = this.selectedNiveau;

                    const chambreHtml = `
            <div class="chambre-card" id="${chambreId}" data-structure="simple" data-niveau="${niveau}">
                <div class="chambre-card-header">
                    <div class="chambre-card-title">
                        <i class="fas fa-door-open"></i>
                        Chambre #${chambreNum}
                        <span class="chambre-card-badge">${niveauInfo.label}</span>
                    </div>
                    @adminroute('admin.hebergement.parc-immobilier.destroy')
<button type="button" class="btn btn-sm btn-outline-danger"
                            onclick="StructureManager.structures.simple.removeChambre('${chambreId}')">
                        <i class="fas fa-trash"></i>
                    </button>
@endadminroute

                </div>

                <input type="hidden" name="chambres[${chambreNum}][structure_type]" value="simple">
                <input type="hidden" name="chambres[${chambreNum}][niveau]" value="${niveau}">

                <div class="form-row-two-inline">
                    <div class="form-group">
                        <label>Numéro Porte <span class="required">*</span></label>
                        <input type="text"
                               name="chambres[${chambreNum}][numero]"
                               class="form-input"
                               placeholder="Ex: 101"
                               value="${chambreData.numero || ''}"
                               required>
                    </div>
                    <div class="form-group">
                        <label>Type de Chambre</label>
                        <select name="chambres[${chambreNum}][type_chambre]" class="form-select">
                            <option value="">Utiliser le type commun</option>
                            @foreach($type_chambre ?? [] as $item)
                    <option value="{{ $item->code }}" ${chambreData.type_chambre === '{{ $item->code }}' ? 'selected' : ''}>
                                {{ $item->name }}
                    </option>
@endforeach
                    </select>
                </div>
            </div>
        </div>
`;

                    container.append(chambreHtml);
                });
            }


            generateChambresForm() {
                const container = $('#chambres-list');
                container.empty();



                StructureManager.updateSubmitButton();
            }

            generateNiveauSection(niveauInfo) {
                const niveauId = `niveau_simple_${niveauInfo.niveau}`;
                return `
            <div class="niveau-section" id="${niveauId}">
                <div class="d-flex justify-content-between align-items-center mb-3" style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                    <h6 class="mb-0">
                        <i class="fas fa-layer-group"></i> ${niveauInfo.label}
                    </h6>
                    @adminroute('admin.hebergement.parc-immobilier.create')
<button type="button" class="btn btn-sm btn-outline-primary"
                            onclick="StructureManager.structures.simple.addChambre(${niveauInfo.niveau})">
                        Ajouter une chambre
                    </button>
@endadminroute

                </div>
                <div class="chambres-container" id="chambres_simple_${niveauInfo.niveau}"></div>
            </div>
        `;
            }

            addChambre(niveau_numero) {
                // ✅ Utiliser le NUMÉRO pour l'affichage et l'organisation
                const niveauInfo = this.selectedNiveau;

                // ✅ Le champ caché doit contenir le NUMÉRO pour le traitement backend
                `<input type="hidden" name="chambres[${chambreNum}][niveau_numero]" value="${niveau_numero}">`

                // ❌ NE PAS UTILISER le code ici
                // `<input type="hidden" name="chambres[${chambreNum}][niveau_aile_code]" value="${niveauInfo.code}">`
            }

            removeChambre(chambreId) {
                if (confirm('Êtes-vous sûr de vouloir supprimer cette chambre ?')) {
                    $(`#${chambreId}`).remove();
                    StructureManager.updateSubmitButton();
                }
            }

            reset() {
                $('.simple-niveau-radio').prop('checked', false);
                this.selectedNiveau = null;
                $('#chambres-list').empty();
                $('#niveau-chambres-dashboard').hide();
            }
        }


        // =============================================
        // MEME HAUTEUR STRUCTURE - Version corrigée avec affichage des chambres
        // =============================================
        // =============================================
        // MEME HAUTEUR STRUCTURE - Version avec radios pour les niveaux
        // =============================================
        class MemeHauteurStructure {
            constructor() {
                this.type = 'meme';
                this.selectedAiles = new Set();
                this.selectedNiveau = null;  // Remplacer selectedNiveaux (array) par un objet unique
                this.chambres = [];
                this.containerId = 'structure-complexe-meme-section';
                this.etages = [];
                this.ailes = [];
                this.chambreCounter = 0;
            }

            setEtages(etages) {
                console.log('📥 MemeHauteurStructure reçoit les étages:', etages);

                // Transformer les données reçues
                this.etages = etages.map(e => ({
                    id_niveau_aile: e.id_niveau_aile,
                    niveau_numero: e.niveau_numero,
                    niveau_aile_code: e.niveau_aile_code,
                    code_aile: e.code_aile,
                    aile_name: e.aile_name,
                    aile_numero: e.aile_numero,
                    label: e.label || (e.niveau_numero == 0 ? 'Rez-de-chaussée' : `Étage ${e.niveau_numero}`),
                    total_chambres: e.total_chambres || 0
                }));

                // Extraire les ailes uniques
                this.ailes = [];
                const ailesMap = new Map();
                this.etages.forEach(etage => {
                    if (!ailesMap.has(etage.code_aile)) {
                        ailesMap.set(etage.code_aile, {
                            code: etage.code_aile,
                            numero: etage.aile_numero,
                            name: etage.aile_name || `Aile ${etage.aile_numero}`
                        });
                    }
                });
                this.ailes = Array.from(ailesMap.values());

                console.log('📊 Ailes disponibles:', this.ailes);
                console.log('📊 Étages:', this.etages);

                this.updateAilesCheckboxes();
            }

            show() {
                $(`#${this.containerId}`).show();
                this.initEvents();
            }

            hide() {
                $(`#${this.containerId}`).hide();
                this.reset();
            }

            initEvents() {
                // Nettoyer les anciens événements
                $('.meme-aile-radio').off('change');
                $('.meme-niveau-radio').off('change');

                // Ajouter les nouveaux événements
                $('.meme-aile-radio').on('change', (e) => this.handleAileChange(e.target));
            }

            handleAileChange(radio) {
                const $radio = $(radio);
                const aileCode = $radio.val();
                const aileName = $radio.data('aile-name');
                const aileNumero = $radio.data('aile-numero');

                if ($radio.is(':checked')) {
                    // Vérifier s'il y avait une aile précédemment sélectionnée
                    const previousAileCode = Array.from(this.selectedAiles)[0];

                    if (previousAileCode && previousAileCode !== aileCode) {
                        // Supprimer l'ancienne aile
                        const chambresCount = this.chambres.filter(c => c.aile_code === previousAileCode).length;

                        // Supprimer l'ancien conteneur d'aile
                        $(`#meme-aile-container-${previousAileCode.replace(/[^a-zA-Z0-9]/g, '_')}`).remove();
                        this.selectedAiles.clear();
                        this.selectedNiveau = null; // Réinitialiser le niveau sélectionné
                    }

                    // Ajouter la nouvelle aile
                    this.selectedAiles.add(aileCode);
                    this.createAileContainer(aileCode, aileName, aileNumero);

                    // Cacher le dashboard si présent
                    $('#niveau-chambres-dashboard-complexe').hide();
                }

                this.updateMainChambresSection();
            }

            createAileContainer(aileCode, aileName, aileNumero) {
                const containerId = `meme-aile-container-${aileCode.replace(/[^a-zA-Z0-9]/g, '_')}`;
                const niveauxContainerId = `meme-niveaux-${aileCode.replace(/[^a-zA-Z0-9]/g, '_')}`;

                // Filtrer les étages pour cette aile
                const etagesAile = this.etages.filter(e => e.code_aile === aileCode);

                let niveauRadios = '';
                etagesAile.forEach(etage => {
                    niveauRadios += `
                <label class="radio-item">
                    <input type="radio"
                           name="niveau_selection_${aileCode.replace(/[^a-zA-Z0-9]/g, '_')}"  // Name unique par aile
                           class="meme-niveau-radio"
                           data-aile-code="${aileCode}"
                           data-aile-numero="${aileNumero}"
                           data-aile-name="${aileName}"
                           data-niveau-numero="${etage.niveau_numero}"<div id="chambre-form" class="form-container"
     style="{{ $selectedType === 'chambre' ? 'display: block;' : 'display: none;' }}">
    <h4 class="card-title mb-4">{{ $editMode ? 'Modifier la Chambre' : 'Formulaire Nouvelle Chambre' }}</h4>
    <form id="form-chambre"
          action="{{ $editMode ? route('admin.hebergement.parc-immobilier.updateChambre') : route('admin.hebergement.parc-immobilier.storeChambre') }}"
          method="POST"
          enctype="multipart/form-data">
        @csrf
                    @if($editMode)
                    @method('PUT')
                    <input type="hidden" name="code" value="{{ $itemToEdit->code ?? '' }}">
        @endif
                    <input type="hidden" name="type" value="chambre">

                    <!-- Ligne 1: Campus, Cité, Bâtiment -->
                    <div class="form-row-three">
                        <div class="form-group">
                            <label>Campus <span class="required">*</span></label>
                            <select name="code_campus" required class="form-select"
                                    onchange="StructureManager.handleCampusChange(this.value)">
                                <option value="">Sélectionner un campus...</option>
@foreach($campus as $item)
                    <option value="{{ $item->code }}"
                                {{ ($editMode && $itemToEdit && $itemToEdit->cite_code == $item->code) ? 'selected' : '' }}>
                            {{ $item->name }}
                    </option>
@endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Cité de rattachement <span class="required">*</span></label>
                    <select name="code_cite" id="code_citea" required class="form-select"
                            onchange="StructureManager.handleCiteChange(this.value)">
                        <option value="">Sélectionner une cité...</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Bâtiment <span class="required">*</span></label>
                    <select name="code_batiment" id="code_batiment_chambre" required class="form-select"
                            onchange="StructureManager.handleBatimentChange(this.value)">
                        <option value="">Sélectionner un bâtiment</option>
                    </select>
                </div>
            </div>

            <!-- Sélection du type de structure (déterminée automatiquement par le bâtiment) -->
            <div class="form-group">
                <label>Structure du Bâtiment</label>
                <div class="structure-type-selector">
                    <button type="button" class="structure-btn active" data-structure-type="simple" id="structure-btn-simple" style="display: none;">
                        <i class="fas fa-building"></i> Structure Simple
                    </button>
                    <button type="button" class="structure-btn" data-structure-type="meme" id="structure-btn-meme" style="display: none;">
                        <i class="fas fa-layer-group"></i> Structure Complexe
                    </button>

                </div>
            </div>

            <!-- Section Structure Dynamique - Sélection des Niveaux -->
            <div id="structure-section">
                <!-- Pour Structure Simple -->
                <!-- Dans la section structure simple, remplacer les checkboxes par des radios -->
                <div id="structure-simple-section" class="structure-container">
                    <div class="form-group">
                        <label>Sélectionner l'étage <span class="required">*</span></label>
                        <div class="radio-grid" id="simple-niveaux-grid">

                            <!-- Les étages seront chargés dynamiquement en radio buttons -->
                        </div>
                    </div>

                    <!-- ✅ DASHBOARD DES CHAMBRES PAR NIVEAU -->
                    <div id="niveau-chambres-dashboard" class="niveau-chambres-dashboard" style="display: none;">
                        <div class="dashboard-header">
                            <h4><i class="fas fa-layer-group"></i> <span class="niveau-selection-info"></span></h4>
                        </div>
                        <div class="dashboard-grid">
                            <div class="dashboard-column" id="chambres-crees-container"></div>
                            <div class="dashboard-column" id="chambres-non-crees-container"></div>
                        </div>
                    </div>
                </div>

                <!-- Pour Structure Complexe (Même Hauteur) -->
                <div id="structure-complexe-meme-section" class="structure-container" style="display: none;">
                    <div class="form-group">
                        <label>Sélectionner les ailes <span class="required">*</span></label>
                        <!-- ✅ CONTENEUR VIDÉ - sera rempli par JavaScript -->
                        <div class="checkbox-grid" id="meme-aile-grid">
                            <!-- Les ailes seront chargées dynamiquement depuis le serveur -->
                        </div>
                    </div>

                    <!-- Les conteneurs des niveaux pour chaque aile seront injectés ici -->
                    <div id="meme-niveaux-containers"></div>

                    <!-- ✅ DASHBOARD DES CHAMBRES PAR NIVEAU POUR STRUCTURE COMPLEXE -->
                    <!-- DÉPLACÉ À L'EXTÉRIEUR DE LA SÉLECTION DES AILES, MAIS TOUJOURS DANS LA STRUCTURE -->
                </div>

                <!-- ✅ DASHBOARD DES CHAMBRES PAR NIVEAU POUR STRUCTURE COMPLEXE - DÉPLACÉ EN BAS -->
                <div id="niveau-chambres-dashboard-complexe" class="niveau-chambres-dashboard" style="display: none;">
                    <div class="dashboard-header">
                        <h4><i class="fas fa-layer-group"></i> <span class="niveau-selection-info-complexe"></span></h4>
                    </div>
                    <div class="dashboard-grid">
                        <div class="dashboard-column" id="chambres-crees-container-complexe"></div>
                        <div class="dashboard-column" id="chambres-non-crees-container-complexe"></div>
                    </div>
                </div>
            </div>




            <div class="form-actions" id="form-actions" style="display: none;">
                <button type="submit" class="btn btn-info">
                    <i class="fas fa-save"></i> <span id="submit-btn-text">Enregistrer les Chambres</span>
                </button>
                <button type="button" class="btn btn-secondary" onclick="StructureManager.resetForm()">
                    <i class="fas fa-redo"></i> Réinitialiser
                </button>
            </div>
        </form>
    </div>

    <style>
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group label .required {
            color: #dc3545;
        }

        .structure-btn i {
            font-size: 24px;
        }

        .radio-grid label, .checkbox-grid label {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            background: white;
            border: 2px solid #dee2e6;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .checkbox-grid label {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
        }

        .radio-grid label:hover, .checkbox-grid label:hover {
            background: #e9ecef;
            border-color: #adb5bd;
        }

        .radio-grid input[type="radio"], .checkbox-grid input[type="checkbox"] {
            margin-right: 8px;
            cursor: pointer;
        }

        .radio-grid input[type="radio"] {
            width: 18px;
            height: 18px;
        }

        .checkbox-grid input[type="checkbox"]:checked + span,
        .radio-grid input[type="radio"]:checked + span {
            font-weight: 600;
            color: #0066cc;
        }

        .radio-grid input[type="radio"]:checked {
            accent-color: #0066cc;
        }

        .radio-grid label:has(input[type="radio"]:checked),
        .checkbox-grid label:has(input[type="checkbox"]:checked) {
            background: #e7f3ff;
            border-color: #0066cc;
        }



        .section-title i {
            color: #0066cc;
        }



        .aile-title i {
            font-size: 18px;
        }





        .niveau-chambres-title span {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .niveau-chambres-title i {
            color: #0066cc;
        }



        /* ============================================= */
        /* STYLES POUR LES AILES DANS LA SECTION PRINCIPALE */
        /* ============================================= */

        .aile-header {
            padding: 10px 15px;
            background: #0066cc;
            color: white;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .aile-header h5 {
            color: white;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .niveau-header {
            padding: 8px 12px;
            background: #e7f3ff;
            border-radius: 6px;
            margin-bottom: 10px;
        }

        .niveau-header h6 {
            color: #0066cc;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ============================================= */
        /* STYLES POUR LES MINI-CARTES DE CHAMBRES */
        /* ============================================= */

        .chambre-mini-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 10px 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s;
        }

        .chambre-mini-card:hover {
            border-color: #0066cc;
            background: #f8f9fa;
        }

        .chambre-mini-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chambre-mini-info i {
            color: #0066cc;
        }

        .chambre-mini-badge {
            background: #e7f3ff;
            color: #0066cc;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
        }

        /* ============================================= */
        /* STYLES POUR LES MESSAGES ET ÉTATS */
        /* ============================================= */

        .empty-chambres-message {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-style: italic;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px dashed #dee2e6;
        }

        .alert-info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-info i {
            font-size: 18px;
        }



        .text-muted small {
            font-size: var(--app-font-min, 15px);
        }

        /* ============================================= */
        /* UTILITAIRES */
        /* ============================================= */

        .d-flex {
            display: flex;
        }

        .justify-content-between {
            justify-content: space-between;
        }

        .align-items-center {
            align-items: center;
        }

        .mb-0 {
            margin-bottom: 0 !important;
        }

        .mb-3 {
            margin-bottom: 1rem !important;
        }

        .mb-4 {
            margin-bottom: 1.5rem !important;
        }

        .mt-4 {
            margin-top: 1.5rem !important;
        }

        .mt-3 {
            margin-top: 1rem !important;
        }

        .text-left {
            text-align: left;
        }

        .text-primary {
            color: #0066cc !important;
        }

        .text-secondary {
            color: #6c757d !important;
        }

        .text-white {
            color: white !important;
        }

        /* ============================================= */
        /* RESPONSIVE DESIGN */
        /* ============================================= */

        @media (max-width: 768px) {
            .form-row-three {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .structure-type-selector {
                flex-direction: column;
            }

            .radio-grid, .checkbox-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .form-row-two-inline, .chambre-form-fields {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .radio-grid, .checkbox-grid {
                grid-template-columns: 1fr;
            }

            .structure-btn {
                padding: 12px 15px;
                font-size: var(--app-font-min, 15px);
            }

            .structure-btn i {
                font-size: 20px;
            }
        }


        /* ============================================= */
        /* STYLES POUR LE DASHBOARD DES CHAMBRES PAR NIVEAU */
        /* ============================================= */

        .niveau-chambres-dashboard {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            border: 1px solid #dee2e6;
        }

        .dashboard-header {
            margin-bottom: 20px;
            padding-bottom: 15px;

        }

        .dashboard-header h4 {
            margin: 0;
            color: #333;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .niveau-selection-info {
            color: #0066cc;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .dashboard-column {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Style des boîtes de chambres */
        .chambres-box {
            background: white;
            border-radius: 10px;
            overflow: hidden;

            border: 1px solid #dee2e6;
        }

        .chambres-box .box-header {
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chambres-box .box-header h5 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chambres-box .box-body {
            padding: 15px;
            max-height: 500px;
            overflow-y: auto;
        }

        /* Style spécifique pour les chambres créées */
        .chambres-crees .box-header {
            background: #d4edda;
            border-bottom: 1px solid #28a745;
        }

        .chambres-crees .box-header h5 {
            color: #155724;
        }

        .chambres-crees .box-header h5 i {
            color: #28a745;
        }

        /* Style spécifique pour les chambres non créées */
        .chambres-non-crees .box-header {
            background: #fff3cd;
            border-bottom: 1px solid #ffc107;
        }

        .chambres-non-crees .box-header h5 {
            color: #856404;
        }

        .chambres-non-crees .box-header h5 i {
            color: #ffc107;
        }

        /* Style des items de chambre */
        .chambre-item {
            padding: 12px;
            margin-bottom: 10px;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .chambre-item:hover {
            border-color: #0066cc;

        }

        .chambre-info {
            display: flex;
            gap: 12px;
        }

        .chambre-icon {
            width: 40px;
            height: 40px;
            background: #e7f3ff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0066cc;
            font-size: 20px;
        }

        .chambre-details {
            flex: 1;
        }

        .chambre-numero {
            font-size: 15px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chambre-type {
            display: inline-block;
            padding: 2px 8px;
            background: #e7f3ff;
            color: #0066cc;
            border-radius: 4px;
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
        }

        .chambre-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            font-size: var(--app-font-min, 15px);
            color: #6c757d;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .meta-item i {
            font-size: var(--app-font-min, 15px);
        }

        .statut {
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 500;
        }

        .statut-disponible {
            background: #d4edda;
            color: #155724;
        }

        .statut-occupee {
            background: #f8d7da;
            color: #721c24;
        }

        .statut-maintenance {
            background: #fff3cd;
            color: #856404;
        }

        .statut-reservee {
            background: #cce5ff;
            color: #004085;
        }

        /* Actions des chambres */
        .chambre-actions {
            margin-top: 10px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .chambre-actions-quick {
            margin-top: 8px;
            display: flex;
            gap: 8px;
        }

        /* Chambre non créée */
        .chambre-non-creee
            background: #fffaf0;
        }

        .chambre-non-creee .chambre-icon {
            background: #fff3cd;
            color: #856404;
        }

        /* État vide */
        .empty-state {
            padding: 40px 20px;
            text-align: center;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #adb5bd;
        }

        .empty-state p {
            margin: 0;
            font-style: italic;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
        }

        .bg-success {
            background: #28a745;
            color: white;
        }

        .bg-warning {
            background: #ffc107;
            color: #212529;
        }

        /* Scroll personnalisé */
        .chambres-box .box-body::-webkit-scrollbar {
            width: 6px;
        }

        .chambres-box .box-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .chambres-box .box-body::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }

        .chambres-box .box-body::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .chambre-info {
                flex-direction: column;
                align-items: flex-start;
            }

            .chambre-icon {
                width: 32px;
                height: 32px;
                font-size: 16px;
            }

            .chambre-actions-quick {
                flex-direction: column;
            }

            .chambre-actions-quick .btn {
                width: 100%;
            }

            .quick-chambre-form {
                padding: 20px;
            }

            .quick-chambre-form .form-row-two {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 15px;
            }

            .quick-chambre-form .checkbox-group {
                display: flex;
                flex-wrap: wrap;
                gap: 15px;
                padding: 10px 0;
            }

            .quick-chambre-form .checkbox-group label {
                display: flex;
                align-items: center;
                gap: 5px;
                cursor: pointer;
            }

            .quick-chambre-form .form-actions {
                display: flex;
                gap: 10px;
                margin-top: 20px;
                padding-top: 20px;
                border-top: 1px solid #dee2e6;
            }

            .btn-success {
                background: #28a745;
                color: white;
                border: none;
            }

            .btn-success:hover {
                background: #218838;
            }
        }



        /* ============================================= */
        /* STYLES POUR LA SÉLECTION DES ÉTAGES/AILES */
        /* ============================================= */

        /* Style de base pour les conteneurs de sélection */
        .checkbox-grid, .radio-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 12px;
            margin: 15px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 12px;
            border: 1px solid #e9ecef;
        }

        /* Style des items (checkboxes et radios) */
        .checkbox-item, .radio-item {
            position: relative;
            display: flex;
            align-items: center;
            padding: 12px 16px;
            background: white;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;

        }

        .checkbox-item:hover, .radio-item:hover {
            border-color: #0066cc;
            background: #f0f7ff;

        }

        /* Style pour les items cochés */
        .checkbox-item:has(input:checked),
        .radio-item:has(input:checked) {
            background: #e7f3ff;
            border-color: #0066cc;

        }

        /* Style des inputs */
        .checkbox-item input,
        .radio-item input {
            margin-right: 12px;
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #0066cc;
        }

        /* Style des labels */
        .checkbox-label, .radio-label {
            flex: 1;
            font-weight: 500;
            color: #495057;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-label i, .radio-label i {
            color: #0066cc;
            font-size: 16px;
        }


        /* ============================================= */
        /* STYLES POUR LES CONTENEURS D'AILES (STRUCTURE COMPLEXE) */
        /* ============================================= */

        .meme-aile-container {

            background: white;
            border-radius: 16px;


        }

        @keyframes slideDown {
            from {
                opacity: 0;

            }
            to {
                opacity: 1;

            }
        }

        /* En-tête de l'aile */
        .meme-aile-container::before {
            content: attr(data-aile-name);
            display: block;
            padding: 10px 0 15px 0;
            font-size: 16px;
            font-weight: 600;
            color: #0066cc;

            margin-bottom: 15px;
        }

        /* Style des checkboxes de niveaux dans une aile */
        .meme-aile-container .checkbox-grid {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 12px;
            margin: 10px 0 0 0;
        }

        .meme-aile-container .checkbox-item {
            background: white;
            border-width: 1px;
            padding: 10px 14px;
        }

        .meme-aile-container .checkbox-item input {
            margin-right: 10px;
        }

        /* ============================================= */
        /* STYLES POUR LA SECTION DES CHAMBRES PAR NIVEAU/AILE */
        /* ============================================= */

        .aile-chambres-group {
            margin-bottom: 25px;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e9ecef;
        }

        .aile-chambres-header {
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .aile-chambres-header h6 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #495057;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .aile-chambres-header h6 i {
            color: #0066cc;
        }

        /* ============================================= */
        /* STYLES POUR LES BOUTONS DE STRUCTURE */
        /* ============================================= */

        .structure-type-selector {
            display: flex;
            gap: 15px;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        .structure-btn {
            flex: 1;
            min-width: 200px;
            padding: 15px 20px;
            background: white;
            border: 2px solid #dee2e6;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            color: #495057;
        }

        .structure-btn i {
            font-size: 28px;
            color: #6c757d;
            transition: all 0.3s ease;
        }

        .structure-btn:hover {
            border-color: #0066cc;
            background: #f8f9fa;


        }

        .structure-btn:hover i {
            color: #0066cc;
        }

        .structure-btn.active {
            background: #0066cc;
            border-color: #004d99;
            color: white;

        }

        .structure-btn.active i {
            color: white;
        }

        /* ============================================= */
        /* STYLES POUR LES MESSAGES D'INFORMATION */
        /* ============================================= */

        .structure-info-message {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 20px;
            background: #e7f3ff;
            border-left: 4px solid #0066cc;
            border-radius: 8px;
            margin: 20px 0;
            color: #004d99;
            font-weight: 500;
        }

        .structure-info-message i {
            font-size: 20px;
        }



        /* ============================================= */
        /* RESPONSIVE POUR LA SÉLECTION */
        /* ============================================= */

        @media (max-width: 768px) {
            .checkbox-grid, .radio-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
                padding: 12px;
            }

            .checkbox-item, .radio-item {
                padding: 10px 12px;
            }

            .checkbox-label, .radio-label {
                font-size: var(--app-font-min, 15px);
            }

            .checkbox-label i, .radio-label i {
                font-size: var(--app-font-min, 15px);
            }

            .structure-btn {
                min-width: 150px;
                padding: 12px 15px;
            }

            .structure-btn i {
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            .checkbox-grid, .radio-grid {
                grid-template-columns: 1fr;
            }

            .structure-type-selector {
                flex-direction: column;
            }

            .structure-btn {
                width: 100%;
            }
        }

        /* ============================================= */
        /* STYLES POUR LES ÉTATS SPÉCIFIQUES */
        /* ============================================= */

        /* Style pour les étages avec des chambres existantes */
        .checkbox-item.has-chambres {
            border-left: 4px solid #28a745;
        }

        .checkbox-item.has-chambres::after {
            content: "✓";
            position: absolute;
            top: -5px;
            right: -5px;
            width: 20px;
            height: 20px;
            background: #28a745;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: var(--app-font-min, 15px);
            font-weight: bold;
            border: 2px solid white;
        }

        /* Style pour les étages complets */
        .checkbox-item.full {
            opacity: 0.6;
            background: #f8f9fa;
            border-color: #adb5bd;
            cursor: not-allowed;
        }

        .checkbox-item.full input {
            cursor: not-allowed;
        }

        /* Tooltip personnalisé */
        .checkbox-item[data-tooltip] {
            position: relative;
        }

        .checkbox-item[data-tooltip]:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;

            background: #333;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: var(--app-font-min, 15px);
            white-space: nowrap;
            z-index: 1000;
            margin-bottom: 5px;
        }
    </style>

@push('scripts')
                    <script>
                        // =============================================.
                        // STRUCTURE MANAGER - Gestionnaire Principal
                        // =============================================.
                        class StructureManager {
                            static currentStructure = null;
                            static structures = {};
                            static chambreCounter = 0;

                            static init() {
                                // Initialiser les structures
                                this.structures = {
                                    'simple': new SimpleStructure(),
                                    'meme': new MemeHauteurStructure(),

                                };

                                // Initialiser les événements
                                this.initEvents();

                                // Activer la structure par défaut
                                this.showStructure('simple');
                            }



                            static initEvents() {
                                // Boutons de sélection de structure
                                $('.structure-btn').off('click').on('click', function() {
                                    const type = $(this).data('structure-type');
                                    StructureManager.showStructure(type);
                                });

                                // Form submission
                                $('#form-chambre').on('submit', function(e) {
                                    return StructureManager.validateForm(e);
                                });
                            }

                            static showStructure(type) {
                                // Cacher toutes les structures
                                $('.structure-container').hide();

                                // Désactiver tous les boutons
                                $('.structure-btn').removeClass('active');

                                // Activer la structure sélectionnée
                                this.currentStructure = this.structures[type];
                                this.currentStructure.show();

                                // Activer le bouton correspondant
                                $(`.structure-btn[data-structure-type="${type}"]`).addClass('active');

                // Cacher les sections communes
                this.hideCommonSections();
            }

            static hideCommonSections() {

                $('#common-info-section').hide();
                $('#form-actions').hide();
            }

            static showCommonSections() {

                $('#form-actions').show();
            }

            static updateSubmitButton() {
                const totalChambres = $('.chambre-card').length;
                if (totalChambres > 0) {
                    $('#submit-btn-text').text(`Enregistrer ${totalChambres} Chambre${totalChambres > 1 ? 's' : ''}`);
                } else {
                    $('#submit-btn-text').text('Enregistrer les Chambres');
                }
            }

            static validateForm(e) {
                // // Only intercept if it's the main form submission and NOT the quick creation
                // if (e.target.id === 'form-chambre' && !e.target.id.includes('quick')) {
                //     e.preventDefault();
                //
                //     // Your existing JSON display code...
                //     const formData = this.collectFormData();
                //     console.dir(formData, { depth: null, colors: true });
                //     alert('Données JSON affichées dans la console !\nCliquez sur les triangles ▶ pour explorer les données.');
                //
                //     return false;
                // }
                //
                // // Allow other forms (like quick creation) to submit normally
                return true;
            }



            static collectFormData() {
                const form = document.getElementById('form-chambre');
                const formData = new FormData(form);

                // Structure de base
                const data = {
                    metadata: {
                        type: 'chambre',
                        edit_mode: form.querySelector('input[name="_method"]') ? true : false,
                        timestamp: new Date().toISOString()
                    },
                    selection_structure: {
                        campus: form.querySelector('select[name="code_campus"]').value,
                        cite: form.querySelector('select[name="code_cite"]').value,
                        batiment: form.querySelector('select[name="code_batiment"]').value,
                        structure_type: $('.structure-btn.active').data('structure-type')
                    },
                    chambres: []
                };

                // Collecter les chambres selon la structure
                if (data.selection_structure.structure_type === 'simple') {
                    this.collectSimpleStructureChambres(data);
                } else if (data.selection_structure.structure_type === 'meme') {
                    this.collectMemeStructureChambres(data);
                }

                // Calculer les totaux
                data.resume = {
                    total_chambres: data.chambres.length,
                    validation: this.validateChambresData(data.chambres)
                };

                return data;
            }

            static collectSimpleStructureChambres(data) {
                $('.chambre-card[data-structure="simple"]').each(function(index) {
                    const niveau_numero = $(this).data('niveau');  // ✅ C'est le NUMÉRO (0,1,2)

                    const chambreData = {
                        id: `chambre_simple_${index + 1}`,
                        structure_type: 'simple',
                        // ✅ Envoyer le NUMÉRO au backend
                        niveau_numero: niveau_numero,
                        numero: $(this).find('input[name$="[numero]"]').val(),
                        type_chambre: $(this).find('select[name$="[type_chambre]"]').val(),
                        niveau_label: $(this).find('.chambre-card-badge').text()
                    };
                    data.chambres.push(chambreData);
                });
            }

            // ✅ AJOUTER CETTE MÉTHODE DANS StructureManager
            static getNiveauInfo(checkbox) {
                const $checkbox = $(checkbox);
                return {
                    numero: parseInt($checkbox.data('niveau')),  // 0, 1, 2...
                    code: $checkbox.data('code'),                // NIV_XXXX_0, NIV_XXXX_1...
                    label: $checkbox.data('label')
                };
            }

            static collectMemeStructureChambres(data) {
                const memeStructure = this.structures.meme;
                let globalIndex = 1;

                // Parcourir toutes les ailes
                for (const aileNum in memeStructure.chambresByAileAndNiveau) {
                    const aileName = $(`.meme-aile-checkbox[value="${aileNum}"]`).data('aile-name');
                    const aileChambres = memeStructure.chambresByAileAndNiveau[aileNum];

                    // Parcourir tous les niveaux de cette aile
                    for (const niveau in aileChambres) {
                        const niveauChambres = aileChambres[niveau];
                        const niveauLabel = niveau == 0 ? 'RDC' : `Niveau ${niveau}`;

                        // Ajouter chaque chambre
                        niveauChambres.forEach((chambre, chambreIndex) => {
                            const chambreData = {
                                id: `chambre_meme_${globalIndex}`,
                                structure_type: 'meme',
                                aile: parseInt(aileNum),
                                aile_name: aileName,
                                niveau: parseInt(niveau),
                                niveau_label: niveauLabel,
                                numero: chambre.numero,
                                type_chambre: chambre.type_chambre,
                                chambre_numero: chambre.chambreNum
                            };
                            data.chambres.push(chambreData);
                            globalIndex++;
                        });
                    }
                }
            }

            static validateChambresData(chambres) {
                const errors = [];

                chambres.forEach((chambre, index) => {
                    if (!chambre.numero || chambre.numero.trim() === '') {
                        errors.push(`Chambre ${index + 1}: Numéro de porte manquant`);
                    }

                    // Validation supplémentaire pour les numéros de porte
                    if (!/^[A-Za-z0-9\-_]+$/.test(chambre.numero)) {
                        errors.push(`Chambre ${index + 1}: Numéro de porte invalide (caractères autorisés: lettres, chiffres, tirets)`);
                    }
                });

                return {
                    valide: errors.length === 0,
                    erreurs: errors
                };
            }

            static resetForm() {
                if (confirm('Êtes-vous sûr de vouloir réinitialiser le formulaire ?')) {
                    if (this.currentStructure) {
                        this.currentStructure.reset();
                    }
                    $('#chambres-list').empty();
                    this.hideCommonSections();
                    this.chambreCounter = 0;
                }
            }

            // Méthodes pour les sélecteurs
            static handleCampusChange(campusId) {
                if (!campusId) return;

                console.log('Loading cites for campus:', campusId);
                getRequest(
                    '{{ url("/") }}/admin/list/cite-campus?parent_id=' + campusId,
                    'code_citea',
                    'select',
                    campusId
                );
            }

            // Dans handleCiteChange, ajoutez ce log pour voir la réponse
            static handleCiteChange(citeId) {
                if (!citeId) return;

                console.log('🟣 CITÉ sélectionnée:', citeId);
                console.log('🟣 Chargement des bâtiments pour la cité:', citeId);

                getRequest(
                    '{{ url("/") }}/admin/list/batiment-cite?parent_id=' + citeId,
                    'code_batiment_chambre',
                    'select',
                    citeId,

                    function(response) {
                        console.log('🟣 Bâtiments reçus:', response);
                        console.log('🟣 Structure de la réponse:', JSON.stringify(response, null, 2));

                        // Vérifiez le HTML généré
                        setTimeout(() => {
                            const select = document.getElementById('code_batiment_chambre');
                            console.log('🟣 Options du select:');
                            Array.from(select.options).forEach(opt => {
                                console.log(`   value: "${opt.value}", text: "${opt.text}"`);
                            });
                        }, 100);
                    }
                );


            }

            static handleBatimentChange(batimentId) {
                console.log('🔵 handleBatimentChange CALLED avec:', batimentId);

                if (!batimentId) {
                    console.log('🔵 Aucun bâtiment sélectionné, on cache tout');
                    if (this.currentStructure) {
                        this.currentStructure.hide();
                    }
                    this.hideCommonSections();
                    this.hideStructureButtons();
                    this.resetEtagesSelection();
                    return;
                }

                console.log('🔵 Appel de getBatimentInfo pour:', batimentId);
                this.getBatimentInfo(batimentId);
            }

            static resetEtagesSelection() {
                // Réinitialiser les radios d'étages
                $('.simple-niveau-radio').prop('checked', false);
                $('.meme-niveau-radio').prop('checked', false);  // Ajouter cette ligne

                // Réinitialiser les étages sélectionnés dans la structure
                if (this.currentStructure) {
                    if (this.currentStructure.type === 'simple') {
                        this.currentStructure.selectedNiveau = null;
                    } else if (this.currentStructure.type === 'meme') {
                        this.currentStructure.selectedNiveau = null;  // Ajouter cette ligne
                    }
                }

                // Cacher les sections de chambres
                $('#chambres-list').empty();
                this.hideCommonSections();
            }




            static getBatimentInfo(batimentId) {
                console.log('='.repeat(50));
                console.log('🟢 SÉLECTION DU BÂTIMENT');
                console.log('='.repeat(50));
                console.log('📍 ID du bâtiment sélectionné:', batimentId);

                $.ajax({
                    url: '{{ route("admin.hebergement.parc-immobilier.getChambresByBatiment") }}',
                    method: 'GET',
                    data: { batiment_code: batimentId },
                    success: function(response) {
                        console.log('✅ RÉPONSE DU SERVEUR');
                        console.log('----------------------');
                        console.log('📦 Réponse complète:', response);

                        if (response.success) {
                            console.log('🎯 INFORMATIONS BÂTIMENT');
                            console.log('   └─ Code:', response.batiment?.code);
                            console.log('   └─ Nom:', response.batiment?.name);
                            console.log('   └─ TYPE ARCHITECTURE:', response.batiment?.type_architecture);

                            // ✅ LOG SPÉCIFIQUE POUR LES ÉTAGES
                            console.log('📊 ÉTAGES REÇUS DU SERVEUR:', response.etages);
                            console.log('   └─ Nombre d\'étages:', response.etages?.length || 0);

                            if (response.etages && response.etages.length > 0) {
                                console.table(response.etages.map(e => ({
                                    'Niveau numéro': e.niveau_numero,
                                    'Code': e.code,
                                    'Label': e.label,
                                    'ID': e.id_niveau_aile,
                                    'Code aile': e.code_aile,
                                    'Total chambres': e.total_chambres
                                })));
                            }

                            // Vérifier si le niveau 1 est présent
                            const niveau1 = response.etages?.find(e => e.niveau_numero === 1);
                            console.log('🔍 Niveau 1 trouvé?', niveau1 ? 'OUI' : 'NON', niveau1);

                            StructureManager.setStructureBasedOnBatiment(
                                response.batiment.type_architecture,
                                {
                                    ...response.batiment,
                                    etages: response.etages || [],
                                    chambres_par_etage: response.chambres_par_etage || {}
                                }
                            );
                        }

                        console.log('='.repeat(50) + '\n');
                    },
                    error: function(xhr, status, error) {
                        console.error('❌ ERREUR AJAX');
                        console.error('----------------');
                        console.error('Status:', status);
                        console.error('Error:', error);
                        console.error('Response:', xhr.responseText);
                        console.error('='.repeat(50) + '\n');
                    }
                });
            }


            static setStructureBasedOnBatiment(typeArchitecture, batimentData = null) {
                // Cacher tous les boutons de structure d'abord
                this.hideStructureButtons();

                // Afficher le message d'information
                $('#structure-info-message').show();

                // Déterminer quelle structure afficher en fonction du type_architecture
                let structureType = 'simple';

                if (typeArchitecture === 'complexe') {
                    structureType = 'meme';
                } else if (typeArchitecture === 'simple') {
                    structureType = 'simple';
                }

                // Afficher le bouton correspondant
                $(`#structure-btn-${structureType}`).show().addClass('active');

                // ✅ AFFICHER LA STRUCTURE ET PASSER LES ÉTAGES
                this.showStructure(structureType);

                if (this.currentStructure) {
                    this.currentStructure.reset();

                    // ✅ PASSER LES ÉTAGES À LA STRUCTURE
                    if (batimentData && batimentData.etages) {
                        console.log('📤 Transmission des étages à la structure:', batimentData.etages);
                        this.currentStructure.setEtages(batimentData.etages);
                    }

                    this.currentStructure.show();
                }
            }

            static hideStructureButtons() {
                // Cacher tous les boutons de structure
                $('.structure-btn').hide().removeClass('active');
                $('#structure-info-message').hide();
            }

            static showDefaultStructure() {
                // En cas d'erreur, afficher la structure simple par défaut
                this.hideStructureButtons();
                $('#structure-btn-simple').show().addClass('active');
                this.showStructure('simple');
            }

            static getNextChambreNumber() {
                return ++this.chambreCounter;
            }
        }

        // =============================================
        // SIMPLE STRUCTURE - Structure Simple
        // =============================================
        class SimpleStructure {
            constructor() {
                this.type = 'simple';
                this.selectedNiveau = null;
                this.containerId = 'structure-simple-section';
                this.batimentData = null;
                this.etages = [];
            }

            setEtages(etages) {
                console.log('📥 SimpleStructure reçoit les étages:', etages);

                // ✅ S'assurer que TOUS les étages sont conservés
                this.etages = etages.map(etage => ({
                    niveau_numero: parseInt(etage.niveau_numero ?? etage.niveau ?? 0),
                    niveau_aile_code: etage.niveau_aile_code ?? etage.code ?? `NIV_${etage.niveau}`,
                    label: etage.label || (etage.niveau_numero == 0 ? 'Rez-de-chaussée' : `Étage ${etage.niveau_numero}`),
                    total_chambres: etage.total_chambres || 0,
                    // Ajouter ces informations pour le débogage
                    id_niveau_aile: etage.id_niveau_aile,
                    code_aile: etage.code_aile,
                    aile_name: etage.aile_name
                }));

                console.log('📊 Étages traités:', this.etages);
                this.updateEtagesCheckboxes();
            }

            updateEtagesCheckboxes() {
                const grid = $('#simple-niveaux-grid');
                grid.empty();

                // Changer la classe pour utiliser radio-grid
                grid.removeClass('checkbox-grid').addClass('radio-grid');

                console.log('🔄 Création des radios pour', this.etages.length, 'étages');

                this.etages.forEach(etage => {
                    // Créer un radio button pour CHAQUE étage (un seul choix possible)
                    const radioHtml = `
                    <label class="radio-item">
                        <input type="radio"
                    name="niveau_selection_simple"  // Même name pour tous = un seul choix possible
                    class="simple-niveau-radio"
                    data-niveau="${etage.niveau_numero}"
                    data-code="${etage.niveau_aile_code}"
                    data-label="${etage.label}"
                    data-id="${etage.id_niveau_aile || ''}"
                    data-aile="${etage.code_aile || ''}">
                        <span class="radio-label">

                        ${etage.label}
                    ${etage.total_chambres > 0 ? `<small class="text-muted">(${etage.total_chambres} ch.)</small>` : ''}
                </span>
                </label>
                    `;
                    grid.append(radioHtml);
                });

                console.log('✅ Radios créées:', $('.simple-niveau-radio').length);

                // Restaurer la sélection précédente si elle existe
                this.restoreSelectedNiveau();

                // Initialiser les événements
                this.initEvents();
            }


            setBatimentData(batimentData) {
                this.batimentData = batimentData;
                this.updateEtagesCheckboxes();
            }



            restoreSelectedNiveau() {
                if (this.selectedNiveau) {
                    $(`.simple-niveau-radio[data-niveau="${this.selectedNiveau.niveau}"]`).prop('checked', true);
                }
            }

            show() {
                $(`#${this.containerId}`).show();
                this.initEvents();

                // ✅ Si on a déjà des étages, les afficher
                if (this.etages && this.etages.length > 0) {
                    this.updateEtagesCheckboxes();
                }
            }

            hide() {
                $(`#${this.containerId}`).hide();
                this.selectedNiveau = null;
            }

            initEvents() {
                // Nettoyer les anciens événements
                $('.simple-niveau-radio').off('change');

                // Ajouter les nouveaux événements
                $('.simple-niveau-radio').on('change', (e) => this.handleNiveauChange(e.target));
            }

            handleNiveauChange(radio) {
                const $radio = $(radio);

                if ($radio.is(':checked')) {
                    const niveau = $radio.data('niveau');
                    const label = $radio.data('label');
                    const code = $radio.data('code');

                    // Sauvegarder le niveau sélectionné (un seul)
                    this.selectedNiveau = {
                        niveau: niveau,
                        label: label,
                        code: code
                    };

                    // Charger les chambres pour ce niveau
                    const batimentCode = $('#code_batiment_chambre').val();
                    if (batimentCode && code) {
                        NiveauChambresManager.loadChambresByNiveau(
                            code,
                            batimentCode,
                            label,
                            'simple'
                        );
                    }

                    this.updateChambresSection();
                }
            }





            updateChambresSection() {
                if (this.selectedNiveau) {
                    this.updateNiveauSection();
                    StructureManager.showCommonSections();
                } else {
                    $('#chambres-list').empty();
                    StructureManager.hideCommonSections();
                    $('#niveau-chambres-dashboard').hide(); // Cacher le dashboard si aucun niveau sélectionné
                }
            }


            updateNiveauSection() {
                const container = $('#chambres-list');

                // Sauvegarder les données des chambres existantes
                const chambresData = this.saveChambresData();

                // Vider le conteneur
                container.empty();

                if (this.selectedNiveau) {
                    const niveauId = `niveau_simple_${this.selectedNiveau.niveau}`;
                    const chambresContainerId = `chambres_simple_${this.selectedNiveau.niveau}`;

                    // Créer la section du niveau
                    const niveauHtml = `
                    <div class="niveau-section" id="${niveauId}">
                        <div class="d-flex justify-content-between align-items-center mb-3" style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                        <h6 class="mb-0">
                        <i class="fas fa-layer-group"></i> ${this.selectedNiveau.label}
                </h6>
                    @adminroute('admin.hebergement.parc-immobilier.create')
<button type="button" class="btn btn-sm btn-outline-primary"
                            onclick="StructureManager.structures.simple.addChambre(${this.selectedNiveau.niveau})">
                        <i class="fas fa-plus"></i> Ajouter une chambre
                    </button>
@endadminroute

                </div>
                    <div class="chambres-container" id="${chambresContainerId}"></div>
                </div>
                    `;

                    container.append(niveauHtml);

                    // Restaurer les chambres pour ce niveau
                    this.restoreChambresForNiveau(this.selectedNiveau.niveau, chambresData);
                }

                StructureManager.updateSubmitButton();
            }

// Nouvelle méthode pour sauvegarder les données
            saveChambresData() {
                const chambresData = {};

                $('.chambre-card[data-structure="simple"]').each(function() {
                    const niveau = $(this).data('niveau');
                    const chambreId = $(this).attr('id');

                    if (!chambresData[niveau]) {
                        chambresData[niveau] = [];
                    }

                    // Sauvegarder toutes les données du formulaire
                    const chambreData = {
                        id: chambreId,
                        numero: $(this).find('input[name$="[numero]"]').val(),
                        type_chambre: $(this).find('select[name$="[type_chambre]"]').val(),
                        chambreNum: chambreId.split('_').pop() // Récupérer le numéro
                    };

                    chambresData[niveau].push(chambreData);
                });

                return chambresData;
            }

// Nouvelle méthode pour restaurer les chambres
            restoreChambresForNiveau(niveau, chambresData) {
                const chambresContainerId = `chambres_simple_${niveau}`;
                const container = $(`#${chambresContainerId}`);

                if (!chambresData[niveau] || !container.length) return;

                chambresData[niveau].forEach(chambreData => {
                    // Trouver le prochain numéro disponible pour éviter les conflits
                    const existingChambreNums = $('.chambre-card').map(function() {
                        const id = $(this).attr('id');
                        const match = id.match(/chambre_simple_\d+_(\d+)/);
                        return match ? parseInt(match[1]) : 0;
                    }).get();

                    const maxNum = existingChambreNums.length > 0 ? Math.max(...existingChambreNums) : 0;
                    const chambreNum = maxNum + 1;
                    const chambreId = `chambre_simple_${niveau}_${chambreNum}`;
                    const niveauInfo = this.selectedNiveau;

                    const chambreHtml = `
                    <div class="chambre-card" id="${chambreId}" data-structure="simple" data-niveau="${niveau}">
                        <div class="chambre-card-header">
                        <div class="chambre-card-title">
                        <i class="fas fa-door-open"></i>
                    Chambre #${chambreNum}
                    <span class="chambre-card-badge">${niveauInfo.label}</span>
                </div>
                    @adminroute('admin.hebergement.parc-immobilier.destroy')
<button type="button" class="btn btn-sm btn-outline-danger"
                            onclick="StructureManager.structures.simple.removeChambre('${chambreId}')">
                        <i class="fas fa-trash"></i>
                    </button>
@endadminroute

                </div>

                    <input type="hidden" name="chambres[${chambreNum}][structure_type]" value="simple">
                        <input type="hidden" name="chambres[${chambreNum}][niveau]" value="${niveau}">

                            <div class="form-row-two-inline">
                                <div class="form-group">
                                    <label>Numéro Porte <span class="required">*</span></label>
                                    <input type="text"
                                           name="chambres[${chambreNum}][numero]"
                                           class="form-input"
                                           placeholder="Ex: 101"
                                           value="${chambreData.numero || ''}"
                                           required>
                                </div>
                                <div class="form-group">
                                    <label>Type de Chambre</label>
                                    <select name="chambres[${chambreNum}][type_chambre]" class="form-select">
                                        <option value="">Utiliser le type commun</option>
                                        @foreach($type_chambre ?? [] as $item)
                                        <option value="{{ $item->code }}" ${chambreData.type_chambre === '{{ $item->code }}' ? 'selected' : ''}>
                                        {{ $item->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        `;

                        container.append(chambreHtml);
                        });
                        }


                        generateChambresForm() {
                        const container = $('#chambres-list');
                        container.empty();



                        StructureManager.updateSubmitButton();
                    }

                        generateNiveauSection(niveauInfo) {
                        const niveauId = `niveau_simple_${niveauInfo.niveau}`;
                        return `
            <div class="niveau-section" id="${niveauId}">
                <div class="d-flex justify-content-between align-items-center mb-3" style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                    <h6 class="mb-0">
                        <i class="fas fa-layer-group"></i> ${niveauInfo.label}
                    </h6>
                    @adminroute('admin.hebergement.parc-immobilier.create')
<button type="button" class="btn btn-sm btn-outline-primary"
                            onclick="StructureManager.structures.simple.addChambre(${niveauInfo.niveau})">
                        Ajouter une chambre
                    </button>
@endadminroute

                </div>
                <div class="chambres-container" id="chambres_simple_${niveauInfo.niveau}"></div>
            </div>
        `;
                    }

                        addChambre(niveau_numero) {
                        // ✅ Utiliser le NUMÉRO pour l'affichage et l'organisation
                        const niveauInfo = this.selectedNiveau;

                        // ✅ Le champ caché doit contenir le NUMÉRO pour le traitement backend
                        `<input type="hidden" name="chambres[${chambreNum}][niveau_numero]" value="${niveau_numero}">`

                        // ❌ NE PAS UTILISER le code ici
                        // `<input type="hidden" name="chambres[${chambreNum}][niveau_aile_code]" value="${niveauInfo.code}">`
                    }

                        removeChambre(chambreId) {
                        if (confirm('Êtes-vous sûr de vouloir supprimer cette chambre ?')) {
                        $(`#${chambreId}`).remove();
                        StructureManager.updateSubmitButton();
                    }
                    }

                        reset() {
                        $('.simple-niveau-radio').prop('checked', false);
                        this.selectedNiveau = null;
                        $('#chambres-list').empty();
                        $('#niveau-chambres-dashboard').hide();
                    }
                        }


                        // =============================================
                        // MEME HAUTEUR STRUCTURE - Version corrigée avec affichage des chambres
                        // =============================================
                        // =============================================
                        // MEME HAUTEUR STRUCTURE - Version avec radios pour les niveaux
                        // =============================================
                        class MemeHauteurStructure {
                        constructor() {
                        this.type = 'meme';
                        this.selectedAiles = new Set();
                        this.selectedNiveau = null;  // Remplacer selectedNiveaux (array) par un objet unique
                        this.chambres = [];
                        this.containerId = 'structure-complexe-meme-section';
                        this.etages = [];
                        this.ailes = [];
                        this.chambreCounter = 0;
                    }

                        setEtages(etages) {
                        console.log('📥 MemeHauteurStructure reçoit les étages:', etages);

                        // Transformer les données reçues
                        this.etages = etages.map(e => ({
                        id_niveau_aile: e.id_niveau_aile,
                        niveau_numero: e.niveau_numero,
                        niveau_aile_code: e.niveau_aile_code,
                        code_aile: e.code_aile,
                        aile_name: e.aile_name,
                        aile_numero: e.aile_numero,
                        label: e.label || (e.niveau_numero == 0 ? 'Rez-de-chaussée' : `Étage ${e.niveau_numero}`),
                        total_chambres: e.total_chambres || 0
                    }));

                        // Extraire les ailes uniques
                        this.ailes = [];
                        const ailesMap = new Map();
                        this.etages.forEach(etage => {
                        if (!ailesMap.has(etage.code_aile)) {
                        ailesMap.set(etage.code_aile, {
                        code: etage.code_aile,
                        numero: etage.aile_numero,
                        name: etage.aile_name || `Aile ${etage.aile_numero}`
                    });
                    }
                    });
                        this.ailes = Array.from(ailesMap.values());

                        console.log('📊 Ailes disponibles:', this.ailes);
                        console.log('📊 Étages:', this.etages);

                        this.updateAilesCheckboxes();
                    }

                        show() {
                        $(`#${this.containerId}`).show();
                        this.initEvents();
                    }

                        hide() {
                        $(`#${this.containerId}`).hide();
                        this.reset();
                    }

                        initEvents() {
                        // Nettoyer les anciens événements
                        $('.meme-aile-radio').off('change');
                        $('.meme-niveau-radio').off('change');

                        // Ajouter les nouveaux événements
                        $('.meme-aile-radio').on('change', (e) => this.handleAileChange(e.target));
                    }

                        handleAileChange(radio) {
                        const $radio = $(radio);
                        const aileCode = $radio.val();
                        const aileName = $radio.data('aile-name');
                        const aileNumero = $radio.data('aile-numero');

                        if ($radio.is(':checked')) {
                        // Vérifier s'il y avait une aile précédemment sélectionnée
                        const previousAileCode = Array.from(this.selectedAiles)[0];

                        if (previousAileCode && previousAileCode !== aileCode) {
                        // Supprimer l'ancienne aile
                        const chambresCount = this.chambres.filter(c => c.aile_code === previousAileCode).length;

                        // Supprimer l'ancien conteneur d'aile
                        $(`#meme-ai