@extends( 'layouts.admin' )

@section( 'title', 'Dashboard Administrativo' )

@section( 'breadcrumb' )
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section( 'admin_content' )
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">
                    <i class="bi bi-speedometer2 me-2"></i>{{ $pageTitle ?? 'Dashboard Administrativo' }}
                    @if( isset( $systemStatus ) && $systemStatus )
                        <span
                            class="badge bg-{{ $systemStatus[ 'health' ] == 'healthy' ? 'success' : ( $systemStatus[ 'health' ] == 'warning' ? 'warning' : 'danger' ) }} ms-2">
                            {{ $systemStatus[ 'health' ] == 'healthy' ? 'Saudável' : ( $systemStatus[ 'health' ] == 'warning' ? 'Atenção' : 'Crítico' ) }}
                        </span>
                    @endif
                </h2>

                {{-- Alertas Críticos --}}
                @if( isset( $alerts ) && count( $alerts ) > 0 )
                    <div class="row mb-4">
                        <div class="col-12">
                            @foreach( $alerts as $alert )
                                <div class="alert alert-{{ $alert[ 'type' ] ?? 'info' }} alert-dismissible fade show" role="alert">
                                    <i
                                        class="bi bi-{{ ( $alert[ 'type' ] ?? 'info' ) == 'danger' ? 'exclamation-triangle' : 'info-circle' }} me-2"></i>
                                    {{ $alert[ 'message' ] ?? '' }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- KPIs Principais --}}
                @if( isset( $kpis ) )
                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body text-center">
                                    <i class="bi bi-graph-up text-primary fs-1 mb-3"></i>
                                    <h3 class="text-primary mb-1">{{ number_format( $kpis[ 'total_requests' ] ?? 0 ) }}</h3>
                                    <h6 class="text-muted mb-0">Requisições (24h)</h6>
                                    @if( isset( $kpis[ 'growth_rate' ] ) )
                                        <small
                                            class="text-{{ strpos( $kpis[ 'growth_rate' ], '+' ) === 0 ? 'success' : 'danger' }}">
                                            <i
                                                class="bi bi-arrow-{{ strpos( $kpis[ 'growth_rate' ], '+' ) === 0 ? 'up' : 'down' }}"></i>
                                            {{ $kpis[ 'growth_rate' ] }} vs período anterior
                                        </small>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body text-center">
                                    <i class="bi bi-check-circle text-success fs-1 mb-3"></i>
                                    <h3 class="text-success mb-1">{{ number_format( $kpis[ 'success_rate' ] ?? 0, 1 ) }}%</h3>
                                    <h6 class="text-muted mb-0">Taxa de Sucesso</h6>
                                    @if( isset( $kpis[ 'success_rate' ] ) )
                                        <small class="text-{{ ( $kpis[ 'success_rate' ] ?? 0 ) >= 95 ? 'success' : 'warning' }}">
                                            <i
                                                class="bi bi-{{ ( $kpis[ 'success_rate' ] ?? 0 ) >= 95 ? 'check' : 'exclamation-triangle' }}"></i>
                                            {{ ( $kpis[ 'success_rate' ] ?? 0 ) >= 95 ? 'Excelente' : 'Atenção' }}
                                        </small>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body text-center">
                                    <i class="bi bi-lightning text-warning fs-1 mb-3"></i>
                                    <h3 class="text-warning mb-1">{{ number_format( $kpis[ 'avg_response_time' ] ?? 0, 1 ) }}ms
                                    </h3>
                                    <h6 class="text-muted mb-0">Tempo Médio</h6>
                                    @if( isset( $kpis[ 'avg_response_time' ] ) )
                                        <small class="text-{{ ( $kpis[ 'avg_response_time' ] ?? 0 ) < 50 ? 'success' : 'info' }}">
                                            <i class="bi bi-speedometer2"></i>
                                            {{ ( $kpis[ 'avg_response_time' ] ?? 0 ) < 50 ? 'Rápido' : 'Normal' }}
                                        </small>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body text-center">
                                    <i class="bi bi-shield-check text-info fs-1 mb-3"></i>
                                    <h3 class="text-info mb-1">{{ $kpis[ 'active_middlewares' ] ?? 0 }}</h3>
                                    <h6 class="text-muted mb-0">Middlewares Ativos</h6>
                                    @if( isset( $kpis[ 'active_middlewares' ] ) )
                                        <small
                                            class="text-{{ ( $kpis[ 'active_middlewares' ] ?? 0 ) > 0 ? 'success' : 'warning' }}">
                                            <i
                                                class="bi bi-{{ ( $kpis[ 'active_middlewares' ] ?? 0 ) > 0 ? 'check' : 'exclamation-triangle' }}"></i>
                                            {{ ( $kpis[ 'active_middlewares' ] ?? 0 ) > 0 ? 'Ativos' : 'Nenhum ativo' }}
                                        </small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Status do Sistema --}}
                @if( isset( $systemStatus ) )
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
                                                    class="bi bi-heart-pulse text-{{ $systemStatus[ 'health' ] == 'healthy' ? 'success' : 'warning' }} fs-2 mb-2"></i>
                                                <h6>Saúde Geral</h6>
                                                <span
                                                    class="badge bg-{{ $systemStatus[ 'health' ] == 'healthy' ? 'success' : 'warning' }}">
                                                    {{ $systemStatus[ 'health' ] == 'healthy' ? 'Saudável' : 'Atenção' }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="p-3">
                                                <i
                                                    class="bi bi-speedometer text-{{ $systemStatus[ 'performance' ] == 'excellent' ? 'success' : 'info' }} fs-2 mb-2"></i>
                                                <h6>Performance</h6>
                                                <span
                                                    class="badge bg-{{ $systemStatus[ 'performance' ] == 'excellent' ? 'success' : 'info' }}">
                                                    {{ $systemStatus[ 'performance' ] == 'excellent' ? 'Excelente' : 'Boa' }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="p-3">
                                                <i class="bi bi-clock text-success fs-2 mb-2"></i>
                                                <h6>Uptime</h6>
                                                <span class="badge bg-success">{{ $systemStatus[ 'uptime' ] ?? 'N/A' }}</span>
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
                                        <a href="{{ route( 'admin.monitoring.index' ) }}" class="btn btn-outline-secondary">
                                            <i class="bi bi-graph-up me-2 text-primary"></i>Ver Métricas Técnicas
                                        </a>
                                        <a href="{{ route( 'admin.alerts.index' ) }}" class="btn btn-outline-secondary">
                                            <i class="bi bi-bell me-2 text-warning"></i>Gerenciar Alertas
                                        </a>
                                        <button class="btn btn-outline-secondary" onclick="refreshData()">
                                            <i class="bi bi-arrow-clockwise me-2 text-success"></i>Atualizar Dados
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Gráfico de Tendência --}}
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

@push( 'scripts' )
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        document.addEventListener( 'DOMContentLoaded', function () {
            const ctx = document.getElementById( 'trendChart' );
            if ( ctx && typeof chartData !== 'undefined' ) {
                new Chart( ctx, {
                    type: 'line',
                    data: {
                        labels: chartData.labels || [],
                        datasets: [{
                            label: 'Requisições por Hora',
                            data: chartData.requests || [],
                            borderColor: 'rgb(54, 162, 235)',
                            backgroundColor: 'rgba(54, 162, 235, 0.1)',
                            tension: 0.4,
                            fill: true
                        }, {
                            label: 'Tempo de Resposta (ms)',
                            data: chartData.response_times || [],
                            borderColor: 'rgb(255, 99, 132)',
                            backgroundColor: 'rgba(255, 99, 132, 0.1)',
                            tension: 0.4,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
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
                                    text: 'Tempo (ms)'
                                },
                                grid: {
                                    drawOnChartArea: false,
                                },
                            }
                        },
                        plugins: {
                            legend: {
                                display: true
                            }
                        }
                    }
                } );
            }

            // Auto-refresh a cada 5 minutos
            setInterval( function () {
                location.reload();
            }, 300000 );
        } );
    </script>
@endpush
