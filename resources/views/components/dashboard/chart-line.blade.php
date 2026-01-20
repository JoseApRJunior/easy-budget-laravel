@props([
    'id',
    'data' => [],
    'label' => 'Total',
    'height' => 300,
    'emptyText' => 'Nenhum dado financeiro para o período',
    'borderColor' => '#0d6efd',
    'backgroundColor' => 'rgba(13, 110, 253, 0.1)',
])

<div class="chart-container" style="position: relative; height: {{ $height }}px; width: 100%;">
    <canvas id="{{ $id }}"></canvas>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chartData = @json($data);
            const labels = Object.keys(chartData);
            const values = Object.values(chartData);

            if (values.length === 0 || values.every(v => v === 0)) {
                const container = document.getElementById('{{ $id }}').parentElement;
                container.classList.add('d-flex', 'align-items-center', 'justify-content-center');
                container.innerHTML = `<span class="text-muted fw-medium">{{ $emptyText }}</span>`;
                return;
            }

            const ctx = document.getElementById('{{ $id }}').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '{{ $label }}',
                        data: values,
                        borderColor: '{{ $borderColor }}',
                        backgroundColor: '{{ $backgroundColor }}',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'R$ ' + value.toLocaleString('pt-BR');
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
@endpush
