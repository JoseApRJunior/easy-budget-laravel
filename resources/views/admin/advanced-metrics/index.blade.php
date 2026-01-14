@extends('layouts.admin')

@section('title', 'Advanced Metrics Dashboard - EasyBudget Admin')

@section('content')
    <div class="container-fluid py-4">
        <x-layout.page-header
            title="Advanced Metrics Dashboard"
            icon="graph-up-arrow"
            :breadcrumb-items="[
                'Admin' => route('admin.dashboard'),
                'Métricas Avançadas' => '#'
            ]">
            <div class="d-flex gap-2">
                <!-- Date Range Selector -->
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="dateRangeDropdown"
                        data-bs-toggle="dropdown">
                        <i class="bi bi-calendar3"></i>
                        {{ ucfirst(str_replace('days', ' Days', str_replace('months', ' Months', request('range', '30days')))) }}
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['range' => '7days']) }}">Last 7 Days</a></li>
                        <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['range' => '30days']) }}">Last 30 Days</a></li>
                        <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['range' => '90days']) }}">Last 90 Days</a></li>
                        <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['range' => '12months']) }}">Last 12 Months</a></li>
                    </ul>
                </div>

                <!-- Export Options -->
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="exportDropdown"
                        data-bs-toggle="dropdown">
                        <i class="bi bi-download"></i> Export
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item"
                                href="{{ route('admin.metrics.export', ['format' => 'csv'] + request()->query()) }}">
                                <i class="bi bi-file-earmark-text"></i> Export CSV
                            </a></li>
                        <li><a class="dropdown-item"
                                href="{{ route('admin.metrics.export', ['format' => 'json'] + request()->query()) }}">
                                <i class="bi bi-file-earmark-code"></i> Export JSON
                            </a></li>
                    </ul>
                </div>

                <!-- Refresh Button -->
                <button class="btn btn-outline-secondary" id="refreshMetrics" title="Refresh Metrics">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>
        </x-layout.page-header>

        <!-- Card de Filtros (SEPARADO) -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="tenant_filter">Tenant</label>
                                <select class="form-control" name="tenant_filter" id="tenant_filter">
                                    <option value="">Todos os Tenants</option>
                                    <option value="active">Ativos</option>
                                    <option value="trial">Em Trial</option>
                                    <option value="premium">Premium</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="metric_type">Tipo de Métrica</label>
                                <select class="form-control" name="metric_type" id="metric_type">
                                    <option value="">Todas</option>
                                    <option value="revenue">Receita</option>
                                    <option value="users">Usuários</option>
                                    <option value="performance">Performance</option>
                                    <option value="usage">Uso do Sistema</option>
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
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Filtrar
                            </button>
                            <a href="{{ route('admin.advanced-metrics.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Limpar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Critical Alerts -->
        @if (isset($metrics['critical_alerts']) && count($metrics['critical_alerts']) > 0)
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <h5 class="alert-heading">
                            <i class="bi bi-exclamation-triangle-fill"></i> Critical Alerts
                        </h5>
                        @foreach ($metrics['critical_alerts'] as $alert)
                            <div class="d-flex justify-content-between align-items-center py-1">
                                <span>{{ $alert['message'] }}</span>
                                @if (isset($alert['link']))
                                    <a href="{{ $alert['link'] }}" class="btn btn-sm btn-outline-dark">View Details</a>
                                @endif
                            </div>
                        @endforeach
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Key Metrics Cards -->
        <div class="row mb-4">
            <!-- Total Revenue -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Revenue ({{ $dateRange['range'] ?? '30 dias' }})
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    R$ {{ number_format($metrics['total_revenue_period'] ?? 0, 2, ',', '.') }}
                                </div>
                                <div class="text-xs text-muted">
                                    Growth: {{ number_format($metrics['revenue_growth_rate'] ?? 0, 1) }}%
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-currency-dollar fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Tenants -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Active Tenants
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ number_format($metrics['active_tenants'] ?? 0) }}
                                </div>
                                <div class="text-xs text-muted">
                                    {{ number_format($metrics['trial_tenants'] ?? 0) }} in trial
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-building fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Providers -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Active Providers
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ number_format($metrics['active_providers'] ?? 0) }}
                                </div>
                                <div class="text-xs text-muted">
                                    Retention: {{ number_format($metrics['provider_retention_rate'] ?? 0, 1) }}%
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-briefcase fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Health -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    System Health
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ number_format($metrics['system_health_score'] ?? 0, 1) }}%
                                </div>
                                <div class="text-xs text-muted">
                                    Performance: {{ number_format($metrics['performance_score'] ?? 0, 1) }}%
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-heart-pulse fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="row mb-4">
            <!-- Revenue Trend Chart -->
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Revenue Trend</h6>
                    </div>
                    <div class="card-body">
                        @if (isset($charts['revenue_trend']) && count($charts['revenue_trend']) > 0)
                            <canvas id="revenueTrendChart" height="100"></canvas>
                        @else
                            <div class="text-center py-4">
                                <i class="bi bi-graph-up fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Dados de receita não disponíveis para o período selecionado</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Plan Distribution -->
            <div class="col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Plan Distribution</h6>
                    </div>
                    <div class="card-body">
                        @if (isset($charts['plan_distribution']) && count($charts['plan_distribution']) > 0)
                            <canvas id="planDistributionChart" height="200"></canvas>
                        @else
                            <div class="text-center py-4">
                                <i class="bi bi-pie-chart fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Dados de planos não disponíveis</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="row mb-4">
            <!-- User Growth Chart -->
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">User Growth</h6>
                    </div>
                    <div class="card-body">
                        @if (isset($charts['user_growth']) && count($charts['user_growth']) > 0)
                            <canvas id="userGrowthChart" height="100"></canvas>
                        @else
                            <div class="text-center py-4">
                                <i class="bi bi-people fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Dados de crescimento de usuários não disponíveis</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Provider Growth Chart -->
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Provider Growth</h6>
                    </div>
                    <div class="card-body">
                        @if (isset($charts['provider_growth']) && count($charts['provider_growth']) > 0)
                            <canvas id="providerGrowthChart" height="100"></canvas>
                        @else
                            <div class="text-center py-4">
                                <i class="bi bi-building fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Dados de crescimento de provedores não disponíveis</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Metrics Tables -->
        <div class="row mb-4">
            <!-- Top Revenue Providers -->
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Top Revenue Providers</h6>
                    </div>
                    <div class="card-body">
                        @if (isset($metrics['top_revenue_providers']) && count($metrics['top_revenue_providers']) > 0)
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Provider</th>
                                            <th>Revenue</th>
                                            <th>Customers</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($metrics['top_revenue_providers'] as $provider)
                                            <tr>
                                                <td>{{ $provider['name'] }}</td>
                                                <td>R$ {{ number_format($provider['revenue'], 2, ',', '.') }}</td>
                                                <td>{{ number_format($provider['customers_count']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="bi bi-trophy fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Nenhum dado de receita disponível</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Subscription Metrics -->
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Subscription Metrics</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Subscriptions
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ number_format($metrics['total_subscriptions'] ?? 0) }}</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Subscriptions
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ number_format($metrics['active_subscriptions'] ?? 0) }}</div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Churn Rate</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ number_format($metrics['subscription_churn_rate'] ?? 0, 1) }}%</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Avg Value</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">R$
                                    {{ number_format($metrics['avg_subscription_value'] ?? 0, 2, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Metrics -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Content Distribution</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-3 text-center">
                                <div class="border-right">
                                    <div class="h4 font-weight-bold text-primary">
                                        {{ number_format($metrics['total_categories'] ?? 0) }}</div>
                                    <div class="text-xs text-uppercase text-muted">Categories</div>
                                </div>
                            </div>
                            <div class="col-lg-3 text-center">
                                <div class="border-right">
                                    <div class="h4 font-weight-bold text-success">
                                        {{ number_format($metrics['total_activities'] ?? 0) }}</div>
                                    <div class="text-xs text-uppercase text-muted">Activities</div>
                                </div>
                            </div>
                            <div class="col-lg-3 text-center">
                                <div class="border-right">
                                    <div class="h4 font-weight-bold text-info">
                                        {{ number_format($metrics['total_professions'] ?? 0) }}</div>
                                    <div class="text-xs text-uppercase text-muted">Professions</div>
                                </div>
                            </div>
                            <div class="col-lg-3 text-center">
                                <div class="h4 font-weight-bold text-warning">
                                    {{ number_format($metrics['categories_with_activities'] ?? 0) }}</div>
                                <div class="text-xs text-uppercase text-muted">Active Categories</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Real-time Metrics -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Real-time Metrics</h6>
                        <div class="text-xs text-muted">
                            <i class="bi bi-circle-fill text-success"></i> Live
                            <span id="lastUpdate">Last update: just now</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-lg-2 col-md-4 mb-3">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Active Users</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="activeUsers">-</div>
                            </div>
                            <div class="col-lg-2 col-md-4 mb-3">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">New Signups Today
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="newSignups">-</div>
                            </div>
                            <div class="col-lg-2 col-md-4 mb-3">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Revenue Today</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="revenueToday">-</div>
                            </div>
                            <div class="col-lg-2 col-md-4 mb-3">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">System Load</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="systemLoad">-</div>
                            </div>
                            <div class="col-lg-2 col-md-4 mb-3">
                                <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Memory Usage</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="memoryUsage">-</div>
                            </div>
                            <div class="col-lg-2 col-md-4 mb-3">
                                <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">Disk Usage</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="diskUsage">-</div>
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
        // Chart configuration
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        };

        // Revenue Trend Chart
        @if (isset($charts['revenue_trend']) && count($charts['revenue_trend']) > 0)
            const revenueTrendCtx = document.getElementById('revenueTrendChart').getContext('2d');
            new Chart(revenueTrendCtx, {
                type: 'line',
                data: {
                    labels: {!! json_encode(array_column($charts['revenue_trend'], 'date')) !!},
                    datasets: [{
                        label: 'Revenue (R$)',
                        data: {!! json_encode(array_column($charts['revenue_trend'], 'revenue')) !!},
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.4
                    }]
                },
                options: chartOptions
            });
        @endif

        // Plan Distribution Chart
        @if (isset($charts['plan_distribution']) && count($charts['plan_distribution']) > 0)
            const planDistributionCtx = document.getElementById('planDistributionChart').getContext('2d');
            new Chart(planDistributionCtx, {
                type: 'doughnut',
                data: {
                    labels: {!! json_encode(array_column($charts['plan_distribution'], 'name')) !!},
                    datasets: [{
                        data: {!! json_encode(array_column($charts['plan_distribution'], 'count')) !!},
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(255, 205, 86, 0.8)',
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(153, 102, 255, 0.8)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        @endif

        // User Growth Chart
        @if (isset($charts['user_growth']) && count($charts['user_growth']) > 0)
            const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
            new Chart(userGrowthCtx, {
                type: 'line',
                data: {
                    labels: {!! json_encode(array_column($charts['user_growth'], 'date')) !!},
                    datasets: [{
                        label: 'Total Users',
                        data: {!! json_encode(array_column($charts['user_growth'], 'count')) !!},
                        borderColor: 'rgb(54, 162, 235)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        tension: 0.4
                    }]
                },
                options: chartOptions
            });
        @endif

        // Provider Growth Chart
        @if (isset($charts['provider_growth']) && count($charts['provider_growth']) > 0)
            const providerGrowthCtx = document.getElementById('providerGrowthChart').getContext('2d');
            new Chart(providerGrowthCtx, {
                type: 'line',
                data: {
                    labels: {!! json_encode(array_column($charts['provider_growth'], 'date')) !!},
                    datasets: [{
                        label: 'Total Providers',
                        data: {!! json_encode(array_column($charts['provider_growth'], 'count')) !!},
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        tension: 0.4
                    }]
                },
                options: chartOptions
            });
        @endif

        // Real-time metrics update
        function updateRealTimeMetrics() {
            fetch('{{ route('admin.metrics.realtime') }}')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('activeUsers').textContent = data.active_users.toLocaleString();
                    document.getElementById('newSignups').textContent = data.new_signups_today.toLocaleString();
                    document.getElementById('revenueToday').textContent = 'R$ ' + data.revenue_today.toLocaleString(
                        'pt-BR', {
                            minimumFractionDigits: 2
                        });
                    document.getElementById('systemLoad').textContent = data.system_load.toFixed(2) + '%';
                    document.getElementById('memoryUsage').textContent = data.memory_usage.toFixed(1) + '%';
                    document.getElementById('diskUsage').textContent = data.disk_usage.toFixed(1) + '%';
                    document.getElementById('lastUpdate').textContent = 'Last update: ' + new Date()
                    .toLocaleTimeString();
                })
                .catch(error => console.error('Error updating real-time metrics:', error));
        }

        // Refresh button functionality
        document.getElementById('refreshMetrics').addEventListener('click', function() {
            this.innerHTML = '<i class="bi bi-arrow-clockwise bi-spin"></i>';
            this.disabled = true;

            // Reload page after short delay
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        });

        // Update real-time metrics every 30 seconds
        updateRealTimeMetrics();
        setInterval(updateRealTimeMetrics, 30000);
    </script>
@endsection
