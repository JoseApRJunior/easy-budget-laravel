@extends('layouts.app')

@section('title', 'Dashboard de Serviços')

@section('content')
    <div class="container-fluid py-4">
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
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-primary bg-gradient me-3">
                                <i class="bi bi-tools text-white"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Total de Serviços</h6>
                                <h3 class="mb-0">{{ $total }}</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">
                            Quantidade total de serviços cadastrados para este tenant.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-success bg-gradient me-3">
                                <i class="bi bi-check-circle-fill text-white"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Serviços Concluídos</h6>
                                <h3 class="mb-0">{{ $completed }}</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">
                            Serviços finalizados com sucesso e aprovados pelos clientes.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-warning bg-gradient me-3">
                                <i class="bi bi-clock-fill text-white"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Em Andamento</h6>
                                <h3 class="mb-0">{{ $inProgress }}</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">
                            Serviços atualmente em execução pela equipe.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-info bg-gradient me-3">
                                <i class="bi bi-graph-up-arrow text-white"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Taxa de Conclusão</h6>
                                <h3 class="mb-0">{{ $completionRate }}%</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">
                            Percentual de serviços concluídos em relação ao total.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cards de Valores Financeiros -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-success bg-gradient me-3">
                                <i class="bi bi-currency-dollar text-white"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Valor Total em Serviços</h6>
                                <h3 class="mb-0">{{ \App\Helpers\CurrencyHelper::format($totalValue) }}</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">
                            Valor total de todos os serviços cadastrados no sistema.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar-circle bg-primary bg-gradient me-3">
                                    <i class="bi bi-bar-chart-line-fill text-white"></i>
                                </div>
                                <h6 class="text-muted mb-0">Distribuição por Status</h6>
                            </div>
                            <div class="chart-container">
                                <canvas id="statusChart" style="max-height: 120px;"></canvas>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">
                            Visualização da distribuição dos serviços por status atual.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Serviços Recentes e Atalhos -->
        <div class="row g-4">
            <!-- Serviços Recentes -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0">
                        <h5 class="mb-0">
                            <i class="bi bi-clock-history me-2"></i>
                            <span class="d-none d-sm-inline">Serviços Recentes</span>
                            <span class="d-sm-none">Recentes</span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        @if ($recent->isNotEmpty())
                            <!-- Desktop View -->
                            <div class="desktop-view">
                                <div class="table-responsive">
                                    <table class="modern-table table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Cliente</th>
                                            <th>Valor</th>
                                            <th>Status</th>
                                            <th>Data</th>
                                            <th class="text-center">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
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
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Mobile View -->
                        <div class="mobile-view">
                            <div class="list-group list-group-flush">
                                @foreach ($recent as $service)
                                    @php
                                        $customerName = $service->budget->customer->commonData->first_name ?? 'N/A';
                                    @endphp
                                    <a href="{{ route('provider.services.show', $service->code) }}" class="list-group-item list-group-item-action py-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="fw-bold text-dark">{{ $service->code }}</span>
                                                    <span class="text-muted small">{{ $service->created_at->format('d/m/Y') }}</span>
                                                </div>
                                            </div>
                                            <i class="bi bi-chevron-right text-muted ms-2"></i>
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
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-inbox mb-2" style="font-size: 2rem;"></i>
                            <br>
                            Nenhum serviço recente encontrado.
                            <br>
                            <small>Crie novos serviços para visualizar aqui.</small>
                        </div>
                    @endif
                    </div>
                </div>
            </div>

            <!-- Insights e Atalhos -->
            <div class="col-lg-4">
                <!-- Insights -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0 py-3">
                        <h6 class="mb-0 fw-bold d-flex align-items-center text-dark">
                            <i class="bi bi-lightbulb me-2 text-warning"></i>Insights Rápidos
                        </h6>
                    </div>
                    <div class="card-body pt-0">
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
                </div>

                <!-- Atalhos -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 py-3">
                        <h6 class="mb-0 fw-bold d-flex align-items-center text-dark">
                            <i class="bi bi-link-45deg me-2 text-primary"></i>Atalhos Rápidos
                        </h6>
                    </div>
                    <div class="card-body pt-0 d-grid gap-2">
                        <a href="{{ route('provider.services.index') }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-tools me-2"></i>Listar Serviços
                        </a>
                        <a href="{{ route('provider.reports.services') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-file-earmark-text me-2"></i>Relatório de Serviços
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
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

            // Preparar dados para o gráfico usando cores do backend
            Object.keys(statusData).forEach(status => {
                const statusInfo = statusData[status];
                if (statusInfo && typeof statusInfo === 'object' && statusInfo.count > 0) {
                    // Formatar label para melhor legibilidade
                    let label = status.charAt(0).toUpperCase() + status.slice(1);
                    label = label.replace(/_/g, ' ').replace('-', ' ');
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
