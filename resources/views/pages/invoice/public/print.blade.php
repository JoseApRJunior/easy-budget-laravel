@extends('layouts.print')

@section('title', 'Fatura ' . $invoice->code . ' - ' . ($invoice->tenant->name ?? 'Easy Budget'))

@section('styles')
    .status-badge {
      font-size: 16px;
      padding: 8px 16px;
      border-radius: 20px;
    }

    .payment-info {
      background-color: #fef9e7;
      border: 1px solid #f9e79f;
      border-radius: 8px;
      padding: 15px;
      margin: 15px 0;
    }
@endsection

@section('actions')
    <a href="{{ $invoice->getPublicUrl() }}" class="btn btn-outline-secondary ms-2">
        <i class="bi bi-arrow-left me-2"></i>Voltar
    </a>
@endsection

@section('content')
    <div class="invoice-container">
        @php
            // Prestador (Tenant/Provider)
            $provider = $invoice->tenant;
            
            // Cliente
            $customer = $invoice->customer;
            $customerCommonData = $customer?->commonData;
            $customerContact = $customer?->contact;
            $customerAddress = $customer?->address;
        @endphp

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
            <div>
                @if($invoice->tenant->user?->logo)
                    <img src="{{ asset('storage/' . $invoice->tenant->user->logo) }}" alt="Logo" style="max-height: 80px;">
                @else
                    <h1 class="text-primary fw-bold mb-0">{{ config('app.name') }}</h1>
                @endif
            </div>
            <div class="text-end">
                <h1 class="text-uppercase h3 fw-black mb-0">Fatura</h1>
                <p class="text-muted fw-bold mb-0">#{{ $invoice->code }}</p>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="row g-4 mb-4">
            <div class="col-6">
                <h6 class="text-muted text-uppercase fw-bold border-bottom pb-2 mb-3">Prestador</h6>
                <h5 class="fw-bold mb-2">{{ $invoice->tenant_company_name }}</h5>
                <p class="text-secondary small mb-1">{{ $invoice->tenant->email }}</p>
                @if($invoice->tenant->phone)
                    <p class="text-secondary small mb-1">Tel: {{ \App\Helpers\MaskHelper::formatPhone($invoice->tenant->phone) }}</p>
                @endif
            </div>

            <div class="col-6">
                <h6 class="text-muted text-uppercase fw-bold border-bottom pb-2 mb-3">Cliente</h6>
                <h5 class="fw-bold mb-2">
                    @if($customerCommonData)
                        {{ $customerCommonData->display_name }}
                    @else
                        {{ $invoice->customer->user->name ?? 'Cliente não identificado' }}
                    @endif
                </h5>
                
                @if($customerCommonData?->cpf)
                    <p class="text-secondary small mb-1">CPF: {{ \App\Helpers\MaskHelper::formatCPF($customerCommonData->cpf) }}</p>
                @endif
                @if($customerCommonData?->cnpj)
                    <p class="text-secondary small mb-1">CNPJ: {{ \App\Helpers\MaskHelper::formatCNPJ($customerCommonData->cnpj) }}</p>
                @endif
                
                @php $email = $customerCommonData?->email_business ?? $invoice->customer->user->email; @endphp
                @if($email)
                    <p class="text-secondary small mb-1">{{ $email }}</p>
                @endif
                
                @php $phone = $customerContact?->phone_business ?? $customerContact?->phone; @endphp
                @if($phone)
                    <p class="text-secondary small mb-1">Tel: {{ \App\Helpers\MaskHelper::formatPhone($phone) }}</p>
                @endif

                @if($customerAddress)
                    <p class="text-secondary small mb-0">
                        {{ $customerAddress->address }}, {{ $customerAddress->address_number }}<br>
                        {{ $customerAddress->neighborhood }} - {{ $customerAddress->city }}/{{ $customerAddress->state }}
                    </p>
                @endif
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-4">
                <div class="info-box h-100 bg-light p-3 border rounded">
                    <span class="text-muted small text-uppercase fw-bold d-block mb-1">Data de Emissão</span>
                    <span class="fw-bold">{{ $invoice->created_at->format('d/m/Y') }}</span>
                </div>
            </div>
            <div class="col-4">
                <div class="info-box h-100 bg-light p-3 border rounded">
                    <span class="text-muted small text-uppercase fw-bold d-block mb-1">Data de Vencimento</span>
                    <span class="fw-bold">{{ $invoice->due_date?->format('d/m/Y') ?? 'N/A' }}</span>
                </div>
            </div>
            <div class="col-4">
                <div class="info-box h-100 bg-light p-3 border rounded text-center">
                    <span class="text-muted small text-uppercase fw-bold d-block mb-1">Status</span>
                    <span class="badge" style="background-color: {{ $invoice->status->getColor() }}">
                        {{ $invoice->status->label() }}
                    </span>
                </div>
            </div>
        </div>

        @if($invoice->service)
            <div class="info-box border-start border-4 border-primary bg-light p-3 mb-4 rounded">
                <h6 class="text-muted text-uppercase fw-bold mb-2">Serviço Associado</h6>
                <p class="mb-0"><strong>{{ $invoice->service->code }}</strong> - {{ $invoice->service->description }}</p>
            </div>
        @endif

        <!-- Itens da fatura -->
        @if($invoice->invoiceItems && $invoice->invoiceItems->count() > 0)
            <div class="mb-4">
                <table class="table table-striped border">
                    <thead class="table-dark">
                        <tr>
                            <th width="50%">Descrição do Item</th>
                            <th class="text-center" width="10%">Qtd</th>
                            <th class="text-end" width="20%">Vlr. Unitário</th>
                            <th class="text-end" width="20%">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->invoiceItems as $item)
                            <tr>
                                <td>
                                    <strong>{{ $item->product?->name ?? 'Item' }}</strong>
                                    @if($item->description)
                                        <br><small class="text-muted">{{ $item->description }}</small>
                                    @endif
                                </td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($item->unit_price) }}</td>
                                <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($item->total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Totals and Payment Info -->
        <div class="row g-4 mb-4">
            <div class="col-7">
                @if($invoice->payment_method || $invoice->transaction_date)
                    <div class="info-box bg-light p-3 border rounded h-100">
                        <h6 class="text-muted text-uppercase fw-bold mb-3 border-bottom pb-2">Informações de Pagamento</h6>
                        @if($invoice->payment_method)
                            <p class="mb-1"><strong>Método:</strong> {{ $invoice->payment_method }}</p>
                        @endif
                        @if($invoice->transaction_date)
                            <p class="mb-1"><strong>Data:</strong> {{ $invoice->transaction_date->format('d/m/Y H:i') }}</p>
                        @endif
                        @if($invoice->payment_id)
                            <p class="mb-0"><strong>ID Transação:</strong> <small class="text-muted">{{ $invoice->payment_id }}</small></p>
                        @endif
                    </div>
                @endif
            </div>
            <div class="col-5">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Subtotal:</span>
                    <span class="fw-bold">{{ \App\Helpers\CurrencyHelper::format($invoice->subtotal) }}</span>
                </div>
                @if($invoice->discount > 0)
                    <div class="d-flex justify-content-between mb-2 text-danger">
                        <span>Desconto:</span>
                        <span class="fw-bold">- {{ \App\Helpers\CurrencyHelper::format($invoice->discount) }}</span>
                    </div>
                @endif
                <div class="d-flex justify-content-between border-top pt-3 mt-2 h4 text-primary">
                    <span class="fw-black">Total Geral:</span>
                    <span class="fw-black">{{ \App\Helpers\CurrencyHelper::format($invoice->total) }}</span>
                </div>
            </div>
        </div>

        @if($invoice->notes)
            <div class="info-box bg-light border-warning p-3 mb-4 rounded border-start border-4">
                <h6 class="text-warning-emphasis text-uppercase fw-bold mb-2">Observações</h6>
                <p class="mb-0 fst-italic">{{ $invoice->notes }}</p>
            </div>
        @endif
    </div>
@endsection

@section('footer')
    <div class="text-center border-top pt-3 mt-5">
        <p class="text-muted small mb-1">
            <strong>{{ $invoice->tenant_company_name }}</strong>
        </p>
        <p class="text-muted small mb-0">
            Fatura gerada em {{ now()->format('d/m/Y H:i') }} - {{ config('app.name') }}
        </p>
    </div>
@endsection
