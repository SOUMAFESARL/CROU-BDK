
<div id="chambre-form" class="form-container"
     style="{{ $selectedType === 'chambre' ? 'display: block;' : 'display: none;' }}">
    <h4 class="card-title mb-4">{{ $editMode ? 'Modifier la Chambre' : 'Formulaire Nouvelle Chambre' }}</h4>
    <form id="form-chambre"
          action="{{ $editMode ? route('admin.hebergement.parc-immobilier.updateChambre') : route('admin.hebergement.parc-immobilier.quickCreateAllChambre') }}"
          method="POST"
          enctype="multipart/form-data">
        @csrf
        @if($editMode)
            @method('PUT')
            <input type="hidden" name="code" value="{{ $itemToEdit->id_chambre ?? '' }}">
        @endif
        <input type="hidden" name="type" value="chambre">

        <!-- Ligne 1: Campus, Cité, Bâtiment -->
        <div class="form-row-three">
            <div class="form-group" hidden="hidden">
                <label>Campus <span class="required">*</span></label>

                <input type="hidden" name="campus_id" id="campus_id" value="{{$campus->id_campus}}">
            </div>

            <div class="form-group">
                <label>Cité de rattachement <span class="required">*</span></label>
                <select name="code_cite" id="code_citea" required class="form-select"
                        onchange="StructureManager.handleCiteChange(this.value)">
                    <option value="">Sélectionner une cité...</option>
                    @foreach($cites as $item)
                        <option value="{{$item->id_cite}}">{{$item->name}}</option>
                    @endforeach
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

        <!-- Sélection du type de structure -->
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

        <!-- Section Structure Dynamique -->
        <div id="structure-section">
            <!-- Structure Simple -->
            <div id="structure-simple-section" class="structure-container">
                <div class="form-group">
                    <label>Sélectionner l'étage <span class="required">*</span></label>
                    <div class="radio-grid" id="simple-niveaux-grid"></div>
                </div>
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

            <!-- Structure Complexe -->
            <div id="structure-complexe-meme-section" class="structure-container" style="display: none;">
                <div class="form-group">
                    <label>Sélectionner les ailes <span class="required">*</span></label>
                    <div class="checkbox-grid" id="meme-aile-grid"></div>
                </div>
                <div id="meme-niveaux-containers"></div>
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
        </div>

     <!-------   <div class="form-actions" id="form-actions" style="display: none;">
            <button type="submit" class="btn btn-info">
                <i class="fas fa-save"></i> <span id="submit-btn-text">Enregistrer les Chambres</span>
            </button>
            <button type="button" class="btn btn-secondary" onclick="StructureManager.resetForm()">
                <i class="fas fa-redo"></i> Réinitialiser
            </button>
        </div>    --------------------->
    </form>
</div>

