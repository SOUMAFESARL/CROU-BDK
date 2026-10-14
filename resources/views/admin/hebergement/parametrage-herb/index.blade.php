@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'parametrage-herb'
])

@section('title', 'Paramétrage du module hébergement')

@section('content')
    <div class="page-body">
        <x-breadcrumbs
                title="Paramétrage du module hébergement"
                :items="[
                    'Gestion Hébergement' => route('admin.hebergement.index'),
                    'Paramétrage du module hébergement' => null,
                ]"
        />

        <div class="pt-2">
            <!-- Section Parc immobilier -->
            <div class="section-container">
                <h2 class="section-title">Parc immobilier</h2>
                <div class="cards-container">
                    <!-- Card Type cité -->
                    @adminroute('admin.hebergement.parametrage-herb.type-cite.index')
                    <a href="{{ route('admin.hebergement.parametrage-herb.type-cite.index') }}"
                       class="card-select card-option primary">
                        <div class="icone-stats-card">
                            <i class="fas fa-city"></i>
                        </div>
                        <div class="card-select-content">
                            <div class="titre-card-select">
                                Type de cité
                            </div>
                        </div>
                    </a>
                    @endadminroute

                    <!-- Card Equipement disponible -->
                    @adminroute('admin.hebergement.parametrage-herb.type-equipement.index')
                    <a href="{{ route('admin.hebergement.parametrage-herb.type-equipement.index') }}"
                       class="card-select card-option primary">
                        <div class="icone-stats-card">
                            <i class="fas fa-couch"></i>
                        </div>
                        <div class="card-select-content">
                            <div class="titre-card-select">
                                Types d'équipements
                            </div>
                        </div>
                    </a>
                    @endadminroute

                    <!-- Card Type Chambre -->
                    @adminroute('admin.hebergement.parametrage-herb.type-chambre.index')
                    <a href="{{ route('admin.hebergement.parametrage-herb.type-chambre.index') }}"
                       class="card-select card-option primary">
                        <div class="icone-stats-card">
                            <i class="fas fa-bed"></i>
                        </div>
                        <div class="card-select-content">
                            <div class="titre-card-select">
                                Type de Chambre
                            </div>
                        </div>
                    </a>
                    @endadminroute
                </div>
            </div>

            <!-- Section Campagnes & Admissions -->
            <div class="section-container">
                <h2 class="section-title">Campagnes & Admissions</h2>
                <div class="cards-container">
                    @adminroute('admin.hebergement.parametrage-herb.quotas-campagne.index')
                    <a href="{{ route('admin.hebergement.parametrage-herb.quotas-campagne.index') }}"
                       class="card-select card-option primary">
                        <div class="icone-stats-card">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <div class="card-select-content">
                            <div class="titre-card-select">
                                Paramétrage des quotas
                            </div>
                        </div>
                    </a>
                    @endadminroute

                    @adminroute('admin.hebergement.parametrage-herb.batiments-campagne.index')
                    <a href="{{ route('admin.hebergement.parametrage-herb.batiments-campagne.index') }}"
                       class="card-select card-option primary">
                        <div class="icone-stats-card">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="card-select-content">
                            <div class="titre-card-select">
                                Configuration des Bâtiments
                            </div>
                        </div>
                    </a>
                    @endadminroute

                    @adminroute('admin.hebergement.parametrage-herb.regles-campagne.index')
                    <a href="{{ route('admin.hebergement.parametrage-herb.regles-campagne.index') }}"
                       class="card-select card-option primary">
                        <div class="icone-stats-card">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="card-select-content">
                            <div class="titre-card-select">
                                Règles d'éligibilité
                            </div>
                        </div>
                    </a>
                    @endadminroute

                    @adminroute('admin.hebergement.parametrage-herb.scoring-campagne.index')
                    <a href="{{ route('admin.hebergement.parametrage-herb.scoring-campagne.index') }}"
                       class="card-select card-option primary">
                        <div class="icone-stats-card">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="card-select-content">
                            <div class="titre-card-select">
                                Scoring
                            </div>
                        </div>
                    </a>
                    @endadminroute
                </div>
            </div>

            <!-- Section Documents -->
            <div class="section-container">
                <h2 class="section-title">Documents</h2>
                <div class="cards-container">
                    @adminroute('admin.hebergement.parametrage-herb.document.index')
                    <a href="{{ route('admin.hebergement.parametrage-herb.document.index') }}"
                       class="card-select card-option primary">
                        <div class="icone-stats-card">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="card-select-content">
                            <div class="titre-card-select">
                                Paramétrage des Documents
                            </div>
                        </div>
                    </a>
                    @endadminroute
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .section-container {
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
        }

        .card-select {
            border-radius: 12px;
            padding: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            text-align: center;
            background: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            height: 100%;
            min-height: 140px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-decoration: none;
            color: inherit;
        }



        .card-select.primary:hover {
            border-color: #1d4ed8;
        }

        .card-select.success:hover {
            border-color: #059669;
        }

        .card-select.warning:hover {
            border-color: #d97706;
        }

        .card-select.info:hover {
            border-color: #0369a1;
        }

        .card-select.secondary:hover {
            border-color: #4b5563;
        }

        .card-select.danger:hover {
            border-color: #dc2626;
        }

        .card-select.dark:hover {
            border-color: #111827;
        }

        .card-select.light:hover {
            border-color: #9ca3af;
        }

        .card-select.primary .icone-stats-card {
            background-color: #dbeafe;
            color: #1d4ed8;
        }

        .card-select.success .icone-stats-card {
            background-color: #d1fae5;
            color: #059669;
        }

        .card-select.warning .icone-stats-card {
            background-color: #fef3c7;
            color: #d97706;
        }

        .card-select.info .icone-stats-card {
            background-color: #e0f2fe;
            color: #0369a1;
        }

        .card-select.secondary .icone-stats-card {
            background-color: #e5e7eb;
            color: #4b5563;
        }

        .card-select.danger .icone-stats-card {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .card-select.dark .icone-stats-card {
            background-color: #d1d5db;
            color: #111827;
        }

        .card-select.light .icone-stats-card {
            background-color: #f9fafb;
            color: #6b7280;
            border: 1px solid #e5e7eb;
        }

        .icone-stats-card {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 24px;
            transition: all 0.3s ease;
        }



        .titre-card-select {
            font-size: 16px;
            font-weight: 600;
            color: #374151;
            text-align: center;
            line-height: 1.3;
            margin-bottom: 5px;
        }
    </style>
@endpush