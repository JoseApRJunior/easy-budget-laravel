@extends('layouts.pdf_base')

@section('title', 'Fatura #' . $invoice->code)

@section('content')
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="company-info">
                <h1>{{ config('app.name') }}</h1>
                <p>Fatura #{{ $invoice->code }}</p>
                <p>Data de emissão: {{ $invoice->created_at->format('d/m/Y') }}</p>
            </div>
            <div class="invoice-info">
                <h2>Fatura</h2>
                <p><strong>Status:</strong> {{ ucfirst($invoice->status) }}</p>
                <p><strong>Data de vencimento:</strong> {{ $invoice->due_date?->format('d/m/Y') ?? 'N/A' }}</p>
            </div>
        </div>

        <!-- Customer and Service Info -->
        <div class="invoice-details">
            <div class="customer-info">
                <h3>Cliente</h3>
                <p><strong>{{ $invoice->customer->name ?? 'N/A' }}</strong></p>
                @if ($invoice->customer->email)
                    <p>{{ $invoice->customer->email }}</p>
                @endif
                @if ($invoice->customer->phone)
                    <p>{{ $invoice->customer->phone }}</p>
                @endif
            </div>

            <div class="service-info">
                <h3>Serviço</h3>
                <p><strong>{{ $invoice->service->code ?? 'N/A' }}</strong></p>
                <p>{{ Str::limit($invoice->service->description ?? '', 100) }}</p>
            </div>
        </div>

        <!-- Invoice Items -->
        <div class="invoice-items">
            <h3>Itens da Fatura</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th class="text-center">Qtd</th>
                        <th class="text-end">Valor Unit.</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->invoiceItems as $item)
                        <tr>
                            <td>{{ $item->product->name ?? 'N/A' }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($item->unit_value) }}</td>
                            <td class="text-end">{{ \App\Helpers\CurrencyHelper::format($item->total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end">Subtotal:</th>
                        <th class="text-end">{{ \App\Helpers\CurrencyHelper::format($invoice->invoiceItems->sum('total')) }}</th>
                    </tr>
                    <tr>
                        <th colspan="3" class="text-end">Total:</th>
                        <th class="text-end">{{ \App\Helpers\CurrencyHelper::format($invoice->total_amount) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Footer -->
        <div class="invoice-footer">
            <p>Gerado em: {{ now()->format('d/m/Y H:i:s') }}</p>
            <p>Usuário: {{ Auth::user()->name ?? 'N/A' }}</p>
        </div>
    </div>
@endsection

@section('styles')
    <style>
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            font-family: Arial, sans-serif;
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 15px;
        }

        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .customer-info,
        .service-info {
            width: 48%;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .items-table th {
            background-color: #f8f9fa;
        }

        .text-center {
            text-align: center;
        }

        .text-end {
            text-align: right;
        }

        .invoice-footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
@endsection
