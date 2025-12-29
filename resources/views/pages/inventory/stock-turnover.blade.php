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
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-filter me-1"></i> Filtros de Busca</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('provider.inventory.stock-turnover') }}">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="start_date">Data Inicial</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $filters['start_date'] }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="end_date">Data Final</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $filters['end_date'] }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="search">Buscar Produto</label>
                                    <input type="text" name="search" id="search" class="form-control" placeholder="Nome ou SKU..." value="{{ $filters['search'] ?? '' }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="category_id">Categoria</label>
                                    <select name="category_id" id="category_id" class="form-select tom-select">
                                        <option value="">Todas</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ $filters['category_id'] == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex gap-2 flex-wrap justify-content-between align-items-center">
                                    <div class="d-flex gap-2">
                                        <x-button type="submit" variant="primary" icon="search" label="Filtrar" />
                                        <x-button type="link" :href="route('provider.inventory.stock-turnover')" variant="secondary" icon="x-circle" label="Limpar" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumo do Período -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-info bg-gradient me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-box text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold">PRODUTOS ANALISADOS</h6>
                    </div>
                    <h3 class="mb-1 fw-bold">{{ $reportData['total_products'] }}</h3>
                    <small class="text-muted">No período selecionado</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-success bg-gradient me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-plus-circle text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold">TOTAL ENTRADAS</h6>
                    </div>
                    <h3 class="mb-1 fw-bold">{{ number_format($reportData['total_entries'], 0, ',', '.') }}</h3>
                    <small class="text-muted">Soma das quantidades</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-danger bg-gradient me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-dash-circle text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold">TOTAL SAÍDAS</h6>
                    </div>
                    <h3 class="mb-1 fw-bold">{{ number_format($reportData['total_exits'], 0, ',', '.') }}</h3>
                    <small class="text-muted">Soma das quantidades</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-warning bg-gradient me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-arrow-repeat text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold">GIRO MÉDIO</h6>
                    </div>
                    <h3 class="mb-1 fw-bold">{{ number_format($reportData['average_turnover'], 2, ',', '.') }}</h3>
                    <small class="text-muted">Vezes no período</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Giro de Estoque -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent">
            <div class="row align-items-center">
                <div class="col-12 col-md-8">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-list-ul me-2"></i>Análise de Giro por Produto
                        <span class="text-muted small">({{ $stockTurnover->total() }} produtos)</span>
                    </h5>
                </div>
                <div class="col-12 col-md-4 text-md-end mt-2 mt-md-0">
                    <div class="dropdown">
                        <x-button variant="outline-secondary" size="sm" icon="download" label="Exportar" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="exportDropdown" />
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
                    <table class="table modern-table mb-0">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Produto</th>
                                <th>Categoria</th>
                                <th class="text-center">Estoque Atual</th>
                                <th class="text-center">Estoque Médio</th>
                                <th class="text-center">Entradas</th>
                                <th class="text-center">Saídas</th>
                                <th class="text-center">Giro</th>
                                <th class="text-center">Classificação</th>
                                <th class="text-center">Ações</th>
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
                                    <td><span class="text-code">{{ $item->sku }}</span></td>
                                    <td>
                                        <a href="{{ route('provider.inventory.show', $item->sku) }}" class="text-decoration-none fw-bold">
                                            {{ $item->name }}
                                        </a>
                                    </td>
                                    <td>{{ $item->category->name ?? 'N/A' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-primary">
                                            {{ number_format($item->inventory->quantity ?? 0, 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="text-center text-muted">
                                        {{ number_format($item->average_stock, 1, ',', '.') }}
                                    </td>
                                    <td class="text-center text-success">
                                        +{{ number_format($item->total_entries, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center text-danger">
                                        -{{ number_format($item->total_exits, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center fw-bold">
                                        {{ number_format($turnover, 2, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $classificationColor }}" data-bs-toggle="tooltip" title="{{ $classificationFull }}">
                                            {{ $classification }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <x-button type="link" :href="route('provider.inventory.show', $item->sku)" variant="info" icon="eye" title="Ver Inventário" size="sm" />
                                            <x-button type="link" :href="route('provider.inventory.movements', ['product_id' => $item->id])" variant="primary" icon="clock-history" title="Ver Movimentações" size="sm" />
                                            <x-button type="link" :href="route('provider.inventory.adjust', $item->sku)" variant="secondary" icon="sliders" title="Ajustar" size="sm" />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Versão Mobile (Cards) -->
                <div class="d-md-none">
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
                        <div class="p-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-0 fw-bold">{{ $item->name }}</h6>
                                    <small class="text-muted">{{ $item->sku }} | {{ $item->category->name ?? 'N/A' }}</small>
                                </div>
                                <span class="badge bg-{{ $classificationColor }}" data-bs-toggle="tooltip" title="{{ $classificationFull }}">{{ $classification }}</span>
                            </div>
                            
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <small class="text-muted d-block">Estoque Atual</small>
                                    <span class="fw-bold">{{ number_format($item->inventory->quantity ?? 0, 0, ',', '.') }}</span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Giro</small>
                                    <span class="fw-bold">{{ number_format($turnover, 2, ',', '.') }}</span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block text-success">Entradas (+)</small>
                                    <span class="text-success">{{ number_format($item->total_entries, 0, ',', '.') }}</span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block text-danger">Saídas (-)</small>
                                    <span class="text-danger">{{ number_format($item->total_exits, 0, ',', '.') }}</span>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <x-button type="link" :href="route('provider.inventory.show', $item->sku)" variant="info" icon="eye" label="Ver" size="sm" class="flex-grow-1" />
                                <x-button type="link" :href="route('provider.inventory.movements', ['product_id' => $item->id])" variant="primary" icon="clock-history" label="Movim." size="sm" class="flex-grow-1" />
                            </div>
                        </div>
                    @endforeach
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
                    <i class="bi bi-info-circle text-muted mb-3" style="font-size: 3rem;"></i>
                    <p class="text-muted">Nenhum dado de giro de estoque disponível para o período selecionado.</p>
                    <a href="{{ route('provider.inventory.stock-turnover') }}" class="btn btn-primary">Limpar Filtros</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    const form = startDate ? startDate.closest('form') : null;

    if (form) {
        form.addEventListener('submit', function(e) {
            if (startDate.value && endDate.value && startDate.value > endDate.value) {
                e.preventDefault();
                if (window.easyAlert) {
                    window.easyAlert.error('A data inicial não pode ser maior que a data final.');
                } else {
                    alert('A data inicial não pode ser maior que a data final.');
                }
                startDate.focus();
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

    // Scripts específicos se necessário
});
</script>
@endsection
