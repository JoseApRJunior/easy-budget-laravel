@extends('layouts.app')

@section('content')
    <div class="container py-1">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-key me-2"></i>Recuperação de Senha
                </h1>
                <p class="text-muted mb-0">Digite seu e-mail para receber o link de reset</p>
            </div>
        </div>

        <!-- Formulário -->
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-5">
                        <form method="POST" action="{{ route('password.email') }}" id="forgotPasswordForm">
                            @csrf

                            <!-- Dados de Recuperação -->
                            <div class="form-section">
                                <h5 class="section-title">
                                    <i class="bi bi-envelope-arrow-up me-2"></i>Recuperação de Senha
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
                                        <input id="email" name="email" type="email" value="{{ old('email') }}"
                                            required autofocus class="form-control @error('email') is-invalid @enderror"
                                            placeholder="seu@email.com">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Instruções -->
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="bi bi-info-circle me-1"></i>
                                            Digite o e-mail associado à sua conta. Você receberá um link para criar uma nova
                                            senha.
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- Botões de ação -->
                            <div class="d-flex justify-content-between pt-4 border-top">
                                <a href="{{ route('login') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Voltar ao Login
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-envelope-check me-2"></i>Enviar Link de Reset
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* Estilos específicos do formulário de recuperação */
        .form-section {
            background: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .section-title {
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
        }

        .input-group-text {
            background-color: #e9ecef;
            border-color: #ced4da;
            color: #495057;
        }

        .is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }

        .invalid-feedback {
            display: block;
        }

        .btn-outline-secondary {
            border-color: #6c757d;
            color: #6c757d;
        }

        .btn-outline-secondary:hover {
            background-color: #6c757d;
            border-color: #6c757d;
            color: white;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            .d-flex.justify-content-between {
                flex-direction: column;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        // Funcionalidades do formulário de recuperação
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('forgotPasswordForm');

            if (form) {
                // Validação em tempo real
                setupRealTimeValidation();

                // Submit com indicador de loading
                form.addEventListener('submit', function(e) {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Enviando...';
                        submitBtn.disabled = true;
                    }
                });
            }

            function setupRealTimeValidation() {
                const emailInput = document.getElementById('email');

                if (emailInput) {
                    emailInput.addEventListener('blur', function() {
                        const email = this.value.trim();
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                        if (email && !emailRegex.test(email)) {
                            this.classList.add('is-invalid');
                        } else {
                            this.classList.remove('is-invalid');
                        }
                    });

                    emailInput.addEventListener('input', function() {
                        if (this.classList.contains('is-invalid')) {
                            this.classList.remove('is-invalid');
                        }
                    });
                }
            }
        });
    </script>
@endpush
