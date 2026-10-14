

<div id="cite-form" class="form-container"
     style="{{ $selectedType === 'cite' ? 'display: block;' : 'display: none;' }}">
    <h4 class="card-title mb-4">{{ $editMode ? 'Modifier la Cité' : 'Formulaire Nouvelle Cité' }}</h4>
    <form id="form-cite"
          action="{{ $editMode ? route('admin.hebergement.parc-immobilier.updateCite') : route('admin.hebergement.parc-immobilier.storeCite') }}"
          method="POST"
          enctype="multipart/form-data">
        @csrf
        @if($editMode)
            @method('PUT')
            <input type="hidden" name="code" value="{{ $itemToEdit->id_cite ?? '' }}">
        @endif

        <input type="hidden" name="type" value="cite">

        <div class="form-group" hidden="hidden">
            <label>Campus de rattachement <span class="required">*</span></label>
            <input type="hidden" name="campus_id" value="{{$campus->id_campus}}">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Nom de la Cité <span class="required">*</span></label>
                <input type="text"
                       name="name"
                       id="cite_name"
                       value="{{ $editMode ? ($itemToEdit->name ?? '') : old('name') }}"
                       placeholder="Cite 1"
                       required>
            </div>
            <div class="form-group">
                <label>Code Cité <span class="required">*</span></label>
                <input type="text"
                       name="code_cite"
                       id="code_cite"
                       value="{{ $editMode ? ($itemToEdit->code_cite ?? '') : old('code_cite') }}"
                       placeholder="Ex: CIT-012"
                       required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Type de Cité <span class="required">*</span></label>
                <select name="type_cite" required class="form-select">
                    @foreach($type_cites as $item)
                        <option value="{{ $item->id_type_cite }}"
                                {{ ($editMode && $itemToEdit && $itemToEdit->type_cite_id == $item->id_type_cite) ? 'selected' : '' }}>
                            {{ $item->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Capacité estimée d'etudiant  <span class="required">*</span></label>
                <div class="counter-input">
                    <button type="button" class="counter-btn" onclick="decrementCounter('capacite_max_cite')">−</button>
                    <input type="number"
                           id="capacite_max_cite"
                           name="capacite_max"
                           value="{{ $editMode ? ($itemToEdit->capacite_max ?? 0) : 0 }}"
                           min="0" required>
                    <button type="button" class="counter-btn" onclick="incrementCounter('capacite_max_cite')">+</button>
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Nombre de bâtiments</label>
                <div class="counter-input">
                    <button type="button" class="counter-btn" onclick="decrementCounter('nombre_batiments')">−</button>
                    <input type="number"
                           id="nombre_batiments"
                           name="nombre_batiments"
                           value="{{ $editMode ? ($itemToEdit->nombre_batiments ?? 0) : 0 }}"
                           min="0">
                    <button type="button" class="counter-btn" onclick="incrementCounter('nombre_batiments')">+</button>
                </div>
            </div>

            <div class="form-group">
                <label>Superficie (m²)</label>
                <input type="number"
                       name="superficie"
                       id="superficie"
                       value="{{ $editMode ? ($itemToEdit->superficie ?? '') : '' }}"
                       step="0.01"
                       min="0"
                       placeholder="Ex: 1500.50">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Année de construction</label>
                <input type="number"
                       name="annee_construction"
                       id="annee_construction"
                       value="{{ $editMode ? ($itemToEdit->annee_construction ?? '') : '' }}"
                       min="1900"
                       max="{{ date('Y') }}"
                       placeholder="Ex: 2020">
            </div>

            <div class="form-group">
                <label>Statut</label>
                <select name="statut" class="form-select">
                    <option value="ouvert" {{ ($editMode && $itemToEdit && $itemToEdit->statut == 'ouvert') ? 'selected' : '' }}>Ouvert</option>
                    <option value="ferme" {{ ($editMode && $itemToEdit && $itemToEdit->statut == 'ferme') ? 'selected' : '' }}>Ferme</option>
                    <option value="en_renovation" {{ ($editMode && $itemToEdit && $itemToEdit->statut == 'en_renovation') ? 'selected' : '' }}>En renovation</option>
                    <option value="en_construction" {{ ($editMode && $itemToEdit && $itemToEdit->statut == 'en_construction') ? 'selected' : '' }}>En construction</option>
                </select>
            </div>
        </div>

        <div class="form-section">
            <h5 class="mt-3">Contacts</h5>
            <div class="form-row">
                <div class="form-group">
                    <label>Responsable</label>
{{--                    <input type="text"--}}
{{--                           name="responsable_code"--}}
{{--                           value="{{ $editMode ? ($itemToEdit->responsable_code ?? '') : '' }}"--}}
{{--                           placeholder="Code du responsable">--}}
                    <select name="responsable_id" id="responsable_id">
                        <option value=""> Sélèctionnez le responsable </option>
                        @foreach($users as $user)
                            @php
                                $responsableData = [
                                    'name' => $user->name,
                                    'matricule' => $user->matricule,
                                    'telephone_principal' => $user->telephone_principal,
                                    'telephone_secondaire' => $user->telephone_secondaire,
                                    'email' => $user->email,
                                    'email_secondaire' => $user->email_secondaire,
                                    'adresse' => $user->adresse,
                                ];
                            @endphp
                            <option value="{{ $user->id_user }}"
                                    data-user="{{ json_encode($responsableData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
                                    {{ ($editMode && $itemToEdit && $itemToEdit->responsable_id == $user->id_user) ? 'selected' : '' }}
                            > {{ $user->name }} </option>
                        @endforeach
                    </select>
                    <div id="responsable-details" class="mt-2 p-2 border rounded" style="display: none;"></div>
                </div>
                <div class="form-group">
                    <label>Téléphone 1 de la cite</label>
                    <input type="tel"
                           name="telephone1"
                           value="{{ $editMode ? ($itemToEdit->telephone1 ?? '') : '' }}"
                           placeholder="Ex: 05 04 03 02 01">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Téléphone 2 de la cite</label>
                    <input type="tel"
                           name="telephone2"
                           value="{{ $editMode ? ($itemToEdit->telephone2 ?? '') : '' }}"
                           placeholder="Ex: 05 04 03 02 02">
                </div>
                <div class="form-group">
                    <label>Téléphone 3 de la cite</label>
                    <input type="tel"
                           name="telephone3"
                           value="{{ $editMode ? ($itemToEdit->telephone3 ?? '') : '' }}"
                           placeholder="Ex: 05 04 03 02 03">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Email 1 de la cite</label>
                    <input type="email"
                           name="email1"
                           value="{{ $editMode ? ($itemToEdit->email1 ?? '') : '' }}"
                           placeholder="cite@universite.edu">
                </div>
                <div class="form-group">
                    <label>Email 2 de la cite</label>
                    <input type="email"
                           name="email2"
                           value="{{ $editMode ? ($itemToEdit->email2 ?? '') : '' }}"
                           placeholder="contact@universite.edu">
                </div>
            </div>
        </div>

        <div class="form-group">
            <label> Règlement Intérieur</label>
            <select name="reglement_id" id="reglement_id">
                <option value=""> Sélèctionnez le règlement Intérieur </option>
                @foreach($reglements as $reglement)
                    <option value="{{ $reglement->id_reglement }}"
                            {{ ($editMode && $itemToEdit && $itemToEdit->reglement_interieur_id == $reglement->id_reglement) ? 'selected' : '' }}
                    > {{ $reglement->titre }} </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label>Adresse Géographique  <span class="required">*</span></label>
            <input type="text"
                   name="adresse"
                   id="adresse_cite"
                   value="{{ $editMode ? ($itemToEdit->adresse ?? '') : old('adresse') }}"
                   placeholder="Ex: Rue 123, Quartier Centre, Ville"
                   class="form-control" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Latitude  <span class="required">*</span></label>
                <input type="text"
                       name="latitude"
                       id="latitude_cite"
                       value="{{ $editMode ? ($itemToEdit->latitude ?? '6.827600') : old('latitude', '6.827600') }}"
                       placeholder="Ex: 8.034670"
                       class="coordinate-input" required>
            </div>
            <div class="form-group">
                <label>Longitude  <span class="required">*</span></label>
                <input type="text"
                       name="longitude"
                       id="longitude_cite"
                       value="{{ $editMode ? ($itemToEdit->longitude ?? '-5.289300') : old('longitude', '-5.289300') }}"
                       placeholder="Ex: -2.796940"
                       class="coordinate-input" required>
            </div>
        </div>

        <div class="form-row" style="margin-bottom: 15px;">
            <div class="form-group" style="flex: 1;">
                <label>Sélectionner la position sur la carte</label>
                <button type="button" class="btn btn-primary" onclick="openMapModalCite()" style="width: 100%;">
                    <i class="fas fa-map-marked-alt"></i> Ouvrir la carte pour sélectionner la position
                </button>
                <small class="form-text text-muted">Cliquez sur la carte pour positionner précisément le marqueur</small>
            </div>

            <div class="form-group" style="flex: 1;">
                <label>Ou rechercher par adresse</label>
                <button type="button" class="btn btn-info" onclick="searchAddressForCite()" style="width: 100%;">
                    <i class="fas fa-map-pin"></i> Obtenir les coordonnées GPS
                </button>
                <small class="form-text text-muted">Recherche automatique à partir de l'adresse</small>
            </div>
        </div>

        <div class="form-group">
            <label>Services Disponibles</label>
            <div class="services-grid">
                <div class="service-checkbox">
                    <input type="checkbox"
                           id="wifi_gratuit"
                           name="wifi_gratuit"
                           value="1"
                            {{ ($editMode && $itemToEdit && $itemToEdit->wifi_gratuit == 1) ? 'checked' : '' }}>
                    <label for="wifi_gratuit">Wifi gratuit</label>
                </div>
                <div class="service-checkbox">
                    <input type="checkbox"
                           id="salle_etude"
                           name="salle_etude"
                           value="1"
                            {{ ($editMode && $itemToEdit && $itemToEdit->salle_etude == 1) ? 'checked' : '' }}>
                    <label for="salle_etude">Salle d'étude</label>
                </div>
                <div class="service-checkbox">
                    <input type="checkbox"
                           id="infirmerie"
                           name="infirmerie"
                           value="1"
                            {{ ($editMode && $itemToEdit && $itemToEdit->infirmerie == 1) ? 'checked' : '' }}>
                    <label for="infirmerie">Infirmerie</label>
                </div>
                <div class="service-checkbox">
                    <input type="checkbox"
                           id="restaurant"
                           name="restaurant"
                           value="1"
                            {{ ($editMode && $itemToEdit && $itemToEdit->restaurant == 1) ? 'checked' : '' }}>
                    <label for="restaurant">Salle de Restauration</label>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Plan de la Cité</label>
            @if($editMode && $itemToEdit && $itemToEdit->plan)
                <div class="mb-2">
                    @if(in_array(strtolower(pathinfo($itemToEdit->plan, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']))
                        <img src="{{ asset($itemToEdit->plan) }}" alt="Plan actuel" style="max-width: 240px; max-height: 160px; object-fit: contain;">
                    @else
                        <a href="{{ asset($itemToEdit->plan) }}" target="_blank" rel="noopener">Voir le plan actuel</a>
                    @endif
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="remove_plan" id="remove_plan_cite" value="1">
                        <label class="form-check-label" for="remove_plan_cite">
                            Supprimer le plan actuel
                        </label>
                    </div>
                </div>
            @endif
            <div class="upload-area" onclick="document.getElementById('cite-fileInput').click()">
                <div class="upload-icon">☁️</div>
                <div class="upload-text">
                    {{ $editMode && $itemToEdit && $itemToEdit->plan ? 'Cliquez pour changer le plan' : 'Cliquez pour ajouter un fichier (PDF, PNG, JPEG ...)' }}
                </div>
            </div>
            <input type="file" id="cite-fileInput" name="plan" accept=".pdf,.png,.jpg,.jpeg" style="display: none;">
            <div id="cite-file-name" class="mt-2 text-success" style="display: none;"></div>
            <div id="cite-file-preview" class="mt-2" style="display: none;"></div>
        </div>

        <div class="form-group">
            <label>Description / Notes</label>
            <textarea name="description" placeholder="Détails supplémentaires" rows="3">{{ $editMode ? ($itemToEdit->description ?? '') : old('description') }}</textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> {{ $editMode ? 'Modifier la Cité' : 'Enregistrer la Cité' }}
            </button>
            <button type="button" class="btn btn-secondary" onclick="resetFormCite()">
                <i class="fas fa-redo"></i> Réinitialiser
            </button>
            @if($editMode)
                @adminroute('admin.hebergement.parc-immobilier.create')
                <a href="{{ route('admin.hebergement.parc-immobilier.create', ['type' => 'cite']) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-plus"></i> Nouvelle
                </a>
                @endadminroute
            @endif
        </div>
    </form>
</div>

<!-- Modal pour la carte interactive (Cité) -->
<div class="modal fade" id="mapModalCite" tabindex="-1" data-bs-backdrop="false" aria-labelledby="mapModalCiteLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mapModalCiteLabel">
                    <i class="fas fa-map-marked-alt"></i> Sélectionner la position sur la carte - Cité
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Latitude</label>
                        <input type="text" id="modal-latitude-cite" class="form-control" readonly placeholder="Cliquez sur la carte">
                    </div>
                    <div class="col-md-6">
                        <label>Longitude</label>
                        <input type="text" id="modal-longitude-cite" class="form-control" readonly placeholder="Cliquez sur la carte">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-8">
                        <div class="input-group">
                            <input type="text" id="modal-search-address-cite" class="form-control" placeholder="Rechercher une adresse...">
                            <button class="btn btn-primary" type="button" onclick="searchAddressInModalCite()">
                                <i class="fas fa-search"></i> Rechercher
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-success w-100" type="button" onclick="getCurrentLocationInModalCite()">
                            <i class="fas fa-location-dot"></i> Ma position
                        </button>
                    </div>
                </div>
                <div id="select-map-cite" style="height: 500px; width: 100%; border-radius: 8px;"></div>
                <small class="text-muted mt-2 d-block">💡 Astuce : Cliquez directement sur la carte pour placer le marqueur, ou glissez-déposez le marqueur pour ajuster</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="confirmPositionCite()">
                    <i class="fas fa-check"></i> Confirmer cette position
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Leaflet CSS et JS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

<style>
    /* ============ STYLES AMÉLIORÉS ============ */

    /* ÉTAPE 1: Fond et couleurs */
    .form-container {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.08);
        animation: fadeSlideUp 0.6s ease;
    }

    .card-title {
        color: #1a202c;
        font-size: 1.6rem;
        font-weight: 700;
        padding-bottom: 15px;
        border-bottom: 3px solid #e2e8f0;
        position: relative;
    }

    .card-title::after {
        content: '';
        position: absolute;
        bottom: -3px;
        left: 0;
        width: 60px;
        height: 3px;
        background: linear-gradient(90deg, #667eea, #764ba2);
        border-radius: 2px;
    }

    @keyframes fadeSlideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* ÉTAPE 2: Champs de formulaire */
    .form-group {
        margin-bottom: 20px;
        position: relative;
        animation: fadeIn 0.5s ease;
        animation-fill-mode: both;
    }

    .form-group:nth-child(1) { animation-delay: 0.05s; }
    .form-group:nth-child(2) { animation-delay: 0.1s; }
    .form-group:nth-child(3) { animation-delay: 0.15s; }
    .form-group:nth-child(4) { animation-delay: 0.2s; }
    .form-group:nth-child(5) { animation-delay: 0.25s; }
    .form-group:nth-child(6) { animation-delay: 0.3s; }
    .form-group:nth-child(7) { animation-delay: 0.35s; }
    .form-group:nth-child(8) { animation-delay: 0.4s; }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .form-group label {
        display: block;
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 8px;
        font-size: var(--app-font-min, 15px);
        letter-spacing: 0.3px;
    }

    .form-group label .required {
        color: #e53e3e;
        margin-left: 4px;
        font-weight: 700;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        background: #f7fafc;
        color: #2d3748;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
        background: white;
        transform: translateY(-2px);
    }

    .form-group input:hover,
    .form-group select:hover,
    .form-group textarea:hover {
        border-color: #a0aec0;
        background: white;
    }

    .form-group textarea {
        min-height: 100px;
        resize: vertical;
        font-family: inherit;
    }

    .form-group select {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%234a5568' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 16px center;
        padding-right: 40px;
        cursor: pointer;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 5px;
    }

    /* ÉTAPE 3: Compteurs */
    .counter-input {
        display: flex;
        align-items: center;
        background: #f7fafc;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .counter-input:focus-within {
        border-color: #667eea;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    }

    .counter-btn {
        background: white;
        border: none;
        padding: 10px 18px;
        font-size: 1.2rem;
        font-weight: 700;
        color: #4a5568;
        cursor: pointer;
        transition: all 0.2s ease;
        min-width: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .counter-btn:hover {
        background: #667eea;
        color: white;
    }

    .counter-btn:active {
        transform: scale(0.95);
    }

    .counter-input input {
        border: none !important;
        background: transparent !important;
        text-align: center;
        font-weight: 600;
        font-size: 1.1rem;
        padding: 10px 5px !important;
        flex: 1;
        min-width: 50px;
        box-shadow: none !important;
    }

    .counter-input input:focus {
        box-shadow: none !important;
        transform: none !important;
    }

    /* ÉTAPE 4: Boutons */
    .btn {
        padding: 12px 30px;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }

    .btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .btn:active {
        transform: translateY(0);
    }

    .btn-success {
        background: linear-gradient(135deg, #48bb78, #38a169);
        color: white;
        position: relative;
        overflow: hidden;
    }

    .btn-success:hover {
        background: linear-gradient(135deg, #38a169, #2f855a);
        box-shadow: 0 8px 25px rgba(72, 187, 120, 0.4);
    }

    .btn-success::after {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 60%);
        transform: scale(0);
        transition: transform 0.5s ease;
    }

    .btn-success:active::after {
        transform: scale(1);
    }

    .btn-secondary {
        background: #edf2f7;
        color: #4a5568;
    }

    .btn-secondary:hover {
        background: #e2e8f0;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .btn-primary {
        background: linear-gradient(135deg, #4198e0, #3789d3);
        color: white;
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #3182ce, #2b6cb0);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    }

    .btn-info {
        background: linear-gradient(135deg, #4299e1, #3182ce);
        color: white;
    }

    .btn-info:hover {
        background: linear-gradient(135deg, #3182ce, #2b6cb0);
        box-shadow: 0 8px 25px rgba(66, 153, 225, 0.4);
    }

    .btn-outline-secondary {
        background: transparent;
        border: 2px solid #e2e8f0;
        color: #4a5568;
    }

    .btn-outline-secondary:hover {
        background: #f7fafc;
        border-color: #a0aec0;
        transform: translateY(-2px);
    }

    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 2px solid #f0f0f0;
        flex-wrap: wrap;
    }

    /* ÉTAPE 5: Services */
    .services-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 12px;
        margin-top: 5px;
    }

    .service-checkbox {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 18px;
        background: #f7fafc;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .service-checkbox:hover {
        background: #edf2f7;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .service-checkbox input[type="checkbox"] {
        width: 20px;
        height: 20px;
        accent-color: #667eea;
        cursor: pointer;
        flex-shrink: 0;
    }

    .service-checkbox input[type="checkbox"]:checked + label {
        color: #667eea;
        font-weight: 600;
    }

    .service-checkbox:has(input:checked) {
        background: #ebf4ff;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .service-checkbox label {
        margin: 0 !important;
        cursor: pointer;
        font-weight: 500;
        color: #4a5568;
        transition: color 0.3s ease;
    }

    /* ÉTAPE 6: Upload */
    .upload-area {
        border: 3px dashed #cbd5e0;
        border-radius: 16px;
        padding: 40px 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: #f7fafc;
        position: relative;
    }

    .upload-area:hover {
        border-color: #667eea;
        background: #edf2f7;
        transform: scale(1.02);
        box-shadow: 0 4px 20px rgba(102, 126, 234, 0.1);
    }

    .upload-area:active {
        transform: scale(0.98);
    }

    .upload-icon {
        font-size: 3.5rem;
        color: #a0aec0;
        margin-bottom: 10px;
        transition: all 0.3s ease;
    }

    .upload-area:hover .upload-icon {
        color: #667eea;
        transform: translateY(-5px);
    }

    .upload-text {
        color: #4a5568;
        font-weight: 500;
        font-size: 0.95rem;
    }

    /* ÉTAPE 7: Section contacts */
    .form-section {
        background: linear-gradient(135deg, #f7fafc, #edf2f7);
        border-radius: 16px;
        padding: 25px;
        margin: 20px 0;
        border: 2px solid #e2e8f0;
        transition: all 0.3s ease;
    }

    .form-section:hover {
        border-color: #a0aec0;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .form-section h5 {
        color: #2d3748;
        font-weight: 700;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1.1rem;
    }

    .form-section h5::before {
        content: '📞';
        font-size: 1.3rem;
    }

    /* Coordonnées GPS */
    .coordinate-input {
        font-family: 'Courier New', monospace;
        font-weight: 600;
        letter-spacing: 0.5px;
        background: #f7fafc;
    }

    .coordinate-input:focus {
        background: white;
        border-color: #667eea;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    }

    .coordinate-input[value="Recherche..."] {
        animation: pulse 1s infinite;
        background: #fef3c7 !important;
        border-color: #f6ad55 !important;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    .form-text {
        color: #718096;
        font-size: var(--app-font-min, 15px);
        margin-top: 6px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .form-text::before {
        content: '💡';
        font-size: var(--app-font-min, 15px);
    }

    /* Checkbox suppression plan */
    .form-check {
        margin-top: 10px;
    }

    .form-check-input {
        width: 18px;
        height: 18px;
        margin-right: 8px;
        accent-color: #667eea;
        cursor: pointer;
    }

    .form-check-label {
        color: #4a5568;
        font-weight: 500;
        cursor: pointer;
    }

    /* Carte */
    #select-map-cite {
        border-radius: 12px !important;
        border: 2px solid #e2e8f0;
        overflow: hidden;
        cursor: crosshair;
        min-height: 500px;
    }

    #select-map-cite:focus-within {
        border-color: #667eea;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    }

    #mapModalCite {
        z-index: 1055 !important;
    }

    #mapModalCite .modal-dialog {
        z-index: 1056 !important;
    }

    #select-map-cite .leaflet-control-container {
        z-index: 1060 !important;
    }

    #select-map-cite .leaflet-pane {
        z-index: 400;
    }

    /* Notification */
    .custom-notification-cite {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border-radius: 8px;
        font-size: var(--app-font-min, 15px);
    }

    /* Animation marqueur */
    @keyframes markerPulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.3); }
        100% { transform: scale(1); }
    }

    .marker-animation {
        animation: markerPulse 0.3s ease;
    }

    /* ÉTAPE 9: Responsive */
    @media (max-width: 768px) {
        .form-container {
            padding: 15px;
        }

        .form-row {
            grid-template-columns: 1fr !important;
            gap: 15px;
        }

        .services-grid {
            grid-template-columns: 1fr 1fr;
        }

        .form-actions {
            flex-direction: column;
        }

        .form-actions .btn {
            width: 100%;
            justify-content: center;
        }

        .form-section {
            padding: 15px;
        }

        .upload-area {
            padding: 25px 15px;
        }

        .card-title {
            font-size: 1.3rem;
        }
    }

    @media (max-width: 480px) {
        .services-grid {
            grid-template-columns: 1fr;
        }

        .form-container {
            padding: 10px;
        }

        .btn {
            padding: 10px 20px;
            font-size: var(--app-font-min, 15px);
        }

        .card-title {
            font-size: 1.1rem;
        }
    }
</style>

<script>
    // ============ JAVASCRIPT AMÉLIORÉ ============

    let mapModalCite = null;
    let selectMapCite = null;
    let selectMarkerCite = null;

    // Fonctions pour les compteurs
    function incrementCounter(fieldId) {
        const input = document.getElementById(fieldId);
        if (input) {
            let value = parseInt(input.value) || 0;
            input.value = value + 1;
            // Déclencher un événement change pour les validations
            input.dispatchEvent(new Event('change'));
        }
    }

    function decrementCounter(fieldId) {
        const input = document.getElementById(fieldId);
        if (input) {
            let value = parseInt(input.value) || 0;
            if (value > 0) {
                input.value = value - 1;
                input.dispatchEvent(new Event('change'));
            }
        }
    }

    // Réinitialisation du formulaire
    function resetFormCite() {
        if (confirm('Voulez-vous réinitialiser le formulaire ?')) {
            const form = document.getElementById('form-cite');
            if (form) {
                form.reset();
            }

            const fileNameDiv = document.getElementById('cite-file-name');
            if (fileNameDiv) fileNameDiv.style.display = 'none';

            showNotificationCite('Formulaire réinitialisé avec succès', 'info');
        }
    }

    // Ouvrir le modal avec la carte
    function openMapModalCite() {
        const latInput = document.getElementById('latitude_cite');
        const lonInput = document.getElementById('longitude_cite');

        let initialLat = parseFloat(latInput.value);
        let initialLng = parseFloat(lonInput.value);

        if (isNaN(initialLat) || initialLat === 0) initialLat = 6.8276;
        if (isNaN(initialLng) || initialLng === 0) initialLng = -5.2893;

        if (!mapModalCite) {
            mapModalCite = new bootstrap.Modal(document.getElementById('mapModalCite'));
        }

        mapModalCite.show();

        setTimeout(() => {
            initMapInModalCite(initialLat, initialLng);
        }, 500);
    }

    function initMapInModalCite(lat, lng) {
        const mapContainer = document.getElementById('select-map-cite');

        if (selectMapCite) {
            selectMapCite.remove();
            selectMapCite = null;
        }

        selectMapCite = L.map('select-map-cite', {
            preferCanvas: true
        }).setView([lat, lng], 18);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 22,
            minZoom: 2
        }).addTo(selectMapCite);

        const customIcon = L.divIcon({
            html: '<div style="background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%); width: 32px; height: 32px; border-radius: 12px 12px 12px 0; display: flex; align-items: center; justify-content: center; color: white; font-size: 16px; border: 3px solid white; box-shadow: 0 4px 8px rgba(0,0,0,0.2);"><i class="fas fa-building"></i></div>',
            iconSize: [32, 32],
            iconAnchor: [16, 32],
            popupAnchor: [0, -32]
        });

        selectMarkerCite = L.marker([lat, lng], {
            icon: customIcon,
            draggable: true
        }).addTo(selectMapCite);

        selectMarkerCite.on('dragend', function(e) {
            const pos = selectMarkerCite.getLatLng();
            document.getElementById('modal-latitude-cite').value = pos.lat.toFixed(6);
            document.getElementById('modal-longitude-cite').value = pos.lng.toFixed(6);
        });

        selectMapCite.on('click', function(e) {
            selectMarkerCite.setLatLng(e.latlng);
            document.getElementById('modal-latitude-cite').value = e.latlng.lat.toFixed(6);
            document.getElementById('modal-longitude-cite').value = e.latlng.lng.toFixed(6);

            const markerElement = selectMarkerCite.getElement();
            if (markerElement) {
                markerElement.classList.add('marker-animation');
                setTimeout(() => markerElement.classList.remove('marker-animation'), 300);
            }
        });

        document.getElementById('modal-latitude-cite').value = lat.toFixed(6);
        document.getElementById('modal-longitude-cite').value = lng.toFixed(6);

        setTimeout(() => {
            if (selectMapCite) {
                selectMapCite.invalidateSize();
            }
        }, 100);
    }

    // Rechercher une adresse dans le modal
    function searchAddressInModalCite() {
        const address = document.getElementById('modal-search-address-cite').value;
        if (!address || !address.trim()) {
            showNotificationCite('Veuillez entrer une adresse à rechercher.', 'error');
            return;
        }

        showNotificationCite('Recherche en cours...', 'info');

        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`;

        fetch(url, {
            headers: { 'User-Agent': 'CampusManagement/1.0' }
        })
            .then(response => response.json())
            .then(data => {
                if (data && data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lng = parseFloat(data[0].lon);
                    selectMapCite.setView([lat, lng], 18);
                    selectMarkerCite.setLatLng([lat, lng]);
                    document.getElementById('modal-latitude-cite').value = lat.toFixed(6);
                    document.getElementById('modal-longitude-cite').value = lng.toFixed(6);
                    showNotificationCite('Adresse trouvée !', 'success');
                } else {
                    showNotificationCite('Adresse non trouvée. Veuillez préciser votre recherche.', 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotificationCite('Erreur lors de la recherche.', 'error');
            });
    }

    // Obtenir la position actuelle dans le modal
    function getCurrentLocationInModalCite() {
        if (!navigator.geolocation) {
            showNotificationCite('La géolocalisation n\'est pas supportée.', 'error');
            return;
        }

        showNotificationCite('Recherche de votre position...', 'info');

        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                selectMapCite.setView([lat, lng], 18);
                selectMarkerCite.setLatLng([lat, lng]);
                document.getElementById('modal-latitude-cite').value = lat.toFixed(6);
                document.getElementById('modal-longitude-cite').value = lng.toFixed(6);
                showNotificationCite('Position récupérée avec succès !', 'success');
            },
            function(error) {
                let errorMessage = '';
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage = 'Permission refusée. Activez la géolocalisation.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage = 'Position non disponible.';
                        break;
                    case error.TIMEOUT:
                        errorMessage = 'Délai dépassé.';
                        break;
                    default:
                        errorMessage = 'Erreur de géolocalisation.';
                }
                showNotificationCite(errorMessage, 'error');
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    }

    // Confirmer la position
    function confirmPositionCite() {
        const lat = document.getElementById('modal-latitude-cite').value;
        const lng = document.getElementById('modal-longitude-cite').value;

        if (lat && lng && lat !== '' && lng !== '') {
            document.getElementById('latitude_cite').value = lat;
            document.getElementById('longitude_cite').value = lng;
            showNotificationCite('Position enregistrée avec succès !', 'success');
            mapModalCite.hide();
        } else {
            showNotificationCite('Veuillez sélectionner une position sur la carte.', 'error');
        }
    }

    // Recherche d'adresse à partir du formulaire
    function searchAddressForCite() {
        const address = document.getElementById('adresse_cite').value;

        if (!address || !address.trim()) {
            showNotificationCite('Veuillez renseigner l\'adresse géographique.', 'error');
            return;
        }

        const latInput = document.getElementById('latitude_cite');
        const lonInput = document.getElementById('longitude_cite');
        const originalLat = latInput.value;
        const originalLon = lonInput.value;

        latInput.value = 'Recherche...';
        lonInput.value = 'Recherche...';
        latInput.style.backgroundColor = '#f5f5f5';
        lonInput.style.backgroundColor = '#f5f5f5';

        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1&countrycodes=ci`;

        fetch(url, {
            headers: { 'User-Agent': 'CampusManagement/1.0' }
        })
            .then(response => response.json())
            .then(data => {
                if (data && data.length > 0) {
                    latInput.value = parseFloat(data[0].lat).toFixed(6);
                    lonInput.value = parseFloat(data[0].lon).toFixed(6);
                    latInput.style.backgroundColor = '#e8f5e9';
                    lonInput.style.backgroundColor = '#e8f5e9';

                    const citeNameInput = document.getElementById('cite_name');
                    if (!citeNameInput.value && data[0].display_name) {
                        const addressParts = data[0].display_name.split(',');
                        if (addressParts.length > 0) {
                            citeNameInput.value = addressParts[0].trim();
                        }
                    }

                    showNotificationCite('Coordonnées trouvées avec succès !', 'success');

                    setTimeout(() => {
                        latInput.style.backgroundColor = '';
                        lonInput.style.backgroundColor = '';
                    }, 2000);
                } else {
                    latInput.value = originalLat;
                    lonInput.value = originalLon;
                    latInput.style.backgroundColor = '#ffebee';
                    lonInput.style.backgroundColor = '#ffebee';
                    showNotificationCite('Adresse non trouvée. Veuillez préciser votre recherche.', 'error');

                    setTimeout(() => {
                        latInput.style.backgroundColor = '';
                        lonInput.style.backgroundColor = '';
                    }, 2000);
                }
            })
            .catch(error => {
                latInput.value = originalLat;
                lonInput.value = originalLon;
                latInput.style.backgroundColor = '#ffebee';
                lonInput.style.backgroundColor = '#ffebee';
                console.error('Erreur:', error);
                showNotificationCite('Erreur lors de la recherche.', 'error');

                setTimeout(() => {
                    latInput.style.backgroundColor = '';
                    lonInput.style.backgroundColor = '';
                }, 2000);
            });
    }

    // Notification améliorée
    function showNotificationCite(message, type = 'info') {
        const existingNotifications = document.querySelectorAll('.custom-notification-cite');
        existingNotifications.forEach(notif => notif.remove());

        const colors = {
            success: '#48bb78',
            error: '#fc8181',
            info: '#63b3ed',
            warning: '#f6ad55'
        };

        const icons = {
            success: '✅',
            error: '❌',
            info: 'ℹ️',
            warning: '⚠️'
        };

        const notification = document.createElement('div');
        notification.className = 'custom-notification-cite';
        notification.style.cssText = `
            position: fixed;
            top: 30px;
            right: 30px;
            z-index: 9999;
            min-width: 350px;
            max-width: 500px;
            background: white;
            padding: 18px 22px;
            border-radius: 14px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideInRight 0.5s ease;
            border-left: 5px solid ${colors[type] || colors.info};
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        `;

        notification.innerHTML = `
            <div style="font-size: 1.8rem; flex-shrink: 0;">${icons[type] || icons.info}</div>
            <div style="flex: 1; font-size: 0.95rem; color: #2d3748; font-weight: 500;">${message}</div>
            <button onclick="this.parentElement.remove()" style="
                background: none;
                border: none;
                font-size: 1.3rem;
                cursor: pointer;
                color: #a0aec0;
                padding: 0 6px;
                transition: color 0.2s ease;
            " onmouseover="this.style.color='#4a5568'" onmouseout="this.style.color='#a0aec0'">✕</button>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            if (notification && notification.remove) {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(50px)';
                notification.style.transition = 'all 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }
        }, 4000);
    }

    // Ajout de l'animation pour les notifications
    const styleSheet = document.createElement("style");
    styleSheet.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    `;
    document.head.appendChild(styleSheet);

    // Gestionnaire pour le fichier plan
    document.addEventListener('DOMContentLoaded', function() {
        const responsableSelect = document.getElementById('responsable_id');
        const responsableDetails = document.getElementById('responsable-details');

        function updateResponsableFields() {
            const selectedOption = responsableSelect?.selectedOptions[0];
            if (!selectedOption || !selectedOption.value) {
                if (responsableDetails) responsableDetails.style.display = 'none';
                return;
            }

            const responsable = JSON.parse(selectedOption.dataset.user || '{}');
            const setValue = (name, value) => {
                const input = document.querySelector(`[name="${name}"]`);
                if (input && value) input.value = value;
            };

            setValue('telephone1', responsable.telephone_principal);
            setValue('telephone2', responsable.telephone_secondaire);
            setValue('email1', responsable.email);
            setValue('email2', responsable.email_secondaire);

            if (responsableDetails) {
                responsableDetails.innerHTML = `
                    <strong>Informations du responsable</strong><br>
                    ${responsable.name || ''}${responsable.matricule ? ` - ${responsable.matricule}` : ''}<br>
                    Téléphone : ${responsable.telephone_principal || 'Non renseigné'}<br>
                    Email : ${responsable.email || 'Non renseigné'}
                    ${responsable.adresse ? `<br>Adresse : ${responsable.adresse}` : ''}
                `;
                responsableDetails.style.display = 'block';
            }
        }

        responsableSelect?.addEventListener('change', updateResponsableFields);
        updateResponsableFields();

        const fileInput = document.getElementById('cite-fileInput');
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                const fileNameDiv = document.getElementById('cite-file-name');
                const previewDiv = document.getElementById('cite-file-preview');
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    const previewUrl = URL.createObjectURL(file);
                    fileNameDiv.style.display = 'block';
                    fileNameDiv.innerHTML = '<i class="fas fa-check-circle"></i> Fichier sélectionné : ' + file.name;
                    previewDiv.style.display = 'block';
                    previewDiv.innerHTML = file.type === 'application/pdf'
                        ? `<iframe src="${previewUrl}" title="Aperçu du plan" style="width: 100%; height: 360px; border: 1px solid #ddd;"></iframe>`
                        : `<img src="${previewUrl}" alt="Aperçu du plan" style="max-width: 100%; max-height: 360px; object-fit: contain;">`;
                } else {
                    fileNameDiv.style.display = 'none';
                    previewDiv.style.display = 'none';
                    previewDiv.innerHTML = '';
                }
            });
        }

        const addressInput = document.getElementById('adresse_cite');
        if (addressInput) {
            addressInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchAddressForCite();
                }
            });
        }

        document.getElementById('mapModalCite')?.addEventListener('hidden.bs.modal', function() {
            if (selectMapCite) {
                selectMapCite.remove();
                selectMapCite = null;
            }
        });
    });
</script>
