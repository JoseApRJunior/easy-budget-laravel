<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fatura #{{ $invoice->code }}</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: Arial, sans-serif;
      font-size: 12px;
      line-height: 1.4;
      color: #333;
    }

    .container {
      max-width: 210mm;
      margin: 0 auto;
      padding: 20mm;
      background: white;
    }

    /* Header */
    .header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 30px;
      border-bottom: 2px solid #007bff;
      padding-bottom: 20px;
    }

    .company-info h1 {
      color: #007bff;
      font-size: 24px;
      margin-bottom: 5px;
    }

    .company-info p {
      margin-bottom: 2px;
      font-size: 10px;
    }

    .invoice-info {
      text-align: right;
    }

    .invoice-info h2 {
      color: #007bff;
      font-size: 20px;
      margin-bottom: 5px;
    }

    .invoice-meta {
      font-size: 10px;
    }

    /* Cliente e Serviço */
    .client-service {
      display: flex;
      gap: 30px;
      margin-bottom: 30px;
    }

    .section {
      flex: 1;
    }

    .section h3 {
      background: #007bff;
      color: white;
      padding: 8px 12px;
      margin-bottom: 10px;
      font-size: 12px;
    }

    .section-content {
      border: 1px solid #ddd;
      padding: 12px;
      background: #f9f9f9;
    }

    /* Tabela de Itens */
    .items-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 30px;
    }

    .items-table th,
    .items-table td {
      border: 1px solid #ddd;
      padding: 8px;
      text-align: left;
    }

    .items-table th {
      background: #007bff;
      color: white;
      font-weight: bold;
      font-size: 11px;
    }

    .items-table tr:nth-child(even) {
      background: #f9f9f9;
    }

    .items-table .text-right {
      text-align: right;
    }

    .items-table .text-center {
      text-align: center;
    }

    .items-table .text-bold {
      font-weight: bold;
    }

    /* Resumo */
    .summary {
      margin-left: auto;
      width: 300px;
    }

    .summary-table {
      width: 100%;
      border-collapse: collapse;
    }

    .summary-table td {
      padding: 6px 8px;
      border-bottom: 1px solid #ddd;
    }

    .summary-table .total-row {
      background: #007bff;
      color: white;
      font-weight: bold;
      font-size: 14px;
    }

    /* Footer */
    .footer {
      position: fixed;
      bottom: 10mm;
      left: 20mm;
      right: 20mm;
      border-top: 1px solid #ddd;
      padding-top: 10px;
      font-size: 10px;
      text-align: center;
      color: #666;
    }

    /* Status */
    .status {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 10px;
      font-weight: bold;
      text-transform: uppercase;
    }

    .status.pending {
      background: #ffc107;
      color: #856404;
    }

    .status.paid {
      background: #28a745;
      color: white;
    }

    .status.overdue {
      background: #dc3545;
      color: white;
    }

    .status.cancelled {
      background: #6c757d;
      color: white;
    }

    /* Print styles */
    @media print {
      body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      .container {
        padding: 10mm;
      }

      .no-print {
        display: none;
      }
    }

    /* Responsivo */
    @media screen and (max-width: 768px) {
      .client-service {
        flex-direction: column;
      }

      .summary {
        width: 100%;
        margin-left: 0;
      }
    }
  </style>
</head>

