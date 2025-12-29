@extends('layouts.app')

@section('title', 'Inventário de Produtos')

@section('content')
@php
    // Os cards são exibidos se não estivermos no estado inicial (onde filters é vazio)
    $hasResults = !empty($filters);
@endphp
<div class="container-fluid py-1">
    <x-page-header
        title="Inventário"
        icon="boxes"
        :breadcrumb-items="[
            'Inventário' => route('provider.inventory.dashboard'),
            'Listar' => '#'
        ]">
        <p class="text-muted small">Gerencie o estoque e movimentações de seus produtos</p>
    </x-page-header>

    <!-- Resumo de Inventário -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-md-4 col-xl">
            <div class="card h-100">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-primary bg-gradient me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-box text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Total</h6>
                    </div>
                    <h3 class="mb-1 fw-bold">{{ $hasResults ? number_format($stats['total_items'] ?? 0, 0, ',', '.') : '-' }}</h3>
                    <p class="text-muted small mb-0">
                        {{ $hasResults ? 'Produtos' : 'Busque' }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-xl">
            <div class="card h-100">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-success bg-gradient me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-currency-dollar text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Valor Total</h6>
                    </div>
                    <h3 class="mb-1 fw-bold text-success">{{ $hasResults ? 'R$ ' . number_format($stats['total_inventory_value'] ?? 0, 2, ',', '.') : '-' }}</h3>
                    <p class="text-muted small mb-0">
                        {{ $hasResults ? 'Valor em estoque' : 'Busque' }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-xl">
            <div class="card h-100">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-success bg-gradient me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-check-circle text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Estoque OK</h6>
                    </div>
                    <h3 class="mb-1 fw-bold text-success">{{ $hasResults ? number_format($stats['sufficient_stock_items_count'] ?? 0, 0, ',', '.') : '-' }}</h3>
                    <p class="text-muted small mb-0">
                        {{ $hasResults ? 'Itens regulares' : 'Busque' }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-xl">
            <div class="card h-100">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-warning bg-gradient me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-exclamation-triangle text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Baixo</h6>
                    </div>
                    <h3 class="mb-1 fw-bold text-warning">{{ $hasResults ? number_format($stats['low_stock_items_count'] ?? 0, 0, ',', '.') : '-' }}</h3>
                    <p class="text-muted small mb-0">
                        {{ $hasResults ? 'Abaixo do mín.' : 'Busque' }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-xl">
            <div class="card h-100">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-danger bg-gradient me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-x-circle text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Sem Estoque</h6>
                    </div>
                    <h3 class="mb-1 fw-bold text-danger">{{ $hasResults ? number_format($stats['out_of_stock_items_count'] ?? 0, 0, ',', '.') : '-' }}</h3>
                    <p class="text-muted small mb-0">
                        {{ $hasResults ? 'Esgotados' : 'Busque' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-filter me-1"></i> Filtros de Busca</h5>
                </div>
                <div class="card-body">
                    <form id="filtersFormInventory" method="GET" action="{{ route('provider.inventory.index') }}">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="search">Buscar</label>
                                    <input type="text" class="form-control" id="search" name="search"
                                        value="{{ request('search') }}" placeholder="Nome ou SKU do produto">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="category">Categoria</label>
                                    <select name="category" id="category" class="form-select tom-select">
                                        <option value="">Todas as Categorias</option>
                                        @foreach($categories as $category)
                                            @if($category->parent_id === null)
                                                @if($category->children->isEmpty())
                                                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                                        {{ $category->name }}
                                                    </option>
                                                @else
                                                    <optgroup label="{{ $category->name }}">
                                                        <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                                            {{ $category->name }} (Pai)
                                                        </option>
                                                        @foreach($category->children as $child)
                                                            <option value="{{ $child->id }}" {{ request('category') == $child->id ? 'selected' : '' }}>
                                                                {{ $child->name }}
                                                            </option>
                                                        @endforeach
                                                    </optgroup>
                                                @endif
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select name="status" id="status" class="form-select tom-select">
                                        <option value="">Todos os Status</option>
                                        <option value="sufficient" {{ request('status') == 'sufficient' ? 'selected' : '' }}>Estoque OK</option>
                                        <option value="low" {{ request('status') == 'low' ? 'selected' : '' }}>Estoque Baixo</option>
                                        <option value="out" {{ request('status') == 'out' ? 'selected' : '' }}>Sem Estoque</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <x-button type="submit" icon="search" label="Filtrar" variant="primary" />
                                    <x-button type="link" :href="route('provider.inventory.index')" variant="secondary" icon="x" label="Limpar" />
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabela de Inventário -->
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-12 col-lg-8 mb-2 mb-lg-0">
                            <h5 class="mb-0 d-flex align-items-center flex-wrap">
                                <span class="me-2">
                                    <i class="bi bi-list-ul me-1"></i>
                                    <span class="d-none d-sm-inline">Lista de Inventário</span>
                                    <span class="d-sm-none">Inventário</span>
                                </span>
                                <span class="text-muted" style="font-size: 0.875rem;">
                                    ({{ $inventories->total() }})
                                </span>
                            </h5>
                        </div>
                        <div class="col-12 col-lg-4 mt-2 mt-lg-0">
                            <div class="d-flex justify-content-start justify-content-lg-end gap-2">
                                <div class="dropdown">
                                    <x-button variant="outline-secondary" size="sm" icon="download" label="Exportar" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="exportDropdown" />
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="exportDropdown">
                                        <li>
                                            <a class="dropdown-item"
                                                href="{{ route('provider.inventory.export', array_merge(request()->query(), ['type' => 'xlsx'])) }}">
                                                <i class="bi bi-file-earmark-excel me-2 text-success"></i> Excel (.xlsx)
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item"
                                                href="{{ route('provider.inventory.export', array_merge(request()->query(), ['type' => 'pdf'])) }}">
                                                <i class="bi bi-file-earmark-pdf me-2 text-danger"></i> PDF (.pdf)
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <!-- Desktop View -->
                    <div class="desktop-view">
                        <div class="table-responsive">
                            <table class="table modern-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Produto</th>
                                        <th class="text-center">Estoque</th>
                                        <th class="text-center">Disponível</th>
                                        <th class="text-end">Valor Total</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($inventories as $inventory)
                                        @php
                                            $product = $inventory->product;
                                            $currentQuantity = $inventory->quantity;
                                            $minQuantity = $inventory->min_quantity;
                                            $unitValue = $product->price;
                                            $totalValue = $currentQuantity * $unitValue;

                                            // Status seguindo o padrão de produtos (badge-active, badge-inactive, badge-deleted)
                                            $statusClass = $currentQuantity <= 0 ? 'badge-inactive' : ($currentQuantity <= $minQuantity ? 'badge-deleted' : 'badge-active');
                                            $statusLabel = $currentQuantity <= 0 ? 'Sem Estoque' : ($currentQuantity <= $minQuantity ? 'Estoque Baixo' : 'Estoque OK');
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-light rounded p-2 me-2 d-none d-lg-block">
                                                        <i class="bi bi-box text-secondary"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark">{{ $product->name }}</div>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <small class="text-muted text-code">{{ $product->sku }}</small>
                                                            <span class="badge bg-light text-muted border-0 p-0" style="font-size: 0.7rem;">• {{ $product->category->name ?? 'Geral' }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="fw-bold">{{ number_format($currentQuantity, 0, ',', '.') }}</div>
                                                @if($minQuantity > 0)
                                                    <div class="small text-muted" style="font-size: 0.7rem;">Mín: {{ number_format($minQuantity, 0, ',', '.') }}</div>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $inventory->available_quantity > 0 ? 'primary' : 'secondary' }}">
                                                    {{ number_format($inventory->available_quantity, 0, ',', '.') }}
                                                </span>
                                                @if($inventory->reserved_quantity > 0)
                                                    <div class="small text-info" style="font-size: 0.7rem;">{{ number_format($inventory->reserved_quantity, 0, ',', '.') }} res.</div>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <div class="fw-bold text-dark">R$ {{ number_format($totalValue, 2, ',', '.') }}</div>
                                                <div class="small text-muted" style="font-size: 0.7rem;">R$ {{ number_format($unitValue, 2, ',', '.') }} un.</div>
                                            </td>
                                            <td class="text-center">
                                                <span class="modern-badge {{ $statusClass }}">
                                                    {{ $statusLabel }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-1">
                                                    <x-button type="link" :href="route('provider.products.show', $product->sku)" variant="info" icon="eye" title="Ver Detalhes" size="sm" />
                                                    <x-button type="link" :href="route('provider.inventory.entry', $product->sku)" variant="success" icon="plus-lg" title="Entrada de Estoque" size="sm" />
                                                    <x-button type="link" :href="route('provider.inventory.exit', $product->sku)" variant="warning" icon="dash-lg" title="Saída de Estoque" size="sm" />
                                                    <x-button type="link" :href="route('provider.inventory.adjust', $product->sku)" variant="secondary" icon="sliders" title="Ajustar Estoque" size="sm" />
                                                    <x-button type="link" :href="route('provider.inventory.movements', ['product_id' => $product->id])" variant="primary" icon="clock-history" title="Ver Histórico" size="sm" />
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center py-5 text-muted">
                                                <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                                @if($hasResults)
                                                    Nenhum item de inventário encontrado para os filtros aplicados.
                                                @else
                                                    Utilize os filtros acima para pesquisar no inventário.
                                                @endif
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Mobile View -->
                    <div class="mobile-view">
                        <div class="list-group list-group-flush">
                            @forelse($inventories as $inventory)
                                @php
                                    $product = $inventory->product;
                                    $currentQuantity = $inventory->quantity;
                                    $minQuantity = $inventory->min_quantity;

                                    // Status seguindo o padrão de produtos (badge-active, badge-inactive, badge-deleted)
                                    $statusClass = $currentQuantity <= 0 ? 'badge-inactive' : ($currentQuantity <= $minQuantity ? 'badge-deleted' : 'badge-active');
                                    $statusLabel = $currentQuantity <= 0 ? 'Sem Estoque' : ($currentQuantity <= $minQuantity ? 'Estoque Baixo' : 'Estoque OK');
                                @endphp
                                <div class="list-group-item py-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <div class="fw-bold text-dark">{{ $product->name }}</div>
                                            <small class="text-muted text-code">{{ $product->sku }}</small>
                                        </div>
                                        <span class="modern-badge {{ $statusClass }}" style="font-size: 0.7rem;">
                                            {{ $statusLabel }}
                                        </span>
                                    </div>

                                    <div class="row g-2 mb-3">
                                        <div class="col-4 text-center">
                                            <small class="text-muted d-block small text-uppercase">Total</small>
                                            <span class="fw-bold">{{ number_format($currentQuantity, 0, ',', '.') }}</span>
                                        </div>
                                        <div class="col-4 text-center">
                                            <small class="text-muted d-block small text-uppercase">Reserv.</small>
                                            <span class="text-info">{{ number_format($inventory->reserved_quantity, 0, ',', '.') }}</span>
                                        </div>
                                        <div class="col-4 text-center">
                                            <small class="text-muted d-block small text-uppercase">Dispon.</small>
                                            <span class="fw-bold text-primary">{{ number_format($inventory->available_quantity, 0, ',', '.') }}</span>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="text-muted d-block small text-uppercase">Valor Total</small>
                                            <span class="fw-bold">R$ {{ number_format($currentQuantity * $product->price, 2, ',', '.') }}</span>
                                        </div>
                                        <div class="action-btn-group d-flex gap-1">
                                               <x-button type="link" :href="route('provider.products.show', $product->sku)" variant="info" icon="eye" size="sm" title="Ver" />
                                               <x-button type="link" :href="route('provider.inventory.entry', $product->sku)" variant="success" icon="plus-lg" size="sm" title="Entrada" />
                                               <x-button type="link" :href="route('provider.inventory.exit', $product->sku)" variant="warning" icon="dash-lg" size="sm" title="Saída" />
                                               <x-button type="link" :href="route('provider.inventory.adjust', $product->sku)" variant="secondary" icon="sliders" size="sm" title="Ajustar" />
                                               <x-button type="link" :href="route('provider.inventory.movements', ['product_id' => $product->id])" variant="primary" icon="clock-history" size="sm" title="Histórico" />
                                           </div>
                                    </div>
                                </div>
                            @empty
                                <div class="p-4 text-center text-muted">
                                    <i class="bi bi-inbox mb-2" style="font-size: 2rem;"></i>
                                    <br>
                                    @if($hasResults)
                                        Nenhum item encontrado para os filtros aplicados.
                                    @else
                                        Utilize os filtros acima para pesquisar.
                                    @endif
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                @if($inventories->hasPages())
                        @include('partials.components.paginator', [
                            'p' => $inventories->appends(request()->query()),
                            'show_info' => true
                        ])
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
