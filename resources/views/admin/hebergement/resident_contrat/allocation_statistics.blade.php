@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'residents'
])
@section('title', 'Residents & Contrats')


@section('content')



    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <a href="{{ route('allocation.show', $campagne->id_campagne) }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
                &larr; Retour aux détails
            </a>
            <h1 class="text-3xl font-bold text-gray-800">Statistiques d'Allocation</h1>
            <p class="text-gray-600">Campagne: <strong>{{ $campagne->annee_campagne }}</strong></p>
        </div>

        @if(!empty($stats))
            <!-- Cartes KPI -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Demandes Acceptées -->
                <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-blue-500">
                    <p class="text-blue-600 text-sm font-semibold uppercase">Demandes Acceptées</p>
                    <p class="text-4xl font-bold text-blue-900 mt-2">{{ $stats['demandes_acceptees'] }}</p>
                    <p class="text-xs text-gray-500 mt-2">Étudiants avec demande</p>
                </div>

                <!-- Assignations Confirmées -->
                <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-green-500">
                    <p class="text-green-600 text-sm font-semibold uppercase">Confirmées</p>
                    <p class="text-4xl font-bold text-green-900 mt-2">{{ $stats['assignations_confirmees'] }}</p>
                    <p class="text-xs text-gray-500 mt-2">Assignations validées</p>
                </div>

                <!-- En Attente -->
                <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-yellow-500">
                    <p class="text-yellow-600 text-sm font-semibold uppercase">En Attente</p>
                    <p class="text-4xl font-bold text-yellow-900 mt-2">{{ $stats['assignations_en_attente'] }}</p>
                    <p class="text-xs text-gray-500 mt-2">À confirmer</p>
                </div>

                <!-- Sans Chambre -->
                <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-red-500">
                    <p class="text-red-600 text-sm font-semibold uppercase">Sans Chambre</p>
                    <p class="text-4xl font-bold text-red-900 mt-2">{{ $stats['sans_chambre'] }}</p>
                    <p class="text-xs text-gray-500 mt-2">Non assignés</p>
                </div>
            </div>

            <!-- Taux d'Allocation Global -->
            <div class="bg-white rounded-lg shadow-lg p-8 mb-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Taux d'Allocation Global</h2>

                <div class="mb-8">
                    <div class="flex justify-between items-center mb-4">
                <span class="text-lg font-semibold text-gray-700">
                    {{ $stats['assignations_confirmees'] }} / {{ $stats['demandes_acceptees'] }} étudiants
                </span>
                        <span class="text-5xl font-bold text-blue-600">{{ $stats['taux_allocation'] }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-4">
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-4 rounded-full transition-all"
                             style="width: {{ $stats['taux_allocation'] }}%"></div>
                    </div>
                </div>

                <!-- Détails du Taux -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-6 border-t">
                    <div>
                        <p class="text-sm text-gray-600 mb-2">Assignations Confirmées</p>
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-bold text-green-600">{{ $stats['assignations_confirmees'] }}</span>
                            <span class="text-sm text-gray-600">/ {{ $stats['demandes_acceptees'] }}</span>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-2">Assignations Attendues</p>
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-bold text-yellow-600">{{ $stats['assignations_en_attente'] }}</span>
                            <span class="text-sm text-gray-600">en validation</span>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-2">Non Assignés</p>
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-bold text-red-600">{{ $stats['sans_chambre'] }}</span>
                            <span class="text-sm text-gray-600">non attribués</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Distribution par Type de Chambre -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                @foreach(['individuelle' => ['label' => 'Individuelles', 'color' => 'blue'], 'double' => ['label' => 'Doubles', 'color' => 'purple'], 'triple' => ['label' => 'Triples', 'color' => 'indigo']] as $type => $config)
                    @if(isset($stats['par_type'][$type]))
                        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                            <div class="bg-gradient-to-r from-{{ $config['color'] }}-500 to-{{ $config['color'] }}-600 px-6 py-4">
                                <h3 class="text-lg font-bold text-white">Chambres {{ $config['label'] }}</h3>
                            </div>

                            <div class="p-6">
                                <!-- Stat Principale -->
                                <div class="text-center mb-6">
                                    <p class="text-4xl font-bold text-gray-800">{{ $stats['par_type'][$type]['occupees'] }}</p>
                                    <p class="text-sm text-gray-600">assignées sur {{ $stats['par_type'][$type]['total'] }}</p>
                                </div>

                                <!-- Barre -->
                                <div class="mb-6">
                                    @php
                                        $occupation = $stats['par_type'][$type]['total'] > 0
                                            ? round(($stats['par_type'][$type]['occupees'] / $stats['par_type'][$type]['total']) * 100, 1)
                                            : 0;
                                    @endphp
                                    <div class="flex justify-between text-xs text-gray-600 mb-2">
                                        <span>Taux</span>
                                        <span>{{ $occupation }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-3">
                                        <div class="bg-{{ $config['color'] }}-600 h-3 rounded-full"
                                             style="width: {{ $occupation }}%"></div>
                                    </div>
                                </div>

                                <!-- Détails -->
                                <div class="space-y-3 pt-4 border-t border-gray-200">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Assignées:</span>
                                        <span class="font-bold">{{ $stats['par_type'][$type]['occupees'] }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Libres:</span>
                                        <span class="font-bold text-green-600">{{ $stats['par_type'][$type]['disponibles'] }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Total:</span>
                                        <span class="font-bold">{{ $stats['par_type'][$type]['total'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            <!-- Résumé Texte -->
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Résumé de la Campagne</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <h3 class="font-bold text-lg text-gray-800 mb-4">Situation Actuelle</h3>
                        <ul class="space-y-3 text-gray-700">
                            <li class="flex items-start">
                                <span class="text-green-600 font-bold mr-3">✓</span>
                                <span><strong>{{ $stats['assignations_confirmees'] }} étudiants</strong> ont une chambre confirmée</span>
                            </li>
                            <li class="flex items-start">
                                <span class="text-yellow-600 font-bold mr-3">⏳</span>
                                <span><strong>{{ $stats['assignations_en_attente'] }} assignations</strong> sont en attente de confirmation</span>
                            </li>
                            <li class="flex items-start">
                                <span class="text-red-600 font-bold mr-3">✗</span>
                                <span><strong>{{ $stats['sans_chambre'] }} étudiants</strong> n'ont pas encore de chambre</span>
                            </li>
                            <li class="flex items-start">
                                <span class="text-blue-600 font-bold mr-3">📊</span>
                                <span>Le taux d'allocation est de <strong>{{ $stats['taux_allocation'] }}%</strong></span>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="font-bold text-lg text-gray-800 mb-4">Types de Chambres</h3>
                        <ul class="space-y-3 text-gray-700">
                            @foreach(['individuelle' => 'Chambres individuelles (1 personne)', 'double' => 'Chambres doubles (2 personnes)', 'triple' => 'Chambres triples (3 personnes)'] as $type => $label)
                                @if(isset($stats['par_type'][$type]))
                                    <li class="flex items-start">
                                        <span class="text-blue-600 font-bold mr-3">•</span>
                                        <span>
                                <strong>{{ $stats['par_type'][$type]['total'] }}</strong> {{ $label }}
                                ({{ $stats['par_type'][$type]['occupees'] }} assignées,
                                {{ $stats['par_type'][$type]['disponibles'] }} libres)
                            </span>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Recommandations -->
            <div class="mt-8 bg-blue-50 border-l-4 border-blue-500 rounded-lg p-6">
                <h3 class="font-bold text-lg text-blue-900 mb-3">Recommandations</h3>
                <ul class="space-y-2 text-blue-800 text-sm">
                    @if($stats['sans_chambre'] > 0)
                        <li>• <strong>{{ $stats['sans_chambre'] }} étudiants</strong> attendent toujours une assignation. Envisagez une allocation automatique.</li>
                    @endif
                    @if($stats['assignations_en_attente'] > 0)
                        <li>• <strong>{{ $stats['assignations_en_attente'] }} assignations</strong> en attente peuvent être confirmées si elles sont valides.</li>
                    @endif
                    @if($stats['taux_allocation'] < 50)
                        <li>• Le taux d'allocation est inférieur à 50%. Vérifiez la disponibilité des chambres.</li>
                    @elseif($stats['taux_allocation'] == 100)
                        <li>• ✓ Tous les étudiants acceptés ont une chambre assignée.</li>
                    @endif
                </ul>
            </div>

            <!-- Actions -->
            <div class="mt-8 flex gap-4 flex-wrap">
                <a href="{{ route('allocation.show', $campagne->id_campagne) }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition">
                    ← Retour aux Détails
                </a>
                <a href="{{ route('allocation.index') }}"
                   class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-medium transition">
                    ← Retour aux Campagnes
                </a>
            </div>
        @endif
    </div>
@endsection