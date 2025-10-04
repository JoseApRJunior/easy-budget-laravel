@php
    use App\Helpers\DateHelper;
    use App\Helpers\StatusHelper;
@endphp

@extends( 'layout.app' )

@section( 'content' )
    <div class="container-fluid py-1">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-file-earmark-text me-2"></i>
                Detalhes do Orçamento
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.dashboard' ) }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route( 'budget.index' ) }}">Orçamentos</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $budget->code }}</li>
                </ol>
            </nav>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-end align-items-center mb-4 gap-2">
            @if ( StatusHelper::status_allows_edit( $budget->status->slug ) )
                <a href="{{ route( 'budget.edit', $budget->code ) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-pencil me-2"></i>Editar
                </a>
            @endif
            <a href="{{ route( 'budget.pdf', $budget->code ) }}" target="_blank" class="btn btn-outline-info">
                <i class="bi bi-printer me-2"></i>Imprimir
            </a>
            <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#changeStatusModal">
                <i class="bi bi-check-circle me-2"></i>Alterar Status
            </button>
            <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteBudgetModal">
                <i class="bi bi-trash me-2"></i>Excluir
            </button>
        </div>

        <div class="row g-4">
            <!-- Main Budget Details -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <small class="text-muted">Código</small>
                                <h5 class="fw-bold mb-0">{{ $budget->code }}</h5>
                            </div>
                            <div class="col-md-5">
                                <small class="text-muted">Cliente</small>
                                <h5 class="mb-0">{{ $budget->customer->first_name }} {{ $budget->customer->last_name }}</h5>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">Status</small>
                                <div>
                                    {!! StatusHelper::status_badge( $budget->status ) !!}
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <small class="text-muted">Descrição</small>
                            <p class="lead">{{ $budget->description }}</p>
                        </div>

                        <!-- Accordion for Additional Details -->
                        <div class="accordion" id="additionalDetailsAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingOne">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                        <i class="bi bi-info-circle me-2"></i>Informações Adicionais
                                    </button>
                                </h2>
                                <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne"
                                    data-bs-parent="#additionalDetailsAccordion">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <strong>Telefone do Cliente:</strong> {{ $budget->customer->phone }}
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <strong>Email do Cliente:</strong> {{ $budget->customer->email }}
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <strong>Criado em:</strong>
                                                {{ DateHelper::format( $budget->created_at, 'd/m/Y H:i' ) }}
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <strong>Atualizado em:</strong>
                                                {{ DateHelper::format( $budget->updated_at, 'd/m/Y H:i' ) }}
                                            </div>
                                            @if( $budget->payment_terms )
                                                <div class="col-12 mb-3">
                                                    <strong>Condições de Pagamento:</strong> {{ $budget->payment_terms }}
                                                </div>
                                            @endif
                                            @if( $budget->attachment )
                                                <div class="col-12 mb-3">
                                                    <strong>Anexos:</strong> {{ $budget->attachment }}
                                                </div>
                                            @endif
                                            @if( $budget->history )
                                                <div class="col-12">
                                                    <strong>Histórico:</strong> {{ $budget->history }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Summary -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent">
                        <h5 class="card-title mb-0"><i class="bi bi-currency-dollar me-2"></i>Resumo Financeiro</h5>
                    </div>
                    <div class="card-body">
                        @php
                            $cancelled_total = $budget->services->where( 'status.slug', 'CANCELLED' )->sum( 'total' );
                            $total_discount  = $budget->discount + $budget->services->sum( 'discount' );
                            $real_total      = $budget->total - $cancelled_total - $total_discount;
                        @endphp
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">Total Bruto: <span
                                    class="fw-bold">R$ {{ number_format( $budget->total, 2, ',', '.' ) }}</span></li>
                            @if( $cancelled_total > 0 )
                                <li class="list-group-item d-flex justify-content-between align-items-center text-danger">
                                    Cancelados: <span>- R$ {{ number_format( $cancelled_total, 2, ',', '.' ) }}</span></li>
                            @endif
                            <li class="list-group-item d-flex justify-content-between align-items-center text-danger">
                                Descontos: <span>- R$ {{ number_format( $total_discount, 2, ',', '.' ) }}</span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center h5 mb-0">
                                <strong>Total a Pagar:</strong> <strong class="text-success">R$
                                    {{ number_format( $real_total, 2, ',', '.' ) }}</strong>
                            </li>
                        </ul>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Vencimento:</span>
                            <span
                                class="fw-bold @if( $budget->due_date->isPast() ) text-danger @endif">{{ DateHelper::formatBR( $budget->due_date ) }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Serviços:</span>
                            <span class="fw-bold">{{ $budget->services->count() }}</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-{{ StatusHelper::status_color_class( $budget->status->color ) }}"
                                role="progressbar"
                                style="width: {{ StatusHelper::status_progress( $budget->status->slug ) }}%;"
                                aria-valuenow="{{ StatusHelper::status_progress( $budget->status->slug ) }}" aria-valuemin="0"
                                aria-valuemax="100" title="{{ $budget->status->name }}"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Linked Services -->
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-transparent p-4">
                <h4 class="mb-0"><i class="bi bi-tools me-2"></i>Serviços Vinculados</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th>Descrição</th>
                                <th>Status</th>
                                <th>Valor</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse( $budget->services as $service )
                                <tr>
                                    <td>{{ $service->code }}</td>
                                    <td>{{ Str::limit( $service->description, 50 ) }}</td>
                                    <td>{!! StatusHelper::status_badge( $service->status ) !!}
                                    </td>
                                    <td>R$ {{ number_format( $service->total, 2, ',', '.' ) }}</td>
                                    <td class="text-end">
                                        <a href="{{ route( 'service.show', $service->code ) }}"
                                            class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4">Nenhum serviço vinculado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Status Modal -->
    <div class="modal fade" id="changeStatusModal" tabindex="-1" aria-labelledby="changeStatusModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route( 'budget.updateStatus', $budget->id ) }}" method="POST">
                    @csrf
                    @method( 'PATCH' )
                    <div class="modal-header">
                        <h5 class="modal-title" id="changeStatusModalLabel">Alterar Status do Orçamento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Selecione o novo status para o orçamento <strong>{{ $budget->code }}</strong>.</p>
                        <select name="status_id" class="form-select">
                            @foreach( StatusHelper::budget_next_statuses( $budget->status->slug ) as $status )
                                <option value="{{ $status[ 'id' ] }}">
                                    {{ $status[ 'name' ] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Alteração</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteBudgetModal" tabindex="-1" aria-labelledby="deleteBudgetModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteBudgetModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Tem certeza que deseja excluir o orçamento <strong>{{ $budget->code }}</strong>?
                    <p class="text-danger mt-2"><strong>Atenção:</strong> Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form action="{{ route( 'budget.destroy', $budget->id ) }}" method="POST">
                        @csrf
                        @method( 'DELETE' )
                        <button type="submit" class="btn btn-danger">Excluir</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
