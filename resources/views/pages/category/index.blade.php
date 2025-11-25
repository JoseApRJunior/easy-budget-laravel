@extends( 'layouts.app' )

@section( 'content' )
<div class="container-fluid py-4">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-tags me-2"></i>Categorias
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route( 'provider.dashboard' ) }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Categorias</li>
            </ol>
        </nav>
    </div>

    @if(session('status'))
    <div class="alert alert-success" role="alert">
        {{ session('status') }}
    </div>
    @endif

    <!-- Botão de Adicionar -->
    <div class="mb-4">
        <a href="{{ route( 'admin.categories.create' ) }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Nova Categoria
        </a>
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
                            <td>
                                {{ $category->name }}
                                @php $pivot = $category->tenants->first()?->pivot; @endphp
                                @if($pivot && $pivot->is_default)
                                <i class="bi bi-star-fill text-warning ms-2" title="Categoria padrão"></i>
                                @endif
                            </td>
                            <td><span class="text-code">{{ $category->slug }}</span></td>
                            <td>{{ $category->created_at->format( 'd/m/Y H:i' ) }}</td>
                            <td class="text-end pe-4">
                                <div class="btn-group">
                                    <a href="{{ route( 'admin.categories.show', $category->id ) }}"
                                        class="btn btn-sm btn-outline-primary" title="Ver Detalhes">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route( 'admin.categories.edit', $category->id ) }}"
                                        class="btn btn-sm btn-outline-secondary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                        data-bs-target="#deleteModal-{{ $category->id }}" title="Excluir">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    @can('manage-custom-categories')
                                    <form action="{{ route('categories.set-default', $category->id) }}" method="POST" class="d-inline ms-1">
                                        @csrf
                                        @if(auth()->user()?->isAdmin())
                                        <input type="number" name="tenant_id" class="form-control form-control-sm d-inline-block" style="width:120px" placeholder="Tenant ID (admin)">
                                        @endif
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Definir como padrão">
                                            <i class="bi bi-star"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">Nenhuma categoria encontrada.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Paginação -->
    @if ( $categories->hasPages() )
    <div class="mt-4 d-flex justify-content-center">
        {{ $categories->links() }}
    </div>
    @endif
</div>

<!-- Modais de Exclusão -->
@foreach ( $categories as $category )
<div class="modal fade" id="deleteModal-{{ $category->id }}" tabindex="-1"
    aria-labelledby="deleteModalLabel-{{ $category->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel-{{ $category->id }}">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Tem certeza de que deseja excluir a categoria <strong>"{{ $category->name }}"</strong>?
                <br><small class="text-muted">Esta ação não pode ser desfeita.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form action="{{ route( 'admin.categories.destroy', $category->id ) }}" method="POST" class="d-inline">
                    @csrf
                    @method( 'DELETE' )
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endforeach

@endsection
