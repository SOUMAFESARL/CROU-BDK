
@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'parc-immobilier'
])

@section('title', 'Parc immobilier')

@section('content')
    @adminroute('admin.hebergement.equipements.affectations.index')
    <a href="{{ route('admin.hebergement.equipements.affectations.index') }}" class="btn btn-success m-3">Équipements affectés au parc</a>
@endadminroute
    <div class="page-body">
        <x-breadcrumbs
                title="Parc Immobilier"
                :items="[
                        'Gestion Hébergement' => route('admin.hebergement.index'),
                        'Parc Immobilier' => null,
                    ]"
        />

        <div class="pt-2">
            <div class="stats-container">
                <x-card-statistics
                        icon="fas fa-city"
                        titre="NOMBRE DE CITÉ"
                        nombre="{{number_format($cites->count(), 0, '', ' ')}}"
                        color="success"
                />

                <x-card-statistics
                        icon="fas fa-building"
                        titre="NOMBRE DE BÂTIMENT"
                        nombre="{{number_format($batiments->count(), 0, '', ' ')}}"
                        color="warning"
                />

                <x-card-statistics
                        icon="fas fa-bed"
                        titre="CHAMBRES TOTALES"
                        nombre="{{number_format($statistiquesChambres['total'], 0, '', ' ')}}"
                        color="info"
                />

                <x-card-statistics
                        icon="fas fa-check-circle"
                        titre="CHAMBRES DISPONIBLES"
                        nombre="{{number_format($statistiquesChambres['disponible'], 0, '', ' ')}}"
                        color="success"
                />

                <x-card-statistics
                        icon="fas fa-user"
                        titre="CHAMBRES OCCUPÉES"
                        nombre="{{number_format($statistiquesChambres['occupe'], 0, '', ' ')}}"
                        color="primary"
                />
            </div>
        </div>

        <div
                class="overflow-hidden rounded-[20px] bg-white shadow-[0_8px_24px_rgba(0,0,0,0.08)]"
                x-data="{
        // Données initiales
        campusList: {{ Js::from($campus) }},
        citesList: {{ Js::from($cites->map(function($cite) {
            return [
                'id' => $cite->id_cite,
                'code_cite' => $cite->code_cite,
                'name' => $cite->name,
                'campus_id' => $cite->campus_id,
                'nombre_batiments' => $cite->nombre_batiment ?? 0,
                'nombre_chambres' => $cite->nombre_chambres ?? 0,
                'responsable' => $cite->responsable ?? 'N/A',
                'statut' =>$cite->statut,
                'localisation' => $cite->localisation ?? 'Non renseignée',
                'batiments_disponibles' => $cite->batiments_disponibles ?? 0,
                'batiments_desactives' => $cite->batiments_desactives ?? 0,
                'chambres_disponibles' => $cite->chambres_disponibles ?? 0,
                'chambres_occupees' => $cite->chambres_occupees ?? 0,
                'etudiants_par_categorie' => $cite->etudiants_par_categorie ?? [],
                'type_name' => $cite->typeCite?->name,
                'type' => 'cite',
                'occupation' => $cite->occupation
            ];
        })) }},
        batimentsList: {{ Js::from($batiments->map(function($bat) {

            return [
                'id' => $bat->id_batiment,
                'name' => $bat->name ?? $bat->nom_batiment,
                'cite_id' => $bat->cite_id,
                'campus_id' => $bat->campus_id,
                'cite_name' => $bat->cite?->name,
                'nombre_chambres' => $bat->nombre_chambres ?? 0,
                'nombre_etages' => $bat->nombre_etages ?? 0,
                'statut' => $bat->statut,
                'localisation' => $bat->localisation ?? 'Non renseignée',
                'chambres_disponibles' => $bat->chambres_disponibles ?? 0,
                'chambres_occupees' => $bat->chambres_occupees ?? 0,
                'etudiants_par_categorie' => $bat->etudiants_par_categorie ?? [],
                'type' => 'batiment',
                'occupation' => $bat->occupation,
                'responsable' => $bat->responsable ?? 'N/A'
            ];
        })) }},
        chambresList: {{ Js::from($chambres->map(function($chambre) {
            return [
                'id' => $chambre->id_chambre,
                'name' => $chambre->numero_chambre,
                'numero' => $chambre->numero_chambre,
                'statut' => $chambre->etat,
                'batiment_id' => $chambre->batiment_id,
                'cite_id' => $chambre->cite_id,
                'campus_id' => $chambre->campus_id,
                'batiment_name' => $chambre->batiment?->name,
                'cite_name' => $chambre->cite?->name,
                'capacite_max' => $chambre->capacite_max,
                'superficie' => $chambre->superficie,
                'prix' => $chambre->prix,
                'type_code' => $chambre->type_chambre_id,
                'type_name' => $chambre->typeChambre?->name,
                'niveau_confort' => $chambre->niveau_confort,
                'pmr' => $chambre->pmr,
                'nb_lits' => $chambre->nb_lits,
                 'occupation' => $chambre->occupation,
                'statut_activation' => $chambre->statut,
                'localisation' => $chambre->localisation ?? 'Non renseignée',
                'etudiants' => $chambre->etudiants ?? [],
                'responsable' => $chambre->responsable ?? 'N/A',
                'type' => 'chambre'
            ];
        })) }},
        statistiquesChambres: {{ Js::from($statistiquesChambres) }},

        // Filtres
        selectedCampus: 'All',
        selectedCiteId: 'All',
        selectedBatimentId: 'All',

        // Vue
        viewMode: 'table',
        search: '',

        // Pagination
        currentPage: 1,
        perPage: 25,

        // Données à afficher dynamiquement
        displayItems: [],
        currentLevel: 'cite',

        // UI state
        openActionMenu: null,
        selectedItem: null,
        showEditModal: false,

        /* ===== INITIALISATION ===== */
        init() {
            console.log('Cités chargées:', this.citesList);
            console.log('Bâtiments chargés:', this.batimentsList);
            console.log('Chambres chargées:', this.chambresList);
            this.updateDisplayItems();
        },

        /* ===== OBSERVATEURS POUR LES FILTRES ===== */
        watchSelectedCampus() {
            if (this.selectedCampus === 'All') {
                this.resetFilters();
            } else {
                this.updateDisplayItems();
                this.selectedCiteId = 'All';
                this.selectedBatimentId = 'All';
            }
        },

        watchSelectedCite() {
            this.selectedBatimentId = 'All';
            this.updateDisplayItems();
        },

        watchSelectedBatiment() {
            this.updateDisplayItems();
        },

        /* ===== LOGIQUE D'AFFICHAGE ===== */
        updateDisplayItems() {
            this.currentPage = 1;

            if (this.selectedBatimentId !== 'All') {
                this.currentLevel = 'chambre';
                this.displayItems = this.chambresList
                    .filter(chambre => String(chambre.batiment_id) === String(this.selectedBatimentId))
                    .map(item => ({ ...item, type: 'chambre' }));
            } else if (this.selectedCiteId !== 'All') {
                this.currentLevel = 'batiment';
                this.displayItems = this.batimentsList
                    .filter(bat => String(bat.cite_id) === String(this.selectedCiteId))
                    .map(item => ({ ...item, type: 'batiment' }));
            } else if (this.selectedCampus !== 'All') {
                this.currentLevel = 'cite';
                this.displayItems = this.citesList
                    .filter(cite => String(cite.campus_id) === String(this.selectedCampus))
                    .map(item => ({ ...item, type: 'cite' }));
            } else {
                // AFFICHAGE PAR DÉFAUT : Liste des cités
                this.currentLevel = 'cite';
                this.displayItems = this.citesList.map(item => ({ ...item, type: 'cite' }));
            }
        },

        /* ===== FILTRAGE ET PAGINATION ===== */
        get filteredItems() {
            return this.displayItems.filter(item => {
                const searchOk = !this.search ||
                Object.values(item).some(v =>
                    v && String(v).toLowerCase().includes(this.search.toLowerCase())
                );
                return searchOk;
            });
        },

        get totalPages() {
            return Math.ceil(this.filteredItems.length / this.perPage) || 1;
        },

        get paginatedItems() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filteredItems.slice(start, start + this.perPage);
        },

        /* ===== ICONES PAR TYPE ===== */
        getIcon(type) {
            const icons = {
                campus: 'fa-university',
                cite: 'fa-city',
                batiment: 'fa-building',
                chambre: 'fa-bed'
            };
            return icons[type] || 'fa-circle';
        },

        /* ===== COULEURS PAR TYPE ===== */
        getColor(type) {
            const colors = {
                campus: 'primary',
                cite: 'success',
                batiment: 'warning',
                chambre: 'info'
            };
            return colors[type] || 'secondary';
        },

        /* ===== DESCRIPTION ===== */
        get tableDescription() {
            const findById = (items, id) => items.find(item => String(item.id ?? item.id_campus) === String(id));

            if (this.selectedBatimentId !== 'All') {
                const batiment = findById(this.batimentsList, this.selectedBatimentId);
                const room = this.displayItems.find(item => item.type === 'chambre');
                const citeId = batiment?.cite_id ?? room?.cite_id;
                const cite = findById(this.citesList, citeId);
                const batimentName = batiment?.name ?? room?.batiment_name ?? 'Bâtiment sélectionné';
                const citeName = cite?.name ?? batiment?.cite_name ?? room?.cite_name ?? 'Cité sélectionnée';
                return `Liste des chambres du bâtiment «<span class='text-yellow-600'>${batimentName}</span>» de la cité «<span class='text-green-600'>${citeName}</span>»`;
            }

            if (this.selectedCiteId !== 'All') {
                const cite = findById(this.citesList, this.selectedCiteId);
                const building = this.displayItems.find(item => item.type === 'batiment');
                const citeName = cite?.name ?? building?.cite_name ?? 'Cité sélectionnée';
                return `Liste des bâtiments de la cité «<span class='text-green-600'>${citeName}</span>»`;
            }

            if (this.selectedCampus !== 'All') {
                const campus = findById(this.campusList, this.selectedCampus);
                if (campus) {
                    return `Liste des cités du campus «<span class='text-blue-600'>${campus.name}</span>»`;
                }
            }

            return 'Liste des cités';
        },

        /* ===== ACTIONS ===== */
        getEditUrl(item) {
            switch(item.type) {
                case 'campus':
                    return `{{ route('admin.hebergement.parc-immobilier.create') }}?type=campus&edit=1&id=${item.id}`;
                case 'cite':
                    return `{{ route('admin.hebergement.parc-immobilier.create') }}?type=cite&edit=1&id=${item.id}`;
                case 'batiment':
                    return `{{ route('admin.hebergement.parc-immobilier.create') }}?type=batiment&edit=1&id=${item.id}`;
                case 'chambre':
                    return `{{ route('admin.hebergement.parc-immobilier.create') }}?type=chambre&edit=1&id=${item.id}`;
                default:
                    return '#';
            }
        },

        getDeleteUrl(item) {
            return `{{ route('admin.hebergement.parc-immobilier.destroy', ['type' => ':type', 'code' => ':code']) }}`
                .replace(':type', item.type)
                .replace(':code', item.id);
        },

        getStatusUrl(item) {
            return `{{ route('admin.hebergement.parc-immobilier.toggle-status', ['type' => ':type', 'code' => ':code']) }}`
                .replace(':type', item.type)
                .replace(':code', item.id);
        },

        toggleStatus(item) {
            const status = item.type === 'chambre' ? item.statut_activation : item.statut;
            if (status === 'desactive') {
                sendStatusRequest(item);
                return;
            }

            openStatusConfirmationModal(item);
        },

        applyStatusUpdate(item, status) {
            if (item.type === 'cite') {
                const batimentIds = this.batimentsList
                    .filter(batiment => String(batiment.cite_id) === String(item.id))
                    .map(batiment => String(batiment.id));
                this.citesList.forEach(cite => {
                    if (String(cite.id) === String(item.id)) cite.statut = status;
                });
                this.batimentsList.forEach(batiment => {
                    if (String(batiment.cite_id) === String(item.id)) batiment.statut = status;
                });
                this.chambresList.forEach(chambre => {
                    if (String(chambre.cite_id) === String(item.id) || batimentIds.includes(String(chambre.batiment_id))) {
                        chambre.statut_activation = status;
                    }
                });
            } else if (item.type === 'batiment') {
                this.batimentsList.forEach(batiment => {
                    if (String(batiment.id) === String(item.id)) batiment.statut = status;
                });
                this.chambresList.forEach(chambre => {
                    if (String(chambre.batiment_id) === String(item.id)) chambre.statut_activation = status;
                });
            } else {
                this.chambresList.forEach(chambre => {
                    if (String(chambre.id) === String(item.id)) chambre.statut_activation = status;
                });
            }
            this.updateDisplayItems();
        },

        deleteItem(item) {
            if (confirm(`Êtes-vous sûr de vouloir supprimer ce ${item.type} ?`)) {
                const form = document.getElementById('delete-form');
                form.action = this.getDeleteUrl(item);
                form.submit();
            }
        },

        viewDetails(item) {
            openDetailsModal(item);
        },

        selectItem(item) {
            if (item.type === 'campus') {
                this.selectedCampus = item.id;
                this.watchSelectedCampus();
            } else if (item.type === 'cite') {
                this.selectedCiteId = item.id;
                this.watchSelectedCite();
            } else if (item.type === 'batiment') {
                this.selectedCiteId = item.cite_id;
                this.selectedBatimentId = item.id;
                this.watchSelectedBatiment();
            }
        },

        showBuildings(citeId) {
            this.selectedCiteId = citeId;
            this.selectedBatimentId = 'All';
            this.updateDisplayItems();
        },

        showRooms(batimentId) {
            const batiment = this.batimentsList.find(item => String(item.id) === String(batimentId));
            if (batiment) {
                this.selectedCiteId = batiment.cite_id;
            }
            this.selectedBatimentId = batimentId;
            this.updateDisplayItems();
        },

        resetFilters() {
            this.selectedCampus = 'All';
            this.selectedCiteId = 'All';
            this.selectedBatimentId = 'All';
            this.search = '';
            this.currentPage = 1;
            this.updateDisplayItems();
        }
        }"
        >
            <!-- ================= HEADER ================= -->
            <div class="flex flex-col gap-4 border-b px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">
                        Vue d’ensemble
                        <span class="text-sm text-gray-500 font-normal ml-2" x-text="'(' + currentLevel + ')'"></span>
                    </h3>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <!-- Barre de recherche -->
                    <input
                            type="text"
                            placeholder="Rechercher..."
                            x-model="search"
                            @input="currentPage = 1"
                            class="h-11 w-full rounded-[20px] border border-gray-300 px-4 text-sm focus:ring-2 focus:ring-[#1F7C43] sm:w-[200px] lg:w-[260px]"
                    >

                    <div class="flex items-center gap-2">
                        <!-- Boutons de vue -->
                        <div class="flex items-center rounded-[20px] border border-gray-300">
                            <button
                                    @click="viewMode='cards'"
                                    :class="viewMode==='cards'
            ? 'bg-[#1F7C43] text-white'
            : 'bg-white text-gray-700 hover:bg-gray-50'"
                                    class="rounded-l-[20px] px-4 py-3 text-sm font-medium transition-colors"
                                    title="Vue cartes"
                            >
                                <span class="hidden sm:inline">Cartes</span>
                                <i class="fas fa-th-large sm:hidden"></i>
                            </button>

                            <button
                                    @click="viewMode='table'"
                                    :class="viewMode==='table'
            ? 'bg-[#1F7C43] text-white'
            : 'bg-white text-gray-700 hover:bg-gray-50'"
                                    class="rounded-r-[20px] border-l px-4 py-3 text-sm font-medium transition-colors"
                                    title="Vue tableau"
                            >
                                <span class="hidden sm:inline">Tableau</span>
                                <i class="fas fa-table sm:hidden"></i>
                            </button>
                        </div>

                        <!-- Bouton Réinitialiser -->
                        @adminroute('admin.map')
                        <a href="{{route('admin.map')}}" target="_blank"
                           class="bg-gray-200 hover:bg-gray-300 inline-flex items-center justify-center gap-2 rounded-[20px] px-4 py-3 text-sm font-medium text-gray-700 transition"
                           title="Réinitialiser les filtres"
                        >
                            <i class="fas fa-redo"></i>
                            <span class="hidden sm:inline">Vue map</span>
                        </a>
                        @endadminroute

                        <!-- Bouton Ajouter -->
                        @adminroute('admin.hebergement.parc-immobilier.create')
