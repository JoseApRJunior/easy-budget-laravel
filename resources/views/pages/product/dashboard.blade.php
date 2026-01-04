@extends('layouts.app')

@section('title', 'Dashboard de Produtos')

@section('content')
<div class="container-fluid py-4">
    <x-page-header
        title="Dashboard de Produtos"
        icon="box-seam"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Produtos' => '#'
        ]">
        <p class="text-muted mb-0 small">Visão geral do seu catálogo de produtos com atalhos de gestão.</p>
    </x-page-header>

    @php
    $total = $stats['total_products'] ?? 0;
    $active = $stats['active_products'] ?? 0;
    $inactive = $stats['inactive_products'] ?? 0;
    $deleted = $stats['deleted_products'] ?? 0;
    $recent = $stats['recent_products'] ?? collect();

    $avgMargin = $stats['average_profit_margin'] ?? 0;
    $inventoryCost = $stats['total_inventory_cost'] ?? 0;
    $inventorySale = $stats['total_inventory_sale'] ?? 0;
    $potentialProfit = $inventorySale - $inventoryCost;

    $activityRate = $total > 0 ? number_format(($active / $total) * 100, 1, ',', '.') : 0;
    @endphp

    <!-- Cards de Métricas de Estoque e Lucro -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100 bg-primary bg-gradient text-white">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-white bg-opacity-25 me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-cash-stack text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-white text-opacity-75 mb-0 small fw-bold">VALOR EM ESTOQUE (VENDA)</h6>
                    </div>
                    <h3 class="mb-1 fw-bold">R$ {{ number_format($inventorySale, 2, ',', '.') }}</h3>
                    <p class="text-white text-opacity-75 small-text mb-0">Preço total de venda.</p>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100 bg-success bg-gradient text-white">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-white bg-opacity-25 me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-graph-up-arrow text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-white text-opacity-75 mb-0 small fw-bold">LUCRO POTENCIAL</h6>
                    </div>
                    <h3 class="mb-1 fw-bold">R$ {{ number_format($potentialProfit, 2, ',', '.') }}</h3>
                    <p class="text-white text-opacity-75 small-text mb-0">Diferença venda vs custo.</p>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100 bg-info bg-gradient text-white">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-white bg-opacity-25 me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-percent text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-white text-opacity-75 mb-0 small fw-bold">MARGEM MÉDIA</h6>
                    </div>
                    <h3 class="mb-1 fw-bold">{{ number_format($avgMargin, 1, ',', '.') }}%</h3>
                    <p class="text-white text-opacity-75 small-text mb-0">Média de todos os produtos.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Cards de Métricas de Quantidade -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-5-custom">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-primary bg-gradient me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-box-seam text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold">TOTAL</h6>
                    </div>
                    <h3 class="mb-1 fw-bold">{{ $total }}</h3>
                    <p class="text-muted small-text mb-0">Ativos e inativos.</p>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-5-custom">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-success bg-gradient me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-check-circle-fill text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold">ATIVOS</h6>
                    </div>
                    <h3 class="mb-1 fw-bold text-success">{{ $active }}</h3>
                    <p class="text-muted small-text mb-0">Disponíveis para uso.</p>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-5-custom">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-secondary bg-gradient me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-pause-circle-fill text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold">INATIVOS</h6>
                    </div>
                    <h3 class="mb-1 fw-bold text-secondary">{{ $inactive }}</h3>
                    <p class="text-muted small-text mb-0">Suspensos temporariamente.</p>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-5-custom">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-danger bg-gradient me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-trash3-fill text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold">DELETADOS</h6>
                    </div>
                    <h3 class="mb-1 fw-bold text-danger">{{ $deleted }}</h3>
                    <p class="text-muted small-text mb-0">Na lixeira.</p>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-5-custom">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-info bg-gradient me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-percent text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold">TAXA USO</h6>
                    </div>
                    <h3 class="mb-1 fw-bold text-info">{{ $activityRate }}%</h3>
                    <p class="text-muted small-text mb-0">Percentual de ativos.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Conteúdo Principal -->
    <div class="row g-4">
        <!-- Produtos Recentes -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history me-2"></i>
                        <span class="d-none d-sm-inline">Produtos Recentes</span>
                        <span class="d-sm-none">Recentes</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if ($recent instanceof \Illuminate\Support\Collection && $recent->isNotEmpty())
                    <!-- Desktop View -->
                    <div class="desktop-view">
                        <div class="table-responsive">
                            <table class="modern-table table mb-0">
                                <thead>
                                    <tr>
                                        <th>Produto</th>
                                        <th>Categoria</th>
                                        <th class="text-nowrap">Venda</th>
                                        <th class="text-nowrap">Margem</th>
                                        <th>Status</th>
                                        <th>Criado em</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recent as $product)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-box-seam me-2 text-muted"></i>
                                                <span>{{ $product->name }}</span>
                                            </div>
                                        </td>
                                        <td>{{ $product->category->name ?? '—' }}</td>
                                        <td class="text-nowrap">{{ $product->formatted_price }}</td>
                                        <td>
                                            @if($product->cost_price > 0)
                                                <span class="badge bg-{{ $product->profit_margin_percentage >= 30 ? 'success' : ($product->profit_margin_percentage >= 15 ? 'warning' : 'danger') }}-subtle text-{{ $product->profit_margin_percentage >= 30 ? 'success' : ($product->profit_margin_percentage >= 15 ? 'warning' : 'danger') }}">
                                                    {{ number_format($product->profit_margin_percentage, 1, ',', '.') }}%
                                                </span>
                                            @else
                                                <span class="text-muted small">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($product->active)
                                            <span class="badge bg-success-subtle text-success">Ativo</span>
                                            @else
                                            <span class="badge bg-danger-subtle text-danger">Inativo</span>
                                            @endif
                                        </td>
                                        <td>{{ optional($product->created_at)->format('d/m/Y') }}</td>
                                        <td class="text-center">
                                            <x-button type="link" :href="route('provider.products.show', $product->sku)"
                                                    variant="info" size="sm" icon="eye" />
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Mobile View -->
                    <div class="mobile-view">
                        <div class="list-group ">
                            @foreach ($recent as $product)
                            <div class="list-group-item py-3">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-box-seam text-muted me-2 mt-1"></i>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold mb-1">{{ $product->name }}</div>
                                        <div class="d-flex gap-2 flex-wrap mb-2">
                                            @if ($product->active)
                                            <span class="badge bg-success-subtle text-success">Ativo</span>
                                            @else
                                            <span class="badge bg-danger-subtle text-danger">Inativo</span>
                                            @endif
                                            @if($product->cost_price > 0)
                                            <span class="badge bg-{{ $product->profit_margin_percentage >= 30 ? 'success' : ($product->profit_margin_percentage >= 15 ? 'warning' : 'danger') }}-subtle text-{{ $product->profit_margin_percentage >= 30 ? 'success' : ($product->profit_margin_percentage >= 15 ? 'warning' : 'danger') }}">
                                                {{ number_format($product->profit_margin_percentage, 1, ',', '.') }}%
                                            </span>
                                            @endif
                                        </div>
                                        <div class="small text-muted">
                                            Venda: {{ $product->formatted_price }}
                                        </div>
                                    </div>
                                    <div class="ms-2">
                                        <x-button type="link" :href="route('provider.products.show', $product->sku)"
                                            variant="info" size="sm" icon="eye" />
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @else
                    <div class="p-4">
                        <p class="text-muted mb-0">
                            Nenhum produto recente encontrado. Cadastre novos produtos para visualizar aqui.
                        </p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Insights e Atalhos -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-transparent border-0">
                    <h6 class="mb-0">
                        <i class="bi bi-lightbulb me-2"></i>Insights Rápidos
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0 small text-muted">
                        <li class="mb-2">
                            <i class="bi bi-box-arrow-in-up-right text-primary me-2"></i>
                            Mantenha os produtos mais usados sempre ativos para agilizar orçamentos.
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-tag-fill text-success me-2"></i>
                            Use categorias e unidades para padronizar seu catálogo.
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                            Revise produtos inativos que ainda são utilizados em serviços.
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <h6 class="mb-0">
                        <i class="bi bi-link-45deg me-2"></i>Atalhos
                    </h6>
                </div>
                <div class="card-body d-grid gap-2">
                    <x-button type="link" :href="route('provider.products.create')" variant="success" size="sm" icon="plus-circle" label="Novo Produto" />
                    <x-button type="link" :href="route('provider.products.index')" variant="primary" outline size="sm" icon="box-seam" label="Listar Produtos" />
                    <x-button type="link" :href="route('provider.products.index', ['deleted' => 'only'])" variant="secondary" outline size="sm" icon="archive" label="Ver Deletados" />
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
@endpush
