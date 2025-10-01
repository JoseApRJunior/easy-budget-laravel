@extends( 'layouts.app' )

@section( 'title', 'Dashboard de Relatórios' )

@section( 'content' )
    <div class="reports-dashboard">
        <!-- Cabeçalho -->
        <div class="mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Relatórios</h1>
                    <p class="mt-2 text-gray-600">Gerencie e visualize seus relatórios personalizados</p>
                </div>
                <div class="mt-4 sm:mt-0 flex space-x-3">
                    <a href="{{ route( 'reports.builder' ) }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>
                        Novo Relatório
                    </a>
                    <button type="button" onclick="refreshDashboard()" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise me-2"></i>
                        Atualizar
                    </button>
                </div>
            </div>
        </div>

        <!-- Cards de Estatísticas -->
        <div class="stats-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stat-card bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Relatórios Ativos</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $stats[ 'total_definitions' ] ?? 0 }}</p>
                        <p class="text-sm text-gray-500 mt-1">Definições criadas</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="bi bi-graph-up text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Execuções Hoje</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $stats[ 'executions_today' ] ?? 0 }}</p>
                        <p class="text-sm text-gray-500 mt-1">Relatórios gerados</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="bi bi-play-circle text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Agendamentos</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $stats[ 'active_schedules' ] ?? 0 }}</p>
                        <p class="text-sm text-gray-500 mt-1">Relatórios automáticos</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                        <i class="bi bi-clock text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Taxa de Sucesso</p>
                        <p class="text-3xl font-bold text-gray-900">
                            @php
                                $successRate = $stats[ 'executions_today' ] > 0 ?
                                    round( ( ( $stats[ 'executions_today' ] - ( $stats[ 'failed_executions' ] ?? 0 ) ) / $stats[ 'executions_today' ] ) * 100 ) : 100;
                            @endphp
                            {{ $successRate }}%
                        </p>
                        <p class="text-sm text-gray-500 mt-1">Últimos 7 dias</p>
                    </div>
                    <div
                        class="w-12 h-12 bg-{{ $successRate >= 90 ? 'green' : ( $successRate >= 70 ? 'yellow' : 'red' ) }}-100 rounded-full flex items-center justify-center">
                        <i
                            class="bi bi-check-circle text-{{ $successRate >= 90 ? 'green' : ( $successRate >= 70 ? 'yellow' : 'red' ) }}-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos de Visão Geral -->
        <div class="overview-charts grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Tendência de Execuções -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Tendência de Execuções</h3>
                <div class="h-64">
                    <x-reports.chart type="line" :data="[]" :options="[
        'title'   => 'Execuções por Dia',
        'x_field' => 'date',
        'y_field' => 'count',
        'height'  => 250
    ]" height="250" interactive="true" />
                </div>
            </div>

            <!-- Distribuição por Status -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Status das Execuções</h3>
                <div class="h-64">
                    <x-reports.chart type="doughnut" :data="[]" :options="[
        'title'       => 'Distribuição por Status',
        'label_field' => 'status',
        'value_field' => 'count',
        'height'      => 250
    ]" height="250"
                        interactive="true" />
                </div>
            </div>
        </div>

        <!-- Filtros Rápidos -->
        <div class="filters-section bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Filtros Rápidos</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Categoria</label>
                    <select id="categoryFilter" class="form-select w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">Todas</option>
                        <option value="financial">Financeiro</option>
                        <option value="customer">Clientes</option>
                        <option value="budget">Orçamentos</option>
                        <option value="executive">Executivo</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Período</label>
                    <select id="periodFilter" class="form-select w-full rounded-md border-gray-300 shadow-sm">
                        <option value="today">Hoje</option>
                        <option value="week">Esta Semana</option>
                        <option value="month">Este Mês</option>
                        <option value="quarter">Este Trimestre</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="statusFilter" class="form-select w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">Todos</option>
                        <option value="completed">Concluído</option>
                        <option value="failed">Falhou</option>
                        <option value="running">Executando</option>
                    </select>
                </div>

                <div class="flex items-end">
                    <button onclick="applyFilters()" class="btn btn-primary w-full">
                        <i class="bi bi-funnel me-2"></i>
                        Aplicar Filtros
                    </button>
                </div>
            </div>
        </div>

        <!-- Abas de Conteúdo -->
        <div class="content-tabs">
            <div class="border-b border-gray-200 mb-6">
                <nav class="-mb-px flex space-x-8">
                    <button data-tab="reports"
                        class="tab-button active whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm border-blue-500 text-blue-600">
                        Meus Relatórios
                    </button>
                    <button data-tab="recent"
                        class="tab-button whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Execuções Recentes
                    </button>
                    <button data-tab="scheduled"
                        class="tab-button whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Agendamentos
                    </button>
                </nav>
            </div>

            <!-- Meus Relatórios -->
            <div id="reports-tab" class="tab-content">
                @if( isset( $reportDefinitions ) && $reportDefinitions->count() > 0 )
                    <div class="reports-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach( $reportDefinitions as $report )
                            <x-reports.report-card :report="$report" />
                        @endforeach
                    </div>
                @else
                    <div class="empty-state text-center py-12">
                        <i class="bi bi-graph-up text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhum relatório encontrado</h3>
                        <p class="text-gray-500 mb-6">Crie seu primeiro relatório personalizado para começar.</p>
                        <a href="{{ route( 'reports.builder' ) }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>
                            Criar Primeiro Relatório
                        </a>
                    </div>
                @endif
            </div>

            <!-- Execuções Recentes -->
            <div id="recent-tab" class="tab-content hidden">
                @if( isset( $recentExecutions ) && $recentExecutions->count() > 0 )
                    <div class="executions-list bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Execuções Recentes</h3>
                        </div>
                        <div class="divide-y divide-gray-200">
                            @foreach( $recentExecutions as $execution )
                                        <div class="px-6 py-4 hover:bg-gray-50">
                                            <div class="flex items-center justify-between">
                                                <div class="flex-1">
                                                    <h4 class="text-sm font-medium text-gray-900">
                                                        {{ $execution->definition->name ?? 'Relatório sem nome' }}
                                                    </h4>
                                                    <p class="text-sm text-gray-500">
                                                        {{ $execution->created_at->diffForHumans() }}
                                                    </p>
                                                </div>
                                                <div class="flex items-center space-x-3">
                                                    <span
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                                {{ $execution->status === 'completed' ? 'bg-green-100 text-green-800' :
                                ( $execution->status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' ) }}">
                                                        {{ $execution->getStatusLabel() }}
                                                    </span>
                                                    @if( $execution->file_path )
                                                        <a href="{{ route( 'reports.download', $execution->execution_id ) }}"
                                                            class="btn btn-sm btn-secondary">
                                                            <i class="bi bi-download me-1"></i>
                                                            Download
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="empty-state text-center py-12">
                        <i class="bi bi-clock-history text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhuma execução recente</h3>
                        <p class="text-gray-500">Execute alguns relatórios para ver o histórico aqui.</p>
                    </div>
                @endif
            </div>

            <!-- Agendamentos -->
            <div id="scheduled-tab" class="tab-content hidden">
                @if( isset( $activeSchedules ) && $activeSchedules->count() > 0 )
                    <div class="schedules-list bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Agendamentos Ativos</h3>
                        </div>
                        <div class="divide-y divide-gray-200">
                            @foreach( $activeSchedules as $schedule )
                                <div class="px-6 py-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <h4 class="text-sm font-medium text-gray-900">
                                                {{ $schedule->name }}
                                            </h4>
                                            <p class="text-sm text-gray-500">
                                                {{ $schedule->definition->name ?? 'Relatório sem nome' }}
                                            </p>
                                            <p class="text-xs text-gray-400 mt-1">
                                                Próxima execução: {{ $schedule->next_run_at->format( 'd/m/Y H:i' ) }}
                                            </p>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $schedule->getFrequencyLabel() }}
                                            </span>
                                            <button onclick="editSchedule({{ $schedule->id }})" class="btn btn-sm btn-secondary">
                                                <i class="bi bi-pencil me-1"></i>
                                                Editar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="empty-state text-center py-12">
                        <i class="bi bi-calendar-x text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhum agendamento ativo</h3>
                        <p class="text-gray-500">Crie agendamentos automáticos para seus relatórios.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push( 'scripts' )
        <script>
            // Controle das abas
            document.querySelectorAll( '.tab-button' ).forEach( button => {
                button.addEventListener( 'click', function () {
                    const tabName = this.getAttribute( 'data-tab' );

                    // Remover classe active de todas as abas
                    document.querySelectorAll( '.tab-button' ).forEach( btn => {
                        btn.classList.remove( 'border-blue-500', 'text-blue-600' );
                        btn.classList.add( 'border-transparent', 'text-gray-500' );
                    } );

                    // Adicionar classe active à aba clicada
                    this.classList.remove( 'border-transparent', 'text-gray-500' );
                    this.classList.add( 'border-blue-500', 'text-blue-600' );

                    // Ocultar todos os conteúdos
                    document.querySelectorAll( '.tab-content' ).forEach( content => {
                        content.classList.add( 'hidden' );
                    } );

                    // Mostrar conteúdo da aba selecionada
                    document.getElementById( tabName + '-tab' ).classList.remove( 'hidden' );
                } );
            } );

            // Aplicar filtros
            function applyFilters() {
                const category = document.getElementById( 'categoryFilter' ).value;
                const period = document.getElementById( 'periodFilter' ).value;
                const status = document.getElementById( 'statusFilter' ).value;

                // TODO: Implementar lógica de filtros via AJAX
                console.log( 'Aplicando filtros:', { category, period, status } );

                // Mostrar indicador de loading
                showLoadingState();
            }

            // Atualizar dashboard
            function refreshDashboard() {
                // TODO: Implementar refresh via AJAX
                console.log( 'Atualizando dashboard...' );

                // Mostrar indicador de loading
                showLoadingState();

                // Simular refresh
                setTimeout( () => {
                    hideLoadingState();
                }, 1000 );
            }

            // Estados de loading
            function showLoadingState() {
                // Adicionar classe de loading aos elementos principais
                document.querySelectorAll( '.stat-card, .reports-grid, .executions-list, .schedules-list' ).forEach( el => {
                    el.classList.add( 'opacity-50', 'pointer-events-none' );
                } );
            }

            function hideLoadingState() {
                // Remover classe de loading
                document.querySelectorAll( '.stat-card, .reports-grid, .executions-list, .schedules-list' ).forEach( el => {
                    el.classList.remove( 'opacity-50', 'pointer-events-none' );
                } );
            }

            // Editar agendamento
            function editSchedule( scheduleId ) {
                // TODO: Implementar modal de edição de agendamento
                console.log( 'Editando agendamento:', scheduleId );
            }

            // Auto-refresh a cada 5 minutos
            setInterval( () => {
                if ( document.visibilityState === 'visible' ) {
                    refreshDashboard();
                }
            }, 300000 );
        </script>
    @endpush

    @push( 'styles' )
        <style>
            .reports-dashboard {
                min-height: 100vh;
            }

            .stat-card {
                transition: all 0.3s ease;
            }

            .stat-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            }

            .tab-button {
                transition: all 0.2s ease;
            }

            .tab-button:hover {
                color: rgb(59 130 246);
            }

            .empty-state {
                background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
                border-radius: 12px;
                border: 2px dashed #cbd5e1;
            }

            .chart-container {
                background: #fff;
                border-radius: 8px;
            }

            /* Responsividade para dispositivos móveis */
            @media (max-width: 768px) {
                .stats-grid {
                    grid-template-columns: 1fr 1fr;
                    gap: 1rem;
                }

                .overview-charts {
                    grid-template-columns: 1fr;
                    gap: 1.5rem;
                }

                .filters-section .grid {
                    grid-template-columns: 1fr;
                    gap: 1rem;
                }
            }

            @media (max-width: 640px) {
                .stats-grid {
                    grid-template-columns: 1fr;
                }

                .reports-dashboard h1 {
                    font-size: 1.875rem;
                }

                .btn {
                    width: 100%;
                    margin-bottom: 0.5rem;
                }
            }

            /* Animações */
            .stat-card {
                animation: fadeInUp 0.6s ease-out;
            }

            .stat-card:nth-child(2) {
                animation-delay: 0.1s;
            }

            .stat-card:nth-child(3) {
                animation-delay: 0.2s;
            }

            .stat-card:nth-child(4) {
                animation-delay: 0.3s;
            }

            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            /* Loading skeleton */
            .skeleton {
                background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
                background-size: 200% 100%;
                animation: loading 1.5s infinite;
            }

            @keyframes loading {
                0% {
                    background-position: 200% 0;
                }

                100% {
                    background-position: -200% 0;
                }
            }
        </style>
    @endpush
@endsection
