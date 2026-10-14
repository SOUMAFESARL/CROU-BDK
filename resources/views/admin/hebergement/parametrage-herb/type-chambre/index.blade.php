@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'parametrage-herb'
])

@section('title', 'Types de chambre')

@section('content')
    <div class="page-body">
        <x-breadcrumbs
                title="Types de chambre"
                :items="[
                    'Gestion Hébergement' => route('admin.hebergement.index'),
                    'Paramétrage du module hébergement' => route('admin.hebergement.parametrage-herb.index'),
                    'Types de chambre' => null,
                ]"
        />

        @php
            use Illuminate\Support\Js;
        @endphp

        <div
                class="overflow-hidden rounded-[20px] bg-white shadow-[0_8px_24px_rgba(0,0,0,0.08)]"
                x-data="{
                    items: {{ Js::from($chambresData ?? []) }},
                    search: '',
                    currentPage: 1,
                    perPage: 25,
                    openActionMenu: null,
                    actionMenuPosition: { x: 0, y: 0 },
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

                    closeAllModals() {
                        this.openModalId = null;
                        document.querySelectorAll('.custom-modal').forEach(modal => {
                            modal.classList.remove('show');
                        });
                        document.body.style.overflow = '';
                    },

                    openAddModal() {
                        this.openModal('add');
                    },

                    closeAddModal() {
                        this.closeModal('add');
                    },

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
                        Gestion des types de chambre
                    </h3>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <!-- Barre de recherche -->
                    <input
                            type="text"
                            placeholder="Rechercher un type de chambre..."
                            x-model="search"
                            @input="currentPage = 1"
                            class="h-11 w-full rounded-[20px] border border-gray-300 px-4 text-sm focus:ring-2 focus:ring-blue-500 sm:w-[200px] lg:w-[260px]"
                    >

