@extends('layouts.app')

@section('title', 'Dashboard de Orçamentos')

@section('content')
    <x-layout.page-container>
        <x-layout.page-header
            title="Dashboard de Orçamentos"
            icon="file-earmark-text"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Orçamentos' => '#'
            ]"
            description="Visão geral dos orçamentos do seu negócio com métricas e acompanhamento de performance."
        />

        @php
            $total = $stats['total_budgets'] ?? 0;
            $approved = $stats['approved_budgets'] ?? 0;
            $pending = $stats['pending_budgets'] ?? 0;
            $rejected = $stats['rejected_budgets'] ?? 0;
            $totalValue = $stats['total_budget_value'] ?? 0;
            $recent = $stats['recent_budgets'] ?? collect();

            $approvedRate = $total > 0 ? \App\Helpers\CurrencyHelper::format(($approved / $total) * 100, 1, false) : 0;
        @endphp

        <!-- Cards de Métricas -->
        <x-layout.grid-row>
            <x-dashboard.stat-card
                title="Total de Orçamentos"
                :value="$total"
                description="Quantidade total de orçamentos cadastrados."
                icon="file-earmark-text"
                variant="primary"
            />

            <x-dashboard.stat-card
                title="Orçamentos Aprovados"
                :value="$approved"
                description="Orçamentos que foram aceitos pelos clientes."
                icon="check-circle"
                variant="success"
            />

            <x-dashboard.stat-card
                title="Orçamentos Pendentes"
                :value="$pending"
                description="Aguardando resposta ou revisão do cliente."
                icon="clock"
                variant="warning"
            />

            <x-dashboard.stat-card
                title="Taxa de Aprovação"
                :value="$approvedRate . '%'"
                description="Percentual de orçamentos aprovados."
                icon="graph-up-arrow"
                variant="info"
            />
        </x-layout.grid-row>

        <!-- Cards de Valores Financeiros e Gráfico -->
        <x-layout.grid-row>
            <x-dashboard.stat-card
                title="Valor Total em Orçamentos"
                :value="\App\Helpers\CurrencyHelper::format($totalValue)"
                description="Soma total de todos os orçamentos gerados."
                icon="cash-stack"
                variant="success"
                col="col-md-4"
            />

            <x-dashboard.stat-card
                title="Ticket Médio"
                :value="\App\Helpers\CurrencyHelper::format($total > 0 ? $totalValue / $total : 0)"
                description="Valor médio por orçamento gerado no sistema."
                icon="calculator"
                variant="primary"
                col="col-md-4"
            />

            <x-resource.resource-list-card
                title="Status dos Orçamentos"
                mobileTitle="Status"
                icon="pie-chart"
                padding="p-4"
                col="col-md-4"
            >
                <x-dashboard.chart-doughnut
                    id="statusChart"
                    :data="$stats['status_breakdown_detailed'] ?? []"
                    empty-text="Nenhum orçamento cadastrado"
                />
                 <p class="text-muted small mb-0 mt-3 text-center">
                    Acompanhe o fluxo de trabalho por status atual.
                </p>
            </x-resource.resource-list-card>
        </x-layout.grid-row>

        <!-- Conteúdo Principal -->
        <x-layout.grid-row>
            <!-- Orçamentos Recentes (8 colunas) -->
            <x-layout.grid-col size="col-lg-8">
                <x-resource.resource-list-card
                    title="Orçamentos Recentes"
                    icon="clock-history"
                    :total="$recent->count()"
                >
                    @if ($recent instanceof \Illuminate\Support\Collection && $recent->isNotEmpty())
                        <x-slot:desktop>
                            <x-resource.resource-table>
                                <x-slot:thead>
                                    <x-resource.table-row>
                                        <x-resource.table-cell header>Código</x-resource.table-cell>
                                        <x-resource.table-cell header>Cliente</x-resource.table-cell>
                                        <x-resource.table-cell header>Valor Total</x-resource.table-cell>
                                        <x-resource.table-cell header>Status</x-resource.table-cell>
                                        <x-resource.table-cell header>Data</x-resource.table-cell>
                                        <x-resource.table-cell header align="center">Ações</x-resource.table-cell>
                                    </x-resource.table-row>
                                </x-slot:thead>

                                @foreach ($recent as $budget)
                                    @php
                                        $customer = $budget->customer ?? null;
                                        $commonData = $customer?->commonData ?? null;

                                        $customerName = $commonData?->company_name ??
                                            trim(($commonData->first_name ?? '') . ' ' . ($commonData->last_name ?? '')) ?:
                                            'Cliente não informado';
                                    @endphp
                                    <x-resource.table-row>
                                        <x-resource.table-cell class="fw-bold text-dark">{{ $budget->code }}</x-resource.table-cell>
                                        <x-resource.table-cell>
                                            <x-resource.table-cell-truncate :text="$customerName" />
                                        </x-resource.table-cell>
                                        <x-resource.table-cell class="fw-bold text-dark">{{ \App\Helpers\CurrencyHelper::format($budget->total ?? 0) }}</x-resource.table-cell>
                                        <x-resource.table-cell>
                                            <x-ui.status-badge :item="$budget" />
                                        </x-resource.table-cell>
                                        <x-resource.table-cell class="text-muted small">{{ optional($budget->created_at)->format('d/m/Y') }}</x-resource.table-cell>
                                        <x-resource.table-cell align="center">
                                            <x-resource.action-buttons
                                                :item="$budget"
                                                resource="budgets"
                                                identifier="code"
                                                :can-delete="false"
                                                size="sm"
                                            />
                                        </x-resource.table-cell>
                                    </x-resource.table-row>
                                @endforeach
                            </x-resource.resource-table>
                        </x-slot:desktop>

                        <x-slot:mobile>
                            @foreach ($recent as $budget)
                                @php
                                    $customer = $budget->customer ?? null;
                                    $commonData = $customer?->commonData ?? null;
                                    $customerName = $commonData?->company_name ?? trim(($commonData->first_name ?? '') . ' ' . ($commonData->last_name ?? '')) ?: 'Cliente não informado';
                                @endphp
                                <x-resource.resource-mobile-item
                                    :href="route('provider.budgets.show', $budget->code)"
                                >
                                    <x-resource.resource-mobile-header
                                        :title="$budget->code"
                                        :subtitle="optional($budget->created_at)->format('d/m/Y')"
                                    />

                                    <x-resource.resource-mobile-field
                                        label="Cliente"
                                        :value="$customerName"
                                    />

                                    <x-layout.grid-row g="2">
                                        <x-resource.resource-mobile-field
                                            label="Valor"
                                            :value="\App\Helpers\CurrencyHelper::format($budget->total ?? 0)"
                                            col="col-6"
                                        />
                                        <x-resource.resource-mobile-field
                                            label="Status"
                                            col="col-6"
                                            align="end"
                                        >
                                            <x-ui.status-badge :item="$budget" />
                                        </x-resource.resource-mobile-field>
                                    </x-layout.grid-row>
                                </x-resource.resource-mobile-item>
                            @endforeach
                        </x-slot:mobile>
                    @else
                        <x-resource.empty-state
                            title="Nenhum orçamento recente"
                            description="Crie novos orçamentos para visualizar aqui."
                            icon="inbox"
                        />
                    @endif
                </x-resource.resource-list-card>
            </x-layout.grid-col>

            <!-- Sidebar (4 colunas) -->
            <x-layout.grid-col size="col-lg-4">
                <x-layout.v-stack gap="4">
                    <!-- Insights -->
                    <x-resource.resource-list-card
                        title="Insights Rápidos"
                        icon="lightbulb"
                        padding="p-3"
                        gap="3"
                    >
                        <x-dashboard.insight-item
                            icon="clock-fill"
                            variant="warning"
                            description="Acompanhe orçamentos pendentes para aumentar sua taxa de conversão."
                        />
                        <x-dashboard.insight-item
                            icon="graph-up-arrow"
                            variant="success"
                            description="Orçamentos aprovados geram receita garantida para seu negócio."
                        />
                        <x-dashboard.insight-item
                            icon="envelope-check"
                            variant="primary"
                            description="Envie lembretes para clientes com orçamentos pendentes."
                        />
                    </x-resource.resource-list-card>

                    <!-- Atalhos -->
                    <x-resource.quick-actions
                        title="Ações de Orçamento"
                        icon="lightning-charge"
                    >
                        <x-ui.button type="link" :href="route('provider.budgets.create')" variant="success" icon="plus-lg" label="Novo Orçamento" />
                        <x-ui.button type="link" :href="route('provider.customers.create')" variant="success" icon="person-plus" label="Novo Cliente" />
                        <x-ui.button type="link" :href="route('provider.budgets.index')" variant="primary" icon="file-earmark-text" label="Listar Orçamentos" />
                        <x-ui.button type="link" :href="route('provider.customers.index')" variant="primary" icon="people" label="Listar Clientes" />
                        <x-ui.button type="link" :href="route('provider.budgets.index', ['deleted' => 'only'])" variant="secondary" icon="trash" label="Ver Deletados" />
                    </x-resource.quick-actions>
                </x-layout.v-stack>
            </x-layout.grid-col>
        </x-layout.grid-row>
    </x-layout.page-container>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        function refreshData() {
            window.location.reload();
        }
    </script>
@endpush
