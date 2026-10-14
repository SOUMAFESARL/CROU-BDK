@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'vie-cite'
])

@section('title', 'Modifier l\'ordre de travail')

@section('content')

    <x-breadcrumbs
            title="Modifier l'ordre de travail"
            :items="[
            'Gestion Hébergement' => route('admin.hebergement.index'),
            'Vie résidentielle' => route('admin.hebergement.vie_residentielle.index'),
            'Maintenance' => route('admin.hebergement.maintenance.index'),
            $ordre->numero_ot => route('admin.hebergement.maintenance.show', $ordre->id_ordres_travail),
            'Modifier' => null,
        ]"
    />

    <div class="ot-edit-container">

        <!-- EN-TÊTE -->
        <div class="ot-edit-header">
            <div>
                <h1>✏️ Modifier l'ordre de travail</h1>
                <p class="subtitle">#{{ $ordre->numero_ot }} · {{ $ordre->statut_label }}</p>
            </div>
            <div class="header-actions">
                @adminroute('admin.hebergement.maintenance.show')
                <a href="{{ route('admin.hebergement.maintenance.show', $ordre->id_ordres_travail) }}" class="btn btn-secondary">
                    ↩️ Retour
                </a>
                @endadminroute
                <button type="submit" form="editForm" class="btn btn-primary">
                    💾 Enregistrer les modifications
                </button>
            </div>
        </div>

        <!-- FORMULAIRE -->
        @adminroute('admin.hebergement.maintenance.update')
        <form id="editForm" action="{{ route('admin.hebergement.maintenance.update', $ordre->id_ordres_travail) }}" method="post" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="ot-edit-grid">

                <!-- COLONNE GAUCHE -->
                <div class="ot-edit-left">

                    <!-- Informations générales -->
                    <div class="panel">
                        <div class="panel-header">
                            <h2>📋 Informations générales</h2>
                            <span class="panel-badge">Champs obligatoires *</span>
                        </div>
                        <div class="panel-body">
                            <div class="form-group">
                                <label class="form-label">Titre <span class="required">*</span></label>
                                <input type="text" class="form-control @error('titre') is-invalid @enderror"
                                       name="titre" value="{{ old('titre', $ordre->titre) }}"
                                       placeholder="Ex: Panne électrique dans la chambre B-214" required>
                                @error('titre')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label">Description <span class="required">*</span></label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                          name="description" rows="4"
                                          placeholder="Décrivez le problème en détail..." required>{{ old('description', $ordre->description) }}</textarea>
                                @error('description')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Catégorie <span class="required">*</span></label>
                                    <select class="form-control @error('categorie') is-invalid @enderror" name="categorie" required>
                                        <option value="">— Sélectionner —</option>
                                        <option value="electricite" {{ old('categorie', $ordre->categorie) == 'electricite' ? 'selected' : '' }}>⚡ Électricité</option>
                                        <option value="eau" {{ old('categorie', $ordre->categorie) == 'eau' ? 'selected' : '' }}>💧 Eau</option>
                                        <option value="plomberie" {{ old('categorie', $ordre->categorie) == 'plomberie' ? 'selected' : '' }}>🚰 Plomberie</option>
                                        <option value="sanitaire" {{ old('categorie', $ordre->categorie) == 'sanitaire' ? 'selected' : '' }}>🚽 Sanitaire</option>
                                        <option value="mobilier" {{ old('categorie', $ordre->categorie) == 'mobilier' ? 'selected' : '' }}>🪑 Mobilier</option>
                                        <option value="securite" {{ old('categorie', $ordre->categorie) == 'securite' ? 'selected' : '' }}>🔒 Sécurité</option>
                                        <option value="climatisation" {{ old('categorie', $ordre->categorie) == 'climatisation' ? 'selected' : '' }}>❄️ Climatisation</option>
                                        <option value="batiment" {{ old('categorie', $ordre->categorie) == 'batiment' ? 'selected' : '' }}>🏗️ Bâtiment</option>
                                        <option value="autre" {{ old('categorie', $ordre->categorie) == 'autre' ? 'selected' : '' }}>📌 Autre</option>
                                    </select>
                                    @error('categorie')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Priorité <span class="required">*</span></label>
                                    <select class="form-control @error('priorite') is-invalid @enderror" name="priorite" required>
                                        <option value="">— Sélectionner —</option>
                                        <option value="critique" {{ old('priorite', $ordre->priorite) == 'critique' ? 'selected' : '' }}>🔴 Critique</option>
                                        <option value="elevee" {{ old('priorite', $ordre->priorite) == 'elevee' ? 'selected' : '' }}>🟠 Élevée</option>
                                        <option value="moyenne" {{ old('priorite', $ordre->priorite) == 'moyenne' ? 'selected' : '' }}>🟡 Moyenne</option>
                                        <option value="faible" {{ old('priorite', $ordre->priorite) == 'faible' ? 'selected' : '' }}>🟢 Faible</option>
                                    </select>
                                    @error('priorite')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Type d'intervention <span class="required">*</span></label>
                                    <select class="form-control @error('type_intervention') is-invalid @enderror" name="type_intervention" required>
                                        <option value="">— Sélectionner —</option>
                                        <option value="corrective" {{ old('type_intervention', $ordre->type_intervention) == 'corrective' ? 'selected' : '' }}>🔧 Corrective</option>
                                        <option value="preventive" {{ old('type_intervention', $ordre->type_intervention) == 'preventive' ? 'selected' : '' }}>🛡️ Préventive</option>
                                        <option value="urgente" {{ old('type_intervention', $ordre->type_intervention) == 'urgente' ? 'selected' : '' }}>🚨 Urgente</option>
                                    </select>
                                    @error('type_intervention')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Date d'échéance</label>
                                    <input type="date" class="form-control @error('date_echeance') is-invalid @enderror"
                                           name="date_echeance" value="{{ old('date_echeance', $ordre->date_echeance ? $ordre->date_echeance->format('Y-m-d') : '') }}">
                                    @error('date_echeance')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Localisation -->
                    <div class="panel">
                        <div class="panel-header">
                            <h2>📍 Localisation</h2>
                        </div>
                        <div class="panel-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Résidence</label>
                                    <select class="form-control @error('cite_id') is-invalid @enderror" name="cite_id" id="cite_id">
                                        <option value="">— Sélectionner —</option>
                                        @foreach($cites as $cite)
                                            <option value="{{ $cite->id_cite }}" {{ old('cite_id', $ordre->cite_id) == $cite->id_cite ? 'selected' : '' }}>
                                                {{ $cite->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('cite_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Bâtiment</label>
                                    <select class="form-control @error('batiment_id') is-invalid @enderror" name="batiment_id" id="batiment_id">
                                        <option value="">— Sélectionner —</option>
                                        @foreach($batiments as $batiment)
                                            <option value="{{ $batiment->id_batiments }}" {{ old('batiment_id', $ordre->batiment_id) == $batiment->id_batiments ? 'selected' : '' }}>
                                                {{ $batiment->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('batiment_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Chambre</label>
                                    <select class="form-control @error('chambre_id') is-invalid @enderror" name="chambre_id" id="chambre_id">
                                        <option value="">— Sélectionner —</option>
                                        @foreach($chambres as $chambre)
                                            <option value="{{ $chambre->id_chambres }}" {{ old('chambre_id', $ordre->chambre_id) == $chambre->id_chambres ? 'selected' : '' }}>
                                                {{ $chambre->numero_chambre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('chambre_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Équipement</label>
                                    <select class="form-control @error('equipement_id') is-invalid @enderror" name="equipement_id">
                                        <option value="">— Sélectionner —</option>
                                        @foreach($equipements as $equipement)
                                            <option value="{{ $equipement->id_equipements }}" {{ old('equipement_id', $ordre->equipement_id) == $equipement->id_equipements ? 'selected' : '' }}>
                                                {{ $equipement->nom }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('equipement_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Résident concerné</label>
                                <select class="form-control @error('resident_id') is-invalid @enderror" name="resident_id">
                                    <option value="">— Sélectionner —</option>
                                    @foreach($residents ??[] as $resident)
                                        <option value="{{ $resident->id_residents }}" {{ old('resident_id', $ordre->resident_id) == $resident->id_residents ? 'selected' : '' }}>
                                            {{ $resident->name }} ({{ $resident->matricule ?? 'N/A' }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('resident_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLONNE DROITE -->
                <div class="ot-edit-right">

                    <!-- Statut & Assignation -->
                    <div class="panel">
                        <div class="panel-header">
                            <h2>📊 Statut & Assignation</h2>
                        </div>
                        <div class="panel-body">
                            <div class="form-group">
                                <label class="form-label">Statut actuel</label>
                                <div class="statut-display">
                                    <span class="status-badge {{ $ordre->statut }}">
                                        {{ $ordre->statut_label }}
                                    </span>
                                    <span class="status-hint">
                                        @switch($ordre->statut)
                                            @case('en_attente')
                                                En attente d'assignation
                                                @break
                                            @case('en_cours')
                                                Intervention en cours
                                                @break
                                            @case('en_attente_pieces')
                                                En attente de pièces
                                                @break
                                            @case('termine')
                                                Intervention terminée
                                                @break
                                            @case('annule')
                                                Annulé
                                                @break
                                            @case('reporte')
                                                Reporté
                                                @break
                                            @default
                                                {{ $ordre->statut_label }}
                                        @endswitch
                                    </span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Assigné à</label>
                                <select class="form-control @error('assigne_a') is-invalid @enderror" name="assigne_a">
                                    <option value="">— Non assigné —</option>
                                    @foreach($techniciens ??[] as $technicien)
                                        <option value="{{ $technicien->id }}" {{ old('assigne_a', $ordre->assigne_a) == $technicien->id ? 'selected' : '' }}>
                                            {{ $technicien->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('assigne_a')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            @if($ordre->date_debut_intervention)
                                <div class="info-row">
                                    <span class="info-label">Date de début</span>
                                    <span class="info-value">{{ $ordre->date_debut_intervention->format('d/m/Y à H:i') }}</span>
                                </div>
                            @endif

                            @if($ordre->date_fin_intervention)
                                <div class="info-row">
                                    <span class="info-label">Date de fin</span>
                                    <span class="info-value">{{ $ordre->date_fin_intervention->format('d/m/Y à H:i') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Coûts -->
                    <div class="panel">
                        <div class="panel-header">
                            <h2>💰 Coûts</h2>
                        </div>
                        <div class="panel-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Coût estimé (FCFA)</label>
                                    <input type="number" class="form-control @error('cout_estime') is-invalid @enderror"
                                           name="cout_estime" value="{{ old('cout_estime', $ordre->cout_estime) }}"
                                           placeholder="0" step="100">
                                    @error('cout_estime')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Coût réel (FCFA)</label>
                                    <input type="number" class="form-control @error('cout_reel') is-invalid @enderror"
                                           name="cout_reel" value="{{ old('cout_reel', $ordre->cout_reel) }}"
                                           placeholder="0" step="100">
                                    @error('cout_reel')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Temps passé (heures)</label>
                                <input type="number" class="form-control @error('temps_passe') is-invalid @enderror"
                                       name="temps_passe" value="{{ old('temps_passe', $ordre->temps_passe) }}"
                                       placeholder="0" step="0.5" min="0">
                                @error('temps_passe')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Pièces détachées -->
                    <div class="panel">
                        <div class="panel-header">
                            <h2>🔩 Pièces détachées</h2>
                            @adminroute('admin.hebergement.maintenance.intervention.store')
<button type="button" class="btn btn-sm btn-primary" onclick="addPiece()">➕ Ajouter</button>
@endadminroute

                        </div>
                        <div class="panel-body" id="pieces-container">
                            @php
                               // $pieces = old('pieces', $ordre->piecesDetachees->toArray() ?? []);
                               $pieces = [];
                                $piecesCount = count($pieces);
                            @endphp

                            @if($piecesCount > 0)
                                @foreach($pieces as $index => $piece)
                                    <div class="piece-row" data-index="{{ $index }}">
                                        <div class="form-row">
                                            <div class="form-group" style="flex:2;">
                                                <select class="form-control" name="pieces[{{ $index }}][id]">
                                                    <option value="">— Sélectionner —</option>
                                                    @foreach($piecesDisponibles as $p)
                                                        <option value="{{ $p->id_pieces_detachees }}"
                                                                {{ (old('pieces.' . $index . '.id', $piece['id'] ?? '') == $p->id_pieces_detachees) ? 'selected' : '' }}>
                                                            {{ $p->nom }} ({{ $p->reference }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group" style="flex:1;">
                                                <input type="number" class="form-control"
                                                       name="pieces[{{ $index }}][quantite]"
                                                       value="{{ old('pieces.' . $index . '.quantite', $piece['pivot']['quantite'] ?? 1) }}"
                                                       placeholder="Qté" min="1">
                                            </div>
                                            <div class="form-group" style="flex:0 0 auto;">
                                                <button type="button" class="btn btn-danger btn-sm" onclick="removePiece(this)">✕</button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="empty-pieces" id="empty-pieces">
                                    <p style="text-align:center; color:var(--muted); padding:20px 0;">
                                        🔩 Aucune pièce détachée associée
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Photos -->
                    <div class="panel">
                        <div class="panel-header">
                            <h2>📸 Photos</h2>
                        </div>
                        <div class="panel-body">
                            <div class="form-group">
                                <label class="form-label">Ajouter des photos</label>
                                <div class="file-upload">
                                    <input type="file" id="photos" name="photos[]" multiple accept="image/*" hidden>
                                    <label for="photos" class="file-upload-label">
                                        <span class="upload-icon">📎</span>
                                        <span>Glissez vos photos ici</span>
                                        <span class="upload-hint">ou cliquez pour parcourir</span>
                                    </label>
                                    <div class="file-list" id="file-list"></div>
                                </div>
                            </div>

                            @if($ordre->photos)
                                <div class="existing-photos">
                                    <label class="form-label">Photos existantes</label>
                                    <div class="photo-grid">
                                        @foreach(json_decode($ordre->photos, true) ?? [] as $photo)
                                            <div class="photo-item">
                                                <img src="{{ asset('storage/' . $photo) }}" alt="Photo">
                                                <button type="button" class="photo-remove" onclick="removePhoto(this)">✕</button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </form>
        @endadminroute
    </div>

@endsection

@push('styles')
    <style>
        /* ============================================
           STYLES - MAINTENANCE EDIT
        ============================================ */

        :root {
            --primary: #1e7aa8;
            --primary-dark: #166a94;
            --success: #2a8d6c;
            --warning: #d68a3a;
            --danger: #b13e3e;
            --info: #5b7b99;
            --border: #dae2ec;
            --bg-light: #f4f7fc;
            --text: #0a2b44;
            --muted: #5b7b99;
            --radius: 16px;
            --shadow: 0 4px 16px rgba(0, 20, 40, 0.06);
        }

        .ot-edit-container {
            max-width: 1440px;
            margin: 0 auto;
            padding: 0 4px;
        }

        /* --- EN-TÊTE --- */
        .ot-edit-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            padding: 20px 24px;
            background: #fff;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 24px;
        }

        .ot-edit-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text);
            margin: 0;
        }

        .ot-edit-header .subtitle {
            font-size: var(--app-font-min, 15px);
            color: var(--muted);
            margin: 2px 0 0;
        }

        .header-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-weight: 500;
            font-size: var(--app-font-min, 15px);
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 122, 168, 0.3);
        }

        .btn-secondary {
            background: var(--muted);
            color: #fff;
        }

        .btn-secondary:hover {
            background: #4a6a8a;
        }

        .btn-sm {
            padding: 6px 14px;
            font-size: var(--app-font-min, 15px);
        }

        .btn-danger {
            background: var(--danger);
            color: #fff;
        }

        .btn-danger:hover {
            background: #9a3333;
        }

        /* --- GRILLE --- */
        .ot-edit-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            align-items: start;
        }

        /* --- PANELS --- */
        .panel {
            background: #fff;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 24px;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 20px;
            border-bottom: 1px solid var(--border);
            background: var(--bg-light);
        }

        .panel-header h2 {
            font-size: 15px;
            font-weight: 600;
            color: var(--text);
            margin: 0;
        }

        .panel-badge {
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
            background: #e7edf5;
            padding: 2px 12px;
            border-radius: 30px;
            color: var(--info);
        }

        .panel-body {
            padding: 20px;
        }

        /* --- FORMULAIRE --- */
        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-size: var(--app-font-min, 15px);
            font-weight: 600;
            color: var(--text);
            margin-bottom: 4px;
        }

        .form-label .required {
            color: var(--danger);
        }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: var(--app-font-min, 15px);
            font-family: inherit;
            transition: 0.2s;
            background: #fff;
        }

        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(30, 122, 168, 0.1);
        }

        .form-control.is-invalid {
            border-color: var(--danger);
        }

        .invalid-feedback {
            display: block;
            font-size: var(--app-font-min, 15px);
            color: var(--danger);
            margin-top: 4px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-row .form-group:last-child {
            margin-bottom: 0;
        }

        /* --- STATUT --- */
        .statut-display {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: var(--bg-light);
            border-radius: 10px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 30px;
            font-size: var(--app-font-min, 15px);
            font-weight: 600;
        }

        .status-badge.en_attente {
            background: #fce1e1;
            color: var(--danger);
        }

        .status-badge.en_cours {
            background: #fcead8;
            color: var(--warning);
        }

        .status-badge.en_attente_pieces {
            background: #dce8f5;
            color: var(--info);
        }

        .status-badge.termine {
            background: #ddf0e8;
            color: var(--success);
        }

        .status-badge.annule {
            background: #e7edf5;
            color: var(--muted);
        }

        .status-badge.reporte {
            background: #fcead8;
            color: var(--warning);
        }

        .status-hint {
            font-size: var(--app-font-min, 15px);
            color: var(--muted);
        }

        /* --- INFO ROW --- */
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--border);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-size: var(--app-font-min, 15px);
            color: var(--muted);
        }

        .info-value {
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
            color: var(--text);
        }

        /* --- PIÈCES --- */
        .piece-row {
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
        }

        .piece-row:last-child {
            border-bottom: none;
        }

        .empty-pieces {
            color: var(--muted);
        }

        /* --- FILE UPLOAD --- */
        .file-upload {
            border: 2px dashed var(--border);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: 0.2s;
        }

        .file-upload:hover {
            border-color: var(--primary);
            background: var(--bg-light);
        }

        .file-upload-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            cursor: pointer;
        }

        .file-upload-label .upload-icon {
            font-size: 28px;
        }

        .file-upload-label .upload-hint {
            font-size: var(--app-font-min, 15px);
            color: var(--muted);
        }

        .file-list {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .file-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            background: var(--bg-light);
            border-radius: 30px;
            font-size: var(--app-font-min, 15px);
        }

        .file-tag .remove-file {
            cursor: pointer;
            color: var(--danger);
            font-weight: 700;
        }

        /* --- PHOTOS --- */
        .photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 10px;
            margin-top: 8px;
        }

        .photo-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            aspect-ratio: 1;
        }

        .photo-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-remove {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: none;
            background: rgba(0,0,0,0.6);
            color: #fff;
            font-size: var(--app-font-min, 15px);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .photo-remove:hover {
            background: var(--danger);
        }

        /* --- RESPONSIVE --- */
        @media (max-width: 1024px) {
            .ot-edit-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .ot-edit-header {
                flex-direction: column;
                align-items: stretch;
            }

            .header-actions {
                flex-direction: column;
            }

            .header-actions .btn {
                width: 100%;
                justify-content: center;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .panel-body {
                padding: 16px;
            }
        }

        @media (max-width: 480px) {
            .ot-edit-header h1 {
                font-size: 20px;
            }

            .statut-display {
                flex-direction: column;
                align-items: flex-start;
            }

            .photo-grid {
                grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        // ============================================
        // GESTION DES PIÈCES DÉTACHÉES
        // ============================================
        let pieceIndex = {{ count(old('pieces',  []))   }}; ////count(old('pieces', $ordre->piecesDetachees->toArray() ?? []))

        function addPiece() {
            const container = document.getElementById('pieces-container');
            const emptyPieces = document.getElementById('empty-pieces');

            if (emptyPieces) {
                emptyPieces.style.display = 'none';
            }

            const row = document.createElement('div');
            row.className = 'piece-row';
            row.dataset.index = pieceIndex;

            row.innerHTML = `
            <div class="form-row">
                <div class="form-group" style="flex:2;">
                    <select class="form-control" name="pieces[${pieceIndex}][id]">
                        <option value="">— Sélectionner —</option>
                        @foreach($piecesDisponibles  ?? [] as $p)
            <option value="{{ $p->id_pieces_detachees }}">{{ $p->nom }} ({{ $p->reference }})</option>
                        @endforeach
            </select>
        </div>
        <div class="form-group" style="flex:1;">
            <input type="number" class="form-control" name="pieces[${pieceIndex}][quantite]" value="1" placeholder="Qté" min="1">
                </div>
                <div class="form-group" style="flex:0 0 auto;">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removePiece(this)">✕</button>
                </div>
            </div>
        `;

            container.appendChild(row);
            pieceIndex++;
        }

        function removePiece(button) {
            const row = button.closest('.piece-row');
            if (row) {
                row.remove();

                // Vérifier s'il n'y a plus de pièces
                const remaining = document.querySelectorAll('.piece-row');
                if (remaining.length === 0) {
                    const empty = document.getElementById('empty-pieces');
                    if (empty) {
                        empty.style.display = 'block';
                    }
                }
            }
        }

        // ============================================
        // GESTION DES FICHIERS
        // ============================================
        document.getElementById('photos')?.addEventListener('change', function(e) {
            const fileList = document.getElementById('file-list');
            const files = e.target.files;

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const tag = document.createElement('span');
                tag.className = 'file-tag';
                tag.innerHTML = `${file.name} (${(file.size / 1024).toFixed(1)} KB) <span class="remove-file" onclick="this.parentElement.remove()">×</span>`;
                fileList.appendChild(tag);
            }
        });

        // ============================================
        // SUPPRESSION DE PHOTOS
        // ============================================
        function removePhoto(button) {
            if (confirm('Supprimer cette photo ?')) {
                const item = button.closest('.photo-item');
                if (item) {
                    item.remove();
                }
            }
        }

        // ============================================
        // DYNAMIC LOADING (AJAX)
        // ============================================
        // Charger les bâtiments par résidence
        document.getElementById('cite_id')?.addEventListener('change', function() {
            const citeId = this.value;
            const batimentSelect = document.getElementById('batiment_id');
            const chambreSelect = document.getElementById('chambre_id');

            if (!citeId) {
                batimentSelect.innerHTML = '<option value="">— Sélectionner —</option>';
                chambreSelect.innerHTML = '<option value="">— Sélectionner —</option>';
                return;
            }

            fetch(`{{ url('admin/gestion-hebergement') }}/cites/${citeId}/batiments`)
                .then(response => response.json())
                .then(data => {
                    batimentSelect.innerHTML = '<option value="">— Sélectionner —</option>';
                    data.forEach(batiment => {
                        batimentSelect.innerHTML += `<option value="${batiment.id}">${batiment.name}</option>`;
                    });
                    chambreSelect.innerHTML = '<option value="">— Sélectionner —</option>';
                })
                .catch(error => console.error('Erreur:', error));
        });

        // Charger les chambres par bâtiment
        document.getElementById('batiment_id')?.addEventListener('change', function() {
            const batimentId = this.value;
            const chambreSelect = document.getElementById('chambre_id');

            if (!batimentId) {
                chambreSelect.innerHTML = '<option value="">— Sélectionner —</option>';
                return;
            }

            fetch(`{{ url('admin/gestion-hebergement') }}/batiments/${batimentId}/chambres`)
                .then(response => response.json())
                .then(data => {
                    chambreSelect.innerHTML = '<option value="">— Sélectionner —</option>';
                    data.forEach(chambre => {
                        chambreSelect.innerHTML += `<option value="${chambre.id}">${chambre.numero_chambre}</option>`;
                    });
                })
                .catch(error => console.error('Erreur:', error));
        });

        console.log('✅ Page modification OT initialisée avec succès !');
    </script>
@endpush