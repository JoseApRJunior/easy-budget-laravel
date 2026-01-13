@extends('layouts.app')

@section('title', 'Dashboard de Produtos')

@section('content')
<x-layout.page-container>
    <x-layout.page-header
        title="Dashboard de Produtos"
        icon="box-seam"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Produtos' => '#'
        ]">
        <p class="text-muted mb-0 small">Visão geral do seu catálogo de produtos com atalhos de gestão.</p>
    </x-layout.page-header>

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
    <x-layout.grid-row class="mb-4">
        <x-dashboard.stat-card
            title="VALOR EM ESTOQUE (VENDA)"
            :value="'R$ ' . number_format($inventorySale, 2, ',', '.')"
            description="Preço total de venda."
            icon="cash-stack"
            variant="primary"
            col="col-12 col-md-4"
        />

        <x-dashboard.stat-card
            title="LUCRO POTENCIAL"
            :value="'R$ ' . number_format($potentialProfit, 2, ',', '.')"
            description="Diferença venda vs custo."
            icon="graph-up-arrow"
            variant="success"
            col="col-12 col-md-4"
        />

        <x-dashboard.stat-card
            title="MARGEM MÉDIA"
            :value="number_format($avgMargin, 1, ',', '.') . '%'"
            description="Média de todos os produtos."
            icon="percent"
            variant="info"
            col="col-12 col-md-4"
        />
    </x-layout.grid-row>

    <!-- Cards de Métricas de Quantidade -->
    <x-layout.grid-row class="mb-4">
        <x-dashboard.stat-card
            title="Total"
            :value="$total"
            description="Ativos e inativas."
            icon="box-seam"
            variant="primary"
            isCustom
        />

        <x-dashboard.stat-card
            title="Ativas"
            :value="$active"
            description="Disponíveis para uso."
            icon="check-circle-fill"
            variant="success"
            isCustom
        />

        <x-dashboard.stat-card
            title="Inativas"
            :value="$inactive"
            description="Suspensas temporariamente."
            icon="pause-circle-fill"
            variant="secondary"
            isCustom
        />

        <x-dashboard.stat-card
            title="Deletadas"
            :value="$deleted"
            description="Na lixeira."
            icon="trash3-fill"
            variant="danger"
            isCustom
        />

        <x-dashboard.stat-card
            title="Taxa Uso"
            :value="$activityRate . '%'"
            description="Percentual de ativas."
            icon="percent"
            variant="info"
            isCustom
        />
    </x-layout.grid-row>

    <!-- Conteúdo Principal -->
    <x-layout.grid-row>
        <!-- Produtos Recentes -->
        <x-layout.grid-col lg="8">
            <x-resource.resource-list-card
                title="Produtos Recentes"
                mobileTitle="Recentes"
                icon="clock-history"
                class="h-100"
            >
                @if ($recent instanceof \Illuminate\Support\Collection && $recent->isNotEmpty())
                    <x-slot:desktop>
                        <x-resource.resource-table>
                            <x-slot:thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Categoria</th>
                                    <th class="text-nowrap">Venda</th>
                                    <th class="text-nowrap">Margem</th>
                                    <th>Status</th>
                                    <th>Criado em</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </x-slot:thead>
                            <x-slot:tbody>
                                @foreach ($recent as $product)
                                    <tr>
                                        <td>
                                            <x-resource.resource-info
                                                :title="$product->name"
                                                icon="box-seam"
                                            />
                                        </td>
                                        <td>{{ $product->category->name ?? '—' }}</td>
                                        <td class="text-nowrap">{{ $product->formatted_price }}</td>
                                        <td>
                                            @if($product->cost_price > 0)
                                                <span class="badge bg-{{ $product->profit_margin_percentage >= 30 ? 'success' : ($product->profit_margin_percentage >= 15 ? 'warning' : 'danger') }}-subtle text-{{ $product->profit_margin_percentage >= 30 ? 'success' : ($product->profit_margin_percentage >= 15 ? 'warning' : 'danger') }}">
                                                    {{ number_format($product->profit_margin_percentage, 1, ',', '.') }}%
                                                </span>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <x-ui.status-badge :item="$product" />
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ optional($product->created_at)->format('d/m/Y') }}</small>
                                        </td>
                                        <x-resource.table-actions>
                                            <x-ui.button type="link" :href="route('provider.products.show', $product->sku)" variant="info" icon="eye" size="sm" title="Visualizar" />
                                        </x-resource.table-actions>
                                    </tr>
                                @endforeach
                            </x-slot:tbody>
                        </x-resource.resource-table>
                    </x-slot:desktop>

                    <x-slot:mobile>
                        @foreach ($recent as $product)
                            <x-resource.resource-mobile-item>
                                <x-resource.resource-info
                                    :title="$product->name"
                                    icon="box-seam"
                                />
                                <x-slot:description>
                                    <div class="d-flex gap-2 flex-wrap mb-2">
                                        <x-ui.status-badge :item="$product" />
                                        @if($product->cost_price > 0)
                                            <span class="badge bg-{{ $product->profit_margin_percentage >= 30 ? 'success' : ($product->profit_margin_percentage >= 15 ? 'warning' : 'danger') }}-subtle text-{{ $product->profit_margin_percentage >= 30 ? 'success' : ($product->profit_margin_percentage >= 15 ? 'warning' : 'danger') }}">
                                                {{ number_format($product->profit_margin_percentage, 1, ',', '.') }}%
                                            </span>
                                        @endif
                                    </div>
                                    <div class="small text-muted">
                                        Venda: {{ $product->formatted_price }}
                                    </div>
                                </x-slot:description>
                                <x-slot:footer>
                                    <small class="text-muted">{{ optional($product->created_at)->format('d/m/Y') }}</small>
                                </x-slot:footer>
                                <x-slot:actions>
                                    <x-resource.table-actions mobile>
                                        <x-ui.button type="link" :href="route('provider.products.show', $product->sku)" variant="info" icon="eye" size="sm" />
                                    </x-resource.table-actions>
                                </x-slot:actions>
                            </x-resource.resource-mobile-item>
                        @endforeach
                    </x-slot:mobile>
                @else
                    <x-resource.empty-state resource="produtos recentes" />
                @endif
            </x-resource.resource-list-card>
        </x-layout.grid-col>

        <!-- Insights e Atalhos -->
        <x-layout.grid-col lg="4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-lightbulb me-2"></i>Insights Rápidos
                    </h6>
                </div>
                <div class="card-body">
                    <x-layout.v-stack gap="3">
                        <x-dashboard.insight-item
                            icon="graph-up-arrow"
                            variant="success"
                            description="Produtos com margem acima de 30% são seus maiores geradores de lucro."
                        />
                        <x-dashboard.insight-item
                            icon="exclamation-triangle"
                            variant="warning"
                            description="Revise produtos com margem abaixo de 15% para garantir a sustentabilidade."
                        />
                        <x-dashboard.insight-item
                            icon="box-seam"
                            variant="primary"
                            description="Mantenha seu catálogo atualizado para orçamentos mais precisos."
                        />
                    </x-layout.v-stack>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-link-45deg me-2"></i>Atalhos
                    </h6>
                </div>
                <div class="card-body">
                    <x-resource.quick-actions>
                        <x-ui.button type="link" :href="route('provider.products.create')" variant="success" size="sm" icon="plus-circle" label="Novo Produto" />
                        <x-ui.button type="link" :href="route('provider.products.index')" variant="primary" outline size="sm" icon="box-seam" label="Listar Produtos" />
                        <x-ui.button type="link" :href="route('provider.inventory.index')" variant="info" outline size="sm" icon="inventory" label="Gerir Estoque" />
                    </x-resource.quick-actions>
                </div>
            </div>
        </x-layout.grid-col>
    </x-layout.grid-row>
</x-layout.page-container>
@endsection

@push('styles')
@endpush
