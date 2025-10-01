<div class="space-y-6">
    <!-- Integrações Disponíveis -->
    <div class="bg-white rounded-lg border p-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="bi bi-link mr-2 text-purple-600"></i>
            Integrações
        </h3>

        <div class="space-y-4">
            <!-- Mercado Pago -->
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                        <i class="bi bi-credit-card text-white text-sm"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">Mercado Pago</p>
                        <p class="text-sm text-gray-600">Pagamentos</p>
                    </div>
                </div>
                <span
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    Conectado
                </span>
            </div>

            <!-- Google Analytics -->
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-green-600 rounded-lg flex items-center justify-center">
                        <i class="bi bi-graph-up text-white text-sm"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">Google Analytics</p>
                        <p class="text-sm text-gray-600">Análise</p>
                    </div>
                </div>
                <span
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                    Desconectado
                </span>
            </div>

            <!-- Webhook -->
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-purple-600 rounded-lg flex items-center justify-center">
                        <i class="bi bi-webhook text-white text-sm"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">Webhook</p>
                        <p class="text-sm text-gray-600">Integração</p>
                    </div>
                </div>
                <span
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                    Pendente
                </span>
            </div>
        </div>

        <div class="mt-4 text-sm text-gray-500">
            <p>Gerencie suas integrações com serviços externos</p>
        </div>
    </div>

    <!-- Logs de Integração -->
    <div class="bg-white rounded-lg border p-4">
        <h4 class="font-medium text-gray-900 mb-3">Últimos Logs</h4>

        <div class="space-y-2">
            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center space-x-2">
                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                    <span>Sincronização OK</span>
                </div>
                <span class="text-gray-500">há 2h</span>
            </div>

            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center space-x-2">
                    <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                    <span>Tentativa de conexão</span>
                </div>
                <span class="text-gray-500">há 5h</span>
            </div>

            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center space-x-2">
                    <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                    <span>Erro de autenticação</span>
                </div>
                <span class="text-gray-500">há 1d</span>
            </div>
        </div>
    </div>
</div>
