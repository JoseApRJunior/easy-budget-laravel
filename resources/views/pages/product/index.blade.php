@extends('layouts.app')

@section('title', 'Produtos')

@section('content')
    <div class="container-fluid py-1">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-box-seam me-2"></i>
                    Produtos
                </h1>
                <p class="text-muted">Lista de todos os produtos registrados no sistema</p>
            </div>
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('provider.products.dashboard') }}">Produtos</a></li>
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
                        <form id="filtersFormProducts" method="GET" action="{{ route('provider.products.index') }}">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="search">Buscar</label>
                                        <input type="text" class="form-control" id="search" name="search"
                                            value="{{ old('search', $filters['search'] ?? '') }}"
                                            placeholder="Nome, SKU ou Descrição">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="category_id">Categoria</label>
                                        <select class="form-control" id="category_id" name="category_id">
                                            <option value="">Todas as categorias</option>
                                            @foreach ($categories as $category)
                                                @if ($category->parent_id === null)
                                                    @if ($category->children->isEmpty())
                                                        <option value="{{ $category->id }}"
                                                            {{ old('category_id', $filters['category_id'] ?? '') == $category->id ? 'selected' : '' }}>
                                                            {{ $category->name }}
                                                        </option>
                                                    @else
                                                        <optgroup label="{{ $category->name }}">
                                                            <option value="{{ $category->id }}"
                                                                {{ old('category_id', $filters['category_id'] ?? '') == $category->id ? 'selected' : '' }}>
                                                                {{ $category->name }} (Geral)
                                                            </option>
                                                            @foreach ($category->children as $subcategory)
                                                                <option value="{{ $subcategory->id }}"
                                                                    {{ old('category_id', $filters['category_id'] ?? '') == $subcategory->id ? 'selected' : '' }}>
                                                                    {{ $subcategory->name }}
                                                                </option>
                                                            @endforeach
                                                        </optgroup>
                                                    @endif
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="active">Status</label>
                                        <select class="form-control" id="active" name="active">
                                            @php($selectedActive = request()->has('active') ? request('active') : '1')
                                            <option value="1" {{ $selectedActive === '1' ? 'selected' : '' }}>
                                                Ativo
                                            </option>
                                            <option value="0" {{ $selectedActive === '0' ? 'selected' : '' }}>
                                                Inativo
                                            </option>
                                            <option value=""
                                                {{ $selectedActive === '' || $selectedActive === null ? 'selected' : '' }}>
                                                Todos
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
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
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="deleted">Registros</label>
                                        <select name="deleted" id="deleted" class="form-control">
                                            @php($selectedDeleted = request()->has('deleted') ? request('deleted') : 'current')
                                            <option value="current" {{ $selectedDeleted === 'current' ? 'selected' : '' }}>
                                                Atuais
                                            </option>
                                            <option value="only" {{ $selectedDeleted === 'only' ? 'selected' : '' }}>
                                                Deletados
                                            </option>
                                            <option value=""
                                                {{ $selectedDeleted === '' || $selectedDeleted === null ? 'selected' : '' }}>
                                                Todos
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="min_price">Preço Mínimo</label>
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="text" class="form-control currency-brl" id="min_price"
                                                name="min_price"
                                                value="{{ old('min_price', $filters['min_price'] ?? '') }}"
                                                inputmode="decimal" placeholder="0,00">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="max_price">Preço Máximo</label>
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="text" class="form-control currency-brl" id="max_price"
                                                name="max_price"
                                                value="{{ old('max_price', $filters['max_price'] ?? '') }}"
                                                inputmode="decimal" placeholder="0,00">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="d-flex gap-2 flex-nowrap">
                                        <button type="submit" id="btnFilterProducts" class="btn btn-primary"
                                            aria-label="Filtrar">
                                            <i class="bi bi-search me-1" aria-hidden="true"></i>Filtrar
                                        </button>
                                        <a href="{{ route('provider.products.index') }}" class="btn btn-secondary">
                                            <i class="bi bi-x me-1"></i>Limpar
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
                                        <span class="d-none d-sm-inline">Lista de Produtos</span>
                                        <span class="d-sm-none">Produtos</span>
                                    </span>
                                    <span class="text-muted" style="font-size: 0.875rem;">
                                        @if ($products instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                            ({{ $products->total() }})
                                        @else
                                            ({{ count($products) }})
                                        @endif
                                    </span>
                                </h5>
                            </div>
                            <div class="col-12 col-lg-4 mt-2 mt-lg-0">
                                <div class="d-flex justify-content-start justify-content-lg-end gap-2">
                                    <div class="dropdown">
                                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                            id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-download me-1" aria-hidden="true"></i> Exportar
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="exportDropdown">
                                            <li>
                                                <a class="dropdown-item"
                                                    href="{{ route('provider.products.export', array_merge(request()->query(), ['format' => 'xlsx', 'deleted' => request('deleted') ?? '', 'search' => request('search') ?? ''])) }}">
                                                    <i class="bi bi-file-earmark-excel me-2 text-success"></i> Excel
                                                    (.xlsx)
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item"
                                                    href="{{ route('provider.products.export', array_merge(request()->query(), ['format' => 'pdf', 'deleted' => request('deleted') ?? '', 'search' => request('search') ?? ''])) }}">
                                                    <i class="bi bi-file-earmark-pdf me-2 text-danger"></i> PDF (.pdf)
                                                </a>
                                            </li>
                                        </ul>
                                    </div>

                                    <a href="{{ route('provider.products.create') }}" class="btn btn-primary btn-sm">
                                        <i class="bi bi-plus" aria-hidden="true"></i>
                                        <span class="ms-1">Novo</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <!-- Desktop View -->
                        <div class="desktop-view">
                            <div class="table-responsive">
                                <table class="modern-table table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Imagem</th>
                                            <th>Nome</th>
                                            <th>SKU</th>
                                            <th>Categoria</th>
                                            <th>Preço</th>
                                            <th>Status</th>
                                            <th class="text-center">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($products as $product)
                                            <tr>
                                                <td class="text-center">
                                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                                        class="img-thumbnail"
                                                        style="width: 50px; height: 50px; object-fit: cover;">
                                                </td>
                                                <td>{{ $product->name }}</td>
                                                <td><span class="text-code">{{ $product->sku }}</span></td>
                                                <td>{{ $product->category->name ?? 'N/A' }}</td>
                                                <td>R$ {{ number_format($product->price, 2, ',', '.') }}</td>
                                                <td>
                                                    <span
                                                        class="modern-badge {{ $product->active ? 'badge-active' : 'badge-inactive' }}">
                                                        {{ $product->active ? 'Ativo' : 'Inativo' }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex justify-content-center gap-2">
                                                        @if ($product->deleted_at)
                                                            {{-- Produto deletado: visualizar e restaurar --}}
                                                            <a href="{{ route('provider.products.show', $product->sku) }}"
                                                                class="btn btn-info" title="Visualizar"
                                                                aria-label="Visualizar">
                                                                <i class="bi bi-eye" aria-hidden="true"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-success"
                                                                data-bs-toggle="modal" data-bs-target="#restoreModal"
                                                                data-restore-url="{{ route('provider.products.restore', $product->sku) }}"
                                                                data-product-name="{{ $product->name }}"
                                                                title="Restaurar" aria-label="Restaurar">
                                                                <i class="bi bi-arrow-counterclockwise"
                                                                    aria-hidden="true"></i>
                                                            </button>
                                                        @else
                                                            {{-- Produto ativo: show, edit, toggle, delete --}}
                                                            <a href="{{ route('provider.products.show', $product->sku) }}"
                                                                class="btn btn-info" title="Visualizar"
                                                                aria-label="Visualizar">
                                                                <i class="bi bi-eye" aria-hidden="true"></i>
                                                            </a>
                                                            <a href="{{ route('provider.products.edit', $product->sku) }}"
                                                                class="btn btn-primary" title="Editar"
                                                                aria-label="Editar">
                                                                <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-danger"
                                                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                                data-delete-url="{{ route('provider.products.destroy', $product->sku) }}"
                                                                data-product-name="{{ $product->name }}" title="Excluir"
                                                                aria-label="Excluir">
                                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                                            </button>
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
                                                        Nenhum produto deletado encontrado.
                                                        <br>
                                                        <small>Você ainda não deletou nenhum produto.</small>
                                                    @else
                                                        Nenhum produto encontrado.
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Mobile View -->
                        <div class="mobile-view">
                            <div class="list-group list-group-flush">
                                @forelse($products as $product)
                                    <div class="list-group-item py-3 {{ $product->deleted_at ? 'bg-light' : '' }}">
                                        <div class="d-flex align-items-start">
                                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                                class="rounded me-2"
                                                style="width: 40px; height: 40px; object-fit: cover;">
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold mb-1">{{ $product->name }}</div>
                                                <div class="d-flex gap-2 flex-wrap mb-2">
                                                    <span class="badge bg-secondary">{{ $product->sku }}</span>
                                                    @if ($product->deleted_at)
                                                        <span class="badge bg-danger">Deletado</span>
                                                    @elseif ($product->active)
                                                        <span class="badge bg-success-subtle text-success">Ativo</span>
                                                    @else
                                                        <span class="badge bg-danger-subtle text-danger">Inativo</span>
                                                    @endif
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">R$
                                                        {{ number_format($product->price, 2, ',', '.') }}</small>
                                                    <div class="d-flex gap-2">
                                                        <a href="{{ route('provider.products.show', $product->sku) }}"
                                                            class="btn btn-sm btn-info" title="Visualizar">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        @if ($product->deleted_at)
                                                            <button type="button" class="btn btn-sm btn-success"
                                                                data-bs-toggle="modal" data-bs-target="#restoreModal"
                                                                data-restore-url="{{ route('provider.products.restore', $product->sku) }}"
                                                                data-product-name="{{ $product->name }}"
                                                                title="Restaurar">
                                                                <i class="bi bi-arrow-counterclockwise"></i>
                                                            </button>
                                                        @else
                                                            <a href="{{ route('provider.products.edit', $product->sku) }}"
                                                                class="btn btn-sm btn-primary" title="Editar">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-danger"
                                                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                                data-delete-url="{{ route('provider.products.destroy', $product->sku) }}"
                                                                data-product-name="{{ $product->name }}" title="Excluir">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
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
                                            Nenhum produto deletado encontrado.
                                        @else
                                            Nenhum produto encontrado.
                                        @endif
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    @if ($products instanceof \Illuminate\Pagination\LengthAwarePaginator && $products->hasPages())
                        @include('partials.components.paginator', [
                            'p' => $products->appends(request()->query()),
                            'show_info' => true,
                        ])
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    Tem certeza de que deseja excluir o produto <strong id="deleteProductName"></strong>?
                    <br><small class="text-muted">Esta ação pode ser desfeita.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form id="deleteForm" action="#" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Excluir</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="restoreModal" tabindex="-1" aria-labelledby="restoreModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="restoreModalLabel">Confirmar Restauração</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    Tem certeza de que deseja restaurar o produto <strong id="restoreProductName"></strong>?
                    <br><small class="text-muted">O produto será restaurado e ficará disponível novamente.</small>
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
    <div class="modal fade" id="confirmAllProductsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Listar todos os produtos?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p>Você não aplicou filtros. Listar todos pode retornar muitos registros.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-confirm-all-products">Listar todos</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('assets/js/product.js') }}?v={{ time() }}"></script>
@endpush
