@extends('layouts.admin')

@section('admin_content')
    <x-layout.page-header
        title="Dashboard de Inteligência Artificial"
        icon="robot"
        :breadcrumb-items="[
            'Admin' => url('/admin'),
            'IA' => '#'
        ]">
    </x-layout.page-header>

    <style>
        .metric-card {
            text-align: center;
            padding: 1.5rem;
        }

        .metric-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .metric-label {
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .prediction-chart {
            height: 300px;
            max-height: 300px;
            margin: 1rem 0;
        }

        .alert-item {
            transition: all 0.3s ease;
        }

        .alert-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
    </style>

    <div class="container-fluid">
        <!-- Métricas Principais -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row" id="header-metrics">
                            <div class="col-md-3 text-center border-end">
                                <div class="h5 mb-0 font-weight-bold" id="downtime-reduction">-</div>
                                <small class="text-muted">Redução de Downtime</small>
                            </div>
                            <div class="col-md-3 text-center border-end">
                                <div class="h5 mb-0 font-weight-bold" id="revenue-increase">-</div>
                                <small class="text-muted">Aumento de Receita</small>
                            </div>
                            <div class="col-md-3 text-center border-end">
                                <div class="h5 mb-0 font-weight-bold" id="alerts-count">-</div>
                                <small class="text-muted">Alertas Ativos</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="h5 mb-0 font-weight-bold" id="efficiency-gains">-</div>
                                <small class="text-muted">Ganhos de Eficiência</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Métricas ROI -->
        <div class="row mb-4" id="roi-metrics">
            <div class="col-md-3">
                <div class="card metric-card">
                    <div class="card-body">
                        <div class="metric-value text-success" id="roi-downtime">-</div>
                        <div class="metric-label text-muted">Redução Downtime</div>
                        <small class="text-muted">Meta: 70-85%</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card metric-card">
                    <div class="card-body">
                        <div class="metric-value text-success" id="roi-revenue">-</div>
                        <div class="metric-label text-muted">Aumento Receita</div>
                        <small class="text-muted">Vs. período anterior</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card metric-card">
                    <div class="card-body">
                        <div class="metric-value text-success" id="roi-savings">-</div>
                        <div class="metric-label text-muted">Economia Custos</div>
                        <small class="text-muted">Automação + Otimização</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card metric-card">
                    <div class="card-body">
                        <div class="metric-value text-success" id="roi-efficiency">-</div>
                        <div class="metric-label text-muted">Ganhos Eficiência</div>
                        <small class="text-muted">Processos otimizados</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Predições de Churn -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-person-x text-warning me-2"></i>
                            Predições de Churn
                        </h5>
                    </div>
                    <div class="card-body" id="churn-predictions">
                        <div class="text-center text-muted py-1">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                            <p class="mt-2">Carregando predições...</p>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <x-ui.button type="link" href="/admin/ai/reports?type=churn" variant="primary" size="sm" label="Ver Relatório Completo" />
                            <small class="text-muted">Atualizado há 2 horas</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alertas Proativos -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                            Alertas Proativos
                        </h5>
                    </div>
                    <div class="card-body" id="alerts-container">
                        <div class="text-center text-muted py-1">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                            <p class="mt-2">Carregando alertas...</p>
                        </div>
                        <div class="text-center mt-3">
                            <x-ui.button variant="primary" size="sm" icon="arrow-clockwise" label="Atualizar Dashboard" onclick="loadDashboardData()" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Previsão de Receita -->
        <div class="row">
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-graph-up text-success me-2"></i>
                            Previsão de Receita
                        </h5>
                    </div>
                    <div class="card-body" style="height: 350px;">
                        <div style="position: relative; height: 300px;">
                            <canvas id="revenueChart" class="prediction-chart"></canvas>
                        </div>
                        <div class="row mt-3" id="revenue-forecast">
                            <div class="col-md-4 text-center">
                                <h6>Próximo Mês</h6>
                                <span class="h4 text-success" id="next-month">-</span>
                            </div>
                            <div class="col-md-4 text-center">
                                <h6>Próximo Trimestre</h6>
                                <span class="h4 text-primary" id="next-quarter">-</span>
                            </div>
                            <div class="col-md-4 text-center">
                                <h6>Taxa de Crescimento</h6>
                                <span class="h4 text-info" id="growth-rate">-</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData();
        });

        function loadDashboardData() {
            fetch('/admin/ai/dashboard-data')
                .then(response => response.json())
                .then(data => {
                    updateMetrics(data.metrics);
                    updateChurnPredictions(data.churn_predictions);
                    updateProactiveAlerts(data.proactive_alerts);
                    createRevenueChart(data.revenue_forecast);
                })
                .catch(error => console.error('Erro ao carregar dados do dashboard:', error));
        }

        function updateMetrics(metrics) {
            document.getElementById('downtime-reduction').textContent = metrics.downtime_reduction;
            document.getElementById('revenue-increase').textContent = metrics.revenue_increase;
            document.getElementById('alerts-count').textContent = metrics.alerts_count;
            document.getElementById('efficiency-gains').textContent = metrics.efficiency_gains;

            document.getElementById('roi-downtime').textContent = metrics.roi.downtime;
            document.getElementById('roi-revenue').textContent = metrics.roi.revenue;
            document.getElementById('roi-savings').textContent = metrics.roi.savings;
            document.getElementById('roi-efficiency').textContent = metrics.roi.efficiency;
        }

        function updateChurnPredictions(predictions) {
            const container = document.getElementById('churn-predictions');
            container.innerHTML = ''; // Limpa o spinner
            predictions.forEach(p => {
                const predictionHtml = `
                <div class="alert alert-warning alert-item">
                    <strong>${p.customer_name}</strong> (ID: ${p.customer_id})<br>
                    Probabilidade de Churn: <strong>${p.churn_probability}%</strong><br>
                    <a href="/admin/customer/${p.customer_id}" class="btn btn-sm btn-secondary mt-2">Ver Cliente</a>
                </div>`;
                container.insertAdjacentHTML('beforeend', predictionHtml);
            });
        }

        function updateProactiveAlerts(alerts) {
            const container = document.getElementById('alerts-container');
            container.innerHTML = ''; // Limpa o spinner
            alerts.forEach(a => {
                const alertHtml = `
                <div class="alert alert-danger alert-item">
                    <strong>${a.title}</strong><br>
                    ${a.description}<br>
                    <small class="text-muted">Detectado em: ${a.timestamp}</small>
                </div>`;
                container.insertAdjacentHTML('beforeend', alertHtml);
            });
        }

        function createRevenueChart(forecast) {
            const ctx = document.getElementById('revenueChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: forecast.labels,
                    datasets: [{
                        label: 'Previsão de Receita',
                        data: forecast.data,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        fill: true
                    }]
                }
            });

            document.getElementById('next-month').textContent = forecast.summary.next_month;
            document.getElementById('next-quarter').textContent = forecast.summary.next_quarter;
            document.getElementById('growth-rate').textContent = forecast.summary.growth_rate;
        }
    </script>
@endsection
