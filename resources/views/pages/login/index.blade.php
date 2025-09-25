@extends('layouts.app')

@section('content')
<div class="container-fluid min-vh-75 d-flex align-items-center justify-content-center p-3 p-md-5">
  <div class="row justify-content-center w-100">
    <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-4">
      <div class="card shadow-lg border-0 fade-in">
        <div class="card-body p-4">
          {{-- Logo e Título --}}
          <div class="text-center mb-4">
            <div class="logo-container mb-3">
              <img src="{{ asset('images/logo.png') }}" alt="Easy Budget Logo" class="logo-img" height="40">
              <span class="logo-text">Easy Budget</span>
            </div>
            <h1 class="h3 fw-bold">Seja bem-vindo</h1>
            <p class="text-muted small">Faça login para continuar</p>
          </div>

          {{-- Mensagens de Erro --}}
          @if ($errors->any())
            <div class="alert alert-danger">
              <ul class="mb-0">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          @if (session('status'))
            <div class="alert alert-success">
              {{ session('status') }}
            </div>
          @endif

          @if (session('error'))
            <div class="alert alert-danger">
              {{ session('error') }}
            </div>
          @endif

          {{-- Formulário de Login --}}
          <form action="{{ route('login') }}" method="post" class="needs-validation" novalidate>
            @csrf
            {{-- Campo de Email --}}
            <div class="form-floating mb-3">
              <input type="email" class="form-control @error('email') is-invalid @enderror" 
                     id="email" name="email" placeholder="nome@exemplo.com" 
                     value="{{ old('email') }}" required autocomplete="email" autofocus>
              <label for="email">Email</label>
              @error('email')
                <div class="invalid-feedback">
                  {{ $message }}
                </div>
              @enderror
            </div>

            {{-- Campo de Senha --}}
            <div class="form-floating password-container mb-4">
              <input type="password" class="form-control @error('password') is-invalid @enderror" 
                     id="password" name="password" placeholder=" " required autocomplete="current-password">
              <label for="password">Senha</label>
              <button type="button" class="password-toggle" data-input="password" aria-label="Mostrar/Ocultar senha">
                <i class="bi bi-exclamation-circle error-icon d-none"></i>
                <i class="bi bi-eye"></i>
              </button>
              @error('password')
                <div class="invalid-feedback">
                  {{ $message }}
                </div>
              @enderror
            </div>

            {{-- Lembrar de mim --}}
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
              <label class="form-check-label" for="remember">
                Lembrar de mim
              </label>
            </div>

            {{-- Botão de Login --}}
            <button type="submit" class="btn btn-primary w-100 mb-3">
              <i class="bi bi-box-arrow-in-right me-2"></i>
              Entrar
            </button>
          </form>

          {{-- Links de Ajuda --}}
          <div class="mt-4 pt-3 border-top">
            <div class="d-flex flex-column align-items-center gap-3">
              @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-decoration-none">
                  <i class="bi bi-key me-1"></i>
                  Esqueceu sua senha?
                </a>
              @endif
              @if (Route::has('register'))
                <div class="d-flex align-items-center gap-2">
                  <span class="text-muted">Não tem uma conta?</span>
                  <a href="{{ route('register') }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-person-plus me-1"></i>
                    Registre-se
                  </a>
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>

      {{-- Rodapé do Card --}}
      <div class="text-center mt-3">
        <small class="text-muted">
          <i class="bi bi-shield-lock me-1"></i>
          Login seguro via SSL
        </small>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/login.js') }}" type="module"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    // Validação do formulário
    const form = document.querySelector('form');
    form.addEventListener('submit', function (event) {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add('was-validated');
    });

    // Funcionalidade de mostrar/ocultar senha
    const passwordToggle = document.querySelector('.password-toggle');
    if (passwordToggle) {
      passwordToggle.addEventListener('click', function() {
        const input = document.getElementById(this.dataset.input);
        const icon = this.querySelector('.bi-eye, .bi-eye-slash');
        
        if (input.type === 'password') {
          input.type = 'text';
          icon.classList.remove('bi-eye');
          icon.classList.add('bi-eye-slash');
        } else {
          input.type = 'password';
          icon.classList.remove('bi-eye-slash');
          icon.classList.add('bi-eye');
        }
      });
    }
  });
</script>
@endpush

@section('title', 'Login')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Login') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="row mb-3">
                            <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Password') }}</label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 offset-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>

                                    <label class="form-check-label" for="remember">
                                        {{ __('Remember Me') }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-8 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Login') }}
                                </button>

                                @if (Route::has('password.request'))
                                    <a class="btn btn-link" href="{{ route('password.request') }}">
                                        {{ __('Forgot Your Password?') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection