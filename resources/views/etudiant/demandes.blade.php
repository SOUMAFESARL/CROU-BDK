

{{--<div id="page-demandes" class="content">--}}

{{--    <div class="panel">--}}
{{--        <div class="panel-header">--}}
{{--            <h2>Dernière demande</h2>--}}
{{--            <span class="badge pending">1 en attente</span>--}}
{{--        </div>--}}
{{--        <div class="panel-body">--}}



{{--            @if($data['derniere_demande'] && ($data['statut_demande'] != 'refuser' || $data['statut_demande'] != 'Acceptée') )--}}
{{--                <div class="demande-card">--}}
{{--                    <div class="demande-row">--}}
{{--                        <div>--}}
{{--                            <div class="demande-title">--}}
{{--                                Année Académique : <strong>{{ $data['campagne']->annee_campagne ?? 'N/A' }}</strong>--}}
{{--                            </div>--}}
{{--                            <div class="demande-detail">--}}
{{--                                Numéro de demande : <span>{{ $data['derniere_demande']['numero_demande'] ?? 'N/A' }}</span>--}}
{{--                            </div>--}}
{{--                            <div class="demande-detail">--}}
{{--                                Soumise le <span>{{ formatDateFr($data['derniere_demande']['date_demande'] ?? '', true) }}</span>--}}
{{--                            </div>--}}
{{--                            <div class="demande-detail">--}}
{{--                                Délai de validation : <span>{{ formatDateFr($data['campagne']->date_echeance_demande->format('Y-m-d'), true) ?? 'N/A' }}</span>--}}
{{--                            </div>--}}
{{--                            @if(isset($data['derniere_demande']['moyenne']))--}}
{{--                                <div class="demande-detail">--}}
{{--                                    Moyenne : <span>{{ $data['derniere_demande']['moyenne'] }}/20</span>--}}
{{--                                </div>--}}
{{--                            @endif--}}
{{--                        </div>--}}
{{--                        <span class="badge pending">En attente</span>--}}
{{--                    </div>--}}

{{--                    --}}{{-- BARRE DE PROGRESSION: Documents --}}
{{--                    @php--}}
{{--                        $totalDocs = count($data['derniere_demande']['documents'] ?? []);--}}
{{--                        $validDocs = collect($data['derniere_demande']['documents'] ?? [])->where('statut', 'valide')->count();--}}
{{--                        $progressPercent = $totalDocs > 0 ? round(($validDocs / $totalDocs) * 100) : 0;--}}
{{--                    @endphp--}}

{{--                    @if($totalDocs > 0)--}}
{{--                        <div class="demande-progress" style="margin: 20px 0;">--}}
{{--                            <div style="font-size: var(--app-font-min, 15px); margin-bottom: 8px; display: flex; justify-content: space-between;">--}}
{{--                                <span>Documents validés</span>--}}
{{--                                <span style="font-weight: 600;">{{ $validDocs }}/{{ $totalDocs }} ({{ $progressPercent }}%)</span>--}}
{{--                            </div>--}}
{{--                            <div class="progress-bar" style="height: 8px; background: #e0e0e0; border-radius: 4px; overflow: hidden;">--}}
{{--                                <div style="height: 100%; width: {{ $progressPercent }}%; background: #4caf50; transition: width 0.3s;"></div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    @endif--}}

