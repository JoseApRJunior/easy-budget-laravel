@props([
    'variables',
    'selectedVariables' => [],
    'onVariableSelect' => null,
    'compact' => false,
    'showCounts' => true
])

@php
    $totalVariables = array_sum(array_map('count', $variables));
@endphp

<div class="variable-selector {{ $compact ? 'compact' : '' }}">
    @if(!$compact)
        <div class="selector-header mb-4">
            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                </svg>
                Variáveis Disponíveis
                @if($showCounts)
                    <span class="ml-2 text-sm font-normal text-gray-500">
                        ({{ $totalVariables }} variáveis)
                    </span>
                @endif
            </h3>
            @if($selectedVariables)
                <p class="text-sm text-gray-600 mt-1">
                    {{ count($selectedVariables) }} variáveis selecionadas
                </p>
            @endif
        </div>
    @endif

    <div class="categories-grid {{ $compact ? 'grid-cols-2' : 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3' }} gap-4">
        @foreach($variables as $category => $categoryVariables)
            @if(count($categoryVariables) > 0)
                <div class="category-section">
                    <div class="category-header {{ $compact ? 'mb-2' : 'mb-3' }}">
                        <h4 class="font-medium text-gray-700 flex items-center {{ $compact ? 'text-sm' : '' }}">
                            <span class="capitalize">{{ $category }}</span>
                            @if($showCounts)
                                <span class="ml-2 text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
                                    {{ count($categoryVariables) }}
                                </span>
                            @endif
                        </h4>
                    </div>

                    <div class="variables-list {{ $compact ? 'space-y-1' : 'space-y-2' }}">
                        @foreach($categoryVariables as $variable => $description)
                            <div class="variable-item {{ in_array($variable, $selectedVariables) ? 'selected' : '' }}">
                                <button type="button"
                                        onclick="{{ $onVariableSelect ? $onVariableSelect . '(\'' . $variable . '\')' : 'selectVariable(\'' . $variable . '\')' }}"
                                        class="variable-btn w-full text-left {{ $compact ? 'p-2' : 'p-3' }} bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded {{ $compact ? 'text-sm' : '' }} transition-all duration-200 group">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2">
                                                <code class="text-blue-600 font-mono {{ $compact ? 'text-xs' : '' }}">
                                                    {{ '{{' . $variable . '}}' }}
                                                </code>
                                                @if(in_array($variable, $selectedVariables))
                                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                @endif
                                            </div>
                                            @if(!$compact)
                                                <div class="text-xs text-gray-600 mt-1">{{ $description }}</div>
                                            @endif
                                        </div>

                                        <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </button>

                                @if(!$compact && in_array($variable, $selectedVariables))
                                    <div class="mt-1 ml-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-green-100 text-green-800">
                                            Selecionada
                                        </span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    @if($compact && $selectedVariables)
        <div class="selected-variables mt-3 pt-3 border-t border-gray-200">
            <div class="text-xs font-medium text-gray-700 mb-2">Selecionadas:</div>
            <div class="flex flex-wrap gap-1">
                @foreach($selectedVariables as $variable)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-800">
                        {{ $variable }}
                        <button type="button"
                                onclick="removeVariable('{{ $variable }}')"
                                class="ml-1 text-blue-600 hover:text-blue-800">
                            ×
                        </button>
                    </span>
                @endforeach
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
function selectVariable(variable) {
    // Função padrão para seleção de variável
    const variableCode = '{{' + variable + '}}';

    // Se houver um editor TinyMCE ativo, inserir a variável
    if (window.tinymce && window.tinymce.activeEditor) {
        window.tinymce.activeEditor.insertContent(variableCode);
    } else {
        // Caso contrário, copiar para área de transferência
        navigator.clipboard.writeText(variableCode).then(() => {
            showNotification('Variável copiada: ' + variableCode, 'success');
        }).catch(() => {
            // Fallback para navegadores antigos
            const textArea = document.createElement('textarea');
            textArea.value = variableCode;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showNotification('Variável copiada: ' + variableCode, 'success');
        });
    }
}

function removeVariable(variable) {
    // Implementar lógica para remover variável selecionada
    console.log('Removendo variável:', variable);
}

function showNotification(message, type = 'info') {
    // Implementar sistema de notificações se necessário
    if (type === 'success') {
        console.log('✓ ' + message);
    } else {
        console.log('ℹ ' + message);
    }
}

// Função global para permitir customização externa
window.VariableSelector = {
    selectVariable,
    removeVariable
};
</script>
@endpush

@push('styles')
<style>
.variable-selector {
    /* Estilos base */
}

.variable-btn {
    position: relative;
    transition: all 0.2s ease-in-out;
}

.variable-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    border-color: #d1d5db;
}

.variable-item.selected .variable-btn {
    background-color: #dbeafe;
    border-color: #3b82f6;
}

.variable-item.selected {
    background-color: #eff6ff;
    border-radius: 0.375rem;
    padding: 0.25rem;
    margin: -0.25rem;
}

.categories-grid {
    display: grid;
    gap: 1rem;
}

.category-section {
    /* Estilos para cada categoria */
}

.category-header {
    /* Estilos para cabeçalho de categoria */
}

.variables-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.variable-item {
    transition: all 0.2s ease-in-out;
}

.variable-item:hover {
    transform: translateX(2px);
}

/* Modo compacto */
.variable-selector.compact .categories-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}

.variable-selector.compact .category-header {
    margin-bottom: 0.5rem;
}

.variable-selector.compact .variables-list {
    gap: 0.25rem;
}

.variable-selector.compact .variable-btn {
    padding: 0.5rem;
    font-size: 0.875rem;
}

/* Responsividade */
@media (max-width: 768px) {
    .categories-grid {
        grid-template-columns: 1fr;
    }

    .variable-selector.compact .categories-grid {
        grid-template-columns: 1fr;
    }
}

/* Animações */
@keyframes variableSelect {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.variable-item.selected {
    animation: variableSelect 0.3s ease-in-out;
}

/* Estados de loading */
.variable-loading {
    opacity: 0.6;
    pointer-events: none;
}

.variable-loading .variable-btn {
    position: relative;
}

.variable-loading .variable-btn::after {
    content: '';
    position: absolute;
    top: 50%;
    right: 0.5rem;
    transform: translateY(-50%);
    width: 12px;
    height: 12px;
    border: 2px solid #e5e7eb;
    border-top: 2px solid #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: translateY(-50%) rotate(0deg); }
    100% { transform: translateY(-50%) rotate(360deg); }
}
</style>
@endpush
