<!-- Modal : État des lieux -->
<div class="modal fade" id="edlModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-clipboard-list me-2"></i> État des lieux de sortie
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="edlStudentInfo" class="mb-3"></div>

                <form id="edlForm">
                    <input type="hidden" id="edlDemandeId">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date de l'état des lieux</label>
                            <input type="date" class="form-control" id="edlDate">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Clés rendues</label>
                            <input type="number" class="form-control" id="edlCles" value="1" min="0">
                        </div>
                    </div>

                    <hr>
                    <h6 class="fw-bold mb-3"><i class="fas fa-door-open me-2"></i>État des pièces</h6>
                    <div id="edlPieces"></div>
                    <button type="button" class="btn btn-outline-primary btn-sm mb-3" onclick="addPiece()">
                        <i class="fas fa-plus me-1"></i> Ajouter une pièce
                    </button>

                    <hr>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Observations générales</label>
                        <textarea class="form-control" id="edlObservations" rows="3"
                                  placeholder="Remarques complémentaires..."></textarea>
                    </div>

                    <hr>
                    <h6 class="fw-bold mb-3"><i class="fas fa-pen me-2"></i>Signatures</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Signature du CROU</label>
                            <div class="signature-pad" id="sigCrou" onclick="signatureClick('sigCrou')">
                                <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                                    <span>Cliquez pour signer</span>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger mt-1"
                                    onclick="clearSignature('sigCrou')">
                                <i class="fas fa-eraser me-1"></i> Effacer
                            </button>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Signature de l'étudiant</label>
                            <div class="signature-pad" id="sigEtudiant" onclick="signatureClick('sigEtudiant')">
                                <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                                    <span>Cliquez pour signer</span>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger mt-1"
                                    onclick="clearSignature('sigEtudiant')">
                                <i class="fas fa-eraser me-1"></i> Effacer
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-success" onclick="saveEdl()">
                    <i class="fas fa-save me-1"></i> Enregistrer l'état des lieux
                </button>
                <button type="button" class="btn btn-info" onclick="printEdl()">
                    <i class="fas fa-print me-1"></i> Imprimer
                </button>
            </div>
        </div>
    </div>
</div>