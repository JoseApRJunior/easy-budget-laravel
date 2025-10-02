@extends( 'layout.pdf' )

@section( 'content' )
    <div class="container-fluid">
        {{-- Cabeçalho --}}
        <div class="row">
            <div class="col-8">
                <img src="{{ $company[ 'logo_url' ] }}" alt="Logo" style="max-height: 80px;" class="mb-4">
                <h4 class="text-dark">{{ $company[ 'name' ] }}</h4>
                <p class="text-muted mb-0">{{ $company[ 'address' ] }}</p>
                <p class="text-muted mb-0">CNPJ: {{ $company[ 'cnpj' ] }}</p>
            </div>
            <div class="col-4 text-end">
                <h2 class="text-primary">Relatório de Serviços</h2>
                <p class="text-muted mb-0">Data: {{ now()->format( 'd/m/Y' ) }}</p>
                <p class="text-muted mb-0">Horário: {{ now()->format( 'H:i:s' ) }}</p>
            </div>
        </div>

        <hr class="my-4 bg-secondary">

        {{-- Tabela de Serviços --}}
        <div class="card border-0">
            <div class="card-body p-0">
                <table class="table table-sm table-striped table-bordered">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 40%; text-align: left;">Nome</th>
                            <th style="width: 40%; text-align: left;">Descrição</th>
                            <th style="width: 20%; text-align: right;">Preço</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ( $services as $service )
                            <tr>
                                <td>{{ $service[ 'name' ] }}</td>
                                <td>{{ $service[ 'description' ] }}</td>
                                <td class="text-end">R$ {{ number_format( $service[ 'price' ], 2, ',', '.' ) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">
                                    Nenhum serviço encontrado para os filtros aplicados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Totais e Estatísticas --}}
        @if ( count( $services ) > 0 )
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card border-0 bg-light">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-4 text-center">
                                    <h6 class="text-muted mb-1">Total de Serviços</h6>
                                    <h4 class="text-dark mb-0">{{ count( $services ) }}</h4>
                                </div>
                                <div class="col-4 text-center">
                                    <h6 class="text-muted mb-1">Valor Total</h6>
                                    <h4 class="text-dark mb-0">R$
                                        {{ number_format( collect( $services )->sum( 'price' ), 2, ',', '.' ) }}</h4>
                                </div>
                                <div class="col-4 text-center">
                                    <h6 class="text-muted mb-1">Preço Médio</h6>
                                    <h4 class="text-dark mb-0">R$
                                        {{ number_format( collect( $services )->avg( 'price' ), 2, ',', '.' ) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>
@endsection
