@extends('layouts.app')

@section('title', 'Produtos Mais Utilizados')

@section('content')
@php
    // Variável para controlar se há filtros aplicados (excluindo paginação)
    $hasResults = !empty($filters) && collect($filters)->except(['per_page', 'page'])->filter()->isNotEmpty();
@endphp
<div class="container-fluid py-1">
    <x-page-header
        title="Produtos Mais Utilizados"
        icon="star"
        :breadcrumb-items="[
            'Inventário' => route('provider.inventory.dashboard'),
            'Produtos Mais Utilizados' => '#'
        ]">
        <p class="text-muted small">Análise de produtos com maior frequência de saída no período</p>
    </x-page-header>

    <!-- Filtros -->
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-filter me-1"></i> Filtros de Busca</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('provider.inventory.most-used') }}">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="start_date" class="form-label small fw-bold text-muted text-uppercase">Data Inicial <span class="text-danger">*</span></label>
                                    <input type="text" name="start_date" id="start_date" class="form-control"
                                        placeholder="DD/MM/AAAA" value="{{ $filters['start_date'] ?? '' }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="end_date" class="form-label small fw-bold text-muted text-uppercase">Data Final <span class="text-danger">*</span></label>
                                    <input type="text" name="end_date" id="end_date" class="form-control"
                                        placeholder="DD/MM/AAAA" value="{{ $filters['end_date'] ?? '' }}">
                                </div>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="d-flex gap-2 flex-nowrap w-100 mb-1">
                                    <x-button type="submit" variant="primary" icon="search" label="Filtrar" class="flex-grow-1" />
                                    <x-button type="link" :href="route('provider.inventory.most-used')" variant="outline-secondary" icon="x" label="Limpar" />
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @php
        $totalUsage = $summary['total_usage'] ?? 0;
        $totalValue = $summary['total_value'] ?? 0;
        $averageUsage = $summary['average_usage'] ?? 0;
        $analyzedCount = $summary['total_products'] ?? 0;
    @endphp

    <!-- Resumo do Período -->
    <div class="row mb-4">
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-info text-white me-3" style="width: 35px; height: 35px;">
                            <i class="bi bi-box" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Analisados</h6>
                    </div>
                    <h5 class="mb-0 fw-bold">{{ number_format($analyzedCount, 0, ',', '.') }}</h5>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-success text-white me-3" style="width: 35px; height: 35px;">
                            <i class="bi bi-arrow-up-circle" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Total Saídas</h6>
                    </div>
                    <h5 class="mb-0 fw-bold">{{ number_format($totalUsage, 0, ',', '.') }}</h5>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-warning text-white me-3" style="width: 35px; height: 35px;">
                            <i class="bi bi-currency-dollar" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Valor Total</h6>
                    </div>
                    <h5 class="mb-0 fw-bold text-warning">R$ {{ number_format($totalValue, 2, ',', '.') }}</h5>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-primary text-white me-3" style="width: 35px; height: 35px;">
                            <i class="bi bi-graph-up" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Média Uso</h6>
                    </div>
                    <h5 class="mb-0 fw-bold text-primary">{{ number_format($averageUsage, 1, ',', '.') }}</h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Produtos Mais Utilizados -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header  py-3">
                    <div class="row align-items-center">
                        <div class="col-12 col-lg-8 mb-2 mb-lg-0">
                            <h5 class="mb-0 d-flex align-items-center flex-wrap">
                                <span class="me-2">
                                    <i class="bi bi-list-ul me-1"></i>
                                    <span class="d-none d-sm-inline">Produtos Mais Utilizados</span>
                                    <span class="d-sm-none">Mais Utilizados</span>
                                </span>
                                <span class="text-muted" style="font-size: 0.875rem;">
                                    ({{ $products->total() }})
                                </span>
                            </h5>
                        </div>
                        <div class="col-12 col-lg-4 mt-2 mt-lg-0 text-lg-end">
                            <div class="d-flex justify-content-start justify-content-lg-end gap-2">
                                <div class="dropdown">
                                    <x-button variant="outline-secondary" size="sm" icon="download" label="Exportar" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="exportDropdown" />
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="exportDropdown">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('provider.inventory.export-most-used', array_merge(request()->query(), ['format' => 'xlsx'])) }}">
                                                <i class="bi bi-file-earmark-excel me-2 text-success"></i> Excel (.xlsx)
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('provider.inventory.export-most-used', array_merge(request()->query(), ['format' => 'pdf'])) }}">
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
                    @if(isset($products) && $products->count() > 0)
                        <!-- Desktop View -->
                        <div class="desktop-view">
                            <div class="table-responsive">
                                <table class="modern-table table mb-0">
                                    <thead>
                                        <tr>
                                            <th width="80" class="ps-4">Pos.</th>
                                            <th>Produto</th>
                                            <th class="text-center">Quantidade</th>
                                            <th class="text-end">Valor Total</th>
                                            <th class="text-center" width="150">% do Total</th>
                                            <th class="text-center" width="120">Status</th>
                                            <th class="text-center pe-4" width="150">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($products as $index => $product)
                                            @php
                                                $statusClass = $product['current_stock'] <= 0 ? 'badge-inactive' : ($product['current_stock'] <= $product['min_quantity'] ? 'badge-deleted' : 'badge-active');
                                                $statusLabel = $product['current_stock'] <= 0 ? 'Sem Estoque' : ($product['current_stock'] <= $product['min_quantity'] ? 'Estoque Baixo' : 'Estoque OK');
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="badge bg-light text-primary border shadow-sm">#{{ $index + 1 }}</span>
                                                </td>
                                                <td>
                                                    <div class="item-name-cell">
                                                        <div class="fw-bold text-body">{{ $product['name'] }}</div>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <small class="text-muted text-code" style="font-size: 0.7rem;">{{ $product['sku'] }}</small>
                                                            <span class="text-muted small" style="font-size: 0.7rem;">• {{ $product['category'] ?? 'Geral' }}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <div class="fw-bold text-primary">{{ number_format($product['total_usage'], 0, ',', '.') }}</div>
                                                    <div class="small text-muted" style="font-size: 0.7rem;">{{ number_format($product['average_usage'], 2, ',', '.') }}/dia</div>
                                                </td>
                                                <td class="text-end">
                                                    <div class="fw-bold text-dark">R$ {{ number_format($product['total_value'], 2, ',', '.') }}</div>
                                                    <div class="small text-muted" style="font-size: 0.7rem;">R$ {{ number_format($product['unit_price'], 2, ',', '.') }} un.</div>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex align-items-center justify-content-center gap-2 px-2">
                                                        <div class="progress flex-grow-1" style="height: 6px;">
                                                            <div class="progress-bar" role="progressbar"
                                                                 style="width: {{ $product['percentage_of_total'] }}%"
                                                                 aria-valuenow="{{ $product['percentage_of_total'] }}"
                                                                 aria-valuemin="0" aria-valuemax="100">
                                                            </div>
                                                        </div>
                                                        <small class="fw-bold text-muted">{{ number_format($product['percentage_of_total'], 1, ',', '.') }}%</small>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <span class="modern-badge {{ $statusClass }}">
                                                        {{ $statusLabel }}
                                                    </span>
                                                </td>
                                                <td class="text-center pe-4">
                                                    <div class="action-btn-group justify-content-center">
                                                        <x-button type="link" :href="route('provider.inventory.show', $product['sku'])" variant="info" icon="eye" title="Ver Produto" />
                                                        <x-button type="link" :href="route('provider.inventory.movements', ['sku' => $product['sku']])" variant="primary" icon="clock-history" title="Ver Movimentações" />
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
                                @foreach($products as $index => $product)
                                    @php
                                        $statusClass = $product['current_stock'] <= 0 ? 'badge-inactive' : ($product['current_stock'] <= $product['min_quantity'] ? 'badge-deleted' : 'badge-active');
                                        $statusLabel = $product['current_stock'] <= 0 ? 'Sem Estoque' : ($product['current_stock'] <= $product['min_quantity'] ? 'Estoque Baixo' : 'Estoque OK');
                                    @endphp
                                    <div class="list-group-item py-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-light text-primary border shadow-sm me-2">#{{ $index + 1 }}</span>
                                                <div>
                                                    <div class="fw-bold text-dark small">{{ $product['name'] }}</div>
                                                    <small class="text-muted text-code" style="font-size: 0.65rem;">{{ $product['sku'] }}</small>
                                                </div>
                                            </div>
                                            <span class="modern-badge {{ $statusClass }}" style="font-size: 0.65rem;">
                                                {{ $statusLabel }}
                                            </span>
                                        </div>

                                        <div class="row g-2 mb-3 bg-light rounded p-2 mx-0">
                                            <div class="col-4 text-center">
                                                <small class="text-muted d-block small text-uppercase" style="font-size: 0.6rem;">Saídas</small>
                                                <span class="fw-bold small text-primary">{{ number_format($product['total_usage'], 0, ',', '.') }}</span>
                                            </div>
                                            <div class="col-4 text-center border-start border-end">
                                                <small class="text-muted d-block small text-uppercase" style="font-size: 0.6rem;">% Total</small>
                                                <span class="fw-bold small">{{ number_format($product['percentage_of_total'], 1, ',', '.') }}%</span>
                                            </div>
                                            <div class="col-4 text-center">
                                                <small class="text-muted d-block small text-uppercase" style="font-size: 0.6rem;">Estoque</small>
                                                <span class="fw-bold small">{{ number_format($product['current_stock'], 0, ',', '.') }}</span>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <small class="text-muted d-block small text-uppercase" style="font-size: 0.6rem;">Valor Total</small>
                                                <span class="fw-bold small">R$ {{ number_format($product['total_value'], 2, ',', '.') }}</span>
                                            </div>
                                            <div class="action-btn-group d-flex gap-1">
                                                <x-button type="link" :href="route('provider.inventory.show', $product['sku'])" variant="info" icon="eye" size="sm" title="Ver Produto" />
                                                <x-button type="link" :href="route('provider.inventory.movements', ['sku' => $product['sku']])" variant="primary" icon="clock-history" size="sm" title="Ver Movimentações" />
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="p-5 text-center">
                            <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="bi bi-{{ $hasResults ? 'search' : 'filter' }} text-muted opacity-50 fs-1"></i>
                            </div>
                            <h5 class="fw-bold text-dark">
                                {{ $hasResults ? 'Nenhum dado encontrado' : 'Aguardando Filtros' }}
                            </h5>
                            <p class="text-muted mx-auto mb-0" style="max-width: 400px;">
                                @if($hasResults)
                                    Nenhum produto encontrado com os filtros aplicados para este período.
                                    <br>
                                    <a href="{{ route('provider.inventory.most-used') }}" class="text-primary text-decoration-none small mt-2 d-inline-block">
                                        <i class="bi bi-x-circle me-1"></i>Limpar filtros
                                    </a>
                                @else
                                    Selecione o período acima e clique em <strong>Filtrar</strong> para analisar os produtos mais utilizados.
                                @endif
                            </p>
                        </div>
                    @endif
                </div>

                @if($products->hasPages())
                        @include('partials.components.paginator', [
                            'p' => $products->appends(request()->query()),
                            'show_info' => true
                        ])
                @endif

                <!-- Análise de Curva ABC -->
                <div class="p-5 border-top">
                            <h6 class="fw-bold mb-3 d-flex align-items-center">
                                <i class="bi bi-pie-chart me-2"></i> Análise de Curva ABC
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="card h-100 bg-success bg-opacity-10 border-0 shadow-sm">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <strong class="text-success small text-uppercase">Classe A (80%)</strong>
                                                <span class="badge bg-success text-white">Forte</span>
                                            </div>
                                            <h5 class="mb-1 fw-bold text-dark">{{ $summary['abc_analysis']['class_a']['count'] }} produtos</h5>
                                            <div class="text-muted small">{{ number_format($summary['abc_analysis']['class_a']['percentage'], 1, ',', '.') }}% do volume total</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card h-100 bg-warning bg-opacity-10 border-0 shadow-sm">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <strong class="text-warning small text-uppercase">Classe B (15%)</strong>
                                                <span class="badge bg-warning text-white">Médio</span>
                                            </div>
                                            <h5 class="mb-1 fw-bold text-dark">{{ $summary['abc_analysis']['class_b']['count'] }} produtos</h5>
                                            <div class="text-muted small">{{ number_format($summary['abc_analysis']['class_b']['percentage'], 1, ',', '.') }}% do volume total</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card h-100 bg-info bg-opacity-10 border-0 shadow-sm">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <strong class="text-info small text-uppercase">Classe C (5%)</strong>
                                                <span class="badge bg-info text-white">Baixo</span>
                                            </div>
                                            <h5 class="mb-1 fw-bold text-dark">{{ $summary['abc_analysis']['class_c']['count'] }} produtos</h5>
                                            <div class="text-muted small">{{ number_format($summary['abc_analysis']['class_c']['percentage'], 1, ',', '.') }}% do volume total</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info border-0 shadow-sm mb-0 mt-4 d-flex align-items-center">
                                <i class="bi bi-info-circle fs-5 me-3"></i>
                                <div>
                                    <strong>Período analisado:</strong>
                                    {{ \Carbon\Carbon::parse($filters['start_date'])->format('d/m/Y') }}
                                    até {{ \Carbon\Carbon::parse($filters['end_date'])->format('d/m/Y') }}
                                    <span class="text-muted ms-1">({{ \Carbon\Carbon::parse($filters['start_date'])->diffInDays(\Carbon\Carbon::parse($filters['end_date'])) + 1 }} dias)</span>
                                </div>
                            </div>
                        </div>
            </div>
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
                    Análise gerada em: {{ now()->format('d/m/Y H:i') }}
                </small>
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
    const form = startDate ? startDate.closest('form') : null;

    if (form) {
        if (typeof VanillaMask !== 'undefined') {
            new VanillaMask('start_date', 'date');
            new VanillaMask('end_date', 'date');
        }

        form.addEventListener('submit', function(e) {
            if (startDate.value && endDate.value && startDate.value > endDate.value) {
                e.preventDefault();
                if (window.easyAlert) {
                    window.easyAlert.error('A data inicial não pode ser maior que a data final.');
                } else {
                    alert('A data inicial não pode ser maior que a data final.');
                }
                startDate.focus();
                return;
            }

            if ((startDate.value && !endDate.value) || (!startDate.value && endDate.value)) {
                e.preventDefault();
                const message = 'Para filtrar por período, informe as datas inicial e final.';
                if (window.easyAlert) {
                    window.easyAlert.error(message);
                } else {
                    alert(message);
                }
                if (!startDate.value) startDate.focus();
                else endDate.focus();
            }
        });

        startDate.addEventListener('change', function() {
            if (this.value && endDate.value && this.value > endDate.value) {
                if (window.easyAlert) {
                    window.easyAlert.warning('A data inicial não pode ser maior que a data final.');
                } else {
                    alert('A data inicial não pode ser maior que a data final.');
                }
                this.value = '';
            }
        });

        endDate.addEventListener('change', function() {
            if (this.value && startDate.value && this.value < startDate.value) {
                if (window.easyAlert) {
                    window.easyAlert.warning('A data final não pode ser menor que a data inicial.');
                } else {
                    alert('A data final não pode ser menor que a data inicial.');
                }
                this.value = '';
            }
        });
    }
});
</script>
@endpush
