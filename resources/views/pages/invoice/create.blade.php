@extends('layouts.app')

@section('title', 'Nova Fatura')
@section('content')
<x-layout.page-container>
    <x-layout.page-header
        title="Nova Fatura"
        icon="plus-circle"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Faturas' => route('provider.invoices.dashboard'),
            'Nova' => '#'
        ]">
        <p class="text-muted mb-0">Preencha os dados para criar uma nova fatura</p>
    </x-layout.page-header>

    <form action="{{ route('provider.invoices.store') }}" method="POST" id="invoiceForm">
        @csrf

        <div class="row g-4">
            <!-- Dados da Fatura -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-receipt-cutoff me-2"></i>Dados da Fatura</h5>
                    </div>
                    <div class="card-body">
                        <!-- Serviço -->
                        <div class="mb-3">
                            <label for="service_code" class="form-label small fw-bold text-muted text-uppercase">Serviço *</label>
                            <div class="input-group">
                                <input type="text" class="form-control @error('service_code') is-invalid @enderror"
                                    name="service_code" id="service_code"
                                    value="{{ old('service_code', $service->code ?? '') }}"
                                    placeholder="Digite o código do serviço" required>
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
                            <label for="customer_id" class="form-label small fw-bold text-muted text-uppercase">Cliente *</label>
                            <select class="form-select tom-select @error('customer_id') is-invalid @enderror"
                                name="customer_id" id="customer_id" required>
                                <option value="">Selecione o cliente</option>
                                @foreach ($customers as $customer)
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
                                    <label for="issue_date" class="form-label small fw-bold text-muted text-uppercase">Data de Emissão *</label>
                                    <input type="date" class="form-control @error('issue_date') is-invalid @enderror"
                                        name="issue_date" id="issue_date"
                                        value="{{ old('issue_date', now()->format('Y-m-d')) }}" required>
                                    @error('issue_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="due_date" class="form-label small fw-bold text-muted text-uppercase">Data de Vencimento *</label>
                                    <input type="date" class="form-control @error('due_date') is-invalid @enderror"
                                        name="due_date" id="due_date"
                                        value="{{ old('due_date', now()->addDays(30)->format('Y-m-d')) }}" required>
                                    @error('due_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <label for="status" class="form-label small fw-bold text-muted text-uppercase">Status *</label>
                            <select class="form-select tom-select @error('status') is-invalid @enderror"
                                name="status" id="status" required>
                                @foreach ($statusOptions as $status)
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
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-list-check me-2"></i>Itens da Fatura</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addItemBtn">
                            <i class="bi bi-plus-lg me-1"></i>Adicionar Item
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="invoice-items">
                            <div class="item-row mb-3 p-3 border rounded bg-light">
                                <div class="row align-items-end g-3">
                                    <div class="col-12">
                                        <label class="form-label small fw-bold text-muted text-uppercase">Produto *</label>
                                        <select name="items[0][product_id]"
                                            class="form-select product-select @error('items.0.product_id') is-invalid @enderror"
                                            required>
                                            <option value="">Selecione o produto</option>
                                            @foreach (\App\Models\Product::where('active', true)->get() as $product)
                                                <option value="{{ $product->id }}"
                                                    data-price="{{ $product->price }}">
                                                    {{ $product->name }} -
                                                    {{ \App\Helpers\CurrencyHelper::format($product->price) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('items.0.product_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small fw-bold text-muted text-uppercase">Qtd *</label>
                                        <input type="number" name="items[0][quantity]"
                                            class="form-control quantity-input @error('items.0.quantity') is-invalid @enderror"
                                            value="{{ old('items.0.quantity', 1) }}" step="0.01" min="0.01"
                                            required>
                                        @error('items.0.quantity')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small fw-bold text-muted text-uppercase">Valor Unit. *</label>
                                        <input type="text" name="items[0][unit_value]"
                                            class="form-control unit-value-input currency-brl @error('items.0.unit_value') is-invalid @enderror"
                                            value="{{ \App\Helpers\CurrencyHelper::format(old('items.0.unit_value', 0)) }}"
                                            required>
                                        @error('items.0.unit_value')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small fw-bold text-muted text-uppercase">Total</label>
                                        <input type="text" class="form-control total-display bg-white" value="0,00"
                                            readonly>
                                    </div>
                                    <div class="col-12 text-end">
                                        <button type="button" class="btn btn-outline-danger btn-sm remove-item"
                                            title="Remover item" style="display: none;">
                                            <i class="bi bi-trash me-1"></i>Remover
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resumo -->
        <div class="row mt-4">
            <div class="col-md-6 offset-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title small fw-bold text-muted text-uppercase mb-3">Resumo da Fatura</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span id="subtotal" class="fw-bold">0,00</span>
                            <input type="hidden" name="subtotal" id="subtotal_input" value="0">
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Desconto:</span>
                            <span id="discount" class="fw-bold">0,00</span>
                            <input type="hidden" name="discount" id="discount_input" value="0">
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold fs-5 text-primary">
                            <span>Total:</span>
                            <span id="grandTotal">0,00</span>
                            <input type="hidden" name="total" id="total_input" value="0">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Botões de Ação (Footer) --}}
        <div class="d-flex justify-content-between mt-4 pb-4">
            <x-ui.back-button index-route="provider.invoices.index" label="Cancelar" feature="invoices" />
            <x-ui.button type="submit" variant="primary" icon="check-circle" label="Criar Fatura" feature="invoices" />
        </div>
    </form>
</x-layout.page-container>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Cálculo automático de totais
            function calculateTotals() {
                let subtotal = 0;

                document.querySelectorAll('.item-row').forEach(function(row) {
                    const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
                    const unitValueInput = row.querySelector('.unit-value-input');
                    const unitValue = typeof parseCurrencyBRLToNumber === 'function' 
                        ? parseCurrencyBRLToNumber(unitValueInput.value) 
                        : parseFloat(unitValueInput.value) || 0;
                    
                    const total = quantity * unitValue;

                    row.querySelector('.total-display').value = (typeof formatCurrencyBRL === 'function' 
                        ? formatCurrencyBRL(total) 
                        : total.toFixed(2).replace('.', ','));
                    
                    subtotal += total;
                });

                const formattedSubtotal = (typeof formatCurrencyBRL === 'function' 
                    ? formatCurrencyBRL(subtotal) 
                    : subtotal.toFixed(2).replace('.', ','));
                
                document.getElementById('subtotal').textContent = formattedSubtotal;
                document.getElementById('grandTotal').textContent = formattedSubtotal;

                // Atualizar campos ocultos para o backend
                document.getElementById('subtotal_input').value = (typeof formatCurrencyBRL === 'function' 
                    ? formatCurrencyBRL(subtotal) 
                    : subtotal.toFixed(2).replace('.', ','));
                document.getElementById('total_input').value = (typeof formatCurrencyBRL === 'function' 
                    ? formatCurrencyBRL(subtotal) 
                    : subtotal.toFixed(2).replace('.', ','));
            }

            // Preencher valor unitário quando produto for selecionado
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('product-select')) {
                    const price = e.target.selectedOptions[0]?.dataset.price || 0;
                    const itemRow = e.target.closest('.item-row');
                    const unitValueInput = itemRow.querySelector('.unit-value-input');
                    
                    unitValueInput.value = typeof formatCurrencyBRL === 'function' 
                        ? formatCurrencyBRL(price) 
                        : price;
                    
                    // Disparar evento de input para atualizar a máscara e o total
                    unitValueInput.dispatchEvent(new Event('input', { bubbles: true }));
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
                        <label class="form-label small fw-bold text-muted text-uppercase">Produto *</label>
                        <select name="items[${newIndex}][product_id]" class="form-select product-select" required>
                            <option value="">Selecione o produto</option>
                            @foreach (\App\Models\Product::where('active', true)->get() as $product)
                                <option value="{{ $product->id }}" data-price="{{ $product->price }}">
                                    {{ $product->name }} - {{ \App\Helpers\CurrencyHelper::format($product->price) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted text-uppercase">Quantidade *</label>
                        <input type="number"
                               name="items[${newIndex}][quantity]"
                               class="form-control quantity-input"
                               value="1"
                               step="0.01"
                               min="0.01"
                               required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Valor Unit. *</label>
                        <input type="text"
                               name="items[${newIndex}][unit_value]"
                               class="form-control unit-value-input currency-brl"
                               value="0,00"
                               required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted text-uppercase">Total</label>
                        <input type="text" class="form-control total-display" value="0,00" readonly>
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

                // Re-inicializar máscaras para os novos campos
                if (typeof window.initVanillaMask === 'function') {
                    window.initVanillaMask();
                }
                
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