<button
                                onclick="openCustomModal()"
                                class="bg-yellow-600 hover:bg-yellow-500 inline-flex items-center justify-center gap-2 rounded-[20px] px-4 py-3 text-sm font-medium text-white transition"
                                title="Ajouter un élément"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M5 10.0002H15.0006M10.0002 5V15.0006" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                            Ajouter
                        </button>
@endadminroute

                    </div>
                </div>
            </div>

            <!-- ================= FILTRES ================= -->
            <div class="flex flex-col gap-4 border-b px-4 py-4">
                <div class="flex flex-wrap items-center gap-2 text-sm">
                    <button
                            type="button"
                            @click="resetFilters()"
                            class="inline-flex items-center gap-2 rounded-full px-3 py-2 font-medium transition"
                            :class="currentLevel === 'cite' && selectedCiteId === 'All' ? 'bg-[#1F7C43] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    >
                        <i class="fas fa-city"></i> Cités
                    </button>
                    <i class="fas fa-chevron-right text-gray-400" aria-hidden="true"></i>
                    <button
                            type="button"
                            @click="selectedBatimentId = 'All'; updateDisplayItems()"
                            :disabled="selectedCiteId === 'All'"
                            class="inline-flex items-center gap-2 rounded-full px-3 py-2 font-medium transition disabled:cursor-not-allowed disabled:opacity-40"
                            :class="currentLevel === 'batiment' ? 'bg-[#1F7C43] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    >
                        <i class="fas fa-building"></i> Bâtiments
                    </button>
                    <i class="fas fa-chevron-right text-gray-400" aria-hidden="true"></i>
                    <button
                            type="button"
                            @click="updateDisplayItems()"
                            :disabled="selectedBatimentId === 'All'"
                            class="inline-flex items-center gap-2 rounded-full px-3 py-2 font-medium transition disabled:cursor-not-allowed disabled:opacity-40"
                            :class="currentLevel === 'chambre' ? 'bg-[#1F7C43] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    >
                        <i class="fas fa-bed"></i> Chambres
                    </button>
                </div>


                <div class="flex items-center gap-2 rounded-lg bg-gray-50 px-4 py-3 text-base font-semibold text-gray-800" aria-live="polite">
                    <i class="fas fa-location-dot text-[#1F7C43]" aria-hidden="true"></i>
                    <span class="text-gray-500">Emplacement actuel :</span>
                    <span x-html="tableDescription"></span>
                </div>
            </div>

            <!-- ================= VUE CARTES ================= -->
            <div x-show="viewMode==='cards'" x-cloak class="p-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                    <template x-for="item in paginatedItems" :key="item.id">
                        <div
                                @click="selectItem(item)"
                                :class="{
                                    'border-green-500 bg-green-50': item.type === 'cite',
                                    'border-yellow-500 bg-yellow-50': item.type === 'batiment',
                                    'border-purple-500 bg-purple-50': item.type === 'chambre'
                                }"
                                class="rounded-[16px] border-2 p-4 text-center relative hover:shadow-md transition-shadow cursor-pointer"
                        >
                            <!-- Bouton des 3 points en haut à droite -->
                            <div class="absolute top-2 right-2 z-10">
                                <div class="relative">
                                    <button
                                            @click.stop="openActionMenu = openActionMenu === item.id ? null : item.id"
                                            class="w-6 h-6 rounded-full hover:bg-white/80 flex items-center justify-center"
                                            title="Actions"
                                    >
                                        <i class="fas fa-ellipsis-v text-sm"></i>
                                    </button>

                                    <!-- Menu d'actions -->
                                    <div
                                            x-show="openActionMenu === item.id"
                                            x-cloak
                                            @click.stop
                                            class="absolute top-full right-0 mt-1 bg-white rounded-lg shadow-lg border p-2 min-w-[120px] z-50"
                                    >
                                        <div class="flex flex-col space-y-2">
                                            <button
                                                    @click="viewDetails(item); openActionMenu = null"
                                                    class="flex items-center space-x-2 px-3 py-2 text-sm hover:bg-blue-50 rounded text-blue-600"
                                            >
                                                <i class="fas fa-eye w-4"></i>
                                                <span>Voir</span>
                                            </button>
                                            @adminroute('admin.hebergement.parc-immobilier.toggle-status')
