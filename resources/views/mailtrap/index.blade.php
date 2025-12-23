@extends('layouts.app')

@section('content')
    <div class="container-fluid py-1">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="bi bi-envelope-paper me-2"></i>
                            {{ $title ?? 'Mailtrap - Ferramentas de E-mail' }}
                        </h1>
                        <p class="text-muted mt-1">Dashboard de gerenciamento e testes de e-mail</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary" onclick="refreshDashboard()">
                            <i class="bi bi-arrow-clockwise me-1"></i>
                            Atualizar
                        </button>
                        <a href="{{ route('mailtrap.providers') }}" class="btn btn-primary">
                            <i class="bi bi-gear me-1"></i>
                            Configurar Provedores
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status do Provedor Atual -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-left-primary">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h6 class="text-primary font-weight-bold text-uppercase mb-1">
                                    Provedor Atual
                                </h6>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-circle-fill me-2"
                                        style="color: {{ isset($current_provider) && $current_provider['is_active'] ? '#28a745' : '#dc3545' }}"></i>
                                    <span class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $current_provider['provider'] ?? 'Nenhum' }}
                                    </span>
                                    @if (isset($current_provider) && $current_provider['is_active'])
                                        <span class="badge bg-success ms-2">Ativo</span>
                                    @else
                                        <span class="badge bg-danger ms-2">Inativo</span>
                                    @endif
                                </div>
                                @if (isset($current_provider) && $current_provider['description'])
                                    <p class="text-muted mt-2 mb-0">{{ $current_provider['description'] }}</p>
                                @endif
                            </div>
                            <div class="col-md-4 text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary me-2"
                                    onclick="testCurrentProvider()">
                                    <i class="bi bi-lightning me-1"></i>
                                    Testar Provedor
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cards de Estatísticas -->
        <div class="row mb-4">
            <!-- Provedores Disponíveis -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Provedores Disponíveis
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ isset($available_providers) ? count($available_providers) : 0 }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-envelope-at fs-2 text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Testes Recentes -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Testes Recentes
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ isset($recent_tests) ? count($recent_tests) : 0 }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-check-circle fs-2 text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status do Sistema -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Status do Sistema
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <i class="bi bi-circle-fill text-success me-1"></i>
                                    Online
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-activity fs-2 text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Última Atualização -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-secondary">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                    Última Atualização
                                </div>
                                <div class="h6 mb-0 font-weight-bold text-gray-800">
                                    {{ now()->format('H:i:s') }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-clock fs-2 text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Testes Recentes -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-graph-up me-2"></i>
                            Testes Recentes
                        </h6>
                    </div>
                    <div class="card-body">
                        @if (isset($recent_tests) && count($recent_tests) > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Tipo de Teste</th>
                                            <th>Status</th>
                                            <th>Mensagem</th>
                                            <th>Data/Hora</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($recent_tests as $test)
                                            <tr>
                                                <td>
                                                    <strong>{{ $test['name'] ?? $test['test_type'] }}</strong>
                                                </td>
                                                <td>
                                                    @if ($test['is_success'])
                                                        <span class="badge bg-success">
                                                            <i class="bi bi-check-circle me-1"></i>
                                                            Sucesso
                                                        </span>
                                                    @else
                                                        <span class="badge bg-danger">
                                                            <i class="bi bi-x-circle me-1"></i>
                                                            Falha
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <small class="text-muted">{{ $test['message'] ?? 'N/A' }}</small>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        {{ isset($test['cached_at']) ? \Carbon\Carbon::parse($test['cached_at'])->format('d/m/Y H:i:s') : 'N/A' }}
                                                    </small>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                                        onclick="runTest('{{ $test['test_type'] }}')">
                                                        <i class="bi bi-play me-1"></i>
                                                        Executar
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-1">
                                <i class="bi bi-inbox fs-1 text-muted mb-3"></i>
                                <h6 class="text-muted">Nenhum teste realizado ainda</h6>
                                <p class="text-muted">Execute um teste para ver os resultados aqui.</p>
                                <button type="button" class="btn btn-primary" onclick="showTestModal()">
                                    <i class="bi bi-plus-circle me-2"></i>
                                    Executar Primeiro Teste
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Ações Rápidas -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-lightning me-2"></i>
                            Ações Rápidas
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <button type="button" class="btn btn-outline-primary w-100 h-100 p-4"
                                    onclick="window.location.href='{{ route('mailtrap.tests') }}'">
                                    <i class="bi bi-play-circle fs-3 mb-2"></i>
                                    <div>Executar Testes</div>
                                    <small class="text-muted">Testar funcionalidades de e-mail</small>
                                </button>
                            </div>
                            <div class="col-md-3 mb-3">
                                <button type="button" class="btn btn-outline-info w-100 h-100 p-4"
                                    onclick="window.location.href='{{ route('mailtrap.providers') }}'">
                                    <i class="bi bi-gear fs-3 mb-2"></i>
                                    <div>Configurar Provedores</div>
                                    <small class="text-muted">Gerenciar configurações de e-mail</small>
                                </button>
                            </div>
                            <div class="col-md-3 mb-3">
                                <button type="button" class="btn btn-outline-warning w-100 h-100 p-4"
                                    onclick="window.location.href='{{ route('mailtrap.logs') }}'">
                                    <i class="bi bi-journal-text fs-3 mb-2"></i>
                                    <div>Ver Logs</div>
                                    <small class="text-muted">Consultar logs de e-mail</small>
                                </button>
                            </div>
                            <div class="col-md-3 mb-3">
                                <button type="button" class="btn btn-outline-success w-100 h-100 p-4"
                                    onclick="generateReport()">
                                    <i class="bi bi-file-earmark-bar-graph fs-3 mb-2"></i>
                                    <div>Gerar Relatório</div>
                                    <small class="text-muted">Relatório completo de testes</small>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Teste -->
    <div class="modal fade" id="testModal" tabindex="-1" aria-labelledby="testModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="testModalLabel">Executar Teste de E-mail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="testForm">
                        <div class="mb-3">
                            <label for="testType" class="form-label">Tipo de Teste</label>
                            <select class="form-select" id="testType" name="test_type" required>
                                <option value="">Selecione um tipo de teste...</option>
                                @if (isset($test_types))
                                    @foreach ($test_types as $key => $test)
                                        <option value="{{ $key }}">{{ $test['name'] }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="recipientEmail" class="form-label">E-mail do Destinatário</label>
                            <input type="email" class="form-control" id="recipientEmail" name="recipient_email"
                                value="test@example.com" required>
                            <div class="form-text">E-mail para onde o teste será enviado</div>
                        </div>
                        <div class="mb-3">
                            <label for="verificationUrl" class="form-label">URL de Verificação (opcional)</label>
                            <input type="url" class="form-control" id="verificationUrl" name="verification_url"
                                placeholder="https://exemplo.com/verificar">
                            <div class="form-text">URL para testes de verificação de e-mail</div>
                        </div>
                    </form>
                    <div id="testResult" class="mt-3" style="display: none;">
                        <div class="alert" id="testAlert">
                            <div id="testMessage"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="executeTest()">Executar Teste</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function refreshDashboard() {
            window.location.reload();
        }

        function testCurrentProvider() {
            showLoading('Testando provedor atual...');

            fetch('{{ route('mailtrap.test-provider') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        provider: '{{ $current_provider['provider'] ?? '' }}'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        showAlert('Provedor testado com sucesso!', 'success');
                    } else {
                        showAlert('Erro ao testar provedor: ' + (data.error || 'Erro desconhecido'), 'danger');
                    }
                })
                .catch(error => {
                    hideLoading();
                    showAlert('Erro interno: ' + error.message, 'danger');
                });
        }

        function showTestModal() {
            const modal = new bootstrap.Modal(document.getElementById('testModal'));
            modal.show();
        }

        function executeTest() {
            const testType = document.getElementById('testType').value;
            const recipientEmail = document.getElementById('recipientEmail').value;

            if (!testType) {
                showAlert('Selecione um tipo de teste', 'warning');
                return;
            }

            if (!recipientEmail) {
                showAlert('Informe o e-mail do destinatário', 'warning');
                return;
            }

            showLoading('Executando teste...');

            const formData = new FormData(document.getElementById('testForm'));

            fetch('{{ route('mailtrap.run-test') }}', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();

                    const resultDiv = document.getElementById('testResult');
                    const alertDiv = document.getElementById('testAlert');
                    const messageDiv = document.getElementById('testMessage');

                    resultDiv.style.display = 'block';

                    if (data.success) {
                        alertDiv.className = 'alert alert-success';
                        messageDiv.innerHTML = '<i class="bi bi-check-circle me-2"></i>' + (data.message ||
                            'Teste executado com sucesso!');
                    } else {
                        alertDiv.className = 'alert alert-danger';
                        messageDiv.innerHTML = '<i class="bi bi-x-circle me-2"></i>' + (data.error ||
                            'Erro ao executar teste');
                    }

                    // Fechar modal após 2 segundos em caso de sucesso
                    if (data.success) {
                        setTimeout(() => {
                            bootstrap.Modal.getInstance(document.getElementById('testModal')).hide();
                            window.location.reload();
                        }, 2000);
                    }
                })
                .catch(error => {
                    hideLoading();

                    const resultDiv = document.getElementById('testResult');
                    const alertDiv = document.getElementById('testAlert');
                    const messageDiv = document.getElementById('testMessage');

                    resultDiv.style.display = 'block';
                    alertDiv.className = 'alert alert-danger';
                    messageDiv.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>Erro interno: ' + error
                        .message;
                });
        }

        function runTest(testType) {
            document.getElementById('testType').value = testType;
            showTestModal();
        }

        function generateReport() {
            showLoading('Gerando relatório...');

            fetch('{{ route('mailtrap.generate-report') }}', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        showAlert('Relatório gerado com sucesso!', 'success');
                        // Abrir relatório em nova aba
                        window.open('{{ route('mailtrap.report') }}', '_blank');
                    } else {
                        showAlert('Erro ao gerar relatório: ' + (data.error || 'Erro desconhecido'), 'danger');
                    }
                })
                .catch(error => {
                    hideLoading();
                    showAlert('Erro interno: ' + error.message, 'danger');
                });
        }

        function showLoading(message = 'Carregando...') {
            // Implementar loading state
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

            // Auto-remover após 5 segundos
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        // Atualizar dashboard automaticamente a cada 30 segundos
        setInterval(refreshDashboard, 30000);
    </script>
@endpush
