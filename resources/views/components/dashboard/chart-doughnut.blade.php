@props([
    'id',
    'data' => [],
    'height' => 100,
    'emptyText' => 'Nenhum dado disponível',
])

<div class="chart-container py-2" style="position: relative; height: {{ $height }}px; width: 100%;">
    <canvas id="{{ $id }}"></canvas>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chartData = @json($data);
            const labels = [];
            const values = [];
            const colors = [];

            Object.keys(chartData).forEach(key => {
                const info = chartData[key];
                if (info && typeof info === 'object' && info.count > 0) {
                    labels.push(info.label || key);
                    values.push(info.count);
                    colors.push(info.color || '#6c757d');
                }
            });

            if (values.length === 0) {
                const container = document.getElementById('{{ $id }}').parentElement;
                container.innerHTML = `<p class="text-center mb-0 small mt-2 py-2" style="color: {{ config('theme.colors.secondary', '#94a3b8') }};">{{ $emptyText }}</p>`;
                return;
            }

            const ctx = document.getElementById('{{ $id }}');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 10,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                boxWidth: 8,
                                boxHeight: 8,
                                font: {
                                    size: 11,
                                    family: "'Inter', sans-serif"
                                },
                                color: '#64748b'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(255, 255, 255, 0.95)',
                            titleColor: '#1e293b',
                            bodyColor: '#475569',
                            borderColor: '#e2e8f0',
                            borderWidth: 1,
                            padding: 10,
                            displayColors: true,
                            usePointStyle: true,
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return ' ' + context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
@endpush
