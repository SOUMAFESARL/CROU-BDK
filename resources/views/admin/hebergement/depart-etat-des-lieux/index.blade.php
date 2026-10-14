 @extends('admin.layouts.app', [
     'activeMenu' => 'hebergement',
    'activeSubMenu' => 'departs'
])
@section('title', 'Depart et Etat des lieux')

@section('content')




<!-- ===================== HEADER ===================== -->


<div class="departure-page">

    <x-breadcrumbs
            title="Départs et états des lieux"
            :items="[
                        'Gestion Hébergement' => route('admin.hebergement.index'),
                        'Départs et états des lieux' => null,
                    ]"
    />
    <!-- ===================== STATISTIQUES ===================== -->
    <div class="row g-3 mb-4 no-print departure-stats">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="number" id="totalDemandes">0</div>
                        <div class="label">Total demandes</div>
                    </div>
                    <div class="icon">
                        <i class="fas fa-list-ul"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="number" id="enAttente">0</div>
                        <div class="label">En attente</div>
                    </div>
                    <div class="icon">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="number" id="terminees">0</div>
                        <div class="label">Approuvées</div>
                    </div>
                    <div class="icon">
                        <i class="fas fa-sign-out-alt"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card danger">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="number" id="urgentes">0</div>
                        <div class="label">Dans 7 jours</div>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== FILTRES ===================== -->
