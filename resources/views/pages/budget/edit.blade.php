@extends('layouts.app')

@section('title', 'Editar Orçamento')

@section('content')
    <div class="container-fluid py-1">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-pencil-square me-2"></i>Editar Orçamento
                </h1>
                <p class="text-muted mb-0">Atualize as informações do orçamento</p>
            </div>
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('provider.budgets.index') }}">Orçamentos</a></li>
                    <li class="breadcrumb-item"><a
                            href="{{ route('provider.budgets.show', $budget->code) }}">{{ $budget->code }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Editar</li>
                </ol>
            </nav>
        </div>

        <form id="edit-budget-form" action="{{ route('provider.budgets.update', $budget->code) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-4">
                <!-- Informações Básicas -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent">
                            <h5 class="mb-0">
                                <i class="bi bi-info-circle me-2"></i>Informações Básicas
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <!-- Cliente (readonly) -->
                                <div class="col-md-6">
                                    <label for="customer_display" class="form-label">Cliente</label>
                                    <input type="text" id="customer_display" class="form-control"
                                        value="{{ $budget->customer->commonData ? ($budget->customer->commonData->company_name ?: ($budget->customer->commonData->first_name . ' ' . $budget->customer->commonData->last_name)) : 'Nome não informado' }} ({{ $budget->customer->commonData ? ($budget->customer->commonData->cnpj ?: $budget->customer->commonData->cpf) : 'Sem documento' }})"
                                        disabled readonly>
                                    <input type="hidden" name="customer_id" value="{{ $budget->customer_id }}">
                                </div>

                                <!-- Data de Vencimento -->
                                <div class="col-md-3">
                                    <label for="due_date" class="form-label">Data de Vencimento</label>
                                    <input type="date" id="due_date" name="due_date"
                                        class="form-control @error('due_date') is-invalid @enderror"
                                        value="{{ old('due_date', $budget->due_date ? $budget->due_date->format('Y-m-d') : '') }}"
                                        required>
                                    @error('due_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Status Atual (readonly) -->
                                <div class="col-md-3">
                                    <label class="form-label">Status Atual</label>
                                    <input type="text" class="form-control" value="{{ $budget->status->label() }}"
                                        readonly disabled>
                                    <input type="hidden" name="status" value="{{ $budget->status->value }}">
                                    <small class="text-muted">O status será alterado para "Pendente" após salvar</small>
                                </div>

                                <!-- Descrição -->
                                <div class="col-12">
                                    <label for="description" class="form-label">Descrição</label>
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
                                    <label for="payment_terms" class="form-label">Condições de Pagamento (Opcional)</label>
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
                        <div class="card-header bg-transparent">
                            <h5 class="mb-0">
                                <i class="bi bi-cash-stack me-2"></i>Valores
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <!-- Valor Total -->
                                <div class="col-md-6">
                                    <label for="total" class="form-label">Valor Total</label>
                                    <input type="number" id="total" name="total"
                                        class="form-control @error('total') is-invalid @enderror"
                                        value="{{ old('total', $budget->total) }}" step="0.01" min="0">
                                    @error('total')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Desconto -->
                                <div class="col-md-6">
                                    <label for="discount" class="form-label">Desconto</label>
                                    <input type="text" id="discount" name="discount"
                                        class="form-control @error('discount') is-invalid @enderror" inputmode="numeric"
                                        value="{{ old('discount', $budget->discount) }}">
                                    @error('discount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botões -->
            <div class="d-flex justify-content-between mt-4">
                <div>
                    <a href="{{ url()->previous(route('provider.budgets.index')) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Cancelar
                    </a>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Salvar
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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

            // Discount field formatting
            try {
                var discountInput = document.getElementById('discount');
                console.info('[budget:edit] DOM ready, discount input found:', !!discountInput, 'VanillaMask:', !!
                    window.VanillaMask);
                if (window.VanillaMask) {
                    new VanillaMask('discount', 'currency');
                    console.info('[budget:edit] VanillaMask initialized for discount');
                } else if (discountInput) {
                    discountInput.addEventListener('input', function() {
                        var digits = this.value.replace(/\D/g, '');
                        var num = (parseInt(digits || '0', 10) / 100);
                        var integer = Math.floor(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                        var cents = Math.round((num - Math.floor(num)) * 100).toString().padStart(2, '0');
                        this.value = 'R$ ' + integer + ',' + cents;
                        console.debug('[budget:edit] discount input formatted:', this.value);
                    });
                }
                var form = document.getElementById('edit-budget-form');
                if (form) {
                    form.addEventListener('submit', function() {
                        var discountInput = document.getElementById('discount');
                        var num = 0;
                        if (discountInput) {
                            if (window.parseCurrencyBRLToNumber) {
                                num = window.parseCurrencyBRLToNumber(discountInput.value);
                            } else {
                                var digits = discountInput.value.replace(/\D/g, '');
                                num = parseInt(digits || '0', 10) / 100;
                            }
                            discountInput.value = Number.isFinite(num) ? num.toFixed(2) : '0.00';
                            console.info('[budget:edit] discount normalized on submit:', discountInput.value);
                        }
                    });
                }
            } catch (e) {}
        });
    </script>
@endpush
