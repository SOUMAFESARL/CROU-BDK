@props([
    'title' => 'Sales Analytics',
    'description' => 'For more details about usage, please refer amCharts licences.',
    'chartId' => 'sales-analytics',
    'height' => '265px',
    'width' => '100%',
    'chartType' => 'line',
    'data' => null,
    'options' => null,
    'cardOptions' => true,
    'headerClass' => ''
])

<div class="card">
    <div class="card-header {{ $headerClass }}">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">{{ $title }}</h5>
                @if($description)
                <p class="text-muted mb-0 small">{{ $description }}</p>
                @endif
            </div>
            
            @if($cardOptions)
            <div class="card-header-right">
                <ul class="list-unstyled card-option mb-0">
                    <li title="Maximize">
                        <i class="feather icon-maximize full-card"></i>
                    </li>
                    <li title="Minimize">
                        <i class="feather icon-minus minimize-card"></i>
                    </li>
                    <li title="Close">
                        <i class="feather icon-trash-2 close-card"></i>
                    </li>
                    @if(isset($actions) && $actions)
                    <li class="dropdown">
                        <i class="feather icon-more-horizontal" data-toggle="dropdown"></i>
                        <div class="dropdown-menu dropdown-menu-right">
                            {{ $actions }}
                        </div>
                    </li>
                    @endif
                </ul>
            </div>
            @endif
        </div>
    </div>
    
    <div class="card-block">
        <div class="chart-container">
            <canvas 
                id="{{ $chartId }}" 
                data-type="{{ $chartType }}"
                data-chart-data="{{ json_encode($data) }}"
                data-chart-options="{{ json_encode($options) }}"
                style="height: {{ $height }}; width: {{ $width }};"
            ></canvas>
        </div>
        
        @if(isset($footer) && $footer)
        <div class="card-footer">
            {{ $footer }}
        </div>
        @endif
    </div>
</div>

@pushOnce('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts when component is loaded
    const chartElements = document.querySelectorAll('[data-chart-data]');
    
    chartElements.forEach(element => {
        const chartId = element.id;
        const chartType = element.getAttribute('data-type');
        const chartData = JSON.parse(element.getAttribute('data-chart-data'));
        const chartOptions = JSON.parse(element.getAttribute('data-chart-options'));
        
        initializeChart(chartId, chartType, chartData, chartOptions);
    });
    
    function initializeChart(chartId, chartType, data, options) {
        const ctx = document.getElementById(chartId).getContext('2d');
        
        return new Chart(ctx, {
            type: chartType,
            data: data,
            options: options
        });
    }
});
</script>
@endPushOnce