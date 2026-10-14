@extends('admin.layouts.app', [
     'activeMenu' => 'etudiant',

])
@section('title', 'Nouvelle demande')

@section('content')


    <x-breadcrumbs
            title="Nouvelle demande"
            :items="[
                    'Etudiant' => route('admin.hebergement.index'),
                    'Nouvelle demande' => null,
                ]"
    />



    <div class="overflow-hidden rounded-[20px] bg-white shadow-[0_8px_24px_rgba(0,0,0,0.08)]">
        <!-- Ajouter le padding ici pour tout le contenu -->
        <div class="p-6">
            <!-- En-tête avec boutons -->
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-semibold text-[#1F7C43]">Liste des nouvelles demandes</h2>

                <div class="flex gap-3">
                    @adminroute('admin.etudiant.index')
                    <a href="{{ route('admin.etudiant.index') }}" class="inline-flex items-center justify-center gap-2 rounded-[20px] bg-white px-4 py-3 text-sm font-medium text-gray-700 ring-1 ring-gray-300 transition hover:bg-gray-50">
                        Retour
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M16.667 10H3.33366M3.33366 10L8.33366 15M3.33366 10L8.33366 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                    @endadminroute


                </div>
            </div>

            <!-- Tableau Catalogue & Disponibilités -->
            <div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                        <tr class="border-b-2 border-gray-200">
                            <th class="text-left py-4 px-4 text-sm font-semibold text-gray-700">#</th>
                            <th class="text-left py-4 px-4 text-sm font-semibold text-gray-700">Date de demande</th>
                            <th class="text-left py-4 px-4 text-sm font-semibold text-gray-700">Nom et prenom</th>

                            <th class="text-left py-4 px-4 text-sm font-semibold text-gray-700">Numero</th>
                            <th class="text-left py-4 px-4 text-sm font-semibold text-gray-700">Adresse</th>
                            <th class="text-left py-4 px-4 text-sm font-semibold text-gray-700">Region/Ville</th>
                            <th class="text-left py-4 px-4 text-sm font-semibold text-gray-700">Date Naissance</th>
                            <th class="text-left py-4 px-4 text-sm font-semibold text-gray-700">Nombre demande</th>

                            <th class="text-left py-4 px-4 text-sm font-semibold text-gray-700">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($etudiant as $etudiants)
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-4 px-4 text-sm text-gray-800">{{ $loop->iteration }}</td>
                                <td class="py-4 px-4 text-sm text-gray-800">{{ $etudiants->created_at->format('d/m/Y H:i') }}</td>
                                <td class="py-4 px-4 text-sm text-gray-800">{{ $etudiants->name }}</td>
                                <td class="py-4 px-4 text-sm text-gray-800">{{ $etudiants->telephone1 }}</td>
                                <td class="py-4 px-4 text-sm text-gray-800">{{ $etudiants->adresse }}</td>
                                <td class="py-4 px-4 text-sm text-gray-800">{{ optional($etudiants->region)->name ?? '' }} / {{ optional($etudiants->ville)->name ?? '' }}</td>
                                <td class="py-4 px-4 text-sm text-gray-800">{{ $etudiants->date_naissance?->format('d/m/Y') }}
                                </td><td class="py-4 px-4 text-sm text-gray-800"> {{ count($etudiants->demandes) }} </td>
                                <td class="py-4 px-4 text-sm text-gray-800">
                                    <a href="#"
                                            class="p-2 text-primary-600 hover:bg-primary-100 rounded-lg transition-colors"
                                            title="Detail de la demande">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                </td>






                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div class="mt-6 flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        Affichage de <span class="font-medium">1</span> à <span class="font-medium">{{ count($etudiant) }}</span> sur <span class="font-medium">{{ count($etudiant) }}</span> résultats
                    </div>
                    <div class="flex items-center gap-2">
                        <button class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>
                        <button class="px-3 py-2 text-sm font-medium text-white bg-[#1F7C43] border border-[#1F7C43] rounded-lg">
                            1
                        </button>
                        @if(ceil(count($etudiant)/10) > 1)
                            <button class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                2
                            </button>
                        @endif
                        @if(ceil(count($etudiant)/10) > 2)
                            <button class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                3
                            </button>
                        @endif
                        @if(ceil(count($etudiant)/10) > 1)
                            <button class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>




@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const typeSelect = document.getElementById('move_type');

            const dateGroup = document.getElementById('date-acquisition-group');
            const dateInput = document.getElementById('date_acquisition');

            function toggleFields() {
                if (typeSelect.value === 'exit') {

                    dateGroup.style.display = 'none';

                    dateInput.removeAttribute('required');
                    dateInput.value = '';
                } else if (typeSelect.value === 'entry') {

                    dateGroup.style.display = 'block';

                    dateInput.setAttribute('required', 'required');
                }
            }

            // Initial state
            toggleFields();

            // On change
            typeSelect.addEventListener('change', toggleFields);
        });
    </script>

    <script>
        // Fonction pour ouvrir le modal de mouvement
        function openMoveModal(equipementId, equipementNom) {
            // Mettre à jour les champs cachés
            document.getElementById('move_equipement_id').value = equipementId;

            // Réinitialiser le formulaire
            document.querySelector('#customModalMove form').reset();

            // Afficher le nom de l'équipement dans le titre
            const modalTitle = document.querySelector('#customModalMove .custom-modal-title');
            if (modalTitle && equipementNom) {
                modalTitle.textContent = `Mouvement - ${equipementNom}`;
            }

            // Ouvrir le modal
            const modal = document.getElementById('customModalMove');
            if (modal) {
                modal.classList.add('show');
            }
        }

        // Gestion des modaux
        document.addEventListener('DOMContentLoaded', function() {
            // Ouverture des modaux
            document.querySelectorAll('[data-open-modal]').forEach(button => {
                button.addEventListener('click', function() {
                    const modalType = this.getAttribute('data-open-modal');
                    let modalId = '';

                    switch(modalType) {
                        case 'add':
                            modalId = 'customModaladd';
                            break;
                        case 'stockEntry':
                            modalId = 'customModalStockEntry';
                            break;
                        case 'newType':
                            modalId = 'customModalNewType';
                            break;
                        case 'move':
                            modalId = 'customModalMove';
                            break;
                        case 'edit':
                            modalId = 'customModalEdit';
                            break;
                    }

                    if (modalId) {
                        const modal = document.getElementById(modalId);
                        if (modal) {
                            modal.classList.add('show');
                        }
                    }
                });
            });

            // Fermeture des modaux
            document.querySelectorAll('[data-close-modal]').forEach(element => {
                element.addEventListener('click', function() {
                    const modalType = this.getAttribute('data-close-modal');
                    let modalId = '';

                    switch(modalType) {
                        case 'add':
                            modalId = 'customModaladd';
                            break;
                        case 'stockEntry':
                            modalId = 'customModalStockEntry';
                            break;
                        case 'newType':
                            modalId = 'customModalNewType';
                            break;
                        case 'move':
                            modalId = 'customModalMove';
                            break;
                        case 'edit':
                            modalId = 'customModalEdit';
                            break;
                    }

                    if (modalId) {
                        const modal = document.getElementById(modalId);
                        if (modal) {
                            modal.classList.remove('show');
                        }
                    }
                });
            });



            // Fermer le modal en cliquant en dehors
            document.querySelectorAll('.custom-modal-overlay').forEach(overlay => {
                overlay.addEventListener('click', function() {
                    const modal = this.closest('.custom-modal');
                    if (modal) {
                        modal.classList.remove('show');
                    }
                });
            });
        });


        function openEditModal(id, nom,  type,  seuil) {
            // Remplir le formulaire avec les données de l'équipement
            document.getElementById('edit_equipement_id').value = id;
            document.getElementById('edit_nom').value = nom;
            // document.getElementById('edit_reference').value = reference;
            document.getElementById('edit_type').value = type;
            // document.getElementById('edit_stock_actuel').value = stockActuel;
            document.getElementById('edit_seuil').value = seuil;
            // document.getElementById('edit_categorie').value = categorie;
            // document.getElementById('edit_description').value = description;
            //  document.getElementById('edit_localisation').value = localisation;

            // Mettre à jour l'action du formulaire avec l'URL de mise à jour
            // const form = document.getElementById('editEquipementForm');
            //form.action = `/admin/hebergement/equipements/${id}`;

            // Afficher le modal
            const modal = document.getElementById('customModalEdit');
            if (modal) {
                modal.classList.add('show');
            }
        }
    </script>
@endpush