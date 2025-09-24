<li class="nav-item dropdown">
    @auth
        <a class="nav-link dropdown-toggle d-flex align-items-center px-3 rounded-pill mx-1 nav-link-hover user-menu-toggle"
            id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <div class="user-avatar me-2">
                <i class="bi bi-person-circle fs-5"></i>
            </div>
            <span
                class="d-none d-lg-inline">{{ auth()->user()->email ? ucfirst( explode( '@', auth()->user()->email )[ 0 ] ) : 'Usuário' }}</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 mt-2" aria-labelledby="userDropdown">
            <li>
                <h6 class="dropdown-header text-primary"><i class="bi bi-person me-2"></i>Minha Conta</h6>
            </li>
            <li>
                <a class="dropdown-item py-2" href="{{ url( '/about' ) }}">
                    <i class="bi bi-info-circle me-2 text-info"></i>Sobre
                </a>
            </li>
            <li>
                <a class="dropdown-item py-2" href="{{ url( '/plans' ) }}">
                    <i class="bi bi-clipboard-check me-2 text-success"></i>Planos
                </a>
            </li>
            <li>
                <a class="dropdown-item py-2" href="{{ url( '/provider/integrations/mercadopago' ) }}">
                    <i class="bi bi-credit-card-2-front-fill me-2 text-primary"></i>Mercado Pago
                </a>
            </li>
            <li>
                <a class="dropdown-item py-2" href="{{ url( '/settings' ) }}">
                    <i class="bi bi-gear me-2 text-secondary"></i>Configurações
                </a>
            </li>
            <li>
                <a class="dropdown-item py-2" href="{{ url( '/support' ) }}">
                    <i class="bi bi-headset me-2 text-warning"></i>Suporte
                </a>
            </li>
            <li>
                <hr class="dropdown-divider my-2">
            </li>
            <li>
                <form id="logoutForm" action="{{ url( '/logout' ) }}" method="post">
                    @csrf
                    <input type="hidden" name="__method" value="DELETE">
                    <button class="dropdown-item text-danger py-2" type="submit">
                        <i class="bi bi-box-arrow-right me-2"></i>Sair
                    </button>
                </form>
            </li>
        </ul>
    @else
        <a class="nav-link btn btn-primary btn-sm px-4 rounded-pill mx-1 login-btn" href="{{ url( '/login' ) }}">
            <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
        </a>
    @endauth
</li>
