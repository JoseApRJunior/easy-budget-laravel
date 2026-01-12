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
        <div class="row g-4 mb-4">
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
        </div>

        <!-- Cards de Valores Financeiros -->
        <div class="row g-4 mb-4">
            <x-stat-card
                title="Valor Total em Serviços"
                :value="\App\Helpers\CurrencyHelper::format($totalValue)"
                description="Valor total de todos os serviços cadastrados no sistema."
                icon="currency-dollar"
                variant="success"
                col="col-md-6"
            />

            <div class="col-md-6">
                <x-resource-list-card
                    title="Distribuição por Status"
                    icon="pie-chart"
                    padding="p-3"
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
            </div>
        </div>

        <!-- Serviços Recentes e Atalhos -->
        <div class="row g-4">
            <!-- Serviços Recentes -->
            <div class="col-lg-8">
                <x-resource-list-card
                    title="Serviços Recentes"
                    icon="clock-history"
                    :total="$recent->count()"
                >
                    @if ($recent->isNotEmpty())
                        <x-slot name="desktop">
                            <x-resource-table>
                                <x-slot name="thead">
                                    <tr>
                                        <th>Código</th>
                                        <th>Cliente</th>
                                        <th>Valor</th>
                                        <th>Status</th>
                                        <th>Data</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </x-slot>

                                @foreach ($recent as $service)
                                    @php
                                        $customerName = $service->budget->customer->commonData->first_name ?? 'N/A';
                                    @endphp
                                    <tr>
                                        <td class="fw-bold text-dark">{{ $service->code }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="text-truncate" style="max-width: 150px;" title="{{ $customerName }}">
                                                    {{ $customerName }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="fw-bold text-dark">{{ \App\Helpers\CurrencyHelper::format($service->total) }}</td>
                                        <td>
                                            <x-status-badge :item="$service" />
                                        </td>
                                        <td class="text-muted small">{{ $service->created_at->format('d/m/Y') }}</td>
                                        <td class="text-center">
                                            <x-action-buttons
                                                :item="$service"
                                                resource="services"
                                                identifier="code"
                                                :can-delete="false"
                                                size="sm"
                                            />
                                        </td>
                                    </tr>
                                @endforeach
                            </x-resource-table>
                        </x-slot>

                        <x-slot name="mobile">
                            @foreach ($recent as $service)
                                @php
                                    $customerName = $service->budget->customer->commonData->first_name ?? 'N/A';
                                @endphp
                                <x-resource-mobile-item
                                    icon="tools"
                                    :href="route('provider.services.show', $service->code)"
                                >
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-bold text-dark">{{ $service->code }}</span>
                                        <span class="text-muted small">{{ $service->created_at->format('d/m/Y') }}</span>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted d-block text-uppercase mb-1 small fw-bold">Cliente</small>
                                        <div class="text-dark fw-semibold text-truncate">{{ $customerName }}</div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <small class="text-muted d-block text-uppercase mb-1 small fw-bold">Valor</small>
                                            <span class="fw-bold text-dark">{{ \App\Helpers\CurrencyHelper::format($service->total) }}</span>
                                        </div>
                                        <div class="col-6 text-end">
                                            <small class="text-muted d-block text-uppercase mb-1 small fw-bold">Status</small>
                                            <x-status-badge :item="$service" />
                                        </div>
                                    </div>
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
            </div>

            <!-- Insights e Atalhos -->
            <div class="col-lg-4">
                <!-- Insights -->
                <x-resource-list-card
                    title="Insights Rápidos"
                    icon="lightbulb"
                    class="mb-4"
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
            </div>
        </div>
    </x-page-container>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
@endpush
