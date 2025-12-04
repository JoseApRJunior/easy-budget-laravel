@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="container-fluid py-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0"><i class="bi bi-speedometer2 me-2"></i>Dashboard</h1>
                <p class="text-muted mb-0">Período: {{ $currentPeriod ?? 'month' }} • Atualizado: {{ $lastUpdated ?? '' }}
                </p>
            </div>
        </div>

        @php
            $m = $metrics ?? [];
            $saldo = $m['saldo_atual']['valor'] ?? 0;
            $saldoFmt = $m['saldo_atual']['formatado'] ?? 'R$ 0,00';
            $receitaFmt = $m['receita_total']['formatado'] ?? 'R$ 0,00';
            $despesaFmt = $m['despesas_totais']['formatado'] ?? 'R$ 0,00';
            $transHoje = $m['transacoes_hoje']['quantidade'] ?? 0;
            $metas = $m['metas_alcancadas'] ?? ['receita' => 0, 'despesas' => 0, 'saldo' => 0];
        @endphp

        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center"><i class="bi bi-cash-coin text-success fs-1 mb-3"></i>
                        <h3 class="text-success mb-1">{{ $receitaFmt }}</h3>
                        <h6 class="text-muted mb-0">Receita</h6>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center"><i class="bi bi-credit-card-2-front text-danger fs-1 mb-3"></i>
                        <h3 class="text-danger mb-1">{{ $despesaFmt }}</h3>
                        <h6 class="text-muted mb-0">Despesas</h6>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center"><i class="bi bi-wallet2 text-primary fs-1 mb-3"></i>
                        <h3 class="text-primary mb-1">{{ $saldoFmt }}</h3>
                        <h6 class="text-muted mb-0">Saldo Atual</h6>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center"><i class="bi bi-list-check text-info fs-1 mb-3"></i>
                        <h3 class="text-info mb-1">{{ $transHoje }}</h3>
                        <h6 class="text-muted mb-0">Transações Hoje</h6>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Evolução Mensal</h6>
                    </div>
                    <div class="card-body"><canvas id="evolutionChart" height="120"></canvas></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bi bi-flag me-2"></i>Metas Alcançadas</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled small text-muted mb-0">
                            <li class="mb-2"><i class="bi bi-graph-up text-success me-2"></i>Receita:
                                {{ number_format($metas['receita'] ?? 0, 1, ',', '.') }}%</li>
                            <li class="mb-2"><i class="bi bi-graph-down text-danger me-2"></i>Despesas:
                                {{ number_format($metas['despesas'] ?? 0, 1, ',', '.') }}%</li>
                            <li class="mb-2"><i class="bi bi-percent text-primary me-2"></i>Saldo:
                                {{ number_format($metas['saldo'] ?? 0, 1, ',', '.') }}%</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bi bi-currency-dollar me-2"></i>Receita Mensal</h6>
                    </div>
                    <div class="card-body"><canvas id="revenueChart" height="120"></canvas></div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Despesas por Categoria</h6>
                    </div>
                    <div class="card-body"><canvas id="expensesChart" height="120"></canvas></div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Atividades Recentes</h6>
                    </div>
                    <div class="card-body">
                        @if (!empty($recentTransactions))
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Data</th>
                                            <th>Ação</th>
                                            <th>Descrição</th>
                                            <th>Usuário</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($recentTransactions as $a)
                                            <tr>
                                                <td>{{ optional($a->created_at)->format('d/m/Y H:i') }}</td>
                                                <td>{{ $a->action_type }}</td>
                                                <td>{{ $a->description }}</td>
                                                <td>{{ $a->user_name }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                        </div>@else<div class="text-center py-5"><i class="bi bi-inbox display-4 text-muted mb-3"></i>
                                <h6 class="text-muted">Nenhuma atividade encontrada</h6>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bi bi-link-45deg me-2"></i>Ações Rápidas</h6>
                    </div>
                    <div class="card-body d-grid gap-2">
                        @if (!empty($quickActions))
                            @foreach ($quickActions as $qa)
                                <a href="{{ isset($qa['route']) && \Illuminate\Support\Facades\Route::has($qa['route']) ? route($qa['route'], $qa['params'] ?? []) : '#' }}"
                                    class="btn btn-sm btn-outline-secondary"><i
                                        class="bi bi-{{ $qa['icon'] ?? 'lightning' }} me-2"></i>{{ $qa['title'] ?? 'Ação' }}</a>
                            @endforeach@else<p class="text-muted small mb-0">Nenhuma ação disponível
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const charts = @json($charts ?? []);
            const evo = charts.monthly_evolution || [];
            const evoLabels = evo.map(i => i.month),
                evoRevenue = evo.map(i => i.revenue),
                evoExpenses = evo.map(i => i.expenses);
            const ctxEvo = document.getElementById('evolutionChart');
            if (ctxEvo) {
                new Chart(ctxEvo, {
                    type: 'line',
                    data: {
                        labels: evoLabels,
                        datasets: [{
                            label: 'Receita',
                            data: evoRevenue,
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16,185,129,0.1)',
                            tension: 0.4,
                            fill: true
                        }, {
                            label: 'Despesas',
                            data: evoExpenses,
                            borderColor: '#EF4444',
                            backgroundColor: 'rgba(239,68,68,0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }

            const rev = charts.monthly_revenue || [];
            const revLabels = rev.map(i => i.month),
                revValues = rev.map(i => i.revenue);
            const ctxRev = document.getElementById('revenueChart');
            if (ctxRev) {
                new Chart(ctxRev, {
                    type: 'bar',
                    data: {
                        labels: revLabels,
                        datasets: [{
                            label: 'Receita',
                            data: revValues,
                            backgroundColor: '#3B82F6'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }

            const exp = charts.expenses_by_category || [];
            const expLabels = exp.map(i => i.category),
                expValues = exp.map(i => i.amount);
            const ctxExp = document.getElementById('expensesChart');
            if (ctxExp) {
                new Chart(ctxExp, {
                    type: 'doughnut',
                    data: {
                        labels: expLabels,
                        datasets: [{
                            data: expValues,
                            backgroundColor: ['#F59E0B', '#10B981', '#3B82F6', '#6B7280'],
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        });
    </script>
@endpush
