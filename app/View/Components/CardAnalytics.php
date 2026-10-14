<?php

namespace App\View\Components;

use Illuminate\View\Component;

class CardAnalytics extends Component
{
    public $title;
    public $description;
    public $chartId;
    public $height;
    public $width;
    public $chartType;
    public $data;
    public $options;
    public $cardOptions;
    public $headerClass;

    /**
     * Create a new component instance.
     *
     * @param string $title
     * @param string $description
     * @param string $chartId
     * @param string $height
     * @param string $width
     * @param string $chartType
     * @param array $data
     * @param array $options
     * @param bool $cardOptions
     * @param string $headerClass
     */
    public function __construct(
        $title = 'Sales Analytics',
        $description = 'For more details about usage, please refer amCharts licences.',
        $chartId = 'sales-analytics',
        $height = '265px',
        $width = '100%',
        $chartType = 'line',
        $data = null,
        $options = null,
        $cardOptions = true,
        $headerClass = ''
    ) {
        $this->title = $title;
        $this->description = $description;
        $this->chartId = $chartId;
        $this->height = $height;
        $this->width = $width;
        $this->chartType = $chartType;
        $this->data = $data ?? $this->defaultData();
        $this->options = $options ?? $this->defaultOptions();
        $this->cardOptions = $cardOptions;
        $this->headerClass = $headerClass;
    }

    /**
     * Default chart data
     */
    private function defaultData()
    {
        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'datasets' => [[
                'label' => 'Sales',
                'data' => [12, 19, 3, 5, 2, 3],
                'borderColor' => '#4CAF50',
                'backgroundColor' => 'rgba(76, 175, 80, 0.2)',
            ]]
        ];
    }

    /**
     * Default chart options
     */
    private function defaultOptions()
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true
                ]
            ]
        ];
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.card-analytics');
    }
}