@extends( 'layouts.app' )

@section( 'content' )
    <div class="container-fluid py-1">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-file-earmark-text me-2"></i>Detalhes do Orçamento
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/">Início</a></li>
                    <li class="breadcrumb-item active">{{ $budget->code }}</li>
                </ol>
            </nav>
        </div>

        <div class="row g-4 mb-4">
            <!-- Main Details -->
            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <!-- Budget Info -->
                        <div class="row g-4 mb-4">
                            <div class="col-md-4">
                                <small class="text-muted">Código</small>
                                <h5 class="fw-semibold mb-0">{{ $budget->code }}</h5>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted">Prestador</small>
                                <h5 class="mb-0">{{ $budget->provider->company_name }}</h5>
                            </div>
                            <div class="col-md-2">
                                <small class="text-muted">Status</small>
                                <span class="badge fs-6"
                                    style="background-color: {{ $budget->status->getColor() }};">{{ $budget->status->getDescription() }}</span>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <small class="text-muted">Descrição</small>
                            <p class="lead mb-0">{{ $budget->description }}</p>
                        </div>

                        <!-- Additional Details -->
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-transparent border-0">
                                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Detalhes Adicionais</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6"><small class="text-muted">Criado em:</small>
                                        {{ $budget->created_at->format( 'd/m/Y H:i' ) }}</div>
                                    <div class="col-md-6"><small class="text-muted">Atualizado em:</small>
                                        {{ $budget->updated_at->format( 'd/m/Y H:i' ) }}</div>
                                    @if( $budget->payment_terms )
                                        <div class="col-12"><small class="text-muted">Condições:</small>
                                            {{ $budget->payment_terms }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Summary -->
            <div class="col-md-4">
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
                            <li class="list-group-item d-flex justify-content-between">Total Bruto: <span
                                    class="fw-semibold">R$ {{ \App\Helpers\CurrencyHelper::format($budget->total) }}</span></li>
                            @if( $cancelled_total > 0 )
                                <li class="list-group-item d-flex justify-content-between text-danger">Cancelados: <span>- R$
                                        {{ \App\Helpers\CurrencyHelper::format($cancelled_total) }}</span></li>
                            @endif
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Desconto Aplicado
                                <span class="badge bg-info rounded-pill">
                                    {{ \App\Helpers\CurrencyHelper::format($total_discount) }}</span></li>
                            <li class="list-group-item d-flex justify-content-between h5 mb-0"><strong>Total:</strong>
                                <strong class="text-success">R$ {{ \App\Helpers\CurrencyHelper::format($real_total) }}</strong></li>
                        </ul>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Vencimento:</span>
                            <span
                                class="fw-semibold @if( $budget->due_date->isPast() ) text-danger @endif">{{ $budget->due_date->format( 'd/m/Y' ) }}</span>
                        </div>
                        <!-- Action Buttons for PENDING status -->
                        @if( $budget->status === \App\Enums\BudgetStatus::PENDING )
                            <div class="d-grid gap-2 mt-4">
                                <button class="btn btn-success btn-lg" data-bs-toggle="modal"
                                    data-bs-target="#approveBudgetModal"><i class="bi bi-check-circle-fill me-2"></i>Aprovar
                                    Orçamento</button>
                                <button class="btn btn-danger btn-lg" data-bs-toggle="modal"
                                    data-bs-target="#rejectBudgetModal"><i class="bi bi-x-circle-fill me-2"></i>Rejeitar
                                    Orçamento</button>
                            </div>
                        @else
                            <div class="alert alert-info text-center">Este orçamento já foi
                                {{ strtolower( $budget->status->getDescription() ) }}.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Linked Services -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent p-4">
                <h4 class="mb-0"><i class="bi bi-tools me-2"></i>Serviços Vinculados</h4>
            </div>
            <div class="card-body p-4">
                @forelse( $budget->services as $service )
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header p-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-tag me-2"></i>{{ $service->category->name }}</h5>
                            <span class="badge"
                                style="background-color: {{ $service->status->getColor() }};">{{ $service->status->getDescription() }}</span>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-4">
                                <div class="col-md-8">
                                    <p class="mb-1"><strong>Descrição:</strong> {{ $service->description }}</p>
                                    <p class="mb-0"><small class="text-muted">Vencimento:
                                            {{ $service->due_date->format( 'd/m/Y' ) }}</small></p>
                                </div>
                                <div class="col-md-4">
                                    <ul class="list-group list-group-flush text-end">
                                        <li class="list-group-item">Total: <span class="fw-bold text-success">R$
                                                {{ \App\Helpers\CurrencyHelper::format($service->total) }}</span></li>
                                        @if( $service->discount > 0 )
                                            <li class="list-group-item">Desconto: <span class="fw-bold text-danger">- R$
                                                    {{ \App\Helpers\CurrencyHelper::format($service->discount) }}</span></li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Valor Final
                                                <span class="badge bg-success rounded-pill">
                                                    {{ \App\Helpers\CurrencyHelper::format($service->total - $service->discount) }}</span>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-center">Nenhum serviço vinculado.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Approve Modal -->
    <div class="modal fade" id="approveBudgetModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route( 'budget.approve', $budget->id ) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Aprovar Orçamento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Você confirma a aprovação do orçamento <strong>{{ $budget->code }}</strong> no valor de <strong
                                class="text-success">R$ {{ \App\Helpers\CurrencyHelper::format($real_total) }}</strong>?</p>
                        <p class="text-muted small">Ao aprovar, o prestador será notificado para iniciar o trabalho.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Confirmar Aprovação</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectBudgetModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route( 'budget.reject', $budget->id ) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Rejeitar Orçamento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Tem certeza que deseja rejeitar o orçamento <strong>{{ $budget->code }}</strong>?</p>
                        <div class="form-group">
                            <label for="rejection_reason">Motivo (opcional):</label>
                            <textarea name="rejection_reason" id="rejection_reason" class="form-control"
                                rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Confirmar Rejeição</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
