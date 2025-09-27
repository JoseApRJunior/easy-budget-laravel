@extends( 'layouts.app' )

@section( 'title', 'Orçamentos - Easy Budget' )

@section( 'content' )
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="bi bi-receipt text-primary me-2"></i>
                    Gerenciar Orçamentos
                </h1>
                <a href="{{ route( 'budgets.create' ) }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>
                    Novo Orçamento
                </a>
            </div>

            <!-- Filtros e Busca -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Buscar</label>
                            <input type="text" class="form-control" id="search" name="search"
                                value="{{ request( 'search' ) }}" placeholder="Código ou cliente...">
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Todos</option>
                                <option value="draft" {{ request( 'status' ) === 'draft' ? 'selected' : '' }}>Rascunho</option>
                                <option value="pending" {{ request( 'status' ) === 'pending' ? 'selected' : '' }}>Pendente
                                </option>
                                <option value="approved" {{ request( 'status' ) === 'approved' ? 'selected' : '' }}>Aprovado
                                </option>
                                <option value="rejected" {{ request( 'status' ) === 'rejected' ? 'selected' : '' }}>Rejeitado
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="user" class="form-label">Usuário</label>
                            <select class="form-select" id="user" name="user">
                                <option value="">Todos</option>
                                @foreach( \App\Models\User::where( 'status', 'active' )->get() as $userOption )
                                    <option value="{{ $userOption->id }}" {{ request( 'user' ) == $userOption->id ? 'selected' : '' }}>
                                        {{ $userOption->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="date_from" class="form-label">Data Inicial</label>
                            <input type="date" class="form-control" id="date_from" name="date_from"
                                value="{{ request( 'date_from' ) }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="bi bi-search me-1"></i>
                                    Filtrar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabela de Orçamentos -->
            <div class="card">
                <div class="card-body">
                    @if( $budgets->count() > 0 )
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>
                                            <a href="{{ request()->fullUrlWithQuery( [ 'sort' => 'code', 'direction' => request( 'direction' ) === 'asc' ? 'desc' : 'asc' ] ) }}"
                                                class="text-decoration-none">
                                                Código
                                                @if( request( 'sort' ) === 'code' )
                                                    <i
                                                        class="bi bi-chevron-{{ request( 'direction' ) === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>
                                            <a href="{{ request()->fullUrlWithQuery( [ 'sort' => 'client_name', 'direction' => request( 'direction' ) === 'asc' ? 'desc' : 'asc' ] ) }}"
                                                class="text-decoration-none">
                                                Cliente
                                                @if( request( 'sort' ) === 'client_name' )
                                                    <i
                                                        class="bi bi-chevron-{{ request( 'direction' ) === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>Usuário</th>
                                        <th>
                                            <a href="{{ request()->fullUrlWithQuery( [ 'sort' => 'amount', 'direction' => request( 'direction' ) === 'asc' ? 'desc' : 'asc' ] ) }}"
                                                class="text-decoration-none">
                                                Valor
                                                @if( request( 'sort' ) === 'amount' )
                                                    <i
                                                        class="bi bi-chevron-{{ request( 'direction' ) === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>Status</th>
                                        <th>Criado em</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach( $budgets as $budget )
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong>{{ $budget->code ?? 'ORC-' . $budget->id }}</strong>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong>{{ $budget->client_name }}</strong>
                                                    @if( $budget->client_email )
                                                        <br>
                                                        <small class="text-muted">
                                                            <i class="bi bi-envelope me-1"></i>
                                                            {{ $budget->client_email }}
                                                        </small>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @if( $budget->user )
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-xs me-2">
                                                            <div class="avatar-title bg-primary text-white rounded-circle">
                                                                {{ strtoupper( substr( $budget->user->name, 0, 1 ) ) }}
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold">{{ $budget->user->name }}</div>
                                                            <small class="text-muted">{{ $budget->user->email }}</small>
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="fw-bold text-success">
                                                    R$ {{ number_format( $budget->amount, 2, ',', '.' ) }}
                                                </span>
                                            </td>
                                            <td>
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
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    {{ $budget->created_at->format( 'd/m/Y' ) }}
                                                </small>
                                                <br>
                                                <small class="text-muted">
                                                    {{ $budget->created_at->format( 'H:i' ) }}
                                                </small>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route( 'budgets.show', $budget ) }}"
                                                        class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip"
                                                        title="Visualizar">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="{{ route( 'budgets.edit', $budget ) }}"
                                                        class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip"
                                                        title="Editar">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <div class="btn-group" role="group">
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                            data-bs-toggle="dropdown">
                                                            <i class="bi bi-three-dots-vertical"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li>
                                                                <a class="dropdown-item"
                                                                    href="{{ route( 'budgets.pdf', $budget ) ?? '#' }}">
                                                                    <i class="bi bi-file-earmark-pdf me-2"></i>
                                                                    Gerar PDF
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item"
                                                                    href="{{ route( 'budgets.duplicate', $budget ) ?? '#' }}">
                                                                    <i class="bi bi-copy me-2"></i>
                                                                    Duplicar
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
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginação -->
                        <div class="d-flex justify-content-center">
                            {{ $budgets->appends( request()->query() )->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-receipt text-muted" style="font-size: 4rem;"></i>
                            <h4 class="mt-3 text-muted">Nenhum orçamento encontrado</h4>
                            <p class="text-muted">Comece criando seu primeiro orçamento para gerenciar seus projetos.</p>
                            <a href="{{ route( 'budgets.create' ) }}" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-2"></i>
                                Criar Primeiro Orçamento
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section( 'scripts' )
    <script>
        // Inicializar tooltips e event listeners
        document.addEventListener( 'DOMContentLoaded', function () {
            // Inicializar tooltips
            var tooltipTriggerList = [].slice.call( document.querySelectorAll( '[data-bs-toggle="tooltip"]' ) )
            var tooltipList = tooltipTriggerList.map( function ( tooltipTriggerEl ) {
                return new bootstrap.Tooltip( tooltipTriggerEl )
            } )

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
