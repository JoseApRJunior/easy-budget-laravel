@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="mb-0 text-muted">Status da Fatura</h5>
                            <small class="text-muted">Código: {{ $invoice->code }}</small>
                        </div>
                        <div>
                            <span class="badge bg-{{ $invoice->invoiceStatus->color ?? 'secondary' }} fs-6 px-3 py-2">
                                <i class="bi bi-{{ $invoice->invoiceStatus->icon ?? 'circle' }} me-1"></i>
                                {{ $invoice->invoiceStatus->name }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light border-0">
                                <div class="card-body">
                                    <h6 class="card-title text-muted mb-3">
                                        <i class="bi bi-person-circle me-2"></i>
                                        Cliente
                                    </h6>
                                    <h5 class="mb-1">{{ $invoice->customer->common_data->first_name }} {{ $invoice->customer->common_data->last_name }}</h5>
                                    <p class="text-muted mb-0">{{ $invoice->customer->contact->email }}</p>
                                    @if($invoice->customer->contact->phone)
                                        <p class="text-muted mb-0">{{ $invoice->customer->contact->phone }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card bg-light border-0">
                                <div class="card-body">
                                    <h6 class="card-title text-muted mb-3">
                                        <i class="bi bi-receipt me-2"></i>
                                        Serviço
                                    </h6>
                                    <h5 class="mb-1">{{ $invoice->service->code }}</h5>
                                    <p class="text-muted mb-0">{{ $invoice->service->description }}</p>
                                    <p class="mb-0">
                                        <strong>Total: R$ {{ number_format($invoice->service->total, 2, ',', '.') }}</strong>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-0">
                                <div class="card-body">
                                    <h6 class="card-title mb-3">
                                        <i class="bi bi-file-earmark-text me-2"></i>
                                        Detalhes da Fatura
                                    </h6>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <strong>Valor Subtotal:</strong><br>
                                            R$ {{ number_format($invoice->subtotal, 2, ',', '.') }}
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Desconto:</strong><br>
                                            R$ {{ number_format($invoice->discount, 2, ',', '.') }}
                                        </div>
                                        <div class="col-md-4">
                                            <strong class="text-success fs-5">Total: R$ {{ number_format($invoice->total, 2, ',', '.') }}</strong>
                                        </div>
                                    </div>

                                    @if($invoice->due_date)
                                        <div class="row mt-2">
                                            <div class="col-md-4">
                                                <strong>Vencimento:</strong><br>
                                                {{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}
                                            </div>
                                        </div>
                                    @endif

                                    @if($invoice->payment_method)
                                        <div class="row mt-2">
                                            <div class="col-md-4">
                                                <strong>Forma de Pagamento:</strong><br>
                                                {{ $invoice->payment_method }}
                                            </div>
                                        </div>
                                    @endif

                                    @if($invoice->transaction_date)
                                        <div class="row mt-2">
                                            <div class="col-md-4">
                                                <strong>Data do Pagamento:</strong><br>
                                                {{ \Carbon\Carbon::parse($invoice->transaction_date)->format('d/m/Y H:i') }}
                                            </div>
                                        </div>
                                    @endif

                                    @if($invoice->notes)
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <strong>Observações:</strong><br>
                                                {{ $invoice->notes }}
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($invoice->invoiceStatus->slug === 'pendente')
                        <div class="row">
                            <div class="col-12">
                                <div class="card border-warning">
                                    <div class="card-body">
                                        <h6 class="card-title text-warning mb-3">
                                            <i class="bi bi-exclamation-triangle me-2"></i>
                                            Ação Necessária
                                        </h6>
                                        <p class="mb-3">Por favor, informe o status atual desta fatura:</p>

                                        <form method="POST" action="{{ route('invoices.public.choose-status') }}">
                                            @csrf
                                            <input type="hidden" name="invoice_code" value="{{ $invoice->code }}">
                                            <input type="hidden" name="token" value="{{ $token }}">

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label for="invoice_status_id" class="form-label">Status da Fatura</label>
                                                    <select name="invoice_status_id" id="invoice_status_id" class="form-select" required>
                                                        <option value="">Selecione o status...</option>
                                                        @foreach(\App\Models\InvoiceStatus::whereIn('slug', ['pago', 'cancelado', 'vencido'])->get() as $status)
                                                            <option value="{{ $status->id }}" {{ old('invoice_status_id') == $status->id ? 'selected' : '' }}>
                                                                {{ $status->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @error('invoice_status_id')
                                                        <div class="text-danger small">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="col-md-6 d-flex align-items-end">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="bi bi-check-circle me-2"></i>
                                                        Atualizar Status
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <a href="{{ route('invoices.public.print', ['code' => $invoice->code, 'token' => $token]) }}"
                                   class="btn btn-outline-secondary" target="_blank">
                                    <i class="bi bi-printer me-2"></i>
                                    Imprimir
                                </a>

                                <div class="text-muted small">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Link válido até: {{ \Carbon\Carbon::parse($invoice->userConfirmationToken->expires_at)->format('d/m/Y H:i') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.card {
    border-radius: 12px;
}
.badge {
    border-radius: 20px;
}
.btn {
    border-radius: 8px;
}
</style>
@endpush
