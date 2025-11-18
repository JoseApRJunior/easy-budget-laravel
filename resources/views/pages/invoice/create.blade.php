@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-plus-circle me-2"></i>Nova Fatura
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('provider.invoices.index') }}">Faturas</a></li>
                <li class="breadcrumb-item active">Nova</li>
            </ol>
        </nav>
    </div>

    <form action="{{ route('provider.invoices.store') }}" method="POST" id="invoiceForm">
        @csrf

        <div class="row g-4">
            <!-- Dados da Fatura -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-receipt-cutoff me-2"></i>Dados da Fatura
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Serviço -->
                        <div class="mb-3">
                            <label for="service_code" class="form-label">Serviço *</label>
                            <div class="input-group">
                                <input type="text"
                                       class="form-control @error('service_code') is-invalid @enderror"
                                       name="service_code"
                                       id="service_code"
                                       value="{{ old('service_code', $service->code ?? '') }}"
                                       placeholder="Digite o código do serviço"
                                       required>
                                <button class="btn btn-outline-secondary" type="button" id="searchServiceBtn">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                            <div id="serviceInfo" class="mt-2" style="display: none;">
                                <div class="alert alert-info">
                                    <strong>Serviço:</strong> <span id="serviceDescription"></span><br>
                                    <strong>Cliente:</strong> <span id="serviceCustomer"></span><br>
                                    <strong>Orçamento:</strong> <span id="serviceBudget"></span>
                                </div>
                            </div>
                            @error('service_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Cliente -->
                        <div class="mb-3">
                            <label for="customer_id" class="form-label">Cliente *</label>
                            <select class="form-select @error('customer_id') is-invalid @enderror"
                                    name="customer_id" id="customer_id" required>
                                <option value="">Selecione o cliente</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}"
                                            {{ old('customer_id', $service->customer_id ?? '') == $customer->id ? 'selected' : '' }}>
                                        {{ $customer->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('customer_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="issue_date" class="form-label">Data de Emissão *</label>
                                    <input type="date"
                                           class="form-control @error('issue_date') is-invalid @enderror"
                                           name="issue_date"
                                           id="issue_date"
                                           value="{{ old('issue_date', now()->format('Y-m-d')) }}"
                                           required>
                                    @error('issue_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="due_date" class="form-label">Data de Vencimento *</label>
                                    <input type="date"
                                           class="form-control @error('due_date') is-invalid @enderror"
                                           name="due_date"
                                           id="due_date"
                                           value="{{ old('due_date', now()->addDays(30)->format('Y-m-d')) }}"
                                           required>
                                    @error('due_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <label for="status" class="form-label">Status *</label>
                            <select class="form-select @error('status') is-invalid @enderror"
                                    name="status" id="status" required>
                                @foreach($statusOptions as $status)
                                    <option value="{{ $status->value }}"
                                            {{ old('status', 'pending') == $status->value ? 'selected' : '' }}>
                                        {{ $status->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Itens da Fatura -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-list-check me-2"></i>Itens da Fatura
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="invoice-items">
                            <div class="item-row mb-3 p-3 border rounded">
                                <div class="row align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label">Produto *</label>
                                        <select name="items[0][product_id]"
                                                class="form-select product-select @error('items.0.product_id') is-invalid @enderror"
                                                required>
                                            <option value="">Selecione o produto</option>
                                            @foreach(\App\Models\Product::where('active', true)->get() as $product)
                                                <option value="{{ $product->id }}"
                                                        data-price="{{ $product->price }}">
                                                    {{ $product->name }} - R$ {{ number_format($product->price, 2, ',', '.') }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('items.0.product_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Quantidade *</label>
                                        <input type="number"
                                               name="items[0][quantity]"
                                               class="form-control quantity-input @error('items.0.quantity') is-invalid @enderror"
                                               value="{{ old('items.0.quantity', 1) }}"
                                               step="0.01"
                                               min="0.01"
                                               required>
                                        @error('items.0.quantity')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Valor Unit. *</label>
                                        <input type="number"
                                               name="items[0][unit_value]"
                                               class="form-control unit-value-input @error('items.0.unit_value') is-invalid @enderror"
                                               value="{{ old('items.0.unit_value', 0) }}"
                                               step="0.01"
                                               min="0.01"
                                               required>
                                        @error('items.0.unit_value')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Total</label>
                                        <input type="text"
                                               class="form-control total-display"
                                               value="R$ 0,00"
                                               readonly>
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button"
                                                class="btn btn-danger btn-sm remove-item"
                                                title="Remover item"
                                                style="display: none;">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-outline-success btn-sm" id="addItemBtn">
                            <i class="bi bi-plus-circle me-1"></i>Adicionar Item
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resumo -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Resumo da Fatura</h5>
                        <div class="d-flex justify-content-between">
                            <span>Subtotal:</span>
                            <span id="subtotal">R$ 0,00</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Desconto:</span>
                            <span id="discount">R$ 0,00</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total:</span>
                            <span id="grandTotal">R$ 0,00</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ações -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Criar Fatura
                    </button>
                    <a href="{{ route('provider.invoices.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i>Cancelar
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cálculo automático de totais
    function calculateTotals() {
        let subtotal = 0;

        document.querySelectorAll('.item-row').forEach(function(row) {
            const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
            const unitValue = parseFloat(row.querySelector('.unit-value-input').value) || 0;
            const total = quantity * unitValue;

            row.querySelector('.total-display').value = 'R$ ' + total.toFixed(2).replace('.', ',');
            subtotal += total;
        });

        document.getElementById('subtotal').textContent = 'R$ ' + subtotal.toFixed(2).replace('.', ',');
        document.getElementById('grandTotal').textContent = 'R$ ' + subtotal.toFixed(2).replace('.', ',');
    }

    // Preencher valor unitário quando produto for selecionado
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('product-select')) {
            const price = e.target.selectedOptions[0]?.dataset.price || 0;
            const itemRow = e.target.closest('.item-row');
            itemRow.querySelector('.unit-value-input').value = price;
            calculateTotals();
        }
    });

    // Recalcular totais quando os valores mudarem
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('quantity-input') || e.target.classList.contains('unit-value-input')) {
            calculateTotals();
        }
    });

    // Remover item
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-item')) {
            const itemRows = document.querySelectorAll('.item-row');
            if (itemRows.length > 1) {
                e.target.closest('.item-row').remove();
                calculateTotals();
            }
        }
    });

    // Adicionar novo item
    document.getElementById('addItemBtn').addEventListener('click', function() {
        const itemsContainer = document.getElementById('invoice-items');
        const newIndex = itemsContainer.children.length;

        const newItemHtml = `
            <div class="item-row mb-3 p-3 border rounded">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Produto *</label>
                        <select name="items[${newIndex}][product_id]" class="form-select product-select" required>
                            <option value="">Selecione o produto</option>
                            @foreach(\App\Models\Product::where('active', true)->get() as $product)
                                <option value="{{ $product->id }}" data-price="{{ $product->price }}">
                                    {{ $product->name }} - R$ {{ number_format($product->price, 2, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Quantidade *</label>
                        <input type="number"
                               name="items[${newIndex}][quantity]"
                               class="form-control quantity-input"
                               value="1"
                               step="0.01"
                               min="0.01"
                               required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Valor Unit. *</label>
                        <input type="number"
                               name="items[${newIndex}][unit_value]"
                               class="form-control unit-value-input"
                               value="0"
                               step="0.01"
                               min="0.01"
                               required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Total</label>
                        <input type="text" class="form-control total-display" value="R$ 0,00" readonly>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-danger btn-sm remove-item" title="Remover item">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;

        itemsContainer.insertAdjacentHTML('beforeend', newItemHtml);

        // Mostrar botão de remover em todos os itens quando houver mais de 1
        updateRemoveButtons();
    });

    // Atualizar visibilidade dos botões de remover
    function updateRemoveButtons() {
        const itemRows = document.querySelectorAll('.item-row');
        const removeButtons = document.querySelectorAll('.remove-item');

        removeButtons.forEach(function(btn) {
            btn.style.display = itemRows.length > 1 ? 'block' : 'none';
        });
    }

    // Buscar informações do serviço
    document.getElementById('searchServiceBtn').addEventListener('click', function() {
        const serviceCode = document.getElementById('service_code').value;

        if (!serviceCode) {
            alert('Digite o código do serviço');
            return;
        }

        // Aqui você pode fazer uma requisição AJAX para buscar as informações do serviço
        // Por enquanto, vamos apenas simular
        alert('Funcionalidade de busca de serviço será implementada');
    });

    // Calcular totais iniciais
    calculateTotals();
    updateRemoveButtons();
});
</script>
@endsection
