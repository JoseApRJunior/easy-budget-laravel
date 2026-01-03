@extends('layouts.app')

@section('title', 'Novo Serviço')

@section('content')
<div class="container-fluid py-1">
    <x-page-header
        title="Novo Serviço"
        icon="plus-circle"
        :breadcrumb-items="[
                'Serviços' => route('provider.services.index'),
                'Novo' => '#'
            ]">
        <p class="text-muted mb-0">Preencha os dados para criar um novo serviço</p>
    </x-page-header>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            @if ($budget)
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Orçamento pré-selecionado:</strong> {{ $budget->code }} -
                {{ Str::limit($budget->description, 50) }}
                <x-button type="link" :href="route('provider.services.create')" variant="outline-info" size="sm" class="ms-2" icon="x" label="Remover" />
            </div>
            @endif

            <form id="serviceForm" method="POST" action="{{ route('provider.services.store') }}">
                @csrf

                <!-- Informações Básicas -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="budget_id" class="form-label small fw-bold text-muted text-uppercase">
                                Orçamento <span class="text-danger">*</span>
                            </label>
                            <select class="form-select tom-select @error('budget_id') is-invalid @enderror" id="budget_id"
                                name="budget_id" required>
                                <option value="">Selecione um orçamento</option>
                                @if($budgets && is_iterable($budgets))
                                @foreach ($budgets as $budgetOption)
                                @if($budgetOption && isset($budgetOption->id))
                                <option value="{{ $budgetOption->id }}"
                                    {{ old('budget_id') == $budgetOption->id || ($budget && $budget->id == $budgetOption->id) ? 'selected' : '' }}>
                                    {{ $budgetOption->code }} - R$
                                    {{ number_format($budgetOption->total, 2, ',', '.') }}
                                </option>
                                @endif
                                @endforeach
                                @endif
                            </select>
                            @error('budget_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="category_id" class="form-label small fw-bold text-muted text-uppercase">
                                Categoria <span class="text-danger">*</span>
                            </label>
                            <select class="form-select tom-select @error('category_id') is-invalid @enderror"
                                id="category_id" name="category_id" required>
                                <option value="">Selecione uma categoria</option>
                                @if($categories && is_iterable($categories))
                                @foreach ($categories as $category)
                                @if($category && isset($category->id))
                                @if ($category->children->isEmpty())
                                <option value="{{ $category->id }}"
                                    {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                                @else
                                <optgroup label="{{ $category->name }}">
                                    <option value="{{ $category->id }}"
                                        {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }} (Geral)
                                    </option>
                                    @foreach ($category->children as $subcategory)
                                    @if($subcategory && isset($subcategory->id))
                                    <option value="{{ $subcategory->id }}"
                                        {{ old('category_id') == $subcategory->id ? 'selected' : '' }}>
                                        {{ $subcategory->name }}
                                    </option>
                                    @endif
                                    @endforeach
                                </optgroup>
                                @endif
                                @endif
                                @endforeach
                                @endif
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
                            <label for="code" class="form-label small fw-bold text-muted text-uppercase">
                                Código do Serviço <span class="text-muted">(gerado automaticamente)</span>
                            </label>
                            <input type="text" class="form-control" id="code" name="code"
                                value="{{ old('code', $nextCode) }}" readonly placeholder="Será gerado automaticamente">
                            <div class="form-text">Código pré-visualizado. Será confirmado ao salvar.</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="service_statuses_id" class="form-label small fw-bold text-muted text-uppercase">
                                Status <span class="text-danger">*</span>
                            </label>
                            <select class="form-select tom-select @error('service_statuses_id') is-invalid @enderror"
                                id="service_statuses_id" name="service_statuses_id" required disabled>
                                @foreach ($statusOptions as $status)
                                <option value="{{ $status->value }}" selected>
                                    {{ $status->label() }}
                                </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="service_statuses_id" value="{{ \App\Enums\ServiceStatus::DRAFT->value }}">
                            <div class="form-text">Novos serviços são criados como Rascunho por padrão.</div>
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
                            <label for="description" class="form-label small fw-bold text-muted text-uppercase">Descrição</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                rows="3" placeholder="Descreva o serviço a ser realizado...">{{ old('description') }}</textarea>
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
                            <label for="discount" class="form-label small fw-bold text-muted text-uppercase">Desconto (R$)</label>
                            <input type="text" class="form-control @error('discount') is-invalid @enderror"
                                id="discount" name="discount"
                                value="{{ \App\Helpers\CurrencyHelper::format(\App\Helpers\CurrencyHelper::unformat(old('discount', '0')), 2, false) }}"
                                inputmode="numeric" placeholder="0,00"
                                data-mask="currency">
                            @error('discount')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="total" class="form-label small fw-bold text-muted text-uppercase">Total (R$)</label>
                            <input type="text" class="form-control @error('total') is-invalid @enderror"
                                id="total" name="total"
                                value="{{ \App\Helpers\CurrencyHelper::format(\App\Helpers\CurrencyHelper::unformat(old('total', '0')), 2, false) }}"
                                inputmode="numeric" placeholder="0,00" readonly
                                data-mask="currency">
                            @error('total')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="due_date" class="form-label small fw-bold text-muted text-uppercase">Data de Vencimento</label>
                            <input type="date" class="form-control @error('due_date') is-invalid @enderror"
                                id="due_date" name="due_date"
                                value="{{ \App\Helpers\DateHelper::formatDateOrDefault(old('due_date', now()->format('Y-m-d')), 'Y-m-d', now()->format('Y-m-d')) }}"
                                required>
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
                            <i class="bi bi-box-seam me-2"></i>
                            Produtos/Serviços
                        </h5>

                        <div class="mb-3">
                            <x-button type="button" variant="success" size="sm" id="addItem" icon="plus" label="Adicionar Item" disabled />
                        </div>

                        <div id="itemsContainer">
                            <!-- Itens serão adicionados dinamicamente -->
                            @if(old('items'))
                                @foreach(old('items') as $index => $item)
                                    <div class="item-row border rounded p-3 mb-3 bg-light">
                                        <div class="row align-items-end">
                                            <div class="col-md-4">
                                                <label class="form-label">Produto/Serviço</label>
                                                <select class="form-select product-select tom-select" name="items[{{ $index }}][product_id]" required>
                                                    <option value="">Selecione um produto</option>
                                                    @foreach ($products as $product)
                                                    <option value="{{ $product->id }}" data-price="{{ $product->price }}"
                                                        {{ $item['product_id'] == $product->id ? 'selected' : '' }}>
                                                        {{ $product->name }} - R$ {{ number_format($product->price, 2, ',', '.') }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Quantidade</label>
                                                <div class="input-group">
                                                    <button type="button" class="btn btn-outline-secondary btn-sm quantity-decrement">-</button>
                                                    <input type="number" class="form-control quantity-input" name="items[{{ $index }}][quantity]"
                                                        value="{{ $item['quantity'] ?? 1 }}" min="0" step="1" inputmode="numeric" required>
                                                    <button type="button" class="btn btn-outline-secondary btn-sm quantity-increment">+</button>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Valor Unit.</label>
                                                <input type="text" inputmode="numeric" class="form-control unit-value currency-brl" name="items[{{ $index }}][unit_value]"
                                                    value="{{ \App\Helpers\CurrencyHelper::format(\App\Helpers\CurrencyHelper::unformat($item['unit_value'] ?? 0), 2, false) }}" placeholder="0,00" required readonly data-mask="currency">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Total</label>
                                                <input type="text" inputmode="numeric" class="form-control item-total currency-brl" name="items[{{ $index }}][total]"
                                                    value="{{ \App\Helpers\CurrencyHelper::format(\App\Helpers\CurrencyHelper::unformat($item['total'] ?? 0), 2, false) }}" placeholder="0,00" readonly data-mask="currency">
                                            </div>
                                            <div class="col-md-2">
                                                <input type="hidden" name="items[{{ $index }}][action]" value="create">
                                                <x-button type="button" variant="outline-danger" size="sm" class="remove-item w-100 mt-2 mt-md-0" icon="trash" label="Excluir" />
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                        <div id="emptyState" class="text-center py-4 text-muted" style="{{ old('items') ? 'display: none;' : '' }}">
                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                            <p class="mb-0 mt-2">Nenhum item adicionado</p>
                            <small>Clique em "Adicionar Item" para começar</small>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <div>
                        <x-back-button index-route="provider.services.index" label="Cancelar" />
                    </div>
                    <x-button type="submit" icon="check-circle" label="Criar" />
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Template para novos itens -->
<template id="itemTemplate">
    <div class="item-row border rounded p-3 mb-3 bg-light">
        <div class="row align-items-end">
            <div class="col-md-4">
                <label class="form-label">Produto/Serviço</label>
                <select class="form-select product-select tom-select" name="items[__INDEX__][product_id]" required>
                    <option value="">Selecione um produto</option>
                    @foreach ($products as $product)
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
                    <input type="number" class="form-control quantity-input" name="items[__INDEX__][quantity]"
                        value="1" min="0" step="1" inputmode="numeric" required>
                    <button type="button" class="btn btn-outline-secondary btn-sm quantity-increment">+</button>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label">Valor Unit.</label>
                <input type="text" inputmode="numeric" class="form-control unit-value" name="items[__INDEX__][unit_value]"
                    placeholder="R$ 0,00" required readonly data-mask="currency">
            </div>
            <div class="col-md-2">
                <label class="form-label">Total</label>
                <input type="text" inputmode="numeric" class="form-control item-total" name="items[__INDEX__][total]"
                    placeholder="R$ 0,00" readonly data-mask="currency">
            </div>
            <div class="col-md-2">
                <input type="hidden" name="items[__INDEX__][action]" value="create">
                <x-button type="button" variant="outline-danger" size="sm" class="remove-item w-100 mt-2 mt-md-0" icon="trash" label="Excluir" />
            </div>
        </div>
    </div>
</template>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let itemIndex = {{ old('items') ? count(old('items')) : 0 }};

        // Adicionar novo item
        document.getElementById('addItem').addEventListener('click', function() {
            addItem();
        });

        // Inicializar listeners para itens que vieram do "old"
        @if(old('items'))
            document.querySelectorAll('#itemsContainer .item-row').forEach((row, index) => {
                addItemListeners(row);
            });
            updateFormTotal();
        @endif

        function addItem() {
            const template = document.getElementById('itemTemplate');
            const container = document.getElementById('itemsContainer');
            const emptyState = document.getElementById('emptyState');
            const clone = template.content.cloneNode(true);

            // Atualizar índices
            const inputs = clone.querySelectorAll('[name]');
            inputs.forEach(input => {
                input.name = input.name.replace('__INDEX__', itemIndex);
            });

            container.appendChild(clone);

            // Inicializar TomSelect no novo item
            const newSelect = container.querySelector(`.item-row:last-child .product-select`);
            if (newSelect && window.initTomSelect) {
                window.initTomSelect(newSelect);
            }

            // Inicializar máscaras no novo item
            if (window.VanillaMask) {
                const newRow = container.querySelector('.item-row:last-child');
                newRow.querySelectorAll('[data-mask="currency"]').forEach(el => {
                    new VanillaMask(el, 'currency');
                });
            }

            itemIndex++;

            // Ocultar empty state
            if (emptyState) {
                emptyState.style.display = 'none';
            }

            // Adicionar listeners para cálculos
            addItemListeners(container.querySelector('.item-row:last-child'));
        }

        function addItemListeners(itemRow) {
            if (itemRow) {
                const productSelect = itemRow.querySelector('.product-select');
                const quantityInput = itemRow.querySelector('.quantity-input');
                const unitValueInput = itemRow.querySelector('.unit-value');
                const totalInput = itemRow.querySelector('.item-total');
                const removeButton = itemRow.querySelector('.remove-item');
                const incBtn = itemRow.querySelector('.quantity-increment');
                const decBtn = itemRow.querySelector('.quantity-decrement');

                // Preencher valor unitário quando produto for selecionado
                productSelect.addEventListener('change', function() {
                    const price = this.options[this.selectedIndex].dataset.price;
                    const num = parseFloat(price || '0');
                    unitValueInput.value = window.formatCurrencyBRL ? window.formatCurrencyBRL(num) : (
                        num.toFixed ? num.toFixed(2).replace('.', ',') : num);
                    calculateTotal();
                });

                // Calcular total
                quantityInput.addEventListener('input', calculateTotal);

                // Remover o listener manual de unitValueInput pois o VanillaMask já cuida da formatação
                // e o campo é readonly por padrão, sendo preenchido pelo productSelect change.

                // Bloquear entrada não numérica na quantidade
                quantityInput.addEventListener('keypress', function(e) {
                    if (!/[0-9]/.test(e.key)) {
                        e.preventDefault();
                    }
                });

                // Incremento/decremento
                if (incBtn) {
                    incBtn.addEventListener('click', function() {
                        const current = parseInt(quantityInput.value || '1', 10);
                        quantityInput.value = (isNaN(current) ? 1 : current + 1);
                        calculateTotal();
                    });
                }
                if (decBtn) {
                    decBtn.addEventListener('click', function() {
                        const current = parseInt(quantityInput.value || '1', 10);
                        const next = (isNaN(current) ? 0 : Math.max(0, current - 1));
                        quantityInput.value = next;
                        calculateTotal();
                    });
                }

                function calculateTotal() {
                    const quantity = parseFloat(quantityInput.value) || 0;
                    const unitValue = window.parseCurrencyBRLToNumber ? window.parseCurrencyBRLToNumber(
                        unitValueInput.value) : 0;
                    const total = quantity * unitValue;
                    totalInput.value = window.formatCurrencyBRL ? window.formatCurrencyBRL(total) : total
                        .toFixed(2).replace('.', ',');
                    updateFormTotal();
                }

                // Remover item
                removeButton.addEventListener('click', function() {
                    if (!confirm('Deseja excluir este item?')) {
                        return;
                    }
                    itemRow.remove();
                    updateFormTotal();

                    // Mostrar empty state se não houver mais itens
                    const container = document.getElementById('itemsContainer');
                    const emptyState = document.getElementById('emptyState');
                    if (container && emptyState && container.children.length === 0) {
                        emptyState.style.display = 'block';
                    }
                });

                // Inicializar máscaras no novo item
                if (window.VanillaMask) {
                    new VanillaMask(unitValueInput, 'currency');
                    new VanillaMask(totalInput, 'currency');
                }

                // Inicializar cálculos para itens existentes
                calculateTotal();
            }
        }

        // funções de máscara BRL providas pela VanillaMask

        // Máscara para desconto (moeda BRL)
        const discountInput = document.getElementById('discount');
        if (discountInput) {
            discountInput.addEventListener('input', updateFormTotal);
        }

        function updateFormTotal() {
            const totals = document.querySelectorAll('.item-total');
            let sum = 0;
            totals.forEach(input => {
                const val = window.parseCurrencyBRLToNumber ? window.parseCurrencyBRLToNumber(input
                    .value) : 0;
                sum += val;
            });
            const discountEl = document.getElementById('discount');
            let discountNum = window.parseCurrencyBRLToNumber ? window.parseCurrencyBRLToNumber(discountEl ?
                discountEl.value : 0) : 0;
            if (discountNum > sum) {
                discountNum = sum;
                if (discountEl && window.formatCurrencyBRL) {
                    discountEl.value = window.formatCurrencyBRL(sum);
                }
            }
            const finalTotal = Math.max(0, sum - discountNum);
            const totalEl = document.getElementById('total');
            if (totalEl) {
                totalEl.value = window.formatCurrencyBRL ? window.formatCurrencyBRL(finalTotal) : finalTotal
                    .toFixed(2).replace('.', ',');
            }
        }

        // Calcular total quando desconto mudar
        document.getElementById('discount').addEventListener('input', updateFormTotal);

        // Habilitar/desabilitar botão Adicionar Item baseado no orçamento
        const budgetSelect = document.getElementById('budget_id');
        const addItemBtn = document.getElementById('addItem');

        budgetSelect.addEventListener('change', function() {
            if (this.value) {
                addItemBtn.disabled = false;
            } else {
                addItemBtn.disabled = true;
            }
        });

        // Auto-seleção de orçamento se fornecido
        const budgetId = "{{ optional($budget)->id ?? '' }}";
        if (budgetId) {
            document.getElementById('budget_id').value = budgetId;
            if (typeof addItemBtn !== 'undefined') {
                addItemBtn.disabled = false;
            }
        }

        // NÃO adicionar item automaticamente - deixar empty state visível

        if (window.VanillaMask) {
            new VanillaMask('discount', 'currency');
            new VanillaMask('total', 'currency');
            document.querySelectorAll('.unit-value').forEach(el => new VanillaMask(el, 'currency'));
            document.querySelectorAll('.item-total').forEach(el => new VanillaMask(el, 'currency'));
        }

        document.getElementById('serviceForm').addEventListener('submit', function(e) {
            let valid = true;
            const budget = document.getElementById('budget_id');
            const category = document.getElementById('category_id');
            const status = document.getElementById('service_statuses_id');
            const dueDate = document.getElementById('due_date');
            if (!budget.value) {
                window.easyAlert.error('Selecione um orçamento');
                valid = false;
            }
            if (!category.value) {
                window.easyAlert.error('Selecione uma categoria');
                valid = false;
            }
            if (!status.value) {
                window.easyAlert.error('Selecione um status');
                valid = false;
            }
            if (dueDate.value) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const inputDate = new Date(dueDate.value + 'T00:00:00');
                if (inputDate < today) {
                    window.easyAlert.error('Data deve ser hoje ou posterior');
                    valid = false;
                }
            }
            const rows = document.querySelectorAll('#itemsContainer .item-row');
            if (rows.length === 0) {
                window.easyAlert.error('Adicione ao menos um item');
                valid = false;
            }
            rows.forEach(function(row) {
                const q = row.querySelector('.quantity-input');
                const u = row.querySelector('.unit-value');
                const qNum = (parseFloat(q.value) || 0);
                const uNum = window.parseCurrencyBRLToNumber ? window.parseCurrencyBRLToNumber(u
                    .value) : (parseFloat(u.value) || 0);
                if (qNum <= 0) {
                    window.easyAlert.error('Quantidade deve ser maior que zero');
                    valid = false;
                }
                if (uNum <= 0) {
                    window.easyAlert.error('Valor unitário deve ser maior que zero');
                    valid = false;
                }
            });
            // NÃO converter campos monetários para número antes de enviar
            // O backend agora usa prepareForValidation para unformat os valores BR
            updateFormTotal();
            if (!valid) {
                e.preventDefault();
            }
        });
    });
</script>
@endpush
@endsection
