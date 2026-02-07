@props([
    'email' => 'provider1@test.com',
    'password' => 'Password1@',
])

@if (app()->environment('local'))
    <div class="mt-4 pt-4 border-top bg-light p-3 rounded-3 small">
        <div class="d-flex align-items-center mb-2">
            <i class="bi bi-info-circle-fill text-warning me-2"></i>
            <strong class="text-dark">Ambiente de Teste</strong>
        </div>
        <div class="d-flex justify-content-between text-muted">
            <span>Email: <strong>{{ $email }}</strong></span>
            <span>Senha: <strong>{{ $password }}</strong></span>
        </div>
    </div>
@endif
