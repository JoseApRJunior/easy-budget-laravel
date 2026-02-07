<x-app-layout title="Editar Plano">
    <x-layout.page-container>
        <x-layout.page-header
            :title="'Editar Plano: ' . $plan->name"
            icon="pencil"
            :breadcrumb-items="[
                'Dashboard' => route('admin.dashboard'),
                'Planos' => route('admin.plans.index'),
                'Editar' => '#'
            ]">
            <x-slot:actions>
                <x-ui.button :href="route('admin.plans.index')" variant="secondary" outline icon="arrow-left" label="Voltar" />
            </x-slot:actions>
        </x-layout.page-header>

        <form method="POST" action="{{ route('admin.plans.update', $plan) }}">
            @csrf
            @method('PUT')
            
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
                                    value="{{ old('name', $plan->name) }}" 
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
                                    value="{{ old('price', $plan->price) }}" 
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
                            >{{ old('description', $plan->description) }}</x-ui.form.textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <x-ui.form.input 
                                    type="number" 
                                    name="max_budgets" 
                                    id="max_budgets" 
                                    label="Máximo de Orçamentos *" 
                                    value="{{ old('max_budgets', $plan->max_budgets) }}" 
                                    required 
                                />
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <x-ui.form.input 
                                    type="number" 
                                    name="max_clients" 
                                    id="max_clients" 
                                    label="Máximo de Clientes *" 
                                    value="{{ old('max_clients', $plan->max_clients) }}" 
                                    required 
                                />
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Status *</label>
                            <div class="d-flex gap-3">
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
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Recursos Disponíveis</label>
                            <div class="card bg-light border-0 p-3">
                                <div class="row">
                                    @php
                                        $currentFeatures = json_decode($plan->features, true) ?? [];
                                    @endphp
                                    @foreach($features as $feature => $label)
                                        <div class="col-md-6">
                                            <div class="form-check mb-2">
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
                        </div>

                        <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                            <x-ui.button :href="route('admin.plans.index')" variant="secondary" outline label="Cancelar" />
                            <x-ui.button type="submit" variant="primary" icon="save" label="Atualizar Plano" />
                        </div>
                    </x-ui.card>
                </div>

                <div class="col-md-4">
                    <x-ui.card>
                        <x-slot:header>
                            <h5 class="mb-0 text-primary fw-bold">
                                <i class="bi bi-info-circle me-2"></i>Informações do Plano
                            </h5>
                        </x-slot:header>
                        
                        <div class="mb-4">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <th class="text-muted text-end w-50">ID:</th>
                                    <td class="fw-bold ps-3">{{ $plan->id }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted text-end">Slug:</th>
                                    <td class="ps-3"><code>{{ $plan->slug }}</code></td>
                                </tr>
                                <tr>
                                    <th class="text-muted text-end">Criado em:</th>
                                    <td class="ps-3">{{ $plan->created_at ? $plan->created_at->format('d/m/Y') : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted text-end">Atualizado:</th>
                                    <td class="ps-3">{{ $plan->updated_at ? $plan->updated_at->format('d/m/Y') : 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>

                        <div class="mb-4">
                            <h6 class="text-dark fw-bold mb-3 border-bottom pb-2">Estatísticas Atuais</h6>
                            <ul class="list-unstyled mb-0">
                                <li class="d-flex justify-content-between mb-2">
                                    <span><i class="bi bi-people me-2"></i>Assinaturas:</span>
                                    <span class="badge bg-secondary">{{ $plan->planSubscriptions()->count() }}</span>
                                </li>
                                <li class="d-flex justify-content-between mb-2">
                                    <span><i class="bi bi-check-circle text-success me-2"></i>Ativas:</span>
                                    <span class="badge bg-success">{{ $plan->planSubscriptions()->where('status', 'active')->count() }}</span>
                                </li>
                                <li class="d-flex justify-content-between mb-2">
                                    <span><i class="bi bi-x-circle text-danger me-2"></i>Canceladas:</span>
                                    <span class="badge bg-danger">{{ $plan->planSubscriptions()->where('status', 'cancelled')->count() }}</span>
                                </li>
                            </ul>
                        </div>

                        @if($plan->planSubscriptions()->exists())
                            <div class="alert alert-warning mb-4">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <strong>Atenção:</strong> Este plano possui assinaturas ativas. Alterações podem afetar usuários existentes.
                            </div>
                        @endif

                        <div class="card bg-light border-0">
                            <div class="card-header bg-transparent border-bottom">
                                <h6 class="mb-0 fw-bold"><i class="bi bi-lightning me-2"></i>Ações Rápidas</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <x-ui.button :href="route('admin.plans.duplicate', $plan)" variant="secondary" outline icon="copy" label="Duplicar Plano" size="sm" />
                                    <x-ui.button :href="route('admin.plans.analytics', $plan)" variant="primary" outline icon="graph-up" label="Ver Análises" size="sm" />
                                    <x-ui.button :href="route('admin.plans.subscribers', $plan)" variant="info" outline icon="people" label="Ver Assinantes" size="sm" />
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                </div>
            </x-layout.grid-row>
        </form>
    </x-layout.page-container>
</x-app-layout>
