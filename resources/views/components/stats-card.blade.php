@props([
    'title',
    'value',
    'subValue' => null,
    'description' => null,
    'icon' => null,
    'color' => 'blue',
    'trend' => null,
    'trendValue' => null,
    'badge' => null,
    'badgeColor' => 'primary',
    'progress' => null,
    'progressMax' => 100,
    'showProgress' => false,
    'compact' => false
])

@php
    $colorClass = 'stats-color-' . $color;
    $trendIcon = match($trend) {
        'up' => 'feather icon-arrow-up',
        'down' => 'feather icon-arrow-down',
        'neutral' => 'feather icon-minus',
        default => null
    };
    $trendColor = match($trend) {
        'up' => 'text-success',
        'down' => 'text-danger',
        'neutral' => 'text-warning',
        default => ''
    };
    $progressPercentage = $progress && $progressMax ? ($progress / $progressMax) * 100 : 0;
@endphp

<div class="stats-card {{ $compact ? 'compact' : '' }} {{ $colorClass }}">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-start">
            <h6 class="stats-title">{{ $title }}</h6>
            
            @if($badge)
            <span class="badge bg-{{ $badgeColor }}">{{ $badge }}</span>
            @endif
            
            @if($icon)
            <div class="stats-icon">
                <i class="{{ $icon }}"></i>
            </div>
            @endif
        </div>
    </div>
    
    <div class="card-body">
        <div class="stats-value-container">
            <h2 class="stats-value">{{ $value }}</h2>
            
            @if($subValue)
            <div class="stats-subvalue">{{ $subValue }}</div>
            @endif
            
            @if($trend && $trendValue)
            <div class="stats-trend {{ $trendColor }}">
                <i class="{{ $trendIcon }}"></i>
                <span>{{ $trendValue }}</span>
            </div>
            @endif
        </div>
        
        @if($description)
        <p class="stats-description">{{ $description }}</p>
        @endif
        
        @if($showProgress && $progress !== null)
        <div class="stats-progress">
            <div class="progress">
                <div class="progress-bar" 
                     role="progressbar" 
                     style="width: {{ $progressPercentage }}%"
                     aria-valuenow="{{ $progress }}"
                     aria-valuemin="0" 
                     aria-valuemax="{{ $progressMax }}">
                </div>
            </div>
            @if($progressMax)
            <div class="progress-label">
                <span>{{ $progress }}/{{ $progressMax }}</span>
                <span>{{ number_format($progressPercentage, 1) }}%</span>
            </div>
            @endif
        </div>
        @endif
    </div>
    
    @if(isset($footer))
    <div class="card-footer">
        {{ $footer }}
    </div>
    @endif
</div>

@pushOnce('styles')
<style>
    .stats-card {
        background: white;
        border-radius: 16px;
        border: 1px solid #eef2f7;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        transition: all 0.3s ease;
        overflow: hidden;
        height: 100%;
    }
    
    .stats-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        border-color: #dce4ef;
    }
    
    .stats-card.compact {
        padding: 1rem;
    }
    
    .stats-card .card-header {
        background: transparent;
        border-bottom: 1px solid #f0f4f8;
        padding: 1.25rem 1.5rem 0.75rem;
    }
    
    .stats-card .card-body {
        padding: 1.5rem;
    }
    
    .stats-card .card-footer {
        background: #f8fafc;
        border-top: 1px solid #f0f4f8;
        padding: 1rem 1.5rem;
    }
    
    .stats-title {
        font-size: var(--app-font-min, 15px);
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0;
    }
    
    .stats-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }
    
    .stats-value-container {
        margin-bottom: 1rem;
        position: relative;
    }
    
    .stats-value {
        font-size: 2.5rem;
        font-weight: 700;
        color: #1e293b;
        line-height: 1;
        margin: 0 0 0.5rem 0;
    }
    
    .stats-subvalue {
        font-size: 1rem;
        color: #64748b;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }
    
    .stats-trend {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-size: var(--app-font-min, 15px);
        font-weight: 600;
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
        background: rgba(var(--color-rgb), 0.1);
    }
    
    .stats-trend.text-success {
        background: rgba(34, 197, 94, 0.1);
        color: #16a34a;
    }
    
    .stats-trend.text-danger {
        background: rgba(239, 68, 68, 0.1);
        color: #dc2626;
    }
    
    .stats-trend.text-warning {
        background: rgba(245, 158, 11, 0.1);
        color: #d97706;
    }
    
    .stats-description {
        font-size: var(--app-font-min, 15px);
        color: #64748b;
        line-height: 1.5;
        margin: 0;
    }
    
    .stats-progress {
        margin-top: 1.5rem;
    }
    
    .stats-progress .progress {
        height: 6px;
        border-radius: 3px;
        background-color: #e2e8f0;
        overflow: hidden;
        margin-bottom: 0.5rem;
    }
    
    .stats-progress .progress-bar {
        background-color: currentColor;
        border-radius: 3px;
        transition: width 0.6s ease;
    }
    
    .progress-label {
        display: flex;
        justify-content: space-between;
        font-size: var(--app-font-min, 15px);
        color: #64748b;
    }
    
    /* Couleurs thématiques */
    .stats-color-blue .stats-icon,
    .stats-color-blue .progress-bar {
        color: #3b82f6;
        background-color: #3b82f6;
    }
    
    .stats-color-blue .stats-trend {
        --color-rgb: 59, 130, 246;
    }
    
    .stats-color-green .stats-icon,
    .stats-color-green .progress-bar {
        color: #10b981;
        background-color: #10b981;
    }
    
    .stats-color-green .stats-trend {
        --color-rgb: 16, 185, 129;
    }
    
    .stats-color-purple .stats-icon,
    .stats-color-purple .progress-bar {
        color: #8b5cf6;
        background-color: #8b5cf6;
    }
    
    .stats-color-purple .stats-trend {
        --color-rgb: 139, 92, 246;
    }
    
    .stats-color-orange .stats-icon,
    .stats-color-orange .progress-bar {
        color: #f59e0b;
        background-color: #f59e0b;
    }
    
    .stats-color-orange .stats-trend {
        --color-rgb: 245, 158, 11;
    }
    
    .stats-color-red .stats-icon,
    .stats-color-red .progress-bar {
        color: #ef4444;
        background-color: #ef4444;
    }
    
    .stats-color-red .stats-trend {
        --color-rgb: 239, 68, 68;
    }
    
    .stats-color-cyan .stats-icon,
    .stats-color-cyan .progress-bar {
        color: #06b6d4;
        background-color: #06b6d4;
    }
    
    .stats-color-cyan .stats-trend {
        --color-rgb: 6, 182, 212;
    }
    
    /* Version avec accent coloré sur le côté */
    .stats-card.with-side-accent {
        border-left: 4px solid;
    }
    
    .stats-color-blue.with-side-accent {
        border-left-color: #3b82f6;
    }
    
    .stats-color-green.with-side-accent {
        border-left-color: #10b981;
    }
    
    .stats-color-purple.with-side-accent {
        border-left-color: #8b5cf6;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .stats-card {
            border-radius: 12px;
        }
        
        .stats-value {
            font-size: 2rem;
        }
        
        .stats-card .card-header,
        .stats-card .card-body {
            padding: 1rem;
        }
    }
    
    @media (max-width: 576px) {
        .stats-value {
            font-size: 1.75rem;
        }
        
        .stats-title {
            font-size: var(--app-font-min, 15px);
        }
    }
</style>
@endPushOnce