<body>
  <div class="container">
    <!-- Header -->
    <div class="header">
      <div class="company-info">
        <h1>{{ auth()->user()->company_name ?? 'Sua Empresa' }}</h1>
        <p><strong>CNPJ:</strong> {{ auth()->user()->cnpj ?? '00.000.000/0001-00' }}</p>
        <p><strong>Email:</strong> {{ auth()->user()->email }}</p>
        <p><strong>Telefone:</strong> {{ auth()->user()->phone_business ?? auth()->user()->phone }}</p>
        <p>{{ auth()->user()->address ?? 'Endereço da empresa' }}, {{ auth()->user()->address_number ?? '' }}</p>
        <p>{{ auth()->user()->neighborhood ?? '' }}, {{ auth()->user()->city ?? '' }} -
          {{ auth()->user()->state ?? '' }}
        </p>
      </div>
      <div class="invoice-info">
        <h2>Fatura</h2>
        <div class="invoice-meta">
          <p><strong>Código:</strong> #{{ $invoice->code }}</p>
          <p><strong>Emissão:</strong> {{ $invoice->issue_date?->format( 'd/m/Y' ) ?? 'N/A' }}</p>
          <p><strong>Vencimento:</strong> {{ $invoice->due_date?->format( 'd/m/Y' ) ?? 'N/A' }}</p>
          <p><strong>Status:</strong> <span
              class="status {{ $invoice->status->value }}">{{ ucfirst( $invoice->status->value ) }}</span>
          </p>
        </div>
      </div>
    </div>

    <!-- Cliente e Serviço -->
    <div class="client-service">
      <div class="section">
        <h3>Cliente</h3>
        <div class="section-content">
          <p><strong>{{ $invoice->customer->name ?? 'N/A' }}</strong></p>
          @if( $invoice->customer->email )
            <p>Email: {{ $invoice->customer->email }}</p>
          @endif
          @if( $invoice->customer->phone )
            <p>Telefone: {{ $invoice->customer->phone }}</p>
          @endif
        </div>
      </div>
      <div class="section">
        <h3>Serviço</h3>
        <div class="section-content">
          <p><strong>Código:</strong> {{ $invoice->service->code ?? 'N/A' }}</p>
          <p><strong>Descrição:</strong> {{ Str::limit( $invoice->service->description ?? 'N/A', 100 ) }}</p>
        </div>
      </div>
    </div>

    <!-- Itens da Fatura -->
    @if( $invoice->invoiceItems->count() > 0 )
      <table class="items-table">
        <thead>
          <tr>
            <th>Produto/Serviço</th>
            <th class="text-center">Quantidade</th>
            <th class="text-right">Valor Unitário</th>
            <th class="text-right">Total</th>
          </tr>
        </thead>
        <tbody>
          @foreach( $invoice->invoiceItems as $item )
            <tr>
              <td>
                <strong>{{ $item->product->name ?? 'N/A' }}</strong>
                @if( $item->product->description )
                  <br><small>{{ $item->product->description }}</small>
                @endif
              </td>
              <td class="text-center">{{ number_format( $item->quantity, 2, ',', '.' ) }}</td>
              <td class="text-right">R$ {{ number_format( $item->unit_value, 2, ',', '.' ) }}</td>
              <td class="text-right"><strong>R$ {{ number_format( $item->total, 2, ',', '.' ) }}</strong></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @else
      <div class="no-items">
        <p><em>Nenhum item encontrado nesta fatura.</em></p>
      </div>
    @endif

    <!-- Resumo Financeiro -->
    <div class="summary">
      <table class="summary-table">
        <tr>
          <td>Subtotal:</td>
          <td class="text-right">R$ {{ number_format( $invoice->invoiceItems->sum( 'total' ), 2, ',', '.' ) }}</td>
        </tr>
        <tr>
          <td>Desconto:</td>
          <td class="text-right">R$ 0,00</td>
        </tr>
        <tr>
          <td>Frete:</td>
          <td class="text-right">R$ 0,00</td>
        </tr>
        <tr class="total-row">
          <td>Total:</td>
          <td class="text-right">R$ {{ number_format( $invoice->total_amount, 2, ',', '.' ) }}</td>
        </tr>
      </table>
    </div>

    <!-- Observações -->
    @if( $invoice->notes )
      <div style="margin-top: 30px;">
        <h3>Observações</h3>
        <div style="border: 1px solid #ddd; padding: 12px; background: #f9f9f9;">
          <p>{{ $invoice->notes }}</p>
        </div>
      </div>
    @endif
  </div>

  <!-- Footer -->
  <div class="footer">
    <p>Documento gerado automaticamente pelo Easy Budget Laravel em {{ now()->format( 'd/m/Y H:i:s' ) }}</p>
    <p>Esta fatura foi gerada eletronicamente e é válida sem assinatura física.</p>
  </div>

  <script>
    // Auto-print para PDFs
    if ( window.location.search.includes( 'print=1' ) ) {
      window.onload = function () {
        window.print();
      };
    }
  </script>
</body>

</html>
