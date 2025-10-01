@props([
    'template',
    'showStats' => true,
    'compact' => false
])

@php
    $stats = $template->getUsageStats();
@endphp

<div class="template-card bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-all duration-200 {{ $compact ? 'p-4' : 'p-6' }}">
    <!-- Cabeçalho do card -->
    <div class="card-header {{ $compact ? 'mb-3' : 'mb-4' }}">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 {{ $compact ? 'text-base' : '' }}">
                    {{ $template->name }}
                </h3>
                @if(!$compact)
                    <p class="text-sm text-gray-500 mt-1">{{ $template->subject }}</p>
                @endif
            </div>
            <div class="flex items-center space-x-2 ml-4">
                @if($template->is_active)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Ativo
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Inativo
                    </span>
                @endif
            </div>
        </div>

        <!-- Categoria e informações básicas -->
        <div class="mt-3 flex items-center justify-between">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                @if($template->category === 'transactional') bg-blue-100 text-blue-800
                @elseif($template->category === 'promotional') bg-purple-100 text-purple-800
                @elseif($template->category === 'notification') bg-green-100 text-green-800
                @else bg-gray-100 text-gray-800 @endif">
                @if($template->category === 'transactional')
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                @elseif($template->category === 'promotional')
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                @elseif($template->category === 'notification')
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5h5m-1-5v5m-9-5h.01M12 2v4m6.364 2.364l-2.828 2.828M6.464 6.464L3.636 9.192M12 12h.01M17 12h.01M7 12h.01"></path>
                    </svg>
                @else
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                @endif
                {{ ucfirst($template->category) }}
            </span>

            @if(!$compact)
                <div class="flex items-center space-x-2 text-xs text-gray-500">
                    <span>{{ $template->created_at->format('d/m/Y') }}</span>
                    @if($template->is_system)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-yellow-100 text-yellow-800">
                            Sistema
                        </span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Estatísticas -->
    @if($showStats && !$compact)
        <div class="card-stats mb-4">
            <div class="grid grid-cols-3 gap-4 text-center">
                <div class="stat-item">
                    <div class="text-lg font-semibold text-gray-900">{{ number_format($stats['total_sent']) }}</div>
                    <div class="text-xs text-gray-500">Enviados</div>
                </div>
                <div class="stat-item">
                    <div class="text-lg font-semibold text-gray-900">{{ number_format($stats['open_rate'], 1) }}%</div>
                    <div class="text-xs text-gray-500">Abertura</div>
                </div>
                <div class="stat-item">
                    <div class="text-lg font-semibold text-gray-900">{{ number_format($stats['click_rate'], 1) }}%</div>
                    <div class="text-xs text-gray-500">Cliques</div>
                </div>
            </div>
        </div>
    @endif

    <!-- Variáveis utilizadas (modo compacto) -->
    @if($template->variables && count($template->variables) > 0 && $compact)
        <div class="variables-preview mb-3">
            <div class="flex flex-wrap gap-1">
                @foreach(array_slice($template->variables, 0, 3) as $variable)
                    <code class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-mono bg-gray-100 text-gray-800">
                        {{ $variable }}
                    </code>
                @endforeach
                @if(count($template->variables) > 3)
                    <span class="text-xs text-gray-500">+{{ count($template->variables) - 3 }}</span>
                @endif
            </div>
        </div>
    @endif

    <!-- Ações -->
    <div class="card-actions">
        <div class="flex items-center justify-between">
            <div class="flex space-x-2">
                <a href="{{ route('email-templates.show', $template) }}"
                   class="btn btn-sm {{ $compact ? 'btn-ghost' : 'btn-primary' }}"
                   title="Visualizar">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    @if(!$compact) Visualizar @endif
                </a>

                @if($template->canBeEdited())
                    <a href="{{ route('email-templates.edit', $template) }}"
                       class="btn btn-sm {{ $compact ? 'btn-ghost' : 'btn-secondary' }}"
                       title="Editar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        @if(!$compact) Editar @endif
                    </a>
                @endif
            </div>

            <div class="flex space-x-1">
                @if($template->canBeDeleted())
                    <form method="POST" action="{{ route('email-templates.destroy', $template) }}" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="btn btn-sm btn-ghost text-red-600 hover:text-red-700"
                                title="Excluir"
                                onclick="return confirm('Tem certeza que deseja excluir este template?')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </form>
                @endif

                <form method="POST" action="{{ route('email-templates.duplicate', $template) }}" class="inline">
                    @csrf
                    <button type="submit"
                            class="btn btn-sm btn-ghost"
                            title="Duplicar"
                            onclick="return confirm('Deseja duplicar este template?')">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Indicadores visuais -->
    @if($template->is_system)
        <div class="absolute top-2 right-2">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-yellow-100 text-yellow-800">
                Sistema
            </span>
        </div>
    @endif
</div>

@push('styles')
<style>
.btn {
    @apply inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md transition-colors duration-200;
}

.btn-primary {
    @apply bg-blue-600 text-white hover:bg-blue-700;
}

.btn-secondary {
    @apply bg-gray-200 text-gray-900 hover:bg-gray-300;
}

.btn-ghost {
    @apply text-gray-600 hover:text-gray-900 hover:bg-gray-100;
}

.btn-sm {
    @apply px-2 py-1 text-xs;
}

.template-card {
    position: relative;
    transition: all 0.2s ease-in-out;
}

.template-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.stat-item {
    padding: 0.5rem;
    border-radius: 0.375rem;
    background: #f9fafb;
}

.variables-preview code {
    font-size: 0.75rem;
    padding: 0.125rem 0.375rem;
}
</style>
@endpush
