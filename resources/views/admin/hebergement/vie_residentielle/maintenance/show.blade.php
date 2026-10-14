
@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'vie-cite'
])

@section('title', 'Détail de l\'ordre de travail')

@section('content')

    <x-breadcrumbs
            title="Détail de l'ordre de travail"
            :items="[
            'Gestion Hébergement' => route('admin.hebergement.index'),
            'Vie résidentielle' => route('admin.hebergement.vie_residentielle.index'),
            'Maintenance' => route('admin.hebergement.maintenance.index'),
            $ordre->numero_ot => null,
        ]"
    />

    <div class="ot-detail-container">

        <!-- EN-TÊTE -->
        <div class="ot-header">
            <div class="ot-header-left">
                <div class="ot-number">
                    <span class="ot-label">Ordre de travail</span>
                    <h1>#{{ $ordre->numero_ot }}</h1>
                </div>
                <div class="ot-status-badge">
                    <span class="status-badge {{ $ordre->statut }}">
                        {{ $ordre->statut_label }}
                    </span>
                </div>
            </div>
            <div class="ot-header-right">
                <div class="ot-meta">
                    <span class="meta-item">
                        <span class="meta-label">Créé le</span>
                        <span class="meta-value">{{ $ordre->created_at ? $ordre->created_at->format('d/m/Y à H:i') : 'N/A' }}</span>
                    </span>
                    <span class="meta-item">
                        <span class="meta-label">Par</span>
                        <span class="meta-value">{{ $ordre->created_by ? $ordre->createdBy?->name ?? 'Inconnu' : 'Étudiant' }}</span>
                    </span>
                    <span class="meta-item">
                        <span class="meta-label">Dernière mise à jour</span>
                        <span class="meta-value">{{ $ordre->updated_at ? $ordre->updated_at->format('d/m/Y à H:i') : 'N/A' }}</span>
                    </span>
                </div>
                <div class="ot-actions">
                    @if($ordre->statut !== 'termine' && $ordre->statut !== 'annule')
                        @adminroute('admin.hebergement.maintenance.edit')
                        <a href="{{ route('admin.hebergement.maintenance.edit', $ordre->id_ordres_travail) }}" class="btn btn-edit">
                            ✏️ Modifier
                        </a>
                        @endadminroute
                    @endif
                    <button class="btn btn-print" onclick="window.print()">
                        🖨️ Imprimer
                    </button>
                </div>
            </div>
        </div>

        <!-- GRILLE PRINCIPALE -->
        <div class="ot-grid">

            <!-- COLONNE GAUCHE : INFORMATIONS -->
            <div class="ot-left-column">

                <!-- Description -->
                <div class="panel">
                    <div class="panel-header">
                        <h2>📝 Description</h2>
                        <span class="panel-badge">{{ $ordre->categorie_label }}</span>
                    </div>
                    <div class="panel-body">
                        <p class="ot-description">{{ $ordre->description ?? 'Aucune description' }}</p>
                        @if($ordre->cause)
                            <div class="ot-cause">
                                <span class="cause-label">🔍 Cause identifiée</span>
                                <p>{{ $ordre->cause }}</p>
                            </div>
                        @endif
                        @if($ordre->solution_apportee)
                            <div class="ot-solution">
                                <span class="solution-label">✅ Solution apportée</span>
                                <p>{{ $ordre->solution_apportee }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Informations techniques -->
                <div class="panel">
                    <div class="panel-header">
                        <h2>🔧 Informations techniques</h2>
                    </div>
                    <div class="panel-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Type d'intervention</span>
                                <span class="info-value">{{ $ordre->type_intervention_label }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Catégorie</span>
                                <span class="info-value">{{ $ordre->categorie_label }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Priorité</span>
                                <span class="info-value priority-{{ $ordre->priorite }}">{{ $ordre->priorite_label }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Statut</span>
                                <span class="info-value">{{ $ordre->statut_label }}</span>
                            </div>
                            @if($ordre->cout_estime)
                                <div class="info-item">
                                    <span class="info-label">Coût estimé</span>
                                    <span class="info-value">{{ number_format($ordre->cout_estime, 0, ',', ' ') }} FCFA</span>
                                </div>
                            @endif
                            @if($ordre->cout_reel)
                                <div class="info-item">
                                    <span class="info-label">Coût réel</span>
                                    <span class="info-value">{{ number_format($ordre->cout_reel, 0, ',', ' ') }} FCFA</span>
                                </div>
                            @endif
                            @if($ordre->temps_passe)
                                <div class="info-item">
                                    <span class="info-label">Temps passé</span>
                                    <span class="info-value">{{ $ordre->temps_passe }} heures</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Localisation -->
                <div class="panel">
                    <div class="panel-header">
                        <h2>📍 Localisation</h2>
                    </div>
                    <div class="panel-body">
                        <div class="location-grid">
                            <div class="location-item">
                                <span class="location-icon">🏢</span>
                                <div>
                                    <span class="location-label">Résidence</span>
                                    <span class="location-value">{{ $ordre->residence?->name ?? 'Non définie' }}</span>
                                </div>
                            </div>
                            <div class="location-item">
                                <span class="location-icon">🏗️</span>
                                <div>
                                    <span class="location-label">Bâtiment</span>
                                    <span class="location-value">{{ $ordre->batiment?->name ?? 'Non défini' }}</span>
                                </div>
                            </div>
                            <div class="location-item">
                                <span class="location-icon">🚪</span>
                                <div>
                                    <span class="location-label">Chambre</span>
                                    <span class="location-value">{{ $ordre->chambre?->numero_chambre ?? 'Non définie' }}</span>
                                </div>
                            </div>
                            @if($ordre->equipement)
                                <div class="location-item">
                                    <span class="location-icon">🔧</span>
                                    <div>
                                        <span class="location-label">Équipement</span>
                                        <span class="location-value">{{ $ordre->equipement?->nom ?? 'Non défini' }}</span>
                                    </div>
                                </div>
                            @endif
                            @if($ordre->resident)
                                <div class="location-item">
                                    <span class="location-icon">👤</span>
                                    <div>
                                        <span class="location-label">Résident</span>
                                        <span class="location-value">{{ $ordre->resident?->name ?? 'Non défini' }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Assignation -->
                <div class="panel">
                    <div class="panel-header">
                        <h2>👨‍🔧 Assignation</h2>
                        @if($ordre->statut !== 'termine' && $ordre->statut !== 'annule' && $ordre->assigne_a ==null)
                            <button class="btn btn-primary btn-sm" onclick="openAssignationModal('{{ $ordre->id_ordres_travail }}')">
                                👨‍🔧 Assigner
                            </button>
                        @endif
                    </div>
                    <div class="panel-body">
                        @if($ordre->assigne_a)
                            <div class="assignation-info">
                                <div class="assignation-avatar">
                                    {{ getInitials($ordre->assigneA?->name ?? '') }}
                                </div>
                                <div class="assignation-details">
                                    <span class="assignation-name">{{ $ordre->assigneA?->name ?? 'Technicien' }}</span>
                                    <span class="assignation-role">
                                        {{ $ordre->assigneA?->specialite ?? 'Technicien' }}
                                        @if($ordre->assigneA?->email)
                                            · {{ $ordre->assigneA->email }}
                                        @endif
                                    </span>
                                    @if($ordre->assigneA?->tel)
                                        <span class="assignation-contact">📱 {{ $ordre->assigneA->tel }}</span>
                                    @endif
                                    @if($ordre->date_debut_intervention)
                                        <span class="assignation-date">📅 Début : {{ $ordre->date_debut_intervention->format('d/m/Y à H:i') }}</span>
                                    @endif
                                    @if($ordre->date_fin_intervention)
                                        <span class="assignation-date">📅 Fin : {{ $ordre->date_fin_intervention->format('d/m/Y à H:i') }}</span>
                                    @endif
                                </div>
                                @if($ordre->statut !== 'termine' && $ordre->statut !== 'annule')
                                    <button class="btn btn-danger btn-sm" onclick="removeAssignation('{{ $ordre->id_ordres_travail }}')">
                                        ❌
                                    </button>
                                @endif
                            </div>
                        @else
                            <div class="assignation-empty">
                                <span class="empty-icon">👨‍🔧</span>
                                <p>Aucun maintenancier assigné</p>
                                @if($ordre->statut === 'en_attente' || $ordre->statut === 'en_cours')
                                    <button class="btn btn-primary" onclick="openAssignationModal('{{ $ordre->id_ordres_travail }}')">
                                        👨‍🔧 Assigner un maintenancier
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- COLONNE DROITE : TIMELINE & ACTIONS -->
            <div class="ot-right-column">

                <!-- Timeline -->
                <div class="panel">
                    <div class="panel-header">
                        <h2>⏱️ Chronologie</h2>
                        <span class="panel-badge">{{ $ordre->interventions->count() }} interventions</span>
                    </div>
                    <div class="panel-body">
                        <div class="timeline">
                            <!-- Création -->
                            <div class="tl-item">
                                <div class="tl-dot created"></div>
                                <div class="tl-content">
                                    <div class="tl-header">
                                        <span class="tl-title">Demande créée</span>
                                        <span class="tl-date">{{ $ordre->created_at ? $ordre->created_at->format('d/m/Y à H:i') : 'N/A' }}</span>
                                    </div>
                                    <div class="tl-desc">Par {{ $ordre->created_by ? $ordre->createdBy?->name ?? 'Inconnu' : 'Étudiant' }}</div>
                                </div>
                            </div>

                            <!-- Assignation -->
                            @if($ordre->assigne_a)
                                <div class="tl-item">
                                    <div class="tl-dot assigned"></div>
                                    <div class="tl-content">
                                        <div class="tl-header">
                                            <span class="tl-title">Assigné à un maintenancier</span>
                                            <span class="tl-date">{{ $ordre->date_debut_intervention ? $ordre->date_debut_intervention->format('d/m/Y') : 'N/A' }}</span>
                                        </div>
                                        <div class="tl-desc">👨‍🔧 {{ $ordre->assigneA?->name ?? 'N/A' }} - {{ $ordre->assigneA?->specialite ?? '' }}</div>
                                    </div>
                                </div>
                            @endif

                            <!-- Interventions -->
                            @foreach($ordre->interventions as $intervention)
                                <div class="tl-item">
                                    <div class="tl-dot intervention"></div>
                                    <div class="tl-content">
                                        <div class="tl-header">
                                            <span class="tl-title">Intervention réalisée</span>
                                            <span class="tl-date">{{ $intervention->date_intervention ? $intervention->date_intervention->format('d/m/Y à H:i') : 'N/A' }}</span>
                                        </div>
                                        <div class="tl-desc">
                                            {{ $intervention->description ?? 'Intervention sans description' }}
                                            @if($intervention->resultat)
                                                <span class="tl-resultat resultat-{{ $intervention->resultat }}">
                                                {{ $intervention->resultat_label }}
                                            </span>
                                            @endif
                                        </div>
                                        @if($intervention->technicien)
                                            <div class="tl-technicien">👨‍🔧 {{ $intervention->technicien?->name ?? 'Technicien' }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach

                            <!-- Résolution -->
                            @if($ordre->statut === 'termine' && $ordre->date_resolution)
                                <div class="tl-item">
                                    <div class="tl-dot resolved"></div>
                                    <div class="tl-content">
                                        <div class="tl-header">
                                            <span class="tl-title">✅ Résolu</span>
                                            <span class="tl-date">{{ $ordre->date_resolution->format('d/m/Y à H:i') }}</span>
                                        </div>
                                        <div class="tl-desc">Problème résolu avec succès</div>
                                        @if($ordre->taux_satisfaction)
                                            <div class="tl-satisfaction">⭐ Satisfaction : {{ $ordre->taux_satisfaction }}/5</div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Annulation -->
                            @if($ordre->statut === 'annule')
                                <div class="tl-item">
                                    <div class="tl-dot cancelled"></div>
                                    <div class="tl-content">
                                        <div class="tl-header">
                                            <span class="tl-title">❌ Annulé</span>
                                            <span class="tl-date">{{ $ordre->updated_at ? $ordre->updated_at->format('d/m/Y à H:i') : 'N/A' }}</span>
                                        </div>
                                        <div class="tl-desc">Ordre de travail annulé</div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="panel">
                    <div class="panel-header">
                        <h2>⚡ Actions</h2>
                    </div>
                    <div class="panel-body">
                        <div class="action-buttons">
                            @if($ordre->statut === 'en_attente')
                                @if(!$ordre->assigne_a)
                                    <button class="btn btn-primary btn-block" onclick="openAssignationModal('{{ $ordre->id_ordres_travail }}')">
                                        👨‍🔧 Assigner un maintenancier
                                    </button>
                                @endif
                                <button class="btn btn-warning btn-block" onclick="openInterventionModal('{{ $ordre->id_ordres_travail }}')">
                                    ▶️ Démarrer l'intervention
                                </button>
                            @endif

                            @if($ordre->statut === 'en_cours')
                                <button class="btn btn-info btn-block" onclick="updateStatut('{{ $ordre->id_ordres_travail }}', 'en_attente_pieces')">
                                    ⏳ En attente de pièces
                                </button>
                                @adminroute('admin.hebergement.maintenance.intervention.store')
<button class="btn btn-success btn-block" onclick="showInterventionForm('{{ $ordre->id_ordres_travail }}')">
                                    ✅ Ajouter une intervention
                                </button>
@endadminroute

                                <button class="btn btn-success btn-block" onclick="updateStatut('{{ $ordre->id_ordres_travail }}', 'termine')">
                                    ✅ Marquer comme terminé
                                </button>
                            @endif

                            @if($ordre->statut === 'en_attente_pieces')
                                <button class="btn btn-warning btn-block" onclick="updateStatut('{{ $ordre->id_ordres_travail }}', 'en_cours')">
                                    ▶️ Reprendre l'intervention
                                </button>
                                <button class="btn btn-success btn-block" onclick="updateStatut('{{ $ordre->id_ordres_travail }}', 'termine')">
                                    ✅ Marquer comme terminé
                                </button>
                            @endif

                            @if($ordre->statut === 'termine' && !$ordre->taux_satisfaction)
                                <button class="btn btn-primary btn-block" onclick="showSatisfactionForm('{{ $ordre->id_ordres_travail }}')">
                                    ⭐ Donner mon avis
                                </button>
                            @endif

                            @if($ordre->statut !== 'termine' && $ordre->statut !== 'annule')
                                <button class="btn btn-danger btn-block" onclick="confirmCancel('{{ $ordre->id_ordres_travail }}')">
                                    ❌ Annuler l'OT
                                </button>
                            @endif

                            @if($ordre->statut === 'termine')
                                @adminroute('admin.hebergement.maintenance.index')
<button class="btn btn-secondary btn-block" onclick="window.location.href='{{ route('admin.hebergement.maintenance.index') }}'">
                                    ↩️ Retour à la liste
                                </button>
@endadminroute

                            @endif
                        </div>
                    </div>
                </div>

                <!-- Commentaires -->
                @if($ordre->commentaire_satisfaction)
                    <div class="panel">
                        <div class="panel-header">
                            <h2>💬 Commentaire du résident</h2>
                        </div>
                        <div class="panel-body">
                            <div class="comment-box">
                                <p>{{ $ordre->commentaire_satisfaction }}</p>
                                @if($ordre->taux_satisfaction)
                                    <div class="comment-rating">
                                        @for($i = 1; $i <= 5; $i++)
                                            <span class="star {{ $i <= $ordre->taux_satisfaction ? 'filled' : '' }}">★</span>
                                        @endfor
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODAL : ASSIGNATION D'UN MAINTENANCIER                       -->
    <!-- ============================================================ -->
    <div id="assignationModal" class="modal" style="display: none;">
        <div class="modal-overlay" onclick="closeAssignationModal()"></div>
        <div class="modal-content" style="max-width: 550px;">
            <div class="modal-header">
                <h3>👨‍🔧 Assigner un maintenancier</h3>
                <button class="modal-close" onclick="closeAssignationModal()">×</button>
            </div>
            @adminroute('admin.hebergement.maintenance.assigner')
            <form id="assignationForm" action="{{ route('admin.hebergement.maintenance.assigner',['id' =>$ordre->id_ordres_travail]) }}" method="post">
                @csrf
                <input type="hidden" name="id_ordres_travail" id="assignation_ot_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Maintenancier <span class="required">*</span></label>
                        <select class="form-control" name="assigne_a" id="maintenancier_select" required>
                            <option value="">Sélectionnez un maintenancier</option>
                            @foreach($maintenanciers as $maintenancier)
                                <option value="{{ $maintenancier->id_maintenancier }}"
                                        data-specialite="{{ $maintenancier->specialite }}"
                                        data-email="{{ $maintenancier->email }}"
                                        data-tel="{{ $maintenancier->tel }}">
                                    {{ $maintenancier->name }} - {{ $maintenancier->specialite }}
                                    @if($maintenancier->cite)
                                        ({{ $maintenancier->cite->name }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <div class="form-hint" id="maintenancier_info"></div>
                    </div>



                        <div class="form-row-modal">
                            <div class="form-group">
                                <label class="form-label">📅 Date de début</label>
                                <input type="datetime-local" class="form-control" name="date_debut_intervention" id="date_debut_intervention">
                            </div>
                            <div class="form-group">
                                <label class="form-label">📅 Date de fin prévue</label>
                                <input type="datetime-local" class="form-control" name="date_fin_intervention" id="date_fin_intervention">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">📝 Instructions</label>
                            <textarea class="form-control" name="instructions" rows="3" placeholder="Instructions spécifiques pour le maintenancier..."></textarea>
                        </div>

                        <div class="form-group" style="margin-top:12px;">
                            <label style="display:flex; align-items:center; gap:8px; font-size:var(--app-font-min, 15px); cursor:pointer;">
                                <input type="checkbox" style="width:18px; height:18px; accent-color:var(--primary);" name="notification_envoyee" value="1" checked>
                                <span>📧 Notifier le maintenancier par email</span>
                            </label>
                        </div>
                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAssignationModal()">Annuler</button>
                    <button type="submit" class="btn btn-primary">👨‍🔧 Assigner</button>
                </div>
            </form>
            @endadminroute
        </div>
    </div>

    <!-- MODAL: Ajouter une intervention -->
    <div id="interventionModal" class="modal" style="display: none;">
        <div class="modal-overlay" onclick="closeInterventionModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>📝 Ajouter une intervention</h3>
                <button class="modal-close" onclick="closeInterventionModal()">×</button>
            </div>
            @adminroute('admin.hebergement.maintenance.intervention.store')
            <form id="interventionForm" action="{{ route('admin.hebergement.maintenance.intervention.store') }}" method="post">
                @csrf
                <input type="hidden" value="{{$ordre->id_ordres_travail}}" name="id_ordres_travail" id="intervention_ot_id">

                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Date de l'intervention</label>
                        <input type="datetime-local" class="form-control" name="date_intervention" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" required placeholder="Décrivez l'intervention réalisée..."></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Résultat</label>
                        <select class="form-control" name="resultat" required>
                            <option value="resolu">Résolu</option>
                            <option value="partiellement_resolu">Partiellement résolu</option>
                            <option value="non_resolu">Non résolu</option>
                            <option value="necessite_suivi">Nécessite un suivi</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Durée (minutes)</label>
                        <input type="number" class="form-control" name="duree" placeholder="Durée en minutes">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeInterventionModal()">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer l'intervention</button>
                </div>
            </form>
            @endadminroute
        </div>
    </div>

    <!-- MODAL: Satisfaction -->
    <div id="satisfactionModal" class="modal" style="display: none;">
        <div class="modal-overlay" onclick="closeSatisfactionModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>⭐ Donner votre avis</h3>
                <button class="modal-close" onclick="closeSatisfactionModal()">×</button>
            </div>
            @adminroute('admin.hebergement.maintenance.satisfaction')
            <form id="satisfactionForm" action="{{ route('admin.hebergement.maintenance.satisfaction') }}" method="post">
                @csrf
                <input type="hidden" name="ot_id" id="satisfaction_ot_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Note</label>
                        <div class="rating-stars">
                            <span class="star-rating" data-value="1">★</span>
                            <span class="star-rating" data-value="2">★</span>
                            <span class="star-rating" data-value="3">★</span>
                            <span class="star-rating" data-value="4">★</span>
                            <span class="star-rating" data-value="5">★</span>
                        </div>
                        <input type="hidden" name="taux_satisfaction" id="satisfaction_rating" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Commentaire</label>
                        <textarea class="form-control" name="commentaire_satisfaction" rows="3" placeholder="Votre avis sur l'intervention..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeSatisfactionModal()">Annuler</button>
                    <button type="submit" class="btn btn-primary">Envoyer mon avis</button>
                </div>
            </form>
            @endadminroute
        </div>
    </div> 


    <!-- MODAL: Démarrer une intervention -->
    <div id="demarrerInterventionModal" class="modal" style="display: none;">
        <div class="modal-overlay" onclick="closeDemarrerInterventionModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>▶️ Démarrer l'intervention</h3>
                <button class="modal-close" onclick="closeDemarrerInterventionModal()">×</button>
            </div>
            <form id="demarrerInterventionForm" action="#" method="post">
                @csrf
                <input type="hidden" name="ot_id" id="demarrer_ot_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">📅 Date de début <span class="required">*</span></label>
                        <input type="datetime-local" class="form-control" name="date_debut_intervention" id="date_debut_intervention" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">📝 Description de l'intervention <span class="required">*</span></label>
                        <textarea class="form-control" name="description" rows="3" required placeholder="Décrivez l'intervention qui va être réalisée..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">🔧 Type d'intervention</label>
                        <select class="form-control" name="type_intervention">
                            <option value="preventive">Préventive</option>
                            <option value="curative" selected>Curative</option>
                            <option value="urgente">Urgente</option>
                            <option value="amelioration">Amélioration</option>
                        </select>
                    </div>

                    <div class="form-row-modal">
                        <div class="form-group">
                            <label class="form-label">⏱️ Durée estimée (minutes)</label>
                            <input type="number" class="form-control" name="duree_estimee" placeholder="Ex: 30" min="1">
                        </div>
                        <div class="form-group">
                            <label class="form-label">👨‍🔧 Technicien</label>
                            <input type="text" class="form-control" name="technicien_nom" placeholder="Nom du technicien" value="{{ $ordre->assigneA?->name ?? '' }}" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">📋 Matériel utilisé</label>
                        <input type="text" class="form-control" name="materiel_utilise" placeholder="Liste du matériel utilisé">
                    </div>

                    <div class="form-group">
                        <label class="form-label">📝 Remarques</label>
                        <textarea class="form-control" name="remarques" rows="2" placeholder="Remarques particulières..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeDemarrerInterventionModal()">Annuler</button>
                    <button type="submit" class="btn btn-success">▶️ Démarrer l'intervention</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('styles')
    <style>
        /* ============================================
           STYLES - DETAIL MAINTENANCE
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

        .ot-detail-container {
            max-width: 1440px;
            margin: 0 auto;
            padding: 0 4px;
        }

        /* --- EN-TÊTE --- */
        .ot-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 16px;
            padding: 24px 28px;
            background: #fff;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 24px;
        }

        .ot-header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .ot-number .ot-label {
            display: block;
            font-size: var(--app-font-min, 15px);
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .ot-number h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--text);
            margin: 0;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 16px;
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

        .ot-header-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 12px;
        }

        .ot-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
            font-size: var(--app-font-min, 15px);
        }

        .meta-label {
            color: var(--muted);
            font-weight: 500;
        }

        .meta-value {
            font-weight: 600;
            color: var(--text);
        }

        .ot-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 18px;
            border: none;
            border-radius: 10px;
            font-weight: 500;
            font-size: var(--app-font-min, 15px);
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        .btn-edit {
            background: var(--primary);
            color: #fff;
        }

        .btn-edit:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 122, 168, 0.3);
        }

        .btn-print {
            background: var(--bg-light);
            color: var(--text);
            border: 1px solid var(--border);
        }

        .btn-print:hover {
            background: var(--border);
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-success {
            background: var(--success);
            color: #fff;
        }

        .btn-success:hover {
            background: #1f7a5c;
        }

        .btn-warning {
            background: var(--warning);
            color: #fff;
        }

        .btn-warning:hover {
            background: #c07a2e;
        }

        .btn-danger {
            background: var(--danger);
            color: #fff;
        }

        .btn-danger:hover {
            background: #9a3333;
        }

        .btn-secondary {
            background: var(--muted);
            color: #fff;
        }

        .btn-secondary:hover {
            background: #4a6a8a;
        }

        .btn-info {
            background: var(--info);
            color: #fff;
        }

        .btn-info:hover {
            background: #4a6a8a;
        }

        .btn-sm {
            padding: 6px 14px;
            font-size: var(--app-font-min, 15px);
        }

        .btn-block {
            width: 100%;
            justify-content: center;
            margin-bottom: 8px;
        }

        .btn-block:last-child {
            margin-bottom: 0;
        }

        /* --- GRILLE --- */
        .ot-grid {
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

        /* --- DESCRIPTION --- */
        .ot-description {
            font-size: var(--app-font-min, 15px);
            line-height: 1.6;
            color: var(--text);
            margin: 0;
        }

        .ot-cause, .ot-solution {
            margin-top: 12px;
            padding: 12px 16px;
            border-radius: 10px;
        }

        .ot-cause {
            background: #fcead8;
        }

        .ot-solution {
            background: #ddf0e8;
        }

        .cause-label, .solution-label {
            display: block;
            font-size: var(--app-font-min, 15px);
            font-weight: 600;
            margin-bottom: 4px;
        }

        .cause-label {
            color: #a8621a;
        }

        .solution-label {
            color: var(--success);
        }

        .ot-cause p, .ot-solution p {
            margin: 0;
            font-size: var(--app-font-min, 15px);
        }

        /* --- INFO GRID --- */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            padding: 8px 12px;
            background: var(--bg-light);
            border-radius: 8px;
        }

        .info-label {
            font-size: var(--app-font-min, 15px);
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
            color: var(--text);
            margin-top: 2px;
        }

        .priority-critique { color: var(--danger); font-weight: 700; }
        .priority-elevee { color: var(--warning); font-weight: 700; }
        .priority-moyenne { color: var(--info); }
        .priority-faible { color: var(--success); }

        /* --- LOCALISATION --- */
        .location-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .location-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            background: var(--bg-light);
            border-radius: 8px;
        }

        .location-icon {
            font-size: 20px;
            flex-shrink: 0;
        }

        .location-label {
            display: block;
            font-size: var(--app-font-min, 15px);
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .location-value {
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
            color: var(--text);
        }

        /* --- ASSIGNATION --- */
        .assignation-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .assignation-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--primary);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 600;
            flex-shrink: 0;
        }

        .assignation-details {
            flex: 1;
        }

        .assignation-name {
            display: block;
            font-size: 15px;
            font-weight: 600;
            color: var(--text);
        }

        .assignation-role {
            display: block;
            font-size: var(--app-font-min, 15px);
            color: var(--muted);
        }

        .assignation-contact {
            display: block;
            font-size: var(--app-font-min, 15px);
            color: var(--muted);
        }

        .assignation-date {
            display: inline-block;
            font-size: var(--app-font-min, 15px);
            color: var(--muted);
            margin-top: 2px;
        }

        .assignation-empty {
            text-align: center;
            padding: 20px;
            color: var(--muted);
        }

        .empty-icon {
            font-size: 40px;
            display: block;
            margin-bottom: 8px;
        }

        /* --- TIMELINE --- */
        .timeline {
            position: relative;
            padding-left: 28px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--border);
        }

        .tl-item {
            position: relative;
            padding-bottom: 20px;
        }

        .tl-item:last-child {
            padding-bottom: 0;
        }

        .tl-dot {
            position: absolute;
            left: -20px;
            top: 4px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid var(--border);
            background: var(--surface);
        }

        .tl-dot.created { border-color: var(--primary); background: var(--primary); }
        .tl-dot.assigned { border-color: var(--warning); background: var(--warning); }
        .tl-dot.intervention { border-color: var(--info); background: var(--info); }
        .tl-dot.resolved { border-color: var(--success); background: var(--success); }
        .tl-dot.cancelled { border-color: var(--danger); background: var(--danger); }

        .tl-content {
            flex: 1;
        }

        .tl-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .tl-title {
            font-size: var(--app-font-min, 15px);
            font-weight: 600;
            color: var(--text);
        }

        .tl-date {
            font-size: var(--app-font-min, 15px);
            color: var(--muted);
        }

        .tl-desc {
            font-size: var(--app-font-min, 15px);
            color: var(--muted);
            margin-top: 2px;
        }

        .tl-resultat {
            display: inline-block;
            padding: 1px 10px;
            border-radius: 30px;
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
            margin-left: 8px;
        }

        .tl-resultat.resultat-resolu {
            background: #ddf0e8;
            color: var(--success);
        }

        .tl-resultat.resultat-partiellement_resolu {
            background: #fcead8;
            color: var(--warning);
        }

        .tl-resultat.resultat-non_resolu {
            background: #fce1e1;
            color: var(--danger);
        }

        .tl-resultat.resultat-necessite_suivi {
            background: #dce8f5;
            color: var(--info);
        }

        .tl-technicien {
            font-size: var(--app-font-min, 15px);
            color: var(--muted);
            margin-top: 2px;
        }

        .tl-satisfaction {
            font-size: var(--app-font-min, 15px);
            color: var(--warning);
            margin-top: 4px;
        }

        /* --- ACTION BUTTONS --- */
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        /* --- COMMENTAIRE --- */
        .comment-box {
            padding: 16px;
            background: var(--bg-light);
            border-radius: 10px;
        }

        .comment-box p {
            margin: 0 0 8px;
            font-size: var(--app-font-min, 15px);
            color: var(--text);
        }

        .comment-rating {
            display: flex;
            gap: 4px;
        }

        .star {
            font-size: 20px;
            color: var(--border);
        }

        .star.filled {
            color: #f0c040;
        }

        /* --- MODAL --- */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            cursor: pointer;
            backdrop-filter: blur(4px);
        }

        .modal-content {
            position: relative;
            background: #fff;
            border-radius: var(--radius);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 90%;
            z-index: 1001;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: var(--text);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: var(--muted);
            transition: 0.2s;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            background: var(--bg-light);
            color: var(--text);
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            padding: 16px 20px;
            border-top: 1px solid var(--border);
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

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
        }

        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(30, 122, 168, 0.1);
        }

        .form-hint {
            font-size: var(--app-font-min, 15px);
            color: var(--muted);
            margin-top: 4px;
        }

        .form-row-modal {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .rating-stars {
            display: flex;
            gap: 8px;
            font-size: 32px;
            cursor: pointer;
        }

        .star-rating {
            color: var(--border);
            transition: 0.2s;
            cursor: pointer;
        }

        .star-rating:hover,
        .star-rating.active {
            color: #f0c040;
        }

        /* --- RESPONSIVE --- */
        @media (max-width: 1024px) {
            .ot-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .ot-header {
                flex-direction: column;
                align-items: stretch;
            }

            .ot-header-left {
                flex-wrap: wrap;
            }

            .ot-header-right {
                align-items: flex-start;
            }

            .ot-meta {
                flex-direction: column;
                gap: 8px;
            }

            .ot-actions {
                flex-wrap: wrap;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .location-grid {
                grid-template-columns: 1fr;
            }

            .ot-number h1 {
                font-size: 22px;
            }

            .form-row-modal {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }

        @media (max-width: 480px) {
            .modal-content {
                width: 95%;
                margin: 10px;
            }

            .rating-stars {
                font-size: 28px;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>


        // ============================================
        // MODAL ASSIGNATION
        // ============================================
        function openAssignationModal(otId) {
            document.getElementById('assignation_ot_id').value = otId;
            document.getElementById('assignationModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function openInterventionModal(otId) {
            document.getElementById('intervention_ot_id').value = otId;
            document.getElementById('demarrerInterventionModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeAssignationModal() {
            document.getElementById('assignationModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        function closeDemarrerInterventionModal() {
            document.getElementById('demarrerInterventionModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // ============================================
        // AFFICHAGE DES INFOS DU MAINTENANCIER
        // ============================================
        document.addEventListener('DOMContentLoaded', function() {
            const select = document.getElementById('maintenancier_select');
            const infoDiv = document.getElementById('maintenancier_info');

            if (select && infoDiv) {
                select.addEventListener('change', function() {
                    const selected = this.options[this.selectedIndex];
                    if (this.value) {
                        const specialite = selected.dataset.specialite || '';
                        const email = selected.dataset.email || '';
                        const tel = selected.dataset.tel || '';

                        let info = '';
                        if (specialite) info += `🔧 ${specialite}`;
                        if (email) info += `${info ? ' · ' : ''}📧 ${email}`;
                        if (tel) info += `${info ? ' · ' : ''}📱 ${tel}`;

                        infoDiv.textContent = info;
                        infoDiv.style.display = 'block';
                    } else {
                        infoDiv.textContent = '';
                        infoDiv.style.display = 'none';
                    }
                });
            }
        });

        // ============================================
        // MODAL INTERVENTION
        // ============================================
        function showInterventionForm(otId) {
            document.getElementById('intervention_ot_id').value = otId;
            document.getElementById('interventionModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeInterventionModal() {
            document.getElementById('interventionModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // ============================================
        // MODAL SATISFACTION
        // ============================================
        function showSatisfactionForm(otId) {
            document.getElementById('satisfaction_ot_id').value = otId;
            document.getElementById('satisfactionModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeSatisfactionModal() {
            document.getElementById('satisfactionModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // ============================================
        // GESTION DES ÉTOILES DE SATISFACTION
        // ============================================
        document.querySelectorAll('.star-rating').forEach(star => {
            star.addEventListener('click', function() {
                const value = parseInt(this.dataset.value);
                document.getElementById('satisfaction_rating').value = value;

                document.querySelectorAll('.star-rating').forEach(s => {
                    s.classList.toggle('active', parseInt(s.dataset.value) <= value);
                });
            });

            star.addEventListener('mouseenter', function() {
                const value = parseInt(this.dataset.value);
                document.querySelectorAll('.star-rating').forEach(s => {
                    s.classList.toggle('active', parseInt(s.dataset.value) <= value);
                });
            });

            star.addEventListener('mouseleave', function() {
                const selected = parseInt(document.getElementById('satisfaction_rating').value) || 0;
                document.querySelectorAll('.star-rating').forEach(s => {
                    s.classList.toggle('active', parseInt(s.dataset.value) <= selected);
                });
            });
        });

        // ============================================
        // FERMER MODALES AVEC ÉCHAP
        // ============================================
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAssignationModal();
                closeInterventionModal();
                closeSatisfactionModal();
            }
        });

        // ============================================
        // RETIRER L'ASSIGNATION
        // ============================================
        function removeAssignation(otId) {
            if (!confirm('Êtes-vous sûr de vouloir retirer l\'assignation de ce maintenancier ?')) {
                return;
            }

            fetch(`{{ url('admin/gestion-hebergement/maintenance') }}/${otId}/retirer-assignation`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Erreur lors du retrait de l\'assignation');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue');
                });
        }

        // ============================================
        // CONFIRMER ANNULATION
        // ============================================
        function confirmCancel(otId) {
            if (confirm('Êtes-vous sûr de vouloir annuler cet ordre de travail ?')) {
                fetch(`{{ url('admin/gestion-hebergement/maintenance') }}/${otId}/annuler`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert('Erreur lors de l\'annulation');
                        }
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        alert('Une erreur est survenue');
                    });
            }
        }

        // ============================================
        // METTRE À JOUR LE STATUT
        // ============================================
        function updateStatut(otId, statut) {
            const messages = {
                'en_cours': 'Démarrer l\'intervention',
                'en_attente_pieces': 'Mettre en attente de pièces',
                'termine': 'Marquer comme terminé'
            };

            if (!confirm(`Êtes-vous sûr de vouloir ${messages[statut] || 'changer le statut'} ?`)) {
                return;
            }

            fetch(`{{ url('admin/gestion-hebergement/maintenance') }}/${otId}/statut`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ statut: statut })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Erreur lors du changement de statut');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue');
                });
        }

        console.log('✅ Page détail OT initialisée avec succès !');
    </script>
@endpush