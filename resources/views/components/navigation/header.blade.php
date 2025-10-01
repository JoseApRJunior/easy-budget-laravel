<header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo and Brand -->
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <h1 class="text-xl font-bold text-gray-900">
                        {{ config( 'app.name', 'Easy Budget' ) }}
                    </h1>
                </div>

                <!-- Desktop Navigation -->
                <nav class="hidden md:ml-8 md:flex md:space-x-8">
                    <a href="{{ route( 'dashboard' ) }}" class="text-gray-900 hover:text-blue-600 px-3 py-2 text-sm font-medium transition-colors
                              {{ request()->routeIs( 'dashboard' ) ? 'text-blue-600 border-b-2 border-blue-600' : '' }}">
                        Dashboard
                    </a>
                    <a href="{{ route( 'budgets.index' ) }}" class="text-gray-700 hover:text-blue-600 px-3 py-2 text-sm font-medium transition-colors
                              {{ request()->routeIs( 'budgets.*' ) ? 'text-blue-600 border-b-2 border-blue-600' : '' }}">
                        Orçamentos
                    </a>
                    <a href="{{ route( 'reports.index' ) }}" class="text-gray-700 hover:text-blue-600 px-3 py-2 text-sm font-medium transition-colors
                              {{ request()->routeIs( 'reports.*' ) ? 'text-blue-600 border-b-2 border-blue-600' : '' }}">
                        Relatórios
                    </a>
                </nav>
            </div>

            <!-- Right Section -->
            <div class="flex items-center space-x-4">
                <!-- Notifications -->
                <button class="text-gray-400 hover:text-gray-600 transition-colors" x-data="{ open: false }"
                    @click="open = !open">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-5 5v-5zM4.868 12.683A17.925 17.925 0 0112 21c7.962 0 12-1.21 12-4.586 0-3.376-4.038-4.586-12-4.586S0 12.683 0 16.059c0 3.376 4.038 4.586 12 4.586a17.925 17.925 0 01-7.132-1.962z" />
                    </svg>
                </button>

                <!-- User Menu -->
                <div class="relative" x-data="dropdown()">
                    <button @click="toggle()"
                        class="flex items-center space-x-2 text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                            <span class="text-white text-sm font-medium">
                                {{ substr( Auth::user()->name, 0, 1 ) }}
                            </span>
                        </div>
                        <span class="hidden md:block text-gray-700">{{ Auth::user()->name }}</span>
                        <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div x-show="open" x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200"
                        @click.away="close()">

                        <a href="{{ route( 'profile.show' ) }}"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            Perfil
                        </a>
                        <a href="{{ route( 'settings.index' ) }}"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            Configurações
                        </a>
                        <div class="border-t border-gray-100"></div>
                        <form method="POST" action="{{ route( 'logout' ) }}" class="block">
                            @csrf
                            <button type="submit"
                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Sair
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Mobile menu button -->
                <button class="md:hidden text-gray-600 hover:text-gray-900" x-data="{ mobileMenuOpen: false }"
                    @click="$dispatch('toggle-mobile-menu')">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation Menu -->
    <div x-show="$store.mobileMenu.open" x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95" class="md:hidden bg-white border-t border-gray-200"
        x-trap="open">

        <div class="px-2 pt-2 pb-3 space-y-1">
            <a href="{{ route( 'dashboard' ) }}" class="block px-3 py-2 text-base font-medium text-gray-900 hover:text-blue-600 hover:bg-gray-50 rounded-md
                      {{ request()->routeIs( 'dashboard' ) ? 'text-blue-600 bg-blue-50' : '' }}">
                Dashboard
            </a>
            <a href="{{ route( 'budgets.index' ) }}" class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50 rounded-md
                      {{ request()->routeIs( 'budgets.*' ) ? 'text-blue-600 bg-blue-50' : '' }}">
                Orçamentos
            </a>
            <a href="{{ route( 'reports.index' ) }}" class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50 rounded-md
                      {{ request()->routeIs( 'reports.*' ) ? 'text-blue-600 bg-blue-50' : '' }}">
                Relatórios
            </a>
        </div>
    </div>
</header>