{{--                    <div class="flex items-center gap-2">--}}
{{--                        <!-- Bouton Ajouter -->--}}
{{--                        <button @click="openAddModal()" class="btn btn-primary">+ Ajouter</button>--}}
{{--                    </div>--}}
                </div>
            </div>

            <!-- ================= TABLEAU ================= -->
            <div x-cloak class="table-responsive-container">
                <table class="w-full table-auto">
                    <thead>
                    <tr class="sticky-header border-b text-xs text-gray-500">
                        <th class="px-4 py-4">Nom</th>
                        <th class="px-4 py-4">Description</th>
                        <th class="px-4 py-4">Superficie</th>
                        <th class="px-4 py-4">Montant</th>
                        <th class="px-4 py-4">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    <template x-for="chambre in paginatedItems" :key="chambre.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium" x-text="chambre.nom"></td>
                            <td class="px-4 py-3" x-text="chambre.description || '—'"></td>
                            <td class="px-4 py-3" x-text="chambre.superficie || '—'"></td>
                            <td class="px-4 py-3" x-text="chambre.montant || '—'"></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center space-x-2">
                                    <!-- Bouton Modifier -->
                                    <button @click="openModal('edit' + chambre.id)"
                                            class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <!-- Bouton Supprimer -->
{{--                                    <button @click="openModal('delete' + chambre.id)"--}}
{{--                                            class="text-red-600 hover:text-red-900">--}}
{{--                                        <i class="fas fa-trash"></i>--}}
{{--                                    </button>--}}
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template x-if="paginatedItems.length === 0">
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-gray-500">
                                Aucun type de chambre trouvé
                            </td>
                        </tr>
                    </template>
                    </tbody>
                </table>
            </div>

            <!-- ================= PAGINATION ================= -->
            <div x-show="filteredItems.length > 0" class="flex items-center justify-between border-t px-4 py-4 text-sm">
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
                            <h5 class="custom-modal-title">Ajouter un type de chambre</h5>
                            <button type="button" data-close-modal="add" class="custom-modal-close">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="custom-modal-body">
                        @adminroute('admin.hebergement.parametrage-herb.type-chambre.store')
                        <form class="row g-3 needs-validation" novalidate method="POST"
                              action="{{ route('admin.hebergement.parametrage-herb.type-chambre.store') }}">
                            @csrf

                            <div class="col-md-12">
                                <label for="name" class="form-label">Nom <strong class="text-danger">*</strong></label>
                                <input type="text" class="form-control" name="name" id="name"
                                       placeholder="Saisir le nom du type de chambre" required>
                            </div>

                            <div class="col-md-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" name="description" id="description"
                                          rows="3" placeholder="Description du type de chambre"></textarea>
                            </div>
                            <div class="col-md-12">
                                <label for="superficie" class="form-label">Superficie </label>
                                <input type="number" min="0" class="form-control" name="superficie" id="superficie"
                                       placeholder="Saisir la superficie du type de chambre" >
                            </div>
                            <div class="col-md-12">
                                <label for="montant" class="form-label">Montant <strong class="text-danger">*</strong></label>
                                <input type="number" min="0" class="form-control" name="montant" id="montant"
                                       placeholder="Saisir le montant du type de chambre" required>
                            </div>
                            <div class="col-md-12">
                                <label for="montant" class="form-label">Montant <strong class="text-danger">*</strong></label>
                                <input type="number" min="0" class="form-control" name="montant" id="montant" value="0"
                                       placeholder="Saisir le montant du type de chambre" required>
                            </div>
                            <div class="col-md-12">
                                <label for="caution" class="form-label">Nombre de mois de caution <strong class="text-danger">*</strong></label>
                                <input type="number" min="0" class="form-control" name="caution" id="caution" value="0"
                                       placeholder="Saisir le nombre de mois de caution du type de chambre" required>
                            </div>
                            <div class="col-md-12">
                                <label for="avance" class="form-label">Nombre de mois d'avance <strong class="text-danger">*</strong></label>
                                <input type="number" min="0" class="form-control" name="caution" id="avance" value="0"
                                       placeholder="Saisir le nombre de mois d'avance  du type de chambre" required>
                            </div>

                            <div class="col-md-12 mt-3">
                                <div class="col-md-12">
                                    <label class="form-label" for="inlineCheckbox1">Cocher les équipements disponible dans ce type de chambre</label>
                                </div>
                                <div class="form-check form-check-inline ">
                                    <input class="form-check-input" name="equipement[Chaise]"   type="checkbox" id="Chaise" value="Chaise">
                                    <label class="form-check-label"  for="Chaise">Chaise</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" name="equipement[Table]"  type="checkbox" id="Table" value="Table">
                                    <label class="form-check-label" for="Table">Table</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" name="equipement[Courant]" type="checkbox" id="Courant" value="Courant">
                                    <label class="form-check-label" for="Courant">Courant</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input"  name="equipement[Eau]" type="checkbox" id="Eau" value="Eau">
                                    <label class="form-check-label" for="Eau">Eau</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" name="equipement[Cuisine]" type="checkbox" id="Cuisine" value="Cuisine">
                                    <label class="form-check-label"  for="Cuisine">Cuisine</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" name="equipement[Placard]" type="checkbox" id="Placard" value="Placard">
                                    <label class="form-check-label" for="Placard">Placard</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input"  name="equipement[Matelas]" type="checkbox" id="Matelas" value="Matelas">
                                    <label class="form-check-label" for="Matelas">Matelas</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" name="equipement[Prise]" type="checkbox" id="Prise" value="Prise">
                                    <label class="form-check-label" for="Prise">Prise</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" name="equipement[Douche]" type="checkbox" id="Douche" value="Douche">
                                    <label class="form-check-label" for="Douche">Douche</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" name="equipement[WC]" type="checkbox" id="WC" value="WC">
                                    <label class="form-check-label" for="WC">WC</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" name="equipement[Climatiseur]" type="checkbox" id="Climatiseur" value="Climatiseur" >
                                    <label class="form-check-label" for="Climatiseur">Climatiseur</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" name="equipement[Ventilateur]" type="checkbox" id="Ventilateur" value="Ventilateur" >
                                    <label class="form-check-label" for="Ventilateur">Ventilateur</label>
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
        @if(isset($chambresData) && count($chambresData) > 0)
            @foreach($chambresData as $chambre)
                <div id="customModaledit{{ $chambre['id'] }}" class="custom-modal">
                    <div class="custom-modal-overlay" data-close-modal="edit{{ $chambre['id'] }}"></div>
                    <div class="custom-modal-container">
                        <div class="custom-modal-content">
                            <div class="custom-modal-header">
                                <div class="custom-modal-header-top">
                                    <h5 class="custom-modal-title">Modifier le type de chambre : {{ $chambre['nom'] }}</h5>
                                    <button type="button" data-close-modal="edit{{ $chambre['id'] }}" class="custom-modal-close">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="custom-modal-body">
                                @adminroute('admin.hebergement.parametrage-herb.type-chambre.update')
                                <form class="row g-3 needs-validation" novalidate method="POST"
                                      action="{{ route('admin.hebergement.parametrage-herb.type-chambre.update', $chambre['id']) }}">
                                    @csrf
                                    @method('PUT')

                                    <div class="col-md-12">
                                        <label for="name{{ $chambre['id'] }}" class="form-label">Nom <strong class="text-danger">*</strong></label>
                                        <input type="text" class="form-control" name="name" readonly
                                               id="name{{ $chambre['id'] }}" value="{{ $chambre['nom'] }}" required>
                                    </div>

                                    <div class="col-md-12">
                                        <label for="description{{ $chambre['id'] }}" class="form-label">Description</label>
                                        <textarea class="form-control" name="description" id="description{{ $chambre['id'] }}"
                                                  rows="3">{{ $chambre['description'] }}</textarea>
                                    </div>
                                    <div class="col-md-12">
                                        <label for="name{{ $chambre['id'] }}" class="form-label">Superficie </label>
                                        <input type="number" class="form-control" name="superficie" min="0"
                                               id="name{{ $chambre['superficie'] }}" value="{{ $chambre['superficie'] }}" >
                                    </div>
                                    <div class="col-md-12">
                                        <label for="name{{ $chambre['id'] }}" class="form-label">Montant <strong class="text-danger">*</strong></label>
                                        <input type="number" class="form-control" name="montant" min="0"
                                               id="name{{ $chambre['montant'] }}" value="{{ $chambre['montant'] }}" required>
                                    </div>
                                    <div class="col-md-12">
                                        <label for="caution" class="form-label">Nombre de mois de caution <strong class="text-danger">*</strong></label>
                                        <input type="number" min="0" class="form-control" name="caution" id="caution" value="{{ $chambre['caution'] }}"
                                               placeholder="Saisir le nombre de mois de caution du type de chambre" required>
                                    </div>
                                    <div class="col-md-12">
                                        <label for="avance" class="form-label">Nombre de mois d'avance <strong class="text-danger">*</strong></label>
                                        <input type="number" min="0" class="form-control" name="avance" id="avance" value="{{ $chambre['avance'] }}"
                                               placeholder="Saisir le nombre de mois d'avance  du type de chambre" required>
                                    </div>