{{--                    --}}{{-- BOUTONS D'ACTION --}}


{{--                    --}}{{-- INFOS SUPPLÉMENTAIRES --}}
{{--                    <div class="demande-info" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border);">--}}
{{--                        <p style="font-size: var(--app-font-min, 15px); color: var(--muted); margin-bottom: 0;">--}}
{{--                            <strong>ℹ️ Important:</strong> Vous devez accepter ou refuser cette demande avant le--}}
{{--                            <strong>{{ formatDateFr($data['campagne']->date_echeance_demande->format('Y-m-d'), false) }}</strong>.--}}
{{--                            Passé ce délai, votre demande sera traitée automatiquement.--}}
{{--                        </p>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            @endif--}}



{{--        </div>--}}
{{--    </div>--}}

{{--@if($data['demandes']->count()> 1)--}}
{{--    <div class="panel">--}}
{{--        <div class="panel-header">--}}
{{--            <h2>Autres Demandes d'hébergement</h2>--}}
{{--            <span class="badge pending">1 en attente</span>--}}
{{--        </div>--}}
{{--        <div class="panel-body">--}}




{{--            <div class="demande-card">--}}
{{--                <div class="demande-row">--}}
{{--                    <div>--}}
{{--                        <div class="demande-title">Demande initiale d'hébergement</div>--}}
{{--                        <div class="demande-detail">Soumise le <span>15 janvier 2025</span></div>--}}
{{--                    </div>--}}
{{--                    <span class="badge success">Traitée</span>--}}
{{--                </div>--}}
{{--                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 8px;">--}}
{{--                    <div class="demande-detail">Préférence : <span>Simple</span></div>--}}
{{--                    <div class="demande-detail">Durée : <span>1 an</span></div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}
{{--    @endif--}}
{{--</div>--}}



<div id="page-demandes" class="content">

    <style>
        /* ===== Variables ===== */
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #dbeafe;
            --success: #16a34a;
            --success-light: #dcfce7;
            --warning: #f59e0b;
            --warning-light: #fef3c7;
            --danger: #dc2626;
            --danger-light: #fee2e2;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-500: #64748b;
            --gray-700: #334155;
            --gray-900: #0f172a;
            --shadow: 0 1px 3px rgba(0,0,0,0.08), 0 4px 12px rgba(0,0,0,0.05);
            --shadow-hover: 0 8px 30px rgba(0,0,0,0.10);
            --radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Styles limités à la section : un `body { padding }` décollait la barre du haut de la coque. */
        #page-demandes {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--gray-900);
            line-height: 1.6;
        }

        #page-demandes {
            max-width: 1100px;
            margin: 0 auto;
        }

        /* ===== Panel ===== */
        .panel {
            background: #ffffff;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 28px;
            overflow: hidden;
            transition: var(--transition);
            border: 1px solid var(--gray-200);
        }

        .panel:hover {
            box-shadow: var(--shadow-hover);
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 28px;
            border-bottom: 1px solid var(--gray-200);
            background: var(--gray-50);
        }

        .panel-header h2 {
            font-size: 18px;
            font-weight: 600;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .panel-header h2 i {
            color: var(--primary);
        }

        .panel-body {
            padding: 24px 28px 28px;
        }

        /* ===== Badges ===== */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: var(--app-font-min, 15px);
            font-weight: 600;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .badge i {
            font-size: var(--app-font-min, 15px);
        }

        .badge.pending {
            background: var(--warning-light);
            color: #92400e;
        }

        .badge.success {
            background: var(--success-light);
            color: #14532d;
        }

        .badge.danger {
            background: var(--danger-light);
            color: #991b1b;
        }

        .badge.info {
            background: var(--primary-light);
            color: var(--primary-dark);
        }

        .badge.secondary {
            background: var(--gray-200);
            color: var(--gray-700);
        }

        /* ===== Demande Card ===== */
        .demande-card {
            background: #ffffff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 22px 24px;
            transition: var(--transition);
        }

        .demande-card:not(:last-child) {
            margin-bottom: 18px;
        }

        .demande-card:hover {
            border-color: var(--primary);
            background: #fafcff;
        }

        .demande-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            flex-wrap: wrap;
        }

        .demande-title {
            font-size: 16px;
            font-weight: 500;
            color: var(--gray-900);
            margin-bottom: 6px;
        }

        .demande-title strong {
            color: var(--primary-dark);
        }

        .demande-detail {
            font-size: var(--app-font-min, 15px);
            color: var(--gray-700);
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 2px;
        }

        .demande-detail span {
            font-weight: 500;
            color: var(--gray-900);
        }

        .demande-detail i {
            color: var(--gray-500);
            width: 18px;
            font-size: 15px;
        }

        .demande-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 8px 20px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px dashed var(--gray-200);
        }

        /* ===== Progress Bar ===== */
        .demande-progress {
            margin: 18px 0 14px;
        }

        .demande-progress .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: 6px;
        }

        .progress-track {
            height: 8px;
            background: var(--gray-200);
            border-radius: 6px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            border-radius: 6px;
            transition: width 0.6s ease;
            background: linear-gradient(90deg, var(--success), #22c55e);
        }

        .progress-fill.warning {
            background: linear-gradient(90deg, var(--warning), #fbbf24);
        }

        .progress-fill.danger {
            background: linear-gradient(90deg, var(--danger), #ef4444);
        }

        /* ===== Action Buttons ===== */
        .demande-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--gray-200);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 20px;
            border: none;
            border-radius: 8px;
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            background: var(--gray-100);
            color: var(--gray-700);
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.10);
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }
        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }
        .btn-success:hover {
            background: #15803d;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }
        .btn-danger:hover {
            background: #b91c1c;
        }

        .btn-outline {
            background: transparent;
            border: 1.5px solid var(--gray-300);
            color: var(--gray-700);
        }
        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: var(--primary-light);
        }

        /* ===== Info Box ===== */
        .demande-info {
            margin-top: 16px;
            padding: 14px 18px;
            background: var(--primary-light);
            border-radius: 8px;
            border-left: 4px solid var(--primary);
            font-size: var(--app-font-min, 15px);
            color: var(--gray-700);
        }

        .demande-info strong {
            color: var(--primary-dark);
        }

        /* ===== Empty State ===== */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray-500);
        }

        .empty-state i {
            font-size: 48px;
            color: var(--gray-300);
            margin-bottom: 16px;
            display: block;
        }

        /* ===== Sortie Chambre ===== */
        .sortie-card {
            background: #fff8f5;
            border: 1px solid #fde5d4;
            border-radius: var(--radius);
            padding: 20px 24px;
            margin-top: 12px;
        }

        .sortie-card .demande-row {
            align-items: center;
        }

        .sortie-card .badge {
            background: #fde5d4;
            color: #9a3412;
        }

        /* ===== Formulaire ===== */
        .sortie-form {
            background: #ffffff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 24px;
            margin-top: 16px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: var(--app-font-min, 15px);
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: 6px;
        }

        .form-group label .required {
            color: var(--danger);
            margin-left: 2px;
        }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: var(--app-font-min, 15px);
            transition: var(--transition);
            background: #ffffff;
            color: var(--gray-900);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-control:disabled {
            background: var(--gray-100);
            cursor: not-allowed;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
        }

        textarea.form-control {
            min-height: 80px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-hint {
            font-size: var(--app-font-min, 15px);
            color: var(--gray-500);
            margin-top: 4px;
        }

        /* ===== Document Upload ===== */
        .upload-area {
            border: 2px dashed var(--gray-300);
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            transition: var(--transition);
            cursor: pointer;
        }

        .upload-area:hover {
            border-color: var(--primary);
            background: var(--primary-light);
        }

        .upload-area i {
            font-size: 32px;
            color: var(--gray-500);
            display: block;
            margin-bottom: 8px;
        }

        .upload-area p {
            font-size: var(--app-font-min, 15px);
            color: var(--gray-700);
            margin: 0;
        }

        .upload-area .upload-hint {
            font-size: var(--app-font-min, 15px);
            color: var(--gray-500);
        }

        /* ===== Status Timeline ===== */
        .timeline {
            position: relative;
            padding-left: 24px;
            margin: 12px 0;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 6px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--gray-200);
        }

        .timeline-item {
            position: relative;
            padding-bottom: 16px;
            padding-left: 16px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -22px;
            top: 4px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--gray-300);
            border: 2px solid white;
        }

        .timeline-item.active::before {
            background: var(--primary);
        }

        .timeline-item.done::before {
            background: var(--success);
        }

        .timeline-item .timeline-title {
            font-weight: 500;
            font-size: var(--app-font-min, 15px);
            color: var(--gray-900);
        }

        .timeline-item .timeline-date {
            font-size: var(--app-font-min, 15px);
            color: var(--gray-500);
        }

        /* ===== Responsive ===== */
        @media (max-width: 640px) {
            .panel-header { flex-direction: column; align-items: flex-start; gap: 8px; }
            .panel-body { padding: 16px; }
            .demande-row { flex-direction: column; align-items: stretch; }
            .demande-actions { flex-direction: column; }
            .btn { justify-content: center; }
            .form-row { grid-template-columns: 1fr; }
        }

        /* ===== Animations ===== */
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .demande-card, .sortie-card, .sortie-form {
            animation: slideDown 0.3s ease forwards;
        }

        /* ===== Toggle Switch ===== */
        .toggle-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: var(--app-font-min, 15px);
            color: var(--gray-700);
        }

        .toggle {
            position: relative;
            width: 44px;
            height: 24px;
            background: var(--gray-300);
            border-radius: 12px;
            cursor: pointer;
            transition: var(--transition);
            flex-shrink: 0;
        }

        .toggle.active {
            background: var(--success);
        }

        .toggle::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
            transition: var(--transition);
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }

        .toggle.active::after {
            left: 22px;
        }

        /* ===== Modal / Accordion ===== */
        .accordion-trigger {
            cursor: pointer;
            user-select: none;
        }

        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease, opacity 0.3s ease;
            opacity: 0;
        }

        .accordion-content.open {
            max-height: 1500px;
            opacity: 1;
        }

        .chevron {
            transition: transform 0.3s ease;
            display: inline-block;
        }

        .chevron.rotated {
            transform: rotate(180deg);
        }
    </style>

    @php
        $latestRequest = $data['derniere_demande'] ?? null;
        $requests = collect($data['demandes'] ?? [])->sortByDesc(fn ($request) => $request->created_at ?? $request->date_demande);
        $otherRequests = $latestRequest
            ? $requests->where('id_demande', '!=', $latestRequest->id_demande)
            : collect();

        $statusPresentation = function ($status) {
            $normalized = \Illuminate\Support\Str::of((string) $status)
                ->ascii()->lower()->replace([' ', '-'], '_')->toString();

            if (isset(\App\Http\Services\DossierWorkflow::LABELS[$normalized])) {
                return [\App\Http\Services\DossierWorkflow::LABELS[$normalized],
                    in_array($normalized,['attribue_definitif','accepte','eligible'],true) ? 'success' : 'info', ''];
            }
            return match ($normalized) {
                'acceptee', 'accepte', 'validee', 'valide' => ['Acceptée', 'success', '✅'],
                'refusee', 'refuse', 'rejetee', 'rejete' => ['Refusée', 'danger', '❌'],
                'annulee', 'annule' => ['Annulée', 'secondary', '⊘'],
                'en_cours', 'verification', 'en_verification' => ['En cours', 'info', '🔄'],
                default => ['En attente', 'pending', '⏳'],
            };
        };

        $pendingCount = $requests->filter(function ($request) use ($statusPresentation) {
            return $statusPresentation($request->etat_dossier ?? null)[0] === 'En attente';
        })->count();
    @endphp

    <!-- ============================================================ -->
    <!-- PANEL : DERNIÈRE DEMANDE                                     -->
    <!-- ============================================================ -->
    <div class="panel">
        <div class="panel-header">
            <h2>
                <i>📋</i> Dernière demande
                @if($pendingCount > 0)
                    <span class="badge pending">⏳ {{ $pendingCount }} en attente</span>
                @endif
            </h2>
            <span style="font-size:var(--app-font-min, 15px); color:var(--gray-500);">
                <i>🕒</i> Mise à jour : {{ $latestRequest?->updated_at?->diffForHumans() ?? 'aucune' }}
            </span>
        </div>
        <div class="panel-body">

            @if($latestRequest)
                @include('dossier-cycle', ['dossier'=>$latestRequest, 'mode'=>'student'])
                @php
                    [$latestStatusLabel, $latestStatusClass, $latestStatusIcon] = $statusPresentation($latestRequest->etat_dossier);
                    $requestCampaign = $latestRequest->campagne ?? $data['campagne'] ?? null;
                    $deadline = $requestCampaign?->date_echeance_demande;
                @endphp
                <div class="demande-card">
                    <div class="demande-row">
                        <div style="flex:1;">
                            <div class="demande-title">
                                🏫 Année Académique : <strong>{{ $requestCampaign?->annee_campagne ?? 'Non renseignée' }}</strong>
                            </div>
                            <div class="demande-detail">
                                <i>📄</i> Numéro : <span>{{ $latestRequest->numero_demande ?? $latestRequest->id_demande }}</span>
                            </div>
                            <div class="demande-detail">
                                <i>📅</i> Soumise le <span>{{ formatDateFr($latestRequest->date_demande ?? $latestRequest->created_at, true) }}</span>
                            </div>
                            @if($deadline)
                                <div class="demande-detail">
                                    <i>⏰</i> Délai de validation : <span>{{ formatDateFr($deadline, true) }}</span>
                                </div>
                            @endif
                            @if($latestRequest->moyenne !== null)
                                <div class="demande-detail">
                                    <i>📊</i> Moyenne : <span>{{ $latestRequest->moyenne }}/20</span>
                                </div>
                            @endif
                        </div>
                        <span class="badge {{ $latestStatusClass }}">{{ $latestStatusIcon }} {{ $latestStatusLabel }}</span>
                    </div>

                    @php
                        $documents = collect($latestRequest->documents ?? []);
                        $totalDocs = $documents->count();
                        $validDocs = $documents->filter(fn ($document) => in_array(
                            \Illuminate\Support\Str::lower($document->statut ?? ''),
                            ['valide', 'validé', 'validee', 'validée']
                        ))->count();
                        $progressPercent = $totalDocs > 0 ? round(($validDocs / $totalDocs) * 100) : 0;
                        $progressClass = $progressPercent >= 80 ? '' : ($progressPercent >= 50 ? 'warning' : 'danger');
                    @endphp

                    @if($totalDocs > 0)
                        <div class="demande-progress">
                            <div class="progress-label">
                                <span><i>📎</i> Documents validés</span>
                                <span style="font-weight:600;">{{ $validDocs }}/{{ $totalDocs }} ({{ $progressPercent }}%)</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill {{ $progressClass }}" style="width:{{ $progressPercent }}%;"></div>
                            </div>
                        </div>
                    @endif

                    @if(false)
                        <div class="demande-actions">
                            <button type="button" class="btn btn-success" onclick="showAcceptModal('{{ $latestRequest->id_demande }}')">
                                <i>✅</i> Accepter
                            </button>
                            <button type="button" class="btn btn-danger" onclick="showRejectModal('{{ $latestRequest->id_demande }}')">
                                <i>❌</i> Refuser
                            </button>
                        </div>
                    @endif

                    @if(false)
                        <div class="demande-info">
                            <strong>ℹ️ Important :</strong> Vous devez accepter ou refuser cette demande avant le
                            <strong>{{ formatDateFr($deadline, false) }}</strong>.
                            Passé ce délai, votre demande sera traitée automatiquement.
                        </div>
                    @endif
                </div>
            @else
                <div class="empty-state">
                    <i>📭</i>
                    <h3>Aucune demande en cours</h3>
                    <p>Vous n'avez pas de demande d'hébergement en attente.</p>
                </div>
            @endif

        </div>
    </div>

    <!-- ============================================================ -->
    <!-- PANEL : AUTRES DEMANDES                                      -->
    <!-- ============================================================ -->
    @if($otherRequests->isNotEmpty())
        <div class="panel">
            <div class="panel-header">
                <h2>
                    <i>📂</i> Autres demandes
                    <span class="badge secondary">{{ $otherRequests->count() }} au total</span>
                </h2>
                <span class="badge info">📋 Archivées</span>
            </div>
            <div class="panel-body">
                @foreach($otherRequests as $request)
                    @php
                        [$statusLabel, $statusClass, $statusIcon] = $statusPresentation($request->etat_dossier);
                    @endphp
                    <div class="demande-card">
                        <div class="demande-row">
                            <div>
                                <div class="demande-title">🏠 Demande d'hébergement {{ $request->campagne?->annee_campagne ? '— '.$request->campagne->annee_campagne : '' }}</div>
                                <div class="demande-detail"><i>📅</i> Soumise le <span>{{ formatDateFr($request->date_demande ?? $request->created_at, true) }}</span></div>
                                <div class="demande-detail"><i>📄</i> Référence : <span>{{ $request->numero_demande ?? $request->id_demande }}</span></div>
                            </div>
                            <span class="badge {{ $statusClass }}">{{ $statusIcon }} {{ $statusLabel }}</span>
                        </div>
                        <div class="demande-meta">
                            @if($request->moyenne !== null)
                                <div class="demande-detail">📊 Moyenne : <span>{{ $request->moyenne }}/20</span></div>
                            @endif
                            @if($request->credit_valider !== null)
                                <div class="demande-detail">🎓 Crédits validés : <span>{{ $request->credit_valider }}</span></div>
                            @endif
                            <div class="demande-detail">📌 Statut : <span>{{ $statusLabel }}</span></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- ============================================================ -->
    <!-- PANEL : DEMANDE DE SORTIE DE CHAMBRE AVEC FORMULAIRE        -->
    <!-- ============================================================ -->
    @php
        $departureRequests = collect($result['departureRequests'] ?? []);
        $activeDeparture = $departureRequests->firstWhere('statut', 'en_attente');
        $departureHistory = $departureRequests->where('statut', '!=', 'en_attente');
        $currentAssignment = $data['etudiant']->assignationActive ?? $data['derniereAssignation'] ?? null;
        $currentRoom = $currentAssignment?->chambre;
        $departureMotifs = [
            'fin_etudes' => 'Fin des études',
            'demenagement' => 'Déménagement',
            'stage' => 'Stage',
            'raison_personnelle' => 'Raison personnelle',
            'autre' => 'Autre motif',
        ];
    @endphp
    <div class="panel" id="departure-request-section">
        <div class="panel-header">
            <h2>
                <i>🚪</i> Demande de sortie de chambre
                @if($activeDeparture)<span class="badge {{ $activeDeparture->decision === 'approuvee' ? 'success' : 'pending' }}">{{ $activeDeparture->decision === 'approuvee' ? '✅ Demande approuvée' : '⏳ 1 en attente' }}</span>@endif
            </h2>
            <span style="font-size:var(--app-font-min, 15px); color:var(--gray-500);">
                <i>🔄</i> Dernière mise à jour : {{ $departureRequests->first()?->updated_at?->diffForHumans() ?? 'aucune demande' }}
            </span>
        </div>
        <div class="panel-body">

            @if(session('success'))
                <div class="demande-info" style="border-left-color:var(--success);">✅ {{ session('success') }}</div>
            @endif
            @if($errors->has('departure'))
                <div class="demande-info" style="border-left-color:var(--danger);">❌ {{ $errors->first('departure') }}</div>
            @endif

            @if($activeDeparture)
            <div class="sortie-card">
                <div class="demande-row">
                    <div>
                        <div class="demande-title">
                            🏠 Sortie de la chambre <strong>#{{ $activeDeparture->chambre?->numero_chambre ?? 'N/D' }}</strong>
                            <span style="font-weight:400; font-size:var(--app-font-min, 15px); color:var(--gray-500); margin-left:8px;">
                                ({{ $activeDeparture->chambre?->cite?->name ?? $activeDeparture->chambre?->batiment?->name ?? 'Résidence non renseignée' }})
                            </span>
                        </div>
                        <div class="demande-detail">
                            <i>📅</i> Date de sortie souhaitée : <span>{{ formatDateFr($activeDeparture->date_sortie_prevue, false) }}</span>
                        </div>
                        <div class="demande-detail">
                            <i>📄</i> Référence : <span>{{ $activeDeparture->id_demande_sortie }}</span>
                        </div>
                        <div class="demande-detail">
                            <i>📋</i> Motif : <span>{{ $departureMotifs[$activeDeparture->motif] ?? $activeDeparture->motif }}</span>
                        </div>
                    </div>
                    <span class="badge {{ $activeDeparture->decision === 'approuvee' ? 'success' : 'pending' }}">{{ $activeDeparture->decision === 'approuvee' ? '✅ Approuvée' : '⏳ En attente' }}</span>
                </div>

                <!-- Timeline de suivi -->
                <div class="timeline">
                    <div class="timeline-item done">
                        <div class="timeline-title">📝 Demande soumise</div>
                        <div class="timeline-date">{{ formatDateFr($activeDeparture->date_demande, false) }}</div>
                    </div>
                    <div class="timeline-item {{ $activeDeparture->decision === 'approuvee' ? 'done' : 'active' }}">
                        <div class="timeline-title">{{ $activeDeparture->decision === 'approuvee' ? '✅ Demande approuvée' : '⏳ En attente de validation' }}</div>
                        <div class="timeline-date">{{ $activeDeparture->date_decision?->format('d/m/Y H:i') ?? 'En cours' }}</div>
                    </div>
                    <div class="timeline-item {{ $activeDeparture->decision === 'approuvee' ? 'active' : '' }}">
                        <div class="timeline-title">🚪 État des lieux et clôture</div>
                        <div class="timeline-date">{{ $activeDeparture->decision === 'approuvee' ? 'À réaliser avec le CROU' : 'En attente' }}</div>
                    </div>
                </div>

            </div>
            @endif

            <!-- ========================================================== -->
            <!-- FORMULAIRE DE DEMANDE DE SORTIE POUR L'ÉTUDIANT            -->
            <!-- ========================================================== -->
            @if(!$activeDeparture && $currentAssignment && $currentRoom)
            <div style="margin-top:24px; padding-top:20px; border-top:2px solid var(--gray-200);">
                <div class="accordion-trigger" onclick="toggleAccordion(this)" style="display:flex; justify-content:space-between; align-items:center; padding:12px 0;">
                    <h3 style="font-size:16px; font-weight:600; color:var(--gray-900); display:flex; align-items:center; gap:10px; margin:0;">
                        <i>✏️</i> Faire une nouvelle demande de sortie
                        <span class="badge info" style="font-size:var(--app-font-min, 15px);">Nouvelle</span>
                    </h3>
                    <span class="chevron" style="font-size:20px; color:var(--gray-500);">▼</span>
                </div>

                <div class="accordion-content open">
                    <div class="sortie-form">
                        <form action="{{ route('etudiants.departure-requests.store') }}" method="POST">
                            @csrf

                            <!-- Informations personnelles pré-remplies -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label>👤 Nom complet</label>
                                    <input type="text" class="form-control" value="{{ $data['etudiant']->name ?? 'N/D' }}" disabled>
                                </div>
                                <div class="form-group">
                                    <label>🎓 Numéro étudiant</label>
                                    <input type="text" class="form-control" value="{{ $data['etudiant']->matricule ?? 'N/D' }}" disabled>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>🏠 Chambre actuelle <span class="required">*</span></label>
                                    <input type="text" class="form-control" value="Chambre {{ $currentRoom->numero_chambre ?? 'N/D' }}" disabled>
                                </div>
                                <div class="form-group">
                                    <label>🏢 Résidence <span class="required">*</span></label>
                                    <input type="text" class="form-control" value="{{ $currentRoom->cite?->name ?? $currentRoom->batiment?->name ?? 'N/D' }}" disabled>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>📅 Date de sortie souhaitée <span class="required">*</span></label>
                                    <input type="date" name="date_sortie_prevue" class="form-control" required min="{{ today()->addDays(15)->toDateString() }}" value="{{ old('date_sortie_prevue', today()->addDays(30)->toDateString()) }}">
                                    @error('date_sortie_prevue')<div class="form-hint" style="color:var(--danger);">{{ $message }}</div>@enderror
                                    <div class="form-hint">La date de sortie doit être au minimum 15 jours après la demande</div>
                                </div>
                                <div class="form-group">
                                    <label>📋 Motif de la sortie <span class="required">*</span></label>
                                    <select name="motif" class="form-control" required>
                                        <option value="">Sélectionnez un motif</option>
                                        @foreach($departureMotifs as $value => $label)
                                            <option value="{{ $value }}" @selected(old('motif') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('motif')<div class="form-hint" style="color:var(--danger);">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="form-group">
                                <label>📝 Informations complémentaires</label>
                                <textarea name="observations" class="form-control" maxlength="2000" placeholder="Ajoutez toute information utile...">{{ old('observations') }}</textarea>
                                @error('observations')<div class="form-hint" style="color:var(--danger);">{{ $message }}</div>@enderror
                            </div>

                            <!-- Conditions d'acceptation -->
                            <div class="demande-info" style="background:#fefce8; border-left-color:var(--warning); margin:16px 0;">
                                <strong>⚠️ Conditions de sortie :</strong>
                                <ul style="margin:8px 0 0 20px; font-size:var(--app-font-min, 15px); color:var(--gray-700);">
                                    <li>La sortie de chambre est effective après validation définitive par l'administration</li>
                                    <li>Vous devez restituer les clés et l'ensemble des équipements fournis</li>
                                    <li>Un état des lieux de sortie sera réalisé en votre présence</li>
                                    <li>Toute dégradation constatée sera facturée</li>
                                </ul>
                            </div>

                            <div style="display:flex; align-items:center; gap:12px; margin:16px 0;">
                                <input type="checkbox" name="conditions_acceptees" value="1" id="accept-conditions" style="width:18px; height:18px; accent-color:var(--primary);" required>
                                <label for="accept-conditions" style="font-size:var(--app-font-min, 15px); color:var(--gray-700);">
                                    J'accepte les conditions de sortie et je confirme avoir pris connaissance du règlement intérieur
                                </label>
                            </div>

                            <!-- Boutons d'action -->
                            <div class="demande-actions" style="border-top: none; padding-top: 8px;">
                                <button type="submit" class="btn btn-primary" style="padding:12px 32px;">
                                    <i>📤</i> Soumettre la demande de sortie
                                </button>
                                <button type="reset" class="btn btn-outline">
                                    <i>🔄</i> Réinitialiser
                                </button>
                                <span style="font-size:var(--app-font-min, 15px); color:var(--gray-500); margin-left:auto; display:flex; align-items:center; gap:6px;">
                                    <i>🔒</i> Tous les champs marqués d'un <span class="required">*</span> sont obligatoires
                                </span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @elseif(!$activeDeparture)
                <div class="empty-state">
                    <i>🏠</i>
                    <h3>Aucune chambre active</h3>
                    <p>Une assignation de chambre active est nécessaire pour déposer une demande de sortie.</p>
                </div>
            @endif

            <!-- Historique des sorties -->
            @if($departureHistory->isNotEmpty())
            <div style="margin-top:24px; padding-top:16px; border-top:1px solid var(--gray-200);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                    <span style="font-weight:500; font-size:var(--app-font-min, 15px); color:var(--gray-700);">
                        📜 Historique des sorties
                    </span>
                    <span class="badge secondary">{{ $departureHistory->count() }} demande(s)</span>
                </div>

                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap:12px;">
                    @foreach($departureHistory as $departure)
                        <div style="background:var(--gray-50); padding:12px 16px; border-radius:8px; border:1px solid var(--gray-200);">
                            <div style="font-weight:500; font-size:var(--app-font-min, 15px);">Chambre #{{ $departure->chambre?->numero_chambre ?? 'N/D' }}</div>
                            <div style="font-size:var(--app-font-min, 15px); color:var(--gray-500);">Sortie prévue le {{ formatDateFr($departure->date_sortie_prevue, false) }}</div>
                            <span class="badge {{ $departure->statut === 'cloturee' ? 'success' : 'secondary' }}" style="font-size:var(--app-font-min, 15px); margin-top:4px;">
                                {{ $departure->statut === 'cloturee' ? '✅ Clôturée' : '⊘ Annulée' }}
                            </span>
                            @if($departure->motif_decision)<div style="font-size:var(--app-font-min, 15px);color:var(--danger);margin-top:5px;">Motif : {{ $departure->motif_decision }}</div>@endif
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>
    </div>

</div>

<script>
    // ===== Fonction pour l'accordéon =====
    function toggleAccordion(trigger) {
        const content = trigger.nextElementSibling;
        const chevron = trigger.querySelector('.chevron');

        content.classList.toggle('open');
        chevron.classList.toggle('rotated');
    }

</script>
