@extends( 'layouts.app' )

@section( 'title', 'Orçamentos' )

@section( 'content' )
    <div class="budgets-index">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Orçamentos</h1>
                <p class="text-muted mb-0">Gerencie seus orçamentos de forma eficiente</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route( 'budgets.create' ) }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-2"></i>Novo Orçamento
                </a>
                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal"
                    data-bs-target="#filterModal">
                    <i class="bi bi-funnel me-2"></i>Filtros
                </button>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-0 bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title mb-0">Total</h6>
                                <h2 class="mb-0">{{ $stats[ 'total' ] }}</h2>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-file-earmark-text fs-1 opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 bg-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title mb-0">Rascunhos</h6>
                                <h2 class="mb-0">{{ $stats[ 'draft' ] }}</h2>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-pencil fs-1 opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 bg-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title mb-0">Enviados</h6>
                                <h2 class="mb-0">{{ $stats[ 'sent' ] }}</h2>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-send fs-1 opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title mb-0">Aprovados</h6>
                                <h2 class="mb-0">{{ $stats[ 'approved' ] }}</h2>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-check-circle fs-1 opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros Rápidos -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="searchInput" class="form-label">Buscar</label>
                        <input type="text" class="form-control" id="searchInput" placeholder="Cliente, número..."
                            value="{{ $filters[ 'search' ] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label for="statusFilter" class="form-label">Status</label>
                        <select class="form-select" id="statusFilter">
                            <option value="">Todos</option>
                            <option value="rascunho" {{ ( $filters[ 'status' ] ?? '' ) === 'rascunho' ? 'selected' : '' }}>
                                Rascunho</option>
                            <option value="enviado" {{ ( $filters[ 'status' ] ?? '' ) === 'enviado' ? 'selected' : '' }}>Enviado
                            </option>
                            <option value="aprovado" {{ ( $filters[ 'status' ] ?? '' ) === 'aprovado' ? 'selected' : '' }}>
                                Aprovado</option>
                            <option value="rejeitado" {{ ( $filters[ 'status' ] ?? '' ) === 'rejeitado' ? 'selected' : '' }}>
                                Rejeitado</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="periodFilter" class="form-label">Período</label>
                        <select class="form-select" id="periodFilter">
                            <option value="">Todo período</option>
                            <option value="today" {{ ( $filters[ 'period' ] ?? '' ) === 'today' ? 'selected' : '' }}>Hoje</option>
                            <option value="week" {{ ( $filters[ 'period' ] ?? '' ) === 'week' ? 'selected' : '' }}>Esta semana
                            </option>
                            <option value="month" {{ ( $filters[ 'period' ] ?? '' ) === 'month' ? 'selected' : '' }}>Este mês
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="sortFilter" class="form-label">Ordenar</label>
                        <select class="form-select" id="sortFilter">
                            <option value="created_at" {{ ( $filters[ 'sort_by' ] ?? '' ) === 'created_at' ? 'selected' : '' }}>
                                Data</option>
                            <option value="budget_number" {{ ( $filters[ 'sort_by' ] ?? '' ) === 'budget_number' ? 'selected' : '' }}>Número</option>
                            <option value="grand_total" {{ ( $filters[ 'sort_by' ] ?? '' ) === 'grand_total' ? 'selected' : '' }}>
                                Valor</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-primary me-2" onclick="applyFilters()">
                            <i class="bi bi-search me-2"></i>Filtrar
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="clearFilters()">
                            <i class="bi bi-x-lg me-2"></i>Limpar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Orçamentos -->
        <div class="card">
            <div class="card-body">
                @if( $budgets->count() > 0 )
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Número</th>
                                    <th>Cliente</th>
                                    <th>Status</th>
                                    <th>Valor</th>
                                    <th>Data</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach( $budgets as $budget )
                                    <tr>
                                        <td>
                                            <strong>{{ $budget->code }}</strong>
                                            @if( $budget->current_version_id )
                                                <br><small
                                                    class="text-muted">v{{ $budget->currentVersion->version_number ?? '1.0' }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-xs me-2">
                                                    <div class="avatar-initial bg-label-primary rounded-circle">
                                                        {{ substr( $budget->customer->name ?? 'N', 0, 1 ) }}
                                                    </div>
                                                </div>
                                                <div>
                                                    <span
                                                        class="fw-medium">{{ $budget->customer->name ?? 'Cliente não identificado' }}</span>
                                                    @if( $budget->description )
                                                        <br><small class="text-muted">{{ Str::limit( $budget->description, 50 ) }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @php
                                                $statusColors = [
                                                    'rascunho'  => 'warning',
                                                    'enviado'   => 'info',
                                                    'aprovado'  => 'success',
                                                    'rejeitado' => 'danger',
                                                    'expirado'  => 'secondary'
                                                ];
                                                $statusColor  = $statusColors[ $budget->budgetStatus->slug ?? 'rascunho' ] ?? 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $statusColor }}">
                                                <i class="bi bi-circle-fill me-1"></i>
                                                {{ $budget->budgetStatus->name ?? 'Indefinido' }}
                                            </span>
                                        </td>
                                        <td>
                                            <strong>R$ {{ number_format( $budget->total, 2, ',', '.' ) }}</strong>
                                            @if( $budget->items->count() > 0 )
                                                <br><small class="text-muted">{{ $budget->items->count() }} itens</small>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $budget->created_at->format( 'd/m/Y' ) }}
                                            <br><small class="text-muted">{{ $budget->created_at->format( 'H:i' ) }}</small>
                                        </td>
                                        <td class="text-end">
                                            <div class="dropdown">
                                                <button class="btn p-0" type="button" data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item" href="{{ route( 'budgets.show', $budget ) }}">
                                                        <i class="bi bi-eye me-2"></i>Visualizar
                                                    </a>
                                                    <a class="dropdown-item" href="{{ route( 'budgets.edit', $budget ) }}">
                                                        <i class="bi bi-pencil me-2"></i>Editar
                                                    </a>
                                                    <a class="dropdown-item" href="{{ route( 'budgets.duplicate', $budget ) }}">
                                                        <i class="bi bi-copy me-2"></i>Duplicar
                                                    </a>
                                                    <div class="dropdown-divider"></div>
                                                    @if( $budget->canBeSent() )
                                                        <a class="dropdown-item text-info" href="#"
                                                            onclick="sendBudget({{ $budget->id }})">
                                                            <i class="bi bi-send me-2"></i>Enviar
                                                        </a>
                                                    @endif
                                                    <a class="dropdown-item" href="{{ route( 'budgets.generate-pdf', $budget ) }}"
                                                        target="_blank">
                                                        <i class="bi bi-file-earmark-pdf me-2"></i>Gerar PDF
                                                    </a>
                                                    @if( $budget->versions->count() > 1 )
                                                        <a class="dropdown-item" href="{{ route( 'budgets.versions', $budget ) }}">
                                                            <i class="bi bi-clock-history me-2"></i>Versões
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    <div class="d-flex justify-content-center mt-4">
                        {{ $budgets->appends( request()->query() )->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bi bi-file-earmark-text fs-1 text-muted mb-3"></i>
                        <h5 class="text-muted">Nenhum orçamento encontrado</h5>
                        <p class="text-muted mb-4">Crie seu primeiro orçamento para começar.</p>
                        <a href="{{ route( 'budgets.create' ) }}" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-2"></i>Criar Primeiro Orçamento
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal de Filtros -->
    <div class="modal fade" id="filterModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filtros Avançados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="advancedFiltersForm">
                        <div class="mb-3">
                            <label class="form-label">Cliente</label>
                            <select class="form-select" name="customer_id">
                                <option value="">Todos os clientes</option>
                                @foreach( \App\Models\Customer::where( 'tenant_id', auth()->user()->tenant_id )->orderBy( 'name' )->get() as $customer )
                                    <option value="{{ $customer->id }}" {{ ( $filters[ 'customer' ] ?? '' ) == $customer->id ? 'selected' : '' }}>
                                        {{ $customer->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Valor Mínimo</label>
                            <input type="number" class="form-control" name="min_value" step="0.01" placeholder="0.00">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Valor Máximo</label>
                            <input type="number" class="form-control" name="max_value" step="0.01" placeholder="999999.99">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="applyAdvancedFilters()">Aplicar Filtros</button>
                </div>
            </div>
        </div>
    </div>

    @push( 'scripts' )
        <script>
            function applyFilters() {
                const search = document.getElementById( 'searchInput' ).value;
                const status = document.getElementById( 'statusFilter' ).value;
                const period = document.getElementById( 'periodFilter' ).value;
                const sort = document.getElementById( 'sortFilter' ).value;

                const params = new URLSearchParams( window.location.search );

                if ( search ) params.set( 'search', search );
                else params.delete( 'search' );

                if ( status ) params.set( 'status', status );
                else params.delete( 'status' );

                if ( period ) params.set( 'period', period );
                else params.delete( 'period' );

                if ( sort ) params.set( 'sort_by', sort );
                else params.delete( 'sort_by' );

                window.location.href = window.location.pathname + '?' + params.toString();
            }

            function clearFilters() {
                window.location.href = window.location.pathname;
            }

            function applyAdvancedFilters() {
                const form = document.getElementById( 'advancedFiltersForm' );
                const formData = new FormData( form );
                const params = new URLSearchParams( window.location.search );

                for ( let [key, value] of formData.entries() ) {
                    if ( value ) params.set( key, value );
                    else params.delete( key );
                }

                // Fechar modal
                document.querySelector( '#filterModal .btn-close' ).click();

                // Aplicar filtros
                window.location.href = window.location.pathname + '?' + params.toString();
            }

            function sendBudget( budgetId ) {
                if ( confirm( 'Deseja enviar este orçamento para o cliente?' ) ) {
                    // Criar form e submit
                    const form = document.createElement( 'form' );
                    form.method = 'POST';
                    form.action = `/budgets/${budgetId}/send`;

                    const csrfInput = document.createElement( 'input' );
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = document.querySelector( 'meta[name="csrf-token"]' ).getAttribute( 'content' );

                    form.appendChild( csrfInput );
                    document.body.appendChild( form );
                    form.submit();
                }
            }

            // Auto-submit search
            document.getElementById( 'searchInput' ).addEventListener( 'keypress', function ( e ) {
                if ( e.key === 'Enter' ) {
                    applyFilters();
                }
            } );
        </script>
    @endpush
@endsection
