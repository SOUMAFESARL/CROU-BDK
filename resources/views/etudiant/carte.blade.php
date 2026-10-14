
<!-- Carte Interactive -->
<div id="page-carte-interactive" class="content">
    <div style="display: flex; flex-direction: column; height: calc(100vh - 80px);">
        <!-- Header de la carte -->
        <div style="background: linear-gradient(135deg, #3f8454 0%, #448861 100%); color: white; padding: 20px 24px; border-radius: 12px 12px 0 0;">
            <h1 style="font-size: 22px; font-weight: 700; margin-bottom: 8px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-map-location-dot"></i> Carte Interactive
            </h1>
            <p style="font-size: var(--app-font-min, 15px); opacity: 0.9; margin: 0;">Gestion Immobilière - Campus, Cités et Bâtiments</p>
        </div>

        <!-- Conteneur principal de la carte -->
        <div style="display: grid; grid-template-columns: 320px 1fr; gap: 20px; flex: 1; padding: 20px; background: #f5f5f5; overflow: hidden;">

            <!-- PANEL DE FILTRES (droite) -->
            <div style="background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow-y: auto; display: flex; flex-direction: column;">
                <div style="padding: 20px; border-bottom: 1px solid #e2e8f0;">
                    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-sliders-h"></i> Filtres
                    </h3>

                    <!-- Stats -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px;">
                        <div style="background: #f0f9ff; padding: 12px; border-radius: 10px; text-align: center;">
                            <div style="font-size: 20px; font-weight: 700; color: #2563eb;">{{ count($campuses) }}</div>
                            <div style="font-size: var(--app-font-min, 15px); color: #64748b;">Campus</div>
                        </div>
                        <div style="background: #f5f3ff; padding: 12px; border-radius: 10px; text-align: center;">
                            <div style="font-size: 20px; font-weight: 700; color: #7c3aed;">{{ count($cites) }}</div>
                            <div style="font-size: var(--app-font-min, 15px); color: #64748b;">Cités</div>
                        </div>
                        <div style="background: #f0fdf4; padding: 12px; border-radius: 10px; text-align: center;">
                            <div style="font-size: 20px; font-weight: 700; color: #059669;">{{ count($batiments) }}</div>
                            <div style="font-size: var(--app-font-min, 15px); color: #64748b;">Bâtiments</div>
                        </div>
                    </div>
                </div>

                <div style="padding: 20px;">
                    <!-- Filter: Campus -->
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: var(--app-font-min, 15px); font-weight: 600; color: #64748b; margin-bottom: 8px;">
                            <i class="fas fa-building"></i> Filtrer par Campus
                        </label>
                        <select id="filter-campus" onchange="handleCampusChange()" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: var(--app-font-min, 15px);">
                            <option value="">-- Tous les campus --</option>
                            @foreach($campuses as $campus)
                                <option value="{{ $campus->id_campus }}">{{ $campus->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter: Cité -->
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: var(--app-font-min, 15px); font-weight: 600; color: #64748b; margin-bottom: 8px;">
                            <i class="fas fa-building"></i> Filtrer par Cité
                        </label>
                        <select id="filter-cite" onchange="handleCiteChange()" disabled style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: var(--app-font-min, 15px); background: #f5f5f5;">
                            <option value="">-- Sélectionnez un campus --</option>
                        </select>
                    </div>

                    <!-- Filter: Bâtiment -->
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: var(--app-font-min, 15px); font-weight: 600; color: #64748b; margin-bottom: 8px;">
                            <i class="fas fa-building"></i> Filtrer par Bâtiment
                        </label>
                        <select id="filter-batiment" onchange="handleBatimentChange()" disabled style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: var(--app-font-min, 15px); background: #f5f5f5;">
                            <option value="">-- Sélectionnez une cité --</option>
                        </select>
                    </div>

                    <!-- Éléments visibles -->
                    <div style="margin-top: 20px;">
                        <label style="display: block; font-size: var(--app-font-min, 15px); font-weight: 600; color: #64748b; margin-bottom: 8px;">
                            <i class="fas fa-list"></i> Éléments visibles
                        </label>
                        <div id="info-list" style="background: #f8fafc; border-radius: 8px; padding: 12px; max-height: 250px; overflow-y: auto;">
                            <div style="text-align: center; padding: 20px; color: #94a3b8;">
                                <div style="font-size: 24px; margin-bottom: 8px;">📍</div>
                                <p style="font-size: var(--app-font-min, 15px);">Aucun marqueur</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer filtres -->
                <div style="padding: 16px 20px; border-top: 1px solid #e2e8f0; margin-top: auto;">
                    <button onclick="resetFilters()" style="width: 100%; padding: 10px; background: #2563eb; color: white; border: none; border-radius: 8px; font-size: var(--app-font-min, 15px); font-weight: 500; cursor: pointer;">
                        <i class="fas fa-redo"></i> Réinitialiser les filtres
                    </button>
                </div>
            </div>

            <!-- CONTENEUR DE LA CARTE -->
            <div style="background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden; position: relative;">
                <div id="map" style="width: 100%; height: 100%; min-height: 500px;"></div>

                <!-- Zoom Info -->
                <div style="position: absolute; top: 15px; right: 15px; background: white; padding: 8px 12px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); z-index: 400; font-size: var(--app-font-min, 15px);">
                    <span id="zoom-level">Zoom: 5</span>
                    <span style="margin: 0 6px;">•</span>
                    <span id="marker-count">0 marqueurs</span>
                </div>

                <!-- Légende -->
                <div style="position: absolute; bottom: 15px; right: 15px; background: white; padding: 12px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); z-index: 400;">
                    <div style="font-weight: 600; margin-bottom: 8px; font-size: var(--app-font-min, 15px);">Légende</div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 5px;">
                        <div style="width: 16px; height: 16px; border-radius: 50%; background: #2563eb;"></div>
                        <span style="font-size: var(--app-font-min, 15px);">Campus</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 5px;">
                        <div style="width: 16px; height: 16px; border-radius: 50%; background: #7c3aed;"></div>
                        <span style="font-size: var(--app-font-min, 15px);">Cité</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="width: 16px; height: 16px; border-radius: 50%; background: #059669;"></div>
                        <span style="font-size: var(--app-font-min, 15px);">Bâtiment</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />

<style>
    #page-carte-interactive {
        padding: 0 !important;
    }

    .leaflet-popup-content {
        font-family: 'Segoe UI', sans-serif !important;
    }
    .leaflet-popup-content-wrapper {
        border-radius: 12px !important;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2) !important;
    }

    .leaflet-popup-content {
        margin: 12px !important;
        min-width: 250px !important;
    }

    @keyframes pulse-blue {
        0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
        100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* Nouveaux styles pour les marqueurs améliorés */
    @keyframes marker-pulse {
        0% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.2); opacity: 0.8; }
        100% { transform: scale(1); opacity: 1; }
    }

    @keyframes marker-float {
        0% { transform: translateY(0px); }
        50% { transform: translateY(-5px); }
        100% { transform: translateY(0px); }
    }

    .custom-marker {
        transition: all 0.3s ease;
    }

    .custom-marker:hover {
        transform: scale(1.15);
        transition: transform 0.2s ease;
    }

    .marker-glow {
        animation: marker-pulse 2s infinite;
    }

    .leaflet-control-custom {
        background-color: white;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .leaflet-control-custom:hover {
        background-color: #f0f9ff;
        transform: scale(1.05);
    }

    /* Scrollbar personnalisée */
    #info-list::-webkit-scrollbar {
        width: 5px;
    }

    #info-list::-webkit-scrollbar-track {
        background: #e2e8f0;
        border-radius: 3px;
    }

    #info-list::-webkit-scrollbar-thumb {
        background: #94a3b8;
        border-radius: 3px;
    }
