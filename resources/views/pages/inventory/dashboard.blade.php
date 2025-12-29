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
        <p class="text-muted mb-0 small">Visão geral do seu estoque e movimentações com atalhos de gestão.</p>
    </x-page-header>

    <!-- Ações Rápidas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header  pt-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-lightning-charge me-2 "></i>Ações Rápidas</h6>
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
        <div class="col-12 col-md-4 col-xl">
            <div class="card h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-primary text-white me-3" style="width: 35px; height: 35px;">
                            <i class="bi bi-box" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Total Produtos</h6>
                    </div>
                    <h5 class="mb-0 fw-bold">{{ $totalProducts }}</h5>
                    <div class="mt-2">
                        <a href="{{ route('provider.inventory.index') }}" class="text-primary small text-decoration-none">Ver todos <i class="bi bi-chevron-right ms-1"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4 col-xl">
            <div class="card h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-success text-white me-3" style="width: 35px; height: 35px;">
                            <i class="bi bi-check-circle" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Estoque OK</h6>
                    </div>
                    <h5 class="mb-0 fw-bold">{{ $sufficientStockProducts }}</h5>
                    <div class="mt-2">
                        <a href="{{ route('provider.inventory.index', ['status' => 'sufficient']) }}" class="text-success small text-decoration-none">Ver produtos <i class="bi bi-chevron-right ms-1"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4 col-xl">
            <div class="card h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-warning text-white me-3" style="width: 35px; height: 35px;">
                            <i class="bi bi-exclamation-triangle" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Estoque Baixo</h6>
                    </div>
                    <h5 class="mb-0 fw-bold">{{ $lowStockProducts }}</h5>
                    <div class="mt-2">
                        <a href="{{ route('provider.inventory.index', ['status' => 'low']) }}" class="text-warning small text-decoration-none">Ver produtos <i class="bi bi-chevron-right ms-1"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4 col-xl">
            <div class="card h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-danger text-white me-3" style="width: 35px; height: 35px;">
                            <i class="bi bi-x-circle" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Sem Estoque</h6>
                    </div>
                    <h5 class="mb-0 fw-bold">{{ $outOfStockProducts }}</h5>
                    <div class="mt-2">
                        <a href="{{ route('provider.inventory.index', ['status' => 'out']) }}" class="text-danger small text-decoration-none">Ver produtos <i class="bi bi-chevron-right ms-1"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4 col-xl">
            <div class="card h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-info text-white me-3" style="width: 35px; height: 35px;">
                            <i class="bi bi-bookmark-check" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Reservados</h6>
                    </div>
                    <h5 class="mb-0 fw-bold">{{ $reservedItemsCount }}</h5>
                    <div class="mt-1 text-muted small">Total: {{ $totalReservedQuantity }} un.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar-circle bg-primary text-white me-4" style="width: 45px; height: 45px;">
                            <i class="bi bi-currency-dollar" style="font-size: 1.2rem;"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0 small fw-bold text-uppercase">Valor Total em Estoque</h6>
                            <h3 class="mb-0 fw-bold text-dark">R$ {{ number_format($totalInventoryValue, 2, ',', '.') }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabelas de Alerta -->
    <div class="row g-4 mb-4">
        <!-- Estoque Baixo -->
        <div class="col-12 col-xl-6">
            <div class="card h-100">
                <div class="card-header  pt-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>Itens com Estoque Baixo
                    </h6>
                    <a href="{{ route('provider.inventory.index', ['status' => 'low']) }}" class="btn btn-sm btn-link text-warning p-0">Ver todos</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-3">Produto</th>
                                    <th class="text-center">Qtd</th>
                                    <th class="text-center">Mín</th>
                                    <th class="text-center pe-3">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lowStockItems as $item)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold text-dark small">{{ $item->product->name }}</div>
                                            <div class="text-muted small text-code" style="font-size: 0.7rem;">{{ $item->product->sku }}</div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-warning border border-warning">{{ $item->available_quantity }}</span>
                                        </td>
                                        <td class="text-center small text-muted">{{ $item->min_quantity }}</td>
                                        <td class="text-center pe-3">
                                            <div class="d-flex justify-content-center gap-1">
                                                <x-button type="link" :href="route('provider.inventory.entry', $item->product->sku)" variant="success" icon="plus" size="sm" title="Entrada" />
                                                <x-button type="link" :href="route('provider.inventory.adjust', $item->product->sku)" variant="secondary" icon="sliders" size="sm" title="Ajustar" />
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
            </div>
        </div>

        <!-- Estoque Alto -->
        <div class="col-12 col-xl-6">
            <div class="card h-100">
                <div class="card-header  pt-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-info">
                        <i class="bi bi-arrow-up-circle me-2"></i>Itens com Estoque Alto
                    </h6>
                    <a href="{{ route('provider.inventory.index', ['status' => 'high']) }}" class="btn btn-sm btn-link text-info p-0">Ver todos</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-3">Produto</th>
                                    <th class="text-center">Qtd</th>
                                    <th class="text-center">Máx</th>
                                    <th class="text-center pe-3">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($highStockItems as $item)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold text-dark small">{{ $item->product->name }}</div>
                                            <div class="text-muted small text-code" style="font-size: 0.7rem;">{{ $item->product->sku }}</div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-info border border-info">{{ $item->quantity }}</span>
                                        </td>
                                        <td class="text-center small text-muted">{{ $item->max_quantity }}</td>
                                        <td class="text-center pe-3">
                                            <div class="d-flex justify-content-center gap-1">
                                                <x-button type="link" :href="route('provider.inventory.exit', $item->product->sku)" variant="warning" icon="dash" size="sm" title="Saída" />
                                                <x-button type="link" :href="route('provider.inventory.adjust', $item->product->sku)" variant="secondary" icon="sliders" size="sm" title="Ajustar" />
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
            </div>
        </div>
    </div>

    <!-- Últimas Movimentações -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header  pt-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-clock-history me-2 text-primary"></i>Últimas Movimentações
                    </h6>
                    <a href="{{ route('provider.inventory.movements') }}" class="btn btn-sm btn-link p-0">Ver histórico completo</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-3">Data</th>
                                    <th>Produto</th>
                                    <th class="text-center">Tipo</th>
                                    <th class="text-center">Qtd</th>
                                    <th class="pe-3">Usuário</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentMovements as $movement)
                                    <tr>
                                        <td class="ps-3 small text-muted">
                                            {{ $movement->created_at->format('d/m/Y H:i') }}
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark small">{{ $movement->product->name }}</div>
                                            <div class="text-muted small text-code" style="font-size: 0.65rem;">{{ $movement->product->sku }}</div>
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $typeBadge = match($movement->type) {
                                                    'entry' => ['class' => 'bg-success', 'label' => 'Entrada'],
                                                    'exit' => ['class' => 'bg-warning', 'label' => 'Saída'],
                                                    'adjustment' => ['class' => 'bg-secondary', 'label' => 'Ajuste'],
                                                    'reservation' => ['class' => 'bg-info', 'label' => 'Reserva'],
                                                    'cancellation' => ['class' => 'bg-danger', 'label' => 'Cancelamento'],
                                                    default => ['class' => 'bg-dark', 'label' => $movement->type]
                                                };
                                            @endphp
                                            <span class="badge {{ $typeBadge['class'] }} bg-opacity-10 text-{{ str_replace('bg-', '', $typeBadge['class']) }} border border-{{ str_replace('bg-', '', $typeBadge['class']) }} px-2" style="font-size: 0.65rem;">
                                                {{ $typeBadge['label'] }}
                                            </span>
                                        </td>
                                        <td class="text-center fw-bold small">
                                            {{ $movement->quantity }}
                                        </td>
                                        <td class="pe-3 small text-muted">
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
            </div>
        </div>
    </div>
</div>
@endsection
