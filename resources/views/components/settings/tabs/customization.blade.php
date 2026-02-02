@props(['activeTab', 'tabs'])

<div class="tab-pane fade {{ $activeTab === 'customization' ? 'show active' : '' }}" id="customization">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0">
            <h3 class="h5 mb-0">Personalização</h3>
            <p class="text-muted small mb-0 mt-1">Personalize a aparência do sistema</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('settings.customization.update') }}">
                @csrf
                
                <!-- Tema do Sistema -->
                <div class="mb-4">
                    <h5 class="h6 text-primary mb-3">Cores do Sistema</h5>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="primary_color" class="form-label">Cor Primária</label>
                            <input type="color" class="form-control form-control-color w-100" id="primary_color" name="primary_color"
                                   value="{{ old('primary_color', $tabs['customization']['data']['user_settings']['primary_color'] ?? '#0066cc') }}">
                            <small class="form-text text-muted">
                                Cor principal do sistema
                            </small>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Layout e Densidade -->
                <div class="mb-4">
                    <h5 class="h6 text-primary mb-3">Layout</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="layout_density" class="form-label">Densidade do Layout</label>
                            <select class="form-select" id="layout_density" name="layout_density">
                                <option value="compact" {{ old('layout_density', $tabs['customization']['data']['user_settings']['layout_density'] ?? 'normal') === 'compact' ? 'selected' : '' }}>
                                    Compacto
                                </option>
                                <option value="normal" {{ old('layout_density', $tabs['customization']['data']['user_settings']['layout_density'] ?? 'normal') === 'normal' ? 'selected' : '' }}>
                                    Normal
                                </option>
                                <option value="spacious" {{ old('layout_density', $tabs['customization']['data']['user_settings']['layout_density'] ?? 'normal') === 'spacious' ? 'selected' : '' }}>
                                    Espaçoso
                                </option>
                            </select>
                            <small class="form-text text-muted">
                                Espaçamento entre elementos da interface
                            </small>
                        </div>
                        <div class="col-md-6">
                            <label for="sidebar_position" class="form-label">Posição da Sidebar</label>
                            <select class="form-select" id="sidebar_position" name="sidebar_position">
                                <option value="left" {{ old('sidebar_position', $tabs['customization']['data']['user_settings']['sidebar_position'] ?? 'left') === 'left' ? 'selected' : '' }}>
                                    Esquerda
                                </option>
                                <option value="right" {{ old('sidebar_position', $tabs['customization']['data']['user_settings']['sidebar_position'] ?? 'left') === 'right' ? 'selected' : '' }}>
                                    Direita
                                </option>
                            </select>
                            <small class="form-text text-muted">
                                Posição do menu lateral
                            </small>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Animações e Sons -->
                <div class="mb-4">
                    <h5 class="h6 text-primary mb-3">Animações e Sons</h5>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="animations_enabled" name="animations_enabled"
                               value="1" {{ old('animations_enabled', $tabs['customization']['data']['user_settings']['animations_enabled'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="animations_enabled">
                            Animações ativadas
                        </label>
                        <small class="form-text text-muted d-block">
                            Transições suaves entre páginas e elementos
                        </small>
                    </div>
                    
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="sound_enabled" name="sound_enabled"
                               value="1" {{ old('sound_enabled', $tabs['customization']['data']['user_settings']['sound_enabled'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="sound_enabled">
                            Sons de notificação
                        </label>
                        <small class="form-text text-muted d-block">
                            Sons para alertas e notificações importantes
                        </small>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Salvar Alterações
                    </button>
                    <button type="button" class="btn btn-outline-secondary ms-2" onclick="resetCustomization()">
                        <i class="bi bi-arrow-clockwise me-2"></i>Redefinir para Padrão
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
