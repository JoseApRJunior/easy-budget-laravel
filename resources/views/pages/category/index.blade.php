@extends('layouts.app')

@section('title', 'Categorias')

@section('content')
<div class="container-fluid py-1">
    <x-page-header
        title="Categorias"
        icon="tags"
        :breadcrumb-items="[
            'Categorias' => route('provider.categories.dashboard'),
            'Listar' => '#'
        ]">
        <p class="text-muted mb-0">Lista de suas categorias</p>
    </x-page-header>

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
                                    <select class="form-select tom-select" id="active" name="active">
                                        @php($selectedActive = $filters['active'] ?? '1')
                                        <option value="1" {{ $selectedActive === '1' ? 'selected' : '1' }}>
                                            Ativo
                                        </option>
                                        <option value="0" {{ $selectedActive === '0' ? 'selected' : '1' }}>
                                            Inativo
                                        </option>
                                        <option value="all" {{ $selectedActive === 'all' ? 'selected' : '1' }}>
                                            Todos
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="per_page" class="text-nowrap">Por página</label>
                                    <select class="form-select tom-select" id="per_page" name="per_page">
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
                                    <select name="deleted" id="deleted" class="form-select tom-select">
                                        @php($selectedDeleted = $filters['deleted'] ?? 'current')
                                        <option value="current" {{ $selectedDeleted === 'current' ? 'selected' : 'current' }}>
                                            Atuais
                                        </option>
                                        <option value="only" {{ $selectedDeleted === 'only' ? 'selected' : 'current' }}>
                                            Deletados
                                        </option>
                                        <option value="all" {{ $selectedDeleted === 'all' ? 'selected' : 'current' }}>
                                            Todos
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex gap-2 flex-nowrap">
                                    <x-button type="submit" icon="search" label="Filtrar" id="btnFilterCategories" />
                                    <x-button type="link" :href="route('provider.categories.index')" variant="secondary" icon="x" label="Limpar" />
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
                                    <x-button variant="outline-secondary" size="sm" icon="download" label="Exportar" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="exportDropdown" />
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
                                <x-button type="link" :href="route('provider.categories.create')" size="sm" icon="plus" label="Nova" />
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
                                                <x-button type="link" :href="route('provider.categories.show', $category->slug)" variant="info" size="sm" icon="eye" title="Visualizar" />
                                                <x-button variant="success" size="sm" icon="arrow-counterclockwise"
                                                    data-bs-toggle="modal" data-bs-target="#restoreModal"
                                                    data-restore-url="{{ route('provider.categories.restore', $category->slug) }}"
                                                    data-category-name="{{ $category->name }}"
                                                    title="Restaurar" />
                                                @else
                                                <x-button type="link" :href="route('provider.categories.show', $category->slug)" variant="info" size="sm" icon="eye" title="Visualizar" />
                                                @php($canDelete = $category->children_count === 0 && $category->services_count === 0 && $category->products_count === 0)
                                                <x-button type="link" :href="route('provider.categories.edit', $category->slug)" size="sm" icon="pencil-square" title="Editar" />
                                                @if ($canDelete)
                                                <x-button variant="danger" size="sm" icon="trash"
                                                    data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                    data-delete-url="{{ route('provider.categories.destroy', $category->slug) }}"
                                                    data-category-name="{{ $category->name }}"
                                                    title="Excluir" />
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
                                                <x-button type="link" :href="route('provider.categories.show', $category->slug)" variant="info" icon="eye" title="Visualizar" />
                                                <x-button variant="success" icon="arrow-counterclockwise"
                                                    data-bs-toggle="modal" data-bs-target="#restoreModal"
                                                    data-restore-url="{{ route('provider.categories.restore', $category->slug) }}"
                                                    data-category-name="{{ $category->name }}"
                                                    title="Restaurar" />
                                                @else
                                                {{-- Categoria ativa: show, edit, delete --}}
                                                <x-button type="link" :href="route('provider.categories.show', $category->slug)" variant="info" icon="eye" title="Visualizar" />
                                                <x-button type="link" :href="route('provider.categories.edit', $category->slug)" icon="pencil-square" title="Editar" />
                                                @php($canDelete = $category->children_count === 0 && $category->services_count === 0 && $category->products_count === 0)
                                                @if ($canDelete)
                                                <x-button variant="danger" icon="trash"
                                                    data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                    data-delete-url="{{ route('provider.categories.destroy', $category->slug) }}"
                                                    data-category-name="{{ $category->name }}"
                                                    title="Excluir" />
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
                                <x-button variant="secondary" data-bs-dismiss="modal" label="Cancelar" />
                                <form id="deleteForm" action="#" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <x-button type="submit" variant="danger" label="Excluir" />
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
                        <x-button variant="secondary" data-bs-dismiss="modal" label="Cancelar" />
                        <x-button type="button" class="btn-confirm-all-categories" label="Listar todos" />
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
                        <x-button variant="secondary" data-bs-dismiss="modal" label="Cancelar" />
                        <form id="restoreForm" action="#" method="POST" class="d-inline">
                            @csrf
                            <x-button type="submit" variant="success" label="Restaurar" />
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
