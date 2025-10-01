@extends('layouts.app')

@section('title', 'Email Templates')

@section('page-title', 'Email Templates')

@section('content')
<div class="email-templates">
    <!-- Header com estatísticas -->
    <div class="stats-header mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="stat-card bg-blue-50 p-4 rounded-lg">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-500 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-blue-600">Total Templates</p>
                        <p class="text-2xl font-semibold text-blue-900">{{ $stats['total'] }}</p>
                    </div>
                </div>
            </div>

            <div class="stat-card bg-green-50 p-4 rounded-lg">
                <div class="flex items-center">
                    <div class="p-2 bg-green-500 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-600">Templates Ativos</p>
                        <p class="text-2xl font-semibold text-green-900">{{ $stats['active'] }}</p>
                    </div>
                </div>
            </div>

            <div class="stat-card bg-purple-50 p-4 rounded-lg">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-500 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-purple-600">Transacionais</p>
                        <p class="text-2xl font-semibold text-purple-900">{{ $stats['transactional'] }}</p>
                    </div>
                </div>
            </div>

            <div class="stat-card bg-orange-50 p-4 rounded-lg">
                <div class="flex items-center">
                    <div class="p-2 bg-orange-500 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-orange-600">Promocionais</p>
                        <p class="text-2xl font-semibold text-orange-900">{{ $stats['promotional'] }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros e busca -->
    <div class="filters-section mb-6">
        <div class="bg-white p-4 rounded-lg shadow-sm border">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                    <input type="text"
                           name="search"
                           value="{{ $filters['search'] ?? '' }}"
                           placeholder="Nome do template..."
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                    <select name="category" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todas</option>
                        <option value="transactional" {{ ($filters['category'] ?? '') === 'transactional' ? 'selected' : '' }}>Transacional</option>
                        <option value="promotional" {{ ($filters['category'] ?? '') === 'promotional' ? 'selected' : '' }}>Promocional</option>
                        <option value="notification" {{ ($filters['category'] ?? '') === 'notification' ? 'selected' : '' }}>Notificação</option>
                        <option value="system" {{ ($filters['category'] ?? '') === 'system' ? 'selected' : '' }}>Sistema</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="is_active" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="1" {{ ($filters['is_active'] ?? '') === '1' ? 'selected' : '' }}>Ativo</option>
                        <option value="0" {{ ($filters['is_active'] ?? '') === '0' ? 'selected' : '' }}>Inativo</option>
                    </select>
                </div>

                <div class="flex items-end space-x-2">
                    <button type="submit" class="btn btn-primary flex-1">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Filtrar
                    </button>
                    <a href="{{ route('email-templates.index') }}" class="btn btn-secondary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Ações principais -->
    <div class="actions-bar mb-6 flex justify-between items-center">
        <div class="flex space-x-3">
            <a href="{{ route('email-templates.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Novo Template
            </a>
            <button onclick="showPresetsModal()" class="btn btn-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                Templates Prontos
            </button>
        </div>

        <div class="flex items-center space-x-4">
            <div class="text-sm text-gray-600">
                Mostrando {{ $templates->count() }} de {{ $templates->total() }} templates
            </div>
        </div>
    </div>

    <!-- Grid de templates -->
    @if($templates->count() > 0)
        <div class="templates-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($templates as $template)
                <div class="template-card bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                    <!-- Cabeçalho do card -->
                    <div class="card-header p-4 border-b border-gray-200">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900">{{ $template->name }}</h3>
                                <p class="text-sm text-gray-500 mt-1">{{ $template->subject }}</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                @if($template->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Ativo
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Inativo
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Categoria e informações -->
                        <div class="mt-3 flex items-center justify-between">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($template->category === 'transactional') bg-blue-100 text-blue-800
                                @elseif($template->category === 'promotional') bg-purple-100 text-purple-800
                                @elseif($template->category === 'notification') bg-green-100 text-green-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ ucfirst($template->category) }}
                            </span>
                            <span class="text-xs text-gray-500">
                                {{ $template->created_at->format('d/m/Y') }}
                            </span>
                        </div>
                    </div>

                    <!-- Estatísticas rápidas -->
                    @php
                        $stats = $template->getUsageStats();
                    @endphp
                    <div class="card-stats px-4 py-3 bg-gray-50">
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div>
                                <div class="text-lg font-semibold text-gray-900">{{ number_format($stats['total_sent']) }}</div>
                                <div class="text-xs text-gray-500">Enviados</div>
                            </div>
                            <div>
                                <div class="text-lg font-semibold text-gray-900">{{ number_format($stats['open_rate'], 1) }}%</div>
                                <div class="text-xs text-gray-500">Abertura</div>
                            </div>
                            <div>
                                <div class="text-lg font-semibold text-gray-900">{{ number_format($stats['click_rate'], 1) }}%</div>
                                <div class="text-xs text-gray-500">Cliques</div>
                            </div>
                        </div>
                    </div>

                    <!-- Ações -->
                    <div class="card-actions p-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="flex space-x-2">
                                <a href="{{ route('email-templates.show', $template) }}"
                                   class="btn btn-sm btn-primary">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                                <a href="{{ route('email-templates.edit', $template) }}"
                                   class="btn btn-sm btn-secondary">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                            </div>

                            <div class="flex space-x-1">
                                <form method="POST" action="{{ route('email-templates.duplicate', $template) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-ghost"
                                            onclick="return confirm('Deseja duplicar este template?')">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                        </svg>
                                    </button>
                                </form>

                                @if($template->canBeDeleted())
                                    <form method="POST" action="{{ route('email-templates.destroy', $template) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-ghost text-red-600 hover:text-red-700"
                                                onclick="return confirm('Tem certeza que deseja excluir este template?')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Paginação -->
        @if($templates->hasPages())
            <div class="mt-8">
                {{ $templates->appends(request()->query())->links() }}
            </div>
        @endif

    @else
        <!-- Estado vazio -->
        <div class="empty-state text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum template encontrado</h3>
            <p class="mt-1 text-sm text-gray-500">
                @if($filters)
                    Tente ajustar os filtros ou crie um novo template.
                @else
                    Comece criando seu primeiro template de email.
                @endif
            </p>
            <div class="mt-6">
                <a href="{{ route('email-templates.create') }}" class="btn btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Criar Primeiro Template
                </a>
            </div>
        </div>
    @endif
