<div class="space-y-8">
    <!-- Informações da Empresa -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="bi bi-building mr-2 text-blue-600"></i>
            Informações da Empresa
        </h3>

        <form class="space-y-6" action="{{ route('settings.general.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nome da Empresa -->
                <div>
                    <label for="company_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nome da Empresa *
                    </label>
                    <input
                        type="text"
                        id="company_name"
                        name="company_name"
                        value="{{ $systemSettings['settings']->company_name ?? old('company_name') }}"
                        class="form-input w-full"
                        placeholder="Digite o nome da sua empresa"
                        required
                    >
                    @error('company_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- E-mail de Contato -->
                <div>
                    <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-2">
                        E-mail de Contato *
                    </label>
                    <input
                        type="email"
                        id="contact_email"
                        name="contact_email"
                        value="{{ $systemSettings['settings']->contact_email ?? old('contact_email') }}"
                        class="form-input w-full"
                        placeholder="contato@empresa.com"
                        required
                    >
                    @error('contact_email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Telefone -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                        Telefone
                    </label>
                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        value="{{ $systemSettings['settings']->phone ?? old('phone') }}"
                        class="form-input w-full"
                        placeholder="(11) 99999-9999"
                    >
                    @error('phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Website -->
                <div>
                    <label for="website" class="block text-sm font-medium text-gray-700 mb-2">
                        Website
                    </label>
                    <input
                        type="url"
                        id="website"
                        name="website"
                        value="{{ $systemSettings['settings']->website ?? old('website') }}"
                        class="form-input w-full"
                        placeholder="https://www.empresa.com"
                    >
                    @error('website')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Logo da Empresa -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Logo da Empresa
                </label>
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        @if($systemSettings['settings']->logo_url)
                            <img src="{{ $systemSettings['settings']->logo_url }}" alt="Logo" class="h-16 w-16 rounded-lg object-cover border">
                        @else
                            <div class="h-16 w-16 rounded-lg bg-gray-200 border flex items-center justify-center">
                                <i class="bi bi-building text-gray-400 text-2xl"></i>
                            </div>
                        @endif
                    </div>
                    <div>
                        <input type="file" name="logo" accept="image/*" class="form-input">
                        <p class="mt-1 text-sm text-gray-500">
                            Formatos aceitos: JPEG, PNG, GIF, WebP. Tamanho máximo: 2MB
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Configurações Regionais -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="bi bi-globe mr-2 text-green-600"></i>
            Configurações Regionais
        </h3>

        <form class="space-y-6" action="{{ route('settings.general.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Moeda -->
                <div>
                    <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">
                        Moeda *
                    </label>
                    <select
                        id="currency"
                        name="currency"
                        class="form-select w-full"
                        required
                    >
                        <option value="BRL" {{ ($systemSettings['settings']->currency ?? 'BRL') === 'BRL' ? 'selected' : '' }}>
                            Real (R$) - BRL
                        </option>
                        <option value="USD" {{ ($systemSettings['settings']->currency ?? 'BRL') === 'USD' ? 'selected' : '' }}>
                            Dólar ($) - USD
                        </option>
                        <option value="EUR" {{ ($systemSettings['settings']->currency ?? 'BRL') === 'EUR' ? 'selected' : '' }}>
                            Euro (€) - EUR
                        </option>
                    </select>
                    @error('currency')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Fuso Horário -->
                <div>
                    <label for="timezone" class="block text-sm font-medium text-gray-700 mb-2">
                        Fuso Horário *
                    </label>
                    <select
                        id="timezone"
                        name="timezone"
                        class="form-select w-full"
                        required
                    >
                        <option value="America/Sao_Paulo" {{ ($systemSettings['settings']->timezone ?? 'America/Sao_Paulo') === 'America/Sao_Paulo' ? 'selected' : '' }}>
                            São Paulo (UTC-3)
                        </option>
                        <option value="America/New_York" {{ ($systemSettings['settings']->timezone ?? 'America/Sao_Paulo') === 'America/New_York' ? 'selected' : '' }}>
                            Nova York (UTC-5)
                        </option>
                        <option value="Europe/London" {{ ($systemSettings['settings']->timezone ?? 'America/Sao_Paulo') === 'Europe/London' ? 'selected' : '' }}>
                            Londres (UTC+0)
                        </option>
                        <option value="Europe/Paris" {{ ($systemSettings['settings']->timezone ?? 'America/Sao_Paulo') === 'Europe/Paris' ? 'selected' : '' }}>
                            Paris (UTC+1)
                        </option>
                        <option value="Asia/Tokyo" {{ ($systemSettings['settings']->timezone ?? 'America/Sao_Paulo') === 'Asia/Tokyo' ? 'selected' : '' }}>
                            Tóquio (UTC+9)
                        </option>
                    </select>
                    @error('timezone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Idioma -->
                <div>
                    <label for="language" class="block text-sm font-medium text-gray-700 mb-2">
                        Idioma *
                    </label>
                    <select
                        id="language"
                        name="language"
                        class="form-select w-full"
                        required
                    >
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
                    @error('language')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </form>
    </div>

    <!-- Endereço da Empresa -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="bi bi-geo-alt mr-2 text-purple-600"></i>
            Endereço da Empresa
        </h3>

        <form class="space-y-6" action="{{ route('settings.general.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Rua -->
                <div class="md:col-span-2">
                    <label for="address_street" class="block text-sm font-medium text-gray-700 mb-2">
                        Rua
                    </label>
                    <input
                        type="text"
                        id="address_street"
                        name="address_street"
                        value="{{ $systemSettings['settings']->address_street ?? old('address_street') }}"
                        class="form-input w-full"
                        placeholder="Rua das Flores"
                    >
                </div>

                <!-- Número -->
                <div>
                    <label for="address_number" class="block text-sm font-medium text-gray-700 mb-2">
                        Número
                    </label>
                    <input
                        type="text"
                        id="address_number"
                        name="address_number"
                        value="{{ $systemSettings['settings']->address_number ?? old('address_number') }}"
                        class="form-input w-full"
                        placeholder="123"
                    >
                </div>

                <!-- Complemento -->
                <div>
                    <label for="address_complement" class="block text-sm font-medium text-gray-700 mb-2">
                        Complemento
                    </label>
                    <input
                        type="text"
                        id="address_complement"
                        name="address_complement"
                        value="{{ $systemSettings['settings']->address_complement ?? old('address_complement') }}"
                        class="form-input w-full"
                        placeholder="Sala 101"
                    >
                </div>

                <!-- Bairro -->
                <div>
                    <label for="address_neighborhood" class="block text-sm font-medium text-gray-700 mb-2">
                        Bairro
                    </label>
                    <input
                        type="text"
                        id="address_neighborhood"
                        name="address_neighborhood"
                        value="{{ $systemSettings['settings']->address_neighborhood ?? old('address_neighborhood') }}"
                        class="form-input w-full"
                        placeholder="Centro"
                    >
                </div>

                <!-- Cidade -->
                <div>
                    <label for="address_city" class="block text-sm font-medium text-gray-700 mb-2">
                        Cidade
                    </label>
                    <input
                        type="text"
                        id="address_city"
                        name="address_city"
                        value="{{ $systemSettings['settings']->address_city ?? old('address_city') }}"
                        class="form-input w-full"
                        placeholder="São Paulo"
                    >
                </div>

                <!-- Estado -->
                <div>
                    <label for="address_state" class="block text-sm font-medium text-gray-700 mb-2">
                        Estado
                    </label>
                    <input
                        type="text"
                        id="address_state"
                        name="address_state"
                        value="{{ $systemSettings['settings']->address_state ?? old('address_state') }}"
                        class="form-input w-full"
                        placeholder="SP"
                    >
                </div>

                <!-- CEP -->
                <div>
                    <label for="address_zip_code" class="block text-sm font-medium text-gray-700 mb-2">
                        CEP
                    </label>
                    <input
                        type="text"
                        id="address_zip_code"
                        name="address_zip_code"
                        value="{{ $systemSettings['settings']->address_zip_code ?? old('address_zip_code') }}"
                        class="form-input w-full"
                        placeholder="01234-567"
                    >
                </div>

                <!-- País -->
                <div>
                    <label for="address_country" class="block text-sm font-medium text-gray-700 mb-2">
                        País
                    </label>
                    <input
                        type="text"
                        id="address_country"
                        name="address_country"
                        value="{{ $systemSettings['settings']->address_country ?? old('address_country') }}"
                        class="form-input w-full"
                        placeholder="Brasil"
                    >
                </div>
            </div>
        </form>
    </div>

    <!-- Configurações do Sistema -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="bi bi-gear mr-2 text-orange-600"></i>
            Configurações do Sistema
        </h3>

        <form class="space-y-6" action="{{ route('settings.general.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                    <p class="mt-1 text-sm text-gray-500">
                        Quando ativado, o sistema ficará indisponível para usuários comuns
                    </p>
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
                    <p class="mt-1 text-sm text-gray-500">
                        Permite novos usuários se cadastrarem no sistema
                    </p>
                </div>

                <!-- Verificação de E-mail Obrigatória -->
                <div>
                    <label class="flex items-center">
                        <input
                            type="checkbox"
                            name="email_verification_required"
                            value="1"
                            {{ ($systemSettings['settings']->email_verification_required ?? true) ? 'checked' : '' }}
                            class="form-checkbox"
                        >
                        <span class="ml-2 text-sm font-medium text-gray-700">Verificação de E-mail Obrigatória</span>
                    </label>
                    <p class="mt-1 text-sm text-gray-500">
                        Novos usuários devem verificar o e-mail antes de usar o sistema
                    </p>
                </div>

                <!-- Tempo de Vida da Sessão -->
                <div>
                    <label for="session_lifetime" class="block text-sm font-medium text-gray-700 mb-2">
                        Tempo de Vida da Sessão (minutos)
                    </label>
                    <input
                        type="number"
                        id="session_lifetime"
                        name="session_lifetime"
                        value="{{ $systemSettings['settings']->session_lifetime ?? 120 }}"
                        class="form-input w-full"
                        min="5"
                        max="10080"
                    >
                    <p class="mt-1 text-sm text-gray-500">
                        Tempo máximo de uma sessão ativa (5 min - 1 semana)
                    </p>
                </div>
            </div>

            <!-- Mensagem de Manutenção -->
            <div x-show="document.querySelector('input[name=maintenance_mode]').checked">
                <label for="maintenance_message" class="block text-sm font-medium text-gray-700 mb-2">
                    Mensagem de Manutenção
                </label>
                <textarea
                    id="maintenance_message"
                    name="maintenance_message"
                    rows="3"
                    class="form-textarea w-full"
                    placeholder="Sistema em manutenção. Voltaremos em breve."
                >{{ $systemSettings['settings']->maintenance_message ?? '' }}</textarea>
                <p class="mt-1 text-sm text-gray-500">
                    Mensagem exibida quando o sistema estiver em manutenção
                </p>
            </div>
        </form>
    </div>

    <!-- Botões de Ação -->
    <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
        <button type="button" class="btn btn-secondary" onclick="restoreDefaults()">
            <i class="bi bi-arrow-counterclockwise mr-2"></i>
            Restaurar Padrões
        </button>
        <button type="submit" form="general-form" class="btn btn-primary">
            <i class="bi bi-check-lg mr-2"></i>
            Salvar Alterações
        </button>
    </div>
</div>

<script>
// Máscara para telefone
document.getElementById('phone')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length <= 11) {
        if (value.length <= 10) {
            value = value.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
        } else {
            value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
        }
        e.target.value = value;
    }
});

// Máscara para CEP
document.getElementById('address_zip_code')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length <= 8) {
        value = value.replace(/(\d{5})(\d{3})/, '$1-$2');
        e.target.value = value;
    }
});

// Toggle para mensagem de manutenção
document.querySelector('input[name=maintenance_mode]')?.addEventListener('change', function() {
    const messageField = document.getElementById('maintenance_message').closest('div');
    if (this.checked) {
        messageField.style.display = 'block';
    } else {
        messageField.style.display = 'none';
    }
});
</script>
