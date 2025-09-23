@extends( 'layouts.app' )

@section( 'content' )
<div class="container-fluid min-vh-75 d-flex align-items-center justify-content-center p-3 p-md-5">
  <div class="row justify-content-center w-100">
    <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-4">
      <div class="card shadow-lg border-0 fade-in">
        <div class="card-body p-4">
          {{-- Logo e Título --}}
          <div class="text-center mb-4">
            <div class="logo-container mb-3">
              <img src="{{ asset( 'images/logo.png' ) }}" alt="Easy Budget Logo" class="logo-img" height="40">
              <span class="logo-text">Easy Budget</span>
            </div>
            <h1 class="h3 fw-bold">Seja bem-vindo</h1>
            <p class="text-muted small">Faça login para continuar</p>
          </div>

          {{-- Mensagens de erro/sucesso --}}
          @if( session( 'success' ) )
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session( 'success' ) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          @endif

          @if( session( 'error' ) )
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session( 'error' ) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          @endif

          {{-- Alerta de Reenvio de Confirmação --}}
          @if( session( 'resendConfirmation' ) )
          <div class="alert alert-warning d-flex align-items-center fade-in" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <div class="flex-grow-1">
              Email não confirmado. Reenviar link de confirmação?
            </div>
            <form action="{{ route( 'resend-confirmation' ) }}" method="post">
              @csrf
              <button type="submit" class="btn btn-warning btn-sm">
                <i class="bi bi-envelope-fill me-1"></i>
                Reenviar
              </button>
            </form>
          </div>
          @endif

          {{-- Formulário de Login --}}
          <form action="{{ route( 'login' ) }}" method="post" class="needs-validation" novalidate>
            @csrf
            {{-- Campo de Email --}}
            <div class="form-floating mb-3">
              <input type="email" class="form-control @error( 'email' ) is-invalid @enderror" id="email" name="email"
                placeholder="nome@exemplo.com" required autocomplete="email" autofocus value="{{ old( 'email' ) }}">
              <label for="email">Email</label>
              @error( 'email' )
              <div class="invalid-feedback">
                {{ $message }}
              </div>
              @enderror
            </div>

            {{-- Campo de Senha --}}
            <div class="form-floating password-container mb-4">
              <input type="password" class="form-control @error( 'password' ) is-invalid @enderror" id="password"
                name="password" placeholder=" " required autocomplete="current-password">
              <label for="password">Senha</label>
              <button type="button" class="password-toggle" data-input="password" aria-label="Mostrar/Ocultar senha">
                <i class="bi bi-exclamation-circle error-icon d-none"></i>
                <i class="bi bi-eye"></i>
              </button>
              @error( 'password' )
              <div class="invalid-feedback">
                {{ $message }}
              </div>
              @enderror
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
              <a href="{{ route( 'password.request' ) }}" class="text-decoration-none">
                <i class="bi bi-key me-1"></i>
                Esqueceu sua senha?
              </a>
              <div class="d-flex align-items-center gap-2">
                <span class="text-muted">Não tem uma conta?</span>
                <a href="{{ route( 'register' ) }}" class="btn btn-outline-primary btn-sm">
                  <i class="bi bi-person-plus me-1"></i>
                  Registre-se
                </a>
              </div>
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

@section( 'scripts' )
@vite( [ 'resources/js/login.js' ] )
<script>
document.addEventListener('DOMContentLoaded', function() {
  clearLocalStorageOnLogout();

  // Validação do formulário
  const form = document.querySelector('form');
  form.addEventListener('submit', function(event) {
    if (!form.checkValidity()) {
      event.preventDefault();
      event.stopPropagation();
    }
    form.classList.add('was-validated');
  });
});
</script>
@endsection