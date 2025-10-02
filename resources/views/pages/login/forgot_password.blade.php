@extends( 'layouts.app' )

@section( 'content' )
    <div class="container-fluid min-vh-75 d-flex align-items-center justify-content-center p-3 p-md-5">
        <div class="row justify-content-center w-100">
            <div class="col-12 col-sm-11 col-md-9 col-lg-7 col-xl-5">
                <div class="card shadow-lg border-0 fade-in">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <div class="logo-container mb-3">
                                <img src="{{ asset( 'assets/img/logo.png' ) }}" alt="Easy Budget Logo" class="logo-img"
                                    height="40" width="40" loading="eager">
                                <span class="logo-text">Easy Budget</span>
                            </div>
                            <h1 class="h3 fw-bold text-primary mb-2">Recuperar Senha</h1>
                            <p class="text-muted small mb-0">Insira seu e-mail para receber o link de recuperação</p>
                        </div>

                        @if ( session( 'status' ) )
                            <div class="alert alert-success" role="alert">
                                {{ session( 'status' ) }}
                            </div>
                        @endif

                        <form action="{{ route( 'password.email' ) }}" method="post" id="forgotPasswordForm">
                            @csrf
                            <div class="form-floating mb-4">
                                <input type="email" class="form-control @error( 'email' ) is-invalid @enderror" id="email"
                                    name="email" placeholder="nome@exemplo.com" required autocomplete="email" autofocus
                                    value="{{ old( 'email' ) }}">
                                <label for="email">Endereço de E-mail</label>
                                @error( 'email' )
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg" id="submitButton">
                                    <i class="bi bi-envelope-fill me-2"></i>
                                    <span>Enviar Link</span>
                                </button>
                                <a href="{{ route( 'login' ) }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>
                                    <span>Voltar para o login</span>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <small class="text-muted">
                        <i class="bi bi-shield-lock me-1"></i>
                        <span>Recuperação segura via SSL</span>
                    </small>
                </div>
            </div>
        </div>
    </div>
@endsection
