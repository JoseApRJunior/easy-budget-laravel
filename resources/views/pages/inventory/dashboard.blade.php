@extends('layouts.app')

@section('title', 'Dashboard de Inventário')

@section('content')
<div class="container-fluid py-1">
    <!-- Cabeçalho -->
    <x-page-header
        title="Dashboard de Inventário"
        icon="archive"
        :breadcrumb-items="[
            'Inventário' => '#'
        ]">
        <p class="text-muted mb-0">Visão geral do seu estoque e movimentações com atalhos de gestão.</p>
    </x-page-header>

    <!-- Cards de Métricas -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-md-4 col-xl">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-primary-subtle text-primary me-3" style="width: 35px; height: 35px;">
                            <i class="bi bi-box" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Total Produtos</h6>
                    </div>
                    <h5 class="mb-0 fw-bold text-body">{{ number_format($totalProducts, 0, ',', '.') }}</h5>
                    <div class="mt-2">
                        <x-button type="link" :href="route('provider.inventory.index')" variant="link" size="sm" label="Ver todos" icon="chevron-right" icon-right class="p-0 text-decoration-none" />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-md-4 col-xl">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-success-subtle text-success me-3" style="width: 35px; height: 35px;">
                            <i class="bi bi-check-circle" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Estoque OK</h6>
                    </div>
                    <h5 class="mb-0 fw-bold text-body">{{ number_format($sufficientStockProducts, 0, ',', '.') }}</h5>
                    <div class="mt-2">
                        <x-button type="link" :href="route('provider.inventory.index', ['status' => 'sufficient'])" variant="link" size="sm" label="Ver produtos" icon="chevron-right" icon-right class="text-success p-0 text-decoration-none" />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-md-4 col-xl">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-warning-subtle me-3" style="width: 35px; height: 35px;">
                            <i class="bi bi-exclamation-triangle" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Estoque Baixo</h6>
                    </div>
                    <h5 class="mb-0 fw-bold text-body">{{ number_format($lowStockProducts, 0, ',', '.') }}</h5>
                    <div class="mt-2">
                        <a href="{{ route('provider.inventory.index', ['status' => 'low']) }}" class="text-warning small text-decoration-none">Ver produtos <i class="bi bi-chevron-right ms-1"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-md-4 col-xl">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-danger-subtle text-danger me-3" style="width: 35px; height: 35px;">
                            <i class="bi bi-x-circle" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Sem Estoque</h6>
                    </div>
                    <h5 class="mb-0 fw-bold text-body">{{ number_format($outOfStockProducts, 0, ',', '.') }}</h5>
                    <div class="mt-2">
                        <a href="{{ route('provider.inventory.index', ['status' => 'out']) }}" class="text-danger small text-decoration-none">Ver produtos <i class="bi bi-chevron-right ms-1"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-md-4 col-xl">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-info-subtle text-info me-3" style="width: 35px; height: 35px;">
                            <i class="bi bi-bookmark-check" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Reservados</h6>
                    </div>
                    <h5 class="mb-0 fw-bold text-body">{{ number_format($reservedItemsCount, 0, ',', '.') }}</h5>
                    <div class="mt-1 text-muted small">Total: {{ number_format($totalReservedQuantity, 0, ',', '.') }} un.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar-circle bg-success-subtle text-success me-4" style="width: 45px; height: 45px;">
                            <i class="bi bi-currency-dollar" style="font-size: 1.2rem;"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0 small fw-bold text-uppercase">Valor Total em Estoque</h6>
                            <h3 class="mb-0 fw-bold text-success">R$ {{ number_format($totalInventoryValue, 2, ',', '.') }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ações Rápidas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header pt-3 bg-transparent border-0">
                    <h6 class="mb-0 fw-bold text-body"><i class="bi bi-lightning-charge me-2 t"></i>Ações Rápidas</h6>
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

    <!-- Tabelas de Alerta -->
    <div class="row g-4 mb-5">
        <!-- Estoque Baixo -->
        <div class="col-12 col-xl-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header pt-3 bg-transparent border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-exclamation-triangle me-2 "></i>Estoque Baixo
                    </h5>
                    <x-button type="link" :href="route('provider.inventory.index', ['status' => 'low'])" variant="link" size="sm" label="Ver todos" class="p-0 text-decoration-none" />
                </div>
                <div class="card-body p-0">
                    <!-- Desktop View -->
                    <div class="desktop-view d-none d-md-block">
                        <div class="table-responsive">
                            <table class="modern-table table mb-0">
                                <thead>
                                    <tr>
                                        <th>Produto</th>
                                        <th class="text-center">Qtd</th>
                                        <th class="text-center">Mín</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($lowStockItems as $item)
                                        <tr>
                                            <td>
                                                <div class="item-name-cell">
                                                    <div class="fw-bold text-dark">{{ $item->product->name }}</div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <small class="text-muted text-code">{{ $item->product->sku }}</small>
                                                        <span class="text-muted" style="font-size: 0.75rem;">• {{ $item->product->category->name ?? 'Geral' }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="fw-bold text-danger">{{ number_format($item->available_quantity, 0, ',', '.') }}</div>
                                            </td>
                                            <td class="text-center">
                                                <div class="small text-muted">{{ number_format($item->min_quantity, 0, ',', '.') }}</div>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-1">
                                                    <x-button type="link" :href="route('provider.inventory.entry', $item->product->sku)" variant="outline-success" icon="plus" size="sm" title="Entrada" />
                                                    <x-button type="link" :href="route('provider.inventory.adjust', $item->product->sku)" variant="outline-secondary" icon="sliders" size="sm" title="Ajustar" />
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted small">
                                                <i class="bi bi-check-circle text-success d-block mb-2 fs-4"></i>
                                                Nenhum item com estoque baixo.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Mobile View -->
                    <div class="mobile-view d-md-none">
                        <div class="list-group list-group-flush">
                            @forelse($lowStockItems as $item)
                                <div class="list-group-item py-3 bg-transparent">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <div class="fw-bold text-dark small">{{ $item->product->name }}</div>
                                            <small class="text-muted text-code" style="font-size: 0.65rem;">{{ $item->product->sku }}</small>
                                        </div>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill">{{ number_format($item->available_quantity, 0, ',', '.') }} un</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">Mín: {{ number_format($item->min_quantity, 0, ',', '.') }}</small>
                                        <div class="d-flex gap-1">
                                            <x-button type="link" :href="route('provider.inventory.entry', $item->product->sku)" variant="outline-success" icon="plus" size="sm" />
                                            <x-button type="link" :href="route('provider.inventory.adjust', $item->product->sku)" variant="outline-secondary" icon="sliders" size="sm" />
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="p-4 text-center text-muted small">
                                    Nenhum item com estoque baixo.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estoque Alto -->
        <div class="col-12 col-xl-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header pt-3 bg-transparent border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-arrow-up-circle me-2 "></i>Estoque Alto
                    </h5>
                    <x-button type="link" :href="route('provider.inventory.index', ['status' => 'high'])" variant="link" size="sm" label="Ver todos" class="p-0 text-decoration-none" />
                </div>
                <div class="card-body p-0">
                    <!-- Desktop View -->
                    <div class="desktop-view d-none d-md-block">
                        <div class="table-responsive">
                            <table class="modern-table table mb-0">
                                <thead>
                                    <tr>
                                        <th>Produto</th>
                                        <th class="text-center">Qtd</th>
                                        <th class="text-center">Máx</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($highStockItems as $item)
                                        <tr>
                                            <td>
                                                <div class="item-name-cell">
                                                    <div class="fw-bold text-dark">{{ $item->product->name }}</div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <small class="text-muted text-code">{{ $item->product->sku }}</small>
                                                        <span class="text-muted" style="font-size: 0.75rem;">• {{ $item->product->category->name ?? 'Geral' }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="fw-bold text-primary">{{ number_format($item->quantity, 0, ',', '.') }}</div>
                                            </td>
                                            <td class="text-center">
                                                <div class="small text-muted">{{ number_format($item->max_quantity, 0, ',', '.') }}</div>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-1">
                                                    <x-button type="link" :href="route('provider.inventory.exit', $item->product->sku)" variant="outline-warning" icon="dash" size="sm" title="Saída" />
                                                    <x-button type="link" :href="route('provider.inventory.adjust', $item->product->sku)" variant="outline-secondary" icon="sliders" size="sm" title="Ajustar" />
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted small">
                                                <i class="bi bi-info-circle text-info d-block mb-2 fs-4"></i>
                                                Nenhum item com estoque alto.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Mobile View -->
                    <div class="mobile-view d-md-none">
                        <div class="list-group list-group-flush">
                            @forelse($highStockItems as $item)
                                <div class="list-group-item py-3 bg-transparent">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <div class="fw-bold text-dark small">{{ $item->product->name }}</div>
                                            <small class="text-muted text-code" style="font-size: 0.65rem;">{{ $item->product->sku }}</small>
                                        </div>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill">{{ number_format($item->quantity, 0, ',', '.') }} un</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">Máx: {{ number_format($item->max_quantity, 0, ',', '.') }}</small>
                                        <div class="d-flex gap-1">
                                            <x-button type="link" :href="route('provider.inventory.exit', $item->product->sku)" variant="warning" icon="dash" size="sm" />
                                            <x-button type="link" :href="route('provider.inventory.adjust', $item->product->sku)" variant="secondary" icon="sliders" size="sm" />
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="p-4 text-center text-muted small">
                                    Nenhum item com estoque alto.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Últimas Movimentações -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header pt-3 bg-transparent border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-clock-history me-2"></i>Últimas Movimentações
                    </h5>
                    <a href="{{ route('provider.inventory.movements') }}" class="btn btn-sm btn-link p-0 text-decoration-none text-primary">Ver histórico completo</a>
                </div>
                <div class="card-body p-0">
                    <!-- Desktop View -->
                    <div class="desktop-view d-none d-md-block">
                        <div class="table-responsive">
                            <table class="modern-table table mb-0">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Produto</th>
                                        <th class="text-center">Tipo</th>
                                        <th class="text-center">Qtd</th>
                                        <th>Usuário</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentMovements as $movement)
                                        <tr>
                                            <td class="small text-muted">
                                                {{ $movement->created_at->format('d/m/Y H:i') }}
                                            </td>
                                            <td>
                                                <div class="item-name-cell">
                                                    <div class="fw-bold text-dark">{{ $movement->product->name }}</div>
                                                    <small class="text-muted text-code">{{ $movement->product->sku }}</small>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                @php
                                                    $statusClass = match($movement->type) {
                                                        'entry' => 'badge-active',
                                                        'exit' => 'badge-deleted',
                                                        'adjustment' => 'badge-personal',
                                                        'reservation' => 'badge-system',
                                                        'cancellation' => 'badge-inactive',
                                                        default => 'badge-system'
                                                    };
                                                    $statusLabel = match($movement->type) {
                                                        'entry' => 'Entrada',
                                                        'exit' => 'Saída',
                                                        'adjustment' => 'Ajuste',
                                                        'reservation' => 'Reserva',
                                                        'cancellation' => 'Cancelamento',
                                                        default => $movement->type
                                                    };
                                                @endphp
                                                <span class="modern-badge {{ $statusClass }}">
                                                    {{ $statusLabel }}
                                                </span>
                                            </td>
                                            <td class="text-center fw-bold small text-body">
                                                {{ number_format($movement->quantity, 0, ',', '.') }}
                                            </td>
                                            <td class="small text-muted">
                                                {{ $movement->user->name ?? 'Sistema' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted small">
                                                Nenhuma movimentação recente.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Mobile View -->
                    <div class="mobile-view d-md-none">
                        <div class="list-group list-group-flush">
                            @forelse($recentMovements as $movement)
                                <div class="list-group-item py-3 bg-transparent">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <div class="fw-bold text-dark small">{{ $movement->product->name }}</div>
                                            <small class="text-muted text-code" style="font-size: 0.65rem;">{{ $movement->product->sku }}</small>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold text-dark small">{{ number_format($movement->quantity, 0, ',', '.') }} un</div>
                                            <small class="text-muted" style="font-size: 0.65rem;">{{ $movement->created_at->format('d/m/y H:i') }}</small>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        @php
                                            $statusClass = match($movement->type) {
                                                'entry' => 'badge-active',
                                                'exit' => 'badge-deleted',
                                                'adjustment' => 'badge-personal',
                                                'reservation' => 'badge-system',
                                                'cancellation' => 'badge-inactive',
                                                default => 'badge-system'
                                            };
                                            $statusLabel = match($movement->type) {
                                                'entry' => 'Entrada',
                                                'exit' => 'Saída',
                                                'adjustment' => 'Ajuste',
                                                'reservation' => 'Reserva',
                                                'cancellation' => 'Cancelamento',
                                                default => $movement->type
                                            };
                                        @endphp
                                        <span class="modern-badge {{ $statusClass }}">
                                            {{ $statusLabel }}
                                        </span>
                                        <small class="text-muted" style="font-size: 0.7rem;">
                                            <i class="bi bi-person me-1"></i>{{ explode(' ', $movement->user->name ?? 'Sistema')[0] }}
                                        </small>
                                    </div>
                                </div>
                            @empty
                                <div class="p-4 text-center text-muted small">
                                    Nenhuma movimentação recente.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
