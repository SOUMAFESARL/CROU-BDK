@extends('admin.layouts.app', [
    'activeMenu' => 'dashboard',
    'activeSubMenu' => null
])
@section('title', 'Dashboard - Restauration')

@section('content')
    <div class="page-body">

        {{-- MODULE SWITCHER - Onglets de navigation --}}
        <div class="row mb-4">
            <div class="col-12">
                <ul class="nav nav-tabs" style="border-bottom: 2px solid #dee2e6;">
                    {{-- Module Hébergement --}}
                    <li class="nav-item">
                        @adminroute('admin.dashboard.hebergement')
                        <a class="nav-link {{ $moduleActif == 'hebergement' ? 'active' : '' }}"
                           href="{{ route('admin.dashboard.hebergement') }}"
                           style="{{ $moduleActif == 'hebergement' ? 'font-weight: 600;' : 'color: #6c757d;' }}">
                            Hébergement
                        </a>
                        @endadminroute
                    </li>

                    {{-- Module Restauration --}}
                    <li class="nav-item">
                        @adminroute('admin.dashboard.restauration')
                        <a class="nav-link {{ $moduleActif == 'restauration' ? 'active' : '' }}"
                           href="{{ route('admin.dashboard.restauration') }}"
                           style="{{ $moduleActif == 'restauration' ? 'font-weight: 600;' : 'color: #6c757d;' }}">
                            <i class="feather icon-coffee me-2"></i>Restauration
                        </a>
                        @endadminroute
                    </li>
                </ul>
            </div>
        </div>


        {{-- STATISTIQUES PRINCIPALES (3 CASES) --}}
        <div class="row g-3 mb-4">
            {{-- CASE 1 : REPAS SERVIS AUJOURD'HUI --}}
            <div class="col-xl-4 col-md-6">
                <x-social-card
                        color="info" {{-- Bleu pour la fraîcheur --}}
                icon="feather icon-clock"
                        title="REPAS SERVIS AUJOURD'HUI"
                        value="1,245"
                        description="Dont 320 étudiants boursiers"
                        :icon-border="true"
                        border-width="3px"
                />
            </div>

            {{-- CASE 2 : FRÉQUENTATION MOYENNE --}}
            <div class="col-xl-4 col-md-6">
                <x-social-card
                        color="success"
                        icon="feather icon-trending-up"
                        title="FRÉQUENTATION MOYENNE"
                        value="72%"
                        description="Taux de remplissage / service"
                        :icon-border="true"
                        border-width="3px"
                />
            </div>

            {{-- CASE 3 : TICKETS RESTANTS --}}
            <div class="col-xl-4 col-md-6">
                <x-social-card
                        color="warning"
                        icon="feather icon-credit-card"
                        title="TICKETS / QUOTAS"
                        value="3,842"
                        description="Repas prépayés non consommés"
                        :icon-border="true"
                        border-width="3px"
                />
            </div>
        </div>

    </div>
@endsection

