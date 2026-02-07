@extends('layouts.app')

@section('title', 'Relatório de Analytics')

@section('content')
    <div class="container-fluid py-4">
        <x-layout.page-header
            title="Relatório de Analytics"
            icon="pie-chart"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Relatórios' => route('provider.reports.index'),
                'Analytics' => '#'
            ]">
            <div class="d-flex gap-2">
                <x-ui.button type="button" variant="secondary" outline size="sm" icon="arrow-clockwise" label="Atualizar" onclick="refreshData()" />
                <x-ui.button type="button" variant="success" size="sm" icon="file-earmark-spreadsheet" label="Exportar" onclick="exportAnalytics()" />
                <x-ui.button type="link" :href="route('provider.reports.index')" variant="secondary" size="sm" icon="arrow-left" label="Voltar" />
            </div>
        </x-layout.page-header>

        <!-- Cards de Métricas Principais -->
        <div class="row mb-4">
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Total de Vendas</h6>
                                <h2 class="mb-0">R$ 127.350</h2>
                                <small>+12.5% vs mês anterior</small>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-chart-line fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Novos Clientes</h6>
                                <h2 class="mb-0">247</h2>
                                <small>+8.3% vs mês anterior</small>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3">
                <div class="card bg-info text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Taxa de Conversão</h6>
                                <h2 class="mb-0">23.4%</h2>
                                <small>+2.1% vs mês anterior</small>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-percentage fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-3">
                <div class="card bg-warning text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Ticket Médio</h6>
                                <h2 class="mb-0">R$ 515</h2>
                                <small>+5.7% vs mês anterior</small>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-dollar-sign fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos de Análise -->
        <div class="row mb-4">
            <!-- Gráfico de Vendas por Período -->
            <div class="col-xl-8 col-lg-7 col-md-12 mb-4">
                <x-ui.card>
                    <x-slot:header>
                        <h5 class="mb-0">
                            <i class="fas fa-chart-area"></i> Vendas por Período
                        </h5>
                    </x-slot:header>
                    <canvas id="salesChart" width="400" height="200"></canvas>
                </x-ui.card>
            </div>

            <!-- Top Produtos/Serviços -->
            <div class="col-xl-4 col-lg-5 col-md-12 mb-4">
                <x-ui.card>
                    <x-slot:header>
                        <h5 class="mb-0">
                            <i class="fas fa-star"></i> Top Produtos/Serviços
                        </h5>
                    </x-slot:header>
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Consultoria em TI</h6>
                                <small class="text-muted">R$ 45.200</small>
                            </div>
                            <span class="badge bg-primary rounded-pill">35%</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Desenvolvimento Web</h6>
                                <small class="text-muted">R$ 32.800</small>
                            </div>
                            <span class="badge bg-primary rounded-pill">25%</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Manutenção de Sistemas</h6>
                                <small class="text-muted">R$ 28.600</small>
                            </div>
                            <span class="badge bg-primary rounded-pill">22%</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Suporte Técnico</h6>
                                <small class="text-muted">R$ 20.750</small>
                            </div>
                            <span class="badge bg-primary rounded-pill">18%</span>
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </div>

        <!-- Análise de Performance -->
        <div class="row mb-4">
            <!-- Performance por Canal -->
            <div class="col-xl-6 col-lg-6 col-md-12 mb-4">
                <x-ui.card>
                    <x-slot:header>
                        <h5 class="mb-0">
                            <i class="fas fa-chart-pie"></i> Performance por Canal
                        </h5>
                    </x-slot:header>
                    <canvas id="channelChart" width="400" height="200"></canvas>
                </x-ui.card>
            </div>

            <!-- Análise de Retenção -->
            <div class="col-xl-6 col-lg-6 col-md-12 mb-4">
                <x-ui.card>
                    <x-slot:header>
                        <h5 class="mb-0">
                            <i class="fas fa-user-check"></i> Taxa de Retenção de Clientes
                        </h5>
                    </x-slot:header>
                    <div class="row text-center">
                        <div class="col-4">
                            <h4 class="text-success">94.2%</h4>
                            <small class="text-muted">Retenção 30 dias</small>
                        </div>
                        <div class="col-4">
                            <h4 class="text-info">87.5%</h4>
                            <small class="text-muted">Retenção 90 dias</small>
                        </div>
                        <div class="col-4">
                            <h4 class="text-warning">76.3%</h4>
                            <small class="text-muted">Retenção 180 dias</small>
                        </div>
                    </div>
                    <div class="progress mt-3">
                        <div class="progress-bar bg-success" style="width: 94.2%"></div>
                        <div class="progress-bar bg-info" style="width: 87.5%"></div>
                        <div class="progress-bar bg-warning" style="width: 76.3%"></div>
                    </div>
                </x-ui.card>
            </div>
        </div>

        <!-- Tabela de Insights -->
        <x-ui.card>
            <x-slot:header>
                <h5 class="mb-0">
                    <i class="fas fa-lightbulb"></i> Insights e Recomendações
                </h5>
            </x-slot:header>
            <div class="row">
                <div class="col-xl-4 col-lg-6 col-md-12 mb-3">
                    <div class="alert alert-success" role="alert">
                        <h6><i class="fas fa-check-circle"></i> Oportunidade Identificada</h6>
                        <p class="mb-0">Clientes que compraram "Consultoria em TI" têm 40% mais chance de contratar
                            "Manutenção de Sistemas".</p>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-6 col-md-12 mb-3">
                    <div class="alert alert-info" role="alert">
                        <h6><i class="fas fa-info-circle"></i> Tendência Positiva</h6>
                        <p class="mb-0">Vendas online cresceram 25% este mês. Considere investir mais em marketing
                            digital.</p>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-6 col-md-12 mb-3">
                    <div class="alert alert-warning" role="alert">
                        <h6><i class="fas fa-exclamation-triangle"></i> Atenção Necessária</h6>
                        <p class="mb-0">Taxa de conversão do canal "Redes Sociais" está 15% abaixo da média. Revisar
                            estratégia.</p>
                    </div>
                </div>
            </div>
        </x-ui.card>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Dados simulados para demonstração
        const salesData = {
            labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
            datasets: [{
                label: 'Vendas (R$)',
                data: [85000, 92000, 78000, 105000, 118000, 127350],
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }]
        };

        const channelData = {
            labels: ['Site', 'Redes Sociais', 'Indicações', 'Email', 'Outros'],
            datasets: [{
                data: [35, 25, 20, 15, 5],
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF'
                ]
            }]
        };

        // Configuração dos gráficos
        const salesConfig = {
            type: 'line',
            data: salesData,
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Evolução das Vendas nos Últimos 6 Meses'
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
        };

        const channelConfig = {
            type: 'doughnut',
            data: channelData,
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Distribuição de Vendas por Canal'
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        };

        // Inicializar gráficos quando a página carregar
        document.addEventListener('DOMContentLoaded', function() {
            const salesCtx = document.getElementById('salesChart').getContext('2d');
            const channelCtx = document.getElementById('channelChart').getContext('2d');

            new Chart(salesCtx, salesConfig);
            new Chart(channelCtx, channelConfig);
        });

        // Funções de controle
        function refreshData() {
            location.reload();
        }

        function exportAnalytics() {
            alert('Funcionalidade de exportação será implementada em breve.');
        }

        // Atualizar dados automaticamente a cada 5 minutos
        setInterval(function() {
            console.log('Atualizando dados de analytics...');
            // Implementar lógica de atualização de dados
        }, 300000);
    </script>
@endpush
