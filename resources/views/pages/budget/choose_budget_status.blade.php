@extends( 'layouts.app' )

@section( 'content' )
    <div class="container-fluid py-4">
        <x-layout.page-header
            title="Detalhes do Orçamento #{{ $budget->code }}"
            icon="file-earmark-text"
            :breadcrumb-items="[
                'Início' => url('/'),
                $budget->code => '#'
            ]">
        </x-layout.page-header>

        @if(isset($info) && $info)
            <div class="alert alert-warning alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                {{ $info }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
                <i class="bi bi-exclamation-octagon-fill me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

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
                                <h5 class="mb-0">
                                    @php
                                        $providerName = $budget->tenant->provider->commonData->display_name ?? ($budget->tenant->name ?? 'Prestador');
                                    @endphp
                                    {{ $providerName }}
                                </h5>
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

                        @if(isset($permissions['can_print']) && $permissions['can_print'])
                            <div class="d-grid gap-2 mt-3">
                                <a href="{{ route('budgets.public.print', ['code' => $budget->code, 'token' => $token]) }}" class="btn btn-outline-secondary" target="_blank">
                                    <i class="bi bi-printer me-2"></i>Imprimir Orçamento
                                </a>
                                <a href="{{ route('budgets.public.print', ['code' => $budget->code, 'token' => $token, 'pdf' => true, 'download' => true]) }}" class="btn btn-outline-danger">
                                    <i class="bi bi-file-earmark-pdf me-2"></i>Baixar em PDF
                                </a>
                            </div>
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
                    <form action="{{ route('budgets.public.choose-status.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <input type="hidden" name="action" value="approve">
                        <div class="modal-header">
                            <h5 class="modal-title">Aprovar Orçamento</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Você confirma a aprovação do orçamento <strong>{{ $budget->code }}</strong> no valor de <strong
                                    class="text-success">R$ {{ \App\Helpers\CurrencyHelper::format($real_total) }}</strong>?</p>
                            <p class="text-muted small">Ao aprovar, o prestador será notificado para iniciar o trabalho.</p>
                            <div class="form-group mt-3">
                                <label for="approve_comment">Observações (opcional):</label>
                                <textarea name="comment" id="approve_comment" class="form-control" rows="3" 
                                    maxlength="500" placeholder="Algum detalhe ou instrução adicional?"
                                    oninput="updateCharCount(this, 'approveCharCount')"></textarea>
                                <div class="form-text text-end small" id="approveCharCount">0 / 500 caracteres</div>
                            </div>
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
                    <form action="{{ route('budgets.public.choose-status.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <input type="hidden" name="action" value="reject">
                        <div class="modal-header">
                            <h5 class="modal-title">Rejeitar Orçamento</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Tem certeza que deseja rejeitar o orçamento <strong>{{ $budget->code }}</strong>?</p>
                            <div class="form-group">
                                <label for="rejection_reason">Motivo da Rejeição (opcional):</label>
                                <textarea name="comment" id="rejection_reason" class="form-control"
                                    maxlength="500" rows="3" placeholder="Poderia nos informar o motivo da rejeição para que possamos melhorar?"
                                    oninput="updateCharCount(this, 'rejectCharCount')"></textarea>
                                <div class="form-text text-end small" id="rejectCharCount">0 / 500 caracteres</div>
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

@push('scripts')
<script>
    function updateCharCount(textarea, counterId) {
        const count = textarea.value.length;
        const counter = document.getElementById(counterId);
        if (counter) {
            counter.textContent = `${count} / 500 caracteres`;
            if (count >= 500) {
                counter.classList.add('text-danger');
            } else {
                counter.classList.remove('text-danger');
            }
        }
    }
</script>
@endpush
