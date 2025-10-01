<nav class="bg-white shadow-sm border-b border-gray-200">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center py-3">
            <!-- Brand/Logo -->
            <div class="navbar-brand">
                <a href="{{ route('home') }}" class="flex items-center space-x-2 text-decoration-none">
                    <img src="{{ asset('assets/img/logo.png') }}" alt="Easy Budget" class="h-8 w-auto">
                    <span class="text-xl font-bold text-blue-600">Easy Budget</span>
                </a>
            </div>
            
            <!-- Navigation Links -->
            <div class="hidden md:flex items-center space-x-6">
                @auth
                    <a href="{{ route('home') }}" class="text-gray-700 hover:text-blue-600 transition-colors">
                        <i class="bi bi-house-door me-1"></i>
                        Dashboard
                    </a>
                    
                    <div class="relative group">
                        <button class="text-gray-700 hover:text-blue-600 transition-colors flex items-center">
                            <i class="bi bi-briefcase me-1"></i>
                            Negócios
                            <i class="bi bi-chevron-down ms-1"></i>
                        </button>
                        <div class="absolute top-full left-0 mt-1 w-48 bg-white rounded-md shadow-lg border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                            <a href="{{ route('customers.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <i class="bi bi-people me-2"></i>Clientes
                            </a>
                            <a href="{{ route('budgets.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <i class="bi bi-calculator me-2"></i>Orçamentos
                            </a>
                            <a href="{{ route('services.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <i class="bi bi-tools me-2"></i>Serviços
                            </a>
                            <a href="{{ route('invoices.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <i class="bi bi-receipt me-2"></i>Faturas
                            </a>
                        </div>
                    </div>
                    
                    <div class="relative group">
                        <button class="text-gray-700 hover:text-blue-600 transition-colors flex items-center">
                            <i class="bi bi-box me-1"></i>
                            Produtos
                            <i class="bi bi-chevron-down ms-1"></i>
                        </button>
                        <div class="absolute top-full left-0 mt-1 w-48 bg-white rounded-md shadow-lg border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                            <a href="{{ route('products.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <i class="bi bi-box-seam me-2"></i>Produtos
                            </a>
                            <a href="{{ route('categories.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <i class="bi bi-tags me-2"></i>Categorias
                            </a>
                            <a href="{{ route('providers.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <i class="bi bi-truck me-2"></i>Fornecedores
                            </a>
                        </div>
                    </div>
                    
                    <a href="{{ route('reports.index') }}" class="text-gray-700 hover:text-blue-600 transition-colors">
                        <i class="bi bi-graph-up me-1"></i>
                        Relatórios
                    </a>
                @endauth
            </div>
            
            <!-- User Menu -->
            <div class="flex items-center space-x-4">
                @auth
                    <!-- Notifications -->
                    <div class="relative">
                        <button class="text-gray-500 hover:text-gray-700 transition-colors">
                            <i class="bi bi-bell text-lg"></i>
                            @if(auth()->user()->unreadNotifications->count() > 0)
                                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                    {{ auth()->user()->unreadNotifications->count() }}
                                </span>
                            @endif
                        </button>
                    </div>
                    
                    <!-- User Dropdown -->
                    <div class="relative group">
                        <button class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 transition-colors">
                            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-medium">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <span class="hidden md:block">{{ auth()->user()->name }}</span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="absolute top-full right-0 mt-1 w-48 bg-white rounded-md shadow-lg border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                            <a href="{{ route('profile.show') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <i class="bi bi-person me-2"></i>Perfil
                            </a>
                            <a href="{{ route('settings.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <i class="bi bi-gear me-2"></i>Configurações
                            </a>
                            <div class="border-t border-gray-100"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="bi bi-box-arrow-right me-2"></i>Sair
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="btn-primary">
                        <i class="bi bi-box-arrow-in-right me-1"></i>
                        Entrar
                    </a>
                @endauth
            </div>
            
            <!-- Mobile Menu Button -->
            <div class="md:hidden">
                <button type="button" class="text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700" 
                        onclick="toggleMobileMenu()">
                    <i class="bi bi-list text-xl"></i>
                </button>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="md:hidden hidden border-t border-gray-200 py-4">
            @auth
                <a href="{{ route('home') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">
                    <i class="bi bi-house-door me-2"></i>Dashboard
                </a>
                <a href="{{ route('customers.index') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">
                    <i class="bi bi-people me-2"></i>Clientes
                </a>
                <a href="{{ route('budgets.index') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">
                    <i class="bi bi-calculator me-2"></i>Orçamentos
                </a>
                <a href="{{ route('services.index') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">
                    <i class="bi bi-tools me-2"></i>Serviços
                </a>
                <a href="{{ route('products.index') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">
                    <i class="bi bi-box-seam me-2"></i>Produtos
                </a>
                <a href="{{ route('reports.index') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">
                    <i class="bi bi-graph-up me-2"></i>Relatórios
                </a>
                <div class="border-t border-gray-200 mt-4 pt-4">
                    <a href="{{ route('profile.show') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">
                        <i class="bi bi-person me-2"></i>Perfil
                    </a>
                    <a href="{{ route('settings.index') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">
                        <i class="bi bi-gear me-2"></i>Configurações
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-50">
                            <i class="bi bi-box-arrow-right me-2"></i>Sair
                        </button>
                    </form>
                </div>
            @else
                <a href="{{ route('login') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
                </a>
            @endauth
        </div>
    </div>
</nav>

<script>
function toggleMobileMenu() {
    const menu = document.getElementById('mobile-menu');
    menu.classList.toggle('hidden');
}
</script>