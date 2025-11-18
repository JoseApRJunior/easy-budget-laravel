@extends('layouts.admin')

@section('title', 'Dashboard de Inventário')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Dashboard de Inventário</h1>
        </div>
    </div>

    <!-- Cards de Resumo -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="card bg-info">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <h4>{{ $inventorySummary->count() }}</h4>
                            <p>Produtos com Inventário</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('provider.inventory.index') }}" class="text-white">
                        Ver Detalhes <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="card bg-warning">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <h4>{{ $lowStockProducts->count() }}</h4>
                            <p>Produtos com Estoque Baixo</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('provider.inventory.index', ['status' => 'low']) }}" class="text-white">
                        Ver Produtos <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="card bg-success">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <h4>{{ $inventorySummary->where('quantity', 0)->count() }}</h4>
                            <p>Produtos sem Estoque</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('provider.inventory.index', ['status' => 'out']) }}" class="text-white">
                        Ver Produtos <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="card bg-primary">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <h4>{{ $inventorySummary->sum('quantity') }}</h4>
                            <p>Total de Itens em Estoque</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('provider.inventory.index') }}" class="text-white">
                        Ver Inventário <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Produtos com Estoque Baixo -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Produtos com Estoque Baixo</h3>
                    <div class="card-tools">
                        <a href="{{ route('provider.inventory.index', ['status' => 'low']) }}" class="btn btn-sm btn-outline-primary">
                            Ver Todos
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($lowStockProducts->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>SKU</th>
                                        <th>Produto</th>
                                        <th>Quantidade Atual</th>
                                        <th>Estoque Mínimo</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($lowStockProducts as $item)
                                        <tr>
                                            <td>{{ $item->product->sku }}</td>
                                            <td>{{ $item->product->name }}</td>
                                            <td>{{ $item->quantity }}</td>
                                            <td>{{ $item->min_quantity }}</td>
                                            <td>
                                                <span class="badge badge-warning">
                                                    Estoque Baixo
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('provider.inventory.movements', $item->product) }}" 
                                                       class="btn btn-sm btn-info">
                                                        <i class="fas fa-list"></i> Movimentos
                                                    </a>
                                                    <a href="{{ route('provider.inventory.entry', $item->product) }}" 
                                                       class="btn btn-sm btn-success">
                                                        <i class="fas fa-plus"></i> Entrada
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            Nenhum produto com estoque baixo no momento.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Visão Geral do Inventário -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Visão Geral do Inventário</h3>
                    <div class="card-tools">
                        <a href="{{ route('provider.inventory.index') }}" class="btn btn-sm btn-outline-primary">
                            Ver Inventário Completo
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($inventorySummary->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>SKU</th>
                                        <th>Produto</th>
                                        <th>Quantidade</th>
                                        <th>Mínimo</th>
                                        <th>Máximo</th>
                                        <th>Status</th>
                                        <th>% Utilização</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($inventorySummary->take(10) as $item)
                                        <tr>
                                            <td>{{ $item->product->sku }}</td>
                                            <td>{{ $item->product->name }}</td>
                                            <td>{{ $item->quantity }}</td>
                                            <td>{{ $item->min_quantity }}</td>
                                            <td>{{ $item->max_quantity ?? '-' }}</td>
                                            <td>
                                                @if($item->isLowStock())
                                                    <span class="badge badge-warning">Baixo</span>
                                                @elseif($item->isHighStock())
                                                    <span class="badge badge-info">Alto</span>
                                                @else
                                                    <span class="badge badge-success">Ideal</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($item->max_quantity)
                                                    <div class="progress">
                                                        <div class="progress-bar" role="progressbar" 
                                                             style="width: {{ $item->stock_utilization_percentage }}%"
                                                             aria-valuenow="{{ $item->stock_utilization_percentage }}" 
                                                             aria-valuemin="0" aria-valuemax="100">
                                                            {{ $item->stock_utilization_percentage }}%
                                                        </div>
                                                    </div>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('provider.inventory.movements', $item->product) }}" 
                                                       class="btn btn-sm btn-info">
                                                        <i class="fas fa-list"></i>
                                                    </a>
                                                    <a href="{{ route('provider.inventory.entry', $item->product) }}" 
                                                       class="btn btn-sm btn-success">
                                                        <i class="fas fa-plus"></i>
                                                    </a>
                                                    <a href="{{ route('provider.inventory.exit', $item->product) }}" 
                                                       class="btn btn-sm btn-warning">
                                                        <i class="fas fa-minus"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Nenhum produto com inventário cadastrado.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="bi bi-link-45deg me-2"></i>Atalhos</h3>
                </div>
                <div class="card-body d-grid gap-2">
                    <a href="{{ route('provider.inventory.index') }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-archive me-2"></i>Listar Estoque
                    </a>
                    <a href="{{ route('provider.inventory.index', ['low_stock' => 1]) }}" class="btn btn-outline-warning btn-sm">
                        <i class="bi bi-exclamation-triangle me-2"></i>Baixo Estoque
                    </a>
                    <a href="{{ route('provider.inventory.report') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-clipboard-data me-2"></i>Relatório de Inventário
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection