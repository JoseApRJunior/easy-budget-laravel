@extends( 'layout' )

@section( 'content' )
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-graph-up-arrow me-2"></i>Dashboard Executivo</h2>
            <div>
                <button class="btn btn-outline-primary" onclick="refreshCharts()">
                    <i class="bi bi-arrow-clockwise me-1"></i>Atualizar
                </button>
                <a href="/admin/executive-dashboard/export-pdf" class="btn btn-success">
                    <i class="bi bi-file-earmark-pdf me-1"></i>Exportar PDF
                </a>
            </div>
        </div>

        <!-- KPIs Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="d-flex align-items-center justify-content-center mb-2">
                            <i class="bi bi-activity fs-1 text-primary me-2"></i>
                            <div>
                                <h3 class="mb-0">{{ number_format( $kpis[ 'total_requests' ] ) }}</h3>
                                <small class="text-muted">Requisições (24h)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="d-flex align-items-center justify-content-center mb-2">
                            <i class="bi bi-check-circle fs-1 text-success me-2"></i>
                            <div>
                                <h3 class="mb-0">{{ $kpis[ 'success_rate' ] }}%</h3>
                                <small class="text-muted">Taxa de Sucesso</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="d-flex align-items-center justify-content-center mb-2">
                            <i class="bi bi-speedometer2 fs-1 text-warning me-2"></i>
                            <div>
                                <h3 class="mb-0">{{ $kpis[ 'avg_response_time' ] }}ms</h3>
                                <small class="text-muted">Tempo Médio</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="d-flex align-items-center justify-content-center mb-2">
                            @if ( $kpis[ 'system_health' ] == 'HEALTHY' )
                                <i class="bi bi-heart-fill fs-1 text-success me-2"></i>
                            @elseif ( $kpis[ 'system_health' ] == 'WARNING' )
                                <i class="bi bi-exclamation-triangle fs-1 text-warning me-2"></i>
                            @else
                                <i class="bi bi-x-circle fs-1 text-danger me-2"></i>
                            @endif
                            <div>
                                <h3
                                    class="mb-0 text-{{ $kpis[ 'system_health' ] == 'HEALTHY' ? 'success' : ( $kpis[ 'system_health' ] == 'WARNING' ? 'warning' : 'danger' ) }}">
                                    {{ $kpis[ 'system_health' ] == 'HEALTHY' ? 'Saudável' : ( $kpis[ 'system_health' ] == 'WARNING' ? 'Atenção' : 'Crítico' ) }}
                                </h3>
                                <small class="text-muted">Status do Sistema</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Tendência de Performance (6h)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="performanceChart" height="100"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Distribuição por Middleware</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="middlewareChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Alertas (24h)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="alertsChart" height="150"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Resumo de Alertas</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="border-end">
                                    <h3 class="text-danger">{{ $alerts_summary[ 'CRITICAL' ] }}</h3>
                                    <small class="text-muted">Críticos</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border-end">
                                    <h3 class="text-warning">{{ $alerts_summary[ 'WARNING' ] }}</h3>
                                    <small class="text-muted">Atenção</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <h3 class="text-info">{{ $alerts_summary[ 'INFO' ] }}</h3>
                                <small class="text-muted">Info</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section( 'scripts' )
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        let performanceChart, middlewareChart, alertsChart;

        document.addEventListener( 'DOMContentLoaded', function () {
            loadChartData();
        } );

        function loadChartData() {
            fetch( '/admin/executive-dashboard/chart-data' )
                .then( response => {
                    if ( !response.ok ) {
                        throw new Error( 'Erro na resposta: ' + response.status );
                    }
                    return response.json();
                } )
                .then( data => {
                    createPerformanceChart( data.performance_trend );
                    createMiddlewareChart( data.middleware_distribution );
                    createAlertsChart( data.alerts_timeline );
                } )
                .catch( error => {
                    console.error( 'Erro ao carregar dados dos gráficos:', error );
                } );
        }

        function createPerformanceChart( data ) {
            const ctx = document.getElementById( 'performanceChart' ).getContext( '2d' );
            if ( performanceChart ) performanceChart.destroy();
            if ( !data || !Array.isArray( data ) || data.length === 0 ) return;

            performanceChart = new Chart( ctx, {
                type: 'line',
                data: {
                    labels: data.map( d => d.time ),
                    datasets: [{
                        label: 'Tempo de Resposta (ms)',
                        data: data.map( d => parseFloat( d.avg_time ) ),
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
            } );
        }

        function createMiddlewareChart( data ) {
            const ctx = document.getElementById( 'middlewareChart' ).getContext( '2d' );
            if ( middlewareChart ) middlewareChart.destroy();
            if ( !data ) return;

            middlewareChart = new Chart( ctx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys( data ),
                    datasets: [{
                        data: Object.values( data ),
                        backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8']
                    }]
                }
            } );
        }

        function createAlertsChart( data ) {
            const ctx = document.getElementById( 'alertsChart' ).getContext( '2d' );
            if ( alertsChart ) alertsChart.destroy();
            if ( !data || !Array.isArray( data ) || data.length === 0 ) return;

            alertsChart = new Chart( ctx, {
                type: 'bar',
                data: {
                    labels: data.map( d => d.time ),
                    datasets: [{
                        label: 'Nº de Alertas',
                        data: data.map( d => d.count ),
                        backgroundColor: data.map( d => {
                            if ( d.level === 'CRITICAL' ) return '#dc3545';
                            if ( d.level === 'WARNING' ) return '#ffc107';
                            return '#17a2b8';
                        } )
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            } );
        }

        function refreshCharts() {
            loadChartData();
        }
    </script>
@endsection
