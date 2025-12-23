@extends('layouts.admin')

@section('title', 'Dashboard de Inventário')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="mb-4">
        <h3 class="mb-2">
            <i class="bi bi-speedometer2 me-2"></i>
            Dashboard de Inventário
        </h3>
        <p class="text-muted mb-3">Visão geral do estoque e movimentações</p>
        <nav aria-label="breadcrumb" class="d-none d-md-block">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Inventário</li>
            </ol>
        </nav>
    </div>

    <!-- Ações Rápidas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Ações Rápidas</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('provider.inventory.index') }}" class="btn btn-primary">
                            <i class="bi bi-list me-1"></i> Ver Inventário
                        </a>
                        <a href="{{ route('provider.inventory.movements') }}" class="btn btn-info">
                            <i class="bi bi-arrow-left-right me-1"></i> Movimentações
                        </a>
                        <a href="{{ route('provider.inventory.stock-turnover') }}" class="btn btn-success">
                            <i class="bi bi-graph-up me-1"></i> Giro de Estoque
                        </a>
                        <a href="{{ route('provider.inventory.most-used') }}" class="btn btn-warning">
                            <i class="bi bi-star me-1"></i> Produtos Mais Usados
                        </a>
                        <a href="{{ route('provider.inventory.alerts') }}" class="btn btn-danger">
                            <i class="bi bi-bell me-1"></i> Alertas
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cards de Resumo -->
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h4 class="mb-2">{{ $totalProducts }}</h4>
                    <p class="mb-0">Total de Produtos</p>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="{{ route('provider.inventory.index') }}" class="text-white text-decoration-none">
                        Ver Detalhes <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h4 class="mb-2">{{ $lowStockProducts }}</h4>
                    <p class="mb-0">Estoque Baixo</p>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="{{ route('provider.inventory.index', ['status' => 'low']) }}" class="text-white text-decoration-none">
                        Ver Produtos <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h4 class="mb-2">{{ $highStockProducts }}</h4>
                    <p class="mb-0">Estoque Alto</p>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="{{ route('provider.inventory.index', ['status' => 'high']) }}" class="text-white text-decoration-none">
                        Ver Produtos <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h4 class="mb-2">{{ $outOfStockProducts }}</h4>
                    <p class="mb-0">Sem Estoque</p>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="{{ route('provider.inventory.index', ['status' => 'out']) }}" class="text-white text-decoration-none">
                        Ver Produtos <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h4 class="mb-2">R$ {{ number_format($totalInventoryValue, 2, ',', '.') }}</h4>
                    <p class="mb-0">Valor Total do Estoque</p>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="{{ route('provider.inventory.index') }}" class="text-white text-decoration-none">
                        Ver Inventário <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Produtos com Estoque Alto -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-arrow-up me-2"></i>Produtos com Estoque Alto</h5>
                </div>
                <div class="card-body">
                    @if($highStockItems->count() > 0)
                        <!-- Desktop View -->
                        <div class="table-responsive d-none d-md-block">
                            <table class="table modern-table mb-0">
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
                                            <td><span class="badge bg-success">Estoque Alto</span></td>
                                            <td>
                                                <div class="action-btn-group">
                                                    <a href="{{ route('provider.inventory.movements', $item->product) }}" class="btn btn-info btn-sm">
                                                        <i class="bi bi-list"></i>
                                                    </a>
                                                    <a href="{{ route('provider.inventory.adjust', $item->product) }}" class="btn btn-warning btn-sm">
                                                        <i class="bi bi-sliders"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile View -->
                        <div class="mobile-view d-md-none">
                            @foreach($highStockItems as $item)
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1">{{ $item->product->name }}</h6>
                                            <small class="text-muted">SKU: {{ $item->product->sku }}</small>
                                        </div>
                                        <span class="badge bg-success">Estoque Alto</span>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">Quantidade:</small> {{ $item->quantity }} / Máx: {{ $item->max_quantity }}
                                    </div>
                                    <div class="action-btn-group">
                                        <a href="{{ route('provider.inventory.movements', $item->product) }}" class="btn btn-info btn-sm">
                                            <i class="bi bi-list"></i> Movimentos
                                        </a>
                                        <a href="{{ route('provider.inventory.adjust', $item->product) }}" class="btn btn-warning btn-sm">
                                            <i class="bi bi-sliders"></i> Ajustar
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-success mb-0">
                            <i class="bi bi-check-circle me-2"></i>
                            Nenhum produto com estoque alto no momento.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Produtos com Estoque Baixo -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Produtos com Estoque Baixo</h5>
                </div>
                <div class="card-body">
                    @if($lowStockItems->count() > 0)
                        <!-- Desktop View -->
                        <div class="table-responsive d-none d-md-block">
                            <table class="table modern-table mb-0">
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
                                            <td><span class="badge bg-warning">Estoque Baixo</span></td>
                                            <td>
                                                <div class="action-btn-group">
                                                    <a href="{{ route('provider.inventory.movements', $item->product) }}" class="btn btn-info btn-sm">
                                                        <i class="bi bi-list"></i>
                                                    </a>
                                                    <a href="{{ route('provider.inventory.adjust', $item->product) }}" class="btn btn-success btn-sm">
                                                        <i class="bi bi-plus"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile View -->
                        <div class="mobile-view d-md-none">
                            @foreach($lowStockItems as $item)
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1">{{ $item->product->name }}</h6>
                                            <small class="text-muted">SKU: {{ $item->product->sku }}</small>
                                        </div>
                                        <span class="badge bg-warning">Estoque Baixo</span>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">Quantidade:</small> {{ $item->quantity }} / Mín: {{ $item->min_quantity }}
                                    </div>
                                    <div class="action-btn-group">
                                        <a href="{{ route('provider.inventory.movements', $item->product) }}" class="btn btn-info btn-sm">
                                            <i class="bi bi-list"></i> Movimentos
                                        </a>
                                        <a href="{{ route('provider.inventory.adjust', $item->product) }}" class="btn btn-success btn-sm">
                                            <i class="bi bi-plus"></i> Ajustar
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-success mb-0">
                            <i class="bi bi-check-circle me-2"></i>
                            Nenhum produto com estoque baixo no momento.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Movimentações Recentes -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-arrow-left-right me-2"></i>Movimentações Recentes</h5>
                </div>
                <div class="card-body">
                    @if($recentMovements->count() > 0)
                        <!-- Desktop View -->
                        <div class="table-responsive d-none d-md-block">
                            <table class="table modern-table mb-0">
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
                                                    <span class="badge bg-success">Entrada</span>
                                                @elseif($movement->type === 'exit')
                                                    <span class="badge bg-danger">Saída</span>
                                                @else
                                                    <span class="badge bg-info">{{ ucfirst($movement->type) }}</span>
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

                        <!-- Mobile View -->
                        <div class="mobile-view d-md-none">
                            @foreach($recentMovements as $movement)
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1">{{ $movement->product->name }}</h6>
                                            <small class="text-muted">{{ $movement->created_at->format('d/m/Y H:i') }}</small>
                                        </div>
                                        @if($movement->type === 'entry')
                                            <span class="badge bg-success">Entrada</span>
                                        @elseif($movement->type === 'exit')
                                            <span class="badge bg-danger">Saída</span>
                                        @else
                                            <span class="badge bg-info">{{ ucfirst($movement->type) }}</span>
                                        @endif
                                    </div>
                                    <div>
                                        <small class="text-muted">Quantidade:</small> {{ $movement->quantity }}<br>
                                        <small class="text-muted">Motivo:</small> {{ $movement->reason }}<br>
                                        <small class="text-muted">Usuário:</small> {{ $movement->user->name ?? 'Sistema' }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            Nenhuma movimentação recente.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
