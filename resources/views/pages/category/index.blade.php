@extends('layouts.app')

@section('title', 'Categorias')

@push('styles')
    <style>
        .breadcrumb-sm .breadcrumb-item {
            font-size: 0.875rem;
        }

        .breadcrumb-sm .breadcrumb-item+.breadcrumb-item::before {
            padding-right: 0.3rem;
        }

        .dropdown-menu {
            min-width: 120px;
        }

        /* Responsividade da tabela */
        @media (max-width: 768px) {
            .card-header .btn {
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
            }

            /* Layout mobile - cards em vez de tabela */
            .mobile-cards {
                display: block !important;
            }

            .mobile-cards .table-responsive {
                display: none !important;
            }

            .mobile-card-item {
                border: 1px solid;
                border-radius: 0.375rem;
                margin-bottom: 0.75rem;
                padding: 1rem;

            }

            .mobile-card-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 0.75rem;
            }

            .mobile-card-title {
                font-weight: 600;
                font-size: 1rem;
                margin: 0;
                flex: 1;
            }

            .mobile-card-meta {
                font-size: 0.875rem;
                color: #6c757d;
                margin-bottom: 0.5rem;
            }

            .mobile-card-actions {
                display: flex;
                gap: 0.5rem;
                flex-wrap: wrap;
            }

            .mobile-card-actions .btn {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }

            .mobile-category-type {
                display: inline-block;
                font-size: 0.75rem;
                padding: 0.125rem 0.375rem;
                border-radius: 0.25rem;
                margin-left: 0.5rem;
            }
        }

        /* Desktop - manter tabela */
        @media (min-width: 769px) {
            .mobile-cards {
                display: none !important;
            }
        }

        /* Melhorias gerais */
        .table td {
            vertical-align: middle;
            padding: 0.75rem;
        }

        .category-name {
            font-weight: 500;
            line-height: 1.4;
        }

        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            margin: 0 0.125rem;
        }

        @media (max-width: 576px) {
            .table td {
                padding: 0.5rem 0.25rem;
                font-size: 0.875rem;
            }

            .category-name {
                font-size: 0.875rem;
            }

            .action-buttons .btn {
                padding: 0.2rem 0.4rem;
                font-size: 0.75rem;
            }
        }
    </style>
