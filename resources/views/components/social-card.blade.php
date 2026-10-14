@props([
    'color' => 'blue',
    'icon' => 'feather icon-mail',
    'title' => 'Mail',
    'value' => '',
    'description' => '',
    'iconSize' => 'f-34',
    'badge' => null,
    'badgeColor' => 'warning',
    'iconBorder' => true,
    'borderWidth' => '2px',
    'iconBg' => 'transparent',
    'iconShape' => 'circle',
    'containerId' => null,
    'pulse' => false,
    'doubleBorder' => false
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
@endphp

<div class="card social-card bg-white" {{ $containerId ? 'id="' . $containerId . '"' : '' }}>
    <div class="card-block">
        <div class="row align-items-center g-3">
            <!-- Icon Column -->
            <div class="col-auto">
                <div class="{{ implode(' ', array_filter($wrapperClasses)) }}"
                        @style([$wrapperStyle => $iconBorder])>
                    <i class="{{ $icon }} {{ $iconSize }} {{ $iconColorClass }} social-icon"></i>
                </div>
            </div>

            <!-- Content Column -->
            <div class="col">
                <!-- Title with optional badge -->
                <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                    <h6 class="mb-0 text-uppercase fw-bold text-muted">
                        <strong>{{ $title }}</strong>
                    </h6>
                    @if($badge)
                        <span class="{{ $badgeClass }}">{{ $badge }}</span>
                    @endif
                </div>

                <!-- Value -->
                @if($value)
                    <h5 class="mb-2 fw-bold {{ $iconColorClass }}">{{ $value }}</h5>
                @endif

                <!-- Description -->
                @if($description)
                    <p class="mb-0 text-muted">{{ $description }}</p>
                @endif

                <!-- Additional Slot Content -->
                @isset($additional)
                    <div class="mt-3">
                        {{ $additional }}
                    </div>
                @endisset
            </div>
        </div>
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



        /* Couleurs de texte pour l'icône */
        .text-blue { color: #1e88e5 !important; }
        .text-pink { color: #e91e63 !important; }
        .text-green { color: #4caf50 !important; }
        .text-yellow { color: #ffb300 !important; }
        .text-purple { color: #9c27b0 !important; }
        .text-orange { color: #f57c00 !important; }
        .text-red { color: #e53935 !important; }
        .text-cyan { color: #00acc1 !important; }
        .text-teal { color: #009688 !important; }
        .text-indigo { color: #3f51b5 !important; }
        .text-brown { color: #795548 !important; }
        .text-gray { color: #9e9e9e !important; }
        .text-black { color: #000000 !important; }
        .text-white { color: #ffffff !important; }

        /* Couleurs de bordure */
        .border-blue { border-color: #1e88e5 !important; }
        .border-pink { border-color: #e91e63 !important; }
        .border-green { border-color: #4caf50 !important; }
        .border-yellow { border-color: #ffb300 !important; }
        .border-purple { border-color: #9c27b0 !important; }
        .border-orange { border-color: #f57c00 !important; }
        .border-red { border-color: #e53935 !important; }
        .border-cyan { border-color: #00acc1 !important; }
        .border-teal { border-color: #009688 !important; }
        .border-indigo { border-color: #3f51b5 !important; }
        .border-brown { border-color: #795548 !important; }
        .border-gray { border-color: #9e9e9e !important; }
        .border-black { border-color: #000000 !important; }
        .border-white { border-color: #ffffff !important; }

        /* Couleurs de fond pour l'icône (version légère) */
        .bg-blue-light { background-color: rgba(30, 136, 229, 0.1) !important; }
        .bg-pink-light { background-color: rgba(233, 30, 99, 0.1) !important; }
        .bg-green-light { background-color: rgba(76, 175, 80, 0.1) !important; }
        .bg-yellow-light { background-color: rgba(255, 179, 0, 0.1) !important; }
        .bg-purple-light { background-color: rgba(156, 39, 176, 0.1) !important; }
        .bg-orange-light { background-color: rgba(245, 124, 0, 0.1) !important; }
        .bg-red-light { background-color: rgba(229, 57, 53, 0.1) !important; }
        .bg-cyan-light { background-color: rgba(0, 172, 193, 0.1) !important; }
        .bg-teal-light { background-color: rgba(0, 150, 136, 0.1) !important; }
        .bg-indigo-light { background-color: rgba(63, 81, 181, 0.1) !important; }
        .bg-brown-light { background-color: rgba(121, 85, 72, 0.1) !important; }
        .bg-gray-light { background-color: rgba(158, 158, 158, 0.1) !important; }

        /* Couleurs de fond pleines pour l'icône */
        .bg-blue { background-color: #1e88e5 !important; }
        .bg-pink { background-color: #e91e63 !important; }
        .bg-green { background-color: #4caf50 !important; }
        .bg-yellow { background-color: #ffb300 !important; }
        .bg-purple { background-color: #9c27b0 !important; }
        .bg-orange { background-color: #f57c00 !important; }
        .bg-red { background-color: #e53935 !important; }
        .bg-cyan { background-color: #00acc1 !important; }
        .bg-teal { background-color: #009688 !important; }
        .bg-indigo { background-color: #3f51b5 !important; }
        .bg-brown { background-color: #795548 !important; }
        .bg-gray { background-color: #9e9e9e !important; }
        .bg-black { background-color: #000000 !important; }
        .bg-white { background-color: #ffffff !important; }

        /* Style pour fond coloré avec texte blanc */
        .icon-wrapper.bg-blue .social-icon,
        .icon-wrapper.bg-pink .social-icon,
        .icon-wrapper.bg-green .social-icon,
        .icon-wrapper.bg-yellow .social-icon,
        .icon-wrapper.bg-purple .social-icon,
        .icon-wrapper.bg-orange .social-icon,
        .icon-wrapper.bg-red .social-icon,
        .icon-wrapper.bg-cyan .social-icon,
        .icon-wrapper.bg-teal .social-icon,
        .icon-wrapper.bg-indigo .social-icon,
        .icon-wrapper.bg-brown .social-icon,
        .icon-wrapper.bg-gray .social-icon,
        .icon-wrapper.bg-black .social-icon {
            color: white !important;
        }



      


        /* Style avec double bordure */
        .icon-wrapper.double-border {
            position: relative;
        }

        .icon-wrapper.double-border::before {
            content: '';
            position: absolute;
            top: -4px;
            left: -4px;
            right: -4px;
            bottom: -4px;
            border: 2px solid;
            border-radius: inherit;
            opacity: 0.3;
            z-index: -1;
        }

        .icon-wrapper.double-border.border-blue::before { border-color: #1e88e5; }
        .icon-wrapper.double-border.border-pink::before { border-color: #e91e63; }
        .icon-wrapper.double-border.border-green::before { border-color: #4caf50; }

        /* Responsive */
        @media (max-width: 768px) {
            .social-card {
                border-radius: 15px;
            }

            .social-card .icon-wrapper {
                width: 60px;
                height: 60px;
            }

            .social-card .icon-wrapper.has-border {
                border-width: 1.5px;
            }
        }
    </style>
@endPushOnce