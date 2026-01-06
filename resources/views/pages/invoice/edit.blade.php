@extends('layouts.app')

@section('title', 'Editar Fatura')
@section('content')
    <div class="container-fluid py-4">
        <x-page-header
            title="Editar Fatura"
            icon="pencil-square"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Faturas' => route('provider.invoices.dashboard'),
                $invoice->code => route('provider.invoices.show', $invoice->code),
                'Editar' => '#'
            ]">
            <p class="text-muted mb-0">Atualize as informações da fatura {{ $invoice->code }}</p>
        </x-page-header>

    <form action="{{ route('provider.invoices.update', $invoice->code) }}" method="POST" id="invoiceEditForm">
        @csrf
        @method('PUT')

            <div class="row g-4">
                <!-- Dados da Fatura -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 text-primary fw-bold">
                                <i class="bi bi-receipt-cutoff me-2"></i>Dados da Fatura
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="customer_id" class="form-label small fw-bold text-muted text-uppercase">Cliente *</label>
                                        <select class="form-select @error('customer_id') is-invalid @enderror"
                                            name="customer_id" id="customer_id" required>
                                            <option value="">Selecione o cliente</option>
                                            @foreach ($customers as $customer)
                                                <option value="{{ $customer->id }}"
                                                    {{ old('customer_id', $invoice->customer_id) == $customer->id ? 'selected' : '' }}>
                                                    {{ $customer->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('customer_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="status" class="form-label small fw-bold text-muted text-uppercase">Status *</label>
                                        <select class="form-select @error('status') is-invalid @enderror" name="status"
                                            id="status" required>
                                            @foreach ($statusOptions as $status)
                                                <option value="{{ $status->value }}"
                                                    {{ old('status', $invoice->status) == $status->value ? 'selected' : '' }}>
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

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="issue_date" class="form-label small fw-bold text-muted text-uppercase">Data de Emissão *</label>
                                        <input type="date" class="form-control @error('issue_date') is-invalid @enderror"
                                            name="issue_date" id="issue_date"
                                            value="{{ old('issue_date', $invoice->issue_date?->format('Y-m-d')) }}"
                                            required>
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
                                            value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}" required>
                                        @error('due_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Itens da Fatura -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 text-success fw-bold">
                                <i class="bi bi-list-check me-2"></i>Itens da Fatura
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="invoice-items">
                                @foreach ($invoice->invoiceItems as $item)
                                    <div class="item-row mb-3 p-3 border rounded" data-item-id="{{ $item->id }}">
                                        <div class="row align-items-end">
                                            <div class="col-md-4">
                                                <label class="form-label small fw-bold text-muted text-uppercase">Produto *</label>
                                                <select name="items[{{ $loop->index }}][product_id]"
                                                    class="form-select product-select @error('items.' . $loop->index . '.product_id') is-invalid @enderror"
                                                    required>
                                                    <option value="">Selecione o produto</option>
                                                    @foreach (\App\Models\Product::where('active', true)->get() as $product)
                                                        <option value="{{ $product->id }}"
                                                            data-price="{{ $product->price }}"
                                                            {{ old('items.' . $loop->index . '.product_id', $item->product_id) == $product->id ? 'selected' : '' }}>
                                                                    {{ $product->name }} -
                                                                    {{ \App\Helpers\CurrencyHelper::format($product->price) }}
                                                                </option>
                                                    @endforeach
                                                </select>
                                                @error('items.' . $loop->index . '.product_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small fw-bold text-muted text-uppercase">Quantidade *</label>
                                                <input type="number" name="items[{{ $loop->index }}][quantity]"
                                                    class="form-control quantity-input @error('items.' . $loop->index . '.quantity') is-invalid @enderror"
                                                    value="{{ old('items.' . $loop->index . '.quantity', $item->quantity) }}"
                                                    step="0.01" min="0.01" required>
                                                @error('items.' . $loop->index . '.quantity')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small fw-bold text-muted text-uppercase">Valor Unit. *</label>
                                                <input type="text" name="items[{{ $loop->index }}][unit_value]"
                                                    class="form-control unit-value-input currency-brl @error('items.' . $loop->index . '.unit_value') is-invalid @enderror"
                                                    value="{{ \App\Helpers\CurrencyHelper::format(old('items.' . $loop->index . '.unit_value', $item->unit_value)) }}"
                                                    required>
                                                @error('items.' . $loop->index . '.unit_value')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small fw-bold text-muted text-uppercase">Total</label>
                                                <input type="text" class="form-control total-display"
                                                    value="{{ \App\Helpers\CurrencyHelper::format($item->total) }}" readonly>
                                            </div>
                                            <div class="col-md-1">
                                                <button type="button" class="btn btn-danger btn-sm remove-item"
                                                    title="Remover item">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <input type="hidden" name="items[{{ $loop->index }}][id]"
                                            value="{{ $item->id }}">
                                        <input type="hidden" name="items[{{ $loop->index }}][action]" value="update">
                                    </div>
                                @endforeach
                            </div>

                            <x-button type="button" variant="outline-success" icon="plus-circle" label="Adicionar Item" size="sm" id="addItemBtn" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resumo -->
            <div class="row mt-4">
                <div class="col-md-6">
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
            <div class="d-flex justify-content-between mt-4">
                <x-button type="link" :href="url()->previous(route('provider.invoices.index'))" variant="outline-secondary" icon="arrow-left" label="Cancelar" />
                <x-button type="submit" variant="primary" icon="check-circle" label="Salvar Alterações" />
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Cálculo automático de totais
            function calculateTotals() {
                let subtotal = 0;

                document.querySelectorAll('.item-row').forEach(function(row) {
                    if (row.querySelector('input[name$="[action]"]')?.value === 'delete') return;

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
                    const itemRow = e.target.closest('.item-row');
                    if (itemRow.dataset.itemId) {
                        // Marcar como deleted se for um item existente
                        const actionInput = itemRow.querySelector('input[name$="[action]"]');
                        actionInput.value = 'delete';
                        itemRow.style.opacity = '0.5';
                    } else {
                        // Remover completamente se for um item novo
                        itemRow.remove();
                    }
                    calculateTotals();
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
                <input type="hidden" name="items[${newIndex}][action]" value="create">
            </div>
        `;

                itemsContainer.insertAdjacentHTML('beforeend', newItemHtml);

                // Re-inicializar máscaras para os novos campos
                if (typeof window.initVanillaMask === 'function') {
                    window.initVanillaMask();
                }
            });

            // Calcular totais iniciais
            calculateTotals();
        });
    </script>
@endsection
