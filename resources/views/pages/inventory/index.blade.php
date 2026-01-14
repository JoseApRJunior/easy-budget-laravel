@extends('layouts.app')

@section('title', 'Inventário de Produtos')

@section('content')

@php
    // Variável para controlar se há filtros aplicados (excluindo paginação)
    $hasResults = !empty($filters) && collect($filters)->except(['per_page', 'page'])->filter()->isNotEmpty();
@endphp

<div class="container-fluid py-4">
    <x-layout.page-header
        title="Inventário"
        icon="archive"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Inventário' => route('provider.inventory.dashboard'),
            'Lista' => '#'
        ]">
        <p class="text-muted mb-0">Gerencie o estoque e movimentações de seus produtos</p>
    </x-layout.page-header>

    <!-- Resumo de Inventário -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-md-4 col-xl">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-primary bg-gradient me-3" style="width: 35px; height: 35px;">
                            <i class="bi bi-box text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Total</h6>
                    </div>
                    <h5 class="mb-0 fw-bold text-body">{{ \App\Helpers\CurrencyHelper::format($stats['total_items'] ?? 0, 0, false) }}</h5>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-xl">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-success bg-gradient me-3" style="width: 35px; height: 35px;">
                            <i class="bi bi-currency-dollar text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Valor Total</h6>
                    </div>
                    <h5 class="mb-0 fw-bold text-success">{{ \App\Helpers\CurrencyHelper::format($stats['total_inventory_value'] ?? 0) }}</h5>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-xl">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-success bg-gradient me-3" style="width: 35px; height: 35px;">
                            <i class="bi bi-check-circle text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Estoque OK</h6>
                    </div>
                    <h5 class="mb-0 fw-bold text-body">{{ \App\Helpers\CurrencyHelper::format($stats['sufficient_stock_items_count'] ?? 0, 0, false) }}</h5>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-xl">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-warning bg-gradient me-3" style="width: 35px; height: 35px;">
                            <i class="bi bi-exclamation-triangle text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Baixo</h6>
                    </div>
                    <h5 class="mb-0 fw-bold text-body">{{ \App\Helpers\CurrencyHelper::format($stats['low_stock_items_count'] ?? 0, 0, false) }}</h5>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-xl">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-danger bg-gradient me-3" style="width: 35px; height: 35px;">
                            <i class="bi bi-x-circle text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Sem Estoque</h6>
                    </div>
                    <h5 class="mb-0 fw-bold text-body">{{ \App\Helpers\CurrencyHelper::format($stats['out_of_stock_items_count'] ?? 0, 0, false) }}</h5>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-xl">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-info bg-gradient me-3" style="width: 35px; height: 35px;">
                            <i class="bi bi-percent text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Taxa Uso</h6>
                    </div>
                    <h5 class="mb-0 fw-bold text-body">{{ \App\Helpers\CurrencyHelper::format($stats['usage_rate'] ?? 0, 1, false) }}%</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
        <!-- Card de Filtros -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0 "><i class="bi bi-filter me-1"></i> Filtros de Busca</h5>
                </div>
                <div class="card-body">
                    <form id="filtersFormInventory" method="GET" action="{{ route('provider.inventory.index') }}">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="search" class="form-label small fw-bold text-muted text-uppercase">Buscar Produto</label>
                                    <input type="text" class="form-control" id="search" name="search"
                                        value="{{ request('search') }}" placeholder="Nome ou SKU">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="category" class="form-label small fw-bold text-muted text-uppercase">Categoria</label>
                                    <select name="category" id="category" class="form-select tom-select">
                                        <option value="">Todas</option>
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
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="status" class="form-label small fw-bold text-muted text-uppercase">Status</label>
                                    <select name="status" id="status" class="form-select tom-select">
                                        <option value="">Todos</option>
                                        <option value="sufficient" {{ request('status') == 'sufficient' ? 'selected' : '' }}>Estoque OK</option>
                                        <option value="low" {{ request('status') == 'low' ? 'selected' : '' }}>Estoque Baixo</option>
                                        <option value="out" {{ request('status') == 'out' ? 'selected' : '' }}>Sem Estoque</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <x-form.filter-field
                                    type="date"
                                    name="start_date"
                                    id="start_date"
                                    label="Período Inicial"
                                    :value="$filters['start_date'] ?? request('start_date')"
                                />
                            </div>
                            <div class="col-md-2">
                                <x-form.filter-field
                                    type="date"
                                    name="end_date"
                                    id="end_date"
                                    label="Período Final"
                                    :value="$filters['end_date'] ?? request('end_date')"
                                />
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <label for="per_page" class="form-label small fw-bold text-muted text-uppercase">Por Página</label>
                                    <select name="per_page" id="per_page" class="form-select tom-select">
                                        <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                                        <option value="20" {{ request('per_page') == 20 ? 'selected' : '' }}>20</option>
                                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex gap-2 flex-nowrap">
                                    <x-ui.button type="submit" variant="primary" icon="search" label="Filtrar" id="btnFilterInventory" class="flex-grow-1" />
                                    <x-ui.button type="link" :href="route('provider.inventory.index')" variant="outline-secondary" icon="x" label="Limpar" />
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
                                    @if ($inventories instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                    ({{ $inventories->total() }})
                                    @else
                                    ({{ count($inventories) }})
                                    @endif
                                </span>
                            </h5>
                        </div>
                        <div class="col-12 col-lg-4 mt-2 mt-lg-0">
                            <div class="d-flex justify-content-start justify-content-lg-end gap-2">
                                <x-ui.button type="link" :href="route('provider.inventory.movements')" variant="primary" size="sm" icon="clock-history" label="Histórico" />
                                <div class="dropdown">
                                    <x-ui.button variant="secondary" size="sm" icon="download" label="Exportar" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="exportDropdown" />
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
                <div class="card-body p-0 border-0">
                    <!-- Desktop View -->
                    <div class="desktop-view d-none d-lg-block">
                        <div class="table-responsive">
                            <table class="modern-table table mb-0">
                                <thead>
                                    <tr>
                                        <th width="60"><i class="bi bi-box" aria-hidden="true"></i></th>
                                        <th>Produto</th>
                                        <th class="text-center">Estoque</th>
                                        <th class="text-center">Disponível</th>
                                        <th class="text-end">Valor Total</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center" style="width: 180px;">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($inventories as $inventory)
                                        @php
                                            $product = $inventory->product;
                                            $currentQuantity = $inventory->quantity;
                                            $availableQuantity = $inventory->available_quantity;
                                            $minQuantity = $inventory->min_quantity;
                                            $unitValue = $product->price;
                                            $totalValue = $currentQuantity * $unitValue;

                                            $statusClass = $availableQuantity <= 0 ? 'badge-inactive' : ($availableQuantity <= $minQuantity ? 'badge-deleted' : 'badge-active');
                                            $statusLabel = $availableQuantity <= 0 ? 'Sem Estoque' : ($availableQuantity <= $minQuantity ? 'Estoque Baixo' : 'Estoque OK');
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="item-icon">
                                                    @if($product->image_url)
                                                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="rounded shadow-sm" style="width: 32px; height: 32px; object-fit: cover;">
                                                    @else
                                                        <div class="avatar-circle bg-primary bg-gradient" style="width: 32px; height: 32px;">
                                                            <i class="bi bi-box-fill text-white" style="font-size: 0.8rem;"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div class="item-name-cell">
                                                    <div class="fw-bold text-dark">{{ $product->name }}</div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <small class="text-muted text-code">{{ $product->sku }}</small>
                                                        <span class="text-muted" style="font-size: 0.75rem;">• {{ $product->category->name ?? 'Geral' }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="fw-bold">{{ \App\Helpers\CurrencyHelper::format($currentQuantity, 0, false) }}</div>
                                                @if($minQuantity > 0)
                                                    <div class="small text-muted" style="font-size: 0.7rem;">Mín: {{ \App\Helpers\CurrencyHelper::format($minQuantity, 0, false) }}</div>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $inventory->available_quantity > 0 ? 'primary' : 'secondary' }} rounded-pill px-2">
                                                    {{ \App\Helpers\CurrencyHelper::format($inventory->available_quantity, 0, false) }}
                                                </span>
                                                @if($inventory->reserved_quantity > 0)
                                                    <div class="small text-info" style="font-size: 0.7rem;">{{ \App\Helpers\CurrencyHelper::format($inventory->reserved_quantity, 0, false) }} res.</div>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <div class="fw-bold text-body">{{ \App\Helpers\CurrencyHelper::format($totalValue) }}</div>
                                                <div class="small text-muted" style="font-size: 0.7rem;">{{ \App\Helpers\CurrencyHelper::format($unitValue) }} un.</div>
                                            </td>
                                            <td class="text-center">
                                                <span class="modern-badge {{ $statusClass }}">
                                                    {{ $statusLabel }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-1">
                                                    <x-ui.button type="link" :href="route('provider.inventory.show', $product->sku)" variant="info" icon="eye" title="Ver Inventário" size="sm" />
                                                    <x-ui.button type="link" :href="route('provider.inventory.entry', $product->sku)" variant="success" icon="plus" title="Entrada" size="sm" />
                                                    <x-ui.button type="link" :href="route('provider.inventory.exit', $product->sku)" variant="warning" icon="dash" title="Saída" size="sm" />
                                                    <x-ui.button type="link" :href="route('provider.inventory.adjust', $product->sku)" variant="secondary" icon="sliders" title="Ajustar" size="sm" />
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-5 text-muted">
                                                <div class="avatar-circle bg-secondary bg-gradient mx-auto mb-3" style="width: 50px; height: 50px;">
                                                    <i class="bi bi-inbox text-white" style="font-size: 1.5rem;"></i>
                                                </div>
                                                @if($hasResults)
                                                    <p class="mb-0">Nenhum item de inventário encontrado para os filtros aplicados.</p>
                                                @else
                                                    <p class="mb-0">Utilize os filtros acima para pesquisar no inventário.</p>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Mobile View -->
                    <div class="mobile-view d-lg-none">
                        <div class="list-group list-group-flush">
                            @forelse($inventories as $inventory)
                                @php
                                    $product = $inventory->product;
                                    $currentQuantity = $inventory->quantity;
                                    $availableQuantity = $inventory->available_quantity;
                                    $minQuantity = $inventory->min_quantity;

                                    $statusClass = $availableQuantity <= 0 ? 'badge-inactive' : ($availableQuantity <= $minQuantity ? 'badge-deleted' : 'badge-active');
                                    $statusLabel = $availableQuantity <= 0 ? 'Sem Estoque' : ($availableQuantity <= $minQuantity ? 'Estoque Baixo' : 'Estoque OK');
                                @endphp
                                <div class="list-group-item py-3">
                                    <div class="d-flex align-items-start mb-3">
                                        <div class="me-3 mt-1">
                                            @if($product->image_url)
                                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="rounded shadow-sm" style="width: 40px; height: 40px; object-fit: cover;">
                                            @else
                                                <div class="avatar-circle bg-primary bg-gradient" style="width: 40px; height: 40px;">
                                                    <i class="bi bi-box-fill text-white" style="font-size: 1rem;"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <div class="fw-bold text-dark">{{ $product->name }}</div>
                                                    <small class="text-muted text-code">{{ $product->sku }}</small>
                                                </div>
                                                <span class="modern-badge {{ $statusClass }}">
                                                    {{ $statusLabel }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-2 mb-3 bg-light rounded p-2 mx-0 border">
                                        <div class="col-4 text-center">
                                            <small class="text-muted d-block text-uppercase" style="font-size: 0.6rem; letter-spacing: 0.5px;">Total</small>
                                            <span class="fw-bold text-dark">{{ \App\Helpers\CurrencyHelper::format($currentQuantity, 0, false) }}</span>
                                        </div>
                                        <div class="col-4 text-center border-start border-end">
                                            <small class="text-muted d-block text-uppercase" style="font-size: 0.6rem; letter-spacing: 0.5px;">Reserv.</small>
                                            <span class="text-info">{{ \App\Helpers\CurrencyHelper::format($inventory->reserved_quantity, 0, false) }}</span>
                                        </div>
                                        <div class="col-4 text-center">
                                            <small class="text-muted d-block text-uppercase" style="font-size: 0.6rem; letter-spacing: 0.5px;">Dispon.</small>
                                            <span class="fw-bold text-primary">{{ \App\Helpers\CurrencyHelper::format($inventory->available_quantity, 0, false) }}</span>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="text-muted d-block text-uppercase" style="font-size: 0.6rem; letter-spacing: 0.5px;">Valor Total</small>
                                            <span class="fw-bold text-dark">{{ \App\Helpers\CurrencyHelper::format($currentQuantity * $product->price) }}</span>
                                        </div>
                                        <div class="d-flex gap-1">
                                            <x-ui.button type="link" :href="route('provider.inventory.show', $product->sku)" variant="info" icon="eye" size="sm" title="Ver Inventário" />
                                            <x-ui.button type="link" :href="route('provider.inventory.entry', $product->sku)" variant="success" icon="plus" size="sm" title="Entrada" />
                                            <x-ui.button type="link" :href="route('provider.inventory.exit', $product->sku)" variant="warning" icon="dash" size="sm" title="Saída" />
                                            <x-ui.button type="link" :href="route('provider.inventory.adjust', $product->sku)" variant="secondary" icon="sliders" size="sm" title="Ajustar" />
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="p-4 text-center text-muted">
                                    <div class="avatar-circle bg-secondary bg-gradient mx-auto mb-3" style="width: 50px; height: 50px;">
                                        <i class="bi bi-inbox text-white" style="font-size: 1.5rem;"></i>
                                    </div>
                                    @if($hasResults)
                                        <p class="mb-0">Nenhum item encontrado para os filtros aplicados.</p>
                                    @else
                                        <p class="mb-0">Utilize os filtros acima para pesquisar.</p>
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

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const startDate = document.getElementById('start_date');
            const endDate = document.getElementById('end_date');
            const form = document.getElementById('filtersFormInventory');

            if (!form || !startDate || !endDate) return;

            const parseDate = (str) => {
                if (!str) return null;
                // Suporta YYYY-MM-DD (input date) ou DD/MM/AAAA (antigo)
                if (str.includes('-')) {
                    const d = new Date(str);
                    return isNaN(d.getTime()) ? null : d;
                }
                const parts = str.split('/');
                if (parts.length === 3) {
                    const d = new Date(parts[2], parts[1] - 1, parts[0]);
                    return isNaN(d.getTime()) ? null : d;
                }
                return null;
            };

            const validateDates = () => {
                if (!startDate.value || !endDate.value) return true;

                const start = parseDate(startDate.value);
                const end = parseDate(endDate.value);

                if (start && end && start > end) {
                    const message = 'A data inicial não pode ser maior que a data final.';
                    if (window.easyAlert) {
                        window.easyAlert.warning(message);
                    } else {
                        alert(message);
                    }
                    return false;
                }
                return true;
            };

            form.addEventListener('submit', function(e) {
                if (!validateDates()) {
                    e.preventDefault();
                    return;
                }

                if (startDate.value && !endDate.value) {
                    e.preventDefault();
                    const message = 'Para filtrar por período, informe as datas inicial e final.';
                    if (window.easyAlert) {
                        window.easyAlert.error(message);
                    } else {
                        alert(message);
                    }
                    endDate.focus();
                } else if (!startDate.value && endDate.value) {
                    e.preventDefault();
                    const message = 'Para filtrar por período, informe as datas inicial e final.';
                    if (window.easyAlert) {
                        window.easyAlert.error(message);
                    } else {
                        alert(message);
                    }
                    startDate.focus();
                }
            });
        });
    </script>
@endpush
