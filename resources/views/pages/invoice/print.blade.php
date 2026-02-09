@extends('layouts.pdf_base')

@section('title', 'Fatura #' . $invoice->code)

@section('content')
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="company-logo">
                @if(isset($invoice->tenant->user->logo))
                    <img src="{{ asset('storage/' . $invoice->tenant->user->logo) }}" alt="Logo" style="max-height: 80px;">
                @else
                    <h1 class="brand-name">{{ config('app.name') }}</h1>
                @endif
            </div>
            <div class="invoice-title">
                <h1 class="text-uppercase">Fatura</h1>
                <p class="invoice-number">#{{ $invoice->code }}</p>
            </div>
        </div>

        <div class="invoice-meta-grid">
            <div class="meta-item">
                <span class="label">Data de Emissão</span>
                <span class="value">{{ $invoice->created_at->format('d/m/Y') }}</span>
            </div>
            <div class="meta-item">
                <span class="label">Data de Vencimento</span>
                <span class="value">{{ $invoice->due_date?->format('d/m/Y') ?? 'N/A' }}</span>
            </div>
            <div class="meta-item text-center">
                <span class="label">Status</span>
                <span class="status-badge" style="background-color: {{ $invoice->status->getColor() }}">
                    {{ $invoice->status->label() }}
                </span>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="info-grid">
            <div class="info-box">
                <h3 class="section-title">Prestador</h3>
                <div class="info-content">
                    <p class="name"><strong>{{ $invoice->tenant->name ?? config('app.name') }}</strong></p>
                    @if(isset($invoice->tenant->user->email))
                        <p class="text-muted">{{ $invoice->tenant->user->email }}</p>
                    @endif
                </div>
            </div>

            <div class="info-box">
                <h3 class="section-title">Cliente</h3>
                <div class="info-content">
                    <p class="name"><strong>{{ $invoice->customer->user->name ?? 'N/A' }}</strong></p>
                    @if ($invoice->customer->user->email)
                        <p class="text-muted">{{ $invoice->customer->user->email }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="service-summary">
            <h3 class="section-title">Serviço Associado</h3>
            <p><strong>{{ $invoice->service->code ?? 'N/A' }}</strong> - {{ $invoice->service->description ?? 'Descrição não disponível' }}</p>
        </div>

        <!-- Table Items -->
        <div class="items-section">
            <table class="table-items">
                <thead>
                    <tr>
                        <th width="50%">Descrição do Item</th>
                        <th class="text-center" width="10%">Qtd</th>
                        <th class="text-end" width="20%">Vlr. Unitário</th>
                        <th class="text-end" width="20%">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoice->invoiceItems as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->product->name ?? 'Item' }}</strong>
                                @if($item->description)
                                    <br><small class="text-muted">{{ $item->description }}</small>
                                @endif
                            </td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($item->unit_price) }}</td>
                            <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($item->total) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">Nenhum item encontrado nesta fatura.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="totals-section">
            <div class="totals-wrapper">
                <div class="total-row">
                    <span class="label">Subtotal:</span>
                    <span class="value">{{ \App\Helpers\CurrencyHelper::format($invoice->subtotal ?? $invoice->invoiceItems->sum('total')) }}</span>
                </div>
                @if($invoice->discount > 0)
                    <div class="total-row discount">
                        <span class="label">Desconto:</span>
                        <span class="value">- {{ \App\Helpers\CurrencyHelper::format($invoice->discount) }}</span>
                    </div>
                @endif
                <div class="total-row grand-total">
                    <span class="label">Total Geral:</span>
                    <span class="value">{{ \App\Helpers\CurrencyHelper::format($invoice->total) }}</span>
                </div>
            </div>
        </div>

        @if($invoice->notes)
            <div class="notes-section">
                <h3 class="section-title">Observações</h3>
                <p>{{ $invoice->notes }}</p>
            </div>
        @endif

        <!-- Footer -->
        <div class="invoice-footer">
            <div class="footer-content">
                <p>Obrigado por utilizar o <strong>{{ config('app.name') }}</strong></p>
                <div class="timestamp">Documento gerado em {{ now()->format('d/m/Y \à\s H:i') }}</div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --dark-color: #1f2937;
            --light-bg: #f9fafb;
            --border-color: #e5e7eb;
        }

        .invoice-container {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #111827;
            max-width: 100%;
            margin: 0 auto;
            line-height: 1.4;
        }

        /* Header */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }

        .brand-name {
            color: var(--primary-color);
            margin: 0;
            font-size: 32px;
            font-weight: 800;
        }

        .invoice-title {
            text-align: right;
        }

        .invoice-title h1 {
            margin: 0;
            font-size: 36px;
            color: var(--dark-color);
            font-weight: 900;
            letter-spacing: -1px;
        }

        .invoice-number {
            font-size: 18px;
            color: var(--secondary-color);
            margin: 0;
            font-weight: 600;
        }

        /* Meta Grid */
        .invoice-meta-grid {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            background: var(--light-bg);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .meta-item .label {
            display: block;
            font-size: 11px;
            color: var(--secondary-color);
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .meta-item .value {
            font-size: 15px;
            font-weight: 700;
            color: var(--dark-color);
        }

        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 800;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Info Grid */
        .info-grid {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            gap: 20px;
        }

        .info-box {
            flex: 1;
            padding: 0;
        }

        .section-title {
            font-size: 12px;
            text-transform: uppercase;
            color: var(--secondary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 6px;
            margin-bottom: 15px;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .info-content p {
            margin: 4px 0;
            font-size: 14px;
        }

        .info-content .name {
            font-size: 16px;
            color: var(--dark-color);
        }

        .service-summary {
            margin-bottom: 35px;
            background: white;
            border-left: 5px solid var(--primary-color);
            padding: 15px 20px;
            border-radius: 0 8px 8px 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .service-summary p {
            margin: 0;
            font-size: 14px;
            color: #4b5563;
        }

        /* Table Items */
        .table-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 35px;
        }

        .table-items th {
            background-color: var(--dark-color);
            color: white;
            padding: 14px 18px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .table-items td {
            padding: 16px 18px;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
            vertical-align: middle;
        }

        .table-items tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .text-muted { color: #6b7280; }
        .text-uppercase { text-transform: uppercase; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }

        /* Totals */
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 50px;
        }

        .totals-wrapper {
            width: 300px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 15px;
            color: #4b5563;
        }

        .total-row.discount {
            color: var(--danger-color);
            font-weight: 700;
        }

        .grand-total {
            border-top: 3px solid var(--dark-color);
            margin-top: 15px;
            padding-top: 20px;
            font-size: 22px;
            font-weight: 900;
            color: var(--primary-color);
        }

        .notes-section {
            background: #fffbeb;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 40px;
            border: 1px solid #fef3c7;
        }

        .notes-section p {
            margin: 0;
            font-size: 14px;
            color: #92400e;
            font-style: italic;
        }

        /* Footer */
        .invoice-footer {
            margin-top: 60px;
            border-top: 1px solid var(--border-color);
            padding-top: 25px;
            text-align: center;
        }

        .footer-content {
            color: var(--secondary-color);
            font-size: 13px;
        }

        .timestamp {
            margin-top: 10px;
            font-size: 11px;
            color: #9ca3af;
        }

        /* PDF Optimization */
        @media print {
            .invoice-container { width: 100%; padding: 0; }
            .status-badge { -webkit-print-color-adjust: exact; }
            .table-items th { -webkit-print-color-adjust: exact; background-color: #1f2937 !important; }
            .invoice-meta-grid { -webkit-print-color-adjust: exact; background-color: #f9fafb !important; }
        }
    </style>
@endsection
