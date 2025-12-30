@extends('layouts.app')

@section('title', 'Relatório de Giro de Estoque')

@section('content')
<div class="container-fluid py-1">
    <x-page-header
        title="Giro de Estoque"
        icon="graph-up"
        :breadcrumb-items="[
            'Inventário' => route('provider.inventory.index'),
            'Giro de Estoque' => '#'
        ]">
        <p class="text-muted small">Análise de movimentação e giro de produtos</p>
    </x-page-header>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold text-muted small text-uppercase">
                <i class="bi bi-filter me-2"></i>Filtros
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('provider.inventory.stock-turnover') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="start_date" class="form-label small fw-bold text-muted text-uppercase">Data Inicial <span class="text-danger">*</span></label>
                            <input type="text" name="start_date" id="start_date" class="form-control"
                                placeholder="DD/MM/AAAA" value="{{ $filters['start_date'] }}"
                                data-mask="00/00/0000">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="end_date" class="form-label small fw-bold text-muted text-uppercase">Data Final <span class="text-danger">*</span></label>
                            <input type="text" name="end_date" id="end_date" class="form-control"
                                placeholder="DD/MM/AAAA" value="{{ $filters['end_date'] }}"
                                data-mask="00/00/0000">
                        </div>
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
                                    <option value="{{ $category->id }}" {{ $filters['category_id'] == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex gap-2">
                            <x-button type="submit" variant="primary" icon="search" label="Filtrar" class="flex-grow-1" />
                            <x-button type="link" :href="route('provider.inventory.stock-turnover')" variant="outline-secondary" icon="x" label="Limpar" />
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Resumo -->
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-6">
            <div class="card border-0 shadow-sm h-100 bg-primary bg-opacity-10">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="rounded-circle bg-primary bg-opacity-25 p-2 me-3">
                            <i class="bi bi-box text-primary fs-4"></i>
                        </div>
                        <h6 class="card-subtitle text-muted mb-0 small fw-bold text-uppercase">Produtos</h6>
                    </div>
                    <h4 class="card-title mb-0 fw-bold">{{ $reportData['total_products'] }}</h4>
                    <p class="text-muted small mb-0">Produtos Analisados</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card border-0 shadow-sm h-100 bg-success bg-opacity-10">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="rounded-circle bg-success bg-opacity-25 p-2 me-3">
                            <i class="bi bi-plus-circle text-success fs-4"></i>
                        </div>
                        <h6 class="card-subtitle text-muted mb-0 small fw-bold text-uppercase">Entradas</h6>
                    </div>
                    <h4 class="card-title mb-0 fw-bold">{{ number_format($reportData['total_entries'], 0, ',', '.') }}</h4>
                    <p class="text-muted small mb-0">Total no Período</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card border-0 shadow-sm h-100 bg-danger bg-opacity-10">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="rounded-circle bg-danger bg-opacity-25 p-2 me-3">
                            <i class="bi bi-dash-circle text-danger fs-4"></i>
                        </div>
                        <h6 class="card-subtitle text-muted mb-0 small fw-bold text-uppercase">Saídas</h6>
                    </div>
                    <h4 class="card-title mb-0 fw-bold">{{ number_format($reportData['total_exits'], 0, ',', '.') }}</h4>
                    <p class="text-muted small mb-0">Total no Período</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card border-0 shadow-sm h-100 bg-warning bg-opacity-10">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="rounded-circle bg-warning bg-opacity-25 p-2 me-3">
                            <i class="bi bi-arrow-repeat text-warning fs-4"></i>
                        </div>
                        <h6 class="card-subtitle text-muted mb-0 small fw-bold text-uppercase">Giro Médio</h6>
                    </div>
                    <h4 class="card-title mb-0 fw-bold">{{ number_format($reportData['average_turnover'], 2, ',', '.') }}</h4>
                    <p class="text-muted small mb-0">Vezes no Período</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Giro de Estoque -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <div class="row align-items-center">
                <div class="col-12 col-lg-8 mb-2 mb-lg-0">
                    <h5 class="mb-0 fw-bold text-muted small text-uppercase">
                        <i class="bi bi-graph-up me-2"></i>Giro de Produtos
                    </h5>
                </div>
                <div class="col-12 col-lg-4 text-start text-lg-end">
                    <div class="dropdown d-inline-block">
                        <x-button variant="success" size="sm" icon="download" label="Exportar" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="exportDropdown" />
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="exportDropdown">
                            <li>
                                <a class="dropdown-item"
                                    href="{{ route('provider.inventory.export-stock-turnover', array_merge(request()->all(), ['type' => 'xlsx'])) }}">
                                    <i class="bi bi-file-earmark-excel me-2 text-success"></i> Excel (.xlsx)
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item"
                                    href="{{ route('provider.inventory.export-stock-turnover', array_merge(request()->all(), ['type' => 'pdf'])) }}">
                                    <i class="bi bi-file-earmark-pdf me-2 text-danger"></i> PDF (.pdf)
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            @if($stockTurnover->count() > 0)
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-hover align-middle mb-0 modern-table">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-3">SKU / Produto</th>
                                <th class="px-3">Categoria</th>
                                <th class="px-3 text-center">Estoque Atual</th>
                                <th class="px-3 text-center">Estoque Médio</th>
                                <th class="px-3 text-center">Entradas</th>
                                <th class="px-3 text-center">Saídas</th>
                                <th class="px-3 text-center">Giro</th>
                                <th class="px-3 text-center">Classificação</th>
                                <th class="px-3 text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stockTurnover as $item)
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
                                    <td class="px-3">
                                        <div class="fw-bold text-dark">{{ $item->name }}</div>
                                        <div class="small text-muted">{{ $item->sku }}</div>
                                    </td>
                                    <td class="px-3 text-muted small">{{ $item->category->name ?? 'N/A' }}</td>
                                    <td class="px-3 text-center">
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">
                                            {{ number_format($item->inventory->quantity ?? 0, 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="px-3 text-center text-muted small">
                                        {{ number_format($item->average_stock, 1, ',', '.') }}
                                    </td>
                                    <td class="px-3 text-center">
                                        <span class="text-success fw-bold">+{{ number_format($item->total_entries, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="px-3 text-center">
                                        <span class="text-danger fw-bold">-{{ number_format($item->total_exits, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="px-3 text-center">
                                        <span class="fw-bold text-dark">{{ number_format($turnover, 2, ',', '.') }}</span>
                                    </td>
                                    <td class="px-3 text-center">
                                        <span class="badge bg-{{ $classificationColor }} bg-opacity-10 text-{{ $classificationColor }} border border-{{ $classificationColor }} border-opacity-25 px-2 py-1"
                                              data-bs-toggle="tooltip" title="{{ $classificationFull }}">
                                            {{ $classification }}
                                        </span>
                                    </td>
                                    <td class="px-3 text-center">
                                        <div class="btn-group">
                                            <x-button type="link" :href="route('provider.inventory.movements', ['sku' => $item->sku])" variant="outline-info" size="sm" icon="clock-history" title="Ver Movimentações" />
                                            <x-button type="link" :href="route('provider.inventory.adjust', $item->sku)" variant="outline-success" size="sm" icon="sliders" title="Ajustar" />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Versão Mobile (List Group) -->
                <div class="d-md-none">
                    <div class="list-group list-group-flush">
                        @foreach($stockTurnover as $item)
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
                                    <div>
                                        <div class="fw-bold text-dark">{{ $item->name }}</div>
                                        <div class="small text-muted">{{ $item->sku }} | {{ $item->category->name ?? 'N/A' }}</div>
                                    </div>
                                    <span class="badge bg-{{ $classificationColor }} bg-opacity-10 text-{{ $classificationColor }} border border-{{ $classificationColor }} border-opacity-25 px-2 py-1">
                                        {{ $classification }}
                                    </span>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Estoque Atual</div>
                                        <div class="fw-bold">{{ number_format($item->inventory->quantity ?? 0, 0, ',', '.') }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Giro</div>
                                        <div class="fw-bold text-primary">{{ number_format($turnover, 2, ',', '.') }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Entradas (+)</div>
                                        <div class="text-success fw-bold">{{ number_format($item->total_entries, 0, ',', '.') }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Saídas (-)</div>
                                        <div class="text-danger fw-bold">{{ number_format($item->total_exits, 0, ',', '.') }}</div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <x-button type="link" :href="route('provider.inventory.movements', ['sku' => $item->sku])" variant="outline-info" size="sm" icon="clock-history" label="Movimentações" class="flex-grow-1" />
                                    <x-button type="link" :href="route('provider.inventory.adjust', $item->sku)" variant="outline-success" size="sm" icon="sliders" label="Ajustar" class="flex-grow-1" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if($stockTurnover->hasPages())
                    <div class="mt-2 mb-2">
                        @include('partials.components.paginator', [
                            'p' => $stockTurnover->appends(request()->except('page')),
                            'show_info' => true
                        ])
                    </div>
                @endif

                <!-- Legenda e Dicas -->
                <div class="p-4 border-top bg-light bg-opacity-50">
                    <div class="row g-4">
                        <div class="col-md-7">
                            <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-1"></i> Legenda de Classificação</h6>
                            <div class="d-flex flex-wrap gap-2">
                                <div class="p-2 border rounded bg-white flex-grow-1" style="min-width: 120px;">
                                    <span class="badge bg-success mb-1">MA</span>
                                    <div class="small fw-bold">Muito Alto</div>
                                    <div class="small text-muted">≥ 12x no período</div>
                                </div>
                                <div class="p-2 border rounded bg-white flex-grow-1" style="min-width: 120px;">
                                    <span class="badge bg-info mb-1">A</span>
                                    <div class="small fw-bold">Alto</div>
                                    <div class="small text-muted">6-11x no período</div>
                                </div>
                                <div class="p-2 border rounded bg-white flex-grow-1" style="min-width: 120px;">
                                    <span class="badge bg-warning mb-1">M</span>
                                    <div class="small fw-bold">Médio</div>
                                    <div class="small text-muted">3-5x no período</div>
                                </div>
                                <div class="p-2 border rounded bg-white flex-grow-1" style="min-width: 120px;">
                                    <span class="badge bg-danger mb-1">B</span>
                                    <div class="small fw-bold">Baixo</div>
                                    <div class="small text-muted">1-2x no período</div>
                                </div>
                                <div class="p-2 border rounded bg-white flex-grow-1" style="min-width: 120px;">
                                    <span class="badge bg-dark mb-1">MB</span>
                                    <div class="small fw-bold">Muito Baixo</div>
                                    <div class="small text-muted">< 1x no período</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <h6 class="fw-bold mb-3"><i class="bi bi-lightbulb me-1"></i> Dicas de Gestão</h6>
                            <div class="card border-0 shadow-none bg-transparent">
                                <div class="card-body p-0 small">
                                    <div class="d-flex mb-2">
                                        <i class="bi bi-check2-circle text-success me-2"></i>
                                        <span><strong>MA / A:</strong> Produtos com alta saída. Garanta estoque para não perder vendas.</span>
                                    </div>
                                    <div class="d-flex mb-2">
                                        <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                                        <span><strong>M:</strong> Desempenho equilibrado. Mantenha os níveis atuais.</span>
                                    </div>
                                    <div class="d-flex">
                                        <i class="bi bi-graph-down text-danger me-2"></i>
                                        <span><strong>B / MB:</strong> Baixa saída. Evite excesso de estoque e capital parado.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="bi bi-graph-up text-muted opacity-50 fs-1"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Nenhum dado encontrado</h5>
                    <p class="text-muted mx-auto" style="max-width: 400px;">
                        Não encontramos dados de giro de estoque para o período selecionado ou com os filtros aplicados.
                    </p>
                    <x-button type="link" :href="route('provider.inventory.stock-turnover')" variant="primary" icon="x-circle" label="Limpar Filtros" />
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
