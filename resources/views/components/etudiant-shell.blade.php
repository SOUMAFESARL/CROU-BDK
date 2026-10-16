@props(['student' => null, 'title' => null])
@php
    /*
     * Coque de l'espace étudiant : même barre latérale et même barre du haut que
     * l'admin (<x-admin-shell>), mêmes styles (public/css/admin-shell.css) et même
     * comportement (public/js/admin-shell.js : repli, tiroir mobile, menu avatar).
     * Seule différence visuelle : l'entrée de menu sélectionnée est orange
     * (classe .etudiant-shell, styles dans public/files/assets/css/student-space.css).
     *
     * Les entrées `page` correspondent aux sections du tableau de bord
     * (etudiant/index.blade.php) : sur cette page, le script local bascule la
     * section sans recharger ; ailleurs, le lien ouvre ?section=...
     */
    $student = $student ?? auth('etudiant')->user();
    $collapsed = request()->cookie('sidebar_state') === 'false';
    $dashboard = route('etudiants.dashboard');

    // Menu commun à la coque Blade et à la mise en page React (App\Services\EtudiantMenu).
    $groupes = collect(app(\App\Services\EtudiantMenu::class)->groupes(request()))
        ->mapWithKeys(fn ($groupe) => [$groupe['label'] => $groupe['items']])
        ->all();
    $actif = collect($groupes)->flatten(1)->firstWhere('active', true);

    $title = $title ?: ($actif['label'] ?? 'Espace étudiant');
    $surAccueil = ($actif['page'] ?? null) === 'dashboard';

    $nom = $student?->name ?? 'Étudiant';
    $initiales = \App\Services\EtudiantMenu::initiales($nom);
@endphp

<div class="admin-shell etudiant-shell" data-admin-shell data-state="{{ $collapsed ? 'collapsed' : 'expanded' }}">
    <aside class="as-sidebar" id="admin-shell-sidebar">
        <div class="as-sidebar-header">
            <div class="as-brand-row">
                <a href="{{ $dashboard }}" class="as-brand">
                    <img src="{{ asset('admin/assets/images/logos/crou_bdk.jpeg') }}" alt="CROU Bondoukou">
                    <span class="as-brand-name">CROU Bondoukou</span>
                </a>
            </div>
        </div>

        <nav class="as-content admin-shell-nav" id="nav" aria-label="Navigation principale">
            @foreach ($groupes as $nomGroupe => $items)
                <div class="as-group-label">{{ $nomGroupe }}</div>
                <ul class="as-menu">
                    @foreach ($items as $item)
                        <li>
                            <a href="{{ $item['href'] }}"
                               class="as-menu-button"
                               data-active="{{ $item['active'] ? 'true' : 'false' }}"
                               data-tooltip="{{ $item['label'] }}"
                               @if ($item['page']) data-page="{{ $item['page'] }}" @endif
                               @if ($item['active']) aria-current="page" @endif>
                                <x-admin-icon :name="$item['icon']" />
                                <span>{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </nav>
    </aside>

    <div class="as-backdrop" data-shell-close></div>

    <div class="as-inset">
        <header class="as-topbar">
            <button type="button" class="as-btn as-trigger as-topbar-trigger" data-shell-toggle aria-controls="admin-shell-sidebar" aria-label="Afficher ou masquer le menu">
                <x-admin-icon name="panel-left" size="16" />
            </button>
            <span class="as-title" data-shell-title>{{ $title }}</span>
            {{-- Fil d'Ariane : écrans larges uniquement (remplace alors le titre). --}}
            <nav class="as-breadcrumb" aria-label="Fil d’Ariane">
                <ol>
                    <li data-shell-crumb-home @if ($surAccueil) hidden @endif><a href="{{ $dashboard }}">Accueil</a></li>
                    <li data-shell-crumb-home aria-hidden="true" @if ($surAccueil) hidden @endif><x-admin-icon name="chevron-right" size="14" /></li>
                    <li><span aria-current="page" data-shell-crumb-current>{{ $title }}</span></li>
                </ol>
            </nav>

            <div class="as-user" data-shell-user>
                <button type="button" class="as-btn as-user-trigger" data-shell-user-toggle aria-haspopup="menu" aria-expanded="false">
                    <span class="as-avatar">{{ $initiales }}</span>
                    <span class="as-user-text">
                        <span class="as-user-name">{{ $nom }}</span>
                        @if ($student?->matricule)
                            <span class="as-user-role">{{ $student->matricule }}</span>
                        @endif
                    </span>
                    <x-admin-icon name="chevron-down" class="as-user-chevron" />
                </button>
                <div class="as-dropdown" role="menu" hidden>
                    <div class="as-dropdown-label">
                        <span class="as-user-name">{{ $nom }}</span>
                        <span class="as-user-role">Année académique {{ getAnneeUniversitaire(date('Y-m-d')) }}</span>
                    </div>
                    <div class="as-dropdown-sep"></div>
                    <a href="{{ route('etudiants.profile') }}" class="as-dropdown-item" role="menuitem">
                        <x-admin-icon name="user-round" />
                        Mon profil
                    </a>
                    <a href="{{ route('etudiants.documents.personal') }}" class="as-dropdown-item" role="menuitem">
                        <x-admin-icon name="folder-open" />
                        Mes documents
                    </a>
                    <div class="as-dropdown-sep"></div>
                    {{-- Déconnexion en POST classique (route etudiants.logout), comme dans l'admin. --}}
                    <form method="POST" action="{{ route('etudiants.logout') }}" style="margin: 0">
                        @csrf
                        <button type="submit" class="as-btn as-dropdown-item as-danger" role="menuitem">
                            <x-admin-icon name="log-out" />
                            Déconnexion
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="as-main">
            {{ $slot }}
        </main>
    </div>
</div>
<div class="as-tooltip" role="tooltip" hidden></div>
