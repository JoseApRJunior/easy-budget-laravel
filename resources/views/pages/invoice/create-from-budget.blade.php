@extends('layouts.app')

@section('content')
    <div class="container py-1">
        <h1 class="h4 mb-3">Criar Fatura a partir do Orçamento</h1>

        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div><strong>Orçamento:</strong> {{ $budget->code }}</div>
                        <div><strong>Cliente:</strong> {{ $budget->customer->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div><strong>Total do Orçamento:</strong> R$ {{ number_format($budget->total, 2, ',', '.') }}</div>
                        <div><strong>Total Faturado:</strong> R$ {{ number_format($alreadyBilled, 2, ',', '.') }}</div>
                        <div><strong>Saldo Disponível:</strong> <span id="remaining-balance"
                                class="text-{{ $remainingBalance > 0 ? 'success' : 'danger' }}">R$
                                {{ number_format($remainingBalance, 2, ',', '.') }}</span></div>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('provider.invoices.store.from-budget', $budget) }}">
            @csrf
            <input type="hidden" name="budget_code" value="{{ $budget->code }}" />

            <div class="card mb-4">
                <div class="card-header">Itens do Orçamento</div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>Selecione os itens para faturar:</strong> O total selecionado não pode exceder o saldo
                        disponível de <strong>R$ {{ number_format($remainingBalance, 2, ',', '.') }}</strong>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th style="width: 40px">
                                        <input type="checkbox" id="select-all" class="form-check-input" />
                                    </th>
                                    <th>Serviço</th>
                                    <th>Produto</th>
                                    <th class="text-end">Qtd</th>
                                    <th class="text-end">Valor Unit.</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Total Selecionado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($budget->services as $service)
                                    @foreach ($service->serviceItems as $item)
                                        <tr data-item-id="{{ $item->id }}" data-item-total="{{ $item->total }}">
                                            <td>
                                                <input type="checkbox" class="form-check-input item-checkbox"
                                                    name="items[{{ $item->id }}][selected]" value="1"
                                                    data-item-id="{{ $item->id }}" />
                                                <input type="hidden" name="items[{{ $item->id }}][service_item_id]"
                                                    value="{{ $item->id }}" />
                                            </td>
                                            <td>{{ $service->description }}</td>
                                            <td>{{ $item->product->name ?? '#' . $item->product_id }}</td>
                                            <td>
                                                <input type="number" step="0.01" min="0.01"
                                                    max="{{ $item->quantity }}"
                                                    class="form-control form-control-sm text-end item-quantity"
                                                    name="items[{{ $item->id }}][quantity]"
                                                    value="{{ $item->quantity }}" data-item-id="{{ $item->id }}"
                                                    data-original-quantity="{{ $item->quantity }}" />
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0.01"
                                                    class="form-control form-control-sm text-end item-unit-value"
                                                    name="items[{{ $item->id }}][unit_value]"
                                                    value="{{ $item->unit_value }}" data-item-id="{{ $item->id }}"
                                                    data-original-unit-value="{{ $item->unit_value }}" />
                                            </td>
                                            <td class="text-end original-total">R$
                                                {{ number_format($item->total, 2, ',', '.') }}</td>
                                            <td class="text-end selected-total" data-item-id="{{ $item->id }}">R$ 0,00
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="table-active">
                                    <th colspan="6" class="text-end">Total Selecionado:</th>
                                    <th class="text-end" id="total-selected">R$ 0,00</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">Dados da Fatura</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Serviço</label>
                            <select class="form-select" name="service_id" required>
                                @foreach ($budget->services as $service)
                                    <option value="{{ $service->id }}">{{ $service->code }} —
                                        {{ $service->description }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Vencimento</label>
                            <input type="date" class="form-control" name="due_date"
                                value="{{ now()->addDays(7)->format('Y-m-d') }}" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                @foreach ($statusOptions as $st)
                                    <option value="{{ $st->value }}">{{ $st->getDescription() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Desconto</label>
                            <input type="text" inputmode="numeric" class="form-control" name="discount" id="discount"
                                value="R$ {{ number_format($budget->discount ?? 0, 2, ',', '.') }}" />
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    try {
                                        var input = document.getElementById('discount');
                                        console.info('[invoice:create-from-budget] DOM ready, discount input found:', !!input,
                                            'VanillaMask:', !!window.VanillaMask);
                                        if (window.VanillaMask) {
                                            new VanillaMask('discount', 'currency');
                                            console.info('[invoice:create-from-budget] VanillaMask initialized for discount');
                                        } else if (input) {
                                            input.addEventListener('input', function() {
                                                var digits = this.value.replace(/\D/g, '');
                                                var num = (parseInt(digits || '0', 10) / 100);
                                                var integer = Math.floor(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                                                var cents = Math.round((num - Math.floor(num)) * 100).toString().padStart(2, '0');
                                                this.value = 'R$ ' + integer + ',' + cents;
                                                console.debug('[invoice:create-from-budget] discount input formatted:', this.value);
                                            });
                                        }
                                        var form = document.querySelector('form');
                                        if (form) {
                                            form.addEventListener('submit', function() {
                                                var input = document.getElementById('discount');
                                                var num = 0;
                                                if (input) {
                                                    if (window.parseCurrencyBRLToNumber) {
                                                        num = window.parseCurrencyBRLToNumber(input.value);
                                                    } else {
                                                        var digits = input.value.replace(/\D/g, '');
                                                        num = parseInt(digits || '0', 10) / 100;
                                                    }
                                                    input.value = Number.isFinite(num) ? num.toFixed(2) : '0.00';
                                                    console.info('[invoice:create-from-budget] discount normalized on submit:',
                                                        input.value);
                                                }
                                            });
                                        }
                                    } catch (e) {}
                                });
                            </script>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary" id="submit-btn">Criar Fatura</button>
                <a href="{{ route('provider.invoices.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const remainingBalance = {{ $remainingBalance }};
                const selectAllCheckbox = document.getElementById('select-all');
                const itemCheckboxes = document.querySelectorAll('.item-checkbox');
                const quantityInputs = document.querySelectorAll('.item-quantity');
                const unitValueInputs = document.querySelectorAll('.item-unit-value');
                const totalSelectedElement = document.getElementById('total-selected');
                const submitBtn = document.getElementById('submit-btn');
                const remainingBalanceElement = document.getElementById('remaining-balance');

                function formatCurrency(value) {
                    return 'R$ ' + value.toFixed(2).replace('.', ',');
                }

                function calculateItemTotal(itemId) {
                    const checkbox = document.querySelector(`.item-checkbox[data-item-id="${itemId}"]`);
                    if (!checkbox.checked) return 0;

                    const quantity = parseFloat(document.querySelector(`.item-quantity[data-item-id="${itemId}"]`)
                        .value) || 0;
                    const unitValue = parseFloat(document.querySelector(`.item-unit-value[data-item-id="${itemId}"]`)
                        .value) || 0;
                    return quantity * unitValue;
                }

                function updateSelectedTotal() {
                    let totalSelected = 0;
                    itemCheckboxes.forEach(checkbox => {
                        const itemId = checkbox.dataset.itemId;
                        const itemTotal = calculateItemTotal(itemId);
                        totalSelected += itemTotal;

                        // Update individual item total display
                        const selectedTotalElement = document.querySelector(
                            `.selected-total[data-item-id="${itemId}"]`);
                        if (selectedTotalElement) {
                            selectedTotalElement.textContent = formatCurrency(itemTotal);
                        }
                    });

                    totalSelectedElement.textContent = formatCurrency(totalSelected);

                    // Validate against remaining balance
                    if (totalSelected > remainingBalance) {
                        totalSelectedElement.classList.add('text-danger');
                        totalSelectedElement.classList.remove('text-dark');
                        submitBtn.disabled = true;
                        submitBtn.classList.add('btn-danger');
                        submitBtn.classList.remove('btn-primary');
                        submitBtn.textContent = 'Total excede o saldo disponível';
                    } else if (totalSelected === 0) {
                        totalSelectedElement.classList.add('text-warning');
                        totalSelectedElement.classList.remove('text-dark');
                        submitBtn.disabled = true;
                        submitBtn.classList.add('btn-warning');
                        submitBtn.classList.remove('btn-primary');
                        submitBtn.textContent = 'Selecione pelo menos um item';
                    } else {
                        totalSelectedElement.classList.remove('text-danger', 'text-warning');
                        totalSelectedElement.classList.add('text-dark');
                        submitBtn.disabled = false;
                        submitBtn.classList.remove('btn-danger', 'btn-warning');
                        submitBtn.classList.add('btn-primary');
                        submitBtn.textContent = 'Criar Fatura';
                    }
                }

                // Select all functionality
                selectAllCheckbox.addEventListener('change', function() {
                    itemCheckboxes.forEach(checkbox => {
                        checkbox.checked = selectAllCheckbox.checked;
                    });
                    updateSelectedTotal();
                });

                // Individual checkbox changes
                itemCheckboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        // If any checkbox is unchecked, uncheck "select all"
                        if (!checkbox.checked) {
                            selectAllCheckbox.checked = false;
                        }
                        updateSelectedTotal();
                    });
                });

                // Quantity and unit value changes
                quantityInputs.forEach(input => {
                    input.addEventListener('input', function() {
                        const itemId = this.dataset.itemId;
                        const originalQuantity = parseFloat(this.dataset.originalQuantity);
                        const currentQuantity = parseFloat(this.value);

                        // Validate quantity doesn't exceed original
                        if (currentQuantity > originalQuantity) {
                            this.value = originalQuantity;
                            if (window.easyAlert) {
                                window.easyAlert.warning('A quantidade não pode exceder a quantidade original do orçamento');
                            } else {
                                alert('A quantidade não pode exceder a quantidade original do orçamento');
                            }
                        }

                        updateSelectedTotal();
                    });
                });

                unitValueInputs.forEach(input => {
                    input.addEventListener('input', function() {
                        updateSelectedTotal();
                    });
                });

                // Form submission validation
                document.querySelector('form').addEventListener('submit', function(e) {
                    const totalSelected = parseFloat(totalSelectedElement.textContent.replace('R$ ', '')
                        .replace(',', '.'));

                    if (totalSelected === 0) {
                        e.preventDefault();
                        if (window.easyAlert) {
                            window.easyAlert.error('Por favor, selecione pelo menos um item para faturar.');
                        } else {
                            alert('Por favor, selecione pelo menos um item para faturar.');
                        }
                        return;
                    }

                    if (totalSelected > remainingBalance) {
                        e.preventDefault();
                        if (window.easyAlert) {
                            window.easyAlert.error('O total selecionado excede o saldo disponível do orçamento.');
                        } else {
                            alert('O total selecionado excede o saldo disponível do orçamento.');
                        }
                        return;
                    }

                    // Confirm before submitting
                    if (!confirm(
                        `Confirma a criação da fatura no valor de ${formatCurrency(totalSelected)}?`)) {
                        e.preventDefault();
                    }
                });

                // Initial calculation
                updateSelectedTotal();
            });
        </script>
    @endpush
