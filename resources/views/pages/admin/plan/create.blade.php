@extends('layouts.app')

@section('title', 'Criar Novo Plano')

@section('content')
    <x-layout.page-container>
        <x-layout.page-header
            title="Criar Novo Plano"
            icon="plus-circle"
            :breadcrumb-items="[
                'Dashboard' => route('admin.dashboard'),
                'Planos' => route('admin.plans.index'),
                'Novo' => '#'
            ]">
            <x-slot:actions>
                <x-ui.button :href="route('admin.plans.index')" variant="secondary" outline icon="arrow-left" label="Voltar" />
            </x-slot:actions>
        </x-layout.page-header>

        <form method="POST" action="{{ route('admin.plans.store') }}">
            @csrf
            
            <x-layout.grid-row>
                <div class="col-md-8">
                    <x-ui.card>
                        <x-slot:header>
                            <h5 class="mb-0 text-primary fw-bold">
                                <i class="bi bi-file-earmark-text me-2"></i>Dados do Plano
                            </h5>
                        </x-slot:header>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <x-ui.form.input 
                                    name="name" 
                                    id="name" 
                                    label="Nome do Plano *" 
                                    value="{{ old('name') }}" 
                                    required 
                                />
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <x-ui.form.input 
                                    type="number" 
                                    step="0.01" 
                                    name="price" 
                                    id="price" 
                                    label="Preço (R$) *" 
                                    value="{{ old('price') }}" 
                                    required 
                                />
                            </div>
                        </div>

                        <div class="mb-3">
                            <x-ui.form.textarea 
                                name="description" 
                                id="description" 
                                label="Descrição" 
                                rows="3"
                            >{{ old('description') }}</x-ui.form.textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <x-ui.form.input 
                                    type="number" 
                                    name="max_budgets" 
                                    id="max_budgets" 
                                    label="Máximo de Orçamentos *" 
                                    value="{{ old('max_budgets', 10) }}" 
                                    required 
                                />
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <x-ui.form.input 
                                    type="number" 
                                    name="max_clients" 
                                    id="max_clients" 
                                    label="Máximo de Clientes *" 
                                    value="{{ old('max_clients', 50) }}" 
                                    required 
                                />
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Status *</label>
                            <div class="d-flex gap-3">
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
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Recursos Disponíveis</label>
                            <div class="card bg-light border-0 p-3">
                                <div class="row">
                                    @foreach($features as $feature => $label)
                                        <div class="col-md-6">
                                            <div class="form-check mb-2">
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
                        </div>

                        <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                            <x-ui.button :href="route('admin.plans.index')" variant="secondary" outline label="Cancelar" />
                            <x-ui.button type="submit" variant="primary" icon="save" label="Criar Plano" />
                        </div>
                    </x-ui.card>
                </div>

                <div class="col-md-4">
                    <x-ui.card>
                        <x-slot:header>
                            <h5 class="mb-0 text-primary fw-bold">
                                <i class="bi bi-info-circle me-2"></i>Informações Úteis
                            </h5>
                        </x-slot:header>
                        
                        <h6 class="text-dark fw-bold mb-2">Configurações Recomendadas:</h6>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-3">
                                <strong class="text-primary">Plano Básico:</strong>
                                <ul class="list-unstyled ps-3 small text-muted">
                                    <li>• Máximo de Orçamentos: 10</li>
                                    <li>• Máximo de Clientes: 50</li>
                                    <li>• Recursos essenciais</li>
                                </ul>
                            </li>
                            <li class="mb-3 border-top pt-3">
                                <strong class="text-primary">Plano Profissional:</strong>
                                <ul class="list-unstyled ps-3 small text-muted">
                                    <li>• Máximo de Orçamentos: 50</li>
                                    <li>• Máximo de Clientes: 200</li>
                                    <li>• Recursos avançados</li>
                                </ul>
                            </li>
                            <li class="border-top pt-3">
                                <strong class="text-primary">Plano Empresarial:</strong>
                                <ul class="list-unstyled ps-3 small text-muted">
                                    <li>• Máximo de Orçamentos: Ilimitado</li>
                                    <li>• Máximo de Clientes: Ilimitado</li>
                                    <li>• Todos os recursos</li>
                                </ul>
                            </li>
                        </ul>
                    </x-ui.card>
                </div>
            </x-layout.grid-row>
        </form>
    </x-layout.page-container>
@endsection
