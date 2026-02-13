@extends('layouts.print')

@section('title', 'Fatura ' . $invoice->code . ' - ' . ($invoice->tenant->name ?? 'Easy Budget'))

@section('styles')
    .status-badge {
      font-size: 16px;
      padding: 8px 16px;
      border-radius: 20px;
    }

    .payment-info {
      background-color: #ffeeba;
      border: 1px solid #d4ac0d;
      border-radius: 8px;
      padding: 15px;
      margin: 15px 0;
      color: #000;
    }
@endsection

@section('actions')
    <a href="{{ $invoice->getPublicUrl() }}" class="btn btn-outline-secondary ms-2">
        <i class="bi bi-arrow-left me-2"></i>Voltar
    </a>
@endsection

@section('content')
    <!-- Cabeçalho -->
    <div class="print-header text-center">
      <h1 class="mb-2">{{ $invoice->tenant->name ?? 'Easy Budget' }}</h1>
      <h3 class="text-muted">Fatura #{{ $invoice->code }}</h3>
    </div>

    <!-- Status e informações principais -->
    <div class="row mb-4">
      <div class="col-md-6">
        <div class="info-box h-100">
          <h5 class="text-muted mb-3">
            <i class="bi bi-person-circle me-2"></i>
            Dados do Cliente
          </h5>
          @if($invoice->customer?->commonData)
            <strong>{{ $invoice->customer->commonData->first_name }}
              {{ $invoice->customer->commonData->last_name }}</strong><br>
          @else
            <strong>Cliente não identificado</strong><br>
          @endif

          @if($invoice->customer?->contact)
            <span class="text-muted">{{ $invoice->customer->contact->email_personal ?? $invoice->customer->contact->email_business }}</span><br>
            @php
              $phone = $invoice->customer->contact->phone_personal ?? $invoice->customer->contact->phone_business;
            @endphp
            @if($phone)
              <span class="text-muted">{{ \App\Helpers\MaskHelper::formatPhone($phone) }}</span><br>
            @endif
          @endif

          @if($invoice->customer?->address)
            <small class="text-muted">
              {{ $invoice->customer->address->address }},
              {{ $invoice->customer->address->address_number }},
              {{ $invoice->customer->address->neighborhood }},
              {{ $invoice->customer->address->city }} - {{ $invoice->customer->address->state }}
            </small>
          @endif
        </div>
      </div>

      <div class="col-md-6">
        <div class="info-box h-100">
          <h5 class="text-muted mb-3">
            <i class="bi bi-receipt me-2"></i>
            Serviço
          </h5>
          <strong>Código:</strong> {{ $invoice->service?->code }}<br>
          <strong>Descrição:</strong> {{ $invoice->service?->description }}<br>
          <strong>Status:</strong>
          <span class="badge bg-{{ $invoice->status?->getColor() ?? 'secondary' }}">
            {{ $invoice->status?->label() ?? 'N/A' }}
          </span>
        </div>
      </div>
    </div>

    <!-- Detalhes da fatura -->
    <div class="info-box">
      <h5 class="text-muted mb-3">
        <i class="bi bi-file-earmark-text me-2"></i>
        Detalhes da Fatura
      </h5>

      <div class="row">
        <div class="col-md-6">
          <strong>Valor Subtotal:</strong><br>
          {{ \App\Helpers\CurrencyHelper::format($invoice->subtotal) }}<br><br>

          <strong>Desconto:</strong><br>
          {{ \App\Helpers\CurrencyHelper::format($invoice->discount) }}<br><br>

          @if( $invoice->due_date )
            <strong>Data de Vencimento:</strong><br>
            {{ \Carbon\Carbon::parse( $invoice->due_date )->format( 'd/m/Y' ) }}<br><br>
          @endif

          @if( $invoice->payment_method )
            <strong>Forma de Pagamento:</strong><br>
            {{ $invoice->payment_method }}<br><br>
          @endif
        </div>

        <div class="col-md-6">
          <strong>Status Atual:</strong><br>
          <span class="badge bg-{{ $invoiceStatus->getColor() ?? 'secondary' }} status-badge fs-5 mb-3">
            <i class="bi bi-{{ $invoiceStatus->getIcon() ?? 'circle' }} me-2"></i>
            {{ $invoiceStatus->label() }}
          </span><br><br>

          @if( $invoice->transaction_date )
            <strong>Data do Pagamento:</strong><br>
            {{ \Carbon\Carbon::parse( $invoice->transaction_date )->format( 'd/m/Y H:i' ) }}<br><br>
          @endif

          @if( $invoice->payment_id )
            <strong>ID da Transação:</strong><br>
            {{ $invoice->payment_id }}<br><br>
          @endif

          <div class="total-highlight">
            <strong class="fs-4">Total: {{ \App\Helpers\CurrencyHelper::format($invoice->total) }}</strong>
          </div>
        </div>
      </div>
    </div>

    <!-- Itens da fatura (se houver) -->
    @if( $invoice->invoiceItems && $invoice->invoiceItems->count() > 0 )
      <div class="info-box">
        <h5 class="text-muted mb-3">
          <i class="bi bi-list-ul me-2"></i>
          Itens da Fatura
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
              @foreach( $invoice->invoiceItems as $item )
                <tr>
                  <td>{{ $item->product?->name ?? 'Produto não encontrado' }}</td>
                  <td class="text-center">{{ $item->quantity }}</td>
                  <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($item->unit_price) }}</td>
                  <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($item->total) }}</td>
                </tr>
              @endforeach
            </tbody>
            <tfoot>
              <tr>
                <th colspan="3" class="text-end">Subtotal:</th>
                <th class="text-end">{{ \App\Helpers\CurrencyHelper::format($invoice->subtotal) }}</th>
              </tr>
              <tr>
                <th colspan="3" class="text-end">Desconto:</th>
                <th class="text-end">{{ \App\Helpers\CurrencyHelper::format($invoice->discount) }}</th>
              </tr>
              <tr>
                <th colspan="3" class="text-end"><strong>Total:</strong></th>
                <th class="text-end"><strong>{{ \App\Helpers\CurrencyHelper::format($invoice->total) }}</strong></th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    @endif

    <!-- Informações de pagamento -->
    @if( $invoice->transaction_amount || $invoice->payment_method )
      <div class="payment-info">
        <h5 class="text-muted mb-3">
          <i class="bi bi-credit-card me-2"></i>
          Informações de Pagamento
        </h5>

        @if( $invoice->payment_method )
          <strong>Forma de Pagamento:</strong> {{ $invoice->payment_method }}<br>
        @endif

        @if( $invoice->transaction_amount )
          <strong>Valor da Transação:</strong> {{ \App\Helpers\CurrencyHelper::format($invoice->transaction_amount) }}<br>
        @endif

        @if( $invoice->transaction_date )
          <strong>Data da Transação:</strong>
          {{ \Carbon\Carbon::parse( $invoice->transaction_date )->format( 'd/m/Y H:i' ) }}<br>
        @endif

        @if( $invoice->payment_id )
          <strong>ID da Transação:</strong> {{ $invoice->payment_id }}<br>
        @endif
      </div>
    @endif

    <!-- Observações -->
    @if( $invoice->notes )
      <div class="info-box">
        <h5 class="text-muted mb-3">
          <i class="bi bi-file-text me-2"></i>
          Observações
        </h5>
        <p>{{ $invoice->notes }}</p>
      </div>
    @endif
@endsection

@section('footer')
    <p class="text-muted mb-1">
      <strong>{{ $invoice->tenant->name ?? 'Easy Budget' }}</strong>
    </p>
    @if( $invoice->tenant->contact ?? false )
      <p class="text-muted mb-1">{{ $invoice->tenant->contact->email }}</p>
      @if( $invoice->tenant->contact->phone )
        <p class="text-muted mb-1">{{ $invoice->tenant->contact->phone }}</p>
      @endif
    @endif
@endsection

