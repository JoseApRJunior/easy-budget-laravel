@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="h2 mb-0">
                        <i class="bi bi-robot text-primary me-2"></i>
                        IA Analytics
                    </h1>
                    <div class="d-flex gap-2">
                        <x-ui.button 
                            type="button" 
                            variant="primary" 
                            id="refreshAnalytics"
                            outline
                            icon="bi bi-arrow-clockwise"
                            feature="analytics">
                            Atualizar
                        </x-ui.button>
                        <x-ui.button 
                            type="button" 
                            variant="secondary" 
                            id="exportReport"
                            outline
                            icon="bi bi-download"
                            feature="analytics">
                            Exportar
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Alertas e Insights --}}
        <div class="row mb-4" id="insightsContainer">
            <div class="col-12">
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="bi bi-lightbulb me-2"></i>
                    <strong>Insight da IA:</strong> Analisando seus dados para fornecer as melhores recomendações...
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>

        {{-- Cards Principais --}}
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Faturamento Total
                                    <span class="ms-1 text-muted" data-bs-toggle="tooltip"
                                        title="Somatório das faturas com status 'PAGO' (PAID) no mês corrente">?</span>
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalRevenue">
                                    R$ 0,00
                                </div>
                                <div class="text-xs text-muted">
                                    <i class="bi bi-arrow-up text-success"></i>
                                    <span id="revenueGrowth">+0%</span> este mês
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-currency-dollar fa-2x text-gray-300"></i>
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
                                    Taxa de Conversão
                                    <span class="ms-1 text-muted" data-bs-toggle="tooltip"
                                        title="Orçamentos aprovados / orçamentos criados no mês">?</span>
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="conversionRate">
                                    0%
                                </div>
                                <div class="text-xs text-muted">
                                    <i class="bi bi-arrow-up text-success"></i>
                                    <span id="conversionTrend">+0%</span> vs mês anterior
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-graph-up fa-2x text-gray-300"></i>
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
                                    Clientes Ativos
                                    <span class="ms-1 text-muted" data-bs-toggle="tooltip"
                                        title="Clientes existentes até o fim do mês e com atividade recente">?</span>
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="activeCustomers">
                                    0
                                </div>
                                <div class="text-xs text-muted">
                                    <i class="bi bi-arrow-up text-success"></i>
                                    <span id="customerGrowth">+0</span> novos este mês
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-people fa-2x text-gray-300"></i>
                            </div>
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
                                    Ticket Médio
                                    <span class="ms-1 text-muted" data-bs-toggle="tooltip"
                                        title="Média do valor total dos orçamentos aprovados no mês">?</span>
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="averageTicket">
                                    R$ 0,00
                                </div>
                                <div class="text-xs text-muted">
                                    <i class="bi bi-arrow-up text-success"></i>
                                    <span id="ticketGrowth">+0%</span> vs mês anterior
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-receipt fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Gráficos e Análises --}}
        <div class="row mb-4">
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-graph-up me-2"></i>
                            Tendências de Faturamento
                            <span class="ms-1 text-muted" data-bs-toggle="tooltip"
                                title="Linha do tempo com receita mensal, baseada em faturas pagas">?</span>
                        </h6>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button"
                                id="periodDropdown" data-bs-toggle="dropdown">
                                Últimos 6 meses
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" data-period="3months">Últimos 3 meses</a>
                                </li>
                                <li><a class="dropdown-item" href="#" data-period="6months">Últimos 6 meses</a>
                                </li>
                                <li><a class="dropdown-item" href="#" data-period="12months">Últimos 12 meses</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueChart" height="100"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-pie-chart me-2"></i>
                            Distribuição de Serviços
                            <span class="ms-1 text-muted" data-bs-toggle="tooltip"
                                title="Participação de cada tipo de serviço nas vendas">?</span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <canvas id="servicesChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sugestões da IA --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-lightbulb me-2"></i>
                            Sugestões de Melhorias (IA)
                            <span class="ms-1 text-muted" data-bs-toggle="tooltip"
                                title="Recomendações geradas por análise de dados e regras heurísticas">?</span>
                        </h6>
                        <button class="btn btn-sm btn-outline-primary" id="refreshSuggestions">
                            <i class="bi bi-arrow-clockwise"></i>
                            Novas Sugestões
                        </button>
                    </div>
                    <div class="card-body" id="suggestionsContainer">
                        <div class="text-center py-1">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Carregando sugestões...</span>
                            </div>
                            <p class="mt-2 text-muted">Carregando sugestões inteligentes...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Análises Detalhadas --}}
        <div class="row">
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-people me-2"></i>
                            Análise de Clientes
                            <span class="ms-1 text-muted" data-bs-toggle="tooltip"
                                title="Totais, novos clientes, ativos e taxa de cancelamento (churn)">?</span>
                        </h6>
                    </div>
                    <div class="card-body" id="customerAnalysis">
                        <div class="text-center py-1">
                            <div class="spinner-border text-primary" role="status"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-cash-stack me-2"></i>
                            Saúde Financeira
                            <span class="ms-1 text-muted" data-bs-toggle="tooltip"
                                title="Receitas, despesas, margem de lucro e fluxo de caixa">?</span>
                        </h6>
                    </div>
                    <div class="card-body" id="financialAnalysis">
                        <div class="text-center py-1">
                            <div class="spinner-border text-primary" role="status"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

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

        .card {
            transition: transform 0.2s ease-in-out;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        .suggestion-item {
            border-left: 3px solid #007bff;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }

        .suggestion-item:hover {
            background-color: #e9ecef;
            border-left-color: #0056b3;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Carregar dados iniciais
            loadAnalyticsData();
            loadSuggestions();
            loadCustomerAnalysis();
            loadFinancialAnalysis();

            // Event listeners
            document.getElementById('refreshAnalytics').addEventListener('click', function() {
                loadAnalyticsData();
                loadSuggestions();
                loadCustomerAnalysis();
                loadFinancialAnalysis();
            });

            document.getElementById('refreshSuggestions').addEventListener('click', loadSuggestions);

            // Period dropdown
            document.querySelectorAll('[data-period]').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const period = this.dataset.period;
                    document.getElementById('periodDropdown').textContent = this.textContent;
                    loadTrends(period);
                });
            });

            function loadAnalyticsData() {
                fetch('{{ route('provider.analytics.overview') }}')
                    .then(response => response.json())
                    .then(data => {
                        updateMetrics(data);
                        loadTrends('6months');
                        loadServicesChart();
                    })
                    .catch(error => console.error('Erro ao carregar analytics:', error));
            }

            function updateMetrics(data) {
                document.getElementById('totalRevenue').textContent = formatCurrency(data.revenue?.total || 0);
                document.getElementById('revenueGrowth').textContent = (data.revenue?.growth || 0) + '%';
                document.getElementById('conversionRate').textContent = (data.conversion?.rate || 0) + '%';
                document.getElementById('conversionTrend').textContent = (data.conversion?.trend || 0) + '%';
                document.getElementById('activeCustomers').textContent = data.customers?.active || 0;
                document.getElementById('customerGrowth').textContent = '+' + (data.customers?.new_this_month || 0);
                document.getElementById('averageTicket').textContent = formatCurrency(data.ticket?.average || 0);
                document.getElementById('ticketGrowth').textContent = (data.ticket?.growth || 0) + '%';

                // Load predictions after metrics
                loadPredictions();
            }

            function loadTrends(period = '6months') {
                fetch(`{{ route('provider.analytics.trends') }}?period=${period}`)
                    .then(response => response.json())
                    .then(data => {
                        window.currentTrendsData = data; // Store for combining with predictions
                        updateRevenueChart(data, window.currentPrediction);
                    })
                    .catch(error => console.error('Erro ao carregar tendências:', error));
            }

            function loadPredictions() {
                fetch('{{ route('provider.analytics.predictions') }}')
                    .then(response => response.json())
                    .then(data => {
                        window.currentPrediction = data.next_month_revenue;
                        if(window.currentTrendsData) {
                             updateRevenueChart(window.currentTrendsData, window.currentPrediction);
                        }
                    })
                    .catch(error => console.error('Erro ao carregar previsões:', error));
            }

            function updateRevenueChart(data, prediction = null) {
                const ctx = document.getElementById('revenueChart').getContext('2d');

                let labels = [...(data.labels || [])];
                let values = [...(data.values || [])];
                let forecastValues = new Array(values.length).fill(null); // Empty for historical

                // Add prediction point if available
                if (prediction && prediction.predicted > 0) {
                     // Get current month name from last label or generate next
                     // Simple approach: Add "Próximo Mês"
                     labels.push('Previsão');
                     // Connect the line: last actual value needed?
                     // Chart.js handles gaps.
                     // To make it continuous, last actual point + predicted point
                     forecastValues.push(null); // Spacer

                     // We want the forecast line to start from the last actual point
                     // So we replace the null at the last position of forecastValues with the last actual value
                     forecastValues[forecastValues.length - 1] = values[values.length - 1];

                     values.push(null); // No actual value for next month
                     forecastValues.push(prediction.predicted);
                }

                if (window.revenueChartInstance) {
                    window.revenueChartInstance.destroy();
                }

                window.revenueChartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Faturamento Real',
                            data: values,
                            borderColor: '#4e73df',
                            backgroundColor: 'rgba(78, 115, 223, 0.1)',
                            tension: 0.3,
                            fill: true
                        },
                        {
                            label: 'Previsão (IA)',
                            data: forecastValues,
                            borderColor: '#1cc88a', // Green for forecast
                            backgroundColor: 'rgba(28, 200, 138, 0.1)',
                            borderDash: [5, 5], // Dashed line
                            tension: 0.3,
                            fill: false
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true
                            },
                             tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.y !== null) {
                                            label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed.y);
                                        }
                                        return label;
                                    }
                                }
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
            }

            function loadServicesChart() {
                // Dados mock para demonstração - substituir com dados reais
                const ctx = document.getElementById('servicesChart').getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Manutenção', 'Instalação', 'Consultoria', 'Outros'],
                        datasets: [{
                            data: [40, 30, 20, 10],
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
            }

            function loadSuggestions() {
                fetch('{{ route('provider.analytics.suggestions') }}')
                    .then(response => response.json())
                    .then(data => {
                        updateSuggestions(data);
                    })
                    .catch(error => console.error('Erro ao carregar sugestões:', error));
            }

            function updateSuggestions(data) {
                const container = document.getElementById('suggestionsContainer');
                if (data.suggestions && data.suggestions.length > 0) {
                    container.innerHTML = data.suggestions.map(suggestion => `
                <div class="suggestion-item p-3 mb-3 rounded">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-lightbulb text-warning me-3 mt-1"></i>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">${suggestion.title}</h6>
                            <p class="mb-2 text-muted">${suggestion.description}</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-success">
                                    <i class="bi bi-graph-up"></i>
                                    Impacto: ${suggestion.impact}
                                </small>
                                <button class="btn btn-sm btn-outline-primary" onclick="implementSuggestion('${suggestion.id}')">
                                    <i class="bi bi-check-circle"></i>
                                    Implementar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
                } else {
                    container.innerHTML =
                        '<p class="text-muted text-center">Nenhuma sugestão disponível no momento.</p>';
                }
            }

            function loadCustomerAnalysis() {
                fetch('{{ route('provider.analytics.customers') }}')
                    .then(response => response.json())
                    .then(data => {
                        updateCustomerAnalysis(data);
                    })
                    .catch(error => console.error('Erro ao carregar análise de clientes:', error));
            }

            function updateCustomerAnalysis(data) {
                const container = document.getElementById('customerAnalysis');
                container.innerHTML = `
            <div class="row text-center">
                <div class="col-6 mb-3">
                    <h5 class="text-primary">${data.total_customers || 0}</h5>
                    <small class="text-muted">Total de Clientes</small>
                </div>
                <div class="col-6 mb-3">
                    <h5 class="text-success">${data.active_customers || 0}</h5>
                    <small class="text-muted">Clientes Ativos</small>
                </div>
                <div class="col-6 mb-3">
                    <h5 class="text-info">${data.new_customers_month || 0}</h5>
                    <small class="text-muted">Novos este Mês</small>
                </div>
                <div class="col-6 mb-3">
                    <h5 class="text-warning">${data.churn_rate || 0}%</h5>
                    <small class="text-muted">Taxa de Cancelamento</small>
                </div>
            </div>
            <hr>
                let segmentsHtml = '';
                if(data.segments && Object.keys(data.segments).length > 0) {
                    // Translated map
                    const segmentNames = {
                        'Champions': 'Campeões (VIP)',
                        'Loyal': 'Leais',
                        'Potential Loyalist': 'Potenciais Leais',
                        'New': 'Novos',
                        'At Risk': 'Em Risco',
                        'Lost': 'Perdidos'
                    };

                    segmentsHtml = '<h6 class="mt-3">Segmentação (RFM):</h6><ul class="list-group list-group-flush small">';
                    for (const [segment, count] of Object.entries(data.segments)) {
                        const name = segmentNames[segment] || segment;
                        const percent = ((count / data.total_segmented) * 100).toFixed(0);
                        let badgeClass = 'bg-secondary';
                        if(segment === 'Champions') badgeClass = 'bg-success';
                        if(segment === 'At Risk') badgeClass = 'bg-danger';
                        if(segment === 'Loyal') badgeClass = 'bg-primary';

                        segmentsHtml += `
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                ${name}
                                <span class="badge ${badgeClass} rounded-pill">${count} (${percent}%)</span>
                            </li>
                        `;
                    }
                    segmentsHtml += '</ul>';
                }

                container.innerHTML = `
            <div class="row text-center">
                <div class="col-6 mb-3">
                    <h5 class="text-primary">${data.total_customers || 0}</h5>
                    <small class="text-muted">Total de Clientes</small>
                </div>
                <div class="col-6 mb-3">
                    <h5 class="text-success">${data.active_customers || 0}</h5>
                    <small class="text-muted">Clientes Ativos</small>
                </div>
                <div class="col-6 mb-3">
                    <h5 class="text-info">${data.new_customers_month || 0}</h5>
                    <small class="text-muted">Novos este Mês</small>
                </div>
                <div class="col-6 mb-3">
                    <h5 class="text-warning">${data.churn_rate || 0}%</h5>
                    <small class="text-muted">Taxa de Cancelamento</small>
                </div>
            </div>
            <hr>
            <div class="mt-3">
                <h6>Segmentação Principal:</h6>
                <p class="text-muted small">${data.main_segment || 'Análise em progresso...'}</p>
                ${segmentsHtml}
            </div>
        `;
        `;
            }

            function loadFinancialAnalysis() {
                fetch('{{ route('provider.analytics.financial') }}')
                    .then(response => response.json())
                    .then(data => {
                        updateFinancialAnalysis(data);
                    })
                    .catch(error => console.error('Erro ao carregar análise financeira:', error));
            }

            function updateFinancialAnalysis(data) {
                const container = document.getElementById('financialAnalysis');
                container.innerHTML = `
            <div class="row text-center">
                <div class="col-6 mb-3">
                    <h5 class="text-success">${formatCurrency(data.monthly_revenue || 0)}</h5>
                    <small class="text-muted">Receita Mensal</small>
                </div>
                <div class="col-6 mb-3">
                    <h5 class="text-danger">${formatCurrency(data.monthly_expenses || 0)}</h5>
                    <small class="text-muted">Despesas Mensais</small>
                </div>
                <div class="col-6 mb-3">
                    <h5 class="text-primary">${formatCurrency(data.profit_margin || 0)}</h5>
                    <small class="text-muted">Margem de Lucro</small>
                </div>
                <div class="col-6 mb-3">
                    <h5 class="${data.cash_flow >= 0 ? 'text-success' : 'text-danger'}">
                        ${formatCurrency(data.cash_flow || 0)}
                    </h5>
                    <small class="text-muted">Fluxo de Caixa</small>
                </div>
            </div>
            <hr>
            <div class="mt-3">
                <h6>Saúde Financeira:</h6>
                <div class="progress mb-2">
                    <div class="progress-bar bg-${data.health_score >= 70 ? 'success' : data.health_score >= 40 ? 'warning' : 'danger'}"
                         style="width: ${data.health_score || 0}%"></div>
                </div>
                <small class="text-muted">${data.health_score || 0}% de saúde financeira</small>
            </div>
        `;
            }

            function formatCurrency(value) {
                return new Intl.NumberFormat('pt-BR', {
                    style: 'currency',
                    currency: 'BRL'
                }).format(value);
            }

            window.implementSuggestion = function(suggestionId) {
                alert('Implementação da sugestão ID: ' + suggestionId +
                    ' - Esta funcionalidade será desenvolvida em breve.');
            };
        });
        // Inicializar tooltips do Bootstrap
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function(tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
@endpush
