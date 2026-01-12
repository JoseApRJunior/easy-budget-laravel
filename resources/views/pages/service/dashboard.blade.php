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
                    icon="pie-chart-fill"
                    class="h-100 border-0 shadow-sm"
                >
                    <div class="p-3">
                        <div class="chart-container" style="position: relative; height: 160px;">
                            <canvas id="statusChart"></canvas>
                        </div>
                        <p class="text-muted small mb-0 mt-3 text-center">
                            Acompanhe o fluxo de trabalho por status atual.
                        </p>
                    </div>
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
                    class="border-0 shadow-sm"
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
                                            <span class="fw-bold text-primary">{{ \App\Helpers\CurrencyHelper::format($service->total) }}</span>
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
                    class="border-0 shadow-sm mb-4"
                >
                    <div class="p-3">
                        <div class="d-flex flex-column gap-3">
                            <div class="d-flex align-items-start">
                                <div class="avatar-circle-xs bg-success bg-opacity-10 p-2 rounded me-3">
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                </div>
                                <div>
                                    <p class="small mb-0 text-muted">Serviços concluídos geram receita garantida para seu negócio.</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-start">
                                <div class="avatar-circle-xs bg-warning bg-opacity-10 p-2 rounded me-3">
                                    <i class="bi bi-clock-fill text-warning"></i>
                                </div>
                                <div>
                                    <p class="small mb-0 text-muted">Acompanhe serviços em andamento para manter prazos.</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-start">
                                <div class="avatar-circle-xs bg-primary bg-opacity-10 p-2 rounded me-3">
                                    <i class="bi bi-graph-up-arrow text-primary"></i>
                                </div>
                                <div>
                                    <p class="small mb-0 text-muted">Monitore a taxa de conclusão para otimizar processos.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-resource-list-card>

                <!-- Atalhos -->
                <x-quick-actions title="Atalhos Rápidos" icon="link-45deg" variant="none">
                    <x-button type="link" href="{{ route('provider.services.create') }}" variant="success" size="sm" icon="plus-circle" label="Criar Serviço" />
                    <x-button type="link" href="{{ route('provider.services.index') }}" variant="primary" size="sm" icon="tools" label="Listar Serviços" />
                    <x-button type="link" href="{{ route('provider.reports.services') }}" variant="secondary" size="sm" icon="file-earmark-text" label="Relatório de Serviços" />
                </x-quick-actions>
            </div>
        </div>
    </x-page-container>
@endsection

@push('styles')
    <style>
        .text-code {
            font-family: 'Courier New', monospace;
            background-color: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.85em;
        }

        .chart-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 120px;
            width: 100%;
        }

        .chart-container canvas {
            max-width: 100% !important;
            height: auto !important;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dados para o gráfico de status
            const statusData = @json($stats['status_breakdown'] ?? []);
            const statusLabels = [];
            const statusValues = [];
            const statusColors = [];

            // Preparar dados para o gráfico usando cores e labels do backend
            Object.keys(statusData).forEach(status => {
                const statusInfo = statusData[status];
                if (statusInfo && typeof statusInfo === 'object' && statusInfo.count > 0) {
                    // Garantir que a label esteja traduzida ou formatada
                    let label = statusInfo.label;
                    if (!label) {
                        label = status.charAt(0).toUpperCase() + status.slice(1).replace(/_/g, ' ');
                    }
                    statusLabels.push(label);
                    statusValues.push(statusInfo.count);
                    statusColors.push(statusInfo.color || '#6c757d');
                }
            });

            // Só criar gráfico se houver dados
            if (statusValues.length === 0) {
                // Mostrar mensagem quando não há dados
                const chartContainer = document.querySelector('.chart-container');
                if (chartContainer) {
                    chartContainer.innerHTML =
                        '<p class="text-muted text-center mb-0 small">Nenhum serviço cadastrado</p>';
                }
                return;
            }

            // Criar gráfico de pizza
            const ctx = document.getElementById('statusChart');
            if (!ctx) {
                console.error('Canvas element not found');
                return;
            }
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: statusLabels,
                    datasets: [{
                        data: statusValues,
                        backgroundColor: statusColors,
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return context.label + ': ' + context.parsed + ' (' + percentage +
                                        '%)';
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
@endpush
