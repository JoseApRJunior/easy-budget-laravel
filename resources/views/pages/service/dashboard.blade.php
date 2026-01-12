@extends('layouts.app')

@section('title', 'Dashboard de Serviços')

@section('content')
    <x-page-container>
        <x-page-header
            title="Dashboard de Serviços"
            icon="tools"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Serviços' => '#'
            ]"
        >
            <p class="text-muted mb-0">Visão geral dos serviços do seu negócio com métricas e acompanhamento de performance.</p>
        </x-page-header>

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
        <x-grid-row>
            <x-stat-card
                title="Total de Serviços"
                :value="$total"
                description="Quantidade total de serviços cadastrados para o seu negócio."
                icon="tools"
                variant="primary"
            />

            <x-stat-card
                title="Serviços Concluídos"
                :value="$completed"
                description="Serviços finalizados com sucesso e aprovados pelos clientes."
                icon="check-circle-fill"
                variant="success"
            />

            <x-stat-card
                title="Em Andamento"
                :value="$inProgress"
                description="Serviços atualmente em execução pela equipe."
                icon="clock-fill"
                variant="warning"
            />

            <x-stat-card
                title="Taxa de Conclusão"
                :value="$completionRate . '%'"
                description="Percentual de serviços concluídos em relação ao total."
                icon="graph-up-arrow"
                variant="info"
            />
        </x-grid-row>

        <!-- Cards de Valores Financeiros -->
        <x-grid-row>
            <x-stat-card
                title="Valor Total em Serviços"
                :value="\App\Helpers\CurrencyHelper::format($totalValue)"
                description="Valor total de todos os serviços cadastrados no sistema."
                icon="currency-dollar"
                variant="success"
                col="col-md-6"
            />

            <x-resource-list-card
                title="Distribuição por Status"
                icon="pie-chart"
                padding="p-3"
                col="col-md-6"
            >
                <x-chart-doughnut
                    id="statusChart"
                    :data="$stats['status_breakdown'] ?? []"
                    empty-text="Nenhum serviço cadastrado"
                />
                <p class="text-muted small mb-0 mt-3 text-center">
                    Acompanhe o fluxo de trabalho por status atual.
                </p>
            </x-resource-list-card>
        </x-grid-row>

        <!-- Serviços Recentes e Atalhos -->
        <x-grid-row class="mb-0">
            <!-- Serviços Recentes -->
            <x-resource-list-card
                title="Serviços Recentes"
                icon="clock-history"
                :total="$recent->count()"
                col="col-lg-8"
            >
                    @if ($recent->isNotEmpty())
                        <x-slot name="desktop">
                            <x-resource-table>
                                <x-slot name="thead">
                                    <x-table-row>
                                        <x-table-cell header>Código</x-table-cell>
                                        <x-table-cell header>Cliente</x-table-cell>
                                        <x-table-cell header>Valor</x-table-cell>
                                        <x-table-cell header>Status</x-table-cell>
                                        <x-table-cell header>Data</x-table-cell>
                                        <x-table-cell header align="center">Ações</x-table-cell>
                                    </x-table-row>
                                </x-slot>

                                @foreach ($recent as $service)
                                    @php
                                        $customerName = $service->budget->customer->commonData?->full_name ?? 'N/A';
                                    @endphp
                                    <x-table-row>
                                        <x-table-cell class="fw-bold text-dark">{{ $service->code }}</x-table-cell>
                                        <x-table-cell>
                                            <x-table-cell-truncate :text="$customerName" />
                                        </x-table-cell>
                                        <x-table-cell class="fw-bold text-dark">{{ \App\Helpers\CurrencyHelper::format($service->total) }}</x-table-cell>
                                        <x-table-cell>
                                            <x-status-badge :item="$service" />
                                        </x-table-cell>
                                        <x-table-cell class="text-muted small">{{ $service->created_at->format('d/m/Y') }}</x-table-cell>
                                        <x-table-cell align="center">
                                            <x-action-buttons
                                                :item="$service"
                                                resource="services"
                                                identifier="code"
                                                :can-delete="false"
                                                size="sm"
                                            />
                                        </x-table-cell>
                                    </x-table-row>
                                @endforeach
                            </x-resource-table>
                        </x-slot>

                        <x-slot name="mobile">
                            @foreach ($recent as $service)
                                @php
                                    $customerName = $service->budget->customer->commonData?->full_name ?? 'N/A';
                                @endphp
                                <x-resource-mobile-item
                                    icon="tools"
                                    :href="route('provider.services.show', $service->code)"
                                >
                                    <x-resource-mobile-header
                                        :title="$service->code"
                                        :subtitle="$service->created_at->format('d/m/Y')"
                                    />

                                    <x-resource-mobile-field
                                        label="Cliente"
                                        :value="$customerName"
                                    />

                                    <x-grid-row g="2">
                                        <x-resource-mobile-field
                                            label="Valor"
                                            :value="\App\Helpers\CurrencyHelper::format($service->total)"
                                            col="col-6"
                                        />
                                        <x-resource-mobile-field
                                            label="Status"
                                            col="col-6"
                                            align="end"
                                        >
                                            <x-status-badge :item="$service" />
                                        </x-resource-mobile-field>
                                    </x-grid-row>
                                </x-resource-mobile-item>
                            @endforeach
                        </x-slot>
                    @else
                        <x-empty-state
                            title="Nenhum serviço recente encontrado"
                            description="Crie novos serviços para visualizar aqui."
                            icon="inbox"
                        />
                    @endif
                </x-resource-list-card>

            <!-- Insights e Atalhos -->
            <x-grid-col size="col-lg-4">
                <x-v-stack gap="4">
                    <!-- Insights -->
                    <x-resource-list-card
                        title="Insights Rápidos"
                        icon="lightbulb"
                        padding="p-3"
                        gap="3"
                    >
                        <x-insight-item
                            icon="check-circle-fill"
                            variant="success"
                            description="Serviços concluídos geram receita garantida para seu negócio."
                        />
                        <x-insight-item
                            icon="clock-fill"
                            variant="warning"
                            description="Acompanhe serviços em andamento para manter prazos."
                        />
                        <x-insight-item
                            icon="graph-up-arrow"
                            variant="primary"
                            description="Monitore a taxa de conclusão para otimizar processos."
                        />
                    </x-resource-list-card>

                    <!-- Atalhos -->
                    <x-quick-actions
                        title="Atalhos Rápidos"
                        icon="link-45deg"
                    >
                        <x-button type="link" href="{{ route('provider.services.create') }}" variant="success" size="sm" icon="plus-circle" label="Criar Serviço" />
                        <x-button type="link" href="{{ route('provider.services.index') }}" variant="primary" size="sm" icon="tools" label="Listar Serviços" />
                        <x-button type="link" href="{{ route('provider.reports.services') }}" variant="secondary" size="sm" icon="file-earmark-text" label="Relatório de Serviços" />
                    </x-quick-actions>
                </x-v-stack>
            </x-grid-col>
        </x-grid-row>
    </x-page-container>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
@endpush
