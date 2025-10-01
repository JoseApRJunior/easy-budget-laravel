<div class="space-y-6">
    <!-- Informações da Empresa -->
    <div class="bg-white rounded-lg border p-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="bi bi-building mr-2 text-blue-600"></i>
            Empresa
        </h3>

        <form class="space-y-4" action="{{ route('settings.general.update') }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Nome da Empresa -->
            <div>
                <label for="company_name_mobile" class="block text-sm font-medium text-gray-700 mb-1">
                    Nome da Empresa *
                </label>
                <input
                    type="text"
                    id="company_name_mobile"
                    name="company_name"
                    value="{{ $systemSettings['settings']->company_name ?? old('company_name') }}"
                    class="form-input w-full"
                    required
                >
            </div>

            <!-- E-mail de Contato -->
            <div>
                <label for="contact_email_mobile" class="block text-sm font-medium text-gray-700 mb-1">
                    E-mail de Contato *
                </label>
                <input
                    type="email"
                    id="contact_email_mobile"
                    name="contact_email"
                    value="{{ $systemSettings['settings']->contact_email ?? old('contact_email') }}"
                    class="form-input w-full"
                    required
                >
            </div>

            <!-- Moeda -->
            <div>
                <label for="currency_mobile" class="block text-sm font-medium text-gray-700 mb-1">
                    Moeda *
                </label>
                <select id="currency_mobile" name="currency" class="form-select w-full" required>
                    <option value="BRL" {{ ($systemSettings['settings']->currency ?? 'BRL') === 'BRL' ? 'selected' : '' }}>
                        Real (R$)
                    </option>
                    <option value="USD" {{ ($systemSettings['settings']->currency ?? 'BRL') === 'USD' ? 'selected' : '' }}>
                        Dólar ($)
                    </option>
                    <option value="EUR" {{ ($systemSettings['settings']->currency ?? 'BRL') === 'EUR' ? 'selected' : '' }}>
                        Euro (€)
                    </option>
                </select>
            </div>

            <!-- Botão Salvar -->
            <button type="submit" class="btn btn-primary w-full">
                <i class="bi bi-check-lg mr-2"></i>
                Salvar Empresa
            </button>
        </form>
    </div>

    <!-- Configurações Regionais -->
    <div class="bg-white rounded-lg border p-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="bi bi-globe mr-2 text-green-600"></i>
            Regional
        </h3>

        <form class="space-y-4" action="{{ route('settings.general.update') }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Fuso Horário -->
            <div>
                <label for="timezone_mobile" class="block text-sm font-medium text-gray-700 mb-1">
                    Fuso Horário *
                </label>
                <select id="timezone_mobile" name="timezone" class="form-select w-full" required>
                    <option value="America/Sao_Paulo" {{ ($systemSettings['settings']->timezone ?? 'America/Sao_Paulo') === 'America/Sao_Paulo' ? 'selected' : '' }}>
                        São Paulo (UTC-3)
                    </option>
                    <option value="America/New_York" {{ ($systemSettings['settings']->timezone ?? 'America/Sao_Paulo') === 'America/New_York' ? 'selected' : '' }}>
                        Nova York (UTC-5)
                    </option>
                    <option value="Europe/London" {{ ($systemSettings['settings']->timezone ?? 'America/Sao_Paulo') === 'Europe/London' ? 'selected' : '' }}>
                        Londres (UTC+0)
                    </option>
                </select>
            </div>

            <!-- Idioma -->
            <div>
                <label for="language_mobile" class="block text-sm font-medium text-gray-700 mb-1">
                    Idioma *
                </label>
                <select id="language_mobile" name="language" class="form-select w-full" required>
                    <option value="pt-BR" {{ ($systemSettings['settings']->language ?? 'pt-BR') === 'pt-BR' ? 'selected' : '' }}>
                        Português (Brasil)
                    </option>
                    <option value="en-US" {{ ($systemSettings['settings']->language ?? 'pt-BR') === 'en-US' ? 'selected' : '' }}>
                        English (US)
                    </option>
                    <option value="es-ES" {{ ($systemSettings['settings']->language ?? 'pt-BR') === 'es-ES' ? 'selected' : '' }}>
                        Español
                    </option>
                </select>
            </div>

            <!-- Botão Salvar -->
            <button type="submit" class="btn btn-primary w-full">
                <i class="bi bi-check-lg mr-2"></i>
                Salvar Regional
            </button>
        </form>
    </div>

    <!-- Configurações do Sistema -->
    <div class="bg-white rounded-lg border p-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="bi bi-gear mr-2 text-orange-600"></i>
            Sistema
        </h3>

        <form class="space-y-4" action="{{ route('settings.general.update') }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Modo de Manutenção -->
            <div>
                <label class="flex items-center">
                    <input
                        type="checkbox"
                        name="maintenance_mode"
                        value="1"
                        {{ ($systemSettings['settings']->maintenance_mode ?? false) ? 'checked' : '' }}
                        class="form-checkbox"
                    >
                    <span class="ml-2 text-sm font-medium text-gray-700">Modo de Manutenção</span>
                </label>
            </div>

            <!-- Cadastro Habilitado -->
            <div>
                <label class="flex items-center">
                    <input
                        type="checkbox"
                        name="registration_enabled"
                        value="1"
                        {{ ($systemSettings['settings']->registration_enabled ?? true) ? 'checked' : '' }}
                        class="form-checkbox"
                    >
                    <span class="ml-2 text-sm font-medium text-gray-700">Cadastro Habilitado</span>
                </label>
            </div>

            <!-- Botão Salvar -->
            <button type="submit" class="btn btn-primary w-full">
                <i class="bi bi-check-lg mr-2"></i>
                Salvar Sistema
            </button>
        </form>
    </div>
</div>
