@extends('layouts.app')

@section('title', 'Dashboard de Serviços')

@section('content')
    <x-layout.page-container>
        <x-layout.page-header
            title="Dashboard de Serviços"
            icon="tools"
            description="Visão geral dos serviços do seu negócio com métricas e acompanhamento de performance."
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Serviços' => '#'
            ]"
        />

        @php
            $total = $stats['total_services'] ?? 0;
            $completed = $stats['completed_services'] ?? 0;
            $inProgress = $stats['in_progress_services'] ?? 0;
            $pending = $stats['pending_services'] ?? 0;
            $cancelled = $stats['cancelled_services'] ?? 0;
            $totalValue = $stats['total_service_value'] ?? 0;
            $recent = $stats['recent_services'] ?? collect();

            $completionRate = $total > 0 ? \App\Helpers\CurrencyHelper::format(($completed / $total) * 100, 1, false) : 0;
        @endphp

        <!-- Cards de Métricas -->
        <x-layout.grid-row>
            <x-dashboard.stat-card
                title="Total de Serviços"
                :value="$total"
                description="Quantidade total de serviços cadastrados para o seu negócio."
                icon="tools"
                variant="primary"
            />

            <x-dashboard.stat-card
                title="Serviços Concluídos"
                :value="$completed"
                description="Serviços finalizados com sucesso e aprovados pelos clientes."
                icon="check-circle-fill"
                variant="success"
            />

            <x-dashboard.stat-card
                title="Em Andamento"
                :value="$inProgress"
                description="Serviços atualmente em execução pela equipe."
                icon="clock-fill"
                variant="warning"
            />

            <x-dashboard.stat-card
                title="Taxa de Conclusão"
                :value="$completionRate . '%'"
                description="Percentual de serviços concluídos em relação ao total."
                icon="graph-up-arrow"
                variant="info"
            />
        </x-layout.grid-row>

        <!-- Cards de Valores Financeiros -->
        <x-layout.grid-row>
            <x-dashboard.stat-card
                title="Valor Total em Serviços"
                :value="\App\Helpers\CurrencyHelper::format($totalValue)"
                description="Valor total de todos os serviços cadastrados no sistema."
                icon="currency-dollar"
                variant="success"
                col="col-md-6"
            />

            <x-resource.resource-list-card
                title="Distribuição por Status"
                icon="pie-chart"
                padding="p-3"
                col="col-md-6"
            >
                <x-dashboard.chart-doughnut
                    id="statusChart"
                    :data="$stats['status_breakdown'] ?? []"
                    empty-text="Nenhum serviço cadastrado"
                />
                <p class="text-muted small mb-0 mt-3 text-center">
                    Acompanhe o fluxo de trabalho por status atual.
                </p>
            </x-resource.resource-list-card>
        </x-layout.grid-row>

        <!-- Serviços Recentes e Atalhos -->
        <x-layout.grid-row>
            <!-- Serviços Recentes -->
            <x-layout.grid-col size="col-lg-8">
                <x-resource.resource-list-card
                    title="Serviços Recentes"
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
                                        <x-resource.table-cell header>Valor</x-resource.table-cell>
                                        <x-resource.table-cell header>Status</x-resource.table-cell>
                                        <x-resource.table-cell header>Data</x-resource.table-cell>
                                        <x-resource.table-cell header align="center">Ações</x-resource.table-cell>
                                    </x-resource.table-row>
                                </x-slot:thead>

                                @foreach ($recent as $service)
                                    @php
                                        $customerName = $service->budget->customer->commonData?->full_name ?? 'N/A';
                                    @endphp
                                    <x-resource.table-row>
                                        <x-resource.table-cell class="fw-bold text-dark">{{ $service->code }}</x-resource.table-cell>
                                        <x-resource.table-cell>
                                            <x-resource.table-cell-truncate :text="$customerName" />
                                        </x-resource.table-cell>
                                        <x-resource.table-cell class="fw-bold text-dark">{{ \App\Helpers\CurrencyHelper::format($service->total) }}</x-resource.table-cell>
                                        <x-resource.table-cell>
                                            <x-ui.status-badge :item="$service" />
                                        </x-resource.table-cell>
                                        <x-resource.table-cell class="text-muted small">{{ $service->created_at->format('d/m/Y') }}</x-resource.table-cell>
                                        <x-resource.table-cell align="center">
                                            <x-resource.action-buttons
                                                :item="$service"
                                                resource="services"
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
                            @foreach ($recent as $service)
                                @php
                                    $customerName = $service->budget->customer->commonData?->full_name ?? 'N/A';
                                @endphp
                                <x-resource.resource-mobile-item
                                    :href="route('provider.services.show', $service->code)"
                                >
                                    <x-resource.resource-mobile-header
                                        :title="$service->code"
                                        :subtitle="$service->created_at->format('d/m/Y')"
                                    />

                                    <x-resource.resource-mobile-field
                                        label="Cliente"
                                        :value="$customerName"
                                    />

                                    <x-layout.grid-row g="2">
                                        <x-resource.resource-mobile-field
                                            label="Valor"
                                            :value="\App\Helpers\CurrencyHelper::format($service->total)"
                                            col="col-6"
                                        />
                                        <x-resource.resource-mobile-field
                                            label="Status"
                                            col="col-6"
                                            align="end"
                                        >
                                            <x-ui.status-badge :item="$service" />
                                        </x-resource.resource-mobile-field>
                                    </x-layout.grid-row>
                                </x-resource.resource-mobile-item>
                            @endforeach
                        </x-slot:mobile>
                    @else
                        <x-resource.empty-state
                            title="Nenhum serviço recente encontrado"
                            description="Crie novos serviços para visualizar aqui."
                            icon="inbox"
                        />
                    @endif
                </x-resource.resource-list-card>
            </x-layout.grid-col>

            <!-- Insights e Atalhos -->
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
                            icon="check-circle-fill"
                            variant="success"
                            description="Serviços concluídos geram receita garantida para seu negócio."
                        />
                        <x-dashboard.insight-item
                            icon="clock-fill"
                            variant="warning"
                            description="Acompanhe serviços em andamento para manter prazos."
                        />
                        <x-dashboard.insight-item
                            icon="graph-up-arrow"
                            variant="primary"
                            description="Monitore a taxa de conclusão para otimizar processos."
                        />
                    </x-resource.resource-list-card>

                    <!-- Atalhos -->
                    <x-resource.quick-actions
                        title="Ações de Serviço"
                        icon="lightning-charge"
                    >
                        <x-ui.button type="link" :href="route('provider.services.create')" variant="success" icon="plus-lg" label="Novo Serviço" feature="services" />
                        <x-ui.button type="link" :href="route('provider.categories.index')" variant="primary" icon="tags" label="Categorias" feature="categories" />
                        <x-ui.button type="link" :href="route('provider.services.index')" variant="primary" icon="tools" label="Listar Serviços" feature="services" />
                        <x-ui.button type="link" :href="route('provider.services.index', ['deleted' => 'only'])" variant="secondary" icon="trash" label="Ver Deletados" feature="services" />
                    </x-resource.quick-actions>
                </x-layout.v-stack>
            </x-layout.grid-col>
        </x-layout.grid-row>
    </x-layout.page-container>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
@endpush
