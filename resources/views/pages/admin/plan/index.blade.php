@extends( 'layout.app' )

@section( 'content' )
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>{{ $page_title ?? 'Gerenciamento de Assinaturas' }}</h1>
            @if ( $is_history_page )
                <a href="{{ url( '/admin/plans/subscriptions' ) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Voltar para Assinaturas Ativas
                </a>
            @endif
        </div>

        @if ( empty( $subscriptions ) )
            <div class="alert alert-info" role="alert">
                Nenhuma assinatura encontrada.
            </div>
        @else
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Prestador</th>
                                    <th scope="col">Plano</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Início</th>
                                    <th scope="col">Fim</th>
                                    <th scope="col">Valor Pago</th>
                                    <th scope="col">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ( $subscriptions as $subscription )
                                    @php
                                        $status_map = [
                                            'active'    => [ 'class' => 'bg-success', 'text' => 'Ativa' ],
                                            'pending'   => [ 'class' => 'bg-warning text-dark', 'text' => 'Pendente' ],
                                            'cancelled' => [ 'class' => 'bg-dark', 'text' => 'Cancelada' ],
                                            'expired'   => [ 'class' => 'bg-secondary', 'text' => 'Expirada' ]
                                        ];
                                    @endphp
                                    <tr>
                                        <th scope="row">{{ $subscription->id }}</th>
                                        <td>{{ $subscription->provider_name }}</td>
                                        <td>{{ $subscription->plan_name }}</td>
                                        <td>
                                            <span
                                                class="badge {{ $status_map[ $subscription->status ][ 'class' ] ?? 'bg-light text-dark border' }}">
                                                {{ $status_map[ $subscription->status ][ 'text' ] ?? ucfirst( $subscription->status ) }}
                                            </span>
                                        </td>
                                        <td>{{ $subscription->start_date ? date( 'd/m/Y', strtotime( $subscription->start_date ) ) : 'N/A' }}
                                        </td>
                                        <td>{{ $subscription->end_date ? date( 'd/m/Y', strtotime( $subscription->end_date ) ) : 'N/A' }}
                                        </td>
                                        <td>R$ {{ number_format( $subscription->transaction_amount, 2, ',', '.' ) }}</td>
                                        <td>
                                            <a href="{{ url( '/admin/plans/subscription/show/' . $subscription->id ) }}"
                                                class="btn btn-sm btn-primary" title="Ver Detalhes">
                                                <i class="bi bi-eye-fill"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
