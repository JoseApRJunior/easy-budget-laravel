@extends('layouts.app')

@section('content')
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-8 col-lg-6 col-xl-5">
      <div class="card shadow-lg border-0 rounded-3">
        <div class="card-header bg-primary text-white text-center py-3">
          <h4 class="mb-0">Acesso ao Easy Budget</h4>
        </div>
        <div class="card-body p-4 p-sm-5">
          @if (session('status'))
          <div class="alert alert-success" role="alert">
            {{ session('status') }}
          </div>
          @endif

          @if (session('error'))
          <div class="alert alert-danger" role="alert">
            {{ session('error') }}
          </div>
          @endif

          <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input id="email" class="form-control @error('email') is-invalid @enderror" type="email" name="email"
                value="{{ old('email') }}" required autofocus>
              @error('email')
              <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
              </span>
              @enderror
            </div>

            <div class="mb-3">
              <label for="password" class="form-label">Senha</label>
              <div class="password-container position-relative">
                <input id="password" class="form-control @error('password') is-invalid @enderror" type="password"
                  name="password" required>
                <button type="button"
                  class="btn btn-sm btn-outline-secondary position-absolute top-50 end-0 translate-middle-y ms-2 password-toggle"
                  data-input="password">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
              @error('password')
              <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
              </span>
              @enderror
            </div>

            <div class="mb-3 form-check">
              <input class="form-check-input" type="checkbox" name="remember" id="remember"
                {{ old('remember') ? 'checked' : '' }}>
              <label class="form-check-label" for="remember">
                Lembrar-me
              </label>
            </div>

            <div class="d-grid">
              <button type="submit" class="btn btn-primary btn-lg">Entrar</button>
            </div>

            <div class="text-center">
              <a class="small" href="{{ route('password.request') }}">Esqueceu sua senha?</a>
            </div>
          </form>
        </div>
      </div>

      <div class="text-center mt-4">
        <p class="text-muted">Não tem uma conta? <a href="{{ route('register') }}">Cadastre-se</a></p>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Toggle password visibility
  const passwordToggle = document.querySelector('.password-toggle');
  const passwordInput = document.getElementById('password');

  if (passwordToggle && passwordInput) {
    passwordToggle.addEventListener('click', function() {
      const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordInput.setAttribute('type', type);

      const icon = this.querySelector('i');
      icon.className = type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
    });
  }
});
</script>
@endsection