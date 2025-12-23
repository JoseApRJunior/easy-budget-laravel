@extends('layouts.admin')

@section('title', 'Produtos Mais Usados')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Produtos Mais Usados</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('provider.inventory.dashboard') }}">Inventário</a></li>
                    <li class="breadcrumb-item active">Produtos Mais Usados</li>
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
                    <form method="GET" action="{{ route('provider.inventory.most-used') }}">
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
                                    <label for="limit">Top</label>
                                    <select name="limit" id="limit" class="form-control">
                                        <option value="10" {{ $filters['limit'] == 10 ? 'selected' : '' }}>Top 10</option>
                                        <option value="20" {{ $filters['limit'] == 20 ? 'selected' : '' }}>Top 20</option>
                                        <option value="50" {{ $filters['limit'] == 50 ? 'selected' : '' }}>Top 50</option>
                                        <option value="100" {{ $filters['limit'] == 100 ? 'selected' : '' }}>Top 100</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="min_quantity">Quantidade Mínima</label>
                                    <input type="number" name="min_quantity" id="min_quantity" class="form-control" value="{{ $filters['min_quantity'] }}" min="0" step="0.01">
                                </div>
                            </div>
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="fas fa-search"></i> Filtrar
                                        </button>
                                        <a href="{{ route('provider.inventory.most-used') }}" class="btn btn-secondary btn-block">
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

    <!-- Resumo -->
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
                            <h4>{{ number_format($reportData['total_quantity_used'], 2, ',', '.') }}</h4>
                            <p>Quantidade Total Usada</p>
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
                            <h4>R$ {{ number_format($reportData['total_value_used'], 2, ',', '.') }}</h4>
                            <p>Valor Total Usado</p>
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
                            <h4>{{ $reportData['average_usage_per_product'] }}</h4>
                            <p>Média de Uso por Produto</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico de Pizza - Top 10 -->
    @if($mostUsedProducts->count() > 0)
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Top 10 Produtos Mais Usados (Quantidade)</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="quantityChart" width="400" height="400"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Top 10 Produtos Mais Usados (Valor)</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="valueChart" width="400" height="400"></canvas>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Tabela de Produtos Mais Usados -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Produtos Mais Usados</h3>
                    <div class="card-tools">
                        <a href="{{ route('provider.inventory.export-most-used') }}?{{ request()->getQueryString() }}" class="btn btn-sm btn-success">
                            <i class="fas fa-file-excel"></i> Exportar
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($mostUsedProducts->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>SKU</th>
                                        <th>Produto</th>
                                        <th>Categoria</th>
                                        <th>Quantidade Usada</th>
                                        <th>Valor Total</th>
                                        <th>Estoque Atual</th>
                                        <th>Última Utilização</th>
                                        <th>Frequência</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($mostUsedProducts as $index => $product)
                                        <tr>
                                            <td>
                                                <span class="badge badge-primary">
                                                    {{ ($mostUsedProducts->currentPage() - 1) * $mostUsedProducts->perPage() + $index + 1 }}
                                                </span>
                                            </td>
                                            <td>{{ $product->sku }}</td>
                                            <td>
                                                <a href="{{ route('provider.inventory.show', $product->id) }}">
                                                    {{ $product->name }}
                                                </a>
                                            </td>
                                            <td>{{ $product->category->name ?? 'N/A' }}</td>
                                            <td>
                                                <strong class="text-primary">
                                                    {{ number_format($product->total_quantity_used, 2, ',', '.') }} {{ $product->unit }}
                                                </strong>
                                            </td>
                                            <td>
                                                <strong class="text-success">
                                                    R$ {{ number_format($product->total_value_used, 2, ',', '.') }}
                                                </strong>
                                            </td>
                                            <td>
                                                @if($product->inventory)
                                                    <span class="badge badge-{{ $product->inventory->quantity <= ($product->inventory->min_quantity ?? 0) ? 'danger' : 'primary' }}">
                                                        {{ number_format($product->inventory->quantity, 2, ',', '.') }} {{ $product->unit }}
                                                    </span>
                                                @else
                                                    <span class="badge badge-secondary">Sem estoque</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($product->last_used_at)
                                                    {{ \Carbon\Carbon::parse($product->last_used_at)->diffForHumans() }}
                                                @else
                                                    <span class="text-muted">Nunca</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-info">
                                                    {{ $product->usage_count }} vezes
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('provider.inventory.movements', ['product_id' => $product->id]) }}" 
                                                       class="btn btn-sm btn-info" 
                                                       title="Ver movimentações">
                                                        <i class="fas fa-list"></i>
                                                    </a>
                                                    @if($product->inventory)
                                                        <a href="{{ route('provider.inventory.adjust', $product->id) }}" 
                                                           class="btn btn-sm btn-success" 
                                                           title="Ajustar estoque">
                                                            <i class="fas fa-plus"></i>
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-center">
                            {{ $mostUsedProducts->appends(request()->except('page'))->links() }}
                        </div>

                        <!-- Análise de Dados -->
                        <div class="mt-4">
                            <h5>Análise de Uso dos Produtos:</h5>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-success"><i class="fas fa-chart-line"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Produtos de Alta Rotatividade</span>
                                            <span class="info-box-number">{{ $mostUsedProducts->where('total_quantity_used', '>=', $reportData['average_usage_per_product'] * 2)->count() }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-warning"><i class="fas fa-exclamation-triangle"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Produtos com Estoque Baixo</span>
                                            <span class="info-box-number">{{ $mostUsedProducts->filter(function($product) { return $product->inventory && $product->inventory->quantity <= ($product->inventory->min_quantity ?? 0); })->count() }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-info"><i class="fas fa-calendar"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Produtos Não Utilizados Recentemente</span>
                                            <span class="info-box-number">{{ $mostUsedProducts->filter(function($product) { return !$product->last_used_at || \Carbon\Carbon::parse($product->last_used_at)->diffInDays() > 30; })->count() }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-danger"><i class="fas fa-dollar-sign"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Valor Total em Uso</span>
                                            <span class="info-box-number">R$ {{ number_format($reportData['total_value_used'], 2, ',', '.') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recomendações -->
                        <div class="mt-4">
                            <h5>Recomendações de Gestão:</h5>
                            <div class="alert alert-info">
                                <ul class="mb-0">
                                    <li><strong>Produtos de Alta Rotatividade:</strong> Mantenha estoque de segurança e considere compras em maior volume para obter melhores preços</li>
                                    <li><strong>Produtos com Estoque Baixo:</strong> Priorize a reposição para evitar ruptura de estoque</li>
                                    <li><strong>Produtos com Baixa Utilização:</strong> Avalie se devem permanecer no portfólio ou se precisam de ações promocionais</li>
                                    <li><strong>Análise de Período:</strong> Compare com períodos anteriores para identificar tendências e sazonalidades</li>
                                </ul>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Nenhum produto encontrado com os filtros aplicados.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    @if($mostUsedProducts->count() > 0)
        // Gráfico de Quantidade
        const quantityCtx = document.getElementById('quantityChart').getContext('2d');
        const quantityData = {
            labels: [
                @foreach($mostUsedProducts->take(10) as $product)
                    '{{ Str::limit($product->name, 20) }}',
                @endforeach
            ],
            datasets: [{
                data: [
                    @foreach($mostUsedProducts->take(10) as $product)
                        {{ $product->total_quantity_used }},
                    @endforeach
                ],
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                    '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384'
                ]
            }]
        };

        new Chart(quantityCtx, {
            type: 'pie',
            data: quantityData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed + ' {{ $mostUsedProducts->first()->unit ?? "un" }}';
                                return label;
                            }
                        }
                    }
                }
            }
        });

        // Gráfico de Valor
        const valueCtx = document.getElementById('valueChart').getContext('2d');
        const valueData = {
            labels: [
                @foreach($mostUsedProducts->take(10) as $product)
                    '{{ Str::limit($product->name, 20) }}',
                @endforeach
            ],
            datasets: [{
                data: [
                    @foreach($mostUsedProducts->take(10) as $product)
                        {{ $product->total_value_used }},
                    @endforeach
                ],
                backgroundColor: [
                    '#4BC0C0', '#FF6384', '#36A2EB', '#FFCE56', '#9966FF',
                    '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384'
                ]
            }]
        };

        new Chart(valueCtx, {
            type: 'pie',
            data: valueData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': R$ ';
                                }
                                label += context.parsed.toFixed(2).replace('.', ',');
                                return label;
                            }
                        }
                    }
                }
            }
        });
    @endif
});
</script>
@endsection