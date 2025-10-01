@props( [ 'active' => 'dashboard' ] )

<aside class="bg-white shadow-lg border-r border-gray-200 h-full flex flex-col">
    <!-- Logo/Brand -->
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-xl font-bold text-gray-900">
            {{ config( 'app.name', 'Easy Budget' ) }}
            </h2> <p class="text-sm text-gray-600 mt-1">Administração</p>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
        <!-- Dashboard -->
        <a href="{{ route( 'admin.dashboard' ) }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg
            transition-colors duration-200 {{ $active === 'dashboard' ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-700' : 'text-gray-700 hover:bg-gray-50 hover:text-blue-700' }}">
            <svg class="w-5 h-5 mr-3 {{ $active === 'dashboard' ? 'text-blue-600' : 'text-gray-400' }}" fill="none"
                stroke="currentColor" viewBox="0 0 24 24"> <path stroke-linecap="round" stroke-linejoin="round"
                stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z" />
            </svg>
            Dashboard
        </a>

        <!-- Gestão de Usuários -->
        <div class="space-y-1">
            <div class="px-4 py-2">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Usuários</h3>
            </div>
            <a href="{{ route( 'admin.users.index' ) }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg
                transition-colors duration-200 ml-4 {{ $active === 'users' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50 hover:text-blue-700' }}">
                <svg class="w-5 h-5 mr-3 {{ $active === 'users' ? 'text-blue-600' : 'text-gray-400' }}" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                </svg>
                Todos os Usuários
            </a>
            <a href="{{ route( 'admin.users.create' ) }}"
                class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors duration-200 ml-4
                      {{ $active === 'users-create' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50 hover:text-blue-700' }}">
                <svg class="w-5 h-5 mr-3 {{ $active === 'users-create' ? 'text-blue-600' : 'text-gray-400' }}"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                Novo Usuário
            </a>
        </div>

        <!-- Configurações do Sistema -->
        <div class="space-y-1">
            <div class="px-4 py-2">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Sistema</h3>
            </div>
            <a href="{{ route( 'admin.settings.index' ) }}"
                class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors duration-200 ml-4
                      {{ $active === 'settings' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50 hover:text-blue-700' }}">
                <svg class="w-5 h-5 mr-3 {{ $active === 'settings' ? 'text-blue-600' : 'text-gray-400' }}" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Configurações
            </a>
            <a href="{{ route( 'admin.audit.index' ) }}"
                class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors duration-200 ml-4
                      {{ $active === 'audit' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50 hover:text-blue-700' }}">
                <svg class="w-5 h-5 mr-3 {{ $active === 'audit' ? 'text-blue-600' : 'text-gray-400' }}" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Logs de Auditoria
            </a>
        </div>

        <!-- Relatórios -->
        <div class="space-y-1">
            <div class="px-4 py-2">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Relatórios</h3>
            </div>
            <a href="{{ route( 'admin.reports.users' ) }}"
                class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors duration-200 ml-4
                      {{ $active === 'reports-users' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50 hover:text-blue-700' }}">
                <svg class="w-5 h-5 mr-3 {{ $active === 'reports-users' ? 'text-blue-600' : 'text-gray-400' }}"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Relatório de Usuários
            </a>
        </div>
    </nav>

    <!-- User Info & Logout -->
    <div class="p-4 border-t border-gray-200">
        <div class="flex items-center space-x-3 mb-3">
            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                <span class="text-white text-sm font-medium">
                    {{ substr( Auth::user()->name, 0, 1 ) }}
                </span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">{{ Auth::user()->name }}</p>
                <p class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</p>
            </div>
        </div>
        <form method="POST" action="{{ route( 'logout' ) }}" class="w-full">
            @csrf
            <button type="submit"
                class="w-full flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Sair
            </button>
        </form>
    </div>
</aside>
