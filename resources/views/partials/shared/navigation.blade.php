{{-- partials/shared/navigation.blade.php --}}
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ url( '/' ) }}">
            EasyBudget
        </a>

        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">

                {{-- Visitante --}}
                @guest
                    <li class="nav-item">
                        <a class="btn btn-google btn-primary" href="{{ route( 'google.login' ) }}">
                            <i class="fab fa-google"></i> Entrar com Google
                        </a>
                    </li>
                @endguest

                {{-- Usuário autenticado --}}
                @auth
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown"
                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="{{ auth()->user()->avatar ?? asset( 'images/default-avatar.png' ) }}" alt="Avatar"
                            class="rounded-circle me-2" width="32" height="32">
                        {{ auth()->user()->name ?? auth()->user()->email }}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="{{ route( 'profile' ) }}">Meu Perfil</a></li>

                        @role( 'admin' )
                        <li><a class="dropdown-item" href="{{ route( 'admin.dashboard' ) }}">Administração</a></li>
                        @endrole

                        @anyrole( [ 'manager', 'editor' ] )
                        <li><a class="dropdown-item" href="{{ route( 'manager.panel' ) }}">Painel de Gestão</a></li>
                        @endanyrole

                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form action="{{ route( 'logout' ) }}" method="POST">
                                @csrf
                                <button class="dropdown-item" type="submit">Sair</button>
                            </form>
                        </li>
                    </ul>
                </li>
                @endauth

            </ul>
        </div>
    </div>
</nav>
