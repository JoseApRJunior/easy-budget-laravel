@extends( 'layouts.app' )

@section( 'content' )
    <div class="container-fluid min-vh-75 d-flex align-items-center justify-content-center p-3 p-md-5">
        <div class="row justify-content-center w-100">
            <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-4">
                <div class="card shadow-lg border-0 fade-in">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <div class="logo-container mb-3">
                                <img src="{{ asset( 'assets/img/logo.png' ) }}" alt="Easy Budget Logo" class="logo-img"
                                    height="40">
                                <span class="logo-text">Easy Budget</span>
                            </div>
                            <h1 class="h3 fw-bold">Seja bem-vindo</h1>
                            <p class="text-muted small">Faça login para continuar</p>
                        </div>

                        @if ( $errors->any() )
                            <div class="alert alert-danger">
                                @foreach ( $errors->all() as $error )
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        @if ( session( 'status' ) )
                            <div class="alert alert-success">
                                {{ session( 'status' ) }}
                            </div>
                        @endif

                        @if ( session( 'resent' ) )
                            <div class="alert alert-success" role="alert">
                                Um novo link de verificação foi enviado para o seu endereço de e-mail.
                            </div>
                        @endif

                        <form action="{{ route( 'login' ) }}" method="post" class="needs-validation" novalidate>
                            @csrf
                            <div class="form-floating mb-3">
                                <input type="email" class="form-control" id="email" name="email"
                                    placeholder="nome@exemplo.com" required autocomplete="email" autofocus
                                    value="{{ old( 'email' ) }}">
                                <label for="email">Endereço de E-mail</label>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="password" name="password"
                                    placeholder="Senha" required autocomplete="current-password">
                                <label for="password">Senha</label>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                    <label class="form-check-label" for="remember">
                                        Lembrar-me
                                    </label>
                                </div>
                                <a href="{{ route( 'password.request' ) }}" class="small text-decoration-none">Esqueceu a
                                    senha?</a>
                            </div>

                            <div class="d-grid gap-2 mb-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>
                                    <span>Entrar</span>
                                </button>
                            </div>

                            <div class="text-center">
                                <p class="mb-0">Não tem uma conta?
                                    <a href="{{ route( 'register' ) }}" class="fw-bold text-decoration-none">Cadastre-se</a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
