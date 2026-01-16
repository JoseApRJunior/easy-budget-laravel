@extends('layouts.app')

@section('title', 'Criar Conta')

@section('content')
    <div class="container-fluid p-0">
        <div class="row g-0 min-vh-100">
            <!-- Welcome Section (Left Side) -->
            <div class="col-lg-6 d-none d-lg-flex bg-primary bg-gradient align-items-center justify-content-center text-white p-5">
                <div class="text-center" style="max-width: 500px;">
                    <div class="mb-4">
                        <i class="bi bi-graph-up display-3 text-white-50"></i>
                    </div>
                    <h1 class="h2 fw-bold mb-3">Bem-vindo ao Easy Budget!</h1>
                    <p class="lead text-white-50 mb-4">
                        Crie sua conta e comece a transformar a gestão do seu negócio hoje mesmo.
                    </p>

                    <div class="row text-start g-3 justify-content-center">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-check-circle-fill text-info me-2"></i>
                                <span>Gestão financeira completa</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-check-circle-fill text-info me-2"></i>
                                <span>Controle de orçamentos</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-check-circle-fill text-info me-2"></i>
                                <span>Relatórios detalhados</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-check-circle-fill text-info me-2"></i>
                                <span>Suporte especializado</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 text-white-50 small">
                        <i class="bi bi-shield-check me-1"></i>
                        Seus dados estão seguros conosco
                    </div>
                </div>
            </div>

            <!-- Register Form Section (Right Side) -->
            <div class="col-lg-6 d-flex align-items-center justify-content-center bg-light">
                <div class="w-100 p-4 p-md-5" style="max-width: 600px;">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-primary">Criar uma Conta</h2>
                        <p class="text-muted">Preencha os dados abaixo para começar</p>
                    </div>

                    <!-- Social Login -->
                    <div class="mb-4">
                        <x-ui.button href="{{ route('auth.google') }}" variant="outline-danger" size="lg" icon="google" label="Continuar com Google" class="w-100" />
                        <div class="text-center mt-2">
                            <small class="text-muted" style="font-size: 0.75rem;">
                                Ao continuar com Google, você concorda com nossos
                                <a href="/terms-of-service" target="_blank" class="text-decoration-none">Termos de Serviço</a>
                                e
                                <a href="/privacy-policy" target="_blank" class="text-decoration-none">Política de Privacidade</a>.
                            </small>
                        </div>
                    </div>

                    <div class="position-relative text-center mb-4">
                        <hr class="position-absolute w-100 top-50 translate-middle-y my-0 text-muted opacity-25">
                        <span class="position-relative bg-light px-3 text-muted small">ou preencha o formulário</span>
                    </div>

                    @if ($errors->any())
                        <x-ui.alert variant="danger" title="Ops! Verifique os erros abaixo:">
                            <ul class="mb-0 mt-2 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </x-ui.alert>
                    @endif

                    <form action="{{ route('register.store') }}" method="POST" class="needs-validation" novalidate>
                        @csrf

                        <!-- Nome e Sobrenome -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <x-ui.form.input name="first_name" label="Nome" placeholder="Seu nome" required :value="old('first_name')" />
                            </div>
                            <div class="col-md-6">
                                <x-ui.form.input name="last_name" label="Sobrenome" placeholder="Seu sobrenome" required :value="old('last_name')" />
                            </div>
                        </div>

                        <!-- Email e Telefone -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <x-ui.form.input type="email" name="email" label="Email" placeholder="seu@email.com" required :value="old('email')" />
                            </div>
                            <div class="col-md-6">
                                <x-ui.form.input type="tel" name="phone" id="phone" label="Telefone" placeholder="(11) 99999-9999" required :value="old('phone')" />
                            </div>
                        </div>

                        <!-- Senha -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label fw-bold small text-muted text-uppercase">
                                    Senha <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                                        id="password" name="password" placeholder="Sua senha" required>
                                    <span class="input-group-text cursor-pointer" onclick="togglePasswordVisibility('password', 'passwordIcon')">
                                        <i class="bi bi-eye text-muted" id="passwordIcon"></i>
                                    </span>
                                </div>
                                
                                <!-- Indicador de Força da Senha -->
                                <div class="password-strength mt-2" style="display: none;">
                                    <div class="progress" style="height: 4px;">
                                        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <small class="password-feedback text-muted" style="font-size: 0.7rem;"></small>
                                        <small class="strength-text fw-bold" style="font-size: 0.7rem;"></small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label fw-bold small text-muted text-uppercase">
                                    Confirmar Senha <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror"
                                        id="password_confirmation" name="password_confirmation" placeholder="Confirme a senha" required>
                                    <span class="input-group-text cursor-pointer" onclick="togglePasswordVisibility('password_confirmation', 'passwordConfirmIcon')">
                                        <i class="bi bi-eye text-muted" id="passwordConfirmIcon"></i>
                                    </span>
                                </div>
                                <div class="password-match-feedback mt-1" style="display: none;">
                                    <small class="text-success small"><i class="bi bi-check-circle me-1"></i>Senhas coincidem</small>
                                </div>
                                <div class="password-mismatch-feedback mt-1" style="display: none;">
                                    <small class="text-danger small"><i class="bi bi-exclamation-triangle me-1"></i>Não coincidem</small>
                                </div>
                            </div>
                        </div>

                        <!-- Termos -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input @error('terms_accepted') is-invalid @enderror" type="checkbox" id="terms_accepted" name="terms_accepted" value="1" required {{ old('terms_accepted') ? 'checked' : '' }}>
                                <label class="form-check-label small text-muted" for="terms_accepted">
                                    Eu li e aceito os <a href="/terms-of-service" target="_blank" class="text-decoration-none">Termos de Serviço</a> e a <a href="/privacy-policy" target="_blank" class="text-decoration-none">Política de Privacidade</a>.
                                </label>
                            </div>
                        </div>

                        <div class="d-grid mb-4">
                            <x-ui.button type="submit" variant="primary" size="lg" icon="person-plus" label="Criar Conta" />
                        </div>

                        <div class="text-center">
                            <p class="small text-muted mb-0">
                                Já tem uma conta? 
                                <a href="{{ route('login') }}" class="fw-bold text-primary text-decoration-none">Fazer login</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle Password Visibility
            window.togglePasswordVisibility = function(inputId, iconId) {
                const input = document.getElementById(inputId);
                const icon = document.getElementById(iconId);
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('bi-eye', 'bi-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.replace('bi-eye-slash', 'bi-eye');
                }
            };

            // Password Strength Logic
            const passwordInput = document.getElementById('password');
            const strengthContainer = document.querySelector('.password-strength');
            const progressBar = document.querySelector('.password-strength .progress-bar');
            const feedback = document.querySelector('.password-feedback');
            const strengthText = document.querySelector('.strength-text');

            const criteria = {
                length: { regex: /.{8,}/, weight: 20 },
                lowercase: { regex: /[a-z]/, weight: 20 },
                uppercase: { regex: /[A-Z]/, weight: 20 },
                number: { regex: /[0-9]/, weight: 20 },
                special: { regex: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/, weight: 20 }
            };

            passwordInput.addEventListener('input', function() {
                const val = this.value;
                if(!val) {
                    strengthContainer.style.display = 'none';
                    return;
                }
                strengthContainer.style.display = 'block';

                let score = 0;
                let validCount = 0;
                Object.values(criteria).forEach(c => {
                    if(c.regex.test(val)) {
                        score += c.weight;
                        validCount++;
                    }
                });

                progressBar.style.width = score + '%';
                
                if(score < 40) {
                    progressBar.className = 'progress-bar bg-danger';
                    strengthText.textContent = 'Fraca';
                    strengthText.className = 'strength-text fw-bold text-danger';
                } else if(score < 80) {
                    progressBar.className = 'progress-bar bg-warning';
                    strengthText.textContent = 'Média';
                    strengthText.className = 'strength-text fw-bold text-warning';
                } else {
                    progressBar.className = 'progress-bar bg-success';
                    strengthText.textContent = 'Forte';
                    strengthText.className = 'strength-text fw-bold text-success';
                }

                if(val.length < 8) {
                    feedback.textContent = 'Mínimo 8 caracteres';
                } else {
                    feedback.textContent = '';
                }
            });

            // Password Match Logic
            const confirmInput = document.getElementById('password_confirmation');
            const matchFeedback = document.querySelector('.password-match-feedback');
            const mismatchFeedback = document.querySelector('.password-mismatch-feedback');

            function checkMatch() {
                if(!confirmInput.value) {
                    matchFeedback.style.display = 'none';
                    mismatchFeedback.style.display = 'none';
                    return;
                }
                if(passwordInput.value === confirmInput.value) {
                    matchFeedback.style.display = 'block';
                    mismatchFeedback.style.display = 'none';
                    confirmInput.classList.remove('is-invalid');
                    confirmInput.classList.add('is-valid');
                } else {
                    matchFeedback.style.display = 'none';
                    mismatchFeedback.style.display = 'block';
                    confirmInput.classList.remove('is-valid');
                    confirmInput.classList.add('is-invalid');
                }
            }

            passwordInput.addEventListener('input', checkMatch);
            confirmInput.addEventListener('input', checkMatch);

            // Phone Mask
            const phoneInput = document.getElementById('phone');
            phoneInput.addEventListener('input', function(e) {
                let v = e.target.value.replace(/\D/g, '');
                if(v.length > 11) v = v.substring(0, 11);
                if(v.length > 10) {
                    v = v.replace(/^(\d\d)(\d{5})(\d{4}).*/, '($1) $2-$3');
                } else if(v.length > 5) {
                    v = v.replace(/^(\d\d)(\d{4})(\d{0,4}).*/, '($1) $2-$3');
                } else if(v.length > 2) {
                    v = v.replace(/^(\d\d)(\d{0,5}).*/, '($1) $2');
                }
                e.target.value = v;
            });
        });
    </script>
@endpush
