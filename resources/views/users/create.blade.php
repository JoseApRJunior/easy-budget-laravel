@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">
      <div class="card">
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Criar Novo Usuário</h5>
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
              <i class="bi bi-arrow-left me-1"></i>Voltar
            </a>
          </div>
        </div>
        <div class="card-body">
          {{-- Mensagens de erro/sucesso --}}
          @if(session('success'))
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          @endif

          @if(session('error'))
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          @endif

          {{-- Formulário de Criação de Usuário --}}
          <form action="{{ route('users.store') }}" method="post" class="needs-validation" novalidate>
            @csrf

            <div class="row g-3">
              {{-- Nome --}}
              <div class="col-md-6">
                <label for="first_name" class="form-label">Nome <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="first_name"
                  name="first_name" value="{{ old('first_name') }}" required>
                @error('first_name')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              {{-- Sobrenome --}}
              <div class="col-md-6">
                <label for="last_name" class="form-label">Sobrenome <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('last_name') is-invalid @enderror" id="last_name"
                  name="last_name" value="{{ old('last_name') }}" required>
                @error('last_name')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              {{-- Email --}}
              <div class="col-12">
                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                  value="{{ old('email') }}" required>
                @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              {{-- Email Comercial --}}
              <div class="col-12">
                <label for="email_business" class="form-label">Email Comercial</label>
                <input type="email" class="form-control @error('email_business') is-invalid @enderror"
                  id="email_business" name="email_business" value="{{ old('email_business') }}">
                @error('email_business')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              {{-- Telefone --}}
              <div class="col-md-6">
                <label for="phone" class="form-label">Telefone</label>
                <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone"
                  value="{{ old('phone') }}">
                @error('phone')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              {{-- Data de Nascimento --}}
              <div class="col-md-6">
                <label for="birth_date" class="form-label">Data de Nascimento</label>
                <input type="date" class="form-control @error('birth_date') is-invalid @enderror" id="birth_date"
                  name="birth_date" value="{{ old('birth_date') }}">
                @error('birth_date')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              {{-- Perfil --}}
              <div class="col-md-6">
                <label for="role_id" class="form-label">Perfil <span class="text-danger">*</span></label>
                <select class="form-select @error('role_id') is-invalid @enderror" id="role_id" name="role_id" required>
                  <option value="">Selecione um perfil</option>
                  {{-- Perfis serão carregados dinamicamente --}}
                  <option value="1" {{ old('role_id') == '1' ? 'selected' : '' }}>Administrador</option>
                  <option value="2" {{ old('role_id') == '2' ? 'selected' : '' }}>Usuário</option>
                </select>
                @error('role_id')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              {{-- Status --}}
              <div class="col-md-6">
                <label for="is_active" class="form-label">Status</label>
                <select class="form-select @error('is_active') is-invalid @enderror" id="is_active" name="is_active">
                  <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Ativo</option>
                  <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inativo</option>
                </select>
                @error('is_active')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              {{-- Senha --}}
              <div class="col-12">
                <label for="password" class="form-label">Senha <span class="text-danger">*</span></label>
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password"
                  name="password" required>
                @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              {{-- Confirmar Senha --}}
              <div class="col-12">
                <label for="password_confirmation" class="form-label">Confirmar Senha <span
                    class="text-danger">*</span></label>
                <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror"
                  id="password_confirmation" name="password_confirmation" required>
                @error('password_confirmation')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            {{-- Botões --}}
            <div class="d-flex justify-content-end gap-2 mt-4">
              <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-x-circle me-1"></i>Cancelar
              </a>
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle me-1"></i>Criar Usuário
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Validação do formulário
  const form = document.querySelector('form');
  form.addEventListener('submit', function(event) {
    if (!form.checkValidity()) {
      event.preventDefault();
      event.stopPropagation();
    }
    form.classList.add('was-validated');
  });

  // Máscara para telefone
  const phoneInput = document.getElementById('phone');
  if (phoneInput) {
    phoneInput.addEventListener('input', function(e) {
      let value = e.target.value.replace(/\D/g, '');
      if (value.length <= 11) {
        if (value.length <= 2) {
          e.target.value = value;
        } else if (value.length <= 7) {
          e.target.value = `(${value.slice(0, 2)}) ${value.slice(2)}`;
        } else {
          e.target.value = `(${value.slice(0, 2)}) ${value.slice(2, 7)}-${value.slice(7)}`;
        }
      }
    });
  }
});
</script>
@endsection