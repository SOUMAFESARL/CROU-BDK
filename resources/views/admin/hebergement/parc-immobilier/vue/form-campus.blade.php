
{{--<div id="campus-form" class="form-container"--}}
{{--     style="{{ $selectedType === 'campus' ? 'display: block;' : 'display: none;' }}">--}}

{{--    <h4 class="card-title mb-4">{{ $editMode ? 'Modifier le Campus' : 'Formulaire Nouveau Campus' }}</h4>--}}

{{--    @if(request()->has('errors'))--}}
{{--        <div class="alert alert-danger alert-dismissible fade show" role="alert">--}}
{{--            <i class="fas fa-exclamation-circle me-2"></i>--}}
{{--            <ul class="mb-0">--}}
{{--                --}}{{--                    @foreach($errors->all() as $error)--}}
{{--                --}}{{--                        <li>{{ $error }}</li>--}}
{{--                --}}{{--                    @endforeach--}}
{{--                {{ session('errors') }}--}}
{{--            </ul>--}}
{{--            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>--}}
{{--        </div>--}}
{{--    @endif--}}

{{--    <form id="form-campus"--}}
{{--          action="{{ $editMode ? route('admin.hebergement.parc-immobilier.updateCampus') : route('admin.hebergement.parc-immobilier.storeCampus') }}"--}}
{{--          method="POST"--}}
{{--          enctype="multipart/form-data">--}}

{{--        @csrf--}}
{{--        @if($editMode)--}}
{{--            @method('PUT')--}}
{{--            <input type="hidden" name="code" value="{{ $itemToEdit->code ?? '' }}">--}}
{{--        @endif--}}

{{--        <input type="hidden" name="type" value="campus">--}}

{{--        <div class="form-row">--}}
{{--            <div class="form-group">--}}
{{--                <label>Nom du Campus <span class="required">*</span></label>--}}
{{--                <input type="text"--}}
{{--                       name="name"--}}
{{--                       value="{{ old('name', $itemToEdit->name ?? '') }}"--}}
{{--                       placeholder="Ex: Campus de Bondoukou"--}}
{{--                       class="{{ $errors->has('name') ? 'error' : '' }}"--}}
{{--                       required>--}}
{{--                @error('name')--}}
{{--                <span class="error-message">{{ $message }}</span>--}}
{{--                @enderror--}}
{{--            </div>--}}

{{--            <div class="form-group">--}}
{{--                <label>Code Campus</label>--}}
{{--                <input type="text"--}}
{{--                       name="code_campus"--}}
{{--                       value="{{ old('code_campus', $itemToEdit->code_campus ?? '') }}"--}}
{{--                       placeholder="Ex: Camp-012"--}}
{{--                       class="{{ $errors->has('code_campus') ? 'error' : '' }}">--}}
{{--                @error('code_campus')--}}
{{--                <span class="error-message">{{ $message }}</span>--}}
{{--                @enderror--}}
{{--            </div>--}}
{{--        </div>--}}

{{--        <div class="form-row">--}}

{{--            <div class="form-group">--}}
{{--                <label>Region <span class="required">*</span></label>--}}
{{--                <select name="region_id" class="form-select" id="region_id" required--}}


{{--onchange="getRequest('{{ url('/') }}/admin/list/city?parent_id='+this.value,'ville_id','select',this.value)"--}}

{{--                >--}}
{{--                    <option value="">selectionnez une region</option>--}}
{{--                    @foreach($regions as $item)--}}
{{--                        <option value="{{$item->id}}" {{ old('region_id', $itemToEdit->region_id ?? '') == $item->id ? 'selected' : '' }}>{{$item->name}}</option>--}}
{{--                    @endforeach--}}
{{--                </select>--}}
{{--            </div>--}}

{{--            <div class="form-group">--}}
{{--                <label>Ville <span class="required">*</span></label>--}}
{{--                <select name="ville_id" class="form-select" id="ville_id" required>--}}
{{--                    <option value="">selectionnez une ville</option>--}}

{{--                        @foreach($villes ?? [] as $ville)--}}
{{--                            <option value="{{$ville->id}}" {{ old('ville_id', $itemToEdit->ville_id) == $ville->id ? 'selected' : '' }}>{{$ville->name}}</option>--}}
{{--                        @endforeach--}}
{{--                    --}}
{{--                </select>--}}
{{--            </div>--}}

{{--            <div class="form-group">--}}
{{--                <label>Adresse Géographique <span class="required">*</span></label>--}}
{{--                <input type="text"--}}
{{--                       name="adresse"--}}
{{--                       id="adresse"--}}
{{--                       value="{{ old('adresse', $itemToEdit->adresse ?? '') }}"--}}
{{--                       placeholder="Ex: Rue 123, Quartier Centre"--}}
{{--                       class="{{ $errors->has('adresse') ? 'error' : '' }}"--}}
{{--                       required>--}}
{{--                @error('adresse')--}}
{{--                <span class="error-message">{{ $message }}</span>--}}
{{--                @enderror--}}
{{--            </div>--}}
{{--        </div>--}}

{{--        <!-- NOUVEAU: Ligne pour la longitude et latitude avec récupération automatique -->--}}
{{--        <div class="form-row">--}}
{{--            <div class="form-group">--}}
{{--                <label>Latitude</label>--}}
{{--                <input type="text"--}}
{{--                       name="latitude"--}}
{{--                       id="latitude"--}}
{{--                       value="{{ old('latitude', $itemToEdit->latitude ?? '') }}"--}}
{{--                       placeholder="Ex: 8.034670"--}}
{{--                       class="{{ $errors->has('latitude') ? 'error' : '' }}">--}}
{{--                @error('latitude')--}}
{{--                <span class="error-message">{{ $message }}</span>--}}
{{--                @enderror--}}
{{--            </div>--}}

