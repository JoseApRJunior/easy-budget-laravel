<div class="space-y-8">
    <!-- Configurações de Notificação -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="bi bi-bell mr-2 text-yellow-600"></i>
            Configurações de Notificação
        </h3>

        <form class="space-y-6" action="{{ route('settings.notifications.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Notificações por E-mail -->
                <div>
                    <label class="flex items-center">
                        <input
                            type="checkbox"
                            name="email_notifications"
                            value="1"
                            {{ ($userSettings['settings']->email_notifications ?? true) ? 'checked' : '' }}
                            class="form-checkbox"
                        >
                        <span class="ml-2 text-sm font-medium text-gray-700">Notificações por E-mail</span>
                    </label>
                    <p class="mt-1 text-sm text-gray-500">
                        Receber notificações sobre atividades da conta por e-mail
                    </p>
                </div>

                <!-- Notificações de Transações -->
                <div>
                    <label class="flex items-center">
                        <input
                            type="checkbox"
                            name="transaction_notifications"
                            value="1"
                            {{ ($userSettings['settings']->transaction_notifications ?? true) ? 'checked' : '' }}
                            class="form-checkbox"
                        >
                        <span class="ml-2 text-sm font-medium text-gray-700">Notificações de Transações</span>
                    </label>
                    <p class="mt-1 text-sm text-gray-500">
                        Receber alertas sobre transações importantes
                    </p>
                </div>

                <!-- Relatórios Semanais -->
                <div>
                    <label class="flex items-center">
                        <input
                            type="checkbox"
                            name="weekly_reports"
                            value="1"
                            {{ ($userSettings['settings']->weekly_reports ?? false) ? 'checked' : '' }}
                            class="form-checkbox"
                        >
                        <span class="ml-2 text-sm font-medium text-gray-700">Relatórios Semanais</span>
                    </label>
                    <p class="mt-1 text-sm text-gray-500">
                        Receber relatórios de atividades semanais por e-mail
                    </p>
                </div>

                <!-- Alertas de Segurança -->
                <div>
                    <label class="flex items-center">
                        <input
                            type="checkbox"
                            name="security_alerts"
                            value="1"
                            {{ ($userSettings['settings']->security_alerts ?? true) ? 'checked' : '' }}
                            class="form-checkbox"
                        >
                        <span class="ml-2 text-sm font-medium text-gray-700">Alertas de Segurança</span>
                    </label>
                    <p class="mt-1 text-sm text-gray-500">
                        Receber alertas sobre atividades suspeitas na conta
                    </p>
                </div>

                <!-- Newsletter -->
                <div>
                    <label class="flex items-center">
                        <input
                            type="checkbox"
                            name="newsletter_subscription"
                            value="1"
                            {{ ($userSettings['settings']->newsletter_subscription ?? false) ? 'checked' : '' }}
                            class="form-checkbox"
                        >
                        <span class="ml-2 text-sm font-medium text-gray-700">Newsletter</span>
                    </label>
                    <p class="mt-1 text-sm text-gray-500">
                        Receber novidades e atualizações do sistema
                    </p>
                </div>

                <!-- Push Notifications -->
                <div>
                    <label class="flex items-center">
                        <input
                            type="checkbox"
                            name="push_notifications"
                            value="1"
                            {{ ($userSettings['settings']->push_notifications ?? false) ? 'checked' : '' }}
                            class="form-checkbox"
                        >
                        <span class="ml-2 text-sm font-medium text-gray-700">Notificações Push</span>
                    </label>
                    <p class="mt-1 text-sm text-gray-500">
                        Receber notificações push no navegador (quando suportado)
                    </p>
                </div>
            </div>

            <!-- Botão Salvar -->
            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg mr-2"></i>
                    Salvar Configurações
                </button>
            </div>
        </form>
    </div>

    <!-- Teste de Notificações -->
    <div class="bg-blue-50 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="bi bi-send mr-2 text-blue-600"></i>
            Teste de Notificações
        </h3>

        <p class="text-sm text-gray-600 mb-4">
            Envie um e-mail de teste para verificar se suas configurações de notificação estão funcionando corretamente.
        </p>

        <button type="button" class="btn btn-outline">
            <i class="bi bi-envelope mr-2"></i>
            Enviar E-mail de Teste
        </button>
    </div>
</div>