<button
                                                    @click="item.type === 'chambre' ? openChambreEditModal(item) : window.location.href = getEditUrl(item); openActionMenu = null"
                                                    class="flex items-center space-x-2 px-3 py-2 text-sm hover:bg-green-50 rounded text-green-600"
                                            >
                                                <i class="fas fa-edit w-4"></i>
                                                <span>Modifier</span>
                                            </button>
@endadminroute

                                            @adminroute('admin.hebergement.parc-immobilier.destroy')
<button
                                                    @click="deleteItem(item); openActionMenu = null"
                                                    class="flex items-center space-x-2 px-3 py-2 text-sm hover:bg-red-50 rounded text-red-600"
                                            >
                                                <i class="fas fa-trash w-4"></i>
                                                <span>Supprimer</span>
                                            </button>
@endadminroute

                                            <button
                                                    @click="toggleStatus(item); openActionMenu = null"
                                                    class="flex items-center space-x-2 px-3 py-2 text-sm hover:bg-orange-50 rounded text-orange-600"
                                            >
                                                <i class="fas" :class="(item.type === 'chambre' ? item.statut_activation : item.statut) === 'desactive' ? 'fa-toggle-on' : 'fa-toggle-off'"></i>
                                                <span x-text="(item.type === 'chambre' ? item.statut_activation : item.statut) === 'desactive' ? 'Activer' : 'Désactiver'"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Icone -->
                            <div class="mb-3">
                                <i
                                        :class="getIcon(item.type)"
                                        class="fas text-2xl"
                                        :class="{
                                        'text-green-600': item.type === 'cite',
                                        'text-yellow-600': item.type === 'batiment',
                                        'text-purple-600': item.type === 'chambre'
                                    }"
                                ></i>
                            </div>

                            <!-- Contenu de la carte -->
                            <h3 class="text-lg font-semibold mb-1" x-text="item.name"></h3>

                            <!-- Statistiques pour les CITÉS -->
                            <template x-if="item.type === 'cite'">
                                <div class="mt-2 flex flex-wrap gap-2 justify-center">
                                    <button
                                            type="button"
                                            @click.stop="showBuildings(item.id)"
                                            class="rounded-full bg-green-600 px-3 py-1 text-xs font-semibold text-white hover:bg-green-700"
                                    >
                                        <i class="fas fa-building mr-1"></i> Voir les bâtiments
                                    </button>
                                    <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">
                                        <i class="fas fa-building mr-1"></i>
                                        <span x-text="item.nombre_batiments + ' bât.'"></span>
                                    </span>
                                    <span class="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded-full">
                                        <i class="fas fa-bed mr-1"></i>
                                        <span x-text="item.nombre_chambres + ' ch.'"></span>
                                    </span>
                                </div>
                            </template>

                            <!-- Statistiques pour les BÂTIMENTS -->
                            <template x-if="item.type === 'batiment'">
                                <div class="mt-2 flex flex-wrap gap-2 justify-center">
                                    <button
                                            type="button"
                                            @click.stop="showRooms(item.id)"
                                            class="rounded-full bg-yellow-600 px-3 py-1 text-xs font-semibold text-white hover:bg-yellow-700"
                                    >
                                        <i class="fas fa-bed mr-1"></i> Voir les chambres
                                    </button>
                                    <span class="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded-full">
                                        <i class="fas fa-bed mr-1"></i>
                                        <span x-text="item.nombre_chambres + ' ch.'"></span>
                                    </span>
                                    <span class="text-xs bg-orange-100 text-orange-800 px-2 py-1 rounded-full">
                                        <i class="fas fa-layer-group mr-1"></i>
                                        <span x-text="item.nombre_etages + ' niv.'"></span>
                                    </span>
                                </div>
                            </template>

                        </div>
                    </template>
                </div>
            </div>

            <!-- ================= VUE TABLEAU ================= -->
            <div x-show="viewMode==='table'" x-cloak class="table-responsive-container">
                <table class="w-full table-auto">
                    <thead>
                    <tr class="sticky-header border-b text-xs text-gray-500">
                        <th class="px-4 py-4 text-left">Nom</th>
                        <th x-show="currentLevel === 'cite'" class="px-4 py-4 text-left">Responsable</th>
                        <th x-show="currentLevel === 'batiment'" class="px-4 py-4 text-left">Concierge</th>
                        <!-- Colonnes pour les statistiques des CITÉS -->
                        <th x-show="currentLevel === 'cite'" class="px-4 py-4 text-left">Bâtiments</th>
                        <th x-show="currentLevel === 'cite'" class="px-4 py-4 text-left">Chambres</th>
                        <th x-show="currentLevel === 'cite'" class="px-4 py-4 text-left">Statut</th>
                        <!-- Colonnes pour les statistiques des BÂTIMENTS -->
                        <th x-show="currentLevel === 'batiment'" class="px-4 py-4 text-left">Chambres</th>
                        <th x-show="currentLevel === 'batiment'" class="px-4 py-4 text-left">Niveaux</th>
                        <th x-show="currentLevel === 'batiment'" class="px-4 py-4 text-left">Statut</th>
                        <!-- Colonnes pour les statistiques des CHAMBRES -->
                        <th x-show="currentLevel === 'chambre'" class="px-4 py-4 text-left">Statut</th>
                        <th class="px-4 py-4 text-left">Occupation</th>
                        <th class="px-4 py-4 text-center">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    <template x-for="item in paginatedItems" :key="item.id">
                        <tr
                                @click="selectItem(item)"
                                :class="{
                                    'bg-green-50 hover:bg-green-100': item.type === 'cite',
                                    'bg-yellow-50 hover:bg-yellow-100': item.type === 'batiment',
                                    'bg-purple-50 hover:bg-purple-100': item.type === 'chambre'
                                }"
                                class="cursor-pointer transition-colors"
                        >
                            <!-- Colonne Nom (avec icône) -->
                            <td class="px-4 py-3 font-medium">
                                <div class="flex items-center gap-3">
                                    <i :class="getIcon(item.type)" class="fas w-4 text-gray-400"></i>
                                    <div>
                                        <div x-text="item.name" class="font-medium"></div>
                                    </div>
                                </div>
                            </td>


                            <template x-if="item.type === 'cite' || item.type === 'batiment'">
                                <td class="px-4 py-3">
                                    <span class="badge capitalize" x-text="item.responsable"></span>
                                </td>
                            </template>
                            <!-- Statistiques pour les CITÉS -->
                            <template x-if="item.type === 'cite'">
                                <td class="px-4 py-3">
                                        <span class="badge badge-info">
                                            <i class="fas fa-building mr-1"></i>
                                            <span x-text="item.nombre_batiments"></span>
                                        </span>
                                </td>
                                <td class="px-4 py-3">
                                       <span class="badge badge-secondary">
                                            <i class="fas fa-bed mr-1"></i>
                                            <span x-text="item.nombre_chambres"></span>
                                        </span>
                                </td>

                            </template>

                            <template x-if="item.type === 'cite'">

                                <td class="px-4 py-3">
                                       <span class="badge badge-secondary">
                                            <i class="fas fa-bed mr-1"></i>
                                            <span x-text="item.nombre_chambres"></span>
                                        </span>
                                </td>

                            </template>
                            <template x-if="item.type === 'cite'">

                                <td class="px-4 py-3">
                                       <span class="badge badge-secondary">

                                            <span x-text="item.statut"></span>
                                        </span>
                                </td>

                            </template>
                            <template x-if="item.type === 'cite'">

                                <td class="px-4 py-3">
                                       <span class="badge badge-secondary">

                                            <span x-text="item.occupation"></span>
                                        </span>
                                </td>

                            </template>

                            <!-- Statistiques pour les BÂTIMENTS -->
                            <template x-if="item.type === 'batiment'">
                                <td class="px-4 py-3">
                                        <span class="badge badge-secondary">
                                            <i class="fas fa-bed mr-1"></i>
                                            <span x-text="item.nombre_chambres"></span>
                                        </span>
                                </td>
                            </template>
                            <template x-if="item.type === 'batiment'">
                                <td class="px-4 py-3">
                                        <span class="badge badge-warning">
                                            <i class="fas fa-layer-group mr-1"></i>
                                            <span x-text="item.nombre_etages"></span>
                                        </span>
                                </td>
                            </template>
                            <template x-if="item.type === 'batiment'">

                                <td class="px-4 py-3">
                                        <span class="badge badge-secondary">
                                            <span x-text="item.statut"></span>
                                        </span>
                                </td>
                            </template>
                            <template x-if="item.type === 'batiment'">

                                <td class="px-4 py-3">
                                        <span class="badge badge-secondary" x-text="item.occupation">

                                        </span>
                                </td>

                            </template>

                            <!-- Statistiques pour les CHAMBRES -->
                            <template x-if="item.type === 'chambre'">
                                <td class="px-4 py-3">
                                        <span
                                                class="badge capitalize"
                                                :class="{
                                                'badge-danger': item.statut === 'occupee',
                                                'badge-success': item.statut === 'disponible',
                                                'badge-warning': item.statut === 'maintenance'
                                            }"
                                                x-text="item.statut"
                                        ></span>
                                </td>
                            </template>
                            <template x-if="item.type === 'chambre'">

                                <td class="px-4 py-3">
                                       <span class="badge badge-secondary">

                                            <span x-text="item.occupation"></span>
                                        </span>
                                </td>

                            </template>



                            <!-- Actions -->
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center space-x-2">
                                    <button
                                            @click.stop="viewDetails(item)"
                                            class="text-blue-600 hover:text-blue-900 p-1 rounded-full hover:bg-blue-50"
                                            title="Voir les détails"
                                    >
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button
                                            @click.stop="toggleStatus(item)"
                                            class="p-1 rounded-full hover:bg-orange-50"
                                            :class="(item.type === 'chambre' ? item.statut_activation : item.statut) === 'desactive' ? 'text-green-600 hover:text-green-900' : 'text-orange-600 hover:text-orange-900'"
                                            :title="(item.type === 'chambre' ? item.statut_activation : item.statut) === 'desactive' ? 'Activer' : 'Désactiver'"
                                    >
                                        <i class="fas" :class="(item.type === 'chambre' ? item.statut_activation : item.statut) === 'desactive' ? 'fa-toggle-on' : 'fa-toggle-off'"></i>
                                    </button>
                                    @adminroute('admin.hebergement.parc-immobilier.toggle-status')
<button
                                            @click.stop="item.type === 'chambre' ? openChambreEditModal(item) : window.location.href = getEditUrl(item)"
                                            class="text-green-600 hover:text-green-900 p-1 rounded-full hover:bg-green-50"
                                            title="Modifier"
                                    >
                                        <i class="fas fa-edit"></i>
                                    </button>
@endadminroute

                                </div>
                            </td>
                        </tr>
                    </template>
                    </tbody>
                </table>
            </div>

            <!-- ================= PAGINATION ================= -->
            <div class="flex items-center justify-between border-t px-4 py-4 text-sm">
                <span x-text="filteredItems.length + ' élément(s)'"></span>

                <div class="flex gap-2">
                    <button
                            @click="currentPage--"
                            :disabled="currentPage === 1"
                            class="h-8 w-8 rounded-full border disabled:opacity-50 hover:bg-gray-50"
                    >
                        ‹
                    </button>

                    <template x-for="page in totalPages" :key="page">
                        <button
                                @click="currentPage = page"
                                :class="currentPage === page
                            ? 'bg-[#1F7C43] text-white border-[#1F7C43]'
                            : 'border border-gray-300 bg-white text-gray-700'"
                                class="h-8 w-8 rounded-full hover:bg-gray-50"
                                x-text="page"
                        ></button>
                    </template>

                    <button
                            @click="currentPage++"
                            :disabled="currentPage === totalPages"
                            class="h-8 w-8 rounded-full border disabled:opacity-50 hover:bg-gray-50"
                    >
                        ›
                    </button>
                </div>
            </div>
        </div>

        {{-- Modal Ajout --}}
        <div id="customModal" class="custom-modal">
            <div class="custom-modal-overlay" onclick="closeCustomModal()"></div>
            <div class="custom-modal-container">
                <div class="custom-modal-content">
                    <div class="custom-modal-header">
                        <div class="custom-modal-header-top">
                            <h5 class="custom-modal-title">Ajouter un élément</h5>
                            <button type="button" class="custom-modal-close" onclick="closeCustomModal()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <p class="custom-modal-subtitle">Que souhaitez-vous créer dans le parc immobilier ?</p>
                    </div>
                    <div class="custom-modal-body">
                        <div class="options-grid">
                            @adminroute('admin.hebergement.parc-immobilier.create')
                            <a href="{{ route('admin.hebergement.parc-immobilier.create', ['type' => 'cite']) }}" class="option-card">
                                <div class="option-icon green">
                                    <i class="fas fa-city"></i>
                                </div>
                                <div class="option-title">Nouvelle Cité</div>
                            </a>
                            @endadminroute

                            @adminroute('admin.hebergement.parc-immobilier.create')
                            <a href="{{ route('admin.hebergement.parc-immobilier.create', ['type' => 'batiment']) }}" class="option-card">
                                <div class="option-icon yellow">
                                    <i class="fas fa-building"></i>
                                </div>
                                <div class="option-title">Nouveau Bâtiment</div>
                            </a>
                            @endadminroute

                            @adminroute('admin.hebergement.parc-immobilier.create')
                            <a href="{{ route('admin.hebergement.parc-immobilier.create', ['type' => 'chambre']) }}" class="option-card">
                                <div class="option-icon info">
                                    <i class="fas fa-bed"></i>
                                </div>
                                <div class="option-title">Nouvelle Chambre</div>
                            </a>
                            @endadminroute
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal Détails --}}
        <div id="detailsModal" class="custom-modal">
            <div class="custom-modal-overlay" onclick="closeDetailsModal()"></div>
            <div class="custom-modal-container">
                <div class="custom-modal-content">
                    <div class="custom-modal-header">
                        <div class="custom-modal-header-top">
                            <h5 class="custom-modal-title">Détails</h5>
                            <button type="button" class="custom-modal-close" onclick="closeDetailsModal()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="custom-modal-body">
                        <!-- Content will be dynamically populated -->
                        <div id="modalContent"></div>
                    </div>
                </div>
            </div>
        </div>

        <div id="chambreEditModal" class="custom-modal" style="display: none;">
            <div class="custom-modal-overlay" onclick="closeChambreEditModal()"></div>
            <div class="custom-modal-container">
                <div class="custom-modal-content">
                    <div class="custom-modal-header">
                        <div class="custom-modal-header-top">
                            <h5 class="custom-modal-title">Modifier la chambre</h5>
                            @adminroute('admin.hebergement.parc-immobilier.toggle-status')
