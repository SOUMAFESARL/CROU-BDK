<?php

namespace App\View\Components;

use Illuminate\View\Component;

class AppScripts extends Component
{
    public $jqueryVersion;
    public $bootstrapVersion;
    public $loadChartJS;
    public $loadAmCharts;
    public $loadCustomDashboard;
    public $additionalScripts;

    public function __construct(
        $jqueryVersion = '3.3.1',
        $bootstrapVersion = '4.1.3',
        $loadChartJS = true,
        $loadAmCharts = true,
        $loadCustomDashboard = true,
        $additionalScripts = []
    ) {
        $this->jqueryVersion = $jqueryVersion;
        $this->bootstrapVersion = $bootstrapVersion;
        $this->loadChartJS = $loadChartJS;
        $this->loadAmCharts = $loadAmCharts;
        $this->loadCustomDashboard = $loadCustomDashboard;
        $this->additionalScripts = $additionalScripts;
    }

    public function render()
    {
        return view('components.app-scripts');
    }
}