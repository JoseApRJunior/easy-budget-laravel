<div class="space-y-6">
    <!-- Tema e Aparência -->
    <div class="bg-white rounded-lg border p-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="bi bi-palette mr-2 text-indigo-600"></i>
            Aparência
        </h3>

        <form class="space-y-4" action="{{ route('settings.customization.update') }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Tema -->
            <div>
                <label for="theme_mobile" class="block text-sm font-medium text-gray-700 mb-1">
                    Tema
                </label>
                <select id="theme_mobile" name="theme" class="form-select w-full">
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
            </div>

            <!-- Cor Primária -->
            <div>
                <label for="primary_color_mobile" class="block text-sm font-medium text-gray-700 mb-1">
                    Cor Primária
                </label>
                <div class="flex items-center space-x-3">
                    <input
                        type="color"
                        id="primary_color_mobile"
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
            </div>

            <!-- Densidade do Layout -->
            <div>
                <label for="layout_density_mobile" class="block text-sm font-medium text-gray-700 mb-1">
                    Densidade
                </label>
                <select id="layout_density_mobile" name="layout_density" class="form-select w-full">
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
            </div>

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
            </div>

            <!-- Botão Salvar -->
            <button type="submit" class="btn btn-primary w-full">
                <i class="bi bi-check-lg mr-2"></i>
                Salvar Aparência
            </button>
        </form>
    </div>
</div>

<script>
// Sincroniza color picker com input de texto
document.getElementById('primary_color_mobile')?.addEventListener('input', function() {
    const textInput = document.querySelector('input[type="text"][pattern*="#"]');
    if (textInput) {
        textInput.value = this.value;
    }
});
</script>
