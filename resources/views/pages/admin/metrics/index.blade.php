@extends( 'layouts.admin' )

@section( 'title', 'Métricas de Middlewares' )

@section( 'admin_content' )
    <x-page-header
        title="Métricas de Middlewares"
        icon="graph-up"
        :breadcrumb-items="[
            'Admin' => url('/admin'),
            'Métricas' => '#'
        ]">
    </x-page-header>

    <!-- Estatísticas Gerais -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h5 class="text-muted fw-normal mt-0 text-truncate">Total de Requisições</h5>
                            <h3 class="my-2 py-1">{{ \App\Helpers\CurrencyHelper::format( $stats[ 'total_requests' ], 0, false ) }}</h3>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <i class="mdi mdi-chart-line widget-icon bg-success-lighten text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h5 class="text-muted fw-normal mt-0 text-truncate">Tempo Médio</h5>
                            <h3 class="my-2 py-1">{{ \App\Helpers\CurrencyHelper::format( $stats[ 'avg_response_time' ], 2, false ) }}ms</h3>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <i class="mdi mdi-clock-outline widget-icon bg-info-lighten text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h5 class="text-muted fw-normal mt-0 text-truncate">Memória Média</h5>
                            <h3 class="my-2 py-1">{{ \App\Helpers\CurrencyHelper::format( $stats[ 'avg_memory_usage' ] / 1024 / 1024, 1, false ) }}MB</h3>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <i class="mdi mdi-memory widget-icon bg-warning-lighten text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h5 class="text-muted fw-normal mt-0 text-truncate">Taxa de Cache</h5>
                            <h3 class="my-2 py-1">{{ \App\Helpers\CurrencyHelper::format( $stats[ 'cache_hit_rate' ], 1, false ) }}%</h3>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <i class="mdi mdi-cached widget-icon bg-primary-lighten text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Métricas Recentes -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="header-title">Métricas Recentes</h4>
                        <div>
                            <button class="btn btn-sm btn-outline-primary" onclick="refreshMetrics()">
                                <i class="mdi mdi-refresh"></i> Atualizar
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="cleanupMetrics()">
                                <i class="mdi mdi-delete"></i> Limpar Antigas
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped" id="metricsTable">
                            <thead>
                                <tr>
                                    <th>Middleware</th>
                                    <th>Endpoint</th>
                                    <th>Método</th>
                                    <th>Tempo (ms)</th>
                                    <th>Memória</th>
                                    <th>Status</th>
                                    <th>Data/Hora</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ( $metrics as $metric )
                                    <tr
                                        class="@if( $metric[ 'responseTime' ] > 1000 ) table-danger @elseif( $metric[ 'responseTime' ] > 500 ) table-warning @endif">
                                        <td>
                                            <span class="badge bg-info">{{ $metric[ 'middlewareName' ] }}</span>
                                        </td>
                                        <td>
                                            <code
                                                class="text-muted">{{ substr( $metric[ 'endpoint' ], 0, 40 ) }}{{ strlen( $metric[ 'endpoint' ] ) > 40 ? '...' : '' }}</code>
                                        </td>
                                        <td>
                                            @php
                                                $method_class = [
                                                    'GET'    => 'success',
                                                    'POST'   => 'primary',
                                                    'PUT'    => 'warning',
                                                    'DELETE' => 'danger',
                                                    'PATCH'  => 'info'
                                                ];
                                            @endphp
                                            <span
                                                class="badge bg-{{ $method_class[ $metric[ 'method' ] ] ?? 'secondary' }}">{{ $metric[ 'method' ] }}</span>
                                        </td>
                                        <td>
                                            @if ( $metric[ 'responseTime' ] > 1000 )
                                                <span class="text-danger fw-bold"><i class="mdi mdi-alert-circle"></i>
                                                    {{ \App\Helpers\CurrencyHelper::format( $metric[ 'responseTime' ], 2, false ) }}ms</span>
                                            @elseif ( $metric[ 'responseTime' ] > 500 )
                                                <span class="text-warning fw-bold"><i class="mdi mdi-alert"></i>
                                                    {{ \App\Helpers\CurrencyHelper::format( $metric[ 'responseTime' ], 2, false ) }}ms</span>
                                            @else
                                                <span class="text-success"><i class="mdi mdi-check-circle"></i>
                                                    {{ \App\Helpers\CurrencyHelper::format( $metric[ 'responseTime' ], 2, false ) }}ms</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $metric[ 'formattedMemoryUsage' ] }}</span>
                                        </td>
                                        <td>
                                            @if ( $metric[ 'isSuccessful' ] )
                                                <span class="badge bg-success"><i class="mdi mdi-check"></i>
                                                    {{ $metric[ 'statusCode' ] }}</span>
                                            @else
                                                <span class="badge bg-danger"><i class="mdi mdi-close"></i>
                                                    {{ $metric[ 'statusCode' ] }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small
                                                class="text-muted">{{ \Carbon\Carbon::parse( $metric[ 'createdAt' ] )->format( 'd/m H:i:s' ) }}</small>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <div class="empty-state">
                                                <i class="mdi mdi-chart-line-stacked display-4 text-muted mb-3"></i>
                                                <h5 class="text-muted">Nenhuma métrica disponível</h5>
                                                <p class="text-muted">As métricas aparecerão aqui conforme o sistema for
                                                    utilizado.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    @if ( $pagination[ 'total_pages' ] > 1 )
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="text-muted">
                                Mostrando {{ ( ( $pagination[ 'current_page' ] - 1 ) * $pagination[ 'per_page' ] + 1 ) }} a
                                {{ min( $pagination[ 'current_page' ] * $pagination[ 'per_page' ], $pagination[ 'total_items' ] ) }} de
                                {{ $pagination[ 'total_items' ] }} registros
                            </div>
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    @if ( $pagination[ 'current_page' ] > 1 )
                                        <li class="page-item">
                                            <a class="page-link" href="?page={{ $pagination[ 'current_page' ] - 1 }}">
                                                <i class="mdi mdi-chevron-left"></i>
                                            </a>
                                        </li>
                                    @endif

                                    @foreach ( range( max( 1, $pagination[ 'current_page' ] - 2 ), min( $pagination[ 'total_pages' ], $pagination[ 'current_page' ] + 2 ) ) as $page )
                                        <li class="page-item {{ $page == $pagination[ 'current_page' ] ? 'active' : '' }}">
                                            <a class="page-link" href="?page={{ $page }}">{{ $page }}</a>
                                        </li>
                                    @endforeach

                                    @if ( $pagination[ 'current_page' ] < $pagination[ 'total_pages' ] )
                                        <li class="page-item">
                                            <a class="page-link" href="?page={{ $pagination[ 'current_page' ] + 1 }}">
                                                <i class="mdi mdi-chevron-right"></i>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </nav>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Estatísticas por Middleware -->
    @if ( $middlewareStats )
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="header-title mb-3">Performance por Middleware</h4>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Middleware</th>
                                        <th>Requisições</th>
                                        <th>Tempo Médio</th>
                                        <th>Taxa de Erro</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ( $middlewareStats as $stat )
                                        <tr>
                                            <td><strong>{{ $stat[ 'name' ] }}</strong></td>
                                            <td><span class="badge bg-light text-dark">{{ $stat[ 'count' ] }}</span></td>
                                            <td>
                                                @if ( $stat[ 'avg_time' ] > 1000 )
                                                    <span class="text-danger">{{ $stat[ 'avg_time' ] }}ms</span>
                                                @elseif ( $stat[ 'avg_time' ] > 500 )
                                                    <span class="text-warning">{{ $stat[ 'avg_time' ] }}ms</span>
                                                @else
                                                    <span class="text-success">{{ $stat[ 'avg_time' ] }}ms</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ( $stat[ 'error_rate' ] > 5 )
                                                    <span class="text-danger">{{ $stat[ 'error_rate' ] }}%</span>
                                                @elseif ( $stat[ 'error_rate' ] > 1 )
                                                    <span class="text-warning">{{ $stat[ 'error_rate' ] }}%</span>
                                                @else
                                                    <span class="text-success">{{ $stat[ 'error_rate' ] }}%</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ( $stat[ 'avg_time' ] < 500 && $stat[ 'error_rate' ] < 1 )
                                                    <span class="badge bg-success">Ótimo</span>
                                                @elseif ( $stat[ 'avg_time' ] < 1000 && $stat[ 'error_rate' ] < 5 )
                                                    <span class="badge bg-warning">Atenção</span>
                                                @else
                                                    <span class="badge bg-danger">Crítico</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@section( 'styles' )
    <style>
        .widget-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 24px;
        }

        .empty-state {
            padding: 2rem;
        }

        .table-responsive {
            border-radius: 8px;
        }

        .badge {
            font-size: 0.75em;
        }

        code {
            font-size: 0.8em;
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .pagination-sm .page-link {
            padding: 0.25rem 0.5rem;
        }
    </style>
@endsection

@section( 'scripts' )
    <script>
        function refreshMetrics() {
            fetch( '{{ url( "/admin/metrics/realtime" ) }}' )
                .then( response => response.json() )
                .then( data => {
                    if ( data.success ) {
                        location.reload();
                    }
                } )
                .catch( error => console.error( 'Erro ao atualizar métricas:', error ) );
        }

        function cleanupMetrics() {
            const days = prompt( 'Manter métricas dos últimos quantos dias?', '30' );
            if ( days ) {
                fetch( '{{ url( "/admin/metrics/cleanup" ) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: `days=${days}`
                } )
                    .then( response => response.json() )
                    .then( data => {
                        if ( data.success ) {
                            alert( 'Limpeza realizada! ' + data.data.deleted_count + ' registros removidos.' );
                            location.reload();
                        } else {
                            alert( 'Erro: ' + data.message );
                        }
                    } );
            }
        }

        setInterval( refreshMetrics, 30000 );
    </script>
@endsection
