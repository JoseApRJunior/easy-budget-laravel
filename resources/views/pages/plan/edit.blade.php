@extends('layouts.app')

@section('content')
    <div class="main-container py-1">
        <!-- Cabeçalho -->
        <div class="text-center mb-5">
            <h1 class="h2 fw-bold text-primary mb-3">Editar Plano</h1>
            <p class="text-muted lead">Atualize as informações do plano "{{ $plan->name }}"</p>
        </div>

        <!-- Formulário -->
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-lg border-0 rounded-lg">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-pencil-square me-2"></i>Editar Informações do Plano
                        </h5>
                    </div>

                    <form action="{{ route('plans.update', $plan->slug) }}" method="POST" class="needs-validation"
                        novalidate>
                        @csrf
                        @method('PUT')

                        <div class="card-body p-4">
                            <!-- Nome do Plano -->
                            <div class="mb-4">
                                <label for="name" class="form-label fw-bold">
                                    <i class="bi bi-tag text-primary me-1"></i>Nome do Plano <span
                                        class="text-danger">*</span>
                                </label>
                                <input type="text"
                                    class="form-control form-control-lg @error('name') is-invalid @enderror" id="name"
                                    name="name" value="{{ old('name', $plan->name) }}"
                                    placeholder="Ex: Plano Básico, Plano Profissional" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Slug -->
                            <div class="mb-4">
                                <label for="slug" class="form-label fw-bold">
                                    <i class="bi bi-link-45deg text-primary me-1"></i>Slug (URL) <span
                                        class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('slug') is-invalid @enderror"
                                    id="slug" name="slug" value="{{ old('slug', $plan->slug) }}"
                                    placeholder="Ex: plano-basico, plano-profissional" required>
                                <div class="form-text">Será usado na URL do plano. Apenas letras minúsculas, números e
                                    hífens.</div>
                                @error('slug')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Descrição -->
                            <div class="mb-4">
                                <label for="description" class="form-label fw-bold">
                                    <i class="bi bi-file-text text-primary me-1"></i>Descrição
                                </label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                    rows="3" placeholder="Descreva os benefícios e características do plano">{{ old('description', $plan->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Preço -->
                            <div class="mb-4">
                                <label for="price" class="form-label fw-bold">
                                    <i class="bi bi-cash text-primary me-1"></i>Preço (R$) <span
                                        class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="text"
                                        class="form-control form-control-lg @error('price') is-invalid @enderror"
                                        id="price" name="price"
                                        value="{{ old('price', 'R$ ' . number_format($plan->price, 2, ',', '.')) }}"
                                        inputmode="numeric" placeholder="R$ 0,00" required>
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
                                        <i class="bi bi-file-earmark-text text-primary me-1"></i>Máximo de Orçamentos <span
                                            class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control @error('max_budgets') is-invalid @enderror"
                                        id="max_budgets" name="max_budgets"
                                        value="{{ old('max_budgets', $plan->max_budgets) }}" min="1"
                                        placeholder="Ex: 50" required>
                                    <div class="form-text">Número máximo de orçamentos que podem ser criados por mês.</div>
                                    @error('max_budgets')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-4">
                                    <label for="max_clients" class="form-label fw-bold">
                                        <i class="bi bi-people text-primary me-1"></i>Máximo de Clientes <span
                                            class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control @error('max_clients') is-invalid @enderror"
                                        id="max_clients" name="max_clients"
                                        value="{{ old('max_clients', $plan->max_clients) }}" min="1"
                                        placeholder="Ex: 100" required>
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
                                <textarea class="form-control @error('features') is-invalid @enderror" id="features" name="features" rows="4"
                                    placeholder="Liste os recursos incluídos no plano, um por linha">{{ old('features', is_array($plan->features) ? implode("\n", $plan->features) : $plan->features) }}</textarea>
                                <div class="form-text">Digite cada recurso em uma linha separada.</div>
                                @error('features')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="status" name="status"
                                        value="1" {{ old('status', $plan->status) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="status">
                                        <i class="bi bi-toggle-on text-success me-1"></i>Plano Ativo
                                    </label>
                                </div>
                                <div class="form-text">Marque para tornar o plano disponível para novos assinantes.</div>
                            </div>

                            <!-- Informações do plano -->
                            <div class="alert alert-info">
                                <h6><i class="bi bi-info-circle me-2"></i>Informações do Plano</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-muted">Criado em:</small><br>
                                        <strong>{{ $plan->created_at->format('d/m/Y H:i') }}</strong>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">Última atualização:</small><br>
                                        <strong>{{ $plan->updated_at->format('d/m/Y H:i') }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer com botões -->
                        <div class="card-footer bg-light d-flex justify-content-between">
                            <x-button type="link" :href="route('plans.show', $plan->slug)" variant="info" icon="eye" label="Visualizar" />

                            <div class="d-flex gap-2">
                                <x-button type="link" :href="route('plans.index')" variant="secondary" icon="arrow-left" label="Voltar" />
                                <x-button type="submit" variant="warning" size="lg" icon="check-lg" label="Salvar Alterações" />
                            </div>
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
            (function() {
                'use strict'
                const forms = document.querySelectorAll('.needs-validation')
                Array.prototype.slice.call(forms).forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
            })()
            // Máscara BRL para preço de plano (edição)
            if (window.VanillaMask) {
                new VanillaMask('price', 'currency');
            }
            document.querySelector('form[action*="plans"]').addEventListener('submit', function(e) {
                const price = document.getElementById('price');
                if (price && window.parseCurrencyBRLToNumber) {
                    price.value = window.parseCurrencyBRLToNumber(price.value).toFixed(2);
                }
            });
        </script>
    @endpush
@endsection
