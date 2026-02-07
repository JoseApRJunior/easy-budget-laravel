@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <x-layout.page-header
            :title="$title ?? 'Testes de E-mail'"
            icon="play-circle"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Mailtrap' => route('mailtrap.index'),
                'Testes' => '#'
            ]">
            <div class="d-flex gap-2">
                <x-ui.button type="button" variant="secondary" outline icon="arrow-clockwise" label="Atualizar" onclick="refreshTests()" />
                <x-ui.button type="button" variant="primary" icon="plus-circle" label="Novo Teste" onclick="showTestModal()" />
                <x-ui.button :href="route('mailtrap.index')" variant="secondary" icon="arrow-left" label="Voltar" />
            </div>
        </x-layout.page-header>

        <!-- Cards de Estatísticas -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Testes Executados
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ isset($test_history) ? count($test_history) : 0 }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-check-circle fs-2 text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Tipos Disponíveis
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ isset($test_types) ? count($test_types) : 0 }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-list-ul fs-2 text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Último Teste
                                </div>
                                <div class="h6 mb-0 font-weight-bold text-gray-800">
                                    {{ isset($recent_results) && count($recent_results) > 0 ? \Carbon\Carbon::parse($recent_results[0]['cached_at'])->format('H:i') : 'Nunca' }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-clock-history fs-2 text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Taxa de Sucesso
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ isset($recent_results) && count($recent_results) > 0
                                        ? number_format((collect($recent_results)->where('is_success', true)->count() / count($recent_results)) * 100, 1)
                                        : '0' }}%
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-graph-up fs-2 text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tipos de Teste Disponíveis -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-list-check me-2"></i>
                            Tipos de Teste Disponíveis
                        </h6>
                    </div>
                    <div class="card-body">
                        @if (isset($test_types) && count($test_types) > 0)
                            <div class="row">
                                @foreach ($test_types as $testKey => $testInfo)
                                    <div class="col-xl-6 col-lg-12 mb-4">
                                        <div class="card border-left-info h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <div class="flex-grow-1">
                                                        <h5 class="card-title mb-2">{{ $testInfo['name'] }}</h5>
                                                        <p class="card-text small text-muted mb-3">
                                                            {{ $testInfo['description'] }}
                                                        </p>

                                                        <!-- Status do último teste -->
                                                        @php
                                                            $lastTest = collect($recent_results ?? [])->firstWhere(
                                                                'test_type',
                                                                $testKey,
                                                            );
                                                        @endphp
                                                        @if ($lastTest)
                                                            <div class="test-status mb-3">
                                                                @if ($lastTest['is_success'])
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
                                                                <small class="text-muted ms-2">
                                                                    Último teste:
                                                                    {{ \Carbon\Carbon::parse($lastTest['cached_at'])->format('d/m/Y H:i:s') }}
                                                                </small>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="text-end">
                                                        <button type="button" class="btn btn-sm btn-primary"
                                                            onclick="runSpecificTest('{{ $testKey }}')">
                                                            <i class="bi bi-play me-1"></i>
                                                            Executar
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Detalhes do teste -->
                                                @if (isset($testInfo['parameters']) && count($testInfo['parameters']) > 0)
                                                    <div class="test-parameters">
                                                        <h6 class="small text-muted mb-2">PARÂMETROS NECESSÁRIOS:</h6>
                                                        <div class="row">
                                                            @foreach ($testInfo['parameters'] as $param)
                                                                <div class="col-md-6 mb-2">
                                                                    <small class="d-block">
                                                                        <i class="bi bi-circle-fill me-1"
                                                                            style="font-size: 6px;"></i>
                                                                        {{ $param['name'] }}
                                                                        @if (isset($param['required']) && $param['required'])
                                                                            <span class="text-danger">*</span>
                                                                        @endif
                                                                    </small>
                                                                    @if (isset($param['description']))
                                                                        <small
                                                                            class="text-muted d-block">{{ $param['description'] }}</small>
                                                                    @endif
                                                                </div>
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
                            <div class="text-center py-1">
                                <i class="bi bi-clipboard-x fs-1 text-muted mb-3"></i>
                                <h6 class="text-muted">Nenhum tipo de teste disponível</h6>
                                <p class="text-muted">Os tipos de teste serão carregados automaticamente.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Histórico de Testes -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-clock-history me-2"></i>
                            Histórico de Testes
                        </h6>
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm" id="filterStatus" style="width: auto;">
                                <option value="">Todos os Status</option>
                                <option value="success">Apenas Sucessos</option>
                                <option value="failed">Apenas Falhas</option>
                            </select>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="clearTestHistory()">
                                <i class="bi bi-trash me-1"></i>
                                Limpar Histórico
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        @if (isset($test_history) && count($test_history) > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tipo de Teste</th>
                                            <th>Status</th>
                                            <th>Mensagem</th>
                                            <th>Data/Hora</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody id="testHistoryBody">
                                        @foreach ($test_history as $test)
                                            <tr data-status="{{ $test['is_success'] ? 'success' : 'failed' }}">
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
                                                    <span
                                                        class="text-muted">{{ Str::limit($test['message'] ?? 'N/A', 50) }}</span>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        {{ isset($test['cached_at']) ? \Carbon\Carbon::parse($test['cached_at'])->format('d/m/Y H:i:s') : 'N/A' }}
                                                    </small>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                                        onclick="runSpecificTest('{{ $test['test_type'] }}')">
                                                        <i class="bi bi-play me-1"></i>
                                                        Repetir
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
                                <h6 class="text-muted">Nenhum teste no histórico</h6>
                                <p class="text-muted">Execute seu primeiro teste para ver o histórico aqui.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Execução de Teste -->
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

                        <!-- Campos dinâmicos serão inseridos aqui -->
                        <div id="dynamicFields">
                            <!-- Campos específicos do teste serão adicionados via JavaScript -->
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
        function refreshTests() {
            window.location.reload();
        }

        function showTestModal() {
            const modal = new bootstrap.Modal(document.getElementById('testModal'));
            modal.show();
        }

        function runSpecificTest(testType) {
            // Preencher o select com o tipo específico
            document.getElementById('testType').value = testType;

            // Buscar parâmetros específicos do teste
            loadTestParameters(testType);

            // Mostrar modal
            showTestModal();
        }

        function loadTestParameters(testType) {
            const dynamicFieldsDiv = document.getElementById('dynamicFields');

            // Simular busca de parâmetros (em produção seria via AJAX)
            const testParams = {
                'connectivity': [{
                    name: 'timeout',
                    label: 'Timeout (segundos)',
                    type: 'number',
                    value: 30,
                    required: false
                }],
                'verification': [{
                    name: 'template',
                    label: 'Template de E-mail',
                    type: 'select',
                    options: [{
                            value: 'default',
                            label: 'Padrão'
                        },
                        {
                            value: 'custom',
                            label: 'Personalizado'
                        }
                    ],
                    value: 'default',
                    required: false
                }],
                'budget_notification': [{
                    name: 'budget_id',
                    label: 'ID do Orçamento',
                    type: 'number',
                    value: '',
                    required: true,
                    placeholder: 'Digite o ID do orçamento para teste'
                }],
                'invoice_notification': [{
                    name: 'invoice_id',
                    label: 'ID da Fatura',
                    type: 'number',
                    value: '',
                    required: true,
                    placeholder: 'Digite o ID da fatura para teste'
                }]
            };

            if (testParams[testType]) {
                let fieldsHtml = '';

                testParams[testType].forEach(param => {
                    fieldsHtml += `<div class="mb-3">`;
                    fieldsHtml += `<label for="${param.name}" class="form-label">${param.label}</label>`;

                    if (param.type === 'select') {
                        fieldsHtml += `<select class="form-select" id="${param.name}" name="${param.name}">`;
                        param.options.forEach(option => {
                            fieldsHtml +=
                                `<option value="${option.value}" ${option.value === param.value ? 'selected' : ''}>${option.label}</option>`;
                        });
                        fieldsHtml += `</select>`;
                    } else {
                        fieldsHtml +=
                            `<input type="${param.type}" class="form-control" id="${param.name}" name="${param.name}" value="${param.value}" ${param.required ? 'required' : ''} placeholder="${param.placeholder || ''}">`;
                    }

                    if (param.description) {
                        fieldsHtml += `<div class="form-text">${param.description}</div>`;
                    }

                    fieldsHtml += `</div>`;
                });

                dynamicFieldsDiv.innerHTML = fieldsHtml;
            } else {
                dynamicFieldsDiv.innerHTML = '';
            }
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

                        // Fechar modal e atualizar página após sucesso
                        setTimeout(() => {
                            bootstrap.Modal.getInstance(document.getElementById('testModal')).hide();
                            window.location.reload();
                        }, 2000);
                    } else {
                        alertDiv.className = 'alert alert-danger';
                        messageDiv.innerHTML = '<i class="bi bi-x-circle me-2"></i>' + (data.error ||
                            'Erro ao executar teste');
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

        function clearTestHistory() {
            if (confirm('Tem certeza que deseja limpar o histórico de testes? Esta ação não pode ser desfeita.')) {
                showLoading('Limpando histórico...');

                fetch('{{ route('mailtrap.clear-cache') }}', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        hideLoading();
                        if (data.success) {
                            showAlert('Histórico limpo com sucesso!', 'success');
                            setTimeout(() => window.location.reload(), 1500);
                        } else {
                            showAlert('Erro ao limpar histórico: ' + (data.error || 'Erro desconhecido'), 'danger');
                        }
                    })
                    .catch(error => {
                        hideLoading();
                        showAlert('Erro interno: ' + error.message, 'danger');
                    });
            }
        }

        function filterTests() {
            const filterValue = document.getElementById('filterStatus').value;
            const rows = document.querySelectorAll('#testHistoryBody tr');

            rows.forEach(row => {
                const status = row.getAttribute('data-status');
                if (filterValue === '' || status === filterValue) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Adicionar event listener para o filtro
        document.getElementById('filterStatus').addEventListener('change', filterTests);

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
    </script>
@endpush
