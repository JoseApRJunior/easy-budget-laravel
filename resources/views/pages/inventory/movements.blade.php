@extends('layouts.admin')

@section('title', 'Movimentações de Estoque')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Movimentações de Estoque</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('inventory.dashboard') }}">Inventário</a></li>
                    <li class="breadcrumb-item active">Movimentações</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Filtros -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Filtros</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('inventory.movements') }}">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="product_id">Produto</label>
                                    <select name="product_id" id="product_id" class="form-control select2">
                                        <option value="">Todos os Produtos</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                                {{ $product->sku }} - {{ $product->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="type">Tipo</label>
                                    <select name="type" id="type" class="form-control">
                                        <option value="">Todos</option>
                                        <option value="entry" {{ request('type') == 'entry' ? 'selected' : '' }}>Entrada</option>
                                        <option value="exit" {{ request('type') == 'exit' ? 'selected' : '' }}>Saída</option>
                                        <option value="adjustment" {{ request('type') == 'adjustment' ? 'selected' : '' }}>Ajuste</option>
                                        <option value="reservation" {{ request('type') == 'reservation' ? 'selected' : '' }}>Reserva</option>
                                        <option value="cancellation" {{ request('type') == 'cancellation' ? 'selected' : '' }}>Cancelamento</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="start_date">Data Inicial</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control" value="{{ request('start_date') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="end_date">Data Final</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control" value="{{ request('end_date') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="fas fa-search"></i> Filtrar
                                        </button>
                                        <a href="{{ route('inventory.movements') }}" class="btn btn-secondary btn-block">
                                            <i class="fas fa-times"></i> Limpar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumo -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="card bg-success">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <h4>{{ number_format($summary['total_entries'], 2, ',', '.') }}</h4>
                            <p>Total de Entradas</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card bg-danger">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <h4>{{ number_format($summary['total_exits'], 2, ',', '.') }}</h4>
                            <p>Total de Saídas</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card bg-info">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <h4>{{ $movements->total() }}</h4>
                            <p>Total de Movimentações</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card bg-warning">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <h4>{{ number_format($summary['balance'], 2, ',', '.') }}</h4>
                            <p>Saldo do Período</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Movimentações -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Movimentações de Estoque</h3>
                    <div class="card-tools">
                        <a href="{{ route('inventory.export-movements') }}?{{ request()->getQueryString() }}" class="btn btn-sm btn-success">
                            <i class="fas fa-file-excel"></i> Exportar
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($movements->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Data/Hora</th>
                                        <th>Produto</th>
                                        <th>SKU</th>
                                        <th>Tipo</th>
                                        <th>Quantidade</th>
                                        <th>Saldo Anterior</th>
                                        <th>Saldo Atual</th>
                                        <th>Motivo</th>
                                        <th>Referência</th>
                                        <th>Usuário</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($movements as $movement)
                                        <tr>
                                            <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                                            <td>
                                                <a href="{{ route('inventory.show', $movement->product) }}">
                                                    {{ $movement->product->name }}
                                                </a>
                                            </td>
                                            <td>{{ $movement->product->sku }}</td>
                                            <td>
                                                @if($movement->type === 'entry')
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-plus"></i> Entrada
                                                    </span>
                                                @elseif($movement->type === 'exit')
                                                    <span class="badge badge-danger">
                                                        <i class="fas fa-minus"></i> Saída
                                                    </span>
                                                @elseif($movement->type === 'adjustment')
                                                    <span class="badge badge-warning">
                                                        <i class="fas fa-tools"></i> Ajuste
                                                    </span>
                                                @elseif($movement->type === 'reservation')
                                                    <span class="badge badge-info">
                                                        <i class="fas fa-lock"></i> Reserva
                                                    </span>
                                                @elseif($movement->type === 'cancellation')
                                                    <span class="badge badge-secondary">
                                                        <i class="fas fa-undo"></i> Cancelamento
                                                    </span>
                                                @else
                                                    <span class="badge badge-light">
                                                        {{ ucfirst($movement->type) }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($movement->type === 'entry')
                                                    <span class="text-success">+{{ number_format($movement->quantity, 2, ',', '.') }}</span>
                                                @elseif($movement->type === 'exit' || $movement->type === 'subtraction')
                                                    <span class="text-danger">-{{ number_format($movement->quantity, 2, ',', '.') }}</span>
                                                @else
                                                    {{ number_format($movement->quantity, 2, ',', '.') }}
                                                @endif
                                            </td>
                                            <td>{{ number_format($movement->previous_quantity, 2, ',', '.') }}</td>
                                            <td>
                                                @php
                                                    $currentQuantity = $movement->previous_quantity;
                                                    if ($movement->type === 'entry') {
                                                        $currentQuantity += $movement->quantity;
                                                    } elseif ($movement->type === 'exit' || $movement->type === 'subtraction') {
                                                        $currentQuantity -= $movement->quantity;
                                                    }
                                                @endphp
                                                {{ number_format($currentQuantity, 2, ',', '.') }}
                                            </td>
                                            <td>
                                                <small>{{ Str::limit($movement->reason, 50) }}</small>
                                                @if(strlen($movement->reason) > 50)
                                                    <button type="button" class="btn btn-xs btn-link" data-toggle="modal" data-target="#reasonModal{{ $movement->id }}">
                                                        Ver mais
                                                    </button>
                                                @endif
                                            </td>
                                            <td>
                                                @if($movement->reference_type && $movement->reference_id)
                                                    @if($movement->reference_type === 'budget')
                                                        <a href="{{ route('budgets.show', $movement->reference_id) }}" class="btn btn-xs btn-info">
                                                            Orçamento #{{ $movement->reference_id }}
                                                        </a>
                                                    @elseif($movement->reference_type === 'service')
                                                        <a href="{{ route('services.show', $movement->reference_id) }}" class="btn btn-xs btn-info">
                                                            Serviço #{{ $movement->reference_id }}
                                                        </a>
                                                    @else
                                                        {{ ucfirst($movement->reference_type) }} #{{ $movement->reference_id }}
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <small>{{ $movement->user->name ?? 'Sistema' }}</small>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('inventory.movements', ['product_id' => $movement->product_id]) }}" 
                                                       class="btn btn-sm btn-info" 
                                                       title="Ver movimentações do produto">
                                                        <i class="fas fa-list"></i>
                                                    </a>
                                                    @if($movement->product)
                                                        <a href="{{ route('inventory.adjustStockForm', $movement->product) }}" 
                                                           class="btn btn-sm btn-success" 
                                                           title="Ajustar estoque">
                                                            <i class="fas fa-plus"></i>
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Modal para motivo completo -->
                                        @if(strlen($movement->reason) > 50)
                                            <div class="modal fade" id="reasonModal{{ $movement->id }}" tabindex="-1" role="dialog">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Motivo Completo</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>{{ $movement->reason }}</p>
                                                            <small class="text-muted">
                                                                Registrado em: {{ $movement->created_at->format('d/m/Y H:i') }}
                                                            </small>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-center">
                            {{ $movements->appends(request()->except('page'))->links() }}
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Nenhuma movimentação encontrada com os filtros aplicados.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('.select2').select2({
        placeholder: 'Selecione um produto',
        allowClear: true
    });
});
</script>
@endsection