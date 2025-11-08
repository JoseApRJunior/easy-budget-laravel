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
                        <a href="{{ route('services.show', $service->code) }}" class="btn btn-info">
                            <i class="fas fa-eye me-1"></i>
                            Visualizar
                        </a>
                        <a href="{{ route('services.index') }}" class="btn btn-secondary">
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
                                {{ $service->serviceStatus->getName() }}
                            </span>
                        </span>
                    </div>

                    <form id="serviceForm" method="POST" action="{{ route('services.update', $service->code) }}">
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
                                            required>
                                        <option value="">Selecione um orçamento</option>
                                        @foreach($budgets as $budgetOption)
                                            <option value="{{ $budgetOption->id }}"
                                                    {{ (old('budget_id', $service->budget_id) == $budgetOption->id) ? 'selected' : '' }}>
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
                                    <label for="service_statuses_id" class="form-label">
                                        Status <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('service_statuses_id') is-invalid @enderror"
                                            id="service_statuses_id"
                                            name="service_statuses_id"
                                            required>
                                        @foreach($statusOptions as $status)
                                            <option value="{{ $status->value }}"
                                                    {{ old('service_statuses_id', $service->service_statuses_id) == $status->value ? 'selected' : '' }}
                                                    {{ $service->serviceStatus->value == $status->value ? '' : '' }}>
                                                {{ $status->getName() }}
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
                                    <input type="number"
                                           class="form-control @error('discount') is-invalid @enderror"
                                           id="discount"
                                           name="discount"
                                           value="{{ old('discount', $service->discount) }}"
                                           step="0.01"
                                           min="0"
                                           placeholder="0,00">
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
                                           value="{{ old('total', $service->total) }}"
                                           step="0.01"
                                           min="0"
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
                                    <!-- Itens existentes -->
                                    @if($service->serviceItems->count() > 0)
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
                                                        <input type="number"
                                                               class="form-control quantity-input"
                                                               name="items[{{ $index }}][quantity]"
                                                               value="{{ $item->quantity }}"
                                                               min="1"
                                                               required>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label">Valor Unit.</label>
                                                        <input type="number"
                                                               class="form-control unit-value"
                                                               name="items[{{ $index }}][unit_value]"
                                                               value="{{ $item->unit_value }}"
                                                               step="0.01"
                                                               min="0"
                                                               required>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label">Total</label>
                                                        <input type="number"
                                                               class="form-control item-total"
                                                               name="items[{{ $index }}][total]"
                                                               value="{{ $item->total }}"
                                                               step="0.01"
                                                               min="0"
                                                               readonly>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                                        <button type="button" class="btn btn-danger btn-sm remove-item w-100">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <!-- Item vazio inicial -->
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Botões de Ação -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('services.show', $service->code) }}" class="btn btn-secondary">
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
                <input type="number"
                       class="form-control quantity-input"
                       name="items[__INDEX__][quantity]"
                       value="1"
                       min="1"
                       required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Valor Unit.</label>
                <input type="number"
                       class="form-control unit-value"
                       name="items[__INDEX__][unit_value]"
                       step="0.01"
                       min="0"
                       required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Total</label>
                <input type="number"
                       class="form-control item-total"
                       name="items[__INDEX__][total]"
                       step="0.01"
                       min="0"
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
    let itemIndex = {{ $service->serviceItems->count() }};

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

            // Preencher valor unitário quando produto for selecionado
            productSelect.addEventListener('change', function() {
                const price = this.options[this.selectedIndex].dataset.price;
                unitValueInput.value = price || '';
                calculateTotal();
            });

            // Calcular total
            quantityInput.addEventListener('input', calculateTotal);
            unitValueInput.addEventListener('input', calculateTotal);

            function calculateTotal() {
                const quantity = parseFloat(quantityInput.value) || 0;
                const unitValue = parseFloat(unitValueInput.value) || 0;
                totalInput.value = (quantity * unitValue).toFixed(2);
                updateFormTotal();
            }

            // Remover item
            removeButton.addEventListener('click', function() {
                if (document.querySelectorAll('#itemsContainer .item-row').length > 1) {
                    lastItem.remove();
                    updateFormTotal();
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
            sum += parseFloat(input.value) || 0;
        });

        const discount = parseFloat(document.getElementById('discount').value) || 0;
        const finalTotal = sum - discount;

        document.getElementById('total').value = finalTotal.toFixed(2);
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

        if (productSelect) {
            productSelect.addEventListener('change', function() {
                const price = this.options[this.selectedIndex]?.dataset.price;
                unitValueInput.value = price || '';
                calculateTotal();
            });
        }

        if (quantityInput && unitValueInput && totalInput) {
            function calculateTotal() {
                const quantity = parseFloat(quantityInput.value) || 0;
                const unitValue = parseFloat(unitValueInput.value) || 0;
                totalInput.value = (quantity * unitValue).toFixed(2);
                updateFormTotal();
            }

            quantityInput.addEventListener('input', calculateTotal);
            unitValueInput.addEventListener('input', calculateTotal);
        }

        if (removeButton) {
            removeButton.addEventListener('click', function() {
                if (document.querySelectorAll('#itemsContainer .item-row').length > 1) {
                    item.remove();
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
