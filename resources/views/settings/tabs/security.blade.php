<div class="space-y-8">
    <!-- Alterar Senha -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="bi bi-key mr-2 text-red-600"></i>
            Alterar Senha
        </h3>

        <form class="space-y-6" action="{{ route('settings.security.update') }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Senha Atual -->
            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                    Senha Atual *
                </label>
                <input
                    type="password"
                    id="current_password"
                    name="current_password"
                    class="form-input w-full"
                    required
                >
                @error('current_password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Nova Senha -->
            <div>
                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                    Nova Senha *
                </label>
                <input
                    type="password"
                    id="new_password"
                    name="new_password"
                    class="form-input w-full"
                    required
                >
                <p class="mt-1 text-sm text-gray-500">
                    Mínimo 8 caracteres
                </p>
            </div>

            <!-- Confirmação da Nova Senha -->
            <div>
                <label for="new_password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                    Confirmar Nova Senha *
                </label>
                <input
                    type="password"
                    id="new_password_confirmation"
                    name="new_password_confirmation"
                    class="form-input w-full"
                    required
                >
            </div>

            <!-- Botão Salvar -->
            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg mr-2"></i>
                    Alterar Senha
                </button>
            </div>
        </form>
    </div>

    <!-- Configurações de Segurança -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="bi bi-shield-check mr-2 text-green-600"></i>
            Configurações de Segurança
        </h3>

        <form class="space-y-6" action="{{ route('settings.security.update') }}" method="POST">
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
                        Receber notificações sobre atividades da conta
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
                        Receber alertas sobre atividades suspeitas
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
                        Receber relatórios de atividades semanais
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

    <!-- Sessões Ativas -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="bi bi-laptop mr-2 text-purple-600"></i>
            Sessões Ativas
        </h3>

        <div class="space-y-4">
            <div class="flex items-center justify-between p-4 bg-white rounded-lg border">
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        <i class="bi bi-laptop text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Sessão Atual</p>
                        <p class="text-sm text-gray-500">{{ request()->ip() }} • {{ now()->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    Ativa
                </span>
            </div>
        </div>

        <div class="mt-4 text-sm text-gray-500">
            <p>Para encerrar esta sessão, basta fazer logout normalmente.</p>
        </div>
    </div>
</div>
