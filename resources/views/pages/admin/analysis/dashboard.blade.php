@extends( 'layouts.admin' )

@section( 'admin_content' )
    <x-layout.page-header
        title="Análise Histórica"
        icon="graph-up"
        :breadcrumb-items="[
            'Admin' => url('/admin'),
            'Análise' => '#'
        ]">
    </x-layout.page-header>
                <div class="card-body">
                    @if ( isset( $error ) )
                        <div class="alert alert-danger">{{ $error }}</div>
                    @else
                        <div class="row">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h6>Total de Métricas</h6>
                                        <h3>{{ $dashboard[ 'total_metrics' ] ?? 0 }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h6>Performance Média</h6>
                                        <h3>{{ $dashboard[ 'avg_performance' ] ?? '0ms' }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
@endsection
