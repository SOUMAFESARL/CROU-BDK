@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'campagnes'
])

@section('title', 'Traiter la demande')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/campagnes-admission.css') }}">
@endpush

@php
    $derniere = $demande->derniereDemande;
    $mots = preg_split('/\s+/', trim((string) $demande->name)) ?: [];
    $initiales = mb_substr($mots[0] ?? '', 0, 1).mb_substr($mots[1] ?? '', 0, 1);

    // Valeur d'une ligne d'information : texte grisé si non renseigné.
    $valeur = fn ($v, string $vide = 'Non renseigné') => filled($v) ? e($v) : '<span class="ca-muted">'.$vide.'</span>';

    $statutsInscription = [
        'residents' => ['Résident', 'tone-green'], 'en_attente' => ['En attente', 'tone-amber'],
        'demande' => ['Nouvelle demande', 'tone-blue'], 'annuler' => ['Annulé', 'tone-red'],
    ];
    $statutsDocument = [
        'valide' => ['Validé', 'tone-green', 'circle-check'],
        'rejete' => ['Rejeté', 'tone-red', 'circle-x'],
    ];
    $statutsDemande = [
        'Acceptée' => 'tone-green', 'En_attente' => 'tone-amber', 'Refusée' => 'tone-red',
        'annuler' => 'tone-gray', 'demande' => 'tone-blue',
    ];
@endphp

