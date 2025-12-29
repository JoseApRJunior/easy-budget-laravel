@extends('layouts.app')

@section('title', 'Relatório de Giro de Estoque')

@section('content')
<div class="container-fluid py-1">
    <x-page-header
        title="Relatório de Giro de Estoque"
        icon="graph-up"
        :breadcrumb-items="[
            'Inventário' => route('provider.inventory.dashboard'),
            'Giro de Estoque' => '#'
        ]">
        <p class="text-muted mb-0">Análise de movimentação e giro de produtos</p>
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
                                    <label for="category_id">Categoria</label>
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
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="d-flex gap-2 flex-nowrap w-100">
                                    <x-button type="submit" icon="search" label="Filtrar" class="w-100" />
                                    <x-button type="link" :href="route('provider.inventory.stock-turnover')" variant="secondary" icon="x" label="Limpar" class="w-100" />
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
                    <x-button type="link" :href="route('provider.inventory.export-stock-turnover', request()->all())" variant="success" icon="file-earmark-excel" label="Exportar Excel" size="sm" />
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            @if($stockTurnover->count() > 0)
                <div class="table-responsive">
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
                                    $classificationColor = '';
                                    
                                    if ($turnover >= 12) {
                                        $classification = 'Muito Alto';
                                        $classificationColor = 'success';
                                    } elseif ($turnover >= 6) {
                                        $classification = 'Alto';
                                        $classificationColor = 'info';
                                    } elseif ($turnover >= 3) {
                                        $classification = 'Médio';
                                        $classificationColor = 'warning';
                                    } elseif ($turnover >= 1) {
                                        $classification = 'Baixo';
                                        $classificationColor = 'danger';
                                    } else {
                                        $classification = 'Muito Baixo';
                                        $classificationColor = 'dark';
                                    }
                                @endphp
                                <tr>
                                    <td><span class="text-code">{{ $item->sku }}</span></td>
                                    <td>
                                        <a href="{{ route('provider.products.show', $item->sku) }}" class="text-decoration-none fw-bold">
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
                                        <span class="badge bg-{{ $classificationColor }}">
                                            {{ $classification }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                <i class="bi bi-three-dots"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="{{ route('provider.inventory.movements', ['product_id' => $item->id]) }}"><i class="bi bi-list me-2"></i>Ver Movimentações</a></li>
                                                <li><a class="dropdown-item" href="{{ route('provider.inventory.adjust', $item->sku) }}"><i class="bi bi-sliders me-2"></i>Ajustar Estoque</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="p-3">
                    {{ $stockTurnover->appends(request()->except('page'))->links() }}
                </div>

                <!-- Legenda e Dicas -->
                <div class="p-4 border-top bg-light bg-opacity-50">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-1"></i> Classificação do Giro</h6>
                            <div class="d-flex flex-wrap gap-2">
                                <div class="p-2 border rounded bg-white" style="flex: 1; min-width: 120px;">
                                    <span class="badge bg-success mb-1">Muito Alto</span>
                                    <div class="small text-muted">≥ 12x no período</div>
                                </div>
                                <div class="p-2 border rounded bg-white" style="flex: 1; min-width: 120px;">
                                    <span class="badge bg-info mb-1">Alto</span>
                                    <div class="small text-muted">6-11x no período</div>
                                </div>
                                <div class="p-2 border rounded bg-white" style="flex: 1; min-width: 120px;">
                                    <span class="badge bg-warning mb-1">Médio</span>
                                    <div class="small text-muted">3-5x no período</div>
                                </div>
                                <div class="p-2 border rounded bg-white" style="flex: 1; min-width: 120px;">
                                    <span class="badge bg-danger mb-1">Baixo</span>
                                    <div class="small text-muted">1-2x no período</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3"><i class="bi bi-lightbulb me-1"></i> Dicas de Gestão</h6>
                            <div class="small">
                                <ul class="mb-0 ps-3">
                                    <li class="mb-1"><strong>Giro Alto:</strong> Revise frequências de reposição para evitar rupturas.</li>
                                    <li class="mb-1"><strong>Giro Médio:</strong> Monitore regularmente e ajuste níveis conforme demanda.</li>
                                    <li class="mb-1"><strong>Giro Baixo:</strong> Avalie promoções ou redução de pedidos para liberar capital.</li>
                                    <li><strong>Sem Giro:</strong> Avalie se o produto deve permanecer no portfólio.</li>
                                </ul>
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
        // Scripts específicos se necessário
    });
</script>
@endsection
