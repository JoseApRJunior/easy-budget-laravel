<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container-fluid">
        <!-- Logo/Brand -->
        <a class="navbar-brand d-flex align-items-center" href="{{ route( 'dashboard' ) ?? '/' }}">
            <i class="bi bi-calculator me-2"></i>
            <strong>Easy Budget</strong>
        </a>

        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation Menu -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs( 'dashboard' ) ? 'active' : '' }}"
                        href="{{ route( 'dashboard' ) ?? '/' }}">
                        <i class="bi bi-speedometer2 me-1"></i>
                        Dashboard
                    </a>
                </li>

                <!-- Plans -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs( 'plans.*' ) ? 'active' : '' }}"
                        href="{{ route( 'plans.index' ) }}">
                        <i class="bi bi-diagram-3 me-1"></i>
                        Planos
                    </a>
                </li>

                <!-- Users -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs( 'users.*' ) ? 'active' : '' }}"
                        href="{{ route( 'users.index' ) }}">
                        <i class="bi bi-people me-1"></i>
                        Usuários
                    </a>
                </li>

                <!-- Budgets -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs( 'budgets.*' ) ? 'active' : '' }}"
                        href="{{ route( 'budgets.index' ) }}">
                        <i class="bi bi-receipt me-1"></i>
                        Orçamentos
                    </a>
                </li>
            </ul>

            <!-- User Menu -->
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-1"></i>
                        {{ Auth::user()->name ?? 'Usuário' }}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li>
                            <a class="dropdown-item" href="#"
                                onclick="alert('Funcionalidade de perfil em desenvolvimento')">
                                <i class="bi bi-person me-2"></i>
                                Perfil
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#"
                                onclick="alert('Funcionalidade de configurações em desenvolvimento')">
                                <i class="bi bi-gear me-2"></i>
                                Configurações
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form method="POST" action="{{ route( 'logout' ) ?? '#' }}" class="d-inline">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i>
                                    Sair
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Breadcrumb -->
@if( isset( $breadcrumbs ) )
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route( 'dashboard' ) ?? '/' }}">
                    <i class="bi bi-house-door"></i> Início
                </a>
            </li>
            @foreach( $breadcrumbs as $breadcrumb )
                @if( $loop->last )
                    <li class="breadcrumb-item active" aria-current="page">
                        {{ $breadcrumb[ 'title' ] }}
                    </li>
                @else
                    <li class="breadcrumb-item">
                        <a href="{{ $breadcrumb[ 'url' ] }}">{{ $breadcrumb[ 'title' ] }}</a>
                    </li>
                @endif
            @endforeach
        </ol>
    </nav>
@endif
