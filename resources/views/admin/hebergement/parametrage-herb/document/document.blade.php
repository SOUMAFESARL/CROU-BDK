@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'parametrage-herb'
])

@section('title', 'Document')

@section('content')
    <div class="page-body">
        <x-breadcrumbs
                title="Document "
                :items="[
                    'Gestion Hébergement' => route('admin.hebergement.index'),
                    'Paramétrage du module hébergement' => route('admin.hebergement.parametrage-herb.index'),
                    'Document' => null,
                ]"
        />

        @php
            use Illuminate\Support\Js;
        @endphp

        @php
            $document = $document ?? collect();
        @endphp

        <div
                class="overflow-hidden rounded-[20px] bg-white shadow-[0_8px_24px_rgba(0,0,0,0.08)]"
                x-data="{
                    items: {{ Js::from($document) }},
                    search: '',
                    viewMode: 'table',
                    selectedCampus: 'All',
                    selectedCity: 'All',
                    selectedBuilding: 'All',
                    currentPage: 1,
                    perPage: 25,
                    openActionMenu: null,
                    actionMenuPosition: { x: 0, y: 0 },

                    // Variable pour gérer le modal ouvert
                    openModalId: null,

                    get filteredItems() {
                        return this.items.filter(item => {
                            const searchOk =
                                !this.search ||
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

                    // Méthodes pour les modals
                    openModal(modalId) {
                        this.openModalId = modalId;
                        const modal = document.getElementById('customModal' + modalId);
                        if (modal) {
                            modal.classList.add('show');
                            document.body.style.overflow = 'hidden';
                        }
                    },

                    closeModal(modalId) {
                        const modal = document.getElementById('customModal' + modalId);
                        if (modal) {
                            modal.classList.remove('show');
                        }
                        this.openModalId = null;
                        document.body.style.overflow = '';
                    },

                    // Fermer tous les modals
                    closeAllModals() {
                        this.openModalId = null;
                        document.querySelectorAll('.custom-modal').forEach(modal => {
                            modal.classList.remove('show');
                        });
                        document.body.style.overflow = '';
                    },

                    // Ouvrir le modal d'ajout
                    openAddModal() {
                        this.openModal('add');
                    },

                    // Fermer le modal d'ajout
                    closeAddModal() {
                        this.closeModal('add');
                    },

                    // Pour les actions des cartes
                    openActions(typeId, event) {
                        event.stopPropagation();
                        if (this.openActionMenu === typeId) {
                            this.openActionMenu = null;
                        } else {
                            this.openActionMenu = typeId;
                            const button = event.currentTarget;
                            const rect = button.getBoundingClientRect();
                            const menuWidth = 120;

                            const buttonCenterX = rect.left + rect.width / 2;
                            const buttonBottom = rect.bottom;

                            this.actionMenuPosition = {
                                x: buttonCenterX - menuWidth / 2,
                                y: buttonBottom + 8,
                                buttonCenterX: buttonCenterX
                            };
                        }
                    },

                    closeActionMenu() {
                        this.openActionMenu = null;
                    },


                }"
                @click.away="closeActionMenu()"
                @keydown.escape.window="closeAllModals()"
        >

            <!-- ================= HEADER ================= -->
            <div class="flex flex-col gap-4 border-b px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <!-- Bouton retour -->
                    @adminroute('admin.hebergement.parametrage-herb.index')
                    <a href="{{ route('admin.hebergement.parametrage-herb.index') }}"
                       class="inline-flex items-center gap-2 rounded-full border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                        <i class="fas fa-arrow-left"></i>
                        Retour
                    </a>
                    @endadminroute

                    <h3 class="text-lg font-semibold text-gray-800">
                        Gestion des types de document
                    </h3>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <!-- Barre de recherche -->
                    <input
                            type="text"
                            placeholder="Rechercher un type de document..."
                            x-model="search"
                            @input="currentPage = 1"
                            class="h-11 w-full rounded-[20px] border border-gray-300 px-4 text-sm focus:ring-2 focus:ring-blue-500 sm:w-[200px] lg:w-[260px]"
                    >

                    <div class="flex items-center gap-2">
                        <!-- Bouton Ajouter -->
                        <button @click="openAddModal()" class="btn btn-primary">+ Ajouter</button>
                    </div>
                </div>
            </div>


            <!-- ================= TABLEAU ================= -->
            <div x-show="viewMode==='table'" x-cloak class="table-responsive-container">
                <table class="w-full table-auto">
                    <thead>
                    <tr class="sticky-header border-b text-xs text-gray-500">
                        <th class="px-4 py-4">Nom</th>
                        <th class="px-4 py-4">Description</th>
                        <th class="px-4 py-4">Ordre d'affichage</th>
                        <th class="px-4 py-4">Est_requis</th>
                        <th class="px-4 py-4">Statut</th>

                        <th class="px-4 py-4">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    <template x-for="type in paginatedItems" :key="type.id_document">
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium" x-text="type.name"></td>
                            <td class="px-4 py-3" x-text="type.description"></td>
                            <td class="px-4 py-3" x-text="type.ordre_affiche"></td>
                            <td class="px-4 py-3" x-text="type.champs_requis"></td>
                            <td class="px-4 py-3" x-text="type.statut"></td>

                            <td class="px-4 py-3">
                                <div class="flex items-center space-x-2">
                                    <!-- Bouton Modifier -->
                                    <button :data-open-modal="type.id_document" class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-edit"></i>
                                    </button>



                                    <!-- Bouton Supprimer -->
{{--                                    <button :data-open-modal="'delete' + type.id_document" class="text-red-600 hover:text-red-900" title="Supprimer">--}}
{{--                                        <i class="fas fa-trash"></i>--}}
{{--                                    </button>--}}
                                </div>
                            </td>
                        </tr>
                    </template>
                    </tbody>
                </table>
            </div>



            <!-- ================= PAGINATION ================= -->
            <div class="flex items-center justify-between border-t px-4 py-4 text-sm">
                <span x-text="filteredItems.length + ' résultat(s)'"></span>

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

        {{-- Modal pour l'ajout --}}
        <div id="customModaladd" class="custom-modal">
            <div class="custom-modal-overlay" data-close-modal="add"></div>
            <div class="custom-modal-container">
                <div class="custom-modal-content">
                    <div class="custom-modal-header">
                        <div class="custom-modal-header-top">
                            <h5 class="custom-modal-title">Ajouter un type de document</h5>
                            <button type="button" data-close-modal="add" class="custom-modal-close">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="custom-modal-body">
                        @adminroute('admin.hebergement.parametrage-herb.document.store')
                        <form class="row g-3 needs-validation" novalidate method="POST"
                              action="{{ route('admin.hebergement.parametrage-herb.document.store') }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Nom <strong class="text-danger">*</strong></label>
                                    <input type="text" class="form-control" name="name" id="name"
                                           placeholder="Saisir le nom du type" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="name" class="form-label">Période de validité</label>
                                    <select name="delai_validite_mois" id="delai_validite_mois" class="form-control">
                                        <option value="">-- Sélectionner --</option>
                                        <option value="24" {{ old('delai_validite_mois') == 24 ? 'selected' : '' }}>Moins de 2 ans</option>
                                        <option value="6" {{ old('delai_validite_mois') == 6 ? 'selected' : '' }}>Moins de 6 mois</option>
                                        <option value="3" {{ old('delai_validite_mois') == 3 ? 'selected' : '' }}>Moins de 3 mois</option>
                                    </select>
                                </div>

                                <div class="col-md-6  mt-3">
                                    <label for="name" class="form-label">Ordre d'affichage <strong class="text-danger">*</strong></label>
                                    <input type="number" min="0" class="form-control" name="ordre_affiche" id="ordre_affiche" value="0"
                                           placeholder="Saisir l'ordre d'affichage" required>
                                </div>

                                <div class="col-md-6 mt-5">
                                    <div class="form-check">
                                        <input class="form-check-input" name="champs_requis" type="checkbox"  id="checkIndeterminateDisabled" >
                                        <label class="form-check-label" for="checkIndeterminateDisabled">
                                            Ce document est obligatoire
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-12 mt-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" name="description" id="description"
                                              rows="3" placeholder="Description du type du document"></textarea>
                                </div>
                            </div>




                            <div class="col-12 mt-3">
                                <button class="btn btn-primary float-end" type="submit">Enregistrer</button>
                            </div>
                        </form>
                        @endadminroute
                    </div>
                </div>
            </div>
        </div>

        {{-- Modals pour l'édition --}}
        @foreach($document as $item)

            <div id="customModal{{ $item->id_document }}" class="custom-modal">
                <div class="custom-modal-overlay" data-close-modal="{{ $item->id_document }}"></div>
                <div class="custom-modal-container">
                    <div class="custom-modal-content">
                        <div class="custom-modal-header">
                            <div class="custom-modal-header-top">
                                <h5 class="custom-modal-title">Modifier le type de document : {{ $item->name }}</h5>
                                <button type="button" data-close-modal="{{ $item->id_document }}" class="custom-modal-close">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="custom-modal-body">
                            @adminroute('admin.hebergement.parametrage-herb.document.update')
                            <form class="row g-3 needs-validation" novalidate method="POST"
                                  action="{{ route('admin.hebergement.parametrage-herb.document.update', ['document' => $item->id_document ] ) }}">
                                @csrf
                                @method('PUT')


                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="name{{ $item->id_document }}" class="form-label">Nom <strong class="text-danger">*</strong></label>
                                        <input type="text" class="form-control" name="name"
                                               id="name{{ $item->id_document }}" value="{{ $item->name }}" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="name{{ $item->id_document }}" class="form-label">Période de validité</label>
                                        <select name="delai_validite_mois" id="delai_validite_mois" class="form-control">
                                            <option value="">-- Sélectionner --</option>
                                            <option value="24" {{ old('delai_validite_mois') == 24 ? 'selected' : '' }}>Moins de 2 ans</option>
                                            <option value="6" {{ old('delai_validite_mois') == 6 ? 'selected' : '' }}>Moins de 6 mois</option>
                                            <option value="3" {{ old('delai_validite_mois') == 3 ? 'selected' : '' }}>Moins de 3 mois</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6  mt-3">
                                        <label for="ordre_affiche{{ $item->id_document }}" class="form-label">Ordre d'affichage </label>
                                        <input type="number" min="0" class="form-control" name="ordre_affiche" id="ordre_affiche{{ $item->id_document }}" value="{{ $item->ordre_affiche}}"
                                               placeholder="Saisir l'ordre d'affichage" >
                                    </div>

                                    <div class="col-md-3 mt-5">
                                        <div class="form-check">
                                            <input class="form-check-input" name="champs_requis" {{ $item->champs_requis==1 ? 'checked' :'' }} type="checkbox"  id="champs_requis{{ $item->id_document }}" >
                                            <label class="form-check-label" for="champs_requis{{ $item->id_document }}">
                                                Ce document est obligatoire
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mt-5">
                                        <div class="form-check">
                                            <input class="form-check-input" name="statut" {{ $item->statut==1 ? 'checked' :'' }} type="checkbox"  id="statut{{ $item->id_document }}" >
                                            <label class="form-check-label" for="statut{{ $item->id_document }}">
                                                {{$item->statut ==1 ? 'Désactiver ce document' : 'Activer ce document'}}
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-12 mt-3">
                                        <label for="description{{ $item->id_document }}" class="form-label">Description</label>
                                        <textarea class="form-control" name="description" id="description{{ $item->id_document }}"
                                                  rows="3">{{ $item->description }}</textarea>
                                    </div>
                                </div>






                                <div class="col-12 mt-3">
                                    <button class="btn btn-primary float-end" type="submit">Mettre à jour</button>
                                </div>
                            </form>
                            @endadminroute
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Modals pour la suppression --}}
{{--        @foreach($document as $item)--}}
{{--            <div id="customModaldelete{{ $item->id_type_cite }}" class="custom-modal">--}}
{{--                <div class="custom-modal-overlay" data-close-modal="delete{{ $item->id_type_cite }}"></div>--}}
{{--                <div class="custom-modal-container">--}}
{{--                    <div class="custom-modal-content">--}}
{{--                        <div class="custom-modal-header">--}}
{{--                            <div class="custom-modal-header-top">--}}
{{--                                <h5 class="custom-modal-title text-danger">Supprimer le type de cité : {{ $item->name }}</h5>--}}
{{--                                <button type="button" data-close-modal="delete{{ $item->id_type_cite }}" class="custom-modal-close">--}}
{{--                                    <i class="fas fa-times"></i>--}}
{{--                                </button>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                        <div class="custom-modal-body text-center">--}}
{{--                            <p class="mb-4">--}}
{{--                                Êtes-vous sûr de vouloir supprimer <strong>{{ $item->name }}</strong> ({{ $item->code_type }}) ?--}}
{{--                            </p>--}}
{{--                            <form method="POST" action="{{ route('admin.hebergement.parametrage-herb.document.destroy', $item) }}">--}}
{{--                                @csrf--}}
{{--                                @method('DELETE')--}}
{{--                                <div class="flex justify-center gap-3">--}}
{{--                                    <button type="button" data-close-modal="delete{{ $item->id_type_cite }}" class="btn btn-secondary">--}}
{{--                                        Annuler--}}
{{--                                    </button>--}}
{{--                                    <button type="submit" class="btn btn-danger">--}}
{{--                                        Supprimer--}}
{{--                                    </button>--}}
{{--                                </div>--}}
{{--                            </form>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        @endforeach--}}

    </div>
@endsection

@push('styles')
    <style>
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: var(--app-font-min, 15px);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .badge-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .badge-primary {
            background-color: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .badge-info {
            background-color: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Fonctions utilitaires pour les labels
            window.getCategorieLabel = function(categorie) {
                const categories = {
                    'universitaire': 'Universitaire',
                    'privee': 'Privée',
                    'mixte': 'Mixte',
                    'residence': 'Résidence'
                };
                return categories[categorie] || categorie;
            };

            window.getPublicCibleLabel = function(publicCible) {
                const publics = {
                    'etudiant': 'Étudiant',
                    'enseignant': 'Enseignant',
                    'personnel': 'Personnel',
                    'tous': 'Tous'
                };
                return publics[publicCible] || publicCible;
            };

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