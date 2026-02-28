@extends('layouts.print')

@section('title', 'Fatura #' . $invoice->code)

@section('actions')
    <a href="{{ route('provider.invoices.show', $invoice->code) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Voltar
    </a>
@endsection

@section('content')
<div class="print-content">
    @php
        // Prestador (Tenant/Provider)
        $provider = $invoice->tenant->provider;
        $providerCommonData = $provider?->commonData;
        $providerContact = $provider?->contact;

        // Cliente
        $customer = $invoice->customer;
        $customerCommonData = $customer?->commonData;
        $customerContact = $customer?->contact;
        $customerAddress = $customer?->address;
    @endphp

    <!-- Header -->
    <div class="mb-4">
        <div class="row align-items-center">
            <!-- Company Data -->
            <div class="col-6">
                <h5 class="fw-bold mb-1">
                    @if($providerCommonData)
                        {{ $providerCommonData->display_name }}
                    @else
                        {{ $invoice->tenant->name }}
                    @endif
                </h5>

                <div class="text-secondary small">
                    @if($providerContact)
                        @php $email = $providerContact->email_business ?? $providerContact->email_personal; @endphp
                        @if($email) <p class="mb-1">{{ $email }}</p> @endif

                        @php $phone = $providerContact->phone_business ?? $providerContact->phone_personal; @endphp
                        @if($phone) <p class="mb-1">Tel: {{ \App\Helpers\MaskHelper::formatPhone($phone) }}</p> @endif

                        @if($providerContact->website) <p class="mb-0">{{ $providerContact->website }}</p> @endif
                    @else
                        <p class="mb-1">{{ $invoice->tenant->users->first()?->email }}</p>
                    @endif
                </div>
            </div>

            <!-- Invoice Number and Info -->
            <div class="col-6 text-end">
                <h4 class="text-primary mb-2 text-uppercase">Fatura #{{ $invoice->code }}</h4>
                <div class="text-secondary small mb-2">
                    <p class="mb-1">Emissão: {{ $invoice->created_at->format('d/m/Y') }}</p>
                    @if($invoice->due_date)
                        <p class="mb-1">Vencimento: {{ $invoice->due_date->format('d/m/Y') }}</p>
                    @endif
                </div>
                <span class="badge" style="background-color: {{ $invoice->status->getColor() }};">
                    {{ $invoice->status->label() }}
                </span>
            </div>
        </div>
    </div>

    <hr class="my-4">

    <!-- Grid de Dados (Cliente e Serviço) -->
    <div class="row mb-4">
        <!-- Customer Data -->
        <div class="col-6">
            <h6 class="text-secondary border-bottom pb-2 text-uppercase fw-bold small">Dados do Cliente</h6>
            <div class="mt-2">
                <p class="fw-bold mb-1">
                    @if($customerCommonData)
                        {{ $customerCommonData->display_name }}
                    @else
                        {{ $customer->user->name ?? 'Cliente não identificado' }}
                    @endif
                </p>

                @if($customerCommonData?->cpf)
                    <p class="text-secondary small mb-1">CPF: {{ \App\Helpers\MaskHelper::formatCPF($customerCommonData->cpf) }}</p>
                @endif
                @if($customerCommonData?->cnpj)
                    <p class="text-secondary small mb-1">CNPJ: {{ \App\Helpers\MaskHelper::formatCNPJ($customerCommonData->cnpj) }}</p>
                @endif

                @if($customerAddress)
                    <p class="text-secondary small mb-1">
                        {{ $customerAddress->address }}, {{ $customerAddress->address_number }}<br>
                        {{ $customerAddress->neighborhood }} - {{ $customerAddress->city }}/{{ $customerAddress->state }}
                    </p>
                @endif

                @php
                    $cPhone = $customerContact?->phone_business ?? $customerContact?->phone_personal;
                    $cEmail = $customerContact?->email_business ?? $customerContact?->email_personal;
                @endphp

                @if($cPhone)
                    <p class="text-secondary small mb-1">Tel: {{ \App\Helpers\MaskHelper::formatPhone($cPhone) }}</p>
                @endif
                @if($cEmail)
                    <p class="text-secondary small mb-0">Email: {{ $cEmail }}</p>
                @endif
            </div>
        </div>

        <!-- Associated Service -->
        <div class="col-6">
            @if($invoice->service)
                <h6 class="text-secondary border-bottom pb-2 text-uppercase fw-bold small">Serviço Associado</h6>
                <div class="mt-2">
                    <p class="mb-1"><strong>Código:</strong> #{{ $invoice->service->code }}</p>
                    @if($invoice->service->description)
                        <p class="text-secondary small mb-0"><strong>Descrição:</strong><br>{{ $invoice->service->description }}</p>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Items Table -->
    <div class="mb-4">
        <h6 class="text-secondary border-bottom pb-2 text-uppercase fw-bold small">Itens da Fatura</h6>
        <div class="table-responsive mt-2">
            <table class="table table-sm table-striped border">
                <thead class="table-dark">
                    <tr>
                        <th>Produto/Serviço</th>
                        <th class="text-center" style="width: 80px;">Qtd</th>
                        <th class="text-end" style="width: 120px;">Unitário</th>
                        <th class="text-end" style="width: 120px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoice->invoiceItems as $item)
                        <tr>
                            <td>
                                <span class="fw-bold text-dark">{{ $item->product->name ?? 'Item' }}</span>
                                @if($item->description)
                                    <br><small class="text-muted">{{ $item->description }}</small>
                                @endif
                            </td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($item->unit_price, 2, true) }}</td>
                            <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($item->total, 2, true) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-3 text-muted">Nenhum item encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Financial Summary -->
    <div class="row justify-content-end">
        <div class="col-5">
            <div class="p-3 border rounded bg-light">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary small fw-bold text-uppercase">Subtotal</span>
                    <span class="fw-bold text-dark">{{ \App\Helpers\CurrencyHelper::format($invoice->subtotal, 2, true) }}</span>
                </div>

                @if($invoice->discount > 0)
                    <div class="d-flex justify-content-between mb-2 text-danger">
                        <span class="small fw-bold text-uppercase">Desconto</span>
                        <span class="fw-bold">- {{ \App\Helpers\CurrencyHelper::format($invoice->discount, 2, true) }}</span>
                    </div>
                @endif

                <hr class="my-2">

                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-primary fw-black text-uppercase h6 mb-0">Total Final</span>
                    <span class="text-primary fw-black h5 mb-0">{{ \App\Helpers\CurrencyHelper::format($invoice->total, 2, true) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Notes -->
    @if($invoice->notes)
        <div class="mt-4 pt-4 border-top">
            <h6 class="text-secondary text-uppercase fw-bold small mb-2">Observações</h6>
            <div class="p-3 bg-light rounded small text-muted fst-italic">
                {{ $invoice->notes }}
            </div>
        </div>
    @endif
</div>
@endsection

@section('styles')
<style>
    .fw-black { font-weight: 900; }
    .print-content { font-size: 0.9rem; color: #333; }
    .table-dark { background-color: #212529 !important; color: #fff !important; }

    @media print {
        .btn, .actions-container { display: none !important; }
        .bg-light { background-color: #f8f9fa !important; -webkit-print-color-adjust: exact; }
        .table-striped tbody tr:nth-of-type(odd) { background-color: rgba(0,0,0,.05) !important; }
        .text-primary { color: #0d6efd !important; }
        .badge { border: 1px solid #dee2e6 !important; color: #000 !important; }
    }
</style>
@endsection