<p id="departureSearchHelp" class="text-muted">Recherche commune aux demandes, au calendrier et aux historiques.</p>
    <div class="filter-section no-print">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label fw-bold">
                    <i class="fas fa-search me-1"></i> Recherche
                </label>
                <div class="input-group">
                    <input type="text" class="form-control" id="searchStudent"
                           placeholder="Nom, matricule, chambre..." aria-describedby="departureSearchHelp">
                    <button class="btn btn-primary" onclick="applyFilters()">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold"><i class="fas fa-filter me-1"></i> Statut</label>
                <select class="form-select" id="filterStatus" onchange="applyFilters()">
                    <option value="">Tous</option>
                    <option value="en_attente">En attente</option>
                    <option value="approuvee">Approuvée</option>
                    <option value="cloturee">Clôturée</option>
                    <option value="refusee">Refusée/annulée</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold">
                    <i class="fas fa-calendar me-1"></i> Période
                </label>
                <input type="date" class="form-control" id="filterDate" onchange="applyFilters()">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">
                    <i class="fas fa-building me-1"></i> Cité
                </label>
                <select class="form-select" id="filterCite" onchange="applyFilters()">
                    <option value="">Toutes</option>
                    @foreach($cites ?? [] as $cite)
                        <option value="{{ $cite->id_cite }}">{{ $cite->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-reset w-100" onclick="resetFilters()">
                    <i class="fas fa-undo me-1"></i> Réinitialiser
                </button>
            </div>
        </div>
    </div>
    <!-- ===================== ONGLETS ===================== -->
    <ul class="nav nav-tabs mb-4 no-print" id="sortieTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="demandes-tab" data-bs-toggle="tab"
                    data-bs-target="#demandes" type="button" role="tab">
                <i class="fas fa-list me-2"></i> Demandes
                <span class="badge bg-danger ms-2" id="badgeDemandes">0</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="calendrier-tab" data-bs-toggle="tab"
                    data-bs-target="#calendrier" type="button" role="tab">
                <i class="fas fa-calendar me-2"></i> Calendrier
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="historique-tab" data-bs-toggle="tab"
                    data-bs-target="#historique" type="button" role="tab">
                <i class="fas fa-history me-2"></i> Historique
            </button>
        </li>

      
    </ul>

    <div class="tab-content" id="sortieTabsContent">

        <!-- ===================== TAB DEMANDES ===================== -->
        <div class="tab-pane fade show active" id="demandes" role="tabpanel">
            <div class="table-container">
                <div class="table-toolbar">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>
                        Liste des demandes de sortie
                    </h5>
                    <div class="toolbar-actions">
                        @adminroute('admin.hebergement.depart_etat_des_lieux.settings.edit')
                        <a href="{{ route('admin.hebergement.depart_etat_des_lieux.settings.edit') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-sliders-h me-1"></i> Paramètres de clôture</a>
@endadminroute
                        @adminroute('admin.hebergement.depart_etat_des_lieux.etatDesLieux')
                        <a href="{{ route('admin.hebergement.depart_etat_des_lieux.etatDesLieux') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i> État des lieux
                        </a>
                        @endadminroute
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table " id="demandesTable">
                        <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Étudiant</th>
                            <th>Chambre</th>
                            <th>Décision</th>
                            <th>Sortie prévue</th>
                            <th>Cité</th>
                            <th>Bâtiment</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody id="demandesTableBody">
                        @forelse($requests->where('statut', 'en_attente') as $departure)
                            @php($displayStatus = $departure->decision === 'approuvee' ? 'approuvee' : 'en_attente')
                            <tr data-type="{{ $displayStatus }}" data-date="{{ $departure->date_sortie_prevue?->format('Y-m-d') }}" data-cite="{{ $departure->chambre?->cite_id }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <strong>{{ $departure->etudiant->name ?? 'N/A' }}</strong><br>
                                    <small class="text-muted">{{ $departure->etudiant->matricule ?? '' }}</small>
                                </td>
                                <td>{{ $departure->chambre->numero_chambre ?? 'N/A' }}</td>
                                <td><span class="badge {{ $departure->decision === 'approuvee' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $departure->decision === 'approuvee' ? 'Approuvée' : 'À traiter' }}</span></td>
                                <td>{{ $departure->date_sortie_prevue?->format('d/m/Y') }}</td>
                                <td>{{ $departure->chambre?->cite?->name ?? 'N/A' }}</td>
                                <td>{{ $departure->chambre?->batiment?->name ?? 'N/A' }}</td>
                                <td>
                                    @if($departure->decision === 'approuvee')
                                        @adminroute('admin.hebergement.depart_etat_des_lieux.etatDesLieux')
                                        <a href="{{ route('admin.hebergement.depart_etat_des_lieux.etatDesLieux') }}" class="btn btn-sm btn-success"><i class="fas fa-clipboard-check"></i> État des lieux</a>
@endadminroute
                                    @else
                                        @adminroute('admin.hebergement.depart_etat_des_lieux.departure_request.decision')
                                        <form method="POST" action="{{ route('admin.hebergement.depart_etat_des_lieux.departure_request.decision', $departure->id_demande_sortie) }}" class="d-inline">@csrf @method('PUT')<input type="hidden" name="decision" value="approuvee"><button class="btn btn-sm btn-success"><i class="fas fa-check"></i> Approuver</button></form>
@endadminroute
                                        @adminroute('admin.hebergement.depart_etat_des_lieux.departure_request.decision')
                                        <form method="POST" action="{{ route('admin.hebergement.depart_etat_des_lieux.departure_request.decision', $departure->id_demande_sortie) }}" class="d-inline" onsubmit="this.motif_decision.value=prompt('Motif du refus :')||'';return this.motif_decision.value!=='';">@csrf @method('PUT')<input type="hidden" name="decision" value="refusee"><input type="hidden" name="motif_decision"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i> Refuser</button></form>
@endadminroute
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-inbox fa-2x text-muted d-block mb-2"></i>
                                    <span class="text-muted">Aucune demande de départ enregistrée</span>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        <span class="text-muted" id="paginationInfo">Affichage de {{ $requests->where('statut', 'en_attente')->count() }} demandes</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================== TAB CALENDRIER ===================== -->
        <div class="tab-pane fade" id="calendrier" role="tabpanel">
            <div class="calendar-container">
                @include('admin.hebergement.depart-etat-des-lieux.calendar')
            </div>
        </div>

        <!-- ===================== TAB HISTORIQUE ===================== -->
        <div class="tab-pane fade" id="historique" role="tabpanel">
@include('admin.hebergement.depart-etat-des-lieux.inspection-history')
            <div class="table-container">
                <h5 class="mb-3">
                    <i class="fas fa-history me-2"></i>
                    Historique des sorties
                </h5>
                <div class="table-responsive"><table class="table"><thead><tr><th>Étudiant</th><th>Chambre</th><th>Statut</th><th>Retenues</th><th>Caution à restituer</th><th>Actions</th></tr></thead><tbody>
                @foreach($requests->whereIn('statut', ['cloturee','annulee']) as $departure)
                    <tr data-history-status="{{ $departure->statut === 'cloturee' ? 'cloturee' : 'refusee' }}" data-history-date="{{ $departure->date_sortie_prevue?->format('Y-m-d') }}" data-history-cite="{{ $departure->chambre?->cite_id }}"><td><strong>{{ $departure->etudiant->name ?? 'N/A' }}</strong><br><small>{{ $departure->etudiant?->matricule }} · {{ $departure->date_demande?->format('d/m/Y') }}</small></td><td>{{ $departure->chambre->numero_chambre ?? 'N/A' }}</td><td><span class="badge {{ $departure->statut === 'cloturee' ? 'bg-success' : 'bg-secondary' }}">{{ $departure->statut === 'cloturee' ? 'Clôturée' : ucfirst($departure->decision ?? 'Annulée') }}</span></td><td>{{ number_format((float)($departure->settlement?->total_retenues ?? 0),0,',',' ') }} FCFA</td><td>{{ number_format((float)($departure->settlement?->caution_a_restituer ?? 0),0,',',' ') }} FCFA</td><td>
                        @if($departure->settlement)@adminroute('admin.hebergement.depart_etat_des_lieux.settlement.pdf')
<a class="btn btn-sm btn-outline-primary" href="{{ route('admin.hebergement.depart_etat_des_lieux.settlement.pdf',$departure->id_demande_sortie) }}"><i class="fas fa-file-pdf"></i> PDF</a>
@endadminroute
@endif
                        @if($departure->settlement && (float)$departure->settlement->caution_a_restituer > 0 && !$departure->settlement->date_restitution)
                        @adminroute('admin.hebergement.depart_etat_des_lieux.settlement.refund')
                        <form method="POST" action="{{ route('admin.hebergement.depart_etat_des_lieux.settlement.refund',$departure->settlement->id_decompte) }}" class="d-inline">@csrf<select name="methode_paiement" class="form-select form-select-sm d-inline-block" style="width:130px" required><option value="">Mode</option><option value="especes">Espèces</option><option value="virement">Virement</option><option value="orange_money">Orange Money</option><option value="mtn_money">MTN Money</option><option value="moov_money">Moov Money</option></select><button class="btn btn-sm btn-warning">Restituer</button></form>
@endadminroute
                        @elseif($departure->settlement?->date_restitution)<span class="badge bg-info text-dark">Restituée le {{ $departure->settlement->date_restitution->format('d/m/Y') }}</span>@endif
                    </td></tr>
                @endforeach<tr id="departureHistoryEmpty" @if($requests->whereIn('statut', ['cloturee','annulee'])->isNotEmpty()) hidden @endif><td colspan="6" class="text-center text-muted">Aucune sortie pour ces filtres.</td></tr>
                </tbody></table></div>
            </div>
        </div>
    </div>

</div>


<!-- ===================== MODAL NOUVELLE DEMANDE ===================== -->
<div class="modal fade" id="newDemandeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i>
                    Nouvelle demande de sortie
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="newDemandeForm">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Étudiant *</label>
                        <select class="form-select" required id="demandeEtudiant">
                            <option value="">Sélectionner un étudiant</option>
                            @foreach($activeStays as $stay)
                                <option value="{{ $stay->etudiant_id }}">{{ $stay->etudiant->name }} — {{ $stay->etudiant->matricule }} · Chambre {{ $stay->chambre->numero_chambre }}{{ $departureRequests->has($stay->etudiant_id) ? ' (préavis déjà enregistré)' : '' }}</option>
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

<!-- ===================== MODAL ÉTAT DES LIEUX ===================== -->
<div class="modal fade" id="edlModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-clipboard-list me-2"></i>
                    État des lieux de sortie
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

                    <div id="edlPieces">
                        <!-- Pièces générées dynamiquement -->
                    </div>

                    @adminroute('admin.hebergement.depart_etat_des_lieux.store_departure_request')
<button type="button" class="btn btn-outline-primary btn-sm mb-3" onclick="addPiece()">
                        <i class="fas fa-plus me-1"></i> Ajouter une pièce
                    </button>
@endadminroute


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

<!-- ===================== MODAL DÉTAILS DEMANDE ===================== -->
<div class="modal fade" id="demandeDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>
                    Détails de la demande
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="demandeDetailContent">
                <!-- Rempli dynamiquement -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" id="detailActionBtn">Action</button>
            </div>
        </div>
    </div>
</div>


<!-- ===================== SCRIPTS ===================== -->


@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- FullCalendar CSS -->
    
    <style>
        .departure-page {
            --departure-green: #1f7c43;
            --departure-green-dark: #176236;
            --departure-orange: #f5a623;
            --departure-border: #e5e7eb;
            --departure-muted: #6b7280;
            width: 100%;
            font-family: 'Montserrat', sans-serif;
        }

        .departure-page .stat-card {
            min-height: 118px;
            padding: 22px;
            background: #fff;
            border: 1px solid #edf0ee;
            border-radius: 20px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .06);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .departure-page .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(31, 124, 67, .1);
        }

        .departure-page .stat-card .icon {
            display: grid;
            width: 48px;
            height: 48px;
            place-items: center;
            border-radius: 14px;
            background: #ecf8f0;
            color: var(--departure-green);
            font-size: 20px;
        }

        .departure-page .stat-card.warning .icon { background: #fff7e8; color: #c87900; }
        .departure-page .stat-card.success .icon { background: #eaf7ef; color: #198754; }
        .departure-page .stat-card.danger .icon { background: #fff0f0; color: #dc3545; }

        .departure-page .stat-card .number {
            color: #1f2937;
            font-size: 28px;
            font-weight: 700;
            line-height: 1.2;
        }

        .departure-page .stat-card .label {
            margin-top: 6px;
            color: var(--departure-muted) !important;
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
        }

        .departure-page .filter-section,
        .departure-page .table-container,
        .departure-page .calendar-container {
            padding: 24px;
            background: #fff;
            border: 1px solid #edf0ee;
            border-radius: 20px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .06);
        }

        .departure-page .filter-section { margin-bottom: 24px; }

        .departure-page .form-label {
            margin-bottom: 8px;
            color: #374151;
            font-size: var(--app-font-min, 15px);
            font-weight: 600 !important;
        }

        .departure-page .form-control,
        .departure-page .form-select {
            min-height: 44px;
            border-color: #d1d5db;
            border-radius: 12px;
            font-size: var(--app-font-min, 15px);
        }

        .departure-page .form-control:focus,
        .departure-page .form-select:focus {
            border-color: var(--departure-green);
            box-shadow: 0 0 0 3px rgba(31, 124, 67, .12);
        }

        .departure-page .btn {
            border-radius: 12px;
            font-weight: 600;
        }

        .departure-page .btn-primary {
            border-color: var(--departure-green);
            background: var(--departure-green);
        }

        .departure-page .btn-primary:hover,
        .departure-page .btn-primary:focus {
            border-color: var(--departure-green-dark);
            background: var(--departure-green-dark);
        }

        .departure-page .btn-reset {
            min-height: 44px;
            border: 1px solid #d1d5db;
            background: #fff;
            color: #4b5563;
        }

        .departure-page .btn-reset:hover { background: #f9fafb; color: #111827; }

        .departure-page .nav-tabs {
            gap: 8px;
            padding: 6px;
            border: 0;
            border-radius: 14px;
            background: #eaf5ee;
        }

        .departure-page .nav-tabs .nav-link {
            border: 0;
            border-radius: 10px;
            color: #4b5563;
            font-size: var(--app-font-min, 15px);
            font-weight: 600;
        }

        .departure-page .nav-tabs .nav-link.active {
            background: #fff;
            color: var(--departure-green);
            box-shadow: 0 3px 10px rgba(31, 124, 67, .1);
        }

        .departure-page .table-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
        }

        .departure-page .table-toolbar h5 { color: var(--departure-green); font-weight: 600; }
        .departure-page .toolbar-actions { display: flex; flex-wrap: wrap; gap: 10px; }

        .departure-page .table thead th {
            padding: 14px 12px;
            border-bottom: 2px solid var(--departure-border);
            background: #fff;
            color: #4b5563;
            font-size: var(--app-font-min, 15px);
            font-weight: 600;
            letter-spacing: .02em;
            text-transform: none;
            white-space: nowrap;
        }

        .departure-page .table tbody td {
            padding: 15px 12px;
            border-color: #f0f1f2;
            color: #374151;
            font-size: var(--app-font-min, 15px);
            vertical-align: middle;
        }

        .departure-page .table tbody tr:hover { background: #f8fbf9; }

        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: var(--app-font-min, 15px);
            font-weight: 600;
        }

        .badge-status.en-attente {
            background: #fefcbf;
            color: #975a16;
        }

        .badge-status.approuve {
            background: #c6f6d5;
            color: #22543d;
        }

        .badge-status.refuse {
            background: #fed7d7;
            color: #9b2c2c;
        }

        .badge-status.termine {
            background: #bee3f8;
            color: #2a69ac;
        }

        .btn-action {
            padding: 4px 10px;
            font-size: var(--app-font-min, 15px);
            border-radius: 8px;
            margin: 2px;
            transition: all 0.2s;
        }

        .btn-action:hover {
            transform: scale(1.05);
        }

        .departure-page + .modal .modal-content,
        .departure-page ~ .modal .modal-content {
            overflow: hidden;
            border: 0;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, .18);
        }

        .departure-page + .modal .modal-header,
        .departure-page ~ .modal .modal-header {
            background: #1f7c43;
            color: white;
        }

        .departure-page ~ .modal .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        .edl-preview {
            background: #f7fafc;
            border-radius: 10px;
            padding: 15px;
            margin: 10px 0;
        }

        .signature-pad {
            border: 2px dashed #cbd5e0;
            border-radius: 10px;
            height: 120px;
            background: white;
            cursor: crosshair;
        }

        .signature-pad.drawn {
            border-color: #48bb78;
            border-style: solid;
        }

        .timeline-item {
            padding: 15px;
            border-left: 3px solid #1f7c43;
            margin-bottom: 15px;
            background: #f7fafc;
            border-radius: 0 10px 10px 0;
        }

        .timeline-item .date {
            font-size: var(--app-font-min, 15px);
            color: #718096;
        }

        .timeline-item .content {
            font-size: var(--app-font-min, 15px);
        }

        @media print {
            .no-print { display: none !important; }
            .stat-card { border: 1px solid #ddd !important; }
        }

        @media (max-width: 767.98px) {
            .departure-page .filter-section,
            .departure-page .table-container,
            .departure-page .calendar-container { padding: 16px; border-radius: 16px; }
            .departure-page .table-toolbar { align-items: flex-start; flex-direction: column; }
            .departure-page .toolbar-actions { width: 100%; }
            .departure-page .toolbar-actions .btn { flex: 1 1 auto; }
            .departure-page .nav-tabs { flex-wrap: nowrap; overflow-x: auto; }
            .departure-page .nav-tabs .nav-link { white-space: nowrap; }
        }

        /* Animation pour les nouvelles entrées */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
@endpush

@push('scripts')
<script>
async function submitDemande() {
    const data = {
        etudiant_id: document.getElementById('demandeEtudiant').value,
        date_sortie_prevue: document.getElementById('demandeDate').value,
        motif: document.getElementById('demandeMotif').value,
        observations: document.getElementById('demandeObservations').value
    };
    if (!data.etudiant_id || !data.date_sortie_prevue || !data.motif) return alert('Veuillez renseigner tous les champs obligatoires.');
    const response = await fetch(@json(route('admin.hebergement.depart_etat_des_lieux.store_departure_request')), {
        method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content || ''}, body:JSON.stringify(data)
    });
    const result = await response.json();
    if (!response.ok) return alert(result.message || Object.values(result.errors || {}).flat().join('\n') || 'Erreur lors de l’enregistrement.');
    alert(result.message); location.reload();
}
</script>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/departure-calendar.js') }}?v=20261013-2"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            setupFilters();
            applyFilters();
            updateStats();
        });

        function resetFilters() { ['searchStudent', 'filterStatus', 'filterDate', 'filterCite'].forEach(id => document.getElementById(id).value = ''); applyFilters(); }
        function setupFilters() {
            ['searchStudent', 'filterStatus', 'filterDate', 'filterCite'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', applyFilters);
                }
            });
        }

        function applyFilters() {
            const search = window.departureSearchText(document.getElementById('searchStudent').value);
            const type = document.getElementById('filterStatus').value;
            const date = document.getElementById('filterDate').value;
            const cite = document.getElementById('filterCite').value;
            const rows = document.querySelectorAll('#demandesTableBody tr[data-type]');
            window.departureCalendar?.refresh();
            let visibleCount = 0;

            rows.forEach(row => {
                const rowText = window.departureSearchText(row.textContent);
                const matchesSearch = !search || rowText.includes(search);
                const matchesType = !type || row.dataset.type === type;
                const matchesDate = !date || row.dataset.date === date;
                const matchesCite = !cite || row.dataset.cite === cite;
                const visible = matchesSearch && matchesType && matchesDate && matchesCite;

                row.style.display = visible ? '' : 'none';
                if (visible) visibleCount++;
            });

            document.getElementById('paginationInfo').textContent = `Affichage de ${visibleCount} sur ${rows.length} demandes`;
        }

        function updateStats() {
            const rows = document.querySelectorAll('#demandesTableBody tr[data-type]');
            let total = 0;
            let entree = 0;
            let sortie = 0;
            let week = 0;
            const now = new Date();

            rows.forEach(row => {
                total++;
                if (row.dataset.type === 'en_attente') {
                    entree++;
                }
                if (row.dataset.type === 'approuvee') {
                    sortie++;
                }
                if (row.dataset.date) {
                    const rowDate = new Date(row.dataset.date);
                    const diff = (rowDate - now) / (1000 * 60 * 60 * 24);
                    if (diff >= 0 && diff <= 7) {
                        week++;
                    }
                }
            });

            document.getElementById('totalDemandes').textContent = total;
            document.getElementById('enAttente').textContent = entree;
            document.getElementById('terminees').textContent = sortie;
            document.getElementById('urgentes').textContent = week;
            document.getElementById('badgeDemandes').textContent = total;
        }
    </script>
@endpush
