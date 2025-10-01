@extends( 'layouts.app' )

@section( 'title', 'Dashboard' )

@push( 'styles' )
    <style>
        .metric-card {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .metric-value {
            font-variant-numeric: tabular-nums;
            transition: color 0.3s ease;
        }

        .metric-trend {
            transition: all 0.3s ease;
        }

        .trend-up {
            color: rgb(34, 197, 94);
        }

        .trend-down {
            color: rgb(239, 68, 68);
        }

        .trend-stable {
            color: rgb(156, 163, 175);
        }

        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        .loading-overlay::after {
            content: '';
            width: 20px;
            height: 20px;
            border: 2px solid #f3f4f6;
            border-top: 2px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .status-active {
            background-color: rgb(34, 197, 94);
            box-shadow: 0 0 6px rgba(34, 197, 94, 0.4);
        }

        .status-paused {
            background-color: rgb(245, 158, 11);
            box-shadow: 0 0 6px rgba(245, 158, 11, 0.4);
        }

        .status-stopped {
            background-color: rgb(156, 163, 175);
        }

        .dashboard-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 1000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            max-width: 300px;
        }

        .dashboard-notification.show {
            transform: translateX(0);
        }

        .notification-success {
            background-color: rgb(34, 197, 94);
        }

        .notification-error {
            background-color: rgb(239, 68, 68);
        }

        .notification-info {
            background-color: rgb(59, 130, 246);
        }

        .period-filter.active {
            background-color: rgb(59, 130, 246);
            color: white;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .refresh-controls {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 0.875rem;
        }

        .metric-comparison {
            font-size: 0.75rem;
            opacity: 0.8;
            margin-top: 2px;
        }
    </style>
@endpush

@section( 'content' )
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto">
            <!-- Cabeçalho do Dashboard -->
            <div class="mb-8">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">Dashboard Financeiro</h1>
                        <p class="text-gray-600">Visão geral em tempo real do seu controle financeiro</p>
                    </div>

                    <!-- Controles de Refresh -->
                    <div class="flex items-center gap-3 mt-4 lg:mt-0">
                        <div class="refresh-controls">
                            <span class="status-indicator status-active" title="Auto-refresh ativo"></span>
                            <span class="last-update-time">Última atualização: Carregando...</span>
                        </div>

                        <div class="flex gap-2">
                            <button class="btn btn-sm btn-outline-primary btn-refresh" title="Atualizar agora">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary btn-toggle-refresh" title="Pausar auto-refresh">
                                <i class="bi bi-pause"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filtros de Período -->
                <div class="flex gap-2 mt-4 overflow-x-auto">
                    <button class="period-filter btn btn-sm btn-outline-primary active" data-period="month">
                        Este Mês
                    </button>
                    <button class="period-filter btn btn-sm btn-outline-primary" data-period="week">
                        Esta Semana
                    </button>
                    <button class="period-filter btn btn-sm btn-outline-primary" data-period="today">
                        Hoje
                    </button>
                    <button class="period-filter btn btn-sm btn-outline-primary" data-period="year">
                        Este Ano
                    </button>
                </div>
            </div>

            <!-- Cards de Métricas -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Métrica 1: Receita Total -->
                <div class="metric-card bg-white rounded-lg shadow-md p-6 relative">
                    <div class="loading-overlay"></div>
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600">Receita Total</p>
                            <p class="metric-value text-2xl font-bold text-green-600 metric-receita-total">
                                R$ 0,00
                            </p>
                            <div class="metric-trend metric-comparison">
                                <span class="text-gray-500">vs período anterior</span>
                            </div>
                        </div>
                        <div class="bg-green-100 rounded-full p-3">
                            <i class="bi bi-graph-up text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Métrica 2: Despesas Totais -->
                <div class="metric-card bg-white rounded-lg shadow-md p-6 relative">
                    <div class="loading-overlay"></div>
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600">Despesas Totais</p>
                            <p class="metric-value text-2xl font-bold text-red-600 metric-despesas-totais">
                                R$ 0,00
                            </p>
                            <div class="metric-trend metric-comparison">
                                <span class="text-gray-500">vs período anterior</span>
                            </div>
                        </div>
                        <div class="bg-red-100 rounded-full p-3">
                            <i class="bi bi-graph-down text-red-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Métrica 3: Saldo Atual -->
                <div class="metric-card bg-white rounded-lg shadow-md p-6 relative">
                    <div class="loading-overlay"></div>
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600">Saldo Atual</p>
                            <p class="metric-value text-2xl font-bold text-blue-600 metric-saldo-atual">
                                R$ 0,00
                            </p>
                            <div class="metric-comparison">
                                <span class="text-gray-500">da receita</span>
                            </div>
                        </div>
                        <div class="bg-blue-100 rounded-full p-3">
                            <i class="bi bi-wallet text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Métrica 4: Transações Hoje -->
                <div class="metric-card bg-white rounded-lg shadow-md p-6 relative">
                    <div class="loading-overlay"></div>
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600">Transações Hoje</p>
                            <p class="metric-value text-2xl font-bold text-purple-600 metric-transacoes-hoje">
                                0
                            </p>
                            <div class="metric-comparison">
                                <span class="text-gray-500">Atualizado em tempo real</span>
                            </div>
                        </div>
                        <div class="bg-purple-100 rounded-full p-3">
                            <i class="bi bi-receipt text-purple-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos e Análises -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Gráfico de Receitas vs Despesas -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Receitas vs Despesas (30 dias)</h3>
                        <div class="flex gap-2">
                            <button class="btn btn-sm btn-outline-primary"
                                onclick="window.dashboardManager?.refreshCharts()">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="receitaDespesaChart"></canvas>
                    </div>
                </div>

                <!-- Gráfico de Pizza - Distribuição por Categoria -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Distribuição por Categoria</h3>
                        <div class="flex gap-2">
                            <button class="btn btn-sm btn-outline-primary"
                                onclick="window.dashboardManager?.refreshCharts()">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="categoriasChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Gráfico de Barras - Comparativo Mensal -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Comparativo Mensal (Receitas vs Despesas)</h3>
                    <div class="flex gap-2">
                        <button class="btn btn-sm btn-outline-primary" onclick="window.dashboardManager?.refreshCharts()">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="mensalChart"></canvas>
                </div>
            </div>

            <!-- Transações Recentes e Ações Rápidas -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Transações Recentes -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Transações Recentes</h3>
                    <div class="space-y-3 max-h-64 overflow-y-auto">
                        <div class="text-center py-8">
                            <i class="bi bi-receipt-cutoff text-gray-300 text-3xl mb-2"></i>
                            <p class="text-gray-500">Nenhuma transação recente</p>
                            <p class="text-xs text-gray-400">As transações aparecerão aqui conforme forem registradas</p>
                        </div>
                    </div>
                </div>

                <!-- Ações Rápidas -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Ações Rápidas</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <button class="btn btn-success w-full"
                            onclick="alert('Funcionalidade será implementada na próxima fase')">
                            <i class="bi bi-plus-circle mr-2"></i>Nova Receita
                        </button>
                        <button class="btn btn-danger w-full"
                            onclick="alert('Funcionalidade será implementada na próxima fase')">
                            <i class="bi bi-dash-circle mr-2"></i>Nova Despesa
                        </button>
                        <button class="btn btn-info w-full"
                            onclick="alert('Funcionalidade será implementada na próxima fase')">
                            <i class="bi bi-bar-chart mr-2"></i>Relatórios
                        </button>
                        <button class="btn btn-secondary w-full" onclick="window.location.href='/settings'">
                            <i class="bi bi-gear mr-2"></i>Configurações
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push( 'scripts' )
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Dados iniciais para gráficos -->
    <script>
        window.DASHBOARD_CHARTS = {
            'receita_despesa': {
                'labels': [],
                'datasets': []
            },
            'categorias': {
                'labels': [],
                'datasets': []
            },
            'mensal': {
                'labels': [],
                'datasets': []
            }
        };
    </script>

    <!-- Dashboard JavaScript -->
    <script src="{{ asset( 'js/dashboard.js' ) }}"></script>
@endpush
