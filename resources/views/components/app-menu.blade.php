@props([
    'activeMenu' => null,
    'activeSubMenu' => null,
])

@php
    // Normaliser - toujours traiter comme chaîne
    $menu = (string) $activeMenu;
    $subMenu = (string) $activeSubMenu;
    $hasSubMenu = !empty(trim($subMenu));
@endphp

<div class="flex h-full">
    <!-- ======= SIDEBAR PRINCIPALE ======= -->
    <aside
            class="{{ $hasSubMenu ? 'w-20 items-center' : 'w-80 px-4' }}
               bg-white border-r flex flex-col py-4 space-y-2 transition-all duration-300"
    >
        <!-- Tableau de bord -->
        @adminroute('admin.dashboard.index')
        <a href="{{ route('admin.dashboard.index') }}"
           title="{{ __('ui.dashboard') }}"
           class="flex items-center gap-3 py-2 px-3 rounded-lg text-gray-600 hover:text-yellow-600 hover:bg-gray-50 transition
                  {{ $menu === 'dashboard' ? 'text-yellow-600 font-semibold bg-gray-100' : '' }}">
            <i class="fa-solid fa-table-columns text-xl"></i>
            @unless($hasSubMenu)
                <span>{{ __('ui.dashboard') }}</span>
            @endunless
        </a>
        @endadminroute

        <!-- Utilisateurs -->
        @adminroute('admin.users.index')
        <a href="{{ route('admin.users.index') }}"
           title="{{ __('ui.users') }}"
           class="flex items-center gap-3 py-2 px-3 rounded-lg text-gray-600 hover:text-yellow-600 hover:bg-gray-50 transition
                  {{ $menu === 'users' ? 'text-yellow-600 font-semibold bg-gray-100' : '' }}">
            <i class="fa-solid fa-users text-xl"></i>
            @unless($hasSubMenu)
                <span>{{ __('ui.users') }}</span>
            @endunless
        </a>
        @endadminroute

        <!-- Étudiants -->
{{--        <a href="{{ route('admin.etudiant.index') }}"--}}
{{--           title="Étudiants"--}}
{{--           class="flex items-center gap-3 py-2 px-3 rounded-lg text-gray-600 hover:text-yellow-600 hover:bg-gray-50 transition--}}
{{--                  {{ $menu === 'etudiants' ? 'text-yellow-600 font-semibold bg-gray-100' : '' }}">--}}
{{--            <i class="fa-solid fa-user-graduate text-xl"></i>--}}
{{--            @unless($hasSubMenu)--}}
{{--                <span>Étudiants</span>--}}
{{--            @endunless--}}
{{--        </a>--}}

        <!-- Gestion Hébergement -->
        @adminroute('admin.hebergement.index')
        <a href="{{ route('admin.hebergement.index') }}"
           title="{{ __('ui.housing') }}"
           class="flex items-center gap-3 py-2 px-3 rounded-lg text-gray-600 hover:text-yellow-600 hover:bg-gray-50 transition
                  {{ $menu === 'hebergement' ? 'text-yellow-600 font-semibold bg-gray-100' : '' }}">
            <i class="fa-solid fa-building text-xl"></i>
            @unless($hasSubMenu)
                <span>{{ __('ui.housing') }}</span>
            @endunless
        </a>
        @endadminroute

        <!-- Paramètres -->
        @adminroute('admin.activity-logs.index')
        <a href="{{ route('admin.activity-logs.index') }}"
           title="{{ __('ui.activity') }}"
           class="flex items-center gap-3 py-2 px-3 rounded-lg text-gray-600 hover:text-yellow-600 hover:bg-gray-50 transition
                  {{ $menu === 'activity' ? 'text-yellow-600 font-semibold bg-gray-100' : '' }}">
            <i class="fa-solid fa-clock-rotate-left text-xl"></i>
            @unless($hasSubMenu)<span>{{ __('ui.activity') }}</span>@endunless
        </a>
        @endadminroute

        <!-- Paramètres -->
        @adminroute('admin.parametrage.index')
        <a href="{{ route('admin.parametrage.index') }}"
           title="{{ __('ui.settings') }}"
           class="flex items-center gap-3 py-2 px-3 rounded-lg text-gray-600 hover:text-yellow-600 hover:bg-gray-50 transition
                  {{ $menu === 'parametres' ? 'text-yellow-600 font-semibold bg-gray-100' : '' }}">
            <i class="fa-solid fa-gear text-xl"></i>
            @unless($hasSubMenu)
                <span>{{ __('ui.settings') }}</span>
            @endunless
        </a>
        @endadminroute

{{--        <a href="{{ route('admin.welcome') }}"--}}
{{--           title="API"--}}
{{--           class="flex items-center gap-3 py-2 px-3 rounded-lg text-gray-600 hover:text-yellow-600 hover:bg-gray-50 transition--}}
{{--                  {{ $menu === 'API' ? 'text-yellow-600 font-semibold bg-gray-100' : '' }}">--}}
{{--            <i class="fa-solid fa-gear text-xl"></i>--}}
{{--            @unless($hasSubMenu)--}}
{{--                <span>API</span>--}}
{{--            @endunless--}}
{{--        </a>--}}
    </aside>

    <!-- ======= SÉPARATEUR & SOUS-MENU SECONDAIRE ======= -->
    @if ($hasSubMenu)
        <div class="w-[1px] bg-gray-200"></div>

        <aside class="w-80 bg-white border-r py-6 px-4 overflow-y-auto transition-all duration-300 mb-5">
            @if ($menu === 'hebergement')
                <h3 class="text-gray-800 font-semibold mb-4 text-sm uppercase">{{ __('ui.housing') }}</h3>
                <ul class="space-y-1 text-sm">
                      <li class="{{ $subMenu === 'parc-immobilier' ? 'text-yellow-600 font-semibold bg-gray-100 rounded' : 'text-gray-600' }}">
                        @adminroute('admin.hebergement.parc-immobilier.index')
                        <a href="{{ route('admin.hebergement.parc-immobilier.index') }}"
                           class="flex items-center gap-2 px-2 py-2 rounded hover:text-yellow-600 hover:bg-gray-50 transition">
                            <i class="fa-solid fa-building"></i> <span>{{ __('ui.real_estate') }}</span>
                        </a>
                        @endadminroute

                    </li>
                    @adminroute('admin.allocation.index')
                 {{--   <li><a href="{{ route('admin.allocation.index') }}" class="flex items-center gap-2 px-2 py-2"><i class="fa-solid fa-bed"></i><span>Attribution des chambres</span></a></li> --}}
                    @endadminroute
                    @adminroute('admin.hebergement.maintenance.index')
                 {{----   <li><a href="{{ route('admin.hebergement.maintenance.index') }}" class="flex items-center gap-2 px-2 py-2"><i class="fa-solid fa-screwdriver-wrench"></i><span>Maintenance</span></a></li> --------}}
                    @endadminroute
                    @adminroute('admin.hebergement.annonce.index')
                    <li><a href="{{ route('admin.hebergement.annonce.index') }}" class="flex items-center gap-2 px-2 py-2"><i class="fa-solid fa-bullhorn"></i><span>Annonces</span></a></li>
                    @endadminroute
                    @adminroute('admin.hebergement.vie_residentielle.incidents.index')
                  {{-- --  <li><a href="{{ route('admin.hebergement.vie_residentielle.incidents.index') }}" class="flex items-center gap-2 px-2 py-2"><i class="fa-solid fa-shield-halved"></i><span>Discipline</span></a></li> --}}
                    @endadminroute
                    <li>@adminroute('admin.housing-entry.settings')
<a href="{{ route('admin.housing-entry.settings') }}" class="flex items-center gap-2 px-2 py-2"><i class="fa-solid fa-calendar-check"></i><span>Notifications, contrat et entrée</span></a>
@endadminroute</li>


                    <li class="{{ $subMenu === 'equipements' ? 'text-yellow-600 font-semibold bg-gray-100 rounded' : 'text-gray-600' }}">
                        @adminroute('admin.hebergement.equipements.index')
                        <a href="{{ route('admin.hebergement.equipements.index') }}"
                           class="flex items-center gap-2 px-2 py-2 rounded hover:text-yellow-600 hover:bg-gray-50 transition">
                            <i class="fa-solid fa-toolbox"></i> <span>{{ __('ui.equipment') }}</span>
                        </a>
                        @endadminroute

                    </li>

                    <li class="{{ $subMenu === 'campagnes' ? 'text-yellow-600 font-semibold bg-gray-100 rounded' : 'text-gray-600' }}">
                        @adminroute('admin.hebergement.campagnes_admission.admission')
                        <a href="{{ route('admin.hebergement.campagnes_admission.admission') }}"
                           class="flex items-center gap-2 px-2 py-2 rounded hover:text-yellow-600 hover:bg-gray-50 transition">
                            <i class="fa-solid fa-bullhorn"></i> <span>{{ __('ui.campaigns') }}</span>
                        </a>
                        @endadminroute

                    </li>

                    <li class="{{ $subMenu === 'residents' ? 'text-yellow-600 font-semibold bg-gray-100 rounded' : 'text-gray-600' }}">
                        @adminroute('admin.hebergement.residents_contrat.admission')
                        <a href="{{ route('admin.hebergement.residents_contrat.admission',['etat' =>'residents']) }}"
                           class="flex items-center gap-2 px-2 py-2 rounded hover:text-yellow-600 hover:bg-gray-50 transition">
                            <i class="fa-solid fa-users"></i> <span>{{ __('ui.residents') }}</span>
                        </a>
                        @endadminroute

                    </li>

                    <li class="{{ $subMenu === 'vie-cite' ? 'text-yellow-600 font-semibold bg-gray-100 rounded' : 'text-gray-600' }}">
                        @adminroute('admin.hebergement.vie_residentielle.index')
                        <a href="{{ route('admin.hebergement.vie_residentielle.index') }}"
                           class="flex items-center gap-2 px-2 py-2 rounded hover:text-yellow-600 hover:bg-gray-50 transition">
                            <i class="fa-solid fa-city"></i> <span>{{ __('ui.community_life') }}</span>
                        </a>
                        @endadminroute

                    </li>

                    <li class="{{ $subMenu === 'departs' ? 'text-yellow-600 font-semibold bg-gray-100 rounded' : 'text-gray-600' }}">
                        @adminroute('admin.hebergement.depart_etat_des_lieux.depart')
                        <a href="{{ route('admin.hebergement.depart_etat_des_lieux.depart') }}"
                           class="flex items-center gap-2 px-2 py-2 rounded hover:text-yellow-600 hover:bg-gray-50 transition">
                            <i class="fa-solid fa-door-open"></i> <span>{{ __('ui.departures') }}</span>
                        </a>
                        @endadminroute

                    </li>

                    <li class="{{ $subMenu === 'finances' ? 'text-yellow-600 font-semibold bg-gray-100 rounded' : 'text-gray-600' }}">
                        @adminroute('admin.hebergement.paiements.index')
                        <a href="{{ route('admin.hebergement.paiements.index') }}"
                           class="flex items-center gap-2 px-2 py-2 rounded hover:text-yellow-600 hover:bg-gray-50 transition">
                            <i class="fa-solid fa-money-bill-wave"></i> <span>{{ __('ui.rent_payments') }}</span>
                        </a>
                        @endadminroute

                    </li>


                    <li class="{{ $subMenu === 'reporting' ? 'text-yellow-600 font-semibold bg-gray-100 rounded' : 'text-gray-600' }}">
                        @adminroute('admin.hebergement.reporting.index')
                        <a href="{{ route('admin.hebergement.reporting.index') }}"
                           class="flex items-center gap-2 px-2 py-2 rounded hover:text-yellow-600 hover:bg-gray-50 transition">
                            <i class="fa-solid fa-chart-bar"></i> <span>{{ __('ui.reporting') }}</span>
                        </a>
                        @endadminroute
                    </li>
                    <li class="{{ $subMenu === 'parametrage-herb' ? 'text-yellow-600 font-semibold bg-gray-100 rounded' : 'text-gray-600' }}">
                        @adminroute('admin.hebergement.parametrage-herb.index')
                        <a href="{{ route('admin.hebergement.parametrage-herb.index') }}"
                           class="flex items-center gap-2 px-2 py-2 rounded hover:text-yellow-600 hover:bg-gray-50 transition">
                            <i class="fa-solid fa-tools"></i> <span>Paramétrages</span>
                        </a>
                        @endadminroute

                    </li>
                </ul>
            @elseif ($menu === 'parametres')
                <h3 class="text-gray-800 font-semibold mb-4 text-sm uppercase">{{ __('ui.settings') }}</h3>
                <ul class="space-y-1 text-sm">
                    <li class="{{ $subMenu === 'legal-documents' ? 'text-yellow-600 font-semibold bg-gray-100 rounded' : 'text-gray-600' }}">
                        @adminroute('admin.parametrage.legal-documents.index')
                        <a href="{{ route('admin.parametrage.legal-documents.index') }}" class="flex items-center gap-2 px-2 py-2 rounded hover:bg-gray-50">
                            <i class="fa-solid fa-file-signature"></i> Documents de la résidence
                        </a>
                        @endadminroute
                    </li>
                    <li class="{{ $subMenu === 'payment-providers' ? 'text-yellow-600 font-semibold bg-gray-100 rounded' : 'text-gray-600' }}">
                        @adminroute('admin.parametrage.payment-providers.index')
                        <a href="{{ route('admin.parametrage.payment-providers.index') }}"
                           class="flex items-center gap-2 px-2 py-2 rounded hover:text-yellow-600 hover:bg-gray-50 transition">
                            <i class="fa-solid fa-credit-card"></i> <span>API de paiement</span>
                        </a>
                        @endadminroute
                    </li>
                    <li class="{{ $subMenu === 'modules' ? 'text-yellow-600 font-semibold bg-gray-100 rounded' : 'text-gray-600' }}">
                        @adminroute('admin.parametrage.modules.index')
                        <a href="{{ route('admin.parametrage.modules.index') }}"
                           class="flex items-center gap-2 px-2 py-2 rounded hover:text-yellow-600 hover:bg-gray-50 transition">
                            <i class="fa-solid fa-sitemap"></i> <span>{{ __('ui.modules') }}</span>
                        </a>
                        @endadminroute
                    </li>

                    <li class="{{ $subMenu === 'roles' ? 'text-yellow-600 font-semibold bg-gray-100 rounded' : 'text-gray-600' }}">
                        @adminroute('admin.parametrage.roles-permissions.index')
                        <a href="{{ route('admin.parametrage.roles-permissions.index') }}"
                           class="flex items-center gap-2 px-2 py-2 rounded hover:text-yellow-600 hover:bg-gray-50 transition">
                            <i class="fa-solid fa-users"></i> <span>{{ __('ui.roles') }}</span>
                        </a>
                        @endadminroute
                    </li>

                    <li class="{{ $subMenu === 'pays' ? 'text-yellow-600 font-semibold bg-gray-100 rounded' : 'text-gray-600' }}">
                        @adminroute('admin.parametrage.countrie.index')
                        <a href="{{ route('admin.parametrage.countrie.index') }}"
                           class="flex items-center gap-2 px-2 py-2 rounded hover:text-yellow-600 hover:bg-gray-50 transition">
                            <i class="fa-solid fa-flag"></i> <span>{{ __('ui.countries') }}</span>
                        </a>
                        @endadminroute
                    </li>

                    <li class="{{ $subMenu === 'regions' ? 'text-yellow-600 font-semibold bg-gray-100 rounded' : 'text-gray-600' }}">
                        @adminroute('admin.parametrage.region.index')
                        <a href="{{ route('admin.parametrage.region.index') }}"
                           class="flex items-center gap-2 px-2 py-2 rounded hover:text-yellow-600 hover:bg-gray-50 transition">
                            <i class="fa-solid fa-map"></i> <span>{{ __('ui.regions') }}</span>
                        </a>
                        @endadminroute
                    </li>

                    <li class="{{ $subMenu === 'villes' ? 'text-yellow-600 font-semibold bg-gray-100 rounded' : 'text-gray-600' }}">
                        @adminroute('admin.parametrage.ville.index')
                        <a href="{{ route('admin.parametrage.ville.index') }}"
                           class="flex items-center gap-2 px-2 py-2 rounded hover:text-yellow-600 hover:bg-gray-50 transition">
                            <i class="fa-solid fa-city"></i> <span>{{ __('ui.cities') }}</span>
                        </a>
                        @endadminroute
                    </li>

                    <li class="{{ $subMenu === 'niveaux-etude' ? 'text-yellow-600 font-semibold bg-gray-100 rounded' : 'text-gray-600' }}">
                        @adminroute('admin.parametrage.niveau-etude.index')
                        <a href="{{ route('admin.parametrage.niveau-etude.index') }}"
                           class="flex items-center gap-2 px-2 py-2 rounded hover:text-yellow-600 hover:bg-gray-50 transition">
                            <i class="fa-solid fa-graduation-cap"></i> <span>{{ __('ui.education_levels') }}</span>
                        </a>
                        @endadminroute
                    </li>

                    <li class="{{ $subMenu === 'filieres' ? 'text-yellow-600 font-semibold bg-gray-100 rounded' : 'text-gray-600' }}">
                        @adminroute('admin.parametrage.filiere.index')
                        <a href="{{ route('admin.parametrage.filiere.index') }}"
                           class="flex items-center gap-2 px-2 py-2 rounded hover:text-yellow-600 hover:bg-gray-50 transition">
                            <i class="fa-solid fa-diagram-project"></i> <span>{{ __('ui.courses') }}</span>
                        </a>
                        @endadminroute
                    </li>

                    <li class="{{ $subMenu === 'departements' ? 'text-yellow-600 font-semibold bg-gray-100 rounded' : 'text-gray-600' }}">
                        @adminroute('admin.parametrage.departement.index')
                        <a href="{{ route('admin.parametrage.departement.index') }}"
                           class="flex items-center gap-2 px-2 py-2 rounded hover:text-yellow-600 hover:bg-gray-50 transition">
                            <i class="fa-solid fa-layer-group"></i> <span>{{ __('ui.departments') }}</span>
                        </a>
                        @endadminroute
                    </li>

                    <li class="{{ $subMenu === 'parametrage' ? 'text-yellow-600 font-semibold bg-gray-100 rounded' : 'text-gray-600' }}">
                        @adminroute('admin.parametrage.configuration.index')
                        <a href="{{ route('admin.parametrage.configuration.index') }}"
                           class="flex items-center gap-2 px-2 py-2 rounded hover:text-yellow-600 hover:bg-gray-50 transition">
                            <i class="fa-solid fa-user-gear"></i> <span>{{ __('ui.system_config') }}</span>
                        </a>
                        @endadminroute
                    </li>

{{--                    <li class="{{ $subMenu === 'types-chambres' ? 'text-yellow-600 font-semibold bg-gray-100 rounded' : 'text-gray-600' }}">--}}
{{--                        <a href="#"--}}
{{--                           class="flex items-center gap-2 px-2 py-2 rounded hover:text-yellow-600 hover:bg-gray-50 transition">--}}
{{--                            <i class="fa-solid fa-bed"></i> <span>Gestion des types de chambres</span>--}}
{{--                        </a>--}}
{{--                    </li>--}}

{{--                    <li class="{{ $subMenu === 'types-cites' ? 'text-yellow-600 font-semibold bg-gray-100 rounded' : 'text-gray-600' }}">--}}
{{--                        <a href="#"--}}
{{--                           class="flex items-center gap-2 px-2 py-2 rounded hover:text-yellow-600 hover:bg-gray-50 transition">--}}
{{--                            <i class="fa-solid fa-building-columns"></i> <span>Gestion des types de cités</span>--}}
{{--                        </a>--}}
{{--                    </li>--}}

{{--                    <li class="{{ $subMenu === 'types-equipements' ? 'text-yellow-600 font-semibold bg-gray-100 rounded' : 'text-gray-600' }}">--}}
{{--                        <a href="#"--}}
{{--                           class="flex items-center gap-2 px-2 py-2 rounded hover:text-yellow-600 hover:bg-gray-50 transition">--}}
{{--                            <i class="fa-solid fa-toolbox"></i> <span>Gestion des types d'équipement</span>--}}
{{--                        </a>--}}
{{--                    </li>--}}

                    <li class="{{ $subMenu === 'type-utilisateur' ? 'text-yellow-600 font-semibold bg-gray-100 rounded' : 'text-gray-600' }}">
                        @adminroute('admin.parametrage.type-utilisateur.index')
                        <a href="{{ route('admin.parametrage.type-utilisateur.index') }}"
                           class="flex items-center gap-2 px-2 py-2 rounded hover:text-yellow-600 hover:bg-gray-50 transition">
                            <i class="fa-solid fa-user-gear"></i> <span>{{ __('ui.user_types') }}</span>
                        </a>
                        @endadminroute
                    </li>
                </ul>
            @endif

        </aside>
    @endif
</div>
