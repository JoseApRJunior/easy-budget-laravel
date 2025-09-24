@extends( 'layouts.app' )

@section( 'content' )
    <div class="container-fluid py-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-receipt me-2"></i>Gerar Fatura para o Serviço #{{ $invoice->service_code }}
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/provider">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/provider/services">Serviços</a></li>
                    <li class="breadcrumb-item"><a
                            href="/provider/services/show/{{ $invoice->service_code }}">{{ $invoice->service_code }}</a>
                    </li>
                    <li class="breadcrumb-item active">Gerar Fatura</li>
                </ol>
            </nav>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h4>Para:</h4>
                        <address>
                            <strong>{{ $invoice->customer_name }}</strong><br>
                            {{ $invoice->customer_details->address }}, {{ $invoice->customer_details->address_number }}<br>
                            {{ $invoice->customer_details->neighborhood }}, {{ $invoice->customer_details->city }} -
                            {{ $invoice->customer_details->state }}<br>
                            CEP: {{ $invoice->customer_details->cep }}<br>
                            Email: {{ $invoice->customer_details->email }}
                        </address>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h4>Fatura #...</h4>
                        <p><strong>Data da Fatura:</strong> {{ now()->format( 'd/m/Y' ) }}</p>
                        <p><strong>Data de Vencimento:</strong> {{ $invoice->due_date->format( 'd/m/Y' ) }}</p>
                        <p><strong>Serviço Referente:</strong> #{{ $invoice->service_code }}</p>
                    </div>
                </div>

                @if( $invoice->status == 'PARTIAL' )
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Fatura Parcial:</strong>
                        {{ $invoice->notes ?? 'Esta fatura corresponde à conclusão parcial do serviço.' }}
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="text-center">Qtd.</th>
                                <th class="text-end">Preço Unitário</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach( $invoice->items as $item )
                                <tr>
                                    <td>
                                        <strong>{{ $item->name }}</strong>
                                        <p class="small text-muted mb-0">{{ $item->description }}</p>
                                    </td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end">R$ {{ number_format( $item->unit_value, 2, ',', '.' ) }}</td>
                                    <td class="text-end">R$
                                        {{ number_format( $item->unit_value * $item->quantity, 2, ',', '.' ) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <p class="text-muted"><strong>Notas e Termos:</strong></p>
                        <p>{{ $budget->payment_terms ?? 'Pagamento a ser realizado na data de vencimento.' }}</p>
                    </div>
                    <div class="col-md-6">
                        <div class="table-responsive">
                            <table class="table">
                                <tbody>
                                    <tr>
                                        <th style="width:50%">Subtotal:</th>
                                        <td class="text-end">R$ <span
                                                id="subtotal">{{ number_format( $invoice->subtotal, 2, ',', '.' ) }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Desconto:</th>
                                        <td class="text-end">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">R$</span>
                                                <input type="text" class="form-control text-end" id="discount"
                                                    value="{{ number_format( $invoice->discount, 2, ',', '.' ) }}"
                                                    oninput="formatCurrency(this)" onchange="updateTotal()"
                                                    placeholder="0,00">
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="h5">
                                        <th>Total:</th>
                                        <td class="text-end text-success">R$ <span
                                                id="total">{{ number_format( $invoice->total, 2, ',', '.' ) }}</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="d-flex justify-content-end gap-2">
                    <a href="/provider/services/show/{{ $invoice->service_code }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Voltar
                    </a>
                    <form action="/provider/invoices/store" method="POST" id="invoiceForm">
                        @csrf
                        <input type="hidden" name="service_code" value="{{ $invoice->service_code }}">
                        <input type="hidden" name="invoice_data" value="{{ $invoice->toJson() }}" id="invoiceData">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle-fill me-2"></i>Confirmar e Gerar Fatura
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function formatCurrency( input ) {
            let value = input.value.replace( /\D/g, '' );
            value = ( value / 100 ).toFixed( 2 );
            value = value.replace( '.', ',' );
            value = value.replace( /\B(?=(\d{3})+(?!\d))/g, '.' );
            input.value = value;
        }

        function parseCurrency( value ) {
            return parseFloat( value.replace( /\./g, '' ).replace( ',', '.' ) ) || 0;
        }

        function updateTotal() {
            const subtotalText = '{{ $invoice->subtotal ?? 0 }}';
            const subtotal = parseFloat( subtotalText.replace( /\./g, '' ).replace( ',', '.' ) ) || 0;
            const discountValue = document.getElementById( 'discount' ).value;
            const discount = parseCurrency( discountValue );
            const total = subtotal - discount;

            // Atualizar display do total
            document.getElementById( 'total' ).textContent = total.toLocaleString( 'pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            } );

            // Atualizar dados do formulário
            const invoiceData = JSON.parse( document.getElementById( 'invoiceData' ).value );
            invoiceData.discount = discount;
            invoiceData.total = total;
            document.getElementById( 'invoiceData' ).value = JSON.stringify( invoiceData );
        }
    </script>
@endsection
