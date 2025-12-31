@extends('layouts.app')

@section('title', 'Dashboard de Orçamentos')

@section('content')
    <div class="container-fluid py-1">
        <x-page-header
            title="Dashboard de Orçamentos"
            icon="file-earmark-text"
            :breadcrumb-items="[
                'Orçamentos' => route('provider.budgets.index'),
                'Dashboard' => '#'
            ]">
            <p class="text-muted mb-0">Visão geral dos orçamentos do seu negócio com métricas e acompanhamento de performance.</p>
        </x-page-header>

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
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar avatar-sm bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                <i class="bi bi-file-earmark-text text-primary fs-5"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Total de Orçamentos</small>
                                <h3 class="fw-bold mb-0 text-dark">{{ $total }}</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0 lh-sm">
                            Quantidade total de orçamentos cadastrados para este tenant.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar avatar-sm bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Orçamentos Aprovados</small>
                                <h3 class="fw-bold mb-0 text-dark">{{ $approved }}</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0 lh-sm">
                            Propostas aprovadas pelos clientes e prontas para execução.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar avatar-sm bg-warning bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                <i class="bi bi-clock-fill text-warning fs-5"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Orçamentos Pendentes</small>
                                <h3 class="fw-bold mb-0 text-dark">{{ $pending }}</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0 lh-sm">
                            Propostas aguardando aprovação ou resposta do cliente.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar avatar-sm bg-info bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                <i class="bi bi-graph-up-arrow text-info fs-5"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Taxa de Aprovação</small>
                                <h3 class="fw-bold mb-0 text-dark">{{ $approvedRate }}%</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0 lh-sm">
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
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar avatar-sm bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                <i class="bi bi-cash-stack text-success fs-5"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Valor Total em Orçamentos</small>
                                <h3 class="fw-bold mb-0 text-dark">R$ {{ number_format($totalValue, 2, ',', '.') }}</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0 lh-sm">
                            Soma do valor de todos os orçamentos cadastrados.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar avatar-sm bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                <i class="bi bi-calculator text-primary fs-5"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Ticket Médio</small>
                                <h3 class="fw-bold mb-0 text-dark">R$ {{ $total > 0 ? number_format($totalValue / $total, 2, ',', '.') : '0,00' }}</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0 lh-sm">
                            Valor médio por orçamento gerado no sistema.
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
                    <div class="card-header bg-transparent border-0 py-3">
                        <h5 class="mb-0 d-flex align-items-center">
                            <i class="bi bi-clock-history me-2 "></i>
                            <span class="d-none d-sm-inline">Orçamentos Recentes</span>
                            <span class="d-sm-none">Recentes</span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        @if ($recent instanceof \Illuminate\Support\Collection && $recent->isNotEmpty())
                            <!-- Desktop View -->
                            <div class="desktop-view">
                                <div class="table-responsive">
                                    <table class="modern-table table mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-muted small text-uppercase" style="font-size: 0.7rem;">Código</th>
                                            <th class="text-muted small text-uppercase" style="font-size: 0.7rem;">Cliente</th>
                                            <th class="text-muted small text-uppercase" style="font-size: 0.7rem;">Valor Total</th>
                                            <th class="text-muted small text-uppercase" style="font-size: 0.7rem;">Status</th>
                                            <th class="text-muted small text-uppercase" style="font-size: 0.7rem;">Data</th>
                                            <th class="text-end text-muted small text-uppercase" style="font-size: 0.7rem;">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($recent as $budget)
                                            @php
                                                $customer = $budget->customer ?? null;
                                                $commonData = $customer?->commonData ?? null;

                                                $customerName = $commonData?->company_name ??
                                                    trim(($commonData->first_name ?? '') . ' ' . ($commonData->last_name ?? '')) ?:
                                                    'Cliente não informado';

                                                $statusValue = is_string($budget->status) ? $budget->status : ($budget->status?->value ?? 'draft');
                                                $statusEnum = \App\Enums\BudgetStatus::fromString($statusValue);
                                            @endphp
                                            <tr>
                                                <td class="fw-bold text-dark">{{ $budget->code }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar avatar-xs me-2 bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                            <i class="bi bi-person text-primary small"></i>
                                                        </div>
                                                        <div class="text-truncate" style="max-width: 150px;" title="{{ $customerName }}">
                                                            {{ $customerName }}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="fw-bold text-dark">R$ {{ number_format($budget->total ?? 0, 2, ',', '.') }}</td>
                                                <td>
                                                    <span class="badge rounded-pill" style="background-color: {{ $statusEnum->getColor() }}20; color: {{ $statusEnum->getColor() }}; border: 1px solid {{ $statusEnum->getColor() }}40;">
                                                        <i class="bi {{ $statusEnum->getIcon() }} me-1"></i>
                                                        {{ $statusEnum->getDescription() }}
                                                    </span>
                                                </td>
                                                <td class="text-muted small">{{ optional($budget->created_at)->format('d/m/Y') }}</td>
                                                <td class="text-end">
                                                    <x-button type="link" :href="route('provider.budgets.show', $budget->code)" variant="info" size="sm" icon="eye" title="Visualizar" />
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
                                @foreach ($recent as $budget)
                                    @php
                                        $customer = $budget->customer ?? null;
                                        $commonData = $customer?->commonData ?? null;
                                        $customerName = $commonData?->company_name ?? trim(($commonData->first_name ?? '') . ' ' . ($commonData->last_name ?? '')) ?: 'Cliente não informado';

                                        $statusValue = is_string($budget->status) ? $budget->status : ($budget->status?->value ?? 'draft');
                                        $statusEnum = \App\Enums\BudgetStatus::fromString($statusValue);
                                    @endphp
                                    <a href="{{ route('provider.budgets.show', $budget->code) }}" class="list-group-item list-group-item-action py-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="avatar avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                                <i class="bi bi-file-earmark-text text-primary"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="fw-bold text-dark">{{ $budget->code }}</span>
                                                    <span class="text-muted small">{{ optional($budget->created_at)->format('d/m/Y') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted d-block text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Cliente</small>
                                            <div class="text-dark fw-semibold text-truncate">{{ $customerName }}</div>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <small class="text-muted d-block text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Valor Total</small>
                                                <span class="fw-bold text-primary">R$ {{ number_format($budget->total ?? 0, 2, ',', '.') }}</span>
                                            </div>
                                            <div class="col-6 text-end">
                                                <small class="text-muted d-block text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Status</small>
                                                <span class="badge rounded-pill" style="background-color: {{ $statusEnum->getColor() }}20; color: {{ $statusEnum->getColor() }}; border: 1px solid {{ $statusEnum->getColor() }}40; font-size: 0.7rem;">
                                                    {{ $statusEnum->getDescription() }}
                                                </span>
                                            </div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="p-5 text-center text-muted">
                            <i class="bi bi-inbox mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                            <p class="mb-1 fw-bold">Nenhum orçamento recente</p>
                            <p class="small mb-0">Crie novos orçamentos para visualizar aqui.</p>
                        </div>
                    @endif
                    </div>
                </div>
            </div>

            <!-- Insights e Atalhos -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0 py-3">
                        <h6 class="mb-0 fw-bold d-flex align-items-center">
                            <i class="bi bi-lightbulb me-2 "></i>Insights Rápidos
                        </h6>
                    </div>
                    <div class="card-body pt-0">
                        <div class="d-flex flex-column gap-3">
                            <div class="d-flex align-items-start">
                                <div class="bg-warning bg-opacity-10 p-2 rounded me-3">
                                    <i class="bi bi-clock-fill text-warning"></i>
                                </div>
                                <div>
                                    <p class="small mb-0 text-muted">Acompanhe orçamentos pendentes para aumentar sua taxa de conversão.</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-start">
                                <div class="bg-success bg-opacity-10 p-2 rounded me-3">
                                    <i class="bi bi-graph-up-arrow text-success"></i>
                                </div>
                                <div>
                                    <p class="small mb-0 text-muted">Orçamentos aprovados geram receita garantida para seu negócio.</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-start">
                                <div class="bg-primary bg-opacity-10 p-2 rounded me-3">
                                    <i class="bi bi-envelope-check text-primary"></i>
                                </div>
                                <div>
                                    <p class="small mb-0 text-muted">Envie lembretes para clientes com orçamentos pendentes.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 py-3">
                        <h6 class="mb-0 fw-bold d-flex align-items-center">
                            <i class="bi bi-link-45deg me-2 text-primary"></i>Atalhos Rápidos
                        </h6>
                    </div>
                    <div class="card-body pt-0 d-grid gap-2">
                        <x-button type="link" :href="route('provider.budgets.create')" variant="primary" size="sm" icon="plus-circle" label="Novo Orçamento" class="w-100 justify-content-start" />
                        <x-button type="link" :href="route('provider.budgets.index')" variant="primary" outline size="sm" icon="list-ul" label="Listar Todos" class="w-100 justify-content-start" />
                        <x-button type="link" :href="route('provider.reports.budgets')" variant="secondary" outline size="sm" icon="file-earmark-bar-graph" label="Relatórios" class="w-100 justify-content-start" />
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
