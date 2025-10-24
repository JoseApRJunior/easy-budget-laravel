<!-- Botão hamburguer para abrir/fechar sidebar -->
<button class="btn btn-outline-secondary d-lg-none mb-2" type="button" data-bs-toggle="offcanvas"
    data-bs-target="#sidebarMenu" aria-controls="sidebarMenu">
    <i class="bi bi-list"></i> Menu
</button>

<!-- Sidebar Offcanvas -->
<div class="offcanvas offcanvas-start sidebar" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="sidebarMenuLabel">EasyBudget</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
    </div>
    <div class="offcanvas-body">
        <ul class="nav flex-column">

            {{-- Sempre visível --}}
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center" href="{{ url( '/about' ) }}">
                    <i class="bi bi-info-circle me-2"></i> Sobre
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center" href="{{ url( '/support' ) }}">
                    <i class="bi bi-headset me-2"></i> Suporte
                </a>
            </li>

            {{-- Visitante --}}
            @guest
                <li class="nav-item mt-3">
                    <a class="btn btn-google w-100" href="{{ route( 'google' ) }}">
                        <i class="fab fa-google me-2"></i> Entrar com Google
                    </a>
                </li>
            @endguest

            {{-- Usuário autenticado --}}
            @auth
            <li class="nav-item mt-3">
                <span class="nav-link text-muted">
                    <i class="bi bi-person-circle me-2"></i>
                    {{ auth()->user()->name ?? auth()->user()->email }}
                </span>
            </li>

            <li class="nav-item">
                <a class="nav-link d-flex align-items-center" href="{{ route( 'provider.dashboard' ) }}">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>

            {{-- Apenas admin --}}
            @role( 'admin' )
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center" href="{{ route( 'admin.dashboard' ) }}">
                    <i class="bi bi-shield-lock me-2"></i> Administração
                </a>
            </li>
            @endrole

            {{-- Roles múltiplas --}}
            @anyrole( [ 'manager', 'editor' ] )
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center" href="{{ route( 'manager.panel' ) }}">
                    <i class="bi bi-kanban me-2"></i> Painel de Gestão
                </a>
            </li>
            @endanyrole

            <li class="nav-item mt-3">
                <form action="{{ route( 'logout' ) }}" method="POST">
                    @csrf
                    <button class="btn btn-outline-danger w-100" type="submit">
                        <i class="bi bi-box-arrow-right me-2"></i> Sair
                    </button>
                </form>
            </li>
            @endauth

        </ul>
    </div>
</div>
