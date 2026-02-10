@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <x-layout.page-header
            title="Gerar Fatura para o Serviço #{{ $serviceCode }}"
            icon="receipt"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Faturas' => route('provider.invoices.dashboard'),
                'Serviço #' . $serviceCode => route('provider.services.show', $serviceCode),
                'Gerar Fatura' => '#'
            ]">
            <p class="text-muted mb-0">Confirme os dados para gerar a fatura do serviço</p>
        </x-layout.page-header>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <!-- Informações do Cliente e Serviço -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <h5 class="text-uppercase text-muted small fw-bold mb-3">Cliente</h5>
                        <address class="mb-0">
                            <strong class="h5 d-block mb-1">{{ $invoiceData['customer_name'] ?? 'Cliente' }}</strong>
                            @php
                                $customer = $invoiceData['customer_details'];
                                $address = $customer?->address;
                                $document = $customer?->document;
                            @endphp

                            @if ($document)
                                <span class="d-block mb-1"><i class="bi bi-person-vcard me-2"></i>{{ $document }}</span>
                            @endif

                            @if ($address)
                                <span class="d-block text-muted">
                                    <i class="bi bi-geo-alt me-2"></i>
                                    @if ($address->address)
                                        {{ $address->address }}@if ($address->address_number), {{ $address->address_number }}@endif<br>
                                    @endif

                                    @if ($address->neighborhood)
                                        <span class="ms-4">{{ $address->neighborhood }}</span><br>
                                    @endif

                                    @if ($address->city)
                                        <span class="ms-4">{{ $address->city }}@if ($address->state) - {{ $address->state }}@endif</span><br>
                                    @endif

                                    @if ($address->cep)
                                        <span class="ms-4">CEP: {{ \App\Helpers\MaskHelper::formatCEP($address->cep) }}</span>
                                    @endif
                                </span>
                            @else
                                <span class="text-muted italic">Endereço não informado</span>
                            @endif
                        </address>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h5 class="text-uppercase text-muted small fw-bold mb-3">Detalhes da Fatura</h5>
                        <div class="h4 fw-bold text-primary mb-2">#{{ 'FAT-' . date('Ymd') . '-XXXX' }}</div>
                        <p class="mb-1"><strong>Emissão:</strong> {{ now()->format('d/m/Y') }}</p>
                        <p class="mb-1"><strong>Vencimento:</strong>
                            {{ \Carbon\Carbon::parse($invoiceData['due_date'])->format('d/m/Y') }}</p>
                        <p class="mb-0"><strong>Referente ao Serviço:</strong> #{{ $invoiceData['service_code'] }}</p>
                    </div>
                </div>

                @if ($invoiceData['status'] === 'partial')
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Fatura Parcial:</strong>
                        {{ $invoiceData['notes'] ?? 'Esta fatura corresponde à conclusão parcial do serviço.' }}
                    </div>
                @endif

                <!-- Itens da Fatura -->
                <div class="table-responsive-sm">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="text-nowrap">Item / Descrição</th>
                                <th class="text-center" style="width: 80px;">Qtd.</th>
                                <th class="text-end" style="width: 120px;">Unitário</th>
                                <th class="text-end" style="width: 120px;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoiceData['items'] as $item)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-wrap">{{ $item->product->name ?? 'Produto' }}</div>
                                        @if ($item->product->description)
                                            <div class="small text-muted text-wrap">{{ $item->product->description }}</div>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end text-nowrap">R$ {{ number_format($item->unit_value, 2, ',', '.') }}</td>
                                    <td class="text-end text-nowrap fw-bold">R$ {{ number_format($item->total, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Resumo -->
                <div class="row mt-4">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <p class="text-muted mb-1"><strong>Notas e Termos:</strong></p>
                        <textarea class="form-control form-control-sm" name="notes" rows="3" placeholder="Observações adicionais para a fatura...">{{ old('notes', 'Pagamento a ser realizado na data de vencimento.') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <div class="table-responsive-sm">
                            <table class="table table-sm table-borderless">
                                <tbody>
                                    <tr>
                                        <th class="text-end py-1" style="width:70%">Subtotal:</th>
                                        <td class="text-end py-1">
                                            <span id="display_subtotal">R$ {{ \App\Helpers\CurrencyHelper::format($invoiceData['subtotal']) }}</span>
                                            <input type="hidden" id="subtotal_value" value="{{ $invoiceData['subtotal'] }}">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-end py-1 align-middle">Desconto (R$):</th>
                                        <td class="text-end py-1">
                                            <input type="text" class="form-control form-control-sm text-end text-danger d-inline-block currency-brl"
                                                style="width: 120px;" name="discount_display" id="discount_input"
                                                value="{{ old('discount', \App\Helpers\CurrencyHelper::format($invoiceData['discount'] ?? 0)) }}">
                                        </td>
                                    </tr>
                                    <tr class="border-top">
                                        <th class="text-end pt-2 h5 mb-0">Total Final:</th>
                                        <td class="text-end pt-2 h5 mb-0 text-success fw-bold">
                                            <span id="display_total">R$ {{ \App\Helpers\CurrencyHelper::format($invoiceData['total']) }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- Formulário de Confirmação -->
                <form action="{{ route('provider.invoices.store.from-service') }}" method="POST" id="invoiceForm">
                    @csrf
                    <input type="hidden" name="service_code" value="{{ $serviceCode }}">
                    <input type="hidden" name="discount" id="hidden_discount" value="{{ old('discount', $invoiceData['discount'] ?? 0) }}">
                    <input type="hidden" name="notes" id="hidden_notes">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="issue_date" class="form-label">Data de Emissão *</label>
                            <input type="date" class="form-control @error('issue_date') is-invalid @enderror"
                                name="issue_date" id="issue_date"
                                value="{{ old('issue_date', now()->format('Y-m-d')) }}" required>
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

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('provider.services.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Voltar
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle-fill me-2"></i>Confirmar e Gerar Fatura
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
            const discountInput = document.getElementById('discount_input');
            const hiddenDiscount = document.getElementById('hidden_discount');
            const hiddenNotes = document.getElementById('hidden_notes');
            const notesTextarea = document.querySelector('textarea[name="notes"]');
            const subtotalValue = parseFloat(document.getElementById('subtotal_value').value);
            const displayTotal = document.getElementById('display_total');

            // Inicializar VanillaMask se disponível
            if (window.VanillaMask) {
                new VanillaMask(discountInput, 'currency');
            }

            // Limpar campo ao focar se for zero para facilitar a digitação
            discountInput.addEventListener('focus', function() {
                let value = 0;
                if (window.parseCurrencyBRLToNumber) {
                    value = window.parseCurrencyBRLToNumber(this.value);
                } else {
                    value = parseFloat(this.value.replace(/\./g, '').replace(',', '.')) || 0;
                }

                if (value === 0) {
                    this.value = '';
                } else {
                    this.select(); // Se já tiver valor, seleciona tudo para facilitar a troca
                }
            });

            // Se sair do campo e estiver vazio, volta para 0,00 formatado
            discountInput.addEventListener('blur', function() {
                if (this.value === '') {
                    if (window.formatCurrencyBRL) {
                        this.value = window.formatCurrencyBRL(0);
                    } else {
                        this.value = '0,00';
                    }
                }
                updateTotal();
            });

            function updateTotal() {
                let discountValue = 0;
                if (window.parseCurrencyBRLToNumber) {
                    discountValue = window.parseCurrencyBRLToNumber(discountInput.value);
                } else {
                    discountValue = parseFloat(discountInput.value.replace(/\./g, '').replace(',', '.')) || 0;
                }

                // Impede que o desconto seja maior que o subtotal
                if (discountValue > subtotalValue) {
                    discountValue = subtotalValue;
                    if (window.formatCurrencyBRL) {
                        discountInput.value = window.formatCurrencyBRL(discountValue);
                    } else {
                        discountInput.value = discountValue.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    }
                    alert('O desconto não pode ser maior que o subtotal da fatura.');
                }

                let total = subtotalValue - discountValue;
                if (total < 0) total = 0;

                hiddenDiscount.value = discountValue;

                if (window.formatCurrencyBRL) {
                    displayTotal.textContent = 'R$ ' + window.formatCurrencyBRL(total);
                } else {
                    displayTotal.textContent = 'R$ ' + total.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
            }

            discountInput.addEventListener('input', updateTotal);

            // Validação do formulário
            document.getElementById('invoiceForm').addEventListener('submit', function(e) {
                const issueDate = document.getElementById('issue_date').value;
                const dueDate = document.getElementById('due_date').value;

                // Sincronizar campos que estão fora do form original
                hiddenNotes.value = notesTextarea.value;

                let discountValue = 0;
                if (window.parseCurrencyBRLToNumber) {
                    discountValue = window.parseCurrencyBRLToNumber(discountInput.value);
                } else {
                    discountValue = parseFloat(discountInput.value.replace(/\./g, '').replace(',', '.')) || 0;
                }
                hiddenDiscount.value = discountValue;

                if (new Date(dueDate) < new Date(issueDate)) {
                    e.preventDefault();
                    alert('A data de vencimento deve ser posterior ou igual à data de emissão.');
                    return false;
                }
            });

            // Inicializar total
            updateTotal();
        });
    </script>
@endpush
