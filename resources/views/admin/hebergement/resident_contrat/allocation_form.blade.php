@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'residents'
])
@section('title', 'Residents & Contrats')



@section('content')
    @include('admin.hebergement.resident_contrat.ranking-summary')
    <div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            @adminroute('admin.allocation.show')
            <a href="{{ route('admin.allocation.show', $campagne->id_campagne) }}" class="text-blue-600 btn btn-primary hover:text-blue-800 mb-4 inline-block text-white">
                &larr; Liste des allocations
            </a>
            @endadminroute
            <h1 class="text-3xl font-bold text-gray-800">Attribution de Chambre</h1>
            <p class="text-gray-600">Campagne: <strong>{{ $campagne->annee_campagne }}</strong></p>
        </div>

        @if($errors->any())
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <details class="bg-white rounded-lg shadow-lg p-4 mb-6">
            <summary class="font-bold">Confirmation PMR du dossier étudiant</summary>
            @adminroute('admin.allocation.pmr')
            <form method="POST" action="{{ route('admin.allocation.pmr', $campagne->id_campagne) }}" class="mt-4">
                @csrf
                <label for="pmr-etudiant">Étudiant</label>
                <select id="pmr-etudiant" name="etudiant_id" class="form-select mb-3" required>
                    <option value="">Sélectionner un étudiant</option>
                    @foreach($etudiants as $etudiant)
                        <option value="{{ $etudiant->getKey() }}">{{ $etudiant->name }} - {{ $etudiant->matricule }} (PMR {{ $etudiant->pmr_confirme ? 'confirmé' : 'non confirmé' }})</option>
                    @endforeach
                </select>
                <label for="pmr-confirmation">Résultat de la vérification du dossier</label>
                <select id="pmr-confirmation" name="pmr_confirme" class="form-select mb-3" required>
                    <option value="">Sélectionner</option>
                    <option value="1">PMR confirmé</option>
                    <option value="0">PMR non confirmé</option>
                </select>
                <button class="btn btn-primary" type="submit">Enregistrer la vérification</button>
            </form>
            @endadminroute
        </details>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Formulaire Principal -->
            <div class="lg:col-span-2">
                @adminroute('admin.allocation.allocate')
                <form method="POST" action="{{ route('admin.allocation.allocate', $campagne->id_campagne) }}" class="bg-white rounded-lg shadow-lg p-8">
                    @csrf

                    <!-- Sélection de l'étudiant -->
                    <div class="mb-8">
                        <label for="etudiant_id" class="block text-sm font-bold text-gray-700 mb-2">
                            Sélectionner un Étudiant
                        </label>
                        <select name="etudiant_id" id="etudiant_id" required
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition"
                                onchange="updateEtudiantInfo()">
                            <option value="">-- Choisir un étudiant --</option>
                            @foreach($etudiants as $etudiant)
                                <option value="{{ $etudiant->id_etudiant }}"
                                        data-niveau="{{ $etudiant->getAnneeEtude() }}"
                                        data-niveau-numeric="{{ $etudiant->getNiveauNumeric() }}"
                                        data-email="{{ $etudiant->email1 }}"
                                        data-matricule="{{ $etudiant->matricule }}">
                                    {{ $etudiant->name }} ({{ $etudiant->getAnneeEtude() }}) - {{ $etudiant->matricule }}
                                </option>
                            @endforeach
                        </select>
                        @error('etudiant_id')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Infos Étudiant -->
                    <div id="etudiant-info" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                        <h3 class="font-bold text-gray-800 mb-4">Profil de l'Étudiant</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-600">Niveau d'étude</p>
                                <p class="text-lg font-bold" id="info-niveau">-</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-600">Matricule</p>
                                <p class="text-lg font-bold font-mono" id="info-matricule">-</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-600">Email</p>
                                <p class="text-sm" id="info-email">-</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-600">Types Éligibles</p>
                                <p class="text-sm font-bold text-blue-600" id="info-types">-</p>
                            </div>
                        </div>
                    </div>

                    <!-- Mode d'Allocation -->
                    <div class="mb-8">
                        <label class="block text-sm font-bold text-gray-700 mb-4">Mode d'Allocation</label>
                        <div class="space-y-3">
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="allocation_type" value="automatique" checked
                                       class="w-4 h-4" onchange="toggleChambreSelection()">
                                <span class="ml-3">
                                <strong class="text-gray-900">Automatique</strong>
                                <p class="text-sm text-gray-600">Assigner la première chambre disponible</p>
                            </span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="allocation_type" value="manuelle"
                                       class="w-4 h-4" onchange="toggleChambreSelection()">
                                <span class="ml-3">
                                <strong class="text-gray-900">Manuelle</strong>
                                <p class="text-sm text-gray-600">Choisir une chambre spécifique</p>
                            </span>
                            </label>
                        </div>
                    </div>

                    <!-- Sélection de Chambre (Mode Manuel) -->
                    <div id="chambre-section" class="hidden mb-8">
                        <label for="chambre_id" class="block text-sm font-bold text-gray-700 mb-2">
                            Sélectionner une Chambre
                        </label>
                        <select name="chambre_id" id="chambre_id"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition"
                                onchange="updateChambreInfo()">
                            <option value="">-- Choisir une chambre --</option>
                            @foreach($chambres as $chambre)
                                @php
                                    $occupes = $chambre->getNombrePlacesOccupees();
                                    $capacite = $chambre->capacite_max;
                                    $placesDisponibles = $chambre->getNombrePlacesDisponibles();
                                    $estComplet = $placesDisponibles <= 0;
                                @endphp

                                <option value="{{ $chambre->id_chambre }}"
                                        data-type="{{ $chambre->type_chambre_code }}"
                                        data-numero="{{ $chambre->numero_chambre }}"
                                        data-capacite="{{ $capacite }}"
                                        data-lits="{{ json_encode(app(\App\Http\Services\LitService::class)->disponibles($chambre)) }}"
                                        data-occupes="{{ $occupes }}"
                                        data-places-disponibles="{{ $placesDisponibles }}"
                                        data-prix="{{ number_format($chambre->prix, 0, ',', ' ') }}"
                                        @disabled($estComplet)>

                                    {{ $chambre->numero_chambre }} -
                                    {{ ucfirst($chambre->typeChambre?->name ?? 'Non défini') }} -
                                    {{ $occupes }}/{{ $capacite }} places occupées
                                    @if($estComplet)
                                        (Complet)
                                    @elseif($placesDisponibles === 1)
                                        (Dernière place disponible !)
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <label for="numero_lit" class="block text-sm font-bold text-gray-700 mt-3 mb-2">Lit</label>
                        <select name="numero_lit" id="numero_lit" class="form-select">
                            <option value="">Premier lit disponible</option>
                        </select>
                    </div>

                    <!-- Infos Chambre -->
                    <div id="chambre-info" class="hidden bg-green-50 border border-green-200 rounded-lg p-6 mb-8">
                        <h3 class="font-bold text-gray-800 mb-4">Détails de la Chambre</h3>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <p class="text-xs text-gray-600">Type</p>
                                <p class="text-lg font-bold" id="info-type">-</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-600">Capacité</p>
                                <p class="text-lg font-bold" id="info-capacite">-</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-600">Occupée</p>
                                <p class="text-lg font-bold" id="info-occupes">-</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-600">Places Libres</p>
                                <p class="text-lg font-bold text-green-600" id="info-libres">-</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-600">Prix</p>
                                <p class="text-lg font-bold" id="info-prix">-</p>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600 mb-2">Taux d'Occupation</p>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div id="occupation-bar" class="bg-green-600 h-3 rounded-full" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Avertissements -->
                    <div id="warnings" class="mb-8"></div>

                    <!-- Règles d'Éligibilité -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-8">
                        <h3 class="font-bold text-gray-800 mb-3">Règles d'Éligibilité</h3>
                        <ul class="text-sm text-gray-700 space-y-2">
                            <li>✓ <strong>Années 1-3:</strong> Chambres Double ou Triple uniquement</li>
                            <li>✓ <strong>Années 4+:</strong> Chambre Individuelle (prioritaire) ou Double/Triple</li>
                            <li>✓ <strong>Capacité:</strong> Individuelle (1), Double (2), Triple (3)</li>
                            <li>✓ Une chambre ne peut pas dépasser sa capacité</li>
                        </ul>
                    </div>

                    <!-- Boutons -->
                    <div class="flex gap-4">
                        <button type="submit"
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-bold transition">
                            ✓ Confirmer l'Allocation
                        </button>
                        @adminroute('admin.allocation.show')
                        <a href="{{ route('admin.allocation.show', $campagne->id_campagne) }}"
                           class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-3 rounded-lg text-center font-bold transition">
                            Annuler
                        </a>
                        @endadminroute

                    </div>
                </form>
                @endadminroute
            </div>

            <!-- Résumé à Droite -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-lg p-6 sticky top-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">Résumé</h2>

                    <!-- Résumé Étudiant -->
                    <div class="mb-6 pb-6 border-b">
                        <p class="text-xs text-gray-600 font-semibold">ÉTUDIANT</p>
                        <p class="text-lg font-bold text-gray-800 mt-1" id="summary-etudiant">Non sélectionné</p>
                        <p class="text-sm text-gray-600 mt-1" id="summary-niveau">-</p>
                    </div>

                    <!-- Résumé Chambre -->
                    <div class="mb-6 pb-6 border-b">
                        <p class="text-xs text-gray-600 font-semibold">CHAMBRE</p>
                        <p class="text-lg font-bold text-gray-800 mt-1" id="summary-chambre">Non sélectionnée</p>
                        <p class="text-sm text-gray-600 mt-1" id="summary-type">-</p>
                    </div>

                    <!-- Vérification d'Éligibilité -->
                    <div class="mb-6 pb-6 border-b">
                        <p class="text-xs text-gray-600 font-semibold mb-2">ÉLIGIBILITÉ</p>
                        <div id="eligibility-result" class="text-sm">
                            <p class="text-gray-500">Sélectionnez un étudiant et une chambre</p>
                        </div>
                    </div>

                    <!-- Statistiques Rapides -->
                    <div class="bg-gray-50 rounded-lg p-4 space-y-2 text-sm">
                        <h3 class="font-bold text-gray-800 mb-3">Statistiques Rapides</h3>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Étudiants sans chambre:</span>
                            <span class="font-bold">{{ $etudiants->count() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Chambres disponibles:</span>
                            <span class="font-bold">{{ $chambres->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleChambreSelection() {
            const section = document.getElementById('chambre-section');
            const type = document.querySelector('input[name="allocation_type"]:checked').value;

            if (type === 'manuelle') {
                section.classList.remove('hidden');
            } else {
                section.classList.add('hidden');
                document.getElementById('chambre_id').value = '';
                document.getElementById('chambre-info').classList.add('hidden');
            }
        }

        function updateEtudiantInfo() {
            const select = document.getElementById('etudiant_id');
            const option = select.options[select.selectedIndex];
            const lits = document.getElementById('numero_lit');
            lits.replaceChildren(new Option('Premier lit disponible', ''));
            JSON.parse(option.dataset.lits || '[]').forEach(numero => lits.add(new Option('Lit ' + numero, numero)));

            if (!option.value) {
                document.getElementById('etudiant-info').classList.add('hidden');
                document.getElementById('summary-etudiant').textContent = 'Non sélectionné';
                return;
            }

            const niveau = option.dataset.niveau;
            const niveauNumeric = option.dataset['niveau-numeric'];
            const email = option.dataset.email;
            const matricule = option.dataset.matricule;
            const nom = option.textContent.split('(')[0].trim();

            // Types éligibles selon le niveau
            let types = niveauNumeric <= 3 ? ['Double', 'Triple'] : ['Individuelle', 'Double', 'Triple'];

            document.getElementById('info-niveau').textContent = niveau;
            document.getElementById('info-matricule').textContent = matricule;
            document.getElementById('info-email').textContent = email;
            document.getElementById('info-types').textContent = types.join(', ');

            document.getElementById('etudiant-info').classList.remove('hidden');
            document.getElementById('summary-etudiant').textContent = nom;
            document.getElementById('summary-niveau').textContent = 'Niveau: ' + niveau;

            checkEligibility();
        }

        function updateChambreInfo() {
            const select = document.getElementById('chambre_id');
            const option = select.options[select.selectedIndex];
            const lits = document.getElementById('numero_lit');
            lits.replaceChildren(new Option('Premier lit disponible', ''));
            JSON.parse(option.dataset.lits || '[]').forEach(numero => lits.add(new Option('Lit ' + numero, numero)));

            if (!option.value) {
                document.getElementById('chambre-info').classList.add('hidden');
                document.getElementById('summary-chambre').textContent = 'Non sélectionnée';
                return;
            }

            const type = option.dataset.type;
            const numero = option.dataset.numero;
            const capacite = parseInt(option.dataset.capacite);
            const occupes = parseInt(option.dataset.occupes);
            const prix = option.dataset.prix;

            const libres = parseInt(option.dataset.placesDisponibles);
            const pourcentage = (occupes / capacite) * 100;

            document.getElementById('info-type').textContent = type.charAt(0).toUpperCase() + type.slice(1);
            document.getElementById('info-capacite').textContent = capacite;
            document.getElementById('info-occupes').textContent = occupes;
            document.getElementById('info-libres').textContent = libres;
            document.getElementById('info-prix').textContent = prix + ' FCFA';
            document.getElementById('occupation-bar').style.width = pourcentage + '%';

            document.getElementById('chambre-info').classList.remove('hidden');
            document.getElementById('summary-chambre').textContent = 'Chambre ' + numero;
            document.getElementById('summary-type').textContent = 'Type: ' + type;

            checkEligibility();
        }

        function checkEligibility() {
            const etudiantId = document.getElementById('etudiant_id').value;
            const chambreId = document.getElementById('chambre_id').value;

            if (!etudiantId || !chambreId) {
                return;
            }

            // Simulation locale
            const etudiantOption = document.getElementById('etudiant_id').options[document.getElementById('etudiant_id').selectedIndex];
            const chambreOption = document.getElementById('chambre_id').options[document.getElementById('chambre_id').selectedIndex];

            const niveauNumeric = parseInt(etudiantOption.dataset['niveau-numeric']);
            const type = chambreOption.dataset.type;

            let eligible = false;
            let message = '';

            if (niveauNumeric <= 3) {
                eligible = type !== 'individuelle';
                message = eligible ? '✓ Étudiant éligible pour cette chambre' : '✗ Les années 1-3 ne peuvent pas occuper une chambre individuelle';
            } else {
                eligible = true;
                if (type === 'individuelle') {
                    message = '✓ Éligible (priorité)';
                } else {
                    message = '✓ Éligible (fallback si pas d\'individuelle)';
                }
            }

            const result = document.getElementById('eligibility-result');
            result.innerHTML = `<div class="${eligible ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'} px-3 py-2 rounded text-sm font-medium">${message}</div>`;
        }

        // Initialiser au chargement
        const urlParams = new URLSearchParams(window.location.search);
        const etudiantId = urlParams.get('etudiant_id');
        if (etudiantId) {
            document.getElementById('etudiant_id').value = etudiantId;
            updateEtudiantInfo();
        }
    </script>
@endsection