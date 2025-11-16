@extends( 'layouts.app' )

@section( 'content' )
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Movimentações de Inventário</h3>
                    <div class="card-tools">
                        <a href="{{ route( 'provider.inventory.dashboard' ) }}" class="btn btn-secondary btn-sm mr-2">
                            <i class="fas fa-chart-bar"></i> Dashboard
                        </a>
                        <a href="{{ route( 'provider.inventory.index' ) }}" class="btn btn-primary btn-sm mr-2">
                            <i class="fas fa-boxes"></i> Inventário
                        </a>
                        <div class="btn-group">
                            <button type="button" class="btn btn-success btn-sm dropdown-toggle" data-toggle="dropdown">
                                <i class="fas fa-plus"></i> Nova Movimentação
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="{{ route( 'provider.inventory.entry' ) }}">
                                    <i class="fas fa-arrow-down text-success"></i> Entrada
                                </a>
                                <a class="dropdown-item" href="{{ route( 'provider.inventory.exit' ) }}">
                                    <i class="fas fa-arrow-up text-danger"></i> Saída
                                </a>
                                <a class="dropdown-item" href="{{ route( 'provider.inventory.adjust' ) }}">
                                    <i class="fas fa-sliders-h text-warning"></i> Ajuste
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route( 'provider.inventory.movements' ) }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="product_search">Buscar Produto</label>
                                    <input type="text" name="product_search" id="product_search" class="form-control"
                                        value="{{ request( 'product_search' ) }}"
                                        placeholder="Nome ou código do produto...">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="type">Tipo de Movimento</label>
                                    <select name="type" id="type" class="form-control">
                                        <option value="">Todos os Tipos</option>
                                        <option value="entry" {{ request( 'type' ) == 'entry' ? 'selected' : '' }}>
                                            Entrada
                                        </option>
                                        <option value="exit" {{ request( 'type' ) == 'exit' ? 'selected' : '' }}>
                                            Saída
                                        </option>
                                        <option value="adjustment" {{ request( 'type' ) == 'adjustment' ? 'selected' : '' }}>
                                            Ajuste
                                        </option>
                                        <option value="service" {{ request( 'type' ) == 'service' ? 'selected' : '' }}>
                                            Serviço
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="date_from">Data Inicial</label>
                                    <input type="date" name="date_from" id="date_from" class="form-control"
                                        value="{{ request( 'date_from' ) }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="date_to">Data Final</label>
                                    <input type="date" name="date_to" id="date_to" class="form-control"
                                        value="{{ request( 'date_to' ) }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div class="d-flex">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i> Filtrar
                                        </button>
                                        <a href="{{ route( 'provider.inventory.movements' ) }}" class="btn btn-secondary ml-2">
                                            <i class="fas fa-times"></i>
                                        </a>
                                        <button type="button" class="btn btn-success ml-2" onclick="exportMovements()">
                                            <i class="fas fa-file-excel"></i> Exportar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-md-3">
                                            <h5 class="text-success">
                                                <i class="fas fa-arrow-down"></i> Total Entradas
                                            </h5>
                                            <h3 class="font-weight-bold text-success">
                                                {{ $summary[ 'total_entries' ] }}
                                            </h3>
                                            <small class="text-muted">R$
                                                {{ number_format( $summary[ 'total_entry_value' ], 2, ',', '.' ) }}</small>
                                        </div>
                                        <div class="col-md-3">
                                            <h5 class="text-danger">
                                                <i class="fas fa-arrow-up"></i> Total Saídas
                                            </h5>
                                            <h3 class="font-weight-bold text-danger">
                                                {{ $summary[ 'total_exits' ] }}
                                            </h3>
                                            <small class="text-muted">R$
                                                {{ number_format( $summary[ 'total_exit_value' ], 2, ',', '.' ) }}</small>
                                        </div>
                                        <div class="col-md-3">
                                            <h5 class="text-warning">
                                                <i class="fas fa-sliders-h"></i> Total Ajustes
                                            </h5>
                                            <h3 class="font-weight-bold text-warning">
                                                {{ $summary[ 'total_adjustments' ] }}
                                            </h3>
                                            <small class="text-muted">R$
                                                {{ number_format( $summary[ 'total_adjustment_value' ], 2, ',', '.' ) }}</small>
                                        </div>
                                        <div class="col-md-3">
                                            <h5 class="text-info">
                                                <i class="fas fa-cogs"></i> Total Serviços
                                            </h5>
                                            <h3 class="font-weight-bold text-info">
                                                {{ $summary[ 'total_services' ] }}
                                            </h3>
                                            <small class="text-muted">R$
                                                {{ number_format( $summary[ 'total_service_value' ], 2, ',', '.' ) }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Produto</th>
                                    <th>Tipo</th>
                                    <th>Quantidade</th>
                                    <th>Valor Unitário</th>
                                    <th>Valor Total</th>
                                    <th>Saldo Anterior</th>
                                    <th>Saldo Atual</th>
                                    <th>Motivo/Referência</th>
                                    <th>Responsável</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse( $movements as $movement )
                                    @php
                                        $product    = $movement->product;
                                        $quantity   = $movement->quantity;
                                        $unitValue  = $movement->unit_value;
                                        $totalValue = $quantity * $unitValue;

                                        // Definir cores e ícones baseado no tipo
                                        switch ( $movement->type ) {
                                            case 'entry':
                                                $typeLabel = 'Entrada';
                                                $typeClass = 'success';
                                                $icon = 'fa-arrow-down';
                                                break;
                                            case 'exit':
                                                $typeLabel = 'Saída';
                                                $typeClass = 'danger';
                                                $icon = 'fa-arrow-up';
                                                break;
                                            case 'adjustment':
                                                $typeLabel = 'Ajuste';
                                                $typeClass = 'warning';
                                                $icon = 'fa-sliders-h';
                                                break;
                                            case 'service':
                                                $typeLabel = 'Serviço';
                                                $typeClass = 'info';
                                                $icon = 'fa-cogs';
                                                break;
                                            default:
                                                $typeLabel = 'Desconhecido';
                                                $typeClass = 'secondary';
                                                $icon = 'fa-question';
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            {{ \Carbon\Carbon::parse( $movement->created_at )->format( 'd/m/Y H:i' ) }}
                                        </td>
                                        <td>
                                            <strong>{{ $product->name }}</strong><br>
                                            <small class="text-muted">Código: {{ $product->code }}</small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-{{ $typeClass }}">
                                                <i class="fas {{ $icon }}"></i> {{ $typeLabel }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="{{ $movement->type == 'exit' ? 'text-danger' : 'text-success' }}">
                                                {{ $movement->type == 'exit' ? '-' : '+' }}{{ $quantity }}
                                            </span>
                                        </td>
                                        <td class="text-right">R$ {{ number_format( $unitValue, 2, ',', '.' ) }}</td>
                                        <td class="text-right">
                                            <strong>R$ {{ number_format( $totalValue, 2, ',', '.' ) }}</strong>
                                        </td>
                                        <td class="text-center">{{ $movement->previous_quantity }}</td>
                                        <td class="text-center">
                                            <strong>{{ $movement->current_quantity }}</strong>
                                        </td>
                                        <td>
                                            <small>{{ $movement->reason }}</small>
                                            @if( $movement->reference_id && $movement->reference_type )
                                                <br><small class="text-muted">
                                                    Ref: {{ ucfirst( $movement->reference_type ) }} #{{ $movement->reference_id }}
                                                </small>
                                            @endif
                                        </td>
                                        <td>
                                            @if( $movement->user )
                                                {{ $movement->user->name }}
                                            @else
                                                <span class="text-muted">Sistema</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-muted">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <p>Nenhuma movimentação encontrada</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center">
                        {{ $movements->appends( request()->query() )->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section( 'css' )
<style>
    .badge {
        font-size: 0.9em;
        padding: 0.5em 0.75em;
    }

    .table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }

    .card-tools .btn-group {
        margin-left: 10px;
    }

    .summary-card .card-body {
        padding: 1rem;
    }

    .summary-card h3 {
        margin: 0.5rem 0;
        font-size: 2rem;
    }

    .summary-card small {
        display: block;
        margin-top: 0.25rem;
    }
</style>
@stop

@section( 'js' )
<script>
    function exportMovements() {
        const params = new URLSearchParams( window.location.search );
        const exportUrl = '{{ route( "provider.inventory.export" ) }}?' + params.toString();

        // Mostrar loading
        Swal.fire( {
            title: 'Preparando exportação...',
            text: 'Aguarde enquanto preparamos o arquivo Excel.',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        } );

        // Fazer download
        window.location.href = exportUrl;

        // Fechar loading após 2 segundos
        setTimeout( () => {
            Swal.close();
        }, 2000 );
    }

    // Auto-submit form após 1 segundo de inatividade na busca
    let searchTimeout;
    $( '#product_search' ).on( 'input', function () {
        clearTimeout( searchTimeout );
        searchTimeout = setTimeout( function () {
            $( 'form' ).submit();
        }, 1000 );
    } );

    // Validar datas
    $( '#date_from, #date_to' ).on( 'change', function () {
        const dateFrom = $( '#date_from' ).val();
        const dateTo = $( '#date_to' ).val();

        if ( dateFrom && dateTo && dateFrom > dateTo ) {
            Swal.fire( {
                icon: 'warning',
                title: 'Atenção',
                text: 'A data inicial não pode ser maior que a data final.'
            } );
            $( '#date_to' ).val( '' );
        }
    } );
</script>
@stop