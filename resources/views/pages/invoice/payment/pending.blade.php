@extends( 'layouts.app' )

@section( 'content' )
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body text-center">
                        <h2 class="card-title text-warning">
                            <i class="bi bi-clock"></i>
                            Pagamento Pendente
                        </h2>
                        <p class="card-text">
                            {{ $message }}
                        </p>
                        <hr>
                        <h4>Detalhes da Transação</h4>
                        <ul class="list-unstyled">
                            <li>
                                <strong>ID da Transação:</strong>
                                {{ $payment_id }}
                            </li>

                            <li>
                                <strong>Fatura:</strong>
                                {{ $invoice_code }}
                            </li>
                            <li>
                                <strong>Valor:</strong>
                                R$
                                {{ number_format( $total, 2, ',', '.' ) }}
                            </li>
                            <li>
                                <strong>Data:</strong>
                                {{ \Carbon\Carbon::parse( $transaction_date )->format( "d/m/Y H:i:s" ) }}
                            </li>
                        </ul>

                        <hr>
                        <p>
                            Fique tranquilo, você receberá uma notificação assim que o pagamento for processado.
                        </p>
                        <a href="{{ $invoice_paymant_link }}" class="btn btn-primary">Tentar Novamente</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
