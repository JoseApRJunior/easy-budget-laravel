@props([
    'type' => 'bar',
    'data' => [],
    'options' => [],
    'height' => 400,
    'width' => '100%',
    'class' => '',
    'interactive' => true
])

@php
    $chartId = 'chart_' . uniqid();
    $chartService = app(\App\Services\ChartVisualizationService::class);
    $dataCollection = collect($data);

    // Gerar configuração baseada no tipo
    $config = [];
    switch($type) {
        case 'line':
            $config = $chartService->generateLineChart($dataCollection, $options);
            break;
        case 'bar':
            $config = $chartService->generateBarChart($dataCollection, $options);
            break;
        case 'pie':
        case 'doughnut':
            $config = $chartService->generatePieChart($dataCollection, $options);
            break;
        case 'area':
            $config = $chartService->generateAreaChart($dataCollection, $options);
            break;
        case 'kpi':
            $config = $chartService->generateKpiChart($dataCollection, $options);
            break;
        case 'scatter':
            $config = $chartService->generateScatterChart($dataCollection, $options);
            break;
        default:
            $config = $chartService->generateBarChart($dataCollection, $options);
    }
@endphp

<div class="chart-container {{ $class }}" style="position: relative; height: {{ $height }}px; width: {{ $width }};">
    <canvas id="{{ $chartId }}" width="100%" height="100%"></canvas>

    @if($interactive)
        <!-- Controles interativos -->
        <div class="chart-controls absolute top-2 right-2 bg-white rounded-lg shadow-lg p-2 opacity-75 hover:opacity-100 transition-opacity">
            <div class="flex space-x-1">
                <button class="chart-control-btn p-1 text-gray-600 hover:text-gray-800"
                        data-action="toggle-grid"
                        data-chart-id="{{ $chartId }}"
                        title="Alternar Grade">
                    <i class="bi bi-grid-3x3"></i>
                </button>
                <button class="chart-control-btn p-1 text-gray-600 hover:text-gray-800"
                        data-action="toggle-legend"
                        data-chart-id="{{ $chartId }}"
                        title="Alternar Legenda">
                    <i class="bi bi-list-ul"></i>
                </button>
                <button class="chart-control-btn p-1 text-gray-600 hover:text-gray-800"
                        data-action="download-png"
                        data-chart-id="{{ $chartId }}"
                        title="Download PNG">
                    <i class="bi bi-download"></i>
                </button>
                <button class="chart-control-btn p-1 text-gray-600 hover:text-gray-800"
                        data-action="fullscreen"
                        data-chart-id="{{ $chartId }}"
                        title="Tela Cheia">
                    <i class="bi bi-arrows-fullscreen"></i>
                </button>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('{{ $chartId }}').getContext('2d');
    const chartConfig = @json($config);
    let chart = new Chart(ctx, chartConfig);

    // Controles interativos
    @if($interactive)
    document.querySelectorAll('.chart-control-btn[data-chart-id="{{ $chartId }}"]').forEach(button => {
        button.addEventListener('click', function() {
            const action = this.getAttribute('data-action');

            switch(action) {
                case 'toggle-grid':
                    const gridConfig = chart.options.scales;
                    Object.keys(gridConfig).forEach(scale => {
                        if (gridConfig[scale].grid) {
                            gridConfig[scale].grid.display = !gridConfig[scale].grid.display;
                        }
                    });
                    chart.update();
                    break;

                case 'toggle-legend':
                    chart.options.plugins.legend.display = !chart.options.plugins.legend.display;
                    chart.update();
                    break;

                case 'download-png':
                    const link = document.createElement('a');
                    link.download = 'chart_{{ $chartId }}.png';
                    link.href = chart.toBase64Image();
                    link.click();
                    break;

                case 'fullscreen':
                    const container = document.querySelector('#{{ $chartId }}').parentElement;
                    if (!document.fullscreenElement) {
                        container.requestFullscreen();
                    } else {
                        document.exitFullscreen();
                    }
                    break;
            }
        });
    });
    @endif

    // Responsividade
    window.addEventListener('resize', function() {
        if (chart) {
            chart.resize();
        }
    });
});
</script>
@endpush

@push('styles')
<style>
.chart-container {
    position: relative;
}

.chart-controls {
    z-index: 10;
}

.chart-control-btn {
    transition: all 0.2s ease;
}

.chart-control-btn:hover {
    transform: scale(1.1);
}

/* Estilos para gráficos em tela cheia */
.chart-container:fullscreen {
    background: white;
    padding: 20px;
}

/* Loading state */
.chart-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    background: rgba(255, 255, 255, 0.9);
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 5;
}

.chart-loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Estilos para gráficos D3.js */
.d3-chart-container {
    overflow: hidden;
}

.d3-tooltip {
    position: absolute;
    text-align: center;
    padding: 8px 12px;
    font: 12px sans-serif;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    border: 0px;
    border-radius: 8px;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.3s;
}

.d3-axis path,
.d3-axis line {
    fill: none;
    stroke: #000;
    shape-rendering: crispEdges;
}

.d3-grid .tick {
    stroke: lightgrey;
    stroke-opacity: 0.7;
}

.d3-grid path {
    stroke-width: 0;
}
</style>
@endpush
