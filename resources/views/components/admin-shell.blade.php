@props(['title' => '', 'breadcrumbs' => null])
@php
    /*
     * Coque des pages admin Blade : barre latérale + barre du haut identiques
     * au layout React (resources/js/components/layout/admin-layout.tsx).
     * Même menu (config/admin_menu.php filtré par AdminAccess) et même cookie
     * `sidebar_state` que la Sidebar shadcn : l'état replié suit d'une page à l'autre.
     * Styles : public/css/admin-shell.css ; comportement : public/js/admin-shell.js.
     */
    $adminUser = auth('admin')->user();
    $menu = app(\App\Services\AdminMenu::class)->forUser($adminUser, request());
    $collapsed = request()->cookie('sidebar_state') === 'false';
    $initiales = collect(preg_split('/\s+/', trim($adminUser?->name ?? '')))
        ->filter()
        ->take(2)
        ->map(fn ($mot) => mb_strtoupper(mb_substr($mot, 0, 1)))
        ->implode('');
    $avatar = $adminUser ? ($adminUser->photo ?: asset('files/assets/images/avatar-4.jpg')) : null;

    // Fil d'Ariane de la barre du haut (écrans larges) : [libellé => lien|null].
    // Par défaut : Accueil › rubrique active du menu › module actif du sous-menu › titre de la page ;
    // une page peut fournir le sien via @extends(..., ['breadcrumbs' => [...]]).
    if (! is_array($breadcrumbs)) {
        $breadcrumbs = [];
        $accueil = $menu[0] ?? null;
        $rubrique = collect($menu)->firstWhere('active', true);
        if ($accueil && $accueil !== $rubrique) {
            $breadcrumbs['Accueil'] = $accueil['href'];
        }
        if ($rubrique && $rubrique['label'] !== $title) {
            $breadcrumbs[$rubrique['label']] = $rubrique['href'];
        }
        $module = collect($rubrique['children'] ?? [])->firstWhere('active', true);
        if ($module && $module['label'] !== $title) {
            $breadcrumbs[$module['label']] = $module['href'];
        }
        if ($title !== '') {
            $breadcrumbs[$title] = null;
        }
    }
@endphp

<div class="admin-shell" data-admin-shell data-state="{{ $collapsed ? 'collapsed' : 'expanded' }}">
    <aside class="as-sidebar" id="admin-shell-sidebar">
        <div class="as-sidebar-header">
            {{-- Logo au-dessus du nom ; replié : logo seul. --}}
            <div class="as-brand-row">
                <a href="{{ $menu[0]['href'] ?? '#' }}" class="as-brand">
                    <img src="{{ asset('admin/assets/images/logos/crou_bdk.jpeg') }}" alt="CROU Bondoukou">
                    <span class="as-brand-name">CROU Bondoukou</span>
                </a>
            </div>
        </div>

        <nav class="as-content admin-shell-nav" aria-label="Navigation principale">
            <div class="as-group-label">Navigation</div>
            <ul class="as-menu">
                @foreach ($menu as $item)
                    @php
                        $children = $item['children'] ?? [];
                        $activeChild = collect($children)->contains('active', true);
                    @endphp
                    <li class="as-menu-item"
                        @if (count($children))
                            data-has-sub
                            data-open="{{ $item['active'] ? 'true' : 'false' }}"
                            data-active-child="{{ $activeChild ? 'true' : 'false' }}"
                        @endif>
                        <a href="{{ $item['href'] }}"
                           class="as-menu-button"
                           data-active="{{ $item['active'] ? 'true' : 'false' }}"
                           data-tooltip="{{ $item['label'] }}"
                           @if ($item['active'] && ! $activeChild) aria-current="page" @endif>
                            <x-admin-icon :name="$item['icon']" />
                            <span>{{ $item['label'] }}</span>
                        </a>
                        @if (count($children))
                            {{-- Sous-menu : déplié dans la barre ; en mode replié, menu flottant au survol (admin-shell.js). --}}
                            <button type="button" class="as-btn as-sub-toggle" data-sub-toggle
                                    aria-controls="as-sub-{{ $loop->index }}"
                                    aria-expanded="{{ $item['active'] ? 'true' : 'false' }}"
                                    aria-label="Afficher ou masquer les modules de {{ $item['label'] }}">
                                <x-admin-icon name="chevron-down" size="16" />
                            </button>
                            {{-- Enveloppe animée (hauteur + fondu) : grid-template-rows 0fr ↔ 1fr dans admin-shell.css. --}}
                            <div class="as-sub-wrap">
                                <ul class="as-sub" id="as-sub-{{ $loop->index }}">
                                    <li class="as-sub-title" aria-hidden="true">{{ $item['label'] }}</li>
                                    @foreach ($children as $child)
                                        <li>
                                            <a href="{{ $child['href'] }}"
                                               class="as-sub-button"
                                               data-active="{{ $child['active'] ? 'true' : 'false' }}"
                                               @if ($child['active']) aria-current="page" @endif>
                                                <x-admin-icon :name="$child['icon']" size="16" />
                                                <span>{{ $child['label'] }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </nav>
    </aside>

    <div class="as-backdrop" data-shell-close></div>

    <div class="as-inset">
        <header class="as-topbar">
            {{-- Bureau : replie/déplie la barre latérale ; mobile : ouvre le tiroir. --}}
            <button type="button" class="as-btn as-trigger as-topbar-trigger" data-shell-toggle aria-controls="admin-shell-sidebar" aria-label="Afficher ou masquer le menu">
                <x-admin-icon name="panel-left" size="16" />
            </button>
            <span class="as-title">{{ $title }}</span>
            @if (count($breadcrumbs))
                <nav class="as-breadcrumb" aria-label="Fil d’Ariane">
                    <ol>
                        @foreach ($breadcrumbs as $label => $url)
                            <li>
                                @if ($url && ! $loop->last)
                                    <a href="{{ $url }}">{{ $label }}</a>
                                @else
                                    <span @if ($loop->last) aria-current="page" @endif>{{ $label }}</span>
                                @endif
                            </li>
                            @unless ($loop->last)
                                <li aria-hidden="true"><x-admin-icon name="chevron-right" size="14" /></li>
                            @endunless
                        @endforeach
                    </ol>
                </nav>
            @endif

            @if ($adminUser)
                <div class="as-user" data-shell-user>
                    <button type="button" class="as-btn as-user-trigger" data-shell-user-toggle aria-haspopup="menu" aria-expanded="false">
                        <span class="as-avatar">
                            @if ($avatar)
                                <img src="{{ $avatar }}" alt="{{ $adminUser->nom_affichage }}" onerror="this.remove()">
                            @endif
                            {{ $initiales }}
                        </span>
                        <span class="as-user-text">
                            <span class="as-user-name">{{ $adminUser->nom_affichage }}</span>
                            @if ($adminUser->role?->name)
                                <span class="as-user-role">{{ $adminUser->role->name }}</span>
                            @endif
                        </span>
                        <x-admin-icon name="chevron-down" class="as-user-chevron" />
                    </button>
                    <div class="as-dropdown" role="menu" hidden>
                        <div class="as-dropdown-label">
                            <span class="as-user-name">{{ $adminUser->nom_affichage }}</span>
                            <span class="as-user-role">{{ $adminUser->email }}</span>
                        </div>
                        <div class="as-dropdown-sep"></div>
                        <a href="{{ route('admin.profile.show') }}" class="as-dropdown-item" role="menuitem">
                            <x-admin-icon name="user-round" />
                            Mon profil
                        </a>
                        {{-- Déconnexion en POST classique (route admin.logout inchangée). --}}
                        <form method="POST" action="{{ route('admin.logout') }}" style="margin: 0">
                            @csrf
                            <button type="submit" class="as-btn as-dropdown-item as-danger" role="menuitem">
                                <x-admin-icon name="log-out" />
                                Déconnexion
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </header>

        <main class="as-main">
            {{ $slot }}
        </main>

        {{ $footer ?? '' }}
    </div>
</div>
<div class="as-tooltip" role="tooltip" hidden></div>
