@extends('layouts.app')

@section('content')
    <div class="container-fluid py-1">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">
                    <i class="bi bi-speedometer2 me-2"></i>{{ $page_title }}
                    <span
                        class="badge bg-{{ $system_status['health'] == 'healthy' ? 'success' : ($system_status['health'] == 'warning' ? 'warning' : 'danger') }} ms-2">
                        {{ $system_status['health'] == 'healthy'
                            ? 'Saudável'
                            : ($system_status['health'] == 'warning'
                                ? 'Atenção'
                                : 'Crítico') }}
                    </span>
                </h2>

                <!-- Alertas Críticos -->
                @if (count($alerts) > 0)
                    <div class="row mb-4">
                        <div class="col-12">
                            @foreach ($alerts as $alert)
                                <div class="alert alert-{{ $alert['type'] }} alert-dismissible fade show" role="alert">
                                    <i
                                        class="bi bi-{{ $alert['type'] == 'danger' ? 'exclamation-triangle' : 'info-circle' }} me-2"></i>
                                    {{ $alert['message'] }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- KPIs Principais -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-graph-up text-primary fs-1 mb-3"></i>
                                <h3 class="text-primary mb-1">{{ number_format($kpis['total_requests']) }}</h3>
                                <h6 class="text-muted mb-0">Requisições (24h)</h6>
                                <small
                                    class="text-{{ str_starts_with($kpis['growth_rate'], '+') ? 'success' : 'danger' }}">
                                    <i
                                        class="bi bi-arrow-{{ str_starts_with($kpis['growth_rate'], '+') ? 'up' : 'down' }}"></i>
                                    {{ $kpis['growth_rate'] }} vs período anterior
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-check-circle text-success fs-1 mb-3"></i>
                                <h3 class="text-success mb-1">{{ number_format($kpis['success_rate'], 1) }}%</h3>
                                <h6 class="text-muted mb-0">Taxa de Sucesso</h6>
                                <small class="text-{{ $kpis['success_rate'] >= 95 ? 'success' : 'warning' }}">
                                    <i
                                        class="bi bi-{{ $kpis['success_rate'] >= 95 ? 'check' : 'exclamation-triangle' }}"></i>
                                    {{ $kpis['success_rate'] >= 95 ? 'Excelente' : 'Atenção' }}
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-lightning text-warning fs-1 mb-3"></i>
                                <h3 class="text-warning mb-1">{{ number_format($kpis['avg_response_time'], 1) }}ms</h3>
                                <h6 class="text-muted mb-0">Tempo Médio</h6>
                                <small class="text-{{ $kpis['avg_response_time'] < 50 ? 'success' : 'info' }}">
                                    <i class="bi bi-speedometer2"></i>
                                    {{ $kpis['avg_response_time'] < 50 ? 'Rápido' : 'Normal' }} </small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-shield-check text-info fs-1 mb-3"></i>
                                <h3 class="text-info mb-1">{{ $kpis['active_middlewares'] }}</h3>
                                <h6 class="text-muted mb-0">Middlewares Ativos</h6>
                                <small class="text-{{ $kpis['active_middlewares'] > 0 ? 'success' : 'warning' }}">
                                    <i
                                        class="bi bi-{{ $kpis['active_middlewares'] > 0 ? 'check' : 'exclamation-triangle' }}"></i>
                                    {{ $kpis['active_middlewares'] > 0 ? 'Ativos' : 'Nenhum ativo' }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status do Sistema -->
                <div class="row g-4 mb-4">
                    <div class="col-md-8">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-transparent">
                                <h5 class="mb-0">
                                    <i class="bi bi-activity me-2"></i>Status do Sistema
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-4">
                                        <div class="p-3">
                                            <i
                                                class="bi bi-heart-pulse text-{{ $system_status['health'] == 'healthy' ? 'success' : 'warning' }} fs-2 mb-2"></i>
                                            <h6>Saúde Geral</h6>
                                            <span
                                                class="badge bg-{{ $system_status['health'] == 'healthy' ? 'success' : 'warning' }}">
                                                {{ $system_status['health'] == 'healthy' ? 'Saudável' : 'Atenção' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-3">
                                            <i
                                                class="bi bi-speedometer text-{{ $system_status['performance'] == 'excellent' ? 'success' : 'info' }} fs-2 mb-2"></i>
                                            <h6>Performance</h6>
                                            <span
                                                class="badge bg-{{ $system_status['performance'] == 'excellent' ? 'success' : 'info' }}">
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
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-transparent">
                                <h5 class="mb-0">
                                    <i class="bi bi-tools me-2"></i>Ações Rápidas
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <x-button type="link" href="/admin/monitoring" variant="secondary" outline icon="graph-up" label="Ver Métricas Técnicas" />
                                    <x-button type="link" href="/admin/alerts" variant="secondary" outline icon="bell" label="Gerenciar Alertas" />
                                    <x-button variant="secondary" outline icon="arrow-clockwise" label="Atualizar Dados" onclick="refreshData()" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráfico de Tendência -->
                <div class="row">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-transparent">
                                <h5 class="mb-0">
                                    <i class="bi bi-graph-up me-2"></i>Tendência de Performance (Últimas 24h)
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="trendChart" height="100"></canvas>
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
@endsection
