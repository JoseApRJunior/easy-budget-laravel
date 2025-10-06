@extends( 'layouts.app' )

@section( 'content' )
    <!-- Tela de Registro Aprimorada -->
    <div class="container-fluid  min-vh-100 d-flex align-items-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-10 col-lg-12 col-md-9">
                    <div class="card o-hidden border-0 shadow-lg">
                        <div class="card-body p-0">
                            <!-- Linha aninhada dentro do card para layout de duas colunas -->
                            <div class="row min-vh-100">
                                <div class="col-lg-6 d-flex align-items-center justify-content-center ">
                                    <div class="px-4 py-5 text-center w-100">
                                        <div class="mb-4">
                                            <i class="bi bi-graph-up display-3  mb-3"></i>
                                        </div>
                                        <h1 class="h3  mb-3 fw-bold">Bem-vindo ao Easy Budget!</h1>
                                        <p class="-50 mb-4 lead">
                                            Crie sua conta e comece a transformar a gestão do seu negócio hoje mesmo.
                                        </p>

                                        <div class="row text-start">
                                            <div class="col-sm-6 mb-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-check-circle-fill text-success me-2 fs-5"></i>
                                                    <span class="-50">Gestão financeira completa</span>
                                                </div>
                                            </div>
                                            <div class="col-sm-6 mb-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-check-circle-fill text-success me-2 fs-5"></i>
                                                    <span class="-50">Controle de orçamentos</span>
                                                </div>
                                            </div>
                                            <div class="col-sm-6 mb-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-check-circle-fill text-success me-2 fs-5"></i>
                                                    <span class="-50">Relatórios detalhados</span>
                                                </div>
                                            </div>
                                            <div class="col-sm-6 mb-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-check-circle-fill text-success me-2 fs-5"></i>
                                                    <span class="-50">Suporte especializado</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-4 pt-3">
                                            <small class="-50">
                                                <i class="bi bi-shield-check me-1"></i>
                                                Seus dados estão seguros conosco
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6 d-flex align-items-center justify-content-center">
                                    <div class="px-4 py-5 w-100">
                                        <div class="text-center mb-4">
                                            <h1 class="h4 text-gray-900 mb-3 fw-bold">Criar uma Conta</h1>
                                            <p class="small">Preencha os dados abaixo para começar</p>
                                        </div>

                                        @if ( $errors->any() )
                                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                                <strong>Ops!</strong> Verifique os erros abaixo:
                                                <ul class="mb-0 mt-2">
                                                    @foreach ( $errors->all() as $error )
                                                        <li>{{ $error }}</li>
                                                    @endforeach
                                                </ul>
                                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                            </div>
                                        @endif

                                        <form action="{{ route( 'register.store' ) }}" method="POST"
                                            class="user needs-validation" novalidate>
                                            @csrf

                                            <!-- Nome e Sobrenome -->
                                            <div class="row g-3">
                                                <div class="col-sm-6">
                                                    <label for="first_name" class="form-label fw-semibold">
                                                        Nome <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="text"
                                                        class="form-control form-control-user @error( 'first_name' ) is-invalid @enderror"
                                                        id="first_name" name="first_name" value="{{ old( 'first_name' ) }}"
                                                        placeholder="Digite seu nome" required>
                                                    @error( 'first_name' )
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="col-sm-6">
                                                    <label for="last_name" class="form-label fw-semibold">
                                                        Sobrenome <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="text"
                                                        class="form-control form-control-user @error( 'last_name' ) is-invalid @enderror"
                                                        id="last_name" name="last_name" value="{{ old( 'last_name' ) }}"
                                                        placeholder="Digite seu sobrenome" required>
                                                    @error( 'last_name' )
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>

                                            <!-- Email e Telefone -->
                                            <div class="row g-3">
                                                <div class="col-sm-6">
                                                    <label for="email" class="form-label fw-semibold">
                                                        Email <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="email"
                                                        class="form-control form-control-user @error( 'email' ) is-invalid @enderror"
                                                        id="email" name="email" value="{{ old( 'email' ) }}"
                                                        placeholder="seu@email.com" required>
                                                    @error( 'email' )
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="col-sm-6">
                                                    <label for="phone" class="form-label fw-semibold">
                                                        Telefone <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="tel"
                                                        class="form-control form-control-user @error( 'phone' ) is-invalid @enderror"
                                                        id="phone" name="phone" value="{{ old( 'phone' ) }}"
                                                        placeholder="(11) 99999-9999" required>
                                                    @error( 'phone' )
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>

                                            <!-- Senha e Confirmação -->
                                            <div class="row g-3">
                                                <div class="col-sm-6">
                                                    <label for="password" class="form-label fw-semibold">
                                                        Senha <span class="text-danger">*</span>
                                                    </label>
                                                    <div class="input-group">
                                                        <input type="password"
                                                            class="form-control form-control-user @error( 'password' ) is-invalid @enderror"
                                                            id="password" name="password" placeholder="Digite sua senha"
                                                            required>
                                                        <button class="btn btn-outline-secondary" type="button"
                                                            id="btnPasswordToggle">
                                                            <i class="bi bi-eye" id="passwordIcon"></i>
                                                        </button>
                                                    </div>
                                                    @error( 'password' )
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                    <div class="password-strength mt-2" style="display: none;">
                                                        <div class="progress" style="height: 5px;">
                                                            <div class="progress-bar" role="progressbar" style="width: 0%">
                                                            </div>
                                                        </div>
                                                        <small class="password-feedback text-muted"></small>
                                                    </div>
                                                </div>

                                                <div class="col-sm-6">
                                                    <label for="password_confirmation" class="form-label fw-semibold">
                                                        Confirmar Senha <span class="text-danger">*</span>
                                                    </label>
                                                    <div class="input-group">
                                                        <input type="password"
                                                            class="form-control form-control-user @error( 'password_confirmation' ) is-invalid @enderror"
                                                            id="password_confirmation" name="password_confirmation"
                                                            placeholder="Confirme sua senha" required>
                                                        <button class="btn btn-outline-secondary" type="button"
                                                            id="btnPasswordConfirmToggle">
                                                            <i class="bi bi-eye" id="passwordConfirmIcon"></i>
                                                        </button>
                                                    </div>
                                                    @error( 'password_confirmation' )
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>

                                            <!-- Termos de Serviço -->
                                            <div class="mb-4">
                                                <div class="form-check d-flex align-items-start">
                                                    <input
                                                        class="form-check-input @error( 'terms_accepted' ) is-invalid @enderror mt-1"
                                                        type="checkbox" id="terms_accepted" name="terms_accepted" value="1"
                                                        {{ old( 'terms_accepted' ) ? 'checked' : '' }} required>
                                                    <label class="form-check-label ms-2" for="terms_accepted">
                                                        Eu li e aceito os
                                                        <a href="/terms-of-service" target="_blank"
                                                            class="text-decoration-none fw-semibold">Termos de Serviço</a>
                                                        e a
                                                        <a href="/privacy-policy" target="_blank"
                                                            class="text-decoration-none fw-semibold">Política de
                                                            Privacidade</a>.
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    @error( 'terms_accepted' )
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>

                                            <!-- Botão de Registro -->
                                            <div class="d-grid mb-3">
                                                <button type="submit" class="btn btn-primary btn-user">
                                                    <i class="bi bi-person-plus me-2"></i>
                                                    Criar Conta
                                                </button>
                                            </div>
                                        </form>

                                        <hr class="my-4">

                                        <div class="text-center">
                                            <small class="text-muted">
                                                Já tem uma conta?
                                                <a href="{{ route( 'login' ) }}"
                                                    class="text-decoration-none fw-semibold text-primary">
                                                    Fazer login
                                                </a>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push( 'styles' )
    <style>
        .form-control-user {
            border-radius: 10px;
            padding: 1rem 1rem;
            font-size: 1rem;
            border: 2px solid #e3e6f0;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .form-label {
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            color: #495057;
        }

        .btn-user {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }


        .password-strength .progress {
            border-radius: 10px;
        }

        .input-group .btn-outline-secondary {
            border-color: #e3e6f0;
        }

        /* Media Queries para Responsividade Mobile */

        /* Touch device optimizations */
        @media (hover: none) and (pointer: coarse) {

            /* Larger touch targets for touch devices */
            .btn,
            .form-control,
            .form-check-input {
                min-height: 44px !important;
            }

            /* Remove hover effects on touch devices */
            .btn-user:hover {
                transform: none !important;
            }

            .card:hover {
                transform: none !important;
            }

            .plan-option:hover {
                transform: none !important;
            }

            /* Better focus visibility for touch */

            /* Touch-friendly checkboxes */
            .form-check-input {
                min-width: 44px !important;
                min-height: 44px !important;
            }

            /* Touch-friendly links */
            .text-decoration-none {
                padding: 8px !important;
                margin: -8px !important;
            }
        }

        /* High DPI displays */
        @media (-webkit-min-device-pixel-ratio: 2),
        (min-resolution: 192dpi) {

            /* Sharper borders and shadows for retina displays */

            .form-control-user {
                border-width: 1px;
            }

            .plan-option {
                border-width: 1px;
            }
        }

        /* Dispositivos móveis pequenos (576px e abaixo) */
        @media (max-width: 575.98px) {

            /* Container adjustments */
            .container {
                padding-left: 15px;
                padding-right: 15px;
            }

            /* Main container responsive */
            .col-xl-10.col-lg-12.col-md-9 {
                padding-left: 0;
                padding-right: 0;
            }

            /* Card responsive */
            .card {
                margin: 10px 0;
                border-radius: 15px;
            }

            /* Single column layout for mobile */
            .row>.col-lg-6 {
                flex: 0 0 100%;
                max-width: 100%;
            }

            /* Hide left panel on mobile */
            .bg-register-image {
                display: none !important;
            }

            /* Form container mobile */
            .col-lg-6.d-flex {
                padding: 30px 20px !important;
            }

            /* Typography mobile */
            .display-3 {
                font-size: 2.5rem !important;
            }

            h1.h4 {
                font-size: 1.3rem !important;
                margin-bottom: 1rem !important;
            }

            .lead {
                font-size: 1rem !important;
            }

            /* Form elements mobile */
            .form-control-user {
                padding: 0.75rem 1rem !important;
                font-size: 16px !important;
                /* Prevents zoom on iOS */
                border-radius: 8px !important;
            }

            .btn-user {
                padding: 0.75rem 1rem !important;
                font-size: 1rem !important;
                min-height: 44px !important;
                /* Touch target */
                border-radius: 8px !important;
            }

            /* Input group mobile */
            .input-group .btn-outline-secondary {
                min-height: 44px !important;
                border-radius: 0 8px 8px 0 !important;
            }

            /* Plan options mobile */
            .plan-option {
                margin-bottom: 0.75rem !important;
            }

            .plan-price .price {
                font-size: 1.25rem !important;
            }

            /* Form spacing mobile */
            .mb-3 {
                margin-bottom: 1rem !important;
            }

            .mb-4 {
                margin-bottom: 1.25rem !important;
            }

            /* Textos menores mas legíveis */
            .text-gray-600 {
                font-size: 0.9rem;
            }

            /* Links menores */
            .text-decoration-none {
                font-size: 0.9rem;
            }

            /* List items mobile */
            .list-unstyled li {
                padding: 0.25rem 0 !important;
                font-size: 0.9rem !important;
            }

            /* Icon sizing mobile */
            .bi-check-circle-fill {
                font-size: 0.875rem !important;
            }
        }

        /* Touch targets mínimos (aplicado a todas as telas) */
        @media (hover: none) and (pointer: coarse) {
            .btn {
                min-height: 44px;
                min-width: 44px;
            }

            .form-check-input {
                min-width: 44px;
                min-height: 44px;
            }

            .text-decoration-none {
                padding: 8px;
                margin: -8px;
            }
        }
    </style>
