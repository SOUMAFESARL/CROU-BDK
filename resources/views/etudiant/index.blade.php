<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Espace Étudiant — Résidence</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono&display=swap" rel="stylesheet"/>
    <!-- Dans votre layout -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('files/assets/css/system-theme.css') }}">
     <link rel="icon" href="{{ asset('admin/assets/images/logos/crou_bdk.jpeg') }}?v=20261015" type="image/jpeg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Inclure jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Pour SweetAlert2 (optionnel) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="{{ asset('css/admin-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('files/assets/css/student-space.css') }}?v=20261014b">

</head>
<body class="{{ systemConfig('force_dark_mode', false) ? 'app-dark-mode' : '' }}">



@php

    $etudiant = $result['etudiant']['data'];

    $derniere_demande = $etudiant['derniereDemande'] ?? null;
    $demandes = $etudiant['demandes'] ?? null;
     $statut_demande = $derniere_demande['statut_demande'] ?? null;
     $assignationsChambre =$etudiant['assignationsChambre'] ?? null;
    $data=[
            'statCampagne' => $result['data']['stats'],
            'etudiant' => $etudiant,
            'campagne' => isset($result['data']['campagnes'][0]) ? $result['data']['campagnes'][0] : null,
            'derniere_demande' => $derniere_demande,
            'demandes' => $demandes,
            'statut_demande' => $statut_demande,
            'has_accepted_demande' => $statut_demande === 'Acceptée' || count($etudiant['demandes'] ?? []) > 1,
            'assignationsChambre' => $assignationsChambre,
            'derniereAssignation' => ($derniere_demande && isset($assignationsChambre) && !empty($assignationsChambre)) ? $assignationsChambre->where('campagne_id', $derniere_demande->campagne_id)->first() : null,
            'campuses' => $result['carte']['campuses'],
            'cites' => $result['carte']['cites'],
            'batiments' => $result['carte']['batiments'],
        ];


@endphp

<x-etudiant-shell :student="$data['etudiant']">
   @includeIf('etudiant.dashboard',$data)

    <!-- ── Ma Chambre ── -->

    @include('etudiant.chambre',$data)
    <!-- ── Demandes ── -->
    @include('etudiant.demandes',$data)

    @include('etudiant.etat-lieux',$data)
    <!-- ── Paiements ── -->
  @include('etudiant.paiements', [
    'paymentProviders' => $result['paymentProviders'] ?? collect(),
    'etudiant' => $result['paiementData']['etudiant'] ?? null,
    'loyers' => $result['paiementData']['loyers'] ?? collect(),
    'paiements' => $result['paiementData']['paiements'] ?? collect(),
    'stats' => $result['paiementData']['stats'] ?? [],
    'impayes' => $result['paiementData']['impayes'] ?? collect(),
    'prochainPaiement' => $result['paiementData']['prochainPaiement'] ?? null,
])

    <!-- ── Maintenance ── -->
    @include('etudiant.maintenance',$data)

    @include('etudiant.incidents', ['reports' => $result['disciplinaryReports'] ?? collect(), 'assignment' => $data['derniereAssignation']])

    @include('etudiant.carte', $data)

    @include('etudiant.annonce', $data)
</x-etudiant-shell>

<div id="toast"></div>

<script src="{{ asset('js/admin-shell.js') }}" defer></script>
<script>
    // Bascule entre les sections du tableau de bord depuis la barre latérale
    // (composant etudiant-shell) : entrée active, titre et fil d'Ariane de la barre du haut.
    const shell = document.querySelector('[data-admin-shell]');
    const shellTitle = document.querySelector('[data-shell-title]');
    const shellCrumb = document.querySelector('[data-shell-crumb-current]');
    const shellCrumbHome = document.querySelectorAll('[data-shell-crumb-home]');

    document.querySelectorAll('#nav a[data-page]').forEach(link => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            const page = link.dataset.page;
            const title = link.textContent.trim();
            document.querySelectorAll('#nav .as-menu-button').forEach(a => {
                a.dataset.active = 'false';
                a.removeAttribute('aria-current');
            });
            document.querySelectorAll('.content').forEach(c => c.classList.remove('active'));
            link.dataset.active = 'true';
            link.setAttribute('aria-current', 'page');
            document.getElementById('page-' + page)?.classList.add('active');
            shellTitle.textContent = title;
            shellCrumb.textContent = title;
            shellCrumbHome.forEach(el => el.hidden = page === 'dashboard');
            shell.dataset.mobileOpen = 'false';

            const url = new URL(window.location.href);
            if (page === 'dashboard') {
                url.searchParams.delete('section');
            } else {
                url.searchParams.set('section', page);
            }
            url.searchParams.delete('focus');
            history.replaceState(null, '', url);
            if (link.dataset.scrollTarget) {
                requestAnimationFrame(() => {
                    document.getElementById(link.dataset.scrollTarget)?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start',
                    });
                });
            }
        });
    });

    const requestedSection = new URLSearchParams(window.location.search).get('section');
    if (requestedSection) {
        const focus = new URLSearchParams(window.location.search).get('focus');
        const selector = focus === 'departure'
            ? 'nav a[data-scroll-target="departure-request-section"]'
            : `nav a[data-page="${requestedSection}"]`;
        document.querySelector(selector)?.click();
    }

    @if($errors->hasAny(['departure', 'date_sortie_prevue', 'motif', 'observations', 'conditions_acceptees']))
        document.querySelector('nav a[data-scroll-target="departure-request-section"]')?.click();
    @endif

    // « Mon profil » (menu de l'avatar) : ouvre le modal du tableau de bord quand il existe.
    const profileModalAvailable = () => document.getElementById('studentProfileModal') && typeof openProfileModal === 'function';
    document.querySelectorAll('[data-student-profile]').forEach(link => {
        link.addEventListener('click', (event) => {
            if (!profileModalAvailable()) return;
            event.preventDefault();
            link.closest('.as-dropdown').hidden = true;
            document.querySelector('[data-shell-user-toggle]')?.setAttribute('aria-expanded', 'false');
            openProfileModal();
        });
    });
    if (window.location.hash === '#profil' && profileModalAvailable()) {
        openProfileModal();
    }

    function showToast(msg) {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 3000);
    }

    function acceptDemande() {
        document.getElementById('badge-dem1').className = 'badge success';
        document.getElementById('badge-dem1').textContent = 'Acceptée';
        document.getElementById('actions-dem1').innerHTML = '<span style="font-size:var(--app-font-min, 15px);color:var(--teal-600);font-weight:600;">✓ Vous avez accepté cette offre</span>';

        showToast('Offre acceptée. La résidence va être notifiée.');
    }

    function rejectDemande() {
        document.getElementById('badge-dem1').className = 'badge danger';
        document.getElementById('badge-dem1').textContent = 'Refusée';
        document.getElementById('actions-dem1').innerHTML = '<span style="font-size:var(--app-font-min, 15px);color:var(--red-400);font-weight:600;">Offre refusée. Une nouvelle proposition vous sera envoyée.</span>';
        showToast('Offre refusée.');
    }

    function submitMaintenance() {
        const sel = document.querySelector('.form-select').value;
        if (sel === '— Sélectionner —') { showToast('Veuillez choisir une catégorie.'); return; }
        showToast('Demande soumise avec succès !');
    }

    function AcceptOrRefuseDemande(demandeId, action) {
        if (!confirm(`Êtes-vous sûr de vouloir ${action === 'accept' ? 'accepter' : 'refuser'} cette demande ?`)) {
            return;
        }

        $.ajax({
            url: `/demande/${demandeId}/${action}`,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                _method: 'PUT' // Pour Laravel
            },
            beforeSend: function() {
                $(`#demande-${demandeId}`).addClass('opacity-50');
                $(`#btn-${demandeId}`).prop('disabled', true);
            },
            success: function(response) {
                if (response.status === 'success') {
                    // Mettre à jour l'interface sans recharger
                    $(`#demande-${demandeId}`).fadeOut(300, function() {
                        $(this).remove();

                        // Mettre à jour le compteur
                        let count = parseInt($('#demandes-count').text()) - 1;
                        $('#demandes-count').text(count);

                        // Afficher message de succès
                        $('#notification-area').html(`
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            ${response.message}
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    `);
                    });
                } else {
                    alert(response.message || 'Erreur lors du traitement');
                }
            },
            error: function(xhr) {
                console.error(xhr);
                alert('Une erreur est survenue. Veuillez réessayer.');
            },
            complete: function() {
                $(`#demande-${demandeId}`).removeClass('opacity-50');
                $(`#btn-${demandeId}`).prop('disabled', false);
            }
        });
    }

</script>
</body>
</html>
