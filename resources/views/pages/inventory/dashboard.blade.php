@extends('layouts.app')

@section('title', 'Dashboard de Inventário')

@section('content')
<x-layout.page-container>
    <x-layout.page-header
        title="Dashboard de Inventário"
        icon="archive"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Inventário' => '#'
        ]"
        description="Visão geral do seu estoque, movimentações e alertas de reposição."
    />

    <!-- Cards de Métricas -->
    <x-layout.grid-row>
        <x-dashboard.stat-card
            title="Valor Total em Estoque"
            :value="\App\Helpers\CurrencyHelper::format($totalInventoryValue)"
            description="Soma do valor de custo de todos os itens."
            icon="currency-dollar"
            variant="success"
        />

        <x-dashboard.stat-card
            title="Total de Produtos"
            :value="$totalProducts"
            description="Itens cadastrados no inventário."
            icon="box"
            variant="primary"
        />

        <x-dashboard.stat-card
            title="Itens com Estoque Baixo"
            :value="$lowStockProducts"
            description="Produtos abaixo da quantidade mínima."
            icon="exclamation-triangle"
            variant="warning"
        />

        <x-dashboard.stat-card
            title="Itens Sem Estoque"
            :value="$outOfStockProducts"
            description="Produtos com quantidade zerada."
            icon="x-circle"
            variant="danger"
        />
    </x-layout.grid-row>

    <!-- Conteúdo Principal -->
    <x-layout.grid-row>
        <!-- Tabelas de Alerta (8 colunas) -->
        <x-layout.grid-col size="col-lg-8">
            <x-layout.v-stack gap="4">
                <!-- Estoque Baixo -->
                <x-resource.resource-list-card
                    title="Alertas de Estoque Baixo"
                    icon="exclamation-triangle"
                    :total="count($lowStockItems)"
                >
                    @if(count($lowStockItems) > 0)
                        <x-slot:desktop>
                            <x-resource.resource-table>
                                <x-slot:thead>
                                    <x-resource.table-row>
                                        <x-resource.table-cell header>Produto</x-resource.table-cell>
                                        <x-resource.table-cell header align="center">Qtd Atual</x-resource.table-cell>
                                        <x-resource.table-cell header align="center">Qtd Mín</x-resource.table-cell>
                                        <x-resource.table-cell header align="center">Ações</x-resource.table-cell>
                                    </x-resource.table-row>
                                </x-slot:thead>

                                @foreach($lowStockItems as $item)
                                    <x-resource.table-row>
                                        <x-resource.table-cell>
                                            <div class="fw-bold text-dark">{{ $item->product->name }}</div>
                                            <div class="d-flex align-items-center gap-2">
                                                <small class="text-muted small">{{ $item->product->sku }}</small>
                                                <span class="text-muted small">• {{ $item->product->category->name ?? 'Geral' }}</span>
                                            </div>
                                        </x-resource.table-cell>
                                        <x-resource.table-cell align="center">
                                            <span class="fw-bold text-danger">{{ number_format($item->available_quantity, 0, ',', '.') }}</span>
                                        </x-resource.table-cell>
                                        <x-resource.table-cell align="center">
                                            <span class="text-muted small">{{ number_format($item->min_quantity, 0, ',', '.') }}</span>
                                        </x-resource.table-cell>
                                        <x-resource.table-cell align="center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <x-ui.button type="link" :href="route('provider.inventory.entry', $item->product->sku)" variant="outline-success" icon="plus" size="sm" title="Entrada" feature="inventory" />
                                                <x-ui.button type="link" :href="route('provider.inventory.adjust', $item->product->sku)" variant="outline-secondary" icon="sliders" size="sm" title="Ajustar" feature="inventory" />
                                            </div>
                                        </x-resource.table-cell>
                                    </x-resource.table-row>
                                @endforeach
                            </x-resource.resource-table>
                        </x-slot:desktop>

                        <x-slot:mobile>
                            @foreach($lowStockItems as $item)
                                <x-resource.resource-mobile-item
                                    :href="route('provider.inventory.index', ['search' => $item->product->sku])"
                                >
                                    <x-resource.resource-mobile-header
                                        :title="$item->product->name"
                                        :subtitle="$item->product->sku"
                                    />
                                    <x-resource.resource-mobile-field
                                        label="Qtd Atual"
                                        :value="number_format($item->available_quantity, 0, ',', '.') . ' un'"
                                        class="text-danger fw-bold"
                                    />
                                    <x-resource.resource-mobile-field
                                        label="Qtd Mínima"
                                        :value="number_format($item->min_quantity, 0, ',', '.') . ' un'"
                                    />
                                </x-resource.resource-mobile-item>
                            @endforeach
                        </x-slot:mobile>
                    @else
                        <x-resource.empty-state
                            title="Tudo em dia!"
                            description="Nenhum item com estoque abaixo do mínimo."
                            icon="check-circle"
                        />
                    @endif
                </x-resource.resource-list-card>

                <!-- Últimas Movimentações -->
                <x-resource.resource-list-card
                    title="Últimas Movimentações"
                    icon="clock-history"
                    :total="count($recentMovements)"
                >
                    @if(count($recentMovements) > 0)
                        <x-slot:desktop>
                            <x-resource.resource-table>
                                <x-slot:thead>
                                    <x-resource.table-row>
                                        <x-resource.table-cell header>Data</x-resource.table-cell>
                                        <x-resource.table-cell header>Produto</x-resource.table-cell>
                                        <x-resource.table-cell header align="center">Tipo</x-resource.table-cell>
                                        <x-resource.table-cell header align="center">Qtd</x-resource.table-cell>
                                        <x-resource.table-cell header>Usuário</x-resource.table-cell>
                                    </x-resource.table-row>
                                </x-slot:thead>

                                @foreach($recentMovements as $movement)
                                    <x-resource.table-row>
                                        <x-resource.table-cell class="text-muted small">
                                            {{ $movement->created_at->format('d/m/Y H:i') }}
                                        </x-resource.table-cell>
                                        <x-resource.table-cell>
                                            <div class="fw-bold text-dark">{{ $movement->product->name }}</div>
                                            <small class="text-muted small">{{ $movement->product->sku }}</small>
                                        </x-resource.table-cell>
                                        <x-resource.table-cell align="center">
                                            @php
                                                $typeLabel = match($movement->type) {
                                                    'entry' => 'Entrada',
                                                    'exit' => 'Saída',
                                                    'adjustment' => 'Ajuste',
                                                    'reservation' => 'Reserva',
                                                    'cancellation' => 'Cancelamento',
                                                    default => $movement->type
                                                };
                                                $typeVariant = match($movement->type) {
                                                    'entry' => 'success',
                                                    'exit' => 'danger',
                                                    'adjustment' => 'primary',
                                                    'reservation' => 'info',
                                                    'cancellation' => 'secondary',
                                                    default => 'secondary'
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $typeVariant }}-subtle text-{{ $typeVariant }} border-0 px-3">
                                                {{ $typeLabel }}
                                            </span>
                                        </x-resource.table-cell>
                                        <x-resource.table-cell align="center" class="fw-bold">
                                            {{ number_format($movement->quantity, 0, ',', '.') }}
                                        </x-resource.table-cell>
                                        <x-resource.table-cell class="text-muted small">
                                            {{ explode(' ', $movement->user->name ?? 'Sistema')[0] }}
                                        </x-resource.table-cell>
                                    </x-resource.table-row>
                                @endforeach
                            </x-resource.resource-table>
                        </x-slot:desktop>

                        <x-slot:mobile>
                            @foreach($recentMovements as $movement)
                                <x-resource.resource-mobile-item
                                >
                                    <x-resource.resource-mobile-header
                                        :title="$movement->product->name"
                                        :subtitle="$movement->created_at->format('d/m/Y H:i')"
                                    />
                                    <x-resource.resource-mobile-field
                                        label="Quantidade"
                                        :value="number_format($movement->quantity, 0, ',', '.') . ' un'"
                                        class="fw-bold"
                                    />
                                    <x-resource.resource-mobile-field
                                        label="Tipo"
                                    >
                                        <span class="badge bg-{{ $typeVariant ?? 'secondary' }}-subtle text-{{ $typeVariant ?? 'secondary' }} border-0">
                                            {{ $typeLabel ?? $movement->type }}
                                        </span>
                                    </x-resource.resource-mobile-field>
                                </x-resource.resource-mobile-item>
                            @endforeach
                        </x-slot:mobile>
                    @else
                        <x-resource.empty-state
                            title="Nenhuma movimentação"
                            description="As movimentações de estoque aparecerão aqui conforme forem realizadas."
                            icon="clock-history"
                        />
                    @endif
                </x-resource.resource-list-card>
            </x-layout.v-stack>
        </x-layout.grid-col>

        <!-- Sidebar (4 colunas) -->
        <x-layout.grid-col size="col-lg-4">
            <x-layout.v-stack gap="4">
                <!-- Insights -->
                <x-resource.resource-list-card
                    title="Insights de Estoque"
                    icon="lightbulb"
                    padding="p-3"
                    gap="3"
                >
                    <x-dashboard.insight-item
                        icon="graph-up"
                        variant="success"
                        description="Produtos com alto giro de estoque devem ter prioridade na reposição."
                    />
                    <x-dashboard.insight-item
                        icon="shield-check"
                        variant="primary"
                        description="Mantenha o estoque mínimo atualizado para evitar rupturas de venda."
                    />
                    <x-dashboard.insight-item
                        icon="calendar-event"
                        variant="info"
                        description="Realize inventários periódicos para garantir a acuracidade dos dados."
                    />
                </x-resource.resource-list-card>

                <!-- Atalhos -->
                <x-resource.quick-actions
                    title="Ações de Estoque"
                    icon="lightning-charge"
                >
                    <x-ui.button type="link" :href="route('provider.inventory.index')" variant="primary" icon="list" label="Ver Inventário" />
                    <x-ui.button type="link" :href="route('provider.inventory.movements')" variant="primary" icon="arrow-left-right" label="Movimentações" />
                    <x-ui.button type="link" :href="route('provider.inventory.stock-turnover')" variant="primary" icon="graph-up" label="Giro de Estoque" />
                    <x-ui.button type="link" :href="route('provider.inventory.most-used')" variant="primary" icon="star" label="Mais Usados" />
                    <x-ui.button type="link" :href="route('provider.inventory.report')" variant="secondary" icon="file-earmark-text" label="Relatórios" />
                </x-resource.quick-actions>

                <!-- Reservados -->
                <x-resource.resource-list-card
                    title="Itens Reservados"
                    icon="bookmark-check"
                    padding="p-3"
                >
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Produtos com reserva:</span>
                        <span class="fw-bold">{{ $reservedItemsCount }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Quantidade total:</span>
                        <span class="fw-bold">{{ number_format($totalReservedQuantity, 0, ',', '.') }} un.</span>
                    </div>
                </x-resource.resource-list-card>
            </x-layout.v-stack>
        </x-layout.grid-col>
    </x-layout.grid-row>
</x-layout.page-container>
@endsection
