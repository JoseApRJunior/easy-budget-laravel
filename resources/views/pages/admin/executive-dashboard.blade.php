@extends('layouts.app')

@section('content')
    <x-layout.page-container>
        <x-layout.page-header
            title="Dashboard Executivo"
            icon="graph-up-arrow"
            :breadcrumb-items="[
                'Admin' => '#',
                'Executivo' => '#'
            ]">
            <x-slot:actions>
                <div class="d-flex gap-2">
                    <x-ui.button 
                        variant="outline-primary" 
                        onclick="refreshCharts()"
                        icon="arrow-clockwise"
                        label="Atualizar"
                    />
                    <x-ui.button 
                        href="/admin/executive-dashboard/export-pdf" 
                        variant="success"
                        icon="file-earmark-pdf"
                        label="Exportar PDF"
                    />
                </div>
            </x-slot:actions>
        </x-layout.page-header>

        <!-- KPIs Cards -->
        <div class="row mb-4">
            <x-dashboard.stat-card 
                col="col-md-3"
                title="Requisições (24h)"
                :value="\App\Helpers\CurrencyHelper::format($kpis['total_requests'], 0, false)"
                icon="activity"
                variant="primary"
            />

            <x-dashboard.stat-card 
                col="col-md-3"
                title="Taxa de Sucesso"
                :value="$kpis['success_rate'] . '%'"
                icon="check-circle"
                variant="success"
            />

            <x-dashboard.stat-card 
                col="col-md-3"
                title="Tempo Médio"
                :value="$kpis['avg_response_time'] . 'ms'"
                icon="speedometer2"
                variant="warning"
            />

            <x-dashboard.stat-card 
                col="col-md-3"
                title="Status do Sistema"
                :value="$kpis['system_health'] == 'HEALTHY' ? 'Saudável' : ($kpis['system_health'] == 'WARNING' ? 'Atenção' : 'Crítico')"
                :icon="$kpis['system_health'] == 'HEALTHY' ? 'heart-fill' : ($kpis['system_health'] == 'WARNING' ? 'exclamation-triangle' : 'x-circle')"
                :variant="$kpis['system_health'] == 'HEALTHY' ? 'success' : ($kpis['system_health'] == 'WARNING' ? 'warning' : 'danger')"
            />
        </div>

        <!-- Gráficos -->
        <div class="row">
            <div class="col-md-8">
                <x-ui.card>
                    <x-slot:header>
                        <h5 class="mb-0 text-primary fw-bold">
                            <i class="bi bi-graph-up me-2"></i>Tendência de Performance (6h)
                        </h5>
                    </x-slot:header>
                    <canvas id="performanceChart" height="100"></canvas>
                </x-ui.card>
            </div>
            <div class="col-md-4">
                <x-ui.card>
                    <x-slot:header>
                        <h5 class="mb-0 text-primary fw-bold">
                            <i class="bi bi-pie-chart me-2"></i>Distribuição por Middleware
                        </h5>
                    </x-slot:header>
                    <canvas id="middlewareChart"></canvas>
                </x-ui.card>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <x-ui.card>
                    <x-slot:header>
                        <h5 class="mb-0 text-primary fw-bold">
                            <i class="bi bi-exclamation-triangle me-2"></i>Alertas (24h)
                        </h5>
                    </x-slot:header>
                    <canvas id="alertsChart" height="150"></canvas>
                </x-ui.card>
            </div>
            <div class="col-md-6">
                <x-ui.card>
                    <x-slot:header>
                        <h5 class="mb-0 text-primary fw-bold">
                            <i class="bi bi-list-check me-2"></i>Resumo de Alertas
                        </h5>
                    </x-slot:header>
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="border-end">
                                <h3 class="text-danger fw-bold">{{ $alerts_summary['CRITICAL'] }}</h3>
                                <small class="text-muted fw-bold text-uppercase">Críticos</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border-end">
                                <h3 class="text-warning fw-bold">{{ $alerts_summary['WARNING'] }}</h3>
                                <small class="text-muted fw-bold text-uppercase">Atenção</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <h3 class="text-info fw-bold">{{ $alerts_summary['INFO'] }}</h3>
                            <small class="text-muted fw-bold text-uppercase">Info</small>
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </x-layout.page-container>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        let performanceChart, middlewareChart, alertsChart;

        document.addEventListener('DOMContentLoaded', function() {
            loadChartData();
        });

        function loadChartData() {
            fetch('/admin/executive-dashboard/chart-data')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erro na resposta: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    createPerformanceChart(data.performance_trend);
                    createMiddlewareChart(data.middleware_distribution);
                    createAlertsChart(data.alerts_timeline);
                })
                .catch(error => {
                    console.error('Erro ao carregar dados dos gráficos:', error);
                });
        }

        function createPerformanceChart(data) {
            const ctx = document.getElementById('performanceChart').getContext('2d');
            if (performanceChart) performanceChart.destroy();
            if (!data || !Array.isArray(data) || data.length === 0) return;

            performanceChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(d => d.time),
                    datasets: [{
                        label: 'Tempo de Resposta (ms)',
                        data: data.map(d => parseFloat(d.avg_time)),
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        function createMiddlewareChart(data) {
            const ctx = document.getElementById('middlewareChart').getContext('2d');
            if (middlewareChart) middlewareChart.destroy();
            if (!data) return;

            middlewareChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(data),
                    datasets: [{
                        data: Object.values(data),
                        backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8']
                    }]
                }
            });
        }

        function createAlertsChart(data) {
            const ctx = document.getElementById('alertsChart').getContext('2d');
            if (alertsChart) alertsChart.destroy();
            if (!data || !Array.isArray(data) || data.length === 0) return;

            alertsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(d => d.time),
                    datasets: [{
                        label: 'Nº de Alertas',
                        data: data.map(d => d.count),
                        backgroundColor: data.map(d => {
                            if (d.level === 'CRITICAL') return '#dc3545';
                            if (d.level === 'WARNING') return '#ffc107';
                            return '#17a2b8';
                        })
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        function refreshCharts() {
            loadChartData();
        }
    </script>
@endsection
