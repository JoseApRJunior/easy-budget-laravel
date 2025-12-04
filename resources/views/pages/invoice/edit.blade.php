@extends('layouts.app')

@section('content')
    <div class="container-fluid py-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-pencil-square me-2"></i>Editar Fatura #{{ $invoice->code }}
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('provider.invoices.index') }}">Faturas</a></li>
                    <li class="breadcrumb-item"><a
                            href="{{ route('provider.invoices.show', $invoice->code) }}">#{{ $invoice->code }}</a></li>
                    <li class="breadcrumb-item active">Editar</li>
                </ol>
            </nav>
        </div>

        <form action="{{ route('provider.invoices.update', $invoice->code) }}" method="POST" id="invoiceEditForm">
            @csrf
            @method('PUT')

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
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="customer_id" class="form-label">Cliente *</label>
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
                                        <label for="status" class="form-label">Status *</label>
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
                                        <label for="issue_date" class="form-label">Data de Emissão *</label>
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
                                        <label for="due_date" class="form-label">Data de Vencimento *</label>
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
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-list-check me-2"></i>Itens da Fatura
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="invoice-items">
                                @foreach ($invoice->invoiceItems as $item)
                                    <div class="item-row mb-3 p-3 border rounded" data-item-id="{{ $item->id }}">
                                        <div class="row align-items-end">
                                            <div class="col-md-4">
                                                <label class="form-label">Produto *</label>
                                                <select name="items[{{ $loop->index }}][product_id]"
                                                    class="form-select @error('items.' . $loop->index . '.product_id') is-invalid @enderror"
                                                    required>
                                                    <option value="">Selecione o produto</option>
                                                    <!-- Aqui você pode adicionar os produtos disponíveis -->
                                                </select>
                                                @error('items.' . $loop->index . '.product_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Quantidade *</label>
                                                <input type="number" name="items[{{ $loop->index }}][quantity]"
                                                    class="form-control quantity-input @error('items.' . $loop->index . '.quantity') is-invalid @enderror"
                                                    value="{{ old('items.' . $loop->index . '.quantity', $item->quantity) }}"
                                                    step="0.01" min="0.01" required>
                                                @error('items.' . $loop->index . '.quantity')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Valor Unit. *</label>
                                                <input type="number" name="items[{{ $loop->index }}][unit_value]"
                                                    class="form-control unit-value-input @error('items.' . $loop->index . '.unit_value') is-invalid @enderror"
                                                    value="{{ old('items.' . $loop->index . '.unit_value', $item->unit_value) }}"
                                                    step="0.01" min="0.01" required>
                                                @error('items.' . $loop->index . '.unit_value')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Total</label>
                                                <input type="text" class="form-control total-display"
                                                    value="R$ {{ number_format($item->total, 2, ',', '.') }}" readonly>
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
                            <i class="bi bi-check-circle me-1"></i>Salvar Alterações
                        </button>
                        <a href="{{ route('provider.invoices.show', $invoice->code) }}" class="btn btn-secondary">
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

            // Recalcular totais quando os valores mudarem
            document.addEventListener('input', function(e) {
                if (e.target.classList.contains('quantity-input') || e.target.classList.contains(
                        'unit-value-input')) {
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
                        <label class="form-label">Produto *</label>
                        <select name="items[${newIndex}][product_id]" class="form-select" required>
                            <option value="">Selecione o produto</option>
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
                <input type="hidden" name="items[${newIndex}][action]" value="create">
            </div>
        `;

                itemsContainer.insertAdjacentHTML('beforeend', newItemHtml);
            });

            // Calcular totais iniciais
            calculateTotals();
        });
    </script>
@endsection
