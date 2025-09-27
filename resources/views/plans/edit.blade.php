@extends('layouts.app')

@section('title', 'Editar Plano - Easy Budget')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('plans.index') }}" class="btn btn-outline-secondary me-3">
                <i class="bi bi-arrow-left me-1"></i>
                Voltar
            </a>
            <h1 class="h3 mb-0">
                <i class="bi bi-pencil text-primary me-2"></i>
                Editar Plano: {{ $plan->name }}
            </h1>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('plans.update', $plan) }}">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <!-- Nome -->
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">
                                Nome do Plano <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   id="name"
                                   name="name"
                                   value="{{ old('name', $plan->name) }}"
                                   required
                                   placeholder="Ex: Plano Básico">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Slug -->
                        <div class="col-md-6 mb-3">
                            <label for="slug" class="form-label">
                                Slug <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('slug') is-invalid @enderror"
                                   id="slug"
                                   name="slug"
                                   value="{{ old('slug', $plan->slug) }}"
                                   required
                                   placeholder="Ex: plano-basico">
                            <div class="form-text">Identificador único para URLs (sem espaços ou caracteres especiais)</div>
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <!-- Preço -->
                        <div class="col-md-4 mb-3">
                            <label for="price" class="form-label">
                                Preço <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="number"
                                       step="0.01"
                                       min="0"
                                       class="form-control @error('price') is-invalid @enderror"
                                       id="price"
                                       name="price"
                                       value="{{ old('price', $plan->price) }}"
                                       required
                                       placeholder="99.90">
                            </div>
                            @error('price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Máximo de Orçamentos -->
                        <div class="col-md-4 mb-3">
                            <label for="max_budgets" class="form-label">
                                Máx. Orçamentos
                            </label>
                            <input type="number"
                                   min="1"
                                   class="form-control @error('max_budgets') is-invalid @enderror"
                                   id="max_budgets"
                                   name="max_budgets"
                                   value="{{ old('max_budgets', $plan->max_budgets) }}"
                                   placeholder="100">
                            <div class="form-text">Deixe em branco para ilimitado</div>
                            @error('max_budgets')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Máximo de Clientes -->
                        <div class="col-md-4 mb-3">
                            <label for="max_clients" class="form-label">
                                Máx. Clientes
                            </label>
                            <input type="number"
                                   min="1"
                                   class="form-control @error('max_clients') is-invalid @enderror"
                                   id="max_clients"
                                   name="max_clients"
                                   value="{{ old('max_clients', $plan->max_clients) }}"
                                   placeholder="50">
                            <div class="form-text">Deixe em branco para ilimitado</div>
                            @error('max_clients')
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
                                   {{ old('status', $plan->status) === 'active' ? 'checked' : '' }}>
                            <label class="form-check-label" for="status">
                                <strong>Plano Ativo</strong>
                            </label>
                        </div>
                        <div class="form-text">Planos ativos podem ser utilizados por usuários</div>
                        @error('status')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Descrição -->
                    <div class="mb-4">
                        <label for="description" class="form-label">
                            Descrição
                        </label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                                  id="description"
                                  name="description"
                                  rows="4"
                                  placeholder="Descreva os benefícios e características deste plano...">{{ old('description', $plan->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Informações Adicionais -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="bg-light p-3 rounded">
                                <h6 class="mb-2">
                                    <i class="bi bi-info-circle text-primary me-1"></i>
                                    Informações
                                </h6>
                                <small class="text-muted">
                                    <strong>Criado em:</strong> {{ $plan->created_at->format('d/m/Y H:i') }}<br>
                                    <strong>Última atualização:</strong> {{ $plan->updated_at->format('d/m/Y H:i') }}
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-light p-3 rounded">
                                <h6 class="mb-2">
                                    <i class="bi bi-bar-chart text-primary me-1"></i>
                                    Estatísticas
                                </h6>
                                <small class="text-muted">
                                    <strong>Usuários ativos:</strong> {{ $plan->users()->where('status', 'active')->count() }}<br>
                                    <strong>Orçamentos criados:</strong> {{ $plan->budgets()->count() }}
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Botões -->
                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('plans.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-2"></i>
                            Cancelar
                        </a>
                        <div>
                            <a href="{{ route('plans.show', $plan) }}" class="btn btn-outline-info me-2">
                                <i class="bi bi-eye me-2"></i>
                                Visualizar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-2"></i>
                                Atualizar Plano
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Gerar slug automaticamente baseado no nome
    document.getElementById('name').addEventListener('input', function() {
        const name = this.value;
        const slug = name.toLowerCase()
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '') // Remove acentos
                        .replace(/[^a-z0-9\s-]/g, '') // Remove caracteres especiais
                        .trim()
                        .replace(/\s+/g, '-'); // Substitui espaços por hífens
        document.getElementById('slug').value = slug;
    });

    // Formatação do preço
    document.getElementById('price').addEventListener('blur', function() {
        let value = parseFloat(this.value);
        if (!isNaN(value)) {
            this.value = value.toFixed(2);
        }
    });
</script>
@endsection
