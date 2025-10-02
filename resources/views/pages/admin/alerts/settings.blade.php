@extends('layouts.admin')

@section('title', $pageTitle . ' - Easy Budget')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-cog text-primary me-2"></i>
            Configurações de Alertas
        </h1>
        <a href="{{ url('/admin/alerts') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>

    <form id="settingsForm" method="POST" action="{{ url('/admin/alerts/settings') }}">
        @csrf
        <div class="row">
            <!-- Thresholds -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-exclamation-triangle me-2"></i>Limites de Alerta
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Taxa de Sucesso Crítica (%)</label>
                                <input type="number" class="form-control" name="critical_success_rate"
                                       value="{{ $settings['thresholds']['critical_success_rate'] }}" min="0" max="100">
                                <small class="text-muted">Abaixo deste valor gera alerta crítico</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Taxa de Sucesso Warning (%)</label>
                                <input type="number" class="form-control" name="warning_success_rate"
                                       value="{{ $settings['thresholds']['warning_success_rate'] }}" min="0" max="100">
                                <small class="text-muted">Abaixo deste valor gera warning</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tempo Resposta Crítico (ms)</label>
                                <input type="number" class="form-control" name="critical_response_time"
                                       value="{{ $settings['thresholds']['critical_response_time'] }}" min="1">
                                <small class="text-muted">Acima deste valor gera alerta crítico</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tempo Resposta Warning (ms)</label>
                                <input type="number" class="form-control" name="warning_response_time"
                                       value="{{ $settings['thresholds']['warning_response_time'] }}" min="1">
                                <small class="text-muted">Acima deste valor gera warning</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Memória Máxima (MB)</label>
                                <input type="number" class="form-control" name="max_memory_mb"
                                       value="{{ $settings['thresholds']['max_memory_mb'] }}" min="64">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">CPU Máximo (%)</label>
                                <input type="number" class="form-control" name="max_cpu_percent"
                                       value="{{ $settings['thresholds']['max_cpu_percent'] }}" min="1" max="100">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notificações -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-bell me-2"></i>Notificações
                        </h6>
                    </div>
                    <div class="card-body">
                        <!-- E-mail -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="email_enabled"
                                       {{ $settings['notifications']['email_enabled'] ? 'checked' : '' }}>
                                <label class="form-check-label">Notificações por E-mail</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">E-mails (separados por vírgula)</label>
                            <textarea class="form-control" name="email_addresses" rows="2">{{ $settings['notifications']['email_addresses'] }}</textarea>
                        </div>

                        <!-- Webhook -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="webhook_enabled"
                                       {{ $settings['notifications']['webhook_enabled'] ? 'checked' : '' }}>
                                <label class="form-check-label">Webhook</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">URL do Webhook</label>
                            <input type="url" class="form-control" name="webhook_url"
                                   value="{{ $settings['notifications']['webhook_url'] }}">
                        </div>

                        <!-- Slack -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="slack_enabled"
                                       {{ $settings['notifications']['slack_enabled'] ? 'checked' : '' }}>
                                <label class="form-check-label">Slack</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Webhook do Slack</label>
                            <input type="url" class="form-control" name="slack_webhook"
                                   value="{{ $settings['notifications']['slack_webhook'] }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Monitoramento -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-eye me-2"></i>Monitoramento
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Intervalo de Verificação (min)</label>
                                <input type="number" class="form-control" name="check_interval"
                                       value="{{ $settings['monitoring']['check_interval'] }}" min="1">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Severidade Mínima</label>
                                <select class="form-control" name="min_severity">
                                    <option value="INFO" {{ $settings['monitoring']['min_severity'] == 'INFO' ? 'selected' : '' }}>Info</option>
                                    <option value="WARNING" {{ $settings['monitoring']['min_severity'] == 'WARNING' ? 'selected' : '' }}>Warning</option>
                                    <option value="CRITICAL" {{ $settings['monitoring']['min_severity'] == 'CRITICAL' ? 'selected' : '' }}>Crítico</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="auto_resolve"
                                       {{ $settings['monitoring']['auto_resolve'] ? 'checked' : '' }}>
                                <label class="form-check-label">Resolver automaticamente quando normalizar</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Middlewares Monitorados</label>
                            @foreach (['auth', 'admin', 'user', 'provider', 'guest'] as $middleware)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="enabled_middlewares[]"
                                       value="{{ $middleware }}"
                                       {{ in_array($middleware, $settings['monitoring']['enabled_middlewares']) ? 'checked' : '' }}>
                                <label class="form-check-label">{{ ucfirst($middleware) }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Interface -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-desktop me-2"></i>Interface
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Auto-refresh (segundos)</label>
                                <input type="number" class="form-control" name="auto_refresh"
                                       value="{{ $settings['interface']['auto_refresh'] }}" min="10">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tema</label>
                                <select class="form-control" name="theme">
                                    <option value="light" {{ $settings['interface']['theme'] == 'light' ? 'selected' : '' }}>Claro</option>
                                    <option value="dark" {{ $settings['interface']['theme'] == 'dark' ? 'selected' : '' }}>Escuro</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Timezone</label>
                            <select class="form-control" name="timezone">
                                <option value="America/Sao_Paulo" {{ $settings['interface']['timezone'] == 'America/Sao_Paulo' ? 'selected' : '' }}>São Paulo (UTC-3)</option>
                                <option value="America/New_York" {{ $settings['interface']['timezone'] == 'America/New_York' ? 'selected' : '' }}>New York (UTC-5)</option>
                                <option value="Europe/London" {{ $settings['interface']['timezone'] == 'Europe/London' ? 'selected' : '' }}>London (UTC+0)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botões -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-body text-center">
                        <button type="submit" class="btn btn-primary btn-lg me-3">
                            <i class="fas fa-save me-2"></i>Salvar Configurações
                        </button>
                        <button type="button" class="btn btn-secondary btn-lg" onclick="resetForm()">
                            <i class="fas fa-undo me-2"></i>Restaurar Padrões
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@section('scripts')
<script>
function resetForm() {
    if (confirm('Deseja restaurar as configurações padrão?')) {
        document.getElementById('settingsForm').reset();
    }
}
</script>
@endsection
