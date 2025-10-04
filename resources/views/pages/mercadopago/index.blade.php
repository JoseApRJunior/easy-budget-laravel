@extends( 'layout' )

@section( 'content' )
    <div class="container-fluid py-4">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-credit-card-2-front-fill me-2"></i>Integração com Mercado Pago
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ url( '/provider' ) }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Integração Mercado Pago</li>
                </ol>
            </nav>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light border-0">
                <h5 class="mb-0">Status da Conexão</h5>
            </div>
            <div class="card-body">
                @if ( $isConnected )
                    <div class="alert alert-success d-flex align-items-center" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <div>
                            <strong>Conectado!</strong> Sua conta Mercado Pago está vinculada com sucesso.
                        </div>
                    </div>
                    <p class="text-muted">Sua chave pública é: <code>{{ $publicKey }}</code></p>
                    <p>Agora você pode gerar faturas e receber pagamentos diretamente na sua conta.</p>
                    <form action="{{ url( '/provider/integrations/mercadopago/disconnect' ) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-lg me-2"></i>Desconectar Conta
                        </button>
                    </form>
                @else
                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div>
                            <strong>Não conectado.</strong> Vincule sua conta Mercado Pago para começar a receber pagamentos.
                        </div>
                    </div>
                    <p>Ao conectar sua conta, você autoriza o Easy Budget a criar cobranças em seu nome. Nós não temos acesso à
                        sua
                        senha ou dados financeiros.</p>
                    <a href="{{ $mercadoPagoAuthUrl }}" class="btn btn-primary btn-lg">
                        <i class="bi bi-link-45deg me-2"></i>Conectar com Mercado Pago
                    </a>
                @endif
            </div>
            <div class="card-footer bg-transparent border-0">
                <small class="text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    A integração permite que seus clientes paguem faturas diretamente para sua conta Mercado Pago. Uma taxa
                    de
                    serviço da plataforma pode ser aplicada.
                </small>
            </div>
        </div>
    </div>
@endsection
