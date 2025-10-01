<div class="space-y-6">
    <!-- Configurações de Notificação -->
    <div class="bg-white rounded-lg border p-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="bi bi-bell mr-2 text-yellow-600"></i>
            Notificações
        </h3>

        <form class="space-y-4" action="{{ route('settings.notifications.update') }}" method="POST">
            @csrf
            @method('PUT')

            <!-- E-mail Notifications -->
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
                    Receber notificações sobre atividades da conta
                </p>
            </div>

            <!-- Transações -->
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
                    Alertas sobre transações importantes
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
                    Relatórios de atividades semanais
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
                    Novidades e atualizações do sistema
                </p>
            </div>

            <!-- Botão Salvar -->
            <button type="submit" class="btn btn-primary w-full">
                <i class="bi bi-check-lg mr-2"></i>
                Salvar Notificações
            </button>
        </form>
    </div>
</div>
