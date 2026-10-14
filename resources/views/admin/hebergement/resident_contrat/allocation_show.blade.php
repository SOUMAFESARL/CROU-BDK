@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'residents'
])
@section('title', 'Residents & Contrats')


@section('content')
    @include('admin.hebergement.resident_contrat.ranking-summary')



    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            @adminroute('admin.allocation.index')
            <a href="{{ route('admin.allocation.index') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
                &larr; Retour aux campagnes
            </a>
            @endadminroute
            <h1 class="text-3xl font-bold text-gray-800">{{ $campagne->annee_campagne }}</h1>
            <p class="text-gray-600">{{ $campagne->description }}</p>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                {{ session('success') }}
            </div>
        @endif

        <!-- Statistiques Clés -->
        @if(!empty($stats))
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg p-6 border border-red-200">
                    <p class="text-red-600 text-sm font-semibold">Demande En attente</p>
                    <p class="text-3xl font-bold text-red-900 mt-2">{{ $stats['sans_chambre'] }}</p>
                </div>
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-6 border border-blue-200">
                            <p class="text-blue-600 text-sm font-semibold">Demandes En Verification</p>
                    <p class="text-3xl font-bold text-blue-900 mt-2">{{ $stats['demandes_acceptees'] }}</p>
                </div>

                <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-lg p-6 border border-yellow-200">
                    <p class="text-yellow-600 text-sm font-semibold">En Attente d'Assignation</p>
                    <p class="text-3xl font-bold text-yellow-900 mt-2">{{ $stats['assignations_en_attente'] }}</p>
                </div>
                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-6 border border-green-200">
                    <p class="text-green-600 text-sm font-semibold">Assignations Confirmées</p>
                    <p class="text-3xl font-bold text-green-900 mt-2">{{ $stats['assignations_confirmees'] }}</p>
                </div>

            </div>

            <!-- Taux d'Allocation -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800">Taux d'Allocation Global</h2>
                    <span class="text-3xl font-bold text-blue-600">{{ $stats['taux_allocation'] }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-3 rounded-full transition-all"
                         style="width: {{ $stats['taux_allocation'] }}%"></div>
                </div>
            </div>


            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                @foreach($stats['par_type'] as $typeId => $data)
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">
                            Chambres {{ $data['nom'] }}
                        </h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total:</span>
                                <span class="font-bold">{{ $data['total'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Assignées:</span>
                                <span class="font-bold text-blue-600">{{ $data['occupees'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Disponibles:</span>
                                <span class="font-bold text-green-600">{{ $data['total'] - $data['occupees'] }}</span>
{{--                                <span class="font-bold text-green-600">{{ $data['disponibles'] }}</span>--}}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

        @endif

        <!-- Actions Principales -->
        <div class="flex gap-4 mb-8 flex-wrap">
            @adminroute('admin.allocation.allocate-form')
            <a href="{{ route('admin.allocation.allocate-form', $campagne->id_campagne) }}"
               class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition">
                ➕ Allouer une Chambre
            </a>
            @endadminroute
            @adminroute('admin.allocation.allocate-all')
            <form method="POST" action="{{ route('admin.allocation.allocate-all', $campagne->id_campagne) }}" class="inline">
                @csrf
                <button type="submit"
                        class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-3 rounded-lg font-medium transition"
                        onclick="return confirm('Allouer automatiquement les chambres restantes ?')">
                    🚀 Allocation Automatique
                </button>
            </form>
            @endadminroute
            @adminroute('admin.allocation.statistics')
            <a href="{{ route('admin.allocation.statistics', $campagne->id_campagne) }}"
               class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-medium transition">
                📊 Statistiques
            </a>
            @endadminroute
            @adminroute('admin.allocation.export-csv')
            <a href="{{ route('admin.allocation.export-csv', $campagne->id_campagne) }}"
               class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition">
                📥 Exporter CSV
            </a>
            @endadminroute
        </div>

        <!-- Onglets -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="border-b">
                <div class="flex gap-8 px-6 py-4">
                    <button type="button" class="tab-btn font-medium text-blue-600 border-b-2 border-blue-600 pb-2"
                            data-tab="assignations">
                        Assignations ({{ $assignations->count() }})
                    </button>
                    <button type="button" class="tab-btn font-medium text-gray-600 border-b-2 border-transparent pb-2 hover:text-gray-800"
                            data-tab="etudiants">
                        Sans Chambre ({{ $etudiants_sans_chambre->count() }})
                    </button>
                    <button type="button" class="tab-btn font-medium text-gray-600 border-b-2 border-transparent pb-2 hover:text-gray-800"
                            data-tab="chambres">
                        Chambres ({{ $chambres_disponibles->count() }})
                    </button>
                </div>
            </div>

            <!-- Tab: Assignations -->
            <div id="assignations" class="tab-content p-6">
                @if($assignations->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Étudiant</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Matricule</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Niveau</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Chambre</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Type</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Statut</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Actions</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y">
                            @foreach($assignations as $assignation)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm">{{ $assignation->etudiant?->name ?? "N/D" }}</td>
                                    <td class="px-4 py-3 text-sm font-mono">{{ $assignation->etudiant?->matricule  ?? "N/D" }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $assignation->etudiant?->getAnneeEtude()  ?? "N/D" }}</td>
                                    <td class="px-4 py-3 text-sm font-bold">{{ $assignation->chambre?->numero_chambre  ?? "N/D" }} <span class="text-sm text-gray-500">{{ $assignation->lit ? ' / Lit '.$assignation->lit->numero : ' / Lit non renseigné' }}</span></td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-semibold">
                                            {{ ucfirst($assignation->chambre?->typeChambre?->name ?? 'Non defini' ) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="px-2 py-1 rounded text-xs font-semibold
                                            @if($assignation->statut === 'confirmee') bg-green-100 text-green-800
                                            @elseif($assignation->statut === 'en_attente') bg-yellow-100 text-yellow-800
                                            @else bg-red-100 text-red-800
                                            @endif">
                                            {{ \App\Http\Services\DossierWorkflow::LABELS[$assignation->dossier?->etat_dossier] ?? ucfirst($assignation->statut) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm flex gap-2">
                                        @if($assignation->statut === 'en_attente' && $assignation->dossier?->etat_dossier === 'accepte')
                                            @adminroute('admin.allocation.confirm')
                                            <form method="POST" action="{{ route('admin.allocation.confirm', $campagne->id_campagne) }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="assignation_id" value="{{ $assignation->id_assignation }}">
                                                <button type="submit" class="text-green-600 hover:text-green-800 font-bold text-xs">
                                                    Valider définitivement
                                                </button>
                                            </form>
                                            @endadminroute
                                        @endif
                                        @if($assignation->statut === 'en_attente')
                                        @adminroute('admin.allocation.cancel')
                                        <form method="POST" action="{{ route('admin.allocation.cancel', $campagne->id_campagne) }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="assignation_id" value="{{ $assignation->id_assignation }}">
                                            <button type="submit" class="text-red-600 hover:text-red-800 font-bold text-xs"
                                                    onclick="return confirm('Êtes-vous sûr ?')">
                                                ✕ Annuler
                                            </button>
                                        </form>
                                        @endadminroute
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-center text-gray-500 py-8">Aucune assignation pour le moment</p>
                @endif
            </div>

            <!-- Tab: Étudiants sans Chambre -->
            <div id="etudiants" class="tab-content hidden p-6">
                @if($etudiants_sans_chambre->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Nom</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Email</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Niveau</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Matricule</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Actions</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y">
                            @foreach($etudiants_sans_chambre as $etudiant)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-medium">{{ $etudiant->name }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $etudiant->email1 }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $etudiant->getAnneeEtude() }}</td>
                                    <td class="px-4 py-3 text-sm font-mono">{{ $etudiant->matricule }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        @adminroute('admin.allocation.allocate-form')
                                        <a href="{{ route('admin.allocation.allocate-form', $campagne->id_campagne) }}?etudiant_id={{ $etudiant->id_etudiant }}"
                                           class="text-blue-600 hover:text-blue-800 font-bold">
                                            Allouer
                                        </a>
                                        @endadminroute
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-center text-gray-500 py-8">✓ Tous les étudiants ont une chambre !</p>
                @endif
            </div>

            <!-- Tab: Chambres -->
            <div id="chambres" class="tab-content hidden p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($chambres_disponibles as $chambre)
                        <div class="border rounded-lg p-4 hover:shadow-lg transition">
                            <h3 class="font-bold text-lg mb-2">{{ $chambre->numero_chambre }}</h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Type:</span>
                                    <span class="font-bold">{{ ucfirst($chambre->type_chambre_code) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Capacité:</span>
                                    <span class="font-bold">{{ $chambre->capacite_max }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Occupée:</span>
                                    <span class="font-bold">{{ $chambre->getNombrePlacesOccupees() }}/{{ $chambre->capacite_max }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Libre:</span>
                                    <span class="font-bold text-green-600">{{ $chambre->getNombrePlacesDisponibles() }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Prix:</span>
                                    <span class="font-bold">{{ number_format($chambre->prix, 0) }} FCFA</span>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="text-xs text-gray-500 mb-2">Occupation</div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-600 h-2 rounded-full"
                                         style="width: {{ $chambre->getPourcentageOccupation() }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const tab = this.dataset.tab;

                // Cacher tous les tabs
                document.querySelectorAll('.tab-content').forEach(t => t.classList.add('hidden'));

                // Afficher le tab sélectionné
                document.getElementById(tab).classList.remove('hidden');

                // Mettre à jour les boutons
                document.querySelectorAll('.tab-btn').forEach(b => {
                    b.classList.remove('border-blue-600', 'text-blue-600');
                    b.classList.add('border-transparent', 'text-gray-600');
                });

                this.classList.add('border-blue-600', 'text-blue-600');
                this.classList.remove('border-transparent', 'text-gray-600');
            });
        });
    </script>
@endsection