@section('content')
<div class="as-refonte ca-page">
    <x-breadcrumbs
            title="Traiter la demande"
            :items="[
        'Gestion Hébergement' => route('admin.hebergement.index'),
        'Admissions' => route('admin.hebergement.campagnes_admission.admission'),
        'Traiter la demande' => null,
    ]"
    />

    {{-- En-tête du dossier --}}
    <div class="ca-header">
        <div class="ca-header-text">
            <h2 class="ca-title">Demande #{{ $derniere?->numero_demande ?? 'N/D' }}</h2>
            <p class="ca-subtitle">
                {{ $demande->name }}
                @if($demande->matricule) · {{ $demande->matricule }} @endif
                @if($derniere?->created_at) · déposée le {{ \Carbon\Carbon::parse($derniere->created_at)->format('d/m/Y') }} @endif
            </p>
            <div class="mt-2">@include('admin.hebergement.campagnes_admission.partials.etat-dossier', ['etat' => $derniere?->etat_dossier])</div>
        </div>
        <div class="ca-actions">
            @include('admin.etudiant.contract-link', ['student' => $demande])
            @adminroute('admin.hebergement.campagnes_admission.admission')
            <a href="{{ route('admin.hebergement.campagnes_admission.admission') }}" class="ca-btn ca-btn-outline">
                <x-admin-icon name="arrow-left" /> Retour aux admissions
            </a>
            @endadminroute
        </div>
    </div>

    {{-- Messages --}}
    @if(session('success'))
        <div class="ca-alert ca-alert-success" role="status"><x-admin-icon name="circle-check" /> <div>{{ session('success') }}</div></div>
    @endif
    @if(session('error'))
        <div class="ca-alert ca-alert-error" role="alert"><x-admin-icon name="circle-x" /> <div>{{ session('error') }}</div></div>
    @endif
    @if($errors->any())
        <div class="ca-alert ca-alert-error" role="alert">
            <x-admin-icon name="circle-x" />
            <div>Veuillez corriger les points suivants :
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        </div>
    @endif

    @adminblock('housing.discipline')
        @php $disciplineStatus = $demande->statut_disciplinaire; @endphp
        @if($disciplineStatus !== 'aucune')
            <div class="ca-alert ca-alert-warning" role="alert">
                <x-admin-icon name="shield-alert" />
                <div>
                    Statut disciplinaire : <strong>{{ \App\Http\Services\DisciplinaryImpactService::LABELS[$disciplineStatus] }}</strong>.
                    @if(in_array($disciplineStatus, ['suspension', 'expulsion']))
                        Admission et attribution bloquées tant que la sanction est applicable.
                    @endif
                </div>
            </div>
        @endif
    @endadminblock

    <div class="ca-layout">
        {{-- ===================== Colonne principale ===================== --}}
        <div class="ca-main">

            {{-- Critères d'éligibilité --}}
            @adminblock('admissions.eligibility')
            @php
                $campagne = $campagnes['campagnes']->first();
                $niveauActuel = optional($derniere)->niveauEtudiantActuel;

                /* Âge de l'étudiant et âge maximum selon son niveau */
                $age = $demande->date_naissance?->age;
                $niveauKey = 'age_' . strtoupper($niveauActuel->abreviation ?? '') . '_max';
                $ageMax = $campagne?->{$niveauKey} ?? null;

                /* Règles : state = ok | nok | nd (non renseigné) */
                $regles = [];

                // 1. Âge maximum
                if ($ageMax !== null) {
                    $regles[] = $age === null
                        ? ['label' => 'Âge maximum (' . $campagne->niveau . ')', 'campagne' => '≤ ' . $ageMax . ' ans', 'etudiant' => 'Date de naissance non renseignée', 'state' => 'nd', 'ok' => false]
                        : ['label' => 'Âge maximum (niveau ' . strtoupper($niveauActuel->name ?? '?') . ')', 'campagne' => '≤ ' . $ageMax . ' ans', 'etudiant' => $age . ' ans', 'state' => $age <= $ageMax ? 'ok' : 'nok', 'ok' => $age <= $ageMax];
                }

                // 2. Moyenne minimale
                if ($campagne?->moyenne_min !== null) {
                    $moyenne = optional($derniere)->moyenne ?? null;
                    $regles[] = $moyenne === null
                        ? ['label' => 'Moyenne', 'campagne' => '≥ ' . $campagne->moyenne_min . '/20', 'etudiant' => 'Non renseignée', 'state' => 'nd', 'ok' => false]
                        : ['label' => 'Moyenne', 'campagne' => '≥ ' . $campagne->moyenne_min . '/20', 'etudiant' => $moyenne . '/20', 'state' => $moyenne >= $campagne->moyenne_min ? 'ok' : 'nok', 'ok' => $moyenne >= $campagne->moyenne_min];
                }

                // 3. Pourcentage de crédits validés (hors L1)
                if ($campagne?->pourcentage_validate !== null && ($niveauActuel->abreviation ?? null) != 'L1') {
                    $credits = optional($derniere)->credit_valider ?? null;
                    $regles[] = $credits === null
                        ? ['label' => 'Crédits validés', 'campagne' => '≥ ' . $campagne->pourcentage_validate . ' %', 'etudiant' => 'Non renseigné', 'state' => 'nd', 'ok' => false]
                        : ['label' => 'Crédits validés', 'campagne' => '≥ ' . $campagne->pourcentage_validate . ' %', 'etudiant' => $credits . ' %', 'state' => $credits >= $campagne->pourcentage_validate ? 'ok' : 'nok', 'ok' => $credits >= $campagne->pourcentage_validate];
                }

                // 4. Distance domicile ↔ campus
                if ($campagne?->distance_min !== null) {
                    $distance = $demande->distance ?? null;
                    $regles[] = $distance === null
                        ? ['label' => 'Distance domicile – campus', 'campagne' => '≥ ' . $campagne->distance_min . ' km', 'etudiant' => 'Non renseignée', 'state' => 'nd', 'ok' => false]
                        : ['label' => 'Distance domicile – campus', 'campagne' => '≥ ' . $campagne->distance_min . ' km', 'etudiant' => $distance . ' km', 'state' => $distance >= $campagne->distance_min ? 'ok' : 'nok', 'ok' => $distance >= $campagne->distance_min];
                }

                // 5. Boursier (toujours accepté)
                if ($campagne !== null) {
                    $regles[] = ['label' => 'Boursier', 'campagne' => 'Acceptés', 'etudiant' => $demande->boursier ? 'Oui' : 'Non', 'state' => 'ok', 'ok' => true];
                }

                // 6. Redoublement
                if ($campagne?->redoublement !== null) {
                    $autoriseRedoublement = (bool) $campagne->redoublement;
                    $estRedoublant = (bool) ($demande->redoublant ?? false);
                    $conforme = $autoriseRedoublement || !$estRedoublant;
                    $regles[] = ['label' => 'Redoublement', 'campagne' => $autoriseRedoublement ? 'Autorisé' : 'Non autorisé', 'etudiant' => $estRedoublant ? 'Redoublant' : 'Non redoublant', 'state' => $conforme ? 'ok' : 'nok', 'ok' => $conforme];
                }

                // 7. PMR
                if ($campagne?->pmr !== null) {
                    $pmrAutorise = (bool) $campagne->pmr;
                    $estPmr = (bool) ($demande->pmr ?? false);
                    $conforme = !$estPmr || $pmrAutorise;
                    $regles[] = ['label' => 'PMR (mobilité réduite)', 'campagne' => $pmrAutorise ? 'Accepté' : 'Non accepté', 'etudiant' => $estPmr ? 'Oui' : 'Non', 'state' => $conforme ? 'ok' : 'nok', 'ok' => $conforme];
                }

                // 8. Étudiants internationaux
                if ($campagne?->internationnaux !== null) {
                    $intlAutorise = (bool) $campagne->internationnaux;
                    $estIntl = isset($demande->nationalite)
                        && strtolower(trim($demande->nationalite)) !== 'ivoirienne'
                        && strtolower(trim($demande->nationalite)) !== 'ivoirien';
                    $conforme = !$estIntl || $intlAutorise;
                    $regles[] = ['label' => 'Étudiant international', 'campagne' => $intlAutorise ? 'Accepté' : 'Non accepté', 'etudiant' => $estIntl ? 'Oui (' . ($demande->nationalite ?? '?') . ')' : 'Non', 'state' => $conforme ? 'ok' : 'nok', 'ok' => $conforme];
                }

                // 9. Nouveau bachelier
                if ($campagne?->new_bachelier !== null) {
                    $nbAutorise = (bool) $campagne->new_bachelier;
                    $estNb = (bool) ($demande->nouveau_bachelier ?? false);
                    $conforme = !$estNb || $nbAutorise;
                    $regles[] = ['label' => 'Nouveau bachelier', 'campagne' => $nbAutorise ? 'Accepté' : 'Non accepté', 'etudiant' => $estNb ? 'Oui' : 'Non', 'state' => $conforme ? 'ok' : 'nok', 'ok' => $conforme];
                }

                // 10. Réadmission
                if ($campagne?->readmission !== null) {
                    $raAutorisee = (bool) $campagne->readmission;
                    $estRa = $demande->demandes->count() > 1;
                    $conforme = !$estRa || $raAutorisee;
                    $regles[] = ['label' => 'Réadmission', 'campagne' => $raAutorisee ? 'Autorisée' : 'Non autorisée', 'etudiant' => $estRa ? 'Oui (' . $demande->demandes->count() . ' demande(s))' : 'Non', 'state' => $conforme ? 'ok' : 'nok', 'ok' => $conforme];
                }

                // 11. Documents validés
                $totalDocs = count($demande->documents);
                $valideDocs = collect($demande->documents)->where('statut', 'valide')->count();
                $pctDocs = $totalDocs > 0 ? round(($valideDocs / $totalDocs) * 100) : 0;
                $pctRequis = $campagne?->pourcentage_validate ?? 100;
                $regles[] = ['label' => 'Documents validés', 'campagne' => '≥ ' . $pctRequis . ' %', 'etudiant' => $valideDocs . '/' . $totalDocs . ' (' . $pctDocs . ' %)', 'state' => $pctDocs >= $pctRequis ? 'ok' : 'nok', 'ok' => $pctDocs >= $pctRequis];

                /* Score de conformité global */
                $totalRegles = count($regles);
                $conformeCount = collect($regles)->where('ok', true)->count();
                $scoreGlobal = $totalRegles > 0 ? round(($conformeCount / $totalRegles) * 100) : 0;
                $couleurScore = $scoreGlobal >= 80 ? '#067647' : ($scoreGlobal >= 50 ? '#b54708' : '#b42318');
                $circonference = round(2 * pi() * 26, 1);

                $etatsRegle = [
                    'ok' => ['Conforme', 'tone-green', 'circle-check'],
                    'nok' => ['Non conforme', 'tone-red', 'circle-x'],
                    'nd' => ['Non renseigné', 'tone-amber', 'triangle-alert'],
                ];
            @endphp
            <section class="ca-card">
                <div class="ca-card-header">
                    <h3 class="ca-card-title"><span class="ca-kpi-icon tone-green"><x-admin-icon name="list-checks" /></span> Critères d'éligibilité</h3>
                    <span class="ca-card-meta">Campagne {{ $campagne?->annee_campagne ?? 'N/D' }}</span>
                </div>
                <div class="ca-card-body">
                    <div class="ca-score mb-4">
                        <div class="ca-ring" role="img" aria-label="{{ $scoreGlobal }} % des critères conformes">
                            <svg viewBox="0 0 64 64">
                                <circle cx="32" cy="32" r="26" fill="none" stroke="#e8ebe4" stroke-width="7"/>
                                <circle cx="32" cy="32" r="26" fill="none" stroke="{{ $couleurScore }}" stroke-width="7" stroke-linecap="round"
                                        stroke-dasharray="{{ round($circonference * $scoreGlobal / 100, 1) }} {{ $circonference }}"/>
                            </svg>
                            <span style="color: {{ $couleurScore }}">{{ $scoreGlobal }}%</span>
                        </div>
                        <div>
                            <div class="ca-strong">
                                @if($scoreGlobal == 100)
                                    Tous les critères sont conformes — étudiant éligible.
                                @elseif($scoreGlobal >= 60)
                                    Quelques critères non conformes — vérification recommandée avant décision.
                                @else
                                    Plusieurs critères non conformes — étudiant probablement non éligible.
                                @endif
                            </div>
                            <div class="ca-small ca-muted">{{ $conformeCount }} critère(s) conforme(s) sur {{ $totalRegles }}.</div>
                        </div>
                    </div>

                    <div class="ca-table-wrap" style="border: 1px solid var(--ca-border); border-radius: var(--ca-radius);">
                        <table class="ca-table">
                            <thead>
                            <tr>
                                <th>Critère</th>
                                <th>Règle de la campagne</th>
                                <th>Valeur de l'étudiant</th>
                                <th class="is-actions">Statut</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($regles as $regle)
                                @php [$libelleEtat, $toneEtat, $iconeEtat] = $etatsRegle[$regle['state']]; @endphp
                                <tr class="{{ $regle['ok'] ? '' : 'is-danger' }}">
                                    <td class="ca-strong">{{ $regle['label'] }}</td>
                                    <td class="ca-muted ca-mono">{{ $regle['campagne'] }}</td>
                                    <td class="{{ $regle['ok'] ? '' : 'text-red-700' }}">{{ $regle['etudiant'] }}</td>
                                    <td class="is-actions">
                                        <span class="ca-badge {{ $toneEtat }}"><x-admin-icon :name="$iconeEtat" size="14" /> {{ $libelleEtat }}</span>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
            @endadminblock

            {{-- Validation des documents --}}
            @adminblock('admissions.documents')
            @php
                $docs = collect($demande->documents);
                $docsValides = $docs->where('statut', 'valide')->count();
            @endphp
            <section class="ca-card">
                <div class="ca-card-header">
                    <h3 class="ca-card-title"><span class="ca-kpi-icon tone-blue"><x-admin-icon name="file-text" /></span> Documents fournis</h3>
                    <span class="ca-card-meta">{{ $docsValides }} / {{ $docs->count() }} validé(s)</span>
                </div>
                <div class="ca-card-body">
                    @if($docs->count())
                        <div class="ca-progress mb-4" aria-hidden="true"><span style="width: {{ round($docsValides / $docs->count() * 100) }}%"></span></div>
                    @endif
                    @forelse($docs as $document)
                        @php [$libelleDoc, $toneDoc, $iconeDoc] = $statutsDocument[$document['statut']] ?? ['En vérification', 'tone-amber', 'clock']; @endphp
                        <div class="ca-doc">
                            <div class="ca-doc-main">
                                <span class="ca-kpi-icon tone-gray"><x-admin-icon name="file-text" /></span>
                                <div>
                                    <span class="ca-doc-name">{{ $document['type_document'] }}</span>
                                    <a href="{{ $document['chemin_fichier'] }}" target="_blank" rel="noopener" class="ca-doc-link">
                                        <x-admin-icon name="external-link" size="14" /> Ouvrir le document
                                    </a>
                                </div>
                            </div>
                            <div class="ca-doc-actions">
                                @if($document['statut'] != 'valide' && $document['statut'] != 'rejete')
                                    @adminroute('admin.hebergement.campagnes_admission.validate_document')
                                    <a href="{{ route('admin.hebergement.campagnes_admission.validate_document', ['documentId' => $document['id_document'], 'statut' => 'valide']) }}"
                                       class="ca-btn ca-btn-soft ca-btn-sm">
                                        <x-admin-icon name="check" size="16" /> Valider
                                    </a>
                                    <a href="{{ route('admin.hebergement.campagnes_admission.validate_document', ['documentId' => $document['id_document'], 'statut' => 'rejete']) }}"
                                       class="ca-btn ca-btn-danger ca-btn-sm"
                                       onclick="return confirm(@js('Rejeter le document « '.$document['type_document'].' » ?'));">
                                        <x-admin-icon name="x" size="16" /> Rejeter
                                    </a>
                                    @endadminroute
                                @endif
                                <span class="ca-badge {{ $toneDoc }}"><x-admin-icon :name="$iconeDoc" size="14" /> {{ $libelleDoc }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="ca-empty">
                            <span class="ca-empty-icon"><x-admin-icon name="file-text" size="22" /></span>
                            <span>Aucun document fourni.</span>
                        </div>
                    @endforelse
                </div>
            </section>
            @endadminblock

            {{-- Dérogation --}}
            @adminpermission('hebergement.admissions.view')
                @include('admin.hebergement.campagnes_admission.admission.derogations')
            @endadminpermission

            {{-- Suivi du dossier (partiel partagé avec l'espace étudiant) --}}
            @adminpermission('hebergement.admissions.view')
            @if($derniere)
                <section class="ca-card">
                    <div class="ca-card-header">
                        <h3 class="ca-card-title"><span class="ca-kpi-icon tone-purple"><x-admin-icon name="route" /></span> Suivi du dossier</h3>
                    </div>
                    <div class="ca-card-body">
                        @php
                            $parcours = ['brouillon', 'soumis', 'en_etude', 'eligible', 'pre_attribue', 'accepte', 'attribue_definitif'];
                            $position = array_search($derniere->etat_dossier, $parcours, true);
                        @endphp
                        <ol class="ca-steps" aria-label="Parcours du dossier">
                            @foreach($parcours as $i => $etape)
                                <li class="{{ $position !== false && $i < $position ? 'is-done' : '' }} {{ $position === $i ? 'is-current' : '' }}"
                                    @if($position === $i) aria-current="step" @endif>
                                    @if($position !== false && $i < $position)<x-admin-icon name="check" size="12" />@endif
                                    {{ \App\Http\Services\DossierWorkflow::LABELS[$etape] }}
                                </li>
                            @endforeach
                        </ol>
                        <div class="ca-legacy">
                            @include('dossier-cycle', ['dossier' => $derniere, 'mode' => 'admin'])
                        </div>
                    </div>
                </section>
            @endif
            @endadminpermission
        </div>

        {{-- ===================== Colonne latérale ===================== --}}
        <aside class="ca-aside">
            @adminblock('admissions.personal')
            @php [$libelleInscription, $toneInscription] = $statutsInscription[$demande->statut_inscription] ?? ['Non défini', 'tone-gray']; @endphp
            <section class="ca-card">
                <div class="ca-card-body">
                    <div class="ca-profile mb-3">
                        <span class="ca-avatar ca-avatar-lg" aria-hidden="true">{{ $initiales ?: '?' }}</span>
                        <div>
                            <p class="ca-profile-name">{{ $demande->name }}</p>
                            <div class="ca-small ca-muted">{{ $demande->matricule ?? 'Matricule non renseigné' }}</div>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 mb-2">
                        <span class="ca-badge {{ $toneInscription }}">{{ $libelleInscription }}</span>
                        @if($demande->is_validation)
                            <span class="ca-badge tone-green"><x-admin-icon name="circle-check" size="14" /> Validé</span>
                        @elseif($demande->is_rejet)
                            <span class="ca-badge tone-red"><x-admin-icon name="circle-x" size="14" /> Rejeté</span>
                        @else
                            <span class="ca-badge tone-amber"><x-admin-icon name="clock" size="14" /> Validation en cours</span>
                        @endif
                        @if($demande->boursier)
                            <span class="ca-badge tone-blue">Boursier</span>
                        @endif
                    </div>

                    <h4 class="ca-dl-title">Admission</h4>
                    <dl class="ca-dl">
                        <div class="ca-dl-row"><dt>Niveau</dt><dd>{!! $valeur($demande->niveau) !!}</dd></div>
                        <div class="ca-dl-row"><dt>Campagne</dt><dd>{!! $valeur($demande->campagne, 'Non renseignée') !!}</dd></div>
                        <div class="ca-dl-row"><dt>Score</dt><dd style="color: var(--ca-primary)">{{ $demande->score ?? 0 }}/100</dd></div>
                        <div class="ca-dl-row"><dt>Boursier</dt><dd>{{ $demande->boursier ? 'Oui' : 'Non' }}</dd></div>
                        <div class="ca-dl-row"><dt>Groupe sanguin</dt><dd>{!! $valeur($demande->groupeSanguin?->name) !!}</dd></div>

                    </dl>
                    <h4 class="ca-dl-title">Contact</h4>
                    <dl class="ca-dl">
                        <div class="ca-dl-row"><dt>Téléphone 1</dt><dd>{!! $valeur($demande->telephone1) !!}</dd></div>
                        <div class="ca-dl-row"><dt>Téléphone 2</dt><dd>{!! $valeur($demande->telephone2) !!}</dd></div>
                        <div class="ca-dl-row"><dt>E-mail 1</dt><dd>{!! $valeur($demande->email1) !!}</dd></div>
                        <div class="ca-dl-row"><dt>E-mail 2</dt><dd>{!! $valeur($demande->email2) !!}</dd></div>

                    </dl>
                    <h4 class="ca-dl-title">Adresse</h4>
                    <dl class="ca-dl">
                        <div class="ca-dl-row"><dt>Région</dt><dd>{!! $valeur($demande->region->name ?? null, 'Non renseignée') !!}</dd></div>
                        <div class="ca-dl-row"><dt>Ville</dt><dd>{!! $valeur($demande->ville->name ?? null, 'Non renseignée') !!}</dd></div>
                        <div class="ca-dl-row"><dt>Adresse</dt><dd>{!! $valeur($demande->adresse, 'Non renseignée') !!}</dd></div>
                        <div class="ca-dl-row"><dt>Nationalité</dt><dd>{!! $valeur($demande->nationalite, 'Non renseignée') !!}</dd></div>

                    </dl>
                    <h4 class="ca-dl-title">Pièce d'identité</h4>
                    <dl class="ca-dl">
                        <div class="ca-dl-row"><dt>Date de naissance</dt><dd>{!! $valeur($demande->date_naissance?->format('d/m/Y')) !!}</dd></div>
                        <div class="ca-dl-row"><dt>Lieu de naissance</dt><dd>{!! $valeur($demande->lieu_naissance) !!}</dd></div>
                        <div class="ca-dl-row"><dt>Type</dt><dd>{!! $valeur($demande->type_piece_identite) !!}</dd></div>
                        <div class="ca-dl-row"><dt>Numéro</dt><dd>{!! $valeur($demande->numero_piece) !!}</dd></div>
                        <div class="ca-dl-row"><dt>Expiration</dt><dd>{!! $valeur(isset($demande->date_expiration) ? $demande->date_expiration->format('d/m/Y') : null, 'Non renseignée') !!}</dd></div>
                    </dl>
                </div>
            </section>
            @endadminblock

            @adminblock('admissions.history')
            <section class="ca-card">
                <div class="ca-card-header">
                    <h3 class="ca-card-title"><span class="ca-kpi-icon tone-gray"><x-admin-icon name="history" /></span> Historique des demandes</h3>
                    <span class="ca-card-meta">{{ $demande->demandes->count() }}</span>
                </div>
                <div class="ca-card-body">
                    <ul class="ca-timeline">
                        @forelse($demande->demandes->sortByDesc('created_at')->values() as $index => $item)
                            <li>
                                <div>
                                    <div class="ca-strong">
                                        Demande #{{ $item->numero_demande }}
                                        @if($index === 0)<span class="ca-badge tone-blue" style="margin-left: 4px">Dernière</span>@endif
                                    </div>
                                    <div class="ca-small ca-muted">
                                        {{ $item->annee_academique_precedent }}
                                        @if($item->annee_academique_precedent) · @endif
                                        {{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y') }}
                                    </div>
                                </div>
                                <span class="ca-badge {{ $statutsDemande[$item->statut_demande] ?? 'tone-gray' }}">
                                    {{ ucfirst(str_replace('_', ' ', (string) $item->statut_demande)) }}
                                </span>
                            </li>
                        @empty
                            <li class="ca-empty">
                                <span class="ca-empty-icon"><x-admin-icon name="folder-open" size="22" /></span>
                                <span>Aucune demande trouvée.</span>
                            </li>
                        @endforelse
                    </ul>
                </div>
            </section>
            @endadminblock
        </aside>
    </div>
</div>
@endsection
