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
                    <li class="breadcrumb-item"><a href="{{ route( 'settings.index' ) }}">Configurações</a></li>
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
                                <div class="form-floating password-container mb-3">
                                    <input type="password"
                                        class="form-control @error( 'current_password' ) is-invalid @enderror"
                                        id="current_password" name="current_password" required
                                        aria-describedby="current_password_help" />
                                    <label for="current_password" class="form-label">
                                        Senha atual
                                    </label>
                                    <button type="button" class="password-toggle" data-input="current_password"
                                        aria-label="Mostrar/ocultar senha atual">
                                        <i class="bi bi-eye"></i>
                                    </button>
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

                            <div class="form-floating password-container mb-3">
                                <input type="password" class="form-control @error( 'password' ) is-invalid @enderror"
                                    id="password" name="password" required
                                    aria-describedby="password_help password-strength" />
                                <label for="password" class="form-label">
                                    Nova Senha
                                </label>
                                <button type="button" class="password-toggle" data-input="password"
                                    aria-label="Mostrar/ocultar nova senha">
                                    <i class="bi bi-eye"></i>
                                </button>
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

                            <div class="form-floating password-container">
                                <input type="password" class="form-control" id="password_confirmation"
                                    name="password_confirmation" required aria-describedby="password_confirmation_help" />
                                <label for="password_confirmation" class="form-label">
                                    Confirmar Nova Senha
                                </label>
                                <button type="button" class="password-toggle" data-input="password_confirmation"
                                    aria-label="Mostrar/ocultar confirmação de senha">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <div id="password_confirmation_help" class="form-text">
                                    Digite novamente a nova senha para confirmar que está correta.
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
    </div>
@endsection

@push( 'scripts' )
    <script>
        document.addEventListener( 'DOMContentLoaded', function () {
            const passwordToggles = document.querySelectorAll( '.password-toggle' );

            // Toggle visibility de senha
            passwordToggles.forEach( function ( toggle ) {
                toggle.addEventListener( 'click', function () {
                    const inputId = this.dataset.input;
                    const input = document.getElementById( inputId );
                    const icon = this.querySelector( 'i' );

                    if ( input.type === 'password' ) {
                        input.type = 'text';
                        icon.classList.remove( 'bi-eye' );
                        icon.classList.add( 'bi-eye-slash' );
                    } else {
                        input.type = 'password';
                        icon.classList.remove( 'bi-eye-slash' );
                        icon.classList.add( 'bi-eye' );
                    }
                } );
            } );

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

                if ( percentage < 40 ) {
                    strength = 'Muito Fraca';
                    className = 'bg-danger';
                    textClass = 'danger';
                } else if ( percentage < 60 ) {
                    strength = 'Fraca';
                    className = 'bg-warning';
                    textClass = 'warning';
                } else if ( percentage < 80 ) {
                    strength = 'Boa';
                    className = 'bg-info';
                    textClass = 'info';
                } else {
                    strength = 'Muito Forte';
                    className = 'bg-success';
                    textClass = 'success';
                }

                return {
                    percentage: percentage,
                    class: className,
                    text: strength,
                    textClass: textClass
                };
            }

            // Validação de confirmação de senha
            const passwordConfirmationInput = document.getElementById( 'password_confirmation' );
            const passwordConfirmationContainer = passwordConfirmationInput.closest( '.form-floating' );

            passwordConfirmationInput.addEventListener( 'input', function () {
                const password = document.getElementById( 'password' ).value;
                const confirmation = this.value;

                // Remove classes de validação anteriores
                passwordConfirmationContainer.classList.remove( 'was-validated' );

                if ( confirmation.length === 0 ) {
                    return;
                }

                if ( password === confirmation ) {
                    this.classList.remove( 'is-invalid' );
                    this.classList.add( 'is-valid' );
                    passwordConfirmationContainer.classList.add( 'was-validated' );
                } else {
                    this.classList.remove( 'is-valid' );
                    this.classList.add( 'is-invalid' );
                    passwordConfirmationContainer.classList.add( 'was-validated' );
                }
            } );

            // Validação também quando a senha principal muda
            passwordInput.addEventListener( 'input', function () {
                const password = this.value;
                const confirmation = passwordConfirmationInput.value;

                if ( confirmation.length > 0 ) {
                    if ( password === confirmation ) {
                        passwordConfirmationInput.classList.remove( 'is-invalid' );
                        passwordConfirmationInput.classList.add( 'is-valid' );
                    } else {
                        passwordConfirmationInput.classList.remove( 'is-valid' );
                        passwordConfirmationInput.classList.add( 'is-invalid' );
                    }
                }
            } );

            // Validação no submit do formulário
            const form = document.getElementById( 'changePasswordForm' );
            const isGoogleUser = {{ $isGoogleUser ?? false ? 'true' : 'false' }};

            form.addEventListener( 'submit', function ( e ) {
                const password = passwordInput.value;
                const confirmation = passwordConfirmationInput.value;

                // Verificar se as senhas coincidem
                if ( password !== confirmation ) {
                    e.preventDefault();
                    passwordConfirmationInput.classList.add( 'is-invalid' );
                    passwordConfirmationInput.focus();

                    // Adicionar mensagem de erro se não existir
                    let feedback = passwordConfirmationInput.parentNode.querySelector( '.invalid-feedback' );
                    if ( !feedback ) {
                        feedback = document.createElement( 'div' );
                        feedback.className = 'invalid-feedback';
                        passwordConfirmationInput.parentNode.appendChild( feedback );
                    }
                    feedback.textContent = 'As senhas não coincidem.';

                    return false;
                }

                // Só verificar força da senha se não for usuário Google OAuth
                if ( !isGoogleUser ) {
                    const strength = calculatePasswordStrength( password );
                    if ( strength.percentage < 60 ) {
                        e.preventDefault();
                        passwordInput.classList.add( 'is-invalid' );
                        passwordInput.focus();

                        let feedback = passwordInput.parentNode.querySelector( '.invalid-feedback' );
                        if ( !feedback ) {
                            feedback = document.createElement( 'div' );
                            feedback.className = 'invalid-feedback';
                            passwordInput.parentNode.appendChild( feedback );
                        }
                        feedback.textContent = 'A senha deve ser pelo menos "Boa" para ser aceita.';

                        return false;
                    }
                }
            } );
        } );
    </script>
@endpush
