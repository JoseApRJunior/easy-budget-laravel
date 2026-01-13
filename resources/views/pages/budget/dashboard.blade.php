@extends('layouts.app')

@section('title', 'Dashboard de Orçamentos')

@section('content')
    <div class="container-fluid py-4">
    <x-layout.page-header
        title="Dashboard de Orçamentos"
        icon="file-earmark-text"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Orçamentos' => '#'
        ]">
            <p class="text-muted mb-0 small">Visão geral dos orçamentos do seu negócio com métricas e acompanhamento de performance.</p>
        </x-layout.page-header>

        @php
            $total = $stats['total_budgets'] ?? 0;
            $approved = $stats['approved_budgets'] ?? 0;
            $pending = $stats['pending_budgets'] ?? 0;
            $rejected = $stats['rejected_budgets'] ?? 0;
            $totalValue = $stats['total_budget_value'] ?? 0;
            $recent = $stats['recent_budgets'] ?? collect();

            $approvedRate = $total > 0 ? \App\Helpers\CurrencyHelper::format(($approved / $total) * 100, 1, false) : 0;
        @endphp

        <!-- Cards de Métricas -->
        <x-layout.grid-row>
            <x-dashboard.stat-card
                title="Total de Orçamentos"
                :value="$total"
                description="Quantidade total de orçamentos cadastrados."
                icon="file-earmark-text"
                variant="primary"
            />

            <x-dashboard.stat-card
                title="Orçamentos Aprovados"
                :value="$approved"
                description="Orçamentos que foram aceitos pelos clientes."
                icon="check-circle"
                variant="success"
            />

            <x-dashboard.stat-card
                title="Orçamentos Pendentes"
                :value="$pending"
                description="Aguardando resposta ou revisão do cliente."
                icon="clock"
                variant="warning"
            />

            <x-dashboard.stat-card
                title="Taxa de Aprovação"
                :value="$approvedRate . '%'"
                description="Percentual de orçamentos aprovados."
                icon="graph-up-arrow"
                variant="info"
            />
        </x-layout.grid-row>

        <!-- Cards de Valores Financeiros e Gráfico -->
        <x-layout.grid-row>
            <x-dashboard.stat-card
                title="Valor Total em Orçamentos"
                :value="\App\Helpers\CurrencyHelper::format($totalValue)"
                description="Soma total de todos os orçamentos gerados."
                icon="cash-stack"
                variant="success"
                col="col-md-4"
            />

            <x-dashboard.stat-card
                title="Ticket Médio"
                :value="\App\Helpers\CurrencyHelper::format($total > 0 ? $totalValue / $total : 0)"
                description="Valor médio por orçamento gerado no sistema."
                icon="calculator"
                variant="primary"
                col="col-md-4"
            />

            <x-resource.resource-list-card
                title="Distribuição por Status"
                icon="bar-chart-line"
                padding="p-3"
                col="col-md-4"
            >
                <x-dashboard.chart-doughnut
                    id="statusChart"
                    :data="$stats['status_breakdown_detailed'] ?? []"
                    empty-text="Nenhum orçamento cadastrado"
                />
            </x-resource.resource-list-card>
        </x-layout.grid-row>

        <!-- Conteúdo Principal -->
        <div class="row g-4">
            <!-- Orçamentos Recentes (8 colunas) -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 py-3">
                        <h5 class="mb-0 d-flex align-items-center fw-bold text-dark">
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
                                                <th>Código</th>
                                                <th>Cliente</th>
                                                <th>Valor Total</th>
                                                <th>Status</th>
                                                <th>Data</th>
                                                <th class="text-center">Ações</th>
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
                                                @endphp
                                                <tr>
                                                    <td class="fw-bold text-dark">{{ $budget->code }}</td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="text-truncate" style="max-width: 150px;" title="{{ $customerName }}">
                                                                {{ $customerName }}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="fw-bold text-dark">{{ \App\Helpers\CurrencyHelper::format($budget->total ?? 0) }}</td>
                                                    <td>
                                                        <x-ui.status-badge :item="$budget" />
                                                    </td>
                                                    <td class="text-muted small">{{ optional($budget->created_at)->format('d/m/Y') }}</td>
                                                    <td class="text-center">
                                                        <x-resource.action-buttons
                                                            :item="$budget"
                                                            resource="budgets"
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
                                    @foreach ($recent as $budget)
                                        @php
                                            $customer = $budget->customer ?? null;
                                            $commonData = $customer?->commonData ?? null;
                                            $customerName = $commonData?->company_name ?? trim(($commonData->first_name ?? '') . ' ' . ($commonData->last_name ?? '')) ?: 'Cliente não informado';
                                        @endphp
                                        <a href="{{ route('provider.budgets.show', $budget->code) }}" class="list-group-item list-group-item-action py-3">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="fw-bold text-dark">{{ $budget->code }}</span>
                                                        <span class="text-muted small">{{ optional($budget->created_at)->format('d/m/Y') }}</span>
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
                                                    <small class="text-muted d-block text-uppercase mb-1 small fw-bold">Valor Total</small>
                                                    <span class="fw-bold text-primary">{{ \App\Helpers\CurrencyHelper::format($budget->total ?? 0) }}</span>
                                                </div>
                                                <div class="col-6 text-end">
                                                    <small class="text-muted d-block text-uppercase mb-1 small fw-bold">Status</small>
                                                    <x-ui.status-badge :item="$budget" />
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

            <!-- Sidebar (4 colunas) -->
            <div class="col-lg-4">
                <!-- Insights -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0 py-3">
                        <h6 class="mb-0 fw-bold d-flex align-items-center text-dark">
                            <i class="bi bi-lightbulb me-2 "></i>Insights Rápidos
                        </h6>
                    </div>
                    <div class="card-body pt-0">
                        <div class="d-flex flex-column gap-3">
                            <div class="d-flex align-items-start">
                                <div class="avatar-circle-xs bg-warning bg-opacity-10 p-2 rounded me-3">
                                    <i class="bi bi-clock-fill text-warning"></i>
                                </div>
                                <div>
                                    <p class="small mb-0 text-muted">Acompanhe orçamentos pendentes para aumentar sua taxa de conversão.</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-start">
                                <div class="avatar-circle-xs bg-success bg-opacity-10 p-2 rounded me-3">
                                    <i class="bi bi-graph-up-arrow text-success"></i>
                                </div>
                                <div>
                                    <p class="small mb-0 text-muted">Orçamentos aprovados geram receita garantida para seu negócio.</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-start">
                                <div class="avatar-circle-xs bg-primary bg-opacity-10 p-2 rounded me-3">
                                    <i class="bi bi-envelope-check text-primary"></i>
                                </div>
                                <div>
                                    <p class="small mb-0 text-muted">Envie lembretes para clientes com orçamentos pendentes.</p>
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
                        <a href="{{ route('provider.budgets.create') }}" class="btn btn-sm btn-success">
                            <i class="bi bi-plus-circle me-2"></i>Novo Orçamento
                        </a>
                        <a href="{{ route('provider.budgets.index') }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-list-ul me-2"></i>Listar Orçamentos
                        </a>
                        <a href="{{ route('provider.budgets.index', ['deleted' => 'only']) }}"
                            class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-archive me-2"></i>Ver Deletados
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        function refreshData() {
            window.location.reload();
        }
    </script>
@endpush
