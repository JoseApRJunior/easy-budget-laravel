@extends('layouts.app')

@section('title', 'Redefinir Senha')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                <x-ui.card class="border-0 shadow-lg">
                    <div class="p-4">
                        <div class="text-center mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 rounded-circle p-3 mb-3 text-primary">
                                <i class="bi bi-key display-6"></i>
                            </div>
                            <h4 class="fw-bold text-dark mb-2">Redefinir Senha</h4>
                            <p class="text-muted small">
                                Crie uma nova senha forte e segura para sua conta.
                            </p>
                        </div>

                        <form method="POST" action="{{ route('password.store') }}">
                            @csrf
                            <input type="hidden" name="token" value="{{ $request->route('token') ?? $request->query('token') }}">

                            <div class="mb-4">
                                <x-ui.form.input type="email" name="email" label="Email" placeholder="seu@email.com" required :value="old('email', $request->email)" />
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label fw-bold small text-muted text-uppercase">
                                    Nova Senha <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                                        id="password" name="password" placeholder="Nova senha" required autocomplete="new-password">
                                    <span class="input-group-text cursor-pointer" onclick="togglePasswordVisibility('password', 'passwordIcon')">
                                        <i class="bi bi-eye text-muted" id="passwordIcon"></i>
                                    </span>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="form-text text-muted small mt-2">
                                    Mínimo de 8 caracteres, com letras maiúsculas, minúsculas, números e símbolos.
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password_confirmation" class="form-label fw-bold small text-muted text-uppercase">
                                    Confirmar Senha <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror"
                                        id="password_confirmation" name="password_confirmation" placeholder="Confirme a nova senha" required autocomplete="new-password">
                                    <span class="input-group-text cursor-pointer" onclick="togglePasswordVisibility('password_confirmation', 'passwordConfirmIcon')">
                                        <i class="bi bi-eye text-muted" id="passwordConfirmIcon"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="d-grid gap-3">
                                <x-ui.button type="submit" variant="primary" size="lg" icon="check-circle" label="Redefinir Senha" />
                            </div>
                        </form>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function togglePasswordVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }
    </script>
@endpush
