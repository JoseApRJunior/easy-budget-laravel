@extends( 'layouts.app' )

@section( 'title', 'Construtor de Relatórios' )

@section( 'content' )
    <div class="reports-builder">
        <!-- Cabeçalho -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Construtor de Relatórios</h1>
                    <p class="mt-2 text-gray-600">Crie relatórios personalizados de forma visual e intuitiva</p>
                </div>
                <div class="flex space-x-3">
                    <button onclick="previewReport()" class="btn btn-secondary">
                        <i class="bi bi-eye me-2"></i>
                        Visualizar
                    </button>
                    <button onclick="saveReport()" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>
                        Salvar Relatório
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Painel de Configuração (Esquerda) -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Configuração Básica</h3>

                    <!-- Nome e Descrição -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nome do Relatório</label>
                        <input type="text" id="reportName" placeholder="Ex: Relatório de Vendas Mensal"
                            class="form-input w-full rounded-md border-gray-300 shadow-sm">
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
                        <textarea id="reportDescription" rows="3"
                            placeholder="Descreva brevemente o objetivo deste relatório"
                            class="form-textarea w-full rounded-md border-gray-300 shadow-sm"></textarea>
                    </div>

                    <!-- Categoria -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Categoria</label>
                        <select id="reportCategory" class="form-select w-full rounded-md border-gray-300 shadow-sm">
                            <option value="financial">Financeiro</option>
                            <option value="customer">Clientes</option>
                            <option value="budget">Orçamentos</option>
                            <option value="executive">Executivo</option>
                            <option value="custom">Personalizado</option>
                        </select>
                    </div>

                    <!-- Tipo de Visualização -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Visualização</label>
                        <select id="reportType" class="form-select w-full rounded-md border-gray-300 shadow-sm">
                            <option value="table">Tabela</option>
                            <option value="chart">Gráfico</option>
                            <option value="mixed">Misto</option>
                            <option value="kpi">KPI</option>
                        </select>
                    </div>

                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Seleção de Dados</h3>

                    <!-- Fonte de Dados -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fonte de Dados</label>
                        <select id="dataSource" class="form-select w-full rounded-md border-gray-300 shadow-sm">
                            <option value="budgets">Orçamentos</option>
                            <option value="customers">Clientes</option>
                            <option value="transactions">Transações</option>
                            <option value="invoices">Faturas</option>
                        </select>
                    </div>

                    <!-- Campos Disponíveis -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Campos para Seleção</label>
                        <div class="border border-gray-300 rounded-md p-3 max-h-48 overflow-y-auto">
                            <div id="availableFields">
                                <!-- Campos serão carregados dinamicamente -->
                            </div>
                        </div>
                    </div>

                    <!-- Campos Selecionados -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Campos Selecionados</label>
                        <div class="border border-gray-300 rounded-md p-3 min-h-24">
                            <div id="selectedFields" class="space-y-2">
                                <p class="text-sm text-gray-500">Arraste campos da lista acima ou clique para adicionar</p>
                            </div>
                        </div>
                    </div>

                    <!-- Filtros Dinâmicos -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">Filtros</label>
                            <button onclick="addFilter()" class="btn btn-sm btn-secondary">
                                <i class="bi bi-plus me-1"></i>
                                Adicionar
                            </button>
                        </div>
                        <div id="filtersContainer" class="space-y-3">
                            <!-- Filtros serão adicionados aqui -->
                        </div>
                    </div>

                    <!-- Configurações Avançadas -->
                    <div class="mb-6">
                        <h4 class="text-md font-medium text-gray-900 mb-3">Configurações Avançadas</h4>

                        <!-- Ordenação -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ordenação</label>
                            <div class="flex space-x-2">
                                <select id="orderField" class="form-select flex-1 rounded-md border-gray-300 shadow-sm">
                                    <option value="">Selecionar campo</option>
                                </select>
                                <select id="orderDirection" class="form-select flex-1 rounded-md border-gray-300 shadow-sm">
                                    <option value="ASC">Crescente</option>
                                    <option value="DESC">Decrescente</option>
                                </select>
                            </div>
                        </div>

                        <!-- Limite de Registros -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Limite de Registros</label>
                            <input type="number" id="recordLimit" min="1" max="10000" value="1000"
                                class="form-input w-full rounded-md border-gray-300 shadow-sm">
                        </div>

                        <!-- Cache -->
                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" id="enableCache" checked
                                    class="form-checkbox rounded border-gray-300 text-blue-600 shadow-sm">
                                <span class="ml-2 text-sm text-gray-700">Habilitar Cache</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Área de Visualização (Direita) -->
            <div class="lg:col-span-2">
                <!-- Abas de Visualização -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="flex space-x-8 px-6">
                            <button data-tab="preview"
                                class="tab-button active py-4 px-1 border-b-2 font-medium text-sm border-blue-500 text-blue-600">
                                Preview
                            </button>
                            <button data-tab="chart"
                                class="tab-button py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700">
                                Gráfico
                            </button>
                            <button data-tab="export"
                                class="tab-button py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700">
                                Exportação
                            </button>
                        </nav>
                    </div>

                    <!-- Preview dos Dados -->
                    <div id="preview-tab" class="tab-content p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Preview dos Dados</h3>
                            <div class="flex items-center space-x-3">
                                <select id="previewFormat" class="form-select rounded-md border-gray-300 shadow-sm">
                                    <option value="table">Tabela</option>
                                    <option value="cards">Cards</option>
                                </select>
                                <button onclick="refreshPreview()" class="btn btn-sm btn-secondary">
                                    <i class="bi bi-arrow-clockwise me-1"></i>
                                    Atualizar
                                </button>
                            </div>
                        </div>

                        <!-- Loading State -->
                        <div id="previewLoading" class="hidden text-center py-8">
                            <div class="inline-flex items-center">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mr-3"></div>
                                <span>Carregando dados...</span>
                            </div>
                        </div>

                        <!-- Área de Preview -->
                        <div id="previewContent" class="min-h-96">
                            <div class="text-center py-12 text-gray-500">
                                <i class="bi bi-table text-4xl mb-4"></i>
                                <p>Configure os campos e filtros para ver o preview</p>
                            </div>
                        </div>
                    </div>

                    <!-- Configuração de Gráfico -->
                    <div id="chart-tab" class="tab-content hidden p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Configuração de Gráfico</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Tipo de Gráfico -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Gráfico</label>
                                <select id="chartType" class="form-select w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="line">Linha</option>
                                    <option value="bar">Barras</option>
                                    <option value="pie">Pizza</option>
                                    <option value="area">Área</option>
                                    <option value="scatter">Dispersão</option>
                                </select>
                            </div>

                            <!-- Campo X -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Campo X (Eixo
                                    Horizontal)</label>
                                <select id="chartXField" class="form-select w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="">Selecionar campo</option>
                                </select>
                            </div>

                            <!-- Campo Y -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Campo Y (Eixo Vertical)</label>
                                <select id="chartYField" class="form-select w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="">Selecionar campo</option>
                                </select>
                            </div>

                            <!-- Cor do Gráfico -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cor Principal</label>
                                <select id="chartColor" class="form-select w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="primary">Azul</option>
                                    <option value="success">Verde</option>
                                    <option value="warning">Amarelo</option>
                                    <option value="danger">Vermelho</option>
                                    <option value="info">Ciano</option>
                                </select>
                            </div>
                        </div>

                        <!-- Área do Gráfico -->
                        <div class="mt-6">
                            <div class="bg-gray-50 rounded-lg p-4 min-h-96">
                                <div id="chartPreview" class="h-80">
                                    <div class="text-center py-12 text-gray-500">
                                        <i class="bi bi-graph-up text-4xl mb-4"></i>
                                        <p>Configure o gráfico para visualizar</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Configuração de Exportação -->
                    <div id="export-tab" class="tab-content hidden p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Configuração de Exportação</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Formatos Disponíveis -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Formatos de Exportação</label>
                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input type="checkbox" id="exportPdf" checked class="form-checkbox">
                                        <span class="ml-2">PDF</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" id="exportExcel" checked class="form-checkbox">
                                        <span class="ml-2">Excel</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" id="exportCsv" class="form-checkbox">
                                        <span class="ml-2">CSV</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" id="exportJson" class="form-checkbox">
                                        <span class="ml-2">JSON</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Configurações PDF -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Configurações PDF</label>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Orientação</label>
                                        <select id="pdfOrientation"
                                            class="form-select w-full rounded-md border-gray-300 shadow-sm text-sm">
                                            <option value="portrait">Retrato</option>
                                            <option value="landscape">Paisagem</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Tamanho da Página</label>
                                        <select id="pdfPageSize"
                                            class="form-select w-full rounded-md border-gray-300 shadow-sm text-sm">
                                            <option value="a4">A4</option>
                                            <option value="a3">A3</option>
                                            <option value="letter">Carta</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Ações de Exportação -->
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-md font-medium text-gray-900">Exportar Agora</h4>
                                    <p class="text-sm text-gray-500">Gere o relatório nos formatos selecionados</p>
                                </div>
                                <button onclick="exportReport()" class="btn btn-success">
                                    <i class="bi bi-download me-2"></i>
                                    Exportar Relatório
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estatísticas do Preview -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                        <div>
                            <div class="text-2xl font-bold text-blue-600" id="totalRecords">0</div>
                            <div class="text-sm text-gray-500">Total de Registros</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-green-600" id="executionTime">0ms</div>
                            <div class="text-sm text-gray-500">Tempo de Execução</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-purple-600" id="memoryUsage">0MB</div>
                            <div class="text-sm text-gray-500">Memória Utilizada</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-orange-600" id="cacheStatus">Ativo</div>
                            <div class="text-sm text-gray-500">Status do Cache</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Filtro -->
    <div id="filterModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Adicionar Filtro</h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Campo</label>
                    <select id="filterField" class="form-select w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">Selecionar campo</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Operador</label>
                    <select id="filterOperator" class="form-select w-full rounded-md border-gray-300 shadow-sm">
                        <option value="equals">Igual</option>
                        <option value="not_equals">Diferente</option>
                        <option value="greater_than">Maior que</option>
                        <option value="less_than">Menor que</option>
                        <option value="contains">Contém</option>
                        <option value="starts_with">Começa com</option>
                        <option value="in">Está em</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Valor</label>
                    <input type="text" id="filterValue" class="form-input w-full rounded-md border-gray-300 shadow-sm">
                </div>

                <div class="flex justify-end space-x-3">
                    <button onclick="closeFilterModal()" class="btn btn-secondary">
                        Cancelar
                    </button>
                    <button onclick="applyFilter()" class="btn btn-primary">
                        Aplicar Filtro
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push( 'scripts' )
        <script>
            let currentFilters = [];
            let selectedFields = [];

            // Controle das abas
            document.querySelectorAll( '.tab-button' ).forEach( button => {
                button.addEventListener( 'click', function () {
                    const tabName = this.getAttribute( 'data-tab' );

                    // Remover classe active
                    document.querySelectorAll( '.tab-button' ).forEach( btn => {
                        btn.classList.remove( 'border-blue-500', 'text-blue-600' );
                        btn.classList.add( 'border-transparent', 'text-gray-500' );
                    } );

                    // Adicionar classe active
                    this.classList.remove( 'border-transparent', 'text-gray-500' );
                    this.classList.add( 'border-blue-500', 'text-blue-600' );

                    // Ocultar todos os conteúdos
                    document.querySelectorAll( '.tab-content' ).forEach( content => {
                        content.classList.add( 'hidden' );
                    } );

                    // Mostrar conteúdo selecionado
                    document.getElementById( tabName + '-tab' ).classList.remove( 'hidden' );
                } );
            } );

            // Carregar campos disponíveis baseado na fonte de dados
            document.getElementById( 'dataSource' ).addEventListener( 'change', function () {
                loadAvailableFields( this.value );
            } );

            // Adicionar filtro
            function addFilter() {
                document.getElementById( 'filterModal' ).classList.remove( 'hidden' );
            }

            function closeFilterModal() {
                document.getElementById( 'filterModal' ).classList.add( 'hidden' );
            }

            function applyFilter() {
                const field = document.getElementById( 'filterField' ).value;
                const operator = document.getElementById( 'filterOperator' ).value;
                const value = document.getElementById( 'filterValue' ).value;

                if ( field && operator && value ) {
                    currentFilters.push( { field, operator, value } );
                    renderFilters();
                    closeFilterModal();
                    refreshPreview();
                }
            }

            // Renderizar filtros aplicados
            function renderFilters() {
                const container = document.getElementById( 'filtersContainer' );

                if ( currentFilters.length === 0 ) {
                    container.innerHTML = '<p class="text-sm text-gray-500">Nenhum filtro aplicado</p>';
                    return;
                }

                container.innerHTML = currentFilters.map( ( filter, index ) => `
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="text-sm">
                            <span class="font-medium">${filter.field}</span>
                            <span class="text-gray-500"> ${getOperatorLabel( filter.operator )} </span>
                            <span class="font-medium">${filter.value}</span>
                        </div>
                        <button onclick="removeFilter(${index})"
                                class="text-red-500 hover:text-red-700 p-1">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                `).join( '' );
            }

            function removeFilter( index ) {
                currentFilters.splice( index, 1 );
                renderFilters();
                refreshPreview();
            }

            function getOperatorLabel( operator ) {
                const labels = {
                    'equals': 'igual a',
                    'not_equals': 'diferente de',
                    'greater_than': 'maior que',
                    'less_than': 'menor que',
                    'contains': 'contém',
                    'starts_with': 'começa com',
                    'in': 'está em'
                };
                return labels[operator] || operator;
            }

            // Preview do relatório
            function refreshPreview() {
                showPreviewLoading();

                // Simular dados de preview
                setTimeout( () => {
                    const mockData = generateMockData();
                    renderPreviewTable( mockData );
                    updatePreviewStats( mockData );
                    hidePreviewLoading();
                }, 1000 );
            }

            function generateMockData() {
                const dataSource = document.getElementById( 'dataSource' ).value;
                const mockData = [];

                for ( let i = 1; i <= 10; i++ ) {
                    switch ( dataSource ) {
                        case 'customers':
                            mockData.push( {
                                id: i,
                                name: `Cliente ${i}`,
                                email: `cliente${i}@exemplo.com`,
                                phone: `(11) 99999-999${i}`,
                                city: ['São Paulo', 'Rio de Janeiro', 'Belo Horizonte'][i % 3],
                                created_at: new Date( Date.now() - Math.random() * 30 * 24 * 60 * 60 * 1000 ).toISOString().split( 'T' )[0]
                            } );
                            break;
                        case 'budgets':
                            mockData.push( {
                                id: i,
                                name: `Orçamento ${i}`,
                                customer: `Cliente ${i}`,
                                value: Math.floor( Math.random() * 10000 ) + 1000,
                                status: ['Pendente', 'Aprovado', 'Rejeitado'][i % 3],
                                created_at: new Date( Date.now() - Math.random() * 30 * 24 * 60 * 60 * 1000 ).toISOString().split( 'T' )[0]
                            } );
                            break;
                    }
                }

                return mockData;
            }

            function renderPreviewTable( data ) {
                const format = document.getElementById( 'previewFormat' ).value;
                const container = document.getElementById( 'previewContent' );

                if ( format === 'cards' ) {
                    renderCards( data );
                } else {
                    renderTable( data );
                }
            }

            function renderTable( data ) {
                if ( data.length === 0 ) {
                    document.getElementById( 'previewContent' ).innerHTML = `
                        <div class="text-center py-12 text-gray-500">
                            <i class="bi bi-table text-4xl mb-4"></i>
                            <p>Nenhum dado encontrado</p>
                        </div>
                    `;
                    return;
                }

                const headers = Object.keys( data[0] );
                const table = `
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    ${headers.map( header => `<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">${header}</th>` ).join( '' )}
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                ${data.map( row => `
                                    <tr>
                                        ${headers.map( header => `<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${row[header]}</td>` ).join( '' )}
                                    </tr>
                                `).join( '' )}
                            </tbody>
                        </table>
                    </div>
                `;

                document.getElementById( 'previewContent' ).innerHTML = table;
            }

            function renderCards( data ) {
                if ( data.length === 0 ) {
                    document.getElementById( 'previewContent' ).innerHTML = `
                        <div class="text-center py-12 text-gray-500">
                            <i class="bi bi-grid text-4xl mb-4"></i>
                            <p>Nenhum dado encontrado</p>
                        </div>
                    `;
                    return;
                }

                const cards = `
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        ${data.map( item => `
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                ${Object.entries( item ).map( ( [key, value] ) => `
                                    <div class="mb-2">
                                        <span class="text-xs font-medium text-gray-500 uppercase">${key}:</span>
                                        <div class="text-sm text-gray-900">${value}</div>
                                    </div>
                                `).join( '' )}
                            </div>
                        `).join( '' )}
                    </div>
                `;

                document.getElementById( 'previewContent' ).innerHTML = cards;
            }

            function updatePreviewStats( data ) {
                document.getElementById( 'totalRecords' ).textContent = data.length;
                document.getElementById( 'executionTime' ).textContent = Math.floor( Math.random() * 500 ) + 'ms';
                document.getElementById( 'memoryUsage' ).textContent = Math.floor( Math.random() * 10 ) + 'MB';
            }

            function showPreviewLoading() {
                document.getElementById( 'previewLoading' ).classList.remove( 'hidden' );
            }

            function hidePreviewLoading() {
                document.getElementById( 'previewLoading' ).classList.add( 'hidden' );
            }

            // Salvar relatório
            function saveReport() {
                const reportData = {
                    name: document.getElementById( 'reportName' ).value,
                    description: document.getElementById( 'reportDescription' ).value,
                    category: document.getElementById( 'reportCategory' ).value,
                    type: document.getElementById( 'reportType' ).value,
                    data_source: document.getElementById( 'dataSource' ).value,
                    fields: selectedFields,
                    filters: currentFilters,
                    config: {
                        order_field: document.getElementById( 'orderField' ).value,
                        order_direction: document.getElementById( 'orderDirection' ).value,
                        record_limit: document.getElementById( 'recordLimit' ).value,
                        enable_cache: document.getElementById( 'enableCache' ).checked
                    }
                };

                if ( !reportData.name ) {
                    alert( 'Por favor, informe o nome do relatório' );
                    return;
                }

                // TODO: Implementar salvamento via AJAX
                console.log( 'Salvando relatório:', reportData );
                alert( 'Relatório salvo com sucesso!' );
            }

            // Exportar relatório
            function exportReport() {
                const formats = [];
                if ( document.getElementById( 'exportPdf' ).checked ) formats.push( 'pdf' );
                if ( document.getElementById( 'exportExcel' ).checked ) formats.push( 'excel' );
                if ( document.getElementById( 'exportCsv' ).checked ) formats.push( 'csv' );
                if ( document.getElementById( 'exportJson' ).checked ) formats.push( 'json' );

                if ( formats.length === 0 ) {
                    alert( 'Selecione pelo menos um formato de exportação' );
                    return;
                }

                // TODO: Implementar exportação via AJAX
                console.log( 'Exportando relatório nos formatos:', formats );
                alert( 'Relatório exportado com sucesso!' );
            }

            // Inicialização
            document.addEventListener( 'DOMContentLoaded', function () {
                loadAvailableFields( 'budgets' );
                refreshPreview();
            } );
        </script>
    @endpush
@endsection
