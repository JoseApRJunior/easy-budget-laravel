<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <title>Orçamento {{ $budget->code }}</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      font-size: 12px;
      line-height: 1.4;
      color: #333;
      margin: 0;
      padding: 20px;
    }

    .header {
      text-align: center;
      border-bottom: 2px solid #007bff;
      padding-bottom: 20px;
      margin-bottom: 30px;
    }

    .header h1 {
      color: #007bff;
      margin: 0;
      font-size: 24px;
    }

    .info-section {
      margin-bottom: 25px;
    }

    .info-section h3 {
      background-color: #f8f9fa;
      padding: 8px 12px;
      margin: 0 0 10px 0;
      border-left: 4px solid #007bff;
      font-size: 14px;
    }

    .info-grid {
      display: table;
      width: 100%;
    }

    .info-row {
      display: table-row;
    }

    .info-label {
      display: table-cell;
      font-weight: bold;
      padding: 5px 10px 5px 0;
      width: 120px;
    }

    .info-value {
      display: table-cell;
      padding: 5px 0;
    }

    .items-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }

    .items-table th,
    .items-table td {
      border: 1px solid #ddd;
      padding: 8px;
      text-align: left;
    }

    .items-table th {
      background-color: #f8f9fa;
      font-weight: bold;
    }

    .items-table .text-center {
      text-align: center;
    }

    .items-table .text-right {
      text-align: right;
    }

    .total-row {
      background-color: #f8f9fa;
      font-weight: bold;
    }

    .status-badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 11px;
      font-weight: bold;
      text-transform: uppercase;
    }

    .status-approved {
      background-color: #d4edda;
      color: #155724;
    }

    .status-pending {
      background-color: #fff3cd;
      color: #856404;
    }

    .status-rejected {
      background-color: #f8d7da;
      color: #721c24;
    }

    .footer {
      margin-top: 40px;
      text-align: center;
      font-size: 10px;
      color: #666;
      border-top: 1px solid #ddd;
      padding-top: 20px;
    }
  </style>
</head>

<body>
  <!-- Header -->
  <div class="header">
    <h1>ORÇAMENTO</h1>
    <p><strong>Código:</strong> {{ $budget->code }}</p>
    <p>
      <span class="status-badge status-{{ $budget->status->value }}">
        {{ $budget->status->label() }}
      </span>
    </p>
  </div>

  <!-- Dados do Cliente -->
  <div class="info-section">
    <h3>Dados do Cliente</h3>
    <div class="info-grid">
      <div class="info-row">
        <div class="info-label">Nome:</div>
        <div class="info-value">{{ $budget->customer->name }}</div>
      </div>
      <div class="info-row">
        <div class="info-label">E-mail:</div>
        <div class="info-value">{{ $budget->customer->email }}</div>
      </div>
      <div class="info-row">
        <div class="info-label">Telefone:</div>
        <div class="info-value">{{ $budget->customer->phone ?? 'Não informado' }}</div>
      </div>
    </div>
  </div>

  <!-- Dados do Orçamento -->
  <div class="info-section">
    <h3>Detalhes do Orçamento</h3>
    <div class="info-grid">
      <div class="info-row">
        <div class="info-label">Descrição:</div>
        <div class="info-value">{{ $budget->description }}</div>
      </div>
      <div class="info-row">
        <div class="info-label">Data de Criação:</div>
        <div class="info-value">{{ $budget->created_at->format( 'd/m/Y H:i' ) }}</div>
      </div>
      <div class="info-row">
        <div class="info-label">Vencimento:</div>
        <div class="info-value">
          {{ $budget->due_date ? $budget->due_date->format( 'd/m/Y' ) : 'Não definido' }}
        </div>
      </div>
    </div>
  </div>

  <!-- Itens do Orçamento -->
  <div class="info-section">
    <h3>Itens do Orçamento</h3>
    <table class="items-table">
      <thead>
        <tr>
          <th>Descrição</th>
          <th class="text-center" width="80">Qtd</th>
          <th class="text-right" width="100">Valor Unit.</th>
          <th class="text-right" width="100">Total</th>
        </tr>
      </thead>
      <tbody>
        @forelse( $budget->items as $item )
          <tr>
            <td>{{ $item->description }}</td>
            <td class="text-center">{{ $item->quantity }}</td>
            <td class="text-right">R$ {{ \App\Helpers\CurrencyHelper::format($item->unit_price) }}</td>
            <td class="text-right">{{ \App\Helpers\CurrencyHelper::format($item->total_price) }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="4" class="text-center">Nenhum item adicionado</td>
          </tr>
        @endforelse
      </tbody>
      @if( $budget->items->count() > 0 )
        <tfoot>
          <tr class="total-row">
            <td colspan="3" class="text-right"><strong>TOTAL GERAL:</strong></td>
            <td class="text-right">
              <strong>R$ {{ \App\Helpers\CurrencyHelper::format($budget->total_amount) }}</strong>
            </td>
          </tr>
        </tfoot>
      @endif
    </table>
  </div>

  <!-- Footer -->
  <div class="footer">
    <p>Este orçamento foi gerado automaticamente em {{ now()->format( 'd/m/Y H:i' ) }}</p>
    <p>Código de verificação: {{ $budget->pdf_verification_hash ?? 'Não disponível' }}</p>
    @if(isset($verificationUrl) && isset($qrDataUri) && $qrDataUri !== '')
      <p>Verifique a autenticidade:</p>
      <p><a href="{{ $verificationUrl }}">{{ $verificationUrl }}</a></p>
      <p><img src="{{ $qrDataUri }}" alt="QR Code" width="140" height="140"></p>
    @endif
  </div>
</body>

</html>
