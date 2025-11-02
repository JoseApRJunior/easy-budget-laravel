{{-- partials/shared/user-menu.blade.php --}}
{{-- Menu do usuário seguindo melhores práticas Laravel --}}

{{-- Botão de login --}}
@guest
    <li class="nav-item">
        <a class="nav-link btn btn-primary d-flex align-items-center" href="{{ route( 'login' ) }}">
            <i class="bi bi-box-arrow-in-right me-2"></i>
            <span>Entrar</span>
        </a>
    </li>
@endguest

{{-- Usuário autenticado - Dropdown com menu completo --}}
@auth
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button"
        data-bs-toggle="dropdown" aria-expanded="false">
        <img src="{{ auth()->user()->avatar_or_google_avatar }}" alt="Avatar" class="rounded-circle me-2 "
            style="object-fit: cover;" width="32" height="32">
        {{ Str::before( auth()->user()->name, ' ' ) ?: auth()->user()->name ?? auth()->user()->email }}
    </a>
    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userDropdown">

        {{-- Links comuns para todos os usuários --}}
        <li><a class="dropdown-item" href="{{ route( 'provider.plans.index' ) }}">
                <i class="bi bi-clipboard-check me-2"></i>Planos</a></li>
        <li><a class="dropdown-item" href="{{ route( 'settings.index' ) }}">
                <i class="bi bi-gear me-2"></i>Configurações</a></li>
        {{-- Acesso administrativo - apenas para admins --}}
        @role( 'admin' )
        <li>
            <hr class="dropdown-divider">
        </li>
        <li><a class="dropdown-item" href="{{ route( 'admin.dashboard' ) }}">
                <i class="bi bi-shield-lock me-2"></i>Administração</a></li>
        @endrole

        {{-- Acesso de gestão - para managers e editors --}}
        @anyrole( [ 'manager', 'editor' ] )
        <li><a class="dropdown-item" href="{{ route( 'manager.panel' ) }}">
                <i class="bi bi-kanban me-2"></i>Painel de Gestão</a></li>
        @endanyrole

        {{-- Separador e logout --}}
        <li>
            <hr class="dropdown-divider">
        </li>
        <li>
            <form action="{{ route( 'logout' ) }}" method="POST">
                @csrf
                <button class="dropdown-item text-danger" type="submit">
                    <i class="bi bi-box-arrow-right me-2"></i>Sair
                </button>
            </form>
        </li>
    </ul>
</li>
@endauth
