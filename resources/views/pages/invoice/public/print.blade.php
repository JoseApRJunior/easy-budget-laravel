<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fatura {{ $invoice->code }} - {{ $invoice->tenant->name ?? 'Easy Budget' }}</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    @media print {
      .no-print {
        display: none !important;
      }

      body {
        font-size: 12px;
      }

      .container {
        max-width: none !important;
        margin: 0 !important;
        padding: 0 !important;
      }
    }

    body {
      font-size: 14px;
    }

    .header {
      border-bottom: 2px solid #dee2e6;
      padding-bottom: 15px;
      margin-bottom: 20px;
    }

    .info-box {
      background-color: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 15px;
    }

    .status-badge {
      font-size: 16px;
      padding: 8px 16px;
      border-radius: 20px;
    }

    .total-highlight {
      background-color: #e8f5e8;
      border: 2px solid #28a745;
      border-radius: 8px;
      padding: 15px;
      text-align: center;
      margin: 20px 0;
    }

    .payment-info {
      background-color: #fff3cd;
      border: 1px solid #ffeaa7;
      border-radius: 8px;
      padding: 15px;
      margin: 15px 0;
    }
  </style>
</head>

<body>
  <div class="container">
    <!-- Botão de impressão (não aparece na impressão) -->
    <div class="text-center no-print mb-3">
      <button onclick="window.print()" class="btn btn-primary">
        <i class="bi bi-printer me-2"></i>
        Imprimir
      </button>
      <a href="{{ route( 'provider.invoices.public.view-status', [ 'code' => $invoice->code, 'token' => request( 'token' ) ] ) }}"
        class="btn btn-outline-secondary ms-2">
        <i class="bi bi-arrow-left me-2"></i>
        Voltar
      </a>
    </div>

    <!-- Cabeçalho -->
    <div class="header text-center">
      <h1 class="mb-2">{{ $invoice->tenant->name ?? 'Easy Budget' }}</h1>
      <h3 class="text-muted">Fatura #{{ $invoice->code }}</h3>
      <p class="mb-0">{{ date( 'd/m/Y H:i:s' ) }}</p>
    </div>

    <!-- Status e informações principais -->
    <div class="row mb-4">
      <div class="col-md-6">
        <div class="info-box">
          <h5 class="text-muted mb-3">
            <i class="bi bi-person-circle me-2"></i>
            Dados do Cliente
          </h5>
          <strong>{{ $invoice->customer->common_data->first_name }}
            {{ $invoice->customer->common_data->last_name }}</strong><br>
          <span class="text-muted">{{ $invoice->customer->contact->email }}</span><br>
          @if( $invoice->customer->contact->phone )
            <span class="text-muted">{{ $invoice->customer->contact->phone }}</span><br>
          @endif
          @if( $invoice->customer->address )
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
        <div class="info-box">
          <h5 class="text-muted mb-3">
            <i class="bi bi-receipt me-2"></i>
            Serviço
          </h5>
          <strong>Código:</strong> {{ $invoice->service->code }}<br>
          <strong>Descrição:</strong> {{ $invoice->service->description }}<br>
          <strong>Valor do Serviço:</strong> {{ \App\Helpers\CurrencyHelper::format($invoice->service->total) }}<br>
          <strong>Status:</strong>
          <span class="badge bg-{{ $invoice->service->status->color() }} status-badge">
            {{ $invoice->service->status->label() }}
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
            {{ $invoiceStatus->getName() }}
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
            <strong class="fs-4">Total: R$ {{ number_format( $invoice->total, 2, ',', '.' ) }}</strong>
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
                  <td class="text-end">R$ {{ number_format( $item->unit_price, 2, ',', '.' ) }}</td>
                  <td class="text-end">R$ {{ number_format( $item->total, 2, ',', '.' ) }}</td>
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

    <!-- Rodapé -->
    <div class="text-center mt-5 pt-4 border-top">
      <p class="text-muted mb-1">
        <strong>{{ $invoice->tenant->name ?? 'Easy Budget' }}</strong>
      </p>
      @if( $invoice->tenant->contact ?? false )
        <p class="text-muted mb-1">{{ $invoice->tenant->contact->email }}</p>
        @if( $invoice->tenant->contact->phone )
          <p class="text-muted mb-1">{{ $invoice->tenant->contact->phone }}</p>
        @endif
      @endif
      <p class="text-muted mb-0">
        <small>Documento gerado em {{ date( 'd/m/Y \à\s H:i:s' ) }}</small>
      </p>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