</style>

<!-- Leaflet JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

<script>
    // Attendre que le DOM soit chargé
    document.addEventListener('DOMContentLoaded', function() {
        // Détecter quand la page carte est activée
        const carteLink = document.querySelector('a[data-page="carte-interactive"]');
        if (carteLink) {
            carteLink.addEventListener('click', function() {
                setTimeout(function() {
                    if (document.getElementById('page-carte-interactive').classList.contains('active')) {
                        if (typeof map !== 'undefined' && map) {
                            setTimeout(function() {
                                map.invalidateSize();
                            }, 100);
                        } else {
                            initMap();
                        }
                    }
                }, 150);
            });
        }

        // Si la carte est déjà active au chargement
        if (document.getElementById('page-carte-interactive').classList.contains('active')) {
            initMap();
        }
    });

    // ========================
    // DATA INITIALIZATION
    // ========================

    @php
        $campusData = $campuses->map(function($c) {
            return [
                'id_campus' => $c->id_campus,
               'code_campus' => $c->code_campus,
                'name' => addslashes($c->name),
                'latitude' => (float)$c->latitude,
                'longitude' => (float)$c->longitude,
                'adresse' => addslashes($c->adresse ?? ''),
                'telephone1' => addslashes($c->telephone1 ?? ''),
                'telephone2' => addslashes($c->telephone2 ?? ''),
                'email1' => addslashes($c->email1 ?? ''),
                'description' => addslashes($c->description ?? ''),
            ];
        })->toArray();

        $citeData = $cites->map(function($c) {
            return [
                'id_cite' => $c->id_cite,
                'code_cite' => $c->code_cite,
               // 'code' => $c->code,
                'name' => addslashes($c->name),
                'campus_id' => $c->campus_id,
                'latitude' => (float)$c->latitude,
                'longitude' => (float)$c->longitude,
                'adresse' => addslashes($c->adresse ?? ''),
                'telephone1' => addslashes($c->telephone1 ?? ''),
                'email1' => addslashes($c->email1 ?? ''),
                'capacite_max' => (int)($c->capacite_max ?? 0),
                'type_cite_id' => addslashes($c->type_cite_id ?? ''),
                'type_cite_name' => addslashes($c->typeCite->name ?? ''),
                'wifi_gratuit' => (bool)$c->wifi_gratuit,
                'salle_etude' => (bool)$c->salle_etude,
                'infirmerie' => (bool)$c->infirmerie,
                'restaurant' => (bool)$c->restaurant,
            ];
        })->toArray();

        $batimentData = $batiments->map(function($b) {
            return [
                'id_batiment' => $b->id_batiment,
                'code_batiment' => $b->code_batiment,
                'name' => addslashes($b->name),
                'cite_id' => $b->cite_id,
                'campus_id' => $b->campus_id,
                'latitude' => (float)$b->latitude,
                'longitude' => (float)$b->longitude,
                'adresse' => addslashes($b->adresse ?? ''),
                'type_occupation' => addslashes($b->type_occupation ?? ''),
                'total_etages' => (int)($b->total_etages ?? 0),
                'total_chambres' => (int)($b->total_chambres ?? 0),
                'description' => addslashes($b->description ?? ''),
            ];
        })->toArray();
    @endphp

    const allData = {
        campus: @json($campusData),
        cite: @json($citeData),
        batiment: @json($batimentData)
    };

    let markers = {
        campus: [],
        cite: [],
        batiment: []
    };

    let map;
    let currentFilter = {
        campus: null,
        cite: null,
        batiment: null
    };

    let currentLocationMarker = null;
    let currentLocationCircle = null;

    // ========================
    // FONCTIONS DE GÉOLOCALISATION
    // ========================

    function showLocationLoadingIndicator() {
        const loadingDiv = document.createElement('div');
        loadingDiv.id = 'location-loading';
        loadingDiv.style.cssText = `
            position: absolute;
            bottom: 80px;
            right: 20px;
            background: white;
            padding: 8px 12px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            z-index: 400;
            font-size: var(--app-font-min, 15px);
            display: flex;
            align-items: center;
            gap: 8px;
        `;
        loadingDiv.innerHTML = `
            <div class="spinner" style="width: 16px; height: 16px; border: 2px solid #e2e8f0; border-top-color: #2563eb; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
            <span>Recherche de votre position...</span>
        `;
        const mapContainer = document.querySelector('#map');
        if (mapContainer) mapContainer.appendChild(loadingDiv);
    }

    function showGeolocationError(message) {
        const loadingIndicator = document.getElementById('location-loading');
        if (loadingIndicator) loadingIndicator.remove();

        const errorDiv = document.createElement('div');
        errorDiv.id = 'location-error';
        errorDiv.style.cssText = `
            position: absolute;
            bottom: 80px;
            right: 20px;
            background: #fee2e2;
            color: #dc2626;
            padding: 10px 15px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            z-index: 400;
            font-size: var(--app-font-min, 15px);
            max-width: 250px;
            border-left: 3px solid #dc2626;
        `;
        errorDiv.innerHTML = `<div style="display: flex; align-items: center; gap: 8px;"><span>⚠️</span><span>${message}</span></div>`;
        const mapContainer = document.querySelector('#map');
        if (mapContainer) mapContainer.appendChild(errorDiv);
        setTimeout(() => { if (errorDiv) errorDiv.remove(); }, 5000);
    }

    function displayCurrentLocation(latitude, longitude, accuracy) {
        const loadingIndicator = document.getElementById('location-loading');
        if (loadingIndicator) loadingIndicator.remove();

        if (currentLocationMarker) map.removeLayer(currentLocationMarker);
        if (currentLocationCircle) map.removeLayer(currentLocationCircle);

        const currentLocationIcon = L.divIcon({
            className: 'current-location-marker',
            html: `<div style="position: relative; width: 24px; height: 24px;"><div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 16px; height: 16px; background: #3b82f6; border: 3px solid white; border-radius: 50%; box-shadow: 0 0 0 3px rgba(59,130,246,0.3); animation: pulse-blue 2s infinite;"></div></div>`,
            iconSize: [24, 24],
            iconAnchor: [12, 12],
            popupAnchor: [0, -12]
        });

        currentLocationMarker = L.marker([latitude, longitude], { icon: currentLocationIcon }).addTo(map);
        currentLocationCircle = L.circle([latitude, longitude], { radius: accuracy, color: '#3b82f6', fillColor: '#3b82f6', fillOpacity: 0.1, weight: 2, opacity: 0.5 }).addTo(map);
        currentLocationMarker.bindPopup(`<div style="padding: 8px;"><div style="font-weight: 600;">📍 Votre position</div><div style="font-size: var(--app-font-min, 15px);">Précision: ±${Math.round(accuracy)}m</div></div>`, { maxWidth: 250 });
        currentLocationMarker.openPopup();
    }

    function getCurrentLocation() {
        if (!navigator.geolocation) {
            showGeolocationError("La géolocalisation n'est pas supportée");
            return;
        }
        showLocationLoadingIndicator();
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const { latitude, longitude, accuracy } = position.coords;
                displayCurrentLocation(latitude, longitude, accuracy);
                map.setView([latitude, longitude], 15);
            },
            (error) => {
                let errorMessage = error.code === error.PERMISSION_DENIED ? "Permission refusée" : "Erreur de géolocalisation";
                showGeolocationError(errorMessage);
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    }

    function addLocationControl() {
        const LocationControl = L.Control.extend({
            options: { position: 'topleft' },
            onAdd: function(map) {
                const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
                container.style.cssText = 'background:white;width:34px;height:34px;cursor:pointer;border-radius:4px;box-shadow:0 2px 6px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;font-size:20px;';
                container.innerHTML = '📍';
                container.title = 'Voir ma position';
                container.onclick = () => getCurrentLocation();
                return container;
            }
        });
        map.addControl(new LocationControl());
    }

    // ========================
    // FONCTIONS POUR CRÉER DES MARQUEURS AMÉLIORÉS
    // ========================

    function getSizeByZoom(baseSize) {
        const zoom = map.getZoom();
        if (zoom >= 18) return baseSize * 1.8;
        if (zoom >= 16) return baseSize * 1.5;
        if (zoom >= 14) return baseSize * 1.2;
        if (zoom >= 12) return baseSize * 1;
        return baseSize * 0.8;
    }

    function createCampusIcon() {
        const size = getSizeByZoom(32);
        return L.divIcon({
            className: 'custom-marker',
            html: `<div style="background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); width: ${size}px; height: ${size}px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: ${size * 0.4}px; font-weight: bold; border: 3px solid white; box-shadow: 0 4px 8px rgba(0,0,0,0.2), 0 0 0 2px rgba(37,99,235,0.3); transition: all 0.3s ease;">
                <i class="fas fa-university" style="font-size: ${size * 0.45}px;"></i>
            </div>`,
            iconSize: [size, size],
            iconAnchor: [size/2, size/2],
            popupAnchor: [0, -size/2]
        });
    }

    function createCiteIcon() {
        const size = getSizeByZoom(28);
        return L.divIcon({
            className: 'custom-marker',
            html: `<div style="background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%); width: ${size}px; height: ${size}px; border-radius: 12px 12px 12px 0; display: flex; align-items: center; justify-content: center; color: white; font-size: ${size * 0.4}px; font-weight: bold; border: 3px solid white; box-shadow: 0 4px 8px rgba(0,0,0,0.2), 0 0 0 2px rgba(124,58,237,0.3); transform: rotate(0deg); transition: all 0.3s ease;">
                <i class="fas fa-building" style="font-size: ${size * 0.45}px; transform: rotate(0deg);"></i>
            </div>`,
            iconSize: [size, size],
            iconAnchor: [size/2, size/2],
            popupAnchor: [0, -size/2]
        });
    }

    function createBatimentIcon() {
        const size = getSizeByZoom(24);
        return L.divIcon({
            className: 'custom-marker',
            html: `<div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); width: ${size}px; height: ${size}px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: ${size * 0.4}px; font-weight: bold; border: 3px solid white; box-shadow: 0 4px 8px rgba(0,0,0,0.2), 0 0 0 2px rgba(5,150,105,0.3); transition: all 0.3s ease;">
                <i class="fas fa-home" style="font-size: ${size * 0.45}px;"></i>
            </div>`,
            iconSize: [size, size],
            iconAnchor: [size/2, size/2],
            popupAnchor: [0, -size/2]
        });
    }

    // ========================
    // INIT MAP
    // ========================
    function initMap() {
        if (map) {
            map.remove();
        }

        // Configuration de la carte avec zoom maximum élevé
        map = L.map('map', {
            maxZoom: 22,  // Zoom très élevé pour voir les détails
            minZoom: 2,
            zoomSnap: 0.5,  // Zoom plus fluide
            zoomDelta: 0.5   // Incréments de zoom plus petits
        }).setView([6.8276, -5.2893], 15);  // Zoom initial à 15 pour plus de détails

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 22,  // Support du zoom maximum
            minZoom: 2
        }).addTo(map);

        map.on('zoomend', function() {
            const zoomLevel = document.getElementById('zoom-level');
            if (zoomLevel) zoomLevel.textContent = 'Zoom: ' + map.getZoom();

            // Mettre à jour les marqueurs pour qu'ils s'adaptent au zoom
            updateMarkersForZoom();
        });

        addLocationControl();
        displayAllCampus();
    }

    function updateMarkersForZoom() {
        // Re-créer tous les marqueurs avec les nouvelles tailles
        const currentCampusMarkers = markers.campus;
        const currentCiteMarkers = markers.cite;
        const currentBatimentMarkers = markers.batiment;

        // Sauvegarder les données des marqueurs
        const campusData = currentCampusMarkers.map(m => m.options.campusData);
        const citeData = currentCiteMarkers.map(m => m.options.citeData);
        const batimentData = currentBatimentMarkers.map(m => m.options.batimentData);

        // Supprimer les marqueurs actuels
        clearMarkers();

        // Recréer les marqueurs avec les nouvelles tailles
        campusData.forEach(campus => addCampusMarker(campus));
        citeData.forEach(cite => addCiteMarker(cite));
        batimentData.forEach(batiment => addBatimentMarker(batiment));
    }

    // ========================
    // FILTER HANDLERS
    // ========================
    function handleCampusChange() {
        const campus = document.getElementById('filter-campus').value;
        currentFilter.campus = campus;
        currentFilter.cite = null;
        currentFilter.batiment = null;

        document.getElementById('filter-cite').value = '';
        document.getElementById('filter-batiment').value = '';

        if (campus) {
            const select = document.getElementById('filter-cite');
            select.innerHTML = '<option value="">-- Toutes les cités --</option>';
            const cites = allData.cite.filter(c => c.campus_id === campus);
            cites.forEach(cite => {
                const option = document.createElement('option');
                option.value = cite.id_cite;
                option.textContent = `${cite.name}`;
                select.appendChild(option);
            });
            select.disabled = cites.length === 0;
            displayFilteredData();
        } else {
            document.getElementById('filter-cite').disabled = true;
            document.getElementById('filter-batiment').disabled = true;
            displayAllCampus();
        }
    }

    function handleCiteChange() {
        const cite = document.getElementById('filter-cite').value;
        currentFilter.cite = cite;
        currentFilter.batiment = null;
        document.getElementById('filter-batiment').value = '';

        if (cite) {
            const select = document.getElementById('filter-batiment');
            select.innerHTML = '<option value="">-- Tous les bâtiments --</option>';
            const batiments = allData.batiment.filter(b => b.cite_id === cite);
            batiments.forEach(bat => {
                const option = document.createElement('option');
                option.value = bat.id_batiment;
                option.textContent = `${bat.name}`;
                select.appendChild(option);
            });
            select.disabled = batiments.length === 0;
            displayFilteredData();
        } else {
            document.getElementById('filter-batiment').disabled = true;
            displayFilteredData();
        }
    }

    function handleBatimentChange() {
        const batiment = document.getElementById('filter-batiment').value;
        currentFilter.batiment = batiment;
        displayFilteredData();
    }

    function displayAllCampus() {
        clearMarkers();
        currentFilter = { campus: null, cite: null, batiment: null };
        allData.campus.forEach(campus => {
            if (campus.latitude && campus.longitude && campus.latitude !== 0) {
                addCampusMarker(campus);
            }
        });
        updateInfoList();
        updateMarkerCount();
        fitMapBounds();
    }

    function displayFilteredData() {
        clearMarkers();
        if (currentFilter.batiment) {
            const batiments = allData.batiment.filter(b => b.id_batiment === currentFilter.batiment);
            batiments.forEach(bat => addBatimentMarker(bat));
        } else if (currentFilter.cite) {
            const batiments = allData.batiment.filter(b => b.cite_id === currentFilter.cite);
            batiments.forEach(bat => addBatimentMarker(bat));
        } else if (currentFilter.campus) {
            const cites = allData.cite.filter(c => c.campus_id === currentFilter.campus);
            cites.forEach(cite => addCiteMarker(cite));
        } else {
            displayAllCampus();
            return;
        }
        updateInfoList();
        updateMarkerCount();
        fitMapBounds();
    }

    // ========================
    // ADD MARKERS AMÉLIORÉS
    // ========================
    function addCampusMarker(campus) {
        const marker = L.marker([campus.latitude, campus.longitude], {
            icon: createCampusIcon(),
            campusData: campus
        }).addTo(map);

        marker.bindPopup(`
            <div style="padding:12px;">
                <div style="font-weight:700;font-size:16px;margin-bottom:8px;">🏢 ${campus.name}</div>
                ${campus.id_campus ? `<div style="margin-bottom:4px;"><strong>Code:</strong> ${campus.id_campus}</div>` : ''}
                ${campus.adresse ? `<div style="margin-bottom:4px;"><strong>Adresse:</strong> ${campus.adresse}</div>` : ''}
            </div>
        `, { maxWidth: 350 });

        markers.campus.push(marker);
    }

    function addCiteMarker(cite) {
        const marker = L.marker([cite.latitude, cite.longitude], {
            icon: createCiteIcon(),
            citeData: cite
        }).addTo(map);

        marker.bindPopup(`
            <div style="padding:12px;">
                <div style="font-weight:700;font-size:16px;margin-bottom:8px;">🏠 ${cite.name}</div>
                ${cite.id_cite ? `<div style="margin-bottom:4px;"><strong>Code:</strong> ${cite.id_cite}</div>` : ''}
                ${cite.type_cite_name ? `<div style="margin-bottom:4px;"><strong>Type:</strong> ${cite.type_cite_name}</div>` : ''}
            </div>
        `, { maxWidth: 350 });

        markers.cite.push(marker);
    }

    function addBatimentMarker(batiment) {
        const marker = L.marker([batiment.latitude, batiment.longitude], {
            icon: createBatimentIcon(),
            batimentData: batiment
        }).addTo(map);

        marker.bindPopup(`
            <div style="padding:12px;">
                <div style="font-weight:700;font-size:16px;margin-bottom:8px;">🏗️ ${batiment.name}</div>
                ${batiment.type_occupation ? `<div style="margin-bottom:4px;"><strong>Type:</strong> ${batiment.type_occupation}</div>` : ''}
                ${batiment.total_chambres ? `<div style="margin-bottom:4px;"><strong>Chambres:</strong> ${batiment.total_chambres}</div>` : ''}
            </div>
        `, { maxWidth: 350 });

        markers.batiment.push(marker);
    }

    function updateInfoList() {
        const list = document.getElementById('info-list');
        if (!list) return;
        list.innerHTML = '';
        let items = [];

        if (currentFilter.batiment) {
            items = allData.batiment.filter(b => b.id_batiment === currentFilter.batiment).map(b => ({ icon: '🏗️', name: b.name, code: b.id_batiment }));
        } else if (currentFilter.cite) {
            items = allData.batiment.filter(b => b.id_cite === currentFilter.cite).map(b => ({ icon: '🏗️', name: b.name, code: b.id_batiment }));
        } else if (currentFilter.campus) {
            items = allData.cite.filter(c => c.campus_id === currentFilter.campus).map(c => ({ icon: '🏠', name: c.name, code: c.id_cite }));
        } else {
            items = allData.campus.filter(c => c.latitude).map(c => ({ icon: '🏢', name: c.name, code: c.id_campus }));
        }

        if (items.length === 0) {
            list.innerHTML = '<div style="text-align:center;padding:20px;color:#94a3b8;"><div>📍</div><p>Aucun marqueur</p></div>';
            return;
        }

        items.forEach(item => {
            const div = document.createElement('div');
            div.style.cssText = 'padding:8px;margin-bottom:6px;background:white;border-left:3px solid #2563eb;border-radius:4px;font-size:var(--app-font-min, 15px);';
            div.innerHTML = `<strong>${item.icon}</strong> ${item.name}<br><small style="color:#94a3b8;">${item.code}</small>`;
            list.appendChild(div);
        });
    }

    function clearMarkers() {
        [...markers.campus, ...markers.cite, ...markers.batiment].forEach(m => map.removeLayer(m));
        markers = { campus: [], cite: [], batiment: [] };
    }

    function fitMapBounds() {
        const allMarkers = [...markers.campus, ...markers.cite, ...markers.batiment];
        if (allMarkers.length === 0) {
            map.setView([6.8276, -5.2893], 15);
            return;
        }
        const group = new L.featureGroup(allMarkers);
        map.fitBounds(group.getBounds().pad(0.1), { maxZoom: 18 });
    }

    function updateMarkerCount() {
        const count = markers.campus.length + markers.cite.length + markers.batiment.length;
        const markerCountElem = document.getElementById('marker-count');
        if (markerCountElem) markerCountElem.textContent = `${count} marqueur${count > 1 ? 's' : ''}`;
    }

    function resetFilters() {
        document.getElementById('filter-campus').value = '';
        document.getElementById('filter-cite').value = '';
        document.getElementById('filter-batiment').value = '';
        currentFilter = { campus: null, cite: null, batiment: null };
        document.getElementById('filter-cite').disabled = true;
        document.getElementById('filter-batiment').disabled = true;
        displayAllCampus();
    }
</script>