{{-- partials/shared/user-menu.blade.php --}}
{{-- Menu do usuário autenticado --}}
@if( session()->has( 'auth' ) )
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle d-flex align-items-center" id="userDropdown" role="button"
            data-bs-toggle="dropdown" aria-expanded="false">
            @php
                $email    = session( 'auth.email' );
                $username = explode( '@', $email )[ 0 ];
            @endphp
            <span>{{ ucwords( $username ) }}</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userDropdown">
            <li>
                <a class="dropdown-item" href="/about">
                    <i class="bi bi-info-circle me-1"></i>Sobre
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="/plans">
                    <i class="bi bi-clipboard-check me-2"></i>Planos
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="/provider/integrations/mercadopago">
                    <i class="bi bi-credit-card-2-front-fill me-2"></i>Integração Mercado Pago
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="/settings">
                    <i class="bi bi-gear me-2"></i>Configurações
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="/support">
                    <i class="bi bi-headset me-1"></i>Suporte
                </a>
            </li>
            <li>
                <hr class="dropdown-divider">
            </li>
            <li>
                <form id="logoutForm" action="/logout" method="post">
                    @csrf
                    @method( 'DELETE' )

                    <button class="dropdown-item text-danger" type="submit">
                        <i class="bi bi-box-arrow-right me-2"></i>Sair
                    </button>
                </form>
            </li>
        </ul>
    </li>
@endif

{{-- Botão de login - fora do dropdown --}}
@if( !session()->has( 'auth' ) )
    <li class="nav-item">
        <a class="nav-link btn btn-primary d-flex align-items-center" href="/login">
            <i class="bi bi-box-arrow-in-right me-2"></i>
            <span>Entrar</span>
        </a>
    </li>
@endif
</li>
