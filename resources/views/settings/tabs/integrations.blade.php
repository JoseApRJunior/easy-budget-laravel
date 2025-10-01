<div class="space-y-8">
    <!-- Integrações Disponíveis -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="bi bi-link mr-2 text-purple-600"></i>
            Integrações Disponíveis
        </h3>

        <div class="space-y-6">
            <!-- Mercado Pago -->
            <div class="bg-white rounded-lg border p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center">
                                <i class="bi bi-credit-card text-white text-xl"></i>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-lg font-medium text-gray-900">Mercado Pago</h4>
                            <p class="text-sm text-gray-600">Processamento de pagamentos online</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Conectado
                        </span>
                        <button class="btn btn-outline btn-sm">
                            <i class="bi bi-gear mr-1"></i>
                            Configurar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Google Analytics -->
            <div class="bg-white rounded-lg border p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center">
                                <i class="bi bi-graph-up text-white text-xl"></i>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-lg font-medium text-gray-900">Google Analytics</h4>
                            <p class="text-sm text-gray-600">Análise de tráfego e comportamento</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            Desconectado
                        </span>
                        <button class="btn btn-primary btn-sm">
                            <i class="bi bi-plus mr-1"></i>
                            Conectar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Webhook Personalizado -->
            <div class="bg-white rounded-lg border p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center">
                                <i class="bi bi-webhook text-white text-xl"></i>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-lg font-medium text-gray-900">Webhook Personalizado</h4>
                            <p class="text-sm text-gray-600">Integração com sistemas externos</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            Pendente
                        </span>
                        <button class="btn btn-outline btn-sm">
                            <i class="bi bi-gear mr-1"></i>
                            Configurar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Logs de Integração -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="bi bi-journal-text mr-2 text-orange-600"></i>
            Logs de Integração
        </h3>

        <div class="bg-white rounded-lg border">
            <div class="p-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                        <span class="text-sm font-medium text-gray-900">Última sincronização bem-sucedida</span>
                        <span class="text-sm text-gray-500">há 2 horas</span>
                    </div>
                    <span class="text-sm text-green-600">Sucesso</span>
                </div>
            </div>
            <div class="p-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                        <span class="text-sm font-medium text-gray-900">Tentativa de conexão</span>
                        <span class="text-sm text-gray-500">há 5 horas</span>
                    </div>
                    <span class="text-sm text-yellow-600">Aviso</span>
                </div>
            </div>
            <div class="p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                        <span class="text-sm font-medium text-gray-900">Erro de autenticação</span>
                        <span class="text-sm text-gray-500">há 1 dia</span>
                    </div>
                    <span class="text-sm text-red-600">Erro</span>
                </div>
            </div>
        </div>

        <div class="mt-4 flex justify-between items-center">
            <p class="text-sm text-gray-500">
                Exibindo últimos 10 logs de integração
            </p>
            <button class="btn btn-outline btn-sm">
                <i class="bi bi-eye mr-2"></i>
                Ver Todos os Logs
            </button>
        </div>
    </div>

    <!-- Configurações de API -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="bi bi-key mr-2 text-indigo-600"></i>
            Chaves de API
        </h3>

        <div class="space-y-4">
            <!-- Chave Principal -->
            <div class="bg-white rounded-lg border p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h5 class="font-medium text-gray-900">Chave da API Principal</h5>
                        <p class="text-sm text-gray-600">Chave para acesso às APIs principais do sistema</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <code class="text-sm bg-gray-100 px-2 py-1 rounded">
                            sk-****7890
                        </code>
                        <button class="btn btn-outline btn-sm">
                            <i class="bi bi-eye mr-1"></i>
                            Mostrar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Webhook Secret -->
            <div class="bg-white rounded-lg border p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h5 class="font-medium text-gray-900">Webhook Secret</h5>
                        <p class="text-sm text-gray-600">Segredo para validação de webhooks recebidos</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <code class="text-sm bg-gray-100 px-2 py-1 rounded">
                            whsec_****abcd
                        </code>
                        <button class="btn btn-outline btn-sm">
                            <i class="bi bi-arrow-repeat mr-1"></i>
                            Renovar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 p-4 bg-blue-50 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="bi bi-info-circle text-blue-600"></i>
                </div>
                <div class="ml-3">
                    <h4 class="text-sm font-medium text-blue-800">Segurança das Chaves</h4>
                    <p class="mt-1 text-sm text-blue-700">
                        Mantenha suas chaves de API em segurança. Nunca compartilhe chaves públicas em repositórios ou
                        código cliente.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
