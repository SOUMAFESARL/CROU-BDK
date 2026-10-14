

<div id="batiment-form" class="form-container"
     style="{{ $selectedType === 'batiment' ? 'display: block;' : 'display: none;' }}">

    <h4 class="card-title mb-4">{{ $editMode ? 'Modifier le Bâtiment' : 'Formulaire Nouveau Bâtiment' }}</h4>
    <form id="form-batiment"
            action="{{ $editMode ? route('admin.hebergement.parc-immobilier.updateBatiment') : route('admin.hebergement.parc-immobilier.storeBatiment') }}"
          method="POST"
          enctype="multipart/form-data">
        @csrf
        @if($editMode)
            @method('PUT')
            <input type="hidden" name="code" value="{{ $itemToEdit->id_batiment ?? '' }}">
        @endif
        <input type="hidden" name="type" value="batiment">

        <!-- Campus / Cité -->
        <div class="form-row">
            <div class="form-group" hidden="hidden">
                <label>Campus de rattachement <span class="required">*</span></label>
                <input type="hidden" name="campus_id" id="campus_id" value="{{$campus->id_campus}}">
            </div>
            <div class="form-group">
                <label>Cité de rattachement <span class="required">*</span></label>
                <select name="code_cite" id="code_cite" required class="form-select" onchange="updateCiteType()">
                    <option value="">Sélectionner une cité...</option>
                    @foreach($cites as $item)
                        <option value="{{$item->id_cite}}"
                                data-type-cite-id="{{ $item->type_cite_code ?? '' }}"
                                data-type-name="{{ $item->typeCite->name ?? '' }}"
                                {{ ($editMode && $itemToEdit && $itemToEdit->code_cite == $item->id_cite) ? 'selected' : '' }}>
                            {{$item->name}}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Nom / Type occupation -->
        <div class="form-row">
            <div class="form-group">
                <label>Nom / Numéro Bâtiment <span class="required">*</span></label>
                <input type="text" id="batiment-name" name="name"
                       value="{{ $editMode ? ($itemToEdit->name ?? '') : old('name') }}"
                       placeholder="BAT A1" required>
            </div>
            <div class="form-group">
                <label>Type d'occupation <span class="required">*</span></label>
                <select name="type_occupation" id="type_occupation" required class="form-select">
                    @foreach($type_cites as $item)
                        <option value="{{ $item->id_type_cite }}"
                                data-type-name="{{ strtolower($item->name) }}"
                                {{ ($editMode && $itemToEdit && $itemToEdit->type_cite_id == $item->id_type_cite) ? 'selected' : '' }}>
                            {{ $item->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Concierge</label>

            <select name="responsable_id" id="responsable_id">
                <option value=""> Sélectionnez le concierge </option>
                @foreach($users as $user)
                    <option value="{{ $user->id_user }}"
                            {{ ($editMode && $itemToEdit && $itemToEdit->responsable_id == $user->id_user) ? 'selected' : '' }}
                    > {{ $user->name }} </option>
                @endforeach
            </select>
        </div>
        <!-- Adresse -->
        <div class="form-group">
            <label>Adresse Géographique</label>
            <input type="text" name="adresse" id="adresse_batiment"
                   value="{{ $editMode ? ($itemToEdit->adresse ?? '') : old('adresse') }}"
                   placeholder="Ex: Rue 123, Quartier Centre, Ville" class="form-control">
        </div>

        <!-- GPS -->
        <div class="form-row">
            <div class="form-group">
                <label>Latitude</label>
                <input type="text" name="latitude" id="latitude_batiment"
                       value="{{ $editMode ? ($itemToEdit->latitude ?? '6.827600') : old('latitude', '6.827600') }}"
                       placeholder="Ex: 8.034670" class="coordinate-input">
            </div>
            <div class="form-group">
                <label>Longitude</label>
                <input type="text" name="longitude" id="longitude_batiment"
                       value="{{ $editMode ? ($itemToEdit->longitude ?? '-5.289300') : old('longitude', '-5.289300') }}"
                       placeholder="Ex: -2.796940" class="coordinate-input">
            </div>
        </div>

        <!-- Carte / Adresse GPS -->
        <div class="form-row" style="margin-bottom: 15px;">
            <div class="form-group" style="flex: 1;">
                <label>Sélectionner la position sur la carte</label>
                <button type="button" class="btn btn-primary" onclick="openMapModalBatiment()" style="width: 100%;">
                    <i class="fas fa-map-marked-alt"></i> Ouvrir la carte
                </button>
                <small class="form-text text-muted">Cliquez sur la carte pour positionner le marqueur</small>
            </div>
            <div class="form-group" style="flex: 1;">
                <label>Ou rechercher par adresse</label>
                <button type="button" class="btn btn-info" onclick="searchAddressForBatiment()" style="width: 100%;">
                    <i class="fas fa-map-pin"></i> Obtenir les coordonnées GPS
                </button>
                <small class="form-text text-muted">Recherche automatique à partir de l'adresse</small>
            </div>
        </div>

        <!-- Type d'architecture -->
        <div class="form-group">
            <label>Type d'architecture <span class="required">*</span></label>
            <div class="architecture-options">
                <label class="architecture-option {{ $editMode && $itemToEdit && $itemToEdit->type_architecture == 'simple' ? 'selected' : '' }}">
                    <input type="radio" name="type_architecture" value="simple"
                           {{ ($editMode && $itemToEdit && $itemToEdit->type_architecture == 'simple') ? 'checked' : 'checked' }} required>
                    <div class="option-content">
                        <div class="option-title">Structure simple</div>
                        <div class="option-desc">Tour unique, Bâtiment linéaire</div>
                    </div>
                </label>
                <label class="architecture-option {{ $editMode && $itemToEdit && $itemToEdit->type_architecture == 'complexe' ? 'selected' : '' }}">
                    <input type="radio" name="type_architecture" value="complexe"
                           {{ ($editMode && $itemToEdit && $itemToEdit->type_architecture == 'complexe') ? 'checked' : '' }} required>
                    <div class="option-content">
                        <div class="option-title">Structure complexe (Ailes)</div>
                        <div class="option-desc">Forme en U, H, L, Étoile...</div>
                    </div>
                </label>
            </div>
        </div>

        <!-- STRUCTURE SIMPLE -->
        <div id="structure-simple" style="display: block;">
            <div class="structure-simple-layout">
                <div class="structure-simple-left">
                    <div class="form-group">
                        <label>Nombre d'étages ( R+? ) <span class="required">*</span></label>
                        <div class="counter-input">
                            <button type="button" class="counter-btn" onclick="decrementCounter('nombre_etages_simple')">−</button>
                            <input type="number" id="nombre_etages_simple" name="nombre_etages"
                                   value="{{ $editMode ? ($itemToEdit->nombre_etages ?? 1) : 1 }}" min="1" readonly>
                            <button type="button" class="counter-btn" onclick="incrementCounter('nombre_etages_simple')">+</button>
                        </div>
                    </div>
                </div>
                <div class="structure-simple-right">
                    <div class="etages-configuration">
                        <div id="etages-container">
                            <!-- Généré dynamiquement par generateEtages() -->
                        </div>
                        <button type="button" class="btn-show-total" onclick="showTotalChambres()">
                            <i class="fas fa-bed"></i> Total : <span id="total-chambres">0</span> Chambres
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- STRUCTURE COMPLEXE -->
        <div id="structure-complexe" style="display: none;">
            <div class="complexe-layout">
                <div class="complexe-left">
                    <div class="form-group">
                        <label>Nombre d'Ailes</label>
                        <div class="counter-input">
                            <button type="button" class="counter-btn" onclick="decrementCounter('nombre_ailes')">−</button>
                            <input type="number" id="nombre_ailes" name="nombre_ailes"
                                   value="{{ $editMode ? ($itemToEdit->nombre_ailes ?? 4) : 4 }}" min="1" readonly>
                            <button type="button" class="counter-btn" onclick="incrementCounter('nombre_ailes')">+</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Configuration des Niveaux <span class="required">*</span></label>
                        <div class="niveau-config-options">
                            <button type="button" class="niveau-config-btn active" data-config="meme-hauteur" onclick="toggleNiveauConfig(this)">
                                Même hauteur partout
                            </button>
                            <button type="button" class="niveau-config-btn" data-config="hauteur-variable" onclick="toggleNiveauConfig(this)">
                                Hauteur variable
                            </button>
                        </div>
                        <input type="hidden" id="config_niveaux" name="config_niveaux" value="meme-hauteur">
                    </div>
                </div>
                <div class="complexe-right">
                    <div id="meme-hauteur-section" style="display: block;">
                        <div class="form-group">
                            <label>Nombre de niveaux (pour toutes les ailes)</label>
                            <div class="counter-input">
                                <button type="button" class="counter-btn" onclick="decrementCounter('nombre_niveaux_global')">−</button>
                                <input type="number" id="nombre_niveaux_global" name="nombre_niveaux_global"
                                       value="{{ $editMode ? ($itemToEdit->nombre_niveaux_global ?? 2) : 2 }}" min="1" readonly>
                                <button type="button" class="counter-btn" onclick="incrementCounter('nombre_niveaux_global')">+</button>
                            </div>
                        </div>
                    </div>
                    <div class="ailes-list" id="ailes-container">
                        <!-- Généré dynamiquement par generateAiles() -->
                    </div>
                </div>
            </div>
            <button type="button" class="btn-show-total-ailes">
                <i class="fas fa-bed"></i> Total : <span id="total-chambres-ailes">0</span> Chambres
            </button>
        </div>

        <!-- MODAL configuration étages par aile (complexe) -->
        <div id="modal-etages-aile" class="custom-modal" style="display: none;">
            <div class="custom-modal-overlay" onclick="closeModal()"></div>
            <div class="custom-modal-container">
                <div class="custom-modal-content">
                    <div class="custom-modal-header">
                        <div class="custom-modal-header-top">
                            <h5 class="custom-modal-title">Configuration des étages — <span id="modal-aile-name"></span></h5>
                            <button type="button" class="custom-modal-close" onclick="closeModal()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <p class="custom-modal-subtitle">Configurez les étages et le nombre de chambres pour cette aile</p>
                    </div>
                    <div class="custom-modal-body">
                        <div id="modal-etages-container"></div>
                    </div>
                    <div class="custom-modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">Annuler</button>
                        <button type="button" class="btn btn-primary" onclick="saveEtagesAile()">Enregistrer</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- État initial -->
        <div class="form-group">
            <label>Etat Initial</label>
            <select name="etat_initial" id="etat_initial" class="form-select">
                <option value="operationnel"   {{ ($editMode && $itemToEdit && $itemToEdit->etat_initial == 'operationnel')   ? 'selected' : '' }}>Opérationnel</option>
                <option value="renovation"     {{ ($editMode && $itemToEdit && $itemToEdit->etat_initial == 'renovation')     ? 'selected' : '' }}>En renovation</option>
                <option value="en_construction"{{ ($editMode && $itemToEdit && $itemToEdit->etat_initial == 'en_construction') ? 'selected' : '' }}>En construction</option>
                <option value="ferme"          {{ ($editMode && $itemToEdit && $itemToEdit->etat_initial == 'ferme')          ? 'selected' : '' }}>Hors service</option>
            </select>
        </div>

        <!-- Services -->
        <div class="form-group">
            <label>Services Disponibles</label>
            <div class="services-grid">
                <div class="service-checkbox">
                    <input type="checkbox" id="acces_handicape" name="acces_handicape" value="1"
                            {{ ($editMode && $itemToEdit && $itemToEdit->acces_handicape == 1) ? 'checked' : '' }}>
                    <label for="acces_handicape">Accès handicapé</label>
                </div>
                <div class="service-checkbox">
                    <input type="checkbox" id="ascenseur" name="ascenseur" value="1"
                            {{ ($editMode && $itemToEdit && $itemToEdit->ascenseur == 1) ? 'checked' : '' }}>
                    <label for="ascenseur">Ascenseur</label>
                </div>
                <div class="service-checkbox">
                    <input type="checkbox" id="gardiennage" name="gardiennage" value="1"
                            {{ ($editMode && $itemToEdit && $itemToEdit->gardiennage == 1) ? 'checked' : '' }}>
                    <label for="gardiennage">Gardiennage</label>
                </div>
                <div class="service-checkbox">
                    <input type="checkbox" id="restaurant_bat" name="restaurant" value="1"
                            {{ ($editMode && $itemToEdit && $itemToEdit->restaurant == 1) ? 'checked' : '' }}>
                    <label for="restaurant_bat">Restaurant</label>
                </div>
            </div>
        </div>

        <!-- Plan -->
        <div class="form-group">
            <label>Plan du bâtiment</label>
            @if($editMode && $itemToEdit && $itemToEdit->plan)
                <div class="mb-2">
                    @if(in_array(strtolower(pathinfo($itemToEdit->plan, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']))
                        <img src="{{ asset($itemToEdit->plan) }}" alt="Plan actuel" style="max-width: 240px; max-height: 160px; object-fit: contain;">
                    @else
                        <a href="{{ asset($itemToEdit->plan) }}" target="_blank" rel="noopener">Voir le plan actuel</a>
                    @endif
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="remove_plan" id="remove_plan_bat" value="1">
                        <label class="form-check-label" for="remove_plan_bat">Supprimer le plan actuel</label>
                    </div>
                </div>
            @endif
            <div class="upload-area" onclick="document.getElementById('batiment-fileInput').click()">
                <div class="upload-icon">☁️</div>
                <div class="upload-text">
                    {{ $editMode && $itemToEdit && $itemToEdit->plan ? 'Cliquez pour changer le plan' : 'Cliquez pour ajouter un fichier ( PDF, PNG, JPEG ... )' }}
                </div>
            </div>
            <input type="file" id="batiment-fileInput" name="plan" accept=".pdf,.png,.jpg,.jpeg" style="display: none;">
            <div id="batiment-file-name" class="mt-2 text-success" style="display: none;"></div>
            <div id="batiment-file-preview" class="mt-2" style="display: none;"></div>
        </div>

        <!-- Description -->
        <div class="form-group">
            <label>Description / Notes</label>
            <textarea id="batiment-description" name="description" placeholder="Détails supplémentaires" rows="3">{{ $editMode ? ($itemToEdit->description ?? '') : old('description') }}</textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-warning validation-batiment">
                <i class="fas fa-save"></i> {{ $editMode ? 'Modifier le Bâtiment' : 'Enregistrer le Bâtiment' }}
            </button>
            <button type="button" class="btn btn-secondary" onclick="resetForm('form-batiment')">
                <i class="fas fa-redo"></i> Réinitialiser
            </button>
            @if($editMode)
                @adminroute('admin.hebergement.parc-immobilier.create')
                <a href="{{ route('admin.hebergement.parc-immobilier.create', ['type' => 'batiment']) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-plus"></i> Nouveau
                </a>
                @endadminroute

            @endif
        </div>
    </form>
</div>

<!-- Modal carte interactive -->
<div class="modal fade" id="mapModalBatiment" tabindex="-1" data-bs-backdrop="false" aria-labelledby="mapModalBatimentLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mapModalBatimentLabel">
                    <i class="fas fa-map-marked-alt"></i> Sélectionner la position — Bâtiment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Latitude</label>
                        <input type="text" id="modal-latitude-batiment" class="form-control" readonly placeholder="Cliquez sur la carte">
                    </div>
                    <div class="col-md-6">
                        <label>Longitude</label>
                        <input type="text" id="modal-longitude-batiment" class="form-control" readonly placeholder="Cliquez sur la carte">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-8">
                        <div class="input-group">
                            <input type="text" id="modal-search-address-batiment" class="form-control" placeholder="Rechercher une adresse...">
                            <button class="btn btn-primary" type="button" onclick="searchAddressInModalBatiment()">
                                <i class="fas fa-search"></i> Rechercher
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-success w-100" type="button" onclick="getCurrentLocationInModalBatiment()">
                            <i class="fas fa-location-dot"></i> Ma position
                        </button>
                    </div>
                </div>
                <div id="select-map-batiment" style="height: 500px; width: 100%; border-radius: 8px;"></div>
                <small class="text-muted mt-2 d-block">💡 Cliquez sur la carte ou glissez le marqueur pour ajuster</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="confirmPositionBatiment()">
                    <i class="fas fa-check"></i> Confirmer cette position
                </button>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

@push('styles')
    <style>
        .coordinate-input { font-family: monospace; font-size: var(--app-font-min, 15px); transition: background-color 0.3s ease; }
        .coordinate-input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
        .custom-notification-batiment { border-radius: 8px; font-size: var(--app-font-min, 15px); }
        .btn-info    { background-color: #17a2b8; border-color: #17a2b8; color: white; }
        .btn-info:hover { background-color: #138496; }
        .btn-primary { background-color: #667eea; border-color: #667eea; }
        .btn-primary:hover { background-color: #5a67d8; }
        .form-text   { font-size: var(--app-font-min, 15px); margin-top: 0.25rem; }

        @keyframes markerPulse {
            0%   { transform: scale(1); }
            50%  { transform: scale(1.3); }
            100% { transform: scale(1); }
        }
        .marker-animation { animation: markerPulse 0.3s ease; }

        #select-map-batiment { cursor: crosshair; min-height: 500px; }
        #mapModalBatiment { z-index: 1055 !important; }
        #mapModalBatiment .modal-dialog { z-index: 1056 !important; }
        #select-map-batiment .leaflet-control-container { z-index: 1060 !important; }
        #select-map-batiment .leaflet-pane { z-index: 400; }

        /* Style modal aile */
        .custom-modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            z-index: 9999; display: flex; align-items: center; justify-content: center;
        }
        .custom-modal-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
        }
        .custom-modal-container {
            position: relative; z-index: 1; width: 90%; max-width: 600px;
            max-height: 90vh; overflow-y: auto;
        }
        .custom-modal-content {
            background: white; border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        .custom-modal-header {
            padding: 20px 24px; border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(135deg, #7c3aed, #5b21b6);
            border-radius: 12px 12px 0 0; color: white;
        }
        .custom-modal-header-top { display: flex; justify-content: space-between; align-items: center; }
        .custom-modal-title { margin: 0; font-size: 16px; font-weight: 700; }
        .custom-modal-subtitle { margin: 6px 0 0; font-size: var(--app-font-min, 15px); opacity: 0.85; }
        .custom-modal-close {
            background: none; border: none; color: white;
            font-size: 18px; cursor: pointer; padding: 0;
        }
        .custom-modal-body { padding: 20px 24px; }
        .custom-modal-footer {
            padding: 16px 24px; border-top: 1px solid #e2e8f0;
            display: flex; justify-content: flex-end; gap: 12px;
        }

        /* Architecture options */
        .architecture-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 5px;
        }

        .architecture-option {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            background: #f7fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .architecture-option:hover {
            border-color: #a0aec0;
            transform: translateY(-2px);
        }

        .architecture-option input[type="radio"] {
            margin-right: 12px;
            width: 18px;
            height: 18px;
            accent-color: #667eea;
            cursor: pointer;
            flex-shrink: 0;
        }

        .architecture-option .option-content {
            flex: 1;
        }

        .architecture-option .option-title {
            font-weight: 600;
            color: #2d3748;
            font-size: 0.95rem;
        }

        .architecture-option .option-desc {
            font-size: var(--app-font-min, 15px);
            color: #718096;
            margin-top: 2px;
        }

        .architecture-option:has(input:checked) {
            background: #ebf4ff;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Structure simple layout */
        .structure-simple-layout {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
            margin-top: 15px;
        }

        .etages-configuration {
            background: #f8fafc;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }

        .btn-show-total {
            margin-top: 15px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #059669, #047857);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-show-total:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(5, 150, 105, 0.3);
        }

        /* Structure complexe layout */
        .complexe-layout {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
            margin-top: 15px;
        }

        .niveau-config-options {
            display: flex;
            gap: 10px;
            margin-top: 5px;
        }

        .niveau-config-btn {
            flex: 1;
            padding: 10px 15px;
            background: #f7fafc;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            color: #4a5568;
        }

        .niveau-config-btn:hover {
            border-color: #a0aec0;
        }

        .niveau-config-btn.active {
            background: #ebf4ff;
            border-color: #667eea;
            color: #667eea;
        }

        .ailes-list {
            margin-top: 10px;
            max-height: 300px;
            overflow-y: auto;
            padding-right: 5px;
        }

        .btn-show-total-ailes {
            margin-top: 15px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #7c3aed, #5b21b6);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-show-total-ailes:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(124, 58, 237, 0.3);
        }

        /* Form row */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 5px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .structure-simple-layout,
            .complexe-layout {
                grid-template-columns: 1fr;
            }
            .architecture-options {
                grid-template-columns: 1fr;
            }
            .niveau-config-options {
                flex-direction: column;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        // ================================================
        // VARIABLES GLOBALES
        // ================================================
        let mapModalBatiment     = null;
        let selectMapBatiment    = null;
        let selectMarkerBatiment = null;
        let ailesData            = {};
        let currentAileIndex     = null;
        let selectedCiteTypeName = null;

        // ================================================
        // GESTION DU TYPE DE CITÉ - CORRIGÉE
        // ================================================

        function updateCiteType() {
            // 1. Récupérer la cité sélectionnée
            const selectedOption = $('#code_cite option:selected');
            const citeId = $('#code_cite').val();

            if (!citeId) {
                // Aucune cité sélectionnée, réinitialiser
                selectedCiteTypeName = null;
                showAllOccupationTypes();
                return;
            }

            // 2. Récupérer le nom du type de cité depuis le data attribute
            const typeName = selectedOption.data('type-name') || '';

            if (!typeName) {
                showAllOccupationTypes();
                return;
            }

            // 3. Stocker le nom du type de cité en minuscules pour comparaison
            selectedCiteTypeName = typeName.toLowerCase();

            // 4. Récupérer tous les options du select type_occupation
            const typeSelect = document.getElementById('type_occupation');
            const options = typeSelect.options;

            // 5. Déterminer les types d'occupation à afficher en fonction du type de cité
            let allowedTypes = [];
            let allowedTypeNames = [];

            if (selectedCiteTypeName === 'mixte') {
                // Si cité mixte → afficher tous les types
                allowedTypes = ['mixte', 'uniquement homme', 'uniquement femme'];
                allowedTypeNames = ['Mixte', 'uniquement homme', 'Uniquement Femme'];
            } else if (selectedCiteTypeName === 'uniquement homme') {
                // Si cité hommes → afficher uniquement hommes
                allowedTypes = ['uniquement homme'];
                allowedTypeNames = ['Uniquement Homme'];
            } else if (selectedCiteTypeName === 'uniquement femme') {
                // Si cité femmes → afficher uniquement femmes
                allowedTypes = ['uniquement femme'];
                allowedTypeNames = ['Uniquement Femme'];
            } else {
                // Par défaut, afficher tous les types
                allowedTypes = ['mixte', 'hommes', 'femmes'];
                allowedTypeNames = ['Mixte', 'Hommes', 'Femmes'];
            }
            getTypesFromCiteType(selectedCiteTypeName);

            // 6. Filtrer les options du select type_occupation
            let firstVisibleOption = null;
            for (let i = 0; i < options.length; i++) {
                const option = options[i];
                const optionTypeName = option.getAttribute('data-type-name') || '';

                // Vérifier si cette option est autorisée
                const isAllowed = allowedTypes.includes(optionTypeName);

                if (isAllowed) {
                    option.style.display = '';
                    if (!firstVisibleOption) {
                        firstVisibleOption = option;
                    }
                } else {
                    option.style.display = 'none';
                    if (option.selected) {
                        option.selected = false;
                    }
                }
            }

            // 7. Si une seule option est disponible, la sélectionner automatiquement
            const visibleOptions = Array.from(options).filter(opt => opt.style.display !== 'none');
            if (visibleOptions.length === 1 && visibleOptions[0]) {
                visibleOptions[0].selected = true;
            } else if (visibleOptions.length > 1) {
                // Si plusieurs options, sélectionner la première visible si aucune n'est sélectionnée
                const hasSelected = visibleOptions.some(opt => opt.selected);
                if (!hasSelected && firstVisibleOption) {
                    firstVisibleOption.selected = true;
                }
            }

            // 8. Mettre à jour tous les selects d'occupation dans les étages
            updateAllOccupationSelects(allowedTypes);
        }

        function showAllOccupationTypes() {
            // Afficher toutes les options
            const typeSelect = document.getElementById('type_occupation');
            const options = typeSelect.options;
            for (let i = 0; i < options.length; i++) {
                options[i].style.display = '';
            }

            // Mettre à jour les selects des étages avec tous les types
            const allTypes = ['mixte', 'uniquement homme', 'uniquement femme'];
            updateAllOccupationSelects(allTypes);
        }

        function updateAllOccupationSelects(allowedTypes) {
            // Mettre à jour les selects dans la structure simple
            const simpleEtages = document.querySelectorAll('#etages-container select[id^="type_etage_"]');
            simpleEtages.forEach(select => {
                filterSelectOptions(select, allowedTypes);
            });

            // Mettre à jour les selects dans la structure complexe (modal)
            const modalEtages = document.querySelectorAll('#modal-etages-container select[id^="modal_type_"]');
            modalEtages.forEach(select => {
                filterSelectOptions(select, allowedTypes);
            });
        }

        function filterSelectOptions(selectElement, allowedTypes) {
            const options = selectElement.options;
            let firstVisibleOption = null;

            for (let i = 0; i < options.length; i++) {
                const option = options[i];
                const isAllowed = allowedTypes.includes(option.value);

                if (isAllowed) {
                    option.style.display = '';
                    if (!firstVisibleOption) {
                        firstVisibleOption = option;
                    }
                } else {
                    option.style.display = 'none';
                    if (option.selected) {
                        option.selected = false;
                    }
                }
            }

            // Si aucune option n'est sélectionnée, sélectionner la première visible
            const hasSelected = Array.from(options).some(opt => opt.selected && opt.style.display !== 'none');
            if (!hasSelected && firstVisibleOption) {
                firstVisibleOption.selected = true;
            }
        }

        // ================================================
        // STRUCTURE SIMPLE — generateEtages
        // ================================================

        function generateEtages() {
            const nombreEtages = parseInt(document.getElementById('nombre_etages_simple').value) || 1;
            const container    = document.getElementById('etages-container');
            container.innerHTML = '';

            // RDC (niveau 0)
            container.appendChild(createEtageRow(0, 'RDC', '#059669'));

            // Étages 1 à N
            for (let i = 1; i <= nombreEtages; i++) {
                container.appendChild(createEtageRow(i, `Étage ${i}`, '#2563eb'));
            }

            updateTotalChambres();

            // Réappliquer le filtre après génération
            if (selectedCiteTypeName) {
                const allowedTypes = getTypesFromCiteType(selectedCiteTypeName);
                updateAllOccupationSelects(allowedTypes);
            }
        }

        function getTypesFromCiteType(citeTypeName) {
            if (citeTypeName === 'mixte') {
                return ['mixte', 'Uniquement Homme', 'Uniquement Femme'];
            } else if (citeTypeName === 'uniquement homme') {
                return ['uniquement homme'];
            } else if (citeTypeName === 'uniquement femme') {
                return ['uniquement femme'];
            }
            return ['mixte', 'Uniquement Homme', 'Uniquement Femme'];
        }

        function createEtageRow(numero, label, color) {
            const div = document.createElement('div');
            div.style.cssText = `display:flex; align-items:center; gap:12px; margin-bottom:10px;
            padding:12px; background:#f8fafc; border-radius:8px; border-left:4px solid ${color};`;

            // Générer les options pour le type d'occupation
            let optionsHtml = '';
            const types = selectedCiteTypeName ? getTypesFromCiteType(selectedCiteTypeName) : ['mixte', 'Uniquement Homme', 'Uniquement Femme'];

            // Récupérer les libellés depuis le select principal
            const mainSelect = document.getElementById('type_occupation');
            const mainOptions = mainSelect ? mainSelect.options : [];

            // Filtrer les options visibles du select principal
            const visibleMainOptions = Array.from(mainOptions).filter(opt => opt.style.display !== 'none');

            types.forEach(type => {
                let labelText = type.charAt(0).toUpperCase() + type.slice(1);
                // Chercher le libellé dans les options visibles du select principal
                for (let i = 0; i < visibleMainOptions.length; i++) {
                    if (visibleMainOptions[i].getAttribute('data-type-name') === type) {
                        labelText = visibleMainOptions[i].textContent;
                        break;
                    }
                }
                optionsHtml += `<option value="${type}">${labelText}</option>`;
            });

            div.innerHTML = `
            <span style="font-weight:600; min-width:90px; color:${color};">${label}</span>
            <div style="flex:1;">
                <label style="font-size:var(--app-font-min, 15px); color:#64748b; margin-bottom:4px; display:block;">Nombre de chambres</label>
                <input type="number" id="chambres_etage_${numero}" min="0" value="0" placeholder="0"
                       oninput="updateTotalChambres()"
                       style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px; font-size:var(--app-font-min, 15px);">
            </div>
            <div style="flex:1;">
                <label style="font-size:var(--app-font-min, 15px); color:#64748b; margin-bottom:4px; display:block;">Type d'occupation</label>
                <select id="type_etage_${numero}"
                        style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px; font-size:var(--app-font-min, 15px);">
                    ${optionsHtml}
                </select>
            </div>`;
            return div;
        }

        function updateTotalChambres() {
            const nombreEtages = parseInt(document.getElementById('nombre_etages_simple').value) || 1;
            let total = 0;
            for (let i = 0; i <= nombreEtages; i++) {
                const input = document.getElementById(`chambres_etage_${i}`);
                if (input) total += parseInt(input.value) || 0;
            }
            document.getElementById('total-chambres').textContent = total;
        }

        function showTotalChambres() {
            updateTotalChambres();
        }

        // ================================================
        // STRUCTURE COMPLEXE — generateAiles
        // ================================================

        function generateAiles() {
            const nombreAiles   = parseInt(document.getElementById('nombre_ailes').value) || 4;
            const niveauxGlobal = parseInt(document.getElementById('nombre_niveaux_global').value) || 2;
            const container     = document.getElementById('ailes-container');
            container.innerHTML = '';

            for (let i = 1; i <= nombreAiles; i++) {
                if (!ailesData[i]) {
                    ailesData[i] = { aile_numero: i, aile_nom: `Aile ${i}`, etages: [] };
                }

                const configured    = ailesData[i].etages.length > 0;
                const totalChambres = ailesData[i].etages.reduce((s, e) => s + (e.total_chambres || 0), 0);

                const div = document.createElement('div');
                div.style.cssText = `padding:12px; margin-bottom:10px; background:#f8fafc;
                border-radius:8px; border-left:4px solid #7c3aed; display:flex; align-items:center; gap:12px;`;
                div.innerHTML = `
                <div style="flex:1;">
                    <div style="font-weight:600; color:#7c3aed; margin-bottom:4px;">Aile ${i}</div>
                    <input type="text" id="aile_nom_${i}" value="${ailesData[i].aile_nom}"
                           placeholder="Nom de l'aile"
                           oninput="ailesData[${i}].aile_nom = this.value"
                           style="width:100%; padding:6px; border:1px solid #e2e8f0; border-radius:6px; font-size:var(--app-font-min, 15px);">
                </div>
                <div style="text-align:center; min-width:100px;">
                    <div style="font-size:var(--app-font-min, 15px); color:#64748b;">Chambres</div>
                    <div style="font-size:20px; font-weight:700; color:#7c3aed;" id="total_aile_${i}">${totalChambres}</div>
                </div>
                <div>
                    <button type="button" onclick="openModalEtagesAile(${i}, ${niveauxGlobal})"
                            style="padding:8px 14px; background:${configured ? '#059669' : '#7c3aed'};
                                   color:white; border:none; border-radius:6px; font-size:var(--app-font-min, 15px); cursor:pointer;">
                        <i class="fas ${configured ? 'fa-check' : 'fa-cog'}"></i>
                        ${configured ? 'Modifier' : 'Configurer'}
                    </button>
                </div>`;
                container.appendChild(div);
            }

            updateTotalChambresAiles();
        }

        function toggleNiveauConfig(btn) {
            document.querySelectorAll('.niveau-config-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const config = btn.dataset.config;
            document.getElementById('config_niveaux').value = config;
            document.getElementById('meme-hauteur-section').style.display = config === 'meme-hauteur' ? 'block' : 'none';
            generateAiles();
        }

        // ================================================
        // MODAL étages par aile
        // ================================================

        function openModalEtagesAile(aileIndex, niveauxCount) {
            currentAileIndex = aileIndex;
            document.getElementById('modal-aile-name').textContent = ailesData[aileIndex]?.aile_nom || `Aile ${aileIndex}`;

            const container     = document.getElementById('modal-etages-container');
            container.innerHTML = '';
            const existingEtages = ailesData[aileIndex]?.etages || [];

            // Générer les options pour le type d'occupation
            let optionsHtml = '';
            const types = selectedCiteTypeName ? getTypesFromCiteType(selectedCiteTypeName) : ['mixte', 'hommes', 'femmes'];

            // Récupérer les libellés depuis le select principal
            const mainSelect = document.getElementById('type_occupation');
            const mainOptions = mainSelect ? mainSelect.options : [];
            const visibleMainOptions = Array.from(mainOptions).filter(opt => opt.style.display !== 'none');

            types.forEach(type => {
                let labelText = type.charAt(0).toUpperCase() + type.slice(1);
                for (let i = 0; i < visibleMainOptions.length; i++) {
                    if (visibleMainOptions[i].getAttribute('data-type-name') === type) {
                        labelText = visibleMainOptions[i].textContent;
                        break;
                    }
                }
                optionsHtml += `<option value="${type}">${labelText}</option>`;
            });

            for (let i = 0; i <= niveauxCount; i++) {
                const existing = existingEtages.find(e => e.niveau_numero === i);
                const label    = i === 0 ? 'RDC' : `Étage ${i}`;
                const color    = i === 0 ? '#059669' : '#7c3aed';

                const div = document.createElement('div');
                div.style.cssText = `display:flex; align-items:center; gap:12px; margin-bottom:10px;
                padding:12px; background:#f8fafc; border-radius:8px; border-left:4px solid ${color};`;
                div.innerHTML = `
                <span style="font-weight:600; min-width:80px; color:${color};">${label}</span>
                <div style="flex:1;">
                    <label style="font-size:var(--app-font-min, 15px); color:#64748b; margin-bottom:4px; display:block;">Chambres</label>
                    <input type="number" id="modal_chambres_${i}" min="0"
                           value="${existing?.total_chambres || 0}"
                           style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;">
                </div>
                <div style="flex:1;">
                    <label style="font-size:var(--app-font-min, 15px); color:#64748b; margin-bottom:4px; display:block;">Type</label>
                    <select id="modal_type_${i}" style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px;">
                        ${optionsHtml}
                    </select>
                </div>`;
                container.appendChild(div);
            }

            document.getElementById('modal-etages-aile').style.display = 'flex';
        }

        function saveEtagesAile() {
            const niveauxCount = parseInt(document.getElementById('nombre_niveaux_global').value) || 2;
            const etages       = [];

            for (let i = 0; i <= niveauxCount; i++) {
                const chambres = parseInt(document.getElementById(`modal_chambres_${i}`)?.value) || 0;
                const type     = document.getElementById(`modal_type_${i}`)?.value || 'mixte';
                etages.push({ niveau_numero: i, total_chambres: chambres, type_occupation: type });
            }

            ailesData[currentAileIndex].etages = etages;
            closeModal();
            generateAiles();
        }

        function closeModal() {
            document.getElementById('modal-etages-aile').style.display = 'none';
            currentAileIndex = null;
        }

        function updateTotalChambresAiles() {
            let total = 0;
            Object.values(ailesData).forEach(aile => {
                aile.etages.forEach(e => total += e.total_chambres || 0);
            });
            document.getElementById('total-chambres-ailes').textContent = total;
        }

        // ================================================
        // COLLECTE DES DONNÉES
        // ================================================

        function collectBatimentFormData() {
            const formData = {
                code_campus:       $('#campus_id').val(),
                code_cite:         $('#code_cite').val(),
                name:              $('#batiment-name').val(),
                type_occupation:   $('#type_occupation').val(),
                responsable_id : $('#responsable_id').val(),
                type_architecture: $('input[name="type_architecture"]:checked').val(),
                latitude:          $('#latitude_batiment').val(),
                longitude:         $('#longitude_batiment').val(),
                adresse:           $('#adresse_batiment').val(),
                etat_initial:      $('#etat_initial').val(),
                services: {
                    acces_handicape: $('#acces_handicape').is(':checked') ? 1 : 0,
                    ascenseur:       $('#ascenseur').is(':checked') ? 1 : 0,
                    gardiennage:     $('#gardiennage').is(':checked') ? 1 : 0,
                    restaurant:      $('#restaurant_bat').is(':checked') ? 1 : 0
                },
                description:    $('#batiment-description').val(),
                structure_data: null,
                total_chambres: 0,
                total_etages:   0,
                total_ailes:    0
            };

            if (formData.type_architecture === 'simple') {
                const nombreEtages = parseInt($('#nombre_etages_simple').val()) || 1;
                const etages       = [];

                for (let i = 0; i <= nombreEtages; i++) {
                    const chambres = parseInt($(`#chambres_etage_${i}`).val()) || 10;
                    const type     = $(`#type_etage_${i}`).val() || 'mixte';
                    etages.push({
                        etage_numero:   i,
                        chambres:       chambres,
                        total_chambres: chambres,
                        type_occupation: type
                    });
                }

                const totalChambres = etages.reduce((sum, e) => sum + e.chambres, 0);

                formData.structure_data = {
                    nombre_etages: nombreEtages,
                    type:          'simple',
                    etages:        etages
                };
                formData.total_chambres = totalChambres;
                formData.total_etages   = 1 + nombreEtages;

            } else if (formData.type_architecture === 'complexe') {
                const nombreAiles  = parseInt($('#nombre_ailes').val()) || 4;
                const ailesArray   = Object.values(ailesData).map(aile => ({
                    aile_numero:    aile.aile_numero,
                    aile_nom:       aile.aile_nom,
                    nombre_niveaux: aile.etages.length,
                    total_chambres: aile.etages.reduce((s, e) => s + (e.total_chambres || 0), 0),
                    etages:         aile.etages
                }));

                const totalChambres = ailesArray.reduce((s, a) => s + a.total_chambres, 0);

                formData.structure_data = {
                    nombre_ailes:   nombreAiles,
                    config_niveaux: $('#config_niveaux').val(),
                    type:           'complexe',
                    ailes:          ailesArray
                };
                formData.total_chambres = totalChambres;
                formData.total_ailes    = nombreAiles;
            }

            return formData;
        }

        // ================================================
        // COMPTEURS
        // ================================================

        function incrementCounter(fieldId) {
            const input = document.getElementById(fieldId);
            if (input) {
                input.value = (parseInt(input.value) || 0) + 1;
                if (fieldId === 'nombre_etages_simple') generateEtages();
                if (fieldId === 'nombre_ailes' || fieldId === 'nombre_niveaux_global') generateAiles();
            }
        }

        function decrementCounter(fieldId) {
            const input = document.getElementById(fieldId);
            if (input) {
                const val = parseInt(input.value) || 1;
                if (val > 1) {
                    input.value = val - 1;
                    if (fieldId === 'nombre_etages_simple') generateEtages();
                    if (fieldId === 'nombre_ailes' || fieldId === 'nombre_niveaux_global') generateAiles();
                }
            }
        }

        function resetForm(formId) {
            if (confirm('Voulez-vous réinitialiser le formulaire ?')) {
                const form = document.getElementById(formId);
                if (form) form.reset();
                ailesData = {};
                selectedCiteTypeName = null;
                generateEtages();
                showNotificationBatiment('Formulaire réinitialisé avec succès', 'info');
            }
        }

        // ================================================
        // CARTE
        // ================================================

        function openMapModalBatiment() {
            let initialLat = parseFloat(document.getElementById('latitude_batiment').value)  || 6.8276;
            let initialLng = parseFloat(document.getElementById('longitude_batiment').value) || -5.2893;

            if (!mapModalBatiment) {
                mapModalBatiment = new bootstrap.Modal(document.getElementById('mapModalBatiment'));
            }
            mapModalBatiment.show();
            setTimeout(() => initMapInModalBatiment(initialLat, initialLng), 500);
        }

        function initMapInModalBatiment(lat, lng) {
            if (selectMapBatiment) { selectMapBatiment.remove(); selectMapBatiment = null; }

            selectMapBatiment = L.map('select-map-batiment', { preferCanvas: true }).setView([lat, lng], 18);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors', maxZoom: 22, minZoom: 2
            }).addTo(selectMapBatiment);

            const customIcon = L.divIcon({
                html: '<div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 16px; border: 3px solid white; box-shadow: 0 4px 8px rgba(0,0,0,0.2);"><i class="fas fa-home"></i></div>',
                iconSize: [32, 32], iconAnchor: [16, 32], popupAnchor: [0, -32]
            });

            selectMarkerBatiment = L.marker([lat, lng], { icon: customIcon, draggable: true }).addTo(selectMapBatiment);

            selectMarkerBatiment.on('dragend', () => {
                const pos = selectMarkerBatiment.getLatLng();
                document.getElementById('modal-latitude-batiment').value  = pos.lat.toFixed(6);
                document.getElementById('modal-longitude-batiment').value = pos.lng.toFixed(6);
            });

            selectMapBatiment.on('click', (e) => {
                selectMarkerBatiment.setLatLng(e.latlng);
                document.getElementById('modal-latitude-batiment').value  = e.latlng.lat.toFixed(6);
                document.getElementById('modal-longitude-batiment').value = e.latlng.lng.toFixed(6);
            });

            document.getElementById('modal-latitude-batiment').value  = lat.toFixed(6);
            document.getElementById('modal-longitude-batiment').value = lng.toFixed(6);

            setTimeout(() => { if (selectMapBatiment) selectMapBatiment.invalidateSize(); }, 100);
        }

        function searchAddressInModalBatiment() {
            const address = document.getElementById('modal-search-address-batiment').value;
            if (!address?.trim()) { showNotificationBatiment('Veuillez entrer une adresse.', 'error'); return; }
            showNotificationBatiment('Recherche en cours...', 'info');

            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`, {
                headers: { 'User-Agent': 'CampusManagement/1.0' }
            })
                .then(r => r.json())
                .then(data => {
                    if (data?.length > 0) {
                        const lat = parseFloat(data[0].lat), lng = parseFloat(data[0].lon);
                        selectMapBatiment.setView([lat, lng], 18);
                        selectMarkerBatiment.setLatLng([lat, lng]);
                        document.getElementById('modal-latitude-batiment').value  = lat.toFixed(6);
                        document.getElementById('modal-longitude-batiment').value = lng.toFixed(6);
                        showNotificationBatiment('Adresse trouvée !', 'success');
                    } else {
                        showNotificationBatiment('Adresse non trouvée.', 'error');
                    }
                })
                .catch(() => showNotificationBatiment('Erreur lors de la recherche.', 'error'));
        }

        function getCurrentLocationInModalBatiment() {
            if (!navigator.geolocation) { showNotificationBatiment('Géolocalisation non supportée.', 'error'); return; }
            showNotificationBatiment('Recherche de votre position...', 'info');

            navigator.geolocation.getCurrentPosition(
                ({ coords: { latitude: lat, longitude: lng } }) => {
                    selectMapBatiment.setView([lat, lng], 18);
                    selectMarkerBatiment.setLatLng([lat, lng]);
                    document.getElementById('modal-latitude-batiment').value  = lat.toFixed(6);
                    document.getElementById('modal-longitude-batiment').value = lng.toFixed(6);
                    showNotificationBatiment('Position récupérée !', 'success');
                },
                (err) => {
                    const msgs = {
                        [err.PERMISSION_DENIED]:    'Permission refusée.',
                        [err.POSITION_UNAVAILABLE]: 'Position non disponible.',
                        [err.TIMEOUT]:              'Délai dépassé.',
                    };
                    showNotificationBatiment(msgs[err.code] || 'Erreur de géolocalisation.', 'error');
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }

        function confirmPositionBatiment() {
            const lat = document.getElementById('modal-latitude-batiment').value;
            const lng = document.getElementById('modal-longitude-batiment').value;

            if (lat && lng) {
                document.getElementById('latitude_batiment').value  = lat;
                document.getElementById('longitude_batiment').value = lng;
                showNotificationBatiment('Position enregistrée !', 'success');
                mapModalBatiment.hide();
            } else {
                showNotificationBatiment('Veuillez sélectionner une position.', 'error');
            }
        }

        function searchAddressForBatiment() {
            const address = document.getElementById('adresse_batiment').value;
            if (!address?.trim()) { showNotificationBatiment('Veuillez renseigner l\'adresse.', 'error'); return; }

            const latInput = document.getElementById('latitude_batiment');
            const lonInput = document.getElementById('longitude_batiment');
            const origLat  = latInput.value, origLon = lonInput.value;

            latInput.value = lonInput.value = 'Recherche...';

            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1&countrycodes=ci`, {
                headers: { 'User-Agent': 'CampusManagement/1.0' }
            })
                .then(r => r.json())
                .then(data => {
                    if (data?.length > 0) {
                        latInput.value = parseFloat(data[0].lat).toFixed(6);
                        lonInput.value = parseFloat(data[0].lon).toFixed(6);
                        latInput.style.backgroundColor = lonInput.style.backgroundColor = '#e8f5e9';
                        showNotificationBatiment('Coordonnées trouvées !', 'success');
                    } else {
                        latInput.value = origLat; lonInput.value = origLon;
                        latInput.style.backgroundColor = lonInput.style.backgroundColor = '#ffebee';
                        showNotificationBatiment('Adresse non trouvée.', 'error');
                    }
                    setTimeout(() => { latInput.style.backgroundColor = lonInput.style.backgroundColor = ''; }, 2000);
                })
                .catch(() => {
                    latInput.value = origLat; lonInput.value = origLon;
                    showNotificationBatiment('Erreur lors de la recherche.', 'error');
                });
        }

        // ================================================
        // NOTIFICATIONS
        // ================================================

        function showNotificationBatiment(message, type) {
            document.querySelectorAll('.custom-notification-batiment').forEach(n => n.remove());

            const n = document.createElement('div');
            n.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} alert-dismissible fade show custom-notification-batiment`;
            n.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
            n.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
            document.body.appendChild(n);
            setTimeout(() => n.remove(), 4000);
        }

        // ================================================
        // DOCUMENT READY
        // ================================================

        document.addEventListener('DOMContentLoaded', function () {
            const fileInput = document.getElementById('batiment-fileInput');
            const fileNameDiv = document.getElementById('batiment-file-name');
            const previewDiv = document.getElementById('batiment-file-preview');

            fileInput?.addEventListener('change', function () {
                if (!this.files || !this.files[0]) {
                    fileNameDiv.style.display = 'none';
                    previewDiv.style.display = 'none';
                    previewDiv.innerHTML = '';
                    return;
                }

                const file = this.files[0];
                const previewUrl = URL.createObjectURL(file);
                fileNameDiv.style.display = 'block';
                fileNameDiv.innerHTML = '<i class="fas fa-check-circle"></i> Fichier sélectionné : ' + file.name;
                previewDiv.style.display = 'block';
                previewDiv.innerHTML = file.type === 'application/pdf'
                    ? `<iframe src="${previewUrl}" title="Aperçu du plan" style="width: 100%; height: 360px; border: 1px solid #ddd;"></iframe>`
                    : `<img src="${previewUrl}" alt="Aperçu du plan" style="max-width: 100%; max-height: 360px; object-fit: contain;">`;
            });
        });

        $(document).ready(function () {
            // Initialisation au chargement
            const selectedType = $('input[name="type_architecture"]:checked').val();
            if (selectedType === 'simple') {
                $('#structure-simple').show();
                $('#structure-complexe').hide();
                generateEtages();
            } else {
                $('#structure-simple').hide();
                $('#structure-complexe').show();
                generateAiles();
            }

            // Initialiser le type de cité si une cité est déjà sélectionnée (mode édition)
            if ($('#code_cite').val()) {
                updateCiteType();
            }

            // Changement de type d'architecture
            $('input[name="type_architecture"]').change(function () {
                if ($(this).val() === 'simple') {
                    $('#structure-simple').show();
                    $('#structure-complexe').hide();
                    generateEtages();
                } else {
                    $('#structure-simple').hide();
                    $('#structure-complexe').show();
                    generateAiles();
                }
            });

            // Nettoyage carte à la fermeture du modal
            document.getElementById('mapModalBatiment')?.addEventListener('hidden.bs.modal', function () {
                if (selectMapBatiment) { selectMapBatiment.remove(); selectMapBatiment = null; }
            });

            // Soumission du formulaire
            $('.validation-batiment').click(function (e) {
                e.preventDefault();

                const batimentData = collectBatimentFormData();
                console.log('✅ Données envoyées:', batimentData);

                // Validations
                if (!batimentData.code_campus || !batimentData.code_cite) {
                    showNotificationBatiment('Veuillez sélectionner un campus et une cité.', 'error');
                    return;
                }
                if (!batimentData.name?.trim()) {
                    showNotificationBatiment('Veuillez saisir un nom pour le bâtiment.', 'error');
                    return;
                }
                if (batimentData.total_chambres === 0) {
                    showNotificationBatiment('Veuillez saisir au moins une chambre.', 'error');
                    return;
                }

                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                @if($editMode)
                    formData.append('_method', 'PUT');
                    formData.append('code', '{{ $itemToEdit->id_batiment ?? '' }}');
                @endif
                formData.append('batiment_data', JSON.stringify(batimentData));
                const removePlan = document.getElementById('remove_plan_bat');
                if (removePlan?.checked) {
                    formData.append('remove_plan', '1');
                }

                const fileInput = $('#batiment-fileInput')[0];
                if (fileInput.files.length > 0) {
                    formData.append('plan_file', fileInput.files[0]);
                }

                const btn          = $('.validation-batiment');
                const originalText = btn.html();
                btn.html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...').prop('disabled', true);

                $.ajax({
                    url:         '{{ $editMode ? route("admin.hebergement.parc-immobilier.updateBatiment") : route("admin.hebergement.parc-immobilier.storeBatiment") }}',
                    method:      'POST',
                    data:        formData,
                    contentType: false,
                    processData: false,
                    dataType:    'json',
                    success: function (response) {
                        if (response.success) {
                            window.location.href = response.redirect;
                        } else {
                            showNotificationBatiment(response.message || 'Erreur lors de l\'enregistrement.', 'error');
                            btn.html(originalText).prop('disabled', false);
                        }
                    },
                    error: function (xhr) {
                        const msg = xhr.responseJSON?.message || 'Erreur lors de l\'enregistrement.';
                        showNotificationBatiment(msg, 'error');
                        btn.html(originalText).prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endpush
