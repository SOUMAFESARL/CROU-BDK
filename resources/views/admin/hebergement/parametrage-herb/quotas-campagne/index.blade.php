@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'parametrage-herb'
])

@section('title', 'Paramétrage des quotas')

@section('content')
    <div class="page-body">
        <x-breadcrumbs
                title="Paramétrage des quotas"
                :items="[
                'Gestion Hébergement' => route('admin.hebergement.index'),
                'Paramétrage du module hébergement' => route('admin.hebergement.parametrage-herb.index'),
                'Paramétrage des quotas' => null,
            ]"
        />

        @php
            use Illuminate\Support\Js;

            // Données statiques pour les quotas par campagne
            $quotas = collect([
                (object)[
                    'id' => 1,
                    'campagne' => '2024-2025',
                    'total_chambres' => 780,
                    'repartition' => (object)[
                        'individuelle' => 250,
                        'double' => 380,
                        'multiple' => 150,
                        'pmr' => 45
                    ],
                    'quotas' => (object)[
                        'boursiers' => 70,
                        'non_boursiers' => 30,
                        'pmr' => 5,
                        'cas_sociaux' => 10,
                        'internationaux' => 15,
                        'filles' => 50,
                        'garcons' => 50
                    ],
                    'statut' => 'actif',
                    'date_modification' => '2024-05-15'
                ],
                (object)[
                    'id' => 2,
                    'campagne' => '2023-2024',
                    'total_chambres' => 780,
                    'repartition' => (object)[
                        'individuelle' => 250,
                        'double' => 380,
                        'multiple' => 150,
                        'pmr' => 45
                    ],
                    'quotas' => (object)[
                        'boursiers' => 65,
                        'non_boursiers' => 35,
                        'pmr' => 8,
                        'cas_sociaux' => 12,
                        'internationaux' => 10,
                        'filles' => 50,
                        'garcons' => 50
                    ],
                    'statut' => 'termine',
                    'date_modification' => '2023-05-16'
                ],
                (object)[
                    'id' => 3,
                    'campagne' => '2025-2026',
                    'total_chambres' => 850,
                    'repartition' => (object)[
                        'individuelle' => 300,
                        'double' => 400,
                        'multiple' => 150,
                        'pmr' => 50
                    ],
                    'quotas' => (object)[
                        'boursiers' => 75,
                        'non_boursiers' => 25,
                        'pmr' => 6,
                        'cas_sociaux' => 15,
                        'internationaux' => 20,
                        'filles' => 48,
                        'garcons' => 52
                    ],
                    'statut' => 'planifie',
                    'date_modification' => '2024-05-17'
                ]
            ]);

            // Statistiques
            $stats = [
                'total_campagnes' => $quotas->count(),
                'campagnes_actives' => $quotas->where('statut', 'actif')->count(),
                'total_chambres' => $quotas->firstWhere('statut', 'actif')->total_chambres ?? 0,
                'moyenne_boursiers' => round($quotas->avg(fn($q) => $q->quotas->boursiers))
            ];
        @endphp

        <div
                class="overflow-hidden rounded-[20px] bg-white shadow-[0_8px_24px_rgba(0,0,0,0.08)]"
                x-data="{
                items: {{ Js::from($quotas) }},
                search: '',
                currentPage: 1,
                perPage: 10,

                get filteredItems() {
                    if (!this.search) {
                        return this.items;
                    }
                    return this.items.filter(item => {
                        return item.campagne.toLowerCase().includes(this.search.toLowerCase()) ||
                               item.statut.toLowerCase().includes(this.search.toLowerCase());
                    });
                },

                get totalPages() {
                    return Math.ceil(this.filteredItems.length / this.perPage) || 1;
                },

                get paginatedItems() {
                    const start = (this.currentPage - 1) * this.perPage;
                    return this.filteredItems.slice(start, start + this.perPage);
                },

                openAddModal() {
                    this.resetForm();
                    const modal = document.getElementById('customModaladd');
                    if (modal) {
                        modal.classList.add('show');
                        document.body.style.overflow = 'hidden';
                    }
                },

                editQuota(id) {
                    const quota = this.items.find(q => q.id === id);
                    if (quota) {
                        document.getElementById('campagne').value = quota.campagne;
                        document.getElementById('boursiers').value = quota.quotas.boursiers;
                        document.getElementById('non_boursiers').value = quota.quotas.non_boursiers;
                        document.getElementById('pmr').value = quota.quotas.pmr;
                        document.getElementById('cas_sociaux').value = quota.quotas.cas_sociaux;
                        document.getElementById('internationaux').value = quota.quotas.internationaux;
                        document.getElementById('filles').value = quota.quotas.filles;
                        document.getElementById('garcons').value = quota.quotas.garcons;

                        this.loadRepartitionChambres(quota.campagne);

                        document.getElementById('modalTitle').textContent = 'Modifier les quotas - ' + quota.campagne;
                        document.getElementById('quotaId').value = id;
                    }

                    const modal = document.getElementById('customModaladd');
                    if (modal) {
                        modal.classList.add('show');
                        document.body.style.overflow = 'hidden';
                    }
                },

                resetForm() {
                    document.getElementById('quotaForm').reset();
                    document.getElementById('modalTitle').textContent = 'Nouveaux quotas pour campagne';
                    document.getElementById('quotaId').value = '';
                    document.getElementById('totalChambresInfo').style.display = 'none';
                    document.getElementById('total_quotas').value = '100%';
                    document.getElementById('total_sexe').value = '100%';
                    document.getElementById('totalWarning').style.display = 'none';
                    document.getElementById('totalSexeWarning').style.display = 'none';
                },

                loadRepartitionChambres(campagne) {
                    if (campagne) {
                        document.getElementById('totalChambres').textContent =
                            campagne === '2024-2025' ? '780' :
                            (campagne === '2025-2026' ? '850' : '920');
                        document.getElementById('nbIndividuelle').textContent =
                            campagne === '2024-2025' ? '250' :
                            (campagne === '2025-2026' ? '300' : '320');
                        document.getElementById('nbDouble').textContent =
                            campagne === '2024-2025' ? '380' :
                            (campagne === '2025-2026' ? '400' : '430');
                        document.getElementById('nbMultiple').textContent =
                            campagne === '2024-2025' ? '150' :
                            (campagne === '2025-2026' ? '150' : '170');
                        document.getElementById('nbPmr').textContent =
                            campagne === '2024-2025' ? '45' :
                            (campagne === '2025-2026' ? '50' : '55');

                        document.getElementById('totalChambresInfo').style.display = 'block';
                    } else {
                        document.getElementById('totalChambresInfo').style.display = 'none';
                    }
                },

                deleteQuota(id) {
                    if (confirm('Êtes-vous sûr de vouloir retirer ces quotas ? Cette action est irréversible.')) {
                        this.items = this.items.filter(item => item.id !== id);
                        alert('Quotas retirés avec succès');
                    }
                },

                updateTotal() {
                    const boursiers = parseInt(document.getElementById('boursiers').value) || 0;
                    const nonBoursiers = parseInt(document.getElementById('non_boursiers').value) || 0;
                    const pmr = parseInt(document.getElementById('pmr').value) || 0;
                    const casSociaux = parseInt(document.getElementById('cas_sociaux').value) || 0;
                    const internationaux = parseInt(document.getElementById('internationaux').value) || 0;

                    const total = boursiers + nonBoursiers + pmr + casSociaux + internationaux;
                    document.getElementById('total_quotas').value = total + '%';

                    const warning = document.getElementById('totalWarning');
                    if (total !== 100) {
                        warning.style.display = 'block';
                    } else {
                        warning.style.display = 'none';
                    }
                },

                updateTotalSexe() {
                    const filles = parseInt(document.getElementById('filles').value) || 0;
                    const garcons = parseInt(document.getElementById('garcons').value) || 0;

                    const total = filles + garcons;
                    document.getElementById('total_sexe').value = total + '%';

                    const warningSexe = document.getElementById('totalSexeWarning');
                    if (total !== 100) {
                        warningSexe.style.display = 'block';
                    } else {
                        warningSexe.style.display = 'none';
                    }
                },

                submitForm() {
                    const totalQuotas = parseInt(document.getElementById('total_quotas').value) || 0;
                    const totalSexe = parseInt(document.getElementById('total_sexe').value) || 0;

                    if (totalQuotas !== 100) {
                        alert('Le total des quotas doit être égal à 100%');
                        return;
                    }

                    if (totalSexe !== 100) {
                        alert('La répartition filles/garçons doit être égale à 100%');
                        return;
                    }

                    alert('Quotas enregistrés avec succès pour la campagne');

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
                    <h3 class="text-lg font-semibold text-gray-800">Gestion des quotas par campagne</h3>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <input
                            type="text"
                            placeholder="Rechercher une campagne..."
                            x-model="search"
                            @input="currentPage = 1"
                            class="h-11 w-full rounded-[20px] border border-gray-300 px-4 text-sm focus:ring-2 focus:ring-blue-500 sm:w-[200px] lg:w-[260px]"
                    >
                    <div class="flex items-center gap-2">
                        <button @click="openAddModal()" class="btn btn-primary">+ Nouveaux quotas</button>
                    </div>
                </div>
            </div>

            <!-- ================= STATISTIQUES ================= -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-4">
                <div class="bg-blue-50 p-4 rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total campagnes</p>
                            <p class="text-xl font-semibold mt-1">{{ $stats['total_campagnes'] }}</p>
                        </div>
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-calendar text-blue-600"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-green-50 p-4 rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Campagnes actives</p>
                            <p class="text-xl font-semibold mt-1 text-green-600">{{ $stats['campagnes_actives'] }}</p>
                        </div>
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-play text-green-600"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-purple-50 p-4 rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total chambres</p>
                            <p class="text-xl font-semibold mt-1">{{ $stats['total_chambres'] }}</p>
                        </div>
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-door-open text-purple-600"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-orange-50 p-4 rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Moyenne boursiers</p>
                            <p class="text-xl font-semibold mt-1">{{ $stats['moyenne_boursiers'] }}%</p>
                        </div>
                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-graduation-cap text-orange-600"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================= TABLEAU ================= -->
            <div x-cloak class="table-responsive-container">
                <table class="w-full table-auto">
                    <thead>
                    <tr class="sticky-header border-b text-xs text-gray-500">
                        <th class="px-4 py-4">Campagne</th>
                        <th class="px-4 py-4">Total chambres</th>
                        <th class="px-4 py-4">Répartition</th>
                        <th class="px-4 py-4">Boursiers</th>
                        <th class="px-4 py-4">Non boursiers</th>
                        <th class="px-4 py-4">PMR</th>
                        <th class="px-4 py-4">Statut</th>
                        <th class="px-4 py-4">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    <template x-for="quota in paginatedItems" :key="quota.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium" x-text="quota.campagne"></td>
                            <td class="px-4 py-3" x-text="quota.total_chambres"></td>
                            <td class="px-4 py-3">
                                <div class="text-xs">
                                    <div>Indiv: <span x-text="quota.repartition.individuelle"></span></div>
                                    <div>Double: <span x-text="quota.repartition.double"></span></div>
                                    <div>Multiple: <span x-text="quota.repartition.multiple"></span></div>
                                    <div class="text-purple-600">PMR: <span x-text="quota.repartition.pmr"></span></div>
                                </div>
                            </td>
                            <td class="px-4 py-3 font-medium text-green-600" x-text="quota.quotas.boursiers + '%'"></td>
                            <td class="px-4 py-3 text-gray-600" x-text="quota.quotas.non_boursiers + '%'"></td>
                            <td class="px-4 py-3 text-purple-600" x-text="quota.quotas.pmr + '%'"></td>
                            <td class="px-4 py-3">
                                <span
                                        class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                                        :class="{
                                        'bg-green-100 text-green-700': quota.statut === 'actif',
                                        'bg-gray-100 text-gray-700': quota.statut === 'termine',
                                        'bg-blue-100 text-blue-700': quota.statut === 'planifie'
                                    }"
                                        x-text="quota.statut"
                                ></span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center space-x-2">
                                    <button @click="editQuota(quota.id)"
                                            class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button @click="deleteQuota(quota.id)"
                                            class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template x-if="paginatedItems.length === 0">
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                        <i class="fa-solid fa-chart-pie text-gray-400 text-2xl"></i>
                                    </div>
                                    <p class="mb-2" x-text="search ? 'Aucun quota trouvé pour \"' + search + '\"' : 'Aucun quota trouvé'"></p>
                                    <template x-if="search">
                                        <button @click="search = ''" class="mt-2 text-[#F5A623] hover:underline">
                                            <i class="fa-solid fa-times mr-1"></i>Effacer la recherche
                                        </button>
                                    </template>
                                    <template x-if="!search">
                                        <button @click="openAddModal()" class="mt-2 text-[#1F7C43] hover:underline">
                                            <i class="fa-solid fa-plus mr-1"></i>Ajouter des quotas
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
                <span x-text="filteredItems.length + ' campagne(s)'"></span>

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

        <!-- ================= MODAL D'AJOUT/ÉDITION ================= -->
        <div id="customModaladd" class="custom-modal">
            <div class="custom-modal-overlay" data-close-modal="add"></div>
            <div class="custom-modal-container custom-modal-lg">
                <div class="custom-modal-content">
                    <div class="custom-modal-header">
                        <div class="custom-modal-header-top">
                            <h5 class="custom-modal-title" id="modalTitle">Nouveaux quotas pour campagne</h5>
                            <button type="button" data-close-modal="add" class="custom-modal-close">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="custom-modal-body">
                        <form id="quotaForm" class="row g-3 needs-validation" novalidate @submit.prevent="submitForm">
                            @csrf
                            <input type="hidden" id="quotaId" name="id">

                            <!-- Campagne -->
                            <div class="col-md-12">
                                <label for="campagne" class="form-label">Campagne <strong class="text-danger">*</strong></label>
                                <select name="campagne" id="campagne" class="form-control" required
                                        @change="loadRepartitionChambres($event.target.value)">
                                    <option value="">Sélectionner une campagne</option>
                                    <option value="2024-2025">2024-2025</option>
                                    <option value="2025-2026">2025-2026</option>
                                    <option value="2026-2027">2026-2027</option>
                                </select>
                            </div>

                            <!-- Information sur le total des chambres -->
                            <div id="totalChambresInfo" class="col-md-12" style="display: none;">
                                <div class="alert alert-info" style="background-color: #e6f3ff; border: 1px solid #b8daff; border-radius: 10px; padding: 15px;">
                                    <div class="d-flex align-items-center">
                                        <i class="fa-solid fa-door-open me-3" style="color: #1F7C43; font-size: 24px;"></i>
                                        <div>
                                            <strong id="totalChambres">0</strong> chambres disponibles pour cette campagne
                                            <div style="font-size: var(--app-font-min, 15px); color: #666; margin-top: 5px;">Toutes résidences confondues</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Répartition par type de chambre -->
                            <div class="col-md-12">
                                <label class="form-label">Répartition des chambres</label>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="border rounded p-2 text-center">
                                            <span class="text-sm">Individuelle</span>
                                            <div class="font-semibold" id="nbIndividuelle">250</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="border rounded p-2 text-center">
                                            <span class="text-sm">Double</span>
                                            <div class="font-semibold" id="nbDouble">380</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="border rounded p-2 text-center">
                                            <span class="text-sm">Multiple</span>
                                            <div class="font-semibold" id="nbMultiple">150</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="border rounded p-2 text-center bg-purple-50">
                                            <span class="text-sm">PMR</span>
                                            <div class="font-semibold text-purple-600" id="nbPmr">45</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Quotas principaux -->
                            <div class="col-md-6">
                                <label for="boursiers" class="form-label">Boursiers <strong class="text-danger">*</strong></label>
                                <div class="input-group">
                                    <input type="number" name="boursiers" id="boursiers" class="form-control"
                                           min="0" max="100" required @input="updateTotal()">
                                    <span class="input-group-text ms-1">%</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="non_boursiers" class="form-label">Non boursiers <strong class="text-danger">*</strong></label>
                                <div class="input-group">
                                    <input type="number" name="non_boursiers" id="non_boursiers" class="form-control"
                                           min="0" max="100" required @input="updateTotal()">
                                    <span class="input-group-text ms-1">%</span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label for="pmr" class="form-label">PMR</label>
                                <div class="input-group">
                                    <input type="number" name="pmr" id="pmr" class="form-control"
                                           min="0" max="100" value="5" @input="updateTotal()">
                                    <span class="input-group-text ms-1">%</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="cas_sociaux" class="form-label">Cas sociaux</label>
                                <div class="input-group">
                                    <input type="number" name="cas_sociaux" id="cas_sociaux" class="form-control"
                                           min="0" max="100" value="10" @input="updateTotal()">
                                    <span class="input-group-text ms-1">%</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="internationaux" class="form-label">Internationaux</label>
                                <div class="input-group">
                                    <input type="number" name="internationaux" id="internationaux" class="form-control"
                                           min="0" max="100" value="15" @input="updateTotal()">
                                    <span class="input-group-text ms-1">%</span>
                                </div>
                            </div>

                            <!-- Total quotas -->
                            <div class="col-md-12">
                                <label class="form-label">Total quotas</label>
                                <input type="text" id="total_quotas" class="form-control" value="100%" readonly style="background-color: #f8f9fa;">
                            </div>

                            <!-- Alerte quotas -->
                            <div class="col-md-12">
                                <div id="totalWarning" class="alert alert-warning" style="display: none;">
                                    <i class="fa-solid fa-exclamation-triangle me-2"></i> Le total des quotas doit être égal à 100%
                                </div>
                            </div>

                            <!-- Répartition filles/garçons -->
                            <div class="col-md-12">
                                <hr class="my-3">
                                <h6 class="font-semibold mb-3">Répartition par genre</h6>
                            </div>

                            <div class="col-md-6">
                                <label for="filles" class="form-label">Filles</label>
                                <div class="input-group">
                                    <input type="number" name="filles" id="filles" class="form-control"
                                           min="0" max="100" value="50" @input="updateTotalSexe()">
                                    <span class="input-group-text ms-1">%</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="garcons" class="form-label">Garçons</label>
                                <div class="input-group">
                                    <input type="number" name="garcons" id="garcons" class="form-control"
                                           min="0" max="100" value="50" @input="updateTotalSexe()">
                                    <span class="input-group-text ms-1">%</span>
                                </div>
                            </div>

                            <!-- Total genre -->
                            <div class="col-md-12">
                                <label class="form-label">Total répartition genre</label>
                                <input type="text" id="total_sexe" class="form-control" value="100%" readonly style="background-color: #f8f9fa;">
                            </div>

                            <!-- Alerte genre -->
                            <div class="col-md-12">
                                <div id="totalSexeWarning" class="alert alert-warning" style="display: none;">
                                    <i class="fa-solid fa-exclamation-triangle me-2"></i> La répartition filles/garçons doit être égale à 100%
                                </div>
                            </div>

                            <div class="col-12 mt-4">
                                <button class="btn btn-primary float-end" type="submit">
                                    <i class="fa-solid fa-save me-2"></i> Enregistrer les quotas
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
            max-width: 700px;
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