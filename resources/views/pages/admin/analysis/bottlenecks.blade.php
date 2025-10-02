@extends( 'layouts.admin' )

@section( 'breadcrumb' )
    <li class="breadcrumb-item"><a href="{{ url( '/admin/analysis' ) }}">Análise</a></li>
    <li class="breadcrumb-item active">Gargalos</li>
@endsection

@section( 'admin_content' )
    <div class="card">
        <div class="card-header">
            <h5>Identificação de Gargalos - {{ $period[ 'start' ] }} a {{ $period[ 'end' ] }}</h5>
        </div>
        <div class="card-body">
            @if ( isset( $error ) )
                <div class="alert alert-danger">{{ $error }}</div>
            @else
                @if ( isset( $bottlenecks ) && count( $bottlenecks ) > 0 )
                    <div class="alert alert-warning">
                        <h6>Gargalos Identificados:</h6>
                        <ul class="mb-0">
                            @foreach ( $bottlenecks as $bottleneck )
                                <li>{{ $bottleneck[ 'description' ] }}</li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="alert alert-success">Nenhum gargalo crítico identificado no período.</div>
                @endif
            @endif
        </div>
    </div>
@endsection
