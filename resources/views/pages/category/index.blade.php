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
                <p class="text-muted">Lista de categorias do sistema</p>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('categories.dashboard') }}">Categorias</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Listar</li>
                </ol>
            </nav>
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
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="search">Buscar</label>
                                        <input type="text" class="form-control" id="search" name="search"
                                            value="{{ $filters['search'] ?? '' }}"
                                            placeholder="Categoria, Subcategoria, Slug">
                                    </div>
                                </div>
                                <div class="col-md-3">
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
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="per_page">Itens por página</label>
                                        <select class="form-control" id="per_page" name="per_page">
                                            @php($pp = (int) ($filters['per_page'] ?? 10))
                                            <option value="10" {{ $pp === 10 ? 'selected' : '' }}>10</option>
                                            <option value="20" {{ $pp === 20 ? 'selected' : '' }}>20</option>
                                            <option value="50" {{ $pp === 50 ? 'selected' : '' }}>50</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
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
                                    <div class="d-flex gap-2 flex-nowrap">
                                        <button type="submit" id="btnFilterCategories" class="btn btn-primary"
                                            aria-label="Filtrar">
                                            <i class="bi bi-search me-1" aria-hidden="true"></i>Filtrar
                                        </button>
                                        <a href="{{ route('categories.index') }}" class="btn btn-secondary"
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
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-list-ul me-1"></i> Lista de Categorias
                            @if ($categories instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                ({{ $categories->total() }} registros)
                            @else
                                ({{ $categories->count() }} registros)
                            @endif
                            @if (($filters['search'] ?? '') !== '' || ($filters['active'] ?? '') !== '')
                                <span class="badge badge-info ms-2"><i class="bi bi-funnel me-1"
                                        aria-hidden="true"></i>Filtros
                                    ativos</span>
                            @endif
                        </h5>
                        <div class="d-flex gap-2">
                            <a href="{{ route('categories.create') }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus me-1" aria-hidden="true"></i>Nova Categoria
                            </a>
                            <a href="{{ route('categories.export', ['format' => 'xlsx']) }}"
                                class="btn btn-outline-success btn-sm">
                                <i class="bi bi-file-earmark-spreadsheet me-1" aria-hidden="true"></i>Excel
                            </a>
                            <a href="{{ route('categories.export', ['format' => 'pdf']) }}"
                                class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-file-pdf me-1" aria-hidden="true"></i>PDF
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mb-0">
                                @php($isAdminTable = false)
                                @role('admin')
                                    @php($isAdminTable = true)
                                @endrole
                                <thead>
                                    <tr>
                                        <th><i class="bi bi-tag" aria-hidden="true"></i></th>
                                        <th>Categoria</th>
                                        <th>Subcategoria</th>
                                        @if ($isAdminTable)
                                            <th>Slug</th>
                                        @endif
                                        <th>Status</th>
                                        <th>Criado em</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php($tenantId = auth()->user()->tenant_id ?? null)
                                    @forelse( $categories as $category )
                                        <tr>
                                            <td class="text-center">
                                                <i class="bi bi-tag text-muted" aria-hidden="true"></i>
                                            </td>
                                            <td>
                                                {{ $category->parent ? $category->parent->name : $category->name }}
                                                @if (!$category->parent)
                                                    @php($isCustom = $tenantId ? $category->isCustomFor($tenantId) : false)
                                                    @if ($isCustom)
                                                        <span class="badge bg-primary ms-2">Pessoal</span>
                                                    @else
                                                        <span class="badge bg-secondary ms-2">Sistema</span>
                                                    @endif
                                                @endif
                                            </td>
                                            <td>
                                                @if ($category->parent)
                                                    {{ $category->name }}
                                                    @php($isCustom = $tenantId ? $category->isCustomFor($tenantId) : false)
                                                    @if ($isCustom)
                                                        <span class="badge bg-primary ms-2">Pessoal</span>
                                                    @else
                                                        <span class="badge bg-secondary ms-2">Sistema</span>
                                                    @endif
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            @if ($isAdminTable)
                                                <td><span class="text-code">{{ $category->slug }}</span></td>
                                            @endif
                                            <td>
                                                @if ($category->is_active)
                                                    <span class="badge badge-success">Ativo</span>
                                                @else
                                                    <span class="badge badge-danger">Inativo</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php($isGlobalDate = $category->isGlobal())
                                                @php($isAdminDate = false)
                                                @role('admin')
                                                    @php($isAdminDate = true)
                                                @endrole
                                                @if ($isAdminDate || !$isGlobalDate)
                                                    {{ $category->created_at?->format('d/m/Y H:i') }}
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-2">
                                                    @if ($category->deleted_at)
                                                        {{-- Categoria deletada: apenas restaurar --}}
                                                        <form action="{{ route('categories.restore', $category->id) }}"
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
                                                            class="btn btn-info" title="Visualizar"
                                                            aria-label="Visualizar">
                                                            <i class="bi bi-eye" aria-hidden="true"></i>
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
                                                            <a href="{{ route('categories.edit', $category->id) }}"
                                                                class="btn btn-warning" title="Editar"
                                                                aria-label="Editar">
                                                                <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                                            </a>
                                                            @if ($canDelete)
                                                                <button type="button" class="btn btn-danger"
                                                                    data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                                    data-delete-url="{{ route('categories.destroy', $category->id) }}"
                                                                    data-category-name="{{ $category->name }}"
                                                                    title="Excluir" aria-label="Excluir">
                                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                                </button>
                                                            @endif
                                                        @else
                                                            @if (!$isGlobal)
                                                                <a href="{{ route('categories.edit', $category->id) }}"
                                                                    class="btn btn-warning" title="Editar"
                                                                    aria-label="Editar">
                                                                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                                                </a>
                                                                @if ($canDelete)
                                                                    <button type="button" class="btn btn-danger"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#deleteModal"
                                                                        data-delete-url="{{ route('categories.destroy', $category->id) }}"
                                                                        data-category-name="{{ $category->name }}"
                                                                        title="Excluir" aria-label="Excluir">
                                                                        <i class="bi bi-trash" aria-hidden="true"></i>
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
                                            <td colspan="6" class="text-center text-muted">
                                                <i class="bi bi-inbox mb-2" aria-hidden="true"
                                                    style="font-size: 2rem;"></i>
                                                <br>
                                                @if (($filters['deleted'] ?? '') === 'only')
                                                    Nenhuma categoria deletada encontrada.
                                                    <br>
                                                    <small>Você ainda não deletou nenhuma categoria personalizada.</small>
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
                    @if ($categories instanceof \Illuminate\Pagination\LengthAwarePaginator && $categories->hasPages())
                        <div class="card-footer">
                            <div class="d-flex justify-content-center">
                                {{ $categories->appends(request()->query())->links() }}
                            </div>
                        </div>
                    @endif
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
