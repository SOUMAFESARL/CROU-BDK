<!-- Modal : Nouvelle demande -->
<div class="modal fade" id="newDemandeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i> Nouvelle demande de sortie
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="newDemandeForm">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Étudiant *</label>
                        <select class="form-select" required id="demandeEtudiant">
                            <option value="">Sélectionner un étudiant</option>
                            @foreach($etudiants ?? [] as $etudiant)
                                <option value="{{ $etudiant->id }}">
                                    {{ $etudiant->name }} - {{ $etudiant->matricule ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Date de sortie *</label>
                        <input type="date" class="form-control" required id="demandeDate">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Motif *</label>
                        <select class="form-select" required id="demandeMotif">
                            <option value="">Sélectionner un motif</option>
                            <option value="fin_etudes">Fin d'études</option>
                            <option value="demenagement">Déménagement</option>
                            <option value="stage">Stage professionnel</option>
                            <option value="raison_personnelle">Raison personnelle</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Observations</label>
                        <textarea class="form-control" id="demandeObservations" rows="3"
                                  placeholder="Informations complémentaires..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="submitDemande()">
                    <i class="fas fa-save me-1"></i> Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>