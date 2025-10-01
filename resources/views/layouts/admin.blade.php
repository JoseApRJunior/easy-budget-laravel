@extends( 'layouts.app' )

@section( 'title', 'Administração - ' . ( $title ?? 'Dashboard' ) )

@push( 'styles' )
    <style>
        /* Estilos específicos do admin com Alpine.js */
        [x-cloak] {
            display: none !important;
        }
    </style>
@endpush

@section( 'content' )
    <div class="flex bg-gray-100 min-h-screen" x-data="adminLayout()">
        <!-- Sidebar -->
        <aside class="bg-white shadow-lg border-r border-gray-200"
            :class="{ '-translate-x-full md:translate-x-0': !sidebarOpen, 'translate-x-0': sidebarOpen }"
            class="fixed md:static inset-y-0 left-0 z-50 w-80 bg-white shadow-lg border-r border-gray-200 transform transition-transform duration-300 ease-in-out md:translate-x-0">

            <!-- Mobile overlay -->
            <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-600 bg-opacity-75 md:hidden"
                @click="closeSidebar()"></div>

            <!-- Sidebar content -->
            <div class="flex flex-col h-full">
                <!-- Logo/Brand -->
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-900">
                        {{ config( 'app.name', 'Easy Budget' ) }}
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">Administração</p>
                </div>

                <!-- Navigation Menu -->
                <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                    <x-admin.sidebar :active="$active ?? 'dashboard'" />
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
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col md:ml-0">
            <!-- Mobile menu button -->
            <div class="bg-white shadow-sm border-b border-gray-200 p-4 md:hidden">
                <button @click="toggleSidebar()"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    Menu
                </button>
            </div>

            <!-- Content area -->
            <div class="flex-1 p-4 md:p-8">
                <!-- Breadcrumb -->
                <x-admin.breadcrumb :items="$breadcrumb ?? []" class="mb-6" />

                <!-- Page Header -->
                @hasSection( 'page-header' )
                    <div class="mb-8">
                        @yield( 'page-header' )
                    </div>
                @endif

                <!-- Page Content -->
                <div class="bg-white rounded-lg shadow-sm">
                    @yield( 'admin-content' )
                </div>
            </div>
        </div>
    </div>
@endsection

@push( 'scripts' )
    <script>
        // Alpine.js data for admin layout
        function adminLayout() {
            return {
                sidebarOpen: false,

                toggleSidebar() {
                    this.sidebarOpen = !this.sidebarOpen;
                },

                closeSidebar() {
                    this.sidebarOpen = false;
                }
            }
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener( 'DOMContentLoaded', function () {
            document.addEventListener( 'click', function ( event ) {
                const sidebar = document.querySelector( 'aside' );
                const mobileMenuButton = document.querySelector( '[x-data*="adminLayout"] button' );

                if ( window.innerWidth < 768 &&
                    !sidebar.contains( event.target ) &&
                    !mobileMenuButton?.contains( event.target ) ) {
                    // Dispatch event to close sidebar
                    window.dispatchEvent( new CustomEvent( 'close-sidebar' ) );
                }
            } );

            // Listen for close sidebar events
            window.addEventListener( 'close-sidebar', function () {
                const adminLayoutElement = document.querySelector( '[x-data*="adminLayout"]' );
                if ( adminLayoutElement && adminLayoutElement._x_dataStack ) {
                    adminLayoutElement._x_dataStack[0].sidebarOpen = false;
                }
            } );
        } );
    </script>
@endpush
