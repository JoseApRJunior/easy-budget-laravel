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
                            <div class="row">
                                <div class="col-lg-6 d-flex align-items-center bg-register-image">
                                    <div class="px-5 py-4 text-center">
                                        <h1 class="h4 text-gray-900 mb-4">Bem-vindo ao Easy Budget!</h1>
                                        <p class="text-gray-600 mb-4">
                                            Crie sua conta e comece a transformar a gestão do seu negócio hoje mesmo.
                                        </p>
                                        <div class="mb-4">
                                            <i class="bi bi-graph-up display-4 text-primary mb-3"></i>
                                        </div>
                                        <ul class="list-unstyled text-start">
                                            <li class="mb-2">
                                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                                Gestão financeira completa
                                            </li>
                                            <li class="mb-2">
                                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                                Controle de orçamentos
                                            </li>
                                            <li class="mb-2">
                                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                                Relatórios detalhados
                                            </li>
                                            <li class="mb-2">
                                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                                Suporte especializado
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="col-lg-6 d-flex ">
                                    <div class="px-5 py-4 text-center">
                                        <div class="text-center">
                                            <h1 class="h4 text-gray-900 mb-4">Criar uma Conta</h1>
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

                                        <form action="{{ route( 'register.store' ) }}" method="POST" class="user needs-validation" novalidate>
                                            @csrf

                                            <!-- Nome e Sobrenome -->
                                            <div class="row">
                                                <div class="col-sm-6 mb-3">
                                                    <label for="first_name" class="form-label">
                                                        Nome <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="text"
                                                           class="form-control form-control-user @error( 'first_name' ) is-invalid @enderror"
                                                           id="first_name"
                                                           name="first_name"
                                                           value="{{ old( 'first_name' ) }}"
                                                           placeholder="Digite seu nome"
                                                           required>
                                                    @error( 'first_name' )
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="col-sm-6 mb-3">
                                                    <label for="last_name" class="form-label">
                                                        Sobrenome <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="text"
                                                           class="form-control form-control-user @error( 'last_name' ) is-invalid @enderror"
                                                           id="last_name"
                                                           name="last_name"
                                                           value="{{ old( 'last_name' ) }}"
                                                           placeholder="Digite seu sobrenome"
                                                           required>
                                                    @error( 'last_name' )
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>

                                            <!-- Email e Telefone -->
                                            <div class="row">
                                                <div class="col-sm-6 mb-3">
                                                    <label for="email" class="form-label">
                                                        Email <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="email"
                                                           class="form-control form-control-user @error( 'email' ) is-invalid @enderror"
                                                           id="email"
                                                           name="email"
                                                           value="{{ old( 'email' ) }}"
                                                           placeholder="seu@email.com"
                                                           required>
                                                    @error( 'email' )
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="col-sm-6 mb-3">
                                                    <label for="phone" class="form-label">
                                                        Telefone <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="tel"
                                                           class="form-control form-control-user @error( 'phone' ) is-invalid @enderror"
                                                           id="phone"
                                                           name="phone"
                                                           value="{{ old( 'phone' ) }}"
                                                           placeholder="(11) 99999-9999"
                                                           required>
                                                    @error( 'phone' )
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>

                                            <!-- Senha e Confirmação -->
                                            <div class="row">
                                                <div class="col-sm-6 mb-3">
                                                    <label for="password" class="form-label">
                                                        Senha <span class="text-danger">*</span>
                                                    </label>
                                                    <div class="input-group">
                                                        <input type="password"
                                                               class="form-control form-control-user @error( 'password' ) is-invalid @enderror"
                                                               id="password"
                                                               name="password"
                                                               placeholder="Digite sua senha"
                                                               required>
                                                        <button class="btn btn-outline-secondary" type="button" id="btnPasswordToggle">
                                                            <i class="bi bi-eye" id="passwordIcon"></i>
                                                        </button>
                                                    </div>
                                                    @error( 'password' )
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                    <div class="password-strength mt-2" style="display: none;">
                                                        <div class="progress" style="height: 5px;">
                                                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                                        </div>
                                                        <small class="password-feedback text-muted"></small>
                                                    </div>
                                                </div>

                                                <div class="col-sm-6 mb-3">
                                                    <label for="password_confirmation" class="form-label">
                                                        Confirmar Senha <span class="text-danger">*</span>
                                                    </label>
                                                    <div class="input-group">
                                                        <input type="password"
                                                               class="form-control form-control-user @error( 'password_confirmation' ) is-invalid @enderror"
                                                               id="password_confirmation"
                                                               name="password_confirmation"
                                                               placeholder="Confirme sua senha"
                                                               required>
                                                        <button class="btn btn-outline-secondary" type="button" id="btnPasswordConfirmToggle">
                                                            <i class="bi bi-eye" id="passwordConfirmIcon"></i>
                                                        </button>
                                                    </div>
                                                    @error( 'password_confirmation' )
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>

                                            <!-- Seleção de Plano -->
                                            <div class="mb-4">
                                                <label class="form-label">
                                                    Escolha seu Plano <span class="text-danger">*</span>
                                                </label>
                                                <div class="row">
                                                    @foreach( $plans as $plan )
                                                        <div class="col-md-4 mb-3">
                                                            <div class="form-check border rounded p-3 h-100 plan-option
                                                                {{ $plan[ 'slug' ] === 'free' ? 'border-primary bg-light' : 'border-secondary' }}
                                                                {{ old( 'plan' ) === $plan[ 'slug' ] ? 'selected' : '' }}"
                                                                 data-plan="{{ $plan[ 'slug' ] }}">
                                                                <input class="form-check-input plan-radio"
                                                                       type="radio"
                                                                       name="plan"
                                                                       id="plan_{{ $plan[ 'slug' ] }}"
                                                                       value="{{ $plan[ 'slug' ] }}"
                                                                       {{ old( 'plan', 'free' ) === $plan[ 'slug' ] ? 'checked' : '' }}
                                                                       {{ $plan[ 'slug' ] !== 'free' ? 'disabled' : '' }}>
                                                                <label class="form-check-label w-100" for="plan_{{ $plan[ 'slug' ] }}">
                                                                    <div class="text-center">
                                                                        <h6 class="mb-2">{{ $plan[ 'name' ] }}</h6>
                                                                        <div class="plan-price">
                                                                            <span class="currency">R$</span>
                                                                            <span class="price">{{ number_format( $plan[ 'price' ], 0, '', '.' ) }}</span>
                                                                            <span class="period">/mês</span>
                                                                        </div>
                                                                        @if( $plan[ 'slug' ] !== 'free' )
                                                                            <small class="text-muted">Em desenvolvimento</small>
                                                                        @endif
                                                                    </div>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                @error( 'plan' )
                                                    <div class="text-danger small">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- Termos de Serviço -->
                                            <div class="mb-4">
                                                <div class="form-check">
                                                    <input class="form-check-input @error( 'terms_accepted' ) is-invalid @enderror"
                                                           type="checkbox"
                                                           id="terms_accepted"
                                                           name="terms_accepted"
                                                           value="1"
                                                           {{ old( 'terms_accepted' ) ? 'checked' : '' }}
                                                           required>
                                                    <label class="form-check-label" for="terms_accepted">
                                                        Eu li e aceito os
                                                        <a href="/terms-of-service" target="_blank" class="text-decoration-none">Termos de Serviço</a>
                                                        e a
                                                        <a href="/privacy-policy" target="_blank" class="text-decoration-none">Política de Privacidade</a>.
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    @error( 'terms_accepted' )
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>

                                            <!-- Botão de Registro -->
                                            <button type="submit" class="btn btn-primary btn-user btn-block">
                                                <i class="bi bi-person-plus me-2"></i>
                                                Criar Conta
                                            </button>
                                        </form>

                                        <hr class="my-4">

                                        <div class="text-center">
                                            <small class="text-muted">
                                                Já tem uma conta?
                                                <a href="{{ route( 'login' ) }}" class="text-decoration-none">Fazer login</a>
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
    .bg-register-image {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        background-size: cover;
        background-position: center;
    }

    .bg-gradient-primary {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    }

    .form-control-user {
        border-radius: 10px;
        padding: 1rem 1rem;
        font-size: 1rem;
        border: 2px solid #e3e6f0;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .form-control-user:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    .btn-user {
        border-radius: 10px;
        padding: 0.75rem 1rem;
        font-size: 1rem;
        font-weight: 600;
    }

    .plan-option {
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .plan-option:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .plan-option.selected {
        border-color: #667eea !important;
        background-color: #f8f9ff !important;
    }

    .plan-price {
        margin-bottom: 0.5rem;
    }

    .plan-price .price {
        font-size: 1.5rem;
        font-weight: bold;
        color: #667eea;
    }

    .plan-price .currency,
    .plan-price .period {
        color: #6c757d;
        font-size: 0.875rem;
    }

    .password-strength .progress {
        border-radius: 10px;
    }

    .input-group .btn-outline-secondary {
        border-color: #e3e6f0;
    }

    .input-group .btn-outline-secondary:hover {
        background-color: #f8f9fa;
        border-color: #667eea;
    }
</style>
@endpush

@push( 'scripts' )
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle de visibilidade da senha
    document.getElementById('btnPasswordToggle').addEventListener('click', function() {
        const passwordInput = document.getElementById('password');
        const passwordIcon = document.getElementById('passwordIcon');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            passwordIcon.classList.remove('bi-eye');
            passwordIcon.classList.add('bi-eye-slash');
        } else {
            passwordInput.type = 'password';
            passwordIcon.classList.remove('bi-eye-slash');
            passwordIcon.classList.add('bi-eye');
        }
    });

    // Toggle de visibilidade da confirmação de senha
    document.getElementById('btnPasswordConfirmToggle').addEventListener('click', function() {
        const passwordConfirmInput = document.getElementById('password_confirmation');
        const passwordConfirmIcon = document.getElementById('passwordConfirmIcon');

        if (passwordConfirmInput.type === 'password') {
            passwordConfirmInput.type = 'text';
            passwordConfirmIcon.classList.remove('bi-eye');
            passwordConfirmIcon.classList.add('bi-eye-slash');
        } else {
            passwordConfirmInput.type = 'password';
            passwordConfirmIcon.classList.remove('bi-eye-slash');
            passwordConfirmIcon.classList.add('bi-eye');
        }
    });

    // Validação de senha em tempo real
    const passwordInput = document.getElementById('password');
    const passwordStrength = document.querySelector('.password-strength');
    const progressBar = document.querySelector('.password-strength .progress-bar');
    const feedback = document.querySelector('.password-feedback');

    passwordInput.addEventListener('input', function() {
        const password = this.value;

        if (password.length === 0) {
            passwordStrength.style.display = 'none';
            return;
        }

        passwordStrength.style.display = 'block';

        let strength = 0;
        let feedbackText = '';

        // Verificar critérios de senha
        if (password.length >= 8) strength += 25;
        else feedbackText += 'Mínimo 8 caracteres. ';

        if (/[a-z]/.test(password)) strength += 25;
        else feedbackText += 'Letras minúsculas. ';

        if (/[A-Z]/.test(password)) strength += 25;
        else feedbackText += 'Letras maiúsculas. ';

        if (/[0-9]/.test(password)) strength += 25;
        else feedbackText += 'Números. ';

        if (feedbackText === '') {
            feedbackText = 'Senha forte!';
        }

        // Atualizar barra de progresso
        progressBar.style.width = strength + '%';

        if (strength < 50) {
            progressBar.className = 'progress-bar bg-danger';
        } else if (strength < 75) {
            progressBar.className = 'progress-bar bg-warning';
        } else {
            progressBar.className = 'progress-bar bg-success';
        }

        feedback.textContent = feedbackText;
    });

    // Máscara para telefone
    const phoneInput = document.getElementById('phone');
    phoneInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');

        if (value.length <= 11) {
            if (value.length <= 10) {
                value = value.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
            } else {
                value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            }
        }

        e.target.value = value;
    });

    // Validação visual dos planos
    const planOptions = document.querySelectorAll('.plan-option');
    const planRadios = document.querySelectorAll('.plan-radio');

    planOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remover seleção de todos
            planOptions.forEach(opt => opt.classList.remove('selected'));

            // Adicionar seleção ao clicado
            this.classList.add('selected');

            // Marcar o radio correspondente
            const planSlug = this.dataset.plan;
            const radio = document.getElementById(`plan_${planSlug}`);
            if (radio && !radio.disabled) {
                radio.checked = true;
            }
        });
    });

    // Validação de formulário
    const form = document.querySelector('.needs-validation');
    form.addEventListener('submit', function(e) {
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }

        form.classList.add('was-validated');
    });
});
</script>
@endpush
