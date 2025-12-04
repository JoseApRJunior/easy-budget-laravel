@extends('layouts.admin')

@section('title', 'Configurações Globais - EasyBudget Admin')

@section('content')
    <div class="container-fluid py-1">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-0 text-gray-800">Configurações Globais</h1>
                        <p class="text-muted mb-0">Gerencie as configurações do sistema EasyBudget</p>
                    </div>
                    <div>
                        <button class="btn btn-outline-secondary btn-sm" onclick="exportSettings()">
                            <i class="bi bi-download me-1"></i> Exportar
                        </button>
                        <button class="btn btn-outline-danger btn-sm ms-2" onclick="clearCache()">
                            <i class="bi bi-trash me-1"></i> Limpar Cache
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Navigation -->
        <div class="row mb-4">
            <div class="col-12">
                <ul class="nav nav-pills" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="general-tab" data-bs-toggle="pill" data-bs-target="#general"
                            type="button">
                            <i class="bi bi-gear me-1"></i> Geral
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="configuration-tab" data-bs-toggle="pill"
                            data-bs-target="#configuration" type="button">
                            <i class="bi bi-sliders me-1"></i> Configuração
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="email-tab" data-bs-toggle="pill" data-bs-target="#email"
                            type="button">
                            <i class="bi bi-envelope me-1"></i> Email
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="payment-tab" data-bs-toggle="pill" data-bs-target="#payment"
                            type="button">
                            <i class="bi bi-credit-card me-1"></i> Pagamento
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="notifications-tab" data-bs-toggle="pill"
                            data-bs-target="#notifications" type="button">
                            <i class="bi bi-bell me-1"></i> Notificações
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="ai-tab" data-bs-toggle="pill" data-bs-target="#ai" type="button">
                            <i class="bi bi-robot me-1"></i> IA & Analytics
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="backup-tab" data-bs-toggle="pill" data-bs-target="#backup"
                            type="button">
                            <i class="bi bi-cloud-download me-1"></i> Backup
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="entities-tab" data-bs-toggle="pill" data-bs-target="#entities"
                            type="button">
                            <i class="bi bi-database me-1"></i> Entidades
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Settings Content -->
        <div class="tab-content" id="settingsTabContent">
            <!-- General Settings -->
            <div class="tab-pane fade show active" id="general" role="tabpanel">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Configurações Gerais</h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.settings.general.update') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="app_name" class="form-label">Nome da Aplicação</label>
                                        <input type="text" class="form-control" id="app_name" name="app_name"
                                            value="{{ $settings['app_name'] ?? 'EasyBudget' }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="timezone" class="form-label">Fuso Horário</label>
                                        <select class="form-select" id="timezone" name="timezone" required>
                                            <option value="America/Sao_Paulo"
                                                {{ ($settings['timezone'] ?? 'America/Sao_Paulo') == 'America/Sao_Paulo' ? 'selected' : '' }}>
                                                America/São Paulo</option>
                                            <option value="America/New_York"
                                                {{ ($settings['timezone'] ?? '') == 'America/New_York' ? 'selected' : '' }}>
                                                America/New York</option>
                                            <option value="Europe/London"
                                                {{ ($settings['timezone'] ?? '') == 'Europe/London' ? 'selected' : '' }}>
                                                Europe/London</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="contact_email" class="form-label">Email de Contato</label>
                                        <input type="email" class="form-control" id="contact_email"
                                            name="contact_email" value="{{ $settings['contact_email'] ?? '' }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="support_email" class="form-label">Email de Suporte</label>
                                        <input type="email" class="form-control" id="support_email"
                                            name="support_email" value="{{ $settings['support_email'] ?? '' }}" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="currency" class="form-label">Moeda</label>
                                        <select class="form-select" id="currency" name="currency" required>
                                            <option value="BRL"
                                                {{ ($settings['currency'] ?? 'BRL') == 'BRL' ? 'selected' : '' }}>BRL - Real
                                                Brasileiro</option>
                                            <option value="USD"
                                                {{ ($settings['currency'] ?? '') == 'USD' ? 'selected' : '' }}>USD - Dólar
                                                Americano</option>
                                            <option value="EUR"
                                                {{ ($settings['currency'] ?? '') == 'EUR' ? 'selected' : '' }}>EUR - Euro
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="language" class="form-label">Idioma Padrão</label>
                                        <select class="form-select" id="language" name="language" required>
                                            <option value="pt-BR"
                                                {{ ($settings['language'] ?? 'pt-BR') == 'pt-BR' ? 'selected' : '' }}>
                                                Português (Brasil)</option>
                                            <option value="en"
                                                {{ ($settings['language'] ?? '') == 'en' ? 'selected' : '' }}>English
                                            </option>
                                            <option value="es"
                                                {{ ($settings['language'] ?? '') == 'es' ? 'selected' : '' }}>Español
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="app_description" class="form-label">Descrição da Aplicação</label>
                                <textarea class="form-control" id="app_description" name="app_description" rows="3">{{ $settings['app_description'] ?? '' }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Salvar Configurações
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Configuration Settings -->
            <div class="tab-pane fade" id="configuration" role="tabpanel">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Configurações do Sistema</h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.settings.configuration.update') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="maintenance_mode"
                                                name="maintenance_mode"
                                                {{ $settings['maintenance_mode'] ?? false ? 'checked' : '' }}>
                                            <label class="form-check-label" for="maintenance_mode">
                                                Modo de Manutenção
                                            </label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="allow_registration"
                                                name="allow_registration"
                                                {{ $settings['allow_registration'] ?? true ? 'checked' : '' }}>
                                            <label class="form-check-label" for="allow_registration">
                                                Permitir Registro de Novos Usuários
                                            </label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox"
                                                id="require_email_verification" name="require_email_verification"
                                                {{ $settings['require_email_verification'] ?? true ? 'checked' : '' }}>
                                            <label class="form-check-label" for="require_email_verification">
                                                Requerer Verificação de Email
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="trial_days" class="form-label">Dias de Trial</label>
                                        <input type="number" class="form-control" id="trial_days" name="trial_days"
                                            value="{{ $settings['trial_days'] ?? '7' }}" min="1" max="365"
                                            required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="max_tenants_per_user" class="form-label">Máximo de Tenants por
                                            Usuário</label>
                                        <input type="number" class="form-control" id="max_tenants_per_user"
                                            name="max_tenants_per_user"
                                            value="{{ $settings['max_tenants_per_user'] ?? '1' }}" min="1"
                                            max="100" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="session_lifetime" class="form-label">Tempo de Sessão (minutos)</label>
                                        <input type="number" class="form-control" id="session_lifetime"
                                            name="session_lifetime" value="{{ $settings['session_lifetime'] ?? '120' }}"
                                            min="1" max="43200" required>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Salvar Configurações
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Email Settings -->
            <div class="tab-pane fade" id="email" role="tabpanel">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Configurações de Email</h6>
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="testEmail()">
                            <i class="bi bi-envelope-check me-1"></i> Testar Email
                        </button>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.settings.email.update') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="mail_driver" class="form-label">Driver de Email</label>
                                        <select class="form-select" id="mail_driver" name="mail_driver" required>
                                            <option value="smtp"
                                                {{ ($settings['mail_driver'] ?? 'smtp') == 'smtp' ? 'selected' : '' }}>SMTP
                                            </option>
                                            <option value="mailgun"
                                                {{ ($settings['mail_driver'] ?? '') == 'mailgun' ? 'selected' : '' }}>
                                                Mailgun</option>
                                            <option value="ses"
                                                {{ ($settings['mail_driver'] ?? '') == 'ses' ? 'selected' : '' }}>Amazon
                                                SES</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="mail_host" class="form-label">Host SMTP</label>
                                        <input type="text" class="form-control" id="mail_host" name="mail_host"
                                            value="{{ $settings['mail_host'] ?? '' }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="mail_port" class="form-label">Porta SMTP</label>
                                        <input type="number" class="form-control" id="mail_port" name="mail_port"
                                            value="{{ $settings['mail_port'] ?? '587' }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="mail_username" class="form-label">Usuário SMTP</label>
                                        <input type="text" class="form-control" id="mail_username"
                                            name="mail_username" value="{{ $settings['mail_username'] ?? '' }}">
                                    </div>
                                    <div class="mb-3">
                                        <label for="mail_password" class="form-label">Senha SMTP</label>
                                        <input type="password" class="form-control" id="mail_password"
                                            name="mail_password" placeholder="Deixe em branco para manter a senha atual">
                                    </div>
                                    <div class="mb-3">
                                        <label for="mail_encryption" class="form-label">Criptografia</label>
                                        <select class="form-select" id="mail_encryption" name="mail_encryption">
                                            <option value=""
                                                {{ ($settings['mail_encryption'] ?? '') == '' ? 'selected' : '' }}>Nenhuma
                                            </option>
                                            <option value="tls"
                                                {{ ($settings['mail_encryption'] ?? '') == 'tls' ? 'selected' : '' }}>TLS
                                            </option>
                                            <option value="ssl"
                                                {{ ($settings['mail_encryption'] ?? '') == 'ssl' ? 'selected' : '' }}>SSL
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="mail_from_address" class="form-label">Email Remetente</label>
                                        <input type="email" class="form-control" id="mail_from_address"
                                            name="mail_from_address" value="{{ $settings['mail_from_address'] ?? '' }}"
                                            required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="mail_from_name" class="form-label">Nome Remetente</label>
                                        <input type="text" class="form-control" id="mail_from_name"
                                            name="mail_from_name"
                                            value="{{ $settings['mail_from_name'] ?? 'EasyBudget' }}" required>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Salvar Configurações
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Payment Settings -->
            <div class="tab-pane fade" id="payment" role="tabpanel">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Configurações de Pagamento</h6>
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="testPayment()">
                            <i class="bi bi-credit-card me-1"></i> Testar Pagamento
                        </button>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.settings.payment.update') }}" method="POST">
                            @csrf

                            <!-- Mercado Pago -->
                            <h5 class="mb-3">Mercado Pago</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="mercadopago_enabled"
                                                name="mercadopago_enabled"
                                                {{ $settings['mercadopago_enabled'] ?? true ? 'checked' : '' }}>
                                            <label class="form-check-label" for="mercadopago_enabled">
                                                Habilitar Mercado Pago
                                            </label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="mercadopago_public_key" class="form-label">Chave Pública</label>
                                        <input type="text" class="form-control" id="mercadopago_public_key"
                                            name="mercadopago_public_key"
                                            value="{{ $settings['mercadopago_public_key'] ?? '' }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="mercadopago_access_token" class="form-label">Token de Acesso</label>
                                        <input type="password" class="form-control" id="mercadopago_access_token"
                                            name="mercadopago_access_token"
                                            placeholder="Deixe em branco para manter o token atual">
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <!-- General Payment Settings -->
                            <h5 class="mb-3">Configurações Gerais</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payment_currency" class="form-label">Moeda de Pagamento</label>
                                        <select class="form-select" id="payment_currency" name="payment_currency"
                                            required>
                                            <option value="BRL"
                                                {{ ($settings['payment_currency'] ?? 'BRL') == 'BRL' ? 'selected' : '' }}>
                                                BRL</option>
                                            <option value="USD"
                                                {{ ($settings['payment_currency'] ?? '') == 'USD' ? 'selected' : '' }}>USD
                                            </option>
                                            <option value="EUR"
                                                {{ ($settings['payment_currency'] ?? '') == 'EUR' ? 'selected' : '' }}>EUR
                                            </option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="payment_tax_rate" class="form-label">Taxa de Imposto (%)</label>
                                        <input type="number" class="form-control" id="payment_tax_rate"
                                            name="payment_tax_rate" value="{{ $settings['payment_tax_rate'] ?? '0' }}"
                                            min="0" max="100" step="0.01" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payment_processing_fee" class="form-label">Taxa de Processamento
                                            (%)</label>
                                        <input type="number" class="form-control" id="payment_processing_fee"
                                            name="payment_processing_fee"
                                            value="{{ $settings['payment_processing_fee'] ?? '0' }}" min="0"
                                            max="100" step="0.01" required>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox"
                                                id="enable_recurring_payments" name="enable_recurring_payments"
                                                {{ $settings['enable_recurring_payments'] ?? true ? 'checked' : '' }}>
                                            <label class="form-check-label" for="enable_recurring_payments">
                                                Habilitar Pagamentos Recorrentes
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Salvar Configurações
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Notifications Settings -->
            <div class="tab-pane fade" id="notifications" role="tabpanel">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Configurações de Notificação</h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.settings.notifications.update') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox"
                                                id="enable_email_notifications" name="enable_email_notifications"
                                                {{ $settings['enable_email_notifications'] ?? true ? 'checked' : '' }}>
                                            <label class="form-check-label" for="enable_email_notifications">
                                                Habilitar Notificações por Email
                                            </label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="enable_sms_notifications"
                                                name="enable_sms_notifications"
                                                {{ $settings['enable_sms_notifications'] ?? false ? 'checked' : '' }}>
                                            <label class="form-check-label" for="enable_sms_notifications">
                                                Habilitar Notificações por SMS
                                            </label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="notification_email_frequency" class="form-label">Frequência de
                                            Email</label>
                                        <select class="form-select" id="notification_email_frequency"
                                            name="notification_email_frequency" required>
                                            <option value="immediate"
                                                {{ ($settings['notification_email_frequency'] ?? 'immediate') == 'immediate' ? 'selected' : '' }}>
                                                Imediato</option>
                                            <option value="daily"
                                                {{ ($settings['notification_email_frequency'] ?? '') == 'daily' ? 'selected' : '' }}>
                                                Diário</option>
                                            <option value="weekly"
                                                {{ ($settings['notification_email_frequency'] ?? '') == 'weekly' ? 'selected' : '' }}>
                                                Semanal</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="max_emails_per_hour" class="form-label">Máximo de Emails por
                                            Hora</label>
                                        <input type="number" class="form-control" id="max_emails_per_hour"
                                            name="max_emails_per_hour"
                                            value="{{ $settings['max_emails_per_hour'] ?? '100' }}" min="1"
                                            max="1000" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="max_sms_per_hour" class="form-label">Máximo de SMS por Hora</label>
                                        <input type="number" class="form-control" id="max_sms_per_hour"
                                            name="max_sms_per_hour" value="{{ $settings['max_sms_per_hour'] ?? '10' }}"
                                            min="1" max="100" required>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox"
                                                id="enable_notification_queue" name="enable_notification_queue"
                                                {{ $settings['enable_notification_queue'] ?? true ? 'checked' : '' }}>
                                            <label class="form-check-label" for="enable_notification_queue">
                                                Usar Fila de Notificações
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Salvar Configurações
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- AI & Analytics Settings -->
            <div class="tab-pane fade" id="ai" role="tabpanel">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Configurações de IA e Analytics</h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.settings.ai.update') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="enable_ai_analytics"
                                                name="enable_ai_analytics"
                                                {{ $settings['enable_ai_analytics'] ?? false ? 'checked' : '' }}>
                                            <label class="form-check-label" for="enable_ai_analytics">
                                                Habilitar IA e Analytics
                                            </label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="ai_provider" class="form-label">Provedor de IA</label>
                                        <select class="form-select" id="ai_provider" name="ai_provider" required>
                                            <option value="openai"
                                                {{ ($settings['ai_provider'] ?? 'openai') == 'openai' ? 'selected' : '' }}>
                                                OpenAI</option>
                                            <option value="anthropic"
                                                {{ ($settings['ai_provider'] ?? '') == 'anthropic' ? 'selected' : '' }}>
                                                Anthropic</option>
                                            <option value="google"
                                                {{ ($settings['ai_provider'] ?? '') == 'google' ? 'selected' : '' }}>Google
                                            </option>
                                            <option value="local"
                                                {{ ($settings['ai_provider'] ?? '') == 'local' ? 'selected' : '' }}>Local
                                            </option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="ai_model" class="form-label">Modelo de IA</label>
                                        <input type="text" class="form-control" id="ai_model" name="ai_model"
                                            value="{{ $settings['ai_model'] ?? 'gpt-3.5-turbo' }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="ai_api_key" class="form-label">Chave API de IA</label>
                                        <input type="password" class="form-control" id="ai_api_key" name="ai_api_key"
                                            placeholder="Deixe em branco para manter a chave atual">
                                    </div>
                                    <div class="mb-3">
                                        <label for="analytics_retention_days" class="form-label">Retenção de Analytics
                                            (dias)</label>
                                        <input type="number" class="form-control" id="analytics_retention_days"
                                            name="analytics_retention_days"
                                            value="{{ $settings['analytics_retention_days'] ?? '365' }}" min="30"
                                            max="3650" required>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox"
                                                id="enable_real_time_analytics" name="enable_real_time_analytics"
                                                {{ $settings['enable_real_time_analytics'] ?? false ? 'checked' : '' }}>
                                            <label class="form-check-label" for="enable_real_time_analytics">
                                                Analytics em Tempo Real
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox"
                                                id="enable_predictive_analytics" name="enable_predictive_analytics"
                                                {{ $settings['enable_predictive_analytics'] ?? true ? 'checked' : '' }}>
                                            <label class="form-check-label" for="enable_predictive_analytics">
                                                Analytics Preditivo
                                            </label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="enable_anomaly_detection"
                                                name="enable_anomaly_detection"
                                                {{ $settings['enable_anomaly_detection'] ?? true ? 'checked' : '' }}>
                                            <label class="form-check-label" for="enable_anomaly_detection">
                                                Detecção de Anomalias
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="enable_customer_insights"
                                                name="enable_customer_insights"
                                                {{ $settings['enable_customer_insights'] ?? true ? 'checked' : '' }}>
                                            <label class="form-check-label" for="enable_customer_insights">
                                                Insights de Clientes
                                            </label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox"
                                                id="enable_financial_forecasting" name="enable_financial_forecasting"
                                                {{ $settings['enable_financial_forecasting'] ?? true ? 'checked' : '' }}>
                                            <label class="form-check-label" for="enable_financial_forecasting">
                                                Previsão Financeira
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Salvar Configurações
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Backup Settings -->
            <div class="tab-pane fade" id="backup" role="tabpanel">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Configurações de Backup</h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.settings.backup.update') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="enable_auto_backup"
                                                name="enable_auto_backup"
                                                {{ $settings['enable_auto_backup'] ?? true ? 'checked' : '' }}>
                                            <label class="form-check-label" for="enable_auto_backup">
                                                Habilitar Backup Automático
                                            </label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="backup_frequency" class="form-label">Frequência de Backup</label>
                                        <select class="form-select" id="backup_frequency" name="backup_frequency"
                                            required>
                                            <option value="daily"
                                                {{ ($settings['backup_frequency'] ?? 'daily') == 'daily' ? 'selected' : '' }}>
                                                Diário</option>
                                            <option value="weekly"
                                                {{ ($settings['backup_frequency'] ?? '') == 'weekly' ? 'selected' : '' }}>
                                                Semanal</option>
                                            <option value="monthly"
                                                {{ ($settings['backup_frequency'] ?? '') == 'monthly' ? 'selected' : '' }}>
                                                Mensal</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="backup_time" class="form-label">Horário do Backup</label>
                                        <input type="time" class="form-control" id="backup_time" name="backup_time"
                                            value="{{ $settings['backup_time'] ?? '02:00' }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="backup_retention_days" class="form-label">Retenção (dias)</label>
                                        <input type="number" class="form-control" id="backup_retention_days"
                                            name="backup_retention_days"
                                            value="{{ $settings['backup_retention_days'] ?? '30' }}" min="1"
                                            max="365" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="backup_storage_driver" class="form-label">Driver de
                                            Armazenamento</label>
                                        <select class="form-select" id="backup_storage_driver"
                                            name="backup_storage_driver" required>
                                            <option value="local"
                                                {{ ($settings['backup_storage_driver'] ?? 'local') == 'local' ? 'selected' : '' }}>
                                                Local</option>
                                            <option value="s3"
                                                {{ ($settings['backup_storage_driver'] ?? '') == 's3' ? 'selected' : '' }}>
                                                Amazon S3</option>
                                            <option value="ftp"
                                                {{ ($settings['backup_storage_driver'] ?? '') == 'ftp' ? 'selected' : '' }}>
                                                FTP</option>
                                            <option value="dropbox"
                                                {{ ($settings['backup_storage_driver'] ?? '') == 'dropbox' ? 'selected' : '' }}>
                                                Dropbox</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="backup_encryption"
                                                name="backup_encryption"
                                                {{ $settings['backup_encryption'] ?? true ? 'checked' : '' }}>
                                            <label class="form-check-label" for="backup_encryption">
                                                Criptografar Backups
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Salvar Configurações
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Entities Management -->
            <div class="tab-pane fade" id="entities" role="tabpanel">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card shadow mb-4">
                            <div class="card-body text-center">
                                <i class="bi bi-tags fs-1 text-primary mb-3"></i>
                                <h5 class="card-title">Categorias</h5>
                                <p class="card-text">Gerenciar categorias do sistema</p>
                                <h3 class="text-primary">{{ $categories }}</h3>
                                <a href="{{ route('admin.categories.index') }}" class="btn btn-primary">
                                    <i class="bi bi-gear me-1"></i> Gerenciar
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow mb-4">
                            <div class="card-body text-center">
                                <i class="bi bi-briefcase fs-1 text-success mb-3"></i>
                                <h5 class="card-title">Áreas de Atividade</h5>
                                <p class="card-text">Gerenciar áreas de atividade</p>
                                <h3 class="text-success">{{ $activities }}</h3>
                                <a href="{{ route('admin.activities.index') }}" class="btn btn-success">
                                    <i class="bi bi-gear me-1"></i> Gerenciar
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow mb-4">
                            <div class="card-body text-center">
                                <i class="bi bi-person-workspace fs-1 text-info mb-3"></i>
                                <h5 class="card-title">Profissões</h5>
                                <p class="card-text">Gerenciar profissões do sistema</p>
                                <h3 class="text-info">{{ $professions }}</h3>
                                <a href="{{ route('admin.professions.index') }}" class="btn btn-info">
                                    <i class="bi bi-gear me-1"></i> Gerenciar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card shadow mb-4">
                            <div class="card-body text-center">
                                <i class="bi bi-credit-card fs-1 text-warning mb-3"></i>
                                <h5 class="card-title">Planos</h5>
                                <p class="card-text">Gerenciar planos de assinatura</p>
                                <h3 class="text-warning">{{ $plans }}</h3>
                                <a href="{{ route('admin.plans.index') }}" class="btn btn-warning">
                                    <i class="bi bi-gear me-1"></i> Gerenciar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Import Settings Modal -->
        <div class="modal fade" id="importModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Importar Configurações</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('admin.settings.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="settings_file" class="form-label">Arquivo de Configurações (JSON)</label>
                                <input type="file" class="form-control" id="settings_file" name="settings_file"
                                    accept=".json" required>
                            </div>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Atenção: Isso substituirá todas as configurações atuais.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Importar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function testEmail() {
                const testEmail = prompt('Digite o email para teste:');
                if (testEmail) {
                    fetch('{{ route('admin.settings.email.test') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                test_email: testEmail
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Email de teste enviado com sucesso!');
                            } else {
                                alert('Erro: ' + data.message);
                            }
                        })
                        .catch(error => {
                            alert('Erro ao enviar email de teste.');
                        });
                }
            }

            function testPayment() {
                if (confirm('Deseja testar a configuração de pagamento?')) {
                    fetch('{{ route('admin.settings.payment.test') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Configuração de pagamento testada com sucesso!');
                            } else {
                                alert('Erro: ' + data.message);
                            }
                        })
                        .catch(error => {
                            alert('Erro ao testar configuração de pagamento.');
                        });
                }
            }

            function exportSettings() {
                window.open('{{ route('admin.settings.export') }}', '_blank');
            }

            function clearCache() {
                if (confirm('Tem certeza que deseja limpar o cache do sistema?')) {
                    fetch('{{ route('admin.settings.clear-cache') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Cache limpo com sucesso!');
                                location.reload();
                            } else {
                                alert('Erro: ' + data.message);
                            }
                        })
                        .catch(error => {
                            alert('Erro ao limpar cache.');
                        });
                }
            }

            // Auto-save functionality (optional)
            let autoSaveTimer;
            document.querySelectorAll('form input, form select, form textarea').forEach(element => {
                element.addEventListener('change', function() {
                    clearTimeout(autoSaveTimer);
                    autoSaveTimer = setTimeout(() => {
                        // Implement auto-save if needed
                        console.log('Auto-saving...');
                    }, 2000);
                });
            });
        </script>
    @endpush
@endsection
