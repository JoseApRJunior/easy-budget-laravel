<x-app-layout :title="$page_title">
    <x-layout.page-container>
        <x-layout.page-header
            :title="$page_title"
            icon="speedometer2"
            :breadcrumb-items="[
                'Admin' => '#',
                'Dashboard' => '#'
            ]">
            <x-slot:actions>
                <span class="badge bg-{{ $system_status['health'] == 'healthy' ? 'success' : ($system_status['health'] == 'warning' ? 'warning' : 'danger') }} ms-2">
                    {{ $system_status['health'] == 'healthy' ? 'Saudável' : ($system_status['health'] == 'warning' ? 'Atenção' : 'Crítico') }}
                </span>
            </x-slot:actions>
        </x-layout.page-header>

        <!-- Alertas Críticos -->
        @if (count($alerts) > 0)
            <div class="row mb-4">
                <div class="col-12">
                    @foreach ($alerts as $alert)
                        <div class="alert alert-{{ $alert['type'] }} alert-dismissible fade show" role="alert">
                            <i class="bi bi-{{ $alert['type'] == 'danger' ? 'exclamation-triangle' : 'info-circle' }} me-2"></i>
                            {{ $alert['message'] }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- KPIs Principais -->
        <div class="row g-4 mb-4">
            <x-dashboard.stat-card
                col="col-md-3"
                title="Requisições (24h)"
                :value="\App\Helpers\CurrencyHelper::format($kpis['total_requests'], 0, false)"
                icon="graph-up"
                variant="primary"
                :description="$kpis['growth_rate'] . ' vs período anterior'"
            />

            <x-dashboard.stat-card
                col="col-md-3"
                title="Taxa de Sucesso"
                :value="\App\Helpers\CurrencyHelper::format($kpis['success_rate'], 1, false) . '%'"
                icon="check-circle"
                variant="success"
                :description="($kpis['success_rate'] >= 95 ? 'Excelente' : 'Atenção')"
            />

            <x-dashboard.stat-card
                col="col-md-3"
                title="Tempo Médio"
                :value="\App\Helpers\CurrencyHelper::format($kpis['avg_response_time'], 1, false) . 'ms'"
                icon="lightning"
                variant="warning"
                :description="($kpis['avg_response_time'] < 50 ? 'Rápido' : 'Normal')"
            />

            <x-dashboard.stat-card
                col="col-md-3"
                title="Middlewares Ativos"
                :value="$kpis['active_middlewares']"
                icon="shield-check"
                variant="info"
                :description="($kpis['active_middlewares'] > 0 ? 'Ativos' : 'Nenhum ativo')"
            />
        </div>

        <!-- Status do Sistema -->
        <div class="row g-4 mb-4">
            <div class="col-md-8">
                <x-ui.card>
                    <x-slot:header>
                        <h5 class="mb-0 text-primary fw-bold">
                            <i class="bi bi-activity me-2"></i>Status do Sistema
                        </h5>
                    </x-slot:header>

                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="p-3">
                                <i class="bi bi-heart-pulse text-{{ $system_status['health'] == 'healthy' ? 'success' : 'warning' }} fs-2 mb-2"></i>
                                <h6>Saúde Geral</h6>
                                <span class="badge bg-{{ $system_status['health'] == 'healthy' ? 'success' : 'warning' }}">
                                    {{ $system_status['health'] == 'healthy' ? 'Saudável' : 'Atenção' }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3">
                                <i class="bi bi-speedometer text-{{ $system_status['performance'] == 'excellent' ? 'success' : 'info' }} fs-2 mb-2"></i>
                                <h6>Performance</h6>
                                <span class="badge bg-{{ $system_status['performance'] == 'excellent' ? 'success' : 'info' }}">
                                    {{ $system_status['performance'] == 'excellent' ? 'Excelente' : 'Boa' }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3">
                                <i class="bi bi-clock text-success fs-2 mb-2"></i>
                                <h6>Uptime</h6>
                                <span class="badge bg-success">{{ $system_status['uptime'] }}</span>
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div class="col-md-4">
                <x-ui.card>
                    <x-slot:header>
                        <h5 class="mb-0 text-primary fw-bold">
                            <i class="bi bi-tools me-2"></i>Ações Rápidas
                        </h5>
                    </x-slot:header>

                    <div class="d-grid gap-2">
                        <x-ui.button href="/admin/monitoring" variant="secondary" outline icon="graph-up" label="Ver Métricas Técnicas" />
                        <x-ui.button href="/admin/alerts" variant="secondary" outline icon="bell" label="Gerenciar Alertas" />
                        <x-ui.button variant="secondary" outline icon="arrow-clockwise" label="Atualizar Dados" onclick="refreshData()" />
                    </div>
                </x-ui.card>
            </div>
        </div>

        <!-- Gráfico de Tendência -->
        <div class="row">
            <div class="col-12">
                <x-ui.card>
                    <x-slot:header>
                        <h5 class="mb-0 text-primary fw-bold">
                            <i class="bi bi-graph-up me-2"></i>Tendência de Performance (Últimas 24h)
                        </h5>
                    </x-slot:header>

                    <canvas id="trendChart" height="100"></canvas>
                </x-ui.card>
            </div>
        </div>
    </x-layout.page-container>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        // Gráfico de Tendência
        const ctx = document.getElementById('trendChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($chart_data['labels']),
                    datasets: [{
                        label: 'Requisições por Hora',
                        data: @json($chart_data['requests']),
                        borderColor: 'rgb(54, 162, 235)',
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        tension: 0.4,
                        fill: true
                    }, {
                        label: 'Tempo de Resposta (ms)',
                        data: @json($chart_data['response_times']),
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.1)',
                        tension: 0.4,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Requisições'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Tempo de Resposta (ms)'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });
        }

        function refreshData() {
            // Adicionar lógica para atualizar os dados do dashboard
            location.reload();
        }
    </script>
    @endpush
</x-app-layout>
