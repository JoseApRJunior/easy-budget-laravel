@extends( 'layouts.app' )

@section( 'title', 'Usuários - Easy Budget' )

@section( 'content' )
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="bi bi-people text-primary me-2"></i>
                    Gerenciar Usuários
                </h1>
                <a href="{{ route( 'users.create' ) }}" class="btn btn-primary">
                    <i class="bi bi-person-plus me-2"></i>
                    Novo Usuário
                </a>
            </div>

            <!-- Filtros e Busca -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Buscar</label>
                            <input type="text" class="form-control" id="search" name="search"
                                value="{{ request( 'search' ) }}" placeholder="Nome ou email...">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Todos</option>
                                <option value="active" {{ request( 'status' ) === 'active' ? 'selected' : '' }}>Ativo</option>
                                <option value="inactive" {{ request( 'status' ) === 'inactive' ? 'selected' : '' }}>Inativo
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="plan" class="form-label">Plano</label>
                            <select class="form-select" id="plan" name="plan">
                                <option value="">Todos</option>
                                @foreach( \App\Models\Plan::where( 'status', 'active' )->get() as $planOption )
                                    <option value="{{ $planOption->id }}" {{ request( 'plan' ) == $planOption->id ? 'selected' : '' }}>
                                        {{ $planOption->name }}
                                    </option>
                                @endforeach
                            </select>
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

            <!-- Tabela de Usuários -->
            <div class="card">
                <div class="card-body">
                    @if( $users->count() > 0 )
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>
                                            <a href="{{ request()->fullUrlWithQuery( [ 'sort' => 'name', 'direction' => request( 'direction' ) === 'asc' ? 'desc' : 'asc' ] ) }}"
                                                class="text-decoration-none">
                                                Nome
                                                @if( request( 'sort' ) === 'name' )
                                                    <i
                                                        class="bi bi-chevron-{{ request( 'direction' ) === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>
                                            <a href="{{ request()->fullUrlWithQuery( [ 'sort' => 'email', 'direction' => request( 'direction' ) === 'asc' ? 'desc' : 'asc' ] ) }}"
                                                class="text-decoration-none">
                                                Email
                                                @if( request( 'sort' ) === 'email' )
                                                    <i
                                                        class="bi bi-chevron-{{ request( 'direction' ) === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>Plano</th>
                                        <th>Tenant</th>
                                        <th>Status</th>
                                        <th>Criado em</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach( $users as $user )
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm me-3">
                                                        <div class="avatar-title bg-primary text-white rounded-circle">
                                                            {{ strtoupper( substr( $user->name, 0, 1 ) ) }}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <strong>{{ $user->name }}</strong>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <i class="bi bi-envelope text-muted me-1"></i>
                                                    {{ $user->email }}
                                                </div>
                                            </td>
                                            <td>
                                                @if( $user->plan )
                                                    <span class="badge bg-info">
                                                        {{ $user->plan->name }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">Sem plano</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if( $user->tenant )
                                                    <small class="text-muted">{{ $user->tenant->name ?? $user->tenant_id }}</small>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if( $user->status === 'active' )
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-check-circle me-1"></i>
                                                        Ativo
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger">
                                                        <i class="bi bi-x-circle me-1"></i>
                                                        Inativo
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    {{ $user->created_at->format( 'd/m/Y' ) }}
                                                </small>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route( 'users.show', $user ) }}" class="btn btn-sm btn-outline-info"
                                                        data-bs-toggle="tooltip" title="Visualizar">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="{{ route( 'users.edit', $user ) }}"
                                                        class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip"
                                                        title="Editar">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    @if( $user->status === 'active' )
                                                        <button type="button" class="btn btn-sm btn-outline-warning btn-delete"
                                                            data-url="{{ route( 'users.destroy', $user ) }}" data-name="{{ $user->name }}"
                                                            data-bs-toggle="tooltip" title="Desativar">
                                                            <i class="bi bi-pause"></i>
                                                        </button>
                                                    @else
                                                        <form method="POST" action="{{ route( 'users.activate', $user ) }}"
                                                            class="d-inline">
                                                            @csrf
                                                            @method( 'PATCH' )
                                                            <button type="submit" class="btn btn-sm btn-outline-success"
                                                                data-bs-toggle="tooltip" title="Ativar">
                                                                <i class="bi bi-play"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginação -->
                        <div class="d-flex justify-content-center">
                            {{ $users->appends( request()->query() )->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-people text-muted" style="font-size: 4rem;"></i>
                            <h4 class="mt-3 text-muted">Nenhum usuário encontrado</h4>
                            <p class="text-muted">Comece criando seu primeiro usuário para acessar o sistema.</p>
                            <a href="{{ route( 'users.create' ) }}" class="btn btn-primary">
                                <i class="bi bi-person-plus me-2"></i>
                                Criar Primeiro Usuário
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
