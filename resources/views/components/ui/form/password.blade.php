@props([
    'name' => 'password',
    'label' => 'Senha',
    'id' => null,
    'placeholder' => 'Digite sua senha',
    'required' => false,
    'autocomplete' => 'current-password',
    'showForgot' => false,
    'showStrength' => false,
    'confirmId' => null,
])

@php
    $id = $id ?? $name;
    $iconId = $id . '-icon';
@endphp

<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-1">
        @if($label)
            <label for="{{ $id }}" class="form-label fw-bold small text-muted text-uppercase mb-0">
                {{ $label }}
                @if($required) <span class="text-danger">*</span> @endif
            </label>
        @endif
        
        @if($showForgot && Route::has('password.request'))
            <a href="{{ route('password.request') }}" class="small text-decoration-none text-primary fw-bold">
                Esqueceu a senha?
            </a>
        @endif
    </div>
    
    <div class="input-group">
        <span class="input-group-text bg-light border-end-0">
            <i class="bi bi-key text-muted"></i>
        </span>
        
        <input 
            id="{{ $id }}" 
            name="{{ $name }}" 
            type="password"
            autocomplete="{{ $autocomplete }}" 
            @if($required) required @endif
            {{ $attributes->merge(['class' => 'form-control ' . ($errors->has($name) ? 'is-invalid' : ''), 'style' => 'background-color: var(--form-input-bg);']) }}
            placeholder="{{ $placeholder }}"
            @if($showStrength) data-password-strength="true" @endif
            @if($confirmId) data-confirm-with="{{ $confirmId }}" @endif
        >
            
        <span class="input-group-text bg-white border-start-0 cursor-pointer" onclick="togglePasswordVisibility('{{ $id }}', '{{ $iconId }}')">
            <i class="bi bi-eye text-muted" id="{{ $iconId }}"></i>
        </span>
    </div>
    
    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror

    @if($showStrength)
        <div class="password-strength-container mt-2" style="display: none;" id="{{ $id }}-strength">
            <div class="progress" style="height: 4px;">
                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-1">
                <small class="password-feedback text-muted" style="font-size: 0.7rem;"></small>
                <small class="strength-text fw-bold" style="font-size: 0.7rem;"></small>
            </div>
        </div>
    @endif

    @if($confirmId)
        <div class="password-match-feedback mt-1" style="display: none;" id="{{ $id }}-match">
            <small class="text-success small"><i class="bi bi-check-circle me-1"></i>Senhas coincidem</small>
        </div>
        <div class="password-mismatch-feedback mt-1" style="display: none;" id="{{ $id }}-mismatch">
            <small class="text-danger small"><i class="bi bi-exclamation-triangle me-1"></i>Não coincidem</small>
        </div>
    @endif
</div>

@once
    @push('scripts')
        <script>
            window.togglePasswordVisibility = function(inputId, iconId) {
                const passwordInput = document.getElementById(inputId);
                const passwordIcon = document.getElementById(iconId);

                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    passwordIcon.className = 'bi bi-eye-slash text-muted';
                } else {
                    passwordInput.type = 'password';
                    passwordIcon.className = 'bi bi-eye text-muted';
                }
            };

            document.addEventListener('DOMContentLoaded', function() {
                // Força da senha
                const strengthInputs = document.querySelectorAll('[data-password-strength="true"]');
                const criteria = {
                    length: { regex: /.{8,}/, weight: 20 },
                    lowercase: { regex: /[a-z]/, weight: 20 },
                    uppercase: { regex: /[A-Z]/, weight: 20 },
                    number: { regex: /[0-9]/, weight: 20 },
                    special: { regex: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/, weight: 20 }
                };

                strengthInputs.forEach(input => {
                    const container = document.getElementById(input.id + '-strength');
                    const progressBar = container.querySelector('.progress-bar');
                    const feedback = container.querySelector('.password-feedback');
                    const strengthText = container.querySelector('.strength-text');

                    input.addEventListener('input', function() {
                        const val = this.value;
                        if(!val) {
                            container.style.display = 'none';
                            return;
                        }
                        container.style.display = 'block';

                        let score = 0;
                        Object.values(criteria).forEach(c => {
                            if(c.regex.test(val)) score += c.weight;
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

                        feedback.textContent = val.length < 8 ? 'Mínimo 8 caracteres' : '';
                    });
                });

                // Confirmação de senha
                const confirmInputs = document.querySelectorAll('[data-confirm-with]');
                confirmInputs.forEach(confirmInput => {
                    const targetInput = document.getElementById(confirmInput.getAttribute('data-confirm-with'));
                    const matchFeedback = document.getElementById(confirmInput.id + '-match');
                    const mismatchFeedback = document.getElementById(confirmInput.id + '-mismatch');

                    function checkMatch() {
                        if(!confirmInput.value) {
                            matchFeedback.style.display = 'none';
                            mismatchFeedback.style.display = 'none';
                            return;
                        }
                        if(targetInput.value === confirmInput.value) {
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

                    confirmInput.addEventListener('input', checkMatch);
                    targetInput.addEventListener('input', checkMatch);
                });
            });
        </script>
    @endpush
@endonce
