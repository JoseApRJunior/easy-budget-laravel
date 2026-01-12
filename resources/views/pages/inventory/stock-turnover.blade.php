@extends('layouts.app')

@section('title', 'Relatório de Giro de Estoque')

@php
    // Variável para controlar se há filtros aplicados
    $hasResults = isset($stockTurnover) && $stockTurnover->total() > 0;
    $isFirstAccess = empty(request()->query());
@endphp

@section('content')
<div class="container-fluid py-4">
    <x-layout.page-header
        title="Giro de Estoque"
        icon="graph-up"
        icon-color="warning"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Inventário' => route('provider.inventory.dashboard'),
            'Giro de Estoque' => '#'
        ]">
        <p class="text-muted small mb-0">Análise de movimentação e giro de produtos</p>
    </x-layout.page-header>

    <!-- Filtros -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header py-3">
            <h5 class="mb-0 fw-bold text-muted small text-uppercase">
                <i class="bi bi-filter me-2"></i>Filtros de Análise
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('provider.inventory.stock-turnover') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <x-form.filter-field
                            type="date"
                            name="start_date"
                            id="start_date"
                            label="Data Inicial"
                            :value="$filters['start_date'] ?? ''"
                        />
                    </div>
                    <div class="col-md-3">
                        <x-form.filter-field
                            type="date"
                            name="end_date"
                            id="end_date"
                            label="Data Final"
                            :value="$filters['end_date'] ?? ''"
                        />
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search" class="form-label small fw-bold text-muted text-uppercase">Buscar Produto</label>
                            <input type="text" name="search" id="search" class="form-control" placeholder="Nome ou SKU..." value="{{ $filters['search'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="category_id" class="form-label small fw-bold text-muted text-uppercase">Categoria</label>
                            <select name="category_id" id="category_id" class="form-select tom-select">
                                <option value="">Todas as Categorias</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ ($filters['category_id'] ?? '') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex gap-2">
                            <x-ui.button type="submit" variant="primary" icon="search" label="Filtrar" class="flex-grow-1" />
                            <x-ui.button type="link" :href="route('provider.inventory.stock-turnover')" variant="secondary" icon="x" label="Limpar" />
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($hasResults)
    <!-- Resumo -->
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-primary bg-gradient me-3" style="width: 35px; height: 35px;">
                            <i class="bi bi-box text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Produtos</h6>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold display-6">{{ \App\Helpers\CurrencyHelper::format($reportData['total_products'], 0, false) }}</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-success bg-gradient me-3" style="width: 35px; height: 35px;">
                            <i class="bi bi-plus-circle text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Entradas</h6>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold display-6"><span class="text-success small">+</span>{{ \App\Helpers\CurrencyHelper::format($reportData['total_entries'], 0, false) }}</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-danger bg-gradient me-3" style="width: 35px; height: 35px;">
                            <i class="bi bi-dash-circle text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Saídas</h6>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold display-6"><span class="text-danger small">-</span>{{ \App\Helpers\CurrencyHelper::format($reportData['total_exits'], 0, false) }}</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-warning bg-gradient me-3" style="width: 35px; height: 35px;">
                            <i class="bi bi-arrow-repeat text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold text-uppercase">Giro Médio</h6>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold display-6">{{ \App\Helpers\CurrencyHelper::format($reportData['average_turnover'], 2, false) }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Tabela de Giro de Estoque -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header py-3">
            <div class="row align-items-center">
                <div class="col-12 col-lg-8 mb-2 mb-lg-0">
                    <h5 class="mb-0 fw-bold text-body d-flex align-items-center">
                        <i class="bi bi-graph-up me-2"></i>
                        Giro de Produtos
                        @if($hasResults)
                            <span class="text-muted ms-2 small" style="font-size: 0.875rem;">
                                ({{ $stockTurnover->total() }})
                            </span>
                        @endif
                    </h5>
                </div>
                <div class="col-12 col-lg-4 text-start text-lg-end">
                    @if($hasResults)
                    <div class="d-flex justify-content-start justify-content-lg-end gap-1">
                        <x-ui.button type="link" :href="route('provider.inventory.export-stock-turnover', array_merge(request()->all(), ['type' => 'pdf']))" variant="primary" size="sm" icon="file-earmark-pdf" label="PDF" id="export-pdf" title="Exportar PDF" />
                        <x-ui.button type="link" :href="route('provider.inventory.export-stock-turnover', array_merge(request()->all(), ['type' => 'xlsx']))" variant="success" size="sm" icon="file-earmark-excel" label="Excel" id="export-excel" title="Exportar Excel" />
                    </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            @if($hasResults)
                <div class="table-responsive desktop-view">
                    <table class="table table-hover align-middle mb-0 modern-table">
                        <thead class=" text-muted small text-uppercase fw-bold">
                            <tr>
                                <th class="ps-4">#</th>
                                <th>Produto / SKU</th>
                                <th>Categoria</th>
                                <th class="text-center">Estoque Atual</th>
                                <th class="text-center">Estoque Médio</th>
                                <th class="text-center">Entradas</th>
                                <th class="text-center">Saídas</th>
                                <th class="text-center">Giro</th>
                                <th class="text-center">Classificação</th>
                                <th class="text-center pe-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stockTurnover as $index => $item)
                                @php
                                    $turnover = $item->average_stock > 0 ? $item->total_exits / $item->average_stock : 0;
                                    $classification = '';
                                    $classificationFull = '';
                                    $classificationColor = '';

                                    if ($turnover >= 12) {
                                        $classification = 'MA';
                                        $classificationFull = 'Muito Alto';
                                        $classificationColor = 'success';
                                    } elseif ($turnover >= 6) {
                                        $classification = 'A';
                                        $classificationFull = 'Alto';
                                        $classificationColor = 'info';
                                    } elseif ($turnover >= 3) {
                                        $classification = 'M';
                                        $classificationFull = 'Médio';
                                        $classificationColor = 'warning';
                                    } elseif ($turnover >= 1) {
                                        $classification = 'B';
                                        $classificationFull = 'Baixo';
                                        $classificationColor = 'danger';
                                    } else {
                                        $classification = 'MB';
                                        $classificationFull = 'Muito Baixo';
                                        $classificationColor = 'dark';
                                    }
                                @endphp
                                <tr>
                                    <td class="ps-4">
                                        <span class="badge  text-primary border shadow-sm">#{{ ($stockTurnover->currentPage() - 1) * $stockTurnover->perPage() + $index + 1 }}</span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $item->name }}</div>
                                        <div class="small text-muted text-code">{{ $item->sku }}</div>
                                    </td>
                                    <td class="text-muted small">{{ $item->category->name ?? 'Geral' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">
                                            {{ \App\Helpers\CurrencyHelper::format($item->inventory->quantity ?? 0, 0, false) }}
                                        </span>
                                    </td>
                                    <td class="text-center text-muted small">
                                        {{ \App\Helpers\CurrencyHelper::format($item->average_stock, 1, false) }}
                                    </td>
                                    <td class="text-center">
                                        <span class="text-success fw-bold">+{{ \App\Helpers\CurrencyHelper::format($item->total_entries, 0, false) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-danger fw-bold">-{{ \App\Helpers\CurrencyHelper::format($item->total_exits, 0, false) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-bold text-dark">{{ \App\Helpers\CurrencyHelper::format($turnover, 2, false) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="modern-badge badge-{{ $classificationColor }}"
                                              data-bs-toggle="tooltip" title="{{ $classificationFull }}">
                                            {{ $classification }}
                                        </span>
                                    </td>
                                    <td class="text-center pe-4">
                                        <div class="action-btn-group justify-content-center">
                                            <x-ui.button type="link" :href="route('provider.inventory.movements', ['sku' => $item->sku])" variant="info" size="sm" icon="clock-history" title="Ver Movimentações" />
                                            <x-ui.button type="link" :href="route('provider.inventory.adjust', $item->sku)" variant="success" size="sm" icon="sliders" title="Ajustar" />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Versão Mobile (List Group) -->
                <div class="mobile-view">
                    <div class="list-group list-group-flush">
                        @foreach($stockTurnover as $index => $item)
                            @php
                                $turnover = $item->average_stock > 0 ? $item->total_exits / $item->average_stock : 0;
                                $classification = '';
                                $classificationFull = '';
                                $classificationColor = '';

                                if ($turnover >= 12) {
                                    $classification = 'MA';
                                    $classificationFull = 'Muito Alto';
                                    $classificationColor = 'success';
                                } elseif ($turnover >= 6) {
                                    $classification = 'A';
                                    $classificationFull = 'Alto';
                                    $classificationColor = 'info';
                                } elseif ($turnover >= 3) {
                                    $classification = 'M';
                                    $classificationFull = 'Médio';
                                    $classificationColor = 'warning';
                                } elseif ($turnover >= 1) {
                                    $classification = 'B';
                                    $classificationFull = 'Baixo';
                                    $classificationColor = 'danger';
                                } else {
                                    $classification = 'MB';
                                    $classificationFull = 'Muito Baixo';
                                    $classificationColor = 'dark';
                                }
                            @endphp
                            <div class="list-group-item px-3 py-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center">
                                        <span class="badge  text-primary border shadow-sm me-2">#{{ ($stockTurnover->currentPage() - 1) * $stockTurnover->perPage() + $index + 1 }}</span>
                                        <div>
                                            <div class="fw-bold text-dark small">{{ $item->name }}</div>
                                            <div class="small text-muted text-code" style="font-size: 0.65rem;">{{ $item->sku }} | {{ $item->category->name ?? 'Geral' }}</div>
                                        </div>
                                    </div>
                                    <span class="modern-badge badge-{{ $classificationColor }}" style="font-size: 0.65rem;">
                                        {{ $classification }}
                                    </span>
                                </div>

                                <div class="row g-2 mb-3  rounded p-2 mx-0">
                                    <div class="col-6">
                                        <small class="text-muted d-block small text-uppercase" style="font-size: 0.6rem;">Estoque</small>
                                        <span class="fw-bold small">{{ \App\Helpers\CurrencyHelper::format($item->inventory->quantity ?? 0, 0, false) }}</span>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block small text-uppercase" style="font-size: 0.6rem;">Giro</small>
                                        <span class="fw-bold small text-primary">{{ \App\Helpers\CurrencyHelper::format($turnover, 2, false) }}</span>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block small text-uppercase" style="font-size: 0.6rem;">Entradas (+)</small>
                                        <span class="text-success fw-bold small">{{ \App\Helpers\CurrencyHelper::format($item->total_entries, 0, false) }}</span>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block small text-uppercase" style="font-size: 0.6rem;">Saídas (-)</small>
                                        <span class="text-danger fw-bold small">{{ \App\Helpers\CurrencyHelper::format($item->total_exits, 0, false) }}</span>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <x-ui.button type="link" :href="route('provider.inventory.movements', ['sku' => $item->sku])" variant="info" size="sm" icon="clock-history" title="Histórico" />
                                    <x-ui.button type="link" :href="route('provider.inventory.adjust', $item->sku)" variant="success" size="sm" icon="sliders" title="Ajuste" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
 @if ($stockTurnover instanceof \Illuminate\Pagination\LengthAwarePaginator && $stockTurnover->hasPages())
                @include('partials.components.paginator', [
                    'p' => $stockTurnover->appends(
                        collect(request()->query())->map(fn($v) => is_null($v) ? '' : $v)->toArray()
                    ),
                    'show_info' => true,
                ])
        @endif
                <!-- Legenda e Dicas -->
                <div class="p-4 border-top  bg-opacity-50">
                    <div class="row g-4">
                        <div class="col-md-7">
                            <h6 class="fw-bold text-muted small text-uppercase mb-3">
                                <i class="bi bi-info-circle me-2"></i>Entendendo a Classificação de Giro
                            </h6>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="d-flex align-items-center">
                                    <span class="modern-badge badge-success me-2">MA</span>
                                    <small class="text-muted">Muito Alto (>= 12x)</small>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="modern-badge badge-info me-2">A</span>
                                    <small class="text-muted">Alto (>= 6x)</small>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="modern-badge badge-warning me-2">M</span>
                                    <small class="text-muted">Médio (>= 3x)</small>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="modern-badge badge-danger me-2">B</span>
                                    <small class="text-muted">Baixo (>= 1x)</small>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="modern-badge badge-dark me-2">MB</span>
                                    <small class="text-muted">Muito Baixo (< 1x)</small>
                                </div>
                            </div>

                            <div class="alert alert-info border-0 shadow-sm mb-0 mt-4 d-flex align-items-center">
                                <i class="bi bi-info-circle fs-5 me-3"></i>
                                <div>
                                    <strong>Período analisado:</strong>
                                    @if(!empty($filters['start_date']) && !empty($filters['end_date']))
                                        {{ $filters['start_date'] }} até {{ $filters['end_date'] }}
                                    @else
                                        Todo o período histórico
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <h6 class="fw-bold text-muted small text-uppercase mb-3">
                                <i class="bi bi-lightbulb me-2"></i>Dica de Gestão
                            </h6>
                            <div class="p-3 rounded border border-light-subtle shadow-sm">
                                <p class="small text-muted mb-0">
                                    <strong>Giro Alto (MA/A):</strong> Produtos que saem rápido. Mantenha o estoque sempre em dia para não perder vendas/uso.
                                    <br><br>
                                    <strong>Giro Baixo (B/MB):</strong> Produtos "parados". Podem estar ocupando espaço e capital. Considere reduzir as compras ou fazer promoções.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <div class="avatar-circle  shadow-sm mx-auto mb-3" style="width: 80px; height: 80px;">
                        <i class="bi bi-graph-up text-muted" style="font-size: 2rem;"></i>
                    </div>
                    @if($isFirstAccess)
                        <h5 class="fw-bold text-dark">Análise de Giro de Estoque</h5>
                        <p class="text-muted mx-auto mb-4" style="max-width: 400px;">
                            Utilize os filtros acima para analisar a movimentação dos produtos e o giro de estoque em um período específico.
                        </p>
                    @else
                        <h5 class="fw-bold text-dark">Nenhum resultado encontrado</h5>
                        <p class="text-muted mx-auto mb-4" style="max-width: 400px;">
                            Não foram encontradas movimentações para os filtros selecionados. Tente ajustar o período ou a busca.
                        </p>
                    @endif
                    <x-ui.button type="link" :href="route('provider.inventory.stock-turnover')" variant="outline-primary" icon="arrow-left" label="Limpar Filtros" />
                </div>
            @endif
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

    if (!form || !startDate || !endDate) return;

    // Função utilitária para converter DD/MM/YYYY em objeto Date
    const parseDate = (str) => {
        if (!str) return null;
        const parts = str.split('/');
        if (parts.length === 3) {
            const d = new Date(parts[2], parts[1] - 1, parts[0]);
            return isNaN(d.getTime()) ? null : d;
        }
        return null;
    };

    const validateDates = (input) => {
        if (!startDate || !endDate || !startDate.value || !endDate.value) return true;

        const start = parseDate(startDate.value);
        const end = parseDate(endDate.value);

        if (start && end && start > end) {
            if (window.easyAlert) {
                window.easyAlert.warning('A data inicial não pode ser maior que a data final.');
            } else {
                alert('A data inicial não pode ser maior que a data final.');
            }
            if (input) input.value = '';
            return false;
        }
        return true;
    };

    // Aplicar máscaras (opcional se já carregado globalmente)
    if (window.VMasker) {
        VMasker(startDate).maskPattern("99/99/9999");
        VMasker(endDate).maskPattern("99/99/9999");
    }

    startDate.addEventListener('change', function() { validateDates(this); });
    endDate.addEventListener('change', function() { validateDates(this); });

    form.addEventListener('submit', function(e) {
        if (!validateDates()) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
