@extends( 'layouts.app' )

@section( 'content' )
    <main class="container py-5">
        <!-- Cabeçalho da página -->
        <div class="text-center mb-5">
            <h1 class="display-5 fw-bold  mb-3">
                Confirmação de E-mail
            </h1>
            <p class="lead text-muted">
                Verifique sua caixa de entrada para ativar sua conta
            </p>
        </div>

        <!-- Conteúdo principal -->
        <div class="row g-4">
            <div class="col-lg-8 mx-auto">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-5">
                        <!-- Ícone ilustrativo -->
                        <div class="text-center mb-4">
                            <i class="bi bi-envelope-check text-primary mb-3" style="font-size: 4rem;"></i>
                        </div>

                        <!-- Mensagem principal -->
                        <div class="text-center mb-4">
                            <h5 class="mb-3">E-mail de confirmação enviado!</h5>
                            <p class="text-muted mb-4">
                                Enviamos um link de confirmação para o endereço de e-mail fornecido durante o cadastro.
                                Clique no link para ativar sua conta e começar a usar o Easy Budget.
                            </p>
                        </div>

                        <!-- Session Status -->
                        <x-auth-session-status class="mb-4 text-center" :status="session( 'status' )" />

                        <!-- Instruções -->
                        <div class="alert alert-info" role="alert">
                            <h6 class="alert-heading mb-2">
                                <i class="bi bi-info-circle me-2"></i>O que fazer agora?
                            </h6>
                            <ul class="mb-0 text-start">
                                <li>Verifique sua caixa de entrada (e também a pasta de spam)</li>
                                <li>Clique no link de confirmação no e-mail</li>
                                <li>Após confirmar, você será redirecionado automaticamente</li>
                                <li>Caso não encontre o e-mail, use o botão abaixo para reenviar</li>
                            </ul>
                        </div>

                        <!-- Ações -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <form method="POST" action="{{ route( 'verification.send' ) }}" class="d-grid">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-envelope-arrow-up me-2"></i>
                                        Reenviar E-mail
                                    </button>
                                </form>
                            </div>

                            <div class="col-md-6">
                                <a href="{{ route( 'force-logout' ) }}" class="btn btn-outline-secondary d-grid">
                                    <i class="bi bi-box-arrow-right me-2"></i>
                                    Sair do Sistema
                                </a>
                            </div>
                        </div>

                        <!-- Ajuda -->
                        <div class="text-center mt-4 pt-4 border-top">
                            <small class="text-muted">
                                Não recebeu o e-mail?
                                <a href="{{ route( 'support' ) }}" class="text-decoration-none">
                                    Entre em contato conosco
                                </a>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push( 'styles' )

@endpush

@push( 'scripts' )
    <script>
        document.addEventListener( 'DOMContentLoaded', function () {
            const form = document.querySelector( 'form[action*="verification.send"]' );
            if ( form ) {
                form.addEventListener( 'submit', function ( e ) {
                    const button = form.querySelector( 'button[type="submit"]' );
                    if ( button ) {
                        button.value = 'Enviando...';
                        button.disabled = true;
                    }
                } );
            }
        } );
    </script>
@endpush
