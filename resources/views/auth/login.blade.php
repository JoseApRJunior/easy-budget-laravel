{{-- resources/views/auth/login.blade.php --}}
{{-- Página de Login integrada ao tema do sistema --}}

@extends( 'layouts.app' )

@section( 'content' )
    {{-- Breadcrumbs --}}
    @section( 'breadcrumbs' )
        <li class="breadcrumb-item active d-flex align-items-center">
            <i class="bi bi-box-arrow-in-right me-2"></i>
            Login
        </li>
    @endsection

    {{-- Login Section --}}
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-xl-8 col-lg-9 col-md-10">
                <div class="text-center mb-5">
                    <div class="d-inline-flex align-items-center justify-content-center w-100 mb-4" style="height: 100px;">
                        <div class="bg-opacity-10 p-4 rounded-circle">
                            <i class="bi bi-person-circle " style="font-size: 4rem;"></i>
                        </div>
                    </div>
                    <h1 class="display-5 fw-bold mb-3">Entrar na sua conta</h1>
                    <p class="lead">Acesse o painel do Easy Budget</p>
                </div>

                {{-- Login Form --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-5">
                        <form method="POST" action="{{ route( 'login' ) }}">
                            @csrf

                            {{-- Email Field --}}
                            <div class="mb-4">
                                <label for="email" class="form-label fw-semibold">
                                    <i class="bi bi-envelope me-2"></i>Endereço de E-mail
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-envelope"></i>
                                    </span>
                                    <input id="email" name="email" type="email" autocomplete="email" required
                                        value="{{ old( 'email' ) }}"
                                        class="form-control @error( 'email' ) is-invalid @enderror"
                                        placeholder="seu@email.com">
                                    @error( 'email' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- Password Field --}}
                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold">
                                    <i class="bi bi-key me-2"></i>Senha
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-key"></i>
                                    </span>
                                    <input id="password-input" name="password" type="password"
                                        autocomplete="current-password" required
                                        class="form-control @error( 'password' ) is-invalid @enderror"
                                        placeholder="••••••••">
                                    <span class="input-group-text" style="cursor: pointer;" onclick="togglePassword()">
                                        <i class="bi bi-eye" id="password-icon"></i>
                                    </span>
                                    @error( 'password' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            {{-- Remember Me --}}
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input id="remember_me" name="remember" type="checkbox"
                                            class="form-check-input @error( 'remember' ) is-invalid @enderror">
                                        <label for="remember_me" class="form-check-label">
                                            Lembrar de mim
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-6 text-end">
                                    @if ( Route::has( 'password.request' ) )
                                        <a href="{{ route( 'password.request' ) }}" class="text-decoration-none">
                                            <i class="bi bi-question-circle me-1"></i>
                                            Esqueceu a senha?
                                        </a>
                                    @endif
                                </div>
                            </div>

                            {{-- Submit Button --}}
                            <div class="d-grid mb-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>
                                    Entrar
                                </button>
                            </div>

                            {{-- Register Link --}}
                            <div class="text-center">
                                <p class="mb-0">
                                    Não tem uma conta?
                                    <a href="{{ route( 'register' ) }}" class="text-decoration-none">
                                        <i class="bi bi-person-plus me-1"></i>
                                        Cadastre-se gratuitamente
                                    </a>
                                </p>
                            </div>
                    </div>
                </div>

                {{-- Demo Credentials (only in development) --}}
                @if( app()->environment( 'local' ) )
                    <div class="alert alert-warning d-flex align-items-center">
                        <i class="bi bi-info-circle me-3" style="font-size: 1.2rem;"></i>
                        <div>
                            <h6 class="alert-heading mb-2 fw-bold">Credenciais de Teste</h6>
                            <p class="mb-0"><strong>Prestador:</strong> provider@easybudget.com / password</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    </div>
    </div>
@endsection

{{-- Custom Scripts --}}
@section( 'scripts' )
@endsection

@section( 'content' )
@endsection

{{-- Scripts inline no final da página --}}
<script>
    // Password visibility toggle - versão mais simples
    function togglePassword() {
        var passwordInput = document.getElementById( 'password-input' );
        var passwordIcon = document.getElementById( 'password-icon' );

        if ( passwordInput.type === 'password' ) {
            passwordInput.type = 'text';
            passwordIcon.className = 'bi bi-eye-slash';
        } else {
            passwordInput.type = 'password';
            passwordIcon.className = 'bi bi-eye';
        }
    }
</script>
