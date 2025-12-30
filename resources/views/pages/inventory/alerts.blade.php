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
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-warning text-white me-3" style="width: 35px; height: 35px;">
                            <i class="bi bi-exclamation-triangle" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Estoque Baixo</h6>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold">{{ number_format($lowStockProducts->total(), 0, ',', '.') }}</h5>
                        <a href="#low-stock-section" class="btn btn-sm btn-outline-warning border-0 p-0">
                            <i class="bi bi-arrow-down-circle"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-info text-white me-3" style="width: 35px; height: 35px;">
                            <i class="bi bi-arrow-up-circle" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Estoque Alto</h6>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold">{{ number_format($highStockProducts->total(), 0, ',', '.') }}</h5>
                        <a href="#high-stock-section" class="btn btn-sm btn-outline-info border-0 p-0">
                            <i class="bi bi-arrow-up-circle"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Produtos com Estoque Baixo -->
    <div class="card mb-4 border-0 shadow-sm" id="low-stock-section">
        <div class="card-header py-3 border-0">
            <div class="row align-items-center">
                <div class="col-12 col-md-auto mb-2 mb-md-0">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Estoque Baixo
                    </h5>
                </div>
                <div class="col-12 col-md text-md-end">
                    <div class="d-flex flex-wrap justify-content-md-end align-items-center gap-2">
                        <span class="text-muted small me-md-2">
                            ({{ $lowStockProducts->total() }} produtos encontrados)
                        </span>
                        <div class="dropdown">
                            <x-button variant="outline-secondary" size="sm" icon="download" label="Exportar" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="exportLowStock" />
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="exportLowStock">
                                <li>
                                    <a class="dropdown-item" href="{{ route('provider.inventory.export', ['type' => 'xlsx', 'status' => 'low']) }}">
                                        <i class="bi bi-file-earmark-excel me-2 text-success"></i> Excel (.xlsx)
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('provider.inventory.export', ['type' => 'pdf', 'status' => 'low']) }}">
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
                                <th class="text-center pe-4" style="width: 180px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lowStockProducts as $item)
                                @php
                                    $difference = $item->min_quantity - $item->quantity;
                                    $urgency = $difference > $item->min_quantity * 0.5 ? 'high' : 'medium';

                                    // Status usando o padrão modern-badge
                                    $statusClass = $item->quantity <= 0 ? 'badge-inactive' : ($urgency === 'high' ? 'badge-deleted' : 'badge-warning');
                                    $statusLabel = $item->quantity <= 0 ? 'Esgotado' : ($urgency === 'high' ? 'Crítico' : 'Baixo');
                                @endphp
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <div class="fw-bold text-dark">{{ $item->product->name }}</div>
                                                <small class="text-muted text-code">{{ $item->product->sku }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-muted border-0 p-0" style="font-size: 0.75rem;">
                                            {{ $item->product->category->name ?? 'Sem categoria' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="fw-bold {{ $item->quantity <= 0 ? 'text-danger' : 'text-warning' }}">
                                            {{ number_format($item->quantity, 0, ',', '.') }}
                                        </div>
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
                                        <span class="modern-badge {{ $statusClass }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td class="text-center pe-4">
                                        <div class="d-flex justify-content-center gap-1">
                                            <x-button type="link" :href="route('provider.inventory.show', $item->product->sku)" variant="info" icon="eye" size="sm" title="Ver Detalhes" />
                                            <x-button type="link" :href="route('provider.inventory.movements', ['sku' => $item->product->sku])" variant="primary" icon="clock-history" size="sm" title="Movimentações" />
                                            <x-button type="link" :href="route('provider.inventory.adjust', $item->product->sku)" variant="secondary" icon="sliders" size="sm" title="Ajustar" />
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
                                $statusClass = $item->quantity <= 0 ? 'badge-inactive' : ($urgency === 'high' ? 'badge-deleted' : 'badge-warning');
                                $statusLabel = $item->quantity <= 0 ? 'Esgotado' : ($urgency === 'high' ? 'Crítico' : 'Baixo');
                            @endphp
                            <div class="list-group-item py-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <div class="fw-bold text-dark small">{{ $item->product->name }}</div>
                                        <small class="text-muted text-code" style="font-size: 0.65rem;">{{ $item->product->sku }}</small>
                                    </div>
                                    <span class="modern-badge {{ $statusClass }}" style="font-size: 0.65rem;">
                                        {{ $statusLabel }}
                                    </span>
                                </div>
                                <div class="row g-2 mb-3 bg-light rounded p-2 mx-0">
                                    <div class="col-4 text-center">
                                        <small class="text-muted d-block small text-uppercase" style="font-size: 0.6rem;">Atual</small>
                                        <span class="fw-bold small {{ $item->quantity <= 0 ? 'text-danger' : 'text-warning' }}">{{ number_format($item->quantity, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="col-4 text-center border-start border-end">
                                        <small class="text-muted d-block small text-uppercase" style="font-size: 0.6rem;">Mínimo</small>
                                        <span class="small">{{ number_format($item->min_quantity, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="col-4 text-center">
                                        <small class="text-muted d-block small text-uppercase" style="font-size: 0.6rem;">Dif.</small>
                                        <span class="fw-bold text-danger small">-{{ number_format($difference, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end gap-1">
                                    <x-button type="link" :href="route('provider.inventory.show', $item->product->sku)" variant="info" icon="eye" size="sm" title="Ver" />
                                    <x-button type="link" :href="route('provider.inventory.movements', ['sku' => $item->product->sku])" variant="primary" icon="clock-history" size="sm" title="Hist." />
                                    <x-button type="link" :href="route('provider.inventory.adjust', $item->product->sku)" variant="secondary" icon="sliders" size="sm" title="Ajuste" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if($lowStockProducts->hasPages())
                    @include('partials.components.paginator', [
                        'p' => $lowStockProducts->appends(request()->query()),
                        'show_info' => true
                    ])
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
    <div class="card mb-4 border-0 shadow-sm" id="high-stock-section">
        <div class="card-header py-3 border-0">
            <div class="row align-items-center">
                <div class="col-12 col-md-auto mb-2 mb-md-0">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-arrow-up-circle me-2"></i>
                        Estoque Alto (Excesso)
                    </h5>
                </div>
                <div class="col-12 col-md text-md-end">
                    <div class="d-flex flex-wrap justify-content-md-end align-items-center gap-2">
                        <span class="text-muted small me-md-2">
                            ({{ $highStockProducts->total() }} produtos encontrados)
                        </span>
                        <div class="dropdown">
                            <x-button variant="outline-secondary" size="sm" icon="download" label="Exportar" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="exportHighStock" />
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="exportHighStock">
                                <li>
                                    <a class="dropdown-item" href="{{ route('provider.inventory.export', ['type' => 'xlsx', 'status' => 'sufficient']) }}">
                                        <i class="bi bi-file-earmark-excel me-2 text-success"></i> Excel (.xlsx)
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('provider.inventory.export', ['type' => 'pdf', 'status' => 'sufficient']) }}">
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
            @if($highStockProducts->count() > 0)
                <!-- Desktop View -->
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">Produto</th>
                                <th>Categoria</th>
                                <th class="text-center">Estoque Atual</th>
                                <th class="text-center">Máximo</th>
                                <th class="text-center">Excesso</th>
                                <th class="text-center">Status</th>
                                <th class="text-center pe-4" style="width: 180px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($highStockProducts as $item)
                                @php
                                    $excess = $item->quantity - $item->max_quantity;
                                    $excessPercentage = ($excess / $item->max_quantity) * 100;

                                    // Status usando o padrão modern-badge
                                    $statusClass = $excessPercentage > 50 ? 'badge-deleted' : 'badge-info';
                                    $statusLabel = $excessPercentage > 50 ? 'Excessivo' : 'Alto';
                                @endphp
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <div class="fw-bold text-dark">{{ $item->product->name }}</div>
                                                <small class="text-muted text-code">{{ $item->product->sku }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-muted border-0 p-0" style="font-size: 0.75rem;">
                                            {{ $item->product->category->name ?? 'Sem categoria' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="fw-bold text-info">
                                            {{ number_format($item->quantity, 0, ',', '.') }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        {{ number_format($item->max_quantity, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        <span class="text-info fw-bold">
                                            +{{ number_format($excess, 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="modern-badge {{ $statusClass }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td class="text-center pe-4">
                                        <div class="d-flex justify-content-center gap-1">
                                            <x-button type="link" :href="route('provider.inventory.show', $item->product->sku)" variant="info" icon="eye" size="sm" title="Ver Detalhes" />
                                            <x-button type="link" :href="route('provider.inventory.movements', ['sku' => $item->product->sku])" variant="primary" icon="clock-history" size="sm" title="Movimentações" />
                                            <x-button type="link" :href="route('provider.inventory.adjust', $item->product->sku)" variant="secondary" icon="sliders" size="sm" title="Ajustar" />
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
                        @foreach($highStockProducts as $item)
                            @php
                                $excess = $item->quantity - $item->max_quantity;
                                $excessPercentage = ($excess / $item->max_quantity) * 100;
                                $statusClass = $excessPercentage > 50 ? 'badge-deleted' : 'badge-info';
                                $statusLabel = $excessPercentage > 50 ? 'Excessivo' : 'Alto';
                            @endphp
                            <div class="list-group-item py-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <div class="fw-bold text-dark small">{{ $item->product->name }}</div>
                                        <small class="text-muted text-code" style="font-size: 0.65rem;">{{ $item->product->sku }}</small>
                                    </div>
                                    <span class="modern-badge {{ $statusClass }}" style="font-size: 0.65rem;">
                                        {{ $statusLabel }}
                                    </span>
                                </div>
                                <div class="row g-2 mb-3 bg-light rounded p-2 mx-0">
                                    <div class="col-4 text-center">
                                        <small class="text-muted d-block small text-uppercase" style="font-size: 0.6rem;">Atual</small>
                                        <span class="fw-bold text-info small">{{ number_format($item->quantity, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="col-4 text-center border-start border-end">
                                        <small class="text-muted d-block small text-uppercase" style="font-size: 0.6rem;">Máximo</small>
                                        <span class="small">{{ number_format($item->max_quantity, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="col-4 text-center">
                                        <small class="text-muted d-block small text-uppercase" style="font-size: 0.6rem;">Excesso</small>
                                        <span class="fw-bold text-info small">+{{ number_format($excess, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end gap-1">
                                    <x-button type="link" :href="route('provider.inventory.show', $item->product->sku)" variant="info" icon="eye" size="sm" title="Ver" />
                                    <x-button type="link" :href="route('provider.inventory.movements', ['sku' => $item->product->sku])" variant="primary" icon="clock-history" size="sm" title="Hist." />
                                    <x-button type="link" :href="route('provider.inventory.adjust', $item->product->sku)" variant="secondary" icon="sliders" size="sm" title="Ajuste" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if($highStockProducts->hasPages())
                    @include('partials.components.paginator', [
                        'p' => $highStockProducts->appends(request()->query()),
                        'show_info' => true
                    ])
                @endif
            @else
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-info-circle fs-1 d-block mb-3 text-info opacity-25"></i>
                    Nenhum produto com estoque excessivo encontrado.
                </div>
            @endif
        </div>
    </div>

    <!-- Footer Actions -->
    <div class="mt-4 pb-2">
        <div class="row align-items-center g-3">
            <div class="col-12 col-md-auto">
                <x-back-button index-route="provider.inventory.dashboard" class="w-100 w-md-auto px-md-3" />
            </div>
            <div class="col-12 col-md text-center d-none d-md-block">
                <small class="text-muted">
                    Última atualização: {{ now()->format('d/m/Y H:i') }}
                </small>
            </div>
        </div>
    </div>
</div>
@endsection
