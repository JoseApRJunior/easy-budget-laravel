@extends('layouts.app')

@section('title', 'Fatura #' . $invoice->code)

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-receipt-cutoff me-2"></i>Detalhes da Fatura
            </h1>
            {{-- Sem breadcrumbs para a visualização pública --}}
        </div>

        <div class="row g-4">
            <!-- Coluna Principal -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <!-- Cabeçalho da Fatura -->
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <div>
                                <h2 class="h4 mb-1">Fatura #{{ $invoice->code }}</h2>
                                <p class="text-muted mb-0">Gerada em:
                                    {{ \Carbon\Carbon::parse($invoice->created_at)->format('d/m/Y') }}</p>
                            </div>
                            <span class="badge fs-6" style="background-color: {{ $invoice->status_color }};">
                                <i class="bi {{ $invoice->status_icon }} me-1"></i> {{ $invoice->status_name }}
                            </span>
                        </div>

                        <!-- Informações do Cliente e Prestador -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-uppercase text-muted small">Faturado para</h6>
                                <p class="mb-1"><strong>{{ $invoice->customer_name }} </strong></p>
                                <p class="mb-1">{{ $invoice->customer_email_business ?? $invoice->customer_email }}</p>
                                <p class="mb-0">{{ $invoice->customer_phone_business ?? $invoice->customer_phone }}</p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <h6 class="text-uppercase text-muted small">De</h6>
                                <p class="mb-1">
                                    <strong>{{ $invoice->provider_company_name ?? $invoice->provider_name }}</strong>
                                </p>
                                <p class="mb-1">{{ $invoice->provider_email }}</p>
                                <p class="mb-0">{{ $invoice->provider_phone }}</p>
                            </div>
                        </div>

                        <!-- Itens da Fatura -->
                        <div class="table-responsive">
                            <table class="table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Descrição</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <p class="mb-0">Referente ao serviço:
                                                <strong>{{ $invoice->service_code }}</strong>
                                            </p>
                                            <small class="text-muted">{{ $invoice->service_description }}</small>
                                        </td>
                                        <td class="text-end">R$ {{ number_format($invoice->subtotal, 2, ',', '.') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Notas -->
                        @if ($invoice->notes)
                            <div class="mt-4">
                                <h6 class="text-uppercase text-muted small">Observações</h6>
                                <p class="text-muted">{{ $invoice->notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Coluna Lateral -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent p-4">
                        <h5 class="mb-0"><i class="bi bi-wallet2 me-2"></i>Resumo do Pagamento</h5>
                    </div>
                    <div class="card-body p-4">
                        <ul class="list-group list-group-flush mb-4">
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                Subtotal
                                <span>R$ {{ number_format($invoice->subtotal, 2, ',', '.') }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                Desconto
                                <span class="text-danger">- R$
                                    {{ number_format($invoice->discount, 2, ',', '.') }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 rounded mt-2">
                                <strong class="h5 mb-0">Total a Pagar</strong>
                                <strong class="h5 mb-0 text-success">R$
                                    {{ number_format($invoice->total, 2, ',', '.') }}</strong>
                            </li>
                        </ul>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Data de Vencimento:</span>
                            <span class="fw-semibold @if (\Carbon\Carbon::parse($invoice->due_date)->isPast() && $invoice->status_slug == 'pending') text-danger @endif">
                                {{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}
                            </span>
                        </div>

                        @if ($invoice->transaction_date)
                            <div class="d-flex justify-content-between align-items-center mb-3 text-success">
                                <span class="text-muted">Pago em:</span>
                                <span
                                    class="fw-semibold">{{ \Carbon\Carbon::parse($invoice->transaction_date)->format('d/m/Y') }}</span>
                            </div>
                        @endif

                        <!-- Ações -->
                        <div class="d-grid gap-2 mt-4">
                            @if ($invoice->status_slug == 'PENDING')
                                <a href="{{ url('/invoices/pay/' . $invoice->public_hash) }}"
                                    class="btn btn-primary btn-lg">
                                    <i class="bi bi-credit-card me-2"></i> Pagar com Mercado Pago
                                </a>
                                <p class="text-muted mt-2 small text-center">Você será redirecionado para um ambiente seguro
                                    para concluir o pagamento.</p>
                            @elseif ($invoice->status_slug == 'OVERDUE')
                                <a href="{{ url('/invoices/pay/' . $invoice->public_hash) }}"
                                    class="btn btn-danger btn-lg">
                                    <i class="bi bi-exclamation-triangle me-2"></i> Pagar Fatura Vencida
                                </a>
                                <div class="alert alert-warning text-center mt-3" role="alert">
                                    <small><i class="bi bi-clock"></i> Esta fatura está vencida desde
                                        {{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</small>
                                </div>
                            @elseif ($invoice->status_slug == 'PAID')
                                <div class="alert alert-success text-center" role="alert">
                                    <h4 class="alert-heading mb-1"><i class="bi bi-check-circle-fill"></i> Fatura Paga!</h4>
                                    <p class="mb-0">Esta fatura já foi liquidada. Obrigado!</p>
                                    @if ($invoice->transaction_date)
                                        <small class="text-muted">Pago em
                                            {{ \Carbon\Carbon::parse($invoice->transaction_date)->format('d/m/Y H:i') }}</small>
                                    @endif
                                </div>
                            @elseif ($invoice->status_slug == 'CANCELLED')
                                <div class="alert alert-secondary text-center" role="alert">
                                    <h4 class="alert-heading mb-1"><i class="bi bi-x-circle-fill"></i> Fatura Cancelada</h4>
                                    <p class="mb-0">Esta fatura foi cancelada e não pode mais ser paga.</p>
                                </div>
                            @else
                                <div class="alert alert-info text-center" role="alert">
                                    <h4 class="alert-heading mb-1"><i class="bi bi-info-circle-fill"></i> Status:
                                        {{ $invoice->status_name }}</h4>
                                    <p class="mb-0">Entre em contato com o prestador para mais informações.</p>
                                </div>
                            @endif
                        </div>

                    </div>
                    <div class="card-footer text-center text-muted small">
                        Fatura gerada por EasyBudget para {{ $invoice->tenant_company_name }}.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
