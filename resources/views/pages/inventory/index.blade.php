@extends('layouts.app')

@section('title', 'Inventário de Produtos')

@section('content')

@php
    // Variável para controlar se há filtros aplicados (excluindo paginação)
    $hasResults = !empty($filters) && collect($filters)->except(['per_page', 'page'])->filter()->isNotEmpty();
@endphp

<x-layout.page-container>
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
    <x-layout.grid-row class="g-3 mb-4">
        <x-dashboard.stat-card 
            col="col-12 col-sm-6 col-md-4 col-xl"
            title="Total" 
            :value="\App\Helpers\CurrencyHelper::format($stats['total_items'] ?? 0, 0, false)"
            icon="box"
            variant="primary"
        />
        <x-dashboard.stat-card 
            col="col-12 col-sm-6 col-md-4 col-xl"
            title="Valor Total" 
            :value="\App\Helpers\CurrencyHelper::format($stats['total_inventory_value'] ?? 0)"
            icon="currency-dollar"
            variant="success"
        />
        <x-dashboard.stat-card 
            col="col-12 col-sm-6 col-md-4 col-xl"
            title="Estoque OK" 
            :value="\App\Helpers\CurrencyHelper::format($stats['sufficient_stock_items_count'] ?? 0, 0, false)"
            icon="check-circle"
            variant="success"
        />
        <x-dashboard.stat-card 
            col="col-12 col-sm-6 col-md-4 col-xl"
            title="Baixo" 
            :value="\App\Helpers\CurrencyHelper::format($stats['low_stock_items_count'] ?? 0, 0, false)"
            icon="exclamation-triangle"
            variant="warning"
        />
        <x-dashboard.stat-card 
            col="col-12 col-sm-6 col-md-4 col-xl"
            title="Sem Estoque" 
            :value="\App\Helpers\CurrencyHelper::format($stats['out_of_stock_items_count'] ?? 0, 0, false)"
            icon="x-circle"
            variant="danger"
        />
        <x-dashboard.stat-card 
            col="col-12 col-sm-6 col-md-4 col-xl"
            title="Taxa Uso" 
            :value="\App\Helpers\CurrencyHelper::format($stats['usage_rate'] ?? 0, 1, false) . '%'"
            icon="percent"
            variant="info"
        />
    </x-layout.grid-row>

    <!-- Card de Filtros -->
    <x-ui.card class="mb-4">
        <x-slot:header>
            <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-filter me-1"></i> Filtros de Busca</h5>
        </x-slot:header>
        <div class="p-2">
            <form id="filtersFormInventory" method="GET" action="{{ route('provider.inventory.index') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <x-ui.form.input 
                            name="search" 
                            label="Buscar Produto" 
                            :value="request('search')" 
                            placeholder="Nome ou SKU" 
                        />
                    </div>
                    <div class="col-md-2">
                        <x-ui.form.select name="category" label="Categoria" class="tom-select" :selected="request('category')">
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
                        </x-ui.form.select>
                    </div>
                    <div class="col-md-2">
                        <x-ui.form.select name="status" label="Status" class="tom-select" :selected="request('status')">
                            <option value="">Todos</option>
                            <option value="sufficient" {{ request('status') == 'sufficient' ? 'selected' : '' }}>Estoque OK</option>
                            <option value="low" {{ request('status') == 'low' ? 'selected' : '' }}>Estoque Baixo</option>
                            <option value="out" {{ request('status') == 'out' ? 'selected' : '' }}>Sem Estoque</option>
                        </x-ui.form.select>
                    </div>
                    <div class="col-md-2">
                        <x-ui.form.input type="date" name="start_date" id="start_date" label="Período Inicial" :value="$filters['start_date'] ?? request('start_date')" />
                    </div>
                    <div class="col-md-2">
                        <x-ui.form.input type="date" name="end_date" id="end_date" label="Período Final" :value="$filters['end_date'] ?? request('end_date')" />
                    </div>
                    <div class="col-md-1">
                        <x-ui.form.select name="per_page" label="Por Página" class="tom-select" :selected="request('per_page', 10)">
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </x-ui.form.select>
                    </div>
                    <div class="col-12">
                        <div class="d-flex gap-2 flex-nowrap">
                            <x-ui.button type="submit" variant="primary" icon="search" label="Filtrar" id="btnFilterInventory" class="flex-grow-1" feature="inventory" />
                            <x-ui.button href="{{ route('provider.inventory.index') }}" variant="secondary" outline icon="x" label="Limpar" feature="inventory" />
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </x-ui.card>

    <!-- Tabela de Inventário -->
    <x-ui.card no-padding>
        <x-slot:header>
            <div class="row align-items-center">
                <div class="col-12 col-lg-8 mb-2 mb-lg-0">
                    <h5 class="mb-0 d-flex align-items-center flex-wrap fw-bold text-primary">
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
                        <x-ui.button href="{{ route('provider.inventory.movements') }}" variant="primary" size="sm" icon="clock-history" label="Histórico" feature="inventory" />
                        <div class="dropdown">
                            <x-ui.button variant="secondary" size="sm" icon="download" label="Exportar" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="exportDropdown" feature="inventory" />
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
        </x-slot:header>
        
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

                                $statusClass = $availableQuantity <= 0 ? 'bg-danger' : ($availableQuantity <= $minQuantity ? 'bg-warning text-dark' : 'bg-success');
                                $statusLabel = $availableQuantity <= 0 ? 'Sem Estoque' : ($availableQuantity <= $minQuantity ? 'Estoque Baixo' : 'Estoque OK');
                            @endphp
                            <tr>
                                <td>
                                    <div class="item-icon">
                                        @if($product->image_url)
                                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="rounded shadow-sm" style="width: 32px; height: 32px; object-fit: cover;">
                                        @else
                                            <div class="avatar-circle bg-primary bg-gradient rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                <i class="bi bi-box-fill text-white" style="font-size: 0.8rem;"></i>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="item-name-cell">
                                        <div class="fw-bold text-dark">{{ $product->name }}</div>
                                        <div class="d-flex align-items-center gap-2">
                                            <small class="text-muted text-code font-monospace">{{ $product->sku }}</small>
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
                                    <span class="badge {{ $statusClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <x-ui.button :href="route('provider.inventory.show', $product->sku)" variant="info" outline icon="eye" title="Ver Inventário" size="sm" feature="inventory" />
                                        <x-ui.button :href="route('provider.inventory.entry', $product->sku)" variant="success" outline icon="plus" title="Entrada" size="sm" feature="inventory" />
                                        <x-ui.button :href="route('provider.inventory.exit', $product->sku)" variant="warning" outline icon="dash" title="Saída" size="sm" feature="inventory" />
                                        <x-ui.button :href="route('provider.inventory.adjust', $product->sku)" variant="secondary" outline icon="sliders" title="Ajustar" size="sm" feature="inventory" />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <div class="avatar-circle bg-secondary bg-gradient mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
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

                        $statusClass = $availableQuantity <= 0 ? 'bg-danger' : ($availableQuantity <= $minQuantity ? 'bg-warning text-dark' : 'bg-success');
                        $statusLabel = $availableQuantity <= 0 ? 'Sem Estoque' : ($availableQuantity <= $minQuantity ? 'Estoque Baixo' : 'Estoque OK');
                    @endphp
                    <div class="list-group-item py-3">
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3 mt-1">
                                @if($product->image_url)
                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="rounded shadow-sm" style="width: 40px; height: 40px; object-fit: cover;">
                                @else
                                    <div class="avatar-circle bg-primary bg-gradient rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="bi bi-box-fill text-white" style="font-size: 1rem;"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-bold text-dark">{{ $product->name }}</div>
                                        <small class="text-muted text-code font-monospace">{{ $product->sku }}</small>
                                    </div>
                                    <span class="badge {{ $statusClass }}">
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
                                <x-ui.button :href="route('provider.inventory.show', $product->sku)" variant="info" outline icon="eye" size="sm" />
                                <x-ui.button :href="route('provider.inventory.entry', $product->sku)" variant="success" outline icon="plus" size="sm" />
                                <x-ui.button :href="route('provider.inventory.exit', $product->sku)" variant="warning" outline icon="dash" size="sm" />
                                <x-ui.button :href="route('provider.inventory.adjust', $product->sku)" variant="secondary" outline icon="sliders" size="sm" />
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-muted">
                        <div class="avatar-circle bg-secondary bg-gradient mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
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
        
        @if($inventories->hasPages())
            <div class="card-footer bg-white border-top">
                @include('partials.components.paginator', [
                    'p' => $inventories->appends(request()->query()),
                    'show_info' => true
                ])
            </div>
        @endif
    </x-ui.card>
</x-layout.page-container>
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
                    alert('A data inicial não pode ser maior que a data final.');
                    return false;
                }
                return true;
            };

            form.addEventListener('submit', function(e) {
                if (!validateDates()) {
                    e.preventDefault();
                    return;
                }
                if ((startDate.value && !endDate.value) || (!startDate.value && endDate.value)) {
                    e.preventDefault();
                    alert('Para filtrar por período, informe as datas inicial e final.');
                }
            });
        });
    </script>
@endpush
