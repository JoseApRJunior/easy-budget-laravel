@extends('layouts.app')

@section('title', 'Alertas de Estoque')

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <x-layout.page-header
        title="Alertas de Estoque"
        icon="bell"
        icon-color="danger"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Inventário' => route('provider.inventory.dashboard'),
            'Alertas' => '#'
        ]">
        <p class="text-muted small mb-0">Produtos com estoque baixo ou excessivo que requerem atenção imediata</p>
    </x-layout.page-header>

    <!-- Resumo dos Alertas -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-danger shadow-sm text-white me-3" style="width: 45px; height: 45px;">
                            <i class="bi bi-exclamation-triangle" style="font-size: 1.25rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Estoque Baixo</h6>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold display-6">{{ \App\Helpers\CurrencyHelper::format($lowStockProducts->total(), 0, false) }}</h5>
                        <a href="#low-stock-section" class="btn btn-link text-danger p-0" title="Ver detalhes">
                            <i class="bi bi-arrow-down-circle fs-4"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-primary shadow-sm text-white me-3" style="width: 45px; height: 45px;">
                            <i class="bi bi-arrow-up-circle" style="font-size: 1.25rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Estoque Alto</h6>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold display-6">{{ \App\Helpers\CurrencyHelper::format($highStockProducts->total(), 0, false) }}</h5>
                        <a href="#high-stock-section" class="btn btn-link text-primary p-0" title="Ver detalhes">
                            <i class="bi bi-arrow-up-circle fs-4"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Produtos com Estoque Baixo -->
    <div class="card mb-4 border-0 shadow-sm" id="low-stock-section">
        <div class="card-header py-3">
            <div class="row align-items-center">
                <div class="col-12 col-lg-8 mb-2 mb-lg-0">
                    <h5 class="mb-0 d-flex align-items-center flex-wrap text-body">
                        <span class="me-2 ">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            <span class="d-none d-sm-inline">Estoque Baixo</span>
                            <span class="d-sm-none">Baixo</span>
                        </span>
                        <span class="text-muted" style="font-size: 0.875rem;">
                            ({{ $lowStockProducts->total() }})
                        </span>
                    </h5>
                </div>
                <div class="col-12 col-lg-4 mt-2 mt-lg-0">
                    <div class="d-flex justify-content-start justify-content-lg-end gap-2">
                        <div class="dropdown">
                            <x-ui.button variant="secondary" size="sm" icon="download" label="Exportar" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="exportLowStock" />
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
                <div class="desktop-view">
                    <div class="table-responsive">
                        <table class="modern-table table mb-0">
                            <thead>
                                <tr>
                                    <th width="60"><i class="bi bi-box " aria-hidden="true"></i></th>
                                    <th class="small text-uppercase">Produto</th>
                                    <th class=" text-uppercase">Categoria</th>
                                    <th class="text-center  text-uppercase">Estoque Atual</th>
                                    <th class="text-center  text-uppercase">Mínimo</th>
                                    <th class="text-center  text-uppercase">Diferença</th>
                                    <th class="text-center  text-uppercase" width="120">Status</th>
                                    <th class="text-center  text-uppercase" width="150">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($lowStockProducts as $item)
                                    @php
                                        $difference = $item->min_quantity - $item->quantity;
                                        $urgency = $difference > $item->min_quantity * 0.5 ? 'high' : 'medium';

                                        // Status usando o padrão modern-badge
                                        $statusClass = $item->quantity <= 0 ? 'badge-deleted' : ($urgency === 'high' ? 'badge-deleted' : 'badge-warning');
                                        $statusLabel = $item->quantity <= 0 ? 'Esgotado' : ($urgency === 'high' ? 'Crítico' : 'Baixo');
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="item-icon bg-danger shadow-sm text-white">
                                                <i class="bi bi-box-seam"></i>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="item-name-cell">
                                                <div class="fw-bold text-body">{{ $item->product->name }}</div>
                                                <small class="text-muted text-code">{{ $item->product->sku }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-muted small">
                                                {{ $item->product->category->name ?? 'Sem categoria' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="fw-bold {{ $item->quantity <= 0 ? 'text-danger' : 'text-danger' }}">
                                                {{ \App\Helpers\CurrencyHelper::format($item->quantity, 0, false) }}
                                            </div>
                                        </td>
                                        <td class="text-center text-muted">
                                            {{ \App\Helpers\CurrencyHelper::format($item->min_quantity, 0, false) }}
                                        </td>
                                        <td class="text-center">
                                            <span class="text-danger fw-bold">
                                                -{{ \App\Helpers\CurrencyHelper::format($difference, 0, false) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="modern-badge {{ $statusClass }}">
                                                {{ $statusLabel }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-btn-group justify-content-center">
                                                <x-ui.button type="link" :href="route('provider.inventory.show', $item->product->sku)" variant="info" icon="eye" title="Ver Detalhes" />
                                                <x-ui.button type="link" :href="route('provider.inventory.movements', ['sku' => $item->product->sku])" variant="primary" icon="clock-history" title="Movimentações" />
                                                <x-ui.button type="link" :href="route('provider.inventory.adjust', $item->product->sku)" variant="secondary" icon="sliders" title="Ajustar" />
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mobile View -->
                <div class="mobile-view">
                    <div class="list-group list-group-flush">
                        @foreach($lowStockProducts as $item)
                            @php
                                $difference = $item->min_quantity - $item->quantity;
                                $urgency = $difference > $item->min_quantity * 0.5 ? 'high' : 'medium';
                                $statusClass = $item->quantity <= 0 ? 'badge-deleted' : ($urgency === 'high' ? 'badge-deleted' : 'badge-warning');
                                $statusLabel = $item->quantity <= 0 ? 'Esgotado' : ($urgency === 'high' ? 'Crítico' : 'Baixo');
                            @endphp
                            <div class="list-group-item py-3">
                                <div class="d-flex align-items-start">
                                    <div class="me-3 mt-1">
                                        <div class="avatar-circle bg-danger shadow-sm text-white" style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                            <i class="bi bi-box-seam"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <div>
                                                <div class="fw-semibold text-body">{{ $item->product->name }}</div>
                                                <small class="text-muted text-code" style="font-size: 0.65rem;">{{ $item->product->sku }}</small>
                                            </div>
                                            <span class="modern-badge {{ $statusClass }}" style="font-size: 0.65rem;">
                                                {{ $statusLabel }}
                                            </span>
                                        </div>

                                        <div class="row g-2 mb-3 bg-light rounded p-2 mx-0 mt-2">
                                            <div class="col-4 text-center">
                                                <small class="text-muted d-block small text-uppercase" style="font-size: 0.6rem;">Atual</small>
                                                <span class="fw-bold small {{ $item->quantity <= 0 ? 'text-danger' : 'text-danger' }}">{{ \App\Helpers\CurrencyHelper::format($item->quantity, 0, false) }}</span>
                                            </div>
                                            <div class="col-4 text-center border-start border-end">
                                                <small class="text-muted d-block small text-uppercase" style="font-size: 0.6rem;">Mínimo</small>
                                                <span class="small text-muted">{{ \App\Helpers\CurrencyHelper::format($item->min_quantity, 0, false) }}</span>
                                            </div>
                                            <div class="col-4 text-center">
                                                <small class="text-muted d-block small text-uppercase" style="font-size: 0.6rem;">Dif.</small>
                                                <span class="fw-bold text-danger small">-{{ \App\Helpers\CurrencyHelper::format($difference, 0, false) }}</span>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-end gap-2">
                                            <x-ui.button type="link" :href="route('provider.inventory.show', $item->product->sku)" variant="info" icon="eye" size="sm" title="Ver" />
                                            <x-ui.button type="link" :href="route('provider.inventory.movements', ['sku' => $item->product->sku])" variant="primary" icon="clock-history" size="sm" title="Hist." />
                                            <x-ui.button type="link" :href="route('provider.inventory.adjust', $item->product->sku)" variant="secondary" icon="sliders" size="sm" title="Ajuste" />
                                        </div>
                                    </div>
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
                <div class="text-center py-5">
                    <div class="mb-3">
                        <div class="avatar-circle bg-success shadow-sm text-white mx-auto" style="width: 80px; height: 80px; font-size: 2rem; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold text-body">Tudo em ordem!</h5>
                    <p class="text-muted mx-auto" style="max-width: 400px;">
                        Não há produtos com estoque abaixo do nível mínimo no momento.
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Produtos com Estoque Alto -->
    <div class="card mb-4 border-0 shadow-sm" id="high-stock-section">
        <div class="card-header py-3">
            <div class="row align-items-center">
                <div class="col-12 col-lg-8 mb-2 mb-lg-0">
                    <h5 class="mb-0 d-flex align-items-center flex-wrap text-body">
                        <span class="me-2 ">
                            <i class="bi bi-arrow-up-circle me-1"></i>
                            <span class="d-none d-sm-inline">Estoque Alto (Excesso)</span>
                            <span class="d-sm-none">Excesso</span>
                        </span>
                        <span class="text-muted" style="font-size: 0.875rem;">
                            ({{ $highStockProducts->total() }})
                        </span>
                    </h5>
                </div>
                <div class="col-12 col-lg-4 mt-2 mt-lg-0">
                    <div class="d-flex justify-content-start justify-content-lg-end gap-2">
                        <div class="dropdown">
                            <x-ui.button variant="outline-secondary" size="sm" icon="download" label="Exportar" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="exportHighStock" />
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
                <div class="desktop-view">
                    <div class="table-responsive">
                        <table class="modern-table table mb-0">
                            <thead>
                                <tr>
                                    <th width="60"><i class="bi bi-box " aria-hidden="true"></i></th>
                                    <th class=" text-uppercase">Produto</th>
                                    <th class=" text-uppercase">Categoria</th>
                                    <th class="text-center  text-uppercase">Estoque Atual</th>
                                    <th class="text-center  text-uppercase">Máximo</th>
                                    <th class="text-center  text-uppercase">Excesso</th>
                                    <th class="text-center  text-uppercase" width="120">Status</th>
                                    <th class="text-center  text-uppercase" width="150">Ações</th>
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
                                        <td>
                                            <div class="item-icon bg-primary shadow-sm text-white">
                                                <i class="bi bi-box-seam"></i>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="item-name-cell">
                                                <div class="fw-bold text-body">{{ $item->product->name }}</div>
                                                <small class="text-muted text-code">{{ $item->product->sku }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-muted small">
                                                {{ $item->product->category->name ?? 'Sem categoria' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="fw-bold text-primary">
                                                {{ \App\Helpers\CurrencyHelper::format($item->quantity, 0, false) }}
                                            </div>
                                        </td>
                                        <td class="text-center text-muted">
                                            {{ \App\Helpers\CurrencyHelper::format($item->max_quantity, 0, false) }}
                                        </td>
                                        <td class="text-center">
                                            <span class="text-primary fw-bold">
                                                +{{ \App\Helpers\CurrencyHelper::format($excess, 0, false) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="modern-badge {{ $statusClass }}">
                                                {{ $statusLabel }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-btn-group justify-content-center">
                                                <x-ui.button type="link" :href="route('provider.inventory.show', $item->product->sku)" variant="info" icon="eye" title="Ver Detalhes" />
                                                <x-ui.button type="link" :href="route('provider.inventory.movements', ['sku' => $item->product->sku])" variant="primary" icon="clock-history" title="Movimentações" />
                                                <x-ui.button type="link" :href="route('provider.inventory.adjust', $item->product->sku)" variant="secondary" icon="sliders" title="Ajustar" />
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mobile View -->
                <div class="mobile-view">
                    <div class="list-group list-group-flush">
                        @foreach($highStockProducts as $item)
                            @php
                                $excess = $item->quantity - $item->max_quantity;
                                $excessPercentage = ($excess / $item->max_quantity) * 100;
                                $statusClass = $excessPercentage > 50 ? 'badge-deleted' : 'badge-info';
                                $statusLabel = $excessPercentage > 50 ? 'Excessivo' : 'Alto';
                            @endphp
                            <div class="list-group-item py-3">
                                <div class="d-flex align-items-start">
                                    <div class="me-3 mt-1">
                                        <div class="avatar-circle bg-primary shadow-sm text-white" style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                            <i class="bi bi-box-seam"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <div>
                                                <div class="fw-semibold text-body">{{ $item->product->name }}</div>
                                                <small class="text-muted text-code" style="font-size: 0.65rem;">{{ $item->product->sku }}</small>
                                            </div>
                                            <span class="modern-badge {{ $statusClass }}" style="font-size: 0.65rem;">
                                                {{ $statusLabel }}
                                            </span>
                                        </div>

                                        <div class="row g-2 mb-3 bg-light rounded p-2 mx-0 mt-2">
                                            <div class="col-4 text-center">
                                                <small class="text-muted d-block small text-uppercase" style="font-size: 0.6rem;">Atual</small>
                                                <span class="fw-bold small text-primary">{{ \App\Helpers\CurrencyHelper::format($item->quantity, 0, false) }}</span>
                                            </div>
                                            <div class="col-4 text-center border-start border-end">
                                                <small class="text-muted d-block small text-uppercase" style="font-size: 0.6rem;">Máximo</small>
                                                <span class="small text-muted">{{ \App\Helpers\CurrencyHelper::format($item->max_quantity, 0, false) }}</span>
                                            </div>
                                            <div class="col-4 text-center">
                                                <small class="text-muted d-block small text-uppercase" style="font-size: 0.6rem;">Excesso</small>
                                                <span class="fw-bold text-primary small">+{{ \App\Helpers\CurrencyHelper::format($excess, 0, false) }}</span>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-end gap-2">
                                            <x-ui.button type="link" :href="route('provider.inventory.show', $item->product->sku)" variant="info" icon="eye" size="sm" title="Ver" />
                                            <x-ui.button type="link" :href="route('provider.inventory.movements', ['sku' => $item->product->sku])" variant="primary" icon="clock-history" size="sm" title="Hist." />
                                            <x-ui.button type="link" :href="route('provider.inventory.adjust', $item->product->sku)" variant="secondary" icon="sliders" size="sm" title="Ajuste" />
                                        </div>
                                    </div>
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
                <div class="text-center py-5">
                    <div class="mb-3">
                        <div class="avatar-circle border border-primary shadow-sm text-primary mx-auto" style="width: 80px; height: 80px; font-size: 2rem; display: flex; align-items: center; justify-content: center; background: rgba(13, 110, 253, 0.1);">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold text-body">Tudo em ordem!</h5>
                    <p class="text-muted mx-auto" style="max-width: 400px;">
                        Não há produtos com estoque em excesso no momento.
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Footer Actions -->
    <div class="mt-4 pb-2">
        <div class="row align-items-center g-3">
            <div class="col-12 col-md-auto">
                <x-ui.back-button index-route="provider.inventory.dashboard" class="w-100 w-md-auto px-md-3" />
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
