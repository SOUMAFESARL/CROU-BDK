<?php

namespace App\View\Components;

use Illuminate\View\Component;

class CardStatistics extends Component
{
    public $color;
    public $title;
    public $value;
    public $updateTime;
    public $chartId;

    public function __construct(
        $color = 'c-yellow',
        $title = 'Statistics',
        $value = '0',
        $updateTime = '2:15 am',
        $chartId = 'update-chart-1'
    ) {
        $this->color = $color;
        $this->title = $title;
        $this->value = $value;
        $this->updateTime = $updateTime;
        $this->chartId = $chartId;
    }

    public function render()
    {
        return view('components.card-statistics');
    }
}