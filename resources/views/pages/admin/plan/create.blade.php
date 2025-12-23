@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-plus-circle me-2"></i>Criar Novo Plano</h1>
        <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Voltar
        </a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.plans.store') }}">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Nome do Plano *</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">Preço (R$) *</label>
                                <input type="number" step="0.01" class="form-control @error('price') is-invalid @enderror" 
                                       id="price" name="price" value="{{ old('price') }}" required>
                                @error('price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Descrição</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="max_budgets" class="form-label">Máximo de Orçamentos *</label>
                                <input type="number" class="form-control @error('max_budgets') is-invalid @enderror" 
                                       id="max_budgets" name="max_budgets" value="{{ old('max_budgets', 10) }}" required>
                                @error('max_budgets')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="max_clients" class="form-label">Máximo de Clientes *</label>
                                <input type="number" class="form-control @error('max_clients') is-invalid @enderror" 
                                       id="max_clients" name="max_clients" value="{{ old('max_clients', 50) }}" required>
                                @error('max_clients')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status *</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status" id="status_active" value="1" 
                                       {{ old('status', 1) == 1 ? 'checked' : '' }}>
                                <label class="form-check-label" for="status_active">
                                    Ativo
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status" id="status_inactive" value="0" 
                                       {{ old('status') == 0 ? 'checked' : '' }}>
                                <label class="form-check-label" for="status_inactive">
                                    Inativo
                                </label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Recursos Disponíveis</label>
                            <div class="row">
                                @foreach($features as $feature => $label)
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="features[]" id="feature_{{ $feature }}" value="{{ $feature }}"
                                                   {{ in_array($feature, old('features', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="feature_{{ $feature }}">
                                                {{ $label }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-1"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i>Criar Plano
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informações Úteis</h5>
                </div>
                <div class="card-body">
                    <h6>Configurações Recomendadas:</h6>
                    <ul class="list-unstyled">
                        <li><strong>Plano Básico:</strong></li>
                        <li>• Máximo de Orçamentos: 10</li>
                        <li>• Máximo de Clientes: 50</li>
                        <li>• Recursos essenciais</li>
                    </ul>
                    <hr>
                    <ul class="list-unstyled">
                        <li><strong>Plano Profissional:</strong></li>
                        <li>• Máximo de Orçamentos: 50</li>
                        <li>• Máximo de Clientes: 200</li>
                        <li>• Recursos avançados</li>
                    </ul>
                    <hr>
                    <ul class="list-unstyled">
                        <li><strong>Plano Empresarial:</strong></li>
                        <li>• Máximo de Orçamentos: Ilimitado</li>
                        <li>• Máximo de Clientes: Ilimitado</li>
                        <li>• Todos os recursos</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection