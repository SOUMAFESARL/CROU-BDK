@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'parametrage-herb'
])

@section('title', 'Configuration des Bâtiments')

@section('content')
    <div class="page-body">
        <x-breadcrumbs
                title="Configuration des Bâtiments"
                :items="[
                'Gestion Hébergement' => route('admin.hebergement.index'),
                'Paramétrage du module hébergement' => route('admin.hebergement.parametrage-herb.index'),
                'Configuration des Bâtiments' => null,
            ]"
        />

        @php
            use Illuminate\Support\Js;

            // Données statiques pour les Bâtiments
            $residences = collect([
                (object)[
                    'id' => 1,
                    'nom' => 'Bâtiment Universitaire A',
                    'ville' => 'Paris',
                    'adresse' => '123 Rue de l\'Université, 75005 Paris',
                    'capacite_totale' => 250,
                    'campagnes_actives' => ['2024-2025'],
                    'chambres_disponibles' => 45,
                    'taux_occupation' => 82,
                    'responsable' => 'Jean Dupont',
                    'email_responsable' => 'j.dupont@crou.fr',
                    'telephone' => '01 23 45 67 89',
                    'types_chambres' => [
                        'individuelle' => 50,
                        'double' => 100,
                        'multiple' => 100
                    ],
                    'equipements' => ['Ascenseur', 'Salle commune', 'Laverie', 'Cafétéria'],
                    'date_creation' => '2010-09-01'
                ],
                (object)[
                    'id' => 2,
                    'nom' => 'Bâtiment Camus',
                    'ville' => 'Lyon',
                    'adresse' => '45 Avenue des Étudiants, 69007 Lyon',
                    'capacite_totale' => 180,
                    'campagnes_actives' => ['2024-2025'],
                    'chambres_disponibles' => 23,
                    'taux_occupation' => 87,
                    'responsable' => 'Marie Martin',
                    'email_responsable' => 'm.martin@crou.fr',
                    'telephone' => '04 78 90 12 34',
                    'types_chambres' => [
                        'individuelle' => 40,
                        'double' => 80,
                        'multiple' => 60
                    ],
                    'equipements' => ['Ascenseur', 'Salle de sport', 'Laverie', 'Parking'],
                    'date_creation' => '2012-03-15'
                ],
                (object)[
                    'id' => 3,
                    'nom' => 'Bâtiment Simone Veil',
                    'ville' => 'Toulouse',
                    'adresse' => '12 Rue de la Réussite, 31000 Toulouse',
                    'capacite_totale' => 200,
                    'campagnes_actives' => ['2024-2025'],
                    'chambres_disponibles' => 67,
                    'taux_occupation' => 66,
                    'responsable' => 'Sophie Bernard',
                    'email_responsable' => 's.bernard@crou.fr',
                    'telephone' => '05 61 23 45 67',
                    'types_chambres' => [
                        'individuelle' => 60,
                        'double' => 90,
                        'multiple' => 50
                    ],
                    'equipements' => ['Ascenseur', 'Salle commune', 'Laverie', 'Cafétéria', 'Parking'],
                    'date_creation' => '2018-09-10'
                ]
            ]);

            // Statistiques
            $stats = [
                'total' => $residences->count(),
                'capacite' => $residences->sum('capacite_totale'),
                'disponibles' => $residences->sum('chambres_disponibles')
            ];
        @endphp

        <div
                class="overflow-hidden rounded-[20px] bg-white shadow-[0_8px_24px_rgba(0,0,0,0.08)]"
                x-data="{
                items: {{ Js::from($residences) }},
                search: '',
                currentPage: 1,
                perPage: 10,
                openModalId: null,

                // Données pour les select cascading
                citesData: {
                    1: [
                        { id: 101, nom: 'Cité A' },
                        { id: 102, nom: 'Cité B' },
                        { id: 103, nom: 'Cité C' }
                    ],
                    2: [
                        { id: 201, nom: 'Cité Newton' },
                        { id: 202, nom: 'Cité Curie' },
                        { id: 203, nom: 'Cité Einstein' }
                    ],
                    3: [
                        { id: 301, nom: 'Cité Victor Hugo' },
                        { id: 302, nom: 'Cité Camus' },
                        { id: 303, nom: 'Cité Sartre' }
                    ],
                    4: [
                        { id: 401, nom: 'Cité Pasteur' },
                        { id: 402, nom: 'Cité Hippocrate' },
                        { id: 403, nom: 'Cité Paré' }
                    ]
                },

                batimentsData: {
                    101: [
                        { id: 1001, nom: 'Bâtiment A1', chambres: 45 },
                        { id: 1002, nom: 'Bâtiment A2', chambres: 38 },
                        { id: 1003, nom: 'Bâtiment A3', chambres: 52 }
                    ],
                    102: [
                        { id: 1004, nom: 'Bâtiment B1', chambres: 42 },
                        { id: 1005, nom: 'Bâtiment B2', chambres: 35 }
                    ],
                    201: [
                        { id: 2001, nom: 'Bâtiment Newton A', chambres: 28 },
                        { id: 2002, nom: 'Bâtiment Newton B', chambres: 32 }
                    ],
                    301: [
                        { id: 3001, nom: 'Bâtiment Hugo 1', chambres: 25 },
                        { id: 3002, nom: 'Bâtiment Hugo 2', chambres: 30 }
                    ],
                    401: [
                        { id: 4001, nom: 'Bâtiment Pasteur A', chambres: 40 },
                        { id: 4002, nom: 'Bâtiment Pasteur B', chambres: 35 }
                    ]
                },

                selectedCampus: '',
                selectedCite: '',
                selectedBatiment: '',
                chambresLibres: 0,
                showChambresInfo: false,

                get filteredItems() {
                    if (!this.search) {
                        return this.items;
                    }
                    return this.items.filter(item => {
                        return item.nom.toLowerCase().includes(this.search.toLowerCase()) ||
                               item.ville.toLowerCase().includes(this.search.toLowerCase()) ||
                               item.responsable.toLowerCase().includes(this.search.toLowerCase());
                    });
                },

                get totalPages() {
                    return Math.ceil(this.filteredItems.length / this.perPage) || 1;
                },

                get paginatedItems() {
                    const start = (this.currentPage - 1) * this.perPage;
                    return this.filteredItems.slice(start, start + this.perPage);
                },

                get cites() {
                    if (!this.selectedCampus) return [];
                    return this.citesData[this.selectedCampus] || [];
                },

                get batiments() {
                    if (!this.selectedCite) return [];
                    return this.batimentsData[this.selectedCite] || [];
                },

                openAddModal() {
                    this.resetForm();
                    const modal = document.getElementById('customModaladd');
                    if (modal) {
                        modal.classList.add('show');
                        document.body.style.overflow = 'hidden';
                    }
                },

                resetForm() {
                    this.selectedCampus = '';
                    this.selectedCite = '';
                    this.selectedBatiment = '';
                    this.showChambresInfo = false;
                    const form = document.getElementById('residenceForm');
                    if (form) form.reset();
                },

                onCampusChange() {
                    this.selectedCite = '';
                    this.selectedBatiment = '';
                    this.showChambresInfo = false;
                },

                onCiteChange() {
                    this.selectedBatiment = '';
                    this.showChambresInfo = false;
                },

                onBatimentChange() {
                    if (this.selectedBatiment) {
                        const batiment = this.batiments.find(b => b.id == this.selectedBatiment);
                        if (batiment) {
                            // Simulation de chambres libres (à remplacer par un appel AJAX)
                            const chambresAttribuees = Math.floor(Math.random() * batiment.chambres);
                            this.chambresLibres = batiment.chambres - chambresAttribuees;
                            this.showChambresInfo = true;
                        }
                    } else {
                        this.showChambresInfo = false;
                    }
                },

                deleteResidence(id) {
                    if (confirm('Êtes-vous sûr de vouloir retirer ce bâtiment ? Cette action est irréversible.')) {
                        this.items = this.items.filter(item => item.id !== id);
                        alert('Bâtiment #' + id + ' retirée avec succès');
                    }
                },

                submitForm() {
                    alert('Bâtiment enregistrée avec succès');
                    const modal = document.getElementById('customModaladd');
                    if (modal) {
                        modal.classList.remove('show');
                        document.body.style.overflow = '';
                    }
                }
            }"
                @keydown.escape.window="
                document.querySelectorAll('.custom-modal.show').forEach(modal => {
                    modal.classList.remove('show');
                    document.body.style.overflow = '';
                });
            "
        >

            <!-- ================= HEADER ================= -->
            <div class="flex flex-col gap-4 border-b px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    @adminroute('admin.hebergement.parametrage-herb.index')
                    <a href="{{ route('admin.hebergement.parametrage-herb.index') }}"
                       class="inline-flex items-center gap-2 rounded-full border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                        <i class="fas fa-arrow-left"></i>
                        Retour
                    </a>
                    @endadminroute
                    <h3 class="text-lg font-semibold text-gray-800">Gestion des bâtiments</h3>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <input
                            type="text"
                            placeholder="Rechercher..."
                            x-model="search"
                            @input="currentPage = 1"
                            class="h-11 w-full rounded-[20px] border border-gray-300 px-4 text-sm focus:ring-2 focus:ring-blue-500 sm:w-[200px] lg:w-[260px]"
                    >
                    <div class="flex items-center gap-2">
                        <button @click="openAddModal()" class="btn btn-primary">+ Configurer un Bâtiment</button>
                    </div>
                </div>
            </div>

            <!-- ================= STATISTIQUES ================= -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4">
                <div class="bg-blue-50 p-4 rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total Bâtiments</p>
                            <p class="text-xl font-semibold mt-1" x-text="items.length"></p>
                        </div>
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-building text-blue-600"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-purple-50 p-4 rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Capacité totale</p>
                            <p class="text-xl font-semibold mt-1">{{ $stats['capacite'] }}</p>
                        </div>
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-door-open text-purple-600"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-orange-50 p-4 rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Places disponibles</p>
                            <p class="text-xl font-semibold mt-1 text-orange-600">{{ $stats['disponibles'] }}</p>
                        </div>
                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-bed text-orange-600"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================= TABLEAU ================= -->
            <div x-cloak class="table-responsive-container">
                <table class="w-full table-auto">
                    <thead>
                    <tr class="sticky-header border-b text-xs text-gray-500">
                        <th class="px-4 py-4">Bâtiment</th>
                        <th class="px-4 py-4">Capacité</th>
                        <th class="px-4 py-4">Disponibles</th>
                        <th class="px-4 py-4">Responsable</th>
                        <th class="px-4 py-4">Campagnes</th>
                        <th class="px-4 py-4">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    <template x-for="residence in paginatedItems" :key="residence.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center">
                                        <i class="fa-solid fa-building text-gray-600"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium" x-text="residence.nom"></div>
                                        <div class="text-xs text-gray-500" x-text="residence.ville"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 font-medium" x-text="residence.capacite_totale + ' places'"></td>
                            <td class="px-4 py-3">
                                <span
                                        class="font-medium"
                                        :class="{
                                        'text-green-600': residence.chambres_disponibles > 0,
                                        'text-orange-600': residence.chambres_disponibles === 0
                                    }"
                                        x-text="residence.chambres_disponibles"
                                ></span>
                            </td>
                            <td class="px-4 py-3">
                                <div x-text="residence.responsable"></div>
                                <div class="text-xs text-gray-400" x-text="residence.telephone"></div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <template x-for="campagne in residence.campagnes_actives" :key="campagne">
                                        <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs" x-text="campagne"></span>
                                    </template>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center space-x-2">
                                    <button @click="deleteResidence(residence.id)"
                                            class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template x-if="paginatedItems.length === 0">
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                        <i class="fa-solid fa-building text-gray-400 text-2xl"></i>
                                    </div>
                                    <p class="mb-2" x-text="search ? 'Aucun Bâtiment trouvé' : 'Aucun Bâtiment trouvé'"></p>
                                    <template x-if="search">
                                        <button @click="search = ''" class="mt-2 text-[#F5A623] hover:underline">
                                            <i class="fa-solid fa-times mr-1"></i>Effacer la recherche
                                        </button>
                                    </template>
                                    <template x-if="!search">
                                        <button @click="openAddModal()" class="mt-2 text-[#1F7C43] hover:underline">
                                            <i class="fa-solid fa-plus mr-1"></i>Ajouter un bâtiment
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                    </tbody>
                </table>
            </div>

            <!-- ================= PAGINATION ================= -->
            <div x-show="filteredItems.length > 0" class="flex items-center justify-between border-t px-4 py-4 text-sm">
                <span x-text="filteredItems.length + ' Bâtiment(s)'"></span>

                <div class="flex gap-2">
                    <button
                            @click="currentPage--"
                            :disabled="currentPage === 1"
                            class="h-8 w-8 rounded-full border disabled:opacity-50"
                    >
                        ‹
                    </button>

                    <template x-for="page in totalPages" :key="page">
                        <button
                                @click="currentPage = page"
                                :class="[
                                'h-8 w-8 rounded-full border transition-colors duration-200',
                                currentPage === page
                                    ? 'bg-[#1F7C43] text-white border-[#1F7C43]'
                                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                            ]"
                                x-text="page"
                        ></button>
                    </template>

                    <button
                            @click="currentPage++"
                            :disabled="currentPage === totalPages"
                            class="h-8 w-8 rounded-full border disabled:opacity-50"
                    >
                        ›
                    </button>
                </div>
            </div>
        </div>

        <!-- ================= MODAL D'AJOUT ================= -->
        <div id="customModaladd" class="custom-modal">
            <div class="custom-modal-overlay" data-close-modal="add"></div>
            <div class="custom-modal-container custom-modal-lg">
                <div class="custom-modal-content">
                    <div class="custom-modal-header">
                        <div class="custom-modal-header-top">
                            <h5 class="custom-modal-title">Nouveau bâtiment</h5>
                            <button type="button" data-close-modal="add" class="custom-modal-close">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="custom-modal-body">
                        <form id="residenceForm" class="row g-3 needs-validation" novalidate @submit.prevent="submitForm">
                            @csrf

                            <div class="col-md-12">
                                <label for="campus" class="form-label">Campus <strong class="text-danger">*</strong></label>
                                <select x-model="selectedCampus" @change="onCampusChange" id="campus" class="form-control" required>
                                    <option value="">Sélectionner un campus</option>
                                    <option value="1">Campus Principal</option>
                                    <option value="2">Campus Sciences</option>
                                    <option value="3">Campus Lettres</option>
                                    <option value="4">Campus Médecine</option>
                                </select>
                            </div>

                            <div class="col-md-12">
                                <label for="cite" class="form-label">Cité <strong class="text-danger">*</strong></label>
                                <select x-model="selectedCite" @change="onCiteChange" id="cite" class="form-control" required :disabled="!selectedCampus">
                                    <option value="">Sélectionner d'abord un campus</option>
                                    <template x-for="cite in cites" :key="cite.id">
                                        <option :value="cite.id" x-text="cite.nom"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="col-md-12">
                                <label for="batiment" class="form-label">Bâtiment <strong class="text-danger">*</strong></label>
                                <select x-model="selectedBatiment" @change="onBatimentChange" id="batiment" class="form-control" required :disabled="!selectedCite">
                                    <option value="">Sélectionner d'abord une cité</option>
                                    <template x-for="batiment in batiments" :key="batiment.id">
                                        <option :value="batiment.id" x-text="batiment.nom"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="col-md-12" x-show="showChambresInfo" x-cloak>
                                <div class="alert" :class="{
                                    'alert-success': chambresLibres >= 10,
                                    'alert-warning': chambresLibres > 0 && chambresLibres < 10,
                                    'alert-danger': chambresLibres === 0
                                }" style="border-radius: 10px; padding: 15px;">
                                    <div class="d-flex align-items-center">
                                        <i class="fa-solid fa-bed me-3" style="color: #1F7C43; font-size: 24px;"></i>
                                        <div>
                                            <strong x-text="chambresLibres"></strong> chambres disponibles dans ce bâtiment
                                            <div style="font-size: var(--app-font-min, 15px); color: #666; margin-top: 5px;">
                                                Dernière mise à jour: {{ date('d/m/Y H:i') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="responsable" class="form-label">Responsable</label>
                                <input type="text" name="responsable" id="responsable" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <input type="text" name="telephone" id="telephone" class="form-control">
                            </div>

                            <div class="col-md-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea name="description" id="description" rows="2" class="form-control"></textarea>
                            </div>

                            <div class="col-12 mt-4">
                                <button class="btn btn-primary float-end" type="submit">
                                    <i class="fa-solid fa-save me-2"></i> Enregistrer le bâtiment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .custom-modal-lg .custom-modal-container {
            max-width: 600px;
        }

        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }

        .alert-danger {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // === OUVERTURE DU MODAL ===
            document.querySelectorAll('[data-open-modal]').forEach(button => {
                button.addEventListener('click', function () {
                    const modalId = this.getAttribute('data-open-modal');
                    const modal = document.getElementById('customModal' + modalId);
                    if (modal) {
                        modal.classList.add('show');
                        document.body.style.overflow = 'hidden';
                    }
                });
            });

            // === FERMETURE DU MODAL ===
            document.querySelectorAll('[data-close-modal]').forEach(button => {
                button.addEventListener('click', function () {
                    const modalId = this.getAttribute('data-close-modal');
                    const modal = document.getElementById('customModal' + modalId);
                    if (modal) {
                        modal.classList.remove('show');
                        document.body.style.overflow = '';
                    }
                });
            });

            // === FERMETURE LORS DU CLIC SUR L'OVERLAY ===
            document.querySelectorAll('.custom-modal-overlay').forEach(overlay => {
                overlay.addEventListener('click', function () {
                    const modal = this.closest('.custom-modal');
                    modal.classList.remove('show');
                    document.body.style.overflow = '';
                });
            });

            // === FERMETURE AVEC LA TOUCHE ÉCHAP ===
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.custom-modal.show').forEach(modal => {
                        modal.classList.remove('show');
                    });
                    document.body.style.overflow = '';
                }
            });
        });
    </script>
@endpush