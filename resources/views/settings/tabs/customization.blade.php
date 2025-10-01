<div class="space-y-8">
    <!-- Tema e Aparência -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="bi bi-palette mr-2 text-indigo-600"></i>
            Tema e Aparência
        </h3>

        <form class="space-y-6" action="{{ route('settings.customization.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Tema -->
                <div>
                    <label for="theme" class="block text-sm font-medium text-gray-700 mb-2">
                        Tema
                    </label>
                    <select
                        id="theme"
                        name="theme"
                        class="form-select w-full"
                    >
                        <option value="light" {{ ($userSettings['settings']->theme ?? 'auto') === 'light' ? 'selected' : '' }}>
                            Claro
                        </option>
                        <option value="dark" {{ ($userSettings['settings']->theme ?? 'auto') === 'dark' ? 'selected' : '' }}>
                            Escuro
                        </option>
                        <option value="auto" {{ ($userSettings['settings']->theme ?? 'auto') === 'auto' ? 'selected' : '' }}>
                            Automático
                        </option>
                    </select>
                    <p class="mt-1 text-sm text-gray-500">
                        Escolha o tema que deseja usar na interface
                    </p>
                </div>

                <!-- Cor Primária -->
                <div>
                    <label for="primary_color" class="block text-sm font-medium text-gray-700 mb-2">
                        Cor Primária
                    </label>
                    <div class="flex items-center space-x-3">
                        <input
                            type="color"
                            id="primary_color"
                            name="primary_color"
                            value="{{ $userSettings['settings']->primary_color ?? '#3B82F6' }}"
                            class="h-10 w-16 rounded border border-gray-300"
                        >
                        <input
                            type="text"
                            value="{{ $userSettings['settings']->primary_color ?? '#3B82F6' }}"
                            class="form-input flex-1"
                            placeholder="#3B82F6"
                            pattern="^#[0-9A-Fa-f]{6}$"
                            maxlength="7"
                        >
                    </div>
                    <p class="mt-1 text-sm text-gray-500">
                        Cor principal usada nos botões e elementos destacados
                    </p>
                </div>

                <!-- Densidade do Layout -->
                <div>
                    <label for="layout_density" class="block text-sm font-medium text-gray-700 mb-2">
                        Densidade do Layout
                    </label>
                    <select
                        id="layout_density"
                        name="layout_density"
                        class="form-select w-full"
                    >
                        <option value="compact" {{ ($userSettings['settings']->layout_density ?? 'normal') === 'compact' ? 'selected' : '' }}>
                            Compacto
                        </option>
                        <option value="normal" {{ ($userSettings['settings']->layout_density ?? 'normal') === 'normal' ? 'selected' : '' }}>
                            Normal
                        </option>
                        <option value="spacious" {{ ($userSettings['settings']->layout_density ?? 'normal') === 'spacious' ? 'selected' : '' }}>
                            Espaçoso
                        </option>
                    </select>
                    <p class="mt-1 text-sm text-gray-500">
                        Controla o espaçamento entre elementos
                    </p>
                </div>

                <!-- Posição da Sidebar -->
                <div>
                    <label for="sidebar_position" class="block text-sm font-medium text-gray-700 mb-2">
                        Posição da Sidebar
                    </label>
                    <select
                        id="sidebar_position"
                        name="sidebar_position"
                        class="form-select w-full"
                    >
                        <option value="left" {{ ($userSettings['settings']->sidebar_position ?? 'left') === 'left' ? 'selected' : '' }}>
                            Esquerda
                        </option>
                        <option value="right" {{ ($userSettings['settings']->sidebar_position ?? 'left') === 'right' ? 'selected' : '' }}>
                            Direita
                        </option>
                    </select>
                    <p class="mt-1 text-sm text-gray-500">
                        Posição do menu lateral (quando aplicável)
                    </p>
                </div>
            </div>

            <!-- Opções Avançadas -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Animações -->
                <div>
                    <label class="flex items-center">
                        <input
                            type="checkbox"
                            name="animations_enabled"
                            value="1"
                            {{ ($userSettings['settings']->animations_enabled ?? true) ? 'checked' : '' }}
                            class="form-checkbox"
                        >
                        <span class="ml-2 text-sm font-medium text-gray-700">Animações</span>
                    </label>
                    <p class="mt-1 text-sm text-gray-500">
                        Habilitar animações e transições na interface
                    </p>
                </div>

                <!-- Som de Notificações -->
                <div>
                    <label class="flex items-center">
                        <input
                            type="checkbox"
                            name="sound_enabled"
                            value="1"
                            {{ ($userSettings['settings']->sound_enabled ?? true) ? 'checked' : '' }}
                            class="form-checkbox"
                        >
                        <span class="ml-2 text-sm font-medium text-gray-700">Som de Notificações</span>
                    </label>
                    <p class="mt-1 text-sm text-gray-500">
                        Reproduzir sons para notificações (quando suportado)
                    </p>
                </div>
            </div>

            <!-- Botão Salvar -->
            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg mr-2"></i>
                    Salvar Personalização
                </button>
            </div>
        </form>
    </div>

    <!-- Preview do Tema -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="bi bi-eye mr-2 text-purple-600"></i>
            Preview do Tema
        </h3>

        <div class="border rounded-lg p-4 bg-white">
            <div class="flex items-center space-x-4 mb-4">
                <div class="w-8 h-8 rounded-full bg-blue-600"></div>
                <div>
                    <h4 class="font-medium text-gray-900">Exemplo de Card</h4>
                    <p class="text-sm text-gray-600">Este é um exemplo de como os elementos aparecem com suas configurações</p>
                </div>
            </div>
            <div class="flex space-x-2">
                <button class="btn btn-primary btn-sm">Botão Primário</button>
                <button class="btn btn-secondary btn-sm">Botão Secundário</button>
            </div>
        </div>

        <p class="mt-4 text-sm text-gray-500">
            As alterações de tema são aplicadas automaticamente. Você pode ver uma prévia acima.
        </p>
    </div>
</div>

<script>
// Sincroniza color picker com input de texto
document.getElementById('primary_color')?.addEventListener('input', function() {
    const textInput = document.querySelector('input[type="text"][pattern*="#[0-9A-Fa-f]"]');
    if (textInput) {
        textInput.value = this.value;
    }
});

// Sincroniza input de texto com color picker
document.querySelector('input[type="text"][pattern*="#[0-9A-Fa-f]"]')?.addEventListener('input', function() {
    const colorPicker = document.getElementById('primary_color');
    if (colorPicker && /^#[0-9A-Fa-f]{6}$/.test(this.value)) {
        colorPicker.value = this.value;
    }
});
</script>
