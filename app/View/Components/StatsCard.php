<?php

namespace App\View\Components;

use Illuminate\View\Component;

class StatsCard extends Component
{
    public $title;
    public $value;
    public $subValue;
    public $description;
    public $icon;
    public $color;
    public $trend;
    public $trendValue;
    public $badge;
    public $badgeColor;
    public $progress;
    public $progressMax;
    public $showProgress;
    public $compact;

    public function __construct(
        $title,
        $value,
        $subValue = null,
        $description = null,
        $icon = null,
        $color = 'blue',
        $trend = null, // 'up', 'down', 'neutral'
        $trendValue = null,
        $badge = null,
        $badgeColor = 'primary',
        $progress = null,
        $progressMax = 100,
        $showProgress = false,
        $compact = false
    ) {
        $this->title = $title;
        $this->value = $value;
        $this->subValue = $subValue;
        $this->description = $description;
        $this->icon = $icon;
        $this->color = $color;
        $this->trend = $trend;
        $this->trendValue = $trendValue;
        $this->badge = $badge;
        $this->badgeColor = $badgeColor;
        $this->progress = $progress;
        $this->progressMax = $progressMax;
        $this->showProgress = $showProgress;
        $this->compact = $compact;
    }

    public function getColorClass()
    {
        return 'stats-color-' . $this->color;
    }

    public function getTrendIcon()
    {
        return match($this->trend) {
            'up' => 'feather icon-arrow-up',
            'down' => 'feather icon-arrow-down',
            'neutral' => 'feather icon-minus',
            default => null
        };
    }

    public function getTrendColor()
    {
        return match($this->trend) {
            'up' => 'text-success',
            'down' => 'text-danger',
            'neutral' => 'text-warning',
            default => ''
        };
    }

    public function getProgressPercentage()
    {
        if ($this->progress && $this->progressMax) {
            return ($this->progress / $this->progressMax) * 100;
        }
        return 0;
    }

    public function render()
    {
        return view('components.stats-card');
    }
}