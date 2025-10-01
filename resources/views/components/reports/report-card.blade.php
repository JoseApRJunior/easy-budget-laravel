@props([
    'report' => null,
    'showActions' => true,
    'compact' => false
])

@php
    if (!$report) return;

    $categoryColors = [
        'financial' => 'blue',
        'customer' => 'green',
        'budget' => 'purple',
        'executive' => 'orange',
        'custom' => 'gray'
    ];

    $color = $categoryColors[$report->category] ?? 'gray';
    $categoryLabel = $report->getCategoryLabel();
    $typeLabel = $report->getTypeLabel();

    // Última execução
    $lastExecution = $report->getLastExecution();
    $executionCount = $report->executions()->count();
@endphp

<div class="report-card bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-all duration-200 {{ $compact ? 'p-4' : 'p-6' }}">
    <!-- Cabeçalho do Card -->
    <div class="flex items-start justify-between mb-4">
        <div class="flex-1">
            <div class="flex items-center space-x-3 mb-2">
                <div class="w-10 h-10 bg-{{ $color }}-100 rounded-lg flex items-center justify-center">
                    <i class="bi bi-graph-up text-{{ $color }}-600 text-lg"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 leading-tight">
                        {{ $report->name }}
                    </h3>
                    @if($report->description)
                        <p class="text-sm text-gray-500 mt-1 line-clamp-2">
                            {{ $report->description }}
                        </p>
                    @endif
                </div>
            </div>

            <!-- Badges -->
            <div class="flex items-center space-x-2 mt-3">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800">
                    {{ $categoryLabel }}
                </span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                    {{ $typeLabel }}
                </span>
                @if($report->is_system)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        Sistema
                    </span>
                @endif
            </div>
        </div>

        <!-- Status -->
        <div class="flex flex-col items-end space-y-2">
            @if($report->is_active)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <i class="bi bi-check-circle-fill me-1"></i>
                    Ativo
                </span>
            @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                    <i class="bi bi-x-circle-fill me-1"></i>
                    Inativo
                </span>
            @endif
        </div>
    </div>

    <!-- Estatísticas do Relatório -->
    @unless($compact)
    <div class="grid grid-cols-3 gap-4 mb-4 p-4 bg-gray-50 rounded-lg">
        <div class="text-center">
            <div class="text-2xl font-bold text-gray-900">{{ $executionCount }}</div>
            <div class="text-xs text-gray-500">Execuções</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold text-gray-900">
                @if($lastExecution)
                    {{ $lastExecution->created_at->diffForHumans() }}
                @else
                    Nunca
                @endif
            </div>
            <div class="text-xs text-gray-500">Última Execução</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold text-gray-900">
                @if($report->isScheduled())
                    <i class="bi bi-clock text-green-600"></i>
                @else
                    <i class="bi bi-dash text-gray-400"></i>
                @endif
            </div>
            <div class="text-xs text-gray-500">Agendado</div>
        </div>
    </div>
    @endunless

    <!-- Ações -->
    @if($showActions)
    <div class="flex items-center justify-between pt-4 border-t border-gray-200">
        <div class="flex items-center space-x-2">
            <!-- Botão Visualizar -->
            <a href="{{ route('reports.show', $report) }}"
               class="btn btn-sm btn-secondary">
                <i class="bi bi-eye me-1"></i>
                Visualizar
            </a>

            <!-- Botão Executar -->
            <button onclick="executeReport({{ $report->id }})"
                    class="btn btn-sm btn-primary">
                <i class="bi bi-play-circle me-1"></i>
                Executar
            </button>
        </div>

        <!-- Menu de Ações -->
        <div class="relative">
            <button onclick="toggleReportMenu({{ $report->id }})"
                    class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                <i class="bi bi-three-dots-vertical"></i>
            </button>

            <!-- Dropdown Menu -->
            <div id="report-menu-{{ $report->id }}"
                 class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200 hidden">
                <div class="py-1">
                    <a href="{{ route('reports.edit', $report) }}"
                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="bi bi-pencil me-2"></i>
                        Editar
                    </a>
                    <a href="{{ route('reports.schedules', $report) }}"
                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="bi bi-calendar-plus me-2"></i>
                        Agendar
                    </a>
                    <a href="{{ route('reports.history', $report) }}"
                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="bi bi-clock-history me-2"></i>
                        Histórico
                    </a>
                    <button onclick="duplicateReport({{ $report->id }})"
                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="bi bi-copy me-2"></i>
                        Duplicar
                    </button>
                    <div class="border-t border-gray-100"></div>
                    <button onclick="deleteReport({{ $report->id }})"
                            class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                        <i class="bi bi-trash me-2"></i>
                        Excluir
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
    // Toggle menu de ações
    function toggleReportMenu(reportId) {
        const menu = document.getElementById(`report-menu-${reportId}`);
        menu.classList.toggle('hidden');
    }

    // Fechar menu ao clicar fora
    document.addEventListener('click', function(event) {
        document.querySelectorAll('[id^="report-menu-"]').forEach(menu => {
            if (!menu.contains(event.target) && !event.target.closest('[onclick*="toggleReportMenu"]')) {
                menu.classList.add('hidden');
            }
        });
    });

    // Executar relatório
    function executeReport(reportId) {
        if (confirm('Deseja executar este relatório agora?')) {
            // TODO: Implementar execução via AJAX
            console.log('Executando relatório:', reportId);

            // Mostrar indicador de loading
            const button = event.target.closest('button');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Executando...';
            button.disabled = true;

            // Simular execução
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
                alert('Relatório executado com sucesso!');
            }, 2000);
        }
    }

    // Duplicar relatório
    function duplicateReport(reportId) {
        if (confirm('Deseja criar uma cópia deste relatório?')) {
            // TODO: Implementar duplicação via AJAX
            console.log('Duplicando relatório:', reportId);
            alert('Funcionalidade em desenvolvimento');
        }
    }

    // Excluir relatório
    function deleteReport(reportId) {
        if (confirm('Tem certeza que deseja excluir este relatório? Esta ação não pode ser desfeita.')) {
            // TODO: Implementar exclusão via AJAX
            console.log('Excluindo relatório:', reportId);
            alert('Funcionalidade em desenvolvimento');
        }
    }
</script>
@endpush

@push('styles')
<style>
    .report-card {
        height: {{ $compact ? 'auto' : '100%' }};
        transition: all 0.2s ease;
    }

    .report-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Menu dropdown */
    .report-card .dropdown-menu {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .report-card .dropdown-menu a,
    .report-card .dropdown-menu button {
        transition: all 0.2s ease;
    }

    /* Responsividade */
    @media (max-width: 768px) {
        .report-card {
            margin-bottom: 1rem;
        }

        .report-card .grid-cols-3 {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .report-card .flex.items-center.space-x-2 {
            flex-direction: column;
            align-items: stretch;
        }

        .report-card .btn {
            width: 100%;
            margin-bottom: 0.5rem;
        }
    }

    /* Estados de loading */
    .report-card.loading {
        opacity: 0.6;
        pointer-events: none;
    }

    .report-card.loading::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Animações */
    .report-card {
        animation: slideInUp 0.5s ease-out;
    }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
@endpush
