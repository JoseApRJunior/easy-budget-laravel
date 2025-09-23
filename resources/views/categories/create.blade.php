@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
  {{-- Cabeçalho --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">
      <i class="bi bi-tag-plus me-2"></i>Nova Categoria
    </h1>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('categories.index') }}">Categorias</a></li>
        <li class="breadcrumb-item active">Nova</li>
      </ol>
    </nav>
  </div>

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

  {{-- Formulário --}}
  <div class="card border-0 shadow-sm">
    <div class="card-body p-4">
      <form action="{{ route('categories.store') }}" method="POST">
        @csrf

        <div class="row g-4">
          {{-- Nome --}}
          <div class="col-md-12">
            <div class="form-floating">
              <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                placeholder="Nome da Categoria" value="{{ old('name') }}" required>
              <label for="name">Nome da Categoria *</label>
              @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          {{-- Slug (opcional) --}}
          <div class="col-md-12">
            <div class="form-floating">
              <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug"
                placeholder="Slug da Categoria" value="{{ old('slug') }}">
              <label for="slug">Slug da Categoria (opcional)</label>
              @error('slug')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <div class="form-text">
                Se não informado, será gerado automaticamente a partir do nome.
              </div>
            </div>
          </div>

          {{-- Descrição --}}
          <div class="col-md-12">
            <div class="form-floating">
              <textarea class="form-control @error('description') is-invalid @enderror" id="description"
                name="description" rows="4" placeholder="Descrição da categoria">{{ old('description') }}</textarea>
              <label for="description">Descrição (opcional)</label>
              @error('description')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          {{-- Status --}}
          <div class="col-md-6">
            <div class="form-floating">
              <select class="form-select @error('is_active') is-invalid @enderror" id="is_active" name="is_active">
                <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Ativa</option>
                <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inativa</option>
              </select>
              <label for="is_active">Status</label>
              @error('is_active')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>

        <div class="mt-4 d-flex justify-content-between">
          <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Voltar
          </a>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-2"></i>Salvar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const nameInput = document.getElementById('name');
  const slugInput = document.getElementById('slug');

  // Gerar slug automaticamente quando o nome muda
  if (nameInput && slugInput) {
    nameInput.addEventListener('input', function() {
      if (!slugInput.value) {
        slugInput.value = this.value
          .toLowerCase()
          .replace(/[^a-z0-9\s-]/g, '')
          .replace(/\s+/g, '-')
          .replace(/-+/g, '-')
          .trim('-');
      }
    });
  }
});
</script>
@endsection