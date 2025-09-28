<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm sticky-top">
    <div class="container">
        <!-- Logo/Brand -->
        <a class="navbar-brand fw-bold" href="{{ route( 'home' ) }}">
            <i class="bi bi-calculator me-2"></i>Easy Budget
        </a>

        <!-- Mobile Menu Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation Menu -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Menu Público (para visitantes e usuários autenticados) -->
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs( 'home' ) ? 'active' : '' }}" href="{{ route( 'home' ) }}">
                        <i class="bi bi-house me-1"></i>Início
                    </a>
                </li>

                @auth
                    <!-- Menu Provider -->
                    @if( auth()->check() && auth()->user() && ( auth()->user()->email === 'admin@easybudget.com' || auth()->user()->email === 'provider@easybudget.com' ) )
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->routeIs( 'provider.*' ) ? 'active' : '' }}"
                                href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-speedometer2 me-1"></i>Provider
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route( 'provider.dashboard' ) }}">
                                        <i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                                <li><a class="dropdown-item" href="{{ route( 'provider.budgets.index' ) }}">
                                        <i class="bi bi-receipt me-2"></i>Orçamentos</a></li>
                                <li><a class="dropdown-item" href="{{ route( 'provider.reports.index' ) }}">
                                        <i class="bi bi-bar-chart me-2"></i>Relatórios</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="{{ route( 'provider.profile' ) }}">
                                        <i class="bi bi-person me-2"></i>Perfil</a></li>
                            </ul>
                        </li>
                    @endif

                    <!-- Menu Admin -->
                    @if( auth()->check() && auth()->user() && auth()->user()->email === 'admin@easybudget.com' )
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->routeIs( 'admin.*' ) ? 'active' : '' }}" href="#"
                                role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-shield-check me-1"></i>Admin
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route( 'admin.dashboard' ) }}">
                                        <i class="bi bi-house me-2"></i>Home Admin</a></li>
                                <li><a class="dropdown-item" href="{{ route( 'admin.users.index' ) }}">
                                        <i class="bi bi-people me-2"></i>Usuários</a></li>
                                <li><a class="dropdown-item" href="{{ route( 'admin.plans.index' ) }}">
                                        <i class="bi bi-card-checklist me-2"></i>Planos</a></li>
                                <li><a class="dropdown-item" href="{{ route( 'admin.monitoring.index' ) }}">
                                        <i class="bi bi-graph-up me-2"></i>Monitoramento</a></li>
                                <li><a class="dropdown-item" href="{{ route( 'admin.logs' ) }}">
                                        <i class="bi bi-journal-text me-2"></i>Logs</a></li>
                                <li><a class="dropdown-item" href="{{ route( 'admin.activities' ) }}">
                                        <i class="bi bi-activity me-2"></i>Atividades</a></li>
                            </ul>
                        </li>
                    @endif
                @endauth

                <!-- Menu Público Geral -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs( 'about' ) ? 'active' : '' }}"
                        href="{{ route( 'about' ) }}">
                        <i class="bi bi-info-circle me-1"></i>Sobre
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs( 'support' ) ? 'active' : '' }}"
                        href="{{ route( 'support' ) }}">
                        <i class="bi bi-headset me-1"></i>Suporte
                    </a>
                </li>
            </ul>

            <!-- Menu do Usuário (lado direito) -->
            <ul class="navbar-nav">
                <!-- Botão de Toggle Tema -->
                <li class="nav-item">
                    <button class="nav-link theme-toggle" onclick="toggleTheme()" title="Alternar Tema"
                        aria-label="Alternar Tema">
                        <i class="bi bi-sun-fill theme-light-icon" aria-hidden="true"></i>
                        <i class="bi bi-moon-stars-fill theme-dark-icon" aria-hidden="true"></i>
                    </button>
                </li>

                @guest
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light btn-sm px-3 me-2" href="{{ route( 'login' ) }}">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Entrar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-light btn-sm px-3" href="{{ route( 'register' ) }}">
                            <i class="bi bi-person-plus me-1"></i>Cadastrar
                        </a>
                    </li>
                @endguest

                @auth
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-2"></i>
                            <span class="d-lg-inline d-none">{{ auth()->user()->name ?? 'Usuário' }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li><a class="dropdown-item" href="{{ route( 'provider.profile' ) }}">
                                    <i class="bi bi-person me-2"></i>Meu Perfil</a></li>
                            <li><a class="dropdown-item" href="{{ route( 'settings.index' ) }}">
                                    <i class="bi bi-gear me-2"></i>Configurações</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <form method="POST" action="{{ route( 'logout' ) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i>Sair
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>
