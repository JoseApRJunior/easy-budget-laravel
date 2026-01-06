@extends('layouts.admin')

@section('title', 'Dashboard de Monitoramento')

@section('content')
    <div class="container-fluid py-4">
        <x-page-header
            title="Dashboard de Monitoramento"
            icon="graph-up"
            :breadcrumb-items="[
                'Admin' => url('/admin'),
                'Monitoramento' => '#'
            ]">
            <div class="d-flex gap-2">
                <x-button variant="secondary" outline size="sm" icon="arrow-clockwise" label="Atualizar" onclick="refreshMetrics()" />
                <x-button type="link" href="{{ url('/admin/monitoring/metrics') }}" variant="primary" size="sm" icon="graph-up" label="Métricas Detalhadas" />
            </div>
        </x-page-header>

        <!-- Cards de Resumo -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="bi bi-speedometer2 fs-2 text-primary"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="text-muted small">Tempo Médio de Resposta</div>
                                <div class="fs-4 fw-bold text-primary" id="avg-response-time">
                                    {{ \App\Helpers\CurrencyHelper::format($summary['average_response_time'] ?? 0, 4, false) }}s
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="bi bi-check-circle fs-2 text-success"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="text-muted small">Taxa de Sucesso</div>
                                <div class="fs-4 fw-bold text-success" id="success-rate">
                                    {{ \App\Helpers\CurrencyHelper::format($summary['success_rate'] ?? 100, 2, false) }}%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="bi bi-memory fs-2 text-info"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="text-muted small">Uso Médio de Memória</div>
                                <div class="fs-4 fw-bold text-info" id="avg-memory">
                                    {{ \App\Helpers\CurrencyHelper::format(($summary['average_memory'] ?? 0) / 1024 / 1024, 2, false) }}MB
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="bi bi-play-circle fs-2 text-warning"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="text-muted small">Total de Execuções</div>
                                <div class="fs-4 fw-bold text-warning" id="total-executions">
                                    {{ \App\Helpers\CurrencyHelper::format($summary['total_executions'] ?? 0, 0, false) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status dos Middlewares -->
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-layers me-2"></i>Status dos Middlewares
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Middleware</th>
                                        <th>Status</th>
                                        <th>Taxa de Sucesso</th>
                                        <th>Tempo Médio</th>
                                        <th>Última Execução</th>
                                    </tr>
                                </thead>
                                <tbody id="middleware-status-table">
                                    @forelse ($middleware_status as $middleware)
                                        <tr>
                                            <td>
                                                <strong>{{ $middleware['name'] }}</strong>
                                            </td>
                                            <td>
                                                @if ($middleware['status'] == 'healthy')
                                                    <span class="badge bg-success">Saudável</span>
                                                @elseif ($middleware['status'] == 'warning')
                                                    <span class="badge bg-warning">Atenção</span>
                                                @else
                                                    <span class="badge bg-danger">Crítico</span>
                                                @endif
                                            </td>
                                            <td>{{ $middleware['success_rate'] }}%</td>
                                            <td>{{ \App\Helpers\CurrencyHelper::format($middleware['average_time'], 4, false) }}s</td>
                                            <td>
                                                <small class="text-muted">{{ $middleware['last_execution'] }}</small>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">
                                                Nenhum middleware monitorado ainda
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Alertas Ativos -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-exclamation-triangle me-2"></i>Alertas Ativos
                        </h5>
                    </div>
                    <div class="card-body">
                        @if (empty($alerts))
                            <div class="text-center text-muted py-3">
                                <i class="bi bi-check-circle fs-1 text-success mb-2"></i>
                                <p class="mb-0">Nenhum alerta ativo</p>
                                <small>Sistema funcionando normalmente</small>
                            </div>
                        @else
                            @foreach ($alerts as $alert)
                                <div class="alert alert-{{ $alert['type'] }} alert-dismissible fade show" role="alert">
                                    <strong>{{ $alert['title'] }}</strong><br>
                                    <small>{{ $alert['message'] }}</small>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>

                <!-- Tendências de Performance -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-graph-up-arrow me-2"></i>Tendências
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span>Tempo de Resposta</span>
                            <span
                                class="badge bg-{{ $performance_trends['response_time_trend'] == 'stable' ? 'success' : ($performance_trends['response_time_trend'] == 'increasing' ? 'warning' : 'danger') }}">
                                @if ($performance_trends['response_time_trend'] == 'stable')
                                    <i class="bi bi-dash"></i> Estável
                                @elseif ($performance_trends['response_time_trend'] == 'increasing')
                                    <i class="bi bi-arrow-up"></i> Aumentando
                                @else
                                    <i class="bi bi-arrow-down"></i> Diminuindo
                                @endif
                            </span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span>Uso de Memória</span>
                            <span
                                class="badge bg-{{ $performance_trends['memory_usage_trend'] == 'stable' ? 'success' : ($performance_trends['memory_usage_trend'] == 'increasing' ? 'warning' : 'danger') }}">
                                @if ($performance_trends['memory_usage_trend'] == 'stable')
                                    <i class="bi bi-dash"></i> Estável
                                @elseif ($performance_trends['memory_usage_trend'] == 'increasing')
                                    <i class="bi bi-arrow-up"></i> Aumentando
                                @else
                                    <i class="bi bi-arrow-down"></i> Diminuindo
                                @endif
                            </span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <span>Taxa de Erro</span>
                            <span
                                class="badge bg-{{ $performance_trends['error_rate_trend'] == 'stable' ? 'success' : ($performance_trends['error_rate_trend'] == 'increasing' ? 'danger' : 'success') }}">
                                @if ($performance_trends['error_rate_trend'] == 'stable')
                                    <i class="bi bi-dash"></i> Estável
                                @elseif ($performance_trends['error_rate_trend'] == 'increasing')
                                    <i class="bi bi-arrow-up"></i> Aumentando
                                @else
                                    <i class="bi bi-arrow-down"></i> Diminuindo
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfico de Performance (placeholder para futuro) -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-bar-chart me-2"></i>Gráfico de Performance
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-graph-up fs-1 mb-3"></i>
                            <h6>Gráficos Interativos</h6>
                            <p class="mb-0">Funcionalidade será implementada em versão futura</p>
                            <small>Incluirá gráficos de linha temporal, distribuição de performance e análise
                                comparativa</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        /**
         * Atualiza as métricas do dashboard via AJAX.
         */
        function refreshMetrics() {
            const button = document.querySelector('[onclick="refreshMetrics()"]');
            const originalText = button.innerHTML;

            // Mostra loading
            button.innerHTML = '<i class="bi bi-arrow-clockwise spin me-1"></i>Atualizando...';
            button.disabled = true;

            fetch('{{ url('/admin/monitoring/api/metrics') }}')
                .then(response => response.json())
                .then(data => {
                    updateDashboardMetrics(data);
                })
                .catch(error => {
                    console.error('Erro ao atualizar métricas:', error);
                    showAlert('Erro ao atualizar métricas', 'danger');
                })
                .finally(() => {
                    // Restaura botão
                    button.innerHTML = originalText;
                    button.disabled = false;
                });
        }

        /**
         * Atualiza os elementos do dashboard com novas métricas.
         */
        function updateDashboardMetrics(data) {
            // Atualiza cards de resumo
            if (data.summary) {
                const avgTime = document.getElementById('avg-response-time');
                const successRate = document.getElementById('success-rate');
                const avgMemory = document.getElementById('avg-memory');
                const totalExec = document.getElementById('total-executions');

                if (avgTime) avgTime.textContent = (data.summary.average_response_time || 0).toFixed(4) + 's';
                if (successRate) successRate.textContent = (data.summary.success_rate || 100).toFixed(2) + '%';
                if (avgMemory) avgMemory.textContent = ((data.summary.average_memory || 0) / 1024 / 1024).toFixed(2) + 'MB';
                if (totalExec) totalExec.textContent = (data.summary.total_executions || 0).toLocaleString();
            }

            showAlert('Métricas atualizadas com sucesso', 'success');
        }

        /**
         * Mostra alerta temporário.
         */
        function showAlert(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      `;

            document.body.appendChild(alertDiv);

            // Remove automaticamente após 3 segundos
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 3000);
        }

        // Atualização automática a cada 30 segundos
        setInterval(refreshMetrics, 30000);

        // CSS para animação de loading
        const style = document.createElement('style');
        style.textContent = `
      .spin {
        animation: spin 1s linear infinite;
      }

      @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
      }

      .hover-shadow:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        transition: box-shadow 0.15s ease-in-out;
      }
    `;
        document.head.appendChild(style);
    </script>
@endsection
