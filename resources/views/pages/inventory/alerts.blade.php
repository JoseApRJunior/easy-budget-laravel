@extends('layouts.admin')

@section('title', 'Alertas de Estoque')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <x-page-header
        title="Alertas de Estoque"
        icon="bell"
        :breadcrumb-items="[
            'Inventário' => route('provider.inventory.dashboard'),
            'Alertas' => '#'
        ]">
        <p class="text-muted mb-3">Produtos com estoque baixo ou alto que requerem atenção</p>
    </x-page-header>

    <!-- Resumo dos Alertas -->
    <div class="row">
        <div class="col-lg-6 col-12">
            <div class="card bg-warning">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <h4>{{ $lowStockProducts->total() }}</h4>
                            <p>Produtos com Estoque Baixo</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="#low-stock-section" class="text-dark">
                        Ver Lista <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-12">
            <div class="card bg-info">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <h4>{{ $highStockProducts->total() }}</h4>
                            <p>Produtos com Estoque Alto</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="#high-stock-section" class="text-dark">
                        Ver Lista <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Produtos com Estoque Baixo -->
    <div class="row mt-4" id="low-stock-section">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning">
                    <div class="row align-items-center">
                        <div class="col-12 col-md-8">
                            <h5 class="mb-0">
                                <i class="bi bi-exclamation-triangle me-2"></i>Produtos com Estoque Baixo
                            </h5>
                        </div>
                        <div class="col-12 col-md-4 text-md-end mt-2 mt-md-0">
                            <span class="badge bg-dark">{{ $lowStockProducts->total() }} produtos</span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if($lowStockProducts->count() > 0)
                        <!-- Desktop View -->
                        <div class="table-responsive d-none d-md-block">
                            <table class="table modern-table mb-0">
                                <thead>
                                    <tr>
                                        <th>SKU</th>
                                        <th>Produto</th>
                                        <th>Categoria</th>
                                        <th>Estoque Atual</th>
                                        <th>Reservado</th>
                                        <th>Disponível</th>
                                        <th>Estoque Mínimo</th>
                                        <th>Diferença</th>
                                        <th>Status</th>
                                        <th>Última Movimentação</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($lowStockProducts as $item)
                                        @php
                                            $difference = $item->min_quantity - $item->quantity;
                                            $urgency = $difference > $item->min_quantity * 0.5 ? 'high' : 'medium';
                                        @endphp
                                        <tr>
                                            <td>{{ $item->product->sku }}</td>
                                            <td>
                                                <strong>{{ $item->product->name }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $item->product->description }}</small>
                                            </td>
                                            <td>{{ $item->product->category->name ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge badge-{{ $item->quantity <= 0 ? 'danger' : 'warning' }}">
                                                    {{ $item->quantity }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-info">{{ $item->reserved_quantity }}</span>
                                            </td>
                                            <td>
                                                <span class="fw-bold">{{ $item->available_quantity }}</span>
                                            </td>
                                            <td>
                                                <span class="badge badge-info">{{ $item->min_quantity }}</span>
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $urgency === 'high' ? 'danger' : 'warning' }}">
                                                    -{{ $difference }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($item->quantity <= 0)
                                                    <span class="badge badge-danger">
                                                        <i class="fas fa-times-circle"></i> ESGOTADO
                                                    </span>
                                                @elseif($urgency === 'high')
                                                    <span class="badge badge-danger">
                                                        <i class="fas fa-exclamation-circle"></i> CRÍTICO
                                                    </span>
                                                @else
                                                    <span class="badge badge-warning">
                                                        <i class="fas fa-exclamation-triangle"></i> BAIXO
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $lastMovement = \App\Models\InventoryMovement::where('product_id', $item->product_id)
                                                        ->where('tenant_id', auth()->user()->tenant_id)
                                                        ->orderBy('created_at', 'desc')
                                                        ->first();
                                                @endphp
                                                @if($lastMovement)
                                                    {{ $lastMovement->created_at->format('d/m/Y H:i') }}
                                                    <br>
                                                    <small class="text-muted">
                                                        {{ ucfirst($lastMovement->type) }}: {{ $lastMovement->quantity }}
                                                    </small>
                                                @else
                                                    <span class="text-muted">Nenhuma movimentação</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('provider.inventory.show', $item->product) }}" 
                                                       class="btn btn-sm btn-info" 
                                                       title="Ver Produto">
                                                        <i class="fas fa-box"></i>
                                                    </a>
                                                    <a href="{{ route('provider.inventory.movements', ['product_id' => $item->product_id]) }}" 
                                                       class="btn btn-sm btn-primary" 
                                                       title="Ver Movimentações">
                                                        <i class="fas fa-list"></i>
                                                    </a>
                                                    <a href="{{ route('provider.inventory.adjust', $item->product) }}" 
                                                       class="btn btn-sm btn-success" 
                                                       title="Ajustar Estoque">
                                                        <i class="fas fa-plus"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-center">
                            {{ $lowStockProducts->links() }}
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

    <!-- Produtos com Estoque Alto -->
    <div class="row mt-4" id="high-stock-section">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info">
                    <div class="row align-items-center">
                        <div class="col-12 col-md-8">
                            <h5 class="mb-0">
                                <i class="bi bi-arrow-up me-2"></i>Produtos com Estoque Alto
                            </h5>
                        </div>
                        <div class="col-12 col-md-4 text-md-end mt-2 mt-md-0">
                            <span class="badge bg-dark">{{ $highStockProducts->total() }} produtos</span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if($highStockProducts->count() > 0)
                        <!-- Desktop View -->
                        <div class="table-responsive d-none d-md-block">
                            <table class="table modern-table mb-0">
                                <thead>
                                    <tr>
                                        <th>SKU</th>
                                        <th>Produto</th>
                                        <th>Categoria</th>
                                        <th>Estoque Atual</th>
                                        <th>Reservado</th>
                                        <th>Disponível</th>
                                        <th>Estoque Máximo</th>
                                        <th>Excesso</th>
                                        <th>Status</th>
                                        <th>Última Movimentação</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($highStockProducts as $item)
                                        @php
                                            $excess = $item->quantity - $item->max_quantity;
                                            $excessPercentage = ($excess / $item->max_quantity) * 100;
                                        @endphp
                                        <tr>
                                            <td>{{ $item->product->sku }}</td>
                                            <td>
                                                <strong>{{ $item->product->name }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $item->product->description }}</small>
                                            </td>
                                            <td>{{ $item->product->category->name ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge badge-info">{{ $item->quantity }}</span>
                                            </td>
                                            <td>
                                                <span class="text-info">{{ $item->reserved_quantity }}</span>
                                            </td>
                                            <td>
                                                <span class="fw-bold">{{ $item->available_quantity }}</span>
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary">{{ $item->max_quantity }}</span>
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $excessPercentage > 50 ? 'danger' : 'warning' }}">
                                                    +{{ $excess }} ({{ number_format($excessPercentage, 1) }}%)
                                                </span>
                                            </td>
                                            <td>
                                                @if($excessPercentage > 50)
                                                    <span class="badge badge-danger">
                                                        <i class="fas fa-arrow-up"></i> EXCESSIVO
                                                    </span>
                                                @else
                                                    <span class="badge badge-info">
                                                        <i class="fas fa-arrow-up"></i> ALTO
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $lastMovement = \App\Models\InventoryMovement::where('product_id', $item->product_id)
                                                        ->where('tenant_id', auth()->user()->tenant_id)
                                                        ->orderBy('created_at', 'desc')
                                                        ->first();
                                                @endphp
                                                @if($lastMovement)
                                                    {{ $lastMovement->created_at->format('d/m/Y H:i') }}
                                                    <br>
                                                    <small class="text-muted">
                                                        {{ ucfirst($lastMovement->type) }}: {{ $lastMovement->quantity }}
                                                    </small>
                                                @else
                                                    <span class="text-muted">Nenhuma movimentação</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('provider.inventory.show', $item->product) }}" 
                                                       class="btn btn-sm btn-info" 
                                                       title="Ver Produto">
                                                        <i class="fas fa-box"></i>
                                                    </a>
                                                    <a href="{{ route('provider.inventory.movements', ['product_id' => $item->product_id]) }}" 
                                                       class="btn btn-sm btn-primary" 
                                                       title="Ver Movimentações">
                                                        <i class="fas fa-list"></i>
                                                    </a>
                                                    <a href="{{ route('provider.inventory.adjust', $item->product) }}" 
                                                       class="btn btn-sm btn-warning" 
                                                       title="Ajustar Estoque">
                                                        <i class="fas fa-minus"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-center">
                            {{ $highStockProducts->links() }}
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

    <!-- Ações em Massa -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-cogs"></i> Ações Rápidas
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Produtos com Estoque Baixo</h5>
                            <div class="d-grid gap-2">
                                <a href="{{ route('provider.inventory.index', ['status' => 'low']) }}" class="btn btn-warning">
                                    <i class="fas fa-list"></i> Ver Todos os Produtos com Baixo Estoque
                                </a>
                                <button class="btn btn-success" onclick="window.easyAlert ? easyAlert.info('Funcionalidade de geração de pedido de compra em desenvolvimento') : alert('Funcionalidade de geração de pedido de compra em desenvolvimento')">
                                    <i class="fas fa-shopping-cart"></i> Gerar Pedido de Compra
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5>Produtos com Estoque Alto</h5>
                            <div class="d-grid gap-2">
                                <a href="{{ route('provider.inventory.index', ['status' => 'high']) }}" class="btn btn-info">
                                    <i class="fas fa-list"></i> Ver Todos os Produtos com Alto Estoque
                                </a>
                                <button class="btn btn-warning" onclick="window.easyAlert ? easyAlert.info('Funcionalidade de promoção de vendas em desenvolvimento') : alert('Funcionalidade de promoção de vendas em desenvolvimento')">
                                    <i class="fas fa-tag"></i> Criar Promoção de Vendas
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection