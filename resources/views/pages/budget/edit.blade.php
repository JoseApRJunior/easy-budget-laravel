@extends('layouts.app')

@section('title', 'Editar Orçamento')

@section('content')
    <x-page-header
        title="Editar Orçamento"
        icon="pencil-square"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Orçamentos' => route('provider.budgets.dashboard'),
            $budget->code => route('provider.budgets.show', $budget->code),
            'Editar' => '#'
        ]">
        <p class="text-muted mb-0">Atualize as informações do orçamento <strong>{{ $budget->code }}</strong></p>
    </x-page-header>

    <form id="edit-budget-form" action="{{ route('provider.budgets.update', $budget->code) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <!-- Informações Básicas -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 py-3">
                        <h5 class="mb-0 d-flex align-items-center text-dark fw-bold">
                            <i class="bi bi-info-circle me-2"></i>
                            <span>Informações Básicas</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Cliente (readonly) -->
                            <div class="col-md-6">
                                <label for="customer_display" class="form-label small fw-bold text-muted text-uppercase">Cliente</label>
                                <input type="text" id="customer_display" class="form-control"
                                    value="{{ $budget->customer->commonData ? ($budget->customer->commonData->company_name ?: ($budget->customer->commonData->first_name . ' ' . $budget->customer->commonData->last_name)) : 'Nome não informado' }} ({{ $budget->customer->commonData ? ($budget->customer->commonData->cnpj ? \App\Helpers\DocumentHelper::formatCnpj($budget->customer->commonData->cnpj) : \App\Helpers\DocumentHelper::formatCpf($budget->customer->commonData->cpf)) : 'Sem documento' }})"
                                    disabled readonly>
                                <input type="hidden" name="customer_id" value="{{ $budget->customer_id }}">
                            </div>

                            <!-- Data de Vencimento -->
                            <div class="col-md-3">
                                <label for="due_date" class="form-label small fw-bold text-muted text-uppercase">Data de Vencimento</label>
                                <input type="date" class="form-control @error('due_date') is-invalid @enderror"
                                    id="due_date" name="due_date"
                                    value="{{ \App\Helpers\DateHelper::formatDateOrDefault(old('due_date', $budget->due_date ? $budget->due_date->format('Y-m-d') : ''), 'Y-m-d', $budget->due_date ? $budget->due_date->format('Y-m-d') : '') }}"
                                    required>
                                <div class="form-text text-muted small">
                                    <i class="bi bi-info-circle me-1"></i>A data de vencimento deve ser igual ou posterior a hoje.
                                </div>
                                @error('due_date')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Status Atual (readonly) -->
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Status Atual</label>
                                <input type="text" class="form-control" value="{{ $budget->status->label() }}"
                                    readonly disabled>
                                <input type="hidden" name="status" value="{{ $budget->status->value }}">
                                <small class="text-muted">O status será alterado para "Pendente" após salvar</small>
                            </div>

                            <!-- Descrição -->
                            <div class="col-12">
                                <label for="description" class="form-label small fw-bold text-muted text-uppercase">Descrição</label>
                                <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror"
                                    rows="4" maxlength="255"
                                    placeholder="Ex: Projeto de reforma da cozinha, incluindo instalação de armários e pintura.">{{ old('description', $budget->description) }}</textarea>
                                <div class="d-flex justify-content-end">
                                    <small id="char-count" class="text-muted mt-2">{{ 255 - strlen($budget->description) }}
                                        caracteres restantes</small>
                                </div>
                                @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Condições de Pagamento -->
                            <div class="col-12">
                                <label for="payment_terms" class="form-label small fw-bold text-muted text-uppercase">Condições de Pagamento (Opcional)</label>
                                <textarea id="payment_terms" name="payment_terms"
                                    class="form-control @error('payment_terms') is-invalid @enderror" rows="2" maxlength="255"
                                    placeholder="Ex: 50% de entrada e 50% na conclusão.">{{ old('payment_terms', $budget->payment_terms) }}</textarea>
                                @error('payment_terms')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Valores -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 py-3">
                        <h5 class="mb-0 d-flex align-items-center text-dark fw-bold">
                            <i class="bi bi-cash-stack me-2"></i>
                            <span>Valores</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Valor Total -->
                            <div class="col-md-6">
                                <label for="total" class="form-label small fw-bold text-muted text-uppercase">Valor Total</label>
                                <input type="text" id="total_display"
                                    class="form-control bg-light currency-brl @error('total') is-invalid @enderror"
                                    value="{{ \App\Helpers\CurrencyHelper::format(old('total', $budget->total)) }}"
                                    readonly tabindex="-1">
                                <input type="hidden" id="total" name="total" value="{{ old('total', $budget->total) }}">
                                @error('total')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Desconto -->
                            <div class="col-md-6">
                                <label for="discount" class="form-label small fw-bold text-muted text-uppercase">Desconto</label>
                                <input type="text" id="discount_display"
                                    class="form-control bg-light currency-brl @error('discount') is-invalid @enderror"
                                    value="{{ \App\Helpers\CurrencyHelper::format(old('discount', $budget->discount)) }}"
                                    readonly tabindex="-1">
                                <input type="hidden" id="discount" name="discount" value="{{ old('discount', $budget->discount) }}">
                                @error('discount')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botões -->
        <div class="d-flex justify-content-between align-items-center mt-5">
            <x-button type="link" :href="route('provider.budgets.show', $budget->code)" variant="outline-secondary" icon="x-circle" label="Cancelar" />
            <x-button type="submit" variant="primary" icon="check-circle" label="Salvar Alterações" />
        </div>
    </form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar máscaras de moeda
        if (typeof VanillaMask === 'function') {
            new VanillaMask('total_display', 'currency');
            new VanillaMask('discount_display', 'currency');
        }

        // Character counter for description
        const description = document.getElementById('description');
        const charCount = document.getElementById('char-count');
        const maxLength = 255;

        if (description && charCount) {
            description.addEventListener('input', function() {
                const remaining = maxLength - this.value.length;
                charCount.textContent = remaining + ' caracteres restantes';
            });
        }
    });
</script>
@endpush
