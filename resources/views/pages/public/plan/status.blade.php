@extends( 'layouts.app' )

@section( 'title', 'Status do Pagamento' )

@section( 'content' )
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card text-center shadow-sm border-0">
                    <div class="card-body p-4 p-md-5">
                        {{-- Icon and Color based on status --}}
                        @if ( $status == 'success' )
                            <div class="icon-circle bg-success-soft text-success mx-auto mb-4">
                                <i class="fas fa-check fa-3x"></i>
                            </div>
                            <h2 class="card-title text-success h3">{{ $message }}</h2>
                        @elseif ( $status == 'pending' )
                            <div class="icon-circle bg-warning-soft text-warning mx-auto mb-4">
                                <i class="fas fa-hourglass-half fa-3x"></i>
                            </div>
                            <h2 class="card-title text-warning h3">{{ $message }}</h2>
                        @else {{-- failure --}}
                            <div class="icon-circle bg-danger-soft text-danger mx-auto mb-4">
                                <i class="fas fa-times fa-3x"></i>
                            </div>
                            <h2 class="card-title text-danger h3">{{ $message }}</h2>
                        @endif

                        <p class="card-text text-muted mt-3 mb-4">{{ $details }}</p>

                        @if ( $plan )
                            <h4>Detalhes da Transação</h4>
                            <ul class="list-unstyled">
                                <li>
                                    <strong>ID da Transação:</strong>
                                    {{ $payment_id }}
                                </li>
                            </ul>
                            <div class="card border-dashed">
                                <div class="card-body">
                                    <h5 class="mb-3">Detalhes do Plano</h5>
                                    <p class="mb-1"><strong>Plano:</strong> {{ $plan->name}}</p>
                                    <p class="mb-1"><strong>Descrição:</strong> {{ $plan->description}}</p>
                                    <p class="mb-0"><strong>Valor:</strong> R$ {{ number_format( $plan->price, 2, ',', '.' ) }}
                                    </p>
                                </div>
                            </div>
                            @if ( $status == 'success' )
                                <a href="/provider" class="btn btn-success mt-4">Ir para o Painel</a>
                            @else
                                <a href="{{ url( '/plans' ) }}" class="btn btn-primary mt-4">
                                    <i class="bi-clipboard-check me-2"></i>Planos Disponíveis
                                </a>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section( 'styles' )
    @parent
    <style>
        .icon-circle {
            height: 80px;
            width: 80px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .bg-success-soft {
            background-color: rgba(25, 135, 84, 0.1);
        }

        .bg-warning-soft {
            background-color: rgba(255, 193, 7, 0.1);
        }

        .bg-danger-soft {
            background-color: rgba(220, 53, 69, 0.1);
        }

        .border-dashed {
            border-style: dashed !important;
        }
    </style>
@endsection
