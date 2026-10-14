
@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'residents'
])

@section('title', 'Residents & Contrats')

@section('content')

    <x-breadcrumbs
            title="Residents & Contrats"
            :items="[
        'Gestion Hébergement' => route('admin.hebergement.index'),
        'Admission' => null,
    ]"
    />

    @php

        $dossiers= $etudiants;
     //   dd($campagne_active['id_campagne']);
        // Données statiques pour les dossiers

    @endphp

    <div class="overflow-hidden rounded-[20px] bg-white shadow-[0_8px_24px_rgba(0,0,0,0.08)]">
        <div class="p-6">

            <!-- En-tête -->
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-semibold text-[#1F7C43]">
                    Gestion des admissions
                </h2>
@if(!empty($campagne_active))
                @adminroute('admin.allocation.show')
                <a href="{{ route('admin.allocation.show', $campagne_active['id_campagne']) }}" class="text-blue-600 btn btn-primary hover:text-blue-800 mb-4 inline-block text-white">
                    &larr; Liste des allocations
                </a>
                @endadminroute
                @endif
            </div>

            <!-- Indicateurs cliquables (sans le total) -->
            <div class="grid grid-cols-4 gap-4 mb-6"> <!-- Changé de grid-cols-4 à grid-cols-3 -->
                @adminroute('admin.hebergement.residents_contrat.admission')
                <a href="{{ route('admin.hebergement.residents_contrat.admission',['etat' =>'en_verification']) }}"
                   class="block bg-blue-50 p-4 rounded-xl hover:bg-blue-100 transition-colors {{ $etat == 'en_verification' ? 'ring-2 ring-blue-500' : '' }}">
                    <div class="text-sm text-gray-600">En verifications</div>
                    <div class="text-xl font-semibold">{{ $stats['en_verification'] }}</div>
                    <div class="text-xs text-gray-500">Campagne 2024-2025</div>
                </a>
                @endadminroute



                @adminroute('admin.hebergement.residents_contrat.admission')
                <a href="{{ route('admin.hebergement.residents_contrat.admission',['etat' =>'residents']) }}"
                   class="block bg-success p-4 rounded-xl hover:bg-success-100 transition-colors {{ $etat == 'residents' ? 'ring-2 ring-purple-500' : '' }}">
                    <div class="text-sm text-gray-600">Résidents</div>
                    <div class="text-xl text-gray-600 font-semibold">{{ $stats['residents'] }}</div>
                    <div class="text-xs text-gray-500">Déjà logés</div>
                </a>
                @endadminroute

            </div>

            <!-- Indication du filtre actif -->

            <div class="mb-4 flex items-center gap-2">
                <span class="text-sm text-gray-600">Filtre actif :</span>
                @if($etat == 'en_verification')
                    <span class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-medium">En verification</span>
                @elseif($etat == 'residents')
                    <span class="px-3 py-1 rounded-full bg-purple-100 text-purple-700 text-xs font-medium">Résidents</span>

                @endif
                <!-- Changé le texte -->
            </div>


            <!-- Tableau -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="text-left py-4 px-4 text-sm font-semibold text-gray-700">#</th>
                        <th class="text-left py-4 px-4 text-sm font-semibold text-gray-700">Date de demande</th>
                        <th class="text-left py-4 px-4 text-sm font-semibold text-gray-700">Nom et prenom</th>

                        @adminblock('admissions.personal')
<th class="text-left py-4 px-4 text-sm font-semibold text-gray-700">Numero</th>
@endadminblock

                        @adminblock('admissions.personal')
<th class="text-left py-4 px-4 text-sm font-semibold text-gray-700">Adresse</th>
@endadminblock

                        @adminblock('admissions.personal')
<th class="text-left py-4 px-4 text-sm font-semibold text-gray-700">Region/Ville</th>
@endadminblock

                        @adminblock('admissions.personal')
<th class="text-left py-4 px-4 text-sm font-semibold text-gray-700">Date Naissance</th>
@endadminblock

                        @adminblock('admissions.history')
<th class="text-left py-4 px-4 text-sm font-semibold text-gray-700">Nombre demande</th>
@endadminblock

                        <th class="text-left py-4 px-4 text-sm font-semibold text-gray-700">Chambre Assignée</th>
                        <th class="py-4 px-4 text-left text-sm font-semibold">Actions</th>
                    </tr>
                    </thead>

                    <tbody>
                    @forelse($dossiers as $dossier)
                        <tr class="border-b hover:bg-gray-50">

                            <td class="py-4 px-4">{{ $loop->iteration }}</td>

                            <td class="py-4 px-4">
                                {{ $dossier->created_at->format('d/m/Y H:i') }}
                            </td>

                            <td class="py-4 px-4">{{ $dossier->name }}</td>

                            @adminblock('admissions.personal')
<td class="py-4 px-4">{{ $dossier->telephone1 }}</td>
@endadminblock


                            @adminblock('admissions.personal')
<td class="py-4 px-4">
                                {{ $dossier->adresse }}
                            </td>
@endadminblock


                            @adminblock('admissions.personal')
<td class="py-4 px-4">
                                <span class="text-sm">{{  optional($dossier->region)->name ?? '' }} / {{ optional($dossier->ville)->name ?? '' }}</span>
                            </td>
@endadminblock


                            <!-- Statut -->
                            @adminblock('admissions.personal')
<td class="py-4 px-4">{{ $dossier->date_naissance?->format('d/m/Y') }}</td>
@endadminblock


                            <!-- Chambre pour les résidents -->
                            @adminblock('admissions.history')
<td class="py-4 px-4">{{ count($dossier->demandes) }}</td>
@endadminblock


                            <!-- Chambre Assignée -->
                            <td class="py-4 px-4">
                                @php
                                    $assignation = $dossier->assignationsChambre()
                                        ->where('statut', 'confirmee')
                                        ->latest()
                                        ->first();
                                @endphp

                                @if($assignation)
                                    <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-medium">
                                        {{ $assignation->chambre?->numero_chambre ?? 'N/A' }}
                                    </span>
                                @else
                                    <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-600 text-xs font-medium">
                                        Sans chambre
                                    </span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-4">
                                <div class="flex gap-2">

                                    <!-- Lien vers l'allocation de chambres -->
                                    @adminroute('admin.allocation.allocate-form')
                                    <a href="{{ route('admin.allocation.allocate-form', ['campagneId' => $dossier->derniereDemande?->campagne_id]) }}?etudiant_id={{ $dossier->id_etudiant }}"
                                       class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg inline-flex items-center justify-center"
                                       title="Allouer une chambre">
                                        <i class="fa-solid fa-bed"></i>
                                    </a>
                                    @endadminroute

                                    @if($etat != 'residents')
                                        <!-- Traiter (pour nouvelles demandes) -->
                                        @if($etat == 'en_verification')
                                            @adminroute('admin.hebergement.campagnes_admission.show')
                                            <a href="{{ route('admin.hebergement.campagnes_admission.show',['id' =>$dossier->id_etudiant]) }}"
                                               class="p-2 text-green-600 hover:bg-green-100 rounded-lg inline-flex items-center justify-center"
                                               title="Traiter la demande">
                                                <i class="fa-solid fa-check-circle"></i>
                                            </a>
                                            @endadminroute
                                        @endif


                                    @endif

                                    @include('admin.etudiant.contract-link', ['student' => $dossier])

                                    <!-- Voir détails -->
                                    @adminroute('admin.hebergement.campagnes_admission.show')
                                    <a href="{{ route('admin.hebergement.campagnes_admission.show',['id' =>$dossier->id_etudiant]) }}"
                                       class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg inline-flex items-center justify-center"
                                       title="Voir détails">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    @endadminroute

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-8 text-center text-gray-500">
                                Aucun dossier trouvé pour ce filtre
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Information sur le nombre de résultats -->
            <div class="mt-4 text-sm text-gray-500">
                Affichage de {{ $dossiers->count() }} dossier(s) sur {{ $dossiers->count() }} au total
            </div>

        </div>
    </div>

    <div id="customModalAttribution" class="custom-modal">
        <div class="custom-modal-overlay" data-close-modal="attribution"></div>
        <div class="custom-modal-container">
            <div class="custom-modal-content">
                <div class="custom-modal-header">
                    <h5 class="custom-modal-title">Attribution de chambre</h5>
                </div>

                <div class="custom-modal-body">
                    <form method="POST" action="#">
                        @csrf
                        <input type="hidden" name="dossier_id" id="attrib_dossier_id">

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2">Résidence</label>
                            <select name="residence_id" class="w-full p-2 border rounded-lg" required>
                                {{--                                @foreach($residences as $residence)--}}
                                {{--                                    <option value="{{ $residence->id }}">--}}
                                {{--                                        {{ $residence->name }}--}}
                                {{--                                    </option>--}}
                                {{--                                @endforeach--}}
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2">Chambre</label>
                            <select name="chambre_id" class="w-full p-2 border rounded-lg" required>
                                {{--                                @foreach($chambres as $chambre)--}}
                                {{--                                    <option value="{{ $chambre->id }}">--}}
                                {{--                                        {{ $chambre->numero }}--}}
                                {{--                                    </option>--}}
                                {{--                                @endforeach--}}
                            </select>
                        </div>

                        <button class="w-full py-3 rounded-lg text-white font-medium"
                                style="background:#1F7C43;">
                            Valider l'attribution
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>


        function openReattributionModal(dossierId){
            document.getElementById('attrib_dossier_id').value = dossierId;
            document.getElementById('customModalAttribution').classList.add('show');
        }

        function traiterDemande(dossierId){
            alert('Traitement de la demande #' + dossierId);
        }


    </script>
@endpush