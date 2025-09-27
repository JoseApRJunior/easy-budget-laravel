@extends( 'layouts.app' )

@section( 'title', ( $budget->code ?? 'ORC-' . $budget->id ) . ' - Easy Budget' )

@section( 'content' )
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Header -->
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="d-flex align-items-center">
                    <a href="{{ route( 'budgets.index' ) }}" class="btn btn-outline-secondary me-3">
                        <i class="bi bi-arrow-left me-1"></i>
                        Voltar
                    </a>
                    <div>
                        <h1 class="h3 mb-1">
                            <i class="bi bi-receipt text-primary me-2"></i>
                            {{ $budget->code ?? 'ORC-' . $budget->id }}
                        </h1>
                        <p class="text-muted mb-0">{{ $budget->client_name }}</p>
                    </div>
                </div>
                <div class="btn-group" role="group">
                    <a href="{{ route( 'budgets.edit', $budget ) }}" class="btn btn-primary">
                        <i class="bi bi-pencil me-2"></i>
                        Editar
                    </a>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="{{ route( 'budgets.pdf', $budget ) ?? '#' }}">
                                    <i class="bi bi-file-earmark-pdf me-2"></i>
                                    Gerar PDF
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route( 'budgets.duplicate', $budget ) ?? '#' }}">
                                    <i class="bi bi-copy me-2"></i>
                                    Duplicar
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item"
                                    href="mailto:{{ $budget->client_email }}?subject=Orçamento {{ $budget->code ?? 'ORC-' . $budget->id }}">
                                    <i class="bi bi-envelope me-2"></i>
                                    Enviar por Email
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <button type="button" class="dropdown-item text-danger btn-delete"
                                    data-url="{{ route( 'budgets.destroy', $budget ) }}"
                                    data-name="orçamento {{ $budget->code ?? 'ORC-' . $budget->id }}">
                                    <i class="bi bi-trash me-2"></i>
                                    Excluir
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Informações Principais -->
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-info-circle text-primary me-2"></i>
                                Informações do Orçamento
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="bi bi-upc-scan text-muted me-2"></i>
                                        <div>
                                            <small class="text-muted">Código</small>
                                            <div class="fw-bold">{{ $budget->code ?? 'ORC-' . $budget->id }}</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="bi bi-person text-muted me-2"></i>
                                        <div>
                                            <small class="text-muted">Cliente</small>
                                            <div class="fw-bold">{{ $budget->client_name }}</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="bi bi-cash text-muted me-2"></i>
                                        <div>
                                            <small class="text-muted">Valor Total</small>
                                            <div class="fw-bold text-success h5">R$
                                                {{ number_format( $budget->amount, 2, ',', '.' ) }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="bi bi-envelope text-muted me-2"></i>
                                        <div>
                                            <small class="text-muted">Email do Cliente</small>
                                            <div class="fw-bold">{{ $budget->client_email ?? 'Não informado' }}</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="bi bi-telephone text-muted me-2"></i>
                                        <div>
                                            <small class="text-muted">Telefone</small>
                                            <div class="fw-bold">{{ $budget->client_phone ?? 'Não informado' }}</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="bi bi-toggle-on text-muted me-2"></i>
                                        <div>
                                            <small class="text-muted">Status</small>
                                            <div>
                                                @php
                                                    $statusConfig = [
                                                        'draft'    => [ 'class' => 'secondary', 'icon' => 'file-earmark', 'text' => 'Rascunho' ],
                                                        'pending'  => [ 'class' => 'warning', 'icon' => 'clock', 'text' => 'Pendente' ],
                                                        'approved' => [ 'class' => 'success', 'icon' => 'check-circle', 'text' => 'Aprovado' ],
                                                        'rejected' => [ 'class' => 'danger', 'icon' => 'x-circle', 'text' => 'Rejeitado' ]
                                                    ];
                                                    $config       = $statusConfig[ $budget->status ] ?? $statusConfig[ 'draft' ];
                                                @endphp
                                                <span class="badge bg-{{ $config[ 'class' ] }}">
                                                    <i class="bi bi-{{ $config[ 'icon' ] }} me-1"></i>
                                                    {{ $config[ 'text' ] }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if( $budget->notes )
                                <div class="mt-4">
                                    <h6 class="text-muted mb-2">Observações</h6>
                                    <div class="bg-light p-3 rounded">
                                        {!! nl2br( e( $budget->notes ) ) !!}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Itens do Orçamento (se houver) -->
                    @if( isset( $budget->items ) && $budget->items->count() > 0 )
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-list-ul text-primary me-2"></i>
                                    Itens do Orçamento
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Descrição</th>
                                                <th class="text-center">Qtd</th>
                                                <th class="text-center">Valor Unit.</th>
                                                <th class="text-center">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach( $budget->items as $item )
                                                <tr>
                                                    <td>{{ $item->description }}</td>
                                                    <td class="text-center">{{ number_format( $item->quantity, 2, ',', '.' ) }}</td>
                                                    <td class="text-center">R$ {{ number_format( $item->unit_price, 2, ',', '.' ) }}
                                                    </td>
                                                    <td class="text-center fw-bold">R$
                                                        {{ number_format( $item->total, 2, ',', '.' ) }}</td>
                                                </tr>
                                            @endforeach
                                            <tr class="border-top">
                                                <td colspan="3" class="text-end fw-bold">Total:</td>
                                                <td class="text-center fw-bold text-success">R$
                                                    {{ number_format( $budget->amount, 2, ',', '.' ) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Ações Rápidas -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="bi bi-lightning text-primary me-2"></i>
                                Ações Rápidas
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route( 'budgets.pdf', $budget ) ?? '#' }}" class="btn btn-outline-danger">
                                    <i class="bi bi-file-earmark-pdf me-2"></i>
                                    Baixar PDF
                                </a>
                                <a href="mailto:{{ $budget->client_email }}?subject=Orçamento {{ $budget->code ?? 'ORC-' . $budget->id }}"
                                    class="btn btn-outline-info">
                                    <i class="bi bi-envelope me-2"></i>
                                    Enviar Email
                                </a>
                                <a href="{{ route( 'budgets.duplicate', $budget ) ?? '#' }}" class="btn btn-outline-primary">
                                    <i class="bi bi-copy me-2"></i>
                                    Duplicar
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Informações do Responsável -->
                    @if( $budget->user )
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="bi bi-person-circle text-primary me-2"></i>
                                    Responsável
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm me-3">
                                        <div class="avatar-title bg-primary text-white rounded-circle">
                                            {{ strtoupper( substr( $budget->user->name, 0, 1 ) ) }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="fw-bold">{{ $budget->user->name }}</div>
                                        <small class="text-muted">{{ $budget->user->email }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Timeline -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="bi bi-clock-history text-primary me-2"></i>
                                Histórico
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-primary"></div>
                                    <div class="timeline-content">
                                        <small class="text-muted">Orçamento criado</small>
                                        <div class="fw-bold">{{ $budget->created_at->format( 'd/m/Y H:i' ) }}</div>
                                    </div>
                                </div>
                                @if( $budget->updated_at != $budget->created_at )
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-warning"></div>
                                        <div class="timeline-content">
                                            <small class="text-muted">Última atualização</small>
                                            <div class="fw-bold">{{ $budget->updated_at->format( 'd/m/Y H:i' ) }}</div>
                                        </div>
                                    </div>
                                @endif
                                @if( $budget->approved_at )
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-success"></div>
                                        <div class="timeline-content">
                                            <small class="text-muted">Aprovado em</small>
                                            <div class="fw-bold">{{ $budget->approved_at->format( 'd/m/Y H:i' ) }}</div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section( 'styles' )
    <style>
        .avatar-sm {
            width: 2.5rem;
            height: 2.5rem;
        }

        .avatar-title {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.875rem;
        }

        .timeline {
            position: relative;
            padding-left: 1.5rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 0.75rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 1rem;
        }

        .timeline-marker {
            position: absolute;
            left: -1.5rem;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            border: 2px solid #fff;
        }

        .timeline-content {
            padding-bottom: 0.5rem;
        }
    </style>
@endsection

@section( 'scripts' )
    <script>
        // Inicializar event listeners
        document.addEventListener( 'DOMContentLoaded', function () {
            // Event listeners para botões de delete
            document.querySelectorAll( '.btn-delete' ).forEach( button => {
                button.addEventListener( 'click', function () {
                    const url = this.getAttribute( 'data-url' );
                    const name = this.getAttribute( 'data-name' );
                    confirmDelete( url, name );
                } );
            } );
        } );
    </script>
@endsection