<button type="button" class="custom-modal-close" onclick="closeChambreEditModal()">
                                <i class="fas fa-times"></i>
                            </button>
@endadminroute

                        </div>
                    </div>
                    <div class="custom-modal-body">
                        <form id="chambreEditForm">
                            <input type="hidden" id="editChambreId">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="editChambreNumero">Numéro</label>
                                    <input type="text" id="editChambreNumero" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="editChambreType">Type de chambre</label>
                                    <select id="editChambreType" class="form-select" required>
                                        <option value="">Sélectionner un type...</option>
                                        @foreach($type_chambre ?? [] as $typeChambre)
                                            <option value="{{ $typeChambre->id_type_chambre }}">{{ $typeChambre->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="editChambreConfort">Niveau de confort</label>
                                    <select id="editChambreConfort" class="form-select">
                                        <option value="standard">Standard</option>
                                        <option value="eleve">Élevé</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="editChambrePmr">PMR</label>
                                    <select id="editChambrePmr" class="form-select">
                                        <option value="0">Non</option>
                                        <option value="1">Oui</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="editChambreLits">Nombre de lits</label>
                                <input type="number" id="editChambreLits" class="form-control" min="1" required>
                            </div>
                            <div id="chambreEditError" class="alert alert-danger" style="display: none;"></div>
                        </form>
                    </div>
                    <div class="custom-modal-footer">
                        @adminroute('admin.hebergement.parc-immobilier.toggle-status')
<button type="button" class="btn btn-secondary" onclick="closeChambreEditModal()">Annuler</button>
@endadminroute

                        <button type="button" id="saveChambreEdit" class="btn btn-success">Enregistrer</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal de confirmation de désactivation --}}
        <div id="statusConfirmationModal" class="custom-modal">
            <div class="custom-modal-overlay" onclick="closeStatusConfirmationModal()"></div>
            <div class="custom-modal-container">
                <div class="custom-modal-content">
                    <div class="custom-modal-header">
                        <div class="custom-modal-header-top">
                            <h5 class="custom-modal-title text-orange-700">Confirmer la désactivation</h5>
                            <button type="button" class="custom-modal-close" onclick="closeStatusConfirmationModal()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <p class="custom-modal-subtitle">Cette action peut affecter la disponibilité du logement.</p>
                    </div>
                    <div class="custom-modal-body">
                        <div class="flex items-start gap-3 rounded-lg bg-orange-50 p-4 text-orange-900">
                            <i class="fas fa-triangle-exclamation mt-1"></i>
                            <p id="statusConfirmationMessage" class="m-0 text-sm"></p>
                        </div>
                        <div class="mt-5 flex justify-end gap-3">
                            <button type="button" onclick="closeStatusConfirmationModal()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Annuler
                            </button>
                            <button type="button" onclick="submitStatusChange()" class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700">
                                Désactiver
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Notification d’échec de désactivation --}}
        <div id="statusNotificationModal" class="custom-modal {{ session('status_error') ? 'show' : '' }}">
                <div class="custom-modal-overlay" onclick="closeStatusNotificationModal()"></div>
                <div class="custom-modal-container">
                    <div class="custom-modal-content">
                        <div class="custom-modal-header">
                            <div class="custom-modal-header-top">
                                <h5 class="custom-modal-title text-red-700">Désactivation impossible</h5>
                                <button type="button" class="custom-modal-close" onclick="closeStatusNotificationModal()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="custom-modal-body">
                            <div class="flex items-start gap-3 rounded-lg bg-red-50 p-4 text-red-900">
                                <i class="fas fa-circle-exclamation mt-1"></i>
                                <p id="statusNotificationMessage" class="m-0 text-sm">{{ session('status_error') }}</p>
                            </div>
                            <div class="mt-5 flex justify-end">
                                <button type="button" onclick="closeStatusNotificationModal()" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                                    Compris
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Formulaire de suppression caché --}}
        <form id="delete-form" method="POST" style="display: none;">
            @csrf
            @method('DELETE')
        </form>

        <form id="status-form" method="POST" style="display: none;">
            @csrf
        </form>

    </div>
