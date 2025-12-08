@extends('layouts.app')

@section('title', 'Categorias')

@section('content')
    <div class="container-fluid py-1">
        <!-- Cabeçalho -->
        <div class="mb-4">
            <div class="row">
                <div class="col-lg-8 col-md-12">
                    <h1 class="h4 mb-1 d-md-none">
                        <i class="bi bi-tags me-2"></i>
                        Categorias
                    </h1>
                    <h1 class="h3 mb-2 d-none d-md-block">
                        <i class="bi bi-tags me-2"></i>
                        Categorias
                    </h1>
                    <p class="text-muted mb-0 small">Lista de categorias do sistema</p>
                </div>
                <div class="col-lg-4 col-md-12 text-lg-end mt-3 mt-lg-0">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb breadcrumb-sm mb-0 justify-content-lg-end">
                            <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('categories.dashboard') }}">Categorias</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Listar</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Filtros de Busca -->
        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-filter me-1"></i> Filtros de Busca</h5>
                    </div>
                    <div class="card-body">
                        <form id="filtersFormCategories" method="GET" action="{{ route('categories.index') }}">
                            <div class="row g-3">
                                <div class="col-lg-6 col-md-12">
                                    <div class="form-group">
                                        <label for="search">Buscar</label>
                                        <input type="text" class="form-control" id="search" name="search"
                                            value="{{ $filters['search'] ?? '' }}"
                                            placeholder="Categoria, Subcategoria, Slug">
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-6 col-6">
                                    <div class="form-group">
                                        <label for="active">Status</label>
                                        <select class="form-control" id="active" name="active">
                                            <option value="">Todos</option>
                                            <option value="1"
                                                {{ ($filters['active'] ?? '') === '1' ? 'selected' : '' }}>
                                                Ativo
                                            </option>
                                            <option value="0"
                                                {{ ($filters['active'] ?? '') === '0' ? 'selected' : '' }}>
                                                Inativo</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-6 col-6">
                                    <div class="form-group">
                                        <label for="per_page" class="text-nowrap">Por página</label>
                                        <select class="form-control" id="per_page" name="per_page">
                                            @php($pp = (int) ($filters['per_page'] ?? 10))
                                            <option value="10" {{ $pp === 10 ? 'selected' : '' }}>10</option>
                                            <option value="20" {{ $pp === 20 ? 'selected' : '' }}>20</option>
                                            <option value="50" {{ $pp === 50 ? 'selected' : '' }}>50</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-6 col-6">
                                    <div class="form-group">
                                        <label for="deleted">Registros</label>
                                        <select class="form-control" id="deleted" name="deleted">
                                            <option value="">Atuais</option>
                                            <option value="only"
                                                {{ ($filters['deleted'] ?? '') === 'only' ? 'selected' : '' }}>Deletados
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="submit" id="btnFilterCategories" class="btn btn-primary"
                                            aria-label="Filtrar">
                                            <i class="bi bi-search me-1" aria-hidden="true"></i>
                                            <span class="d-none d-md-inline">Filtrar</span>
                                            <span class="d-md-none">Filtrar</span>
                                        </button>
                                        <a href="{{ route('categories.index') }}" class="btn btn-secondary"
                                            aria-label="Limpar filtros">
                                            <i class="bi bi-x me-1" aria-hidden="true"></i>
                                            <span class="d-none d-md-inline">Limpar</span>
                                            <span class="d-md-none">Limpar</span>
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
                                <div class="d-flex justify-content-start justify-content-lg-end">
                                    <a href="{{ route('categories.create') }}" class="btn btn-primary btn-sm">
                                        <i class="bi bi-plus" aria-hidden="true"></i>
                                        <span class="ms-1">Nova</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">

                        <!-- Versão Mobile: Cards -->
                        <div class="mobile-view">
                            <div class="p-3">
                                @php($tenantId = auth()->user()->tenant_id ?? null)
                                @php($isAdminMobile = false)
                                @role('admin')
                                    @php($isAdminMobile = true)
                                @endrole
                                @forelse( $categories as $category )
                                    @php($isCustom = $tenantId ? $category->isCustomFor($tenantId) : false)
                                    <div class="modern-card {{ $isCustom ? 'personal' : 'system' }}">
                                        <div class="card-header-mobile">
                                            <div class="card-title-mobile">
                                                <i class="bi bi-tag-fill"></i>
                                                @if ($category->deleted_at)
                                                    <span class="badge bg-danger me-2">Deletada</span>
                                                @endif
                                                {{ $category->parent ? $category->parent->name : $category->name }}
                                            </div>
                                            @if ($category->parent)
                                                <div class="card-subtitle-mobile">
                                                    Subcategoria: {{ $category->name }}
                                                </div>
                                            @endif
                                        </div>


                                        <div class="card-body-mobile">
                                            <div class="info-row">
                                                <span class="info-label">Tipo</span>
                                                <span class="info-value">
                                                    <span
                                                        class="modern-badge {{ $isCustom ? 'badge-personal' : 'badge-system' }}">
                                                        {{ $isCustom ? 'Pessoal' : 'Sistema' }}
                                                    </span>
                                                </span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Status</span>
                                                <span class="info-value">
                                                    <span
                                                        class="modern-badge {{ $category->is_active ? 'badge-active' : 'badge-inactive' }}">
                                                        {{ $category->is_active ? 'Ativo' : 'Inativo' }}
                                                    </span>
                                                </span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Criado em</span>
                                                <span
                                                    class="info-value">{{ $category->created_at?->format('d/m/Y') ?? '—' }}</span>
                                            </div>
                                        </div>

                                        <div class="card-actions-mobile">
                                            @if ($category->deleted_at)
                                                {{-- Categoria deletada: apenas restaurar --}}
                                                <form action="{{ route('categories.restore', $category->id) }}"
                                                    method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success" title="Restaurar"
                                                        aria-label="Restaurar">
                                                        <i class="bi bi-arrow-counterclockwise"></i> Restaurar
                                                    </button>
                                                </form>
                                            @else
                                                {{-- Categoria ativa: show, edit, delete --}}
                                                <a href="{{ route('categories.show', $category->slug) }}"
                                                    class="btn btn-info" title="Visualizar" aria-label="Visualizar">
                                                    <i class="bi bi-eye-fill me-1"></i>Ver
                                                </a>
                                                @php($isGlobal = $category->isGlobal())
                                                @php($hasChildren = $category->hasChildren())
                                                @php($hasServices = $category->services()->exists())
                                                @php($hasProducts = \App\Models\Product::query()->where('category_id', $category->id)->whereNull('deleted_at')->exists())
                                                @php($canDelete = !$hasChildren && !$hasServices && !$hasProducts)
                                                @if ($isAdminMobile)
                                                    <a href="{{ route('categories.edit', $category->slug) }}"
                                                        class="btn btn-warning" title="Editar" aria-label="Editar">
                                                        <i class="bi bi-pencil-fill me-1"></i>Editar
                                                    </a>
                                                    @if ($canDelete)
                                                        <button type="button" class="btn btn-danger"
                                                            data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                            data-delete-url="{{ route('categories.destroy', $category->slug) }}"
                                                            data-category-name="{{ $category->name }}" title="Excluir"
                                                            aria-label="Excluir">
                                                            <i class="bi bi-trash"></i> Excluir
                                                        </button>
                                                    @endif
                                                @else
                                                    @if (!$isGlobal)
                                                        <a href="{{ route('categories.edit', $category->slug) }}"
                                                            class="btn btn-warning" title="Editar" aria-label="Editar">
                                                            <i class="bi bi-pencil-fill me-1"></i>Editar
                                                        </a>
                                                        @if ($canDelete)
                                                            <button type="button" class="btn btn-danger"
                                                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                                data-delete-url="{{ route('categories.destroy', $category->slug) }}"
                                                                data-category-name="{{ $category->name }}"
                                                                title="Excluir" aria-label="Excluir">
                                                                <i class="bi bi-trash"></i> Excluir
                                                            </button>
                                                        @endif
                                                    @endif
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="empty-state">
                                        <div class="empty-state-icon">
                                            <i class="bi bi-inbox"></i>
                                        </div>
                                        <div class="empty-state-title">Nenhuma categoria encontrada</div>
                                        <div class="empty-state-text">
                                            @php($hasActiveFilters = collect($filters)->filter(fn($v) => filled($v))->isNotEmpty())
                                            @if ($hasActiveFilters)
                                                Tente ajustar os filtros ou limpar a busca
                                            @else
                                                Use os filtros acima para buscar categorias
                                            @endif
                                        </div>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Versão Desktop: Tabela -->
                        <div class="desktop-view">
                            <div class="table-responsive">
                                <table class="modern-table table mb-0">
                                    @php($isAdminTable = false)
                                    @role('admin')
                                        @php($isAdminTable = true)
                                    @endrole
                                    <thead>
                                        <tr>
                                            <th width="60"><i class="bi bi-tag" aria-hidden="true"></i></th>
                                            <th>Categoria</th>
                                            <th>Subcategoria</th>
                                            @if ($isAdminTable)
                                                <th>Slug</th>
                                            @endif
                                            <th width="120">Tipo</th>
                                            <th width="120">Status</th>
                                            <th width="150">Criado em</th>
                                            <th width="150" class="text-center">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php($tenantId = auth()->user()->tenant_id ?? null)
                                        @forelse( $categories as $category )
                                            <tr>
                                                <td>
                                                    <div class="item-icon">
                                                        <i class="bi bi-tag-fill"></i>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="item-name-cell">
                                                        @if($category->parent)
                                                            {{ $category->parent->name }}
                                                        @else
                                                            {{ $category->name }}
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($category->parent)
                                                        <span class="text-muted">{{ $category->name }}</span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                @if ($isAdminTable)
                                                    <td><span class="text-code">{{ $category->slug }}</span></td>
                                                @endif
                                                <td>
                                                    @php($isCustom = $tenantId ? $category->isCustomFor($tenantId) : false)
                                                    <span
                                                        class="modern-badge {{ $isCustom ? 'badge-personal' : 'badge-system' }}">
                                                        {{ $isCustom ? 'Pessoal' : 'Sistema' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span
                                                        class="modern-badge {{ $category->is_active ? 'badge-active' : 'badge-inactive' }}">
                                                        {{ $category->is_active ? 'Ativo' : 'Inativo' }}
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
                                                            {{-- Categoria deletada: apenas restaurar --}}
                                                            <form
                                                                action="{{ route('categories.restore', $category->id) }}"
                                                                method="POST" class="d-inline">
                                                                @csrf
                                                                <button type="submit" class="btn btn-success"
                                                                    title="Restaurar" aria-label="Restaurar">
                                                                    <i class="bi bi-arrow-counterclockwise"
                                                                        aria-hidden="true"></i>
                                                                </button>
                                                            </form>
                                                        @else
                                                            {{-- Categoria ativa: show, edit, delete --}}
                                                            <a href="{{ route('categories.show', $category->slug) }}"
                                                                class="action-btn action-btn-view" title="Visualizar">
                                                                <i class="bi bi-eye-fill"></i>
                                                            </a>
                                                            @php($isGlobal = $category->isGlobal())
                                                            @php($isAdmin = false)
                                                            @role('admin')
                                                                @php($isAdmin = true)
                                                            @endrole
                                                            @php($hasChildren = $category->hasChildren())
                                                            @php($hasServices = $category->services()->exists())
                                                            @php($hasProducts = \App\Models\Product::query()->where('category_id', $category->id)->whereNull('deleted_at')->exists())
                                                            @php($canDelete = !$hasChildren && !$hasServices && !$hasProducts)
                                                            @if ($isAdmin)
                                                                <a href="{{ route('categories.edit', $category->slug) }}"
                                                                    class="action-btn action-btn-edit" title="Editar">
                                                                    <i class="bi bi-pencil-fill"></i>
                                                                </a>
                                                                @if ($canDelete)
                                                                    <button type="button"
                                                                        class="action-btn action-btn-delete"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#deleteModal"
                                                                        data-delete-url="{{ route('categories.destroy', $category->slug) }}"
                                                                        data-category-name="{{ $category->name }}"
                                                                        title="Excluir">
                                                                        <i class="bi bi-trash-fill"></i>
                                                                    </button>
                                                                @endif
                                                            @else
                                                                @if (!$isGlobal)
                                                                    <a href="{{ route('categories.edit', $category->slug) }}"
                                                                        class="action-btn action-btn-edit" title="Editar">
                                                                        <i class="bi bi-pencil-fill"></i>
                                                                    </a>
                                                                    @if ($canDelete)
                                                                        <button type="button"
                                                                            class="action-btn action-btn-delete"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#deleteModal"
                                                                            data-delete-url="{{ route('categories.destroy', $category->slug) }}"
                                                                            data-category-name="{{ $category->name }}"
                                                                            title="Excluir">
                                                                            <i class="bi bi-trash-fill"></i>
                                                                        </button>
                                                                    @endif
                                                                @endif
                                                            @endif
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7">
                                                    <div class="empty-state">
                                                        <div class="empty-state-icon">
                                                            <i class="bi bi-inbox"></i>
                                                        </div>
                                                        <div class="empty-state-title">Nenhuma categoria encontrada</div>
                                                        <div class="empty-state-text">
                                                            Tente ajustar os filtros ou criar uma nova categoria
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @if ($categories instanceof \Illuminate\Pagination\LengthAwarePaginator && $categories->hasPages())
                        @php($p = $categories->appends(request()->query()))
                        @include('partials.components.paginator', [
                            'p' => $p,
                            'size' => 'sm',
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
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('assets/js/category.js') }}?v={{ time() }}"></script>
    @endpush
    <div class="modal fade" id="confirmAllCategoriesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Listar todas as categorias?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p>Você não aplicou filtros. Listar todos pode retornar muitos registros.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-confirm-all-categories">Listar todos</button>
                </div>
            </div>
        </div>
    </div>
@endsection
