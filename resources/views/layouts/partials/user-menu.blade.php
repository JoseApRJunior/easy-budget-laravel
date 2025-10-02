<li class="nav-item dropdown">
    @auth
        <a class="nav-link dropdown-toggle d-flex align-items-center" id="userDropdown" role="button"
            data-bs-toggle="dropdown" aria-expanded="false">
            <span>{{ explode('@', auth()->user()->email ?? 'user@example.com')[0] }}</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userDropdown">
            <li>
                <a class="dropdown-item" href="{{ route( 'about' ) }}">
                    <i class="bi bi-info-circle me-1"></i>Sobre
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route( 'plans.index' ) }}">
                    <i class="bi bi-clipboard-check me-2"></i>Planos
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route( 'provider.integrations.mercadopago' ) }}">
                    <i class="bi bi-credit-card-2-front-fill me-2"></i>Integração Mercado Pago
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route( 'settings.index' ) }}">
                    <i class="bi bi-gear me-2"></i>Configurações
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route( 'support' ) }}">
                    <i class="bi bi-headset me-1"></i>Suporte
                </a>
            </li>
            <li>
                <hr class="dropdown-divider">
            </li>
            <li>
                <form id="logoutForm" action="{{ route( 'logout' ) }}" method="post">
                    @csrf
                    @method( 'DELETE' )
                    <button class="dropdown-item text-danger" type="submit">
                        <i class="bi bi-box-arrow-right me-2"></i>Sair
                    </button>
                </form>
            </li>
        </ul>
    @else
        <a class="nav-link btn btn-primary btn-sm px-3" href="{{ route( 'login' ) }}">
            <i class="bi bi-box-arrow-in-right me-1"></i>Entrar
        </a>
    @endauth
</li>
