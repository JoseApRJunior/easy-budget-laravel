@extends('layouts.app')

@section('title', 'Novo Usuário - Easy Budget')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary me-3">
                <i class="bi bi-arrow-left me-1"></i>
                Voltar
            </a>
            <h1 class="h3 mb-0">
                <i class="bi bi-person-plus text-primary me-2"></i>
                Criar Novo Usuário
            </h1>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('users.store') }}">
                    @csrf

                    <div class="row">
                        <!-- Nome -->
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">
                                Nome Completo <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   id="name"
                                   name="name"
                                   value="{{ old('name') }}"
                                   required
                                   placeholder="Ex: João Silva">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">
                                Email <span class="text-danger">*</span>
                            </label>
                            <input type="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   id="email"
                                   name="email"
                                   value="{{ old('email') }}"
                                   required
                                   placeholder="joao@exemplo.com">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <!-- Senha -->
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">
                                Senha <span class="text-danger">*</span>
                            </label>
                            <input type="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   id="password"
                                   name="password"
                                   required
                                   placeholder="Mínimo 8 caracteres">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Confirmação de Senha -->
                        <div class="col-md-6 mb-3">
                            <label for="password_confirmation" class="form-label">
                                Confirmar Senha <span class="text-danger">*</span>
                            </label>
                            <input type="password"
                                   class="form-control"
                                   id="password_confirmation"
                                   name="password_confirmation"
                                   required
                                   placeholder="Repita a senha">
                        </div>
                    </div>

                    <div class="row">
                        <!-- Plano -->
                        <div class="col-md-6 mb-3">
                            <label for="plan_id" class="form-label">
                                Plano
                            </label>
                            <select class="form-select @error('plan_id') is-invalid @enderror"
                                    id="plan_id"
                                    name="plan_id">
                                <option value="">Selecionar plano...</option>
                                @foreach(\App\Models\Plan::where('status', 'active')->get() as $planOption)
                                    <option value="{{ $planOption->id }}"
                                            {{ old('plan_id') == $planOption->id ? 'selected' : '' }}>
                                        {{ $planOption->name }} - R$ {{ number_format($planOption->price, 2, ',', '.') }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Deixe em branco para definir posteriormente</div>
                            @error('plan_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Tenant ID -->
                        <div class="col-md-6 mb-3">
                            <label for="tenant_id" class="form-label">
                                Tenant ID
                            </label>
                            <input type="text"
                                   class="form-control @error('tenant_id') is-invalid @enderror"
                                   id="tenant_id"
                                   name="tenant_id"
                                   value="{{ old('tenant_id') }}"
                                   placeholder="ID do tenant">
                            <div class="form-text">Identificador único do tenant</div>
                            @error('tenant_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input @error('status') is-invalid @enderror"
                                   type="checkbox"
                                   role="switch"
                                   id="status"
                                   name="status"
                                   value="active"
                                   {{ old('status', 'active') === 'active' ? 'checked' : '' }}>
                            <label class="form-check-label" for="status">
                                <strong>Usuário Ativo</strong>
                            </label>
                        </div>
                        <div class="form-text">Usuários ativos podem fazer login no sistema</div>
                        @error('status')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Observações -->
                    <div class="mb-4">
                        <label for="notes" class="form-label">
                            Observações
                        </label>
                        <textarea class="form-control @error('notes') is-invalid @enderror"
                                  id="notes"
                                  name="notes"
                                  rows="3"
                                  placeholder="Observações sobre o usuário...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Botões -->
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-2"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-person-plus me-2"></i>
                            Criar Usuário
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Mostrar/Ocultar senha
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle para mostrar senha
        const passwordField = document.getElementById('password');
        const togglePassword = document.createElement('button');
        togglePassword.type = 'button';
        togglePassword.className = 'btn btn-outline-secondary position-absolute';
        togglePassword.style.right = '10px';
        togglePassword.style.top = '50%';
        togglePassword.style.transform = 'translateY(-50%)';
        togglePassword.innerHTML = '<i class="bi bi-eye"></i>';

        passwordField.parentElement.classList.add('position-relative');
        passwordField.parentElement.appendChild(togglePassword);

        togglePassword.addEventListener('click', function() {
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                this.innerHTML = '<i class="bi bi-eye-slash"></i>';
            } else {
                passwordField.type = 'password';
                this.innerHTML = '<i class="bi bi-eye"></i>';
            }
        });

        // Validação de senha em tempo real
        passwordField.addEventListener('input', function() {
            const password = this.value;
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /\d/.test(password)
            };

            // Feedback visual (opcional)
            console.log('Validação de senha:', requirements);
        });
    });
</script>
@endsection
