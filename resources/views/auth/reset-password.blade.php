@extends( 'layouts.app' )

@section( 'content' )
    <div class="container py-2">
        <!-- Formulário -->
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-key-fill me-2"></i>Redefinir Senha
                        </h4>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted mb-4">
                            Digite sua nova senha abaixo. Certifique-se de escolher uma senha forte e segura.
                        </p>

                        <form method="POST" action="{{ route( 'password.store' ) }}" id="resetPasswordForm">
                            @csrf

                            <!-- Password Reset Token -->
                            <input type="hidden" name="token"
                                value="{{ $request->route( 'token' ) ?? $request->query( 'token' ) }}">

                            <!-- Email Address -->
                            <div class="mb-4">
                                <label for="email" class="form-label fw-semibold">
                                    Endereço de E-mail <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-envelope"></i>
                                    </span>
                                    <input id="email" name="email" type="email"
                                        value="{{ old( 'email', $request->email ) }}"
                                        class="form-control @error( 'email' ) is-invalid @enderror"
                                        placeholder="seu@email.com" required autofocus autocomplete="username">
                                    @error( 'email' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold">
                                    Nova Senha <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-key"></i>
                                    </span>
                                    <input id="password-input" name="password" type="password"
                                        class="form-control @error( 'password' ) is-invalid @enderror"
                                        placeholder="Digite sua nova senha" required autocomplete="new-password">
                                    <span class="input-group-text" style="cursor: pointer;"
                                        onclick="togglePasswordVisibility('password-input', 'passwordIcon')">
                                        <i class="bi bi-eye" id="passwordIcon"></i>
                                    </span>
                                    @error( 'password' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-text">
                                    <small class="text-muted">
                                        Sua nova senha deve ter pelo menos 8 caracteres, incluindo maiúsculas, minúsculas,
                                        números e símbolos.
                                    </small>
                                </div>
                                <!-- Indicador de força da senha -->
                                <div class="password-strength mt-2" id="password-strength" style="display: none;">
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar" id="strength-bar" role="progressbar" style="width: 0%"
                                            aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small class="text-muted" id="strength-text">Senha muito fraca</small>
                                </div>
                            </div>

                            <!-- Confirm Password -->
                            <div class="mb-4">
                                <label for="password_confirmation" class="form-label fw-semibold">
                                    Confirmar Nova Senha <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-key-fill"></i>
                                    </span>
                                    <input id="password_confirmation" name="password_confirmation" type="password"
                                        class="form-control @error( 'password_confirmation' ) is-invalid @enderror"
                                        placeholder="Confirme sua nova senha" required autocomplete="new-password">
                                    <span class="input-group-text" style="cursor: pointer;"
                                        onclick="togglePasswordVisibility('password_confirmation', 'passwordConfirmIcon')">
                                        <i class="bi bi-eye" id="passwordConfirmIcon"></i>
                                    </span>
                                    @error( 'password_confirmation' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

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

                            <!-- Submit Button -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-circle me-2"></i>Redefinir Senha
                                </button>
                            </div>

                            <!-- Links -->
                            <div class="text-center mt-4">
                                <a href="{{ route( 'login' ) }}" class="text-decoration-none">
                                    <i class="bi bi-arrow-left me-1"></i>Voltar ao Login
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener( 'DOMContentLoaded', function () {
            // Função unificada para toggle de visibilidade de senha
            window.togglePasswordVisibility = function ( inputId, iconId ) {
                const input = document.getElementById( inputId );
                const icon = document.getElementById( iconId );

                if ( input.type === 'password' ) {
                    input.type = 'text';
                    icon.classList.remove( 'bi-eye' );
                    icon.classList.add( 'bi-eye-slash' );
                } else {
                    input.type = 'password';
                    icon.classList.remove( 'bi-eye-slash' );
                    icon.classList.add( 'bi-eye' );
                }
            };

            // Critérios de validação
            const criteria = {
                length: { regex: /.{8,}/, weight: 20 },
                lowercase: { regex: /[a-z]/, weight: 20 },
                uppercase: { regex: /[A-Z]/, weight: 20 },
                number: { regex: /[0-9]/, weight: 20 },
                special: { regex: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/, weight: 20 }
            };

            // Validação de força de senha
            const passwordInput = document.getElementById( 'password-input' );
            const strengthBar = document.getElementById( 'strength-bar' );
            const strengthText = document.getElementById( 'strength-text' );
            const strengthContainer = document.getElementById( 'password-strength' );

            passwordInput.addEventListener( 'input', function () {
                const password = this.value;
                if ( password.length === 0 ) {
                    strengthContainer.style.display = 'none';
                    return;
                }

                strengthContainer.style.display = 'block';
                const strength = calculatePasswordStrength( password );

                strengthBar.style.width = strength.percentage + '%';
                strengthBar.className = 'progress-bar ' + strength.class;
                strengthText.textContent = strength.text;
                strengthText.className = 'text-' + strength.textClass;
            } );

            function calculatePasswordStrength( password ) {
                let score = 0;

                // Critérios de força
                if ( password.length >= 8 ) score += 1;
                if ( password.length >= 12 ) score += 1;
                if ( /[a-z]/.test( password ) ) score += 1;
                if ( /[A-Z]/.test( password ) ) score += 1;
                if ( /[0-9]/.test( password ) ) score += 1;
                if ( /[^A-Za-z0-9]/.test( password ) ) score += 1;

                let percentage = ( score / 6 ) * 100;
                let strength, className, textClass;

                if ( percentage < 40 ) { strength = 'Muito Fraca'; className = 'bg-danger'; textClass = 'danger'; } else if (
                    percentage < 60 ) { strength = 'Fraca'; className = 'bg-warning'; textClass = 'warning'; } else if ( percentage
                        < 80 ) { strength = 'Boa'; className = 'bg-info'; textClass = 'info'; } else {
                    strength = 'Muito Forte';
                    className = 'bg-success'; textClass = 'success';
                } return {
                    percentage: percentage, class: className, text:
                        strength, textClass: textClass
                };
            }

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

                if ( passwordConfirm.length === 0 ) {
                    return;
                }

                if ( password === passwordConfirm ) {
                    passwordMatchFeedback.style.display = 'block';
                    passwordMismatchFeedback.style.display = 'none';
                    passwordConfirmInput.classList.remove( 'is-invalid' );
                    passwordConfirmInput.classList.add( 'is-valid' );
                } else {
                    passwordMatchFeedback.style.display = 'none';
                    passwordMismatchFeedback.style.display = 'block';
                    passwordConfirmInput.classList.remove( 'is-valid' );
                    passwordConfirmInput.classList.add( 'is-invalid' );
                }
            }

            passwordInput.addEventListener( 'input', updatePasswordMatch );
            passwordConfirmInput.addEventListener( 'input', updatePasswordMatch );

            // Validação do formulário
            const form = document.getElementById( 'resetPasswordForm' );

            form.addEventListener( 'submit', function ( e ) {
                const password = passwordInput.value;
                const passwordConfirm = passwordConfirmInput.value;

                // Verificar se as senhas coincidem
                if ( password !== passwordConfirm ) {
                    e.preventDefault();
                    passwordMismatchFeedback.style.display = 'block';
                    passwordConfirmInput.classList.add( 'is-invalid' );
                    passwordConfirmInput.focus();
                    return;
                }

                // Verificar força da senha
                let allCriteriaMet = true;
                Object.keys( criteria ).forEach( key => {
                    if ( !criteria[key].regex.test( password ) ) {
                        allCriteriaMet = false;
                    }
                } );

                if ( !allCriteriaMet ) {
                    e.preventDefault();
                    alert( 'A senha deve atender todos os critérios de segurança.' );
                    return;
                }

                // Desabilitar botão para evitar submissões duplas
                const submitBtn = form.querySelector( 'button[type="submit"]' );
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Processando...';
            } );
        } );
    </script>
@endsection
