@extends('layouts.admin')

@section('title', 'Produtos Mais Utilizados')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Produtos Mais Utilizados</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('provider.inventory.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Produtos Mais Utilizados</li>
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
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="start_date">Data Inicial</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $filters['start_date'] }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="end_date">Data Final</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $filters['end_date'] }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i> Filtrar
                                        </button>
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
                            <h4>{{ $products->count() }}</h4>
                            <p>Produtos Analisados</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @php
            $totalUsage = $products->sum('total_usage');
            $totalValue = $products->sum('total_value');
            $averageUsage = $products->avg('total_usage');
        @endphp

        <div class="col-lg-3 col-6">
            <div class="card bg-success">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <h4>{{ $totalUsage }}</h4>
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
                            <h4>R$ {{ number_format($totalValue, 2, ',', '.') }}</h4>
                            <p>Valor Total</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="card bg-primary">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <h4>{{ number_format($averageUsage, 1) }}</h4>
                            <p>Média de Uso</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Produtos Mais Utilizados -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Produtos Mais Utilizados</h3>
                    <div class="card-tools">
                        <a href="{{ route('provider.inventory.dashboard') }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($products->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Posição</th>
                                        <th>SKU</th>
                                        <th>Produto</th>
                                        <th>Categoria</th>
                                        <th>Quantidade Utilizada</th>
                                        <th>Valor Total</th>
                                        <th>% do Total</th>
                                        <th>Estoque Atual</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($products as $index => $product)
                                        <tr>
                                            <td>
                                                <span class="badge badge-primary">
                                                    #{{ $index + 1 }}
                                                </span>
                                            </td>
                                            <td>{{ $product['sku'] }}</td>
                                            <td>{{ $product['name'] }}</td>
                                            <td>{{ $product['category'] ?? 'N/A' }}</td>
                                            <td>
                                                <strong>{{ $product['total_usage'] }}</strong>
                                                <br>
                                                <small class="text-muted">
                                                    Média: {{ number_format($product['average_usage'], 2) }}/dia
                                                </small>
                                            </td>
                                            <td>
                                                R$ {{ number_format($product['total_value'], 2, ',', '.') }}
                                                <br>
                                                <small class="text-muted">
                                                    Unit: R$ {{ number_format($product['unit_price'], 2, ',', '.') }}
                                                </small>
                                            </td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: {{ $product['percentage_of_total'] }}%"
                                                         aria-valuenow="{{ $product['percentage_of_total'] }}" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        {{ number_format($product['percentage_of_total'], 1) }}%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $product['current_stock'] > 0 ? 'success' : 'danger' }}">
                                                    {{ $product['current_stock'] }}
                                                </span>
                                                @if($product['current_stock'] <= $product['min_quantity'])
                                                    <i class="fas fa-exclamation-triangle text-warning" title="Estoque baixo"></i>
                                                @endif
                                            </td>
                                            <td>
                                                @if($product['current_stock'] <= 0)
                                                    <span class="badge badge-danger">Sem Estoque</span>
                                                @elseif($product['current_stock'] <= $product['min_quantity'])
                                                    <span class="badge badge-warning">Estoque Baixo</span>
                                                @else
                                                    <span class="badge badge-success">OK</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('provider.inventory.show', $product['id']) }}" 
                                                       class="btn btn-sm btn-info" 
                                                       title="Ver Produto">
                                                        <i class="fas fa-box"></i>
                                                    </a>
                                                    <a href="{{ route('provider.inventory.movements', ['product_id' => $product['id']]) }}" 
                                                       class="btn btn-sm btn-primary" 
                                                       title="Ver Movimentações">
                                                        <i class="fas fa-list"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Análise de Curva ABC -->
                        <div class="mt-4">
                            <h5>Análise de Curva ABC:</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="alert alert-success">
                                        <strong>Classe A (80%):</strong> 
                                        @php
                                            $classA = $products->filter(function($product) {
                                                return $product['percentage_of_total'] >= 5;
                                            });
                                        @endphp
                                        {{ $classA->count() }} produtos ({{ number_format($classA->sum('percentage_of_total'), 1) }}% do total)
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="alert alert-warning">
                                        <strong>Classe B (15%):</strong> 
                                        @php
                                            $classB = $products->filter(function($product) {
                                                return $product['percentage_of_total'] >= 1 && $product['percentage_of_total'] < 5;
                                            });
                                        @endphp
                                        {{ $classB->count() }} produtos ({{ number_format($classB->sum('percentage_of_total'), 1) }}% do total)
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="alert alert-info">
                                        <strong>Classe C (5%):</strong> 
                                        @php
                                            $classC = $products->filter(function($product) {
                                                return $product['percentage_of_total'] < 1;
                                            });
                                        @endphp
                                        {{ $classC->count() }} produtos ({{ number_format($classC->sum('percentage_of_total'), 1) }}% do total)
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Período Analisado -->
                        <div class="mt-4">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Período analisado:</strong> 
                                {{ \Carbon\Carbon::parse($filters['start_date'])->format('d/m/Y') }} 
                                até {{ \Carbon\Carbon::parse($filters['end_date'])->format('d/m/Y') }}
                                ({{ \Carbon\Carbon::parse($filters['start_date'])->diffInDays(\Carbon\Carbon::parse($filters['end_date'])) + 1 }} dias)
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Nenhum produto encontrado com os filtros aplicados.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection