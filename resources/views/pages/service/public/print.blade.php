@extends('layouts.print')

@section('title', 'Serviço ' . $service->code . ' - Easy Budget')

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
    <!-- Cabeçalho -->
    <div class="print-header text-center">
        <h1 class="mb-2">Comprovante de Serviço</h1>
        <h3 class="text-muted">Serviço #{{ $service->code }}</h3>
    </div>

    <!-- Status e informações principais -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="info-box h-100">
                <h5 class="text-muted mb-3">
                    <i class="bi bi-person-circle me-2"></i>
                    Dados do Cliente
                </h5>
                @if($service->budget?->customer?->commonData)
                    <strong>{{ $service->budget->customer->commonData->first_name }}
                        {{ $service->budget->customer->commonData->last_name }}</strong><br>
                @else
                    <strong>Cliente não identificado</strong><br>
                @endif

                @if($service->budget?->customer?->contact)
                    <span class="text-muted">{{ $service->budget->customer->contact->email_personal ?? $service->budget->customer->contact->email_business }}</span><br>
                    @php
                        $phone = $service->budget->customer->contact->phone_personal ?? $service->budget->customer->contact->phone_business;
                    @endphp
                    @if($phone)
                        <span class="text-muted">{{ \App\Helpers\MaskHelper::formatPhone($phone) }}</span><br>
                    @endif
                @endif

                @if($service->budget?->customer?->address)
                    <small class="text-muted">
                        {{ $service->budget->customer->address->address }},
                        {{ $service->budget->customer->address->address_number }},
                        {{ $service->budget->customer->address->neighborhood }},
                        {{ $service->budget->customer->address->city }} - {{ $service->budget->customer->address->state }}
                    </small>
                @endif
            </div>
        </div>

        <div class="col-md-6">
            <div class="info-box h-100">
                <h5 class="text-muted mb-3">
                    <i class="bi bi-receipt me-2"></i>
                    Orçamento
                </h5>
                <strong>Código:</strong> {{ $service->budget?->code }}<br>
                <strong>Descrição:</strong> {{ $service->budget?->description }}<br>
                <strong>Total do Orçamento:</strong> {{ \App\Helpers\CurrencyHelper::format($service->budget?->total) }}<br>
                <strong>Status:</strong>
                <x-ui.status-badge :item="$service->budget" />
            </div>
        </div>
    </div>

    <!-- Detalhes do serviço -->
    <div class="info-box">
        <h5 class="text-muted mb-3">
            <i class="bi bi-tools me-2"></i>
            Detalhes do Serviço
        </h5>

        <div class="row">
            <div class="col-md-6">
                <strong>Categoria:</strong><br>
                {{ $service->category?->name ?? 'Não informada' }}<br><br>

                @if( $service->description )
                    <strong>Descrição:</strong><br>
                    {{ $service->description }}<br><br>
                @endif

                <strong>Prazo de Entrega:</strong><br>
                @if( $service->due_date )
                    {{ \Carbon\Carbon::parse( $service->due_date )->format( 'd/m/Y' ) }}
                @else
                    A combinar
                @endif
            </div>

            <div class="col-md-6">
                <strong>Status Atual:</strong><br>
                <x-ui.status-badge :item="$service" class="fs-5 mb-3" />
                <br><br>

                <strong>Valor do Serviço:</strong><br>
                {{ \App\Helpers\CurrencyHelper::format($service->total) }}<br><br>

                <strong>Desconto:</strong><br>
                {{ \App\Helpers\CurrencyHelper::format($service->discount) }}<br><br>

                <div class="total-highlight" style="background-color: #cfe2ff; border: 2px solid #2196f3; border-radius: 8px; padding: 15px; text-align: center; margin: 20px 0;">
                    <strong class="fs-4">Valor Final:
                        {{ \App\Helpers\CurrencyHelper::format($service->total - $service->discount) }}</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Itens do serviço (se houver) -->
    @if( $service->serviceItems && $service->serviceItems->count() > 0 )
        <div class="info-box">
            <h5 class="text-muted mb-3">
                <i class="bi bi-list-ul me-2"></i>
                Itens do Serviço
            </h5>

            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th class="text-center">Quantidade</th>
                            <th class="text-end">Valor Unitário</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach( $service->serviceItems as $item )
                            <tr>
                                <td>{{ $item->product?->name ?? 'Produto não encontrado' }}</td>
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

    <!-- Observações -->
    @if( $service->budget?->payment_terms )
        <div class="info-box">
            <h5 class="text-muted mb-3">
                <i class="bi bi-file-text me-2"></i>
                Condições de Pagamento
            </h5>
            {{ $service->budget->payment_terms }}
        </div>
    @endif

    @if( $service->budget?->notes )
        <div class="info-box">
            <h5 class="text-muted mb-3">
                <i class="bi bi-chat-left-text me-2"></i>
                Observações Adicionais
            </h5>
            {{ $service->budget->notes }}
        </div>
    @endif
@endsection

@section('footer')
    @if(isset($verificationUrl) && isset($qrDataUri) && $qrDataUri)
        <div class="mt-3">
            <p class="text-muted">Verifique a autenticidade:</p>
            <p><a href="{{ $verificationUrl }}">{{ $verificationUrl }}</a></p>
            <img src="{{ $qrDataUri }}" alt="QR Code de verificação" width="140" height="140">
        </div>
    @endif
@endsection
