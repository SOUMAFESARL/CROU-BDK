@extends('admin.layouts.app', [
    'activeMenu' => 'parametres',
    'activeSubMenu' => 'villes'
])

@section('title', 'Gestion Parametrage | Gestion des villes')

@section('content')
    <div class="page-body">
        <x-breadcrumbs
                title="Gestion des villes"
                :items="[
                        'Gestion Parametrage' => route('admin.parametrage.index'),
                        'Gestion des villes' => null,
                    ]"
        />

        @php
            use Illuminate\Support\Js;
        @endphp

        @php
            $rooms = $ville;
        @endphp

        <div
                class="overflow-hidden rounded-[20px] bg-white shadow-[0_8px_24px_rgba(0,0,0,0.08)]"
                x-data="{
        items: {{ Js::from($rooms) }},
        search: '',
        viewMode: 'table',
        selectedCampus: 'All',
        selectedCity: 'All',
        selectedBuilding: 'All',
        currentPage: 1,
        perPage: 25,
        openActionMenu: null,
        actionMenuPosition: { x: 0, y: 0 },

        // Nouvelle variable pour gérer le modal ouvert
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
        modal.classList.remove('show'); // <-- Enlève la classe show !
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
        openActions(roomId, event) {
            event.stopPropagation();
            if (this.openActionMenu === roomId) {
                this.openActionMenu = null;
            } else {
                this.openActionMenu = roomId;
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
        }
    }"
                @click.away="closeActionMenu()"
                @keydown.escape.window="closeAllModals()"
        >

            <!-- ================= HEADER ================= -->
            <div class="flex flex-col gap-4 border-b px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                <h3 class="text-lg font-semibold text-gray-800">
                    Gestion des villes
                </h3>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <!-- Barre de recherche -->
                    <input
                            type="text"
                            placeholder="Rechercher une ville..."
                            x-model="search"
                            @input="currentPage = 1"
                            class="h-11 w-full rounded-[20px] border border-gray-300 px-4 text-sm focus:ring-2 focus:ring-blue-500 sm:w-[200px] lg:w-[260px]"
                    >

                    <div class="flex items-center gap-2">
                        <!-- Bouton Ajouter -->
                        @adminroute('admin.parametrage.countrie.store')
<button data-open-modal="add" class="btn btn-primary">+ Ajouter</button>
@endadminroute


                    </div>
                </div>
            </div>

            <!-- ================= TABLEAU ================= -->
            <div x-show="viewMode==='table'" x-cloak class="table-responsive-container">
                <table class="w-full table-auto">
                    <thead>
                    <tr class="sticky-header border-b text-xs text-gray-500">
                        <th class="px-4 py-4">Name</th>
                        <th class="px-4 py-4">Region</th>
                        <th class="px-4 py-4">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    <template x-for="room in paginatedItems" :key="room.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium" x-text="room.name"></td>
                            <td class="px-4 py-3 font-medium" x-text="room.regions.name"></td>

                            <td class="px-4 py-3">
                                <div class="flex items-center space-x-2">
                                    <!-- Change $item->id to room.id -->
                                    @adminroute('admin.parametrage.countrie.update')
<button :data-open-modal="room.id" class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-edit"></i>
                                    </button>
@endadminroute


                                    @adminroute('admin.parametrage.countrie.delete')
