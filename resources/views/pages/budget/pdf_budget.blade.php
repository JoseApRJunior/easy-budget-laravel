@extends( 'layout.pdf_base' )

@section( 'content' )
    <div class="container p-4 print-content">
        <!-- Header -->
        <div class="mb-4">
            <div class="row">
                <!-- Company Data -->
                <div class="col-6">
                    <h5 class="fw-bold mb-2">{{ auth()->user()->company_name }}</h5>
                    <div class="text-secondary small">
                        <p class="mb-1">{{ auth()->user()->address }}</p>
                        <p class="mb-1">
                            @if( auth()->user()->cnpj )
                                CNPJ: {{ auth()->user()->cnpj }}
                            @else
                                CPF: {{ auth()->user()->cpf }}
                            @endif
                        </p>
                        <p class="mb-1">Tel: {{ auth()->user()->phone }}</p>
                        <p class="mb-0">Email: {{ auth()->user()->email_business }}</p>
                    </div>
                </div>

                <!-- Budget Number and Info -->
                <div class="col-6 text-end">
                    <h4 class="text-primary mb-2">ORÇAMENTO #{{ $budget->code }}</h4>
                    <div class="text-secondary small">
                        <p class="mb-1">Emissão: {{ $budget->created_at->format( 'd/m/Y' ) }}</p>
                        <p class="mb-1">Validade: {{ $budget->due_date->format( 'd/m/Y' ) }}</p>
                    </div>
                    <span class="badge"
                        style="background-color: {{ $budget->status->color }};">{{ $budget->status->name }}</span>
                </div>
            </div>
        </div>

        <hr class="my-4">

        <!-- Customer Data -->
        <div class="mb-4">
            <h6 class="text-secondary border-bottom pb-2">DADOS DO CLIENTE</h6>
            <div class="row mt-3">
                <div class="col-6">
                    <p class="fw-medium mb-1">{{ $budget->customer->first_name }} {{ $budget->customer->last_name }}</p>
                    @if( $budget->customer->cpf )
                        <p class="text-secondary small mb-1">CPF: {{ $budget->customer->cpf }}</p>
                    @endif
                    @if( $budget->customer->cnpj )
                        <p class="text-secondary small mb-1">CNPJ: {{ $budget->customer->cnpj }}</p>
                    @endif
                </div>
                <div class="col-6 text-end">
                    <p class="text-secondary small mb-1">Tel: {{ $budget->customer->phone }}</p>
                    <p class="text-secondary small mb-1">Email: {{ $budget->customer->email }}</p>
                </div>
            </div>
        </div>

        <!-- Budget Description -->
        @if( $budget->description )
            <div class="mb-4">
                <h6 class="text-secondary border-bottom pb-2">DESCRIÇÃO</h6>
                <p class="mt-3 mb-0">{{ $budget->description }}</p>
            </div>
        @endif

        <!-- Linked Services -->
        @if( $budget->services->isNotEmpty() )
            @foreach( $budget->services as $service )
                <div class="mb-4">
                    <div class="bg-light p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">{{ $service->category->name }}</h6>
                            <span class="badge"
                                style="background-color: {{ $service->status->color }};">{{ $service->status->name }}</span>
                        </div>
                    </div>

                    <div class="text-secondary small mt-3 mb-3">
                        <p class="mb-1">{{ $service->description }}</p>
                        <p class="mb-1">Data: {{ $service->created_at->format( 'd/m/Y' ) }}</p>
                        <p class="mb-0">Validade: {{ $service->due_date->format( 'd/m/Y' ) }}</p>
                    </div>

                    <!-- Service Items Table -->
                    <div class="table-responsive">
                        <table class="table table-borderless">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th class="text-center" width="100">Qtd</th>
                                    <th class="text-end" width="140">Valor Unit.</th>
                                    <th class="text-end" width="140">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach( $service->items as $item )
                                    <tr>
                                        <td>
                                            <p class="fw-medium mb-0">{{ $item->name }}</p>
                                            @if( $item->description )
                                                <small class="text-secondary">{{ $item->description }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $item->quantity }}</td>
                                        <td class="text-end">R$ {{ number_format( $item->unit_value, 2, ',', '.' ) }}</td>
                                        <td class="text-end">R$ {{ number_format( $item->quantity * $item->unit_value, 2, ',', '.' ) }}
                                        </td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td colspan="3" class="text-end border-top pt-2"><strong>Total do Serviço:</strong></td>
                                    <td class="text-end border-top pt-2"><strong>R$
                                            {{ number_format( $service->total, 2, ',', '.' ) }}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach

            <!-- Total Budget Value -->
            <div class="bg-primary text-white p-3 mb-4">
                <div class="row align-items-center">
                    <div class="col-6">
                        <h6 class="mb-0">VALOR TOTAL DO ORÇAMENTO</h6>
                    </div>
                    <div class="col-6 text-end">
                        <h4 class="mb-0">R$ {{ number_format( $budget->total, 2, ',', '.' ) }}</h4>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-info">Nenhum serviço vinculado.</div>
        @endif

        <!-- Conditions and Observations -->
        <div class="row">
            @if( $budget->payment_terms )
                <div class="col-md-6 mb-4">
                    <h6 class="text-secondary border-bottom pb-2">CONDIÇÕES DE PAGAMENTO</h6>
                    <p class="mt-3 mb-0">{{ $budget->payment_terms }}</p>
                </div>
            @endif
            <div class="col-md-6 mb-4">
                <h6 class="text-secondary border-bottom pb-2">OBSERVAÇÕES</h6>
                <div class="text-secondary small mt-3">
                    <p class="mb-1">* Orçamento válido até {{ $budget->due_date->format( 'd/m/Y' ) }}</p>
                    <p class="mb-0">* Gerado em {{ now()->format( 'd/m/Y H:i:s' ) }}</p>
                </div>
            </div>
        </div>

        <!-- Signatures -->
        <div class="row mt-5">
            <div class="col-6">
                <div class="text-center">
                    <div class="border-top pt-2" style="display: inline-block; min-width: 200px;">
                        <p class="mb-0">
                            {{ auth()->user()->cnpj ? auth()->user()->company_name : auth()->user()->first_name . ' ' . auth()->user()->last_name }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="text-center">
                    <div class="border-top pt-2" style="display: inline-block; min-width: 200px;">
                        <p class="mb-0">{{ $budget->customer->first_name }} {{ $budget->customer->last_name }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Print and Back Buttons -->
        <div class="d-print-none text-end mt-4">
            <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer me-2"></i>Imprimir</button>
            <button onclick="history.back()" class="btn btn-outline-secondary ms-2"><i
                    class="bi bi-arrow-left me-2"></i>Voltar</button>
        </div>
    </div>
@endsection

@push( 'styles' )
    <style>
        /* Page settings */
        @page {
            margin: 15mm;
            size: A4 portrait;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #2c3e50;
        }

        .container {
            padding: 20px;
        }

        .row:after {
            content: "";
            display: table;
            clear: both;
        }

        .col-6 {
            float: left;
            width: 48%;
        }

        .col-6:last-child {
            float: right;
        }

        h4 {
            font-size: 16px;
        }

        h5 {
            font-size: 14px;
        }

        h6 {
            font-size: 12px;
        }

        .text-primary {
            color: #0d6efd;
        }

        .text-secondary {
            color: #6c757d;
        }

        .text-white {
            color: #ffffff;
        }

        .small {
            font-size: 11px;
        }

        .mb-4 {
            margin-bottom: 20px;
        }

        .mb-2 {
            margin-bottom: 10px;
        }

        .mb-1 {
            margin-bottom: 5px;
        }

        .mb-0 {
            margin-bottom: 0;
        }

        .mt-3 {
            margin-top: 15px;
        }

        .mt-5 {
            margin-top: 25px;
        }

        .p-3 {
            padding: 15px;
        }

        .pt-2 {
            padding-top: 10px;
        }

        .pb-2 {
            padding-bottom: 10px;
        }

        .text-end {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .border-top {
            border-top: 1px solid #dee2e6;
        }

        .border-bottom {
            border-bottom: 1px solid #dee2e6;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .table th {
            background-color: #f8f9fa !important;
            padding: 8px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .table td {
            padding: 8px;
            border-bottom: 1px solid #dee2e6;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            font-size: 11px;
            font-weight: bold;
            color: #ffffff;
        }

        .bg-light {
            background-color: #f8f9fa !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .bg-primary {
            background-color: #0d6efd !important;
            color: #ffffff !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        hr {
            border: 0;
            border-top: 1px solid #dee2e6;
            margin: 15px 0;
        }

        @media print {
            .d-print-none {
                display: none !important;
            }
        }
    </style>
@endpush
