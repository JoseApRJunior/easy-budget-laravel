{{-- partials/shared/user-menu.blade.php --}}
{{-- Menu do usuário autenticado --}}
@auth
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle d-flex align-items-center" id="userDropdown" role="button"
            data-bs-toggle="dropdown" aria-expanded="false">
            <img src="{{ Auth::user()->avatar ?? asset( 'images/default-avatar.png' ) }}" alt="Avatar"
                class="rounded-circle me-2" width="28" height="28">
            <span>{{ Auth::user()->name ?? Auth::user()->email }}</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userDropdown">
            <li><a class="dropdown-item" href="/plans"><i class="bi bi-clipboard-check me-2"></i>Planos</a></li>
            <li><a class="dropdown-item" href="/settings"><i class="bi bi-gear me-2"></i>Configurações</a></li>
            <li>
                <hr class="dropdown-divider">
            </li>
            <li>
                <form id="logoutForm" action="{{ route( 'logout' ) }}" method="POST">
                    @csrf
                    <button class="dropdown-item text-danger" type="submit">
                        <i class="bi bi-box-arrow-right me-2"></i>Sair
                    </button>
                </form>
            </li>
        </ul>
    </li>
@endauth

{{-- Botão de login --}}
@guest
    <li class="nav-item">
        <a class="nav-link btn btn-primary d-flex align-items-center" href="{{ route( 'login' ) }}">
            <i class="bi bi-box-arrow-in-right me-2"></i>
            <span>Entrar</span>
        </a>
    </li>
@endguest
