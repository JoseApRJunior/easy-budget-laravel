<div class="space-y-6">
    <!-- Alterar Senha -->
    <div class="bg-white rounded-lg border p-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="bi bi-key mr-2 text-red-600"></i>
            Segurança
        </h3>

        <form class="space-y-4" action="{{ route('settings.security.update') }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Senha Atual -->
            <div>
                <label for="current_password_mobile" class="block text-sm font-medium text-gray-700 mb-1">
                    Senha Atual *
                </label>
                <input
                    type="password"
                    id="current_password_mobile"
                    name="current_password"
                    class="form-input w-full"
                    required
                >
            </div>

            <!-- Nova Senha -->
            <div>
                <label for="new_password_mobile" class="block text-sm font-medium text-gray-700 mb-1">
                    Nova Senha *
                </label>
                <input
                    type="password"
                    id="new_password_mobile"
                    name="new_password"
                    class="form-input w-full"
                    required
                >
            </div>

            <!-- Confirmação -->
            <div>
                <label for="new_password_confirmation_mobile" class="block text-sm font-medium text-gray-700 mb-1">
                    Confirmar Nova Senha *
                </label>
                <input
                    type="password"
                    id="new_password_confirmation_mobile"
                    name="new_password_confirmation"
                    class="form-input w-full"
                    required
                >
            </div>

            <!-- Botão Salvar -->
            <button type="submit" class="btn btn-primary w-full">
                <i class="bi bi-check-lg mr-2"></i>
                Alterar Senha
            </button>
        </form>
    </div>

    <!-- Configurações de Segurança -->
    <div class="bg-white rounded-lg border p-4">
        <h4 class="font-medium text-gray-900 mb-3">Notificações de Segurança</h4>

        <form class="space-y-3" action="{{ route('settings.security.update') }}" method="POST">
            @csrf
            @method('PUT')

            <!-- E-mail Notifications -->
            <label class="flex items-center">
                <input
                    type="checkbox"
                    name="email_notifications"
                    value="1"
                    {{ ($userSettings['settings']->email_notifications ?? true) ? 'checked' : '' }}
                    class="form-checkbox"
                >
                <span class="ml-2 text-sm text-gray-700">Notificações por E-mail</span>
            </label>

            <!-- Alertas de Segurança -->
            <label class="flex items-center">
                <input
                    type="checkbox"
                    name="security_alerts"
                    value="1"
                    {{ ($userSettings['settings']->security_alerts ?? true) ? 'checked' : '' }}
                    class="form-checkbox"
                >
                <span class="ml-2 text-sm text-gray-700">Alertas de Segurança</span>
            </label>

            <!-- Botão Salvar -->
            <button type="submit" class="btn btn-primary w-full">
                <i class="bi bi-check-lg mr-2"></i>
                Salvar Configurações
            </button>
        </form>
    </div>
</div>