@endpush

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
                                        <label for="per_page">Itens por página</label>
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
                            <div class="col-lg-8 col-md-12 mb-2 mb-lg-0">
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
                            </div>
                            <div class="col-lg-4 col-md-12">
                                <div class="d-flex flex-wrap gap-1 justify-content-lg-end">
                                    <a href="{{ route('categories.create') }}" class="btn btn-primary btn-sm">
                                        <i class="bi bi-plus me-1" aria-hidden="true"></i>
                                        <span class="d-none d-md-inline">Nova Categoria</span>
                                        <span class="d-md-none">Nova</span>
                                    </a>
                                    <div class="dropdown">
                                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-download" aria-hidden="true"></i>
                                            <span class="ms-1 d-none d-sm-inline">Exportar</span>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item"
                                                    href="{{ route('categories.export', ['format' => 'xlsx']) }}">
                                                    <i class="bi bi-file-earmark-spreadsheet me-2"></i>Excel
                                                </a></li>
                                            <li><a class="dropdown-item"
                                                    href="{{ route('categories.export', ['format' => 'pdf']) }}">
                                                    <i class="bi bi-file-pdf me-2"></i>PDF
                                                </a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">

                        <!-- Versão Mobile: Cards -->
                        <div class="mobile-cards d-block d-md-none">
                            @php($tenantId = auth()->user()->tenant_id ?? null)
                            @php($isAdminMobile = false)
                            @role('admin')
                                @php($isAdminMobile = true)
                            @endrole
                            @forelse( $categories as $category )
                                <div class="mobile-card-item">
                                    <div class="mobile-card-header">
                                        <h5 class="mobile-card-title">
                                            @if ($category->deleted_at)
                                                <span class="badge bg-danger me-2">Deletada</span>
                                            @endif
                                            {{ $category->parent ? $category->parent->name : $category->name }}
                                            @if (!$category->parent)
                                                @php($isCustom = $tenantId ? $category->isCustomFor($tenantId) : false)
                                                @if ($isCustom)
                                                    <span class="mobile-category-type badge bg-primary">Pessoal</span>
                                                @else
                                                    <span class="mobile-category-type badge bg-secondary">Sistema</span>
                                                @endif
                                            @endif
                                        </h5>
                                    </div>

                                    @if ($category->parent)
                                        <div class="mobile-card-meta">
                                            <strong>Subcategoria:</strong> {{ $category->name }}
                                            @php($isCustom = $tenantId ? $category->isCustomFor($tenantId) : false)
                                            @if ($isCustom)
                                                <span class="badge bg-primary ms-2">Pessoal</span>
                                            @else
                                                <span class="badge bg-secondary ms-2">Sistema</span>
                                            @endif
                                        </div>
                                    @endif

                                    @if ($isAdminMobile)
                                        <div class="mobile-card-meta">
                                            <strong>Slug:</strong> <code>{{ $category->slug }}</code>
                                        </div>
                                    @endif

                                    <div class="mobile-card-meta">
                                        <strong>Status:</strong>
                                        @if ($category->is_active)
                                            <span class="badge badge-success">Ativo</span>
                                        @else
                                            <span class="badge badge-danger">Inativo</span>
                                        @endif
                                    </div>

                                    @php($isGlobalDate = $category->isGlobal())
                                    @php($isAdminDate = false)
                                    @role('admin')
                                        @php($isAdminDate = true)
                                    @endrole
                                    @if ($isAdminDate || !$isGlobalDate)
                                        <div class="mobile-card-meta">
                                            <strong>Criado em:</strong> {{ $category->created_at?->format('d/m/Y H:i') }}
                                        </div>
                                    @endif

                                    <div class="mobile-card-actions">
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
                                                <i class="bi bi-eye"></i> Visualizar
                                            </a>
                                            @php($isGlobal = $category->isGlobal())
                                            @php($hasChildren = $category->hasChildren())
                                            @php($hasServices = $category->services()->exists())
                                            @php($hasProducts = \App\Models\Product::query()->where('category_id', $category->id)->whereNull('deleted_at')->exists())
                                            @php($canDelete = !$hasChildren && !$hasServices && !$hasProducts)
                                            @if ($isAdminMobile)
                                                <a href="{{ route('categories.edit', $category->id) }}"
                                                    class="btn btn-warning" title="Editar" aria-label="Editar">
                                                    <i class="bi bi-pencil-square"></i> Editar
                                                </a>
                                                @if ($canDelete)
                                                    <button type="button" class="btn btn-danger" data-bs-toggle="modal"
                                                        data-bs-target="#deleteModal"
                                                        data-delete-url="{{ route('categories.destroy', $category->id) }}"
                                                        data-category-name="{{ $category->name }}" title="Excluir"
                                                        aria-label="Excluir">
                                                        <i class="bi bi-trash"></i> Excluir
                                                    </button>
                                                @endif
                                            @else
                                                @if (!$isGlobal)
                                                    <a href="{{ route('categories.edit', $category->id) }}"
                                                        class="btn btn-warning" title="Editar" aria-label="Editar">
                                                        <i class="bi bi-pencil-square"></i> Editar
                                                    </a>
                                                    @if ($canDelete)
                                                        <button type="button" class="btn btn-danger"
                                                            data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                            data-delete-url="{{ route('categories.destroy', $category->id) }}"
                                                            data-category-name="{{ $category->name }}" title="Excluir"
                                                            aria-label="Excluir">
                                                            <i class="bi bi-trash"></i> Excluir
                                                        </button>
                                                    @endif
                                                @endif
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-search mb-2" style="font-size: 2rem;"></i>
                                    <br>
                                    @php($hasActiveFilters = collect($filters)->filter(fn($v) => filled($v))->isNotEmpty())
                                    @if (($filters['deleted'] ?? '') === 'only')
                                        Nenhuma categoria deletada encontrada.
                                        <br><small>Você ainda não deletou nenhuma categoria personalizada.</small>
                                    @elseif ($hasActiveFilters)
                                        Nenhuma categoria encontrada com os filtros aplicados.
                                        <br><small>Tente ajustar os filtros ou <a
                                                href="{{ route('categories.index') }}">limpar a busca</a>.</small>
                                    @else
                                        Use os filtros acima para buscar categorias.
                                        <br><small>Você pode buscar por nome, status ou visualizar categorias
                                            deletadas.</small>
                                    @endif
                                </div>
                            @endforelse
                        </div>

                        <!-- Versão Desktop: Tabela -->
                        <div class="table-responsive d-none d-md-block">
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
                                            <td colspan="{{ $isAdminTable ? '7' : '6' }}" class="text-center text-muted">
                                                <i class="bi bi-search mb-2" aria-hidden="true"
                                                    style="font-size: 2rem;"></i>
                                                <br>
                                                @php($hasActiveFilters = collect($filters)->filter(fn($v) => filled($v))->isNotEmpty())
                                                @if (($filters['deleted'] ?? '') === 'only')
                                                    Nenhuma categoria deletada encontrada.
                                                    <br>
                                                    <small>Você ainda não deletou nenhuma categoria personalizada.</small>
                                                @elseif ($hasActiveFilters)
                                                    Nenhuma categoria encontrada com os filtros aplicados.
                                                    <br>
                                                    <small>Tente ajustar os filtros ou <a
                                                            href="{{ route('categories.index') }}">limpar a
                                                            busca</a>.</small>
                                                @else
                                                    Use os filtros acima para buscar categorias.
                                                    <br>
                                                    <small>Você pode buscar por nome, status ou visualizar categorias
                                                        deletadas.</small>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
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
