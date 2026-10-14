@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'equipements'
])
@section('title', 'Historique des mouvements')

@section('content')

    <x-breadcrumbs
            title="Historique des mouvements"
            :items="[
                'Gestion Hébergement' => route('admin.hebergement.index'),
                'Équipements' => route('admin.hebergement.equipements.index'),
                'Historique' => null,
            ]"
    />

    <div class="overflow-hidden rounded-[20px] bg-white shadow-[0_8px_24px_rgba(0,0,0,0.08)]">
        <div class="p-6">
            <!-- En-tête avec informations de l'équipement -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-2xl font-semibold text-gray-800">Historique des mouvements << {{$equipement->name}} >> </h2>

                    @adminroute('admin.hebergement.equipements.index')
                    <a href="{{ route('admin.hebergement.equipements.index') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-[20px] bg-white px-4 py-3 text-sm font-medium text-gray-700 ring-1 ring-gray-300 transition hover:bg-gray-50">
                        Retour aux équipements
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M16.667 10H3.33366M3.33366 10L8.33366 15M3.33366 10L8.33366 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                    @endadminroute
                </div>


            </div>

            <!-- Tableau de l'historique -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="text-left py-4 px-4 text-sm font-semibold text-gray-700">#</th>
                        <th class="text-left py-4 px-4 text-sm font-semibold text-gray-700">Date</th>
                        <th class="text-left py-4 px-4 text-sm font-semibold text-gray-700">Type</th>
                        <th class="text-left py-4 px-4 text-sm font-semibold text-gray-700">Quantité</th>
                        <th class="text-left py-4 px-4 text-sm font-semibold text-gray-700">Raison</th>
                        <th class="text-left py-4 px-4 text-sm font-semibold text-gray-700">Utilisateur</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($mouvements as $mouvement)
                        <tr class="{{ $mouvement->type === 'entry' ? 'table-success' : 'table-danger' }}">
                            <td class="text-left py-4 px-4 text-sm font-semibold text-gray-700">{{ $loop->iteration }}</td>
                            <td class="text-left py-4 px-4 text-sm font-semibold text-gray-700">{{ $mouvement->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-left py-4 px-4 text-sm font-semibold text-gray-700">{{ $mouvement->type === 'entry' ? 'Entrée (+)' : 'Sortie (-)' }}</td>
                            <td class="text-left py-4 px-4 text-sm font-semibold text-gray-700">{{ $mouvement->quantity }}</td>
                            <td class="text-left py-4 px-4 text-sm font-semibold text-gray-700">{{ $mouvement->reason }}</td>
                            <td class="text-left py-4 px-4 text-sm font-semibold text-gray-700">{{ $mouvement->createdBy->name ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination (si nécessaire) -->
            <div class="mt-6 flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    Affichage de <span class="font-medium">1</span> à <span class="font-medium">{{ count($mouvements) }}</span>
                    sur <span class="font-medium">{{ count($mouvements) }}</span> mouvements
                </div>
                <div class="mt-6 flex items-center justify-between">

                    <div class="flex items-center gap-2">
                        <button class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>
                        <button class="px-3 py-2 text-sm font-medium text-white bg-[#1F7C43] border border-[#1F7C43] rounded-lg">
                            1
                        </button>
                        @if(ceil(count($mouvements)/10) > 1)
                            <button class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                2
                            </button>
                        @endif
                        @if(ceil(count($mouvements)/10) > 2)
                            <button class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                3
                            </button>
                        @endif
                        @if(ceil(count($mouvements)/10) > 1)
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