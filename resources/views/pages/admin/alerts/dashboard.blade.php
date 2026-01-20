<x-app-layout :title="$pageTitle . ' - Easy Budget'">
    <div class="container-fluid py-4">
        <x-layout.page-header
            title="Sistema de Alertas"
            icon="exclamation-triangle"
            :breadcrumb-items="[
                'Admin' => url('/admin'),
                'Alertas' => '#'
            ]">
            <div class="d-flex gap-2">
                <x-ui.button type="button" variant="primary" icon="sync-alt" label="Verificar Agora" onclick="checkAlertsNow()" />
                <x-ui.button type="link" :href="url('/admin/alerts/settings')" variant="secondary" icon="cog" label="Configurações" />
            </div>
        </x-layout.page-header>

        <!-- Cards de Estatísticas -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <x-ui.card class="border-left-danger shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                    Alertas Críticos
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ $alertStats['critical_count'] }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-exclamation-circle fa-2x text-danger"></i>
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <x-ui.card class="border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Avisos
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ $alertStats['warning_count'] }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <x-ui.card class="border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Total Ativos
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ $alertStats['total_active'] }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-bell fa-2x text-info"></i>
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <x-ui.card class="border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Resolvidos Hoje
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ $alertStats['resolved_today'] }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x text-success"></i>
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </div>

        <div class="row">
            <!-- Alertas Ativos -->
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Alertas Ativos</h6>
                        <span class="badge badge-info">{{ count($activeAlerts) }} alertas</span>
                    </div>
                    <div class="card-body">
                        @if (count($activeAlerts) > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered" id="alertsTable">
                                    <thead>
                                        <tr>
                                            <th>Severidade</th>
                                            <th>Middleware</th>
                                            <th>Mensagem</th>
                                            <th>Criado em</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($activeAlerts as $alert)
                                            <tr>
                                                <td>
                                                    @if ($alert['severity'] == 'CRITICAL')
                                                        <span class="badge badge-danger">{{ $alert['severity'] }}</span>
                                                    @elseif ($alert['severity'] == 'WARNING')
                                                        <span class="badge badge-warning">{{ $alert['severity'] }}</span>
                                                    @else
                                                        <span class="badge badge-info">{{ $alert['severity'] }}</span>
                                                    @endif
                                                </td>
                                                <td>{{ $alert['middleware_name'] }}</td>
                                                <td>{{ $alert['message'] }}</td>
                                                <td>{{ \Carbon\Carbon::parse($alert['created_at'])->format('d/m/Y H:i') }}
                                                </td>
                                                <td>
                                                    <x-ui.button variant="success" size="sm" icon="check" label="Resolver"
                                                        onclick="resolveAlert({{ $alert['id'] }})" />
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-1">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h5 class="text-muted">Nenhum alerta ativo</h5>
                                <p class="text-muted">Sistema funcionando normalmente</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Insights IA -->
            <div class="col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-brain me-2"></i>Insights IA
                        </h6>
                    </div>
                    <div class="card-body">
                        @if ($aiInsights)
                            <div class="mb-3">
                                <h6 class="text-primary">Padrões Detectados:</h6>
                                <ul class="list-unstyled">
                                    @foreach ($aiInsights['patterns'] as $insight)
                                        <li class="mb-2">
                                            <i class="fas fa-lightbulb text-warning me-2"></i>
                                            {{ $insight }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-success">Recomendações:</h6>
                                <ul class="list-unstyled">
                                    @foreach ($aiInsights['recommendations'] as $recommendation)
                                        <li class="mb-2">
                                            <i class="fas fa-arrow-right text-success me-2"></i>
                                            {{ $recommendation }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <div class="text-center py-3">
                                <i class="fas fa-chart-line fa-2x text-muted mb-2"></i>
                                <p class="text-muted">Coletando dados para análise...</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Status do Sistema -->
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Status do Sistema</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-sm">Performance Geral</span>
                                <span class="text-sm text-{{ $systemStatus['performance'] >= 95 ? 'success' : ($systemStatus['performance'] >= 90 ? 'warning' : 'danger') }}">{{ \App\Helpers\CurrencyHelper::format($systemStatus['performance'], 1, false) }}%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-{{ $systemStatus['performance'] >= 95 ? 'success' : ($systemStatus['performance'] >= 90 ? 'warning' : 'danger') }}"
                                    style="width: {{ $systemStatus['performance'] }}%"></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-sm">Disponibilidade</span>
                                <span
                                    class="text-sm text-{{ $systemStatus['availability'] >= 99 ? 'success' : 'warning' }}">{{ \App\Helpers\CurrencyHelper::format($systemStatus['availability'], 1, false) }}%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-{{ $systemStatus['availability'] >= 99 ? 'success' : 'warning' }}"
                                    style="width: {{ $systemStatus['availability'] }}%"></div>
                            </div>
                        </div>

                        <div class="text-center mt-3">
                            <span
                                class="badge badge-{{ $systemStatus['status'] == 'Operacional' ? 'success' : 'warning' }}">{{ $systemStatus['status'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Auto-refresh a cada 30 segundos
        setInterval(function() {
            location.reload();
        }, 30000);

        function checkAlertsNow() {
            fetch('{{ url('/admin/alerts/check-now') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Verificação executada com sucesso!', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showAlert('Erro ao executar verificação: ' + data.message, 'error');
                    }
                });
        }

        function resolveAlert(alertId) {
            if (!confirm('Deseja resolver este alerta?')) return;

            fetch('/admin/alerts/resolve/' + alertId, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Alerta resolvido com sucesso!', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showAlert('Erro ao resolver alerta: ' + data.message, 'error');
                    }
                });
        }

        function showAlert(message, type) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `;

            document.querySelector('.container-fluid').insertAdjacentHTML('afterbegin', alertHtml);
        }
    </script>
    @endpush
</x-app-layout>
