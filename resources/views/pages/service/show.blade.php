@extends( 'layout' )

@section( 'content' )
    <div class="container-fluid py-1">
        <!-- Page header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-tools me-2"></i>Detalhes do Serviço
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.dashboard' ) }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.services.index' ) }}">Serviços</a></li>
                    <li class="breadcrumb-item active">{{ $service->code }}</li>
                </ol>
            </nav>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-end gap-2 mb-4">
            @if( $service->status_slug === 'DRAFT' )
                <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#actionModal"
                    data-action="SCHEDULING" data-title="Iniciar Agendamento" data-button-class="btn-success"
                    data-message="Deseja mover o serviço {{ $service->code }} para agendamento?">
                    <i class="bi bi-calendar-plus me-2"></i>Iniciar Agendamento
                </button>
            @endif
            <a href="{{ route( 'provider.services.print', $service->code ) }}" class="btn btn-outline-primary"
                target="_blank">
                <i class="bi bi-printer me-2"></i>Imprimir
            </a>
            <a href="{{ route( 'provider.services.edit', $service->id ) }}" class="btn btn-outline-secondary">
                <i class="bi bi-pencil me-2"></i>Editar
            </a>
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                <i class="bi bi-trash me-2"></i>Excluir
            </button>
        </div>

        <!-- Service Details -->
        <div class="row g-4">
            <!-- Basic Information Card -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-info-circle me-2"></i>Informações do Serviço
                        </h5>
                        @include( 'partials.components.status_badge', [ 'status' => $service->status ] )
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6"><span class="text-muted">Código:</span> <strong
                                    class="ms-2">{{ $service->code }}</strong></div>
                            <div class="col-md-6"><span class="text-muted">Criação:</span> <strong
                                    class="ms-2">{{ $service->created_at->format( 'd/m/Y H:i' ) }}</strong></div>
                            <div class="col-md-6"><span class="text-muted">Cliente:</span> <strong
                                    class="ms-2">{{ $service->customer_name }}</strong></div>
                            <div class="col-md-6"><span class="text-muted">Orçamento:</span> <strong
                                    class="ms-2">{{ $service->budget_code }}</strong></div>
                            <div class="col-md-6"><span class="text-muted">Categoria:</span> <strong
                                    class="ms-2">{{ $service->category->name }}</strong></div>
                            <div class="col-md-6"><span class="text-muted">Vencimento:</span> <strong
                                    class="ms-2 {{ $service->due_date->isPast() ? 'text-danger' : '' }}">{{ $service->due_date->format( 'd/m/Y' ) }}</strong>
                            </div>
                            <div class="col-12 mt-3">
                                <div class="text-muted mb-2">Descrição:</div>
                                <div class="p-3 rounded bg-light">{{ nl2br( e( $service->description ) ) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Summary Card -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-currency-dollar me-2"></i>Resumo Financeiro
                        </h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item ps-0 d-flex justify-content-between align-items-center">Total Bruto:
                                <span class="fw-bold text-success">R$
                                    {{ number_format( $service->total, 2, ',', '.' ) }}</span></li>
                            @if( $service->discount > 0 )
                                <li class="list-group-item ps-0 d-flex justify-content-between align-items-center">Desconto:
                                    <span class="fw-bold text-danger">- R$
                                        {{ number_format( $service->discount, 2, ',', '.' ) }}</span></li>
                                <li class="list-group-item ps-0 d-flex justify-content-between align-items-center"><strong>Total
                                        Líquido:</strong> <strong class="text-success">R$
                                        {{ number_format( $service->total - $service->discount, 2, ',', '.' ) }}</strong></li>
                            @endif
                            <li class="list-group-item ps-0 d-flex justify-content-between align-items-center">Itens: <span
                                    class="fw-semibold">{{ $service->items->count() }}</span></li>
                        </ul>
                        <div class="mt-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Progresso:</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-{{ $service->status->slug | status_color_class }}"
                                    role="progressbar" style="width: {{ $service->status->slug | status_progress }}%;"
                                    aria-valuenow="{{ $service->status->slug | status_progress }}" aria-valuemin="0"
                                    aria-valuemax="100" data-bs-toggle="tooltip" title="{{ $service->status->name }}"></div>
                            </div>
                            <small class="text-muted mt-1 d-block">{{ $service->status->description }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Items -->
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-transparent">
                <h5 class="card-title mb-0">
                    <i class="bi bi-list-check me-2"></i>Itens do Serviço
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%">#</th>
                                <th style="width: 10%">Código</th>
                                <th style="width: 40%">Produto</th>
                                <th class="text-end" style="width: 15%">Valor Unit.</th>
                                <th class="text-center" style="width: 10%">Qtd</th>
                                <th class="text-end" style="width: 20%">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ( $service->items as $item )
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $item->product->code }}</td>
                                    <td>{{ $item->product->name }}</td>
                                    <td class="text-end">R$ {{ number_format( $item->unit_value, 2, ',', '.' ) }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end">R$
                                        {{ number_format( $item->unit_value * $item->quantity, 2, ',', '.' ) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">Nenhum item vinculado a este serviço.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Modal -->
    <div class="modal fade" id="actionModal" tabindex="-1" aria-labelledby="actionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="actionModalLabel"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="actionModalMessage"></p>
                </div>
                <div class="modal-footer">
                    <form id="actionForm" action="{{ route( 'provider.services.updateStatus', $service->id ) }}"
                        method="POST">
                        @csrf
                        @method( 'PATCH' )
                        <input type="hidden" name="action" id="formActionInput">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Voltar</button>
                        <button type="submit" class="btn" id="actionConfirmButton">Confirmar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Tem certeza que deseja excluir o serviço <strong>{{ $service->code }}</strong>?
                    <p class="text-danger mt-2"><strong>Atenção:</strong> Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <form action="{{ route( 'provider.services.destroy', $service->id ) }}" method="POST">
                        @csrf
                        @method( 'DELETE' )
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Confirmar Exclusão</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push( 'scripts' )
    <script>
        document.addEventListener( 'DOMContentLoaded', function () {
            const actionModal = document.getElementById( 'actionModal' );
            if ( actionModal ) {
                actionModal.addEventListener( 'show.bs.modal', function ( event ) {
                    const button = event.relatedTarget;
                    const action = button.getAttribute( 'data-action' );
                    const title = button.getAttribute( 'data-title' );
                    const message = button.getAttribute( 'data-message' );
                    const buttonClass = button.getAttribute( 'data-button-class' ) || 'btn-primary';

                    const modalTitle = actionModal.querySelector( '.modal-title' );
                    const modalMessage = actionModal.querySelector( '#actionModalMessage' );
                    const formActionInput = actionModal.querySelector( '#formActionInput' );
                    const confirmButton = actionModal.querySelector( '#actionConfirmButton' );

                    modalTitle.textContent = title;
                    modalMessage.innerHTML = message;
                    formActionInput.value = action;
                    confirmButton.className = 'btn ' + buttonClass;
                } );
            }
        } );
    </script>
@endpush
