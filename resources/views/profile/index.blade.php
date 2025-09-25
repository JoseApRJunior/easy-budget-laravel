@extends( 'layouts.app' )

@section( 'content' )
<div class="container-fluid py-4">
  {{-- Header --}}
  <div class="row mb-4">
    <div class="col-12">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h4 class="mb-1">Perfil do Usuário</h4>
          <p class="text-muted mb-0">Gerencie suas informações pessoais e configurações</p>
        </div>
        <a href="{{ route( 'dashboard' ) }}" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left me-1"></i>Voltar ao Dashboard
        </a>
      </div>
    </div>
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

  <div class="row g-4">
    {{-- Informações Pessoais --}}
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Informações Pessoais</h5>
        </div>
        <div class="card-body">
          <form action="{{ route( 'profile.update' ) }}" method="post">
            @csrf
            @method( 'PUT' )

            <div class="row g-3">
              <div class="col-md-6">
                <label for="first_name" class="form-label">Nome <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error( 'first_name' ) is-invalid @enderror" id="first_name"
                  name="first_name" value="{{ old( 'first_name', auth()->user()->first_name ?? '' ) }}" required>
                @error( 'first_name' )
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6">
                <label for="last_name" class="form-label">Sobrenome <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error( 'last_name' ) is-invalid @enderror" id="last_name"
                  name="last_name" value="{{ old( 'last_name', auth()->user()->last_name ?? '' ) }}" required>
                @error( 'last_name' )
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-12">
                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control @error( 'email' ) is-invalid @enderror" id="email" name="email"
                  value="{{ old( 'email', auth()->user()->email ?? '' ) }}" required>
                @error( 'email' )
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-12">
                <label for="email_business" class="form-label">Email Comercial</label>
                <input type="email" class="form-control @error( 'email_business' ) is-invalid @enderror"
                  id="email_business" name="email_business"
                  value="{{ old( 'email_business', auth()->user()->email_business ?? '' ) }}">
                @error( 'email_business' )
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6">
                <label for="phone" class="form-label">Telefone</label>
                <input type="tel" class="form-control @error( 'phone' ) is-invalid @enderror" id="phone" name="phone"
                  value="{{ old( 'phone', auth()->user()->phone ?? '' ) }}">
                @error( 'phone' )
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6">
                <label for="birth_date" class="form-label">Data de Nascimento</label>
                <input type="date" class="form-control @error( 'birth_date' ) is-invalid @enderror" id="birth_date"
                  name="birth_date" value="{{ old( 'birth_date', auth()->user()->birth_date ?? '' ) }}">
                @error( 'birth_date' )
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="d-flex justify-content-end mt-4">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle me-1"></i>Atualizar Perfil
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    {{-- Informações da Conta --}}
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Informações da Conta</h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label class="small text-muted">ID do Usuário</label>
            <p class="mb-0 fw-semibold">{{ auth()->user()->id ?? 'N/A' }}</p>
          </div>

          <div class="mb-3">
            <label class="small text-muted">Perfil</label>
            <p class="mb-0">
              <span class="badge bg-primary">{{ auth()->user()->role->name ?? 'Usuário' }}</span>
            </p>
          </div>

          <div class="mb-3">
            <label class="small text-muted">Status</label>
            <p class="mb-0">
              @if( auth()->user()->is_active ?? true )
              <span class="badge bg-success">Ativo</span>
              @else
              <span class="badge bg-danger">Inativo</span>
              @endif
            </p>
          </div>

          <div class="mb-3">
            <label class="small text-muted">Último Login</label>
            <p class="mb-0">
              {{ auth()->user()->last_login_at ? auth()->user()->last_login_at->format( 'd/m/Y H:i' ) : 'Nunca' }}
            </p>
          </div>

          <div class="mb-3">
            <label class="small text-muted">Data de Criação</label>
            <p class="mb-0">
              {{ auth()->user()->created_at ? auth()->user()->created_at->format( 'd/m/Y H:i' ) : 'N/A' }}
            </p>
          </div>
        </div>
      </div>

      {{-- Ações Rápidas --}}
      <div class="card mt-4">
        <div class="card-header">
          <h5 class="mb-0">Ações Rápidas</h5>
        </div>
        <div class="card-body">
          <div class="d-grid gap-2">
            <a href="{{ route( 'password.change' ) }}" class="btn btn-outline-primary">
              <i class="bi bi-key me-1"></i>Alterar Senha
            </a>

            <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal"
              data-bs-target="#blockAccountModal">
              <i class="bi bi-exclamation-triangle me-1"></i>Bloquear Conta
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Modal de Bloqueio de Conta --}}
<div class="modal fade" id="blockAccountModal" tabindex="-1" aria-labelledby="blockAccountModalLabel"
  aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="blockAccountModalLabel">Bloquear Conta</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Tem certeza de que deseja bloquear sua conta? Esta ação não pode ser desfeita sem a intervenção de um
          administrador.</p>
        <div class="alert alert-warning">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>
          <strong>Atenção:</strong> Você perderá acesso a todas as funcionalidades do sistema.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <form action="{{ route( 'account.block' ) }}" method="post" class="d-inline">
          @csrf
          <button type="submit" class="btn btn-danger">
            <i class="bi bi-x-circle me-1"></i>Bloquear Conta
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@section( 'scripts' )
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Máscara para telefone
  const phoneInput = document.getElementById('phone');
  if (phoneInput) {
    phoneInput.addEventListener('input', function(e) {
      let value = e.target.value.replace(/\D/g, '');
      if (value.length <= 11) {
        if (value.length <= 2) {
          e.target.value = value;
        } else if (value.length <= 7) {
          e.target.value = `(${value.slice( 0, 2 )}) ${value.slice( 2 )}`;
        } else {
          e.target.value = `(${value.slice( 0, 2 )}) ${value.slice( 2, 7 )}-${value.slice( 7 )}`;
        }
      }
    });
  }
});
</script>
@endsection