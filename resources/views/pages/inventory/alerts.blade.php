@extends('layouts.app')

@section('title', 'Alertas de Estoque')

@section('content')
<div class="container-fluid py-1">
    <!-- Page Header -->
    <x-page-header
        title="Alertas de Estoque"
        icon="bell"
        :breadcrumb-items="[
            'Inventário' => route('provider.inventory.dashboard'),
            'Alertas' => '#'
        ]">
        <p class="text-muted small mb-0">Produtos com estoque baixo ou excessivo que requerem atenção imediata</p>
    </x-page-header>

    <!-- Resumo dos Alertas -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning bg-opacity-10 p-3 rounded-3 me-3">
                            <i class="bi bi-exclamation-triangle text-warning fs-3"></i>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold">{{ $lowStockProducts->total() }}</h3>
                            <p class="text-muted small mb-0">Produtos com Estoque Baixo</p>
                        </div>
                        <div class="ms-auto">
                            <a href="#low-stock-section" class="btn btn-sm btn-outline-warning border-0">
                                <i class="bi bi-arrow-down-circle"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-info bg-opacity-10 p-3 rounded-3 me-3">
                            <i class="bi bi-arrow-up-circle text-info fs-3"></i>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold">{{ $highStockProducts->total() }}</h3>
                            <p class="text-muted small mb-0">Produtos com Estoque Alto</p>
                        </div>
                        <div class="ms-auto">
                            <a href="#high-stock-section" class="btn btn-sm btn-outline-info border-0">
                                <i class="bi bi-arrow-up-circle"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Produtos com Estoque Baixo -->
    <div class="card border-0 shadow-sm mb-4" id="low-stock-section">
        <div class="card-header bg-white border-0 py-3">
            <div class="row align-items-center g-3">
                <div class="col-12 col-md-auto">
                    <h5 class="mb-0 fw-bold d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                        Estoque Baixo
                    </h5>
                </div>
                <div class="col-12 col-md text-md-end">
                    <span class="badge bg-light text-muted fw-normal">{{ $lowStockProducts->total() }} produtos encontrados</span>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            @if($lowStockProducts->count() > 0)
                <!-- Desktop View -->
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">Produto</th>
                                <th>Categoria</th>
                                <th class="text-center">Estoque Atual</th>
                                <th class="text-center">Mínimo</th>
                                <th class="text-center">Diferença</th>
                                <th class="text-center">Status</th>
                                <th class="text-end pe-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lowStockProducts as $item)
                                @php
                                    $difference = $item->min_quantity - $item->quantity;
                                    $urgency = $difference > $item->min_quantity * 0.5 ? 'high' : 'medium';
                                @endphp
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <h6 class="mb-0 fw-bold">{{ $item->product->name }}</h6>
                                                <small class="text-muted d-block">{{ $item->product->sku }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-muted fw-normal">
                                            {{ $item->product->category->name ?? 'Sem categoria' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-bold {{ $item->quantity <= 0 ? 'text-danger' : 'text-warning' }}">
                                            {{ number_format($item->quantity, 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        {{ number_format($item->min_quantity, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        <span class="text-danger fw-bold">
                                            -{{ number_format($difference, 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($item->quantity <= 0)
                                            <span class="badge bg-danger bg-opacity-10 text-danger px-2 py-1">ESGOTADO</span>
                                        @elseif($urgency === 'high')
                                            <span class="badge bg-danger bg-opacity-10 text-danger px-2 py-1">CRÍTICO</span>
                                        @else
                                            <span class="badge bg-warning bg-opacity-10 text-warning px-2 py-1">BAIXO</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex justify-content-end gap-1">
                                            <x-button type="link" :href="route('provider.inventory.show', $item->product->sku)" variant="info" icon="eye" size="sm" title="Ver Detalhes" />
                                            <x-button type="link" :href="route('provider.inventory.movements', ['sku' => $item->product->sku])" variant="primary" icon="clock-history" size="sm" title="Movimentações" />
                                            <x-button type="link" :href="route('provider.inventory.adjust', $item->product->sku)" variant="success" icon="plus-lg" size="sm" title="Ajustar" />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Mobile View -->
                <div class="d-md-none">
                    <div class="list-group list-group-flush">
                        @foreach($lowStockProducts as $item)
                            @php
                                $difference = $item->min_quantity - $item->quantity;
                                $urgency = $difference > $item->min_quantity * 0.5 ? 'high' : 'medium';
                            @endphp
                            <div class="list-group-item p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-0 fw-bold">{{ $item->product->name }}</h6>
                                        <small class="text-muted">{{ $item->product->sku }}</small>
                                    </div>
                                    @if($item->quantity <= 0)
                                        <span class="badge bg-danger bg-opacity-10 text-danger">ESGOTADO</span>
                                    @elseif($urgency === 'high')
                                        <span class="badge bg-danger bg-opacity-10 text-danger">CRÍTICO</span>
                                    @else
                                        <span class="badge bg-warning bg-opacity-10 text-warning">BAIXO</span>
                                    @endif
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <small class="text-muted d-block">Estoque Atual</small>
                                        <span class="fw-bold {{ $item->quantity <= 0 ? 'text-danger' : 'text-warning' }}">
                                            {{ number_format($item->quantity, 0, ',', '.') }}
                                        </span>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Estoque Mínimo</small>
                                        <span>{{ number_format($item->min_quantity, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                                <div class="d-flex gap-1">
                                    <x-button type="link" :href="route('provider.inventory.show', $item->product->sku)" variant="info" icon="eye" size="sm" class="flex-grow-1" label="Ver" />
                                    <x-button type="link" :href="route('provider.inventory.movements', ['sku' => $item->product->sku])" variant="primary" icon="clock-history" size="sm" class="flex-grow-1" label="Hist." />
                                    <x-button type="link" :href="route('provider.inventory.adjust', $item->product->sku)" variant="success" icon="plus-lg" size="sm" class="flex-grow-1" label="Ajuste" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if($lowStockProducts->hasPages())
                    <div class="p-3 border-top">
                        {{ $lowStockProducts->appends(request()->query())->links() }}
                    </div>
                @endif
            @else
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-check-circle fs-1 d-block mb-3 text-success opacity-25"></i>
                    Tudo certo! Nenhum produto com estoque baixo.
                </div>
            @endif
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