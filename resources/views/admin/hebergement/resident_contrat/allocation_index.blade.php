@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'residents'
])
@section('title', 'Residents & Contrats')


@section('content')
    @include('admin.hebergement.resident_contrat.ranking-summary')
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-4xl font-bold text-gray-800">Attribution de Chambres</h1>
                <p class="text-gray-600 mt-2">Gestion des allocations de chambres pour les étudiants</p>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                {{ session('error') }}
            </div>
        @endif

        <!-- Campagnes Actives -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Campagnes Actives</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($campagnes as $campagne)
                    <div class="bg-white rounded-lg shadow-lg hover:shadow-xl transition overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
                            <h3 class="text-xl font-bold text-white">{{ $campagne->annee_campagne }}</h3>
                            <p class="text-blue-100 text-sm">{{ $campagne->description }}</p>
                        </div>

                        <div class="p-6">
                            <!-- Statut -->
                            <div class="mb-4 pb-4 border-b">
                            <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold
                                @if($campagne->statut === 'active') bg-green-100 text-green-800
                                @elseif($campagne->statut === 'planifiee') bg-yellow-100 text-yellow-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ $campagne->getLabelStatut() }}
                            </span>
                            </div>

                            <!-- Infos -->
                            <div class="space-y-3 mb-4">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Demandes acceptées:</span>
                                    <span class="font-bold text-gray-900">{{ $campagne->demandes()->where('statut_demande', 'acceptee')->count() }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Assignations:</span>
                                    <span class="font-bold text-gray-900">{{ $campagne->assignations()->where('statut', 'confirmee')->count() }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Taux:</span>
                                    <span class="font-bold text-blue-600">{{ $campagne->getTauxAllocation() }}%</span>
                                </div>
                            </div>

                            <!-- Barre de progression -->
                            <div class="mb-6">
                                <div class="flex justify-between text-xs text-gray-500 mb-2">
                                    <span>Progression</span>
                                    <span>{{ $campagne->getTauxAllocation() }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-600 h-2 rounded-full transition-all"
                                         style="width: {{ $campagne->getTauxAllocation() }}%"></div>
                                </div>
                            </div>

                            <!-- Dates -->
                            <div class="text-xs text-gray-500 mb-6 space-y-1">
                                <div>Début: <span class="text-gray-700">{{ $campagne->periode_debut?->format('d/m/Y') ?? 'N/A' }}</span></div>
                                <div>Fin: <span class="text-gray-700">{{ $campagne->periode_fin?->format('d/m/Y') ?? 'N/A' }}</span></div>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-2">
                                @adminroute('admin.allocation.show')
                                <a href="{{ route('admin.allocation.show', $campagne->id_campagne) }}"
                                   class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-center font-medium transition">
                                    Détails
                                </a>
                                @endadminroute
                                @adminroute('admin.allocation.allocate-form')
                                <a href="{{ route('admin.allocation.allocate-form', $campagne->id_campagne) }}"
                                   class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-center font-medium transition">
                                    Allouer
                                </a>
                                @endadminroute
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full bg-gray-50 rounded-lg p-12 text-center">
                        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">Aucune campagne trouvée</h3>
                        <p class="text-gray-600">Il n'y a pas de campagnes pour le moment.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-8">
            {{ $campagnes->links() }}
        </div>
    </div>

    <style>
        @media (max-width: 768px) {
            .container {
                padding-left: 1rem;
                padding-right: 1rem;
            }
        }
    </style>
@endsection