@endpush

@push( 'scripts' )
    <script>
        document.addEventListener( 'DOMContentLoaded', function () {
            // Toggle de visibilidade da senha
            document.getElementById( 'btnPasswordToggle' ).addEventListener( 'click', function () {
                const passwordInput = document.getElementById( 'password' );
                const passwordIcon = document.getElementById( 'passwordIcon' );

                if ( passwordInput.type === 'password' ) {
                    passwordInput.type = 'text';
                    passwordIcon.classList.remove( 'bi-eye' );
                    passwordIcon.classList.add( 'bi-eye-slash' );
                } else {
                    passwordInput.type = 'password';
                    passwordIcon.classList.remove( 'bi-eye-slash' );
                    passwordIcon.classList.add( 'bi-eye' );
                }
            } );

            // Toggle de visibilidade da confirmação de senha
            document.getElementById( 'btnPasswordConfirmToggle' ).addEventListener( 'click', function () {
                const passwordConfirmInput = document.getElementById( 'password_confirmation' );
                const passwordConfirmIcon = document.getElementById( 'passwordConfirmIcon' );

                if ( passwordConfirmInput.type === 'password' ) {
                    passwordConfirmInput.type = 'text';
                    passwordConfirmIcon.classList.remove( 'bi-eye' );
                    passwordConfirmIcon.classList.add( 'bi-eye-slash' );
                } else {
                    passwordConfirmInput.type = 'password';
                    passwordConfirmIcon.classList.remove( 'bi-eye-slash' );
                    passwordConfirmIcon.classList.add( 'bi-eye' );
                }
            } );

            // Validação de senha em tempo real
            const passwordInput = document.getElementById( 'password' );
            const passwordStrength = document.querySelector( '.password-strength' );
            const progressBar = document.querySelector( '.password-strength .progress-bar' );
            const feedback = document.querySelector( '.password-feedback' );

            passwordInput.addEventListener( 'input', function () {
                const password = this.value;

                if ( password.length === 0 ) {
                    passwordStrength.style.display = 'none';
                    return;
                }

                passwordStrength.style.display = 'block';

                let strength = 0;
                let feedbackText = '';

                // Verificar critérios de senha
                if ( password.length >= 8 ) strength += 25;
                else feedbackText += 'Mínimo 8 caracteres. ';

                if ( /[a-z]/.test( password ) ) strength += 25;
                else feedbackText += 'Letras minúsculas. ';

                if ( /[A-Z]/.test( password ) ) strength += 25;
                else feedbackText += 'Letras maiúsculas. ';

                if ( /[0-9]/.test( password ) ) strength += 25;
                else feedbackText += 'Números. ';

                if ( feedbackText === '' ) {
                    feedbackText = 'Senha forte!';
                }

                // Atualizar barra de progresso
                progressBar.style.width = strength + '%';

                if ( strength < 50 ) {
                    progressBar.className = 'progress-bar bg-danger';
                } else if ( strength < 75 ) {
                    progressBar.className = 'progress-bar bg-warning';
                } else {
                    progressBar.className = 'progress-bar bg-success';
                }

                feedback.textContent = feedbackText;
            } );

            // Máscara para telefone
            const phoneInput = document.getElementById( 'phone' );
            phoneInput.addEventListener( 'input', function ( e ) {
                let value = e.target.value.replace( /\D/g, '' );

                if ( value.length <= 11 ) {
                    if ( value.length <= 10 ) {
                        value = value.replace( /(\d{2})(\d{4})(\d{4})/, '($1) $2-$3' );
                    } else {
                        value = value.replace( /(\d{2})(\d{5})(\d{4})/, '($1) $2-$3' );
                    }
                }

                e.target.value = value;
            } );

            // Validação de formulário
            const form = document.querySelector( '.needs-validation' );
            form.addEventListener( 'submit', function ( e ) {
                if ( !form.checkValidity() ) {
                    e.preventDefault();
                    e.stopPropagation();
                }

                form.classList.add( 'was-validated' );
            } );
        } );
    </script>
@endpush
