@extends('layouts.app')

@section('title', 'Dashboard de Orçamentos')

@section('content')
    <div class="container-fluid py-1">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-file-earmark-text-fill me-2"></i>Dashboard de Orçamentos
                </h1>
                <p class="text-muted mb-0">
                    Visão geral dos orçamentos do seu negócio com métricas e acompanhamento de performance.
                </p>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('provider.dashboard') }}">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('provider.budgets.index') }}">Orçamentos</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        Dashboard
                    </li>
                </ol>
            </nav>
        </div>

        @php
            $total = $stats['total_budgets'] ?? 0;
            $approved = $stats['approved_budgets'] ?? 0;
            $pending = $stats['pending_budgets'] ?? 0;
            $rejected = $stats['rejected_budgets'] ?? 0;
            $totalValue = $stats['total_budget_value'] ?? 0;
            $recent = $stats['recent_budgets'] ?? collect();

            $approvedRate = $total > 0 ? number_format(($approved / $total) * 100, 1, ',', '.') : 0;
        @endphp

        <!-- Cards de Métricas -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-primary bg-gradient me-3">
                                <i class="bi bi-file-earmark-text text-white"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Total de Orçamentos</h6>
                                <h3 class="mb-0">{{ $total }}</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">
                            Quantidade total de orçamentos cadastrados para este tenant.
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
                                <h6 class="text-muted mb-1">Orçamentos Aprovados</h6>
                                <h3 class="mb-0">{{ $approved }}</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">
                            Propostas aprovadas pelos clientes e prontas para execução.
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
                                <h6 class="text-muted mb-1">Orçamentos Pendentes</h6>
                                <h3 class="mb-0">{{ $pending }}</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">
                            Propostas aguardando aprovação ou resposta do cliente.
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
                                <h6 class="text-muted mb-1">Taxa de Aprovação</h6>
                                <h3 class="mb-0">{{ $approvedRate }}%</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">
                            Percentual de orçamentos aprovados em relação ao total.
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
                                <h6 class="text-muted mb-1">Valor Total em Orçamentos</h6>
                                <h3 class="mb-0">R$ {{ number_format($totalValue, 2, ',', '.') }}</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">
                            Soma do valor de todos os orçamentos cadastrados.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-secondary bg-gradient me-3">
                                <i class="bi bi-x-circle-fill text-white"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Orçamentos Rejeitados</h6>
                                <h3 class="mb-0">{{ $rejected }}</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">
                            Propostas recusadas ou rejeitadas pelos clientes.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfico de Distribuição de Status -->
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0">
                        <h5 class="mb-0">
                            <i class="bi bi-bar-chart-line me-2"></i>Distribuição de Orçamentos por Status
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="statusChart" width="400" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conteúdo Principal -->
        <div class="row g-4">
            <!-- Orçamentos Recentes -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-clock-history me-2"></i>Orçamentos Recentes
                        </h5>
                        <a href="{{ route('provider.budgets.index') }}" class="btn btn-sm btn-outline-primary">
                            Ver todos
                        </a>
                    </div>
                    <div class="card-body">
                        @if ($recent instanceof \Illuminate\Support\Collection && $recent->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Código</th>
                                            <th>Cliente</th>
                                            <th>Valor Total</th>
                                            <th>Status</th>
                                            <th>Data de Criação</th>
                                            <th class="text-end">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($recent as $budget)
                                            @php
                                                $customer = $budget->customer ?? null;
                                                $commonData = $customer?->commonData ?? null;

                                                $customerName =
                                                    $commonData?->company_name ??
                                                    trim(
                                                        ($commonData->first_name ?? '') .
                                                            ' ' .
                                                            ($commonData->last_name ?? ''),
                                                    ) ?:
                                                    'Cliente não informado';

                                                $statusBadge = match ($budget->status ?? 'pending') {
                                                    \App\Enums\BudgetStatus::APPROVED->value
                                                        => '<span class="badge bg-success-subtle text-success">Aprovado</span>',
                                                    \App\Enums\BudgetStatus::REJECTED->value
                                                        => '<span class="badge bg-danger-subtle text-danger">Rejeitado</span>',
                                                    \App\Enums\BudgetStatus::COMPLETED->value
                                                        => '<span class="badge bg-info-subtle text-info">Concluído</span>',
                                                    \App\Enums\BudgetStatus::CANCELLED->value
                                                        => '<span class="badge bg-secondary-subtle text-secondary">Cancelado</span>',
                                                    \App\Enums\BudgetStatus::DRAFT->value
                                                        => '<span class="badge bg-light-subtle text-muted">Rascunho</span>',
                                                    default
                                                        => '<span class="badge bg-warning-subtle text-warning">Pendente</span>',
                                                };
                                            @endphp
                                            <tr>
                                                <td>{{ $budget->code }}</td>
                                                <td>{{ Str::limit($customerName, 30) }}</td>
                                                <td>R$ {{ number_format($budget->total ?? 0, 2, ',', '.') }}</td>
                                                <td>{!! $statusBadge !!}</td>
                                                <td>{{ optional($budget->created_at)->format('d/m/Y') }}</td>
                                                <td class="text-end">
                                                    <a href="{{ route('provider.budgets.show', $budget->code) }}"
                                                        class="btn btn-sm btn-outline-secondary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted mb-0">
                                Nenhum orçamento recente encontrado. Crie novos orçamentos para visualizar aqui.
                            </p>
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
                                <i class="bi bi-clock-fill text-warning me-2"></i>
                                Acompanhe orçamentos pendentes para aumentar sua taxa de conversão.
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-graph-up-arrow text-success me-2"></i>
                                Orçamentos aprovados geram receita garantida para seu negócio.
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-envelope-fill text-primary me-2"></i>
                                Envie lembretes para clientes com orçamentos pendentes.
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
                        <a href="{{ route('provider.budgets.create') }}" class="btn btn-sm btn-success">
                            <i class="bi bi-plus-circle me-2"></i>Novo Orçamento
                        </a>
                        <a href="{{ route('provider.budgets.index') }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-file-earmark-text me-2"></i>Listar Orçamentos
                        </a>
                        <a href="{{ route('provider.reports.budgets') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-file-earmark-text me-2"></i>Relatório de Orçamentos
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

            // Mapeamento de cores para cada status
            const statusColorMap = {
                'draft': '#6c757d',
                'pending': '#ffc107',
                'approved': '#28a745',
                'rejected': '#dc3545',
                'cancelled': '#6c757d',
                'completed': '#007bff'
            };

            // Preparar dados para o gráfico
            Object.keys(statusData).forEach(status => {
                if (statusData[status] > 0) {
                    statusLabels.push(status.charAt(0).toUpperCase() + status.slice(1));
                    statusValues.push(statusData[status]);
                    statusColors.push(statusColorMap[status] || '#6c757d');
                }
            });

            // Criar gráfico de pizza
            const ctx = document.getElementById('statusChart').getContext('2d');
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
