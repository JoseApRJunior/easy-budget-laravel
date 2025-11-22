@extends('layouts.app')

@section('title', 'Novo Serviço')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-plus-circle me-2"></i>
                        Criar Novo Serviço
                    </h3>
                    <div class="card-actions">
                        <a href="{{ route('provider.services.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>
                            Voltar à Lista
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if($budget)
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Orçamento pré-selecionado:</strong> {{ $budget->code }} -
                            {{ Str::limit($budget->description, 50) }}
                            <a href="{{ route('provider.services.create') }}" class="btn btn-sm btn-outline-info ms-2">
                                <i class="fas fa-times"></i> Remover
                            </a>
                        </div>
                    @endif

                    <form id="serviceForm" method="POST" action="{{ route('provider.services.store') }}">
                        @csrf

                        <!-- Informações Básicas -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="budget_id" class="form-label">
                                        Orçamento <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('budget_id') is-invalid @enderror"
                                            id="budget_id"
                                            name="budget_id"
                                            required>
                                        <option value="">Selecione um orçamento</option>
                                        @foreach($budgets as $budgetOption)
                                            <option value="{{ $budgetOption->id }}"
                                                    {{ (old('budget_id') == $budgetOption->id || ($budget && $budget->id == $budgetOption->id)) ? 'selected' : '' }}>
                                                {{ $budgetOption->code }} - R$ {{ number_format($budgetOption->total, 2, ',', '.') }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('budget_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">
                                        Categoria <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('category_id') is-invalid @enderror"
                                            id="category_id"
                                            name="category_id"
                                            required>
                                        <option value="">Selecione uma categoria</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="code" class="form-label">
                                        Código do Serviço <span class="text-muted">(gerado automaticamente)</span>
                                    </label>
                                    <input type="text"
                                           class="form-control"
                                           id="code"
                                           name="code"
                                           value="{{ old('code') }}"
                                           readonly
                                           placeholder="Será gerado automaticamente">
                                    <div class="form-text">Código será gerado automaticamente ao salvar.</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="service_statuses_id" class="form-label">
                                        Status <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('service_statuses_id') is-invalid @enderror"
                                            id="service_statuses_id"
                                            name="service_statuses_id"
                                            required>
                                        <option value="">Selecione um status</option>
                                        @foreach($statusOptions as $status)
                                            <option value="{{ $status->value }}" {{ old('service_statuses_id', \App\Enums\ServiceStatus::DRAFT->value) == $status->value ? 'selected' : '' }}>
                                                {{ $status->getDescription() }}
                                            </option>
                                        @endforeach
                                        </select>
                                        @error('service_statuses_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                        </div>

                        <!-- Descrição e Detalhes -->
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="description" class="form-label">Descrição</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror"
                                              id="description"
                                              name="description"
                                              rows="3"
                                              placeholder="Descreva o serviço a ser realizado...">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Valores e Datas -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="discount" class="form-label">Desconto (R$)</label>
                                    <input type="text"
                                           class="form-control @error('discount') is-invalid @enderror"
                                           id="discount"
                                           name="discount"
                                           value="{{ old('discount', 'R$ 0,00') }}"
                                           inputmode="numeric"
                                           placeholder="R$ 0,00">
                                    @error('discount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="total" class="form-label">Total (R$)</label>
                                    <input type="number"
                                           class="form-control @error('total') is-invalid @enderror"
                                           id="total"
                                           name="total"
                                           value="{{ old('total', '0.00') }}"
                                           step="0.01"
                                           min="0"
                                           placeholder="0,00" readonly>
                                    @error('total')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="due_date" class="form-label">Data de Vencimento</label>
                                    <input type="text"
                                           class="form-control @error('due_date') is-invalid @enderror"
                                           id="due_date"
                                           name="due_date"
                                           inputmode="numeric"
                                           placeholder="dd/mm/aaaa"
                                           value="{{ old('due_date', now()->format('d/m/Y')) }}">
                                    @error('due_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Produtos/Serviços -->
                        <div class="row">
                            <div class="col-12">
                                <h5 class="mb-3">
                                    <i class="fas fa-box me-2"></i>
                                    Produtos/Serviços
                                </h5>

                                <div class="mb-3">
                                    <button type="button" class="btn btn-success btn-sm" id="addItem">
                                        <i class="fas fa-plus me-1"></i>
                                        Adicionar Item
                                    </button>
                                </div>

                                <div id="itemsContainer">
                                    <!-- Itens serão adicionados dinamicamente -->
                                </div>
                            </div>
                        </div>

                        <!-- Botões de Ação -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('provider.services.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-times me-1"></i>
                                        Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>
                                        Salvar Serviço
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Template para novos itens -->
<template id="itemTemplate">
    <div class="item-row border rounded p-3 mb-3 bg-light">
        <div class="row align-items-end">
            <div class="col-md-4">
                <label class="form-label">Produto/Serviço</label>
                <select class="form-select product-select" name="items[__INDEX__][product_id]" required>
                    <option value="">Selecione um produto</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" data-price="{{ $product->price }}">
                            {{ $product->name }} - R$ {{ number_format($product->price, 2, ',', '.') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Quantidade</label>
                <div class="input-group">
                    <button type="button" class="btn btn-outline-secondary btn-sm quantity-decrement">-</button>
                    <input type="number"
                           class="form-control quantity-input"
                           name="items[__INDEX__][quantity]"
                           value="1"
                           min="1"
                           step="1"
                           inputmode="numeric"
                           required>
                    <button type="button" class="btn btn-outline-secondary btn-sm quantity-increment">+</button>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label">Valor Unit.</label>
                <input type="text"
                       class="form-control unit-value"
                       name="items[__INDEX__][unit_value]"
                       inputmode="numeric"
                       required readonly>
            </div>
            <div class="col-md-2">
                <label class="form-label">Total</label>
                <input type="text"
                       class="form-control item-total"
                       inputmode="numeric"
                       readonly>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger btn-sm remove-item w-100">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
</template>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let itemIndex = 0;

    // Adicionar novo item
    document.getElementById('addItem').addEventListener('click', function() {
        addItem();
    });

    function addItem() {
        const template = document.getElementById('itemTemplate');
        const container = document.getElementById('itemsContainer');
        const clone = template.content.cloneNode(true);

        // Atualizar índices
        const inputs = clone.querySelectorAll('[name]');
        inputs.forEach(input => {
            input.name = input.name.replace('__INDEX__', itemIndex);
        });

        container.appendChild(clone);
        itemIndex++;

        // Adicionar listeners para cálculos
        addItemListeners();
    }

    function addItemListeners() {
        const lastItem = document.querySelector('#itemsContainer .item-row:last-child');
        if (lastItem) {
            const productSelect = lastItem.querySelector('.product-select');
            const quantityInput = lastItem.querySelector('.quantity-input');
            const unitValueInput = lastItem.querySelector('.unit-value');
            const totalInput = lastItem.querySelector('.item-total');
            const removeButton = lastItem.querySelector('.remove-item');
            const incBtn = lastItem.querySelector('.quantity-increment');
            const decBtn = lastItem.querySelector('.quantity-decrement');

            // Preencher valor unitário quando produto for selecionado
            productSelect.addEventListener('change', function() {
                const price = this.options[this.selectedIndex].dataset.price;
                const num = parseFloat(price || '0');
                unitValueInput.value = window.formatCurrencyBRL ? window.formatCurrencyBRL(num) : (num.toFixed ? num.toFixed(2) : num);
                calculateTotal();
            });

            // Calcular total
            quantityInput.addEventListener('input', calculateTotal);
            unitValueInput.setAttribute('type','text');
            unitValueInput.setAttribute('inputmode','numeric');
            unitValueInput.addEventListener('input', function(){
                var digits = this.value.replace(/\D/g, '');
                var num = (parseInt(digits||'0',10)/100);
                var integer = Math.floor(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                var cents = Math.round((num - Math.floor(num))*100).toString().padStart(2,'0');
                this.value = 'R$ ' + integer + ',' + cents;
                console.debug('[service:create] unit-value formatted:', this.value);
                calculateTotal();
            });

            // Bloquear entrada não numérica na quantidade
            quantityInput.addEventListener('keypress', function(e) {
                if (!/[0-9]/.test(e.key)) {
                    e.preventDefault();
                }
            });

            // Incremento/decremento
            incBtn.addEventListener('click', function() {
                const current = parseInt(quantityInput.value || '1', 10);
                quantityInput.value = (isNaN(current) ? 1 : current + 1);
                calculateTotal();
            });
            decBtn.addEventListener('click', function() {
                const current = parseInt(quantityInput.value || '1', 10);
                const next = (isNaN(current) ? 1 : Math.max(1, current - 1));
                quantityInput.value = next;
                calculateTotal();
            });

            function calculateTotal() {
                const quantity = parseFloat(quantityInput.value) || 0;
                const unitValue = window.parseCurrencyBRLToNumber ? window.parseCurrencyBRLToNumber(unitValueInput.value) : 0;
                const total = quantity * unitValue;
                totalInput.value = window.formatCurrencyBRL ? window.formatCurrencyBRL(total) : total.toFixed(2);
                updateFormTotal();
            }

            // Remover item
            removeButton.addEventListener('click', function() {
                if (!confirm('Deseja excluir este item?')) { return; }
                if (document.querySelectorAll('#itemsContainer .item-row').length > 1) {
                    lastItem.remove();
                    updateFormTotal();
                }
            });
        }
    }

    // funções de máscara BRL providas pela VanillaMask

    // Máscara para desconto (moeda BRL)
    const discountInput = document.getElementById('discount');
    if (discountInput) {
        discountInput.setAttribute('type','text');
        discountInput.setAttribute('inputmode','numeric');
        discountInput.addEventListener('input', function(e) {
            const num = window.parseCurrencyBRLToNumber ? window.parseCurrencyBRLToNumber(e.target.value) : 0;
            e.target.value = window.formatCurrencyBRL ? window.formatCurrencyBRL(num) : e.target.value;
            updateFormTotal();
        });
        discountInput.addEventListener('blur', function(e) {
            const num = window.parseCurrencyBRLToNumber ? window.parseCurrencyBRLToNumber(e.target.value) : 0;
            e.target.value = window.formatCurrencyBRL ? window.formatCurrencyBRL(num) : e.target.value;
        });
    }

function updateFormTotal() {
    const totals = document.querySelectorAll('.item-total');
    let sum = 0;
    totals.forEach(input => {
        const val = window.parseCurrencyBRLToNumber ? window.parseCurrencyBRLToNumber(input.value) : 0;
        sum += val;
    });
    const discountEl = document.getElementById('discount');
    let discountNum = window.parseCurrencyBRLToNumber ? window.parseCurrencyBRLToNumber(discountEl ? discountEl.value : 0) : 0;
    if (discountNum > sum) {
        discountNum = sum;
        if (discountEl && window.formatCurrencyBRL) {
            discountEl.value = window.formatCurrencyBRL(sum);
        }
    }
    const finalTotal = Math.max(0, sum - discountNum);
    document.getElementById('total').value = finalTotal.toFixed(2);
}

    // Calcular total quando desconto mudar
    document.getElementById('discount').addEventListener('input', updateFormTotal);

    // Auto-seleção de orçamento se fornecido
    @if($budget)
        document.getElementById('budget_id').value = '{{ $budget->id }}';
    @endif

    // Adicionar primeiro item automaticamente
    addItem();

    if (window.VanillaMask) {
        new VanillaMask('due_date', 'date');
        new VanillaMask('discount', 'currency');
    }

    function parseBRDateToISO(str) {
        const m = (str || '').match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
        if (!m) return null;
        const d = parseInt(m[1], 10);
        const mo = parseInt(m[2], 10);
        const y = parseInt(m[3], 10);
        if (mo < 1 || mo > 12) return null;
        if (d < 1 || d > 31) return null;
        const iso = `${y}-${String(mo).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        return iso;
    }

    document.getElementById('serviceForm').addEventListener('submit', function(e) {
        let valid = true;
        const budget = document.getElementById('budget_id');
        const category = document.getElementById('category_id');
        const status = document.getElementById('service_statuses_id');
        const dueDate = document.getElementById('due_date');
        if (!budget.value) { window.easyAlert.error('Selecione um orçamento'); valid = false; }
        if (!category.value) { window.easyAlert.error('Selecione uma categoria'); valid = false; }
        if (!status.value) { window.easyAlert.error('Selecione um status'); valid = false; }
        if (dueDate.value) {
            const iso = parseBRDateToISO(dueDate.value);
            if (!iso) { window.easyAlert.error('Data inválida'); valid = false; }
            else {
                const today = new Date();
                const todayIso = today.toISOString().slice(0,10);
                if (iso < todayIso) { window.easyAlert.error('Data deve ser hoje ou posterior'); valid = false; }
                dueDate.value = iso;
            }
        }
        const rows = document.querySelectorAll('#itemsContainer .item-row');
        if (rows.length === 0) { window.easyAlert.error('Adicione ao menos um item'); valid = false; }
        rows.forEach(function(row) {
            const q = row.querySelector('.quantity-input');
            const u = row.querySelector('.unit-value');
            const qNum = (parseFloat(q.value) || 0);
            const uNum = window.parseCurrencyBRLToNumber ? window.parseCurrencyBRLToNumber(u.value) : (parseFloat(u.value) || 0);
            if (qNum <= 0) { window.easyAlert.error('Quantidade deve ser maior que zero'); valid = false; }
            if (uNum <= 0) { window.easyAlert.error('Valor unitário deve ser maior que zero'); valid = false; }
        });
        // Converter campos monetários para número antes de enviar
        const discountNum = window.parseCurrencyBRLToNumber ? window.parseCurrencyBRLToNumber(discountInput.value) : 0;
        discountInput.value = discountNum.toFixed(2);
        document.querySelectorAll('.unit-value').forEach(function(input){
            const num = window.parseCurrencyBRLToNumber ? window.parseCurrencyBRLToNumber(input.value) : 0;
            input.value = num.toFixed(2);
        });
        updateFormTotal();
        if (!valid) { e.preventDefault(); }
    });
});
</script>
@endpush
@endsection
