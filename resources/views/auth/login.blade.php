@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <x-ui.card class="border-0 shadow-lg">
                    <div class="p-4">
                        <div class="text-center mb-4">
                            <h4 class="fw-bold text-primary mb-2">Bem-vindo de volta!</h4>
                            <p class="text-muted small">Acesse sua conta para continuar</p>
                        </div>

                        <form method="POST" action="{{ route('login') }}" id="loginForm">
                            @csrf

                            <!-- Email -->
                            <div class="mb-4">
                                <label for="email" class="form-label fw-bold small text-muted text-uppercase">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="bi bi-envelope text-muted"></i>
                                    </span>
                                    <input id="email" name="email" type="email" autocomplete="email" required
                                        value="{{ old('email') }}"
                                        class="form-control border-start-0 ps-0 @error('email') is-invalid @enderror"
                                        placeholder="seu@email.com">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="password" class="form-label fw-bold small text-muted text-uppercase mb-0">Senha</label>
                                    @if (Route::has('password.request'))
                                        <a href="{{ route('password.request') }}" class="small text-decoration-none text-primary fw-bold">
                                            Esqueceu a senha?
                                        </a>
                                    @endif
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="bi bi-key text-muted"></i>
                                    </span>
                                    <input id="password-input" name="password" type="password"
                                        autocomplete="current-password" required
                                        class="form-control border-start-0 ps-0 @error('password') is-invalid @enderror"
                                        placeholder="Digite sua senha">
                                    <span class="input-group-text bg-white border-start-0 cursor-pointer" onclick="togglePassword()">
                                        <i class="bi bi-eye text-muted" id="password-icon"></i>
                                    </span>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Remember Me -->
                            <div class="mb-4">
                                <div class="form-check">
                                    <input id="remember_me" name="remember" type="checkbox"
                                        class="form-check-input @error('remember') is-invalid @enderror">
                                    <label for="remember_me" class="form-check-label small text-muted">
                                        Lembrar de mim neste dispositivo
                                    </label>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="d-grid gap-3">
                                <x-ui.button type="submit" variant="primary" size="lg" icon="box-arrow-in-right" label="Entrar" />
                                
                                <div class="position-relative text-center my-2">
                                    <hr class="position-absolute w-100 top-50 translate-middle-y my-0 text-muted opacity-25">
                                    <span class="position-relative bg-white px-3 small text-muted">ou</span>
                                </div>

                                <x-ui.button href="{{ route('auth.google') }}" variant="outline-danger" icon="google" label="Continuar com Google" />
                            </div>

                            <div class="text-center mt-4">
                                <p class="small text-muted mb-0">
                                    Não tem uma conta? 
                                    <a href="{{ route('register') }}" class="fw-bold text-primary text-decoration-none">Cadastre-se</a>
                                </p>
                            </div>
                        </form>

                        @if (app()->environment('local'))
                            <div class="mt-4 pt-4 border-top bg-light p-3 rounded small">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-info-circle-fill text-warning me-2"></i>
                                    <strong class="text-dark">Ambiente de Teste</strong>
                                </div>
                                <div class="d-flex justify-content-between text-muted">
                                    <span>Email: <strong>provider1@test.com</strong></span>
                                    <span>Senha: <strong>Password1@</strong></span>
                                </div>
                            </div>
                        @endif
                    </div>
                </x-ui.card>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password-input');
            const passwordIcon = document.getElementById('password-icon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.className = 'bi bi-eye-slash text-muted';
            } else {
                passwordInput.type = 'password';
                passwordIcon.className = 'bi bi-eye text-muted';
            }
        }
    </script>
@endpush
