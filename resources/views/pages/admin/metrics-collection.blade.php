<x-app-layout title="Coleta de Métricas">
    <x-layout.page-header
        title="Coleta de Métricas"
        icon="graph-up"
        :breadcrumb-items="[
            'Admin' => '#',
            'Coleta de Métricas' => '#'
        ]">
    </x-layout.page-header>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ $title }}</h5>
                    <div>
                        <button class="btn btn-primary btn-sm" onclick="collectMetrics()">
                            <i class="bi bi-arrow-clockwise"></i> Coletar Agora
                        </button>
                        <button class="btn btn-warning btn-sm" onclick="cleanupMetrics()">
                            <i class="bi bi-trash"></i> Limpeza
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h6>Total de Métricas</h6>
                                    <h3>{{ $collection_stats[ 'total_metrics' ] ?? 0 }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h6>Middlewares Únicos</h6>
                                    <h3>{{ $collection_stats[ 'unique_middlewares' ] ?? 0 }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h6>Métrica Mais Antiga</h6>
                                    <small>{{ $collection_stats[ 'oldest_metric' ] ?? 'N/A' }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-secondary text-white">
                                <div class="card-body">
                                    <h6>Métrica Mais Recente</h6>
                                    <small>{{ $collection_stats[ 'newest_metric' ] ?? 'N/A' }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6>Métricas Recentes</h6>
                        <small class="text-muted">
                            Mostrando {{ ( $pagination->currentPage() - 1 ) * $pagination->perPage() + 1 }} -
                            {{ $pagination->currentPage() * $pagination->perPage() > $pagination->total() ? $pagination->total() : $pagination->currentPage() * $pagination->perPage() }}
                            de {{ $pagination->total() }} registros
                        </small>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Middleware</th>
                                    <th>Tempo (ms)</th>
                                    <th>Status</th>
                                    <th>Data/Hora</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ( $recent_metrics as $metric )
                                    <tr>
                                        <td>{{ $metric->middleware_name }}</td>
                                        <td>{{ $metric->response_time }}</td>
                                        <td>
                                            <span
                                                class="badge bg-{{ $metric->status_code >= 200 && $metric->status_code < 300 ? 'success' : 'danger' }}">
                                                {{ $metric->status_code }}
                                            </span>
                                        </td>
                                        <td>{{ $metric->created_at }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    {{ $recent_metrics->links() }}
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function collectMetrics() {
                fetch( '{{ url( "/admin/metrics/record" ) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify( {
                        middleware_name: 'ManualCollection',
                        response_time: Math.floor( Math.random() * 100 ),
                        status_code: 200
                    } )
                } ).then( response => response.json() )
                    .then( data => {
                        alert( data.message );
                        location.reload();
                    } );
            }

            function cleanupMetrics() {
                if ( confirm( 'Remover métricas antigas (>30 dias)?' ) ) {
                    fetch( '{{ url( "/admin/metrics/cleanup" ) }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    } )
                        .then( response => response.json() )
                        .then( data => {
                            alert( data.message );
                            location.reload();
                        } );
                }
            }
        </script>
    @endpush
</x-app-layout>