<button :data-open-modal="'delete' + room.id" class="text-red-600 hover:text-red-900" title="Supprimer">
                                        <i class="fas fa-trash"></i>
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
                            <h5 class="custom-modal-title">Ajouter une ville</h5>
                            <button type="button" data-close-modal="add" class="custom-modal-close">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="custom-modal-body">
                        @adminroute('admin.parametrage.ville.store')
                        <form class="row g-3 needs-validation" novalidate method="POST" action="{{ route('admin.parametrage.ville.store') }}">
                            @csrf
                            <div class="col-md-12">
                                <label for="validationCustom01" class="form-label">Region <strong class="text-danger">*</strong> </label>
                                <select class="form-control"  name="region_id" id="region_id">
                                    <option value="">Selectionner la region</option>
                                    @foreach($region as $item)
                                        <option value="{{$item->id}}">{{$item->name}}</option>
                                    @endforeach
                                </select>
                                <div class="valid-feedback">
                                    Looks good!
                                </div>
                            </div>

                            <div class="col-md-12">
                                <label for="validationCustom01" class="form-label">Nom <strong class="text-danger">*</strong> </label>
                                <input type="text" class="form-control" name="name" id="validationCustom01" placeholder="Saisir le nom de la ville" required>
                                <div class="valid-feedback">
                                    Looks good!
                                </div>
                            </div>

                            <div class="col-12 mt-3 ">
                                <button class="btn btn-primary float-end" type="submit">Enregistrer</button>
                            </div>
                        </form>
                        @endadminroute
                    </div>
                </div>
            </div>
        </div>

        {{-- Modals pour l'édition --}}
        @foreach($rooms as $item)

            <div id="customModal{{ $item->id }}" class="custom-modal">
                <div class="custom-modal-overlay" data-close-modal="{{ $item->id }}"></div>
                <div class="custom-modal-container">
                    <div class="custom-modal-content">
                        <div class="custom-modal-header">
                            <div class="custom-modal-header-top">
                                <h5 class="custom-modal-title">Modifier la ville : {{ $item->name }}</h5>
                                <button type="button" data-close-modal="{{ $item->id }}" class="custom-modal-close">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="custom-modal-body">
                            @adminroute('admin.parametrage.ville.update')
                            <form class="row g-3 needs-validation" novalidate method="POST" action="{{ route('admin.parametrage.ville.update', $item->id) }}">
                                @csrf
                                @method('PUT')

                                <div class="col-md-12">
                                    <label for="validationCustom01" class="form-label">Region <strong class="text-danger">*</strong> </label>
                                    <select class="form-control"  name="region_id" id="region_id">
                                        <option value="">Selectionner la region</option>
                                        @foreach($region as $items)
                                            <option {{$item->region_id == $items->id ? 'selected':''}} value="{{$items->id}}">{{$items->name}}</option>
                                        @endforeach
                                    </select>
                                    <div class="valid-feedback">
                                        Looks good!
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <label for="validationCustom01{{ $item->id }}" class="form-label">Nom <strong class="text-danger">*</strong> </label>
                                    <input type="text" class="form-control" value="{{ $item->name }}" name="name" id="validationCustom01{{ $item->id }}" placeholder="Saisir le nom de la region" required>
                                    <div class="valid-feedback">
                                        Looks good!
                                    </div>
                                </div>


                                <div class="col-12 mt-3 ">
                                    <button class="btn btn-primary float-end" type="submit">Mettre à jour</button>
                                </div>
                            </form>
                            @endadminroute
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        @foreach($rooms as $item)
            <div id="customModaldelete{{ $item->id }}" class="custom-modal">
                <div class="custom-modal-overlay" data-close-modal="delete{{ $item->id }}"></div>
                <div class="custom-modal-container">
                    <div class="custom-modal-content">
                        <div class="custom-modal-header">
                            <div class="custom-modal-header-top">
                                <h5 class="custom-modal-title text-danger">Supprimer la ville : {{ $item->name }}</h5>
                                <button type="button" data-close-modal="delete{{ $item->id }}" class="custom-modal-close">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="custom-modal-body text-center">
                            <p class="mb-4">
                                Êtes-vous sûr de vouloir supprimer <strong>{{ $item->name }}</strong> ?
                            </p>
                            @adminroute('admin.parametrage.ville.delete')
                            <form method="POST" action="{{ route('admin.parametrage.ville.delete', $item->id) }}">
                                @csrf
                                @method('DELETE')
                                <div class="flex justify-center gap-3">
                                    <button type="button" data-close-modal="delete{{ $item->id }}" class="btn btn-secondary">
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

            // === FERMETURE LORS DU CLIC SUR L’OVERLAY ===
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
