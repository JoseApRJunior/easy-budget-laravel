@extends('layouts.app')

@section('content')
    <x-layout.page-container>
        <x-layout.page-header
            title="Dashboard Financeiro"
            icon="currency-dollar"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Financeiro' => '#'
            ]"
            description="Visão geral das finanças do seu negócio com métricas de receita e faturamento."
        >
        </x-layout.page-header>

        <x-layout.actions-bar>
            <x-ui.period-selector
                :periods="$periods"
                :current-period="$period"
            />
        </x-layout.actions-bar>

        <!-- KPI Cards -->
        <x-layout.grid-row>
            <!-- Receita -->
            <x-dashboard.stat-card
                title="Receita"
                :value="'R$ ' . number_format($revenue['current'], 2, ',', '.')"
                icon="currency-dollar"
                variant="primary"
            >
                <x-slot:description>
                    <small class="text-{{ $revenue['growth_positive'] ? 'success' : 'danger' }}">
                        <i class="bi bi-arrow-{{ $revenue['growth_positive'] ? 'up' : 'down' }}"></i>
                        {{ abs($revenue['growth']) }}% vs anterior
                    </small>
                </x-slot:description>
            </x-dashboard.stat-card>

            <!-- Faturas Pagas -->
            <x-dashboard.stat-card
                title="Faturas Pagas"
                :value="$invoices['paid']"
                icon="check-circle"
                variant="success"
                :description="'de ' . $invoices['total'] . ' faturas (' . $invoices['conversion_rate'] . '%)'"
            />

            <!-- Faturas Pendentes -->
            <x-dashboard.stat-card
                title="Faturas Pendentes"
                :value="$invoices['pending'] + $invoices['overdue']"
                icon="clock"
                variant="warning"
                :description="'R$ ' . number_format($invoices['pending_amount'], 2, ',', '.') . ' em aberto'"
            />

            <!-- Ticket Médio -->
            <x-dashboard.stat-card
                title="Ticket Médio"
                :value="'R$ ' . number_format($payments['average_ticket'], 2, ',', '.')"
                icon="graph-up"
                variant="info"
                description="Média por pagamento recebido"
            />
        </x-layout.grid-row>

            <!-- Gráficos -->
            <x-layout.grid-row>
                <!-- Receita Timeline -->
                <x-layout.grid-col size="col-lg-8">
                    <x-resource.resource-list-card
                        title="Receita dos Últimos 30 Dias"
                        icon="graph-up"
                        padding="p-4"
                    >
                        <x-dashboard.chart-line
                            id="revenueChart"
                            :data="$charts['revenue_timeline']"
                            label="Receita (R$)"
                             height="150"
                        />
                    </x-resource.resource-list-card>
                </x-layout.grid-col>

                <!-- Status das Faturas -->
                <x-layout.grid-col size="col-lg-4">
                    <x-resource.resource-list-card
                        title="Status das Faturas"
                        icon="pie-chart"
                        padding="p-4"
                    >
                        <x-dashboard.chart-doughnut
                            id="invoiceStatusChart"
                            :data="$charts['invoice_status']"
                            height="150"
                        />
                    </x-resource.resource-list-card>
                </x-layout.grid-col>
            </x-layout.grid-row>

        <!-- Métodos de Pagamento e Orçamentos -->
        <x-layout.grid-row>
            <!-- Métodos de Pagamento (8 colunas) -->
            <x-layout.grid-col size="col-lg-8">
                <x-resource.resource-list-card
                    title="Métodos de Pagamento"
                    icon="credit-card"
                    padding="p-4"
                >
                    @if (!empty($payments['by_method']))
                        <x-layout.grid-row class="mb-0">
                            @foreach ($payments['by_method'] as $method => $data)
                                <x-dashboard.mini-stat-card
                                    :label="\App\Models\Payment::getPaymentMethods()[$method] ?? $method"
                                    :value="'R$ ' . number_format($data['total'], 2, ',', '.')"
                                    variant="primary"
                                    col="col-md-6"
                                />
                            @endforeach
                        </x-layout.grid-row>
                    @else
                        <x-resource.empty-state
                            title="Sem pagamentos registrados"
                            description="Nenhum pagamento encontrado no período selecionado."
                            :icon="null"
                        />
                    @endif
                </x-resource.resource-list-card>
            </x-layout.grid-col>

            <!-- Sidebar (4 colunas) -->
            <x-layout.grid-col size="col-lg-4">
                <x-layout.v-stack gap="4">
                    <!-- Performance de Orçamentos -->
                    <x-resource.resource-list-card
                        title="Performance"
                        icon="file-earmark-text"
                        padding="p-3"
                    >
                        <x-layout.grid-row class="text-center mb-3 g-2">
                            <x-dashboard.mini-stat-card
                                label="Total"
                                :value="$budgets['total']"
                                variant="primary"
                            />
                            <x-dashboard.mini-stat-card
                                label="Aprovados"
                                :value="$budgets['approved']"
                                variant="success"
                            />
                        </x-layout.grid-row>

                        <x-dashboard.mini-stat-card
                            label="Taxa de Aprovação"
                            :value="$budgets['approval_rate'] . '%'"
                            variant="primary"
                            col="col-12"
                            class="text-center"
                        />
                    </x-resource.resource-list-card>

                    <!-- Insights -->
                    <x-resource.resource-list-card
                        title="Insights Financeiros"
                        icon="lightbulb"
                        padding="p-3"
                        gap="3"
                    >
                        <x-dashboard.insight-item
                            icon="graph-up-arrow"
                            variant="success"
                            description="Sua receita teve uma variação de {{ $revenue['growth'] }}% em relação ao período anterior."
                        />
                        <x-dashboard.insight-item
                            icon="check-circle"
                            variant="primary"
                            description="A taxa de liquidez das suas faturas está em {{ $invoices['conversion_rate'] }}%."
                        />
                    </x-resource.resource-list-card>

                    <!-- Atalhos -->
                    <x-resource.quick-actions
                        title="Ações Financeiras"
                        icon="lightning-charge"
                    >
                        <x-ui.button type="link" :href="route('provider.invoices.index')" variant="primary" icon="receipt" label="Gerir Faturas" feature="invoices" />
                        <x-ui.button type="link" :href="route('provider.budgets.index')" variant="primary" icon="file-earmark-text" label="Ver Orçamentos" feature="budgets" />
                        <x-ui.button type="link" :href="route('provider.reports.financial')" variant="secondary" icon="file-earmark-bar-graph" label="Relatórios" feature="reports" />
                    </x-resource.quick-actions>
                </x-layout.v-stack>
            </x-layout.grid-col>
        </x-layout.grid-row>
    </x-layout.page-container>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function changePeriod() {
            const period = document.getElementById('periodSelect').value;
            window.location.href = `?period=${period}`;
        }
    </script>
@endpush
