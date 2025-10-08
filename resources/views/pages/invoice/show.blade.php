@extends( 'layout.app' )

@section( 'content' )
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-receipt-cutoff me-2"></i>Detalhes da Fatura
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.dashboard' ) }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.invoices.index' ) }}">Faturas</a></li>
                    <li class="breadcrumb-item active">{{ $invoice->code }}</li>
                </ol>
            </nav>
        </div>

        <div class="row g-4">
            <!-- Main Column -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <!-- Invoice Header -->
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <div>
                                <h2 class="h4 mb-1">Fatura #{{ $invoice->code }}</h2>
                                <p class="text-muted mb-0">Gerada em: {{ $invoice->created_at->format( 'd/m/Y' ) }}</p>
                            </div>
                            <span class="badge fs-6" style="background-color: {{ $invoice->status_color }};">
                                <i class="bi {{ $invoice->status_icon }} me-1"></i> {{ $invoice->status_name }}
                            </span>
                        </div>

                        <!-- Customer and Provider Info -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-uppercase text-muted small">Faturado para</h6>
                                <p class="mb-1"><strong>{{ $invoice->customer_name }}</strong></p>
                                <p class="mb-1">{{ $invoice->customer_email_business ?? $invoice->customer_email }}</p>
                                <p class="mb-0">{{ $invoice->customer_phone_business ?? $invoice->customer_phone }}</p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <h6 class="text-uppercase text-muted small">De</h6>
                                <p class="mb-1">
                                    <strong>{{ $invoice->provider_company_name ?? $invoice->provider_name }}</strong></p>
                                <p class="mb-1">{{ $invoice->provider_email }}</p>
                                <p class="mb-0">{{ $invoice->provider_phone }}</p>
                            </div>
                        </div>

                        <!-- Invoice Items -->
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
                                                <a
                                                    href="{{ route( 'provider.services.show', $invoice->service_code ) }}"><strong>{{ $invoice->service_code }}</strong></a>
                                            </p>
                                            <small class="text-muted">{{ $invoice->service_description }}</small>
                                        </td>
                                        <td class="text-end">R$ {{ number_format( $invoice->subtotal, 2, ',', '.' ) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Notes -->
                        @if ( $invoice->notes )
                            <div class="mt-4">
                                <h6 class="text-uppercase text-muted small">Observações</h6>
                                <p class="text-muted">{{ $invoice->notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent p-4">
                        <h5 class="mb-0"><i class="bi bi-wallet2 me-2"></i>Resumo do Pagamento</h5>
                    </div>
                    <div class="card-body p-4">
                        <ul class="list-group list-group-flush mb-4">
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                Subtotal
                                <span>R$ {{ number_format( $invoice->subtotal, 2, ',', '.' ) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                Desconto
                                <span class="text-danger">- R$ {{ number_format( $invoice->discount, 2, ',', '.' ) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 rounded mt-2">
                                <strong class="h5 mb-0">Total a Pagar</strong>
                                <strong class="h5 mb-0 text-success">R$
                                    {{ number_format( $invoice->total, 2, ',', '.' ) }}</strong>
                            </li>
                        </ul>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Data de Vencimento:</span>
                            <span
                                class="fw-semibold @if( $invoice->due_date->isPast() && $invoice->status_slug == 'pending' ) text-danger @endif">
                                {{ $invoice->due_date->format( 'd/m/Y' ) }}
                            </span>
                        </div>

                        @if ( $invoice->transaction_date )
                            <div class="d-flex justify-content-between align-items-center mb-3 text-success">
                                <span class="text-muted">Pago em:</span>
                                <span class="fw-semibold">{{ $invoice->transaction_date->format( 'd/m/Y' ) }}</span>
                            </div>
                        @endif

                        <!-- Actions -->
                        <div class="d-grid gap-2 mt-4">
                            @if ( $invoice->status_slug == 'pending' )
                                <button class="btn btn-success">
                                    <i class="bi bi-check-circle me-2"></i>Marcar como Paga
                                </button>
                                <button class="btn btn-outline-danger">
                                    <i class="bi bi-x-circle me-2"></i>Cancelar Fatura
                                </button>
                            @endif

                            @if ( $invoice->status_slug != 'paid' && $invoice->status_slug != 'cancelled' && $invoice->public_hash )
                                <div class="card mt-4">
                                    <div class="card-body bg-light">
                                        <h5 class="card-title"><i class="bi bi-link-45deg"></i> Link de Pagamento para o Cliente
                                        </h5>
                                        <p class="card-text text-muted">Envie este link para seu cliente visualizar a fatura e
                                            realizar o pagamento de forma segura.</p>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="public-link"
                                                value="{{ url( '/invoices/view/' . $invoice->public_hash ) }}" readonly>
                                            <button class="btn btn-outline-secondary" type="button" id="copy-link-btn"
                                                data-bs-toggle="tooltip" data-bs-placement="top"
                                                title="Copiar para a área de transferência">
                                                <i class="bi bi-clipboard"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <a href="{{ route( 'provider.invoices.print', $invoice->code ) }}" target="_blank"
                                class="btn btn-primary">
                                <i class="bi bi-printer me-2"></i>Imprimir Fatura
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <a href="{{ route( 'provider.invoices.index' ) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Voltar para Faturas
            </a>
        </div>
    </div>
@endsection

@push( 'scripts' )
    <script>
        document.addEventListener( 'DOMContentLoaded', function () {
            var tooltipTriggerList = [].slice.call( document.querySelectorAll( '[data-bs-toggle="tooltip"]' ) )
            var tooltipList = tooltipTriggerList.map( function ( tooltipTriggerEl ) {
                return new bootstrap.Tooltip( tooltipTriggerEl )
            } )

            const copyButton = document.getElementById( 'copy-link-btn' );
            if ( copyButton ) {
                const tooltip = bootstrap.Tooltip.getInstance( copyButton );

                copyButton.addEventListener( 'click', function () {
                    const linkInput = document.getElementById( 'public-link' );
                    navigator.clipboard.writeText( linkInput.value ).then( function () {
                        copyButton.setAttribute( 'data-bs-original-title', 'Copiado!' );
                        tooltip.show();
                        setTimeout( () => { tooltip.hide(); copyButton.setAttribute( 'data-bs-original-title', 'Copiar para a área de transferência' ); }, 2000 );
                    } );
                } );
            }
        } );
    </script>
@endpush
