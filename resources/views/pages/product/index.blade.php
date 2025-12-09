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
                                            value="{{ $filters['search'] ?? '' }}" placeholder="Nome, SKU ou descrição">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="category_id">Categoria</label>
                                        <select class="form-control" id="category_id" name="category_id">
                                            <option value="">Todas</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}"
                                                    {{ ($filters['category_id'] ?? '') == $category->id ? 'selected' : '' }}>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
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
                                        <select class="form-control" id="deleted" name="deleted">
                                            <option value="">Atuais</option>
                                            <option value="only"
                                                {{ ($filters['deleted'] ?? '') === 'only' ? 'selected' : '' }}>Deletados
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
                                                name="min_price" value="{{ $filters['min_price'] ?? '' }}"
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
                                                name="max_price" value="{{ $filters['max_price'] ?? '' }}"
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
                                        <a href="{{ route('provider.products.index') }}" class="btn btn-secondary"
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
                                        <span class="d-none d-sm-inline">Lista de Produtos</span>
                                        <span class="d-sm-none">Produtos</span>
                                    </span>
                                    <span class="text-muted" style="font-size: 0.875rem;">
                                        @if ($products instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                            ({{ $products->total() }})
                                        @else
                                            ({{ $products->count() }})
                                        @endif
                                    </span>
                                </h5>
                            </div>
                            <div class="col-12 col-lg-4 mt-2 mt-lg-0">
                                <div class="d-flex justify-content-start justify-content-lg-end">
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
                                                @if ($product->active)
                                                    <span class="badge badge-success">Ativo</span>
                                                @else
                                                    <span class="badge badge-danger">Inativo</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-2">
                                                    @if ($product->deleted_at)
                                                        {{-- Produto deletado: apenas restaurar --}}
                                                        <form
                                                            action="{{ route('provider.products.restore', $product->sku) }}"
                                                            method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-success"
                                                                title="Restaurar" aria-label="Restaurar">
                                                                <i class="bi bi-arrow-counterclockwise"
                                                                    aria-hidden="true"></i>
                                                            </button>
                                                        </form>
                                                    @else
                                                        {{-- Produto ativo: show, edit, toggle, delete --}}
                                                        <a href="{{ route('provider.products.show', $product->sku) }}"
                                                            class="btn btn-info" title="Visualizar"
                                                            aria-label="Visualizar">
                                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                                        </a>
                                                        <a href="{{ route('provider.products.edit', $product->sku) }}"
                                                            class="btn btn-warning" title="Editar" aria-label="Editar">
                                                            <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                                        </a>
                                                        <form
                                                            action="{{ route('provider.products.toggle-status', $product->sku) }}"
                                                            method="POST" class="d-inline toggle-status-form"
                                                            onsubmit="return confirm('{{ $product->active ? 'Desativar' : 'Ativar' }} este produto?')">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit"
                                                                class="btn {{ $product->active ? 'btn-warning' : 'btn-success' }}"
                                                                title="{{ $product->active ? 'Desativar' : 'Ativar' }}"
                                                                aria-label="{{ $product->active ? 'Desativar' : 'Ativar' }}">
                                                                <i class="bi bi-{{ $product->active ? 'slash-circle' : 'check-lg' }}"
                                                                    aria-hidden="true"></i>
                                                            </button>
                                                        </form>
                                                        <button type="button" class="btn btn-danger"
                                                            data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                            data-delete-url="{{ route('provider.products.destroy', $product->sku) }}"
                                                            data-product-name="{{ $product->name }}"
                                                            title="Excluir" aria-label="Excluir">
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
                                    <a href="{{ route('provider.products.show', $product->sku) }}"
                                        class="list-group-item list-group-item-action py-3">
                                        <div class="d-flex align-items-start">
                                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                                class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold mb-2">{{ $product->name }}</div>
                                                <div class="d-flex gap-2 flex-wrap mb-2">
                                                    <span class="badge bg-secondary">{{ $product->sku }}</span>
                                                    @if ($product->active)
                                                        <span class="badge bg-success-subtle text-success">Ativo</span>
                                                    @else
                                                        <span class="badge bg-danger-subtle text-danger">Inativo</span>
                                                    @endif
                                                </div>
                                                <small class="text-muted">R$ {{ number_format($product->price, 2, ',', '.') }}</small>
                                            </div>
                                            <i class="bi bi-chevron-right text-muted ms-2"></i>
                                        </div>
                                    </a>
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
                        @include('partials.components.paginator', ['p' => $products->appends(request()->query()), 'show_info' => true])
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
                    <br><small class="text-muted">Esta ação não pode ser desfeita.</small>
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
    <script src="{{ asset('assets/js/product.js') }}"></script>
@endpush
