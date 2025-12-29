@extends('layouts.app')

@section('title', 'Dashboard de Inventário')

@section('content')
<div class="container-fluid py-1">
    <!-- Cabeçalho -->
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div class="flex-grow-1">
                <h1 class="h4 h3-md mb-1">
                    <i class="bi bi-speedometer2 me-2"></i>
                    <span class="d-none d-sm-inline">Dashboard de Inventário</span>
                    <span class="d-sm-none">Inventário</span>
                </h1>
            </div>
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('provider.dashboard') }}">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        Dashboard de Inventário
                    </li>
                </ol>
            </nav>
        </div>
        <p class="text-muted mb-0 small">Visão geral do seu estoque e movimentações com atalhos de gestão.</p>
    </div>

    <!-- Ações Rápidas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-lightning-charge me-2 text-warning"></i>Ações Rápidas</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <x-button type="link" :href="route('provider.inventory.index')" icon="list" label="Ver Inventário" size="sm" />
                        <x-button type="link" :href="route('provider.inventory.movements')" variant="info" icon="arrow-left-right" label="Movimentações" size="sm" />
                        <x-button type="link" :href="route('provider.inventory.stock-turnover')" variant="success" icon="graph-up" label="Giro de Estoque" size="sm" />
                        <x-button type="link" :href="route('provider.inventory.most-used')" variant="warning" icon="star" label="Produtos Mais Usados" size="sm" />
                        <x-button type="link" :href="route('provider.inventory.alerts')" variant="danger" icon="bell" label="Alertas" size="sm" />
                        <x-button type="link" :href="route('provider.inventory.report')" variant="secondary" icon="file-earmark-text" label="Relatórios" size="sm" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cards de Métricas -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-primary bg-gradient me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-box text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold">TOTAL PRODUTOS</h6>
                    </div>
                    <h3 class="mb-1 fw-bold">{{ $totalProducts }}</h3>
                    <a href="{{ route('provider.inventory.index') }}" class="text-primary small text-decoration-none">Ver todos <i class="bi bi-chevron-right ms-1"></i></a>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-warning bg-gradient me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-exclamation-triangle text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold">ESTOQUE BAIXO</h6>
                    </div>
                    <h3 class="mb-1 fw-bold text-warning">{{ $lowStockProducts }}</h3>
                    <a href="{{ route('provider.inventory.index', ['status' => 'low']) }}" class="text-warning small text-decoration-none">Ver produtos <i class="bi bi-chevron-right ms-1"></i></a>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-danger bg-gradient me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-x-circle text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold">SEM ESTOQUE</h6>
                    </div>
                    <h3 class="mb-1 fw-bold text-danger">{{ $outOfStockProducts }}</h3>
                    <a href="{{ route('provider.inventory.index', ['status' => 'out']) }}" class="text-danger small text-decoration-none">Ver produtos <i class="bi bi-chevron-right ms-1"></i></a>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-info bg-gradient me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-bookmark-check text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold">PRODUTOS RESERVADOS</h6>
                    </div>
                    <h3 class="mb-1 fw-bold text-info">{{ $reservedItemsCount }}</h3>
                    <div class="text-muted small">Total: {{ $totalReservedQuantity }} unidades</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 bg-success bg-gradient text-white">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-white bg-opacity-25 me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-currency-dollar text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-white text-opacity-75 mb-0 small fw-bold text-uppercase">Valor em Estoque</h6>
                    </div>
                    <h3 class="mb-1 fw-bold">R$ {{ number_format($totalInventoryValue, 2, ',', '.') }}</h3>
                    <p class="text-white text-opacity-75 small-text mb-0">Custo total aproximado.</p>
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
