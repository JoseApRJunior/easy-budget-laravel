@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <x-layout.page-header
            title="Gerar Fatura Parcial para o Serviço #{{ $serviceCode }}"
            icon="receipt"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Faturas' => route('provider.invoices.dashboard'),
                'Serviço #' . $serviceCode => route('provider.services.show', $serviceCode),
                'Fatura Parcial' => '#'
            ]">
            <p class="text-muted mb-0">Selecione apenas os itens que deseja faturar agora</p>
        </x-layout.page-header>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <!-- Informações do Cliente e Serviço -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h4>Para:</h4>
                        <address>
                            <strong>{{ $invoiceData['customer_name'] }}</strong><br>
                            @if ($invoiceData['customer_details']->email)
                                Email: {{ $invoiceData['customer_details']->email }}<br>
                            @endif
                            @if ($invoiceData['customer_details']->phone)
                                Telefone: {{ $invoiceData['customer_details']->phone }}<br>
                            @endif
                            @if ($invoiceData['customer_details']->address)
                                {{ $invoiceData['customer_details']->address }}<br>
                            @endif
                        </address>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h4>Fatura Parcial #{{ $serviceCode ? $serviceCode . '-INVXXX' : 'FAT-' . date('Ymd') . 'XXXX' }}</h4>
                        <p><strong>Data da Fatura:</strong> {{ now()->format('d/m/Y') }}</p>
                        <p><strong>Data de Vencimento:</strong>
                            {{ \Carbon\Carbon::parse($invoiceData['due_date'])->format('d/m/Y') }}</p>
                        <p><strong>Serviço Referente:</strong> #{{ $invoiceData['service_code'] }}</p>
                        <div class="alert alert-info mt-2">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            <strong>Fatura Parcial:</strong> Selecione apenas os itens que deseja faturar agora.
                        </div>
                    </div>
                </div>

                <!-- Formulário de Seleção de Itens -->
                <form action="{{ route('provider.invoices.store.manual-from-service', $serviceCode) }}" method="POST"
                    id="partialInvoiceForm">
                    @csrf

                    <!-- Seleção de Itens -->
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered" id="itemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="10%">
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    <th>Item</th>
                                    <th class="text-center">Qtd. Total</th>
                                    <th class="text-center">Qtd. a Faturar</th>
                                    <th class="text-end">Preço Unitário</th>
                                    <th class="text-end">Total Item</th>
                                    <th class="text-end">Total a Faturar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invoiceData['items'] as $index => $item)
                                    <tr data-item-id="{{ $item->id }}">
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input item-checkbox"
                                                name="items[{{ $index }}][selected]" value="1"
                                                data-index="{{ $index }}">
                                            <input type="hidden" name="items[{{ $index }}][service_item_id]"
                                                value="{{ $item->id }}">
                                            <input type="hidden" name="items[{{ $index }}][product_id]"
                                                value="{{ $item->product_id }}">
                                        </td>
                                        <td>
                                            <strong>{{ $item->product->name ?? 'Produto' }}</strong>
                                            @if ($item->product->description)
                                                <p class="small text-muted mb-0">{{ $item->product->description }}</p>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="total-quantity">{{ $item->quantity }}</span>
                                        </td>
                                        <td class="text-center">
                                            <input type="number" class="form-control form-control-sm quantity-input"
                                                name="items[{{ $index }}][quantity]" value="{{ $item->quantity }}"
                                                min="0.01" max="{{ $item->quantity }}" step="0.01"
                                                data-index="{{ $index }}" data-unit-value="{{ $item->unit_value }}"
                                                disabled>
                                        </td>
                                        <td class="text-end">
                                            R$ <span
                                                class="unit-value">{{ \App\Helpers\CurrencyHelper::format($item->unit_value, 2, false) }}</span>
                                            <input type="hidden" name="items[{{ $index }}][unit_value]"
                                                value="{{ $item->unit_value }}">
                                        </td>
                                        <td class="text-end">
                                            R$ <span
                                                class="total-item">{{ \App\Helpers\CurrencyHelper::format($item->total, 2, false) }}</span>
                                        </td>
                                        <td class="text-end">
                                            R$ <span class="partial-total text-primary fw-bold">0,00</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="6" class="text-end">Total Selecionado:</th>
                                    <th class="text-end">
                                        <h5 class="mb-0 text-success">R$ <span id="selectedTotal">0,00</span></h5>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Datas da Fatura -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="issue_date" class="form-label">Data de Emissão *</label>
                            <input type="date" class="form-control @error('issue_date') is-invalid @enderror"
                                name="issue_date" id="issue_date" value="{{ old('issue_date', now()->format('Y-m-d')) }}"
                                required>
                            @error('issue_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="due_date" class="form-label">Data de Vencimento *</label>
                            <input type="date" class="form-control @error('due_date') is-invalid @enderror"
                                name="due_date" id="due_date"
                                value="{{ old('due_date', \Carbon\Carbon::parse($invoiceData['due_date'])->format('Y-m-d')) }}"
                                required>
                            @error('due_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Observações -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <label for="notes" class="form-label">Observações (opcional)</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" id="notes" rows="3"
                                placeholder="Observações sobre esta fatura parcial...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Ações -->
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('provider.services.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Voltar
                        </a>
                        <button type="submit" class="btn btn-success" id="submitBtn" disabled>
                            <i class="bi bi-check-circle-fill me-2"></i>Gerar Fatura Parcial
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAllCheckbox = document.getElementById('selectAll');
            const itemCheckboxes = document.querySelectorAll('.item-checkbox');
            const quantityInputs = document.querySelectorAll('.quantity-input');
            const submitBtn = document.getElementById('submitBtn');

            // Função para calcular total
            function calculateTotals() {
                let selectedTotal = 0;
                let hasSelectedItems = false;

                itemCheckboxes.forEach((checkbox, index) => {
                    if (checkbox.checked) {
                        const quantityInput = document.querySelector(
                            `.quantity-input[data-index="${index}"]`);
                        const unitValue = parseFloat(quantityInput.dataset.unitValue);
                        const quantity = parseFloat(quantityInput.value) || 0;
                        const partialTotal = unitValue * quantity;

                        // Atualizar total parcial do item
                        const partialTotalElement = document.querySelector(
                            `.partial-total[data-index="${index}"]`);
                        if (partialTotalElement) {
                            partialTotalElement.textContent = partialTotal.toLocaleString('pt-BR', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }

                        selectedTotal += partialTotal;
                        hasSelectedItems = true;
                    }
                });

                // Atualizar total geral
                document.getElementById('selectedTotal').textContent = selectedTotal.toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });

                // Habilitar/desabilitar botão de submit
                submitBtn.disabled = !hasSelectedItems;
            }

            // Selecionar/deselecionar todos
            selectAllCheckbox.addEventListener('change', function() {
                itemCheckboxes.forEach((checkbox, index) => {
                    checkbox.checked = selectAllCheckbox.checked;
                    const quantityInput = document.querySelector(
                        `.quantity-input[data-index="${index}"]`);
                    quantityInput.disabled = !checkbox.checked;
                });
                calculateTotals();
            });

            // Evento de mudança nos checkboxes individuais
            itemCheckboxes.forEach((checkbox, index) => {
                checkbox.addEventListener('change', function() {
                    const quantityInput = document.querySelector(
                        `.quantity-input[data-index="${index}"]`);
                    quantityInput.disabled = !checkbox.checked;

                    // Atualizar estado do "selecionar todos"
                    const allChecked = Array.from(itemCheckboxes).every(cb => cb.checked);
                    const anyChecked = Array.from(itemCheckboxes).some(cb => cb.checked);
                    selectAllCheckbox.checked = allChecked;
                    selectAllCheckbox.indeterminate = anyChecked && !allChecked;

                    calculateTotals();
                });
            });

            // Evento de mudança nos inputs de quantidade
            quantityInputs.forEach(input => {
                input.addEventListener('input', calculateTotals);
            });

            // Validação do formulário
            document.getElementById('partialInvoiceForm').addEventListener('submit', function(e) {
                const issueDate = document.getElementById('issue_date').value;
                const dueDate = document.getElementById('due_date').value;

                if (new Date(dueDate) < new Date(issueDate)) {
                    e.preventDefault();
                    alert('A data de vencimento deve ser posterior ou igual à data de emissão.');
                    return false;
                }

                // Verificar se há itens selecionados
                const hasSelectedItems = Array.from(itemCheckboxes).some(cb => cb.checked);
                if (!hasSelectedItems) {
                    e.preventDefault();
                    alert('Selecione pelo menos um item para gerar a fatura parcial.');
                    return false;
                }
            });
        });
    </script>
@endpush