@php  $equipement =  json_decode($chambre['equipement'] ,true);

  @endphp
                                    <div class="col-md-12 mt-3">
                                        <div class="col-md-12">
                                            <label class="form-label" for="inlineCheckbox1">Cocher les équipements disponible dans ce type de chambre</label>
                                        </div>
                                        <div class="form-check form-check-inline ">
                                            <input class="form-check-input" name="equipement[Chaise]" {{(!empty($equipement) && isset($equipement['Chaise']) && $equipement['Chaise']) ? 'checked' :''}}  type="checkbox" id="Chaise" value="Chaise">
                                            <label class="form-check-label"  for="Chaise">Chaise</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" name="equipement[Table]" {{(!empty($equipement) && isset($equipement['Table']) && $equipement['Table']) ? 'checked' :''}}  type="checkbox" id="Table" value="Table">
                                            <label class="form-check-label" for="Table">Table</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" name="equipement[Courant]" {{(!empty($equipement) && isset($equipement['Courant']) && $equipement['Courant']) ? 'checked' :''}} type="checkbox" id="Courant" value="Courant">
                                            <label class="form-check-label" for="Courant">Courant</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input"  name="equipement[Eau]" {{(!empty($equipement) && isset($equipement['Eau']) && $equipement['Eau']) ? 'checked' :''}} type="checkbox" id="Eau" value="Eau">
                                            <label class="form-check-label" for="Eau">Eau</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" name="equipement[Cuisine]" {{(!empty($equipement) && isset($equipement['Cuisine']) && $equipement['Cuisine']) ? 'checked' :''}} type="checkbox" id="Cuisine" value="Cuisine">
                                            <label class="form-check-label"  for="Cuisine">Cuisine</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" name="equipement[Placard]" {{(!empty($equipement) && isset($equipement['Placard']) && $equipement['Placard']) ? 'checked' :''}} type="checkbox" id="Placard" value="Placard">
                                            <label class="form-check-label" for="Placard">Placard</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input"  name="equipement[Matelas]" {{(!empty($equipement) && isset($equipement['Matelas']) && $equipement['Matelas']) ? 'checked' :''}}  type="checkbox" id="Matelas" value="Matelas">
                                            <label class="form-check-label" for="Matelas">Matelas</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" name="equipement[Prise]" {{(!empty($equipement) && isset($equipement['Prise']) && $equipement['Prise']) ? 'checked' :''}}  type="checkbox" id="Prise" value="Prise">
                                            <label class="form-check-label" for="Prise">Prise</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" name="equipement[Douche]" {{(!empty($equipement) && isset($equipement['Douche']) && $equipement['Douche']) ? 'checked' :''}} type="checkbox" id="Douche" value="Douche">
                                            <label class="form-check-label" for="Douche">Douche</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" name="equipement[WC]" type="checkbox" {{(!empty($equipement) && isset($equipement['WC']) && $equipement['WC']) ? 'checked' :''}} id="WC" value="WC">
                                            <label class="form-check-label" for="WC">WC</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" name="equipement[Climatiseur]" type="checkbox" {{(!empty($equipement) && isset($equipement['Climatiseur']) && $equipement['Climatiseur']) ? 'checked' :''}} id="Climatiseur" value="Climatiseur" >
                                            <label class="form-check-label" for="Climatiseur">Climatiseur</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" name="equipement[Ventilateur]" type="checkbox" {{(!empty($equipement) && isset($equipement['Ventilateur']) && $equipement['Ventilateur']) ? 'checked' :''}} id="Ventilateur" value="Ventilateur" >
                                            <label class="form-check-label" for="Ventilateur">Ventilateur</label>
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
        @endif

        {{-- Modals pour la suppression --}}
        @if(isset($chambresData) && count($chambresData) > 0)
            @foreach($chambresData as $chambre)
                <div id="customModaldelete{{ $chambre['id'] }}" class="custom-modal">
                    <div class="custom-modal-overlay" data-close-modal="delete{{ $chambre['id'] }}"></div>
                    <div class="custom-modal-container">
                        <div class="custom-modal-content">
                            <div class="custom-modal-header">
                                <div class="custom-modal-header-top">
                                    <h5 class="custom-modal-title text-danger">Supprimer le type de chambre : {{ $chambre['nom'] }}</h5>
                                    <button type="button" data-close-modal="delete{{ $chambre['id'] }}" class="custom-modal-close">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="custom-modal-body text-center">
                                <p class="mb-4">
                                    Êtes-vous sûr de vouloir supprimer <strong>{{ $chambre['nom'] }}</strong> ?
                                </p>
                                @adminroute('admin.hebergement.parametrage-herb.type-chambre.destroy')
                                <form method="POST" action="{{ route('admin.hebergement.parametrage-herb.type-chambre.destroy', $chambre['id']) }}">
                                    @csrf
                                    @method('DELETE')
                                    <div class="flex justify-center gap-3">
                                        <button type="button" data-close-modal="delete{{ $chambre['id'] }}" class="btn btn-secondary">
                                            Annuler
                                        </button>
                                        <button type="submit" class="btn btn-danger">
                                            Supprimer
                                        </button>
                                    </div>
                                </form>
                                @endadminroute
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif

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