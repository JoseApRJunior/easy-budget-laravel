@extends( 'layout' )

@section( 'content' )
    <div class="container-fluid py-4">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-briefcase me-2"></i>Profissões
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ url( '/admin' ) }}">Dashboard Admin</a></li>
                    <li class="breadcrumb-item active">Profissões</li>
                </ol>
            </nav>
        </div>

        <!-- Botão Adicionar -->
        <div class="mb-4">
            <a href="{{ url( '/admin/professions/create' ) }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Adicionar Profissão
            </a>
        </div>

        <!-- Tabela de Profissões -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                @if ( $professions && count( $professions ) > 0 )
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="px-4 py-3">#</th>
                                    <th scope="col" class="px-4 py-3">Nome</th>
                                    <th scope="col" class="px-4 py-3">Slug</th>
                                    <th scope="col" class="px-4 py-3">Criado em</th>
                                    <th scope="col" class="px-4 py-3 text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ( $professions as $profession )
                                    <tr>
                                        <td class="px-4 py-3 fw-medium">{{ $profession->id }}</td>
                                        <td class="px-4 py-3">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-briefcase text-muted me-2"></i>
                                                {{ $profession->name }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <code class="text-muted">{{ $profession->slug }}</code>
                                        </td>
                                        <td class="px-4 py-3 text-muted">
                                            {{ \Carbon\Carbon::parse( $profession->createdAt )->format( 'd/m/Y H:i' ) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="{{ url( '/admin/professions/' . $profession->id ) }}"
                                                    class="btn btn-sm btn-outline-info" title="Visualizar">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="{{ url( '/admin/professions/' . $profession->id . '/edit' ) }}"
                                                    class="btn btn-sm btn-outline-warning" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" title="Excluir"
                                                    onclick="confirmDelete('{{ $profession->id }}', '{{ $profession->name }}')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bi bi-briefcase text-muted" style="font-size: 3rem;"></i>
                        <h5 class="mt-3 text-muted">Nenhuma profissão encontrada</h5>
                        <p class="text-muted mb-4">Comece adicionando sua primeira profissão.</p>
                        <a href="{{ url( '/admin/professions/create' ) }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Adicionar Profissão
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">
                        <i class="bi bi-exclamation-triangle text-warning me-2"></i>Confirmar Exclusão
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir a profissão <strong id="professionName"></strong>?</p>
                    <p class="text-muted small mb-0">Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form id="deleteForm" method="POST" style="display: inline;">
                        @csrf
                        @method( 'DELETE' )
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash me-2"></i>Excluir
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @section( 'scripts' )
        <script>
            function confirmDelete( professionId, professionName ) {
                document.getElementById( 'professionName' ).textContent = professionName;
                document.getElementById( 'deleteForm' ).action = '{{ url( "/admin/professions" ) }}/' + professionId;

                const modal = new bootstrap.Modal( document.getElementById( 'deleteModal' ) );
                modal.show();
            }
        </script>
    @endsection
@endsection
