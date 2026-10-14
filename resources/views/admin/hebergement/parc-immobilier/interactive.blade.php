
@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'parc-immobilier'
])

@section('title', 'Carte Interactive - Gestion Immobilière')

@section('content')
    <div class="container-fluid p-0 h-100">
        <div style="display: grid; grid-template-columns: 350px 1fr; height: 100vh; gap: 0;">
            <!-- SIDEBAR -->
            <aside style="background: white; border-right: 1px solid #e2e8f0; overflow-y: auto; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); display: flex; flex-direction: column;">
                <div style="padding: 24px 20px;     background: linear-gradient(135deg, #3f8454 0%, #448861 100%); color: white; border-bottom: 2px solid #e2e8f0;">
                    <h1 style="font-size: 22px; font-weight: 700; margin-bottom: 8px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-map-location-dot"></i> Carte Interactive
                    </h1>
                    <p style="font-size: var(--app-font-min, 15px); opacity: 0.9; margin: 0;">Gestion Immobilière - Campus, Cités et Bâtiments</p>
                </div>

                <div style="flex: 1; padding: 20px; overflow-y: auto;">
                    <!-- Stats -->
                    <div style="background: linear-gradient(135deg, #f0f9ff 0%, #ede9fe 100%); padding: 16px; border-radius: 12px; margin-bottom: 20px; border-left: 4px solid #43875f;">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; font-size: var(--app-font-min, 15px); margin-bottom: 8px;">
                            <span style="color: #64748b; font-weight: 500;"><i class="fas fa-building"></i> Campus</span>
                            <span style="font-size: 18px; font-weight: 700; color: #2563eb;" id="count-campus">{{ count($campuses) }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; font-size: var(--app-font-min, 15px); margin-bottom: 8px;">
                            <span style="color: #64748b; font-weight: 500;"><i class="fas fa-house"></i> Cités</span>
                            <span style="font-size: 18px; font-weight: 700; color: #2563eb;" id="count-cite">{{ count($cites) }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; font-size: var(--app-font-min, 15px);">
                            <span style="color: #64748b; font-weight: 500;"><i class="fas fa-gopuram"></i> Bâtiments</span>
                            <span style="font-size: 18px; font-weight: 700; color: #2563eb;" id="count-batiment">{{ count($batiments) }}</span>
                        </div>
                    </div>

                    <!-- Filter: Cité -->
                    <div style="margin-bottom: 24px;">
                        <label style="display: block; font-size: var(--app-font-min, 15px); font-weight: 600; text-transform: uppercase; color: #64748b; margin-bottom: 8px; letter-spacing: 0.5px;">
                            <i class="fas fa-filter"></i> Filtrer par Cité
                        </label>
                        <select id="filter-cite" onchange="handleCiteChange()" style="width: 100%; padding: 10px 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: var(--app-font-min, 15px); background: white; cursor: pointer; transition: all 0.3s ease; color: #1e293b;">
                            <option value="">-- Toutes les cités --</option>
                        </select>
                    </div>

                    <!-- Filter: Bâtiment -->
                    <div style="margin-bottom: 24px;">
                        <label style="display: block; font-size: var(--app-font-min, 15px); font-weight: 600; text-transform: uppercase; color: #64748b; margin-bottom: 8px; letter-spacing: 0.5px;">
                            <i class="fas fa-filter"></i> Filtrer par Bâtiment
                        </label>
                        <select id="filter-batiment" onchange="handleBatimentChange()" disabled style="width: 100%; padding: 10px 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: var(--app-font-min, 15px); background: white; cursor: pointer; transition: all 0.3s ease; color: #1e293b; opacity: 0.6;">
                            <option value="">-- Sélectionnez une cité d'abord --</option>
                        </select>
                    </div>

                    <!-- Info Section -->
                    <div style="margin-bottom: 24px;">
                        <label style="display: block; font-size: var(--app-font-min, 15px); font-weight: 600; text-transform: uppercase; color: #64748b; margin-bottom: 8px; letter-spacing: 0.5px;">
                            <i class="fas fa-info-circle"></i> Éléments Visibles
                        </label>
                        <div id="info-list" style="background: #f1f5f9; border-radius: 8px; padding: 12px; max-height: 300px; overflow-y: auto;">
                            <div style="text-align: center; padding: 20px; color: #94a3b8;">
                                <div style="font-size: 32px; margin-bottom: 8px; opacity: 0.5;">📍</div>
                                <p>Aucun marqueur affiché</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div style="padding: 16px 20px; border-top: 1px solid #e2e8f0; background: #f8fafc;">
                    <button onclick="resetFilters()" style="width: 100%; padding: 12px; border: 2px solid #2563eb; background: white; color: #2563eb; border-radius: 8px; font-size: var(--app-font-min, 15px); font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="fas fa-redo"></i> Réinitialiser
                    </button>
                </div>
            </aside>

            <!-- MAP CONTAINER -->
            <div style="position: relative; overflow: hidden;">
                <div id="map" style="width: 100%; height: 100%;"></div>

                <!-- Zoom Info -->
                <div style="position: absolute; top: 20px; right: 20px; background: white; padding: 12px 16px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); z-index: 400; font-size: var(--app-font-min, 15px); color: #64748b; font-weight: 500;">
                    <span id="zoom-level">Zoom: 15</span>
                    <span style="margin: 0 8px; opacity: 0.5;">•</span>
                    <span id="marker-count">0 marqueurs</span>
                </div>

                <!-- Legend -->
                <div style="position: absolute; bottom: 20px; right: 20px; background: white; padding: 16px; border-radius: 12px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15); z-index: 400; max-width: 250px; font-size: var(--app-font-min, 15px);">
                    <div style="font-weight: 700; margin-bottom: 12px; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-layer-group"></i> Légende
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px; padding: 6px 0;">
                        <div style="width: 24px; height: 24px; background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: var(--app-font-min, 15px); font-weight: bold; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                            <i class="fas fa-university" style="font-size: var(--app-font-min, 15px);"></i>
                        </div>
                        <span>Campus</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px; padding: 6px 0;">
                        <div style="width: 24px; height: 24px; background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%); border-radius: 12px 12px 12px 0; display: flex; align-items: center; justify-content: center; color: white; font-size: var(--app-font-min, 15px); font-weight: bold; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                            <i class="fas fa-building" style="font-size: var(--app-font-min, 15px);"></i>
                        </div>
                        <span>Cité / Dortoir</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px; padding: 6px 0;">
                        <div style="width: 24px; height: 24px; background: linear-gradient(135deg, #059669 0%, #047857 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: var(--app-font-min, 15px); font-weight: bold; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                            <i class="fas fa-home" style="font-size: var(--app-font-min, 15px);"></i>
                        </div>
                        <span>Bâtiment</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />

    <!-- Font Awesome pour les icônes dans les marqueurs -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <!-- Leaflet JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

    <style>
        .leaflet-popup-content {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
        }
        .leaflet-popup-content-wrapper {
            border-radius: 12px !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2) !important;
        }
        .custom-popup .leaflet-popup-tip {
            background: white !important;
        }
        .popup-content {
            padding: 0 !important;
        }

        /* Correction pour l'affichage des popups */
        .leaflet-popup-content {
            margin: 12px !important;
            min-width: 250px !important;
        }

        /* Animation de pulsation pour le marqueur de position */
        @keyframes pulse-blue {
            0% {
                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(59, 130, 246, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0);
            }
        }

        /* Animation du spinner */
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Animation pour les marqueurs */
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

        /* Style pour le bouton de contrôle */
        .leaflet-control-custom {
            background-color: white;
            transition: all 0.3s ease;
        }

        .leaflet-control-custom:hover {
            background-color: #f0f9ff;
            transform: scale(1.05);
        }

        /* Effet de survol pour les marqueurs */
        .custom-marker {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .custom-marker:hover {
            transform: scale(1.15);
            transition: transform 0.2s ease;
        }
    </style>

    <script>
        // ========================
        // DATA INITIALIZATION
        // ========================

        // Préparer les données en PHP avec échappement des caractères
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
                    'code' => $c->id_cite,
                    'name' => addslashes($c->name),
                    'campus_code' => $c->campus_code,
                    'latitude' => (float)$c->latitude,
                    'longitude' => (float)$c->longitude,
                    'adresse' => addslashes($c->adresse ?? ''),
                    'telephone1' => addslashes($c->telephone1 ?? ''),
                    'email1' => addslashes($c->email1 ?? ''),
                    'capacite_max' => (int)($c->capacite_max ?? 0),
                    'type_cite_code' => addslashes($c->type_cite_code ?? ''),
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
                    'code_cite' => $b->cite_id ?? $b->code_cite,
                    'cite_id' => $b->cite_id,
                    'code_campus' => $b->campus_id ?? $b->code_campus,
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

        console.log('Data loaded:', allData);
        console.log('Campus count:', allData.campus.length);
        console.log('First campus:', allData.campus[0]);

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

        // Variables pour la position actuelle
        let currentLocationMarker = null;
        let currentLocationCircle = null;
        let watchPositionId = null;

        // ========================
        // FONCTIONS POUR LA TAILLE ADAPTATIVE DES MARQUEURS
        // ========================

        function getSizeByZoom(baseSize) {
            const zoom = map ? map.getZoom() : 15;
            if (zoom >= 20) return baseSize * 2.0;
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
                html: `<div style="background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); width: ${size}px; height: ${size}px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: ${size * 0.45}px; font-weight: bold; border: 3px solid white; box-shadow: 0 4px 8px rgba(0,0,0,0.2), 0 0 0 2px rgba(37,99,235,0.3); transition: all 0.3s ease;">
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
                html: `<div style="background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%); width: ${size}px; height: ${size}px; border-radius: 12px 12px 12px 0; display: flex; align-items: center; justify-content: center; color: white; font-size: ${size * 0.45}px; font-weight: bold; border: 3px solid white; box-shadow: 0 4px 8px rgba(0,0,0,0.2), 0 0 0 2px rgba(124,58,237,0.3); transition: all 0.3s ease;">
                    <i class="fas fa-building" style="font-size: ${size * 0.45}px;"></i>
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
                html: `<div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); width: ${size}px; height: ${size}px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: ${size * 0.45}px; font-weight: bold; border: 3px solid white; box-shadow: 0 4px 8px rgba(0,0,0,0.2), 0 0 0 2px rgba(5,150,105,0.3); transition: all 0.3s ease;">
                    <i class="fas fa-home" style="font-size: ${size * 0.45}px;"></i>
                </div>`,
                iconSize: [size, size],
                iconAnchor: [size/2, size/2],
                popupAnchor: [0, -size/2]
            });
        }

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
                <div class="spinner" style="
                    width: 16px;
                    height: 16px;
                    border: 2px solid #e2e8f0;
                    border-top-color: #2563eb;
                    border-radius: 50%;
                    animation: spin 0.8s linear infinite;
                "></div>
                <span>Recherche de votre position...</span>
            `;
            document.querySelector('#map').appendChild(loadingDiv);
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
            errorDiv.innerHTML = `
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span>⚠️</span>
                    <span>${message}</span>
                </div>
            `;
            document.querySelector('#map').appendChild(errorDiv);

            setTimeout(() => {
                if (errorDiv) errorDiv.remove();
            }, 5000);
        }

        function displayCurrentLocation(latitude, longitude, accuracy) {
            const loadingIndicator = document.getElementById('location-loading');
            if (loadingIndicator) loadingIndicator.remove();

            if (currentLocationMarker) {
                map.removeLayer(currentLocationMarker);
            }
            if (currentLocationCircle) {
                map.removeLayer(currentLocationCircle);
            }

            const currentLocationIcon = L.divIcon({
                className: 'current-location-marker',
                html: `
                    <div style="position: relative; width: 24px; height: 24px;">
                        <div style="
                            position: absolute;
                            top: 50%;
                            left: 50%;
                            transform: translate(-50%, -50%);
                            width: 16px;
                            height: 16px;
                            background: #3b82f6;
                            border: 3px solid white;
                            border-radius: 50%;
                            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
                            animation: pulse-blue 2s infinite;
                        "></div>
                    </div>
                `,
                iconSize: [24, 24],
                iconAnchor: [12, 12],
                popupAnchor: [0, -12]
            });

            currentLocationMarker = L.marker([latitude, longitude], {
                icon: currentLocationIcon
            }).addTo(map);

            currentLocationCircle = L.circle([latitude, longitude], {
                radius: accuracy,
                color: '#3b82f6',
                fillColor: '#3b82f6',
                fillOpacity: 0.1,
                weight: 2,
                opacity: 0.5
            }).addTo(map);

            currentLocationMarker.bindPopup(`
                <div style="padding: 8px; font-family: 'Segoe UI', sans-serif;">
                    <div style="font-weight: 600; color: #1e293b; margin-bottom: 5px;">
                        📍 Votre position
                    </div>
                    <div style="font-size: var(--app-font-min, 15px); color: #64748b;">
                        Précision: ±${Math.round(accuracy)}m
                    </div>
                    <div style="font-size: var(--app-font-min, 15px); color: #64748b; margin-top: 3px;">
                        Lat: ${latitude.toFixed(6)}<br>
                        Lng: ${longitude.toFixed(6)}
                    </div>
                </div>
            `, {
                maxWidth: 250,
                className: 'custom-popup'
            });

            currentLocationMarker.openPopup();
        }

        function getCurrentLocation() {
            if (!navigator.geolocation) {
                showGeolocationError("La géolocalisation n'est pas supportée par votre navigateur");
                return;
            }

            showLocationLoadingIndicator();

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const { latitude, longitude, accuracy } = position.coords;
                    console.log(`Position trouvée: ${latitude}, ${longitude} (précision: ${accuracy}m)`);
                    displayCurrentLocation(latitude, longitude, accuracy);
                    map.setView([latitude, longitude], 18);
                },
                (error) => {
                    console.error("Erreur de géolocalisation:", error);
                    let errorMessage = "";
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = "Permission refusée. Activez la géolocalisation pour voir votre position.";
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = "Position indisponible. Vérifiez votre connexion GPS.";
                            break;
                        case error.TIMEOUT:
                            errorMessage = "Délai dépassé. Réessayez plus tard.";
                            break;
                        default:
                            errorMessage = "Erreur de géolocalisation. Réessayez.";
                    }
                    showGeolocationError(errorMessage);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }

        function addLocationControl() {
            const LocationControl = L.Control.extend({
                options: {
                    position: 'topleft'
                },

                onAdd: function(map) {
                    const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
                    container.style.backgroundColor = 'white';
                    container.style.width = '34px';
                    container.style.height = '34px';
                    container.style.cursor = 'pointer';
                    container.style.borderRadius = '4px';
                    container.style.boxShadow = '0 2px 6px rgba(0,0,0,0.3)';
                    container.style.display = 'flex';
                    container.style.alignItems = 'center';
                    container.style.justifyContent = 'center';
                    container.style.fontSize = '20px';
                    container.innerHTML = '📍';
                    container.title = 'Voir ma position';

                    container.onclick = () => {
                        getCurrentLocation();
                    };

                    return container;
                }
            });

            map.addControl(new LocationControl());
        }

        // ========================
        // FONCTION DE MISE À JOUR DES MARQUEURS SELON LE ZOOM
        // ========================

        function updateMarkersForZoom() {
            // Sauvegarder les données des marqueurs actuels
            const currentCampusData = markers.campus.map(m => m.options.campusData);
            const currentCiteData = markers.cite.map(m => m.options.citeData);
            const currentBatimentData = markers.batiment.map(m => m.options.batimentData);

            // Supprimer les marqueurs actuels
            clearMarkers();

            // Recréer les marqueurs avec les nouvelles tailles
            currentCampusData.forEach(campus => {
                if (campus && campus.latitude && campus.longitude) {
                    addCampusMarker(campus);
                }
            });
            currentCiteData.forEach(cite => {
                if (cite && cite.latitude && cite.longitude) {
                    addCiteMarker(cite);
                }
            });
            currentBatimentData.forEach(batiment => {
                if (batiment && batiment.latitude && batiment.longitude) {
                    addBatimentMarker(batiment);
                }
            });
        }

        // ========================
        // INIT MAP
        // ========================
        function initMap() {
            // Configuration de la carte avec zoom maximum élevé
            map = L.map('map', {
                maxZoom: 22,
                minZoom: 2,
                zoomSnap: 0.5,
                zoomDelta: 0.5
            }).setView([6.8276, -5.2893], 6);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 22,
                minZoom: 2
            }).addTo(map);

            map.on('zoomend', function() {
                document.getElementById('zoom-level').textContent = 'Zoom: ' + map.getZoom();
                updateMarkersForZoom();
            });

            // Ajouter le contrôle de localisation
            addLocationControl();

            populateCiteFilter(allData.cite);
            displayAllCites();
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
                const cites = allData.cite.filter(c => c.campus_code === campus);

                cites.forEach(cite => {
                    const option = document.createElement('option');
                    option.value = cite.code;
                    option.textContent = `${cite.name} (${cite.code_cite})`;
                    select.appendChild(option);
                });

                select.disabled = cites.length === 0;
                displayFilteredData();
            } else {
                populateCiteFilter(allData.cite);
                document.getElementById('filter-cite').disabled = false;
                document.getElementById('filter-batiment').disabled = true;
                displayAllCites();
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
                const batiments = allData.batiment.filter(b =>
                    String(b.code_cite) === String(cite) || String(b.cite_id) === String(cite)
                );

                batiments.forEach(bat => {
                    const option = document.createElement('option');
                    option.value = bat.code_batiment;
                    option.textContent = `${bat.name} (${bat.code_batiment})`;
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

        // ========================
        // DISPLAY FUNCTIONS
        // ========================
        function displayAllCampus() {
            clearMarkers();
            currentFilter = { campus: null, cite: null, batiment: null };

            allData.campus.forEach(campus => {
                if (campus.latitude && campus.longitude && campus.latitude !== 0 && campus.longitude !== 0) {
                    addCampusMarker(campus);
                } else {
                    console.warn('Campus sans coordonnées:', campus);
                }
            });

            updateInfoList();
            updateMarkerCount();
            fitMapBounds();
        }

        function populateCiteFilter(cites) {
            const select = document.getElementById('filter-cite');
            select.innerHTML = '<option value="">-- Toutes les cités --</option>';
            cites.forEach(cite => {
                const option = document.createElement('option');
                option.value = cite.code;
                option.textContent = `${cite.name} (${cite.code_cite})`;
                select.appendChild(option);
            });
            select.disabled = cites.length === 0;
            select.style.opacity = cites.length === 0 ? '0.6' : '1';
        }

        function displayAllCites() {
            clearMarkers();
            currentFilter = { campus: currentFilter.campus, cite: null, batiment: null };
            allData.cite.forEach(cite => {
                if (cite.latitude && cite.longitude && cite.latitude !== 0 && cite.longitude !== 0) {
                    addCiteMarker(cite);
                }
            });
            updateInfoList();
            updateMarkerCount();
            fitMapBounds();
        }

        function displayFilteredData() {
            clearMarkers();

            if (currentFilter.batiment) {
                const batiments = allData.batiment.filter(b => b.code_batiment === currentFilter.batiment);
                batiments.forEach(bat => addBatimentMarker(bat));
            } else if (currentFilter.cite) {
                const batiments = allData.batiment.filter(b =>
                    String(b.code_cite) === String(currentFilter.cite) ||
                    String(b.cite_id) === String(currentFilter.cite)
                );
                batiments.forEach(bat => addBatimentMarker(bat));
            } else if (currentFilter.campus) {
                const cites = allData.cite.filter(c => c.campus_code === currentFilter.campus);
                cites.forEach(cite => addCiteMarker(cite));
            } else {
                displayAllCites();
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

            const popupContent = createCampusPopup(campus);
            marker.bindPopup(popupContent, {
                maxWidth: 350,
                minWidth: 280,
                className: 'custom-popup'
            });

            marker.on('click', () => {
                document.getElementById('filter-campus').value = campus.code_campus;
              //  handleCampusChange();
            });

            markers.campus.push(marker);
        }

        function addCiteMarker(cite) {
            const marker = L.marker([cite.latitude, cite.longitude], {
                icon: createCiteIcon(),
                citeData: cite
            }).addTo(map);

            const popupContent = createCitePopup(cite);
            marker.bindPopup(popupContent, {
                maxWidth: 350,
                minWidth: 280,
                className: 'custom-popup'
            });

            markers.cite.push(marker);
        }

        function addBatimentMarker(batiment) {
            const marker = L.marker([batiment.latitude, batiment.longitude], {
                icon: createBatimentIcon(),
                batimentData: batiment
            }).addTo(map);

            const popupContent = createBatimentPopup(batiment);
            marker.bindPopup(popupContent, {
                maxWidth: 350,
                minWidth: 280,
                className: 'custom-popup'
            });

            markers.batiment.push(marker);
        }

        // ========================
        // CREATE POPUPS
        // ========================
        function createCampusPopup(campus) {
            return `
                <div style="padding: 12px; font-family: 'Segoe UI', sans-serif; min-width: 260px;">
                    <div style="font-weight: 700; font-size: 16px; margin-bottom: 12px; color: #1e293b; border-bottom: 2px solid #2563eb; padding-bottom: 8px;">
                        🏢 ${campus.name || 'Sans nom'}
                    </div>
                    <div style="font-size: var(--app-font-min, 15px); color: #475569; line-height: 1.8;">
                        ${campus.code_campus ? `
                        <div style="display: flex; gap: 12px; margin-bottom: 8px;">
                            <span style="font-weight: 600; color: #2563eb; min-width: 80px;">Code:</span>
                            <span>${campus.code_campus}</span>
                        </div>
                        ` : ''}
                        ${campus.adresse && campus.adresse !== '' ? `
                        <div style="display: flex; gap: 12px; margin-bottom: 8px;">
                            <span style="font-weight: 600; color: #2563eb; min-width: 80px;">Adresse:</span>
                            <span>${campus.adresse}</span>
                        </div>
                        ` : ''}
                        ${campus.telephone1 && campus.telephone1 !== '' ? `
                        <div style="display: flex; gap: 12px; margin-bottom: 8px;">
                            <span style="font-weight: 600; color: #2563eb; min-width: 80px;">Tél:</span>
                            <a href="tel:${campus.telephone1}" style="color: #2563eb; text-decoration: none; font-weight: 500;">${campus.telephone1}</a>
                        </div>
                        ` : ''}
                        ${campus.telephone2 && campus.telephone2 !== '' ? `
                        <div style="display: flex; gap: 12px; margin-bottom: 8px;">
                            <span style="font-weight: 600; color: #2563eb; min-width: 80px;">Tél 2:</span>
                            <a href="tel:${campus.telephone2}" style="color: #2563eb; text-decoration: none; font-weight: 500;">${campus.telephone2}</a>
                        </div>
                        ` : ''}
                        ${campus.email1 && campus.email1 !== '' ? `
                        <div style="display: flex; gap: 12px; margin-bottom: 8px;">
                            <span style="font-weight: 600; color: #2563eb; min-width: 80px;">Email:</span>
                            <a href="mailto:${campus.email1}" style="color: #2563eb; text-decoration: none; word-break: break-all;">${campus.email1}</a>
                        </div>
                        ` : ''}
                        ${campus.description && campus.description !== '' ? `
                        <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #e2e8f0;">
                            <span style="font-weight: 600; color: #2563eb;">Description:</span>
                            <p style="margin: 5px 0 0; font-size: var(--app-font-min, 15px); color: #64748b;">${campus.description}</p>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }

        function createCitePopup(cite) {
            return `
                <div style="padding: 12px; font-family: 'Segoe UI', sans-serif; min-width: 260px;">
                    <div style="font-weight: 700; font-size: 16px; margin-bottom: 12px; color: #1e293b; border-bottom: 2px solid #7c3aed; padding-bottom: 8px;">
                        🏠 ${cite.name || 'Sans nom'}
                    </div>
                    <div style="font-size: var(--app-font-min, 15px); color: #475569; line-height: 1.8;">
                        ${cite.code_cite ? `
                        <div style="display: flex; gap: 12px; margin-bottom: 8px;">
                            <span style="font-weight: 600; color: #7c3aed; min-width: 80px;">Code:</span>
                            <span>${cite.code_cite}</span>
                        </div>
                        ` : ''}
                        ${cite.type_cite_name && cite.type_cite_name !== '' ? `
                        <div style="display: flex; gap: 12px; margin-bottom: 8px;">
                            <span style="font-weight: 600; color: #7c3aed; min-width: 80px;">Type:</span>
                            <span>${cite.type_cite_name}</span>
                        </div>
                        ` : ''}
                        ${cite.capacite_max && cite.capacite_max > 0 ? `
                        <div style="display: flex; gap: 12px; margin-bottom: 8px;">
                            <span style="font-weight: 600; color: #7c3aed; min-width: 80px;">Capacité:</span>
                            <span>${cite.capacite_max} personnes</span>
                        </div>
                        ` : ''}
                        ${cite.adresse && cite.adresse !== '' ? `
                        <div style="display: flex; gap: 12px; margin-bottom: 8px;">
                            <span style="font-weight: 600; color: #7c3aed; min-width: 80px;">Adresse:</span>
                            <span>${cite.adresse}</span>
                        </div>
                        ` : ''}
                        ${cite.telephone1 && cite.telephone1 !== '' ? `
                        <div style="display: flex; gap: 12px; margin-bottom: 8px;">
                            <span style="font-weight: 600; color: #7c3aed; min-width: 80px;">Tél:</span>
                            <a href="tel:${cite.telephone1}" style="color: #7c3aed; text-decoration: none; font-weight: 500;">${cite.telephone1}</a>
                        </div>
                        ` : ''}
                        ${cite.email1 && cite.email1 !== '' ? `
                        <div style="display: flex; gap: 12px; margin-bottom: 8px;">
                            <span style="font-weight: 600; color: #7c3aed; min-width: 80px;">Email:</span>
                            <a href="mailto:${cite.email1}" style="color: #7c3aed; text-decoration: none; word-break: break-all;">${cite.email1}</a>
                        </div>
                        ` : ''}
                        ${(cite.wifi_gratuit || cite.salle_etude || cite.infirmerie || cite.restaurant) ? `
                        <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #e2e8f0;">
                            <span style="font-weight: 600; color: #7c3aed;">Services:</span>
                            <div style="margin-top: 5px;">
                                ${cite.wifi_gratuit ? '<span style="display: inline-block; background: #e0e7ff; color: #3730a3; padding: 2px 8px; border-radius: 12px; font-size: var(--app-font-min, 15px); margin: 2px;">📶 Wifi</span>' : ''}
                                ${cite.salle_etude ? '<span style="display: inline-block; background: #e0e7ff; color: #3730a3; padding: 2px 8px; border-radius: 12px; font-size: var(--app-font-min, 15px); margin: 2px;">📚 Salle étude</span>' : ''}
                                ${cite.infirmerie ? '<span style="display: inline-block; background: #e0e7ff; color: #3730a3; padding: 2px 8px; border-radius: 12px; font-size: var(--app-font-min, 15px); margin: 2px;">🏥 Infirmerie</span>' : ''}
                                ${cite.restaurant ? '<span style="display: inline-block; background: #e0e7ff; color: #3730a3; padding: 2px 8px; border-radius: 12px; font-size: var(--app-font-min, 15px); margin: 2px;">🍽️ Restaurant</span>' : ''}
                            </div>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }

        function createBatimentPopup(batiment) {
            return `
                <div style="padding: 12px; font-family: 'Segoe UI', sans-serif; min-width: 260px;">
                    <div style="font-weight: 700; font-size: 16px; margin-bottom: 12px; color: #1e293b; border-bottom: 2px solid #059669; padding-bottom: 8px;">
                        🏗️ ${batiment.name || 'Sans nom'}
                    </div>
                    <div style="font-size: var(--app-font-min, 15px); color: #475569; line-height: 1.8;">
                        ${batiment.type_occupation && batiment.type_occupation !== '' ? `
                        <div style="display: flex; gap: 12px; margin-bottom: 8px;">
                            <span style="font-weight: 600; color: #059669; min-width: 80px;">Type:</span>
                            <span>${batiment.type_occupation}</span>
                        </div>
                        ` : ''}
                        ${batiment.total_etages && batiment.total_etages > 0 ? `
                        <div style="display: flex; gap: 12px; margin-bottom: 8px;">
                            <span style="font-weight: 600; color: #059669; min-width: 80px;">Étages:</span>
                            <span>${batiment.total_etages}</span>
                        </div>
                        ` : ''}
                        ${batiment.total_chambres && batiment.total_chambres > 0 ? `
                        <div style="display: flex; gap: 12px; margin-bottom: 8px;">
                            <span style="font-weight: 600; color: #059669; min-width: 80px;">Chambres:</span>
                            <span>${batiment.total_chambres}</span>
                        </div>
                        ` : ''}
                        ${batiment.adresse && batiment.adresse !== '' ? `
                        <div style="display: flex; gap: 12px; margin-bottom: 8px;">
                            <span style="font-weight: 600; color: #059669; min-width: 80px;">Adresse:</span>
                            <span>${batiment.adresse}</span>
                        </div>
                        ` : ''}
                        ${batiment.description && batiment.description !== '' ? `
                        <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #e2e8f0;">
                            <span style="font-weight: 600; color: #059669;">Description:</span>
                            <p style="margin: 5px 0 0; font-size: var(--app-font-min, 15px); color: #64748b;">${batiment.description.substring(0, 150)}${batiment.description.length > 150 ? '...' : ''}</p>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }

        function updateInfoList() {
            const list = document.getElementById('info-list');
            list.innerHTML = '';

            let items = [];

            if (currentFilter.batiment) {
                const batiments = allData.batiment.filter(b => b.code_batiment === currentFilter.batiment);
                items = batiments.map(b => ({
                    type: 'batiment',
                    name: b.name,
                    code: b.code_batiment,
                    icon: '🏗️'
                }));
            } else if (currentFilter.cite) {
                const batiments = allData.batiment.filter(b => b.code_cite === currentFilter.cite);
                items = batiments.map(b => ({
                    type: 'batiment',
                    name: b.name,
                    code: b.code_batiment,
                    icon: '🏗️'
                }));
            } else if (currentFilter.campus) {
                const cites = allData.cite.filter(c => c.campus_code === currentFilter.campus);
                items = cites.map(c => ({
                    type: 'cite',
                    name: c.name,
                    code: c.code_cite,
                    icon: '🏠'
                }));
            } else {
                items = allData.cite.filter(c => c.latitude && c.longitude).map(c => ({
                    type: 'cite',
                    name: c.name,
                    code: c.code_cite,
                    icon: '🏠'
                }));
            }

            if (items.length === 0) {
                list.innerHTML = `
                    <div style="text-align: center; padding: 20px; color: #94a3b8;">
                        <div style="font-size: 32px; margin-bottom: 8px; opacity: 0.5;">📍</div>
                        <p>Aucun marqueur affiché</p>
                    </div>
                `;
                return;
            }

            items.forEach(item => {
                const div = document.createElement('div');
                div.style.cssText = 'padding: 10px; margin-bottom: 8px; background: white; border-left: 3px solid #2563eb; border-radius: 4px; font-size: var(--app-font-min, 15px); cursor: pointer; transition: all 0.3s ease;';
                div.innerHTML = `<strong>${item.icon}</strong> ${item.name}<br><small style="color: #94a3b8;">${item.code}</small>`;
                div.onmouseover = () => div.style.backgroundColor = '#e0f2fe';
                div.onmouseout = () => div.style.backgroundColor = 'white';
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
                map.setView([6.8276, -5.2893], 6);
                return;
            }

            const group = new L.featureGroup(allMarkers);
            map.fitBounds(group.getBounds().pad(0.2), { maxZoom: 15, animate: false });
        }

        function updateMarkerCount() {
            const count = markers.campus.length + markers.cite.length + markers.batiment.length;
            document.getElementById('marker-count').textContent = `${count} marqueur${count > 1 ? 's' : ''}`;
            document.getElementById('count-campus').textContent = markers.campus.length;
            document.getElementById('count-cite').textContent = markers.cite.length;
            document.getElementById('count-batiment').textContent = markers.batiment.length;
        }

        function resetFilters() {
            document.getElementById('filter-cite').value = '';
            document.getElementById('filter-batiment').value = '';
            currentFilter = { campus: null, cite: null, batiment: null };
            populateCiteFilter(allData.cite);
            document.getElementById('filter-cite').disabled = false;
            document.getElementById('filter-batiment').disabled = true;
            displayAllCites();
        }

        // ========================
        // INIT
        // ========================
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
        });
    </script>
@endsection
