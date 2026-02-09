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
                        <p class="small">Pagamento a ser realizado na data de vencimento.</p>
                    </div>
                    <div class="col-md-6">
                        <div class="table-responsive-sm">
                            <table class="table table-sm table-borderless">
                                <tbody>
                                    <tr>
                                        <th class="text-end py-1" style="width:70%">Subtotal:</th>
                                        <td class="text-end py-1">R$
                                            {{ \App\Helpers\CurrencyHelper::format($invoiceData['subtotal']) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-end py-1">Desconto:</th>
                                        <td class="text-end py-1 text-danger">- R$
                                            {{ \App\Helpers\CurrencyHelper::format($invoiceData['discount']) }}</td>
                                    </tr>
                                    <tr class="border-top">
                                        <th class="text-end pt-2 h5 mb-0">Total:</th>
                                        <td class="text-end pt-2 h5 mb-0 text-success fw-bold">R$
                                            {{ \App\Helpers\CurrencyHelper::format($invoiceData['total']) }}</td>
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
            // Validação do formulário
            document.getElementById('invoiceForm').addEventListener('submit', function(e) {
                const issueDate = document.getElementById('issue_date').value;
                const dueDate = document.getElementById('due_date').value;

                if (new Date(dueDate) < new Date(issueDate)) {
                    e.preventDefault();
                    alert('A data de vencimento deve ser posterior ou igual à data de emissão.');
                    return false;
                }
            });
        });
    </script>
@endpush
