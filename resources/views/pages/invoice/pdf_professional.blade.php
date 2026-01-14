<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fatura {{ $invoice->code }}</title>
    <style>
        @page {
            margin: 20mm;
            size: A4 portrait;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #2c3e50;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #e74c3c;
        }
        
        .company-info {
            flex: 1;
        }
        
        .company-logo {
            width: 80px;
            height: 80px;
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            font-size: 10px;
            color: #6c757d;
        }
        
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .company-details {
            font-size: 10px;
            color: #6c757d;
            line-height: 1.3;
        }
        
        .invoice-info {
            text-align: right;
            flex: 1;
        }
        
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 10px;
        }
        
        .invoice-code {
            font-size: 16px;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .invoice-dates {
            font-size: 10px;
            color: #6c757d;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 10px;
            font-weight: bold;
            color: white;
            margin-top: 10px;
        }
        
        .customer-section {
            background: #f8f9fa;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid #e74c3c;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .customer-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .customer-name {
            font-size: 14px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .customer-details {
            font-size: 10px;
            color: #6c757d;
            line-height: 1.4;
        }
        
        .service-section {
            margin-bottom: 25px;
            padding: 15px;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        
        .service-header {
            background: #e74c3c;
            color: white;
            padding: 12px 15px;
            margin: -15px -15px 15px -15px;
            border-radius: 5px 5px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .service-name {
            font-size: 12px;
            font-weight: bold;
        }
        
        .service-status {
            font-size: 9px;
            padding: 3px 8px;
            border-radius: 10px;
            background: rgba(255,255,255,0.2);
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .items-table th {
            background: #ecf0f1;
            padding: 10px 8px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
            color: #2c3e50;
            border-bottom: 2px solid #bdc3c7;
        }
        
        .items-table td {
            padding: 8px;
            border-bottom: 1px solid #ecf0f1;
            font-size: 10px;
        }
        
        .items-table .text-center {
            text-align: center;
        }
        
        .items-table .text-right {
            text-align: right;
        }
        
        .invoice-total {
            background: #2c3e50;
            color: white;
            font-weight: bold;
        }
        
        .totals-section {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 5px;
        }
        
        .totals-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            align-items: center;
        }
        
        .total-item {
            text-align: center;
        }
        
        .total-label {
            font-size: 10px;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        
        .total-value {
            font-size: 16px;
            font-weight: bold;
        }
        
        .grand-total .total-value {
            font-size: 20px;
        }
        
        .payment-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .payment-box {
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        
        .qr-code {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .qr-code img {
            width: 120px;
            height: 120px;
        }
        
        .payment-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .payment-info h4 {
            color: #856404;
            margin-bottom: 10px;
        }
        
        .signatures-section {
            margin-top: 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }
        
        .signature-box {
            text-align: center;
        }
        
        .signature-line {
            border-top: 1px solid #2c3e50;
            margin-bottom: 5px;
            height: 40px;
        }
        
        .signature-name {
            font-size: 11px;
            color: #2c3e50;
        }
        
        .signature-role {
            font-size: 9px;
            color: #6c757d;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 9px;
            color: #6c757d;
        }
        
        .overdue-notice {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }
        
        .paid-notice {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="company-info">
            @if(auth()->user()->logo)
                <img src="{{ asset('storage/' . auth()->user()->logo) }}" alt="Logo" class="company-logo">
            @else
                <div class="company-logo">LOGO</div>
            @endif
            
            <div class="company-name">
                {{ auth()->user()->provider->commonData->type === 'company' 
                    ? auth()->user()->provider->commonData->company_name 
                    : auth()->user()->provider->commonData->first_name . ' ' . auth()->user()->provider->commonData->last_name }}
            </div>
            
            <div class="company-details">
                @if(auth()->user()->provider->address)
                    {{ auth()->user()->provider->address->address }}, {{ auth()->user()->provider->address->address_number }}<br>
                    {{ auth()->user()->provider->address->neighborhood }} - {{ auth()->user()->provider->address->city }}/{{ auth()->user()->provider->address->state }}<br>
                    CEP: {{ auth()->user()->provider->address->cep }}<br>
                @endif
                
                @if(auth()->user()->provider->commonData->type === 'company' && auth()->user()->provider->commonData->cnpj)
                    CNPJ: {{ auth()->user()->provider->commonData->cnpj }}<br>
                @elseif(auth()->user()->provider->commonData->cpf)
                    CPF: {{ auth()->user()->provider->commonData->cpf }}<br>
                @endif
                
                @if(auth()->user()->provider->contact->phone_business)
                    Tel: {{ auth()->user()->provider->contact->phone_business }}<br>
                @endif
                
                @if(auth()->user()->provider->contact->email_business)
                    Email: {{ auth()->user()->provider->contact->email_business }}
                @endif
            </div>
        </div>
        
        <div class="invoice-info">
            <div class="invoice-title">FATURA</div>
            <div class="invoice-code">#{{ $invoice->code }}</div>
            <div class="invoice-dates">
                <strong>Emissão:</strong> {{ $invoice->created_at->format('d/m/Y') }}<br>
                <strong>Vencimento:</strong> {{ $invoice->due_date->format('d/m/Y') }}
            </div>
            <div class="status-badge" style="background-color: {{ $invoice->status->getColor() }};">
                {{ $invoice->status->getDescription() }}
            </div>
        </div>
    </div>
    
    <!-- Status Notices -->
    @if($invoice->status->value === 'OVERDUE')
        <div class="overdue-notice">
            ⚠️ FATURA VENCIDA - Pagamento em atraso desde {{ $invoice->due_date->format('d/m/Y') }}
        </div>
    @elseif($invoice->status->value === 'PAID')
        <div class="paid-notice">
            ✅ FATURA PAGA - Pagamento confirmado em {{ $invoice->transaction_date ? $invoice->transaction_date->format('d/m/Y') : 'data não informada' }}
        </div>
    @endif
    
    <!-- Customer Section -->
    <div class="customer-section">
        <div class="section-title">Dados do Cliente</div>
        <div class="customer-grid">
            <div>
                <div class="customer-name">
                    @if($invoice->customer->commonData->type === 'company')
                        {{ $invoice->customer->commonData->company_name }}
                    @else
                        {{ $invoice->customer->commonData->first_name }} {{ $invoice->customer->commonData->last_name }}
                    @endif
                </div>
                <div class="customer-details">
                    @if($invoice->customer->commonData->type === 'company' && $invoice->customer->commonData->cnpj)
                        <strong>CNPJ:</strong> {{ $invoice->customer->commonData->cnpj }}<br>
                    @elseif($invoice->customer->commonData->cpf)
                        <strong>CPF:</strong> {{ $invoice->customer->commonData->cpf }}<br>
                    @endif
                    
                    @if($invoice->customer->address)
                        <strong>Endereço:</strong> {{ $invoice->customer->address->address }}, {{ $invoice->customer->address->address_number }}<br>
                        {{ $invoice->customer->address->neighborhood }} - {{ $invoice->customer->address->city }}/{{ $invoice->customer->address->state }}
                    @endif
                </div>
            </div>
            <div>
                <div class="customer-details">
                    @if($invoice->customer->contact->phone_personal)
                        <strong>Telefone:</strong> {{ $invoice->customer->contact->phone_personal }}<br>
                    @endif
                    @if($invoice->customer->contact->email_personal)
                        <strong>Email:</strong> {{ $invoice->customer->contact->email_personal }}<br>
                    @endif
                    @if($invoice->customer->contact->phone_business)
                        <strong>Tel. Comercial:</strong> {{ $invoice->customer->contact->phone_business }}<br>
                    @endif
                    @if($invoice->customer->contact->email_business)
                        <strong>Email Comercial:</strong> {{ $invoice->customer->contact->email_business }}
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <!-- Service Section -->
    @if($invoice->service)
    <div class="service-section">
        <div class="service-header">
            <div class="service-name">{{ $invoice->service->category->name ?? 'Serviço Prestado' }}</div>
            <div class="service-status">Ref: {{ $invoice->service->code }}</div>
        </div>
        
        @if($invoice->service->description)
        <div style="margin-bottom: 15px; font-size: 10px; color: #6c757d;">
            <strong>Descrição:</strong> {{ $invoice->service->description }}
        </div>
        @endif
        
        @if($invoice->invoiceItems->isNotEmpty())
        <table class="items-table">
            <thead>
                <tr>
                    <th>Item/Produto</th>
                    <th class="text-center" width="80">Qtd</th>
                    <th class="text-right" width="100">Valor Unit.</th>
                    <th class="text-right" width="100">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->invoiceItems as $item)
                <tr>
                    <td>
                        <strong>{{ $item->product->name ?? 'Item' }}</strong>
                        @if($item->product->description)
                            <br><small>{{ $item->product->description }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ \App\Helpers\CurrencyHelper::format($item->quantity, 0, false) }}</td>
                        <td class="text-right">{{ \App\Helpers\CurrencyHelper::format($item->unit_price) }}</td>
                        <td class="text-right">{{ \App\Helpers\CurrencyHelper::format($item->total) }}</td>
                </tr>
                @endforeach
                <tr class="invoice-total">
                    <td colspan="3"><strong>Total da Fatura</strong></td>
                    <td class="text-right"><strong>{{ \App\Helpers\CurrencyHelper::format($invoice->total) }}</strong></td>
                </tr>
            </tbody>
        </table>
        @endif
    </div>
    @endif
    
    <!-- Totals -->
    @php
        $subtotal = $invoice->subtotal ?? $invoice->total;
        $discount = $invoice->discount ?? 0;
        $total = $invoice->total;
    @endphp
    
    <div class="totals-section">
        <div class="totals-grid">
            <div class="total-item">
                <div class="total-label">Subtotal</div>
                <div class="total-value">{{ \App\Helpers\CurrencyHelper::format($subtotal) }}</div>
            </div>
            <div class="total-item">
                <div class="total-label">Desconto</div>
                <div class="total-value">{{ \App\Helpers\CurrencyHelper::format($discount) }}</div>
            </div>
            <div class="total-item grand-total">
                <div class="total-label">TOTAL A PAGAR</div>
                <div class="total-value">{{ \App\Helpers\CurrencyHelper::format($total) }}</div>
            </div>
        </div>
    </div>
    
    <!-- Payment Information -->
    @if($invoice->status->isPending())
    <div class="payment-info">
        <h4>💳 Informações para Pagamento</h4>
        <p><strong>Vencimento:</strong> {{ $invoice->due_date->format('d/m/Y') }}</p>
        <p><strong>Valor:</strong> {{ \App\Helpers\CurrencyHelper::format($invoice->total) }}</p>
        <p><strong>Formas de Pagamento:</strong> PIX, Boleto Bancário, Cartão de Crédito/Débito</p>
    </div>
    
    <div class="payment-section">
        <div class="payment-box">
            <div class="section-title">Pagamento via PIX</div>
            <p style="font-size: 10px; margin-bottom: 10px;">Escaneie o QR Code ou use a chave PIX:</p>
            <div class="qr-code">
                <!-- QR Code seria gerado aqui -->
                <div style="width: 120px; height: 120px; border: 2px dashed #dee2e6; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                    QR CODE PIX
                </div>
            </div>
            <p style="font-size: 9px; text-align: center; margin-top: 10px;">
                Chave PIX: {{ auth()->user()->provider->contact->email_business ?? 'pix@empresa.com' }}
            </p>
        </div>
        
        <div class="payment-box">
            <div class="section-title">Outras Formas de Pagamento</div>
            <p style="font-size: 10px; margin-bottom: 10px;"><strong>Boleto Bancário:</strong></p>
            <p style="font-size: 9px; margin-bottom: 15px;">Acesse o link de pagamento ou entre em contato conosco.</p>
            
            <p style="font-size: 10px; margin-bottom: 10px;"><strong>Cartão de Crédito/Débito:</strong></p>
            <p style="font-size: 9px;">Pagamento online através do nosso sistema seguro.</p>
            
            @if($invoice->public_hash)
            <p style="font-size: 9px; margin-top: 15px; padding: 8px; background: #f8f9fa; border-radius: 3px;">
                <strong>Link de Pagamento:</strong><br>
                {{ config('app.url') }}/invoice/{{ $invoice->public_hash }}
            </p>
            @endif
        </div>
    </div>
    @endif
    
    <!-- Notes -->
    @if($invoice->notes)
    <div style="margin-bottom: 25px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
        <div class="section-title">Observações</div>
        <p style="font-size: 10px;">{{ $invoice->notes }}</p>
    </div>
    @endif
    
    <!-- Terms -->
    <div style="margin-bottom: 25px; padding: 15px; border: 1px solid #dee2e6; border-radius: 5px;">
        <div class="section-title">Condições Gerais</div>
        <div style="font-size: 9px; color: #6c757d; line-height: 1.4;">
            <p>• Fatura com vencimento em {{ $invoice->due_date->format('d/m/Y') }}</p>
            <p>• Após o vencimento, incidirão juros de 1% ao mês e multa de 2%</p>
            <p>• Em caso de dúvidas, entre em contato conosco</p>
            <p>• Documento gerado automaticamente em {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
    
    <!-- Signatures -->
    <div class="signatures-section">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-name">
                {{ auth()->user()->provider->commonData->type === 'company' 
                    ? auth()->user()->provider->commonData->company_name 
                    : auth()->user()->provider->commonData->first_name . ' ' . auth()->user()->provider->commonData->last_name }}
            </div>
            <div class="signature-role">Prestador de Serviços</div>
        </div>
        
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-name">
                @if($invoice->customer->commonData->type === 'company')
                    {{ $invoice->customer->commonData->company_name }}
                @else
                    {{ $invoice->customer->commonData->first_name }} {{ $invoice->customer->commonData->last_name }}
                @endif
            </div>
            <div class="signature-role">Cliente</div>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <p>Este documento foi gerado automaticamente pelo sistema {{ config('app.name') }} em {{ now()->format('d/m/Y H:i:s') }}</p>
        @if($invoice->public_hash)
            <p>Código de verificação: {{ $invoice->public_hash }}</p>
        @endif
    </div>
</body>
</html>