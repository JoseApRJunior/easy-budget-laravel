@extends( 'layouts.app' )

@section( 'content' )
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-receipt me-2"></i>Gerar Fatura para o Serviço #{{ $serviceCode }}
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.dashboard' ) }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.services.index' ) }}">Serviços</a></li>
                    <li class="breadcrumb-item"><a href="#">#{{ $serviceCode }}</a></li>
                    <li class="breadcrumb-item active">Gerar Fatura</li>
                </ol>
            </nav>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <!-- Informações do Cliente e Serviço -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h4>Para:</h4>
                        <address>
                            <strong>{{ $invoiceData[ 'customer_name' ] ?? 'Cliente' }}</strong><br>
                            @if( isset( $invoiceData[ 'customer_details' ]->address ) && $invoiceData[ 'customer_details' ]->address )
                                {{ $invoiceData[ 'customer_details' ]->address }}
                                @if( isset( $invoiceData[ 'customer_details' ]->address_number ) && $invoiceData[ 'customer_details' ]->address_number )
                                    , {{ $invoiceData[ 'customer_details' ]->address_number }}
                                @endif
                                <br>
                            @endif
                            @if( isset( $invoiceData[ 'customer_details' ]->neighborhood ) && $invoiceData[ 'customer_details' ]->neighborhood )
                                {{ $invoiceData[ 'customer_details' ]->neighborhood }}<br>
                            @endif
                            @if( isset( $invoiceData[ 'customer_details' ]->city ) && $invoiceData[ 'customer_details' ]->city )
                                {{ $invoiceData[ 'customer_details' ]->city }}
                                @if( isset( $invoiceData[ 'customer_details' ]->state ) && $invoiceData[ 'customer_details' ]->state )
                                    - {{ $invoiceData[ 'customer_details' ]->state }}
                                @endif
                                <br>
                            @endif
                            @if( isset( $invoiceData[ 'customer_details' ]->cep ) && $invoiceData[ 'customer_details' ]->cep )
                                CEP: {{ $invoiceData[ 'customer_details' ]->cep }}<br>
                            @endif
                        </address>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h4>Fatura #{{ 'FAT-' . date( 'Ymd' ) . 'XXXX' }}</h4>
                        <p><strong>Data da Fatura:</strong> {{ now()->format( 'd/m/Y' ) }}</p>
                        <p><strong>Data de Vencimento:</strong>
                            {{ \Carbon\Carbon::parse( $invoiceData[ 'due_date' ] )->format( 'd/m/Y' ) }}</p>
                        <p><strong>Serviço Referente:</strong> #{{ $invoiceData[ 'service_code' ] }}</p>
                    </div>
                </div>

                @if( $invoiceData[ 'status' ] === 'partial' )
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Fatura Parcial:</strong>
                        {{ $invoiceData[ 'notes' ] ?? 'Esta fatura corresponde à conclusão parcial do serviço.' }}
                    </div>
                @endif

                <!-- Itens da Fatura -->
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
                            @foreach( $invoiceData[ 'items' ] as $item )
                                <tr>
                                    <td>
                                        <strong>{{ $item->product->name ?? 'Produto' }}</strong>
                                        @if( $item->product->description )
                                            <p class="small text-muted mb-0">{{ $item->product->description }}</p>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end">R$ {{ number_format( $item->unit_value, 2, ',', '.' ) }}</td>
                                    <td class="text-end">R$ {{ number_format( $item->total, 2, ',', '.' ) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Resumo -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <p class="text-muted"><strong>Notas e Termos:</strong></p>
                        <p>Pagamento a ser realizado na data de vencimento.</p>
                    </div>
                    <div class="col-md-6">
                        <div class="table-responsive">
                            <table class="table">
                                <tbody>
                                    <tr>
                                        <th style="width:50%">Subtotal:</th>
                                        <td class="text-end">R$ {{ number_format( $invoiceData[ 'subtotal' ], 2, ',', '.' ) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Desconto:</th>
                                        <td class="text-end text-danger">- R$
                                            {{ number_format( $invoiceData[ 'discount' ], 2, ',', '.' ) }}</td>
                                    </tr>
                                    <tr class="h5">
                                        <th>Total:</th>
                                        <td class="text-end text-success">R$
                                            {{ number_format( $invoiceData[ 'total' ], 2, ',', '.' ) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- Formulário de Confirmação -->
                <form action="{{ route( 'provider.invoices.store.from-service' ) }}" method="POST" id="invoiceForm">
                    @csrf
                    <input type="hidden" name="service_code" value="{{ $serviceCode }}">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="issue_date" class="form-label">Data de Emissão *</label>
                            <input type="date" class="form-control @error( 'issue_date' ) is-invalid @enderror"
                                name="issue_date" id="issue_date" value="{{ old( 'issue_date', now()->format( 'Y-m-d' ) ) }}"
                                required>
                            @error( 'issue_date' )
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="due_date" class="form-label">Data de Vencimento *</label>
                            <input type="date" class="form-control @error( 'due_date' ) is-invalid @enderror" name="due_date"
                                id="due_date"
                                value="{{ old( 'due_date', \Carbon\Carbon::parse( $invoiceData[ 'due_date' ] )->format( 'Y-m-d' ) ) }}"
                                required>
                            @error( 'due_date' )
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route( 'provider.services.index' ) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Voltar
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle-fill me-2"></i>Confirmar e Gerar Fatura
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push( 'scripts' )
    <script>
        document.addEventListener( 'DOMContentLoaded', function () {
            // Validação do formulário
            document.getElementById( 'invoiceForm' ).addEventListener( 'submit', function ( e ) {
                const issueDate = document.getElementById( 'issue_date' ).value;
                const dueDate = document.getElementById( 'due_date' ).value;

                if ( new Date( dueDate ) < new Date( issueDate ) ) {
                    e.preventDefault();
                    alert( 'A data de vencimento deve ser posterior ou igual à data de emissão.' );
                    return false;
                }
            } );
        } );
    </script>
@endpush
