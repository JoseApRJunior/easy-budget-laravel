@extends( 'layouts.admin' )

@section( 'breadcrumb' )
    <li class="breadcrumb-item"><a href="{{ url( '/admin/analysis' ) }}">Análise</a></li>
    <li class="breadcrumb-item active">Tendências</li>
@endsection

@section( 'admin_content' )
    <div class="card">
        <div class="card-header">
            <h5>Análise de Tendências - {{ $period[ 'start' ] }} a {{ $period[ 'end' ] }}</h5>
        </div>
        <div class="card-body">
            @if ( isset( $error ) )
                <div class="alert alert-danger">{{ $error }}</div>
            @else
                <canvas id="trendsChart" width="400" height="200"></canvas>
            @endif
        </div>
    </div>
@endsection

@section( 'scripts' )
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener( 'DOMContentLoaded', function () {
            @if ( !isset( $error ) && isset( $chartData ) )
                const ctx = document.getElementById( 'trendsChart' ).getContext( '2d' );
                new Chart( ctx, {
                    type: 'line',
                    data: {
                        labels: @json( $chartData[ 'labels' ] ),
                        datasets: @json( $chartData[ 'datasets' ] )
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                } );
            @endif
        } );
    </script>
@endsection
