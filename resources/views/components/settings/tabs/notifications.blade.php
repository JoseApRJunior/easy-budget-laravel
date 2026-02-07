@props(['activeTab', 'tabs'])

<div class="tab-pane fade {{ $activeTab === 'notifications' ? 'show active' : '' }}" id="notificacoes">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0">
            <h3 class="h5 mb-0">Notificações</h3>
            <p class="text-muted small mb-0 mt-1">Configure suas preferências de notificação</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('settings.notifications.update') }}">
                @csrf

                <!-- Notificações por Email -->
                <div class="mb-4">
                    <h5 class="h6 text-primary mb-3">Notificações por Email</h5>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications"
                               value="1" {{ old('email_notifications', $tabs['notifications']['data']['user_settings']['email_notifications'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="email_notifications">
                            Receber notificações por email
                        </label>
                        <small class="form-text text-muted d-block">
                            Notificações importantes sobre seu negócio
                        </small>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="transaction_notifications" name="transaction_notifications"
                               value="1" {{ old('transaction_notifications', $tabs['notifications']['data']['user_settings']['transaction_notifications'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="transaction_notifications">
                            Notificações de transações
                        </label>
                        <small class="form-text text-muted d-block">
                            Alertas sobre novas vendas, pagamentos e alterações financeiras
                        </small>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="weekly_reports" name="weekly_reports"
                               value="1" {{ old('weekly_reports', $tabs['notifications']['data']['user_settings']['weekly_reports'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="weekly_reports">
                            Relatórios semanais
                        </label>
                        <small class="form-text text-muted d-block">
                            Resumo semanal do desempenho do seu negócio
                        </small>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="newsletter_subscription" name="newsletter_subscription"
                               value="1" {{ old('newsletter_subscription', $tabs['notifications']['data']['user_settings']['newsletter_subscription'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="newsletter_subscription">
                            Newsletter e atualizações
                        </label>
                        <small class="form-text text-muted d-block">
                            Novidades e dicas sobre como melhorar seu negócio
                        </small>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Notificações Push -->
                <div class="mb-4">
                    <h5 class="h6 text-primary mb-3">Notificações Push</h5>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="push_notifications" name="push_notifications"
                               value="1" {{ old('push_notifications', $tabs['notifications']['data']['user_settings']['push_notifications'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="push_notifications">
                            Notificações push no navegador
                        </label>
                        <small class="form-text text-muted d-block">
                            Notificações instantâneas quando você estiver usando o sistema
                        </small>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Frequência de Notificações -->
                <div class="mb-4">
                    <h5 class="h6 text-primary mb-3">Frequência de Notificações</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="notification_frequency" class="form-label">Frequência</label>
                            <select class="form-select" id="notification_frequency" name="notification_frequency">
                                <option value="immediate" {{ old('notification_frequency', $tabs['notifications']['data']['user_settings']['notification_frequency'] ?? 'immediate') === 'immediate' ? 'selected' : '' }}>
                                    Imediata
                                </option>
                                <option value="hourly" {{ old('notification_frequency', $tabs['notifications']['data']['user_settings']['notification_frequency'] ?? 'immediate') === 'hourly' ? 'selected' : '' }}>
                                    A cada hora
                                </option>
                                <option value="daily" {{ old('notification_frequency', $tabs['notifications']['data']['user_settings']['notification_frequency'] ?? 'immediate') === 'daily' ? 'selected' : '' }}>
                                    Diária
                                </option>
                                <option value="weekly" {{ old('notification_frequency', $tabs['notifications']['data']['user_settings']['notification_frequency'] ?? 'immediate') === 'weekly' ? 'selected' : '' }}>
                                    Semanal
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="notification_time" class="form-label">Horário de Envio</label>
                            <input type="time" class="form-control" id="notification_time" name="notification_time"
                                   value="{{ old('notification_time', $tabs['notifications']['data']['user_settings']['notification_time'] ?? '09:00') }}">
                            <small class="form-text text-muted">
                                Horário para notificações agendadas
                            </small>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
