@extends( 'layouts.admin' )

@section( 'breadcrumb' )
<li class="breadcrumb-item active">Categorias</li>
@endsection

@section( 'admin_content' )
<div class="container-fluid py-4">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-tags me-2"></i>Gestão de Categorias
            </h1>
            <p class="text-muted mb-0">Gerencie as categorias do sistema</p>
        </div>

    </div>

    <!-- Filtro simples -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <label for="search" class="form-label">Buscar</label>
                    <input type="text" class="form-control" id="search" name="search" value="{{ request( 'search' ) }}"
                        placeholder="Nome ou slug">
                </div>
                <div class="col-md-3">
                    <label for="sort_by" class="form-label">Ordenar por</label>
                    <select class="form-select" id="sort_by" name="sort_by">
                        <option value="name" {{ request( 'sort_by' ) == 'name' ? 'selected' : '' }}>Nome</option>
                        <option value="slug" {{ request( 'sort_by' ) == 'slug' ? 'selected' : '' }}>Slug</option>
                        <option value="created_at" {{ request( 'sort_by' ) == 'created_at' ? 'selected' : '' }}>Criado em</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="sort_order" class="form-label">Ordem</label>
                    <select class="form-select" id="sort_order" name="sort_order">
                        <option value="asc" {{ request( 'sort_order' ) == 'asc' ? 'selected' : '' }}>Crescente</option>
                        <option value="desc" {{ request( 'sort_order' ) == 'desc' ? 'selected' : '' }}>Decrescente</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i>Filtrar
                    </button>
                    <a href="{{ route( 'admin.categories.index' ) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Limpar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Ações Principais -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <span class="text-muted">Total de categorias: {{ $categories->total() }}</span>
        </div>
        <div>
            <a href="{{ route( 'admin.categories.create' ) }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Nova Categoria
            </a>
            <a href="{{ route( 'admin.categories.export', ['format' => 'xlsx'] ) }}" class="btn btn-outline-success">
                <i class="fas fa-download me-1"></i>Exportar
            </a>
        </div>
    </div>

    <!-- Tabela de Categorias -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th scope="col" class="ps-4">ID</th>
                            <th scope="col">Nome</th>
                            <th scope="col">Slug</th>

                            <th scope="col">Criado em</th>
                            <th scope="col" class="text-end pe-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ( $categories as $category )
                        <tr>
                            <td class="ps-4">{{ $category->id }}</td>
                            <td><strong>{{ $category->name }}</strong></td>
                            <td><code class="text-muted">{{ $category->slug }}</code></td>
                            <td>{{ $category->created_at->format( 'd/m/Y H:i' ) }}</td>
                            <td class="text-end pe-4">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route( 'admin.categories.show', $category->id ) }}"
                                        class="btn btn-outline-primary" title="Ver Detalhes">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route( 'admin.categories.edit', $category->id ) }}"
                                        class="btn btn-outline-secondary" title="Editar">
                                        <i class="fas fa-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal"
                                        data-bs-target="#deleteModal-{{ $category->id }}" title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-tags fa-3x mb-3"></i>
                                    <p>Nenhuma categoria encontrada.</p>
                                    <a href="{{ route( 'admin.categories.create' ) }}" class="btn btn-primary">
                                        <i class="fas fa-plus me-1"></i>Criar Primeira Categoria
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Paginação -->
    @if( $categories->hasPages() )
    <div class="mt-4 d-flex justify-content-center">
        {{ $categories->links() }}
    </div>
    @endif
</div>

<!-- Modais de Exclusão -->
@foreach( $categories as $category )
<div class="modal fade" id="deleteModal-{{ $category->id }}" tabindex="-1"
    aria-labelledby="deleteModalLabel-{{ $category->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel-{{ $category->id }}">
                    <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                    Confirmar Exclusão
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza de que deseja excluir a categoria <strong>"{{ $category->name }}"</strong>?</p>



                <div class="alert alert-danger">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Esta ação não pode ser desfeita.</strong>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancelar
                </button>
                <form action="{{ route( 'admin.categories.destroy', $category->id ) }}" method="POST" class="d-inline">
                    @csrf
                    @method( 'DELETE' )
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Excluir
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endforeach
@endsection

@section( 'scripts' )
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-submit do formulário quando os filtros mudam
        const form = document.querySelector('form');
        const selects = form.querySelectorAll('select');

        selects.forEach(select => {
            select.addEventListener('change', function() {
                form.submit();
            });
        });

        // Confirmação personalizada para exclusão
        const deleteButtons = document.querySelectorAll('[data-bs-target^="#deleteModal"]');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const modal = document.querySelector(this.getAttribute('data-bs-target'));
                const modalTitle = modal.querySelector('.modal-title');
                const categoryName = this.closest('tr').querySelector('td:nth-child(2) strong').textContent;

                modalTitle.innerHTML = '<i class="fas fa-exclamation-triangle text-danger me-2"></i>Confirmar Exclusão';
                modal.querySelector('p').innerHTML = `Tem certeza de que deseja excluir a categoria <strong>"${categoryName}"</strong>?`;

                const modalInstance = new bootstrap.Modal(modal);
                modalInstance.show();
            });
        });
    });
</script>
@endsection
