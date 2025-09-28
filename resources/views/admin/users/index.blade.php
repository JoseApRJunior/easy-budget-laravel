@extends( 'layouts.admin' )

@section( 'title', 'Gerenciar Usuários' )

@section( 'breadcrumb' )
    <li class="breadcrumb-item active">Usuários</li>
@endsection

@section( 'page_actions' )
    <a href="{{ route( 'admin.users.create' ) }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>Novo Usuário </a>
@endsection

@section( 'admin_content' )
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-people me-2"></i>Gerenciar Usuários
                        </h5>
                        <a href="{{ route( 'admin.users.create' ) }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle me-1"></i>Novo Usuário </a>
                    </div>
                    <div class="card-body">
                        @if( isset( $users ) && count( $users ) > 0 )
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nome</th>
                                            <th>Email</th>
                                            <th>Status</th>
                                            <th>Criado em</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach( $users as $user )
                                            <tr>
                                                <td>{{ $user->id }}</td>
                                                <td>{{ $user->name }}</td>
                                                <td>{{ $user->email }}</td>
                                                <td>
                                                    @if( $user->active ?? true )
                                                        <span class="badge bg-success">Ativo</span>
                                                    @else
                                                        <span class="badge bg-secondary">Inativo</span>
                                                    @endif
                                                </td>
                                                <td>{{ $user->created_at ? $user->created_at->format( 'd/m/Y H:i' ) : 'N/A' }}</td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="{{ route( 'admin.users.show', $user->id ) }}"
                                                            class="btn btn-sm btn-outline-primary" title="Ver">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <a href="{{ route( 'admin.users.edit', $user->id ) }}"
                                                            class="btn btn-sm btn-outline-secondary" title="Editar">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" title="Excluir"
                                                            onclick="confirmDelete({{ $user->id }})">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{-- Paginação --}}
                            @if( isset( $users ) && method_exists( $users, 'links' ) )
                                <div class="d-flex justify-content-center">
                                    {{ $users->links() }}
                                </div>
                            @endif
                        @else
                            <div class="text-center py-5">
                                <i class="bi bi-people text-muted fs-1 mb-3"></i>
                                <h5 class="text-muted">Nenhum usuário encontrado</h5>
                                <p class="text-muted mb-4">Comece cadastrando o primeiro usuário do sistema.</p>
                                <a href="{{ route( 'admin.users.create' ) }}" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-2"></i>Cadastrar Primeiro Usuário
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push( 'scripts' )
    <script>
        function confirmDelete( userId ) {
            if ( confirm( 'Tem certeza que deseja excluir este usuário? Esta ação não pode ser desfeita.' ) ) {
                // Criar formulário para DELETE
                const form = document.createElement( 'form' );
                form.method = 'POST';
                form.action = `/admin/users/${userId}`;

                // Adicionar CSRF token
                const csrfInput = document.createElement( 'input' );
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = document.querySelector( 'meta[name="csrf-token"]' )?.getAttribute( 'content' ) || '';

                const methodInput = document.createElement( 'input' );
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'DELETE';

                form.appendChild( csrfInput );
                form.appendChild( methodInput );
                document.body.appendChild( form );
                form.submit();
            }
        }
    </script>
@endpush
