@extends('admin.layouts.app', ['activeMenu' => 'hebergement', 'activeSubMenu' => 'campagnes'])

@section('title', 'Admission directe')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/campagnes-admission.css') }}">
@endpush

@php
    $colonnesObligatoires = ['name', 'civilite', 'matricule', 'telephone1', 'cite', 'batiment', 'campagne', 'chambre', 'date_entree'];
    $formulaireBloque = $campagnes->isEmpty() || $chambres->isEmpty();

    // Rapport d'import : le contrôleur renvoie les lignes refusées dans le message « warning », séparées par « | ».
    $lignesRefusees = [];
    if (is_string(session('warning')) && str_contains(session('warning'), 'enregistrées : ')) {
        $lignesRefusees = array_filter(array_map('trim', explode('|', \Illuminate\Support\Str::after(session('warning'), 'enregistrées : '))));
    }
@endphp

@section('content')
<div class="as-refonte ca-page">
    <x-breadcrumbs title="Admission directe d’un étudiant" :items="[
        'Campagnes & Admission' => route('admin.hebergement.campagnes_admission.admission'),
        'Admission directe' => null,
    ]" />

    <div class="ca-header">
        <div class="ca-header-text">
            <p class="ca-subtitle">Enregistrez un étudiant déjà installé en cité : son dossier résident et son affectation sont confirmés immédiatement, sans demande d’admission.</p>
        </div>
        <div class="ca-actions">
            @adminroute('admin.hebergement.campagnes_admission.admission')
            <a href="{{ route('admin.hebergement.campagnes_admission.admission') }}" class="ca-btn ca-btn-outline">
                <x-admin-icon name="arrow-left" /> Retour aux admissions
            </a>
            @endadminroute
        </div>
    </div>

    @if($campagnes->isEmpty())
        <div class="ca-alert ca-alert-warning" role="alert"><x-admin-icon name="triangle-alert" /> <div>Aucune année académique n’est configurée. Créez d’abord une campagne.</div></div>
    @elseif($chambres->isEmpty())
        <div class="ca-alert ca-alert-warning" role="alert"><x-admin-icon name="triangle-alert" /> <div>Aucune place n’est actuellement disponible dans les chambres.</div></div>
    @endif

    <div class="ca-layout">
        {{-- Saisie individuelle --}}
        <div>
            @adminroute('admin.etudiant.admission_directe.store')
            <form method="POST" action="{{ route('admin.etudiant.admission_directe.store') }}" class="ca-card">
                @csrf
                <div class="ca-card-header">
                    <h2 class="ca-card-title"><span class="ca-kpi-icon tone-green"><x-admin-icon name="user-plus" /></span> Enregistrer un étudiant</h2>
                    <span class="ca-card-meta"><span class="ca-error">*</span> Champs obligatoires</span>
                </div>

                <div class="ca-card-body">
                    <section class="ca-section">
                        <div class="ca-section-head">
                            <span class="ca-kpi-icon tone-blue"><x-admin-icon name="user-round" /></span>
                            <div>
                                <h3 class="ca-section-title">1. Identité</h3>
                                <p class="ca-section-desc">Si le matricule ou le téléphone correspond à un étudiant existant, son dossier est réutilisé.</p>
                            </div>
                        </div>
                        <div class="ca-form-grid cols-2">
                            <div class="ca-field ca-span-full">
                                <label for="name" class="ca-label">Nom et prénoms <span class="req">*</span></label>
                                <input id="name" name="name" value="{{ old('name') }}" required autocomplete="name" class="ca-input @error('name') is-invalid @enderror">
                                @error('name')<span class="ca-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="ca-field">
                                <label for="genre_id" class="ca-label">Civilité <span class="req">*</span></label>
                                <select id="genre_id" name="genre_id" required class="ca-input @error('genre_id') is-invalid @enderror">
                                    <option value="">Sélectionner</option>
                                    @foreach($genres as $genre)
                                        <option value="{{ $genre->id }}" @selected((string) old('genre_id') === (string) $genre->id)>{{ $genre->name }}</option>
                                    @endforeach
                                </select>
                                @error('genre_id')<span class="ca-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="ca-field">
                                <label for="matricule" class="ca-label">Matricule <span class="req">*</span></label>
                                <input id="matricule" name="matricule" value="{{ old('matricule') }}" required class="ca-input @error('matricule') is-invalid @enderror">
                                @error('matricule')<span class="ca-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="ca-field">
                                <label for="telephone1" class="ca-label">Téléphone <span class="req">*</span></label>
                                <input type="tel" id="telephone1" name="telephone1" value="{{ old('telephone1') }}" required autocomplete="tel" class="ca-input @error('telephone1') is-invalid @enderror">
                                @error('telephone1')<span class="ca-error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </section>

                    <section class="ca-section">
                        <div class="ca-section-head">
                            <span class="ca-kpi-icon tone-green"><x-admin-icon name="bed-double" /></span>
                            <div>
                                <h3 class="ca-section-title">2. Logement occupé</h3>
                                <p class="ca-section-desc">Seules les chambres disponibles ayant encore une place libre sont proposées.</p>
                            </div>
                        </div>
                        <div class="ca-form-grid cols-2">
                            <div class="ca-field">
                                <label for="cite_id" class="ca-label">Cité <span class="req">*</span></label>
                                <select id="cite_id" name="cite_id" required class="ca-input @error('cite_id') is-invalid @enderror">
                                    <option value="">Sélectionner</option>
                                    @foreach($cites as $cite)
                                        <option value="{{ $cite->id_cite }}" @selected((string) old('cite_id') === (string) $cite->id_cite)>{{ $cite->name }}</option>
                                    @endforeach
                                </select>
                                @error('cite_id')<span class="ca-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="ca-field">
                                <label for="batiment_id" class="ca-label">Bâtiment <span class="req">*</span></label>
                                <select id="batiment_id" name="batiment_id" required disabled class="ca-input @error('batiment_id') is-invalid @enderror">
                                    <option value="">Sélectionner d’abord une cité</option>
                                    @foreach($batiments as $batiment)
                                        <option value="{{ $batiment->id_batiment }}" data-cite="{{ $batiment->cite_id }}" @selected((string) old('batiment_id') === (string) $batiment->id_batiment)>{{ $batiment->name }}</option>
                                    @endforeach
                                </select>
                                @error('batiment_id')<span class="ca-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="ca-field">
                                <label for="chambre_id" class="ca-label">Chambre <span class="req">*</span></label>
                                <select id="chambre_id" name="chambre_id" required disabled class="ca-input @error('chambre_id') is-invalid @enderror">
                                    <option value="">Sélectionner d’abord un bâtiment</option>
                                    @foreach($chambres as $chambre)
                                        @php $placesLibres = max(0, (int) $chambre->capacite_max - (int) $chambre->places_occupees); @endphp
                                        <option value="{{ $chambre->id_chambre }}" data-cite="{{ $chambre->cite_id }}" data-batiment="{{ $chambre->batiment_id }}" @selected((string) old('chambre_id') === (string) $chambre->id_chambre)>
                                            Chambre {{ $chambre->numero_chambre }} — {{ $placesLibres }} place{{ $placesLibres > 1 ? 's' : '' }} libre{{ $placesLibres > 1 ? 's' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('chambre_id')<span class="ca-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="ca-field">
                                <label for="numero_lit" class="ca-label">Numéro du lit</label>
                                <input type="number" min="1" id="numero_lit" name="numero_lit" value="{{ old('numero_lit') }}" placeholder="Premier lit disponible" class="ca-input @error('numero_lit') is-invalid @enderror">
                                @error('numero_lit')<span class="ca-error">{{ $message }}</span>@enderror
                                <span class="ca-hint">Facultatif : laissé vide, le premier lit libre est attribué.</span>
                            </div>
                        </div>
                    </section>

                    <section class="ca-section">
                        <div class="ca-section-head">
                            <span class="ca-kpi-icon tone-amber"><x-admin-icon name="calendar-check" /></span>
                            <div>
                                <h3 class="ca-section-title">3. Période d’occupation</h3>
                                <p class="ca-section-desc">Année académique de rattachement et date à laquelle l’étudiant est réellement entré dans la chambre.</p>
                            </div>
                        </div>
                        <div class="ca-form-grid cols-2">
                            <div class="ca-field">
                                <label for="campagne_id" class="ca-label">Année académique <span class="req">*</span></label>
                                <select id="campagne_id" name="campagne_id" required class="ca-input @error('campagne_id') is-invalid @enderror">
                                    <option value="">Sélectionner</option>
                                    @foreach($campagnes as $campagne)
                                        <option value="{{ $campagne->id_campagne }}" @selected((string) old('campagne_id', $campagneActuelle?->id_campagne) === (string) $campagne->id_campagne)>{{ $campagne->annee_campagne }}{{ $campagne->statut === 'active' ? ' — Active' : '' }}</option>
                                    @endforeach
                                </select>
                                @error('campagne_id')<span class="ca-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="ca-field">
                                <label for="date_entree" class="ca-label">Date réelle d’entrée <span class="req">*</span></label>
                                <input type="date" id="date_entree" name="date_entree" value="{{ old('date_entree', today()->toDateString()) }}" max="{{ today()->toDateString() }}" required class="ca-input @error('date_entree') is-invalid @enderror">
                                @error('date_entree')<span class="ca-error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </section>
                </div>

                <div class="ca-card-footer">
                    <span>Le dossier passe directement à l’état « attribué définitif ».</span>
                    <div class="ca-actions">
                        @adminroute('admin.hebergement.campagnes_admission.admission')
                        <a class="ca-btn ca-btn-outline" href="{{ route('admin.hebergement.campagnes_admission.admission') }}">Annuler</a>
                        @endadminroute
                        <button class="ca-btn ca-btn-primary" type="submit" @disabled($formulaireBloque)>
                            <x-admin-icon name="user-check" /> Enregistrer et affecter
                        </button>
                    </div>
                </div>
            </form>
            @endadminroute
        </div>

        {{-- Import en masse --}}
        <aside class="ca-aside">
            @adminroute('admin.etudiant.admission_directe.import')
            <div class="ca-card">
                <div class="ca-card-header">
                    <h2 class="ca-card-title"><span class="ca-kpi-icon tone-purple"><x-admin-icon name="upload" /></span> Import en masse</h2>
                </div>
                <div class="ca-card-body">
                    <ol class="ca-timeline">
                        <li>
                            <div>
                                <span class="ca-strong">1. Télécharger le modèle</span>
                                <div class="ca-small ca-muted">Listes déroulantes préremplies depuis la base.</div>
                            </div>
                            @adminroute('admin.etudiant.admission_directe.template')
                            <a class="ca-btn ca-btn-soft ca-btn-sm" href="{{ route('admin.etudiant.admission_directe.template') }}"><x-admin-icon name="download" size="16" /> Modèle</a>
                            @endadminroute
                        </li>
                        <li>
                            <div>
                                <span class="ca-strong">2. Remplir une ligne par étudiant</span>
                                <div class="ca-small ca-muted">Colonnes obligatoires :</div>
                                <div class="ca-chips" style="margin-top:6px">
                                    @foreach($colonnesObligatoires as $colonne)
                                        <span class="ca-badge tone-gray"><code>{{ $colonne }}</code></span>
                                    @endforeach
                                </div>
                                <div class="ca-small ca-muted" style="margin-top:6px">Seul <code>numero_lit</code> est facultatif.</div>
                            </div>
                        </li>
                        <li style="display:block">
                            <span class="ca-strong">3. Importer le fichier</span>
                            <form method="POST" action="{{ route('admin.etudiant.admission_directe.import') }}" enctype="multipart/form-data" class="ca-field" style="margin-top:10px">
                                @csrf
                                <input type="file" name="fichier" accept=".xlsx,.csv,.txt" required class="ca-input @error('fichier') is-invalid @enderror" aria-label="Fichier à importer (.xlsx ou .csv)">
                                @error('fichier')<span class="ca-error">{{ $message }}</span>@enderror
                                <span class="ca-hint">Formats .xlsx ou .csv, 10 Mo maximum.</span>
                                <button class="ca-btn ca-btn-primary" type="submit"><x-admin-icon name="upload" size="16" /> Importer</button>
                            </form>
                        </li>
                    </ol>
                </div>
                <div class="ca-card-footer">
                    <span>Les doublons, chambres pleines et lignes invalides sont signalés sans bloquer les autres lignes. Dossiers et affectations sont validés automatiquement.</span>
                </div>
            </div>
            @endadminroute

            @if($lignesRefusees)
                <div class="ca-card">
                    <div class="ca-card-header">
                        <h2 class="ca-card-title"><span class="ca-kpi-icon tone-red"><x-admin-icon name="triangle-alert" /></span> Lignes non enregistrées</h2>
                        <span class="ca-card-meta">{{ count($lignesRefusees) }}</span>
                    </div>
                    <div class="ca-card-body">
                        <ul class="ca-small" style="margin:0;padding-left:18px">
                            @foreach($lignesRefusees as $ligne)
                                <li style="margin-bottom:6px">{{ $ligne }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </aside>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const cite = document.getElementById('cite_id');
        const batiment = document.getElementById('batiment_id');
        const chambre = document.getElementById('chambre_id');
        if (!cite || !batiment || !chambre) return;

        // Affiche uniquement les options rattachées au parent choisi ; conserve la sélection si elle reste valide.
        function filtrer(select, attribut, valeur, invite, attente) {
            let selectionValide = false;
            select.querySelectorAll('option[data-' + attribut + ']').forEach(function (option) {
                option.hidden = option.dataset[attribut] !== valeur;
                if (option.selected && !option.hidden) selectionValide = true;
            });
            if (!selectionValide) select.value = '';
            select.disabled = !valeur;
            select.options[0].textContent = valeur ? invite : attente;
        }

        function majBatiments() {
            filtrer(batiment, 'cite', cite.value, 'Sélectionner un bâtiment', 'Sélectionner d’abord une cité');
            majChambres();
        }

        function majChambres() {
            filtrer(chambre, 'batiment', batiment.value, 'Sélectionner une chambre', 'Sélectionner d’abord un bâtiment');
            if (batiment.value && !chambre.querySelector('option[data-batiment]:not([hidden])')) {
                chambre.options[0].textContent = 'Aucune chambre libre dans ce bâtiment';
            }
        }

        cite.addEventListener('change', majBatiments);
        batiment.addEventListener('change', majChambres);
        majBatiments();
    });
</script>
@endsection