<!-- MODAL POUR CRÉATION RAPIDE DE CHAMBRE (UNE SEULE) -->
<div id="quick-chambre-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 999999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 16px; width: 90%; max-width: 500px; max-height: 90vh; overflow: hidden;">
        <div style="padding: 20px 24px; background: #f8f9fa; border-bottom: 1px solid #dee2e6;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h5 style="margin: 0;"><i class="fas fa-plus-circle"></i> Création Rapide de Chambre</h5>
                <button type="button" id="close-quick-modal" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
            </div>
        </div>
        <div style="padding: 20px 24px;">
            <input type="hidden" id="quick-chambre-code">
            <div class="form-group">
                <label>Numéro de Porte <span class="required">*</span></label>
                <input type="text" id="quick-chambre-numero" class="form-input" placeholder="Ex: A-12, ...">
            </div>
            <div class="form-group">
                <label>Type de Chambre <span class="required">*</span></label>
                <select id="quick-chambre-type" class="form-select">
                    <option value="">Sélectionner un type...</option>
                    @foreach($type_chambre ?? [] as $item)
                        <option value="{{ $item->id_type_chambre }}">{{ $item->name }} ({{ number_format($item->montant, 0, ',', ' ') }} FCFA)</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Niveau de confort <span class="required">*</span></label>
                <select id="quick-chambre-confort" class="form-select">
                    <option value="standard">standard</option>
                    <option value="eleve">Élevé</option>
                </select>
            </div>
            <div class="form-group">
                <label>Type d'occupation <span class="required">*</span></label>
                    <select name="quick-type_occupation" id="quick-type-occupation" required class="form-select">
                    @foreach($type_cites as $item)
                        <option value="{{ $item->id_type_cite }}"
                                data-type-name="{{ strtolower($item->name) }}"
                                {{ ($editMode && $itemToEdit && $itemToEdit->type_cite_id == $item->id_type_cite) ? 'selected' : '' }}>
                            {{ $item->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Personne à mobilité réduite (PMR) <span class="required">*</span></label>
                <select id="quick-chambre-pmr" class="form-select">
                    <option value="0">Non</option>
                    <option value="1">Oui</option>
                </select>
            </div>
            <div class="form-group">
                <label>Nombre de lit disponible <span class="required">*</span></label>
                <input type="number" id="quick-chambre-lits" class="form-input" min="1" max="3" value="1">
                <small class="text-muted">Min: 1, Max: 3</small>
            </div>
        </div>
        <div style="padding: 16px 24px; background: #f8f9fa; border-top: 1px solid #dee2e6; display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" id="cancel-quick-modal" class="btn btn-secondary">Annuler</button>
            <button type="button" id="save-quick-chambre" class="btn btn-primary">Créer la chambre</button>
        </div>
    </div>
</div>

<!-- MODAL DE MODIFICATION D'UNE CHAMBRE -->
<div id="chambre-info-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 16px; width: 90%; max-width: 620px; max-height: 90vh; overflow: auto;">
        <div style="padding: 20px 24px; background: #f8f9fa; border-bottom: 1px solid #dee2e6; display: flex; justify-content: space-between; align-items: center;">
            <h5 style="margin: 0;"><i class="fas fa-bed"></i> Modifier la chambre</h5>
            <button type="button" id="close-chambre-info-modal" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <div id="chambre-info-content" style="padding: 24px;"></div>
        <div style="padding: 16px 24px; background: #f8f9fa; border-top: 1px solid #dee2e6; text-align: right;">
            <button type="button" id="cancel-chambre-info-modal" class="btn btn-secondary">Fermer</button>
            <button type="button" id="save-chambre-info-modal" class="btn btn-primary">Enregistrer</button>
        </div>
    </div>
</div>

<!-- MODAL POUR CRÉATION MASSIVE DE CHAMBRES -->
<div id="mass-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 999998; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 16px; width: 90%; max-width: 800px; max-height: 90vh; overflow: hidden;">
        <div style="padding: 20px 24px; background: #f8f9fa; border-bottom: 1px solid #dee2e6;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h5 style="margin: 0;"><i class="fas fa-magic"></i> Création automatique des chambres</h5>
                <button type="button" id="close-mass-modal" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
            </div>
            <p id="mass-modal-info" style="margin: 8px 0 0 0; font-size: var(--app-font-min, 15px); color: #666;">Configuration pour le niveau sélectionné</p>
        </div>
        <div style="padding: 20px 24px; overflow-y: auto; max-height: 60vh;">
            <div id="mass-configs-container"></div>
            <button type="button" id="add-mass-config" style="background: #0066cc; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; width: 100%; margin-top: 10px;">
                <i class="fas fa-plus"></i> Ajouter une configuration
            </button>
        </div>
        <div style="padding: 16px 24px; background: #f8f9fa; border-top: 1px solid #dee2e6; display: flex; justify-content: space-between; align-items: center;">
            <div id="mass-total-preview" style="font-weight: 600; color: #28a745;">Total: 0 chambre(s)</div>
            <div>
                <button type="button" id="cancel-mass-modal" style="margin-right: 10px; padding: 8px 16px; border-radius: 6px; border: 1px solid #dee2e6; background: white; cursor: pointer;">Annuler</button>
                <button type="button" id="execute-mass-modal" style="padding: 8px 16px; border-radius: 6px; border: none; background: #28a745; color: white; cursor: pointer;">
                    <i class="fas fa-check"></i> Créer
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }
    .form-group label .required { color: #dc3545; }
    .structure-btn i { font-size: 24px; }

    .radio-grid, .checkbox-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 12px;
        margin: 15px 0;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 12px;
        border: 1px solid #e9ecef;
    }

    .radio-item, .checkbox-item {
        display: flex;
        align-items: center;
        padding: 10px 15px;
        background: white;
        border: 2px solid #dee2e6;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .radio-item:hover, .checkbox-item:hover {
        border-color: #0066cc;
        background: #f0f7ff;
    }

    .radio-item input, .checkbox-item input { margin-right: 10px; }

    .niveau-chambres-dashboard {
        margin-top: 30px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 12px;
        border: 1px solid #dee2e6;
    }
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

    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .dashboard-header h4 {
        margin: 0;
        color: #333;
        font-weight: 600;
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
        gap: 8px;
    }

    .chambres-box .box-body {
        padding: 15px;
        max-height: 400px;
        overflow-y: auto;
    }

    .chambres-crees .box-header {
        background: #d4edda;
        border-bottom-color: #28a745;
    }

    .chambres-crees .box-header h5 { color: #155724; }
    .chambres-crees .box-header h5 i { color: #28a745; }

    .chambres-non-crees .box-header {
        background: #fff3cd;
        border-bottom-color: #ffc107;
    }

    .chambres-non-crees .box-header h5 { color: #856404; }
    .chambres-non-crees .box-header h5 i { color: #ffc107; }

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
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .chambre-info {
        display: flex;
        gap: 12px;
        align-items: flex-start;
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

    .chambre-details { flex: 1; }

    .chambre-numero {
        font-size: 15px;
        font-weight: 600;
        color: #333;
        margin-bottom: 5px;
    }

    .chambre-type {
        font-size: var(--app-font-min, 15px);
        color: #666;
        display: inline-block;
        padding: 2px 8px;
        background: #e7f3ff;
        border-radius: 4px;
    }

    .chambre-actions-quick {
        margin-top: 8px;
    }

    .chambre-non-creee {
        background: #fffaf0;
    }

    .chambre-non-creee .chambre-icon {
        background: #fff3cd;
        color: #856404;
    }

    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: var(--app-font-min, 15px);
        font-weight: 500;
    }

    .bg-success { background: #28a745; color: white; }
    .bg-warning { background: #ffc107; color: #212529; }
    .bg-secondary { background: #6c757d; color: white; }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 48px;
        margin-bottom: 15px;
        color: #adb5bd;
    }

    .btn-mass-create {
        background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-size: var(--app-font-min, 15px);
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }

    .btn-mass-create:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }

    .btn-sm {
        padding: 4px 12px;
        font-size: var(--app-font-min, 15px);
        border-radius: 4px;
        cursor: pointer;
    }

    .btn-primary {
        background: #0066cc;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
    }

    .form-row-three {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
    }

    .form-group { margin-bottom: 15px; }

    .form-input, .form-select {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #dee2e6;
        border-radius: 6px;
    }

    .text-muted {
        font-size: var(--app-font-min, 15px);
        color: #6c757d;
        display: block;
        margin-top: 5px;
    }

    @media (max-width: 768px) {
        .form-row-three, .dashboard-grid { grid-template-columns: 1fr; }
        .dashboard-header { flex-direction: column; align-items: flex-start; }
        .btn-mass-create { width: 100%; justify-content: center; }
    }
</style>

@push('scripts')
    <script>
        @php
            $typeEquipMap = [];
            foreach($type_chambre ?? [] as $t) {
                $decoded = null;
                try {
                    $decoded = json_decode($t->equipement, true);
                } catch (\Throwable $e) {
                    $decoded = null;
                }
                $typeEquipMap[$t->id_type_chambre] = $decoded && is_array($decoded) ? array_values($decoded) : [];
            }
        @endphp
        const TYPE_CHAMBRE_EQUIPEMENTS = @json($typeEquipMap);
        // =============================================
        // STRUCTURE MANAGER
        // =============================================
        class StructureManager {
            static currentStructure = null;
            static structures = {};

            static init() {
                this.structures = {
                    'simple': new SimpleStructure(),
                    'meme': new MemeHauteurStructure(),
                };
                this.initEvents();
                this.showStructure('simple');
            }

            static initEvents() {
                $('.structure-btn').off('click').on('click', function() {
                    const type = $(this).data('structure-type');
                    StructureManager.showStructure(type);
                });
            }

            static showStructure(type) {
                $('.structure-container').hide();
                $('.structure-btn').removeClass('active');
                this.currentStructure = this.structures[type];
                this.currentStructure.show();
                $(`.structure-btn[data-structure-type="${type}"]`).addClass('active');
                $('#form-actions').hide();
            }

            static showCommonSections() { $('#form-actions').show(); }
            static hideCommonSections() { $('#form-actions').hide(); }

            static handleCampusChange(campusId) {
                if (!campusId) return;
                getRequest('{{ url("/") }}/admin/list/cite-campus?parent_id=' + campusId, 'code_citea', 'select', campusId);
            }

            static handleCiteChange(citeId) {
                if (!citeId) return;
                getRequest('{{ url("/") }}/admin/list/batiment-cite?parent_id=' + citeId, 'code_batiment_chambre', 'select', citeId);
            }



            static handleBatimentChange(batimentId) {
                if (!batimentId) {
                    if (this.currentStructure) this.currentStructure.hide();
                    this.hideCommonSections();
                    $('#niveau-chambres-dashboard').hide();
                    $('#niveau-chambres-dashboard-complexe').hide();
                    return;
                }
                // Reset les dashboards avant de recharger
                $('#niveau-chambres-dashboard').hide();
                $('#niveau-chambres-dashboard-complexe').hide();
                $('#chambres-crees-container').empty();
                $('#chambres-non-crees-container').empty();
                this.getBatimentInfo(batimentId);
            }



            static getBatimentInfo(batimentId) {
                // Cacher d'abord les deux boutons
                $('#structure-btn-simple').hide().removeClass('active');
                $('#structure-btn-meme').hide().removeClass('active');

                $.ajax({
                    url: '{{ route("admin.hebergement.parc-immobilier.getChambresByBatiment") }}',
                    method: 'GET',
                    data: { batiment_code: batimentId },
                    success: function(response) {
                        if (response.success) {
                            let structureType = response.batiment.type_architecture === 'complexe' ? 'meme' : 'simple';
                            $(`#structure-btn-${structureType}`).show().addClass('active');
                            StructureManager.showStructure(structureType);
                            if (StructureManager.currentStructure && response.etages) {
                                StructureManager.currentStructure.setEtages(response.etages);
                            }
                        }
                    },
                    error: function(xhr) {
                        console.error('Erreur getBatimentInfo:', xhr);
                    }
                });
            }

            static resetForm() {
                if (confirm('Réinitialiser le formulaire ?')) {
                    if (this.currentStructure) this.currentStructure.reset();
                    $('#chambres-list').empty();
                    this.hideCommonSections();
                }
            }
        }

        // =============================================
        // SIMPLE STRUCTURE
        // =============================================
        class SimpleStructure {
            constructor() {
                this.type = 'simple';
                this.selectedNiveau = null;
                this.etages = [];
            }

            setEtages(etages) {
                this.etages = etages.map(e => ({
                    niveau_numero: parseInt(e.niveau_numero ?? 0),
                    niveau_aile_code: e.niveau_aile_code,
                    label: e.label || (e.niveau_numero == 0 ? 'Rez-de-chaussée' : `Étage ${e.niveau_numero}`),
                    total_chambres: e.total_chambres || 0
                }));
                this.updateEtagesCheckboxes();
            }

            updateEtagesCheckboxes() {
                const grid = $('#simple-niveaux-grid');
                grid.empty();
                this.etages.forEach(etage => {
                    grid.append(`
                <label class="radio-item">
                    <input type="radio" name="niveau_selection_simple" class="simple-niveau-radio"
                           data-niveau="${etage.niveau_numero}" data-code="${etage.niveau_aile_code}" data-label="${etage.label}">
                    <span>${etage.label} ${etage.total_chambres > 0 ? `(${etage.total_chambres} ch.)` : ''}</span>
                </label>
            `);
                });
                this.initEvents();
            }

            show() { $('#structure-simple-section').show(); this.initEvents(); }
            hide() { $('#structure-simple-section').hide(); this.selectedNiveau = null; }

            initEvents() {
                $('.simple-niveau-radio').off('change').on('change', (e) => {
                    const radio = e.target;
                    if (radio.checked) {
                        this.selectedNiveau = {
                            niveau: $(radio).data('niveau'),
                            label: $(radio).data('label'),
                            code: $(radio).data('code')
                        };
                        const batimentCode = $('#code_batiment_chambre').val();
                        if (batimentCode && this.selectedNiveau.code) {
                            NiveauChambresManager.loadChambresByNiveau(this.selectedNiveau.code, batimentCode, this.selectedNiveau.label, 'simple');
                        }
                        StructureManager.showCommonSections();
                    }
                });

                $('#simple-niveaux-grid').off('change').on('change', '.simple-niveau-radio', (e) => {
                    const radio = e.target;
                    if (radio.checked) {
                        this.selectedNiveau = {
                            niveau: $(radio).data('niveau'),
                            label: $(radio).data('label'),
                            code: $(radio).data('code')
                        };
                        const batimentCode = $('#code_batiment_chambre').val();
                        if (batimentCode && this.selectedNiveau.code) {
                            NiveauChambresManager.loadChambresByNiveau(
                                this.selectedNiveau.code,
                                batimentCode,
                                this.selectedNiveau.label,
                                'simple'
                            );
                        }
                        StructureManager.showCommonSections();
                    }
                });

                $('#meme-aile-grid').off('change').on('change', '.meme-aile-radio', (e) => {
                    const radio = e.target;
                    if (radio.checked) {
                        const aileCode = $(radio).val();
                        const aileName = $(radio).data('aile-name');
                        this.showNiveauxForAile(aileCode, aileName);
                    }
                });
            }

            reset() {
                $('.simple-niveau-radio').prop('checked', false);
                this.selectedNiveau = null;
                $('#niveau-chambres-dashboard').hide();
            }
        }

        // =============================================
        // MEME HAUTEUR STRUCTURE
        // =============================================
        class MemeHauteurStructure {
            constructor() {
                this.type = 'meme';
                this.selectedNiveau = null;
                this.etages = [];
                this.ailes = [];
            }

            setEtages(etages) {
                this.etages = etages;
                const ailesMap = new Map();
                this.etages.forEach(e => {
                    if (!ailesMap.has(e.code_aile)) {
                        ailesMap.set(e.code_aile, { code: e.code_aile, name: e.aile_name || `Aile ${e.aile_numero}` });
                    }
                });
                this.ailes = Array.from(ailesMap.values());
                this.updateAilesCheckboxes();
            }

            updateAilesCheckboxes() {
                const grid = $('#meme-aile-grid');
                grid.empty();
                this.ailes.forEach(aile => {
                    grid.append(`
                <label class="radio-item">
                    <input type="radio" name="aile_selection" class="meme-aile-radio" value="${aile.code}" data-aile-name="${aile.name}">
                    <span>${aile.name}</span>
                </label>
            `);
                });
                this.initEvents();
            }

            show() { $('#structure-complexe-meme-section').show(); this.initEvents(); }
            hide() { $('#structure-complexe-meme-section').hide(); this.reset(); }

            initEvents() {
                $('.meme-aile-radio').off('change').on('change', (e) => {
                    const radio = e.target;
                    if (radio.checked) {
                        const aileCode = $(radio).val();
                        const aileName = $(radio).data('aile-name');
                        this.showNiveauxForAile(aileCode, aileName);
                    }
                });
            }

            showNiveauxForAile(aileCode, aileName) {
                const etagesAile = this.etages.filter(e => e.code_aile === aileCode);
                let html = `<div class="form-group"><label>Sélectionner l'étage</label><div class="radio-grid" id="niveaux-${aileCode}">`;
                etagesAile.forEach(etage => {
                    html += `
                <label class="radio-item">
                    <input type="radio" name="niveau_${aileCode}" class="meme-niveau-radio"
                           data-aile-code="${aileCode}" data-aile-name="${aileName}"
                           data-niveau-numero="${etage.niveau_numero}"
                           data-niveau-code="${etage.niveau_aile_code}"
                           data-niveau-label="${etage.label}">
                    <span>${etage.label} ${etage.total_chambres > 0 ? `(${etage.total_chambres} ch.)` : ''}</span>
                </label>
            `;
                });
                html += `</div></div>`;
                $('#meme-niveaux-containers').html(html);

                $('.meme-niveau-radio').off('change').on('change', (e) => {
                    const radio = e.target;
                    if (radio.checked) {
                        this.selectedNiveau = {
                            aile_code: $(radio).data('aile-code'),
                            aile_name: $(radio).data('aile-name'),
                            niveau_numero: $(radio).data('niveau-numero'),
                            niveau_code: $(radio).data('niveau-code'),
                            niveau_label: $(radio).data('niveau-label')
                        };
                        const batimentCode = $('#code_batiment_chambre').val();
                        if (batimentCode && this.selectedNiveau.niveau_code) {
                            NiveauChambresManager.loadChambresByNiveau(this.selectedNiveau.niveau_code, batimentCode, this.selectedNiveau.niveau_label, 'meme');
                        }
                        StructureManager.showCommonSections();
                    }
                });
            }

            reset() {
                $('.meme-aile-radio').prop('checked', false);
                $('#meme-niveaux-containers').empty();
                this.selectedNiveau = null;
                $('#niveau-chambres-dashboard-complexe').hide();
            }
        }

        // =============================================
        // GESTIONNAIRE DES CHAMBRES PAR NIVEAU
        // =============================================
        class NiveauChambresManager {
            static currentNiveauCode = null;
            static currentBatimentCode = null;
            static currentStructure = null;
            static currentNiveauLabel = null;
            static currentChambreCode = null;
            static editingChambre = null;

            static loadChambresByNiveau(niveauCode, batimentCode, niveauLabel, structureType = null) {

                console.log('🔵 loadChambresByNiveau appelé avec:', { niveauCode, batimentCode, niveauLabel, structureType });
                if (structureType) this.currentStructure = structureType;
                this.currentNiveauCode = niveauCode;
                this.currentBatimentCode = batimentCode;
                this.currentNiveauLabel = niveauLabel;

                $.ajax({
                    url: '{{ route("admin.hebergement.parc-immobilier.getChambresByNiveau") }}',
                    method: 'GET',
                    data: { niveau_code: niveauCode, batiment_code: batimentCode },
                    success: (response) => {
                        console.log('✅ Réponse AJAX reçue:', response);
                        if (response.success) this.displayChambresBoxes(response, niveauLabel);
                    },
                    error: function(xhr) {
                        console.error('Erreur chargement chambres:', xhr);
                    }
                });
            }

            static displayChambresBoxes(data, niveauLabel) {
                const isMeme = this.currentStructure === 'meme';
                const dashboardId = isMeme ? '#niveau-chambres-dashboard-complexe' : '#niveau-chambres-dashboard';
                const creesId = isMeme ? '#chambres-crees-container-complexe' : '#chambres-crees-container';
                const nonCreesId = isMeme ? '#chambres-non-crees-container-complexe' : '#chambres-non-crees-container';
                const infoId = isMeme ? '.niveau-selection-info-complexe' : '.niveau-selection-info';

                $(infoId).text(`Niveau sélectionné: ${niveauLabel}`);

                // Affichage des chambres créées
                if (data.chambres_crees && data.chambres_crees.count > 0) {
                    let html = `<div class="chambres-box chambres-crees">
                            <div class="box-header">
                                <h5><i class="fas fa-check-circle"></i> Chambres Créées</h5>
                                <span class="badge bg-success">${data.chambres_crees.count} ch.</span>
                            </div>
                            <div class="box-body">`;
                    data.chambres_crees.items.forEach(chambre => {
                        html += `<div class="chambre-item">
                            <div class="chambre-info">
                                <div class="chambre-icon"><i class="fas fa-door-open"></i></div>
                                <div class="chambre-details">
                                    <div class="chambre-numero">${chambre.numero || 'N/A'}</div>
                                    <div class="chambre-type">${chambre.type || 'Standard'}</div>
                                </div>
                                <button type="button" class="btn-sm btn-primary" onclick="NiveauChambresManager.openInfoModal('${encodeURIComponent(JSON.stringify(chambre))}')">
                                    <i class="fas fa-edit"></i> Modifier
                                </button>
                            </div>
                        </div>`;
                    });
                    html += `</div></div>`;
                    $(creesId).html(html);
                } else {
                    $(creesId).html(`<div class="chambres-box chambres-crees">
                                <div class="box-header">
                                    <h5><i class="fas fa-check-circle"></i> Chambres Créées</h5>
                                    <span class="badge bg-secondary">0 ch.</span>
                                </div>
                                <div class="box-body">
                                    <div class="empty-state">
                                        <i class="fas fa-door-closed"></i>
                                        <p>Aucune chambre créée dans ce niveau</p>
                                    </div>
                                </div>
                            </div>`);
                }

                // Affichage des chambres non créées
                const hasNonCrees = data.chambres_non_crees && data.chambres_non_crees.count > 0;
                if (hasNonCrees) {
                    let html = `<div class="chambres-box chambres-non-crees">
                            <div class="box-header">
                                <h5><i class="fas fa-plus-circle"></i> Chambres Non Créées</h5>
                                <span class="badge bg-warning">${data.chambres_non_crees.count} à créer</span>
                            </div>
                            <div class="box-body">`;
                    data.chambres_non_crees.items.forEach((chambre, index) => {
                        html += `<div class="chambre-item chambre-non-creee" data-code="${chambre.code}">
                            <div class="chambre-info">
                                <div class="chambre-icon"><i class="fas fa-door-closed"></i></div>
                                <div class="chambre-details">
                                    <div class="chambre-numero">${chambre.numero || `Chambre ${index + 1}`}</div>
                                    <div class="chambre-actions-quick">
                                        <button type="button" class="btn-sm btn-primary" onclick="NiveauChambresManager.openQuickCreateModal('${chambre.code}')">
                                            <i class="fas fa-plus"></i> Créer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                    });
                    html += `</div></div>`;
                    $(nonCreesId).html(html);
                } else {
                    $(nonCreesId).html(`<div class="chambres-box chambres-non-crees">
                                    <div class="box-header">
                                        <h5><i class="fas fa-plus-circle"></i> Chambres Non Créées</h5>
                                        <span class="badge bg-success">0 à créer</span>
                                    </div>
                                    <div class="box-body">
                                        <div class="empty-state">
                                            <i class="fas fa-check-double"></i>
                                            <p>Toutes les chambres sont créées dans ce niveau</p>
                                        </div>
                                    </div>
                                </div>`);
                }

                $(dashboardId).show();

                // Ajouter le bouton de création massive
                if (hasNonCrees && this.currentNiveauCode && this.currentBatimentCode) {
                    const header = $(dashboardId).find('.dashboard-header');
                    header.find('.btn-mass-create').remove();

                    const $btn = $(`<button type="button" class="btn-mass-create"><i class="fas fa-magic"></i> Créer automatiquement</button>`);
                    $btn.on('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        MassCreateManager.openModal(
                            NiveauChambresManager.currentNiveauCode,
                            NiveauChambresManager.currentBatimentCode,
                            niveauLabel,
                            NiveauChambresManager.currentStructure
                        );
                    });
                    header.append($btn);
                }
            }

            // ========== MÉTHODES POUR LE MODAL RAPIDE ==========
            static openQuickCreateModal(chambreCode) {
                console.log('🔵 Ouverture du modal rapide pour:', chambreCode);
                this.currentChambreCode = chambreCode;
                $('#quick-chambre-code').val(chambreCode);
                $('#quick-chambre-numero').val('').focus();
                $('#quick-chambre-type').val('');
                $('#quick-type-occupation').val('');

                $('#quick-chambre-confort').val('standard');
                $('#quick-chambre-pmr').val('0');
                $('#quick-chambre-lits').val('1').attr('min', 1).attr('max', 3);
                $('#quick-chambre-modal').css('display', 'flex');
                $('body').css('overflow', 'hidden');
            }

            static closeQuickCreateModal() {
                console.log('🔴 Fermeture du modal rapide');
                $('#quick-chambre-modal').hide();
                $('body').css('overflow', '');
            }

            static openInfoModal(chambrePayload) {
                const chambre = JSON.parse(decodeURIComponent(chambrePayload));
                this.editingChambre = chambre;
                $('#chambre-info-content').html(`
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Numéro</label>
                            <input type="text" id="edit-chambre-numero" class="form-control" value="${chambre.numero || ''}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type de chambre</label>
                            <select id="edit-chambre-type" class="form-select" required>
                                <option value="">Sélectionner un type...</option>
                                @foreach($type_chambre ?? [] as $item)
                                    <option value="{{ $item->id_type_chambre }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Niveau de confort</label>
                            <select id="edit-chambre-confort" class="form-select">
                                <option value="standard">Standard</option>
                                <option value="eleve">Élevé</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">PMR</label>
                            <select id="edit-chambre-pmr" class="form-select">
                                <option value="0">Non</option>
                                <option value="1">Oui</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nombre de lits</label>
                            <input type="number" id="edit-chambre-lits" class="form-control" min="1" value="${chambre.nb_lits || 1}">
                        </div>
                        <div class="col-md-6">
                            <label for="edit-chambre-etat" class="form-label">État de la chambre</label>
                            <select id="edit-chambre-etat" class="form-select" required>
                                <option value="disponible">Disponible</option>
                                <option value="occupee">Occupée</option>
                                <option value="reservee">Réservée</option>
                                <option value="maintenance">En maintenance</option>
                                <option value="hors_service">Hors service</option>
                                <option value="fermee">Fermée</option>
                            </select>
                        </div>
                        <div class="col-md-6"><strong>Capacité :</strong><br>${chambre.capacite ?? 'Non renseignée'}</div>
                        <div class="col-md-6"><strong>Prix :</strong><br>${chambre.prix ?? 'Non renseigné'}</div>
                        <div class="col-md-6"><strong>Superficie :</strong><br>${chambre.superficie ?? 'Non renseignée'}</div>
                    </div>
                `);
                $('#edit-chambre-type').val(chambre.type_code || '');
                $('#edit-chambre-confort').val(chambre.niveau_confort || 'standard');
                $('#edit-chambre-pmr').val(chambre.pmr ? '1' : '0');
                $('#edit-chambre-etat').val(chambre.etat || 'disponible');
                $('#chambre-info-modal').css('display', 'flex');
                $('body').css('overflow', 'hidden');
            }

            static closeInfoModal() {
                $('#chambre-info-modal').hide();
                $('body').css('overflow', '');
            }

            static saveInfoModal() {
                const chambre = this.editingChambre;
                const numero = $('#edit-chambre-numero').val().trim();
                const type = $('#edit-chambre-type').val();

                if (!numero || !type) {
                    alert('Veuillez renseigner le numéro et le type de chambre.');
                    return;
                }

                const $button = $('#save-chambre-info-modal');
                $button.prop('disabled', true).text('Enregistrement...');

                $.ajax({
                    url: '{{ route("admin.hebergement.parc-immobilier.updateChambre") }}',
                    method: 'PUT',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        code: chambre.code,
                        numero_chambre: numero,
                        type_chambre_code: type,
                        niveau_confort: $('#edit-chambre-confort').val(),
                        pmr: $('#edit-chambre-pmr').val(),
                        nb_lits: $('#edit-chambre-lits').val(),
                        etat: $('#edit-chambre-etat').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            NiveauChambresManager.closeInfoModal();
                            NiveauChambresManager.loadChambresByNiveau(
                                NiveauChambresManager.currentNiveauCode,
                                NiveauChambresManager.currentBatimentCode,
                                NiveauChambresManager.currentNiveauLabel,
                                NiveauChambresManager.currentStructure
                            );
                        } else {
                            alert(response.message || 'Erreur lors de la modification.');
                        }
                    },
                    error: function(xhr) {
                        alert(xhr.responseJSON?.message || 'Erreur lors de la modification.');
                    },
                    complete: function() {
                        $button.prop('disabled', false).text('Enregistrer');
                    }
                });
            }

            static saveQuickChambre() {
                console.log('💾 Sauvegarde de la chambre rapide');

                const code = $('#quick-chambre-code').val();
                const numero = $('#quick-chambre-numero').val().trim();
                const typeChambre = $('#quick-chambre-type').val();
                const niveauConfort = $('#quick-chambre-confort').val();
                const pmr = $('#quick-chambre-pmr').val();
                const typeOccupation = $('#quick-type-occupation').val();
                const nbLits = $('#quick-chambre-lits').val();

                if (!numero) {
                    alert('Veuillez saisir un numéro de porte');
                    $('#quick-chambre-numero').focus();
                    return;
                }
                if (!typeChambre) {
                    alert('Veuillez sélectionner un type de chambre');
                    return;
                }

                const $btn = $('#save-quick-chambre');
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');

                $.ajax({
                    url: '{{ route("admin.hebergement.parc-immobilier.quickCreateChambre") }}',
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        code: code,
                        numero_chambre: numero,
                        type_chambre_code: typeChambre,
                        niveau_confort: niveauConfort,
                        typeOccupation: typeOccupation,
                        pmr: pmr,
                        nb_lits: nbLits,
                    },
                    success: function(response) {
                        console.log('✅ Réponse reçue:', response);
                        if (response.success) {
                            alert('Chambre créée avec succès !');
                            NiveauChambresManager.closeQuickCreateModal();
                            if (NiveauChambresManager.currentNiveauCode && NiveauChambresManager.currentBatimentCode) {
                                NiveauChambresManager.loadChambresByNiveau(
                                    NiveauChambresManager.currentNiveauCode,
                                    NiveauChambresManager.currentBatimentCode,
                                    NiveauChambresManager.currentNiveauLabel,
                                    NiveauChambresManager.currentStructure
                                );
                            }
                        } else {
                            $btn.prop('disabled', false).html('Créer la chambre');
                            alert(response.message || 'Erreur lors de la création');
                        }
                    },
                    error: function(xhr) {
                        console.error('❌ Erreur:', xhr);
                        $btn.prop('disabled', false).html('Créer la chambre');
                        let message = 'Une erreur est survenue';
                        try {
                            message = JSON.parse(xhr.responseText).message || message;
                        } catch(e) {}
                        alert(message);
                    }
                });
            }

            static initQuickModalEvents() {
                $('#close-quick-modal, #cancel-quick-modal').off('click').on('click', function() {
                    NiveauChambresManager.closeQuickCreateModal();
                });

                $('#close-chambre-info-modal, #cancel-chambre-info-modal').off('click').on('click', function() {
                    NiveauChambresManager.closeInfoModal();
                });
                $('#save-chambre-info-modal').off('click').on('click', function() {
                    NiveauChambresManager.saveInfoModal();
                });

                $('#save-quick-chambre').off('click').on('click', function() {
                    NiveauChambresManager.saveQuickChambre();
                });

                $(window).off('click').on('click', function(e) {
                    if ($(e.target).is('#quick-chambre-modal')) {
                        NiveauChambresManager.closeQuickCreateModal();
                    }
                    if ($(e.target).is('#chambre-info-modal')) {
                        NiveauChambresManager.closeInfoModal();
                    }
                });

                // Ajustement du nombre de lits selon le type de chambre (préremplissage + limites)
                $('#quick-chambre-type').off('change').on('change', function() {
                    const selectedOption = $(this).find('option:selected');
                    const typeName = selectedOption.text().toLowerCase();
                    let maxLits = 3;
                    let defaultLits = 1;

                    if (typeName.includes('individuel') || typeName === 'individuel' || typeName.includes('simple')) {
                        maxLits = 1;
                        defaultLits = 1;
                    } else if (typeName.includes('double') || typeName === 'double') {
                        maxLits = 2;
                        defaultLits = 2;
                    } else if (typeName.includes('triple') || typeName === 'triple') {
                        maxLits = 3;
                        defaultLits = 3;
                    }

                    const $litsInput = $('#quick-chambre-lits');
                    $litsInput.attr('max', maxLits);
                    $litsInput.attr('min', 1);

                    // PRÉREMPLIR avec la valeur par défaut (2 pour double, 3 pour triple, 1 pour individuel)
                    $litsInput.val(defaultLits);
                });

                // Bloquer la saisie manuelle des valeurs hors limites
                $('#quick-chambre-lits').off('change').on('change', function() {
                    let val = parseInt($(this).val()) || 1;
                    const max = parseInt($(this).attr('max')) || 3;
                    const min = parseInt($(this).attr('min')) || 1;

                    if (val > max) {
                        $(this).val(max);
                        alert(`Le nombre maximum de lits pour ce type de chambre est ${max}.`);
                    } else if (val < min) {
                        $(this).val(min);
                        alert(`Le nombre minimum de lits pour ce type de chambre est ${min}.`);
                    }
                });
            }
        }

        // =============================================
        // MASS CREATE MANAGER
        // =============================================
        class MassCreateManager {
            static currentNiveauCode = null;
            static currentBatimentCode = null;
            static currentNiveauLabel = null;
            static currentStructure = null;

            static openModal(niveauCode, batimentCode, niveauLabel, structure) {
                console.log('📢 Ouverture du modal de création massive');

                this.currentNiveauCode = niveauCode;
                this.currentBatimentCode = batimentCode;
                this.currentNiveauLabel = niveauLabel;
                this.currentStructure = structure;

                $('#mass-modal-info').text(`Configuration pour: ${niveauLabel}`);
                $('#mass-configs-container').empty();
                this.addConfig();
                this.updateTotal();

                $('#mass-modal').css('display', 'flex');
                $('body').css('overflow', 'hidden');
            }

            static closeModal() {
                $('#mass-modal').hide();
                $('body').css('overflow', '');
            }

            static addConfig() {
                const container = document.getElementById('mass-configs-container');
                if (!container) return;

                const index = container.children.length;
                const div = document.createElement('div');
                div.className = 'mass-config-item';
                div.style.cssText = 'background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 12px; padding: 20px; margin-bottom: 20px;';
                div.innerHTML = `
            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                <strong style="color: #0066cc;">Configuration ${index + 1}</strong>
                <button type="button" class="remove-mass-config" style="background: #dc3545; color: white; border: none; width: 30px; height: 30px; border-radius: 6px; cursor: pointer;" ${index === 0 ? 'disabled style="opacity:0.5"' : ''}>✕</button>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label>Préfixe / Début numérotation</label>
                    <input type="text"  class="mass-prefix" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;" placeholder="CH-" >
                </div>
                <div>
                    <label>Type de chambre</label>
                    <select class="mass-type" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                        <option value="">Sélectionner...</option>
                        @foreach($type_chambre ?? [] as $item)
                <option value="{{ $item->id_type_chambre }}">{{ $item->name }} ({{ number_format($item->montant, 0, ',', ' ') }} FCFA)</option>
                        @endforeach
                </select>
            </div>
              <div>
                    <label>Niveau de confort</label>
                    <select class="mass-confort" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                        <option value="stardard">Stardard</option>
                        <option value="élévé">Élévé</option>

                </select>
            </div>
             <div class="form-group">
                <label>Type d'occupation <span class="required">*</span></label>
                <select name="type_occupation" id="type_occupation" required class="form-select mass-type-occupation">
                    @foreach($type_cites as $item)
                <option value="{{ $item->id_type_cite }}"
                                data-type-name="{{ strtolower($item->name) }}"
                                {{ ($editMode && $itemToEdit && $itemToEdit->type_cite_id == $item->id_type_cite) ? 'selected' : '' }}>
                            {{ $item->name }}
                </option>
@endforeach
                </select>
            </div>

            <div class="form-group mass-equipements" style="margin-top:10px;">
                <label>Équipements associés</label>
                <div class="mass-equipements-list text-muted">Sélectionnez un type pour afficher les équipements</div>
            </div>

        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
            <div>
                <label>Nombre de chambres</label>
                <input type="number" class="mass-quantity" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;" min="1" value="1">
            </div>
            <div>
                <label>PMR</label>
                <select class="mass-pmr" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                    <option value="0">Non</option>
                    <option value="1">Oui</option>
                </select>
            </div>
            <div>
                <label>Nombre de lits</label>
                <input type="number" class="mass-lits" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;" min="1" max="3" value="1">
            </div>
        </div>
`;

                container.appendChild(div);

                // Lier le nombre de lits au type de chambre (préremplissage + limites)
                const typeSelect = div.querySelector('.mass-type');
                const confortSelect = div.querySelector('.mass-confort');
                const litsInput = div.querySelector('.mass-lits');

                typeSelect.addEventListener('change', function() {
                    const typeName = this.options[this.selectedIndex]?.text.toLowerCase() || '';
                    let maxLits = 3;
                    let defaultLits = 1;

                    if (typeName.includes('individuel') || typeName === 'individuel' || typeName.includes('simple')) {
                        maxLits = 1;
                        defaultLits = 1;
                    } else if (typeName.includes('double') || typeName === 'double') {
                        maxLits = 2;
                        defaultLits = 2;
                    } else if (typeName.includes('triple') || typeName === 'triple') {
                        maxLits = 3;
                        defaultLits = 3;
                    }

                    litsInput.setAttribute('max', maxLits);
                    litsInput.setAttribute('min', 1);

                    // PRÉREMPLIR avec la valeur par défaut
                    litsInput.value = defaultLits;

                    // Mettre à jour les équipements affichés pour ce type
                    const equipListDiv = div.querySelector('.mass-equipements-list');
                    if (equipListDiv) {
                        const typeId = this.value;
                        const equips = TYPE_CHAMBRE_EQUIPEMENTS[typeId] || [];
                        if (equips.length) {
                            equipListDiv.innerHTML = equips.map(e => `
                                <div class="mass-equip-row" data-equip="${e}" style="display:flex; align-items:center; margin-bottom:5px; padding: 10px;">
                                    <span class="mass-equip-name">${e}</span>
                                    <input type="number" class="mass-equip-qty" min="1" value="1" style="margin-left: 30px;"/>
                                </div>
                            `).join('');
                        } else {
                            equipListDiv.innerHTML = '<div class="text-muted">Aucun équipement associé</div>';
                        }
                    }
                });

                // Initialiser l'affichage des équipements si une valeur est déjà sélectionnée
                (function initEquipDisplay(){
                    const equipListDiv = div.querySelector('.mass-equipements-list');
                    if (!equipListDiv) return;
                    const typeId = typeSelect.value;
                    const equips = TYPE_CHAMBRE_EQUIPEMENTS[typeId] || [];
                    if (equips.length) {
                        equipListDiv.innerHTML = equips.map(e => `
                            <div class="mass-equip-row" data-equip="${e}" style="display:flex; align-items:center; margin-bottom:5px; padding: 10px;">
                                <span class="mass-equip-name">${e}</span>
                                <input type="number" class="mass-equip-qty" min="1" value="1" style="margin-left: 30px;"/>
                            </div>
                        `).join('');
                    } else {
                        equipListDiv.innerHTML = '<div class="text-muted">Aucun équipement associé</div>';
                    }
                })();

                // Bloquer la saisie manuelle des valeurs hors limites
                litsInput.addEventListener('change', function() {
                    let val = parseInt(this.value) || 1;
                    const max = parseInt(this.getAttribute('max')) || 3;
                    const min = parseInt(this.getAttribute('min')) || 1;

                    if (val > max) {
                        this.value = max;
                        alert(`Le nombre maximum de lits pour ce type de chambre est ${max}.`);
                    } else if (val < min) {
                        this.value = min;
                        alert(`Le nombre minimum de lits pour ce type de chambre est ${min}.`);
                    }
                });

                const removeBtn = div.querySelector('.remove-mass-config');
                if (removeBtn && index !== 0) {
                    removeBtn.onclick = () => {
                        if (container.children.length > 1) {
                            div.remove();
                            Array.from(container.children).forEach((child, i) => {
                                const title = child.querySelector('strong');
                                if (title) title.textContent = `Configuration ${i + 1}`;
                                const btn = child.querySelector('.remove-mass-config');
                                if (btn) {
                                    btn.disabled = (i === 0);
                                    btn.style.opacity = i === 0 ? '0.5' : '1';
                                }
                            });
                            this.updateTotal();
                        } else {
                            alert('Vous devez conserver au moins une configuration');
                        }
                    };
                }

                div.querySelectorAll('input, select').forEach(el => {
                    el.addEventListener('change', () => this.updateTotal());
                    el.addEventListener('input', () => this.updateTotal());
                });

                this.updateTotal();
            }

            static updateTotal() {
                let total = 0;
                document.querySelectorAll('#mass-configs-container .mass-quantity').forEach(el => {
                    total += parseInt(el.value) || 0;
                });
                $('#mass-total-preview').text(`Total: ${total} chambre(s)`);
            }

            static getConfigurations() {
                const configs = [];
                document.querySelectorAll('#mass-configs-container .mass-config-item').forEach(div => {
                    const type = div.querySelector('.mass-type')?.value;
                    const quantity = parseInt(div.querySelector('.mass-quantity')?.value) || 0;
                    const typeOccupation = div.querySelector('.mass-type-occupation')?.value;
                    const confort = parseInt(div.querySelector('.mass-confort')?.value) || 'standard';
                    if (type && quantity > 0) {
                        // collect equipment quantities
                        const equipRows = div.querySelectorAll('.mass-equip-row');
                        const equipements = [];
                        equipRows.forEach(r => {
                            const name = r.dataset.equip || (r.querySelector('.mass-equip-name')?.textContent || '').trim();
                            const qty = parseInt(r.querySelector('.mass-equip-qty')?.value) || 0;
                            equipements.push({ name: name, quantity: qty });
                        });

                        configs.push({
                            prefix: div.querySelector('.mass-prefix')?.value || '',
                            type_chambre_code: type,
                            quantity: quantity,
                            confort: confort,
                            typeOccupation : typeOccupation,
                            pmr: div.querySelector('.mass-pmr')?.value || '0',
                            nb_lits: parseInt(div.querySelector('.mass-lits')?.value) || 1,
                            equipements: equipements
                        });
                    }
                });
                return configs;
            }

            static executeMassCreate() {
                const configs = this.getConfigurations();
                if (configs.length === 0) {
                    alert('Veuillez configurer au moins un type de chambre valide');
                    return;
                }

                const total = configs.reduce((sum, c) => sum + c.quantity, 0);
                if (!confirm(`Voulez-vous créer ${total} chambre(s) ?`)) return;

                const $btn = $('#execute-mass-modal');
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Création...');

                $.ajax({
                    url: '{{ route("admin.hebergement.parc-immobilier.massCreateChambres") }}',
                    method: 'PUT',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        niveau_code:    this.currentNiveauCode,
                        batiment_code:  this.currentBatimentCode,
                        configurations: configs
                    },
                    success: (response) => {
                        $btn.prop('disabled', false).html('<i class="fas fa-check"></i> Créer');
                        if (response.success) {
                            alert(response.message);
                            this.closeModal();
                            NiveauChambresManager.loadChambresByNiveau(
                                this.currentNiveauCode,
                                this.currentBatimentCode,
                                this.currentNiveauLabel,
                                this.currentStructure
                            );
                        } else {
                            alert(response.message || 'Erreur lors de la création');
                        }
                    },
                    error: (xhr) => {
                        $btn.prop('disabled', false).html('<i class="fas fa-check"></i> Créer');
                        let msg = 'Une erreur est survenue';
                        try { msg = JSON.parse(xhr.responseText).message || msg; } catch(e) {}
                        alert(msg);
                    }
                });
            }
        }

        // =============================================
        // INITIALISATION GLOBALE
        // =============================================
        $(document).ready(function() {
            console.log('🚀 Initialisation des managers...');

            StructureManager.init();

            // Initialiser les événements du modal rapide
            NiveauChambresManager.initQuickModalEvents();

            // Initialiser les événements du modal massif
            $('#close-mass-modal, #cancel-mass-modal').on('click', function() {
                MassCreateManager.closeModal();
            });
            $('#add-mass-config').on('click', function() {
                MassCreateManager.addConfig();
            });
            $('#execute-mass-modal').on('click', function() {
                MassCreateManager.executeMassCreate();
            });
            $(window).on('click', function(e) {
                if ($(e.target).is('#mass-modal')) {
                    MassCreateManager.closeModal();
                }
            });

            console.log('✅ Tous les managers sont initialisés');
        });
    </script>
@endpush
