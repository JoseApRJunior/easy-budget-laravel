@extends('layouts.app')

@section('title', 'Inventário de Produtos')

@section('content')
<div class="container-fluid py-1">
    <x-page-header
        title="Inventário de Produtos"
        icon="boxes"
        :breadcrumb-items="[
            'Inventário' => route('provider.inventory.dashboard'),
            'Listar' => '#'
        ]">
        <p class="text-muted mb-0">Controle de estoque de produtos</p>
    </x-page-header>

    <div class="row">
        <div class="col-12">
            <!-- Filtros -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0"><i class="bi bi-filter me-1"></i> Filtros de Busca</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('provider.inventory.index') }}">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="search" class="form-label">Buscar</label>
                                    <input type="text" class="form-control" id="search" name="search"
                                        value="{{ request('search') }}" placeholder="Nome ou SKU do produto">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="category" class="form-label">Categoria</label>
                                    <select name="category" id="category" class="form-select tom-select">
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
                                    <label for="status" class="form-label">Status do Estoque</label>
                                    <select name="status" id="status" class="form-select tom-select">
                                        <option value="">Todos</option>
                                        <option value="sufficient" {{ request('status') == 'sufficient' ? 'selected' : '' }}>Estoque OK</option>
                                        <option value="low" {{ request('status') == 'low' ? 'selected' : '' }}>Estoque Baixo</option>
                                        <option value="out" {{ request('status') == 'out' ? 'selected' : '' }}>Sem Estoque</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex gap-2 flex-nowrap">
                                    <x-button type="submit" icon="search" label="Filtrar" />
                                    <x-button type="link" :href="route('provider.inventory.index')" variant="secondary" icon="x" label="Limpar" />
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabela de Inventário -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-transparent">
                    <div class="row align-items-center">
                        <div class="col-12 col-md-8">
                            <h5 class="mb-0">
                                <i class="bi bi-list-ul me-1"></i> Lista de Inventário
                                <span class="text-muted small">({{ $inventories->total() }} registros)</span>
                            </h5>
                        </div>
                        <div class="col-12 col-md-4 text-md-end mt-2 mt-md-0">
                            <x-button type="link" :href="route('provider.inventory.dashboard')" variant="info" icon="bar-chart" label="Dashboard" size="sm" />
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <!-- Desktop View -->
                    <div class="table-responsive d-none d-md-block">
                        <table class="table modern-table mb-0">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>SKU</th>
                                    <th>Categoria</th>
                                    <th class="text-center">Quantidade</th>
                                    <th class="text-center">Mínimo</th>
                                    <th>Valor Unit.</th>
                                    <th>Valor Total</th>
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

                                        // Determinar status do estoque
                                        if ($currentQuantity <= 0) {
                                            $statusLabel = 'Sem Estoque';
                                            $statusClass = 'danger';
                                        } elseif ($currentQuantity <= $minQuantity) {
                                            $statusLabel = 'Estoque Baixo';
                                            $statusClass = 'warning';
                                        } else {
                                            $statusLabel = 'Estoque OK';
                                            $statusClass = 'success';
                                        }
                                    @endphp
                                    <tr>
                                        <td>{{ $product->name }}</td>
                                        <td><span class="text-code">{{ $product->sku }}</span></td>
                                        <td>{{ $product->category->name ?? 'Sem Categoria' }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-{{ $statusClass }}">{{ $currentQuantity }}</span>
                                        </td>
                                        <td class="text-center">{{ $minQuantity }}</td>
                                        <td>R$ {{ number_format($unitValue, 2, ',', '.') }}</td>
                                        <td><strong>R$ {{ number_format($totalValue, 2, ',', '.') }}</strong></td>
                                        <td class="text-center">
                                            <span class="badge badge-{{ $statusClass }}">{{ $statusLabel }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="{{ route('provider.products.show', $product->sku) }}"
                                                   class="btn btn-info btn-sm" title="Ver Produto">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="{{ route('provider.inventory.entry', $product->sku) }}"
                                                   class="btn btn-success btn-sm" title="Entrada de Estoque">
                                                    <i class="bi bi-arrow-down"></i>
                                                </a>
                                                <a href="{{ route('provider.inventory.exit', $product->sku) }}"
                                                   class="btn btn-warning btn-sm" title="Saída de Estoque">
                                                    <i class="bi bi-arrow-up"></i>
                                                </a>
                                                <a href="{{ route('provider.inventory.adjust', $product->sku) }}"
                                                   class="btn btn-secondary btn-sm" title="Ajustar Estoque">
                                                    <i class="bi bi-sliders"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">
                                            <i class="bi bi-inbox mb-2" style="font-size: 2rem;"></i>
                                            <br>
                                            Nenhum produto encontrado no inventário.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile View -->
                    <div class="mobile-view d-md-none">
                        @forelse($inventories as $inventory)
                            @php
                                $product = $inventory->product;
                                $currentQuantity = $inventory->quantity;
                                $minQuantity = $inventory->min_quantity;
                                $unitValue = $product->price;
                                $totalValue = $currentQuantity * $unitValue;

                                if ($currentQuantity <= 0) {
                                    $statusLabel = 'Sem Estoque';
                                    $statusClass = 'danger';
                                } elseif ($currentQuantity <= $minQuantity) {
                                    $statusLabel = 'Estoque Baixo';
                                    $statusClass = 'warning';
                                } else {
                                    $statusLabel = 'Estoque OK';
                                    $statusClass = 'success';
                                }
                            @endphp
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1">{{ $product->name }}</h6>
                                        <small class="text-muted">SKU: {{ $product->sku }}</small>
                                    </div>
                                    <span class="badge bg-{{ $statusClass }}">{{ $statusLabel }}</span>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted">Categoria:</small> {{ $product->category->name ?? 'Sem Categoria' }}<br>
                                    <small class="text-muted">Quantidade:</small> <span class="badge bg-{{ $statusClass }}">{{ $currentQuantity }}</span> / Mín: {{ $minQuantity }}<br>
                                    <small class="text-muted">Valor Total:</small> <strong>R$ {{ number_format($totalValue, 2, ',', '.') }}</strong>
                                </div>
                                <div class="action-btn-group">
                                    <a href="{{ route('provider.products.show', $product->sku) }}" class="action-btn-view" title="Ver Produto">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('provider.inventory.entry', $product->sku) }}" class="btn btn-success btn-sm" title="Entrada">
                                        <i class="bi bi-arrow-down"></i>
                                    </a>
                                    <a href="{{ route('provider.inventory.exit', $product->sku) }}" class="btn btn-warning btn-sm" title="Saída">
                                        <i class="bi bi-arrow-up"></i>
                                    </a>
                                    <a href="{{ route('provider.inventory.adjust', $product->sku) }}" class="btn btn-secondary btn-sm" title="Ajustar">
                                        <i class="bi bi-sliders"></i>
                                    </a>
                                </div>
                            </div>
                        @empty
                            <div class="list-group-item text-center text-muted py-5">
                                <i class="bi bi-inbox mb-2" style="font-size: 2rem;"></i>
                                <p class="mb-0">Nenhum produto encontrado no inventário.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
                @if($inventories->hasPages())
                <div class="card-footer">
                    <div class="d-flex justify-content-center">
                        {{ $inventories->appends(request()->query())->links() }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
