@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <x-layout.page-header
        :title="'Editar Plano: ' . $plan->name"
        icon="pencil"
        :breadcrumb-items="[
            'Dashboard' => route('admin.dashboard'),
            'Planos' => route('admin.plans.index'),
            'Editar' => '#'
        ]">
        <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Voltar
        </a>
    </x-layout.page-header>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.plans.update', $plan) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Nome do Plano *</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $plan->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">Preço (R$) *</label>
                                <input type="number" step="0.01" class="form-control @error('price') is-invalid @enderror" 
                                       id="price" name="price" value="{{ old('price', $plan->price) }}" required>
                                @error('price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Descrição</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description', $plan->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="max_budgets" class="form-label">Máximo de Orçamentos *</label>
                                <input type="number" class="form-control @error('max_budgets') is-invalid @enderror" 
                                       id="max_budgets" name="max_budgets" value="{{ old('max_budgets', $plan->max_budgets) }}" required>
                                @error('max_budgets')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="max_clients" class="form-label">Máximo de Clientes *</label>
                                <input type="number" class="form-control @error('max_clients') is-invalid @enderror" 
                                       id="max_clients" name="max_clients" value="{{ old('max_clients', $plan->max_clients) }}" required>
                                @error('max_clients')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status *</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status" id="status_active" value="1" 
                                       {{ old('status', $plan->status == 'active' ? 1 : 0) == 1 ? 'checked' : '' }}>
                                <label class="form-check-label" for="status_active">
                                    Ativo
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status" id="status_inactive" value="0" 
                                       {{ old('status', $plan->status == 'active' ? 1 : 0) == 0 ? 'checked' : '' }}>
                                <label class="form-check-label" for="status_inactive">
                                    Inativo
                                </label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Recursos Disponíveis</label>
                            <div class="row">
                                @php
                                    $currentFeatures = json_decode($plan->features, true) ?? [];
                                @endphp
                                @foreach($features as $feature => $label)
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="features[]" id="feature_{{ $feature }}" value="{{ $feature }}"
                                                   {{ in_array($feature, old('features', $currentFeatures)) ? 'checked' : '' }}>
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
                                <i class="bi bi-save me-1"></i>Atualizar Plano
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informações do Plano</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th>ID:</th>
                            <td>{{ $plan->id }}</td>
                        </tr>
                        <tr>
                            <th>Slug:</th>
                            <td><code>{{ $plan->slug }}</code></td>
                        </tr>
                        <tr>
                            <th>Criado em:</th>
                            <td>{{ $plan->created_at ? $plan->created_at->format('d/m/Y H:i') : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Última atualização:</th>
                            <td>{{ $plan->updated_at ? $plan->updated_at->format('d/m/Y H:i') : 'N/A' }}</td>
                        </tr>
                    </table>

                    <hr>

                    <h6>Estatísticas Atuais:</h6>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-people me-2"></i>{{ $plan->planSubscriptions()->count() }} assinaturas</li>
                        <li><i class="bi bi-check-circle text-success me-2"></i>{{ $plan->planSubscriptions()->where('status', 'active')->count() }} ativas</li>
                        <li><i class="bi bi-x-circle text-danger me-2"></i>{{ $plan->planSubscriptions()->where('status', 'cancelled')->count() }} canceladas</li>
                    </ul>

                    @if($plan->planSubscriptions()->exists())
                        <div class="alert alert-warning mt-3">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Atenção:</strong> Este plano possui assinaturas ativas. Alterações podem afetar usuários existentes.
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Ações Rápidas</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.plans.duplicate', $plan) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-copy me-1"></i>Duplicar Plano
                        </a>
                        <a href="{{ route('admin.plans.analytics', $plan) }}" class="btn btn-outline-primary">
                            <i class="bi bi-graph-up me-1"></i>Ver Análises
                        </a>
                        <a href="{{ route('admin.plans.subscribers', $plan) }}" class="btn btn-outline-info">
                            <i class="bi bi-people me-1"></i>Ver Assinantes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection