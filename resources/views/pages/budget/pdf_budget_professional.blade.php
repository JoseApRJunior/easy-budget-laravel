<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orçamento {{ $budget->code }}</title>
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
            border-bottom: 3px solid #3498db;
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
        
        .budget-info {
            text-align: right;
            flex: 1;
        }
        
        .budget-title {
            font-size: 24px;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 10px;
        }
        
        .budget-code {
            font-size: 16px;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .budget-dates {
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
            border-left: 4px solid #3498db;
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
        
        .description-section {
            margin-bottom: 25px;
            padding: 15px;
            background: #fff;
            border: 1px solid #dee2e6;
        }
        
        .services-section {
            margin-bottom: 25px;
        }
        
        .service-item {
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .service-header {
            background: #3498db;
            color: white;
            padding: 12px 15px;
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
        
        .service-details {
            padding: 15px;
            background: #f8f9fa;
            font-size: 10px;
            color: #6c757d;
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
        
        .service-total {
            background: #2c3e50;
            color: white;
            font-weight: bold;
        }
        
        .totals-section {
            background: linear-gradient(135deg, #3498db, #2980b9);
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
        
        .conditions-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .condition-box {
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
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
        
        .no-services {
            text-align: center;
            padding: 40px;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            color: #6c757d;
            font-style: italic;
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
        
        <div class="budget-info">
            <div class="budget-title">ORÇAMENTO</div>
            <div class="budget-code">#{{ $budget->code }}</div>
            <div class="budget-dates">
                <strong>Emissão:</strong> {{ $budget->created_at->format('d/m/Y') }}<br>
                <strong>Validade:</strong> {{ $budget->due_date->format('d/m/Y') }}
            </div>
            <div class="status-badge" style="background-color: {{ $budget->status->getColor() }};">
                {{ $budget->status->getDescription() }}
            </div>
        </div>
    </div>
    
    <!-- Customer Section -->
    <div class="customer-section">
        <div class="section-title">Dados do Cliente</div>
        <div class="customer-grid">
            <div>
                <div class="customer-name">
                    @if($budget->customer->commonData->type === 'company')
                        {{ $budget->customer->commonData->company_name }}
                    @else
                        {{ $budget->customer->commonData->first_name }} {{ $budget->customer->commonData->last_name }}
                    @endif
                </div>
                <div class="customer-details">
                    @if($budget->customer->commonData->type === 'company' && $budget->customer->commonData->cnpj)
                        <strong>CNPJ:</strong> {{ $budget->customer->commonData->cnpj }}<br>
                    @elseif($budget->customer->commonData->cpf)
                        <strong>CPF:</strong> {{ $budget->customer->commonData->cpf }}<br>
                    @endif
                    
                    @if($budget->customer->address)
                        <strong>Endereço:</strong> {{ $budget->customer->address->address }}, {{ $budget->customer->address->address_number }}<br>
                        {{ $budget->customer->address->neighborhood }} - {{ $budget->customer->address->city }}/{{ $budget->customer->address->state }}
                    @endif
                </div>
            </div>
            <div>
                <div class="customer-details">
                    @if($budget->customer->contact->phone_personal)
                        <strong>Telefone:</strong> {{ $budget->customer->contact->phone_personal }}<br>
                    @endif
                    @if($budget->customer->contact->email_personal)
                        <strong>Email:</strong> {{ $budget->customer->contact->email_personal }}<br>
                    @endif
                    @if($budget->customer->contact->phone_business)
                        <strong>Tel. Comercial:</strong> {{ $budget->customer->contact->phone_business }}<br>
                    @endif
                    @if($budget->customer->contact->email_business)
                        <strong>Email Comercial:</strong> {{ $budget->customer->contact->email_business }}
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <!-- Description -->
    @if($budget->description)
    <div class="description-section">
        <div class="section-title">Descrição do Orçamento</div>
        <p>{{ $budget->description }}</p>
    </div>
    @endif
    
    <!-- Services -->
    <div class="services-section">
        <div class="section-title">Serviços Inclusos</div>
        
        @if($budget->services->isNotEmpty())
            @foreach($budget->services as $service)
            <div class="service-item">
                <div class="service-header">
                    <div class="service-name">{{ $service->category->name ?? 'Serviço' }}</div>
                    <div class="service-status" style="background-color: {{ $service->status->getColor() }};">
                        {{ $service->status->getDescription() }}
                    </div>
                </div>
                
                @if($service->description)
                <div class="service-details">
                    <strong>Descrição:</strong> {{ $service->description }}<br>
                    <strong>Prazo:</strong> {{ $service->due_date ? $service->due_date->format('d/m/Y') : 'A definir' }}
                </div>
                @endif
                
                @if($service->serviceItems->isNotEmpty())
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
                        @foreach($service->serviceItems as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->product->name ?? 'Item' }}</strong>
                                @if($item->product->description)
                                    <br><small>{{ $item->product->description }}</small>
                                @endif
                            </td>
                            <td class="text-center">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                            <td class="text-right">R$ {{ number_format($item->unit_value, 2, ',', '.') }}</td>
                            <td class="text-right">R$ {{ number_format($item->total, 2, ',', '.') }}</td>
                        </tr>
                        @endforeach
                        <tr class="service-total">
                            <td colspan="3"><strong>Total do Serviço</strong></td>
                            <td class="text-right"><strong>R$ {{ number_format($service->total, 2, ',', '.') }}</strong></td>
                        </tr>
                    </tbody>
                </table>
                @endif
            </div>
            @endforeach
            
            <!-- Totals -->
            @php
                $servicesSubtotal = $budget->services->sum('total');
                $discountValue = $budget->discount ?? 0;
                $netTotal = $budget->total;
            @endphp
            
            <div class="totals-section">
                <div class="totals-grid">
                    <div class="total-item">
                        <div class="total-label">Subtotal dos Serviços</div>
                        <div class="total-value">R$ {{ number_format($servicesSubtotal, 2, ',', '.') }}</div>
                    </div>
                    <div class="total-item">
                        <div class="total-label">Desconto</div>
                        <div class="total-value">R$ {{ number_format($discountValue, 2, ',', '.') }}</div>
                    </div>
                    <div class="total-item grand-total">
                        <div class="total-label">TOTAL GERAL</div>
                        <div class="total-value">R$ {{ number_format($netTotal, 2, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        @else
            <div class="no-services">
                <p>Nenhum serviço foi adicionado a este orçamento.</p>
            </div>
        @endif
    </div>
    
    <!-- Conditions -->
    <div class="conditions-section">
        @if($budget->payment_terms)
        <div class="condition-box">
            <div class="section-title">Condições de Pagamento</div>
            <p>{{ $budget->payment_terms }}</p>
        </div>
        @endif
        
        <div class="condition-box">
            <div class="section-title">Observações Gerais</div>
            <p>• Este orçamento é válido até {{ $budget->due_date->format('d/m/Y') }}</p>
            <p>• Valores sujeitos a alteração sem aviso prévio após o vencimento</p>
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
                @if($budget->customer->commonData->type === 'company')
                    {{ $budget->customer->commonData->company_name }}
                @else
                    {{ $budget->customer->commonData->first_name }} {{ $budget->customer->commonData->last_name }}
                @endif
            </div>
            <div class="signature-role">Cliente</div>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <p>Este documento foi gerado automaticamente pelo sistema {{ config('app.name') }} em {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>