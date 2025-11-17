@extends('layouts.admin')

@section('title', 'Controle Financeiro - Admin')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0 text-gray-800">Controle Financeiro</h1>
            <p class="text-muted">Monitoramento detalhado dos custos e receitas dos provedores</p>
        </div>
    </div>

    <!-- Alertas de Orçamento -->
    @if(count($budgetAlerts) > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-left-warning shadow">
                <div class="card-body">
                    <h5 class="card-title text-warning">
                        <i class="fas fa-exclamation-triangle"></i> Alertas de Orçamento
                    </h5>
                    <div class="list-group list-group-flush">
                        @foreach($budgetAlerts as $alert)
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $alert['provider_name'] }}</strong>
                                <br>
                                <small class="text-muted">{{ $alert['message'] }}</small>
                            </div>
                            <div class="text-right">
                                <span class="badge badge-{{ $alert['severity'] == 'critical' ? 'danger' : 'warning' }}">
                                    {{ round($alert['percentage_used']) }}%
                                </span>
                                <br>
                                <small class="text-muted">
                                    R$ {{ number_format($alert['current_spending'], 2, ',', '.') }} / 
                                    R$ {{ number_format($alert['budget_limit'], 2, ',', '.') }}
                                </small>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Visão Geral Financeira -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Receita Total
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                R$ {{ number_format($financialOverview['total_revenue'], 2, ',', '.') }}
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
                                R$ {{ number_format($financialOverview['total_costs'], 2, ',', '.') }}
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
                                R$ {{ number_format($financialOverview['net_profit'], 2, ',', '.') }}
                            </div>
                            <div class="text-xs text-success">
                                {{ number_format($financialOverview['profit_margin'], 1, ',', '.') }}% margem
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
                                Provedores Ativos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $financialOverview['active_providers'] }}
                            </div>
                            <div class="text-xs text-info">
                                R$ {{ number_format($financialOverview['avg_revenue_per_provider'], 2, ',', '.') }} média
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-building fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ações Rápidas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Ações Rápidas</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <a href="{{ route('admin.financial.reports') }}" class="btn btn-primary btn-block">
                                <i class="fas fa-chart-bar"></i> Relatórios Detalhados
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-success btn-block" onclick="exportReports()">
                                <i class="fas fa-download"></i> Exportar Relatórios
                            </button>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-warning btn-block" onclick="refreshBudgetAlerts()">
                                <i class="fas fa-bell"></i> Atualizar Alertas
                            </button>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="{{ route('admin.enterprises.index') }}" class="btn btn-info btn-block">
                                <i class="fas fa-building"></i> Ver Empresas
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Provedores -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Desempenho dos Provedores</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="providersTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Empresa</th>
                                    <th>Receita Mensal</th>
                                    <th>Custos</th>
                                    <th>Lucro</th>
                                    <th>Margem</th>
                                    <th>Clientes</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        <i class="fas fa-spinner fa-spin"></i> Carregando dados dos provedores...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    loadProvidersData();
    
    // Refresh budget alerts every 30 seconds
    setInterval(refreshBudgetAlerts, 30000);
});

function loadProvidersData() {
    $.ajax({
        url: '{{ route("admin.enterprises.data") }}',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                updateProvidersTable(response.enterprises);
            }
        },
        error: function() {
            $('#providersTable tbody').html(
                '<tr><td colspan="7" class="text-center text-danger">Erro ao carregar dados</td></tr>'
            );
        }
    });
}

function updateProvidersTable(enterprises) {
    let tbody = '';
    
    if (enterprises.data && enterprises.data.length > 0) {
        enterprises.data.forEach(function(enterprise) {
            // Get financial data for each enterprise
            $.ajax({
                url: '/admin/enterprises/' + enterprise.id + '/financial-data',
                method: 'GET',
                async: false,
                success: function(financialResponse) {
                    if (financialResponse.success) {
                        const financial = financialResponse.data;
                        const profit = financial.monthly_revenue - financial.monthly_costs;
                        
                        tbody += `
                            <tr>
                                <td>
                                    <strong>${enterprise.name}</strong>
                                    <br><small class="text-muted">${enterprise.document || 'N/A'}</small>
                                </td>
                                <td class="text-success">
                                    <strong>R$ ${formatCurrency(financial.monthly_revenue || 0)}</strong>
                                </td>
                                <td class="text-danger">
                                    R$ ${formatCurrency(financial.monthly_costs || 0)}
                                </td>
                                <td class="${profit >= 0 ? 'text-success' : 'text-danger'}">
                                    <strong>R$ ${formatCurrency(profit || 0)}</strong>
                                </td>
                                <td>
                                    <span class="badge badge-${getMarginColor(financial.profit_margin)}">
                                        ${formatPercentage(financial.profit_margin || 0)}%
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-info">${financial.customer_count || 0}</span>
                                </td>
                                <td>
                                    <a href="/admin/financial/providers/${enterprise.id}/details" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-chart-line"></i> Detalhes
                                    </a>
                                </td>
                            </tr>
                        `;
                    }
                },
                error: function() {
                    // Fallback with default values
                    tbody += `
                        <tr>
                            <td>
                                <strong>${enterprise.name}</strong>
                                <br><small class="text-muted">${enterprise.document || 'N/A'}</small>
                            </td>
                            <td class="text-success">R$ 0,00</td>
                            <td class="text-danger">R$ 0,00</td>
                            <td class="text-muted">R$ 0,00</td>
                            <td><span class="badge badge-secondary">0%</span></td>
                            <td><span class="badge badge-info">0</span></td>
                            <td>
                                <a href="/admin/financial/providers/${enterprise.id}/details" 
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-chart-line"></i> Detalhes
                                </a>
                            </td>
                        </tr>
                    `;
                }
            });
        });
    } else {
        tbody = '<tr><td colspan="7" class="text-center text-muted">Nenhum provedor encontrado</td></tr>';
    }
    
    $('#providersTable tbody').html(tbody);
}

function formatCurrency(value) {
    return parseFloat(value).toFixed(2).replace('.', ',');
}

function formatPercentage(value) {
    return parseFloat(value).toFixed(1);
}

function getMarginColor(margin) {
    if (margin >= 20) return 'success';
    if (margin >= 10) return 'warning';
    return 'danger';
}

function exportReports() {
    const startDate = prompt('Data inicial (YYYY-MM-DD):', new Date().toISOString().slice(0, 7) + '-01');
    const endDate = prompt('Data final (YYYY-MM-DD):', new Date().toISOString().slice(0, 10));
    
    if (startDate && endDate) {
        window.location.href = `/admin/financial/reports/export?start_date=${startDate}&end_date=${endDate}`;
    }
}

function refreshBudgetAlerts() {
    $.ajax({
        url: '{{ route("admin.financial.budget-alerts") }}',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                // Reload page to update alerts
                location.reload();
            }
        },
        error: function() {
            alert('Erro ao atualizar alertas');
        }
    });
}
</script>
@endsection