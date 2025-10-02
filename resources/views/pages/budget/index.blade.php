@extends( 'layout.app' )

@section( 'content' )
    <div class="container-fluid py-1">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-files me-2"></i>
                Orçamentos
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.dashboard' ) }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Orçamentos</li>
                </ol>
            </nav>
        </div>

        <!-- Action Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-file-earmark-plus fs-2 text-primary"></i>
                        <h5 class="card-title mt-3">Novo Orçamento</h5>
                        <p class="card-text">Crie um novo orçamento detalhado para seus clientes.</p>
                        <a href="{{ route( 'budget.create' ) }}" class="btn btn-primary">Criar Agora</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-file-earmark-text fs-2 text-secondary"></i>
                        <h5 class="card-title mt-3">Gerenciar Orçamentos</h5>
                        <p class="card-text">Visualize e gerencie todos os seus orçamentos existentes.</p>
                        <a href="#budget-list" class="btn btn-outline-secondary">Ver Orçamentos</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-graph-up fs-2 text-info"></i>
                        <h5 class="card-title mt-3">Relatórios</h5>
                        <p class="card-text">Acesse relatórios e análises sobre seus orçamentos.</p>
                        <a href="{{-- route('reports.budgets') --}}" class="btn btn-outline-info">Ver Relatórios</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Budget List and Filters -->
        <div id="budget-list" class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 p-4">
                <h4 class="mb-0">Filtros</h4>
            </div>
            <div class="card-body p-4">
                <form action="{{ route( 'budget.index' ) }}" method="GET">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label for="filter_code" class="form-label">Código</label>
                            <input type="text" id="filter_code" name="filter_code" class="form-control"
                                value="{{ request( 'filter_code' ) }}">
                        </div>
                        <div class="col-md-2">
                            <label for="filter_start_date" class="form-label">Data Início</label>
                            <input type="date" id="filter_start_date" name="filter_start_date" class="form-control"
                                value="{{ request( 'filter_start_date' ) }}">
                        </div>
                        <div class="col-md-2">
                            <label for="filter_end_date" class="form-label">Data Fim</label>
                            <input type="date" id="filter_end_date" name="filter_end_date" class="form-control"
                                value="{{ request( 'filter_end_date' ) }}">
                        </div>
                        <div class="col-md-3">
                            <label for="filter_customer" class="form-label">Cliente</label>
                            <input type="text" id="filter_customer" name="filter_customer" class="form-control"
                                value="{{ request( 'filter_customer' ) }}">
                        </div>
                        <div class="col-md-2">
                            <label for="filter_min_value" class="form-label">Valor Mínimo</label>
                            <input type="number" id="filter_min_value" name="filter_min_value" class="form-control"
                                step="0.01" value="{{ request( 'filter_min_value' ) }}">
                        </div>
                        <div class="col-md-2">
                            <label for="filter_status" class="form-label">Status</label>
                            <select id="filter_status" name="filter_status" class="form-select">
                                <option value="">Todos</option>
                                @foreach( $statuses as $status )
                                    <option value="{{ $status->slug }}" {{ request( 'filter_status' ) == $status->slug ? 'selected' : '' }}>
                                        {{ $status->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="filter_order_by" class="form-label">Ordenar por</label>
                            <select id="filter_order_by" name="filter_order_by" class="form-select">
                                <option value="created_at_desc" {{ request( 'filter_order_by', 'created_at_desc' ) == 'created_at_desc' ? 'selected' : '' }}>Mais Recentes</option>
                                <option value="created_at_asc" {{ request( 'filter_order_by' ) == 'created_at_asc' ? 'selected' : '' }}>Mais Antigos</option>
                                <option value="total_desc" {{ request( 'filter_order_by' ) == 'total_desc' ? 'selected' : '' }}>
                                    Maior Valor</option>
                                <option value="total_asc" {{ request( 'filter_order_by' ) == 'total_asc' ? 'selected' : '' }}>
                                    Menor Valor</option>
                            </select>
                        </div>
                        <div class="col-md-12 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary me-2">Filtrar</button>
                            <a href="{{ route( 'budget.index' ) }}" class="btn btn-outline-secondary">Limpar</a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th>Cliente</th>
                                <th>Data</th>
                                <th>Vencimento</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse( $budgets as $budget )
                                <tr>
                                    <td>{{ $budget->code }}</td>
                                    <td>{{ $budget->customer->first_name }} {{ $budget->customer->last_name }}</td>
                                    <td>{{ $budget->created_at->format( 'd/m/Y' ) }}</td>
                                    <td>{{ $budget->due_date->format( 'd/m/Y' ) }}</td>
                                    <td>R$ {{ number_format( $budget->total, 2, ',', '.' ) }}</td>
                                    <td>
                                        <span class="badge"
                                            style="background-color: {{ $budget->status->color }};">{{ $budget->status->name }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route( 'budget.show', $budget->code ) }}"
                                            class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip"
                                            title="Ver Detalhes">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route( 'budget.edit', $budget->code ) }}"
                                            class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                            data-bs-target="#deleteBudgetModal" data-budget-code="{{ $budget->code }}"
                                            data-budget-id="{{ $budget->id }}" data-bs-toggle="tooltip" title="Excluir">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">Nenhum orçamento encontrado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if( $budgets->hasPages() )
                <div class="card-footer bg-transparent border-0 p-3">
                    {{ $budgets->links( 'vendor.pagination.bootstrap-5' ) }}
                </div>
            @endif
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
                    Tem certeza que deseja excluir o orçamento <strong id="budgetCodeToDelete"></strong>?
                    <p class="text-danger mt-2"><strong>Atenção:</strong> Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form id="deleteBudgetForm" method="POST">
                        @csrf
                        @method( 'DELETE' )
                        <button type="submit" class="btn btn-danger">Excluir</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push( 'scripts' )
    <script>
        document.addEventListener( 'DOMContentLoaded', function () {
            const deleteBudgetModal = document.getElementById( 'deleteBudgetModal' );
            if ( deleteBudgetModal ) {
                deleteBudgetModal.addEventListener( 'show.bs.modal', function ( event ) {
                    const button = event.relatedTarget;
                    const budgetCode = button.getAttribute( 'data-budget-code' );
                    const budgetId = button.getAttribute( 'data-budget-id' );

                    const modalTitle = deleteBudgetModal.querySelector( '.modal-title' );
                    const budgetCodeToDelete = deleteBudgetModal.querySelector( '#budgetCodeToDelete' );
                    const deleteForm = deleteBudgetModal.querySelector( '#deleteBudgetForm' );

                    budgetCodeToDelete.textContent = budgetCode;
                    deleteForm.action = `{{ url( 'provider/budgets' ) }}/${budgetId}`;
                } );
            }
        } );
    </script>
@endpush
