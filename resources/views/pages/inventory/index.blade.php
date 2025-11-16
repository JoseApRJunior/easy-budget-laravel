@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Inventário de Produtos</h3>
                    <div class="card-tools">
                        <a href="{{ route('provider.inventory.dashboard') }}" class="btn btn-secondary btn-sm mr-2">
                            <i class="fas fa-chart-bar"></i> Dashboard
                        </a>
                        <a href="{{ route('provider.inventory.movements') }}" class="btn btn-info btn-sm mr-2">
                            <i class="fas fa-exchange-alt"></i> Movimentações
                        </a>
                        <div class="btn-group">
                            <button type="button" class="btn btn-success btn-sm dropdown-toggle" data-toggle="dropdown">
                                <i class="fas fa-plus"></i> Novo Movimento
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="{{ route('provider.inventory.entry') }}">
                                    <i class="fas fa-arrow-down text-success"></i> Entrada
                                </a>
                                <a class="dropdown-item" href="{{ route('provider.inventory.exit') }}">
                                    <i class="fas fa-arrow-up text-danger"></i> Saída
                                </a>
                                <a class="dropdown-item" href="{{ route('provider.inventory.adjust') }}">
                                    <i class="fas fa-sliders-h text-warning"></i> Ajuste
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('provider.inventory.index') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="search">Buscar Produto</label>
                                    <input type="text" name="search" id="search" class="form-control"
                                           value="{{ request('search') }}" placeholder="Nome ou código do produto...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="category">Categoria</label>
                                    <select name="category" id="category" class="form-control">
                                        <option value="">Todas as Categorias</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}"
                                                    {{ request('category') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="status">Status do Estoque</label>
                                    <select name="status" id="status" class="form-control">
                                        <option value="">Todos</option>
                                        <option value="sufficient" {{ request('status') == 'sufficient' ? 'selected' : '' }}>
                                            Estoque OK
                                        </option>
                                        <option value="low" {{ request('status') == 'low' ? 'selected' : '' }}>
                                            Estoque Baixo
                                        </option>
                                        <option value="out" {{ request('status') == 'out' ? 'selected' : '' }}>
                                            Sem Estoque
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div class="d-flex">
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="fas fa-search"></i> Filtrar
                                        </button>
                                        <a href="{{ route('provider.inventory.index') }}" class="btn btn-secondary ml-2">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Categoria</th>
                                    <th>Quantidade Atual</th>
                                    <th>Estoque Mínimo</th>
                                    <th>Valor Unitário</th>
                                    <th>Valor Total</th>
                                    <th>Última Movimentação</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($inventories as $inventory)
                                    @php
                                        $product = $inventory->product;
                                        $currentQuantity = $inventory->current_quantity;
                                        $minQuantity = $inventory->minimum_quantity;
                                        $unitValue = $inventory->unit_value;
                                        $totalValue = $currentQuantity * $unitValue;

                                        // Determinar status do estoque
                                        if ($currentQuantity <= 0) {
                                            $status = 'out';
                                            $statusLabel = 'Sem Estoque';
                                            $statusClass = 'danger';
                                        } elseif ($currentQuantity <= $minQuantity) {
                                            $status = 'low';
                                            $statusLabel = 'Estoque Baixo';
                                            $statusClass = 'warning';
                                        } else {
                                            $status = 'sufficient';
                                            $statusLabel = 'Estoque OK';
                                            $statusClass = 'success';
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $product->name }}</strong><br>
                                            <small class="text-muted">Código: {{ $product->code }}</small>
                                        </td>
                                        <td>{{ $product->category->name ?? 'Sem Categoria' }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-{{ $statusClass }}">
                                                {{ $currentQuantity }}
                                            </span>
                                        </td>
                                        <td class="text-center">{{ $minQuantity }}</td>
                                        <td class="text-right">R$ {{ number_format($unitValue, 2, ',', '.') }}</td>
                                        <td class="text-right">
                                            <strong>R$ {{ number_format($totalValue, 2, ',', '.') }}</strong>
                                        </td>
                                        <td>
                                            @if($inventory->last_movement_at)
                                                {{ \Carbon\Carbon::parse($inventory->last_movement_at)->format('d/m/Y H:i') }}
                                            @else
                                                <span class="text-muted">Nenhuma movimentação</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-{{ $statusClass }}">
                                                {{ $statusLabel }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-primary btn-sm"
                                                        onclick="showMovements({{ $inventory->id }})"
                                                        title="Ver Movimentações">
                                                    <i class="fas fa-list"></i>
                                                </button>
                                                <a href="{{ route('provider.inventory.adjust', ['product_id' => $product->id]) }}"
                                                   class="btn btn-warning btn-sm" title="Ajustar Estoque">
                                                    <i class="fas fa-sliders-h"></i>
                                                </a>
                                                <a href="{{ route('provider.products.show', $product->id) }}"
                                                   class="btn btn-info btn-sm" title="Ver Produto">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <p>Nenhum produto encontrado no inventário</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center">
                        {{ $inventories->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para visualizar movimentações -->
<div class="modal fade" id="movementsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Movimentações do Produto</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="movementsContent">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p>Carregando movimentações...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    .badge {
        font-size: 0.9em;
        padding: 0.5em 0.75em;
    }
    .table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    .btn-group .btn {
        margin-right: 2px;
    }
    .btn-group .btn:last-child {
        margin-right: 0;
    }
</style>
@stop

@section('js')
<script>
    function showMovements(inventoryId) {
        $('#movementsModal').modal('show');

        $.ajax({
            url: '{{ route("provider.inventory.movements") }}',
            method: 'GET',
            data: { inventory_id: inventoryId },
            success: function(response) {
                if (response.html) {
                    $('#movementsContent').html(response.html);
                } else {
                    $('#movementsContent').html('<div class="alert alert-info">Nenhuma movimentação encontrada.</div>');
                }
            },
            error: function() {
                $('#movementsContent').html('<div class="alert alert-danger">Erro ao carregar movimentações.</div>');
            }
        });
    }

    // Auto-submit form após 1 segundo de inatividade na busca
    let searchTimeout;
    $('#search').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            $('form').submit();
        }, 1000);
    });
</script>
@stop