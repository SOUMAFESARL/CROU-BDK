<div class="form-section no-print">
    <div class="section-title">
        <i class="fas fa-plus-circle me-2"></i> Nouvelle annonce
    </div>

    <form id="announcementForm">
        @csrf
        <input type="hidden" id="editId" value="">

        <!-- Canal de diffusion -->
        <div class="mb-3">
            <label class="form-label fw-bold">Canal de diffusion</label>
            <div class="channel-selector">
                @foreach(['flux' => 'Flux d\'actualité', 'sms' => 'SMS', 'email' => 'Email', 'push' => 'Push'] as $key => $label)
                    <div class="channel-option {{ $key === 'flux' ? 'active' : '' }}" data-channel="{{ $key }}">
                        <input type="checkbox" name="channels[]" value="{{ $key }}" {{ $key === 'flux' ? 'checked' : '' }}>
                        <span class="icon"><i class="fas fa-{{ $key === 'flux' ? 'rss' : ($key === 'sms' ? 'sms' : ($key === 'email' ? 'envelope' : 'bell')) }}"></i></span>
                        <span class="label">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Audience -->
        <div class="mb-3">
            <label class="form-label fw-bold">Audience cible</label>
            <div class="audience-group">
                @foreach(['all' => 'Tous les étudiants', 'cite' => 'Par cité', 'batiment' => 'Par bâtiment', 'chambre' => 'Par chambre', 'specific' => 'Sélection spécifique'] as $key => $label)
                    <button type="button" class="audience-btn {{ $key === 'all' ? 'active' : '' }}" data-audience="{{ $key }}">
                        <i class="fas fa-{{ $key === 'all' ? 'users' : ($key === 'cite' ? 'building' : ($key === 'batiment' ? 'hotel' : ($key === 'chambre' ? 'door-open' : 'user-check'))) }} me-1"></i>
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div id="audienceSelectors" class="mt-3">
                <div class="row g-3" id="audienceCible" style="display: none;">
                    <div class="col-md-6">
                        <label class="form-label">Cités</label>
                        <select class="form-select select2-multiple" multiple id="selectCites">
                            @foreach($cites ?? [] as $cite)
                                <option value="{{ $cite->id_cite }}">{{ $cite->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Bâtiments</label>
                        <select class="form-select select2-multiple" multiple id="selectBatiments">
                            @foreach($batiments ?? [] as $batiment)
                                <option value="{{ $batiment->id_batiment }}">{{ $batiment->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div id="audienceSpecific" style="display: none;">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Étudiants</label>
                            <select class="form-select select2-multiple" multiple id="selectEtudiants">
                                @foreach($etudiants ?? [] as $etudiant)
                                    <option value="{{ $etudiant->id_etudiant }}">{{ $etudiant->name }} - {{ $etudiant->matricule ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Titre -->
        <div class="mb-3">
            <label class="form-label fw-bold">Titre <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="annonceTitre" placeholder="Ex: Réunion d'information" maxlength="100" required>
            <div class="form-text"><span id="charCount">0</span>/100 caractères</div>
        </div>

        <!-- Contenu -->
        <div class="mb-3">
            <label class="form-label fw-bold">Contenu <span class="text-danger">*</span></label>
            <div class="editor-toolbar">
                <button type="button" onclick="insertText('**gras**')" title="Gras"><i class="fas fa-bold"></i></button>
                <button type="button" onclick="insertText('*italique*')" title="Italique"><i class="fas fa-italic"></i></button>
                <button type="button" onclick="insertText('# Titre')" title="Titre"><i class="fas fa-heading"></i></button>
                <button type="button" onclick="insertText('- Liste')" title="Liste"><i class="fas fa-list"></i></button>
                <button type="button" onclick="insertText('[Lien](url)')" title="Lien"><i class="fas fa-link"></i></button>
            </div>
            <textarea class="form-control" id="annonceContenu" rows="5" placeholder="Rédigez votre annonce..." style="border-radius: 0 0 8px 8px;" required></textarea>
        </div>

        <!-- Options -->
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label fw-bold">Statut</label>
                <select class="form-select" id="annonceStatut">
                    <option value="brouillon">Brouillon</option>
                    <option value="publie">Publier immédiatement</option>
                    <option value="planifie">Planifier</option>
                </select>
            </div>
            <div class="col-md-4" id="planificationContainer" style="display: none;">
                <label class="form-label fw-bold">Date de publication</label>
                <input type="datetime-local" class="form-control" id="annonceDatePlanifiee">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Priorité</label>
                <select class="form-select" id="annoncePriorite">
                    <option value="normale">Normale</option>
                    <option value="haute">Haute</option>
                    <option value="urgente">Urgente</option>
                </select>
            </div>
        </div>

        <!-- Boutons -->
        <div class="mt-4">
            <button type="button" class="btn btn-secondary me-2" onclick="resetForm()">
                <i class="fas fa-undo me-1"></i> Réinitialiser
            </button>
            <button type="button" class="btn btn-success" onclick="saveAnnouncement()">
                <i class="fas fa-save me-1"></i> Enregistrer
            </button>
            <button type="button" class="btn btn-primary" onclick="publishAnnouncement()">
                <i class="fas fa-paper-plane me-1"></i> Publier
            </button>
        </div>
    </form>
</div>