@extends('layouts.admin')

@section('title', 'Relatório de Giro de Estoque')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Relatório de Giro de Estoque</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('inventory.dashboard') }}">Inventário</a></li>
                    <li class="breadcrumb-item active">Giro de Estoque</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Filtros -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Filtros</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('inventory.stock-turnover') }}">
                        <div class="row">
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
                                    <select name="category_id" id="category_id" class="form-control">
                                        <option value="">Todas as Categorias</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ $filters['category_id'] == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="fas fa-search"></i> Filtrar
                                        </button>
                                        <a href="{{ route('inventory.stock-turnover') }}" class="btn btn-secondary btn-block">
                                            <i class="fas fa-times"></i> Limpar
                                        </a>
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
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="card bg-info">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <h4>{{ $reportData['total_products'] }}</h4>
                            <p>Produtos Analisados</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card bg-success">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <h4>{{ number_format($reportData['total_entries'], 2, ',', '.') }}</h4>
                            <p>Total de Entradas</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card bg-danger">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <h4>{{ number_format($reportData['total_exits'], 2, ',', '.') }}</h4>
                            <p>Total de Saídas</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="card bg-warning">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <h4>{{ number_format($reportData['average_turnover'], 2, ',', '.') }}</h4>
                            <p>Giro Médio</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Giro de Estoque -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Análise de Giro de Estoque</h3>
                    <div class="card-tools">
                        <a href="{{ route('inventory.export-stock-turnover') }}?{{ request()->getQueryString() }}" class="btn btn-sm btn-success">
                            <i class="fas fa-file-excel"></i> Exportar
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($stockTurnover->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>SKU</th>
                                        <th>Produto</th>
                                        <th>Categoria</th>
                                        <th>Estoque Atual</th>
                                        <th>Estoque Médio</th>
                                        <th>Entradas</th>
                                        <th>Saídas</th>
                                        <th>Giro de Estoque</th>
                                        <th>Classificação</th>
                                        <th>Status</th>
                                        <th>Ações</th>
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
                                            <td>{{ $item->sku }}</td>
                                            <td>
                                                <a href="{{ route('inventory.show', $item->id) }}">
                                                    {{ $item->name }}
                                                </a>
                                            </td>
                                            <td>{{ $item->category->name ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge badge-primary">
                                                    {{ number_format($item->current_stock, 2, ',', '.') }} {{ $item->unit }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ number_format($item->average_stock, 2, ',', '.') }} {{ $item->unit }}
                                            </td>
                                            <td>
                                                <span class="text-success">
                                                    +{{ number_format($item->total_entries, 2, ',', '.') }} {{ $item->unit }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-danger">
                                                    -{{ number_format($item->total_exits, 2, ',', '.') }} {{ $item->unit }}
                                                </span>
                                            </td>
                                            <td>
                                                <strong>{{ number_format($turnover, 2, ',', '.') }}</strong>
                                                <small class="text-muted d-block">
                                                    {{ $turnover >= 1 ? 'vezes/ano' : 'vezes no período' }}
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $classificationColor }}">
                                                    {{ $classification }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($item->current_stock <= ($item->min_quantity ?? 0))
                                                    <span class="badge badge-danger">
                                                        <i class="fas fa-exclamation-triangle"></i> Estoque Baixo
                                                    </span>
                                                @elseif($turnover >= 12)
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-check"></i> Giro Ótimo
                                                    </span>
                                                @elseif($turnover >= 6)
                                                    <span class="badge badge-info">
                                                        <i class="fas fa-check"></i> Giro Bom
                                                    </span>
                                                @elseif($turnover >= 3)
                                                    <span class="badge badge-warning">
                                                        <i class="fas fa-exclamation"></i> Giro Médio
                                                    </span>
                                                @else
                                                    <span class="badge badge-danger">
                                                        <i class="fas fa-times"></i> Giro Baixo
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('inventory.movements', ['product_id' => $item->id]) }}" 
                                                       class="btn btn-sm btn-info" 
                                                       title="Ver movimentações">
                                                        <i class="fas fa-list"></i>
                                                    </a>
                                                    <a href="{{ route('inventory.adjustStockForm', $item->id) }}" 
                                                       class="btn btn-sm btn-success" 
                                                       title="Ajustar estoque">
                                                        <i class="fas fa-plus"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-center">
                            {{ $stockTurnover->appends(request()->except('page'))->links() }}
                        </div>

                        <!-- Legenda -->
                        <div class="mt-4">
                            <h5>Legenda de Classificação do Giro de Estoque:</h5>
                            <div class="row">
                                <div class="col-md-2">
                                    <span class="badge badge-success">Muito Alto</span>
                                    <small class="d-block">≥ 12 vezes/ano</small>
                                </div>
                                <div class="col-md-2">
                                    <span class="badge badge-info">Alto</span>
                                    <small class="d-block">6-11 vezes/ano</small>
                                </div>
                                <div class="col-md-2">
                                    <span class="badge badge-warning">Médio</span>
                                    <small class="d-block">3-5 vezes/ano</small>
                                </div>
                                <div class="col-md-2">
                                    <span class="badge badge-danger">Baixo</span>
                                    <small class="d-block">1-2 vezes/ano</small>
                                </div>
                                <div class="col-md-2">
                                    <span class="badge badge-dark">Muito Baixo</span>
                                    <small class="d-block">< 1 vez/ano</small>
                                </div>
                            </div>
                        </div>

                        <!-- Dicas de Gestão -->
                        <div class="mt-4">
                            <h5>Dicas de Gestão:</h5>
                            <div class="alert alert-info">
                                <ul class="mb-0">
                                    <li><strong>Produtos com Giro Alto (≥ 6):</strong> Mantenha estoque de segurança adequado e revise frequências de reposição</li>
                                    <li><strong>Produtos com Giro Médio (3-5):</strong> Monitore regularmente e ajuste níveis de estoque conforme demanda</li>
                                    <li><strong>Produtos com Giro Baixo (< 3):</strong> Considere promover vendas, ajustar preços ou reduzir compras</li>
                                    <li><strong>Produtos Sem Movimentação:</strong> Avalie se devem permanecer no portfólio</li>
                                </ul>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Nenhum dado de giro de estoque disponível para o período selecionado.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Auto-submit form when dates change (optional)
    $('#start_date, #end_date').on('change', function() {
        // Uncomment the line below to auto-submit when dates change
        // $(this).closest('form').submit();
    });
});
</script>
@endsection