{{--            <div class="form-group">--}}
{{--                <label>Longitude</label>--}}
{{--                <input type="text"--}}
{{--                       name="longitude"--}}
{{--                       id="longitude"--}}
{{--                       value="{{ old('longitude', $itemToEdit->longitude ?? '') }}"--}}
{{--                       placeholder="Ex: -2.796940"--}}
{{--                       class="{{ $errors->has('longitude') ? 'error' : '' }}">--}}
{{--                @error('longitude')--}}
{{--                <span class="error-message">{{ $message }}</span>--}}
{{--                @enderror--}}
{{--            </div>--}}

{{--            <div class="form-group" style="display: flex; align-items: flex-end;">--}}
{{--                <button type="button" class="btn btn-info" onclick="getCurrentLocation()" style="width: 100%;">--}}
{{--                    <i class="fas fa-map-marker-alt"></i> Récupérer ma position--}}
{{--                </button>--}}
{{--            </div>--}}
{{--        </div>--}}

{{--        <!-- MODIFIÉ: Option de recherche par adresse complète -->--}}
{{--        <div class="form-row" style="margin-bottom: 15px;">--}}
{{--            <div class="form-group" style="flex: 1;">--}}
{{--                <label>Rechercher les coordonnées à partir de l'adresse</label>--}}
{{--                <button type="button" class="btn btn-primary" onclick="searchAddressFromForm()" style="width: 100%;">--}}
{{--                    <i class="fas fa-map-pin"></i> Obtenir les coordonnées GPS--}}
{{--                </button>--}}
{{--                <small class="form-text text-muted">Cliquez pour obtenir automatiquement la latitude et longitude à partir de la région, ville et adresse saisies</small>--}}
{{--            </div>--}}
{{--        </div>--}}

{{--        <div class="form-row">--}}
{{--            <div class="form-group">--}}
{{--                <label>Telephone <span class="required">*</span></label>--}}
{{--                <input type="text"--}}
{{--                       name="telephone1"--}}
{{--                       value="{{ old('telephone1', $itemToEdit->telephone1 ?? '') }}"--}}
{{--                       placeholder="Ex: 07xxxxxxxxxx"--}}
{{--                       class="{{ $errors->has('telephone1') ? 'error' : '' }}"--}}
{{--                       required>--}}
{{--                @error('telephone1')--}}
{{--                <span class="error-message">{{ $message }}</span>--}}
{{--                @enderror--}}
{{--            </div>--}}

{{--            <div class="form-group">--}}
{{--                <label>Telephone 2</label>--}}
{{--                <input type="text"--}}
{{--                       name="telephone2"--}}
{{--                       value="{{ old('telephone2', $itemToEdit->telephone2 ?? '') }}"--}}
{{--                       placeholder="Ex: 07xxxxxxxxxx"--}}
{{--                       class="{{ $errors->has('telephone2') ? 'error' : '' }}">--}}
{{--                @error('telephone2')--}}
{{--                <span class="error-message">{{ $message }}</span>--}}
{{--                @enderror--}}
{{--            </div>--}}

{{--            <div class="form-group">--}}
{{--                <label>Telephone 3</label>--}}
{{--                <input type="tel"--}}
{{--                       name="telephone3"--}}
{{--                       value="{{ old('telephone3', $itemToEdit->telephone3 ?? '') }}"--}}
{{--                       placeholder="Ex: 07xxxxxxxxxx"--}}
{{--                       class="{{ $errors->has('telephone3') ? 'error' : '' }}">--}}
{{--                @error('telephone3')--}}
{{--                <span class="error-message">{{ $message }}</span>--}}
{{--                @enderror--}}
{{--            </div>--}}
{{--        </div>--}}

{{--        <div class="form-row">--}}
{{--            <div class="form-group">--}}
{{--                <label>Email <span class="required">*</span></label>--}}
{{--                <input type="email"--}}
{{--                       name="email1"--}}
{{--                       value="{{ old('email1', $itemToEdit->email1 ?? '') }}"--}}
{{--                       placeholder="Ex: johndoe@yahoo.fr"--}}
{{--                       class="{{ $errors->has('email1') ? 'error' : '' }}"--}}
{{--                       required>--}}
{{--                @error('email1')--}}
{{--                <span class="error-message">{{ $message }}</span>--}}
{{--                @enderror--}}
{{--            </div>--}}

{{--            <div class="form-group">--}}
{{--                <label>Email 2</label>--}}
{{--                <input type="email"--}}
{{--                       name="email2"--}}
{{--                       value="{{ old('email2', $itemToEdit->email2 ?? '') }}"--}}
{{--                       placeholder="Ex: johndoe@yahoo.fr"--}}
{{--                       class="{{ $errors->has('email2') ? 'error' : '' }}">--}}
{{--                @error('email2')--}}
{{--                <span class="error-message">{{ $message }}</span>--}}
{{--                @enderror--}}
{{--            </div>--}}

{{--            <div class="form-group">--}}
{{--                <label>Email 3</label>--}}
{{--                <input type="email"--}}
{{--                       name="email3"--}}
{{--                       value="{{ old('email3', $itemToEdit->email3 ?? '') }}"--}}
{{--                       placeholder="Ex: johndoe@yahoo.fr"--}}
{{--                       class="{{ $errors->has('email3') ? 'error' : '' }}">--}}
{{--                @error('email3')--}}
{{--                <span class="error-message">{{ $message }}</span>--}}
{{--                @enderror--}}
{{--            </div>--}}
{{--        </div>--}}

{{--        <div class="form-group">--}}
{{--            <label>Logo</label>--}}

{{--            @if($editMode && $itemToEdit && $itemToEdit->logo && !old('remove_logo'))--}}
{{--                <div class="mb-2">--}}
{{--                    <img src="{{ asset($itemToEdit->logo) }}" alt="Logo actuel" style="max-width: 100px; max-height: 100px;">--}}
{{--                    <div class="form-check mt-2">--}}
{{--                        <input class="form-check-input" type="checkbox" name="remove_logo" id="remove_logo" value="1"--}}
{{--                                {{ old('remove_logo') ? 'checked' : '' }}>--}}
{{--                        <label class="form-check-label" for="remove_logo">--}}
{{--                            Supprimer le logo actuel--}}
{{--                        </label>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            @endif--}}

{{--            <div class="upload-area" onclick="document.getElementById('campus-fileInput').click()">--}}
{{--                <div class="upload-icon">☁️</div>--}}
{{--                <div class="upload-text">--}}
{{--                    {{ $editMode && $itemToEdit && $itemToEdit->logo ? 'Cliquez pour changer le logo' : 'Cliquez pour ajouter un fichier (PDF, PNG, JPEG...)' }}--}}
{{--                </div>--}}
{{--            </div>--}}

{{--            <input type="file" id="campus-fileInput" name="logo" accept=".pdf,.png,.jpg,.jpeg" style="display: none;"--}}
{{--                   onchange="updateFileName(this)">--}}

{{--            <div id="file-name" class="mt-2 text-success" style="display: none;"></div>--}}

{{--            @error('logo')--}}
{{--            <span class="error-message">{{ $message }}</span>--}}
{{--            @enderror--}}
{{--        </div>--}}

{{--        <div class="form-group">--}}
{{--            <label>Description / Notes</label>--}}
{{--            <textarea name="description" placeholder="Détails supplémentaires" rows="3"--}}
{{--                      class="{{ $errors->has('description') ? 'error' : '' }}">{{ old('description', $itemToEdit->description ?? '') }}</textarea>--}}
{{--            @error('description')--}}
{{--            <span class="error-message">{{ $message }}</span>--}}
{{--            @enderror--}}
{{--        </div>--}}

{{--        <div class="form-actions">--}}
{{--            <button type="submit" class="btn btn-primary">--}}
{{--                <i class="fas fa-save"></i> {{ $editMode ? 'Modifier le Campus' : 'Enregistrer le Campus' }}--}}
{{--            </button>--}}

{{--            <button type="button" class="btn btn-secondary" onclick="resetForm()">--}}
{{--                <i class="fas fa-redo"></i> Réinitialiser--}}
{{--            </button>--}}

{{--            @if($editMode)--}}
{{--                <a href="{{ route('admin.hebergement.parc-immobilier.create', ['type' => 'campus']) }}" class="btn btn-outline-secondary">--}}
{{--                    <i class="fas fa-plus"></i> Nouveau--}}
{{--                </a>--}}
{{--            @endif--}}
{{--        </div>--}}
{{--    </form>--}}
{{--</div>--}}

{{--<script>--}}
{{--    function updateFileName(input) {--}}
{{--        let fileNameDiv = document.getElementById('file-name');--}}
{{--        if (input.files && input.files[0]) {--}}
{{--            fileNameDiv.style.display = 'block';--}}
{{--            fileNameDiv.innerHTML = '<i class="fas fa-check-circle"></i> Fichier sélectionné : ' + input.files[0].name;--}}
{{--        } else {--}}
{{--            fileNameDiv.style.display = 'none';--}}
{{--        }--}}
{{--    }--}}

{{--    function resetForm() {--}}
{{--        if (confirm('Voulez-vous réinitialiser le formulaire ?')) {--}}
{{--            document.getElementById('form-campus').reset();--}}
{{--            document.getElementById('file-name').style.display = 'none';--}}
{{--        }--}}
{{--    }--}}

{{--    // NOUVELLE FONCTION: Recherche d'adresse à partir du formulaire--}}
{{--    function searchAddressFromForm() {--}}
{{--        // Récupérer les valeurs du formulaire--}}
{{--        const regionSelect = document.getElementById('region_id');--}}
{{--        const villeSelect = document.getElementById('ville_id');--}}
{{--        const adresseInput = document.getElementById('adresse');--}}

{{--        const regionName = regionSelect.options[regionSelect.selectedIndex]?.text || '';--}}
{{--        const villeName = villeSelect.options[villeSelect.selectedIndex]?.text || '';--}}
{{--        const adresse = adresseInput.value.trim();--}}

{{--        // Construire l'adresse complète--}}
{{--        let fullAddress = '';--}}

{{--        if (adresse) {--}}
{{--            fullAddress += adresse;--}}
{{--        }--}}

{{--        if (villeName && villeName !== 'selectionnez une ville') {--}}
{{--            fullAddress += fullAddress ? ', ' + villeName : villeName;--}}
{{--        }--}}

{{--        if (regionName && regionName !== 'selectionnez une region') {--}}
{{--            fullAddress += fullAddress ? ', ' + regionName : regionName;--}}
{{--        }--}}

{{--        // Ajouter le pays par défaut (vous pouvez le modifier selon votre pays)--}}
{{--        fullAddress += fullAddress ? ', Côte d\'Ivoire' : 'Côte d\'Ivoire';--}}

{{--        if (!adresse && (!villeName || villeName === 'selectionnez une ville')) {--}}
{{--            showNotification('Veuillez renseigner au moins l\'adresse géographique ou la ville.', 'error');--}}
{{--            return;--}}
{{--        }--}}

{{--        // Afficher un indicateur de chargement--}}
{{--        const latInput = document.getElementById('latitude');--}}
{{--        const lonInput = document.getElementById('longitude');--}}
{{--        const originalLat = latInput.value;--}}
{{--        const originalLon = lonInput.value;--}}

{{--        latInput.value = 'Recherche...';--}}
{{--        lonInput.value = 'Recherche...';--}}

{{--        // Utiliser l'API Nominatim d'OpenStreetMap--}}
{{--        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(fullAddress)}&limit=1&countrycodes=ci`;--}}

{{--        fetch(url, {--}}
{{--            headers: {--}}
{{--                'User-Agent': 'CampusManagement/1.0'--}}
{{--            }--}}
{{--        })--}}
{{--            .then(response => response.json())--}}
{{--            .then(data => {--}}
{{--                if (data && data.length > 0) {--}}
{{--                    latInput.value = parseFloat(data[0].lat).toFixed(6);--}}
{{--                    lonInput.value = parseFloat(data[0].lon).toFixed(6);--}}
{{--                    showNotification(`Coordonnées trouvées avec succès pour : ${fullAddress.substring(0, 100)}`, 'success');--}}
{{--                } else {--}}
{{--                    latInput.value = originalLat;--}}
{{--                    lonInput.value = originalLon;--}}
{{--                    showNotification('Adresse non trouvée. Veuillez vérifier les informations saisies.', 'error');--}}
{{--                }--}}
{{--            })--}}
{{--            .catch(error => {--}}
{{--                latInput.value = originalLat;--}}
{{--                lonInput.value = originalLon;--}}
{{--                console.error('Erreur:', error);--}}
{{--                showNotification('Erreur lors de la recherche d\'adresse. Vérifiez votre connexion internet.', 'error');--}}
{{--            });--}}
{{--    }--}}

{{--    // Fonction pour rechercher par adresse personnalisée (gardée pour compatibilité)--}}
{{--    function searchAddress() {--}}
{{--        const address = document.getElementById('search_address')?.value;--}}
{{--        if (!address || !address.trim()) {--}}
{{--            showNotification('Veuillez entrer une adresse à rechercher.', 'error');--}}
{{--            return;--}}
{{--        }--}}

{{--        const latInput = document.getElementById('latitude');--}}
{{--        const lonInput = document.getElementById('longitude');--}}
{{--        const originalLat = latInput.value;--}}
{{--        const originalLon = lonInput.value;--}}

{{--        latInput.value = 'Recherche...';--}}
{{--        lonInput.value = 'Recherche...';--}}

{{--        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`;--}}

{{--        fetch(url, {--}}
{{--            headers: {--}}
{{--                'User-Agent': 'CampusManagement/1.0'--}}
{{--            }--}}
{{--        })--}}
{{--            .then(response => response.json())--}}
{{--            .then(data => {--}}
{{--                if (data && data.length > 0) {--}}
{{--                    latInput.value = data[0].lat;--}}
{{--                    lonInput.value = data[0].lon;--}}
{{--                    showNotification('Coordonnées trouvées avec succès !', 'success');--}}
{{--                } else {--}}
{{--                    latInput.value = originalLat;--}}
{{--                    lonInput.value = originalLon;--}}
{{--                    showNotification('Adresse non trouvée. Veuillez préciser votre recherche.', 'error');--}}
{{--                }--}}
{{--            })--}}
{{--            .catch(error => {--}}
{{--                latInput.value = originalLat;--}}
{{--                lonInput.value = originalLon;--}}
{{--                console.error('Erreur:', error);--}}
{{--                showNotification('Erreur lors de la recherche d\'adresse.', 'error');--}}
{{--            });--}}
{{--    }--}}

{{--    // Fonction pour la géolocalisation automatique--}}
{{--    function getCurrentLocation() {--}}
{{--        if (navigator.geolocation) {--}}
{{--            const latInput = document.getElementById('latitude');--}}
{{--            const lonInput = document.getElementById('longitude');--}}
{{--            const originalLat = latInput.value;--}}
{{--            const originalLon = lonInput.value;--}}

{{--            latInput.value = 'Chargement...';--}}
{{--            lonInput.value = 'Chargement...';--}}

{{--            navigator.geolocation.getCurrentPosition(--}}
{{--                function(position) {--}}
{{--                    latInput.value = position.coords.latitude.toFixed(6);--}}
{{--                    lonInput.value = position.coords.longitude.toFixed(6);--}}
{{--                    showNotification('Position récupérée avec succès !', 'success');--}}
{{--                },--}}
{{--                function(error) {--}}
{{--                    latInput.value = originalLat;--}}
{{--                    lonInput.value = originalLon;--}}

{{--                    let errorMessage = '';--}}
{{--                    switch(error.code) {--}}
{{--                        case error.PERMISSION_DENIED:--}}
{{--                            errorMessage = 'Permission refusée. Veuillez autoriser la géolocalisation.';--}}
{{--                            break;--}}
{{--                        case error.POSITION_UNAVAILABLE:--}}
{{--                            errorMessage = 'Position non disponible.';--}}
{{--                            break;--}}
{{--                        case error.TIMEOUT:--}}
{{--                            errorMessage = 'Délai dépassé.';--}}
{{--                            break;--}}
{{--                        default:--}}
{{--                            errorMessage = 'Erreur de géolocalisation.';--}}
{{--                    }--}}
{{--                    showNotification(errorMessage, 'error');--}}
{{--                },--}}
{{--                {--}}
{{--                    enableHighAccuracy: true,--}}
{{--                    timeout: 10000,--}}
{{--                    maximumAge: 0--}}
{{--                }--}}
{{--            );--}}
{{--        } else {--}}
{{--            showNotification('La géolocalisation n\'est pas supportée par votre navigateur.', 'error');--}}
{{--        }--}}
{{--    }--}}

{{--    function showNotification(message, type) {--}}
{{--        // Supprimer les notifications existantes--}}
{{--        const existingNotifications = document.querySelectorAll('.custom-notification');--}}
{{--        existingNotifications.forEach(notif => notif.remove());--}}

{{--        // Créer une notification temporaire--}}
{{--        const notification = document.createElement('div');--}}
{{--        notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show custom-notification`;--}}
{{--        notification.style.position = 'fixed';--}}
{{--        notification.style.top = '20px';--}}
{{--        notification.style.right = '20px';--}}
{{--        notification.style.zIndex = '9999';--}}
{{--        notification.style.minWidth = '300px';--}}
{{--        notification.innerHTML = `--}}
{{--            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} me-2"></i>--}}
{{--            ${message}--}}
{{--            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>--}}
{{--        `;--}}

{{--        document.body.appendChild(notification);--}}

{{--        // Auto-fermeture après 4 secondes--}}
{{--        setTimeout(() => {--}}
{{--            if (notification && notification.remove) {--}}
{{--                notification.remove();--}}
{{--            }--}}
{{--        }, 4000);--}}
{{--    }--}}

{{--    // Initialisation au chargement de la page--}}
{{--    document.addEventListener('DOMContentLoaded', function() {--}}
{{--        // Si une région est pré-sélectionnée, charger ses villes--}}
{{--        let regionSelect = document.getElementById('region_id');--}}
{{--        if (regionSelect && regionSelect.value) {--}}
{{--            loadCities(regionSelect.value);--}}
{{--        }--}}

{{--        // Ajouter un écouteur pour le changement de région--}}
{{--        if (regionSelect) {--}}
{{--            regionSelect.addEventListener('change', function() {--}}
{{--                loadCities(this.value);--}}
{{--            });--}}
{{--        }--}}
{{--    });--}}
{{--</script>--}}



<div id="campus-form" class="form-container"
     style="{{ $selectedType === 'campus' ? 'display: block;' : 'display: none;' }}">

    <h4 class="card-title mb-4">{{ $editMode ? 'Modifier le Campus' : 'Formulaire Nouveau Campus' }}</h4>

    @if(request()->has('errors'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <ul class="mb-0">
                {{ session('errors') }}
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form id="form-campus"
          action="{{ $editMode ? route('admin.hebergement.parc-immobilier.updateCampus') : route('admin.hebergement.parc-immobilier.storeCampus') }}"
          method="POST"
          enctype="multipart/form-data">

        @csrf
        @if($editMode)
            @method('PUT')
            <input type="hidden" name="code" value="{{ $itemToEdit->code ?? '' }}">
        @endif

        <input type="hidden" name="type" value="campus">

        <div class="form-row">
            <div class="form-group">
                <label>Nom du Campus <span class="required">*</span></label>
                <input type="text"
                       name="name"
                       value="{{ old('name', $itemToEdit->name ?? '') }}"
                       placeholder="Ex: Campus de Bondoukou"
                       class="{{ $errors->has('name') ? 'error' : '' }}"
                       required>
                @error('name')
                <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label>Code Campus</label>
                <input type="text"
                       name="code_campus"
                       value="{{ old('code_campus', $itemToEdit->code_campus ?? '') }}"
                       placeholder="Ex: Camp-012"
                       class="{{ $errors->has('code_campus') ? 'error' : '' }}">
                @error('code_campus')
                <span class="error-message">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="form-row">

            <div class="form-group">
                <label>Region <span class="required">*</span></label>
                <select name="region_id" class="form-select" id="region_id" required
                        onchange="getRequest('{{ url('/') }}/admin/list/city?parent_id='+this.value,'ville_id','select',this.value)">
                    <option value="">selectionnez une region</option>
                    @foreach($regions as $item)
                        <option value="{{$item->id}}" {{ old('region_id', $itemToEdit->region_id ?? '') == $item->id ? 'selected' : '' }}>{{$item->name}}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Ville <span class="required">*</span></label>
                <select name="ville_id" class="form-select" id="ville_id" required>
                    <option value="">selectionnez une ville</option>
                    @foreach($villes ?? [] as $ville)
                        <option value="{{$ville->id}}" {{ old('ville_id', $itemToEdit->ville_id) == $ville->id ? 'selected' : '' }}>{{$ville->name}}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Adresse Géographique <span class="required">*</span></label>
                <input type="text"
                       name="adresse"
                       id="adresse"
                       value="{{ old('adresse', $itemToEdit->adresse ?? '') }}"
                       placeholder="Ex: Rue 123, Quartier Centre"
                       class="{{ $errors->has('adresse') ? 'error' : '' }}"
                       required>
                @error('adresse')
                <span class="error-message">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <!-- Ligne pour la longitude et latitude avec carte interactive -->
        <div class="form-row">
            <div class="form-group">
                <label>Latitude</label>
                <input type="text"
                       name="latitude"
                       id="latitude"
                       value="{{ old('latitude', $itemToEdit->latitude ?? '') }}"
                       placeholder="Ex: 8.034670"
                       class="{{ $errors->has('latitude') ? 'error' : '' }}">
                @error('latitude')
                <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label>Longitude</label>
                <input type="text"
                       name="longitude"
                       id="longitude"
                       value="{{ old('longitude', $itemToEdit->longitude ?? '') }}"
                       placeholder="Ex: -2.796940"
                       class="{{ $errors->has('longitude') ? 'error' : '' }}">
                @error('longitude')
                <span class="error-message">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <!-- Option avec carte interactive -->
        <div class="form-row" style="margin-bottom: 15px;">
            <div class="form-group" style="flex: 1;">
                <label>Sélectionner la position sur la carte</label>
                <button type="button" class="btn btn-primary" onclick="openMapModal()" style="width: 100%;">
                    <i class="fas fa-map-marked-alt"></i> Ouvrir la carte pour sélectionner la position
                </button>
                <small class="form-text text-muted">Cliquez sur la carte pour positionner précisément le marqueur</small>
            </div>

            <div class="form-group" style="flex: 1;">
                <label>Ou rechercher par adresse</label>
                <button type="button" class="btn btn-info" onclick="searchAddressFromForm()" style="width: 100%;">
                    <i class="fas fa-map-pin"></i> Obtenir les coordonnées GPS
                </button>
                <small class="form-text text-muted">Recherche automatique à partir de l'adresse</small>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Telephone <span class="required">*</span></label>
                <input type="text"
                       name="telephone1"
                       value="{{ old('telephone1', $itemToEdit->telephone1 ?? '') }}"
                       placeholder="Ex: 07xxxxxxxxxx"
                       class="{{ $errors->has('telephone1') ? 'error' : '' }}"
                       required>
                @error('telephone1')
                <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label>Telephone 2</label>
                <input type="text"
                       name="telephone2"
                       value="{{ old('telephone2', $itemToEdit->telephone2 ?? '') }}"
                       placeholder="Ex: 07xxxxxxxxxx"
                       class="{{ $errors->has('telephone2') ? 'error' : '' }}">
                @error('telephone2')
                <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label>Telephone 3</label>
                <input type="tel"
                       name="telephone3"
                       value="{{ old('telephone3', $itemToEdit->telephone3 ?? '') }}"
                       placeholder="Ex: 07xxxxxxxxxx"
                       class="{{ $errors->has('telephone3') ? 'error' : '' }}">
                @error('telephone3')
                <span class="error-message">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Email <span class="required">*</span></label>
                <input type="email"
                       name="email1"
                       value="{{ old('email1', $itemToEdit->email1 ?? '') }}"
                       placeholder="Ex: johndoe@yahoo.fr"
                       class="{{ $errors->has('email1') ? 'error' : '' }}"
                       required>
                @error('email1')
                <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label>Email 2</label>
                <input type="email"
                       name="email2"
                       value="{{ old('email2', $itemToEdit->email2 ?? '') }}"
                       placeholder="Ex: johndoe@yahoo.fr"
                       class="{{ $errors->has('email2') ? 'error' : '' }}">
                @error('email2')
                <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label>Email 3</label>
                <input type="email"
                       name="email3"
                       value="{{ old('email3', $itemToEdit->email3 ?? '') }}"
                       placeholder="Ex: johndoe@yahoo.fr"
                       class="{{ $errors->has('email3') ? 'error' : '' }}">
                @error('email3')
                <span class="error-message">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="form-group">
            <label>Logo</label>

            @if($editMode && $itemToEdit && $itemToEdit->logo && !old('remove_logo'))
                <div class="mb-2">
                    <img src="{{ asset($itemToEdit->logo) }}" alt="Logo actuel" style="max-width: 100px; max-height: 100px;">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="remove_logo" id="remove_logo" value="1"
                                {{ old('remove_logo') ? 'checked' : '' }}>
                        <label class="form-check-label" for="remove_logo">
                            Supprimer le logo actuel
                        </label>
                    </div>
                </div>
            @endif

            <div class="upload-area" onclick="document.getElementById('campus-fileInput').click()">
                <div class="upload-icon">☁️</div>
                <div class="upload-text">
                    {{ $editMode && $itemToEdit && $itemToEdit->logo ? 'Cliquez pour changer le logo' : 'Cliquez pour ajouter un fichier (PDF, PNG, JPEG...)' }}
                </div>
            </div>

            <input type="file" id="campus-fileInput" name="logo" accept=".pdf,.png,.jpg,.jpeg" style="display: none;"
                   onchange="updateFileName(this)">

            <div id="file-name" class="mt-2 text-success" style="display: none;"></div>

            @error('logo')
            <span class="error-message">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label>Description / Notes</label>
            <textarea name="description" placeholder="Détails supplémentaires" rows="3"
                      class="{{ $errors->has('description') ? 'error' : '' }}">{{ old('description', $itemToEdit->description ?? '') }}</textarea>
            @error('description')
            <span class="error-message">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> {{ $editMode ? 'Modifier le Campus' : 'Enregistrer le Campus' }}
            </button>

            <button type="button" class="btn btn-secondary" onclick="resetForm()">
                <i class="fas fa-redo"></i> Réinitialiser
            </button>

            @if($editMode)
                @adminroute('admin.hebergement.parc-immobilier.create')
                <a href="{{ route('admin.hebergement.parc-immobilier.create', ['type' => 'campus']) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-plus"></i> Nouveau
                </a>
                @endadminroute

            @endif
        </div>
    </form>
</div>

<!-- Modal pour la carte interactive -->
<div class="modal fade" id="mapModal" tabindex="-1" data-bs-backdrop="false" aria-labelledby="mapModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mapModalLabel">
                    <i class="fas fa-map-marked-alt"></i> Sélectionner la position sur la carte
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Latitude</label>
                        <input type="text" id="modal-latitude" class="form-control" readonly placeholder="Cliquez sur la carte">
                    </div>
                    <div class="col-md-6">
                        <label>Longitude</label>
                        <input type="text" id="modal-longitude" class="form-control" readonly placeholder="Cliquez sur la carte">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-8">
                        <div class="input-group">
                            <input type="text" id="modal-search-address" class="form-control" placeholder="Rechercher une adresse...">
                            <button class="btn btn-primary" type="button" onclick="searchAddressInModal()">
                                <i class="fas fa-search"></i> Rechercher
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-success w-100" type="button" onclick="getCurrentLocationInModal()">
                            <i class="fas fa-location-dot"></i> Ma position
                        </button>
                    </div>
                </div>
                <div id="select-map" style="height: 500px; width: 100%; border-radius: 8px;"></div>
                <small class="text-muted mt-2 d-block">💡 Astuce : Cliquez directement sur la carte pour placer le marqueur, ou glissez-déposez le marqueur pour ajuster</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="confirmPosition()">
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
    .leaflet-popup-content {
        font-family: 'Segoe UI', sans-serif;
    }


    .custom-marker {
        background: transparent;
    }

    @keyframes markerPulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.3); }
        100% { transform: scale(1); }
    }
    #mapModal {
        z-index: 1055 !important;
    }

    #mapModal .modal-dialog {
        z-index: 1056 !important;
    }

    /* Les contrôles Leaflet doivent être au-dessus */
    #select-map .leaflet-control-container {
        z-index: 1060 !important;
    }

    #select-map .leaflet-pane {
        z-index: 400;
    }
    .marker-animation {
        animation: markerPulse 0.3s ease;
    }

    #select-map {
        cursor: crosshair;
    }
</style>

<script>
    let mapModal = null;
    let selectMap = null;
    let selectMarker = null;
    let isMapInitialized = false;

    function updateFileName(input) {
        let fileNameDiv = document.getElementById('file-name');
        if (input.files && input.files[0]) {
            fileNameDiv.style.display = 'block';
            fileNameDiv.innerHTML = '<i class="fas fa-check-circle"></i> Fichier sélectionné : ' + input.files[0].name;
        } else {
            fileNameDiv.style.display = 'none';
        }
    }

    function resetForm() {
        if (confirm('Voulez-vous réinitialiser le formulaire ?')) {
            document.getElementById('form-campus').reset();
            document.getElementById('file-name').style.display = 'none';
        }
    }

    // Ouvrir le modal avec la carte
    function openMapModal() {
        const latInput = document.getElementById('latitude');
        const lonInput = document.getElementById('longitude');

        let initialLat = parseFloat(latInput.value);
        let initialLng = parseFloat(lonInput.value);

        // Valeurs par défaut (Côte d'Ivoire)
        if (isNaN(initialLat) || initialLat === 0) initialLat = 6.8276;
        if (isNaN(initialLng) || initialLng === 0) initialLng = -5.2893;

        // Afficher le modal
        if (!mapModal) {
            mapModal = new bootstrap.Modal(document.getElementById('mapModal'));
        }

        mapModal.show();

        // Initialiser la carte après l'ouverture du modal
        setTimeout(() => {
            initMapInModal(initialLat, initialLng);
        }, 500);
    }

    function initMapInModal(lat, lng) {
        const mapContainer = document.getElementById('select-map');

        // Détruire la carte existante si elle existe
        if (selectMap) {
            selectMap.remove();
            selectMap = null;
        }

        // Créer une nouvelle carte
        selectMap = L.map('select-map', {
            preferCanvas: true  // ← améliore les performances dans un modal
        }).setView([lat, lng], 18);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 22,
            minZoom: 2
        }).addTo(selectMap);

        // Icône personnalisée pour le marqueur
        const customIcon = L.divIcon({
            html: '<div style="background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 16px; border: 3px solid white; box-shadow: 0 4px 8px rgba(0,0,0,0.2);"><i class="fas fa-map-marker-alt"></i></div>',
            iconSize: [32, 32],
            iconAnchor: [16, 32],
            popupAnchor: [0, -32],
            className: ''
        });

        // Ajouter le marqueur
        selectMarker = L.marker([lat, lng], {
            icon: customIcon,
            draggable: true
        }).addTo(selectMap);

        // Événement de fin de drag
        selectMarker.on('dragend', function(e) {
            const pos = selectMarker.getLatLng();
            document.getElementById('modal-latitude').value = pos.lat.toFixed(6);
            document.getElementById('modal-longitude').value = pos.lng.toFixed(6);
        });

        // Événement de clic sur la carte
        selectMap.on('click', function(e) {
            selectMarker.setLatLng(e.latlng);
            document.getElementById('modal-latitude').value = e.latlng.lat.toFixed(6);
            document.getElementById('modal-longitude').value = e.latlng.lng.toFixed(6);

            // Animation
            const markerElement = selectMarker.getElement();
            if (markerElement) {
                markerElement.classList.add('marker-animation');
                setTimeout(() => markerElement.classList.remove('marker-animation'), 300);
            }
        });

        // Mettre à jour les champs initiaux
        document.getElementById('modal-latitude').value = lat.toFixed(6);
        document.getElementById('modal-longitude').value = lng.toFixed(6);

        // Forcer le redimensionnement de la carte
        setTimeout(() => {
            selectMap.invalidateSize();
        }, 100);
    }

    // Rechercher une adresse dans le modal
    function searchAddressInModal() {
        const address = document.getElementById('modal-search-address').value;
        if (!address || !address.trim()) {
            showNotification('Veuillez entrer une adresse à rechercher.', 'error');
            return;
        }

        showNotification('Recherche en cours...', 'info');

        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`;

        fetch(url, {
            headers: { 'User-Agent': 'CampusManagement/1.0' }
        })
            .then(response => response.json())
            .then(data => {
                if (data && data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lng = parseFloat(data[0].lon);
                    selectMap.setView([lat, lng], 18);
                    selectMarker.setLatLng([lat, lng]);
                    document.getElementById('modal-latitude').value = lat.toFixed(6);
                    document.getElementById('modal-longitude').value = lng.toFixed(6);
                    showNotification('Adresse trouvée !', 'success');
                } else {
                    showNotification('Adresse non trouvée. Veuillez préciser votre recherche.', 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur lors de la recherche.', 'error');
            });
    }

    // Obtenir la position actuelle
    function getCurrentLocationInModal() {
        if (!navigator.geolocation) {
            showNotification('La géolocalisation n\'est pas supportée.', 'error');
            return;
        }

        showNotification('Recherche de votre position...', 'info');

        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                selectMap.setView([lat, lng], 18);
                selectMarker.setLatLng([lat, lng]);
                document.getElementById('modal-latitude').value = lat.toFixed(6);
                document.getElementById('modal-longitude').value = lng.toFixed(6);
                showNotification('Position récupérée avec succès !', 'success');
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
                showNotification(errorMessage, 'error');
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    }

    // Confirmer la position
    function confirmPosition() {
        const lat = document.getElementById('modal-latitude').value;
        const lng = document.getElementById('modal-longitude').value;

        if (lat && lng && lat !== '' && lng !== '') {
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
            showNotification('Position enregistrée avec succès !', 'success');
            mapModal.hide();
        } else {
            showNotification('Veuillez sélectionner une position sur la carte.', 'error');
        }
    }

    // Recherche d'adresse à partir du formulaire
    function searchAddressFromForm() {
        const regionSelect = document.getElementById('region_id');
        const villeSelect = document.getElementById('ville_id');
        const adresseInput = document.getElementById('adresse');

        const regionName = regionSelect.options[regionSelect.selectedIndex]?.text || '';
        const villeName = villeSelect.options[villeSelect.selectedIndex]?.text || '';
        const adresse = adresseInput.value.trim();

        let fullAddress = '';

        if (adresse) fullAddress += adresse;
        if (villeName && villeName !== 'selectionnez une ville') fullAddress += fullAddress ? ', ' + villeName : villeName;
        if (regionName && regionName !== 'selectionnez une region') fullAddress += fullAddress ? ', ' + regionName : regionName;
        fullAddress += fullAddress ? ', Côte d\'Ivoire' : 'Côte d\'Ivoire';

        if (!adresse && (!villeName || villeName === 'selectionnez une ville')) {
            showNotification('Veuillez renseigner au moins l\'adresse géographique ou la ville.', 'error');
            return;
        }

        const latInput = document.getElementById('latitude');
        const lonInput = document.getElementById('longitude');
        const originalLat = latInput.value;
        const originalLon = lonInput.value;

        latInput.value = 'Recherche...';
        lonInput.value = 'Recherche...';

        showNotification('Recherche des coordonnées...', 'info');

        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(fullAddress)}&limit=1&countrycodes=ci`;

        fetch(url, {
            headers: { 'User-Agent': 'CampusManagement/1.0' }
        })
            .then(response => response.json())
            .then(data => {
                if (data && data.length > 0) {
                    latInput.value = parseFloat(data[0].lat).toFixed(6);
                    lonInput.value = parseFloat(data[0].lon).toFixed(6);
                    showNotification('Coordonnées trouvées avec succès !', 'success');
                } else {
                    latInput.value = originalLat;
                    lonInput.value = originalLon;
                    showNotification('Adresse non trouvée. Vérifiez les informations.', 'error');
                }
            })
            .catch(error => {
                latInput.value = originalLat;
                lonInput.value = originalLon;
                console.error('Erreur:', error);
                showNotification('Erreur lors de la recherche.', 'error');
            });
    }

    // Notification
    function showNotification(message, type) {
        const existingNotifications = document.querySelectorAll('.custom-notification');
        existingNotifications.forEach(notif => notif.remove());

        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} alert-dismissible fade show custom-notification`;
        notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
        notification.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            if (notification && notification.remove) {
                notification.remove();
            }
        }, 4000);
    }

    // Initialisation
    document.addEventListener('DOMContentLoaded', function() {
        let regionSelect = document.getElementById('region_id');
        if (regionSelect && regionSelect.value) {
            if (typeof getRequest === 'function') {
                getRequest('{{ url('/') }}/admin/list/city?parent_id=' + regionSelect.value, 'ville_id', 'select', regionSelect.value);
            }
        }

        if (regionSelect) {
            regionSelect.addEventListener('change', function() {
                if (typeof getRequest === 'function') {
                    getRequest('{{ url('/') }}/admin/list/city?parent_id=' + this.value, 'ville_id', 'select', this.value);
                }
            });
        }

        // Nettoyer la carte quand le modal est fermé
        document.getElementById('mapModal')?.addEventListener('hidden.bs.modal', function() {
            if (selectMap) {
                selectMap.remove();
                selectMap = null;
            }
        });
    });
</script>