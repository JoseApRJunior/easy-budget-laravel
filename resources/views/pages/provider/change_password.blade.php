@extends( 'layouts.app' )

@section( 'content' )
    <div class="container-fluid  py-1 ">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-key-fill me-2 text-primary"></i>Alterar Senha
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.dashboard' ) }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.settings.index' ) }}">Configurações</a></li>
                    <li class="breadcrumb-item active">Alterar Senha</li>
                </ol>
            </nav>
        </div>
        <div class="row justify-content-center w-100">
            <div class="col-12 col-sm-11 col-md-9 col-lg-7 col-xl-5">
                <div class="card border-0 shadow-lg rounded-3">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <div class="mb-3">
                                <i class="bi bi-shield-lock display-4 text-primary"></i>
                            </div>
                            <h2 class="fw-bold text-primary mb-2">
                                @if( $isGoogleUser ?? false )
                                    Definir Senha
                                @else
                                    Alterar Senha
                                @endif
                            </h2>
                            <p class="text-muted small mb-0">
                                @if( $isGoogleUser ?? false )
                                    Você se cadastrou usando Google. Defina uma senha para sua conta para maior segurança.
                                @else
                                    Digite sua senha atual e a nova senha para alterar. Certifique-se de que a nova senha seja
                                    forte e segura.
                                @endif
                            </p>
                        </div>

                        <!-- Alertas de sucesso/erro -->
                        @if ( session( 'success' ) )
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle me-2"></i>{{ session( 'success' ) }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if ( session( 'error' ) )
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i>{{ session( 'error' ) }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <form action="{{ route( 'provider.change_password_store' ) }}" method="post"
                            id="changePasswordForm">
                            @csrf

                            @if( !( $isGoogleUser ?? false ) )
                                <div class="mb-3">
                                    <label for="current_password" class="form-label fw-semibold">
                                        Senha atual <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="password"
                                            class="form-control form-control-user @error( 'current_password' ) is-invalid @enderror"
                                            id="current_password" name="current_password" required
                                            aria-describedby="current_password_help" />
                                        <span class="input-group-text" style="cursor: pointer;"
                                            onclick="togglePasswordVisibility('current_password', 'currentPasswordIcon')">
                                            <i class="bi bi-eye" id="currentPasswordIcon"></i>
                                        </span>
                                    </div>
                                    @error( 'current_password' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div id="current_password_help" class="form-text">
                                        Digite a senha que você usa atualmente para fazer login.
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-info mb-3">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Usuário Google OAuth:</strong> Você se cadastrou usando sua conta Google.
                                    Esta será a primeira senha da sua conta.
                                </div>
                            @endif

                            <div class="mb-3">
                                <label for="password" class="form-label fw-semibold">
                                    Nova Senha <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="password"
                                        class="form-control form-control-user @error( 'password' ) is-invalid @enderror"
                                        id="password" name="password" required
                                        aria-describedby="password_help password-strength" />
                                    <span class="input-group-text" style="cursor: pointer;"
                                        onclick="togglePasswordVisibility('password', 'passwordIcon')">
                                        <i class="bi bi-eye" id="passwordIcon"></i>
                                    </span>
                                </div>
                                @error( 'password' )
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div id="password_help" class="form-text">
                                    Sua nova senha deve ter pelo menos 8 caracteres, incluindo maiúsculas, minúsculas,
                                    números e símbolos.
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

                            <div class="mb-3">
                                <label for="password_confirmation" class="form-label fw-semibold">
                                    Confirmar Nova Senha <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="password"
                                        class="form-control form-control-user @error( 'password_confirmation' ) is-invalid @enderror"
                                        id="password_confirmation" name="password_confirmation"
                                        placeholder="Confirme sua senha" required>
                                    <span class="input-group-text" style="cursor: pointer;"
                                        onclick="togglePasswordVisibility('password_confirmation', 'passwordConfirmIcon')">
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

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-circle me-2"></i>
                                    @if( $isGoogleUser ?? false )
                                        Definir Senha
                                    @else
                                        Alterar Senha
                                    @endif
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
</div> @endsection @push( 'scripts' )
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
            const passwordInput = document.getElementById( 'password' );
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

            // Validação de formulário aprimorada
            const form = document.getElementById( 'changePasswordForm' );
            const isGoogleUser = {{ $isGoogleUser ?? false ? 'true' : 'false' }};

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

                // Só verificar força da senha se não for usuário Google OAuth
                if ( !isGoogleUser ) {
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
                }
            } );
        } );
    </script>
@endpush
