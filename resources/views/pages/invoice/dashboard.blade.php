@extends('layouts.app')

@section('title', 'Dashboard de Faturas')

@section('content')
    <x-layout.page-container>
        <x-layout.page-header
            title="Dashboard de Faturas"
            icon="receipt"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Faturas' => '#'
            ]"
            description="Acompanhe suas faturas, recebimentos e pendências de faturamento."
        />

        @php
            $total = $stats['total_invoices'] ?? 0;
            $paid = $stats['paid_invoices'] ?? 0;
            $pending = $stats['pending_invoices'] ?? 0;
            $overdue = $stats['overdue_invoices'] ?? 0;
            $cancelled = $stats['cancelled_invoices'] ?? 0;
            $billed = $stats['total_billed'] ?? 0;
            $received = $stats['total_received'] ?? 0;
            $toReceive = $stats['total_pending'] ?? 0;
            $recent = $stats['recent_invoices'] ?? collect();
            $breakdown = $stats['status_breakdown'] ?? [];
            $paidRate = $total > 0 ? \App\Helpers\CurrencyHelper::format(($paid / $total) * 100) : 0;
        @endphp

        <!-- Cards de Métricas -->
        <x-layout.grid-row>
            <x-dashboard.stat-card
                title="Total de Faturas"
                :value="$total"
                description="Quantidade total emitida."
                icon="receipt"
                variant="primary"
            />

            <x-dashboard.stat-card
                title="Faturas Pagas"
                :value="$paid"
                description="Faturas liquidadas."
                icon="check-circle"
                variant="success"
            />

            <x-dashboard.stat-card
                title="Faturas Pendentes"
                :value="$pending"
                description="Aguardando pagamento."
                icon="hourglass-split"
                variant="warning"
            />

            <x-dashboard.stat-card
                title="Faturas Vencidas"
                :value="$overdue"
                description="Passaram do vencimento."
                icon="calendar-x"
                variant="danger"
            />
        </x-layout.grid-row>

        <!-- Totais Financeiros e Gráfico -->
        <x-layout.grid-row>
            <x-layout.grid-col size="col-lg-6">
                <x-resource.resource-list-card
                    title="Totais Financeiros"
                    icon="currency-dollar"
                    padding="p-3"
                >
                    <x-layout.grid-row class="text-center g-3 mb-0">
                        <x-dashboard.mini-stat-card
                            label="Faturado"
                            :value="\App\Helpers\CurrencyHelper::format($billed)"
                            variant="primary"
                            col="col-12 col-md-4"
                        />
                        <x-dashboard.mini-stat-card
                            label="Recebido"
                            :value="\App\Helpers\CurrencyHelper::format($received)"
                            variant="success"
                            col="col-12 col-md-4"
                        />
                        <x-dashboard.mini-stat-card
                            label="A Receber"
                            :value="\App\Helpers\CurrencyHelper::format($toReceive)"
                            variant="warning"
                            col="col-12 col-md-4"
                        />
                    </x-layout.grid-row>
                </x-resource.resource-list-card>
            </x-layout.grid-col>

            <x-layout.grid-col size="col-lg-6">
                <x-resource.resource-list-card
                    title="Distribuição por Status"
                    icon="pie-chart"
                    padding="p-4"
                >
                    <x-layout.grid-row class="align-items-center mb-0">
                        <x-layout.grid-col size="col-7">
                            <x-dashboard.chart-doughnut
                                id="statusChart"
                                :data="$breakdown"
                                height="150"
                            />
                        </x-layout.grid-col>
                        <x-layout.grid-col size="col-5" class="text-end">
                            <div class="display-6 fw-bold text-dark mb-0">{{ $paidRate }}%</div>
                            <div class="text-muted small text-uppercase fw-medium">Taxa de Liquidez</div>
                        </x-layout.grid-col>
                    </x-layout.grid-row>
                </x-resource.resource-list-card>
            </x-layout.grid-col>
        </x-layout.grid-row>

        <!-- Conteúdo Principal -->
        <x-layout.grid-row>
            <!-- Faturas Recentes (8 colunas) -->
            <x-layout.grid-col size="col-lg-8">
                <x-resource.resource-list-card
                    title="Faturas Recentes"
                    icon="clock-history"
                    :total="$recent->count()"
                >
                    @if ($recent->isNotEmpty())
                        <x-slot:desktop>
                            <x-resource.resource-table>
                                <x-slot:thead>
                                    <x-resource.table-row>
                                        <x-resource.table-cell header>Código</x-resource.table-cell>
                                        <x-resource.table-cell header>Cliente</x-resource.table-cell>
                                        <x-resource.table-cell header>Status</x-resource.table-cell>
                                        <x-resource.table-cell header>Valor</x-resource.table-cell>
                                        <x-resource.table-cell header>Vencimento</x-resource.table-cell>
                                        <x-resource.table-cell header align="center">Ações</x-resource.table-cell>
                                    </x-resource.table-row>
                                </x-slot:thead>

                                @foreach ($recent as $inv)
                                    <x-resource.table-row>
                                        <x-resource.table-cell class="fw-bold text-dark">
                                            {{ $inv->code }}
                                        </x-resource.table-cell>
                                        <x-resource.table-cell class="fw-medium text-dark">
                                            {{ $inv->customer?->commonData?->first_name ?? 'Cliente N/A' }}
                                        </x-resource.table-cell>
                                        <x-resource.table-cell>
                                            <x-ui.status-badge :item="$inv" />
                                        </x-resource.table-cell>
                                        <x-resource.table-cell class="fw-bold">
                                            {{ \App\Helpers\CurrencyHelper::format($inv->total) }}
                                        </x-resource.table-cell>
                                        <x-resource.table-cell class="text-muted small">
                                            {{ optional($inv->due_date)->format('d/m/Y') }}
                                        </x-resource.table-cell>
                                        <x-resource.table-cell align="center">
                                            <x-ui.button type="link" :href="route('provider.invoices.show', $inv->code)" variant="outline-primary" size="sm" icon="eye" title="Visualizar" />
                                        </x-resource.table-cell>
                                    </x-resource.table-row>
                                @endforeach
                            </x-resource.resource-table>
                        </x-slot:desktop>

                        <x-slot:mobile>
                            @foreach ($recent as $inv)
                                <x-resource.resource-mobile-item
                                    :href="route('provider.invoices.show', $inv->code)"
                                >
                                    <x-resource.resource-mobile-header
                                        :title="$inv->code"
                                        :subtitle="optional($inv->due_date)->format('d/m/Y')"
                                    />
                                    <x-resource.resource-mobile-field
                                        label="Cliente"
                                        :value="$inv->customer?->commonData?->first_name ?? 'N/A'"
                                    />
                                    <x-layout.grid-row g="2">
                                        <x-resource.resource-mobile-field
                                            label="Valor"
                                            :value="\App\Helpers\CurrencyHelper::format($inv->total)"
                                            col="col-6"
                                        />
                                        <x-resource.resource-mobile-field
                                            label="Status"
                                            col="col-6"
                                            align="end"
                                        >
                                            <x-ui.status-badge :item="$inv" />
                                        </x-resource.resource-mobile-field>
                                    </x-layout.grid-row>
                                </x-resource.resource-mobile-item>
                            @endforeach
                        </x-slot:mobile>
                    @else
                        <x-resource.empty-state
                            title="Nenhuma fatura recente"
                            description="Suas faturas aparecerão aqui conforme forem geradas."
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
                        title="Insights de Faturamento"
                        icon="lightbulb"
                        padding="p-3"
                        gap="3"
                    >
                        <x-dashboard.insight-item
                            icon="calendar-x"
                            variant="danger"
                            description="Priorize a cobrança de faturas vencidas para manter o fluxo de caixa."
                        />
                        <x-dashboard.insight-item
                            icon="hourglass-split"
                            variant="warning"
                            description="Monitore faturas próximas ao vencimento e envie lembretes."
                        />
                    </x-resource.resource-list-card>

                    <!-- Atalhos -->
                    <x-resource.quick-actions
                        title="Ações de Fatura"
                        icon="lightning-charge"
                    >
                        <x-ui.button type="link" :href="route('provider.invoices.create')" variant="success" icon="plus-lg" label="Nova Fatura" />
                        <x-ui.button type="link" :href="route('provider.invoices.index')" variant="primary" icon="receipt" label="Listar Faturas" />
                        <x-ui.button type="link" :href="route('provider.financial.dashboard')" variant="secondary" icon="currency-dollar" label="Dashboard Financeiro" />
                    </x-resource.quick-actions>
                </x-layout.v-stack>
            </x-layout.grid-col>
        </x-layout.grid-row>
    </x-layout.page-container>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
@endpush
