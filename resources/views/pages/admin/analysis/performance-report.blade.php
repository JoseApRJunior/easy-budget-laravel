@extends( 'layouts.admin' )

@section( 'breadcrumb' )
    <li class="breadcrumb-item"><a href="{{ url( '/admin/analysis' ) }}">Análise</a></li>
    <li class="breadcrumb-item active">Relatório de Performance</li>
@endsection

@section( 'admin_content' )
    <div class="card">
        <div class="card-header">
            <h5>Relatório de Performance - {{ $period[ 'start' ] }} a {{ $period[ 'end' ] }}</h5>
        </div>
        <div class="card-body">
            @if ( isset( $error ) )
                <div class="alert alert-danger">{{ $error }}</div>
            @else
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Métrica</th>
                                <th>Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ( $report as $metric )
                                <tr>
                                    <td>{{ $metric[ 'name' ] }}</td>
                                    <td>{{ $metric[ 'value' ] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
