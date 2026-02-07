<x-app-layout title="Monitoramento do Sistema">
    <div class="container-fluid py-4">
        <x-layout.page-header
            title="Monitoramento do Sistema"
            icon="graph-up"
            :breadcrumb-items="[
                'Admin' => url('/admin'),
                'Monitoramento' => url('/admin/monitoring/dashboard'),
                'Métricas' => '#'
            ]">
            <x-ui.button type="link" :href="url('/admin/monitoring/dashboard')" variant="secondary" icon="arrow-left" label="Voltar" />
        </x-layout.page-header>

                <!-- Navegação por Abas -->
                <ul class="nav nav-tabs mb-4" id="monitoringTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" href="{{ url('/admin/monitoring/dashboard') }}">
                            <i class="bi bi-speedometer2 me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" href="{{ url('/admin/monitoring/metrics') }}">
                            <i class="bi bi-graph-up me-1"></i>Métricas
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" href="{{ url('/admin/monitoring/middleware') }}">
                            <i class="bi bi-lightning me-1"></i>Middlewares
                        </a>
                    </li>
                </ul>

                <!-- Conteúdo das Abas -->
                <div class="tab-content" id="monitoringTabsContent">

                    <!-- Aba Métricas -->
                    <div class="tab-pane fade show active" id="metrics" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5>Métricas Detalhadas</h5>
                            <button class="btn btn-outline-secondary btn-sm" onclick="exportMetrics()">
                                <i class="bi bi-download me-1"></i>Exportar
                            </button>
                        </div>

                        <!-- Resumo Geral -->
                        <div class="row g-4 mb-4">
                            <div class="col-md-3">
                                <div class="card border-0 shadow-sm text-center">
                                    <div class="card-body">
                                        <i class="bi bi-layers fs-1 text-primary mb-2"></i>
                                        <h5 class="card-title">Total de Middlewares</h5>
                                        <h3 class="text-primary">{{ count($middlewares) }}</h3>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="card border-0 shadow-sm text-center">
                                    <div class="card-body">
                                        <i class="bi bi-play-circle fs-1 text-success mb-2"></i>
                                        <h5 class="card-title">Total de Execuções</h5>
                                        <h3 class="text-success">{{ \App\Helpers\CurrencyHelper::format($total_executions, 0, false) }}</h3>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="card border-0 shadow-sm text-center">
                                    <div class="card-body">
                                        <i class="bi bi-speedometer2 fs-1 text-warning mb-2"></i>
                                        <h5 class="card-title">Tempo Médio</h5>
                                        <h3 class="text-warning">{{ \App\Helpers\CurrencyHelper::format($average_response_time, 4, false) }}s</h3>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="card border-0 shadow-sm text-center">
                                    <div class="card-body">
                                        <i class="bi bi-check-circle fs-1 text-info mb-2"></i>
                                        <h5 class="card-title">Taxa de Sucesso</h5>
                                        <h3 class="text-info">{{ \App\Helpers\CurrencyHelper::format($success_rate, 2, false) }}%</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabela Detalhada de Métricas -->
                        <div class="card border-0 shadow-sm">
                            <div class="card-header border-bottom">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-table me-2"></i>Métricas por Middleware
                                    </h5>
                                    <div class="d-flex gap-2">
                                        <input type="text" class="form-control form-control-sm"
                                            placeholder="Filtrar middlewares..." id="middleware-filter"
                                            style="width: 200px;">
                                        <select class="form-select form-select-sm" id="sort-by" style="width: 150px;">
                                            <option value="name">Nome</option>
                                            <option value="executions">Execuções</option>
                                            <option value="average_time">Tempo Médio</option>
                                            <option value="success_rate">Taxa de Sucesso</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped mb-0" id="metrics-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-center" style="width: 50px;">#</th>
                                                <th>Middleware</th>
                                                <th class="text-center">Execuções</th>
                                                <th class="text-center">Tempo Médio</th>
                                                <th class="text-center">Memória Média</th>
                                                <th class="text-center">Taxa de Sucesso</th>
                                                <th class="text-center">Status</th>
                                                <th class="text-center">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($middlewares as $middleware)
                                                <tr>
                                                    <td class="text-center">{{ $loop->iteration }}</td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <i class="bi bi-shield-shaded me-2 fs-5"></i>
                                                            <div>
                                                                <strong>{{ $middleware['name'] }}</strong><br>
                                                                <small
                                                                    class="text-muted">{{ $middleware['class'] }}</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        {{ \App\Helpers\CurrencyHelper::format($middleware['metrics']['total_executions'], 0, false) }}
                                                    </td>
                                                    <td class="text-center">
                                                        {{ \App\Helpers\CurrencyHelper::format($middleware['metrics']['average_response_time'] * 1000, 2, false) }}
                                                        ms</td>
                                                    <td class="text-center">
                                                        {{ \App\Helpers\CurrencyHelper::format($middleware['metrics']['average_memory_usage'] / 1024, 2, false) }}
                                                        KB</td>
                                                    <td class="text-center">
                                                        <div class="progress" style="height: 20px;">
                                                            <div class="progress-bar bg-{{ $middleware['metrics']['success_rate'] >= 95 ? 'success' : ($middleware['metrics']['success_rate'] >= 80 ? 'warning' : 'danger') }}"
                                                                role="progressbar"
                                                                style="width: {{ $middleware['metrics']['success_rate'] }}%;"
                                                                aria-valuenow="{{ $middleware['metrics']['success_rate'] }}"
                                                                aria-valuemin="0" aria-valuemax="100">
                                                                {{ \App\Helpers\CurrencyHelper::format($middleware['metrics']['success_rate'], 1, false) }}%
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <span
                                                            class="badge bg-{{ $middleware['health']['status'] == 'Saudável' ? 'success' : ($middleware['health']['status'] == 'Atenção' ? 'warning' : 'danger') }}">
                                                            {{ $middleware['health']['status'] }}
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <button class="btn btn-sm btn-outline-primary"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#detailsModal-{{ $middleware['id'] }}">
                                                            <i class="bi bi-search"></i> Detalhes
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="8" class="text-center py-1">
                                                        <i class="bi bi-info-circle fs-3 text-muted"></i>
                                                        <p class="mb-0 mt-2">Nenhuma métrica de middleware encontrada.</p>
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
        </div>
    </div>

    @foreach ($middlewares as $middleware)
        <!-- Modal de Detalhes -->
        <div class="modal fade" id="detailsModal-{{ $middleware['id'] }}" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Detalhes de {{ $middleware['name'] }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Conteúdo do modal -->
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Funções de ordenação e filtro
        // ...

        function exportMetrics() {
            // Lógica para exportar métricas
            console.log('Exportando métricas...');
        }
    </script>
    @endpush
</x-app-layout>
