@extends('layouts.admin')

@section('title', 'Relatórios Financeiros - Admin')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0 text-gray-800">Relatórios Financeiros</h1>
            <p class="text-muted">Análise detalhada de receitas, custos e performance</p>
        </div>
    </div>

    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Filtros</h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.financial.reports') }}" class="form-inline">
                        <div class="form-group mr-3">
                            <label for="start_date" class="mr-2">Data Inicial:</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="{{ $filters['start_date'] ? $filters['start_date']->format('Y-m-d') : date('Y-m-01') }}">
                        </div>
                        <div class="form-group mr-3">
                            <label for="end_date" class="mr-2">Data Final:</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="{{ $filters['end_date'] ? $filters['end_date']->format('Y-m-d') : date('Y-m-d') }}">
                        </div>
                        <div class="form-group mr-3">
                            <label for="tenant_id" class="mr-2">Empresa:</label>
                            <select class="form-control" id="tenant_id" name="tenant_id">
                                <option value="">Todas as Empresas</option>
                                @foreach($tenants as $tenant)
                                <option value="{{ $tenant->id }}" {{ $filters['tenant_id'] == $tenant->id ? 'selected' : '' }}>
                                    {{ $tenant->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary mr-2">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        <a href="{{ route('admin.financial.reports') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Limpar
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumo Geral -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Receita Total no Período
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                R$ {{ number_format(array_sum(array_column($reports['revenue_by_period'], 'total')), 2, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Custos Totais
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                R$ {{ number_format(array_sum($reports['costs_by_category']), 2, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-minus-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Lucro Líquido
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                R$ {{ number_format(array_sum(array_column($reports['revenue_by_period'], 'total')) - array_sum($reports['costs_by_category']), 2, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Receitas Pendentes
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                R$ {{ number_format(array_sum(array_column($reports['outstanding_receivables'], 'total')), 2, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos e Tabelas -->
    <div class="row">
        <!-- Receita por Período -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Receita por Período</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custos por Categoria -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Custos por Categoria</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie">
                        <canvas id="costsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Performance dos Provedores -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Performance dos Provedores</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Empresa</th>
                                    <th>Receita</th>
                                    <th>Clientes</th>
                                    <th>Faturas Pagas</th>
                                    <th>Ticket Médio</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reports['provider_performance'] as $provider)
                                <tr>
                                    <td>{{ $provider['name'] }}</td>
                                    <td class="text-success">
                                        R$ {{ number_format($provider['revenue'], 2, ',', '.') }}
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ $provider['customer_count'] }}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-success">{{ $provider['paid_invoices'] }}</span>
                                    </td>
                                    <td>
                                        R$ {{ number_format($provider['customer_count'] > 0 ? $provider['revenue'] / $provider['customer_count'] : 0, 2, ',', '.') }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Métodos de Pagamento -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Métodos de Pagamento</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie">
                        <canvas id="paymentMethodsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tendências Financeiras -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Tendências Financeiras</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="trendsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ações -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-success btn-block" onclick="exportReports()">
                                <i class="fas fa-download"></i> Exportar Relatório
                            </button>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-info btn-block text-white" onclick="printReports()">
                                <i class="fas fa-print"></i> Imprimir
                            </button>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="{{ route('admin.financial.index') }}" class="btn btn-secondary btn-block">
                                <i class="fas fa-arrow-left"></i> Voltar
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-warning btn-block" onclick="refreshData()">
                                <i class="fas fa-sync"></i> Atualizar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    initializeCharts();
});

function initializeCharts() {
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode(array_column($reports['revenue_by_period'], 'date')) !!},
            datasets: [{
                label: 'Receita',
                data: {!! json_encode(array_column($reports['revenue_by_period'], 'total')) !!},
                borderColor: 'rgba(78, 115, 223, 1)',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'R$ ' + value.toLocaleString('pt-BR');
                        }
                    }
                }
            }
        }
    });

    // Costs Chart
    const costsCtx = document.getElementById('costsChart').getContext('2d');
    new Chart(costsCtx, {
        type: 'doughnut',
        data: {
            labels: ['Assinatura', 'Taxas de Processamento', 'Operacional'],
            datasets: [{
                data: {!! json_encode(array_values($reports['costs_by_category'])) !!},
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc']
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

    // Payment Methods Chart
    const paymentCtx = document.getElementById('paymentMethodsChart').getContext('2d');
    new Chart(paymentCtx, {
        type: 'pie',
        data: {
            labels: {!! json_encode(array_column($reports['payment_method_analysis'], 'payment_method')) !!},
            datasets: [{
                data: {!! json_encode(array_column($reports['payment_method_analysis'], 'total')) !!},
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e']
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

    // Trends Chart
    const trendsCtx = document.getElementById('trendsChart').getContext('2d');
    new Chart(trendsCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode(array_column($reports['financial_trends'], 'month')) !!},
            datasets: [
                {
                    label: 'Receita',
                    data: {!! json_encode(array_column($reports['financial_trends'], 'revenue')) !!},
                    borderColor: 'rgba(78, 115, 223, 1)',
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                    tension: 0.3
                },
                {
                    label: 'Custos',
                    data: {!! json_encode(array_column($reports['financial_trends'], 'costs')) !!},
                    borderColor: 'rgba(231, 74, 59, 1)',
                    backgroundColor: 'rgba(231, 74, 59, 0.1)',
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'R$ ' + value.toLocaleString('pt-BR');
                        }
                    }
                }
            }
        }
    });
}

function exportReports() {
    const startDate = $('#start_date').val();
    const endDate = $('#end_date').val();
    const tenantId = $('#tenant_id').val();
    
    let url = '/admin/financial/reports/export?start_date=' + startDate + '&end_date=' + endDate;
    if (tenantId) {
        url += '&tenant_id=' + tenantId;
    }
    
    window.location.href = url;
}

function printReports() {
    window.print();
}

function refreshData() {
    location.reload();
}
</script>
@endsection