@endsection

@push('styles')
    <style>
        /* Styles personnalisés pour les badges */
        .badge-primary { background-color: #dbeafe; color: #1e40af; }
        .badge-success { background-color: #d1fae5; color: #065f46; }
        .badge-warning { background-color: #fef3c7; color: #92400e; }
        .badge-info { background-color: #e0f2fe; color: #0369a1; }
        .badge-danger { background-color: #fee2e2; color: #991b1b; }
        .badge-secondary { background-color: #e5e7eb; color: #374151; }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: var(--app-font-min, 15px);
            font-weight: 600;
            text-transform: uppercase;
        }

        /* Styles pour le modal de détails */
        #detailsModal.custom-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1060;
        }

        #detailsModal.custom-modal.show {
            display: block;
        }

        /* Modal styles */
        .custom-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1050;
        }

        .custom-modal.show {
            display: block;
        }

        .custom-modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .custom-modal-container {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100%;
            padding: 1rem;
        }

        .custom-modal-content {
            background-color: white;
            border-radius: 16px;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .custom-modal-header {
            padding: 1.5rem 1.5rem 0.5rem;
        }

        .custom-modal-header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }

        .custom-modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }

        .custom-modal-close {
            background: none;
            border: none;
            color: #6b7280;
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0.25rem;
            margin: -0.25rem;
        }

        .custom-modal-close:hover {
            color: #374151;
        }

        .custom-modal-subtitle {
            color: #6b7280;
            font-size: var(--app-font-min, 15px);
            margin: 0;
        }

        .custom-modal-body {
            padding: 1.5rem;
        }

        .detail-group {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }

        .detail-group h4 {
            margin: 0 0 0.5rem;
            font-size: var(--app-font-min, 15px);
            font-weight: 700;
            color: #374151;
        }

        .detail-list {
            display: grid;
            gap: 0.4rem;
            margin: 0;
            padding: 0;
            list-style: none;
            font-size: var(--app-font-min, 15px);
        }

        .detail-list li {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.45rem 0.6rem;
            border-radius: 0.5rem;
            background: #f9fafb;
        }

        .detail-list strong,
        .detail-list small {
            color: #6b7280;
        }

        .options-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .option-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.5rem 1rem;
            background-color: #f9fafb;
            border-radius: 12px;
            border: 2px solid transparent;
            text-decoration: none;
            color: #374151;
            transition: all 0.2s;
            cursor: pointer;
        }

        .option-card:hover {
            background-color: white;
            border-color: #fbbf24;
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .option-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            margin-bottom: 0.75rem;
            font-size: 1.5rem;
        }

        .option-icon.blue {
            background-color: #dbeafe;
            color: #1d4ed8;
        }

        .option-icon.green {
            background-color: #d1fae5;
            color: #059669;
        }

        .option-icon.yellow {
            background-color: #fef3c7;
            color: #d97706;
        }

        .option-icon.info {
            background-color: #e0f2fe;
            color: #0369a1;
        }

        .option-title {
            font-weight: 500;
            text-align: center;
            font-size: 0.9375rem;
        }

        @media (max-width: 640px) {
            .options-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('files/assets/pages/dashboard/custom-dashboard.js') }}"></script>

    <script>
        window.campusList = {{ Js::from($campus) }};
        window.citesList = {{ Js::from($cites) }};
        window.batimentsList = {{ Js::from($batiments) }};
        window.chambresList = {{ Js::from($chambres) }};

        function openDetailsModal(item) {
            const modal = document.getElementById('detailsModal');
            const content = document.getElementById('modalContent');
            const categories = Object.entries(item.etudiants_par_categorie || {})
                .map(([category, count]) => `<li><span>${category}</span><strong>${count}</strong></li>`)
                .join('') || '<li><span>Aucun étudiant</span><strong>0</strong></li>';
            const students = (item.etudiants || []).map(student => `
                <li><span>${student.nom}${student.matricule ? ` (${student.matricule})` : ''}</span><small>${student.categorie || 'Non catégorisé'}</small></li>
            `).join('') || '<li><span>Aucun étudiant affecté</span></li>';

            // Générer le contenu en fonction du type d'élément
            let html = '';

            if (item.type === 'cite') {
                html = `
                    <div class="room-header">
                        <span class="status-badge badge-success">
                            <i class="fas fa-city"></i>
                            Cité
                        </span>
                    </div>

                    <div class="room-details">
                        <div class="detail-row">
                            <span class="detail-label">Nom:</span>
                            <span class="detail-value">${item.name}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Code:</span>
                            <span class="detail-value">${item.code_cite || 'Non renseigné'}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Localisation:</span>
                            <span class="detail-value">${item.localisation}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Responsable:</span>
                            <span class="detail-value">${item.responsable || 'Aucun responsable'}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Nombre de bâtiments:</span>
                            <span class="detail-value">${item.nombre_batiments || 0} (${item.batiments_disponibles || 0} disponible(s), ${item.batiments_desactives || 0} désactivé(s))</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Nombre de chambres:</span>
                            <span class="detail-value">${item.nombre_chambres || 0} (${item.chambres_disponibles || 0} disponible(s), ${item.chambres_occupees || 0} occupée(s))</span>
                        </div>
                        <div class="detail-group">
                            <h4>Étudiants par catégorie</h4>
                            <ul class="detail-list">${categories}</ul>
                        </div>
                    </div>
                `;
            } else if (item.type === 'batiment') {
                html = `
                    <div class="room-header">
                        <span class="status-badge badge-warning">
                            <i class="fas fa-building"></i>
                            Bâtiment
                        </span>
                    </div>

                    <div class="room-details">
                        <div class="detail-row">
                            <span class="detail-label">Nom:</span>
                            <span class="detail-value">${item.name}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Cité:</span>
                            <span class="detail-value">${window.citesList.find(c => String(c.id_cite) === String(item.cite_id))?.name || item.cite_name || 'Non spécifié'}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Localisation:</span>
                            <span class="detail-value">${item.localisation}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Concierge:</span>
                            <span class="detail-value">${item.responsable || 'Aucun concierge'}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Nombre de chambres:</span>
                            <span class="detail-value">${item.nombre_chambres || 0} (${item.chambres_disponibles || 0} disponible(s), ${item.chambres_occupees || 0} occupée(s))</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Nombre de niveaux:</span>
                            <span class="detail-value">${item.nombre_etages || 0}</span>
                        </div>
                        <div class="detail-group">
                            <h4>Étudiants par catégorie</h4>
                            <ul class="detail-list">${categories}</ul>
                        </div>
                    </div>
                `;
            } else if (item.type === 'chambre') {
                html = `
                    <div class="room-header">
                        <span class="status-badge ${item.statut === 'disponible' ? 'badge-success' : item.statut === 'occupee' ? 'badge-danger' : 'badge-warning'}">
                            <i class="fas fa-circle"></i>
                            ${item.statut === 'disponible' ? 'Disponible' : item.statut === 'occupee' ? 'Occupée' : 'Maintenance'}
                        </span>
                    </div>

                    <div class="room-details">
                        <div class="detail-row">
                            <span class="detail-label">Numéro:</span>
                            <span class="detail-value">${item.numero || ''}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Bâtiment:</span>
                            <span class="detail-value">${window.batimentsList.find(b => String(b.id_batiment) === String(item.batiment_id))?.name || item.batiment_name || 'Non spécifié'}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Cité:</span>
                            <span class="detail-value">${window.citesList.find(c => String(c.id_cite) === String(item.cite_id))?.name || item.cite_name || 'Non spécifié'}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Localisation:</span>
                            <span class="detail-value">${item.localisation}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Étudiants:</span>
                            <span class="detail-value">${(item.etudiants || []).length}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Capacité:</span>
                            <span class="detail-value">${item.capacite_max || 'Non spécifiée'}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Superficie:</span>
                            <span class="detail-value">${item.superficie || 'Non spécifiée'} m²</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Prix:</span>
                            <span class="detail-value">${item.prix || 'Non spécifié'} FCFA</span>
                        </div>
                        <div class="detail-group">
                            <h4>Étudiants affectés</h4>
                            <ul class="detail-list">${students}</ul>
                        </div>
                    </div>
                `;
            }

            content.innerHTML = html;
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeDetailsModal() {
            const modal = document.getElementById('detailsModal');
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }

        function openChambreEditModal(chambre) {
            document.getElementById('editChambreId').value = chambre.id;
            document.getElementById('editChambreNumero').value = chambre.numero || '';
            document.getElementById('editChambreType').value = chambre.type_code || '';
            document.getElementById('editChambreConfort').value = chambre.niveau_confort || 'standard';
            document.getElementById('editChambrePmr').value = chambre.pmr ? '1' : '0';
            document.getElementById('editChambreLits').value = chambre.nb_lits || 1;
            document.getElementById('chambreEditError').style.display = 'none';
            document.getElementById('chambreEditModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeChambreEditModal() {
            document.getElementById('chambreEditModal').classList.remove('show');
            document.body.style.overflow = '';
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('saveChambreEdit')?.addEventListener('click', function () {
                const button = this;
                const error = document.getElementById('chambreEditError');
                const data = new URLSearchParams({
                    _token: document.querySelector('meta[name="csrf-token"]')?.content || '',
                    _method: 'PUT',
                    code: document.getElementById('editChambreId').value,
                    numero_chambre: document.getElementById('editChambreNumero').value.trim(),
                    type_chambre_code: document.getElementById('editChambreType').value,
                    niveau_confort: document.getElementById('editChambreConfort').value,
                    pmr: document.getElementById('editChambrePmr').value,
                    nb_lits: document.getElementById('editChambreLits').value
                });

                button.disabled = true;
                button.textContent = 'Enregistrement...';
                error.style.display = 'none';

                fetch('{{ route("admin.hebergement.parc-immobilier.updateChambre") }}', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: data
                })
                    .then(response => response.json().then(body => ({ ok: response.ok, body })))
                    .then(({ ok, body }) => {
                        if (!ok || !body.success) {
                            throw new Error(body.message || 'Erreur lors de la modification.');
                        }
                        closeChambreEditModal();
                        window.location.reload();
                    })
                    .catch(exception => {
                        error.textContent = exception.message;
                        error.style.display = 'block';
                    })
                    .finally(() => {
                        button.disabled = false;
                        button.textContent = 'Enregistrer';
                    });
            });
        });

        let pendingStatusForm = null;
        let pendingStatusItem = null;

        function openStatusConfirmationModal(item) {
            const messages = {
                cite: `La cité « ${item.name} » sera désactivée, ainsi que tous ses bâtiments et toutes ses chambres. Elle ne pourra plus être utilisée tant qu'elle ne sera pas réactivée.`,
                batiment: `Le bâtiment « ${item.name} » sera désactivé, ainsi que toutes ses chambres. Il ne pourra plus être utilisé tant qu'il ne sera pas réactivé.`,
                chambre: `La chambre « ${item.name} » sera désactivée et ne sera plus proposée pour une nouvelle affectation tant qu'elle ne sera pas réactivée.`
            };

            pendingStatusForm = document.getElementById('status-form');
            pendingStatusItem = item;
            const statusUrlTemplate = @json(route('admin.hebergement.parc-immobilier.toggle-status', ['type' => ':type', 'code' => ':code']));
            pendingStatusForm.action = statusUrlTemplate
                .replace(':type', item.type)
                .replace(':code', item.id);
            document.getElementById('statusConfirmationMessage').textContent = messages[item.type];
            document.getElementById('statusConfirmationModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function submitStatusChange() {
            if (pendingStatusForm && pendingStatusItem) {
                closeStatusConfirmationModal();
                sendStatusRequest(pendingStatusItem);
            }
        }

        async function sendStatusRequest(item) {
            const form = document.getElementById('status-form');
            const csrfToken = form.querySelector('input[name="_token"]').value;
            const statusUrlTemplate = @json(route('admin.hebergement.parc-immobilier.toggle-status', ['type' => ':type', 'code' => ':code']));
            form.action = statusUrlTemplate
                .replace(':type', item.type)
                .replace(':code', item.id);

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'La mise à jour du statut a échoué.');
                }

                const state = Alpine.$data(document.querySelector('[x-data]'));
                state.applyStatusUpdate(item, data.status);
            } catch (error) {
                showStatusNotification(error.message);
            }
        }

        function showStatusNotification(message) {
            document.getElementById('statusNotificationMessage').textContent = message;
            document.getElementById('statusNotificationModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeStatusConfirmationModal() {
            document.getElementById('statusConfirmationModal').classList.remove('show');
            pendingStatusForm = null;
            pendingStatusItem = null;
            document.body.style.overflow = '';
        }

        function closeStatusNotificationModal() {
            const modal = document.getElementById('statusNotificationModal');
            if (modal) {
                modal.classList.remove('show');
            }
            document.body.style.overflow = '';
        }

        // Modal d'ajout
        function openCustomModal() {
            const modal = document.getElementById('customModal');
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeCustomModal() {
            const modal = document.getElementById('customModal');
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }

        // Fermer avec la touche Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const detailsModal = document.getElementById('detailsModal');
                if (detailsModal && detailsModal.classList.contains('show')) {
                    closeDetailsModal();
                }

                const statusModal = document.getElementById('statusConfirmationModal');
                if (statusModal && statusModal.classList.contains('show')) {
                    closeStatusConfirmationModal();
                }

                const notificationModal = document.getElementById('statusNotificationModal');
                if (notificationModal && notificationModal.classList.contains('show')) {
                    closeStatusNotificationModal();
                }

                const customModal = document.getElementById('customModal');
                if (customModal && customModal.classList.contains('show')) {
                    closeCustomModal();
                }
            }
        });

        // Fermer le modal en cliquant sur l'overlay
        document.addEventListener('DOMContentLoaded', function() {
            const notificationModal = document.getElementById('statusNotificationModal');
            if (notificationModal && notificationModal.classList.contains('show')) {
                document.body.style.overflow = 'hidden';
            }

            const overlays = document.querySelectorAll('.custom-modal-overlay');
            overlays.forEach(overlay => {
                if (overlay.closest('#customModal')) {
                    overlay.addEventListener('click', closeCustomModal);
                }
                if (overlay.closest('#detailsModal')) {
                    overlay.addEventListener('click', closeDetailsModal);
                }
            });
        });
    </script>
@endpush
