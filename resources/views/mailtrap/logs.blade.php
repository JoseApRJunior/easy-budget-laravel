@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <x-page-header
            :title="$title ?? 'Logs de E-mail'"
            icon="journal-text"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Mailtrap' => route('mailtrap.index'),
                'Logs' => '#'
            ]">
            <div class="d-flex gap-2">
                <x-button type="button" variant="secondary" outline icon="arrow-clockwise" label="Atualizar" onclick="refreshLogs()" />
                <x-button type="button" variant="success" icon="download" label="Exportar CSV" onclick="exportLogs()" />
                <x-button type="button" variant="danger" icon="trash" label="Limpar Logs" onclick="clearLogs()" />
                <x-button :href="route('mailtrap.index')" variant="secondary" icon="arrow-left" label="Voltar" />
            </div>
        </x-page-header>

        <!-- Filtros e Controles -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="logLevelFilter" class="form-label">Nível</label>
                                <select class="form-select" id="logLevelFilter">
                                    <option value="">Todos os níveis</option>
                                    <option value="info">Info</option>
                                    <option value="warning">Warning</option>
                                    <option value="error">Error</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="logProviderFilter" class="form-label">Provedor</label>
                                <select class="form-select" id="logProviderFilter">
                                    <option value="">Todos os provedores</option>
                                    <option value="mailtrap">Mailtrap</option>
                                    <option value="smtp">SMTP</option>
                                    <option value="ses">SES</option>
                                    <option value="log">Log</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="logDateFilter" class="form-label">Data</label>
                                <input type="date" class="form-control" id="logDateFilter">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <x-button type="button" variant="primary" icon="funnel" onclick="applyFilters()" label="Aplicar Filtros" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resumo dos Logs -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-left-info">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <div class="log-stat">
                                    <div class="log-stat-number">{{ $log_summary['total_entries'] ?? 0 }}</div>
                                    <div class="log-stat-label">Total de Entradas</div>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="log-stat">
                                    <div class="log-stat-number text-success">
                                        {{ isset($log_entries) ? collect($log_entries)->where('level', 'info')->count() : 0 }}
                                    </div>
                                    <div class="log-stat-label">Info</div>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="log-stat">
                                    <div class="log-stat-number text-warning">
                                        {{ isset($log_entries) ? collect($log_entries)->where('level', 'warning')->count() : 0 }}
                                    </div>
                                    <div class="log-stat-label">Warnings</div>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="log-stat">
                                    <div class="log-stat-number text-danger">
                                        {{ isset($log_entries) ? collect($log_entries)->where('level', 'error')->count() : 0 }}
                                    </div>
                                    <div class="log-stat-label">Errors</div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12 text-center">
                                <small class="text-muted">
                                    <i class="bi bi-clock me-1"></i>
                                    Última atualização: {{ $log_summary['last_updated'] ?? now()->format('d/m/Y H:i:s') }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Logs -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-list-ul me-2"></i>
                            Entradas de Log
                        </h6>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-info" id="logCount">
                                {{ isset($log_entries) ? count($log_entries) : 0 }} entradas
                            </span>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="autoRefreshToggle">
                                <label class="form-check-label" for="autoRefreshToggle">
                                    <small>Auto-refresh</small>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if (isset($log_entries) && count($log_entries) > 0)
                            <div class="log-container" style="max-height: 600px; overflow-y: auto;">
                                @foreach ($log_entries as $log)
                                    <div class="log-entry" data-level="{{ $log['level'] ?? 'info' }}"
                                        data-provider="{{ $log['provider'] ?? '' }}">
                                        <div class="d-flex align-items-start p-3 border-bottom">
                                            <div class="log-icon me-3">
                                                @if (($log['level'] ?? 'info') === 'error')
                                                    <i class="bi bi-x-circle text-danger fs-5"></i>
                                                @elseif(($log['level'] ?? 'info') === 'warning')
                                                    <i class="bi bi-exclamation-triangle text-warning fs-5"></i>
                                                @elseif(($log['level'] ?? 'info') === 'critical')
                                                    <i class="bi bi-exclamation-diamond text-danger fs-5"></i>
                                                @else
                                                    <i class="bi bi-info-circle text-info fs-5"></i>
                                                @endif
                                            </div>
                                            <div class="log-content flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-start mb-1">
                                                    <div class="log-header">
                                                        <span class="log-timestamp">
                                                            {{ isset($log['timestamp']) ? \Carbon\Carbon::parse($log['timestamp'])->format('d/m/Y H:i:s') : now()->format('d/m/Y H:i:s') }}
                                                        </span>
                                                        <span
                                                            class="badge bg-{{ ($log['level'] ?? 'info') === 'error' ? 'danger' : (($log['level'] ?? 'info') === 'warning' ? 'warning' : 'info') }} ms-2">
                                                            {{ strtoupper($log['level'] ?? 'INFO') }}
                                                        </span>
                                                        @if (isset($log['provider']))
                                                            <span
                                                                class="badge bg-secondary ms-1">{{ $log['provider'] }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="log-actions">
                                                        <x-button type="button" variant="info" size="sm" icon="eye"
                                                    onclick="showLogDetails({{ json_encode($log) }})"
                                                    title="Ver detalhes" />
                                                    </div>
                                                </div>
                                                <div class="log-message">
                                                    {{ $log['message'] ?? 'Mensagem não disponível' }}
                                                </div>
                                                @if (isset($log['context']) && count($log['context']) > 0)
                                                    <div class="log-context mt-2">
                                                        <small class="text-muted">
                                                            <i class="bi bi-code me-1"></i>
                                                            Contexto:
                                                        </small>
                                                        <div class="mt-1">
                                                            @foreach ($log['context'] as $key => $value)
                                                                <span class="badge bg-light text-dark me-1 mb-1">
                                                                    {{ $key }}:
                                                                    {{ is_array($value) || is_object($value) ? json_encode($value) : $value }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="bi bi-journal-x fs-1 text-muted mb-3"></i>
                                <h6 class="text-muted">Nenhum log encontrado</h6>
                                <p class="text-muted">Os logs aparecerão aqui conforme o sistema for utilizado.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Paginação (se necessário) -->
        @if (isset($log_entries) && count($log_entries) >= 50)
            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex justify-content-center">
                        <nav aria-label="Navegação de logs">
                            <ul class="pagination">
                                <li class="page-item disabled">
                                    <span class="page-link">Anterior</span>
                                </li>
                                <li class="page-item active">
                                    <span class="page-link">1</span>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="#">Próxima</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Modal de Detalhes do Log -->
    <div class="modal fade" id="logDetailsModal" tabindex="-1" aria-labelledby="logDetailsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logDetailsModalLabel">Detalhes do Log</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="logDetailsContent">
                        <!-- Conteúdo dinâmico será inserido aqui -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary" onclick="copyLogDetails()">Copiar Detalhes</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .log-container {
            scrollbar-width: thin;
            scrollbar-color: #ccc transparent;
        }

        .log-container::-webkit-scrollbar {
            width: 6px;
        }

        .log-container::-webkit-scrollbar-track {
            background: transparent;
        }

        .log-container::-webkit-scrollbar-thumb {
            background-color: #ccc;
            border-radius: 3px;
        }

        .log-entry {
            transition: background-color 0.2s ease;
        }

        .log-entry:hover {
            background-color: #f8f9fa;
        }

        .log-stat {
            padding: 10px 0;
        }

        .log-stat-number {
            font-size: 2rem;
            font-weight: bold;
            line-height: 1;
            margin-bottom: 5px;
        }

        .log-stat-label {
            font-size: 0.875rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .log-timestamp {
            font-size: 0.875rem;
            color: #6c757d;
            font-weight: 500;
        }

        .log-message {
            color: #495057;
            line-height: 1.4;
            word-break: break-word;
        }

        .log-context {
            background-color: #f8f9fa;
            border-radius: 4px;
            padding: 8px;
            border-left: 3px solid #dee2e6;
        }

        .provider-badge {
            font-size: 0.75rem;
            padding: 2px 6px;
            border-radius: 3px;
        }
    </style>
@endpush

@push('scripts')
    <script>
        let autoRefreshInterval = null;

        function refreshLogs() {
            window.location.reload();
        }

        function applyFilters() {
            const levelFilter = document.getElementById('logLevelFilter').value;
            const providerFilter = document.getElementById('logProviderFilter').value;
            const dateFilter = document.getElementById('logDateFilter').value;

            const logEntries = document.querySelectorAll('.log-entry');

            logEntries.forEach(entry => {
                let show = true;

                if (levelFilter && entry.getAttribute('data-level') !== levelFilter) {
                    show = false;
                }

                if (providerFilter && entry.getAttribute('data-provider') !== providerFilter) {
                    show = false;
                }

                if (dateFilter) {
                    const entryDate = entry.querySelector('.log-timestamp').textContent;
                    const entryDateObj = new Date(entryDate.split(' ')[0].split('/').reverse().join('-'));
                    const filterDateObj = new Date(dateFilter);

                    if (entryDateObj.toDateString() !== filterDateObj.toDateString()) {
                        show = false;
                    }
                }

                entry.style.display = show ? 'block' : 'none';
            });

            // Atualizar contador
            const visibleEntries = document.querySelectorAll('.log-entry[style*="block"], .log-entry:not([style*="none"])');
            document.getElementById('logCount').textContent = `${visibleEntries.length} entradas`;
        }

        function clearLogs() {
            if (confirm('Tem certeza que deseja limpar todos os logs? Esta ação não pode ser desfeita.')) {
                showLoading('Limpando logs...');

                // Simular limpeza (em produção seria uma chamada real)
                setTimeout(() => {
                    hideLoading();
                    showAlert('Logs limpos com sucesso!', 'success');
                    setTimeout(() => window.location.reload(), 1500);
                }, 1000);
            }
        }

        function exportLogs() {
            showLoading('Exportando logs...');

            // Simular exportação (em produção seria uma chamada real)
            setTimeout(() => {
                hideLoading();
                showAlert('Logs exportados com sucesso!', 'success');
            }, 1500);
        }

        function showLogDetails(logData) {
            const modal = new bootstrap.Modal(document.getElementById('logDetailsModal'));
            const contentDiv = document.getElementById('logDetailsContent');

            const detailsHtml = `
        <div class="row">
            <div class="col-md-6">
                <h6>Informações Gerais</h6>
                <table class="table table-sm">
                    <tr>
                        <td><strong>Timestamp:</strong></td>
                        <td>${logData.timestamp || 'N/A'}</td>
                    </tr>
                    <tr>
                        <td><strong>Nível:</strong></td>
                        <td>
                            <span class="badge bg-${logData.level === 'error' ? 'danger' : (logData.level === 'warning' ? 'warning' : 'info')}">
                                ${logData.level ? logData.level.toUpperCase() : 'INFO'}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Provedor:</strong></td>
                        <td>${logData.provider || 'N/A'}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Contexto Completo</h6>
                <div class="bg-light p-3 rounded">
                    <pre class="mb-0"><code>${JSON.stringify(logData.context || {}, null, 2)}</code></pre>
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <h6>Mensagem Completa</h6>
                <div class="alert alert-info">
                    ${logData.message || 'Mensagem não disponível'}
                </div>
            </div>
        </div>
    `;

            contentDiv.innerHTML = detailsHtml;
            modal.show();
        }

        function copyLogDetails() {
            const content = document.getElementById('logDetailsContent');
            const textToCopy = content.textContent || content.innerText;

            navigator.clipboard.writeText(textToCopy).then(() => {
                showAlert('Detalhes copiados para a área de transferência!', 'success');
            }).catch(() => {
                showAlert('Erro ao copiar detalhes', 'danger');
            });
        }

        // Auto-refresh toggle
        document.getElementById('autoRefreshToggle').addEventListener('change', function() {
            if (this.checked) {
                autoRefreshInterval = setInterval(refreshLogs, 30000); // 30 segundos
                showAlert('Auto-refresh ativado (30s)', 'info');
            } else {
                if (autoRefreshInterval) {
                    clearInterval(autoRefreshInterval);
                    autoRefreshInterval = null;
                }
                showAlert('Auto-refresh desativado', 'info');
            }
        });

        // Aplicar filtros quando selects mudarem
        document.getElementById('logLevelFilter').addEventListener('change', applyFilters);
        document.getElementById('logProviderFilter').addEventListener('change', applyFilters);
        document.getElementById('logDateFilter').addEventListener('change', applyFilters);

        function showLoading(message = 'Carregando...') {
            if (!document.getElementById('loadingOverlay')) {
                const overlay = document.createElement('div');
                overlay.id = 'loadingOverlay';
                overlay.className =
                    'd-flex justify-content-center align-items-center position-fixed w-100 h-100 bg-dark bg-opacity-50';
                overlay.style.zIndex = '9999';
                overlay.style.top = '0';
                overlay.style.left = '0';
                overlay.innerHTML = `
            <div class="bg-white p-4 rounded shadow">
                <div class="d-flex align-items-center">
                    <div class="spinner-border text-primary me-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span>${message}</span>
                </div>
            </div>
        `;
                document.body.appendChild(overlay);
            }
        }

        function hideLoading() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.remove();
            }
        }

        function showAlert(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 10000; min-width: 300px;';
            alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

            document.body.appendChild(alertDiv);

            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        // Inicializar com filtros aplicados
        document.addEventListener('DOMContentLoaded', function() {
            applyFilters();
        });
    </script>
@endpush
