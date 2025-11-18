@extends('layouts.admin')

@section('title', 'Dashboard de Inventário')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Dashboard de Inventário</h1>
        </div>
    </div>

    <!-- Ações Rápidas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Ações Rápidas</h3>
                </div>
                <div class="card-body">
                    <div class="btn-group" role="group">
                        <a href="{{ route('provider.inventory.index') }}" class="btn btn-primary">
                            <i class="fas fa-list"></i> Ver Inventário
                        </a>
                        <a href="{{ route('provider.inventory.movements') }}" class="btn btn-info">
                            <i class="fas fa-exchange-alt"></i> Movimentações
                        </a>
                        <a href="{{ route('provider.inventory.stock-turnover') }}" class="btn btn-success">
                            <i class="fas fa-chart-line"></i> Giro de Estoque
                        </a>
                        <a href="{{ route('provider.inventory.most-used') }}" class="btn btn-warning">
                            <i class="fas fa-star"></i> Produtos Mais Usados
                        </a>
                        <a href="{{ route('provider.inventory.alerts') }}" class="btn btn-danger">
                            <i class="fas fa-bell"></i> Alertas
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cards de Resumo -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="card bg-info">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <h4>{{ $totalProducts }}</h4>
                            <p>Total de Produtos</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('provider.inventory.index') }}" class="text-white">
                        Ver Detalhes <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="card bg-warning">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <h4>{{ $lowStockProducts }}</h4>
                            <p>Produtos com Estoque Baixo</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('provider.inventory.index', ['status' => 'low']) }}" class="text-white">
                        Ver Produtos <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="card bg-success">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <h4>{{ $highStockProducts }}</h4>
                            <p>Produtos com Estoque Alto</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('provider.inventory.index', ['status' => 'high']) }}" class="text-white">
                        Ver Produtos <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="card bg-danger">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <h4>{{ $outOfStockProducts }}</h4>
                            <p>Produtos Sem Estoque</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('provider.inventory.index', ['status' => 'out']) }}" class="text-white">
                        Ver Produtos <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="card bg-primary">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <h4>R$ {{ number_format($totalInventoryValue, 2, ',', '.') }}</h4>
                            <p>Valor Total do Estoque</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('provider.inventory.index') }}" class="text-white">
                        Ver Inventário <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Produtos com Estoque Alto -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Produtos com Estoque Alto</h3>
                    <div class="card-tools">
                        <a href="{{ route('provider.inventory.index', ['status' => 'high']) }}" class="btn btn-sm btn-outline-primary">
                            Ver Todos
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($highStockItems->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>SKU</th>
                                        <th>Produto</th>
                                        <th>Quantidade Atual</th>
                                        <th>Estoque Máximo</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($highStockItems as $item)
                                        <tr>
                                            <td>{{ $item->product->sku }}</td>
                                            <td>{{ $item->product->name }}</td>
                                            <td>{{ $item->quantity }}</td>
                                            <td>{{ $item->max_quantity }}</td>
                                            <td>
                                                <span class="badge badge-success">
                                                    Estoque Alto
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('provider.inventory.movements', $item->product) }}" 
                                                       class="btn btn-sm btn-info">
                                                        <i class="fas fa-list"></i> Movimentos
                                                    </a>
                                                    <a href="{{ route('provider.inventory.adjust', $item->product) }}" 
                                                       class="btn btn-sm btn-warning">
                                                        <i class="fas fa-minus"></i> Ajustar
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            Nenhum produto com estoque alto no momento.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Produtos com Estoque Baixo -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Produtos com Estoque Baixo</h3>
                    <div class="card-tools">
                        <a href="{{ route('provider.inventory.index', ['status' => 'low']) }}" class="btn btn-sm btn-outline-primary">
                            Ver Todos
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($lowStockItems->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>SKU</th>
                                        <th>Produto</th>
                                        <th>Quantidade Atual</th>
                                        <th>Estoque Mínimo</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($lowStockItems as $item)
                                        <tr>
                                            <td>{{ $item->product->sku }}</td>
                                            <td>{{ $item->product->name }}</td>
                                            <td>{{ $item->quantity }}</td>
                                            <td>{{ $item->min_quantity }}</td>
                                            <td>
                                                <span class="badge badge-warning">
                                                    Estoque Baixo
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('provider.inventory.movements', $item->product) }}" 
                                                       class="btn btn-sm btn-info">
                                                        <i class="fas fa-list"></i> Movimentos
                                                    </a>
                                                    <a href="{{ route('provider.inventory.adjust', $item->product) }}" 
                                                       class="btn btn-sm btn-success">
                                                        <i class="fas fa-plus"></i> Ajustar
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            Nenhum produto com estoque baixo no momento.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Movimentações Recentes -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Movimentações Recentes</h3>
                    <div class="card-tools">
                        <a href="{{ route('provider.inventory.movements') }}" class="btn btn-sm btn-outline-primary">
                            Ver Todas
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($recentMovements->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Produto</th>
                                        <th>Tipo</th>
                                        <th>Quantidade</th>
                                        <th>Motivo</th>
                                        <th>Usuário</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentMovements as $movement)
                                        <tr>
                                            <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                                            <td>{{ $movement->product->name }}</td>
                                            <td>
                                                @if($movement->type === 'entry')
                                                    <span class="badge badge-success">Entrada</span>
                                                @elseif($movement->type === 'exit')
                                                    <span class="badge badge-danger">Saída</span>
                                                @else
                                                    <span class="badge badge-info">{{ ucfirst($movement->type) }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $movement->quantity }}</td>
                                            <td>{{ $movement->reason }}</td>
                                            <td>{{ $movement->user->name ?? 'Sistema' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Nenhuma movimentação recente.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection