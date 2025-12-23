@extends('layouts.app')

@section('content')
    <div class="container py-2">
        <!-- Formulário -->
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-5">
                        <form method="POST" action="{{ route('login') }}" id="loginForm">
                            @csrf

                            <!-- Seções do formulário -->
                            <div class="form-section">
                                <h5 class="section-title">
                                    <i class="bi bi-person-circle me-2"></i>Dados de Acesso
                                </h5>

                                <!-- Email -->
                                <div class="mb-4">
                                    <label for="email" class="form-label fw-semibold">
                                        Endereço de E-mail <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-envelope"></i>
                                        </span>
                                        <input id="email" name="email" type="email" autocomplete="email" required
                                            value="{{ old('email') }}"
                                            class="form-control @error('email') is-invalid @enderror"
                                            placeholder="seu@email.com">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Senha -->
                                <div class="mb-4">
                                    <label for="password" class="form-label fw-semibold">
                                        Senha <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-key"></i>
                                        </span>
                                        <input id="password-input" name="password" type="password"
                                            autocomplete="current-password" required
                                            class="form-control @error('password') is-invalid @enderror"
                                            placeholder="Digite sua senha">
                                        <span class="input-group-text" style="cursor: pointer;" onclick="togglePassword()">
                                            <i class="bi bi-eye" id="password-icon"></i>
                                        </span>
                                        @error('password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Dicas de segurança -->
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="bi bi-shield-check me-1"></i>
                                            Use uma senha forte com pelo menos 8 caracteres
                                        </small>
                                    </div>
                                </div>

                                <!-- Opções adicionais -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input id="remember_me" name="remember" type="checkbox"
                                                class="form-check-input @error('remember') is-invalid @enderror">
                                            <label for="remember_me" class="form-check-label">
                                                Lembrar de mim
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-md-6 text-end">
                                        @if (Route::has('password.request'))
                                            <a href="{{ route('password.request') }}" class="text-decoration-none">
                                                <i class="bi bi-question-circle me-1"></i>
                                                Esqueceu a senha?
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Botões de ação -->
                            <div class="row pt-4 border-top">
                                <div class="col-12 col-md-4 mb-2 mb-md-0">
                                    <a href="{{ route('register') }}" class="btn btn-primary w-100">
                                        <i class="bi bi-person-plus me-2"></i>Criar Conta
                                    </a>
                                </div>
                                <div class="col-12 col-md-4 mb-2 mb-md-0">
                                    <a href="{{ route('auth.google') }}" class="btn btn-google btn-primary w-100">
                                        <i class="bi bi-google me-1"></i>
                                        <span class="d-none d-md-inline">Continuar com</span> Google
                                    </a>
                                </div>
                                <div class="col-12 col-md-4">
                                    <button type="submit" class="btn btn-primary btn-lg w-100">
                                        <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Credenciais de teste (apenas em desenvolvimento) -->
                        @if (app()->environment('local'))
                            <div class="mt-4 pt-4 border-top">
                                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <h6 class="alert-heading mb-1">
                                                <i class="bi bi-info-circle me-2"></i>Credenciais de Teste
                                            </h6>
                                            <small class="d-block text-muted">
                                                <strong>Email:</strong>
                                                <span> provider1@test.com</span>
                                                <strong>Senha:</strong>
                                                <span>Password1@</span>
                                            </small>
                                        </div>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"
                                            aria-label="Fechar"></button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
@endpush

@push('scripts')
    <script>
        // Funcionalidades do formulário de login
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');

            if (form) {
                // Máscaras de entrada
                setupInputMasks();

                // Validação em tempo real
                setupRealTimeValidation();

                // Submit com indicador de loading
                form.addEventListener('submit', function(e) {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Entrando...';
                        submitBtn.disabled = true;
                    }
                });
            }

            function setupInputMasks() {
                // Máscara básica para email se necessário
                const emailInput = document.getElementById('email');
                if (emailInput) {
                    emailInput.addEventListener('blur', function() {
                        const email = this.value.trim();
                        if (email && !email.includes('@')) {
                            this.classList.add('is-invalid');
                        } else {
                            this.classList.remove('is-invalid');
                        }
                    });
                }
            }

            function setupRealTimeValidation() {
                // Validação básica em tempo real
                const requiredInputs = form.querySelectorAll('input[required]');
                requiredInputs.forEach(input => {
                    input.addEventListener('blur', function() {
                        if (this.value.trim() === '') {
                            this.classList.add('is-invalid');
                        } else {
                            this.classList.remove('is-invalid');
                        }
                    });
                });
            }
        });

        // Password visibility toggle
        function togglePassword() {
            const passwordInput = document.getElementById('password-input');
            const passwordIcon = document.getElementById('password-icon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.className = 'bi bi-eye-slash';
            } else {
                passwordInput.type = 'password';
                passwordIcon.className = 'bi bi-eye';
            }
        }
    </script>
@endpush
