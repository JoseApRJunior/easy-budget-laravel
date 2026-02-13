@extends('layouts.print')

@section('title', 'Fatura #' . $invoice->code)

@section('actions')
    <a href="{{ route('provider.invoices.show', $invoice->code) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Voltar
    </a>
@endsection

@section('content')
    <div class="invoice-container">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
            <div>
                @if(isset($invoice->tenant->user->logo))
                    <img src="{{ asset('storage/' . $invoice->tenant->user->logo) }}" alt="Logo" style="max-height: 80px;">
                @else
                    <h1 class="text-primary fw-bold mb-0">{{ config('app.name') }}</h1>
                @endif
            </div>
            <div class="text-end">
                <h1 class="text-uppercase h3 fw-black mb-0">Fatura</h1>
                <p class="text-muted fw-bold mb-0">#{{ $invoice->code }}</p>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-4">
                <div class="info-box h-100">
                    <span class="text-muted small text-uppercase fw-bold d-block mb-1">Data de Emissão</span>
                    <span class="fw-bold">{{ $invoice->created_at->format('d/m/Y') }}</span>
                </div>
            </div>
            <div class="col-4">
                <div class="info-box h-100">
                    <span class="text-muted small text-uppercase fw-bold d-block mb-1">Data de Vencimento</span>
                    <span class="fw-bold">{{ $invoice->due_date?->format('d/m/Y') ?? 'N/A' }}</span>
                </div>
            </div>
            <div class="col-4">
                <div class="info-box h-100 text-center">
                    <span class="text-muted small text-uppercase fw-bold d-block mb-1">Status</span>
                    <span class="badge" style="background-color: {{ $invoice->status->getColor() }}">
                        {{ $invoice->status->label() }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="row g-4 mb-4">
            <div class="col-6">
                <h6 class="text-muted text-uppercase fw-bold border-bottom pb-2 mb-3">Prestador</h6>
                <p class="mb-1"><strong>{{ $invoice->tenant->name ?? config('app.name') }}</strong></p>
                @if(isset($invoice->tenant->user->email))
                    <p class="text-muted small mb-0">{{ $invoice->tenant->user->email }}</p>
                @endif
            </div>

            <div class="col-6">
                <h6 class="text-muted text-uppercase fw-bold border-bottom pb-2 mb-3">Cliente</h6>
                <p class="mb-1"><strong>{{ $invoice->customer->user->name ?? 'N/A' }}</strong></p>
                @if ($invoice->customer->user->email)
                    <p class="text-muted small mb-0">{{ $invoice->customer->user->email }}</p>
                @endif
            </div>
        </div>

        @if($invoice->service)
            <div class="info-box border-start border-4 border-primary mb-4">
                <h6 class="text-muted text-uppercase fw-bold mb-2">Serviço Associado</h6>
                <p class="mb-0"><strong>{{ $invoice->service->code }}</strong> - {{ $invoice->service->description }}</p>
            </div>
        @endif

        <!-- Table Items -->
        <div class="mb-4">
            <table class="table table-striped border">
                <thead class="table-dark">
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
        <div class="row justify-content-end mb-4">
            <div class="col-4">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Subtotal:</span>
                    <span class="fw-bold">{{ \App\Helpers\CurrencyHelper::format($invoice->subtotal ?? $invoice->invoiceItems->sum('total')) }}</span>
                </div>
                @if($invoice->discount > 0)
                    <div class="d-flex justify-content-between mb-2 text-danger">
                        <span>Desconto:</span>
                        <span class="fw-bold">- {{ \App\Helpers\CurrencyHelper::format($invoice->discount) }}</span>
                    </div>
                @endif
                <div class="d-flex justify-content-between border-top pt-3 mt-2 h4 text-primary">
                    <span class="fw-black">Total Geral:</span>
                    <span class="fw-black">{{ \App\Helpers\CurrencyHelper::format($invoice->total) }}</span>
                </div>
            </div>
        </div>

        @if($invoice->notes)
            <div class="info-box bg-light border-warning mb-4">
                <h6 class="text-warning-emphasis text-uppercase fw-bold mb-2">Observações</h6>
                <p class="mb-0 fst-italic">{{ $invoice->notes }}</p>
            </div>
        @endif
    </div>
@endsection

@section('styles')
<style>
    .fw-black { font-weight: 900; }
    @media print {
        .table-dark {
            background-color: #212529 !important;
            color: #fff !important;
        }
        .badge {
            border: 1px solid #dee2e6 !important;
            background-color: transparent !important;
            color: #000 !important;
        }
    }
</style>
@endsection
