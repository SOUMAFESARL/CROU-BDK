@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'parametrage-herb'
])

@section('title', 'Règles d\'éligibilité')

@section('content')
    <div class="page-body">
        <x-breadcrumbs
                title="Règles d'éligibilité"
                :items="[
                'Gestion Hébergement' => route('admin.hebergement.index'),
                'Paramétrage du module hébergement' => route('admin.hebergement.parametrage-herb.index'),
                'Règles d\'éligibilité' => null,
            ]"
        />

        @php
            use Illuminate\Support\Js;

            // Données statiques pour les règles d'éligibilité par campagne
            $regles = collect([
                (object)[
                    'id' => 1,
                    'campagne' => '2024-2025',
                    'inscription_requise' => true,
                    'distance_minimale' => 50, // km
                    'age_min' => 18,
                    'age_max' => 30,
                    'redoublement_autorise' => false,
                    'moyenne_minimale' => 12, // /20
                    'plafond_revenus' => 50000, // € par an
                    'documents_requis' => [
                        'Relevé de notes',
                        'Acte de naissance',
                        'Attestation de boursier',
                        'Justificatif de domicile'
                    ],
                    'conditions_speciales' => [
                        'Orphelins prioritaires',
                        'PMR accès garantis',
                        'Étudiants internationaux quota spécifique'
                    ],
                    'statut' => 'actif',
                    'date_modification' => '2024-05-15'
                ],
                (object)[
                    'id' => 2,
                    'campagne' => '2025-2026',
                    'inscription_requise' => true,
                    'distance_minimale' => 40,
                    'age_min' => 18,
                    'age_max' => 32,
                    'redoublement_autorise' => true,
                    'moyenne_minimale' => 10,
                    'plafond_revenus' => 55000,
                    'documents_requis' => [
                        'Relevé de notes',
                        'Acte de naissance',
                        'Attestation de boursier'
                    ],
                    'conditions_speciales' => [
                        'PMR accès garantis',
                        'Cas sociaux prioritaires'
                    ],
                    'statut' => 'planifie',
                    'date_modification' => '2024-05-20'
                ]
            ]);
        @endphp

        <div
                class="overflow-hidden rounded-[20px] bg-white shadow-[0_8px_24px_rgba(0,0,0,0.08)]"
                x-data="{
                items: {{ Js::from($regles) }},
                search: '',
                currentPage: 1,
                perPage: 10,

                get filteredItems() {
                    if (!this.search) {
                        return this.items;
                    }
                    return this.items.filter(item => {
                        return item.campagne.toLowerCase().includes(this.search.toLowerCase());
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

                viewRegle(id) {
                    const regle = this.items.find(r => r.id === id);
                    if (regle) {
                        alert('Détails de la règle pour ' + regle.campagne);
                    }
                },

                editRegle(id) {
                    const regle = this.items.find(r => r.id === id);
                    if (regle) {
                        document.getElementById('campagne').value = regle.campagne;
                        document.getElementById('inscription_requise').value = regle.inscription_requise ? '1' : '0';
                        document.getElementById('distance_minimale').value = regle.distance_minimale || '';
                        document.getElementById('age_min').value = regle.age_min;
                        document.getElementById('age_max').value = regle.age_max;
                        document.getElementById('redoublement_autorise').value = regle.redoublement_autorise ? '1' : '0';
                        document.getElementById('moyenne_minimale').value = regle.moyenne_minimale || '';
                        document.getElementById('plafond_revenus').value = regle.plafond_revenus || '';

                        // Gérer les checkboxes des documents
                        document.querySelectorAll('input[name=\'documents[]\']').forEach(cb => {
                            cb.checked = regle.documents_requis.includes(cb.value);
                        });

                        // Gérer les checkboxes des conditions
                        document.querySelectorAll('input[name=\'conditions[]\']').forEach(cb => {
                            cb.checked = regle.conditions_speciales.includes(cb.value);
                        });

                        document.getElementById('modalTitle').textContent = 'Modifier les règles - ' + regle.campagne;
                        document.getElementById('regleId').value = id;
                    }

                    const modal = document.getElementById('customModaladd');
                    if (modal) {
                        modal.classList.add('show');
                        document.body.style.overflow = 'hidden';
                    }
                },

                resetForm() {
                    document.getElementById('regleForm').reset();
                    document.getElementById('modalTitle').textContent = 'Nouvelles règles d\'éligibilité';
                    document.getElementById('regleId').value = '';

                    document.getElementById('inscription_requise').value = '1';
                    document.getElementById('age_min').value = '18';
                    document.getElementById('age_max').value = '30';
                    document.getElementById('redoublement_autorise').value = '0';

                    // Rechecker les documents par défaut
                    document.querySelectorAll('input[name=\'documents[]\']').forEach(cb => {
                        if (cb.value === 'Relevé de notes' || cb.value === 'Acte de naissance' || cb.value === 'Attestation de boursier') {
                            cb.checked = true;
                        } else {
                            cb.checked = false;
                        }
                    });

                    // Rechecker les conditions par défaut
                    document.querySelectorAll('input[name=\'conditions[]\']').forEach(cb => {
                        if (cb.value === 'PMR accès garantis') {
                            cb.checked = true;
                        } else {
                            cb.checked = false;
                        }
                    });
                },

                deleteRegle(id) {
                    if (confirm('Êtes-vous sûr de vouloir retirer ces règles ? Cette action est irréversible.')) {
                        this.items = this.items.filter(item => item.id !== id);
                        alert('Règles retirées avec succès');
                    }
                },

                submitForm() {
                    alert('Règles d\'éligibilité enregistrées avec succès');

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
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Règles d'éligibilité</h3>
                        <p class="text-sm text-gray-500">Définissez les critères d'éligibilité pour chaque campagne</p>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <input
                            type="text"
                            placeholder="Rechercher par campagne..."
                            x-model="search"
                            @input="currentPage = 1"
                            class="h-11 w-full rounded-[20px] border border-gray-300 px-4 text-sm focus:ring-2 focus:ring-blue-500 sm:w-[200px] lg:w-[260px]"
                    >
                    <div class="flex items-center gap-2">
                        <button @click="openAddModal()" class="btn btn-primary">+ Nouvelle règle</button>
                    </div>
                </div>
            </div>

            <!-- ================= TABLEAU ================= -->
            <div x-cloak class="table-responsive-container">
                <table class="w-full table-auto">
                    <thead>
                    <tr class="sticky-header border-b text-xs text-gray-500">
                        <th class="px-4 py-4">#</th>
                        <th class="px-4 py-4">Campagne</th>
                        <th class="px-4 py-4">Distance min</th>
                        <th class="px-4 py-4">Âge</th>
                        <th class="px-4 py-4">Moyenne min</th>
                        <th class="px-4 py-4">Redoublement</th>
                        <th class="px-4 py-4">Plafond revenus</th>
                        <th class="px-4 py-4">Documents requis</th>
                        <th class="px-4 py-4">Statut</th>
                        <th class="px-4 py-4">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    <template x-for="(regle, index) in paginatedItems" :key="regle.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3" x-text="((currentPage-1)*perPage) + index + 1"></td>
                            <td class="px-4 py-3 font-medium" x-text="regle.campagne"></td>
                            <td class="px-4 py-3">
                                <template x-if="regle.distance_minimale">
                                    <span x-text="regle.distance_minimale + ' km'"></span>
                                </template>
                                <template x-if="!regle.distance_minimale">
                                    <span class="text-gray-400">Non définie</span>
                                </template>
                            </td>
                            <td class="px-4 py-3" x-text="regle.age_min + ' - ' + regle.age_max + ' ans'"></td>
                            <td class="px-4 py-3">
                                <template x-if="regle.moyenne_minimale">
                                    <span x-text="regle.moyenne_minimale + '/20'"></span>
                                </template>
                                <template x-if="!regle.moyenne_minimale">
                                    <span class="text-gray-400">Non définie</span>
                                </template>
                            </td>
                            <td class="px-4 py-3">
                                <span x-show="regle.redoublement_autorise"
                                      class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">Autorisé</span>
                                <span x-show="!regle.redoublement_autorise"
                                      class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs">Interdit</span>
                            </td>
                            <td class="px-4 py-3">
                                <template x-if="regle.plafond_revenus">
                                    <span x-text="new Intl.NumberFormat('fr-FR').format(regle.plafond_revenus) + ' €'"></span>
                                </template>
                                <template x-if="!regle.plafond_revenus">
                                    <span class="text-gray-400">Non défini</span>
                                </template>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1 max-w-[200px]">
                                    <template x-for="doc in regle.documents_requis" :key="doc">
                                        <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs whitespace-nowrap" x-text="doc"></span>
                                    </template>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span x-show="regle.statut === 'actif'"
                                      class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-medium">Actif</span>
                                <span x-show="regle.statut === 'planifie'"
                                      class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-medium">Planifié</span>
                                <span x-show="regle.statut === 'termine'"
                                      class="px-3 py-1 rounded-full bg-gray-100 text-gray-700 text-xs font-medium">Terminé</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center space-x-2">
                                    <button @click="viewRegle(regle.id)"
                                            class="text-purple-600 hover:text-purple-900">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button @click="editRegle(regle.id)"
                                            class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button @click="deleteRegle(regle.id)"
                                            class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template x-if="paginatedItems.length === 0">
                        <tr>
                            <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                        <i class="fa-solid fa-scale-balanced text-gray-400 text-2xl"></i>
                                    </div>
                                    <p class="mb-2" x-text="search ? 'Aucune règle trouvée pour \"' + search + '\"' : 'Aucune règle d\'éligibilité trouvée'"></p>
                                    <template x-if="search">
                                        <button @click="search = ''" class="mt-2 text-[#F5A623] hover:underline">
                                            <i class="fa-solid fa-times mr-1"></i>Effacer la recherche
                                        </button>
                                    </template>
                                    <template x-if="!search">
                                        <button @click="openAddModal()" class="mt-2 text-[#1F7C43] hover:underline">
                                            <i class="fa-solid fa-plus mr-1"></i>Ajouter des règles
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
                <span x-text="filteredItems.length + ' règle(s)'"></span>

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
                            <h5 class="custom-modal-title" id="modalTitle">Nouvelles règles d'éligibilité</h5>
                            <button type="button" data-close-modal="add" class="custom-modal-close">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="custom-modal-body">
                        <form id="regleForm" class="row g-3 needs-validation" novalidate @submit.prevent="submitForm">
                            @csrf
                            <input type="hidden" id="regleId" name="id">

                            <!-- Campagne -->
                            <div class="col-md-12">
                                <label for="campagne" class="form-label">Campagne <strong class="text-danger">*</strong></label>
                                <select name="campagne" id="campagne" class="form-control" required>
                                    <option value="">Sélectionner une campagne</option>
                                    <option value="2024-2025">2024-2025</option>
                                    <option value="2025-2026">2025-2026</option>
                                    <option value="2026-2027">2026-2027</option>
                                </select>
                            </div>

                            <!-- Critères académiques -->
                            <div class="col-12">
                                <label class="form-label fw-bold">Critères académiques</label>
                            </div>

                            <div class="col-md-6">
                                <label for="inscription_requise" class="form-label">Inscription requise</label>
                                <select name="inscription_requise" id="inscription_requise" class="form-control">
                                    <option value="1">Oui</option>
                                    <option value="0">Non</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="moyenne_minimale" class="form-label">Moyenne minimale (/20)</label>
                                <input type="number" name="moyenne_minimale" id="moyenne_minimale" class="form-control" min="0" max="20" step="0.5">
                            </div>

                            <div class="col-md-6">
                                <label for="redoublement_autorise" class="form-label">Redoublement autorisé</label>
                                <select name="redoublement_autorise" id="redoublement_autorise" class="form-control">
                                    <option value="1">Oui</option>
                                    <option value="0">Non</option>
                                </select>
                            </div>

                            <!-- Critères géographiques -->
                            <div class="col-12">
                                <label class="form-label fw-bold">Critères géographiques</label>
                            </div>

                            <div class="col-md-6">
                                <label for="distance_minimale" class="form-label">Distance domicile-campus (km)</label>
                                <input type="number" name="distance_minimale" id="distance_minimale" class="form-control" min="0">
                            </div>

                            <!-- Critères démographiques -->
                            <div class="col-12">
                                <label class="form-label fw-bold">Critères démographiques</label>
                            </div>

                            <div class="col-md-4">
                                <label for="age_min" class="form-label">Âge minimum</label>
                                <input type="number" name="age_min" id="age_min" class="form-control" min="16" max="60" value="18">
                            </div>
                            <div class="col-md-4">
                                <label for="age_max" class="form-label">Âge maximum</label>
                                <input type="number" name="age_max" id="age_max" class="form-control" min="16" max="60" value="30">
                            </div>

                            <!-- Critères sociaux -->
                            <div class="col-12">
                                <label class="form-label fw-bold">Critères sociaux</label>
                            </div>

                            <div class="col-md-6">
                                <label for="plafond_revenus" class="form-label">Plafond de revenus familiaux (€/an)</label>
                                <input type="number" name="plafond_revenus" id="plafond_revenus" class="form-control" min="0" step="1000">
                            </div>

                            <!-- Documents requis -->
                            <div class="col-12">
                                <label class="form-label fw-bold">Documents requis</label>
                            </div>

                            <div class="col-md-12">
                                <div class="border rounded p-3">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input type="checkbox" name="documents[]" value="Relevé de notes" class="form-check-input" id="doc_notes" checked>
                                                <label class="form-check-label" for="doc_notes">Relevé de notes</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" name="documents[]" value="Acte de naissance" class="form-check-input" id="doc_naissance" checked>
                                                <label class="form-check-label" for="doc_naissance">Acte de naissance</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" name="documents[]" value="Attestation de boursier" class="form-check-input" id="doc_bourse" checked>
                                                <label class="form-check-label" for="doc_bourse">Attestation de boursier</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input type="checkbox" name="documents[]" value="Justificatif de domicile" class="form-check-input" id="doc_domicile">
                                                <label class="form-check-label" for="doc_domicile">Justificatif de domicile</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" name="documents[]" value="Certificat médical PMR" class="form-check-input" id="doc_pmr">
                                                <label class="form-check-label" for="doc_pmr">Certificat médical PMR</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" name="documents[]" value="Attestation situation sociale" class="form-check-input" id="doc_social">
                                                <label class="form-check-label" for="doc_social">Attestation situation sociale</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Conditions spéciales -->
                            <div class="col-12">
                                <label class="form-label fw-bold">Conditions spéciales</label>
                            </div>

                            <div class="col-md-12">
                                <div class="border rounded p-3">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input type="checkbox" name="conditions[]" value="Orphelins prioritaires" class="form-check-input" id="cond_orphelin">
                                                <label class="form-check-label" for="cond_orphelin">Orphelins prioritaires</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" name="conditions[]" value="PMR accès garantis" class="form-check-input" id="cond_pmr" checked>
                                                <label class="form-check-label" for="cond_pmr">PMR accès garantis</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input type="checkbox" name="conditions[]" value="Cas sociaux prioritaires" class="form-check-input" id="cond_social">
                                                <label class="form-check-label" for="cond_social">Cas sociaux prioritaires</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" name="conditions[]" value="Étudiants internationaux quota spécifique" class="form-check-input" id="cond_international">
                                                <label class="form-check-label" for="cond_international">Étudiants internationaux quota spécifique</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mt-4">
                                <button class="btn btn-primary float-end" type="submit">
                                    <i class="fa-solid fa-save me-2"></i> Enregistrer les règles
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

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