@extends( 'layouts.app' )

@section( 'content' )
    <!-- Tela de Registro Aprimorada -->
    <div class="container-fluid ">
            <div class="row justify-content-center">
                <div class="col-xl-10 col-lg-12 col-md-9">
                    <div class="card o-hidden border-0 shadow-lg">
                        <div class="card-body p-0">
                            <!-- Layout vertical: welcome em cima, formulário embaixo -->
                            <div class="row min-vh-100 flex-column flex-lg-row">
                                <div class="col-lg-6 d-flex align-items-center justify-content-center welcome-section">
                                    <div class="px-3 py-1 text-center w-100">
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

                                <div
                                    class="col-lg-6 d-flex align-items-center justify-content-center register-form-section">
                                    <div class="px-3 py-1 w-100">
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
                                                        <span class="input-group-text" style="cursor: pointer;" onclick="togglePasswordVisibility('password', 'passwordIcon')">
                                                            <i class="bi bi-eye" id="passwordIcon"></i>
                                                        </span>
                                                    </div>
                                                    @error( 'password' )
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror

                                                    <!-- Dicas da Senha -->
                                                    <div class="password-hint mt-2">
                                                        <small class="text-muted">
                                                            <i class="bi bi-info-circle me-1"></i>
                                                            Use 8+ caracteres com letras maiúsculas, minúsculas, números e símbolos (!@#$%)
                                                        </small>
                                                    </div>

                                                    <!-- Indicador de Força da Senha -->
                                                    <div class="password-strength mt-2" style="display: none;">
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <small class="fw-semibold">Força da senha:</small>
                                                            <small class="strength-text text-muted"></small>
                                                        </div>
                                                        <div class="progress" style="height: 8px;">
                                                            <div class="progress-bar" role="progressbar" style="width: 0%">
                                                            </div>
                                                        </div>
                                                        <small class="password-feedback text-muted mt-1"></small>
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
                                                        <span class="input-group-text" style="cursor: pointer;" onclick="togglePasswordVisibility('password_confirmation', 'passwordConfirmIcon')">
                                                            <i class="bi bi-eye" id="passwordConfirmIcon"></i>
                                                        </span>
                                                    </div>
                                                    @error( 'password_confirmation' )
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror

                                                    {{-- Indicador de comparação de senha --}}
                                                    <div class="password-match-feedback mt-2" style="display: none;">
                                                        <small class="text-muted">
                                                            <i class="bi bi-check-circle text-success me-1"></i>
                                                            Senhas coincidem
                                                        </small>
                                                    </div>
                                                    <div class="password-mismatch-feedback mt-2" style="display: none;">
                                                        <small class="text-danger">
                                                            <i class="bi bi-exclamation-triangle me-1"></i>
                                                            As senhas não coincidem
                                                        </small>
                                                    </div>
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
@endsection

@push( 'styles' )

@endpush

@push( 'scripts' )
    <script>
        document.addEventListener( 'DOMContentLoaded', function () {
            // Função unificada para toggle de visibilidade de senha
            window.togglePasswordVisibility = function(inputId, iconId) {
                const input = document.getElementById(inputId);
                const icon = document.getElementById(iconId);

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            };

            // Validação de senha em tempo real aprimorada
            const passwordInput = document.getElementById( 'password' );
            const passwordStrength = document.querySelector( '.password-strength' );
            const progressBar = document.querySelector( '.password-strength .progress-bar' );
            const feedback = document.querySelector( '.password-feedback' );
            const strengthText = document.querySelector( '.strength-text' );
            const criteriaElements = document.querySelectorAll( '.password-criteria' );

            // Critérios de validação
            const criteria = {
                length: { regex: /.{8,}/, weight: 20 },
                lowercase: { regex: /[a-z]/, weight: 20 },
                uppercase: { regex: /[A-Z]/, weight: 20 },
                number: { regex: /[0-9]/, weight: 20 },
                special: { regex: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/, weight: 20 }
            };

            function updatePasswordStrength() {
                const password = passwordInput.value;

                if ( password.length === 0 ) {
                    passwordStrength.style.display = 'none';
                    return;
                }

                passwordStrength.style.display = 'block';

                let totalStrength = 0;
                let validCriteria = 0;
                let feedbackText = '';

                // Verificar cada critério
                Object.keys(criteria).forEach(key => {
                    const isValid = criteria[key].regex.test(password);

                    if (isValid) {
                        totalStrength += criteria[key].weight;
                        validCriteria++;
                    } else {
                        const labels = {
                            length: '8+ caracteres',
                            lowercase: 'Letras minúsculas',
                            uppercase: 'Letras maiúsculas',
                            number: 'Números',
                            special: 'Caracteres especiais (!@#$%)'
                        };
                        if (feedbackText) feedbackText += ', ';
                        feedbackText += labels[key];
                    }
                });

                // Adicionar pontuação final se necessário
                if (feedbackText && !feedbackText.endsWith('.')) {
                    feedbackText += '.';
                }

                // Se todos os critérios forem atendidos
                if (validCriteria === 5) {
                    feedbackText = 'Senha forte! Todos os critérios atendidos.';
                }

                // Determinar nível de força
                let strengthLevel = '';
                let progressBarClass = '';

                if (validCriteria < 3) {
                    strengthLevel = 'Fraca';
                    progressBarClass = 'bg-danger';
                } else if (validCriteria < 5) {
                    strengthLevel = 'Média';
                    progressBarClass = 'bg-warning';
                } else {
                    strengthLevel = 'Forte';
                    progressBarClass = 'bg-success';
                }

                // Atualizar barra de progresso
                progressBar.style.width = totalStrength + '%';
                progressBar.className = `progress-bar ${progressBarClass}`;
                strengthText.textContent = strengthLevel;
                feedback.textContent = feedbackText;
            }

            passwordInput.addEventListener( 'input', updatePasswordStrength );
            passwordInput.addEventListener( 'focus', function() {
                if (this.value.length > 0) {
                    updatePasswordStrength();
                }
            });

            // Validação de comparação de senhas
            const passwordConfirmInput = document.getElementById( 'password_confirmation' );
            const passwordMatchFeedback = document.querySelector( '.password-match-feedback' );
            const passwordMismatchFeedback = document.querySelector( '.password-mismatch-feedback' );

            function updatePasswordMatch() {
                const password = passwordInput.value;
                const passwordConfirm = passwordConfirmInput.value;

                // Esconder ambos os feedbacks inicialmente
                passwordMatchFeedback.style.display = 'none';
                passwordMismatchFeedback.style.display = 'none';

                if (passwordConfirm.length === 0) {
                    return;
                }

                if (password === passwordConfirm) {
                    passwordMatchFeedback.style.display = 'block';
                    passwordMismatchFeedback.style.display = 'none';
                    passwordConfirmInput.classList.remove('is-invalid');
                    passwordConfirmInput.classList.add('is-valid');
                } else {
                    passwordMatchFeedback.style.display = 'none';
                    passwordMismatchFeedback.style.display = 'block';
                    passwordConfirmInput.classList.remove('is-valid');
                    passwordConfirmInput.classList.add('is-invalid');
                }
            }

            passwordInput.addEventListener( 'input', updatePasswordMatch );
            passwordConfirmInput.addEventListener( 'input', updatePasswordMatch );

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

            // Validação de formulário aprimorada
            const form = document.querySelector( '.needs-validation' );
            form.addEventListener( 'submit', function ( e ) {
                const password = passwordInput.value;
                const passwordConfirm = passwordConfirmInput.value;

                // Verificar se as senhas coincidem
                if (password !== passwordConfirm) {
                    e.preventDefault();
                    passwordMismatchFeedback.style.display = 'block';
                    passwordConfirmInput.classList.add('is-invalid');
                    alert('As senhas não coincidem. Verifique e tente novamente.');
                    return;
                }

                // Verificar se a senha atende todos os critérios
                let allCriteriaMet = true;
                Object.keys(criteria).forEach(key => {
                    if (!criteria[key].regex.test(password)) {
                        allCriteriaMet = false;
                    }
                });

                if (!allCriteriaMet) {
                    e.preventDefault();
                    alert('A senha deve atender todos os critérios de segurança.');
                    return;
                }

                if ( !form.checkValidity() ) {
                    e.preventDefault();
                    e.stopPropagation();
                }

                form.classList.add( 'was-validated' );
            } );
        } );
    </script>
@endpush
