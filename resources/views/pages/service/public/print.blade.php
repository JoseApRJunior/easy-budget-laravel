<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Serviço {{ $service->code }} - Easy Budget</title>
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
      background-color: #e3f2fd;
      border: 2px solid #2196f3;
      border-radius: 8px;
      padding: 15px;
      text-align: center;
      margin: 20px 0;
    }
  </style>
</head>

<body>
  <x-page-container :fluid="false" padding="py-0">
    <!-- Botão de impressão (não aparece na impressão) -->
    <div class="text-center no-print mb-3 py-3">
      <x-button onclick="window.print()" variant="primary" icon="printer" label="Imprimir" />
      <x-button type="link" :href="route( 'provider.services.public.view-status', [ 'code' => $service->code, 'token' => request( 'token' ) ] )"
        variant="outline-secondary" icon="arrow-left" label="Voltar" class="ms-2" />
    </div>

    <!-- Cabeçalho -->
    <div class="header text-center">
      <h1 class="mb-2">Comprovante de Serviço</h1>
      <h3 class="text-muted">Serviço #{{ $service->code }}</h3>
      <p class="mb-0">{{ date( 'd/m/Y H:i:s' ) }}</p>
    </div>

    <!-- Status e informações principais -->
    <div class="row mb-4">
      <div class="col-md-6">
        <div class="info-box h-100">
          <h5 class="text-muted mb-3">
            <i class="bi bi-person-circle me-2"></i>
            Dados do Cliente
          </h5>
          <strong>{{ $service->customer->common_data->first_name }}
            {{ $service->customer->common_data->last_name }}</strong><br>
          <span class="text-muted">{{ $service->customer->contact->email }}</span><br>
          @if( $service->customer->contact->phone )
            <span class="text-muted">{{ $service->customer->contact->phone }}</span><br>
          @endif
          @if( $service->customer->address )
            <small class="text-muted">
              {{ $service->customer->address->address }},
              {{ $service->customer->address->address_number }},
              {{ $service->customer->address->neighborhood }},
              {{ $service->customer->address->city }} - {{ $service->customer->address->state }}
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
          <strong>Código:</strong> {{ $service->budget->code }}<br>
          <strong>Descrição:</strong> {{ $service->budget->description }}<br>
          <strong>Total do Orçamento:</strong> {{ \App\Helpers\CurrencyHelper::format($service->budget->total) }}<br>
          <strong>Status:</strong>
          <x-status-badge :item="$service->budget" />
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
          {{ $service->category->name }}<br><br>

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
          <x-status-badge :item="$service" class="fs-5 mb-3" />
          <br><br>

          <strong>Valor do Serviço:</strong><br>
          {{ \App\Helpers\CurrencyHelper::format($service->total) }}<br><br>

          <strong>Desconto:</strong><br>
          {{ \App\Helpers\CurrencyHelper::format($service->discount) }}<br><br>

          <div class="total-highlight">
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
    @if( $service->budget->payment_terms )
      <div class="info-box">
        <h5 class="text-muted mb-3">
          <i class="bi bi-file-text me-2"></i>
          Condições de Pagamento
        </h5>
        <p>{{ $service->budget->payment_terms }}</p>
      </div>
    @endif

    <!-- Rodapé -->
  <div class="text-center mt-5 pt-4 border-top">
    <p class="text-muted mb-0">
      <small>Documento gerado em {{ date( 'd/m/Y \à\s H:i:s' ) }}</small>
    </p>
    @if(isset($verificationUrl) && isset($qrDataUri) && $qrDataUri)
      <div class="mt-3">
        <p class="text-muted">Verifique a autenticidade:</p>
        <p><a href="{{ $verificationUrl }}">{{ $verificationUrl }}</a></p>
        <img src="{{ $qrDataUri }}" alt="QR Code de verificação" width="140" height="140">
      </div>
    @endif
  </div>
  </x-page-container>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
