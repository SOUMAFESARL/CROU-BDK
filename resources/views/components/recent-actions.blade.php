{{-- resources/views/components/recent-actions.blade.php --}}
@props([
    'items' => [],
    'maxItems' => 5,
    'title' => 'Actions récentes',
    'icon' => 'feather icon-clock',
    'color' => 'blue',
    'iconSize' => 'f-24',
    'iconBorder' => true,
    'borderWidth' => '2px',
    'iconBg' => 'light',
    'iconShape' => 'circle',
    'containerId' => null,
    'pulse' => false,
    'doubleBorder' => false,
    'badge' => null,
    'badgeColor' => 'warning',
    'showEmpty' => true,
    'emptyMessage' => 'Aucune activité récente',
    'timeFormat' => 'diff', // diff, datetime
    'customDateFormat' => 'd/m/Y H:i',
    'showTimeAgo' => true,
])

@php
    // Classes pour l'icône
    $iconColorClass = 'text-' . $color;
    $borderColorClass = 'border-' . $color;
    $badgeClass = $badge ? 'badge bg-' . $badgeColor : '';

    // Classes pour la forme de l'icône
    $iconShapeClass = match($iconShape) {
        'square' => 'rounded-0',
        'rounded' => 'rounded-3',
        default => 'rounded-circle'
    };

    // Classe pour le fond de l'icône
    $iconBgClass = match($iconBg) {
        'light' => 'bg-' . $color . '-light',
        'colored' => 'bg-' . $color,
        default => 'bg-transparent'
    };

    // Classes additionnelles pour l'icon-wrapper
    $wrapperClasses = [
        'icon-wrapper',
        $iconShapeClass,
        $iconBgClass,
        $borderColorClass,
        'p-3',
        $iconBorder ? 'has-border' : '',
        $pulse ? 'pulse' : '',
        $doubleBorder ? 'double-border' : ''
    ];

    // Style inline pour la bordure
    $wrapperStyle = $iconBorder ? 'border-width: ' . $borderWidth . '; border-style: solid;' : '';

    // Tronquer la liste si nécessaire
    $displayItems = array_slice($items, 0, $maxItems);

    // Fonction pour formater le temps
    $formatTime = function($datetime) use ($timeFormat, $customDateFormat, $showTimeAgo) {
        if (!$datetime) return '';

        $carbon = \Carbon\Carbon::parse($datetime);

        if ($timeFormat === 'diff' && $showTimeAgo) {
            return $carbon->diffForHumans();
        } else {
            return $carbon->format($customDateFormat);
        }
    };

    // Fonction pour obtenir la couleur selon le type
    $getItemColor = function($type) {
        return match($type) {
            'success' => 'green',
            'warning' => 'orange',
            'danger' => 'red',
            'info' => 'blue',
            default => 'gray'
        };
    };

    // Fonction pour obtenir l'icône par défaut selon le type
    $getItemIcon = function($type) {
        return match($type) {
            'success' => 'feather icon-check-circle',
            'warning' => 'feather icon-alert-triangle',
            'danger' => 'feather icon-x-circle',
            'info' => 'feather icon-info',
            default => 'feather icon-clock'
        };
    };
@endphp

<div class="card social-card bg-white col-md-6" {{ $containerId ? 'id="' . $containerId . '"' : '' }}>
    <div class="card-block">
        <!-- En-tête avec la même structure que social-card -->
        <div class="row align-items-center g-3 mb-3">


            <!-- Title Column -->
            <div class="col">
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <h6 class="mb-0 text-uppercase fw-bold text-muted">
                        <strong>{{ $title }}</strong>
                    </h6>
                    @if($badge)
                        <span class="{{ $badgeClass }}">{{ $badge }}</span>
                    @endif
                </div>
                @if($showEmpty && empty($displayItems))
                    <p class="mb-0 text-muted small mt-1">{{ $emptyMessage }}</p>
                @endif
            </div>
        </div>

        <!-- Liste des actions récentes -->
        @if(!empty($displayItems))
            <div class="recent-actions-list mt-3">
                @foreach($displayItems as $item)
                    @php
                        $itemColor = $getItemColor($item['type'] ?? 'default');
                        $itemIcon = $item['icon'] ?? $getItemIcon($item['type'] ?? 'default');
                    @endphp
                    <div class="recent-action-item d-flex align-items-start gap-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }} border-light">
                        <!-- Mini icône pour chaque action -->
                        <div class="action-mini-icon-wrapper mt-1">
                            <div class="icon-wrapper-small bg-{{ $itemColor }}-light rounded-circle p-2 d-flex align-items-center justify-content-center">
                                <i class="{{ $itemIcon }} f-14 text-{{ $itemColor }}"></i>
                            </div>
                        </div>

                        <!-- Contenu de l'action -->
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <h6 class="mb-0 fw-semibold small">{{ $item['title'] ?? 'Action' }}</h6>
                                    @if(isset($item['description']))
                                        <p class="mb-0 text-muted small">{{ $item['description'] }}</p>
                                    @endif
                                </div>
                                @if(isset($item['badge']))
                                    <span class="badge bg-{{ $item['badge']['color'] ?? $itemColor }} rounded-pill px-2 py-1"
                                          style="font-size: var(--app-font-min, 15px);">
                                        {{ $item['badge']['text'] ?? '' }}
                                    </span>
                                @endif
                            </div>

                            <!-- Métadonnées -->
                            <div class="d-flex align-items-center gap-3 mt-1">
                                @if(isset($item['datetime']))
                                    <small class="text-muted d-flex align-items-center gap-1">
                                        <i class="feather icon-clock f-10"></i>
                                        {{ $formatTime($item['datetime']) }}
                                    </small>
                                @endif

                                @if(isset($item['user']))
                                    <small class="text-muted d-flex align-items-center gap-1">
                                        <i class="feather icon-user f-10"></i>
                                        {{ $item['user']['name'] ?? 'Système' }}
                                    </small>
                                @endif

                                @if(isset($item['source']))
                                    <small class="text-muted d-flex align-items-center gap-1">
                                        <i class="feather icon-map-pin f-10"></i>
                                        {{ $item['source'] }}
                                    </small>
                                @endif
                            </div>
                        </div>

                        <!-- Statut optionnel -->
                        @if(isset($item['status']))
                            <div class="action-status">
                                @if($item['status'] === 'success')
                                    <i class="feather icon-check-circle text-success f-16"></i>
                                @elseif($item['status'] === 'failed')
                                    <i class="feather icon-x-circle text-danger f-16"></i>
                                @elseif($item['status'] === 'pending')
                                    <i class="feather icon-clock text-warning f-16"></i>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach

                <!-- Footer avec lien "Voir tout" si nécessaire -->
                @if(count($items) > $maxItems)
                    <div class="text-center mt-3">
                        <a href="#" class="text-{{ $color }} text-decoration-none small fw-semibold">
                            Voir toutes les activités <i class="feather icon-arrow-right ms-1 f-12"></i>
                        </a>
                    </div>
                @endif
            </div>
        @endif

        <!-- Slot pour contenu supplémentaire -->
        @isset($additional)
            <div class="mt-3">
                {{ $additional }}
            </div>
        @endisset
    </div>

    <!-- Footer Slot -->
    @isset($footer)
        <div class="card-footer bg-transparent border-top-0">
            {{ $footer }}
        </div>
    @endisset
</div>

@pushOnce('styles')
    <style>
        .social-card {
            position: relative;
            transition: all 0.3s ease;
            overflow: hidden;
            border-radius: 20px;
            border: 1px solid #f0f0f0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            background: white !important;
            min-width: 300px;
        }

        .social-card .icon-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 70px;
            height: 70px;
            transition: all 0.3s ease;
        }

        .social-card .icon-wrapper.has-border {
            border-width: 2px;
            border-style: solid;
        }

        .social-card .social-icon {
            transition: transform 0.3s ease;
        }

        /* Mini icônes pour les actions */
        .icon-wrapper-small {
            width: 32px;
            height: 32px;
            transition: all 0.3s ease;
        }

        .icon-wrapper-small i {
            transition: transform 0.2s ease;
        }

        .recent-action-item:hover .icon-wrapper-small i {
            transform: scale(1.1);
        }

        /* Style des items d'action */
        .recent-action-item {
            transition: all 0.2s ease;
            padding: 8px 4px;
            border-radius: 8px;
        }

        .recent-action-item:hover {
            background-color: rgba(0, 0, 0, 0.02);
            transform: translateX(2px);
        }

        .recent-action-item.border-bottom {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05) !important;
        }

        /* Badges */
        .badge {
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        /* Animation de pulsation pour les nouveaux éléments */
        @keyframes pulse-item {
            0% { background-color: rgba(30, 136, 229, 0.05); }
            50% { background-color: rgba(30, 136, 229, 0.1); }
            100% { background-color: transparent; }
        }

        .recent-action-item.new {
            animation: pulse-item 2s ease;
        }

        /* Couleurs pour les fonds d'icônes */
        .bg-blue-light { background-color: rgba(30, 136, 229, 0.1) !important; }
        .bg-green-light { background-color: rgba(76, 175, 80, 0.1) !important; }
        .bg-orange-light { background-color: rgba(245, 124, 0, 0.1) !important; }
        .bg-red-light { background-color: rgba(229, 57, 53, 0.1) !important; }
        .bg-gray-light { background-color: rgba(158, 158, 158, 0.1) !important; }

        /* Texte coloré */
        .text-blue { color: #1e88e5 !important; }
        .text-green { color: #4caf50 !important; }
        .text-orange { color: #f57c00 !important; }
        .text-red { color: #e53935 !important; }
        .text-gray { color: #757575 !important; }

        /* Liens */
        .text-decoration-none:hover {
            text-decoration: underline !important;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .social-card {
                border-radius: 15px;
            }

            .social-card .icon-wrapper {
                width: 60px;
                height: 60px;
            }

            .icon-wrapper-small {
                width: 28px;
                height: 28px;
            }

            .recent-action-item {
                padding: 6px 2px;
            }
        }


    </style>
@endPushOnce