@extends('layouts.app')

@section('title', 'Dashboard de Serviços')

@section('content')
    <div class="container-fluid py-1">
        <!-- Cabeçalho -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="flex-grow-1">
                    <h1 class="h4 h3-md mb-1">
                        <i class="bi bi-tools me-2"></i>
                        <span class="d-none d-sm-inline">Dashboard de Serviços</span>
                        <span class="d-sm-none">Serviços</span>
                    </h1>
                </div>
                <nav aria-label="breadcrumb" class="d-none d-md-block">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="{{ route('provider.dashboard') }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('provider.services.index') }}">Serviços</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            Dashboard
                        </li>
                    </ol>
                </nav>
            </div>
            <p class="text-muted mb-0 small">
                Visão geral dos serviços do seu negócio com métricas e acompanhamento de performance.
            </p>
        </div>

        @php
            $total = $stats['total_services'] ?? 0;
            $completed = $stats['completed_services'] ?? 0;
            $inProgress = $stats['in_progress_services'] ?? 0;
            $pending = $stats['pending_services'] ?? 0;
            $cancelled = $stats['cancelled_services'] ?? 0;
            $totalValue = $stats['total_service_value'] ?? 0;
            $recent = $stats['recent_services'] ?? collect();

            $completionRate = $total > 0 ? number_format(($completed / $total) * 100, 1, ',', '.') : 0;
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
                                <h3 class="mb-0">R$ {{ number_format($totalValue, 2, ',', '.') }}</h3>
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
                                    <thead class="table-light">
                                        <tr>
                                            <th>Código</th>
                                            <th>Cliente</th>
                                            <th>Status</th>
                                            <th>Valor</th>
                                            <th>Data</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($recent as $service)
                                            <tr>
                                                <td>
                                                    <code class="text-code">{{ $service->code }}</code>
                                                </td>
                                                <td>
                                                    {{ $service->budget->customer->commonData->first_name ?? 'N/A' }}
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-{{ $service->serviceStatus->color ?? 'secondary' }}">
                                                        {{ $service->serviceStatus->name ?? 'N/A' }}
                                                    </span>
                                                </td>
                                                <td>R$ {{ number_format($service->total, 2, ',', '.') }}</td>
                                                <td>{{ $service->created_at->format('d/m/Y') }}</td>
                                                <td>
                                                    <a href="{{ route('provider.services.show', $service->code) }}"
                                                            class="btn btn-sm btn-info text-white" title="Visualizar">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
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
                                    <a href="{{ route('provider.services.show', $service->code) }}" class="list-group-item list-group-item-action py-3">
                                        <div class="d-flex align-items-start">
                                            <i class="bi bi-tools text-muted me-2 mt-1"></i>
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold mb-1">{{ $service->code }}</div>
                                                <div class="small text-muted mb-2">{{ $service->budget->customer->commonData->first_name ?? 'N/A' }}</div>
                                                <div class="d-flex gap-2 flex-wrap align-items-center">
                                                    <span class="badge bg-{{ $service->serviceStatus->color ?? 'secondary' }}">{{ $service->serviceStatus->name ?? 'N/A' }}</span>
                                                    <span class="small text-muted">R$ {{ number_format($service->total, 2, ',', '.') }}</span>
                                                    <span class="small text-muted">{{ $service->created_at->format('d/m/Y') }}</span>
                                                </div>
                                            </div>
                                            <i class="bi bi-chevron-right text-muted ms-2"></i>
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
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0">
                            <i class="bi bi-lightbulb me-2"></i>Insights Rápidos
                        </h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0 small text-muted">
                            <li class="mb-2">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                Serviços concluídos geram receita garantida para seu negócio.
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-clock-fill text-warning me-2"></i>
                                Acompanhe serviços em andamento para manter prazos.
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-graph-up-arrow text-primary me-2"></i>
                                Monitore a taxa de conclusão para otimizar processos.
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0">
                            <i class="bi bi-link-45deg me-2"></i>Atalhos
                        </h6>
                    </div>
                    <div class="card-body d-grid gap-2">
                        <a href="{{ route('provider.services.create') }}" class="btn btn-sm btn-success">
                            <i class="bi bi-plus-circle me-2"></i>Novo Serviço
                        </a>
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
