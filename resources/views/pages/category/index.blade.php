@extends('layouts.app')

@section('title', 'Categorias')

@section('content')
<div class="container-fluid py-1">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-tags me-2"></i>
                Categorias
            </h1>
            <p class="text-muted">Lista de suas categorias </p>
        </div>
        <nav aria-label="breadcrumb" class="d-none d-md-block">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('provider.categories.dashboard') }}">Categorias</a></li>
                <li class="breadcrumb-item active" aria-current="page">Listar</li>
            </ol>
        </nav>
    </div>

    <div class="row">
        <div class="col-12">
            <!-- Filtros de Busca -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-filter me-1"></i> Filtros de Busca</h5>
                </div>
                <div class="card-body">
                    <form id="filtersFormCategories" method="GET" action="{{ route('provider.categories.index') }}">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="search">Buscar</label>
                                    <input type="text" class="form-control" id="search" name="search"
                                        value="{{ old('search', $filters['search'] ?? '') }}"
                                        placeholder="Categoria, Subcategoria">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="active">Status</label>
                                    <select class="form-control" id="active" name="active">
                                        @php($selectedActive = empty($filters) ? '1' : $filters['active'] ?? '')
                                        <option value="1" {{ $selectedActive === '1' ? 'selected' : '' }}>
                                            Ativo
                                        </option>
                                        <option value="0" {{ $selectedActive === '0' ? 'selected' : '' }}>
                                            Inativo
                                        </option>
                                        <option value="" {{ $selectedActive === '' ? 'selected' : '' }}>
                                            Todos
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="per_page" class="text-nowrap">Por página</label>
                                    <select class="form-control" id="per_page" name="per_page">
                                        @php($pp = (int) request('per_page', 10))
                                        <option value="10" {{ $pp === 10 ? 'selected' : '' }}>10</option>
                                        <option value="20" {{ $pp === 20 ? 'selected' : '' }}>20</option>
                                        <option value="50" {{ $pp === 50 ? 'selected' : '' }}>50</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="deleted">Registros</label>
                                    <select name="deleted" id="deleted" class="form-control">
                                        @php($selectedDeleted = empty($filters) ? 'current' : $filters['deleted'] ?? '')
                                        <option value="current" {{ $selectedDeleted === 'current' ? 'selected' : '' }}>
                                            Atuais
                                        </option>
                                        <option value="only" {{ $selectedDeleted === 'only' ? 'selected' : '' }}>
                                            Deletados
                                        </option>
                                        <option value="" {{ $selectedDeleted === '' ? 'selected' : '' }}>
                                            Todos
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex gap-2 flex-nowrap">
                                    <button type="submit" id="btnFilterCategories" class="btn btn-primary"
                                        aria-label="Filtrar">
                                        <i class="bi bi-search me-1" aria-hidden="true"></i>Filtrar
                                    </button>
                                    <a href="{{ route('provider.categories.index') }}" class="btn btn-secondary"
                                        aria-label="Limpar filtros">
                                        <i class="bi bi-x me-1" aria-hidden="true"></i>Limpar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-12 col-lg-8 mb-2 mb-lg-0">
                            <h5 class="mb-0 d-flex align-items-center flex-wrap">
                                <span class="me-2">
                                    <i class="bi bi-list-ul me-1"></i>
                                    <span class="d-none d-sm-inline">Lista de Categorias</span>
                                    <span class="d-sm-none">Categorias</span>
                                </span>
                                <span class="text-muted" style="font-size: 0.875rem;">
                                    @if ($categories instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                    ({{ $categories->total() }})
                                    @else
                                    ({{ $categories->count() }})
                                    @endif
                                </span>
                            </h5>
                        </div>
                        <div class="col-12 col-lg-4 mt-2 mt-lg-0">
                            <div class="d-flex justify-content-start justify-content-lg-end gap-2">
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                        id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-download me-1"></i> Exportar
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="exportDropdown">
                                        <li>
                                            <a class="dropdown-item"
                                                href="{{ route('provider.categories.export', array_merge(request()->query(), ['format' => 'xlsx', 'deleted' => request('deleted') ?? '', 'search' => request('search') ?? ''])) }}">
                                                <i class="bi bi-file-earmark-excel me-2 text-success"></i> Excel (.xlsx)
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item"
                                                href="{{ route('provider.categories.export', array_merge(request()->query(), ['format' => 'pdf', 'deleted' => request('deleted') ?? '', 'search' => request('search') ?? ''])) }}">
                                                <i class="bi bi-file-earmark-pdf me-2 text-danger"></i> PDF (.pdf)
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                <a href="{{ route('provider.categories.create') }}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus" aria-hidden="true"></i>
                                    <span class="ms-1">Nova</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">

                    <!-- Mobile View -->
                    <div class="mobile-view">
                        <div class="list-group list-group-flush">
                            @forelse($categories as $category)
                            <div class="list-group-item py-3">
                                <div class="d-flex align-items-start">
                                    <div class="me-3 mt-1">
                                        <div class="avatar-circle"
                                            style="width: 40px; height: 40px; background-color: var(--primary-color); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                            <i class="bi bi-tag-fill"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold mb-1">
                                            {{ $category->parent ? $category->parent->name : $category->name }}
                                        </div>
                                        <div class="d-flex gap-2 flex-wrap mb-2">
                                            <span
                                                class="modern-badge {{ $category->deleted_at ? 'badge-deleted' : ($category->is_active ? 'badge-active' : 'badge-inactive') }}">
                                                {{ $category->deleted_at ? 'Deletado' : ($category->is_active ? 'Ativo' : 'Inativo') }}
                                            </span>
                                        </div>
                                        @if ($category->parent)
                                        <div class="mb-2">
                                            <small class="text-muted">Subcategoria:
                                                {{ $category->name }}</small>
                                        </div>
                                        @endif

                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <small class="text-muted">
                                                {{ $category->created_at?->format('d/m/Y') ?? '—' }}
                                            </small>
                                            <div class="d-flex gap-2">
                                                @if ($category->deleted_at)
                                                <a href="{{ route('provider.categories.show', $category->slug) }}"
                                                    class="btn btn-sm btn-info" title="Visualizar">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-success"
                                                    data-bs-toggle="modal" data-bs-target="#restoreModal"
                                                    data-restore-url="{{ route('provider.categories.restore', $category->slug) }}"
                                                    data-category-name="{{ $category->name }}"
                                                    title="Restaurar">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </button>
                                                @else
                                                <a href="{{ route('provider.categories.show', $category->slug) }}"
                                                    class="btn btn-sm btn-info" title="Visualizar">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                @php($canDelete = $category->children_count === 0 && $category->services_count === 0 && $category->products_count === 0)
                                                <a href="{{ route('provider.categories.edit', $category->slug) }}"
                                                    class="btn btn-sm btn-primary" title="Editar">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                @if ($canDelete)
                                                <button type="button" class="btn btn-sm btn-danger"
                                                    data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                    data-delete-url="{{ route('provider.categories.destroy', $category->slug) }}"
                                                    data-category-name="{{ $category->name }}"
                                                    title="Excluir">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                                @endif
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="p-4 text-center text-muted">
                                <i class="bi bi-inbox mb-2" style="font-size: 2rem;"></i>
                                <br>
                                @if (($filters['deleted'] ?? '') === 'only')
                                Nenhuma categoria deletada encontrada.
                                @else
                                Nenhuma categoria encontrada.
                                @endif
                            </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Versão Desktop: Tabela -->
                    <div class="desktop-view">
                        <div class="table-responsive">
                            <table class="modern-table table mb-0">
                                <thead>
                                    <tr>
                                        <th width="60"><i class="bi bi-tag" aria-hidden="true"></i></th>
                                        <th>Categoria</th>
                                        <th>Subcategoria</th>
                                        <th width="120">Status</th>
                                        <th width="150">Criado em</th>
                                        <th width="150" class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($categories as $category)
                                    <tr>
                                        <td>
                                            <div class="item-icon">
                                                <i class="bi bi-tag-fill"></i>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="item-name-cell">
                                                @if ($category->parent)
                                                {{ $category->parent->name }}
                                                @else
                                                {{ $category->name }}
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if ($category->parent)
                                            <span class="text-muted">{{ $category->name }}</span>
                                            @else
                                            <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span
                                                class="modern-badge {{ $category->deleted_at ? 'badge-deleted' : ($category->is_active ? 'badge-active' : 'badge-inactive') }}">
                                                {{ $category->deleted_at ? 'Deletado' : ($category->is_active ? 'Ativo' : 'Inativo') }}
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $category->created_at?->format('d/m/Y H:i') ?? '—' }}
                                            </small>
                                        </td>
                                        <td>
                                            <div class="action-btn-group">
                                                @if ($category->deleted_at)
                                                {{-- Categoria deletada: visualizar e restaurar --}}
                                                <a href="{{ route('provider.categories.show', $category->slug) }}"
                                                    class="btn btn-info" title="Visualizar"
                                                    aria-label="Visualizar">
                                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                                </a>
                                                <button type="button" class="btn btn-success"
                                                    data-bs-toggle="modal" data-bs-target="#restoreModal"
                                                    data-restore-url="{{ route('provider.categories.restore', $category->slug) }}"
                                                    data-category-name="{{ $category->name }}"
                                                    title="Restaurar" aria-label="Restaurar">
                                                    <i class="bi bi-arrow-counterclockwise"
                                                        aria-hidden="true"></i>
                                                </button>
                                                @else
                                                {{-- Categoria ativa: show, edit, delete --}}
                                                <a href="{{ route('provider.categories.show', $category->slug) }}"
                                                    class="btn btn-info" title="Visualizar"
                                                    aria-label="Visualizar">
                                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                                </a>
                                                @php($canDelete = $category->children_count === 0 && $category->services_count === 0 && $category->products_count === 0)
                                                <a href="{{ route('provider.categories.edit', $category->slug) }}"
                                                    class="btn btn-primary" title="Editar"
                                                    aria-label="Editar">
                                                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                                </a>
                                                @if ($canDelete)
                                                <button type="button" class="btn btn-danger"
                                                    data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                    data-delete-url="{{ route('provider.categories.destroy', $category->slug) }}"
                                                    data-category-name="{{ $category->name }}"
                                                    title="Excluir" aria-label="Excluir">
                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                </button>
                                                @endif
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            <i class="bi bi-inbox mb-2" aria-hidden="true"
                                                style="font-size: 2rem;"></i>
                                            <br>
                                            @if (($filters['deleted'] ?? '') === 'only')
                                            Nenhuma categoria deletada encontrada.
                                            <br>
                                            <small>Você ainda não deletou nenhuma categoria.</small>
                                            @else
                                            Nenhuma categoria encontrada.
                                            @endif
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @if ($categories instanceof \Illuminate\Pagination\LengthAwarePaginator && $categories->hasPages())
                @include('partials.components.paginator', [
                'p' => $categories->appends(
                collect(request()->query())->map(fn($v) => is_null($v) ? '' : $v)->toArray()),
                'show_info' => true,
                ])
                @endif
                <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Fechar"></button>
                            </div>
                            <div class="modal-body">
                                Tem certeza de que deseja excluir a categoria <strong
                                    id="deleteCategoryName"></strong>?
                                <br><small class="text-muted">Esta ação não pode ser desfeita.</small>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    data-bs-dismiss="modal">Cancelar</button>
                                <form id="deleteForm" action="#" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">Excluir</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="confirmAllCategoriesModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Listar todas as categorias?</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <p>Você não aplicou filtros. Listar todos pode retornar muitos registros.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary btn-confirm-all-categories">Listar
                            todos</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Confirmação de Restauração -->
        <div class="modal fade" id="restoreModal" tabindex="-1" aria-labelledby="restoreModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="restoreModalLabel">Confirmar Restauração</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        Tem certeza de que deseja restaurar a categoria <strong id="restoreCategoryName"></strong>?
                        <br><small class="text-muted">A categoria será restaurada e ficará disponível
                            novamente.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <form id="restoreForm" action="#" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success">Restaurar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/category.js') }}?v={{ time() }}"></script>
<script>
    // Script para os modais
    document.addEventListener('DOMContentLoaded', function() {
        // Modal de exclusão
        const deleteModal = document.getElementById('deleteModal');
        if (deleteModal) {
            deleteModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const deleteUrl = button.getAttribute('data-delete-url');
                const categoryName = button.getAttribute('data-category-name');

                const deleteCategoryName = deleteModal.querySelector('#deleteCategoryName');
                const deleteForm = deleteModal.querySelector('#deleteForm');

                deleteCategoryName.textContent = categoryName;
                deleteForm.action = deleteUrl;
            });
        }

        // Modal de restauração
        const restoreModal = document.getElementById('restoreModal');
        if (restoreModal) {
            restoreModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const restoreUrl = button.getAttribute('data-restore-url');
                const categoryName = button.getAttribute('data-category-name');

                const restoreCategoryName = restoreModal.querySelector('#restoreCategoryName');
                const restoreForm = restoreModal.querySelector('#restoreForm');

                restoreCategoryName.textContent = categoryName;
                restoreForm.action = restoreUrl;
            });
        }
    });
</script>
@endpush
