<x-app-layout title="Dashboard de Monitoramento">
    <x-layout.page-container>
        <x-layout.page-header
            title="Dashboard de Monitoramento"
            icon="graph-up"
            :breadcrumb-items="[
                'Admin' => url('/admin'),
                'Monitoramento' => '#'
            ]">
            <x-slot:actions>
                <div class="d-flex gap-2">
                    <x-ui.button variant="secondary" outline icon="arrow-clockwise" label="Atualizar" onclick="refreshMetrics()" />
                    <x-ui.button type="link" href="{{ url('/admin/monitoring/metrics') }}" variant="primary" icon="graph-up" label="Métricas Detalhadas" />
                </div>
            </x-slot:actions>
        </x-layout.page-header>

        <!-- Cards de Resumo -->
        <div class="row g-4 mb-4">
            <x-dashboard.stat-card 
                col="col-md-3"
                title="Tempo Médio de Resposta"
                :value="\App\Helpers\CurrencyHelper::format($summary['average_response_time'] ?? 0, 4, false) . 's'"
                icon="speedometer2"
                variant="primary"
                id="avg-response-time"
            />

            <x-dashboard.stat-card 
                col="col-md-3"
                title="Taxa de Sucesso"
                :value="\App\Helpers\CurrencyHelper::format($summary['success_rate'] ?? 100, 2, false) . '%'"
                icon="check-circle"
                variant="success"
                id="success-rate"
            />

            <x-dashboard.stat-card 
                col="col-md-3"
                title="Uso Médio de Memória"
                :value="\App\Helpers\CurrencyHelper::format(($summary['average_memory'] ?? 0) / 1024 / 1024, 2, false) . 'MB'"
                icon="memory"
                variant="info"
                id="avg-memory"
            />

            <x-dashboard.stat-card 
                col="col-md-3"
                title="Total de Execuções"
                :value="\App\Helpers\CurrencyHelper::format($summary['total_executions'] ?? 0, 0, false)"
                icon="play-circle"
                variant="warning"
                id="total-executions"
            />
        </div>

        <!-- Status dos Middlewares -->
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <x-ui.card>
                    <x-slot:header>
                        <h5 class="mb-0 text-primary fw-bold">
                            <i class="bi bi-layers me-2"></i>Status dos Middlewares
                        </h5>
                    </x-slot:header>
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
                </x-ui.card>
            </div>

            <div class="col-lg-4">
                <!-- Alertas Ativos -->
                <x-ui.card class="mb-4">
                    <x-slot:header>
                        <h5 class="mb-0 text-primary fw-bold">
                            <i class="bi bi-exclamation-triangle me-2"></i>Alertas Ativos
                        </h5>
                    </x-slot:header>
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
                </x-ui.card>

                <!-- Tendências de Performance -->
                <x-ui.card>
                    <x-slot:header>
                        <h5 class="mb-0 text-primary fw-bold">
                            <i class="bi bi-graph-up-arrow me-2"></i>Tendências
                        </h5>
                    </x-slot:header>
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
                </x-ui.card>
            </div>
        </div>

        <!-- Gráfico de Performance (placeholder para futuro) -->
        <div class="row">
            <div class="col-12">
                <x-ui.card>
                    <x-slot:header>
                        <h5 class="mb-0 text-primary fw-bold">
                            <i class="bi bi-bar-chart me-2"></i>Gráfico de Performance
                        </h5>
                    </x-slot:header>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-graph-up fs-1 mb-3"></i>
                        <h6>Gráficos Interativos</h6>
                        <p class="mb-0">Funcionalidade será implementada em versão futura</p>
                        <small>Incluirá gráficos de linha temporal, distribuição de performance e análise
                            comparativa</small>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </x-layout.page-container>

    @push('scripts')
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
                const avgTime = document.getElementById('avg-response-time').querySelector('.h3');
                const successRate = document.getElementById('success-rate').querySelector('.h3');
                const avgMemory = document.getElementById('avg-memory').querySelector('.h3');
                const totalExec = document.getElementById('total-executions').querySelector('.h3');

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
    @endpush
</x-app-layout>
