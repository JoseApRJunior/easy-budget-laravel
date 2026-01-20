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
        ]"
        description="Visão geral do seu catálogo de produtos com métricas de rentabilidade e gestão."
    />

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

    <!-- Cards de Métricas Financeiras -->
    <x-layout.grid-row>
        <x-dashboard.stat-card
            title="Valor em Estoque (Venda)"
            :value="'R$ ' . number_format($inventorySale, 2, ',', '.')"
            description="Preço total de venda do inventário."
            icon="cash-stack"
            variant="primary"
        />

        <x-dashboard.stat-card
            title="Lucro Potencial"
            :value="'R$ ' . number_format($potentialProfit, 2, ',', '.')"
            description="Diferença estimada entre venda e custo."
            icon="graph-up-arrow"
            variant="success"
        />

        <x-dashboard.stat-card
            title="Margem Média"
            :value="number_format($avgMargin, 1, ',', '.') . '%'"
            description="Média de rentabilidade do catálogo."
            icon="percent"
            variant="info"
        />

        <x-dashboard.stat-card
            title="Taxa de Atividade"
            :value="$activityRate . '%'"
            description="Percentual de produtos ativos."
            icon="check-circle"
            variant="primary"
        />
    </x-layout.grid-row>

    <!-- Conteúdo Principal -->
    <x-layout.grid-row>
        <!-- Produtos Recentes (8 colunas) -->
        <x-layout.grid-col size="col-lg-8">
            <x-resource.resource-list-card
                title="Produtos Recentes"
                icon="clock-history"
                :total="$recent->count()"
            >
                @if ($recent instanceof \Illuminate\Support\Collection && $recent->isNotEmpty())
                    <x-slot:desktop>
                        <x-resource.resource-table>
                            <x-slot:thead>
                                <x-resource.table-row>
                                    <x-resource.table-cell header>Produto</x-resource.table-cell>
                                    <x-resource.table-cell header>Categoria</x-resource.table-cell>
                                    <x-resource.table-cell header>Preço Venda</x-resource.table-cell>
                                    <x-resource.table-cell header>Margem</x-resource.table-cell>
                                    <x-resource.table-cell header>Status</x-resource.table-cell>
                                    <x-resource.table-cell header align="center">Ações</x-resource.table-cell>
                                </x-resource.table-row>
                            </x-slot:thead>

                            @foreach ($recent as $product)
                                <x-resource.table-row>
                                    <x-resource.table-cell>
                                        <x-resource.resource-info
                                            :title="$product->name"
                                            :subtitle="'SKU: ' . $product->sku"
                                            titleClass="fw-bold text-dark"
                                        />
                                    </x-resource.table-cell>
                                    <x-resource.table-cell class="text-muted small">
                                        {{ $product->category->name ?? '—' }}
                                    </x-resource.table-cell>
                                    <x-resource.table-cell class="fw-bold text-dark">
                                        {{ $product->formatted_price }}
                                    </x-resource.table-cell>
                                    <x-resource.table-cell>
                                        @if($product->cost_price > 0)
                                            <x-ui.badge
                                                :variant="$product->profit_margin_percentage >= 30 ? 'success' : ($product->profit_margin_percentage >= 15 ? 'warning' : 'danger')"
                                                :label="number_format($product->profit_margin_percentage, 1, ',', '.') . '%'"
                                            />
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </x-resource.table-cell>
                                    <x-resource.table-cell>
                                        <x-ui.status-badge :item="$product" />
                                    </x-resource.table-cell>
                                    <x-resource.table-cell align="center">
                                        <x-resource.action-buttons
                                            :item="$product"
                                            resource="products"
                                            identifier="sku"
                                            :can-delete="false"
                                            size="sm"
                                        />
                                    </x-resource.table-cell>
                                </x-resource.table-row>
                            @endforeach
                        </x-resource.resource-table>
                    </x-slot:desktop>

                    <x-slot:mobile>
                        @foreach ($recent as $product)
                            <x-resource.resource-mobile-item
                                :href="route('provider.products.show', $product->sku)"
                            >
                                <x-resource.resource-mobile-header
                                    :title="$product->name"
                                    :subtitle="$product->category->name ?? 'Sem categoria'"
                                />

                                <x-resource.resource-mobile-field
                                    label="Preço Venda"
                                    :value="$product->formatted_price"
                                />

                                <x-layout.grid-row g="2">
                                    <x-resource.resource-mobile-field
                                        label="Margem"
                                        col="col-6"
                                    >
                                        @if($product->cost_price > 0)
                                            <x-ui.badge
                                                :variant="$product->profit_margin_percentage >= 30 ? 'success' : ($product->profit_margin_percentage >= 15 ? 'warning' : 'danger')"
                                                :label="number_format($product->profit_margin_percentage, 1, ',', '.') . '%'"
                                            />
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </x-resource.resource-mobile-field>
                                    <x-resource.resource-mobile-field
                                        label="Status"
                                        col="col-6"
                                        align="end"
                                    >
                                        <x-ui.status-badge :item="$product" />
                                    </x-resource.resource-mobile-field>
                                </x-layout.grid-row>
                            </x-resource.resource-mobile-item>
                        @endforeach
                    </x-slot:mobile>
                @else
                    <x-resource.empty-state
                        title="Nenhum produto recente"
                        description="Comece cadastrando seus produtos para visualizá-los aqui."
                        :icon="null"
                    />
                @endif
            </x-resource.resource-list-card>
        </x-layout.grid-col>

        <!-- Sidebar (4 colunas) -->
        <x-layout.grid-col size="col-lg-4">
            <x-layout.v-stack gap="4">
                <!-- Insights -->
                <x-resource.resource-list-card
                    title="Insights de Produtos"
                    icon="lightbulb"
                    padding="p-3"
                    gap="3"
                >
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
                </x-resource.resource-list-card>

                <!-- Atalhos -->
                <x-resource.quick-actions
                    title="Ações de Produto"
                    icon="lightning-charge"
                >
                    <x-ui.button type="link" :href="route('provider.products.create')" variant="outline-success" icon="plus-lg" label="Novo Produto" />
                    <x-ui.button type="link" :href="route('provider.products.index')" variant="outline-primary" icon="box-seam" label="Listar Produtos" />
                    <x-ui.button type="link" :href="route('provider.inventory.index')" variant="outline-primary" icon="box" label="Gerir Estoque" />
                    <x-ui.button type="link" :href="route('provider.categories.index')" variant="outline-secondary" icon="tags" label="Categorias" />
                </x-resource.quick-actions>
            </x-layout.v-stack>
        </x-layout.grid-col>
    </x-layout.grid-row>
</x-layout.page-container>
@endsection
