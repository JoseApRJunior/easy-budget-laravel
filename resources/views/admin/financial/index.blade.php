@extends('layouts.admin')

@section('title', 'Controle Financeiro - Admin')

@section('content')
    <div class="container-fluid py-1">
        <!-- Cabeçalho Administrativo -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Controle Financeiro</li>
                    </ol>
                </nav>
                <h1 class="h4">Controle Financeiro</h1>
                <p class="text-muted">Monitoramento detalhado dos custos e receitas dos provedores</p>
            </div>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-secondary" onclick="exportReports()">
                    <i class="fas fa-download"></i> Exportar Relatórios
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="refreshBudgetAlerts()">
                    <i class="fas fa-bell"></i> Atualizar Alertas
                </button>
                <a href="{{ route('admin.enterprises.index') }}" class="btn btn-primary">
                    <i class="fas fa-building"></i> Ver Empresas
                </a>
            </div>
        </div>

        <!-- Alertas de Orçamento -->
        @if (isset($budgetAlerts) && count($budgetAlerts) > 0)
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-left-warning shadow">
                        <div class="card-body">
                            <h5 class="card-title text-warning">
                                <i class="fas fa-exclamation-triangle"></i> Alertas de Orçamento
                            </h5>
                            <div class="list-group list-group-flush">
                                @foreach ($budgetAlerts as $alert)
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>{{ $alert['provider_name'] }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $alert['message'] }}</small>
                                        </div>
                                        <div class="text-right">
                                            <span
                                                class="badge badge-{{ $alert['severity'] == 'critical' ? 'danger' : 'warning' }}">
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
                                    R$ {{ number_format($financialOverview['total_revenue'] ?? 0, 2, ',', '.') }}
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
                                    R$ {{ number_format($financialOverview['total_costs'] ?? 0, 2, ',', '.') }}
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
                                    R$ {{ number_format($financialOverview['net_profit'] ?? 0, 2, ',', '.') }}
                                </div>
                                <div class="text-xs text-success">
                                    {{ number_format($financialOverview['profit_margin'] ?? 0, 1, ',', '.') }}% margem
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
                                    {{ $financialOverview['active_providers'] ?? 0 }}
                                </div>
                                <div class="text-xs text-info">
                                    R$
                                    {{ number_format($financialOverview['avg_revenue_per_provider'] ?? 0, 2, ',', '.') }}
                                    média
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

        <!-- Card de Filtros (SEPARADO) -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control" name="status" id="status">
                                    <option value="">Todos os Status</option>
                                    <option value="active">Ativo</option>
                                    <option value="inactive">Inativo</option>
                                    <option value="suspended">Suspenso</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="plan">Plano</label>
                                <select class="form-control" name="plan" id="plan">
                                    <option value="">Todos os Planos</option>
                                    <option value="trial">Trial</option>
                                    <option value="basic">Básico</option>
                                    <option value="premium">Premium</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="date_from">Data Início</label>
                                <input type="date" class="form-control" name="date_from" id="date_from"
                                    value="{{ request('date_from') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="date_to">Data Fim</label>
                                <input type="date" class="form-control" name="date_to" id="date_to"
                                    value="{{ request('date_to') }}">
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="search">Buscar</label>
                                <input type="text" class="form-control" name="search" id="search"
                                    placeholder="Buscar por empresa..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="min_revenue">Receita Mínima</label>
                                <input type="number" class="form-control" name="min_revenue" id="min_revenue"
                                    placeholder="0.00" step="0.01" value="{{ request('min_revenue') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Filtrar
                                    </button>
                                    <a href="{{ route('admin.financial.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Limpar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Card Principal -->
        <div class="card">
            <div class="card-body">
                <!-- Ações Rápidas -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-bar me-2"></i>Desempenho dos Provedores
                    </h6>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.financial.reports') }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-chart-bar"></i> Relatórios Detalhados
                        </a>
                    </div>
                </div>

                @if (isset($providersData) && count($providersData) > 0)
                    <!-- Tabela responsiva -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Empresa</th>
                                    <th>Receita Mensal</th>
                                    <th>Custos</th>
                                    <th>Lucro</th>
                                    <th>Margem</th>
                                    <th>Clientes</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($providersData as $provider)
                                    <tr>
                                        <td>
                                            <strong>{{ $provider['name'] }}</strong>
                                            <br><small class="text-muted">{{ $provider['document'] ?? 'N/A' }}</small>
                                        </td>
                                        <td class="text-success">
                                            <strong>R$
                                                {{ number_format($provider['monthly_revenue'] ?? 0, 2, ',', '.') }}</strong>
                                        </td>
                                        <td class="text-danger">
                                            R$ {{ number_format($provider['monthly_costs'] ?? 0, 2, ',', '.') }}
                                        </td>
                                        <td
                                            class="{{ ($provider['profit'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                            <strong>R$ {{ number_format($provider['profit'] ?? 0, 2, ',', '.') }}</strong>
                                        </td>
                                        <td>
                                            @php
                                                $margin = $provider['profit_margin'] ?? 0;
                                                $marginClass = match (true) {
                                                    $margin >= 20 => 'bg-success',
                                                    $margin >= 10 => 'bg-warning',
                                                    default => 'bg-danger',
                                                };
                                            @endphp
                                            <span class="badge {{ $marginClass }}">
                                                {{ number_format($margin, 1) }}%
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $provider['customer_count'] ?? 0 }}</span>
                                        </td>
                                        <td>
                                            @php
                                                $statusClass = match ($provider['status'] ?? 'inactive') {
                                                    'active' => 'bg-success',
                                                    'suspended' => 'bg-danger',
                                                    default => 'bg-warning',
                                                };
                                                $statusText = match ($provider['status'] ?? 'inactive') {
                                                    'active' => 'Ativo',
                                                    'suspended' => 'Suspenso',
                                                    default => 'Inativo',
                                                };
                                            @endphp
                                            <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="/admin/financial/providers/{{ $provider['id'] }}/details"
                                                    class="btn btn-sm btn-outline-primary" title="Ver Detalhes">
                                                    <i class="fas fa-chart-line"></i>
                                                </a>
                                                <a href="{{ route('admin.enterprises.show', $provider['id']) }}"
                                                    class="btn btn-sm btn-outline-info" title="Ver Empresa">
                                                    <i class="fas fa-building"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile view -->
                    <div class="d-md-none">
                        @foreach ($providersData as $provider)
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">{{ $provider['name'] }}</h5>
                                    <p class="card-text">
                                        <strong>Receita:</strong>
                                        <span class="text-success">R$
                                            {{ number_format($provider['monthly_revenue'] ?? 0, 2, ',', '.') }}</span><br>
                                        <strong>Custos:</strong>
                                        <span class="text-danger">R$
                                            {{ number_format($provider['monthly_costs'] ?? 0, 2, ',', '.') }}</span><br>
                                        <strong>Lucro:</strong>
                                        <span
                                            class="{{ ($provider['profit'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                            R$ {{ number_format($provider['profit'] ?? 0, 2, ',', '.') }}
                                        </span><br>
                                        <strong>Margem:</strong>
                                        @php
                                            $margin = $provider['profit_margin'] ?? 0;
                                            $marginClass = match (true) {
                                                $margin >= 20 => 'bg-success',
                                                $margin >= 10 => 'bg-warning',
                                                default => 'bg-danger',
                                            };
                                        @endphp
                                        <span
                                            class="badge {{ $marginClass }}">{{ number_format($margin, 1) }}%</span><br>
                                        <strong>Clientes:</strong>
                                        <span class="badge bg-info">{{ $provider['customer_count'] ?? 0 }}</span><br>
                                        <strong>Status:</strong>
                                        @php
                                            $statusClass = match ($provider['status'] ?? 'inactive') {
                                                'active' => 'bg-success',
                                                'suspended' => 'bg-danger',
                                                default => 'bg-warning',
                                            };
                                            $statusText = match ($provider['status'] ?? 'inactive') {
                                                'active' => 'Ativo',
                                                'suspended' => 'Suspenso',
                                                default => 'Inativo',
                                            };
                                        @endphp
                                        <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                                    </p>
                                    <div class="btn-group w-100" role="group">
                                        <a href="/admin/financial/providers/{{ $provider['id'] }}/details"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-chart-line"></i> Detalhes
                                        </a>
                                        <a href="{{ route('admin.enterprises.show', $provider['id']) }}"
                                            class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-building"></i> Empresa
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <!-- Empty State -->
                    <div class="text-center py-5">
                        <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Nenhum dado financeiro encontrado</h5>
                        <p class="text-muted">Não há dados para exibir com os filtros aplicados.</p>
                        <a href="{{ route('admin.enterprises.index') }}" class="btn btn-primary">
                            <i class="fas fa-building"></i> Ver Empresas
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Auto-refresh budget alerts every 30 seconds if they exist
            @if (isset($budgetAlerts) && count($budgetAlerts) > 0)
                setInterval(refreshBudgetAlerts, 30000);
            @endif
        });

        function exportReports() {
            const startDate = prompt('Data inicial (YYYY-MM-DD):', new Date().toISOString().slice(0, 7) + '-01');
            const endDate = prompt('Data final (YYYY-MM-DD):', new Date().toISOString().slice(0, 10));

            if (startDate && endDate) {
                window.location.href = `/admin/financial/reports/export?start_date=${startDate}&end_date=${endDate}`;
            }
        }

        function refreshBudgetAlerts() {
            $.ajax({
                url: '{{ route('admin.financial.budget-alerts') }}',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        // Reload page to update alerts
                        location.reload();
                    }
                },
                error: function() {
                    console.log('Erro ao atualizar alertas');
                }
            });
        }
    </script>
@endsection
