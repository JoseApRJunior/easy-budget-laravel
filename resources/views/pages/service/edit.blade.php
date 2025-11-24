@extends('layouts.app')

@section('title', 'Editar Serviço')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-edit me-2"></i>
                        Editar Serviço
                    </h3>
                    <div class="card-actions">
                        <a href="{{ route('provider.services.show', $service->code) }}" class="btn btn-info">
                            <i class="fas fa-eye me-1"></i>
                            Visualizar
                        </a>
                        <a href="{{ route('provider.services.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>
                            Voltar à Lista
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Código do Serviço:</strong> {{ $service->code }}
                        <span class="ms-3"><strong>Status:</strong>
                            <span class="badge" style="background-color: {{ $service->serviceStatus->getColor() }}">
                                {{ $service->serviceStatus->getDescription() }}
                            </span>
                        </span>
                    </div>

                    <form id="serviceForm" method="POST" action="{{ route('provider.services.update', $service->code) }}">
                        @csrf
                        @method('PUT')

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
                                            required disabled>
                                        <option value="">Selecione um orçamento</option>
                                        @foreach($budgets as $budgetOption)
                                            <option value="{{ $budgetOption->id }}"
                                                    {{ (old('budget_id', $service->budget_id) == $budgetOption->id) ? 'selected' : '' }}>
                                                {{ $budgetOption->code }} - R$ {{ number_format($budgetOption->total, 2, ',', '.') }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="budget_id" value="{{ old('budget_id', $service->budget_id) }}">
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
                                            <option value="{{ $category->id }}" {{ old('category_id', $service->category_id) == $category->id ? 'selected' : '' }}>
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
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">
                                        Status <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('status') is-invalid @enderror"
                                            id="status"
                                            name="status"
                                            required>
                                        @foreach($statusOptions as $status)
                                            <option value="{{ $status->value }}"
                                                    {{ old('status', $service->status->value ?? $service->serviceStatus->value) == $status->value ? 'selected' : '' }}>
                                                {{ $status->getDescription() }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('status')
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
                                              placeholder="Descreva o serviço a ser realizado...">{{ old('description', $service->description) }}</textarea>
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
                                           inputmode="numeric"
                                           class="form-control @error('discount') is-invalid @enderror"
                                           id="discount"
                                           name="discount"
                                           value="{{ old('discount', number_format($service->discount, 2, ',', '.')) }}"
                                           placeholder="0,00">
                                    @error('discount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="total" class="form-label">Total (R$)</label>
                                    <input type="text"
                                           inputmode="numeric"
                                           class="form-control @error('total') is-invalid @enderror"
                                           id="total"
                                           name="total"
                                           value="{{ old('total', number_format($service->total, 2, ',', '.')) }}"
                                           placeholder="0,00">
                                    @error('total')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="due_date" class="form-label">Data de Vencimento</label>
                                    <input type="date"
                                           class="form-control @error('due_date') is-invalid @enderror"
                                           id="due_date"
                                           name="due_date"
                                           value="{{ old('due_date', $service->due_date?->format('Y-m-d')) }}">
                                    @error('due_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <script>
                          document.addEventListener('DOMContentLoaded', function(){
                            try {
                              if (window.VanillaMask) {
                                new VanillaMask('discount', 'currency');
                                new VanillaMask('total', 'currency');
                                document.querySelectorAll('.unit-value').forEach(function(el){ new VanillaMask(el, 'currency'); });
                                document.querySelectorAll('.item-total').forEach(function(el){ new VanillaMask(el, 'currency'); });
                              }
                              var form = document.querySelector('form');
                              if (form) {
                                form.addEventListener('submit', function(){
                                  var fields = [];
                                  var discountEl = document.querySelector('[name="discount"]');
                                  var totalEl = document.querySelector('[name="total"]');
                                  if (discountEl) fields.push(discountEl);
                                  if (totalEl) fields.push(totalEl);
                                  document.querySelectorAll('.unit-value').forEach(function(el){ fields.push(el); });
                                  document.querySelectorAll('.item-total').forEach(function(el){ fields.push(el); });
                                  fields.forEach(function(input){
                                    if (window.parseCurrencyBRLToNumber) {
                                      var num = window.parseCurrencyBRLToNumber(input.value);
                                      input.value = Number.isFinite(num) ? num.toFixed(2) : '0.00';
                                    }
                                  });
                                });
                              }
                            } catch (e) {}
                          });
                        </script>

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
                                    <!-- Itens enviados anteriormente (old) ou itens existentes -->
                                    @php($oldItems = old('items'))
                                    @if(is_array($oldItems) && count($oldItems) > 0)
                                        @foreach($oldItems as $index => $old)
                                            <div class="item-row border rounded p-3 mb-3 bg-light">
                                                <div class="row align-items-end">
                                                    <div class="col-md-4">
                                                        <label class="form-label">Produto/Serviço</label>
                                                        <select class="form-select product-select" name="items[{{ $index }}][product_id]" required>
                                                            <option value="">Selecione um produto</option>
                                                            @foreach($products as $product)
                                                                <option value="{{ $product->id }}"
                                                                        data-price="{{ $product->price }}"
                                                                        {{ (string)($old['product_id'] ?? '') === (string)$product->id ? 'selected' : '' }}>
                                                                    {{ $product->name }} - R$ {{ number_format($product->price, 2, ',', '.') }}
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
                                                                   name="items[{{ $index }}][quantity]"
                                                                   value="{{ $old['quantity'] ?? 1 }}"
                                                                   min="1"
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
                                                               name="items[{{ $index }}][unit_value]"
                                                               value="{{ $old['unit_value'] ?? '' }}"
                                                               required readonly>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label">Total</label>
                                                        <input type="text"
                                                               inputmode="numeric"
                                                               class="form-control item-total"
                                                               name="items[{{ $index }}][total]"
                                                               value="{{ $old['total'] ?? '' }}"
                                                               readonly>
                                                    </div>
                                                    <div class="col-md-2">
                                                        @if(!empty($old['id']))
                                                            <input type="hidden" name="items[{{ $index }}][id]" value="{{ $old['id'] }}">
                                                        @endif
                                                        <button type="button" class="btn btn-outline-danger btn-sm remove-item w-100 d-flex align-items-center justify-content-center gap-2" aria-label="Excluir">
                                                            <i class="fas fa-minus-circle text-danger"></i>
                                                            <span>Excluir</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        @foreach($service->serviceItems as $index => $item)
                                            <div class="item-row border rounded p-3 mb-3 bg-light">
                                                <div class="row align-items-end">
                                                    <div class="col-md-4">
                                                        <label class="form-label">Produto/Serviço</label>
                                                        <select class="form-select product-select" name="items[{{ $index }}][product_id]" required>
                                                            <option value="">Selecione um produto</option>
                                                            @foreach($products as $product)
                                                                <option value="{{ $product->id }}"
                                                                        data-price="{{ $product->price }}"
                                                                        {{ $item->product_id == $product->id ? 'selected' : '' }}>
                                                                    {{ $product->name }} - R$ {{ number_format($product->price, 2, ',', '.') }}
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
                                                                   name="items[{{ $index }}][quantity]"
                                                                   value="{{ $item->quantity }}"
                                                                   min="1"
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
                                                               name="items[{{ $index }}][unit_value]"
                                                               value="{{ number_format($item->unit_value, 2, ',', '.') }}"
                                                               required readonly>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label">Total</label>
                                                        <input type="text"
                                                               inputmode="numeric"
                                                               class="form-control item-total"
                                                               name="items[{{ $index }}][total]"
                                                               value="{{ number_format($item->total, 2, ',', '.') }}"
                                                               readonly>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                                        <input type="hidden" name="items[{{ $index }}][action]" value="update">
                                                        <button type="button" class="btn btn-outline-danger btn-sm remove-item w-100 d-flex align-items-center justify-content-center gap-2" aria-label="Excluir">
                                                            <i class="fas fa-minus-circle text-danger"></i>
                                                            <span>Excluir</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Botões de Ação -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('provider.services.show', $service->code) }}" class="btn btn-secondary">
                                        <i class="fas fa-times me-1"></i>
                                        Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>
                                        Atualizar Serviço
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
                    <button type="button" class="btn btn-outline-secondary btn-sm quantity-decrement" aria-label="Diminuir">-</button>
                    <input type="number"
                           class="form-control quantity-input"
                           name="items[__INDEX__][quantity]"
                           value="1"
                           min="1"
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
                <button type="button" class="btn btn-outline-danger btn-sm remove-item w-100 d-flex align-items-center justify-content-center gap-2" aria-label="Excluir">
                    <i class="fas fa-minus-circle text-danger"></i>
                    <span>Excluir</span>
                </button>
            </div>
        </div>
    </div>
</template>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let itemIndex = {{ is_array(old('items')) ? count(old('items')) : $service->serviceItems->count() }};

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
                  unitValue = parseFloat((unitValueInput.value || '0').replace(/\./g,'').replace(',','.')) || 0;
                }
                var t = (quantity * unitValue).toFixed(2);
                totalInput.value = window.formatCurrencyBRL ? window.formatCurrencyBRL(t) : t.replace('.', ',');
                updateFormTotal();
            }

            // Remover item
            removeButton.addEventListener('click', function() {
                const idInput = lastItem.querySelector('input[name^="items"][name$="[id]"]');
                const actionInput = lastItem.querySelector('input[name^="items"][name$="[action]"]');
                if (idInput) {
                  if (actionInput) actionInput.value = 'delete';
                  lastItem.style.opacity = '0.5';
                  lastItem.querySelectorAll('input, select, button').forEach(el => {
                    if (!el.name.endsWith('[id]') && !el.name.endsWith('[action]')) {
                      el.disabled = true;
                    }
                  });
                } else {
                  if (document.querySelectorAll('#itemsContainer .item-row').length > 1) {
                    lastItem.remove();
                    updateFormTotal();
                  }
                }
            });

            // Inicializar cálculos para itens existentes
            calculateTotal();
        }
    }

    function updateFormTotal() {
        const totals = document.querySelectorAll('.item-total');
        let sum = 0;
        totals.forEach(input => {
            var v = 0;
            if (window.parseCurrencyBRLToNumber) {
              v = window.parseCurrencyBRLToNumber(input.value) || 0;
            } else {
              v = parseFloat((input.value || '0').replace(/\./g,'').replace(',','.')) || 0;
            }
            sum += v;
        });

        var discount = 0;
        var dEl = document.getElementById('discount');
        if (dEl) {
          discount = window.parseCurrencyBRLToNumber ? (window.parseCurrencyBRLToNumber(dEl.value) || 0) : (parseFloat((dEl.value || '0').replace(/\./g,'').replace(',','.')) || 0);
        }
        const finalTotal = sum - discount;

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

    // Delegação para botões de quantidade (+/-) em itens dinâmicos
    const itemsContainer = document.getElementById('itemsContainer');
    if (itemsContainer) {
      itemsContainer.addEventListener('click', function(e){
        const btn = e.target.closest('.quantity-increment, .quantity-decrement');
        if (!btn) return;
        const itemRow = btn.closest('.item-row');
        if (!itemRow) return;
        const quantityInput = itemRow.querySelector('.quantity-input');
        const unitValueInput = itemRow.querySelector('.unit-value');
        const totalInput = itemRow.querySelector('.item-total');
        if (!quantityInput || !unitValueInput || !totalInput) return;

        const current = parseInt(quantityInput.value || '1', 10);
        const isInc = btn.classList.contains('quantity-increment');
        quantityInput.value = isInc ? (isNaN(current) ? 1 : current + 1) : (isNaN(current) ? 1 : Math.max(1, current - 1));

        const qty = parseFloat(quantityInput.value) || 0;
        let unitValue = 0;
        if (window.parseCurrencyBRLToNumber) {
          unitValue = window.parseCurrencyBRLToNumber(unitValueInput.value) || 0;
        } else {
          unitValue = parseFloat((unitValueInput.value || '0').replace(/\./g,'').replace(',','.')) || 0;
        }
        const t = (qty * unitValue).toFixed(2);
        totalInput.value = window.formatCurrencyBRL ? window.formatCurrencyBRL(t) : t.replace('.', ',');
        updateFormTotal();
      });
    }

    function addItemListenersForItem(item) {
        const productSelect = item.querySelector('.product-select');
        const quantityInput = item.querySelector('.quantity-input');
        const unitValueInput = item.querySelector('.unit-value');
        const totalInput = item.querySelector('.item-total');
        const removeButton = item.querySelector('.remove-item');
        const incBtn = item.querySelector('.quantity-increment');
        const decBtn = item.querySelector('.quantity-decrement');

        if (productSelect) {
            productSelect.addEventListener('change', function() {
                const price = this.options[this.selectedIndex]?.dataset.price;
                if (window.formatCurrencyBRL) {
                  unitValueInput.value = window.formatCurrencyBRL(price || '0');
                } else {
                  var v = parseFloat(price || '0');
                  unitValueInput.value = isFinite(v) ? (v.toFixed(2)).replace('.', ',') : '';
                }
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
                    unitValue = parseFloat((unitValueInput.value || '0').replace(/\./g,'').replace(',','.')) || 0;
                }
                var t = (quantity * unitValue).toFixed(2);
                totalInput.value = window.formatCurrencyBRL ? window.formatCurrencyBRL(t) : t.replace('.', ',');
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
                  const next = (isNaN(current) ? 1 : Math.max(1, current - 1));
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
                  if (actionInput) actionInput.value = 'delete';
                  item.style.opacity = '0.5';
                  item.querySelectorAll('input, select, button').forEach(el => {
                    if (!el.name.endsWith('[id]') && !el.name.endsWith('[action]')) {
                      el.disabled = true;
                    }
                  });
                  updateFormTotal();
                } else {
                  if (document.querySelectorAll('#itemsContainer .item-row').length > 1) {
                    item.remove();
                    updateFormTotal();
                  }
                }
            });
        }
    }

    // Calcular total inicial
    updateFormTotal();
});
</script>
@endpush

@push('styles')
<style>
  .remove-item.btn {
    transition: transform .05s ease-in-out, box-shadow .2s ease;
  }
  .remove-item.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 12px rgba(0,0,0,.08);
  }
  @media (prefers-color-scheme: dark) {
    .remove-item.btn.btn-outline-danger {
      color: #f28b82;
      border-color: #f28b82;
    }
    .remove-item.btn.btn-outline-danger:hover {
      background-color: rgba(220,53,69,.15);
    }
  }
</style>
@endpush
@endsection
