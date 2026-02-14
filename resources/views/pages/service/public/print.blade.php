@extends('layouts.print')

@section('title', 'Serviço #' . $service->code)

@section('actions')
    @if(request('token'))
        <a href="{{ route('services.public.view-status', ['code' => $service->code, 'token' => request('token')]) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Voltar
        </a>
    @else
        <a href="{{ route('provider.services.show', $service->code) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Voltar
        </a>
    @endif
@endsection

@section('content')
<div class="print-content">
    <!-- Header -->
    <div class="mb-4">
        <div class="row">
            <!-- Company Data -->
            <div class="col-6">
                @php
                    $provider = $service->budget?->provider;
                    $providerCommonData = $provider?->commonData;
                @endphp
                <h5 class="fw-bold mb-2">
                    @if($providerCommonData)
                        {{ $providerCommonData->display_name }}
                    @else
                        {{ $service->tenant->name ?? 'Easy Budget' }}
                    @endif
                </h5>
                <div class="text-secondary small">
                    @if($provider?->contact)
                        <p class="mb-1">{{ $provider->contact->email_business ?? $provider->contact->email_personal }}</p>
                        @php
                            $phone = $provider->contact->phone_business ?? $provider->contact->phone_personal;
                        @endphp
                        @if($phone)
                            <p class="mb-1">Tel: {{ \App\Helpers\MaskHelper::formatPhone($phone) }}</p>
                        @endif
                        @if($provider->contact->website)
                            <p class="mb-0">{{ $provider->contact->website }}</p>
                        @endif
                    @elseif($service->tenant->contact ?? false)
                        <p class="mb-1">{{ $service->tenant->contact->email }}</p>
                        @if($service->tenant->contact->phone)
                            <p class="mb-1">Tel: {{ $service->tenant->contact->phone }}</p>
                        @endif
                        @if($service->tenant->contact->website)
                            <p class="mb-0">{{ $service->tenant->contact->website }}</p>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Service Number and Info -->
            <div class="col-6 text-end">
                <h4 class="text-primary mb-2">SERVIÇO #{{ $service->code }}</h4>
                <div class="text-secondary small">
                    <p class="mb-1">Emissão: {{ $service->created_at->format( 'd/m/Y' ) }}</p>
                    @if($service->due_date)
                        <p class="mb-1">Entrega: {{ $service->due_date->format( 'd/m/Y' ) }}</p>
                    @endif
                </div>
                @php
                    $status = $service->status;
                @endphp
                <span class="badge" style="background-color: {{ $status?->getColor() ?? '#6c757d' }};">
                    {{ $status?->getDescription() ?? $service->status }}
                </span>
            </div>
        </div>
    </div>

    <hr class="my-4">

    <!-- Customer Data -->
    <div class="mb-4">
        <h6 class="text-secondary border-bottom pb-2">DADOS DO CLIENTE</h6>
        <div class="row mt-3">
            @php
                $customer = $service->budget?->customer;
                $customerCommonData = $customer?->commonData;
                $customerContact = $customer?->contact;
                $customerAddress = $customer?->address;
            @endphp
            <div class="col-6">
                <p class="fw-medium mb-1">
                    @if($customerCommonData)
                        {{ $customerCommonData->display_name }}
                    @else
                        Cliente não identificado
                    @endif
                </p>
                @if($customerCommonData?->cpf)
                    <p class="text-secondary small mb-1">CPF: {{ \App\Helpers\MaskHelper::formatCPF($customerCommonData->cpf) }}</p>
                @endif
                @if($customerCommonData?->cnpj)
                    <p class="text-secondary small mb-1">CNPJ: {{ \App\Helpers\MaskHelper::formatCNPJ($customerCommonData->cnpj) }}</p>
                @endif
                @if($customerAddress)
                    <p class="text-secondary small mb-0">
                        {{ $customerAddress->address }}, {{ $customerAddress->address_number }}<br>
                        {{ $customerAddress->neighborhood }} - {{ $customerAddress->city }}/{{ $customerAddress->state }}
                    </p>
                @endif
            </div>
            <div class="col-6 text-end">
                @php
                    $phone = $customerContact?->phone_personal ?? $customerContact?->phone_business;
                    $email = $customerContact?->email_personal ?? $customerContact?->email_business;
                @endphp
                @if($phone)
                    <p class="text-secondary small mb-1">Tel: {{ \App\Helpers\MaskHelper::formatPhone($phone) }}</p>
                @endif
                @if($email)
                    <p class="text-secondary small mb-1">Email: {{ $email }}</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Service Details -->
    <div class="mb-4">
        <h6 class="text-secondary border-bottom pb-2">DETALHES DO SERVIÇO</h6>
        <div class="row mt-3">
            <div class="col-md-6">
                <p class="mb-2"><strong>Categoria:</strong> {{ $service->category?->name ?? 'Não informada' }}</p>
                @if( $service->description )
                    <p class="mb-2"><strong>Descrição:</strong><br>{{ $service->description }}</p>
                @endif
            </div>
            <div class="col-md-6">
                <p class="mb-2"><strong>Orçamento Origem:</strong> #{{ $service->budget?->code }}</p>
                @if($service->budget?->description)
                    <p class="mb-2"><strong>Resumo Orçamento:</strong> {{ $service->budget->description }}</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Service Items -->
    @if( $service->serviceItems && $service->serviceItems->count() > 0 )
        <div class="mb-4">
            <h6 class="text-secondary border-bottom pb-2">ITENS DO SERVIÇO</h6>
            <div class="table-responsive mt-3">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>Produto/Serviço</th>
                            <th class="text-center">Qtd</th>
                            <th class="text-end">Vlr. Unitário</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach( $service->serviceItems as $item )
                            <tr>
                                <td>{{ $item->product?->name ?? 'Item não identificado' }}</td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($item->unit_value) }}</td>
                                <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($item->total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Financial Summary -->
    <div class="mb-4">
        <h6 class="text-secondary border-bottom pb-2">RESUMO FINANCEIRO</h6>
        <div class="row mt-3">
            <div class="col-6">
                <p class="mb-1"><strong>Subtotal:</strong> R$ {{ \App\Helpers\CurrencyHelper::format($service->total) }}</p>
                <p class="mb-1"><strong>Desconto:</strong> R$ {{ \App\Helpers\CurrencyHelper::format($service->discount) }}</p>
            </div>
            <div class="col-6 text-end">
                <div class="p-3 border rounded bg-light">
                    <h5 class="mb-0 fw-bold">Total Final: R$ {{ \App\Helpers\CurrencyHelper::format($service->total - $service->discount) }}</h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Notes & Terms -->
    @if( $service->budget?->payment_terms || $service->budget?->notes )
        <div class="row mt-4">
            @if( $service->budget?->payment_terms )
                <div class="col-6">
                    <h6 class="text-secondary border-bottom pb-2">CONDIÇÕES DE PAGAMENTO</h6>
                    <p class="small mt-2">{{ $service->budget->payment_terms }}</p>
                </div>
            @endif
            @if( $service->budget?->notes )
                <div class="col-6">
                    <h6 class="text-secondary border-bottom pb-2">OBSERVAÇÕES</h6>
                    <p class="small mt-2">{{ $service->budget->notes }}</p>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection

@section('footer')
    @if(isset($verificationUrl) && isset($qrDataUri) && $qrDataUri)
        <div class="text-center mt-4 pt-4 border-top">
            <p class="text-muted small mb-2">Verifique a autenticidade deste documento:</p>
            <div class="d-flex justify-content-center align-items-center gap-3">
                <img src="{{ $qrDataUri }}" alt="QR Code" width="100">
                <div class="text-start">
                    <p class="small mb-0">Escaneie o código ou acesse:</p>
                    <a href="{{ $verificationUrl }}" class="small text-decoration-none">{{ $verificationUrl }}</a>
                </div>
            </div>
        </div>
    @endif
@endsection
