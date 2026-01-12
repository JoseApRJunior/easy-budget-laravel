@props([
    'id',
    'data' => [],
    'height' => 160,
    'emptyText' => 'Nenhum dado disponível',
])

<div class="chart-container" style="position: relative; height: {{ $height }}px;">
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
                container.innerHTML = `<p class="text-muted text-center mb-0 small mt-4">{{ $emptyText }}</p>`;
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
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: {
                                    size: 11
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
@endpush