</div>

<!-- Modal de Templates Prontos -->
<div id="presetsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Templates Prontos</h3>
                <button onclick="closePresetsModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="space-y-4">
                <div class="preset-category">
                    <h4 class="font-medium text-gray-700 mb-2">Transacionais</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <button onclick="usePreset('budget-confirmation')" class="preset-item p-3 border border-gray-200 rounded-lg hover:bg-gray-50 text-left">
                            <div class="font-medium text-gray-900">Confirmação de Orçamento</div>
                            <div class="text-sm text-gray-500">Email enviado ao cliente confirmando recebimento</div>
                        </button>
                        <button onclick="usePreset('invoice-generated')" class="preset-item p-3 border border-gray-200 rounded-lg hover:bg-gray-50 text-left">
                            <div class="font-medium text-gray-900">Fatura Gerada</div>
                            <div class="text-sm text-gray-500">Notificação de geração de fatura</div>
                        </button>
                    </div>
                </div>

                <div class="preset-category">
                    <h4 class="font-medium text-gray-700 mb-2">Promocionais</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <button onclick="usePreset('newsletter')" class="preset-item p-3 border border-gray-200 rounded-lg hover:bg-gray-50 text-left">
                            <div class="font-medium text-gray-900">Newsletter Mensal</div>
                            <div class="text-sm text-gray-500">Informativo mensal para clientes</div>
                        </button>
                        <button onclick="usePreset('welcome')" class="preset-item p-3 border border-gray-200 rounded-lg hover:bg-gray-50 text-left">
                            <div class="font-medium text-gray-900">Boas-vindas</div>
                            <div class="text-sm text-gray-500">Email de boas-vindas ao usuário</div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showPresetsModal() {
    document.getElementById('presetsModal').classList.remove('hidden');
}

function closePresetsModal() {
    document.getElementById('presetsModal').classList.add('hidden');
}

function usePreset(presetType) {
    // Redirecionar para criação com preset selecionado
    window.location.href = `/email-templates/create?preset=${presetType}`;
    closePresetsModal();
}

// Fechar modal ao clicar fora
document.getElementById('presetsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePresetsModal();
    }
});
</script>
@endsection

@push('styles')
<style>
.btn {
    @apply inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md transition-colors duration-200;
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
    @apply px-3 py-1.5 text-xs;
}

.stat-card {
    transition: transform 0.2s ease-in-out;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.template-card {
    transition: all 0.2s ease-in-out;
}

.template-card:hover {
    transform: translateY(-2px);
}

.preset-item {
    transition: all 0.2s ease-in-out;
}

.preset-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}
</style>
@endpush
