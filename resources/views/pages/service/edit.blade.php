@extends('layouts.app')

@section('title', 'Editar Serviço')

@section('content')
<x-layout.page-container>
    <x-layout.page-header
        title="Editar Serviço"
        icon="tools"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Serviços' => route('provider.services.dashboard'),
            $service->code => route('provider.services.show', $service->code),
            'Editar' => '#'
        ]">
        <p class="text-muted mb-0">Atualize as informações do serviço {{ $service->code }}</p>
    </x-layout.page-header>
    <x-layout.grid-row>
        <x-layout.grid-col size="col-12">
            <x-resource.resource-list-card
                title="Dados do Serviço"
                icon="tools"
                padding="p-4"
            >
                <x-ui.alert type="info">
                    <strong>Código do Serviço:</strong> {{ $service->code }}
                    <span class="ms-3"><strong>Status:</strong>
                        <x-ui.status-badge :item="$service" />
                    </span>
                </x-ui.alert>

                <form id="serviceForm" method="POST" action="{{ route('provider.services.update', $service->code) }}">
                    @csrf
                    @method('PUT')

                    <!-- Informações Básicas -->
                    <x-layout.grid-row>
                        <x-layout.grid-col size="col-md-6">
                            <div class="mb-3">
                                <label for="budget_id" class="form-label small fw-bold text-muted text-uppercase">
                                    Orçamento <span class="text-danger">*</span>
                                </label>
                                <select class="form-select tom-select @error('budget_id') is-invalid @enderror"
                                    id="budget_id"
                                    name="budget_id"
                                    required disabled>
                                    <option value="">Selecione um orçamento</option>
                                    @foreach($budgets as $budgetOption)
                                    <option value="{{ $budgetOption->id }}"
                                        {{ (old('budget_id', $service->budget_id) == $budgetOption->id) ? 'selected' : '' }}>
                                        {{ $budgetOption->code }} - {{ \App\Helpers\CurrencyHelper::format($budgetOption->total) }}
                                    </option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="budget_id" value="{{ old('budget_id', $service->budget_id) }}">
                                @error('budget_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </x-layout.grid-col>

                        <x-layout.grid-col size="col-md-6">
                            <div class="mb-3">
                                <label for="category_id" class="form-label small fw-bold text-muted text-uppercase">
                                    Categoria <span class="text-danger">*</span>
                                </label>
                                <select class="form-select tom-select @error('category_id') is-invalid @enderror"
                                    id="category_id"
                                    name="category_id"
                                    required>
                                    <option value="">Selecione uma categoria</option>
                                    @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id', $service->category_id) == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </x-layout.grid-col>
                    </x-layout.grid-row>

                    <x-layout.grid-row>
                        <x-layout.grid-col size="col-md-6">
                            <div class="mb-3">
                                <label for="code" class="form-label small fw-bold text-muted text-uppercase">
                                    Código do Serviço
                                </label>
                                <input type="text"
                                    class="form-control"
                                    id="code"
                                    name="code"
                                    value="{{ old('code', $service->code) }}"
                                    readonly>
                                <div class="form-text">O código não pode ser alterado.</div>
                            </div>
                        </x-layout.grid-col>

                        <x-layout.grid-col size="col-md-6">
                            <div class="mb-3">
                                <label for="service_statuses_id" class="form-label small fw-bold text-muted text-uppercase">
                                    Status <span class="text-danger">*</span>
                                </label>
                                <select class="form-select tom-select @error('status') is-invalid @enderror"
                                    id="status"
                                    name="status"
                                    required>
                                    @foreach($statusOptions as $status)
                                    <option value="{{ $status->value }}"
                                        {{ old('status', $service->status->value) == $status->value ? 'selected' : '' }}>
                                        {{ $status->getDescription() }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </x-layout.grid-col>
                    </x-layout.grid-row>

                    <!-- Descrição e Detalhes -->
                    <x-layout.grid-row>
                        <x-layout.grid-col size="col-12">
                            <div class="mb-3">
                                <label for="description" class="form-label small fw-bold text-muted text-uppercase">Descrição</label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                    id="description"
                                    name="description"
                                    rows="3"
                                    placeholder="Descreva o serviço a ser realizado...">{{ old('description', $service->description) }}</textarea>
                                @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </x-layout.grid-col>
                    </x-layout.grid-row>

                    <!-- Valores e Datas -->
                    <x-layout.grid-row>
                        <x-layout.grid-col size="col-md-4">
                            <div class="mb-3">
                                <label for="discount" class="form-label small fw-bold text-muted text-uppercase">Desconto (R$)</label>
                                <input type="text"
                                    inputmode="numeric"
                                    class="form-control currency-brl @error('discount') is-invalid @enderror"
                                    id="discount"
                                    name="discount"
                                    value="{{ \App\Helpers\CurrencyHelper::format(\App\Helpers\CurrencyHelper::unformat(old('discount', $service->discount)), 2, false) }}"
                                    placeholder="0,00">
                                @error('discount')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </x-layout.grid-col>

                        <x-layout.grid-col size="col-md-4">
                            <div class="mb-3">
                                <label for="total" class="form-label small fw-bold text-muted text-uppercase">Total (R$)</label>
                                <input type="text"
                                    inputmode="numeric"
                                    class="form-control @error('total') is-invalid @enderror"
                                    id="total"
                                    name="total"
                                    value="{{ \App\Helpers\CurrencyHelper::format(\App\Helpers\CurrencyHelper::unformat(old('total', $service->total)), 2, false) }}"
                                    placeholder="0,00">
                                @error('total')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </x-layout.grid-col>

                        <x-layout.grid-col size="col-md-4">
                            <div class="mb-3">
                                <label for="due_date" class="form-label small fw-bold text-muted text-uppercase">Data de Vencimento</label>
                                <input type="date"
                                    class="form-control @error('due_date') is-invalid @enderror"
                                    id="due_date"
                                    name="due_date"
                                    min="{{ date('Y-m-d') }}"
                                    value="{{ \App\Helpers\DateHelper::formatDateOrDefault(old('due_date', $service->due_date?->format('Y-m-d')), 'Y-m-d', $service->due_date?->format('Y-m-d') ?? '') }}">
                                @error('due_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </x-layout.grid-col>
                    </x-layout.grid-row>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                try {
                                    if (window.VanillaMask) {
                                        new VanillaMask('discount', 'currency');
                                        new VanillaMask('total', 'currency');
                                        document.querySelectorAll('.unit-value').forEach(function(el) {
                                            new VanillaMask(el, 'currency');
                                        });
                                        document.querySelectorAll('.item-total').forEach(function(el) {
                                            new VanillaMask(el, 'currency');
                                        });
                                    }
                                } catch (e) {}
                            });
                        </script>

                        <!-- Produtos/Serviços -->
                        <x-layout.grid-row>
                            <x-layout.grid-col size="col-12">
                                <h5 class="mb-3">
                                    <i class="bi bi-box-seam me-2" aria-hidden="true"></i>
                                    Produtos/Serviços
                                </h5>

                                <div class="mb-3">
                                    <x-ui.button type="button" variant="success" size="sm" id="addItem" icon="plus" label="Adicionar Item" />
                                </div>

                                <div id="itemsContainer">
                                    <!-- Itens enviados anteriormente (old) ou itens existentes -->
                                    @php($oldItems = old('items'))
                                    @if(is_array($oldItems) && count($oldItems) > 0)
                                    @foreach($oldItems as $index => $old)
                                    <div class="item-row border rounded p-3 mb-3 bg-body-secondary">
                                        <x-layout.grid-row class="align-items-end">
                                            <x-layout.grid-col size="col-md-4">
                                                <label class="form-label">Produto/Serviço</label>
                                                <select class="form-select product-select tom-select" name="items[{{ $index }}][product_id]" required>
                                                    <option value="">Selecione um produto</option>
                                                    @foreach($products as $product)
                                                    <option value="{{ $product->id }}"
                                                        data-price="{{ $product->price }}"
                                                        {{ (string)($old['product_id'] ?? '') === (string)$product->id ? 'selected' : '' }}>
                                                        {{ $product->name }} - {{ \App\Helpers\CurrencyHelper::format($product->price) }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                            </x-layout.grid-col>
                                            <x-layout.grid-col size="col-md-2">
                                                <label class="form-label">Quantidade</label>
                                                <div class="input-group">
                                                    <button type="button" class="btn btn-outline-secondary btn-sm quantity-decrement" aria-label="Diminuir">-</button>
                                                    <input type="number"
                                                        class="form-control quantity-input"
                                                        name="items[{{ $index }}][quantity]"
                                                        value="{{ $old['quantity'] ?? 1 }}"
                                                        min="0"
                                                        step="1"
                                                        inputmode="numeric"
                                                        required>
                                                    <button type="button" class="btn btn-outline-secondary btn-sm quantity-increment" aria-label="Aumentar">+</button>
                                                </div>
                                            </x-layout.grid-col>
                                            <x-layout.grid-col size="col-md-2">
                                                <label class="form-label">Valor Unit.</label>
                                                <input type="text"
                                                    inputmode="numeric"
                                                    class="form-control unit-value currency-brl"
                                                    name="items[{{ $index }}][unit_value]"
                                                    value="{{ \App\Helpers\CurrencyHelper::format(\App\Helpers\CurrencyHelper::unformat($old['unit_value'] ?? 0), 2, false) }}"
                                                    required readonly>
                                            </x-layout.grid-col>
                                            <x-layout.grid-col size="col-md-2">
                                                <label class="form-label">Total</label>
                                                <input type="text"
                                                    inputmode="numeric"
                                                    class="form-control item-total currency-brl"
                                                    name="items[{{ $index }}][total]"
                                                    value="{{ \App\Helpers\CurrencyHelper::format(\App\Helpers\CurrencyHelper::unformat($old['total'] ?? 0), 2, false) }}"
                                                    readonly>
                                            </x-layout.grid-col>
                                            <x-layout.grid-col size="col-md-2">
                                                @if(!empty($old['id']))
                                                <input type="hidden" name="items[{{ $index }}][id]" value="{{ $old['id'] }}">
                                                <input type="hidden" name="items[{{ $index }}][action]" value="update">
                                                @else
                                                <input type="hidden" name="items[{{ $index }}][action]" value="create">
                                                @endif
                                                <x-ui.button type="button" variant="outline-danger" size="sm" class="remove-item w-100 mt-2 mt-md-0" icon="trash" label="Excluir" />
                                                <x-ui.button type="button" variant="outline-secondary" size="sm" class="undo-item w-100 d-none" icon="arrow-counterclockwise" label="Desfazer" />
                                            </x-layout.grid-col>
                                            <x-layout.grid-col size="col-12" class="mt-2">
                                                <span class="badge bg-warning text-dark item-deleted-badge d-none"><i class="bi bi-exclamation-triangle me-1"></i>Marcado para exclusão</span>
                                            </x-layout.grid-col>
                                        </x-layout.grid-row>
                                    </div>
                                    @endforeach
                                    @else
                                    @foreach($service->serviceItems as $index => $item)
                                    <div class="item-row border rounded p-3 mb-3 bg-body-secondary">
                                        <x-layout.grid-row class="align-items-end">
                                            <x-layout.grid-col size="col-md-4">
                                                <label class="form-label">Produto/Serviço</label>
                                                <select class="form-select product-select tom-select" name="items[{{ $index }}][product_id]" required>
                                                    <option value="">Selecione um produto</option>
                                                    @foreach($products as $product)
                                                    <option value="{{ $product->id }}"
                                                        data-price="{{ $product->price }}"
                                                        {{ $item->product_id == $product->id ? 'selected' : '' }}>
                                                        {{ $product->name }} - {{ \App\Helpers\CurrencyHelper::format($product->price) }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                            </x-layout.grid-col>
                                            <x-layout.grid-col size="col-md-2">
                                                <label class="form-label">Quantidade</label>
                                                <div class="input-group">
                                                    <button type="button" class="btn btn-outline-secondary btn-sm quantity-decrement" aria-label="Diminuir">-</button>
                                                    <input type="number"
                                                        class="form-control quantity-input"
                                                        name="items[{{ $index }}][quantity]"
                                                        value="{{ $item->quantity }}"
                                                        min="0"
                                                        step="1"
                                                        inputmode="numeric"
                                                        required>
                                                    <button type="button" class="btn btn-outline-secondary btn-sm quantity-increment" aria-label="Aumentar">+</button>
                                                </div>
                                            </x-layout.grid-col>
                                            <x-layout.grid-col size="col-md-2">
                                                <label class="form-label">Valor Unit.</label>
                                                <input type="text"
                                                    inputmode="numeric"
                                                    class="form-control unit-value currency-brl"
                                                    name="items[{{ $index }}][unit_value]"
                                                    value="{{ \App\Helpers\CurrencyHelper::format($item->unit_value, 2, false) }}"
                                                    required readonly>
                                            </x-layout.grid-col>
                                            <x-layout.grid-col size="col-md-2">
                                                <label class="form-label">Total</label>
                                                <input type="text"
                                                    inputmode="numeric"
                                                    class="form-control item-total currency-brl"
                                                    name="items[{{ $index }}][total]"
                                                    value="{{ \App\Helpers\CurrencyHelper::format($item->total, 2, false) }}"
                                                    readonly>
                                            </x-layout.grid-col>
                                            <x-layout.grid-col size="col-md-2">
                                                <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                                <input type="hidden" name="items[{{ $index }}][action]" value="update">
                                                <x-ui.button type="button" variant="outline-danger" size="sm" class="remove-item w-100 mt-2 mt-md-0" icon="trash" label="Excluir" />
                                                <x-ui.button type="button" variant="outline-secondary" size="sm" class="undo-item w-100 d-none" icon="arrow-counterclockwise" label="Desfazer" />
                                            </x-layout.grid-col>
                                            <x-layout.grid-col size="col-12" class="mt-2">
                                                <span class="badge bg-warning text-dark item-deleted-badge d-none"><i class="bi bi-exclamation-triangle me-1"></i>Marcado para exclusão</span>
                                            </x-layout.grid-col>
                                        </x-layout.grid-row>
                                    </div>
                                    @endforeach
                                    @endif
                                </div>
                            </x-layout.grid-col>
                        </x-layout.grid-row>

                        <x-layout.grid-row class="mt-4">
                            <x-layout.grid-col size="col-12 text-end">
                                <x-ui.button type="link" :href="route('provider.services.show', $service->code)" variant="outline-secondary" icon="x" label="Cancelar" />
                                <x-ui.button type="submit" variant="primary" icon="check-lg" label="Salvar Alterações" />
                            </x-layout.grid-col>
                        </x-layout.grid-row>
                    </form>
                </x-resource.resource-list-card>
            </x-layout.grid-col>
        </x-layout.grid-row>
    </x-layout.page-container>

<!-- Template para novos itens -->
<template id="itemTemplate">
    <div class="item-row border rounded p-3 mb-3 bg-body-secondary">
        <div class="row align-items-end">
            <div class="col-md-4">
                <label class="form-label">Produto/Serviço</label>
                <select class="form-select product-select" name="items[__INDEX__][product_id]" required>
                    <option value="">Selecione um produto</option>
                    @foreach($products as $product)
                    <option value="{{ $product->id }}" data-price="{{ $product->price }}">
                        {{ $product->name }} - {{ \App\Helpers\CurrencyHelper::format($product->price) }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Quantidade</label>
                <div class="input-group">
                    <button type="button" class="btn btn-outline-secondary btn-sm quantity-decrement" aria-label="Diminuir">-</button>
                    <input type="number"
                        class="form-control quantity-input"
                        name="items[__INDEX__][quantity]"
                        value="1"
                        min="0"
                        step="1"
                        inputmode="numeric"
                        required>
                    <button type="button" class="btn btn-outline-secondary btn-sm quantity-increment" aria-label="Aumentar">+</button>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label">Valor Unit.</label>
                <input type="text"
                    inputmode="numeric"
                    class="form-control unit-value"
                    name="items[__INDEX__][unit_value]"
                    required readonly>
            </div>
            <div class="col-md-2">
                <label class="form-label">Total</label>
                <input type="text"
                    inputmode="numeric"
                    class="form-control item-total"
                    name="items[__INDEX__][total]"
                    readonly>
            </div>
            <div class="col-md-2">
                <input type="hidden" name="items[__INDEX__][action]" value="create">
                <x-ui.button type="button" variant="outline-danger" size="sm" class="remove-item w-100 mt-2 mt-md-0" icon="trash" label="Excluir" />
                <x-ui.button type="button" variant="outline-secondary" size="sm" class="undo-item w-100 d-none" icon="arrow-counterclockwise" label="Desfazer" />
            </div>
            <div class="col-12 mt-2">
                <span class="badge bg-warning text-dark item-deleted-badge d-none"><i class="bi bi-exclamation-triangle me-1"></i>Marcado para exclusão</span>
            </div>
        </div>
    </div>
</template>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var itemIndex = document.querySelectorAll('#itemsContainer .item-row').length;

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

            // Inicializar TomSelect no novo item
            const newSelect = container.querySelector(`.item-row:last-child .product-select`);
            if (newSelect && window.initTomSelect) {
                window.initTomSelect(newSelect);
            }

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
                const undoButton = lastItem.querySelector('.undo-item');
                const incBtn = lastItem.querySelector('.quantity-increment');
                const decBtn = lastItem.querySelector('.quantity-decrement');
                const deletedBadge = lastItem.querySelector('.item-deleted-badge');

                // Preencher valor unitário quando produto for selecionado
                productSelect.addEventListener('change', function() {
                    var opt = this.options[this.selectedIndex];
                    var price = opt ? opt.dataset.price : null;
                    if (window.formatCurrencyBRL) {
                        unitValueInput.value = window.formatCurrencyBRL(price || '0');
                    } else {
                        var v = parseFloat(price || '0');
                        unitValueInput.value = isFinite(v) ? (v.toFixed(2)).replace('.', ',') : '';
                    }
                    calculateTotal();
                });

                // Calcular total
                quantityInput.addEventListener('input', calculateTotal);
                unitValueInput.addEventListener('input', calculateTotal);

                function calculateTotal() {
                    const quantity = parseFloat(quantityInput.value) || 0;
                    var unitValue = 0;
                    if (window.parseCurrencyBRLToNumber) {
                        unitValue = window.parseCurrencyBRLToNumber(unitValueInput.value) || 0;
                    } else {
                        unitValue = parseFloat((unitValueInput.value || '0').replace(/\./g, '').replace(',', '.')) || 0;
                    }
                    var t = (quantity * unitValue).toFixed(2);
                    totalInput.value = window.formatCurrencyBRL ? window.formatCurrencyBRL(t) : t.replace('.', ',');
                    updateFormTotal();
                }

                removeButton.addEventListener('click', function() {
                    const idInput = lastItem.querySelector('input[name^="items"][name$="[id]"]');
                    const actionInput = lastItem.querySelector('input[name^="items"][name$="[action]"]');
                    if (idInput) {
                        if (actionInput && actionInput.value === 'delete') {
                            return;
                        }
                        if (actionInput) actionInput.value = 'delete';
                        lastItem.querySelectorAll('input, select').forEach(function(el) {
                            var n = el.name || '';
                            if (!n.endsWith('[id]') && !n.endsWith('[action]')) {
                                el.disabled = true;
                                el.classList.add('soft-disabled');
                            }
                        });
                        if (removeButton) removeButton.classList.add('d-none');
                        if (undoButton) undoButton.classList.remove('d-none');
                        if (deletedBadge) deletedBadge.classList.remove('d-none');
                        const dEl = document.getElementById('discount');
                        if (dEl) {
                            dEl.value = (window.formatCurrencyBRL ? window.formatCurrencyBRL('0') : '0,00');
                        }
                        updateFormTotal();
                    } else {
                        if (document.querySelectorAll('#itemsContainer .item-row').length > 1) {
                            lastItem.remove();
                            const dEl = document.getElementById('discount');
                            if (dEl) {
                                dEl.value = (window.formatCurrencyBRL ? window.formatCurrencyBRL('0') : '0,00');
                            }
                            updateFormTotal();
                        }
                    }
                });

                if (undoButton) {
                    undoButton.addEventListener('click', function() {
                        const idInput = lastItem.querySelector('input[name^="items"][name$="[id]"]');
                        const actionInput = lastItem.querySelector('input[name^="items"][name$="[action]"]');
                        if (idInput && actionInput && actionInput.value === 'delete') {
                            actionInput.value = 'update';
                            lastItem.querySelectorAll('input, select').forEach(function(el) {
                                el.disabled = false;
                                el.classList.remove('soft-disabled');
                            });
                            if (undoButton) undoButton.classList.add('d-none');
                            if (removeButton) removeButton.classList.remove('d-none');
                            if (deletedBadge) deletedBadge.classList.add('d-none');
                            updateFormTotal();
                        }
                    });
                }

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

                // Inicializar máscaras no novo item
                if (window.VanillaMask) {
                    new VanillaMask(unitValueInput, 'currency');
                    new VanillaMask(totalInput, 'currency');
                }

                // Inicializar cálculos para itens existentes
                calculateTotal();
            }
        }

        function updateFormTotal() {
            const rows = document.querySelectorAll('#itemsContainer .item-row');
            let sum = 0;
            rows.forEach(row => {
                const actionInput = row.querySelector('input[name^="items"][name$="[action]"]');
                if (actionInput && actionInput.value === 'delete') {
                    return;
                }
                const input = row.querySelector('.item-total');
                if (!input) {
                    return;
                }
                var v = 0;
                if (window.parseCurrencyBRLToNumber) {
                    v = window.parseCurrencyBRLToNumber(input.value) || 0;
                } else {
                    v = parseFloat((input.value || '0').replace(/\./g, '').replace(',', '.')) || 0;
                }
                sum += v;
            });

            var discount = 0;
            var dEl = document.getElementById('discount');
            if (dEl) {
                discount = window.parseCurrencyBRLToNumber ? (window.parseCurrencyBRLToNumber(dEl.value) || 0) : (parseFloat((dEl.value || '0').replace(/\./g, '').replace(',', '.')) || 0);
            }
            const finalTotal = Math.max(0, sum - discount);

            var totalEl = document.getElementById('total');
            if (totalEl) {
                var t = finalTotal.toFixed(2);
                totalEl.value = window.formatCurrencyBRL ? window.formatCurrencyBRL(t) : t.replace('.', ',');
            }
        }

        // Calcular total quando desconto mudar
        document.getElementById('discount').addEventListener('input', updateFormTotal);

        // Inicializar listeners para itens existentes
        const existingItems = document.querySelectorAll('#itemsContainer .item-row');
        existingItems.forEach(item => {
            addItemListenersForItem(item);
        });



        function addItemListenersForItem(item) {
            const productSelect = item.querySelector('.product-select');
            const quantityInput = item.querySelector('.quantity-input');
            const unitValueInput = item.querySelector('.unit-value');
            const totalInput = item.querySelector('.item-total');
            const removeButton = item.querySelector('.remove-item');
            const undoButton = item.querySelector('.undo-item');
            const incBtn = item.querySelector('.quantity-increment');
            const decBtn = item.querySelector('.quantity-decrement');
            const deletedBadge = item.querySelector('.item-deleted-badge');

            if (productSelect) {
                productSelect.addEventListener('change', function() {
                    var opt = this.options[this.selectedIndex];
                    var price = opt ? opt.dataset.price : null;
                    if (window.formatCurrencyBRL) {
                        unitValueInput.value = window.formatCurrencyBRL(price || '0');
                    } else {
                        var v = parseFloat(price || '0');
                        unitValueInput.value = isFinite(v) ? (v.toFixed(2)).replace('.', ',') : '0,00';
                    }

                    // Disparar evento input para atualizar máscara e cálculos
                    unitValueInput.dispatchEvent(new Event('input', { bubbles: true }));
                    calculateTotal();
                });
            }

            if (quantityInput && unitValueInput && totalInput) {
                function calculateTotal() {
                    const quantity = parseFloat(quantityInput.value) || 0;
                    var unitValue = 0;
                    if (window.parseCurrencyBRLToNumber) {
                        unitValue = window.parseCurrencyBRLToNumber(unitValueInput.value) || 0;
                    } else {
                        unitValue = parseFloat((unitValueInput.value || '0').replace(/\./g, '').replace(',', '.')) || 0;
                    }
                    var t = (quantity * unitValue).toFixed(2);
                    totalInput.value = window.formatCurrencyBRL ? window.formatCurrencyBRL(t) : t.replace('.', ',');
                    if (quantity === 0) {
                        const dEl = document.getElementById('discount');
                        if (dEl) {
                            dEl.value = (window.formatCurrencyBRL ? window.formatCurrencyBRL('0') : '0,00');
                        }
                    }
                    updateFormTotal();
                }

                quantityInput.addEventListener('input', calculateTotal);
                unitValueInput.addEventListener('input', calculateTotal);

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
            }

            if (removeButton) {
                removeButton.addEventListener('click', function() {
                    const idInput = item.querySelector('input[name^="items"][name$="[id]"]');
                    const actionInput = item.querySelector('input[name^="items"][name$="[action]"]');
                    if (idInput) {
                        if (actionInput && actionInput.value === 'delete') {
                            return;
                        }
                        if (actionInput) actionInput.value = 'delete';
                        item.querySelectorAll('input, select').forEach(function(el) {
                            var n = el.name || '';
                            if (!n.endsWith('[id]') && !n.endsWith('[action]')) {
                                el.disabled = true;
                                el.classList.add('soft-disabled');
                            }
                        });
                        if (removeButton) removeButton.classList.add('d-none');
                        if (undoButton) undoButton.classList.remove('d-none');
                        if (deletedBadge) deletedBadge.classList.remove('d-none');
                        const dEl = document.getElementById('discount');
                        if (dEl) {
                            dEl.value = (window.formatCurrencyBRL ? window.formatCurrencyBRL('0') : '0,00');
                        }
                        updateFormTotal();
                    } else {
                        if (document.querySelectorAll('#itemsContainer .item-row').length > 1) {
                            item.remove();
                            const dEl = document.getElementById('discount');
                            if (dEl) {
                                dEl.value = (window.formatCurrencyBRL ? window.formatCurrencyBRL('0') : '0,00');
                            }
                            updateFormTotal();
                        }
                    }
                });
            }

            if (undoButton) {
                undoButton.addEventListener('click', function() {
                    const idInput = item.querySelector('input[name^="items"][name$="[id]"]');
                    const actionInput = item.querySelector('input[name^="items"][name$="[action]"]');
                    if (idInput && actionInput && actionInput.value === 'delete') {
                        actionInput.value = 'update';
                        item.querySelectorAll('input, select').forEach(function(el) {
                            el.disabled = false;
                            el.classList.remove('soft-disabled');
                        });
                        if (undoButton) undoButton.classList.add('d-none');
                        if (removeButton) removeButton.classList.remove('d-none');
                        if (deletedBadge) deletedBadge.classList.add('d-none');
                        updateFormTotal();
                    }
                });
            }
        }

        // Calcular total inicial
        updateFormTotal();
    });
</script>
@endpush


@endsection
