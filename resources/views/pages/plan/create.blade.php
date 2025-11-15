@extends('layouts.app')

@section('content')
<div class="main-container py-4">
    <!-- Cabeçalho -->
    <div class="text-center mb-5">
        <h1 class="h2 fw-bold text-primary mb-3">Criar Novo Plano</h1>
        <p class="text-muted lead">Configure um novo plano de assinatura para seus clientes</p>
    </div>

    <!-- Formulário -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg border-0 rounded-lg">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-plus-circle me-2"></i>Informações do Plano
                    </h5>
                </div>

                <form action="{{ route('plans.store') }}" method="POST" class="needs-validation" novalidate>
                    @csrf

                    <div class="card-body p-4">
                        <!-- Nome do Plano -->
                        <div class="mb-4">
                            <label for="name" class="form-label fw-bold">
                                <i class="bi bi-tag text-primary me-1"></i>Nome do Plano <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control form-control-lg @error('name') is-invalid @enderror"
                                   id="name"
                                   name="name"
                                   value="{{ old('name') }}"
                                   placeholder="Ex: Plano Básico, Plano Profissional"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Slug -->
                        <div class="mb-4">
                            <label for="slug" class="form-label fw-bold">
                                <i class="bi bi-link-45deg text-primary me-1"></i>Slug (URL) <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('slug') is-invalid @enderror"
                                   id="slug"
                                   name="slug"
                                   value="{{ old('slug') }}"
                                   placeholder="Ex: plano-basico, plano-profissional"
                                   required>
                            <div class="form-text">Será usado na URL do plano. Apenas letras minúsculas, números e hífens.</div>
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Descrição -->
                        <div class="mb-4">
                            <label for="description" class="form-label fw-bold">
                                <i class="bi bi-file-text text-primary me-1"></i>Descrição
                            </label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description"
                                      name="description"
                                      rows="3"
                                      placeholder="Descreva os benefícios e características do plano">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Preço -->
                        <div class="mb-4">
                            <label for="price" class="form-label fw-bold">
                                <i class="bi bi-cash text-primary me-1"></i>Preço (R$) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="number"
                                       class="form-control form-control-lg @error('price') is-invalid @enderror"
                                       id="price"
                                       name="price"
                                       value="{{ old('price', '0.00') }}"
                                       step="0.01"
                                       min="0"
                                       placeholder="0,00"
                                       required>
                                <span class="input-group-text">/mês</span>
                            </div>
                            @error('price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Limites -->
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="max_budgets" class="form-label fw-bold">
                                    <i class="bi bi-file-earmark-text text-primary me-1"></i>Máximo de Orçamentos <span class="text-danger">*</span>
                                </label>
                                <input type="number"
                                       class="form-control @error('max_budgets') is-invalid @enderror"
                                       id="max_budgets"
                                       name="max_budgets"
                                       value="{{ old('max_budgets', 1) }}"
                                       min="1"
                                       placeholder="Ex: 50"
                                       required>
                                <div class="form-text">Número máximo de orçamentos que podem ser criados por mês.</div>
                                @error('max_budgets')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-4">
                                <label for="max_clients" class="form-label fw-bold">
                                    <i class="bi bi-people text-primary me-1"></i>Máximo de Clientes <span class="text-danger">*</span>
                                </label>
                                <input type="number"
                                       class="form-control @error('max_clients') is-invalid @enderror"
                                       id="max_clients"
                                       name="max_clients"
                                       value="{{ old('max_clients', 1) }}"
                                       min="1"
                                       placeholder="Ex: 100"
                                       required>
                                <div class="form-text">Número máximo de clientes que podem ser gerenciados.</div>
                                @error('max_clients')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Features -->
                        <div class="mb-4">
                            <label for="features" class="form-label fw-bold">
                                <i class="bi bi-check-circle text-primary me-1"></i>Recursos Incluídos
                            </label>
                            <textarea class="form-control @error('features') is-invalid @enderror"
                                      id="features"
                                      name="features"
                                      rows="4"
                                      placeholder="Liste os recursos incluídos no plano, um por linha">{{ old('features') }}</textarea>
                            <div class="form-text">Digite cada recurso em uma linha separada. Ex: Acesso básico, Até 3 orçamentos por mês</div>
                            @error('features')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Status -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input"
                                       type="checkbox"
                                       id="status"
                                       name="status"
                                       value="1"
                                       {{ old('status', true) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="status">
                                    <i class="bi bi-toggle-on text-success me-1"></i>Plano Ativo
                                </label>
                            </div>
                            <div class="form-text">Marque para tornar o plano disponível para novos assinantes.</div>
                        </div>
                    </div>

                    <!-- Footer com botões -->
                    <div class="card-footer bg-light d-flex justify-content-between">
                        <a href="{{ route('plans.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Voltar
                        </a>

                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-check-lg me-2"></i>Criar Plano
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Auto-gerar slug a partir do nome
document.getElementById('name').addEventListener('input', function() {
    const name = this.value;
    const slug = name.toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '') // Remove caracteres especiais
        .replace(/\s+/g, '-') // Substitui espaços por hífens
        .replace(/-+/g, '-') // Remove hífens duplicados
        .replace(/^-|-$/g, ''); // Remove hífens no início/fim

    document.getElementById('slug').value = slug;
});

// Validação do formulário
(function () {
    'use strict'
    const forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()
</script>
@endpush
@endsection
