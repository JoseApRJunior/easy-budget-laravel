@extends('layouts.admin')

@section('title', 'Admin Dashboard - EasyBudget')

@section('content')
    <div class="container-fluid py-1">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-0 text-gray-800">Admin Dashboard</h1>
                        <p class="text-muted mb-0">Visão geral do sistema EasyBudget MultiTenancy</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <select class="form-select form-select-sm" id="period-selector" style="width: auto;">
                            <option value="week" {{ $currentPeriod === 'week' ? 'selected' : '' }}>Última Semana</option>
                            <option value="month" {{ $currentPeriod === 'month' ? 'selected' : '' }}>Último Mês</option>
                            <option value="quarter" {{ $currentPeriod === 'quarter' ? 'selected' : '' }}>Último Trimestre
                            </option>
                            <option value="year" {{ $currentPeriod === 'year' ? 'selected' : '' }}>Último Ano</option>
                        </select>
                        <x-button 
                            variant="outline-primary" 
                            size="sm" 
                            onclick="refreshDashboard()"
                            icon="bi bi-arrow-clockwise">
                            Atualizar
                        </x-button>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Alerts -->
        @if (count($alerts) > 0)
            <div class="row mb-4">
                <div class="col-12">
                    @foreach ($alerts as $alert)
                        <div class="alert alert-{{ $alert['type'] }} alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            {{ $alert['message'] }}
                            @if (isset($alert['link']))
                                <a href="{{ $alert['link'] }}" class="alert-link ms-2">Ver detalhes</a>
                            @endif
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- System Metrics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total de Tenants
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ number_format($systemMetrics['total_tenants']) }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-building fs-2 text-gray-300"></i>
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-success">
                                <i class="bi bi-arrow-up"></i> {{ $systemMetrics['new_tenants_period'] }} novos neste
                                período
                            </small>
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
                                    Receita Mensal
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    R$ {{ number_format($financialMetrics['monthly_revenue'], 2, ',', '.') }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-currency-dollar fs-2 text-gray-300"></i>
                            </div>
                        </div>
                        <div class="mt-2">
                            @if ($financialMetrics['revenue_growth'] >= 0)
                                <small class="text-success">
                                    <i class="bi bi-arrow-up"></i>
                                    +{{ number_format($financialMetrics['revenue_growth'], 1) }}%
                                </small>
                            @else
                                <small class="text-danger">
                                    <i class="bi bi-arrow-down"></i>
                                    {{ number_format($financialMetrics['revenue_growth'], 1) }}%
                                </small>
                            @endif
                            vs período anterior
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
                                    Clientes Ativos
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ number_format($userMetrics['total_customers']) }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-people fs-2 text-gray-300"></i>
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-info">
                                <i class="bi bi-arrow-up"></i> {{ $userMetrics['new_customers_period'] }} novos neste
                                período
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Prestadores Ativos
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ number_format($userMetrics['total_providers']) }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-person-workspace fs-2 text-gray-300"></i>
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-warning">
                                Retenção: {{ number_format($userMetrics['provider_retention_rate'], 1) }}%
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <!-- Revenue Chart -->
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Evolução da Receita</h6>
                        <div class="dropdown no-arrow">
                            <a class="dropdown-toggle" href="#" role="button" id="revenueDropdown"
                                data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical fs-6 text-gray-400"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end shadow animated--fade-in">
                                <a class="dropdown-item" href="#" onclick="exportChart('revenue')">Exportar</a>
                                <a class="dropdown-item" href="#" onclick="printChart('revenue')">Imprimir</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-area" style="height: 300px;">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Plan Distribution Chart -->
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Distribuição de Planos</h6>
                        <div class="dropdown no-arrow">
                            <a class="dropdown-toggle" href="#" role="button" id="planDropdown"
                                data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical fs-6 text-gray-400"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end shadow animated--fade-in">
                                <a class="dropdown-item" href="#" onclick="exportChart('plan')">Exportar</a>
                                <a class="dropdown-item" href="#" onclick="printChart('plan')">Imprimir</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-pie pt-4 pb-2" style="height: 300px;">
                            <canvas id="planChart"></canvas>
                        </div>
                        <div class="mt-4 text-center small">
                            @foreach ($planMetrics['plan_distribution'] as $plan)
                                <span class="me-2">
                                    <i class="bi bi-circle-fill"
                                        style="color: {{ $loop->index == 0 ? '#4e73df' : ($loop->index == 1 ? '#1cc88a' : ($loop->index == 2 ? '#36b9cc' : '#f6c23e')) }}"></i>
                                    {{ $plan->name }} ({{ $plan->subscriptions_count }})
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Metrics Row -->
        <div class="row mb-4">
            <!-- User Growth Chart -->
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Crescimento de Usuários</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-area" style="height: 300px;">
                            <canvas id="userGrowthChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Health -->
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Saúde do Sistema</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-xs font-weight-bold">Uptime</span>
                                <span class="text-xs font-weight-bold">{{ $systemMetrics['system_uptime'] }}</span>
                            </div>
                            <div class="progress" style="height: 5px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 99%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-xs font-weight-bold">Tamanho do Banco</span>
                                <span class="text-xs font-weight-bold">{{ $systemMetrics['database_size'] }}</span>
                            </div>
                            <div class="progress" style="height: 5px;">
                                <div class="progress-bar bg-info" role="progressbar" style="width: 60%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-xs font-weight-bold">Armazenamento</span>
                                <span class="text-xs font-weight-bold">{{ $systemMetrics['total_storage_used'] }}</span>
                            </div>
                            <div class="progress" style="height: 5px;">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: 45%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Ações Rápidas</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-gear me-1"></i> Gerenciar Planos
                            </a>
                            <a href="{{ route('admin.tenants.index') }}" class="btn btn-outline-success btn-sm">
                                <i class="bi bi-building me-1"></i> Gerenciar Tenants
                            </a>
                            <x-button 
                                href="{{ route('admin.global-settings.index') }}" 
                                variant="outline-info" 
                                size="sm">
                                <i class="bi bi-gear me-1"></i>Configurações
                            </x-button>
                            <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-warning btn-sm">
                                <i class="bi bi-file-earmark-text me-1"></i> Ver Relatórios
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Atividades Recentes do Sistema</h6>
                        <a href="{{ route('admin.audit.logs') }}" class="btn btn-outline-primary btn-sm">
                            Ver todas
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Data/Hora</th>
                                        <th>Usuário</th>
                                        <th>Ação</th>
                                        <th>Descrição</th>
                                        <th>Tipo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentActivities as $activity)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($activity->created_at)->format('d/m/Y H:i') }}
                                            </td>
                                            <td>{{ $activity->user_name ?? 'Sistema' }}</td>
                                            <td>
                                                <span
                                                    class="badge bg-secondary">{{ $activity->action ?? 'sistema' }}</span>
                                            </td>
                                            <td>{{ Str::limit($activity->description, 50) }}</td>
                                            <td>
                                                <span
                                                    class="badge bg-light text-dark">{{ class_basename($activity->model_type ?? 'sistema') }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">
                                                Nenhuma atividade recente encontrada
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            // Revenue Chart
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            const revenueChart = new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: {!! json_encode(array_column($charts['revenue_chart'], 'date')) !!},
                    datasets: [{
                        label: 'Receita Diária',
                        data: {!! json_encode(array_column($charts['revenue_chart'], 'revenue')) !!},
                        borderColor: '#4e73df',
                        backgroundColor: 'rgba(78, 115, 223, 0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
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

            // Plan Distribution Chart
            const planCtx = document.getElementById('planChart').getContext('2d');
            const planChart = new Chart(planCtx, {
                type: 'doughnut',
                data: {
                    labels: {!! json_encode(array_column($charts['plan_distribution_chart'], 'name')) !!},
                    datasets: [{
                        data: {!! json_encode(array_column($charts['plan_distribution_chart'], 'value')) !!},
                        backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // User Growth Chart
            const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
            const userGrowthChart = new Chart(userGrowthCtx, {
                type: 'line',
                data: {
                    labels: {!! json_encode(array_column($charts['user_growth_chart'], 'date')) !!},
                    datasets: [{
                        label: 'Clientes',
                        data: {!! json_encode(array_column($charts['user_growth_chart'], 'customers')) !!},
                        borderColor: '#1cc88a',
                        backgroundColor: 'rgba(28, 200, 138, 0.1)',
                        tension: 0.3,
                        fill: true
                    }, {
                        label: 'Prestadores',
                        data: {!! json_encode(array_column($charts['user_growth_chart'], 'providers')) !!},
                        borderColor: '#4e73df',
                        backgroundColor: 'rgba(78, 115, 223, 0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Period selector change handler
            document.getElementById('period-selector').addEventListener('change', function() {
                const period = this.value;
                window.location.href = '{{ route('admin.dashboard') }}?period=' + period;
            });

            // Refresh dashboard function
            function refreshDashboard() {
                window.location.reload();
            }

            // Export chart function
            function exportChart(chartName) {
                // Implementation for chart export
                console.log('Export chart:', chartName);
            }

            // Print chart function
            function printChart(chartName) {
                // Implementation for chart printing
                console.log('Print chart:', chartName);
            }
        </script>
    @endpush

    @push('styles')
        <style>
            .border-left-primary {
                border-left: 0.25rem solid #4e73df !important;
            }

            .border-left-success {
                border-left: 0.25rem solid #1cc88a !important;
            }

            .border-left-info {
                border-left: 0.25rem solid #36b9cc !important;
            }

            .border-left-warning {
                border-left: 0.25rem solid #f6c23e !important;
            }

            .chart-area {
                position: relative;
                height: 300px;
            }

            .chart-pie {
                position: relative;
                height: 300px;
            }
        </style>
    @endpush
@endsection
